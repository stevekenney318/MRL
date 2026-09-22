<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION = 'v057';
const TARGET_VERSION = 'v058';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/team.php';
$backupDir = $docRoot . '/_installer_backups/team_name_gate_refresh_20260922_051202pm';
$backupFile = $backupDir . '/team.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function lint_php_file(string $path): array {
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
        ' * VERSION: v057',
        ' * VERSION: v058',
        'Version header',
        $errors
    );

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace($m[0], ' * LAST MODIFIED: 9/22/2026 5:12:02 pm ET' . PHP_EOL, $out);
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $changeAnchor = " * CHANGELOG:\n *\n";
    $changeInsert = " * CHANGELOG:\n *\n"
        . " * v058 (9/22/2026 5:12:02 pm ET)\n"
        . " * - FIX: Re-resolves the authoritative current-year Team Name after current_user_team_chart.php returns and immediately before pick routing.\n"
        . " * - FIX: Prevents legacy include-scope variable pollution from bypassing the v057 Team Name prerequisite gate.\n"
        . " * - PRESERVE: v057 Team Name outer gate, LP/RD logic, pick-window rules, chart output, themes, print/export, and DB write behavior unchanged.\n"
        . " *\n";

    $out = replace_once(
        $out,
        $changeAnchor,
        $changeInsert,
        'Changelog anchor',
        $errors
    );

    $old = <<<'PHPBLOCK'
<section class="mrl-rd-chart-shell mrl-user-info-panel">
    <?php include 'current_user_team_chart.php'; ?>
</section>

<section class="mrl-rd-chart-shell mrl-rd-pick-section">
PHPBLOCK;

    $new = <<<'PHPBLOCK'
<section class="mrl-rd-chart-shell mrl-user-info-panel">
    <?php include 'current_user_team_chart.php'; ?>
</section>

<?php
/*
 * Re-resolve the authoritative Team Name after the legacy chart include.
 * Included PHP executes in this scope, so shared variables must not be trusted
 * after current_user_team_chart.php has reloaded configuration.
 */
if (isset($dbconnect) && $dbconnect instanceof mysqli) {
    $currentUserTeamName = teampage_get_current_user_team_name(
        $dbconnect,
        $uid,
        (string)$raceYear
    );
} else {
    $currentUserTeamName = '';
}
?>

<section class="mrl-rd-chart-shell mrl-rd-pick-section">
PHPBLOCK;

    $out = replace_once(
        $out,
        $old,
        $new,
        'Post-chart Team Name refresh anchor',
        $errors
    );

    return $out;
}

function candidate_lint(string $candidate, string $dir): array {
    $tmp = @tempnam($dir, '.mrl_team058_');
    if ($tmp === false) {
        $tmp = @tempnam(sys_get_temp_dir(), 'mrl_team058_');
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

$installedLint = $targetExists
    ? lint_php_file($target)
    : ['ok'=>false,'output'=>'Target missing.'];

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
                    $newLint = lint_php_file($target);

                    if (empty($newLint['ok'])) {
                        @copy($backupFile, $target);
                        @chmod($target, 0644);
                        $message = 'Installed file failed lint and backup was restored.';
                        $messageClass = 'bad';
                    } else {
                        $message = 'PASS — team.php v058 installed and passed PHP lint.';
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
    } elseif (!@copy($backupFile, $target)) {
        $message = 'Rollback failed: backup could not be restored.';
        $messageClass = 'bad';
    } else {
        @chmod($target, 0644);
        $restoredLint = lint_php_file($target);
        if (empty($restoredLint['ok'])) {
            $message = 'Rollback copied v057 back, but restored file failed lint.';
            $messageClass = 'bad';
        } else {
            $message = 'Rollback complete — team.php v057 restored and lint passed.';
            $messageClass = 'good';
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
<title>MRL Team Name Gate Refresh</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e;--muted:#aaa}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:#eee;font-family:Arial,sans-serif}
.wrap{max-width:1050px;margin:auto}
h1{color:var(--gold);margin:0 0 6px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.pass{color:var(--good);font-weight:800}.fail{color:var(--bad);font-weight:800}.muted{color:var(--muted)}
table{width:100%;border-collapse:collapse}
th,td{padding:9px;border-bottom:1px solid #333;text-align:left}
th{color:var(--gold)}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}
.apply{background:#248c4b}.neutral{background:#276fca}.rollback{background:#a83434}
button:disabled{opacity:.4}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL Team Name Gate Refresh</h1>
<div class="muted">Installer v001 · generated 9/22/2026 5:12:02 pm ET</div>

<?php if ($message !== ''): ?>
<div class="card"><?php echo h($message); ?></div>
<?php endif; ?>

<div class="card">
<h2>What this fixes</h2>
<p>Re-resolves the guest/current user's 2026 Team Name after <code>current_user_team_chart.php</code> returns, immediately before the v057 Team Name prerequisite gate runs.</p>
<p>No Team Name save logic, pick routing rules, LP/RD behavior, or database schema changes.</p>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<tr><td>Version</td><td class="<?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'pass':'fail'; ?>"><?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'PASS':'FAIL'; ?></td><td><?php echo h($version); ?> → v058</td></tr>
<tr><td>Patch signatures</td><td class="<?php echo ($alreadyInstalled||empty($errors))?'pass':'fail'; ?>"><?php echo ($alreadyInstalled||empty($errors))?'PASS':'FAIL'; ?></td><td><?php echo h($alreadyInstalled?'Already installed.':(empty($errors)?'Expected v057 chart/routing anchor found exactly once.':implode(' | ',$errors))); ?></td></tr>
<tr><td>Candidate lint</td><td class="<?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'pass':'fail'; ?>"><?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'PASS':'FAIL'; ?></td><td><?php echo h($alreadyInstalled?'Not needed.':$candidateLint['output']); ?></td></tr>
<tr><td>Installed lint</td><td class="<?php echo !empty($installedLint['ok'])?'pass':'fail'; ?>"><?php echo !empty($installedLint['ok'])?'PASS':'FAIL'; ?></td><td><?php echo h($installedLint['output']); ?></td></tr>
</table>

<p>
<form method="post" style="display:inline"><input type="hidden" name="action" value="apply"><button class="apply" <?php echo $canApply?'':'disabled'; ?>>Apply v058</button></form>
<a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF'] ?? '')); ?>">Refresh / Preflight</a>
<form method="post" style="display:inline"><input type="hidden" name="action" value="rollback"><button class="rollback" <?php echo $backupExists?'':'disabled'; ?>>Rollback</button></form>
</p>
</div>
</div>
</body>
</html>
