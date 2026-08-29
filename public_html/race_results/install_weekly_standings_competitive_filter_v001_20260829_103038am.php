<?php
declare(strict_types=1);

/**
 * install_weekly_standings_competitive_filter_v001.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/29/2026 10:30:38 am
 *
 * TARGET:
 *   /race_results/weekly_standings.php v067 -> v068
 *
 * PURPOSE:
 * - Stop adding dormant yearly user_teams records as manufactured 0-point standings rows.
 * - Permanently exclude the non-competitive "MRL test team" from official scoring output.
 * - Preserve normal participation, legitimate zero-point results, LP/RD overlays,
 *   snapshots, validation, releases, print, spreadsheet, and UI behavior.
 *
 * SAFETY:
 * - Normalizes CRLF/LF before baseline comparison and patching.
 * - Requires the normalized v067 source to match the known GitHub blob exactly.
 * - Creates a timestamped migration backup before replacing production.
 * - Refuses to install unless every patch anchor matches exactly once.
 * - Rolls back automatically if postflight checks fail.
 *
 * LOCATION:
 *   Put this installer in public_html/race_results/
 */

date_default_timezone_set('America/New_York');

const EXPECTED_BASE_GIT_BLOB_SHA1 = 'd514a87e54628643fa3ae5ef515f40d5c8f1f0eb';

$target = __DIR__ . '/weekly_standings.php';

function ih(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function normalized_source(string $s): string
{
    return str_replace(["\r\n", "\r"], "\n", $s);
}

function git_blob_sha1(string $s): string
{
    return sha1('blob ' . strlen($s) . "\0" . $s);
}

function replace_once(string $source, string $old, string $new, string $label, array &$log, bool &$ok): string
{
    $count = substr_count($source, $old);
    if ($count !== 1) {
        $ok = false;
        $log[] = "PATCH FAIL: {$label} — expected 1 anchor, found {$count}.";
        return $source;
    }

    $log[] = "PATCH PASS: {$label}.";
    return str_replace($old, $new, $source);
}

function build_v068(string $source, array &$log, bool &$ok): string
{
    $s = normalized_source($source);

    $s = replace_once(
        $s,
        " * VERSION: v067\n * LAST MODIFIED: 8/28/2026 1:01:11 am\n",
        " * VERSION: v068\n * LAST MODIFIED: 8/29/2026 10:30:38 am\n",
        'version header v067 -> v068',
        $log,
        $ok
    );

    $changelogAnchor = " * CHANGELOG:\n *\n * v067 (8/28/2026 1:01:11 am)\n";
    $changelogNew =
        " * CHANGELOG:\n *\n" .
        " * v068 (8/29/2026 10:30:38 am)\n" .
        " *   - FIX: Removed v055 yearly-roster filler from live standings calculations so user_teams records with no actual participation are not manufactured as 0-point standings rows.\n" .
        " *   - FIX: MRL test team is now explicitly non-competitive and excluded from official scoring rows, including normal SEG picks and LP/RD overlays.\n" .
        " *   - PRESERVE: Legitimate participating teams may still score 0; no score>0 filter was added.\n" .
        " *   - PRESERVE: Existing LP/RD effective-race logic, snapshots, validation, audit, release history, navigation, print, spreadsheet, and UI behavior are unchanged.\n" .
        " *\n" .
        " * v067 (8/28/2026 1:01:11 am)\n";

    $s = replace_once(
        $s,
        $changelogAnchor,
        $changelogNew,
        'v068 changelog',
        $log,
        $ok
    );

    $buildAnchor =
        "function rrsg_build_weekly_rows(array \$teamRows, array \$driverPoints): array\n" .
        "{\n" .
        "    \$weeklyRows = [];\n" .
        "\n" .
        "    foreach (\$teamRows as \$team) {\n";

    $buildNew =
        "function rrsg_is_noncompetitive_test_team(array \$team): bool\n" .
        "{\n" .
        "    \$userId = (int)(\$team['userID'] ?? 0);\n" .
        "    \$teamName = strtolower(trim((string)(\$team['teamName'] ?? '')));\n" .
        "\n" .
        "    // userID 0 is the current legacy test account; 999 is its planned positive-ID replacement.\n" .
        "    if (\$userId === 0 || \$userId === 999) {\n" .
        "        return true;\n" .
        "    }\n" .
        "\n" .
        "    return \$teamName === 'mrl test team';\n" .
        "}\n" .
        "\n" .
        "function rrsg_build_weekly_rows(array \$teamRows, array \$driverPoints): array\n" .
        "{\n" .
        "    \$weeklyRows = [];\n" .
        "\n" .
        "    foreach (\$teamRows as \$team) {\n" .
        "        if (!is_array(\$team) || rrsg_is_noncompetitive_test_team(\$team)) {\n" .
        "            continue;\n" .
        "        }\n";

    $s = replace_once(
        $s,
        $buildAnchor,
        $buildNew,
        'non-competitive test-team guard in weekly-row builder',
        $log,
        $ok
    );

    $overlayBaseAnchor =
        "    foreach (\$baseTeamRows as \$row) {\n" .
        "        if (!is_array(\$row)) {\n" .
        "            continue;\n" .
        "        }\n";

    $overlayBaseNew =
        "    foreach (\$baseTeamRows as \$row) {\n" .
        "        if (!is_array(\$row) || rrsg_is_noncompetitive_test_team(\$row)) {\n" .
        "            continue;\n" .
        "        }\n";

    $s = replace_once(
        $s,
        $overlayBaseAnchor,
        $overlayBaseNew,
        'exclude test team from normal SEG/base rows',
        $log,
        $ok
    );

    $overlaySpecialAnchor =
        "    \$specialByTeam = [];\n" .
        "    foreach (\$specialRows as \$row) {\n" .
        "        if (!is_array(\$row)) {\n" .
        "            continue;\n" .
        "        }\n";

    $overlaySpecialNew =
        "    \$specialByTeam = [];\n" .
        "    foreach (\$specialRows as \$row) {\n" .
        "        if (!is_array(\$row) || rrsg_is_noncompetitive_test_team(\$row)) {\n" .
        "            continue;\n" .
        "        }\n";

    $s = replace_once(
        $s,
        $overlaySpecialAnchor,
        $overlaySpecialNew,
        'exclude test team from LP/RD special rows',
        $log,
        $ok
    );

    $segmentRosterAnchor =
        "): array {\n" .
        "    \$rows = [];\n" .
        "    \$yearRoster = rrsg_get_year_team_roster(\$selectedYear, \$dbo ?? null);\n" .
        "    \$racesAscending = \$pointRaces;\n";

    $segmentRosterNew =
        "): array {\n" .
        "    \$rows = [];\n" .
        "    \$racesAscending = \$pointRaces;\n";

    $s = replace_once(
        $s,
        $segmentRosterAnchor,
        $segmentRosterNew,
        'remove yearly roster load from segment breakdown',
        $log,
        $ok
    );

    $segmentAppendAnchor =
        "        \$driverPoints = rrs_load_snapshot_driver_points(\$snapshotFile);\n" .
        "        \$weeklyRows = rrsg_build_weekly_rows(\$raceTeamRows, \$driverPoints);\n" .
        "        \$weeklyRows = rrsg_append_missing_roster_rows(\$weeklyRows, \$yearRoster);\n" .
        "\n" .
        "        \$rows[] = [\n";

    $segmentAppendNew =
        "        \$driverPoints = rrs_load_snapshot_driver_points(\$snapshotFile);\n" .
        "        \$weeklyRows = rrsg_build_weekly_rows(\$raceTeamRows, \$driverPoints);\n" .
        "\n" .
        "        \$rows[] = [\n";

    $s = replace_once(
        $s,
        $segmentAppendAnchor,
        $segmentAppendNew,
        'remove manufactured 0 rows from segment breakdown',
        $log,
        $ok
    );

    $mainRosterAnchor =
        "\$teamRowsBase = rr_get_segment_team_picks(\$dbo ?? null, \$dbconnect ?? null, \$scoreYear, \$scoreSegment);\n" .
        "\$teamRowsSpecial = rrsg_special_pick_rows(\$scoreYear, \$scoreSegment, \$dbo ?? null);\n" .
        "\$teamRows = rrsg_overlay_special_rows_for_race(\$teamRowsBase, \$teamRowsSpecial, \$selectedRaceNumber, \$scoreSegment);\n" .
        "\$yearRoster = rrsg_get_year_team_roster(\$scoreYear, \$dbo ?? null);\n";

    $mainRosterNew =
        "\$teamRowsBase = rr_get_segment_team_picks(\$dbo ?? null, \$dbconnect ?? null, \$scoreYear, \$scoreSegment);\n" .
        "\$teamRowsSpecial = rrsg_special_pick_rows(\$scoreYear, \$scoreSegment, \$dbo ?? null);\n" .
        "\$teamRows = rrsg_overlay_special_rows_for_race(\$teamRowsBase, \$teamRowsSpecial, \$selectedRaceNumber, \$scoreSegment);\n";

    $s = replace_once(
        $s,
        $mainRosterAnchor,
        $mainRosterNew,
        'remove yearly roster load from main standings',
        $log,
        $ok
    );

    $preseedAnchor =
        "foreach (\$yearRoster as \$teamName => \$rosterRow) {\n" .
        "    \$segmentTotals[(string)\$teamName] = 0;\n" .
        "    \$seasonTotals[(string)\$teamName] = 0;\n" .
        "}\n" .
        "\n";

    $s = replace_once(
        $s,
        $preseedAnchor,
        '',
        'remove dormant-team Segment/Season zero pre-seeding',
        $log,
        $ok
    );

    $mainAppendAnchor =
        "            \$driverPoints = rrs_load_snapshot_driver_points(\$snapshotFile);\n" .
        "            \$weeklyRows = rrsg_build_weekly_rows(\$raceTeamRows, \$driverPoints);\n" .
        "            \$weeklyRows = rrsg_append_missing_roster_rows(\$weeklyRows, \$yearRoster);\n" .
        "            \$winner = rrsg_get_weekly_winner(\$weeklyRows);\n";

    $mainAppendNew =
        "            \$driverPoints = rrs_load_snapshot_driver_points(\$snapshotFile);\n" .
        "            \$weeklyRows = rrsg_build_weekly_rows(\$raceTeamRows, \$driverPoints);\n" .
        "            \$winner = rrsg_get_weekly_winner(\$weeklyRows);\n";

    $s = replace_once(
        $s,
        $mainAppendAnchor,
        $mainAppendNew,
        'remove manufactured 0 rows from main weekly calculations',
        $log,
        $ok
    );

    return $s;
}

$sourceExists = is_file($target);
$source = $sourceExists ? (string)file_get_contents($target) : '';
$normalized = normalized_source($source);
$currentBlob = $sourceExists ? git_blob_sha1($normalized) : '';

$baselineOk =
    $sourceExists
    && $currentBlob === EXPECTED_BASE_GIT_BLOB_SHA1
    && strpos($normalized, ' * VERSION: v067') !== false
    && strpos($normalized, 'rrsg_append_missing_roster_rows') !== false;

$patchLog = [];
$patchOk = true;
$candidate = $sourceExists ? build_v068($source, $patchLog, $patchOk) : '';

$postflightCandidateOk =
    $patchOk
    && strpos($candidate, ' * VERSION: v068') !== false
    && strpos($candidate, 'function rrsg_is_noncompetitive_test_team') !== false
    && strpos($candidate, "\$userId === 0 || \$userId === 999") !== false
    && strpos($candidate, "return \$teamName === 'mrl test team';") !== false
    && strpos($candidate, 'rrsg_append_missing_roster_rows($weeklyRows, $yearRoster);') === false
    && strpos($candidate, 'foreach ($yearRoster as $teamName => $rosterRow)') === false
    && strpos($candidate, '$yearRoster = rrsg_get_year_team_roster($scoreYear') === false
    && strpos($candidate, '$yearRoster = rrsg_get_year_team_roster($selectedYear') === false;

$preflightOk = $baselineOk && $patchOk && $postflightCandidateOk;
$apply = isset($_POST['apply']) && $_POST['apply'] === '1';

$messages = [];
$success = false;

if ($apply && $preflightOk) {
    $backupDir = __DIR__ . '/_migration_backups/weekly_standings_v068_' . date('Ymd_His');
    $ok = is_dir($backupDir) || mkdir($backupDir, 0755, true);

    if (!$ok) {
        $messages[] = 'FAIL: Could not create migration backup directory.';
    }

    $backupPath = $backupDir . '/weekly_standings.php';

    if ($ok && !copy($target, $backupPath)) {
        $ok = false;
        $messages[] = 'FAIL: Could not back up weekly_standings.php v067.';
    } elseif ($ok) {
        $messages[] = 'PASS: Backed up weekly_standings.php v067.';
    }

    if ($ok && file_put_contents($target, $candidate, LOCK_EX) === false) {
        $ok = false;
        $messages[] = 'FAIL: Could not install weekly_standings.php v068.';
    } elseif ($ok) {
        $messages[] = 'PASS: Installed weekly_standings.php v068.';
    }

    if ($ok) {
        $installed = normalized_source((string)file_get_contents($target));

        $checks = [
            'v068 header installed' =>
                strpos($installed, ' * VERSION: v068') !== false,

            'non-competitive test-team helper installed' =>
                strpos($installed, 'function rrsg_is_noncompetitive_test_team') !== false,

            'legacy/current test user IDs excluded' =>
                strpos($installed, '$userId === 0 || $userId === 999') !== false,

            'MRL test team name excluded' =>
                strpos($installed, "return \$teamName === 'mrl test team';") !== false,

            'test team excluded from SEG/base rows' =>
                strpos($installed, 'if (!is_array($row) || rrsg_is_noncompetitive_test_team($row))') !== false,

            'test team excluded from weekly rows' =>
                strpos($installed, 'if (!is_array($team) || rrsg_is_noncompetitive_test_team($team))') !== false,

            'year-roster append call removed everywhere' =>
                strpos($installed, 'rrsg_append_missing_roster_rows($weeklyRows, $yearRoster);') === false,

            'year-roster total pre-seed removed' =>
                strpos($installed, 'foreach ($yearRoster as $teamName => $rosterRow)') === false,

            'main year-roster load removed' =>
                strpos($installed, '$yearRoster = rrsg_get_year_team_roster($scoreYear') === false,

            'segment-breakdown year-roster load removed' =>
                strpos($installed, '$yearRoster = rrsg_get_year_team_roster($selectedYear') === false,

            'LP/RD overlay function retained' =>
                strpos($installed, 'function rrsg_overlay_special_rows_for_race') !== false,

            'weekly winner function retained' =>
                strpos($installed, 'function rrsg_get_weekly_winner') !== false,
        ];

        foreach ($checks as $label => $pass) {
            $messages[] = ($pass ? 'PASS: ' : 'FAIL: ') . $label;
            if (!$pass) {
                $ok = false;
            }
        }
    }

    if (!$ok && is_file($backupPath)) {
        if (copy($backupPath, $target)) {
            $messages[] = 'ROLLBACK: Restored weekly_standings.php v067.';
        } else {
            $messages[] = 'ROLLBACK ERROR: Could not restore weekly_standings.php v067.';
        }
    } else {
        $success = true;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Weekly Standings Competitive Filter v068</title>
<style>
*{box-sizing:border-box}
html{background:#111}
body{margin:0;color:#eee;font-family:Tahoma,Verdana,"Segoe UI",sans-serif}
.wrap{width:94%;max-width:1180px;margin:20px auto}
.card{background:#202020;border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}
h1,h2{color:#efc982}
table{width:100%;border-collapse:collapse}
td{padding:9px;border-bottom:1px solid #444;vertical-align:top}
.ok{color:#61e493;font-weight:bold}
.bad{color:#ff7777;font-weight:bold}
button{padding:11px 20px;background:#1466c9;color:#fff;border:1px solid #5a7fb5;border-radius:9px;font-weight:800;cursor:pointer}
code{color:#76cfff}
li{line-height:1.45;margin-bottom:4px}
.patchlog{font-family:Consolas,monospace;font-size:13px;line-height:1.5}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
    <h1>Weekly Standings v068</h1>
    <p>Competitive-team inclusion fix + permanent MRL test-team exclusion.</p>
</div>

<div class="card">
    <h2>Preflight</h2>
    <table>
        <tr>
            <td>Production file exists</td>
            <td class="<?php echo $sourceExists ? 'ok' : 'bad'; ?>"><?php echo $sourceExists ? 'PASS' : 'FAIL'; ?></td>
            <td><?php echo ih($target); ?></td>
        </tr>
        <tr>
            <td>Normalized v067 source matches known GitHub baseline</td>
            <td class="<?php echo $baselineOk ? 'ok' : 'bad'; ?>"><?php echo $baselineOk ? 'PASS' : 'FAIL'; ?></td>
            <td><?php echo ih($currentBlob); ?></td>
        </tr>
        <tr>
            <td>All v068 patch anchors build cleanly</td>
            <td class="<?php echo ($patchOk && $postflightCandidateOk) ? 'ok' : 'bad'; ?>"><?php echo ($patchOk && $postflightCandidateOk) ? 'PASS' : 'FAIL'; ?></td>
            <td>8 controlled source changes</td>
        </tr>
    </table>

    <div class="patchlog">
        <?php foreach ($patchLog as $line): ?>
            <div><?php echo ih($line); ?></div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($preflightOk): ?>
<div class="card">
    <h2>What v068 changes</h2>
    <ul>
        <li>Stops creating zero-point standings entries merely because a team has a yearly <code>user_teams</code> record.</li>
        <li>A legitimate participating team can still appear with an actual score of <strong>0</strong>; this is not a “hide zero scores” filter.</li>
        <li><strong>MRL test team is always excluded from official scoring output</strong>, including normal picks, LP, and RD rows.</li>
        <li>The test account is recognized as userID <code>0</code> now and userID <code>999</code> after the planned migration, with team-name fallback protection.</li>
        <li>No LP/RD effective-race logic, race snapshots, scoring arithmetic, Weekly Winners, audit/release logic, printing, spreadsheet export, or presentation code is changed.</li>
    </ul>

    <?php if (!$apply): ?>
    <form method="post">
        <input type="hidden" name="apply" value="1">
        <button type="submit">Install Weekly Standings v068</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($apply): ?>
<div class="card">
    <h2>Apply Result</h2>
    <p class="<?php echo $success ? 'ok' : 'bad'; ?>">
        <?php echo $success ? 'SUCCESS' : 'FAILED / ROLLED BACK'; ?>
    </p>
    <ul>
        <?php foreach ($messages as $message): ?>
            <li><?php echo ih($message); ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($success): ?>
        <p><a href="weekly_standings.php" style="color:#76cfff" target="_blank">Open Weekly Standings v068</a></p>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
