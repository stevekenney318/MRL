<?php
declare(strict_types=1);

/**
 * payouts.php
 *
 * VERSION: v003
 * LAST MODIFIED: 10/29/2026 3:03:19 am ET
 *
 * PURPOSE:
 * - Public MRL payout page built from the same race-result snapshots and scoring rules as Weekly Standings.
 * - Shows the complete planned payout chart all season, with winners filling in as results become available.
 * - Supports per-year public/hidden and official/unofficial state controlled by admin_payouts.php.
 * - Includes owner payout tally and Excel tally export.
 *
 * CHANGELOG:
 * v003 (10/29/2026 3:03:19 am ET)
 * - PRESENTATION FIX: Tie dividers now remove both adjoining solid cell borders before drawing the long-dash/short-dash separator, so the patterned divider is the only seam visible between tied rows.
 * - PRESERVE: v002 colors, widths, column proportions, payout calculations, tie money allocation, tally/export, year visibility, admin settings, scoring, and data sources unchanged.
 *
 * v002 (10/29/2026 2:54:04 am ET)
 * - PRESENTATION: Segment/The Chase payout-total rows now use the same green treatment as Yearly Point Total rows.
 * - PRESENTATION: Weekly High Point rows retain the Weekly Standings S1/S2/S3/S4 colors.
 * - PRESENTATION: Desktop report width and column proportions are tightened to more closely match the payout spreadsheet.
 * - PRESENTATION: Consecutive tie rows now use a visible long-dash/short-dash separator between tied entries.
 * - PRESERVE: Payout calculations, tie money allocation, tally/export, year visibility, admin settings, scoring, and data sources unchanged.
 */

ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('America/New_York');

require_once $_SERVER['DOCUMENT_ROOT'].'/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/mrl_team/mrl_theme_helper.php';
require_once __DIR__.'/payouts_helper.php';

$theme='dark';
try { $uid=(int)($_SESSION['userSession']??0); if(isset($dbo)&&$dbo instanceof PDO&&$uid>0)$theme=mrl_theme_get($dbo,$uid); } catch(Throwable $e){$theme='dark';}

$config=mrlp_load_config();$allYears=mrlp_available_years(__DIR__);$isAdminPreview=false;
if(!empty($_GET['preview']) && function_exists('isAdmin')){ $isAdminPreview=isAdmin((int)($_SESSION['userSession']??0)); }

$publicYears=[];
foreach($allYears as $y){$yc=mrlp_year_config($config,$y);if(!empty($yc['public']))$publicYears[]=$y;}
$requested=preg_replace('/[^0-9]/','',(string)($_GET['year']??''));
if($requested==='')$requested=!empty($publicYears)?$publicYears[0]:(!empty($allYears)?$allYears[0]:date('Y'));
if(!in_array($requested,$allYears,true))$requested=!empty($publicYears)?$publicYears[0]:(!empty($allYears)?$allYears[0]:date('Y'));
$yearCfg=mrlp_year_config($config,$requested);
$allowed=!empty($yearCfg['public'])||$isAdminPreview;
if(!$allowed){http_response_code(404);echo '<!doctype html><html><body style="font:18px Arial;padding:30px">Payouts for '.mrlp_h($requested).' are not public yet.</body></html>';exit;}

$score=mrlp_compute_year($requested,$dbo??null,$dbconnect??null);$model=mrlp_build_payout_model($score,$yearCfg);
if(($_GET['export']??'')==='tally')mrlp_send_tally_xlsx($requested,$model['tally'],(float)$model['awarded_total']);
$selectYears=$publicYears;if($isAdminPreview&&!in_array($requested,$selectYears,true))$selectYears[]=$requested;rsort($selectYears,SORT_STRING);
function pp_money($v):string{return '$'.number_format((float)$v,2);}
?>
<!doctype html>
<html class="mrl-theme-<?php echo mrlp_h($theme); ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Payouts</title>
<link rel="stylesheet" href="/mrl_team/mrl_shared_theme.css?v=001">
<style>
@view-transition{navigation:auto}*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;font-size:16px;color:var(--mrl-rd-text,#111)}
.page-wrap{width:96%;max-width:900px;margin:14px auto 30px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px}
.controls{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.controls select,.btn{height:32px;border:1px solid #7a7a7a;border-radius:9px;background:#f6f6f6;color:#111;padding:0 10px;font:600 14px Arial;text-decoration:none;display:inline-flex;align-items:center;cursor:pointer}
.title-row{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.title-row h1{margin:3px 0 8px;font-size:28px;color:var(--mrl-rd-text,#fff)}
.badge{display:inline-block;border:2px solid #c00000;color:#c00000;background:#fff;border-radius:999px;padding:3px 12px;font-size:13px;font-weight:700;white-space:nowrap}.badge.official{border-color:#2e8b57;color:#116b36}
.report{background:#fff;color:#111;border:1px solid #555;border-radius:4px;overflow:hidden}.year-label{font-size:20px;font-weight:700;padding:8px 10px 4px;background:#fff}
table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:16px}col.prize{width:37%}col.winner{width:25%}col.owner{width:27%}col.amount{width:11%}
th,td{border:1px solid #333;padding:4px 7px;line-height:1.16;vertical-align:middle}th{background:#ffff00;font-size:17px;font-weight:700;text-align:center}.amount{text-align:right;white-space:nowrap}
.seg-S1 td{background:#c5d9f1}.seg-S2 td{background:#c4bd97}.seg-S3 td{background:#fcd5b4}.seg-S4 td{background:#c4d79b}.segment-prize td{background:#92d050!important;font-weight:500;border-top:2px solid #333}.yearly td{background:#92d050;font-weight:500}.future .winner,.future .owner{color:#777}
.tie-lead td{border-bottom:0!important}.tie-follow td{border-top:0!important;background-image:repeating-linear-gradient(to right,#555 0 14px,transparent 14px 19px,#555 19px 23px,transparent 23px 29px)!important;background-size:29px 1px!important;background-repeat:repeat-x!important;background-position:left top!important}.section-head th{border-top:3px solid #333}.planned-total td{background:#1f4e78;color:#fff;font-weight:700;font-size:17px}.planned-total td:first-child{text-align:right}.tally-wrap{margin-top:16px;display:grid;grid-template-columns:minmax(0,560px) auto;gap:12px;align-items:start}.tally-title{margin:0 0 6px;font-size:20px}.tally table{font-size:15px}.tally th{background:#1f4e78;color:#fff}.tally td{background:#d9e8f6}.tally-total td{background:#1f4e78;color:#fff;font-weight:700}.note{font-size:13px;color:var(--mrl-rd-muted,#666);margin-top:8px}
@media(max-width:760px){body{font-size:15px}.page-wrap{width:99%}table{font-size:14px}th{font-size:14px}th,td{padding:4px}.tally-wrap{grid-template-columns:1fr}.title-row h1{font-size:24px}}
@media print{html,body{background:#fff!important;background-image:none!important;color:#111!important}.controls,.tally-export{display:none!important}.page-wrap{width:100%;max-width:none;margin:0}.report{border:0}.badge{-webkit-print-color-adjust:exact;print-color-adjust:exact}.seg-S1 td,.seg-S2 td,.seg-S3 td,.seg-S4 td,.segment-prize td,.yearly td,th,.planned-total td{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style></head>
<body><div class="page-wrap">
<div class="topbar"><div class="controls"><form method="get"><select name="year" onchange="this.form.submit()"><?php foreach($selectYears as $y): ?><option value="<?php echo mrlp_h($y); ?>" <?php echo $y===$requested?'selected':''; ?>><?php echo mrlp_h($y); ?></option><?php endforeach; ?></select><?php if($isAdminPreview): ?><input type="hidden" name="preview" value="1"><?php endif; ?></form><a class="btn" href="weekly_standings.php?year=<?php echo mrlp_h($requested); ?>">Weekly Standings</a></div><a class="btn tally-export" href="?year=<?php echo mrlp_h($requested); ?><?php echo $isAdminPreview?'&preview=1':''; ?>&export=tally">Tally Spreadsheet</a></div>
<div class="title-row"><h1><?php echo mrlp_h($requested); ?> MRL Payouts</h1><span class="badge <?php echo !empty($yearCfg['official'])?'official':''; ?>"><?php echo !empty($yearCfg['official'])?'OFFICIAL':'AUTO-SCORING – UNOFFICIAL'; ?></span></div>
<div class="report"><div class="year-label"><?php echo mrlp_h($requested); ?></div><table><colgroup><col class="prize"><col class="winner"><col class="owner"><col class="amount"></colgroup>
<?php foreach(['S1','S2','S3','S4'] as $seg): ?><thead class="section-head"><tr><th>Prize</th><th>Winner</th><th>Team Owner</th><th>Amount</th></tr></thead><tbody>
<?php $lastPrize=''; $sectionRows=$model['sections'][$seg]; foreach($sectionRows as $rowIndex=>$row): $tieFollow=!empty($row['tie'])&&$lastPrize===(string)$row['prize']; $nextRow=$sectionRows[$rowIndex+1]??null; $tieLead=!empty($row['tie'])&&is_array($nextRow)&&!empty($nextRow['tie'])&&(string)($nextRow['prize']??'')===(string)$row['prize']; $cls='seg-'.$seg.' '.(($row['kind']??'')==='segment'?'segment-prize ':'').(!empty($row['future'])?'future ':'').($tieLead?'tie-lead ':'').($tieFollow?'tie-follow ':''); ?>
<tr class="<?php echo mrlp_h(trim($cls)); ?>"><td><?php echo mrlp_h($row['prize']); ?></td><td class="winner"><?php echo mrlp_h($row['winner']); ?></td><td class="owner"><?php echo mrlp_h($row['owner']); ?></td><td class="amount"><?php echo pp_money($row['amount']); ?></td></tr>
<?php $lastPrize=(string)$row['prize']; endforeach; ?></tbody><?php endforeach; ?>
<thead class="section-head"><tr><th>Prize</th><th>Winner</th><th>Team Owner</th><th>Amount</th></tr></thead><tbody><?php $lastPrize=''; $yearlyRows=$model['yearly']; foreach($yearlyRows as $rowIndex=>$row): $tf=!empty($row['tie'])&&$lastPrize===(string)$row['prize']; $nextRow=$yearlyRows[$rowIndex+1]??null; $tl=!empty($row['tie'])&&is_array($nextRow)&&!empty($nextRow['tie'])&&(string)($nextRow['prize']??'')===(string)$row['prize']; ?><tr class="yearly <?php echo !empty($row['future'])?'future ':''; ?><?php echo $tl?'tie-lead ':''; ?><?php echo $tf?'tie-follow':''; ?>"><td><?php echo mrlp_h($row['prize']); ?></td><td><?php echo mrlp_h($row['winner']); ?></td><td><?php echo mrlp_h($row['owner']); ?></td><td class="amount"><?php echo pp_money($row['amount']); ?></td></tr><?php $lastPrize=(string)$row['prize']; endforeach; ?>
<tr class="planned-total"><td colspan="3">Planned Season Payout Total</td><td class="amount"><?php echo pp_money($model['planned_total']); ?></td></tr></tbody></table></div>
<div class="tally-wrap"><div class="tally"><h2 class="tally-title">Payout Tally — Awarded to Date</h2><table><thead><tr><th>Team Owner</th><th>Payout</th></tr></thead><tbody><?php foreach($model['tally'] as $tr): ?><tr><td><?php echo mrlp_h($tr['owner']); ?></td><td class="amount"><?php echo pp_money($tr['payout']); ?></td></tr><?php endforeach; ?><tr class="tally-total"><td>Grand Total</td><td class="amount"><?php echo pp_money($model['awarded_total']); ?></td></tr></tbody></table><div class="note">Future payout rows remain visible in the main chart, but the tally includes only awards whose standings are currently known.</div></div></div>
</div></body></html>
