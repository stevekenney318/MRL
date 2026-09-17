<?php
declare(strict_types=1);

/**
 * Manlius Racing League - MRL House Cleaning Inventory
 *
 * VERSION: v002
 * LAST MODIFIED: 9/15/2026 4:50:59 pm
 *
 * PHASE 1 / READ-ONLY.
 *
 * v002:
 * - Adds exact KNOWN ARTIFACT manifest for files positively identified from
 *   the current MRL cleanup review as completed generated installers or
 *   one-time utilities.
 * - Separates CONFIRMED CLEANUP from LIKELY CLEANUP and REVIEW.
 * - Adds explicit PROTECTED rules for active backup helpers and db_backups.
 * - Fixes v001 false positives caused merely by the word "backup".
 * - Still contains NO delete/move/rename/edit/DB-write behavior.
 */

date_default_timezone_set('America/New_York');
const MRL_HOUSE_CLEAN_VERSION = 'v002';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$topLevelExclusions = ['wp-admin','wp-includes','wp-content'];
$hardSkipDirs = ['.git','.svn'];

/*
 * Exact known-artifact manifest.
 *
 * These names are intentionally exact rather than pattern-based.
 * They are items already positively identified during the MRL cleanup review
 * as completed generated deployment installers or one-time utilities.
 *
 * This list is advisory only in v002: the utility remains read-only.
 */
$knownCleanup = [
    "MRL_delete_testphp8_utility_v001_20260907_121829pm.php" => true,
    "MRL_house_cleaning_inventory_v001_20260915_042651pm.php" => true,
    "MRL_install_admin_backup_files_helper_v003_24hr_filename_v001_20260907_135256.php" => true,
    "MRL_install_admin_backup_files_helper_v003_24hr_filename_v002_20260907_140527.php" => true,
    "MRL_install_canonical_short_race_labels_v001_20260913_063443.php" => true,
    "MRL_install_mrl_segment_race_effective_fix_v001_20260913_163646.php" => true,
    "MRL_install_race_monitor_signature_fix_v001_20260915_122413.php" => true,
    "MRL_install_revision_classifier_race_effective_fix_v001_20260913_170921.php" => true,
    "MRL_install_scheduler_canonical_race_labels_v001_20260913_065524.php" => true,
    "MRL_install_scheduler_canonical_race_labels_v002_20260913_070021.php" => true,
    "MRL_install_submit-team-picks_v013_LP_ET_fix_v001_20260907_022847am.php" => true,
    "MRL_install_submit-team-picks_v013_LP_ET_fix_v002.php" => true,
    "MRL_install_team_chart_LP_consistency_v001_20260909_022106am.php" => true,
    "MRL_install_team_chart_LP_consistency_v002_20260909_023648am.php" => true,
    "MRL_install_team_chart_LP_consistency_v003_20260909_024033am.php" => true,
    "MRL_install_team_chart_polish_v001_20260909_030640am.php" => true,
    "MRL_install_team_chart_shared_theme_v001_20260909_040104am.php" => true,
    "MRL_install_team_php_v051_cleanup_v001_20260915_130116.php" => true,
    "MRL_install_team_php_v052_manage_pill_reverse_v001_20260915_131323.php" => true,
    "MRL_install_team_user_menu_header_filter_test_v001_20260913_082222.php" => true,
    "MRL_install_team_user_menu_portal_v001_20260913_074748.php" => true,
    "MRL_install_team_user_menu_restore_v001_20260913_075716.php" => true,
    "MRL_install_team_user_menu_restore_v002_20260913_080713.php" => true,
    "MRL_install_team_user_menu_stacking_v001_20260913_073642.php" => true,
    "MRL_install_weekly_competitive_roster_fix_v001_20260913_153557.php" => true,
    "MRL_install_weekly_competitive_roster_fix_v002_20260913_154452.php" => true,
    "MRL_install_weekly_LP_userid_fix_v001_20260913_204941.php" => true,
    "MRL_rebuild_R27_companions_and_classification_v001_20260913_170921.php" => true,
    "MRL_scoring_edge_case_diagnostic_v001_20260913_151642.php" => true,
    "MRL_scoring_edge_case_diagnostic_v002_20260913_153000.php" => true,
];

/* Explicitly protected active/intentional items. */
$protectedExact = [
    'admin_backup_db_helper.php' => true,
    'admin_backup_files_helper.php' => true,
];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function fmt_bytes(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    $units = ['KB','MB','GB','TB'];
    $value = (float)$bytes;
    foreach ($units as $u) {
        $value /= 1024;
        if ($value < 1024 || $u === 'TB') {
            return number_format($value, $value >= 100 ? 0 : ($value >= 10 ? 1 : 2)) . ' ' . $u;
        }
    }
    return $bytes . ' B';
}

function rel_path(string $root,string $path): string {
    $r=str_replace('\\','/',rtrim($root,'/\\'));
    $p=str_replace('\\','/',$path);
    return strpos($p,$r.'/')===0 ? substr($p,strlen($r)+1) : ltrim($p,'/');
}

function depth(string $relative): int {
    $relative=trim(str_replace('\\','/',$relative),'/');
    return $relative==='' ? 0 : substr_count($relative,'/')+1;
}

function age_days(int $mtime): ?int {
    if ($mtime<=0) return null;
    return $mtime>time() ? 0 : (int)floor((time()-$mtime)/86400);
}

function make_item(string $path,int $size,int $mtime,string $category,string $status,string $reason): array {
    return [
        'path'=>$path,
        'size'=>$size,
        'mtime'=>$mtime,
        'modified'=>$mtime>0 ? date('Y-m-d H:i:s T',$mtime) : 'unknown',
        'age_days'=>age_days($mtime),
        'category'=>$category,
        'status'=>$status,
        'reason'=>$reason
    ];
}

function classify_file(string $relative,int $size,int $mtime,array $knownCleanup,array $protectedExact): ?array {
    $name=basename($relative);
    $lower=strtolower($name);
    $relLower=strtolower(str_replace('\\','/',$relative));
    $isRoot=depth($relative)===1;

    if (isset($knownCleanup[$relative])) {
        return make_item(
            $relative,$size,$mtime,
            'Known generated artifact',
            'CONFIRMED CLEANUP',
            'Exact filename is in the reviewed known-artifact manifest: completed generated installer or one-time utility.'
        );
    }

    if (isset($protectedExact[$relative])) {
        return make_item(
            $relative,$size,$mtime,
            'Active / intentional helper',
            'PROTECTED',
            'Explicitly protected: active helper whose real function includes the word backup.'
        );
    }

    if ($relLower === strtolower(basename(__FILE__))) {
        return make_item(
            $relative,$size,$mtime,
            'Current cleanup utility',
            'PROTECTED',
            'This is the currently running v002 inventory utility.'
        );
    }

    if (strpos($relLower,'db_backups/')===0) {
        return make_item(
            $relative,$size,$mtime,
            'Database backup archive',
            'PROTECTED',
            'Intentional database-backup tree. House-cleaning does not treat these as loose backup clutter.'
        );
    }

    if (strpos($relLower,'_migration_backups/')===0) {
        return make_item(
            $relative,$size,$mtime,
            'Migration backup artifact',
            'REVIEW',
            'Inside _migration_backups. Strong cleanup candidate, but treat by backup-set/folder policy rather than blind file deletion.'
        );
    }

    if (strpos($relLower,'_mrl_installer_backups/')===0) {
        return make_item(
            $relative,$size,$mtime,
            'Installer rollback backup',
            'REVIEW',
            'Installer rollback material. Decide retention policy before cleanup.'
        );
    }

    /*
     * Root install_*.php files are probable generated installers, but only
     * exact manifest matches receive CONFIRMED CLEANUP.
     */
    if ($isRoot && preg_match('/^(?:mrl_)?install_.+\.php$/i',$name)) {
        return make_item(
            $relative,$size,$mtime,
            'Probable generated installer',
            'LIKELY CLEANUP',
            'Root-level install-style PHP file; likely completed deployment artifact, but not yet in exact known manifest.'
        );
    }

    if ($isRoot && preg_match('/^mrl_(?:rebuild|scoring|diagnostic|repair|reset|delete|cleanup|audit|inspect|check|test)_.+\.php$/i',$name)) {
        return make_item(
            $relative,$size,$mtime,
            'Probable one-time MRL utility',
            'LIKELY CLEANUP',
            'Filename strongly suggests a one-time MRL diagnostic/repair/rebuild/test utility, but exact provenance is not yet confirmed.'
        );
    }

    if ($isRoot && preg_match('/^(?:fix|repair|cleanup|migration|migrate|test|debug|diagnostic)[_\-].+\.php$/i',$name)) {
        return make_item(
            $relative,$size,$mtime,
            'Legacy fix / migration utility',
            'LIKELY CLEANUP',
            'Root-level utility-style filename. Likely cleanup, but inspect before approval.'
        );
    }

    /*
     * Loose backup detection now requires an actual backup/copy suffix or
     * backup marker. We do NOT flag ordinary active filenames just because
     * they contain the word "backup".
     */
    if (
        preg_match('/(?:\.bak(?:[._\-].*)?$|\.backup(?:[._\-].*)?$|\.orig$|\.old$|\.save$|~$)/i',$name) ||
        preg_match('/(?:_bak_|_backup_\d|\.bak_|\.backup_)/i',$lower)
    ) {
        return make_item(
            $relative,$size,$mtime,
            'Loose backup / saved copy',
            'REVIEW',
            'Filename has a concrete backup/copy suffix or timestamped backup marker.'
        );
    }

    if (
        preg_match('/(?:_copy\d*|_copy_of_|_previous|_archive|_obsolete|_deprecated)\.(?:php|html?|json|txt)$/i',$lower) ||
        preg_match('/(?:_v\d+_old|_old_v\d+)\.(?:php|html?)$/i',$lower)
    ) {
        return make_item(
            $relative,$size,$mtime,
            'Uncertain legacy copy',
            'REVIEW',
            'Filename suggests an older copy. Keep until references/usage are checked.'
        );
    }

    return null;
}

function walk_site(string $root,array $topLevelExclusions,array $hardSkipDirs,array $knownCleanup,array $protectedExact): array {
    $items=[]; $errors=[];
    $stats=['files_scanned'=>0,'dirs_scanned'=>0,'bytes_scanned'=>0];
    $stack=[$root];

    while ($stack) {
        $dir=array_pop($stack);
        $stats['dirs_scanned']++;

        $entries=@scandir($dir);
        if ($entries===false) {
            $errors[]='Could not read directory: '.rel_path($root,$dir);
            continue;
        }

        foreach ($entries as $entry) {
            if ($entry==='.' || $entry==='..') continue;

            $full=$dir.DIRECTORY_SEPARATOR.$entry;
            $relative=rel_path($root,$full);

            if (depth($relative)===1 && in_array($entry,$topLevelExclusions,true)) continue;
            if (is_link($full)) continue;

            if (is_dir($full)) {
                if (in_array($entry,$hardSkipDirs,true)) continue;
                $stack[]=$full;
                continue;
            }

            if (!is_file($full)) continue;

            $stats['files_scanned']++;
            $size=@filesize($full); $mtime=@filemtime($full);
            $size=$size===false ? 0 : (int)$size;
            $mtime=$mtime===false ? 0 : (int)$mtime;
            $stats['bytes_scanned'] += $size;

            $x=classify_file($relative,$size,$mtime,$knownCleanup,$protectedExact);
            if ($x!==null) $items[]=$x;
        }
    }

    $order=['CONFIRMED CLEANUP'=>0,'LIKELY CLEANUP'=>1,'REVIEW'=>2,'PROTECTED'=>3];
    usort($items,function(array $a,array $b) use ($order): int {
        $sa=$order[$a['status']] ?? 9;
        $sb=$order[$b['status']] ?? 9;
        if ($sa!==$sb) return $sa<=>$sb;
        $c=strcasecmp($a['category'],$b['category']);
        return $c!==0 ? $c : strcasecmp($a['path'],$b['path']);
    });

    return ['items'=>$items,'errors'=>$errors,'stats'=>$stats];
}

function summarize(array $scan): array {
    $s=[
        'item_count'=>0,'item_bytes'=>0,
        'confirmed_count'=>0,'confirmed_bytes'=>0,
        'likely_count'=>0,'likely_bytes'=>0,
        'review_count'=>0,'review_bytes'=>0,
        'protected_count'=>0,'protected_bytes'=>0,
        'categories'=>[]
    ];

    foreach ($scan['items'] as $x) {
        $s['item_count']++; $s['item_bytes'] += (int)$x['size'];

        $key=strtolower(str_replace(' ','_',$x['status']));
        if ($x['status']==='CONFIRMED CLEANUP') { $s['confirmed_count']++; $s['confirmed_bytes'] += (int)$x['size']; }
        elseif ($x['status']==='LIKELY CLEANUP') { $s['likely_count']++; $s['likely_bytes'] += (int)$x['size']; }
        elseif ($x['status']==='REVIEW') { $s['review_count']++; $s['review_bytes'] += (int)$x['size']; }
        elseif ($x['status']==='PROTECTED') { $s['protected_count']++; $s['protected_bytes'] += (int)$x['size']; }

        $c=$x['category'];
        if (!isset($s['categories'][$c])) {
            $s['categories'][$c]=['count'=>0,'bytes'=>0,'confirmed'=>0,'likely'=>0,'review'=>0,'protected'=>0];
        }
        $s['categories'][$c]['count']++;
        $s['categories'][$c]['bytes'] += (int)$x['size'];
        if ($x['status']==='CONFIRMED CLEANUP') $s['categories'][$c]['confirmed']++;
        elseif ($x['status']==='LIKELY CLEANUP') $s['categories'][$c]['likely']++;
        elseif ($x['status']==='REVIEW') $s['categories'][$c]['review']++;
        elseif ($x['status']==='PROTECTED') $s['categories'][$c]['protected']++;
    }

    ksort($s['categories'],SORT_NATURAL|SORT_FLAG_CASE);
    return $s;
}

function known_manifest_presence(string $root,array $knownCleanup): array {
    $present=[]; $missing=[];
    foreach ($knownCleanup as $path=>$true) {
        if (is_file($root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$path))) $present[]=$path;
        else $missing[]=$path;
    }
    return ['present'=>$present,'missing'=>$missing];
}

$run=isset($_GET['scan']) || isset($_POST['scan']) || isset($_GET['export']);
$scan=null; $summary=null; $knownPresence=null;

if ($run) {
    $scan=walk_site($root,$topLevelExclusions,$hardSkipDirs,$knownCleanup,$protectedExact);
    $summary=summarize($scan);
    $knownPresence=known_manifest_presence($root,$knownCleanup);

    if (($_GET['export'] ?? '')==='json') {
        $payload=[
            'tool'=>'MRL House Cleaning Inventory',
            'version'=>MRL_HOUSE_CLEAN_VERSION,
            'generated_at'=>date(DATE_ATOM),
            'root'=>$root,
            'read_only'=>true,
            'known_manifest_count'=>count($knownCleanup),
            'known_manifest_present'=>$knownPresence['present'],
            'known_manifest_missing'=>$knownPresence['missing'],
            'top_level_exclusions'=>$topLevelExclusions,
            'stats'=>$scan['stats'],
            'summary'=>$summary,
            'errors'=>$scan['errors'],
            'items'=>$scan['items']
        ];
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="MRL_house_cleaning_inventory_v002_'.date('Ymd_His').'.json"');
        echo json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$copy='';
if ($scan && $summary) {
    $lines=[
        'MRL HOUSE CLEANING INVENTORY v002',
        'Generated: '.date('n/j/Y g:i:s a T'),
        'Root: '.$root,
        'Mode: READ ONLY',
        '',
        'Files scanned: '.number_format((int)$scan['stats']['files_scanned']),
        'Directories scanned: '.number_format((int)$scan['stats']['dirs_scanned']),
        '',
        'CONFIRMED CLEANUP: '.number_format((int)$summary['confirmed_count']).' / '.fmt_bytes((int)$summary['confirmed_bytes']),
        'LIKELY CLEANUP: '.number_format((int)$summary['likely_count']).' / '.fmt_bytes((int)$summary['likely_bytes']),
        'REVIEW: '.number_format((int)$summary['review_count']).' / '.fmt_bytes((int)$summary['review_bytes']),
        'PROTECTED: '.number_format((int)$summary['protected_count']).' / '.fmt_bytes((int)$summary['protected_bytes']),
        '',
        'Known manifest entries: '.count($knownCleanup),
        'Known manifest present: '.count($knownPresence['present']),
        'Known manifest already missing: '.count($knownPresence['missing']),
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
<title>MRL House Cleaning Inventory v002</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1240px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.note{padding:10px 12px;border:1px solid #526170;border-radius:9px;background:#17232c;color:#dbefff}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.card{padding:11px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.card .value{display:block;margin-top:3px;font-size:22px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
.mono{font-family:Consolas,"Courier New",monospace}
.actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}
button,.btn{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}
.scan{background:#2674a8}.copy{background:#2b8354}.export{background:#bd8320}
button:disabled{opacity:.45;cursor:not-allowed}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{position:sticky;top:0;background:#202624;color:var(--gold);z-index:2}
.status{display:inline-block;white-space:nowrap;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800}
.confirmed{background:#145d36;border:1px solid #55dc8c;color:#e8fff1}
.likely{background:#4f4315;border:1px solid #d0aa49;color:#ffe6a7}
.review{background:#503215;border:1px solid #c98842;color:#ffd3a0}
.protected{background:#173d5a;border:1px solid #69a8dc;color:#dcefff}
.filters{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 10px}
.filter{padding:6px 10px;border:1px solid var(--border);border-radius:999px;background:#252b29;color:var(--text);cursor:pointer}
.filter.active{border-color:#7db7e6;background:#22435e}
.table-wrap{max-height:700px;overflow:auto;border:1px solid #37403c;border-radius:8px}
.path{overflow-wrap:anywhere}.category{font-weight:700;color:#d9e9f5}
ul{margin:5px 0 0;padding-left:20px;line-height:1.45}
code{color:#f8d89a}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body><div class="wrap">
<h1>MRL House Cleaning Inventory v002</h1>

<div class="readonly">READ ONLY — still no delete button. v002 separates exact known artifacts from probable cleanup and protected active files.</div>

<div class="panel">
<h2>What changed from v001</h2>
<div class="note">
<strong>Best of both worlds:</strong> exact known-artifact matching + conservative filename/path heuristics.
Only exact manifest matches get <strong>CONFIRMED CLEANUP</strong>. Everything else is either likely, review, or protected.
</div>
<ul>
<li><strong>CONFIRMED CLEANUP</strong> = exact filename already positively identified during this cleanup review.</li>
<li><strong>LIKELY CLEANUP</strong> = strong pattern match, but not yet proven by exact manifest.</li>
<li><strong>REVIEW</strong> = backups/legacy material needing a retention or dependency decision.</li>
<li><strong>PROTECTED</strong> = active/intentional items that v001 could misread.</li>
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
<div class="card"><span class="small">Confirmed cleanup</span><span class="value"><?php echo number_format($summary['confirmed_count']); ?></span><span class="small"><?php echo h(fmt_bytes($summary['confirmed_bytes'])); ?></span></div>
<div class="card"><span class="small">Likely cleanup</span><span class="value"><?php echo number_format($summary['likely_count']); ?></span><span class="small"><?php echo h(fmt_bytes($summary['likely_bytes'])); ?></span></div>
<div class="card"><span class="small">Review</span><span class="value"><?php echo number_format($summary['review_count']); ?></span><span class="small"><?php echo h(fmt_bytes($summary['review_bytes'])); ?></span></div>
<div class="card"><span class="small">Protected</span><span class="value"><?php echo number_format($summary['protected_count']); ?></span><span class="small"><?php echo h(fmt_bytes($summary['protected_bytes'])); ?></span></div>
</div>
</div>

<div class="panel">
<h2>Known Manifest Check</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<tr><td>Known manifest entries</td><td><span class="status protected">INFO</span></td><td><?php echo number_format(count($knownCleanup)); ?> exact filenames</td></tr>
<tr><td>Present on server</td><td><span class="status confirmed">MATCH</span></td><td><?php echo number_format(count($knownPresence['present'])); ?></td></tr>
<tr><td>Already absent</td><td><span class="status protected">INFO</span></td><td><?php echo number_format(count($knownPresence['missing'])); ?></td></tr>
</table>
</div>

<div class="panel">
<h2>Category Summary</h2>
<table>
<tr><th>Category</th><th>Count</th><th>Size</th><th>Confirmed</th><th>Likely</th><th>Review</th><th>Protected</th></tr>
<?php foreach ($summary['categories'] as $cat=>$d): ?>
<tr>
<td><?php echo h($cat); ?></td>
<td><?php echo number_format($d['count']); ?></td>
<td><?php echo h(fmt_bytes($d['bytes'])); ?></td>
<td><?php echo number_format($d['confirmed']); ?></td>
<td><?php echo number_format($d['likely']); ?></td>
<td><?php echo number_format($d['review']); ?></td>
<td><?php echo number_format($d['protected']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Inventory</h2>
<div class="filters">
<button class="filter active" data-filter="all" type="button">All</button>
<button class="filter" data-filter="CONFIRMED CLEANUP" type="button">Confirmed Cleanup</button>
<button class="filter" data-filter="LIKELY CLEANUP" type="button">Likely Cleanup</button>
<button class="filter" data-filter="REVIEW" type="button">Review</button>
<button class="filter" data-filter="PROTECTED" type="button">Protected</button>
</div>

<div class="table-wrap">
<table id="inventory"><thead><tr><th>Status</th><th>Category</th><th>Path</th><th>Size</th><th>Age</th><th>Reason</th></tr></thead><tbody>
<?php foreach ($scan['items'] as $x):
$cls=$x['status']==='CONFIRMED CLEANUP'?'confirmed':($x['status']==='LIKELY CLEANUP'?'likely':($x['status']==='PROTECTED'?'protected':'review'));
?>
<tr data-status="<?php echo h($x['status']); ?>">
<td><span class="status <?php echo h($cls); ?>"><?php echo h($x['status']); ?></span></td>
<td class="category"><?php echo h($x['category']); ?></td>
<td class="mono path"><?php echo h($x['path']); ?></td>
<td><?php echo h(fmt_bytes((int)$x['size'])); ?></td>
<td><?php echo $x['age_days']===null?'—':number_format($x['age_days']).' d'; ?><div class="small"><?php echo h($x['modified']); ?></div></td>
<td class="small"><?php echo h($x['reason']); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div>

<div class="panel small"><strong>Next:</strong> send the v002 JSON report back to ChatGPT. We can then approve the exact confirmed bucket and work through likely/review groups separately.</div>
<?php else: ?>
<div class="panel small">Click <strong>Scan Live Filesystem</strong>. This scan is read-only.</div>
<?php endif; ?>

<div class="panel small">FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v002 | LAST MODIFIED: 9/15/2026 4:50:59 pm</div>
<textarea id="copySource" style="position:absolute;left:-9999px;top:-9999px"><?php echo h($copy); ?></textarea>
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
var fs=document.querySelectorAll('.filter'),rows=document.querySelectorAll('#inventory tbody tr');
Array.prototype.forEach.call(fs,function(btn){
 btn.addEventListener('click',function(){
  var wanted=btn.getAttribute('data-filter');
  Array.prototype.forEach.call(fs,function(x){x.classList.remove('active');});
  btn.classList.add('active');
  Array.prototype.forEach.call(rows,function(r){
   var st=r.getAttribute('data-status');
   r.style.display=(wanted==='all'||wanted===st)?'':'none';
  });
 });
});
}());
</script>
</body></html>
