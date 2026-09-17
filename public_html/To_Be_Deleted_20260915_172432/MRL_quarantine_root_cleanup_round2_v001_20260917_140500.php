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
function mvfile($src,$dst,&$err){
    $err='';
    if(!is_file($src)){ $err='Source missing'; return false; }
    if(file_exists($dst)){ $err='Destination exists'; return false; }
    if(!is_dir(dirname($dst)) && !@mkdir(dirname($dst),0775,true)){ $err='Cannot create destination'; return false; }
    if(@rename($src,$dst)) return true;
    if(@copy($src,$dst) && @unlink($src)) return true;
    $err='Move failed'; return false;
}

$root=root_dir();
$qdir=jp($root,QUARANTINE_NAME);
$self=basename(__FILE__);
$inQ=(basename(norm(__DIR__))===QUARANTINE_NAME);

$targets=[
'admin_userid_0_postmigration_live_scan_v001_20260830_091028am.php',
'admin_userid_0_postmigration_live_scan_v002_20260830_091828am.php',
'admin_userid_0_to_999_code_scan_v001_20260830_070054am.php',
'admin_userid_0_to_999_discovery_v001_20260830_064716am.php',
'admin_userid_0_to_999_view_check_v001_20260830_081907am.php',
'MRL_highlighted_folder_usage_scan_v001_20260916_112500.php',
'MRL_highlighted_folder_usage_scan_v002_20260916_113500.php',
'MRL_historical_reorg_discovery_v001_20260915_194100.php',
'MRL_historical_reorg_discovery_v002_20260915_194900.php',
'MRL_house_cleaning_inventory_v002_20260915_045059pm.php',
'MRL_house_cleaning_inventory_v003_20260915_050250pm.php',
'MRL_house_cleaning_quarantine_v001_20260915_172432.php',
'MRL_house_cleaning_root_patterns_v001_20260915_174500.php',
'MRL_install_league_info_reorg_v001_20260916_011500.php',
'MRL_install_team_chart_stragglers_v001_20260916_010200.php',
'MRL_install_team_charts_reorg_v001_20260915_234500.php',
'MRL_install_team_league_info_defaults_v001_20260916_015000.php',
'MRL_install_wp_team_chart_links_v001_20260916_023500.php',
'MRL_league_info_duplicate_compare_v001_20260916_030500.php',
'MRL_quarantine_formtools_viewer_v001_20260917_015000.php',
'MRL_remove_league_info_root_duplicates_v001_20260916_031200.php',
'MRL_root_css_js_targeted_usage_scan_v001_20260917_015500.php',
'MRL_root_css_js_targeted_usage_scan_v002_20260917_020500.php',
'MRL_root_css_js_targeted_usage_scan_v003_20260917_021500.php',
'MRL_team_charts_stragglers_discovery_v001_20260916_000500.php',
'MRL_wp_team_chart_link_discovery_v001_20260916_020500.php',
'profile_redesign_v003_20260827_092430pm.php',
'refine_team_smart_pick_review_layout_v001_20260831_033500pm.php'
];

$action=$_POST['action']??'preview';
$result='';
$msg='';
$errors=[];
$stateFile=jp($qdir,'_MRL_root_cleanup_round2_v001_state.json');

if($action==='apply' && !$inQ){
    if(!is_dir($qdir) && !@mkdir($qdir,0775,true)){
        $result='FAIL'; $msg='Could not access quarantine folder.';
    } else {
        $moved=[];
        foreach($targets as $name){
            $src=jp($root,$name); $dst=jp($qdir,$name);
            if(!is_file($src)) continue;
            $err='';
            if(mvfile($src,$dst,$err)) $moved[]=$name;
            else $errors[]="$name: $err";
        }
        if(!$errors){
            $state=[
                'tool'=>$self,'version'=>TOOL_VERSION,'applied_at'=>date(DATE_ATOM),
                'root'=>$root,'quarantine'=>$qdir,'moved_files'=>$moved
            ];
            @file_put_contents($stateFile,json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL,LOCK_EX);

            $selfDst=jp($qdir,$self);
            $err='';
            if(mvfile(__FILE__,$selfDst,$err)){
                $result='PASS';
                $msg='Listed cleanup files moved to quarantine. Nothing deleted. This utility self-quarantined too.';
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
    if(!is_array($state) || empty($state['moved_files'])){
        $result='FAIL'; $msg='Rollback state missing or invalid.';
    } else {
        foreach($state['moved_files'] as $name){
            $src=jp($qdir,$name); $dst=jp($root,$name);
            if(!is_file($src)) continue;
            if(file_exists($dst)){ $errors[]="$name: root destination exists"; continue; }
            $err='';
            if(!mvfile($src,$dst,$err)) $errors[]="$name: $err";
        }
        $result=$errors?'FAIL':'PASS';
        $msg=$errors?'Rollback completed with errors.':'Rollback completed. Utility itself remains quarantined.';
    }
}

$rows=[];$ready=0;$qcount=0;$missing=0;
foreach($targets as $name){
    $src=jp($root,$name);$dst=jp($qdir,$name);
    if(is_file($src)){ $status='READY';$detail='Present in public_html';$ready++; }
    elseif(is_file($dst)){ $status='QUARANTINED';$detail='Present in quarantine';$qcount++; }
    else { $status='NOT FOUND';$detail='Skipped';$missing++; }
    $rows[]=[$name,$status,$detail];
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Root Cleanup Round 2 v001</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#191e1c;--line:#46514c;--text:#f1efe9;--muted:#b8bab5;--gold:#f1c97f}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px Tahoma,Verdana,sans-serif}
.wrap{width:min(1120px,96%);margin:14px auto 30px}h1,h2{color:var(--gold)}h1{font-size:27px;margin:0 0 10px}h2{font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--line);border-radius:11px;background:var(--panel)}
.notice{padding:11px 13px;border-radius:9px;margin-bottom:10px;font-weight:700}.info{background:#172833;border:1px solid #4c6575}
.pass{background:#103b27;border:1px solid #2f9a61}.fail{background:#431b1b;border:1px solid #a34d4d}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.card{padding:10px;border:1px solid var(--line);border-radius:9px}
.card b{display:block;font-size:20px}.small{font-size:12px;color:var(--muted)}
.btn{display:inline-block;padding:9px 14px;border:0;border-radius:7px;color:white;font-weight:700;text-decoration:none;cursor:pointer}
.apply{background:#26834f}.rollback{background:#aa3f3f}button[disabled]{background:#555;color:#999}
table{width:100%;border-collapse:collapse}th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left}
th{color:var(--gold);background:#202624}code{color:#f8d89a}.sREADY{color:#58aee8;font-weight:700}
.sQUARANTINED{color:#8fe0a9;font-weight:700}.sNOTFOUND{color:#ffd391;font-weight:700}
</style></head><body><div class="wrap">
<h1>MRL Root Cleanup — Round 2 v001</h1>
<div class="notice info">QUARANTINE ONLY — successful Apply also self-quarantines this utility.</div>

<?php if($result): ?><div class="notice <?php echo strtolower($result); ?>"><?php echo h($result.' — '.$msg); ?></div><?php endif; ?>

<div class="panel">
<h2>Preflight / Result</h2>
<div class="grid">
<div class="card"><span class="small">Still in public_html</span><b><?php echo $ready; ?></b></div>
<div class="card"><span class="small">Quarantined</span><b><?php echo $qcount; ?></b></div>
<div class="card"><span class="small">Not found / skipped</span><b><?php echo $missing; ?></b></div>
</div><br>
<?php if(!$inQ): ?>
<form method="post"><input type="hidden" name="action" value="apply">
<button class="btn apply" type="submit" <?php echo $ready===0?'disabled':''; ?>>Quarantine Listed Files + Self</button>
</form>
<?php else: ?>
<form method="post"><input type="hidden" name="action" value="rollback">
<button class="btn rollback" type="submit">Rollback Recorded Moves</button>
</form>
<?php endif; ?>
<p class="small">Destination: <code>/<?php echo h(QUARANTINE_NAME); ?>/</code>. Missing files are non-blocking.</p>
</div>

<div class="panel"><h2>Exact target list</h2>
<table><tr><th>File</th><th>Status</th><th>Detail</th></tr>
<?php foreach($rows as $r): $cls='s'.str_replace(' ','',$r[1]); ?>
<tr><td><code><?php echo h($r[0]); ?></code></td><td class="<?php echo h($cls); ?>"><?php echo h($r[1]); ?></td><td><?php echo h($r[2]); ?></td></tr>
<?php endforeach; ?>
</table></div>

<?php if($errors): ?><div class="panel"><h2>Errors</h2><?php foreach($errors as $e): ?><div><?php echo h($e); ?></div><?php endforeach; ?></div><?php endif; ?>

<div class="panel small">FILE: <?php echo h($self); ?> | VERSION: v001 | CREATED: 9/17/2026 2:05:00 pm EDT</div>
</div></body></html>
