<?php
declare(strict_types=1);

/**
 * race_results_single_test.php
 *
 * VERSION: v1.00.00
 * LAST MODIFIED: 2026-03-12
 * BUILD TS: 20260312_013500000
 *
 * CHANGELOG:
 * v1.00.00 (2026-03-12)
 *   - Initial single-race standings proof-of-concept page.
 *   - Uses dedicated scoring controls independent from admin_setup.
 *   - Loads team picks for one scoring year + scoring segment.
 *   - Loads all defined race snapshots up to and including selected race.
 *   - Calculates:
 *       - selected race weekly standings
 *       - cumulative segment standings through selected race
 *       - cumulative season standings through selected race
 *       - weekly winners through selected race
 *   - Intended as the next step toward a Jeff-style race sheet.
 *
 * PHP: 7.3 compatible.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once __DIR__ . '/race_results_team_helper.php';
require_once __DIR__ . '/race_results_snapshot_helper.php';

function rrsg_h($val): string
{
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function rrsg_driver_net(array $driverPoints, string $driverName): int
{
    if ($driverName === '') {
        return 0;
    }

    if (!isset($driverPoints[$driverName]) || !is_array($driverPoints[$driverName])) {
        return 0;
    }

    return (int)($driverPoints[$driverName]['net'] ?? 0);
}

function rrsg_sort_weekly_rows(array &$rows): void
{
    usort($rows, function ($a, $b) {
        $aTotal = (int)($a['weeklyTotal'] ?? 0);
        $bTotal = (int)($b['weeklyTotal'] ?? 0);

        if ($aTotal !== $bTotal) {
            return ($bTotal <=> $aTotal);
        }

        return strcasecmp((string)($a['teamName'] ?? ''), (string)($b['teamName'] ?? ''));
    });
}

function rrsg_sort_total_rows(array $totals): array
{
    $rows = [];

    foreach ($totals as $teamName => $total) {
        $rows[] = [
            'teamName' => (string)$teamName,
            'total' => (int)$total,
        ];
    }

    usort($rows, function ($a, $b) {
        $aTotal = (int)$a['total'];
        $bTotal = (int)$b['total'];

        if ($aTotal !== $bTotal) {
            return ($bTotal <=> $aTotal);
        }

        return strcasecmp((string)$a['teamName'], (string)$b['teamName']);
    });

    return $rows;
}

function rrsg_find_snapshot_file(string $raceFolder): string
{
    if (!is_dir($raceFolder)) {
        return '';
    }

    $files = glob($raceFolder . '/snapshot_*.html');
    if (!is_array($files) || empty($files)) {
        return '';
    }

    sort($files, SORT_STRING);
    return (string)end($files);
}

function rrsg_build_weekly_rows(array $teamRows, array $driverPoints): array
{
    $weeklyRows = [];

    foreach ($teamRows as $team) {
        $driverA = (string)($team['driverA'] ?? '');
        $driverB = (string)($team['driverB'] ?? '');
        $driverC = (string)($team['driverC'] ?? '');
        $driverD = (string)($team['driverD'] ?? '');

        $netA = rrsg_driver_net($driverPoints, $driverA);
        $netB = rrsg_driver_net($driverPoints, $driverB);
        $netC = rrsg_driver_net($driverPoints, $driverC);
        $netD = rrsg_driver_net($driverPoints, $driverD);

        $weeklyTotal = $netA + $netB + $netC + $netD;

        $weeklyRows[] = [
            'teamName' => (string)($team['teamName'] ?? ''),
            'userName' => (string)($team['userName'] ?? ''),
            'driverA' => $driverA,
            'driverB' => $driverB,
            'driverC' => $driverC,
            'driverD' => $driverD,
            'netA' => $netA,
            'netB' => $netB,
            'netC' => $netC,
            'netD' => $netD,
            'weeklyTotal' => $weeklyTotal,
        ];
    }

    rrsg_sort_weekly_rows($weeklyRows);
    return $weeklyRows;
}

function rrsg_get_weekly_winner(array $weeklyRows): array
{
    if (empty($weeklyRows)) {
        return [
            'teamName' => '',
            'points' => 0,
        ];
    }

    return [
        'teamName' => (string)($weeklyRows[0]['teamName'] ?? ''),
        'points' => (int)($weeklyRows[0]['weeklyTotal'] ?? 0),
    ];
}

/* ------------------------------------------------------------------
   SCORING CONTROLS
   Independent from admin_setup / current picks form logic
   ------------------------------------------------------------------ */

$scoreYear = '2026';
$scoreSegment = 'S1';
$scoreRaceCode = 'R04';

/*
    Explicit race list for proof-of-concept testing.

    IMPORTANT:
    - Order matters.
    - Selected race includes all prior races in cumulative totals.
*/
$races = [
    [
        'raceCode'   => 'R01',
        'raceLabel'  => 'Daytona',
        'raceFolder' => __DIR__ . '/2026/R01_Daytona_500_202602150001',
    ],
    [
        'raceCode'   => 'R02',
        'raceLabel'  => 'Atlanta',
        'raceFolder' => __DIR__ . '/2026/R02_NASCAR_Cup_Series_at_Atlanta_202602220025',
    ],
    [
        'raceCode'   => 'R03',
        'raceLabel'  => 'COTA',
        'raceFolder' => __DIR__ . '/2026/R03_NASCAR_Cup_Series_at_Circuit_of_the_Americas_202603013998',
    ],
    [
        'raceCode'   => 'R04',
        'raceLabel'  => 'Phoenix',
        'raceFolder' => __DIR__ . '/2026/R04_NASCAR_Cup_Series_at_Phoenix_202603080023',
    ],
];

$selectedRaceIndex = -1;
for ($i = 0; $i < count($races); $i++) {
    if ((string)$races[$i]['raceCode'] === $scoreRaceCode) {
        $selectedRaceIndex = $i;
        break;
    }
}

if ($selectedRaceIndex < 0) {
    die('Selected race code not found in race list.');
}

$teamRows = rr_get_segment_team_picks($dbo ?? null, $dbconnect ?? null, $scoreYear, $scoreSegment);

$segmentTotals = [];
$seasonTotals = [];
$weeklyWinners = [];
$selectedRaceWeeklyRows = [];
$selectedRaceMeta = [
    'raceCode' => '',
    'raceLabel' => '',
    'snapshotFile' => '',
    'driverCount' => 0,
];

foreach ($teamRows as $team) {
    $teamName = (string)($team['teamName'] ?? '');
    if ($teamName === '') {
        continue;
    }

    $segmentTotals[$teamName] = 0;
    $seasonTotals[$teamName] = 0;
}

for ($i = 0; $i <= $selectedRaceIndex; $i++) {
    $race = $races[$i];

    $raceCode = (string)($race['raceCode'] ?? '');
    $raceLabel = (string)($race['raceLabel'] ?? '');
    $raceFolder = (string)($race['raceFolder'] ?? '');

    $snapshotFile = rrsg_find_snapshot_file($raceFolder);
    $driverPoints = [];
    $weeklyRows = [];

    if ($snapshotFile !== '') {
        $driverPoints = rrs_load_snapshot_driver_points($snapshotFile);
        $weeklyRows = rrsg_build_weekly_rows($teamRows, $driverPoints);

        foreach ($weeklyRows as $row) {
            $teamName = (string)$row['teamName'];
            $weeklyTotal = (int)$row['weeklyTotal'];

            if (!isset($segmentTotals[$teamName])) {
                $segmentTotals[$teamName] = 0;
            }
            if (!isset($seasonTotals[$teamName])) {
                $seasonTotals[$teamName] = 0;
            }

            $segmentTotals[$teamName] += $weeklyTotal;
            $seasonTotals[$teamName] += $weeklyTotal;
        }

        $weeklyWinners[$raceCode] = rrsg_get_weekly_winner($weeklyRows);
    } else {
        $weeklyWinners[$raceCode] = [
            'teamName' => '',
            'points' => 0,
        ];
    }

    if ($raceCode === $scoreRaceCode) {
        $selectedRaceWeeklyRows = $weeklyRows;
        $selectedRaceMeta = [
            'raceCode' => $raceCode,
            'raceLabel' => $raceLabel,
            'snapshotFile' => $snapshotFile,
            'driverCount' => count($driverPoints),
        ];
    }
}

$segmentStandings = rrsg_sort_total_rows($segmentTotals);
$seasonStandings = rrsg_sort_total_rows($seasonTotals);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Race Results Single Test</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            margin: 20px;
        }

        h1, h2 {
            margin: 0 0 10px 0;
        }

        .meta {
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .block {
            margin-bottom: 28px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 1200px;
        }

        th, td {
            border: 1px solid #999;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
        }

        td.num {
            text-align: right;
            white-space: nowrap;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }
    </style>
</head>
<body>

<h1>Race Results Single Test</h1>

<div class="meta">
    <strong>Scoring Year:</strong> <?php echo rrsg_h($scoreYear); ?><br>
    <strong>Scoring Segment:</strong> <?php echo rrsg_h($scoreSegment); ?><br>
    <strong>Selected Race:</strong> <?php echo rrsg_h($scoreRaceCode); ?><br>
    <strong>Teams Loaded:</strong> <?php echo count($teamRows); ?><br>
    <strong>Selected Snapshot:</strong> <?php echo rrsg_h($selectedRaceMeta['snapshotFile'] !== '' ? basename($selectedRaceMeta['snapshotFile']) : 'NOT FOUND'); ?><br>
    <strong>Drivers Loaded For Selected Race:</strong> <?php echo rrsg_h($selectedRaceMeta['driverCount']); ?>
</div>

<div class="block">
    <h2>Weekly Winners Through <?php echo rrsg_h($scoreRaceCode); ?></h2>
    <table>
        <thead>
            <tr>
                <th>Race</th>
                <th>Winner</th>
                <th>Points</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 0; $i <= $selectedRaceIndex; $i++): ?>
                <?php $race = $races[$i]; ?>
                <?php $raceCode = (string)$race['raceCode']; ?>
                <tr>
                    <td><?php echo rrsg_h($raceCode . ' ' . $race['raceLabel']); ?></td>
                    <td><?php echo rrsg_h($weeklyWinners[$raceCode]['teamName'] ?? ''); ?></td>
                    <td class="num"><?php echo rrsg_h($weeklyWinners[$raceCode]['points'] ?? 0); ?></td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>

<div class="block">
    <h2><?php echo rrsg_h($selectedRaceMeta['raceCode'] . ' ' . $selectedRaceMeta['raceLabel']); ?> Weekly Standings</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Team</th>
                <th>Weekly Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($selectedRaceWeeklyRows)): ?>
                <tr>
                    <td colspan="3">No weekly rows generated.</td>
                </tr>
            <?php else: ?>
                <?php $rank = 1; ?>
                <?php foreach ($selectedRaceWeeklyRows as $row): ?>
                    <tr>
                        <td class="num"><?php echo $rank; ?></td>
                        <td><?php echo rrsg_h($row['teamName']); ?></td>
                        <td class="num"><strong><?php echo rrsg_h($row['weeklyTotal']); ?></strong></td>
                    </tr>
                    <?php $rank++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="block">
    <h2>Segment Standings Through <?php echo rrsg_h($scoreRaceCode); ?></h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Team</th>
                <th>Segment Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($segmentStandings)): ?>
                <tr>
                    <td colspan="3">No segment standings generated.</td>
                </tr>
            <?php else: ?>
                <?php $rank = 1; ?>
                <?php foreach ($segmentStandings as $row): ?>
                    <tr>
                        <td class="num"><?php echo $rank; ?></td>
                        <td><?php echo rrsg_h($row['teamName']); ?></td>
                        <td class="num"><strong><?php echo rrsg_h($row['total']); ?></strong></td>
                    </tr>
                    <?php $rank++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="block">
    <h2>Season Standings Through <?php echo rrsg_h($scoreRaceCode); ?></h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Team</th>
                <th>Season Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($seasonStandings)): ?>
                <tr>
                    <td colspan="3">No season standings generated.</td>
                </tr>
            <?php else: ?>
                <?php $rank = 1; ?>
                <?php foreach ($seasonStandings as $row): ?>
                    <tr>
                        <td class="num"><?php echo $rank; ?></td>
                        <td><?php echo rrsg_h($row['teamName']); ?></td>
                        <td class="num"><strong><?php echo rrsg_h($row['total']); ?></strong></td>
                    </tr>
                    <?php $rank++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>