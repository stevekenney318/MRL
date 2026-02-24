<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST_WRITE SIGNATURE: V2026-02-22-A ===\n";
echo "If you see this line, you are running the NEW code.\n\n";

echo "Running: test_write.php\n";
echo "----------------------\n";

echo "Executing file : " . __FILE__ . "\n";
echo "Executing dir  : " . __DIR__ . "\n\n";

// Show a hash of the exact PHP source file being executed
$source = @file_get_contents(__FILE__);
if ($source === false) {
    echo "Could not read __FILE__ to hash it.\n\n";
} else {
    echo "This script SHA256: " . hash('sha256', $source) . "\n\n";
}

echo "Directory exists?   : " . (is_dir(__DIR__) ? "YES" : "NO") . "\n";
echo "Directory writable? : " . (is_writable(__DIR__) ? "YES" : "NO") . "\n\n";

// Unique filename every run
$filename = __DIR__ . '/simple_test_' . date('Ymd_His') . '.log';

echo "Attempting write to:\n$filename\n\n";

$content = "Test write at " . date('Y-m-d H:i:s') . "\n";
$result  = file_put_contents($filename, $content);

if ($result === false) {
    echo "WRITE FAILED\n";
    exit;
}

echo "WRITE SUCCESS\n\n";

// Verify immediately
echo "Verifying file...\n";
echo "file_exists(): " . (file_exists($filename) ? "YES" : "NO") . "\n";
echo "realpath()    : " . (realpath($filename) ?: "(realpath failed)") . "\n";
echo "filesize()    : " . (is_file($filename) ? filesize($filename) : 0) . "\n\n";

echo "Reading file contents:\n";
echo "----------------------\n";

$read = file_get_contents($filename);
echo ($read === false) ? "READ FAILED\n" : $read;

echo "\nDone.\n";