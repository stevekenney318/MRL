<?php
declare(strict_types=1);

/**
 * admin_mrl_test_team_reset.php
 *
 * VERSION: v003
 * LAST MODIFIED: 9/23/2026 3:57:18 am
 *
 * PURPOSE:
 *   Reusable Admin-only utility to reset dedicated MRL test accounts
 *   in the 900-999 userID range to a "no picks submitted yet" state
 *   for one selected year.
 *
 * v003 CHANGE:
 *   - Replaces the hard-coded userID 999 target with a dropdown of existing
 *     users in the 900-999 range.
 *   - Keeps 999 available and adds 998 guest automatically when present.
 *   - Keeps reset scope narrow: selected test user + selected race year only.
 *   - Typed confirmation follows the selected ID, e.g. RESET MRL 998.
 *   - Backup filenames and JSON payload now identify the selected test user.
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
 *   - user_picks rows for selected 900-series test user + selected raceYear
 *   - user_picks_history rows for selected 900-series test user + selected raceYear
 *
 * WHAT IT PRESERVES:
 *   - users row / login
 *   - user_teams row / team name
 *   - profile/theme/preferences
 *   - all non-selected users
 *   - all other years
 *   - scoring/result files and snapshots
 *
 * SAFETY:
 *   - Target must be an existing userID from 900 through 999.
 *   - Admin-only.
 *   - Preflight verifies required tables/columns.
 *   - Shows exact row counts before reset.
 *   - Requires typed confirmation containing the selected ID.
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

const MRL_TEST_UID_MIN = 900;
const MRL_TEST_UID_MAX = 999;

function mttr3_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function mttr3_table_columns(mysqli $db, string $table): array {
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

function mttr3_has_columns(mysqli $db, string $table, array $required): bool {
    $cols = mttr3_table_columns($db, $table);
    foreach ($required as $col) {
        if (!in_array($col, $cols, true)) return false;
    }
    return true;
}

function mttr3_rows(mysqli $db, string $sql, array $params = [], string $types = ''): array {
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

function mttr3_count(mysqli $db, string $table, int $uid, string $year): int {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) throw new RuntimeException('Unsafe table.');
    $rows = mttr3_rows(
        $db,
        "SELECT COUNT(*) AS c FROM `$table` WHERE userID = ? AND raceYear = ?",
        [$uid, $year],
        'is'
    );
    return (int)($rows[0]['c'] ?? 0);
}

function mttr3_test_accounts(mysqli $db): array {
    if (!mttr3_has_columns($db, 'users', ['userID'])) return [];
    return mttr3_rows(
        $db,
        "SELECT * FROM users WHERE userID BETWEEN ? AND ? ORDER BY userID ASC",
        [MRL_TEST_UID_MIN, MRL_TEST_UID_MAX],
        'ii'
    );
}

function mttr3_account_label(array $row): string {
    foreach (['userName','username','user_name','name','displayName','email','userEmail'] as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') return trim((string)$row[$key]);
    }
    return $row ? 'MRL test account' : '(missing)';
}

function mttr3_find_account(array $accounts, int $uid): array {
    foreach ($accounts as $row) {
        if ((int)($row['userID'] ?? 0) === $uid) return $row;
    }
    return [];
}

function mttr3_team_row(mysqli $db, int $uid, string $year): array {
    if (!mttr3_has_columns($db, 'user_teams', ['userID','raceYear'])) return [];
    $rows = mttr3_rows(
        $db,
        "SELECT * FROM user_teams WHERE userID = ? AND raceYear = ? LIMIT 1",
        [$uid, $year],
        'is'
    );
    return $rows[0] ?? [];
}

function mttr3_team_label(array $row): string {
    foreach (['teamName','team_name','name'] as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') return trim((string)$row[$key]);
    }
    return $row ? '(team row found)' : '(no team row for this year)';
}

function mttr3_years(mysqli $db, int $uid, string $fallback): array {
    $years = [$fallback];
    foreach (['user_picks','user_picks_history','user_teams'] as $table) {
        if (!mttr3_has_columns($db, $table, ['userID','raceYear'])) continue;
        try {
            $rows = mttr3_rows(
                $db,
                "SELECT DISTINCT raceYear FROM `$table` WHERE userID = ? ORDER BY raceYear DESC",
                [$uid],
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

function mttr3_backup_dir(): string {
    return __DIR__ . '/_migration_backups/mrl_test_team_reset';
}

function mttr3_backup(mysqli $db, int $uid, string $year): string {
    $dir = mttr3_backup_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create backup folder.');
    }

    $payload = [
        'tool_version' => 'v003',
        'created_at' => date('Y-m-d H:i:s'),
        'userID' => $uid,
        'raceYear' => $year,
        'user_picks' => mttr3_rows(
            $db,
            "SELECT * FROM user_picks WHERE userID = ? AND raceYear = ? ORDER BY pickID ASC",
            [$uid, $year],
            'is'
        ),
        'user_picks_history' => mttr3_rows(
            $db,
            "SELECT * FROM user_picks_history WHERE userID = ? AND raceYear = ?",
            [$uid, $year],
            'is'
        ),
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json)) throw new RuntimeException('Could not encode backup JSON.');

    $path = $dir . '/MRL_test_reset_backup_UID' . $uid . '_' . $year . '_' . date('Ymd_His') . '.json';
    if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Could not write backup JSON.');
    }
    return $path;
}

function mttr3_delete(mysqli $db, int $uid, string $year): array {
    mysqli_begin_transaction($db);
    try {
        $deleted = [];

        foreach (['user_picks_history','user_picks'] as $table) {
            $stmt = mysqli_prepare($db, "DELETE FROM `$table` WHERE userID = ? AND raceYear = ?");
            if (!$stmt) throw new RuntimeException("Prepare failed for $table: " . mysqli_error($db));
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

if (!isset($_SESSION['mttr3_csrf'])) {
    $_SESSION['mttr3_csrf'] = bin2hex(random_bytes(24));
}

$currentYear = isset($raceYear) && preg_match('/^\d{4}$/', (string)$raceYear)
    ? (string)$raceYear
    : date('Y');

$accounts = mttr3_test_accounts($dbconnect);
$accountIds = array_map(static function ($row) {
    return (int)($row['userID'] ?? 0);
}, $accounts);

$requestedUid = (int)($_POST['targetUid'] ?? $_GET['targetUid'] ?? 999);
if (!in_array($requestedUid, $accountIds, true)) {
    if (in_array(999, $accountIds, true)) {
        $requestedUid = 999;
    } elseif (!empty($accountIds)) {
        $requestedUid = (int)$accountIds[0];
    } else {
        $requestedUid = 0;
    }
}

$selectedUid = $requestedUid;
$account = mttr3_find_account($accounts, $selectedUid);

$years = $selectedUid > 0
    ? mttr3_years($dbconnect, $selectedUid, $currentYear)
    : [$currentYear];

$selectedYear = trim((string)($_POST['raceYear'] ?? $_GET['raceYear'] ?? $currentYear));
if (!preg_match('/^\d{4}$/', $selectedYear)) $selectedYear = $currentYear;

$team = $selectedUid > 0 ? mttr3_team_row($dbconnect, $selectedUid, $selectedYear) : [];

$uidInRange = ($selectedUid >= MRL_TEST_UID_MIN && $selectedUid <= MRL_TEST_UID_MAX);
$accountExists = !empty($account) && (int)($account['userID'] ?? 0) === $selectedUid;

$preflight = [
    'users contains userID' => mttr3_has_columns($dbconnect, 'users', ['userID']),
    'At least one 900-series test account exists' => !empty($accounts),
    'Selected userID is in 900-999 range' => $uidInRange,
    'Selected test account exists' => $accountExists,
    'user_picks contains userID + raceYear' => mttr3_has_columns($dbconnect, 'user_picks', ['userID','raceYear']),
    'user_picks_history contains userID + raceYear' => mttr3_has_columns($dbconnect, 'user_picks_history', ['userID','raceYear']),
    'user_teams contains userID + raceYear' => mttr3_has_columns($dbconnect, 'user_teams', ['userID','raceYear']),
];

$ready = !in_array(false, $preflight, true);
$message = '';
$messageClass = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    if (!hash_equals((string)$_SESSION['mttr3_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $message = 'Reset blocked: security token mismatch.';
        $messageClass = 'bad';
    } elseif (!$ready) {
        $message = 'Reset blocked because preflight is not fully green.';
        $messageClass = 'bad';
    } elseif (trim((string)($_POST['confirm_text'] ?? '')) !== ('RESET MRL ' . $selectedUid)) {
        $message = 'Reset blocked: type RESET MRL ' . $selectedUid . ' exactly.';
        $messageClass = 'bad';
    } else {
        try {
            $backup = mttr3_backup($dbconnect, $selectedUid, $selectedYear);
            $deleted = mttr3_delete($dbconnect, $selectedUid, $selectedYear);

            $afterP = mttr3_count($dbconnect, 'user_picks', $selectedUid, $selectedYear);
            $afterH = mttr3_count($dbconnect, 'user_picks_history', $selectedUid, $selectedYear);

            if ($afterP !== 0 || $afterH !== 0) {
                throw new RuntimeException('Postflight failed: rows remain after reset.');
            }

            $message = 'RESET COMPLETE — userID ' . $selectedUid . ': deleted '
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

$picksCount = $ready ? mttr3_count($dbconnect, 'user_picks', $selectedUid, $selectedYear) : 0;
$historyCount = $ready ? mttr3_count($dbconnect, 'user_picks_history', $selectedUid, $selectedYear) : 0;

$accountActive = '';
if (array_key_exists('userActive', $account)) {
    $accountActive = trim((string)$account['userActive']);
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Test Account Reset v003</title>
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
.target-row{display:flex;gap:14px;align-items:end;flex-wrap:wrap}
.target-row label{display:flex;flex-direction:column;gap:6px}
@media(max-width:760px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>MRL Test Account Reset</h1>
<p><strong>VERSION:</strong> v003 &nbsp; | &nbsp; <strong>Last modified:</strong> 9/23/2026 3:57:18 am</p>
<p><a href="/team.php">← Team</a></p>

<?php if ($message !== ''): ?>
<div class="banner <?php echo mttr3_h($messageClass); ?>"><?php echo mttr3_h($message); ?></div>
<?php endif; ?>

<div class="banner info">
Selectable target: <strong>existing userIDs 900-999 only.</strong>
It clears only live picks + pick history for the selected user and selected year.
</div>
</div>

<div class="card">
<h2>Target</h2>
<form method="get" class="target-row">
<label>Test account:
<select name="targetUid" onchange="this.form.submit()">
<?php foreach ($accounts as $row): ?>
<?php
    $uid = (int)($row['userID'] ?? 0);
    $label = mttr3_account_label($row);
?>
<option value="<?php echo $uid; ?>" <?php echo $uid === $selectedUid ? 'selected' : ''; ?>>
<?php echo mttr3_h($label . ' (ID ' . $uid . ')'); ?>
</option>
<?php endforeach; ?>
</select>
</label>

<label>Race year:
<select name="raceYear" onchange="this.form.submit()">
<?php foreach ($years as $y): ?>
<option value="<?php echo mttr3_h($y); ?>" <?php echo $y === $selectedYear ? 'selected' : ''; ?>>
<?php echo mttr3_h($y); ?>
</option>
<?php endforeach; ?>
</select>
</label>
</form>

<p>
<strong>Account:</strong> <?php echo mttr3_h(mttr3_account_label($account)); ?>
(userID <?php echo (int)$selectedUid; ?>)
<?php if ($accountActive !== ''): ?>
<br><strong>userActive:</strong> <?php echo mttr3_h($accountActive); ?>
<?php endif; ?>
<br>
<strong>Team:</strong> <?php echo mttr3_h(mttr3_team_label($team)); ?>
</p>
</div>

<div class="card">
<h2>Current Test Data</h2>
<div class="grid">
<div class="stat"><div class="num"><?php echo $picksCount; ?></div><div class="label">Live user_picks rows</div></div>
<div class="stat"><div class="num"><?php echo $historyCount; ?></div><div class="label">user_picks_history rows</div></div>
</div>

<?php if ($ready && $picksCount === 0 && $historyCount === 0): ?>
<div class="banner ok">Already clean for userID <?php echo (int)$selectedUid; ?> / <?php echo mttr3_h($selectedYear); ?> — no submissions exist.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($preflight as $label => $status): ?>
<tr>
<td><?php echo mttr3_h($label); ?></td>
<td class="<?php echo $status ? 'pass' : 'fail'; ?>"><?php echo $status ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Reset <?php echo mttr3_h($selectedYear); ?></h2>
<p>A complete JSON backup is written first.</p>

<form method="post" onsubmit="return confirm('Reset MRL userID <?php echo (int)$selectedUid; ?> picks/history for <?php echo mttr3_h($selectedYear); ?>?');">
<input type="hidden" name="csrf" value="<?php echo mttr3_h((string)$_SESSION['mttr3_csrf']); ?>">
<input type="hidden" name="action" value="reset">
<input type="hidden" name="targetUid" value="<?php echo (int)$selectedUid; ?>">
<input type="hidden" name="raceYear" value="<?php echo mttr3_h($selectedYear); ?>">

<p>Type <strong>RESET MRL <?php echo (int)$selectedUid; ?></strong>:</p>
<input name="confirm_text" autocomplete="off" style="width:100%;max-width:360px" placeholder="RESET MRL <?php echo (int)$selectedUid; ?>">
<br><br>
<button class="btn reset" type="submit" <?php echo $ready ? '' : 'disabled'; ?>>Reset Selected Test Picks + History</button>
</form>
</div>

</div>
</body>
</html>
