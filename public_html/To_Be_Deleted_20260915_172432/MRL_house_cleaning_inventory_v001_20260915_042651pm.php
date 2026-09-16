<?php
declare(strict_types=1);

/**
 * Manlius Racing League - MRL House Cleaning Inventory
 * VERSION: v001
 * LAST MODIFIED: 9/15/2026 4:26:51 pm
 *
 * Read-only Phase 1 housekeeping utility.
 * No delete, rename, move, chmod, touch, DB-write, or production-edit action exists here.
 */

date_default_timezone_set('America/New_York');
const MRL_HOUSE_CLEAN_VERSION = 'v001';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$topLevelExclusions = ['wp-admin','wp-includes','wp-content'];
$hardSkipDirs = ['.git','.svn'];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function fmt_bytes(int $b): string {
    if ($b < 1024) return $b . ' B';
    foreach (['KB','MB','GB','TB'] as $u) {
        $bFloat = $b / 1024;
        $b = (int)$bFloat;
        if ($bFloat < 1024 || $u === 'TB') {
            return number_format($bFloat, $bFloat >= 100 ? 0 : ($bFloat >= 10 ? 1 : 2)) . ' ' . $u;
        }
    }
    return (string)$b;
}

function rel_path(string $root, string $path): string {
    $r = str_replace('\\','/',rtrim($root,'/\\'));
    $p = str_replace('\\','/',$path);
    return strpos($p,$r.'/') === 0 ? substr($p,strlen($r)+1) : ltrim($p,'/');
}

function depth(string $relative): int {
    $relative = trim(str_replace('\\','/',$relative),'/');
    return $relative === '' ? 0 : substr_count($relative,'/') + 1;
}

function age_days(int $mtime): ?int {
    if ($mtime <= 0) return null;
    return $mtime > time() ? 0 : (int)floor((time()-$mtime)/86400);
}

function item(string $path,int $size,int $mtime,string $category,string $status,string $reason): array {
    return [
        'path'=>$path,'size'=>$size,'mtime'=>$mtime,
        'modified'=>$mtime > 0 ? date('Y-m-d H:i:s T',$mtime) : 'unknown',
        'age_days'=>age_days($mtime),
        'category'=>$category,'status'=>$status,'reason'=>$reason
    ];
}

function classify_file(string $relative,int $size,int $mtime): ?array {
    $name = basename($relative);
    $lower = strtolower($name);
    $relLower = strtolower(str_replace('\\','/',$relative));
    $isRoot = depth($relative) === 1;

    if (strpos($relLower,'_migration_backups/') === 0) {
        return item($relative,$size,$mtime,'Migration backup artifact','REVIEW',
            'Inside _migration_backups; likely superseded deployment backup material.');
    }

    if (strpos($relLower,'_mrl_installer_backups/') === 0) {
        return item($relative,$size,$mtime,'Installer rollback backup','REVIEW',
            'Inside _mrl_installer_backups; created for installer rollback/recovery.');
    }

    if ($isRoot && preg_match('/^mrl_install_.+\.php$/i',$name)) {
        return item($relative,$size,$mtime,'Generated installer','LIKELY CLEANUP',
            'Root-level MRL_install_*.php deployment artifact. Steve keeps installer copies locally; still review before deletion.');
    }

    if ($isRoot && preg_match('/^mrl_(?:rebuild|scoring|diagnostic|repair|reset|delete|cleanup|audit|inspect|check|test)_.+\.php$/i',$name)) {
        return item($relative,$size,$mtime,'One-time MRL utility / diagnostic','REVIEW',
            'Filename strongly suggests a one-time diagnostic, rebuild, repair, reset, cleanup, or test utility.');
    }

    if ($isRoot && preg_match('/^(?:fix|repair|cleanup|migration|migrate|test|debug|diagnostic)[_\-].+\.php$/i',$name)) {
        return item($relative,$size,$mtime,'Legacy fix / migration utility','REVIEW',
            'Root-level utility-style filename; inspect purpose before removal.');
    }

    if (
        preg_match('/(?:\.bak(?:[._\-].*)?$|\.backup(?:[._\-].*)?$|\.orig$|\.old$|\.save$|~$)/i',$name) ||
        preg_match('/(?:_bak_|_backup_|_old_|_save_|\.bak_)/i',$lower)
    ) {
        return item($relative,$size,$mtime,'Loose backup / copy','REVIEW',
            'Filename looks like an ad-hoc backup or saved copy.');
    }

    if (
        preg_match('/(?:_copy\d*|_copy_of_|_previous|_archive|_obsolete|_deprecated)\.(?:php|html?|json|txt)$/i',$lower) ||
        preg_match('/(?:_v\d+_old|_old_v\d+)\.(?:php|html?)$/i',$lower)
    ) {
        return item($relative,$size,$mtime,'Uncertain legacy copy','REVIEW',
            'Filename suggests an older copy; do not remove until references are checked.');
    }

    return null;
}

function walk_site(string $root,array $topLevelExclusions,array $hardSkipDirs): array {
    $candidates=[]; $errors=[];
    $stats=['files_scanned'=>0,'dirs_scanned'=>0,'bytes_scanned'=>0];
    $stack=[$root];

    while ($stack) {
        $dir=array_pop($stack);
        $stats['dirs_scanned']++;
        $entries=@scandir($dir);
        if ($entries === false) {
            $errors[]='Could not read directory: '.rel_path($root,$dir);
            continue;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full=$dir.DIRECTORY_SEPARATOR.$entry;
            $relative=rel_path($root,$full);

            if (depth($relative) === 1 && in_array($entry,$topLevelExclusions,true)) continue;
            if (is_link($full)) continue;

            if (is_dir($full)) {
                if (in_array($entry,$hardSkipDirs,true)) continue;
                $stack[]=$full;
                continue;
            }

            if (!is_file($full)) continue;

            $stats['files_scanned']++;
            $size=@filesize($full); $mtime=@filemtime($full);
            $size=$size === false ? 0 : (int)$size;
            $mtime=$mtime === false ? 0 : (int)$mtime;
            $stats['bytes_scanned'] += $size;

            $c=classify_file($relative,$size,$mtime);
            if ($c !== null) $candidates[]=$c;
        }
    }

    usort($candidates,function(array $a,array $b): int {
        $order=['LIKELY CLEANUP'=>0,'REVIEW'=>1];
        $sa=$order[$a['status']] ?? 9; $sb=$order[$b['status']] ?? 9;
        if ($sa !== $sb) return $sa <=> $sb;
        $cc=strcasecmp($a['category'],$b['category']);
        return $cc !== 0 ? $cc : strcasecmp($a['path'],$b['path']);
    });

    return ['candidates'=>$candidates,'errors'=>$errors,'stats'=>$stats];
}

function summarize(array $scan): array {
    $s=['candidate_count'=>0,'candidate_bytes'=>0,'likely_count'=>0,'likely_bytes'=>0,'review_count'=>0,'review_bytes'=>0,'categories'=>[]];
    foreach ($scan['candidates'] as $x) {
        $s['candidate_count']++; $s['candidate_bytes'] += (int)$x['size'];
        if ($x['status'] === 'LIKELY CLEANUP') { $s['likely_count']++; $s['likely_bytes'] += (int)$x['size']; }
        else { $s['review_count']++; $s['review_bytes'] += (int)$x['size']; }

        $c=$x['category'];
        if (!isset($s['categories'][$c])) $s['categories'][$c]=['count'=>0,'bytes'=>0,'likely'=>0,'review'=>0];
        $s['categories'][$c]['count']++; $s['categories'][$c]['bytes'] += (int)$x['size'];
        if ($x['status'] === 'LIKELY CLEANUP') $s['categories'][$c]['likely']++;
        else $s['categories'][$c]['review']++;
    }
    ksort($s['categories'],SORT_NATURAL|SORT_FLAG_CASE);
    return $s;
}

function tree_summary(array $items,string $prefix): array {
    $r=['count'=>0,'bytes'=>0,'oldest'=>null,'newest'=>null];
    foreach ($items as $x) {
        if (strpos(strtolower($x['path']),strtolower($prefix)) !== 0) continue;
        $r['count']++; $r['bytes'] += (int)$x['size'];
        $m=(int)$x['mtime'];
        if ($m > 0) {
            $r['oldest']=$r['oldest']===null ? $m : min($r['oldest'],$m);
            $r['newest']=$r['newest']===null ? $m : max($r['newest'],$m);
        }
    }
    return $r;
}

$run = isset($_GET['scan']) || isset($_POST['scan']) || isset($_GET['export']);
$scan=null; $summary=null;

if ($run) {
    $scan=walk_site($root,$topLevelExclusions,$hardSkipDirs);
    $summary=summarize($scan);

    if (($_GET['export'] ?? '') === 'json') {
        $payload=[
            'tool'=>'MRL House Cleaning Inventory',
            'version'=>MRL_HOUSE_CLEAN_VERSION,
            'generated_at'=>date(DATE_ATOM),
            'root'=>$root,
            'read_only'=>true,
            'top_level_exclusions'=>$topLevelExclusions,
            'stats'=>$scan['stats'],
            'summary'=>$summary,
            'errors'=>$scan['errors'],
            'candidates'=>$scan['candidates']
        ];
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="MRL_house_cleaning_inventory_'.date('Ymd_His').'.json"');
        echo json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$migration=$scan ? tree_summary($scan['candidates'],'_migration_backups/') : null;
$installerBackups=$scan ? tree_summary($scan['candidates'],'_mrl_installer_backups/') : null;

$copy='';
if ($scan && $summary) {
    $lines=[
        'MRL HOUSE CLEANING INVENTORY',
        'Tool: '.MRL_HOUSE_CLEAN_VERSION,
        'Generated: '.date('n/j/Y g:i:s a T'),
        'Root: '.$root,
        'Mode: READ ONLY',
        '',
        'Scanned: '.number_format((int)$scan['stats']['files_scanned']).' files / '.number_format((int)$scan['stats']['dirs_scanned']).' directories',
        'Candidate items: '.number_format((int)$summary['candidate_count']).' ('.fmt_bytes((int)$summary['candidate_bytes']).')',
        'Likely cleanup: '.number_format((int)$summary['likely_count']).' ('.fmt_bytes((int)$summary['likely_bytes']).')',
        'Review: '.number_format((int)$summary['review_count']).' ('.fmt_bytes((int)$summary['review_bytes']).')',
        '',
        'BY CATEGORY'
    ];
    foreach ($summary['categories'] as $cat=>$d) {
        $lines[]='- '.$cat.': '.number_format((int)$d['count']).' / '.fmt_bytes((int)$d['bytes']);
    }
    if ($scan['errors']) {
        $lines[]=''; $lines[]='SCAN WARNINGS: '.count($scan['errors']);
        foreach ($scan['errors'] as $e) $lines[]='- '.$e;
    }
    $copy=implode("\n",$lines);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL House Cleaning Inventory</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f;--red:#ff8484}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1220px,96%);margin:14px auto 30px}h1{margin:0 0 10px;color:var(--gold);font-size:27px}h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.warning{padding:10px 12px;border:1px solid #9b7b2e;border-radius:9px;background:#3a2f15;color:#ffe2a0}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.card{padding:11px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.card .value{display:block;margin-top:3px;font-size:22px;font-weight:800}.small{font-size:12px;color:var(--muted)}.mono{font-family:Consolas,"Courier New",monospace}
.actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}button,.btn{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}
.scan{background:#2674a8}.copy{background:#2b8354}.export{background:#bd8320}button:disabled{opacity:.45;cursor:not-allowed}
table{width:100%;border-collapse:collapse}th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{position:sticky;top:0;background:#202624;color:var(--gold);z-index:2}.status{display:inline-block;white-space:nowrap;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800}
.status-likely{color:#dffff0;background:#17613a;border:1px solid #54cb87}.status-review{color:#ffe5a8;background:#5a4314;border:1px solid #c69b3e}
.category{color:#d9e9f5;font-weight:700}.path{overflow-wrap:anywhere}.filters{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 10px}
.filter{padding:6px 10px;border:1px solid var(--border);border-radius:999px;background:#252b29;color:var(--text);cursor:pointer}.filter.active{border-color:#7db7e6;background:#22435e}
.table-wrap{max-height:680px;overflow:auto;border:1px solid #37403c;border-radius:8px}ul{margin:5px 0 0;padding-left:20px;line-height:1.45}code{color:#f8d89a}.err{color:var(--red)}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body><div class="wrap">
<h1>MRL House Cleaning Inventory</h1>

<div class="readonly">PHASE 1 — READ ONLY. This utility contains no delete, rename, move, chmod, touch, database-write, or production-edit action.</div>

<div class="panel">
<h2>Purpose</h2>
<p>Scan the current <code>public_html</code> filesystem and package likely housekeeping candidates for review before we build any deletion tool.</p>
<div class="warning"><strong>Important:</strong> “LIKELY CLEANUP” means a strong housekeeping candidate, not automatic permission to delete it. We review the exported inventory first.</div>
</div>

<div class="panel">
<h2>Scan Scope</h2>
<ul>
<li>Live root: <span class="mono"><?php echo h($root); ?></span></li>
<li>Excluded: <code>wp-admin</code>, <code>wp-includes</code>, <code>wp-content</code>.</li>
<li>Symlinks are not followed.</li>
<li>Files are inspected by path/name/metadata only; contents are not modified or hashed.</li>
</ul>
</div>

<div class="panel">
<h2>Actions</h2>
<div class="actions">
<form method="get" style="margin:0"><input type="hidden" name="scan" value="1"><button class="scan" type="submit"><?php echo $scan ? 'Rescan Live Filesystem' : 'Scan Live Filesystem'; ?></button></form>
<button class="copy" id="copySummary" type="button" <?php echo $scan ? '' : 'disabled'; ?>>Copy Summary</button>
<?php if ($scan): ?><a class="btn export" href="?export=json">Export Full JSON Report</a><?php else: ?><span class="btn export" style="opacity:.45;cursor:not-allowed">Export Full JSON Report</span><?php endif; ?>
</div>
</div>

<?php if ($scan && $summary): ?>
<div class="panel">
<h2>At a Glance</h2>
<div class="grid">
<div class="card"><span class="small">Files scanned</span><span class="value"><?php echo number_format((int)$scan['stats']['files_scanned']); ?></span></div>
<div class="card"><span class="small">Directories scanned</span><span class="value"><?php echo number_format((int)$scan['stats']['dirs_scanned']); ?></span></div>
<div class="card"><span class="small">Candidate items</span><span class="value"><?php echo number_format((int)$summary['candidate_count']); ?></span><span class="small"><?php echo h(fmt_bytes((int)$summary['candidate_bytes'])); ?></span></div>
<div class="card"><span class="small">Likely cleanup</span><span class="value"><?php echo number_format((int)$summary['likely_count']); ?></span><span class="small"><?php echo h(fmt_bytes((int)$summary['likely_bytes'])); ?></span></div>
</div>
</div>

<div class="panel">
<h2>Backup Trees</h2>
<table>
<tr><th>Tree</th><th>Files</th><th>Size</th><th>Oldest</th><th>Newest</th></tr>
<tr><td><code>_migration_backups/</code></td><td><?php echo number_format((int)$migration['count']); ?></td><td><?php echo h(fmt_bytes((int)$migration['bytes'])); ?></td><td><?php echo $migration['oldest'] ? h(date('Y-m-d H:i:s',(int)$migration['oldest'])) : '—'; ?></td><td><?php echo $migration['newest'] ? h(date('Y-m-d H:i:s',(int)$migration['newest'])) : '—'; ?></td></tr>
<tr><td><code>_mrl_installer_backups/</code></td><td><?php echo number_format((int)$installerBackups['count']); ?></td><td><?php echo h(fmt_bytes((int)$installerBackups['bytes'])); ?></td><td><?php echo $installerBackups['oldest'] ? h(date('Y-m-d H:i:s',(int)$installerBackups['oldest'])) : '—'; ?></td><td><?php echo $installerBackups['newest'] ? h(date('Y-m-d H:i:s',(int)$installerBackups['newest'])) : '—'; ?></td></tr>
</table>
</div>

<div class="panel">
<h2>Category Summary</h2>
<table>
<tr><th>Category</th><th>Count</th><th>Size</th><th>Likely Cleanup</th><th>Review</th></tr>
<?php foreach ($summary['categories'] as $cat=>$d): ?>
<tr><td><?php echo h($cat); ?></td><td><?php echo number_format((int)$d['count']); ?></td><td><?php echo h(fmt_bytes((int)$d['bytes'])); ?></td><td><?php echo number_format((int)$d['likely']); ?></td><td><?php echo number_format((int)$d['review']); ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Candidate Inventory</h2>
<div class="filters">
<button type="button" class="filter active" data-filter="all">All</button>
<button type="button" class="filter" data-filter="LIKELY CLEANUP">Likely Cleanup</button>
<button type="button" class="filter" data-filter="REVIEW">Review</button>
</div>
<div class="table-wrap">
<table id="candidateTable"><thead><tr><th>Status</th><th>Category</th><th>Path</th><th>Size</th><th>Age</th><th>Why flagged</th></tr></thead><tbody>
<?php foreach ($scan['candidates'] as $x): ?>
<tr data-status="<?php echo h($x['status']); ?>">
<td><span class="status <?php echo $x['status']==='LIKELY CLEANUP' ? 'status-likely' : 'status-review'; ?>"><?php echo h($x['status']); ?></span></td>
<td class="category"><?php echo h($x['category']); ?></td>
<td class="mono path"><?php echo h($x['path']); ?></td>
<td><?php echo h(fmt_bytes((int)$x['size'])); ?></td>
<td><?php echo $x['age_days']===null ? '—' : number_format((int)$x['age_days']).' d'; ?><div class="small"><?php echo h($x['modified']); ?></div></td>
<td class="small"><?php echo h($x['reason']); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div>

<?php if ($scan['errors']): ?>
<div class="panel"><h2>Scan Warnings</h2><ul><?php foreach ($scan['errors'] as $e): ?><li class="err"><?php echo h($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="panel small"><strong>Next step:</strong> Export the Full JSON Report and send it back to ChatGPT. Phase 2 can then build a controlled cleanup plan from the actual current server inventory.</div>
<?php else: ?>
<div class="panel small">Nothing has been scanned yet. Click <strong>Scan Live Filesystem</strong>. The scan is read-only.</div>
<?php endif; ?>

<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: <?php echo h(MRL_HOUSE_CLEAN_VERSION); ?> | LAST MODIFIED: 9/15/2026 4:26:51 pm</div>
<textarea id="copySource" style="position:absolute;left:-9999px;top:-9999px;"><?php echo h($copy); ?></textarea>
</div>

<script>
(function(){
'use strict';
var b=document.getElementById('copySummary'),s=document.getElementById('copySource');
if(b && !b.disabled && s){
 b.addEventListener('click',function(){
  var text=s.value||'';
  function done(){var old=b.textContent;b.textContent='Copied!';setTimeout(function(){b.textContent=old;},1400);}
  if(navigator.clipboard && window.isSecureContext){
   navigator.clipboard.writeText(text).then(done).catch(function(){s.focus();s.select();document.execCommand('copy');done();});
  }else{s.focus();s.select();document.execCommand('copy');done();}
 });
}
var fs=document.querySelectorAll('.filter'),rows=document.querySelectorAll('#candidateTable tbody tr');
Array.prototype.forEach.call(fs,function(btn){
 btn.addEventListener('click',function(){
  var wanted=btn.getAttribute('data-filter');
  Array.prototype.forEach.call(fs,function(x){x.classList.remove('active');});btn.classList.add('active');
  Array.prototype.forEach.call(rows,function(r){var st=r.getAttribute('data-status');r.style.display=(wanted==='all'||wanted===st)?'':'none';});
 });
});
}());
</script>
</body></html>
