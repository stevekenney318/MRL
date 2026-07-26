<?php
declare(strict_types=1);

/**
 * test_secondary_source_access.php
 *
 * VERSION: v001
 * LAST MODIFIED: 7/26/2026 8:06:53 am
 *
 * DESCRIPTION:
 * Standalone diagnostic page for testing direct PHP access to
 * Racing-Reference and Jayski using browser-like request headers.
 *
 * IMPORTANT:
 * - Does not modify any MRL file.
 * - Does not write scheduler state.
 * - Does not affect scoring, monitoring, snapshots, or cron behavior.
 * - Saves no data unless "Save response HTML" is checked.
 *
 * PHP: 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

const TSA_VERSION = 'v001';

$defaultUrls = [
    'Racing-Reference Race Page' => 'https://www.racing-reference.info/race-results/2026-22/W',
    'Racing-Reference Season Page' => 'https://www.racing-reference.info/season-stats/2026/W',
    'Jayski 2026 Results Index' => 'https://www.jayski.com/nascar-cup-series/2026-nascar-cup-series-race-results/',
];

header('Content-Type: text/html; charset=UTF-8');

function tsa_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function tsa_http_get(string $url, bool $browserHeaders, bool $saveHtml): array
{
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
    ];

    $userAgent = $browserHeaders
        ? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36'
        : 'MRL Secondary Source Access Test/' . TSA_VERSION;

    $started = microtime(true);

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'url' => $url,
            'effective_url' => $url,
            'http_code' => 0,
            'content_type' => '',
            'body' => '',
            'body_bytes' => 0,
            'title' => '',
            'elapsed_ms' => 0,
            'error' => 'PHP cURL extension is not available.',
            'challenge_detected' => false,
            'challenge_reasons' => [],
            'saved_file' => '',
            'headers_mode' => $browserHeaders ? 'browser-like' : 'basic',
        ];
    }

    $cookieFile = sys_get_temp_dir() . '/mrl_secondary_source_test_cookie.txt';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $effectiveUrl = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    $body = is_string($body) ? $body : '';
    $title = '';
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $m)) {
        $title = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    $challengeReasons = [];
    $lower = strtolower($body);

    if ($httpCode === 403) {
        $challengeReasons[] = 'HTTP 403';
    }
    if (stripos($title, 'Just a moment') !== false) {
        $challengeReasons[] = 'Page title contains "Just a moment"';
    }
    if (strpos($lower, 'cf-chl-') !== false || strpos($lower, 'cloudflare') !== false) {
        $challengeReasons[] = 'Cloudflare challenge wording/markup detected';
    }
    if (strpos($lower, 'enable javascript and cookies to continue') !== false) {
        $challengeReasons[] = 'JavaScript/cookie challenge detected';
    }

    $savedFile = '';
    if ($saveHtml && $body !== '') {
        $saveDir = __DIR__ . '/_secondary_source_access_test';
        if (!is_dir($saveDir)) {
            @mkdir($saveDir, 0755, true);
        }

        $host = parse_url($effectiveUrl !== '' ? $effectiveUrl : $url, PHP_URL_HOST);
        $host = preg_replace('/[^a-z0-9._-]+/i', '_', (string)$host);
        $savedFile = $host . '_' . date('Ymd_His') . '_' . ($browserHeaders ? 'browser' : 'basic') . '.html';
        @file_put_contents($saveDir . '/' . $savedFile, $body, LOCK_EX);
    }

    return [
        'ok' => $body !== '' && $httpCode >= 200 && $httpCode < 400 && empty($challengeReasons),
        'url' => $url,
        'effective_url' => $effectiveUrl !== '' ? $effectiveUrl : $url,
        'http_code' => $httpCode,
        'content_type' => $contentType,
        'body' => $body,
        'body_bytes' => strlen($body),
        'title' => $title,
        'elapsed_ms' => (int)round((microtime(true) - $started) * 1000),
        'error' => $body === '' ? $error : '',
        'challenge_detected' => !empty($challengeReasons),
        'challenge_reasons' => $challengeReasons,
        'saved_file' => $savedFile,
        'headers_mode' => $browserHeaders ? 'browser-like' : 'basic',
    ];
}

$results = [];
$didRun = false;
$selected = isset($_POST['sources']) && is_array($_POST['sources'])
    ? array_values(array_intersect(array_keys($defaultUrls), $_POST['sources']))
    : array_keys($defaultUrls);

$saveHtml = !empty($_POST['save_html']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_test'])) {
    $didRun = true;

    foreach ($selected as $label) {
        $url = $defaultUrls[$label];

        $results[] = [
            'label' => $label,
            'result' => tsa_http_get($url, false, $saveHtml),
        ];

        $results[] = [
            'label' => $label,
            'result' => tsa_http_get($url, true, $saveHtml),
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRL Secondary Source Access Test</title>
<style>
:root {
    color-scheme: dark;
    --bg: #101318;
    --panel: #1a2028;
    --panel2: #222a34;
    --text: #edf2f7;
    --muted: #aab4c0;
    --border: #364150;
    --good: #56d364;
    --warn: #e3b341;
    --bad: #f85149;
    --accent: #58a6ff;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font-family: Arial, Helvetica, sans-serif;
}
.wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}
.panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 16px;
}
h1 { margin: 0 0 8px; }
.note {
    background: var(--panel2);
    border-left: 4px solid var(--accent);
    padding: 12px 14px;
    margin: 14px 0;
}
.source-option {
    display: block;
    padding: 8px 0;
}
button {
    background: #1976d2;
    color: white;
    border: 0;
    border-radius: 8px;
    padding: 11px 16px;
    font-size: 16px;
    cursor: pointer;
}
.grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.result {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
}
.good { color: var(--good); }
.warn { color: var(--warn); }
.bad { color: var(--bad); }
.meta {
    margin: 5px 0;
    color: var(--muted);
    word-break: break-word;
}
pre {
    white-space: pre-wrap;
    word-break: break-word;
    background: #0d1117;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 12px;
}
a { color: var(--accent); }
@media (max-width: 800px) {
    .grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="wrap">
<div class="panel">
<h1>MRL Secondary Source Access Test</h1>
<div class="meta">Version <?= tsa_h(TSA_VERSION) ?> · Generated 7/26/2026 8:06:53 am America/New_York</div>

<div class="note">
<strong>Standalone diagnostic only.</strong><br>
This page does not change the race-finish monitor, scheduler, cron, scoring, or saved MRL state.
Each selected URL is tested twice: once with a basic request and once with browser-like headers/cookies.
</div>

<form method="post">
<?php foreach ($defaultUrls as $label => $url): ?>
<label class="source-option">
<input type="checkbox" name="sources[]" value="<?= tsa_h($label) ?>" <?= in_array($label, $selected, true) ? 'checked' : '' ?>>
<strong><?= tsa_h($label) ?></strong><br>
<span class="meta"><?= tsa_h($url) ?></span>
</label>
<?php endforeach; ?>

<label class="source-option">
<input type="checkbox" name="save_html" value="1" <?= $saveHtml ? 'checked' : '' ?>>
Save response HTML under <code>/_secondary_source_access_test/</code>
</label>

<p><button type="submit" name="run_test" value="1">Run Access Test</button></p>
</form>
</div>

<?php if ($didRun): ?>
<div class="grid">
<?php foreach ($results as $entry):
    $r = $entry['result'];
    $statusClass = !empty($r['ok']) ? 'good' : (!empty($r['challenge_detected']) ? 'bad' : 'warn');
?>
<div class="result">
<h2><?= tsa_h((string)$entry['label']) ?></h2>
<p><strong>Mode:</strong> <?= tsa_h((string)$r['headers_mode']) ?></p>
<p class="<?= $statusClass ?>"><strong>
<?= !empty($r['ok']) ? 'REAL PAGE LOADED' : (!empty($r['challenge_detected']) ? 'CHALLENGE/BLOCK DETECTED' : 'REQUEST FAILED') ?>
</strong></p>

<div class="meta">HTTP: <?= tsa_h((string)$r['http_code']) ?></div>
<div class="meta">Content type: <?= tsa_h((string)$r['content_type']) ?></div>
<div class="meta">Bytes: <?= tsa_h((string)$r['body_bytes']) ?></div>
<div class="meta">Elapsed: <?= tsa_h((string)$r['elapsed_ms']) ?> ms</div>
<div class="meta">Title: <?= tsa_h((string)$r['title']) ?></div>
<div class="meta">Effective URL: <?= tsa_h((string)$r['effective_url']) ?></div>

<?php if (!empty($r['challenge_reasons'])): ?>
<h3>Challenge indicators</h3>
<pre><?= tsa_h(implode("\n", $r['challenge_reasons'])) ?></pre>
<?php endif; ?>

<?php if (!empty($r['error'])): ?>
<h3>Request error</h3>
<pre><?= tsa_h((string)$r['error']) ?></pre>
<?php endif; ?>

<?php if (!empty($r['saved_file'])): ?>
<p><a href="_secondary_source_access_test/<?= rawurlencode((string)$r['saved_file']) ?>" target="_blank">Open saved response HTML</a></p>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel">
<p><strong>After testing:</strong> send the result page or screenshots back here. You may then delete this PHP test page. If response HTML was saved, the folder can also be deleted after review.</p>
</div>
</div>
</body>
</html>
