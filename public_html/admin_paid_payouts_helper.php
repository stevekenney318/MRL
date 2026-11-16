<?php
declare(strict_types=1);
/**
 * admin_paid_payouts_helper.php
 * VERSION: v002
 * LAST MODIFIED: 11/15/2026 8:18:22 pm ET
 * PURPOSE: Data/helper layer for root-level admin_paid_payouts.php.
 * PHP: 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

function app_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function app_money($v): string { return '$'.number_format((float)$v, 2); }
function app_money_value($v): float {
    $s=str_replace(['$',','],'',trim((string)$v));
    return is_numeric($s) ? round((float)$s,2) : 0.0;
}
function app_date_or_null($v) {
    $s=trim((string)$v);
    if($s==='') return null;
    $d=DateTime::createFromFormat('Y-m-d',$s);
    return ($d && $d->format('Y-m-d')===$s) ? $s : null;
}
function app_years(PDO $dbo, array $payoutYears, string $raceYear): array {
    $years=[];
    foreach($payoutYears as $y){ if(preg_match('/^\d{4}$/',(string)$y)) $years[(string)$y]=true; }
    try{
        $q=$dbo->query("SELECT year FROM years WHERE year > 0 ORDER BY year DESC");
        foreach($q as $r){$y=(string)($r['year']??'');if(preg_match('/^\d{4}$/',$y))$years[$y]=true;}
    }catch(Throwable $e){}
    if(preg_match('/^\d{4}$/',$raceYear))$years[$raceYear]=true;
    if(empty($years))$years[date('Y')]=true;
    $out=array_keys($years);rsort($out,SORT_STRING);return $out;
}
function app_current_fee(PDO $dbo, string $year): array {
    try{
        $s=$dbo->prepare("SELECT * FROM mrl_financial_fee_history WHERE raceYear=:y ORDER BY effective_date DESC,id DESC LIMIT 1");
        $s->execute([':y'=>(int)$year]);$r=$s->fetch(PDO::FETCH_ASSOC);
        if(is_array($r))return $r;
    }catch(Throwable $e){}
    return ['annual_fee'=>0.00,'effective_date'=>'','reason'=>'','changed_by'=>0];
}
function app_fee_history(PDO $dbo,string $year):array{
    try{$s=$dbo->prepare("SELECT * FROM mrl_financial_fee_history WHERE raceYear=:y ORDER BY effective_date DESC,id DESC");$s->execute([':y'=>(int)$year]);$r=$s->fetchAll(PDO::FETCH_ASSOC);return is_array($r)?$r:[];}catch(Throwable $e){return [];}
}
function app_add_fee(PDO $dbo,string $year,float $fee,string $date,string $reason,int $uid):bool{
    $s=$dbo->prepare("INSERT INTO mrl_financial_fee_history (raceYear,annual_fee,effective_date,reason,changed_by,created_at) VALUES (:y,:f,:d,:r,:u,NOW())");
    return $s->execute([':y'=>(int)$year,':f'=>$fee,':d'=>$date,':r'=>$reason,':u'=>$uid]);
}
function app_team_rows(PDO $dbo,string $year):array{
    $rows=[];
    if(function_exists('mrlp_year_roster')){
        try{$r=mrlp_year_roster($year,$dbo);foreach($r as $team=>$x){$rows[(int)($x['userID']??0)]=['userID'=>(int)($x['userID']??0),'teamName'=>(string)($x['teamName']??$team),'userName'=>(string)($x['userName']??'')];}}catch(Throwable $e){}
    }
    try{
        $s=$dbo->prepare("SELECT userID,teamName,COALESCE(userName,'') userName FROM Financial WHERE raceYear=:y AND userID NOT IN (0,998,999)");$s->execute([':y'=>(int)$year]);
        foreach($s->fetchAll(PDO::FETCH_ASSOC) as $x){$uid=(int)($x['userID']??0);if($uid>0&&!isset($rows[$uid]))$rows[$uid]=['userID'=>$uid,'teamName'=>(string)($x['teamName']??''),'userName'=>(string)($x['userName']??'')];}
    }catch(Throwable $e){}
    uasort($rows,function($a,$b){return strnatcasecmp((string)$a['teamName'],(string)$b['teamName']);});
    return array_values($rows);
}
function app_load_team_finance(PDO $dbo,string $year,int $uid):array{
    try{$s=$dbo->prepare("SELECT * FROM mrl_financial_team_year WHERE raceYear=:y AND userID=:u LIMIT 1");$s->execute([':y'=>(int)$year,':u'=>$uid]);$r=$s->fetch(PDO::FETCH_ASSOC);if(is_array($r)){$r['_source']='new';return $r;}}catch(Throwable $e){}
    try{$s=$dbo->prepare("SELECT * FROM Financial WHERE raceYear=:y AND userID=:u LIMIT 1");$s->execute([':y'=>(int)$year,':u'=>$uid]);$r=$s->fetch(PDO::FETCH_ASSOC);if(is_array($r))return [
        'raceYear'=>(int)$year,'userID'=>$uid,'teamName'=>(string)($r['teamName']??''),'userName'=>(string)($r['userName']??''),
        'amount_paid'=>(float)($r['paidAmount']??0),'paid_method'=>(string)($r['paidHow']??''),'paid_date'=>null,
        'manual_adjustment'=>0,'adjustment_reason'=>'','notes'=>(string)($r['paidComment']??''),
        'payout_paid'=>0,'payout_method'=>'','payout_date'=>null,'payout_source'=>'','recipient_url'=>'','payoff_notes'=>'','_source'=>'legacy'
    ];}catch(Throwable $e){}
    return ['raceYear'=>(int)$year,'userID'=>$uid,'amount_paid'=>0,'paid_method'=>'','paid_date'=>null,'manual_adjustment'=>0,'adjustment_reason'=>'','notes'=>'','payout_paid'=>0,'payout_method'=>'','payout_date'=>null,'payout_source'=>'','recipient_url'=>'','payoff_notes'=>'','_source'=>'blank'];
}
function app_save_team_finance(PDO $dbo,array $d,int $adminUid):bool{
    $sql="INSERT INTO mrl_financial_team_year
      (raceYear,userID,teamName,userName,amount_paid,paid_method,paid_date,manual_adjustment,adjustment_reason,notes,payout_paid,payout_method,payout_date,payout_source,recipient_url,payoff_notes,updated_by,created_at,updated_at)
      VALUES (:y,:u,:t,:n,:ap,:pm,:pd,:ma,:ar,:no,:po,:pom,:pod,:ps,:ru,:pn,:ub,NOW(),NOW())
      ON DUPLICATE KEY UPDATE teamName=VALUES(teamName),userName=VALUES(userName),amount_paid=VALUES(amount_paid),paid_method=VALUES(paid_method),paid_date=VALUES(paid_date),manual_adjustment=VALUES(manual_adjustment),adjustment_reason=VALUES(adjustment_reason),notes=VALUES(notes),payout_paid=VALUES(payout_paid),payout_method=VALUES(payout_method),payout_date=VALUES(payout_date),payout_source=VALUES(payout_source),recipient_url=VALUES(recipient_url),payoff_notes=VALUES(payoff_notes),updated_by=VALUES(updated_by),updated_at=NOW()";
    $s=$dbo->prepare($sql);
    return $s->execute([':y'=>(int)$d['raceYear'],':u'=>(int)$d['userID'],':t'=>(string)$d['teamName'],':n'=>(string)$d['userName'],':ap'=>(float)$d['amount_paid'],':pm'=>(string)$d['paid_method'],':pd'=>$d['paid_date'],':ma'=>(float)$d['manual_adjustment'],':ar'=>(string)$d['adjustment_reason'],':no'=>(string)$d['notes'],':po'=>(float)$d['payout_paid'],':pom'=>(string)$d['payout_method'],':pod'=>$d['payout_date'],':ps'=>(string)$d['payout_source'],':ru'=>(string)$d['recipient_url'],':pn'=>(string)$d['payoff_notes'],':ub'=>$adminUid]);
}
function app_prizes_by_team(array $model):array{
    $out=[];
    foreach(['S1','S2','S3','S4'] as $seg){foreach(($model['sections'][$seg]??[]) as $r){if(!empty($r['future']))continue;$team=trim((string)($r['winner']??''));if($team==='')continue;if(!isset($out[$team]))$out[$team]=0.0;$out[$team]+=round((float)($r['amount']??0),2);}}
    foreach(($model['yearly']??[]) as $r){if(!empty($r['future']))continue;$team=trim((string)($r['winner']??''));if($team==='')continue;if(!isset($out[$team]))$out[$team]=0.0;$out[$team]+=round((float)($r['amount']??0),2);}
    return $out;
}
function app_calc(float $fee,float $paid,float $prize,float $adjust,float $paidOut):array{
    $due=max(0.0,round($fee-$paid,2));$remaining=round($prize-$due+$adjust-$paidOut,2);
    $status=$fee<=0?'No Fee':($paid+0.005>=$fee?'Paid':($paid>0?'Partial':'Unpaid'));
    return ['fee_due'=>$due,'remaining'=>$remaining,'status'=>$status];
}
