<?php
declare(strict_types=1);

/*
    filename: fix_admin_backup_files_zip_reopen_v001_20260903_013300pm.php
    VERSION: v001
    LAST MODIFIED: 9/3/2026 1:33:00 pm

    PURPOSE:
    - Corrects the first-run MRL Files Backup "Could not reopen ZIP. Code: 9" issue.
    - Updates admin_backup_files_helper.php v001 -> v002.
    - Makes a rollback copy before changing the helper.
    - Does not alter DB backups, completed ZIPs, admin_backup.php, or the DB helper.

    ROOT CAUSE:
    - The v001 batch start created an entirely empty ZipArchive and immediately
      closed it. On this server, an empty archive was not left as a physical file.
      The next AJAX batch therefore tried to reopen a ZIP path that did not exist.
    - v002 puts a tiny temporary marker entry in the new archive so it persists
      between requests, then removes that marker before the final ZIP is completed.
*/

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $root . DIRECTORY_SEPARATOR . 'admin_backup_files_helper.php';
$rollbackDir = $root . DIRECTORY_SEPARATOR . '_migration_backups'
    . DIRECTORY_SEPARATOR . 'fix_admin_backup_files_zip_reopen_v001_20260903_013300pm';
$rollbackFile = $rollbackDir . DIRECTORY_SEPARATOR . 'admin_backup_files_helper.php';

function fx_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function fx_write_atomic(string $path, string $content): array {
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return [false, 'Could not write temporary file: ' . $tmp];
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return [false, 'Could not replace target file: ' . $path];
    }
    return [true, ''];
}

function fx_php_syntax_ok(string $path): array {
    $php = PHP_BINARY ?: 'php';
    $cmd = escapeshellarg($php) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = [];
    $code = 1;
    @exec($cmd, $out, $code);
    if ($code === 0) return [true, implode("\n", $out)];

    // Some hosts disable exec(). In that case, report INFO rather than falsely fail.
    if (empty($out)) return [null, 'Server-side PHP CLI syntax check unavailable; installer structural checks still apply.'];
    return [false, implode("\n", $out)];
}

$current = is_file($target) ? (string)@file_get_contents($target) : '';
$isExpectedV001 = $current !== ''
    && strpos($current, 'VERSION: v001') !== false
    && strpos($current, "Could not reopen ZIP. Code: ") !== false
    && strpos($current, "ZipArchive::CREATE | ZipArchive::OVERWRITE") !== false;

$zipAvailable = class_exists('ZipArchive');
$rootWritable = is_writable($root);
$payload = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKgogICAgZmlsZW5hbWU6IGFkbWluX2JhY2t1cF9maWxlc19oZWxwZXIucGhwCiAgICBWRVJTSU9OOiB2MDAyCiAgICBMQVNUIE1PRElGSUVEOiA5LzMvMjAyNiAxOjMzOjAwIHBtCgogICAgUFVSUE9TRToKICAgIC0gTVJMIHdlYnNpdGUtZmlsZXMgYmFja3VwIGhlbHBlciBmb3IgYWRtaW5fYmFja3VwLnBocC4KICAgIC0gQmFja3MgdXAgcHVibGljX2h0bWwgZXhjZXB0IHdwLWFkbWluLCB3cC1pbmNsdWRlcywgd3AtY29udGVudC4KICAgIC0gU3RvcmVzIFpJUHMgb3V0c2lkZSBwdWJsaWNfaHRtbC4KICAgIC0gUHJlc2VydmVzIGVudHJ5IG10aW1lcyB3aGVuIFppcEFyY2hpdmU6OnNldE10aW1lTmFtZSBpcyBhdmFpbGFibGUuCiAgICAtIEluY2x1ZGVzIF9iYWNrdXBfbWFuaWZlc3QuanNvbiB3aXRoIHBhdGgsIHR5cGUsIG10aW1lLCBzaXplLCBwZXJtaXNzaW9ucywgU0hBLTI1Ni4KICAgIC0gVXNlcyBzbWFsbCBBSkFYIGJhdGNoZXMgdG8gYXZvaWQgb25lIGxvbmcgYnJvd3NlciByZXF1ZXN0LgoKICAgIENIQU5HRUxPRzoKICAgIHYwMDIgKDkvMy8yMDI2IDE6MzM6MDAgcG0pCiAgICAtIEZJWDogS2VlcHMgYSB0ZW1wb3JhcnkgbWFya2VyIGVudHJ5IGluIGEgbmV3bHkgY3JlYXRlZCBaSVAgc28gdGhlIGFyY2hpdmUKICAgICAgcGh5c2ljYWxseSBleGlzdHMgYW5kIGNhbiBiZSByZW9wZW5lZCBieSB0aGUgbmV4dCBBSkFYIGJhdGNoIHJlcXVlc3QuCiAgICAtIEZJWDogUmVtb3ZlcyB0aGF0IG1hcmtlciBiZWZvcmUgdGhlIGZpbmFsIFpJUCBpcyBjb21wbGV0ZWQuCiAgICAtIFNBRkVUWTogVmVyaWZpZXMgdGhlIHJldXNhYmxlIFpJUCBmaWxlIGV4aXN0cyBiZWZvcmUgc2F2aW5nIGJhdGNoIHN0YXRlLgoKICAgIHYwMDEgKDkvMy8yMDI2IDEyOjU5OjIzIHBtKQogICAgLSBJbml0aWFsIE1STCBmaWxlLWJhY2t1cCBoZWxwZXIuCiovCgppZiAoIWRlZmluZWQoJ01STF9CQUNLVVBfTUFOQUdFUl9FTlRSWScpKSB7CiAgICBodHRwX3Jlc3BvbnNlX2NvZGUoNDAzKTsKICAgIGVjaG8gJzwhZG9jdHlwZSBodG1sPjxodG1sPjxoZWFkPjxtZXRhIGNoYXJzZXQ9InV0Zi04Ij48dGl0bGU+TVJMIEJhY2t1cCBIZWxwZXI8L3RpdGxlPjwvaGVhZD48Ym9keSBzdHlsZT0iZm9udC1mYW1pbHk6QXJpYWw7YmFja2dyb3VuZDojMTExO2NvbG9yOiNlZWU7cGFkZGluZzozMHB4Ij4nOwogICAgZWNobyAnPGgxIHN0eWxlPSJjb2xvcjojZDhjMDhhIj5NUkwgQmFja3VwIEhlbHBlcjwvaDE+JzsKICAgIGVjaG8gJzxwPlRoaXMgZmlsZSBpcyBwYXJ0IG9mIDxjb2RlPmFkbWluX2JhY2t1cC5waHA8L2NvZGU+IGFuZCBpcyBub3QgaW50ZW5kZWQgdG8gYmUgcnVuIGRpcmVjdGx5LjwvcD4nOwogICAgZWNobyAnPHA+PGEgc3R5bGU9ImNvbG9yOiM4ZWM1ZmYiIGhyZWY9ImFkbWluX2JhY2t1cC5waHAiPk9wZW4gdGhlIEJhY2t1cCBNYW5hZ2VyPC9hPjwvcD4nOwogICAgZWNobyAnPC9ib2R5PjwvaHRtbD4nOwogICAgZXhpdDsKfQoKZGF0ZV9kZWZhdWx0X3RpbWV6b25lX3NldCgnQW1lcmljYS9OZXdfWW9yaycpOwoKJHNvdXJjZURpciA9IHJ0cmltKChzdHJpbmcpKCRfU0VSVkVSWydET0NVTUVOVF9ST09UJ10gPz8gX19ESVJfXyksICcvXFwnKTsKJGRvbWFpblJvb3QgPSBkaXJuYW1lKCRzb3VyY2VEaXIpOwokYmFja3VwUm9vdCA9ICRkb21haW5Sb290IC4gRElSRUNUT1JZX1NFUEFSQVRPUiAuICdfbXJsX2JhY2t1cHMnIC4gRElSRUNUT1JZX1NFUEFSQVRPUiAuICdmaWxlcyc7CiRzdGF0ZURpciA9ICRiYWNrdXBSb290IC4gRElSRUNUT1JZX1NFUEFSQVRPUiAuICcuc3RhdGUnOwokZXhjbHVkZWRUb3AgPSBbJ3dwLWFkbWluJywgJ3dwLWluY2x1ZGVzJywgJ3dwLWNvbnRlbnQnXTsKJGJhdGNoRmlsZXMgPSAxMjA7CgpmdW5jdGlvbiBtcmxmYl9oKCR2KTogc3RyaW5nIHsgcmV0dXJuIGh0bWxzcGVjaWFsY2hhcnMoKHN0cmluZykkdiwgRU5UX1FVT1RFUywgJ1VURi04Jyk7IH0KZnVuY3Rpb24gbXJsZmJfanNvbihhcnJheSAkZGF0YSwgaW50ICRzdGF0dXMgPSAyMDApOiB2b2lkIHsKICAgIGh0dHBfcmVzcG9uc2VfY29kZSgkc3RhdHVzKTsKICAgIGhlYWRlcignQ29udGVudC1UeXBlOiBhcHBsaWNhdGlvbi9qc29uOyBjaGFyc2V0PXV0Zi04Jyk7CiAgICBoZWFkZXIoJ0NhY2hlLUNvbnRyb2w6IG5vLXN0b3JlJyk7CiAgICBlY2hvIGpzb25fZW5jb2RlKCRkYXRhLCBKU09OX1VORVNDQVBFRF9TTEFTSEVTIHwgSlNPTl9VTkVTQ0FQRURfVU5JQ09ERSk7CiAgICBleGl0Owp9CmZ1bmN0aW9uIG1ybGZiX2Vuc3VyZV9kaXIoc3RyaW5nICRkaXIpOiBib29sIHsKICAgIHJldHVybiBpc19kaXIoJGRpcikgPyBpc193cml0YWJsZSgkZGlyKSA6IChAbWtkaXIoJGRpciwgMDc1NSwgdHJ1ZSkgJiYgaXNfd3JpdGFibGUoJGRpcikpOwp9CmZ1bmN0aW9uIG1ybGZiX3NhZmVfaWQoc3RyaW5nICRpZCk6IHN0cmluZyB7IHJldHVybiBwcmVnX3JlcGxhY2UoJy9bXkEtWmEtejAtOV8tXS8nLCAnJywgJGlkKSA/OiAnJzsgfQpmdW5jdGlvbiBtcmxmYl9zdGF0ZV9wYXRoKHN0cmluZyAkc3RhdGVEaXIsIHN0cmluZyAkaWQpOiBzdHJpbmcgeyByZXR1cm4gJHN0YXRlRGlyIC4gRElSRUNUT1JZX1NFUEFSQVRPUiAuICdiYWNrdXBfJyAuICRpZCAuICcuanNvbic7IH0KZnVuY3Rpb24gbXJsZmJfcmVhZF9zdGF0ZShzdHJpbmcgJHN0YXRlRGlyLCBzdHJpbmcgJGlkKTogYXJyYXkgewogICAgJHAgPSBtcmxmYl9zdGF0ZV9wYXRoKCRzdGF0ZURpciwgJGlkKTsgaWYgKCFpc19maWxlKCRwKSkgcmV0dXJuIFtdOwogICAgJGogPSBqc29uX2RlY29kZSgoc3RyaW5nKUBmaWxlX2dldF9jb250ZW50cygkcCksIHRydWUpOyByZXR1cm4gaXNfYXJyYXkoJGopID8gJGogOiBbXTsKfQpmdW5jdGlvbiBtcmxmYl93cml0ZV9zdGF0ZShzdHJpbmcgJHN0YXRlRGlyLCBzdHJpbmcgJGlkLCBhcnJheSAkc3RhdGUpOiBib29sIHsKICAgIHJldHVybiBAZmlsZV9wdXRfY29udGVudHMobXJsZmJfc3RhdGVfcGF0aCgkc3RhdGVEaXIsICRpZCksIGpzb25fZW5jb2RlKCRzdGF0ZSwgSlNPTl9QUkVUVFlfUFJJTlQgfCBKU09OX1VORVNDQVBFRF9TTEFTSEVTKSwgTE9DS19FWCkgIT09IGZhbHNlOwp9CmZ1bmN0aW9uIG1ybGZiX3JlbChzdHJpbmcgJHJvb3QsIHN0cmluZyAkcGF0aCk6IHN0cmluZyB7CiAgICAkciA9IGx0cmltKHN0cl9yZXBsYWNlKCdcXCcsJy8nLCBzdWJzdHIoJHBhdGgsIHN0cmxlbigkcm9vdCkpKSwgJy8nKTsgcmV0dXJuICRyOwp9CmZ1bmN0aW9uIG1ybGZiX3NjYW4oc3RyaW5nICRyb290LCBhcnJheSAkZXhjbHVkZWRUb3ApOiBhcnJheSB7CiAgICAkaXRlbXM9W107ICR0b3RhbEJ5dGVzPTA7ICRkaXJzPTA7ICRmaWxlcz0wOwogICAgJGZsYWdzID0gRmlsZXN5c3RlbUl0ZXJhdG9yOjpTS0lQX0RPVFM7CiAgICAkZGlySXQgPSBuZXcgUmVjdXJzaXZlRGlyZWN0b3J5SXRlcmF0b3IoJHJvb3QsICRmbGFncyk7CiAgICAkZmlsdGVyID0gbmV3IFJlY3Vyc2l2ZUNhbGxiYWNrRmlsdGVySXRlcmF0b3IoJGRpckl0LCBmdW5jdGlvbigkY3VycmVudCwgJGtleSwgJGl0ZXJhdG9yKSB1c2UgKCRyb290LCAkZXhjbHVkZWRUb3ApIHsKICAgICAgICAkcmVsID0gbXJsZmJfcmVsKCRyb290LCAkY3VycmVudC0+Z2V0UGF0aG5hbWUoKSk7CiAgICAgICAgJHRvcCA9IGV4cGxvZGUoJy8nLCAkcmVsLCAyKVswXSA/PyAnJzsKICAgICAgICBpZiAoJGN1cnJlbnQtPmlzRGlyKCkgJiYgaW5fYXJyYXkoJHRvcCwgJGV4Y2x1ZGVkVG9wLCB0cnVlKSkgcmV0dXJuIGZhbHNlOwogICAgICAgIHJldHVybiB0cnVlOwogICAgfSk7CiAgICAkaXQgPSBuZXcgUmVjdXJzaXZlSXRlcmF0b3JJdGVyYXRvcigkZmlsdGVyLCBSZWN1cnNpdmVJdGVyYXRvckl0ZXJhdG9yOjpTRUxGX0ZJUlNUKTsKICAgIGZvcmVhY2ggKCRpdCBhcyAkaW5mbykgewogICAgICAgICRwYXRoPSRpbmZvLT5nZXRQYXRobmFtZSgpOyAkcmVsPW1ybGZiX3JlbCgkcm9vdCwkcGF0aCk7IGlmICgkcmVsPT09JycpIGNvbnRpbnVlOwogICAgICAgICR0b3AgPSBleHBsb2RlKCcvJywgJHJlbCwgMilbMF0gPz8gJyc7CiAgICAgICAgaWYgKGluX2FycmF5KCR0b3AsICRleGNsdWRlZFRvcCwgdHJ1ZSkpIGNvbnRpbnVlOwogICAgICAgIGlmICgkaW5mby0+aXNMaW5rKCkpIGNvbnRpbnVlOwogICAgICAgIGlmICgkaW5mby0+aXNEaXIoKSkgeyAkZGlycysrOyAkaXRlbXNbXT1bJ3BhdGgnPT4kcGF0aCwncmVsJz0+JHJlbCwndHlwZSc9PidkaXInLCdzaXplJz0+MCwnbXRpbWUnPT4kaW5mby0+Z2V0TVRpbWUoKSwncGVybXMnPT4kaW5mby0+Z2V0UGVybXMoKSAmIDA3NzddOyB9CiAgICAgICAgZWxzZWlmICgkaW5mby0+aXNGaWxlKCkpIHsgJHNpemU9JGluZm8tPmdldFNpemUoKTsgJGZpbGVzKys7ICR0b3RhbEJ5dGVzICs9ICRzaXplOyAkaXRlbXNbXT1bJ3BhdGgnPT4kcGF0aCwncmVsJz0+JHJlbCwndHlwZSc9PidmaWxlJywnc2l6ZSc9PiRzaXplLCdtdGltZSc9PiRpbmZvLT5nZXRNVGltZSgpLCdwZXJtcyc9PiRpbmZvLT5nZXRQZXJtcygpICYgMDc3N107IH0KICAgIH0KICAgIHVzb3J0KCRpdGVtcyxmdW5jdGlvbigkYSwkYil7cmV0dXJuIHN0cmNtcCgkYVsncmVsJ10sJGJbJ3JlbCddKTt9KTsKICAgIHJldHVybiBbJ2l0ZW1zJz0+JGl0ZW1zLCdmaWxlcyc9PiRmaWxlcywnZGlycyc9PiRkaXJzLCdieXRlcyc9PiR0b3RhbEJ5dGVzXTsKfQpmdW5jdGlvbiBtcmxmYl9saXN0X3ppcHMoc3RyaW5nICRyb290KTogYXJyYXkgewogICAgaWYgKCFpc19kaXIoJHJvb3QpKSByZXR1cm4gW107CiAgICAkZmlsZXM9Z2xvYigkcm9vdCAuIERJUkVDVE9SWV9TRVBBUkFUT1IgLiAncHVibGljX2h0bWxfKi56aXAnKSA/OiBbXTsKICAgIHVzb3J0KCRmaWxlcyxmdW5jdGlvbigkYSwkYil7cmV0dXJuIChAZmlsZW10aW1lKCRiKT86MCkgPD0+IChAZmlsZW10aW1lKCRhKT86MCk7fSk7CiAgICByZXR1cm4gJGZpbGVzOwp9CmZ1bmN0aW9uIG1ybGZiX3ZhbGlkX3ppcChzdHJpbmcgJG5hbWUpOiBib29sIHsKICAgIHJldHVybiBiYXNlbmFtZSgkbmFtZSk9PT0kbmFtZSAmJiBwcmVnX21hdGNoKCcvXnB1YmxpY19odG1sX1xkezh9X1xkezl9KD86YW18cG0pXC56aXAkLycsICRuYW1lKSA9PT0gMTsKfQpmdW5jdGlvbiBtcmxmYl9mbXRfYnl0ZXMoaW50ICRiKTogc3RyaW5nIHsKICAgICR1PVsnQicsJ0tCJywnTUInLCdHQiddOyAkaT0wOyAkdj0oZmxvYXQpJGI7IHdoaWxlKCR2Pj0xMDI0ICYmICRpPGNvdW50KCR1KS0xKXskdi89MTAyNDskaSsrO30KICAgIHJldHVybiBudW1iZXJfZm9ybWF0KCR2LCRpPT09MD8wOjIpLicgJy4kdVskaV07Cn0KCmlmICghbXJsZmJfZW5zdXJlX2RpcigkYmFja3VwUm9vdCkgfHwgIW1ybGZiX2Vuc3VyZV9kaXIoJHN0YXRlRGlyKSkgewogICAgJHN0b3JhZ2VFcnJvciA9ICdCYWNrdXAgc3RvcmFnZSBkaXJlY3RvcnkgY291bGQgbm90IGJlIGNyZWF0ZWQgb3IgaXMgbm90IHdyaXRhYmxlOiAnIC4gJGJhY2t1cFJvb3Q7Cn0gZWxzZSB7ICRzdG9yYWdlRXJyb3IgPSAnJzsgfQoKJGFqYXggPSAoc3RyaW5nKSgkX1JFUVVFU1RbJ2ZpbGVzX2FjdGlvbiddID8/ICcnKTsKaWYgKCRhamF4ICE9PSAnJykgewogICAgaWYgKCRzdG9yYWdlRXJyb3IgIT09ICcnKSBtcmxmYl9qc29uKFsnb2snPT5mYWxzZSwnZXJyb3InPT4kc3RvcmFnZUVycm9yXSw1MDApOwogICAgaWYgKCFjbGFzc19leGlzdHMoJ1ppcEFyY2hpdmUnKSkgbXJsZmJfanNvbihbJ29rJz0+ZmFsc2UsJ2Vycm9yJz0+J1BIUCBaaXBBcmNoaXZlIGlzIG5vdCBhdmFpbGFibGUuJ10sNTAwKTsKCiAgICBpZiAoJGFqYXggPT09ICdwcmVmbGlnaHQnKSB7CiAgICAgICAgJHNjYW49bXJsZmJfc2Nhbigkc291cmNlRGlyLCRleGNsdWRlZFRvcCk7CiAgICAgICAgJGZyZWU9QGRpc2tfZnJlZV9zcGFjZSgkZG9tYWluUm9vdCk7IGlmICgkZnJlZT09PWZhbHNlKSAkZnJlZT0wOwogICAgICAgIG1ybGZiX2pzb24oWydvayc9PnRydWUsJ3NvdXJjZSc9PiRzb3VyY2VEaXIsJ2Rlc3RpbmF0aW9uJz0+JGJhY2t1cFJvb3QsJ2ZpbGVzJz0+JHNjYW5bJ2ZpbGVzJ10sJ2RpcnMnPT4kc2NhblsnZGlycyddLCdieXRlcyc9PiRzY2FuWydieXRlcyddLCdieXRlc19sYWJlbCc9Pm1ybGZiX2ZtdF9ieXRlcygkc2NhblsnYnl0ZXMnXSksJ2ZyZWVfYnl0ZXMnPT4oaW50KSRmcmVlLCdmcmVlX2xhYmVsJz0+bXJsZmJfZm10X2J5dGVzKChpbnQpJGZyZWUpLCdtdGltZV9zdXBwb3J0ZWQnPT5tZXRob2RfZXhpc3RzKCdaaXBBcmNoaXZlJywnc2V0TXRpbWVOYW1lJyksJ2V4Y2x1ZGVkJz0+JGV4Y2x1ZGVkVG9wXSk7CiAgICB9CgogICAgaWYgKCRhamF4ID09PSAnc3RhcnQnKSB7CiAgICAgICAgJHNjYW49bXJsZmJfc2Nhbigkc291cmNlRGlyLCRleGNsdWRlZFRvcCk7CiAgICAgICAgJGlkPWRhdGUoJ1ltZF9IaXMnKSAuICdfJyAuIGJpbjJoZXgocmFuZG9tX2J5dGVzKDMpKTsKICAgICAgICAkc3RhbXA9ZGF0ZSgnWW1kX2hpcycpIC4gc3ByaW50ZignJTAzZCcsKGludClmbG9vcigobWljcm90aW1lKHRydWUpLWZsb29yKG1pY3JvdGltZSh0cnVlKSkpKjEwMDApKSAuIGRhdGUoJ2EnKTsKICAgICAgICAkemlwTmFtZT0ncHVibGljX2h0bWxfJyAuICRzdGFtcCAuICcuemlwJzsKICAgICAgICAkZmluYWxQYXRoPSRiYWNrdXBSb290IC4gRElSRUNUT1JZX1NFUEFSQVRPUiAuICR6aXBOYW1lOwogICAgICAgICR6aXBQYXRoPSRmaW5hbFBhdGggLiAnLnBhcnQnOwogICAgICAgICRzdGF0ZT1bJ2lkJz0+JGlkLCdzdGF0dXMnPT4ncnVubmluZycsJ3ppcF9uYW1lJz0+JHppcE5hbWUsJ3ppcF9wYXRoJz0+JHppcFBhdGgsJ2ZpbmFsX3BhdGgnPT4kZmluYWxQYXRoLCdzb3VyY2UnPT4kc291cmNlRGlyLCdjcmVhdGVkX2F0Jz0+ZGF0ZSgnYycpLCdpbmRleCc9PjAsJ2l0ZW1zJz0+JHNjYW5bJ2l0ZW1zJ10sJ2ZpbGVzX3RvdGFsJz0+JHNjYW5bJ2ZpbGVzJ10sJ2RpcnNfdG90YWwnPT4kc2NhblsnZGlycyddLCdieXRlc190b3RhbCc9PiRzY2FuWydieXRlcyddLCdmaWxlc19kb25lJz0+MCwnZGlyc19kb25lJz0+MCwnYnl0ZXNfZG9uZSc9PjAsJ21hbmlmZXN0Jz0+W10sJ2Vycm9yJz0+JyddOwogICAgICAgICR6aXA9bmV3IFppcEFyY2hpdmUoKTsgJHJlcz0kemlwLT5vcGVuKCR6aXBQYXRoLCBaaXBBcmNoaXZlOjpDUkVBVEUgfCBaaXBBcmNoaXZlOjpPVkVSV1JJVEUpOwogICAgICAgIGlmICgkcmVzIT09dHJ1ZSkgbXJsZmJfanNvbihbJ29rJz0+ZmFsc2UsJ2Vycm9yJz0+J0NvdWxkIG5vdCBjcmVhdGUgWklQLiBaaXBBcmNoaXZlIGNvZGU6ICcuJHJlc10sNTAwKTsKCiAgICAgICAgLy8gSU1QT1JUQU5UOiBhbiBlbnRpcmVseSBlbXB0eSBaaXBBcmNoaXZlIG1heSBub3QgbGVhdmUgYSBwaHlzaWNhbCBaSVAgZmlsZQogICAgICAgIC8vIGJlaGluZCB3aGVuIGl0IGlzIGNsb3NlZC4gVGhlIGJhdGNoIHdvcmtlciBtdXN0IHJlb3BlbiB0aGUgYXJjaGl2ZSBpbiB0aGUKICAgICAgICAvLyBuZXh0IHJlcXVlc3QsIHNvIGFkZCBhIHRlbXBvcmFyeSBtYXJrZXIgZW50cnkgbm93IGFuZCByZW1vdmUgaXQgYXQgY29tcGxldGlvbi4KICAgICAgICBpZiAoISR6aXAtPmFkZEZyb21TdHJpbmcoJy5fbXJsX2JhY2t1cF9pbl9wcm9ncmVzcycsICdNUkwgZmlsZSBiYWNrdXAgaW4gcHJvZ3Jlc3MnKSkgewogICAgICAgICAgICAkemlwLT5jbG9zZSgpOwogICAgICAgICAgICBAdW5saW5rKCR6aXBQYXRoKTsKICAgICAgICAgICAgbXJsZmJfanNvbihbJ29rJz0+ZmFsc2UsJ2Vycm9yJz0+J0NvdWxkIG5vdCBpbml0aWFsaXplIFpJUCBmb3IgYmF0Y2hlZCBiYWNrdXAuJ10sNTAwKTsKICAgICAgICB9CgogICAgICAgICR6aXAtPmNsb3NlKCk7CgogICAgICAgIGlmICghaXNfZmlsZSgkemlwUGF0aCkpIHsKICAgICAgICAgICAgbXJsZmJfanNvbihbJ29rJz0+ZmFsc2UsJ2Vycm9yJz0+J1pJUCBpbml0aWFsaXphdGlvbiBkaWQgbm90IGNyZWF0ZSBhIHJldXNhYmxlIGFyY2hpdmUgZmlsZS4nXSw1MDApOwogICAgICAgIH0KCiAgICAgICAgaWYgKCFtcmxmYl93cml0ZV9zdGF0ZSgkc3RhdGVEaXIsJGlkLCRzdGF0ZSkpIHsgQHVubGluaygkemlwUGF0aCk7IG1ybGZiX2pzb24oWydvayc9PmZhbHNlLCdlcnJvcic9PidDb3VsZCBub3Qgd3JpdGUgYmFja3VwIHN0YXRlIGZpbGUuJ10sNTAwKTsgfQogICAgICAgIG1ybGZiX2pzb24oWydvayc9PnRydWUsJ2lkJz0+JGlkLCd6aXBfbmFtZSc9PiR6aXBOYW1lLCdmaWxlc190b3RhbCc9PiRzY2FuWydmaWxlcyddLCdieXRlc190b3RhbCc9PiRzY2FuWydieXRlcyddXSk7CiAgICB9CgogICAgaWYgKCRhamF4ID09PSAnc3RlcCcpIHsKICAgICAgICAkaWQ9bXJsZmJfc2FmZV9pZCgoc3RyaW5nKSgkX1BPU1RbJ2lkJ10/PycnKSk7ICRzdGF0ZT1tcmxmYl9yZWFkX3N0YXRlKCRzdGF0ZURpciwkaWQpOwogICAgICAgIGlmICghJHN0YXRlKSBtcmxmYl9qc29uKFsnb2snPT5mYWxzZSwnZXJyb3InPT4nQmFja3VwIHN0YXRlIG5vdCBmb3VuZC4nXSw0MDQpOwogICAgICAgIGlmICgoJHN0YXRlWydzdGF0dXMnXT8/JycpPT09J2NvbXBsZXRlJykgbXJsZmJfanNvbihbJ29rJz0+dHJ1ZSwnY29tcGxldGUnPT50cnVlLCdzdGF0ZSc9PiRzdGF0ZV0pOwogICAgICAgICR6aXA9bmV3IFppcEFyY2hpdmUoKTsgJHJlcz0kemlwLT5vcGVuKChzdHJpbmcpJHN0YXRlWyd6aXBfcGF0aCddKTsKICAgICAgICBpZiAoJHJlcyE9PXRydWUpIG1ybGZiX2pzb24oWydvayc9PmZhbHNlLCdlcnJvcic9PidDb3VsZCBub3QgcmVvcGVuIFpJUC4gQ29kZTogJy4kcmVzXSw1MDApOwogICAgICAgICRzdGFydD0oaW50KSRzdGF0ZVsnaW5kZXgnXTsgJGVuZD1taW4oY291bnQoJHN0YXRlWydpdGVtcyddKSwkc3RhcnQrJGJhdGNoRmlsZXMpOwogICAgICAgIGZvcigkaT0kc3RhcnQ7JGk8JGVuZDskaSsrKXsKICAgICAgICAgICAgJGl0PSRzdGF0ZVsnaXRlbXMnXVskaV07ICRyZWw9KHN0cmluZykkaXRbJ3JlbCddOyAkcGF0aD0oc3RyaW5nKSRpdFsncGF0aCddOwogICAgICAgICAgICAkZW50cnk9WydwYXRoJz0+JHJlbCwndHlwZSc9PiRpdFsndHlwZSddLCdtdGltZSc9PihpbnQpJGl0WydtdGltZSddLCdzaXplJz0+KGludCkkaXRbJ3NpemUnXSwncGVybWlzc2lvbnMnPT5zcHJpbnRmKCclMDRvJywoaW50KSRpdFsncGVybXMnXSksJ3NoYTI1Nic9Pm51bGxdOwogICAgICAgICAgICBpZiAoJGl0Wyd0eXBlJ109PT0nZGlyJykgewogICAgICAgICAgICAgICAgJHppcC0+YWRkRW1wdHlEaXIocnRyaW0oJHJlbCwnLycpLicvJyk7CiAgICAgICAgICAgICAgICBpZiAobWV0aG9kX2V4aXN0cygkemlwLCdzZXRNdGltZU5hbWUnKSkgQCAkemlwLT5zZXRNdGltZU5hbWUocnRyaW0oJHJlbCwnLycpLicvJywoaW50KSRpdFsnbXRpbWUnXSk7CiAgICAgICAgICAgICAgICAkc3RhdGVbJ2RpcnNfZG9uZSddKys7CiAgICAgICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICAgICBpZiAoIWlzX2ZpbGUoJHBhdGgpIHx8ICEkemlwLT5hZGRGaWxlKCRwYXRoLCRyZWwpKSB7ICR6aXAtPmNsb3NlKCk7ICRzdGF0ZVsnc3RhdHVzJ109J2Vycm9yJzsgJHN0YXRlWydlcnJvciddPSdDb3VsZCBub3QgYWRkIGZpbGU6ICcuJHJlbDsgbXJsZmJfd3JpdGVfc3RhdGUoJHN0YXRlRGlyLCRpZCwkc3RhdGUpOyBtcmxmYl9qc29uKFsnb2snPT5mYWxzZSwnZXJyb3InPT4kc3RhdGVbJ2Vycm9yJ11dLDUwMCk7IH0KICAgICAgICAgICAgICAgIGlmIChtZXRob2RfZXhpc3RzKCR6aXAsJ3NldE10aW1lTmFtZScpKSBAICR6aXAtPnNldE10aW1lTmFtZSgkcmVsLChpbnQpJGl0WydtdGltZSddKTsKICAgICAgICAgICAgICAgICRlbnRyeVsnc2hhMjU2J109QGhhc2hfZmlsZSgnc2hhMjU2JywkcGF0aCkgPzogbnVsbDsKICAgICAgICAgICAgICAgICRzdGF0ZVsnZmlsZXNfZG9uZSddKys7ICRzdGF0ZVsnYnl0ZXNfZG9uZSddICs9IChpbnQpJGl0WydzaXplJ107CiAgICAgICAgICAgIH0KICAgICAgICAgICAgJHN0YXRlWydtYW5pZmVzdCddW109JGVudHJ5OyAkc3RhdGVbJ2luZGV4J109JGkrMTsKICAgICAgICB9CiAgICAgICAgJGNvbXBsZXRlPSRzdGF0ZVsnaW5kZXgnXT49Y291bnQoJHN0YXRlWydpdGVtcyddKTsKICAgICAgICBpZiAoJGNvbXBsZXRlKSB7CiAgICAgICAgICAgIC8vIFJlbW92ZSB0aGUgdGVtcG9yYXJ5IG1hcmtlciB0aGF0IHdhcyB1c2VkIG9ubHkgdG8gbWFrZSB0aGUgYXJjaGl2ZQogICAgICAgICAgICAvLyBwZXJzaXN0IGJldHdlZW4gQUpBWCByZXF1ZXN0cy4KICAgICAgICAgICAgQCR6aXAtPmRlbGV0ZU5hbWUoJy5fbXJsX2JhY2t1cF9pbl9wcm9ncmVzcycpOwoKICAgICAgICAgICAgJG1hbmlmZXN0PVsnYmFja3VwX3ZlcnNpb24nPT4xLCdjcmVhdGVkX2F0Jz0+JHN0YXRlWydjcmVhdGVkX2F0J10sJ3NvdXJjZSc9PiRzdGF0ZVsnc291cmNlJ10sJ2V4Y2x1ZGVkX3RvcF9sZXZlbCc9PiRleGNsdWRlZFRvcCwnZmlsZV9jb3VudCc9PiRzdGF0ZVsnZmlsZXNfdG90YWwnXSwnZGlyZWN0b3J5X2NvdW50Jz0+JHN0YXRlWydkaXJzX3RvdGFsJ10sJ3NvdXJjZV9ieXRlcyc9PiRzdGF0ZVsnYnl0ZXNfdG90YWwnXSwnZW50cmllcyc9PiRzdGF0ZVsnbWFuaWZlc3QnXV07CiAgICAgICAgICAgICRtYW5pZmVzdEpzb249anNvbl9lbmNvZGUoJG1hbmlmZXN0LEpTT05fUFJFVFRZX1BSSU5UfEpTT05fVU5FU0NBUEVEX1NMQVNIRVMpOwogICAgICAgICAgICAkemlwLT5hZGRGcm9tU3RyaW5nKCdfYmFja3VwX21hbmlmZXN0Lmpzb24nLCRtYW5pZmVzdEpzb249PT1mYWxzZT8ne30nOiRtYW5pZmVzdEpzb24pOwogICAgICAgICAgICAkc3RhdGVbJ3N0YXR1cyddPSdjb21wbGV0ZSc7ICRzdGF0ZVsnY29tcGxldGVkX2F0J109ZGF0ZSgnYycpOwogICAgICAgIH0KICAgICAgICAkemlwLT5jbG9zZSgpOwogICAgICAgIGlmICgkY29tcGxldGUpIHsKICAgICAgICAgICAgJGZpbmFsUGF0aD0oc3RyaW5nKSgkc3RhdGVbJ2ZpbmFsX3BhdGgnXSA/PyAnJyk7CiAgICAgICAgICAgIGlmICgkZmluYWxQYXRoPT09JycgfHwgIUByZW5hbWUoKHN0cmluZykkc3RhdGVbJ3ppcF9wYXRoJ10sJGZpbmFsUGF0aCkpIHsKICAgICAgICAgICAgICAgICRzdGF0ZVsnc3RhdHVzJ109J2Vycm9yJzsgJHN0YXRlWydlcnJvciddPSdaSVAgZmluaXNoZWQgYnV0IGNvdWxkIG5vdCBiZSByZW5hbWVkIHRvIGl0cyBmaW5hbCBmaWxlbmFtZS4nOyBtcmxmYl93cml0ZV9zdGF0ZSgkc3RhdGVEaXIsJGlkLCRzdGF0ZSk7IG1ybGZiX2pzb24oWydvayc9PmZhbHNlLCdlcnJvcic9PiRzdGF0ZVsnZXJyb3InXV0sNTAwKTsKICAgICAgICAgICAgfQogICAgICAgICAgICAkc3RhdGVbJ3ppcF9wYXRoJ109JGZpbmFsUGF0aDsKICAgICAgICAgICAgJHN0YXRlWyd6aXBfYnl0ZXMnXT1AZmlsZXNpemUoJGZpbmFsUGF0aCkgPzogMDsKICAgICAgICAgICAgdW5zZXQoJHN0YXRlWydpdGVtcyddLCRzdGF0ZVsnbWFuaWZlc3QnXSk7CiAgICAgICAgfQogICAgICAgIG1ybGZiX3dyaXRlX3N0YXRlKCRzdGF0ZURpciwkaWQsJHN0YXRlKTsKICAgICAgICBtcmxmYl9qc29uKFsnb2snPT50cnVlLCdjb21wbGV0ZSc9PiRjb21wbGV0ZSwnZmlsZXNfZG9uZSc9PiRzdGF0ZVsnZmlsZXNfZG9uZSddLCdmaWxlc190b3RhbCc9PiRzdGF0ZVsnZmlsZXNfdG90YWwnXSwnZGlyc19kb25lJz0+JHN0YXRlWydkaXJzX2RvbmUnXSwnYnl0ZXNfZG9uZSc9PiRzdGF0ZVsnYnl0ZXNfZG9uZSddLCdieXRlc190b3RhbCc9PiRzdGF0ZVsnYnl0ZXNfdG90YWwnXSwnemlwX25hbWUnPT4kc3RhdGVbJ3ppcF9uYW1lJ10sJ3ppcF9ieXRlcyc9PiRzdGF0ZVsnemlwX2J5dGVzJ10/PzBdKTsKICAgIH0KCiAgICBpZiAoJGFqYXggPT09ICdkb3dubG9hZCcpIHsKICAgICAgICAkbmFtZT1iYXNlbmFtZSgoc3RyaW5nKSgkX0dFVFsnZmlsZSddPz8nJykpOyBpZighbXJsZmJfdmFsaWRfemlwKCRuYW1lKSkgeyBodHRwX3Jlc3BvbnNlX2NvZGUoNDAwKTsgZXhpdCgnSW52YWxpZCBiYWNrdXAgZmlsZW5hbWUuJyk7IH0KICAgICAgICAkcGF0aD0kYmFja3VwUm9vdC5ESVJFQ1RPUllfU0VQQVJBVE9SLiRuYW1lOyBpZighaXNfZmlsZSgkcGF0aCkpe2h0dHBfcmVzcG9uc2VfY29kZSg0MDQpO2V4aXQoJ0JhY2t1cCBub3QgZm91bmQuJyk7fQogICAgICAgIGhlYWRlcignQ29udGVudC1UeXBlOiBhcHBsaWNhdGlvbi96aXAnKTsgaGVhZGVyKCdDb250ZW50LURpc3Bvc2l0aW9uOiBhdHRhY2htZW50OyBmaWxlbmFtZT0iJy4kbmFtZS4nIicpOyBoZWFkZXIoJ0NvbnRlbnQtTGVuZ3RoOiAnLmZpbGVzaXplKCRwYXRoKSk7IGhlYWRlcignQ2FjaGUtQ29udHJvbDogbm8tc3RvcmUnKTsKICAgICAgICAkZmg9Zm9wZW4oJHBhdGgsJ3JiJyk7IHdoaWxlKCFmZW9mKCRmaCkpe2VjaG8gZnJlYWQoJGZoLDEwMjQqMTAyNCk7IGZsdXNoKCk7fSBmY2xvc2UoJGZoKTsgZXhpdDsKICAgIH0KCiAgICBpZiAoJGFqYXggPT09ICdkZWxldGUnKSB7CiAgICAgICAgJG5hbWU9YmFzZW5hbWUoKHN0cmluZykoJF9QT1NUWydmaWxlJ10/PycnKSk7IGlmKCFtcmxmYl92YWxpZF96aXAoJG5hbWUpKSBtcmxmYl9qc29uKFsnb2snPT5mYWxzZSwnZXJyb3InPT4nSW52YWxpZCBiYWNrdXAgZmlsZW5hbWUuJ10sNDAwKTsKICAgICAgICAkcGF0aD0kYmFja3VwUm9vdC5ESVJFQ1RPUllfU0VQQVJBVE9SLiRuYW1lOyBpZighaXNfZmlsZSgkcGF0aCkpIG1ybGZiX2pzb24oWydvayc9PmZhbHNlLCdlcnJvcic9PidCYWNrdXAgbm90IGZvdW5kLiddLDQwNCk7CiAgICAgICAgaWYoIUB1bmxpbmsoJHBhdGgpKSBtcmxmYl9qc29uKFsnb2snPT5mYWxzZSwnZXJyb3InPT4nQ291bGQgbm90IGRlbGV0ZSBiYWNrdXAuJ10sNTAwKTsKICAgICAgICBtcmxmYl9qc29uKFsnb2snPT50cnVlXSk7CiAgICB9CiAgICBtcmxmYl9qc29uKFsnb2snPT5mYWxzZSwnZXJyb3InPT4nVW5rbm93biBmaWxlcyBiYWNrdXAgYWN0aW9uLiddLDQwMCk7Cn0KCiR6aXBzPW1ybGZiX2xpc3RfemlwcygkYmFja3VwUm9vdCk7Cj8+CjwhZG9jdHlwZSBodG1sPgo8aHRtbCBsYW5nPSJlbiI+PGhlYWQ+PG1ldGEgY2hhcnNldD0idXRmLTgiPjxtZXRhIG5hbWU9InZpZXdwb3J0IiBjb250ZW50PSJ3aWR0aD1kZXZpY2Utd2lkdGgsaW5pdGlhbC1zY2FsZT0xIj48dGl0bGU+TVJMIEJhY2t1cCBNYW5hZ2VyIC0gRmlsZXM8L3RpdGxlPgo8c3R5bGU+CmJvZHl7bWFyZ2luOjA7YmFja2dyb3VuZDojMTExO2NvbG9yOiNlZWU7Zm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWZ9LndyYXB7bWF4LXdpZHRoOjExMDBweDttYXJnaW46MThweCBhdXRvIDQwcHg7cGFkZGluZzowIDE0cHh9LmNhcmR7YmFja2dyb3VuZDojMWIxYjFiO2JvcmRlcjoxcHggc29saWQgIzMzMztib3JkZXItcmFkaXVzOjEwcHg7cGFkZGluZzoxOHB4O21hcmdpbi1ib3R0b206MTZweDtib3gtc2hhZG93OjAgOHB4IDI0cHggcmdiYSgwLDAsMCwuMzUpfWgxe2NvbG9yOiNkOGMwOGE7Zm9udC1zaXplOjM0cHg7bWFyZ2luOjAgMCA4cHh9LnRpdGxle2ZvbnQtc2l6ZToyMHB4O2ZvbnQtd2VpZ2h0OjcwMDtjb2xvcjojZmZkMThhfS5tdXRlZHtjb2xvcjojYWFhO2ZvbnQtc2l6ZToxM3B4O2xpbmUtaGVpZ2h0OjEuNX0ubW9ub3tmb250LWZhbWlseTp1aS1tb25vc3BhY2UsU0ZNb25vLVJlZ3VsYXIsTWVubG8sQ29uc29sYXMsbW9ub3NwYWNlfS5idG57Ym9yZGVyOjA7Ym9yZGVyLXJhZGl1czo4cHg7cGFkZGluZzoxMXB4IDE2cHg7Zm9udC13ZWlnaHQ6NzAwO2ZvbnQtc2l6ZToxNXB4O3RleHQtZGVjb3JhdGlvbjpub25lO2N1cnNvcjpwb2ludGVyO2Rpc3BsYXk6aW5saW5lLWJsb2NrfS5zYWZle2JhY2tncm91bmQ6IzIzN2E0NTtjb2xvcjojZmZmfS5ibHVle2JhY2tncm91bmQ6IzJmNmZlYjtjb2xvcjojZmZmfS5kYW5nZXJ7YmFja2dyb3VuZDojYjgyYjJiO2NvbG9yOiNmZmZ9LmdyYXl7YmFja2dyb3VuZDojNTU1ZjZkO2NvbG9yOiNmZmZ9LmJ0bjpkaXNhYmxlZHtiYWNrZ3JvdW5kOiM1NTU7Y29sb3I6I2JiYjtjdXJzb3I6bm90LWFsbG93ZWR9LnJvd3tkaXNwbGF5OmZsZXg7Z2FwOjEwcHg7YWxpZ24taXRlbXM6Y2VudGVyO2ZsZXgtd3JhcDp3cmFwO21hcmdpbi10b3A6MTJweH0uc3RhdHVze21hcmdpbi10b3A6MTJweDtwYWRkaW5nOjEycHg7Ym9yZGVyLXJhZGl1czo4cHg7YmFja2dyb3VuZDojMTIyODNhO2JvcmRlcjoxcHggc29saWQgIzI3NWQ4Mn0ub2t7YmFja2dyb3VuZDojMTIzZDI2O2JvcmRlci1jb2xvcjojMjM3YTQ1O2NvbG9yOiNlYWZmZWZ9LmVycntiYWNrZ3JvdW5kOiM1MzFmMWY7Ym9yZGVyLWNvbG9yOiNiODJiMmI7Y29sb3I6I2ZmZn0ucHJvZ3Jlc3N7aGVpZ2h0OjE4cHg7YmFja2dyb3VuZDojMzMzO2JvcmRlci1yYWRpdXM6MTBweDtvdmVyZmxvdzpoaWRkZW47bWFyZ2luLXRvcDoxMHB4fS5iYXJ7aGVpZ2h0OjEwMCU7d2lkdGg6MDtiYWNrZ3JvdW5kOiMyMzdhNDU7dHJhbnNpdGlvbjp3aWR0aCAuMnN9LmJpZ3tmb250LXNpemU6MThweDtmb250LXdlaWdodDo3MDB9dGFibGV7d2lkdGg6MTAwJTtib3JkZXItY29sbGFwc2U6Y29sbGFwc2U7bWFyZ2luLXRvcDoxMHB4fXRoLHRke3BhZGRpbmc6OXB4IDhweDtib3JkZXItYm90dG9tOjFweCBzb2xpZCAjM2EzYTNhO3RleHQtYWxpZ246bGVmdH10aHtjb2xvcjojZmZkODhhfQo8L3N0eWxlPjwvaGVhZD48Ym9keT48ZGl2IGNsYXNzPSJ3cmFwIj4KPGRpdiBjbGFzcz0iY2FyZCI+PGgxPk1STCBGaWxlcyBCYWNrdXA8L2gxPjxkaXYgY2xhc3M9InJvdyI+PGEgY2xhc3M9ImJ0biBncmF5IiBocmVmPSJhZG1pbl9iYWNrdXAucGhwIj5CYWNrdXAgTWFuYWdlciBIb21lPC9hPjxhIGNsYXNzPSJidG4gYmx1ZSIgaHJlZj0iYWRtaW5fYmFja3VwLnBocD9zZWN0aW9uPWRiIj5EYXRhYmFzZSBCYWNrdXA8L2E+PC9kaXY+PC9kaXY+CjxkaXYgY2xhc3M9ImNhcmQiPjxkaXYgY2xhc3M9InRpdGxlIj5CYWNrdXAgU2NvcGU8L2Rpdj48cCBjbGFzcz0ibXV0ZWQiPlNvdXJjZTogPHNwYW4gY2xhc3M9Im1vbm8iPjw/cGhwIGVjaG8gbXJsZmJfaCgkc291cmNlRGlyKTsgPz48L3NwYW4+PGJyPkRlc3RpbmF0aW9uOiA8c3BhbiBjbGFzcz0ibW9ubyI+PD9waHAgZWNobyBtcmxmYl9oKCRiYWNrdXBSb290KTsgPz48L3NwYW4+PGJyPkV4Y2x1ZGVkIGNvbXBsZXRlbHk6IDxzcGFuIGNsYXNzPSJtb25vIj53cC1hZG1pbiAvIHdwLWluY2x1ZGVzIC8gd3AtY29udGVudDwvc3Bhbj48L3A+PHAgY2xhc3M9Im11dGVkIj5aSVAgZW50cmllcyByZWNlaXZlIG9yaWdpbmFsIG1vZGlmaWNhdGlvbiB0aW1lcyB3aGVuIHN1cHBvcnRlZC4gQSA8c3BhbiBjbGFzcz0ibW9ubyI+X2JhY2t1cF9tYW5pZmVzdC5qc29uPC9zcGFuPiBpcyBhbHNvIHN0b3JlZCBpbnNpZGUgZXZlcnkgWklQIHdpdGggZXhhY3QgVW5peCBtdGltZSwgc2l6ZSwgcGVybWlzc2lvbnMgYW5kIFNIQS0yNTYgZm9yIGV2ZXJ5IGZpbGUuPC9wPjwvZGl2Pgo8P3BocCBpZigkc3RvcmFnZUVycm9yIT09JycpOiA/PjxkaXYgY2xhc3M9ImNhcmQgZXJyIj48P3BocCBlY2hvIG1ybGZiX2goJHN0b3JhZ2VFcnJvcik7ID8+PC9kaXY+PD9waHAgZWxzZTogPz4KPGRpdiBjbGFzcz0iY2FyZCI+PGRpdiBjbGFzcz0idGl0bGUiPkNyZWF0ZSBGaWxlcyBCYWNrdXA8L2Rpdj48ZGl2IGNsYXNzPSJyb3ciPjxidXR0b24gaWQ9InByZWZsaWdodEJ0biIgY2xhc3M9ImJ0biBibHVlIj5SdW4gUHJlZmxpZ2h0PC9idXR0b24+PGJ1dHRvbiBpZD0ic3RhcnRCdG4iIGNsYXNzPSJidG4gc2FmZSIgZGlzYWJsZWQ+Q3JlYXRlIEZpbGVzIEJhY2t1cDwvYnV0dG9uPjwvZGl2PjxkaXYgaWQ9InN0YXR1cyIgY2xhc3M9InN0YXR1cyI+UnVuIFByZWZsaWdodCBmaXJzdC48L2Rpdj48ZGl2IGNsYXNzPSJwcm9ncmVzcyI+PGRpdiBpZD0iYmFyIiBjbGFzcz0iYmFyIj48L2Rpdj48L2Rpdj48L2Rpdj4KPD9waHAgZW5kaWY7ID8+CjxkaXYgY2xhc3M9ImNhcmQiPjxkaXYgY2xhc3M9InRpdGxlIj5Db21wbGV0ZWQgRmlsZSBCYWNrdXBzPC9kaXY+Cjw/cGhwIGlmKCEkemlwcyk6ID8+PHAgY2xhc3M9Im11dGVkIj5ObyBjb21wbGV0ZWQgZmlsZSBiYWNrdXAgWklQcyBmb3VuZCB5ZXQuPC9wPjw/cGhwIGVsc2U6ID8+PHRhYmxlPjx0aGVhZD48dHI+PHRoPkZpbGU8L3RoPjx0aD5Nb2RpZmllZDwvdGg+PHRoPlNpemU8L3RoPjx0aD5BY3Rpb25zPC90aD48L3RyPjwvdGhlYWQ+PHRib2R5Pgo8P3BocCBmb3JlYWNoKCR6aXBzIGFzICRwKTogJG49YmFzZW5hbWUoJHApOyA/Pjx0cj48dGQgY2xhc3M9Im1vbm8iPjw/cGhwIGVjaG8gbXJsZmJfaCgkbik7ID8+PC90ZD48dGQ+PD9waHAgZWNobyBtcmxmYl9oKGRhdGUoJ1ktbS1kIEg6aTpzJywoaW50KWZpbGVtdGltZSgkcCkpKTsgPz48L3RkPjx0ZD48P3BocCBlY2hvIG1ybGZiX2gobXJsZmJfZm10X2J5dGVzKChpbnQpZmlsZXNpemUoJHApKSk7ID8+PC90ZD48dGQ+PGEgY2xhc3M9ImJ0biBibHVlIiBocmVmPSJhZG1pbl9iYWNrdXAucGhwP3NlY3Rpb249ZmlsZXMmYW1wO2ZpbGVzX2FjdGlvbj1kb3dubG9hZCZhbXA7ZmlsZT08P3BocCBlY2hvIHJhd3VybGVuY29kZSgkbik7ID8+Ij5Eb3dubG9hZDwvYT4gPGJ1dHRvbiBjbGFzcz0iYnRuIGRhbmdlciIgb25jbGljaz0iZGVsZXRlWmlwKCc8P3BocCBlY2hvIG1ybGZiX2goJG4pOyA/PicpIj5EZWxldGU8L2J1dHRvbj48L3RkPjwvdHI+PD9waHAgZW5kZm9yZWFjaDsgPz4KPC90Ym9keT48L3RhYmxlPjw/cGhwIGVuZGlmOyA/PjwvZGl2Pgo8ZGl2IGNsYXNzPSJtdXRlZCIgc3R5bGU9InRleHQtYWxpZ246cmlnaHQiPmFkbWluX2JhY2t1cF9maWxlc19oZWxwZXIucGhwICh2aWEgYWRtaW5fYmFja3VwLnBocCk8L2Rpdj48L2Rpdj4KPHNjcmlwdD4KY29uc3QgcT0ocyk9PmRvY3VtZW50LnF1ZXJ5U2VsZWN0b3Iocyk7IGxldCByZWFkeT1mYWxzZSwgcnVubmluZz1mYWxzZTsKYXN5bmMgZnVuY3Rpb24gY2FsbChhY3Rpb24sZGF0YSl7Y29uc3QgYm9keT1uZXcgVVJMU2VhcmNoUGFyYW1zKGRhdGF8fHt9KTtib2R5LnNldCgnZmlsZXNfYWN0aW9uJyxhY3Rpb24pO2NvbnN0IHI9YXdhaXQgZmV0Y2goJ2FkbWluX2JhY2t1cC5waHA/c2VjdGlvbj1maWxlcycse21ldGhvZDonUE9TVCcsaGVhZGVyczp7J0NvbnRlbnQtVHlwZSc6J2FwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZCd9LGJvZHl9KTtjb25zdCBqPWF3YWl0IHIuanNvbigpO2lmKCFqLm9rKXRocm93IG5ldyBFcnJvcihqLmVycm9yfHwnUmVxdWVzdCBmYWlsZWQnKTtyZXR1cm4gajt9CnEoJyNwcmVmbGlnaHRCdG4nKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLGFzeW5jKCk9Pnt0cnl7cSgnI3N0YXR1cycpLnRleHRDb250ZW50PSdTY2FubmluZyBmaWxlcy4uLic7Y29uc3Qgaj1hd2FpdCBjYWxsKCdwcmVmbGlnaHQnKTtyZWFkeT10cnVlO3EoJyNzdGFydEJ0bicpLmRpc2FibGVkPWZhbHNlO3EoJyNzdGF0dXMnKS5jbGFzc05hbWU9J3N0YXR1cyBvayc7cSgnI3N0YXR1cycpLmlubmVySFRNTD0nPHNwYW4gY2xhc3M9ImJpZyI+UkVBRFk8L3NwYW4+PGJyPicrai5maWxlcy50b0xvY2FsZVN0cmluZygpKycgZmlsZXMgKyAnK2ouZGlycy50b0xvY2FsZVN0cmluZygpKycgZm9sZGVycyDigKIgJytqLmJ5dGVzX2xhYmVsKycgc291cmNlIGRhdGE8YnI+RnJlZSBzcGFjZTogJytqLmZyZWVfbGFiZWwrJyDigKIgWklQIG10aW1lIHN1cHBvcnQ6ICcrKGoubXRpbWVfc3VwcG9ydGVkPydZRVMnOidOTycpO31jYXRjaChlKXtxKCcjc3RhdHVzJykuY2xhc3NOYW1lPSdzdGF0dXMgZXJyJztxKCcjc3RhdHVzJykudGV4dENvbnRlbnQ9ZS5tZXNzYWdlO319KTsKcSgnI3N0YXJ0QnRuJyk/LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJyxhc3luYygpPT57aWYoIXJlYWR5fHxydW5uaW5nKXJldHVybjtydW5uaW5nPXRydWU7cSgnI3N0YXJ0QnRuJykuZGlzYWJsZWQ9dHJ1ZTtxKCcjcHJlZmxpZ2h0QnRuJykuZGlzYWJsZWQ9dHJ1ZTt0cnl7Y29uc3Qgcz1hd2FpdCBjYWxsKCdzdGFydCcpO3EoJyNzdGF0dXMnKS5jbGFzc05hbWU9J3N0YXR1cyc7cSgnI3N0YXR1cycpLnRleHRDb250ZW50PSdCYWNrdXAgc3RhcnRlZDogJytzLnppcF9uYW1lO3doaWxlKHRydWUpe2NvbnN0IGo9YXdhaXQgY2FsbCgnc3RlcCcse2lkOnMuaWR9KTtjb25zdCBwY3Q9ai5ieXRlc190b3RhbD4wP01hdGgubWluKDEwMCwoai5ieXRlc19kb25lL2ouYnl0ZXNfdG90YWwpKjEwMCk6KGouY29tcGxldGU/MTAwOjApO3EoJyNiYXInKS5zdHlsZS53aWR0aD1wY3QudG9GaXhlZCgxKSsnJSc7cSgnI3N0YXR1cycpLmlubmVySFRNTD0oai5jb21wbGV0ZT8nPHNwYW4gY2xhc3M9ImJpZyI+QkFDS1VQIENPTVBMRVRFPC9zcGFuPjxicj4nOidCYWNraW5nIHVwLi4uPGJyPicpK2ouZmlsZXNfZG9uZS50b0xvY2FsZVN0cmluZygpKycgLyAnK2ouZmlsZXNfdG90YWwudG9Mb2NhbGVTdHJpbmcoKSsnIGZpbGVzIOKAoiAnK3BjdC50b0ZpeGVkKDEpKyclJztpZihqLmNvbXBsZXRlKXtxKCcjc3RhdHVzJykuY2xhc3NOYW1lPSdzdGF0dXMgb2snO3NldFRpbWVvdXQoKCk9PmxvY2F0aW9uLnJlbG9hZCgpLDcwMCk7YnJlYWs7fX19Y2F0Y2goZSl7cSgnI3N0YXR1cycpLmNsYXNzTmFtZT0nc3RhdHVzIGVycic7cSgnI3N0YXR1cycpLnRleHRDb250ZW50PWUubWVzc2FnZTtxKCcjcHJlZmxpZ2h0QnRuJykuZGlzYWJsZWQ9ZmFsc2U7fWZpbmFsbHl7cnVubmluZz1mYWxzZTt9fSk7CmFzeW5jIGZ1bmN0aW9uIGRlbGV0ZVppcChuYW1lKXtpZighY29uZmlybSgnRGVsZXRlIHRoaXMgY29tcGxldGVkIGZpbGUgYmFja3VwP1xuXG4nK25hbWUrJ1xuXG5UaGlzIGNhbm5vdCBiZSB1bmRvbmUuJykpcmV0dXJuO3RyeXthd2FpdCBjYWxsKCdkZWxldGUnLHtmaWxlOm5hbWV9KTtsb2NhdGlvbi5yZWxvYWQoKTt9Y2F0Y2goZSl7YWxlcnQoZS5tZXNzYWdlKTt9fQo8L3NjcmlwdD48L2JvZHk+PC9odG1sPgo=', true);
$payloadOk = is_string($payload)
    && strpos($payload, 'VERSION: v002') !== false
    && strpos($payload, "._mrl_backup_in_progress") !== false;

$requiredPass = $isExpectedV001 && $zipAvailable && $rootWritable && $payloadOk;

$message = '';
$messageType = '';

if (isset($_POST['action']) && $_POST['action'] === 'apply') {
    if (!$requiredPass) {
        $message = 'APPLY BLOCKED — one or more required preflight checks are not passing.';
        $messageType = 'err';
    } else {
        if (!is_dir($rollbackDir) && !@mkdir($rollbackDir, 0755, true)) {
            $message = 'APPLY FAILED — could not create rollback directory.';
            $messageType = 'err';
        } elseif (!is_file($rollbackFile) && !@copy($target, $rollbackFile)) {
            $message = 'APPLY FAILED — could not create rollback copy.';
            $messageType = 'err';
        } else {
            [$ok, $err] = fx_write_atomic($target, $payload);
            if (!$ok) {
                $message = 'APPLY FAILED — ' . $err;
                $messageType = 'err';
            } else {
                [$syntaxOk, $syntaxMsg] = fx_php_syntax_ok($target);
                if ($syntaxOk === false) {
                    @copy($rollbackFile, $target);
                    $message = 'APPLY FAILED — PHP syntax check failed and the original helper was restored. ' . $syntaxMsg;
                    $messageType = 'err';
                } else {
                    $message = 'INSTALL COMPLETE — admin_backup_files_helper.php is now v002. The ZIP reopen issue is corrected.';
                    $messageType = 'ok';
                }
            }
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'rollback') {
    if (!is_file($rollbackFile)) {
        $message = 'ROLLBACK NOT AVAILABLE — no rollback copy exists yet.';
        $messageType = 'err';
    } elseif (!@copy($rollbackFile, $target)) {
        $message = 'ROLLBACK FAILED — could not restore the original helper.';
        $messageType = 'err';
    } else {
        $message = 'ROLLBACK COMPLETE — original admin_backup_files_helper.php restored.';
        $messageType = 'ok';
    }
}

$currentAfter = is_file($target) ? (string)@file_get_contents($target) : '';
$currentVersion = 'unknown';
if (preg_match('/VERSION:\s*(v\d+)/', $currentAfter, $m)) {
    $currentVersion = $m[1];
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Fix MRL Files Backup ZIP Reopen v001</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;background:#0d1013;color:#eef2f6;font-family:Arial,Helvetica,sans-serif;font-size:14px}
.wrap{max-width:1000px;margin:20px auto 50px;padding:0 16px}
.card{background:#1b1f23;border:1px solid #3b4249;border-radius:12px;padding:18px;margin-bottom:14px}
h1{margin:0 0 8px;color:#ffcf83;font-size:30px}
h2{margin:0 0 14px;color:#ffcf83;font-size:22px}
.sub{color:#c8d0d8;line-height:1.5}
.info{background:#12344a;border:1px solid #1d6f9d;border-radius:8px;padding:12px;line-height:1.55}
.okbox{background:#104d2d;border:1px solid #218750;border-radius:8px;padding:13px;font-weight:bold}
.errbox{background:#671f1f;border:1px solid #c83a3a;border-radius:8px;padding:13px;font-weight:bold}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 8px;border-bottom:1px solid #3a4249;text-align:left;vertical-align:top}
th{color:#ffd27f;font-size:12px}
.pass{color:#57e38c;font-weight:bold}
.fail{color:#ff7373;font-weight:bold}
.infoStatus{color:#8fc8ff;font-weight:bold}
code,.mono{font-family:Consolas,Menlo,monospace;color:#ffd27f}
.btn{border:0;border-radius:7px;padding:11px 16px;font-weight:bold;font-size:14px;cursor:pointer}
.btn-safe{background:#23864b;color:#fff}
.btn-danger{background:#ca2e2e;color:#fff}
.btn-disabled{background:#666;color:#ccc;cursor:not-allowed}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
a{color:#8fc8ff}
.small{font-size:12px;color:#aeb8c1}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
    <h1>Fix MRL Files Backup ZIP Reopen v001</h1>
    <div class="sub">
        Corrects the first-run <code>Could not reopen ZIP. Code: 9</code> error in the new batched files-backup helper.
        Only <code>admin_backup_files_helper.php</code> is changed.
    </div>
    <div class="info" style="margin-top:14px">
        <strong>What happened:</strong> v001 created an empty ZIP and closed it before the first batch.
        On this server, that empty archive did not remain as a physical file. The next batch therefore had nothing to reopen.
        <br><br>
        <strong>v002 fix:</strong> initialize the ZIP with a tiny temporary marker, process the backup normally in batches,
        then remove the marker before the completed ZIP is finalized.
    </div>
</div>

<?php if ($message !== ''): ?>
<div class="<?php echo $messageType === 'ok' ? 'okbox' : 'errbox'; ?>" style="margin-bottom:14px">
    <?php echo fx_h($message); ?>
    <?php if ($messageType === 'ok' && strpos($message, 'INSTALL COMPLETE') !== false): ?>
        &nbsp; <a href="/admin_backup.php?section=files">Open Backup Manager</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <h2>Preflight</h2>
    <table>
        <thead><tr><th>CHECK</th><th>TYPE</th><th>STATUS</th><th>DETAILS</th></tr></thead>
        <tbody>
        <tr>
            <td>Current files helper is expected v001</td>
            <td>Required</td>
            <td class="<?php echo $isExpectedV001 ? 'pass' : 'fail'; ?>"><?php echo $isExpectedV001 ? 'PASS' : 'FAIL'; ?></td>
            <td>Ensures this patch is being applied to the exact first-release helper.</td>
        </tr>
        <tr>
            <td>ZipArchive available</td>
            <td>Required</td>
            <td class="<?php echo $zipAvailable ? 'pass' : 'fail'; ?>"><?php echo $zipAvailable ? 'PASS' : 'FAIL'; ?></td>
            <td>Required by the files-backup engine.</td>
        </tr>
        <tr>
            <td>public_html writable</td>
            <td>Required</td>
            <td class="<?php echo $rootWritable ? 'pass' : 'fail'; ?>"><?php echo $rootWritable ? 'PASS' : 'FAIL'; ?></td>
            <td>Needed to replace the helper and create its rollback copy.</td>
        </tr>
        <tr>
            <td>Corrected v002 helper payload</td>
            <td>Required</td>
            <td class="<?php echo $payloadOk ? 'pass' : 'fail'; ?>"><?php echo $payloadOk ? 'PASS' : 'FAIL'; ?></td>
            <td>Embedded helper contains the temporary marker initialization and completion cleanup.</td>
        </tr>
        <tr>
            <td>Current helper version</td>
            <td>Info</td>
            <td class="infoStatus"><?php echo fx_h($currentVersion); ?></td>
            <td><span class="mono"><?php echo fx_h($target); ?></span></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Apply</h2>
    <p class="sub">
        A rollback copy of the current helper is made first. No database backup files, file-backup ZIPs,
        <code>admin_backup.php</code>, or <code>admin_backup_db_helper.php</code> are modified.
    </p>
    <form method="post">
        <input type="hidden" name="action" value="apply">
        <button type="submit" class="btn <?php echo $requiredPass ? 'btn-safe' : 'btn-disabled'; ?>" <?php echo $requiredPass ? '' : 'disabled'; ?>>
            Apply ZIP Reopen Fix v001
        </button>
    </form>
</div>

<div class="card">
    <h2>Rollback</h2>
    <p class="sub">Restores the pre-patch <code>admin_backup_files_helper.php</code> from this patch's rollback copy.</p>
    <form method="post" onsubmit="return confirm('Restore the original files helper?');">
        <input type="hidden" name="action" value="rollback">
        <button type="submit" class="btn btn-danger">Rollback</button>
    </form>
</div>

<div class="small" style="text-align:right"><?php echo fx_h('fix_admin_backup_files_zip_reopen_v001_20260903_013300pm.php'); ?></div>

</div>
</body>
</html>
