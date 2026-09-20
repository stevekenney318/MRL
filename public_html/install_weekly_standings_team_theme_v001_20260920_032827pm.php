<?php
declare(strict_types=1);

/**
 * install_weekly_standings_team_theme_v001_20260920_032827pm.php
 *
 * VERSION: v001
 * GENERATED: 9/20/2026 3:28:27 pm ET
 *
 * PURPOSE:
 * - Adds the logged-in user's Team theme to race_results/weekly_standings.php.
 * - Uses the existing shared MRL theme helper and shared theme stylesheet.
 * - Keeps report tables, scoring colors, print/PDF, spreadsheet, validation,
 *   audit, release/version controls, and scoring logic unchanged.
 *
 * SAFETY:
 * - Modifies only /public_html/race_results/weekly_standings.php.
 * - Expected baseline: v072.
 * - Candidate + installed PHP lint.
 * - Backup + rollback.
 */

date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION = 'v072';
const TARGET_VERSION = 'v073';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/race_results/weekly_standings.php';
$backupDir = $docRoot . '/_installer_backups/weekly_standings_team_theme_20260920_032827pm';
$backupFile = $backupDir . '/weekly_standings.php';

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function lint_php_file(string $path): array
{
    if (!function_exists('shell_exec')) {
        return ['ok'=>false,'output'=>'shell_exec() is not available.'];
    }

    $out = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');

    if ($out === null) {
        return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    }

    $out = trim((string)$out);

    return [
        'ok'=>stripos($out, 'No syntax errors detected') !== false,
        'output'=>$out,
    ];
}

function current_version(string $content): string
{
    if (preg_match('/\*\s*VERSION:\s*(v\d+)/i', $content, $m)) {
        return (string)$m[1];
    }
    return '';
}

function replace_once(string $subject, string $search, string $replace, string $label, array &$errors): string
{
    $count = substr_count($subject, $search);

    if ($count !== 1) {
        $errors[] = $label . ': expected exactly 1 match, found ' . $count . '.';
        return $subject;
    }

    $pos = strpos($subject, $search);

    if ($pos === false) {
        $errors[] = $label . ': anchor not found.';
        return $subject;
    }

    return substr($subject, 0, $pos)
        . $replace
        . substr($subject, $pos + strlen($search));
}

function build_candidate(string $source, array &$errors): string
{
    $out = $source;

    $out = replace_once(
        $out,
        ' * VERSION: v072',
        ' * VERSION: v073',
        'Version header',
        $errors
    );

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace(
            $m[0],
            ' * LAST MODIFIED: 9/20/2026 3:28:27 pm ET' . PHP_EOL,
            $out
        );
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelogInsert = " * CHANGELOG:\n *\n"
        . " * v073 (9/20/2026 3:28:27 pm ET)\n"
        . " *   - THEME: Weekly Standings now uses the logged-in user's Team theme (Cars, Starry Night, Dark, or Light).\n"
        . " *   - THEME: Reuses /mrl_team/mrl_theme_helper.php and /mrl_team/mrl_shared_theme.css; no duplicate theme engine added.\n"
        . " *   - READABILITY: Existing light report tables remain light with dark text while page-level text adapts to the selected theme.\n"
        . " *   - PRINT: Explicitly restores white/blank print background so Team themes do not alter clean or full PDF output.\n"
        . " *   - PRESERVE: Scoring, snapshots, validation, audit, release history, Pending Review, navigation, spreadsheet export, print controls, and report table colors unchanged.\n"
        . " *\n";

    $out = replace_once(
        $out,
        $changelogAnchor,
        $changelogInsert,
        'Changelog anchor',
        $errors
    );

    $requireAnchor = "require_once \$_SERVER['DOCUMENT_ROOT'] . '/functions_mrl.php';\n";
    $requireReplacement = <<<'PHPBLOCK'
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/mrl_team/mrl_theme_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$weeklyStandingsTheme = 'dark';

try {
    $weeklyThemeUid = (int)($_SESSION['userSession'] ?? 0);

    if (isset($dbo) && $dbo instanceof PDO && $weeklyThemeUid > 0) {
        $weeklyStandingsTheme = mrl_theme_get($dbo, $weeklyThemeUid);
    }
} catch (Throwable $e) {
    $weeklyStandingsTheme = 'dark';
}

PHPBLOCK;

    $out = replace_once(
        $out,
        $requireAnchor,
        $requireReplacement,
        'Theme helper/bootstrap anchor',
        $errors
    );

    $out = replace_once(
        $out,
        "<html>\n<head>\n    <meta charset=\"UTF-8\">\n    <title>Weekly Standings</title>",
        "<html class=\"mrl-theme-<?php echo rrsg_h(\$weeklyStandingsTheme); ?>\">\n<head>\n    <meta charset=\"UTF-8\">\n    <title>Weekly Standings</title>\n    <link rel=\"stylesheet\" href=\"/mrl_team/mrl_shared_theme.css?v=001\">",
        'HTML theme class / stylesheet anchor',
        $errors
    );

    $oldCss = <<<'CSS'
        html {
            scrollbar-gutter: stable;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
            line-height: 1.3;
            margin: 12px;
            color: #111;
        }

        body.release-superseded-view {
            background: rgba(255, 0, 0, 0.10);
        }

        .page-wrap {
            /* max-width: 1750px; */
            max-width: 1400px;
            margin: 0 auto;
        }
CSS;

    $newCss = <<<'CSS'
        html {
            min-height: 100%;
            scrollbar-gutter: stable;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
            line-height: 1.3;
            margin: 12px;
            color: var(--mrl-rd-text);
            background: transparent !important;
        }

        body.release-superseded-view {
            background: rgba(255, 0, 0, 0.10) !important;
        }

        .page-wrap {
            /* max-width: 1750px; */
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Keep the established report itself light/readable over any Team theme. */
        table,
        th,
        td {
            color: #111;
        }

        .historical-note-row {
            color: var(--mrl-rd-muted) !important;
        }
CSS;

    $out = replace_once(
        $out,
        $oldCss,
        $newCss,
        'Base page/theme CSS anchor',
        $errors
    );

    $printAnchor = "@media print {";
    $printInsert = <<<'CSS'
@media print {
            html,
            html.mrl-theme-cars,
            html.mrl-theme-starry-night,
            html.mrl-theme-dark,
            html.mrl-theme-light,
            body,
            body.release-superseded-view {
                background: #ffffff !important;
                background-image: none !important;
                color: #111111 !important;
            }
CSS;

    // Replace only the first print media opener with an opener plus explicit theme reset.
    $out = replace_once(
        $out,
        $printAnchor,
        $printInsert,
        'Print theme reset anchor',
        $errors
    );

    return $out;
}

function candidate_lint(string $candidate, string $targetDir): array
{
    $tmp = @tempnam($targetDir, '.mrl_weekly_theme_');

    if ($tmp === false) {
        $tmp = @tempnam(sys_get_temp_dir(), 'mrl_weekly_theme_');
    }

    if ($tmp === false) {
        return ['ok'=>false,'output'=>'Could not create temporary lint file.'];
    }

    if (@file_put_contents($tmp, $candidate, LOCK_EX) === false) {
        @unlink($tmp);
        return ['ok'=>false,'output'=>'Could not write temporary lint file.'];
    }

    $lint = lint_php_file($tmp);
    @unlink($tmp);

    return $lint;
}

$action = (string)($_POST['action'] ?? '');
$message = '';
$messageClass = 'info';

$targetExists = is_file($target);
$targetWritable = $targetExists && is_writable($target);
$source = $targetExists ? (string)@file_get_contents($target) : '';
$version = $source !== '' ? current_version($source) : '';
$alreadyInstalled = ($version === TARGET_VERSION);

$errors = [];
$candidate = '';
$candidateLint = ['ok'=>false,'output'=>'Not run.'];

if ($targetExists && $version === EXPECTED_SOURCE_VERSION) {
    $candidate = build_candidate($source, $errors);

    if (empty($errors)) {
        $candidateLint = candidate_lint($candidate, dirname($target));
    }
}

$installedLint = $targetExists
    ? lint_php_file($target)
    : ['ok'=>false,'output'=>'Target missing.'];

$backupExists = is_file($backupFile);

$canApply =
    $targetExists
    && $targetWritable
    && $version === EXPECTED_SOURCE_VERSION
    && empty($errors)
    && !empty($candidateLint['ok']);

if ($action === 'apply') {
    if (!$canApply) {
        $message = 'Apply blocked: preflight is not fully PASS.';
        $messageClass = 'bad';
    } else {
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            $message = 'Apply blocked: backup directory could not be created.';
            $messageClass = 'bad';
        } elseif (is_file($backupFile)) {
            $message = 'Apply blocked: backup already exists for this installer run.';
            $messageClass = 'bad';
        } elseif (!@copy($target, $backupFile)) {
            $message = 'Apply blocked: backup could not be created.';
            $messageClass = 'bad';
        } else {
            @chmod($backupFile, 0644);

            $tmpTarget = $target . '.mrl_tmp_' . uniqid('', true);

            if (@file_put_contents($tmpTarget, $candidate, LOCK_EX) === false) {
                @unlink($tmpTarget);
                $message = 'Apply failed: temporary target could not be written.';
                $messageClass = 'bad';
            } else {
                @chmod($tmpTarget, 0644);
                $tmpLint = lint_php_file($tmpTarget);

                if (empty($tmpLint['ok'])) {
                    @unlink($tmpTarget);
                    $message = 'Apply blocked: temporary replacement failed PHP lint. ' . $tmpLint['output'];
                    $messageClass = 'bad';
                } elseif (!@rename($tmpTarget, $target)) {
                    @unlink($tmpTarget);
                    $message = 'Apply failed: atomic replacement could not be completed.';
                    $messageClass = 'bad';
                } else {
                    @chmod($target, 0644);
                    $newLint = lint_php_file($target);

                    if (empty($newLint['ok'])) {
                        @copy($backupFile, $target);
                        @chmod($target, 0644);
                        $message = 'Installed file failed lint and backup was automatically restored. ' . $newLint['output'];
                        $messageClass = 'bad';
                    } else {
                        $message = 'PASS — weekly_standings.php v073 installed and passed PHP lint.';
                        $messageClass = 'good';
                    }
                }
            }
        }
    }
}

if ($action === 'rollback') {
    if (!is_file($backupFile)) {
        $message = 'Rollback unavailable: backup not found.';
        $messageClass = 'bad';
    } else {
        $backupLint = lint_php_file($backupFile);

        if (empty($backupLint['ok'])) {
            $message = 'Rollback blocked: backup failed lint. ' . $backupLint['output'];
            $messageClass = 'bad';
        } elseif (!@copy($backupFile, $target)) {
            $message = 'Rollback failed: backup could not be restored.';
            $messageClass = 'bad';
        } else {
            @chmod($target, 0644);
            $restoredLint = lint_php_file($target);

            if (empty($restoredLint['ok'])) {
                $message = 'Rollback copy completed, but restored file failed lint. ' . $restoredLint['output'];
                $messageClass = 'bad';
            } else {
                $message = 'Rollback complete — weekly_standings.php v072 restored and lint passed.';
                $messageClass = 'good';
            }
        }
    }
}

$source = is_file($target) ? (string)@file_get_contents($target) : '';
$version = $source !== '' ? current_version($source) : '';
$alreadyInstalled = ($version === TARGET_VERSION);
$backupExists = is_file($backupFile);
$installedLint = is_file($target)
    ? lint_php_file($target)
    : ['ok'=>false,'output'=>'Target missing.'];

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Weekly Standings Team Theme</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1100px;margin:0 auto}
h1{margin:0 0 6px;color:var(--gold)}
h2{margin:0 0 10px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.notice{margin:14px 0;padding:12px 14px;border-radius:10px;border:1px solid #36506f;background:#142033}
.notice.good{border-color:#327a4b;background:#13271a;color:#a8efbf}
.notice.bad{border-color:#983f3f;background:#2a1515;color:#ffb0b0}
.pass{color:var(--good);font-weight:800}.fail{color:var(--bad);font-weight:800}.muted{color:var(--muted)}
code{background:#282828;padding:2px 5px;border-radius:5px}
table{width:100%;border-collapse:collapse}
th,td{padding:9px 10px;border-bottom:1px solid #333;text-align:left;vertical-align:top}
th{color:var(--gold)}
.buttons{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;font-size:14px}
.apply{background:#248c4b}.neutral{background:#276fca}.rollback{background:#a83434}
button:disabled{opacity:.38;cursor:not-allowed}
ul{line-height:1.5}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Weekly Standings Team Theme</h1>
    <div class="muted">Installer v001 · generated 9/20/2026 3:28:27 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>What this installs</h2>
        <ul>
            <li>Uses the logged-in user's existing Team theme: Cars, Starry Night, Dark, or Light.</li>
            <li>Reuses the same shared theme helper and stylesheet already used by Team Chart.</li>
            <li>Keeps the four standings tables light with dark text so standings readability and familiar colors stay unchanged.</li>
            <li>Print/PDF explicitly returns to a white blank background.</li>
            <li>Spreadsheet generation is untouched.</li>
        </ul>
    </div>

    <div class="card">
        <h2>Preflight</h2>
        <table>
            <tr><th>Check</th><th>Status</th><th>Detail</th></tr>
            <tr>
                <td>Target exists</td>
                <td class="<?php echo $targetExists ? 'pass' : 'fail'; ?>"><?php echo $targetExists ? 'PASS' : 'FAIL'; ?></td>
                <td><code><?php echo h($target); ?></code></td>
            </tr>
            <tr>
                <td>Target writable</td>
                <td class="<?php echo $targetWritable ? 'pass' : 'fail'; ?>"><?php echo $targetWritable ? 'PASS' : 'FAIL'; ?></td>
                <td><?php echo $targetWritable ? 'Writable' : 'Not writable'; ?></td>
            </tr>
            <tr>
                <td>Current version</td>
                <td class="<?php echo ($version === EXPECTED_SOURCE_VERSION || $alreadyInstalled) ? 'pass' : 'fail'; ?>">
                    <?php echo ($version === EXPECTED_SOURCE_VERSION || $alreadyInstalled) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td>Detected <strong><?php echo h($version !== '' ? $version : '(unknown)'); ?></strong>; expected v072 before Apply, or v073 after Apply.</td>
            </tr>
            <tr>
                <td>Patch signatures</td>
                <td class="<?php echo ($alreadyInstalled || empty($errors)) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || empty($errors)) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h($alreadyInstalled ? 'v073 already installed.' : (empty($errors) ? 'Expected v072 theme anchors found exactly once.' : implode(' | ', $errors))); ?></td>
            </tr>
            <tr>
                <td>Candidate PHP lint</td>
                <td class="<?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h($alreadyInstalled ? 'Not needed — v073 installed.' : (string)$candidateLint['output']); ?></td>
            </tr>
            <tr>
                <td>Installed PHP lint</td>
                <td class="<?php echo !empty($installedLint['ok']) ? 'pass' : 'fail'; ?>">
                    <?php echo !empty($installedLint['ok']) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo !empty($installedLint['ok']) ? 'PASS — ' : 'FAIL — '; ?><?php echo h((string)$installedLint['output']); ?></td>
            </tr>
            <tr>
                <td>Rollback backup</td>
                <td><?php echo $backupExists ? '<span class="pass">READY</span>' : '<span class="muted">Not created yet</span>'; ?></td>
                <td><code><?php echo h($backupFile); ?></code></td>
            </tr>
        </table>

        <div class="buttons">
            <form method="post">
                <input type="hidden" name="action" value="apply">
                <button class="apply" type="submit" <?php echo $canApply ? '' : 'disabled'; ?>>Apply v073</button>
            </form>

            <a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF'] ?? '')); ?>">Refresh / Preflight</a>
            <a class="btn neutral" href="/race_results/weekly_standings.php" target="_blank" rel="noopener">Open Weekly Standings</a>

            <form method="post" onsubmit="return confirm('Restore the backed-up v072 Weekly Standings?');">
                <input type="hidden" name="action" value="rollback">
                <button class="rollback" type="submit" <?php echo $backupExists ? '' : 'disabled'; ?>>Rollback Weekly Standings</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
