<?php
declare(strict_types=1);

/**
 * install_userid_0_to_999_database_migration.php
 *
 * VERSION: v004
 * LAST MODIFIED: 8/30/2026 8:38:37 am
 *
 * PURPOSE:
 *   Step 4 of the controlled MRL test-account migration:
 *       users.userID 0 -> 999
 *
 * IMPORTANT DISCOVERY:
 *   Financial, picks, and picks_history are SQL VIEWS, not physical tables.
 *   They are derived from:
 *     Financial      -> users + user_teams
 *     picks          -> users + user_picks
 *     picks_history  -> users + user_picks_history
 *
 *   Therefore this migration updates ONLY the physical tables:
 *     users                  1 source row
 *     user_teams             8 rows
 *     user_picks            12 rows
 *     user_picks_history    23 rows
 *
 *   Total physical rows affected: 44.
 *
 *   The three views are verified before and after migration but are never
 *   directly updated.
 *
 * MIGRATION METHOD:
 *   1. Exact preflight for physical tables and derived views.
 *   2. Require InnoDB on every physical table being changed.
 *   3. Begin one transaction.
 *   4. Temporarily change userID 0 email to mrl@google.com to free the UNIQUE Gmail key.
 *   5. Copy users.userID 0 -> explicit userID 999, restoring manliusracingleague@gmail.com on 999.
 *   6. Verify copied user payload matches the original userID 0 payload except for userID.
 *   7. Update user_picks, user_picks_history, and user_teams from 0 -> 999.
 *   8. Verify exact physical row counts before source-user deletion.
 *   9. Delete users.userID 0 (and therefore the temporary mrl@google.com identity).
 *  10. Verify physical tables AND the three views.
 *  11. COMMIT only if every check passes; otherwise ROLLBACK.
 *
 * THIS INSTALLER DOES NOT MODIFY APPLICATION FILES.
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
    if (!$rows) return 0;
    $first = $rows[0];
    return (int)reset($first);
}

function m_database_name(mysqli $db): string
{
    $rows = m_query_all($db, "SELECT DATABASE() AS db");
    return (string)($rows[0]['db'] ?? '');
}

function m_object_meta(mysqli $db, string $schema, string $name): array
{
    $s = mysqli_real_escape_string($db, $schema);
    $n = mysqli_real_escape_string($db, $name);

    $rows = m_query_all(
        $db,
        "SELECT TABLE_NAME, TABLE_TYPE, ENGINE
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA='{$s}'
           AND TABLE_NAME='{$n}'
         LIMIT 1"
    );

    return $rows[0] ?? [];
}

function m_view_meta(mysqli $db, string $schema, string $name): array
{
    $s = mysqli_real_escape_string($db, $schema);
    $n = mysqli_real_escape_string($db, $name);

    $rows = m_query_all(
        $db,
        "SELECT TABLE_NAME, IS_UPDATABLE
         FROM INFORMATION_SCHEMA.VIEWS
         WHERE TABLE_SCHEMA='{$s}'
           AND TABLE_NAME='{$n}'
         LIMIT 1"
    );

    return $rows[0] ?? [];
}

function m_user_columns(mysqli $db, string $schema): array
{
    $s = mysqli_real_escape_string($db, $schema);

    $rows = m_query_all(
        $db,
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA='{$s}'
           AND TABLE_NAME='users'
         ORDER BY ORDINAL_POSITION"
    );

    $cols = [];
    foreach ($rows as $row) {
        $name = (string)($row['COLUMN_NAME'] ?? '');
        if ($name !== '') $cols[] = $name;
    }
    return $cols;
}

function m_user_on_update_columns(mysqli $db, string $schema): array
{
    $s = mysqli_real_escape_string($db, $schema);

    $rows = m_query_all(
        $db,
        "SELECT COLUMN_NAME, EXTRA
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA='{$s}'
           AND TABLE_NAME='users'
         ORDER BY ORDINAL_POSITION"
    );

    $cols = [];
    foreach ($rows as $row) {
        $name = (string)($row['COLUMN_NAME'] ?? '');
        $extra = strtolower((string)($row['EXTRA'] ?? ''));
        if ($name !== '' && strpos($extra, 'on update') !== false) {
            $cols[] = $name;
        }
    }
    return $cols;
}

function m_user_diff(array $a, array $b): array
{
    unset($a['userID'], $b['userID']);
    $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
    sort($keys);

    $diffs = [];
    foreach ($keys as $key) {
        $av = array_key_exists($key, $a) ? (string)$a[$key] : '__MISSING__';
        $bv = array_key_exists($key, $b) ? (string)$b[$key] : '__MISSING__';
        if ($av !== $bv) {
            $diffs[$key] = ['source' => $av, 'target' => $bv];
        }
    }
    return $diffs;
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

function m_counts(mysqli $db, array $objects, int $uid): array
{
    $out = [];
    foreach ($objects as $name => $expected) {
        $out[$name] = m_scalar(
            $db,
            "SELECT COUNT(*) FROM " . m_ident($name) . " WHERE `userID`=" . (int)$uid
        );
    }
    return $out;
}

function m_exact(array $actual, array $expected): bool
{
    foreach ($expected as $name => $count) {
        if (!array_key_exists($name, $actual) || (int)$actual[$name] !== (int)$count) {
            return false;
        }
    }
    return true;
}

function m_all_zero(array $actual): bool
{
    foreach ($actual as $count) {
        if ((int)$count !== 0) return false;
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
    exit('Not authorized.');
}

if (!isset($dbconnect) || !($dbconnect instanceof mysqli)) {
    throw new RuntimeException('mysqli connection $dbconnect is unavailable.');
}

$sourceId = 0;
$targetId = 999;
$sourceOriginalEmail = 'manliusracingleague@gmail.com';
$sourceTemporaryEmail = 'mrl@google.com';
$schema = m_database_name($dbconnect);

/*
 * PHYSICAL tables to migrate.
 * These are the real stored rows.
 */
$physicalExpected = [
    'users' => 1,
    'user_teams' => 8,
    'user_picks' => 12,
    'user_picks_history' => 23,
];

/*
 * DERIVED views to verify only.
 * Their counts come from the physical rows above.
 */
$viewExpected = [
    'Financial' => 8,
    'picks' => 12,
    'picks_history' => 23,
];

$physicalChecks = [];
$physicalReady = true;

foreach ($physicalExpected as $table => $count) {
    $meta = m_object_meta($dbconnect, $schema, $table);
    $type = strtoupper((string)($meta['TABLE_TYPE'] ?? ''));
    $engine = strtoupper((string)($meta['ENGINE'] ?? ''));

    $ok = ($type === 'BASE TABLE' && $engine === 'INNODB');

    $physicalChecks[$table] = [
        'type' => $type,
        'engine' => $engine,
        'ok' => $ok,
    ];

    if (!$ok) $physicalReady = false;
}

$viewChecks = [];
$viewsReady = true;

foreach ($viewExpected as $view => $count) {
    $meta = m_object_meta($dbconnect, $schema, $view);
    $vmeta = m_view_meta($dbconnect, $schema, $view);

    $type = strtoupper((string)($meta['TABLE_TYPE'] ?? ''));
    $isUpdatable = strtoupper((string)($vmeta['IS_UPDATABLE'] ?? ''));

    $ok = ($type === 'VIEW');

    $viewChecks[$view] = [
        'type' => $type,
        'is_updatable' => $isUpdatable,
        'ok' => $ok,
    ];

    if (!$ok) $viewsReady = false;
}

$beforePhysical0 = m_counts($dbconnect, $physicalExpected, $sourceId);
$beforePhysical999 = m_counts($dbconnect, $physicalExpected, $targetId);
$beforeViews0 = m_counts($dbconnect, $viewExpected, $sourceId);
$beforeViews999 = m_counts($dbconnect, $viewExpected, $targetId);

$sourceUser = m_fetch_user($dbconnect, $sourceId);
$targetUser = m_fetch_user($dbconnect, $targetId);

$sourceEmailMatches = (
    $sourceUser !== null
    && strcasecmp((string)($sourceUser['userEmail'] ?? ''), $sourceOriginalEmail) === 0
);

$tempEmailCount = m_scalar(
    $dbconnect,
    "SELECT COUNT(*) FROM `users` WHERE LOWER(`userEmail`) = LOWER('" .
    mysqli_real_escape_string($dbconnect, $sourceTemporaryEmail) . "')"
);

$tempEmailAvailable = ($tempEmailCount === 0);

$sourceExact =
    m_exact($beforePhysical0, $physicalExpected)
    && m_exact($beforeViews0, $viewExpected);

$targetEmpty =
    m_all_zero($beforePhysical999)
    && m_all_zero($beforeViews999);

$alreadyMigrated =
    m_all_zero($beforePhysical0)
    && m_all_zero($beforeViews0)
    && m_exact($beforePhysical999, $physicalExpected)
    && m_exact($beforeViews999, $viewExpected)
    && $sourceUser === null
    && $targetUser !== null;

$preflightReady =
    !$alreadyMigrated
    && $physicalReady
    && $viewsReady
    && $sourceExact
    && $targetEmpty
    && $sourceUser !== null
    && $targetUser === null
    && $sourceEmailMatches
    && $tempEmailAvailable;

$mode = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply')
    ? 'apply'
    : 'preview';

$applyAttempted = ($mode === 'apply');
$applySuccess = false;
$applyError = '';
$steps = [];
$userCopyHashMatch = false;

if ($applyAttempted) {
    if (!$preflightReady) {
        $applyError = 'Apply blocked because the live database does not match the approved preflight state.';
    } else {
        mysqli_begin_transaction($dbconnect);

        try {
            /*
             * Repeat exact checks inside the transaction.
             */
            $txPhysical0 = m_counts($dbconnect, $physicalExpected, 0);
            $txPhysical999 = m_counts($dbconnect, $physicalExpected, 999);
            $txViews0 = m_counts($dbconnect, $viewExpected, 0);
            $txViews999 = m_counts($dbconnect, $viewExpected, 999);

            if (!m_exact($txPhysical0, $physicalExpected) || !m_exact($txViews0, $viewExpected)) {
                throw new RuntimeException('Transaction preflight failed: source counts changed.');
            }

            if (!m_all_zero($txPhysical999) || !m_all_zero($txViews999)) {
                throw new RuntimeException('Transaction preflight failed: userID 999 is no longer unused.');
            }

            $txSourceUser = m_fetch_user($dbconnect, 0);
            if ($txSourceUser === null) {
                throw new RuntimeException('users.userID 0 disappeared before migration.');
            }

            if (strcasecmp((string)($txSourceUser['userEmail'] ?? ''), $sourceOriginalEmail) !== 0) {
                throw new RuntimeException('Source user email is not the expected MRL Gmail address.');
            }

            $txTempEmailCount = m_scalar(
                $dbconnect,
                "SELECT COUNT(*) FROM `users` WHERE LOWER(`userEmail`) = LOWER('" .
                mysqli_real_escape_string($dbconnect, $sourceTemporaryEmail) . "')"
            );

            if ($txTempEmailCount !== 0) {
                throw new RuntimeException('Temporary email mrl@google.com is already in use.');
            }

            /*
             * The users.userEmail column is UNIQUE. Temporarily free the MRL Gmail
             * address on userID 0 so userID 999 can retain it.
             */
            $tempEsc = mysqli_real_escape_string($dbconnect, $sourceTemporaryEmail);

            /*
             * Preserve any automatic ON UPDATE columns (for example a timestamp)
             * by explicitly assigning each such column to itself in the same UPDATE.
             * This prevents the temporary email change from altering any other field.
             */
            $onUpdateColumns = m_user_on_update_columns($dbconnect, $schema);
            $setParts = ["`userEmail`='{$tempEsc}'"];
            foreach ($onUpdateColumns as $col) {
                if ($col === 'userEmail' || $col === 'userID') {
                    continue;
                }
                $setParts[] = m_ident($col) . '=' . m_ident($col);
            }

            $tempUpdateSql =
                "UPDATE `users` SET " . implode(', ', $setParts) . " WHERE `userID`=0 LIMIT 1";

            if (!mysqli_query($dbconnect, $tempUpdateSql)) {
                throw new RuntimeException('Temporary source-email change failed: ' . mysqli_error($dbconnect));
            }

            if (mysqli_affected_rows($dbconnect) !== 1) {
                throw new RuntimeException('Expected exactly one temporary source-email change.');
            }

            /*
             * Confirm that the temporary email change did not alter any other source field.
             */
            $postTempSource = m_fetch_user($dbconnect, 0);
            if ($postTempSource === null) {
                throw new RuntimeException('Source user disappeared after temporary email change.');
            }

            $normalizedPostTemp = $postTempSource;
            $normalizedPostTemp['userEmail'] = $sourceOriginalEmail;

            $tempDiffs = m_user_diff($txSourceUser, $normalizedPostTemp);
            if (!empty($tempDiffs)) {
                throw new RuntimeException(
                    'Temporary email change altered other users fields: ' .
                    implode(', ', array_keys($tempDiffs))
                );
            }

            $steps[] = [
                'step' => 'Temporarily change userID 0 email to mrl@google.com',
                'rows' => 1,
                'status' => 'PASS',
            ];

            /*
             * Copy users row to 999. Every field remains identical to the ORIGINAL
             * userID 0 row except the primary key. userEmail is explicitly restored
             * to manliusracingleague@gmail.com on the new 999 row.
             */
            $columns = m_user_columns($dbconnect, $schema);
            if (!$columns || !in_array('userID', $columns, true)) {
                throw new RuntimeException('Could not determine users columns.');
            }

            $insertCols = [];
            $selectExpr = [];

            $originalEmailEsc = mysqli_real_escape_string($dbconnect, $sourceOriginalEmail);

            foreach ($columns as $col) {
                $insertCols[] = m_ident($col);

                if ($col === 'userID') {
                    $selectExpr[] = '999 AS `userID`';
                } elseif ($col === 'userEmail') {
                    $selectExpr[] = "'" . $originalEmailEsc . "' AS `userEmail`";
                } else {
                    $selectExpr[] = m_ident($col);
                }
            }

            $sql =
                "INSERT INTO `users` (" . implode(', ', $insertCols) . ")
                 SELECT " . implode(', ', $selectExpr) . "
                 FROM `users`
                 WHERE `userID`=0";

            if (!mysqli_query($dbconnect, $sql)) {
                throw new RuntimeException('Copying users row failed: ' . mysqli_error($dbconnect));
            }

            if (mysqli_affected_rows($dbconnect) !== 1) {
                throw new RuntimeException('Expected exactly one new users.userID 999 row.');
            }

            $copiedUser = m_fetch_user($dbconnect, 999);
            if ($copiedUser === null) {
                throw new RuntimeException('Copied users.userID 999 row could not be read back.');
            }

            $userCopyHashMatch = hash_equals(
                m_user_payload_hash($txSourceUser),
                m_user_payload_hash($copiedUser)
            );

            if (!$userCopyHashMatch) {
                $copyDiffs = m_user_diff($txSourceUser, $copiedUser);
                throw new RuntimeException(
                    'Copied users row differs in field(s): ' .
                    implode(', ', array_keys($copyDiffs))
                );
            }

            $steps[] = ['step' => 'Copy users row 0 → 999', 'rows' => 1, 'status' => 'PASS'];

            /*
             * Update children that may restrict parent-key changes first.
             */
            $moveTables = [
                'user_picks' => 12,
                'user_picks_history' => 23,
                'user_teams' => 8,
            ];

            foreach ($moveTables as $table => $expectedRows) {
                $sql = "UPDATE " . m_ident($table) . " SET `userID`=999 WHERE `userID`=0";

                if (!mysqli_query($dbconnect, $sql)) {
                    throw new RuntimeException(
                        $table . ' update failed: ' . mysqli_error($dbconnect)
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
                    'step' => $table . ' userID 0 → 999',
                    'rows' => $affected,
                    'status' => 'PASS',
                ];
            }

            /*
             * Verify physical children before deleting source user.
             * Do NOT inspect view zero-counts here because their LEFT JOIN
             * can temporarily expose one source-user row with null child data.
             */
            $midPhysical0 = m_counts($dbconnect, $physicalExpected, 0);
            $midPhysical999 = m_counts($dbconnect, $physicalExpected, 999);

            if ((int)$midPhysical0['users'] !== 1) {
                throw new RuntimeException('Source users row changed before final delete.');
            }

            foreach (['user_teams', 'user_picks', 'user_picks_history'] as $table) {
                if ((int)$midPhysical0[$table] !== 0) {
                    throw new RuntimeException($table . ' still contains userID 0.');
                }
                if ((int)$midPhysical999[$table] !== (int)$physicalExpected[$table]) {
                    throw new RuntimeException($table . ' has an incorrect userID 999 count.');
                }
            }

            if ((int)$midPhysical999['users'] !== 1) {
                throw new RuntimeException('Expected exactly one users.userID 999 row.');
            }

            /*
             * Remove old identity only after every physical child row moved.
             */
            if (!mysqli_query($dbconnect, "DELETE FROM `users` WHERE `userID`=0")) {
                throw new RuntimeException('Deleting users.userID 0 failed: ' . mysqli_error($dbconnect));
            }

            if (mysqli_affected_rows($dbconnect) !== 1) {
                throw new RuntimeException('Expected exactly one deleted users.userID 0 row.');
            }

            $steps[] = ['step' => 'Delete old users.userID 0', 'rows' => 1, 'status' => 'PASS'];

            /*
             * Final physical + derived-view checks before COMMIT.
             */
            $finalPhysical0 = m_counts($dbconnect, $physicalExpected, 0);
            $finalPhysical999 = m_counts($dbconnect, $physicalExpected, 999);
            $finalViews0 = m_counts($dbconnect, $viewExpected, 0);
            $finalViews999 = m_counts($dbconnect, $viewExpected, 999);

            if (!m_all_zero($finalPhysical0) || !m_all_zero($finalViews0)) {
                throw new RuntimeException('Final verification failed: userID 0 still appears.');
            }

            if (!m_exact($finalPhysical999, $physicalExpected)) {
                throw new RuntimeException('Final physical-table counts at userID 999 are incorrect.');
            }

            if (!m_exact($finalViews999, $viewExpected)) {
                throw new RuntimeException('Final derived-view counts at userID 999 are incorrect.');
            }

            $finalUser = m_fetch_user($dbconnect, 999);
            if ($finalUser === null) {
                throw new RuntimeException('Final users.userID 999 row is missing.');
            }

            if (!hash_equals(m_user_payload_hash($txSourceUser), m_user_payload_hash($finalUser))) {
                throw new RuntimeException('Final users row payload verification failed.');
            }

            mysqli_commit($dbconnect);
            $applySuccess = true;

        } catch (Throwable $e) {
            mysqli_rollback($dbconnect);
            $applyError = $e->getMessage();
        }
    }
}

/*
 * Refresh current state after preview/apply/rollback.
 */
$currentPhysical0 = m_counts($dbconnect, $physicalExpected, 0);
$currentPhysical999 = m_counts($dbconnect, $physicalExpected, 999);
$currentViews0 = m_counts($dbconnect, $viewExpected, 0);
$currentViews999 = m_counts($dbconnect, $viewExpected, 999);
$currentUser0 = m_fetch_user($dbconnect, 0);
$currentUser999 = m_fetch_user($dbconnect, 999);

$postMigrated =
    m_all_zero($currentPhysical0)
    && m_all_zero($currentViews0)
    && m_exact($currentPhysical999, $physicalExpected)
    && m_exact($currentViews999, $viewExpected)
    && $currentUser0 === null
    && $currentUser999 !== null;

$report = [
    'report_version' => 'v004',
    'generated_at' => date('Y-m-d H:i:s'),
    'timezone' => 'America/New_York',
    'database' => $schema,
    'migration' => 'userID 0 -> 999',
    'mode' => $mode,
    'physical_rows_to_change' => 44,
    'physical_expected' => $physicalExpected,
    'view_expected' => $viewExpected,
    'preflight_ready' => $preflightReady,
    'source_expected_email' => $sourceOriginalEmail,
    'source_email_matches' => $sourceEmailMatches,
    'temporary_email' => $sourceTemporaryEmail,
    'temporary_email_available' => $tempEmailAvailable,
    'apply_attempted' => $applyAttempted,
    'apply_success' => $applySuccess,
    'post_migrated' => $postMigrated,
    'error' => $applyError,
    'physical_checks' => $physicalChecks,
    'view_checks' => $viewChecks,
    'current_physical_0' => $currentPhysical0,
    'current_physical_999' => $currentPhysical999,
    'current_views_0' => $currentViews0,
    'current_views_999' => $currentViews999,
    'steps' => $steps,
];

if (isset($_GET['export']) && $_GET['export'] === 'json') {
    m_export_json($report);
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
    --green:#63e69a;--green2:#16894b;--red:#ff7e7e;--amber:#ffd479;
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
.banner.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}
.ok{color:var(--green);font-weight:700}
.bad{color:var(--red);font-weight:700}
.muted{color:var(--muted)}
code,.mono{font-family:Consolas,"Courier New",monospace}
code{color:var(--blue)}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border-bottom:1px solid #3a3e42;padding:8px 9px;text-align:left;vertical-align:top}
th{color:#ffe0a0;background:var(--panel2)}
.num{text-align:right}
.btn{display:inline-block;padding:11px 18px;border-radius:8px;font-weight:800;text-decoration:none;cursor:pointer;font-size:15px}
.btn-apply{border:1px solid #4be388;background:#16894b;color:#fff}
.btn-apply:hover{background:#1da95d}
.btn-export{border:1px solid #54b9ef;background:#176fa4;color:#fff}
.btn-export:hover{background:#2188c4}
.actions{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.small{font-size:12px}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>MRL userID 0 → 999 — Step 4 Database Migration</h1>
<p><strong>Installer:</strong> v004 &nbsp; | &nbsp; <strong>Generated:</strong> 8/30/2026 8:38:37 am America/New_York</p>
<p><strong>Database:</strong> <code><?php echo m_h($schema); ?></code></p>

<?php if ($applyAttempted && $applySuccess): ?>
<div class="banner ok">MIGRATION COMPLETE — transaction committed and all physical-table and view checks passed.</div>
<?php elseif ($applyAttempted && !$applySuccess): ?>
<div class="banner bad">MIGRATION NOT APPLIED — <?php echo m_h($applyError); ?></div>
<?php elseif ($postMigrated): ?>
<div class="banner ok">DATABASE ALREADY SHOWS THE COMPLETE 0 → 999 MIGRATION STATE.</div>
<?php elseif ($preflightReady): ?>
<div class="banner ok">PREVIEW PASS — physical tables, derived views, exact counts, target ID, and transaction requirements all passed.</div>
<?php else: ?>
<div class="banner bad">PREVIEW BLOCKED — one or more required checks failed.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>Physical Tables — These Will Change</h2>
<table>
<thead><tr><th>Table</th><th class="num">Expected at 0</th><th class="num">Current 0</th><th class="num">Current 999</th><th>Type</th><th>Engine</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($physicalExpected as $name => $expectedCount): ?>
<?php
$c0=(int)$currentPhysical0[$name];
$c999=(int)$currentPhysical999[$name];
$structureOk=!empty($physicalChecks[$name]['ok']);
$countOk=(!$postMigrated && $c0===(int)$expectedCount && $c999===0) || ($postMigrated && $c0===0 && $c999===(int)$expectedCount);
?>
<tr>
<td><code><?php echo m_h($name); ?></code></td>
<td class="num"><?php echo (int)$expectedCount; ?></td>
<td class="num"><?php echo $c0; ?></td>
<td class="num"><?php echo $c999; ?></td>
<td><?php echo m_h($physicalChecks[$name]['type']); ?></td>
<td><?php echo m_h($physicalChecks[$name]['engine']); ?></td>
<td class="<?php echo ($structureOk && $countOk) ? 'ok':'bad'; ?>"><?php echo ($structureOk && $countOk) ? 'PASS':'CHECK'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p><strong>Total physical rows involved:</strong> 44</p>
</div>

<div class="card">
<h2>Derived Views — Verification Only</h2>
<table>
<thead><tr><th>View</th><th class="num">Expected at 0</th><th class="num">Current 0</th><th class="num">Current 999</th><th>Type</th><th>Updatable</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($viewExpected as $name => $expectedCount): ?>
<?php
$c0=(int)$currentViews0[$name];
$c999=(int)$currentViews999[$name];
$structureOk=!empty($viewChecks[$name]['ok']);
$countOk=(!$postMigrated && $c0===(int)$expectedCount && $c999===0) || ($postMigrated && $c0===0 && $c999===(int)$expectedCount);
?>
<tr>
<td><code><?php echo m_h($name); ?></code></td>
<td class="num"><?php echo (int)$expectedCount; ?></td>
<td class="num"><?php echo $c0; ?></td>
<td class="num"><?php echo $c999; ?></td>
<td><?php echo m_h($viewChecks[$name]['type']); ?></td>
<td><?php echo m_h($viewChecks[$name]['is_updatable']); ?></td>
<td class="<?php echo ($structureOk && $countOk) ? 'ok':'bad'; ?>"><?php echo ($structureOk && $countOk) ? 'PASS':'CHECK'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p class="muted">These views are never updated directly. They should follow the physical-table migration automatically.</p>
</div>

<?php if (!empty($steps)): ?>
<div class="card">
<h2>Migration Steps</h2>
<table>
<thead><tr><th>Step</th><th class="num">Rows</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($steps as $step): ?>
<tr><td><?php echo m_h($step['step']); ?></td><td class="num"><?php echo (int)$step['rows']; ?></td><td class="ok"><?php echo m_h($step['status']); ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<div class="card">
<h2>Email Uniqueness Check</h2>
<table>
<thead><tr><th>Check</th><th>Result</th></tr></thead>
<tbody>
<tr>
<td>userID 0 currently owns <code>manliusracingleague@gmail.com</code></td>
<td class="<?php echo $sourceEmailMatches ? 'ok':'bad'; ?>"><?php echo $sourceEmailMatches ? 'PASS':'CHECK'; ?></td>
</tr>
<tr>
<td>Temporary address <code>mrl@google.com</code> is unused</td>
<td class="<?php echo $tempEmailAvailable ? 'ok':'bad'; ?>"><?php echo $tempEmailAvailable ? 'PASS':'CHECK'; ?></td>
</tr>
</tbody>
</table>
<p class="muted">During Apply, userID 0 temporarily becomes mrl@google.com only long enough to free the UNIQUE Gmail key. Any automatic ON UPDATE fields in users are explicitly preserved. userID 999 receives manliusracingleague@gmail.com, then userID 0 is deleted before commit.</p>
</div>

<div class="card">
<h2>Actions</h2>
<div class="actions">
<?php if ($preflightReady && !$applyAttempted): ?>
<form method="post" onsubmit="return confirm('Migrate the MRL test account from userID 0 to 999 now? This will change 44 physical database rows inside one transaction and use mrl@google.com temporarily to free the unique Gmail address.');">
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
userID 0 is gone from all physical migration tables and all three derived views.
userID 999 now has the exact expected physical and derived counts.
</div>
<p>Next: re-login if necessary, then verify MRL test account through Team, Profile, View As, and the pick flow.</p>
</div>
<?php endif; ?>

</div>
</body>
</html>
