<?php
declare(strict_types=1);

/**
 * admin_payouts.php
 * VERSION: v001
 * LAST MODIFIED: 10/29/2026 1:48:37 am ET
 * PURPOSE: Admin-only yearly payout amounts, public visibility, and official/unofficial state.
 */

if(session_status()===PHP_SESSION_NONE)session_start();
date_default_timezone_set('America/New_York');
$_SESSION['return_to']=$_SERVER['REQUEST_URI']??'/race_results/admin_payouts.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class.user.php';
require_once __DIR__.'/payouts_helper.php';
$user_home=new USER();if(!$user_home->is_logged_in()){$user_home->redirect('/login.php');exit;}
$uid=(int)($_SESSION['userSession']??0);if(!isAdmin($uid)){http_response_code(403);exit('Not authorized.');}
if(!isset($_SESSION['mrlp_admin_csrf']))$_SESSION['mrlp_admin_csrf']=bin2hex(random_bytes(24));
function ap_money_input($v):float{$s=str_replace(['$',','],'',trim((string)$v));return is_numeric($s)?max(0,round((float)$s,2)):0.0;}
$years=mrlp_available_years(__DIR__);$year=preg_replace('/[^0-9]/','',(string)($_POST['year']??$_GET['year']??($years[0]??date('Y'))));if(!in_array($year,$years,true)&&!empty($years))$year=$years[0];
$config=mrlp_load_config();$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='save'){
    if(!hash_equals((string)$_SESSION['mrlp_admin_csrf'],(string)($_POST['csrf']??''))){$err='Security token mismatch.';}else{
        $yc=mrlp_default_year_config($year);$yc['public']=isset($_POST['public']);$yc['official']=isset($_POST['official']);$yc['weekly_high_point']=ap_money_input($_POST['weekly_high_point']??10);
        foreach(['S1','S2','S3'] as $seg)$yc['segments'][$seg]=[ap_money_input($_POST[$seg.'_1']??40),ap_money_input($_POST[$seg.'_2']??20)];
        $yc['segments']['S4']=[ap_money_input($_POST['S4_1']??40),ap_money_input($_POST['S4_2']??10),ap_money_input($_POST['S4_3']??10)];
        $yc['yearly']=[ap_money_input($_POST['Y_1']??60),ap_money_input($_POST['Y_2']??50),ap_money_input($_POST['Y_3']??40)];
        $config['years'][$year]=$yc;$config['updated_by']=$uid;
        if(mrlp_save_config($config)){$msg='Payout settings saved for '.$year.'.';$config=mrlp_load_config();}else{$err='Could not save payout configuration.';}
    }
}
$yc=mrlp_year_config($config,$year);
function apv($v):string{return number_format((float)$v,2,'.','');}
?>
<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MRL Payouts Admin</title>
<style>*{box-sizing:border-box}body{margin:0;background:#151515;color:#f2f2f2;font-family:Tahoma,Verdana,Segoe,sans-serif;font-size:15px}.wrap{width:96%;max-width:1050px;margin:14px auto 30px}.banner,.card{border:1px solid #4d473f;border-radius:14px;background:linear-gradient(180deg,#222,#1b1b1b);padding:14px 16px;margin-bottom:10px}.banner h1{margin:0;color:#fff4df;font-size:25px}.sub{color:#aaa;font-size:13px;margin-top:4px}.card h2{margin:0 0 10px;color:#ffd08a;font-size:18px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.field{display:grid;grid-template-columns:minmax(190px,1fr) 130px;gap:10px;align-items:center;padding:5px 0;border-bottom:1px solid #333}.field label{color:#ddd}.control{width:100%;font:14px Tahoma;background:#f4f4f4;color:#111;border:1px solid #777;border-radius:7px;padding:6px 8px}.checks{display:flex;gap:22px;flex-wrap:wrap;margin:8px 0}.btn{font:14px Tahoma;border:1px solid #7a6641;border-radius:9px;padding:7px 12px;cursor:pointer;text-decoration:none;display:inline-block}.save{background:#173526;border-color:#286c48;color:#62e89b}.blue{background:#163451;border-color:#2f6feb;color:#9ecaff}.notice{padding:9px 12px;border-radius:8px;margin-bottom:10px}.ok{background:#173526;border:1px solid #286c48;color:#62e89b}.err{background:#3b1c1c;border:1px solid #7f3434;color:#ff8a8a}.toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.small{font-size:12px;color:#999}@media(max-width:760px){.grid{grid-template-columns:1fr}.field{grid-template-columns:1fr 120px}}</style></head>
<body><div class="wrap"><div class="banner"><h1>MRL Payouts Admin</h1><div class="sub">Year-specific payout amounts, visibility, and official status. Winners are calculated from race results; they are not entered here.</div></div>
<?php if($msg!==''):?><div class="notice ok"><?php echo mrlp_h($msg);?></div><?php endif;?><?php if($err!==''):?><div class="notice err"><?php echo mrlp_h($err);?></div><?php endif;?>
<div class="card"><div class="toolbar"><form method="get"><select class="control" name="year" onchange="this.form.submit()"><?php foreach($years as $y):?><option value="<?php echo mrlp_h($y);?>" <?php echo $y===$year?'selected':'';?>><?php echo mrlp_h($y);?></option><?php endforeach;?></select></form><a class="btn blue" target="_blank" href="payouts.php?year=<?php echo mrlp_h($year);?>&preview=1">Preview Payouts</a></div></div>
<form method="post"><input type="hidden" name="csrf" value="<?php echo mrlp_h((string)$_SESSION['mrlp_admin_csrf']);?>"><input type="hidden" name="action" value="save"><input type="hidden" name="year" value="<?php echo mrlp_h($year);?>">
<div class="card"><h2><?php echo mrlp_h($year);?> Status</h2><div class="checks"><label><input type="checkbox" name="public" <?php echo !empty($yc['public'])?'checked':'';?>> Show year on public Payouts page</label><label><input type="checkbox" name="official" <?php echo !empty($yc['official'])?'checked':'';?>> Mark payouts OFFICIAL</label></div></div>
<div class="grid"><div class="card"><h2>Weekly High Point</h2><div class="field"><label>High Point Total 1–36</label><input class="control" name="weekly_high_point" value="<?php echo apv($yc['weekly_high_point']);?>"></div></div>
<div class="card"><h2>Segment 1</h2><div class="field"><label>1st Place</label><input class="control" name="S1_1" value="<?php echo apv($yc['segments']['S1'][0]);?>"></div><div class="field"><label>2nd Place</label><input class="control" name="S1_2" value="<?php echo apv($yc['segments']['S1'][1]);?>"></div></div>
<div class="card"><h2>Segment 2</h2><div class="field"><label>1st Place</label><input class="control" name="S2_1" value="<?php echo apv($yc['segments']['S2'][0]);?>"></div><div class="field"><label>2nd Place</label><input class="control" name="S2_2" value="<?php echo apv($yc['segments']['S2'][1]);?>"></div></div>
<div class="card"><h2>Segment 3</h2><div class="field"><label>1st Place</label><input class="control" name="S3_1" value="<?php echo apv($yc['segments']['S3'][0]);?>"></div><div class="field"><label>2nd Place</label><input class="control" name="S3_2" value="<?php echo apv($yc['segments']['S3'][1]);?>"></div></div>
<div class="card"><h2>The Chase / Playoffs (Segment 4)</h2><div class="field"><label>1st Place</label><input class="control" name="S4_1" value="<?php echo apv($yc['segments']['S4'][0]);?>"></div><div class="field"><label>2nd Place</label><input class="control" name="S4_2" value="<?php echo apv($yc['segments']['S4'][1]);?>"></div><div class="field"><label>3rd Place</label><input class="control" name="S4_3" value="<?php echo apv($yc['segments']['S4'][2]);?>"></div></div>
<div class="card"><h2>Yearly Point Total</h2><div class="field"><label>1st Place</label><input class="control" name="Y_1" value="<?php echo apv($yc['yearly'][0]);?>"></div><div class="field"><label>2nd Place</label><input class="control" name="Y_2" value="<?php echo apv($yc['yearly'][1]);?>"></div><div class="field"><label>3rd Place</label><input class="control" name="Y_3" value="<?php echo apv($yc['yearly'][2]);?>"></div></div></div>
<div class="card"><button class="btn save" type="submit">Save <?php echo mrlp_h($year);?> Payout Settings</button><p class="small">Historical years default to hidden until you verify them. 2026 defaults to public and unofficial.</p></div></form></div></body></html>
