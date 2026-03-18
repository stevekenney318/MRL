<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions_mrl.php';

// disableCaching() defined in functions_mrl.php
disableCaching();

/**
 * scrape_nascar.php
 *
 * Open in a browser via your local web server.
 * Paste the ESPN race-results URL, click Capture, done.
 * Output: snapshot_[raceId].html in the same directory.
 *
 * Requirements: PHP 7.4+, php-curl, php-dom extensions
 */

define('ESPN_BASE', 'https://www.espn.com/racing/raceresults/_/series/sprint/raceId/');

$error      = '';
$success    = '';
$outputName = '';
$inputUrl   = '';

// ── Only process when the form is submitted ───────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inputUrl = trim($_POST['espn_url'] ?? '');

    if ($inputUrl === '') {
        $error = 'Please paste a URL before clicking Capture.';

    } elseif (strpos($inputUrl, ESPN_BASE) !== 0) {
        $error  = 'Invalid URL. It must start with:<br>';
        $error .= '<code>' . htmlspecialchars(ESPN_BASE) . '&lt;raceId&gt;</code>';

    } else {
        $raceId = trim(substr($inputUrl, strlen(ESPN_BASE)), '/');

        if (!preg_match('/^\d+$/', $raceId)) {
            $error = 'The race ID at the end of the URL must be numeric. Got: <code>' . htmlspecialchars($raceId) . '</code>';
        } else {
            $sourceUrl  = ESPN_BASE . $raceId;
            $outputFile = __DIR__ . "/snapshot_{$raceId}.html";
            $outputName = "snapshot_{$raceId}.html";

            try {
                // ── Fetch ──────────────────────────────────────────────────
                $ch = curl_init($sourceUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS      => 5,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_ENCODING       => '',
                    CURLOPT_HTTPHEADER     => [
                        'Accept-Language: en-US,en;q=0.9',
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    ],
                    CURLOPT_USERAGENT =>
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
                        'AppleWebKit/537.36 (KHTML, like Gecko) ' .
                        'Chrome/124.0.0.0 Safari/537.36',
                ]);
                $html     = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr  = curl_error($ch);
                curl_close($ch);

                if ($curlErr)          throw new RuntimeException("cURL error: $curlErr");
                if ($httpCode !== 200) throw new RuntimeException("ESPN returned HTTP $httpCode for that URL.");
                if (empty($html))      throw new RuntimeException("ESPN returned an empty response.");

                // ── Extract table ──────────────────────────────────────────
                libxml_use_internal_errors(true);
                $doc = new DOMDocument();
                $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
                libxml_clear_errors();

                $xpath     = new DOMXPath($doc);
                $tableHtml = null;

                foreach ($xpath->query('//table') as $table) {
                    foreach ($xpath->query('.//th | .//td', $table) as $cell) {
                        if (stripos(trim($cell->textContent), 'DRIVER') !== false) {
                            $tableHtml = $doc->saveHTML($table);
                            break 2;
                        }
                    }
                }

                if ($tableHtml === null) {
                    throw new RuntimeException("Could not find the Race Results table. ESPN may have changed their layout.");
                }

                // ── Strip driver links ─────────────────────────────────────
                $tableHtml = preg_replace('/<a\b[^>]*>(.*?)<\/a>/is', '$1', $tableHtml);

                // ── Extract race title from the page <h1> ──────────────────
                $raceTitle = "Race ID {$raceId}";  // fallback if not found
                foreach ($xpath->query('//h1') as $h1) {
                    $txt = trim($h1->textContent);
                    if ($txt !== '') { $raceTitle = $txt; break; }
                }
                $raceTitleEsc = htmlspecialchars($raceTitle);

                // ── Timestamp ──────────────────────────────────────────────
                $tz         = new DateTimeZone('America/New_York');
                $capturedAt = (new DateTime('now', $tz))->format('D, M j, Y \a\t g:i:s A T');
                $sourceEsc  = htmlspecialchars($sourceUrl);

                // ── Build snapshot page ────────────────────────────────────
                $page = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$raceTitleEsc} (Snapshot)</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: #f0f0f0; color: #111; padding: 24px 16px 48px; }
    .wrapper { max-width: 1020px; margin: 0 auto; background: #fff; border-radius: 4px; box-shadow: 0 1px 6px rgba(0,0,0,.18); overflow: hidden; }
    .page-header { background: #1a1a1a; color: #fff; padding: 14px 20px 10px; }
    .page-header h1 { font-size: 1.2rem; font-weight: 700; margin-bottom: 4px; }
    .page-header .meta { font-size: .76rem; opacity: .85; }
    .page-header .meta a { color: #ffe; text-decoration: underline; }
    .snapshot-bar { background: #222; color: #ddd; font-size: .75rem; padding: 6px 20px; display: flex; align-items: center; gap: 8px; }
    .snapshot-bar .badge { background: #f7a700; color: #111; font-weight: 700; font-size: .68rem; padding: 2px 7px; border-radius: 2px; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .table-wrap { overflow-x: auto; }
    .table-wrap table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .table-wrap table tr:first-child td, .table-wrap table tr:first-child th,
    .table-wrap table thead tr:first-child td, .table-wrap table thead tr:first-child th
      { background: #5a3a00 !important; color: #fff !important; padding: 9px 10px; font-weight: 700; font-size: .78rem; letter-spacing: .06em; text-transform: uppercase; border: none; }
    .table-wrap table tr:nth-child(2) td, .table-wrap table tr:nth-child(2) th,
    .table-wrap table thead tr:nth-child(2) td, .table-wrap table thead tr:nth-child(2) th
      { background: #3b2600 !important; color: #ccc !important; padding: 6px 10px; font-size: .76rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; border-bottom: 2px solid #5a3a00; }
    .table-wrap table tbody tr { border-bottom: 1px solid #e8e8e8; }
    .table-wrap table tbody tr:nth-child(even) { background: #f7f7f7; }
    .table-wrap table tbody tr:hover { background: #fff3cd; }
    .table-wrap table tbody td { padding: 7px 10px; vertical-align: middle; }
    .page-footer { padding: 10px 20px; font-size: .72rem; color: #888; border-top: 1px solid #e0e0e0; background: #fafafa; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="page-header">
    <h1>{$raceTitleEsc}</h1>
    <p class="meta">Source: <a href="{$sourceEsc}" target="_blank" rel="noopener">{$sourceEsc}</a></p>
  </div>
  <div class="snapshot-bar">
    <span class="badge">&#128247; Snapshot</span>
    <span>Captured on {$capturedAt}</span>
  </div>
  <div class="table-wrap">{$tableHtml}</div>
  <div class="page-footer">Static snapshot captured at the time shown above. Data reflects the source page at that moment only.</div>
</div>
</body>
</html>
HTML;
                file_put_contents($outputFile, $page);
                $success = "Snapshot saved as <strong>{$outputName}</strong>";

            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// ── Render the form page ──────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NASCAR Snapshot Tool</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: #f0f0f0; padding: 40px 16px; }
    .card { max-width: 680px; margin: 0 auto; background: #fff; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,.15); overflow: hidden; }
    .card-header { background: #1a1a1a; color: #fff; padding: 16px 24px; }
    .card-header h1 { font-size: 1.1rem; font-weight: 700; }
    .card-header p { font-size: .78rem; opacity: .85; margin-top: 3px; }
    .card-body { padding: 24px; }
    label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 6px; color: #333; }
    .hint { font-size: .75rem; color: #777; margin-bottom: 10px; }
    .hint code { background: #f4f4f4; padding: 1px 4px; border-radius: 3px; font-size: .73rem; }
    input[type="text"] {
      width: 100%; padding: 9px 12px; font-size: .88rem;
      border: 1px solid #ccc; border-radius: 4px; outline: none;
    }
    input[type="text"]:focus { border-color: #3b2600; box-shadow: 0 0 0 2px rgba(59,38,0,.15); }
    button {
      margin-top: 14px; padding: 9px 28px;
      background: #3b2600; color: #fff; font-size: .9rem; font-weight: 700;
      border: none; border-radius: 4px; cursor: pointer;
    }
    button:hover { background: #5a3a00; }
    .msg { margin-top: 16px; padding: 10px 14px; border-radius: 4px; font-size: .85rem; }
    .msg.error   { background: #fff0f0; border: 1px solid #f5c6c6; color: #900; }
    .msg.success { background: #f0fff4; border: 1px solid #b2dfbd; color: #1a5c2a; }
  </style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <h1>&#127937; NASCAR ESPN Race Results &ndash; Snapshot Tool</h1>
    <p>Paste the ESPN race-results URL, click Capture, and the snapshot HTML will be saved to this folder.</p>
  </div>
  <div class="card-body">
    <form method="post" action="">
      <label for="espn_url">ESPN Race Results URL</label>
      <p class="hint">Must start with: <code><?= htmlspecialchars(ESPN_BASE) ?>&lt;raceId&gt;</code></p>
      <input type="text"
             id="espn_url"
             name="espn_url"
             value="<?= htmlspecialchars($inputUrl) ?>"
             placeholder="<?= htmlspecialchars(ESPN_BASE) ?>202603150028"
             autofocus>
      <button type="submit">Capture Snapshot</button>
    </form>

    <?php if ($error): ?>
      <div class="msg error">&#10060; <?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="msg success">&#10004; <?= $success ?></div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
