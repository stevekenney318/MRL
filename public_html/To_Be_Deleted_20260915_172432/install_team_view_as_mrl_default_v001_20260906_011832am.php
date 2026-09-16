<?php
declare(strict_types=1);

/*
    filename: install_team_view_as_mrl_default_v001_20260906_011832am.php
    VERSION: v001
    LAST MODIFIED: 9/6/2026 1:18:32 am

    PURPOSE:
    - Update production team_view_as.php from v002 to v003.
    - Put MRL Test Team (userID 999) at the top of the user dropdown.
    - Default the dropdown to userID 999 when no alternate user is currently set.
    - Preserve the currently selected alternate user when one is already active.
    - Make a rollback copy before changing the live file.

    TARGET:
    - /public_html/team_view_as.php

    EXPECTED SOURCE:
    - team_view_as.php VERSION v002
    - GitHub MRL/main/public_html/team_view_as.php baseline

    CHANGELOG FOR TARGET v003:
    - MRL Test Team (userID 999) now sorts first.
    - When no alternate user is active, userID 999 is selected by default.
    - Existing active alternate user remains selected on the page.
*/

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $root . DIRECTORY_SEPARATOR . 'team_view_as.php';

$backupDir = $root . DIRECTORY_SEPARATOR . '_migration_backups'
    . DIRECTORY_SEPARATOR . 'team_view_as_mrl_default_v001_20260906_011832am';
$backupFile = $backupDir . DIRECTORY_SEPARATOR . 'team_view_as.php';

function tvai_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tvai_php_syntax_check(string $path): array {
    $php = PHP_BINARY ?: 'php';
    $cmd = escapeshellarg($php) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = [];
    $code = 1;
    @exec($cmd, $out, $code);

    if ($code === 0) {
        return [true, implode("\n", $out)];
    }

    if (!$out) {
        return [null, 'PHP CLI syntax check unavailable on this host.'];
    }

    return [false, implode("\n", $out)];
}

function tvai_write_atomic(string $path, string $contents): array {
    try {
        $suffix = bin2hex(random_bytes(4));
    } catch (Throwable $e) {
        $suffix = (string)mt_rand(100000, 999999);
    }

    $tmp = $path . '.tmp_' . $suffix;

    if (@file_put_contents($tmp, $contents, LOCK_EX) === false) {
        return [false, 'Could not write temporary file.'];
    }

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return [false, 'Could not replace target file.'];
    }

    return [true, ''];
}

$current = is_file($target) ? (string)@file_get_contents($target) : '';

$sourceExists = is_file($target);
$sourceWritable = $sourceExists && is_writable($target);
$publicWritable = is_writable($root);

$expectedVersion = strpos($current, 'VERSION: v002') !== false
    && strpos($current, 'FILE: " . basename(__FILE__) . " | VERSION: v002 | ') !== false;

$oldSql = <<<'TXT'
    $sql = "SELECT userID, userName
            FROM users
            WHERE userActive = 'Y' OR userID = 999
            ORDER BY userName ASC";
TXT;

$newSql = <<<'TXT'
    $sql = "SELECT userID, userName
            FROM users
            WHERE userActive = 'Y' OR userID = 999
            ORDER BY CASE WHEN userID = 999 THEN 0 ELSE 1 END,
                     userName ASC";
TXT;

$oldOption = <<<'TXT'
                    <option value="<?php echo $id; ?>">
                        <?php echo h($name . ' (ID ' . $id . ')'); ?>
                    </option>
TXT;

$newOption = <<<'TXT'
                    <?php
                        $selectedUidForDropdown = $hasAlternateUser ? $alternateUserUid : 999;
                        $isSelected = ($id === $selectedUidForDropdown);
                    ?>
                    <option value="<?php echo $id; ?>"<?php echo $isSelected ? ' selected' : ''; ?>>
                        <?php echo h($name . ' (ID ' . $id . ')'); ?>
                    </option>
TXT;

$hasSqlAnchor = substr_count($current, $oldSql) === 1;
$hasOptionAnchor = substr_count($current, $oldOption) === 1;

$requiredPass = $sourceExists
    && $sourceWritable
    && $publicWritable
    && $expectedVersion
    && $hasSqlAnchor
    && $hasOptionAnchor;

$message = '';
$messageType = '';

if (isset($_POST['action']) && $_POST['action'] === 'apply') {
    if (!$requiredPass) {
        $message = 'APPLY BLOCKED — one or more required preflight checks are not passing.';
        $messageType = 'err';
    } else {
        $updated = $current;

        $updated = str_replace(
            ' * VERSION: v002' . "\n" . ' * LAST MODIFIED: 3/30/2026 1:22:11 pm',
            ' * VERSION: v003' . "\n" . ' * LAST MODIFIED: 9/6/2026 1:18:32 am',
            $updated,
            $countHeader
        );

        $changelogNeedle = <<<'TXT'
 * CHANGELOG:
 *
 * v002 (3/30/2026)
TXT;

        $changelogReplacement = <<<'TXT'
 * CHANGELOG:
 *
 * v003 (9/6/2026)
 * - MRL Test Team (userID 999) now appears first in the View As dropdown.
 * - userID 999 is selected by default when no alternate user is currently active.
 * - If another alternate user is already active, that user remains selected.
 *
 * v002 (3/30/2026)
TXT;

        $updated = str_replace(
            $changelogNeedle,
            $changelogReplacement,
            $updated,
            $countChangelog
        );

        $updated = str_replace($oldSql, $newSql, $updated, $countSql);
        $updated = str_replace($oldOption, $newOption, $updated, $countOption);

        $updated = str_replace(
            'FILE: " . basename(__FILE__) . " | VERSION: v002 | ',
            'FILE: " . basename(__FILE__) . " | VERSION: v003 | ',
            $updated,
            $countFooter
        );

        $allCountsOk = $countHeader === 1
            && $countChangelog === 1
            && $countSql === 1
            && $countOption === 1
            && $countFooter === 1;

        if (!$allCountsOk) {
            $message = 'APPLY FAILED — target structure did not match the expected v002 baseline exactly. Nothing was changed.';
            $messageType = 'err';
        } else {
            if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
                $message = 'APPLY FAILED — could not create rollback directory.';
                $messageType = 'err';
            } elseif (!is_file($backupFile) && !@copy($target, $backupFile)) {
                $message = 'APPLY FAILED — could not create rollback copy.';
                $messageType = 'err';
            } else {
                [$ok, $err] = tvai_write_atomic($target, $updated);

                if (!$ok) {
                    $message = 'APPLY FAILED — ' . $err;
                    $messageType = 'err';
                } else {
                    [$syntaxOk, $syntaxReport] = tvai_php_syntax_check($target);

                    if ($syntaxOk === false) {
                        @copy($backupFile, $target);
                        $message = 'APPLY FAILED — PHP syntax check failed. The original v002 file was restored. ' . $syntaxReport;
                        $messageType = 'err';
                    } else {
                        $message = 'INSTALL COMPLETE — team_view_as.php is now v003. MRL Test Team (ID 999) is first and is the default when no alternate user is active.';
                        $messageType = 'ok';
                    }
                }
            }
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'rollback') {
    if (!is_file($backupFile)) {
        $message = 'ROLLBACK NOT AVAILABLE — no rollback copy exists yet.';
        $messageType = 'err';
    } elseif (!@copy($backupFile, $target)) {
        $message = 'ROLLBACK FAILED — could not restore the original team_view_as.php.';
        $messageType = 'err';
    } else {
        $message = 'ROLLBACK COMPLETE — original team_view_as.php v002 restored.';
        $messageType = 'ok';
    }
}

$after = is_file($target) ? (string)@file_get_contents($target) : '';
$currentVersion = 'unknown';
if (preg_match('/VERSION:\s*(v\d+)/', $after, $m)) {
    $currentVersion = $m[1];
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Install Team View As MRL Default v001</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root { color-scheme: dark; }
* { box-sizing: border-box; }
body {
    margin: 0;
    background: #0d1013;
    color: #eef2f6;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
}
.wrap {
    max-width: 1000px;
    margin: 20px auto 50px;
    padding: 0 16px;
}
.card {
    background: #1b1f23;
    border: 1px solid #3b4249;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 14px;
}
h1 {
    margin: 0 0 8px;
    color: #ffcf83;
    font-size: 30px;
}
h2 {
    margin: 0 0 14px;
    color: #ffcf83;
    font-size: 22px;
}
.sub {
    color: #c8d0d8;
    line-height: 1.5;
}
.info {
    background: #12344a;
    border: 1px solid #1d6f9d;
    border-radius: 8px;
    padding: 12px;
    line-height: 1.55;
}
.okbox {
    background: #104d2d;
    border: 1px solid #218750;
    border-radius: 8px;
    padding: 13px;
    font-weight: bold;
}
.errbox {
    background: #671f1f;
    border: 1px solid #c83a3a;
    border-radius: 8px;
    padding: 13px;
    font-weight: bold;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 10px 8px;
    border-bottom: 1px solid #3a4249;
    text-align: left;
    vertical-align: top;
}
th {
    color: #ffd27f;
    font-size: 12px;
}
.pass {
    color: #57e38c;
    font-weight: bold;
}
.fail {
    color: #ff7373;
    font-weight: bold;
}
.infoStatus {
    color: #8fc8ff;
    font-weight: bold;
}
code, .mono {
    font-family: Consolas, Menlo, monospace;
    color: #ffd27f;
}
.btn {
    border: 0;
    border-radius: 7px;
    padding: 11px 16px;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
}
.btn-safe {
    background: #23864b;
    color: #fff;
}
.btn-danger {
    background: #ca2e2e;
    color: #fff;
}
.btn-disabled {
    background: #666;
    color: #ccc;
    cursor: not-allowed;
}
a {
    color: #8fc8ff;
}
.small {
    font-size: 12px;
    color: #aeb8c1;
}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
    <h1>Install Team View As MRL Default v001</h1>
    <div class="sub">
        Small convenience update for <code>team_view_as.php</code>.
        MRL Test Team (ID 999) will appear first and will be selected by default when no alternate user is currently active.
    </div>

    <div class="info" style="margin-top:14px">
        <strong>What does not change:</strong> no database changes, no Team-page logic changes,
        no automatic View-As switch, and no submission occurs until you click <strong>Set Alternate User</strong>.
        If another alternate user is already active, that user remains selected instead of snapping back to 999.
    </div>
</div>

<?php if ($message !== ''): ?>
<div class="<?php echo $messageType === 'ok' ? 'okbox' : 'errbox'; ?>" style="margin-bottom:14px">
    <?php echo tvai_h($message); ?>
    <?php if ($messageType === 'ok' && strpos($message, 'INSTALL COMPLETE') !== false): ?>
        &nbsp; <a href="/team_view_as.php">Open Team View As</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <h2>Preflight</h2>
    <p class="sub">All required checks must pass before Apply is enabled.</p>

    <table>
        <thead>
            <tr>
                <th>CHECK</th>
                <th>TYPE</th>
                <th>STATUS</th>
                <th>DETAILS</th>
            </tr>
        </thead>
        <tbody>
        <tr>
            <td>team_view_as.php exists</td>
            <td>Required</td>
            <td class="<?php echo $sourceExists ? 'pass' : 'fail'; ?>"><?php echo $sourceExists ? 'PASS' : 'FAIL'; ?></td>
            <td><span class="mono"><?php echo tvai_h($target); ?></span></td>
        </tr>
        <tr>
            <td>team_view_as.php writable</td>
            <td>Required</td>
            <td class="<?php echo $sourceWritable ? 'pass' : 'fail'; ?>"><?php echo $sourceWritable ? 'PASS' : 'FAIL'; ?></td>
            <td>Needed to replace the target after the rollback copy is created.</td>
        </tr>
        <tr>
            <td>Expected v002 baseline</td>
            <td>Required</td>
            <td class="<?php echo $expectedVersion ? 'pass' : 'fail'; ?>"><?php echo $expectedVersion ? 'PASS' : 'FAIL'; ?></td>
            <td>Confirms the live file matches the current GitHub v002 generation.</td>
        </tr>
        <tr>
            <td>Sort-order anchor</td>
            <td>Required</td>
            <td class="<?php echo $hasSqlAnchor ? 'pass' : 'fail'; ?>"><?php echo $hasSqlAnchor ? 'PASS' : 'FAIL'; ?></td>
            <td>Confirms the current alphabetical user query is present exactly once.</td>
        </tr>
        <tr>
            <td>Dropdown option anchor</td>
            <td>Required</td>
            <td class="<?php echo $hasOptionAnchor ? 'pass' : 'fail'; ?>"><?php echo $hasOptionAnchor ? 'PASS' : 'FAIL'; ?></td>
            <td>Confirms the current option rendering block is present exactly once.</td>
        </tr>
        <tr>
            <td>public_html writable</td>
            <td>Required</td>
            <td class="<?php echo $publicWritable ? 'pass' : 'fail'; ?>"><?php echo $publicWritable ? 'PASS' : 'FAIL'; ?></td>
            <td>Needed for the rollback folder.</td>
        </tr>
        <tr>
            <td>Current detected version</td>
            <td>Info</td>
            <td class="infoStatus"><?php echo tvai_h($currentVersion); ?></td>
            <td>Expected before Apply: v002. Expected after Apply: v003.</td>
        </tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Apply</h2>
    <p class="sub">
        Creates a rollback copy first, changes only <code>team_view_as.php</code>,
        syntax-checks the updated file when PHP CLI is available, and automatically restores v002 if that syntax check fails.
    </p>

    <form method="post">
        <input type="hidden" name="action" value="apply">
        <button type="submit"
                class="btn <?php echo $requiredPass ? 'btn-safe' : 'btn-disabled'; ?>"
                <?php echo $requiredPass ? '' : 'disabled'; ?>>
            Apply Team View As Update v001
        </button>
    </form>
</div>

<div class="card">
    <h2>Rollback</h2>
    <p class="sub">Restores the exact pre-install <code>team_view_as.php</code> from this installer's rollback copy.</p>

    <form method="post" onsubmit="return confirm('Restore the original team_view_as.php v002?');">
        <input type="hidden" name="action" value="rollback">
        <button type="submit" class="btn btn-danger">Rollback</button>
    </form>
</div>

<div class="small" style="text-align:right">install_team_view_as_mrl_default_v001_20260906_011832am.php</div>

</div>
</body>
</html>
