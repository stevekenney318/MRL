<?php
declare(strict_types=1);

/**
 * cron_check_espn_results.php
 *
 * VERSION: v1.5
 * LAST MODIFIED: 2026-02-23
 *
 * Purpose:
 *   - Find latest ESPN race results URL for a year
 *   - Fetch that race results page
 *   - Detect "FINAL" only when scoring is REAL (non-zero in PTS/POINTS/BONUS/PENALTY)
 *   - Email once per race URL, and again only if scoring changes (hash changes)
 *
 * v1.5 changes:
 *   - ESPN's scoring table headers are sometimes <td> (NOT <th>).
 *   - Detector now recognizes header rows using th OR td and maps scoring columns correctly.
 *   - Rows are scanned only after a header row is found.
 *
 * Debug snapshots (from v1.4):
 *   - When NOT FINAL, saves:
 *       _cron_debug_racepage_<raceId>_<timestamp>.html.gz
 *       _cron_debug_summary_<raceId>_<timestamp>.txt
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_cron_espn_results_php_errors.log');
error_reporting(E_ALL);

const SCRIPT_SIGNATURE = 'CRON_CHECK_ESPN_RESULTS v1.5';

// ------------------------- DOCUMENT ROOT ----------------------------
$docRoot = '';
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');
} else {
    $maybe = realpath(__DIR__ . '/..'); // .../public_html
    $docRoot = $maybe ? rtrim($maybe, '/') : '';
}
if ($docRoot === '' || !is_dir($docRoot)) {
    throw new RuntimeException("Could not determine DOCUMENT_ROOT. Derived docRoot='{$docRoot}'");
}

// CLI/cron safety (some configs expect HTTP_HOST)
if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

// ------------------------- INCLUDES -------------------------
require_once $docRoot . '/config.php';
require_once $docRoot . '/config_mrl.php';
require_once $docRoot . '/class.user.php';

$user_home = new USER();

// ------------------------- SETTINGS -------------------------
$year = 2026; // or: (int)date('Y')
$notifyEmail = 'stevekenney318@gmail.com';

// Keep subject EXACT for your Gmail filter:
$subjectFinal = '[MRL] ESPN Results - FINAL Results Detected';

// Files written in this folder
$stateFile     = __DIR__ . '/_cron_espn_results_state.json';
$logFile       = __DIR__ . '/_cron_espn_results_watch.log';
$heartbeatFile = __DIR__ . '/_cron_espn_results_heartbeat.txt';

// Fetch timeout
$timeoutSeconds = 25;

// Debug snapshot settings
$debugSnapshotsEnabled = true;     // set false to disable snapshot files
$debugSnapshotMaxBytes = 3000000; // cap stored HTML (after fetch) to prevent huge files
$debugUseGzip          = true;     // write .html.gz

// Optional CLI override: php cron_check_espn_results.php 2026
if (PHP_SAPI === 'cli' && isset($argv) && is_array($argv) && count($argv) >= 2) {
    $cliYear = (int)$argv[1];
    if ($cliYear >= 2000 && $cliYear <= 2100) {
        $year = $cliYear;
    }
}

// ------------------------- HELPERS -------------------------
function nowLocalString(): string {
    return date('Y-m-d H:i:s');
}

function nowStamp(): string {
    return date('Ymd_His');
}

function logLine(string $logFile, string $msg): void {
    $ts = nowLocalString();
    @file_put_contents($logFile, "[$ts] $msg\n", FILE_APPEND);
}

function atomicWrite(string $path, string $contents): bool {
    $dir = dirname($path);
    $tmp = $dir . '/.' . basename($path) . '.tmp.' . bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, $contents);
    if ($ok === false) return false;
    return @rename($tmp, $path);
}

function loadState(string $stateFile): array {
    if (!is_file($stateFile)) return [];
    $raw = @file_get_contents($stateFile);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function saveState(string $stateFile, array $state): void {
    $json = json_encode($state, JSON_PRETTY_PRINT);
    if ($json === false) return;
    atomicWrite($stateFile, $json);
}

function sha256_file_string(string $path): string {
    $raw = @file_get_contents($path);
    if ($raw === false) return '';
    return hash('sha256', $raw);
}

function writeHeartbeat(string $heartbeatFile, string $msg): void {
    atomicWrite($heartbeatFile, $msg . "\n");
}

function normHeader(string $s): string {
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    return strtoupper($s);
}

function parseIntCell(string $s): ?int {
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = preg_replace('/[^0-9\-]/', '', $s);
    if ($s === '' || $s === '-') return null;
    if (!preg_match('/^-?\d+$/', $s)) return null;
    return (int)$s;
}

function extractRaceIdFromUrl(string $url): string {
    if (preg_match('~/raceId/(\d+)~', $url, $m)) return $m[1];
    return 'unknown';
}
function fetchUrl(string $url, int $timeoutSeconds): array {
    // Returns: [ok(bool), httpStatus(int), body(string), error(string)]
    $ch = curl_init();
    if ($ch === false) return [false, 0, '', 'cURL init failed'];

    // Small cache-buster
    $sep = (strpos($url, '?') === false) ? '?' : '&';
    $urlWithBust = $url . $sep . '_=' . rawurlencode((string)microtime(true));

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

    curl_setopt_array($ch, [
        CURLOPT_URL => $urlWithBust,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 6,
        CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_ENCODING => '',
    ]);

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false || $body === null) return [false, $code, '', $err ?: 'Unknown fetch error'];
    if ($code >= 400 || $code === 0) return [false, $code, (string)$body, $err ?: ("HTTP error " . $code)];
    return [true, $code, (string)$body, ''];
}

function findLatestRaceResultsUrl(int $year, int $timeoutSeconds): array {
    // Returns: [ok(bool), latestUrl(string), error(string), debug(array)]
    $debug = [
        'year' => $year,
        'yearPage' => '',
        'httpStatus' => null,
        'htmlBytes' => 0,
        'matchCount' => 0,
        'lastHref' => '',
        'latestUrl' => '',
    ];

    $yearPage = "https://www.espn.com/racing/results/_/year/" . $year;
    $debug['yearPage'] = $yearPage;

    [$ok, $status, $html, $err] = fetchUrl($yearPage, $timeoutSeconds);
    $debug['httpStatus'] = $status;
    $debug['htmlBytes'] = strlen($html);

    if (!$ok) return [false, '', "Failed to fetch ESPN year page (HTTP $status): " . $err, $debug];

    preg_match_all('~href=[\'"](?P<href>/racing/raceresults[^\'"]*?/raceId/\d+[^\'"]*)[\'"]~i', $html, $m);

    $hrefs = [];
    if (!empty($m['href'])) {
        $hrefs = $m['href'];
    } else {
        preg_match_all('~(/racing/raceresults[^"\s>]*?/raceId/\d+)~i', $html, $m2);
        if (!empty($m2[1])) $hrefs = $m2[1];
    }

    $debug['matchCount'] = count($hrefs);
    if (count($hrefs) === 0) return [false, '', 'No race results links found on ESPN year page.', $debug];

    $lastHref = end($hrefs);
    if (!is_string($lastHref) || $lastHref === '') return [false, '', 'Could not read last race link.', $debug];

    $debug['lastHref'] = $lastHref;

    if (preg_match('~/series/([a-z0-9_-]+)/raceId/(\d+)~i', $lastHref, $mm)) {
        $series = $mm[1];
        $raceId = $mm[2];
        $latestUrl = "https://www.espn.com/racing/raceresults/_/series/{$series}/raceId/{$raceId}";
    } else {
        $latestUrl = "https://www.espn.com" . $lastHref;
    }

    $debug['latestUrl'] = $latestUrl;
    return [true, $latestUrl, '', $debug];
}

/**
 * FINAL detector: finds scoring table AND requires at least one non-zero value in
 * PTS/POINTS or BONUS or PENALTY.
 *
 * IMPORTANT v1.5 fix:
 *   ESPN header row sometimes uses <td>, not <th>.
 *
 * Returns: [isFinal(bool), reason(string), details(array)]
 */
function detectFinalScoringNonZero(string $html): array {
    $details = [
        'mode' => 'dom_table',
        'tableHash' => '',
        'rowsChecked' => 0,
        'nonZeroCounts' => ['PTS'=>0,'BONUS'=>0,'PENALTY'=>0],
        'colIndex' => ['PTS'=>null,'BONUS'=>null,'PENALTY'=>null],
        'tablesFound' => 0,
        'headerRowFound' => false,
        'headerText' => '',
    ];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();

    if (!$loaded) {
        return [false, 'DOM load failed.', $details];
    }

    $xp = new DOMXPath($dom);
    $tables = $xp->query('//table');
    $details['tablesFound'] = $tables ? $tables->length : 0;

    if (!$tables || $tables->length === 0) {
        return [false, 'No <table> elements found on page (cron fetch may be JS-dependent).', $details];
    }

    $bestTable = null;
    $bestHeaders = [];
    $bestHeaderRow = null;

    // Find a header row in ANY table where the row contains PTS/POINTS and BONUS/PENALTY
    for ($t = 0; $t < $tables->length; $t++) {
        $tbl = $tables->item($t);

        // Walk rows until we find a row that looks like the header
        $rows = $xp->query('.//tr', $tbl);
        if (!$rows || $rows->length === 0) continue;

        for ($r = 0; $r < $rows->length; $r++) {
            $row = $rows->item($r);

            // Header cells might be th OR td
            $cells = $xp->query('./th|./td', $row);
            if (!$cells || $cells->length < 5) continue;

            $headers = [];
            for ($i = 0; $i < $cells->length; $i++) {
                $headers[] = normHeader((string)$cells->item($i)->textContent);
            }

            $hasPts = false;
            $hasBonus = false;
            $hasPenalty = false;

            foreach ($headers as $h) {
                if (strpos($h, 'PTS') !== false || strpos($h, 'POINT') !== false) $hasPts = true;
                if (strpos($h, 'BONUS') !== false) $hasBonus = true;
                if (strpos($h, 'PENALTY') !== false) $hasPenalty = true;
            }

            // Require PTS/POINTS plus at least one of BONUS/PENALTY
            if ($hasPts && ($hasBonus || $hasPenalty)) {
                $bestTable = $tbl;
                $bestHeaderRow = $row;
                $bestHeaders = $headers;
                break 2;
            }
        }
    }

    if (!$bestTable || !$bestHeaderRow) {
        return [false, 'Could not locate a scoring-style table (PTS/POINTS with BONUS/PENALTY).', $details];
    }

    $details['headerRowFound'] = true;
    $details['headerText'] = implode(' | ', $bestHeaders);

    $tableHtml = $dom->saveHTML($bestTable) ?: '';
    if ($tableHtml !== '') $details['tableHash'] = hash('sha256', $tableHtml);

    // Map scoring column indexes based on header cell position
    $idxPTS = null; $idxBON = null; $idxPEN = null;
    for ($i = 0; $i < count($bestHeaders); $i++) {
        $h = $bestHeaders[$i];
        if ($idxPTS === null && (strpos($h, 'PTS') !== false || strpos($h, 'POINT') !== false)) $idxPTS = $i;
        if ($idxBON === null && strpos($h, 'BONUS') !== false) $idxBON = $i;
        if ($idxPEN === null && strpos($h, 'PENALTY') !== false) $idxPEN = $i;
    }
    $details['colIndex'] = ['PTS'=>$idxPTS,'BONUS'=>$idxBON,'PENALTY'=>$idxPEN];

    if ($idxPTS === null && $idxBON === null && $idxPEN === null) {
        return [false, 'Scoring table found, but could not map scoring columns.', $details];
    }

    // Data rows: any tr with td cells AFTER the header row
    $rows = $xp->query('.//tr[td]', $bestTable);
    if (!$rows || $rows->length === 0) {
        return [false, 'Scoring table found, but no data rows found.', $details];
    }

    $headerSeen = false;

    for ($r = 0; $r < $rows->length; $r++) {
        $row = $rows->item($r);

        // Determine if this is the header row itself (same node)
        if ($row->isSameNode($bestHeaderRow)) {
            $headerSeen = true;
            continue;
        }
        if (!$headerSeen) {
            // Skip any rows before the detected header row
            continue;
        }

        $tds = $xp->query('./td', $row);
        if (!$tds || $tds->length === 0) continue;

        // Optional safety: require first cell to start with a number (POS)
        $firstCell = trim((string)$tds->item(0)->textContent);
        if ($firstCell === '' || !preg_match('/^\d+$/', preg_replace('/\D+/', '', $firstCell))) {
            continue;
        }

        $details['rowsChecked']++;

        $readAt = function(?int $idx) use ($tds): ?int {
            if ($idx === null) return null;
            if ($idx < 0 || $idx >= $tds->length) return null;
            return parseIntCell((string)$tds->item($idx)->textContent);
        };

        if ($idxPTS !== null) { $v = $readAt($idxPTS); if ($v !== null && $v !== 0) $details['nonZeroCounts']['PTS']++; }
        if ($idxBON !== null) { $v = $readAt($idxBON); if ($v !== null && $v !== 0) $details['nonZeroCounts']['BONUS']++; }
        if ($idxPEN !== null) { $v = $readAt($idxPEN); if ($v !== null && $v !== 0) $details['nonZeroCounts']['PENALTY']++; }

        if ($details['nonZeroCounts']['PTS'] > 0 || $details['nonZeroCounts']['BONUS'] > 0 || $details['nonZeroCounts']['PENALTY'] > 0) {
            return [true, 'Non-zero scoring detected in scoring table.', $details];
        }

        if ($details['rowsChecked'] >= 250) break;
    }

    return [false, 'Scoring table found, but all scoring values are still zero.', $details];
}
function sendEmail(USER $user_home, string $toEmail, string $subject, string $message, string $logFile): bool {
    $ok = false;
    try {
        $ok = (bool)$user_home->send_mail($toEmail, $message, $subject);
    } catch (Throwable $e) {
        logLine($logFile, 'EMAIL EXCEPTION: ' . $e->getMessage());
        $ok = false;
    }
    return $ok;
}

function debugSummaryText(string $html): string {
    $hasNext = (preg_match('~id=["\']__NEXT_DATA__["\']~i', $html) === 1);

    $needles = ['PTS','POINTS','BONUS','PENALTY'];
    $found = [];
    foreach ($needles as $n) {
        $found[$n] = (stripos($html, $n) !== false) ? 'YES' : 'NO';
    }

    preg_match_all('~<table\b~i', $html, $mTbl);
    $tables = is_array($mTbl) ? count($mTbl[0]) : 0;

    $len = strlen($html);
    $sha = hash('sha256', $html);

    $out = [];
    $out[] = "CRON DEBUG SUMMARY (spoiler-safe)";
    $out[] = "Generated: " . nowLocalString();
    $out[] = "HTML bytes: {$len}";
    $out[] = "HTML sha256: {$sha}";
    $out[] = "__NEXT_DATA__ present: " . ($hasNext ? 'YES' : 'NO');
    $out[] = "<table> tags found: {$tables}";
    $out[] = "Raw token presence:";
    foreach ($found as $k => $v) {
        $out[] = "  {$k}: {$v}";
    }
    $out[] = "";
    return implode("\n", $out);
}

function saveDebugSnapshot(
    string $folder,
    string $raceUrl,
    string $html,
    int $maxBytes,
    bool $useGzip
): array {
    // Returns: [saved(bool), htmlPath(string), summaryPath(string)]
    $raceId = extractRaceIdFromUrl($raceUrl);
    $stamp = nowStamp();

    if (strlen($html) > $maxBytes) {
        $html = substr($html, 0, $maxBytes);
    }

    $summary = debugSummaryText($html);

    $summaryPath = rtrim($folder, '/') . "/_cron_debug_summary_{$raceId}_{$stamp}.txt";

    $htmlPath = rtrim($folder, '/') . "/_cron_debug_racepage_{$raceId}_{$stamp}.html";
    if ($useGzip) {
        $htmlPath .= ".gz";
        $gz = gzencode($html, 6);
        if ($gz === false) {
            // fall back to plain
            $htmlPath = rtrim($folder, '/') . "/_cron_debug_racepage_{$raceId}_{$stamp}.html";
            $okHtml = atomicWrite($htmlPath, $html);
        } else {
            $okHtml = atomicWrite($htmlPath, $gz);
        }
    } else {
        $okHtml = atomicWrite($htmlPath, $html);
    }

    $okSum = atomicWrite($summaryPath, $summary . "\n");
    return [($okHtml && $okSum), $htmlPath, $summaryPath];
}

// ------------------------- MAIN -------------------------
$scriptSha = sha256_file_string(__FILE__);
$token = bin2hex(random_bytes(8));

$hb = nowLocalString()
    . "  token={$token}"
    . "  sig=" . SCRIPT_SIGNATURE
    . "  year={$year}"
    . "  sapi=" . PHP_SAPI
    . "  sha={$scriptSha}";

writeHeartbeat($heartbeatFile, $hb);

logLine($logFile, SCRIPT_SIGNATURE . " RUN year={$year} sapi=" . PHP_SAPI . " sha={$scriptSha} token={$token}");

// Load state
$state = loadState($stateFile);
if (!isset($state['byYear']) || !is_array($state['byYear'])) $state['byYear'] = [];

$yKey = (string)$year;
if (!isset($state['byYear'][$yKey]) || !is_array($state['byYear'][$yKey])) {
    $state['byYear'][$yKey] = [
        'latest_url' => '',
        'last_checked_at' => '',
        'latest_debug' => [],
        'final_sent_for_url' => '',
        'final_table_hash' => '',
        'final_check' => [],
    ];
}
$yearState = $state['byYear'][$yKey];

// 1) Find latest URL
[$ok, $latestUrl, $err, $debug] = findLatestRaceResultsUrl($year, $timeoutSeconds);

$yearState['last_checked_at'] = date('c');
$yearState['latest_debug'] = $debug;

if (!$ok) {
    $state['byYear'][$yKey] = $yearState;
    saveState($stateFile, $state);
    logLine($logFile, "ERROR latestUrl: {$err}");
    exit(0);
}

$prevLatestUrl = (string)($yearState['latest_url'] ?? '');

// If latest URL changed, reset FINAL tracking
if ($prevLatestUrl === '' || $prevLatestUrl !== $latestUrl) {
    $yearState['latest_url'] = $latestUrl;
    $yearState['final_sent_for_url'] = '';
    $yearState['final_table_hash'] = '';

    $state['byYear'][$yKey] = $yearState;
    saveState($stateFile, $state);

    if ($prevLatestUrl === '') {
        logLine($logFile, "INIT latest_url -> {$latestUrl} (waiting for non-zero scoring)");
    } else {
        logLine($logFile, "LATEST URL CHANGED -> {$latestUrl} (prev {$prevLatestUrl})  Waiting for non-zero scoring before emailing.");
    }
}

// 2) Fetch race page
[$ok2, $status2, $html2, $err2] = fetchUrl($latestUrl, $timeoutSeconds);
if (!$ok2) {
    $state['byYear'][$yKey] = $yearState;
    saveState($stateFile, $state);
    logLine($logFile, "ERROR fetching race page (HTTP {$status2}): {$err2} url={$latestUrl}");
    exit(0);
}

// 3) Detect FINAL based on non-zero scoring
[$isFinal, $reason, $details] = detectFinalScoringNonZero($html2);

// store debug in state (spoiler-safe)
$yearState['final_check'] = [
    'is_final' => $isFinal,
    'reason' => $reason,
    'checked_at' => date('c'),
    'mode' => (string)($details['mode'] ?? ''),
    'hash' => (string)($details['tableHash'] ?? ''),
    'rows_checked' => (int)($details['rowsChecked'] ?? 0),
    'non_zero_counts' => $details['nonZeroCounts'] ?? [],
    'col_index' => $details['colIndex'] ?? [],
    'tables_found' => (int)($details['tablesFound'] ?? 0),
    'header_row_found' => (bool)($details['headerRowFound'] ?? false),
];

$state['byYear'][$yKey] = $yearState;
saveState($stateFile, $state);

// 4) Not final -> save debug snapshot
if (!$isFinal) {
    logLine(
        $logFile,
        "NOT FINAL (no email) url={$latestUrl} mode=" . ($details['mode'] ?? 'unknown') .
        " tables=" . (string)($details['tablesFound'] ?? '?') .
        " rows=" . (string)($details['rowsChecked'] ?? '?') .
        " header=" . ((string)($details['headerRowFound'] ?? false) === '1' ? 'YES' : 'NO') .
        " reason={$reason}"
    );

    if ($debugSnapshotsEnabled) {
        [$saved, $htmlPath, $summaryPath] = saveDebugSnapshot(__DIR__, $latestUrl, $html2, $debugSnapshotMaxBytes, $debugUseGzip);
        if ($saved) {
            logLine($logFile, "DEBUG SNAPSHOT SAVED html=" . basename($htmlPath) . " summary=" . basename($summaryPath));
        } else {
            logLine($logFile, "DEBUG SNAPSHOT FAILED (could not write debug files)");
        }
    }

    exit(0);
}

// 5) Final - email gating (first time or hash change)
$finalSentForUrl = (string)($yearState['final_sent_for_url'] ?? '');
$finalHashPrev   = (string)($yearState['final_table_hash'] ?? '');
$finalHashNow    = (string)($yearState['final_check']['hash'] ?? '');

$shouldEmail = false;
$emailReason = '';

if ($finalSentForUrl !== $latestUrl) {
    $shouldEmail = true;
    $emailReason = 'First non-zero scoring detection for this race URL.';
} elseif ($finalHashNow !== '' && $finalHashPrev !== '' && $finalHashNow !== $finalHashPrev) {
    $shouldEmail = true;
    $emailReason = 'Scoring/results changed (hash change).';
}

if (!$shouldEmail) {
    logLine($logFile, "FINAL detected but no email needed (already notified) url={$latestUrl}");
    exit(0);
}

// Update state BEFORE sending email
$yearState['final_sent_for_url'] = $latestUrl;
$yearState['final_table_hash'] = $finalHashNow;

$state['byYear'][$yKey] = $yearState;
saveState($stateFile, $state);

// Email (no spoilers)
$subject = $subjectFinal;
$message =
    "FINAL scoring appears to be posted on ESPN (non-zero scoring detected).\n\n" .
    "Year: {$year}\n" .
    "URL : {$latestUrl}\n\n" .
    "Reason: {$reason}\n" .
    "Detector mode: " . ($details['mode'] ?? 'unknown') . "\n\n" .
    "Note: This email will only repeat if ESPN changes the results again.\n";

$sentOk = sendEmail($user_home, $notifyEmail, $subject, $message, $logFile);

logLine(
    $logFile,
    $sentOk
        ? "EMAIL SENT (FINAL) to={$notifyEmail} url={$latestUrl} ({$emailReason})"
        : "EMAIL FAILED (FINAL) to={$notifyEmail} url={$latestUrl} ({$emailReason})"
);

exit(0);