<?php
declare(strict_types=1);

/**
 * MRL_install_team_chart_shared_theme.php
 *
 * VERSION: v001
 * CREATED: 9/9/2026 4:01:04 am ET
 *
 * PURPOSE:
 * Third coordinated Team Chart polish pass and first shared MRL theme rollout.
 *
 * TARGETS:
 * - team_chart.php v020 -> v021
 * - current_user_team_chart.php v006 -> v007
 * - race_results/race_results_monitor.php v139 -> v140
 * - NEW mrl_team/mrl_shared_theme.css v001
 * - Runtime repair of race_results/_race_results_schedule.json R28 MRL short name
 *
 * CHANGES:
 * - Team Chart control row matches the approved mockup:
 *   Live | Year | << >> | Segment | << >> | flexible space | Print | Spreadsheet
 * - Removes "Choose year" / "Choose segment" labels.
 * - Live is disabled/dimmed while already viewing the current live year/segment.
 * - Control row is aligned to the existing chart edges without moving/resizing the chart.
 * - Team Chart adopts the logged-in user's existing Team theme preference.
 * - Adds a reusable shared MRL theme stylesheet for future public-page adoption.
 * - Personal yearly chart uses The Chase for S4 in 2026+, Playoffs through 2025.
 * - Fixes the upstream race-monitor short-label source for World Wide Tech.
 * - Repairs the currently generated 2026 schedule JSON so the fix is visible immediately;
 *   the corrected monitor source preserves it on future regenerations.
 *
 * SAFETY:
 * - Exact baseline/signature preflight.
 * - Exact backups before replacement.
 * - PHP lint on generated PHP temporary files.
 * - Atomic file replacement.
 * - JSON decode/encode validation for schedule repair.
 * - Postflight verification.
 * - Automatic rollback on failure plus manual rollback.
 *
 * DOES NOT:
 * - write database rows
 * - send email
 * - run cron/scheduler/monitor
 * - alter picks or scoring
 * - add the shared header bar yet
 */

date_default_timezone_set('America/New_York');

const MRL_INSTALLER_VERSION = 'v001';

$root = __DIR__;
$backupDir = $root . '/_mrl_installer_backups';
$manifestPath = $backupDir . '/team_chart_shared_theme_v001_manifest.json';

$targets = [
    'team_chart.php' => [
        'path' => $root . '/team_chart.php',
        'from' => 'v020',
        'to' => 'v021',
        'kind' => 'php',
    ],
    'current_user_team_chart.php' => [
        'path' => $root . '/current_user_team_chart.php',
        'from' => 'v006',
        'to' => 'v007',
        'kind' => 'php',
    ],
    'race_results_monitor.php' => [
        'path' => $root . '/race_results/race_results_monitor.php',
        'from' => 'v139',
        'to' => 'v140',
        'kind' => 'php',
    ],
];

$themeFile = $root . '/mrl_team/mrl_shared_theme.css';
$themeHelper = $root . '/mrl_team/mrl_theme_helper.php';
$scheduleFile = $root . '/race_results/_race_results_schedule.json';

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
    $r = replace_once($c, " * VERSION: v020", " * VERSION: v021", 'team_chart version');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        " * CHANGELOG:\n *\n * v020",
        " * CHANGELOG:\n *\n"
        . " * v021 (__INSTALL_TIME__ ET)\n"
        . " * - UI: Control row now follows approved Live / year / year arrows / segment / segment arrows / report-actions layout.\n"
        . " * - UI: Removed redundant Choose year / Choose segment labels.\n"
        . " * - UI: Live is disabled while already viewing the configured current year/segment.\n"
        . " * - UI: Control row aligns to the existing chart edges; chart size/position remains unchanged.\n"
        . " * - THEME: Uses the logged-in user's Team theme through shared mrl_team/mrl_shared_theme.css v001.\n"
        . " * - PRESERVE: Auto-load dropdowns, arrow boundary disabling, privacy gate, chart colors, Print/Spreadsheet, LP/RD display, and timestamped exports.\n"
        . " *\n * v020",
        'team_chart changelog'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        "require_once \$_SERVER['DOCUMENT_ROOT'] . '/class.user.php';\nrequire_once __DIR__ . '/race_results/race_schedule_helper.php';",
        "require_once \$_SERVER['DOCUMENT_ROOT'] . '/class.user.php';\n"
        . "require_once \$_SERVER['DOCUMENT_ROOT'] . '/mrl_team/mrl_theme_helper.php';\n"
        . "require_once __DIR__ . '/race_results/race_schedule_helper.php';",
        'team_chart theme helper include'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'PHP'
if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}

if (!isset($adminStatusLine)) {
PHP;

    $new = <<<'PHP'
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
PHP;

    $r = replace_once($c, $old, $new, 'team_chart theme selection');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        "<html>\n<head>",
        "<html class=\"mrl-theme-<?php echo h(\$teamChartTheme); ?>\">\n<head>",
        'team_chart html theme class'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        "    <link rel=\"stylesheet\" href=\"/mrl-styles.css?v=20260123_prg1\">",
        "    <link rel=\"stylesheet\" href=\"/mrl-styles.css?v=20260123_prg1\">\n"
        . "    <link rel=\"stylesheet\" href=\"/mrl_team/mrl_shared_theme.css?v=001\">",
        'team_chart shared theme stylesheet'
    );
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

    $new = <<<'HTML'
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
                <button type="button" id="btnPrevYear" class="teamchart-actionbtn" title="Previous year">&lt;&lt;</button>
                <button type="button" id="btnNextYear" class="teamchart-actionbtn" title="Next year">&gt;&gt;</button>
            </span>

            <select id="segment" name="segment" class="teamchart-select" aria-label="Segment" required>
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

            <?php if ($chartDisplayed): ?>
HTML;

    $r = replace_once($c, $old, $new, 'team_chart approved navigation order');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'CSS'
        .teamchart-actions {
            display: inline-flex;
            gap: 10px;
            align-items: center;
        }

        .teamchart-navpair {
CSS;

    $new = <<<'CSS'
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
CSS;

    $r = replace_once($c, $old, $new, 'team_chart report actions right alignment');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'JS'
        if (btnNextSegment) {
            btnNextSegment.disabled = !segSel || segSel.selectedIndex >= segSel.options.length - 1;
        }
    }
JS;

    $new = <<<'JS'
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
JS;

    $r = replace_once($c, $old, $new, 'team_chart Live disabled behavior');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'JS'
    updateNavButtons();

    if (btnExcel && excelForm && excelYear && excelSeg) {
JS;

    $new = <<<'JS'
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
JS;

    $r = replace_once($c, $old, $new, 'team_chart control-row chart-edge sync');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function patch_current_user_chart(string $c): array
{
    $r = replace_once($c, " * VERSION: v006", " * VERSION: v007", 'current_user_chart version');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        " * CHANGELOG:\n *\n * v006",
        " * CHANGELOG:\n *\n"
        . " * v007 (__INSTALL_TIME__ ET)\n"
        . " * - CONSISTENCY: S4 displays The Chase for 2026 and later; Playoffs remains for 2025 and earlier.\n"
        . " * - PRESERVE: Existing yearly team chart, LP/RD markers, notes, driver tags, paid/team information, and table presentation.\n"
        . " *\n * v006",
        'current_user_chart changelog'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'PHP'
        case 'S4':
            return 'Playoffs';
PHP;

    $new = <<<'PHP'
        case 'S4':
            global $raceYear;
            return (int)$raceYear >= 2026 ? 'The Chase' : 'Playoffs';
PHP;

    $r = replace_once($c, $old, $new, 'current_user_chart S4 year-aware label');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function patch_monitor(string $c): array
{
    $r = replace_once($c, " * VERSION: v139", " * VERSION: v140", 'monitor version');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $r = replace_once(
        $c,
        " * CHANGELOG:\n *\n * v139",
        " * CHANGELOG:\n *\n"
        . " * v140 (__INSTALL_TIME__ ET)\n"
        . " *   - FIX: Central race short-label normalization now resolves World / World Wide Technology variants as World Wide Tech.\n"
        . " *   - CHANGE: Fix is applied in rr_monitor_short_race_label(), the source used by schedule/status short labels.\n"
        . " *   - PRESERVE: Schedule ingestion, race identity, snapshots, scoring, notification, scheduler ownership, and RD behavior.\n"
        . " *\n * v139",
        'monitor changelog'
    );
    if (!$r['ok']) return $r;
    $c = $r['content'];

    $old = <<<'PHP'
function rr_monitor_short_race_label(string $raceName): string
{
    $slug = rr_sanitize_for_folder($raceName);

    $map = [
PHP;

    $new = <<<'PHP'
function rr_monitor_short_race_label(string $raceName): string
{
    $slug = rr_sanitize_for_folder($raceName);

    /*
     * ESPN / schedule naming normalization:
     * the 2026 World Wide Technology event can surface as either the full
     * facility name or the already-truncated "World". Keep the public MRL
     * short label stable at "World Wide Tech".
     */
    if (
        strcasecmp($slug, 'World') === 0
        || stripos($slug, 'World_Wide_Technology') !== false
        || stripos($slug, 'World_Wide_Tech') !== false
    ) {
        return 'World Wide Tech';
    }

    $map = [
PHP;

    $r = replace_once($c, $old, $new, 'monitor central World Wide Tech normalization');
    if (!$r['ok']) return $r;
    $c = $r['content'];

    return ['ok'=>true, 'content'=>$c, 'error'=>''];
}

function shared_theme_css(): string
{
    return <<<'CSS'
/**
 * mrl_shared_theme.css
 *
 * VERSION: v001
 * CREATED: __INSTALL_TIME__ ET
 *
 * Shared MRL public-page theme foundation.
 * First adopter: team_chart.php.
 *
 * The theme names and visual values intentionally match the existing Team Page
 * themes. The Team Page itself remains unchanged in this pass.
 */

:root {
    --mrl-page-width: 85%;
    --mrl-page-max: 1600px;
    --mrl-rd-panel: rgba(28,28,28,.48);
    --mrl-rd-panel-header: rgba(34,34,34,.42);
    --mrl-rd-border: rgba(195,195,195,.34);
    --mrl-rd-gold: #f1c97f;
    --mrl-rd-text: #f2f2f2;
    --mrl-rd-muted: #d4d0c7;
    --mrl-rd-blue: #43b7f0;
    --mrl-rd-shadow: 0 10px 28px rgba(0,0,0,.30);
}

html {
    min-height: 100%;
    background: #151515;
}

html.mrl-theme-cars {
    --mrl-rd-panel: rgba(28,28,28,.48);
    --mrl-rd-panel-header: rgba(34,34,34,.42);
    --mrl-rd-border: rgba(195,195,195,.34);
    --mrl-rd-gold: #f1c97f;
    --mrl-rd-text: #f2f2f2;
    --mrl-rd-muted: #d4d0c7;
    --mrl-rd-blue: #43b7f0;
    background:
        linear-gradient(rgba(10,20,15,.70), rgba(10,20,15,.70)),
        url("/images/cars.jpg") center/cover no-repeat fixed !important;
}

html.mrl-theme-starry-night {
    --mrl-rd-panel: rgba(19,22,31,.56);
    --mrl-rd-panel-header: rgba(24,27,39,.50);
    --mrl-rd-border: rgba(190,198,221,.34);
    --mrl-rd-gold: #e8cf9a;
    --mrl-rd-text: #f2f3f7;
    --mrl-rd-muted: #d4d7e2;
    --mrl-rd-blue: #67bdf2;
    background:
        linear-gradient(rgba(5,8,18,.60), rgba(5,8,18,.60)),
        url("/images/starry_night.jpg") center/cover no-repeat fixed !important;
}

html.mrl-theme-dark {
    --mrl-rd-panel: rgba(28,28,28,.88);
    --mrl-rd-panel-header: rgba(34,34,34,.92);
    --mrl-rd-border: rgba(195,195,195,.34);
    --mrl-rd-gold: #f1c97f;
    --mrl-rd-text: #f2f2f2;
    --mrl-rd-muted: #d4d0c7;
    --mrl-rd-blue: #43b7f0;
    background: #151515 !important;
}

html.mrl-theme-light {
    --mrl-rd-panel: rgba(255,255,255,.90);
    --mrl-rd-panel-header: rgba(244,244,244,.96);
    --mrl-rd-border: rgba(60,60,60,.28);
    --mrl-rd-gold: #8b5b00;
    --mrl-rd-text: #202020;
    --mrl-rd-muted: #555;
    --mrl-rd-blue: #006eaa;
    background: #eceff1 !important;
}

html.mrl-theme-cars body,
html.mrl-theme-starry-night body,
html.mrl-theme-dark body,
html.mrl-theme-light body {
    min-height: 100%;
    background: transparent !important;
    color: var(--mrl-rd-text);
}

/* Shared future-facing primitives. */
.mrl-themed-panel {
    background: var(--mrl-rd-panel);
    border: 1px solid var(--mrl-rd-border);
    color: var(--mrl-rd-text);
    box-shadow: var(--mrl-rd-shadow);
}

.mrl-themed-muted {
    color: var(--mrl-rd-muted);
}

.mrl-themed-accent {
    color: var(--mrl-rd-gold);
}

@media print {
    html,
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
}
CSS;
}

function repair_schedule_json(string $raw): array
{
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok'=>false, 'json'=>'', 'changed'=>0, 'found'=>0, 'error'=>'Invalid schedule JSON.'];
    }

    if (isset($data['year']) && (int)$data['year'] !== 2026) {
        return ['ok'=>false, 'json'=>'', 'changed'=>0, 'found'=>0, 'error'=>'Schedule JSON year is not 2026.'];
    }

    $changed = 0;
    $found = 0;

    $walk = function (&$node) use (&$walk, &$changed, &$found): void {
        if (!is_array($node)) return;

        $raceNumber = 0;
        foreach (['mrl_race_number', 'race_number', 'schedule_sequence'] as $key) {
            if (isset($node[$key]) && (int)$node[$key] > 0) {
                $raceNumber = (int)$node[$key];
                break;
            }
        }

        if ($raceNumber === 28) {
            $found++;

            $current = trim((string)($node['mrl_race_name'] ?? ''));
            if ($current !== 'World Wide Tech') {
                $node['mrl_race_name'] = 'World Wide Tech';
                $changed++;
            }

            foreach (['short_name', 'short_label', 'race_short_name'] as $key) {
                if (isset($node[$key]) && strcasecmp(trim((string)$node[$key]), 'World') === 0) {
                    $node[$key] = 'World Wide Tech';
                    $changed++;
                }
            }
        }

        foreach ($node as &$child) {
            if (is_array($child)) {
                $walk($child);
            }
        }
        unset($child);
    };

    $walk($data);

    if ($found <= 0) {
        return ['ok'=>false, 'json'=>'', 'changed'=>0, 'found'=>0, 'error'=>'No R28 schedule object found.'];
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return ['ok'=>false, 'json'=>'', 'changed'=>0, 'found'=>$found, 'error'=>'JSON re-encode failed.'];
    }

    return [
        'ok'=>true,
        'json'=>$json . "\n",
        'changed'=>$changed,
        'found'=>$found,
        'error'=>'',
    ];
}

function patch_file(string $name, string $content): array
{
    if ($name === 'team_chart.php') return patch_team_chart($content);
    if ($name === 'current_user_team_chart.php') return patch_current_user_chart($content);
    if ($name === 'race_results_monitor.php') return patch_monitor($content);

    return ['ok'=>false, 'content'=>$content, 'error'=>'Unknown target'];
}

function preflight(array $targets, string $backupDir, string $themeFile, string $themeHelper, string $scheduleFile): array
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
                && exact_count($content, 'id="btnLive"') === 1
                && exact_count($content, 'Choose year:') === 1
                && exact_count($content, 'Choose segment:') === 1
                && exact_count($content, 'mrl_shared_theme.css') === 0;
        } elseif ($name === 'current_user_team_chart.php') {
            $baselineOk = $baselineOk
                && exact_count($content, "case 'S4':") === 1
                && exact_count($content, "return 'Playoffs';") >= 1;
        } elseif ($name === 'race_results_monitor.php') {
            $baselineOk = $baselineOk
                && exact_count($content, 'function rr_monitor_short_race_label(string $raceName): string') === 1
                && exact_count($content, "strcasecmp(\$slug, 'World')") === 0;
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
        $checks[] = [$name . ' patch can be built exactly', $patch['ok'], $patch['error']];
        if (!$patch['ok']) $all = false;
    }

    $themeHelperOk = is_file($themeHelper) && is_readable($themeHelper);
    $checks[] = ['Team theme helper available', $themeHelperOk, $themeHelper];
    if (!$themeHelperOk) $all = false;

    $themeAbsent = !file_exists($themeFile);
    $themeDirWritable = is_dir(dirname($themeFile)) && is_writable(dirname($themeFile));
    $checks[] = ['Shared theme v001 target is new', $themeAbsent, $themeFile];
    $checks[] = ['Shared theme directory writable', $themeDirWritable, dirname($themeFile)];
    if (!$themeAbsent || !$themeDirWritable) $all = false;

    $scheduleOk = is_file($scheduleFile) && is_readable($scheduleFile) && is_writable($scheduleFile);
    $checks[] = ['Canonical schedule JSON readable/writable', $scheduleOk, $scheduleFile];

    if ($scheduleOk) {
        $raw = read_text($scheduleFile);
        $contents['_race_results_schedule.json'] = $raw;
        $repair = repair_schedule_json($raw);
        $checks[] = [
            'R28 schedule repair can be built',
            $repair['ok'],
            $repair['ok']
                ? ('R28 objects found: ' . $repair['found'] . '; fields needing repair: ' . $repair['changed'])
                : $repair['error'],
        ];
        if (!$repair['ok']) $all = false;
    } else {
        $all = false;
    }

    $backupReady = is_dir($backupDir) ? is_writable($backupDir) : is_writable(dirname($backupDir));
    $checks[] = ['Backup location writable/creatable', $backupReady, $backupDir];
    if (!$backupReady) $all = false;

    return [
        'all'=>$all,
        'checks'=>$checks,
        'contents'=>$contents,
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
        $wasNew = !empty($info['was_new']);

        if ($wasNew) {
            if (!file_exists($target)) {
                $details[] = [$name, true, 'New file already absent.'];
                continue;
            }

            $ok = @unlink($target);
            $details[] = [$name, $ok, $ok ? 'New file removed.' : 'Could not remove new file.'];
            if (!$ok) $all = false;
            continue;
        }

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
                    $needsLint = substr($target, -4) === '.php';
                    $lint = $needsLint ? php_lint($tmp) : ['ok'=>true, 'output'=>''];

                    if ($lint['ok'] && @rename($tmp, $target)) {
                        $ok = hash('sha256', read_text($target)) === hash('sha256', $data);
                        $msg = $ok ? 'Exact backup restored.' : 'Restore SHA verification failed.';
                    } else {
                        @unlink($tmp);
                        $msg = 'Rollback temp validation/rename failed.';
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

$pre = preflight($targets, $backupDir, $themeFile, $themeHelper, $scheduleFile);
$manifest = load_manifest($manifestPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'apply') {
        $pre = preflight($targets, $backupDir, $themeFile, $themeHelper, $scheduleFile);

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
                    'installer_version'=>MRL_INSTALLER_VERSION,
                    'installed_at_et'=>date('c'),
                    'files'=>[],
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

                    $newContents[$name] = str_replace('__INSTALL_TIME__ ET', $installTime, $patch['content']);

                    $backup = $backupDir . '/' . $name . '.' . $meta['from'] . '.' . $runStamp . '.bak';
                    if (!write_text($backup, $original)) {
                        $buildOk = false;
                        $details[] = [$name . ' backup', false, 'Unable to write backup.'];
                        break;
                    }

                    $manifestData['files'][$name] = [
                        'target'=>$meta['path'],
                        'backup'=>$backup,
                        'original_sha256'=>hash('sha256', $original),
                        'from'=>$meta['from'],
                        'to'=>$meta['to'],
                        'was_new'=>false,
                    ];

                    $details[] = [$name . ' backup', true, basename($backup)];
                }

                if ($buildOk) {
                    $scheduleRaw = $pre['contents']['_race_results_schedule.json'];
                    $scheduleRepair = repair_schedule_json($scheduleRaw);

                    if (!$scheduleRepair['ok']) {
                        $buildOk = false;
                        $details[] = ['schedule JSON build', false, $scheduleRepair['error']];
                    } else {
                        $newContents['_race_results_schedule.json'] = $scheduleRepair['json'];

                        $scheduleBackup = $backupDir . '/_race_results_schedule.json.pre_shared_theme.' . $runStamp . '.bak';
                        if (!write_text($scheduleBackup, $scheduleRaw)) {
                            $buildOk = false;
                            $details[] = ['schedule JSON backup', false, 'Unable to write backup.'];
                        } else {
                            $manifestData['files']['_race_results_schedule.json'] = [
                                'target'=>$scheduleFile,
                                'backup'=>$scheduleBackup,
                                'original_sha256'=>hash('sha256', $scheduleRaw),
                                'from'=>'runtime',
                                'to'=>'R28 World Wide Tech',
                                'was_new'=>false,
                            ];
                            $details[] = [
                                'schedule JSON backup',
                                true,
                                basename($scheduleBackup) . '; R28 objects=' . $scheduleRepair['found']
                            ];
                        }
                    }
                }

                if ($buildOk) {
                    $themeCss = str_replace('__INSTALL_TIME__ ET', $installTime, shared_theme_css());
                    $newContents['mrl_shared_theme.css'] = $themeCss;

                    $manifestData['files']['mrl_shared_theme.css'] = [
                        'target'=>$themeFile,
                        'backup'=>'',
                        'original_sha256'=>'',
                        'from'=>'NEW',
                        'to'=>'v001',
                        'was_new'=>true,
                    ];
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

                if ($buildOk) {
                    $jsonTmp = dirname($scheduleFile) . '/.' . basename($scheduleFile) . '.install.' . bin2hex(random_bytes(4)) . '.tmp';
                    if (!write_text($jsonTmp, $newContents['_race_results_schedule.json'])) {
                        $buildOk = false;
                        $details[] = ['schedule JSON temp write', false, 'Failed.'];
                    } else {
                        $verifyJson = json_decode(read_text($jsonTmp), true);
                        $jsonOk = is_array($verifyJson);
                        $details[] = ['schedule JSON validation', $jsonOk, $jsonOk ? 'Valid JSON.' : 'Decode failed.'];
                        if (!$jsonOk) {
                            @unlink($jsonTmp);
                            $buildOk = false;
                        } else {
                            $temps['_race_results_schedule.json'] = $jsonTmp;
                        }
                    }
                }

                if ($buildOk) {
                    $themeTmp = dirname($themeFile) . '/.' . basename($themeFile) . '.install.' . bin2hex(random_bytes(4)) . '.tmp';
                    if (!write_text($themeTmp, $newContents['mrl_shared_theme.css'])) {
                        $buildOk = false;
                        $details[] = ['shared theme temp write', false, 'Failed.'];
                    } else {
                        $temps['mrl_shared_theme.css'] = $themeTmp;
                        $details[] = ['shared theme build', true, 'mrl_shared_theme.css v001'];
                    }
                }

                if (!$buildOk) {
                    foreach ($temps as $tmp) @unlink($tmp);
                    $message = 'APPLY BLOCKED: build/backup/validation failed before intended production replacement.';
                    $messageClass = 'bad';
                } else {
                    $replaceOrder = [
                        'race_results_monitor.php',
                        '_race_results_schedule.json',
                        'current_user_team_chart.php',
                        'mrl_shared_theme.css',
                        'team_chart.php',
                    ];

                    $targetPaths = [
                        'race_results_monitor.php'=>$targets['race_results_monitor.php']['path'],
                        '_race_results_schedule.json'=>$scheduleFile,
                        'current_user_team_chart.php'=>$targets['current_user_team_chart.php']['path'],
                        'mrl_shared_theme.css'=>$themeFile,
                        'team_chart.php'=>$targets['team_chart.php']['path'],
                    ];

                    $replaceOk = true;

                    foreach ($replaceOrder as $name) {
                        if (!isset($temps[$name]) || !@rename($temps[$name], $targetPaths[$name])) {
                            $replaceOk = false;
                            $details[] = [$name . ' production replace', false, 'Atomic rename failed.'];
                            break;
                        }
                        $details[] = [$name . ' production replace', true, 'Installed.'];
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
                                    && strpos($installed, 'mrl_shared_theme.css?v=001') !== false
                                    && strpos($installed, 'Choose year:') === false
                                    && strpos($installed, 'Choose segment:') === false
                                    && strpos($installed, 'syncControlRowToChart') !== false
                                    && strpos($installed, 'btnLive.disabled') !== false;
                            } elseif ($name === 'current_user_team_chart.php') {
                                $ok = $ok
                                    && strpos($installed, "(int)\$raceYear >= 2026 ? 'The Chase' : 'Playoffs'") !== false;
                            } elseif ($name === 'race_results_monitor.php') {
                                $ok = $ok
                                    && strpos($installed, "strcasecmp(\$slug, 'World')") !== false
                                    && strpos($installed, "return 'World Wide Tech';") !== false;
                            }

                            $details[] = [$name . ' postflight', $ok, $lint['output']];
                            if (!$ok) $postOk = false;
                        }

                        $themeInstalled = read_text($themeFile);
                        $themeOk = strpos($themeInstalled, 'VERSION: v001') !== false
                            && strpos($themeInstalled, 'mrl-theme-starry-night') !== false
                            && strpos($themeInstalled, 'mrl-theme-light') !== false;
                        $details[] = ['shared theme postflight', $themeOk, $themeOk ? 'v001 + four Team themes present.' : 'Verification failed.'];
                        if (!$themeOk) $postOk = false;

                        $scheduleInstalled = read_text($scheduleFile);
                        $scheduleData = json_decode($scheduleInstalled, true);
                        $scheduleCheck = is_array($scheduleData) ? repair_schedule_json($scheduleInstalled) : ['ok'=>false];
                        $scheduleOk = is_array($scheduleData)
                            && !empty($scheduleCheck['ok'])
                            && (int)($scheduleCheck['changed'] ?? -1) === 0;
                        $details[] = ['schedule JSON postflight', $scheduleOk, $scheduleOk ? 'R28 MRL short name is repaired.' : 'R28 verification failed.'];
                        if (!$scheduleOk) $postOk = false;

                        if ($postOk) {
                            $message = 'SUCCESS: Team Chart shared-theme / navigation / race-name package installed.';
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
                ? 'ROLLBACK SUCCESS: exact pre-install state restored.'
                : 'ROLLBACK INCOMPLETE: inspect failed rows.';
            $messageClass = $rb['ok'] ? 'good' : 'bad';
        }
    }

    $pre = preflight($targets, $backupDir, $themeFile, $themeHelper, $scheduleFile);
    $manifest = load_manifest($manifestPath);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Team Chart Shared Theme Installer</title>
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
<h1>MRL Team Chart Shared Theme Installer</h1>

<?php if ($message !== ''): ?>
<div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
<?php endif; ?>

<div class="panel">
<h2>Package</h2>
<ul>
<li><code>team_chart.php</code> v020 → v021</li>
<li><code>current_user_team_chart.php</code> v006 → v007</li>
<li><code>race_results_monitor.php</code> v139 → v140</li>
<li>Creates <code>/mrl_team/mrl_shared_theme.css</code> v001.</li>
<li>Repairs the current R28 short name in <code>_race_results_schedule.json</code>.</li>
<li>Team Chart uses the existing per-user Team theme (Cars / Starry Night / Dark / Light).</li>
<li>Navigation follows the approved mockup and keeps the chart itself untouched.</li>
<li>Live is dim/disabled when already viewing the configured live year/segment.</li>
<li>Personal yearly S4 label becomes The Chase for 2026+.</li>
<li>World Wide Tech correction is now at the monitor short-label source, not another display-page exception.</li>
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

<?php if (is_array($manifest)): ?>
<form method="post" onsubmit="return confirm('Restore the complete exact pre-install state?');">
<input type="hidden" name="action" value="rollback">
<button class="rollback" type="submit">Rollback</button>
</form>
<?php endif; ?>
</div>
</div>

<div class="panel small">
After install verify:
Team Chart background matches your selected Team theme;
control row matches the mockup;
Live is dim while viewing 2026/S4;
R28 footnotes show <strong>World Wide Tech</strong>;
and the personal yearly chart says <strong>The Chase</strong> for 2026.<br><br>
The shared header bar is intentionally <strong>not</strong> added in this pass; this creates the theme foundation it can later reuse.<br><br>
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: <?php echo h(MRL_INSTALLER_VERSION); ?>
</div>
</div>
</body>
</html>
