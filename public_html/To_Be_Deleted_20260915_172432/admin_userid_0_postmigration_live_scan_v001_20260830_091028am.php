<?php
declare(strict_types=1);

/**
 * admin_userid_0_postmigration_live_scan.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/30/2026 9:10:28 am
 *
 * PURPOSE:
 *   Read-only post-migration scan of CURRENT/LIVE custom PHP code for
 *   assumptions about the retired MRL test-account userID 0.
 *
 *   The scan is intentionally much narrower than the earlier broad codebase
 *   inventory. It is meant to answer:
 *
 *     "Is any active live custom PHP code still assuming that the
 *      MRL test account is userID 0?"
 *
 * WHAT IT LOOKS FOR:
 *   - literal userID comparisons to 0
 *   - SQL userID filters involving 0
 *   - positive-ID gates such as <= 0, < 1, > 0, >= 1
 *   - explicit userID 999 references
 *   - literal "MRL test team" references
 *   - alternate-user / view-as references
 *
 * WHAT IT SKIPS:
 *   - obvious backups / archives / TESTPHP8 remnants
 *   - installers, fix scripts, discovery/scanner utilities
 *   - WordPress core, vendor, node_modules, .git
 *   - common historical/versioned copies when the filename itself clearly
 *     identifies it as a dated/versioned artifact rather than the active file
 *
 * IMPORTANT:
 *   This file DOES NOT modify any file or database row.
 *   Review results before deciding whether anything needs changing.
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

function s_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function s_norm_path(string $path): string
{
    return str_replace('\\', '/', $path);
}

function s_rel(string $path, string $root): string
{
    $path = s_norm_path($path);
    $root = rtrim(s_norm_path($root), '/') . '/';
    return strpos($path, $root) === 0 ? substr($path, strlen($root)) : $path;
}

function s_should_skip_dir(string $name): bool
{
    $n = strtolower($name);

    $exact = [
        '.git',
        '.github',
        'node_modules',
        'vendor',
        'wp-admin',
        'wp-includes',
        'backups',
        'backup',
        'archive',
        'archives',
        'testphp8',
        'test_php8',
        'old',
        'obsolete',
        'deprecated',
        'tmp',
        'temp',
        'cache',
    ];

    if (in_array($n, $exact, true)) {
        return true;
    }

    if (strpos($n, 'testphp8') !== false) {
        return true;
    }

    if (preg_match('/(?:^|[_-])(backup|archive|obsolete|deprecated)(?:$|[_-])/i', $name)) {
        return true;
    }

    return false;
}

function s_should_skip_file(string $filename): bool
{
    $n = strtolower($filename);

    // Non-PHP files are outside the scope of this final live-code pass.
    if (substr($n, -4) !== '.php') {
        return true;
    }

    // Explicit utility / migration / repair artifacts.
    $prefixes = [
        'install_',
        'installer_',
        'fix_',
        'repair_',
        'restore_',
        'rollback_',
        'discovery_',
        'scan_',
        'scanner_',
        'admin_userid_0_to_999_',
        'admin_userid_0_postmigration_',
    ];

    foreach ($prefixes as $prefix) {
        if (strpos($n, $prefix) === 0) {
            return true;
        }
    }

    // Common dated generated copies, e.g. name_v003_20260830_083139am.php
    if (preg_match('/_v\d{3,}_[12]\d{7}_\d{6}(?:am|pm)\.php$/i', $filename)) {
        return true;
    }

    // Common explicit historical-copy naming.
    if (preg_match('/(?:^|[_-])(old|backup|bak|copy|archive|archived|obsolete|deprecated)(?:[_-]|\.)/i', $filename)) {
        return true;
    }

    return false;
}

function s_add_hit(
    array &$hits,
    string $file,
    int $lineNo,
    string $severity,
    string $category,
    string $context
): void {
    $hits[] = [
        'file' => $file,
        'line' => $lineNo,
        'severity' => $severity,
        'category' => $category,
        'context' => trim($context),
    ];
}

function s_classify_line(string $line): array
{
    $matches = [];

    // Explicit 999 is generally expected after migration, but worth inventorying.
    if (preg_match('/\b(?:userID|userid|userSession)\b[^\r\n]{0,40}(?:===|==|!=|<>|<=|>=|=)\s*[\'"]?999[\'"]?/i', $line)
        || preg_match('/\b999\b[^\r\n]{0,40}\b(?:userID|userid|userSession)\b/i', $line)) {
        $matches[] = ['INFO', 'explicit 999 reference'];
    }

    if (stripos($line, 'MRL test team') !== false) {
        $matches[] = ['INFO', 'MRL test team literal'];
    }

    if (preg_match('/\b(team_view_as|view.?as|alternate.?user|impersonat)/i', $line)) {
        $matches[] = ['INFO', 'alternate user / view-as'];
    }

    // High-interest stale-zero comparisons.
    if (preg_match('/\buserID\b\s*(?:===|==)\s*[\'"]?0[\'"]?/i', $line)
        || preg_match('/\b0\b\s*(?:===|==)\s*\$?[A-Za-z_][A-Za-z0-9_]*userID\b/i', $line)) {
        $matches[] = ['HIGH', 'userID equals 0'];
    }

    if (preg_match('/\buserID\b\s*(?:!=|!==|<>)\s*[\'"]?0[\'"]?/i', $line)) {
        $matches[] = ['HIGH', 'userID not-equal 0'];
    }

    // SQL forms such as WHERE userID > 0, AND userID != 0, userID IN (0,...)
    if (preg_match('/\b(?:WHERE|AND|OR|HAVING|ON)\b[^\r\n]{0,80}\buserID\b[^\r\n]{0,30}\b0\b/i', $line)) {
        $matches[] = ['HIGH', 'SQL userID filter involving 0'];
    }

    if (preg_match('/\buserID\b\s+(?:NOT\s+)?IN\s*\([^)]*\b0\b[^)]*\)/i', $line)) {
        $matches[] = ['HIGH', 'SQL userID IN/NOT IN contains 0'];
    }

    // Positive-ID gates are usually fine now that MRL is 999, but verify once.
    if (preg_match('/\b(?:userID|uid|userSession)\b\s*(?:<=\s*0|<\s*1)/i', $line)
        || preg_match('/\$\w*(?:uid|userid|usersession)\w*\s*(?:<=\s*0|<\s*1)/i', $line)) {
        $matches[] = ['MEDIUM', 'positive-ID gate rejects 0'];
    }

    if (preg_match('/\b(?:userID|uid|userSession)\b\s*(?:>\s*0|>=\s*1)/i', $line)
        || preg_match('/\$\w*(?:uid|userid|usersession)\w*\s*(?:>\s*0|>=\s*1)/i', $line)) {
        $matches[] = ['MEDIUM', 'positive-ID gate accepts positive IDs'];
    }

    return $matches;
}

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

if (!isAdmin($_SESSION['userSession'] ?? null)) {
    http_response_code(403);
    exit('Not authorized.');
}

$root = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);

$scannedFiles = 0;
$skippedFiles = 0;
$skippedDirs = 0;
$hits = [];
$scannedList = [];
$skippedExamples = [];

$dir = new RecursiveDirectoryIterator(
    $root,
    FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
);

$filter = new RecursiveCallbackFilterIterator(
    $dir,
    function (SplFileInfo $current) use (&$skippedDirs, &$skippedExamples, $root): bool {
        if ($current->isDir()) {
            if (s_should_skip_dir($current->getFilename())) {
                $skippedDirs++;
                if (count($skippedExamples) < 50) {
                    $skippedExamples[] = [
                        'type' => 'directory',
                        'path' => s_rel($current->getPathname(), $root),
                    ];
                }
                return false;
            }
        }
        return true;
    }
);

$it = new RecursiveIteratorIterator($filter);

foreach ($it as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    $filename = $fileInfo->getFilename();
    $full = $fileInfo->getPathname();
    $rel = s_rel($full, $root);

    if (s_should_skip_file($filename)) {
        $skippedFiles++;
        if (count($skippedExamples) < 50) {
            $skippedExamples[] = [
                'type' => 'file',
                'path' => $rel,
            ];
        }
        continue;
    }

    $scannedFiles++;
    $scannedList[] = $rel;

    $lines = @file($full, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        s_add_hit($hits, $rel, 0, 'HIGH', 'read error', 'Could not read file.');
        continue;
    }

    foreach ($lines as $i => $line) {
        $lineNo = $i + 1;
        $classes = s_classify_line($line);

        foreach ($classes as $c) {
            s_add_hit($hits, $rel, $lineNo, $c[0], $c[1], $line);
        }
    }
}

// De-duplicate identical file/line/category hits.
$uniq = [];
foreach ($hits as $hit) {
    $key = $hit['file'] . '|' . $hit['line'] . '|' . $hit['category'];
    $uniq[$key] = $hit;
}
$hits = array_values($uniq);

$severityOrder = ['HIGH' => 0, 'MEDIUM' => 1, 'INFO' => 2];
usort($hits, function (array $a, array $b) use ($severityOrder): int {
    $sa = $severityOrder[$a['severity']] ?? 9;
    $sb = $severityOrder[$b['severity']] ?? 9;
    if ($sa !== $sb) return $sa <=> $sb;
    $f = strcasecmp($a['file'], $b['file']);
    if ($f !== 0) return $f;
    return ((int)$a['line']) <=> ((int)$b['line']);
});

$countsBySeverity = ['HIGH' => 0, 'MEDIUM' => 0, 'INFO' => 0];
$countsByCategory = [];
$filesWithHits = [];

foreach ($hits as $hit) {
    $sev = $hit['severity'];
    $cat = $hit['category'];

    if (!isset($countsBySeverity[$sev])) $countsBySeverity[$sev] = 0;
    $countsBySeverity[$sev]++;

    if (!isset($countsByCategory[$cat])) $countsByCategory[$cat] = 0;
    $countsByCategory[$cat]++;

    $filesWithHits[$hit['file']] = true;
}

$highFiles = [];
foreach ($hits as $hit) {
    if ($hit['severity'] === 'HIGH') {
        $highFiles[$hit['file']] = true;
    }
}

$summaryStatus = count($highFiles) === 0
    ? 'PASS_NO_HIGH_RISK_ZERO_REFERENCES'
    : 'REVIEW_HIGH_RISK_ZERO_REFERENCES';

$report = [
    'report_version' => 'v001',
    'generated_at' => date('Y-m-d H:i:s'),
    'timezone' => 'America/New_York',
    'root' => $root,
    'purpose' => 'Post-migration targeted scan of active/live custom PHP for retired MRL userID 0 assumptions.',
    'summary_status' => $summaryStatus,
    'files_scanned' => $scannedFiles,
    'files_skipped' => $skippedFiles,
    'directories_skipped' => $skippedDirs,
    'files_with_hits' => count($filesWithHits),
    'files_with_high_hits' => count($highFiles),
    'hits_total' => count($hits),
    'counts_by_severity' => $countsBySeverity,
    'counts_by_category' => $countsByCategory,
    'hits' => $hits,
    'scanned_files' => $scannedList,
    'skipped_examples' => $skippedExamples,
];

if (isset($_GET['export']) && $_GET['export'] === 'json') {
    $name = 'MRL_userID_0_postmigration_live_scan_' . date('Ymd_His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function s_sev_class(string $sev): string
{
    if ($sev === 'HIGH') return 'high';
    if ($sev === 'MEDIUM') return 'medium';
    return 'info';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL Post-Migration Live userID Scan</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
    --bg:#101214;--panel:#1d2023;--panel2:#17191b;--border:#4b5055;
    --text:#f0f0f0;--muted:#b8bec5;--gold:#efc77e;--blue:#55c7ff;
    --green:#63e69a;--red:#ff7e7e;--amber:#ffd479;
}
*{box-sizing:border-box}
html{background:var(--bg)}
body{margin:0;color:var(--text);font-family:Tahoma,Verdana,"Segoe UI",Arial,sans-serif;font-size:14px}
.wrap{width:97%;max-width:1600px;margin:18px auto 60px}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}
h1{font-size:28px}h2{font-size:21px}
.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:700}
.banner.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}
.banner.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}
code,.mono{font-family:Consolas,"Courier New",monospace}
code{color:var(--blue)}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border-bottom:1px solid #3a3e42;padding:8px 9px;text-align:left;vertical-align:top}
th{color:#ffe0a0;background:var(--panel2);position:sticky;top:0}
.high{color:var(--red);font-weight:800}
.medium{color:var(--amber);font-weight:800}
.info{color:#8bd9ff;font-weight:800}
.num{text-align:right}
.btn{display:inline-block;padding:11px 18px;border-radius:8px;font-weight:800;text-decoration:none;background:#176fa4;color:#fff;border:1px solid #54b9ef}
.muted{color:var(--muted)}
.context{font-family:Consolas,"Courier New",monospace;white-space:pre-wrap;word-break:break-word}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px}
.stat{background:var(--panel2);border:1px solid #3d4247;border-radius:10px;padding:12px}
.stat strong{display:block;font-size:22px;color:#fff}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>MRL Post-Migration Live userID Scan</h1>
<p><strong>Version:</strong> v001 &nbsp; | &nbsp; <strong>Generated:</strong> 8/30/2026 9:10:28 am America/New_York</p>
<p><strong>READ ONLY.</strong> This is the narrow post-migration cleanup scan, not the earlier all-files inventory.</p>

<?php if (count($highFiles) === 0): ?>
<div class="banner ok">NO HIGH-RISK LIVE userID 0 ASSUMPTIONS FOUND by this targeted scan.</div>
<?php else: ?>
<div class="banner bad"><?php echo count($highFiles); ?> LIVE FILE(S) HAVE HIGH-RISK userID 0 REFERENCES TO REVIEW.</div>
<?php endif; ?>

<a class="btn" href="?export=json">Export Review Report → JSON</a>
</div>

<div class="card">
<h2>Summary</h2>
<div class="grid">
<div class="stat"><span>PHP files scanned</span><strong><?php echo $scannedFiles; ?></strong></div>
<div class="stat"><span>Files skipped</span><strong><?php echo $skippedFiles; ?></strong></div>
<div class="stat"><span>Directories skipped</span><strong><?php echo $skippedDirs; ?></strong></div>
<div class="stat"><span>Files with matches</span><strong><?php echo count($filesWithHits); ?></strong></div>
<div class="stat"><span>HIGH files</span><strong class="<?php echo count($highFiles) ? 'high' : 'info'; ?>"><?php echo count($highFiles); ?></strong></div>
<div class="stat"><span>Total matches</span><strong><?php echo count($hits); ?></strong></div>
</div>
<p>
<strong class="high">HIGH:</strong> literal zero logic that may still encode the retired test-account identity.
&nbsp;&nbsp;
<strong class="medium">MEDIUM:</strong> generic positive-ID gates, usually valid with userID 999.
&nbsp;&nbsp;
<strong class="info">INFO:</strong> expected 999/test-team/view-as references.
</p>
</div>

<div class="card">
<h2>Matches</h2>
<?php if (!$hits): ?>
<p>No targeted matches found.</p>
<?php else: ?>
<table>
<thead>
<tr><th>Severity</th><th>File</th><th class="num">Line</th><th>Category</th><th>Context</th></tr>
</thead>
<tbody>
<?php foreach ($hits as $hit): ?>
<tr>
<td class="<?php echo s_sev_class($hit['severity']); ?>"><?php echo s_h($hit['severity']); ?></td>
<td><code><?php echo s_h($hit['file']); ?></code></td>
<td class="num"><?php echo (int)$hit['line']; ?></td>
<td><?php echo s_h($hit['category']); ?></td>
<td class="context"><?php echo s_h($hit['context']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<div class="card">
<h2>Scope Notes</h2>
<p>
This scan deliberately ignores obvious installers, repair utilities, backups, archives, TESTPHP8 remnants,
WordPress core, vendor code, and clearly dated/versioned historical copies. A hit is not automatically a bug;
the point is to leave us with a small human-review list of CURRENT code.
</p>
</div>

</div>
</body>
</html>
