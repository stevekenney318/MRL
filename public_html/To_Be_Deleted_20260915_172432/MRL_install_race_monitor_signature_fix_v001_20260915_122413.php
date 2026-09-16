<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');

/*
 * MRL installer — Race Monitor internal signature cleanup
 * INSTALLER VERSION: v001
 * GENERATED: 9/15/2026 12:24:13 pm ET
 * TARGET: /race_results/race_results_monitor.php v141 -> v142
 * PURPOSE: update stale RR_MONITOR_SIGNATURE v138 -> v142 only.
 */

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$target = $docRoot . '/race_results/race_results_monitor.php';
$backupDir = $docRoot . '/_mrl_installer_backups';
$backup = $backupDir . '/race_results_monitor.php.pre_v142_20260915_122413.bak';
$temp = dirname($target) . '/.race_results_monitor_v142_20260915_122413.tmp.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rep1(string $s,string $a,string $b,string $label): string {
    $n=substr_count($s,$a); if($n!==1) throw new RuntimeException($label.': expected 1 anchor, found '.$n); return str_replace($a,$b,$s);
}
function build142(string $src): string {
    $src=rep1($src," * VERSION: v141
"," * VERSION: v142
",'version');
    $anchor=" * CHANGELOG:
 *
";
    $entry=" * CHANGELOG:
 *
 * v142 (9/15/2026 12:24:13 pm ET)
 *   - MAINTENANCE: Updated RR_MONITOR_SIGNATURE from stale v138 to v142.
 *   - CHANGE: Heartbeat/log diagnostic signature now matches the active file version.
 *   - PRESERVE: Race monitoring, schedule refresh, snapshots, scoring, notifications, scheduler ownership, and RD behavior are unchanged.
 *
";
    $src=rep1($src,$anchor,$entry,'changelog');
    $src=rep1($src,"const RR_MONITOR_SIGNATURE = 'RACE_RESULTS_MONITOR v138';","const RR_MONITOR_SIGNATURE = 'RACE_RESULTS_MONITOR v142';",'signature');
    return $src;
}
function lintFile(string $p): array {
    if(!function_exists('exec')) return ['SKIPPED','Server does not expose a usable PHP CLI lint command.'];
    $bin=(defined('PHP_BINARY')&&PHP_BINARY)?PHP_BINARY:'php'; $out=[];$code=0; @exec(escapeshellarg($bin).' -l '.escapeshellarg($p).' 2>&1',$out,$code);
    if($code===0) return ['PASS',trim(implode("
",$out))];
    if($code===127||empty($out)) return ['SKIPPED','PHP CLI lint unavailable; postflight verification will still run.'];
    return ['FAIL',trim(implode("
",$out))];
}
function preflight(string $target,string $backupDir): array {
    $rows=[];$ok=true;
    $add=function($c,$s,$d='') use (&$rows,&$ok){ $rows[]=[$c,$s,$d]; if($s==='FAIL')$ok=false; };
    $exists=is_file($target); $add('race_results_monitor.php exists',$exists?'PASS':'FAIL',$target); if(!$exists)return [$ok,$rows,''];
    $rw=is_readable($target)&&is_writable($target); $add('race_results_monitor.php readable/writable',$rw?'PASS':'FAIL'); if(!$rw)return [$ok,$rows,''];
    $src=file_get_contents($target); if(!is_string($src)){ $add('Target readable','FAIL','Unable to read contents.'); return [$ok,$rows,'']; }
    $baseline=strpos($src,'* VERSION: v141')!==false && substr_count($src,"const RR_MONITOR_SIGNATURE = 'RACE_RESULTS_MONITOR v138';")===1;
    $add('Expected baseline/signatures',$baseline?'PASS':'FAIL','v141 with stale internal signature v138');
    $ready=is_dir($backupDir)?is_writable($backupDir):is_writable(dirname($backupDir)); $add('Backup location writable/creatable',$ready?'PASS':'FAIL',$backupDir);
    if($ok){ try{ build142($src); $add('Patch construction','PASS','Only version/changelog/internal monitor signature are changed.'); $add('Database writes','PASS','None.'); $add('Snapshot files changed by installer','PASS','None.'); $add('Expected result','PASS','RR_MONITOR_SIGNATURE becomes RACE_RESULTS_MONITOR v142.'); }catch(Throwable $e){ $add('Patch construction','FAIL',$e->getMessage()); } }
    return [$ok,$rows,$src];
}

$action=(string)($_POST['action']??''); $rows=[];$state='idle';$notice='Nothing has run yet. Click Preview / Preflight first.';$ready=false;$rollback=is_file($backup);
try{
if($action==='preflight'){ [$ok,$rows,$src]=preflight($target,$backupDir); $ready=$ok; $state=$ok?'good':'bad'; $notice=$ok?'Preflight passed — Ready to Install v142.':'Preflight failed — Install remains disabled.'; }
elseif($action==='install'){ [$ok,$rows,$src]=preflight($target,$backupDir); if(!$ok)throw new RuntimeException('Install blocked because preflight no longer passes.'); if(!is_dir($backupDir)&&!mkdir($backupDir,0775,true)&&!is_dir($backupDir))throw new RuntimeException('Could not create backup directory.'); if(is_file($backup))throw new RuntimeException('Backup already exists for this installer timestamp.'); if(!copy($target,$backup))throw new RuntimeException('Backup creation failed.'); $rows[]=['Backup created','PASS',$backup]; $new=build142($src); if(file_put_contents($temp,$new,LOCK_EX)===false)throw new RuntimeException('Temporary write failed.'); [$ls,$ld]=lintFile($temp); $rows[]=['PHP syntax lint',$ls,$ld]; if($ls==='FAIL'){@unlink($temp);throw new RuntimeException('PHP lint failed; target unchanged.');} if(!@rename($temp,$target)){@unlink($temp);throw new RuntimeException('Atomic rename failed.');} $post=file_get_contents($target); $postOk=is_string($post)&&strpos($post,'* VERSION: v142')!==false&&strpos($post,"const RR_MONITOR_SIGNATURE = 'RACE_RESULTS_MONITOR v142';")!==false&&strpos($post,"RACE_RESULTS_MONITOR v138';")===false; if(!$postOk){@copy($backup,$target);throw new RuntimeException('Postflight failed; backup restored.');} $rows[]=['Postflight version/signature','PASS','v142 active; internal signature now matches v142.']; $rows[]=['Install result','PASS','No DB writes and no snapshot files changed.']; $state='good';$notice='Installed successfully — v142 is active.';$rollback=true; }
elseif($action==='rollback'){ if(!is_file($backup))throw new RuntimeException('Rollback backup is unavailable.'); if(!copy($backup,$temp))throw new RuntimeException('Could not stage rollback.'); [$ls,$ld]=lintFile($temp); $rows[]=['Rollback PHP syntax lint',$ls,$ld]; if($ls==='FAIL'){@unlink($temp);throw new RuntimeException('Rollback lint failed.');} if(!@rename($temp,$target)){@unlink($temp);throw new RuntimeException('Rollback rename failed.');} $restored=file_get_contents($target); if(!is_string($restored)||strpos($restored,'* VERSION: v141')===false)throw new RuntimeException('Rollback postflight could not confirm v141.'); $rows[]=['Rollback result','PASS','Restored race_results_monitor.php v141.']; $state='info';$notice='Rollback complete — v141 restored.'; }
}catch(Throwable $e){ $rows[]=['Action','FAIL',$e->getMessage()];$state='bad';$notice='Action failed.';$rollback=is_file($backup); }
$hasRows=!empty($rows);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MRL Race Monitor Signature Fix</title>
<style>:root{color-scheme:dark;--bg:#101312;--panel:#1b201f;--border:#46504d;--text:#eee9df;--muted:#b8b7b0;--gold:#f1c97f;--green:#167c45;--red:#a93434;--blue:#286c99;--amber:#d49b28;--disabled:#555}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}.wrap{width:min(1120px,95%);margin:14px auto 28px}h1{margin:0 0 10px;color:var(--gold);font-size:26px}h2{margin:0 0 8px;color:var(--gold);font-size:18px}.panel{margin:0 0 10px;padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--panel)}.notice{margin:0 0 10px;padding:10px 12px;border-radius:9px;font-weight:700}.good{background:#103b27;border:1px solid #2f9b63}.bad{background:#4b1d1d;border:1px solid #c04b4b}.info{background:#173246;border:1px solid #387ba8}.idle{background:#332b15;border:1px solid #8c722e}table{width:100%;border-collapse:collapse}th,td{padding:6px 8px;border-bottom:1px solid #353c3a;text-align:left;vertical-align:top}th{color:var(--gold)}.PASS{color:#5ee58e;font-weight:800}.FAIL{color:#ff7b7b;font-weight:800}.SKIPPED{color:#f1c97f;font-weight:800}.small{font-size:12px;color:var(--muted)}.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}.actions{display:flex;gap:9px;flex-wrap:wrap}button{padding:8px 12px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}.preflight{background:var(--blue)}.install{background:var(--green)}.rollback{background:var(--red)}.export{background:var(--amber)}button:disabled{opacity:.45;cursor:not-allowed;background:var(--disabled)}ul{margin:5px 0 0;padding-left:20px;line-height:1.45}</style></head><body><div class="wrap">
<h1>MRL Race Monitor Signature Cleanup</h1>
<div class="panel"><h2>What this does</h2><ul><li>Updates the stale internal monitor signature from <code>v138</code> to <code>v142</code>.</li><li>Advances <code>race_results_monitor.php</code> from v141 to v142.</li><li>Does not change race logic, scoring, snapshots, scheduler behavior, or notifications.</li></ul></div>
<div class="notice <?=h($state)?>"><?=h($notice)?></div>
<div class="panel"><h2>Preflight / Result</h2><?php if(!$hasRows): ?><p class="small">Click Preview / Preflight first. Install remains disabled until the exact v141 baseline and stale v138 signature are confirmed.</p><?php else: ?><table id="resultsTable"><tr><th>Check</th><th>Status</th><th>Detail</th></tr><?php foreach($rows as $r): ?><tr><td><?=h($r[0])?></td><td class="<?=h($r[1])?>"><?=h($r[1])?></td><td class="small mono"><?=h($r[2])?></td></tr><?php endforeach; ?></table><?php endif; ?></div>
<div class="panel"><h2>Actions</h2><div class="actions"><form method="post"><input type="hidden" name="action" value="preflight"><button class="preflight" type="submit">Preview / Preflight</button></form><form method="post"><input type="hidden" name="action" value="install"><button class="install" type="submit" <?=$ready?'':'disabled'?>>Install v142</button></form><form method="post" onsubmit="return confirm('Restore the exact pre-v142 race_results_monitor.php?');"><input type="hidden" name="action" value="rollback"><button class="rollback" type="submit" <?=$rollback?'':'disabled'?>>Rollback</button></form><button id="exportBtn" class="export" type="button" <?=$hasRows?'':'disabled'?>>Export Results</button></div></div>
<div class="panel small"><strong>Expected after install:</strong> heartbeat/log diagnostic lines identify <code>RACE_RESULTS_MONITOR v142</code>. Operational behavior is otherwise unchanged.<br><br>FILE: MRL_install_race_monitor_signature_fix_v001_20260915_122413.php | INSTALLER VERSION: v001</div></div>
<script>(function(){var b=document.getElementById('exportBtn');if(!b||b.disabled)return;b.addEventListener('click',function(){var lines=['MRL Race Monitor Signature Cleanup','Generated: 9/15/2026 12:24:13 pm ET','State: <?=h($notice)?>',''];document.querySelectorAll('#resultsTable tr').forEach(function(tr){var c=tr.querySelectorAll('th,td');if(c.length===3)lines.push(c[0].textContent.trim()+' | '+c[1].textContent.trim()+' | '+c[2].textContent.trim());});var blob=new Blob([lines.join('
')+'
'],{type:'text/plain;charset=utf-8'}),u=URL.createObjectURL(blob),a=document.createElement('a');a.href=u;a.download='MRL_race_monitor_signature_fix_results_20260915_122413.txt';document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(u);});})();</script></body></html>
