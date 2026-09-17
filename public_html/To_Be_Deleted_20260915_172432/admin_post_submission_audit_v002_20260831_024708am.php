<?php
declare(strict_types=1);

/**
 * admin_post_submission_audit.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/31/2026 2:47:08 am
 *
 * PURPOSE:
 *   Admin-only diagnostic tool for finding POST-driven pages and forms that may
 *   cause browser "Confirm form resubmission" behavior or that may be candidates
 *   for POST/Redirect/GET (PRG) or quiet background submission.
 *
 * WHAT IT DOES:
 *   - Scans active PHP files under public_html.
 *   - Finds HTML forms that submit with POST.
 *   - Finds PHP request handlers that branch on POST / REQUEST_METHOD.
 *   - Looks for redirect behavior (header Location, redirect helpers).
 *   - Looks for fetch / XMLHttpRequest / AJAX-style submission.
 *   - Looks for direct HTML output after POST handling.
 *   - Classifies findings conservatively:
 *       REVIEW - likely worth inspecting
 *       PRG CANDIDATE - POST handler appears to render directly without redirect
 *       QUIET-SUBMIT CANDIDATE - form/handler may benefit from no-reload UX
 *       LIKELY OK - evidence of redirect or background submission
 *       INFO - POST-related but not enough evidence for a stronger classification
 *   - Exports the full report as JSON for review in ChatGPT.
 *
 * IMPORTANT:
 *   - Read-only. Makes NO file or database changes.
 *   - Classification is heuristic, not an automatic instruction to change code.
 *   - Historical/backups/installers are intentionally skipped by default.
 *
 * INSTALLATION:
 *   Upload to public_html and open it while logged in as an Admin.
 *
 * v002:
 *   - FIX: JSON export now tolerates invalid/non-UTF-8 bytes found in scanned
 *     legacy source snippets by substituting safe Unicode replacement markers.
 *   - NEW: JSON export failure now reports json_last_error_msg() for diagnosis.
 *
 * NO DATABASE CHANGES.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

require __DIR__ . '/config.php';
require __DIR__ . '/config_mrl.php';

$uid = (int)($_SESSION['userSession'] ?? 0);
if (!isAdmin($uid)) {
    http_response_code(403);
    exit('Admin access required.');
}

function apsa_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function apsa_lf(string $text): string
{
    return str_replace(["\r\n", "\r"], "\n", $text);
}

function apsa_rel(string $root, string $path): string
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $path = str_replace('\\', '/', $path);
    if (strpos($path, $root . '/') === 0) {
        return substr($path, strlen($root) + 1);
    }
    return $path;
}

function apsa_is_skipped(string $relative): bool
{
    $p = '/' . ltrim(str_replace('\\', '/', strtolower($relative)), '/');

    $skipParts = [
        '/_migration_backups/',
        '/backup/',
        '/backups/',
        '/archive/',
        '/archives/',
        '/old/',
        '/older/',
        '/obsolete/',
        '/deprecated/',
        '/testphp8/',
        '/vendor/',
        '/node_modules/',
        '/.git/',
    ];

    foreach ($skipParts as $part) {
        if (strpos($p, $part) !== false) {
            return true;
        }
    }

    $base = strtolower(basename($relative));

    if (preg_match('/(^|[_\-.])(ng|nogood)([_\-.]|$)/i', $base)) {
        return true;
    }

    if (preg_match('/(^|[_\-.])(backup|bak|old|copy|archive)([_\-.]|$)/i', $base)) {
        return true;
    }

    if (preg_match('/^install_.*\.php$/i', $base)) {
        return true;
    }

    if (preg_match('/^installer.*\.php$/i', $base)) {
        return true;
    }

    if (preg_match('/_v\d{3}_\d{8}_\d{6}(am|pm)\.php$/i', $base)) {
        return true;
    }

    return false;
}

function apsa_line_number(string $text, int $offset): int
{
    if ($offset <= 0) return 1;
    return substr_count(substr($text, 0, $offset), "\n") + 1;
}

function apsa_excerpt(string $text, int $offset, int $radius = 140): string
{
    $start = max(0, $offset - $radius);
    $length = min(strlen($text) - $start, $radius * 2);
    $snippet = substr($text, $start, $length);
    $snippet = preg_replace('/\s+/', ' ', (string)$snippet);
    return trim((string)$snippet);
}

function apsa_find_matches(string $text, string $pattern, string $label): array
{
    $items = [];
    if (preg_match_all($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $match) {
            $items[] = [
                'type' => $label,
                'line' => apsa_line_number($text, (int)$match[1]),
                'match' => (string)$match[0],
                'excerpt' => apsa_excerpt($text, (int)$match[1]),
            ];
        }
    }
    return $items;
}

function apsa_bool_match(string $text, string $pattern): bool
{
    return preg_match($pattern, $text) === 1;
}

function apsa_scan_file(string $root, string $file): ?array
{
    $relative = apsa_rel($root, $file);
    if (apsa_is_skipped($relative)) {
        return null;
    }

    $raw = @file_get_contents($file);
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $text = apsa_lf($raw);

    $postForms = apsa_find_matches(
        $text,
        '~<form\b[^>]*\bmethod\s*=\s*["\']?post["\']?[^>]*>~i',
        'post_form'
    );

    $postMethodChecks = array_merge(
        apsa_find_matches(
            $text,
            '~\$_SERVER\s*\[\s*["\']REQUEST_METHOD["\']\s*\]\s*===?\s*["\']POST["\']~i',
            'request_method_post'
        ),
        apsa_find_matches(
            $text,
            '~\$_SERVER\s*\[\s*["\']REQUEST_METHOD["\']\s*\]\s*!==?\s*["\']GET["\']~i',
            'request_method_non_get'
        ),
        apsa_find_matches(
            $text,
            '~isset\s*\(\s*\$_POST\b~i',
            'isset_post'
        ),
        apsa_find_matches(
            $text,
            '~\$_POST\s*\[~i',
            'post_usage'
        )
    );

    if (!$postForms && !$postMethodChecks) {
        return null;
    }

    $hasHeaderLocation = apsa_bool_match($text, '~header\s*\(\s*["\']Location\s*:~i');
    $hasRedirectHelper = apsa_bool_match($text, '~->redirect\s*\(|\bredirect\s*\(~i');
    $hasFetch = apsa_bool_match($text, '~\bfetch\s*\(|XMLHttpRequest|\.ajax\s*\(|axios\.~i');
    $hasPostResponseRender = apsa_bool_match(
        $text,
        '~\$_SERVER\s*\[\s*["\']REQUEST_METHOD["\']\s*\].{0,500}(echo|print|<!doctype|<html|include|require)~is'
    );
    $hasExitAfterRedirect = apsa_bool_match(
        $text,
        '~header\s*\(\s*["\']Location\s*:[^;]+;\s*(exit|die)\s*[\(;]~is'
    );
    $hasCsrf = apsa_bool_match($text, '~csrf|xsrf|hash_equals\s*\(~i');
    $hasSession = apsa_bool_match($text, '~session_start\s*\(|\$_SESSION\b~i');

    $formActions = [];
    if (preg_match_all('~<form\b([^>]*)>~i', $text, $fm, PREG_OFFSET_CAPTURE)) {
        foreach ($fm[0] as $idx => $whole) {
            $attrs = $fm[1][$idx][0] ?? '';
            if (!preg_match('~\bmethod\s*=\s*["\']?post["\']?~i', $attrs)) continue;

            $action = '';
            if (preg_match('~\baction\s*=\s*(["\'])(.*?)\1~i', $attrs, $am)) {
                $action = $am[2];
            } elseif (preg_match('~\baction\s*=\s*([^\s>]+)~i', $attrs, $am)) {
                $action = $am[1];
            }

            $formActions[] = [
                'line' => apsa_line_number($text, (int)$whole[1]),
                'action' => $action,
                'tag' => preg_replace('/\s+/', ' ', trim((string)$whole[0])),
            ];
        }
    }

    $evidence = [];
    foreach (array_merge($postForms, $postMethodChecks) as $item) {
        $key = $item['type'] . ':' . $item['line'] . ':' . $item['match'];
        $evidence[$key] = $item;
    }
    $evidence = array_values($evidence);

    $classification = 'INFO';
    $reason = 'POST-related code found; not enough evidence for stronger classification.';

    if ($hasFetch) {
        $classification = 'LIKELY OK';
        $reason = 'Background submission code is already present.';
    } elseif ($hasHeaderLocation || $hasRedirectHelper) {
        $classification = 'LIKELY OK';
        $reason = 'Redirect behavior is present, suggesting POST/Redirect/GET or equivalent.';
    } elseif ($postMethodChecks && $hasPostResponseRender) {
        $classification = 'PRG CANDIDATE';
        $reason = 'POST handling appears to continue into page rendering without an obvious redirect.';
    } elseif ($postForms && !$hasHeaderLocation && !$hasRedirectHelper) {
        $classification = 'REVIEW';
        $reason = 'POST form found without obvious redirect/background-submission evidence in this file.';
    }

    if ($classification === 'REVIEW' && count($postForms) > 0) {
        foreach ($formActions as $fa) {
            $action = trim((string)$fa['action']);
            if ($action !== '' && preg_match('~submit|save|update|admin|process|action~i', $action)) {
                $classification = 'QUIET-SUBMIT CANDIDATE';
                $reason = 'Interactive POST form found; may benefit from background submission if the page currently reloads.';
                break;
            }
        }
    }

    return [
        'file' => $relative,
        'classification' => $classification,
        'reason' => $reason,
        'signals' => [
            'post_form_count' => count($postForms),
            'post_handler_signal_count' => count($postMethodChecks),
            'has_location_header' => $hasHeaderLocation,
            'has_redirect_helper' => $hasRedirectHelper,
            'has_exit_after_location' => $hasExitAfterRedirect,
            'has_fetch_or_ajax' => $hasFetch,
            'has_possible_direct_render_after_post' => $hasPostResponseRender,
            'has_csrf_signal' => $hasCsrf,
            'has_session_signal' => $hasSession,
        ],
        'forms' => $formActions,
        'evidence' => array_slice($evidence, 0, 25),
    ];
}

$root = __DIR__;
$results = [];
$scanned = 0;
$skipped = 0;
$errors = [];

try {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS
        )
    );

    foreach ($iterator as $info) {
        if (!$info instanceof SplFileInfo || !$info->isFile()) continue;

        $path = $info->getPathname();
        if (!preg_match('/\.php$/i', $path)) continue;

        $relative = apsa_rel($root, $path);

        if (apsa_is_skipped($relative)) {
            $skipped++;
            continue;
        }

        $scanned++;

        try {
            $result = apsa_scan_file($root, $path);
            if (is_array($result)) {
                $results[] = $result;
            }
        } catch (Throwable $e) {
            $errors[] = [
                'file' => $relative,
                'error' => $e->getMessage(),
            ];
        }
    }
} catch (Throwable $e) {
    $errors[] = [
        'file' => '(scanner)',
        'error' => $e->getMessage(),
    ];
}

$order = [
    'PRG CANDIDATE' => 1,
    'QUIET-SUBMIT CANDIDATE' => 2,
    'REVIEW' => 3,
    'INFO' => 4,
    'LIKELY OK' => 5,
];

usort($results, function (array $a, array $b) use ($order): int {
    $oa = $order[$a['classification']] ?? 99;
    $ob = $order[$b['classification']] ?? 99;
    if ($oa !== $ob) return $oa <=> $ob;
    return strcasecmp($a['file'], $b['file']);
});

$counts = [
    'PRG CANDIDATE' => 0,
    'QUIET-SUBMIT CANDIDATE' => 0,
    'REVIEW' => 0,
    'INFO' => 0,
    'LIKELY OK' => 0,
];

foreach ($results as $row) {
    if (isset($counts[$row['classification']])) {
        $counts[$row['classification']]++;
    }
}

$report = [
    'tool' => 'admin_post_submission_audit.php',
    'tool_version' => 'v001',
    'generated_at' => date('Y-m-d H:i:s T'),
    'root' => $root,
    'read_only' => true,
    'summary' => [
        'php_files_scanned' => $scanned,
        'php_files_skipped' => $skipped,
        'post_related_files_found' => count($results),
        'classification_counts' => $counts,
        'scan_errors' => count($errors),
    ],
    'classification_notes' => [
        'PRG CANDIDATE' => 'POST handler appears to render directly without an obvious redirect. Strongest candidate for browser resubmission warnings.',
        'QUIET-SUBMIT CANDIDATE' => 'Interactive POST form may benefit from background submission if a smoother no-reload experience is useful.',
        'REVIEW' => 'POST form/handler needs manual review; evidence is not conclusive.',
        'LIKELY OK' => 'Redirect or AJAX/fetch evidence suggests resubmission risk is already mitigated.',
        'INFO' => 'POST-related code found, but not enough evidence for a stronger classification.',
    ],
    'results' => $results,
    'errors' => $errors,
];

if (isset($_GET['export']) && $_GET['export'] === 'json') {
    $jsonFlags = JSON_PRETTY_PRINT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE;

    $json = json_encode($report, $jsonFlags);

    if (!is_string($json)) {
        http_response_code(500);
        exit('Could not generate JSON report: ' . json_last_error_msg());
    }

    $exportName = 'MRL_POST_submission_audit_' . date('Ymd_His') . '.json';

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $exportName . '"');
    header('Cache-Control: no-store');
    echo $json . PHP_EOL;
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL POST Submission Audit</title>
<style>
:root{
    --bg:#101214;
    --panel:#1d2023;
    --border:#4b5055;
    --text:#f0f0f0;
    --gold:#efc77e;
    --green:#63e69a;
    --red:#ff7e7e;
    --blue:#55c7ff;
    --orange:#ffbd72;
    --gray:#b9bec4;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1500px;margin:20px auto}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}
a{color:#76cfff}
.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}
.stat{background:#151719;border:1px solid #3e4348;border-radius:10px;padding:12px}
.stat strong{display:block;font-size:24px;margin-top:4px}
.toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.btn{display:inline-block;padding:10px 16px;border-radius:8px;font-weight:800;text-decoration:none}
.export{background:#1466c9;color:#fff;border:1px solid #5a7fb5}
.note{background:#151719;border:1px solid #3e4348;border-radius:10px;padding:12px;line-height:1.45}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid #3b4044;text-align:left;vertical-align:top}
th{color:#ffe0a0;position:sticky;top:0;background:#1d2023;z-index:2}
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-weight:800;font-size:12px;white-space:nowrap}
.prg{background:#4a1818;color:#ffd6d6;border:1px solid #a64e4e}
.quiet{background:#4b3512;color:#ffe4ad;border:1px solid #9a6c24}
.review{background:#3d2c12;color:#ffe5b8;border:1px solid #826022}
.ok{background:#123a2a;color:#dcffeb;border:1px solid #2b815b}
.info{background:#173043;color:#ddf4ff;border:1px solid #336d93}
.small{font-size:12px;color:#c9cdd1}
code{color:#7fd2ff;word-break:break-word}
details{margin-top:6px}
summary{cursor:pointer;color:#dcecff}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>MRL POST Submission Audit</h1>
<p><strong>VERSION:</strong> v002 &nbsp; | &nbsp; <strong>Generated:</strong> <?php echo apsa_h(date('n/j/Y g:i:s a')); ?> America/New_York</p>
<div class="note">
<strong>Read-only diagnostic.</strong> This page makes no file or database changes.
It scans active PHP files for POST forms/handlers and flags pages that may deserve review for
browser form-resubmission behavior, POST/Redirect/GET, or quiet background submission.
</div>
</div>

<div class="card">
<div class="summary">
    <div class="stat">PHP files scanned<strong><?php echo (int)$scanned; ?></strong></div>
    <div class="stat">Skipped files<strong><?php echo (int)$skipped; ?></strong></div>
    <div class="stat">POST-related files<strong><?php echo count($results); ?></strong></div>
    <div class="stat">PRG candidates<strong><?php echo (int)$counts['PRG CANDIDATE']; ?></strong></div>
    <div class="stat">Quiet-submit candidates<strong><?php echo (int)$counts['QUIET-SUBMIT CANDIDATE']; ?></strong></div>
    <div class="stat">Likely OK<strong><?php echo (int)$counts['LIKELY OK']; ?></strong></div>
</div>
</div>

<div class="card">
<div class="toolbar">
<a class="btn export" href="?export=json">Export JSON Report</a>
<span class="small">After the scan finishes, download the JSON and upload it back to ChatGPT for classification/recommendations.</span>
</div>
</div>

<div class="card">
<h2>Findings</h2>
<?php if (!$results): ?>
<p>No POST-related active PHP files were found.</p>
<?php else: ?>
<div style="overflow:auto;max-height:70vh">
<table>
<thead>
<tr>
<th>Classification</th>
<th>File</th>
<th>Why</th>
<th>Signals</th>
<th>Details</th>
</tr>
</thead>
<tbody>
<?php foreach ($results as $row): ?>
<?php
    $class = 'info';
    if ($row['classification'] === 'PRG CANDIDATE') $class = 'prg';
    elseif ($row['classification'] === 'QUIET-SUBMIT CANDIDATE') $class = 'quiet';
    elseif ($row['classification'] === 'REVIEW') $class = 'review';
    elseif ($row['classification'] === 'LIKELY OK') $class = 'ok';
?>
<tr>
<td><span class="badge <?php echo $class; ?>"><?php echo apsa_h($row['classification']); ?></span></td>
<td><code><?php echo apsa_h($row['file']); ?></code></td>
<td><?php echo apsa_h($row['reason']); ?></td>
<td class="small">
POST forms: <?php echo (int)$row['signals']['post_form_count']; ?><br>
POST handler signals: <?php echo (int)$row['signals']['post_handler_signal_count']; ?><br>
Redirect: <?php echo ($row['signals']['has_location_header'] || $row['signals']['has_redirect_helper']) ? 'yes' : 'no'; ?><br>
Fetch/AJAX: <?php echo $row['signals']['has_fetch_or_ajax'] ? 'yes' : 'no'; ?><br>
CSRF signal: <?php echo $row['signals']['has_csrf_signal'] ? 'yes' : 'no'; ?>
</td>
<td>
<details>
<summary>Show forms / evidence</summary>
<?php if ($row['forms']): ?>
<p><strong>POST forms</strong></p>
<ul>
<?php foreach ($row['forms'] as $form): ?>
<li>Line <?php echo (int)$form['line']; ?> — action: <code><?php echo apsa_h($form['action'] !== '' ? $form['action'] : '(same/current page)'); ?></code></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($row['evidence']): ?>
<p><strong>Evidence</strong></p>
<ul>
<?php foreach ($row['evidence'] as $ev): ?>
<li>
Line <?php echo (int)$ev['line']; ?> —
<code><?php echo apsa_h($ev['type']); ?></code><br>
<span class="small"><?php echo apsa_h($ev['excerpt']); ?></span>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</details>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

<?php if ($errors): ?>
<div class="card">
<h2>Scan Errors</h2>
<ul>
<?php foreach ($errors as $err): ?>
<li><code><?php echo apsa_h($err['file']); ?></code> — <?php echo apsa_h($err['error']); ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<div class="card">
<h2>How I Would Use the Export</h2>
<p>
Upload the JSON report back to ChatGPT. I can then separate the findings into
<strong>leave alone</strong>, <strong>simple POST/Redirect/GET cleanup</strong>,
<strong>good quiet-submit candidate</strong>, or <strong>needs closer code review</strong>.
Nothing should be converted automatically just because this scanner flags it.
</p>
</div>

</div>
</body>
</html>
