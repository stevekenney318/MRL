<?php
declare(strict_types=1);

/**
 * cron_all_schedulers.php
 *
 * VERSION: v001
 * LAST MODIFIED: 5/25/2026 3:26:00 pm
 *
 * DESCRIPTION:
 * Small top-level launcher for the MRL master schedulers.
 *
 * Purpose:
 * - Allows one short Hostinger cron command to run both scheduler environments.
 * - Runs live scheduler first, then testphp8 scheduler.
 * - Keeps live/test scheduler state, logs, and locks separate because each
 *   scheduler still runs from its own race_results folder.
 *
 * Suggested Hostinger cron command:
 * /usr/bin/php /home/u809830586/domains/manliusracingleague.com/public_html/cron_all_schedulers.php > /dev/null 2>&1
 *
 * PHP: 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

const CAS_VERSION = 'v001';
const CAS_SIGNATURE = 'CRON_ALL_SCHEDULERS v001';

$rootDir = __DIR__;

$targets = [
    [
        'name' => 'live',
        'script' => $rootDir . '/race_results/cron_master_scheduler.php',
    ],
    [
        'name' => 'testphp8',
        'script' => $rootDir . '/testphp8/race_results/cron_master_scheduler.php',
    ],
];

function cas_now_string(): string
{
    return date('Y-m-d H:i:s');
}

function cas_out(string $line): void
{
    if (PHP_SAPI === 'cli') {
        echo $line . PHP_EOL;
        return;
    }

    echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "<br>\n";
}

function cas_append_log(string $logFile, string $line): void
{
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    @file_put_contents($logFile, '[' . cas_now_string() . '] ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function cas_quote_arg(string $value): string
{
    if (function_exists('escapeshellarg')) {
        return escapeshellarg($value);
    }

    return "'" . str_replace("'", "'\\''", $value) . "'";
}

$launcherDir = $rootDir . '/_scheduler_launcher';
$logFile = $launcherDir . '/log.txt';
$heartbeatFile = $launcherDir . '/heartbeat.txt';
$stateFile = $launcherDir . '/state.json';

if (!is_dir($launcherDir)) {
    @mkdir($launcherDir, 0755, true);
}

$token = bin2hex(random_bytes(8));
$startedAt = cas_now_string();

$heartbeat = $startedAt
    . ' token=' . $token
    . ' sig=' . CAS_SIGNATURE
    . ' sapi=' . PHP_SAPI
    . ' root=' . $rootDir;

@file_put_contents($heartbeatFile, $heartbeat . PHP_EOL, LOCK_EX);

cas_append_log($logFile, CAS_SIGNATURE . ' RUN sapi=' . PHP_SAPI . ' token=' . $token);
cas_out('=== ' . CAS_SIGNATURE . ' ===');
cas_out('Run started: ' . $startedAt);
cas_out('Root dir: ' . $rootDir);
cas_out('---');

$results = [];
$ran = 0;
$missing = 0;
$errors = 0;

foreach ($targets as $target) {
    $name = (string)$target['name'];
    $script = (string)$target['script'];

    if (!is_file($script)) {
        $missing++;
        $errors++;
        $message = 'MISSING ' . $name . ' scheduler script=' . $script;
        $results[] = [
            'name' => $name,
            'script' => $script,
            'status' => 'missing',
            'exit_code' => null,
            'message' => $message,
            'started_at' => cas_now_string(),
            'completed_at' => cas_now_string(),
        ];
        cas_append_log($logFile, $message);
        cas_out('MISSING ' . $name . ': ' . $script);
        continue;
    }

    $taskStartedAt = cas_now_string();
    $cmd = PHP_BINARY . ' ' . cas_quote_arg($script) . ' 2>&1';

    cas_append_log($logFile, 'TASK RUN name=' . $name . ' script=' . $script);
    cas_out('RUN ' . $name . ': ' . basename($script));

    $output = [];
    $exitCode = 0;
    @exec($cmd, $output, $exitCode);

    $taskCompletedAt = cas_now_string();
    $ran++;

    if ((int)$exitCode !== 0) {
        $errors++;
        $status = 'error';
        $message = 'exit=' . (string)$exitCode;
        cas_append_log($logFile, 'TASK ERROR name=' . $name . ' exit=' . (string)$exitCode);
        cas_out('ERROR ' . $name . ': exit=' . (string)$exitCode);
    } else {
        $status = 'success';
        $message = 'exit=0';
        cas_append_log($logFile, 'TASK SUCCESS name=' . $name . ' exit=0');
        cas_out('SUCCESS ' . $name . ': exit=0');
    }

    $results[] = [
        'name' => $name,
        'script' => $script,
        'status' => $status,
        'exit_code' => (int)$exitCode,
        'message' => $message,
        'started_at' => $taskStartedAt,
        'completed_at' => $taskCompletedAt,
        'output_tail' => array_slice($output, -10),
    ];
}

$completedAt = cas_now_string();

$state = [
    'signature' => CAS_SIGNATURE,
    'version' => CAS_VERSION,
    'sapi' => PHP_SAPI,
    'root_dir' => $rootDir,
    'started_at' => $startedAt,
    'completed_at' => $completedAt,
    'token' => $token,
    'summary' => [
        'targets' => count($targets),
        'ran' => $ran,
        'missing' => $missing,
        'errors' => $errors,
    ],
    'results' => $results,
];

@file_put_contents(
    $stateFile,
    json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    LOCK_EX
);

cas_out('---');
cas_out('Run complete: ' . $completedAt);
cas_out('Targets checked: ' . count($targets));
cas_out('Targets ran: ' . $ran);
cas_out('Targets missing: ' . $missing);
cas_out('Target errors: ' . $errors);

cas_append_log(
    $logFile,
    CAS_SIGNATURE
    . ' DONE ; TARGETS checked=' . count($targets)
    . ' ran=' . $ran
    . ' missing=' . $missing
    . ' errors=' . $errors
    . ' token=' . $token
);

exit($errors > 0 ? 1 : 0);
