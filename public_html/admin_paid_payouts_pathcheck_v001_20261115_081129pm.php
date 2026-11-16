<?php
/**
 * admin_paid_payouts_pathcheck.php
 * VERSION: v001
 * LAST MODIFIED: 11/15/2026 8:11:29 pm
 *
 * Purpose:
 * - READ-ONLY diagnostic for the first admin_paid_payouts.php installation.
 * - Inspect the exact installed v001 page/helper for location-dependent paths.
 * - Report matching include/require/path lines without exposing config-file contents.
 * - Look for recent matching PHP fatal/runtime errors in the two normal MRL error logs.
 *
 * Safety:
 * - Does not modify, move, create, delete, include, or execute production files.
 * - Does not change database data or schema.
 * - Does not invoke scoring, scheduler, mail, or external actions.
 */

declare(strict_types=1);

date_default_timezone_set('America/New_York');

$publicRoot = dirname(__FILE__);
$raceDir = $publicRoot . '/race_results';
$sourcePage = $raceDir . '/admin_paid_payouts.php';
$sourceHelper = $raceDir . '/admin_paid_payouts_helper.php';

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fileInfoSafe(string $path): array {
    return [
        'path' => $path,
        'exists' => is_file($path),
        'readable' => is_readable($path),
        'size' => is_file($path) ? filesize($path) : null,
        'sha256' => is_file($path) ? hash_file('sha256', $path) : null,
    ];
}

function scanSource(string $path): array {
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    $patterns = [
        '/\brequire(?:_once)?\b/i',
        '/\binclude(?:_once)?\b/i',
        '/__DIR__/',
        '/dirname\s*\(/i',
        '/DOCUMENT_ROOT/i',
        '/config\.php/i',
        '/config_mrl\.php/i',
        '/class\.user\.php/i',
        '/payouts_helper\.php/i',
        '/payouts\.php/i',
        '/admin_paid_payouts_helper\.php/i',
        '/href\s*=\s*[\'\"][^\'\"]*payout/i',
        '/action\s*=\s*[\'\"][^\'\"]*/i',
    ];

    $out = [];
    foreach ($lines as $idx => $line) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                $trimmed = trim($line);
                // Avoid dumping giant inline blocks.
                if (strlen($trimmed) > 500) {
                    $trimmed = substr($trimmed, 0, 500) . ' …';
                }
                $out[] = [
                    'line' => $idx + 1,
                    'text' => $trimmed,
                ];
                break;
            }
        }
    }
    return $out;
}

function matchingErrors(string $path, array $needles): array {
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $size = filesize($path);
    if ($size === false) {
        return [];
    }

    $readSize = min($size, 250000);
    $fp = fopen($path, 'rb');
    if (!$fp) {
        return [];
    }

    if ($size > $readSize) {
        fseek($fp, -$readSize, SEEK_END);
    }
    $text = stream_get_contents($fp);
    fclose($fp);
    if ($text === false) {
        return [];
    }

    $lines = preg_split('/\R/', $text) ?: [];
    $matches = [];
    foreach ($lines as $line) {
        $hit = false;
        foreach ($needles as $needle) {
            if (stripos($line, $needle) !== false) {
                $hit = true;
                break;
            }
        }
        if ($hit) {
            $line = trim($line);
            if (strlen($line) > 1000) {
                $line = substr($line, 0, 1000) . ' …';
            }
            $matches[] = $line;
        }
    }
    return array_slice($matches, -40);
}

$dependencies = [
    'root/config.php' => $publicRoot . '/config.php',
    'root/config_mrl.php' => $publicRoot . '/config_mrl.php',
    'root/class.user.php' => $publicRoot . '/class.user.php',
    'race_results/payouts_helper.php' => $raceDir . '/payouts_helper.php',
    'race_results/payouts.php' => $raceDir . '/payouts.php',
];

$dependencyStatus = [];
foreach ($dependencies as $label => $path) {
    $dependencyStatus[$label] = fileInfoSafe($path);
}

$errorLogCandidates = [
    $publicRoot . '/error_log',
    $raceDir . '/error_log',
];
$errorMatches = [];
foreach ($errorLogCandidates as $logPath) {
    $errorMatches[$logPath] = matchingErrors($logPath, [
        'admin_paid_payouts.php',
        'admin_paid_payouts_helper.php',
        'Fatal error',
        'Uncaught',
    ]);
}

$report = [
    'diagnostic' => 'admin_paid_payouts_pathcheck',
    'version' => 'v001',
    'report_stage' => 'READ_ONLY_PATHCHECK',
    'generated_at_et' => date('c'),
    'safety' => [
        'read_only' => true,
        'production_files_modified' => false,
        'database_modified' => false,
        'production_files_executed' => false,
    ],
    'installed_v001' => [
        'page' => fileInfoSafe($sourcePage),
        'helper' => fileInfoSafe($sourceHelper),
    ],
    'source_path_lines' => [
        'page' => scanSource($sourcePage),
        'helper' => scanSource($sourceHelper),
    ],
    'dependency_status' => $dependencyStatus,
    'matching_error_log_lines' => $errorMatches,
];

if (isset($_GET['export']) && $_GET['export'] === 'json') {
    $stamp = date('Ymd_His');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="admin_paid_payouts_pathcheck_v001_' . $stamp . '.json"');
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRL Admin Paid/Payouts PATHCHECK v001</title>
<style>
body{margin:0;background:#0f151c;color:#e7eef7;font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1320px;margin:28px auto;padding:0 18px 50px}
.card{background:#18222d;border:1px solid #304052;border-radius:14px;padding:20px;margin:16px 0}
h1,h2{margin-top:0}.good{color:#63e69b}.bad{color:#ff7777}.muted{color:#aab7c5}.mono,code,pre{font-family:Consolas,Monaco,monospace}
table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #304052;text-align:left;vertical-align:top}th{color:#cdd9e5}
pre{white-space:pre-wrap;word-break:break-word;background:#101820;border:1px solid #2c3b4a;border-radius:8px;padding:12px;overflow:auto}
a.btn{display:inline-block;background:#2677ad;color:white;text-decoration:none;padding:11px 16px;border-radius:7px;font-weight:bold}
.badge{display:inline-block;padding:4px 9px;border-radius:999px;background:#173f2a;color:#7af0aa;font-weight:bold}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>MRL Admin Paid/Payouts PATHCHECK v001</h1>
<p><span class="badge">READ ONLY</span></p>
<p class="muted">This diagnostic does not modify or execute either production file. It only reads their source text and checks known dependency/error-log locations.</p>
<a class="btn" href="?export=json">Export PATHCHECK JSON</a>
</div>

<div class="card"><h2>Installed v001 Files</h2><table><tr><th>File</th><th>Exists</th><th>Readable</th><th>SHA-256</th></tr>
<?php foreach ($report['installed_v001'] as $label => $info): ?>
<tr><td><?=h($label)?><br><span class="muted mono"><?=h($info['path'])?></span></td><td class="<?=$info['exists']?'good':'bad'?>"><?=$info['exists']?'YES':'NO'?></td><td class="<?=$info['readable']?'good':'bad'?>"><?=$info['readable']?'YES':'NO'?></td><td class="mono"><?=h($info['sha256'] ?? '')?></td></tr>
<?php endforeach; ?></table></div>

<div class="card"><h2>Page Path / Include Lines</h2><pre><?php foreach ($report['source_path_lines']['page'] as $row) echo h('L'.$row['line'].'  '.$row['text'])."\n"; ?></pre></div>
<div class="card"><h2>Helper Path / Include Lines</h2><pre><?php foreach ($report['source_path_lines']['helper'] as $row) echo h('L'.$row['line'].'  '.$row['text'])."\n"; ?></pre></div>

<div class="card"><h2>Expected Dependencies</h2><table><tr><th>Dependency</th><th>Exists</th><th>Readable</th><th>Path</th></tr>
<?php foreach ($report['dependency_status'] as $label => $info): ?>
<tr><td><?=h($label)?></td><td class="<?=$info['exists']?'good':'bad'?>"><?=$info['exists']?'YES':'NO'?></td><td class="<?=$info['readable']?'good':'bad'?>"><?=$info['readable']?'YES':'NO'?></td><td class="mono"><?=h($info['path'])?></td></tr>
<?php endforeach; ?></table></div>

<div class="card"><h2>Matching Error Log Lines</h2>
<?php foreach ($report['matching_error_log_lines'] as $log => $lines): ?>
<h3 class="mono"><?=h($log)?></h3>
<pre><?=h($lines ? implode("\n", $lines) : 'No matching lines found in the readable tail of this log.')?></pre>
<?php endforeach; ?>
</div>
</div>
</body>
</html>
