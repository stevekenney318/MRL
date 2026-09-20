<?php
declare(strict_types=1);

/**
 * install_team_chart_spreadsheet_cleanup_v001_20260920_013721pm.php
 *
 * VERSION: v001
 * GENERATED: 9/20/2026 1:37:21 pm ET
 *
 * PURPOSE:
 * - Team Chart cleanup, Pass 2.
 * - Updates public_html/team_chart.php from v022 to v023.
 * - Replaces PhpSpreadsheet export with a self-contained pure-PHP XLSX writer.
 * - Adds a visible divider between the Year controls and Segment controls.
 *
 * SAFETY:
 * - Modifies only team_chart.php.
 * - Does NOT delete /vendor or Composer files.
 * - Candidate and installed files are linted through shell_exec('php -l ...').
 * - Creates backup before Apply and supports rollback.
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v001';
const EXPECTED_SOURCE_VERSION = 'v022';
const TARGET_VERSION = 'v023';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/team_chart.php';
$backupDir = $docRoot . '/_installer_backups/team_chart_spreadsheet_cleanup_20260920_013721pm';
$backupFile = $backupDir . '/team_chart.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function lint_php_file(string $path): array
{
    if (!function_exists('shell_exec')) {
        return ['available'=>false,'ok'=>false,'output'=>'shell_exec() is not available.'];
    }

    $output = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');

    if ($output === null) {
        return ['available'=>true,'ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    }

    $output = trim((string)$output);

    return [
        'available'=>true,
        'ok'=>stripos($output, 'No syntax errors detected') !== false,
        'output'=>$output,
    ];
}

function current_version(string $content): string
{
    if (preg_match('/\*\s*VERSION:\s*(v\d+)/i', $content, $m)) {
        return (string)$m[1];
    }
    return '';
}

function replace_once(string $subject, string $search, string $replace, string $label, array &$errors): string
{
    $count = substr_count($subject, $search);

    if ($count !== 1) {
        $errors[] = $label . ': expected exactly 1 match, found ' . $count . '.';
        return $subject;
    }

    $pos = strpos($subject, $search);
    if ($pos === false) {
        $errors[] = $label . ': match disappeared unexpectedly.';
        return $subject;
    }

    return substr($subject, 0, $pos)
        . $replace
        . substr($subject, $pos + strlen($search));
}

function tc_pure_xlsx_block(): string
{
    return <<<'PHPBLOCK'
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
        $cellsByRow[$rowNum][] = tc_xlsx_cell_xml('A' . $rowNum, $text, 8);
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
PHPBLOCK;
}

function build_candidate(string $source, array &$errors): string
{
    $out = $source;

    $out = replace_once(
        $out,
        ' * VERSION: v022',
        ' * VERSION: v023',
        'Version header',
        $errors
    );

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace(
            $m[0],
            ' * LAST MODIFIED: 9/20/2026 1:37:21 pm ET' . PHP_EOL,
            $out
        );
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelogInsert = " * CHANGELOG:\n *\n"
        . " * v023 (9/20/2026 1:37:21 pm ET)\n"
        . " * - EXPORT: Replaced PhpSpreadsheet/Composer XLSX export with a self-contained pure-PHP XLSX writer based on the proven Weekly Standings approach.\n"
        . " * - EXPORT: Preserves Team Chart title/header styling, column colors, borders, column widths, row heights, notes, frozen top rows, and timestamped filenames.\n"
        . " * - UI: Added a subtle divider between the Year navigation group and Segment navigation group for clearer visual separation.\n"
        . " * - CLEANUP: team_chart.php no longer requires vendor/autoload.php or PhpSpreadsheet. Vendor files are NOT deleted by this installer.\n"
        . " * - PRESERVE: Privacy gate, LP/RD display, print behavior, navigation, chart width, themes, and all database queries remain unchanged.\n"
        . " *\n";

    $out = replace_once(
        $out,
        $changelogAnchor,
        $changelogInsert,
        'Changelog anchor',
        $errors
    );

    $startNeedle = "/**\n * Sends a real XLSX file (no warning) using PhpSpreadsheet.";
    $endNeedle = "// ---------- load years + segments from DB ----------";

    $startPos = strpos($out, $startNeedle);
    $endPos = strpos($out, $endNeedle);

    if ($startPos === false || $endPos === false || $endPos <= $startPos) {
        $errors[] = 'Spreadsheet export block boundaries were not found exactly as expected.';
    } else {
        $replacement = tc_pure_xlsx_block() . "\n\n";
        $out = substr($out, 0, $startPos)
            . $replacement
            . substr($out, $endPos);
    }

    $yearNavAnchor = <<<'HTML'
            <span class="teamchart-navpair">
                <button type="button" id="btnPrevYear" class="teamchart-actionbtn" title="Previous year">&lt;&lt;</button>
                <button type="button" id="btnNextYear" class="teamchart-actionbtn" title="Next year">&gt;&gt;</button>
            </span>

            <select id="segment" name="segment" class="teamchart-select" aria-label="Segment" required>
HTML;

    $yearNavReplacement = <<<'HTML'
            <span class="teamchart-navpair">
                <button type="button" id="btnPrevYear" class="teamchart-actionbtn" title="Previous year">&lt;&lt;</button>
                <button type="button" id="btnNextYear" class="teamchart-actionbtn" title="Next year">&gt;&gt;</button>
            </span>

            <span class="teamchart-group-divider" aria-hidden="true"></span>

            <select id="segment" name="segment" class="teamchart-select" aria-label="Segment" required>
HTML;

    $out = replace_once(
        $out,
        $yearNavAnchor,
        $yearNavReplacement,
        'Year/Segment divider anchor',
        $errors
    );

    $cssAnchor = <<<'CSS'
        .teamchart-navpair {
            gap: 4px !important;
        }

CSS;

    $cssReplacement = $cssAnchor . <<<'CSS'
        .teamchart-group-divider {
            display: inline-block;
            width: 1px;
            height: 26px;
            margin: 0 3px 0 5px;
            background: rgba(220, 220, 220, 0.72);
            align-self: center;
        }

CSS;

    $out = replace_once(
        $out,
        $cssAnchor,
        $cssReplacement,
        'Divider CSS anchor',
        $errors
    );

    return $out;
}

function candidate_lint(string $candidate, string $targetDir): array
{
    $tmp = @tempnam($targetDir, '.mrl_team_chart_lint_');

    if ($tmp === false) {
        $tmp = @tempnam(sys_get_temp_dir(), 'mrl_team_chart_lint_');
    }

    if ($tmp === false) {
        return ['available'=>true,'ok'=>false,'output'=>'Unable to create temporary lint file.'];
    }

    if (@file_put_contents($tmp, $candidate, LOCK_EX) === false) {
        @unlink($tmp);
        return ['available'=>true,'ok'=>false,'output'=>'Unable to write temporary lint file.'];
    }

    $lint = lint_php_file($tmp);
    @unlink($tmp);

    return $lint;
}

$action = (string)($_POST['action'] ?? '');
$message = '';
$messageClass = 'info';

$targetExists = is_file($target);
$targetWritable = $targetExists && is_writable($target);
$source = $targetExists ? (string)@file_get_contents($target) : '';
$detectedVersion = $source !== '' ? current_version($source) : '';
$alreadyInstalled = ($detectedVersion === TARGET_VERSION);

$patchErrors = [];
$candidate = '';
$candidateLint = ['available'=>function_exists('shell_exec'),'ok'=>false,'output'=>'Not run.'];

if ($targetExists && $detectedVersion === EXPECTED_SOURCE_VERSION) {
    $candidate = build_candidate($source, $patchErrors);

    if (empty($patchErrors)) {
        $candidateLint = candidate_lint($candidate, dirname($target));
    }
}

$canApply =
    $targetExists
    && $targetWritable
    && $detectedVersion === EXPECTED_SOURCE_VERSION
    && empty($patchErrors)
    && !empty($candidateLint['available'])
    && !empty($candidateLint['ok']);

if ($action === 'apply') {
    if (!$canApply) {
        $message = 'Apply blocked: preflight is not fully PASS.';
        $messageClass = 'bad';
    } else {
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            $message = 'Apply blocked: could not create backup directory.';
            $messageClass = 'bad';
        } elseif (is_file($backupFile)) {
            $message = 'Apply blocked: backup already exists for this installer run.';
            $messageClass = 'bad';
        } elseif (!@copy($target, $backupFile)) {
            $message = 'Apply blocked: could not create backup.';
            $messageClass = 'bad';
        } else {
            @chmod($backupFile, 0644);

            $tmpTarget = $target . '.mrl_tmp_' . uniqid('', true);

            if (@file_put_contents($tmpTarget, $candidate, LOCK_EX) === false) {
                @unlink($tmpTarget);
                $message = 'Apply failed: could not write temporary target file.';
                $messageClass = 'bad';
            } else {
                @chmod($tmpTarget, 0644);
                $tmpLint = lint_php_file($tmpTarget);

                if (empty($tmpLint['ok'])) {
                    @unlink($tmpTarget);
                    $message = 'Apply blocked: temporary replacement failed PHP lint. ' . $tmpLint['output'];
                    $messageClass = 'bad';
                } elseif (!@rename($tmpTarget, $target)) {
                    @unlink($tmpTarget);
                    $message = 'Apply failed: atomic replacement could not be completed.';
                    $messageClass = 'bad';
                } else {
                    @chmod($target, 0644);
                    $installedLint = lint_php_file($target);

                    if (empty($installedLint['ok'])) {
                        @copy($backupFile, $target);
                        @chmod($target, 0644);
                        $message = 'Installed file failed lint and backup was automatically restored. ' . $installedLint['output'];
                        $messageClass = 'bad';
                    } else {
                        $message = 'PASS — team_chart.php v023 installed and passed PHP lint.';
                        $messageClass = 'good';
                    }
                }
            }
        }
    }
}

if ($action === 'rollback') {
    if (!is_file($backupFile)) {
        $message = 'Rollback unavailable: backup file was not found.';
        $messageClass = 'bad';
    } else {
        $backupLint = lint_php_file($backupFile);

        if (empty($backupLint['ok'])) {
            $message = 'Rollback blocked: backup file failed PHP lint. ' . $backupLint['output'];
            $messageClass = 'bad';
        } elseif (!@copy($backupFile, $target)) {
            $message = 'Rollback failed: could not restore backup.';
            $messageClass = 'bad';
        } else {
            @chmod($target, 0644);
            $restoredLint = lint_php_file($target);

            if (empty($restoredLint['ok'])) {
                $message = 'Rollback copy completed, but restored file did not pass lint. ' . $restoredLint['output'];
                $messageClass = 'bad';
            } else {
                $message = 'Rollback complete — original team_chart.php restored and lint passed.';
                $messageClass = 'good';
            }
        }
    }
}

$targetExists = is_file($target);
$targetWritable = $targetExists && is_writable($target);
$source = $targetExists ? (string)@file_get_contents($target) : '';
$detectedVersion = $source !== '' ? current_version($source) : '';
$alreadyInstalled = ($detectedVersion === TARGET_VERSION);
$backupExists = is_file($backupFile);

$installedLint = $targetExists
    ? lint_php_file($target)
    : ['available'=>function_exists('shell_exec'),'ok'=>false,'output'=>'Target missing.'];

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Team Chart Spreadsheet Cleanup</title>
<style>
:root {
    color-scheme: dark;
    --bg:#101010; --panel:#1b1b1b; --line:#3d3d3d; --text:#eeeeee;
    --muted:#aaaaaa; --good:#66df8d; --bad:#ff7474; --gold:#f2c98e;
}
* { box-sizing:border-box; }
body {
    margin:0; padding:22px; background:var(--bg); color:var(--text);
    font-family:Arial,Helvetica,sans-serif;
}
.wrap { max-width:1100px; margin:0 auto; }
h1 { margin:0 0 6px; color:var(--gold); }
h2 { margin:0 0 10px; }
.card {
    margin:14px 0; padding:16px; background:var(--panel);
    border:1px solid var(--line); border-radius:14px;
}
.notice {
    margin:14px 0; padding:12px 14px; border-radius:10px;
    border:1px solid #36506f; background:#142033;
}
.notice.good { border-color:#327a4b; background:#13271a; color:#a8efbf; }
.notice.bad { border-color:#983f3f; background:#2a1515; color:#ffb0b0; }
.pass { color:var(--good); font-weight:800; }
.fail { color:var(--bad); font-weight:800; }
.muted { color:var(--muted); }
code { background:#282828; padding:2px 5px; border-radius:5px; }
table { width:100%; border-collapse:collapse; }
th,td { padding:9px 10px; border-bottom:1px solid #333; text-align:left; vertical-align:top; }
th { color:var(--gold); }
.buttons { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
button,.btn {
    border:0; border-radius:9px; padding:10px 16px; color:white;
    font-weight:700; cursor:pointer; text-decoration:none; display:inline-block;
    font-size:14px;
}
.apply { background:#248c4b; }
.neutral { background:#276fca; }
.rollback { background:#a83434; }
button:disabled { opacity:.38; cursor:not-allowed; }
ul { line-height:1.55; }
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Team Chart Spreadsheet Cleanup</h1>
    <div class="muted">Installer <?php echo h(INSTALLER_VERSION); ?> · Pass 2 · generated 9/20/2026 1:37:21 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>What this pass changes</h2>
        <ul>
            <li>Replaces Team Chart PhpSpreadsheet export with the proven pure-PHP XLSX packaging approach used by Weekly Standings.</li>
            <li>Preserves the Team Chart spreadsheet's title, headers, per-column colors, notes, borders, widths, row heights, frozen rows, and timestamped filename.</li>
            <li>Adds a subtle vertical divider between Year controls and Segment controls.</li>
            <li>Removes Team Chart's dependency on <code>vendor/autoload.php</code>.</li>
            <li>Does <strong>not</strong> delete or modify the vendor folder in this pass.</li>
        </ul>
    </div>

    <div class="card">
        <h2>Preflight</h2>
        <table>
            <tr><th>Check</th><th>Status</th><th>Detail</th></tr>
            <tr>
                <td>Target exists</td>
                <td class="<?php echo $targetExists ? 'pass' : 'fail'; ?>"><?php echo $targetExists ? 'PASS' : 'FAIL'; ?></td>
                <td><code><?php echo h($target); ?></code></td>
            </tr>
            <tr>
                <td>Target writable</td>
                <td class="<?php echo $targetWritable ? 'pass' : 'fail'; ?>"><?php echo $targetWritable ? 'PASS' : 'FAIL'; ?></td>
                <td><?php echo $targetWritable ? 'Writable' : 'Not writable'; ?></td>
            </tr>
            <tr>
                <td>Current version</td>
                <td class="<?php echo ($detectedVersion === EXPECTED_SOURCE_VERSION || $alreadyInstalled) ? 'pass' : 'fail'; ?>">
                    <?php echo ($detectedVersion === EXPECTED_SOURCE_VERSION || $alreadyInstalled) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td>Detected <strong><?php echo h($detectedVersion !== '' ? $detectedVersion : '(unknown)'); ?></strong>; expected v022 before Apply, or v023 after Apply.</td>
            </tr>
            <tr>
                <td>Patch signatures</td>
                <td class="<?php echo ($alreadyInstalled || empty($patchErrors)) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || empty($patchErrors)) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td>
                    <?php
                    if ($alreadyInstalled) {
                        echo 'v023 is already installed.';
                    } elseif (empty($patchErrors)) {
                        echo 'All expected v022 anchors found.';
                    } else {
                        echo h(implode(' | ', $patchErrors));
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Candidate PHP lint</td>
                <td class="<?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h($alreadyInstalled ? 'Not needed — installed target is v023.' : (string)$candidateLint['output']); ?></td>
            </tr>
            <tr>
                <td>Installed PHP lint</td>
                <td class="<?php echo !empty($installedLint['ok']) ? 'pass' : 'fail'; ?>">
                    <?php echo !empty($installedLint['ok']) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h((string)$installedLint['output']); ?></td>
            </tr>
            <tr>
                <td>Rollback backup</td>
                <td><?php echo $backupExists ? '<span class="pass">READY</span>' : '<span class="muted">Not created yet</span>'; ?></td>
                <td><code><?php echo h($backupFile); ?></code></td>
            </tr>
        </table>

        <div class="buttons">
            <form method="post">
                <input type="hidden" name="action" value="apply">
                <button class="apply" type="submit" <?php echo $canApply ? '' : 'disabled'; ?>>Apply v023</button>
            </form>

            <a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF'] ?? '')); ?>">Refresh / Preflight</a>
            <a class="btn neutral" href="/team_chart.php" target="_blank" rel="noopener">Open Team Chart</a>

            <form method="post" onsubmit="return confirm('Restore the backed-up v022 Team Chart?');">
                <input type="hidden" name="action" value="rollback">
                <button class="rollback" type="submit" <?php echo $backupExists ? '' : 'disabled'; ?>>Rollback Team Chart</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Production safety</h2>
        <p>
            Target: <code><?php echo h($target); ?></code><br>
            Current detected version: <strong><?php echo h($detectedVersion !== '' ? $detectedVersion : '(unknown)'); ?></strong><br>
            Backup: <code><?php echo h($backupFile); ?></code>
        </p>
        <p class="muted">
            This installer changes only <code>team_chart.php</code>. The vendor folder remains untouched so we can separately audit whether anything else still uses it.
        </p>
    </div>
</div>
</body>
</html>
