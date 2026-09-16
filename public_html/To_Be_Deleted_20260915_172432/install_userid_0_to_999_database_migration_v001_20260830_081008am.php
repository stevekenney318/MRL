<?php
declare(strict_types=1);

/**
 * install_userid_0_to_999_database_migration.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/30/2026 8:10:08 am
 *
 * PURPOSE:
 *   Step 4 of the controlled MRL test-account migration:
 *       users.userID 0 -> 999
 *
 * EXPECTED SOURCE STATE (from Step 1 discovery):
 *   users                  1 row at userID 0
 *   Financial              8 rows at userID 0
 *   picks                 12 rows at userID 0
 *   picks_history         23 rows at userID 0
 *   user_picks            12 rows at userID 0
 *   user_picks_history    23 rows at userID 0
 *   user_teams             8 rows at userID 0
 *
 *   Total references including users row: 87
 *   Expected userID 999 rows before migration: 0 everywhere above.
 *
 * MIGRATION METHOD:
 *   1. Re-run exact preflight immediately before Apply.
 *   2. Require InnoDB transaction support for every affected table.
 *   3. Copy the users row to explicit userID 999, preserving every other column.
 *   4. Verify the copied users row is identical except for userID.
 *   5. Update dependent tables from 0 -> 999.
 *   6. Verify exact row counts before deleting userID 0.
 *   7. Delete the old users.userID 0 row.
 *   8. Verify zero remaining 0 references and exact expected 999 counts.
 *   9. COMMIT only if every check passes; otherwise ROLLBACK.
 *
 * THIS INSTALLER DOES NOT MODIFY APPLICATION FILES.
 *
 * NOTE:
 *   A database backup should already exist before Apply.
 *
 * PHP: 7.3+
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';

function m_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function m_ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function m_query_all(mysqli $db, string $sql): array
{
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

function m_scalar(mysqli $db, string $sql): int
{
    $rows = m_query_all($db, $sql);
    if (!$rows) {
        return 0;
    }
    $first = $rows[0];
    return (int)reset($first);
}

function m_database_name(mysqli $db): string
{
    $rows = m_query_all($db, "SELECT DATABASE() AS db");
    return (string)($rows[0]['db'] ?? '');
}

function m_table_engine(mysqli $db, string $schema, string $table): string
{
    $schemaEsc = mysqli_real_escape_string($db, $schema);
    $tableEsc  = mysqli_real_escape_string($db, $table);

    $rows = m_query_all(
        $db,
        "SELECT ENGINE
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA='{$schemaEsc}'
           AND TABLE_NAME='{$tableEsc}'
         LIMIT 1"
    );

    return strtoupper((string)($rows[0]['ENGINE'] ?? ''));
}

function m_table_has_column(mysqli $db, string $schema, string $table, string $column): bool
{
    $schemaEsc = mysqli_real_escape_string($db, $schema);
    $tableEsc  = mysqli_real_escape_string($db, $table);
    $columnEsc = mysqli_real_escape_string($db, $column);

    return m_scalar(
        $db,
        "SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA='{$schemaEsc}'
           AND TABLE_NAME='{$tableEsc}'
           AND COLUMN_NAME='{$columnEsc}'"
    ) === 1;
}

function m_user_columns(mysqli $db, string $schema): array
{
    $schemaEsc = mysqli_real_escape_string($db, $schema);

    $rows = m_query_all(
        $db,
        "SELECT COLUMN_NAME, ORDINAL_POSITION
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA='{$schemaEsc}'
           AND TABLE_NAME='users'
         ORDER BY ORDINAL_POSITION"
    );

    $cols = [];
    foreach ($rows as $row) {
        $name = (string)($row['COLUMN_NAME'] ?? '');
        if ($name !== '') {
            $cols[] = $name;
        }
    }
    return $cols;
}

function m_fetch_user(mysqli $db, int $uid): ?array
{
    $res = mysqli_query($db, "SELECT * FROM `users` WHERE `userID`=" . (int)$uid . " LIMIT 1");
    if ($res === false) {
        throw new RuntimeException(mysqli_error($db));
    }
    $row = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $row ?: null;
}

function m_user_payload_hash(array $row): string
{
    unset($row['userID']);
    ksort($row);
    return hash('sha256', json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function m_state(mysqli $db, array $tables, int $uid): array
{
    $out = [];
    foreach ($tables as $table => $expected) {
        $out[$table] = m_scalar(
            $db,
            "SELECT COUNT(*) FROM " . m_ident($table) . " WHERE `userID`=" . (int)$uid
        );
    }
    return $out;
}

function m_total(array $state): int
{
    return array_sum(array_map('intval', $state));
}

function m_exact_match(array $state, array $expected): bool
{
    foreach ($expected as $table => $count) {
        if (!array_key_exists($table, $state) || (int)$state[$table] !== (int)$count) {
            return false;
        }
    }
    return true;
}

function m_zero_state(array $state): bool
{
    foreach ($state as $count) {
        if ((int)$count !== 0) {
            return false;
        }
    }
    return true;
}

function m_export_json(array $payload): void
{
    $name = 'MRL_userID_0_to_999_migration_report_' . date('Ymd_His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

if (!isAdmin($_SESSION['userSession'] ?? null)) {
    http_response_code(403);
    echo '<!doctype html><html><body style="background:#111;color:#fff;font-family:Arial,sans-serif">';
    echo '<div style="max-width:900px;margin:40px auto;padding:20px;border:1px solid #844;background:#2a1111;border-radius:12px">';
    echo '<h1>Not Authorized</h1><p>Admin access is required.</p></div></body></html>';
    exit;
}

if (!isset($dbconnect) || !($dbconnect instanceof mysqli)) {
    throw new RuntimeException('mysqli connection $dbconnect is unavailable.');
}

$sourceId = 0;
$targetId = 999;

$expected = [
    'users' => 1,
    'Financial' => 8,
    'picks' => 12,
    'picks_history' => 23,
    'user_picks' => 12,
    'user_picks_history' => 23,
    'user_teams' => 8,
];

$dependentTables = [
    'Financial',
    'picks',
    'picks_history',
    'user_picks',
    'user_picks_history',
    'user_teams',
];

$schema = m_database_name($dbconnect);

$tableChecks = [];
$allTablesGood = true;

foreach ($expected as $table => $count) {
    $hasUserId = m_table_has_column($dbconnect, $schema, $table, 'userID');
    $engine = m_table_engine($dbconnect, $schema, $table);
    $engineOk = ($engine === 'INNODB');

    $tableChecks[$table] = [
        'has_userid' => $hasUserId,
        'engine' => $engine,
        'engine_ok' => $engineOk,
    ];

    if (!$hasUserId || !$engineOk) {
        $allTablesGood = false;
    }
}

$before0 = m_state($dbconnect, $expected, $sourceId);
$before999 = m_state($dbconnect, $expected, $targetId);

$sourceUser = m_fetch_user($dbconnect, $sourceId);
$targetUser = m_fetch_user($dbconnect, $targetId);

$sourceExact = m_exact_match($before0, $expected);
$targetEmpty = m_zero_state($before999);

$alreadyExpected999 = $expected;
$alreadyExpected999['users'] = 1;
$alreadyMigrated = (
    m_zero_state($before0)
    && m_exact_match($before999, $alreadyExpected999)
    && $targetUser !== null
);

$preflightReady = (
    !$alreadyMigrated
    && $allTablesGood
    && $sourceExact
    && $targetEmpty
    && $sourceUser !== null
    && $targetUser === null
);

$mode = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply')
    ? 'apply'
    : 'preview';

$applyAttempted = ($mode === 'apply');
$applySuccess = false;
$applyError = '';
$steps = [];
$after0 = $before0;
$after999 = $before999;
$userCopyHashMatch = false;

if ($applyAttempted) {
    if (!$preflightReady) {
        $applyError = 'Apply blocked because the live database does not match the exact approved preflight state.';
    } else {
        mysqli_begin_transaction($dbconnect);

        try {
            // Re-check exact state inside the transaction.
            $tx0 = m_state($dbconnect, $expected, $sourceId);
            $tx999 = m_state($dbconnect, $expected, $targetId);

            if (!m_exact_match($tx0, $expected)) {
                throw new RuntimeException('Transaction preflight failed: userID 0 counts changed.');
            }
            if (!m_zero_state($tx999)) {
                throw new RuntimeException('Transaction preflight failed: userID 999 is no longer unused.');
            }

            $txSourceUser = m_fetch_user($dbconnect, $sourceId);
            if ($txSourceUser === null) {
                throw new RuntimeException('Transaction preflight failed: users.userID 0 row missing.');
            }

            // Copy full users row, changing only the primary key.
            $columns = m_user_columns($dbconnect, $schema);
            if (!$columns || !in_array('userID', $columns, true)) {
                throw new RuntimeException('Could not determine users table columns.');
            }

            $insertCols = [];
            $selectExpr = [];

            foreach ($columns as $col) {
                $insertCols[] = m_ident($col);
                if ($col === 'userID') {
                    $selectExpr[] = (string)$targetId . ' AS `userID`';
                } else {
                    $selectExpr[] = m_ident($col);
                }
            }

            $copySql =
                "INSERT INTO `users` (" . implode(', ', $insertCols) . ")
                 SELECT " . implode(', ', $selectExpr) . "
                 FROM `users`
                 WHERE `userID`=" . $sourceId;

            if (!mysqli_query($dbconnect, $copySql)) {
                throw new RuntimeException('Copy users row failed: ' . mysqli_error($dbconnect));
            }

            if (mysqli_affected_rows($dbconnect) !== 1) {
                throw new RuntimeException('Expected to insert exactly 1 users row at 999.');
            }

            $newUser = m_fetch_user($dbconnect, $targetId);
            if ($newUser === null) {
                throw new RuntimeException('Copied users.userID 999 row could not be read back.');
            }

            $sourceHash = m_user_payload_hash($txSourceUser);
            $targetHash = m_user_payload_hash($newUser);
            $userCopyHashMatch = hash_equals($sourceHash, $targetHash);

            if (!$userCopyHashMatch) {
                throw new RuntimeException('Copied users row differs from source in one or more non-ID fields.');
            }

            $steps[] = [
                'step' => 'Copy users row 0 -> 999',
                'affected' => 1,
                'status' => 'PASS',
            ];

            // Move each dependent table.
            foreach ($dependentTables as $table) {
                $expectedRows = (int)$expected[$table];

                $sql =
                    "UPDATE " . m_ident($table) .
                    " SET `userID`=" . $targetId .
                    " WHERE `userID`=" . $sourceId;

                if (!mysqli_query($dbconnect, $sql)) {
                    throw new RuntimeException(
                        'Update failed for ' . $table . ': ' . mysqli_error($dbconnect)
                    );
                }

                $affected = mysqli_affected_rows($dbconnect);
                if ($affected !== $expectedRows) {
                    throw new RuntimeException(
                        $table . ': expected ' . $expectedRows .
                        ' updated row(s), got ' . $affected . '.'
                    );
                }

                $steps[] = [
                    'step' => $table . ' userID 0 -> 999',
                    'affected' => $affected,
                    'status' => 'PASS',
                ];
            }

            // Validate all dependent rows moved before deleting source users row.
            $mid0 = m_state($dbconnect, $expected, $sourceId);
            $mid999 = m_state($dbconnect, $expected, $targetId);

            if ((int)$mid0['users'] !== 1) {
                throw new RuntimeException('Source users row changed before final delete.');
            }

            foreach ($dependentTables as $table) {
                if ((int)$mid0[$table] !== 0) {
                    throw new RuntimeException($table . ' still has userID 0 rows.');
                }
                if ((int)$mid999[$table] !== (int)$expected[$table]) {
                    throw new RuntimeException($table . ' userID 999 row count is incorrect.');
                }
            }

            if ((int)$mid999['users'] !== 1) {
                throw new RuntimeException('Expected exactly one users.userID 999 row before source delete.');
            }

            // Delete old identity only after all children have moved.
            if (!mysqli_query($dbconnect, "DELETE FROM `users` WHERE `userID`=0")) {
                throw new RuntimeException('Deleting users.userID 0 failed: ' . mysqli_error($dbconnect));
            }

            if (mysqli_affected_rows($dbconnect) !== 1) {
                throw new RuntimeException('Expected to delete exactly one users.userID 0 row.');
            }

            $steps[] = [
                'step' => 'Delete old users.userID 0 identity',
                'affected' => 1,
                'status' => 'PASS',
            ];

            // Final exact verification before COMMIT.
            $after0 = m_state($dbconnect, $expected, $sourceId);
            $after999 = m_state($dbconnect, $expected, $targetId);

            if (!m_zero_state($after0)) {
                throw new RuntimeException('Final verification failed: userID 0 still exists.');
            }

            if (!m_exact_match($after999, $expected)) {
                throw new RuntimeException('Final verification failed: userID 999 counts do not match source counts.');
            }

            $finalUser = m_fetch_user($dbconnect, $targetId);
            if ($finalUser === null) {
                throw new RuntimeException('Final verification failed: users.userID 999 missing.');
            }

            if (!hash_equals(m_user_payload_hash($txSourceUser), m_user_payload_hash($finalUser))) {
                throw new RuntimeException('Final users.userID 999 payload check failed.');
            }

            mysqli_commit($dbconnect);
            $applySuccess = true;

        } catch (Throwable $e) {
            mysqli_rollback($dbconnect);
            $applyError = $e->getMessage();

            // Refresh state after rollback.
            $after0 = m_state($dbconnect, $expected, $sourceId);
            $after999 = m_state($dbconnect, $expected, $targetId);
        }
    }
}

$current0 = m_state($dbconnect, $expected, $sourceId);
$current999 = m_state($dbconnect, $expected, $targetId);
$currentUser0 = m_fetch_user($dbconnect, $sourceId);
$currentUser999 = m_fetch_user($dbconnect, $targetId);

$postMigrated = (
    m_zero_state($current0)
    && m_exact_match($current999, $expected)
    && $currentUser0 === null
    && $currentUser999 !== null
);

$report = [
    'report_version' => 'v001',
    'generated_at' => date('Y-m-d H:i:s'),
    'timezone' => 'America/New_York',
    'database' => $schema,
    'migration' => 'userID 0 -> 999',
    'mode' => $mode,
    'preflight_ready' => $preflightReady,
    'already_migrated_on_load' => $alreadyMigrated,
    'apply_attempted' => $applyAttempted,
    'apply_success' => $applySuccess,
    'post_migrated' => $postMigrated,
    'error' => $applyError,
    'expected_counts' => $expected,
    'current_userid_0_counts' => $current0,
    'current_userid_999_counts' => $current999,
    'table_checks' => $tableChecks,
    'steps' => $steps,
];

if (isset($_GET['export']) && $_GET['export'] === 'json') {
    m_export_json($report);
}

function m_status_class(bool $ok): string
{
    return $ok ? 'ok' : 'bad';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL userID 0 → 999 Database Migration</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
    --bg:#101214;--panel:#1d2023;--panel2:#17191b;--border:#4b5055;
    --text:#f0f0f0;--muted:#b8bec5;--gold:#efc77e;--blue:#55c7ff;
    --green:#63e69a;--green2:#23a85a;--red:#ff7e7e;--amber:#ffd479;
}
*{box-sizing:border-box}
html{background:var(--bg)}
body{margin:0;color:var(--text);font-family:Tahoma,Verdana,"Segoe UI",Arial,sans-serif;font-size:14px}
.wrap{width:97%;max-width:1500px;margin:18px auto 60px}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}
h1{font-size:28px}h2{font-size:21px}
.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:700}
.banner.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}
.banner.warn{background:#4a3810;border:1px solid #9e7926;color:#fff0b6}
.banner.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}
.ok{color:var(--green);font-weight:700}
.bad{color:var(--red);font-weight:700}
.warntext{color:var(--amber);font-weight:700}
.muted{color:var(--muted)}
code,.mono{font-family:Consolas,"Courier New",monospace}
code{color:var(--blue)}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border-bottom:1px solid #3a3e42;padding:8px 9px;text-align:left;vertical-align:top}
th{color:#ffe0a0;background:var(--panel2)}
.num{text-align:right}
.btn{
    display:inline-block;padding:11px 18px;border-radius:8px;font-weight:800;
    text-decoration:none;cursor:pointer;font-size:15px;
}
.btn-apply{
    border:1px solid #4be388;
    background:#16894b;
    color:#fff;
}
.btn-apply:hover{background:#1da95d}
.btn-export{
    border:1px solid #54b9ef;
    background:#176fa4;
    color:#fff;
}
.btn-export:hover{background:#2188c4}
.actions{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.small{font-size:12px}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
    <h1>MRL userID 0 → 999 — Step 4 Database Migration</h1>
    <p><strong>Installer:</strong> v001 &nbsp; | &nbsp; <strong>Generated:</strong> 8/30/2026 8:10:08 am America/New_York</p>
    <p><strong>Database:</strong> <code><?php echo m_h($schema); ?></code></p>

    <?php if ($applyAttempted && $applySuccess): ?>
        <div class="banner ok">MIGRATION COMPLETE — transaction committed and every postflight check passed.</div>
    <?php elseif ($applyAttempted && !$applySuccess): ?>
        <div class="banner bad">MIGRATION NOT APPLIED — <?php echo m_h($applyError); ?></div>
    <?php elseif ($alreadyMigrated || $postMigrated): ?>
        <div class="banner ok">DATABASE ALREADY SHOWS THE COMPLETE 0 → 999 MIGRATION STATE.</div>
    <?php elseif ($preflightReady): ?>
        <div class="banner ok">PREVIEW PASS — exact source counts, empty target ID, table structure, and transaction requirements all passed.</div>
    <?php else: ?>
        <div class="banner bad">PREVIEW BLOCKED — database state differs from the approved Step 1 discovery.</div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Exact Row Count Matrix</h2>
    <table>
        <thead>
        <tr>
            <th>Table</th>
            <th class="num">Expected at 0 Before</th>
            <th class="num">Current at 0</th>
            <th class="num">Current at 999</th>
            <th>Engine</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($expected as $table => $count): ?>
            <?php
                $c0 = (int)($current0[$table] ?? 0);
                $c999 = (int)($current999[$table] ?? 0);
                $engineOk = !empty($tableChecks[$table]['engine_ok']);
                $rowOkBefore = (!$postMigrated && $c0 === (int)$count && $c999 === 0);
                $rowOkAfter = ($postMigrated && $c0 === 0 && $c999 === (int)$count);
                $rowOk = $rowOkBefore || $rowOkAfter;
            ?>
            <tr>
                <td><code><?php echo m_h($table); ?></code></td>
                <td class="num"><?php echo (int)$count; ?></td>
                <td class="num"><?php echo $c0; ?></td>
                <td class="num"><?php echo $c999; ?></td>
                <td class="<?php echo m_status_class($engineOk); ?>">
                    <?php echo m_h($tableChecks[$table]['engine'] ?? ''); ?>
                </td>
                <td class="<?php echo m_status_class($rowOk && $engineOk); ?>">
                    <?php echo ($rowOk && $engineOk) ? 'PASS' : 'CHECK'; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Identity Check</h2>
    <table>
        <thead><tr><th>Check</th><th>Result</th></tr></thead>
        <tbody>
        <tr>
            <td>users.userID 0</td>
            <td class="<?php echo m_status_class($currentUser0 !== null ? !$postMigrated : $postMigrated); ?>">
                <?php echo $currentUser0 !== null ? 'Present' : 'Not present'; ?>
            </td>
        </tr>
        <tr>
            <td>users.userID 999</td>
            <td class="<?php echo m_status_class($currentUser999 !== null ? $postMigrated : !$postMigrated); ?>">
                <?php echo $currentUser999 !== null ? 'Present' : 'Not present'; ?>
            </td>
        </tr>
        <?php if ($applyAttempted): ?>
        <tr>
            <td>Copied users-row payload identical except userID</td>
            <td class="<?php echo m_status_class($userCopyHashMatch || $applySuccess); ?>">
                <?php echo ($userCopyHashMatch || $applySuccess) ? 'PASS' : 'Not completed'; ?>
            </td>
        </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($steps)): ?>
<div class="card">
    <h2>Migration Steps</h2>
    <table>
        <thead><tr><th>Step</th><th class="num">Rows</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($steps as $step): ?>
            <tr>
                <td><?php echo m_h($step['step']); ?></td>
                <td class="num"><?php echo (int)$step['affected']; ?></td>
                <td class="ok"><?php echo m_h($step['status']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <h2>Actions</h2>
    <div class="actions">
        <?php if ($preflightReady && !$applyAttempted): ?>
            <form method="post" onsubmit="return confirm('Migrate the MRL test account and all 86 dependent rows from userID 0 to 999 now?');">
                <input type="hidden" name="action" value="apply">
                <button class="btn btn-apply" type="submit">Apply Database Migration 0 → 999</button>
            </form>
        <?php elseif (!$postMigrated && !$applyAttempted): ?>
            <span class="bad">Apply is unavailable until every preflight check passes.</span>
        <?php endif; ?>

        <a class="btn btn-export" href="?export=json">Export Migration Report (JSON)</a>
    </div>
</div>

<?php if ($postMigrated): ?>
<div class="card">
    <h2>Postflight</h2>
    <div class="banner ok">
        userID 0 is gone from every approved migration table. userID 999 now contains the exact expected row counts,
        including the copied users identity.
    </div>
    <p>
        After this page is confirmed, the practical functional checks are:
        log in / View As the MRL test account, open Team/Profile, make a test pick when appropriate,
        and confirm Weekly Standings still excludes the MRL test team.
    </p>
</div>
<?php endif; ?>

</div>
</body>
</html>
