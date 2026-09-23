<?php
declare(strict_types=1);

/**
 * install_admin_mrl_test_team_reset_v003_v001_20260923_035718am.php
 *
 * VERSION: v001
 * LAST MODIFIED: 9/23/2026 3:57:18 am
 *
 * PURPOSE:
 * - Upgrade admin_mrl_test_team_reset.php v002 -> v003.
 * - Change hard-coded ID 999 reset utility into a selectable existing 900-999 test-account reset.
 * - Demonstrate the new MRL installer report UX: compact JSON box + Copy button,
 *   with Download JSON retained as an optional secondary action.
 *
 * SAFETY:
 * - Admin-only.
 * - Verifies production baseline/signatures.
 * - Builds exact v003 candidate from embedded content and lints it before Apply.
 * - Backs up the existing production file before replacement.
 * - Lints and verifies installed file after Apply.
 * - Automatic restore on critical postflight failure.
 * - Explicit rollback remains available.
 * - Does not send email, run cron/jobs, or create/delete picks itself.
 */

date_default_timezone_set('America/New_York');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/class.user.php';
$user_home = new USER();
if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}
if (!isAdmin((int)($_SESSION['userSession'] ?? 0))) {
    http_response_code(403);
    exit('Admin access required.');
}

const MRL_INSTALLER_VERSION = 'v001';
const MRL_TARGET_EXPECTED = 'v002';
const MRL_TARGET_NEW = 'v003';
const MRL_TARGET_REL = 'admin_mrl_test_team_reset.php';
const MRL_TARGET_SHA = '787b82d1c474e54ecccce0a4d201b9ca3b9d80554c71c96dc66aea5f110065a8';
const MRL_TARGET_B64 = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogYWRtaW5fbXJsX3Rlc3RfdGVhbV9yZXNldC5waHAKICoKICogVkVSU0lPTjogdjAwMwogKiBMQVNUIE1PRElGSUVEOiA5LzIzLzIwMjYgMzo1NzoxOCBhbQogKgogKiBQVVJQT1NFOgogKiAgIFJldXNhYmxlIEFkbWluLW9ubHkgdXRpbGl0eSB0byByZXNldCBkZWRpY2F0ZWQgTVJMIHRlc3QgYWNjb3VudHMKICogICBpbiB0aGUgOTAwLTk5OSB1c2VySUQgcmFuZ2UgdG8gYSAibm8gcGlja3Mgc3VibWl0dGVkIHlldCIgc3RhdGUKICogICBmb3Igb25lIHNlbGVjdGVkIHllYXIuCiAqCiAqIHYwMDMgQ0hBTkdFOgogKiAgIC0gUmVwbGFjZXMgdGhlIGhhcmQtY29kZWQgdXNlcklEIDk5OSB0YXJnZXQgd2l0aCBhIGRyb3Bkb3duIG9mIGV4aXN0aW5nCiAqICAgICB1c2VycyBpbiB0aGUgOTAwLTk5OSByYW5nZS4KICogICAtIEtlZXBzIDk5OSBhdmFpbGFibGUgYW5kIGFkZHMgOTk4IGd1ZXN0IGF1dG9tYXRpY2FsbHkgd2hlbiBwcmVzZW50LgogKiAgIC0gS2VlcHMgcmVzZXQgc2NvcGUgbmFycm93OiBzZWxlY3RlZCB0ZXN0IHVzZXIgKyBzZWxlY3RlZCByYWNlIHllYXIgb25seS4KICogICAtIFR5cGVkIGNvbmZpcm1hdGlvbiBmb2xsb3dzIHRoZSBzZWxlY3RlZCBJRCwgZS5nLiBSRVNFVCBNUkwgOTk4LgogKiAgIC0gQmFja3VwIGZpbGVuYW1lcyBhbmQgSlNPTiBwYXlsb2FkIG5vdyBpZGVudGlmeSB0aGUgc2VsZWN0ZWQgdGVzdCB1c2VyLgogKgogKiB2MDAyIEZJWDoKICogICAtIFJlbW92ZXMgYXNzdW1wdGlvbnMgYWJvdXQgZXhhY3QgdXNlcnMtdGFibGUgY29sdW1uIG5hbWVzIHRoYXQgY291bGQKICogICAgIHRyaWdnZXIgSFRUUCA1MDAgb24gc2NoZW1hcyB0aGF0IGRvIG5vdCBjb250YWluIHVzZXJOYW1lL3VzZXJBY3RpdmUvCiAqICAgICB1c2VyQWRtaW4vdXNlclN0YXR1cyBpbiB0aGF0IGV4YWN0IGZvcm0uCiAqICAgLSBVc2VzIFNFTEVDVCAqIGZvciB0aGUgc2luZ2xlIHVzZXIgcm93IGFuZCBkaXNwbGF5cyB0aGUgc2FmZXN0IGF2YWlsYWJsZQogKiAgICAgYWNjb3VudCBsYWJlbC4KICogICAtIE1ha2VzIHllYXIgZGlzY292ZXJ5IGFuZCB0ZWFtIGxvb2t1cCB0b2xlcmFudCBvZiBzY2hlbWEgZGlmZmVyZW5jZXMuCiAqCiAqIFdIQVQgSVQgQ0xFQVJTOgogKiAgIC0gdXNlcl9waWNrcyByb3dzIGZvciBzZWxlY3RlZCA5MDAtc2VyaWVzIHRlc3QgdXNlciArIHNlbGVjdGVkIHJhY2VZZWFyCiAqICAgLSB1c2VyX3BpY2tzX2hpc3Rvcnkgcm93cyBmb3Igc2VsZWN0ZWQgOTAwLXNlcmllcyB0ZXN0IHVzZXIgKyBzZWxlY3RlZCByYWNlWWVhcgogKgogKiBXSEFUIElUIFBSRVNFUlZFUzoKICogICAtIHVzZXJzIHJvdyAvIGxvZ2luCiAqICAgLSB1c2VyX3RlYW1zIHJvdyAvIHRlYW0gbmFtZQogKiAgIC0gcHJvZmlsZS90aGVtZS9wcmVmZXJlbmNlcwogKiAgIC0gYWxsIG5vbi1zZWxlY3RlZCB1c2VycwogKiAgIC0gYWxsIG90aGVyIHllYXJzCiAqICAgLSBzY29yaW5nL3Jlc3VsdCBmaWxlcyBhbmQgc25hcHNob3RzCiAqCiAqIFNBRkVUWToKICogICAtIFRhcmdldCBtdXN0IGJlIGFuIGV4aXN0aW5nIHVzZXJJRCBmcm9tIDkwMCB0aHJvdWdoIDk5OS4KICogICAtIEFkbWluLW9ubHkuCiAqICAgLSBQcmVmbGlnaHQgdmVyaWZpZXMgcmVxdWlyZWQgdGFibGVzL2NvbHVtbnMuCiAqICAgLSBTaG93cyBleGFjdCByb3cgY291bnRzIGJlZm9yZSByZXNldC4KICogICAtIFJlcXVpcmVzIHR5cGVkIGNvbmZpcm1hdGlvbiBjb250YWluaW5nIHRoZSBzZWxlY3RlZCBJRC4KICogICAtIFdyaXRlcyBjb21wbGV0ZSBKU09OIGJhY2t1cCBCRUZPUkUgZGVsZXRpb24uCiAqICAgLSBEZWxldGVzIGluc2lkZSBhIERCIHRyYW5zYWN0aW9uLgogKgogKiBOTyBPVEhFUiBVU0VSUyBPUiBZRUFSUyBBUkUgVE9VQ0hFRC4KICovCgpkYXRlX2RlZmF1bHRfdGltZXpvbmVfc2V0KCdBbWVyaWNhL05ld19Zb3JrJyk7CgppZiAoc2Vzc2lvbl9zdGF0dXMoKSA9PT0gUEhQX1NFU1NJT05fTk9ORSkgewogICAgc2Vzc2lvbl9zdGFydCgpOwp9CgpyZXF1aXJlX29uY2UgX19ESVJfXyAuICcvY2xhc3MudXNlci5waHAnOwokdXNlcl9ob21lID0gbmV3IFVTRVIoKTsKCmlmICghJHVzZXJfaG9tZS0+aXNfbG9nZ2VkX2luKCkpIHsKICAgICR1c2VyX2hvbWUtPnJlZGlyZWN0KCcvbG9naW4ucGhwJyk7CiAgICBleGl0Owp9CgpyZXF1aXJlIF9fRElSX18gLiAnL2NvbmZpZy5waHAnOwpyZXF1aXJlIF9fRElSX18gLiAnL2NvbmZpZ19tcmwucGhwJzsKCiRhZG1pblVpZCA9IChpbnQpKCRfU0VTU0lPTlsndXNlclNlc3Npb24nXSA/PyAwKTsKaWYgKCFpc0FkbWluKCRhZG1pblVpZCkpIHsKICAgIGh0dHBfcmVzcG9uc2VfY29kZSg0MDMpOwogICAgZXhpdCgnQWRtaW4gYWNjZXNzIHJlcXVpcmVkLicpOwp9CgppZiAoIWlzc2V0KCRkYmNvbm5lY3QpIHx8ICEoJGRiY29ubmVjdCBpbnN0YW5jZW9mIG15c3FsaSkpIHsKICAgIGh0dHBfcmVzcG9uc2VfY29kZSg1MDApOwogICAgZXhpdCgnRGF0YWJhc2UgY29ubmVjdGlvbiBpcyBub3QgYXZhaWxhYmxlLicpOwp9Cgpjb25zdCBNUkxfVEVTVF9VSURfTUlOID0gOTAwOwpjb25zdCBNUkxfVEVTVF9VSURfTUFYID0gOTk5OwoKZnVuY3Rpb24gbXR0cjNfaCgkdik6IHN0cmluZyB7CiAgICByZXR1cm4gaHRtbHNwZWNpYWxjaGFycygoc3RyaW5nKSR2LCBFTlRfUVVPVEVTLCAnVVRGLTgnKTsKfQoKZnVuY3Rpb24gbXR0cjNfdGFibGVfY29sdW1ucyhteXNxbGkgJGRiLCBzdHJpbmcgJHRhYmxlKTogYXJyYXkgewogICAgaWYgKCFwcmVnX21hdGNoKCcvXltBLVphLXowLTlfXSskLycsICR0YWJsZSkpIHJldHVybiBbXTsKICAgICRyZXMgPSBteXNxbGlfcXVlcnkoJGRiLCAiU0hPVyBDT0xVTU5TIEZST00gYCR0YWJsZWAiKTsKICAgIGlmICghJHJlcykgcmV0dXJuIFtdOwogICAgJGNvbHMgPSBbXTsKICAgIHdoaWxlICgkcm93ID0gbXlzcWxpX2ZldGNoX2Fzc29jKCRyZXMpKSB7CiAgICAgICAgJGNvbHNbXSA9IChzdHJpbmcpKCRyb3dbJ0ZpZWxkJ10gPz8gJycpOwogICAgfQogICAgbXlzcWxpX2ZyZWVfcmVzdWx0KCRyZXMpOwogICAgcmV0dXJuICRjb2xzOwp9CgpmdW5jdGlvbiBtdHRyM19oYXNfY29sdW1ucyhteXNxbGkgJGRiLCBzdHJpbmcgJHRhYmxlLCBhcnJheSAkcmVxdWlyZWQpOiBib29sIHsKICAgICRjb2xzID0gbXR0cjNfdGFibGVfY29sdW1ucygkZGIsICR0YWJsZSk7CiAgICBmb3JlYWNoICgkcmVxdWlyZWQgYXMgJGNvbCkgewogICAgICAgIGlmICghaW5fYXJyYXkoJGNvbCwgJGNvbHMsIHRydWUpKSByZXR1cm4gZmFsc2U7CiAgICB9CiAgICByZXR1cm4gdHJ1ZTsKfQoKZnVuY3Rpb24gbXR0cjNfcm93cyhteXNxbGkgJGRiLCBzdHJpbmcgJHNxbCwgYXJyYXkgJHBhcmFtcyA9IFtdLCBzdHJpbmcgJHR5cGVzID0gJycpOiBhcnJheSB7CiAgICAkc3RtdCA9IG15c3FsaV9wcmVwYXJlKCRkYiwgJHNxbCk7CiAgICBpZiAoISRzdG10KSB0aHJvdyBuZXcgUnVudGltZUV4Y2VwdGlvbignUHJlcGFyZSBmYWlsZWQ6ICcgLiBteXNxbGlfZXJyb3IoJGRiKSk7CiAgICBpZiAoJHBhcmFtcykgbXlzcWxpX3N0bXRfYmluZF9wYXJhbSgkc3RtdCwgJHR5cGVzLCAuLi4kcGFyYW1zKTsKICAgIGlmICghbXlzcWxpX3N0bXRfZXhlY3V0ZSgkc3RtdCkpIHsKICAgICAgICAkZXJyID0gbXlzcWxpX3N0bXRfZXJyb3IoJHN0bXQpOwogICAgICAgIG15c3FsaV9zdG10X2Nsb3NlKCRzdG10KTsKICAgICAgICB0aHJvdyBuZXcgUnVudGltZUV4Y2VwdGlvbignUXVlcnkgZmFpbGVkOiAnIC4gJGVycik7CiAgICB9CiAgICAkcmVzdWx0ID0gbXlzcWxpX3N0bXRfZ2V0X3Jlc3VsdCgkc3RtdCk7CiAgICAkcm93cyA9IFtdOwogICAgaWYgKCRyZXN1bHQpIHsKICAgICAgICB3aGlsZSAoJHJvdyA9IG15c3FsaV9mZXRjaF9hc3NvYygkcmVzdWx0KSkgJHJvd3NbXSA9ICRyb3c7CiAgICB9CiAgICBteXNxbGlfc3RtdF9jbG9zZSgkc3RtdCk7CiAgICByZXR1cm4gJHJvd3M7Cn0KCmZ1bmN0aW9uIG10dHIzX2NvdW50KG15c3FsaSAkZGIsIHN0cmluZyAkdGFibGUsIGludCAkdWlkLCBzdHJpbmcgJHllYXIpOiBpbnQgewogICAgaWYgKCFwcmVnX21hdGNoKCcvXltBLVphLXowLTlfXSskLycsICR0YWJsZSkpIHRocm93IG5ldyBSdW50aW1lRXhjZXB0aW9uKCdVbnNhZmUgdGFibGUuJyk7CiAgICAkcm93cyA9IG10dHIzX3Jvd3MoCiAgICAgICAgJGRiLAogICAgICAgICJTRUxFQ1QgQ09VTlQoKikgQVMgYyBGUk9NIGAkdGFibGVgIFdIRVJFIHVzZXJJRCA9ID8gQU5EIHJhY2VZZWFyID0gPyIsCiAgICAgICAgWyR1aWQsICR5ZWFyXSwKICAgICAgICAnaXMnCiAgICApOwogICAgcmV0dXJuIChpbnQpKCRyb3dzWzBdWydjJ10gPz8gMCk7Cn0KCmZ1bmN0aW9uIG10dHIzX3Rlc3RfYWNjb3VudHMobXlzcWxpICRkYik6IGFycmF5IHsKICAgIGlmICghbXR0cjNfaGFzX2NvbHVtbnMoJGRiLCAndXNlcnMnLCBbJ3VzZXJJRCddKSkgcmV0dXJuIFtdOwogICAgcmV0dXJuIG10dHIzX3Jvd3MoCiAgICAgICAgJGRiLAogICAgICAgICJTRUxFQ1QgKiBGUk9NIHVzZXJzIFdIRVJFIHVzZXJJRCBCRVRXRUVOID8gQU5EID8gT1JERVIgQlkgdXNlcklEIEFTQyIsCiAgICAgICAgW01STF9URVNUX1VJRF9NSU4sIE1STF9URVNUX1VJRF9NQVhdLAogICAgICAgICdpaScKICAgICk7Cn0KCmZ1bmN0aW9uIG10dHIzX2FjY291bnRfbGFiZWwoYXJyYXkgJHJvdyk6IHN0cmluZyB7CiAgICBmb3JlYWNoIChbJ3VzZXJOYW1lJywndXNlcm5hbWUnLCd1c2VyX25hbWUnLCduYW1lJywnZGlzcGxheU5hbWUnLCdlbWFpbCcsJ3VzZXJFbWFpbCddIGFzICRrZXkpIHsKICAgICAgICBpZiAoaXNzZXQoJHJvd1ska2V5XSkgJiYgdHJpbSgoc3RyaW5nKSRyb3dbJGtleV0pICE9PSAnJykgcmV0dXJuIHRyaW0oKHN0cmluZykkcm93WyRrZXldKTsKICAgIH0KICAgIHJldHVybiAkcm93ID8gJ01STCB0ZXN0IGFjY291bnQnIDogJyhtaXNzaW5nKSc7Cn0KCmZ1bmN0aW9uIG10dHIzX2ZpbmRfYWNjb3VudChhcnJheSAkYWNjb3VudHMsIGludCAkdWlkKTogYXJyYXkgewogICAgZm9yZWFjaCAoJGFjY291bnRzIGFzICRyb3cpIHsKICAgICAgICBpZiAoKGludCkoJHJvd1sndXNlcklEJ10gPz8gMCkgPT09ICR1aWQpIHJldHVybiAkcm93OwogICAgfQogICAgcmV0dXJuIFtdOwp9CgpmdW5jdGlvbiBtdHRyM190ZWFtX3JvdyhteXNxbGkgJGRiLCBpbnQgJHVpZCwgc3RyaW5nICR5ZWFyKTogYXJyYXkgewogICAgaWYgKCFtdHRyM19oYXNfY29sdW1ucygkZGIsICd1c2VyX3RlYW1zJywgWyd1c2VySUQnLCdyYWNlWWVhciddKSkgcmV0dXJuIFtdOwogICAgJHJvd3MgPSBtdHRyM19yb3dzKAogICAgICAgICRkYiwKICAgICAgICAiU0VMRUNUICogRlJPTSB1c2VyX3RlYW1zIFdIRVJFIHVzZXJJRCA9ID8gQU5EIHJhY2VZZWFyID0gPyBMSU1JVCAxIiwKICAgICAgICBbJHVpZCwgJHllYXJdLAogICAgICAgICdpcycKICAgICk7CiAgICByZXR1cm4gJHJvd3NbMF0gPz8gW107Cn0KCmZ1bmN0aW9uIG10dHIzX3RlYW1fbGFiZWwoYXJyYXkgJHJvdyk6IHN0cmluZyB7CiAgICBmb3JlYWNoIChbJ3RlYW1OYW1lJywndGVhbV9uYW1lJywnbmFtZSddIGFzICRrZXkpIHsKICAgICAgICBpZiAoaXNzZXQoJHJvd1ska2V5XSkgJiYgdHJpbSgoc3RyaW5nKSRyb3dbJGtleV0pICE9PSAnJykgcmV0dXJuIHRyaW0oKHN0cmluZykkcm93WyRrZXldKTsKICAgIH0KICAgIHJldHVybiAkcm93ID8gJyh0ZWFtIHJvdyBmb3VuZCknIDogJyhubyB0ZWFtIHJvdyBmb3IgdGhpcyB5ZWFyKSc7Cn0KCmZ1bmN0aW9uIG10dHIzX3llYXJzKG15c3FsaSAkZGIsIGludCAkdWlkLCBzdHJpbmcgJGZhbGxiYWNrKTogYXJyYXkgewogICAgJHllYXJzID0gWyRmYWxsYmFja107CiAgICBmb3JlYWNoIChbJ3VzZXJfcGlja3MnLCd1c2VyX3BpY2tzX2hpc3RvcnknLCd1c2VyX3RlYW1zJ10gYXMgJHRhYmxlKSB7CiAgICAgICAgaWYgKCFtdHRyM19oYXNfY29sdW1ucygkZGIsICR0YWJsZSwgWyd1c2VySUQnLCdyYWNlWWVhciddKSkgY29udGludWU7CiAgICAgICAgdHJ5IHsKICAgICAgICAgICAgJHJvd3MgPSBtdHRyM19yb3dzKAogICAgICAgICAgICAgICAgJGRiLAogICAgICAgICAgICAgICAgIlNFTEVDVCBESVNUSU5DVCByYWNlWWVhciBGUk9NIGAkdGFibGVgIFdIRVJFIHVzZXJJRCA9ID8gT1JERVIgQlkgcmFjZVllYXIgREVTQyIsCiAgICAgICAgICAgICAgICBbJHVpZF0sCiAgICAgICAgICAgICAgICAnaScKICAgICAgICAgICAgKTsKICAgICAgICAgICAgZm9yZWFjaCAoJHJvd3MgYXMgJHJvdykgewogICAgICAgICAgICAgICAgJHkgPSB0cmltKChzdHJpbmcpKCRyb3dbJ3JhY2VZZWFyJ10gPz8gJycpKTsKICAgICAgICAgICAgICAgIGlmIChwcmVnX21hdGNoKCcvXlxkezR9JC8nLCAkeSkpICR5ZWFyc1tdID0gJHk7CiAgICAgICAgICAgIH0KICAgICAgICB9IGNhdGNoIChUaHJvd2FibGUgJGUpIHt9CiAgICB9CiAgICAkeWVhcnMgPSBhcnJheV92YWx1ZXMoYXJyYXlfdW5pcXVlKCR5ZWFycykpOwogICAgcnNvcnQoJHllYXJzLCBTT1JUX1NUUklORyk7CiAgICByZXR1cm4gJHllYXJzOwp9CgpmdW5jdGlvbiBtdHRyM19iYWNrdXBfZGlyKCk6IHN0cmluZyB7CiAgICByZXR1cm4gX19ESVJfXyAuICcvX21pZ3JhdGlvbl9iYWNrdXBzL21ybF90ZXN0X3RlYW1fcmVzZXQnOwp9CgpmdW5jdGlvbiBtdHRyM19iYWNrdXAobXlzcWxpICRkYiwgaW50ICR1aWQsIHN0cmluZyAkeWVhcik6IHN0cmluZyB7CiAgICAkZGlyID0gbXR0cjNfYmFja3VwX2RpcigpOwogICAgaWYgKCFpc19kaXIoJGRpcikgJiYgIW1rZGlyKCRkaXIsIDA3NTUsIHRydWUpICYmICFpc19kaXIoJGRpcikpIHsKICAgICAgICB0aHJvdyBuZXcgUnVudGltZUV4Y2VwdGlvbignQ291bGQgbm90IGNyZWF0ZSBiYWNrdXAgZm9sZGVyLicpOwogICAgfQoKICAgICRwYXlsb2FkID0gWwogICAgICAgICd0b29sX3ZlcnNpb24nID0+ICd2MDAzJywKICAgICAgICAnY3JlYXRlZF9hdCcgPT4gZGF0ZSgnWS1tLWQgSDppOnMnKSwKICAgICAgICAndXNlcklEJyA9PiAkdWlkLAogICAgICAgICdyYWNlWWVhcicgPT4gJHllYXIsCiAgICAgICAgJ3VzZXJfcGlja3MnID0+IG10dHIzX3Jvd3MoCiAgICAgICAgICAgICRkYiwKICAgICAgICAgICAgIlNFTEVDVCAqIEZST00gdXNlcl9waWNrcyBXSEVSRSB1c2VySUQgPSA/IEFORCByYWNlWWVhciA9ID8gT1JERVIgQlkgcGlja0lEIEFTQyIsCiAgICAgICAgICAgIFskdWlkLCAkeWVhcl0sCiAgICAgICAgICAgICdpcycKICAgICAgICApLAogICAgICAgICd1c2VyX3BpY2tzX2hpc3RvcnknID0+IG10dHIzX3Jvd3MoCiAgICAgICAgICAgICRkYiwKICAgICAgICAgICAgIlNFTEVDVCAqIEZST00gdXNlcl9waWNrc19oaXN0b3J5IFdIRVJFIHVzZXJJRCA9ID8gQU5EIHJhY2VZZWFyID0gPyIsCiAgICAgICAgICAgIFskdWlkLCAkeWVhcl0sCiAgICAgICAgICAgICdpcycKICAgICAgICApLAogICAgXTsKCiAgICAkanNvbiA9IGpzb25fZW5jb2RlKCRwYXlsb2FkLCBKU09OX1BSRVRUWV9QUklOVCB8IEpTT05fVU5FU0NBUEVEX1NMQVNIRVMgfCBKU09OX0lOVkFMSURfVVRGOF9TVUJTVElUVVRFKTsKICAgIGlmICghaXNfc3RyaW5nKCRqc29uKSkgdGhyb3cgbmV3IFJ1bnRpbWVFeGNlcHRpb24oJ0NvdWxkIG5vdCBlbmNvZGUgYmFja3VwIEpTT04uJyk7CgogICAgJHBhdGggPSAkZGlyIC4gJy9NUkxfdGVzdF9yZXNldF9iYWNrdXBfVUlEJyAuICR1aWQgLiAnXycgLiAkeWVhciAuICdfJyAuIGRhdGUoJ1ltZF9IaXMnKSAuICcuanNvbic7CiAgICBpZiAoZmlsZV9wdXRfY29udGVudHMoJHBhdGgsICRqc29uIC4gUEhQX0VPTCwgTE9DS19FWCkgPT09IGZhbHNlKSB7CiAgICAgICAgdGhyb3cgbmV3IFJ1bnRpbWVFeGNlcHRpb24oJ0NvdWxkIG5vdCB3cml0ZSBiYWNrdXAgSlNPTi4nKTsKICAgIH0KICAgIHJldHVybiAkcGF0aDsKfQoKZnVuY3Rpb24gbXR0cjNfZGVsZXRlKG15c3FsaSAkZGIsIGludCAkdWlkLCBzdHJpbmcgJHllYXIpOiBhcnJheSB7CiAgICBteXNxbGlfYmVnaW5fdHJhbnNhY3Rpb24oJGRiKTsKICAgIHRyeSB7CiAgICAgICAgJGRlbGV0ZWQgPSBbXTsKCiAgICAgICAgZm9yZWFjaCAoWyd1c2VyX3BpY2tzX2hpc3RvcnknLCd1c2VyX3BpY2tzJ10gYXMgJHRhYmxlKSB7CiAgICAgICAgICAgICRzdG10ID0gbXlzcWxpX3ByZXBhcmUoJGRiLCAiREVMRVRFIEZST00gYCR0YWJsZWAgV0hFUkUgdXNlcklEID0gPyBBTkQgcmFjZVllYXIgPSA/Iik7CiAgICAgICAgICAgIGlmICghJHN0bXQpIHRocm93IG5ldyBSdW50aW1lRXhjZXB0aW9uKCJQcmVwYXJlIGZhaWxlZCBmb3IgJHRhYmxlOiAiIC4gbXlzcWxpX2Vycm9yKCRkYikpOwogICAgICAgICAgICAkeXIgPSAkeWVhcjsKICAgICAgICAgICAgbXlzcWxpX3N0bXRfYmluZF9wYXJhbSgkc3RtdCwgJ2lzJywgJHVpZCwgJHlyKTsKICAgICAgICAgICAgaWYgKCFteXNxbGlfc3RtdF9leGVjdXRlKCRzdG10KSkgewogICAgICAgICAgICAgICAgJGVyciA9IG15c3FsaV9zdG10X2Vycm9yKCRzdG10KTsKICAgICAgICAgICAgICAgIG15c3FsaV9zdG10X2Nsb3NlKCRzdG10KTsKICAgICAgICAgICAgICAgIHRocm93IG5ldyBSdW50aW1lRXhjZXB0aW9uKCJEZWxldGUgZmFpbGVkIGZvciAkdGFibGU6ICRlcnIiKTsKICAgICAgICAgICAgfQogICAgICAgICAgICAkZGVsZXRlZFskdGFibGVdID0gbXlzcWxpX3N0bXRfYWZmZWN0ZWRfcm93cygkc3RtdCk7CiAgICAgICAgICAgIG15c3FsaV9zdG10X2Nsb3NlKCRzdG10KTsKICAgICAgICB9CgogICAgICAgIG15c3FsaV9jb21taXQoJGRiKTsKICAgICAgICByZXR1cm4gJGRlbGV0ZWQ7CiAgICB9IGNhdGNoIChUaHJvd2FibGUgJGUpIHsKICAgICAgICBteXNxbGlfcm9sbGJhY2soJGRiKTsKICAgICAgICB0aHJvdyAkZTsKICAgIH0KfQoKaWYgKCFpc3NldCgkX1NFU1NJT05bJ210dHIzX2NzcmYnXSkpIHsKICAgICRfU0VTU0lPTlsnbXR0cjNfY3NyZiddID0gYmluMmhleChyYW5kb21fYnl0ZXMoMjQpKTsKfQoKJGN1cnJlbnRZZWFyID0gaXNzZXQoJHJhY2VZZWFyKSAmJiBwcmVnX21hdGNoKCcvXlxkezR9JC8nLCAoc3RyaW5nKSRyYWNlWWVhcikKICAgID8gKHN0cmluZykkcmFjZVllYXIKICAgIDogZGF0ZSgnWScpOwoKJGFjY291bnRzID0gbXR0cjNfdGVzdF9hY2NvdW50cygkZGJjb25uZWN0KTsKJGFjY291bnRJZHMgPSBhcnJheV9tYXAoc3RhdGljIGZ1bmN0aW9uICgkcm93KSB7CiAgICByZXR1cm4gKGludCkoJHJvd1sndXNlcklEJ10gPz8gMCk7Cn0sICRhY2NvdW50cyk7CgokcmVxdWVzdGVkVWlkID0gKGludCkoJF9QT1NUWyd0YXJnZXRVaWQnXSA/PyAkX0dFVFsndGFyZ2V0VWlkJ10gPz8gOTk5KTsKaWYgKCFpbl9hcnJheSgkcmVxdWVzdGVkVWlkLCAkYWNjb3VudElkcywgdHJ1ZSkpIHsKICAgIGlmIChpbl9hcnJheSg5OTksICRhY2NvdW50SWRzLCB0cnVlKSkgewogICAgICAgICRyZXF1ZXN0ZWRVaWQgPSA5OTk7CiAgICB9IGVsc2VpZiAoIWVtcHR5KCRhY2NvdW50SWRzKSkgewogICAgICAgICRyZXF1ZXN0ZWRVaWQgPSAoaW50KSRhY2NvdW50SWRzWzBdOwogICAgfSBlbHNlIHsKICAgICAgICAkcmVxdWVzdGVkVWlkID0gMDsKICAgIH0KfQoKJHNlbGVjdGVkVWlkID0gJHJlcXVlc3RlZFVpZDsKJGFjY291bnQgPSBtdHRyM19maW5kX2FjY291bnQoJGFjY291bnRzLCAkc2VsZWN0ZWRVaWQpOwoKJHllYXJzID0gJHNlbGVjdGVkVWlkID4gMAogICAgPyBtdHRyM195ZWFycygkZGJjb25uZWN0LCAkc2VsZWN0ZWRVaWQsICRjdXJyZW50WWVhcikKICAgIDogWyRjdXJyZW50WWVhcl07Cgokc2VsZWN0ZWRZZWFyID0gdHJpbSgoc3RyaW5nKSgkX1BPU1RbJ3JhY2VZZWFyJ10gPz8gJF9HRVRbJ3JhY2VZZWFyJ10gPz8gJGN1cnJlbnRZZWFyKSk7CmlmICghcHJlZ19tYXRjaCgnL15cZHs0fSQvJywgJHNlbGVjdGVkWWVhcikpICRzZWxlY3RlZFllYXIgPSAkY3VycmVudFllYXI7CgokdGVhbSA9ICRzZWxlY3RlZFVpZCA+IDAgPyBtdHRyM190ZWFtX3JvdygkZGJjb25uZWN0LCAkc2VsZWN0ZWRVaWQsICRzZWxlY3RlZFllYXIpIDogW107CgokdWlkSW5SYW5nZSA9ICgkc2VsZWN0ZWRVaWQgPj0gTVJMX1RFU1RfVUlEX01JTiAmJiAkc2VsZWN0ZWRVaWQgPD0gTVJMX1RFU1RfVUlEX01BWCk7CiRhY2NvdW50RXhpc3RzID0gIWVtcHR5KCRhY2NvdW50KSAmJiAoaW50KSgkYWNjb3VudFsndXNlcklEJ10gPz8gMCkgPT09ICRzZWxlY3RlZFVpZDsKCiRwcmVmbGlnaHQgPSBbCiAgICAndXNlcnMgY29udGFpbnMgdXNlcklEJyA9PiBtdHRyM19oYXNfY29sdW1ucygkZGJjb25uZWN0LCAndXNlcnMnLCBbJ3VzZXJJRCddKSwKICAgICdBdCBsZWFzdCBvbmUgOTAwLXNlcmllcyB0ZXN0IGFjY291bnQgZXhpc3RzJyA9PiAhZW1wdHkoJGFjY291bnRzKSwKICAgICdTZWxlY3RlZCB1c2VySUQgaXMgaW4gOTAwLTk5OSByYW5nZScgPT4gJHVpZEluUmFuZ2UsCiAgICAnU2VsZWN0ZWQgdGVzdCBhY2NvdW50IGV4aXN0cycgPT4gJGFjY291bnRFeGlzdHMsCiAgICAndXNlcl9waWNrcyBjb250YWlucyB1c2VySUQgKyByYWNlWWVhcicgPT4gbXR0cjNfaGFzX2NvbHVtbnMoJGRiY29ubmVjdCwgJ3VzZXJfcGlja3MnLCBbJ3VzZXJJRCcsJ3JhY2VZZWFyJ10pLAogICAgJ3VzZXJfcGlja3NfaGlzdG9yeSBjb250YWlucyB1c2VySUQgKyByYWNlWWVhcicgPT4gbXR0cjNfaGFzX2NvbHVtbnMoJGRiY29ubmVjdCwgJ3VzZXJfcGlja3NfaGlzdG9yeScsIFsndXNlcklEJywncmFjZVllYXInXSksCiAgICAndXNlcl90ZWFtcyBjb250YWlucyB1c2VySUQgKyByYWNlWWVhcicgPT4gbXR0cjNfaGFzX2NvbHVtbnMoJGRiY29ubmVjdCwgJ3VzZXJfdGVhbXMnLCBbJ3VzZXJJRCcsJ3JhY2VZZWFyJ10pLApdOwoKJHJlYWR5ID0gIWluX2FycmF5KGZhbHNlLCAkcHJlZmxpZ2h0LCB0cnVlKTsKJG1lc3NhZ2UgPSAnJzsKJG1lc3NhZ2VDbGFzcyA9ICdpbmZvJzsKCmlmICgkX1NFUlZFUlsnUkVRVUVTVF9NRVRIT0QnXSA9PT0gJ1BPU1QnICYmICgkX1BPU1RbJ2FjdGlvbiddID8/ICcnKSA9PT0gJ3Jlc2V0JykgewogICAgaWYgKCFoYXNoX2VxdWFscygoc3RyaW5nKSRfU0VTU0lPTlsnbXR0cjNfY3NyZiddLCAoc3RyaW5nKSgkX1BPU1RbJ2NzcmYnXSA/PyAnJykpKSB7CiAgICAgICAgJG1lc3NhZ2UgPSAnUmVzZXQgYmxvY2tlZDogc2VjdXJpdHkgdG9rZW4gbWlzbWF0Y2guJzsKICAgICAgICAkbWVzc2FnZUNsYXNzID0gJ2JhZCc7CiAgICB9IGVsc2VpZiAoISRyZWFkeSkgewogICAgICAgICRtZXNzYWdlID0gJ1Jlc2V0IGJsb2NrZWQgYmVjYXVzZSBwcmVmbGlnaHQgaXMgbm90IGZ1bGx5IGdyZWVuLic7CiAgICAgICAgJG1lc3NhZ2VDbGFzcyA9ICdiYWQnOwogICAgfSBlbHNlaWYgKHRyaW0oKHN0cmluZykoJF9QT1NUWydjb25maXJtX3RleHQnXSA/PyAnJykpICE9PSAoJ1JFU0VUIE1STCAnIC4gJHNlbGVjdGVkVWlkKSkgewogICAgICAgICRtZXNzYWdlID0gJ1Jlc2V0IGJsb2NrZWQ6IHR5cGUgUkVTRVQgTVJMICcgLiAkc2VsZWN0ZWRVaWQgLiAnIGV4YWN0bHkuJzsKICAgICAgICAkbWVzc2FnZUNsYXNzID0gJ2JhZCc7CiAgICB9IGVsc2UgewogICAgICAgIHRyeSB7CiAgICAgICAgICAgICRiYWNrdXAgPSBtdHRyM19iYWNrdXAoJGRiY29ubmVjdCwgJHNlbGVjdGVkVWlkLCAkc2VsZWN0ZWRZZWFyKTsKICAgICAgICAgICAgJGRlbGV0ZWQgPSBtdHRyM19kZWxldGUoJGRiY29ubmVjdCwgJHNlbGVjdGVkVWlkLCAkc2VsZWN0ZWRZZWFyKTsKCiAgICAgICAgICAgICRhZnRlclAgPSBtdHRyM19jb3VudCgkZGJjb25uZWN0LCAndXNlcl9waWNrcycsICRzZWxlY3RlZFVpZCwgJHNlbGVjdGVkWWVhcik7CiAgICAgICAgICAgICRhZnRlckggPSBtdHRyM19jb3VudCgkZGJjb25uZWN0LCAndXNlcl9waWNrc19oaXN0b3J5JywgJHNlbGVjdGVkVWlkLCAkc2VsZWN0ZWRZZWFyKTsKCiAgICAgICAgICAgIGlmICgkYWZ0ZXJQICE9PSAwIHx8ICRhZnRlckggIT09IDApIHsKICAgICAgICAgICAgICAgIHRocm93IG5ldyBSdW50aW1lRXhjZXB0aW9uKCdQb3N0ZmxpZ2h0IGZhaWxlZDogcm93cyByZW1haW4gYWZ0ZXIgcmVzZXQuJyk7CiAgICAgICAgICAgIH0KCiAgICAgICAgICAgICRtZXNzYWdlID0gJ1JFU0VUIENPTVBMRVRFIOKAlCB1c2VySUQgJyAuICRzZWxlY3RlZFVpZCAuICc6IGRlbGV0ZWQgJwogICAgICAgICAgICAgICAgLiAoaW50KSgkZGVsZXRlZFsndXNlcl9waWNrcyddID8/IDApIC4gJyBsaXZlIHBpY2sgcm93KHMpIGFuZCAnCiAgICAgICAgICAgICAgICAuIChpbnQpKCRkZWxldGVkWyd1c2VyX3BpY2tzX2hpc3RvcnknXSA/PyAwKSAuICcgaGlzdG9yeSByb3cocykuICcKICAgICAgICAgICAgICAgIC4gJ0JhY2t1cDogJyAuIGJhc2VuYW1lKCRiYWNrdXApIC4gJy4nOwogICAgICAgICAgICAkbWVzc2FnZUNsYXNzID0gJ29rJzsKICAgICAgICB9IGNhdGNoIChUaHJvd2FibGUgJGUpIHsKICAgICAgICAgICAgJG1lc3NhZ2UgPSAnUmVzZXQgZmFpbGVkOiAnIC4gJGUtPmdldE1lc3NhZ2UoKTsKICAgICAgICAgICAgJG1lc3NhZ2VDbGFzcyA9ICdiYWQnOwogICAgICAgIH0KICAgIH0KfQoKJHBpY2tzQ291bnQgPSAkcmVhZHkgPyBtdHRyM19jb3VudCgkZGJjb25uZWN0LCAndXNlcl9waWNrcycsICRzZWxlY3RlZFVpZCwgJHNlbGVjdGVkWWVhcikgOiAwOwokaGlzdG9yeUNvdW50ID0gJHJlYWR5ID8gbXR0cjNfY291bnQoJGRiY29ubmVjdCwgJ3VzZXJfcGlja3NfaGlzdG9yeScsICRzZWxlY3RlZFVpZCwgJHNlbGVjdGVkWWVhcikgOiAwOwoKJGFjY291bnRBY3RpdmUgPSAnJzsKaWYgKGFycmF5X2tleV9leGlzdHMoJ3VzZXJBY3RpdmUnLCAkYWNjb3VudCkpIHsKICAgICRhY2NvdW50QWN0aXZlID0gdHJpbSgoc3RyaW5nKSRhY2NvdW50Wyd1c2VyQWN0aXZlJ10pOwp9Cgo/Pgo8IWRvY3R5cGUgaHRtbD4KPGh0bWwgbGFuZz0iZW4iPgo8aGVhZD4KPG1ldGEgY2hhcnNldD0idXRmLTgiPgo8bWV0YSBuYW1lPSJ2aWV3cG9ydCIgY29udGVudD0id2lkdGg9ZGV2aWNlLXdpZHRoLGluaXRpYWwtc2NhbGU9MSI+Cjx0aXRsZT5NUkwgVGVzdCBBY2NvdW50IFJlc2V0IHYwMDM8L3RpdGxlPgo8c3R5bGU+Cjpyb290ey0tYmc6IzExMTMxNTstLXBhbmVsOiMxZDIwMjM7LS1ib3JkZXI6IzUxNTY1YjstLXRleHQ6I2VlZTstLW11dGVkOiNiOWJlYzQ7LS1nb2xkOiNlZmNhODQ7LS1ncmVlbjojNmJlYTlmOy0tcmVkOiNmZjdlN2U7LS1ibHVlOiM2NWM4ZmZ9Cip7Ym94LXNpemluZzpib3JkZXItYm94fQpib2R5e21hcmdpbjowO2JhY2tncm91bmQ6dmFyKC0tYmcpO2NvbG9yOnZhcigtLXRleHQpO2ZvbnQtZmFtaWx5OlRhaG9tYSxWZXJkYW5hLFNlZ29lIFVJLHNhbnMtc2VyaWZ9Ci53cmFwe3dpZHRoOjk2JTttYXgtd2lkdGg6MTEwMHB4O21hcmdpbjoyMHB4IGF1dG99Ci5jYXJke2JhY2tncm91bmQ6dmFyKC0tcGFuZWwpO2JvcmRlcjoxcHggc29saWQgdmFyKC0tYm9yZGVyKTtib3JkZXItcmFkaXVzOjE0cHg7cGFkZGluZzoxOHB4IDIwcHg7bWFyZ2luLWJvdHRvbToxNnB4fQpoMSxoMntjb2xvcjp2YXIoLS1nb2xkKTttYXJnaW4tdG9wOjB9CmF7Y29sb3I6dmFyKC0tYmx1ZSl9Ci5ncmlke2Rpc3BsYXk6Z3JpZDtncmlkLXRlbXBsYXRlLWNvbHVtbnM6cmVwZWF0KDIsbWlubWF4KDAsMWZyKSk7Z2FwOjE0cHh9Ci5zdGF0e2JhY2tncm91bmQ6IzE3MTkxYjtib3JkZXI6MXB4IHNvbGlkICM0NDQ7Ym9yZGVyLXJhZGl1czoxMHB4O3BhZGRpbmc6MTRweH0KLm51bXtmb250LXNpemU6MzJweDtmb250LXdlaWdodDo4MDB9Ci5sYWJlbHtjb2xvcjp2YXIoLS1tdXRlZCl9Ci5iYW5uZXJ7cGFkZGluZzoxMnB4IDE1cHg7Ym9yZGVyLXJhZGl1czoxMHB4O21hcmdpbjoxMnB4IDA7Zm9udC13ZWlnaHQ6ODAwfQoub2t7YmFja2dyb3VuZDojMTIzYTJhO2JvcmRlcjoxcHggc29saWQgIzJiODE1Yjtjb2xvcjojZDlmZmVhfQouYmFke2JhY2tncm91bmQ6IzRhMTgxODtib3JkZXI6MXB4IHNvbGlkICNhNjRlNGU7Y29sb3I6I2ZmZDRkNH0KLmluZm97YmFja2dyb3VuZDojMTIyYTNhO2JvcmRlcjoxcHggc29saWQgIzJkNmE4Yztjb2xvcjojZDhmMmZmfQp0YWJsZXt3aWR0aDoxMDAlO2JvcmRlci1jb2xsYXBzZTpjb2xsYXBzZX0KdGgsdGR7cGFkZGluZzo4cHg7Ym9yZGVyLWJvdHRvbToxcHggc29saWQgIzNhM2U0Mjt0ZXh0LWFsaWduOmxlZnR9CnRoe2NvbG9yOiNmZmUwYTB9Ci5wYXNze2NvbG9yOnZhcigtLWdyZWVuKTtmb250LXdlaWdodDo4MDB9Ci5mYWlse2NvbG9yOnZhcigtLXJlZCk7Zm9udC13ZWlnaHQ6ODAwfQppbnB1dCxzZWxlY3R7cGFkZGluZzo5cHg7YmFja2dyb3VuZDojMTAxMjE0O2NvbG9yOiNlZWU7Ym9yZGVyOjFweCBzb2xpZCAjNjY2O2JvcmRlci1yYWRpdXM6N3B4O2ZvbnQtc2l6ZToxNXB4fQouYnRue3BhZGRpbmc6MTFweCAxOHB4O2JvcmRlci1yYWRpdXM6OHB4O2ZvbnQtd2VpZ2h0OjgwMDtjdXJzb3I6cG9pbnRlcn0KLnJlc2V0e2JhY2tncm91bmQ6I2EzMjIyMjtjb2xvcjojZmZmO2JvcmRlcjoxcHggc29saWQgI2VmNjY2Nn0KLnRhcmdldC1yb3d7ZGlzcGxheTpmbGV4O2dhcDoxNHB4O2FsaWduLWl0ZW1zOmVuZDtmbGV4LXdyYXA6d3JhcH0KLnRhcmdldC1yb3cgbGFiZWx7ZGlzcGxheTpmbGV4O2ZsZXgtZGlyZWN0aW9uOmNvbHVtbjtnYXA6NnB4fQpAbWVkaWEobWF4LXdpZHRoOjc2MHB4KXsuZ3JpZHtncmlkLXRlbXBsYXRlLWNvbHVtbnM6MWZyfX0KPC9zdHlsZT4KPC9oZWFkPgo8Ym9keT4KPGRpdiBjbGFzcz0id3JhcCI+Cgo8ZGl2IGNsYXNzPSJjYXJkIj4KPGgxPk1STCBUZXN0IEFjY291bnQgUmVzZXQ8L2gxPgo8cD48c3Ryb25nPlZFUlNJT046PC9zdHJvbmc+IHYwMDMgJm5ic3A7IHwgJm5ic3A7IDxzdHJvbmc+TGFzdCBtb2RpZmllZDo8L3N0cm9uZz4gOS8yMy8yMDI2IDM6NTc6MTggYW08L3A+CjxwPjxhIGhyZWY9Ii90ZWFtLnBocCI+4oaQIFRlYW08L2E+PC9wPgoKPD9waHAgaWYgKCRtZXNzYWdlICE9PSAnJyk6ID8+CjxkaXYgY2xhc3M9ImJhbm5lciA8P3BocCBlY2hvIG10dHIzX2goJG1lc3NhZ2VDbGFzcyk7ID8+Ij48P3BocCBlY2hvIG10dHIzX2goJG1lc3NhZ2UpOyA/PjwvZGl2Pgo8P3BocCBlbmRpZjsgPz4KCjxkaXYgY2xhc3M9ImJhbm5lciBpbmZvIj4KU2VsZWN0YWJsZSB0YXJnZXQ6IDxzdHJvbmc+ZXhpc3RpbmcgdXNlcklEcyA5MDAtOTk5IG9ubHkuPC9zdHJvbmc+Ckl0IGNsZWFycyBvbmx5IGxpdmUgcGlja3MgKyBwaWNrIGhpc3RvcnkgZm9yIHRoZSBzZWxlY3RlZCB1c2VyIGFuZCBzZWxlY3RlZCB5ZWFyLgo8L2Rpdj4KPC9kaXY+Cgo8ZGl2IGNsYXNzPSJjYXJkIj4KPGgyPlRhcmdldDwvaDI+Cjxmb3JtIG1ldGhvZD0iZ2V0IiBjbGFzcz0idGFyZ2V0LXJvdyI+CjxsYWJlbD5UZXN0IGFjY291bnQ6CjxzZWxlY3QgbmFtZT0idGFyZ2V0VWlkIiBvbmNoYW5nZT0idGhpcy5mb3JtLnN1Ym1pdCgpIj4KPD9waHAgZm9yZWFjaCAoJGFjY291bnRzIGFzICRyb3cpOiA/Pgo8P3BocAogICAgJHVpZCA9IChpbnQpKCRyb3dbJ3VzZXJJRCddID8/IDApOwogICAgJGxhYmVsID0gbXR0cjNfYWNjb3VudF9sYWJlbCgkcm93KTsKPz4KPG9wdGlvbiB2YWx1ZT0iPD9waHAgZWNobyAkdWlkOyA/PiIgPD9waHAgZWNobyAkdWlkID09PSAkc2VsZWN0ZWRVaWQgPyAnc2VsZWN0ZWQnIDogJyc7ID8+Pgo8P3BocCBlY2hvIG10dHIzX2goJGxhYmVsIC4gJyAoSUQgJyAuICR1aWQgLiAnKScpOyA/Pgo8L29wdGlvbj4KPD9waHAgZW5kZm9yZWFjaDsgPz4KPC9zZWxlY3Q+CjwvbGFiZWw+Cgo8bGFiZWw+UmFjZSB5ZWFyOgo8c2VsZWN0IG5hbWU9InJhY2VZZWFyIiBvbmNoYW5nZT0idGhpcy5mb3JtLnN1Ym1pdCgpIj4KPD9waHAgZm9yZWFjaCAoJHllYXJzIGFzICR5KTogPz4KPG9wdGlvbiB2YWx1ZT0iPD9waHAgZWNobyBtdHRyM19oKCR5KTsgPz4iIDw/cGhwIGVjaG8gJHkgPT09ICRzZWxlY3RlZFllYXIgPyAnc2VsZWN0ZWQnIDogJyc7ID8+Pgo8P3BocCBlY2hvIG10dHIzX2goJHkpOyA/Pgo8L29wdGlvbj4KPD9waHAgZW5kZm9yZWFjaDsgPz4KPC9zZWxlY3Q+CjwvbGFiZWw+CjwvZm9ybT4KCjxwPgo8c3Ryb25nPkFjY291bnQ6PC9zdHJvbmc+IDw/cGhwIGVjaG8gbXR0cjNfaChtdHRyM19hY2NvdW50X2xhYmVsKCRhY2NvdW50KSk7ID8+Cih1c2VySUQgPD9waHAgZWNobyAoaW50KSRzZWxlY3RlZFVpZDsgPz4pCjw/cGhwIGlmICgkYWNjb3VudEFjdGl2ZSAhPT0gJycpOiA/Pgo8YnI+PHN0cm9uZz51c2VyQWN0aXZlOjwvc3Ryb25nPiA8P3BocCBlY2hvIG10dHIzX2goJGFjY291bnRBY3RpdmUpOyA/Pgo8P3BocCBlbmRpZjsgPz4KPGJyPgo8c3Ryb25nPlRlYW06PC9zdHJvbmc+IDw/cGhwIGVjaG8gbXR0cjNfaChtdHRyM190ZWFtX2xhYmVsKCR0ZWFtKSk7ID8+CjwvcD4KPC9kaXY+Cgo8ZGl2IGNsYXNzPSJjYXJkIj4KPGgyPkN1cnJlbnQgVGVzdCBEYXRhPC9oMj4KPGRpdiBjbGFzcz0iZ3JpZCI+CjxkaXYgY2xhc3M9InN0YXQiPjxkaXYgY2xhc3M9Im51bSI+PD9waHAgZWNobyAkcGlja3NDb3VudDsgPz48L2Rpdj48ZGl2IGNsYXNzPSJsYWJlbCI+TGl2ZSB1c2VyX3BpY2tzIHJvd3M8L2Rpdj48L2Rpdj4KPGRpdiBjbGFzcz0ic3RhdCI+PGRpdiBjbGFzcz0ibnVtIj48P3BocCBlY2hvICRoaXN0b3J5Q291bnQ7ID8+PC9kaXY+PGRpdiBjbGFzcz0ibGFiZWwiPnVzZXJfcGlja3NfaGlzdG9yeSByb3dzPC9kaXY+PC9kaXY+CjwvZGl2PgoKPD9waHAgaWYgKCRyZWFkeSAmJiAkcGlja3NDb3VudCA9PT0gMCAmJiAkaGlzdG9yeUNvdW50ID09PSAwKTogPz4KPGRpdiBjbGFzcz0iYmFubmVyIG9rIj5BbHJlYWR5IGNsZWFuIGZvciB1c2VySUQgPD9waHAgZWNobyAoaW50KSRzZWxlY3RlZFVpZDsgPz4gLyA8P3BocCBlY2hvIG10dHIzX2goJHNlbGVjdGVkWWVhcik7ID8+IOKAlCBubyBzdWJtaXNzaW9ucyBleGlzdC48L2Rpdj4KPD9waHAgZW5kaWY7ID8+CjwvZGl2PgoKPGRpdiBjbGFzcz0iY2FyZCI+CjxoMj5QcmVmbGlnaHQ8L2gyPgo8dGFibGU+Cjx0aGVhZD48dHI+PHRoPkNoZWNrPC90aD48dGg+U3RhdHVzPC90aD48L3RyPjwvdGhlYWQ+Cjx0Ym9keT4KPD9waHAgZm9yZWFjaCAoJHByZWZsaWdodCBhcyAkbGFiZWwgPT4gJHN0YXR1cyk6ID8+Cjx0cj4KPHRkPjw/cGhwIGVjaG8gbXR0cjNfaCgkbGFiZWwpOyA/PjwvdGQ+Cjx0ZCBjbGFzcz0iPD9waHAgZWNobyAkc3RhdHVzID8gJ3Bhc3MnIDogJ2ZhaWwnOyA/PiI+PD9waHAgZWNobyAkc3RhdHVzID8gJ1BBU1MnIDogJ0ZBSUwnOyA/PjwvdGQ+CjwvdHI+Cjw/cGhwIGVuZGZvcmVhY2g7ID8+CjwvdGJvZHk+CjwvdGFibGU+CjwvZGl2PgoKPGRpdiBjbGFzcz0iY2FyZCI+CjxoMj5SZXNldCA8P3BocCBlY2hvIG10dHIzX2goJHNlbGVjdGVkWWVhcik7ID8+PC9oMj4KPHA+QSBjb21wbGV0ZSBKU09OIGJhY2t1cCBpcyB3cml0dGVuIGZpcnN0LjwvcD4KCjxmb3JtIG1ldGhvZD0icG9zdCIgb25zdWJtaXQ9InJldHVybiBjb25maXJtKCdSZXNldCBNUkwgdXNlcklEIDw/cGhwIGVjaG8gKGludCkkc2VsZWN0ZWRVaWQ7ID8+IHBpY2tzL2hpc3RvcnkgZm9yIDw/cGhwIGVjaG8gbXR0cjNfaCgkc2VsZWN0ZWRZZWFyKTsgPz4/Jyk7Ij4KPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iY3NyZiIgdmFsdWU9Ijw/cGhwIGVjaG8gbXR0cjNfaCgoc3RyaW5nKSRfU0VTU0lPTlsnbXR0cjNfY3NyZiddKTsgPz4iPgo8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJhY3Rpb24iIHZhbHVlPSJyZXNldCI+CjxpbnB1dCB0eXBlPSJoaWRkZW4iIG5hbWU9InRhcmdldFVpZCIgdmFsdWU9Ijw/cGhwIGVjaG8gKGludCkkc2VsZWN0ZWRVaWQ7ID8+Ij4KPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0icmFjZVllYXIiIHZhbHVlPSI8P3BocCBlY2hvIG10dHIzX2goJHNlbGVjdGVkWWVhcik7ID8+Ij4KCjxwPlR5cGUgPHN0cm9uZz5SRVNFVCBNUkwgPD9waHAgZWNobyAoaW50KSRzZWxlY3RlZFVpZDsgPz48L3N0cm9uZz46PC9wPgo8aW5wdXQgbmFtZT0iY29uZmlybV90ZXh0IiBhdXRvY29tcGxldGU9Im9mZiIgc3R5bGU9IndpZHRoOjEwMCU7bWF4LXdpZHRoOjM2MHB4IiBwbGFjZWhvbGRlcj0iUkVTRVQgTVJMIDw/cGhwIGVjaG8gKGludCkkc2VsZWN0ZWRVaWQ7ID8+Ij4KPGJyPjxicj4KPGJ1dHRvbiBjbGFzcz0iYnRuIHJlc2V0IiB0eXBlPSJzdWJtaXQiIDw/cGhwIGVjaG8gJHJlYWR5ID8gJycgOiAnZGlzYWJsZWQnOyA/Pj5SZXNldCBTZWxlY3RlZCBUZXN0IFBpY2tzICsgSGlzdG9yeTwvYnV0dG9uPgo8L2Zvcm0+CjwvZGl2PgoKPC9kaXY+CjwvYm9keT4KPC9odG1sPgo=';

function mritr_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function mritr_target_path(): string {
    return __DIR__ . '/' . MRL_TARGET_REL;
}

function mritr_detect_version(string $content): string {
    if (preg_match('/VERSION:\s*(v\d{3})/', $content, $m)) return (string)$m[1];
    return '';
}

function mritr_lint(string $file): array {
    if (!function_exists('shell_exec')) {
        return ['ok'=>false,'output'=>'shell_exec() is not available. Check Hostinger PHP Configuration -> disableFunctions.'];
    }
    $cmd = 'php -l ' . escapeshellarg($file) . ' 2>&1';
    $out = shell_exec($cmd);
    $text = trim((string)$out);
    return [
        'ok' => ($text !== '' && strpos($text, 'No syntax errors detected') !== false),
        'output' => $text,
    ];
}

function mritr_candidate(): string {
    $raw = base64_decode(MRL_TARGET_B64, true);
    if (!is_string($raw)) throw new RuntimeException('Embedded candidate decode failed.');
    if (hash('sha256', $raw) !== MRL_TARGET_SHA) throw new RuntimeException('Embedded candidate SHA-256 mismatch.');
    return $raw;
}

function mritr_temp_candidate(string $candidate): array {
    $tmp = tempnam(sys_get_temp_dir(), 'mrlreset_');
    if (!$tmp) return ['ok'=>false,'path'=>'','lint'=>['ok'=>false,'output'=>'Could not create temp file.']];
    if (file_put_contents($tmp, $candidate, LOCK_EX) === false) {
        @unlink($tmp);
        return ['ok'=>false,'path'=>'','lint'=>['ok'=>false,'output'=>'Could not write temp file.']];
    }
    $lint = mritr_lint($tmp);
    return ['ok'=>(bool)$lint['ok'],'path'=>$tmp,'lint'=>$lint];
}

function mritr_preflight(): array {
    $path = mritr_target_path();
    $checks = [];
    $files = [];

    $exists = is_file($path);
    $checks[] = ['label'=>'Target exists','status'=>$exists?'PASS':'FAIL','required'=>true,'detail'=>$path];

    $writable = $exists && is_writable($path);
    $checks[] = ['label'=>'Target writable','status'=>$writable?'PASS':'FAIL','required'=>true,'detail'=>$writable?'Writable.':'Not writable.'];

    $shell = function_exists('shell_exec');
    $checks[] = ['label'=>'shell_exec available for Hostinger php -l','status'=>$shell?'PASS':'FAIL','required'=>true,'detail'=>$shell?'Available.':'Unavailable; check Hostinger disableFunctions.'];

    $src = $exists ? (string)file_get_contents($path) : '';
    $version = mritr_detect_version($src);
    $verOk = ($version === MRL_TARGET_EXPECTED);
    $checks[] = ['label'=>'Baseline version','status'=>$verOk?'PASS':'FAIL','required'=>true,'detail'=>'Detected ' . ($version ?: '(none)') . '; expected ' . MRL_TARGET_EXPECTED . '.'];

    $sig1 = strpos($src, 'const MRL_TEST_UID = 999;') !== false;
    $sig2 = strpos($src, 'Hard-coded target: <strong>userID 999 only.</strong>') !== false;
    $sigOk = $sig1 && $sig2;
    $checks[] = ['label'=>'Baseline signatures','status'=>$sigOk?'PASS':'FAIL','required'=>true,'detail'=>$sigOk?'Expected v002 hard-coded-999 signatures found.':'Expected baseline signatures not found.'];

    $candidate = '';
    $cand = ['ok'=>false,'path'=>'','lint'=>['ok'=>false,'output'=>'Candidate not built.']];
    try {
        $candidate = mritr_candidate();
        $cand = mritr_temp_candidate($candidate);
    } catch (Throwable $e) {
        $cand['lint'] = ['ok'=>false,'output'=>$e->getMessage()];
    }
    $checks[] = ['label'=>'Candidate SHA-256','status'=>(hash('sha256',$candidate)===MRL_TARGET_SHA)?'PASS':'FAIL','required'=>true,'detail'=>'Expected ' . MRL_TARGET_SHA . '.'];
    $checks[] = ['label'=>'Candidate php -l','status'=>$cand['lint']['ok']?'PASS':'FAIL','required'=>true,'detail'=>(string)$cand['lint']['output']];

    if (!empty($cand['path'])) @unlink((string)$cand['path']);

    $files[MRL_TARGET_REL] = [
        'path'=>$path,
        'detected_version'=>$version,
        'expected_version'=>MRL_TARGET_EXPECTED,
        'target_version'=>MRL_TARGET_NEW,
        'sha256_before'=>$exists?hash_file('sha256',$path):'',
        'candidate_sha256'=>$candidate!==''?hash('sha256',$candidate):'',
    ];

    $ok = true;
    foreach ($checks as $c) if (!empty($c['required']) && $c['status'] !== 'PASS') $ok = false;

    return [
        'installer'=>'install_admin_mrl_test_team_reset_v003',
        'installer_version'=>MRL_INSTALLER_VERSION,
        'report_stage'=>'PRECHECK',
        'report_generated_et'=>date('c'),
        'scope'=>[
            'change'=>'admin_mrl_test_team_reset.php v002 -> v003',
            'target_rule'=>'Selectable existing users in userID range 900-999 only.',
            'preserves'=>['users row/login','user_teams/team name','all non-selected users','all other years','scoring/results/snapshots'],
            'installer_report_ux'=>'Compact JSON text box + Copy button; file download remains optional.',
        ],
        'result'=>['ok'=>$ok,'checks'=>$checks,'files'=>$files],
        'last_installer_session'=>$_SESSION['mritr_last'] ?? [],
    ];
}

function mritr_backup(string $path): array {
    $root = __DIR__ . '/_installer_backups/install_admin_mrl_test_team_reset_v003_v001_' . date('Ymd_hisA');
    if (!is_dir($root) && !mkdir($root,0755,true) && !is_dir($root)) throw new RuntimeException('Could not create backup folder.');
    $backup = $root . '/' . basename($path);
    if (!copy($path,$backup)) throw new RuntimeException('Could not create backup.');
    $srcHash = hash_file('sha256',$path);
    $bakHash = hash_file('sha256',$backup);
    if ($srcHash !== $bakHash) throw new RuntimeException('Backup hash mismatch.');
    return ['backup_root'=>$root,'source'=>$path,'backup'=>$backup,'sha256_source'=>$srcHash,'sha256_backup'=>$bakHash];
}

function mritr_atomic_write(string $path, string $content): void {
    $tmp = $path . '.mrlnew_' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp,$content,LOCK_EX) === false) throw new RuntimeException('Could not write staged target.');
    if (!@rename($tmp,$path)) {
        @unlink($tmp);
        throw new RuntimeException('Could not atomically replace target.');
    }
}

function mritr_postcheck(array $session): array {
    $path = mritr_target_path();
    $checks = [];
    $exists = is_file($path);
    $content = $exists ? (string)file_get_contents($path) : '';
    $version = mritr_detect_version($content);
    $checks[]=['label'=>'Installed version','status'=>$version===MRL_TARGET_NEW?'PASS':'FAIL','required'=>true,'detail'=>'Detected ' . ($version?:'(none)') . '; expected ' . MRL_TARGET_NEW . '.'];

    $sig = strpos($content,'const MRL_TEST_UID_MIN = 900;') !== false
        && strpos($content,'const MRL_TEST_UID_MAX = 999;') !== false
        && strpos($content,'Selectable target: <strong>existing userIDs 900-999 only.</strong>') !== false
        && strpos($content,"RESET MRL ' . \$selectedUid") !== false;
    $checks[]=['label'=>'Installed 900-series selection signatures','status'=>$sig?'PASS':'FAIL','required'=>true,'detail'=>$sig?'Expected v003 selection/safety signatures found.':'Expected signatures missing.'];

    $sha = $exists ? hash_file('sha256',$path) : '';
    $checks[]=['label'=>'Installed SHA-256 matches candidate','status'=>$sha===MRL_TARGET_SHA?'PASS':'FAIL','required'=>true,'detail'=>$sha];

    $lint = $exists ? mritr_lint($path) : ['ok'=>false,'output'=>'Target missing.'];
    $checks[]=['label'=>'Installed php -l','status'=>$lint['ok']?'PASS':'FAIL','required'=>true,'detail'=>(string)$lint['output']];

    $ok=true;
    foreach($checks as $c) if(!empty($c['required']) && $c['status']!=='PASS') $ok=false;

    return [
        'installer'=>'install_admin_mrl_test_team_reset_v003',
        'installer_version'=>MRL_INSTALLER_VERSION,
        'report_stage'=>'POSTCHECK',
        'report_generated_et'=>date('c'),
        'result'=>[
            'ok'=>$ok,
            'checks'=>$checks,
            'files'=>[
                MRL_TARGET_REL=>[
                    'path'=>$path,
                    'detected_version'=>$version,
                    'target_version'=>MRL_TARGET_NEW,
                    'sha256_after'=>$sha,
                    'lint'=>$lint,
                ]
            ]
        ],
        'last_installer_session'=>$session,
    ];
}

if (!isset($_SESSION['mritr_csrf'])) $_SESSION['mritr_csrf']=bin2hex(random_bytes(24));

$message='';
$messageClass='info';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=(string)($_POST['action']??'');
    if (!hash_equals((string)$_SESSION['mritr_csrf'],(string)($_POST['csrf']??''))) {
        $message='Security token mismatch.';
        $messageClass='bad';
    } elseif ($action==='apply') {
        $pre=mritr_preflight();
        if (empty($pre['result']['ok'])) {
            $message='Apply blocked: preflight is not fully green.';
            $messageClass='bad';
        } else {
            $path=mritr_target_path();
            $backupInfo=[];
            try {
                $backupInfo=mritr_backup($path);
                $candidate=mritr_candidate();
                mritr_atomic_write($path,$candidate);

                $session=[
                    'last_action'=>'apply',
                    'at_et'=>date('c'),
                    'ok'=>true,
                    'message'=>'Apply completed; running postflight.',
                    'backup'=>$backupInfo,
                    'automatic_rollback'=>null,
                ];
                $post=mritr_postcheck($session);

                if (empty($post['result']['ok'])) {
                    if (isset($backupInfo['backup']) && is_file($backupInfo['backup'])) {
                        copy($backupInfo['backup'],$path);
                        $session['ok']=false;
                        $session['message']='Critical postflight failed; original automatically restored.';
                        $session['automatic_rollback']=['attempted'=>true,'ok'=>hash_file('sha256',$path)===$backupInfo['sha256_backup']];
                    }
                    $_SESSION['mritr_last']=$session;
                    $_SESSION['mritr_post']=mritr_postcheck($session);
                    $message=$session['message'];
                    $messageClass='bad';
                } else {
                    $session['message']='PASS — reset utility v003 installed and passed postflight.';
                    $_SESSION['mritr_last']=$session;
                    $_SESSION['mritr_post']=mritr_postcheck($session);
                    $message=$session['message'];
                    $messageClass='ok';
                }
            } catch (Throwable $e) {
                if (!empty($backupInfo['backup']) && is_file($backupInfo['backup'])) @copy($backupInfo['backup'],$path);
                $_SESSION['mritr_last']=[
                    'last_action'=>'apply','at_et'=>date('c'),'ok'=>false,
                    'message'=>'Apply failed: '.$e->getMessage(),
                    'backup'=>$backupInfo,
                    'automatic_rollback'=>['attempted'=>!empty($backupInfo),'ok'=>!empty($backupInfo) && is_file($path)],
                ];
                $_SESSION['mritr_post']=mritr_postcheck($_SESSION['mritr_last']);
                $message='Apply failed: '.$e->getMessage();
                $messageClass='bad';
            }
        }
    } elseif ($action==='rollback') {
        $last=$_SESSION['mritr_last']??[];
        $backup=(string)($last['backup']['backup']??'');
        $path=mritr_target_path();
        if ($backup==='' || !is_file($backup)) {
            $message='Rollback unavailable: no valid backup from this installer session.';
            $messageClass='bad';
        } else {
            if (copy($backup,$path)) {
                $lint=mritr_lint($path);
                $_SESSION['mritr_last']=[
                    'last_action'=>'rollback','at_et'=>date('c'),'ok'=>(bool)$lint['ok'],
                    'message'=>$lint['ok']?'Rollback complete and restored file passes php -l.':'Rollback copied backup but restored file failed php -l.',
                    'backup'=>$last['backup']??[],
                    'automatic_rollback'=>null,
                ];
                $_SESSION['mritr_post']=mritr_postcheck($_SESSION['mritr_last']);
                $message=$_SESSION['mritr_last']['message'];
                $messageClass=$lint['ok']?'ok':'bad';
            } else {
                $message='Rollback failed: could not restore backup.';
                $messageClass='bad';
            }
        }
    }
}

$pre=mritr_preflight();
$post=$_SESSION['mritr_post']??null;

$preJson=json_encode($pre,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
$postJson=is_array($post)?json_encode($post,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE):'';

if (isset($_GET['download']) && in_array($_GET['download'],['pre','post'],true)) {
    $which=(string)$_GET['download'];
    $payload=$which==='post'?$postJson:$preJson;
    if ($payload==='') $payload="{\n  \"status\": \"No postcheck available yet\"\n}";
    $stage=$which==='post'?'POSTCHECK':'PRECHECK';
    $filename='install_admin_mrl_test_team_reset_v003_v001_' . date('Ymd_His') . '_' . $stage . '.json';
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo $payload . PHP_EOL;
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install MRL Test Account Reset v003</title>
<style>
:root{--bg:#101214;--panel:#1b1f23;--border:#414850;--text:#eef2f6;--muted:#aeb8c1;--gold:#ffcf83;--green:#57e38c;--red:#ff7373;--blue:#8fc8ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px Arial,Helvetica,sans-serif}
.wrap{max-width:1120px;margin:22px auto 50px;padding:0 16px}.card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:14px}
h1,h2{color:var(--gold);margin-top:0}table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #363d44;text-align:left;vertical-align:top}
.pass{color:var(--green);font-weight:800}.fail{color:var(--red);font-weight:800}.banner{padding:12px 14px;border-radius:9px;margin:12px 0;font-weight:700}
.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}.info{background:#123044;border:1px solid #286a93;color:#dbf2ff}
.btn{border:0;border-radius:7px;padding:11px 16px;font-weight:800;cursor:pointer;margin:3px 6px 3px 0}.green{background:#23864b;color:#fff}.blue{background:#2f6feb;color:#fff}.red{background:#a32222;color:#fff}
.reportbox{width:100%;height:155px;resize:vertical;background:#0f1215;color:#e7edf3;border:1px solid #59616a;border-radius:8px;padding:10px;font:12px/1.4 Consolas,Menlo,monospace;white-space:pre;overflow:auto}
.report-actions{margin-top:8px}.muted{color:var(--muted)}code{color:#ffd27f}
</style>
</head>
<body><div class="wrap">
<div class="card">
<h1>MRL Test Account Reset v003 Installer</h1>
<div class="muted">Installer v001 | Generated 9/23/2026 3:57:18 am ET</div>
<div class="banner info">Scope: upgrade the reset utility from hard-coded ID 999 to a selectable list of existing 900-999 test accounts. No email, cron, scoring, or pick data is changed by this installer.</div>
<?php if($message!==''): ?><div class="banner <?php echo mritr_h($messageClass); ?>"><?php echo mritr_h($message); ?></div><?php endif; ?>
</div>

<div class="card">
<h2>Preflight</h2>
<table><thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody>
<?php foreach($pre['result']['checks'] as $c): ?>
<tr><td><?php echo mritr_h($c['label']); ?></td><td class="<?php echo $c['status']==='PASS'?'pass':'fail'; ?>"><?php echo mritr_h($c['status']); ?></td><td><?php echo mritr_h($c['detail']); ?></td></tr>
<?php endforeach; ?>
</tbody></table>

<h3>PRECHECK JSON</h3>
<textarea id="preJson" class="reportbox" readonly><?php echo mritr_h((string)$preJson); ?></textarea>
<div class="report-actions">
<button class="btn blue" type="button" onclick="copyReport('preJson',this)">Copy PRECHECK JSON</button>
<a class="btn blue" href="?download=pre" style="text-decoration:none;display:inline-block">Download PRECHECK JSON</a>
</div>
</div>

<?php if(is_array($post)): ?>
<div class="card">
<h2>Postflight</h2>
<table><thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody>
<?php foreach($post['result']['checks'] as $c): ?>
<tr><td><?php echo mritr_h($c['label']); ?></td><td class="<?php echo $c['status']==='PASS'?'pass':'fail'; ?>"><?php echo mritr_h($c['status']); ?></td><td><?php echo mritr_h($c['detail']); ?></td></tr>
<?php endforeach; ?>
</tbody></table>

<h3>POSTCHECK JSON</h3>
<textarea id="postJson" class="reportbox" readonly><?php echo mritr_h((string)$postJson); ?></textarea>
<div class="report-actions">
<button class="btn blue" type="button" onclick="copyReport('postJson',this)">Copy POSTCHECK JSON</button>
<a class="btn blue" href="?download=post" style="text-decoration:none;display:inline-block">Download POSTCHECK JSON</a>
</div>
</div>
<?php endif; ?>

<div class="card">
<h2>Actions</h2>
<form method="post" style="display:inline">
<input type="hidden" name="csrf" value="<?php echo mritr_h((string)$_SESSION['mritr_csrf']); ?>">
<input type="hidden" name="action" value="apply">
<button class="btn green" type="submit" <?php echo !empty($pre['result']['ok'])?'':'disabled'; ?>>Apply v003</button>
</form>
<form method="post" style="display:inline" onsubmit="return confirm('Rollback the reset utility to the backup from this installer session?');">
<input type="hidden" name="csrf" value="<?php echo mritr_h((string)$_SESSION['mritr_csrf']); ?>">
<input type="hidden" name="action" value="rollback">
<button class="btn red" type="submit">Rollback</button>
</form>
<a class="btn blue" href="/admin_mrl_test_team_reset.php" target="_blank" rel="noopener" style="text-decoration:none;display:inline-block">Open Reset Utility</a>
</div>
</div>
<script>
function copyReport(id, btn){
    var el=document.getElementById(id);
    if(!el)return;
    var text=el.value;
    function done(){
        var old=btn.textContent;
        btn.textContent='Copied!';
        setTimeout(function(){btn.textContent=old;},1200);
    }
    if(navigator.clipboard && window.isSecureContext){
        navigator.clipboard.writeText(text).then(done).catch(function(){
            el.focus();el.select();document.execCommand('copy');done();
        });
    }else{
        el.focus();el.select();document.execCommand('copy');done();
    }
}
</script>
</body></html>
