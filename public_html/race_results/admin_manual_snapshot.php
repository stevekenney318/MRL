<?php
declare(strict_types=1);

/**
 * admin_manual_snapshot.php
 *
 * VERSION: v001
 * CREATED: 11/9/2026
 *
 * PURPOSE:
 * - Admin-only utility for creating a manual MRL scoring snapshot from an
 *   existing local ESPN canonical snapshot.
 * - Manual files are stored under each race folder in _manual_snapshots/.
 * - ESPN canonical snapshots, final_table_hash.txt, revision_meta.json, and
 *   revision-monitor state are never modified.
 * - A year-level manifest controls which manual snapshot, if any, is active.
 * - Deactivation restores normal ESPN-based scoring without deleting audit files.
 *
 * PHP: 7.3 compatible.
 */

session_start();
$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
date_default_timezone_set('America/New_York');

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';
require_once __DIR__ . '/race_results_snapshot_helper.php';
require_once __DIR__ . '/race_results_snapshot_views_helper.php';
require_once __DIR__ . '/manual_snapshot_override_helper.php';

$user_home = new USER();
if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}
if (!function_exists('isAdmin') || !isAdmin($_SESSION['userSession'] ?? null)) {
    http_response_code(403);
    echo 'Not authorized.';
    exit;
}

function ms_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ms_int($v): int { return (int)preg_replace('/[^0-9\-]/', '', (string)$v); }
function ms_norm(string $v): string { return trim((string)preg_replace('/\s+/', ' ', html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8'))); }

function ms_available_years(string $baseDir): array
{
    $out = [];
    foreach (glob(rtrim($baseDir, '/\\') . '/20[0-9][0-9]', GLOB_ONLYDIR) ?: [] as $dir) $out[] = basename($dir);
    rsort($out, SORT_STRING);
    return $out;
}

function ms_races_for_year(string $yearFolder): array
{
    $rows = [];
    foreach (glob(rtrim($yearFolder, '/\\') . '/R[0-9]*_*', GLOB_ONLYDIR) ?: [] as $dir) {
        $base = basename($dir);
        if (!preg_match('/^R(\d+)_/', $base, $m)) continue;
        $meta = mrlm_load_json_file($dir . '/_meta.json');
        $num = (int)$m[1];
        $code = 'R' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
        $name = (string)($meta['race_name'] ?? preg_replace('/^R\d+_/', '', $base));
        $name = preg_replace('/^NASCAR Cup Series at\s+/i', '', $name);
        $rows[] = ['code'=>$code,'number'=>$num,'name'=>$name,'folder'=>$dir,'folder_base'=>$base,'meta'=>$meta];
    }
    usort($rows, function($a,$b){ return ((int)$b['number']) <=> ((int)$a['number']); });
    return $rows;
}

function ms_find_race(array $races, string $raceCode): array
{
    foreach ($races as $r) if ((string)$r['code'] === $raceCode) return $r;
    return [];
}

function ms_parse_editable_rows(string $snapshot): array
{
    if (!is_file($snapshot)) return [];
    $html = @file_get_contents($snapshot);
    if ($html === false || trim($html) === '') return [];
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    if (!$loaded) return [];
    $xp = new DOMXPath($dom);
    list($table, $headerRow, $idx) = rrs_find_scoring_table($xp);
    if (!$table instanceof DOMElement || empty($idx)) return [];
    $rows = $xp->query('.//tr[td]', $table);
    $out = [];
    if (!$rows) return $out;
    foreach ($rows as $row) {
        if (!$row instanceof DOMElement) continue;
        $tds = $xp->query('./td', $row);
        if (!$tds || $tds->length === 0) continue;
        $first = ms_norm((string)$tds->item(0)->textContent);
        if (!preg_match('/^\d+$/', preg_replace('/\D+/', '', $first))) continue;
        $driver = ($idx['driver'] < $tds->length) ? ms_norm((string)$tds->item($idx['driver'])->textContent) : '';
        if ($driver === '') continue;
        $out[] = [
            'position' => $first,
            'driver' => $driver,
            'pts' => ($idx['pts'] < $tds->length) ? ms_int($tds->item($idx['pts'])->textContent) : 0,
            'bonus' => ($idx['bonus'] < $tds->length) ? ms_int($tds->item($idx['bonus'])->textContent) : 0,
            'penalty' => ($idx['penalty'] < $tds->length) ? ms_int($tds->item($idx['penalty'])->textContent) : 0,
        ];
    }
    return $out;
}

function ms_apply_edits(string $sourceSnapshot, array $postedRows): array
{
    $result = ['ok'=>false,'html'=>'','changes'=>[],'message'=>''];
    $html = @file_get_contents($sourceSnapshot);
    if ($html === false || trim($html) === '') { $result['message']='Source snapshot is unreadable.'; return $result; }

    $edits = [];
    foreach ($postedRows as $p) {
        if (!is_array($p)) continue;
        $driver = ms_norm((string)($p['driver'] ?? ''));
        if ($driver === '') continue;
        $edits[strtolower($driver)] = [
            'driver'=>$driver,
            'pts'=>(int)($p['pts'] ?? 0),
            'bonus'=>(int)($p['bonus'] ?? 0),
            'penalty'=>(int)($p['penalty'] ?? 0),
        ];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = @$dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    if (!$loaded) { $result['message']='Could not parse source snapshot HTML.'; return $result; }
    $xp = new DOMXPath($dom);
    list($table, $headerRow, $idx) = rrs_find_scoring_table($xp);
    if (!$table instanceof DOMElement || empty($idx)) { $result['message']='Scoring table was not found.'; return $result; }
    $rows = $xp->query('.//tr[td]', $table);
    if (!$rows) { $result['message']='No scoring rows were found.'; return $result; }

    foreach ($rows as $row) {
        if (!$row instanceof DOMElement) continue;
        $tds = $xp->query('./td', $row);
        if (!$tds || $tds->length === 0) continue;
        $driver = ($idx['driver'] < $tds->length) ? ms_norm((string)$tds->item($idx['driver'])->textContent) : '';
        $key = strtolower($driver);
        if ($driver === '' || !isset($edits[$key])) continue;
        foreach (['pts','bonus','penalty'] as $field) {
            $cellIndex = $idx[$field];
            if ($cellIndex === null || $cellIndex >= $tds->length) continue;
            $old = ms_int($tds->item($cellIndex)->textContent);
            $new = (int)$edits[$key][$field];
            if ($old === $new) continue;
            while ($tds->item($cellIndex)->firstChild) $tds->item($cellIndex)->removeChild($tds->item($cellIndex)->firstChild);
            $tds->item($cellIndex)->appendChild($dom->createTextNode((string)$new));
            $result['changes'][] = ['driver'=>$driver,'field'=>$field,'before'=>$old,'after'=>$new];
        }
    }

    if (empty($result['changes'])) { $result['message']='No scoring values were changed.'; return $result; }
    $saved = $dom->saveHTML();
    if (!is_string($saved) || trim($saved) === '') { $result['message']='Could not serialize edited snapshot.'; return $result; }
    $result['ok']=true; $result['html']=$saved; $result['message']='Manual snapshot content generated.';
    return $result;
}

function ms_stamp_from_effective(string $effective): array
{
    try { $dt = new DateTime($effective, new DateTimeZone('America/New_York')); }
    catch (Exception $e) { return ['', '']; }
    return [$dt->format('Ymd_His') . '000', $dt->format('Y-m-d H:i:s')];
}

function ms_atomic_write(string $path, string $content): bool
{
    $dir=dirname($path); if(!is_dir($dir) && !@mkdir($dir,0755,true)) return false;
    $tmp=$path.'.tmp_'.getmypid().'_'.date('Ymd_His');
    if(@file_put_contents($tmp,$content,LOCK_EX)===false) return false;
    @chmod($tmp,0644);
    if(!@rename($tmp,$path)){@unlink($tmp);return false;}
    @chmod($path,0644); return true;
}

function ms_next_display_version(string $yearFolder, string $raceCode, array $manifest): string
{
    $history = mrlm_load_json_file(rtrim($yearFolder,'/\\').'/_weekly_standings_release_history.json');
    $count=0;
    foreach (($history['releases'] ?? []) as $r) if(is_array($r) && (string)($r['race_code'] ?? '')===$raceCode) $count++;
    foreach (($manifest['records'] ?? []) as $r) if(is_array($r) && (string)($r['race_code'] ?? '')===$raceCode) $count++;
    return 'v'.(string)($count+1);
}

$baseDir = __DIR__;
$years = ms_available_years($baseDir);
$year = preg_replace('/[^0-9]/','',(string)($_REQUEST['year'] ?? ($years[0] ?? '2026')));
if (!in_array($year,$years,true)) $year=$years[0] ?? '2026';
$yearFolder=$baseDir.'/'.$year;
$races=ms_races_for_year($yearFolder);
$raceCode=strtoupper(trim((string)($_REQUEST['race'] ?? ($races[0]['code'] ?? ''))));
$race=ms_find_race($races,$raceCode);
if(empty($race) && !empty($races)){ $race=$races[0]; $raceCode=(string)$race['code']; }
$canonicalFiles=!empty($race)?rrs_canonical_snapshot_files((string)$race['folder']):[];
$sourceBase=basename((string)($_REQUEST['source'] ?? (!empty($canonicalFiles)?$canonicalFiles[count($canonicalFiles)-1]:'')));
$sourcePath='';
foreach($canonicalFiles as $f) if(basename($f)===$sourceBase){$sourcePath=$f;break;}
if($sourcePath==='' && !empty($canonicalFiles)){ $sourcePath=$canonicalFiles[count($canonicalFiles)-1]; $sourceBase=basename($sourcePath); }
$rows=$sourcePath!==''?ms_parse_editable_rows($sourcePath):[];
$manifest=mrlm_load_manifest($yearFolder,$year);
$message=''; $messageClass='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=(string)($_POST['action'] ?? '');
    if ($action==='deactivate') {
        $manifest['active_by_race'][$raceCode]='';
        $wr=mrlm_write_manifest($yearFolder,$manifest);
        $message=!empty($wr['ok'])?'Manual override deactivated. Weekly Standings now uses the ESPN snapshot chain again.':(string)$wr['message'];
        $messageClass=!empty($wr['ok'])?'ok':'bad';
    } elseif ($action==='activate') {
        $id=(string)($_POST['manual_id'] ?? '');
        $rec=mrlm_find_record($manifest,$id);
        if(empty($rec) || (string)($rec['race_code'] ?? '')!==$raceCode){$message='Manual snapshot record not found.';$messageClass='bad';}
        else {
            $path=rtrim((string)$race['folder'],'/\\').'/'.str_replace('\\','/',(string)$rec['snapshot_rel_path']);
            if(!is_file($path)){$message='Manual snapshot file is missing.';$messageClass='bad';}
            else {$manifest['active_by_race'][$raceCode]=$id;$wr=mrlm_write_manifest($yearFolder,$manifest);$message=!empty($wr['ok'])?'Manual override activated.':(string)$wr['message'];$messageClass=!empty($wr['ok'])?'ok':'bad';}
        }
    } elseif ($action==='generate' || $action==='generate_activate') {
        $effective=(string)($_POST['effective_at'] ?? '');
        list($stamp,$effectiveRaw)=ms_stamp_from_effective($effective);
        $reason=trim((string)($_POST['reason'] ?? ''));
        if($stamp===''){$message='Choose a valid effective timestamp.';$messageClass='bad';}
        elseif($sourcePath===''){$message='Source ESPN snapshot not found.';$messageClass='bad';}
        else {
            $applied=ms_apply_edits($sourcePath,$_POST['edit'] ?? []);
            if(empty($applied['ok'])){$message=(string)$applied['message'];$messageClass='bad';}
            else {
                $manualDir=(string)$race['folder'].'/_manual_snapshots';
                if(!is_dir($manualDir) && !@mkdir($manualDir,0755,true)){$message='Could not create _manual_snapshots folder.';$messageClass='bad';}
                else {
                    $base='manual_snapshot_'.$stamp;
                    $manualPath=$manualDir.'/'.$base.'.html';
                    if(is_file($manualPath)){$message='A manual snapshot already exists for that timestamp.';$messageClass='bad';}
                    else {
                        $manualHtml = '<!-- MRL MANUAL SCORING SNAPSHOT | source=' . ms_h($sourceBase) . ' | effective=' . ms_h($effectiveRaw) . ' America/New_York | NOT RETRIEVED FROM ESPN -->' . "\n" . (string)$applied['html'];
                        if(!ms_atomic_write($manualPath,$manualHtml)){$message='Could not write manual snapshot.';$messageClass='bad';}
                        else {
                        $tmpDir=sys_get_temp_dir().'/mrl_manual_'.getmypid().'_'.str_replace('.','',uniqid('',true));
                        @mkdir($tmpDir,0755,true);
                        $tmpCanonical=$tmpDir.'/snapshot_'.$stamp.'.html';
                        @file_put_contents($tmpCanonical,$manualHtml,LOCK_EX);
                        $comp=rrsv_generate_companion_set($tmpCanonical,(int)$year,(int)$race['number'],(string)$race['folder_base'],$dbo ?? null,$dbconnect ?? null,true);
                        $copyOk=!empty($comp['ok']);
                        $companions=[];
                        foreach(['lite','mrl','mrl_segment'] as $kind){
                            $src=$tmpDir.'/snapshot_'.$stamp.'_'.$kind.'.html';
                            $dst=$manualDir.'/'.$base.'_'.$kind.'.html';
                            if(!is_file($src) || !@copy($src,$dst)){$copyOk=false;} else {$companions[$kind]='_manual_snapshots/'.basename($dst);@chmod($dst,0644);}
                        }
                        foreach(glob($tmpDir.'/*') ?: [] as $f) @unlink($f); @rmdir($tmpDir);
                        if(!$copyOk){$message='Manual snapshot was written, but companion generation failed: '.implode(' | ',$comp['errors'] ?? []);$messageClass='bad';}
                        else {
                            $manualId='manual_'.$stamp.'_'.$raceCode;
                            $displayVersion=ms_next_display_version($yearFolder,$raceCode,$manifest);
                            $record=[
                                'manual_id'=>$manualId,'year'=>$year,'race_code'=>$raceCode,'race_number'=>(int)$race['number'],'race_name'=>(string)($race['meta']['race_name'] ?? $race['name']),
                                'race_folder_rel'=>(string)$race['folder_base'],'source_type'=>'manual_override','source_snapshot'=>$sourceBase,
                                'source_snapshot_id'=>preg_replace('/^snapshot_|\.html$/','',$sourceBase),'source_sha256'=>hash_file('sha256',$sourcePath),
                                'snapshot_rel_path'=>'_manual_snapshots/'.$base.'.html','companion_files'=>$companions,
                                'effective_stamp'=>$stamp,'effective_at'=>$effectiveRaw,'created_at'=>date('Y-m-d H:i:s'),'display_version'=>$displayVersion,
                                'reason'=>$reason!==''?$reason:'Manual scoring correction based on official information not yet reflected by ESPN.',
                                'changes'=>$applied['changes'],'changes_count'=>count($applied['changes']),
                                'manual_sha256'=>hash_file('sha256',$manualPath),'active_on_create'=>($action==='generate_activate'),
                            ];
                            $summary="MRL MANUAL SNAPSHOT SUMMARY\nManual ID: {$manualId}\nRace: {$raceCode} ".(string)$race['name']."\nSource ESPN snapshot: {$sourceBase}\nSource SHA-256: ".$record['source_sha256']."\nEffective: {$effectiveRaw} America/New_York\nCreated: ".$record['created_at']."\nChanges: ".count($applied['changes'])."\nReason: ".$record['reason']."\n\n";
                            foreach($applied['changes'] as $ch)$summary.=$ch['driver'].' '.$ch['field'].' '.$ch['before'].' -> '.$ch['after']."\n";
                            ms_atomic_write($manualDir.'/manual_snapshot_summary_'.$stamp.'.txt',$summary);
                            ms_atomic_write($manualDir.'/manual_snapshot_meta_'.$stamp.'.json',json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
                            $manifest['records'][]=$record;
                            if($action==='generate_activate')$manifest['active_by_race'][$raceCode]=$manualId;
                            $wr=mrlm_write_manifest($yearFolder,$manifest);
                            if(empty($wr['ok'])){$message='Manual files created, but manifest save failed: '.(string)$wr['message'];$messageClass='bad';}
                            else {$message='Manual snapshot family created'.($action==='generate_activate'?' and activated.':'.');$messageClass='ok';}
                        }
                        }
                    }
                }
            }
        }
    }
    $manifest=mrlm_load_manifest($yearFolder,$year);
    $rows=$sourcePath!==''?ms_parse_editable_rows($sourcePath):[];
}

$active=mrlm_active_record_for_race($yearFolder,$raceCode);
$records=[]; foreach(($manifest['records'] ?? []) as $rec) if(is_array($rec) && (string)($rec['race_code'] ?? '')===$raceCode)$records[]=$rec;
usort($records,function($a,$b){return strcmp((string)($b['created_at']??''),(string)($a['created_at']??''));});
$effectiveDefault=date('Y-m-d\TH:i:s');
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MRL Manual Snapshot Utility</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#111;color:#eee;margin:0}.wrap{width:96%;max-width:1250px;margin:18px auto 40px}.card{background:#1b1b1b;border:1px solid #444;border-radius:10px;padding:14px;margin:12px 0}h1{margin:0 0 8px}h2{margin:0 0 10px;font-size:20px}.row{display:flex;gap:8px;flex-wrap:wrap;align-items:end}.field{display:flex;flex-direction:column;gap:4px}.field label{font-size:12px;color:#bbb}.field input,.field select,.field textarea{background:#0d0d0d;color:#eee;border:1px solid #666;border-radius:6px;padding:7px}.btn{border:1px solid #777;border-radius:7px;padding:8px 12px;color:#fff;background:#245ea8;cursor:pointer;text-decoration:none;display:inline-block}.btn.green{background:#187a36}.btn.red{background:#a33131}.btn.gray{background:#444}.msg{padding:10px;border-radius:7px;margin:10px 0}.msg.ok{background:#133c22;border:1px solid #2f8d4f}.msg.bad{background:#4a1717;border:1px solid #b74b4b}.status{font-weight:700}.manual{color:#ffcc66}table{width:100%;border-collapse:collapse;background:#fff;color:#111}th,td{border:1px solid #999;padding:5px 7px;text-align:left}th{background:#ddd}td.num,th.num{text-align:right}input.num{width:72px;text-align:right}.small{font-size:12px;color:#bbb}.scroll{overflow:auto}.warn{color:#ffcc66}.good{color:#8ee39f}@media(max-width:700px){table{font-size:13px}.wrap{width:99%}}
</style></head><body><div class="wrap">
<h1>MRL Manual Snapshot Utility</h1><div class="small">Manual scoring snapshots are separate from ESPN snapshots. The revision monitor continues watching ESPN normally.</div>
<?php if($message!==''): ?><div class="msg <?php echo ms_h($messageClass); ?>"><?php echo ms_h($message); ?></div><?php endif; ?>
<div class="card"><form method="get" class="row"><div class="field"><label>Year</label><select name="year" onchange="this.form.submit()"><?php foreach($years as $y): ?><option <?php echo $y===$year?'selected':''; ?>><?php echo ms_h($y); ?></option><?php endforeach; ?></select></div><div class="field"><label>Race</label><select name="race" onchange="this.form.submit()"><?php foreach($races as $r): ?><option value="<?php echo ms_h($r['code']); ?>" <?php echo $r['code']===$raceCode?'selected':''; ?>><?php echo ms_h($r['code'].' '.$r['name']); ?></option><?php endforeach; ?></select></div><div class="field"><label>ESPN source snapshot</label><select name="source" onchange="this.form.submit()"><?php foreach($canonicalFiles as $f): ?><option value="<?php echo ms_h(basename($f)); ?>" <?php echo basename($f)===$sourceBase?'selected':''; ?>><?php echo ms_h(basename($f)); ?></option><?php endforeach; ?></select></div></form>
<p class="status">Active manual override: <?php echo empty($active)?'<span class="good">NONE — ESPN scoring active</span>':'<span class="manual">'.ms_h((string)$active['display_version'].' MANUAL — '.(string)$active['effective_at']).'</span>'; ?></p>
<?php if(!empty($active)): ?><form method="post"><input type="hidden" name="year" value="<?php echo ms_h($year); ?>"><input type="hidden" name="race" value="<?php echo ms_h($raceCode); ?>"><input type="hidden" name="source" value="<?php echo ms_h($sourceBase); ?>"><button class="btn red" name="action" value="deactivate">Deactivate Manual Override</button></form><?php endif; ?>
</div>

<div class="card"><h2>Create Manual Snapshot</h2><form method="post"><input type="hidden" name="year" value="<?php echo ms_h($year); ?>"><input type="hidden" name="race" value="<?php echo ms_h($raceCode); ?>"><input type="hidden" name="source" value="<?php echo ms_h($sourceBase); ?>">
<div class="row"><div class="field"><label>Effective timestamp (ET)</label><input type="datetime-local" step="1" name="effective_at" value="<?php echo ms_h($effectiveDefault); ?>" required></div><div class="field" style="min-width:420px;flex:1"><label>Reason / note</label><input type="text" name="reason" value="Manual scoring correction based on official information not yet reflected by ESPN."></div></div>
<p class="small">Edit only the values that should differ from the selected ESPN source. PTS already includes BONUS; MRL net scoring is PTS − PENALTY.</p>
<div class="scroll"><table><thead><tr><th>Pos</th><th>Driver</th><th class="num">PTS</th><th class="num">BONUS</th><th class="num">PENALTY</th><th class="num">NET preview</th></tr></thead><tbody>
<?php foreach($rows as $i=>$r): ?><tr><td><?php echo ms_h($r['position']); ?></td><td><?php echo ms_h($r['driver']); ?><input type="hidden" name="edit[<?php echo $i; ?>][driver]" value="<?php echo ms_h($r['driver']); ?>"></td><td class="num"><input class="num pts" type="number" name="edit[<?php echo $i; ?>][pts]" value="<?php echo (int)$r['pts']; ?>"></td><td class="num"><input class="num bonus" type="number" name="edit[<?php echo $i; ?>][bonus]" value="<?php echo (int)$r['bonus']; ?>"></td><td class="num"><input class="num penalty" type="number" name="edit[<?php echo $i; ?>][penalty]" value="<?php echo (int)$r['penalty']; ?>"></td><td class="num net"><?php echo (int)$r['pts']-(int)$r['penalty']; ?></td></tr><?php endforeach; ?>
</tbody></table></div><div style="margin-top:10px" class="row"><button class="btn gray" name="action" value="generate">Generate Only</button><button class="btn green" name="action" value="generate_activate">Generate + Activate</button></div></form></div>

<div class="card"><h2>Manual Snapshot History — <?php echo ms_h($raceCode); ?></h2><?php if(empty($records)): ?><p>No manual snapshots have been created for this race.</p><?php else: ?><div class="scroll"><table><thead><tr><th>Version</th><th>Effective</th><th>Source</th><th>Changes</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($records as $rec): $isActive=((string)($manifest['active_by_race'][$raceCode]??'')===(string)$rec['manual_id']); ?><tr><td><?php echo ms_h((string)$rec['display_version'].' MANUAL'); ?></td><td><?php echo ms_h($rec['effective_at']); ?></td><td><?php echo ms_h($rec['source_snapshot']); ?></td><td><?php echo (int)($rec['changes_count']??0); ?></td><td><?php echo $isActive?'<span class="manual">ACTIVE</span>':'Inactive'; ?></td><td><?php if(!$isActive): ?><form method="post" style="margin:0"><input type="hidden" name="year" value="<?php echo ms_h($year); ?>"><input type="hidden" name="race" value="<?php echo ms_h($raceCode); ?>"><input type="hidden" name="source" value="<?php echo ms_h($sourceBase); ?>"><input type="hidden" name="manual_id" value="<?php echo ms_h($rec['manual_id']); ?>"><button class="btn" name="action" value="activate">Activate</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<p><a class="btn" href="weekly_standings.php?year=<?php echo rawurlencode($year); ?>&race=<?php echo rawurlencode($raceCode); ?>">Open Weekly Standings</a></p></div>
</div><script>document.querySelectorAll('tbody tr').forEach(function(tr){var p=tr.querySelector('input.pts'),q=tr.querySelector('input.penalty'),n=tr.querySelector('.net');if(!p||!q||!n)return;function u(){n.textContent=(parseInt(p.value||'0',10)-parseInt(q.value||'0',10));}p.addEventListener('input',u);q.addEventListener('input',u);});</script></body></html>
