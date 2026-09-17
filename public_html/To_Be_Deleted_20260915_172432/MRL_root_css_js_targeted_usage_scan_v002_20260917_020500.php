<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Root /css + /js Targeted Usage Scanner
 *
 * VERSION: v002
 * CREATED: 9/17/2026 2:05:00 am EDT
 *
 * READ-ONLY.
 *
 * v002:
 * - Much lighter than v001.
 * - Scans only current custom MRL code locations.
 * - Does NOT recurse through WordPress core, plugins/themes, race-result snapshots,
 *   backups, quarantine, vendor libraries, or other large trees.
 * - Searches for exact root /css and /js filenames plus literal path references.
 * - No database access and no writes.
 */

date_default_timezone_set('America/New_York');
@ini_set('display_errors', '1');
error_reporting(E_ALL);

const TOOL_VERSION = 'v002';
const MAX_TEXT_BYTES = 524288; // 512 KB
const MAX_ROWS = 100;

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
if (($rr = realpath($root)) !== false) $root = $rr;

$targets = ['css','js'];
$textExts = ['php','phtml','inc','html','htm','js','css','json','xml','txt','md'];

// Deliberately narrow current-code scope.
$recursiveDirs = [
    'mrl_team',
    'team_charts',
    'league_info',
];

$oneLevelDirs = [
    'race_results',
];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pjoin(string $a,string $b): string { return rtrim($a,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$b); }
function relpath(string $path,string $root): string {
    $p=str_replace('\\','/',$path);
    $r=rtrim(str_replace('\\','/',$root),'/');
    return stripos($p,$r.'/')===0 ? substr($p,strlen($r)+1) : $p;
}
function fmt_bytes(int $bytes): string {
    if($bytes<1024) return $bytes.' B';
    $v=$bytes/1024;
    if($v<1024) return number_format($v,$v>=100?0:($v>=10?1:2)).' KB';
    $v/=1024;
    return number_format($v,$v>=100?0:($v>=10?1:2)).' MB';
}
function text_candidate(string $path,array $exts): bool {
    return in_array(strtolower(pathinfo($path,PATHINFO_EXTENSION)),$exts,true);
}
function read_small(string $path): ?string {
    if(!is_file($path) || !is_readable($path)) return null;
    $s=@filesize($path);
    if($s===false || $s>MAX_TEXT_BYTES) return null;
    $d=@file_get_contents($path);
    return $d===false?null:$d;
}
function snippet(string $content,int $pos): string {
    $start=max(0,$pos-120);
    $s=substr($content,$start,240);
    return trim((string)preg_replace('/\s+/',' ',$s));
}
function target_inventory(string $root,string $target): array {
    $dir=pjoin($root,$target);
    $files=[];
    if(!is_dir($dir)) return ['exists'=>false,'files'=>[]];

    $it=new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS)
    );
    foreach($it as $item){
        if(!$item->isFile()) continue;
        $files[]=[
            'rel'=>relpath($item->getPathname(),$root),
            'basename'=>$item->getBasename(),
            'size'=>(int)$item->getSize(),
            'mtime'=>(int)$item->getMTime(),
        ];
    }
    usort($files,fn($a,$b)=>strcmp($a['rel'],$b['rel']));
    return ['exists'=>true,'files'=>$files];
}
function add_file(array &$list,string $path,string $root,array $exts): void {
    if(!is_file($path) || !text_candidate($path,$exts)) return;
    $list[relpath($path,$root)]=$path;
}
function collect_scan_files(string $root,array $recursiveDirs,array $oneLevelDirs,array $exts): array {
    $files=[];

    // Root files only.
    foreach(new DirectoryIterator($root) as $item){
        if($item->isDot() || !$item->isFile()) continue;
        add_file($files,$item->getPathname(),$root,$exts);
    }

    // Fully recurse only a few current custom-code directories.
    foreach($recursiveDirs as $dirName){
        $dir=pjoin($root,$dirName);
        if(!is_dir($dir)) continue;
        $it=new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS)
        );
        foreach($it as $item){
            if($item->isFile()) add_file($files,$item->getPathname(),$root,$exts);
        }
    }

    // One level only for race_results; do not descend into year/snapshot trees.
    foreach($oneLevelDirs as $dirName){
        $dir=pjoin($root,$dirName);
        if(!is_dir($dir)) continue;
        foreach(new DirectoryIterator($dir) as $item){
            if($item->isDot() || !$item->isFile()) continue;
            add_file($files,$item->getPathname(),$root,$exts);
        }
    }

    ksort($files,SORT_NATURAL|SORT_FLAG_CASE);
    return $files;
}
function scan_target(string $root,string $target,array $targetFiles,array $scanFiles): array {
    $rows=[];
    $basenameMap=[];
    foreach($targetFiles as $f){
        $basenameMap[strtolower($f['basename'])]=$f['rel'];
    }

    foreach($scanFiles as $rel=>$path){
        $content=read_small($path);
        if($content===null) continue;

        // Exact root path references.
        $patterns=[
            '#(?<![A-Za-z0-9_])/'.$target.'/[A-Za-z0-9_./-]+#i',
            '#(?<![A-Za-z0-9_])(?:\.\./|\./)?'.$target.'/[A-Za-z0-9_./-]+#i',
        ];

        foreach($patterns as $p){
            if(preg_match_all($p,$content,$matches,PREG_OFFSET_CAPTURE)){
                foreach($matches[0] as $m){
                    $literal=$m[0];
                    $pos=(int)$m[1];

                    // Reject common nested paths like bootstrap/css or wp-admin/js:
                    // the regex only accepts target at token boundary, but ensure no slash directly before.
                    $before=$pos>0 ? $content[$pos-1] : '';
                    if($before==='/') continue;

                    $rows[]=[
                        'kind'=>'PATH',
                        'file'=>$rel,
                        'match'=>$literal,
                        'target'=>'',
                        'snippet'=>snippet($content,$pos),
                    ];
                    if(count($rows)>=MAX_ROWS) break 2;
                }
            }
        }

        if(count($rows)>=MAX_ROWS) break;

        // Exact filenames from the actual root target folder.
        foreach($basenameMap as $basename=>$targetRel){
            $pos=stripos($content,$basename);
            if($pos===false) continue;

            $dup=false;
            foreach($rows as $r){
                if($r['file']===$rel && stripos($r['match'],$basename)!==false){
                    $dup=true; break;
                }
            }
            if($dup) continue;

            $rows[]=[
                'kind'=>'FILENAME',
                'file'=>$rel,
                'match'=>$basename,
                'target'=>$targetRel,
                'snippet'=>snippet($content,$pos),
            ];
            if(count($rows)>=MAX_ROWS) break;
        }

        if(count($rows)>=MAX_ROWS) break;
    }

    return $rows;
}

$run=isset($_GET['scan']) || isset($_GET['export']);
$report=null;

if($run){
    $scanFiles=collect_scan_files($root,$recursiveDirs,$oneLevelDirs,$textExts);
    $data=[];

    foreach($targets as $target){
        $inv=target_inventory($root,$target);
        $rows=scan_target($root,$target,$inv['files'],$scanFiles);

        $data[$target]=[
            'inventory'=>$inv,
            'matches'=>$rows,
        ];
    }

    $report=[
        'tool'=>'MRL Root CSS JS Targeted Usage Scanner',
        'version'=>TOOL_VERSION,
        'generated_at'=>date(DATE_ATOM),
        'root'=>$root,
        'read_only'=>true,
        'scan_scope'=>[
            'root_files'=>true,
            'recursive_dirs'=>$recursiveDirs,
            'one_level_dirs'=>$oneLevelDirs,
            'wordpress_excluded'=>true,
            'race_snapshot_trees_excluded'=>true,
        ],
        'files_examined'=>count($scanFiles),
        'targets'=>$data,
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
<title>MRL Root CSS + JS Targeted Usage Scanner v002</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1180px,96%);margin:14px auto 30px}h1{margin:0 0 10px;color:var(--gold);font-size:27px}h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.card{padding:10px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:18px;font-weight:800}.small{font-size:12px;color:var(--muted)}
.btn,button{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}.scan{background:#2674a8}.export{background:#bd8320}
table{width:100%;border-collapse:collapse}th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}th{background:#202624;color:var(--gold)}
code{color:#f8d89a;overflow-wrap:anywhere}.good{color:#8fe0a9;font-weight:800}.warn{color:#ffd391;font-weight:800}.snip{font-family:Consolas,monospace;font-size:12px;color:#cfd5d2;word-break:break-word}
@media(max-width:850px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body><div class="wrap">
<h1>MRL Root /css + /js Targeted Usage Scanner v002</h1>
<div class="readonly">READ ONLY — lightweight current-code scan. No WordPress, no snapshot trees, no database, no writes.</div>

<div class="panel">
<h2>Reduced scan scope</h2>
<p>This version intentionally scans only root-level current files plus <code>/mrl_team/</code>, <code>/team_charts/</code>, <code>/league_info/</code>, and the files directly inside <code>/race_results/</code>. That is enough to find normal MRL references without crawling thousands of WordPress and snapshot files.</p>
<form method="get"><input type="hidden" name="scan" value="1"><button class="scan" type="submit"><?php echo $report?'Rescan':'Run Lightweight Targeted Scan'; ?></button>
<?php if($report): ?><a class="btn export" href="?export=json">Export JSON Report</a><?php endif; ?></form>
</div>

<?php if($report): ?>
<div class="panel"><h2>Scan summary</h2>
<p><strong><?php echo h($report['files_examined']); ?></strong> current custom-code files examined.</p>
</div>

<?php foreach($targets as $target): $d=$report['targets'][$target]; ?>
<div class="panel">
<h2>/<?php echo h($target); ?>/</h2>
<div class="grid">
<div class="card"><span class="small">Folder</span><span class="value <?php echo $d['inventory']['exists']?'good':'warn'; ?>"><?php echo $d['inventory']['exists']?'PRESENT':'MISSING'; ?></span></div>
<div class="card"><span class="small">Files inside</span><span class="value"><?php echo count($d['inventory']['files']); ?></span></div>
<div class="card"><span class="small">Targeted matches</span><span class="value"><?php echo count($d['matches']); ?></span></div>
</div>

<?php if(!$d['matches']): ?>
<p class="small">No targeted references found in the current custom-code scope.</p>
<?php else: ?>
<table><tr><th>Type</th><th>Referring file</th><th>Match</th><th>Root target</th><th>Snippet</th></tr>
<?php foreach($d['matches'] as $r): ?>
<tr><td><?php echo h($r['kind']); ?></td><td><code><?php echo h($r['file']); ?></code></td><td><code><?php echo h($r['match']); ?></code></td><td><code><?php echo h($r['target']); ?></code></td><td class="snip"><?php echo h($r['snippet']); ?></td></tr>
<?php endforeach; ?></table>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="panel small">If this finds no current-code references, the next step can be a separate tiny WordPress database search for only the exact root filenames. Keeping that separate avoids another heavy scanner.</div>
<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v002 | CREATED: 9/17/2026 2:05:00 am EDT</div>
</div></body></html>
