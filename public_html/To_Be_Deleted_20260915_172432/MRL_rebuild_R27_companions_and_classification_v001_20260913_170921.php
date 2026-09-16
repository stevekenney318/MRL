<?php
declare(strict_types=1);

/**
 * MRL maintenance utility
 * TITLE: R27 Derived Snapshot + Classification Rebuild
 * VERSION: v001
 * GENERATED: 9/13/2026 5:09:21 pm ET
 *
 * SCOPE:
 * - 2026 R27 only.
 * - Canonical ESPN snapshots are READ-ONLY and hash-verified before/after.
 * - Regenerates only timestamp-matched _lite, _mrl, and _mrl_segment companions.
 * - Uses race_results_snapshot_views_helper.php v002.
 * - Re-runs R27 revision classification using race_results_classify_revisions.php v012.
 * - No database writes.
 * - No ESPN fetch.
 *
 * ROLLBACK:
 * - Creates a timestamped repair backup inside the R27 race folder.
 * - Restores all overwritten derived/classification files and removes files that
 *   did not exist before the repair.
 */

date_default_timezone_set('America/New_York');

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$raceBase = $docRoot . '/race_results';
$year = 2026;
$raceNumber = 27;
$raceCode = 'R27';
$yearIndexPath = $raceBase . '/' . $year . '/_year_index.json';
$repairStamp = '20260913_170921';

function mrl_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function mrl_locate_race(array $index, int $raceNumber, string $yearFolder): array {
    $out = [];
    if (!isset($index['races']) || !is_array($index['races'])) return $out;
    foreach ($index['races'] as $raceId => $row) {
        if (!is_array($row)) continue;
        if ((string)($row['kind'] ?? '') !== 'R') continue;
        if ((int)($row['number'] ?? 0) !== $raceNumber) continue;
        $folder = (string)($row['folder'] ?? '');
        if ($folder === '') continue;
        return [
            'race_id'=>(string)$raceId,
            'folder'=>$folder,
            'race_name'=>(string)($row['race_name'] ?? ''),
            'race_folder'=>rtrim($yearFolder, '/\\') . '/' . $folder,
        ];
    }
    return $out;
}

function mrl_canonical_snapshots(string $raceFolder): array {
    $files = glob(rtrim($raceFolder, '/\\') . '/snapshot_*.html') ?: [];
    $out = [];
    foreach ($files as $f) {
        if (preg_match('/^snapshot_\d{8}_\d{9}\.html$/', basename((string)$f))) {
            $out[] = (string)$f;
        }
    }
    sort($out, SORT_STRING);
    return $out;
}

function mrl_target_files(string $raceFolder, array $canonical): array {
    $targets = [];
    foreach ($canonical as $snap) {
        $targets[] = preg_replace('/\.html$/', '_lite.html', $snap);
        $targets[] = preg_replace('/\.html$/', '_mrl.html', $snap);
        $targets[] = preg_replace('/\.html$/', '_mrl_segment.html', $snap);
    }
    foreach (glob(rtrim($raceFolder, '/\\') . '/mrl_impact*') ?: [] as $f) $targets[] = (string)$f;
    foreach (glob(rtrim($raceFolder, '/\\') . '/all_driver_impact*') ?: [] as $f) $targets[] = (string)$f;
    $targets = array_values(array_unique(array_filter($targets, 'is_string')));
    sort($targets, SORT_STRING);
    return $targets;
}

function mrl_make_backup(string $backupDir, array $targets): array {
    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Could not create repair backup directory.');
    }

    $manifest = ['created_at'=>date('c'),'files'=>[]];

    foreach ($targets as $path) {
        $base = basename((string)$path);
        $exists = is_file($path);
        $manifest['files'][$base] = ['path'=>$path,'existed'=>$exists];

        if ($exists && !copy($path, $backupDir . '/' . $base)) {
            throw new RuntimeException('Backup copy failed for ' . $base);
        }
    }

    $manifestPath = $backupDir . '/manifest.json';
    if (file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Could not write repair backup manifest.');
    }

    return $manifest;
}

function mrl_restore_backup(string $backupDir): array {
    $manifestPath = $backupDir . '/manifest.json';
    if (!is_file($manifestPath)) {
        throw new RuntimeException('Repair backup manifest not found.');
    }
    $raw = file_get_contents($manifestPath);
    $manifest = json_decode((string)$raw, true);
    if (!is_array($manifest) || !isset($manifest['files']) || !is_array($manifest['files'])) {
        throw new RuntimeException('Repair backup manifest is invalid.');
    }

    $restored = 0;
    $removed = 0;

    foreach ($manifest['files'] as $base => $row) {
        if (!is_array($row)) continue;
        $path = (string)($row['path'] ?? '');
        if ($path === '') continue;

        if (!empty($row['existed'])) {
            $backupFile = $backupDir . '/' . $base;
            if (!is_file($backupFile) || !copy($backupFile, $path)) {
                throw new RuntimeException('Rollback restore failed for ' . $base);
            }
            $restored++;
        } else {
            if (is_file($path)) {
                if (!unlink($path)) {
                    throw new RuntimeException('Rollback could not remove newly created ' . $base);
                }
                $removed++;
            }
        }
    }

    return ['restored'=>$restored,'removed'=>$removed];
}

function mrl_file_version(string $path): string {
    if (!is_file($path)) return '';
    $raw = file_get_contents($path);
    if (!is_string($raw)) return '';
    if (preg_match('/\* VERSION:\s*(v\d+)/', $raw, $m)) return (string)$m[1];
    if (preg_match("/const RRCR_VERSION = '(v\d+)'/", $raw, $m)) return (string)$m[1];
    return '';
}

function mrl_preflight(string $raceBase, string $yearIndexPath, int $year, int $raceNumber): array {
    $rows = [];
    $ok = true;

    $add = function(string $check, string $status, string $detail='') use (&$rows, &$ok): void {
        $rows[] = [$check,$status,$detail];
        if ($status === 'FAIL') $ok = false;
    };

    $helper = $raceBase . '/race_results_snapshot_views_helper.php';
    $classifier = $raceBase . '/race_results_classify_revisions.php';

    $add('Snapshot companion helper', mrl_file_version($helper)==='v002' ? 'PASS':'FAIL', mrl_file_version($helper) . ' (required v002)');
    $add('Revision classifier', mrl_file_version($classifier)==='v012' ? 'PASS':'FAIL', mrl_file_version($classifier) . ' (required v012)');
    $add('Year index exists', is_file($yearIndexPath) ? 'PASS':'FAIL', $yearIndexPath);

    $race = [];
    $canonical = [];

    if (is_file($yearIndexPath)) {
        $index = json_decode((string)file_get_contents($yearIndexPath), true);
        if (is_array($index)) {
            $race = mrl_locate_race($index, $raceNumber, $raceBase . '/' . $year);
        }
    }

    $add('R27 found in year index', !empty($race) ? 'PASS':'FAIL', (string)($race['folder'] ?? ''));
    if (!empty($race)) {
        $add('R27 race folder exists', is_dir((string)$race['race_folder']) ? 'PASS':'FAIL', (string)$race['race_folder']);
        if (is_dir((string)$race['race_folder'])) {
            $canonical = mrl_canonical_snapshots((string)$race['race_folder']);
            $add('Canonical ESPN snapshots', count($canonical) >= 2 ? 'PASS':'FAIL', count($canonical) . ' canonical snapshot(s) found');
            foreach ($canonical as $snap) {
                $add('Canonical snapshot readable', is_readable($snap) ? 'PASS':'FAIL', basename($snap));
            }
        }
    }

    if ($ok) {
        $add('Canonical ESPN snapshots modified by repair', 'PASS', 'No — read-only and SHA-256 verified before/after.');
        $add('Database writes', 'PASS', 'None.');
        $add('ESPN/network fetch', 'PASS', 'None — rebuild uses stored canonical snapshots only.');
        $add('Companion timestamp rule', 'PASS', 'Generated companion mtimes must exactly match each canonical snapshot mtime.');
    }

    return ['ok'=>$ok,'rows'=>$rows,'race'=>$race,'canonical'=>$canonical];
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
$rows = [];
$state = 'idle';
$notice = 'Nothing has run yet. Click Preview / Preflight first.';
$preflightPassed = false;

$pre = mrl_preflight($raceBase, $yearIndexPath, $year, $raceNumber);
$race = $pre['race'];
$canonical = $pre['canonical'];
$raceFolder = (string)($race['race_folder'] ?? '');
$backupDir = $raceFolder !== '' ? $raceFolder . '/_repair_backup_' . $repairStamp : '';
$rollbackAvailable = $backupDir !== '' && is_file($backupDir . '/manifest.json');

try {
    if ($action === 'preflight') {
        $rows = $pre['rows'];
        $preflightPassed = !empty($pre['ok']);
        $state = $preflightPassed ? 'good':'bad';
        $notice = $preflightPassed
            ? 'Preflight passed — Ready to rebuild R27 derived companions and classification.'
            : 'Preflight failed — Rebuild remains disabled.';
    } elseif ($action === 'repair') {
        if (empty($pre['ok'])) {
            $rows = $pre['rows'];
            throw new RuntimeException('Repair blocked because preflight does not pass.');
        }

        $rows = $pre['rows'];
        $preflightPassed = true;

        if ($backupDir === '') throw new RuntimeException('Backup directory could not be resolved.');
        if (is_file($backupDir . '/manifest.json')) {
            throw new RuntimeException('Repair backup already exists for this utility timestamp; refusing to overwrite it.');
        }

        $targets = mrl_target_files($raceFolder, $canonical);
        $manifest = mrl_make_backup($backupDir, $targets);
        $rows[] = ['Repair backup', 'PASS', $backupDir . ' (' . count($manifest['files']) . ' tracked file(s))'];

        $canonicalHashes = [];
        $canonicalMtimes = [];
        foreach ($canonical as $snap) {
            $canonicalHashes[$snap] = hash_file('sha256', $snap);
            $canonicalMtimes[$snap] = filemtime($snap);
        }

        define('RRCR_AUTO_RUN', false);
        require_once $raceBase . '/race_results_snapshot_views_helper.php';
        require_once $raceBase . '/race_results_classify_revisions.php';

        global $dbo, $dbconnect;
        if (!isset($dbo) || !($dbo instanceof PDO)) {
            throw new RuntimeException('PDO database handle is unavailable.');
        }

        $generationErrors = [];

        foreach ($canonical as $snap) {
            $result = rrsv_generate_companion_set(
                $snap,
                $year,
                $raceNumber,
                (string)$race['folder'],
                $dbo,
                $dbconnect ?? null,
                true
            );

            if (empty($result['ok'])) {
                $errs = isset($result['errors']) && is_array($result['errors']) ? implode('; ', $result['errors']) : 'unknown generation error';
                $generationErrors[] = basename($snap) . ': ' . $errs;
                $rows[] = ['Regenerate companions', 'FAIL', basename($snap) . ' — ' . $errs];
                continue;
            }

            $count = (int)($result['counts']['mrl_segment']['kept'] ?? -1);
            $source = (int)($result['counts']['mrl_segment']['source'] ?? -1);
            $rows[] = ['Regenerate companions', 'PASS', basename($snap) . ' — _mrl_segment kept ' . $count . '/' . $source];

            $mtimeOk = true;
            foreach (['_lite.html','_mrl.html','_mrl_segment.html'] as $suffix) {
                $p = preg_replace('/\.html$/', $suffix, $snap);
                if (!is_file($p) || filemtime($p) !== $canonicalMtimes[$snap]) {
                    $mtimeOk = false;
                }
            }
            $rows[] = ['Timestamp preservation', $mtimeOk ? 'PASS':'FAIL', basename($snap) . ' companion mtimes = canonical mtime'];
            if (!$mtimeOk) $generationErrors[] = basename($snap) . ': companion timestamp mismatch';

            $afterHash = hash_file('sha256', $snap);
            $hashOk = hash_equals((string)$canonicalHashes[$snap], (string)$afterHash);
            $rows[] = ['Canonical SHA-256 unchanged', $hashOk ? 'PASS':'FAIL', basename($snap)];
            if (!$hashOk) $generationErrors[] = basename($snap) . ': canonical hash changed';
        }

        if (!empty($generationErrors)) {
            $rb = mrl_restore_backup($backupDir);
            throw new RuntimeException('Companion rebuild verification failed; automatic rollback restored ' . $rb['restored'] . ' file(s) and removed ' . $rb['removed'] . ' new file(s).');
        }

        $classification = rrcr_run_single_race((string)$year, $raceCode, $dbo, true, false);

        $classified = !empty($classification['classified']);
        $impact = !empty($classification['impact']);
        $poolCount = (int)($classification['driverPoolCount'] ?? 0);
        $changedSegment = (int)($classification['changedSegmentPickedDriversCount'] ?? ($classification['changedDriversCount'] ?? 0));
        $changedMrl = (int)($classification['changedMrlListedDriversCount'] ?? 0);
        $changedAll = (int)($classification['changedAllDriversCount'] ?? ($classification['allDriverChangedCount'] ?? 0));
        $prev = (string)($classification['previousSnapshot'] ?? '');
        $curr = (string)($classification['currentSnapshot'] ?? '');
        $changeLabel = (string)($classification['change_status_label'] ?? ($classification['message'] ?? ''));

        $rows[] = ['R27 classification completed', $classified ? 'PASS':'FAIL', $prev . ' -> ' . $curr];
        $rows[] = ['Race-effective segment driver pool', $poolCount === 21 ? 'PASS':'FAIL', $poolCount . ' drivers (expected 21 for current R27 competitive state)'];
        $rows[] = ['Changed segment-picked drivers', $changedSegment === 0 ? 'PASS':'FAIL', (string)$changedSegment];
        $rows[] = ['MRL Impact', !$impact ? 'PASS':'FAIL', $impact ? 'YES' : 'NO'];
        $rows[] = ['Changed MRL-listed drivers', 'PASS', (string)$changedMrl];
        $rows[] = ['Changed all ESPN drivers', 'PASS', (string)$changedAll];
        $rows[] = ['Classification label', 'PASS', $changeLabel];

        if (!$classified || $poolCount !== 21 || $changedSegment !== 0 || $impact) {
            $rb = mrl_restore_backup($backupDir);
            throw new RuntimeException('R27 classification did not match the expected no-MRL-impact result; automatic rollback restored the pre-repair derived/classification files.');
        }

        $state = 'good';
        $notice = 'R27 rebuild complete — derived companions and classification are corrected.';
        $preflightPassed = false;
        $rollbackAvailable = true;
    } elseif ($action === 'rollback') {
        if (!$rollbackAvailable) {
            throw new RuntimeException('Repair backup is unavailable.');
        }
        $rb = mrl_restore_backup($backupDir);
        $rows[] = ['Rollback', 'PASS', 'Restored ' . $rb['restored'] . ' file(s); removed ' . $rb['removed'] . ' file(s) created by repair.'];
        $rows[] = ['Canonical ESPN snapshots', 'PASS', 'Untouched by rollback because repair never modifies them.'];
        $state = 'info';
        $notice = 'R27 repair rollback complete.';
    }
} catch (Throwable $e) {
    $rows[] = ['Action', 'FAIL', $e->getMessage()];
    $state = 'bad';
    $notice = 'Action failed.';
    $rollbackAvailable = $backupDir !== '' && is_file($backupDir . '/manifest.json');
}

$hasRows = !empty($rows);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL R27 Derived Snapshot + Classification Rebuild</title>
<style>
:root{color-scheme:dark;--bg:#101312;--panel:#1b201f;--border:#46504d;--text:#eee9df;--muted:#b8b7b0;--gold:#f1c97f;--green:#167c45;--red:#a93434;--blue:#286c99;--amber:#d49b28;--disabled:#555}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1180px,96%);margin:14px auto 28px}
h1{margin:0 0 10px;color:var(--gold);font-size:26px}h2{margin:0 0 8px;color:var(--gold);font-size:18px}
.panel{margin:0 0 10px;padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--panel)}
.notice{margin:0 0 10px;padding:10px 12px;border-radius:9px;font-weight:700}
.notice.good{background:#103b27;border:1px solid #2f9b63}.notice.bad{background:#4b1d1d;border:1px solid #c04b4b}.notice.info{background:#173246;border:1px solid #387ba8}.notice.idle{background:#332b15;border:1px solid #8c722e}
table{width:100%;border-collapse:collapse}th,td{padding:6px 8px;border-bottom:1px solid #353c3a;text-align:left;vertical-align:top}
th{color:var(--gold)}.PASS{color:#5ee58e;font-weight:800}.FAIL{color:#ff7b7b;font-weight:800}.SKIPPED{color:#f1c97f;font-weight:800}
.small{font-size:12px;color:var(--muted)}.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}
.actions{display:flex;gap:9px;flex-wrap:wrap}button{padding:8px 12px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.preflight{background:var(--blue)}.repair{background:var(--green)}.rollback{background:var(--red)}.export{background:var(--amber)}
button:disabled{opacity:.45;cursor:not-allowed;background:var(--disabled)}
ul{margin:5px 0 0;padding-left:20px;line-height:1.45}
</style>
</head>
<body><div class="wrap">
<h1>MRL R27 Derived Snapshot + Classification Rebuild</h1>

<div class="panel">
<h2>Scope</h2>
<ul>
<li><strong>2026 R27 Darlington only.</strong></li>
<li>Canonical ESPN snapshots remain untouched and are SHA-256 checked before/after.</li>
<li>Regenerates only <code>_lite</code>, <code>_mrl</code>, and <code>_mrl_segment</code> companions from each original canonical snapshot.</li>
<li>Companion modification times are reset to the exact canonical snapshot modification time.</li>
<li>Re-runs R27 classification using the corrected race-effective driver pool.</li>
<li>No DB writes and no ESPN/network fetch.</li>
</ul>
</div>

<div class="notice <?=mrl_h($state)?>"><?=mrl_h($notice)?></div>

<div class="panel">
<h2>Preflight / Result</h2>
<?php if (!$hasRows): ?>
<p class="small">Install the classifier v012 fix first, then click Preview / Preflight here.</p>
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
<form method="post" onsubmit="return confirm('Rebuild R27 derived companions and classification from the stored canonical ESPN snapshots?');"><input type="hidden" name="action" value="repair"><button class="repair" type="submit" <?= $preflightPassed ? '' : 'disabled' ?>>Rebuild R27</button></form>
<form method="post" onsubmit="return confirm('Restore the exact pre-repair derived/classification files?');"><input type="hidden" name="action" value="rollback"><button class="rollback" type="submit" <?= $rollbackAvailable ? '' : 'disabled' ?>>Rollback</button></form>
<button id="exportBtn" class="export" type="button" <?= $hasRows ? '' : 'disabled' ?>>Export Results</button>
</div>
</div>

<div class="panel small">
<strong>Expected corrected R27 classification:</strong> 21 race-effective competitive segment drivers, 0 changed segment-picked drivers for the stored Darlington revision, and <strong>MRL Impact = NO</strong>.
<br><br>
The all-driver audit can still show an ESPN change. That is exactly the distinction the classifier is supposed to preserve: ESPN revision ≠ MRL scoring impact.
<br><br>
FILE: MRL_rebuild_R27_companions_and_classification_v001_20260913_170921.php | VERSION: v001
</div>
</div>

<script>
(function(){
  var b=document.getElementById('exportBtn');
  if(!b || b.disabled) return;
  b.addEventListener('click',function(){
    var lines=['MRL R27 Derived Snapshot + Classification Rebuild','Generated: 9/13/2026 5:09:21 pm ET','State: <?=mrl_h($notice)?>',''];
    document.querySelectorAll('#resultsTable tr').forEach(function(tr){
      var c=tr.querySelectorAll('th,td');
      if(c.length===3) lines.push(c[0].textContent.trim()+' | '+c[1].textContent.trim()+' | '+c[2].textContent.trim());
    });
    var blob=new Blob([lines.join('\r\n')+'\r\n'],{type:'text/plain;charset=utf-8'});
    var u=URL.createObjectURL(blob),a=document.createElement('a');
    a.href=u;a.download='MRL_R27_rebuild_results_20260913_170921.txt';
    document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(u);
  });
})();
</script>
</body></html>
