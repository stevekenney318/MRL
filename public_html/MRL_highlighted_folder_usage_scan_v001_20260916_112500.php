<?php
declare(strict_types=1);

date_default_timezone_set('America/New_York');
const TOOL_VERSION = 'v001';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$realRoot = realpath($root);
if ($realRoot !== false) $root = $realRoot;

$targets = ['css','formtools','js','mailer','vendor','viewer'];
$textExts = ['php','phtml','inc','html','htm','js','css','json','xml','txt','md','ini','conf','config','yml','yaml','sql','csv'];
$skipDirPatterns = [
    '#(^|[\\\\/])\\.git([\\\\/]|$)#i',
    '#(^|[\\\\/])node_modules([\\\\/]|$)#i',
    '#(^|[\\\\/])_mrl_installer_backups([\\\\/]|$)#i',
];
$oldLikePatterns = [
    '#(^|[\\\\/])To_Be_Deleted_#i',
    '#(^|[\\\\/]).*backup.*([\\\\/]|$)#i',
    '#(^|[\\\\/])archive([d]?)([\\\\/]|_|$)#i',
    '#(^|[\\\\/])old([\\\\/]|_|$)#i',
    '#(^|[\\\\/])test([\\\\/]|_|$)#i',
    '#(^|[\\\\/])testPHP8([\\\\/]|$)#i',
    '#(^|[\\\\/])sandbox([\\\\/]|_|$)#i',
    '#(^|[\\\\/])dev([0-9]?)([\\\\/]|_|$)#i',
    '#(^|[\\\\/])mrl2_sandbox_site([\\\\/]|$)#i',
];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function relpath(string $path,string $root): string {
    $path=str_replace('\\','/',$path); $root=rtrim(str_replace('\\','/',$root),'/');
    if(stripos($path,$root.'/')===0) return substr($path,strlen($root)+1);
    return $path===$root?'':$path;
}
function should_skip_dir(string $path,array $patterns): bool { foreach($patterns as $p) if(preg_match($p,$path)) return true; return false; }
function is_old_like(string $rel,array $patterns): bool {
    $test='/'.ltrim(str_replace('\\','/',$rel),'/'); foreach($patterns as $p) if(preg_match($p,$test)) return true; return false;
}
function fmt_bytes(int $b): string {
    if($b<1024) return $b.' B'; $units=['KB','MB','GB','TB']; $v=(float)$b;
    foreach($units as $u){ $v/=1024; if($v<1024 || $u==='TB') return number_format($v,$v>=100?0:($v>=10?1:2)).' '.$u; }
    return $b.' B';
}
function safe_file_get(string $path,int $maxBytes=2097152): ?string {
    if(!is_file($path)||!is_readable($path)) return null; $size=@filesize($path); if($size!==false && $size>$maxBytes) return null;
    $data=@file_get_contents($path); return $data===false?null:$data;
}
function detect_signatures(string $dir): array {
    $found=[];
    $checks=[
        'composer.json'=>'Composer package/project metadata','composer.lock'=>'Composer dependency lock file','autoload.php'=>'PHP autoloader entry point',
        'package.json'=>'JavaScript/npm package metadata','index.php'=>'PHP application/index entry point','index.html'=>'HTML application/index entry point',
        'README.md'=>'README documentation','README.txt'=>'README documentation','LICENSE'=>'License file',
        'PHPMailer/src/PHPMailer.php'=>'PHPMailer library','phpmailer/phpmailer/src/PHPMailer.php'=>'PHPMailer Composer package',
        'global/code/general.php'=>'Form Tools application','web/viewer.html'=>'PDF.js-style viewer','viewer.html'=>'Viewer front end',
        'jquery.min.js'=>'jQuery','bootstrap.min.css'=>'Bootstrap CSS','bootstrap.min.js'=>'Bootstrap JavaScript'
    ];
    foreach($checks as $file=>$desc){ if(is_file($dir.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$file))) $found[]=$desc.' ('.$file.')'; }
    return array_values(array_unique($found));
}
function inventory_dir(string $dir,string $root,array $skipPatterns): array {
    $out=['exists'=>is_dir($dir),'files'=>0,'dirs'=>0,'bytes'=>0,'latest_mtime'=>0,'latest_file'=>'','top_files'=>[],'signatures'=>[],'errors'=>[]];
    if(!$out['exists']) return $out; $out['signatures']=detect_signatures($dir);
    try{
        $it=new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),function($current) use($skipPatterns){
            if($current->isDir() && should_skip_dir($current->getPathname(),$skipPatterns)) return false; return true;
        }),RecursiveIteratorIterator::SELF_FIRST);
        $samples=[];
        foreach($it as $item){
            $path=$item->getPathname(); if($item->isDir()){ $out['dirs']++; continue; } if(!$item->isFile()) continue;
            $out['files']++; $sz=$item->getSize(); $mt=$item->getMTime(); $out['bytes']+=$sz;
            if($mt>$out['latest_mtime']){ $out['latest_mtime']=$mt; $out['latest_file']=relpath($path,$root); }
            $samples[]=['path'=>relpath($path,$root),'size'=>$sz,'mtime'=>$mt];
        }
        usort($samples,fn($a,$b)=>$b['mtime']<=>$a['mtime']); $out['top_files']=array_slice($samples,0,12);
    }catch(Throwable $e){ $out['errors'][]=$e->getMessage(); }
    return $out;
}
function text_ext_ok(string $path,array $exts): bool { return in_array(strtolower(pathinfo($path,PATHINFO_EXTENSION)),$exts,true); }
function line_snippet(string $content,int $pos,int $radius=90): string {
    $start=max(0,$pos-$radius); $len=min(strlen($content)-$start,$radius*2); $s=substr($content,$start,$len); return trim((string)preg_replace('/\\s+/',' ',$s));
}
function scan_inbound_references(string $root,array $targets,array $exts,array $skipPatterns,array $oldPatterns): array {
    $results=[]; foreach($targets as $t) $results[$t]=['active_like'=>[],'old_like'=>[],'counts'=>['active_like'=>0,'old_like'=>0]];
    $targetPrefixes=[]; foreach($targets as $t) $targetPrefixes[]=rtrim(str_replace('\\','/',$root),'/').'/'.$t.'/';
    $filter=new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),function($current) use($skipPatterns,$targetPrefixes){
        $path=str_replace('\\','/',$current->getPathname());
        if($current->isDir()){
            foreach($targetPrefixes as $prefix) if(rtrim($path,'/').'/'===$prefix) return false;
            if(should_skip_dir($path,$skipPatterns)) return false;
        }
        return true;
    });
    $it=new RecursiveIteratorIterator($filter);
    foreach($it as $item){
        if(!$item->isFile()) continue; $path=$item->getPathname(); if(!text_ext_ok($path,$exts)) continue;
        $content=safe_file_get($path); if($content===null) continue; $rel=relpath($path,$root); $bucket=is_old_like($rel,$oldPatterns)?'old_like':'active_like';
        foreach($targets as $t){
            $patterns=['#(?<![A-Za-z0-9_])/'.$t.'/#i','#(?<![A-Za-z0-9_])(?:\\.\\.?/)*'.$t.'/#i','#https?://[^\'"\\s<>]*/'.$t.'/#i'];
            $matched=false; $pos=0;
            foreach($patterns as $p){ if(preg_match($p,$content,$m,PREG_OFFSET_CAPTURE)){ $pos=$m[0][1]; $matched=true; break; } }
            if(!$matched) continue; $results[$t]['counts'][$bucket]++;
            if(count($results[$t][$bucket])<40) $results[$t][$bucket][]=['file'=>$rel,'snippet'=>line_snippet($content,$pos)];
        }
    }
    return $results;
}
function wp_db_scan(string $root,array $targets): array {
    $result=['available'=>false,'error'=>'','targets'=>[]]; foreach($targets as $t) $result['targets'][$t]=[];
    $wpLoad=$root.DIRECTORY_SEPARATOR.'wp-load.php'; if(!is_file($wpLoad)){ $result['error']='wp-load.php not found in public_html.'; return $result; }
    try{
        if(!defined('SHORTINIT')) define('SHORTINIT',true); require_once $wpLoad; global $wpdb;
        if(!isset($wpdb)||!is_object($wpdb)){ $result['error']='WordPress loaded, but $wpdb was unavailable.'; return $result; }
        $result['available']=true;
        $tables=['posts'=>[$wpdb->posts,['post_title','post_content','post_excerpt']],'postmeta'=>[$wpdb->postmeta,['meta_key','meta_value']],'options'=>[$wpdb->options,['option_name','option_value']]];
        foreach($targets as $t){
            $needle='%/'.$wpdb->esc_like($t).'/%';
            foreach($tables as $label=>[$table,$cols]){
                $where=[]; foreach($cols as $c) $where[]="$c LIKE %s"; $sql="SELECT * FROM $table WHERE ".implode(' OR ',$where)." LIMIT 100"; $args=array_fill(0,count($cols),$needle);
                $rows=$wpdb->get_results($wpdb->prepare($sql,...$args),ARRAY_A);
                foreach($rows as $row){
                    $summary=''; foreach($cols as $c){ if(isset($row[$c])&&stripos((string)$row[$c],'/'.$t.'/')!==false){ $summary=$c.': '.preg_replace('/\\s+/',' ',substr((string)$row[$c],0,240)); break; } }
                    $result['targets'][$t][]=['table'=>$label,'id'=>$row['ID']??$row['meta_id']??$row['option_id']??'','summary'=>$summary];
                }
            }
        }
    }catch(Throwable $e){ $result['error']=$e->getMessage(); }
    return $result;
}

$doScan=isset($_GET['scan'])||isset($_GET['export']); $report=null;
if($doScan){
    $inventory=[]; foreach($targets as $t) $inventory[$t]=inventory_dir($root.DIRECTORY_SEPARATOR.$t,$root,$skipDirPatterns);
    $refs=scan_inbound_references($root,$targets,$textExts,$skipDirPatterns,$oldLikePatterns); $wp=wp_db_scan($root,$targets);
    $report=['tool'=>'MRL Highlighted Folder Usage Scanner','version'=>TOOL_VERSION,'generated_at'=>date(DATE_ATOM),'root'=>$root,'read_only'=>true,'targets'=>$targets,'inventory'=>$inventory,'references'=>$refs,'wordpress'=>$wp];
    if(($_GET['export']??'')==='json'){
        header('Content-Type: application/json; charset=UTF-8'); header('Content-Disposition: attachment; filename="MRL_highlighted_folder_usage_scan_'.date('Ymd_His').'.json"');
        echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); exit;
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MRL Highlighted Folder Usage Scanner</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}.wrap{width:min(1240px,96%);margin:14px auto 30px}h1{margin:0 0 10px;color:var(--gold);font-size:27px}h2{margin:0 0 9px;color:var(--gold);font-size:18px}h3{margin:12px 0 7px;color:#efd39b;font-size:15px}.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.card{padding:10px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}.value{display:block;margin-top:3px;font-size:18px;font-weight:800}.small{font-size:12px;color:var(--muted)}.actions{display:flex;gap:9px;flex-wrap:wrap}.btn,button{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}.scan{background:#2674a8}.export{background:#bd8320}table{width:100%;border-collapse:collapse}th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}th{background:#202624;color:var(--gold)}code{color:#f8d89a;overflow-wrap:anywhere}.good{color:#8fe0a9;font-weight:800}.bad{color:#ffb3b3;font-weight:800}details{margin-top:7px}summary{cursor:pointer;color:#e9cc92;font-weight:700}.snip{font-family:Consolas,monospace;font-size:12px;color:#cfd5d2;word-break:break-word}@media(max-width:900px){.grid{grid-template-columns:1fr}}
</style></head><body><div class="wrap"><h1>MRL Highlighted Folder Usage Scanner</h1>
<div class="readonly">READ ONLY — inventories and traces <code>/css</code>, <code>/formtools</code>, <code>/js</code>, <code>/mailer</code>, <code>/vendor</code>, and <code>/viewer</code>. No files or database rows are changed.</div>
<div class="panel"><h2>Purpose</h2><p>This pass answers two things for each highlighted folder: <strong>what is it?</strong> and <strong>is anything still using it?</strong></p><p class="small">File references are split into normal/active-looking paths versus obvious backup/test/archive paths. WordPress content is also searched when WordPress can be loaded.</p><div class="actions"><form method="get" style="margin:0"><input type="hidden" name="scan" value="1"><button class="scan" type="submit"><?php echo $report?'Rescan Highlighted Folders':'Run Folder Usage Scan'; ?></button></form><?php if($report): ?><a class="btn export" href="?export=json">Export JSON Report</a><?php endif; ?></div></div>
<?php if($report): foreach($targets as $t): $inv=$report['inventory'][$t]; $ref=$report['references'][$t]; $wpRows=$report['wordpress']['targets'][$t]??[]; $active=$ref['counts']['active_like']; $old=$ref['counts']['old_like']; ?>
<div class="panel"><h2>/<?php echo h($t); ?>/</h2><div class="grid"><div class="card"><span class="small">Exists</span><span class="value <?php echo $inv['exists']?'good':'bad'; ?>"><?php echo $inv['exists']?'YES':'NO'; ?></span></div><div class="card"><span class="small">Contents</span><span class="value"><?php echo h($inv['files']); ?> files / <?php echo h($inv['dirs']); ?> dirs</span><span class="small"><?php echo h(fmt_bytes((int)$inv['bytes'])); ?></span></div><div class="card"><span class="small">Inbound refs</span><span class="value"><?php echo h($active); ?> active-like</span><span class="small"><?php echo h($old); ?> old/test/backup-like; <?php echo h(count($wpRows)); ?> WordPress DB</span></div></div>
<?php if($inv['signatures']): ?><h3>Recognized signatures</h3><ul><?php foreach($inv['signatures'] as $s): ?><li><?php echo h($s); ?></li><?php endforeach; ?></ul><?php endif; ?>
<?php if($inv['latest_mtime']): ?><p><strong>Most recently modified file:</strong> <code><?php echo h($inv['latest_file']); ?></code> — <?php echo h(date('Y-m-d H:i:s T',$inv['latest_mtime'])); ?></p><?php endif; ?>
<details><summary>Newest files inside this folder</summary><table><tr><th>File</th><th>Size</th><th>Modified</th></tr><?php foreach($inv['top_files'] as $f): ?><tr><td><code><?php echo h($f['path']); ?></code></td><td><?php echo h(fmt_bytes((int)$f['size'])); ?></td><td><?php echo h(date('Y-m-d H:i:s T',$f['mtime'])); ?></td></tr><?php endforeach; ?></table></details>
<details <?php echo $active>0?'open':''; ?>><summary>Active-looking file references (<?php echo h($active); ?>)</summary><?php if(!$ref['active_like']): ?><p class="small">None found.</p><?php else: ?><table><tr><th>Referring file</th><th>Snippet</th></tr><?php foreach($ref['active_like'] as $r): ?><tr><td><code><?php echo h($r['file']); ?></code></td><td class="snip"><?php echo h($r['snippet']); ?></td></tr><?php endforeach; ?></table><?php endif; ?></details>
<details><summary>Old/test/backup-like references (<?php echo h($old); ?>)</summary><?php if(!$ref['old_like']): ?><p class="small">None found.</p><?php else: ?><table><tr><th>Referring file</th><th>Snippet</th></tr><?php foreach($ref['old_like'] as $r): ?><tr><td><code><?php echo h($r['file']); ?></code></td><td class="snip"><?php echo h($r['snippet']); ?></td></tr><?php endforeach; ?></table><?php endif; ?></details>
<details><summary>WordPress database references (<?php echo h(count($wpRows)); ?>)</summary><?php if(!$report['wordpress']['available']): ?><p class="small">WordPress DB scan unavailable: <?php echo h($report['wordpress']['error']); ?></p><?php elseif(!$wpRows): ?><p class="small">None found.</p><?php else: ?><table><tr><th>Table</th><th>ID</th><th>Summary</th></tr><?php foreach($wpRows as $r): ?><tr><td><?php echo h($r['table']); ?></td><td><?php echo h($r['id']); ?></td><td class="snip"><?php echo h($r['summary']); ?></td></tr><?php endforeach; ?></table><?php endif; ?></details></div>
<?php endforeach; ?><div class="panel"><h2>How to read this</h2><p><strong>Active-looking reference</strong> means something outside the folder currently points at it and deserves review. <strong>Old/test/backup-like</strong> means the reference came from an obviously historical/support path and is weaker evidence of active use.</p><p class="small">The scanner deliberately does not delete anything and does not decide based on folder age alone.</p></div><?php endif; ?>
<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 11:25:00 am EDT</div></div></body></html>
