<?php
declare(strict_types=1);

/**
 * mrl_vendor_dependency_audit_v002_20260920_021030pm.php
 *
 * VERSION: v002
 * GENERATED: 9/20/2026 2:10:30 pm ET
 *
 * PURPOSE:
 * - READ-ONLY production audit of actual Hostinger public_html.
 * - Focuses on custom MRL code that could depend on root /public_html/vendor.
 * - Excludes WordPress-owned trees from the dependency decision.
 * - Classifies known history/installers/backups separately from active-review files.
 * - Does NOT move, delete, rename, edit, or execute production files.
 */

date_default_timezone_set('America/New_York');

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fmt_bytes(int $bytes): string
{
    $units = ['B','KB','MB','GB'];
    $n = (float)$bytes;
    $i = 0;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return number_format($n, $i === 0 ? 0 : 2) . ' ' . $units[$i];
}

function is_text_candidate(string $path): bool
{
    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, [
        'php','inc','phtml','html','htm','js','json','txt','md','css','xml','ini'
    ], true);
}

function normalize_rel(string $path, string $docRoot): string
{
    return ltrim(str_replace('\\', '/', str_replace($docRoot, '', $path)), '/');
}

function is_wordpress_owned(string $relative): bool
{
    $r = strtolower('/' . ltrim(str_replace('\\', '/', $relative), '/'));

    foreach ([
        '/wp-admin/',
        '/wp-includes/',
        '/wp-content/plugins/',
        '/wp-content/themes/',
    ] as $prefix) {
        if (strpos($r, $prefix) === 0) {
            return true;
        }
    }

    return false;
}

function classify_nonactive(string $relative): string
{
    $r = strtolower('/' . ltrim(str_replace('\\', '/', $relative), '/'));
    $base = strtolower(basename($r));

    foreach ([
        '/_installer_backups/' => 'installer backup',
        '/_migration_backups/' => 'migration backup',
        '/to_be_deleted_' => 'to-be-deleted',
        '/_quarantine/' => 'quarantine',
        '/quarantine/' => 'quarantine',
        '/archive/' => 'archive',
        '/archives/' => 'archive',
        '/backup/' => 'backup',
        '/backups/' => 'backup',
        '/old/' => 'old/history',
        '/obsolete/' => 'obsolete',
        '/legacy/' => 'legacy',
    ] as $marker => $label) {
        if (strpos($r, $marker) !== false) {
            return $label;
        }
    }

    if (strpos($base, 'install_') === 0 || strpos($base, 'mrl_install_') === 0) {
        return 'installer';
    }

    if (strpos($base, '_lintgate.php') !== false) {
        return 'lintgate';
    }

    if (preg_match('/(?:^|[_ -])v\d{3}(?:[_ .-]|$)/i', $base) && strpos($base, 'team_chart') !== false) {
        return 'versioned copy';
    }

    if (preg_match('/team_chart\s*-\s*\d{8}/i', $base)) {
        return 'dated copy';
    }

    if (preg_match('/team_chart[23]\.php$/i', $base)) {
        return 'old variant';
    }

    if (strpos($base, '_bak_') !== false || strpos($base, '.bak') !== false || strpos($base, 'backup') !== false) {
        return 'backup';
    }

    if (strpos($base, 'test') !== false && substr($base, -4) === '.php') {
        return 'test file';
    }

    if (strpos($base, 'audit') !== false && substr($base, -4) === '.php') {
        return 'audit/diagnostic';
    }

    return '';
}

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$vendorDir = $docRoot . '/vendor';

$needles = [
    'vendor/autoload.php',
    '/vendor/autoload.php',
    'PhpOffice',
    'PhpSpreadsheet',
    'Composer\\Autoload',
    'composer/autoload',
    '/vendor/',
];

$scanSkipPrefixes = [
    rtrim($vendorDir, '/\\') . DIRECTORY_SEPARATOR,
    rtrim($docRoot . '/.git', '/\\') . DIRECTORY_SEPARATOR,
    rtrim($docRoot . '/wp-admin', '/\\') . DIRECTORY_SEPARATOR,
    rtrim($docRoot . '/wp-includes', '/\\') . DIRECTORY_SEPARATOR,
    rtrim($docRoot . '/wp-content/plugins', '/\\') . DIRECTORY_SEPARATOR,
    rtrim($docRoot . '/wp-content/themes', '/\\') . DIRECTORY_SEPARATOR,
    rtrim($docRoot . '/wp-content/cache', '/\\') . DIRECTORY_SEPARATOR,
];

$filesScanned = 0;
$bytesScanned = 0;
$oversizeSkipped = 0;
$activeHits = [];
$nonactiveHits = [];
$wpSkippedFiles = 0;
$errors = [];

try {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $docRoot,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
        )
    );

    foreach ($it as $info) {
        if (!$info->isFile()) {
            continue;
        }

        $path = $info->getPathname();
        $relative = normalize_rel($path, $docRoot);

        if (is_wordpress_owned($relative)) {
            $wpSkippedFiles++;
            continue;
        }

        $skip = false;
        foreach ($scanSkipPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                $skip = true;
                break;
            }
        }
        if ($skip || !is_text_candidate($path)) {
            continue;
        }

        $filesScanned++;
        $size = (int)$info->getSize();
        $bytesScanned += $size;

        if ($size > 5 * 1024 * 1024) {
            $oversizeSkipped++;
            continue;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            $errors[] = 'Could not read: ' . $path;
            continue;
        }

        $matched = [];
        foreach ($needles as $needle) {
            if (stripos($content, $needle) !== false) {
                $matched[] = $needle;
            }
        }

        if (empty($matched)) {
            continue;
        }

        $row = [
            'path' => $relative,
            'matches' => array_values(array_unique($matched)),
            'size' => $size,
        ];

        $nonactiveClass = classify_nonactive($relative);

        if ($nonactiveClass !== '') {
            $row['classification'] = $nonactiveClass;
            $nonactiveHits[] = $row;
        } else {
            $row['classification'] = 'active review';
            $activeHits[] = $row;
        }
    }
} catch (Throwable $e) {
    $errors[] = 'Tree scan exception: ' . $e->getMessage();
}

$sorter = function ($a, $b) {
    return strcasecmp((string)$a['path'], (string)$b['path']);
};
usort($activeHits, $sorter);
usort($nonactiveHits, $sorter);

$vendorFiles = 0;
$vendorFolders = 0;
$vendorBytes = 0;
$topLevelVendor = [];

if (is_dir($vendorDir)) {
    $top = @scandir($vendorDir);
    if (is_array($top)) {
        foreach ($top as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $full = $vendorDir . '/' . $name;
            $topLevelVendor[] = [
                'name' => $name,
                'type' => is_dir($full) ? 'folder' : 'file',
            ];
        }
    }

    try {
        $vIt = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $vendorDir,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($vIt as $vInfo) {
            if ($vInfo->isDir()) {
                $vendorFolders++;
            } elseif ($vInfo->isFile()) {
                $vendorFiles++;
                $vendorBytes += (int)$vInfo->getSize();
            }
        }
    } catch (Throwable $e) {
        $errors[] = 'Vendor inventory exception: ' . $e->getMessage();
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Vendor Dependency Audit v002</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--warn:#ffd166;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1200px;margin:0 auto}
h1{margin:0 0 6px;color:var(--gold)}
h2{margin:0 0 10px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.stat{padding:12px;border:1px solid #383838;border-radius:10px;background:#151515}
.stat .label{color:var(--muted);font-size:13px}
.stat .value{font-size:22px;font-weight:800;margin-top:3px}
.good{color:var(--good)}.warn{color:var(--warn)}.bad{color:var(--bad)}.muted{color:var(--muted)}
code{background:#282828;padding:2px 5px;border-radius:5px;word-break:break-word}
table{width:100%;border-collapse:collapse}
th,td{padding:8px 9px;border-bottom:1px solid #333;text-align:left;vertical-align:top}
th{color:var(--gold)}
.tag{display:inline-block;padding:2px 7px;border-radius:12px;font-size:12px;font-weight:700;background:#26303b;color:#c9d8e8}
.tag.active{background:#4a2f12;color:#ffd596}
ul{line-height:1.5}
@media(max-width:850px){.grid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Vendor Dependency Audit v002</h1>
    <div class="muted">READ-ONLY production scan · generated 9/20/2026 2:10:30 pm ET</div>

    <div class="card">
        <h2>Scope</h2>
        <p>
            Document root: <code><?php echo h($docRoot); ?></code><br>
            Root vendor: <code><?php echo h($vendorDir); ?></code>
        </p>
        <p class="good"><strong>WordPress-owned trees are excluded from this dependency decision.</strong></p>
        <p class="muted">
            Excluded: <code>wp-admin</code>, <code>wp-includes</code>,
            <code>wp-content/plugins</code>, <code>wp-content/themes</code>,
            plus root <code>/vendor</code> itself.
        </p>
    </div>

    <div class="card">
        <h2>Vendor inventory</h2>
        <div class="grid">
            <div class="stat"><div class="label">Vendor exists</div><div class="value <?php echo is_dir($vendorDir) ? 'good' : 'bad'; ?>"><?php echo is_dir($vendorDir) ? 'YES' : 'NO'; ?></div></div>
            <div class="stat"><div class="label">Vendor files</div><div class="value"><?php echo (int)$vendorFiles; ?></div></div>
            <div class="stat"><div class="label">Vendor folders</div><div class="value"><?php echo (int)$vendorFolders; ?></div></div>
            <div class="stat"><div class="label">Vendor size</div><div class="value"><?php echo h(fmt_bytes($vendorBytes)); ?></div></div>
        </div>

        <?php if (!empty($topLevelVendor)): ?>
            <p><strong>Top-level contents:</strong>
            <?php
            $parts = [];
            foreach ($topLevelVendor as $item) {
                $parts[] = $item['name'] . ' [' . $item['type'] . ']';
            }
            echo h(implode(' · ', $parts));
            ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Custom-code scan summary</h2>
        <div class="grid">
            <div class="stat"><div class="label">Files scanned</div><div class="value"><?php echo (int)$filesScanned; ?></div></div>
            <div class="stat"><div class="label">Bytes scanned</div><div class="value"><?php echo h(fmt_bytes($bytesScanned)); ?></div></div>
            <div class="stat"><div class="label">Active-review hits</div><div class="value <?php echo empty($activeHits) ? 'good' : 'warn'; ?>"><?php echo count($activeHits); ?></div></div>
            <div class="stat"><div class="label">Known non-active hits</div><div class="value"><?php echo count($nonactiveHits); ?></div></div>
        </div>

        <p class="muted">
            WordPress files skipped by scope: <?php echo (int)$wpSkippedFiles; ?>
        </p>

        <?php if ($oversizeSkipped > 0): ?>
            <p class="warn"><strong>Note:</strong> <?php echo (int)$oversizeSkipped; ?> text-like file(s) over 5 MB were counted but not content-scanned.</p>
        <?php endif; ?>

        <p class="<?php echo empty($activeHits) ? 'good' : 'warn'; ?>" style="font-weight:800;">
            <?php echo empty($activeHits)
                ? 'No active-review references were found in custom MRL scope.'
                : 'Review the active-review references below before quarantining root /vendor.'; ?>
        </p>
    </div>

    <div class="card">
        <h2>Active-review references</h2>

        <?php if (empty($activeHits)): ?>
            <p class="good"><strong>None.</strong></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Classification</th><th>File</th><th>Matched terms</th><th>Size</th></tr>
                </thead>
                <tbody>
                <?php foreach ($activeHits as $row): ?>
                    <tr>
                        <td><span class="tag active">active review</span></td>
                        <td><code><?php echo h($row['path']); ?></code></td>
                        <td><?php echo h(implode(', ', $row['matches'])); ?></td>
                        <td><?php echo h(fmt_bytes((int)$row['size'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Known non-active references</h2>

        <?php if (empty($nonactiveHits)): ?>
            <p class="good"><strong>None.</strong></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Classification</th><th>File</th><th>Matched terms</th><th>Size</th></tr>
                </thead>
                <tbody>
                <?php foreach ($nonactiveHits as $row): ?>
                    <tr>
                        <td><span class="tag"><?php echo h($row['classification']); ?></span></td>
                        <td><code><?php echo h($row['path']); ?></code></td>
                        <td><?php echo h(implode(', ', $row['matches'])); ?></td>
                        <td><?php echo h(fmt_bytes((int)$row['size'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="card">
        <h2 class="bad">Read warnings</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2>Safety</h2>
        <ul>
            <li>No files are edited.</li>
            <li>No files or folders are moved, renamed, quarantined, or deleted.</li>
            <li>No database, scheduler, cron, or WordPress state is changed.</li>
            <li>Quarantine remains a separate reversible installer after we review this report.</li>
        </ul>
    </div>
</div>
</body>
</html>
