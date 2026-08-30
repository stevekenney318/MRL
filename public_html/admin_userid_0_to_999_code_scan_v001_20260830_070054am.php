<?php
declare(strict_types=1);

/**
 * admin_userid_0_to_999_code_scan.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/30/2026 7:00:54 am
 *
 * PURPOSE:
 *   Read-only Step 2 codebase scan for the planned MRL test-account migration:
 *       userID 0 -> userID 999
 *
 * THIS FILE DOES NOT MODIFY ANY FILE OR DATABASE TABLE.
 *
 * It recursively scans the live public_html custom-code area for likely legacy
 * assumptions involving:
 *   - userID 0 / 999
 *   - <= 0 / > 0 user-id gates
 *   - $_SESSION['userSession']
 *   - "MRL test team"
 *   - alternate-user / impersonation helpers
 *   - SQL userID comparisons
 *
 * It intentionally skips large third-party/runtime trees such as WordPress core,
 * plugins, themes, vendor, backups, race-result snapshots, and migration backups.
 *
 * INSTALL / RUN:
 *   1. Upload this file to public_html/.
 *   2. Open it while logged in as an MRL admin.
 *   3. Save / send the resulting HTML report for review.
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

function cs_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

if (!isAdmin($_SESSION['userSession'] ?? null)) {
    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Not Authorized</title></head><body>';
    echo '<div style="max-width:900px;margin:40px auto;padding:20px;background:#2a1111;color:#fff;font-family:Arial,sans-serif;border:1px solid #8c4444;border-radius:12px;">';
    echo '<h1>Not Authorized</h1><p>You are not authorized to view this code scan.</p></div>';
    echo '</body></html>';
    exit;
}

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');

$allowedExtensions = [
    'php' => true,
    'inc' => true,
    'html' => true,
    'htm' => true,
    'js' => true,
    'json' => true,
    'md' => true,
    'txt' => true,
];

$skipDirectoryNames = [
    '.git' => true,
    '.qidb' => true,
    '.well-known' => true,
    'node_modules' => true,
    'vendor' => true,
    'wp-admin' => true,
    'wp-includes' => true,
    'plugins' => true,
    'themes' => true,
    'uploads' => true,
    'cache' => true,
    'logs' => true,
    'db_backups' => true,
    '_migration_backups' => true,
    'backups' => true,
    'backup' => true,
    'archive' => true,
    'to_be_deleted' => true,
];

$skipPathFragments = [
    '/race_results/2017/',
    '/race_results/2018/',
    '/race_results/2019/',
    '/race_results/2020/',
    '/race_results/2021/',
    '/race_results/2022/',
    '/race_results/2023/',
    '/race_results/2024/',
    '/race_results/2025/',
    '/race_results/2026/',
];

$patterns = [
    [
        'category' => 'MRL test team literal',
        'severity' => 'HIGH',
        'regex' => '/MRL\s+test\s+team/i',
    ],
    [
        'category' => 'userID literal 0 comparison',
        'severity' => 'HIGH',
        'regex' => '/\buser_?id\b.{0,50}(?:===|==|=|<>|!=|<=|>=|<|>)\s*[\'"]?0[\'"]?/i',
    ],
    [
        'category' => 'userID literal 999 comparison',
        'severity' => 'INFO',
        'regex' => '/\buser_?id\b.{0,50}(?:===|==|=|<>|!=|<=|>=|<|>)\s*[\'"]?999[\'"]?/i',
    ],
    [
        'category' => 'variable ID <= 0 / < 1 gate',
        'severity' => 'HIGH',
        'regex' => '/\$(?:uid|userId|userID|workingUserId|currentUserId|selectedUserId|targetUserId)\b.{0,20}(?:<=\s*0|<\s*1)/i',
    ],
    [
        'category' => 'variable ID > 0 / >= 1 gate',
        'severity' => 'MEDIUM',
        'regex' => '/\$(?:uid|userId|userID|workingUserId|currentUserId|selectedUserId|targetUserId)\b.{0,20}(?:>\s*0|>=\s*1)/i',
    ],
    [
        'category' => 'userSession reference',
        'severity' => 'INFO',
        'regex' => '/\$_SESSION\s*\[\s*[\'"]userSession[\'"]\s*\]/i',
    ],
    [
        'category' => 'alternate user / impersonation',
        'severity' => 'MEDIUM',
        'regex' => '/(?:alternate\s+user|view\s+team\s+page\s+as|impersonat|act\s+as\s+user|selectedUser|alternateUser)/i',
    ],
    [
        'category' => 'SQL userID filter',
        'severity' => 'MEDIUM',
        'regex' => '/\b(?:WHERE|AND|OR)\b.{0,80}\buserID\b/i',
    ],
];

function cs_should_skip_dir(string $path, string $name, array $skipDirectoryNames): bool
{
    $lowerName = strtolower($name);
    if (isset($skipDirectoryNames[$lowerName])) {
        return true;
    }

    $normalized = strtolower(str_replace('\\', '/', $path));

    if (strpos($normalized, '/wp-content/plugins/') !== false) {
        return true;
    }
    if (strpos($normalized, '/wp-content/themes/') !== false) {
        return true;
    }
    if (strpos($normalized, '/wp-content/uploads/') !== false) {
        return true;
    }

    return false;
}

function cs_should_skip_file_path(string $relative, array $skipPathFragments): bool
{
    $normalized = '/' . ltrim(strtolower(str_replace('\\', '/', $relative)), '/');

    foreach ($skipPathFragments as $frag) {
        if (strpos($normalized, strtolower($frag)) !== false) {
            return true;
        }
    }

    return false;
}

function cs_excerpt(array $lines, int $lineIndex): string
{
    $start = max(0, $lineIndex - 1);
    $end = min(count($lines) - 1, $lineIndex + 1);
    $out = [];

    for ($i = $start; $i <= $end; $i++) {
        $prefix = ($i === $lineIndex) ? '>> ' : '   ';
        $text = rtrim((string)$lines[$i], "\r\n");
        if (strlen($text) > 260) {
            $text = substr($text, 0, 260) . '…';
        }
        $out[] = $prefix . ($i + 1) . ': ' . $text;
    }

    return implode("\n", $out);
}

$filesScanned = 0;
$filesSkipped = 0;
$totalBytes = 0;
$matches = [];
$matchedFiles = [];

$dir = new RecursiveDirectoryIterator(
    $root,
    FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME
);

$filter = new RecursiveCallbackFilterIterator(
    $dir,
    function ($current, $key, $iterator) use ($skipDirectoryNames) {
        if ($current->isDir()) {
            return !cs_should_skip_dir($current->getPathname(), $current->getFilename(), $skipDirectoryNames);
        }
        return true;
    }
);

$iterator = new RecursiveIteratorIterator($filter);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $fullPath = $fileInfo->getPathname();
    $relative = ltrim(str_replace('\\', '/', substr($fullPath, strlen($root))), '/');

    if (cs_should_skip_file_path($relative, $skipPathFragments)) {
        $filesSkipped++;
        continue;
    }

    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    if ($ext === '' || !isset($allowedExtensions[$ext])) {
        $filesSkipped++;
        continue;
    }

    $size = @filesize($fullPath);
    if ($size === false || $size > 2 * 1024 * 1024) {
        $filesSkipped++;
        continue;
    }

    $content = @file_get_contents($fullPath);
    if (!is_string($content)) {
        $filesSkipped++;
        continue;
    }

    $filesScanned++;
    $totalBytes += strlen($content);

    $lines = preg_split('/\r\n|\r|\n/', $content);
    if (!is_array($lines)) {
        continue;
    }

    foreach ($lines as $idx => $line) {
        $lineText = (string)$line;

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern['regex'], $lineText)) {
                continue;
            }

            $key = strtolower($relative);
            $matchedFiles[$key] = $relative;

            $matches[] = [
                'file' => $relative,
                'line' => $idx + 1,
                'category' => $pattern['category'],
                'severity' => $pattern['severity'],
                'excerpt' => cs_excerpt($lines, $idx),
            ];
        }
    }
}

usort($matches, function ($a, $b) {
    $sevOrder = ['HIGH' => 1, 'MEDIUM' => 2, 'INFO' => 3];
    $sa = $sevOrder[$a['severity']] ?? 9;
    $sb = $sevOrder[$b['severity']] ?? 9;

    if ($sa !== $sb) {
        return $sa <=> $sb;
    }

    $cmp = strcasecmp((string)$a['file'], (string)$b['file']);
    if ($cmp !== 0) {
        return $cmp;
    }

    return ((int)$a['line']) <=> ((int)$b['line']);
});

$categoryCounts = [];
$severityCounts = ['HIGH' => 0, 'MEDIUM' => 0, 'INFO' => 0];

foreach ($matches as $m) {
    $cat = (string)$m['category'];
    if (!isset($categoryCounts[$cat])) {
        $categoryCounts[$cat] = 0;
    }
    $categoryCounts[$cat]++;

    $sev = (string)$m['severity'];
    if (!isset($severityCounts[$sev])) {
        $severityCounts[$sev] = 0;
    }
    $severityCounts[$sev]++;
}

ksort($categoryCounts, SORT_NATURAL | SORT_FLAG_CASE);
ksort($matchedFiles, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL userID 0 → 999 Code Scan</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
    --bg:#101214;
    --panel:#1d2023;
    --panel2:#17191b;
    --border:#4b5055;
    --text:#f0f0f0;
    --muted:#b8bec5;
    --gold:#efc77e;
    --blue:#55c7ff;
    --green:#63e69a;
    --red:#ff7e7e;
    --amber:#ffd479;
}
*{box-sizing:border-box}
html{background:var(--bg)}
body{margin:0;color:var(--text);font-family:Tahoma,Verdana,"Segoe UI",Arial,sans-serif;font-size:14px}
.wrap{width:97%;max-width:1700px;margin:18px auto 60px}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2,h3{color:var(--gold);margin-top:0}
h1{font-size:28px} h2{font-size:21px}
.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:700}
.banner.readonly{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}
.ok{color:var(--green);font-weight:700}
.high{color:var(--red);font-weight:700}
.medium{color:var(--amber);font-weight:700}
.info{color:var(--blue);font-weight:700}
.muted{color:var(--muted)}
code,.mono,pre{font-family:Consolas,"Courier New",monospace}
code{color:var(--blue)}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border-bottom:1px solid #3a3e42;padding:8px 9px;text-align:left;vertical-align:top}
th{color:#ffe0a0;background:var(--panel2);position:sticky;top:0}
.num{text-align:right}
.center{text-align:center}
.summary{display:flex;gap:14px;flex-wrap:wrap}
.metric{min-width:165px;padding:12px 14px;background:var(--panel2);border:1px solid #45494d;border-radius:10px}
.metric .value{font-size:24px;font-weight:800}
.metric .label{font-size:12px;color:var(--muted);margin-top:3px}
pre{white-space:pre-wrap;word-break:break-word;margin:0;font-size:12px;line-height:1.45;color:#e5e5e5}
.filelist{columns:2;column-gap:30px}
@media(max-width:900px){.filelist{columns:1}}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
    <h1>MRL userID 0 → 999 Code Scan</h1>
    <div class="banner readonly">READ-ONLY CODE SCAN — no files or database rows are changed.</div>
    <p><strong>Generated:</strong> 8/30/2026 7:00:54 am America/New_York</p>
    <p><strong>Scan root:</strong> <code><?php echo cs_h($root); ?></code></p>
</div>

<div class="card">
    <h2>Scan Summary</h2>
    <div class="summary">
        <div class="metric"><div class="value"><?php echo number_format($filesScanned); ?></div><div class="label">files scanned</div></div>
        <div class="metric"><div class="value"><?php echo number_format($filesSkipped); ?></div><div class="label">files skipped</div></div>
        <div class="metric"><div class="value"><?php echo number_format(count($matchedFiles)); ?></div><div class="label">files with matches</div></div>
        <div class="metric"><div class="value"><?php echo number_format(count($matches)); ?></div><div class="label">total matches</div></div>
        <div class="metric"><div class="value high"><?php echo number_format($severityCounts['HIGH'] ?? 0); ?></div><div class="label">HIGH-priority matches</div></div>
        <div class="metric"><div class="value medium"><?php echo number_format($severityCounts['MEDIUM'] ?? 0); ?></div><div class="label">MEDIUM-priority matches</div></div>
        <div class="metric"><div class="value info"><?php echo number_format($severityCounts['INFO'] ?? 0); ?></div><div class="label">INFO matches</div></div>
    </div>
</div>

<div class="card">
    <h2>What Was Intentionally Skipped</h2>
    <p class="muted">
        WordPress core, WordPress plugins/themes/uploads, vendor/node_modules, backups, db_backups,
        migration backups, cache/log trees, and race_results yearly snapshot folders 2017–2026.
        The purpose is to inspect active/custom MRL application code without drowning the report in
        third-party code or immutable historical snapshot content.
    </p>
</div>

<div class="card">
    <h2>Category Counts</h2>
    <?php if (empty($categoryCounts)): ?>
        <p class="ok">No matching patterns were found.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Category</th><th class="num">Matches</th></tr></thead>
            <tbody>
            <?php foreach ($categoryCounts as $cat => $count): ?>
                <tr><td><?php echo cs_h($cat); ?></td><td class="num"><?php echo (int)$count; ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Files With Matches</h2>
    <?php if (empty($matchedFiles)): ?>
        <p class="ok">No files matched.</p>
    <?php else: ?>
        <div class="filelist">
        <?php foreach ($matchedFiles as $file): ?>
            <div><code><?php echo cs_h($file); ?></code></div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Detailed Matches</h2>
    <?php if (empty($matches)): ?>
        <p class="ok">No matches found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:90px">Severity</th>
                    <th style="width:280px">Category</th>
                    <th style="width:360px">File</th>
                    <th style="width:75px">Line</th>
                    <th>Context</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($matches as $m): ?>
                <?php $sevClass = strtolower((string)$m['severity']); ?>
                <tr>
                    <td class="<?php echo cs_h($sevClass); ?>"><?php echo cs_h($m['severity']); ?></td>
                    <td><?php echo cs_h($m['category']); ?></td>
                    <td><code><?php echo cs_h($m['file']); ?></code></td>
                    <td class="mono"><?php echo (int)$m['line']; ?></td>
                    <td><pre><?php echo cs_h($m['excerpt']); ?></pre></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Step 2 Conclusion</h2>
    <p>
        This is an inventory report only. HIGH/MEDIUM matches are not automatically bugs.
        They identify the files we should inspect before changing the database from userID 0 to 999.
    </p>
    <p class="muted">
        After reviewing this report, the next step is to classify each relevant file as:
        <strong>must change before migration</strong>, <strong>works automatically after 999</strong>,
        or <strong>unrelated / safe to leave alone</strong>.
    </p>
</div>

</div>
</body>
</html>
