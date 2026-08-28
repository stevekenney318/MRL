<?php

declare(strict_types=1);

/**
 * standings_timeline_lite.php
 *
 * VERSION: v005
 * LAST MODIFIED: 8/28/2026 1:56:47 am
 *
 * CHANGELOG:
 * v005 (8/28/2026 1:56:47 am)
 *   - VISUAL MATCH: Corrected the final measured vertical offset; Timeline Lite's yellow table-header band was 6px too high versus weekly_standings.php at the same 1920x971 viewport.
 *   - VISUAL MATCH: Third column in all four tables now uses the same centered alignment as weekly_standings.php instead of Timeline Lite's previous right alignment.
 *   - UI: Removed seconds from the displayed as-of snapshot timestamps in both the top status pill and Table 1 title footnote.
 *   - PRESERVE: Existing table widths, row heights, winner segment colors, subdued as-of pill, unofficial pill, and all timeline logic remain unchanged.
 *   - PRESERVE: No snapshot selection, reconstruction, race navigation, ranking values, weekly-winner values, LP/RD overlay, or timeline calculation logic changes.
 *
 * v004 (8/28/2026 1:40:58 am)
 *   - VISUAL MATCH: Corrected Timeline Lite's vertical report position to match weekly_standings.php exactly at the same viewport.
 *   - VISUAL MATCH: Confirmed/carry-forward exact 1400px wrapper, four equal report columns, 10px gaps, 16px table text, 28px row pitch, 42px rank/week column, and 64px score column used by weekly_standings.php.
 *   - VISUAL MATCH: Snapshot timestamp in Table 1 title now uses the same parenthesized footnote presentation as weekly_standings.php.
 *   - VISUAL MATCH: Weekly Winners now uses the exact S1/S2/S3/S4 segment row colors from weekly_standings.php.
 *   - VISUAL MATCH: Tie ranks/week numbers are bolded to match weekly_standings.php presentation.
 *   - PRESERVE: Subdued Standings as of ... pill and AUTO-SCORING – UNOFFICIAL pill retained unchanged.
 *   - PRESERVE: No snapshot selection, reconstruction, race navigation, ranking values, weekly-winner values, LP/RD overlay, or timeline calculation logic changes.
 *
 * v003 (8/28/2026 1:01:11 am)
 *   - UI: Visually aligned the four standings tables with weekly_standings.php while preserving all existing timeline-lite data behavior.
 *   - UI: Replaced the heavy dark as-of banner with a subdued rounded Standings as of ... status pill.
 *   - UI: Added the approved AUTO-SCORING – UNOFFICIAL pill to the top control row.
 *   - UI: Matched page width, table typography, borders, yellow headers, blue/white striping, title sizing, gaps, and first-three-table column widths to weekly_standings.php.
 *   - PRINT: Added the same print-only AUTO-SCORING – UNOFFICIAL marker used by weekly_standings.php.
 *   - ALIGNMENT: Week / Segment / Season score columns use the same 64px width, keeping tables 1-3 synchronized and allowing two-digit Week labels to fit cleanly.
 *   - PRESERVE: No snapshot selection, reconstruction, race navigation, ranking, weekly-winner, LP/RD overlay, or timeline calculation logic changes.
 *
 * v002 (6/25/2026 4:20:00 pm)
 *   - CHANGE: Locked as-of snapshot remains fixed while the user navigates among race pages that existed at that point in time.
 *   - CHANGE: Top row now stays simple: disabled Live, year, race selector, arrows, and the as-of ID banner.
 *   - CHANGE: Removed Current and Full Timeline action pills from the lite page.
 *   - CHANGE: Weekly Winners table now uses Week / Winner / Points to match weekly_standings.php.
 *
 * v001 (6/25/2026 3:31:00 pm)
 *   - NEW: Public-friendly lite as-of standings page.
 *   - NEW: Reuses standings_timeline.php data-building logic, then renders a weekly_standings-like four-table view.
 *   - NEW: Accepts the same year/snapshot/race query values as standings_timeline.php and also accepts release= as a snapshot alias.
 *
 * Purpose:
 *   Show what weekly standings looked like at one locked as-of snapshot, while allowing race-to-race navigation within that as-of world.
 *
 * PHP: 7.3 compatible.
 */

if (!isset($_GET['snapshot']) && isset($_GET['release'])) {
    $_GET['snapshot'] = (string)$_GET['release'];
}

$timelineFile = __DIR__ . '/standings_timeline.php';
if (!is_file($timelineFile)) {
    http_response_code(500);
    echo 'standings_timeline.php not found.';
    exit;
}

ob_start();
require $timelineFile;
ob_end_clean();

function stlite_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function stlite_compact_snapshot_value(string $value): string
{
    $compact = preg_replace('/^(\d{8}_\d{6}).*_(R\d{2})$/', '$1_$2', $value);
    return is_string($compact) && $compact !== '' ? $compact : $value;
}

function stlite_display_without_seconds(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $trimmed = preg_replace(
        '/^(\d{1,2}\/\d{1,2}\/\d{2,4}\s+\d{1,2}:\d{2}):\d{2}(\s*[ap]m)$/i',
        '$1$2',
        $value
    );

    return is_string($trimmed) && $trimmed !== '' ? $trimmed : $value;
}

function stlite_url(array $params): string
{
    $query = array_merge($_GET, $params);
    foreach ($query as $k => $v) {
        if ($v === null || $v === '') {
            unset($query[$k]);
        }
    }
    return 'standings_timeline_lite.php' . (empty($query) ? '' : '?' . http_build_query($query));
}

function stlite_render_score_table(array $rows, string $scoreKey, string $scoreLabel): void
{
    echo '<table>';
    echo '<thead><tr><th class="col-rank">#</th><th class="team-col">Team</th><th class="col-score">' . stlite_h($scoreLabel) . '</th></tr></thead><tbody>';

    if (empty($rows)) {
        echo '<tr><td colspan="3" class="empty-cell">No rows available.</td></tr>';
    } else {
        $rankCounts = [];
        foreach ($rows as $row) {
            $rankKey = (string)($row['rank'] ?? '');
            if ($rankKey !== '') {
                if (!isset($rankCounts[$rankKey])) {
                    $rankCounts[$rankKey] = 0;
                }
                $rankCounts[$rankKey]++;
            }
        }

        foreach ($rows as $row) {
            $rankText = (string)($row['rank'] ?? '');
            $rankHtml = stlite_h($rankText);
            if ($rankText !== '' && (int)($rankCounts[$rankText] ?? 0) > 1) {
                $rankHtml = '<span class="tie-rank">' . stlite_h($rankText) . '</span>';
            }

            echo '<tr>';
            echo '<td class="col-rank">' . $rankHtml . '</td>';
            echo '<td class="team-col">' . stlite_h((string)($row['team_name'] ?? '')) . '</td>';
            echo '<td class="col-score">' . stlite_h((string)(int)($row[$scoreKey] ?? 0)) . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
}

function stlite_render_weekly_winners_table(array $rows): void
{
    echo '<table>';
    echo '<thead><tr><th class="col-week">Week</th><th class="team-col">Winner</th><th class="col-score">Points</th></tr></thead><tbody>';

    if (empty($rows)) {
        echo '<tr><td colspan="3" class="empty-cell">No weekly winners available.</td></tr>';
    } else {
        $weekCounts = [];
        foreach ($rows as $row) {
            $raceCode = (string)($row['race_code'] ?? '');
            $week = preg_match('/^R(\d+)$/', $raceCode, $m) ? (string)((int)$m[1]) : $raceCode;
            if (!isset($weekCounts[$week])) {
                $weekCounts[$week] = 0;
            }
            $weekCounts[$week]++;
        }

        foreach ($rows as $row) {
            $raceCode = (string)($row['race_code'] ?? '');
            $week = preg_match('/^R(\d+)$/', $raceCode, $m) ? (string)((int)$m[1]) : $raceCode;
            $raceNumber = preg_match('/^R(\d+)$/', $raceCode, $n) ? (int)$n[1] : 0;
            $segment = $raceNumber > 0 ? st_segment_from_race_number($raceNumber) : 'S1';

            $weekHtml = stlite_h($week);
            if ((int)($weekCounts[$week] ?? 0) > 1) {
                $weekHtml = '<span class="tie-rank">' . stlite_h($week) . '</span>';
            }

            echo '<tr class="weekly-winner-segment-row weekly-winner-segment-' . stlite_h($segment) . '">';
            echo '<td class="col-week">' . $weekHtml . '</td>';
            echo '<td class="team-col">' . stlite_h((string)($row['team_name'] ?? '')) . '</td>';
            echo '<td class="col-score">' . stlite_h((string)(int)($row['points'] ?? 0)) . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
}

function stlite_print_button_disabled_attr(): string
{
    return '';
}

$asOfRaceText = trim((string)($asOfRaceCode ?? '') . ' ' . (string)($asOfRaceLabel ?? ''));
$viewRaceText = trim((string)($selectedViewRaceCode ?? '') . ' ' . (string)($selectedViewRaceLabel ?? ''));
$versionLabel = (string)($selectedSnapshot['version_label'] ?? '');
$asOfDisplay = stlite_display_without_seconds((string)($selectedSnapshotDisplay ?? ''));
$selectedSnapshotValueText = (string)($selectedSnapshotValue ?? '');
$topBannerText = 'Standings as of ' . trim($asOfRaceText . ' ' . $versionLabel) . ' — ' . $asOfDisplay;

$yearDisplay = (string)($selectedYear ?? '');
$selectedViewRaceCodeText = (string)($selectedViewRaceCode ?? '');
$selectedViewRaceNumberText = (string)($selectedViewRaceNumber ?? '');
$selectedSegmentText = (string)($selectedSegment ?? '');
$selectedViewSnapshotDisplay = '';
if (isset($snapshotByRaceNumber) && is_array($snapshotByRaceNumber) && isset($snapshotByRaceNumber[(int)($selectedViewRaceNumber ?? 0)])) {
    $selectedViewSnapshotDisplay = stlite_display_without_seconds(
        st_snapshot_display(st_snapshot_key_from_file((string)$snapshotByRaceNumber[(int)$selectedViewRaceNumber]))
    );
}
if ($selectedViewSnapshotDisplay === '') {
    $selectedViewSnapshotDisplay = $asOfDisplay;
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MRL Standings Timeline Lite</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    :root {
        --row-odd: #ffffff;
        --row-even: #d2e5f7;
    }

    html { scrollbar-gutter: stable; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 16px;
        line-height: 1.3;
        margin: 12px;
        color: #111;
        background: #fff;
    }

    .page-wrap {
        max-width: 1400px;
        margin: 0 auto;
    }

    .top-controls {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 43px;
    }

    .top-controls select,
    .top-controls button {
        font: inherit;
        padding: 1px 8px;
    }

    .top-controls button {
        cursor: pointer;
    }

    .live-btn {
        min-width: 66px;
        font-weight: bold;
        border-radius: 18px;
        background: #eef5fb;
        color: #9ba9b6;
        border: 3px solid #d7e3ed;
        cursor: default;
    }

    .year-select { width: 92px; }
    .race-select { min-width: 210px; }

    .nav-button {
        min-width: 34px;
        text-align: center;
        padding-left: 6px;
        padding-right: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #111;
        background: #f2f2f2;
        border: 2px solid #999;
        border-radius: 2px;
        height: 30px;
        box-sizing: border-box;
    }

    .nav-button.disabled {
        opacity: 0.45;
        pointer-events: none;
        color: #999;
        background: #f3f3f3;
        border-color: #ccc;
    }

    .asof-banner {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 2px 12px;
        background: #f5f9fc;
        color: #425466;
        border: 2px solid #a9bfd3;
        border-radius: 999px;
        font-weight: 600;
        font-size: 14px;
        line-height: 1.15;
        white-space: nowrap;
        box-sizing: border-box;
    }

    .unofficial-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 2px 14px;
        border: 3px solid #c00000;
        border-radius: 999px;
        background: #fff;
        color: #c00000;
        font-size: 16px;
        font-weight: 600;
        line-height: 1.1;
        letter-spacing: 0.3px;
        white-space: nowrap;
        box-sizing: border-box;
    }

    .unofficial-print-marker {
        display: none;
    }

    .top-actions {
        margin-left: auto;
        display: inline-flex;
        gap: 10px;
    }

    .report-action-btn {
        min-width: 92px;
        border: 2px solid #777;
        border-radius: 3px;
        background: #f2f2f2;
        color: #111;
    }

    .report-action-btn:hover {
        filter: brightness(0.96);
    }

    .report-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        align-items: start;
    }

    .report-panel { min-width: 0; }

    .panel-title {
        font-size: 15px;
        margin: 10px 0 4px 0;
    }

    .snapshot-footnote {
        margin-left: 5px;
        color: #667;
        font-size: 11px;
        font-style: normal;
        white-space: nowrap;
    }

    .table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        table-layout: fixed;
        font-size: 16px;
        background: #fff;
    }

    th, td {
        border: 2px solid #151313;
        padding: 0 7px;
        text-align: center;
        vertical-align: top;
        white-space: nowrap;
        background: var(--row-odd);
    }

    th {
        background: #fbff00;
        color: #000;
        font-weight: bold;
    }

    tbody tr:nth-child(even) td { background: var(--row-even); }
    tbody tr:nth-child(odd) td { background: var(--row-odd); }

    th.team-col,
    td.team-col {
        text-align: left;
    }

    .col-rank,
    .col-week {
        width: 42px;
        text-align: center;
    }

    .col-score {
        width: 64px;
        text-align: center;
    }

    .empty-cell {
        color: #666;
        font-style: italic;
        text-align: center;
    }

    .tie-rank {
        font-weight: bold;
    }

    /* Exact Weekly Standings Table 4 segment colors */
    .weekly-winner-segment-row.weekly-winner-segment-S1 td { background: #c5d9f1 !important; }
    .weekly-winner-segment-row.weekly-winner-segment-S2 td { background: #c4bd97 !important; }
    .weekly-winner-segment-row.weekly-winner-segment-S3 td { background: #fcd5b4 !important; }
    .weekly-winner-segment-row.weekly-winner-segment-S4 td { background: #c4d79b !important; }
    .weekly-winner-segment-row td {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .asof-id {
        margin-top: 10px;
        font-size: 11px;
        color: #777;
        text-align: center;
    }

    .footer {
        margin-top: 26px;
        color: #666;
        font-size: 12px;
        text-align: center;
    }

    @media (max-width: 1200px) {
        .top-controls {
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .top-actions { margin-left: 0; }
        .report-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .asof-banner { white-space: normal; }
    }

    @media (max-width: 700px) {
        body {
            margin: 8px;
            font-size: 13px;
        }

        .report-grid { grid-template-columns: 1fr; }

        .top-controls select,
        .top-controls button {
            font-size: 12px;
            padding: 2px 6px;
        }

        .asof-banner {
            font-size: 12px;
            padding: 2px 9px;
        }

        .unofficial-status-pill {
            min-height: 30px;
            padding: 2px 10px;
            font-size: 13px;
        }

        table { font-size: 12px; }

        th, td {
            padding: 4px 6px;
        }
    }

    @media print {
        body {
            margin: 8px;
            background: #fff !important;
        }

        .page-wrap { max-width: none; }
        .top-controls { display: none !important; }

        .unofficial-print-marker {
            display: block !important;
            width: fit-content;
            margin: 0 auto 8px auto;
            padding: 3px 14px;
            border: 2px solid #c00000;
            border-radius: 999px;
            background: #fff;
            color: #c00000;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.15;
            letter-spacing: 0.3px;
            text-align: center;
            white-space: nowrap;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
    }
</style>
</head>
<body>
<div class="page-wrap">
    <form method="get" class="top-controls" id="timelineLiteControls">
        <button type="button" class="live-btn" disabled>Live</button>

        <input type="hidden" name="year" value="<?php echo stlite_h($yearDisplay); ?>">
        <input type="hidden" name="snapshot" value="<?php echo stlite_h($selectedSnapshotValueText); ?>">

        <select class="year-select" aria-label="Year" disabled>
            <option selected><?php echo stlite_h($yearDisplay); ?></option>
        </select>

        <select name="race" class="race-select" aria-label="Race" onchange="this.form.submit();">
            <?php foreach (($availableTimelineRaces ?? []) as $raceOption): ?>
                <?php
                $raceCodeOpt = (string)($raceOption['race_code'] ?? '');
                $raceLabelOpt = trim($raceCodeOpt . ' ' . st_short_race_label((string)($raceOption['race_name'] ?? '')));
                ?>
                <option value="<?php echo stlite_h($raceCodeOpt); ?>" <?php echo ($raceCodeOpt === $selectedViewRaceCodeText ? 'selected' : ''); ?>><?php echo stlite_h($raceLabelOpt); ?></option>
            <?php endforeach; ?>
        </select>

        <a class="nav-button <?php echo ((string)($previousRaceCode ?? '') === '' ? 'disabled' : ''); ?>" href="<?php echo stlite_h((string)($previousRaceCode ?? '') !== '' ? stlite_url(['race' => (string)$previousRaceCode]) : '#'); ?>">&lt;&lt;</a>
        <a class="nav-button <?php echo ((string)($nextRaceCode ?? '') === '' ? 'disabled' : ''); ?>" href="<?php echo stlite_h((string)($nextRaceCode ?? '') !== '' ? stlite_url(['race' => (string)$nextRaceCode]) : '#'); ?>">&gt;&gt;</a>

        <div class="asof-banner"><?php echo stlite_h($topBannerText); ?></div>
        <span class="unofficial-status-pill" aria-label="Auto-scoring unofficial status">AUTO-SCORING – UNOFFICIAL</span>

        <div class="top-actions">
            <button type="button" class="report-action-btn" onclick="window.print();">Print</button>
        </div>
    </form>

    <div class="unofficial-print-marker">AUTO-SCORING – UNOFFICIAL</div>

    <div class="report-grid">
        <div class="report-panel">
            <div class="panel-title"><?php echo stlite_h($yearDisplay . ' ' . $viewRaceText); ?><?php if ($selectedViewSnapshotDisplay !== ''): ?> <span class="snapshot-footnote">(<?php echo stlite_h($selectedViewSnapshotDisplay); ?>)</span><?php endif; ?></div>
            <div class="table-wrap"><?php stlite_render_score_table($selectedWeeklyRows ?? [], 'weekly_total', 'Week ' . $selectedViewRaceNumberText); ?></div>
        </div>

        <div class="report-panel">
            <div class="panel-title"><?php echo stlite_h($yearDisplay . ' ' . $selectedSegmentText); ?></div>
            <div class="table-wrap"><?php stlite_render_score_table($segmentRows ?? [], 'total', $selectedSegmentText); ?></div>
        </div>

        <div class="report-panel">
            <div class="panel-title"><?php echo stlite_h($yearDisplay); ?></div>
            <div class="table-wrap"><?php stlite_render_score_table($seasonRows ?? [], 'total', $yearDisplay); ?></div>
        </div>

        <div class="report-panel">
            <div class="panel-title"><?php echo stlite_h($yearDisplay); ?> Weekly Winners</div>
            <div class="table-wrap"><?php stlite_render_weekly_winners_table($weeklyWinnerRows ?? []); ?></div>
        </div>
    </div>

    <div class="asof-id">Snapshot set: <?php echo stlite_h(stlite_compact_snapshot_value($selectedSnapshotValueText)); ?></div>
    <div class="footer">Copyright © 2017-<?php echo stlite_h($yearDisplay); ?> Manlius Racing League<br>All rights reserved.</div>
</div>
</body>
</html>