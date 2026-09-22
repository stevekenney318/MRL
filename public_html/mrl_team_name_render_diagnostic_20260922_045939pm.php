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

function diag_row(string $label, string $value, string $status = ''): void {
    $class = $status === 'PASS' ? 'pass' : ($status === 'FAIL' ? 'fail' : '');
    echo '<tr>';
    echo '<td>' . h($label) . '</td>';
    echo '<td class="' . h($class) . '">' . h($status) . '</td>';
    echo '<td><code>' . h($value) . '</code></td>';
    echo '</tr>';
}

$uid = (int)($_SESSION['userSession'] ?? 0);
$raceYearText = (string)($raceYear ?? '');

$stmt = $user_home->runQuery("SELECT userName FROM users WHERE userID = :uid");
$stmt->execute([':uid' => $uid]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);
$userName = trim((string)($userRow['userName'] ?? ''));

$currentTeamName = '';
$userTeamsQueryOk = false;
$userTeamsError = '';

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
            $userTeamsQueryOk = true;
            if ($res) {
                $r = mysqli_fetch_assoc($res);
                $currentTeamName = trim((string)($r['teamName'] ?? ''));
            }
        } else {
            $userTeamsError = mysqli_stmt_error($st);
        }
        mysqli_stmt_close($st);
    } else {
        $userTeamsError = mysqli_error($dbconnect);
    }
}

$lockFlag = '';
$lockError = '';
try {
    if (isset($dbconnect) && $dbconnect instanceof mysqli) {
        $lockFlag = mrl_teamname_get_lock_flag($dbconnect, $raceYearText);
    }
} catch (Throwable $e) {
    $lockError = get_class($e) . ': ' . $e->getMessage();
}

$renderHtml = '';
$renderError = '';
$renderWarnings = [];

set_error_handler(function ($severity, $message, $file, $line) use (&$renderWarnings) {
    $renderWarnings[] = $message . ' in ' . $file . ':' . $line;
    return false;
});

try {
    ob_start();

    if (!isset($dbconnect) || !($dbconnect instanceof mysqli)) {
        throw new RuntimeException('$dbconnect is missing or is not mysqli.');
    }

    mrl_teamname_render_form($dbconnect, $raceYearText, $uid, '');

    $renderHtml = (string)ob_get_clean();
} catch (Throwable $e) {
    if (ob_get_level() > 0) {
        $partial = (string)ob_get_clean();
        $renderHtml = $partial;
    }
    $renderError = get_class($e) . ': ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine();
}

restore_error_handler();

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Team Name Render Diagnostic</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1100px;margin:0 auto}
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
.preview{background:#151515;border:1px solid #444;border-radius:10px;padding:14px;overflow:auto}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL Team Name Render Diagnostic</h1>
<div class="muted">Generated 9/22/2026 4:59:39 pm ET · read-only · no Team Name save handler is called</div>

<div class="card">
<h2>Context</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php
diag_row('Logged-in userID', (string)$uid, $uid > 0 ? 'PASS' : 'FAIL');
diag_row('User name', $userName, $userName !== '' ? 'PASS' : 'FAIL');
diag_row('raceYear', $raceYearText, $raceYearText !== '' ? 'PASS' : 'FAIL');
diag_row('dbconnect type', isset($dbconnect) ? gettype($dbconnect) . (is_object($dbconnect) ? ' / ' . get_class($dbconnect) : '') : '[missing]', (isset($dbconnect) && $dbconnect instanceof mysqli) ? 'PASS' : 'FAIL');
diag_row('user_teams query', $userTeamsQueryOk ? 'query succeeded' : ($userTeamsError !== '' ? $userTeamsError : 'query not run'), $userTeamsQueryOk ? 'PASS' : 'FAIL');
diag_row('Current Team Name', $currentTeamName === '' ? '[blank / none]' : $currentTeamName, '');
diag_row('lockTeamName', $lockError !== '' ? $lockError : ($lockFlag === '' ? '[blank]' : $lockFlag), $lockError === '' ? 'PASS' : 'FAIL');
?>
</table>
</div>

<div class="card">
<h2>Render Test</h2>
<?php if ($renderError !== ''): ?>
<p class="fail">FAIL — Team Name form threw an error.</p>
<pre><?php echo h($renderError); ?></pre>
<?php else: ?>
<p class="pass">PASS — Team Name form rendered without a Throwable.</p>
<?php endif; ?>

<?php if (!empty($renderWarnings)): ?>
<p class="fail">Warnings/notices captured:</p>
<pre><?php echo h(implode("\n", $renderWarnings)); ?></pre>
<?php endif; ?>

<?php if ($renderHtml !== ''): ?>
<h3>Captured form output</h3>
<div class="preview"><?php echo $renderHtml; ?></div>
<?php else: ?>
<p class="muted">No form HTML was produced.</p>
<?php endif; ?>
</div>

<div class="card">
<h2>What to send back</h2>
<p>A screenshot of this page is enough. If it shows a FAIL, the exact exception/error text is the part I need.</p>
</div>
</div>
</body>
</html>
