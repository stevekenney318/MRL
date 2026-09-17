<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Root /css + /js Targeted Usage Scanner
 *
 * VERSION: v001
 * CREATED: 9/17/2026 1:55:00 am EDT
 *
 * READ-ONLY.
 *
 * Purpose:
 * - Determine whether references elsewhere on the site actually resolve to the
 *   TOP-LEVEL /css/ and /js/ folders.
 * - Avoid false positives from wp-admin/css, plugin /css, bootstrap/css,
 *   wp-admin/js, plugin /js, CDN URLs, etc.
 * - Search exact filenames from root /css and /js as a second signal.
 * - No database access and no file changes.
 */

date_default_timezone_set('America/New_York');
@ini_set('display_errors', '1');
error_reporting(E_ALL);

const TOOL_VERSION = 'v001';
const MAX_SCAN_FILES = 14000;
const MAX_TEXT_BYTES = 1048576;
const MAX_ROWS = 120;

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
if (($rr = realpath($root)) !== false) $root = $rr;

$targets = ['css','js'];
$textExts = ['php','phtml','inc','html','htm','js','css','json','xml','txt','md'];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pjoin(string $a,string $b): string { return rtrim($a,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$b); }
function relpath(string $path,string $root): string {
    $p=str_replace('\\','/',$path); $r=rtrim(str_replace('\\','/',$root),'/');
    return stripos($p,$r.'/')===0 ? substr($p,strlen($r)+1) : $p;
}
function norm_slashes(string $p): string { return str_replace('\\','/',$p); }
function fmt_bytes(int $bytes): string {
    if($bytes<1024) return $bytes.' B';
    $units=['KB','MB','GB']; $v=(float)$bytes;
    foreach($units as $u){ $v/=1024; if($v<1024||$u==='GB') return number_format($v,$v>=100?0:($v>=10?1:2)).' '.$u; }
    return $bytes.' B';
}
function is_skip_path(string $rel): bool {
    $r='/'.ltrim(norm_slashes($rel),'/');
    $patterns=[
        '#/_mrl_installer_backups(?:/|$)#i',
        '#/To_Be_Deleted_[^/]*(?:/|$)#i',
        '#/db_backups(?:/|$)#i',
        '#/[^/]*backup[^/]*(?:/|$)#i',
        '#/\.git(?:/|$)#i',
        '#/node_modules(?:/|$)#i',
        '#/mrl2_sandbox_site(?:/|$)#i',
    ];
    foreach($patterns as $p) if(preg_match($p,$r)) return true;
    return false;
}
function text_candidate(string $path,array $exts): bool {
    return in_array(strtolower(pathinfo($path,PATHINFO_EXTENSION)),$exts,true);
}
function read_small(string $path): ?string {
    if(!is_file($path)||!is_readable($path)) return null;
    $s=@filesize($path);
    if($s===false||$s>MAX_TEXT_BYTES) return null;
    $d=@file_get_contents($path);
    return $d===false?null:$d;
}
function snippet(string $content,int $pos): string {
    $start=max(0,$pos-120);
    $s=substr($content,$start,240);
    return trim((string)preg_replace('/\s+/',' ',$s));
}
function normalize_url_path(string $urlPath): string {
    $parts=[];
    foreach(explode('/',str_replace('\\','/',$urlPath)) as $part){
        if($part===''||$part==='.') continue;
        if($part==='..'){ array_pop($parts); continue; }
        $parts[]=$part;
    }
    return implode('/',$parts);
}
function resolve_reference(string $ref,string $refFileRel): array {
    $raw=trim(html_entity_decode($ref,ENT_QUOTES|ENT_HTML5,'UTF-8'));

    if($raw==='' || str_starts_with($raw,'#')) return ['kind'=>'ignore','resolved'=>''];
    if(preg_match('#^(?:data|mailto|javascript|tel):#i',$raw)) return ['kind'=>'ignore','resolved'=>''];
    if(preg_match('#^https?://#i',$raw)) return ['kind'=>'external','resolved'=>''];

    $path=preg_split('/[?#]/',$raw,2)[0] ?? $raw;
    if($path==='') return ['kind'=>'ignore','resolved'=>''];

    if(str_starts_with($path,'//')) return ['kind'=>'external','resolved'=>''];

    if(str_starts_with($path,'/')){
        return ['kind'=>'site-root','resolved'=>normalize_url_path(ltrim($path,'/'))];
    }

    $baseDir=dirname(norm_slashes($refFileRel));
    if($baseDir==='.') $baseDir='';
    $combined=($baseDir!==''?$baseDir.'/':'').$path;
    return ['kind'=>'relative','resolved'=>normalize_url_path($combined)];
}
function extract_url_refs(string $content): array {
    $refs=[];

    $patterns=[
        '#\b(?:src|href)\s*=\s*(["\'])(.*?)\1#is',
        '#url\(\s*(["\']?)(.*?)\1\s*\)#is',
        '#\b(?:require|include|require_once|include_once)\s*(?:\(\s*)?(["\'])(.*?)\1#is',
    ];

    foreach($patterns as $p){
        if(preg_match_all($p,$content,$m,PREG_OFFSET_CAPTURE)){
            foreach($m[2] as $idx=>$cap){
                $refs[]=['ref'=>$cap[0],'pos'=>$cap[1]];
            }
        }
    }

    return $refs;
}
function target_inventory(string $root,string $target): array {
    $dir=pjoin($root,$target);
    $files=[];
    if(is_dir($dir)){
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
        foreach($it as $item){
            if(!$item->isFile()) continue;
            $files[]=[
                'rel'=>relpath($item->getPathname(),$root),
                'basename'=>$item->getBasename(),
                'size'=>(int)$item->getSize(),
                'mtime'=>(int)$item->getMTime()
            ];
        }
    }
    usort($files,fn($a,$b)=>strcmp($a['rel'],$b['rel']));
    return ['exists'=>is_dir($dir),'files'=>$files];
}
function scan_target(string $root,string $target,array $targetFiles,array $textExts): array {
    $result=[
        'files_examined'=>0,
        'limit_hit'=>false,
        'resolved_refs'=>[],
        'exact_name_refs'=>[],
        'unresolved_dynamic'=>[],
        'error'=>''
    ];

    $basenameMap=[];
    foreach($targetFiles as $f) $basenameMap[strtolower($f['basename'])]=$f['rel'];

    try{
        $it=new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach($it as $item){
            if(!$item->isFile()) continue;

            $path=$item->getPathname();
            $rel=relpath($path,$root);

            if(stripos(norm_slashes($rel),$target.'/')===0) continue;
            if(is_skip_path($rel)) continue;
            if(!text_candidate($path,$textExts)) continue;

            $result['files_examined']++;
            if($result['files_examined']>MAX_SCAN_FILES){
                $result['limit_hit']=true;
                break;
            }

            $content=read_small($path);
            if($content===null) continue;

            // 1) Resolve literal src/href/url()/include references.
            foreach(extract_url_refs($content) as $r){
                $ref=$r['ref'];

                // Dynamic PHP/JS fragments are not safely resolvable.
                if(str_contains($ref,'<?') || str_contains($ref,'${') || str_contains($ref,'"+') || str_contains($ref,"'+")){
                    if(stripos($ref,$target)!==false && count($result['unresolved_dynamic'])<MAX_ROWS){
                        $result['unresolved_dynamic'][]=['file'=>$rel,'ref'=>$ref,'snippet'=>snippet($content,$r['pos'])];
                    }
                    continue;
                }

                $resolved=resolve_reference($ref,$rel);
                if($resolved['kind']==='external'||$resolved['kind']==='ignore') continue;

                $rp=$resolved['resolved'];
                if(stripos($rp,$target.'/')===0){
                    if(count($result['resolved_refs'])<MAX_ROWS){
                        $result['resolved_refs'][]=[
                            'file'=>$rel,
                            'ref'=>$ref,
                            'resolved'=>$rp,
                            'exists'=>is_file(pjoin($root,$rp)),
                            'snippet'=>snippet($content,$r['pos'])
                        ];
                    }
                }
            }

            // 2) Search exact basenames of files in the root target folder.
            foreach($basenameMap as $base=>$targetRel){
                $pos=stripos($content,$base);
                if($pos===false) continue;

                // Avoid duplicate rows when the exact filename was already captured as a resolved ref.
                $dup=false;
                foreach($result['resolved_refs'] as $rr){
                    if($rr['file']===$rel && stripos($rr['resolved'],$base)!==false){ $dup=true; break; }
                }
                if($dup) continue;

                if(count($result['exact_name_refs'])<MAX_ROWS){
                    $result['exact_name_refs'][]=[
                        'file'=>$rel,
                        'filename'=>$base,
                        'target'=>$targetRel,
                        'snippet'=>snippet($content,$pos)
                    ];
                }
            }
        }
    } catch(Throwable $e){
        $result['error']=$e->getMessage();
    }

    return $result;
}

$run=isset($_GET['scan'])||isset($_GET['export']);
$report=null;

if($run){
    $data=[];
    foreach($targets as $target){
        $inv=target_inventory($root,$target);
        $scan=scan_target($root,$target,$inv['files'],$textExts);
        $data[$target]=['inventory'=>$inv,'scan'=>$scan];
    }

    $report=[
        'tool'=>'MRL Root CSS JS Targeted Usage Scanner',
        'version'=>TOOL_VERSION,
        'generated_at'=>date(DATE_ATOM),
        'root'=>$root,
        'read_only'=>true,
        'targets'=>$data
    ];

    if(($_GET['export']??'')==='json'){
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="MRL_root_css_js_targeted_usage_'.date('Ymd_His').'.json"');
        echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Root CSS + JS Targeted Usage Scanner</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1240px,96%);margin:14px auto 30px}h1{margin:0 0 10px;color:var(--gold);font-size:27px}h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}.card{padding:10px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:18px;font-weight:800}.small{font-size:12px;color:var(--muted)}
.btn,button{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}.scan{background:#2674a8}.export{background:#bd8320}
table{width:100%;border-collapse:collapse}th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}th{background:#202624;color:var(--gold)}
code{color:#f8d89a;overflow-wrap:anywhere}.good{color:#8fe0a9;font-weight:800}.warn{color:#ffd391;font-weight:800}.bad{color:#ffb3b3;font-weight:800}.snip{font-family:Consolas,monospace;font-size:12px;color:#cfd5d2;word-break:break-word}
details{margin-top:9px}summary{cursor:pointer;color:#e9cc92;font-weight:700}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">
<h1>MRL Root /css + /js Targeted Usage Scanner</h1>
<div class="readonly">READ ONLY — this pass only counts references that can actually resolve to top-level <code>/css/</code> or <code>/js/</code>.</div>

<div class="panel">
<h2>Why this is different</h2>
<p>The earlier broad scan matched any occurrence of words like <code>css/</code> and <code>js/</code>, so WordPress core/plugin paths created noise. This scanner resolves literal paths against the location of the referring file before deciding they point at the root folders.</p>
<p class="small">It also searches exact filenames from the root folders as a second signal, which helps catch unusual references that path resolution may not recognize.</p>
<form method="get"><input type="hidden" name="scan" value="1"><button class="scan" type="submit"><?php echo $report?'Rescan Root CSS + JS':'Run Targeted Scan'; ?></button>
<?php if($report): ?><a class="btn export" href="?export=json">Export JSON Report</a><?php endif; ?></form>
</div>

<?php if($report): foreach($targets as $target):
$d=$report['targets'][$target]; $inv=$d['inventory']; $s=$d['scan'];
?>
<div class="panel">
<h2>/<?php echo h($target); ?>/</h2>
<div class="grid">
<div class="card"><span class="small">Folder</span><span class="value <?php echo $inv['exists']?'good':'bad'; ?>"><?php echo $inv['exists']?'PRESENT':'MISSING'; ?></span></div>
<div class="card"><span class="small">Files inside</span><span class="value"><?php echo count($inv['files']); ?></span></div>
<div class="card"><span class="small">Confirmed resolving refs</span><span class="value <?php echo count($s['resolved_refs'])?'good':'warn'; ?>"><?php echo count($s['resolved_refs']); ?></span></div>
<div class="card"><span class="small">Exact-name-only refs</span><span class="value"><?php echo count($s['exact_name_refs']); ?></span></div>
</div>

<p class="small">Files examined outside /<?php echo h($target); ?>/: <?php echo h($s['files_examined']); ?><?php echo $s['limit_hit']?' — scan limit reached':''; ?></p>
<?php if($s['error']): ?><p class="bad">Scan error: <?php echo h($s['error']); ?></p><?php endif; ?>

<details open><summary>Confirmed references that resolve to root /<?php echo h($target); ?>/ (<?php echo count($s['resolved_refs']); ?>)</summary>
<?php if(!$s['resolved_refs']): ?><p class="small">None found.</p><?php else: ?>
<table><tr><th>Referring file</th><th>Literal reference</th><th>Resolved path</th><th>Target exists?</th><th>Snippet</th></tr>
<?php foreach($s['resolved_refs'] as $r): ?>
<tr><td><code><?php echo h($r['file']); ?></code></td><td><code><?php echo h($r['ref']); ?></code></td><td><code><?php echo h($r['resolved']); ?></code></td><td class="<?php echo $r['exists']?'good':'bad'; ?>"><?php echo $r['exists']?'YES':'NO'; ?></td><td class="snip"><?php echo h($r['snippet']); ?></td></tr>
<?php endforeach; ?></table><?php endif; ?></details>

<details><summary>Exact target filenames found elsewhere but not path-confirmed (<?php echo count($s['exact_name_refs']); ?>)</summary>
<?php if(!$s['exact_name_refs']): ?><p class="small">None found.</p><?php else: ?>
<table><tr><th>Referring file</th><th>Filename</th><th>Root target</th><th>Snippet</th></tr>
<?php foreach($s['exact_name_refs'] as $r): ?>
<tr><td><code><?php echo h($r['file']); ?></code></td><td><code><?php echo h($r['filename']); ?></code></td><td><code><?php echo h($r['target']); ?></code></td><td class="snip"><?php echo h($r['snippet']); ?></td></tr>
<?php endforeach; ?></table><?php endif; ?></details>

<details><summary>Dynamic references mentioning /<?php echo h($target); ?>/ that could not be resolved (<?php echo count($s['unresolved_dynamic']); ?>)</summary>
<?php if(!$s['unresolved_dynamic']): ?><p class="small">None found.</p><?php else: ?>
<table><tr><th>Referring file</th><th>Reference</th><th>Snippet</th></tr>
<?php foreach($s['unresolved_dynamic'] as $r): ?>
<tr><td><code><?php echo h($r['file']); ?></code></td><td><code><?php echo h($r['ref']); ?></code></td><td class="snip"><?php echo h($r['snippet']); ?></td></tr>
<?php endforeach; ?></table><?php endif; ?></details>

<details><summary>Files currently inside /<?php echo h($target); ?>/</summary>
<table><tr><th>Path</th><th>Size</th><th>Modified</th></tr>
<?php foreach($inv['files'] as $f): ?><tr><td><code><?php echo h($f['rel']); ?></code></td><td><?php echo h(fmt_bytes((int)$f['size'])); ?></td><td><?php echo h(date('Y-m-d H:i:s T',(int)$f['mtime'])); ?></td></tr><?php endforeach; ?>
</table></details>
</div>
<?php endforeach; endif; ?>

<div class="panel small">No database access. No file changes. This utility is intentionally narrower than the v002 broad scanner.</div>
<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/17/2026 1:55:00 am EDT</div>
</div></body></html>
