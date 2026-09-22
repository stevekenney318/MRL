<?php
declare(strict_types=1);

date_default_timezone_set('America/New_York');

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function yesno(bool $v): string {
    return $v ? 'YES' : 'NO';
}

function command_test(string $method, string $cmd): array {
    $out = '';
    $status = null;
    $error = '';

    try {
        if ($method === 'shell_exec') {
            $result = @shell_exec($cmd . ' 2>&1');
            if ($result === null) {
                return ['ok'=>false,'output'=>'[NULL returned]','status'=>null];
            }
            $out = (string)$result;
        } elseif ($method === 'exec') {
            $lines = [];
            $status = 0;
            @exec($cmd . ' 2>&1', $lines, $status);
            $out = implode("\n", $lines);
        } elseif ($method === 'proc_open') {
            $descriptors = [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $process = @proc_open($cmd, $descriptors, $pipes);
            if (!is_resource($process)) {
                return ['ok'=>false,'output'=>'proc_open failed to create process','status'=>null];
            }
            $stdout = isset($pipes[1]) ? stream_get_contents($pipes[1]) : '';
            $stderr = isset($pipes[2]) ? stream_get_contents($pipes[2]) : '';
            if (isset($pipes[1]) && is_resource($pipes[1])) fclose($pipes[1]);
            if (isset($pipes[2]) && is_resource($pipes[2])) fclose($pipes[2]);
            $status = @proc_close($process);
            $out = (string)$stdout . (string)$stderr;
        } elseif ($method === 'popen') {
            $handle = @popen($cmd . ' 2>&1', 'r');
            if (!is_resource($handle)) {
                return ['ok'=>false,'output'=>'popen failed to create process','status'=>null];
            }
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line !== false) $out .= $line;
            }
            $status = @pclose($handle);
        } else {
            return ['ok'=>false,'output'=>'Unsupported method','status'=>null];
        }
    } catch (Throwable $e) {
        $error = get_class($e) . ': ' . $e->getMessage();
    }

    $out = trim($out);
    if ($error !== '') {
        $out = ($out !== '' ? $out . "\n" : '') . $error;
    }

    return [
        'ok' => ($out !== ''),
        'output' => ($out !== '' ? $out : '[no output]'),
        'status' => $status,
    ];
}

$functions = ['shell_exec','exec','proc_open','popen','system','passthru'];
$functionRows = [];
foreach ($functions as $fn) {
    $functionRows[] = [
        'name' => $fn,
        'exists' => function_exists($fn),
        'callable' => is_callable($fn),
    ];
}

$disabled = (string)ini_get('disable_functions');
$disabledList = array_values(array_filter(array_map('trim', explode(',', $disabled))));

$binaryCandidates = [
    'plain php' => 'php',
    '/usr/bin/php' => '/usr/bin/php',
    '/opt/alt/php73/usr/bin/php' => '/opt/alt/php73/usr/bin/php',
    '/opt/alt/php73/usr/bin/lsphp' => '/opt/alt/php73/usr/bin/lsphp',
];

$binaryRows = [];
foreach ($binaryCandidates as $label => $candidate) {
    if ($candidate === 'php') {
        $binaryRows[] = [
            'label' => $label,
            'path' => $candidate,
            'exists' => null,
            'executable' => null,
        ];
    } else {
        $binaryRows[] = [
            'label' => $label,
            'path' => $candidate,
            'exists' => is_file($candidate),
            'executable' => is_executable($candidate),
        ];
    }
}

$availableMethod = '';
foreach (['shell_exec','exec','proc_open','popen'] as $method) {
    if (function_exists($method) && is_callable($method)) {
        $availableMethod = $method;
        break;
    }
}

$tests = [];
if ($availableMethod !== '') {
    foreach ($binaryCandidates as $label => $candidate) {
        if ($candidate !== 'php' && !is_file($candidate)) {
            continue;
        }

        $quoted = escapeshellarg($candidate);
        $tests[] = [
            'method' => $availableMethod,
            'label' => $label . ' -v',
            'command' => $candidate . ' -v',
            'result' => command_test($availableMethod, $quoted . ' -v'),
        ];

        $tests[] = [
            'method' => $availableMethod,
            'label' => $label . ' -l this diagnostic',
            'command' => $candidate . ' -l ' . __FILE__,
            'result' => command_test(
                $availableMethod,
                $quoted . ' -l ' . escapeshellarg(__FILE__)
            ),
        ];
    }
}

$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
$sapi = PHP_SAPI;
$phpVersion = PHP_VERSION;
$phpBinary = defined('PHP_BINARY') ? PHP_BINARY : '';
$loadedIni = php_ini_loaded_file() ?: '';
$scanDir = (string)get_cfg_var('cfg_file_scan_dir');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL PHP Lint Environment Diagnostic</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3a3a3a;--text:#eee;--muted:#aaa;--good:#65df8c;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1150px;margin:0 auto}
h1{margin:0 0 6px;color:var(--gold)}
h2{margin:0 0 12px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
table{width:100%;border-collapse:collapse}
th,td{padding:8px 10px;border-bottom:1px solid #333;text-align:left;vertical-align:top}
th{color:var(--gold)}
.yes{color:var(--good);font-weight:700}
.no{color:var(--bad);font-weight:700}
.muted{color:var(--muted)}
code{background:#252525;padding:2px 5px;border-radius:5px}
pre{white-space:pre-wrap;word-break:break-word;background:#111;border:1px solid #333;border-radius:10px;padding:12px;margin:8px 0 0}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL PHP Lint Environment Diagnostic</h1>
<div class="muted">Generated 9/22/2026 2:10:23 pm ET · read-only diagnostic · no installer actions · no database writes</div>

<div class="card">
<h2>Web PHP</h2>
<table>
<tr><th>Item</th><th>Value</th></tr>
<tr><td>PHP_VERSION</td><td><?php echo h($phpVersion); ?></td></tr>
<tr><td>PHP_SAPI</td><td><?php echo h($sapi); ?></td></tr>
<tr><td>PHP_BINARY</td><td><code><?php echo h($phpBinary); ?></code></td></tr>
<tr><td>SERVER_SOFTWARE</td><td><?php echo h($serverSoftware); ?></td></tr>
<tr><td>Loaded php.ini</td><td><code><?php echo h($loadedIni); ?></code></td></tr>
<tr><td>Additional ini scan dir</td><td><code><?php echo h($scanDir); ?></code></td></tr>
<tr><td>disable_functions</td><td><code><?php echo h($disabled !== '' ? $disabled : '[empty]'); ?></code></td></tr>
</table>
</div>

<div class="card">
<h2>Command Functions</h2>
<table>
<tr><th>Function</th><th>function_exists()</th><th>is_callable()</th><th>Listed in disable_functions</th></tr>
<?php foreach ($functionRows as $row): ?>
<tr>
<td><code><?php echo h($row['name']); ?></code></td>
<td class="<?php echo $row['exists'] ? 'yes' : 'no'; ?>"><?php echo yesno((bool)$row['exists']); ?></td>
<td class="<?php echo $row['callable'] ? 'yes' : 'no'; ?>"><?php echo yesno((bool)$row['callable']); ?></td>
<td><?php echo in_array($row['name'], $disabledList, true) ? 'YES' : 'NO'; ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="card">
<h2>Known PHP Binary Paths</h2>
<table>
<tr><th>Candidate</th><th>Path</th><th>File exists</th><th>Executable</th></tr>
<?php foreach ($binaryRows as $row): ?>
<tr>
<td><?php echo h($row['label']); ?></td>
<td><code><?php echo h($row['path']); ?></code></td>
<td><?php echo $row['exists'] === null ? 'N/A' : yesno((bool)$row['exists']); ?></td>
<td><?php echo $row['executable'] === null ? 'N/A' : yesno((bool)$row['executable']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="card">
<h2>CLI Tests</h2>
<?php if ($availableMethod === ''): ?>
<p class="no">No callable command-launch function was found, so no CLI command was attempted.</p>
<?php else: ?>
<p>First callable launcher: <strong><?php echo h($availableMethod); ?></strong></p>
<?php foreach ($tests as $test): ?>
<div style="margin:16px 0">
<div><strong><?php echo h($test['label']); ?></strong></div>
<div class="muted">Method: <?php echo h($test['method']); ?> · Command: <code><?php echo h($test['command']); ?></code></div>
<pre><?php echo h($test['result']['output']); ?></pre>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<div class="card">
<h2>What to send back</h2>
<p>A screenshot of this page is enough. The most useful sections are <strong>Web PHP</strong>, <strong>Command Functions</strong>, and <strong>CLI Tests</strong>.</p>
</div>
</div>
</body>
</html>
