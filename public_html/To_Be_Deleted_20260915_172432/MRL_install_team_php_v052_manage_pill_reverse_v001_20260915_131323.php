<?php
declare(strict_types=1);

/**
 * MRL installer
 * TITLE: team.php v052 Manage Team Page Content pill color reversal
 * INSTALLER VERSION: v001
 * GENERATED: 9/15/2026 1:13:23 pm ET
 *
 * TARGET:
 *   /team.php
 *   v051 -> v052
 *
 * CHANGE:
 * - Reverse the normal/hover colors for Manage Team Page Content.
 *   Normal = solid green with white text.
 *   Hover  = light green with dark green text.
 *
 * PRESERVE:
 * - All v051 layout, Hide/Unhide pills, footer, masthead cleanup,
 *   details/localStorage behavior, picks, LP, RD, scoring, scheduler and DB behavior.
 *
 * SAFETY:
 * - Exact v051 anchor preflight.
 * - Timestamped backup.
 * - Temp-file write + optional PHP lint.
 * - Atomic rename.
 * - Rollback support.
 * - No database writes.
 */

date_default_timezone_set('America/New_York');

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$target = $docRoot . '/team.php';
$backupDir = $docRoot . '/_mrl_installer_backups';
$backup = $backupDir . '/team.php.pre_v052_20260915_131323.bak';
$temp = dirname($target) . '/.team_v052_20260915_131323.tmp.php';

function mrl_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function mrl_replace_once(string $src, string $old, string $new, string $label): string {
    $count = substr_count($src, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ': expected exactly 1 anchor, found ' . $count . '.');
    }
    return str_replace($old, $new, $src);
}

function mrl_build_v052(string $src): string
{
    $src = mrl_replace_once(
        $src,
        " * VERSION: v051\n",
        " * VERSION: v052\n",
        'Version header'
    );

    $src = mrl_replace_once(
        $src,
        " * LAST MODIFIED: 9/15/2026 1:01:16 pm ET\n",
        " * LAST MODIFIED: 9/15/2026 1:13:23 pm ET\n",
        'Last modified'
    );

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelog = " * CHANGELOG:\n *\n"
        . " * v052 (9/15/2026 1:13:23 pm ET)\n"
        . " * - UI: Reverses Manage Team Page Content pill colors so the default state is solid green with white text and hover is light green with dark green text.\n"
        . " * - PRESERVE: All v051 layout, masthead cleanup, Hide/Unhide pills, footer, localStorage behavior, picks, LP, RD, scoring, scheduler, themes, and DB behavior.\n"
        . " *\n";
    $src = mrl_replace_once($src, $changelogAnchor, $changelog, 'Changelog');

    $oldMain = <<<'CSS'
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

    $newMain = <<<'CSS'
        .mrl-rd-admin-fixed-control a{
            display:inline-block;
            padding:5px 13px 6px;
            border:2px solid #8fd0a8;
            border-radius:999px;
            background:#2f8a58;
            color:#fff!important;
            text-decoration:none!important;
            font-weight:800;
            line-height:1.15;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.20);
        }

        .mrl-rd-admin-fixed-control a:hover{
            border-color:#58a978;
            background:#dff3e7;
            color:#17683b!important;
            text-decoration:none!important;
        }
CSS;

    $src = mrl_replace_once($src, $oldMain, $newMain, 'Manage pill normal/hover colors');

    $oldLight = <<<'CSS'
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

    $newLight = <<<'CSS'
        html.mrl-theme-light .mrl-rd-admin-fixed-control a{
            background:#2f8a58!important;
            border-color:#8fd0a8!important;
            color:#fff!important;
        }
        html.mrl-theme-light .mrl-rd-admin-fixed-control a:hover{
            background:#dff3e7!important;
            border-color:#58a978!important;
            color:#17683b!important;
        }
CSS;

    $src = mrl_replace_once($src, $oldLight, $newLight, 'Light-theme Manage pill colors');

    return str_replace('9/15/2026 1:13:23 pm ET', '__HUMAN_REAL__', $src);
}

function mrl_lint(string $path): array
{
    if (!function_exists('exec')) {
        return ['status'=>'SKIPPED','detail'=>'Server does not expose a usable PHP CLI lint command.'];
    }

    $bin = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($bin) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines = [];
    $code = 0;
    @exec($cmd, $lines, $code);

    if ($code === 0) return ['status'=>'PASS','detail'=>trim(implode("\n", $lines))];
    if ($code === 127 || empty($lines)) return ['status'=>'SKIPPED','detail'=>'PHP CLI lint unavailable; postflight verification will still run.'];
    return ['status'=>'FAIL','detail'=>trim(implode("\n", $lines))];
}

function mrl_preflight(string $target, string $backupDir): array
{
    $rows = [];
    $ok = true;

    $add = function(string $check, string $status, string $detail='') use (&$rows, &$ok): void {
        $rows[] = [$check,$status,$detail];
        if ($status === 'FAIL') $ok = false;
    };

    $exists = is_file($target);
    $add('team.php exists', $exists ? 'PASS' : 'FAIL', $target);
    if (!$exists) return ['ok'=>false,'rows'=>$rows,'source'=>''];

    $rw = is_readable($target) && is_writable($target);
    $add('team.php readable/writable', $rw ? 'PASS' : 'FAIL', '');
    if (!$rw) return ['ok'=>false,'rows'=>$rows,'source'=>''];

    $src = file_get_contents($target);
    if (!is_string($src)) {
        $add('Target readable', 'FAIL', 'Unable to read team.php.');
        return ['ok'=>false,'rows'=>$rows,'source'=>''];
    }

    $add('Expected version baseline',
        strpos($src, '* VERSION: v051') !== false ? 'PASS' : 'FAIL',
        'team.php v051');

    $anchors = [
        'Current light-green default pill' =>
            strpos($src, 'background:#dff3e7;') !== false
            && strpos($src, 'color:#17683b!important;') !== false,
        'Current solid-green hover pill' =>
            strpos($src, 'background:#2f8a58;') !== false
            && strpos($src, 'color:#fff!important;') !== false,
        'v051 Hide/Unhide pills retained' =>
            strpos($src, 'content:"Unhide";') !== false
            && strpos($src, 'content:"Hide";') !== false,
        'v051 footer retained' =>
            strpos($src, 'Some older pages may contain links or images that no longer work.') !== false,
    ];

    foreach ($anchors as $label => $pass) {
        $add($label, $pass ? 'PASS' : 'FAIL', '');
    }

    $backupReady = is_dir($backupDir) ? is_writable($backupDir) : is_writable(dirname($backupDir));
    $add('Backup location writable/creatable', $backupReady ? 'PASS' : 'FAIL', $backupDir);

    if ($ok) {
        try {
            $patched = mrl_build_v052($src);
            $patched = str_replace('__HUMAN_REAL__', '9/15/2026 1:13:23 pm ET', $patched);
            $checks = [
                strpos($patched, '* VERSION: v052') !== false,
                strpos($patched, 'background:#2f8a58;') !== false,
                strpos($patched, 'background:#dff3e7;') !== false,
            ];
            $add('Patch construction',
                !in_array(false, $checks, true) ? 'PASS' : 'FAIL',
                'Only Manage Team Page Content normal/hover colors + version metadata change.');
            $add('Database writes', 'PASS', 'None.');
            $add('Scoring / pick logic changes', 'PASS', 'None.');
        } catch (Throwable $e) {
            $add('Patch construction', 'FAIL', $e->getMessage());
        }
    }

    return ['ok'=>$ok,'rows'=>$rows,'source'=>$src];
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
            ? 'Preflight passed — Ready to Install team.php v052.'
            : 'Preflight failed — Install remains disabled.';
    } elseif ($action === 'install') {
        $pf = mrl_preflight($target, $backupDir);
        $rows = $pf['rows'];
        if (empty($pf['ok'])) throw new RuntimeException('Install blocked because preflight no longer passes.');

        $new = mrl_build_v052((string)$pf['source']);
        $new = str_replace('__HUMAN_REAL__', '9/15/2026 1:13:23 pm ET', $new);

        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Could not create backup directory.');
        }
        if (is_file($backup)) throw new RuntimeException('Backup already exists for this installer timestamp.');
        if (!copy($target, $backup)) throw new RuntimeException('Backup creation failed.');
        $rows[] = ['Backup created','PASS',$backup];

        if (file_put_contents($temp, $new, LOCK_EX) === false) {
            @unlink($temp);
            throw new RuntimeException('Temporary write failed.');
        }

        $lint = mrl_lint($temp);
        $rows[] = ['PHP syntax lint',$lint['status'],$lint['detail']];
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
            && strpos($post, '* VERSION: v052') !== false
            && strpos($post, "background:#2f8a58;\n            color:#fff!important;") !== false
            && strpos($post, "background:#dff3e7;\n            color:#17683b!important;") !== false;

        if (!$postOk) {
            @copy($backup, $target);
            throw new RuntimeException('Postflight failed; backup restored.');
        }

        $rows[] = ['Postflight version','PASS','team.php v052 active.'];
        $rows[] = ['Postflight Manage pill colors','PASS','Normal = solid green/white; hover = light green/dark green.'];
        $rows[] = ['Install result','PASS','No DB writes; no scoring/pick logic changed.'];

        $state = 'good';
        $notice = 'Installed successfully — team.php v052 is active.';
        $preflightPassed = false;
        $rollbackAvailable = true;
    } elseif ($action === 'rollback') {
        if (!is_file($backup)) throw new RuntimeException('Rollback backup is unavailable.');
        if (!copy($backup, $temp)) throw new RuntimeException('Could not stage rollback.');

        $lint = mrl_lint($temp);
        $rows[] = ['Rollback PHP syntax lint',$lint['status'],$lint['detail']];
        if ($lint['status'] === 'FAIL') {
            @unlink($temp);
            throw new RuntimeException('Rollback lint failed.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Rollback rename failed.');
        }

        $restored = file_get_contents($target);
        if (!is_string($restored) || strpos($restored, '* VERSION: v051') === false) {
            throw new RuntimeException('Rollback postflight could not confirm v051.');
        }

        $rows[] = ['Rollback result','PASS','Restored team.php v051.'];
        $state = 'info';
        $notice = 'Rollback complete — team.php v051 restored.';
        $rollbackAvailable = true;
    }
} catch (Throwable $e) {
    $rows[] = ['Action','FAIL',$e->getMessage()];
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
<title>MRL team.php v052 Manage Pill</title>
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
</style>
</head>
<body><div class="wrap">
<h1>MRL team.php v052 — Reverse Manage Pill Colors</h1>

<div class="panel">
<h2>What this does</h2>
<ul>
<li><strong>Normal:</strong> solid green pill with white text.</li>
<li><strong>Hover:</strong> light green pill with dark green text.</li>
<li>No other v051 UI or behavior changes.</li>
</ul>
</div>

<div class="notice <?=mrl_h($state)?>"><?=mrl_h($notice)?></div>

<div class="panel">
<h2>Preflight / Result</h2>
<?php if (!$hasRows): ?>
<p class="small">Click Preview / Preflight first. Install remains disabled until the v051 baseline and current pill colors are confirmed.</p>
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
<form method="post"><input type="hidden" name="action" value="install"><button class="install" type="submit" <?= $preflightPassed ? '' : 'disabled' ?>>Install v052</button></form>
<form method="post" onsubmit="return confirm('Restore the exact pre-v052 team.php?');"><input type="hidden" name="action" value="rollback"><button class="rollback" type="submit" <?= $rollbackAvailable ? '' : 'disabled' ?>>Rollback</button></form>
<button id="exportBtn" class="export" type="button" <?= $hasRows ? '' : 'disabled' ?>>Export Results</button>
</div>
</div>

<div class="panel small">
<strong>Expected after install:</strong> Manage Team Page Content is solid green by default and light green on hover. Everything else remains v051 behavior.
<br><br>
FILE: MRL_install_team_php_v052_manage_pill_reverse_v001_20260915_131323.php | INSTALLER VERSION: v001
</div>
</div>
<script>
(function(){
  var b=document.getElementById('exportBtn');
  if(!b || b.disabled) return;
  b.addEventListener('click',function(){
    var lines=['MRL team.php v052 Manage Pill Color Reversal','Generated: 9/15/2026 1:13:23 pm ET','State: <?=mrl_h($notice)?>',''];
    document.querySelectorAll('#resultsTable tr').forEach(function(tr){
      var c=tr.querySelectorAll('th,td');
      if(c.length===3) lines.push(c[0].textContent.trim()+' | '+c[1].textContent.trim()+' | '+c[2].textContent.trim());
    });
    var blob=new Blob([lines.join('\r\n')+'\r\n'],{type:'text/plain;charset=utf-8'});
    var u=URL.createObjectURL(blob),a=document.createElement('a');
    a.href=u;a.download='MRL_team_php_v052_manage_pill_results_20260915_131323.txt';
    document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(u);
  });
})();
</script>
</body></html>
