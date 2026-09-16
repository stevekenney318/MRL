<?php
declare(strict_types=1);

/**
 * install_current_user_team_chart_no_picks_contrast.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/30/2026 9:37:58 am
 *
 * PURPOSE:
 *   Fix the low-contrast "No picks found for this year." row in
 *   current_user_team_chart.php when Team is using a dark/non-light theme.
 *
 * TARGET:
 *   /current_user_team_chart.php
 *   expected current version: v005
 *
 * CHANGE:
 *   - bump target file v005 -> v006
 *   - add v006 changelog entry
 *   - give the empty-picks row an explicit light background and dark text
 *     so it remains readable under every Team theme
 *
 * NO DATABASE CHANGES.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

if (!isAdmin($_SESSION['userSession'] ?? null)) {
    http_response_code(403);
    exit('Not authorized.');
}

$target = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'current_user_team_chart.php';

$expectedVersion = 'v005';
$newVersion = 'v006';

$oldHeader = " * VERSION: v005\n * LAST MODIFIED: 8/20/2026 1:59:00 am\n";
$newHeader = " * VERSION: v006\n * LAST MODIFIED: 8/30/2026 9:37:58 am\n";

$oldChangelogAnchor = " * CHANGELOG:\n *\n";
$newChangelogAnchor =
    " * CHANGELOG:\n *\n" .
    " * v006 (8/30/2026 9:37:58 am)\n" .
    " * - FIX: Made the \"No picks found for this year.\" row readable in dark/non-light Team themes.\n" .
    " * - CHANGE: Presentation-only; no pick, LP/RD, scoring, or database logic changed.\n" .
    " *\n";

$oldRow = "    echo \"<tr><td colspan='6' style='text-align:center;'>No picks found for this year.</td></tr>\";";
$newRow = "    echo \"<tr><td colspan='6' style='text-align:center;background-color:#f2dcdb;color:#000000;font-weight:600;'>No picks found for this year.</td></tr>\";";

$action = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply')
    ? 'apply'
    : 'preview';

$exists = is_file($target);
$content = $exists ? file_get_contents($target) : false;
if ($content === false) {
    $content = '';
}

$normalized = str_replace("\r\n", "\n", $content);
$normalized = str_replace("\r", "\n", $normalized);

$checks = [
    'target_exists' => $exists,
    'version_v005_found' => substr_count($normalized, " * VERSION: v005\n") === 1,
    'header_found_exactly_once' => substr_count($normalized, str_replace("\r\n", "\n", $oldHeader)) === 1,
    'changelog_anchor_found_once' => substr_count($normalized, $oldChangelogAnchor) === 1,
    'old_empty_row_found_once' => substr_count($normalized, $oldRow) === 1,
    'new_version_not_already_present' => substr_count($normalized, " * VERSION: v006\n") === 0,
    'new_empty_style_not_already_present' => substr_count($normalized, $newRow) === 0,
];

$ready = !in_array(false, $checks, true);
$applyAttempted = ($action === 'apply');
$applySuccess = false;
$error = '';
$backupPath = '';

if ($applyAttempted) {
    if (!$ready) {
        $error = 'Apply blocked because one or more preflight checks failed.';
    } else {
        try {
            $updated = $normalized;

            $updated = str_replace(
                str_replace("\r\n", "\n", $oldHeader),
                str_replace('8/30/2026 9:37:58 am', '8/30/2026 9:37:58 am', $newHeader),
                $updated,
                $countHeader
            );

            $updated = str_replace(
                $oldChangelogAnchor,
                str_replace('8/30/2026 9:37:58 am', '8/30/2026 9:37:58 am', $newChangelogAnchor),
                $updated,
                $countChangelog
            );

            $updated = str_replace(
                $oldRow,
                $newRow,
                $updated,
                $countRow
            );

            if ($countHeader !== 1 || $countChangelog !== 1 || $countRow !== 1) {
                throw new RuntimeException('Transformation counts were not exactly 1/1/1.');
            }

            $updated = str_replace('8/30/2026 9:37:58 am', '8/30/2026 9:37:58 am', $updated);

            $backupDir = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . '_migration_backups'
                . DIRECTORY_SEPARATOR . 'current_user_team_chart_no_picks_contrast_20260830_093758am';

            if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                throw new RuntimeException('Could not create backup directory.');
            }

            $backupPath = $backupDir . DIRECTORY_SEPARATOR . 'current_user_team_chart.php';

            if (!copy($target, $backupPath)) {
                throw new RuntimeException('Could not create backup copy.');
            }

            if (file_put_contents($target, $updated, LOCK_EX) === false) {
                @copy($backupPath, $target);
                throw new RuntimeException('Could not write updated target file.');
            }

            $verify = file_get_contents($target);
            if ($verify === false) {
                @copy($backupPath, $target);
                throw new RuntimeException('Could not read target after write.');
            }

            $verify = str_replace(["\r\n", "\r"], "\n", $verify);

            $postOk =
                substr_count($verify, " * VERSION: v006\n") === 1
                && strpos($verify, 'No picks found for this year.') !== false
                && strpos($verify, 'background-color:#f2dcdb;color:#000000;font-weight:600;') !== false;

            if (!$postOk) {
                @copy($backupPath, $target);
                throw new RuntimeException('Postflight verification failed; original file was restored.');
            }

            $applySuccess = true;

        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$current = is_file($target) ? file_get_contents($target) : '';
$current = str_replace(["\r\n", "\r"], "\n", (string)$current);

$postInstalled =
    substr_count($current, " * VERSION: v006\n") === 1
    && strpos($current, 'background-color:#f2dcdb;color:#000000;font-weight:600;') !== false
    && strpos($current, 'No picks found for this year.') !== false;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL Current User Team Chart Contrast Fix</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
    --bg:#101214;--panel:#1d2023;--border:#4b5055;--text:#f0f0f0;
    --gold:#efc77e;--green:#63e69a;--red:#ff7e7e;--blue:#55c7ff;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Arial,sans-serif}
.wrap{width:96%;max-width:1200px;margin:20px auto}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}
.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:700}
.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}
.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid #3a3e42;text-align:left}
th{color:#ffe0a0}
.pass{color:var(--green);font-weight:700}
.fail{color:var(--red);font-weight:700}
.btn{display:inline-block;padding:11px 18px;border-radius:8px;font-weight:800;cursor:pointer}
.btn-apply{background:#16894b;color:#fff;border:1px solid #4be388}
code{color:var(--blue)}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>Current User Team Chart — Empty Picks Contrast Fix</h1>
<p><strong>Installer:</strong> v001 &nbsp; | &nbsp; <strong>Generated:</strong> 8/30/2026 9:37:58 am America/New_York</p>

<?php if ($applyAttempted && $applySuccess): ?>
<div class="banner ok">INSTALL COMPLETE — current_user_team_chart.php is now v006 and postflight passed.</div>
<?php elseif ($applyAttempted): ?>
<div class="banner bad">INSTALL NOT APPLIED — <?php echo h($error); ?></div>
<?php elseif ($postInstalled): ?>
<div class="banner ok">FIX ALREADY INSTALLED.</div>
<?php elseif ($ready): ?>
<div class="banner ok">PREVIEW PASS — exact target patterns found and safe to apply.</div>
<?php else: ?>
<div class="banner bad">PREVIEW BLOCKED — target file differs from the expected v005 baseline.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($checks as $name => $ok): ?>
<tr>
<td><?php echo h(str_replace('_', ' ', $name)); ?></td>
<td class="<?php echo $ok ? 'pass' : 'fail'; ?>"><?php echo $ok ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Change</h2>
<p>
The only visible behavior change is the empty-picks message row:
<code>No picks found for this year.</code>
</p>
<p>
It will now use the same pale chart-family background with explicit black text,
so it remains readable under Cars, Starry Night, Dark, and Light themes.
</p>
<p><strong>No pick logic, LP/RD logic, scoring logic, or database data is changed.</strong></p>
</div>

<div class="card">
<h2>Action</h2>
<?php if ($ready && !$postInstalled && !$applyAttempted): ?>
<form method="post" onsubmit="return confirm('Apply the presentation-only contrast fix to current_user_team_chart.php?');">
<input type="hidden" name="action" value="apply">
<button type="submit" class="btn btn-apply">Apply Contrast Fix</button>
</form>
<?php elseif (!$ready && !$postInstalled): ?>
<p class="fail">Apply is unavailable until all preflight checks pass.</p>
<?php endif; ?>

<?php if ($backupPath !== ''): ?>
<p><strong>Backup:</strong> <code><?php echo h($backupPath); ?></code></p>
<?php endif; ?>
</div>

</div>
</body>
</html>
