<?php
/**
 * admin_paid_payouts_runtime_check_v002.php
 *
 * VERSION: v002
 * LAST MODIFIED: 11/15/2026 8:43 pm ET
 *
 * PURPOSE:
 * - Corrected read-only runtime diagnostic for admin_paid_payouts.php.
 * - Loads config files at TOP LEVEL so $dbo and $dbconnect remain in the same scope
 *   as the real production page.
 * - Executes the remaining startup work one step at a time and reports the exact failure.
 * - Does not INSERT, UPDATE, DELETE, ALTER, CREATE, rename, move, or remove anything.
 *
 * PHP: 7.3 compatible.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/New_York');

$results = [];
$root = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\') : '';

function rtc2_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rtc2_add(array &$results, string $step, bool $ok, string $detail = ''): void
{
    $results[] = [
        'step' => $step,
        'ok' => $ok,
        'detail' => $detail,
    ];
}

function rtc2_detail(Throwable $e): string
{
    return get_class($e) . ': ' . $e->getMessage()
        . ' | ' . $e->getFile() . ':' . $e->getLine();
}

function rtc2_try(array &$results, string $step, callable $fn): bool
{
    try {
        $detail = $fn();
        if (is_array($detail) || is_object($detail)) {
            $detail = json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($detail === null) {
            $detail = '';
        } elseif (is_bool($detail)) {
            $detail = $detail ? 'true' : 'false';
        }
        rtc2_add($results, $step, true, (string)$detail);
        return true;
    } catch (Throwable $e) {
        rtc2_add($results, $step, false, rtc2_detail($e));
        return false;
    }
}

rtc2_add($results, 'PHP runtime', true, 'PHP ' . PHP_VERSION . ' | SAPI ' . PHP_SAPI);
rtc2_add($results, 'Document root', $root !== '', $root);

$required = [
    'config.php' => $root . '/config.php',
    'config_mrl.php' => $root . '/config_mrl.php',
    'class.user.php' => $root . '/class.user.php',
    'mrl_theme_helper.php' => $root . '/mrl_team/mrl_theme_helper.php',
    'payouts_helper.php' => $root . '/race_results/payouts_helper.php',
    'admin_paid_payouts_helper.php' => $root . '/admin_paid_payouts_helper.php',
];

foreach ($required as $name => $path) {
    rtc2_add(
        $results,
        'File exists: ' . $name,
        is_file($path) && is_readable($path),
        $path
    );
}

/*
 * IMPORTANT:
 * These requires are intentionally TOP LEVEL, matching admin_paid_payouts.php.
 * That keeps $dbo and $dbconnect in this script's main scope.
 */
try {
    require_once $root . '/config.php';
    rtc2_add($results, 'require config.php', true, 'loaded');
} catch (Throwable $e) {
    rtc2_add($results, 'require config.php', false, rtc2_detail($e));
    goto render;
}

rtc2_add(
    $results,
    '$dbconnect mysqli available',
    isset($dbconnect) && $dbconnect instanceof mysqli,
    isset($dbconnect) ? get_class($dbconnect) : 'not set'
);

rtc2_add(
    $results,
    '$dbo PDO available',
    isset($dbo) && $dbo instanceof PDO,
    isset($dbo) ? get_class($dbo) : 'not set'
);

try {
    require_once $root . '/config_mrl.php';
    rtc2_add($results, 'require config_mrl.php', true, 'loaded');
} catch (Throwable $e) {
    rtc2_add($results, 'require config_mrl.php', false, rtc2_detail($e));
    goto render;
}

try {
    require_once $root . '/class.user.php';
    rtc2_add($results, 'require class.user.php', true, 'loaded');
} catch (Throwable $e) {
    rtc2_add($results, 'require class.user.php', false, rtc2_detail($e));
    goto render;
}

try {
    require_once $root . '/mrl_team/mrl_theme_helper.php';
    rtc2_add($results, 'require mrl_theme_helper.php', true, 'loaded');
} catch (Throwable $e) {
    rtc2_add($results, 'require mrl_theme_helper.php', false, rtc2_detail($e));
    goto render;
}

try {
    require_once $root . '/race_results/payouts_helper.php';
    rtc2_add($results, 'require race_results/payouts_helper.php', true, 'loaded');
} catch (Throwable $e) {
    rtc2_add($results, 'require race_results/payouts_helper.php', false, rtc2_detail($e));
    goto render;
}

try {
    require_once $root . '/admin_paid_payouts_helper.php';
    rtc2_add($results, 'require admin_paid_payouts_helper.php', true, 'loaded');
} catch (Throwable $e) {
    rtc2_add($results, 'require admin_paid_payouts_helper.php', false, rtc2_detail($e));
    goto render;
}

if (!rtc2_try($results, 'Create USER object', function () {
    if (!class_exists('USER')) {
        throw new RuntimeException('USER class missing.');
    }
    $u = new USER();
    return 'created';
})) {
    goto render;
}

if (!rtc2_try($results, 'Check logged-in state', function () {
    $u = new USER();
    return $u->is_logged_in() ? 'logged in' : 'not logged in';
})) {
    goto render;
}

if (!rtc2_try($results, 'Check admin state', function () {
    $uid = (int)($_SESSION['userSession'] ?? 0);
    return 'uid=' . $uid . ' | admin=' . (isAdmin($uid) ? 'yes' : 'no');
})) {
    goto render;
}

$payoutYears = [];
if (!rtc2_try($results, 'Read payout years', function () use ($root, &$payoutYears) {
    $payoutYears = mrlp_available_years($root . '/race_results');
    return 'count=' . count($payoutYears) . ' | ' . implode(', ', $payoutYears);
})) {
    goto render;
}

$years = [];
if (!rtc2_try($results, 'Build admin year list', function () use (&$years, &$payoutYears, &$dbo, &$raceYear) {
    $years = app_years($dbo, $payoutYears, (string)($raceYear ?? ''));
    return 'count=' . count($years) . ' | ' . implode(', ', $years);
})) {
    goto render;
}

$year = !empty($years) ? (string)$years[0] : date('Y');

if (!rtc2_try($results, 'Read current annual fee', function () use ($year, &$dbo) {
    $r = app_current_fee($dbo, $year);
    return $r;
})) {
    goto render;
}

if (!rtc2_try($results, 'Read fee history', function () use ($year, &$dbo) {
    $r = app_fee_history($dbo, $year);
    return 'rows=' . count($r);
})) {
    goto render;
}

$score = [];
if (!rtc2_try($results, 'Compute payout scoring model', function () use ($year, &$score, &$dbo, &$dbconnect) {
    $score = mrlp_compute_year($year, $dbo, $dbconnect);
    return 'races=' . count($score['races'] ?? []);
})) {
    goto render;
}

$yearCfg = [];
if (!rtc2_try($results, 'Load payout configuration', function () use ($year, &$yearCfg) {
    $yearCfg = mrlp_year_config(mrlp_load_config(), $year);
    return 'loaded';
})) {
    goto render;
}

$payoutModel = [];
if (!rtc2_try($results, 'Build payout model', function () use (&$score, &$yearCfg, &$payoutModel) {
    $payoutModel = mrlp_build_payout_model($score, $yearCfg);
    return 'awarded_total=' . (string)($payoutModel['awarded_total'] ?? '');
})) {
    goto render;
}

$prizes = [];
if (!rtc2_try($results, 'Build prize totals by team', function () use (&$payoutModel, &$prizes) {
    $prizes = app_prizes_by_team($payoutModel);
    return 'teams=' . count($prizes);
})) {
    goto render;
}

$teams = [];
if (!rtc2_try($results, 'Read team rows', function () use ($year, &$teams, &$dbo) {
    $teams = app_team_rows($dbo, $year);
    return 'teams=' . count($teams);
})) {
    goto render;
}

if (!rtc2_try($results, 'Read team financial records', function () use ($year, &$teams, &$dbo) {
    $count = 0;
    foreach ($teams as $t) {
        app_load_team_finance($dbo, $year, (int)($t['userID'] ?? 0));
        $count++;
    }
    return 'records=' . $count;
})) {
    goto render;
}

rtc2_add($results, 'Production startup path', true, 'All tested startup steps completed.');

render:
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Paid/Payouts Runtime Check v002</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#11161c;color:#e9edf2;font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1250px;margin:24px auto}
.card{background:#1b232c;border:1px solid #394655;border-radius:12px;padding:18px;margin-bottom:12px}
h1{margin:0 0 8px;color:#fff2dc}
.sub{color:#aeb9c5}
table{width:100%;border-collapse:collapse}
th,td{padding:9px 10px;border-bottom:1px solid #34414e;text-align:left;vertical-align:top}
th{color:#b9c9d9}
.pass{color:#72e3a3;font-weight:bold}
.fail{color:#ff939d;font-weight:bold}
code{white-space:pre-wrap;word-break:break-word;color:#d7e7f7}
.note{margin-top:10px;background:#152334;border:1px solid #315274;border-radius:8px;padding:10px 12px;color:#b9d9f5}
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>MRL Paid/Payouts Runtime Check v002</h1>
        <div class="sub">Corrected scope handling. Read-only; no files or database data are changed.</div>
        <div class="note">Send me a screenshot showing the first FAIL row. If everything passes, send the bottom of the page.</div>
    </div>
    <div class="card">
        <table>
            <tr><th>Status</th><th>Step</th><th>Detail</th></tr>
            <?php foreach ($results as $r): ?>
            <tr>
                <td class="<?php echo $r['ok'] ? 'pass' : 'fail'; ?>">
                    <?php echo $r['ok'] ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo rtc2_h($r['step']); ?></td>
                <td><code><?php echo rtc2_h($r['detail']); ?></code></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
