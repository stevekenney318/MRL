<?php
declare(strict_types=1);

/**
 * admin_userid_0_to_999_discovery.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/30/2026 6:47:16 am
 *
 * PURPOSE:
 *   Read-only discovery report for the planned MRL test-account migration:
 *       userID 0 -> userID 999
 *
 * THIS FILE DOES NOT MODIFY THE DATABASE.
 *
 * It reports:
 *   - Current database / connection status
 *   - users.userID 0 and 999 collision status
 *   - Every likely user-ID column in the current database
 *   - Every foreign key that references users.userID
 *   - Counts of rows containing 0 and 999 in each discovered user-ID column
 *   - Safe/redacted users rows for IDs 0 and 999
 *   - user_teams history summary for IDs 0 and 999
 *   - user_picks history summary for IDs 0 and 999
 *   - Referencing constraints / update rules
 *   - A concise migration-readiness summary
 *
 * PHP: 7.3+
 *
 * INSTALL / RUN:
 *   1. Upload this file to public_html/.
 *   2. Open it while logged in as an MRL admin.
 *   3. Send the complete report back for review.
 *
 * SECURITY:
 *   - Admin-only.
 *   - Password/token/secret-style fields are redacted from users output.
 *   - No UPDATE / INSERT / DELETE / ALTER statements exist in this file.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';

function d_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function d_db_name(): string
{
    global $dbconnect;

    if (!isset($dbconnect) || !$dbconnect) {
        return '';
    }

    $res = @mysqli_query($dbconnect, "SELECT DATABASE() AS db");
    if (!$res) {
        return '';
    }

    $row = mysqli_fetch_assoc($res);
    mysqli_free_result($res);

    return (string)($row['db'] ?? '');
}

function d_query_all(string $sql, array $params = []): array
{
    global $dbconnect;

    if (!isset($dbconnect) || !$dbconnect) {
        return [];
    }

    if (empty($params)) {
        $res = @mysqli_query($dbconnect, $sql);
        if (!$res) {
            return [];
        }

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
        mysqli_free_result($res);
        return $rows;
    }

    $stmt = @mysqli_prepare($dbconnect, $sql);
    if (!$stmt) {
        return [];
    }

    $types = '';
    $values = [];

    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
        $values[] = $param;
    }

    if ($types !== '') {
        $bind = [$stmt, $types];
        foreach ($values as $k => $v) {
            $bind[] = &$values[$k];
        }
        call_user_func_array('mysqli_stmt_bind_param', $bind);
    }

    if (!@mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function d_scalar_int(string $sql): int
{
    $rows = d_query_all($sql);
    if (empty($rows)) {
        return 0;
    }

    $first = $rows[0];
    $value = reset($first);
    return (int)$value;
}

function d_ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function d_table_exists(string $table): bool
{
    $db = d_db_name();
    if ($db === '') {
        return false;
    }

    $rows = d_query_all(
        "SELECT COUNT(*) AS c
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?",
        [$db, $table]
    );

    return !empty($rows) && (int)($rows[0]['c'] ?? 0) > 0;
}

function d_table_columns(string $table): array
{
    $db = d_db_name();
    if ($db === '') {
        return [];
    }

    return d_query_all(
        "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_KEY, EXTRA, ORDINAL_POSITION
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?
         ORDER BY ORDINAL_POSITION",
        [$db, $table]
    );
}

function d_safe_user_row(int $userId): array
{
    if (!d_table_exists('users')) {
        return [];
    }

    $columns = d_table_columns('users');
    $safe = [];

    foreach ($columns as $col) {
        $name = (string)($col['COLUMN_NAME'] ?? '');
        if ($name === '') {
            continue;
        }

        $lower = strtolower($name);
        if (
            strpos($lower, 'pass') !== false ||
            strpos($lower, 'secret') !== false ||
            strpos($lower, 'token') !== false ||
            strpos($lower, 'salt') !== false ||
            strpos($lower, 'hash') !== false ||
            strpos($lower, 'auth') !== false
        ) {
            continue;
        }

        $safe[] = $name;
    }

    if (empty($safe)) {
        return [];
    }

    $parts = [];
    foreach ($safe as $name) {
        $parts[] = d_ident($name);
    }

    $sql = "SELECT " . implode(', ', $parts) . "
            FROM `users`
            WHERE `userID` = " . (int)$userId . "
            LIMIT 1";

    $rows = d_query_all($sql);
    return $rows[0] ?? [];
}

function d_group_summary(string $table, int $userId, array $preferredGroupColumns): array
{
    if (!d_table_exists($table)) {
        return [];
    }

    $columnRows = d_table_columns($table);
    $available = [];

    foreach ($columnRows as $row) {
        $name = (string)($row['COLUMN_NAME'] ?? '');
        if ($name !== '') {
            $available[strtolower($name)] = $name;
        }
    }

    if (!isset($available['userid'])) {
        return [];
    }

    $groupCols = [];
    foreach ($preferredGroupColumns as $wanted) {
        $key = strtolower($wanted);
        if (isset($available[$key])) {
            $groupCols[] = $available[$key];
        }
    }

    if (empty($groupCols)) {
        $sql = "SELECT COUNT(*) AS row_count
                FROM " . d_ident($table) . "
                WHERE " . d_ident($available['userid']) . " = " . (int)$userId;
        return d_query_all($sql);
    }

    $selectParts = [];
    $groupParts = [];

    foreach ($groupCols as $name) {
        $selectParts[] = d_ident($name);
        $groupParts[] = d_ident($name);
    }

    $sql = "SELECT " . implode(', ', $selectParts) . ", COUNT(*) AS row_count
            FROM " . d_ident($table) . "
            WHERE " . d_ident($available['userid']) . " = " . (int)$userId . "
            GROUP BY " . implode(', ', $groupParts) . "
            ORDER BY " . implode(', ', $groupParts);

    return d_query_all($sql);
}

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

$isAdmin = isAdmin($_SESSION['userSession'] ?? null);

if (!$isAdmin) {
    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Not Authorized</title></head><body>';
    echo '<div style="max-width:900px;margin:40px auto;padding:20px;background:#2a1111;color:#fff;font-family:Arial,sans-serif;border:1px solid #8c4444;border-radius:12px;">';
    echo '<h1>Not Authorized</h1><p>You are not authorized to view this discovery report.</p></div>';
    echo '</body></html>';
    exit;
}

$dbName = d_db_name();
$dbConnected = ($dbName !== '');

$sourceId = 0;
$targetId = 999;

$users0 = d_safe_user_row($sourceId);
$users999 = d_safe_user_row($targetId);

$users0Count = 0;
$users999Count = 0;

if (d_table_exists('users')) {
    $users0Count = d_scalar_int("SELECT COUNT(*) FROM `users` WHERE `userID` = 0");
    $users999Count = d_scalar_int("SELECT COUNT(*) FROM `users` WHERE `userID` = 999");
}

/*
 * Discover:
 *   A) obvious user-ID columns by name
 *   B) any FK column that references users.userID even if its name is unusual
 */
$namedCandidates = [];
$foreignKeys = [];
$candidateMap = [];

if ($dbConnected) {
    $namedCandidates = d_query_all(
        "SELECT
            TABLE_NAME,
            COLUMN_NAME,
            DATA_TYPE,
            COLUMN_TYPE,
            IS_NULLABLE,
            COLUMN_KEY,
            EXTRA
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND (
               LOWER(COLUMN_NAME) = 'userid'
               OR LOWER(COLUMN_NAME) = 'user_id'
               OR LOWER(COLUMN_NAME) LIKE '%user%id%'
           )
         ORDER BY TABLE_NAME, ORDINAL_POSITION",
        [$dbName]
    );

    $foreignKeys = d_query_all(
        "SELECT
            k.TABLE_NAME,
            k.COLUMN_NAME,
            k.CONSTRAINT_NAME,
            k.REFERENCED_TABLE_NAME,
            k.REFERENCED_COLUMN_NAME,
            r.UPDATE_RULE,
            r.DELETE_RULE
         FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
         LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r
           ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
          AND r.TABLE_NAME = k.TABLE_NAME
          AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
         WHERE k.TABLE_SCHEMA = ?
           AND k.REFERENCED_TABLE_SCHEMA = ?
           AND k.REFERENCED_TABLE_NAME = 'users'
           AND k.REFERENCED_COLUMN_NAME = 'userID'
         ORDER BY k.TABLE_NAME, k.COLUMN_NAME",
        [$dbName, $dbName]
    );

    foreach ($namedCandidates as $row) {
        $table = (string)($row['TABLE_NAME'] ?? '');
        $column = (string)($row['COLUMN_NAME'] ?? '');
        if ($table === '' || $column === '') {
            continue;
        }

        $key = strtolower($table . '|' . $column);
        $candidateMap[$key] = [
            'table' => $table,
            'column' => $column,
            'data_type' => (string)($row['DATA_TYPE'] ?? ''),
            'column_type' => (string)($row['COLUMN_TYPE'] ?? ''),
            'column_key' => (string)($row['COLUMN_KEY'] ?? ''),
            'extra' => (string)($row['EXTRA'] ?? ''),
            'via_name' => true,
            'via_fk' => false,
            'constraint' => '',
            'update_rule' => '',
            'delete_rule' => '',
        ];
    }

    foreach ($foreignKeys as $row) {
        $table = (string)($row['TABLE_NAME'] ?? '');
        $column = (string)($row['COLUMN_NAME'] ?? '');
        if ($table === '' || $column === '') {
            continue;
        }

        $key = strtolower($table . '|' . $column);

        if (!isset($candidateMap[$key])) {
            $candidateMap[$key] = [
                'table' => $table,
                'column' => $column,
                'data_type' => '',
                'column_type' => '',
                'column_key' => '',
                'extra' => '',
                'via_name' => false,
                'via_fk' => true,
                'constraint' => (string)($row['CONSTRAINT_NAME'] ?? ''),
                'update_rule' => (string)($row['UPDATE_RULE'] ?? ''),
                'delete_rule' => (string)($row['DELETE_RULE'] ?? ''),
            ];
        } else {
            $candidateMap[$key]['via_fk'] = true;
            $candidateMap[$key]['constraint'] = (string)($row['CONSTRAINT_NAME'] ?? '');
            $candidateMap[$key]['update_rule'] = (string)($row['UPDATE_RULE'] ?? '');
            $candidateMap[$key]['delete_rule'] = (string)($row['DELETE_RULE'] ?? '');
        }
    }
}

$candidateRows = [];

foreach ($candidateMap as $candidate) {
    $table = (string)$candidate['table'];
    $column = (string)$candidate['column'];

    $count0 = d_scalar_int(
        "SELECT COUNT(*) FROM " . d_ident($table) .
        " WHERE " . d_ident($column) . " = 0"
    );

    $count999 = d_scalar_int(
        "SELECT COUNT(*) FROM " . d_ident($table) .
        " WHERE " . d_ident($column) . " = 999"
    );

    $candidate['count_0'] = $count0;
    $candidate['count_999'] = $count999;
    $candidateRows[] = $candidate;
}

usort($candidateRows, function ($a, $b) {
    $cmp = strcasecmp((string)$a['table'], (string)$b['table']);
    if ($cmp !== 0) {
        return $cmp;
    }
    return strcasecmp((string)$a['column'], (string)$b['column']);
});

$tablesWithZero = 0;
$totalZeroRefs = 0;
$total999Refs = 0;

foreach ($candidateRows as $row) {
    $c0 = (int)($row['count_0'] ?? 0);
    $c999 = (int)($row['count_999'] ?? 0);

    if ($c0 > 0) {
        $tablesWithZero++;
        $totalZeroRefs += $c0;
    }

    if ($c999 > 0) {
        $total999Refs += $c999;
    }
}

$userTeams0 = d_group_summary('user_teams', 0, ['raceYear', 'teamName']);
$userTeams999 = d_group_summary('user_teams', 999, ['raceYear', 'teamName']);

$userPicks0 = d_group_summary('user_picks', 0, ['raceYear', 'segment', 'pick_type', 'teamName']);
$userPicks999 = d_group_summary('user_picks', 999, ['raceYear', 'segment', 'pick_type', 'teamName']);

$usersIdMeta = [];
if ($dbConnected) {
    $usersIdMeta = d_query_all(
        "SELECT
            COLUMN_NAME,
            COLUMN_TYPE,
            IS_NULLABLE,
            COLUMN_KEY,
            EXTRA
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = 'users'
           AND COLUMN_NAME = 'userID'
         LIMIT 1",
        [$dbName]
    );
}

$blocking999 = ($users999Count > 0 || $total999Refs > 0);
$sourceExists = ($users0Count === 1);
$readyForPlanning = ($dbConnected && $sourceExists && !$blocking999);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL userID 0 → 999 Discovery</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
    --bg:#101214;
    --panel:#1d2023;
    --panel2:#17191b;
    --border:#4b5055;
    --text:#f0f0f0;
    --muted:#b8bec5;
    --gold:#efc77e;
    --blue:#55c7ff;
    --green:#63e69a;
    --red:#ff7e7e;
    --amber:#ffd479;
}
*{box-sizing:border-box}
html{background:var(--bg)}
body{
    margin:0;
    color:var(--text);
    font-family:Tahoma,Verdana,"Segoe UI",Arial,sans-serif;
    font-size:15px;
}
.wrap{
    width:96%;
    max-width:1500px;
    margin:18px auto 60px;
}
.card{
    background:var(--panel);
    border:1px solid var(--border);
    border-radius:14px;
    padding:18px 20px;
    margin:0 0 16px;
}
h1,h2,h3{color:var(--gold);margin-top:0}
h1{font-size:28px}
h2{font-size:21px}
h3{font-size:17px}
.banner{
    padding:12px 15px;
    border-radius:10px;
    margin:12px 0;
    font-weight:700;
}
.banner.readonly{
    background:#123a2a;
    border:1px solid #2b815b;
    color:#d9ffea;
}
.banner.warn{
    background:#4a3810;
    border:1px solid #9e7926;
    color:#fff0b6;
}
.banner.bad{
    background:#4a1818;
    border:1px solid #a64e4e;
    color:#ffd4d4;
}
.ok{color:var(--green);font-weight:700}
.bad{color:var(--red);font-weight:700}
.warntext{color:var(--amber);font-weight:700}
.muted{color:var(--muted)}
code,.mono{font-family:Consolas,"Courier New",monospace}
code{color:var(--blue)}
table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}
th,td{
    border-bottom:1px solid #3a3e42;
    padding:8px 9px;
    text-align:left;
    vertical-align:top;
}
th{
    color:#ffe0a0;
    background:var(--panel2);
    position:sticky;
    top:0;
}
.num{text-align:right}
.center{text-align:center}
.small{font-size:13px}
.grid2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}
@media(max-width:950px){
    .grid2{grid-template-columns:1fr}
}
.kv{
    display:grid;
    grid-template-columns:minmax(210px,320px) 1fr;
    gap:6px 16px;
    align-items:start;
}
.kv div{padding:4px 0;border-bottom:1px dotted #34383c}
.summary{
    display:flex;
    gap:18px;
    flex-wrap:wrap;
}
.summary .metric{
    min-width:190px;
    padding:12px 14px;
    background:var(--panel2);
    border:1px solid #45494d;
    border-radius:10px;
}
.metric .value{font-size:24px;font-weight:800;color:#fff}
.metric .label{font-size:12px;color:var(--muted);margin-top:3px}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
    <h1>MRL userID 0 → 999 Discovery</h1>
    <div class="banner readonly">READ-ONLY DISCOVERY — this page performs SELECT / INFORMATION_SCHEMA queries only. It does not change the database.</div>

    <div class="kv">
        <div>Generated</div><div>8/30/2026 6:47:16 am America/New_York</div>
        <div>Connected database</div><div class="<?php echo $dbConnected ? 'ok' : 'bad'; ?>"><?php echo d_h($dbName !== '' ? $dbName : 'NOT CONNECTED'); ?></div>
        <div>Source account</div><div><code>userID 0</code></div>
        <div>Target account</div><div><code>userID 999</code></div>
        <div>Overall discovery status</div>
        <div class="<?php echo $readyForPlanning ? 'ok' : 'warntext'; ?>">
            <?php echo $readyForPlanning ? 'READY FOR MIGRATION PLANNING' : 'REVIEW REQUIRED BEFORE MIGRATION PLANNING'; ?>
        </div>
    </div>
</div>

<div class="card">
    <h2>Collision / Identity Check</h2>
    <div class="summary">
        <div class="metric">
            <div class="value"><?php echo (int)$users0Count; ?></div>
            <div class="label">rows in users with userID 0</div>
        </div>
        <div class="metric">
            <div class="value"><?php echo (int)$users999Count; ?></div>
            <div class="label">rows in users with userID 999</div>
        </div>
        <div class="metric">
            <div class="value"><?php echo (int)$totalZeroRefs; ?></div>
            <div class="label">user-ID references containing 0</div>
        </div>
        <div class="metric">
            <div class="value"><?php echo (int)$total999Refs; ?></div>
            <div class="label">user-ID references containing 999</div>
        </div>
    </div>

    <?php if ($users0Count !== 1): ?>
        <div class="banner bad">Expected exactly one users.userID = 0 row, but found <?php echo (int)$users0Count; ?>.</div>
    <?php endif; ?>

    <?php if ($blocking999): ?>
        <div class="banner bad">userID 999 is already present somewhere in the discovered database references. Do not migrate until reviewed.</div>
    <?php else: ?>
        <div class="banner readonly">No discovered userID 999 collision was found.</div>
    <?php endif; ?>
</div>

<div class="grid2">
    <div class="card">
        <h2>users row — userID 0</h2>
        <?php if (empty($users0)): ?>
            <p class="bad">No safe users row found for userID 0.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Column</th><th>Value</th></tr></thead>
                <tbody>
                <?php foreach ($users0 as $k => $v): ?>
                    <tr><td class="mono"><?php echo d_h($k); ?></td><td><?php echo d_h($v); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>users row — userID 999</h2>
        <?php if (empty($users999)): ?>
            <p class="ok">No users row exists for userID 999.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Column</th><th>Value</th></tr></thead>
                <tbody>
                <?php foreach ($users999 as $k => $v): ?>
                    <tr><td class="mono"><?php echo d_h($k); ?></td><td><?php echo d_h($v); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>users.userID Column Definition</h2>
    <?php if (empty($usersIdMeta)): ?>
        <p class="bad">Could not read users.userID metadata.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Column</th><th>Type</th><th>Nullable</th><th>Key</th><th>Extra</th></tr></thead>
            <tbody>
            <?php foreach ($usersIdMeta as $row): ?>
                <tr>
                    <td class="mono"><?php echo d_h($row['COLUMN_NAME'] ?? ''); ?></td>
                    <td class="mono"><?php echo d_h($row['COLUMN_TYPE'] ?? ''); ?></td>
                    <td><?php echo d_h($row['IS_NULLABLE'] ?? ''); ?></td>
                    <td><?php echo d_h($row['COLUMN_KEY'] ?? ''); ?></td>
                    <td><?php echo d_h($row['EXTRA'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Discovered User-ID Columns</h2>
    <p class="muted">Includes columns whose names look like user IDs plus any foreign-key columns that explicitly reference <code>users.userID</code>.</p>

    <?php if (empty($candidateRows)): ?>
        <p class="bad">No candidate user-ID columns were discovered.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Column</th>
                    <th>Type</th>
                    <th class="center">Named Match</th>
                    <th class="center">FK → users.userID</th>
                    <th>Constraint</th>
                    <th>Update Rule</th>
                    <th>Delete Rule</th>
                    <th class="num">Rows = 0</th>
                    <th class="num">Rows = 999</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($candidateRows as $row): ?>
                <?php
                    $c0 = (int)($row['count_0'] ?? 0);
                    $c999 = (int)($row['count_999'] ?? 0);
                ?>
                <tr>
                    <td class="mono"><?php echo d_h($row['table'] ?? ''); ?></td>
                    <td class="mono"><?php echo d_h($row['column'] ?? ''); ?></td>
                    <td class="mono"><?php echo d_h($row['column_type'] ?? $row['data_type'] ?? ''); ?></td>
                    <td class="center"><?php echo !empty($row['via_name']) ? 'Yes' : ''; ?></td>
                    <td class="center"><?php echo !empty($row['via_fk']) ? 'Yes' : ''; ?></td>
                    <td class="mono"><?php echo d_h($row['constraint'] ?? ''); ?></td>
                    <td><?php echo d_h($row['update_rule'] ?? ''); ?></td>
                    <td><?php echo d_h($row['delete_rule'] ?? ''); ?></td>
                    <td class="num <?php echo $c0 > 0 ? 'warntext' : ''; ?>"><?php echo $c0; ?></td>
                    <td class="num <?php echo $c999 > 0 ? 'bad' : ''; ?>"><?php echo $c999; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Foreign Keys Referencing users.userID</h2>
    <?php if (empty($foreignKeys)): ?>
        <p class="warntext">No explicit foreign keys reference users.userID. Legacy relationships may therefore be enforced only by application code.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Column</th>
                    <th>Constraint</th>
                    <th>Referenced</th>
                    <th>Update Rule</th>
                    <th>Delete Rule</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($foreignKeys as $row): ?>
                <tr>
                    <td class="mono"><?php echo d_h($row['TABLE_NAME'] ?? ''); ?></td>
                    <td class="mono"><?php echo d_h($row['COLUMN_NAME'] ?? ''); ?></td>
                    <td class="mono"><?php echo d_h($row['CONSTRAINT_NAME'] ?? ''); ?></td>
                    <td class="mono"><?php echo d_h(($row['REFERENCED_TABLE_NAME'] ?? '') . '.' . ($row['REFERENCED_COLUMN_NAME'] ?? '')); ?></td>
                    <td><?php echo d_h($row['UPDATE_RULE'] ?? ''); ?></td>
                    <td><?php echo d_h($row['DELETE_RULE'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="grid2">
    <div class="card">
        <h2>user_teams — userID 0</h2>
        <?php if (empty($userTeams0)): ?>
            <p class="muted">No user_teams rows found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($userTeams0[0]) as $k): ?><th><?php echo d_h($k); ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userTeams0 as $row): ?>
                        <tr><?php foreach ($row as $v): ?><td><?php echo d_h($v); ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>user_teams — userID 999</h2>
        <?php if (empty($userTeams999)): ?>
            <p class="ok">No user_teams rows found for 999.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($userTeams999[0]) as $k): ?><th><?php echo d_h($k); ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userTeams999 as $row): ?>
                        <tr><?php foreach ($row as $v): ?><td><?php echo d_h($v); ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="grid2">
    <div class="card">
        <h2>user_picks — userID 0</h2>
        <?php if (empty($userPicks0)): ?>
            <p class="muted">No user_picks rows found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($userPicks0[0]) as $k): ?><th><?php echo d_h($k); ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userPicks0 as $row): ?>
                        <tr><?php foreach ($row as $v): ?><td><?php echo d_h($v); ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>user_picks — userID 999</h2>
        <?php if (empty($userPicks999)): ?>
            <p class="ok">No user_picks rows found for 999.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($userPicks999[0]) as $k): ?><th><?php echo d_h($k); ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userPicks999 as $row): ?>
                        <tr><?php foreach ($row as $v): ?><td><?php echo d_h($v); ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Discovery Conclusion</h2>
    <?php if ($readyForPlanning): ?>
        <div class="banner readonly">
            Database discovery found one source users row, no discovered 999 collision, and has mapped the likely user-ID references.
            This does NOT authorize the migration yet; the next step is the separate PHP/codebase scan.
        </div>
    <?php else: ?>
        <div class="banner warn">
            One or more database conditions require review before migration planning. Do not modify userID values yet.
        </div>
    <?php endif; ?>

    <p class="muted small">
        Next planned step after reviewing this report: scan the MRL PHP/codebase for legacy userID 0 assumptions, userSession checks,
        alternate-user filtering, MRL test-team handling, and any hard-coded ID logic.
    </p>
</div>

</div>
</body>
</html>
