<?php
declare(strict_types=1);

/**
 * MRL installer
 * TITLE: Revision Classifier Race-Effective Driver Pool Fix
 * INSTALLER VERSION: v001
 * GENERATED: 9/13/2026 5:09:21 pm ET
 *
 * TARGET:
 *   /race_results/race_results_classify_revisions.php
 *   v011 -> v012
 *
 * PURPOSE:
 * - Make revision classification use the same race-effective competitive-pick
 *   concept now used by _mrl_segment generation.
 * - Exclude userID 0 / 999, MRL test team, and username MRL.
 * - Preserve baseline SEG / ADJ picks until an LP / RD row is effective.
 * - Prevent future-race LP/RD or temporary test picks from contaminating an
 *   earlier race's "segment-picked" classification.
 *
 * SAFETY:
 * - No database writes.
 * - No snapshot regeneration.
 * - Exact baseline/signature preflight.
 * - Timestamped backup.
 * - Temp-file write and atomic rename.
 * - PHP lint is informational only when unavailable.
 * - Rollback support.
 */

date_default_timezone_set('America/New_York');

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$targetRel = '/race_results/race_results_classify_revisions.php';
$target = $docRoot . $targetRel;
$backupDir = $docRoot . '/_mrl_installer_backups';
$backup = $backupDir . '/race_results_classify_revisions.php.pre_v012_20260913_170921.bak';
$temp = dirname($target) . '/.race_results_classify_revisions_v012_20260913_170921.tmp.php';

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

function mrl_build_v012(string $src): string {
    $src = mrl_replace_once($src, " * VERSION: v011\n", " * VERSION: v012\n", 'Header version');

    $src = mrl_replace_once(
        $src,
        " * CHANGELOG:\n *\n * v011 (7/12/2026 1:15:52 pm)\n",
        " * CHANGELOG:\n *\n"
        . " * v012 (9/13/2026 5:09:21 pm ET)\n"
        . " * - FIX: Segment-picked revision classification now resolves the competitive driver pool for the specific race number being classified.\n"
        . " * - FIX: userID 0 / 999, MRL test team, and username MRL cannot contribute drivers to MRL-impact classification.\n"
        . " * - FIX: SEG / ADJ remains the baseline; LP / RD becomes authoritative only when effective_race <= the classified race.\n"
        . " * - FIX: Future-race LP / RD rows no longer retroactively alter an earlier race's segment-picked driver universe.\n"
        . " * - PRESERVE: Canonical snapshots, all-driver comparison, MRL-listed comparison, release history, pair history, and artifact formats remain unchanged.\n"
        . " *\n"
        . " * v011 (7/12/2026 1:15:52 pm)\n",
        'Changelog'
    );

    $src = mrl_replace_once(
        $src,
        "const RRCR_VERSION = 'v011';\nconst RRCR_SIGNATURE = 'RACE_RESULTS_CLASSIFY_REVISIONS v011';",
        "const RRCR_VERSION = 'v012';\nconst RRCR_SIGNATURE = 'RACE_RESULTS_CLASSIFY_REVISIONS v012';",
        'Classifier constants'
    );

    $oldFunction = <<<'PHP'
function rrcr_get_segment_driver_pool(string $raceYear, string $segment, PDO $dbo): array
{
    $drivers = [];

    $sql = "
        SELECT driverA, driverB, driverC, driverD
        FROM user_picks
        WHERE raceYear = :raceYear
          AND segment = :segment
    ";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':raceYear' => $raceYear,
        ':segment' => $segment,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
        return [];
    }

    foreach ($rows as $row) {
        if (!is_array($row)) continue;

        foreach (['driverA', 'driverB', 'driverC', 'driverD'] as $field) {
            $name = rrcr_normalize_driver_name((string)($row[$field] ?? ''));
            if ($name !== '') {
                $drivers[$name] = true;
            }
        }
    }

    $pool = array_keys($drivers);
    usort($pool, function ($a, $b) {
        return strcasecmp((string)$a, (string)$b);
    });

    return $pool;
}
PHP;

    $newFunction = <<<'PHP'
function rrcr_get_segment_driver_pool(string $raceYear, string $segment, int $raceNumber, PDO $dbo): array
{
    $sql = "
        SELECT
            up.pickID,
            up.userID,
            up.teamName,
            COALESCE(u.userName, '') AS userName,
            up.driverA,
            up.driverB,
            up.driverC,
            up.driverD,
            up.entryDate,
            up.pick_type,
            up.effective_race
        FROM user_picks up
        LEFT JOIN users u ON u.userID = up.userID
        WHERE up.raceYear = :raceYear
          AND up.segment = :segment
          AND up.pick_type IN ('SEG', 'ADJ', 'LP', 'RD')
          AND up.userID NOT IN (0, 999)
          AND LOWER(TRIM(COALESCE(up.teamName, ''))) <> 'mrl test team'
          AND COALESCE(u.userName, '') <> 'MRL'
        ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC
    ";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':raceYear' => $raceYear,
        ':segment' => $segment,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
        return [];
    }

    $teams = [];

    foreach ($rows as $row) {
        if (!is_array($row)) continue;

        $userId = (int)($row['userID'] ?? 0);
        if ($userId === 0 || $userId === 999) continue;

        $teamName = trim((string)($row['teamName'] ?? ''));
        $userName = trim((string)($row['userName'] ?? ''));
        if (strcasecmp($teamName, 'MRL test team') === 0) continue;
        if (strcasecmp($userName, 'MRL') === 0) continue;

        $teamKey = (string)$userId;
        if (!isset($teams[$teamKey])) {
            $teams[$teamKey] = [
                'base' => null,
                'special' => null,
            ];
        }

        $pickType = strtoupper(trim((string)($row['pick_type'] ?? 'SEG')));

        if ($pickType === 'SEG' || $pickType === 'ADJ') {
            // Match the current baseline pick loader: first baseline row wins.
            if ($teams[$teamKey]['base'] === null) {
                $teams[$teamKey]['base'] = $row;
            }
            continue;
        }

        if ($pickType !== 'LP' && $pickType !== 'RD') {
            continue;
        }

        $effectiveRace = (int)($row['effective_race'] ?? 0);
        if ($effectiveRace <= 0 || $effectiveRace > $raceNumber) {
            continue;
        }

        $current = $teams[$teamKey]['special'];
        if ($current === null) {
            $teams[$teamKey]['special'] = $row;
            continue;
        }

        $currentRace = (int)($current['effective_race'] ?? 0);
        $currentDate = strtotime((string)($current['entryDate'] ?? '')) ?: 0;
        $rowDate = strtotime((string)($row['entryDate'] ?? '')) ?: 0;
        $currentPickId = (int)($current['pickID'] ?? 0);
        $rowPickId = (int)($row['pickID'] ?? 0);

        if (
            $effectiveRace > $currentRace
            || ($effectiveRace === $currentRace && $rowDate > $currentDate)
            || ($effectiveRace === $currentRace && $rowDate === $currentDate && $rowPickId > $currentPickId)
        ) {
            $teams[$teamKey]['special'] = $row;
        }
    }

    $drivers = [];

    foreach ($teams as $team) {
        $effectiveRow = is_array($team['special'] ?? null)
            ? $team['special']
            : (is_array($team['base'] ?? null) ? $team['base'] : null);

        if ($effectiveRow === null) continue;

        foreach (['driverA', 'driverB', 'driverC', 'driverD'] as $field) {
            $name = rrcr_normalize_driver_name((string)($effectiveRow[$field] ?? ''));
            if ($name !== '') {
                $drivers[$name] = true;
            }
        }
    }

    return rrcr_sort_driver_pool($drivers);
}
PHP;

    $src = mrl_replace_once($src, $oldFunction, $newFunction, 'Segment driver-pool function');

    $src = mrl_replace_once(
        $src,
        "\$driverPool = rrcr_get_segment_driver_pool((string)\$raceInfo['year'], (string)\$raceInfo['segment'], \$dbo);",
        "\$driverPool = rrcr_get_segment_driver_pool((string)\$raceInfo['year'], (string)\$raceInfo['segment'], (int)(\$raceInfo['number'] ?? 0), \$dbo);",
        'Classifier race-number call'
    );

    return str_replace('9/13/2026 5:09:21 pm ET', '__HUMAN_REAL__', $src);
}

function mrl_lint(string $path): array {
    if (!function_exists('exec')) {
        return ['status'=>'SKIPPED','detail'=>'Server does not expose a usable PHP CLI lint command.'];
    }
    $bin = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($bin) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines = [];
    $code = 0;
    @exec($cmd, $lines, $code);
    if ($code === 0) {
        return ['status'=>'PASS','detail'=>trim(implode("\n", $lines))];
    }
    if ($code === 127 || empty($lines)) {
        return ['status'=>'SKIPPED','detail'=>'PHP CLI lint is unavailable; postflight verification will still run.'];
    }
    return ['status'=>'FAIL','detail'=>trim(implode("\n", $lines))];
}

function mrl_preflight(string $target, string $backupDir): array {
    $rows = [];
    $ok = true;

    $add = function(string $check, string $status, string $detail = '') use (&$rows, &$ok): void {
        $rows[] = [$check, $status, $detail];
        if ($status === 'FAIL') $ok = false;
    };

    $exists = is_file($target);
    $add('race_results_classify_revisions.php exists', $exists ? 'PASS' : 'FAIL', $target);
    if (!$exists) return ['ok'=>false,'rows'=>$rows,'source'=>''];

    $readable = is_readable($target) && is_writable($target);
    $add('race_results_classify_revisions.php readable/writable', $readable ? 'PASS' : 'FAIL', '');
    if (!$readable) return ['ok'=>false,'rows'=>$rows,'source'=>''];

    $src = file_get_contents($target);
    if (!is_string($src)) {
        $add('Target readable', 'FAIL', 'Unable to read file contents.');
        return ['ok'=>false,'rows'=>$rows,'source'=>''];
    }

    $baseline = strpos($src, '* VERSION: v011') !== false
        && strpos($src, "const RRCR_VERSION = 'v011';") !== false
        && strpos($src, "function rrcr_get_segment_driver_pool(string \$raceYear, string \$segment, PDO \$dbo): array") !== false
        && substr_count($src, "\$driverPool = rrcr_get_segment_driver_pool((string)\$raceInfo['year'], (string)\$raceInfo['segment'], \$dbo);") === 1;

    $add('Expected baseline/signatures', $baseline ? 'PASS' : 'FAIL', 'v011 -> v012');

    $backupReady = is_dir($backupDir) ? is_writable($backupDir) : is_writable(dirname($backupDir));
    $add('Backup location writable/creatable', $backupReady ? 'PASS' : 'FAIL', $backupDir);

    if ($ok) {
        try {
            $patched = mrl_build_v012($src);
            $patched = str_replace('__HUMAN_REAL__', '9/13/2026 5:09:21 pm ET', $patched);
            $add('Patch construction', 'PASS', 'Race-effective driver-pool patch built successfully.');
            $add('Database writes', 'PASS', 'None.');
            $add('Snapshot files changed by installer', 'PASS', 'None.');
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
            ? 'Preflight passed — Ready to Install v012.'
            : 'Preflight failed — Install remains disabled.';
    } elseif ($action === 'install') {
        $pf = mrl_preflight($target, $backupDir);
        $rows = $pf['rows'];
        if (empty($pf['ok'])) {
            throw new RuntimeException('Install blocked because preflight no longer passes.');
        }

        $preflightPassed = true;
        $src = (string)$pf['source'];
        $new = mrl_build_v012($src);
        $new = str_replace('__HUMAN_REAL__', '9/13/2026 5:09:21 pm ET', $new);

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
            && strpos($post, '* VERSION: v012') !== false
            && strpos($post, "const RRCR_VERSION = 'v012';") !== false
            && strpos($post, 'int $raceNumber, PDO $dbo') !== false
            && strpos($post, "(int)(\$raceInfo['number'] ?? 0), \$dbo") !== false;

        if (!$postOk) {
            @copy($backup, $target);
            throw new RuntimeException('Postflight failed; backup restored.');
        }

        $rows[] = ['Postflight version/signatures', 'PASS', 'v012 active; race number now reaches the segment-picked driver resolver.'];
        $rows[] = ['Install result', 'PASS', 'No DB writes and no snapshot files changed.'];

        $state = 'good';
        $notice = 'Installed successfully — v012 is active.';
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
        if (!is_string($restored) || strpos($restored, '* VERSION: v011') === false) {
            throw new RuntimeException('Rollback postflight could not confirm v011.');
        }

        $rows[] = ['Rollback result', 'PASS', 'Restored race_results_classify_revisions.php v011.'];
        $state = 'info';
        $notice = 'Rollback complete — v011 restored.';
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
<title>MRL Revision Classifier Race-Effective Fix</title>
<style>
:root{color-scheme:dark;--bg:#101312;--panel:#1b201f;--border:#46504d;--text:#eee9df;--muted:#b8b7b0;--gold:#f1c97f;--green:#167c45;--red:#a93434;--blue:#286c99;--amber:#d49b28;--disabled:#555}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,95%);margin:14px auto 28px}
h1{margin:0 0 10px;color:var(--gold);font-size:26px}
h2{margin:0 0 8px;color:var(--gold);font-size:18px}
.panel{margin:0 0 10px;padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--panel)}
.notice{margin:0 0 10px;padding:10px 12px;border-radius:9px;font-weight:700}
.notice.good{background:#103b27;border:1px solid #2f9b63}.notice.bad{background:#4b1d1d;border:1px solid #c04b4b}.notice.info{background:#173246;border:1px solid #387ba8}.notice.idle{background:#332b15;border:1px solid #8c722e}
table{width:100%;border-collapse:collapse}th,td{padding:6px 8px;border-bottom:1px solid #353c3a;text-align:left;vertical-align:top}
th{color:var(--gold)}.PASS{color:#5ee58e;font-weight:800}.FAIL{color:#ff7b7b;font-weight:800}.SKIPPED{color:#f1c97f;font-weight:800}
.small{font-size:12px;color:var(--muted)}.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}
.actions{display:flex;gap:9px;flex-wrap:wrap}
button{padding:8px 12px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.preflight{background:var(--blue)}.install{background:var(--green)}.rollback{background:var(--red)}.export{background:var(--amber)}
button:disabled{opacity:.45;cursor:not-allowed;background:var(--disabled)}
ul{margin:5px 0 0;padding-left:20px;line-height:1.45}
</style>
</head>
<body><div class="wrap">
<h1>MRL Revision Classifier Race-Effective Fix</h1>

<div class="panel">
<h2>Package</h2>
<ul>
<li><code>race_results_classify_revisions.php</code> v011 → v012</li>
<li>Uses the specific race number when building the segment-picked driver universe.</li>
<li>Excludes userID 0 / 999 and MRL test/admin rows.</li>
<li>LP / RD drivers count only when their <code>effective_race</code> has been reached.</li>
<li>No database writes and no snapshot regeneration.</li>
</ul>
</div>

<div class="notice <?=mrl_h($state)?>"><?=mrl_h($notice)?></div>

<div class="panel">
<h2>Preflight / Result</h2>
<?php if (!$hasRows): ?>
<p class="small">Click Preview / Preflight to run the checks. Install is disabled until preflight passes.</p>
<?php else: ?>
<table id="resultsTable">
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><?=mrl_h($r[0])?></td>
<td class="<?=mrl_h($r[1])?>"><?=mrl_h($r[1])?></td>
<td class="small mono"><?=mrl_h($r[2])?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div class="panel">
<h2>Actions</h2>
<div class="actions">
<form method="post"><input type="hidden" name="action" value="preflight"><button class="preflight" type="submit">Preview / Preflight</button></form>
<form method="post"><input type="hidden" name="action" value="install"><button class="install" type="submit" <?= $preflightPassed ? '' : 'disabled' ?>>Install v012</button></form>
<form method="post" onsubmit="return confirm('Restore the exact pre-v012 classifier file?');"><input type="hidden" name="action" value="rollback"><button class="rollback" type="submit" <?= $rollbackAvailable ? '' : 'disabled' ?>>Rollback</button></form>
<button id="exportBtn" class="export" type="button" <?= $hasRows ? '' : 'disabled' ?>>Export Results</button>
</div>
</div>

<div class="panel small">
<strong>Next step after v012:</strong> run the separate R27 rebuild utility. It will regenerate only R27 derived companions from the untouched canonical ESPN snapshots, preserve their timestamps, then rerun R27 classification.
<br><br>
FILE: MRL_install_revision_classifier_race_effective_fix_v001_20260913_170921.php | INSTALLER VERSION: v001
</div>
</div>

<script>
(function(){
  var b=document.getElementById('exportBtn');
  if(!b || b.disabled) return;
  b.addEventListener('click',function(){
    var lines=['MRL Revision Classifier Race-Effective Fix','Generated: 9/13/2026 5:09:21 pm ET','State: <?=mrl_h($notice)?>',''];
    document.querySelectorAll('#resultsTable tr').forEach(function(tr){
      var c=tr.querySelectorAll('th,td');
      if(c.length===3) lines.push(c[0].textContent.trim()+' | '+c[1].textContent.trim()+' | '+c[2].textContent.trim());
    });
    var blob=new Blob([lines.join('\r\n')+'\r\n'],{type:'text/plain;charset=utf-8'});
    var u=URL.createObjectURL(blob),a=document.createElement('a');
    a.href=u;a.download='MRL_revision_classifier_fix_results_20260913_170921.txt';
    document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(u);
  });
})();
</script>
</body></html>
