<?php
declare(strict_types=1);

/**
 * install_weekly_standings_theme_polish_v001_20260920_054210pm.php
 *
 * VERSION: v001
 * GENERATED: 9/20/2026 5:42:10 pm ET
 *
 * PURPOSE:
 * - Polishes Weekly Standings Team-theme readability after v073.
 * - Themes report footnotes, snapshot timestamp, and footer text.
 * - Preserves light report tables and expandable panel styling.
 */

date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION = 'v073';
const TARGET_VERSION = 'v074';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/race_results/weekly_standings.php';
$backupDir = $docRoot . '/_installer_backups/weekly_standings_theme_polish_20260920_054210pm';
$backupFile = $backupDir . '/weekly_standings.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function lint_php_file(string $path): array {
    if (!function_exists('shell_exec')) {
        return ['ok'=>false,'output'=>'shell_exec() is not available.'];
    }
    $out = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');
    if ($out === null) {
        return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    }
    $out = trim((string)$out);
    return ['ok'=>stripos($out, 'No syntax errors detected') !== false,'output'=>$out];
}

function current_version(string $content): string {
    if (preg_match('/\*\s*VERSION:\s*(v\d+)/i', $content, $m)) {
        return (string)$m[1];
    }
    return '';
}

function replace_once(string $subject, string $search, string $replace, string $label, array &$errors): string {
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
    return substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($search));
}

function build_candidate(string $source, array &$errors): string {
    $out = $source;

    $out = replace_once($out, ' * VERSION: v073', ' * VERSION: v074', 'Version header', $errors);

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace($m[0], ' * LAST MODIFIED: 9/20/2026 5:42:10 pm ET' . PHP_EOL, $out);
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelogInsert = " * CHANGELOG:\n *\n"
        . " * v074 (9/20/2026 5:42:10 pm ET)\n"
        . " *   - THEME POLISH: LP/RD/tie footnotes, snapshot timestamp, and footer text now use the shared theme muted-text color.\n"
        . " *   - READABILITY: Dark, Cars, and Starry Night no longer leave auxiliary report text too dark against the page background.\n"
        . " *   - PRINT: Clean/full print restores dark auxiliary text on the white print page.\n"
        . " *   - PRESERVE: Validation, Audit, Pending Review, expanded driver details, tables, scoring colors, spreadsheet export, navigation, release history, and scoring logic unchanged.\n"
        . " *\n";
    $out = replace_once($out, $changelogAnchor, $changelogInsert, 'Changelog anchor', $errors);

    $pairs = [
        [
            "        .table-footnote {\n            margin-top: 4px;\n            margin-left: 10px;\n            font-size: 16px;\n            color: #444;\n            font-style: italic;\n        }",
            "        .table-footnote {\n            margin-top: 4px;\n            margin-left: 10px;\n            font-size: 16px;\n            color: var(--mrl-rd-muted);\n            font-style: italic;\n        }",
            'Table footnote'
        ],
        [
            "        .footnote-block {\n            margin-top: 4px;\n            margin-left: 10px;\n            font-size: 13px;\n            line-height: 1.35;\n            color: #444;\n            font-style: italic;\n            font-weight: 500;\n            text-align: left;\n        }",
            "        .footnote-block {\n            margin-top: 4px;\n            margin-left: 10px;\n            font-size: 13px;\n            line-height: 1.35;\n            color: var(--mrl-rd-muted);\n            font-style: italic;\n            font-weight: 500;\n            text-align: left;\n        }",
            'Footnote block'
        ],
        [
            "        .winner-footnote {\n            margin-top: 4px;\n            margin-left: 10px;\n            font-size: 16px;\n            color: #444;\n            font-style: italic;\n        }",
            "        .winner-footnote {\n            margin-top: 4px;\n            margin-left: 10px;\n            font-size: 16px;\n            color: var(--mrl-rd-muted);\n            font-style: italic;\n        }",
            'Winner footnote'
        ],
        [
            "        .snapshot-footnote {\n            margin-left: 6px;\n            font-size: 0.82em;\n            color: #777;\n            font-style: normal;\n            white-space: nowrap;\n        }",
            "        .snapshot-footnote {\n            margin-left: 6px;\n            font-size: 0.82em;\n            color: var(--mrl-rd-muted);\n            font-style: normal;\n            white-space: nowrap;\n        }",
            'Snapshot footnote'
        ],
    ];

    foreach ($pairs as $p) {
        $out = replace_once($out, $p[0], $p[1], $p[2], $errors);
    }

    $footerAnchor = "        .snapshot-footnote {\n            margin-left: 6px;\n            font-size: 0.82em;\n            color: var(--mrl-rd-muted);\n            font-style: normal;\n            white-space: nowrap;\n        }\n";
    $footerInsert = $footerAnchor
        . "\n        footer > div {\n            color: var(--mrl-rd-muted) !important;\n        }\n";
    $out = replace_once($out, $footerAnchor, $footerInsert, 'Footer override', $errors);

    $printAnchor = "            body.release-superseded-view {\n                background: #ffffff !important;\n                background-image: none !important;\n                color: #111111 !important;\n            }\n";
    $printInsert = $printAnchor
        . "\n            .table-footnote,\n"
        . "            .footnote-block,\n"
        . "            .winner-footnote,\n"
        . "            .snapshot-footnote,\n"
        . "            .historical-note-row,\n"
        . "            footer > div {\n"
        . "                color: #444444 !important;\n"
        . "            }\n";
    $out = replace_once($out, $printAnchor, $printInsert, 'Print auxiliary text reset', $errors);

    return $out;
}

function candidate_lint(string $candidate, string $targetDir): array {
    $tmp = @tempnam($targetDir, '.mrl_weekly_theme_polish_');
    if ($tmp === false) {
        $tmp = @tempnam(sys_get_temp_dir(), 'mrl_weekly_theme_polish_');
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

$installedLint = $targetExists ? lint_php_file($target) : ['ok'=>false,'output'=>'Target missing.'];
$backupExists = is_file($backupFile);

$canApply = $targetExists && $targetWritable && $version === EXPECTED_SOURCE_VERSION && empty($errors) && !empty($candidateLint['ok']);

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
                        $message = 'PASS — weekly_standings.php v074 installed and passed PHP lint.';
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
                $message = 'Rollback complete — weekly_standings.php v073 restored and lint passed.';
                $messageClass = 'good';
            }
        }
    }
}

$source = is_file($target) ? (string)@file_get_contents($target) : '';
$version = $source !== '' ? current_version($source) : '';
$alreadyInstalled = ($version === TARGET_VERSION);
$backupExists = is_file($backupFile);
$installedLint = is_file($target) ? lint_php_file($target) : ['ok'=>false,'output'=>'Target missing.'];

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Weekly Standings Theme Polish</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1100px;margin:0 auto}
h1{margin:0 0 6px;color:var(--gold)} h2{margin:0 0 10px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.notice{margin:14px 0;padding:12px 14px;border-radius:10px;border:1px solid #36506f;background:#142033}
.notice.good{border-color:#327a4b;background:#13271a;color:#a8efbf}
.notice.bad{border-color:#983f3f;background:#2a1515;color:#ffb0b0}
.pass{color:var(--good);font-weight:800} .fail{color:var(--bad);font-weight:800} .muted{color:var(--muted)}
code{background:#282828;padding:2px 5px;border-radius:5px}
table{width:100%;border-collapse:collapse} th,td{padding:9px 10px;border-bottom:1px solid #333;text-align:left;vertical-align:top} th{color:var(--gold)}
.buttons{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;font-size:14px}
.apply{background:#248c4b} .neutral{background:#276fca} .rollback{background:#a83434} button:disabled{opacity:.38;cursor:not-allowed}
ul{line-height:1.5}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Weekly Standings Theme Polish</h1>
    <div class="muted">Installer v001 · generated 9/20/2026 5:42:10 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>What this changes</h2>
        <ul>
            <li>LP/RD/tie footnotes and the snapshot timestamp use the shared theme-muted color.</li>
            <li>The footer's inline gray is overridden by the same theme-muted color.</li>
            <li>Print/PDF restores those auxiliary texts to dark gray on white.</li>
            <li>Validation, Audit, Pending Review, expanded driver details, tables, scoring colors, and spreadsheet behavior are untouched.</li>
        </ul>
    </div>

    <div class="card">
        <h2>Preflight</h2>
        <table>
            <tr><th>Check</th><th>Status</th><th>Detail</th></tr>
            <tr><td>Target exists</td><td class="<?php echo $targetExists ? 'pass' : 'fail'; ?>"><?php echo $targetExists ? 'PASS' : 'FAIL'; ?></td><td><code><?php echo h($target); ?></code></td></tr>
            <tr><td>Target writable</td><td class="<?php echo $targetWritable ? 'pass' : 'fail'; ?>"><?php echo $targetWritable ? 'PASS' : 'FAIL'; ?></td><td><?php echo $targetWritable ? 'Writable' : 'Not writable'; ?></td></tr>
            <tr><td>Current version</td><td class="<?php echo ($version === EXPECTED_SOURCE_VERSION || $alreadyInstalled) ? 'pass' : 'fail'; ?>"><?php echo ($version === EXPECTED_SOURCE_VERSION || $alreadyInstalled) ? 'PASS' : 'FAIL'; ?></td><td>Detected <strong><?php echo h($version !== '' ? $version : '(unknown)'); ?></strong>; expected v073 before Apply, or v074 after Apply.</td></tr>
            <tr><td>Patch signatures</td><td class="<?php echo ($alreadyInstalled || empty($errors)) ? 'pass' : 'fail'; ?>"><?php echo ($alreadyInstalled || empty($errors)) ? 'PASS' : 'FAIL'; ?></td><td><?php echo h($alreadyInstalled ? 'v074 already installed.' : (empty($errors) ? 'All expected v073 theme-polish anchors found exactly once.' : implode(' | ', $errors))); ?></td></tr>
            <tr><td>Candidate PHP lint</td><td class="<?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'pass' : 'fail'; ?>"><?php echo ($alreadyInstalled || !empty($candidateLint['ok'])) ? 'PASS' : 'FAIL'; ?></td><td><?php echo h($alreadyInstalled ? 'Not needed — v074 installed.' : (string)$candidateLint['output']); ?></td></tr>
            <tr><td>Installed PHP lint</td><td class="<?php echo !empty($installedLint['ok']) ? 'pass' : 'fail'; ?>"><?php echo !empty($installedLint['ok']) ? 'PASS' : 'FAIL'; ?></td><td><?php echo h((string)$installedLint['output']); ?></td></tr>
            <tr><td>Rollback backup</td><td><?php echo $backupExists ? '<span class="pass">READY</span>' : '<span class="muted">Not created yet</span>'; ?></td><td><code><?php echo h($backupFile); ?></code></td></tr>
        </table>

        <div class="buttons">
            <form method="post"><input type="hidden" name="action" value="apply"><button class="apply" type="submit" <?php echo $canApply ? '' : 'disabled'; ?>>Apply v074</button></form>
            <a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF'] ?? '')); ?>">Refresh / Preflight</a>
            <a class="btn neutral" href="/race_results/weekly_standings.php" target="_blank" rel="noopener">Open Weekly Standings</a>
            <form method="post" onsubmit="return confirm('Restore the backed-up v073 Weekly Standings?');"><input type="hidden" name="action" value="rollback"><button class="rollback" type="submit" <?php echo $backupExists ? '' : 'disabled'; ?>>Rollback Weekly Standings</button></form>
        </div>
    </div>
</div>
</body>
</html>
