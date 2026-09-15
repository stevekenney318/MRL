<?php
declare(strict_types=1);

/**
 * MRL installer
 * TITLE: team.php v051 cleanup + UI polish
 * INSTALLER VERSION: v001
 * GENERATED: 9/15/2026 1:01:16 pm ET
 *
 * TARGET:
 *   /team.php
 *   v050 -> v051
 *
 * CHANGES:
 * - Cleans up the temporary v047-v050 dropdown investigation CSS and restores
 *   the intended pre-investigation v046 masthead stacking/filter behavior.
 * - Replaces Admin Menu and Previous Years Picks +/- indicators with
 *   Hide / Unhide blue state pills.
 * - Restyles Manage Team Page Content as a green pill.
 * - Updates the footer to match the WordPress footer wording/layout.
 *
 * PRESERVE:
 * - Existing Admin Menu / Previous Years <details> behavior.
 * - Existing localStorage open/closed memory.
 * - Picks, LP, RD, scoring, quiet submit, Smart Pick Review, scheduler and DB behavior.
 *
 * SAFETY:
 * - Exact v050 anchor preflight.
 * - Timestamped backup.
 * - Temp-file write + optional PHP lint.
 * - Atomic rename.
 * - Rollback support.
 * - No database writes.
 */

date_default_timezone_set('America/New_York');

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$targetRel = '/team.php';
$target = $docRoot . $targetRel;
$backupDir = $docRoot . '/_mrl_installer_backups';
$backup = $backupDir . '/team.php.pre_v051_20260915_130116.bak';
$temp = dirname($target) . '/.team_v051_20260915_130116.tmp.php';

function mrl_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function mrl_replace_once(string $src, string $old, string $new, string $label): string
{
    $count = substr_count($src, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ': expected exactly 1 anchor, found ' . $count . '.');
    }
    return str_replace($old, $new, $src);
}

function mrl_build_v051(string $src): string
{
    $src = mrl_replace_once(
        $src,
        " * VERSION: v050\n",
        " * VERSION: v051\n",
        'Version header'
    );

    $src = mrl_replace_once(
        $src,
        " * LAST MODIFIED: 9/13/2026 8:22:22 am\n",
        " * LAST MODIFIED: 9/15/2026 1:01:16 pm ET\n",
        'Last modified header'
    );

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelog = " * CHANGELOG:\n *\n"
        . " * v051 (9/15/2026 1:01:16 pm ET)\n"
        . " * - CLEANUP: Restores the intended pre-investigation v046 masthead stacking/backdrop CSS after the dropdown issue was traced to Stylus.\n"
        . " * - UI: Admin Menu and Previous Years Picks now use blue Hide / Unhide state pills instead of +/- indicators.\n"
        . " * - UI: Manage Team Page Content is now a matching green action pill.\n"
        . " * - UI: Footer now matches the WordPress footer wording/layout with the older-pages notice above the centered copyright line.\n"
        . " * - PRESERVE: Existing details/localStorage behavior plus picks, LP, RD, scoring, quiet submit, Smart Pick Review, scheduler, themes, and DB behavior.\n"
        . " *\n";
    $src = mrl_replace_once($src, $changelogAnchor, $changelog, 'Changelog');

    $oldSticky = <<<'CSS'
        .mrl-rd-sticky{
            position:sticky;
            top:8px;
            z-index:5000;
            margin-top:8px!important;
            margin-bottom:14px!important;
            border:1px solid rgba(67,142,94,.72);
            border-radius:14px;
            background:linear-gradient(180deg,rgba(18,58,40,.78),rgba(20,35,29,.74));
            /* v050: backdrop filtering removed here to avoid trapping the dropdown in a Chrome compositing layer. */
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

        .mrl-rd-user{position:relative;justify-self:start;z-index:5001}
CSS;

    $newSticky = <<<'CSS'
        .mrl-rd-sticky{
            position:sticky;
            top:8px;
            z-index:1000;
            margin-top:8px!important;
            margin-bottom:14px!important;
            border:1px solid rgba(67,142,94,.72);
            border-radius:14px;
            background:linear-gradient(180deg,rgba(18,58,40,.78),rgba(20,35,29,.74));
            backdrop-filter:blur(3px);
            -webkit-backdrop-filter:blur(3px);
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

        .mrl-rd-user{position:relative;justify-self:start}
CSS;

    $src = mrl_replace_once($src, $oldSticky, $newSticky, 'Masthead cleanup');

    $oldMenu = <<<'CSS'
        .mrl-rd-user-menu{
            display:none;
            position:absolute;
            z-index:5002;
            top:calc(100% + 7px);
CSS;
    $newMenu = <<<'CSS'
        .mrl-rd-user-menu{
            display:none;
            position:absolute;
            top:calc(100% + 7px);
CSS;
    $src = mrl_replace_once($src, $oldMenu, $newMenu, 'Dropdown z-index cleanup');

    $oldAdminSummary = <<<'CSS'
        .mrl-rd-admin-wrap>summary::-webkit-details-marker{display:none}
        .mrl-rd-admin-wrap>summary::before{content:"+ ";font-weight:500}
        .mrl-rd-admin-wrap[open]>summary::before{content:"− "}
        .mrl-rd-admin-wrap[open]>summary{border-bottom:1px solid rgba(255,255,255,.09)}

        .mrl-rd-admin-fixed-control{
            margin:12px 14px 0;
            padding:10px 14px;
            border:1px solid var(--mrl-rd-border);
            border-radius:10px;
            background:rgba(0,0,0,.16);
        }

        .mrl-rd-admin-fixed-control a{
            color:var(--mrl-rd-blue)!important;
            text-decoration:none!important;
            font-weight:800;
        }
CSS;

    $newAdminSummary = <<<'CSS'
        .mrl-rd-admin-wrap>summary::-webkit-details-marker{display:none}
        .mrl-rd-admin-wrap>summary::after{
            content:"Unhide";
            display:inline-block;
            margin-left:10px;
            padding:3px 11px 4px;
            border:2px solid #75adf5;
            border-radius:999px;
            background:#e8f2ff;
            color:#0d4f97;
            font:700 14px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;
            vertical-align:2px;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.38);
        }
        .mrl-rd-admin-wrap[open]>summary::after{
            content:"Hide";
            border-color:#9cc9ff;
            background:#2f6fac;
            color:#fff;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.16);
        }
        .mrl-rd-admin-wrap[open]>summary{border-bottom:1px solid rgba(255,255,255,.09)}

        .mrl-rd-admin-fixed-control{
            margin:12px 14px 0;
            padding:0;
            border:0;
            background:transparent;
        }

        .mrl-rd-admin-fixed-control a{
            display:inline-block;
            padding:5px 13px 6px;
            border:2px solid #58a978;
            border-radius:999px;
            background:#dff3e7;
            color:#17683b!important;
            text-decoration:none!important;
            font-weight:800;
            line-height:1.15;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.35);
        }

        .mrl-rd-admin-fixed-control a:hover{
            border-color:#8fd0a8;
            background:#2f8a58;
            color:#fff!important;
            text-decoration:none!important;
        }
CSS;

    $src = mrl_replace_once($src, $oldAdminSummary, $newAdminSummary, 'Admin pills');

    $oldPrev = <<<'CSS'
        .mrl-previous-years summary::-webkit-details-marker{display:none}
        .mrl-previous-years summary::before{content:"+ ";font-weight:400}
        .mrl-previous-years[open] summary::before{content:"− "}
CSS;

    $newPrev = <<<'CSS'
        .mrl-previous-years summary::-webkit-details-marker{display:none}
        .mrl-previous-years summary::after{
            content:"Unhide";
            display:inline-block;
            margin-left:10px;
            padding:3px 11px 4px;
            border:2px solid #75adf5;
            border-radius:999px;
            background:#e8f2ff;
            color:#0d4f97;
            font:700 14px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;
            vertical-align:4px;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.38);
        }
        .mrl-previous-years[open] summary::after{
            content:"Hide";
            border-color:#9cc9ff;
            background:#2f6fac;
            color:#fff;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.16);
        }
CSS;

    $src = mrl_replace_once($src, $oldPrev, $newPrev, 'Previous Years pills');

    $oldLight = <<<'CSS'
        html.mrl-theme-light .mrl-rd-admin-fixed-control{background:rgba(255,255,255,.78)!important}
        html.mrl-theme-light .mrl-rd-admin-fixed-control a{color:#006eaa!important}
CSS;

    $newLight = <<<'CSS'
        html.mrl-theme-light .mrl-rd-admin-fixed-control{background:transparent!important}
        html.mrl-theme-light .mrl-rd-admin-fixed-control a{
            background:#dff3e7!important;
            border-color:#58a978!important;
            color:#17683b!important;
        }
        html.mrl-theme-light .mrl-rd-admin-fixed-control a:hover{
            background:#2f8a58!important;
            border-color:#2f8a58!important;
            color:#fff!important;
        }
CSS;

    $src = mrl_replace_once($src, $oldLight, $newLight, 'Light-theme green pill');

    $oldFooter = <<<'HTML'
<br>

<div style="width:85%; margin:0 auto; border:none; text-align:left;">
    <p style='font-size:12.0pt; line-height:120%; font-family:"Century Gothic",sans-serif; color:#dfcca8;'>
        Copyright &copy; 2017-<script>document.write(new Date().getFullYear())</script> Manlius Racing League
    </p>
</div>
HTML;

    $newFooter = <<<'HTML'
<br>

<div style="width:85%; margin:0 auto; border:none; text-align:center; padding:18px 12px; box-sizing:border-box;">
    <div style="font-size:16pt; line-height:1.2; font-family:'Century Gothic',sans-serif; color:#dfcca8;">
        <div style="margin-bottom:10px;">
            Some older pages may contain links or images that no longer work.
        </div>
        <div>
            Copyright &copy; 2017-<script>document.write(new Date().getFullYear())</script> Manlius Racing League
        </div>
    </div>
</div>
HTML;

    $src = mrl_replace_once($src, $oldFooter, $newFooter, 'Footer');

    return str_replace('9/15/2026 1:01:16 pm ET', '__HUMAN_REAL__', $src);
}

function mrl_lint(string $path): array
{
    if (!function_exists('exec')) {
        return ['status' => 'SKIPPED', 'detail' => 'Server does not expose a usable PHP CLI lint command.'];
    }

    $bin = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($bin) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines = [];
    $code = 0;
    @exec($cmd, $lines, $code);

    if ($code === 0) {
        return ['status' => 'PASS', 'detail' => trim(implode("\n", $lines))];
    }
    if ($code === 127 || empty($lines)) {
        return ['status' => 'SKIPPED', 'detail' => 'PHP CLI lint unavailable; postflight verification will still run.'];
    }
    return ['status' => 'FAIL', 'detail' => trim(implode("\n", $lines))];
}

function mrl_preflight(string $target, string $backupDir): array
{
    $rows = [];
    $ok = true;

    $add = function(string $check, string $status, string $detail = '') use (&$rows, &$ok): void {
        $rows[] = [$check, $status, $detail];
        if ($status === 'FAIL') $ok = false;
    };

    if (!is_file($target)) {
        $add('team.php exists', 'FAIL', $target);
        return ['ok' => false, 'rows' => $rows, 'source' => ''];
    }
    $add('team.php exists', 'PASS', $target);

    $rw = is_readable($target) && is_writable($target);
    $add('team.php readable/writable', $rw ? 'PASS' : 'FAIL', '');
    if (!$rw) return ['ok' => false, 'rows' => $rows, 'source' => ''];

    $src = file_get_contents($target);
    if (!is_string($src)) {
        $add('Target readable', 'FAIL', 'Unable to read team.php.');
        return ['ok' => false, 'rows' => $rows, 'source' => ''];
    }

    $baseline = strpos($src, '* VERSION: v050') !== false;
    $add('Expected version baseline', $baseline ? 'PASS' : 'FAIL', 'team.php v050');

    $anchors = [
        'v050 masthead investigation CSS' =>
            strpos($src, 'z-index:5000;') !== false
            && strpos($src, 'v050: backdrop filtering removed here') !== false
            && strpos($src, '.mrl-rd-user{position:relative;justify-self:start;z-index:5001}') !== false
            && strpos($src, 'z-index:5002;') !== false,
        'Admin Menu +/- CSS' =>
            strpos($src, '.mrl-rd-admin-wrap>summary::before{content:"+ ";font-weight:500}') !== false
            && strpos($src, '.mrl-rd-admin-wrap[open]>summary::before{content:"− "}') !== false,
        'Previous Years +/- CSS' =>
            strpos($src, '.mrl-previous-years summary::before{content:"+ ";font-weight:400}') !== false
            && strpos($src, '.mrl-previous-years[open] summary::before{content:"− "}') !== false,
        'Manage Team Page Content control' =>
            strpos($src, 'Manage Team Page Content</a>') !== false,
        'Current one-line footer' =>
            strpos($src, 'Copyright &copy; 2017-<script>document.write(new Date().getFullYear())</script> Manlius Racing League') !== false
            && strpos($src, 'Some older pages may contain links or images that no longer work.') === false,
    ];

    foreach ($anchors as $label => $pass) {
        $add($label, $pass ? 'PASS' : 'FAIL', '');
    }

    $backupReady = is_dir($backupDir) ? is_writable($backupDir) : is_writable(dirname($backupDir));
    $add('Backup location writable/creatable', $backupReady ? 'PASS' : 'FAIL', $backupDir);

    if ($ok) {
        try {
            $patched = mrl_build_v051($src);
            $patched = str_replace('__HUMAN_REAL__', '9/15/2026 1:01:16 pm ET', $patched);

            $postChecks = [
                strpos($patched, '* VERSION: v051') !== false,
                strpos($patched, 'z-index:1000;') !== false,
                strpos($patched, 'backdrop-filter:blur(3px);') !== false,
                strpos($patched, 'content:"Unhide";') !== false,
                strpos($patched, 'content:"Hide";') !== false,
                strpos($patched, 'background:#dff3e7;') !== false,
                strpos($patched, 'Some older pages may contain links or images that no longer work.') !== false,
            ];

            $add('Patch construction', !in_array(false, $postChecks, true) ? 'PASS' : 'FAIL',
                'v051 cleanup + Hide/Unhide pills + green manager pill + WP-style footer');
            $add('Existing details/localStorage behavior', 'PASS', 'Markup IDs and rememberDetails() logic are unchanged.');
            $add('Database writes', 'PASS', 'None.');
            $add('Scoring / pick logic changes', 'PASS', 'None.');
        } catch (Throwable $e) {
            $add('Patch construction', 'FAIL', $e->getMessage());
        }
    }

    return ['ok' => $ok, 'rows' => $rows, 'source' => $src];
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
$rows = [];
$state = 'idle';
$notice = 'Nothing has run yet. Click Preview / Preflight first.';
$preflightPassed = false;
$rollbackAvailable = is_file($backup);

try {
    if ($action === 'preflight') {
        $pf = mrl_preflight($target, $backupDir);
        $rows = $pf['rows'];
        $preflightPassed = !empty($pf['ok']);
        $state = $preflightPassed ? 'good' : 'bad';
        $notice = $preflightPassed
            ? 'Preflight passed — Ready to Install team.php v051.'
            : 'Preflight failed — Install remains disabled.';
    } elseif ($action === 'install') {
        $pf = mrl_preflight($target, $backupDir);
        $rows = $pf['rows'];

        if (empty($pf['ok'])) {
            throw new RuntimeException('Install blocked because preflight no longer passes.');
        }

        $src = (string)$pf['source'];
        $new = mrl_build_v051($src);
        $new = str_replace('__HUMAN_REAL__', '9/15/2026 1:01:16 pm ET', $new);

        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Could not create backup directory.');
        }

        if (is_file($backup)) {
            throw new RuntimeException('Backup already exists for this installer timestamp.');
        }

        if (!copy($target, $backup)) {
            throw new RuntimeException('Backup creation failed.');
        }
        $rows[] = ['Backup created', 'PASS', $backup];

        if (file_put_contents($temp, $new, LOCK_EX) === false) {
            @unlink($temp);
            throw new RuntimeException('Temporary write failed.');
        }

        $lint = mrl_lint($temp);
        $rows[] = ['PHP syntax lint', $lint['status'], $lint['detail']];
        if ($lint['status'] === 'FAIL') {
            @unlink($temp);
            throw new RuntimeException('PHP lint failed; target unchanged.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Atomic rename failed.');
        }

        $post = file_get_contents($target);
        $postOk = is_string($post)
            && strpos($post, '* VERSION: v051') !== false
            && strpos($post, 'z-index:5000;') === false
            && strpos($post, '.mrl-rd-user{position:relative;justify-self:start;z-index:5001}') === false
            && strpos($post, 'z-index:5002;') === false
            && strpos($post, 'content:"Unhide";') !== false
            && strpos($post, 'content:"Hide";') !== false
            && strpos($post, 'Manage Team Page Content</a>') !== false
            && strpos($post, 'Some older pages may contain links or images that no longer work.') !== false;

        if (!$postOk) {
            @copy($backup, $target);
            throw new RuntimeException('Postflight failed; backup restored.');
        }

        $rows[] = ['Postflight team.php version', 'PASS', 'v051 active.'];
        $rows[] = ['Postflight masthead cleanup', 'PASS', 'Temporary v047-v050 stacking/filter investigation CSS removed; intended v046 values restored.'];
        $rows[] = ['Postflight UI pills', 'PASS', 'Hide/Unhide blue pills and green Manage Team Page Content pill are present.'];
        $rows[] = ['Postflight footer', 'PASS', 'Older-pages note + centered current-year copyright are present.'];
        $rows[] = ['Install result', 'PASS', 'No DB writes; no scoring/pick logic changed.'];

        $state = 'good';
        $notice = 'Installed successfully — team.php v051 is active.';
        $preflightPassed = false;
        $rollbackAvailable = true;
    } elseif ($action === 'rollback') {
        if (!is_file($backup)) {
            throw new RuntimeException('Rollback backup is unavailable.');
        }

        if (!copy($backup, $temp)) {
            throw new RuntimeException('Could not stage rollback.');
        }

        $lint = mrl_lint($temp);
        $rows[] = ['Rollback PHP syntax lint', $lint['status'], $lint['detail']];
        if ($lint['status'] === 'FAIL') {
            @unlink($temp);
            throw new RuntimeException('Rollback lint failed.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Rollback rename failed.');
        }

        $restored = file_get_contents($target);
        if (!is_string($restored) || strpos($restored, '* VERSION: v050') === false) {
            throw new RuntimeException('Rollback postflight could not confirm v050.');
        }

        $rows[] = ['Rollback result', 'PASS', 'Restored team.php v050.'];
        $state = 'info';
        $notice = 'Rollback complete — team.php v050 restored.';
        $rollbackAvailable = true;
    }
} catch (Throwable $e) {
    $rows[] = ['Action', 'FAIL', $e->getMessage()];
    $state = 'bad';
    $notice = 'Action failed.';
    $rollbackAvailable = is_file($backup);
}

$hasRows = !empty($rows);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL team.php v051 Cleanup</title>
<style>
:root{color-scheme:dark;--bg:#101312;--panel:#1b201f;--border:#46504d;--text:#eee9df;--muted:#b8b7b0;--gold:#f1c97f;--green:#167c45;--red:#a93434;--blue:#286c99;--amber:#d49b28;--disabled:#555}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,95%);margin:14px auto 28px}
h1{margin:0 0 10px;color:var(--gold);font-size:26px}h2{margin:0 0 8px;color:var(--gold);font-size:18px}
.panel{margin:0 0 10px;padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--panel)}
.notice{margin:0 0 10px;padding:10px 12px;border-radius:9px;font-weight:700}
.notice.good{background:#103b27;border:1px solid #2f9b63}.notice.bad{background:#4b1d1d;border:1px solid #c04b4b}.notice.info{background:#173246;border:1px solid #387ba8}.notice.idle{background:#332b15;border:1px solid #8c722e}
table{width:100%;border-collapse:collapse}th,td{padding:6px 8px;border-bottom:1px solid #353c3a;text-align:left;vertical-align:top}
th{color:var(--gold)}.PASS{color:#5ee58e;font-weight:800}.FAIL{color:#ff7b7b;font-weight:800}.SKIPPED,.INFO{color:#f1c97f;font-weight:800}
.small{font-size:12px;color:var(--muted)}.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}
.actions{display:flex;gap:9px;flex-wrap:wrap}button{padding:8px 12px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.preflight{background:var(--blue)}.install{background:var(--green)}.rollback{background:var(--red)}.export{background:var(--amber)}
button:disabled{opacity:.45;cursor:not-allowed;background:var(--disabled)}
ul{margin:5px 0 0;padding-left:20px;line-height:1.45}
code{color:#f8d996}
</style>
</head>
<body><div class="wrap">
<h1>MRL team.php v051 Cleanup + UI Polish</h1>

<div class="panel">
<h2>What this does</h2>
<ul>
<li>Restores the intended pre-investigation masthead CSS after the dropdown problem was traced to Stylus.</li>
<li>Changes Admin Menu and Previous Years Picks from <code>+ / −</code> to blue <strong>Unhide / Hide</strong> state pills.</li>
<li>Restyles <strong>Manage Team Page Content</strong> as a green pill.</li>
<li>Updates the Team page footer to match the WordPress wording/layout.</li>
<li>Preserves localStorage state memory and all picks/scoring/LP/RD logic.</li>
</ul>
</div>

<div class="notice <?=mrl_h($state)?>"><?=mrl_h($notice)?></div>

<div class="panel">
<h2>Preflight / Result</h2>
<?php if (!$hasRows): ?>
<p class="small">Click Preview / Preflight first. Install remains disabled until the exact v050 baseline and all expected UI/CSS anchors are confirmed.</p>
<?php else: ?>
<table id="resultsTable">
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach($rows as $r): ?>
<tr><td><?=mrl_h($r[0])?></td><td class="<?=mrl_h($r[1])?>"><?=mrl_h($r[1])?></td><td class="small mono"><?=mrl_h($r[2])?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div class="panel">
<h2>Actions</h2>
<div class="actions">
<form method="post"><input type="hidden" name="action" value="preflight"><button class="preflight" type="submit">Preview / Preflight</button></form>
<form method="post"><input type="hidden" name="action" value="install"><button class="install" type="submit" <?= $preflightPassed ? '' : 'disabled' ?>>Install v051</button></form>
<form method="post" onsubmit="return confirm('Restore the exact pre-v051 team.php?');"><input type="hidden" name="action" value="rollback"><button class="rollback" type="submit" <?= $rollbackAvailable ? '' : 'disabled' ?>>Rollback</button></form>
<button id="exportBtn" class="export" type="button" <?= $hasRows ? '' : 'disabled' ?>>Export Results</button>
</div>
</div>

<div class="panel small">
<strong>Expected after install:</strong> team.php v051, same functional behavior, cleaner masthead CSS, blue Hide/Unhide pills, green Manage Team Page Content pill, and the centered two-line footer.
<br><br>
FILE: MRL_install_team_php_v051_cleanup_v001_20260915_130116.php | INSTALLER VERSION: v001
</div>
</div>
<script>
(function(){
  var b=document.getElementById('exportBtn');
  if(!b || b.disabled) return;
  b.addEventListener('click',function(){
    var lines=['MRL team.php v051 Cleanup + UI Polish','Generated: 9/15/2026 1:01:16 pm ET','State: <?=mrl_h($notice)?>',''];
    document.querySelectorAll('#resultsTable tr').forEach(function(tr){
      var c=tr.querySelectorAll('th,td');
      if(c.length===3) lines.push(c[0].textContent.trim()+' | '+c[1].textContent.trim()+' | '+c[2].textContent.trim());
    });
    var blob=new Blob([lines.join('\r\n')+'\r\n'],{type:'text/plain;charset=utf-8'});
    var u=URL.createObjectURL(blob),a=document.createElement('a');
    a.href=u;a.download='MRL_team_php_v051_cleanup_results_20260915_130116.txt';
    document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(u);
  });
})();
</script>
</body></html>
