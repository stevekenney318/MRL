<?php
declare(strict_types=1);

/**
 * mrl_vendor_quarantine_v001_20260920_022501pm.php
 *
 * VERSION: v001
 * GENERATED: 9/20/2026 2:25:01 pm ET
 *
 * PURPOSE:
 * - Reversibly quarantine the root /public_html/vendor directory.
 * - Uses a same-filesystem rename into /public_html/_quarantine/.
 * - Provides one-click restore.
 * - Does NOT touch WordPress plugin/theme vendor trees.
 * - Does NOT modify composer.json or any PHP file.
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
    while ($n >= 1024 && $i < count($units)-1) {
        $n /= 1024;
        $i++;
    }
    return number_format($n, $i === 0 ? 0 : 2) . ' ' . $units[$i];
}

function inventory_dir(string $dir): array
{
    $files = 0;
    $dirs = 0;
    $bytes = 0;

    if (!is_dir($dir)) {
        return ['files'=>0,'dirs'=>0,'bytes'=>0];
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $dir,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($it as $info) {
        if ($info->isDir()) {
            $dirs++;
        } elseif ($info->isFile()) {
            $files++;
            $bytes += (int)$info->getSize();
        }
    }

    return ['files'=>$files,'dirs'=>$dirs,'bytes'=>$bytes];
}

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$source = $docRoot . '/vendor';
$quarantineRoot = $docRoot . '/_quarantine';
$destination = $quarantineRoot . '/vendor_20260920_022501pm';

$action = (string)($_POST['action'] ?? '');
$message = '';
$messageClass = 'info';

$sourceExists = is_dir($source);
$destExists = is_dir($destination);

$inventoryPath = $sourceExists ? $source : ($destExists ? $destination : '');
$inventory = $inventoryPath !== '' ? inventory_dir($inventoryPath) : ['files'=>0,'dirs'=>0,'bytes'=>0];

$canQuarantine = $sourceExists && !$destExists && is_writable($docRoot);
$canRestore = !$sourceExists && $destExists && is_writable($docRoot);

if ($action === 'quarantine') {
    if (!$canQuarantine) {
        $message = 'Quarantine blocked: preflight conditions are not satisfied.';
        $messageClass = 'bad';
    } else {
        if (!is_dir($quarantineRoot) && !@mkdir($quarantineRoot, 0755, true) && !is_dir($quarantineRoot)) {
            $message = 'Quarantine failed: could not create /_quarantine directory.';
            $messageClass = 'bad';
        } elseif (!@rename($source, $destination)) {
            $message = 'Quarantine failed: vendor directory could not be moved.';
            $messageClass = 'bad';
        } else {
            $message = 'PASS — root /vendor moved into quarantine. Nothing was deleted.';
            $messageClass = 'good';
        }
    }
}

if ($action === 'restore') {
    if (!$canRestore) {
        $message = 'Restore blocked: preflight conditions are not satisfied.';
        $messageClass = 'bad';
    } elseif (!@rename($destination, $source)) {
        $message = 'Restore failed: quarantined vendor directory could not be moved back.';
        $messageClass = 'bad';
    } else {
        $message = 'PASS — root /vendor restored from quarantine.';
        $messageClass = 'good';
    }
}

$sourceExists = is_dir($source);
$destExists = is_dir($destination);
$inventoryPath = $sourceExists ? $source : ($destExists ? $destination : '');
$inventory = $inventoryPath !== '' ? inventory_dir($inventoryPath) : ['files'=>0,'dirs'=>0,'bytes'=>0];

$canQuarantine = $sourceExists && !$destExists && is_writable($docRoot);
$canRestore = !$sourceExists && $destExists && is_writable($docRoot);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Vendor Quarantine</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1050px;margin:0 auto}
h1{margin:0 0 6px;color:var(--gold)}
h2{margin:0 0 10px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.notice{margin:14px 0;padding:12px 14px;border-radius:10px;border:1px solid #36506f;background:#142033}
.notice.good{border-color:#327a4b;background:#13271a;color:#a8efbf}
.notice.bad{border-color:#983f3f;background:#2a1515;color:#ffb0b0}
.good{color:var(--good);font-weight:800}.bad{color:var(--bad);font-weight:800}.muted{color:var(--muted)}
code{background:#282828;padding:2px 5px;border-radius:5px;word-break:break-word}
table{width:100%;border-collapse:collapse}
th,td{padding:9px 10px;border-bottom:1px solid #333;text-align:left;vertical-align:top}
th{color:var(--gold)}
.buttons{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
button{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;cursor:pointer;font-size:14px}
.quarantine{background:#b06a18}.restore{background:#276fca}
button:disabled{opacity:.38;cursor:not-allowed}
ul{line-height:1.5}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Vendor Quarantine</h1>
    <div class="muted">v001 · generated 9/20/2026 2:25:01 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>What will move</h2>
        <p>
            Source: <code><?php echo h($source); ?></code><br>
            Quarantine: <code><?php echo h($destination); ?></code>
        </p>
        <p class="muted">
            This is a same-filesystem directory rename. No vendor files are deleted or rewritten.
        </p>
    </div>

    <div class="card">
        <h2>Current state</h2>
        <table>
            <tr><th>Check</th><th>Status</th><th>Detail</th></tr>
            <tr>
                <td>Root /vendor</td>
                <td class="<?php echo $sourceExists ? 'good' : 'muted'; ?>"><?php echo $sourceExists ? 'PRESENT' : 'ABSENT'; ?></td>
                <td><code><?php echo h($source); ?></code></td>
            </tr>
            <tr>
                <td>Quarantine copy</td>
                <td class="<?php echo $destExists ? 'good' : 'muted'; ?>"><?php echo $destExists ? 'PRESENT' : 'ABSENT'; ?></td>
                <td><code><?php echo h($destination); ?></code></td>
            </tr>
            <tr>
                <td>Inventory</td>
                <td><?php echo (int)$inventory['files']; ?> files / <?php echo (int)$inventory['dirs']; ?> folders</td>
                <td><?php echo h(fmt_bytes((int)$inventory['bytes'])); ?></td>
            </tr>
        </table>

        <div class="buttons">
            <form method="post" onsubmit="return confirm('Move root /vendor into quarantine? Nothing will be deleted.');">
                <input type="hidden" name="action" value="quarantine">
                <button class="quarantine" type="submit" <?php echo $canQuarantine ? '' : 'disabled'; ?>>Quarantine Root /vendor</button>
            </form>

            <form method="post" onsubmit="return confirm('Restore root /vendor from quarantine?');">
                <input type="hidden" name="action" value="restore">
                <button class="restore" type="submit" <?php echo $canRestore ? '' : 'disabled'; ?>>Restore Root /vendor</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Why this is ready for quarantine</h2>
        <ul>
            <li>The targeted production audit found <strong>zero PHP include/require statements</strong> among the nine remaining review files that referenced vendor.</li>
            <li><code>cpass.php</code>, <code>fpass.php</code>, <code>register.php</code>, <code>verify.php</code>, and <code>js/jotform.js</code> point to <code>js/vendor/...</code>, which is a separate JavaScript directory.</li>
            <li><code>weekly_standings.php</code> and its v065 copy only mention PhpSpreadsheet in comments.</li>
            <li><code>team_chart.php</code> only mentions the old dependency in comments/changelog after v023.</li>
            <li><code>composer.json</code> is a dependency manifest, not a runtime include.</li>
        </ul>
    </div>

    <div class="card">
        <h2>Not touched</h2>
        <ul>
            <li>WordPress core, plugins, themes, and their own vendor folders.</li>
            <li><code>composer.json</code>.</li>
            <li>Any PHP, JS, database, scheduler, cron, or configuration file.</li>
        </ul>
    </div>
</div>
</body>
</html>
