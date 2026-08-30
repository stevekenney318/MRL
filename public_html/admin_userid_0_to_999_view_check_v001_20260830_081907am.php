<?php
declare(strict_types=1);

/**
 * admin_userid_0_to_999_view_check.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/30/2026 8:19:07 am
 *
 * PURPOSE:
 *   Read-only follow-up for Step 4 migration preflight.
 *   Inspect Financial, picks, and picks_history because INFORMATION_SCHEMA
 *   returned no storage engine for them.
 *
 * THIS FILE DOES NOT MODIFY THE DATABASE.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';

function vh($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function q_all(mysqli $db, string $sql): array {
    $res = mysqli_query($db, $sql);
    if ($res === false) {
        throw new RuntimeException(mysqli_error($db) . ' | SQL: ' . $sql);
    }
    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = $row;
    }
    mysqli_free_result($res);
    return $rows;
}

function scalar_i(mysqli $db, string $sql): int {
    $rows = q_all($db, $sql);
    if (!$rows) return 0;
    $first = $rows[0];
    return (int)reset($first);
}

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

if (!isAdmin($_SESSION['userSession'] ?? null)) {
    http_response_code(403);
    exit('Not authorized.');
}

$dbNameRows = q_all($dbconnect, "SELECT DATABASE() AS db");
$dbName = (string)($dbNameRows[0]['db'] ?? '');
$dbEsc = mysqli_real_escape_string($dbconnect, $dbName);

$targets = ['Financial', 'picks', 'picks_history'];
$reportRows = [];

foreach ($targets as $table) {
    $tEsc = mysqli_real_escape_string($dbconnect, $table);

    $meta = q_all(
        $dbconnect,
        "SELECT TABLE_NAME, TABLE_TYPE, ENGINE
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA='{$dbEsc}'
           AND TABLE_NAME='{$tEsc}'
         LIMIT 1"
    );

    $type = (string)($meta[0]['TABLE_TYPE'] ?? '');
    $engine = (string)($meta[0]['ENGINE'] ?? '');

    $viewInfo = [];
    if (strtoupper($type) === 'VIEW') {
        $viewInfo = q_all(
            $dbconnect,
            "SELECT TABLE_NAME, IS_UPDATABLE, CHECK_OPTION, SECURITY_TYPE, VIEW_DEFINITION
             FROM INFORMATION_SCHEMA.VIEWS
             WHERE TABLE_SCHEMA='{$dbEsc}'
               AND TABLE_NAME='{$tEsc}'
             LIMIT 1"
        );
    }

    $showCreate = [];
    $res = @mysqli_query($dbconnect, "SHOW CREATE TABLE `" . str_replace('`','``',$table) . "`");
    if ($res) {
        $showCreate = mysqli_fetch_assoc($res) ?: [];
        mysqli_free_result($res);
    }

    $count0 = scalar_i($dbconnect, "SELECT COUNT(*) FROM `" . str_replace('`','``',$table) . "` WHERE `userID`=0");
    $count999 = scalar_i($dbconnect, "SELECT COUNT(*) FROM `" . str_replace('`','``',$table) . "` WHERE `userID`=999");

    $reportRows[] = [
        'table' => $table,
        'table_type' => $type,
        'engine' => $engine,
        'is_updatable' => (string)($viewInfo[0]['IS_UPDATABLE'] ?? ''),
        'check_option' => (string)($viewInfo[0]['CHECK_OPTION'] ?? ''),
        'security_type' => (string)($viewInfo[0]['SECURITY_TYPE'] ?? ''),
        'view_definition' => (string)($viewInfo[0]['VIEW_DEFINITION'] ?? ''),
        'show_create' => $showCreate,
        'count_userid_0' => $count0,
        'count_userid_999' => $count999,
    ];
}

$payload = [
    'report_version' => 'v001',
    'generated_at' => date('Y-m-d H:i:s'),
    'timezone' => 'America/New_York',
    'database' => $dbName,
    'rows' => $reportRows,
];

if (isset($_GET['export']) && $_GET['export'] === 'json') {
    $name = 'MRL_userID_0_to_999_view_check_' . date('Ymd_His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL userID 0 → 999 View Check</title>
<style>
body{margin:0;background:#101214;color:#eee;font-family:Tahoma,Verdana,Arial,sans-serif}
.wrap{width:96%;max-width:1500px;margin:20px auto}
.card{background:#1d2023;border:1px solid #4b5055;border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:#efc77e}
table{width:100%;border-collapse:collapse}
th,td{padding:9px;border-bottom:1px solid #3a3e42;vertical-align:top;text-align:left}
th{color:#ffe0a0;background:#17191b}
code,pre{font-family:Consolas,"Courier New",monospace;color:#71d7ff}
pre{white-space:pre-wrap;word-break:break-word}
.btn{display:inline-block;padding:10px 16px;border-radius:8px;background:#176fa4;color:#fff;text-decoration:none;font-weight:700}
.ok{color:#63e69a;font-weight:700}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>MRL userID 0 → 999 — View / Table Check</h1>
<p><strong>READ ONLY.</strong> Generated 8/30/2026 8:19:07 am America/New_York</p>
<a class="btn" href="?export=json">Export Report (JSON)</a>
</div>

<div class="card">
<h2>Results</h2>
<table>
<thead>
<tr>
<th>Name</th><th>Type</th><th>Engine</th><th>Updatable</th><th>ID 0</th><th>ID 999</th><th>Definition / Create</th>
</tr>
</thead>
<tbody>
<?php foreach ($reportRows as $r): ?>
<tr>
<td><code><?php echo vh($r['table']); ?></code></td>
<td><?php echo vh($r['table_type']); ?></td>
<td><?php echo vh($r['engine'] !== '' ? $r['engine'] : '(none)'); ?></td>
<td class="<?php echo strtoupper($r['is_updatable']) === 'YES' ? 'ok' : ''; ?>"><?php echo vh($r['is_updatable']); ?></td>
<td><?php echo (int)$r['count_userid_0']; ?></td>
<td><?php echo (int)$r['count_userid_999']; ?></td>
<td>
<?php if ($r['view_definition'] !== ''): ?>
<div><strong>VIEW_DEFINITION</strong></div>
<pre><?php echo vh($r['view_definition']); ?></pre>
<?php endif; ?>
<?php if (!empty($r['show_create'])): ?>
<div><strong>SHOW CREATE</strong></div>
<pre><?php echo vh(json_encode($r['show_create'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); ?></pre>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</body>
</html>
