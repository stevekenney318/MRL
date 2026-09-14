<?php
declare(strict_types=1);

/**
 * MRL installer
 * TITLE: Weekly Standings competitive-roster zero-row fix
 * INSTALLER VERSION: v001
 * GENERATED: 9/13/2026 3:35:57 pm ET
 *
 * TARGET:
 *   /race_results/weekly_standings.php
 *   v069 -> v070
 *
 * PURPOSE:
 * - Keep every legitimate competitive yearly roster team in Weekly Standings,
 *   even when that team has no race-effective picks.
 * - Preserve 0-point weekly/segment/season rows instead of dropping the team.
 * - Exclude userID 0 / 999 and "MRL test team".
 * - Exclude stale user_teams rows with no actual user_picks participation
 *   anywhere in that race year.
 * - Preserve pre-2026 historical behavior.
 *
 * SAFETY:
 * - No database writes.
 * - Exact v069 + anchor preflight.
 * - Timestamped backup.
 * - Temp-file write + PHP lint when available.
 * - Atomic rename.
 * - Rollback button restores this installer's backup.
 */

date_default_timezone_set('America/New_York');

$targetRel = '/race_results/weekly_standings.php';
$target = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . $targetRel;
$backup = dirname($target) . '/weekly_standings.php.pre_v070_20260913_153557.bak';
$temp = dirname($target) . '/.weekly_standings_v070_20260913_153557.tmp.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function exact_replace_once(string $source, string $old, string $new, string $label): string {
    $count = substr_count($source, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ': expected exact anchor count 1, found ' . $count . '.');
    }
    return str_replace($old, $new, $source);
}

function build_v070(string $source): string {
    $headerOld = <<<'TXT'
 * VERSION: v069
 * LAST MODIFIED: 8/29/2026 10:30:38 am
 *
 * CHANGELOG:
 *
 * v069 (9/13/2026 6:34:43 am ET)
TXT;

    $headerNew = <<<'TXT'
 * VERSION: v070
 * LAST MODIFIED: 9/13/2026 3:35:57 pm ET
 *
 * CHANGELOG:
 *
 * v070 (9/13/2026 3:35:57 pm ET)
 *   - FIX: Weekly Standings now begins 2026+ race scoring from the competitive yearly roster so a legitimate team with no race-effective picks remains present as a 0-point row.
 *   - FIX: Competitive roster authority excludes userID 0 / 999, "MRL test team", and stale user_teams rows with no actual user_picks participation in that race year.
 *   - FIX: Roster completion occurs before weekly scoring, so expected competitive teams, teams loaded, and weekly rows generated stay aligned.
 *   - PRESERVE: LP/RD effective-race behavior, scoring values, snapshots, release history, exports, print, and pre-2026 historical behavior are unchanged.
 *
 * v069 (9/13/2026 6:34:43 am ET)
TXT;
    $headerNew = str_replace('9/13/2026 3:35:57 pm ET', '__STAMP_HUMAN_REAL__', $headerNew);
    $source = exact_replace_once($source, $headerOld, $headerNew, 'Header/version');

    $rosterOld = <<<'TXT'
function rrsg_get_year_team_roster(string $raceYear, $dbo): array
{
    if (!($dbo instanceof PDO)) {
        return [];
    }

    $sql = "
        SELECT
            ut.userID,
            ut.teamName,
            COALESCE(u.userName, '') AS userName
        FROM user_teams ut
        LEFT JOIN users u ON u.userID = ut.userID
        WHERE ut.raceYear = :raceYear
        ORDER BY ut.teamName ASC, ut.userID ASC
    ";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':raceYear' => $raceYear,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
        return [];
    }

    $roster = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $teamName = trim((string)($row['teamName'] ?? ''));
        if ($teamName === '') {
            continue;
        }

        $roster[$teamName] = [
            'userID' => (int)($row['userID'] ?? 0),
            'teamName' => $teamName,
            'userName' => (string)($row['userName'] ?? ''),
        ];
    }

    ksort($roster, SORT_NATURAL | SORT_FLAG_CASE);
    return $roster;
}
TXT;

    $rosterNew = <<<'TXT'
function rrsg_get_year_team_roster(string $raceYear, $dbo): array
{
    if (!($dbo instanceof PDO)) {
        return [];
    }

    $sql = "
        SELECT
            ut.userID,
            ut.teamName,
            COALESCE(u.userName, '') AS userName
        FROM user_teams ut
        LEFT JOIN users u ON u.userID = ut.userID
        WHERE ut.raceYear = :raceYear
          AND ut.userID NOT IN (0, 999)
          AND LOWER(TRIM(ut.teamName)) <> 'mrl test team'
          AND EXISTS (
              SELECT 1
              FROM user_picks up_active
              WHERE up_active.raceYear = ut.raceYear
                AND up_active.userID = ut.userID
                AND up_active.userID NOT IN (0, 999)
          )
        ORDER BY ut.teamName ASC, ut.userID ASC
    ";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':raceYear' => $raceYear,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
        return [];
    }

    $roster = [];
    foreach ($rows as $row) {
        if (!is_array($row) || rrsg_is_noncompetitive_test_team($row)) {
            continue;
        }

        $teamName = trim((string)($row['teamName'] ?? ''));
        if ($teamName === '') {
            continue;
        }

        $roster[$teamName] = [
            'userID' => (int)($row['userID'] ?? 0),
            'teamName' => $teamName,
            'userName' => (string)($row['userName'] ?? ''),
        ];
    }

    ksort($roster, SORT_NATURAL | SORT_FLAG_CASE);
    return $roster;
}

function rrsg_append_missing_competitive_team_rows(array $teamRows, array $roster): array
{
    if (empty($roster)) {
        return $teamRows;
    }

    $seenTeams = [];
    foreach ($teamRows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $teamName = strtolower(trim((string)($row['teamName'] ?? '')));
        if ($teamName !== '') {
            $seenTeams[$teamName] = true;
        }
    }

    foreach ($roster as $teamName => $rosterRow) {
        $teamKey = strtolower(trim((string)$teamName));
        if ($teamKey === '' || isset($seenTeams[$teamKey])) {
            continue;
        }

        $teamRows[] = [
            'userID' => (int)($rosterRow['userID'] ?? 0),
            'teamName' => (string)$teamName,
            'userName' => (string)($rosterRow['userName'] ?? ''),
            'driverA' => '',
            'driverB' => '',
            'driverC' => '',
            'driverD' => '',
            'pick_type' => 'MISS',
            'effective_race' => 0,
            'original_driverA' => '',
            'original_driverB' => '',
            'original_driverC' => '',
            'original_driverD' => '',
        ];
    }

    usort($teamRows, function ($a, $b) {
        return strcasecmp((string)($a['teamName'] ?? ''), (string)($b['teamName'] ?? ''));
    });

    return $teamRows;
}
TXT;

    $source = exact_replace_once($source, $rosterOld, $rosterNew, 'Competitive roster helper');

    $breakdownOld = <<<'TXT'
    $rows = [];
    $racesAscending = $pointRaces;

    usort($racesAscending, function ($a, $b) {
TXT;

    $breakdownNew = <<<'TXT'
    $rows = [];
    $racesAscending = $pointRaces;
    $competitiveRoster = ((int)$selectedYear >= 2026)
        ? rrsg_get_year_team_roster($selectedYear, $dbo ?? null)
        : [];

    usort($racesAscending, function ($a, $b) {
TXT;

    $source = exact_replace_once($source, $breakdownOld, $breakdownNew, 'Segment breakdown roster load');

    $breakdownRowsOld = <<<'TXT'
        $raceTeamRowsBase = rr_get_segment_team_picks($dbo ?? null, $dbconnect ?? null, $selectedYear, $scoreSegment);
        $raceTeamRowsSpecial = rrsg_special_pick_rows($selectedYear, $scoreSegment, $dbo ?? null);
        $raceTeamRows = rrsg_overlay_special_rows_for_race($raceTeamRowsBase, $raceTeamRowsSpecial, $raceNumber, $scoreSegment);
        $snapshotFile = rrsg_find_snapshot_file((string)$race['raceFolder']);
TXT;

    $breakdownRowsNew = <<<'TXT'
        $raceTeamRowsBase = rr_get_segment_team_picks($dbo ?? null, $dbconnect ?? null, $selectedYear, $scoreSegment);
        $raceTeamRowsSpecial = rrsg_special_pick_rows($selectedYear, $scoreSegment, $dbo ?? null);
        $raceTeamRows = rrsg_overlay_special_rows_for_race($raceTeamRowsBase, $raceTeamRowsSpecial, $raceNumber, $scoreSegment);
        $raceTeamRows = rrsg_append_missing_competitive_team_rows($raceTeamRows, $competitiveRoster);
        $snapshotFile = rrsg_find_snapshot_file((string)$race['raceFolder']);
TXT;

    $source = exact_replace_once($source, $breakdownRowsOld, $breakdownRowsNew, 'Segment breakdown roster completion');

    $mainTeamOld = <<<'TXT'
$teamRowsBase = rr_get_segment_team_picks($dbo ?? null, $dbconnect ?? null, $scoreYear, $scoreSegment);
$teamRowsSpecial = rrsg_special_pick_rows($scoreYear, $scoreSegment, $dbo ?? null);
$teamRows = rrsg_overlay_special_rows_for_race($teamRowsBase, $teamRowsSpecial, $selectedRaceNumber, $scoreSegment);

$segmentTotals = [];
TXT;

    $mainTeamNew = <<<'TXT'
$competitiveRoster = ((int)$scoreYear >= 2026)
    ? rrsg_get_year_team_roster($scoreYear, $dbo ?? null)
    : [];

$teamRowsBase = rr_get_segment_team_picks($dbo ?? null, $dbconnect ?? null, $scoreYear, $scoreSegment);
$teamRowsSpecial = rrsg_special_pick_rows($scoreYear, $scoreSegment, $dbo ?? null);
$teamRows = rrsg_overlay_special_rows_for_race($teamRowsBase, $teamRowsSpecial, $selectedRaceNumber, $scoreSegment);
$teamRows = rrsg_append_missing_competitive_team_rows($teamRows, $competitiveRoster);

$segmentTotals = [];
TXT;

    $source = exact_replace_once($source, $mainTeamOld, $mainTeamNew, 'Selected-race roster completion');

    $loopRowsOld = <<<'TXT'
        $raceTeamRowsBase = rr_get_segment_team_picks($dbo ?? null, $dbconnect ?? null, $selectedYear, $raceSegment);
        $raceTeamRowsSpecial = rrsg_special_pick_rows($selectedYear, $raceSegment, $dbo ?? null);
        $raceTeamRows = rrsg_overlay_special_rows_for_race($raceTeamRowsBase, $raceTeamRowsSpecial, $raceNumber, $raceSegment);

        $snapshotFile = rrsg_find_snapshot_file($raceFolder);
TXT;

    $loopRowsNew = <<<'TXT'
        $raceTeamRowsBase = rr_get_segment_team_picks($dbo ?? null, $dbconnect ?? null, $selectedYear, $raceSegment);
        $raceTeamRowsSpecial = rrsg_special_pick_rows($selectedYear, $raceSegment, $dbo ?? null);
        $raceTeamRows = rrsg_overlay_special_rows_for_race($raceTeamRowsBase, $raceTeamRowsSpecial, $raceNumber, $raceSegment);
        $raceTeamRows = rrsg_append_missing_competitive_team_rows($raceTeamRows, $competitiveRoster);

        $snapshotFile = rrsg_find_snapshot_file($raceFolder);
TXT;

    $source = exact_replace_once($source, $loopRowsOld, $loopRowsNew, 'Race-loop roster completion');

    $validationOld = <<<'TXT'
    if (count($teamRows) > 0) {
        rrsg_add_validation($validation, 'pass', 'Teams loaded: ' . count($teamRows));
    } else {
        rrsg_add_validation($validation, 'fail', 'No teams loaded for selected segment.');
    }

    if (!empty($selectedRaceWeeklyRows)) {
        rrsg_add_validation($validation, 'pass', 'Weekly rows generated: ' . count($selectedRaceWeeklyRows));
    } else {
        rrsg_add_validation($validation, 'fail', 'No weekly rows generated for selected race.');
    }
TXT;

    $validationNew = <<<'TXT'
    $expectedCompetitiveTeams = count($competitiveRoster);

    if ($expectedCompetitiveTeams > 0) {
        rrsg_add_validation($validation, 'pass', 'Expected competitive teams: ' . $expectedCompetitiveTeams);

        if (count($teamRows) === $expectedCompetitiveTeams) {
            rrsg_add_validation($validation, 'pass', 'Teams loaded: ' . count($teamRows));
        } else {
            rrsg_add_validation(
                $validation,
                'fail',
                'Teams loaded mismatch: expected ' . $expectedCompetitiveTeams . ', got ' . count($teamRows) . '.'
            );
        }

        if (count($selectedRaceWeeklyRows) === $expectedCompetitiveTeams) {
            rrsg_add_validation($validation, 'pass', 'Weekly rows generated: ' . count($selectedRaceWeeklyRows));
        } else {
            rrsg_add_validation(
                $validation,
                'fail',
                'Weekly rows mismatch: expected ' . $expectedCompetitiveTeams . ', got ' . count($selectedRaceWeeklyRows) . '.'
            );
        }
    } else {
        if (count($teamRows) > 0) {
            rrsg_add_validation($validation, 'pass', 'Teams loaded: ' . count($teamRows));
        } else {
            rrsg_add_validation($validation, 'fail', 'No teams loaded for selected segment.');
        }

        if (!empty($selectedRaceWeeklyRows)) {
            rrsg_add_validation($validation, 'pass', 'Weekly rows generated: ' . count($selectedRaceWeeklyRows));
        } else {
            rrsg_add_validation($validation, 'fail', 'No weekly rows generated for selected race.');
        }
    }
TXT;

    $source = exact_replace_once($source, $validationOld, $validationNew, 'Validation counts');

    return $source;
}

function php_lint_file(string $path): array {
    $result = ['ok' => true, 'message' => 'PHP lint not available; skipped.'];

    if (!function_exists('exec')) {
        return $result;
    }

    $binary = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($binary) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines = [];
    $code = 0;
    @exec($cmd, $lines, $code);

    if ($code === 0) {
        return ['ok' => true, 'message' => implode("\n", $lines)];
    }

    return ['ok' => false, 'message' => implode("\n", $lines)];
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : 'preview';
$messages = [];
$success = false;
$failure = false;

try {
    if (!is_file($target)) {
        throw new RuntimeException('Target not found: ' . $targetRel);
    }

    $source = file_get_contents($target);
    if (!is_string($source)) {
        throw new RuntimeException('Unable to read target.');
    }

    $preflight = [];
    $preflight['Target exists'] = true;
    $preflight['Current version is v069'] =
        strpos($source, '* VERSION: v069') !== false &&
        strpos($source, '* VERSION: v070') === false;
    $preflight['Competitive roster helper anchor'] =
        substr_count($source, 'function rrsg_get_year_team_roster(string $raceYear, $dbo): array') === 1;
    $preflight['Segment breakdown anchor'] =
        substr_count($source, '$raceTeamRows = rrsg_overlay_special_rows_for_race($raceTeamRowsBase, $raceTeamRowsSpecial, $raceNumber, $scoreSegment);') === 1;
    $preflight['Main selected-team anchor'] =
        substr_count($source, '$teamRows = rrsg_overlay_special_rows_for_race($teamRowsBase, $teamRowsSpecial, $selectedRaceNumber, $scoreSegment);') === 1;
    $preflight['Race-loop anchor'] =
        substr_count($source, '$raceTeamRows = rrsg_overlay_special_rows_for_race($raceTeamRowsBase, $raceTeamRowsSpecial, $raceNumber, $raceSegment);') === 1;

    foreach ($preflight as $label => $ok) {
        $messages[] = [$ok ? 'ok' : 'bad', $label . ': ' . ($ok ? 'PASS' : 'FAIL')];
        if (!$ok) {
            $failure = true;
        }
    }

    if ($failure) {
        throw new RuntimeException('Preflight failed. Nothing changed.');
    }

    $newSource = build_v070($source);
    $newSource = str_replace('__STAMP_HUMAN_REAL__', '9/13/2026 3:35:57 pm ET', $newSource);

    if ($action === 'apply') {
        if (is_file($backup)) {
            throw new RuntimeException('Backup already exists for this installer timestamp; refusing to overwrite it.');
        }

        if (!copy($target, $backup)) {
            throw new RuntimeException('Backup creation failed.');
        }
        $messages[] = ['ok', 'Backup created: ' . basename($backup)];

        if (file_put_contents($temp, $newSource, LOCK_EX) === false) {
            @unlink($temp);
            throw new RuntimeException('Unable to write temporary v070 file.');
        }

        $lint = php_lint_file($temp);
        $messages[] = [$lint['ok'] ? 'ok' : 'bad', 'PHP lint: ' . trim((string)$lint['message'])];
        if (!$lint['ok']) {
            @unlink($temp);
            throw new RuntimeException('PHP lint failed; target left unchanged. Backup retained.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Atomic rename failed; target left unchanged. Backup retained.');
        }

        clearstatcache(true, $target);
        $installed = file_get_contents($target);
        if (!is_string($installed) || strpos($installed, '* VERSION: v070') === false) {
            if (is_file($backup)) {
                @copy($backup, $target);
            }
            throw new RuntimeException('Postflight version check failed; backup restored.');
        }

        $success = true;
        $messages[] = ['ok', 'Installed weekly_standings.php v070 successfully.'];
        $messages[] = ['ok', 'No database writes were performed.'];
    } elseif ($action === 'rollback') {
        if (!is_file($backup)) {
            throw new RuntimeException('Rollback backup does not exist: ' . basename($backup));
        }

        if (!copy($backup, $temp)) {
            throw new RuntimeException('Could not stage rollback file.');
        }

        $lint = php_lint_file($temp);
        $messages[] = [$lint['ok'] ? 'ok' : 'bad', 'Rollback PHP lint: ' . trim((string)$lint['message'])];
        if (!$lint['ok']) {
            @unlink($temp);
            throw new RuntimeException('Rollback lint failed; target unchanged.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Rollback rename failed.');
        }

        $success = true;
        $messages[] = ['ok', 'Rollback complete: weekly_standings.php restored to pre-v070 backup.'];
    } else {
        $messages[] = ['ok', 'Preview complete. No files changed.'];
        $messages[] = ['ok', 'Planned change: v069 → v070; competitive roster completion for 2026+ scoring only.'];
        $messages[] = ['ok', 'Expected R27 result after install: 18 competitive → 18 loaded → 18 weekly rows; Over The Edge = 0 points.'];
    }
} catch (Throwable $e) {
    $failure = true;
    $messages[] = ['bad', $e->getMessage()];
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Weekly Roster Fix Installer</title>
<style>
:root{color-scheme:dark;--bg:#111;--panel:#1d1d1d;--line:#3b3b3b;--text:#eee;--muted:#aaa;--ok:#62e6a7;--bad:#ff6f6f;--blue:#69b7ff}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.4 system-ui,-apple-system,Segoe UI,Arial,sans-serif}
.wrap{max-width:900px;margin:0 auto;padding:18px}
.panel{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px;margin-bottom:14px}
h1{font-size:23px;margin:0 0 5px;color:#ffd166}
p{margin:6px 0}.muted{color:var(--muted)}
.msg{padding:6px 8px;border-bottom:1px solid #333}.msg:last-child{border-bottom:0}.ok{color:var(--ok)}.bad{color:var(--bad)}
.actions{display:flex;gap:10px;flex-wrap:wrap}
button{border:0;border-radius:8px;padding:9px 15px;font:inherit;font-weight:700;cursor:pointer}
.preview{background:var(--blue);color:#07131d}.apply{background:#5ee29b;color:#07150d}.rollback{background:#e45d5d;color:#fff}
code{color:#bfe3ff}
.small{font-size:13px;color:var(--muted)}
</style>
</head>
<body><div class="wrap">
<div class="panel">
<h1>Weekly Standings Competitive-Roster Fix</h1>
<p><code>weekly_standings.php v069 → v070</code></p>
<p class="muted">Installer v001 · generated 9/13/2026 3:35:57 pm ET</p>
<p class="small">Read-only DB behavior. This installer changes PHP code only; it does not update standings tables or database records.</p>
</div>

<div class="panel">
<?php foreach ($messages as $m): ?>
<div class="msg <?=h($m[0])?>"><?=h($m[1])?></div>
<?php endforeach; ?>
</div>

<div class="panel actions">
<form method="post"><input type="hidden" name="action" value="preview"><button class="preview" type="submit">Preview / Preflight</button></form>
<form method="post"><input type="hidden" name="action" value="apply"><button class="apply" type="submit">Apply v070</button></form>
<form method="post"><input type="hidden" name="action" value="rollback"><button class="rollback" type="submit">Rollback</button></form>
</div>

<div class="panel small">
<strong>Scope:</strong> only <code>/race_results/weekly_standings.php</code>.<br>
<strong>Key rule:</strong> 2026+ competitive roster = current-year <code>user_teams</code> entry with actual current-year <code>user_picks</code> participation, excluding IDs 0/999 and MRL test team.<br>
<strong>Expected R27:</strong> Over The Edge remains present at 0 points even before an LP row exists.
</div>
</div></body></html>
