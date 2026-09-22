<?php
declare(strict_types=1);

session_start();
date_default_timezone_set('America/New_York');

require_once __DIR__ . '/class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    header('Location: /login.php');
    exit;
}

require __DIR__ . '/config.php';
require __DIR__ . '/config_mrl.php';
require_once __DIR__ . '/team_name.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function row(string $label, string $value, string $status = ''): void {
    $class = $status === 'PASS' ? 'pass' : ($status === 'FAIL' ? 'fail' : '');
    echo '<tr><td>' . h($label) . '</td><td class="' . h($class) . '">' . h($status) . '</td><td><code>' . h($value) . '</code></td></tr>';
}

$uid = (int)($_SESSION['userSession'] ?? 0);
$raceYearText = (string)($raceYear ?? '');

$stmt = $user_home->runQuery("SELECT userName, changeAuth FROM users WHERE userID = :uid");
$stmt->execute([':uid' => $uid]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$userName = trim((string)($userRow['userName'] ?? ''));
$changeAuth = trim((string)($userRow['changeAuth'] ?? ''));

$currentTeamName = '';
$teamLookupError = '';

if (isset($dbconnect) && $dbconnect instanceof mysqli) {
    $sql = "SELECT teamName
            FROM user_teams
            WHERE userID = ?
              AND raceYear = ?
            LIMIT 1";
    $st = mysqli_prepare($dbconnect, $sql);
    if ($st) {
        mysqli_stmt_bind_param($st, 'is', $uid, $raceYearText);
        if (mysqli_stmt_execute($st)) {
            $res = mysqli_stmt_get_result($st);
            if ($res) {
                $r = mysqli_fetch_assoc($res);
                $currentTeamName = trim((string)($r['teamName'] ?? ''));
            }
        } else {
            $teamLookupError = mysqli_stmt_error($st);
        }
        mysqli_stmt_close($st);
    } else {
        $teamLookupError = mysqli_error($dbconnect);
    }
}

$teamPath = __DIR__ . '/team.php';
$teamSource = is_file($teamPath) ? (string)file_get_contents($teamPath) : '';

$teamVersion = '';
if ($teamSource !== '' && preg_match('/\*\s*VERSION:\s*(v\d+)/i', $teamSource, $m)) {
    $teamVersion = (string)$m[1];
}

$outerGateNeedle = "if (\$currentUserTeamName === '')";
$renderCallNeedle = "mrl_teamname_render_form(\$dbconnect, (string)\$raceYear, \$uid, (string)\$teamNameMessage);";
$gateCount = $teamSource !== '' ? substr_count($teamSource, $outerGateNeedle) : 0;
$renderCallCount = $teamSource !== '' ? substr_count($teamSource, $renderCallNeedle) : 0;

$expectedRoute = '';
if ((string)($formLocked ?? '') !== 'no') {
    $expectedRoute = 'FORM_LOCKED_MESSAGE';
} elseif ($currentTeamName === '') {
    $expectedRoute = 'TEAM_NAME_GATE';
} elseif (!empty($pickWindowIsOpen)) {
    $expectedRoute = 'NORMAL_PICK_FORM';
} else {
    $expectedRoute = 'POST_DEADLINE_ROUTING';
}

$sourceExcerpt = '';
if ($gateCount > 0) {
    $pos = strpos($teamSource, $outerGateNeedle);
    if ($pos !== false) {
        $sourceExcerpt = substr($teamSource, max(0, $pos - 500), 2200);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Team Name Routing Diagnostic</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1150px;margin:0 auto}
h1{color:var(--gold);margin:0 0 6px}
h2{margin:0 0 12px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
table{width:100%;border-collapse:collapse}
th,td{padding:9px 10px;border-bottom:1px solid #333;text-align:left;vertical-align:top}
th{color:var(--gold)}
.pass{color:var(--good);font-weight:800}
.fail{color:var(--bad);font-weight:800}
.muted{color:var(--muted)}
code{background:#252525;padding:2px 5px;border-radius:5px}
pre{white-space:pre-wrap;word-break:break-word;background:#111;border:1px solid #333;border-radius:10px;padding:12px}
.route{font-size:20px;font-weight:800;color:var(--gold)}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL Team Name Routing Diagnostic</h1>
<div class="muted">Generated 9/22/2026 5:06:28 pm ET · read-only · no writes · inspects current production team.php</div>

<div class="card">
<h2>Runtime State</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php
row('Logged-in userID', (string)$uid, $uid > 0 ? 'PASS' : 'FAIL');
row('User name', $userName, $userName !== '' ? 'PASS' : 'FAIL');
row('changeAuth', $changeAuth);
row('raceYear', $raceYearText, $raceYearText !== '' ? 'PASS' : 'FAIL');
row('segment', (string)($segment ?? ''));
row('formLocked', (string)($formLocked ?? '[unset]'));
row('formLockedMessage', (string)($formLockedMessage ?? '[unset]'));
row('pickWindowIsOpen', isset($pickWindowIsOpen) ? ($pickWindowIsOpen ? 'true' : 'false') : '[unset]');
row('pickWindowStatus', (string)($pickWindowStatus ?? '[unset]'));
row('pickWindowSource', (string)($pickWindowSource ?? '[unset]'));
row('formLockDate', (string)($formLockDate ?? '[unset]'));
row('Current Team Name', $currentTeamName === '' ? '[blank / none]' : $currentTeamName, $teamLookupError === '' ? 'PASS' : 'FAIL');
if ($teamLookupError !== '') row('Team lookup error', $teamLookupError, 'FAIL');
?>
</table>
<p class="route">Expected route from these values: <?php echo h($expectedRoute); ?></p>
</div>

<div class="card">
<h2>Installed team.php Source Check</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php
row('team.php exists', $teamPath, is_file($teamPath) ? 'PASS' : 'FAIL');
row('team.php version', $teamVersion === '' ? '[not found]' : $teamVersion, $teamVersion === 'v057' ? 'PASS' : 'FAIL');
row('Outer Team Name gate count', (string)$gateCount, $gateCount === 1 ? 'PASS' : 'FAIL');
row('Team Name render-call count', (string)$renderCallCount, $renderCallCount >= 1 ? 'PASS' : 'FAIL');
?>
</table>
</div>

<?php if ($sourceExcerpt !== ''): ?>
<div class="card">
<h2>Installed Gate Excerpt</h2>
<pre><?php echo h($sourceExcerpt); ?></pre>
</div>
<?php endif; ?>

<div class="card">
<h2>What to send back</h2>
<p>A screenshot of the <strong>Runtime State</strong> and <strong>Installed team.php Source Check</strong> sections is enough.</p>
</div>
</div>
</body>
</html>
