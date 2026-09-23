<?php
declare(strict_types=1);

/**
 * install_useractive_competitive_eligibility_v002_20260923_033115am.php
 *
 * VERSION: v002
 * LAST MODIFIED: 9/23/2026 3:31:15 am
 *
 * PURPOSE:
 * - Preserve already-recorded legitimate picks/results even when a regular user's
 *   users.userActive value later becomes 'N' during the same season.
 * - Treat users.userActive='Y' as a CURRENT participation/expectation signal for
 *   forward-looking items such as missing-pick lists and reminder/email logic, not
 *   as permission to erase recorded league history.
 * - Add guest/test userID 998 to the existing explicit test-account exclusions
 *   alongside legacy userID 0 and MRL test userID 999 where official reports apply.
 * - Keep historical and current-season legitimate team picks/results visible.
 * - Do NOT change login/account activation, personal Team page visibility, View As,
 *   Pick Reminder logic, scheduler/cron, database values, or add an email flag.
 *
 * TARGETS:
 * - /team_chart.php                         v027 -> v028
 * - /current_segment_chart.php              v009 -> v010
 * - /submitted_teams.php                    v002 -> v003
 * - /race_results/weekly_standings.php      v079 -> v080
 *
 * INSTALLER FEATURES:
 * - Read-only preflight against actual production files/database state.
 * - Candidate generation + Hostinger-approved `php -l` lint before Apply.
 * - Exact backups before replacement.
 * - Atomic replacement where practical.
 * - Installed-file lint + signature/version postflight.
 * - Automatic rollback if a critical postflight fails.
 * - Explicit manual rollback.
 * - Timestamped local JSON export for PRECHECK and POSTCHECK reports.
 */

session_start();
date_default_timezone_set('America/New_York');

const INST_NAME = 'install_useractive_competitive_eligibility';
const INST_VERSION = 'v002';
const INST_GENERATED = '9/23/2026 3:31:15 am ET';
const INST_SESSION_KEY = 'mrl_useractive_eligibility_installer_v002';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
if ($root === '' || !is_dir($root)) {
    $root = __DIR__;
}

require_once $root . '/class.user.php';
$installerUser = new USER();
if (!$installerUser->is_logged_in() || !isAdmin($_SESSION['userSession'] ?? null)) {
    http_response_code(403);
    exit('Admin access required.');
}

$targets = [
    'team_chart.php' => [
        'path' => $root . '/team_chart.php',
        'expected' => 'v027',
        'new' => 'v028',
    ],
    'current_segment_chart.php' => [
        'path' => $root . '/current_segment_chart.php',
        'expected' => 'v009',
        'new' => 'v010',
    ],
    'submitted_teams.php' => [
        'path' => $root . '/submitted_teams.php',
        'expected' => 'v002',
        'new' => 'v003',
    ],
    'race_results/weekly_standings.php' => [
        'path' => $root . '/race_results/weekly_standings.php',
        'expected' => 'v079',
        'new' => 'v080',
    ],
];

function inst_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function inst_et_stamp(): string
{
    return date('Ymd_His') . strtolower(date('a'));
}

function inst_et_iso(): string
{
    return date('c');
}

function inst_version_from_content(string $content): string
{
    if (preg_match('/^\s*\*?\s*VERSION:\s*(v\d+)/mi', $content, $m)) {
        return (string)$m[1];
    }
    return '';
}

function inst_check(string $label, bool $pass, string $detail = '', bool $required = true): array
{
    return [
        'label' => $label,
        'status' => $pass ? 'PASS' : ($required ? 'FAIL' : 'WARN'),
        'required' => $required,
        'detail' => $detail,
    ];
}

function inst_disabled_functions(): array
{
    $raw = (string)ini_get('disable_functions');
    if ($raw === '') return [];
    $parts = array_map('trim', explode(',', $raw));
    return array_values(array_filter($parts, static function ($v) { return $v !== ''; }));
}

function inst_shell_exec_available(): bool
{
    if (!function_exists('shell_exec') || !is_callable('shell_exec')) return false;
    return !in_array('shell_exec', inst_disabled_functions(), true);
}

function inst_lint_file(string $file): array
{
    if (!is_file($file)) {
        return ['ok' => false, 'output' => 'Lint target does not exist.'];
    }
    if (!inst_shell_exec_available()) {
        return ['ok' => false, 'output' => 'shell_exec() is unavailable. Check Hostinger PHP Configuration -> disableFunctions before changing lint code.'];
    }
    $out = shell_exec('php -l ' . escapeshellarg($file) . ' 2>&1');
    $text = trim((string)$out);
    $ok = ($text !== '' && stripos($text, 'No syntax errors detected') !== false);
    return ['ok' => $ok, 'output' => $text];
}

function inst_replace_exact(string $content, string $search, string $replace, int $expectedCount, string $label): string
{
    $count = substr_count($content, $search);
    if ($count !== $expectedCount) {
        throw new RuntimeException($label . ': expected anchor count ' . $expectedCount . ', found ' . $count . '.');
    }
    return str_replace($search, $replace, $content);
}

function inst_patch_header(string $content, string $oldVersion, string $oldModified, string $newVersion, string $newModified, string $changeBlock, string $changeAnchor): string
{
    $content = inst_replace_exact(
        $content,
        'VERSION: ' . $oldVersion . "\n * LAST MODIFIED: " . $oldModified,
        'VERSION: ' . $newVersion . "\n * LAST MODIFIED: " . $newModified,
        1,
        'Header version/timestamp'
    );
    $content = inst_replace_exact($content, $changeAnchor, $changeBlock . $changeAnchor, 1, 'Changelog insertion');
    return $content;
}

function inst_build_team_chart(string $content): string
{
    $change = " * v028 (9/23/2026 3:31:15 am ET)
"
        . " * - SAFETY: Explicitly excludes test accounts userID 0, 998, and 999 plus MRL test-team rows from Team Chart output.
"
        . " * - PARTICIPATION: userActive is NOT used to remove already-recorded picks; a regular user who becomes inactive remains visible where picks exist.
"
        . " * - HISTORY: Prior-season Team Charts remain based on recorded historical picks.
"
        . " * - PRESERVE: Privacy gate, LP/RD display, print/PDF, XLSX export, navigation, themes, and all legitimate team data unchanged.
"
        . " *
";

    $content = inst_patch_header(
        $content,
        'v027',
        '9/21/2026 11:08:45 pm ET',
        'v028',
        '9/23/2026 3:31:15 am ET',
        $change,
        " * CHANGELOG:
 *
"
    );

    $old = "                  AND COALESCE(u.userName, '') != 'MRL'
                ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC";
    $new = "                  AND COALESCE(u.userName, '') != 'MRL'
                  AND up.userID NOT IN (0, 998, 999)
                  AND LOWER(TRIM(up.teamName)) <> 'mrl test team'
                ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC";
    $content = inst_replace_exact($content, $old, $new, 2, 'Team Chart test-account exclusion');

    return $content;
}

function inst_build_current_segment_chart(string $content): string
{
    $change = " * v010 (9/23/2026 3:31:15 am ET)
"
        . " * - SAFETY: Explicitly excludes test accounts userID 0, 998, and 999 plus MRL test-team rows.
"
        . " * - PARTICIPATION: userActive is NOT used to remove already-recorded current-season picks.
"
        . " * - PRESERVE: LP/RD display, colors, chart layout, effective-race notes, and Team page behavior unchanged.
"
        . " *
";

    $content = inst_patch_header(
        $content,
        'v009',
        '9/9/2026 2:44:18 am ET',
        'v010',
        '9/23/2026 3:31:15 am ET',
        $change,
        " * CHANGELOG:
 *
"
    );

    $old = "      AND COALESCE(u.userName, '') != 'MRL'
    ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC";
    $new = "      AND COALESCE(u.userName, '') != 'MRL'
      AND up.userID NOT IN (0, 998, 999)
      AND LOWER(TRIM(up.teamName)) <> 'mrl test team'
    ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC";
    $content = inst_replace_exact($content, $old, $new, 1, 'Current Segment Chart test-account exclusion');

    return $content;
}

function inst_build_submitted_teams(string $content): string
{
    $change = " * v003 (9/23/2026 3:31:15 am ET)
"
        . " * - SAFETY: Submitted-team output explicitly excludes test accounts userID 0, 998, and 999 plus MRL test-team rows.
"
        . " * - PARTICIPATION: Already-submitted legitimate picks remain visible even if that regular user later becomes userActive='N'.
"
        . " * - EXPECTATION: The Missing Picks list requires users.userActive='Y', so inactive/dropout users are no longer expected to submit future picks.
"
        . " * - PRESERVE: LP markers/footnotes, counts, segment comparison, and display formatting unchanged.
"
        . " *
";

    $content = inst_patch_header(
        $content,
        'v002',
        '9/9/2026 2:44:18 am ET',
        'v003',
        '9/23/2026 3:31:15 am ET',
        $change,
        " * CHANGELOG:
"
    );

    $oldSubmitted = '$sql_submitted = "SELECT * FROM `user_picks` WHERE `raceYear` = \'$raceYear\' AND `userID` NOT IN (0, 999) AND `segment` = \'$segment\' ORDER BY `entryDate` ASC";';
    $newSubmitted = '$sql_submitted = "SELECT up.* FROM `user_picks` up WHERE up.`raceYear` = \'$raceYear\' AND up.`userID` NOT IN (0, 998, 999) AND up.`segment` = \'$segment\' AND LOWER(TRIM(up.teamName)) <> \'mrl test team\' ORDER BY up.`entryDate` ASC";';
    $content = inst_replace_exact($content, $oldSubmitted, $newSubmitted, 1, 'Submitted Teams current submissions SQL');

    $oldMissing = '$notSubmitted = "SELECT `teamName` FROM `user_picks` WHERE `raceYear` = \'$raceYear\' AND `segment` = \'$compareSegment\' AND `userID` NOT IN (0, 999) AND `teamName` NOT IN ( SELECT `teamName` FROM `user_picks` WHERE `raceYear` = \'$raceYear\' AND `segment` = \'$segment\' )";';
    $newMissing = '$notSubmitted = "SELECT DISTINCT up_prev.teamName FROM `user_picks` up_prev INNER JOIN `users` u_prev ON u_prev.userID = up_prev.userID WHERE up_prev.`raceYear` = \'$raceYear\' AND up_prev.`segment` = \'$compareSegment\' AND up_prev.`userID` NOT IN (0, 998, 999) AND COALESCE(u_prev.userActive, \'N\') = \'Y\' AND LOWER(TRIM(up_prev.teamName)) <> \'mrl test team\' AND up_prev.teamName NOT IN ( SELECT up_cur.teamName FROM `user_picks` up_cur WHERE up_cur.`raceYear` = \'$raceYear\' AND up_cur.`segment` = \'$segment\' AND up_cur.`userID` NOT IN (0, 998, 999) AND LOWER(TRIM(up_cur.teamName)) <> \'mrl test team\' ) ORDER BY up_prev.teamName ASC";';
    $content = inst_replace_exact($content, $oldMissing, $newMissing, 1, 'Submitted Teams missing-team SQL');

    return $content;
}

function inst_build_weekly_standings(string $content): string
{
    $change = " * v080 (9/23/2026 3:31:15 am ET)
"
        . " *   - SAFETY: Adds guest/test userID 998 to the existing noncompetitive test-account exclusions alongside userID 0 and 999.
"
        . " *   - PARTICIPATION: userActive is NOT used to erase legitimate recorded picks, points, or standings when a regular user becomes inactive midseason.
"
        . " *   - ROSTER: Competitive yearly roster continues to require actual user_picks participation, while explicitly excluding 0/998/999 and MRL test team.
"
        . " *   - PRESERVE: LP/RD effective-race behavior, legitimate missing-pick 0-point rows, scoring, snapshots, validation, audit, release history, exports, print, and UI unchanged.
"
        . " *
";

    $content = inst_patch_header(
        $content,
        'v079',
        '9/22/2026 12:21:58 am ET',
        'v080',
        '9/23/2026 3:31:15 am ET',
        $change,
        " * CHANGELOG:
 *
"
    );

    $oldFn = <<<'PHP'
function rrsg_is_noncompetitive_test_team(array $team): bool
{
    $hasUserId = array_key_exists('userID', $team) && $team['userID'] !== null && $team['userID'] !== '';
    $userId = $hasUserId ? (int)$team['userID'] : null;
    $teamName = strtolower(trim((string)($team['teamName'] ?? '')));

    // userID 0 is the current legacy test account; 999 is its planned positive-ID replacement.
    // A row with no userID field is not automatically a test row.
    if ($hasUserId && ($userId === 0 || $userId === 999)) {
        return true;
    }

    return $teamName === 'mrl test team';
}
PHP;

    $newFn = <<<'PHP'
function rrsg_is_noncompetitive_test_team(array $team): bool
{
    $hasUserId = array_key_exists('userID', $team) && $team['userID'] !== null && $team['userID'] !== '';
    $userId = $hasUserId ? (int)$team['userID'] : null;
    $teamName = strtolower(trim((string)($team['teamName'] ?? '')));

    // Explicit noncompetitive/test accounts. 998 is the guest onboarding/test account;
    // 999 is the positive-ID MRL test account; 0 is the legacy test account.
    // A row with no userID field is not automatically a test row.
    if ($hasUserId && in_array($userId, [0, 998, 999], true)) {
        return true;
    }

    return $teamName === 'mrl test team';
}
PHP;
    $content = inst_replace_exact($content, $oldFn, $newFn, 1, 'Weekly Standings explicit test-account helper');

    $content = inst_replace_exact($content, 'AND ut.userID NOT IN (0, 999)', 'AND ut.userID NOT IN (0, 998, 999)', 1, 'Weekly Standings roster userID exclusion');
    $content = inst_replace_exact($content, 'AND up_active.userID NOT IN (0, 999)', 'AND up_active.userID NOT IN (0, 998, 999)', 1, 'Weekly Standings participation userID exclusion');

    return $content;
}

function inst_build_candidate(string $relative, string $content): string
{
    if ($relative === 'team_chart.php') return inst_build_team_chart($content);
    if ($relative === 'current_segment_chart.php') return inst_build_current_segment_chart($content);
    if ($relative === 'submitted_teams.php') return inst_build_submitted_teams($content);
    if ($relative === 'race_results/weekly_standings.php') return inst_build_weekly_standings($content);
    throw new RuntimeException('Unknown target: ' . $relative);
}

function inst_post_signature_ok(string $relative, string $content): array
{
    if ($relative === 'team_chart.php') {
        return [
            strpos($content, 'VERSION: v028') !== false,
            substr_count($content, 'up.userID NOT IN (0, 998, 999)') === 2,
            substr_count($content, "LOWER(TRIM(up.teamName)) <> 'mrl test team'") === 2,
            strpos($content, "COALESCE(u.userActive, 'N') = 'Y'") === false,
        ];
    }
    if ($relative === 'current_segment_chart.php') {
        return [
            strpos($content, 'VERSION: v010') !== false,
            strpos($content, 'up.userID NOT IN (0, 998, 999)') !== false,
            strpos($content, "LOWER(TRIM(up.teamName)) <> 'mrl test team'") !== false,
            strpos($content, "COALESCE(u.userActive, 'N') = 'Y'") === false,
        ];
    }
    if ($relative === 'submitted_teams.php') {
        return [
            strpos($content, 'VERSION: v003') !== false,
            substr_count($content, 'NOT IN (0, 998, 999)') >= 3,
            strpos($content, "COALESCE(u_prev.userActive, 'N') = 'Y'") !== false,
            strpos($content, 'SELECT up.* FROM `user_picks` up WHERE') !== false,
        ];
    }
    if ($relative === 'race_results/weekly_standings.php') {
        return [
            strpos($content, 'VERSION: v080') !== false,
            strpos($content, 'in_array($userId, [0, 998, 999], true)') !== false,
            strpos($content, 'AND ut.userID NOT IN (0, 998, 999)') !== false,
            strpos($content, 'AND up_active.userID NOT IN (0, 998, 999)') !== false,
            strpos($content, 'rrsg_filter_current_season_active_rows') === false,
        ];
    }
    return [false];
}

function inst_db_diagnostics($dbo): array
{
    $out = [
        'userActive_column' => false,
        'status_counts' => [],
        'known_test_accounts' => [],
    ];

    if (!($dbo instanceof PDO)) return $out;

    try {
        $cols = $dbo->query("SHOW COLUMNS FROM users LIKE 'userActive'");
        $out['userActive_column'] = ($cols && $cols->fetch(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        $out['userActive_column'] = false;
    }

    if (!$out['userActive_column']) return $out;

    try {
        $stmt = $dbo->query("SELECT COALESCE(userActive, '(NULL)') AS status, COUNT(*) AS qty FROM users GROUP BY COALESCE(userActive, '(NULL)') ORDER BY status");
        foreach ((array)($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
            $out['status_counts'][(string)$row['status']] = (int)$row['qty'];
        }
    } catch (Throwable $e) {
        $out['status_counts'] = [];
    }

    try {
        $stmt = $dbo->query("SELECT userID, userName, COALESCE(userActive, '') AS userActive FROM users WHERE userID IN (998, 999) ORDER BY userID");
        $out['known_test_accounts'] = (array)($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []);
    } catch (Throwable $e) {
        $out['known_test_accounts'] = [];
    }

    return $out;
}

function inst_preflight(array $targets, $dbo): array
{
    $checks = [];
    $files = [];
    $candidateLints = [];

    $checks[] = inst_check('shell_exec available for Hostinger php -l', inst_shell_exec_available(), inst_shell_exec_available() ? 'Available.' : 'Unavailable. Check Hostinger PHP Configuration -> disableFunctions and confirm shell_exec has not been re-added.');

    $diag = inst_db_diagnostics($dbo);
    $checks[] = inst_check('users.userActive column exists', !empty($diag['userActive_column']), !empty($diag['userActive_column']) ? 'Column found.' : 'Column not found.');

    foreach ($targets as $relative => $meta) {
        $file = (string)$meta['path'];
        $exists = is_file($file);
        $writable = $exists && is_writable($file);
        $content = $exists ? (string)@file_get_contents($file) : '';
        $version = $content !== '' ? inst_version_from_content($content) : '';
        $versionOk = ($version === (string)$meta['expected']);

        $checks[] = inst_check($relative . ' exists', $exists, $file);
        $checks[] = inst_check($relative . ' writable', $writable, $writable ? 'Writable.' : 'Not writable.');
        $checks[] = inst_check($relative . ' baseline version', $versionOk, 'Detected ' . ($version !== '' ? $version : '(unknown)') . '; expected ' . $meta['expected'] . '.');

        $fileInfo = [
            'path' => $file,
            'detected_version' => $version,
            'expected_version' => $meta['expected'],
            'target_version' => $meta['new'],
            'sha256_before' => ($exists ? @hash_file('sha256', $file) : null),
        ];

        if ($exists && $versionOk) {
            try {
                $candidate = inst_build_candidate($relative, $content);
                $sig = inst_post_signature_ok($relative, $candidate);
                $sigOk = !in_array(false, $sig, true);
                $checks[] = inst_check($relative . ' patch anchors/signature', $sigOk, $sigOk ? 'Candidate patch built with expected signatures.' : 'Candidate signature check failed.');

                $tmp = tempnam(sys_get_temp_dir(), 'mrlua_');
                if ($tmp === false) {
                    throw new RuntimeException('Unable to allocate temp file for candidate lint.');
                }
                file_put_contents($tmp, $candidate);
                $lint = inst_lint_file($tmp);
                @unlink($tmp);
                $candidateLints[$relative] = $lint;
                $checks[] = inst_check($relative . ' candidate php -l', !empty($lint['ok']), (string)$lint['output']);
                $fileInfo['candidate_sha256'] = hash('sha256', $candidate);
            } catch (Throwable $e) {
                $checks[] = inst_check($relative . ' patch anchors/signature', false, $e->getMessage());
                $checks[] = inst_check($relative . ' candidate php -l', false, 'Candidate was not safely built.');
            }
        }

        $files[$relative] = $fileInfo;
    }

    $ok = true;
    foreach ($checks as $check) {
        if (($check['required'] ?? true) && ($check['status'] ?? '') !== 'PASS') {
            $ok = false;
            break;
        }
    }

    return [
        'ok' => $ok,
        'checks' => $checks,
        'files' => $files,
        'candidate_lints' => $candidateLints,
        'database' => $diag,
    ];
}

function inst_postflight(array $targets, $dbo): array
{
    $checks = [];
    $files = [];

    foreach ($targets as $relative => $meta) {
        $file = (string)$meta['path'];
        $exists = is_file($file);
        $content = $exists ? (string)@file_get_contents($file) : '';
        $version = $content !== '' ? inst_version_from_content($content) : '';
        $versionOk = ($version === (string)$meta['new']);
        $sig = $content !== '' ? inst_post_signature_ok($relative, $content) : [false];
        $sigOk = !in_array(false, $sig, true);
        $lint = $exists ? inst_lint_file($file) : ['ok' => false, 'output' => 'File missing.'];

        $checks[] = inst_check($relative . ' installed version', $versionOk, 'Detected ' . ($version !== '' ? $version : '(unknown)') . '; expected ' . $meta['new'] . '.');
        $checks[] = inst_check($relative . ' installed signature', $sigOk, $sigOk ? 'Expected participation/test-account signatures found.' : 'Expected signature missing.');
        $checks[] = inst_check($relative . ' installed php -l', !empty($lint['ok']), (string)$lint['output']);

        $files[$relative] = [
            'path' => $file,
            'detected_version' => $version,
            'target_version' => $meta['new'],
            'sha256_after' => ($exists ? @hash_file('sha256', $file) : null),
            'lint' => $lint,
        ];
    }

    $diag = inst_db_diagnostics($dbo);
    $checks[] = inst_check('users.userActive column still available', !empty($diag['userActive_column']), !empty($diag['userActive_column']) ? 'Column found.' : 'Column not found.');

    $ok = true;
    foreach ($checks as $check) {
        if (($check['required'] ?? true) && ($check['status'] ?? '') !== 'PASS') {
            $ok = false;
            break;
        }
    }

    return [
        'ok' => $ok,
        'checks' => $checks,
        'files' => $files,
        'database' => $diag,
    ];
}

function inst_backup_targets(array $targets, string $root): array
{
    $runStamp = inst_et_stamp();
    $backupRoot = $root . '/_installer_backups/' . INST_NAME . '_' . INST_VERSION . '_' . $runStamp;
    if (!is_dir($backupRoot) && !mkdir($backupRoot, 0755, true) && !is_dir($backupRoot)) {
        throw new RuntimeException('Unable to create backup root: ' . $backupRoot);
    }

    $backups = [];
    foreach ($targets as $relative => $meta) {
        $source = (string)$meta['path'];
        $dest = $backupRoot . '/' . $relative;
        $destDir = dirname($dest);
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            throw new RuntimeException('Unable to create backup folder: ' . $destDir);
        }
        if (!copy($source, $dest)) {
            throw new RuntimeException('Backup copy failed for ' . $relative);
        }
        $backups[$relative] = [
            'source' => $source,
            'backup' => $dest,
            'sha256_source' => hash_file('sha256', $source),
            'sha256_backup' => hash_file('sha256', $dest),
        ];
        if ($backups[$relative]['sha256_source'] !== $backups[$relative]['sha256_backup']) {
            throw new RuntimeException('Backup hash mismatch for ' . $relative);
        }
    }

    return ['backup_root' => $backupRoot, 'files' => $backups, 'run_stamp' => $runStamp];
}

function inst_restore_backups(array $backupState): array
{
    $result = ['ok' => true, 'files' => []];
    foreach ((array)($backupState['files'] ?? []) as $relative => $meta) {
        $source = (string)($meta['backup'] ?? '');
        $target = (string)($meta['source'] ?? '');
        $ok = ($source !== '' && $target !== '' && is_file($source));
        $detail = '';
        if ($ok) {
            $tmp = $target . '.mrl_restore_' . uniqid('', true);
            $ok = copy($source, $tmp);
            if ($ok) {
                $ok = @rename($tmp, $target);
                if (!$ok) @unlink($tmp);
            }
            $detail = $ok ? 'Restored exact backup.' : 'Restore copy/rename failed.';
        } else {
            $detail = 'Backup source missing.';
        }
        $result['files'][$relative] = ['ok' => $ok, 'detail' => $detail, 'backup' => $source, 'target' => $target];
        if (!$ok) $result['ok'] = false;
    }
    return $result;
}

function inst_apply(array $targets, string $root, $dbo): array
{
    $pre = inst_preflight($targets, $dbo);
    if (empty($pre['ok'])) {
        return ['ok' => false, 'message' => 'Apply blocked: required preflight did not pass.', 'preflight' => $pre];
    }

    try {
        $backup = inst_backup_targets($targets, $root);
        $candidates = [];

        foreach ($targets as $relative => $meta) {
            $content = (string)file_get_contents((string)$meta['path']);
            $candidate = inst_build_candidate($relative, $content);

            $tmpLint = tempnam(sys_get_temp_dir(), 'mrlua_apply_');
            if ($tmpLint === false) throw new RuntimeException('Unable to allocate apply lint temp file for ' . $relative);
            file_put_contents($tmpLint, $candidate);
            $lint = inst_lint_file($tmpLint);
            @unlink($tmpLint);
            if (empty($lint['ok'])) {
                throw new RuntimeException('Candidate lint failed for ' . $relative . ': ' . $lint['output']);
            }
            $candidates[$relative] = $candidate;
        }

        foreach ($targets as $relative => $meta) {
            $target = (string)$meta['path'];
            $tmp = $target . '.mrl_install_' . uniqid('', true);
            if (file_put_contents($tmp, $candidates[$relative], LOCK_EX) === false) {
                throw new RuntimeException('Unable to write candidate beside ' . $relative);
            }
            if (!@rename($tmp, $target)) {
                @unlink($tmp);
                throw new RuntimeException('Atomic replacement failed for ' . $relative);
            }
        }

        $post = inst_postflight($targets, $dbo);
        if (empty($post['ok'])) {
            $rollback = inst_restore_backups($backup);
            return [
                'ok' => false,
                'message' => 'Critical postflight failed. Automatic rollback attempted.',
                'backup' => $backup,
                'postflight' => $post,
                'automatic_rollback' => $rollback,
            ];
        }

        return [
            'ok' => true,
            'message' => 'PASS — all four participation/test-account targets installed and passed postflight.',
            'backup' => $backup,
            'postflight' => $post,
        ];
    } catch (Throwable $e) {
        $rollback = isset($backup) ? inst_restore_backups($backup) : null;
        return [
            'ok' => false,
            'message' => 'Apply failed: ' . $e->getMessage(),
            'backup' => $backup ?? null,
            'automatic_rollback' => $rollback,
        ];
    }
}

function inst_report_payload(string $stage, array $data, array $targets): array
{
    $session = (array)($_SESSION[INST_SESSION_KEY] ?? []);
    return [
        'installer' => INST_NAME,
        'installer_version' => INST_VERSION,
        'installer_generated' => INST_GENERATED,
        'report_stage' => $stage,
        'report_generated_et' => inst_et_iso(),
        'scope' => [
            'rule' => "Recorded legitimate picks/results remain authoritative; userActive='Y' is used for forward-looking participation/expectation such as missing-pick/reminder logic, not to erase recorded results.",
            'historical_guard' => 'Historical and current-season recorded picks remain visible for legitimate regular users even if they later become inactive.',
            'legacy_safety' => 'Known userID 0/999 and MRL test-team exclusions remain where applicable.',
            'not_changed' => ['database userActive values', 'login/userStatus', 'current_user_team_chart.php', 'team_view_as.php', 'Pick Reminder logic', 'cron/scheduler', 'database schema/email flags'],
        ],
        'targets' => $targets,
        'result' => $data,
        'last_installer_session' => $session,
    ];
}

function inst_send_json(string $stage, array $data, array $targets): void
{
    $payload = inst_report_payload($stage, $data, $targets);
    $filename = INST_NAME . '_' . INST_VERSION . '_' . inst_et_stamp() . '_' . $stage . '.json';
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) $json = '{"error":"Unable to encode report"}';
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $json . "\n";
    exit;
}

// Database access is read-only for diagnostics. Installer never changes DB values.
$dbo = null;
try {
    require_once $root . '/config.php';
} catch (Throwable $e) {
    // Preflight will report userActive column unavailable if DB setup cannot be loaded.
}

$action = (string)($_POST['action'] ?? '');
$message = '';
$messageClass = 'info';
$applyResult = null;
$rollbackResult = null;

$preflight = inst_preflight($targets, $dbo);

if ($action === 'export_precheck') {
    inst_send_json('PRECHECK', $preflight, $targets);
}

if ($action === 'apply') {
    $applyResult = inst_apply($targets, $root, $dbo);
    $_SESSION[INST_SESSION_KEY] = [
        'last_action' => 'apply',
        'at_et' => inst_et_iso(),
        'ok' => !empty($applyResult['ok']),
        'message' => (string)($applyResult['message'] ?? ''),
        'backup' => $applyResult['backup'] ?? null,
        'automatic_rollback' => $applyResult['automatic_rollback'] ?? null,
    ];
    $message = (string)($applyResult['message'] ?? 'Apply completed.');
    $messageClass = !empty($applyResult['ok']) ? 'ok' : 'bad';
}

$postflight = inst_postflight($targets, $dbo);

if ($action === 'export_postcheck') {
    inst_send_json('POSTCHECK', $postflight, $targets);
}

if ($action === 'rollback') {
    $state = (array)($_SESSION[INST_SESSION_KEY] ?? []);
    $backup = (array)($state['backup'] ?? []);
    if (empty($backup)) {
        $rollbackResult = ['ok' => false, 'message' => 'No backup from this installer session is available.'];
    } else {
        $rr = inst_restore_backups($backup);
        $rollbackResult = ['ok' => !empty($rr['ok']), 'message' => !empty($rr['ok']) ? 'Rollback restored the exact backed-up target files.' : 'Rollback encountered one or more restore failures.', 'detail' => $rr];
    }
    $_SESSION[INST_SESSION_KEY]['last_action'] = 'rollback';
    $_SESSION[INST_SESSION_KEY]['rollback_at_et'] = inst_et_iso();
    $_SESSION[INST_SESSION_KEY]['rollback_result'] = $rollbackResult;
    $message = (string)$rollbackResult['message'];
    $messageClass = !empty($rollbackResult['ok']) ? 'ok' : 'bad';
    $preflight = inst_preflight($targets, $dbo);
    $postflight = inst_postflight($targets, $dbo);
}

function inst_render_checks(array $checks): void
{
    echo '<table><thead><tr><th>CHECK</th><th>STATUS</th><th>DETAIL</th></tr></thead><tbody>';
    foreach ($checks as $c) {
        $status = (string)($c['status'] ?? '');
        $cls = $status === 'PASS' ? 'pass' : ($status === 'WARN' ? 'warn' : 'fail');
        echo '<tr><td>' . inst_h($c['label'] ?? '') . '</td><td class="' . $cls . '">' . inst_h($status) . '</td><td><pre>' . inst_h($c['detail'] ?? '') . '</pre></td></tr>';
    }
    echo '</tbody></table>';
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Participation / Test-Account Eligibility Installer</title>
<style>
:root{--bg:#101214;--panel:#1b1f23;--panel2:#14181c;--border:#424a52;--text:#eef2f6;--muted:#adb7c0;--gold:#ffd28a;--green:#23864b;--blue:#2f6feb;--red:#b83232;--amber:#8a6116}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif;font-size:15px}.wrap{max-width:1240px;margin:22px auto 60px;padding:0 16px}.card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:18px;margin:0 0 15px}.card h1,.card h2{margin-top:0;color:var(--gold)}h1{font-size:28px}h2{font-size:20px}.muted{color:var(--muted)}.banner{padding:12px 14px;border-radius:8px;margin:12px 0;font-weight:700}.banner.ok{background:#123b29;border:1px solid #2e865e}.banner.bad{background:#4a1919;border:1px solid #a64c4c}.banner.info{background:#143149;border:1px solid #2a6e9d}.banner.warn{background:#493812;border:1px solid #9a7019}table{width:100%;border-collapse:collapse;background:var(--panel2)}th,td{border:1px solid #46505a;padding:8px 10px;text-align:left;vertical-align:top}th{background:#242b31;color:#fff}.pass{color:#6dec9a;font-weight:800}.fail{color:#ff8585;font-weight:800}.warn{color:#ffd37a;font-weight:800}pre{margin:0;white-space:pre-wrap;word-break:break-word;font:12px/1.4 Consolas,monospace;color:#d8e0e7}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}.btn{border:0;border-radius:7px;padding:11px 16px;font-weight:800;color:white;cursor:pointer}.btn.green{background:var(--green)}.btn.blue{background:var(--blue)}.btn.red{background:var(--red)}.btn:disabled{opacity:.42;cursor:not-allowed}.scope{line-height:1.55}.scope b{color:var(--gold)}code{color:#ffd58d}.files{font:13px/1.5 Consolas,monospace;background:#11161a;border:1px solid #37404a;border-radius:8px;padding:12px}.small{font-size:12px;color:var(--muted)}
</style>
</head><body><div class="wrap">
<div class="card">
<h1>MRL Participation / Test-Account Eligibility</h1>
<div class="muted">Installer <?php echo inst_h(INST_VERSION); ?> | Generated <?php echo inst_h(INST_GENERATED); ?></div>
<?php if ($message !== ''): ?><div class="banner <?php echo inst_h($messageClass); ?>"><?php echo inst_h($message); ?></div><?php endif; ?>
<div class="scope">
<p><b>Recorded-data rule:</b> legitimate picks/results already stored remain authoritative. A regular user changing to <code>userActive='N'</code> does <b>not</b> erase that user's current-season or historical Team Chart / Weekly Standings data.</p>
<p><b>Forward-looking rule:</b> <code>userActive='Y'</code> is used where the site asks who is still expected to participate, such as the Submitted Teams <b>Missing Picks</b> list. Pick Reminder already follows this model.</p>
<p><b>Belt + suspenders:</b> userID <code>0</code>, <code>998</code>, and <code>999</code> plus <code>MRL test team</code> are explicitly excluded from official report paths where applicable.</p>
<p><b>Not changed:</b> database values/schema, login/userStatus, personal current-user chart, View As, Pick Reminder logic, cron/master scheduler. No separate email flag is added in this pass.</p>
</div>
<div class="files">team_chart.php v027 → v028<br>current_segment_chart.php v009 → v010<br>submitted_teams.php v002 → v003<br>race_results/weekly_standings.php v079 → v080</div>
</div>

<div class="card"><h2>Preflight</h2>
<?php inst_render_checks((array)$preflight['checks']); ?>
<div class="actions">
<form method="post"><button class="btn blue" name="action" value="export_precheck">Export Preflight JSON</button></form>
<form method="post"><button class="btn green" name="action" value="apply" <?php echo !empty($preflight['ok']) ? '' : 'disabled'; ?>>Apply</button></form>
</div>
<div class="small">The exported PRECHECK file is timestamped and contains target versions/hashes, every preflight result, candidate lint output, and non-sensitive participation diagnostics.</div>
</div>

<div class="card"><h2>Postflight / Current Installed State</h2>
<?php inst_render_checks((array)$postflight['checks']); ?>
<div class="actions">
<form method="post"><button class="btn blue" name="action" value="export_postcheck">Export Postflight JSON</button></form>
<?php $sessionState=(array)($_SESSION[INST_SESSION_KEY]??[]); $hasBackup=!empty($sessionState['backup']); ?>
<form method="post" onsubmit="return confirm('Restore the exact backups from this installer session?');"><button class="btn red" name="action" value="rollback" <?php echo $hasBackup ? '' : 'disabled'; ?>>Rollback</button></form>
</div>
<div class="small">POSTCHECK export contains installed versions/hashes, installed lint results, signature checks, database diagnostics, and the backup/apply/rollback state from this browser session.</div>
</div>

<div class="card"><h2>Known test-account diagnostic</h2>
<?php $dbdiag=(array)($preflight['database']??[]); $known=(array)($dbdiag['known_test_accounts']??[]); ?>
<?php if (!$known): ?><div class="muted">No rows for IDs 998/999 were returned, or database diagnostics were unavailable.</div><?php else: ?>
<table><thead><tr><th>userID</th><th>userName</th><th>userActive</th></tr></thead><tbody>
<?php foreach($known as $row): ?><tr><td><?php echo inst_h($row['userID']??''); ?></td><td><?php echo inst_h($row['userName']??''); ?></td><td><?php echo inst_h($row['userActive']??''); ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>

</div></body></html>
