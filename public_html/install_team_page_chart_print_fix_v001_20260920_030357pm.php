<?php
declare(strict_types=1);

/**
 * install_team_page_chart_print_fix_v001_20260920_030357pm.php
 *
 * VERSION: v001
 * GENERATED: 9/20/2026 3:03:57 pm ET
 *
 * PURPOSE:
 * - Fixes Team-page current Team Chart printing after v054.
 * - Replaces visibility-based printing with a temporary print-only clone.
 * - Prevents hidden Team-page content from reserving print pages.
 * - Forces landscape print layout and keeps the chart together when possible.
 *
 * SAFETY:
 * - Modifies only /public_html/team.php.
 * - Expected baseline: v054.
 * - Does not change spreadsheet behavior, chart data, picks, scoring, DB, scheduler, or current_segment_chart.php.
 * - Candidate + installed file PHP lint.
 * - Backup + rollback.
 */

date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION = 'v054';
const TARGET_VERSION = 'v055';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/team.php';
$backupDir = $docRoot . '/_installer_backups/team_page_chart_print_fix_20260920_030357pm';
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
        ' * VERSION: v054',
        ' * VERSION: v055',
        'Version header',
        $errors
    );

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace(
            $m[0],
            ' * LAST MODIFIED: 9/20/2026 3:03:57 pm ET' . PHP_EOL,
            $out
        );
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelogInsert = " * CHANGELOG:\n *\n"
        . " * v055 (9/20/2026 3:03:57 pm ET)\n"
        . " * - FIX: Team-page Team Chart print now uses a temporary print-only clone instead of visibility:hidden on the full Team page.\n"
        . " * - FIX: Hidden Team-page content no longer reserves blank print pages or causes header-only output.\n"
        . " * - PRINT: Forces landscape layout, white background, preserved chart colors, and keeps the chart together when practical.\n"
        . " * - PRESERVE: v054 Print/Spreadsheet buttons, spreadsheet export, current_segment_chart.php, themes, picks, LP/RD, scoring, scheduler, and DB behavior unchanged.\n"
        . " *\n";

    $out = replace_once(
        $out,
        $changelogAnchor,
        $changelogInsert,
        'Changelog anchor',
        $errors
    );

    $oldCss = <<<'CSS'
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
CSS;

    $newCss = <<<'CSS'
        @media print{
            @page{
                size:landscape;
                margin:0.35in;
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
            html.mrl-theme-light body{
                background:#fff!important;
                background-image:none!important;
            }

            body.mrl-print-current-chart > *:not(.mrl-current-chart-print-clone){
                display:none!important;
            }

            body.mrl-print-current-chart .mrl-current-chart-print-clone{
                display:block!important;
                position:static!important;
                width:100%!important;
                max-width:none!important;
                margin:0!important;
                padding:0!important;
                background:#fff!important;
            }

            body.mrl-print-current-chart .mrl-current-chart-print-clone .mrl-current-chart-no-print{
                display:none!important;
            }

            body.mrl-print-current-chart .mrl-current-chart-print-clone table{
                width:100%!important;
                page-break-inside:avoid!important;
                break-inside:avoid-page!important;
                -webkit-print-color-adjust:exact!important;
                print-color-adjust:exact!important;
            }

            body.mrl-print-current-chart .mrl-current-chart-print-clone tr,
            body.mrl-print-current-chart .mrl-current-chart-print-clone td,
            body.mrl-print-current-chart .mrl-current-chart-print-clone th{
                -webkit-print-color-adjust:exact!important;
                print-color-adjust:exact!important;
            }
        }
CSS;

    $out = replace_once(
        $out,
        $oldCss,
        $newCss,
        'Print CSS block',
        $errors
    );

    $oldJs = <<<'JS'
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
JS;

    $newJs = <<<'JS'
    if (printButton && printWrap) {
        printButton.addEventListener('click', function () {
            var oldTitle = document.title;
            var year = printWrap.getAttribute('data-year') || '';
            var segment = printWrap.getAttribute('data-segment') || '';

            var printClone = printWrap.cloneNode(true);
            printClone.removeAttribute('id');
            printClone.classList.add('mrl-current-chart-print-clone');

            var cloneControls = printClone.querySelectorAll('.mrl-current-chart-no-print, form');
            Array.prototype.forEach.call(cloneControls, function (node) {
                if (node && node.parentNode) {
                    node.parentNode.removeChild(node);
                }
            });

            document.body.appendChild(printClone);
            document.title = 'Team_Chart_' + year + '_' + segment + '_' + mrlGenerationStamp();
            document.body.classList.add('mrl-print-current-chart');

            var cleaned = false;

            var cleanup = function () {
                if (cleaned) return;
                cleaned = true;

                document.body.classList.remove('mrl-print-current-chart');
                document.title = oldTitle;

                if (printClone && printClone.parentNode) {
                    printClone.parentNode.removeChild(printClone);
                }

                window.removeEventListener('afterprint', cleanup);
            };

            window.addEventListener('afterprint', cleanup);
            window.print();

            window.setTimeout(cleanup, 1500);
        });
    }
JS;

    $out = replace_once(
        $out,
        $oldJs,
        $newJs,
        'Print JavaScript block',
        $errors
    );

    return $out;
}

function candidate_lint(string $candidate, string $targetDir): array
{
    $tmp = @tempnam($targetDir, '.mrl_team_page_print_lint_');

    if ($tmp === false) {
        $tmp = @tempnam(sys_get_temp_dir(), 'mrl_team_page_print_lint_');
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
                        $message = 'PASS — team.php v055 installed and passed PHP lint.';
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
                $message = 'Rollback complete — team.php v054 restored and lint passed.';
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
<title>MRL Team Page Chart Print Fix</title>
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
    <h1>MRL Team Page Chart Print Fix</h1>
    <div class="muted">Installer v001 · generated 9/20/2026 3:03:57 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>What this fixes</h2>
        <ul>
            <li>Removes the blank-page / header-only PDF behavior from Team-page chart printing.</li>
            <li>Uses a temporary print-only clone so the rest of the Team page is truly absent from print layout.</li>
            <li>Forces landscape page orientation and a clean white background.</li>
            <li>Preserves chart cell colors and the timestamped Team Chart print filename.</li>
            <li>Spreadsheet behavior is unchanged in this fix.</li>
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
                <td>Detected <strong><?php echo h($version !== '' ? $version : '(unknown)'); ?></strong>; expected v054 before Apply, or v055 after Apply.</td>
            </tr>
            <tr>
                <td>Patch signatures</td>
                <td class="<?php echo ($alreadyInstalled || empty($errors)) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || empty($errors)) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h($alreadyInstalled ? 'v055 already installed.' : (empty($errors) ? 'Expected v054 print blocks found exactly once.' : implode(' | ', $errors))); ?></td>
            </tr>
            <tr>
                <td>Candidate PHP lint</td>
                <td class="<?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'pass' : 'fail'; ?>">
                    <?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo h($alreadyInstalled ? 'Not needed — v055 installed.' : (string)$candidateLint['output']); ?></td>
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
                <button class="apply" type="submit" <?php echo $canApply ? '' : 'disabled'; ?>>Apply v055</button>
            </form>

            <a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF'] ?? '')); ?>">Refresh / Preflight</a>
            <a class="btn neutral" href="/team.php" target="_blank" rel="noopener">Open Team Page</a>

            <form method="post" onsubmit="return confirm('Restore the backed-up v054 Team page?');">
                <input type="hidden" name="action" value="rollback">
                <button class="rollback" type="submit" <?php echo $backupExists ? '' : 'disabled'; ?>>Rollback Team Page</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
