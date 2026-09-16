<?php
declare(strict_types=1);

/**
 * install_mrl_team_redesign.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/27/2026 12:36:47 pm
 *
 * PURPOSE:
 * Create an isolated team-page redesign test from the current Live team.php
 * without modifying team.php.
 *
 * OUTPUTS:
 * - team_redesign.php
 * - mrl_team_page_content.json
 *
 * SAFETY:
 * - team.php is read-only and is never modified.
 * - Requires the current source baseline to identify as VERSION: v034.
 * - Existing team_redesign.php is backed up before replacement.
 * - Existing mrl_team_page_content.json is preserved.
 *
 * CHANGELOG:
 * v001 (8/27/2026 12:36:47 pm)
 * - NEW: First isolated MRL team-page redesign builder.
 * - NEW: Dashboard-style narrow sticky header with local JavaScript live clock.
 * - NEW: Header width follows the same centered content width as the page panels.
 * - NEW: Translucent dashboard-style Admin / League Info / Team Menu panels.
 * - NEW: League and Team menu links are data-driven from mrl_team_page_content.json.
 * - CHANGE: DB diagnostic banner is disabled on the redesign page.
 * - PRESERVE: Existing team.php remains untouched.
 * - PRESERVE: Current team/pick/LP/RD/RP/scoring logic is copied from team.php.
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v001';
const EXPECTED_SOURCE_VERSION = 'v034';
const REDESIGN_VERSION = 'v001';
const REDESIGN_LAST_MODIFIED = '8/27/2026 12:36:47 pm';

$baseDir = __DIR__;
$sourcePath = $baseDir . '/team.php';
$targetPath = $baseDir . '/team_redesign.php';
$contentPath = $baseDir . '/mrl_team_page_content.json';

function ih(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function status_row(string $label, bool $ok, string $detail = ''): void
{
    $class = $ok ? 'ok' : 'bad';
    $word = $ok ? 'PASS' : 'FAIL';
    echo '<tr><td>' . ih($label) . '</td><td class="' . $class . '">' . $word . '</td><td>'
        . ih($detail) . '</td></tr>';
}

function write_atomic(string $path, string $content): bool
{
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function build_default_content(): array
{
    return [
        'schema_version' => 1,
        'updated_at' => '2026-08-27 12:36:47',
        'league_panel' => [
            'title' => 'League Information',
            'items' => [
                [
                    'label' => '{year} Fees & Payment Info',
                    'url' => '/{year}_Fees.php',
                    'enabled' => true,
                    'new_tab' => true,
                ],
                [
                    'label' => '{year} Rules',
                    'url' => '/{year}_Rules.php',
                    'enabled' => true,
                    'new_tab' => true,
                ],
                [
                    'label' => '{year} Race Schedule - PDF',
                    'url' => '/wp-content/uploads/{year}/01/{year}_Schedule_MRL.pdf',
                    'enabled' => true,
                    'new_tab' => true,
                ],
                [
                    'label' => '{year} Race Schedule - Spreadsheet',
                    'url' => '/wp-content/uploads/{year}/01/{year}_Schedule_MRL.xlsx',
                    'enabled' => true,
                    'new_tab' => true,
                ],
                [
                    'label' => '{year} Race Schedule on NASCAR.com',
                    'url' => 'https://www.nascar.com/nascar-cup-series/{year}/schedule/',
                    'enabled' => true,
                    'new_tab' => true,
                ],
            ],
        ],
        'team_panel' => [
            'title' => 'Team Menu',
            'items' => [
                [
                    'label' => 'Driver Chart(s) - view, print for any year',
                    'url' => '/showDrivers.php',
                    'enabled' => true,
                    'new_tab' => true,
                ],
                [
                    'label' => 'Team Chart(s) - view, PDF, spreadsheet for any year/segment',
                    'url' => '/team_chart.php',
                    'enabled' => true,
                    'new_tab' => true,
                ],
                [
                    'label' => 'Submitted Teams for Current Segment',
                    'url' => '/submitted_teams.php',
                    'enabled' => true,
                    'new_tab' => true,
                ],
                [
                    'label' => 'Your Profile page',
                    'url' => '/profile.php',
                    'enabled' => true,
                    'new_tab' => true,
                ],
            ],
        ],
    ];
}

function build_redesign(string $source): array
{
    $notes = [];

    if (strpos($source, 'VERSION: ' . EXPECTED_SOURCE_VERSION) === false) {
        return [false, '', ['Source team.php is not the expected ' . EXPECTED_SOURCE_VERSION . ' baseline.']];
    }

    $work = $source;

    // Identify the test file as its own version while retaining the inherited changelog.
    $work = preg_replace('/\* team\.php(\R)/', '* team_redesign.php$1', $work, 1, $countName);
    $work = preg_replace('/VERSION:\s*v034/', 'VERSION: ' . REDESIGN_VERSION, $work, 1, $countVersion);
    $work = preg_replace(
        '/LAST MODIFIED:\s*8\/25\/2026 12:26:16 am/',
        'LAST MODIFIED: ' . REDESIGN_LAST_MODIFIED,
        $work,
        1,
        $countModified
    );

    $redesignChange = " *\n"
        . " * v001 (8/27/2026 12:36:47 pm)\n"
        . " * - DESIGN TEST: Isolated team-page presentation redesign; production team.php remains untouched.\n"
        . " * - UI: Narrow centered sticky header based on race_results_dashboard.php / admin_setup.php styling.\n"
        . " * - UI: Native JavaScript live clock replaces the external clock iframe on this test page.\n"
        . " * - UI: Admin, League Information and Team Menu use translucent modular panels.\n"
        . " * - NEW: League/Team links load from mrl_team_page_content.json with built-in fallback defaults.\n"
        . " * - CHANGE: DB debug banner disabled on this test page.\n"
        . " * - PRESERVE: Inherited v034 pick, LP, RP/RD, scoring, privacy and scheduler behavior.\n";

    $work = preg_replace('/(\* CHANGELOG:\R)/', '$1' . $redesignChange, $work, 1, $countChange);

    // The test page should not display the DB diagnostic banner.
    $work = str_replace('$showDbDebugBanner = true;', '$showDbDebugBanner = false;', $work, $countDebug);

    // Add data-driven panel content before HTML starts.
    $contentLoader = <<<'PHP'

/*
 * team_redesign.php presentation content.
 * The JSON file is deliberately separate from application logic so the page
 * can later be managed by an admin editor without editing this PHP file.
 */
$teamPageContentDefaults = [
    'league_panel' => [
        'title' => 'League Information',
        'items' => [
            ['label' => '{year} Fees & Payment Info', 'url' => '/{year}_Fees.php', 'enabled' => true, 'new_tab' => true],
            ['label' => '{year} Rules', 'url' => '/{year}_Rules.php', 'enabled' => true, 'new_tab' => true],
            ['label' => '{year} Race Schedule - PDF', 'url' => '/wp-content/uploads/{year}/01/{year}_Schedule_MRL.pdf', 'enabled' => true, 'new_tab' => true],
            ['label' => '{year} Race Schedule - Spreadsheet', 'url' => '/wp-content/uploads/{year}/01/{year}_Schedule_MRL.xlsx', 'enabled' => true, 'new_tab' => true],
            ['label' => '{year} Race Schedule on NASCAR.com', 'url' => 'https://www.nascar.com/nascar-cup-series/{year}/schedule/', 'enabled' => true, 'new_tab' => true],
        ],
    ],
    'team_panel' => [
        'title' => 'Team Menu',
        'items' => [
            ['label' => 'Driver Chart(s) - view, print for any year', 'url' => '/showDrivers.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Team Chart(s) - view, PDF, spreadsheet for any year/segment', 'url' => '/team_chart.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Submitted Teams for Current Segment', 'url' => '/submitted_teams.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Your Profile page', 'url' => '/profile.php', 'enabled' => true, 'new_tab' => true],
        ],
    ],
];

$teamPageContent = $teamPageContentDefaults;
$teamPageContentPath = __DIR__ . '/mrl_team_page_content.json';
if (is_file($teamPageContentPath)) {
    $teamPageContentRaw = @file_get_contents($teamPageContentPath);
    if (is_string($teamPageContentRaw) && trim($teamPageContentRaw) !== '') {
        $teamPageContentDecoded = json_decode($teamPageContentRaw, true);
        if (is_array($teamPageContentDecoded)) {
            foreach (['league_panel', 'team_panel'] as $panelKey) {
                if (isset($teamPageContentDecoded[$panelKey]) && is_array($teamPageContentDecoded[$panelKey])) {
                    $teamPageContent[$panelKey] = array_replace(
                        $teamPageContentDefaults[$panelKey],
                        $teamPageContentDecoded[$panelKey]
                    );
                }
            }
        }
    }
}

function teampage_redesign_token(string $value, string $raceYear): string
{
    return str_replace('{year}', $raceYear, $value);
}

function teampage_redesign_render_links(array $panel, string $raceYear): void
{
    $items = isset($panel['items']) && is_array($panel['items']) ? $panel['items'] : [];
    echo '<div class="mrl-rd-links">';

    foreach ($items as $item) {
        if (!is_array($item) || empty($item['enabled'])) {
            continue;
        }

        $label = teampage_redesign_token((string)($item['label'] ?? ''), $raceYear);
        $url = teampage_redesign_token((string)($item['url'] ?? ''), $raceYear);
        if ($label === '' || $url === '') {
            continue;
        }

        $newTab = !empty($item['new_tab']);
        $target = $newTab ? ' target="_blank" rel="noopener noreferrer"' : '';

        echo '<a class="mrl-rd-link" href="' . teampage_h($url) . '"' . $target . '>'
            . '<span class="mrl-rd-link-arrow">›</span>'
            . '<span>' . teampage_h($label) . '</span>'
            . '</a>';
    }

    echo '</div>';
}

PHP;

    $marker = "?>\n<!DOCTYPE html>";
    if (strpos($work, $marker) === false) {
        return [false, '', ['Could not locate the HTML start marker.']];
    }
    $work = str_replace($marker, $contentLoader . "\n?>\n<!DOCTYPE html>", $work, $countLoader);

    // Add redesign CSS after the inherited CSS so it safely overrides old presentation rules.
    $redesignCss = <<<'CSS'

        /* =====================================================================
         * team_redesign.php v001 - isolated presentation layer
         * =================================================================== */
        :root{
            --mrl-rd-bg:rgba(18,18,18,.76);
            --mrl-rd-panel:rgba(30,30,30,.72);
            --mrl-rd-panel-strong:rgba(23,34,29,.88);
            --mrl-rd-border:rgba(206,170,104,.34);
            --mrl-rd-green:#5be08d;
            --mrl-rd-gold:#efc982;
            --mrl-rd-text:#f3f0e9;
            --mrl-rd-muted:#c8c1b5;
            --mrl-rd-blue:#38a9ef;
            --mrl-rd-shadow:0 10px 28px rgba(0,0,0,.30);
            --mrl-rd-width:80%;
            --mrl-rd-max:1500px;
        }

        body{
            background-color:transparent !important;
            padding-top:0 !important;
        }

        .mrl-rd-shell{
            width:var(--mrl-rd-width);
            max-width:var(--mrl-rd-max);
            margin:0 auto;
        }

        .mrl-rd-sticky{
            position:sticky;
            top:8px;
            z-index:1000;
            margin:8px auto 14px;
            border:1px solid rgba(58,125,83,.72);
            border-radius:14px;
            background:linear-gradient(180deg,rgba(20,49,35,.94),rgba(19,28,24,.91));
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
            box-shadow:var(--mrl-rd-shadow);
        }

        .mrl-rd-header{
            min-height:58px;
            display:grid;
            grid-template-columns:minmax(170px,1fr) minmax(260px,2fr) minmax(190px,1fr);
            align-items:center;
            gap:12px;
            padding:8px 14px;
        }

        .mrl-rd-user{
            position:relative;
            justify-self:start;
        }

        .mrl-rd-user-button{
            appearance:none;
            border:1px solid rgba(239,201,130,.34);
            border-radius:999px;
            background:rgba(255,255,255,.045);
            color:var(--mrl-rd-text);
            padding:7px 12px;
            font:600 15px/1.1 Tahoma,Verdana,Segoe UI,sans-serif;
            cursor:pointer;
        }

        .mrl-rd-user-button:hover{
            border-color:rgba(239,201,130,.72);
            background:rgba(255,255,255,.08);
        }

        .mrl-rd-user-menu{
            display:none;
            position:absolute;
            top:calc(100% + 7px);
            left:0;
            min-width:190px;
            padding:7px;
            border:1px solid var(--mrl-rd-border);
            border-radius:10px;
            background:rgba(22,22,22,.97);
            box-shadow:var(--mrl-rd-shadow);
        }

        .mrl-rd-user.open .mrl-rd-user-menu{display:block}

        .mrl-rd-user-menu a{
            display:block;
            padding:8px 10px;
            border-radius:7px;
            color:var(--mrl-rd-text) !important;
            font:14px/1.2 Tahoma,Verdana,Segoe UI,sans-serif;
            text-decoration:none;
        }

        .mrl-rd-user-menu a:hover{
            background:rgba(255,255,255,.08);
            color:#fff !important;
        }

        .mrl-rd-title{
            min-width:0;
            text-align:center;
            color:#fff5e2;
            font:800 20px/1.1 Tahoma,Verdana,Segoe UI,sans-serif;
            letter-spacing:.5px;
        }

        .mrl-rd-title small{
            display:block;
            margin-top:2px;
            color:var(--mrl-rd-gold);
            font-size:12px;
            font-weight:700;
            letter-spacing:.2px;
        }

        .mrl-rd-clock{
            justify-self:end;
            text-align:right;
            color:var(--mrl-rd-text);
            font:700 14px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;
            white-space:nowrap;
        }

        .mrl-rd-clock small{
            display:block;
            margin-top:2px;
            color:var(--mrl-rd-muted);
            font-size:11px;
            font-weight:600;
        }

        .mrl-rd-top{
            width:var(--mrl-rd-width);
            max-width:var(--mrl-rd-max);
            margin:0 auto 18px;
            color:var(--mrl-rd-text);
            font-family:Tahoma,Verdana,Segoe UI,sans-serif;
        }

        .mrl-rd-greeting{
            margin:4px 2px 10px;
            color:var(--mrl-rd-gold);
            font-size:16px;
        }

        .mrl-rd-grid{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:12px;
            align-items:start;
        }

        .mrl-rd-grid.no-admin{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }

        .mrl-rd-card{
            border:1px solid var(--mrl-rd-border);
            border-radius:14px;
            background:var(--mrl-rd-panel);
            backdrop-filter:blur(8px);
            -webkit-backdrop-filter:blur(8px);
            box-shadow:0 8px 22px rgba(0,0,0,.20);
            overflow:hidden;
        }

        .mrl-rd-card.admin{
            border-color:rgba(239,201,130,.42);
        }

        .mrl-rd-card-title,
        .mrl-rd-card > summary{
            list-style:none;
            margin:0;
            padding:11px 14px;
            color:var(--mrl-rd-gold);
            font-size:16px;
            font-weight:800;
            line-height:1.2;
            cursor:default;
            border-bottom:1px solid rgba(255,255,255,.07);
        }

        .mrl-rd-card > summary{
            cursor:pointer;
        }

        .mrl-rd-card > summary::-webkit-details-marker{display:none}
        .mrl-rd-card > summary::after{content:"+";float:right;font-weight:500}
        .mrl-rd-card[open] > summary::after{content:"−"}

        .mrl-rd-card-body{
            padding:9px 12px 11px;
        }

        .mrl-rd-links{
            display:flex;
            flex-direction:column;
            gap:4px;
        }

        .mrl-rd-link{
            display:flex;
            align-items:flex-start;
            gap:7px;
            padding:6px 7px;
            border-radius:8px;
            color:var(--mrl-rd-blue) !important;
            font-size:14px;
            line-height:1.25;
            text-decoration:none !important;
        }

        .mrl-rd-link:hover{
            background:rgba(255,255,255,.065);
            color:#79cfff !important;
        }

        .mrl-rd-link-arrow{
            flex:0 0 auto;
            color:var(--mrl-rd-gold);
            font-weight:900;
        }

        .mrl-rd-admin-group{
            margin:2px 0 9px;
            color:var(--mrl-rd-muted);
            font-size:11px;
            font-weight:800;
            letter-spacing:.7px;
            text-transform:uppercase;
        }

        .mrl-rd-admin-group:not(:first-child){margin-top:12px}

        .mrl-rd-admin-link{
            display:block;
            padding:4px 5px;
            border-radius:6px;
            color:var(--mrl-rd-blue) !important;
            font-size:13px;
            line-height:1.2;
            text-decoration:none !important;
        }

        .mrl-rd-admin-link:hover{background:rgba(255,255,255,.06)}

        /* Keep inherited lower-page geometry but use the same visual language. */
        .mrl-user-info-panel::before,
        .mrl-pick-panel::before,
        .mrl-previous-years::before,
        .mrl-section-panel::before{
            border-color:var(--mrl-rd-border) !important;
            background:rgba(24,24,24,.58) !important;
            backdrop-filter:blur(7px);
            -webkit-backdrop-filter:blur(7px);
        }

        @media (max-width:1000px){
            :root{--mrl-rd-width:94%}
            .mrl-rd-header{grid-template-columns:1fr auto;gap:8px}
            .mrl-rd-title{grid-column:1/-1;grid-row:1;text-align:left}
            .mrl-rd-user{grid-column:1;grid-row:2}
            .mrl-rd-clock{grid-column:2;grid-row:2}
            .mrl-rd-grid,.mrl-rd-grid.no-admin{grid-template-columns:1fr}
        }

        @media (max-width:600px){
            .mrl-rd-header{padding:8px 10px}
            .mrl-rd-title{font-size:17px}
            .mrl-rd-clock{font-size:12px}
            .mrl-rd-user-button{font-size:13px}
        }
CSS;

    $styleClose = "</style>";
    $pos = strpos($work, $styleClose);
    if ($pos === false) {
        return [false, '', ['Could not locate the inherited style block.']];
    }
    $work = substr_replace($work, $redesignCss . "\n    </style>", $pos, strlen($styleClose));

    // Replace only the old top navigation/info area.  Everything beginning with
    // current_user_team_chart remains inherited from current team.php.
    $newTop = <<<'HTML'
<body>

<div class="mrl-rd-shell mrl-rd-sticky">
    <div class="mrl-rd-header">
        <div class="mrl-rd-user" id="mrl-rd-user">
            <button type="button" class="mrl-rd-user-button" id="mrl-rd-user-button"
                    aria-expanded="false" aria-controls="mrl-rd-user-menu">
                👤 <?php echo teampage_h($first_name); ?> ▾
            </button>
            <div class="mrl-rd-user-menu" id="mrl-rd-user-menu">
                <a href="<?php echo teampage_h((string)$mrl); ?>">MRL Home</a>
                <a href="<?php echo teampage_h((string)$mrl); ?>profile.php">Profile Page</a>
                <a href="<?php echo teampage_h((string)$mrl); ?>logout.php">Logout</a>
            </div>
        </div>

        <div class="mrl-rd-title">
            <?php echo teampage_h((string)$sitename); ?>
            <small>My Team Page · redesign test</small>
        </div>

        <div class="mrl-rd-clock" id="mrl-rd-clock">
            <span id="mrl-rd-clock-time">--:--:--</span>
            <small id="mrl-rd-clock-date">America/New_York</small>
        </div>
    </div>
</div>

<div class="mrl-rd-top">
    <div class="mrl-rd-greeting">Hi <?php echo teampage_h($first_name); ?> ...</div>

    <div class="mrl-rd-grid<?php echo $isAdmin ? '' : ' no-admin'; ?>">
        <?php if ($isAdmin): ?>
            <details class="mrl-rd-card admin">
                <summary>Admin Menu</summary>
                <div class="mrl-rd-card-body">
                    <div class="mrl-rd-admin-group">League & Team</div>
                    <a class="mrl-rd-admin-link" href="/race_results/weekly_standings.php" target="_blank">Weekly Standings / scoring - Beta</a>
                    <a class="mrl-rd-admin-link" href="/admin_setup.php" target="_blank">Setup Year / Pick Window</a>
                    <a class="mrl-rd-admin-link" href="/Paid_Status_Year.php" target="_blank">Paid Status by year</a>
                    <a class="mrl-rd-admin-link" href="/team_view_as.php" target="_blank">View Team page as alternate user</a>
                    <a class="mrl-rd-admin-link" href="/email.php" target="_blank">Email addresses</a>
                    <a class="mrl-rd-admin-link" href="/change_user_auth.php" target="_blank">Special user authorization</a>
                    <a class="mrl-rd-admin-link" href="/admin_pick_adjustment.php" target="_blank">Approve LP as regular segment pick</a>
                    <a class="mrl-rd-admin-link" href="/addDrivers.php" target="_blank">Add drivers for a new year</a>
                    <a class="mrl-rd-admin-link" href="/current_segment_chart_by_entry_time.php" target="_blank">Current segment chart by entry time</a>

                    <div class="mrl-rd-admin-group">Hosting & Infrastructure</div>
                    <a class="mrl-rd-admin-link" href="<?php echo teampage_h($phpMyAdminUrl); ?>" target="_blank">phpMyAdmin (Hostinger)</a>
                    <a class="mrl-rd-admin-link" href="<?php echo teampage_h($wpAdminUrl); ?>" target="_blank">WP Admin</a>
                    <a class="mrl-rd-admin-link" href="<?php echo teampage_h($hostingerBackupsUrl); ?>" target="_blank">Hostinger Backups</a>
                    <a class="mrl-rd-admin-link" href="<?php echo teampage_h($hostingerPanelUrl); ?>" target="_blank">Hostinger hPanel</a>
                </div>
            </details>
        <?php endif; ?>

        <section class="mrl-rd-card">
            <div class="mrl-rd-card-title">
                <?php echo teampage_h((string)($teamPageContent['league_panel']['title'] ?? 'League Information')); ?>
            </div>
            <div class="mrl-rd-card-body">
                <?php teampage_redesign_render_links($teamPageContent['league_panel'], (string)$raceYear); ?>
            </div>
        </section>

        <section class="mrl-rd-card">
            <div class="mrl-rd-card-title">
                <?php echo teampage_h((string)($teamPageContent['team_panel']['title'] ?? 'Team Menu')); ?>
            </div>
            <div class="mrl-rd-card-body">
                <?php teampage_redesign_render_links($teamPageContent['team_panel'], (string)$raceYear); ?>
            </div>
        </section>
    </div>
</div>

<a name="current_user_team_chart"></a>
HTML;

    $pattern = '~<body>\s*<\?php if \(\$showDbDebugBanner\): \?>.*?<a name="current_user_team_chart"></a>~s';
    $work = preg_replace($pattern, $newTop, $work, 1, $countTop);
    if ($countTop !== 1) {
        return [false, '', ['Could not replace the expected old top/header block.']];
    }

    // Add small native JS before the inherited closing body.
    $redesignJs = <<<'JS'

<script>
(function () {
    'use strict';

    var user = document.getElementById('mrl-rd-user');
    var button = document.getElementById('mrl-rd-user-button');

    if (user && button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = user.classList.toggle('open');
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            user.classList.remove('open');
            button.setAttribute('aria-expanded', 'false');
        });
    }

    var timeNode = document.getElementById('mrl-rd-clock-time');
    var dateNode = document.getElementById('mrl-rd-clock-date');

    function updateClock() {
        if (!timeNode || !dateNode) return;

        var now = new Date();
        timeNode.textContent = new Intl.DateTimeFormat('en-US', {
            timeZone: 'America/New_York',
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit'
        }).format(now);

        dateNode.textContent = new Intl.DateTimeFormat('en-US', {
            timeZone: 'America/New_York',
            weekday: 'short',
            month: 'numeric',
            day: 'numeric',
            year: 'numeric'
        }).format(now) + ' ET';
    }

    updateClock();
    window.setInterval(updateClock, 1000);
}());
</script>
JS;

    $bodyClose = "</body>";
    $posBody = strrpos($work, $bodyClose);
    if ($posBody === false) {
        return [false, '', ['Could not locate closing body tag.']];
    }
    $work = substr_replace($work, $redesignJs . "\n\n</body>", $posBody, strlen($bodyClose));

    $notes[] = 'Source baseline identified: ' . EXPECTED_SOURCE_VERSION;
    $notes[] = 'team.php not modified.';
    $notes[] = 'DB banner disabled only in team_redesign.php.';
    $notes[] = 'Top/header block replaced with isolated dashboard-style presentation.';
    $notes[] = 'Lower pick/chart logic remains inherited from current team.php.';

    return [true, $work, $notes];
}

$sourceExists = is_file($sourcePath);
$source = $sourceExists ? (string)file_get_contents($sourcePath) : '';
$sourceVersionOk = $sourceExists && strpos($source, 'VERSION: ' . EXPECTED_SOURCE_VERSION) !== false;
[$buildOk, $redesign, $buildNotes] = $sourceVersionOk
    ? build_redesign($source)
    : [false, '', ['Source file missing or baseline version mismatch.']];

$applyRequested = isset($_POST['apply']) && $_POST['apply'] === '1';
$applyOk = false;
$applyMessages = [];

if ($applyRequested && $buildOk) {
    if (is_file($targetPath)) {
        $backupDir = $baseDir . '/_migration_backups/team_redesign_' . date('Ymd_His');
        if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            $applyMessages[] = 'Could not create backup directory.';
        } else {
            if (!copy($targetPath, $backupDir . '/team_redesign.php')) {
                $applyMessages[] = 'Could not back up existing team_redesign.php.';
            } else {
                $applyMessages[] = 'Backed up existing team_redesign.php to ' . $backupDir;
            }
        }
    }

    if (!$applyMessages || !preg_grep('/Could not/', $applyMessages)) {
        if (!write_atomic($targetPath, $redesign)) {
            $applyMessages[] = 'Could not write team_redesign.php.';
        } else {
            $applyMessages[] = 'Created team_redesign.php.';
        }

        if (!is_file($contentPath)) {
            $json = json_encode(build_default_content(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($json) || !write_atomic($contentPath, $json . PHP_EOL)) {
                $applyMessages[] = 'Could not create mrl_team_page_content.json.';
            } else {
                $applyMessages[] = 'Created mrl_team_page_content.json.';
            }
        } else {
            $applyMessages[] = 'Preserved existing mrl_team_page_content.json.';
        }
    }

    $applyOk = !preg_grep('/Could not/', $applyMessages)
        && is_file($targetPath)
        && strpos((string)file_get_contents($targetPath), 'VERSION: ' . REDESIGN_VERSION) !== false;
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL Team Redesign Installer v001</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#151515;color:#eee;font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:94%;max-width:1100px;margin:24px auto}
.card{background:#202020;border:1px solid #555;border-radius:14px;padding:18px;margin-bottom:14px}
h1{margin:0 0 6px;color:#efc982;font-size:26px}
h2{color:#efc982;font-size:19px}
p,li{line-height:1.45}
table{width:100%;border-collapse:collapse}
td{padding:8px;border-bottom:1px solid #3a3a3a;vertical-align:top}
td:nth-child(2){width:90px;font-weight:800}
.ok{color:#61e493}.bad{color:#ff7777}
button{border:1px solid #5a7fb5;background:#135fbe;color:#fff;border-radius:10px;padding:10px 18px;font-weight:800;cursor:pointer}
.note{color:#bbb}
code{color:#8fcfff}
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>MRL Team Redesign Installer v001</h1>
        <p>This creates an isolated <code>team_redesign.php</code>. It does <strong>not</strong> change <code>team.php</code>.</p>
    </div>

    <div class="card">
        <h2>Preflight</h2>
        <table>
            <?php status_row('Source team.php present', $sourceExists, $sourcePath); ?>
            <?php status_row('Source baseline is ' . EXPECTED_SOURCE_VERSION, $sourceVersionOk, $sourceVersionOk ? 'Current expected Live baseline.' : 'STOP - source baseline changed.'); ?>
            <?php status_row('Redesign build generated', $buildOk, $buildOk ? 'Transformation completed in memory.' : implode(' ', $buildNotes)); ?>
            <?php status_row('Production team.php protected', true, 'Installer never writes to team.php.'); ?>
        </table>
    </div>

    <?php if ($buildOk): ?>
    <div class="card">
        <h2>What v001 changes</h2>
        <ul>
            <li>Creates a narrow centered sticky header with user menu, league title and native live ET clock.</li>
            <li>Uses translucent dashboard-style panels based on the visual direction of race_results_dashboard.php/admin_setup.php.</li>
            <li>Creates modular League Information and Team Menu sections.</li>
            <li>Moves League/Team link data to <code>mrl_team_page_content.json</code>.</li>
            <li>Disables the Connected DBs diagnostic banner on the redesign page.</li>
            <li>Leaves current pick, LP, RP/RD, chart and scoring logic inherited from current team.php.</li>
        </ul>

        <?php if (!$applyRequested): ?>
            <form method="post">
                <input type="hidden" name="apply" value="1">
                <button type="submit">Create team_redesign.php</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($applyRequested): ?>
    <div class="card">
        <h2>Apply Result</h2>
        <p class="<?php echo $applyOk ? 'ok' : 'bad'; ?>">
            <?php echo $applyOk ? 'SUCCESS' : 'FAILED'; ?>
        </p>
        <ul>
            <?php foreach ($applyMessages as $message): ?>
                <li><?php echo ih($message); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($applyOk): ?>
            <p><a style="color:#8fcfff" href="/team_redesign.php" target="_blank">Open team_redesign.php</a></p>
            <p class="note">After you inspect it, delete this installer from the server. The JSON content file should remain.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
