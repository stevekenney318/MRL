<?php
declare(strict_types=1);

/**
 * install_mrl_team_redesign.php
 *
 * VERSION: v003
 * LAST MODIFIED: 8/27/2026 3:51:57 pm
 *
 * PURPOSE:
 * Apply the third isolated presentation pass to team_redesign.php.
 * Production team.php remains untouched.
 *
 * EXPECTED INPUT:
 * - team_redesign.php VERSION: v002
 *
 * OUTPUT:
 * - team_redesign.php VERSION: v003
 *
 * DESIGN GOAL:
 * Establish one width authority for the entire page while leaving the
 * presentation of the actual chart tables unchanged.
 *
 * SAFETY:
 * - team.php is never written.
 * - Existing team_redesign.php is backed up before replacement.
 * - Existing mrl_team_page_content.json is preserved unchanged.
 * - Preflight blocks if team_redesign.php is not the expected v002 baseline.
 *
 * CHANGELOG:
 *
 * v003 (8/27/2026 3:51:57 pm)
 * - ARCHITECTURE: Adds one common 85% page-width shell used by header, menus,
 *                 current-user chart, current-segment chart, and Previous Years.
 * - UI: Removes decorative outer borders around chart sections so chart widths
 *       no longer have to be visually matched with pseudo-element math.
 * - UI: Current-user and Previous Years chart tables fill the common shell.
 * - UI: Current-segment chart continues to fill its common shell at 100%.
 * - UI: Admin Menu becomes one collapsible +/- panel.
 * - UI: When Admin Menu is open, League & Team and Hosting & Infrastructure
 *       remain two side-by-side modules on desktop and stack on mobile.
 * - UI: Removes the redundant "League & Team" section label above the two
 *       normal menu cards.
 * - MOBILE: Chart shells allow horizontal scrolling if a chart needs more room,
 *           without changing table cell colors/content/layout logic.
 * - PRESERVE: Chart table colors, fonts, borders, data, LP/RP-RD markers,
 *             scoring logic, JSON content, and production team.php.
 *
 * v002 (8/27/2026 3:23:36 pm)
 * - 85% width target, split Admin modules, installer-style panels,
 *   transparent presentation, dark fallback, readable pick status.
 *
 * v001 (8/27/2026 12:36:47 pm)
 * - Initial isolated team-page redesign.
 */

date_default_timezone_set('America/New_York');

const EXPECTED_VERSION = 'v002';
const OUTPUT_VERSION = 'v003';

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

function build_v003(string $source): array
{
    $notes = [];

    if (strpos($source, 'VERSION: v002') === false) {
        return [false, '', ['team_redesign.php is not the expected v002 baseline.']];
    }

    $work = $source;

    $work = preg_replace('/VERSION:\s*v002/', 'VERSION: v003', $work, 1, $countVersion);
    $work = preg_replace(
        '/LAST MODIFIED:\s*8\/27\/2026 3:23:36 pm/',
        'LAST MODIFIED: 8/27/2026 3:51:57 pm',
        $work,
        1,
        $countModified
    );

    if ($countVersion !== 1 || $countModified !== 1) {
        return [false, '', ['Could not safely update the v002 header.']];
    }

    $v003Change = " *\n"
        . " * v003 (8/27/2026 3:51:57 pm)\n"
        . " * - ARCHITECTURE: One common 85% width shell now controls header, menus and chart sections.\n"
        . " * - UI: Chart-section decorative borders removed; table presentation remains unchanged.\n"
        . " * - UI: Admin Menu is one collapsible +/- section containing two desktop columns.\n"
        . " * - MOBILE: Admin modules stack; chart shells permit horizontal overflow when needed.\n"
        . " * - PRESERVE: Production team.php and all chart/pick/scoring data behavior remain unchanged.\n";

    $work = preg_replace('/(\* CHANGELOG:\R)/', '$1' . $v003Change, $work, 1, $countChange);
    if ($countChange !== 1) {
        return [false, '', ['Could not safely add the v003 changelog.']];
    }

    // Admin: one collapsible section controlling both internal modules.
    $adminPattern = '~<\?php if \(\$isAdmin\): \?>\s*'
        . '<div class="mrl-rd-section-heading">Admin Menu</div>\s*'
        . '<div class="mrl-rd-admin-grid">(.*?)</div>\s*'
        . '<\?php endif; \?>~s';

    $adminReplacement = <<<'HTML'
<?php if ($isAdmin): ?>
        <details class="mrl-rd-admin-wrap">
            <summary>Admin Menu</summary>
            <div class="mrl-rd-admin-grid">$1</div>
        </details>
    <?php endif; ?>
HTML;

    $work2 = preg_replace($adminPattern, $adminReplacement, $work, 1, $countAdmin);
    if ($countAdmin !== 1 || !is_string($work2)) {
        return [false, '', ['Could not safely convert the Admin Menu to one collapsible section.']];
    }
    $work = $work2;

    // Remove the redundant label sitting above League Information / Team Menu.
    $work = str_replace(
        '    <div class="mrl-rd-section-heading">League &amp; Team</div>' . PHP_EOL,
        '',
        $work,
        $countRedundant
    );

    // Current-user chart gets the same common page shell as everything else.
    $currentUserOld = <<<'HTML'
<a name="current_user_team_chart"></a>
<div class="mrl-user-info-panel">
    <?php include 'current_user_team_chart.php'; ?>
</div>
HTML;

    $currentUserNew = <<<'HTML'
<a name="current_user_team_chart"></a>
<section class="mrl-rd-chart-shell mrl-user-info-panel">
    <?php include 'current_user_team_chart.php'; ?>
</section>
HTML;

    if (substr_count($work, $currentUserOld) !== 1) {
        return [false, '', ['Could not safely locate the current-user chart wrapper.']];
    }
    $work = str_replace($currentUserOld, $currentUserNew, $work);

    // Current-segment/pick section: replace the old inline 85% wrapper with the common shell.
    $pickOpenOld = '<div style="width:85%; margin:0 auto; text-align:left;">' . PHP_EOL
        . '    <div style="color:#dfcca8; font-size:16.0pt; line-height:120%; font-family:\'Century Gothic\',sans-serif;">' . PHP_EOL
        . '        <br>' . PHP_EOL
        . '        <div class="mrl-pick-panel">';

    $pickOpenNew = '<section class="mrl-rd-chart-shell mrl-rd-pick-section">' . PHP_EOL
        . '    <div style="color:#dfcca8; font-size:16.0pt; line-height:120%; font-family:\'Century Gothic\',sans-serif;">' . PHP_EOL
        . '        <div class="mrl-pick-panel">';

    if (substr_count($work, $pickOpenOld) !== 1) {
        return [false, '', ['Could not safely locate the current-segment/pick wrapper.']];
    }
    $work = str_replace($pickOpenOld, $pickOpenNew, $work);

    $pickCloseOld = <<<'HTML'
        </div>
    </div>
</div>

<br>

<details class="mrl-previous-years">
HTML;

    $pickCloseNew = <<<'HTML'
        </div>
    </div>
</section>

<details class="mrl-previous-years mrl-rd-chart-shell">
HTML;

    if (substr_count($work, $pickCloseOld) !== 1) {
        return [false, '', ['Could not safely locate the pick-section closing wrapper / Previous Years start.']];
    }
    $work = str_replace($pickCloseOld, $pickCloseNew, $work);

    // Add the v003 CSS last so there is one authoritative geometry layer.
    $v003Css = <<<'CSS'

        /* =====================================================================
         * team_redesign.php v003 - single width authority / chart shell
         * =================================================================== */
        :root{
            --mrl-page-width:85%;
            --mrl-page-max:1600px;
        }

        .mrl-rd-shell,
        .mrl-rd-top,
        .mrl-rd-chart-shell{
            width:var(--mrl-page-width) !important;
            max-width:var(--mrl-page-max) !important;
            box-sizing:border-box;
            margin-left:auto !important;
            margin-right:auto !important;
        }

        /*
         * Charts are the core of the page.
         * Do not restyle their cells; only control their outer geometry.
         */
        .mrl-rd-chart-shell{
            position:relative;
            margin-top:18px !important;
            margin-bottom:28px !important;
            padding:0 !important;
            border:0 !important;
            background:transparent !important;
        }

        .mrl-user-info-panel::before,
        .mrl-pick-panel::before,
        .mrl-previous-years::before{
            display:none !important;
            content:none !important;
        }

        .mrl-user-info-panel{
            width:var(--mrl-page-width) !important;
            margin-top:18px !important;
            margin-bottom:28px !important;
        }

        .mrl-user-info-panel table,
        .mrl-previous-years-content table{
            width:100% !important;
            max-width:none !important;
            margin-left:0 !important;
            margin-right:0 !important;
        }

        .mrl-rd-pick-section .mrl-pick-panel{
            width:100% !important;
            margin:0 !important;
            padding:0 !important;
        }

        .mrl-rd-pick-section .mrl-pick-panel > table{
            width:100% !important;
        }

        .mrl-previous-years{
            width:var(--mrl-page-width) !important;
            margin-top:24px !important;
            margin-bottom:28px !important;
        }

        .mrl-previous-years summary{
            width:100% !important;
            margin:0 !important;
            padding:12px 0 14px !important;
        }

        .mrl-previous-years-content{
            width:100% !important;
            padding:6px 0 0 !important;
        }

        /* One +/- Admin bar controls both Admin modules. */
        .mrl-rd-admin-wrap{
            margin:12px 0 18px;
            padding:0;
            border:1px solid var(--mrl-rd-border);
            border-radius:14px;
            background:rgba(28,28,28,.48);
            overflow:hidden;
            backdrop-filter:blur(2px);
            -webkit-backdrop-filter:blur(2px);
        }

        .mrl-rd-admin-wrap > summary{
            list-style:none;
            cursor:pointer;
            padding:12px 18px;
            color:var(--mrl-rd-gold);
            font:800 18px/1.25 Tahoma,Verdana,Segoe UI,sans-serif;
            border-bottom:0;
            outline:none;
        }

        .mrl-rd-admin-wrap > summary::-webkit-details-marker{
            display:none;
        }

        .mrl-rd-admin-wrap > summary::before{
            content:"+ ";
            font-weight:500;
        }

        .mrl-rd-admin-wrap[open] > summary::before{
            content:"− ";
        }

        .mrl-rd-admin-wrap[open] > summary{
            border-bottom:1px solid rgba(255,255,255,.09);
        }

        .mrl-rd-admin-wrap .mrl-rd-admin-grid{
            margin:0;
            padding:14px;
            gap:14px;
        }

        /*
         * The two inner Admin cards stay separate modules, but their common
         * outer <details> makes the entire Admin area open/close as one.
         */
        .mrl-rd-admin-wrap .mrl-rd-card{
            background:rgba(28,28,28,.40) !important;
        }

        .mrl-rd-main-grid{
            margin-top:12px;
        }

        /*
         * Mobile: preserve chart presentation rather than forcing every table
         * to become a tiny stacked layout. If a chart needs room, scroll it.
         */
        .mrl-rd-chart-shell{
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
        }

        @media (max-width:1000px){
            :root{
                --mrl-page-width:94%;
            }

            .mrl-rd-admin-grid,
            .mrl-rd-main-grid{
                grid-template-columns:1fr !important;
            }

            .mrl-rd-admin-wrap .mrl-rd-admin-grid{
                padding:10px;
            }
        }
CSS;

    $stylePos = strpos($work, '</style>');
    if ($stylePos === false) {
        return [false, '', ['Could not locate the main style block.']];
    }
    $work = substr_replace($work, $v003Css . "\n    </style>", $stylePos, strlen('</style>'));

    $checks = [
        'v003 header' => strpos($work, 'VERSION: v003') !== false,
        'single page width variable' => strpos($work, '--mrl-page-width:85%') !== false,
        'collapsible Admin wrapper' => strpos($work, '<details class="mrl-rd-admin-wrap">') !== false,
        'current user common shell' => strpos($work, '<section class="mrl-rd-chart-shell mrl-user-info-panel">') !== false,
        'current segment common shell' => strpos($work, '<section class="mrl-rd-chart-shell mrl-rd-pick-section">') !== false,
        'Previous Years common shell' => strpos($work, '<details class="mrl-previous-years mrl-rd-chart-shell">') !== false,
        'chart decorative borders disabled' => strpos($work, '.mrl-user-info-panel::before,') !== false
            && strpos($work, 'display:none !important;') !== false,
        'production reference retained' => strpos($work, "include 'current_user_team_chart.php';") !== false
            && strpos($work, "include 'current_segment_chart.php';") !== false,
    ];

    foreach ($checks as $label => $ok) {
        if (!$ok) {
            return [false, '', ['Guard check failed: ' . $label]];
        }
    }

    return [true, $work, [
        'One width authority established.',
        'Admin Menu converted to one collapsible section.',
        'Chart outer borders removed.',
        'Chart cell/table presentation code not rewritten.',
    ]];
}

$targetExists = is_file($targetPath);
$source = $targetExists ? (string)file_get_contents($targetPath) : '';
$baselineOk = $targetExists && strpos($source, 'VERSION: ' . EXPECTED_VERSION) !== false;
$productionExists = is_file($productionPath);
$productionBeforeHash = $productionExists ? hash_file('sha256', $productionPath) : '';

[$buildOk, $built, $buildNotes] = $baselineOk
    ? build_v003($source)
    : [false, '', ['team_redesign.php missing or not VERSION: v002.']];

$applyRequested = isset($_POST['apply']) && $_POST['apply'] === '1';
$applyOk = false;
$applyMessages = [];

if ($applyRequested && $buildOk) {
    $backupDir = $baseDir . '/_migration_backups/team_redesign_' . date('Ymd_His');

    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
        $applyMessages[] = 'Could not create backup directory.';
    } elseif (!copy($targetPath, $backupDir . '/team_redesign.php')) {
        $applyMessages[] = 'Could not back up team_redesign.php.';
    } else {
        $applyMessages[] = 'Backed up team_redesign.php v002 to ' . $backupDir;
    }

    if (!preg_grep('/Could not/', $applyMessages)) {
        if (!write_atomic($targetPath, $built)) {
            $applyMessages[] = 'Could not write team_redesign.php v003.';
        } else {
            $applyMessages[] = 'Installed team_redesign.php v003.';
        }
    }

    $productionAfterHash = $productionExists ? hash_file('sha256', $productionPath) : '';
    $productionUntouched = $productionExists && $productionBeforeHash === $productionAfterHash;
    $targetNow = is_file($targetPath) ? (string)file_get_contents($targetPath) : '';

    $applyOk =
        !preg_grep('/Could not/', $applyMessages)
        && strpos($targetNow, 'VERSION: v003') !== false
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
<title>MRL Team Redesign Installer v003</title>
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
    <h1>MRL Team Redesign Installer v003</h1>
    <p>This pass changes the <strong>layout architecture</strong>, not the chart presentation. Production <code>team.php</code> remains protected.</p>
</div>

<div class="card">
    <h2>Preflight</h2>
    <table>
        <?php status_row('team_redesign.php present', $targetExists, $targetPath); ?>
        <?php status_row('Expected redesign baseline v002', $baselineOk, $baselineOk ? 'Ready for v003.' : 'STOP - baseline mismatch.'); ?>
        <?php status_row('Production team.php present', $productionExists, $productionPath); ?>
        <?php status_row('v003 build generated in memory', $buildOk, $buildOk ? 'Transformation completed.' : implode(' ', $buildNotes)); ?>
        <?php status_row('JSON content file preserved', is_file($contentPath), is_file($contentPath) ? $contentPath : 'Not found.'); ?>
    </table>
</div>

<?php if ($buildOk): ?>
<div class="card">
    <h2>What v003 changes</h2>
    <ul>
        <li>Introduces <strong>one 85% width authority</strong> for header, menus, and every chart section.</li>
        <li>Removes the decorative outer borders around chart sections.</li>
        <li>Makes current-user, current-segment, and Previous Years charts fill the same common shell.</li>
        <li>Turns Admin Menu into <strong>one +/- collapsible panel</strong>.</li>
        <li>Inside Admin, the two modules remain side-by-side on desktop and stack on mobile.</li>
        <li>Removes the extra League &amp; Team label above the normal menu cards.</li>
        <li>Preserves chart colors, fonts, borders, content, and LP/RP-RD presentation.</li>
        <li>Allows chart shells to scroll horizontally on smaller screens instead of redesigning the charts.</li>
    </ul>

    <?php if (!$applyRequested): ?>
    <form method="post">
        <input type="hidden" name="apply" value="1">
        <button type="submit">Install team_redesign.php v003</button>
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
        <p><a href="/team_redesign.php" target="_blank">Open team_redesign.php v003</a></p>
        <p class="note">Production team.php was hash-verified unchanged.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
