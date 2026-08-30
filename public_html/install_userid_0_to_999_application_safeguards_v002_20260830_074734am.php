<?php
declare(strict_types=1);

/**
 * install_userid_0_to_999_application_safeguards.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/30/2026 7:47:34 am
 *
 * PURPOSE:
 *   Step 3 of the controlled MRL test-account migration (userID 0 -> 999).
 *
 *   BEFORE the database identity is migrated, update only the active runtime
 *   pages whose legacy behavior depended on userID 0 being "special".
 *
 *   Safeguards installed:
 *   - Paid-status pages continue excluding the noncompetitive MRL test team.
 *   - Submitted-team / missing-pick utilities continue excluding the test team.
 *   - The year/team/segment utility continues excluding the test team.
 *   - Active/inactive email-list tools do not start including the MRL test mailbox.
 *   - team_view_as.php DOES include reserved userID 999 even though that account
 *     intentionally has userActive='N'.
 *
 * THIS INSTALLER DOES NOT CHANGE THE DATABASE.
 *
 * DEPLOYMENT MODEL:
 *   - Preview first.
 *   - Exact baseline verification using normalized Git blob SHA-1 values.
 *   - Automatic timestamped backups.
 *   - Patch to temporary file.
 *   - PHP lint when exec() is available.
 *   - Atomic replacement.
 *   - Postflight verification.
 *
 * BASELINES:
 *   GitHub repository stevekenney318/MRL, main branch, captured 8/30/2026.
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

function s_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function s_normalize_lf(string $text): string
{
    return str_replace(["\r\n", "\r"], "\n", $text);
}

function s_git_blob_sha1(string $text): string
{
    $text = s_normalize_lf($text);
    return sha1('blob ' . strlen($text) . "\0" . $text);
}

function s_disabled_functions(): array
{
    $raw = (string)ini_get('disable_functions');
    if ($raw === '') {
        return [];
    }

    $parts = array_map('trim', explode(',', $raw));
    return array_fill_keys(array_filter($parts), true);
}

function s_can_exec(): bool
{
    if (!function_exists('exec')) {
        return false;
    }

    $disabled = s_disabled_functions();
    return !isset($disabled['exec']);
}

function s_php_lint(string $path): array
{
    if (!s_can_exec()) {
        return ['available' => false, 'ok' => true, 'output' => 'exec() unavailable; lint skipped'];
    }

    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $output = [];
    $rc = 1;
    @exec($cmd, $output, $rc);

    return [
        'available' => true,
        'ok' => ($rc === 0),
        'output' => implode("\n", $output),
    ];
}

function s_apply_replacements(string $content, array $replacements, array &$details): string
{
    $working = s_normalize_lf($content);

    foreach ($replacements as $idx => $pair) {
        $find = (string)$pair['find'];
        $replace = (string)$pair['replace'];
        $expected = (int)($pair['expected'] ?? 1);

        $count = substr_count($working, $find);
        $details[] = [
            'index' => $idx + 1,
            'find_count' => $count,
            'expected' => $expected,
            'find' => $find,
            'replace' => $replace,
        ];

        if ($count !== $expected) {
            throw new RuntimeException(
                'Replacement #' . ($idx + 1) . ' expected ' . $expected .
                ' match(es), found ' . $count . '.'
            );
        }

        $working = str_replace($find, $replace, $working);
    }

    return $working;
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

$root = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\');
$backupRoot = $root . '/_migration_backups/userid_0_to_999_application_safeguards_20260830_074734am';

$targets = [
    [
        'file' => 'Paid_Status.php',
        'baseline_sha' => '05caa6c795e15eedb1732a174cef4539cf463d5e',
        'purpose' => 'Keep MRL test team out of current-year Paid Status.',
        'replacements' => [
            [
                'find' => "          AND `userID` != 0\n",
                'replace' => "          AND `userID` NOT IN (0, 999)\n",
                'expected' => 1,
            ],
        ],
    ],
    [
        'file' => 'Paid_Status_Year.php',
        'baseline_sha' => '637daad3eae3bd02efd685c470a70fff82987c31',
        'purpose' => 'Keep MRL test team out of historical Paid Status.',
        'replacements' => [
            [
                'find' => '$sql = "SELECT * FROM `Financial` WHERE `raceYear` = \'$selectedYear\' AND `userID`!= 0 ORDER BY `raceYear` DESC";',
                'replace' => '$sql = "SELECT * FROM `Financial` WHERE `raceYear` = \'$selectedYear\' AND `userID` NOT IN (0, 999) ORDER BY `raceYear` DESC";',
                'expected' => 1,
            ],
        ],
    ],
    [
        'file' => 'submitted_teams.php',
        'baseline_sha' => '98746d2c6bb835d91719c87e5bf231ec63d8d487',
        'purpose' => 'Keep MRL test team out of submitted/missing-pick status.',
        'replacements' => [
            [
                'find' => "AND `userID` != '0' AND `segment`",
                'replace' => "AND `userID` NOT IN (0, 999) AND `segment`",
                'expected' => 1,
            ],
            [
                'find' => "AND `segment` = '$compareSegment' AND `userID` != '0' AND `teamName`",
                'replace' => "AND `segment` = '$compareSegment' AND `userID` NOT IN (0, 999) AND `teamName`",
                'expected' => 1,
            ],
        ],
    ],
    [
        'file' => 'submitted_teams_count.php',
        'baseline_sha' => '6d58668247556c5d4aa0f28bd3789c0dbe6bcb1e',
        'purpose' => 'Keep MRL test team out of submitted-team count.',
        'replacements' => [
            [
                'find' => "AND `userID` != '0' AND `segment`",
                'replace' => "AND `userID` NOT IN (0, 999) AND `segment`",
                'expected' => 1,
            ],
        ],
    ],
    [
        'file' => 'select_year_team_segment.php',
        'baseline_sha' => '4527059c7ae0e7951eee936d937361fb6ab419ba',
        'purpose' => 'Preserve test-team exclusion in the legacy year/team/segment utility.',
        'replacements' => [
            [
                'find' => "    // Exclude teams with userID of 0\n    if (\$userID != 0) {\n",
                'replace' => "    // Exclude the noncompetitive MRL test account before and after 0 -> 999 migration.\n    if ((int)\$userID !== 0 && (int)\$userID !== 999) {\n",
                'expected' => 1,
            ],
        ],
    ],
    [
        'file' => 'email-inactive.php',
        'baseline_sha' => '10b763dc17d85b7a7f5562257eff36ead9e2a7ce',
        'purpose' => 'Prevent reserved MRL test mailbox from entering the inactive email list.',
        'replacements' => [
            [
                'find' => '$sql = "SELECT * FROM `users` WHERE `userID` > 0 AND `userActive` = \'N\'";',
                'replace' => '$sql = "SELECT * FROM `users` WHERE `userID` > 0 AND `userID` <> 999 AND `userActive` = \'N\'";',
                'expected' => 1,
            ],
        ],
    ],
    [
        'file' => 'email.php',
        'baseline_sha' => 'ddbb7c466f75ec1ca6f3f5d40f597055c03e755c',
        'purpose' => 'Prevent reserved MRL test mailbox from entering active/inactive email exports.',
        'replacements' => [
            [
                'find' => "        WHERE userID > 0\n          AND userActive = :active\n",
                'replace' => "        WHERE userID > 0\n          AND userID <> 999\n          AND userActive = :active\n",
                'expected' => 1,
            ],
        ],
    ],
    [
        'file' => 'team_view_as.php',
        'baseline_sha' => '6bc17f270dac4a154cf5b6a38c60a62904560e81',
        'purpose' => 'Make reserved userID 999 selectable in View As even though userActive=N.',
        'replacements' => [
            [
                'find' => "            WHERE userActive = 'Y'\n            ORDER BY userName ASC",
                'replace' => "            WHERE userActive = 'Y' OR userID = 999\n            ORDER BY userName ASC",
                'expected' => 1,
            ],
        ],
    ],
];

$mode = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply')
    ? 'apply'
    : 'preview';

$rows = [];
$overallReady = true;
$applyAttempted = ($mode === 'apply');
$applySuccess = false;
$globalError = '';

foreach ($targets as $target) {
    $full = $root . '/' . $target['file'];
    $row = [
        'file' => $target['file'],
        'purpose' => $target['purpose'],
        'exists' => is_file($full),
        'baseline_sha' => $target['baseline_sha'],
        'current_sha' => '',
        'baseline_match' => false,
        'patch_ready' => false,
        'replacement_details' => [],
        'status' => 'PENDING',
        'message' => '',
        'postflight' => false,
    ];

    if (!$row['exists']) {
        $row['status'] = 'BLOCKED';
        $row['message'] = 'Target file not found.';
        $overallReady = false;
        $rows[] = $row;
        continue;
    }

    $original = (string)file_get_contents($full);
    $row['current_sha'] = s_git_blob_sha1($original);
    $row['baseline_match'] = hash_equals($target['baseline_sha'], $row['current_sha']);

    try {
        $details = [];
        $candidate = s_apply_replacements($original, $target['replacements'], $details);
        $row['replacement_details'] = $details;
        $row['patch_ready'] = true;

        if (!$row['baseline_match']) {
            $row['status'] = 'BLOCKED';
            $row['message'] = 'Live file does not match the verified GitHub baseline.';
            $overallReady = false;
        } else {
            $row['status'] = 'READY';
            $row['message'] = 'Baseline and replacement checks passed.';
        }
    } catch (Throwable $e) {
        $row['status'] = 'BLOCKED';
        $row['message'] = $e->getMessage();
        $overallReady = false;
    }

    $rows[] = $row;
}

if ($applyAttempted) {
    if (!$overallReady) {
        $globalError = 'Apply blocked because one or more preflight checks failed.';
    } else {
        try {
            if (!is_dir($backupRoot) && !mkdir($backupRoot, 0755, true) && !is_dir($backupRoot)) {
                throw new RuntimeException('Could not create backup directory.');
            }

            foreach ($targets as $i => $target) {
                $full = $root . '/' . $target['file'];
                $original = (string)file_get_contents($full);

                // Re-verify immediately before changing each file.
                $liveSha = s_git_blob_sha1($original);
                if (!hash_equals($target['baseline_sha'], $liveSha)) {
                    throw new RuntimeException(
                        'Baseline changed before apply: ' . $target['file']
                    );
                }

                $details = [];
                $candidate = s_apply_replacements($original, $target['replacements'], $details);

                $backupPath = $backupRoot . '/' . basename($target['file']);
                if (!copy($full, $backupPath)) {
                    throw new RuntimeException('Backup failed: ' . $target['file']);
                }

                $mtime = @filemtime($full);
                if ($mtime !== false) {
                    @touch($backupPath, $mtime);
                }

                $tmp = $full . '.userid999tmp.' . bin2hex(random_bytes(4));
                if (file_put_contents($tmp, $candidate, LOCK_EX) === false) {
                    throw new RuntimeException('Could not write temporary file: ' . $target['file']);
                }

                @chmod($tmp, fileperms($full) & 0777);

                $lint = s_php_lint($tmp);
                if (!$lint['ok']) {
                    @unlink($tmp);
                    throw new RuntimeException(
                        'PHP lint failed for ' . $target['file'] . ': ' . $lint['output']
                    );
                }

                if (!@rename($tmp, $full)) {
                    @unlink($tmp);
                    throw new RuntimeException('Atomic replacement failed: ' . $target['file']);
                }

                $installed = (string)file_get_contents($full);
                $postflight = true;

                foreach ($target['replacements'] as $pair) {
                    if (substr_count($installed, (string)$pair['replace']) < 1) {
                        $postflight = false;
                        break;
                    }
                }

                $rows[$i]['status'] = $postflight ? 'INSTALLED' : 'POSTFLIGHT FAIL';
                $rows[$i]['message'] = $postflight
                    ? 'Installed; backup + postflight passed.'
                    : 'Installed, but replacement verification failed.';
                $rows[$i]['postflight'] = $postflight;

                if (!$postflight) {
                    throw new RuntimeException('Postflight failed: ' . $target['file']);
                }
            }

            $applySuccess = true;

        } catch (Throwable $e) {
            $globalError = $e->getMessage();
        }
    }
}

function s_status_class(string $status): string
{
    if (in_array($status, ['READY', 'INSTALLED'], true)) {
        return 'ok';
    }
    if ($status === 'PENDING') {
        return 'muted';
    }
    return 'bad';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL userID 0 → 999 Application Safeguards</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
    --bg:#101214;--panel:#1d2023;--panel2:#17191b;--border:#4b5055;
    --text:#f0f0f0;--muted:#b8bec5;--gold:#efc77e;--blue:#55c7ff;
    --green:#63e69a;--red:#ff7e7e;--amber:#ffd479;
}
*{box-sizing:border-box}
html{background:var(--bg)}
body{margin:0;color:var(--text);font-family:Tahoma,Verdana,"Segoe UI",Arial,sans-serif;font-size:14px}
.wrap{width:97%;max-width:1600px;margin:18px auto 60px}
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
.small{font-size:12px}
.btn{
    display:inline-block;padding:10px 16px;border:1px solid #888;border-radius:7px;
    background:#ececec;color:#111;font-weight:700;cursor:pointer;
}
.btn.apply{background:#f1c76f;border-color:#c59a3a}
ul{line-height:1.55}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
    <h1>MRL userID 0 → 999 — Step 3 Application Safeguards</h1>
    <p><strong>Installer:</strong> v002 &nbsp; | &nbsp; <strong>Generated:</strong> 8/30/2026 7:47:34 am America/New_York</p>

    <?php if (!$applyAttempted): ?>
        <div class="banner <?php echo $overallReady ? 'ok' : 'bad'; ?>">
            PREVIEW ONLY — <?php echo $overallReady ? 'all preflight checks passed.' : 'one or more checks are blocking Apply.'; ?>
        </div>
    <?php elseif ($applySuccess): ?>
        <div class="banner ok">APPLY COMPLETE — all targeted safeguards installed and postflight checks passed.</div>
    <?php else: ?>
        <div class="banner bad">APPLY DID NOT COMPLETE: <?php echo s_h($globalError); ?></div>
    <?php endif; ?>

    <p>
        <strong>No database rows are changed by this installer.</strong>
        This step only prepares the live application for the later userID 0 → 999 database migration.
    </p>
</div>

<div class="card">
    <h2>What This Protects</h2>
    <ul>
        <li>The MRL test account remains excluded from official/admin league participation lists that previously relied on <code>userID != 0</code>.</li>
        <li>The MRL mailbox does not suddenly appear in active/inactive email exports after becoming positive ID 999.</li>
        <li><code>team_view_as.php</code> explicitly allows reserved ID 999 to appear even though the MRL account has <code>userActive='N'</code>.</li>
        <li><code>weekly_standings.php</code> is intentionally untouched because it already protects both IDs 0 and 999 plus the exact test-team name.</li>
    </ul>
</div>

<div class="card">
    <h2>Preflight / Install Matrix</h2>
    <table>
        <thead>
        <tr>
            <th>File</th>
            <th>Purpose</th>
            <th>Baseline</th>
            <th>Patch Match</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><code><?php echo s_h($row['file']); ?></code></td>
                <td><?php echo s_h($row['purpose']); ?></td>
                <td class="<?php echo $row['baseline_match'] ? 'ok' : 'bad'; ?>">
                    <?php echo $row['baseline_match'] ? 'PASS' : 'FAIL'; ?>
                    <div class="small mono">expected <?php echo s_h($row['baseline_sha']); ?></div>
                    <div class="small mono">current&nbsp; <?php echo s_h($row['current_sha']); ?></div>
                </td>
                <td class="<?php echo $row['patch_ready'] ? 'ok' : 'bad'; ?>">
                    <?php echo $row['patch_ready'] ? 'PASS' : 'FAIL'; ?>
                    <?php foreach ($row['replacement_details'] as $d): ?>
                        <div class="small">
                            replacement #<?php echo (int)$d['index']; ?>:
                            <?php echo (int)$d['find_count']; ?>/<?php echo (int)$d['expected']; ?> matches
                        </div>
                    <?php endforeach; ?>
                </td>
                <td class="<?php echo s_status_class((string)$row['status']); ?>">
                    <?php echo s_h($row['status']); ?>
                    <div class="small"><?php echo s_h($row['message']); ?></div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!$applyAttempted): ?>
<div class="card">
    <h2>Apply</h2>
    <?php if ($overallReady): ?>
        <p class="ok">All eight files match the verified GitHub baselines and every intended patch location was found exactly as expected.</p>
        <form method="post" onsubmit="return confirm('Apply the Step 3 userID 0 → 999 application safeguards now? Automatic backups will be created first.');">
            <input type="hidden" name="action" value="apply">
            <button class="btn apply" type="submit">Apply Step 3 Safeguards</button>
        </form>
    <?php else: ?>
        <p class="bad">Apply is disabled until all preflight checks pass.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($applyAttempted): ?>
<div class="card">
    <h2>Backup / Next Step</h2>
    <p><strong>Backup folder:</strong> <code><?php echo s_h($backupRoot); ?></code></p>
    <?php if ($applySuccess): ?>
        <p class="ok">
            Step 3 is complete. The next controlled step is the database migration preflight/install for userID 0 → 999.
        </p>
    <?php else: ?>
        <p class="bad">Do not proceed to the database migration until this installer completes cleanly.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
