<?php
declare(strict_types=1);

/*
    filename: admin_db_backup.php
    purpose : Admin DB Backup + Restore (Full database)
              - Create SQL backup file (structure + optional data)
              - Restore from a chosen SQL file
              - Strip DEFINER during restore
              - Dry-run mode for restore (parses + counts statements without executing)
    php     : 7.3+
*/

/*
    ============================================================
    VERSION HISTORY
    ============================================================
    v1.0  (2026-02-21)  Initial full Admin DB Backup + Restore tool.
                       - Full structure + data export
                       - Optional views
                       - DROP support
                       - Restore with DELIMITER support
                       - DEFINER stripping option
                       - Foreign key toggle
                       - Dry-run mode

    v1.1  (2026-02-21)  Adjusted defaults for stable revert workflow.
                       - Include data ON by default
                       - Include views ON
                       - DROP statements ON
                       - Strip DEFINER ON during restore
                       - Disable FK checks ON during restore
                       - Simplified filename format:
                         MRL_DB_YYYYMMDD_HHMMSS.sql

    v1.2  (2026-02-21)  Restore parsing fix:
                       - Robust DELIMITER detection (even if indented)
                       - Proper delimiter reset handling (DELIMITER ;)
                       - Prevents “combined statements” that trigger syntax errors

    v1.3  (2026-02-21)  Restore correctness + UX fixes:
                       - Preserve explicit AUTO_INCREMENT id=0 rows during restore
                         (NO_AUTO_VALUE_ON_ZERO)
                       - PRG pattern (POST → redirect → GET) to prevent resubmission
                         and prevent dry-run button "sticking"

    v1.4  (2026-02-21)  changed time zone +00:00 to SYSTEM

    v1.5  (8/28/2026 11:21:21 pm)  Safer backup file manager.
                       - Active / Archive / To Be Deleted areas
                       - Radio = single-file Restore / Rename target
                       - Checkbox = multi-file management target
                       - Archive / move back / quarantine operations
                       - Rename preserves .sql and refuses overwrites
                       - 30-day delete quarantine; manual final delete only
                       - Restore directly from Active or Archive
                       - Permanent delete only from To Be Deleted after 30 days

    v1.6  (8/29/2026 6:20:08 am)  Rename + selection convenience cleanup.
                       - FIX: Rename uses same selected radio as Restore
                       - UI: Radio selection auto-fills rename field without .sql
                       - UI: Select All / Clear for each checkbox section
                       - PRESERVE: v1.5 archive/quarantine/restore/delete safeguards

    v1.7  (8/29/2026 7:00:54 am)  Simplified delete quarantine behavior.
                       - CHANGE: To Be Deleted is now a simple manual holding area
                       - CHANGE: Removed 30-day eligibility requirement
                       - CHANGE: Moving files no longer changes file modified timestamps
                       - CHANGE: Permanent delete uses explicit confirmation only
                       - PRESERVE: Active / Archive / To Be Deleted organization, restore, rename,
                                   Select All / Clear, overwrite protection, and DB backup engine
    ============================================================
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';

echo "<div style='padding:8px;color:#fff;background:#333;'>Connected DB: " . h(dbName()) . "</div>";

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}

$isAdmin = isAdmin($_SESSION['userSession'] ?? null);

$adminStatusLine = $isAdmin
    ? '<div class="admin-status admin-yes">You are authorized to view/use this page</div>'
    : '<div class="admin-status admin-no">You are NOT authorized to view/use this page</div>';

if (!$isAdmin) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Not Authorized</title></head><body>';
    echo $adminStatusLine;
    echo '</body></html>';
    exit;
}

/* ----------------------- config ----------------------- */

// Backups live in a folder right next to this script.
$backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'db_backups';
$archiveDir = $backupDir . DIRECTORY_SEPARATOR . 'Archive';
$deleteDir = $backupDir . DIRECTORY_SEPARATOR . 'To_Be_Deleted';

// Protect against huge dumps accidentally killing PHP
$defaultMaxRuntimeSeconds = 90;

// Insert batch size (rows per INSERT)
$defaultInsertChunkSize = 500;

/* ----------------------- helpers ----------------------- */

function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function ensureBackupDir(string $dir): array {
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            return [false, "Could not create backup directory: $dir"];
        }
    }
    if (!is_writable($dir)) {
        return [false, "Backup directory is not writable: $dir"];
    }
    return [true, ""];
}

function dbOk(): bool {
    global $dbconnect;
    return isset($dbconnect) && $dbconnect && mysqli_ping($dbconnect);
}

function dbName(): string {
    global $dbconnect;
    $db = '';
    if (isset($dbconnect) && $dbconnect) {
        $res = mysqli_query($dbconnect, "SELECT DATABASE() AS db");
        if ($res) {
            $row = mysqli_fetch_assoc($res);
            $db = (string)($row['db'] ?? '');
            mysqli_free_result($res);
        }
    }
    return $db !== '' ? $db : '(unknown_db)';
}

function listBackupFiles(string $dir): array {
    if (!is_dir($dir)) return [];
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql');
    if (!$files) return [];
    usort($files, function($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });
    return $files;
}

function safeFileBase(string $filename): string {
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

function isValidSqlBackupName(string $filename): bool {
    $filename = trim($filename);
    if ($filename === '' || basename($filename) !== $filename) return false;
    if (strpos($filename, '..') !== false) return false;
    if (!preg_match('/^[A-Za-z0-9 _().-]+\.sql$/i', $filename)) return false;
    return true;
}

function normalizeSqlRename(string $requested): string {
    $requested = trim($requested);
    if ($requested === '') return '';

    $requested = preg_replace('/\.sql$/i', '', $requested);
    $requested = trim((string)$requested);

    if ($requested === '' || strpos($requested, '..') !== false) return '';
    if (!preg_match('/^[A-Za-z0-9 _().-]+$/', $requested)) return '';

    return $requested . '.sql';
}

function ensureManagedDir(string $dir): array {
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            return [false, "Could not create managed backup directory: $dir"];
        }
    }
    if (!is_writable($dir)) {
        return [false, "Managed backup directory is not writable: $dir"];
    }
    return [true, ''];
}

function managedAreaDir(string $area, string $backupDir, string $archiveDir, string $deleteDir): string {
    if ($area === 'archive') return $archiveDir;
    if ($area === 'delete') return $deleteDir;
    return $backupDir;
}

function selectedSqlFiles($raw): array {
    if (!is_array($raw)) return [];

    $out = [];
    foreach ($raw as $item) {
        $name = trim((string)$item);
        if (!isValidSqlBackupName($name)) continue;
        if (!in_array($name, $out, true)) $out[] = $name;
    }
    return $out;
}

function destinationNameExists(string $dir, string $filename): bool {
    if (!is_dir($dir)) return false;
    $target = strtolower($filename);
    $items = scandir($dir);
    if (!is_array($items)) return is_file($dir . DIRECTORY_SEPARATOR . $filename);

    foreach ($items as $item) {
        if (strtolower((string)$item) === $target) return true;
    }
    return false;
}

function moveManagedSqlFile(string $srcDir, string $dstDir, string $filename): array {
    if (!isValidSqlBackupName($filename)) return [false, "Invalid SQL backup filename: $filename"];

    $src = $srcDir . DIRECTORY_SEPARATOR . $filename;
    $dst = $dstDir . DIRECTORY_SEPARATOR . $filename;

    if (!is_file($src)) return [false, "File not found: $filename"];
    if (destinationNameExists($dstDir, $filename)) return [false, "Destination already contains: $filename"];

    $ok = @rename($src, $dst);
    if (!$ok) {
        $ok = @copy($src, $dst);
        if ($ok) {
            if (!@unlink($src)) {
                @unlink($dst);
                return [false, "Could not remove source after copying: $filename"];
            }
        }
    }

    if (!$ok) return [false, "Could not move: $filename"];

    return [true, ''];
}

function renameManagedSqlFile(string $dir, string $oldName, string $newRequested): array {
    if (!isValidSqlBackupName($oldName)) return [false, 'Invalid source filename.'];

    $newName = normalizeSqlRename($newRequested);
    if ($newName === '') {
        return [false, 'New filename is invalid. Use letters, numbers, spaces, underscore, dash, periods or parentheses.'];
    }

    $src = $dir . DIRECTORY_SEPARATOR . $oldName;
    $dst = $dir . DIRECTORY_SEPARATOR . $newName;

    if (!is_file($src)) return [false, "Source file not found: $oldName"];
    if (strcasecmp($oldName, $newName) !== 0 && destinationNameExists($dir, $newName)) {
        return [false, "A file named $newName already exists."];
    }
    if ($oldName === $newName) return [true, "Filename already is $newName"];

    if (!@rename($src, $dst)) return [false, "Could not rename $oldName to $newName"];
    return [true, "Renamed $oldName to $newName"];
}

function setTimeLimitIfAllowed(int $seconds): void {
    if ($seconds <= 0) return;
    if (function_exists('set_time_limit')) {
        @set_time_limit($seconds);
    }
}

/* ----------------------- backup helpers ----------------------- */

function sqlLine(string $s = ''): string {
    return $s . "\n";
}

function writeToFile($fh, string $data): void {
    fwrite($fh, $data);
}

function sqlQuoteLiteral(string $s): string {
    global $dbconnect;
    if (isset($dbconnect) && $dbconnect) {
        return "'" . mysqli_real_escape_string($dbconnect, $s) . "'";
    }
    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], $s) . "'";
}

function getTables(): array {
    global $dbconnect;
    $tables = [];
    $res = mysqli_query($dbconnect, "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if ($res) {
        while ($row = mysqli_fetch_row($res)) {
            $tables[] = (string)$row[0];
        }
        mysqli_free_result($res);
    }
    sort($tables, SORT_NATURAL | SORT_FLAG_CASE);
    return $tables;
}

function getViews(): array {
    global $dbconnect;
    $views = [];
    $res = mysqli_query($dbconnect, "SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    if ($res) {
        while ($row = mysqli_fetch_row($res)) {
            $views[] = (string)$row[0];
        }
        mysqli_free_result($res);
    }
    sort($views, SORT_NATURAL | SORT_FLAG_CASE);
    return $views;
}

function showCreate(string $type, string $name): string {
    global $dbconnect;

    $nameEsc = '`' . str_replace('`', '``', $name) . '`';

    if ($type === 'table') {
        $res = mysqli_query($dbconnect, "SHOW CREATE TABLE $nameEsc");
        if (!$res) return '';
        $row = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return (string)($row['Create Table'] ?? '');
    }

    if ($type === 'view') {
        $res = mysqli_query($dbconnect, "SHOW CREATE VIEW $nameEsc");
        if (!$res) return '';
        $row = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return (string)($row['Create View'] ?? '');
    }

    return '';
}

function dumpTableData($fh, string $table, int $chunkSize = 500): array {
    global $dbconnect;

    $tableEsc = '`' . str_replace('`', '``', $table) . '`';

    $resCols = mysqli_query($dbconnect, "SHOW COLUMNS FROM $tableEsc");
    if (!$resCols) return [false, "SHOW COLUMNS failed for $table"];

    $cols = [];
    while ($r = mysqli_fetch_assoc($resCols)) {
        $cols[] = (string)$r['Field'];
    }
    mysqli_free_result($resCols);

    if (count($cols) === 0) return [true, ""];

    $colList = implode(',', array_map(function($c){
        return '`' . str_replace('`','``',$c) . '`';
    }, $cols));

    $resCount = mysqli_query($dbconnect, "SELECT COUNT(*) AS c FROM $tableEsc");
    $total = 0;
    if ($resCount) {
        $row = mysqli_fetch_assoc($resCount);
        $total = (int)($row['c'] ?? 0);
        mysqli_free_result($resCount);
    }

    if ($total === 0) {
        return [true, ""];
    }

    writeToFile($fh, sqlLine());
    writeToFile($fh, sqlLine("--"));
    writeToFile($fh, sqlLine("-- DATA FOR: $table"));
    writeToFile($fh, sqlLine("--"));
    writeToFile($fh, sqlLine());

    $offset = 0;
    while ($offset < $total) {
        $sql = "SELECT * FROM $tableEsc LIMIT $offset, $chunkSize";
        $res = mysqli_query($dbconnect, $sql);
        if (!$res) {
            return [false, "SELECT failed for $table (offset $offset)"];
        }

        $values = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $parts = [];
            foreach ($cols as $c) {
                if (!array_key_exists($c, $row) || $row[$c] === null) {
                    $parts[] = "NULL";
                } else {
                    $parts[] = sqlQuoteLiteral((string)$row[$c]);
                }
            }
            $values[] = "(" . implode(",", $parts) . ")";
        }
        mysqli_free_result($res);

        if (count($values) > 0) {
            $ins = "INSERT INTO $tableEsc ($colList) VALUES\n" . implode(",\n", $values) . ";\n";
            writeToFile($fh, $ins);
        }

        $offset += $chunkSize;
    }

    return [true, ""];
}

/* ----------------------- restore helpers ----------------------- */

function stripDefiner(string $sql): string {
    // Remove DEFINER to avoid permission errors during RESTORE.
    $sql = preg_replace('~\sDEFINER\s*=\s*`[^`]+`\s*@\s*`[^`]+`~i', '', $sql);
    $sql = preg_replace('~\sDEFINER\s*=\s*CURRENT_USER~i', '', $sql);
    $sql = preg_replace("~\sDEFINER\s*=\s*`[^`]+`\s*@\s*'[^']+'~i", '', $sql);
    return $sql;
}

function normalizeSqlForRestore(string $sql, bool $doStripDefiner): string {
    $sql = str_replace("\r\n", "\n", $sql);
    $sql = str_replace("\r", "\n", $sql);

    if ($doStripDefiner) {
        $sql = stripDefiner($sql);
    }

    return $sql;
}

/**
 * Split SQL into executable statements, with DELIMITER support.
 * - Handles indented DELIMITER lines (important!)
 * - Honors quoted strings ('', "", backticks)
 * - Honors -- and # line comments, and slash-asterisk block comments
 */
function splitSqlStatements(string $sql): array {
    $stmts = [];
    $buf = '';

    $len = strlen($sql);
    $i = 0;

    $delimiter = ';';

    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    while ($i < $len) {
        $ch = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        // Line comment
        if ($inLineComment) {
            $buf .= $ch;
            if ($ch === "\n") $inLineComment = false;
            $i++;
            continue;
        }

        // Block comment
        if ($inBlockComment) {
            $buf .= $ch;
            if ($ch === '*' && $next === '/') {
                $buf .= $next;
                $i += 2;
                $inBlockComment = false;
                continue;
            }
            $i++;
            continue;
        }

        // Detect DELIMITER directive (only if not in quotes/comments)
        if (!$inSingle && !$inDouble && !$inBacktick) {
            // Try to detect at line start (ignoring whitespace)
            $lineStart = $i;
            while ($lineStart > 0 && $sql[$lineStart - 1] !== "\n") {
                $lineStart--;
            }
            $prefix = substr($sql, $lineStart, $i - $lineStart);
            if (preg_match('~^\s*$~', $prefix)) {
                if (strncasecmp(substr($sql, $i, 9), 'DELIMITER', 9) === 0) {
                    // Read the whole line
                    $lineEnd = $i;
                    while ($lineEnd < $len && $sql[$lineEnd] !== "\n") $lineEnd++;
                    $line = trim(substr($sql, $i, $lineEnd - $i));

                    $parts = preg_split('~\s+~', $line);
                    if (count($parts) >= 2) {
                        $delimiter = (string)$parts[1];
                    }

                    // IMPORTANT: skip the DELIMITER line entirely (do not add to buffer)
                    $i = ($lineEnd < $len) ? $lineEnd + 1 : $lineEnd;
                    continue;
                }
            }
        }

        // Start of comments (only if not inside quotes)
        if (!$inSingle && !$inDouble && !$inBacktick) {
            // -- comment (must be followed by space/tab/newline)
            if ($ch === '-' && $next === '-') {
                $after = ($i + 2 < $len) ? $sql[$i + 2] : '';
                if ($after === ' ' || $after === "\t" || $after === "\n" || $after === "\r") {
                    $inLineComment = true;
                    $buf .= $ch . $next;
                    $i += 2;
                    continue;
                }
            }
            // # comment
            if ($ch === '#') {
                $inLineComment = true;
                $buf .= $ch;
                $i++;
                continue;
            }
            // /* block comment */
            if ($ch === '/' && $next === '*') {
                $inBlockComment = true;
                $buf .= $ch . $next;
                $i += 2;
                continue;
            }
        }

        // Quote state toggles (handle backslash escaping inside quotes)
        if ($ch === "'" && !$inDouble && !$inBacktick) {
            $prev = ($i > 0) ? $sql[$i - 1] : '';
            if ($prev !== '\\') $inSingle = !$inSingle;
            $buf .= $ch;
            $i++;
            continue;
        }

        if ($ch === '"' && !$inSingle && !$inBacktick) {
            $prev = ($i > 0) ? $sql[$i - 1] : '';
            if ($prev !== '\\') $inDouble = !$inDouble;
            $buf .= $ch;
            $i++;
            continue;
        }

        if ($ch === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
            $buf .= $ch;
            $i++;
            continue;
        }

        // Statement boundary: current delimiter (only if not in quotes/backtick/comments)
        if (!$inSingle && !$inDouble && !$inBacktick) {
            if ($delimiter === ';') {
                if ($ch === ';') {
                    $buf .= ';';
                    $stmt = trim($buf);
                    if ($stmt !== '' && $stmt !== ';') $stmts[] = $stmt;
                    $buf = '';
                    $i++;
                    continue;
                }
            } else {
                // multi-char delimiter like $$
                $dlen = strlen($delimiter);
                if ($dlen > 0 && $i + $dlen <= $len) {
                    if (substr($sql, $i, $dlen) === $delimiter) {
                        $stmt = trim($buf);
                        if ($stmt !== '') $stmts[] = $stmt;
                        $buf = '';
                        $i += $dlen;
                        continue;
                    }
                }
            }
        }

        // default: append char
        $buf .= $ch;
        $i++;
    }

    $tail = trim($buf);
    if ($tail !== '') $stmts[] = $tail;

    return $stmts;
}

function executeStatements(array $stmts, bool $dryRun): array {
    global $dbconnect;

    $count = 0;
    $errors = [];

    foreach ($stmts as $sql) {
        $count++;

        if ($dryRun) continue;

        $ok = mysqli_query($dbconnect, $sql);
        if ($ok === false) {
            $errors[] = "Stmt #$count failed: " . mysqli_error($dbconnect);
            break;
        }
    }

    return [$count, $errors];
}

/* ----------------------- request handling ----------------------- */

setTimeLimitIfAllowed($defaultMaxRuntimeSeconds);

// FLASH (PRG): pull any prior POST results
$messages = [];
$errors = [];
$restoreReport = [
    'file' => '',
    'bytes' => 0,
    'statements' => 0,
    'errors' => [],
];

if (isset($_SESSION['flash_messages']) && is_array($_SESSION['flash_messages'])) {
    $messages = $_SESSION['flash_messages'];
    unset($_SESSION['flash_messages']);
}
if (isset($_SESSION['flash_errors']) && is_array($_SESSION['flash_errors'])) {
    $errors = $_SESSION['flash_errors'];
    unset($_SESSION['flash_errors']);
}
if (isset($_SESSION['flash_restore_report']) && is_array($_SESSION['flash_restore_report'])) {
    $restoreReport = array_merge($restoreReport, $_SESSION['flash_restore_report']);
    unset($_SESSION['flash_restore_report']);
}

if (!dbOk()) {
    $errors[] = "Database connection is not available (check config.php / dbconnect).";
}

[$dirOk, $dirErr] = ensureBackupDir($backupDir);
if (!$dirOk) {
    $errors[] = $dirErr;
}

[$archiveOk, $archiveErr] = ensureManagedDir($archiveDir);
if (!$archiveOk) {
    $errors[] = $archiveErr;
}
[$deleteOk, $deleteErr] = ensureManagedDir($deleteDir);
if (!$deleteOk) {
    $errors[] = $deleteErr;
}

$action = (string)($_POST['action'] ?? '');

// DEFAULTS (stable revert workflow)
$includeData  = isset($_POST['include_data'])  ? 1 : 1;
$includeViews = isset($_POST['include_views']) ? 1 : 1;
$addDrops     = isset($_POST['add_drops'])     ? 1 : 1;
$disableFK    = isset($_POST['disable_fk'])    ? 1 : 0;

$restoreDryRun       = isset($_POST['restore_dryrun']) ? 1 : 0;
$restoreStripDefiner = isset($_POST['restore_strip_definer']) ? 1 : 1;
$restoreDisableFK    = isset($_POST['restore_disable_fk']) ? 1 : 1;
$restoreFile         = safeFileBase((string)($_POST['restore_file'] ?? ''));
$restoreArea         = (string)($_POST['restore_area'] ?? 'active');
if (!in_array($restoreArea, ['active', 'archive'], true)) $restoreArea = 'active';
$renameTo           = trim((string)($_POST['rename_to'] ?? ''));

$createdFile = '';$didPostAction = false;

$managementActions = [
    'archive_selected',
    'trash_selected',
    'unarchive_selected',
    'archive_to_trash_selected',
    'trash_restore_active_selected',
    'permanent_delete_selected',
    'rename_file',
];

if (in_array($action, $managementActions, true) && count($errors) === 0) {
    $didPostAction = true;

    if ($action === 'archive_selected' || $action === 'trash_selected') {
        $selected = selectedSqlFiles($_POST['selected_files'] ?? []);
        if (count($selected) === 0) {
            $errors[] = 'No backup files selected.';
        } else {
            foreach ($selected as $name) {
                $destination = $action === 'archive_selected' ? $archiveDir : $deleteDir;
                [$ok, $msg] = moveManagedSqlFile($backupDir, $destination, $name);
                if ($ok) {
                    $messages[] = ($action === 'archive_selected' ? 'Archived: ' : 'Moved to To Be Deleted: ') . $name;
                } else {
                    $errors[] = $msg;
                }
            }
        }
    }

    if ($action === 'unarchive_selected' || $action === 'archive_to_trash_selected') {
        $selected = selectedSqlFiles($_POST['selected_files'] ?? []);
        if (count($selected) === 0) {
            $errors[] = 'No archive files selected.';
        } else {
            foreach ($selected as $name) {
                $destination = $action === 'unarchive_selected' ? $backupDir : $deleteDir;
                [$ok, $msg] = moveManagedSqlFile($archiveDir, $destination, $name);
                if ($ok) {
                    $messages[] = ($action === 'unarchive_selected' ? 'Moved back to Active: ' : 'Moved Archive file to To Be Deleted: ') . $name;
                } else {
                    $errors[] = $msg;
                }
            }
        }
    }

    if ($action === 'trash_restore_active_selected') {
        $selected = selectedSqlFiles($_POST['selected_files'] ?? []);
        if (count($selected) === 0) {
            $errors[] = 'No To Be Deleted files selected.';
        } else {
            foreach ($selected as $name) {
                [$ok, $msg] = moveManagedSqlFile($deleteDir, $backupDir, $name);
                if ($ok) {
                    $messages[] = 'Restored to Active: ' . $name;
                } else {
                    $errors[] = $msg;
                }
            }
        }
    }

    if ($action === 'permanent_delete_selected') {
        $selected = selectedSqlFiles($_POST['selected_files'] ?? []);
        if (count($selected) === 0) {
            $errors[] = 'No To Be Deleted files selected.';
        } else {
            foreach ($selected as $name) {
                $path = $deleteDir . DIRECTORY_SEPARATOR . $name;
                if (!is_file($path)) {
                    $errors[] = 'File not found: ' . $name;
                    continue;
                }

                if (@unlink($path)) {
                    $messages[] = 'Permanently deleted: ' . $name;
                } else {
                    $errors[] = 'Could not permanently delete: ' . $name;
                }
            }
        }
    }

    if ($action === 'rename_file') {
        $area = (string)($_POST['rename_area'] ?? 'active');
        if (!in_array($area, ['active', 'archive'], true)) $area = 'active';

        $oldName = trim((string)($_POST['restore_file'] ?? ''));
        $dir = $area === 'archive' ? $archiveDir : $backupDir;

        if (!isValidSqlBackupName($oldName)) {
            $errors[] = 'Select one file with the radio button before renaming.';
        } else {
            [$ok, $msg] = renameManagedSqlFile($dir, $oldName, $renameTo);
            if ($ok) $messages[] = $msg;
            else $errors[] = $msg;
        }
    }
}
if ($action === 'create_backup' && count($errors) === 0) {
    $didPostAction = true;

    $db = dbName();

    // Requested filename format:
    // MRL_DB_YYYYMMDD_HHMMSS.sql
    $filename = 'MRL_DB_' . date('Ymd_His') . '.sql';
    $path = $backupDir . DIRECTORY_SEPARATOR . $filename;

    $fh = @fopen($path, 'wb');
    if (!$fh) {
        $errors[] = "Could not open for writing: " . $path;
    } else {

        // Force export session timezone so TIMESTAMP values are dumped correctly
        @mysqli_query($dbconnect, "SET time_zone = 'SYSTEM';");

        writeToFile($fh, sqlLine("-- ============================================================"));
        writeToFile($fh, sqlLine("-- MRL DB Backup"));
        writeToFile($fh, sqlLine("-- Database: " . $db));
        writeToFile($fh, sqlLine("-- Generated: " . date('Y-m-d H:i:s')));
        writeToFile($fh, sqlLine("-- Include data: " . ($includeData ? 'YES' : 'NO')));
        writeToFile($fh, sqlLine("-- Include views: " . ($includeViews ? 'YES' : 'NO')));
        writeToFile($fh, sqlLine("-- Add DROP statements: " . ($addDrops ? 'YES' : 'NO')));
        writeToFile($fh, sqlLine("-- ============================================================"));
        writeToFile($fh, sqlLine());
        writeToFile($fh, sqlLine("SET NAMES utf8mb4;"));
        writeToFile($fh, sqlLine("SET time_zone = 'SYSTEM';"));
        if ($disableFK) {
            writeToFile($fh, sqlLine("SET FOREIGN_KEY_CHECKS=0;"));
        }
        writeToFile($fh, sqlLine());

        // TABLES
        $tables = getTables();
        foreach ($tables as $t) {
            $create = showCreate('table', $t);
            if ($create === '') {
                $errors[] = "SHOW CREATE TABLE failed for: $t";
                break;
            }

            writeToFile($fh, sqlLine("--"));
            writeToFile($fh, sqlLine("-- STRUCTURE FOR: $t"));
            writeToFile($fh, sqlLine("--"));
            writeToFile($fh, sqlLine());

            if ($addDrops) {
                $tEsc = '`' . str_replace('`','``',$t) . '`';
                writeToFile($fh, sqlLine("DROP TABLE IF EXISTS $tEsc;"));
            }

            writeToFile($fh, $create . ";\n");

            if ($includeData) {
                [$ok, $msg] = dumpTableData($fh, $t, $defaultInsertChunkSize);
                if (!$ok) {
                    $errors[] = $msg;
                    break;
                }
            }

            writeToFile($fh, sqlLine());
        }

        // VIEWS (optional)
        if (count($errors) === 0 && $includeViews) {
            $views = getViews();
            foreach ($views as $v) {
                $createV = showCreate('view', $v);
                if ($createV === '') {
                    $errors[] = "SHOW CREATE VIEW failed for: $v";
                    break;
                }

                writeToFile($fh, sqlLine("--"));
                writeToFile($fh, sqlLine("-- VIEW FOR: $v"));
                writeToFile($fh, sqlLine("--"));
                writeToFile($fh, sqlLine());

                if ($addDrops) {
                    $vEsc = '`' . str_replace('`','``',$v) . '`';
                    writeToFile($fh, sqlLine("DROP VIEW IF EXISTS $vEsc;"));
                }

                writeToFile($fh, $createV . ";\n\n");
            }
        }

        if ($disableFK) {
            writeToFile($fh, sqlLine("SET FOREIGN_KEY_CHECKS=1;"));
        }

        fclose($fh);

        if (count($errors) === 0) {
            $createdFile = $filename;
            $messages[] = "Backup created: $filename";
        } else {
            @unlink($path);
        }
    }
}

if ($action === 'restore_backup' && count($errors) === 0) {
    $didPostAction = true;

    if ($restoreFile === '') {
        $errors[] = "No restore file selected.";
    } else {
        $restoreDir = managedAreaDir($restoreArea, $backupDir, $archiveDir, $deleteDir);
        $path = $restoreDir . DIRECTORY_SEPARATOR . $restoreFile;
        if (!is_file($path)) {
            $errors[] = "Restore file not found: " . $restoreFile;
        } else {
            $sql = @file_get_contents($path);
            if ($sql === false) {
                $errors[] = "Could not read restore file: " . $restoreFile;
            } else {
                $restoreReport['file'] = $restoreFile;
                $restoreReport['bytes'] = strlen($sql);

                $sql = normalizeSqlForRestore($sql, (bool)$restoreStripDefiner);

                if ($restoreDisableFK) {
                    $sql = "SET FOREIGN_KEY_CHECKS=0;\n" . $sql . "\nSET FOREIGN_KEY_CHECKS=1;\n";
                }

                $stmts = splitSqlStatements($sql);

                // IMPORTANT: preserve explicit AUTO_INCREMENT id=0 values on restore
                // (MariaDB otherwise treats 0 as "next auto-increment")
                if (!$restoreDryRun) {
                    @mysqli_query($dbconnect, "SET SESSION sql_mode = CONCAT_WS(',', @@SESSION.sql_mode, 'NO_AUTO_VALUE_ON_ZERO')");
                }

                [$count, $execErrors] = executeStatements($stmts, (bool)$restoreDryRun);

                $restoreReport['statements'] = $count;
                $restoreReport['errors'] = $execErrors;

                if (count($execErrors) === 0) {
                    $messages[] = $restoreDryRun
                        ? "Dry-run OK: parsed $count statements from $restoreFile (no SQL executed)."
                        : "Restore OK: executed $count statements from $restoreFile.";
                } else {
                    $errors[] = "Restore failed. First error: " . $execErrors[0];
                }
            }
        }
    }
}

// PRG redirect: prevent resubmission on refresh, and reset dry-run UI state
if ($didPostAction) {
    $_SESSION['flash_messages'] = $messages;
    $_SESSION['flash_errors'] = $errors;
    $_SESSION['flash_restore_report'] = $restoreReport;

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$files = listBackupFiles($backupDir);
$archiveFiles = listBackupFiles($archiveDir);
$deleteFiles = listBackupFiles($deleteDir);

/* ----------------------- page output ----------------------- */
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin DB Backup + Restore</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
    body{
        margin:0 !important;
        font-family: Arial, Helvetica, sans-serif !important;
        background:#111 !important;
        color:#eee !important;
    }
    .topbar{
        display:flex !important;
        justify-content:space-between !important;
        align-items:center !important;
        padding:10px 14px !important;
        font-size:14px !important;
    }
    .admin-status{ font-weight:bold !important; }
    .admin-yes{ color:#39d353 !important; }
    .admin-no{ color:#ff6b6b !important; }

    .wrap{
        max-width:1100px !important;
        margin:18px auto 40px !important;
        padding:0 14px !important;
    }

    .card{
        background:#1b1b1b !important;
        border:1px solid #2b2b2b !important;
        border-radius:10px !important;
        padding:18px !important;
        box-shadow: 0 8px 24px rgba(0,0,0,.35) !important;
        margin-bottom:16px !important;
        color:#eee !important;
    }

    h1{
        margin:0 0 8px 0 !important;
        font-size:34px !important;
        color:#d8c08a !important;
        letter-spacing:.2px !important;
    }
    .sub{
        margin:0 0 14px 0 !important;
        color:#c9bfa9 !important;
    }

    .msg-ok{
        background:#1f6f2a !important;
        border:1px solid #2aa93b !important;
        padding:12px 12px !important;
        border-radius:8px !important;
        margin:12px 0 0 !important;
        color:#ffffff !important;
        font-weight:bold !important;
        font-size:16px !important;
    }
    .msg-err{
        background:#7a1f1f !important;
        border:1px solid #ff5a5a !important;
        padding:12px 12px !important;
        border-radius:8px !important;
        margin:12px 0 0 !important;
        color:#ffffff !important;
        font-weight:bold !important;
        font-size:16px !important;
    }

    .row{
        display:flex !important;
        gap:10px !important;
        align-items:center !important;
        flex-wrap:wrap !important;
        margin-top:10px !important;
    }

    .btn{
        display:inline-block !important;
        border:0 !important;
        border-radius:8px !important;
        padding:12px 18px !important;
        font-weight:bold !important;
        cursor:pointer !important;
        font-size:16px !important;
        text-decoration:none !important;
    }
    .btn-primary{ background:#2f6feb !important; color:#fff !important; }
    .btn-danger{ background:#b82b2b !important; color:#fff !important; }
    .btn-secondary{ background:#555f6d !important; color:#fff !important; }
    .btn-warning{ background:#9b6a12 !important; color:#fff !important; }
    .btn-safe{ background:#237a45 !important; color:#fff !important; }
    .btn-mini{ background:#3f4650 !important; color:#fff !important; padding:7px 11px !important; font-size:13px !important; }
    .manager-note{ color:#c9bfa9 !important; font-size:13px !important; line-height:1.45 !important; }
    input[type=text]{ background:#121212 !important; color:#fff !important; border:1px solid #444 !important; border-radius:6px !important; padding:9px 10px !important; min-width:330px !important; }

    label.chk{
        display:flex !important;
        align-items:center !important;
        gap:8px !important;
        color:#c9bfa9 !important;
        font-weight:bold !important;
        user-select:none !important;
    }

    table{
        width:100% !important;
        border-collapse:collapse !important;
        margin-top:8px !important;
        font-size:14px !important;
    }
    th, td{
        border-bottom:1px solid #3a3a3a !important;
        padding:10px 8px !important;
        text-align:left !important;
    }
    th{ color:#ffd88a !important; }
    .muted{ color:#aaa !important; font-size:13px !important; }
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important; }
    textarea{
        width:100% !important;
        min-height:180px !important;
        box-sizing:border-box !important;
        padding:12px !important;
        border-radius:8px !important;
        border:1px solid #333 !important;
        background:#121212 !important;
        color:#fff !important;
        font-size:13px !important;
        outline:none !important;
        resize:vertical !important;
    }
</style>
</head>

<body>
<div class="topbar">
    <?php echo $adminStatusLine; ?>
    <div class="muted">DB: <span class="mono"><?php echo h(dbName()); ?></span></div>
</div>

<div class="wrap">

    <div class="card">
        <h1>Admin DB Backup + Restore</h1>
        <p class="sub">
            Backups are stored in: <span class="mono"><?php echo h($backupDir); ?></span>
        </p>

        <?php if (count($messages) > 0): ?>
            <div class="msg-ok"><?php echo h(implode(" | ", $messages)); ?></div>
        <?php endif; ?>

        <?php if (count($errors) > 0): ?>
            <div class="msg-err"><?php echo h(implode(" | ", $errors)); ?></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="font-size:18px; font-weight:bold; color:#d8c08a;">Create Backup</div>
        <div class="muted" style="margin-top:6px;">
            Creates a single <span class="mono">.sql</span> file containing structure (and optionally data).
        </div>

        <form method="post" action="">
            <input type="hidden" name="action" value="create_backup">

            <div class="row">
                <label class="chk"><input type="checkbox" name="include_data" <?php echo $includeData ? 'checked' : ''; ?>> Include table data</label>
                <label class="chk"><input type="checkbox" name="include_views" <?php echo $includeViews ? 'checked' : ''; ?>> Include views</label>
                <label class="chk"><input type="checkbox" name="add_drops" <?php echo $addDrops ? 'checked' : ''; ?>> Add DROP statements</label>
                <label class="chk"><input type="checkbox" name="disable_fk" <?php echo $disableFK ? 'checked' : ''; ?>> Disable foreign key checks during backup</label>
            </div>

            <div class="row">
                <button class="btn btn-primary" type="submit">Create Backup Now</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div style="font-size:18px; font-weight:bold; color:#d8c08a;">Active Backups</div>
        <div class="manager-note" style="margin-top:6px;">
            Radio button = one-file action (Restore or Rename). Checkbox = multi-file action (Archive or Move to To Be Deleted).
        </div>

        <?php if (count($files) === 0): ?>
            <div class="muted" style="margin-top:10px;">No active backup files found.</div>
        <?php else: ?>
            <form method="post" action="" id="active-backup-form">
                <input type="hidden" name="restore_area" value="active">
                <input type="hidden" name="rename_area" value="active">

                <table>
                    <thead>
                        <tr>
                            <th style="width:54px;">One</th>
                            <th style="width:68px;">Select</th>
                            <th>File</th>
                            <th style="width:160px;">Modified</th>
                            <th style="width:120px;">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($files as $fPath): ?>
                        <?php
                            $base = basename($fPath);
                            $mtime = @filemtime($fPath);
                            $size = @filesize($fPath);
                        ?>
                        <tr>
                            <td><input type="radio" name="restore_file" value="<?php echo h($base); ?>" data-rename-source="<?php echo h(preg_replace('/\.sql$/i', '', $base)); ?>"></td>
                            <td><input type="checkbox" name="selected_files[]" value="<?php echo h($base); ?>" data-select-group="active"></td>
                            <td class="mono"><?php echo h($base); ?></td>
                            <td><?php echo h($mtime ? date('Y-m-d H:i:s', $mtime) : ''); ?></td>
                            <td><?php echo h($size !== false ? number_format((int)$size) : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row">
                    <button class="btn btn-mini" type="button" onclick="setBackupChecks('active', true)">Select All</button>
                    <button class="btn btn-mini" type="button" onclick="setBackupChecks('active', false)">Clear</button>
                </div>

                <div class="row">
                    <label class="chk"><input type="checkbox" name="restore_dryrun"> Dry-run (do not execute SQL)</label>
                    <label class="chk"><input type="checkbox" name="restore_strip_definer" checked> Strip DEFINER during restore</label>
                    <label class="chk"><input type="checkbox" name="restore_disable_fk" checked> Disable foreign key checks during restore</label>
                </div>

                <div class="row">
                    <button class="btn btn-danger" type="submit" name="action" value="restore_backup"
                            onclick="return confirm('Restore can overwrite schema/data. Continue?');">Restore Selected Radio</button>
                </div>

                <div class="row" style="margin-top:18px !important;">
                    <input type="text" id="active-rename-to" name="rename_to" placeholder="Select a radio file to populate this name">
                    <button class="btn btn-secondary" type="submit" name="action" value="rename_file">Rename Selected Radio</button>
                </div>

                <div class="row" style="margin-top:18px !important;">
                    <button class="btn btn-safe" type="submit" name="action" value="archive_selected"
                            onclick="return confirm('Archive all checked backup files?');">Archive Checked</button>
                    <button class="btn btn-warning" type="submit" name="action" value="trash_selected"
                            onclick="return confirm('Move all checked files to To Be Deleted?');">Move Checked to To Be Deleted</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($restoreReport['file'] !== ''): ?>
            <div class="muted" style="margin-top:14px;">
                Restore report:
                <span class="mono"><?php echo h($restoreReport['file']); ?></span>
                | bytes: <?php echo h((string)$restoreReport['bytes']); ?>
                | statements: <?php echo h((string)$restoreReport['statements']); ?>
            </div>
            <?php if (count($restoreReport['errors']) > 0): ?>
                <textarea readonly class="mono"><?php echo h(implode("\n", $restoreReport['errors'])); ?></textarea>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="font-size:18px; font-weight:bold; color:#d8c08a;">Archive</div>
        <div class="manager-note" style="margin-top:6px;">
            Long-term keepers. Restore can run directly from Archive. Checked files can be moved back to Active or sent to To Be Deleted.
        </div>

        <?php if (count($archiveFiles) === 0): ?>
            <div class="muted" style="margin-top:10px;">Archive is empty.</div>
        <?php else: ?>
            <form method="post" action="" id="archive-backup-form">
                <input type="hidden" name="restore_area" value="archive">
                <input type="hidden" name="rename_area" value="archive">

                <table>
                    <thead>
                        <tr>
                            <th style="width:54px;">One</th>
                            <th style="width:68px;">Select</th>
                            <th>File</th>
                            <th style="width:160px;">Modified</th>
                            <th style="width:120px;">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($archiveFiles as $fPath): ?>
                        <?php
                            $base = basename($fPath);
                            $mtime = @filemtime($fPath);
                            $size = @filesize($fPath);
                        ?>
                        <tr>
                            <td><input type="radio" name="restore_file" value="<?php echo h($base); ?>" data-rename-source="<?php echo h(preg_replace('/\.sql$/i', '', $base)); ?>"></td>
                            <td><input type="checkbox" name="selected_files[]" value="<?php echo h($base); ?>" data-select-group="archive"></td>
                            <td class="mono"><?php echo h($base); ?></td>
                            <td><?php echo h($mtime ? date('Y-m-d H:i:s', $mtime) : ''); ?></td>
                            <td><?php echo h($size !== false ? number_format((int)$size) : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row">
                    <button class="btn btn-mini" type="button" onclick="setBackupChecks('archive', true)">Select All</button>
                    <button class="btn btn-mini" type="button" onclick="setBackupChecks('archive', false)">Clear</button>
                </div>

                <div class="row">
                    <label class="chk"><input type="checkbox" name="restore_dryrun"> Dry-run (do not execute SQL)</label>
                    <label class="chk"><input type="checkbox" name="restore_strip_definer" checked> Strip DEFINER during restore</label>
                    <label class="chk"><input type="checkbox" name="restore_disable_fk" checked> Disable foreign key checks during restore</label>
                </div>

                <div class="row">
                    <button class="btn btn-danger" type="submit" name="action" value="restore_backup"
                            onclick="return confirm('Restore this archived backup into the database?');">Restore Selected Radio</button>
                </div>

                <div class="row" style="margin-top:18px !important;">
                    <input type="text" id="archive-rename-to" name="rename_to" placeholder="Select a radio file to populate this name">
                    <button class="btn btn-secondary" type="submit" name="action" value="rename_file">Rename Selected Radio</button>
                </div>

                <div class="row" style="margin-top:18px !important;">
                    <button class="btn btn-primary" type="submit" name="action" value="unarchive_selected">Move Checked to Active</button>
                    <button class="btn btn-warning" type="submit" name="action" value="archive_to_trash_selected"
                            onclick="return confirm('Move checked Archive files to To Be Deleted?');">Move Checked to To Be Deleted</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="font-size:18px; font-weight:bold; color:#d8c08a;">To Be Deleted</div>
        <div class="manager-note" style="margin-top:6px;">
            Manual safety holding area. Nothing is deleted automatically.
            Move files back to Active if you change your mind, or permanently delete them when you are sure.
        </div>

        <?php if (count($deleteFiles) === 0): ?>
            <div class="muted" style="margin-top:10px;">Nothing is waiting to be deleted.</div>
        <?php else: ?>
            <form method="post" action="" id="delete-backup-form">
                <table>
                    <thead>
                        <tr>
                            <th style="width:68px;">Select</th>
                            <th>File</th>
                            <th style="width:160px;">Modified</th>
                            <th style="width:110px;">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($deleteFiles as $fPath): ?>
                        <?php
                            $base = basename($fPath);
                            $mtime = @filemtime($fPath);
                            $size = @filesize($fPath);
                        ?>
                        <tr>
                            <td><input type="checkbox" name="selected_files[]" value="<?php echo h($base); ?>" data-select-group="delete"></td>
                            <td class="mono"><?php echo h($base); ?></td>
                            <td><?php echo h($mtime ? date('Y-m-d H:i:s', $mtime) : ''); ?></td>
                            <td><?php echo h($size !== false ? number_format((int)$size) : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row">
                    <button class="btn btn-mini" type="button" onclick="setBackupChecks('delete', true)">Select All</button>
                    <button class="btn btn-mini" type="button" onclick="setBackupChecks('delete', false)">Clear</button>
                </div>

                <div class="row">
                    <button class="btn btn-primary" type="submit" name="action" value="trash_restore_active_selected">Restore Checked to Active</button>
                    <button class="btn btn-danger" type="submit" name="action" value="permanent_delete_selected"
                            onclick="return confirm('PERMANENTLY DELETE the checked file(s)? This cannot be undone.');">Permanently Delete Checked</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <div style="font-size:10px; color:#999; text-align:right; margin:14px 10px 8px 10px; padding:0;">
        admin_db_backup.php
    </div>

</div>

<script>
function setBackupChecks(group, checked) {
    document.querySelectorAll('input[type="checkbox"][data-select-group="' + group + '"]').forEach(function (box) {
        box.checked = checked;
    });
}

function wireRenameAutoFill(formId, inputId) {
    var form = document.getElementById(formId);
    var input = document.getElementById(inputId);
    if (!form || !input) return;

    form.querySelectorAll('input[type="radio"][name="restore_file"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (radio.checked) {
                input.value = radio.getAttribute('data-rename-source') || '';
                input.focus();
                input.select();
            }
        });
        if (radio.checked) {
            input.value = radio.getAttribute('data-rename-source') || '';
        }
    });
}

wireRenameAutoFill('active-backup-form', 'active-rename-to');
wireRenameAutoFill('archive-backup-form', 'archive-rename-to');
</script>
</body>
</html>