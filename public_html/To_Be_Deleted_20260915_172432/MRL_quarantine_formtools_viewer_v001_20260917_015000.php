<?php
declare(strict_types=1);

date_default_timezone_set('America/New_York');

const VERSION = 'v001';
const QUARANTINE = 'To_Be_Deleted_20260915_172432';
const STATE = '_mrl_installer_backups/quarantine_formtools_viewer_20260917_015000/state.json';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
if (($r = realpath($root)) !== false) $root = $r;

$targets = ['formtools','viewer'];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pjoin(string $a,string $b): string { return rtrim($a,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$b); }
function ensure_dir(string $d): bool { return is_dir($d) || @mkdir($d,0755,true) || is_dir($d); }
function load_state(string $root): ?array {
    $p=pjoin($root,STATE);
    if(!is_file($p)) return null;
    $j=@file_get_contents($p);
    $d=$j===false?null:json_decode($j,true);
    return is_array($d)?$d:null;
}
function save_state(string $root,array $data): bool {
    $p=pjoin($root,STATE);
    if(!ensure_dir(dirname($p))) return false;
    return @file_put_contents($p,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n",LOCK_EX)!==false;
}
function preflight(string $root,array $targets): array {
    $rows=[]; $ok=true; $q=pjoin($root,QUARANTINE);
    if(is_dir($q)) $rows[]=['Quarantine folder','PASS','/'.QUARANTINE.'/ exists.'];
    else { $rows[]=['Quarantine folder','FAIL','/'.QUARANTINE.'/ is missing.']; $ok=false; }

    foreach($targets as $t){
        $src=pjoin($root,$t); $dst=pjoin($q,$t);
        if(is_dir($src)) $rows[]=['/'.$t.'/ source','PASS','Source folder exists.'];
        else { $rows[]=['/'.$t.'/ source','FAIL','Source folder is missing.']; $ok=false; }

        if(!file_exists($dst)) $rows[]=['/'.$t.'/ destination','PASS','Destination is clear.'];
        else { $rows[]=['/'.$t.'/ destination','FAIL','Destination already exists in quarantine.']; $ok=false; }
    }

    if(load_state($root)){ $rows[]=['Existing state','INFO','Prior state exists; use Rollback.']; $ok=false; }
    else $rows[]=['Existing state','PASS','No prior state file.'];

    return ['ok'=>$ok,'rows'=>$rows];
}
function install_move(string $root,array $targets): array {
    $pre=preflight($root,$targets);
    if(!$pre['ok']) return ['ok'=>false,'message'=>'Quarantine blocked: preflight is not fully green.','errors'=>[]];

    $q=pjoin($root,QUARANTINE); $moved=[]; $errors=[];
    foreach($targets as $t){
        if(!@rename(pjoin($root,$t),pjoin($q,$t))){
            $errors[]='Could not move /'.$t.'/';
            break;
        }
        $moved[]=$t;
    }

    if($errors){
        foreach(array_reverse($moved) as $t) @rename(pjoin($q,$t),pjoin($root,$t));
        return ['ok'=>false,'message'=>'Move failed; already-moved folders were restored.','errors'=>$errors];
    }

    if(!save_state($root,['installed_at'=>date(DATE_ATOM),'targets'=>$targets,'quarantine'=>QUARANTINE])){
        foreach(array_reverse($moved) as $t) @rename(pjoin($q,$t),pjoin($root,$t));
        return ['ok'=>false,'message'=>'State write failed; folders were restored.','errors'=>[]];
    }

    return ['ok'=>true,'message'=>'formtools and viewer moved to quarantine. Nothing deleted.','errors'=>[]];
}
function rollback_move(string $root,array $targets): array {
    if(!load_state($root)) return ['ok'=>false,'message'=>'Rollback state not found.','errors'=>[]];

    $q=pjoin($root,QUARANTINE); $errors=[];
    foreach($targets as $t){
        $src=pjoin($q,$t); $dst=pjoin($root,$t);
        if(!is_dir($src)){ $errors[]='Quarantined /'.$t.'/ is missing.'; continue; }
        if(file_exists($dst)){ $errors[]='Cannot restore /'.$t.'/ because root destination exists.'; continue; }
        if(!@rename($src,$dst)) $errors[]='Could not restore /'.$t.'/';
    }
    if(!$errors){
        @unlink(pjoin($root,STATE));
        return ['ok'=>true,'message'=>'Rollback complete; both folders restored.','errors'=>[]];
    }
    return ['ok'=>false,'message'=>'Rollback incomplete.','errors'=>$errors];
}
function postflight(string $root,array $targets): array {
    $q=pjoin($root,QUARANTINE); $rows=[]; $ok=true;
    foreach($targets as $t){
        $rootGone=!is_dir(pjoin($root,$t));
        $qPresent=is_dir(pjoin($q,$t));
        $pass=$rootGone && $qPresent;
        if(!$pass) $ok=false;
        $rows[]=['/'.$t.'/ quarantined',$pass?'PASS':'FAIL',($rootGone?'root absent':'root still present').'; '.($qPresent?'quarantine present':'quarantine missing').'.'];
    }
    return ['ok'=>$ok,'rows'=>$rows];
}

$action=(string)($_POST['action']??'');
$result=null;
if($action==='install'){
    $result=(($_POST['confirm']??'')==='yes')
        ? install_move($root,$targets)
        : ['ok'=>false,'message'=>'Not started: confirmation box was not checked.','errors'=>[]];
} elseif($action==='rollback'){
    $result=rollback_move($root,$targets);
}

$state=load_state($root);
$pre=$state?null:preflight($root,$targets);
$post=$state?postflight($root,$targets):null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Quarantine formtools + viewer</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,96%);margin:14px auto 30px}h1{margin:0 0 10px;color:var(--gold);font-size:27px}h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.banner{padding:11px 13px;margin-bottom:11px;border:1px solid #3f8bc2;border-radius:10px;background:#15354d;color:#e8f5ff;font-weight:800}
table{width:100%;border-collapse:collapse}th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}th{color:var(--gold)}
.status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800}.pass{background:#17613a;border:1px solid #55db8b}.fail{background:#5b2323;border:1px solid #e77a7a}.info{background:#4a3813;border:1px solid #d8aa49}
.actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}button{min-height:38px;padding:8px 14px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}.install{background:#2f7f53}.rollback{background:#b46d22}.refresh{background:#2c6f9e}button:disabled{background:#5a5f5d;color:#b9b9b9;cursor:not-allowed}.small{font-size:12px;color:var(--muted)}
.result-ok{border-color:#2f9a61;background:#103b27}.result-bad{border-color:#a65353;background:#3d1d1d}code{color:#f8d89a}
</style>
</head>
<body><div class="wrap">
<h1>MRL Quarantine formtools + viewer</h1>
<div class="banner">Moves only <code>/formtools/</code> and <code>/viewer/</code> into <code>/<?php echo h(QUARANTINE); ?>/</code>. Nothing is deleted.</div>

<?php if($result): ?>
<div class="panel <?php echo $result['ok']?'result-ok':'result-bad'; ?>"><h2>Result</h2>
<p><strong><?php echo $result['ok']?'PASS':'ATTENTION'; ?></strong> — <?php echo h($result['message']); ?></p>
<?php if($result['errors']): ?><ul><?php foreach($result['errors'] as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>
<?php endif; ?>

<div class="panel"><h2><?php echo $state?'Postflight / Result':'Preflight / Result'; ?></h2>
<table><tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php $rows=$state?$post['rows']:$pre['rows']; foreach($rows as $r): $c=$r[1]==='PASS'?'pass':($r[1]==='FAIL'?'fail':'info'); ?>
<tr><td><?php echo h($r[0]); ?></td><td><span class="status <?php echo $c; ?>"><?php echo h($r[1]); ?></span></td><td><?php echo h($r[2]); ?></td></tr>
<?php endforeach; ?>
</table></div>

<div class="panel"><h2>Action</h2>
<?php if($state): ?>
<form method="post"><input type="hidden" name="action" value="rollback"><div class="actions">
<button class="rollback" type="submit">Rollback Quarantine</button>
<button class="refresh" type="button" onclick="location.reload()">Refresh Postflight</button>
</div></form>
<?php else: ?>
<form method="post"><input type="hidden" name="action" value="install">
<label><input type="checkbox" name="confirm" value="yes" id="confirm"> I reviewed the green preflight and want to quarantine these two folders.</label>
<div class="actions" style="margin-top:10px">
<button class="install" id="go" type="submit" disabled>Quarantine formtools + viewer</button>
<button class="refresh" type="button" onclick="location.reload()">Refresh Preflight</button>
</div>
<p class="small"><?php echo $pre['ok']?'Preflight is green. Check the box to enable quarantine.':'Quarantine is blocked until preflight is green.'; ?></p>
</form>
<?php endif; ?>
</div>

<div class="panel small">No deletes. No database writes. Rollback moves the folders back exactly.</div>
<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/17/2026 1:50:00 am EDT</div>
</div>
<script>
(function(){var c=document.getElementById('confirm'),b=document.getElementById('go'),ok=<?php echo (!$state && $pre && $pre['ok'])?'true':'false'; ?>;
function s(){if(b)b.disabled=!(ok&&c&&c.checked)}if(c)c.addEventListener('change',s);s();})();
</script>
</body></html>
