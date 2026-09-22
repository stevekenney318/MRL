<?php
declare(strict_types=1);

/**
 * team_chart.php
 *
 * VERSION: v027
 * LAST MODIFIED: 9/21/2026 11:08:45 pm ET
 *
 * DESCRIPTION:
 * Public Team Chart page with PRG flow, print, spreadsheet export,
 * and render-time LP / RD chart annotations.
 *
 * CHANGELOG:
 *
 * v027 (9/21/2026 11:08:45 pm ET)
 * - UI: Print/Spreadsheet controls are no longer hidden during year/segment navigation; they stay in place until the new page loads.
 * - UI: Added cross-document view transition (@view-transition) so supported browsers crossfade between pages instead of flashing blank.
 * - PRESERVE: v026 layout, responsive chart fit, nav styling, print/PDF, XLSX export, themes, LP/RD display, and database behavior unchanged.
 *
 * v026 (9/21/2026 8:20:16 pm ET)
 * - UI: Print/Spreadsheet controls now use visibility:hidden during year/segment navigation instead of display:none.
 * - RESULT: The action area keeps its dimensions during reload, preventing the mobile control row from jumping up/down.
 * - PRESERVE: v025 responsive chart fit, nav styling, print/PDF, XLSX export, themes, LP/RD display, and database behavior unchanged.
 *
 * v025 (9/21/2026 5:33:46 pm ET)
 * - RESPONSIVE: Team Chart now fits the available screen width instead of defaulting to a horizontal-scroll viewport.
 * - MOBILE: Narrow screens allow cell wrapping so the full seven-column chart remains visible and can be pinch-zoomed naturally.
 * - NAV: Previous/next controls now use ◀ / ▶ with directional half-pill rounding while preserving the existing gap between buttons.
 * - PRINT: Existing landscape print/PDF behavior remains unchanged.
 *
 * v024 (9/20/2026 3:23:48 pm ET)
 * - EXPORT FIX: Applies the note-row border style to all seven cells before merging A:G in the XLSX footer.
 * - RESULT: Restores the visible bottom border across the full width of the final peach note row in Excel.
 * - PRESERVE: Pure-PHP XLSX writer, title/header/data colors, notes, widths, frozen rows, print behavior, navigation, themes, LP/RD display, and DB queries unchanged.
 *
 * v023 (9/20/2026 1:37:21 pm ET)
 * - EXPORT: Replaced PhpSpreadsheet/Composer XLSX export with a self-contained pure-PHP XLSX writer based on the proven Weekly Standings approach.
 * - EXPORT: Preserves Team Chart title/header styling, column colors, borders, column widths, row heights, notes, frozen top rows, and timestamped filenames.
 * - UI: Added a subtle divider between the Year navigation group and Segment navigation group for clearer visual separation.
 * - CLEANUP: team_chart.php no longer requires vendor/autoload.php or PhpSpreadsheet. Vendor files are NOT deleted by this installer.
 * - PRESERVE: Privacy gate, LP/RD display, print behavior, navigation, chart width, themes, and all database queries remain unchanged.
 *
 * v022 (9/20/2026 1:02:07 pm ET)
 * - UI: Standalone Team Chart now uses a wider 85% report width, matching the Team Page chart feel more closely.
 * - UI: Top controls now follow the flatter Weekly Standings visual language: slimmer selects/buttons, no select shadow, tighter spacing, and matching report-action styling.
 * - UI: Live is now a blue pill-shaped control with the same enabled/disabled treatment used by Weekly Standings.
 * - PRINT: Team theme/background image is explicitly removed for print/PDF while chart header/cell colors remain preserved.
 * - PRESERVE: Existing privacy gate, navigation behavior, LP/RD display, Print filename logic, Spreadsheet export, and PhpSpreadsheet dependency are unchanged in this pass.
 *
 * v021 (9/9/2026 4:08:09 am ET)
 * - UI: Control row now follows approved Live / year / year arrows / segment / segment arrows / report-actions layout.
 * - UI: Removed redundant Choose year / Choose segment labels.
 * - UI: Live is disabled while already viewing the configured current year/segment.
 * - UI: Control row aligns to the existing chart edges; chart size/position remains unchanged.
 * - THEME: Uses the logged-in user's Team theme through shared mrl_team/mrl_shared_theme.css v001.
 * - PRESERVE: Auto-load dropdowns, arrow boundary disabling, privacy gate, chart colors, Print/Spreadsheet, LP/RD display, and timestamped exports.
 *
 * v020 (9/9/2026 3:11:07 am ET)
 * - FIX: R28 footnote resolves the known canonical short-name value "World" through richer trusted schedule fields to "World Wide Tech".
 * - UI: Footnote sizing now wins over shared teamchart table CSS and matches the smaller team.php note treatment.
 * - UI: Year and segment dropdown changes load automatically; Show button removed.
 * - UI: Adds side-by-side << >> navigation for year and segment plus Live jump to current configured year/segment.
 * - EXPORT: Spreadsheet footnote font/fill are re-applied after global formatting so notes remain compact with peach background.
 * - PRESERVE: LP/RD markers, The Chase naming, privacy gate, RD merged rows, Print/Spreadsheet actions, and timestamped filenames.
 *
 * v019 (9/9/2026 2:44:18 am ET)
 * - CONSISTENCY: LP rows now append their marker to Team, Owner, all four drivers, and Submission Time in HTML and spreadsheet output.
 * - CONSISTENCY: LP/RD effective-race notes now include the canonical short race name when available, e.g. R28 (World Wide Tech).
 * - CONSISTENCY: S4 is Playoffs through 2025 and The Chase beginning in 2026.
 * - UI: Standalone chart typography now matches the team.php current-segment chart more closely (Arial, 13pt; compact 12px notes).
 * - UI: Adds << / >> segment navigation around the segment selector.
 * - EXPORT: Spreadsheet includes chart notes and uses matching Arial typography.
 * - EXPORT: Spreadsheet and print/PDF filenames now include current generation timestamp with milliseconds.
 * - PRESERVE: Existing privacy gate, RD merged-row display, Approved Exception behavior, PRG flow, and database queries.
 *
 * v018 (8/24/2026 9:45:04 pm)
 * - SAFETY: Current-season driver picks remain private until the segment's first points race starts.
 * - SAFETY: Uses segment_race_ranges + canonical race schedule helper, not legacy formLockDate.
 * - SAFETY: Direct chart access and spreadsheet export use the same privacy gate.
 * - SAFETY: Current-season deadline lookup fails closed; previous seasons remain viewable.
 * - PRESERVE: LP/RD/Approved Exception rendering after deadline.
 *
 * v016 (8/19/2026 7:12:00 pm)
 * - NEW: SEG rows with ADJ history show * Admin-approved regular pick.
 * - CHANGE: Preserved LP/RD and spreadsheet behavior.
 *
 * v015 (4/12/2026)
 * - FIX: Suppressed standalone base SEG/ADJ rows when a team also has an RD row so only the merged two-row RD block is shown.
 * - FIX: Added a space before team-name marker symbols in the chart display.
 * - CHANGE: Preserved existing footnote wording, effective-race notes, and spreadsheet export behavior.
 *
 * v014 (4/8/2026)
 * - CHANGE: Reduced note font size slightly for better proportion with the chart.
 * - FIX: Applied note-row background color inline so the footer cell stays visible even when external CSS overrides table styles.
 * - CHANGE: Moved file header block back to the top of the file, directly below declare(strict_types=1).
 *
 * v013 (4/8/2026)
 * - CHANGE: Moved chart notes into a final full-width table row so styling is no longer affected by page-level CSS.
 * - CHANGE: Notes now stack vertically, one per line, inside a single footer cell.
 * - CHANGE: Uses header-row color treatment for the notes cell.
 *
 * v012 (4/8/2026)
 * - CHANGE: Updated chart footnotes to use black text on a light gray background (#000000 on #eeeeee).
 * - CHANGE: Preserved the current LP/RD marker logic, notes, and RD merged-row layout.
 *
 * v011 (4/8/2026)
 * - CHANGE: Updated chart footnotes to use a higher-contrast button-style color treatment for readability.
 * - CHANGE: Increased footnote padding / line height slightly for easier scanning.
 * - CHANGE: No logic changes to LP/RD markers, unique note assignment, or RD merged-row rendering.
 *
 * v010 (4/8/2026)
 * - CHANGE: Added current-standard file header with version, modified timestamp, and changelog.
 * - CHANGE: Team Chart HTML now uses unique per-team special markers with specific notes including effective race.
 * - CHANGE: LP rows mark all four drivers with the same team-specific marker.
 * - CHANGE: RD rows now render as two-row Excel-style display with shared cells vertically merged and only the changed driver column repeated.
 * - CHANGE: Spreadsheet export remains supported and uses inline markers, but not the merged RD HTML layout.
 *
 * v009 (4/7/2026)
 * - Rebuilt from user_picks + users with render-time (LP) / (RD) markers plus legend for chart and spreadsheet output.
 */

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '/team_chart.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mrl_team/mrl_theme_helper.php';
require_once __DIR__ . '/race_results/race_schedule_helper.php';

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}

$teamChartTheme = 'cars';
try {
    $teamChartUid = (int)($_SESSION['userSession'] ?? 0);
    if (isset($dbo) && $dbo instanceof PDO && $teamChartUid > 0) {
        $teamChartTheme = mrl_theme_get($dbo, $teamChartUid);
    }
} catch (Throwable $e) {
    $teamChartTheme = 'cars';
}

if (!isset($adminStatusLine)) {
    $adminStatusLine = '';
}

date_default_timezone_set('America/New_York');
$currentTimeIs = date("n/j/Y g:i a");



function h($val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function valid_year($y): bool {
    return preg_match('/^\d{4}$/', (string)$y) === 1;
}

function valid_segment($s): bool {
    return preg_match('/^S[1-9]\d*$/', (string)$s) === 1;
}

/**
 * Canonical normal-pick deadline for a year/segment.
 * Segment picks become public when that segment's first points race starts.
 * Returns 0 if the deadline cannot be resolved.
 */
function tc_segment_pick_deadline_timestamp($dbo, $dbconnect, string $year, string $segment): int
{
    $startRace = 0;

    try {
        if (isset($dbo) && $dbo instanceof PDO) {
            $stmt = $dbo->prepare(
                "SELECT startRace
                   FROM segment_race_ranges
                  WHERE raceYear = :year
                    AND segment = :segment
                  LIMIT 1"
            );
            $stmt->execute([':year'=>$year, ':segment'=>$segment]);
            $value = $stmt->fetchColumn();
            $startRace = ($value === false) ? 0 : (int)$value;
        } elseif (isset($dbconnect) && $dbconnect instanceof mysqli) {
            $yearInt = (int)$year;
            $stmt = mysqli_prepare(
                $dbconnect,
                "SELECT startRace
                   FROM segment_race_ranges
                  WHERE raceYear = ?
                    AND segment = ?
                  LIMIT 1"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'is', $yearInt, $segment);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $startRaceDb);
                if (mysqli_stmt_fetch($stmt)) $startRace = (int)$startRaceDb;
                mysqli_stmt_close($stmt);
            }
        }

        if ($startRace <= 0) return 0;

        $races = mrl_schedule_helper_points_races((int)$year);
        foreach ($races as $race) {
            if ((int)($race['race_number'] ?? 0) !== $startRace) continue;
            return (int)mrl_schedule_helper_race_datetime($race)->getTimestamp();
        }
    } catch (Throwable $e) {
        return 0;
    }

    return 0;
}

function tc_marker_symbol(int $index): string
{
    return str_repeat('*', max(1, $index));
}

function tc_segment_label(string $year, string $segment): string
{
    if (function_exists('mrl_config_segment_name')) {
        return mrl_config_segment_name((int)$year, $segment);
    }

    $segment = strtoupper(trim($segment));
    if ($segment === 'S1') return 'Segment #1';
    if ($segment === 'S2') return 'Segment #2';
    if ($segment === 'S3') return 'Segment #3';
    if ($segment === 'S4') return (int)$year >= 2026 ? 'The Chase' : 'Playoffs';
    return $segment;
}

function tc_short_race_name(string $year, int $raceNumber): string
{
    if ($raceNumber <= 0) return '';

    try {
        $race = mrl_schedule_helper_race_by_number((int)$year, $raceNumber);
        if (!is_array($race)) return '';

        $preferred = trim((string)(
            $race['mrl_race_name']
            ?? $race['race_name']
            ?? $race['track_name']
            ?? ''
        ));

        $clean = static function (string $name): string {
            $name = str_replace('_', ' ', trim($name));
            $name = preg_replace('/\s+/', ' ', $name);
            $name = preg_replace('/^NASCAR\s+Cup\s+Series\s+at\s+/i', '', (string)$name);
            return trim((string)$name);
        };

        $preferred = $clean($preferred);
        if ($preferred === '') return '';

        /*
         * Known canonical R28 issue:
         * mrl_race_name currently carries only "World".
         * For that exact value only, inspect the other trusted canonical fields
         * for the fuller World Wide Technology name.
         * Other races continue to use the preferred canonical name unchanged.
         */
        if (strcasecmp($preferred, 'World') === 0) {
            foreach (['race_name', 'track_name', 'display_name', 'name'] as $field) {
                $candidate = $clean((string)($race[$field] ?? ''));
                if (
                    stripos($candidate, 'World Wide Technology') !== false
                    || stripos($candidate, 'World Wide Tech') !== false
                ) {
                    return 'World Wide Tech';
                }
            }
        }

        if (
            stripos($preferred, 'World Wide Technology') !== false
            || stripos($preferred, 'World Wide Tech') !== false
        ) {
            return 'World Wide Tech';
        }

        return $preferred;
    } catch (Throwable $e) {
        return '';
    }
}

function tc_effective_race_label(string $year, $value): string
{
    $num = (int)$value;
    if ($num <= 0) {
        return '';
    }

    $label = 'R' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
    $raceName = tc_short_race_name($year, $num);

    if ($raceName !== '') {
        $label .= ' (' . $raceName . ')';
    }

    return $label;
}

function tc_generation_stamp(): string
{
    $now = microtime(true);
    $seconds = (int)floor($now);
    $milliseconds = (int)floor(($now - $seconds) * 1000);

    return date('Ymd_His', $seconds)
        . str_pad((string)$milliseconds, 3, '0', STR_PAD_LEFT);
}

function tc_get_reference_pick_row(array $row, array $rowsByPickId, ?array $baseRow): ?array
{
    $supersedesPickID = (int)($row['supersedes_pickID'] ?? 0);
    if ($supersedesPickID > 0 && isset($rowsByPickId[$supersedesPickID])) {
        return $rowsByPickId[$supersedesPickID];
    }
    return $baseRow;
}

function tc_get_changed_field_for_rd(array $row, ?array $referenceRow): ?string
{
    if (strtoupper(trim((string)($row['pick_type'] ?? ''))) !== 'RD') {
        return null;
    }

    foreach (['driverA', 'driverB', 'driverC', 'driverD'] as $field) {
        $current = trim((string)($row[$field] ?? ''));
        $original = trim((string)($referenceRow[$field] ?? ''));
        if ($current !== '' && strcasecmp($current, $original) !== 0) {
            return $field;
        }
    }

    return null;
}

function tc_build_chart_context(array $rows, string $year): array
{
    $rowsByPickId = [];
    $baseRowsByTeam = [];
    $teamsWithRd = [];
    $notes = [];
    $htmlRows = [];
    $excelRows = [];
    $markerIndex = 0;

    foreach ($rows as $row) {
        $pickId = (int)($row['pickID'] ?? 0);
        if ($pickId > 0) {
            $rowsByPickId[$pickId] = $row;
        }

        $teamName = trim((string)($row['teamName'] ?? ''));
        $pickType = strtoupper(trim((string)($row['pick_type'] ?? 'SEG')));

        if ($teamName !== '' && !isset($baseRowsByTeam[$teamName]) && ($pickType === 'SEG' || $pickType === 'ADJ' || $pickType === '')) {
            $baseRowsByTeam[$teamName] = $row;
        }

        if ($teamName !== '' && !isset($baseRowsByTeam[$teamName])) {
            $baseRowsByTeam[$teamName] = $row;
        }

        if ($teamName !== '' && $pickType === 'RD') {
            $teamsWithRd[$teamName] = true;
        }
    }

    foreach ($rows as $row) {
        $teamName = trim((string)($row['teamName'] ?? ''));
        $pickType = strtoupper(trim((string)($row['pick_type'] ?? 'SEG')));
        $referenceRow = tc_get_reference_pick_row($row, $rowsByPickId, $baseRowsByTeam[$teamName] ?? null);
        $adminAdjusted = !empty($row['admin_adjusted']);
        $marker = '';

        if (($pickType === 'SEG' || $pickType === 'ADJ') && isset($teamsWithRd[$teamName])) {
            continue;
        }

        if ($adminAdjusted && ($pickType === 'SEG' || $pickType === '')) {
            $markerIndex++;
            $marker = tc_marker_symbol($markerIndex);
            $notes[] = ['marker' => $marker, 'text' => $teamName . ' — Approved Exception'];
        } elseif ($pickType === 'LP') {
            $markerIndex++;
            $marker = tc_marker_symbol($markerIndex);
            $noteText = $teamName . ' — Late Pick';
            $effectiveRaceLabel = tc_effective_race_label($year, $row['effective_race'] ?? 0);
            if ($effectiveRaceLabel !== '') {
                $noteText .= ' — Effective ' . $effectiveRaceLabel;
            }
            $notes[] = ['marker' => $marker, 'text' => $noteText];
        } elseif ($pickType === 'RD') {
            $changedField = tc_get_changed_field_for_rd($row, $referenceRow);
            if ($changedField !== null) {
                $markerIndex++;
                $marker = tc_marker_symbol($markerIndex);
                $noteText = $teamName . ' — Replacement Driver';
                $effectiveRaceLabel = tc_effective_race_label($year, $row['effective_race'] ?? 0);
                if ($effectiveRaceLabel !== '') {
                    $noteText .= ' — Effective ' . $effectiveRaceLabel;
                }
                $notes[] = ['marker' => $marker, 'text' => $noteText];
            }
        }

        $excelRow = $row;
        foreach (['driverA', 'driverB', 'driverC', 'driverD'] as $field) {
            $driver = trim((string)($row[$field] ?? ''));
            if ($driver === '') {
                $excelRow[$field] = '';
                continue;
            }

            if ($pickType === 'LP' && $marker !== '') {
                $excelRow[$field] = $driver . ' ' . $marker;
            } elseif ($pickType === 'RD' && $marker !== '') {
                $changedField = tc_get_changed_field_for_rd($row, $referenceRow);
                $excelRow[$field] = ($field === $changedField) ? ($driver . ' ' . $marker) : $driver;
            } else {
                $excelRow[$field] = $driver;
            }
        }
        if ($pickType === 'LP' && $marker !== '') {
            foreach (['teamName', 'userName', 'entryDate'] as $field) {
                $value = trim((string)($excelRow[$field] ?? ''));
                $excelRow[$field] = $value === '' ? '' : ($value . ' ' . $marker);
            }
        }
        $excelRows[] = $excelRow;

        if ($pickType === 'RD' && $marker !== '') {
            $changedField = tc_get_changed_field_for_rd($row, $referenceRow);
            if ($changedField !== null) {
                $htmlRows[] = [
                    'render_type' => 'rd_pair',
                    'current' => $row,
                    'reference' => $referenceRow,
                    'marker' => $marker,
                    'changed_field' => $changedField,
                ];
                continue;
            }
        }

        $htmlRows[] = [
            'render_type' => 'single',
            'current' => $row,
            'marker' => $marker,
            'pick_type' => $pickType,
        ];
    }

    return [
        'htmlRows' => $htmlRows,
        'excelRows' => $excelRows,
        'notes' => $notes,
    ];
}

/**
 * Team Chart pure-PHP XLSX helpers.
 * Derived from the proven Weekly Standings XLSX packaging approach.
 * No Composer or vendor/autoload.php dependency.
 */
function tc_xlsx_xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function tc_xlsx_col_letter(int $col): string
{
    $col = max(1, $col);
    $letter = '';

    while ($col > 0) {
        $mod = ($col - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $col = (int)(($col - $mod) / 26);
    }

    return $letter;
}

function tc_xlsx_cell_xml(string $cellRef, $value, int $styleIndex): string
{
    return '<c r="' . tc_xlsx_xml($cellRef) . '" t="inlineStr" s="' . $styleIndex . '"><is><t>'
        . tc_xlsx_xml((string)$value)
        . '</t></is></c>';
}

function tc_zip_dos_parts(?int $timestamp = null): array
{
    $timestamp = $timestamp ?? time();

    $year = (int)date('Y', $timestamp);
    $month = (int)date('n', $timestamp);
    $day = (int)date('j', $timestamp);
    $hour = (int)date('G', $timestamp);
    $minute = (int)date('i', $timestamp);
    $second = (int)date('s', $timestamp);

    $dosTime = ($hour << 11) | ($minute << 5) | (int)floor($second / 2);
    $dosDate = (($year - 1980) << 9) | ($month << 5) | $day;

    return [$dosTime, $dosDate];
}

function tc_zip_from_strings(array $files): string
{
    $zipData = '';
    $centralDirectory = '';
    $offset = 0;

    [$dosTime, $dosDate] = tc_zip_dos_parts();

    foreach ($files as $name => $data) {
        $name = str_replace('\\', '/', (string)$name);
        $data = (string)$data;

        $nameLength = strlen($name);
        $dataLength = strlen($data);
        $crc = crc32($data);

        $localHeader = pack(
            'VvvvvvVVVvv',
            0x04034b50,
            20,
            0,
            0,
            $dosTime,
            $dosDate,
            $crc,
            $dataLength,
            $dataLength,
            $nameLength,
            0
        );

        $zipData .= $localHeader . $name . $data;

        $centralDirectory .= pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014b50,
            20,
            20,
            0,
            0,
            $dosTime,
            $dosDate,
            $crc,
            $dataLength,
            $dataLength,
            $nameLength,
            0,
            0,
            0,
            0,
            0,
            $offset
        ) . $name;

        $offset += strlen($localHeader) + $nameLength + $dataLength;
    }

    $centralOffset = strlen($zipData);
    $centralSize = strlen($centralDirectory);
    $fileCount = count($files);

    $endOfCentralDirectory = pack(
        'VvvvvVVv',
        0x06054b50,
        0,
        0,
        $fileCount,
        $fileCount,
        $centralSize,
        $centralOffset,
        0
    );

    return $zipData . $centralDirectory . $endOfCentralDirectory;
}

/**
 * Sends a real XLSX file using only built-in PHP string/pack logic.
 * Preserves Team Chart title/header fills, per-column colors, notes,
 * borders, widths, row heights, and frozen header rows.
 */
function send_excel_xlsx(string $filenameBase, array $rows, string $title, array $notes): void
{
    $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filenameBase);

    if ($safeBase === '') {
        $safeBase = 'team_chart';
    }

    $filename = $safeBase . '.xlsx';

    $cellsByRow = [];
    $mergeRanges = [];

    // Style IDs:
    // 1 title, 2 header, 3 team/owner/time, 4 A, 5 B, 6 C, 7 D, 8 note
    $cellsByRow[1][] = tc_xlsx_cell_xml('A1', $title, 1);
    $mergeRanges[] = 'A1:G1';

    $headers = ['Team','Owner','Group A','Group B','Group C','Group D','Submission Time'];
    foreach ($headers as $idx => $header) {
        $cellsByRow[2][] = tc_xlsx_cell_xml(tc_xlsx_col_letter($idx + 1) . '2', $header, 2);
    }

    $rowNum = 3;

    if (empty($rows)) {
        $cellsByRow[$rowNum][] = tc_xlsx_cell_xml('A' . $rowNum, 'No picks found for this year / segment.', 3);
        $mergeRanges[] = 'A' . $rowNum . ':G' . $rowNum;
        $rowNum++;
    } else {
        foreach ($rows as $row) {
            $values = [
                (string)($row['teamName'] ?? ''),
                (string)($row['userName'] ?? ''),
                (string)($row['driverA'] ?? ''),
                (string)($row['driverB'] ?? ''),
                (string)($row['driverC'] ?? ''),
                (string)($row['driverD'] ?? ''),
                (string)($row['entryDate'] ?? ''),
            ];

            $styles = [3, 3, 4, 5, 6, 7, 3];

            foreach ($values as $idx => $value) {
                $ref = tc_xlsx_col_letter($idx + 1) . $rowNum;
                $cellsByRow[$rowNum][] = tc_xlsx_cell_xml($ref, $value, $styles[$idx]);
            }

            $rowNum++;
        }
    }

    $dataLastRow = $rowNum - 1;

    foreach ($notes as $note) {
        $text = (string)(($note['marker'] ?? '') . ' ' . ($note['text'] ?? ''));

        // Keep A as the visible merged-cell value, but also create styled
        // blank cells B:G before merging so Excel has the border style
        // across the entire perimeter of the merged footer row.
        $cellsByRow[$rowNum][] = tc_xlsx_cell_xml('A' . $rowNum, $text, 8);

        for ($col = 2; $col <= 7; $col++) {
            $cellsByRow[$rowNum][] = tc_xlsx_cell_xml(
                tc_xlsx_col_letter($col) . $rowNum,
                '',
                8
            );
        }

        $mergeRanges[] = 'A' . $rowNum . ':G' . $rowNum;
        $rowNum++;
    }

    ksort($cellsByRow, SORT_NUMERIC);

    $sheetRows = '';
    foreach ($cellsByRow as $r => $parts) {
        $height = ($r === 1) ? '24' : (($r === 2) ? '20' : (($r > $dataLastRow) ? '16' : '18'));
        $sheetRows .= '<row r="' . (int)$r . '" ht="' . $height . '" customHeight="1">'
            . implode('', $parts)
            . '</row>';
    }

    $mergeXml = '';
    if (!empty($mergeRanges)) {
        $mergeXml = '<mergeCells count="' . count($mergeRanges) . '">';

        foreach ($mergeRanges as $range) {
            $mergeXml .= '<mergeCell ref="' . tc_xlsx_xml($range) . '"/>';
        }

        $mergeXml .= '</mergeCells>';
    }

    $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/>'
        . '<cols>'
        . '<col min="1" max="1" width="28" customWidth="1"/>'
        . '<col min="2" max="2" width="22" customWidth="1"/>'
        . '<col min="3" max="6" width="18" customWidth="1"/>'
        . '<col min="7" max="7" width="22" customWidth="1"/>'
        . '</cols>'
        . '<sheetData>' . $sheetRows . '</sheetData>'
        . $mergeXml
        . '<pageMargins left="0.25" right="0.25" top="0.25" bottom="0.25" header="0.3" footer="0.3"/>'
        . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
        . '</worksheet>';

    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="3">'
        . '<font><sz val="13"/><name val="Arial"/></font>'
        . '<font><b/><sz val="13"/><name val="Arial"/></font>'
        . '<font><sz val="9"/><name val="Arial"/></font>'
        . '</fonts>'
        . '<fills count="8">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFABF8F"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFB7DEE8"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9D9D9"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFC4BD97"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFB8CCE4"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFD8E4BC"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="9">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Team Chart" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';

    $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $rootRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
        . '</Relationships>';

    $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
        . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
        . '</Types>';

    $created = gmdate('Y-m-d\TH:i:s\Z');

    $coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:creator>Manlius Racing League</dc:creator>'
        . '<cp:lastModifiedBy>Manlius Racing League</cp:lastModifiedBy>'
        . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>'
        . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:modified>'
        . '</cp:coreProperties>';

    $appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
        . '<Application>Manlius Racing League</Application>'
        . '</Properties>';

    $xlsxBinary = tc_zip_from_strings([
        '[Content_Types].xml' => $contentTypesXml,
        '_rels/.rels' => $rootRelsXml,
        'docProps/core.xml' => $coreXml,
        'docProps/app.xml' => $appXml,
        'xl/workbook.xml' => $workbookXml,
        'xl/_rels/workbook.xml.rels' => $workbookRelsXml,
        'xl/styles.xml' => $stylesXml,
        'xl/worksheets/sheet1.xml' => $worksheetXml,
    ]);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Content-Length: ' . strlen($xlsxBinary));

    echo $xlsxBinary;
    exit;
}

// ---------- load years + segments from DB ----------
$years    = [];
$segments = [];

try {
    if (isset($dbo) && $dbo instanceof PDO) {
        $stmt  = $dbo->query("SELECT year FROM years WHERE year > 0 ORDER BY year ASC");
        $years = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];

        $stmt     = $dbo->query("SELECT segment FROM segments ORDER BY segment ASC");
        $segments = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];

    } elseif (isset($dbconnect)) {
        $res = mysqli_query($dbconnect, "SELECT year FROM years WHERE year > 0 ORDER BY year ASC");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $years[] = $r['year'];
        }

        $res = mysqli_query($dbconnect, "SELECT segment FROM segments ORDER BY segment ASC");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $segments[] = $r['segment'];
        }
    }
} catch (Throwable $e) {
    // fail soft
}

$yearsStr    = array_map('strval', $years);
$segmentsStr = array_map('strval', $segments);

// ---------- defaults from admin_setup (config_mrl.php) ----------
$defaultYear = '';
if (isset($raceYear) && valid_year($raceYear) && in_array((string)$raceYear, $yearsStr, true)) {
    $defaultYear = (string)$raceYear;
} elseif (!empty($yearsStr)) {
    $defaultYear = (string)max(array_map('intval', $yearsStr));
} else {
    $defaultYear = date('Y');
}

$defaultSegment = '';
if (isset($segment) && valid_segment($segment) && in_array((string)$segment, $segmentsStr, true)) {
    $defaultSegment = (string)$segment;
} elseif (!empty($segmentsStr)) {
    $defaultSegment = (string)$segmentsStr[0];
} else {
    $defaultSegment = 'S1';
}

// ---------- PRG / request state ----------
$hasPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$postAction  = $hasPost ? (string)($_POST['action'] ?? 'show') : '';
$postYear    = $hasPost ? (string)($_POST['year'] ?? '') : '';
$postSegment = $hasPost ? (string)($_POST['segment'] ?? '') : '';

$self = basename($_SERVER['PHP_SELF']);

if ($hasPost && $postAction === 'show') {
    $useYear = (valid_year($postYear) && in_array($postYear, $yearsStr, true))
        ? $postYear
        : $defaultYear;

    $useSeg = (valid_segment($postSegment) && in_array($postSegment, $segmentsStr, true))
        ? $postSegment
        : $defaultSegment;

    $_SESSION['teamchart_year']    = $useYear;
    $_SESSION['teamchart_segment'] = $useSeg;
    $_SESSION['teamchart_has']     = true;
    $_SESSION['teamchart_from_prg'] = true;

    header("Location: {$self}", true, 303);
    exit;
}

$hasSelection = (isset($_SESSION['teamchart_has']) && $_SESSION['teamchart_has'] === true);

if (!$hasPost && empty($_SESSION['teamchart_from_prg'])) {
    $_SESSION['teamchart_year']    = $defaultYear;
    $_SESSION['teamchart_segment'] = $defaultSegment;
    $_SESSION['teamchart_has']     = true;
    $hasSelection = true;
}

unset($_SESSION['teamchart_from_prg']);

$selectedYear    = $defaultYear;
$selectedSegment = $defaultSegment;

if ($hasSelection) {
    $sy = (string)($_SESSION['teamchart_year'] ?? '');
    $ss = (string)($_SESSION['teamchart_segment'] ?? '');

    if (valid_year($sy) && in_array($sy, $yearsStr, true)) {
        $selectedYear = $sy;
    }
    if (valid_segment($ss) && in_array($ss, $segmentsStr, true)) {
        $selectedSegment = $ss;
    }
}

$isExcelPost = ($hasPost && $postAction === 'excel');
if ($isExcelPost) {
    $excelYear = (valid_year($postYear) && in_array($postYear, $yearsStr, true)) ? $postYear : $selectedYear;
    $excelSeg  = (valid_segment($postSegment) && in_array($postSegment, $segmentsStr, true)) ? $postSegment : $selectedSegment;

    $_SESSION['teamchart_year']    = $excelYear;
    $_SESSION['teamchart_segment'] = $excelSeg;
    $_SESSION['teamchart_has']     = true;

    $selectedYear = $excelYear;
    $selectedSegment = $excelSeg;
}

$segmentLabel = tc_segment_label($selectedYear, $selectedSegment);

// ---------- open-pick privacy gating ----------
$currentRaceYear = isset($raceYear) ? (string)$raceYear : '';
$userTs = time();
$lockTs = 0;
$showSubmittedInsteadOfChart = false;

/*
 * Privacy rule:
 * For the CURRENT season, a segment's driver selections remain private until
 * that segment's first points race starts. This is independent of the legacy
 * manual formLockDate and of scoring-vs-pick-segment state.
 *
 * Current-season deadline lookup fails CLOSED.
 * Previous seasons remain normally viewable.
 */
if ($hasSelection && $selectedYear === $currentRaceYear) {
    $lockTs = tc_segment_pick_deadline_timestamp(
        $dbo ?? null,
        $dbconnect ?? null,
        $selectedYear,
        $selectedSegment
    );

    if ($lockTs <= 0 || $userTs < $lockTs) {
        $showSubmittedInsteadOfChart = true;
    }
}

$lockTimeDisplay = '';
$lockDateDisplay = '';

if ($lockTs > 0) {
    $lockTimeDisplay = date('g:i A', $lockTs);
    $lockDateDisplay = date('n/j/Y', $lockTs);
} elseif ($showSubmittedInsteadOfChart) {
    $lockTimeDisplay = 'the segment deadline';
    $lockDateDisplay = '(canonical schedule unavailable)';
}

// ---------- load picks ----------
$needsChartData = (($hasSelection && !$showSubmittedInsteadOfChart) || $isExcelPost);

$picks   = [];
$dbError = '';

if ($needsChartData) {
    try {
        if (isset($dbo) && $dbo instanceof PDO) {
            $sql = "
                SELECT
                    up.pickID,
                    up.pick_type,
                    up.supersedes_pickID,
                    up.effective_race,
                    up.userID,
                    up.teamName,
                    COALESCE(u.userName, '') AS userName,
                    up.driverA,
                    up.driverB,
                    up.driverC,
                    up.driverD,
                    up.entryDate,
                    EXISTS(SELECT 1 FROM user_picks_history h WHERE h.userID=up.userID AND h.raceYear=up.raceYear AND h.segment=up.segment AND h.pick_type='ADJ' AND h.formID LIKE 'admin_pick_adjustment.php%') AS admin_adjusted
                FROM user_picks up
                LEFT JOIN users u ON u.userID = up.userID
                WHERE up.raceYear = :year
                  AND up.segment = :segment
                  AND COALESCE(u.userName, '') != 'MRL'
                ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC
            ";

            $stmt = $dbo->prepare($sql);
            $stmt->execute([
                ':year'    => $selectedYear,
                ':segment' => $selectedSegment
            ]);

            $picks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif (isset($dbconnect)) {
            $sql = "
                SELECT
                    up.pickID,
                    up.pick_type,
                    up.supersedes_pickID,
                    up.effective_race,
                    up.userID,
                    up.teamName,
                    COALESCE(u.userName, '') AS userName,
                    up.driverA,
                    up.driverB,
                    up.driverC,
                    up.driverD,
                    up.entryDate,
                    EXISTS(SELECT 1 FROM user_picks_history h WHERE h.userID=up.userID AND h.raceYear=up.raceYear AND h.segment=up.segment AND h.pick_type='ADJ' AND h.formID LIKE 'admin_pick_adjustment.php%') AS admin_adjusted
                FROM user_picks up
                LEFT JOIN users u ON u.userID = up.userID
                WHERE up.raceYear = ?
                  AND up.segment = ?
                  AND COALESCE(u.userName, '') != 'MRL'
                ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC
            ";

            $stmt = mysqli_prepare($dbconnect, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $selectedYear, $selectedSegment);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($res && ($r = mysqli_fetch_assoc($res))) {
                    $picks[] = $r;
                }
                mysqli_stmt_close($stmt);
            } else {
                $dbError = 'Unable to prepare picks query.';
            }
        } else {
            $dbError = 'Database connection not available.';
        }
    } catch (Throwable $e) {
        $dbError = 'Database error while loading picks.';
    }
}

$chartContext = ['htmlRows' => [], 'excelRows' => [], 'notes' => []];
if (!empty($picks)) {
    $chartContext = tc_build_chart_context($picks, $selectedYear);
}

// ---------- EXCEL EXPORT ----------
if ($isExcelPost) {
    if ($showSubmittedInsteadOfChart) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Team Chart for {$selectedYear} / {$selectedSegment} will be available at {$lockTimeDisplay} on {$lockDateDisplay}";
        exit;
    }

    if ($dbError) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo $dbError;
        exit;
    }

    $title = $selectedYear . ' ' . $segmentLabel . ' Team Chart';
    send_excel_xlsx(
        "Team_Chart_{$selectedYear}_{$selectedSegment}_" . tc_generation_stamp(),
        $chartContext['excelRows'],
        $title,
        $chartContext['notes']
    );
}

?>
<!DOCTYPE html>
<html class="mrl-theme-<?php echo h($teamChartTheme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Team Chart</title>
    <link rel="stylesheet" href="/mrl-styles.css?v=20260123_prg1">
    <link rel="stylesheet" href="/mrl_team/mrl_shared_theme.css?v=001">

    <style>
        /* v027: crossfade between page loads (Chrome/Edge/Safari); ignored by other browsers */
        @view-transition {
            navigation: auto;
        }

        .teamchart-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .teamchart-actionbtn {
            height: 32px;
            padding: 0 12px;
            border: 1px solid #999;
            background: #eee;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
            font-size: 13pt;
            line-height: normal;
        }

        .teamchart-actions {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            margin-left: auto;
        }

        .teamchart-row {
            margin-left: auto;
            margin-right: auto;
        }

        .teamchart-navpair {
            display: inline-flex;
            gap: 4px;
            align-items: center;
        }

        .teamchart-navpair .teamchart-actionbtn {
            min-width: 42px;
            padding-left: 8px;
            padding-right: 8px;
        }

        .teamchart-rd-merged {
            text-align: center;
            vertical-align: middle;
        }

        .teamchart-table,
        .teamchart-table th,
        .teamchart-table td {
            font-family: Arial, sans-serif !important;
            font-size: 13pt !important;
            line-height: 140% !important;
        }

        .teamchart-table td.teamchart-notes-row,
        .teamchart-table td.teamchart-notes-row .teamchart-note-line {
            font-family: Arial, sans-serif !important;
            font-size: 12px !important;
            line-height: 1.35 !important;
            text-align: left !important;
        }

        .teamchart-table td.teamchart-notes-row {
            padding: 8px 12px !important;
            background: #fabf8f !important;
            color: #000000 !important;
        }

        .teamchart-note-line + .teamchart-note-line {
            margin-top: 4px;
        }

        /* =========================================================
           v022 — Team Chart report-control unification
           Visual baseline: Weekly Standings.
           ========================================================= */

        .teamchart-container {
            width: 85% !important;
            max-width: 1600px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        .teamchart-form {
            margin: 4px 0 6px 0 !important;
        }

        .teamchart-row {
            gap: 6px 10px !important;
        }

        .teamchart-select {
            width: 120px !important;
            height: 28px !important;
            box-sizing: border-box !important;
            font: 16px/1.2 Arial, Helvetica, sans-serif !important;
            padding: 1px 8px !important;
            border: 1px solid #999 !important;
            border-radius: 3px !important;
            background: #f2f2f2 !important;
            color: #111 !important;
            box-shadow: none !important;
        }

        .teamchart-actionbtn {
            min-height: 28px !important;
            height: 28px !important;
            box-sizing: border-box !important;
            font: 16px/1.2 Arial, Helvetica, sans-serif !important;
            padding: 1px 8px !important;
            border: 1px solid #999 !important;
            border-radius: 3px !important;
            background: #f2f2f2 !important;
            color: #111 !important;
            box-shadow: none !important;
        }

        .teamchart-actionbtn:hover:not(:disabled) {
            filter: brightness(0.96);
        }

        .teamchart-navpair {
            gap: 4px !important;
        }
        .teamchart-group-divider {
            display: inline-block;
            width: 1px;
            height: 26px;
            margin: 0 3px 0 5px;
            background: rgba(220, 220, 220, 0.72);
            align-self: center;
        }

        .teamchart-navpair .teamchart-actionbtn {
            min-width: 34px !important;
            padding-left: 6px !important;
            padding-right: 6px !important;
        }

        .teamchart-navpair .teamchart-actionbtn:first-child { border-radius: 14px 3px 3px 14px !important; }
        .teamchart-navpair .teamchart-actionbtn:last-child { border-radius: 3px 14px 14px 3px !important; }
        .teamchart-scroll { overflow-x: visible !important; width: 100% !important; }
        .teamchart-table { width: 100% !important; display: table !important; table-layout: auto !important; }
        @media screen and (max-width: 900px) {
            .teamchart-container { width: 100% !important; max-width: none !important; }
            .teamchart-row { flex-wrap: wrap !important; }
            .teamchart-table, .teamchart-table th, .teamchart-table td { font-size: 11px !important; line-height: 1.2 !important; }
            .teamchart-table th, .teamchart-table td { white-space: normal !important; padding: 2px 3px !important; overflow-wrap: anywhere; }
            .teamchart-table td.teamchart-notes-row, .teamchart-table td.teamchart-notes-row .teamchart-note-line { font-size: 10px !important; }
        }

        .teamchart-actions {
            gap: 6px 10px !important;
        }

        .teamchart-actions .teamchart-actionbtn {
            min-width: 92px !important;
            border: 2px solid #777 !important;
        }

        #btnLive {
            min-width: 66px !important;
            height: 30px !important;
            padding: 1px 10px !important;
            font-weight: bold !important;
            border-radius: 18px !important;
            background: #d9ecff !important;
            color: #084298 !important;
            border: 3px solid #7db7ff !important;
        }

        #btnLive:hover:not(:disabled) {
            filter: brightness(0.97);
        }

        #btnLive:disabled {
            cursor: default !important;
            opacity: 0.5 !important;
            color: #5f6f82 !important;
            background: #eef5fb !important;
            border-color: #c5d7e7 !important;
            filter: none !important;
        }

        .teamchart-actionbtn:disabled {
            cursor: default !important;
            opacity: 0.5 !important;
            color: #666 !important;
            background: #f3f3f3 !important;
            filter: none !important;
        }

        .teamchart-table {
            width: 100% !important;
            display: table !important;
        }

        @media print {
            @page {
                size: landscape;
                margin: 0.5in;
            }

            html,
            html.mrl-theme-cars,
            html.mrl-theme-starry-night,
            html.mrl-theme-dark,
            html.mrl-theme-light,
            body,
            html.mrl-theme-cars body,
            html.mrl-theme-starry-night body,
            html.mrl-theme-dark body,
            html.mrl-theme-light body {
                background: #ffffff !important;
                background-image: none !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
            }

            .teamchart-no-print,
            .admin-status {
                display: none !important;
            }

            .teamchart-container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
            }

            .teamchart-scroll {
                overflow: visible !important;
            }

            .teamchart-table {
                width: 100% !important;
                margin: 0 auto !important;
                display: table !important;
            }
        }    </style>
</head>
<body>

<?php echo $adminStatusLine; ?>

<div class="teamchart-container">

<?php
$chartDisplayed = ($hasSelection && !$showSubmittedInsteadOfChart && $dbError === '');
?>

    <form id="teamchartForm" method="post" class="teamchart-form teamchart-no-print" action="<?php echo h($self); ?>">
        <input type="hidden" name="action" value="show">

        <div class="teamchart-row">
            <button type="button"
                    id="btnLive"
                    class="teamchart-actionbtn"
                    data-live-year="<?php echo h($defaultYear); ?>"
                    data-live-segment="<?php echo h($defaultSegment); ?>">Live</button>

            <select id="year" name="year" class="teamchart-select" aria-label="Year" required>
                <?php foreach ($yearsStr as $yStr): ?>
                    <option value="<?php echo h($yStr); ?>" <?php echo ($yStr === $selectedYear ? 'selected' : ''); ?>>
                        <?php echo h($yStr); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="teamchart-navpair">
                <button type="button" id="btnPrevYear" class="teamchart-actionbtn" title="Previous year" aria-label="Previous year">◀</button>
                <button type="button" id="btnNextYear" class="teamchart-actionbtn" title="Next year" aria-label="Next year">▶</button>
            </span>

            <span class="teamchart-group-divider" aria-hidden="true"></span>

            <select id="segment" name="segment" class="teamchart-select" aria-label="Segment" required>
                <?php foreach ($segmentsStr as $sStr): ?>
                    <option value="<?php echo h($sStr); ?>" <?php echo ($sStr === $selectedSegment ? 'selected' : ''); ?>>
                        <?php echo h($sStr); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="teamchart-navpair">
                <button type="button" id="btnPrevSegment" class="teamchart-actionbtn" title="Previous segment" aria-label="Previous segment">◀</button>
                <button type="button" id="btnNextSegment" class="teamchart-actionbtn" title="Next segment" aria-label="Next segment">▶</button>
            </span>

            <?php if ($chartDisplayed): ?>
                <span id="chartActions" class="teamchart-actions">
                    <button type="button" id="btnPrint" class="teamchart-actionbtn">Print</button>
                    <button type="button" id="btnExcel" class="teamchart-actionbtn">Spreadsheet</button>
                </span>
            <?php endif; ?>
        </div>
    </form>

    <form id="excelForm" method="post" action="<?php echo h($self); ?>" target="_blank" style="display:none;">
        <input type="hidden" name="action" value="excel">
        <input type="hidden" name="year" id="excelYear" value="">
        <input type="hidden" name="segment" id="excelSegment" value="">
    </form>

<?php if ($hasSelection): ?>

    <?php if ($showSubmittedInsteadOfChart): ?>

        <div style="color:red; font-size:16pt; margin:10px 0;">
            Team Chart for <?php echo h($selectedYear); ?> / <?php echo h($selectedSegment); ?>
            will be available at <?php echo h($lockTimeDisplay); ?> on <?php echo h($lockDateDisplay); ?>
        </div>

        <?php include 'submitted_teams.php'; ?>

    <?php else: ?>

        <?php if ($dbError): ?>
            <div class="notice-error"><?php echo h($dbError); ?></div>
        <?php else: ?>

            <div class="teamchart-scroll">
                <table class="teamchart-table">
                    <thead>
                        <tr class="teamchart-title-row">
                            <th colspan="7"><?php echo h($selectedYear); ?> <?php echo h($segmentLabel); ?> Team Chart</th>
                        </tr>
                        <tr class="teamchart-header-row">
                            <th>Team</th>
                            <th>Owner</th>
                            <th>Group A</th>
                            <th>Group B</th>
                            <th>Group C</th>
                            <th>Group D</th>
                            <th>Submission Time</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($chartContext['htmlRows'])): ?>
                            <tr>
                                <td colspan="7" class="teamchart-empty">No picks found for this year / segment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($chartContext['htmlRows'] as $entry): ?>
                                <?php if ($entry['render_type'] === 'single'): ?>
                                    <?php
                                        $row = $entry['current'];
                                        $pickType = strtoupper(trim((string)($entry['pick_type'] ?? 'SEG')));
                                        $marker = (string)($entry['marker'] ?? '');
                                        $driverA = trim((string)($row['driverA'] ?? ''));
                                        $driverB = trim((string)($row['driverB'] ?? ''));
                                        $driverC = trim((string)($row['driverC'] ?? ''));
                                        $driverD = trim((string)($row['driverD'] ?? ''));

                                        $ownerDisplay = trim((string)($row['userName'] ?? ''));
                                        $timeDisplay = trim((string)($row['entryDate'] ?? ''));

                                        if ($pickType === 'LP' && $marker !== '') {
                                            $driverA .= ' ' . $marker;
                                            $driverB .= ' ' . $marker;
                                            $driverC .= ' ' . $marker;
                                            $driverD .= ' ' . $marker;
                                            $ownerDisplay .= ' ' . $marker;
                                            $timeDisplay .= ' ' . $marker;
                                        }

                                        $teamDisplay = trim((string)($row['teamName'] ?? ''));
                                        if ($marker !== '') {
                                            $teamDisplay .= ' ' . $marker;
                                        }
                                    ?>
                                    <tr>
                                        <td class="teamchart-cell-team"><?php echo h($teamDisplay); ?></td>
                                        <td class="teamchart-cell-owner"><?php echo h($ownerDisplay); ?></td>
                                        <td class="teamchart-cell-a"><?php echo h($driverA); ?></td>
                                        <td class="teamchart-cell-b"><?php echo h($driverB); ?></td>
                                        <td class="teamchart-cell-c"><?php echo h($driverC); ?></td>
                                        <td class="teamchart-cell-d"><?php echo h($driverD); ?></td>
                                        <td class="teamchart-cell-time"><?php echo h($timeDisplay); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                        $row = $entry['current'];
                                        $reference = is_array($entry['reference'] ?? null) ? $entry['reference'] : [];
                                        $marker = (string)($entry['marker'] ?? '');
                                        $changedField = (string)($entry['changed_field'] ?? '');
                                        $fieldOrder = ['driverA', 'driverB', 'driverC', 'driverD'];
                                        $changedCellClass = $changedField === 'driverA'
                                            ? 'teamchart-cell-a'
                                            : ($changedField === 'driverB'
                                                ? 'teamchart-cell-b'
                                                : ($changedField === 'driverC'
                                                    ? 'teamchart-cell-c'
                                                    : 'teamchart-cell-d'));
                                        $teamDisplay = trim((string)($row['teamName'] ?? ''));
                                        if ($marker !== '') {
                                            $teamDisplay .= ' ' . $marker;
                                        }
                                    ?>
                                    <tr>
                                        <td class="teamchart-cell-team" rowspan="2"><?php echo h($teamDisplay); ?></td>
                                        <td class="teamchart-cell-owner" rowspan="2"><?php echo h($row['userName'] ?? ''); ?></td>

                                        <?php foreach ($fieldOrder as $field): ?>
                                            <?php if ($field === $changedField): ?>
                                                <td class="<?php echo h($changedCellClass); ?>"><?php echo h($reference[$field] ?? ''); ?></td>
                                            <?php else: ?>
                                                <?php
                                                    $unchangedClass = $field === 'driverA'
                                                        ? 'teamchart-cell-a'
                                                        : ($field === 'driverB'
                                                            ? 'teamchart-cell-b'
                                                            : ($field === 'driverC'
                                                                ? 'teamchart-cell-c'
                                                                : 'teamchart-cell-d'));
                                                ?>
                                                <td class="<?php echo h($unchangedClass); ?> teamchart-rd-merged" rowspan="2"><?php echo h($row[$field] ?? ''); ?></td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                        <td class="teamchart-cell-time"><?php echo h($reference['entryDate'] ?? $row['entryDate'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="<?php echo h($changedCellClass); ?>"><?php echo h(trim(((string)($row[$changedField] ?? '')) . ' ' . $marker)); ?></td>
                                        <td class="teamchart-cell-time"><?php echo h($row['entryDate'] ?? ''); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($chartContext['notes'])): ?>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="teamchart-notes-row" style="background:#fabf8f !important; color:#000000 !important;">
                                    <?php foreach ($chartContext['notes'] as $note): ?>
                                        <div class="teamchart-note-line"><?php echo h($note['marker'] . ' ' . $note['text']); ?></div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>

        <?php endif; ?>

    <?php endif; ?>

<?php endif; ?>

</div>

<script>
(function () {
    const yearSel = document.getElementById('year');
    const segSel  = document.getElementById('segment');

    const btnPrint = document.getElementById('btnPrint');
    const btnExcel = document.getElementById('btnExcel');
    const btnPrevYear = document.getElementById('btnPrevYear');
    const btnNextYear = document.getElementById('btnNextYear');
    const btnPrevSegment = document.getElementById('btnPrevSegment');
    const btnNextSegment = document.getElementById('btnNextSegment');
    const btnLive = document.getElementById('btnLive');
    const teamchartForm = document.getElementById('teamchartForm');

    const excelForm = document.getElementById('excelForm');
    const excelYear = document.getElementById('excelYear');
    const excelSeg  = document.getElementById('excelSegment');

    function submitSelection() {
        if (!teamchartForm) return;

        if (typeof teamchartForm.requestSubmit === 'function') {
            teamchartForm.requestSubmit();
        } else {
            teamchartForm.submit();
        }
    }

    if (yearSel) yearSel.addEventListener('change', submitSelection);
    if (segSel)  segSel.addEventListener('change', submitSelection);

    function moveSelect(select, direction) {
        if (!select) return;

        const nextIndex = select.selectedIndex + direction;
        if (nextIndex < 0 || nextIndex >= select.options.length) return;

        select.selectedIndex = nextIndex;
        submitSelection();
    }

    function updateNavButtons() {
        if (btnPrevYear) {
            btnPrevYear.disabled = !yearSel || yearSel.selectedIndex <= 0;
        }
        if (btnNextYear) {
            btnNextYear.disabled = !yearSel || yearSel.selectedIndex >= yearSel.options.length - 1;
        }
        if (btnPrevSegment) {
            btnPrevSegment.disabled = !segSel || segSel.selectedIndex <= 0;
        }
        if (btnNextSegment) {
            btnNextSegment.disabled = !segSel || segSel.selectedIndex >= segSel.options.length - 1;
        }
        if (btnLive) {
            const liveYear = btnLive.dataset.liveYear || '';
            const liveSegment = btnLive.dataset.liveSegment || '';
            btnLive.disabled = !!yearSel && !!segSel
                && yearSel.value === liveYear
                && segSel.value === liveSegment;
        }
    }

    if (btnPrevYear) {
        btnPrevYear.addEventListener('click', function () { moveSelect(yearSel, -1); });
    }

    if (btnNextYear) {
        btnNextYear.addEventListener('click', function () { moveSelect(yearSel, 1); });
    }

    if (btnPrevSegment) {
        btnPrevSegment.addEventListener('click', function () { moveSelect(segSel, -1); });
    }

    if (btnNextSegment) {
        btnNextSegment.addEventListener('click', function () { moveSelect(segSel, 1); });
    }

    if (btnLive) {
        btnLive.addEventListener('click', function () {
            if (!yearSel || !segSel) return;

            const liveYear = btnLive.dataset.liveYear || '';
            const liveSegment = btnLive.dataset.liveSegment || '';

            if (liveYear !== '') yearSel.value = liveYear;
            if (liveSegment !== '') segSel.value = liveSegment;

            submitSelection();
        });
    }

    updateNavButtons();

    function syncControlRowToChart() {
        const chartTable = document.querySelector('.teamchart-table');
        const controlRow = document.querySelector('.teamchart-row');

        if (!chartTable || !controlRow) return;

        const width = chartTable.getBoundingClientRect().width;
        if (width > 0) {
            controlRow.style.width = width + 'px';
            controlRow.style.maxWidth = '100%';
        }
    }

    window.addEventListener('resize', syncControlRowToChart);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncControlRowToChart);
    } else {
        syncControlRowToChart();
    }
    window.setTimeout(syncControlRowToChart, 0);

    if (btnExcel && excelForm && excelYear && excelSeg) {
        btnExcel.addEventListener('click', function () {
            excelYear.value = yearSel.value || '';
            excelSeg.value  = segSel.value || '';
            excelForm.submit();
        });
    }

    if (btnPrint) {
        btnPrint.addEventListener('click', function () {
            const oldTitle = document.title;

            const y = yearSel.value || '';
            const s = segSel.value || '';

            function pad(value, width) {
                return String(value).padStart(width, '0');
            }

            const now = new Date();
            const parts = new Intl.DateTimeFormat('en-US', {
                timeZone: 'America/New_York',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).formatToParts(now);

            const values = {};
            parts.forEach(function (part) {
                if (part.type !== 'literal') values[part.type] = part.value;
            });

            const hour = values.hour === '24' ? '00' : values.hour;
            const generationStamp =
                values.year + values.month + values.day + '_' +
                hour + values.minute + values.second +
                pad(now.getMilliseconds(), 3);

            const fileTitle = 'Team_Chart_' + y + '_' + s + '_' + generationStamp;

            document.title = fileTitle;
            window.print();

            setTimeout(function () {
                document.title = oldTitle;
            }, 500);
        });
    }
})();
</script>

</body>
</html>
