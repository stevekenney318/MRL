<?php
declare(strict_types=1);

/**
 * install_team_chart_presentation_cleanup_v001_20260920_010207pm.php
 *
 * VERSION: v001
 * GENERATED: 9/20/2026 1:02:07 pm ET
 *
 * PURPOSE:
 * - Team Chart presentation / print cleanup, Pass 1.
 * - Updates public_html/team_chart.php from v021 to v022.
 * - Keeps spreadsheet logic unchanged in this pass.
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v001';
const EXPECTED_SOURCE_VERSION = 'v021';
const TARGET_VERSION = 'v022';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/team_chart.php';
$backupDir = $docRoot . '/_installer_backups/team_chart_presentation_cleanup_20260920_010207pm';
$backupFile = $backupDir . '/team_chart.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function lint_php_file(string $path): array
{
    if (!function_exists('shell_exec')) {
        return ['available'=>false,'ok'=>false,'output'=>'shell_exec() is not available.'];
    }

    $output = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');

    if ($output === null) {
        return ['available'=>true,'ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    }

    $output = trim((string)$output);

    return [
        'available'=>true,
        'ok'=>stripos($output, 'No syntax errors detected') !== false,
        'output'=>$output,
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
        $errors[] = $label . ': match disappeared unexpectedly.';
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
        ' * VERSION: v021',
        ' * VERSION: v022',
        'Version header',
        $errors
    );

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace(
            $m[0],
            ' * LAST MODIFIED: 9/20/2026 1:02:07 pm ET' . PHP_EOL,
            $out
        );
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelogInsert = " * CHANGELOG:\n *\n"
        . " * v022 (9/20/2026 1:02:07 pm ET)\n"
        . " * - UI: Standalone Team Chart now uses a wider 85% report width, matching the Team Page chart feel more closely.\n"
        . " * - UI: Top controls now follow the flatter Weekly Standings visual language: slimmer selects/buttons, no select shadow, tighter spacing, and matching report-action styling.\n"
        . " * - UI: Live is now a blue pill-shaped control with the same enabled/disabled treatment used by Weekly Standings.\n"
        . " * - PRINT: Team theme/background image is explicitly removed for print/PDF while chart header/cell colors remain preserved.\n"
        . " * - PRESERVE: Existing privacy gate, navigation behavior, LP/RD display, Print filename logic, Spreadsheet export, and PhpSpreadsheet dependency are unchanged in this pass.\n"
        . " *\n";

    $out = replace_once(
        $out,
        $changelogAnchor,
        $changelogInsert,
        'Changelog anchor',
        $errors
    );

    $styleAnchor = "        .teamchart-note-line + .teamchart-note-line {\n            margin-top: 4px;\n        }\n";

    $styleInsert = $styleAnchor . <<<'CSS'

        /* =========================================================
           v022 — Team Chart report-control unification
           Visual baseline: Weekly Standings.
           ========================================================= */

        .teamchart-container {
            width: 85% !important;
            max-width: 1600px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        .teamchart-form {
            margin: 4px 0 6px 0 !important;
        }

        .teamchart-row {
            gap: 6px 10px !important;
        }

        .teamchart-select {
            width: 120px !important;
            height: 28px !important;
            box-sizing: border-box !important;
            font: 16px/1.2 Arial, Helvetica, sans-serif !important;
            padding: 1px 8px !important;
            border: 1px solid #999 !important;
            border-radius: 3px !important;
            background: #f2f2f2 !important;
            color: #111 !important;
            box-shadow: none !important;
        }

        .teamchart-actionbtn {
            min-height: 28px !important;
            height: 28px !important;
            box-sizing: border-box !important;
            font: 16px/1.2 Arial, Helvetica, sans-serif !important;
            padding: 1px 8px !important;
            border: 1px solid #999 !important;
            border-radius: 3px !important;
            background: #f2f2f2 !important;
            color: #111 !important;
            box-shadow: none !important;
        }

        .teamchart-actionbtn:hover:not(:disabled) {
            filter: brightness(0.96);
        }

        .teamchart-navpair {
            gap: 4px !important;
        }

        .teamchart-navpair .teamchart-actionbtn {
            min-width: 34px !important;
            padding-left: 6px !important;
            padding-right: 6px !important;
        }

        .teamchart-actions {
            gap: 6px 10px !important;
        }

        .teamchart-actions .teamchart-actionbtn {
            min-width: 92px !important;
            border: 2px solid #777 !important;
        }

        #btnLive {
            min-width: 66px !important;
            height: 30px !important;
            padding: 1px 10px !important;
            font-weight: bold !important;
            border-radius: 18px !important;
            background: #d9ecff !important;
            color: #084298 !important;
            border: 3px solid #7db7ff !important;
        }

        #btnLive:hover:not(:disabled) {
            filter: brightness(0.97);
        }

        #btnLive:disabled {
            cursor: default !important;
            opacity: 0.5 !important;
            color: #5f6f82 !important;
            background: #eef5fb !important;
            border-color: #c5d7e7 !important;
            filter: none !important;
        }

        .teamchart-actionbtn:disabled {
            cursor: default !important;
            opacity: 0.5 !important;
            color: #666 !important;
            background: #f3f3f3 !important;
            filter: none !important;
        }

        .teamchart-table {
            width: 100% !important;
            display: table !important;
        }

        @media print {
            @page {
                size: landscape;
                margin: 0.5in;
            }

            html,
            html.mrl-theme-cars,
            html.mrl-theme-starry-night,
            html.mrl-theme-dark,
            html.mrl-theme-light,
            body,
            html.mrl-theme-cars body,
            html.mrl-theme-starry-night body,
            html.mrl-theme-dark body,
            html.mrl-theme-light body {
                background: #ffffff !important;
                background-image: none !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
            }

            .teamchart-no-print,
            .admin-status {
                display: none !important;
            }

            .teamchart-container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
            }

            .teamchart-scroll {
                overflow: visible !important;
            }

            .teamchart-table {
                width: 100% !important;
                margin: 0 auto !important;
                display: table !important;
            }
        }
CSS;

    $out = replace_once(
        $out,
        $styleAnchor,
        $styleInsert,
        'Inline style anchor',
        $errors
    );

    return $out;
}

function candidate_lint(string $candidate, string $targetDir): array
{
    $tmp = @tempnam($targetDir, '.mrl_team_chart_lint_');

    if ($tmp === false) {
        $tmp = @tempnam(sys_get_temp_dir(), 'mrl_team_chart_lint_');
    }

    if ($tmp === false) {
        return ['available'=>true,'ok'=>false,'output'=>'Unable to create temporary lint file.'];
    }

    if (@file_put_contents($tmp, $candidate, LOCK_EX) === false) {
        @unlink($tmp);
        return ['available'=>true,'ok'=>false,'output'=>'Unable to write temporary lint file.'];
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
$detectedVersion = $source !== '' ? current_version($source) : '';
$alreadyInstalled = ($detectedVersion === TARGET_VERSION);

$patchErrors = [];
$candidate = '';
$candidateLint = ['available'=>function_exists('shell_exec'),'ok'=>false,'output'=>'Not run.'];

if ($targetExists && $detectedVersion === EXPECTED_SOURCE_VERSION) {
    $candidate = build_candidate($source, $patchErrors);
    if (empty($patchErrors)) {
        $candidateLint = candidate_lint($candidate, dirname($target));
    }
}

$canApply =
    $targetExists
    && $targetWritable
    && $detectedVersion === EXPECTED_SOURCE_VERSION
    && empty($patchErrors)
    && !empty($candidateLint['available'])
    && !empty($candidateLint['ok']);

if ($action === 'apply') {
    if (!$canApply) {
        $message = 'Apply blocked: preflight is not fully PASS.';
        $messageClass = 'bad';
    } else {
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            $message = 'Apply blocked: could not create backup directory.';
            $messageClass = 'bad';
        } elseif (is_file($backupFile)) {
            $message = 'Apply blocked: backup already exists for this installer run.';
            $messageClass = 'bad';
        } elseif (!@copy($target, $backupFile)) {
            $message = 'Apply blocked: could not create backup.';
            $messageClass = 'bad';
        } else {
            @chmod($backupFile, 0644);

            $tmpTarget = $target . '.mrl_tmp_' . uniqid('', true);

            if (@file_put_contents($tmpTarget, $candidate, LOCK_EX) === false) {
                @unlink($tmpTarget);
                $message = 'Apply failed: could not write temporary target file.';
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
                    $installedLint = lint_php_file($target);

                    if (empty($installedLint['ok'])) {
                        @copy($backupFile, $target);
                        @chmod($target, 0644);
                        $message = 'Installed file failed lint and backup was automatically restored. ' . $installedLint['output'];
                        $messageClass = 'bad';
                    } else {
                        $message = 'PASS — team_chart.php v022 installed and passed PHP lint.';
                        $messageClass = 'good';
                    }
                }
            }
        }
    }
}

if ($action === 'rollback') {
    if (!is_file($backupFile)) {
        $message = 'Rollback unavailable: backup file was not found.';
        $messageClass = 'bad';
    } else {
        $backupLint = lint_php_file($backupFile);

        if (empty($backupLint['ok'])) {
            $message = 'Rollback blocked: backup file failed PHP lint. ' . $backupLint['output'];
            $messageClass = 'bad';
        } elseif (!@copy($backupFile, $target)) {
            $message = 'Rollback failed: could not restore backup.';
            $messageClass = 'bad';
        } else {
            @chmod($target, 0644);
            $restoredLint = lint_php_file($target);

            if (empty($restoredLint['ok'])) {
                $message = 'Rollback copy completed, but restored file did not pass lint. ' . $restoredLint['output'];
                $messageClass = 'bad';
            } else {
                $message = 'Rollback complete — original team_chart.php restored and lint passed.';
                $messageClass = 'good';
            }
        }
    }
}

$targetExists = is_file($target);
$targetWritable = $targetExists && is_writable($target);
$source = $targetExists ? (string)@file_get_contents($target) : '';
$detectedVersion = $source !== '' ? current_version($source) : '';
$alreadyInstalled = ($detectedVersion === TARGET_VERSION);
$backupExists = is_file($backupFile);

$installedLint = $targetExists
    ? lint_php_file($target)
    : ['available'=>function_exists('shell_exec'),'ok'=>false,'output'=>'Target missing.'];

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Team Chart Presentation Cleanup</title>
<style>
:root {
    color-scheme: dark;
    --bg:#101010; --panel:#1b1b1b; --line:#3d3d3d; --text:#eeeeee;
    --muted:#aaaaaa; --good:#66df8d; --bad:#ff7474; --gold:#f2c98e;
}
* { box-sizing:border-box; }
body {
    margin:0; padding:22px; background:var(--bg); color:var(--text);
    font-family:Arial,Helvetica,sans-serif;
}
.wrap { max-width:1100px; margin:0 auto; }
h1 { margin:0 0 6px; color:var(--gold); }
h2 { margin:0 0 10px; }
.card {
    margin:14px 0; padding:16px; background:var(--panel);
    border:1px solid var(--line); border-radius:14px;
}
.notice {
    margin:14px 0; padding:12px 14px; border-radius:10px;
    border:1px solid #36506f; background:#142033;
}
.notice.good { border-color:#327a4b; background:#13271a; color:#a8efbf; }
.notice.bad { border-color:#983f3f; background:#2a1515; color:#ffb0b0; }
.pass { color:var(--good); font-weight:800; }
.fail { color:var(--bad); font-weight:800; }
.muted { color:var(--muted); }
code { background:#282828; padding:2px 5px; border-radius:5px; }
table { width:100%; border-collapse:collapse; }
th,td { padding:9px 10px; border-bottom:1px solid #333; text-align:left; vertical-align:top; }
th { color:var(--gold); }
.buttons { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
button,.btn {
    border:0; border-radius:9px; padding:10px 16px; color:white;
    font-weight:700; cursor:pointer; text-decoration:none; display:inline-block;
    font-size:14px;
}
.apply { background:#248c4b; }
.neutral { background:#276fca; }
.rollback { background:#a83434; }
button:disabled { opacity:.38; cursor:not-allowed; }
ul { line-height:1.55; }
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Team Chart Presentation Cleanup</h1>
    <div class="muted">Installer <?php echo h(INSTALLER_VERSION); ?> · Pass 1 · generated 9/20/2026 1:02:07 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>What this pass changes</h2>
        <ul>
            <li>Widens the standalone Team Chart to the Team Page-style report width.</li>
            <li>Restyles Team Chart controls to the flatter Weekly Standings pattern.</li>
            <li>Makes Live a blue pill-shaped control.</li>
            <li>Removes dropdown/control shadows and tightens control height/spacing.</li>
            <li>Forces white/blank print background while preserving Team Chart colors.</li>
            <li>Leaves spreadsheet/vendor/Composer logic unchanged for Pass 2.</li>
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
                <td class="<?php echo ($detectedVersion === EXPECTED_SOURCE_VERSION || $alreadyInstalled) ? 'pass' : 'fail'; ?>">
                    <?php echo ($detectedVersion === EXPECTED_SOURCE_VERSION || $alreadyInstalled) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td>Detected <strong><?php echo h($detectedVersion !== '' ? $detectedVersion : '(unknown)'); ?></strong>; expected v021 before Apply, or v022 after Apply.</td>
            </tr>
            <tr>
                <td>Patch signatures</td>
                <td class="<?php echo ($alreadyInstalled || empty($patchErrors)) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || empty($patchErrors)) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td>
                    <?php
                    if ($alreadyInstalled) {
                        echo 'v022 is already installed.';
                    } elseif (empty($patchErrors)) {
                        echo 'All expected v021 anchors found exactly once.';
                    } else {
                        echo h(implode(' | ', $patchErrors));
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Candidate PHP lint</td>
                <td class="<?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h($alreadyInstalled ? 'Not needed — installed target is v022.' : (string)$candidateLint['output']); ?></td>
            </tr>
            <tr>
                <td>Installed PHP lint</td>
                <td class="<?php echo !empty($installedLint['ok']) ? 'pass' : 'fail'; ?>">
                    <?php echo !empty($installedLint['ok']) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h((string)$installedLint['output']); ?></td>
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
                <button class="apply" type="submit" <?php echo $canApply ? '' : 'disabled'; ?>>Apply v022</button>
            </form>

            <a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF'] ?? '')); ?>">Refresh / Preflight</a>
            <a class="btn neutral" href="/team_chart.php" target="_blank" rel="noopener">Open Team Chart</a>

            <form method="post" onsubmit="return confirm('Restore the backed-up v021 Team Chart?');">
                <input type="hidden" name="action" value="rollback">
                <button class="rollback" type="submit" <?php echo $backupExists ? '' : 'disabled'; ?>>Rollback Team Chart</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Production safety</h2>
        <p>
            Target: <code><?php echo h($target); ?></code><br>
            Current detected version: <strong><?php echo h($detectedVersion !== '' ? $detectedVersion : '(unknown)'); ?></strong><br>
            Backup: <code><?php echo h($backupFile); ?></code>
        </p>
        <p class="muted">
            This installer modifies only <code>team_chart.php</code>. It does not change spreadsheet/vendor/Composer,
            Team Page, Weekly Standings, scheduler, cron, database, or picks.
        </p>
    </div>
</div>
</body>
</html>
