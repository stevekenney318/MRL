<?php
declare(strict_types=1);

date_default_timezone_set('America/New_York');
@ini_set('display_errors','1');
error_reporting(E_ALL);

const TOOL_VERSION='v001';
const QUARANTINE_NAME='To_Be_Deleted_20260915_172432';

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function norm($p){return rtrim(str_replace('\\','/',$p),'/');}
function root_dir(){
    $here=norm(__DIR__);
    return basename($here)===QUARANTINE_NAME ? norm(dirname($here)) : $here;
}
function jp($a,$b){return rtrim($a,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$b);}

function move_path($src,$dst,&$err){
    $err='';
    if(!file_exists($src)){ $err='Source missing'; return false; }
    if(file_exists($dst)){ $err='Destination exists'; return false; }
    if(!is_dir(dirname($dst)) && !@mkdir(dirname($dst),0775,true)){
        $err='Cannot create destination'; return false;
    }
    if(@rename($src,$dst)) return true;
    $err='Move failed'; return false;
}

$root=root_dir();
$qdir=jp($root,QUARANTINE_NAME);
$self=basename(__FILE__);
$inQ=(basename(norm(__DIR__))===QUARANTINE_NAME);

$targets=[
    ['type'=>'dir','name'=>'mrl_live_prior_year_simple_connection_backup_20260821_103000pm'],
    ['type'=>'dir','name'=>'mrl_pick_window_messaging_backup_20260820_150437'],
    ['type'=>'dir','name'=>'mrl_team_user_menu_backup_20260820_201542'],
    ['type'=>'dir','name'=>'mrl_team_user_menu_backup_20260820_203534'],

    ['type'=>'file','name'=>'admin_points_sandbox SAVE 20260217-0155.php'],
    ['type'=>'file','name'=>'admin_post_submission_audit_v001_20260831_023817am.php'],
    ['type'=>'file','name'=>'admin_post_submission_audit_v002_20260831_024708am.php'],

    ['type'=>'file','name'=>'profile_redesign.php'],
    ['type'=>'file','name'=>'raceimages_v001_20260710_014500am.html'],
];

$action=$_POST['action']??'preview';
$result='';
$msg='';
$errors=[];
$stateFile=jp($qdir,'_MRL_root_cleanup_round3_v001_state.json');

if($action==='apply' && !$inQ){
    if(!is_dir($qdir) && !@mkdir($qdir,0775,true)){
        $result='FAIL'; $msg='Could not access quarantine folder.';
    } else {
        $moved=[];
        foreach($targets as $t){
            $name=$t['name'];
            $src=jp($root,$name);
            $dst=jp($qdir,$name);

            if(!file_exists($src)) continue;

            $err='';
            if(move_path($src,$dst,$err)){
                $moved[]=$t;
            } else {
                $errors[]="$name: $err";
            }
        }

        if(!$errors){
            $state=[
                'tool'=>$self,
                'version'=>TOOL_VERSION,
                'applied_at'=>date(DATE_ATOM),
                'root'=>$root,
                'quarantine'=>$qdir,
                'moved_items'=>$moved
            ];
            @file_put_contents(
                $stateFile,
                json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL,
                LOCK_EX
            );

            $selfDst=jp($qdir,$self);
            $err='';
            if(move_path(__FILE__,$selfDst,$err)){
                $result='PASS';
                $msg='Selected files/folders moved to quarantine. Nothing deleted. This utility self-quarantined too.';
            } else {
                $result='FAIL';
                $msg='Targets moved, but this utility could not self-quarantine: '.$err;
            }
        } else {
            $result='FAIL';
            $msg='One or more target moves failed.';
        }
    }
}

if($action==='rollback' && $inQ){
    $state=is_file($stateFile)?json_decode((string)file_get_contents($stateFile),true):null;

    if(!is_array($state) || empty($state['moved_items']) || !is_array($state['moved_items'])){
        $result='FAIL';
        $msg='Rollback state missing or invalid.';
    } else {
        foreach($state['moved_items'] as $t){
            $name=$t['name']??'';
            if($name==='') continue;

            $src=jp($qdir,$name);
            $dst=jp($root,$name);

            if(!file_exists($src)) continue;
            if(file_exists($dst)){
                $errors[]="$name: root destination exists";
                continue;
            }

            $err='';
            if(!move_path($src,$dst,$err)){
                $errors[]="$name: $err";
            }
        }

        $result=$errors?'FAIL':'PASS';
        $msg=$errors
            ? 'Rollback completed with one or more errors.'
            : 'Rollback completed. Utility itself remains quarantined.';
    }
}

$rows=[];
$ready=0;$qcount=0;$missing=0;

foreach($targets as $t){
    $name=$t['name'];
    $src=jp($root,$name);
    $dst=jp($qdir,$name);

    if(file_exists($src)){
        $status='READY';
        $detail='Present in public_html';
        $ready++;
    } elseif(file_exists($dst)){
        $status='QUARANTINED';
        $detail='Present in quarantine';
        $qcount++;
    } else {
        $status='NOT FOUND';
        $detail='Skipped';
        $missing++;
    }

    $rows[]=[$t['type'],$name,$status,$detail];
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Root Cleanup Round 3 v001</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#191e1c;--line:#46514c;--text:#f1efe9;--muted:#b8bab5;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:14px Tahoma,Verdana,sans-serif}
.wrap{width:min(1120px,96%);margin:14px auto 30px}
h1,h2{color:var(--gold)}h1{font-size:27px;margin:0 0 10px}h2{font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--line);border-radius:11px;background:var(--panel)}
.notice{padding:11px 13px;border-radius:9px;margin-bottom:10px;font-weight:700}
.info{background:#172833;border:1px solid #4c6575}
.pass{background:#103b27;border:1px solid #2f9a61}
.fail{background:#431b1b;border:1px solid #a34d4d}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}
.card{padding:10px;border:1px solid var(--line);border-radius:9px}
.card b{display:block;font-size:20px}
.small{font-size:12px;color:var(--muted)}
.btn{display:inline-block;padding:9px 14px;border:0;border-radius:7px;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}
.apply{background:#26834f}.rollback{background:#aa3f3f}
button[disabled]{background:#555;color:#999;cursor:not-allowed}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{color:var(--gold);background:#202624}
code{color:#f8d89a}
.sREADY{color:#58aee8;font-weight:700}
.sQUARANTINED{color:#8fe0a9;font-weight:700}
.sNOTFOUND{color:#ffd391;font-weight:700}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL Root Cleanup — Round 3 v001</h1>

<div class="notice info">
QUARANTINE ONLY — successful Apply also self-quarantines this utility.
</div>

<?php if($result): ?>
<div class="notice <?php echo strtolower($result); ?>">
<?php echo h($result.' — '.$msg); ?>
</div>
<?php endif; ?>

<div class="panel">
<h2>Preflight / Result</h2>
<div class="grid">
<div class="card"><span class="small">Still in public_html</span><b><?php echo $ready; ?></b></div>
<div class="card"><span class="small">Quarantined</span><b><?php echo $qcount; ?></b></div>
<div class="card"><span class="small">Not found / skipped</span><b><?php echo $missing; ?></b></div>
</div>
<br>

<?php if(!$inQ): ?>
<form method="post">
<input type="hidden" name="action" value="apply">
<button class="btn apply" type="submit" <?php echo $ready===0?'disabled':''; ?>>
Quarantine Listed Items + Self
</button>
</form>
<?php else: ?>
<form method="post">
<input type="hidden" name="action" value="rollback">
<button class="btn rollback" type="submit">Rollback Recorded Moves</button>
</form>
<?php endif; ?>

<p class="small">
Destination: <code>/<?php echo h(QUARANTINE_NAME); ?>/</code>.
Missing items are non-blocking.
</p>
</div>

<div class="panel">
<h2>Exact target list</h2>
<table>
<tr><th>Type</th><th>Item</th><th>Status</th><th>Detail</th></tr>
<?php foreach($rows as $r):
    $cls='s'.str_replace(' ','',$r[2]); ?>
<tr>
<td><?php echo h(strtoupper($r[0])); ?></td>
<td><code><?php echo h($r[1]); ?></code></td>
<td class="<?php echo h($cls); ?>"><?php echo h($r[2]); ?></td>
<td><?php echo h($r[3]); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<?php if($errors): ?>
<div class="panel">
<h2>Errors</h2>
<?php foreach($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel small">
FILE: <?php echo h($self); ?> |
VERSION: v001 |
CREATED: 9/17/2026 1:58:00 pm EDT
</div>

</div>
</body>
</html>
