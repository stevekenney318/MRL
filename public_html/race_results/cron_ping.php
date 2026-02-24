<?php
declare(strict_types=1);

/**
 * cron_ping.php
 * Purpose: prove this exact file executed by writing files in THIS folder.
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_cron_ping_php_errors.log');
error_reporting(E_ALL);

$ts    = date('Y-m-d H:i:s');
$micro = sprintf('%.6f', microtime(true));
$token = bin2hex(random_bytes(8));

$line = "PING OK  ts={$ts}  micro={$micro}  token={$token}  sapi=" . PHP_SAPI . "\n";

$pingFile = __DIR__ . '/_cron_ping_last.txt';
$logFile  = __DIR__ . '/_cron_ping.log';

// Always overwrite ping file + append to log
@file_put_contents($pingFile, $line);
@file_put_contents($logFile, "[".$ts."] ".$line, FILE_APPEND);

// Output something visible if run in browser
header('Content-Type: text/plain; charset=utf-8');
echo $line;
echo "Wrote:\n";
echo " - {$pingFile}\n";
echo " - {$logFile}\n";
echo " - " . (__DIR__ . "/_cron_ping_php_errors.log") . "\n";