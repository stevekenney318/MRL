<?php
declare(strict_types=1);

/**
 * install_mrl_team_redesign.php
 *
 * VERSION: v004
 * LAST MODIFIED: 8/27/2026 4:46:58 pm
 *
 * PURPOSE:
 * Set the approved MRL cars background as the built-in default theme for
 * team_redesign.php while preserving the v003 layout and chart presentation.
 *
 * EXPECTED INPUT:
 * - team_redesign.php VERSION: v003
 *
 * OUTPUT:
 * - team_redesign.php VERSION: v004
 *
 * SAFETY:
 * - Production team.php is never written.
 * - Existing team_redesign.php is backed up before replacement.
 * - Existing mrl_team_page_content.json is preserved unchanged.
 *
 * CHANGELOG:
 *
 * v004 (8/27/2026 4:46:58 pm)
 * - THEME: Adds the approved cars.jpg background directly to the redesign page.
 * - THEME: Adds a rgba(10,20,15,0.70) overlay so the background stays subdued.
 * - THEME: Keeps body transparent so the fixed HTML background remains visible.
 * - ARCHITECTURE: Adds named CSS theme variables to make future Dark / Light /
 *                 Cars theme switching easier without changing chart markup.
 * - PRESERVE: v003 common-width layout, collapsible Admin Menu, chart geometry,
 *             chart colors/content, JSON links, pick/scoring logic, and team.php.
 */

date_default_timezone_set('America/New_York');

const EXPECTED_VERSION = 'v003';

$baseDir = __DIR__;
$targetPath = $baseDir . '/team_redesign.php';
$productionPath = $baseDir . '/team.php';
$contentPath = $baseDir . '/mrl_team_page_content.json';

function ih(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

function status_row(string $label, bool $ok, string $detail = ''): void {
    echo '<tr><td>' . ih($label) . '</td><td class="' . ($ok ? 'ok' : 'bad') . '">'
        . ($ok ? 'PASS' : 'FAIL') . '</td><td>' . ih($detail) . '</td></tr>';
}

function write_atomic(string $path, string $content): bool {
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $content, LOCK_EX) === false) return false;
    if (!@rename($tmp, $path)) { @unlink($tmp); return false; }
    return true;
}

function build_v004(string $source): array {
    if (strpos($source, 'VERSION: v003') === false) {
        return [false, '', ['team_redesign.php is not the expected v003 baseline.']];
    }

    $work = $source;
    $work = preg_replace('/VERSION:\s*v003/', 'VERSION: v004', $work, 1, $cv);
    $work = preg_replace('/LAST MODIFIED:\s*8\/27\/2026 3:51:57 pm/', 'LAST MODIFIED: 8/27/2026 4:46:58 pm', $work, 1, $cm);
    if ($cv !== 1 || $cm !== 1) return [false, '', ['Could not safely update the v003 header.']];

    $change = " *\n"
        . " * v004 (8/27/2026 4:46:58 pm)\n"
        . " * - THEME: Built-in cars.jpg background with rgba(10,20,15,0.70) overlay.\n"
        . " * - THEME: Body remains transparent so the fixed background shows through.\n"
        . " * - ARCHITECTURE: Named theme variables added for future per-user Cars/Dark/Light themes.\n"
        . " * - PRESERVE: v003 layout, chart presentation, data and production team.php isolation.\n";

    $work = preg_replace('/(\* CHANGELOG:\R)/', '$1' . $change, $work, 1, $cc);
    if ($cc !== 1) return [false, '', ['Could not safely add the v004 changelog.']];

    $themeCss = <<<'CSS'

        /* =====================================================================
         * team_redesign.php v004 - built-in default theme
         * =================================================================== */
        :root{
            --mrl-theme-overlay:rgba(10,20,15,0.70);
            --mrl-theme-image:url("https://manliusracingleague.com/images/cars.jpg");
            --mrl-theme-fallback:#151515;
        }

        html{
            min-height:100%;
            background:
                linear-gradient(var(--mrl-theme-overlay), var(--mrl-theme-overlay)),
                var(--mrl-theme-image)
                center / cover no-repeat fixed !important;
            background-color:var(--mrl-theme-fallback) !important;
        }

        body{
            min-height:100%;
            background:transparent !important;
        }
CSS;

    $p = strpos($work, '</style>');
    if ($p === false) return [false, '', ['Could not locate the main style block.']];
    $work = substr_replace($work, $themeCss . "\n    </style>", $p, strlen('</style>'));

    $checks = [
        strpos($work, 'VERSION: v004') !== false,
        strpos($work, '--mrl-theme-overlay:rgba(10,20,15,0.70)') !== false,
        strpos($work, 'manliusracingleague.com/images/cars.jpg') !== false,
        strpos($work, '--mrl-page-width:85%') !== false,
        strpos($work, 'mrl-rd-admin-wrap') !== false,
    ];
    foreach ($checks as $ok) if (!$ok) return [false, '', ['A v004 guard check failed.']];

    return [true, $work, ['Default Cars theme prepared.', 'Charts and production team.php are untouched.']];
}

$targetExists = is_file($targetPath);
$source = $targetExists ? (string)file_get_contents($targetPath) : '';
$baselineOk = $targetExists && strpos($source, 'VERSION: ' . EXPECTED_VERSION) !== false;
$productionExists = is_file($productionPath);
$productionBeforeHash = $productionExists ? hash_file('sha256', $productionPath) : '';

[$buildOk, $built, $buildNotes] = $baselineOk
    ? build_v004($source)
    : [false, '', ['team_redesign.php missing or not VERSION: v003.']];

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
        $applyMessages[] = 'Backed up team_redesign.php v003 to ' . $backupDir;
    }

    if (!preg_grep('/Could not/', $applyMessages)) {
        if (!write_atomic($targetPath, $built)) $applyMessages[] = 'Could not write team_redesign.php v004.';
        else $applyMessages[] = 'Installed team_redesign.php v004.';
    }

    $productionAfterHash = $productionExists ? hash_file('sha256', $productionPath) : '';
    $productionUntouched = $productionExists && $productionBeforeHash === $productionAfterHash;
    $targetNow = is_file($targetPath) ? (string)file_get_contents($targetPath) : '';

    $applyOk = !preg_grep('/Could not/', $applyMessages)
        && strpos($targetNow, 'VERSION: v004') !== false
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
<title>MRL Team Redesign Installer v004</title>
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
code{color:#76cfff}.note{color:#c8c8c8}a{color:#76cfff}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>MRL Team Redesign Installer v004</h1>
<p>Adds the approved cars background as the built-in default theme. Production <code>team.php</code> remains protected.</p>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<?php status_row('team_redesign.php present', $targetExists, $targetPath); ?>
<?php status_row('Expected redesign baseline v003', $baselineOk, $baselineOk ? 'Ready for v004.' : 'STOP - baseline mismatch.'); ?>
<?php status_row('Production team.php present', $productionExists, $productionPath); ?>
<?php status_row('v004 build generated in memory', $buildOk, $buildOk ? 'Transformation completed.' : implode(' ', $buildNotes)); ?>
<?php status_row('JSON content file preserved', is_file($contentPath), is_file($contentPath) ? $contentPath : 'Not found.'); ?>
</table>
</div>

<?php if ($buildOk): ?>
<div class="card">
<h2>What v004 changes</h2>
<ul>
<li>Makes <code>cars.jpg</code> the built-in default background.</li>
<li>Uses your approved <code>rgba(10, 20, 15, 0.70)</code> overlay.</li>
<li>Keeps the body transparent.</li>
<li>Adds named theme variables for future Cars / Dark / Light switching.</li>
<li>Does not change chart presentation or layout.</li>
</ul>
<?php if (!$applyRequested): ?>
<form method="post"><input type="hidden" name="apply" value="1"><button type="submit">Install team_redesign.php v004</button></form>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if ($applyRequested): ?>
<div class="card">
<h2>Apply Result</h2>
<p class="<?php echo $applyOk ? 'ok' : 'bad'; ?>"><strong><?php echo $applyOk ? 'SUCCESS' : 'FAILED'; ?></strong></p>
<ul><?php foreach ($applyMessages as $message): ?><li><?php echo ih($message); ?></li><?php endforeach; ?></ul>
<?php if ($applyOk): ?>
<p><a href="/team_redesign.php" target="_blank">Open team_redesign.php v004</a></p>
<p class="note">Production team.php was hash-verified unchanged.</p>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
</body>
</html>
