<?php
declare(strict_types=1);

/**
 * MRL_install_team_chart_LP_consistency.php
 *
 * VERSION: v001
 * CREATED: 9/9/2026 2:21:06 am ET
 *
 * PURPOSE:
 * Coordinated public-facing Team Chart / Late Pick presentation update.
 *
 * CHANGES:
 * - team_chart.php v018 -> v019
 * - current_segment_chart.php v007 -> v008
 * - submitted_teams.php unversioned -> v001
 *
 * SAFETY:
 * - Exact-version / exact-signature preflight.
 * - Exact backups before any production replacement.
 * - Temporary-file PHP lint before replacement.
 * - Atomic replacement.
 * - Postflight verification.
 * - Automatic all-file rollback if any critical step fails.
 * - Manual rollback button.
 *
 * THIS INSTALLER DOES NOT:
 * - alter database rows
 * - send mail
 * - run cron/scheduler jobs
 * - change config_mrl.php
 * - change the canonical schedule JSON
 */

date_default_timezone_set('America/New_York');

const MRL_INSTALLER_VERSION = 'v001';

$root = __DIR__;
$targets = [
    'team_chart.php' => [
        'path' => $root . '/team_chart.php',
        'from' => 'v018',
        'to' => 'v019',
    ],
    'current_segment_chart.php' => [
        'path' => $root . '/current_segment_chart.php',
        'from' => 'v007',
        'to' => 'v008',
    ],
    'submitted_teams.php' => [
        'path' => $root . '/submitted_teams.php',
        'from' => 'UNVERSIONED',
        'to' => 'v001',
    ],
];

$backupDir = $root . '/_mrl_installer_backups';
$manifestPath = $backupDir . '/team_chart_lp_consistency_v001_manifest.json';

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function read_text(string $path): string {
    $v = @file_get_contents($path);
    return $v === false ? '' : $v;
}

function write_text(string $path, string $data): bool {
    $bytes = @file_put_contents($path, $data, LOCK_EX);
    return $bytes !== false && $bytes === strlen($data);
}

function exact_count(string $haystack, string $needle): int {
    return $needle === '' ? 0 : substr_count($haystack, $needle);
}

function php_lint(string $path): array {
    if (!function_exists('exec')) {
        return ['available'=>false, 'ok'=>true, 'output'=>'exec() unavailable; lint skipped.'];
    }
    $binary = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($binary) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = [];
    $code = 999;
    @exec($cmd, $out, $code);
    return ['available'=>true, 'ok'=>$code === 0, 'output'=>trim(implode("\n", $out))];
}

function replace_once(string $content, string $from, string $to, string $label): array {
    $count = exact_count($content, $from);
    if ($count !== 1) {
        return ['ok'=>false, 'content'=>$content, 'error'=>$label . ' signature count = ' . $count . ' (expected 1)'];
    }
    return ['ok'=>true, 'content'=>str_replace($from, $to, $content), 'error'=>''];
}

function team_chart_patch(string $c): array {
    $r = replace_once($c,
        " * VERSION: v018\n * LAST MODIFIED: 8/24/2026 9:45:04 pm",
        " * VERSION: v019\n * LAST MODIFIED: 9/9/2026 2:21:06 am ET",
        'team_chart version');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        " * CHANGELOG:\n *\n * v018 (8/24/2026 9:45:04 pm)",
        " * CHANGELOG:\n *\n * v019 (9/9/2026 2:21:06 am ET)\n"
        . " * - CONSISTENCY: LP rows now append their marker to Team, Owner, all four drivers, and Submission Time in HTML and spreadsheet output.\n"
        . " * - CONSISTENCY: LP/RD effective-race notes now include the canonical short race name when available, e.g. R28 (World Wide Tech).\n"
        . " * - CONSISTENCY: S4 is Playoffs through 2025 and The Chase beginning in 2026.\n"
        . " * - UI: Standalone chart typography now matches the team.php current-segment chart more closely (Arial, 13pt; compact 12px notes).\n"
        . " * - UI: Adds << / >> segment navigation around the segment selector.\n"
        . " * - EXPORT: Spreadsheet includes chart notes and uses matching Arial typography.\n"
        . " * - EXPORT: Spreadsheet and print/PDF filenames now include current generation timestamp with milliseconds.\n"
        . " * - PRESERVE: Existing privacy gate, RD merged-row display, Approved Exception behavior, PRG flow, and database queries.\n"
        . " *\n * v018 (8/24/2026 9:45:04 pm)",
        'team_chart changelog');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'PHP'
function tc_effective_race_label($value): string
{
    $num = (int)$value;
    if ($num <= 0) {
        return '';
    }
    return 'R' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
}
PHP;

    $replacement = <<<'PHP'
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

        $name = trim((string)(
            $race['mrl_race_name']
            ?? $race['race_name']
            ?? $race['track_name']
            ?? ''
        ));

        if ($name === '') return '';

        $name = str_replace('_', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim((string)$name);

        if (
            stripos($name, 'World Wide Technology') !== false
            || stripos($name, 'World Wide Tech') !== false
        ) {
            return 'World Wide Tech';
        }

        $name = preg_replace('/^NASCAR\s+Cup\s+Series\s+at\s+/i', '', $name);
        return trim((string)$name);
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
PHP;

    $r = replace_once($c, $needle, $replacement, 'team_chart helpers');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "function tc_build_chart_context(array \$rows): array",
        "function tc_build_chart_context(array \$rows, string \$year): array",
        'team_chart context signature');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "\$effectiveRaceLabel = tc_effective_race_label(\$row['effective_race'] ?? 0);",
        "\$effectiveRaceLabel = tc_effective_race_label(\$year, \$row['effective_race'] ?? 0);",
        'team_chart LP effective note');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    // There is a second same call for RD after first replacement; replace remaining exact once.
    $r = replace_once($c,
        "\$effectiveRaceLabel = tc_effective_race_label(\$row['effective_race'] ?? 0);",
        "\$effectiveRaceLabel = tc_effective_race_label(\$year, \$row['effective_race'] ?? 0);",
        'team_chart RD effective note');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "        \$excelRows[] = \$excelRow;",
        "        if (\$pickType === 'LP' && \$marker !== '') {\n"
        . "            foreach (['teamName', 'userName', 'entryDate'] as \$field) {\n"
        . "                \$value = trim((string)(\$excelRow[\$field] ?? ''));\n"
        . "                \$excelRow[\$field] = \$value === '' ? '' : (\$value . ' ' . \$marker);\n"
        . "            }\n"
        . "        }\n"
        . "        \$excelRows[] = \$excelRow;",
        'team_chart spreadsheet LP all-cell marker');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "function send_excel_xlsx(string \$filenameBase, array \$rows, string \$title): void",
        "function send_excel_xlsx(string \$filenameBase, array \$rows, string \$title, array \$notes): void",
        'team_chart excel signature');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "\$sheet->getStyle(\$rangeAll)->getFont()->setName('Century Gothic')->setSize(12);",
        "\$sheet->getStyle(\$rangeAll)->getFont()->setName('Arial')->setSize(13);",
        'team_chart spreadsheet font');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'PHP'
        $lastRow    = $r - 1;
        $rangeAll   = "A1:G{$lastRow}";
        $rangeTitle = "A1:G1";
        $rangeHdr   = "A2:G2";
        $rangeData  = ($lastRow >= 3) ? "A3:G{$lastRow}" : "";
PHP;
    $replacement = <<<'PHP'
        $dataLastRow = $r - 1;

        if (!empty($notes)) {
            foreach ($notes as $note) {
                $sheet->setCellValue(
                    "A{$r}",
                    (string)(($note['marker'] ?? '') . ' ' . ($note['text'] ?? ''))
                );
                $sheet->mergeCells("A{$r}:G{$r}");
                $sheet->getStyle("A{$r}:G{$r}")->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 10],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $cHeader],
                    ],
                ]);
                $r++;
            }
        }

        $lastRow    = $r - 1;
        $rangeAll   = "A1:G{$lastRow}";
        $rangeTitle = "A1:G1";
        $rangeHdr   = "A2:G2";
        $rangeData  = ($dataLastRow >= 3) ? "A3:G{$dataLastRow}" : "";
PHP;
    $r = replace_once($c, $needle, $replacement, 'team_chart spreadsheet notes block');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "\$segmentNames = [\n    'S1' => 'Segment #1',\n    'S2' => 'Segment #2',\n    'S3' => 'Segment #3',\n    'S4' => 'Playoffs'\n];\n\$segmentLabel = \$segmentNames[\$selectedSegment] ?? \$selectedSegment;",
        "\$segmentLabel = tc_segment_label(\$selectedYear, \$selectedSegment);",
        'team_chart segment label');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "\$chartContext = tc_build_chart_context(\$picks);",
        "\$chartContext = tc_build_chart_context(\$picks, \$selectedYear);",
        'team_chart context call');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "send_excel_xlsx(\"Team_Chart_{\$selectedYear}_{\$selectedSegment}\", \$chartContext['excelRows'], \$title);",
        "send_excel_xlsx(\n"
        . "        \"Team_Chart_{\$selectedYear}_{\$selectedSegment}_\" . tc_generation_stamp(),\n"
        . "        \$chartContext['excelRows'],\n"
        . "        \$title,\n"
        . "        \$chartContext['notes']\n"
        . "    );",
        'team_chart excel filename/call');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'HTML'
            <label class="teamchart-label" for="segment">Choose segment:</label>
            <select id="segment" name="segment" class="teamchart-select" required>
HTML;
    $replacement = <<<'HTML'
            <label class="teamchart-label" for="segment">Choose segment:</label>
            <button type="button" id="btnPrevSegment" class="teamchart-actionbtn" title="Previous segment">&lt;&lt;</button>
            <select id="segment" name="segment" class="teamchart-select" required>
HTML;
    $r = replace_once($c, $needle, $replacement, 'team_chart previous segment button');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'HTML'
            </select>

            <button type="submit" class="teamchart-button">Show</button>
HTML;
    $replacement = <<<'HTML'
            </select>
            <button type="button" id="btnNextSegment" class="teamchart-actionbtn" title="Next segment">&gt;&gt;</button>

            <button type="submit" class="teamchart-button">Show</button>
HTML;
    $r = replace_once($c, $needle, $replacement, 'team_chart next segment button');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'CSS'
        .teamchart-notes {
            margin-top: 8px;
            color: #666;
            font-size: 13px;
            font-family: Arial, sans-serif;
            text-align: left;
        }

        .teamchart-notes div + div {
            margin-top: 2px;
        }
CSS;
    $replacement = <<<'CSS'
        .teamchart-table,
        .teamchart-table th,
        .teamchart-table td {
            font-family: Arial, sans-serif !important;
            font-size: 13pt !important;
            line-height: 140% !important;
        }

        .teamchart-notes-row {
            font-family: Arial, sans-serif !important;
            font-size: 12px !important;
            line-height: 1.35 !important;
            padding: 8px 12px !important;
            text-align: left !important;
        }

        .teamchart-note-line + .teamchart-note-line {
            margin-top: 4px;
        }
CSS;
    $r = replace_once($c, $needle, $replacement, 'team_chart typography');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'PHP'
                                        if ($pickType === 'LP' && $marker !== '') {
                                            $driverA .= ' ' . $marker;
                                            $driverB .= ' ' . $marker;
                                            $driverC .= ' ' . $marker;
                                            $driverD .= ' ' . $marker;
                                        }

                                        $teamDisplay = trim((string)($row['teamName'] ?? ''));
                                        if ($marker !== '') {
                                            $teamDisplay .= ' ' . $marker;
                                        }
PHP;
    $replacement = <<<'PHP'
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
PHP;
    $r = replace_once($c, $needle, $replacement, 'team_chart LP HTML display variables');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "<td class=\"teamchart-cell-owner\"><?php echo h(\$row['userName'] ?? ''); ?></td>",
        "<td class=\"teamchart-cell-owner\"><?php echo h(\$ownerDisplay); ?></td>",
        'team_chart LP owner HTML');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "<td class=\"teamchart-cell-time\"><?php echo h(\$row['entryDate'] ?? ''); ?></td>",
        "<td class=\"teamchart-cell-time\"><?php echo h(\$timeDisplay); ?></td>",
        'team_chart LP time HTML');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'JS'
    const actionsWrap = document.getElementById('chartActions');
    const btnPrint = document.getElementById('btnPrint');
    const btnExcel = document.getElementById('btnExcel');
JS;
    $replacement = <<<'JS'
    const actionsWrap = document.getElementById('chartActions');
    const btnPrint = document.getElementById('btnPrint');
    const btnExcel = document.getElementById('btnExcel');
    const btnPrevSegment = document.getElementById('btnPrevSegment');
    const btnNextSegment = document.getElementById('btnNextSegment');
    const teamchartForm = document.getElementById('teamchartForm');
JS;
    $r = replace_once($c, $needle, $replacement, 'team_chart JS button refs');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'JS'
    if (yearSel) yearSel.addEventListener('change', hideActionsWhenChanged);
    if (segSel)  segSel.addEventListener('change', hideActionsWhenChanged);

    if (btnExcel && excelForm && excelYear && excelSeg) {
JS;
    $replacement = <<<'JS'
    if (yearSel) yearSel.addEventListener('change', hideActionsWhenChanged);
    if (segSel)  segSel.addEventListener('change', hideActionsWhenChanged);

    function moveSegment(direction) {
        if (!segSel || !teamchartForm) return;

        const options = Array.from(segSel.options);
        let index = segSel.selectedIndex;
        const nextIndex = index + direction;

        if (nextIndex < 0 || nextIndex >= options.length) return;

        segSel.selectedIndex = nextIndex;
        hideActionsWhenChanged();
        teamchartForm.submit();
    }

    if (btnPrevSegment) {
        btnPrevSegment.disabled = !segSel || segSel.selectedIndex <= 0;
        btnPrevSegment.addEventListener('click', function () { moveSegment(-1); });
    }

    if (btnNextSegment) {
        btnNextSegment.disabled = !segSel || segSel.selectedIndex >= segSel.options.length - 1;
        btnNextSegment.addEventListener('click', function () { moveSegment(1); });
    }

    if (btnExcel && excelForm && excelYear && excelSeg) {
JS;
    $r = replace_once($c, $needle, $replacement, 'team_chart JS segment navigation');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'JS'
            const y = yearSel.value || '';
            const s = segSel.value || '';
            const fileTitle = 'Team_Chart_' + y + '_' + s;

            document.title = fileTitle;
JS;
    $replacement = <<<'JS'
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
JS;
    $r = replace_once($c, $needle, $replacement, 'team_chart print timestamp');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function current_segment_patch(string $c): array {
    $r = replace_once($c,
        " * VERSION: v007\n * LAST MODIFIED: 4/13/2026 3:33:00 pm",
        " * VERSION: v008\n * LAST MODIFIED: 9/9/2026 2:21:06 am ET",
        'current_segment version');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        " * CHANGELOG:\n *\n * v007 (4/13/2026)",
        " * CHANGELOG:\n *\n * v008 (9/9/2026 2:21:06 am ET)\n"
        . " * - CONSISTENCY: LP rows append their marker to every displayed cell, including Owner and Submission Time.\n"
        . " * - CONSISTENCY: LP/RD effective-race notes include the canonical short race name when available.\n"
        . " * - PRESERVE: Existing 100% team.php width, Arial chart typography, RD merged rows, colors, and segment naming.\n"
        . " *\n * v007 (4/13/2026)",
        'current_segment changelog');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "include 'config_mrl.php';",
        "include 'config_mrl.php';\nrequire_once __DIR__ . '/race_results/race_schedule_helper.php';",
        'current_segment schedule helper include');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'PHP'
function csc_effective_race_label($value): string
{
    $num = (int)$value;
    if ($num <= 0) {
        return '';
    }
    return 'R' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
}
PHP;
    $replacement = <<<'PHP'
function csc_short_race_name(string $year, int $raceNumber): string
{
    if ($raceNumber <= 0) return '';

    try {
        $race = mrl_schedule_helper_race_by_number((int)$year, $raceNumber);
        if (!is_array($race)) return '';

        $name = trim((string)(
            $race['mrl_race_name']
            ?? $race['race_name']
            ?? $race['track_name']
            ?? ''
        ));

        if ($name === '') return '';

        $name = str_replace('_', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim((string)$name);

        if (
            stripos($name, 'World Wide Technology') !== false
            || stripos($name, 'World Wide Tech') !== false
        ) {
            return 'World Wide Tech';
        }

        $name = preg_replace('/^NASCAR\s+Cup\s+Series\s+at\s+/i', '', $name);
        return trim((string)$name);
    } catch (Throwable $e) {
        return '';
    }
}

function csc_effective_race_label(string $year, $value): string
{
    $num = (int)$value;
    if ($num <= 0) {
        return '';
    }

    $label = 'R' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
    $raceName = csc_short_race_name($year, $num);

    if ($raceName !== '') {
        $label .= ' (' . $raceName . ')';
    }

    return $label;
}
PHP;
    $r = replace_once($c, $needle, $replacement, 'current_segment race helper');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "function csc_build_chart_context(array \$rows): array",
        "function csc_build_chart_context(array \$rows, string \$year): array",
        'current_segment context signature');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "\$effectiveRaceLabel = csc_effective_race_label(\$row['effective_race'] ?? 0);",
        "\$effectiveRaceLabel = csc_effective_race_label(\$year, \$row['effective_race'] ?? 0);",
        'current_segment LP note');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "\$effectiveRaceLabel = csc_effective_race_label(\$row['effective_race'] ?? 0);",
        "\$effectiveRaceLabel = csc_effective_race_label(\$year, \$row['effective_race'] ?? 0);",
        'current_segment RD note');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "\$chartContext = csc_build_chart_context(\$rows);",
        "\$chartContext = csc_build_chart_context(\$rows, (string)\$raceYear);",
        'current_segment context call');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $needle = <<<'PHP'
            if ($pickType === 'LP' && $marker !== '') {
                $driverA .= ' ' . $marker;
                $driverB .= ' ' . $marker;
                $driverC .= ' ' . $marker;
                $driverD .= ' ' . $marker;
            }

            echo "<tr>";
            echo "<td style=background-color:#b7dee8>" . csc_h($teamDisplay) . "</td>";
            echo "<td style=background-color:#b7dee8>" . csc_h($row['userName'] ?? '') . "</td>";
PHP;
    $replacement = <<<'PHP'
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

            echo "<tr>";
            echo "<td style=background-color:#b7dee8>" . csc_h($teamDisplay) . "</td>";
            echo "<td style=background-color:#b7dee8>" . csc_h($ownerDisplay) . "</td>";
PHP;
    $r = replace_once($c, $needle, $replacement, 'current_segment LP owner/time variables');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "echo \"<td style=background-color:#b7dee8>\" . csc_h(\$row['entryDate'] ?? '') . \"</td>\";",
        "echo \"<td style=background-color:#b7dee8>\" . csc_h(\$timeDisplay) . \"</td>\";",
        'current_segment LP timestamp output');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function submitted_teams_patch(string $c): array {
    $header = <<<'PHP'
<?php
/**
 * submitted_teams.php
 *
 * VERSION: v001
 * LAST MODIFIED: 9/9/2026 2:21:06 am ET
 *
 * DESCRIPTION:
 * Lists teams that have submitted picks for the active year/segment.
 *
 * CHANGELOG:
 * v001 (9/9/2026 2:21:06 am ET)
 * - NEW: Adds standard MRL file/version header.
 * - CONSISTENCY: Late Pick submissions append " *" to the team name only.
 * - PRESERVE: Existing timestamps, counts, missing-team list, and test-team exclusions.
 */
PHP;

    if (strpos($c, '<?php' ) !== 0) {
        return ['ok'=>false, 'content'=>$c, 'error'=>'submitted_teams opening PHP tag not found'];
    }

    $c = $header . substr($c, 5);

    $r = replace_once($c,
        "\$sql_submitted = \"SELECT * FROM `user_picks` WHERE `raceYear` = '\$raceYear' AND `userID` NOT IN (0, 999) AND `segment` = '\$segment' ORDER BY `entryDate` ASC\";",
        "\$sql_submitted = \"SELECT * FROM `user_picks` WHERE `raceYear` = '\$raceYear' AND `userID` NOT IN (0, 999) AND `segment` = '\$segment' ORDER BY `entryDate` ASC\";",
        'submitted_teams query presence');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once($c,
        "while (\$row = mysqli_fetch_assoc(\$result_submitted)) {\n    echo \"{\$row['entryDate']} : {\$row['teamName']}<br>\";\n}",
        "while (\$row = mysqli_fetch_assoc(\$result_submitted)) {\n"
        . "    \$teamDisplay = (string)(\$row['teamName'] ?? '');\n"
        . "    if (strtoupper(trim((string)(\$row['pick_type'] ?? ''))) === 'LP') {\n"
        . "        \$teamDisplay .= ' *';\n"
        . "    }\n"
        . "    echo \"{\$row['entryDate']} : {\$teamDisplay}<br>\";\n"
        . "}",
        'submitted_teams LP marker');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function patch_for_file(string $name, string $content): array {
    if ($name === 'team_chart.php') return team_chart_patch($content);
    if ($name === 'current_segment_chart.php') return current_segment_patch($content);
    if ($name === 'submitted_teams.php') return submitted_teams_patch($content);
    return ['ok'=>false, 'content'=>$content, 'error'=>'Unknown target'];
}

function save_manifest(string $path, array $manifest): bool {
    $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return is_string($json) && write_text($path, $json . "\n");
}

function load_manifest(string $path): ?array {
    if (!is_file($path)) return null;
    $data = json_decode(read_text($path), true);
    return is_array($data) ? $data : null;
}

function restore_manifest(array $manifest): array {
    $details = [];
    $all = true;

    foreach (($manifest['files'] ?? []) as $name => $info) {
        $target = (string)($info['target'] ?? '');
        $backup = (string)($info['backup'] ?? '');
        $sha = (string)($info['original_sha256'] ?? '');

        $ok = false;
        $msg = '';

        if (!is_file($backup)) {
            $msg = 'Backup missing.';
        } else {
            $data = read_text($backup);
            if ($data === '') {
                $msg = 'Backup unreadable/empty.';
            } elseif ($sha !== '' && hash('sha256', $data) !== $sha) {
                $msg = 'Backup SHA mismatch.';
            } else {
                $tmp = dirname($target) . '/.' . basename($target) . '.rollback.' . bin2hex(random_bytes(4)) . '.tmp';
                if (write_text($tmp, $data)) {
                    $lint = php_lint($tmp);
                    if ($lint['ok'] && @rename($tmp, $target)) {
                        $ok = hash('sha256', read_text($target)) === hash('sha256', $data);
                        $msg = $ok ? 'Exact backup restored.' : 'Restore SHA verification failed.';
                    } else {
                        @unlink($tmp);
                        $msg = 'Rollback temp lint/rename failed.';
                    }
                } else {
                    $msg = 'Could not write rollback temp file.';
                }
            }
        }

        if (!$ok) $all = false;
        $details[] = [$name, $ok, $msg];
    }

    return ['ok'=>$all, 'details'=>$details];
}

function preflight(array $targets, string $backupDir): array {
    $checks = [];
    $contents = [];
    $all = true;

    foreach ($targets as $name => $meta) {
        $path = $meta['path'];
        $exists = is_file($path);
        $readable = $exists && is_readable($path);
        $writable = $exists && is_writable($path);

        $checks[] = [$name . ' exists', $exists, $path];
        $checks[] = [$name . ' readable/writable', $readable && $writable, ''];

        if (!$exists || !$readable || !$writable) {
            $all = false;
            continue;
        }

        $content = read_text($path);
        $contents[$name] = $content;

        if ($name === 'team_chart.php') {
            $ok = exact_count($content, ' * VERSION: v018') === 1
                && exact_count($content, "function tc_build_chart_context(array \$rows): array") === 1;
        } elseif ($name === 'current_segment_chart.php') {
            $ok = exact_count($content, ' * VERSION: v007') === 1
                && exact_count($content, "function csc_build_chart_context(array \$rows): array") === 1;
        } else {
            $ok = strpos($content, 'VERSION:') === false
                && exact_count($content, "echo \"{\$row['entryDate']} : {\$row['teamName']}<br>\";") === 1;
        }

        $checks[] = [$name . ' expected baseline/signatures', $ok, $meta['from'] . ' -> ' . $meta['to']];
        if (!$ok) $all = false;

        if ($ok) {
            $patch = patch_for_file($name, $content);
            $checks[] = [$name . ' patch can be built exactly', $patch['ok'], $patch['error']];
            if (!$patch['ok']) $all = false;
        }
    }

    $backupReady = is_dir($backupDir) ? is_writable($backupDir) : is_writable(dirname($backupDir));
    $checks[] = ['Backup location writable/creatable', $backupReady, $backupDir];
    if (!$backupReady) $all = false;

    return ['all'=>$all, 'checks'=>$checks, 'contents'=>$contents];
}

$message = '';
$messageClass = 'info';
$actionDetails = [];
$rollbackDetails = [];
$pre = preflight($targets, $backupDir);
$manifest = load_manifest($manifestPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'apply') {
        $pre = preflight($targets, $backupDir);

        if (!$pre['all']) {
            $message = 'APPLY BLOCKED: one or more preflight checks failed.';
            $messageClass = 'bad';
        } else {
            if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                $message = 'APPLY BLOCKED: backup directory could not be created.';
                $messageClass = 'bad';
            } else {
                $runStamp = date('Ymd_His');
                $newContents = [];
                $manifestData = [
                    'installer_version'=>MRL_INSTALLER_VERSION,
                    'installed_at_et'=>date('c'),
                    'files'=>[],
                ];
                $buildOk = true;

                foreach ($targets as $name => $meta) {
                    $original = $pre['contents'][$name];
                    $patch = patch_for_file($name, $original);

                    if (!$patch['ok']) {
                        $buildOk = false;
                        $actionDetails[] = [$name . ' build', false, $patch['error']];
                        break;
                    }

                    $new = str_replace('9/9/2026 2:21:06 am', '__DISPLAY_REAL__', $patch['content']);
                    $new = str_replace('__DISPLAY_REAL__', '9/9/2026 2:21:06 am', $new);
                    $newContents[$name] = $new;

                    $backup = $backupDir . '/' . $name . '.' . $meta['from'] . '.' . $runStamp . '.bak';
                    if (!write_text($backup, $original)) {
                        $buildOk = false;
                        $actionDetails[] = [$name . ' backup', false, 'Unable to write backup.'];
                        break;
                    }

                    $manifestData['files'][$name] = [
                        'target'=>$meta['path'],
                        'backup'=>$backup,
                        'original_sha256'=>hash('sha256', $original),
                        'from'=>$meta['from'],
                        'to'=>$meta['to'],
                    ];
                    $actionDetails[] = [$name . ' backup', true, basename($backup)];
                }

                // Replace placeholder date/time in generated targets now.
                $installDisplay = date('n/j/Y g:i:s a') . ' ET';
                foreach ($newContents as $name => $new) {
                    $newContents[$name] = str_replace('9/9/2026 2:21:06 am ET', $installDisplay, $new);
                }

                $tempFiles = [];
                if ($buildOk) {
                    foreach ($targets as $name => $meta) {
                        $tmp = dirname($meta['path']) . '/.' . basename($meta['path']) . '.install.' . bin2hex(random_bytes(4)) . '.tmp';
                        if (!write_text($tmp, $newContents[$name])) {
                            $buildOk = false;
                            $actionDetails[] = [$name . ' temp write', false, 'Failed.'];
                            break;
                        }

                        $lint = php_lint($tmp);
                        $actionDetails[] = [$name . ' PHP syntax', $lint['ok'], $lint['output']];
                        if (!$lint['ok']) {
                            @unlink($tmp);
                            $buildOk = false;
                            break;
                        }

                        $tempFiles[$name] = $tmp;
                    }
                }

                if (!$buildOk) {
                    foreach ($tempFiles as $tmp) @unlink($tmp);
                    $message = 'APPLY BLOCKED: build/backup/lint failed. Production files were not intentionally replaced.';
                    $messageClass = 'bad';
                } else {
                    $replaceOk = true;

                    foreach ($targets as $name => $meta) {
                        if (!@rename($tempFiles[$name], $meta['path'])) {
                            $replaceOk = false;
                            $actionDetails[] = [$name . ' production replace', false, 'Atomic rename failed.'];
                            break;
                        }
                        $actionDetails[] = [$name . ' production replace', true, $meta['to']];
                    }

                    if ($replaceOk) {
                        save_manifest($manifestPath, $manifestData);
                        $manifest = $manifestData;

                        $postOk = true;
                        foreach ($targets as $name => $meta) {
                            $installed = read_text($meta['path']);

                            if ($name === 'team_chart.php') {
                                $ok = exact_count($installed, ' * VERSION: v019') === 1
                                    && strpos($installed, 'World Wide Tech') !== false
                                    && strpos($installed, 'btnPrevSegment') !== false
                                    && strpos($installed, 'tc_generation_stamp') !== false
                                    && strpos($installed, "setName('Arial')->setSize(13)") !== false;
                            } elseif ($name === 'current_segment_chart.php') {
                                $ok = exact_count($installed, ' * VERSION: v008') === 1
                                    && strpos($installed, 'World Wide Tech') !== false
                                    && strpos($installed, '$ownerDisplay') !== false
                                    && strpos($installed, '$timeDisplay') !== false;
                            } else {
                                $ok = exact_count($installed, ' * VERSION: v001') === 1
                                    && strpos($installed, "\$teamDisplay .= ' *';") !== false;
                            }

                            $lint = php_lint($meta['path']);
                            $ok = $ok && $lint['ok'];
                            $actionDetails[] = [$name . ' postflight', $ok, $lint['output']];
                            if (!$ok) $postOk = false;
                        }

                        if ($postOk) {
                            $message = 'SUCCESS: Team Chart / LP consistency package installed.';
                            $messageClass = 'good';
                        } else {
                            $rb = restore_manifest($manifestData);
                            $rollbackDetails = $rb['details'];
                            $message = $rb['ok']
                                ? 'POSTFLIGHT FAILED: all changed files were automatically rolled back.'
                                : 'CRITICAL: postflight failed and rollback was incomplete.';
                            $messageClass = 'bad';
                        }
                    } else {
                        // Some files may already have been replaced. Restore every target from backup.
                        $rb = restore_manifest($manifestData);
                        $rollbackDetails = $rb['details'];
                        $message = $rb['ok']
                            ? 'REPLACE FAILED: all changed files were restored from backup.'
                            : 'CRITICAL: replacement failed and rollback was incomplete.';
                        $messageClass = 'bad';
                    }
                }
            }
        }
    } elseif ($action === 'rollback') {
        $manifest = load_manifest($manifestPath);
        if (!is_array($manifest)) {
            $message = 'ROLLBACK BLOCKED: no installer manifest found.';
            $messageClass = 'bad';
        } else {
            $rb = restore_manifest($manifest);
            $rollbackDetails = $rb['details'];
            $message = $rb['ok']
                ? 'ROLLBACK SUCCESS: exact pre-install files restored.'
                : 'ROLLBACK INCOMPLETE: inspect the failed rows.';
            $messageClass = $rb['ok'] ? 'good' : 'bad';
        }
    }

    $pre = preflight($targets, $backupDir);
    $manifest = load_manifest($manifestPath);
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Team Chart / LP Consistency Installer</title>
<style>
:root{color-scheme:dark;--bg:#101312;--panel:#1b201f;--border:#46504d;--text:#eee9df;--muted:#b8b7b0;--gold:#f1c97f;--green:#167c45;--red:#a93434;--blue:#286c99}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,95%);margin:14px auto 28px}
h1{margin:0 0 10px;color:var(--gold);font-size:26px}
h2{margin:0 0 8px;color:var(--gold);font-size:18px}
.panel{margin:0 0 10px;padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--panel)}
.notice{margin:0 0 10px;padding:10px 12px;border-radius:9px;font-weight:700}
.notice.good{background:#103b27;border:1px solid #2f9b63}.notice.bad{background:#4b1d1d;border:1px solid #c04b4b}.notice.info{background:#173246;border:1px solid #387ba8}
table{width:100%;border-collapse:collapse}th,td{padding:6px 8px;border-bottom:1px solid #353c3a;text-align:left;vertical-align:top}
th{color:var(--gold)}.pass{color:#5ee58e;font-weight:800}.fail{color:#ff7b7b;font-weight:800}
.small{font-size:12px;color:var(--muted)}.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}
.actions{display:flex;gap:9px;flex-wrap:wrap}
button,a.button{padding:8px 12px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}
.apply{background:var(--green)}.rollback{background:var(--red)}.open{background:var(--blue)}button:disabled{opacity:.45;cursor:not-allowed}
ul{margin:5px 0 0;padding-left:20px;line-height:1.45}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL Team Chart / LP Consistency Installer</h1>

<?php if ($message !== ''): ?>
<div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
<?php endif; ?>

<div class="panel">
<h2>Package</h2>
<ul>
<li><code>team_chart.php</code> v018 → v019</li>
<li><code>current_segment_chart.php</code> v007 → v008</li>
<li><code>submitted_teams.php</code> unversioned → v001</li>
<li>LP stars every chart cell; submitted-teams stars team name only.</li>
<li>LP/RD footnotes add effective race name, including R28 (World Wide Tech).</li>
<li>2026+ S4 title becomes The Chase; earlier years remain Playoffs.</li>
<li>Standalone chart/spreadsheet typography aligns to Arial-based team.php chart.</li>
<li>&lt;&lt; / &gt;&gt; segment navigation added.</li>
<li>Spreadsheet notes added; print/PDF and spreadsheet filenames get current timestamp + milliseconds.</li>
</ul>
</div>

<div class="panel">
<h2>Preflight</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($pre['checks'] as $c): ?>
<tr>
<td><?php echo h((string)$c[0]); ?></td>
<td class="<?php echo $c[1] ? 'pass':'fail'; ?>"><?php echo $c[1] ? 'PASS':'FAIL'; ?></td>
<td class="small mono"><?php echo h((string)$c[2]); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<?php if (!empty($actionDetails)): ?>
<div class="panel">
<h2>Install / Postflight</h2>
<table>
<tr><th>Step</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($actionDetails as $c): ?>
<tr>
<td><?php echo h((string)$c[0]); ?></td>
<td class="<?php echo $c[1] ? 'pass':'fail'; ?>"><?php echo $c[1] ? 'PASS':'FAIL'; ?></td>
<td class="small mono"><?php echo h((string)$c[2]); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<?php if (!empty($rollbackDetails)): ?>
<div class="panel">
<h2>Rollback</h2>
<table>
<tr><th>File</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($rollbackDetails as $c): ?>
<tr>
<td><?php echo h((string)$c[0]); ?></td>
<td class="<?php echo $c[1] ? 'pass':'fail'; ?>"><?php echo $c[1] ? 'PASS':'FAIL'; ?></td>
<td class="small mono"><?php echo h((string)$c[2]); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<div class="panel">
<h2>Actions</h2>
<div class="actions">
<form method="post">
<input type="hidden" name="action" value="apply">
<button class="apply" type="submit" <?php echo $pre['all'] ? '' : 'disabled'; ?>>Apply Package</button>
</form>

<a class="button open" href="/team_chart.php" target="_blank" rel="noopener">Open Team Chart</a>
<a class="button open" href="/team.php" target="_blank" rel="noopener">Open Team Page</a>

<?php if (is_array($manifest)): ?>
<form method="post" onsubmit="return confirm('Restore all exact pre-install files?');">
<input type="hidden" name="action" value="rollback">
<button class="rollback" type="submit">Rollback</button>
</form>
<?php endif; ?>
</div>
</div>

<div class="panel small">
After install, verify Over The Edge on <code>/team_chart.php</code>, the embedded chart on <code>/team.php</code>,
<code>submitted_teams.php</code>, Print/PDF, and Spreadsheet.<br>
This package intentionally does <strong>not</strong> change the canonical schedule/status generator that currently displays
R28 as only “World”; that source is separate from these chart files and should be fixed after its active runtime source is identified.<br><br>
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: <?php echo h(MRL_INSTALLER_VERSION); ?>
</div>
</div>
</body>
</html>
