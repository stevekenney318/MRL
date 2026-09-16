<?php
declare(strict_types=1);

/**
 * MRL_scoring_edge_case_diagnostic.php
 * VERSION: v001
 * GENERATED: 9/13/2026 3:16:42 pm ET
 *
 * READ ONLY:
 * - SELECT/read-only DB access only.
 * - No DB writes.
 * - No snapshot/scheduler/standings/pick file writes.
 *
 * Defaults: 2026 S4, R27/R28.
 * Optional: ?year=2026&segment=S4&races=27,28
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions_mrl.php';

if (function_exists('disableCaching')) { disableCaching(); }
date_default_timezone_set('America/New_York');

$year = isset($_GET['year']) ? (int)$_GET['year'] : 2026;
$segment = isset($_GET['segment']) ? strtoupper(trim((string)$_GET['segment'])) : 'S4';
if (!preg_match('/^S[1-4]$/', $segment)) { $segment = 'S4'; }

$raceInput = isset($_GET['races']) ? (string)$_GET['races'] : '27,28';
$races = [];
foreach (explode(',', $raceInput) as $piece) {
    $n = (int)trim($piece);
    if ($n >= 1 && $n <= 99) { $races[$n] = $n; }
}
$races = array_values($races);
sort($races, SORT_NUMERIC);
if (!$races) { $races = [27, 28]; }

function diag_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function diag_driver_values(array $row): array {
    $out = [];
    foreach (['driverA','driverB','driverC','driverD'] as $k) {
        $v = trim((string)($row[$k] ?? ''));
        if ($v !== '') { $out[$k] = $v; }
    }
    return $out;
}
function diag_pick_type(array $row): string {
    $t = strtoupper(trim((string)($row['pick_type'] ?? 'SEG')));
    return $t !== '' ? $t : 'SEG';
}
function diag_effective_race(array $row): int {
    return (int)($row['effective_race'] ?? 0);
}
function diag_row_sort_key(array $row): string {
    return
        str_pad((string)diag_effective_race($row), 3, '0', STR_PAD_LEFT) . '|' .
        trim((string)($row['entryDate'] ?? '')) . '|' .
        str_pad((string)(int)($row['pickID'] ?? 0), 12, '0', STR_PAD_LEFT);
}
function diag_resolve_team_row(array $rows, int $raceNumber): ?array {
    if (!$rows) { return null; }

    usort($rows, function ($a, $b) {
        return strcmp(diag_row_sort_key($a), diag_row_sort_key($b));
    });

    $base = null;
    foreach ($rows as $row) {
        if (diag_pick_type($row) === 'SEG') { $base = $row; }
    }

    $specials = [];
    foreach ($rows as $row) {
        $type = diag_pick_type($row);
        if ($type !== 'LP' && $type !== 'RD') { continue; }
        $eff = diag_effective_race($row);
        if ($eff > 0 && $eff <= $raceNumber) { $specials[] = $row; }
    }

    if ($specials) {
        usort($specials, function ($a, $b) {
            return strcmp(diag_row_sort_key($a), diag_row_sort_key($b));
        });
        return $specials[count($specials) - 1];
    }

    return $base;
}
function diag_query(string $sql, array $params = []): array {
    global $dbo, $dbconnect;

    if (isset($dbo) && $dbo instanceof PDO) {
        $stmt = $dbo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    if (isset($dbconnect) && $dbconnect instanceof mysqli) {
        foreach ($params as $key => $value) {
            $quoted = (is_int($value) || ctype_digit((string)$value))
                ? (string)(int)$value
                : "'" . $dbconnect->real_escape_string((string)$value) . "'";
            $sql = str_replace($key, $quoted, $sql);
        }
        $res = $dbconnect->query($sql);
        if (!$res) { throw new RuntimeException('Query failed: ' . $dbconnect->error); }
        $rows = [];
        while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        return $rows;
    }

    throw new RuntimeException('No supported DB connection found ($dbo PDO or $dbconnect mysqli).');
}

$error = '';
$rosterRows = [];
$pickRows = [];

try {
    $rosterRows = diag_query(
        "SELECT ut.userID, ut.teamName, COALESCE(u.userName, '') AS userName
         FROM user_teams ut
         LEFT JOIN users u ON u.userID = ut.userID
         WHERE ut.raceYear = :year
         ORDER BY ut.teamName ASC, ut.userID ASC",
        [':year' => $year]
    );

    $pickRows = diag_query(
        "SELECT
            up.pickID, up.userID, up.teamName, up.raceYear, up.segment,
            up.pick_type, up.effective_race, up.supersedes_pickID,
            up.driverA, up.driverB, up.driverC, up.driverD, up.entryDate
         FROM user_picks up
         WHERE up.raceYear = :year
           AND up.segment = :segment
         ORDER BY up.teamName ASC, up.effective_race ASC, up.entryDate ASC, up.pickID ASC",
        [':year' => $year, ':segment' => $segment]
    );
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$competitiveRoster = [];
$excludedRoster = [];
foreach ($rosterRows as $row) {
    $uid = (int)($row['userID'] ?? 0);
    $team = trim((string)($row['teamName'] ?? ''));
    if ($team === '') { continue; }
    if ($uid === 0 || $uid === 999) { $excludedRoster[$team] = $row; }
    else { $competitiveRoster[$team] = $row; }
}
uksort($competitiveRoster, 'strnatcasecmp');
uksort($excludedRoster, 'strnatcasecmp');

$picksByTeam = [];
$testPickRows = [];
$competitivePickRows = [];
foreach ($pickRows as $row) {
    $uid = (int)($row['userID'] ?? 0);
    $team = trim((string)($row['teamName'] ?? ''));
    if ($team === '') { continue; }
    if ($uid === 0 || $uid === 999) { $testPickRows[] = $row; }
    else {
        $competitivePickRows[] = $row;
        $picksByTeam[$team][] = $row;
    }
}

$driverSourcesAll = [];
$driverSourcesCompetitive = [];
foreach ($pickRows as $row) {
    $uid = (int)($row['userID'] ?? 0);
    $isTest = ($uid === 0 || $uid === 999);
    foreach (diag_driver_values($row) as $slot => $driver) {
        $source = [
            'driver'=>$driver, 'slot'=>$slot, 'userID'=>$uid,
            'teamName'=>(string)($row['teamName'] ?? ''),
            'pickID'=>(int)($row['pickID'] ?? 0),
            'pick_type'=>diag_pick_type($row),
            'effective_race'=>diag_effective_race($row),
            'entryDate'=>(string)($row['entryDate'] ?? ''),
            'test'=>$isTest ? 'YES' : 'NO'
        ];
        $driverSourcesAll[$driver][] = $source;
        if (!$isTest) { $driverSourcesCompetitive[$driver][] = $source; }
    }
}
uksort($driverSourcesAll, 'strnatcasecmp');
uksort($driverSourcesCompetitive, 'strnatcasecmp');

$raceReports = [];
foreach ($races as $raceNumber) {
    $resolved = [];
    $missing = [];
    $driverMap = [];

    foreach ($competitiveRoster as $team => $rosterRow) {
        $row = diag_resolve_team_row($picksByTeam[$team] ?? [], $raceNumber);
        if ($row === null) {
            $missing[$team] = $rosterRow;
            continue;
        }
        $resolved[$team] = $row;
        foreach (diag_driver_values($row) as $slot => $driver) {
            $driverMap[$driver][] = [
                'teamName'=>$team, 'slot'=>$slot,
                'pickID'=>(int)($row['pickID'] ?? 0),
                'pick_type'=>diag_pick_type($row),
                'effective_race'=>diag_effective_race($row)
            ];
        }
    }

    uksort($resolved, 'strnatcasecmp');
    uksort($missing, 'strnatcasecmp');
    uksort($driverMap, 'strnatcasecmp');

    $raceReports[$raceNumber] = [
        'resolved'=>$resolved,
        'missing'=>$missing,
        'drivers'=>$driverMap
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Scoring Edge-Case Diagnostic v001</title>
<style>
:root{color-scheme:dark;--bg:#111;--panel:#1c1c1c;--line:#3c3c3c;--text:#eee;--muted:#aaa;--ok:#56f0a5;--warn:#ffd166;--bad:#ff6b6b;--blue:#61c8ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px/1.45 system-ui,-apple-system,Segoe UI,Arial,sans-serif}
.wrap{max-width:1500px;margin:0 auto;padding:18px}h1{margin:0 0 4px;color:#ffcc66;font-size:26px}h2{margin:0 0 12px;color:#ffcc66;font-size:20px}h3{margin:18px 0 8px;color:#ddd;font-size:16px}
.panel{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px;margin:14px 0}.meta,.small{color:var(--muted)}.small{font-size:13px}
.ok{color:var(--ok);font-weight:700}.warn{color:var(--warn);font-weight:700}.bad{color:var(--bad);font-weight:700}.blue{color:var(--blue);font-weight:700}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px;margin:10px 0}.stat{background:#161616;border:1px solid #333;border-radius:9px;padding:10px}.stat b{display:block;font-size:22px}
table{width:100%;border-collapse:collapse;margin:8px 0 14px}th,td{border-bottom:1px solid #383838;padding:7px 8px;text-align:left;vertical-align:top}th{color:#ffcc66;background:#191919}
.tag{display:inline-block;border:1px solid #a66;border-radius:999px;padding:1px 7px;margin-left:5px;font-size:12px;color:#ffb3b3}
pre{white-space:pre-wrap;background:#151515;border:1px solid #333;border-radius:8px;padding:10px}details{margin:8px 0}summary{cursor:pointer;color:#bfe6ff;font-weight:700}
</style>
</head>
<body><div class="wrap">
<div class="panel">
<h1>MRL Scoring Edge-Case Diagnostic v001</h1>
<div class="meta">Generated 9/13/2026 3:16:42 pm ET · READ ONLY · No DB writes · No file writes</div>
<div class="meta">Scope: <?=diag_h($year)?> <?=diag_h($segment)?> · races <?=diag_h(implode(', ', array_map(fn($n) => 'R'.str_pad((string)$n,2,'0',STR_PAD_LEFT), $races)))?></div>
</div>

<?php if ($error !== ''): ?>
<div class="panel"><h2 class="bad">Diagnostic failed</h2><pre><?=diag_h($error)?></pre></div>
<?php else: ?>

<div class="panel">
<h2>1. Authoritative-roster evidence</h2>
<div class="grid">
<div class="stat"><span>user_teams rows</span><b><?=count($rosterRows)?></b></div>
<div class="stat"><span>Competitive roster (excluding 0 / 999)</span><b class="ok"><?=count($competitiveRoster)?></b></div>
<div class="stat"><span>Excluded test roster rows</span><b><?=count($excludedRoster)?></b></div>
<div class="stat"><span><?=diag_h($segment)?> user_picks rows</span><b><?=count($pickRows)?></b></div>
</div>
<details open><summary>Competitive roster</summary>
<table><thead><tr><th>User ID</th><th>Team</th><th>User</th><th><?=diag_h($segment)?> pick rows</th></tr></thead><tbody>
<?php foreach ($competitiveRoster as $team => $row): ?>
<tr><td><?=diag_h($row['userID'] ?? '')?></td><td><?=diag_h($team)?></td><td><?=diag_h($row['userName'] ?? '')?></td><td><?=count($picksByTeam[$team] ?? [])?></td></tr>
<?php endforeach; ?>
</tbody></table></details>
</div>

<div class="panel">
<h2>2. Race-effective team resolution</h2>
<p class="small">Compares the competitive roster with teams that actually have a pick row effective for the selected race.</p>
<?php foreach ($raceReports as $raceNumber => $report): ?>
<h3>R<?=str_pad((string)$raceNumber,2,'0',STR_PAD_LEFT)?></h3>
<div class="grid">
<div class="stat"><span>Expected competitive teams</span><b><?=count($competitiveRoster)?></b></div>
<div class="stat"><span>Teams with effective picks</span><b><?=count($report['resolved'])?></b></div>
<div class="stat"><span>Roster teams with no effective picks</span><b class="<?=count($report['missing']) ? 'warn' : 'ok'?>"><?=count($report['missing'])?></b></div>
<div class="stat"><span>Distinct effective drivers</span><b><?=count($report['drivers'])?></b></div>
</div>

<?php if ($report['missing']): ?>
<div class="warn">Roster teams that must not disappear from standings:</div>
<table><thead><tr><th>Team</th><th>User ID</th><th>Expected weekly treatment</th></tr></thead><tbody>
<?php foreach ($report['missing'] as $team => $row): ?>
<tr><td><?=diag_h($team)?></td><td><?=diag_h($row['userID'] ?? '')?></td><td>Team row = 0 points; driver detail = N/A / no effective picks</td></tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>

<details><summary>Resolved team pick rows</summary>
<table><thead><tr><th>Team</th><th>User ID</th><th>Pick ID</th><th>Type</th><th>Effective</th><th>Drivers</th><th>Entry</th></tr></thead><tbody>
<?php foreach ($report['resolved'] as $team => $row): ?>
<tr>
<td><?=diag_h($team)?></td><td><?=diag_h($row['userID'] ?? '')?></td><td><?=diag_h($row['pickID'] ?? '')?></td>
<td><?=diag_h(diag_pick_type($row))?></td><td>R<?=str_pad((string)diag_effective_race($row),2,'0',STR_PAD_LEFT)?></td>
<td><?=diag_h(implode(' · ', array_values(diag_driver_values($row))))?></td><td><?=diag_h($row['entryDate'] ?? '')?></td>
</tr>
<?php endforeach; ?>
</tbody></table></details>

<details><summary>Distinct effective driver sources</summary>
<table><thead><tr><th>Driver</th><th>Source teams</th></tr></thead><tbody>
<?php foreach ($report['drivers'] as $driver => $sources): ?>
<tr><td><?=diag_h($driver)?></td><td><?php
$bits=[]; foreach($sources as $s){$bits[]=$s['teamName'].' ['.$s['pick_type'].' R'.str_pad((string)$s['effective_race'],2,'0',STR_PAD_LEFT).' / pick '.$s['pickID'].']';}
echo diag_h(implode('; ',$bits)); ?></td></tr>
<?php endforeach; ?>
</tbody></table></details>
<?php endforeach; ?>
</div>

<div class="panel">
<h2>3. Raw segment driver-pool contamination check</h2>
<div class="grid">
<div class="stat"><span>Distinct drivers from ALL <?=diag_h($segment)?> user_picks rows</span><b><?=count($driverSourcesAll)?></b></div>
<div class="stat"><span>Distinct drivers excluding userID 0 / 999</span><b class="blue"><?=count($driverSourcesCompetitive)?></b></div>
<div class="stat"><span>Test pick rows (0 / 999)</span><b class="<?=count($testPickRows) ? 'warn' : 'ok'?>"><?=count($testPickRows)?></b></div>
<div class="stat"><span>Competitive pick rows</span><b><?=count($competitivePickRows)?></b></div>
</div>
<p class="small">If ALL = 22 and competitive-only = 21, the test-team leak is proven. If both = 22, the source map identifies the real row that introduced the extra driver.</p>

<details open><summary>All distinct drivers and every source row</summary>
<table><thead><tr><th>Driver</th><th>Source rows</th></tr></thead><tbody>
<?php foreach ($driverSourcesAll as $driver => $sources): ?>
<?php $hasTest=false; foreach($sources as $s){if($s['test']==='YES'){$hasTest=true;break;}} ?>
<tr><td><?=diag_h($driver)?><?=$hasTest?'<span class="tag">TEST SOURCE</span>':''?></td><td><?php
$bits=[]; foreach($sources as $s){$bits[]=$s['teamName'].' (uid '.$s['userID'].', pick '.$s['pickID'].', '.$s['pick_type'].', eff R'.str_pad((string)$s['effective_race'],2,'0',STR_PAD_LEFT).', '.$s['slot'].', '.$s['entryDate'].($s['test']==='YES'?', TEST':'').')';}
echo diag_h(implode('; ',$bits)); ?></td></tr>
<?php endforeach; ?>
</tbody></table></details>

<?php if ($testPickRows): ?>
<details open><summary>userID 0 / 999 pick rows</summary>
<table><thead><tr><th>User ID</th><th>Team</th><th>Pick ID</th><th>Type</th><th>Effective</th><th>Drivers</th><th>Entry</th></tr></thead><tbody>
<?php foreach($testPickRows as $row): ?>
<tr><td><?=diag_h($row['userID']??'')?></td><td><?=diag_h($row['teamName']??'')?></td><td><?=diag_h($row['pickID']??'')?></td><td><?=diag_h(diag_pick_type($row))?></td><td><?=diag_h($row['effective_race']??'')?></td><td><?=diag_h(implode(' · ',array_values(diag_driver_values($row))))?></td><td><?=diag_h($row['entryDate']??'')?></td></tr>
<?php endforeach; ?>
</tbody></table></details>
<?php endif; ?>
</div>

<div class="panel">
<h2>4. What this proves</h2>
<p><strong>Issue 1:</strong> exactly which database row(s) introduced unexpected drivers into the segment pool, including test-team activity.</p>
<p><strong>Issue 2:</strong> the difference between the authoritative competitive roster and teams with race-effective picks. A legitimate roster team with no effective picks must remain in standings at 0.</p>
<p class="ok">No changes have been made by this page.</p>
</div>

<?php endif; ?>
</div></body></html>
