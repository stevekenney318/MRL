<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Root /css + /js Targeted Usage Scanner
 *
 * VERSION: v003
 * CREATED: 9/17/2026 2:15:00 am EDT
 *
 * READ-ONLY.
 *
 * Ultra-light diagnostic:
 * - scans ONE target at a time: css OR js
 * - scans root-level files plus files directly inside a few current MRL folders
 * - NO recursion
 * - NO WordPress scan
 * - NO database
 * - NO snapshot scan
 * - NO writes
 */

date_default_timezone_set('America/New_York');
@ini_set('display_errors', '1');
error_reporting(E_ALL);

const TOOL_VERSION = 'v003';
const MAX_TEXT_BYTES = 524288;

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
if (($rr = realpath($root)) !== false) $root = $rr;

$allowedTargets = ['css','js'];
$target = isset($_GET['target']) && in_array($_GET['target'],$allowedTargets,true) ? $_GET['target'] : '';

$scanDirs = ['', 'mrl_team', 'team_charts', 'league_info', 'race_results'];
$textExts = ['php','html','htm','js','css','json','txt','md'];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function pjoin(string $a,string $b): string {
    if ($b === '') return rtrim($a,'/\\');
    return rtrim($a,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$b);
}

function relpath(string $path,string $root): string {
    $p=str_replace('\\','/',$path);
    $r=rtrim(str_replace('\\','/',$root),'/');
    return stripos($p,$r.'/')===0 ? substr($p,strlen($r)+1) : $p;
}

function text_ok(string $path,array $exts): bool {
    return in_array(strtolower(pathinfo($path,PATHINFO_EXTENSION)),$exts,true);
}

function read_small(string $path): ?string {
    if(!is_file($path) || !is_readable($path)) return null;
    $size=@filesize($path);
    if($size===false || $size>MAX_TEXT_BYTES) return null;
    $data=@file_get_contents($path);
    return $data===false ? null : $data;
}

function snippet(string $content,int $pos): string {
    $start=max(0,$pos-100);
    $piece=substr($content,$start,200);
    return trim((string)preg_replace('/\s+/',' ',$piece));
}

function direct_files(string $root,array $dirs,array $exts): array {
    $files=[];
    foreach($dirs as $dirRel){
        $dir=pjoin($root,$dirRel);
        if(!is_dir($dir)) continue;

        try{
            $it=new DirectoryIterator($dir);
            foreach($it as $item){
                if($item->isDot() || !$item->isFile()) continue;
                $path=$item->getPathname();
                if(!text_ok($path,$exts)) continue;
                $files[relpath($path,$root)]=$path;
            }
        }catch(Throwable $e){
            // Ignore one bad directory and keep going.
        }
    }
    ksort($files,SORT_NATURAL|SORT_FLAG_CASE);
    return $files;
}

function target_files(string $root,string $target): array {
    $dir=pjoin($root,$target);
    $rows=[];
    if(!is_dir($dir)) return $rows;

    try{
        $it=new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS)
        );
        foreach($it as $item){
            if(!$item->isFile()) continue;
            $rows[]=[
                'path'=>relpath($item->getPathname(),$root),
                'basename'=>$item->getBasename(),
                'size'=>(int)$item->getSize(),
                'mtime'=>(int)$item->getMTime(),
            ];
        }
    }catch(Throwable $e){}
    return $rows;
}

function scan_usage(string $root,string $target,array $scanFiles,array $targetFiles): array {
    $rows=[];
    $basenames=[];
    foreach($targetFiles as $f) $basenames[strtolower($f['basename'])]=$f['path'];

    foreach($scanFiles as $rel=>$path){
        $content=read_small($path);
        if($content===null) continue;

        // Very explicit root-relative references.
        $needle='/'.$target.'/';
        $pos=stripos($content,$needle);
        if($pos!==false){
            $rows[]=[
                'type'=>'ROOT PATH',
                'file'=>$rel,
                'match'=>$needle,
                'target'=>'',
                'snippet'=>snippet($content,$pos),
            ];
        }

        // Simple relative references from a root-level file only:
        // css/foo.css or js/foo.js.
        if(strpos($rel,'/')===false){
            $needle2=$target.'/';
            $pos2=stripos($content,$needle2);
            if($pos2!==false){
                $dup=false;
                foreach($rows as $r){
                    if($r['file']===$rel && $r['type']==='ROOT PATH'){ $dup=true; break; }
                }
                if(!$dup){
                    $rows[]=[
                        'type'=>'ROOT-FILE RELATIVE PATH',
                        'file'=>$rel,
                        'match'=>$needle2,
                        'target'=>'',
                        'snippet'=>snippet($content,$pos2),
                    ];
                }
            }
        }

        // Exact filenames from target folder.
        foreach($basenames as $base=>$targetPath){
            $p=stripos($content,$base);
            if($p===false) continue;

            $already=false;
            foreach($rows as $r){
                if($r['file']===$rel && stripos($r['snippet'],$base)!==false){
                    $already=true; break;
                }
            }
            if($already) continue;

            $rows[]=[
                'type'=>'EXACT FILENAME',
                'file'=>$rel,
                'match'=>$base,
                'target'=>$targetPath,
                'snippet'=>snippet($content,$p),
            ];
        }
    }

    return $rows;
}

$report=null;
if($target!==''){
    $scanFiles=direct_files($root,$scanDirs,$textExts);
    $targetFiles=target_files($root,$target);
    $matches=scan_usage($root,$target,$scanFiles,$targetFiles);

    $report=[
        'tool'=>'MRL Root CSS JS Targeted Usage Scanner',
        'version'=>TOOL_VERSION,
        'generated_at'=>date(DATE_ATOM),
        'root'=>$root,
        'read_only'=>true,
        'target'=>$target,
        'scan_scope'=>$scanDirs,
        'files_examined'=>count($scanFiles),
        'target_files'=>$targetFiles,
        'matches'=>$matches,
    ];
}

if(isset($_GET['export']) && $_GET['export']==='json' && $report){
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="MRL_root_'.$target.'_targeted_usage_'.date('Ymd_His').'.json"');
    echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Root CSS + JS Targeted Usage Scanner v003</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1120px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.btn{display:inline-block;padding:8px 13px;margin-right:8px;border-radius:7px;text-decoration:none;color:#fff;background:#2674a8;font-weight:800}
.btn.current{background:#2f7f53}.export{background:#bd8320}
.small{font-size:12px;color:var(--muted)}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
code{color:#f8d89a;overflow-wrap:anywhere}
.good{color:#8fe0a9;font-weight:800}.warn{color:#ffd391;font-weight:800}
.snip{font-family:Consolas,monospace;font-size:12px;color:#cfd5d2;word-break:break-word}
</style>
</head>
<body><div class="wrap">
<h1>MRL Root /css + /js Targeted Usage Scanner v003</h1>
<div class="readonly">READ ONLY — one target at a time, no recursive site scan.</div>

<div class="panel">
<h2>Choose a target</h2>
<p>This version only checks root files and files directly inside <code>/mrl_team/</code>, <code>/team_charts/</code>, <code>/league_info/</code>, and <code>/race_results/</code>.</p>
<a class="btn <?php echo $target==='css'?'current':''; ?>" href="?target=css">Scan /css/</a>
<a class="btn <?php echo $target==='js'?'current':''; ?>" href="?target=js">Scan /js/</a>
<?php if($report): ?><a class="btn export" href="?target=<?php echo h($target); ?>&export=json">Export JSON</a><?php endif; ?>
</div>

<?php if($report): ?>
<div class="panel">
<h2>/<?php echo h($target); ?>/ result</h2>
<p><strong><?php echo h($report['files_examined']); ?></strong> current-code files examined. <strong><?php echo count($report['target_files']); ?></strong> files exist inside root <code>/<?php echo h($target); ?>/</code>.</p>

<?php if(!$report['matches']): ?>
<p class="warn"><strong>No targeted references found.</strong></p>
<?php else: ?>
<table>
<tr><th>Type</th><th>Referring file</th><th>Match</th><th>Target</th><th>Snippet</th></tr>
<?php foreach($report['matches'] as $r): ?>
<tr>
<td><?php echo h($r['type']); ?></td>
<td><code><?php echo h($r['file']); ?></code></td>
<td><code><?php echo h($r['match']); ?></code></td>
<td><code><?php echo h($r['target']); ?></code></td>
<td class="snip"><?php echo h($r['snippet']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div class="panel">
<h2>Files inside root /<?php echo h($target); ?>/</h2>
<table><tr><th>File</th><th>Size</th><th>Modified</th></tr>
<?php foreach($report['target_files'] as $f): ?>
<tr><td><code><?php echo h($f['path']); ?></code></td><td><?php echo h(fmt_bytes((int)$f['size'])); ?></td><td><?php echo h(date('Y-m-d H:i:s T',(int)$f['mtime'])); ?></td></tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<div class="panel small">No WordPress scan. No database access. No file changes.</div>
<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v003 | CREATED: 9/17/2026 2:15:00 am EDT</div>
</div></body></html>
