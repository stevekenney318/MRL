<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * Historical File Reorganization Discovery
 *
 * VERSION: v002
 * CREATED: 9/15/2026 7:49:00 pm EDT
 *
 * READ-ONLY DISCOVERY ONLY.
 *
 * v002 additions:
 * - Detects dynamic year-based references to Rules / Fees / Schedule files.
 * - Looks for common PHP concatenation/interpolation patterns such as
 *   $year . '_Fees.php', "{$year}_Rules.php", etc.
 * - Specifically reports likely current team.php-style dynamic routing.
 * - Detects companion folders/files used by historical HTML exports.
 * - Separates external/mailto/root-relative URLs from true move-sensitive
 *   relative dependencies, reducing v001 false positives.
 * - Still does not move, edit, rename, delete, chmod, touch, or write DB data.
 */

date_default_timezone_set('America/New_York');
const MRL_REORG_DISCOVERY_VERSION = 'v002';

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
            'dependencies'=>[],'references'=>[],
            'companion_paths'=>[]
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

    if(preg_match_all('/\b(?:href|src|action)\s*=\s*[\'"]([^\'"]+)[\'"]/i',$content,$m)){
        foreach($m[1] as $path) $deps[]=['type'=>'HTML path','value'=>$path];
    }

    if(preg_match_all('/\b(?:fetch|window\.open)\s*\(\s*[\'"]([^\'"]+)[\'"]/i',$content,$m)){
        foreach($m[1] as $path) $deps[]=['type'=>'JS path','value'=>$path];
    }

    $uniq=[];
    foreach($deps as $d){
        $v=trim($d['value']);
        $kind='relative';

        if($v===''){
            $kind='empty';
        }elseif($v[0]==='/'){
            $kind='root-relative';
        }elseif(preg_match('#^(?:https?:|mailto:|tel:|data:|javascript:|#)#i',$v)){
            $kind='external/special';
        }

        $d['path_kind']=$kind;
        $d['likely_breaks_after_move']=($kind==='relative');
        $uniq[strtolower($d['type'].'|'.$d['value'])]=$d;
    }

    return array_values($uniq);
}

function companion_candidates(string $root, string $candidateFile, array $deps): array {
    $out=[];

    foreach($deps as $dep){
        if(($dep['path_kind'] ?? '')!=='relative') continue;
        $v=str_replace('\\','/',$dep['value']);

        // First path component often reveals Word/Excel exported companion folders.
        $parts=explode('/',$v);
        $first=$parts[0] ?? '';

        if($first!=='' && $first!=='.' && $first!=='..'){
            $full=$root.DIRECTORY_SEPARATOR.$first;
            if(is_dir($full)){
                $out[$first]=[
                    'path'=>$first,
                    'type'=>'directory',
                    'reason'=>'Relative dependency from '.$candidateFile
                ];
            }elseif(is_file($full)){
                $out[$first]=[
                    'path'=>$first,
                    'type'=>'file',
                    'reason'=>'Relative dependency from '.$candidateFile
                ];
            }
        }
    }

    return array_values($out);
}

function scan_custom_files(string $root,array $excludeTop,array $candidateNames): array {
    $refs=[]; foreach($candidateNames as $n) $refs[$n]=[];
    $dynamic=[];

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

            foreach($candidateNames as $candidate){
                if($entry===$candidate && dirname($full)===$root) continue;
                if(stripos($content,$candidate)!==false) $refs[$candidate][]=$relative;
            }

            // Dynamic Rules / Fees / Schedule reference patterns.
            $patterns = [
                'Fees' => [
                    '/\$\w+\s*\.\s*[\'"]_Fees\.php[\'"]/i',
                    '/[\'"]\/?\{\$\w+\}_Fees\.php[\'"]/i',
                    '/[\'"]\/?\$\w+_Fees\.php[\'"]/i',
                    '/sprintf\s*\(\s*[\'"][^\'"]*%[sd][^\'"]*_Fees\.php/i',
                ],
                'Rules' => [
                    '/\$\w+\s*\.\s*[\'"]_Rules\.php[\'"]/i',
                    '/[\'"]\/?\{\$\w+\}_Rules\.php[\'"]/i',
                    '/[\'"]\/?\$\w+_Rules\.php[\'"]/i',
                    '/sprintf\s*\(\s*[\'"][^\'"]*%[sd][^\'"]*_Rules\.php/i',
                ],
                'Schedule' => [
                    '/\$\w+\s*\.\s*[\'"]_Schedule\.(?:php|html?)[\'"]/i',
                    '/[\'"]\/?\{\$\w+\}_Schedule\.(?:php|html?)[\'"]/i',
                    '/[\'"]\/?\$\w+_Schedule\.(?:php|html?)[\'"]/i',
                    '/sprintf\s*\(\s*[\'"][^\'"]*%[sd][^\'"]*_Schedule\.(?:php|html?)/i',
                ],
            ];

            foreach($patterns as $kind=>$list){
                foreach($list as $pat){
                    if(preg_match_all($pat,$content,$matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE)){
                        foreach($matches as $match){
                            $snippet=$match[0][0];
                            $dynamic[]=[
                                'file'=>$relative,
                                'kind'=>$kind,
                                'match'=>$snippet
                            ];
                        }
                    }
                }
            }

            // Broad fallback: year-ish variable near suffix.
            if(preg_match_all('/.{0,80}\$(?:year|raceYear|currentYear|season|selectedYear).{0,100}_(?:Fees|Rules|Schedule)\.(?:php|html?).{0,80}/i',$content,$mm)){
                foreach($mm[0] as $snippet){
                    $dynamic[]=[
                        'file'=>$relative,
                        'kind'=>'Broad year-variable match',
                        'match'=>trim(preg_replace('/\s+/',' ',$snippet))
                    ];
                }
            }
        }
    }

    foreach($refs as $name=>$list){
        $list=array_values(array_unique($list));
        sort($list,SORT_NATURAL|SORT_FLAG_CASE);
        $refs[$name]=$list;
    }

    // De-duplicate dynamic findings.
    $seen=[]; $dynamicOut=[];
    foreach($dynamic as $d){
        $key=strtolower($d['file'].'|'.$d['kind'].'|'.$d['match']);
        if(isset($seen[$key])) continue;
        $seen[$key]=true;
        $dynamicOut[]=$d;
    }

    usort($dynamicOut,function($a,$b){
        $c=strnatcasecmp($a['file'],$b['file']);
        if($c!==0) return $c;
        return strnatcasecmp($a['kind'],$b['kind']);
    });

    return ['literal'=>$refs,'dynamic'=>$dynamicOut];
}

function build_report(string $root,array $excludeTop): array {
    $items=discover_root_candidates($root);
    $names=array_keys($items);
    $scan=scan_custom_files($root,$excludeTop,$names);

    $companions=[];

    foreach($items as $name=>&$item){
        $content=@file_get_contents($item['full']);
        if($content!==false){
            $deps=extract_dependencies($content);
            $item['dependencies']=$deps;
            $item['companion_paths']=companion_candidates($root,$name,$deps);

            foreach($item['companion_paths'] as $c){
                $companions[$c['path']]=$c;
            }
        }
        $item['references']=$scan['literal'][$name]??[];
    }
    unset($item);

    $summary=[
        'total'=>count($items),'team_charts'=>0,'docs'=>0,'active_2026'=>0,'historical'=>0,
        'with_references'=>0,'with_relative_dependencies'=>0,'dynamic_reference_hits'=>count($scan['dynamic']),
        'companion_paths'=>count($companions)
    ];

    foreach($items as $item){
        if($item['family']==='Team Chart') $summary['team_charts']++; else $summary['docs']++;
        if($item['active_2026']) $summary['active_2026']++; else $summary['historical']++;
        if($item['references']) $summary['with_references']++;

        foreach($item['dependencies'] as $d){
            if(!empty($d['likely_breaks_after_move'])){ $summary['with_relative_dependencies']++; break; }
        }
    }

    ksort($companions,SORT_NATURAL|SORT_FLAG_CASE);

    return [
        'items'=>$items,
        'summary'=>$summary,
        'dynamic_references'=>$scan['dynamic'],
        'companion_paths'=>array_values($companions)
    ];
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
            'dynamic_references'=>$report['dynamic_references'],
            'companion_paths'=>$report['companion_paths'],
            'items'=>$report['items']
        ];

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="MRL_historical_reorg_discovery_v002_'.date('Ymd_His').'.json"');
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
<title>MRL Historical Reorganization Discovery v002</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1320px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:9px}
.card{padding:10px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:20px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
.actions{display:flex;gap:9px;flex-wrap:wrap}
.btn,button{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}
.scan{background:#2674a8}.export{background:#bd8320}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
.table-wrap{max-height:760px;overflow:auto;border:1px solid #37403c;border-radius:8px}
.badge{display:inline-block;padding:3px 7px;border-radius:999px;font-size:12px;font-weight:800}
.active{background:#5b4315;border:1px solid #d8aa49;color:#ffe6a7}
.hist{background:#173d5a;border:1px solid #69a8dc;color:#dcefff}
.family{font-weight:800;color:#cfe8ff}
code{color:#f8d89a;overflow-wrap:anywhere}
.break{color:#ffd391;font-weight:700}
.safe{color:#9ed9af}
.none{color:#8f9a95}
ul{margin:4px 0;padding-left:18px}
@media(max-width:1100px){.grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">

<h1>MRL Historical File Reorganization Discovery v002</h1>
<div class="readonly">READ ONLY — deeper reference/dependency discovery. No files or database data are changed.</div>

<div class="panel">
<h2>What v002 Adds</h2>
<ul>
<li>Searches custom files for dynamic year-built Rules / Fees / Schedule links.</li>
<li>Separates true move-sensitive relative paths from root-relative and external links.</li>
<li>Finds companion folders/files used by historical exported HTML pages.</li>
</ul>
</div>

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
<div class="card"><span class="small">Total</span><span class="value"><?php echo number_format($report['summary']['total']); ?></span></div>
<div class="card"><span class="small">Team charts</span><span class="value"><?php echo number_format($report['summary']['team_charts']); ?></span></div>
<div class="card"><span class="small">Docs</span><span class="value"><?php echo number_format($report['summary']['docs']); ?></span></div>
<div class="card"><span class="small">2026 active</span><span class="value"><?php echo number_format($report['summary']['active_2026']); ?></span></div>
<div class="card"><span class="small">Literal refs</span><span class="value"><?php echo number_format($report['summary']['with_references']); ?></span></div>
<div class="card"><span class="small">Relative deps</span><span class="value"><?php echo number_format($report['summary']['with_relative_dependencies']); ?></span></div>
<div class="card"><span class="small">Dynamic hits</span><span class="value"><?php echo number_format($report['summary']['dynamic_reference_hits']); ?></span></div>
<div class="card"><span class="small">Companion paths</span><span class="value"><?php echo number_format($report['summary']['companion_paths']); ?></span></div>
</div>
</div>

<div class="panel">
<h2>Dynamic Year-Based References</h2>
<?php if($report['dynamic_references']): ?>
<table><tr><th>File</th><th>Kind</th><th>Matched Code</th></tr>
<?php foreach($report['dynamic_references'] as $d): ?>
<tr><td><code><?php echo h($d['file']); ?></code></td><td><?php echo h($d['kind']); ?></td><td><code><?php echo h($d['match']); ?></code></td></tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p class="none">No dynamic year-built references detected.</p>
<?php endif; ?>
</div>

<div class="panel">
<h2>Companion Paths</h2>
<?php if($report['companion_paths']): ?>
<table><tr><th>Path</th><th>Type</th><th>Why Found</th></tr>
<?php foreach($report['companion_paths'] as $c): ?>
<tr><td><code><?php echo h($c['path']); ?></code></td><td><?php echo h($c['type']); ?></td><td><?php echo h($c['reason']); ?></td></tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p class="none">No companion paths detected.</p>
<?php endif; ?>
</div>

<div class="panel">
<h2>Candidate Inventory</h2>
<div class="table-wrap">
<table>
<thead><tr><th>Year</th><th>Family</th><th>File</th><th>State</th><th>Referenced By</th><th>Move-Sensitive Paths Inside File</th><th>Destination</th></tr></thead>
<tbody>
<?php foreach($report['items'] as $item): ?>
<tr>
<td><?php echo (int)$item['year']; ?></td>
<td class="family"><?php echo h($item['family']); ?></td>
<td><code><?php echo h($item['file']); ?></code><div class="small"><?php echo h(fmt_bytes((int)$item['size'])); ?></div></td>
<td><?php echo $item['active_2026']?'<span class="badge active">2026 ACTIVE</span>':'<span class="badge hist">HISTORICAL</span>'; ?></td>
<td>
<?php if($item['references']): ?><ul><?php foreach($item['references'] as $ref): ?><li><code><?php echo h($ref); ?></code></li><?php endforeach; ?></ul>
<?php else: ?><span class="none">No literal filename references</span><?php endif; ?>
</td>
<td>
<?php $shown=false; foreach($item['dependencies'] as $dep): if(empty($dep['likely_breaks_after_move'])) continue; $shown=true; ?>
<div class="break"><?php echo h($dep['type']); ?>: <code><?php echo h($dep['value']); ?></code></div>
<?php endforeach; if(!$shown): ?><span class="safe">No move-sensitive relative path detected</span><?php endif; ?>
</td>
<td><code><?php echo $item['proposed_folder']==='TBD'?'TBD':'/'.h($item['proposed_folder']).'/'; ?></code></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="panel small"><strong>Next:</strong> export the v002 JSON report and send it back. That should give us enough to define the actual relocation installer safely.</div>
<?php endif; ?>

<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v002 | CREATED: 9/15/2026 7:49:00 pm EDT</div>

</div></body></html>
