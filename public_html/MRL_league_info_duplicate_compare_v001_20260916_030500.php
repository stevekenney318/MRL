<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * League Info Root Duplicate Comparison
 *
 * VERSION: v001
 * CREATED: 9/16/2026 3:05:00 am EDT
 *
 * READ-ONLY.
 */

date_default_timezone_set('America/New_York');

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$files = [
    '2019_rules.docx',
    '2019_rules.htm',
    '2020_rules.htm',
    '2024_Fees.php',
    '2024_Rules.php',
    '2024_Schedule.html',
    '2024_Schedule.php',
    '2025_Fees.php',
    '2025_Rules.php',
    '2026_Fees.php',
    '2026_Rules.php',
];

$expectedRootDeps = [
    '2024_Fees.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2024_Rules.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2024_Schedule.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2025_Fees.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2025_Rules.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2026_Rules.php' => ['header.php'],
];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pjoin(string $a,string $b): string { return rtrim($a,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$b); }
function fmt_bytes(int $bytes): string {
    if ($bytes < 1024) return $bytes.' B';
    $units=['KB','MB','GB']; $v=(float)$bytes;
    foreach($units as $u){ $v/=1024; if($v<1024 || $u==='GB') return number_format($v,$v>=100?0:($v>=10?1:2)).' '.$u; }
    return $bytes.' B';
}
function normalize_line_endings(string $s): string { return str_replace(["\r\n","\r"],"\n",$s); }

function normalize_relocation_repairs(string $content,array $deps): string {
    $content = normalize_line_endings($content);
    foreach($deps as $dep){
        $q=preg_quote($dep,'/');
        $patterns=[
            '/\b(include|include_once|require|require_once)\s+dirname\s*\(\s*__DIR__\s*\)\s*\.\s*[\'"]\/'.$q.'[\'"]\s*;?/i',
            '/\b(include|include_once|require|require_once)\s+[\'"]'.$q.'[\'"]\s*;?/i',
            '/\b(include|include_once|require|require_once)\s*\(\s*[\'"]'.$q.'[\'"]\s*\)\s*;?/i'
        ];
        foreach($patterns as $pattern){
            $content=preg_replace_callback($pattern,function($m) use($dep){
                return strtolower($m[1])." '".$dep."';";
            },$content);
        }
    }
    return $content;
}

function info(string $path): array {
    if(!is_file($path)) return ['exists'=>false,'size'=>0,'mtime'=>0,'mtime_text'=>'','sha256'=>'','content'=>null];
    $size=@filesize($path); $mtime=@filemtime($path); $content=@file_get_contents($path);
    return [
        'exists'=>true,
        'size'=>$size===false?0:(int)$size,
        'mtime'=>$mtime===false?0:(int)$mtime,
        'mtime_text'=>$mtime===false?'unknown':date('Y-m-d H:i:s T',(int)$mtime),
        'sha256'=>$content===false?'':hash('sha256',$content),
        'content'=>$content
    ];
}

function classify(string $name,array $a,array $b,array $depsMap): array {
    if(!$a['exists'] || !$b['exists']){
        return ['class'=>'MISSING SIDE','safe'=>false,'detail'=>!$a['exists']?'Root copy missing.':'/league_info/ copy missing.'];
    }
    if($a['sha256']!=='' && $a['sha256']===$b['sha256']){
        return ['class'=>'IDENTICAL','safe'=>true,'detail'=>'Byte-for-byte identical.'];
    }
    if($a['content']===null || $b['content']===null){
        return ['class'=>'DIFFERENT - REVIEW','safe'=>false,'detail'=>'Could not read one or both files.'];
    }
    $deps=$depsMap[$name]??[];
    if($deps){
        $na=normalize_relocation_repairs($a['content'],$deps);
        $nb=normalize_relocation_repairs($b['content'],$deps);
        if($na===$nb){
            return ['class'=>'RELOCATION-ONLY DIFFERENCE','safe'=>true,'detail'=>'Matches after normalizing expected dirname(__DIR__) repairs.'];
        }
    }
    return ['class'=>'DIFFERENT - REVIEW','safe'=>false,'detail'=>'Differences remain after expected relocation repairs are normalized.'];
}

$run=isset($_GET['scan'])||isset($_GET['export']);
$report=null;

if($run){
    $rows=[]; $counts=['identical'=>0,'relocation_only'=>0,'review'=>0,'missing'=>0,'safe'=>0];
    foreach($files as $name){
        $a=info(pjoin($root,$name));
        $b=info(pjoin($root,'league_info/'.$name));
        $c=classify($name,$a,$b,$expectedRootDeps);
        if($c['class']==='IDENTICAL') $counts['identical']++;
        elseif($c['class']==='RELOCATION-ONLY DIFFERENCE') $counts['relocation_only']++;
        elseif($c['class']==='DIFFERENT - REVIEW') $counts['review']++;
        else $counts['missing']++;
        if($c['safe']) $counts['safe']++;
        $rows[]=[
            'file'=>$name,
            'root'=>['exists'=>$a['exists'],'size'=>$a['size'],'mtime_text'=>$a['mtime_text'],'sha256'=>$a['sha256']],
            'league_info'=>['exists'=>$b['exists'],'size'=>$b['size'],'mtime_text'=>$b['mtime_text'],'sha256'=>$b['sha256']],
            'classification'=>$c['class'],
            'safe_remove_root'=>$c['safe'],
            'detail'=>$c['detail']
        ];
    }
    $report=['tool'=>'MRL League Info Root Duplicate Comparison','version'=>'v001','generated_at'=>date(DATE_ATOM),'root'=>$root,'read_only'=>true,'counts'=>$counts,'rows'=>$rows];
    if(($_GET['export']??'')==='json'){
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="MRL_league_info_duplicate_compare_'.date('Ymd_His').'.json"');
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
<title>MRL League Info Duplicate Comparison</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1240px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px}
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
.safe{color:#8fe0a9;font-weight:800}.review{color:#ffb3b3;font-weight:800}.reloc{color:#ffd391;font-weight:800}.ident{color:#8fe0a9;font-weight:800}.missing{color:#ffb3b3;font-weight:800}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">
<h1>MRL League Info Root Duplicate Comparison</h1>
<div class="readonly">READ ONLY — compares root duplicates to <code>/league_info/</code>. Nothing is changed or deleted.</div>

<div class="panel">
<h2>What This Checks</h2>
<p>Byte-for-byte equality first. For PHP files, expected relocation-only include/require repairs are normalized before deciding whether the root copy is safe to remove.</p>
<div class="actions">
<form method="get" style="margin:0">
<input type="hidden" name="scan" value="1">
<button class="scan" type="submit"><?php echo $report?'Rescan Duplicates':'Run Duplicate Comparison'; ?></button>
</form>
<?php if($report): ?><a class="btn export" href="?export=json">Export JSON Report</a><?php endif; ?>
</div>
</div>

<?php if($report): ?>
<div class="panel">
<h2>At a Glance</h2>
<div class="grid">
<div class="card"><span class="small">Identical</span><span class="value"><?php echo h($report['counts']['identical']); ?></span></div>
<div class="card"><span class="small">Relocation-only</span><span class="value"><?php echo h($report['counts']['relocation_only']); ?></span></div>
<div class="card"><span class="small">Needs review</span><span class="value"><?php echo h($report['counts']['review']); ?></span></div>
<div class="card"><span class="small">Missing side</span><span class="value"><?php echo h($report['counts']['missing']); ?></span></div>
<div class="card"><span class="small">Safe root removals</span><span class="value"><?php echo h($report['counts']['safe']); ?></span></div>
</div>
</div>

<div class="panel">
<h2>Comparison</h2>
<table>
<tr><th>File</th><th>Classification</th><th>Root copy</th><th>/league_info/ copy</th><th>Safe to remove root?</th><th>Detail</th></tr>
<?php foreach($report['rows'] as $r):
$cls=$r['classification']==='IDENTICAL'?'ident':($r['classification']==='RELOCATION-ONLY DIFFERENCE'?'reloc':($r['classification']==='MISSING SIDE'?'missing':'review'));
?>
<tr>
<td><code><?php echo h($r['file']); ?></code></td>
<td class="<?php echo h($cls); ?>"><?php echo h($r['classification']); ?></td>
<td><?php if($r['root']['exists']): ?><?php echo h(fmt_bytes((int)$r['root']['size'])); ?><br><span class="small"><?php echo h($r['root']['mtime_text']); ?></span><br><span class="small">SHA256: <?php echo h(substr($r['root']['sha256'],0,12)); ?>…</span><?php else: ?><span class="missing">MISSING</span><?php endif; ?></td>
<td><?php if($r['league_info']['exists']): ?><?php echo h(fmt_bytes((int)$r['league_info']['size'])); ?><br><span class="small"><?php echo h($r['league_info']['mtime_text']); ?></span><br><span class="small">SHA256: <?php echo h(substr($r['league_info']['sha256'],0,12)); ?>…</span><?php else: ?><span class="missing">MISSING</span><?php endif; ?></td>
<td class="<?php echo $r['safe_remove_root']?'safe':'review'; ?>"><?php echo $r['safe_remove_root']?'YES':'NO'; ?></td>
<td><?php echo h($r['detail']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel small"><strong>Next:</strong> send the screenshot or JSON export. If all 11 are safe, I’ll make the tiny removal installer that deletes only the verified root duplicates and leaves <code>/league_info/</code> untouched.</div>
<?php endif; ?>

<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 3:05:00 am EDT</div>
</div></body></html>
