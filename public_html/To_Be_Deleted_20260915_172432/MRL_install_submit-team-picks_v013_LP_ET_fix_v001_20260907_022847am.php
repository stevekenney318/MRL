<?php
declare(strict_types=1);

/**
 * MRL_install_submit-team-picks_v013_LP_ET_fix.php
 *
 * VERSION: v001
 * LAST MODIFIED: 9/7/2026 2:28:47 am
 *
 * PURPOSE:
 * Production-safe installer for submit-team-picks.php v012 -> v013.
 *
 * FIX:
 * - Corrects Late Pick deadline detection when config_mrl.php supplies a
 *   human-readable deadline ending in the display suffix "ET".
 * - Does NOT change config_mrl.php or any displayed ET labels.
 * - Does NOT submit picks, send email, run cron/scheduler jobs, or alter DB data.
 *
 * TARGET:
 * - /public_html/submit-team-picks.php
 * - Expected current target version: v012
 * - Installed target version: v013
 *
 * INSTALLER SAFETY:
 * - Exact baseline/version/signature preflight.
 * - Exact one-function patch only.
 * - Exact backup before production replacement.
 * - PHP syntax validation of patched temporary file before replacement.
 * - Postflight version/signature checks.
 * - Automatic rollback if a critical postflight check fails.
 * - Manual Rollback button restores the exact installer backup.
 *
 * DELETE THIS INSTALLER FROM THE SERVER AFTER SUCCESSFUL TESTING.
 */

date_default_timezone_set('America/New_York');

const MRL_INSTALLER_VERSION = 'v001';
const MRL_INSTALL_KEY = 'lp013-b25cf537db91';
const MRL_TARGET_FROM_VERSION = 'v012';
const MRL_TARGET_TO_VERSION = 'v013';

$target = __DIR__ . '/submit-team-picks.php';
$backupDir = __DIR__ . '/_mrl_installer_backups';
$manifestPath = $backupDir . '/submit-team-picks_v013_lp_et_fix_manifest.json';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function yesno(bool $ok): string
{
    return $ok ? 'PASS' : 'FAIL';
}

function status_class(bool $ok): string
{
    return $ok ? 'pass' : 'fail';
}

function read_text(string $path): string
{
    $data = @file_get_contents($path);
    return $data === false ? '' : $data;
}

function write_text(string $path, string $data): bool
{
    $bytes = @file_put_contents($path, $data, LOCK_EX);
    return $bytes !== false && $bytes === strlen($data);
}

function php_lint(string $path): array
{
    $binary = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($binary) . ' -l ' . escapeshellarg($path) . ' 2>&1';

    $output = [];
    $code = 999;

    if (function_exists('exec')) {
        @exec($cmd, $output, $code);
        return [
            'available' => true,
            'ok' => ($code === 0),
            'output' => trim(implode("\n", $output)),
        ];
    }

    return [
        'available' => false,
        'ok' => false,
        'output' => 'PHP exec() is unavailable; syntax lint could not be run.',
    ];
}

function exact_count(string $haystack, string $needle): int
{
    if ($needle === '') return 0;
    return substr_count($haystack, $needle);
}

function parse_et_deadline_for_test(string $value): ?DateTimeImmutable
{
    $raw = trim($value);
    if ($raw === '') return null;

    $raw = preg_replace('/\s+ET$/i', '', $raw);
    if (!is_string($raw) || trim($raw) === '') return null;

    try {
        return new DateTimeImmutable(
            trim($raw),
            new DateTimeZone('America/New_York')
        );
    } catch (Throwable $e) {
        return null;
    }
}

function installer_preflight(string $target, string $backupDir): array
{
    $checks = [];

    $exists = is_file($target);
    $checks[] = ['Target file exists', $exists, $target];

    $readable = $exists && is_readable($target);
    $checks[] = ['Target file is readable', $readable, ''];

    $writable = $exists && is_writable($target);
    $checks[] = ['Target file is writable', $writable, ''];

    $dirWritable = is_writable(dirname($target));
    $checks[] = ['Target directory is writable', $dirWritable, dirname($target)];

    $content = $readable ? read_text($target) : '';

    $headerVersion = exact_count($content, ' * VERSION: v012') === 1;
    $checks[] = ['Expected target header VERSION v012', $headerVersion, ''];

    $scriptVersion = exact_count($content, "\$scriptVersion = 'v012';") === 1;
    $checks[] = ['Expected internal $scriptVersion v012', $scriptVersion, ''];

    $oldFunction = <<<'PHP'
function mrl_original_pick_deadline_passed(string $formLockDate, string $formLockTime): bool
{
    $raw = trim($formLockDate . ' ' . $formLockTime);
    if ($raw === '') {
        return false;
    }

    $deadlineTs = strtotime($raw);
    if ($deadlineTs === false) {
        return false;
    }

    return time() >= $deadlineTs;
}
PHP;

    $oldFunctionCount = exact_count($content, $oldFunction);
    $checks[] = [
        'Exact v012 deadline function found once',
        $oldFunctionCount === 1,
        'Found: ' . $oldFunctionCount
    ];

    $callSignature = exact_count(
        $content,
        'if (mrl_original_pick_deadline_passed($formLockDate, $formLockTime)) {'
    ) === 1;
    $checks[] = ['LP decision still calls deadline helper once', $callSignature, ''];

    $newMarkerAbsent = strpos($content, 'DateTimeZone(\'America/New_York\')') === false
        || strpos($content, 'function mrl_original_pick_deadline_passed') === false
        || strpos($content, "VERSION: v013") === false;
    $checks[] = ['Target is not already the installed v013 patch', $newMarkerAbsent, ''];

    $backupReady = is_dir($backupDir) ? is_writable($backupDir) : is_writable(dirname($backupDir));
    $checks[] = ['Backup location can be created/written', $backupReady, $backupDir];

    $parsed = parse_et_deadline_for_test('9/6/2026 5:00 pm ET');
    $parseOk = $parsed instanceof DateTimeImmutable
        && $parsed->format('Y-m-d H:i:s P') === '2026-09-06 17:00:00 -04:00';
    $checks[] = [
        'Replacement parser handles "9/6/2026 5:00 pm ET"',
        $parseOk,
        $parsed instanceof DateTimeImmutable ? $parsed->format('Y-m-d H:i:s P') : 'Parse failed'
    ];

    $all = true;
    foreach ($checks as $check) {
        if (!$check[1]) {
            $all = false;
            break;
        }
    }

    return [
        'all' => $all,
        'checks' => $checks,
        'content' => $content,
        'sha256' => $content !== '' ? hash('sha256', $content) : '',
    ];
}

function build_v013(string $content): array
{
    $oldFunction = <<<'PHP'
function mrl_original_pick_deadline_passed(string $formLockDate, string $formLockTime): bool
{
    $raw = trim($formLockDate . ' ' . $formLockTime);
    if ($raw === '') {
        return false;
    }

    $deadlineTs = strtotime($raw);
    if ($deadlineTs === false) {
        return false;
    }

    return time() >= $deadlineTs;
}
PHP;

    $newFunction = <<<'PHP'
function mrl_original_pick_deadline_passed(string $formLockDate, string $formLockTime): bool
{
    $raw = trim($formLockDate . ' ' . $formLockTime);
    if ($raw === '') {
        return false;
    }

    // config_mrl.php intentionally exposes human-readable ET display strings.
    // "ET" is a display label, not a PHP timezone identifier, so normalize the
    // suffix and parse explicitly in the MRL canonical timezone.
    $normalized = preg_replace('/\s+ET$/i', '', $raw);
    if (!is_string($normalized) || trim($normalized) === '') {
        return false;
    }

    try {
        $deadline = new DateTimeImmutable(
            trim($normalized),
            new DateTimeZone('America/New_York')
        );
        $now = new DateTimeImmutable(
            'now',
            new DateTimeZone('America/New_York')
        );

        return $now >= $deadline;
    } catch (Throwable $e) {
        return false;
    }
}
PHP;

    if (exact_count($content, $oldFunction) !== 1) {
        return ['ok' => false, 'error' => 'Exact old deadline function was not found once.', 'content' => ''];
    }

    $changelogNeedle = " * CHANGELOG:\n *\n * v012 (8/30/2026)";
    $changelogReplacement = <<<'TXT'
 * CHANGELOG:
 *
 * v013 (9/7/2026 2:28:47 am)
 * - FIX: Late Pick deadline detection now accepts config_mrl.php display deadlines ending in "ET".
 * - FIX: Parses the deadline explicitly in America/New_York instead of passing the display suffix to strtotime().
 * - FIX: New LP submissions after the original deadline no longer fall through to the closed normal-window rejection because of an unparseable display suffix.
 * - PRESERVE: No change to config_mrl.php, displayed ET labels, SEG/ADJ/RD behavior, LP effective-race calculation, database/history writes, or Team-page review/quiet-submit behavior.
 *
 * v012 (8/30/2026)
TXT;
    $changelogReplacement = str_replace('9/7/2026 2:28:47 am', '__LAST_MODIFIED_REAL__', $changelogReplacement);

    $replacements = [
        [' * VERSION: v012', ' * VERSION: v013', 1],
        [' * LAST MODIFIED: 8/30/2026', ' * LAST MODIFIED: __LAST_MODIFIED_REAL__', 1],
        [$changelogNeedle, $changelogReplacement, 1],
        ["\$scriptVersion = 'v012';", "\$scriptVersion = 'v013';", 1],
        [$oldFunction, $newFunction, 1],
    ];

    $updated = $content;

    foreach ($replacements as [$from, $to, $expected]) {
        $count = exact_count($updated, $from);
        if ($count !== $expected) {
            return [
                'ok' => false,
                'error' => 'Patch signature mismatch for: ' . substr($from, 0, 80) . ' (found ' . $count . ')',
                'content' => '',
            ];
        }
        $updated = str_replace($from, $to, $updated);
    }

    $updated = str_replace('__LAST_MODIFIED_REAL__', '9/7/2026 2:28:47 am', $updated);

    return ['ok' => true, 'error' => '', 'content' => $updated];
}

function save_manifest(string $manifestPath, array $manifest): bool
{
    $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return is_string($json) && write_text($manifestPath, $json . "\n");
}

function load_manifest(string $manifestPath): ?array
{
    if (!is_file($manifestPath)) return null;
    $data = json_decode(read_text($manifestPath), true);
    return is_array($data) ? $data : null;
}

function restore_backup(string $target, string $backupPath, string $expectedSha = ''): array
{
    if (!is_file($backupPath) || !is_readable($backupPath)) {
        return ['ok' => false, 'message' => 'Backup file is missing or unreadable.'];
    }

    $backup = read_text($backupPath);
    if ($backup === '') {
        return ['ok' => false, 'message' => 'Backup file is empty or unreadable.'];
    }

    if ($expectedSha !== '' && hash('sha256', $backup) !== $expectedSha) {
        return ['ok' => false, 'message' => 'Backup SHA-256 does not match the manifest.'];
    }

    $temp = dirname($target) . '/.' . basename($target) . '.rollback.' . bin2hex(random_bytes(4)) . '.tmp';
    if (!write_text($temp, $backup)) {
        return ['ok' => false, 'message' => 'Unable to write rollback temporary file.'];
    }

    $lint = php_lint($temp);
    if ($lint['available'] && !$lint['ok']) {
        @unlink($temp);
        return ['ok' => false, 'message' => 'Rollback copy failed PHP syntax validation: ' . $lint['output']];
    }

    if (!@rename($temp, $target)) {
        @unlink($temp);
        return ['ok' => false, 'message' => 'Unable to atomically restore the backup.'];
    }

    $restored = read_text($target);
    $ok = hash('sha256', $restored) === hash('sha256', $backup);

    return [
        'ok' => $ok,
        'message' => $ok ? 'Exact backup restored successfully.' : 'Rollback write completed but SHA-256 verification failed.',
    ];
}

$key = isset($_GET['key']) ? (string)$_GET['key'] : '';
$authorized = hash_equals(MRL_INSTALL_KEY, $key);

$message = '';
$messageClass = 'info';
$applyDetails = [];
$rollbackDetails = [];

$preflight = installer_preflight($target, $backupDir);
$manifest = load_manifest($manifestPath);

if ($authorized && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';

    if ($action === 'apply') {
        $preflight = installer_preflight($target, $backupDir);

        if (!$preflight['all']) {
            $message = 'Apply blocked: one or more required preflight checks failed.';
            $messageClass = 'fail';
        } else {
            if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                $message = 'Apply blocked: backup directory could not be created.';
                $messageClass = 'fail';
            } else {
                $original = $preflight['content'];
                $patch = build_v013($original);

                if (!$patch['ok']) {
                    $message = 'Apply blocked: ' . $patch['error'];
                    $messageClass = 'fail';
                } else {
                    $patched = $patch['content'];
                    $backupName = 'submit-team-picks.php.v012.' . date('Ymd_His') . '.bak';
                    $backupPath = $backupDir . '/' . $backupName;

                    if (is_file($backupPath)) {
                        $message = 'Apply blocked: intended backup filename already exists.';
                        $messageClass = 'fail';
                    } elseif (!write_text($backupPath, $original)) {
                        $message = 'Apply blocked: exact production backup could not be written.';
                        $messageClass = 'fail';
                    } else {
                        $originalPerms = @fileperms($target);
                        $temp = dirname($target) . '/.' . basename($target) . '.v013.' . bin2hex(random_bytes(4)) . '.tmp';

                        if (!write_text($temp, $patched)) {
                            $message = 'Apply failed: patched temporary file could not be written. Production target was not changed.';
                            $messageClass = 'fail';
                        } else {
                            $lint = php_lint($temp);
                            $applyDetails[] = ['Temporary v013 PHP syntax check', $lint['available'] ? $lint['ok'] : true, $lint['output']];

                            if ($lint['available'] && !$lint['ok']) {
                                @unlink($temp);
                                $message = 'Apply blocked: patched v013 failed PHP syntax validation. Production target was not changed.';
                                $messageClass = 'fail';
                            } elseif (!@rename($temp, $target)) {
                                @unlink($temp);
                                $message = 'Apply failed: atomic production replacement could not be completed. Original target should still be intact.';
                                $messageClass = 'fail';
                            } else {
                                if (is_int($originalPerms)) {
                                    @chmod($target, $originalPerms & 0777);
                                }

                                $installed = read_text($target);
                                $installedSha = $installed !== '' ? hash('sha256', $installed) : '';

                                $checks = [
                                    ['Target header now VERSION v013', exact_count($installed, ' * VERSION: v013') === 1, ''],
                                    ['Internal $scriptVersion now v013', exact_count($installed, "\$scriptVersion = 'v013';") === 1, ''],
                                    ['Old strtotime deadline implementation removed', strpos($installed, '$deadlineTs = strtotime($raw);') === false, ''],
                                    ['Explicit America/New_York deadline parser installed', strpos($installed, "new DateTimeZone('America/New_York')") !== false, ''],
                                    ['ET normalization installed', strpos($installed, "preg_replace('/\\s+ET$/i', '', \$raw)") !== false, ''],
                                    ['LP decision call preserved', exact_count($installed, 'if (mrl_original_pick_deadline_passed($formLockDate, $formLockTime)) {') === 1, ''],
                                ];

                                $targetLint = php_lint($target);
                                $checks[] = [
                                    'Installed target PHP syntax check',
                                    $targetLint['available'] ? $targetLint['ok'] : true,
                                    $targetLint['output']
                                ];

                                $postOk = true;
                                foreach ($checks as $check) {
                                    $applyDetails[] = $check;
                                    if (!$check[1]) $postOk = false;
                                }

                                if ($postOk) {
                                    $manifestData = [
                                        'installer_version' => MRL_INSTALLER_VERSION,
                                        'installed_at_et' => date('c'),
                                        'target' => $target,
                                        'backup' => $backupPath,
                                        'original_sha256' => hash('sha256', $original),
                                        'installed_sha256' => $installedSha,
                                        'from_version' => MRL_TARGET_FROM_VERSION,
                                        'to_version' => MRL_TARGET_TO_VERSION,
                                    ];
                                    save_manifest($manifestPath, $manifestData);
                                    $manifest = $manifestData;

                                    $message = 'SUCCESS: submit-team-picks.php v013 installed. No database records, picks, email, cron, scheduler, or config files were touched.';
                                    $messageClass = 'pass';
                                } else {
                                    $rollback = restore_backup($target, $backupPath, hash('sha256', $original));
                                    $rollbackDetails[] = ['Automatic rollback after failed postflight', $rollback['ok'], $rollback['message']];
                                    $message = $rollback['ok']
                                        ? 'POSTFLIGHT FAILED: v013 was automatically rolled back to the exact v012 backup.'
                                        : 'CRITICAL: postflight failed and automatic rollback also failed. Inspect the target immediately.';
                                    $messageClass = 'fail';
                                }
                            }
                        }
                    }
                }
            }
        }
    } elseif ($action === 'rollback') {
        $manifest = load_manifest($manifestPath);

        if (!is_array($manifest)) {
            $message = 'Rollback blocked: installer manifest was not found.';
            $messageClass = 'fail';
        } else {
            $backupPath = (string)($manifest['backup'] ?? '');
            $expectedSha = (string)($manifest['original_sha256'] ?? '');
            $result = restore_backup($target, $backupPath, $expectedSha);

            $rollbackDetails[] = ['Restore exact installer backup', $result['ok'], $result['message']];
            $message = $result['ok']
                ? 'ROLLBACK SUCCESS: exact pre-install submit-team-picks.php restored.'
                : 'ROLLBACK FAILED: ' . $result['message'];
            $messageClass = $result['ok'] ? 'pass' : 'fail';

            $preflight = installer_preflight($target, $backupDir);
        }
    }
}

$current = read_text($target);
$currentVersion = 'Unknown';
if (preg_match('/\*\s+VERSION:\s+(v\d{3})/', $current, $m)) {
    $currentVersion = $m[1];
}

$currentSha = $current !== '' ? hash('sha256', $current) : '';
$manifest = load_manifest($manifestPath);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRL Installer — submit-team-picks.php v013 LP ET Fix</title>
<style>
:root{
    color-scheme:dark;
    --bg:#111514;
    --panel:#1b201f;
    --border:#46504d;
    --text:#ece9df;
    --muted:#b9b8b2;
    --gold:#f1c97f;
    --green:#188a4b;
    --green2:#0f6236;
    --blue:#2375b8;
    --red:#a73434;
}
*{box-sizing:border-box}
body{
    margin:0;
    background:var(--bg);
    color:var(--text);
    font-family:Tahoma,Verdana,Segoe UI,sans-serif;
}
.wrap{width:min(1180px,94%);margin:28px auto 48px}
h1,h2{color:var(--gold);margin-top:0}
.panel{
    background:var(--panel);
    border:1px solid var(--border);
    border-radius:14px;
    padding:20px;
    margin:0 0 18px;
}
.notice{
    padding:14px 16px;
    border-radius:10px;
    margin:0 0 18px;
    font-weight:700;
}
.notice.pass{background:#103b27;border:1px solid #2fa267}
.notice.fail{background:#481c1c;border:1px solid #c65353}
.notice.info{background:#172c3b;border:1px solid #3e83b5}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 12px;border-bottom:1px solid #343c39;text-align:left;vertical-align:top}
th{color:var(--gold)}
.badge{display:inline-block;min-width:58px;text-align:center;padding:3px 8px;border-radius:999px;font-weight:800}
.badge.pass{background:#12492e;color:#9ff0bd}
.badge.fail{background:#542020;color:#ffb0b0}
code{color:#bfe7ff}
.actions{display:flex;gap:12px;flex-wrap:wrap}
button,a.button{
    border:0;border-radius:9px;padding:11px 17px;color:#fff;font-weight:800;
    text-decoration:none;cursor:pointer;font-size:15px
}
.apply{background:var(--green)}
.infoBtn{background:var(--blue)}
.rollback{background:var(--red)}
button:disabled{opacity:.45;cursor:not-allowed}
.small{font-size:13px;color:var(--muted)}
.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}
ul{line-height:1.55}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Installer — submit-team-picks.php v013 LP ET Fix</h1>

    <?php if (!$authorized): ?>
        <div class="notice fail">
            Installer key is missing or incorrect.
        </div>
        <div class="panel">
            <p>Open this installer using the private URL/key supplied with the package.</p>
        </div>
    <?php else: ?>

        <?php if ($message !== ''): ?>
            <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
        <?php endif; ?>

        <div class="panel">
            <h2>What this changes</h2>
            <ul>
                <li>Changes only <code>/public_html/submit-team-picks.php</code>.</li>
                <li>Updates target <strong>v012 → v013</strong>.</li>
                <li>Fixes LP deadline detection for display values such as <code>9/6/2026 5:00 pm ET</code>.</li>
                <li>Keeps the visible <code>ET</code> suffix everywhere else. <strong>config_mrl.php is not changed.</strong></li>
                <li>Does not submit picks, touch the database, send email, run cron, run scheduler tasks, or change any JSON/configuration.</li>
            </ul>
        </div>

        <div class="panel">
            <h2>Current target</h2>
            <table>
                <tr><th>File</th><td class="mono"><?php echo h($target); ?></td></tr>
                <tr><th>Detected version</th><td><?php echo h($currentVersion); ?></td></tr>
                <tr><th>SHA-256</th><td class="mono"><?php echo h($currentSha); ?></td></tr>
            </table>
        </div>

        <div class="panel">
            <h2>Preflight</h2>
            <table>
                <tr><th>Check</th><th>Status</th><th>Detail</th></tr>
                <?php foreach ($preflight['checks'] as $check): ?>
                    <tr>
                        <td><?php echo h((string)$check[0]); ?></td>
                        <td><span class="badge <?php echo status_class((bool)$check[1]); ?>"><?php echo yesno((bool)$check[1]); ?></span></td>
                        <td class="mono small"><?php echo h((string)$check[2]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php if (!empty($applyDetails)): ?>
        <div class="panel">
            <h2>Apply / Postflight</h2>
            <table>
                <tr><th>Check</th><th>Status</th><th>Detail</th></tr>
                <?php foreach ($applyDetails as $check): ?>
                    <tr>
                        <td><?php echo h((string)$check[0]); ?></td>
                        <td><span class="badge <?php echo status_class((bool)$check[1]); ?>"><?php echo yesno((bool)$check[1]); ?></span></td>
                        <td class="mono small"><?php echo h((string)$check[2]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($rollbackDetails)): ?>
        <div class="panel">
            <h2>Rollback result</h2>
            <table>
                <tr><th>Check</th><th>Status</th><th>Detail</th></tr>
                <?php foreach ($rollbackDetails as $check): ?>
                    <tr>
                        <td><?php echo h((string)$check[0]); ?></td>
                        <td><span class="badge <?php echo status_class((bool)$check[1]); ?>"><?php echo yesno((bool)$check[1]); ?></span></td>
                        <td class="mono small"><?php echo h((string)$check[2]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <div class="panel">
            <h2>Actions</h2>
            <div class="actions">
                <form method="post" action="?key=<?php echo rawurlencode(MRL_INSTALL_KEY); ?>">
                    <input type="hidden" name="action" value="apply">
                    <button class="apply" type="submit" <?php echo $preflight['all'] ? '' : 'disabled'; ?>>
                        Apply v013 Fix
                    </button>
                </form>

                <a class="button infoBtn" href="/team.php" target="_blank" rel="noopener">Open Team Page</a>

                <?php if (is_array($manifest) && is_file((string)($manifest['backup'] ?? ''))): ?>
                    <form method="post" action="?key=<?php echo rawurlencode(MRL_INSTALL_KEY); ?>"
                          onsubmit="return confirm('Restore the exact pre-install submit-team-picks.php backup?');">
                        <input type="hidden" name="action" value="rollback">
                        <button class="rollback" type="submit">Rollback</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (is_array($manifest)): ?>
                <p class="small mono" style="margin-top:14px;">
                    Backup: <?php echo h((string)($manifest['backup'] ?? '')); ?><br>
                    Original SHA-256: <?php echo h((string)($manifest['original_sha256'] ?? '')); ?><br>
                    Installed SHA-256: <?php echo h((string)($manifest['installed_sha256'] ?? '')); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="panel small">
            <strong>Test after Apply:</strong>
            use Team 999, submit four LP picks, click Confirm Submission, then verify one live
            <code>user_picks</code> row and one <code>user_picks_history</code> row with
            <code>pick_type = LP</code> and <code>effective_race = 28</code>.
            Then check <code>/admin_pick_adjustment.php</code> for the current LP.
            <br><br>
            Delete this installer from the server after successful testing.
        </div>
    <?php endif; ?>
</div>
</body>
</html>
