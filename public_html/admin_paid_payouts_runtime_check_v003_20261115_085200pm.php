<?php
/**
 * admin_paid_payouts_runtime_check_v003.php
 *
 * VERSION: v003
 * LAST MODIFIED: 11/15/2026 8:52 pm ET
 *
 * PURPOSE:
 * - Read-only narrow diagnostic for the final pre-render block in admin_paid_payouts.php v002.
 * - Tests only the remaining unverified operations after v002 runtime check passed.
 * - Does not modify files or database data.
 *
 * PHP: 7.3 compatible.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/New_York');

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$results = [];

function rc3_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function rc3_add(array &$rows, string $step, bool $ok, string $detail=''): void {
    $rows[]=['step'=>$step,'ok'=>$ok,'detail'=>$detail];
}
function rc3_try(array &$rows, string $step, callable $fn): bool {
    try {
        $v=$fn();
        if (is_array($v) || is_object($v)) {
            $v=json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($v===null) {
            $v='';
        }
        rc3_add($rows,$step,true,(string)$v);
        return true;
    } catch (Throwable $e) {
        rc3_add($rows,$step,false,get_class($e).': '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
        return false;
    }
}

require_once $root.'/config.php';
require_once $root.'/config_mrl.php';
require_once $root.'/class.user.php';
require_once $root.'/mrl_team/mrl_theme_helper.php';
require_once $root.'/race_results/payouts_helper.php';
require_once $root.'/admin_paid_payouts_helper.php';

$user_home=new USER();
$uid=(int)($_SESSION['userSession']??0);

if (!$user_home->is_logged_in()) {
    rc3_add($results,'Logged in',false,'Not logged in');
    goto render;
}
rc3_add($results,'Logged in',true,'uid='.$uid);

if (!isAdmin($uid)) {
    rc3_add($results,'Admin',false,'Not authorized');
    goto render;
}
rc3_add($results,'Admin',true,'authorized');

$payoutYears=mrlp_available_years($root.'/race_results');
$years=app_years($dbo,$payoutYears,(string)($raceYear??''));
$year=(string)($years[0]??date('Y'));

$currentFeeRow=app_current_fee($dbo,$year);
$currentFee=(float)($currentFeeRow['annual_fee']??0);
$score=mrlp_compute_year($year,$dbo,$dbconnect);
$yearCfg=mrlp_year_config(mrlp_load_config(),$year);
$payoutModel=mrlp_build_payout_model($score,$yearCfg);
$prizes=app_prizes_by_team($payoutModel);
$teams=app_team_rows($dbo,$year);

rc3_add($results,'Baseline rebuilt',true,'year='.$year.' | teams='.count($teams).' | prize teams='.count($prizes));

$rows=[];
$summary=['expected'=>0.0,'received'=>0.0,'prizes'=>0.0,'due_league'=>0.0,'adjustments'=>0.0,'paid_out'=>0.0,'remaining'=>0.0];

if (!rc3_try($results,'Build combined team rows + app_calc()',function() use (&$rows,&$summary,$teams,$dbo,$year,$prizes,$currentFee) {
    foreach($teams as $t) {
        $f=app_load_team_finance($dbo,$year,(int)$t['userID']);
        $prize=(float)($prizes[$t['teamName']]??0);

        $requiredKeys=['amount_paid','manual_adjustment','payout_paid'];
        foreach($requiredKeys as $k) {
            if (!array_key_exists($k,$f)) {
                throw new RuntimeException('Missing finance key '.$k.' for '.(string)$t['teamName']);
            }
        }

        $c=app_calc(
            $currentFee,
            (float)$f['amount_paid'],
            $prize,
            (float)$f['manual_adjustment'],
            (float)$f['payout_paid']
        );

        foreach(['fee_due','remaining','status'] as $k) {
            if (!array_key_exists($k,$c)) {
                throw new RuntimeException('Missing calc key '.$k.' for '.(string)$t['teamName']);
            }
        }

        $rows[]=['team'=>$t,'f'=>$f,'prize'=>$prize,'calc'=>$c];
        $summary['expected'] += $currentFee;
        $summary['received'] += (float)$f['amount_paid'];
        $summary['prizes'] += $prize;
        $summary['due_league'] += (float)$c['fee_due'];
        $summary['adjustments'] += (float)$f['manual_adjustment'];
        $summary['paid_out'] += (float)$f['payout_paid'];
        if ((float)$c['remaining'] > 0) {
            $summary['remaining'] += (float)$c['remaining'];
        }
    }
    return 'rows='.count($rows).' | remaining='.$summary['remaining'];
})) {
    goto render;
}

$owed=$rows;

if (!rc3_try($results,'Sort How Much Do I Owe You list',function() use (&$owed) {
    usort($owed,function($a,$b){
        $x=(float)$b['calc']['remaining'] <=> (float)$a['calc']['remaining'];
        return $x!==0 ? $x : strnatcasecmp((string)$a['team']['teamName'],(string)$b['team']['teamName']);
    });
    return 'sorted rows='.count($owed);
})) {
    goto render;
}

if (!rc3_try($results,'Render-data field check',function() use ($rows) {
    $fields=['teamName','userName'];
    $finance=['amount_paid','paid_method','paid_date','manual_adjustment','adjustment_reason','notes',
              'payout_paid','payout_method','payout_date','payout_source','recipient_url','payoff_notes'];
    foreach($rows as $r) {
        foreach($fields as $k) {
            if (!array_key_exists($k,$r['team'])) {
                throw new RuntimeException('Missing team field '.$k);
            }
        }
        foreach($finance as $k) {
            if (!array_key_exists($k,$r['f'])) {
                throw new RuntimeException('Missing finance field '.$k.' for '.$r['team']['teamName']);
            }
        }
    }
    return 'all render fields present';
})) {
    goto render;
}

rc3_add($results,'Final pre-render block',true,'Everything before HTML rendering is valid.');

render:
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Paid/Payouts Runtime Check v003</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#11161c;color:#e9edf2;font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1200px;margin:24px auto}
.card{background:#1b232c;border:1px solid #394655;border-radius:12px;padding:18px}
h1{margin:0 0 8px;color:#fff2dc}
table{width:100%;border-collapse:collapse;margin-top:16px}
th,td{padding:9px 10px;border-bottom:1px solid #34414e;text-align:left;vertical-align:top}
.pass{color:#72e3a3;font-weight:bold}
.fail{color:#ff939d;font-weight:bold}
code{white-space:pre-wrap;word-break:break-word}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>MRL Paid/Payouts Runtime Check v003</h1>
<div>Read-only narrow check of the final pre-render block.</div>
<table>
<tr><th>Status</th><th>Step</th><th>Detail</th></tr>
<?php foreach($results as $r): ?>
<tr>
<td class="<?php echo $r['ok']?'pass':'fail'; ?>"><?php echo $r['ok']?'PASS':'FAIL'; ?></td>
<td><?php echo rc3_h($r['step']); ?></td>
<td><code><?php echo rc3_h($r['detail']); ?></code></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</div>
</body>
</html>
