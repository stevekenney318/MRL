<?php
declare(strict_types=1);
/**
 * pick_reminder_helper.php
 * VERSION: v002
 * LAST MODIFIED: 9/6/2026 3:02:08 am
 *
 * CHANGELOG:
 * v002 (9/6/2026 3:02:08 am)
 * - FIX: Strip trailing ET/EST/EDT display suffix before parsing the deadline
 *   with America/New_York, preventing PHP double-timezone parse failure.
 *
 * v001 (9/6/2026 2:42:47 am)
 */
if (!defined('MRL_PICK_REMINDER_CONTEXT')) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(403);
        echo '<!doctype html><html><head><meta charset="utf-8"><title>MRL Pick Reminder Helper</title></head><body style="font-family:Arial;background:#111;color:#eee;padding:24px"><h2>MRL Pick Reminder Helper</h2><p>This file is part of <b>pick_reminder_dashboard.php</b> and is not intended to be run directly.</p><p>Please use the Pick Reminder Dashboard instead.</p></body></html>';
    }
    exit;
}
date_default_timezone_set('America/New_York');
const MRL_PR_TEST_UID = 999;
const MRL_PR_STATE_DIR = __DIR__ . '/_pick_reminder';
const MRL_PR_CONFIG_FILE = MRL_PR_STATE_DIR . '/config.json';
const MRL_PR_LOG_FILE = MRL_PR_STATE_DIR . '/send_log.jsonl';
const MRL_PR_SCHEDULER_STATE_FILE = MRL_PR_STATE_DIR . '/scheduler_state.json';
const MRL_PR_LOCK_FILE = MRL_PR_STATE_DIR . '/scheduler.lock';

function mrlpr_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function mrlpr_ensure_state_dir(): bool {
    if (!is_dir(MRL_PR_STATE_DIR) && !@mkdir(MRL_PR_STATE_DIR, 0755, true) && !is_dir(MRL_PR_STATE_DIR)) return false;
    return is_writable(MRL_PR_STATE_DIR);
}
function mrlpr_default_config(): array {
    return [
        'version'=>1,'mode'=>'MANUAL','scope'=>'TEST','offsets_minutes'=>[180,120,60],
        'subject_template'=>'MRL Pick Reminder - {{year}} {{segment_name}}',
        'body_template'=>"Just a reminder that you have not submitted your picks for {{year}} {{segment_name}}.\nThey are due soon — {{deadline}}.\n{{team_page}}\nThanks, Steve",
        'from_name'=>'Manlius Racing League','from_email'=>'noreply@manliusracingleague.com',
        'reply_to'=>'manliusracingleague@gmail.com','team_page_url'=>'https://manliusracingleague.com/team.php',
        'auto_window_minutes'=>10,'updated_at'=>null
    ];
}
function mrlpr_load_config(): array {
    $d=mrlpr_default_config();
    if (!is_file(MRL_PR_CONFIG_FILE)) return $d;
    $x=json_decode((string)@file_get_contents(MRL_PR_CONFIG_FILE),true);
    if (!is_array($x)) return $d;
    $c=array_merge($d,$x);
    $c['mode']=in_array(strtoupper((string)$c['mode']),['AUTO','MANUAL','OFF'],true)?strtoupper((string)$c['mode']):'MANUAL';
    $c['scope']=in_array(strtoupper((string)$c['scope']),['TEST','LIVE'],true)?strtoupper((string)$c['scope']):'TEST';
    $o=[]; foreach((array)$c['offsets_minutes'] as $m){$m=(int)$m;if($m>0&&$m<=1440)$o[]=$m;}
    $o=array_values(array_unique($o)); rsort($o,SORT_NUMERIC); $c['offsets_minutes']=$o?:[180,120,60];
    return $c;
}
function mrlpr_save_config(array $c): bool {
    if(!mrlpr_ensure_state_dir()) return false; $c['updated_at']=date('c');
    $j=json_encode($c,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); if(!is_string($j)) return false;
    $t=MRL_PR_CONFIG_FILE.'.tmp'; if(@file_put_contents($t,$j.PHP_EOL,LOCK_EX)===false)return false;
    if(!@rename($t,MRL_PR_CONFIG_FILE)){@unlink($t);return false;} return true;
}
function mrlpr_duration_to_minutes(string $v): ?int {
    $v=trim($v); if($v==='')return null;
    if(preg_match('/^(\d{1,2}):([0-5]\d)$/',$v,$m)){ $n=((int)$m[1]*60)+(int)$m[2]; return ($n>0&&$n<=1440)?$n:null; }
    if(ctype_digit($v)){ $n=(int)$v; return ($n>0&&$n<=1440)?$n:null; } return null;
}
function mrlpr_minutes_to_duration(int $m): string { return sprintf('%d:%02d',intdiv($m,60),$m%60); }
function mrlpr_deadline_datetime(string $r): ?DateTime {
    $r = trim($r);
    if ($r === '') return null;

    // config_mrl.php may expose a display value ending in ET/EST/EDT.
    // Because we also pass America/New_York explicitly, strip that display
    // suffix first to avoid PHP's "double timezone specification" parse error.
    $r = preg_replace('/\\s+(?:ET|EST|EDT)$/i', '', $r);

    try {
        return new DateTime($r, new DateTimeZone('America/New_York'));
    } catch (Throwable $e) {
        return null;
    }
}
function mrlpr_deadline_display(DateTime $d): string { return $d->format('l, F j \a\t g:i A').' ET'; }
function mrlpr_current_context(): array {
    global $raceYear,$pickSegment,$segment,$pickSegmentName,$segmentName,$pickDeadlineAt,$formLockDate;
    $y=(string)($raceYear??date('Y')); $s=strtoupper((string)($pickSegment??$segment??'')); $n=(string)($pickSegmentName??$segmentName??$s);
    $r=(string)($pickDeadlineAt??$formLockDate??''); $d=mrlpr_deadline_datetime($r);
    return ['year'=>$y,'segment'=>$s,'segment_name'=>$n,'deadline_raw'=>$r,'deadline_dt'=>$d,'deadline_display'=>$d?mrlpr_deadline_display($d):$r];
}
function mrlpr_render_template(string $t,array $c,bool $html=false): string {
    $u=(string)($c['team_page_url']??'https://manliusracingleague.com/team.php');
    return strtr($t,[
        '{{year}}'=>(string)($c['year']??''),'{{segment}}'=>(string)($c['segment']??''),'{{segment_name}}'=>(string)($c['segment_name']??''),
        '{{deadline}}'=>(string)($c['deadline']??''),'{{team_name}}'=>(string)($c['team_name']??''),
        '{{team_page}}'=>$html?'<a href="'.mrlpr_h($u).'">Team Page</a>':'Team Page: '.$u
    ]);
}
function mrlpr_table_has_columns(mysqli $db,string $table,array $req): bool {
    if(!preg_match('/^[A-Za-z0-9_]+$/',$table))return false; $r=@mysqli_query($db,"SHOW COLUMNS FROM `$table`"); if(!$r)return false;
    $c=[]; while($x=mysqli_fetch_assoc($r))$c[]=(string)($x['Field']??''); mysqli_free_result($r);
    foreach($req as $q)if(!in_array($q,$c,true))return false; return true;
}
function mrlpr_missing_recipients(mysqli $db,string $scope,string $year,string $segment): array {
    if(strtoupper($scope)==='TEST'){
        $sql="SELECT u.userID,u.userName,u.userEmail,u.userEmail2,COALESCE(t.teamName,'MRL Test Team') teamName
              FROM users u LEFT JOIN user_teams t ON t.userID=u.userID AND t.raceYear=?
              WHERE u.userID=? AND NOT EXISTS(SELECT 1 FROM user_picks p WHERE p.userID=u.userID AND p.raceYear=? AND p.segment=?) LIMIT 1";
        $st=mysqli_prepare($db,$sql); if(!$st)return []; $uid=MRL_PR_TEST_UID; mysqli_stmt_bind_param($st,'siss',$year,$uid,$year,$segment);
    } else {
        $sql="SELECT DISTINCT u.userID,u.userName,u.userEmail,u.userEmail2,t.teamName
              FROM users u INNER JOIN user_teams t ON t.userID=u.userID AND t.raceYear=?
              WHERE u.userActive='Y' AND u.userID NOT IN(0,999)
              AND NOT EXISTS(SELECT 1 FROM user_picks p WHERE p.userID=u.userID AND p.raceYear=? AND p.segment=?)
              ORDER BY t.teamName,u.userName";
        $st=mysqli_prepare($db,$sql); if(!$st)return []; mysqli_stmt_bind_param($st,'sss',$year,$year,$segment);
    }
    if(!mysqli_stmt_execute($st)){mysqli_stmt_close($st);return [];} $res=mysqli_stmt_get_result($st); $rows=[];
    if($res){while($r=mysqli_fetch_assoc($res)){ $e=[]; foreach(['userEmail','userEmail2'] as $k){$v=trim((string)($r[$k]??''));if($v!==''&&filter_var($v,FILTER_VALIDATE_EMAIL))$e[strtolower($v)]=$v;} $r['emails']=array_values($e);$rows[]=$r;}}
    mysqli_stmt_close($st); return $rows;
}
function mrlpr_recheck_missing(mysqli $db,int $uid,string $year,string $segment): bool {
    $st=mysqli_prepare($db,"SELECT COUNT(*) c FROM user_picks WHERE userID=? AND raceYear=? AND segment=?"); if(!$st)return false;
    mysqli_stmt_bind_param($st,'iss',$uid,$year,$segment); if(!mysqli_stmt_execute($st)){mysqli_stmt_close($st);return false;}
    $r=mysqli_stmt_get_result($st); $x=$r?mysqli_fetch_assoc($r):null; mysqli_stmt_close($st); return (int)($x['c']??0)===0;
}
function mrlpr_append_log(array $r): bool {
    if(!mrlpr_ensure_state_dir())return false; $j=json_encode($r,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); return is_string($j)&&@file_put_contents(MRL_PR_LOG_FILE,$j.PHP_EOL,FILE_APPEND|LOCK_EX)!==false;
}
function mrlpr_read_log(int $limit=1000): array {
    if(!is_file(MRL_PR_LOG_FILE))return []; $l=@file(MRL_PR_LOG_FILE,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES); if(!is_array($l))return [];
    $o=[];foreach($l as $x){$r=json_decode($x,true);if(is_array($r))$o[]=$r;} return ($limit>0&&count($o)>$limit)?array_slice($o,-$limit):$o;
}
function mrlpr_auto_key_exists(string $y,string $s,int $off,int $uid): bool {
    foreach(mrlpr_read_log(5000) as $r)if(($r['send_kind']??'')==='AUTO'&&(string)($r['year']??'')===$y&&(string)($r['segment']??'')===$s&&(int)($r['offset_minutes']??-1)===$off&&(int)($r['userID']??-1)===$uid&&($r['result']??'')==='SENT')return true;
    return false;
}
function mrlpr_send_user(mysqli $db,array $r,array $cfg,array $ctx,string $kind,?int $off=null): array {
    $uid=(int)($r['userID']??0);$team=trim((string)($r['teamName']??$r['userName']??('User '.$uid)));$emails=(array)($r['emails']??[]);
    $base=['sent_at'=>date('c'),'send_kind'=>$kind,'scope'=>(string)$cfg['scope'],'year'=>(string)$ctx['year'],'segment'=>(string)$ctx['segment'],'offset_minutes'=>$off,'userID'=>$uid,'teamName'=>$team,'emails'=>$emails];
    if(!mrlpr_recheck_missing($db,$uid,(string)$ctx['year'],(string)$ctx['segment'])){ $base['result']='SKIPPED_ALREADY_SUBMITTED';mrlpr_append_log($base);return $base; }
    if(!$emails){$base['result']='FAILED_NO_EMAIL';mrlpr_append_log($base);return $base;}
    $tc=['year'=>$ctx['year'],'segment'=>$ctx['segment'],'segment_name'=>$ctx['segment_name'],'deadline'=>$ctx['deadline_display'],'team_name'=>$team,'team_page_url'=>$cfg['team_page_url']];
    $subject=mrlpr_render_template((string)$cfg['subject_template'],$tc,false);
    $raw=mrlpr_render_template((string)$cfg['body_template'],$tc,true);$link='<a href="'.mrlpr_h((string)$cfg['team_page_url']).'">Team Page</a>';
    $raw=str_replace($link,'__LINK__',$raw);$html=nl2br(mrlpr_h($raw));$html=str_replace('__LINK__',$link,$html);
    $headers="MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: ".(string)$cfg['from_name']." <".(string)$cfg['from_email'].">\r\nReply-To: ".(string)$cfg['reply_to'];
    $ok=[];$bad=[];foreach($emails as $e){$sent=function_exists('mail')?@mail((string)$e,$subject,$html,$headers):false;if($sent)$ok[]=$e;else$bad[]=$e;}
    $base['successes']=$ok;$base['failures']=$bad;$base['result']=$ok&&!$bad?'SENT':($ok?'PARTIAL':'FAILED');$base['subject']=$subject;$base['body_text']=mrlpr_render_template((string)$cfg['body_template'],$tc,false);mrlpr_append_log($base);return $base;
}
function mrlpr_scheduler_state(array $s): bool { if(!mrlpr_ensure_state_dir())return false;$j=json_encode($s,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);return is_string($j)&&@file_put_contents(MRL_PR_SCHEDULER_STATE_FILE,$j.PHP_EOL,LOCK_EX)!==false; }
function mrlpr_load_scheduler_state(): array { if(!is_file(MRL_PR_SCHEDULER_STATE_FILE))return []; $x=json_decode((string)@file_get_contents(MRL_PR_SCHEDULER_STATE_FILE),true); return is_array($x)?$x:[]; }
