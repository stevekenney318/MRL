<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Team Chart Stragglers Add-On Installer
 *
 * VERSION: v001
 * CREATED: 9/16/2026 1:02:00 am EDT
 *
 * PURPOSE
 * -------
 * Move six remaining year-chart stragglers from public_html root into
 * /team_charts/ and repair their move-sensitive root dependencies.
 *
 * SOURCE DISCOVERY:
 * MRL_team_charts_stragglers_discovery_20260916_005254.json
 *
 * SAFETY
 * ------
 * - Exact path + size + mtime preflight for all six files.
 * - Destination collision checks.
 * - Exact dependency preflight before any change.
 * - Full backups before any move/edit.
 * - Atomic rewritten-file writes.
 * - Rollback restores originals and removes relocated copies.
 * - No database writes.
 * - Does not touch current_* helper files.
 */

date_default_timezone_set('America/New_York');

const MRL_INSTALLER_VERSION = 'v001';
const MRL_DEST_DIR = 'team_charts';
const MRL_BACKUP_DIR = '_mrl_installer_backups/team_chart_stragglers_20260916_010200';
const MRL_STATE_FILE = '_mrl_team_chart_stragglers_state.json';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$manifest = [
    "2017_Team_chart.php" => ['size' => 1140, 'mtime' => 1765271965],
    "2017-s2-no-picks-yet.php" => ['size' => 1266, 'mtime' => 1765271965],
    "2017-s3-no-picks-yet.php" => ['size' => 1266, 'mtime' => 1765271965],
    "2017-s4-no-picks-yet.php" => ['size' => 1277, 'mtime' => 1765271965],
    "2023_All_Teams_chart.php" => ['size' => 2170, 'mtime' => 1765271965],
    "2023_All_Teams_history_chart.php" => ['size' => 2080, 'mtime' => 1765271965],
];

$dependencyMap = [
    '2017_Team_chart.php' => ['config.php'],
    '2017-s2-no-picks-yet.php' => ['config.php'],
    '2017-s3-no-picks-yet.php' => ['config.php'],
    '2017-s4-no-picks-yet.php' => ['config.php'],
    '2023_All_Teams_chart.php' => ['class.user.php','config.php','config_mrl.php'],
    '2023_All_Teams_history_chart.php' => ['class.user.php','config.php','config_mrl.php'],
];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function full_path(string $root, string $relative): string {
    return rtrim($root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function ensure_dir(string $dir): bool {
    return is_dir($dir) || @mkdir($dir, 0755, true) || is_dir($dir);
}

function fmt_bytes(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    $units=['KB','MB','GB','TB']; $v=(float)$bytes;
    foreach($units as $u){
        $v/=1024;
        if($v<1024 || $u==='TB') return number_format($v,$v>=100?0:($v>=10?1:2)).' '.$u;
    }
    return $bytes.' B';
}

function atomic_write(string $path, string $content): bool {
    $dir = dirname($path);
    if (!ensure_dir($dir)) return false;
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) return false;
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function state_path(string $root): string {
    return full_path($root, MRL_BACKUP_DIR . '/' . MRL_STATE_FILE);
}

function load_state(string $root): ?array {
    $f = state_path($root);
    if (!is_file($f)) return null;
    $raw = @file_get_contents($f);
    if ($raw === false) return null;
    $d = json_decode($raw, true);
    return is_array($d) ? $d : null;
}

function save_state(string $root, array $state): bool {
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return $json !== false && atomic_write(state_path($root), $json . "\n");
}

function count_literal_dependency(string $content, string $dep): int {
    $q = preg_quote($dep, '/');
    $patterns = [
        '/\b(?:include|include_once|require|require_once)\s+[\'"]' . $q . '[\'"]\s*;?/i',
        '/\b(?:include|include_once|require|require_once)\s*\(\s*[\'"]' . $q . '[\'"]\s*\)\s*;?/i',
    ];
    $count = 0;
    foreach ($patterns as $p) $count += preg_match_all($p, $content, $m);
    return $count;
}

function rewrite_dependencies(string $content, array $deps, array &$counts): string {
    $counts = [];

    foreach ($deps as $dep) {
        $counts[$dep] = 0;
        $q = preg_quote($dep, '/');

        $patterns = [
            '/\b(include|include_once|require|require_once)\s+[\'"]' . $q . '[\'"]\s*;?/i',
            '/\b(include|include_once|require|require_once)\s*\(\s*[\'"]' . $q . '[\'"]\s*\)\s*;?/i',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback(
                $pattern,
                function ($m) use ($dep, &$counts) {
                    $counts[$dep]++;
                    return $m[1] . " dirname(__DIR__) . '/" . $dep . "';";
                },
                $content
            );
        }
    }

    return $content;
}

function preflight(string $root, array $manifest, array $dependencyMap): array {
    $rows=[]; $blocking=0; $matched=0; $depReady=0; $destClear=0; $bytes=0;

    $destDir = full_path($root, MRL_DEST_DIR);
    $backupDir = full_path($root, MRL_BACKUP_DIR);

    $rows[] = [
        'check'=>'Production root',
        'status'=>is_dir($root)?'PASS':'FAIL',
        'detail'=>$root
    ];
    if(!is_dir($root)) $blocking++;

    $rows[] = [
        'check'=>'Existing /team_charts/',
        'status'=>is_dir($destDir)?'PASS':'FAIL',
        'detail'=>is_dir($destDir)?'/team_charts/ exists from the main relocation.':'/team_charts/ is missing.'
    ];
    if(!is_dir($destDir)) $blocking++;

    if(is_dir($backupDir) && load_state($root)){
        $rows[] = [
            'check'=>'Prior add-on state',
            'status'=>'INFO',
            'detail'=>'Existing add-on state found. Use Rollback instead of installing again.'
        ];
    } else {
        $rows[] = [
            'check'=>'Backup destination',
            'status'=>'PASS',
            'detail'=>MRL_BACKUP_DIR.' is available.'
        ];
    }

    foreach($manifest as $name=>$meta){
        $src=full_path($root,$name);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

        if(!is_file($src)){
            $rows[]=['check'=>'Source baseline','status'=>'FAIL','detail'=>$name.' is missing from root.'];
            $blocking++;
            continue;
        }

        $size=@filesize($src); $mtime=@filemtime($src);
        $size=$size===false?-1:(int)$size;
        $mtime=$mtime===false?-1:(int)$mtime;

        if($size!==(int)$meta['size'] || $mtime!==(int)$meta['mtime']){
            $rows[]=['check'=>'Source baseline','status'=>'FAIL','detail'=>$name.' changed since discovery.'];
            $blocking++;
            continue;
        }

        $matched++; $bytes+=$size;

        $content=@file_get_contents($src);
        $ok=($content!==false);

        foreach($dependencyMap[$name] as $dep){
            if(!$ok || count_literal_dependency($content,$dep)<1){
                $ok=false;
                break;
            }
        }

        if($ok) $depReady++;
        else {
            $rows[]=[
                'check'=>'Dependency readiness',
                'status'=>'FAIL',
                'detail'=>$name.' does not match its expected include/require set.'
            ];
            $blocking++;
        }

        if(file_exists($dest)){
            $rows[]=['check'=>'Destination collision','status'=>'FAIL','detail'=>MRL_DEST_DIR.'/'.$name.' already exists.'];
            $blocking++;
        } else {
            $destClear++;
        }
    }

    $rows[]=[
        'check'=>'6-file source manifest',
        'status'=>$matched===count($manifest)?'PASS':'FAIL',
        'detail'=>$matched.' of '.count($manifest).' exact baselines match; '.fmt_bytes($bytes).'.'
    ];
    if($matched!==count($manifest)) $blocking++;

    $rows[]=[
        'check'=>'Dependency repair readiness',
        'status'=>$depReady===count($manifest)?'PASS':'FAIL',
        'detail'=>$depReady.' of '.count($manifest).' files contain every expected relative dependency.'
    ];
    if($depReady!==count($manifest)) $blocking++;

    $rows[]=[
        'check'=>'Destination filenames',
        'status'=>$destClear===count($manifest)?'PASS':'FAIL',
        'detail'=>$destClear.' of '.count($manifest).' target filenames are clear.'
    ];
    if($destClear!==count($manifest)) $blocking++;

    return [
        'ok'=>($blocking===0 && load_state($root)===null),
        'rows'=>$rows,
        'blocking'=>$blocking
    ];
}

function install_addon(string $root, array $manifest, array $dependencyMap): array {
    $pre=preflight($root,$manifest,$dependencyMap);
    if(!$pre['ok']){
        return ['ok'=>false,'message'=>'Install blocked: preflight is not fully green.','errors'=>[]];
    }

    $backupDir=full_path($root,MRL_BACKUP_DIR);
    if(!ensure_dir($backupDir)){
        return ['ok'=>false,'message'=>'Could not create backup directory.','errors'=>[]];
    }

    $errors=[]; $moved=[]; $repairCounts=[];

    foreach($manifest as $name=>$meta){
        $src=full_path($root,$name);
        $bak=full_path($root,MRL_BACKUP_DIR.'/sources/'.$name);

        if(!ensure_dir(dirname($bak)) || !@copy($src,$bak)){
            $errors[]='Backup failed for '.$name;
            break;
        }
    }

    if($errors){
        return ['ok'=>false,'message'=>'Install stopped before move because backup creation failed.','errors'=>$errors];
    }

    foreach($manifest as $name=>$meta){
        $src=full_path($root,$name);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

        if(!@rename($src,$dest)){
            $errors[]='Move failed for '.$name;
            break;
        }

        $moved[]=$name;

        $content=@file_get_contents($dest);
        if($content===false){
            $errors[]='Could not read moved file '.$name;
            break;
        }

        $counts=[];
        $newContent=rewrite_dependencies($content,$dependencyMap[$name],$counts);

        foreach($dependencyMap[$name] as $dep){
            if(($counts[$dep]??0)<1){
                $errors[]='Expected dependency '.$dep.' was not rewritten in '.$name;
                break 2;
            }
        }

        if(!atomic_write($dest,$newContent)){
            $errors[]='Atomic rewrite failed for '.$name;
            break;
        }

        $repairCounts[$name]=$counts;
    }

    if($errors){
        foreach($manifest as $name=>$meta){
            $bak=full_path($root,MRL_BACKUP_DIR.'/sources/'.$name);
            $orig=full_path($root,$name);
            $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

            if(is_file($bak)){
                @copy($bak,$orig);
                @touch($orig,(int)$meta['mtime']);
            }
            if(is_file($dest)) @unlink($dest);
        }

        return [
            'ok'=>false,
            'message'=>'Install failed; automatic rollback was attempted.',
            'errors'=>$errors
        ];
    }

    $state=[
        'tool'=>'MRL Team Chart Stragglers Add-On Installer',
        'version'=>MRL_INSTALLER_VERSION,
        'installed_at'=>date(DATE_ATOM),
        'destination'=>MRL_DEST_DIR,
        'moved_files'=>array_keys($manifest),
        'repair_counts'=>$repairCounts
    ];

    if(!save_state($root,$state)){
        return [
            'ok'=>false,
            'message'=>'Files were installed, but writing installer state failed. Keep backups.',
            'errors'=>['Could not write add-on state file.']
        ];
    }

    return [
        'ok'=>true,
        'message'=>'Six Team Chart stragglers moved and repaired successfully.',
        'errors'=>[]
    ];
}

function rollback_addon(string $root, array $manifest): array {
    $state=load_state($root);
    if(!$state){
        return ['ok'=>false,'message'=>'Rollback state not found.','errors'=>[]];
    }

    $errors=[];

    foreach($manifest as $name=>$meta){
        $bak=full_path($root,MRL_BACKUP_DIR.'/sources/'.$name);
        $orig=full_path($root,$name);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

        if(!is_file($bak) || !@copy($bak,$orig)){
            $errors[]='Could not restore '.$name;
            continue;
        }

        @touch($orig,(int)$meta['mtime']);

        if(is_file($dest) && !@unlink($dest)){
            $errors[]='Could not remove relocated copy '.$name;
        }
    }

    if(!$errors){
        @unlink(state_path($root));
        return ['ok'=>true,'message'=>'Rollback completed. Six original root files restored.','errors'=>[]];
    }

    return ['ok'=>false,'message'=>'Rollback incomplete. Leave backups in place.','errors'=>$errors];
}

function postflight(string $root, array $manifest, array $dependencyMap): array {
    $rows=[]; $ok=true; $destGood=0; $rootGone=0; $repairGood=0;

    foreach($manifest as $name=>$meta){
        $src=full_path($root,$name);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

        if(!is_file($src)) $rootGone++;

        if(is_file($dest)){
            $destGood++;
            $content=@file_get_contents($dest);
            $depsOk=($content!==false);

            foreach($dependencyMap[$name] as $dep){
                if(!$depsOk || strpos($content,"dirname(__DIR__) . '/".$dep."'")===false){
                    $depsOk=false;
                    break;
                }
            }

            if($depsOk) $repairGood++;
        }
    }

    $rows[]=[
        'check'=>'Relocated stragglers present',
        'status'=>$destGood===count($manifest)?'PASS':'FAIL',
        'detail'=>$destGood.' of '.count($manifest).' exist under /'.MRL_DEST_DIR.'/'
    ];
    if($destGood!==count($manifest)) $ok=false;

    $rows[]=[
        'check'=>'Old root copies removed',
        'status'=>$rootGone===count($manifest)?'PASS':'FAIL',
        'detail'=>$rootGone.' of '.count($manifest).' old root paths are absent.'
    ];
    if($rootGone!==count($manifest)) $ok=false;

    $rows[]=[
        'check'=>'Dependency repairs',
        'status'=>$repairGood===count($manifest)?'PASS':'FAIL',
        'detail'=>$repairGood.' of '.count($manifest).' relocated files use dirname(__DIR__) for every expected dependency.'
    ];
    if($repairGood!==count($manifest)) $ok=false;

    return ['ok'=>$ok,'rows'=>$rows];
}

$action=(string)($_POST['action']??'');
$result=null;

if($action==='install'){
    if(($_POST['confirm_install']??'')!=='yes'){
        $result=['ok'=>false,'message'=>'Install not started: confirmation box was not checked.','errors'=>[]];
    } else {
        $result=install_addon($root,$manifest,$dependencyMap);
    }
} elseif($action==='rollback'){
    $result=rollback_addon($root,$manifest);
}

$state=load_state($root);
$pre=$state?null:preflight($root,$manifest,$dependencyMap);
$post=$state?postflight($root,$manifest,$dependencyMap):null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Team Chart Stragglers Add-On</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.banner{padding:11px 13px;margin-bottom:11px;border:1px solid #3f8bc2;border-radius:10px;background:#15354d;color:#e8f5ff;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.card{padding:11px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:20px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
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
.linkgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.linkcard{padding:8px 10px;border:1px solid #3f4945;border-radius:8px;background:#151917}
.linkcard a{color:#63c5ff;text-decoration:none;font-weight:700}
.exist{color:#8fe0a9;font-size:12px}.missing{color:#ff9c9c;font-size:12px}
code{color:#f8d89a}
@media(max-width:800px){.grid,.linkgrid{grid-template-columns:1fr}}
</style>
</head>
<body><div class="wrap">

<h1>MRL Team Chart Stragglers Add-On</h1>

<div class="banner">
Moves the six remaining year-chart stragglers into <code>/team_charts/</code> and repairs their relative root dependencies.
The five <code>current_*</code> helpers are intentionally untouched.
</div>

<div class="panel">
<h2>Plan</h2>
<div class="grid">
<div class="card"><span class="small">Files to move</span><span class="value">6</span></div>
<div class="card"><span class="small">Destination</span><span class="value" style="font-size:16px">/team_charts/</span></div>
<div class="card"><span class="small">current_* files changed</span><span class="value">0</span></div>
</div>
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
<p>The add-on state is active. Test the six links below. Rollback is available if anything looks wrong.</p>
<form method="post">
<input type="hidden" name="action" value="rollback">
<div class="actions">
<button class="rollback" type="submit">Rollback Stragglers Add-On</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Postflight</button>
</div>
</form>
<?php else: ?>
<form method="post">
<input type="hidden" name="action" value="install">
<label class="confirm">
<input type="checkbox" name="confirm_install" value="yes" id="confirmInstall">
I reviewed the green preflight and want to move/repair these six stragglers.
</label>
<div class="actions" style="margin-top:10px">
<button class="install" type="submit" id="installButton" disabled>Install Stragglers Add-On</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Preflight</button>
</div>
<p class="small"><?php echo $pre['ok']?'Preflight is green. Check the confirmation box to enable Install.':'Install is blocked because one or more preflight checks failed.'; ?></p>
</form>
<?php endif; ?>
</div>

<div class="panel">
<h2>Six Verification Links</h2>
<div class="linkgrid">
<?php foreach(array_keys($manifest) as $name):
$exists=is_file(full_path($root,MRL_DEST_DIR.'/'.$name));
$href='/'.MRL_DEST_DIR.'/'.rawurlencode($name);
?>
<div class="linkcard">
<a href="<?php echo h($href); ?>" target="_blank" rel="noopener"><?php echo h($name); ?></a>
<div class="<?php echo $exists?'exist':'missing'; ?>"><?php echo $exists?'FILE EXISTS':'NOT MOVED YET'; ?></div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="panel small">
Backups: <code><?php echo h(MRL_BACKUP_DIR); ?>/</code><br>
No database writes. No Rules / Fees / Schedule changes. No <code>current_*</code> helper changes.
</div>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 1:02:00 am EDT
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
