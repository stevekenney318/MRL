<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * team.php League Information Default-Path Cleanup
 *
 * INSTALLER VERSION: v001
 * CREATED: 9/16/2026 1:50:00 am EDT
 *
 * TARGET:
 *   team.php v052 -> v053
 *
 * PURPOSE:
 * Keep the built-in/fallback League Information defaults in team.php aligned
 * with the now-active editable Team Page Content configuration after the
 * /league_info/ relocation.
 *
 * CHANGES:
 *   /{year}_Fees.php  -> /league_info/{year}_Fees.php
 *   /{year}_Rules.php -> /league_info/{year}_Rules.php
 *
 * SAFETY:
 * - Exact occurrence-count preflight.
 * - Requires team.php to identify as VERSION: v052.
 * - Makes a full backup before writing.
 * - Writes through a temporary file + atomic rename.
 * - Postflight verifies only the intended defaults and version changed.
 * - Rollback restores exact backup.
 * - No database writes.
 * - Does not touch mrl_team_page_content.json.
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v001';
const TARGET_OLD_VERSION = 'v052';
const TARGET_NEW_VERSION = 'v053';
const BACKUP_DIR = '_mrl_installer_backups/team_league_info_defaults_20260916_015000';
const BACKUP_FILE = BACKUP_DIR . '/team.php.v052.backup';
const STATE_FILE = BACKUP_DIR . '/_state.json';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) {
    $root = __DIR__;
}
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$teamPath = $root . DIRECTORY_SEPARATOR . 'team.php';
$backupPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, BACKUP_FILE);
$statePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, STATE_FILE);

$oldFees = "'url' => '/{year}_Fees.php'";
$newFees = "'url' => '/league_info/{year}_Fees.php'";
$oldRules = "'url' => '/{year}_Rules.php'";
$newRules = "'url' => '/league_info/{year}_Rules.php'";

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function ensure_dir(string $dir): bool {
    return is_dir($dir) || @mkdir($dir, 0755, true) || is_dir($dir);
}

function atomic_write(string $path, string $content): bool {
    if (!ensure_dir(dirname($path))) return false;
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) return false;
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function load_state(string $statePath): ?array {
    if (!is_file($statePath)) return null;
    $raw = @file_get_contents($statePath);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function save_state(string $statePath, array $state): bool {
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return $json !== false && atomic_write($statePath, $json . "\n");
}

function preflight(string $teamPath, string $statePath, string $oldFees, string $newFees, string $oldRules, string $newRules): array {
    $rows = [];
    $ok = true;

    if (!is_file($teamPath)) {
        return [
            'ok' => false,
            'rows' => [[
                'check' => 'team.php present',
                'status' => 'FAIL',
                'detail' => 'team.php was not found in production root.'
            ]]
        ];
    }

    $content = @file_get_contents($teamPath);
    if ($content === false) {
        return [
            'ok' => false,
            'rows' => [[
                'check' => 'team.php readable',
                'status' => 'FAIL',
                'detail' => 'team.php could not be read.'
            ]]
        ];
    }

    $versionCount = preg_match_all('/VERSION\s*:\s*v052\b/i', $content, $m);
    $feesOldCount = substr_count($content, $oldFees);
    $rulesOldCount = substr_count($content, $oldRules);
    $feesNewCount = substr_count($content, $newFees);
    $rulesNewCount = substr_count($content, $newRules);

    $rows[] = [
        'check' => 'Production root',
        'status' => 'PASS',
        'detail' => dirname($teamPath)
    ];

    $versionStatus = $versionCount >= 1 ? 'PASS' : 'FAIL';
    if ($versionStatus === 'FAIL') $ok = false;
    $rows[] = [
        'check' => 'team.php version',
        'status' => $versionStatus,
        'detail' => $versionCount >= 1
            ? 'Found VERSION: v052 marker(s); target is v053.'
            : 'Expected VERSION: v052 marker was not found.'
    ];

    $feesStatus = ($feesOldCount === 1 && $feesNewCount === 0) ? 'PASS' : 'FAIL';
    if ($feesStatus === 'FAIL') $ok = false;
    $rows[] = [
        'check' => 'Fees fallback default',
        'status' => $feesStatus,
        'detail' => "Old path occurrences: {$feesOldCount}; new path occurrences: {$feesNewCount}."
    ];

    $rulesStatus = ($rulesOldCount === 1 && $rulesNewCount === 0) ? 'PASS' : 'FAIL';
    if ($rulesStatus === 'FAIL') $ok = false;
    $rows[] = [
        'check' => 'Rules fallback default',
        'status' => $rulesStatus,
        'detail' => "Old path occurrences: {$rulesOldCount}; new path occurrences: {$rulesNewCount}."
    ];

    $state = load_state($statePath);
    $rows[] = [
        'check' => 'Prior installer state',
        'status' => $state ? 'INFO' : 'PASS',
        'detail' => $state ? 'A prior successful install state exists; use Rollback rather than reinstalling.' : 'No prior install state exists.'
    ];

    if ($state) $ok = false;

    return ['ok' => $ok, 'rows' => $rows];
}

function do_install(string $teamPath, string $backupPath, string $statePath, string $oldFees, string $newFees, string $oldRules, string $newRules): array {
    $pre = preflight($teamPath, $statePath, $oldFees, $newFees, $oldRules, $newRules);
    if (!$pre['ok']) {
        return ['ok' => false, 'message' => 'Install blocked because preflight is not fully green.'];
    }

    $original = @file_get_contents($teamPath);
    if ($original === false) {
        return ['ok' => false, 'message' => 'Could not read team.php.'];
    }

    if (!ensure_dir(dirname($backupPath)) || @file_put_contents($backupPath, $original, LOCK_EX) === false) {
        return ['ok' => false, 'message' => 'Could not create team.php backup.'];
    }

    $updated = $original;

    $updated = str_replace($oldFees, $newFees, $updated, $feesCount);
    $updated = str_replace($oldRules, $newRules, $updated, $rulesCount);

    // Update every explicit VERSION: v052 marker only. This deliberately does
    // not perform a blind global v052 -> v053 replacement.
    $updated = preg_replace('/VERSION(\s*:\s*)v052\b/i', 'VERSION$1v053', $updated, -1, $versionCount);

    if ($feesCount !== 1 || $rulesCount !== 1 || $versionCount < 1) {
        @copy($backupPath, $teamPath);
        return [
            'ok' => false,
            'message' => 'Unexpected replacement count; original team.php was restored from backup.'
        ];
    }

    if (!atomic_write($teamPath, $updated)) {
        @copy($backupPath, $teamPath);
        return [
            'ok' => false,
            'message' => 'Atomic write failed; original team.php was restored from backup.'
        ];
    }

    $verify = @file_get_contents($teamPath);
    if (
        $verify === false ||
        substr_count($verify, $newFees) !== 1 ||
        substr_count($verify, $newRules) !== 1 ||
        substr_count($verify, $oldFees) !== 0 ||
        substr_count($verify, $oldRules) !== 0 ||
        preg_match('/VERSION\s*:\s*v053\b/i', $verify) !== 1
    ) {
        @copy($backupPath, $teamPath);
        return [
            'ok' => false,
            'message' => 'Post-write verification failed; original team.php was restored from backup.'
        ];
    }

    $state = [
        'installed_at' => date(DATE_ATOM),
        'installer_version' => INSTALLER_VERSION,
        'target_file' => 'team.php',
        'from_version' => TARGET_OLD_VERSION,
        'to_version' => TARGET_NEW_VERSION,
        'changes' => [
            $oldFees . ' -> ' . $newFees,
            $oldRules . ' -> ' . $newRules,
        ],
        'backup' => BACKUP_FILE
    ];

    if (!save_state($statePath, $state)) {
        @copy($backupPath, $teamPath);
        return [
            'ok' => false,
            'message' => 'State-file write failed; original team.php was restored from backup.'
        ];
    }

    return [
        'ok' => true,
        'message' => 'team.php fallback defaults aligned with /league_info/ and version advanced to v053.'
    ];
}

function do_rollback(string $teamPath, string $backupPath, string $statePath): array {
    if (!is_file($backupPath)) {
        return ['ok' => false, 'message' => 'Backup file is missing; rollback cannot proceed.'];
    }

    $backup = @file_get_contents($backupPath);
    if ($backup === false) {
        return ['ok' => false, 'message' => 'Backup file could not be read.'];
    }

    if (!atomic_write($teamPath, $backup)) {
        return ['ok' => false, 'message' => 'Rollback write failed.'];
    }

    @unlink($statePath);

    return [
        'ok' => true,
        'message' => 'Rollback completed; original team.php v052 restored.'
    ];
}

$action = (string)($_POST['action'] ?? '');
$result = null;

if ($action === 'install') {
    if (($_POST['confirm_install'] ?? '') !== 'yes') {
        $result = ['ok' => false, 'message' => 'Install not started: confirmation box was not checked.'];
    } else {
        $result = do_install($teamPath, $backupPath, $statePath, $oldFees, $newFees, $oldRules, $newRules);
    }
} elseif ($action === 'rollback') {
    $result = do_rollback($teamPath, $backupPath, $statePath);
}

$state = load_state($statePath);
$pre = $state ? null : preflight($teamPath, $statePath, $oldFees, $newFees, $oldRules, $newRules);

$postRows = [];
if ($state && is_file($teamPath)) {
    $content = @file_get_contents($teamPath);
    if ($content !== false) {
        $checks = [
            ['Version', preg_match('/VERSION\s*:\s*v053\b/i', $content) >= 1, 'team.php identifies as v053.'],
            ['Fees fallback', substr_count($content, $newFees) === 1 && substr_count($content, $oldFees) === 0, '/league_info/{year}_Fees.php is the built-in default.'],
            ['Rules fallback', substr_count($content, $newRules) === 1 && substr_count($content, $oldRules) === 0, '/league_info/{year}_Rules.php is the built-in default.'],
        ];
        foreach ($checks as $c) {
            $postRows[] = [
                'check' => $c[0],
                'status' => $c[1] ? 'PASS' : 'FAIL',
                'detail' => $c[2]
            ];
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL team.php League Info Defaults</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.banner{padding:11px 13px;margin-bottom:11px;border:1px solid #3f8bc2;border-radius:10px;background:#15354d;color:#e8f5ff;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.card{padding:11px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:20px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
.status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
.pass{background:#17613a;border:1px solid #55db8b;color:#e8fff1}
.info{background:#4a3813;border:1px solid #d8aa49;color:#ffe6a7}
.fail{background:#5b2323;border:1px solid #e77a7a;color:#ffe4e4}
.actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
button{min-height:38px;padding:8px 14px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.install{background:#2f7f53}.rollback{background:#b46d22}.refresh{background:#2c6f9e}
button:disabled{background:#5a5f5d;color:#b9b9b9;cursor:not-allowed;opacity:.7}
.confirm{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid #5a6a62;border-radius:8px;background:#151a18}
.result-ok{border-color:#2f9a61;background:#103b27}.result-bad{border-color:#a65353;background:#3d1d1d}
code{color:#f8d89a}
@media(max-width:800px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body><div class="wrap">

<h1>MRL team.php League Info Defaults</h1>

<div class="banner">
Final cleanup for the <code>/league_info/</code> reorganization: align the two built-in/fallback
League Information links in <code>team.php</code> with the already-correct editable content configuration.
</div>

<div class="panel">
<h2>Plan</h2>
<div class="grid">
<div class="card"><span class="small">Target</span><span class="value">team.php</span></div>
<div class="card"><span class="small">Version</span><span class="value">v052 → v053</span></div>
<div class="card"><span class="small">Default URLs changed</span><span class="value">2</span></div>
</div>
</div>

<?php if ($result): ?>
<div class="panel <?php echo !empty($result['ok']) ? 'result-ok' : 'result-bad'; ?>">
<h2>Result</h2>
<p><strong><?php echo !empty($result['ok']) ? 'PASS' : 'ATTENTION'; ?></strong> — <?php echo h($result['message']); ?></p>
</div>
<?php endif; ?>

<div class="panel">
<h2><?php echo $state ? 'Postflight / Result' : 'Preflight / Result'; ?></h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php
$rows = $state ? $postRows : $pre['rows'];
foreach ($rows as $r):
    $cls = $r['status'] === 'PASS' ? 'pass' : ($r['status'] === 'FAIL' ? 'fail' : 'info');
?>
<tr>
<td><?php echo h($r['check']); ?></td>
<td><span class="status <?php echo h($cls); ?>"><?php echo h($r['status']); ?></span></td>
<td><?php echo h($r['detail']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Action</h2>
<?php if ($state): ?>
<p>The cleanup is installed. The editable JSON/content-manager configuration was not touched.</p>
<form method="post">
<input type="hidden" name="action" value="rollback">
<div class="actions">
<button class="rollback" type="submit">Rollback team.php Defaults</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Postflight</button>
</div>
</form>
<?php else: ?>
<form method="post">
<input type="hidden" name="action" value="install">
<label class="confirm">
<input type="checkbox" name="confirm_install" value="yes" id="confirmInstall">
I reviewed the green preflight and want to update only these two team.php fallback URLs and advance v052 to v053.
</label>
<div class="actions" style="margin-top:10px">
<button class="install" type="submit" id="installButton" disabled>Install Default-Path Cleanup</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Preflight</button>
</div>
<p class="small"><?php echo $pre['ok'] ? 'Preflight is green. Check the confirmation box to enable Install.' : 'Install is blocked because one or more preflight checks failed.'; ?></p>
</form>
<?php endif; ?>
</div>

<div class="panel small">
Backup: <code><?php echo h(BACKUP_FILE); ?></code><br>
No database writes. No JSON edits. No other team.php content is intentionally changed.
</div>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 1:50:00 am EDT
</div>

</div>
<script>
(function(){
'use strict';
var c=document.getElementById('confirmInstall');
var b=document.getElementById('installButton');
var ok=<?php echo (!$state && $pre && $pre['ok']) ? 'true' : 'false'; ?>;
function sync(){ if(b) b.disabled=!(ok && c && c.checked); }
if(c) c.addEventListener('change',sync);
sync();
}());
</script>
</body>
</html>
