<?php
declare(strict_types=1);
/**
 * pick_reminder_dashboard.php
 * VERSION: v003
 * LAST MODIFIED: 9/6/2026 3:34:16 am
 *
 * CHANGELOG:
 * v003 (9/6/2026 3:34:16 am)
 * - CHANGE: Team-name personalized default reminder message.
 * - NEW: One-time AUTO TEST timestamp control for MRL ID 999.
 * - CHANGE: Dashboard reflects To: MRL Gmail / BCC: team privacy model.
 *
 * v002 (9/6/2026 3:02:08 am)
 * - FIX: Uses corrected deadline parser from pick_reminder_helper.php v002.
 */
date_default_timezone_set('America/New_York');
if(session_status()===PHP_SESSION_NONE)session_start();
$_SESSION['return_to']=$_SERVER['REQUEST_URI']??'/pick_reminder_dashboard.php';
require_once __DIR__.'/config.php';require_once __DIR__.'/config_mrl.php';require_once __DIR__.'/class.user.php';
$uh=new USER();if(!$uh->is_logged_in()){$uh->redirect('/login.php');exit;}if(!isAdmin($_SESSION['userSession']??null)){http_response_code(403);exit('Admin access required.');}
define('MRL_PICK_REMINDER_CONTEXT','dashboard');require_once __DIR__.'/pick_reminder_helper.php';
if(!isset($dbconnect)||!($dbconnect instanceof mysqli)){http_response_code(500);exit('Database connection unavailable.');}
if(!isset($_SESSION['mrlpr_csrf']))$_SESSION['mrlpr_csrf']=bin2hex(random_bytes(24));
$cfg=mrlpr_load_config();$ctx=mrlpr_current_context();$msg='';$msgClass='info';
function prd_csrf():bool{return isset($_POST['csrf'])&&hash_equals((string)($_SESSION['mrlpr_csrf']??''),(string)$_POST['csrf']);}
function prd_post_cfg(array $c):array{
    $m=strtoupper(trim((string)($_POST['mode']??'MANUAL')));$c['mode']=in_array($m,['AUTO','MANUAL','OFF'],true)?$m:'MANUAL';
    $s=strtoupper(trim((string)($_POST['scope']??'TEST')));$c['scope']=in_array($s,['TEST','LIVE'],true)?$s:'TEST';
    $o=[];foreach(['offset1','offset2','offset3'] as $k){$n=mrlpr_duration_to_minutes((string)($_POST[$k]??''));if($n!==null)$o[]=$n;}$o=array_values(array_unique($o));rsort($o,SORT_NUMERIC);$c['offsets_minutes']=$o?:[180,120,60];
    $sub=trim((string)($_POST['subject_template']??''));$body=trim((string)($_POST['body_template']??''));if($sub!=='')$c['subject_template']=$sub;if($body!=='')$c['body_template']=$body;
    $c['test_auto_enabled']=isset($_POST['test_auto_enabled']) && (string)$_POST['test_auto_enabled']==='1';
    $testAt=trim((string)($_POST['test_auto_at']??''));
    $c['test_auto_at']=$testAt;
    return $c;
}
$action=(string)($_POST['action']??'');
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='save_settings'){
    if(!prd_csrf()){$msg='Save blocked: security token mismatch.';$msgClass='bad';}
    else{$n=prd_post_cfg($cfg);if($n['mode']==='AUTO'&&$n['scope']==='LIVE'&&trim((string)($_POST['live_auto_confirm']??''))!=='ENABLE LIVE AUTO'){$msg='LIVE AUTO was not enabled. Type ENABLE LIVE AUTO exactly.';$msgClass='bad';}
    elseif(!mrlpr_save_config($n)){$msg='Could not save settings.';$msgClass='bad';}else{$cfg=$n;$msg='Settings saved.';$msgClass='ok';}}
}
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='send_manual'){
    if(!prd_csrf()){$msg='Send blocked: security token mismatch.';$msgClass='bad';}
    else{$work=prd_post_cfg($cfg);$work['mode']=$cfg['mode'];$scope=(string)$work['scope'];$rec=mrlpr_missing_recipients($dbconnect,$scope,(string)$ctx['year'],(string)$ctx['segment']);$by=[];foreach($rec as $r)$by[(int)$r['userID']]=$r;$sel=array_values(array_unique(array_map('intval',(array)($_POST['recipient_ids']??[]))));
        if($scope==='LIVE'&&trim((string)($_POST['live_send_confirm']??''))!=='SEND LIVE'){$msg='LIVE send blocked. Type SEND LIVE exactly.';$msgClass='bad';}
        elseif(!$sel){$msg='No recipients selected.';$msgClass='bad';}
        else{$sent=0;$skip=0;$fail=0;foreach($sel as $uid){if(!isset($by[$uid])){$skip++;continue;}$x=mrlpr_send_user($dbconnect,$by[$uid],$work,$ctx,'MANUAL',null);$st=(string)($x['result']??'');if($st==='SENT')$sent++;elseif(strpos($st,'SKIPPED')===0)$skip++;else$fail++;}$msg="Manual reminder run complete — sent $sent, skipped $skip, failed $fail.";$msgClass=$fail?'bad':'ok';}
    }
}
$recipients=mrlpr_missing_recipients($dbconnect,(string)$cfg['scope'],(string)$ctx['year'],(string)$ctx['segment']);$deadline=$ctx['deadline_dt'];
$rc=['year'=>$ctx['year'],'segment'=>$ctx['segment'],'segment_name'=>$ctx['segment_name'],'deadline'=>$ctx['deadline_display'],'team_name'=>$recipients?(string)($recipients[0]['teamName']??'Team'):'Team','team_page_url'=>$cfg['team_page_url']];
$previewSub=mrlpr_render_template((string)$cfg['subject_template'],$rc,false);$previewBody=mrlpr_render_template((string)$cfg['body_template'],$rc,false);
$state=mrlpr_load_scheduler_state();$logs=array_reverse(mrlpr_read_log(30));
$pre=['Admin access'=>true,'PHPMailer available'=>is_file(__DIR__.'/mailer/class.phpmailer.php') && class_exists('USER') && method_exists('USER','send_mail'),'users email columns available'=>mrlpr_table_has_columns($dbconnect,'users',['userID','userName','userEmail','userEmail2','userActive']),'user_teams columns available'=>mrlpr_table_has_columns($dbconnect,'user_teams',['userID','raceYear','teamName']),'user_picks columns available'=>mrlpr_table_has_columns($dbconnect,'user_picks',['userID','raceYear','segment']),'Deadline parsed'=>$deadline instanceof DateTime,'State folder writable/creatable'=>mrlpr_ensure_state_dir()];$ready=!in_array(false,$pre,true);
$offs=array_values((array)$cfg['offsets_minutes']);while(count($offs)<3)$offs[]=0;
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MRL Pick Reminder Dashboard</title>
<style>
:root{--bg:#101214;--p:#1b1f23;--p2:#15191d;--b:#414850;--t:#eef2f6;--m:#aeb8c1;--g:#ffcf83;--green:#57e38c;--red:#ff7373;--blue:#8fc8ff}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--t);font-family:Arial,Helvetica,sans-serif;font-size:14px}.wrap{max-width:1180px;margin:20px auto 50px;padding:0 16px}.card{background:var(--p);border:1px solid var(--b);border-radius:12px;padding:18px;margin-bottom:14px}h1,h2,h3{color:var(--g);margin-top:0}h1{font-size:30px;margin-bottom:8px}h2{font-size:21px}.muted{color:var(--m)}.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px}.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.stat{background:var(--p2);border:1px solid #353c43;border-radius:9px;padding:12px}.big{font-size:24px;font-weight:800}.banner{padding:12px 14px;border-radius:9px;margin:12px 0;font-weight:700}.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}.info{background:#123044;border:1px solid #286a93;color:#dbf2ff}.warn{background:#4a3514;border:1px solid #9b6a12;color:#ffe8b4}label{display:block;margin:6px 0}input[type=text],input[type=datetime-local],textarea{width:100%;background:#0f1215;color:#fff;border:1px solid #59616a;border-radius:7px;padding:9px;font:inherit}textarea{min-height:132px}.rad{display:flex;gap:18px;flex-wrap:wrap}.rad label{display:flex;gap:6px;align-items:center;margin:0}.btn{border:0;border-radius:7px;padding:11px 16px;font-weight:800;cursor:pointer}.green{background:#23864b;color:#fff}.blue{background:#2f6feb;color:#fff}table{width:100%;border-collapse:collapse}th,td{padding:9px 8px;border-bottom:1px solid #343b42;text-align:left;vertical-align:top}th{color:#ffd27f;font-size:12px}.pass{color:var(--green);font-weight:800}.fail{color:var(--red);font-weight:800}code,.mono{font-family:Consolas,Menlo,monospace;color:#ffd27f}.preview{white-space:pre-wrap;background:#0f1215;border:1px solid #454d55;border-radius:8px;padding:14px;line-height:1.55}.small{font-size:12px;color:var(--m)}@media(max-width:800px){.g2,.g3{grid-template-columns:1fr}body{font-size:16px}}
</style></head><body><div class="wrap">
<div class="card"><h1>MRL Pick Reminder Dashboard</h1><div class="muted">VERSION v003 | Admin-only | Safe default: MANUAL + TEST (MRL ID 999 only)</div><?php if($msg!==''):?><div class="banner <?php echo $msgClass==='ok'?'ok':($msgClass==='bad'?'bad':'info');?>"><?php echo mrlpr_h($msg);?></div><?php endif;?></div>
<div class="card"><h2>Current Pick Window</h2><div class="g3"><div class="stat"><div class="muted">Year / Segment</div><div class="big"><?php echo mrlpr_h($ctx['year'].' '.$ctx['segment_name']);?></div></div><div class="stat"><div class="muted">Deadline</div><div class="big" style="font-size:18px"><?php echo mrlpr_h($ctx['deadline_display']);?></div></div><div class="stat"><div class="muted">Missing in current scope</div><div class="big"><?php echo count($recipients);?></div></div></div></div>
<div class="card"><h2>Preflight</h2><table><thead><tr><th>CHECK</th><th>STATUS</th></tr></thead><tbody><?php foreach($pre as $l=>$ok):?><tr><td><?php echo mrlpr_h($l);?></td><td class="<?php echo $ok?'pass':'fail';?>"><?php echo $ok?'PASS':'FAIL';?></td></tr><?php endforeach;?></tbody></table></div>
<form method="post"><input type="hidden" name="csrf" value="<?php echo mrlpr_h($_SESSION['mrlpr_csrf']);?>">
<div class="card"><h2>Mode + Scope</h2><div class="g2"><div class="stat"><h3>Mode</h3><div class="rad"><?php foreach(['AUTO','MANUAL','OFF'] as $m):?><label><input type="radio" name="mode" value="<?php echo $m;?>" <?php echo $cfg['mode']===$m?'checked':'';?>><?php echo $m;?></label><?php endforeach;?></div><p class="small">AUTO = scheduled sends. MANUAL = button only. OFF = no automatic sending.</p></div><div class="stat"><h3>Recipient Scope</h3><div class="rad"><label><input type="radio" name="scope" value="TEST" <?php echo $cfg['scope']==='TEST'?'checked':'';?>>TEST — MRL 999 only</label><label><input type="radio" name="scope" value="LIVE" <?php echo $cfg['scope']==='LIVE'?'checked':'';?>>LIVE — missing active teams</label></div><p class="small">LIVE excludes userID 0 and 999 and rechecks the DB immediately before every send.</p></div></div></div>
<div class="card"><h2>AUTO TEST — ID 999 Only</h2>
<p class="muted">Use this to prove the complete automatic path at a convenient time: Hostinger cron → cron launcher → reminder scheduler → missing-pick check → email → send history.</p>
<div class="g2">
    <div class="stat">
        <label><input type="checkbox" name="test_auto_enabled" value="1" <?php echo !empty($cfg['test_auto_enabled'])?'checked':''; ?>> <b>Use one-time TEST AUTO timestamp</b></label>
        <p class="small">Only applies when <b>AUTO + TEST</b> is selected. It never targets LIVE teams.</p>
    </div>
    <div class="stat">
        <label><b>Test send date/time (ET)</b></label>
        <input type="datetime-local" name="test_auto_at" value="<?php echo mrlpr_h((string)($cfg['test_auto_at']??'')); ?>">
        <p class="small">Set a time a few minutes ahead, Save Settings, and leave Mode=AUTO + Scope=TEST.</p>
    </div>
</div>
<?php
$testStatus='Not scheduled';
$testKey=trim((string)($cfg['test_auto_at']??''));
if(!empty($cfg['test_auto_enabled']) && $testKey!==''){
    $testStatus=mrlpr_test_auto_sent($testKey,MRL_PR_TEST_UID)
        ? 'SENT — automatic test completed'
        : 'ARMED — waiting for scheduler';
}
?>
<div class="banner <?php echo strpos($testStatus,'SENT')===0?'ok':'info'; ?>"><b>TEST AUTO status:</b> <?php echo mrlpr_h($testStatus); ?></div>
</div>

<div class="card"><h2>Automatic Reminder Times</h2><p class="muted">Enter H:MM before deadline.</p><div class="g3"><?php for($i=0;$i<3;$i++):?><div class="stat"><label><b>Reminder <?php echo $i+1;?></b></label><input type="text" name="offset<?php echo $i+1;?>" value="<?php echo $offs[$i]?mrlpr_h(mrlpr_minutes_to_duration((int)$offs[$i])):'';?>"><?php if($deadline instanceof DateTime&&$offs[$i]):$slot=clone $deadline;$slot->modify('-'.(int)$offs[$i].' minutes');?><div class="small" style="margin-top:7px">Would run at <?php echo mrlpr_h($slot->format('g:i A'));?> ET</div><?php endif;?></div><?php endfor;?></div><div class="banner warn">AUTO + LIVE requires typing <b>ENABLE LIVE AUTO</b>.</div><input type="text" name="live_auto_confirm" placeholder="ENABLE LIVE AUTO"></div>
<div class="card"><h2>Email Message</h2><div class="g2"><div><label><b>Subject</b></label><input type="text" name="subject_template" value="<?php echo mrlpr_h((string)$cfg['subject_template']);?>"><label style="margin-top:12px"><b>Message</b></label><textarea name="body_template"><?php echo mrlpr_h((string)$cfg['body_template']);?></textarea><div class="small">Placeholders: <code>{{year}}</code>, <code>{{segment_name}}</code>, <code>{{deadline}}</code>, <code>{{team_name}}</code>, <code>{{team_page}}</code>.</div></div><div><label><b>Current Preview</b></label><div class="preview"><b><?php echo mrlpr_h($previewSub);?></b>

<?php echo mrlpr_h($previewBody);?></div></div></div><div style="margin-top:14px"><button class="btn blue" type="submit" name="action" value="save_settings">Save Settings</button></div></div>
<div class="card"><h2>Current Missing Picks — <?php echo $cfg['scope'];?> Scope</h2><p class="small">Each team gets its own message. Visible To: is manliusracingleague@gmail.com; that team\'s email address(es) are BCC recipients.</p><?php if(!$recipients):?><div class="banner ok">No teams in the current scope are missing picks.</div><?php else:?><table><thead><tr><th>Send</th><th>Team</th><th>User</th><th>Email(s)</th></tr></thead><tbody><?php foreach($recipients as $r):?><tr><td><input type="checkbox" name="recipient_ids[]" value="<?php echo (int)$r['userID'];?>" checked></td><td><?php echo mrlpr_h((string)($r['teamName']??''));?></td><td><?php echo mrlpr_h((string)($r['userName']??'').' (ID '.(int)$r['userID'].')');?></td><td><?php echo mrlpr_h(implode(', ',(array)$r['emails']));?></td></tr><?php endforeach;?></tbody></table><?php if($cfg['scope']==='LIVE'):?><div class="banner warn">LIVE manual send requires typing <b>SEND LIVE</b>.</div><input type="text" name="live_send_confirm" placeholder="SEND LIVE"><?php endif;?><div style="margin-top:14px"><button class="btn green" type="submit" name="action" value="send_manual" <?php echo $ready?'':'disabled';?> onclick="return confirm('Send reminder now to checked recipient(s)?');">Send Selected Reminder Now</button></div><?php endif;?></div></form>
<div class="card"><h2>Automatic Scheduler Status</h2><?php if(!$state):?><div class="muted">No scheduler check recorded yet.</div><?php else:?><table><tr><th>Last check</th><td><?php echo mrlpr_h((string)($state['checked_at']??''));?></td></tr><tr><th>Status</th><td><?php echo mrlpr_h((string)($state['status']??''));?></td></tr><tr><th>Mode / Scope</th><td><?php echo mrlpr_h((string)($state['mode']??'').' / '.(string)($state['scope']??''));?></td></tr><tr><th>Summary</th><td><?php echo mrlpr_h(json_encode($state['summary']??[],JSON_UNESCAPED_SLASHES));?></td></tr></table><?php endif;?></div>
<div class="card"><h2>Recent Send History</h2><?php if(!$logs):?><div class="muted">No reminder sends recorded yet.</div><?php else:?><table><thead><tr><th>Time</th><th>Kind</th><th>Scope</th><th>Team</th><th>Result</th><th>Offset</th></tr></thead><tbody><?php foreach($logs as $r):?><tr><td><?php echo mrlpr_h((string)($r['sent_at']??''));?></td><td><?php echo mrlpr_h((string)($r['send_kind']??''));?></td><td><?php echo mrlpr_h((string)($r['scope']??''));?></td><td><?php echo mrlpr_h((string)($r['teamName']??'').' (ID '.(int)($r['userID']??0).')');?></td><td><?php echo mrlpr_h((string)($r['result']??''));?></td><td><?php echo isset($r['offset_minutes'])&&$r['offset_minutes']!==null?mrlpr_h(mrlpr_minutes_to_duration((int)$r['offset_minutes'])):'Manual';?></td></tr><?php endforeach;?></tbody></table><?php endif;?></div>
<div class="small" style="text-align:right">pick_reminder_dashboard.php | VERSION v003</div></div></body></html>
