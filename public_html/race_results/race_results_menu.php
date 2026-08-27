<?php
/**
 * race_results_menu.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/8/2026 9:29:06 pm
 *
 * CHANGELOG:
 * v002 (8/8/2026 9:29:06 pm)
 * - Restyled the menu to visually match the dark race_results_dashboard.php theme.
 * - Added charcoal page/panel backgrounds, warm gold/peach headings and borders,
 *   green status accents, and dashboard-style dark buttons/pills.
 * - Preserved all v001 page discovery, environment detection, filtering,
 *   and cross-environment MRL/testphp8 behavior.
 *
 * v001 (7/27/2026 2:23:17 pm)
 * - Initial visual navigation hub for the race_results area.
 * - Added curated sections for standings, charts, dashboards, monitors,
 *   scheduler reference files, and automatically discovered pages.
 * - Added automatic MRL / testphp8 environment detection.
 * - Added file-existence checks so the same file can run in either environment.
 * - Added browser-side filtering for quickly locating a page or tool.
 */

date_default_timezone_set('America/New_York');

$selfFile = basename(__FILE__);
$baseDir = __DIR__;
$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';

$environment = 'CUSTOM';
if (stripos($baseDir, 'testphp8') !== false || stripos($host, 'testphp8') !== false) {
    $environment = 'TESTPHP8';
} elseif (
    stripos($baseDir, 'manliusracingleague') !== false ||
    stripos($host, 'manliusracingleague') !== false ||
    stripos($host, 'mrl') !== false
) {
    $environment = 'MRL';
}

function mrl_menu_normalize_path($path)
{
    $path = str_replace('\\', '/', (string) $path);
    return preg_replace('#/+#', '/', $path);
}

function mrl_menu_find_existing_path(array $candidates, $baseDir)
{
    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate);

        if ($candidate === '') {
            continue;
        }

        $fullPath = mrl_menu_normalize_path($baseDir . '/' . $candidate);

        if (is_file($fullPath)) {
            return $candidate;
        }
    }

    return '';
}

function mrl_menu_pretty_label($path)
{
    $name = basename((string) $path);
    $name = preg_replace('/\.(php|html?|json|txt)$/i', '', $name);
    $name = str_replace(array('_', '-'), ' ', $name);
    $name = ucwords($name);

    $replacements = array(
        'Mrl' => 'MRL',
        'Php' => 'PHP',
        'Json' => 'JSON',
        'Rd' => 'RD',
        'Lp' => 'LP',
        'Aag' => 'AAG',
        'Ui' => 'UI',
        'Api' => 'API',
        'Nascar' => 'NASCAR'
    );

    foreach ($replacements as $from => $to) {
        $name = preg_replace('/\b' . preg_quote($from, '/') . '\b/', $to, $name);
    }

    return $name;
}

function mrl_menu_file_type($path)
{
    $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

    switch ($extension) {
        case 'php':
            return 'Page';
        case 'html':
        case 'htm':
            return 'HTML';
        case 'json':
            return 'Data';
        case 'txt':
            return 'Text';
        default:
            return $extension !== '' ? strtoupper($extension) : 'File';
    }
}

function mrl_menu_build_items(array $items, $baseDir, array &$registeredPaths)
{
    $built = array();

    foreach ($items as $item) {
        $foundPath = mrl_menu_find_existing_path($item['paths'], $baseDir);

        if ($foundPath === '') {
            continue;
        }

        $registeredPaths[$foundPath] = true;

        $built[] = array(
            'title' => $item['title'],
            'description' => $item['description'],
            'path' => $foundPath,
            'badge' => isset($item['badge']) ? $item['badge'] : mrl_menu_file_type($foundPath),
            'icon' => isset($item['icon']) ? $item['icon'] : '•'
        );
    }

    return $built;
}

function mrl_menu_discover_extra_pages($baseDir, $selfFile, array $registeredPaths)
{
    $items = array();

    $excludedExact = array(
        $selfFile,
        'cron_master_scheduler.php',
        'race_results_monitor.php',
        'race_results_revision_monitor.php',
        'functions_mrl.php'
    );

    $excludedPrefixes = array(
        '_',
        'install_',
        'submit_',
        'config_',
        'functions_',
        'backup_',
        'snapshot_',
        'temp_'
    );

    $allowedExtensions = array('php', 'html', 'htm');
    $files = @scandir($baseDir);

    if (!is_array($files)) {
        return $items;
    }

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $fullPath = mrl_menu_normalize_path($baseDir . '/' . $file);

        if (!is_file($fullPath)) {
            continue;
        }

        if (in_array($file, $excludedExact, true)) {
            continue;
        }

        $skip = false;

        foreach ($excludedPrefixes as $prefix) {
            if (stripos($file, $prefix) === 0) {
                $skip = true;
                break;
            }
        }

        if ($skip) {
            continue;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            continue;
        }

        if (isset($registeredPaths[$file])) {
            continue;
        }

        $items[] = array(
            'title' => mrl_menu_pretty_label($file),
            'description' => 'Auto-discovered page in the race_results folder.',
            'path' => $file,
            'badge' => mrl_menu_file_type($file),
            'icon' => '➜'
        );
    }

    usort($items, function ($a, $b) {
        return strcasecmp($a['title'], $b['title']);
    });

    return $items;
}

$registeredPaths = array();

$sectionDefinitions = array(
    array(
        'title' => 'Standings & Snapshot Pages',
        'icon' => '🏁',
        'class' => 'accent-red',
        'items' => array(
            array(
                'title' => 'Weekly Standings',
                'description' => 'Primary standings page and the main user-facing destination.',
                'paths' => array('weekly_standings.php'),
                'icon' => '🏆',
                'badge' => 'Main'
            ),
            array(
                'title' => 'Standings Timeline Lite',
                'description' => 'Lightweight snapshot and as-of standings browser.',
                'paths' => array('standings_timeline_lite.php'),
                'icon' => '🕒'
            ),
            array(
                'title' => 'Standings Timeline',
                'description' => 'Full timeline and historical audit browser.',
                'paths' => array('standings_timeline.php'),
                'icon' => '📚'
            ),
            array(
                'title' => 'Weekly Winners',
                'description' => 'Weekly race winner presentation if available.',
                'paths' => array('weekly_winners.php'),
                'icon' => '🥇'
            )
        )
    ),
    array(
        'title' => 'Charts & Team Views',
        'icon' => '📊',
        'class' => 'accent-purple',
        'items' => array(
            array(
                'title' => 'Current Segment Chart',
                'description' => 'Current segment standings and chart view.',
                'paths' => array('current_segment_chart.php'),
                'icon' => '📈'
            ),
            array(
                'title' => 'Current Segment by Entry Time',
                'description' => 'Current segment chart ordered by entry time.',
                'paths' => array('current_segment_chart_by_entry_time.php'),
                'icon' => '⏱'
            ),
            array(
                'title' => 'Current User Team Chart',
                'description' => 'Current-year user and team chart.',
                'paths' => array('current_user_team_chart.php'),
                'icon' => '👥'
            ),
            array(
                'title' => 'Prior-Year User Team Chart',
                'description' => 'Prior-year user and team chart.',
                'paths' => array('prior_year_user_team_chart.php'),
                'icon' => '🗂'
            ),
            array(
                'title' => 'Team Chart',
                'description' => 'General team chart page.',
                'paths' => array('team_chart.php'),
                'icon' => '📋'
            )
        )
    ),
    array(
        'title' => 'Dashboard & Browser Tools',
        'icon' => '🧭',
        'class' => 'accent-blue',
        'items' => array(
            array(
                'title' => 'Race Results Dashboard',
                'description' => 'Primary monitoring dashboard if present in this environment.',
                'paths' => array('race_results_dashboard.php', 'dashboard.php', 'index.php'),
                'icon' => '🖥'
            ),
            array(
                'title' => 'Debug Information',
                'description' => 'Dedicated debug or scheduler status page if present.',
                'paths' => array('race_results_debug.php', 'debug.php', 'scheduler_debug.php', 'dashboard_debug.php'),
                'icon' => '🛠'
            ),
            array(
                'title' => 'At a Glance',
                'description' => 'Quick browser status page outside the race_results folder.',
                'paths' => array('../aag.html', '../aag.php', '../at_a_glance.html', '../at_a_glance.php'),
                'icon' => '👀'
            )
        )
    ),
    array(
        'title' => 'Monitoring & Scheduler Reference',
        'icon' => '⚙',
        'class' => 'accent-green',
        'items' => array(
            array(
                'title' => 'Race Results Schedule',
                'description' => 'Current race schedule data used by the monitoring system.',
                'paths' => array('_race_results_schedule.json'),
                'icon' => '�
',
                'badge' => 'Data'
            ),
            array(
                'title' => 'Race Monitor State',
                'description' => 'Current race-results monitor state snapshot.',
                'paths' => array('_race_results_monitor_state.json'),
                'icon' => '📡',
                'badge' => 'Data'
            ),
            array(
                'title' => 'Race Monitor Heartbeat',
                'description' => 'Heartbeat file for the main race-results monitor.',
                'paths' => array('_race_results_monitor_heartbeat.txt'),
                'icon' => '💓',
                'badge' => 'Text'
            ),
            array(
                'title' => 'Revision Monitor Heartbeat',
                'description' => 'Heartbeat file for the revision monitor.',
                'paths' => array('_race_results_revision_monitor_heartbeat.txt'),
                'icon' => '🔁',
                'badge' => 'Text'
            ),
            array(
                'title' => 'Master Scheduler Configuration',
                'description' => 'The schedule configuration used by the master scheduler.',
                'paths' => array('_scheduler/schedule.json'),
                'icon' => '🧩',
                'badge' => 'Data'
            )
        )
    )
);

$sections = array();
$allVisibleItems = array();

foreach ($sectionDefinitions as $section) {
    $items = mrl_menu_build_items($section['items'], $baseDir, $registeredPaths);

    if (empty($items)) {
        continue;
    }

    $section['items'] = $items;
    $sections[] = $section;

    foreach ($items as $item) {
        $allVisibleItems[] = $item;
    }
}

$extraPages = mrl_menu_discover_extra_pages($baseDir, $selfFile, $registeredPaths);
$quickLinks = array_slice($allVisibleItems, 0, 5);
$totalCards = count($allVisibleItems) + count($extraPages);
$generatedAt = date('n/j/Y g:i:s a');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL Race Results Menu</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    :root {
        --bg: #141414;
        --bg-soft: #181818;
        --panel: #20201f;
        --panel-2: #252524;
        --panel-soft: #1a1a1a;
        --text: #f2f2f2;
        --muted: #d2d2d2;
        --muted-2: #a9a9a9;
        --line: #4e4a43;
        --line-warm: #705b39;
        --gold: #ffc77f;
        --gold-soft: #d9ab6c;
        --green: #48de82;
        --green-bg: #173a28;
        --green-line: #34714f;
        --blue: #2674d9;
        --blue-hover: #3384ef;
        --shadow: 0 8px 22px rgba(0, 0, 0, 0.28);
    }

    * {
        box-sizing: border-box;
    }

    html {
        color-scheme: dark;
    }

    body {
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        background: var(--bg);
        color: var(--text);
    }

    .wrap {
        max-width: 1480px;
        margin: 0 auto;
        padding: 20px 20px 44px;
    }

    .hero {
        background: linear-gradient(180deg, #1c2c24 0%, #1c211f 100%);
        border: 1px solid #35684b;
        border-radius: 17px;
        padding: 18px 20px;
        box-shadow: var(--shadow);
        margin-bottom: 14px;
    }

    .hero-top {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }

    .hero h1 {
        margin: 0 0 2px;
        font-size: 30px;
        line-height: 1.1;
        letter-spacing: 0.06em;
        color: #f4f4f4;
        text-shadow: 1px 1px 0 rgba(255, 199, 127, 0.45);
    }

    .hero p {
        margin: 0;
        color: #e0e0e0;
        line-height: 1.5;
        max-width: 880px;
    }

    .status-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 700;
        padding: 8px 12px;
        border-radius: 999px;
        background: #242424;
        border: 1px solid #605747;
        color: #f3f3f3;
    }

    .status-pill.environment {
        background: var(--green-bg);
        border-color: var(--green-line);
        color: var(--green);
    }

    .status-pill.count {
        background: #2a2620;
        border-color: #7a633d;
        color: var(--gold);
    }

    .hero-note {
        color: #d4d4d4;
        font-size: 12px;
        line-height: 1.55;
        text-align: right;
    }

    .filter-wrap {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid rgba(255,255,255,0.09);
    }

    .filter-wrap label {
        display: block;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 700;
        color: var(--gold);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .filter-input {
        width: 100%;
        max-width: 560px;
        padding: 11px 13px;
        border: 1px solid #655b4c;
        border-radius: 11px;
        font-size: 15px;
        background: #181818;
        color: var(--text);
        outline: none;
    }

    .filter-input::placeholder {
        color: #969696;
    }

    .filter-input:focus {
        border-color: #b88a4f;
        box-shadow: 0 0 0 3px rgba(255, 199, 127, 0.10);
    }

    .quick-links {
        margin-top: 14px;
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .quick-links a {
        text-decoration: none;
        color: #f0f0f0;
        background: #242424;
        border: 1px solid #665942;
        border-radius: 12px;
        padding: 10px 13px;
        font-weight: 700;
        font-size: 14px;
        transition: 0.15s ease;
    }

    .quick-links a:hover {
        background: #302b23;
        border-color: #9a7444;
        color: var(--gold);
        transform: translateY(-1px);
    }

    .section {
        margin-top: 18px;
        background: linear-gradient(180deg, #211f1b 0%, #1d1d1c 100%);
        border: 1px solid var(--line-warm);
        border-radius: 17px;
        padding: 14px;
        box-shadow: var(--shadow);
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
        padding: 0 2px;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title h2 {
        margin: 0;
        font-size: 22px;
        color: var(--gold);
        text-shadow: 1px 1px 0 rgba(255,255,255,0.08);
    }

    .section-count {
        color: #d8d8d8;
        font-size: 13px;
        font-weight: 700;
        background: #242424;
        border: 1px solid #514d46;
        border-radius: 999px;
        padding: 6px 10px;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
        gap: 12px;
    }

    .card {
        background: linear-gradient(180deg, var(--panel-2) 0%, var(--panel) 100%);
        border: 1px solid #3d3d3b;
        border-radius: 15px;
        padding: 15px;
        display: flex;
        flex-direction: column;
        min-height: 215px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.025);
    }

    .card:hover {
        border-color: #6a5b45;
        background: linear-gradient(180deg, #292826 0%, #222220 100%);
    }

    .card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 11px;
    }

    .card-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex: 0 0 auto;
        background: #1b1b1b;
        border: 1px solid #5a5041;
    }

    .accent-red .card-icon,
    .accent-purple .card-icon,
    .accent-blue .card-icon,
    .accent-green .card-icon,
    .accent-gold .card-icon {
        background: #1b1b1b;
        border-color: #6a593e;
    }

    .card-badge {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.03em;
        padding: 5px 8px;
        border-radius: 999px;
        background: #242424;
        border: 1px solid #5e5548;
        color: #dedede;
        white-space: nowrap;
    }

    .card h3 {
        margin: 0 0 7px;
        font-size: 18px;
        line-height: 1.25;
        color: #f0f0f0;
    }

    .card p {
        margin: 0 0 13px;
        color: #cccccc;
        line-height: 1.45;
        flex: 1 1 auto;
    }

    .path {
        font-family: Consolas, Monaco, "Courier New", monospace;
        font-size: 12px;
        color: #d0d0d0;
        background: #171717;
        border: 1px solid #343434;
        border-radius: 9px;
        padding: 9px 10px;
        word-break: break-word;
        margin-bottom: 12px;
    }

    .open-button {
        text-decoration: none;
        display: inline-block;
        text-align: center;
        padding: 10px 12px;
        border-radius: 11px;
        font-weight: 700;
        color: #f7f7f7;
        background: #252525;
        border: 1px solid #746040;
        transition: 0.15s ease;
    }

    .open-button:hover {
        background: #302a21;
        border-color: #b4844c;
        color: var(--gold);
        transform: translateY(-1px);
    }

    .footer-note {
        margin-top: 18px;
        background: #1b1b1b;
        border: 1px solid #514938;
        border-radius: 15px;
        padding: 15px 17px;
        color: #c7c7c7;
        line-height: 1.5;
    }

    .footer-note strong {
        color: var(--gold);
    }

    .hidden-card {
        display: none !important;
    }

    .hidden-section {
        display: none !important;
    }

    @media (max-width: 700px) {
        .wrap {
            padding: 14px 10px 32px;
        }

        .hero h1 {
            font-size: 25px;
        }

        .hero-note {
            text-align: left;
        }

        .section-title h2 {
            font-size: 20px;
        }

        .section {
            padding: 11px;
        }
    }
</style>
</head>
<body>
<div class="wrap">

    <div class="hero">
        <div class="hero-top">
            <div>
                <h1>MRL Race Results Menu</h1>
                <p>
                    A visual hub for the standings, chart, dashboard, monitoring, and reference
                    pages around <strong>race_results</strong>. Cards only appear when the target
                    file exists, allowing this same page to work in both MRL and testphp8.
                </p>

                <div class="status-row">
                    <span class="status-pill environment">Environment: <?php echo htmlspecialchars($environment); ?></span>
                    <span class="status-pill count">Visible cards: <?php echo (int) $totalCards; ?></span>
                    <span class="status-pill">Loaded: <?php echo htmlspecialchars($generatedAt); ?></span>
                </div>
            </div>

            <div class="hero-note">
                VERSION: v001<br>
                Folder: race_results<br>
                Missing target files are hidden automatically.
            </div>
        </div>

        <div class="filter-wrap">
            <label for="menuFilter">Find a page or tool</label>
            <input
                id="menuFilter"
                class="filter-input"
                type="search"
                placeholder="Type standings, dashboard, monitor, chart..."
                autocomplete="off"
            >
        </div>

        <?php if (!empty($quickLinks)) : ?>
            <div class="quick-links">
                <?php foreach ($quickLinks as $quickLink) : ?>
                    <a href="<?php echo htmlspecialchars($quickLink['path']); ?>">
                        <?php echo htmlspecialchars($quickLink['icon']); ?>
                        <?php echo htmlspecialchars($quickLink['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php foreach ($sections as $section) : ?>
        <section class="section <?php echo htmlspecialchars($section['class']); ?>" data-menu-section>
            <div class="section-header">
                <div class="section-title">
                    <div style="font-size: 24px;"><?php echo htmlspecialchars($section['icon']); ?></div>
                    <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                </div>
                <div class="section-count"><?php echo count($section['items']); ?> item(s)</div>
            </div>

            <div class="grid">
                <?php foreach ($section['items'] as $item) : ?>
                    <article
                        class="card"
                        data-menu-card
                        data-search="<?php echo htmlspecialchars(strtolower($item['title'] . ' ' . $item['description'] . ' ' . $item['path'])); ?>"
                    >
                        <div class="card-top">
                            <div class="card-icon"><?php echo htmlspecialchars($item['icon']); ?></div>
                            <div class="card-badge"><?php echo htmlspecialchars($item['badge']); ?></div>
                        </div>

                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>

                        <div class="path"><?php echo htmlspecialchars($item['path']); ?></div>

                        <a class="open-button" href="<?php echo htmlspecialchars($item['path']); ?>">Open</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if (!empty($extraPages)) : ?>
        <section class="section accent-gold" data-menu-section>
            <div class="section-header">
                <div class="section-title">
                    <div style="font-size: 24px;">✨</div>
                    <h2>Other Pages Found in race_results</h2>
                </div>
                <div class="section-count"><?php echo count($extraPages); ?> item(s)</div>
            </div>

            <div class="grid">
                <?php foreach ($extraPages as $item) : ?>
                    <article
                        class="card"
                        data-menu-card
                        data-search="<?php echo htmlspecialchars(strtolower($item['title'] . ' ' . $item['description'] . ' ' . $item['path'])); ?>"
                    >
                        <div class="card-top">
                            <div class="card-icon"><?php echo htmlspecialchars($item['icon']); ?></div>
                            <div class="card-badge"><?php echo htmlspecialchars($item['badge']); ?></div>
                        </div>

                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>

                        <div class="path"><?php echo htmlspecialchars($item['path']); ?></div>

                        <a class="open-button" href="<?php echo htmlspecialchars($item['path']); ?>">Open</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="footer-note">
        <strong>v002:</strong> Same menu structure and discovery behavior as v001, now visually
        integrated with the dark MRL race-results dashboard family. Future versions can concentrate
        on page organization, labels, favorites, and any tools you decide should be added or removed.
    </div>

</div>

<script>
(function () {
    'use strict';

    var filter = document.getElementById('menuFilter');

    if (!filter) {
        return;
    }

    filter.addEventListener('input', function () {
        var query = filter.value.toLowerCase().trim();
        var sections = document.querySelectorAll('[data-menu-section]');

        sections.forEach(function (section) {
            var cards = section.querySelectorAll('[data-menu-card]');
            var visibleCount = 0;

            cards.forEach(function (card) {
                var searchText = card.getAttribute('data-search') || '';
                var matches = query === '' || searchText.indexOf(query) !== -1;

                card.classList.toggle('hidden-card', !matches);

                if (matches) {
                    visibleCount++;
                }
            });

            section.classList.toggle('hidden-section', visibleCount === 0);
        });
    });
}());
</script>

</body>
</html>

