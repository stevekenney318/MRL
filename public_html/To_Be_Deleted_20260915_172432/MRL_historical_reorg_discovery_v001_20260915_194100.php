<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Historical File Reorganization Discovery
 *
 * VERSION: v001
 * CREATED: 9/15/2026 7:41:00 pm EDT
 *
 * READ-ONLY DISCOVERY ONLY.
 */

date_default_timezone_set('America/New_York');
const MRL_REORG_DISCOVERY_VERSION = 'v001';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$excludeTop = [
    'wp-admin','wp-includes','wp-content',
    'To_Be_Deleted_20260915_172432',
    '_migration_backups','_mrl_installer_backups','db_backups'
];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function rel_path(string $root, string $path): string {
    $r = str_replace('\\','/',rtrim($root,'/\\'));
    $p = str_replace('\\','/',$path);
    return strpos($p,$r.'/')===0 ? substr($p,strlen($r)+1) : ltrim($p,'/');
}

function fmt_bytes(int $bytes): string {
    if ($bytes < 1024) return $bytes.' B';
    $units=['KB','MB','GB','TB']; $v=(float)$bytes;
    foreach($units as $u){
        $v/=1024;
        if($v<1024 || $u==='TB') return number_format($v,$v>=100?0:($v>=10?1:2)).' '.$u;
    }
    return $bytes.' B';
}

function classify_root_candidate(string $name): ?array {
    if (!preg_match('/^(20\d{2})_(.+)$/i',$name,$m)) return null;
    $year=(int)$m[1]; $tail=$m[2];

    if (preg_match('/(?:^|_)S[1-4]_Team_chart(?:_|\.|$)|(?:^|_)user_team_chart(?:\.|$)/i',$tail)) {
        return ['year'=>$year,'family'=>'Team Chart','proposed_folder'=>'team_charts'];
    }

    if (preg_match('/(?:^|_)(Rules?|Fees?|Schedule)(?:_|\.|$)/i',$tail)) {
        return ['year'=>$year,'family'=>'Rules / Fees / Schedule','proposed_folder'=>'TBD'];
    }

    return null;
}

function discover_root_candidates(string $root): array {
    $items=[]; $entries=@scandir($root);
    if(!is_array($entries)) return $items;

    foreach($entries as $entry){
        if($entry==='.'||$entry==='..'||$entry===basename(__FILE__)) continue;
        $full=$root.DIRECTORY_SEPARATOR.$entry;
        if(!is_file($full)||is_link($full)) continue;
        $class=classify_root_candidate($entry);
        if($class===null) continue;

        $size=@filesize($full); $mtime=@filemtime($full);
        $items[$entry]=[
            'file'=>$entry,'full'=>$full,'year'=>$class['year'],
            'family'=>$class['family'],'proposed_folder'=>$class['proposed_folder'],
            'active_2026'=>$class['year']===2026,
            'size'=>$size===false?0:(int)$size,
            'mtime'=>$mtime===false?0:(int)$mtime,
            'dependencies'=>[],'references'=>[]
        ];
    }
    uksort($items,'strnatcasecmp');
    return $items;
}

function extract_dependencies(string $content): array {
    $deps=[];

    if(preg_match_all('/\b(?:include|include_once|require|require_once)\s*(?:\(\s*)?[\'"]([^\'"]+)[\'"]/i',$content,$m)){
        foreach($m[1] as $path) $deps[]=['type'=>'PHP include/require','value'=>$path];
    }

    if(preg_match_all('/\b(?:href|src|action)\s*=\s*[\'"]([^\'"#]+)[\'"]/i',$content,$m)){
        foreach($m[1] as $path) $deps[]=['type'=>'HTML path','value'=>$path];
    }

    if(preg_match_all('/\b(?:fetch|window\.open)\s*\(\s*[\'"]([^\'"]+)[\'"]/i',$content,$m)){
        foreach($m[1] as $path) $deps[]=['type'=>'JS path','value'=>$path];
    }

    $uniq=[];
    foreach($deps as $d) $uniq[strtolower($d['type'].'|'.$d['value'])]=$d;
    return array_values($uniq);
}

function likely_breaks(string $value): bool {
    $v=trim($value);
    if($v==='') return false;
    if($v[0]==='/') return false;
    if(preg_match('#^(?:https?:|mailto:|tel:|data:|javascript:|#)#i',$v)) return false;
    return true;
}

function scan_custom_files(string $root,array $excludeTop,array $candidateNames): array {
    $refs=[]; foreach($candidateNames as $n) $refs[$n]=[];
    $stack=[$root]; $allowed=['php','html','htm','js','css','json','txt'];

    while($stack){
        $dir=array_pop($stack);
        $entries=@scandir($dir);
        if(!is_array($entries)) continue;

        foreach($entries as $entry){
            if($entry==='.'||$entry==='..') continue;
            $full=$dir.DIRECTORY_SEPARATOR.$entry;
            $relative=rel_path($root,$full);

            if(substr_count(str_replace('\\','/',$relative),'/')===0 && is_dir($full) && in_array($entry,$excludeTop,true)) continue;
            if(is_link($full)) continue;

            if(is_dir($full)){ $stack[]=$full; continue; }
            if(!is_file($full)) continue;

            $ext=strtolower(pathinfo($entry,PATHINFO_EXTENSION));
            if(!in_array($ext,$allowed,true)) continue;

            $content=@file_get_contents($full);
            if($content===false) continue;

            foreach($candidateNames as $candidate){
                if($entry===$candidate && dirname($full)===$root) continue;
                if(stripos($content,$candidate)!==false) $refs[$candidate][]=$relative;
            }
        }
    }

    foreach($refs as $name=>$list){
        $list=array_values(array_unique($list));
        sort($list,SORT_NATURAL|SORT_FLAG_CASE);
        $refs[$name]=$list;
    }
    return $refs;
}

function build_report(string $root,array $excludeTop): array {
    $items=discover_root_candidates($root);
    $names=array_keys($items);
    $refs=scan_custom_files($root,$excludeTop,$names);

    foreach($items as $name=>&$item){
        $content=@file_get_contents($item['full']);
        if($content!==false){
            $deps=extract_dependencies($content);
            foreach($deps as &$d) $d['likely_breaks_after_move']=likely_breaks($d['value']);
            unset($d);
            $item['dependencies']=$deps;
        }
        $item['references']=$refs[$name]??[];
    }
    unset($item);

    $summary=['total'=>count($items),'team_charts'=>0,'docs'=>0,'active_2026'=>0,'historical'=>0,'with_references'=>0,'with_relative_dependencies'=>0];

    foreach($items as $item){
        if($item['family']==='Team Chart') $summary['team_charts']++; else $summary['docs']++;
        if($item['active_2026']) $summary['active_2026']++; else $summary['historical']++;
        if($item['references']) $summary['with_references']++;
        foreach($item['dependencies'] as $d){
            if(!empty($d['likely_breaks_after_move'])){ $summary['with_relative_dependencies']++; break; }
        }
    }

    return ['items'=>$items,'summary'=>$summary];
}

$scan=isset($_GET['scan'])||isset($_GET['export']);
$report=null;

if($scan){
    $report=build_report($root,$excludeTop);

    if(($_GET['export']??'')==='json'){
        $payload=[
            'tool'=>'MRL Historical File Reorganization Discovery',
            'version'=>MRL_REORG_DISCOVERY_VERSION,
            'generated_at'=>date(DATE_ATOM),
            'root'=>$root,
            'read_only'=>true,
            'proposed_destinations'=>[
                'Team Chart'=>'/team_charts/',
                'Rules / Fees / Schedule'=>'TBD after review'
            ],
            'summary'=>$report['summary'],
            'items'=>$report['items']
        ];
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="MRL_historical_reorg_discovery_'.date('Ymd_His').'.json"');
        echo json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Historical Reorganization Discovery</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1280px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:9px}
.card{padding:10px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:20px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
.actions{display:flex;gap:9px;flex-wrap:wrap}
.btn,button{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}
.scan{background:#2674a8}.export{background:#bd8320}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold);position:sticky;top:0;z-index:1}
.table-wrap{max-height:760px;overflow:auto;border:1px solid #37403c;border-radius:8px}
.badge{display:inline-block;padding:3px 7px;border-radius:999px;font-size:12px;font-weight:800}
.active{background:#5b4315;border:1px solid #d8aa49;color:#ffe6a7}
.hist{background:#173d5a;border:1px solid #69a8dc;color:#dcefff}
.family{font-weight:800;color:#cfe8ff}
code{color:#f8d89a;overflow-wrap:anywhere}
ul{margin:4px 0;padding-left:18px}
.break{color:#ffd391;font-weight:700}
.none{color:#8f9a95}
@media(max-width:1000px){.grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">

<h1>MRL Historical File Reorganization Discovery</h1>
<div class="readonly">READ ONLY — discovery only. No file is moved, edited, renamed, or deleted.</div>

<div class="panel">
<h2>Scope</h2>
<p>This pass inventories yearly <strong>Team Chart</strong> files plus yearly <strong>Rules / Fees / Schedule</strong> files in the root, then scans for callers and relative paths that may need repair after relocation.</p>
<p class="small">Proposed Team Chart destination: <code>/team_charts/</code>. The Rules / Fees / Schedule destination remains deliberately TBD until we see the actual files and references.</p>
</div>

<div class="panel">
<h2>Actions</h2>
<div class="actions">
<form method="get" style="margin:0"><input type="hidden" name="scan" value="1"><button type="submit" class="scan"><?php echo $report?'Rescan':'Run Discovery'; ?></button></form>
<?php if($report): ?><a class="btn export" href="?export=json">Export JSON Report</a><?php endif; ?>
</div>
</div>

<?php if($report): ?>
<div class="panel">
<h2>At a Glance</h2>
<div class="grid">
<div class="card"><span class="small">Total candidates</span><span class="value"><?php echo number_format($report['summary']['total']); ?></span></div>
<div class="card"><span class="small">Team charts</span><span class="value"><?php echo number_format($report['summary']['team_charts']); ?></span></div>
<div class="card"><span class="small">Rules / fees / schedule</span><span class="value"><?php echo number_format($report['summary']['docs']); ?></span></div>
<div class="card"><span class="small">2026 active</span><span class="value"><?php echo number_format($report['summary']['active_2026']); ?></span></div>
<div class="card"><span class="small">Referenced elsewhere</span><span class="value"><?php echo number_format($report['summary']['with_references']); ?></span></div>
<div class="card"><span class="small">Relative dependencies</span><span class="value"><?php echo number_format($report['summary']['with_relative_dependencies']); ?></span></div>
</div>
</div>

<div class="panel">
<h2>Candidate Inventory</h2>
<div class="table-wrap">
<table>
<thead><tr><th>Year</th><th>Family</th><th>File</th><th>State</th><th>Referenced By</th><th>Inside File — Paths to Review</th><th>Proposed Destination</th></tr></thead>
<tbody>
<?php foreach($report['items'] as $item): ?>
<tr>
<td><?php echo (int)$item['year']; ?></td>
<td class="family"><?php echo h($item['family']); ?></td>
<td><code><?php echo h($item['file']); ?></code><div class="small"><?php echo h(fmt_bytes((int)$item['size'])); ?></div></td>
<td><?php echo $item['active_2026']?'<span class="badge active">2026 ACTIVE</span>':'<span class="badge hist">HISTORICAL</span>'; ?></td>
<td>
<?php if($item['references']): ?><ul><?php foreach($item['references'] as $ref): ?><li><code><?php echo h($ref); ?></code></li><?php endforeach; ?></ul>
<?php else: ?><span class="none">No filename references found</span><?php endif; ?>
</td>
<td>
<?php $shown=false; foreach($item['dependencies'] as $dep): if(empty($dep['likely_breaks_after_move'])) continue; $shown=true; ?>
<div class="break"><?php echo h($dep['type']); ?>: <code><?php echo h($dep['value']); ?></code></div>
<?php endforeach; if(!$shown): ?><span class="none">No obvious relative path found</span><?php endif; ?>
</td>
<td><code><?php echo $item['proposed_folder']==='TBD'?'TBD':'/'.h($item['proposed_folder']).'/'; ?></code></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="panel small"><strong>Next step:</strong> export the JSON report and send it back to ChatGPT. We will use the actual dependency/reference map to build the relocation plan before changing anything.</div>
<?php endif; ?>

<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: <?php echo h(MRL_REORG_DISCOVERY_VERSION); ?> | CREATED: 9/15/2026 7:41:00 pm EDT</div>

</div></body></html>
