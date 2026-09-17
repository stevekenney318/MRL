<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Remove Reintroduced League Info Root Duplicates
 *
 * VERSION: v001
 * CREATED: 9/16/2026 3:12:00 am EDT
 *
 * PURPOSE
 * -------
 * Remove only the 11 verified duplicate root-level League Information files
 * that were reintroduced after the successful /league_info/ relocation.
 *
 * SAFETY
 * ------
 * - Re-verifies every root copy against /league_info/ before enabling removal.
 * - Accepts byte-identical pairs or relocation-only PHP differences.
 * - Creates an exact backup of every root duplicate before deletion.
 * - Deletes only the verified root copy; /league_info/ is never modified.
 * - Rollback restores the deleted root copies exactly.
 */

date_default_timezone_set('America/New_York');

const MRL_VERSION = 'v001';
const BACKUP_DIR = '_mrl_installer_backups/league_info_root_duplicate_removal_20260916_031200';
const STATE_FILE = BACKUP_DIR . '/_state.json';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$files = [
    '2019_rules.docx',
    '2019_rules.htm',
    '2020_rules.htm',
    '2024_Fees.php',
    '2024_Rules.php',
    '2024_Schedule.html',
    '2024_Schedule.php',
    '2025_Fees.php',
    '2025_Rules.php',
    '2026_Fees.php',
    '2026_Rules.php',
];

$expectedRootDeps = [
    '2024_Fees.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2024_Rules.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2024_Schedule.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2025_Fees.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2025_Rules.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2026_Rules.php' => ['header.php'],
];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pjoin(string $a,string $b): string { return rtrim($a,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$b); }
function ensure_dir(string $dir): bool { return is_dir($dir) || @mkdir($dir,0755,true) || is_dir($dir); }
function atomic_write(string $path,string $content): bool {
    if(!ensure_dir(dirname($path))) return false;
    $tmp=$path.'.tmp_'.bin2hex(random_bytes(4));
    if(@file_put_contents($tmp,$content,LOCK_EX)===false) return false;
    if(!@rename($tmp,$path)){ @unlink($tmp); return false; }
    return true;
}
function normalize_line_endings(string $s): string { return str_replace(["\r\n","\r"],"\n",$s); }

function normalize_relocation_repairs(string $content,array $deps): string {
    $content=normalize_line_endings($content);
    foreach($deps as $dep){
        $q=preg_quote($dep,'/');
        $patterns=[
            '/\b(include|include_once|require|require_once)\s+dirname\s*\(\s*__DIR__\s*\)\s*\.\s*[\'"]\/'.$q.'[\'"]\s*;?/i',
            '/\b(include|include_once|require|require_once)\s+[\'"]'.$q.'[\'"]\s*;?/i',
            '/\b(include|include_once|require|require_once)\s*\(\s*[\'"]'.$q.'[\'"]\s*\)\s*;?/i'
        ];
        foreach($patterns as $pattern){
            $content=preg_replace_callback($pattern,function($m) use($dep){
                return strtolower($m[1])." '".$dep."';";
            },$content);
        }
    }
    return $content;
}

function compare_pair(string $root,string $name,array $depsMap): array {
    $a=pjoin($root,$name);
    $b=pjoin($root,'league_info/'.$name);

    if(!is_file($a)) return ['ok'=>false,'class'=>'FAIL','detail'=>'Root copy is missing.'];
    if(!is_file($b)) return ['ok'=>false,'class'=>'FAIL','detail'=>'/league_info/ copy is missing.'];

    $ac=@file_get_contents($a); $bc=@file_get_contents($b);
    if($ac===false || $bc===false) return ['ok'=>false,'class'=>'FAIL','detail'=>'Could not read one or both copies.'];

    if(hash('sha256',$ac)===hash('sha256',$bc)){
        return ['ok'=>true,'class'=>'IDENTICAL','detail'=>'Byte-for-byte identical.'];
    }

    $deps=$depsMap[$name]??[];
    if($deps){
        if(normalize_relocation_repairs($ac,$deps)===normalize_relocation_repairs($bc,$deps)){
            return ['ok'=>true,'class'=>'RELOCATION-ONLY','detail'=>'Differs only by expected relocation dependency repairs.'];
        }
    }

    return ['ok'=>false,'class'=>'FAIL','detail'=>'Unexpected content differences remain.'];
}

function state_path(string $root): string { return pjoin($root,STATE_FILE); }
function load_state(string $root): ?array {
    $f=state_path($root);
    if(!is_file($f)) return null;
    $raw=@file_get_contents($f);
    if($raw===false) return null;
    $d=json_decode($raw,true);
    return is_array($d)?$d:null;
}
function save_state(string $root,array $state): bool {
    $json=json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    return $json!==false && atomic_write(state_path($root),$json."\n");
}

function preflight(string $root,array $files,array $depsMap): array {
    $rows=[]; $ok=true; $safe=0;

    foreach($files as $name){
        $c=compare_pair($root,$name,$depsMap);
        if($c['ok']) $safe++; else $ok=false;
        $rows[]=[
            'check'=>$name,
            'status'=>$c['ok']?'PASS':'FAIL',
            'detail'=>$c['class'].' — '.$c['detail']
        ];
    }

    $state=load_state($root);
    if($state) $ok=false;

    $rows[]=[
        'check'=>'Safe root removals',
        'status'=>$safe===count($files)?'PASS':'FAIL',
        'detail'=>$safe.' of '.count($files).' root duplicates re-verify safe to remove.'
    ];

    $rows[]=[
        'check'=>'Backup destination',
        'status'=>$state?'INFO':'PASS',
        'detail'=>$state?'Prior removal state exists; use Rollback.':BACKUP_DIR.' is available.'
    ];

    return ['ok'=>$ok && $safe===count($files),'rows'=>$rows];
}

function install_remove(string $root,array $files,array $depsMap): array {
    $pre=preflight($root,$files,$depsMap);
    if(!$pre['ok']) return ['ok'=>false,'message'=>'Removal blocked: preflight is not fully green.','errors'=>[]];

    $errors=[];
    $backupRows=[];

    foreach($files as $name){
        $src=pjoin($root,$name);
        $bak=pjoin($root,BACKUP_DIR.'/files/'.$name);

        if(!ensure_dir(dirname($bak)) || !@copy($src,$bak)){
            $errors[]='Backup failed for '.$name;
            break;
        }

        $backupRows[$name]=[
            'mtime'=>(int)@filemtime($src),
            'sha256'=>hash_file('sha256',$src)
        ];
    }

    if($errors) return ['ok'=>false,'message'=>'Stopped before deletion because backup creation failed.','errors'=>$errors];

    foreach($files as $name){
        $src=pjoin($root,$name);
        if(!@unlink($src)){
            $errors[]='Could not remove root duplicate '.$name;
            break;
        }
    }

    if($errors){
        foreach($files as $name){
            $bak=pjoin($root,BACKUP_DIR.'/files/'.$name);
            $dst=pjoin($root,$name);
            if(is_file($bak) && !is_file($dst)){
                @copy($bak,$dst);
                if(isset($backupRows[$name]['mtime'])) @touch($dst,(int)$backupRows[$name]['mtime']);
            }
        }
        return ['ok'=>false,'message'=>'Deletion failed; automatic restore was attempted.','errors'=>$errors];
    }

    $state=[
        'installed_at'=>date(DATE_ATOM),
        'removed_files'=>$files,
        'backup_rows'=>$backupRows
    ];

    if(!save_state($root,$state)){
        foreach($files as $name){
            $bak=pjoin($root,BACKUP_DIR.'/files/'.$name);
            $dst=pjoin($root,$name);
            if(is_file($bak) && !is_file($dst)){
                @copy($bak,$dst);
                if(isset($backupRows[$name]['mtime'])) @touch($dst,(int)$backupRows[$name]['mtime']);
            }
        }
        return ['ok'=>false,'message'=>'State-file write failed; root copies were restored.','errors'=>[]];
    }

    return ['ok'=>true,'message'=>'All 11 verified root duplicates removed. /league_info/ was untouched.','errors'=>[]];
}

function rollback_remove(string $root,array $files): array {
    $state=load_state($root);
    if(!$state) return ['ok'=>false,'message'=>'Rollback state not found.','errors'=>[]];

    $errors=[];

    foreach($files as $name){
        $bak=pjoin($root,BACKUP_DIR.'/files/'.$name);
        $dst=pjoin($root,$name);

        if(!is_file($bak)){
            $errors[]='Backup missing for '.$name;
            continue;
        }

        if(!@copy($bak,$dst)){
            $errors[]='Could not restore '.$name;
            continue;
        }

        $mtime=$state['backup_rows'][$name]['mtime']??null;
        if($mtime) @touch($dst,(int)$mtime);
    }

    if(!$errors){
        @unlink(state_path($root));
        return ['ok'=>true,'message'=>'Rollback completed; all 11 root duplicates restored.','errors'=>[]];
    }

    return ['ok'=>false,'message'=>'Rollback incomplete.','errors'=>$errors];
}

function postflight(string $root,array $files): array {
    $rows=[]; $rootGone=0; $leaguePresent=0;

    foreach($files as $name){
        if(!is_file(pjoin($root,$name))) $rootGone++;
        if(is_file(pjoin($root,'league_info/'.$name))) $leaguePresent++;
    }

    $rows[]=[
        'check'=>'Root duplicates removed',
        'status'=>$rootGone===count($files)?'PASS':'FAIL',
        'detail'=>$rootGone.' of '.count($files).' root copies are absent.'
    ];

    $rows[]=[
        'check'=>'/league_info/ preserved',
        'status'=>$leaguePresent===count($files)?'PASS':'FAIL',
        'detail'=>$leaguePresent.' of '.count($files).' relocated copies remain present.'
    ];

    return ['ok'=>$rootGone===count($files)&&$leaguePresent===count($files),'rows'=>$rows];
}

$action=(string)($_POST['action']??'');
$result=null;

if($action==='install'){
    if(($_POST['confirm_install']??'')!=='yes'){
        $result=['ok'=>false,'message'=>'Removal not started: confirmation box was not checked.','errors'=>[]];
    } else {
        $result=install_remove($root,$files,$expectedRootDeps);
    }
} elseif($action==='rollback'){
    $result=rollback_remove($root,$files);
}

$state=load_state($root);
$pre=$state?null:preflight($root,$files,$expectedRootDeps);
$post=$state?postflight($root,$files):null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Remove League Info Root Duplicates</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.banner{padding:11px 13px;margin-bottom:11px;border:1px solid #3f8bc2;border-radius:10px;background:#15354d;color:#e8f5ff;font-weight:800}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
.status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
.pass{background:#17613a;border:1px solid #55db8b;color:#e8fff1}
.info{background:#4a3813;border:1px solid #d8aa49;color:#ffe6a7}
.fail{background:#5b2323;border:1px solid #e77a7a;color:#ffe4e4}
.actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
button{min-height:38px;padding:8px 14px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.install{background:#2f7f53}.rollback{background:#b46d22}.refresh{background:#2c6f9e}
button:disabled{background:#5a5f5d;color:#b9b9b9;cursor:not-allowed;opacity:.7}
.confirm{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid #5a6a62;border-radius:8px;background:#151a18}
.result-ok{border-color:#2f9a61;background:#103b27}.result-bad{border-color:#a65353;background:#3d1d1d}
.small{font-size:12px;color:var(--muted)}
code{color:#f8d89a}
</style>
</head>
<body><div class="wrap">

<h1>MRL Remove League Info Root Duplicates</h1>

<div class="banner">
Removes only the 11 root-level duplicates that re-verify as identical or relocation-only copies.
The <code>/league_info/</code> files are never modified.
</div>

<?php if($result): ?>
<div class="panel <?php echo !empty($result['ok'])?'result-ok':'result-bad'; ?>">
<h2>Result</h2>
<p><strong><?php echo !empty($result['ok'])?'PASS':'ATTENTION'; ?></strong> — <?php echo h($result['message']); ?></p>
<?php if(!empty($result['errors'])): ?><ul><?php foreach($result['errors'] as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>
<?php endif; ?>

<div class="panel">
<h2><?php echo $state?'Postflight / Result':'Preflight / Result'; ?></h2>
<table><tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php
$rows=$state?$post['rows']:$pre['rows'];
foreach($rows as $r):
$cls=$r['status']==='PASS'?'pass':($r['status']==='FAIL'?'fail':'info');
?>
<tr><td><?php echo h($r['check']); ?></td><td><span class="status <?php echo h($cls); ?>"><?php echo h($r['status']); ?></span></td><td><?php echo h($r['detail']); ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Action</h2>
<?php if($state): ?>
<p>All 11 verified root duplicates have been removed. Rollback restores them exactly.</p>
<form method="post">
<input type="hidden" name="action" value="rollback">
<div class="actions">
<button class="rollback" type="submit">Rollback Root Duplicate Removal</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Postflight</button>
</div>
</form>
<?php else: ?>
<form method="post">
<input type="hidden" name="action" value="install">
<label class="confirm">
<input type="checkbox" name="confirm_install" value="yes" id="confirmInstall">
I reviewed the green preflight and want to remove these 11 verified root duplicates.
</label>
<div class="actions" style="margin-top:10px">
<button class="install" type="submit" id="installButton" disabled>Remove Verified Root Duplicates</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Preflight</button>
</div>
<p class="small"><?php echo $pre['ok']?'Preflight is green. Check the confirmation box to enable removal.':'Removal is blocked because one or more preflight checks failed.'; ?></p>
</form>
<?php endif; ?>
</div>

<div class="panel small">
Backup: <code><?php echo h(BACKUP_DIR); ?>/files/</code><br>
No database writes. No <code>/league_info/</code> writes.
</div>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 3:12:00 am EDT
</div>

</div>
<script>
(function(){
'use strict';
var c=document.getElementById('confirmInstall');
var b=document.getElementById('installButton');
var ok=<?php echo (!$state && $pre && $pre['ok'])?'true':'false'; ?>;
function sync(){ if(b) b.disabled=!(ok && c && c.checked); }
if(c) c.addEventListener('change',sync);
sync();
}());
</script>
</body>
</html>
