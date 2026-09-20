<?php
declare(strict_types=1);

/**
 * install_team_page_chart_actions_v001_20260920_024605pm.php
 *
 * VERSION: v001
 * GENERATED: 9/20/2026 2:46:05 pm ET
 *
 * PURPOSE:
 * - Adds Print + Spreadsheet controls directly above the current segment chart on team.php.
 * - Matches the latest standalone team_chart.php action-button look and right-edge placement.
 * - Print outputs only the current segment chart on white/blank background while preserving chart colors.
 * - Spreadsheet reuses team_chart.php's current pure-PHP XLSX export; no duplicate XLSX writer is added.
 *
 * SAFETY:
 * - Modifies only /public_html/team.php.
 * - Expected baseline: v053.
 * - Candidate and installed PHP are linted with shell_exec('php -l ...').
 * - Backup + rollback included.
 */

date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION = 'v053';
const TARGET_VERSION = 'v054';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/team.php';
$backupDir = $docRoot . '/_installer_backups/team_page_chart_actions_20260920_024605pm';
$backupFile = $backupDir . '/team.php';

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
        ' * VERSION: v053',
        ' * VERSION: v054',
        'Version header',
        $errors
    );

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace(
            $m[0],
            ' * LAST MODIFIED: 9/20/2026 2:46:05 pm ET' . PHP_EOL,
            $out
        );
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelogInsert = " * CHANGELOG:\n *\n"
        . " * v054 (9/20/2026 2:46:05 pm ET)\n"
        . " * - NEW: Adds Print and Spreadsheet controls directly above the current segment Team Chart on the Team page.\n"
        . " * - UI: Controls match the standalone Team Chart action-button theme and align to the chart's right edge.\n"
        . " * - PRINT: Prints only the current segment chart on a white/blank background while preserving chart colors and timestamped filename behavior.\n"
        . " * - EXPORT: Spreadsheet button reuses team_chart.php's current pure-PHP XLSX export for the active year/segment.\n"
        . " * - PRESERVE: Pick forms, LP/RD logic, scoring, themes, menus, scheduler, database behavior, and current_segment_chart.php remain unchanged.\n"
        . " *\n";

    $out = replace_once(
        $out,
        $changelogAnchor,
        $changelogInsert,
        'Changelog anchor',
        $errors
    );

    $includeAnchor = "                        echo \"</div>\";\n                        include 'current_segment_chart.php';";
    $includeReplacement = <<<'PHPBLOCK'
                        echo "</div>";
                        ?>
                        <div id="mrl-current-segment-chart-print"
                             class="mrl-current-segment-chart-print"
                             data-year="<?php echo teampage_h((string)$raceYear); ?>"
                             data-segment="<?php echo teampage_h((string)$segment); ?>">
                            <div class="mrl-current-chart-actions mrl-current-chart-no-print">
                                <button type="button" id="mrlCurrentChartPrint" class="mrl-current-chart-actionbtn">Print</button>
                                <button type="button" id="mrlCurrentChartSpreadsheet" class="mrl-current-chart-actionbtn">Spreadsheet</button>
                            </div>

                            <form id="mrlCurrentChartExcelForm"
                                  method="post"
                                  action="/team_chart.php"
                                  target="_blank"
                                  style="display:none;">
                                <input type="hidden" name="action" value="excel">
                                <input type="hidden" name="year" value="<?php echo teampage_h((string)$raceYear); ?>">
                                <input type="hidden" name="segment" value="<?php echo teampage_h((string)$segment); ?>">
                            </form>

                            <?php include 'current_segment_chart.php'; ?>
                        </div>
                        <?php
PHPBLOCK;

    $out = replace_once(
        $out,
        $includeAnchor,
        $includeReplacement,
        'Current segment chart include anchor',
        $errors
    );

    $styleAnchor = "        .mrl-rd-shell,\n        .mrl-rd-top,\n        .mrl-rd-chart-shell{";
    $styleInsert = <<<'CSSBLOCK'
        .mrl-current-chart-actions{
            display:flex;
            justify-content:flex-end;
            align-items:center;
            gap:10px;
            margin:0 0 8px 0;
            width:100%;
        }

        .mrl-current-chart-actionbtn{
            min-width:92px;
            height:28px;
            padding:1px 8px;
            border:2px solid #777;
            border-radius:3px;
            background:#f2f2f2;
            color:#111!important;
            box-shadow:none!important;
            font:16px/1.1 Arial,Helvetica,sans-serif;
            cursor:pointer;
        }

        .mrl-current-chart-actionbtn:hover{
            background:#ffffff;
        }

        @media print{
            html,
            html.mrl-theme-cars,
            html.mrl-theme-starry-night,
            html.mrl-theme-dark,
            html.mrl-theme-light,
            body,
            html.mrl-theme-cars body,
            html.mrl-theme-starry-night body,
            html.mrl-theme-dark body,
            html.mrl-theme-light body{
                background:#fff!important;
                background-image:none!important;
            }

            body.mrl-print-current-chart *{
                visibility:hidden!important;
            }

            body.mrl-print-current-chart #mrl-current-segment-chart-print,
            body.mrl-print-current-chart #mrl-current-segment-chart-print *{
                visibility:visible!important;
            }

            body.mrl-print-current-chart #mrl-current-segment-chart-print{
                position:absolute!important;
                left:0!important;
                top:0!important;
                width:100%!important;
                max-width:none!important;
                margin:0!important;
                padding:0!important;
                background:#fff!important;
            }

            body.mrl-print-current-chart .mrl-current-chart-no-print{
                display:none!important;
            }

            body.mrl-print-current-chart table{
                width:100%!important;
                -webkit-print-color-adjust:exact!important;
                print-color-adjust:exact!important;
            }
        }

CSSBLOCK;

    $out = replace_once(
        $out,
        $styleAnchor,
        $styleInsert . $styleAnchor,
        'Team page CSS anchor',
        $errors
    );

    $scriptAnchor = "<script>\n(function () {\n    'use strict';\n\n    var user = document.getElementById('mrl-rd-user');";
    $scriptReplacement = <<<'JSBLOCK'
<script>
(function () {
    'use strict';

    var printWrap = document.getElementById('mrl-current-segment-chart-print');
    var printButton = document.getElementById('mrlCurrentChartPrint');
    var spreadsheetButton = document.getElementById('mrlCurrentChartSpreadsheet');
    var excelForm = document.getElementById('mrlCurrentChartExcelForm');

    function mrlPad(value, width) {
        var s = String(value);
        while (s.length < width) s = '0' + s;
        return s;
    }

    function mrlGenerationStamp() {
        var now = new Date();
        var parts = new Intl.DateTimeFormat('en-US', {
            timeZone: 'America/New_York',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).formatToParts(now);

        var values = {};
        parts.forEach(function (part) {
            if (part.type !== 'literal') values[part.type] = part.value;
        });

        var hour = values.hour === '24' ? '00' : values.hour;

        return values.year + values.month + values.day + '_' +
            hour + values.minute + values.second +
            mrlPad(now.getMilliseconds(), 3);
    }

    if (spreadsheetButton && excelForm) {
        spreadsheetButton.addEventListener('click', function () {
            excelForm.submit();
        });
    }

    if (printButton && printWrap) {
        printButton.addEventListener('click', function () {
            var oldTitle = document.title;
            var year = printWrap.getAttribute('data-year') || '';
            var segment = printWrap.getAttribute('data-segment') || '';

            document.title = 'Team_Chart_' + year + '_' + segment + '_' + mrlGenerationStamp();
            document.body.classList.add('mrl-print-current-chart');

            var cleanup = function () {
                document.body.classList.remove('mrl-print-current-chart');
                document.title = oldTitle;
                window.removeEventListener('afterprint', cleanup);
            };

            window.addEventListener('afterprint', cleanup);
            window.print();

            window.setTimeout(function () {
                if (document.body.classList.contains('mrl-print-current-chart')) {
                    cleanup();
                }
            }, 1000);
        });
    }

    var user = document.getElementById('mrl-rd-user');
JSBLOCK;

    $out = replace_once(
        $out,
        $scriptAnchor,
        $scriptReplacement,
        'Main JavaScript anchor',
        $errors
    );

    return $out;
}

function candidate_lint(string $candidate, string $targetDir): array
{
    $tmp = @tempnam($targetDir, '.mrl_team_page_lint_');

    if ($tmp === false) {
        $tmp = @tempnam(sys_get_temp_dir(), 'mrl_team_page_lint_');
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
                        $message = 'PASS — team.php v054 installed and passed PHP lint.';
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
                $message = 'Rollback complete — team.php v053 restored and lint passed.';
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
<title>MRL Team Page Chart Actions</title>
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
    <h1>MRL Team Page Chart Actions</h1>
    <div class="muted">Installer v001 · generated 9/20/2026 2:46:05 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>What this installs</h2>
        <ul>
            <li>Adds <strong>Print</strong> and <strong>Spreadsheet</strong> directly above the current segment chart on <code>team.php</code>.</li>
            <li>Buttons are right-aligned to the chart and styled to match the current standalone Team Chart buttons.</li>
            <li>Print shows only the chart on a white background and preserves chart colors.</li>
            <li>Spreadsheet posts the current year/segment to <code>/team_chart.php</code>, reusing its tested pure-PHP XLSX export.</li>
            <li><code>current_segment_chart.php</code> is not modified.</li>
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
                <td>Detected <strong><?php echo h($version !== '' ? $version : '(unknown)'); ?></strong>; expected v053 before Apply, or v054 after Apply.</td>
            </tr>
            <tr>
                <td>Patch signatures</td>
                <td class="<?php echo ($alreadyInstalled || empty($errors)) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || empty($errors)) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h($alreadyInstalled ? 'v054 already installed.' : (empty($errors) ? 'All expected v053 anchors found exactly once.' : implode(' | ', $errors))); ?></td>
            </tr>
            <tr>
                <td>Candidate PHP lint</td>
                <td class="<?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h($alreadyInstalled ? 'Not needed — installed target is v054.' : (string)$candidateLint['output']); ?></td>
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
                <button class="apply" type="submit" <?php echo $canApply ? '' : 'disabled'; ?>>Apply v054</button>
            </form>

            <a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF'] ?? '')); ?>">Refresh / Preflight</a>
            <a class="btn neutral" href="/team.php" target="_blank" rel="noopener">Open Team Page</a>

            <form method="post" onsubmit="return confirm('Restore the backed-up v053 Team page?');">
                <input type="hidden" name="action" value="rollback">
                <button class="rollback" type="submit" <?php echo $backupExists ? '' : 'disabled'; ?>>Rollback Team Page</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
