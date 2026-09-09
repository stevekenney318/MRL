<?php
declare(strict_types=1);

/**
 * MRL_install_team_chart_polish.php
 *
 * VERSION: v001
 * CREATED: 9/9/2026 3:06:40 am ET
 *
 * PURPOSE:
 * Second coordinated Team Chart / Late Pick presentation pass after live review.
 *
 * TARGETS:
 * - team_chart.php v019 -> v020
 * - current_segment_chart.php v008 -> v009
 * - submitted_teams.php v001 -> v002
 *
 * CHANGES:
 * - Fix R28 footnote from "(World)" to "(World Wide Tech)" by using the
 *   richer trusted canonical schedule fields only for the known "World" case.
 * - Keep all other race names on the existing trusted canonical name path.
 * - Add an LP explanatory footnote to submitted_teams.php.
 * - Make Team Chart footnote sizing match the smaller team.php footnote.
 * - Re-apply spreadsheet footnote font/fill after global spreadsheet styling.
 * - Replace Show-button navigation with automatic dropdown navigation.
 * - Put << >> buttons side-by-side after BOTH year and segment selectors.
 * - Add Live button to jump to current configured year/segment.
 * - Preserve Print and Spreadsheet actions and timestamped filenames.
 *
 * SAFETY:
 * - Exact baseline/signature preflight.
 * - Backups before replacement.
 * - PHP lint on generated temporary files.
 * - Atomic replacement.
 * - Postflight verification.
 * - Automatic rollback if replacement/postflight fails.
 */

date_default_timezone_set('America/New_York');

const MRL_INSTALLER_VERSION = 'v001';

$root = __DIR__;
$backupDir = $root . '/_mrl_installer_backups';
$manifestPath = $backupDir . '/team_chart_polish_v001_manifest.json';

$targets = [
    'team_chart.php' => [
        'path' => $root . '/team_chart.php',
        'from' => 'v019',
        'to' => 'v020',
    ],
    'current_segment_chart.php' => [
        'path' => $root . '/current_segment_chart.php',
        'from' => 'v008',
        'to' => 'v009',
    ],
    'submitted_teams.php' => [
        'path' => $root . '/submitted_teams.php',
        'from' => 'v001',
        'to' => 'v002',
    ],
];

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function read_text(string $path): string {
    $v = @file_get_contents($path);
    return $v === false ? '' : $v;
}

function write_text(string $path, string $data): bool {
    $n = @file_put_contents($path, $data, LOCK_EX);
    return $n !== false && $n === strlen($data);
}

function exact_count(string $haystack, string $needle): int {
    return $needle === '' ? 0 : substr_count($haystack, $needle);
}

function replace_once(string $content, string $from, string $to, string $label): array {
    $count = exact_count($content, $from);
    if ($count !== 1) {
        return [
            'ok' => false,
            'content' => $content,
            'error' => $label . ' signature count = ' . $count . ' (expected 1)',
        ];
    }

    return [
        'ok' => true,
        'content' => str_replace($from, $to, $content),
        'error' => '',
    ];
}

function php_lint(string $path): array {
    if (!function_exists('exec')) {
        return ['ok'=>true, 'output'=>'exec() unavailable; lint skipped.'];
    }

    $binary = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($binary) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = [];
    $code = 999;
    @exec($cmd, $out, $code);

    return [
        'ok' => $code === 0,
        'output' => trim(implode("\n", $out)),
    ];
}

function patch_team_chart(string $c): array
{
    $r = replace_once(
        $c,
        " * VERSION: v019",
        " * VERSION: v020",
        'team_chart version'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        " * CHANGELOG:\n *\n * v019",
        " * CHANGELOG:\n *\n"
        . " * v020 (__INSTALL_TIME__ ET)\n"
        . " * - FIX: R28 footnote resolves the known canonical short-name value \"World\" through richer trusted schedule fields to \"World Wide Tech\".\n"
        . " * - UI: Footnote sizing now wins over shared teamchart table CSS and matches the smaller team.php note treatment.\n"
        . " * - UI: Year and segment dropdown changes load automatically; Show button removed.\n"
        . " * - UI: Adds side-by-side << >> navigation for year and segment plus Live jump to current configured year/segment.\n"
        . " * - EXPORT: Spreadsheet footnote font/fill are re-applied after global formatting so notes remain compact with peach background.\n"
        . " * - PRESERVE: LP/RD markers, The Chase naming, privacy gate, RD merged rows, Print/Spreadsheet actions, and timestamped filenames.\n"
        . " *\n * v019",
        'team_chart changelog'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'PHP'
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
PHP;

    $new = <<<'PHP'
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
PHP;

    $r = replace_once($c, $old, $new, 'team_chart race-name helper');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'CSS'
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

    $new = <<<'CSS'
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
CSS;

    $r = replace_once($c, $old, $new, 'team_chart footnote CSS');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'HTML'
            <label class="teamchart-label" for="year">Choose year:</label>
            <select id="year" name="year" class="teamchart-select" required>
                <?php foreach ($yearsStr as $yStr): ?>
                    <option value="<?php echo h($yStr); ?>" <?php echo ($yStr === $selectedYear ? 'selected' : ''); ?>>
                        <?php echo h($yStr); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="teamchart-label" for="segment">Choose segment:</label>
            <button type="button" id="btnPrevSegment" class="teamchart-actionbtn" title="Previous segment">&lt;&lt;</button>
            <select id="segment" name="segment" class="teamchart-select" required>
                <?php foreach ($segmentsStr as $sStr): ?>
                    <option value="<?php echo h($sStr); ?>" <?php echo ($sStr === $selectedSegment ? 'selected' : ''); ?>>
                        <?php echo h($sStr); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="btnNextSegment" class="teamchart-actionbtn" title="Next segment">&gt;&gt;</button>

            <button type="submit" class="teamchart-button">Show</button>

            <?php if ($chartDisplayed): ?>
HTML;

    $new = <<<'HTML'
            <label class="teamchart-label" for="year">Choose year:</label>
            <select id="year" name="year" class="teamchart-select" required>
                <?php foreach ($yearsStr as $yStr): ?>
                    <option value="<?php echo h($yStr); ?>" <?php echo ($yStr === $selectedYear ? 'selected' : ''); ?>>
                        <?php echo h($yStr); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="teamchart-navpair">
                <button type="button" id="btnPrevYear" class="teamchart-actionbtn" title="Previous year">&lt;&lt;</button>
                <button type="button" id="btnNextYear" class="teamchart-actionbtn" title="Next year">&gt;&gt;</button>
            </span>

            <label class="teamchart-label" for="segment">Choose segment:</label>
            <select id="segment" name="segment" class="teamchart-select" required>
                <?php foreach ($segmentsStr as $sStr): ?>
                    <option value="<?php echo h($sStr); ?>" <?php echo ($sStr === $selectedSegment ? 'selected' : ''); ?>>
                        <?php echo h($sStr); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="teamchart-navpair">
                <button type="button" id="btnPrevSegment" class="teamchart-actionbtn" title="Previous segment">&lt;&lt;</button>
                <button type="button" id="btnNextSegment" class="teamchart-actionbtn" title="Next segment">&gt;&gt;</button>
            </span>

            <button type="button"
                    id="btnLive"
                    class="teamchart-actionbtn"
                    data-live-year="<?php echo h($defaultYear); ?>"
                    data-live-segment="<?php echo h($defaultSegment); ?>">Live</button>

            <?php if ($chartDisplayed): ?>
HTML;

    $r = replace_once($c, $old, $new, 'team_chart navigation HTML');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'CSS'
        .teamchart-actions {
            display: inline-flex;
            gap: 10px;
            align-items: center;
        }
CSS;

    $new = <<<'CSS'
        .teamchart-actions {
            display: inline-flex;
            gap: 10px;
            align-items: center;
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
CSS;

    $r = replace_once($c, $old, $new, 'team_chart navpair CSS');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'JS'
    const actionsWrap = document.getElementById('chartActions');
    const btnPrint = document.getElementById('btnPrint');
    const btnExcel = document.getElementById('btnExcel');
    const btnPrevSegment = document.getElementById('btnPrevSegment');
    const btnNextSegment = document.getElementById('btnNextSegment');
    const teamchartForm = document.getElementById('teamchartForm');
JS;

    $new = <<<'JS'
    const actionsWrap = document.getElementById('chartActions');
    const btnPrint = document.getElementById('btnPrint');
    const btnExcel = document.getElementById('btnExcel');
    const btnPrevYear = document.getElementById('btnPrevYear');
    const btnNextYear = document.getElementById('btnNextYear');
    const btnPrevSegment = document.getElementById('btnPrevSegment');
    const btnNextSegment = document.getElementById('btnNextSegment');
    const btnLive = document.getElementById('btnLive');
    const teamchartForm = document.getElementById('teamchartForm');
JS;

    $r = replace_once($c, $old, $new, 'team_chart navigation JS refs');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'JS'
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
JS;

    $new = <<<'JS'
    function submitSelection() {
        if (!teamchartForm) return;
        hideActionsWhenChanged();

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
JS;

    $r = replace_once($c, $old, $new, 'team_chart navigation JS behavior');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    /*
     * Spreadsheet notes in v019 are styled before the global range font is
     * applied, so the global 13pt font wins. Re-apply compact note styling at
     * the end after all global/data styling.
     */
    $old = <<<'PHP'
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(20);
        if ($lastRow >= 3) {
            for ($i = 3; $i <= $lastRow; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(18);
            }
        }

        while (ob_get_level() > 0) {
PHP;

    $new = <<<'PHP'
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(20);
        if ($lastRow >= 3) {
            for ($i = 3; $i <= $lastRow; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(18);
            }
        }

        if (!empty($notes)) {
            $noteStartRow = $dataLastRow + 1;
            for ($noteRow = $noteStartRow; $noteRow <= $lastRow; $noteRow++) {
                $sheet->getStyle("A{$noteRow}:G{$noteRow}")->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 9,
                        'bold' => false,
                        'color' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $cHeader],
                    ],
                ]);
                $sheet->getRowDimension($noteRow)->setRowHeight(16);
            }
        }

        while (ob_get_level() > 0) {
PHP;

    $r = replace_once($c, $old, $new, 'team_chart spreadsheet final note styling');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function patch_current_segment(string $c): array
{
    $r = replace_once(
        $c,
        " * VERSION: v008",
        " * VERSION: v009",
        'current_segment version'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        " * CHANGELOG:\n *\n * v008",
        " * CHANGELOG:\n *\n"
        . " * v009 (__INSTALL_TIME__ ET)\n"
        . " * - FIX: R28 footnote resolves the known canonical short-name value \"World\" through richer trusted schedule fields to \"World Wide Tech\".\n"
        . " * - PRESERVE: Existing smaller team.php footnote sizing, LP all-cell markers, RD display, colors, and 100% chart width.\n"
        . " *\n * v008",
        'current_segment changelog'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'PHP'
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
PHP;

    $new = <<<'PHP'
function csc_short_race_name(string $year, int $raceNumber): string
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
PHP;

    $r = replace_once($c, $old, $new, 'current_segment race-name helper');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function patch_submitted_teams(string $c): array
{
    $r = replace_once(
        $c,
        " * VERSION: v001",
        " * VERSION: v002",
        'submitted_teams version'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        " * CHANGELOG:\n * v001",
        " * CHANGELOG:\n"
        . " * v002 (__INSTALL_TIME__ ET)\n"
        . " * - NEW: Adds a compact explanatory LP footnote below the submitted-team list.\n"
        . " * - FIX: Known R28 canonical short-name value \"World\" resolves through richer trusted schedule fields to \"World Wide Tech\".\n"
        . " * - PRESERVE: LP marker remains on team name only in the submitted list.\n"
        . " *\n * v001",
        'submitted_teams changelog'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        "include \"config_mrl.php\"; // setup variables for current MRL season & segment",
        "include \"config_mrl.php\"; // setup variables for current MRL season & segment\n"
        . "require_once __DIR__ . '/race_results/race_schedule_helper.php';",
        'submitted_teams schedule helper include'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'PHP'
$sql_submitted = "SELECT * FROM `user_picks` WHERE `raceYear` = '$raceYear' AND `userID` NOT IN (0, 999) AND `segment` = '$segment' ORDER BY `entryDate` ASC";

echo "Teams submitted for $raceYear $segment :<br><br>";
$result_submitted = mysqli_query($dbconnect, $sql_submitted);
while ($row = mysqli_fetch_assoc($result_submitted)) {
    $teamDisplay = (string)($row['teamName'] ?? '');
    if (strtoupper(trim((string)($row['pick_type'] ?? ''))) === 'LP') {
        $teamDisplay .= ' *';
    }
    echo "{$row['entryDate']} : {$teamDisplay}<br>";
}
PHP;

    $new = <<<'PHP'
function st_short_race_name(string $year, int $raceNumber): string
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

$sql_submitted = "SELECT * FROM `user_picks` WHERE `raceYear` = '$raceYear' AND `userID` NOT IN (0, 999) AND `segment` = '$segment' ORDER BY `entryDate` ASC";

echo "Teams submitted for $raceYear $segment :<br><br>";
$result_submitted = mysqli_query($dbconnect, $sql_submitted);
$lpFootnotes = [];

while ($row = mysqli_fetch_assoc($result_submitted)) {
    $teamDisplay = (string)($row['teamName'] ?? '');
    $pickType = strtoupper(trim((string)($row['pick_type'] ?? '')));

    if ($pickType === 'LP') {
        $teamDisplay .= ' *';

        $effectiveRace = (int)($row['effective_race'] ?? 0);
        $raceLabel = $effectiveRace > 0
            ? ('R' . str_pad((string)$effectiveRace, 2, '0', STR_PAD_LEFT))
            : '';

        if ($effectiveRace > 0) {
            $raceName = st_short_race_name((string)$raceYear, $effectiveRace);
            if ($raceName !== '') {
                $raceLabel .= ' (' . $raceName . ')';
            }
        }

        $note = '* ' . (string)($row['teamName'] ?? '') . ' — Late Pick';
        if ($raceLabel !== '') {
            $note .= ' — Effective ' . $raceLabel;
        }
        $lpFootnotes[] = $note;
    }

    echo "{$row['entryDate']} : {$teamDisplay}<br>";
}

if (!empty($lpFootnotes)) {
    echo "<div style='margin-top:14px; font-family:Arial,sans-serif; font-size:12px; line-height:1.35; color:#dfcca8;'>";
    foreach ($lpFootnotes as $note) {
        echo htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . "<br>";
    }
    echo "</div>";
}
PHP;

    $r = replace_once($c, $old, $new, 'submitted_teams list/footnote block');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function patch_file(string $name, string $content): array
{
    if ($name === 'team_chart.php') return patch_team_chart($content);
    if ($name === 'current_segment_chart.php') return patch_current_segment($content);
    if ($name === 'submitted_teams.php') return patch_submitted_teams($content);

    return ['ok'=>false, 'content'=>$content, 'error'=>'Unknown target'];
}

function preflight(array $targets, string $backupDir): array
{
    $checks = [];
    $contents = [];
    $all = true;

    foreach ($targets as $name => $meta) {
        $path = $meta['path'];
        $exists = is_file($path);
        $rw = $exists && is_readable($path) && is_writable($path);

        $checks[] = [$name . ' exists', $exists, $path];
        $checks[] = [$name . ' readable/writable', $rw, ''];

        if (!$exists || !$rw) {
            $all = false;
            continue;
        }

        $content = read_text($path);
        $contents[$name] = $content;

        $baselineOk = exact_count($content, ' * VERSION: ' . $meta['from']) === 1;

        if ($name === 'team_chart.php') {
            $baselineOk = $baselineOk
                && exact_count($content, 'function tc_short_race_name(string $year, int $raceNumber): string') === 1
                && exact_count($content, 'id="btnLive"') === 0
                && exact_count($content, 'id="btnPrevSegment"') === 1;
        } elseif ($name === 'current_segment_chart.php') {
            $baselineOk = $baselineOk
                && exact_count($content, 'function csc_short_race_name(string $year, int $raceNumber): string') === 1;
        } elseif ($name === 'submitted_teams.php') {
            $baselineOk = $baselineOk
                && exact_count($content, "\$teamDisplay .= ' *';") === 1
                && exact_count($content, '$lpFootnotes') === 0;
        }

        $checks[] = [
            $name . ' expected baseline/signatures',
            $baselineOk,
            $meta['from'] . ' -> ' . $meta['to'],
        ];

        if (!$baselineOk) {
            $all = false;
            continue;
        }

        $patch = patch_file($name, $content);
        $checks[] = [
            $name . ' patch can be built exactly',
            $patch['ok'],
            $patch['error'],
        ];

        if (!$patch['ok']) $all = false;
    }

    $backupReady = is_dir($backupDir) ? is_writable($backupDir) : is_writable(dirname($backupDir));
    $checks[] = ['Backup location writable/creatable', $backupReady, $backupDir];
    if (!$backupReady) $all = false;

    return [
        'all' => $all,
        'checks' => $checks,
        'contents' => $contents,
    ];
}

function save_manifest(string $path, array $manifest): bool
{
    $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return is_string($json) && write_text($path, $json . "\n");
}

function load_manifest(string $path): ?array
{
    if (!is_file($path)) return null;

    $data = json_decode(read_text($path), true);
    return is_array($data) ? $data : null;
}

function restore_manifest(array $manifest): array
{
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

$message = '';
$messageClass = 'info';
$details = [];
$rollbackDetails = [];

$pre = preflight($targets, $backupDir);
$manifest = load_manifest($manifestPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'apply') {
        $pre = preflight($targets, $backupDir);

        if (!$pre['all']) {
            $message = 'APPLY BLOCKED: preflight failed.';
            $messageClass = 'bad';
        } else {
            if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                $message = 'APPLY BLOCKED: backup directory could not be created.';
                $messageClass = 'bad';
            } else {
                $runStamp = date('Ymd_His');
                $installTime = date('n/j/Y g:i:s a') . ' ET';
                $manifestData = [
                    'installer_version' => MRL_INSTALLER_VERSION,
                    'installed_at_et' => date('c'),
                    'files' => [],
                ];

                $newContents = [];
                $temps = [];
                $buildOk = true;

                foreach ($targets as $name => $meta) {
                    $original = $pre['contents'][$name];
                    $patch = patch_file($name, $original);

                    if (!$patch['ok']) {
                        $buildOk = false;
                        $details[] = [$name . ' build', false, $patch['error']];
                        break;
                    }

                    $new = str_replace('__INSTALL_TIME__ ET', $installTime, $patch['content']);
                    $newContents[$name] = $new;

                    $backup = $backupDir . '/' . $name . '.' . $meta['from'] . '.' . $runStamp . '.bak';

                    if (!write_text($backup, $original)) {
                        $buildOk = false;
                        $details[] = [$name . ' backup', false, 'Unable to write backup.'];
                        break;
                    }

                    $manifestData['files'][$name] = [
                        'target' => $meta['path'],
                        'backup' => $backup,
                        'original_sha256' => hash('sha256', $original),
                        'from' => $meta['from'],
                        'to' => $meta['to'],
                    ];

                    $details[] = [$name . ' backup', true, basename($backup)];
                }

                if ($buildOk) {
                    foreach ($targets as $name => $meta) {
                        $tmp = dirname($meta['path']) . '/.' . basename($meta['path']) . '.install.' . bin2hex(random_bytes(4)) . '.tmp';

                        if (!write_text($tmp, $newContents[$name])) {
                            $buildOk = false;
                            $details[] = [$name . ' temp write', false, 'Failed.'];
                            break;
                        }

                        $lint = php_lint($tmp);
                        $details[] = [$name . ' PHP syntax', $lint['ok'], $lint['output']];

                        if (!$lint['ok']) {
                            @unlink($tmp);
                            $buildOk = false;
                            break;
                        }

                        $temps[$name] = $tmp;
                    }
                }

                if (!$buildOk) {
                    foreach ($temps as $tmp) @unlink($tmp);
                    $message = 'APPLY BLOCKED: build/backup/lint failed before intended production replacement.';
                    $messageClass = 'bad';
                } else {
                    $replaceOk = true;

                    foreach ($targets as $name => $meta) {
                        if (!@rename($temps[$name], $meta['path'])) {
                            $replaceOk = false;
                            $details[] = [$name . ' production replace', false, 'Atomic rename failed.'];
                            break;
                        }

                        $details[] = [$name . ' production replace', true, $meta['to']];
                    }

                    if ($replaceOk) {
                        save_manifest($manifestPath, $manifestData);
                        $manifest = $manifestData;

                        $postOk = true;

                        foreach ($targets as $name => $meta) {
                            $installed = read_text($meta['path']);
                            $lint = php_lint($meta['path']);
                            $ok = exact_count($installed, ' * VERSION: ' . $meta['to']) === 1 && $lint['ok'];

                            if ($name === 'team_chart.php') {
                                $ok = $ok
                                    && strpos($installed, 'id="btnPrevYear"') !== false
                                    && strpos($installed, 'id="btnNextYear"') !== false
                                    && strpos($installed, 'id="btnLive"') !== false
                                    && strpos($installed, 'Show</button>') === false
                                    && strpos($installed, 'World Wide Tech') !== false
                                    && strpos($installed, 'noteStartRow') !== false;
                            } elseif ($name === 'current_segment_chart.php') {
                                $ok = $ok
                                    && strpos($installed, "strcasecmp(\$preferred, 'World')") !== false;
                            } elseif ($name === 'submitted_teams.php') {
                                $ok = $ok
                                    && strpos($installed, '$lpFootnotes') !== false
                                    && strpos($installed, 'Effective ') !== false
                                    && strpos($installed, 'World Wide Tech') !== false;
                            }

                            $details[] = [$name . ' postflight', $ok, $lint['output']];
                            if (!$ok) $postOk = false;
                        }

                        if ($postOk) {
                            $message = 'SUCCESS: Team Chart polish package installed.';
                            $messageClass = 'good';
                        } else {
                            $rb = restore_manifest($manifestData);
                            $rollbackDetails = $rb['details'];
                            $message = $rb['ok']
                                ? 'POSTFLIGHT FAILED: all targets automatically rolled back.'
                                : 'CRITICAL: postflight failed and rollback was incomplete.';
                            $messageClass = 'bad';
                        }
                    } else {
                        $rb = restore_manifest($manifestData);
                        $rollbackDetails = $rb['details'];
                        $message = $rb['ok']
                            ? 'REPLACE FAILED: all targets restored from exact backups.'
                            : 'CRITICAL: replacement failed and rollback was incomplete.';
                        $messageClass = 'bad';
                    }
                }
            }
        }
    } elseif ($action === 'rollback') {
        $manifest = load_manifest($manifestPath);

        if (!is_array($manifest)) {
            $message = 'ROLLBACK BLOCKED: no manifest found.';
            $messageClass = 'bad';
        } else {
            $rb = restore_manifest($manifest);
            $rollbackDetails = $rb['details'];
            $message = $rb['ok']
                ? 'ROLLBACK SUCCESS: exact pre-install files restored.'
                : 'ROLLBACK INCOMPLETE: inspect failed rows.';
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
<title>MRL Team Chart Polish Installer</title>
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
<h1>MRL Team Chart Polish Installer</h1>

<?php if ($message !== ''): ?>
<div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
<?php endif; ?>

<div class="panel">
<h2>Package</h2>
<ul>
<li><code>team_chart.php</code> v019 → v020</li>
<li><code>current_segment_chart.php</code> v008 → v009</li>
<li><code>submitted_teams.php</code> v001 → v002</li>
<li>Fixes the known R28 <code>World</code> footnote using richer trusted canonical fields; other races retain the preferred canonical name.</li>
<li>Adds compact LP footnote to Submitted Teams.</li>
<li>Corrects Team Chart and spreadsheet footnote sizing/background consistency.</li>
<li>Year and segment dropdowns now load immediately.</li>
<li>Adds side-by-side year <code>&lt;&lt; &gt;&gt;</code>, segment <code>&lt;&lt; &gt;&gt;</code>, and <strong>Live</strong>.</li>
<li>Removes Show; preserves Print, Spreadsheet, and timestamped filenames.</li>
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

<?php if (!empty($details)): ?>
<div class="panel">
<h2>Install / Postflight</h2>
<table>
<tr><th>Step</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($details as $c): ?>
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
<a class="button open" href="/submitted_teams.php" target="_blank" rel="noopener">Open Submitted Teams</a>

<?php if (is_array($manifest)): ?>
<form method="post" onsubmit="return confirm('Restore all exact pre-install files?');">
<input type="hidden" name="action" value="rollback">
<button class="rollback" type="submit">Rollback</button>
</form>
<?php endif; ?>
</div>
</div>

<div class="panel small">
After install, verify:
<code>/team_chart.php</code> navigation + footnote,
<code>/team.php</code> footnote,
<code>/submitted_teams.php</code> footnote,
Spreadsheet footnote, and Print/PDF footnote.<br><br>
This package still does <strong>not</strong> modify the separate canonical schedule/status generator that displays R28 as only “World”.<br><br>
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: <?php echo h(MRL_INSTALLER_VERSION); ?>
</div>
</div>
</body>
</html>
