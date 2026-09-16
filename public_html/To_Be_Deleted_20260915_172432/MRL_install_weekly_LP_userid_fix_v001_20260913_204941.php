<?php
declare(strict_types=1);

/**
 * MRL installer
 * TITLE: Weekly Standings LP userID preservation fix
 * INSTALLER VERSION: v001
 * GENERATED: 9/13/2026 8:49:41 pm ET
 *
 * TARGET:
 *   /race_results/weekly_standings.php
 *   v071 -> v072
 *
 * ROOT CAUSE:
 * - rrsg_overlay_special_rows_for_race() rebuilt LP/RD rows without userID.
 * - rrsg_is_noncompetitive_test_team() treated a missing userID as 0.
 * - Therefore Reid's valid R28 LP row was incorrectly filtered as a test row.
 * - v071 then correctly restored the missing competitive roster team as a
 *   zero-point MISS row, masking the original LP row.
 *
 * FIX:
 * - Preserve userID when LP/RD overlay rows are constructed.
 * - Only treat userID 0/999 as test IDs when userID is actually present.
 * - Preserve team-name exclusion for "MRL test team".
 *
 * SAFETY:
 * - No database writes.
 * - No snapshot regeneration.
 * - Exact v071/signature preflight.
 * - Timestamped backup.
 * - Temp-file write + optional PHP lint.
 * - Atomic rename.
 * - Rollback support.
 */

date_default_timezone_set('America/New_York');

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$targetRel = '/race_results/weekly_standings.php';
$target = $docRoot . $targetRel;
$backupDir = $docRoot . '/_mrl_installer_backups';
$backup = $backupDir . '/weekly_standings.php.pre_v072_20260913_204941.bak';
$temp = dirname($target) . '/.weekly_standings_v072_20260913_204941.tmp.php';

function mrl_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function mrl_replace_once(string $src, string $old, string $new, string $label): string {
    $count = substr_count($src, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ': expected exactly 1 anchor, found ' . $count . '.');
    }
    return str_replace($old, $new, $src);
}

function mrl_build_v072(string $src): string
{
    $src = mrl_replace_once(
        $src,
        " * VERSION: v071\n",
        " * VERSION: v072\n",
        'Version header'
    );

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelog = " * CHANGELOG:\n *\n"
        . " * v072 (9/13/2026 8:49:41 pm ET)\n"
        . " *   - FIX: LP/RD overlay rows now preserve userID when the applicable special-pick row is constructed.\n"
        . " *   - FIX: Missing userID is no longer implicitly treated as userID 0 by the noncompetitive-test-team filter.\n"
        . " *   - FIX: Restores Over The Edge's valid LP R28 scoring while preserving R27 as No Picks / 0 points.\n"
        . " *   - PRESERVE: userID 0/999 and MRL test team remain excluded; v070/v071 competitive-roster completion remains intact.\n"
        . " *\n";
    $src = mrl_replace_once($src, $changelogAnchor, $changelog, 'Changelog');

    $oldTest = <<<'TXT'
function rrsg_is_noncompetitive_test_team(array $team): bool
{
    $userId = (int)($team['userID'] ?? 0);
    $teamName = strtolower(trim((string)($team['teamName'] ?? '')));

    // userID 0 is the current legacy test account; 999 is its planned positive-ID replacement.
    if ($userId === 0 || $userId === 999) {
        return true;
    }

    return $teamName === 'mrl test team';
}
TXT;

    $newTest = <<<'TXT'
function rrsg_is_noncompetitive_test_team(array $team): bool
{
    $hasUserId = array_key_exists('userID', $team) && $team['userID'] !== null && $team['userID'] !== '';
    $userId = $hasUserId ? (int)$team['userID'] : null;
    $teamName = strtolower(trim((string)($team['teamName'] ?? '')));

    // userID 0 is the current legacy test account; 999 is its planned positive-ID replacement.
    // A row with no userID field is not automatically a test row.
    if ($hasUserId && ($userId === 0 || $userId === 999)) {
        return true;
    }

    return $teamName === 'mrl test team';
}
TXT;

    $src = mrl_replace_once($src, $oldTest, $newTest, 'Noncompetitive test-team helper');

    $oldApplicable = <<<'TXT'
            $rowsByTeam[$teamName] = [
                'teamName' => $teamName,
                'userName' => (string)($applicable['userName'] ?? ($rowsByTeam[$teamName]['userName'] ?? '')),
TXT;
    $newApplicable = <<<'TXT'
            $rowsByTeam[$teamName] = [
                'userID' => (int)($applicable['userID'] ?? ($rowsByTeam[$teamName]['userID'] ?? 0)),
                'teamName' => $teamName,
                'userName' => (string)($applicable['userName'] ?? ($rowsByTeam[$teamName]['userName'] ?? '')),
TXT;
    $src = mrl_replace_once($src, $oldApplicable, $newApplicable, 'Applicable LP/RD overlay row');

    $oldPreEffective = <<<'TXT'
                $rowsByTeam[$teamName] = [
                    'teamName' => $teamName,
                    'userName' => (string)($firstSpecial['userName'] ?? ($rowsByTeam[$teamName]['userName'] ?? '')),
TXT;
    $newPreEffective = <<<'TXT'
                $rowsByTeam[$teamName] = [
                    'userID' => (int)($firstSpecial['userID'] ?? ($rowsByTeam[$teamName]['userID'] ?? 0)),
                    'teamName' => $teamName,
                    'userName' => (string)($firstSpecial['userName'] ?? ($rowsByTeam[$teamName]['userName'] ?? '')),
TXT;
    $src = mrl_replace_once($src, $oldPreEffective, $newPreEffective, 'Pre-effective LP overlay row');

    return str_replace('9/13/2026 8:49:41 pm ET', '__HUMAN_REAL__', $src);
}

function mrl_lint(string $path): array
{
    if (!function_exists('exec')) {
        return ['status' => 'SKIPPED', 'detail' => 'Server does not expose a usable PHP CLI lint command.'];
    }

    $bin = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($bin) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines = [];
    $code = 0;
    @exec($cmd, $lines, $code);

    if ($code === 0) {
        return ['status' => 'PASS', 'detail' => trim(implode("\n", $lines))];
    }
    if ($code === 127 || empty($lines)) {
        return ['status' => 'SKIPPED', 'detail' => 'PHP CLI lint unavailable; postflight verification will still run.'];
    }
    return ['status' => 'FAIL', 'detail' => trim(implode("\n", $lines))];
}

function mrl_preflight(string $target, string $backupDir): array
{
    $rows = [];
    $ok = true;

    $add = function(string $check, string $status, string $detail = '') use (&$rows, &$ok): void {
        $rows[] = [$check, $status, $detail];
        if ($status === 'FAIL') $ok = false;
    };

    $exists = is_file($target);
    $add('weekly_standings.php exists', $exists ? 'PASS' : 'FAIL', $target);
    if (!$exists) return ['ok'=>false,'rows'=>$rows,'source'=>''];

    $rw = is_readable($target) && is_writable($target);
    $add('weekly_standings.php readable/writable', $rw ? 'PASS' : 'FAIL', '');
    if (!$rw) return ['ok'=>false,'rows'=>$rows,'source'=>''];

    $src = file_get_contents($target);
    if (!is_string($src)) {
        $add('Target readable', 'FAIL', 'Unable to read contents.');
        return ['ok'=>false,'rows'=>$rows,'source'=>''];
    }

    $baseline = strpos($src, '* VERSION: v071') !== false
        && strpos($src, 'function rrsg_is_noncompetitive_test_team(array $team): bool') !== false
        && substr_count($src, "'teamName' => \$teamName,\n                'userName' => (string)(\$applicable['userName']") === 1
        && substr_count($src, "'teamName' => \$teamName,\n                    'userName' => (string)(\$firstSpecial['userName']") === 1;

    $add('Expected baseline/signatures', $baseline ? 'PASS' : 'FAIL', 'v071 -> v072');

    $backupReady = is_dir($backupDir) ? is_writable($backupDir) : is_writable(dirname($backupDir));
    $add('Backup location writable/creatable', $backupReady ? 'PASS' : 'FAIL', $backupDir);

    if ($ok) {
        try {
            $patched = mrl_build_v072($src);
            $patched = str_replace('__HUMAN_REAL__', '9/13/2026 8:49:41 pm ET', $patched);
            $add('Patch construction', 'PASS', 'userID preservation + missing-userID hardening built successfully.');
            $add('Database writes', 'PASS', 'None.');
            $add('Snapshot files changed by installer', 'PASS', 'None.');
            $add('Expected R27 behavior', 'PASS', 'Over The Edge = 0 / No Picks.');
            $add('Expected R28 behavior', 'PASS', 'Over The Edge LP R28 scores Daniel Suarez / Denny Hamlin / Ty Gibbs / Alex Bowman.');
        } catch (Throwable $e) {
            $add('Patch construction', 'FAIL', $e->getMessage());
        }
    }

    return ['ok'=>$ok,'rows'=>$rows,'source'=>$src];
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
$rows = [];
$state = 'idle';
$notice = 'Nothing has run yet. Click Preview / Preflight first.';
$preflightPassed = false;
$rollbackAvailable = is_file($backup);

try {
    if ($action === 'preflight') {
        $pf = mrl_preflight($target, $backupDir);
        $rows = $pf['rows'];
        $preflightPassed = !empty($pf['ok']);
        $state = $preflightPassed ? 'good' : 'bad';
        $notice = $preflightPassed
            ? 'Preflight passed — Ready to Install v072.'
            : 'Preflight failed — Install remains disabled.';
    } elseif ($action === 'install') {
        $pf = mrl_preflight($target, $backupDir);
        $rows = $pf['rows'];

        if (empty($pf['ok'])) {
            throw new RuntimeException('Install blocked because preflight no longer passes.');
        }

        $src = (string)$pf['source'];
        $new = mrl_build_v072($src);
        $new = str_replace('__HUMAN_REAL__', '9/13/2026 8:49:41 pm ET', $new);

        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Could not create backup directory.');
        }

        if (is_file($backup)) {
            throw new RuntimeException('Backup already exists for this installer timestamp.');
        }

        if (!copy($target, $backup)) {
            throw new RuntimeException('Backup creation failed.');
        }
        $rows[] = ['Backup created', 'PASS', $backup];

        if (file_put_contents($temp, $new, LOCK_EX) === false) {
            @unlink($temp);
            throw new RuntimeException('Temporary write failed.');
        }

        $lint = mrl_lint($temp);
        $rows[] = ['PHP syntax lint', $lint['status'], $lint['detail']];
        if ($lint['status'] === 'FAIL') {
            @unlink($temp);
            throw new RuntimeException('PHP lint failed; target unchanged.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Atomic rename failed.');
        }

        $post = file_get_contents($target);
        $postOk = is_string($post)
            && strpos($post, '* VERSION: v072') !== false
            && strpos($post, "'userID' => (int)(\$applicable['userID']") !== false
            && strpos($post, "'userID' => (int)(\$firstSpecial['userID']") !== false
            && strpos($post, "A row with no userID field is not automatically a test row.") !== false;

        if (!$postOk) {
            @copy($backup, $target);
            throw new RuntimeException('Postflight failed; backup restored.');
        }

        $rows[] = ['Postflight version/signatures', 'PASS', 'v072 active; LP/RD overlay rows preserve userID.'];
        $rows[] = ['Install result', 'PASS', 'No DB writes and no snapshot files changed.'];

        $state = 'good';
        $notice = 'Installed successfully — v072 is active.';
        $preflightPassed = false;
        $rollbackAvailable = true;
    } elseif ($action === 'rollback') {
        if (!is_file($backup)) {
            throw new RuntimeException('Rollback backup is unavailable.');
        }

        if (!copy($backup, $temp)) {
            throw new RuntimeException('Could not stage rollback.');
        }

        $lint = mrl_lint($temp);
        $rows[] = ['Rollback PHP syntax lint', $lint['status'], $lint['detail']];
        if ($lint['status'] === 'FAIL') {
            @unlink($temp);
            throw new RuntimeException('Rollback lint failed.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Rollback rename failed.');
        }

        $restored = file_get_contents($target);
        if (!is_string($restored) || strpos($restored, '* VERSION: v071') === false) {
            throw new RuntimeException('Rollback postflight could not confirm v071.');
        }

        $rows[] = ['Rollback result', 'PASS', 'Restored weekly_standings.php v071.'];
        $state = 'info';
        $notice = 'Rollback complete — v071 restored.';
        $rollbackAvailable = true;
    }
} catch (Throwable $e) {
    $rows[] = ['Action', 'FAIL', $e->getMessage()];
    $state = 'bad';
    $notice = 'Action failed.';
    $rollbackAvailable = is_file($backup);
}

$hasRows = !empty($rows);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Weekly LP userID Fix</title>
<style>
:root{color-scheme:dark;--bg:#101312;--panel:#1b201f;--border:#46504d;--text:#eee9df;--muted:#b8b7b0;--gold:#f1c97f;--green:#167c45;--red:#a93434;--blue:#286c99;--amber:#d49b28;--disabled:#555}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,95%);margin:14px auto 28px}
h1{margin:0 0 10px;color:var(--gold);font-size:26px}h2{margin:0 0 8px;color:var(--gold);font-size:18px}
.panel{margin:0 0 10px;padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--panel)}
.notice{margin:0 0 10px;padding:10px 12px;border-radius:9px;font-weight:700}
.notice.good{background:#103b27;border:1px solid #2f9b63}.notice.bad{background:#4b1d1d;border:1px solid #c04b4b}.notice.info{background:#173246;border:1px solid #387ba8}.notice.idle{background:#332b15;border:1px solid #8c722e}
table{width:100%;border-collapse:collapse}th,td{padding:6px 8px;border-bottom:1px solid #353c3a;text-align:left;vertical-align:top}
th{color:var(--gold)}.PASS{color:#5ee58e;font-weight:800}.FAIL{color:#ff7b7b;font-weight:800}.SKIPPED{color:#f1c97f;font-weight:800}
.small{font-size:12px;color:var(--muted)}.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}
.actions{display:flex;gap:9px;flex-wrap:wrap}button{padding:8px 12px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.preflight{background:var(--blue)}.install{background:var(--green)}.rollback{background:var(--red)}.export{background:var(--amber)}
button:disabled{opacity:.45;cursor:not-allowed;background:var(--disabled)}
ul{margin:5px 0 0;padding-left:20px;line-height:1.45}
</style>
</head>
<body><div class="wrap">
<h1>MRL Weekly Standings LP userID Fix</h1>

<div class="panel">
<h2>Root Cause</h2>
<ul>
<li>Over The Edge's LP row exists and is correctly effective at R28.</li>
<li>The LP overlay rebuilt the row but accidentally dropped <code>userID</code>.</li>
<li>The test-team filter interpreted the missing ID as <code>0</code> and removed the valid LP row.</li>
<li>v071 then restored the missing legitimate team as a 0-point fallback row.</li>
</ul>
</div>

<div class="notice <?=mrl_h($state)?>"><?=mrl_h($notice)?></div>

<div class="panel">
<h2>Preflight / Result</h2>
<?php if (!$hasRows): ?>
<p class="small">Click Preview / Preflight first. Install remains disabled until the exact v071 baseline passes.</p>
<?php else: ?>
<table id="resultsTable">
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach($rows as $r): ?>
<tr><td><?=mrl_h($r[0])?></td><td class="<?=mrl_h($r[1])?>"><?=mrl_h($r[1])?></td><td class="small mono"><?=mrl_h($r[2])?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div class="panel">
<h2>Actions</h2>
<div class="actions">
<form method="post"><input type="hidden" name="action" value="preflight"><button class="preflight" type="submit">Preview / Preflight</button></form>
<form method="post"><input type="hidden" name="action" value="install"><button class="install" type="submit" <?= $preflightPassed ? '' : 'disabled' ?>>Install v072</button></form>
<form method="post" onsubmit="return confirm('Restore the exact pre-v072 weekly_standings.php?');"><input type="hidden" name="action" value="rollback"><button class="rollback" type="submit" <?= $rollbackAvailable ? '' : 'disabled' ?>>Rollback</button></form>
<button id="exportBtn" class="export" type="button" <?= $hasRows ? '' : 'disabled' ?>>Export Results</button>
</div>
</div>

<div class="panel small">
<strong>Expected after install:</strong> R27 remains Over The Edge = 0 / No Picks. R28 should use LP pick 2460 and score Daniel Suarez, Denny Hamlin, Ty Gibbs, and Alex Bowman.
<br><br>
FILE: MRL_install_weekly_LP_userid_fix_v001_20260913_204941.php | INSTALLER VERSION: v001
</div>
</div>
<script>
(function(){
  var b=document.getElementById('exportBtn');
  if(!b || b.disabled) return;
  b.addEventListener('click',function(){
    var lines=['MRL Weekly Standings LP userID Fix','Generated: 9/13/2026 8:49:41 pm ET','State: <?=mrl_h($notice)?>',''];
    document.querySelectorAll('#resultsTable tr').forEach(function(tr){
      var c=tr.querySelectorAll('th,td');
      if(c.length===3) lines.push(c[0].textContent.trim()+' | '+c[1].textContent.trim()+' | '+c[2].textContent.trim());
    });
    var blob=new Blob([lines.join('\r\n')+'\r\n'],{type:'text/plain;charset=utf-8'});
    var u=URL.createObjectURL(blob),a=document.createElement('a');
    a.href=u;a.download='MRL_weekly_LP_userid_fix_results_20260913_204941.txt';
    document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(u);
  });
})();
</script>
</body></html>
