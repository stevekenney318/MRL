<?php
declare(strict_types=1);

/*
    filename: admin_backup_files_helper.php
    VERSION: v002
    LAST MODIFIED: 9/3/2026 1:33:00 pm

    PURPOSE:
    - MRL website-files backup helper for admin_backup.php.
    - Backs up public_html except wp-admin, wp-includes, wp-content.
    - Stores ZIPs outside public_html.
    - Preserves entry mtimes when ZipArchive::setMtimeName is available.
    - Includes _backup_manifest.json with path, type, mtime, size, permissions, SHA-256.
    - Uses small AJAX batches to avoid one long browser request.

    CHANGELOG:
    v002 (9/3/2026 1:33:00 pm)
    - FIX: Keeps a temporary marker entry in a newly created ZIP so the archive
      physically exists and can be reopened by the next AJAX batch request.
    - FIX: Removes that marker before the final ZIP is completed.
    - SAFETY: Verifies the reusable ZIP file exists before saving batch state.

    v001 (9/3/2026 12:59:23 pm)
    - Initial MRL file-backup helper.
*/

if (!defined('MRL_BACKUP_MANAGER_ENTRY')) {
    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>MRL Backup Helper</title></head><body style="font-family:Arial;background:#111;color:#eee;padding:30px">';
    echo '<h1 style="color:#d8c08a">MRL Backup Helper</h1>';
    echo '<p>This file is part of <code>admin_backup.php</code> and is not intended to be run directly.</p>';
    echo '<p><a style="color:#8ec5ff" href="admin_backup.php">Open the Backup Manager</a></p>';
    echo '</body></html>';
    exit;
}

date_default_timezone_set('America/New_York');

$sourceDir = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$domainRoot = dirname($sourceDir);
$backupRoot = $domainRoot . DIRECTORY_SEPARATOR . '_mrl_backups' . DIRECTORY_SEPARATOR . 'files';
$stateDir = $backupRoot . DIRECTORY_SEPARATOR . '.state';
$excludedTop = ['wp-admin', 'wp-includes', 'wp-content'];
$batchFiles = 120;

function mrlfb_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function mrlfb_json(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function mrlfb_ensure_dir(string $dir): bool {
    return is_dir($dir) ? is_writable($dir) : (@mkdir($dir, 0755, true) && is_writable($dir));
}
function mrlfb_safe_id(string $id): string { return preg_replace('/[^A-Za-z0-9_-]/', '', $id) ?: ''; }
function mrlfb_state_path(string $stateDir, string $id): string { return $stateDir . DIRECTORY_SEPARATOR . 'backup_' . $id . '.json'; }
function mrlfb_read_state(string $stateDir, string $id): array {
    $p = mrlfb_state_path($stateDir, $id); if (!is_file($p)) return [];
    $j = json_decode((string)@file_get_contents($p), true); return is_array($j) ? $j : [];
}
function mrlfb_write_state(string $stateDir, string $id, array $state): bool {
    return @file_put_contents(mrlfb_state_path($stateDir, $id), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}
function mrlfb_rel(string $root, string $path): string {
    $r = ltrim(str_replace('\\','/', substr($path, strlen($root))), '/'); return $r;
}
function mrlfb_scan(string $root, array $excludedTop): array {
    $items=[]; $totalBytes=0; $dirs=0; $files=0;
    $flags = FilesystemIterator::SKIP_DOTS;
    $dirIt = new RecursiveDirectoryIterator($root, $flags);
    $filter = new RecursiveCallbackFilterIterator($dirIt, function($current, $key, $iterator) use ($root, $excludedTop) {
        $rel = mrlfb_rel($root, $current->getPathname());
        $top = explode('/', $rel, 2)[0] ?? '';
        if ($current->isDir() && in_array($top, $excludedTop, true)) return false;
        return true;
    });
    $it = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $info) {
        $path=$info->getPathname(); $rel=mrlfb_rel($root,$path); if ($rel==='') continue;
        $top = explode('/', $rel, 2)[0] ?? '';
        if (in_array($top, $excludedTop, true)) continue;
        if ($info->isLink()) continue;
        if ($info->isDir()) { $dirs++; $items[]=['path'=>$path,'rel'=>$rel,'type'=>'dir','size'=>0,'mtime'=>$info->getMTime(),'perms'=>$info->getPerms() & 0777]; }
        elseif ($info->isFile()) { $size=$info->getSize(); $files++; $totalBytes += $size; $items[]=['path'=>$path,'rel'=>$rel,'type'=>'file','size'=>$size,'mtime'=>$info->getMTime(),'perms'=>$info->getPerms() & 0777]; }
    }
    usort($items,function($a,$b){return strcmp($a['rel'],$b['rel']);});
    return ['items'=>$items,'files'=>$files,'dirs'=>$dirs,'bytes'=>$totalBytes];
}
function mrlfb_list_zips(string $root): array {
    if (!is_dir($root)) return [];
    $files=glob($root . DIRECTORY_SEPARATOR . 'public_html_*.zip') ?: [];
    usort($files,function($a,$b){return (@filemtime($b)?:0) <=> (@filemtime($a)?:0);});
    return $files;
}
function mrlfb_valid_zip(string $name): bool {
    return basename($name)===$name && preg_match('/^public_html_\d{8}_\d{9}(?:am|pm)\.zip$/', $name) === 1;
}
function mrlfb_fmt_bytes(int $b): string {
    $u=['B','KB','MB','GB']; $i=0; $v=(float)$b; while($v>=1024 && $i<count($u)-1){$v/=1024;$i++;}
    return number_format($v,$i===0?0:2).' '.$u[$i];
}

if (!mrlfb_ensure_dir($backupRoot) || !mrlfb_ensure_dir($stateDir)) {
    $storageError = 'Backup storage directory could not be created or is not writable: ' . $backupRoot;
} else { $storageError = ''; }

$ajax = (string)($_REQUEST['files_action'] ?? '');
if ($ajax !== '') {
    if ($storageError !== '') mrlfb_json(['ok'=>false,'error'=>$storageError],500);
    if (!class_exists('ZipArchive')) mrlfb_json(['ok'=>false,'error'=>'PHP ZipArchive is not available.'],500);

    if ($ajax === 'preflight') {
        $scan=mrlfb_scan($sourceDir,$excludedTop);
        $free=@disk_free_space($domainRoot); if ($free===false) $free=0;
        mrlfb_json(['ok'=>true,'source'=>$sourceDir,'destination'=>$backupRoot,'files'=>$scan['files'],'dirs'=>$scan['dirs'],'bytes'=>$scan['bytes'],'bytes_label'=>mrlfb_fmt_bytes($scan['bytes']),'free_bytes'=>(int)$free,'free_label'=>mrlfb_fmt_bytes((int)$free),'mtime_supported'=>method_exists('ZipArchive','setMtimeName'),'excluded'=>$excludedTop]);
    }

    if ($ajax === 'start') {
        $scan=mrlfb_scan($sourceDir,$excludedTop);
        $id=date('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $stamp=date('Ymd_his') . sprintf('%03d',(int)floor((microtime(true)-floor(microtime(true)))*1000)) . date('a');
        $zipName='public_html_' . $stamp . '.zip';
        $finalPath=$backupRoot . DIRECTORY_SEPARATOR . $zipName;
        $zipPath=$finalPath . '.part';
        $state=['id'=>$id,'status'=>'running','zip_name'=>$zipName,'zip_path'=>$zipPath,'final_path'=>$finalPath,'source'=>$sourceDir,'created_at'=>date('c'),'index'=>0,'items'=>$scan['items'],'files_total'=>$scan['files'],'dirs_total'=>$scan['dirs'],'bytes_total'=>$scan['bytes'],'files_done'=>0,'dirs_done'=>0,'bytes_done'=>0,'manifest'=>[],'error'=>''];
        $zip=new ZipArchive(); $res=$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res!==true) mrlfb_json(['ok'=>false,'error'=>'Could not create ZIP. ZipArchive code: '.$res],500);

        // IMPORTANT: an entirely empty ZipArchive may not leave a physical ZIP file
        // behind when it is closed. The batch worker must reopen the archive in the
        // next request, so add a temporary marker entry now and remove it at completion.
        if (!$zip->addFromString('._mrl_backup_in_progress', 'MRL file backup in progress')) {
            $zip->close();
            @unlink($zipPath);
            mrlfb_json(['ok'=>false,'error'=>'Could not initialize ZIP for batched backup.'],500);
        }

        $zip->close();

        if (!is_file($zipPath)) {
            mrlfb_json(['ok'=>false,'error'=>'ZIP initialization did not create a reusable archive file.'],500);
        }

        if (!mrlfb_write_state($stateDir,$id,$state)) { @unlink($zipPath); mrlfb_json(['ok'=>false,'error'=>'Could not write backup state file.'],500); }
        mrlfb_json(['ok'=>true,'id'=>$id,'zip_name'=>$zipName,'files_total'=>$scan['files'],'bytes_total'=>$scan['bytes']]);
    }

    if ($ajax === 'step') {
        $id=mrlfb_safe_id((string)($_POST['id']??'')); $state=mrlfb_read_state($stateDir,$id);
        if (!$state) mrlfb_json(['ok'=>false,'error'=>'Backup state not found.'],404);
        if (($state['status']??'')==='complete') mrlfb_json(['ok'=>true,'complete'=>true,'state'=>$state]);
        $zip=new ZipArchive(); $res=$zip->open((string)$state['zip_path']);
        if ($res!==true) mrlfb_json(['ok'=>false,'error'=>'Could not reopen ZIP. Code: '.$res],500);
        $start=(int)$state['index']; $end=min(count($state['items']),$start+$batchFiles);
        for($i=$start;$i<$end;$i++){
            $it=$state['items'][$i]; $rel=(string)$it['rel']; $path=(string)$it['path'];
            $entry=['path'=>$rel,'type'=>$it['type'],'mtime'=>(int)$it['mtime'],'size'=>(int)$it['size'],'permissions'=>sprintf('%04o',(int)$it['perms']),'sha256'=>null];
            if ($it['type']==='dir') {
                $zip->addEmptyDir(rtrim($rel,'/').'/');
                if (method_exists($zip,'setMtimeName')) @ $zip->setMtimeName(rtrim($rel,'/').'/',(int)$it['mtime']);
                $state['dirs_done']++;
            } else {
                if (!is_file($path) || !$zip->addFile($path,$rel)) { $zip->close(); $state['status']='error'; $state['error']='Could not add file: '.$rel; mrlfb_write_state($stateDir,$id,$state); mrlfb_json(['ok'=>false,'error'=>$state['error']],500); }
                if (method_exists($zip,'setMtimeName')) @ $zip->setMtimeName($rel,(int)$it['mtime']);
                $entry['sha256']=@hash_file('sha256',$path) ?: null;
                $state['files_done']++; $state['bytes_done'] += (int)$it['size'];
            }
            $state['manifest'][]=$entry; $state['index']=$i+1;
        }
        $complete=$state['index']>=count($state['items']);
        if ($complete) {
            // Remove the temporary marker that was used only to make the archive
            // persist between AJAX requests.
            @$zip->deleteName('._mrl_backup_in_progress');

            $manifest=['backup_version'=>1,'created_at'=>$state['created_at'],'source'=>$state['source'],'excluded_top_level'=>$excludedTop,'file_count'=>$state['files_total'],'directory_count'=>$state['dirs_total'],'source_bytes'=>$state['bytes_total'],'entries'=>$state['manifest']];
            $manifestJson=json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            $zip->addFromString('_backup_manifest.json',$manifestJson===false?'{}':$manifestJson);
            $state['status']='complete'; $state['completed_at']=date('c');
        }
        $zip->close();
        if ($complete) {
            $finalPath=(string)($state['final_path'] ?? '');
            if ($finalPath==='' || !@rename((string)$state['zip_path'],$finalPath)) {
                $state['status']='error'; $state['error']='ZIP finished but could not be renamed to its final filename.'; mrlfb_write_state($stateDir,$id,$state); mrlfb_json(['ok'=>false,'error'=>$state['error']],500);
            }
            $state['zip_path']=$finalPath;
            $state['zip_bytes']=@filesize($finalPath) ?: 0;
            unset($state['items'],$state['manifest']);
        }
        mrlfb_write_state($stateDir,$id,$state);
        mrlfb_json(['ok'=>true,'complete'=>$complete,'files_done'=>$state['files_done'],'files_total'=>$state['files_total'],'dirs_done'=>$state['dirs_done'],'bytes_done'=>$state['bytes_done'],'bytes_total'=>$state['bytes_total'],'zip_name'=>$state['zip_name'],'zip_bytes'=>$state['zip_bytes']??0]);
    }

    if ($ajax === 'download') {
        $name=basename((string)($_GET['file']??'')); if(!mrlfb_valid_zip($name)) { http_response_code(400); exit('Invalid backup filename.'); }
        $path=$backupRoot.DIRECTORY_SEPARATOR.$name; if(!is_file($path)){http_response_code(404);exit('Backup not found.');}
        header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="'.$name.'"'); header('Content-Length: '.filesize($path)); header('Cache-Control: no-store');
        $fh=fopen($path,'rb'); while(!feof($fh)){echo fread($fh,1024*1024); flush();} fclose($fh); exit;
    }

    if ($ajax === 'delete') {
        $name=basename((string)($_POST['file']??'')); if(!mrlfb_valid_zip($name)) mrlfb_json(['ok'=>false,'error'=>'Invalid backup filename.'],400);
        $path=$backupRoot.DIRECTORY_SEPARATOR.$name; if(!is_file($path)) mrlfb_json(['ok'=>false,'error'=>'Backup not found.'],404);
        if(!@unlink($path)) mrlfb_json(['ok'=>false,'error'=>'Could not delete backup.'],500);
        mrlfb_json(['ok'=>true]);
    }
    mrlfb_json(['ok'=>false,'error'=>'Unknown files backup action.'],400);
}

$zips=mrlfb_list_zips($backupRoot);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MRL Backup Manager - Files</title>
<style>
body{margin:0;background:#111;color:#eee;font-family:Arial,Helvetica,sans-serif}.wrap{max-width:1100px;margin:18px auto 40px;padding:0 14px}.card{background:#1b1b1b;border:1px solid #333;border-radius:10px;padding:18px;margin-bottom:16px;box-shadow:0 8px 24px rgba(0,0,0,.35)}h1{color:#d8c08a;font-size:34px;margin:0 0 8px}.title{font-size:20px;font-weight:700;color:#ffd18a}.muted{color:#aaa;font-size:13px;line-height:1.5}.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}.btn{border:0;border-radius:8px;padding:11px 16px;font-weight:700;font-size:15px;text-decoration:none;cursor:pointer;display:inline-block}.safe{background:#237a45;color:#fff}.blue{background:#2f6feb;color:#fff}.danger{background:#b82b2b;color:#fff}.gray{background:#555f6d;color:#fff}.btn:disabled{background:#555;color:#bbb;cursor:not-allowed}.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px}.status{margin-top:12px;padding:12px;border-radius:8px;background:#12283a;border:1px solid #275d82}.ok{background:#123d26;border-color:#237a45;color:#eaffef}.err{background:#531f1f;border-color:#b82b2b;color:#fff}.progress{height:18px;background:#333;border-radius:10px;overflow:hidden;margin-top:10px}.bar{height:100%;width:0;background:#237a45;transition:width .2s}.big{font-size:18px;font-weight:700}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{padding:9px 8px;border-bottom:1px solid #3a3a3a;text-align:left}th{color:#ffd88a}
</style></head><body><div class="wrap">
<div class="card"><h1>MRL Files Backup</h1><div class="row"><a class="btn gray" href="admin_backup.php">Backup Manager Home</a><a class="btn blue" href="admin_backup.php?section=db">Database Backup</a></div></div>
<div class="card"><div class="title">Backup Scope</div><p class="muted">Source: <span class="mono"><?php echo mrlfb_h($sourceDir); ?></span><br>Destination: <span class="mono"><?php echo mrlfb_h($backupRoot); ?></span><br>Excluded completely: <span class="mono">wp-admin / wp-includes / wp-content</span></p><p class="muted">ZIP entries receive original modification times when supported. A <span class="mono">_backup_manifest.json</span> is also stored inside every ZIP with exact Unix mtime, size, permissions and SHA-256 for every file.</p></div>
<?php if($storageError!==''): ?><div class="card err"><?php echo mrlfb_h($storageError); ?></div><?php else: ?>
<div class="card"><div class="title">Create Files Backup</div><div class="row"><button id="preflightBtn" class="btn blue">Run Preflight</button><button id="startBtn" class="btn safe" disabled>Create Files Backup</button></div><div id="status" class="status">Run Preflight first.</div><div class="progress"><div id="bar" class="bar"></div></div></div>
<?php endif; ?>
<div class="card"><div class="title">Completed File Backups</div>
<?php if(!$zips): ?><p class="muted">No completed file backup ZIPs found yet.</p><?php else: ?><table><thead><tr><th>File</th><th>Modified</th><th>Size</th><th>Actions</th></tr></thead><tbody>
<?php foreach($zips as $p): $n=basename($p); ?><tr><td class="mono"><?php echo mrlfb_h($n); ?></td><td><?php echo mrlfb_h(date('Y-m-d H:i:s',(int)filemtime($p))); ?></td><td><?php echo mrlfb_h(mrlfb_fmt_bytes((int)filesize($p))); ?></td><td><a class="btn blue" href="admin_backup.php?section=files&amp;files_action=download&amp;file=<?php echo rawurlencode($n); ?>">Download</a> <button class="btn danger" onclick="deleteZip('<?php echo mrlfb_h($n); ?>')">Delete</button></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?></div>
<div class="muted" style="text-align:right">admin_backup_files_helper.php (via admin_backup.php)</div></div>
<script>
const q=(s)=>document.querySelector(s); let ready=false, running=false;
async function call(action,data){const body=new URLSearchParams(data||{});body.set('files_action',action);const r=await fetch('admin_backup.php?section=files',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});const j=await r.json();if(!j.ok)throw new Error(j.error||'Request failed');return j;}
q('#preflightBtn')?.addEventListener('click',async()=>{try{q('#status').textContent='Scanning files...';const j=await call('preflight');ready=true;q('#startBtn').disabled=false;q('#status').className='status ok';q('#status').innerHTML='<span class="big">READY</span><br>'+j.files.toLocaleString()+' files + '+j.dirs.toLocaleString()+' folders • '+j.bytes_label+' source data<br>Free space: '+j.free_label+' • ZIP mtime support: '+(j.mtime_supported?'YES':'NO');}catch(e){q('#status').className='status err';q('#status').textContent=e.message;}});
q('#startBtn')?.addEventListener('click',async()=>{if(!ready||running)return;running=true;q('#startBtn').disabled=true;q('#preflightBtn').disabled=true;try{const s=await call('start');q('#status').className='status';q('#status').textContent='Backup started: '+s.zip_name;while(true){const j=await call('step',{id:s.id});const pct=j.bytes_total>0?Math.min(100,(j.bytes_done/j.bytes_total)*100):(j.complete?100:0);q('#bar').style.width=pct.toFixed(1)+'%';q('#status').innerHTML=(j.complete?'<span class="big">BACKUP COMPLETE</span><br>':'Backing up...<br>')+j.files_done.toLocaleString()+' / '+j.files_total.toLocaleString()+' files • '+pct.toFixed(1)+'%';if(j.complete){q('#status').className='status ok';setTimeout(()=>location.reload(),700);break;}}}catch(e){q('#status').className='status err';q('#status').textContent=e.message;q('#preflightBtn').disabled=false;}finally{running=false;}});
async function deleteZip(name){if(!confirm('Delete this completed file backup?\n\n'+name+'\n\nThis cannot be undone.'))return;try{await call('delete',{file:name});location.reload();}catch(e){alert(e.message);}}
</script></body></html>
