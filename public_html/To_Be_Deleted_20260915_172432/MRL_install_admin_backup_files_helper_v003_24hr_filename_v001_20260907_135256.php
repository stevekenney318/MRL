<?php
declare(strict_types=1);

/**
 * MRL_install_admin_backup_files_helper_v003_24hr_filename.php
 *
 * VERSION: v001
 * CREATED: 9/7/2026 1:52:56 pm ET
 *
 * PURPOSE:
 * Surgical update only:
 * - admin_backup_files_helper.php v002 -> v003
 * - New backup filenames use 24-hour time with no am/pm suffix.
 * - Old backup filenames remain valid for Download/Delete.
 *
 * TARGET:
 * /public_html/admin_backup_files_helper.php
 *
 * NEW FORMAT:
 * public_html_YYYYMMDD_HHMMSSmmm.zip
 *
 * EXAMPLE:
 * public_html_20260907_132045123.zip
 *
 * NO OTHER BACKUP LOGIC IS CHANGED.
 */

date_default_timezone_set('America/New_York');

const MRL_INSTALLER_VERSION = 'v001';
const MRL_TARGET = __DIR__ . '/admin_backup_files_helper.php';

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function read_text(string $path): string {
    $data = @file_get_contents($path);
    return $data === false ? '' : $data;
}

function write_text(string $path, string $data): bool {
    $n = @file_put_contents($path, $data, LOCK_EX);
    return $n !== false && $n === strlen($data);
}

function count_exact(string $haystack, string $needle): int {
    return $needle === '' ? 0 : substr_count($haystack, $needle);
}

function php_lint(string $path): array {
    if (!function_exists('exec')) {
        return ['available'=>false, 'ok'=>true, 'output'=>'exec() unavailable; lint skipped.'];
    }

    $bin = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($bin) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = [];
    $code = 999;
    @exec($cmd, $out, $code);

    return [
        'available'=>true,
        'ok'=>$code === 0,
        'output'=>trim(implode("\n", $out))
    ];
}

function preflight(string $target): array {
    $checks = [];
    $exists = is_file($target);
    $checks[] = ['Target file exists', $exists, $target];

    $content = $exists ? read_text($target) : '';

    $checks[] = [
        'Expected header VERSION v002',
        count_exact($content, '    VERSION: v002') === 1,
        ''
    ];

    $checks[] = [
        'Expected old filename stamp code found once',
        count_exact(
            $content,
            "$stamp=date('Ymd_his') . sprintf('%03d',(int)floor((microtime(true)-floor(microtime(true)))*1000)) . date('a');"
        ) === 1,
        ''
    ];

    $checks[] = [
        'Expected old filename validator found once',
        count_exact(
            $content,
            "return basename($name)===$name && preg_match('/^public_html_\\\\d{8}_\\\\d{9}(?:am|pm)\\\\.zip$/', $name) === 1;"
        ) === 1,
        ''
    ];

    $checks[] = ['Target writable', $exists && is_writable($target), ''];
    $checks[] = ['Target directory writable', is_writable(dirname($target)), dirname($target)];

    $ok = true;
    foreach ($checks as $c) {
        if (!$c[1]) { $ok = false; break; }
    }

    return [
        'ok'=>$ok,
        'checks'=>$checks,
        'content'=>$content,
        'sha256'=>$content !== '' ? hash('sha256', $content) : ''
    ];
}

function build_v003(string $content): array {
    $fromStamp = "$stamp=date('Ymd_his') . sprintf('%03d',(int)floor((microtime(true)-floor(microtime(true)))*1000)) . date('a');";
    $toStamp = "$stamp=date('Ymd_His') . sprintf('%03d',(int)floor((microtime(true)-floor(microtime(true)))*1000));";

    $fromValidator = "return basename($name)===$name && preg_match('/^public_html_\\\\d{8}_\\\\d{9}(?:am|pm)\\\\.zip$/', $name) === 1;";
    $toValidator = "return basename($name)===$name && preg_match('/^public_html_\\\\d{8}_(?:\\\\d{9}|\\\\d{9}(?:am|pm))\\\\.zip$/', $name) === 1;";

    $changes = [
        ['    VERSION: v002', '    VERSION: v003'],
        ['    LAST MODIFIED: 9/3/2026 1:33:00 pm', '    LAST MODIFIED: 9/7/2026 1:52:56 pm'],
        [
            "    CHANGELOG:\n    v002 (9/3/2026 1:33:00 pm)",
            "    CHANGELOG:\n    v003 (9/7/2026 1:52:56 pm)\n    - CHANGE: New file-backup ZIP names use 24-hour HHMMSSmmm timestamps with no am/pm suffix.\n    - COMPAT: Existing 12-hour am/pm backup ZIP names remain valid for Download/Delete.\n    - PRESERVE: No changes to backup contents, scan, batching, ZIP creation, manifest, download, delete, or restore-related behavior.\n\n    v002 (9/3/2026 1:33:00 pm)"
        ],
        [$fromStamp, $toStamp],
        [$fromValidator, $toValidator],
    ];

    $updated = $content;

    foreach ($changes as [$from, $to]) {
        if (count_exact($updated, $from) !== 1) {
            return ['ok'=>false, 'error'=>'Expected patch signature was not found exactly once.', 'content'=>''];
        }
        $updated = str_replace($from, $to, $updated);
    }

    $updated = str_replace('9/7/2026 1:52:56 pm', '__DISPLAY_REAL__', $updated);

    return ['ok'=>true, 'error'=>'', 'content'=>$updated];
}

$pre = preflight(MRL_TARGET);
$message = '';
$messageClass = 'info';
$post = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
    $pre = preflight(MRL_TARGET);

    if (!$pre['ok']) {
        $message = 'Apply blocked: preflight failed.';
        $messageClass = 'bad';
    } else {
        $original = $pre['content'];
        $patch = build_v003($original);

        if (!$patch['ok']) {
            $message = 'Apply blocked: ' . $patch['error'];
            $messageClass = 'bad';
        } else {
            $updated = str_replace('__DISPLAY_REAL__', '9/7/2026 1:52:56 pm', $patch['content']);

            $backup = MRL_TARGET . '.v002.before_24hr_filename_' . date('Ymd_His') . '.bak';

            if (!write_text($backup, $original)) {
                $message = 'Apply blocked: backup could not be written.';
                $messageClass = 'bad';
            } else {
                $temp = dirname(MRL_TARGET) . '/.' . basename(MRL_TARGET) . '.v003.tmp';

                if (!write_text($temp, $updated)) {
                    $message = 'Apply failed: temporary v003 file could not be written.';
                    $messageClass = 'bad';
                } else {
                    $lint = php_lint($temp);
                    $post[] = ['Temporary PHP syntax check', $lint['ok'], $lint['output']];

                    if (!$lint['ok']) {
                        @unlink($temp);
                        $message = 'Apply blocked: v003 failed PHP syntax validation.';
                        $messageClass = 'bad';
                    } elseif (!@rename($temp, MRL_TARGET)) {
                        @unlink($temp);
                        $message = 'Apply failed: production replacement could not be completed.';
                        $messageClass = 'bad';
                    } else {
                        $installed = read_text(MRL_TARGET);

                        $checks = [
                            ['Header VERSION v003', count_exact($installed, '    VERSION: v003') === 1, ''],
                            ['24-hour filename stamp installed', strpos($installed, "$stamp=date('Ymd_His')") !== false, ''],
                            ['am/pm suffix removed from new filename generation', strpos($installed, ". date('a');") === false, ''],
                            ['Old and new backup filename validation retained', strpos($installed, "(?:\\\\d{9}|\\\\d{9}(?:am|pm))") !== false, ''],
                        ];

                        foreach ($checks as $c) $post[] = $c;

                        $all = true;
                        foreach ($post as $c) {
                            if (!$c[1]) { $all = false; break; }
                        }

                        if ($all) {
                            $message = 'SUCCESS: admin_backup_files_helper.php v003 installed. Only backup filename timestamp handling changed.';
                            $messageClass = 'good';
                        } else {
                            @copy($backup, MRL_TARGET);
                            $message = 'Postflight failed. The original v002 file was restored automatically.';
                            $messageClass = 'bad';
                        }
                    }
                }
            }
        }
    }

    $pre = preflight(MRL_TARGET);
}

$current = read_text(MRL_TARGET);
$detected = 'Unknown';
if (preg_match('/VERSION:\s+(v\d{3})/', $current, $m)) {
    $detected = $m[1];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Backup Filename 24-Hour Fix</title>
<style>
:root{color-scheme:dark;--bg:#101312;--panel:#1b201f;--border:#46504d;--text:#eee9df;--muted:#b8b7b0;--gold:#f1c97f;--green:#158547;--red:#b13b3b}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(980px,94%);margin:18px auto 30px}
h1{margin:0 0 12px;color:var(--gold);font-size:28px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.notice{margin:0 0 11px;padding:10px 13px;border-radius:9px;font-weight:700}
.good{background:#103b27;border:1px solid #2f9b63}
.bad{background:#4b1d1d;border:1px solid #c04b4b}
.info{background:#173246;border:1px solid #387ba8}
table{width:100%;border-collapse:collapse}
th,td{padding:6px 8px;border-bottom:1px solid #353c3a;text-align:left;vertical-align:top}
th{color:var(--gold)}
.pass{color:#5ee58e;font-weight:800}.fail{color:#ff7b7b;font-weight:800}
.small{font-size:12px;color:var(--muted)}.mono{font-family:Consolas,"Courier New",monospace}
button{padding:9px 14px;border:0;border-radius:8px;background:var(--green);color:#fff;font-weight:800;cursor:pointer}
button:disabled{opacity:.45;cursor:not-allowed}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL Backup Filename 24-Hour Fix</h1>

<?php if ($message !== ''): ?>
<div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
<?php endif; ?>

<div class="panel">
<h2>Change only</h2>
<div class="mono">public_html_YYYYMMDD_HHMMSSmmm.zip</div>
<div class="small" style="margin-top:5px">
Old am/pm ZIP names remain supported for Download/Delete.
No other backup behavior is changed.
</div>
</div>

<div class="panel">
<h2>Target</h2>
<table>
<tr><th>File</th><td class="mono"><?php echo h(MRL_TARGET); ?></td></tr>
<tr><th>Detected version</th><td><?php echo h($detected); ?></td></tr>
</table>
</div>

<div class="panel">
<h2>Preflight</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($pre['checks'] as $c): ?>
<tr>
<td><?php echo h((string)$c[0]); ?></td>
<td class="<?php echo $c[1] ? 'pass' : 'fail'; ?>"><?php echo $c[1] ? 'PASS' : 'FAIL'; ?></td>
<td class="small mono"><?php echo h((string)$c[2]); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<?php if (!empty($post)): ?>
<div class="panel">
<h2>Postflight</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($post as $c): ?>
<tr>
<td><?php echo h((string)$c[0]); ?></td>
<td class="<?php echo $c[1] ? 'pass' : 'fail'; ?>"><?php echo $c[1] ? 'PASS' : 'FAIL'; ?></td>
<td class="small mono"><?php echo h((string)$c[2]); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<div class="panel">
<form method="post">
<input type="hidden" name="action" value="apply">
<button type="submit" <?php echo $pre['ok'] ? '' : 'disabled'; ?>>Apply 24-Hour Filename Fix</button>
</form>
</div>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: <?php echo h(MRL_INSTALLER_VERSION); ?><br>
Delete this installer from /public_html after successful testing.
</div>
</div>
</body>
</html>
