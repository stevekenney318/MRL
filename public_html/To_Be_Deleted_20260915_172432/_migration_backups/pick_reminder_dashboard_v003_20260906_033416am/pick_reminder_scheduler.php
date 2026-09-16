<?php
declare(strict_types=1);
/** pick_reminder_scheduler.php | VERSION: v001 | LAST MODIFIED: 9/6/2026 2:42:47 am */
date_default_timezone_set('America/New_York');
define('MRL_PICK_REMINDER_CONTEXT','scheduler');
require_once __DIR__.'/config.php';
require_once __DIR__.'/config_mrl.php';
require_once __DIR__.'/pick_reminder_helper.php';
if(!isset($dbconnect)||!($dbconnect instanceof mysqli)){fwrite(STDERR,"DB unavailable\n");exit(2);}
$cfg=mrlpr_load_config();$ctx=mrlpr_current_context();
$state=['version'=>'v001','checked_at'=>date('c'),'mode'=>$cfg['mode'],'scope'=>$cfg['scope'],'year'=>$ctx['year'],'segment'=>$ctx['segment'],'segment_name'=>$ctx['segment_name'],'deadline'=>$ctx['deadline_display'],'actions'=>[]];
if($cfg['mode']!=='AUTO'){$state['status']='IDLE_NOT_AUTO';mrlpr_scheduler_state($state);echo "Pick reminder: {$cfg['mode']}\n";exit(0);}
if(!($ctx['deadline_dt'] instanceof DateTime)){$state['status']='ERROR_DEADLINE';mrlpr_scheduler_state($state);exit(3);}
if(!function_exists('mail')){$state['status']='ERROR_MAIL_UNAVAILABLE';mrlpr_scheduler_state($state);exit(4);}
if(!mrlpr_ensure_state_dir())exit(5);
$lock=@fopen(MRL_PR_LOCK_FILE,'c+');if(!$lock||!@flock($lock,LOCK_EX|LOCK_NB)){if($lock)fclose($lock);exit(0);}
$now=new DateTime('now',new DateTimeZone('America/New_York'));$deadline=clone $ctx['deadline_dt'];$window=max(1,min(30,(int)($cfg['auto_window_minutes']??10)));
$sent=0;$skipped=0;$failed=0;
foreach((array)$cfg['offsets_minutes'] as $off){$off=(int)$off;$at=clone $deadline;$at->modify('-'.$off.' minutes');$end=clone $at;$end->modify('+'.$window.' minutes');
    $slot=['offset_minutes'=>$off,'scheduled_at'=>$at->format('c'),'window_end'=>$end->format('c'),'status'=>'NOT_DUE','results'=>[]];
    if($now<$at||$now>$end){$state['actions'][]=$slot;continue;}
    $rec=mrlpr_missing_recipients($dbconnect,(string)$cfg['scope'],(string)$ctx['year'],(string)$ctx['segment']);$slot['status']='DUE';$slot['candidate_count']=count($rec);
    foreach($rec as $r){$uid=(int)$r['userID'];if(mrlpr_auto_key_exists((string)$ctx['year'],(string)$ctx['segment'],$off,$uid)){$slot['results'][]=['userID'=>$uid,'teamName'=>(string)($r['teamName']??''),'result'=>'SKIPPED_DUPLICATE'];$skipped++;continue;}
        $x=mrlpr_send_user($dbconnect,$r,$cfg,$ctx,'AUTO',$off);$slot['results'][]=$x;$st=(string)($x['result']??'');if($st==='SENT')$sent++;elseif(strpos($st,'SKIPPED')===0)$skipped++;else$failed++;
    }$state['actions'][]=$slot;
}
$state['status']='CHECK_COMPLETE';$state['summary']=['sent'=>$sent,'skipped'=>$skipped,'failed'=>$failed];mrlpr_scheduler_state($state);@flock($lock,LOCK_UN);fclose($lock);
echo "Pick reminder complete: sent=$sent skipped=$skipped failed=$failed\n";exit($failed>0?1:0);
