<?php
declare(strict_types=1);

/**
 * fix_team_smart_pick_review_postsubmit_baseline.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/31/2026 4:39:31 pm
 *
 * PURPOSE:
 *   Fix Smart Pick Review's in-memory baseline after a successful quiet submit.
 *
 * TARGET:
 *   team.php v045 -> v046
 *
 * BUG:
 *   Smart Pick Review remembers the saved picks when the page first loads.
 *   After a successful quiet submit, the visible page updates without a reload,
 *   but that in-memory baseline remained stale. An immediate second Submit could
 *   therefore compare against the old pre-submit values.
 *
 * FIX:
 *   - Quiet-submit dispatches a local "mrl:picks-saved" event only AFTER the
 *     server response has returned the existing success marker.
 *   - Smart Pick Review listens for that event and replaces its baseline with
 *     the just-saved form values.
 *   - No refresh is required.
 *
 * PRESERVE:
 *   - submit-team-picks.php untouched and authoritative
 *   - existing quiet-submit success/error behavior
 *   - Smart Pick Review UI and layout
 *   - identical-pick blocking
 *   - SEG / LP / RD logic
 *   - Custom HTML handshake
 *   - all DB/scoring behavior
 *
 * ROLLBACK:
 *   Dedicated exact team.php backup + manifest.
 *
 * NO DATABASE CHANGES.
 * NO JSON CHANGES.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

require __DIR__ . '/config.php';
require __DIR__ . '/config_mrl.php';

$uid=(int)($_SESSION['userSession'] ?? 0);
if (!isAdmin($uid)) {
    http_response_code(403);
    exit('Admin access required.');
}

function tsprb_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function tsprb_lf(string $s): string {
    return str_replace(["\r\n","\r"],"\n",$s);
}
function tsprb_once(string $source,string $old,string $new,string $label): string {
    $count=substr_count($source,$old);
    if ($count!==1) throw new RuntimeException($label.' expected once; found '.$count.'.');
    return str_replace($old,$new,$source);
}

$teamPath=__DIR__.'/team.php';
$backupRoot=__DIR__.'/_migration_backups/team_smart_pick_review_postsubmit_baseline_v001';
$backupPath=$backupRoot.'/team.php';
$manifestPath=$backupRoot.'/manifest.json';

$raw=is_file($teamPath)?file_get_contents($teamPath):false;
$team=is_string($raw)?tsprb_lf($raw):'';

$installed=
    strpos($team,' * VERSION: v046')!==false &&
    strpos($team,"mrl:picks-saved")!==false &&
    strpos($team,'MRL SMART PICK REVIEW POST-SUBMIT BASELINE v001')!==false;

$checks=[
    'team.php exists'=>is_file($teamPath),
    'team.php is v045'=>strpos($team,' * VERSION: v045')!==false,
    'Smart Pick Review v002 present'=>strpos($team,'MRL SMART PICK REVIEW v002')!==false,
    'layout v002 present'=>strpos($team,'MRL SMART PICK REVIEW LAYOUT v002')!==false,
    'quiet-submit success call present'=>strpos($team,'showPickSuccess(form);')!==false,
    'review baseline map present'=>strpos($team,'var reviewBaselines = new WeakMap();')!==false,
    'driverMap helper present'=>strpos($team,'function driverMap(form)')!==false,
    'quiet-submit marker preserved'=>strpos($team,'X-MRL-Quiet-Submit')!==false,
    'Custom HTML handshake preserved'=>strpos($team,'function teampageResizeCustomHtmlFrame(frame)')!==false,
];

$ready=!in_array(false,$checks,true);
$rollbackAvailable=is_file($backupPath)&&is_file($manifestPath);
$action=(string)($_POST['action']??'preview');
$message='';
$ok=false;

if ($_SERVER['REQUEST_METHOD']==='POST' && $action==='apply') {
    if ($installed) {
        $ok=true;
        $message='Post-submit baseline fix is already installed.';
    } elseif (!$ready) {
        $message='Apply blocked because one or more preflight checks failed.';
    } else {
        try {
            if (!is_dir($backupRoot) && !mkdir($backupRoot,0755,true) && !is_dir($backupRoot)) {
                throw new RuntimeException('Could not create backup folder.');
            }
            if (!copy($teamPath,$backupPath)) {
                throw new RuntimeException('Could not back up team.php.');
            }

            $manifest=[
                'created_at'=>date('Y-m-d H:i:s'),
                'task'=>'team_smart_pick_review_postsubmit_baseline_v001',
                'source_sha256'=>hash_file('sha256',$teamPath),
                'source_version'=>'v045',
                'target_version'=>'v046',
            ];
            $json=json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            if (!is_string($json) || file_put_contents($manifestPath,$json.PHP_EOL,LOCK_EX)===false) {
                throw new RuntimeException('Could not write rollback manifest.');
            }

            $new=$team;

            $changed=0;
            $new=preg_replace(
                '/ \* VERSION: v045\n \* LAST MODIFIED: [^\n]+/',
                " * VERSION: v046\n * LAST MODIFIED: 8/31/2026 4:39:31 pm",
                $new,1,$changed
            );
            if ($changed!==1) {
                throw new RuntimeException('team.php v045 header was not found exactly once.');
            }

            $new=tsprb_once(
                $new,
                " * CHANGELOG:\n *\n",
                " * CHANGELOG:\n *\n"
                ." * v046 (8/31/2026 4:39:31 pm)\n"
                ." * - FIX: Smart Pick Review baseline now refreshes immediately after a confirmed quiet submission.\n"
                ." * - FIX: A second Submit without page refresh correctly reports no changes when picks were just saved.\n"
                ." * - PRESERVE: Existing Smart Pick Review layout, quiet-submit flow, SEG / LP / RD logic, and DB behavior.\n"
                ." *\n",
                'team changelog'
            );

            $new=tsprb_once(
                $new,
                "                showPickSuccess(form);\n",
                "                showPickSuccess(form);\n"
                ."                form.dispatchEvent(new CustomEvent('mrl:picks-saved'));\n",
                'quiet-submit success event hook'
            );

            $anchor = <<<'OLD'
        form.addEventListener('submit', function (event) {
OLD;

            $replacement = <<<'NEW'
        form.addEventListener('mrl:picks-saved', function () {
            /*
             * The quiet-submit layer emits this only after the server returns
             * the normal successful-submission marker. Refresh the in-memory
             * baseline so another immediate Submit compares against what was
             * just saved, not what existed when the page first loaded.
             */
            reviewBaselines.set(form, driverMap(form));
            removeReview(form);
        });

        form.addEventListener('submit', function (event) {
NEW;

            $new=tsprb_once($new,$anchor,$replacement,'review saved-event listener');

            $marker='/* MRL SMART PICK REVIEW LAYOUT v002 */';
            $new=tsprb_once(
                $new,
                $marker,
                "/* MRL SMART PICK REVIEW POST-SUBMIT BASELINE v001 */\n".$marker,
                'baseline fix marker'
            );

            $new=str_replace('8/31/2026 4:39:31 pm','8/31/2026 4:39:31 pm',$new);

            if (file_put_contents($teamPath,$new,LOCK_EX)===false) {
                throw new RuntimeException('Could not write updated team.php.');
            }

            $verify=tsprb_lf((string)file_get_contents($teamPath));
            $post=[
                'team.php v046 installed'=>strpos($verify,' * VERSION: v046')!==false,
                'baseline fix marker installed'=>strpos($verify,'MRL SMART PICK REVIEW POST-SUBMIT BASELINE v001')!==false,
                'success event emitted'=>strpos($verify,"form.dispatchEvent(new CustomEvent('mrl:picks-saved'));")!==false,
                'review listens for success event'=>strpos($verify,"form.addEventListener('mrl:picks-saved'")!==false,
                'review baseline refresh installed'=>strpos($verify,'reviewBaselines.set(form, driverMap(form));')!==false,
                'Smart Pick Review preserved'=>strpos($verify,'MRL SMART PICK REVIEW v002')!==false,
                'layout preserved'=>strpos($verify,'MRL SMART PICK REVIEW LAYOUT v002')!==false,
                'quiet submit preserved'=>strpos($verify,'X-MRL-Quiet-Submit')!==false,
                'Custom HTML handshake preserved'=>strpos($verify,'function teampageResizeCustomHtmlFrame(frame)')!==false,
            ];

            if (in_array(false,$post,true)) {
                @copy($backupPath,$teamPath);
                throw new RuntimeException('Postflight failed; exact v045 team.php restored.');
            }

            $checks=$post;
            $ok=true;
            $message='Post-submit baseline fix installed successfully.';
            $installed=true;
            $rollbackAvailable=true;
        } catch (Throwable $e) {
            $message=$e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $action==='rollback') {
    try {
        if (!is_file($backupPath)||!is_file($manifestPath)) {
            throw new RuntimeException('Rollback backup/manifest not found.');
        }
        if (!copy($backupPath,$teamPath)) {
            throw new RuntimeException('Could not restore v045 team.php.');
        }
        $ok=true;
        $message='ROLLBACK COMPLETE — exact v045 team.php restored.';
        $installed=false;
    } catch (Throwable $e) {
        $message='Rollback failed: '.$e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Smart Pick Review Post-Submit Baseline Fix</title>
<style>
:root{--bg:#101214;--panel:#1d2023;--border:#4b5055;--text:#f0f0f0;--gold:#efc77e;--green:#63e69a;--red:#ff7e7e}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1100px;margin:20px auto}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}
.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:800}
.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}
.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}
.info{background:#122a3a;border:1px solid #2d6a8c;color:#d8f2ff}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid #3a3e42;text-align:left}
th{color:#ffe0a0}
.pass{color:var(--green);font-weight:800}
.fail{color:var(--red);font-weight:800}
.btn{padding:11px 18px;border-radius:8px;font-weight:800;cursor:pointer;margin-right:10px}
.apply{background:#16894b;color:#fff;border:1px solid #4be388}
.rollback{background:#a32222;color:#fff;border:1px solid #ef6666}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>Smart Pick Review — Post-Submit Baseline Fix</h1>
<p><strong>Generated:</strong> 8/31/2026 4:39:31 pm America/New_York</p>

<?php if ($message!==''): ?>
<div class="banner <?php echo $ok?'ok':'bad'; ?>"><?php echo tsprb_h($message); ?></div>
<?php endif; ?>

<?php if (!$message && $installed): ?>
<div class="banner ok">POST-SUBMIT BASELINE FIX IS INSTALLED.</div>
<?php elseif (!$message && $ready): ?>
<div class="banner ok">PREVIEW PASS — ready to apply.</div>
<?php elseif (!$message): ?>
<div class="banner bad">PREVIEW BLOCKED — see failed checks below.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>What This Fixes</h2>
<div class="banner info">
After a confirmed quiet submission, Smart Pick Review immediately learns the newly saved picks. A second Submit on the same page therefore compares against the fresh saved state.
</div>
<p>No page refresh is required, and no database/submission logic is changed.</p>
</div>

<div class="card">
<h2>Preflight / Postflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($checks as $label=>$status): ?>
<tr>
<td><?php echo tsprb_h($label); ?></td>
<td class="<?php echo $status?'pass':'fail'; ?>"><?php echo $status?'PASS':'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Actions</h2>

<?php if ($ready && !$installed): ?>
<form method="post" style="display:inline" onsubmit="return confirm('Apply the post-submit Smart Pick Review baseline fix?');">
<input type="hidden" name="action" value="apply">
<button class="btn apply" type="submit">Apply Baseline Fix</button>
</form>
<?php endif; ?>

<?php if ($rollbackAvailable): ?>
<form method="post" style="display:inline" onsubmit="return confirm('ROLL BACK this fix and restore exact v045 team.php?');">
<input type="hidden" name="action" value="rollback">
<button class="btn rollback" type="submit">Rollback Baseline Fix</button>
</form>
<?php endif; ?>

</div>
</div>
</body>
</html>
