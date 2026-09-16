<?php
declare(strict_types=1);

/**
 * install_mrl_team_redesign.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/27/2026 3:23:36 pm
 *
 * PURPOSE:
 * Apply the second isolated presentation pass to team_redesign.php.
 * Production team.php remains untouched.
 *
 * EXPECTED INPUT:
 * - team_redesign.php VERSION: v001
 *
 * OUTPUT:
 * - team_redesign.php VERSION: v002
 *
 * SAFETY:
 * - team.php is never written.
 * - Existing team_redesign.php is backed up before replacement.
 * - Existing mrl_team_page_content.json is preserved unchanged.
 * - Preflight blocks if team_redesign.php is not the expected v001 baseline.
 *
 * CHANGELOG:
 *
 * v002 (8/27/2026 3:23:36 pm)
 * - UI: Base team-page content width increased from 80% to 85%.
 * - UI: Sticky header, top menu panels, current-user chart, current-segment chart,
 *       Previous Years panel, and related panel borders are aligned to the same 85% width.
 * - UI: Admin area moved above League Information / Team Menu.
 * - UI: Admin area split into two side-by-side panels:
 *       League & Team / Hosting & Infrastructure.
 * - UI: Panel typography, spacing, bullets, heading treatment, and link colors
 *       move closer to the installer/dashboard presentation style.
 * - UI: Panels made more translucent so a user background image remains visible.
 * - FIX: Adds a dark HTML fallback background so no-background mode does not expose white.
 * - FIX: Closed/open pick-window status text gets an explicit readable gold treatment.
 * - PRESERVE: Existing native sticky header, local ET clock, JSON-driven League/Team links,
 *             pick/LP/RP-RD/scoring/chart logic, and production team.php isolation.
 *
 * v001 (8/27/2026 12:36:47 pm)
 * - Initial isolated team-page redesign.
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v002';
const EXPECTED_REDESSIGN_VERSION = 'v001';
const OUTPUT_REDESSIGN_VERSION = 'v002';

$baseDir = __DIR__;
$targetPath = $baseDir . '/team_redesign.php';
$productionPath = $baseDir . '/team.php';
$contentPath = $baseDir . '/mrl_team_page_content.json';

function ih(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function status_row(string $label, bool $ok, string $detail = ''): void
{
    echo '<tr>';
    echo '<td>' . ih($label) . '</td>';
    echo '<td class="' . ($ok ? 'ok' : 'bad') . '">' . ($ok ? 'PASS' : 'FAIL') . '</td>';
    echo '<td>' . ih($detail) . '</td>';
    echo '</tr>';
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

function replace_once(string $subject, string $search, string $replace, string $label, array &$notes): ?string
{
    $count = substr_count($subject, $search);
    if ($count !== 1) {
        $notes[] = $label . ': expected exactly 1 match, found ' . $count . '.';
        return null;
    }

    $notes[] = $label . ': OK.';
    return str_replace($search, $replace, $subject);
}

function build_v002(string $source): array
{
    $notes = [];

    if (strpos($source, 'VERSION: v001') === false) {
        return [false, '', ['team_redesign.php is not the expected v001 baseline.']];
    }

    $work = $source;

    // Header/version update.
    $work = preg_replace('/VERSION:\s*v001/', 'VERSION: v002', $work, 1, $countVersion);
    $work = preg_replace(
        '/LAST MODIFIED:\s*8\/27\/2026 12:36:47 pm/',
        'LAST MODIFIED: 8/27/2026 3:23:36 pm',
        $work,
        1,
        $countModified
    );

    if ($countVersion !== 1 || $countModified !== 1) {
        return [false, '', ['Could not update the v001 header safely.']];
    }

    $v002Change = " *\n"
        . " * v002 (8/27/2026 3:23:36 pm)\n"
        . " * - UI: Base display width increased to 85% and aligned across header/menu/chart panels.\n"
        . " * - UI: Admin area moved above League/Team menus and split into two side-by-side panels.\n"
        . " * - UI: Installer-style typography, spacing, bullets and colors with greater transparency.\n"
        . " * - FIX: Dark fallback replaces white background when no custom background image is active.\n"
        . " * - FIX: Pick-window closed/open status text receives an explicit readable gold style.\n"
        . " * - PRESERVE: Production team.php remains untouched; application/pick/scoring logic unchanged.\n";

    $work = preg_replace('/(\* CHANGELOG:\R)/', '$1' . $v002Change, $work, 1, $countChange);
    if ($countChange !== 1) {
        return [false, '', ['Could not add the v002 changelog safely.']];
    }

    // Replace the v001 top area with the v002 modular two-row layout.
    $v001TopPattern = '~<div class="mrl-rd-top">.*?</div>\s*<a name="current_user_team_chart"></a>~s';

    $v002Top = <<<'HTML'
<div class="mrl-rd-top">
    <div class="mrl-rd-greeting">Hi <?php echo teampage_h($first_name); ?> ...</div>

    <?php if ($isAdmin): ?>
        <div class="mrl-rd-section-heading">Admin Menu</div>
        <div class="mrl-rd-admin-grid">
            <section class="mrl-rd-card">
                <div class="mrl-rd-card-title">League &amp; Team</div>
                <div class="mrl-rd-card-body">
                    <ul class="mrl-rd-list">
                        <li><a href="/race_results/weekly_standings.php" target="_blank">Weekly Standings / scoring - Beta</a></li>
                        <li><a href="/admin_setup.php" target="_blank">Setup Year / Pick Window</a></li>
                        <li><a href="/Paid_Status_Year.php" target="_blank">Paid Status by year</a></li>
                        <li><a href="/team_view_as.php" target="_blank">View Team page as alternate user</a></li>
                        <li><a href="/email.php" target="_blank">Email addresses</a></li>
                        <li><a href="/change_user_auth.php" target="_blank">Special user authorization</a></li>
                        <li><a href="/admin_pick_adjustment.php" target="_blank">Approve LP as regular segment pick</a></li>
                        <li><a href="/addDrivers.php" target="_blank">Add drivers for a new year</a></li>
                        <li><a href="/current_segment_chart_by_entry_time.php" target="_blank">Current segment chart by entry time</a></li>
                    </ul>
                </div>
            </section>

            <section class="mrl-rd-card">
                <div class="mrl-rd-card-title">Hosting &amp; Infrastructure</div>
                <div class="mrl-rd-card-body">
                    <ul class="mrl-rd-list">
                        <li><a href="<?php echo teampage_h($phpMyAdminUrl); ?>" target="_blank">phpMyAdmin (Hostinger)</a></li>
                        <li><a href="<?php echo teampage_h($wpAdminUrl); ?>" target="_blank">WP Admin</a></li>
                        <li><a href="<?php echo teampage_h($hostingerBackupsUrl); ?>" target="_blank">Hostinger Backups</a></li>
                        <li><a href="<?php echo teampage_h($hostingerPanelUrl); ?>" target="_blank">Hostinger hPanel</a></li>
                    </ul>
                </div>
            </section>
        </div>
    <?php endif; ?>

    <div class="mrl-rd-section-heading">League &amp; Team</div>
    <div class="mrl-rd-main-grid">
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

    $work2 = preg_replace($v001TopPattern, $v002Top, $work, 1, $countTop);
    if ($countTop !== 1 || !is_string($work2)) {
        return [false, '', ['Could not replace the v001 top panel block safely.']];
    }
    $work = $work2;

    // Update the JSON link renderer from arrow rows to installer-style bullets.
    $oldRenderer = <<<'PHP'
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

    $newRenderer = <<<'PHP'
function teampage_redesign_render_links(array $panel, string $raceYear): void
{
    $items = isset($panel['items']) && is_array($panel['items']) ? $panel['items'] : [];
    echo '<ul class="mrl-rd-list">';

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

        echo '<li><a href="' . teampage_h($url) . '"' . $target . '>'
            . teampage_h($label)
            . '</a></li>';
    }

    echo '</ul>';
}
PHP;

    $updated = replace_once($work, $oldRenderer, $newRenderer, 'JSON link renderer', $notes);
    if ($updated === null) {
        return [false, '', $notes];
    }
    $work = $updated;

    // Expand the inherited 80%-wide lower wrappers to 85%.
    $work = str_replace(
        '<div style="width:80%; margin:0 auto; text-align:left;">',
        '<div style="width:85%; margin:0 auto; text-align:left;">',
        $work,
        $countMainWidth
    );

    $work = str_replace(
        '<div style="width:80%; margin:0 auto; border:none; text-align:left;">',
        '<div style="width:85%; margin:0 auto; border:none; text-align:left;">',
        $work,
        $countFooterWidth
    );

    $notes[] = 'Inherited 80% content wrappers changed: ' . $countMainWidth;
    $notes[] = 'Inherited 80% footer wrappers changed: ' . $countFooterWidth;

    // v002 CSS is appended last so it intentionally overrides the v001 presentation only.
    $v002Css = <<<'CSS'

        /* =====================================================================
         * team_redesign.php v002 - presentation refinements
         * =================================================================== */
        html{
            background:#151515;
        }

        :root{
            --mrl-rd-width:85%;
            --mrl-rd-max:1600px;
            --mrl-rd-panel:rgba(28,28,28,.56);
            --mrl-rd-panel-header:rgba(34,34,34,.48);
            --mrl-rd-border:rgba(195,195,195,.34);
            --mrl-rd-gold:#f1c97f;
            --mrl-rd-text:#f2f2f2;
            --mrl-rd-muted:#d4d0c7;
            --mrl-rd-blue:#43b7f0;
        }

        body{
            background-color:transparent !important;
        }

        .mrl-rd-shell,
        .mrl-rd-top{
            width:var(--mrl-rd-width) !important;
            max-width:var(--mrl-rd-max) !important;
        }

        .mrl-rd-sticky{
            background:linear-gradient(180deg,rgba(18,58,40,.78),rgba(20,35,29,.74)) !important;
            border-color:rgba(67,142,94,.72) !important;
            backdrop-filter:blur(3px);
            -webkit-backdrop-filter:blur(3px);
        }

        .mrl-rd-greeting{
            margin:6px 2px 10px;
            color:var(--mrl-rd-gold);
            font-size:18px;
            line-height:1.3;
        }

        .mrl-rd-section-heading{
            margin:12px 2px 8px;
            color:var(--mrl-rd-gold);
            font:800 15px/1.2 Tahoma,Verdana,Segoe UI,sans-serif;
            letter-spacing:.4px;
        }

        .mrl-rd-admin-grid,
        .mrl-rd-main-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:14px;
            align-items:start;
            margin-bottom:14px;
        }

        .mrl-rd-card{
            border:1px solid var(--mrl-rd-border) !important;
            border-radius:14px !important;
            background:var(--mrl-rd-panel) !important;
            backdrop-filter:blur(2px);
            -webkit-backdrop-filter:blur(2px);
            box-shadow:0 8px 22px rgba(0,0,0,.18) !important;
        }

        .mrl-rd-card-title{
            padding:13px 18px 11px !important;
            color:var(--mrl-rd-gold) !important;
            background:var(--mrl-rd-panel-header);
            border-bottom:1px solid rgba(255,255,255,.10) !important;
            font:800 18px/1.25 Tahoma,Verdana,Segoe UI,sans-serif !important;
        }

        .mrl-rd-card-body{
            padding:14px 20px 16px !important;
            color:var(--mrl-rd-text);
            font:16px/1.4 Tahoma,Verdana,Segoe UI,sans-serif;
        }

        .mrl-rd-list{
            margin:0;
            padding-left:24px;
            color:var(--mrl-rd-text);
        }

        .mrl-rd-list li{
            margin:0 0 8px;
            padding-left:2px;
            line-height:1.35;
        }

        .mrl-rd-list li:last-child{
            margin-bottom:0;
        }

        .mrl-rd-list li::marker{
            color:#eeeeee;
        }

        .mrl-rd-list a{
            color:var(--mrl-rd-blue) !important;
            text-decoration:none !important;
        }

        .mrl-rd-list a:hover{
            color:#85d5ff !important;
            text-decoration:underline !important;
        }

        /* Retire v001 arrow-row presentation if any stale markup remains. */
        .mrl-rd-links{display:block}
        .mrl-rd-link-arrow{display:none}

        /*
         * 85% geometry.
         * current_user_team_chart.php emits its own inline 80% tables;
         * !important here deliberately makes the redesign test 85%.
         */
        .mrl-user-info-panel table{
            width:85% !important;
            margin-left:auto !important;
            margin-right:auto !important;
        }

        .mrl-user-info-panel::before,
        .mrl-previous-years::before{
            left:calc(7.5% - 22px) !important;
            right:calc(7.5% - 22px) !important;
        }

        .mrl-previous-years summary{
            width:85% !important;
        }

        /*
         * The current-segment chart lives inside an inherited centered wrapper.
         * That wrapper is converted to 85% by the v002 installer, while the
         * included current_segment_chart.php remains width:100% within it.
         */
        .mrl-pick-panel{
            color:#f1d49a !important;
            font:18px/1.35 "Century Gothic",Tahoma,Verdana,sans-serif !important;
            text-shadow:none !important;
            filter:none !important;
        }

        .mrl-pick-panel::before{
            background:rgba(28,28,28,.50) !important;
            border-color:var(--mrl-rd-border) !important;
            backdrop-filter:blur(2px) !important;
            -webkit-backdrop-filter:blur(2px) !important;
        }

        .mrl-user-info-panel::before,
        .mrl-previous-years::before,
        .mrl-section-panel::before{
            background:rgba(28,28,28,.50) !important;
            border-color:var(--mrl-rd-border) !important;
            backdrop-filter:blur(2px) !important;
            -webkit-backdrop-filter:blur(2px) !important;
        }

        @media (max-width:1000px){
            :root{--mrl-rd-width:94%}

            .mrl-rd-admin-grid,
            .mrl-rd-main-grid{
                grid-template-columns:1fr;
            }

            .mrl-user-info-panel table,
            .mrl-previous-years summary{
                width:94% !important;
            }

            .mrl-user-info-panel::before,
            .mrl-previous-years::before{
                left:calc(3% - 10px) !important;
                right:calc(3% - 10px) !important;
            }
        }
CSS;

    $styleClosePos = strpos($work, '</style>');
    if ($styleClosePos === false) {
        return [false, '', ['Could not locate the style block.']];
    }
    $work = substr_replace($work, $v002Css . "\n    </style>", $styleClosePos, strlen('</style>'));

    // Guardrails.
    $guardChecks = [
        'v002 version header' => strpos($work, 'VERSION: v002') !== false,
        '85% shell variable' => strpos($work, '--mrl-rd-width:85%') !== false,
        'Admin two-panel grid' => strpos($work, 'mrl-rd-admin-grid') !== false,
        'League & Team admin panel' => strpos($work, '<div class="mrl-rd-card-title">League &amp; Team</div>') !== false,
        'Hosting admin panel' => strpos($work, '<div class="mrl-rd-card-title">Hosting &amp; Infrastructure</div>') !== false,
        'Dark HTML fallback' => strpos($work, 'html{' . "\n" . '            background:#151515;') !== false,
        'Readable pick status styling' => strpos($work, '.mrl-pick-panel{' . "\n" . '            color:#f1d49a !important;') !== false,
    ];

    foreach ($guardChecks as $label => $ok) {
        if (!$ok) {
            return [false, '', array_merge($notes, ['Guard check failed: ' . $label])];
        }
    }

    $notes[] = 'All v002 guard checks passed.';
    return [true, $work, $notes];
}

$targetExists = is_file($targetPath);
$source = $targetExists ? (string)file_get_contents($targetPath) : '';
$baselineOk = $targetExists && strpos($source, 'VERSION: ' . EXPECTED_REDESSIGN_VERSION) !== false;
$productionExists = is_file($productionPath);
$productionBeforeHash = $productionExists ? hash_file('sha256', $productionPath) : '';

[$buildOk, $built, $buildNotes] = $baselineOk
    ? build_v002($source)
    : [false, '', ['team_redesign.php missing or not VERSION: v001.']];

$applyRequested = isset($_POST['apply']) && $_POST['apply'] === '1';
$applyOk = false;
$applyMessages = [];
$backupPath = '';

if ($applyRequested && $buildOk) {
    $backupDir = $baseDir . '/_migration_backups/team_redesign_' . date('Ymd_His');

    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
        $applyMessages[] = 'Could not create backup directory.';
    } else {
        $backupPath = $backupDir . '/team_redesign.php';
        if (!copy($targetPath, $backupPath)) {
            $applyMessages[] = 'Could not back up team_redesign.php.';
        } else {
            $applyMessages[] = 'Backed up v001 to ' . $backupPath;
        }
    }

    if (!preg_grep('/Could not/', $applyMessages)) {
        if (!write_atomic($targetPath, $built)) {
            $applyMessages[] = 'Could not write team_redesign.php.';
        } else {
            $applyMessages[] = 'Installed team_redesign.php v002.';
        }
    }

    $productionAfterHash = $productionExists ? hash_file('sha256', $productionPath) : '';
    $productionUntouched = $productionExists && $productionBeforeHash === $productionAfterHash;
    $targetNow = is_file($targetPath) ? (string)file_get_contents($targetPath) : '';

    $applyOk =
        !preg_grep('/Could not/', $applyMessages)
        && strpos($targetNow, 'VERSION: v002') !== false
        && $productionUntouched;

    $applyMessages[] = $productionUntouched
        ? 'Verified production team.php hash is unchanged.'
        : 'ERROR: production team.php verification failed.';

    $applyMessages[] = is_file($contentPath)
        ? 'Preserved mrl_team_page_content.json.'
        : 'Note: mrl_team_page_content.json is not present.';
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL Team Redesign Installer v002</title>
<style>
*{box-sizing:border-box}
html{background:#111}
body{margin:0;background:transparent;color:#f2f2f2;font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:16px}
.wrap{width:94%;max-width:1200px;margin:20px auto}
.card{background:rgba(30,30,30,.78);border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}
h1{margin:0 0 8px;color:#efc982;font-size:28px}
h2{margin:0 0 14px;color:#efc982;font-size:20px}
p,li{line-height:1.45}
table{width:100%;border-collapse:collapse}
td{padding:9px 10px;border-bottom:1px solid #444;vertical-align:top}
td:nth-child(2){width:90px;font-weight:800}
.ok{color:#61e493}.bad{color:#ff7777}
button{border:1px solid #5a7fb5;background:#1466c9;color:#fff;border-radius:10px;padding:11px 20px;font-weight:800;cursor:pointer}
code{color:#76cfff}
.note{color:#c8c8c8}
a{color:#76cfff}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
    <h1>MRL Team Redesign Installer v002</h1>
    <p>Presentation-only second pass for <code>team_redesign.php</code>. Production <code>team.php</code> is never modified.</p>
</div>

<div class="card">
    <h2>Preflight</h2>
    <table>
        <?php status_row('team_redesign.php present', $targetExists, $targetPath); ?>
        <?php status_row('Expected redesign baseline v001', $baselineOk, $baselineOk ? 'Ready for v002.' : 'STOP - baseline mismatch.'); ?>
        <?php status_row('Production team.php present', $productionExists, $productionPath); ?>
        <?php status_row('v002 build generated in memory', $buildOk, $buildOk ? 'Transformation completed.' : implode(' ', $buildNotes)); ?>
        <?php status_row('JSON content file preserved', is_file($contentPath), is_file($contentPath) ? $contentPath : 'Not found; redesign fallback content still exists in PHP.'); ?>
    </table>
</div>

<?php if ($buildOk): ?>
<div class="card">
    <h2>What v002 changes</h2>
    <ul>
        <li>Changes the common desktop content target from 80% to <strong>85%</strong>.</li>
        <li>Aligns the sticky header and top menu area to that same target.</li>
        <li>Moves Admin above the League / Team menus.</li>
        <li>Splits Admin into two side-by-side panels: <strong>League &amp; Team</strong> and <strong>Hosting &amp; Infrastructure</strong>.</li>
        <li>Uses installer-like typography, spacing, heading colors, and normal bullet lists.</li>
        <li>Makes panels more translucent so your background image shows through.</li>
        <li>Adds a dark fallback behind the page so no custom background does not turn white.</li>
        <li>Gives the closed/open pick-window message an explicit readable gold style.</li>
    </ul>

    <?php if (!$applyRequested): ?>
    <form method="post">
        <input type="hidden" name="apply" value="1">
        <button type="submit">Install team_redesign.php v002</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($applyRequested): ?>
<div class="card">
    <h2>Apply Result</h2>
    <p class="<?php echo $applyOk ? 'ok' : 'bad'; ?>"><strong><?php echo $applyOk ? 'SUCCESS' : 'FAILED'; ?></strong></p>
    <ul>
        <?php foreach ($applyMessages as $message): ?>
            <li><?php echo ih($message); ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($applyOk): ?>
        <p><a href="/team_redesign.php" target="_blank">Open team_redesign.php v002</a></p>
        <p class="note">Production team.php was hash-verified unchanged.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
