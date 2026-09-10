<?php
declare(strict_types=1);

/**
 * MRL Credential Exposure Audit
 *
 * VERSION: v001
 * LAST MODIFIED: 9/10/2026 12:40:02 am
 *
 * PURPOSE:
 * - Read-only scan for hard-coded credentials/secrets in the MRL account area.
 * - Focuses on public_html, _mrl_backups, account-root config-like files, and other likely text files.
 * - NEVER displays detected secret values.
 *
 * SAFETY:
 * - Requires an authenticated MRL administrator session.
 * - Makes no file changes.
 * - Sends no email.
 * - Does not execute scheduled jobs.
 * - Does not write scan results to disk.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/New_York');

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), "/\\");
$accountRoot = $docRoot !== '' ? dirname($docRoot) : '';
$authorized = false;
$error = '';
$results = array();
$summary = array(
    'files_scanned' => 0,
    'files_skipped' => 0,
    'high' => 0,
    'medium' => 0,
    'info' => 0,
);

function mrl_audit_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mrl_audit_is_within(string $path, string $parent): bool
{
    $path = str_replace('\\', '/', $path);
    $parent = rtrim(str_replace('\\', '/', $parent), '/');
    return $path === $parent || str_starts_with($path, $parent . '/');
}

function mrl_audit_relative(string $path, string $root): string
{
    $path = str_replace('\\', '/', $path);
    $root = rtrim(str_replace('\\', '/', $root), '/');
    if (str_starts_with($path, $root . '/')) {
        return substr($path, strlen($root) + 1);
    }
    return $path;
}

function mrl_audit_should_skip_dir(string $name, string $fullPath): bool
{
    $lower = strtolower($name);

    // Large/generated directories unlikely to contain MRL-authored secrets.
    $skipNames = array(
        '.git', 'node_modules', 'vendor', 'cache', 'tmp', 'temp',
        'uploads', 'upgrade', 'languages', 'plugins', 'themes'
    );

    if (in_array($lower, $skipNames, true)) {
        // Do not skip MRL's own folders merely because their names overlap.
        if (stripos($fullPath, '/public_html/race_results') !== false) {
            return false;
        }
        return true;
    }

    return false;
}

function mrl_audit_is_candidate_file(string $path): bool
{
    $base = strtolower(basename($path));

    // Explicit config-like filenames, including extensionless/hidden ones.
    if (preg_match('/(^|[._-])(config|conf|credential|credentials|secret|secrets|password|passwd|env|settings)([._-]|$)/i', $base)) {
        return true;
    }

    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    $allowed = array(
        'php', 'inc', 'phtml', 'env', 'ini', 'conf', 'cfg',
        'json', 'yml', 'yaml', 'xml', 'txt', 'md', 'log', 'sql'
    );

    return in_array($ext, $allowed, true);
}

function mrl_audit_severity(string $file, string $docRoot, string $accountRoot): string
{
    $norm = str_replace('\\', '/', $file);

    if (mrl_audit_is_within($norm, $docRoot)) {
        return 'HIGH';
    }

    if (str_starts_with($norm, rtrim(str_replace('\\', '/', $accountRoot), '/') . '/_mrl_backups/')) {
        return 'MEDIUM';
    }

    if (str_starts_with($norm, rtrim(str_replace('\\', '/', $accountRoot), '/') . '/_mrl_private/')) {
        return 'INFO';
    }

    return 'MEDIUM';
}

function mrl_audit_add_result(
    array &$results,
    array &$summary,
    string $file,
    int $line,
    string $type,
    string $severity,
    string $note,
    string $accountRoot
): void {
    $results[] = array(
        'file' => mrl_audit_relative($file, $accountRoot),
        'line' => $line,
        'type' => $type,
        'severity' => $severity,
        'note' => $note,
    );

    $key = strtolower($severity);
    if (isset($summary[$key])) {
        $summary[$key]++;
    }
}

function mrl_audit_scan_file(
    string $file,
    string $docRoot,
    string $accountRoot,
    array &$results,
    array &$summary
): void {
    $size = @filesize($file);
    if ($size === false || $size > 2 * 1024 * 1024) {
        $summary['files_skipped']++;
        return;
    }

    $content = @file_get_contents($file);
    if ($content === false) {
        $summary['files_skipped']++;
        return;
    }

    // Avoid trying to parse obvious binary content.
    if (strpos(substr($content, 0, 4096), "\0") !== false) {
        $summary['files_skipped']++;
        return;
    }

    $summary['files_scanned']++;
    $severity = mrl_audit_severity($file, $docRoot, $accountRoot);
    $lines = preg_split('/\R/', $content);

    // Known private secret file is expected and intentionally not treated as exposure.
    $privateExpected = str_replace('\\', '/', $accountRoot . '/_mrl_private/mrl_mail_secrets.php');
    $isExpectedPrivate = str_replace('\\', '/', $file) === $privateExpected;

    foreach ($lines as $index => $lineText) {
        $lineNo = $index + 1;
        $line = (string)$lineText;

        if ($isExpectedPrivate) {
            if (stripos($line, 'gmail_app_password') !== false) {
                mrl_audit_add_result(
                    $results, $summary, $file, $lineNo,
                    'Expected private mail credential',
                    'INFO',
                    'Credential exists in _mrl_private outside public_html. Value not displayed.',
                    $accountRoot
                );
            }
            continue;
        }

        // 1) PHP-style variable/property assignment to a quoted literal.
        if (preg_match('/(?:\$[A-Za-z_][A-Za-z0-9_]*(?:->)?|[A-Za-z_][A-Za-z0-9_]*)'
            . '(?:password|passwd|pwd|secret|token|api[_-]?key|access[_-]?key|private[_-]?key)'
            . '[A-Za-z0-9_]*\s*=\s*[\'"][^\'"]{4,}[\'"]/i', $line)) {
            mrl_audit_add_result(
                $results, $summary, $file, $lineNo,
                'Hard-coded secret assignment',
                $severity,
                'Secret-like variable/property assigned a literal value. Value withheld.',
                $accountRoot
            );
            continue;
        }

        // 2) PHPMailer password specifically.
        if (preg_match('/\$mail->Password\s*=\s*[\'"][^\'"]+[\'"]/i', $line)) {
            mrl_audit_add_result(
                $results, $summary, $file, $lineNo,
                'Hard-coded SMTP password',
                $severity,
                'PHPMailer password assigned a literal value. Value withheld.',
                $accountRoot
            );
            continue;
        }

        // 3) WordPress/database constants.
        if (preg_match('/define\s*\(\s*[\'"](?:DB_PASSWORD|DB_USER|DB_NAME|AUTH_KEY|SECURE_AUTH_KEY|LOGGED_IN_KEY|NONCE_KEY|AUTH_SALT|SECURE_AUTH_SALT|LOGGED_IN_SALT|NONCE_SALT)[\'"]\s*,\s*[\'"][^\'"]+[\'"]\s*\)/i', $line)) {
            mrl_audit_add_result(
                $results, $summary, $file, $lineNo,
                'Hard-coded configuration credential/key',
                $severity,
                'Configuration constant contains a literal credential/key. Value withheld.',
                $accountRoot
            );
            continue;
        }

        // 4) Generic key/value text formats.
        if (preg_match('/^\s*(?:password|passwd|pwd|secret|token|api[_-]?key|access[_-]?key)\s*[:=]\s*[^\s#;]{4,}/i', $line)) {
            mrl_audit_add_result(
                $results, $summary, $file, $lineNo,
                'Plain-text credential field',
                $severity,
                'Credential-like key/value pair found. Value withheld.',
                $accountRoot
            );
            continue;
        }

        // 5) Authorization bearer literals.
        if (preg_match('/Authorization\s*:\s*Bearer\s+[A-Za-z0-9._~+\/-]{8,}/i', $line)) {
            mrl_audit_add_result(
                $results, $summary, $file, $lineNo,
                'Bearer token',
                $severity,
                'Bearer token literal found. Value withheld.',
                $accountRoot
            );
            continue;
        }

        // 6) Private key material.
        if (strpos($line, '-----BEGIN PRIVATE KEY-----') !== false ||
            strpos($line, '-----BEGIN RSA PRIVATE KEY-----') !== false ||
            strpos($line, '-----BEGIN OPENSSH PRIVATE KEY-----') !== false) {
            mrl_audit_add_result(
                $results, $summary, $file, $lineNo,
                'Private key material',
                $severity,
                'Private key block detected. Key material withheld.',
                $accountRoot
            );
            continue;
        }

        // 7) Common token signatures. Only identify type, never print value.
        if (preg_match('/\bgh[pousr]_[A-Za-z0-9]{20,}\b/', $line)) {
            mrl_audit_add_result(
                $results, $summary, $file, $lineNo,
                'GitHub token-like value',
                $severity,
                'GitHub token signature detected. Value withheld.',
                $accountRoot
            );
            continue;
        }

        if (preg_match('/\bAKIA[0-9A-Z]{16}\b/', $line)) {
            mrl_audit_add_result(
                $results, $summary, $file, $lineNo,
                'AWS access key-like value',
                $severity,
                'AWS access-key signature detected. Value withheld.',
                $accountRoot
            );
            continue;
        }
    }
}

try {
    if ($docRoot === '' || !is_dir($docRoot)) {
        throw new RuntimeException('Unable to determine the production document root.');
    }

    require_once $docRoot . '/config.php';
    require_once $docRoot . '/config_mrl.php';
    require_once $docRoot . '/class.user.php';

    $userHome = new USER();

    if (!$userHome->is_logged_in()) {
        throw new RuntimeException('You are not logged in to the MRL website.');
    }

    if (!isAdmin($_SESSION['userSession'] ?? null)) {
        throw new RuntimeException('Your current MRL login is not authorized as an administrator.');
    }

    $authorized = true;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'run_scan') {
        $scanRoots = array();

        if (is_dir($docRoot)) {
            $scanRoots[] = $docRoot;
        }

        $backupRoot = $accountRoot . '/_mrl_backups';
        if (is_dir($backupRoot)) {
            $scanRoots[] = $backupRoot;
        }

        $privateRoot = $accountRoot . '/_mrl_private';
        if (is_dir($privateRoot)) {
            $scanRoots[] = $privateRoot;
        }

        // Scan account-root files themselves for config-like files.
        foreach (@scandir($accountRoot) ?: array() as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $accountRoot . DIRECTORY_SEPARATOR . $entry;
            if (is_file($full) && mrl_audit_is_candidate_file($full)) {
                mrl_audit_scan_file($full, $docRoot, $accountRoot, $results, $summary);
            }
        }

        foreach ($scanRoots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $fullPath = $item->getPathname();

                if ($item->isDir()) {
                    if (mrl_audit_should_skip_dir($item->getFilename(), $fullPath)) {
                        $iterator->next();
                    }
                    continue;
                }

                if (!$item->isFile() || !mrl_audit_is_candidate_file($fullPath)) {
                    continue;
                }

                mrl_audit_scan_file($fullPath, $docRoot, $accountRoot, $results, $summary);
            }
        }

        usort($results, function (array $a, array $b): int {
            $rank = array('HIGH' => 0, 'MEDIUM' => 1, 'INFO' => 2);
            $ra = $rank[$a['severity']] ?? 9;
            $rb = $rank[$b['severity']] ?? 9;
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            $cmp = strcmp($a['file'], $b['file']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return $a['line'] <=> $b['line'];
        });
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRL Credential Exposure Audit</title>
<style>
    :root { color-scheme: dark; }
    body {
        margin: 0;
        background: #11151b;
        color: #e8edf3;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 16px;
    }
    .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
    .panel {
        background: #1a2029;
        border: 1px solid #394554;
        border-radius: 10px;
        padding: 18px;
        margin: 16px 0;
    }
    .good, .info, .bad {
        border-radius: 8px;
        padding: 12px;
        margin: 12px 0;
    }
    .good { background: #173524; border: 1px solid #236f43; }
    .info { background: #182a40; border: 1px solid #31567f; }
    .bad  { background: #3d1c1c; border: 1px solid #7f2d2d; }
    button {
        background: #2e9d5b;
        color: white;
        border: 0;
        border-radius: 7px;
        padding: 12px 18px;
        font-size: 17px;
        font-weight: 700;
        cursor: pointer;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
    }
    th, td {
        border: 1px solid #3b4654;
        padding: 9px;
        vertical-align: top;
        text-align: left;
    }
    th { background: #222a35; }
    .sev-HIGH { color: #ff8e8e; font-weight: 700; }
    .sev-MEDIUM { color: #ffd27a; font-weight: 700; }
    .sev-INFO { color: #8fc8ff; font-weight: 700; }
    code {
        background: #0f1318;
        border: 1px solid #2e3845;
        border-radius: 4px;
        padding: 2px 5px;
        word-break: break-all;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 12px;
    }
    .stat {
        background: #12171e;
        border: 1px solid #303a47;
        border-radius: 8px;
        padding: 12px;
    }
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Credential Exposure Audit</h1>
    <div>Version v001 · Read-only · <?php echo mrl_audit_h(date('n/j/Y g:i:s a')); ?> America/New_York</div>

    <?php if ($error !== ''): ?>
        <div class="bad"><?php echo mrl_audit_h($error); ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>What this does</h2>
        <div class="info">
            Scans likely text/config files in <code>public_html</code>, <code>_mrl_backups</code>,
            <code>_mrl_private</code>, and account-root config-like files for hard-coded credentials or secret-like values.
            <strong>Detected secret values are never displayed.</strong>
        </div>
        <p>This page makes no changes, writes no report file, sends no mail, and runs no jobs.</p>

        <?php if ($authorized): ?>
        <form method="post">
            <input type="hidden" name="action" value="run_scan">
            <button type="submit">Run Read-Only Credential Audit</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authorized && $error === ''): ?>
    <div class="panel">
        <h2>Scan summary</h2>
        <div class="stats">
            <div class="stat"><strong>Files scanned</strong><br><?php echo (int)$summary['files_scanned']; ?></div>
            <div class="stat"><strong>Files skipped</strong><br><?php echo (int)$summary['files_skipped']; ?></div>
            <div class="stat"><strong>HIGH</strong><br><?php echo (int)$summary['high']; ?></div>
            <div class="stat"><strong>MEDIUM</strong><br><?php echo (int)$summary['medium']; ?></div>
            <div class="stat"><strong>INFO</strong><br><?php echo (int)$summary['info']; ?></div>
        </div>

        <?php if (!$results): ?>
            <div class="good">No credential-like findings were detected by this scanner.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Severity</th>
                        <th>File</th>
                        <th>Line</th>
                        <th>Finding</th>
                        <th>Safe note</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td class="sev-<?php echo mrl_audit_h($row['severity']); ?>"><?php echo mrl_audit_h($row['severity']); ?></td>
                        <td><code><?php echo mrl_audit_h($row['file']); ?></code></td>
                        <td><?php echo (int)$row['line']; ?></td>
                        <td><?php echo mrl_audit_h($row['type']); ?></td>
                        <td><?php echo mrl_audit_h($row['note']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
