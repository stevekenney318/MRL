<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$stateFile = __DIR__ . '/_cron_espn_results_state.json';
$logFile   = __DIR__ . '/_cron_espn_results_watch.log';

function fileSummary(string $path): string {
    if (!is_file($path)) {
        return "MISSING: {$path}\n";
    }
    $size = filesize($path);
    $mtime = filemtime($path);
    $mtimeStr = $mtime ? date('Y-m-d H:i:s', (int)$mtime) : '(unknown)';
    return "OK: {$path}\n  size : {$size}\n  mtime: {$mtimeStr}\n";
}

function tailLastLine(string $path): string {
    if (!is_file($path)) return '(log missing)';
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines) || count($lines) === 0) return '(log empty)';
    return (string)$lines[count($lines) - 1];
}

echo "CRON FILE STATUS\n";
echo "================\n\n";

echo "STATE FILE\n";
echo "----------\n";
echo fileSummary($stateFile);

$lastCheckedAt = '(not found)';
if (is_file($stateFile)) {
    $raw = file_get_contents($stateFile);
    if ($raw !== false) {
        $data = json_decode($raw, true);
        if (is_array($data)) {
            // Try to find last_checked_at for any year (or specifically 2026 if present)
            if (isset($data['byYear']['2026']['last_checked_at'])) {
                $lastCheckedAt = (string)$data['byYear']['2026']['last_checked_at'];
            } elseif (isset($data['byYear']) && is_array($data['byYear'])) {
                foreach ($data['byYear'] as $yr => $info) {
                    if (is_array($info) && isset($info['last_checked_at'])) {
                        $lastCheckedAt = "{$yr}: " . (string)$info['last_checked_at'];
                        break;
                    }
                }
            }
        } else {
            $lastCheckedAt = '(JSON decode failed)';
        }
    } else {
        $lastCheckedAt = '(could not read state file)';
    }
}

echo "  last_checked_at: {$lastCheckedAt}\n\n";

echo "LOG FILE\n";
echo "--------\n";
echo fileSummary($logFile);
echo "  last log line: " . tailLastLine($logFile) . "\n\n";

echo "Done.\n";