<?php
declare(strict_types=1);

/**
 * admin_mrl_test_team_reset.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/31/2026 4:03:48 pm
 *
 * PURPOSE:
 *   Reusable Admin-only utility to reset the dedicated MRL test account
 *   (userID 999) to a "no picks submitted yet" state for one selected year.
 *
 * v002 FIX:
 *   - Removes assumptions about exact users-table column names that could
 *     trigger HTTP 500 on schemas that do not contain userName/userActive/
 *     userAdmin/userStatus in that exact form.
 *   - Uses SELECT * for the single user row and displays the safest available
 *     account label.
 *   - Makes year discovery and team lookup tolerant of schema differences.
 *
 * WHAT IT CLEARS:
 *   - user_picks rows for userID 999 + selected raceYear
 *   - user_picks_history rows for userID 999 + selected raceYear
 *
 * WHAT IT PRESERVES:
 *   - users row / login
 *   - user_teams row / team name
 *   - profile/theme/preferences
 *   - all other users
 *   - all other years
 *   - scoring/result files and snapshots
 *
 * SAFETY:
 *   - Hard-coded to userID 999 only.
 *   - Admin-only.
 *   - Preflight verifies required tables/columns.
 *   - Shows exact row counts before reset.
 *   - Requires typed confirmation.
 *   - Writes complete JSON backup BEFORE deletion.
 *   - Deletes inside a DB transaction.
 *
 * NO OTHER USERS OR YEARS ARE TOUCHED.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

require __DIR__ . '/config.php';
require __DIR__ . '/config_mrl.php';

$adminUid = (int)($_SESSION['userSession'] ?? 0);
if (!isAdmin($adminUid)) {
    http_response_code(403);
    exit('Admin access required.');
}

if (!isset($dbconnect) || !($dbconnect instanceof mysqli)) {
    http_response_code(500);
    exit('Database connection is not available.');
}

const MRL_TEST_UID = 999;

function mttr2_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function mttr2_table_columns(mysqli $db, string $table): array {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return [];
    $res = mysqli_query($db, "SHOW COLUMNS FROM `$table`");
    if (!$res) return [];
    $cols = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $cols[] = (string)($row['Field'] ?? '');
    }
    mysqli_free_result($res);
    return $cols;
}

function mttr2_has_columns(mysqli $db, string $table, array $required): bool {
    $cols = mttr2_table_columns($db, $table);
    foreach ($required as $col) {
        if (!in_array($col, $cols, true)) return false;
    }
    return true;
}

function mttr2_rows(mysqli $db, string $sql, array $params = [], string $types = ''): array {
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) throw new RuntimeException('Prepare failed: ' . mysqli_error($db));
    if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Query failed: ' . $err);
    }
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function mttr2_count(mysqli $db, string $table, int $uid, string $year): int {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) throw new RuntimeException('Unsafe table.');
    $rows = mttr2_rows(
        $db,
        "SELECT COUNT(*) AS c FROM `$table` WHERE userID = ? AND raceYear = ?",
        [$uid, $year],
        'is'
    );
    return (int)($rows[0]['c'] ?? 0);
}

function mttr2_account(mysqli $db): array {
    if (!mttr2_has_columns($db, 'users', ['userID'])) return [];
    $rows = mttr2_rows($db, "SELECT * FROM users WHERE userID = ? LIMIT 1", [MRL_TEST_UID], 'i');
    return $rows[0] ?? [];
}

function mttr2_account_label(array $row): string {
    foreach (['userName','username','user_name','name','displayName','email','userEmail'] as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') return trim((string)$row[$key]);
    }
    return $row ? 'MRL test account' : '(missing)';
}

function mttr2_team_row(mysqli $db, string $year): array {
    if (!mttr2_has_columns($db, 'user_teams', ['userID','raceYear'])) return [];
    $rows = mttr2_rows(
        $db,
        "SELECT * FROM user_teams WHERE userID = ? AND raceYear = ? LIMIT 1",
        [MRL_TEST_UID, $year],
        'is'
    );
    return $rows[0] ?? [];
}

function mttr2_team_label(array $row): string {
    foreach (['teamName','team_name','name'] as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') return trim((string)$row[$key]);
    }
    return $row ? '(team row found)' : '(no team row for this year)';
}

function mttr2_years(mysqli $db, string $fallback): array {
    $years = [$fallback];
    foreach (['user_picks','user_picks_history','user_teams'] as $table) {
        if (!mttr2_has_columns($db, $table, ['userID','raceYear'])) continue;
        try {
            $rows = mttr2_rows(
                $db,
                "SELECT DISTINCT raceYear FROM `$table` WHERE userID = ? ORDER BY raceYear DESC",
                [MRL_TEST_UID],
                'i'
            );
            foreach ($rows as $row) {
                $y = trim((string)($row['raceYear'] ?? ''));
                if (preg_match('/^\d{4}$/', $y)) $years[] = $y;
            }
        } catch (Throwable $e) {}
    }
    $years = array_values(array_unique($years));
    rsort($years, SORT_STRING);
    return $years;
}

function mttr2_backup_dir(): string {
    return __DIR__ . '/_migration_backups/mrl_test_team_reset';
}

function mttr2_backup(mysqli $db, string $year): string {
    $dir = mttr2_backup_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create backup folder.');
    }

    $payload = [
        'tool_version' => 'v002',
        'created_at' => date('Y-m-d H:i:s'),
        'userID' => MRL_TEST_UID,
        'raceYear' => $year,
        'user_picks' => mttr2_rows(
            $db,
            "SELECT * FROM user_picks WHERE userID = ? AND raceYear = ? ORDER BY pickID ASC",
            [MRL_TEST_UID, $year],
            'is'
        ),
        'user_picks_history' => mttr2_rows(
            $db,
            "SELECT * FROM user_picks_history WHERE userID = ? AND raceYear = ?",
            [MRL_TEST_UID, $year],
            'is'
        ),
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json)) throw new RuntimeException('Could not encode backup JSON.');

    $path = $dir . '/MRL_test_reset_backup_' . $year . '_' . date('Ymd_His') . '.json';
    if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Could not write backup JSON.');
    }
    return $path;
}

function mttr2_delete(mysqli $db, string $year): array {
    mysqli_begin_transaction($db);
    try {
        $deleted = [];

        foreach (['user_picks_history','user_picks'] as $table) {
            $stmt = mysqli_prepare($db, "DELETE FROM `$table` WHERE userID = ? AND raceYear = ?");
            if (!$stmt) throw new RuntimeException("Prepare failed for $table: " . mysqli_error($db));
            $uid = MRL_TEST_UID;
            $yr = $year;
            mysqli_stmt_bind_param($stmt, 'is', $uid, $yr);
            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                throw new RuntimeException("Delete failed for $table: $err");
            }
            $deleted[$table] = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
        }

        mysqli_commit($db);
        return $deleted;
    } catch (Throwable $e) {
        mysqli_rollback($db);
        throw $e;
    }
}

if (!isset($_SESSION['mttr2_csrf'])) {
    $_SESSION['mttr2_csrf'] = bin2hex(random_bytes(24));
}

$currentYear = isset($raceYear) && preg_match('/^\d{4}$/', (string)$raceYear)
    ? (string)$raceYear
    : date('Y');

$years = mttr2_years($dbconnect, $currentYear);

$selectedYear = trim((string)($_POST['raceYear'] ?? $_GET['raceYear'] ?? $currentYear));
if (!preg_match('/^\d{4}$/', $selectedYear)) $selectedYear = $currentYear;

$account = mttr2_account($dbconnect);
$team = mttr2_team_row($dbconnect, $selectedYear);

$preflight = [
    'users contains userID' => mttr2_has_columns($dbconnect, 'users', ['userID']),
    'MRL test account userID 999 exists' => ((int)($account['userID'] ?? 0) === MRL_TEST_UID),
    'user_picks contains userID + raceYear' => mttr2_has_columns($dbconnect, 'user_picks', ['userID','raceYear']),
    'user_picks_history contains userID + raceYear' => mttr2_has_columns($dbconnect, 'user_picks_history', ['userID','raceYear']),
    'user_teams contains userID + raceYear' => mttr2_has_columns($dbconnect, 'user_teams', ['userID','raceYear']),
];

$ready = !in_array(false, $preflight, true);
$message = '';
$messageClass = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    if (!hash_equals((string)$_SESSION['mttr2_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $message = 'Reset blocked: security token mismatch.';
        $messageClass = 'bad';
    } elseif (!$ready) {
        $message = 'Reset blocked because preflight is not fully green.';
        $messageClass = 'bad';
    } elseif (trim((string)($_POST['confirm_text'] ?? '')) !== 'RESET MRL 999') {
        $message = 'Reset blocked: type RESET MRL 999 exactly.';
        $messageClass = 'bad';
    } else {
        try {
            $backup = mttr2_backup($dbconnect, $selectedYear);
            $deleted = mttr2_delete($dbconnect, $selectedYear);

            $afterP = mttr2_count($dbconnect, 'user_picks', MRL_TEST_UID, $selectedYear);
            $afterH = mttr2_count($dbconnect, 'user_picks_history', MRL_TEST_UID, $selectedYear);

            if ($afterP !== 0 || $afterH !== 0) {
                throw new RuntimeException('Postflight failed: rows remain after reset.');
            }

            $message = 'RESET COMPLETE — deleted '
                . (int)($deleted['user_picks'] ?? 0) . ' live pick row(s) and '
                . (int)($deleted['user_picks_history'] ?? 0) . ' history row(s). '
                . 'Backup: ' . basename($backup) . '.';
            $messageClass = 'ok';
        } catch (Throwable $e) {
            $message = 'Reset failed: ' . $e->getMessage();
            $messageClass = 'bad';
        }
    }
}

$picksCount = $ready ? mttr2_count($dbconnect, 'user_picks', MRL_TEST_UID, $selectedYear) : 0;
$historyCount = $ready ? mttr2_count($dbconnect, 'user_picks_history', MRL_TEST_UID, $selectedYear) : 0;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Test Team Reset v002</title>
<style>
:root{--bg:#111315;--panel:#1d2023;--border:#51565b;--text:#eee;--muted:#b9bec4;--gold:#efca84;--green:#6bea9f;--red:#ff7e7e;--blue:#65c8ff}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1100px;margin:20px auto}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}
a{color:var(--blue)}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.stat{background:#17191b;border:1px solid #444;border-radius:10px;padding:14px}
.num{font-size:32px;font-weight:800}
.label{color:var(--muted)}
.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:800}
.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}
.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}
.info{background:#122a3a;border:1px solid #2d6a8c;color:#d8f2ff}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid #3a3e42;text-align:left}
th{color:#ffe0a0}
.pass{color:var(--green);font-weight:800}
.fail{color:var(--red);font-weight:800}
input,select{padding:9px;background:#101214;color:#eee;border:1px solid #666;border-radius:7px;font-size:15px}
.btn{padding:11px 18px;border-radius:8px;font-weight:800;cursor:pointer}
.reset{background:#a32222;color:#fff;border:1px solid #ef6666}
@media(max-width:760px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>MRL Test Team Reset</h1>
<p><strong>VERSION:</strong> v002 &nbsp; | &nbsp; <strong>Last modified:</strong> 8/31/2026 4:03:48 pm</p>
<p><a href="/team.php">← Team</a></p>

<?php if ($message !== ''): ?>
<div class="banner <?php echo mttr2_h($messageClass); ?>"><?php echo mttr2_h($message); ?></div>
<?php endif; ?>

<div class="banner info">
Hard-coded target: <strong>userID 999 only.</strong>
It clears only live picks + pick history for the selected year.
</div>
</div>

<div class="card">
<h2>Target</h2>
<form method="get">
<label>Race year:
<select name="raceYear" onchange="this.form.submit()">
<?php foreach ($years as $y): ?>
<option value="<?php echo mttr2_h($y); ?>" <?php echo $y === $selectedYear ? 'selected' : ''; ?>>
<?php echo mttr2_h($y); ?>
</option>
<?php endforeach; ?>
</select>
</label>
</form>

<p>
<strong>Account:</strong> <?php echo mttr2_h(mttr2_account_label($account)); ?>
(userID 999)
<br>
<strong>Team:</strong> <?php echo mttr2_h(mttr2_team_label($team)); ?>
</p>
</div>

<div class="card">
<h2>Current Test Data</h2>
<div class="grid">
<div class="stat"><div class="num"><?php echo $picksCount; ?></div><div class="label">Live user_picks rows</div></div>
<div class="stat"><div class="num"><?php echo $historyCount; ?></div><div class="label">user_picks_history rows</div></div>
</div>

<?php if ($ready && $picksCount === 0 && $historyCount === 0): ?>
<div class="banner ok">Already clean for <?php echo mttr2_h($selectedYear); ?> — no submissions exist.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($preflight as $label => $status): ?>
<tr>
<td><?php echo mttr2_h($label); ?></td>
<td class="<?php echo $status ? 'pass' : 'fail'; ?>"><?php echo $status ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Reset <?php echo mttr2_h($selectedYear); ?></h2>
<p>A complete JSON backup is written first.</p>

<form method="post" onsubmit="return confirm('Reset MRL userID 999 picks/history for <?php echo mttr2_h($selectedYear); ?>?');">
<input type="hidden" name="csrf" value="<?php echo mttr2_h((string)$_SESSION['mttr2_csrf']); ?>">
<input type="hidden" name="action" value="reset">
<input type="hidden" name="raceYear" value="<?php echo mttr2_h($selectedYear); ?>">

<p>Type <strong>RESET MRL 999</strong>:</p>
<input name="confirm_text" autocomplete="off" style="width:100%;max-width:360px" placeholder="RESET MRL 999">
<br><br>
<button class="btn reset" type="submit" <?php echo $ready ? '' : 'disabled'; ?>>Reset MRL Picks + History</button>
</form>
</div>

</div>
</body>
</html>
