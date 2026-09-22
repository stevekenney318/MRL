<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION = 'v078';
const TARGET_VERSION = 'v079';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/race_results/weekly_standings.php';
$backupDir = $docRoot . '/_installer_backups/weekly_standings_scrollbar_reserve_20260922_122158am';
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
    return ['ok'=>stripos($out,'No syntax errors detected') !== false,'output'=>$out];
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
    return substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($search));
}

function build_candidate(string $source, array &$errors): string {
    $out = $source;

    $out = replace_once(
        $out,
        ' * VERSION: v078',
        ' * VERSION: v079',
        'Version header',
        $errors
    );

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace($m[0], ' * LAST MODIFIED: 9/22/2026 12:21:58 am ET' . PHP_EOL, $out);
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $changeAnchor = " * CHANGELOG:\n *\n";
    $changeInsert = " * CHANGELOG:\n *\n"
        . " * v079 (9/22/2026 12:21:58 am ET)\n"
        . " *   - UI: Replaces root scrollbar-gutter reservation with measured body-side reserve space only when the page does not need a vertical scrollbar.\n"
        . " *   - UI: Picture themes now keep their background visible to the right edge while preserving a constant report width between short and tall race pages.\n"
        . " *   - UI: Removes the ineffective v078 WebKit transparent-track workaround.\n"
        . " *   - PRESERVE: v077 cross-document view transition, mobile layout, nav behavior, report buttons, scoring, themes, print/PDF, spreadsheet export, validation, audit, and release history unchanged.\n"
        . " *\n";

    $out = replace_once(
        $out,
        $changeAnchor,
        $changeInsert,
        'Changelog anchor',
        $errors
    );

    $oldCss = <<<'CSS'
        html {
            min-height: 100%;
            scrollbar-gutter: stable;
        }

        /* v078: retain the stable scrollbar gutter without a visible empty track */
        html::-webkit-scrollbar {
            background: transparent;
        }

        html::-webkit-scrollbar-track {
            background: transparent;
        }
CSS;

    $newCss = <<<'CSS'
        html {
            min-height: 100%;
        }

        /* v079: keep layout width stable without exposing a root gutter on picture themes. */
        @media screen {
            body.mrl-reserve-vscroll-space {
                padding-right: var(--mrl-vscroll-width, 0px);
            }
        }
CSS;

    $out = replace_once(
        $out,
        $oldCss,
        $newCss,
        'Scrollbar CSS replacement',
        $errors
    );

    $scriptAnchor = "</script>\n\n</body>";

    $scriptInsert = <<<'HTML'
</script>

<script>
(function () {
    'use strict';

    const root = document.documentElement;
    const body = document.body;

    if (!root || !body) return;

    function measureClassicScrollbarWidth() {
        const probe = document.createElement('div');
        probe.style.position = 'absolute';
        probe.style.top = '-9999px';
        probe.style.left = '-9999px';
        probe.style.width = '100px';
        probe.style.height = '100px';
        probe.style.overflow = 'scroll';
        probe.style.visibility = 'hidden';

        body.appendChild(probe);
        const width = probe.offsetWidth - probe.clientWidth;
        probe.remove();

        return Math.max(0, width);
    }

    const scrollbarWidth = measureClassicScrollbarWidth();
    root.style.setProperty('--mrl-vscroll-width', scrollbarWidth + 'px');

    function syncScrollbarReserve() {
        const hasVerticalScroll = root.scrollHeight > root.clientHeight + 1;

        body.classList.toggle(
            'mrl-reserve-vscroll-space',
            scrollbarWidth > 0 && !hasVerticalScroll
        );
    }

    syncScrollbarReserve();
    window.addEventListener('load', syncScrollbarReserve);
    window.addEventListener('resize', syncScrollbarReserve);

    if (typeof ResizeObserver === 'function') {
        const observer = new ResizeObserver(syncScrollbarReserve);
        observer.observe(body);
    }
})();
</script>

</body>
HTML;

    $out = replace_once(
        $out,
        $scriptAnchor,
        $scriptInsert,
        'Scrollbar reserve script anchor',
        $errors
    );

    return $out;
}

function candidate_lint(string $candidate, string $dir): array {
    $tmp = @tempnam($dir, '.mrl_ws079_');
    if ($tmp === false) {
        $tmp = @tempnam(sys_get_temp_dir(), 'mrl_ws079_');
    }
    if ($tmp === false) {
        return ['ok'=>false,'output'=>'Could not create temporary lint file.'];
    }
    if (@file_put_contents($tmp, $candidate, LOCK_EX) === false) {
        @unlink($tmp);
        return ['ok'=>false,'output'=>'Could not write temporary lint file.'];
    }
    $r = lint_php_file($tmp);
    @unlink($tmp);
    return $r;
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

$backupExists = is_file($backupFile);
$canApply = $targetExists
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
            $message = 'Apply blocked: backup already exists.';
            $messageClass = 'bad';
        } elseif (!@copy($target, $backupFile)) {
            $message = 'Apply blocked: backup could not be created.';
            $messageClass = 'bad';
        } else {
            @chmod($backupFile, 0644);
            $tmpTarget = $target . '.mrl_tmp_' . uniqid('', true);

            if (@file_put_contents($tmpTarget, $candidate, LOCK_EX) === false) {
                @unlink($tmpTarget);
                $message = 'Apply failed: temporary replacement could not be written.';
                $messageClass = 'bad';
            } else {
                @chmod($tmpTarget, 0644);
                $tmpLint = lint_php_file($tmpTarget);

                if (empty($tmpLint['ok'])) {
                    @unlink($tmpTarget);
                    $message = 'Apply blocked: temporary replacement failed PHP lint.';
                    $messageClass = 'bad';
                } elseif (!@rename($tmpTarget, $target)) {
                    @unlink($tmpTarget);
                    $message = 'Apply failed: replacement could not be completed.';
                    $messageClass = 'bad';
                } else {
                    @chmod($target, 0644);
                    $installedLint = lint_php_file($target);

                    if (empty($installedLint['ok'])) {
                        @copy($backupFile, $target);
                        @chmod($target, 0644);
                        $message = 'Installed file failed lint and backup was restored.';
                        $messageClass = 'bad';
                    } else {
                        $message = 'PASS — weekly_standings.php v079 installed and passed PHP lint.';
                        $messageClass = 'good';
                    }
                }
            }
        }
    }
}

if ($action === 'rollback' && is_file($backupFile)) {
    @copy($backupFile, $target);
    @chmod($target, 0644);
    $message = 'Rollback complete — weekly_standings.php v078 restored.';
    $messageClass = 'good';
}

$source = is_file($target) ? (string)@file_get_contents($target) : '';
$version = $source !== '' ? current_version($source) : '';
$alreadyInstalled = ($version === TARGET_VERSION);
$backupExists = is_file($backupFile);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Weekly Standings Scrollbar Reserve</title>
<style>
:root{color-scheme:dark}
body{margin:0;padding:22px;background:#101010;color:#eee;font-family:Arial,sans-serif}
.wrap{max-width:1050px;margin:auto}
h1{color:#f2c98e}
.card{margin:14px 0;padding:16px;background:#1b1b1b;border:1px solid #3d3d3d;border-radius:14px}
.pass{color:#66df8d;font-weight:800}
.fail{color:#ff7474;font-weight:800}
table{width:100%;border-collapse:collapse}
th,td{padding:9px;border-bottom:1px solid #333;text-align:left}
th{color:#f2c98e}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}
.apply{background:#248c4b}
.neutral{background:#276fca}
.rollback{background:#a83434}
button:disabled{opacity:.4}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL Weekly Standings Scrollbar Reserve</h1>
<div>Installer v001 · generated 9/22/2026 12:21:58 am ET</div>

<?php if ($message !== ''): ?>
<div class="card"><?php echo h($message); ?></div>
<?php endif; ?>

<div class="card">
<h2>What changes</h2>
<p>Removes the root <code>scrollbar-gutter: stable</code> reservation that exposes a full-height strip over Cars/Starry Night.</p>
<p>On short pages, JavaScript measures the native scrollbar width and reserves the same width inside the page. On tall pages, the real scrollbar supplies that width instead.</p>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<tr><td>Version</td><td class="<?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'pass':'fail'; ?>"><?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'PASS':'FAIL'; ?></td><td><?php echo h($version); ?> → v079</td></tr>
<tr><td>Patch signatures</td><td class="<?php echo ($alreadyInstalled||empty($errors))?'pass':'fail'; ?>"><?php echo ($alreadyInstalled||empty($errors))?'PASS':'FAIL'; ?></td><td><?php echo h($alreadyInstalled?'Already installed.':(empty($errors)?'Expected v078 anchors found exactly once.':implode(' | ',$errors))); ?></td></tr>
<tr><td>Candidate lint</td><td class="<?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'pass':'fail'; ?>"><?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'PASS':'FAIL'; ?></td><td><?php echo h($alreadyInstalled?'Not needed.':$candidateLint['output']); ?></td></tr>
</table>

<p>
<form method="post" style="display:inline"><input type="hidden" name="action" value="apply"><button class="apply" <?php echo $canApply?'':'disabled'; ?>>Apply v079</button></form>
<a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF'] ?? '')); ?>">Refresh / Preflight</a>
<form method="post" style="display:inline"><input type="hidden" name="action" value="rollback"><button class="rollback" <?php echo $backupExists?'':'disabled'; ?>>Rollback</button></form>
</p>
</div>
</div>
</body>
</html>
