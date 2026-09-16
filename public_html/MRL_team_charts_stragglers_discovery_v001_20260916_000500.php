<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Team Charts Stragglers + current_* Discovery
 *
 * VERSION: v001
 * CREATED: 9/16/2026 12:05:00 am EDT
 *
 * READ-ONLY.
 *
 * PURPOSE
 * -------
 * 1) Inspect six year-chart stragglers Steve identified after the main
 *    /team_charts/ relocation.
 * 2) Investigate five current_* chart helpers to determine which are active
 *    dependencies versus old/manual helpers.
 *
 * This utility does not move/edit/delete/write DB data.
 */

date_default_timezone_set('America/New_York');
const MRL_DISCOVERY_VERSION = 'v001';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$stragglers = [
    '2017_Team_chart.php',
    '2017-s2-no-picks-yet.php',
    '2017-s3-no-picks-yet.php',
    '2017-s4-no-picks-yet.php',
    '2023_All_Teams_chart.php',
    '2023_All_Teams_history_chart.php',
];

$currentHelpers = [
    'current_segment_chart.php',
    'current_segment_chart_by_entry_time.php',
    'current_team_chart.php',
    'current_user_team_chart.php',
    'current_user_team_chart_simple.php',
];

$excludeTop = [
    'wp-admin','wp-includes','wp-content',
    'To_Be_Deleted_20260915_172432',
    '_migration_backups','_mrl_installer_backups','db_backups',
    'team_charts',
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

function extract_dependencies(string $content): array {
    $deps=[];

    if(preg_match_all('/\b(?:include|include_once|require|require_once)\s*(?:\(\s*)?[\'"]([^\'"]+)[\'"]/i',$content,$m)){
        foreach($m[1] as $path) $deps[]=['type'=>'PHP include/require','value'=>$path];
    }

    if(preg_match_all('/\b(?:href|src|action)\s*=\s*[\'"]([^\'"]+)[\'"]/i',$content,$m)){
        foreach($m[1] as $path) $deps[]=['type'=>'HTML path','value'=>$path];
    }

    $uniq=[];
    foreach($deps as $d){
        $v=trim($d['value']);
        if($v==='') $kind='empty';
        elseif($v[0]==='/') $kind='root-relative';
        elseif(preg_match('#^(?:https?:|mailto:|tel:|data:|javascript:|#)#i',$v)) $kind='external/special';
        else $kind='relative';

        $d['path_kind']=$kind;
        $d['move_sensitive']=($kind==='relative');
        $uniq[strtolower($d['type'].'|'.$d['value'])]=$d;
    }

    return array_values($uniq);
}

function scan_references(string $root,array $excludeTop,array $names): array {
    $refs=[]; foreach($names as $n) $refs[$n]=[];

    $stack=[$root];
    $allowed=['php','html','htm','js','css','json','txt'];

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

            foreach($names as $name){
                if($entry===$name && dirname($full)===$root) continue;
                if(stripos($content,$name)!==false) $refs[$name][]=$relative;
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

function inspect_files(string $root,array $names,array $refs,string $group): array {
    $items=[];

    foreach($names as $name){
        $full=$root.DIRECTORY_SEPARATOR.$name;
        $exists=is_file($full);

        $item=[
            'file'=>$name,
            'group'=>$group,
            'exists'=>$exists,
            'size'=>0,
            'mtime'=>0,
            'modified'=>'',
            'dependencies'=>[],
            'references'=>$refs[$name]??[],
        ];

        if($exists){
            $size=@filesize($full);
            $mtime=@filemtime($full);
            $content=@file_get_contents($full);

            $item['size']=$size===false?0:(int)$size;
            $item['mtime']=$mtime===false?0:(int)$mtime;
            $item['modified']=$item['mtime']?date('Y-m-d H:i:s T',$item['mtime']):'unknown';
            $item['dependencies']=$content===false?[]:extract_dependencies($content);
        }

        $items[]=$item;
    }

    return $items;
}

$allNames=array_merge($stragglers,$currentHelpers);
$run=isset($_GET['scan'])||isset($_GET['export']);
$report=null;

if($run){
    $refs=scan_references($root,$excludeTop,$allNames);
    $stragItems=inspect_files($root,$stragglers,$refs,'Year-chart straggler');
    $currentItems=inspect_files($root,$currentHelpers,$refs,'current_* helper');

    $summary=[
        'stragglers_found'=>count(array_filter($stragItems,function($x){return $x['exists'];})),
        'current_helpers_found'=>count(array_filter($currentItems,function($x){return $x['exists'];})),
        'stragglers_with_refs'=>count(array_filter($stragItems,function($x){return !empty($x['references']);})),
        'current_helpers_with_refs'=>count(array_filter($currentItems,function($x){return !empty($x['references']);})),
    ];

    $report=[
        'tool'=>'MRL Team Charts Stragglers + current_* Discovery',
        'version'=>MRL_DISCOVERY_VERSION,
        'generated_at'=>date(DATE_ATOM),
        'root'=>$root,
        'read_only'=>true,
        'summary'=>$summary,
        'stragglers'=>$stragItems,
        'current_helpers'=>$currentItems
    ];

    if(($_GET['export']??'')==='json'){
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="MRL_team_charts_stragglers_discovery_'.date('Ymd_His').'.json"');
        echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Team Chart Stragglers Discovery</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1220px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}
.card{padding:10px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:20px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
.actions{display:flex;gap:9px;flex-wrap:wrap}
.btn,button{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}
.scan{background:#2674a8}.export{background:#bd8320}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
code{color:#f8d89a;overflow-wrap:anywhere}
.good{color:#8fe0a9}.warn{color:#ffd391}.none{color:#8f9a95}
ul{margin:4px 0;padding-left:18px}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">

<h1>MRL Team Chart Stragglers Discovery</h1>
<div class="readonly">READ ONLY — no moves or edits. This pass identifies what is safe to add to /team_charts/ and what current_* files are actively referenced.</div>

<div class="panel">
<h2>Actions</h2>
<div class="actions">
<form method="get" style="margin:0"><input type="hidden" name="scan" value="1"><button class="scan" type="submit"><?php echo $report?'Rescan':'Run Discovery'; ?></button></form>
<?php if($report): ?><a class="btn export" href="?export=json">Export JSON Report</a><?php endif; ?>
</div>
</div>

<?php if($report): ?>
<div class="panel">
<h2>At a Glance</h2>
<div class="grid">
<div class="card"><span class="small">Stragglers found</span><span class="value"><?php echo number_format($report['summary']['stragglers_found']); ?></span></div>
<div class="card"><span class="small">Stragglers referenced</span><span class="value"><?php echo number_format($report['summary']['stragglers_with_refs']); ?></span></div>
<div class="card"><span class="small">current_* found</span><span class="value"><?php echo number_format($report['summary']['current_helpers_found']); ?></span></div>
<div class="card"><span class="small">current_* referenced</span><span class="value"><?php echo number_format($report['summary']['current_helpers_with_refs']); ?></span></div>
</div>
</div>

<?php foreach(['stragglers'=>'Year-chart Stragglers','current_helpers'=>'current_* Helpers'] as $key=>$title): ?>
<div class="panel">
<h2><?php echo h($title); ?></h2>
<table>
<tr><th>File</th><th>Exists</th><th>Referenced By</th><th>Move-sensitive dependencies</th><th>Modified</th></tr>
<?php foreach($report[$key] as $item): ?>
<tr>
<td><code><?php echo h($item['file']); ?></code><div class="small"><?php echo h(fmt_bytes((int)$item['size'])); ?></div></td>
<td class="<?php echo $item['exists']?'good':'warn'; ?>"><?php echo $item['exists']?'YES':'NO'; ?></td>
<td>
<?php if($item['references']): ?><ul><?php foreach($item['references'] as $ref): ?><li><code><?php echo h($ref); ?></code></li><?php endforeach; ?></ul>
<?php else: ?><span class="none">No literal references found</span><?php endif; ?>
</td>
<td>
<?php
$shown=false;
foreach($item['dependencies'] as $dep):
    if(empty($dep['move_sensitive'])) continue;
    $shown=true;
?>
<div class="warn"><?php echo h($dep['type']); ?>: <code><?php echo h($dep['value']); ?></code></div>
<?php endforeach; ?>
<?php if(!$shown): ?><span class="none">No obvious move-sensitive relative dependency</span><?php endif; ?>
</td>
<td><?php echo h($item['modified']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endforeach; ?>

<div class="panel small"><strong>Next:</strong> export the JSON and send it back. Then we can make a small add-on mover for the true stragglers and decide which current_* helpers stay active versus move/archive.</div>
<?php endif; ?>

<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 12:05:00 am EDT</div>

</div></body></html>
