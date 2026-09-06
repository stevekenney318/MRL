<?php
declare(strict_types=1);
/** 
 * pick_reminder_scheduler.php
 * VERSION: v002
 * LAST MODIFIED: 9/6/2026 3:34:16 am
 *
 * CHANGELOG:
 * v002 (9/6/2026 3:34:16 am)
 * - NEW: One-time TEST AUTO timestamp for ID 999.
 * - CHANGE: AUTO_TEST uses the same real cron -> scheduler -> missing-pick -> mail path as production.
 * - PRESERVE: Normal deadline-offset AUTO logic and duplicate protection.
 *
 * v001 (9/6/2026 2:42:47 am)
 */
date_default_timezone_set('America/New_York');
define('MRL_PICK_REMINDER_CONTEXT','scheduler');
require_once __DIR__.'/config.php';
require_once __DIR__.'/config_mrl.php';
require_once __DIR__.'/class.user.php';
require_once __DIR__.'/pick_reminder_helper.php';
if(!isset($dbconnect)||!($dbconnect instanceof mysqli)){fwrite(STDERR,"DB unavailable\n");exit(2);}
$cfg=mrlpr_load_config();$ctx=mrlpr_current_context();
$state=['version'=>'v001','checked_at'=>date('c'),'mode'=>$cfg['mode'],'scope'=>$cfg['scope'],'year'=>$ctx['year'],'segment'=>$ctx['segment'],'segment_name'=>$ctx['segment_name'],'deadline'=>$ctx['deadline_display'],'actions'=>[]];
if($cfg['mode']!=='AUTO'){$state['status']='IDLE_NOT_AUTO';mrlpr_scheduler_state($state);echo "Pick reminder: {$cfg['mode']}\n";exit(0);}
if(!($ctx['deadline_dt'] instanceof DateTime)){$state['status']='ERROR_DEADLINE';mrlpr_scheduler_state($state);exit(3);}
if(!class_exists('USER') || !method_exists('USER','send_mail')){$state['status']='ERROR_MAILER_UNAVAILABLE';mrlpr_scheduler_state($state);exit(4);}
if(!mrlpr_ensure_state_dir())exit(5);
$lock=@fopen(MRL_PR_LOCK_FILE,'c+');if(!$lock||!@flock($lock,LOCK_EX|LOCK_NB)){if($lock)fclose($lock);exit(0);}
$now=new DateTime('now',new DateTimeZone('America/New_York'));
$deadline=clone $ctx['deadline_dt'];
$window=max(1,min(30,(int)($cfg['auto_window_minutes']??10)));
$sent=0;$skipped=0;$failed=0;

// One-time AUTO TEST mode: only when scope is TEST and a specific timestamp is enabled.
// This deliberately bypasses the normal deadline offsets so the full automatic path
// can be proven at a convenient time without touching any live team.
if(
    (string)$cfg['scope']==='TEST'
    && !empty($cfg['test_auto_enabled'])
    && trim((string)($cfg['test_auto_at']??''))!==''
){
    $testRaw=trim((string)$cfg['test_auto_at']);
    try{
        $testAt=new DateTime($testRaw,new DateTimeZone('America/New_York'));
    }catch(Throwable $e){
        $testAt=null;
    }

    if(!($testAt instanceof DateTime)){
        $state['actions'][]=['type'=>'AUTO_TEST','status'=>'INVALID_TEST_TIME','test_auto_at'=>$testRaw];
        $failed++;
    }else{
        $testEnd=clone $testAt;
        $testEnd->modify('+'.$window.' minutes');

        $slot=[
            'type'=>'AUTO_TEST',
            'test_auto_at'=>$testAt->format('Y-m-d\TH:i'),
            'scheduled_at'=>$testAt->format('c'),
            'window_end'=>$testEnd->format('c'),
            'status'=>'NOT_DUE',
            'results'=>[]
        ];

        $testKey=$testAt->format('Y-m-d\TH:i');

        if(mrlpr_test_auto_sent($testKey,MRL_PR_TEST_UID)){
            $slot['status']='ALREADY_SENT';
            $skipped++;
        }elseif($now>=$testAt && $now<=$testEnd){
            $slot['status']='DUE';
            $rec=mrlpr_missing_recipients($dbconnect,'TEST',(string)$ctx['year'],(string)$ctx['segment']);
            $slot['candidate_count']=count($rec);

            foreach($rec as $r){
                $x=mrlpr_send_user(
                    $dbconnect,
                    $r,
                    $cfg,
                    $ctx,
                    'AUTO_TEST',
                    null,
                    ['test_auto_at'=>$testKey]
                );
                $slot['results'][]=$x;
                $st=(string)($x['result']??'');
                if($st==='SENT')$sent++;
                elseif(strpos($st,'SKIPPED')===0)$skipped++;
                else$failed++;
            }
        }elseif($now>$testEnd){
            $slot['status']='MISSED_WINDOW';
        }

        $state['actions'][]=$slot;
    }
}else{
    foreach((array)$cfg['offsets_minutes'] as $off){
        $off=(int)$off;
        $at=clone $deadline;
        $at->modify('-'.$off.' minutes');
        $end=clone $at;
        $end->modify('+'.$window.' minutes');

        $slot=[
            'type'=>'NORMAL_OFFSET',
            'offset_minutes'=>$off,
            'scheduled_at'=>$at->format('c'),
            'window_end'=>$end->format('c'),
            'status'=>'NOT_DUE',
            'results'=>[]
        ];

        if($now<$at||$now>$end){
            $state['actions'][]=$slot;
            continue;
        }

        $rec=mrlpr_missing_recipients(
            $dbconnect,
            (string)$cfg['scope'],
            (string)$ctx['year'],
            (string)$ctx['segment']
        );
        $slot['status']='DUE';
        $slot['candidate_count']=count($rec);

        foreach($rec as $r){
            $uid=(int)$r['userID'];
            if(mrlpr_auto_key_exists((string)$ctx['year'],(string)$ctx['segment'],$off,$uid)){
                $slot['results'][]=[
                    'userID'=>$uid,
                    'teamName'=>(string)($r['teamName']??''),
                    'result'=>'SKIPPED_DUPLICATE'
                ];
                $skipped++;
                continue;
            }

            $x=mrlpr_send_user($dbconnect,$r,$cfg,$ctx,'AUTO',$off);
            $slot['results'][]=$x;
            $st=(string)($x['result']??'');
            if($st==='SENT')$sent++;
            elseif(strpos($st,'SKIPPED')===0)$skipped++;
            else$failed++;
        }

        $state['actions'][]=$slot;
    }
}
$state['status']='CHECK_COMPLETE';$state['summary']=['sent'=>$sent,'skipped'=>$skipped,'failed'=>$failed];mrlpr_scheduler_state($state);@flock($lock,LOCK_UN);fclose($lock);
echo "Pick reminder complete: sent=$sent skipped=$skipped failed=$failed\n";exit($failed>0?1:0);
