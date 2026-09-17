<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Highlighted Folder Usage Scanner
 *
 * VERSION: v002
 * CREATED: 9/16/2026 11:35:00 am EDT
 *
 * READ-ONLY.
 *
 * v002 changes:
 * - Removes WordPress bootstrap/database scan from the initial diagnostic.
 * - Scans one highlighted folder at a time to avoid a heavy all-site pass.
 * - Excludes known backup/quarantine trees from the active scan.
 * - Adds visible error reporting and bounded file/content limits.
 * - No writes, deletes, renames, or database access.
 */

date_default_timezone_set('America/New_York');
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

const TOOL_VERSION = 'v002';
const MAX_SCAN_FILES = 12000;
const MAX_TEXT_BYTES = 1048576; // 1 MB per candidate file
const MAX_MATCH_ROWS = 80;

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$realRoot = realpath($root);
if ($realRoot !== false) $root = $realRoot;

$targets = ['css','formtools','js','mailer','vendor','viewer'];
$selected = isset($_GET['target']) ? (string)$_GET['target'] : '';

$textExts = ['php','phtml','inc','html','htm','js','css','json','xml','txt','md','ini','conf','config','yml','yaml'];

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function pjoin(string $a,string $b): string {
    return rtrim($a,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$b);
}
function relpath(string $path,string $root): string {
    $p=str_replace('\\','/',$path);
    $r=rtrim(str_replace('\\','/',$root),'/');
    return stripos($p,$r.'/')===0 ? substr($p,strlen($r)+1) : $p;
}
function fmt_bytes(int $bytes): string {
    if($bytes<1024) return $bytes.' B';
    $units=['KB','MB','GB']; $v=(float)$bytes;
    foreach($units as $u){
        $v/=1024;
        if($v<1024 || $u==='GB') return number_format($v,$v>=100?0:($v>=10?1:2)).' '.$u;
    }
    return $bytes.' B';
}
function is_skip_path(string $rel): bool {
    $r='/'.ltrim(str_replace('\\','/',$rel),'/');
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
function is_old_like(string $rel): bool {
    $r='/'.ltrim(str_replace('\\','/',$rel),'/');
    $patterns=[
        '#/To_Be_Deleted_[^/]*(?:/|$)#i',
        '#/[^/]*backup[^/]*(?:/|$)#i',
        '#/archive[^/]*(?:/|$)#i',
        '#/old[^/]*(?:/|$)#i',
        '#/test[^/]*(?:/|$)#i',
        '#/sandbox[^/]*(?:/|$)#i',
    ];
    foreach($patterns as $p) if(preg_match($p,$r)) return true;
    return false;
}
function text_candidate(string $path,array $exts): bool {
    $ext=strtolower(pathinfo($path,PATHINFO_EXTENSION));
    return in_array($ext,$exts,true);
}
function read_small_text(string $path): ?string {
    if(!is_file($path) || !is_readable($path)) return null;
    $size=@filesize($path);
    if($size===false || $size>MAX_TEXT_BYTES) return null;
    $data=@file_get_contents($path);
    return $data===false ? null : $data;
}
function snippet(string $s,int $pos): string {
    $start=max(0,$pos-110);
    $piece=substr($s,$start,220);
    return trim((string)preg_replace('/\s+/',' ',$piece));
}
function detect_signatures(string $dir): array {
    $checks=[
        'composer.json'=>'Composer project/package metadata',
        'composer.lock'=>'Composer dependency lock',
        'autoload.php'=>'PHP autoloader',
        'index.php'=>'PHP application entry point',
        'index.html'=>'HTML application entry point',
        'README.md'=>'README',
        'README.txt'=>'README',
        'PHPMailer.php'=>'PHPMailer file',
        'src/PHPMailer.php'=>'PHPMailer library',
        'phpmailer/phpmailer/src/PHPMailer.php'=>'PHPMailer Composer package',
        'global/code/general.php'=>'Form Tools application marker',
        'web/viewer.html'=>'PDF.js-style viewer marker',
        'viewer.html'=>'Viewer front end',
        'jquery.min.js'=>'jQuery',
    ];
    $out=[];
    foreach($checks as $rel=>$desc){
        if(is_file(pjoin($dir,$rel))) $out[]=$desc.' ('.$rel.')';
    }
    return $out;
}
function inventory_folder(string $root,string $target): array {
    $dir=pjoin($root,$target);
    $r=['exists'=>is_dir($dir),'files'=>0,'dirs'=>0,'bytes'=>0,'latest_mtime'=>0,'latest_file'=>'','samples'=>[],'signatures'=>[],'error'=>''];
    if(!$r['exists']) return $r;
    $r['signatures']=detect_signatures($dir);

    try{
        $it=new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $newest=[];
        foreach($it as $item){
            if($item->isDir()){ $r['dirs']++; continue; }
            if(!$item->isFile()) continue;
            $r['files']++;
            $sz=(int)$item->getSize();
            $mt=(int)$item->getMTime();
            $r['bytes']+=$sz;
            if($mt>$r['latest_mtime']){
                $r['latest_mtime']=$mt;
                $r['latest_file']=relpath($item->getPathname(),$root);
            }
            $newest[]=['path'=>relpath($item->getPathname(),$root),'size'=>$sz,'mtime'=>$mt];
        }
        usort($newest,function($a,$b){ return $b['mtime']<=>$a['mtime']; });
        $r['samples']=array_slice($newest,0,15);
    }catch(Throwable $e){
        $r['error']=$e->getMessage();
    }
    return $r;
}
function scan_refs(string $root,string $target,array $exts): array {
    $out=['files_examined'=>0,'limit_hit'=>false,'active'=>[],'old'=>[],'error'=>''];

    try{
        $it=new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach($it as $item){
            if(!$item->isFile()) continue;

            $path=$item->getPathname();
            $rel=relpath($path,$root);

            // Don't scan inside the target itself, backups/quarantine, or this scanner.
            if(stripos(str_replace('\\','/',$rel),$target.'/')===0) continue;
            if(is_skip_path($rel)) continue;
            if(!text_candidate($path,$exts)) continue;

            $out['files_examined']++;
            if($out['files_examined']>MAX_SCAN_FILES){
                $out['limit_hit']=true;
                break;
            }

            $content=read_small_text($path);
            if($content===null) continue;

            $patterns=[
                '#(?<![A-Za-z0-9_])/'.$target.'/#i',
                '#(?<![A-Za-z0-9_])(?:\.\.?/)+'.$target.'/#i',
                '#https?://[^\'"\s<>]*/'.$target.'/#i',
                '#(?<![A-Za-z0-9_])'.$target.'/[A-Za-z0-9_.-]+#i',
            ];

            $found=false; $pos=0;
            foreach($patterns as $p){
                if(preg_match($p,$content,$m,PREG_OFFSET_CAPTURE)){
                    $found=true;
                    $pos=(int)$m[0][1];
                    break;
                }
            }
            if(!$found) continue;

            $bucket=is_old_like($rel)?'old':'active';
            if(count($out[$bucket])<MAX_MATCH_ROWS){
                $out[$bucket][]=['file'=>$rel,'snippet'=>snippet($content,$pos)];
            }
        }
    }catch(Throwable $e){
        $out['error']=$e->getMessage();
    }

    return $out;
}

$report=null;
if($selected!=='' && in_array($selected,$targets,true)){
    $report=[
        'tool'=>'MRL Highlighted Folder Usage Scanner',
        'version'=>TOOL_VERSION,
        'generated_at'=>date(DATE_ATOM),
        'root'=>$root,
        'read_only'=>true,
        'target'=>$selected,
        'inventory'=>inventory_folder($root,$selected),
        'references'=>scan_refs($root,$selected,$textExts),
    ];
}

if(isset($_GET['export']) && $_GET['export']==='json' && $report){
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="MRL_highlighted_folder_usage_'.$selected.'_'.date('Ymd_His').'.json"');
    echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Highlighted Folder Usage Scanner v002</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1180px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.targets{display:flex;gap:8px;flex-wrap:wrap}
.btn{display:inline-block;padding:8px 12px;border-radius:7px;text-decoration:none;color:#fff;background:#2674a8;font-weight:800}
.btn.current{background:#2f7f53}
.export{background:#bd8320}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}
.card{padding:10px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:18px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
.good{color:#8fe0a9;font-weight:800}.warn{color:#ffd391;font-weight:800}.bad{color:#ffb3b3;font-weight:800}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
code{color:#f8d89a;overflow-wrap:anywhere}
.snip{font-family:Consolas,monospace;font-size:12px;color:#cfd5d2;word-break:break-word}
details{margin-top:9px}
summary{cursor:pointer;color:#e9cc92;font-weight:700}
@media(max-width:850px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">
<h1>MRL Highlighted Folder Usage Scanner v002</h1>

<div class="readonly">READ ONLY — no writes, deletes, renames, or database access.</div>

<div class="panel">
<h2>Scan one highlighted folder at a time</h2>
<p>v002 intentionally makes a lighter pass than v001. Pick a folder below; each scan inventories that folder and searches the rest of <code>public_html</code> for inbound references.</p>
<div class="targets">
<?php foreach($targets as $t): ?>
<a class="btn <?php echo $selected===$t?'current':''; ?>" href="?target=<?php echo urlencode($t); ?>">/<?php echo h($t); ?>/</a>
<?php endforeach; ?>
<?php if($report): ?><a class="btn export" href="?target=<?php echo urlencode($selected); ?>&export=json">Export This Result</a><?php endif; ?>
</div>
</div>

<?php if($report):
$inv=$report['inventory']; $refs=$report['references'];
?>
<div class="panel">
<h2>/<?php echo h($selected); ?>/</h2>
<div class="grid">
<div class="card"><span class="small">Exists</span><span class="value <?php echo $inv['exists']?'good':'bad'; ?>"><?php echo $inv['exists']?'YES':'NO'; ?></span></div>
<div class="card"><span class="small">Contents</span><span class="value"><?php echo h($inv['files']); ?> files</span><span class="small"><?php echo h($inv['dirs']); ?> directories · <?php echo h(fmt_bytes((int)$inv['bytes'])); ?></span></div>
<div class="card"><span class="small">Active-looking refs</span><span class="value"><?php echo h(count($refs['active'])); ?></span></div>
<div class="card"><span class="small">Old/test refs</span><span class="value"><?php echo h(count($refs['old'])); ?></span></div>
</div>

<?php if($inv['signatures']): ?>
<p><strong>Recognized:</strong> <?php echo h(implode(' · ',$inv['signatures'])); ?></p>
<?php endif; ?>

<?php if($inv['latest_mtime']): ?>
<p><strong>Newest file:</strong> <code><?php echo h($inv['latest_file']); ?></code> — <?php echo h(date('Y-m-d H:i:s T',(int)$inv['latest_mtime'])); ?></p>
<?php endif; ?>

<p class="small">Files examined outside this folder: <?php echo h($refs['files_examined']); ?><?php echo $refs['limit_hit']?' — scan limit reached':''; ?></p>

<?php if($inv['error']): ?><p class="bad">Inventory error: <?php echo h($inv['error']); ?></p><?php endif; ?>
<?php if($refs['error']): ?><p class="bad">Reference-scan error: <?php echo h($refs['error']); ?></p><?php endif; ?>

<details open>
<summary>Active-looking references (<?php echo h(count($refs['active'])); ?>)</summary>
<?php if(!$refs['active']): ?><p class="small">None found.</p><?php else: ?>
<table><tr><th>Referring file</th><th>Snippet</th></tr>
<?php foreach($refs['active'] as $r): ?><tr><td><code><?php echo h($r['file']); ?></code></td><td class="snip"><?php echo h($r['snippet']); ?></td></tr><?php endforeach; ?>
</table><?php endif; ?>
</details>

<details>
<summary>Old/test/archive-like references (<?php echo h(count($refs['old'])); ?>)</summary>
<?php if(!$refs['old']): ?><p class="small">None found.</p><?php else: ?>
<table><tr><th>Referring file</th><th>Snippet</th></tr>
<?php foreach($refs['old'] as $r): ?><tr><td><code><?php echo h($r['file']); ?></code></td><td class="snip"><?php echo h($r['snippet']); ?></td></tr><?php endforeach; ?>
</table><?php endif; ?>
</details>

<details>
<summary>Newest files inside /<?php echo h($selected); ?>/</summary>
<table><tr><th>File</th><th>Size</th><th>Modified</th></tr>
<?php foreach($inv['samples'] as $f): ?>
<tr><td><code><?php echo h($f['path']); ?></code></td><td><?php echo h(fmt_bytes((int)$f['size'])); ?></td><td><?php echo h(date('Y-m-d H:i:s T',(int)$f['mtime'])); ?></td></tr>
<?php endforeach; ?>
</table>
</details>
</div>
<?php endif; ?>

<div class="panel small">
v002 deliberately does not load WordPress. Once we know which folders have real filesystem references, we can do a targeted DB check only where it would add value.
</div>

<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v002 | CREATED: 9/16/2026 11:35:00 am EDT</div>
</div></body></html>
