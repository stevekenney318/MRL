<?php
declare(strict_types=1);

/**
 * mrl_vendor_dependency_audit_v003_20260920_021609pm.php
 *
 * VERSION: v003
 * GENERATED: 9/20/2026 2:16:09 pm ET
 *
 * PURPOSE:
 * - READ-ONLY targeted follow-up to Vendor Dependency Audit v002.
 * - Inspects the exact 9 "active-review" files from v002 on the production server.
 * - Shows matching source lines/context and flags actual PHP include/require usage.
 * - Does NOT move, delete, rename, edit, or execute any production file.
 */

date_default_timezone_set('America/New_York');

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$rootVendor = $docRoot . '/vendor';

$targets = ['composer.json','cpass.php','fpass.php','js/jotform.js','race_results/weekly_standings.php','race_results/weekly_standings_v065.php','register.php','team_chart.php','verify.php'];

$needles = [
    'vendor/autoload.php',
    '/vendor/autoload.php',
    'PhpOffice',
    'PhpSpreadsheet',
    'Composer\\Autoload',
    'composer/autoload',
    '/vendor/',
];

$rows = [];
$errors = [];

foreach ($targets as $relative) {
    $path = $docRoot . '/' . $relative;

    $row = [
        'relative' => $relative,
        'exists' => is_file($path),
        'matches' => [],
        'include_hits' => [],
        'root_vendor_literal' => false,
        'notes' => [],
    ];

    if (!is_file($path)) {
        $row['notes'][] = 'File not found.';
        $rows[] = $row;
        continue;
    }

    $content = @file_get_contents($path);
    if ($content === false) {
        $row['notes'][] = 'Could not read file.';
        $rows[] = $row;
        continue;
    }

    $lines = preg_split('/\R/', $content);
    if (!is_array($lines)) {
        $lines = [];
    }

    foreach ($lines as $idx => $line) {
        $matchedNeedles = [];

        foreach ($needles as $needle) {
            if (stripos($line, $needle) !== false) {
                $matchedNeedles[] = $needle;
            }
        }

        if (!empty($matchedNeedles)) {
            $row['matches'][] = [
                'line' => $idx + 1,
                'text' => trim($line),
                'needles' => array_values(array_unique($matchedNeedles)),
            ];
        }

        // Stronger signal: include/require statement that references vendor.
        if (preg_match('/\b(require|require_once|include|include_once)\b.*vendor/i', $line)) {
            $row['include_hits'][] = [
                'line' => $idx + 1,
                'text' => trim($line),
            ];
        }

        // Strong signal: explicit document-root or __DIR__ reference to root /vendor.
        if (preg_match('/(?:DOCUMENT_ROOT|__DIR__|dirname\s*\().*vendor/i', $line)
            || preg_match('#["\']/?vendor/autoload\.php["\']#i', $line)) {
            $row['root_vendor_literal'] = true;
        }
    }

    // File-specific interpretation helpers.
    if ($relative === 'composer.json') {
        $row['notes'][] = 'Dependency manifest only; not runtime execution by itself.';
    }

    if ($relative === 'team_chart.php') {
        $row['notes'][] = 'Expected to contain historical/changelog wording after v023; inspect whether any include/require remains.';
    }

    if ($relative === 'race_results/weekly_standings.php') {
        $row['notes'][] = 'Known pure-PHP XLSX page; earlier comments may mention PhpSpreadsheet for comparison.';
    }

    if (preg_match('/(?:^|\/)(?:.*_v\d{3}\.php)$/i', $relative)) {
        $row['notes'][] = 'Versioned standalone copy; likely historical unless linked elsewhere.';
    }

    $rows[] = $row;
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Vendor Dependency Audit v003</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--warn:#ffd166;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1250px;margin:0 auto}
h1{margin:0 0 6px;color:var(--gold)}
h2{margin:0 0 10px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.good{color:var(--good)}.warn{color:var(--warn)}.bad{color:var(--bad)}.muted{color:var(--muted)}
code{background:#282828;padding:2px 5px;border-radius:5px;word-break:break-word}
table{width:100%;border-collapse:collapse}
th,td{padding:8px 9px;border-bottom:1px solid #333;text-align:left;vertical-align:top}
th{color:var(--gold)}
pre{white-space:pre-wrap;word-break:break-word;background:#111;border:1px solid #333;border-radius:8px;padding:10px;margin:6px 0}
.tag{display:inline-block;padding:2px 7px;border-radius:12px;font-size:12px;font-weight:700}
.tag.good{background:#163321}.tag.warn{background:#4a2f12}.tag.bad{background:#4b1f1f}
ul{line-height:1.5}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Vendor Dependency Audit v003</h1>
    <div class="muted">READ-ONLY targeted production inspection · generated 9/20/2026 2:16:09 pm ET</div>

    <div class="card">
        <h2>Purpose</h2>
        <p>
            This examines the exact 9 files v002 marked for active review and shows
            the actual matching source lines from the Hostinger production files.
        </p>
        <p>
            Root vendor under consideration:
            <code><?php echo h($rootVendor); ?></code>
        </p>
    </div>

    <?php foreach ($rows as $row): ?>
    <div class="card">
        <h2><?php echo h($row['relative']); ?></h2>

        <?php if (!$row['exists']): ?>
            <p class="bad"><strong>MISSING</strong></p>
        <?php else: ?>
            <p>
                PHP include/require lines referencing vendor:
                <?php if (empty($row['include_hits'])): ?>
                    <span class="tag good">NONE</span>
                <?php else: ?>
                    <span class="tag bad"><?php echo count($row['include_hits']); ?> FOUND</span>
                <?php endif; ?>

                &nbsp;&nbsp; Root-vendor-style literal:
                <?php if ($row['root_vendor_literal']): ?>
                    <span class="tag warn">POSSIBLE</span>
                <?php else: ?>
                    <span class="tag good">NO</span>
                <?php endif; ?>
            </p>

            <?php if (!empty($row['notes'])): ?>
                <ul>
                <?php foreach ($row['notes'] as $note): ?>
                    <li class="muted"><?php echo h($note); ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($row['include_hits'])): ?>
                <h3>Include / require evidence</h3>
                <?php foreach ($row['include_hits'] as $hit): ?>
                    <pre>Line <?php echo (int)$hit['line']; ?>: <?php echo h($hit['text']); ?></pre>
                <?php endforeach; ?>
            <?php endif; ?>

            <h3>All matching lines</h3>
            <?php if (empty($row['matches'])): ?>
                <p class="good">No matching lines remain.</p>
            <?php else: ?>
                <?php foreach ($row['matches'] as $hit): ?>
                    <pre>Line <?php echo (int)$hit['line']; ?> [<?php echo h(implode(', ', $hit['needles'])); ?>]
<?php echo h($hit['text']); ?></pre>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="card">
        <h2>Safety</h2>
        <ul>
            <li>No files are edited.</li>
            <li>No files or folders are moved, renamed, quarantined, or deleted.</li>
            <li>No PHP target file is executed by this audit.</li>
            <li>No database, scheduler, cron, or WordPress state is changed.</li>
        </ul>
    </div>
</div>
</body>
</html>
