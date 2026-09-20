<?php
declare(strict_types=1);

/**
 * mrl_composer_files_quarantine_v001_20260920_023428pm.php
 *
 * VERSION: v001
 * GENERATED: 9/20/2026 2:34:28 pm ET
 *
 * PURPOSE:
 * - Reversibly quarantine root composer.json and composer.lock.
 * - Keeps the pair together under /public_html/_quarantine/.
 * - Provides restore.
 * - Does NOT touch root /vendor, WordPress vendor trees, or any PHP code.
 */

date_default_timezone_set('America/New_York');

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');

$sourceJson = $docRoot . '/composer.json';
$sourceLock = $docRoot . '/composer.lock';

$quarantineRoot = $docRoot . '/_quarantine';
$quarantineDir = $quarantineRoot . '/composer_20260920_023428pm';
$destJson = $quarantineDir . '/composer.json';
$destLock = $quarantineDir . '/composer.lock';

$action = (string)($_POST['action'] ?? '');
$message = '';
$messageClass = 'info';

function file_state(string $source, string $dest): array
{
    return [
        'source' => is_file($source),
        'dest' => is_file($dest),
    ];
}

$stateJson = file_state($sourceJson, $destJson);
$stateLock = file_state($sourceLock, $destLock);

$canQuarantine =
    is_file($sourceJson)
    && is_file($sourceLock)
    && !is_file($destJson)
    && !is_file($destLock)
    && is_writable($docRoot);

$canRestore =
    !is_file($sourceJson)
    && !is_file($sourceLock)
    && is_file($destJson)
    && is_file($destLock)
    && is_writable($docRoot);

if ($action === 'quarantine') {
    if (!$canQuarantine) {
        $message = 'Quarantine blocked: preflight conditions are not satisfied.';
        $messageClass = 'bad';
    } else {
        if (!is_dir($quarantineDir) && !@mkdir($quarantineDir, 0755, true) && !is_dir($quarantineDir)) {
            $message = 'Quarantine failed: could not create quarantine directory.';
            $messageClass = 'bad';
        } else {
            $movedJson = @rename($sourceJson, $destJson);

            if (!$movedJson) {
                $message = 'Quarantine failed: composer.json could not be moved.';
                $messageClass = 'bad';
            } else {
                $movedLock = @rename($sourceLock, $destLock);

                if (!$movedLock) {
                    @rename($destJson, $sourceJson);
                    $message = 'Quarantine failed: composer.lock could not be moved. composer.json was automatically restored.';
                    $messageClass = 'bad';
                } else {
                    $message = 'PASS — composer.json and composer.lock moved into quarantine. Nothing was deleted.';
                    $messageClass = 'good';
                }
            }
        }
    }
}

if ($action === 'restore') {
    if (!$canRestore) {
        $message = 'Restore blocked: preflight conditions are not satisfied.';
        $messageClass = 'bad';
    } else {
        $restoredJson = @rename($destJson, $sourceJson);

        if (!$restoredJson) {
            $message = 'Restore failed: composer.json could not be restored.';
            $messageClass = 'bad';
        } else {
            $restoredLock = @rename($destLock, $sourceLock);

            if (!$restoredLock) {
                @rename($sourceJson, $destJson);
                $message = 'Restore failed: composer.lock could not be restored. composer.json was moved back into quarantine.';
                $messageClass = 'bad';
            } else {
                $message = 'PASS — composer.json and composer.lock restored from quarantine.';
                $messageClass = 'good';
            }
        }
    }
}

$stateJson = file_state($sourceJson, $destJson);
$stateLock = file_state($sourceLock, $destLock);

$canQuarantine =
    is_file($sourceJson)
    && is_file($sourceLock)
    && !is_file($destJson)
    && !is_file($destLock)
    && is_writable($docRoot);

$canRestore =
    !is_file($sourceJson)
    && !is_file($sourceLock)
    && is_file($destJson)
    && is_file($destLock)
    && is_writable($docRoot);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Composer Files Quarantine</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:1000px;margin:0 auto}
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
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Composer Files Quarantine</h1>
    <div class="muted">v001 · generated 9/20/2026 2:34:28 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Files</h2>
        <table>
            <tr><th>File</th><th>Root</th><th>Quarantine</th></tr>
            <tr>
                <td><code>composer.json</code></td>
                <td class="<?php echo $stateJson['source'] ? 'good' : 'muted'; ?>"><?php echo $stateJson['source'] ? 'PRESENT' : 'ABSENT'; ?></td>
                <td class="<?php echo $stateJson['dest'] ? 'good' : 'muted'; ?>"><?php echo $stateJson['dest'] ? 'PRESENT' : 'ABSENT'; ?></td>
            </tr>
            <tr>
                <td><code>composer.lock</code></td>
                <td class="<?php echo $stateLock['source'] ? 'good' : 'muted'; ?>"><?php echo $stateLock['source'] ? 'PRESENT' : 'ABSENT'; ?></td>
                <td class="<?php echo $stateLock['dest'] ? 'good' : 'muted'; ?>"><?php echo $stateLock['dest'] ? 'PRESENT' : 'ABSENT'; ?></td>
            </tr>
        </table>

        <p>
            Quarantine folder:
            <code><?php echo h($quarantineDir); ?></code>
        </p>

        <div class="buttons">
            <form method="post" onsubmit="return confirm('Move composer.json and composer.lock into quarantine? Nothing will be deleted.');">
                <input type="hidden" name="action" value="quarantine">
                <button class="quarantine" type="submit" <?php echo $canQuarantine ? '' : 'disabled'; ?>>Quarantine Composer Files</button>
            </form>

            <form method="post" onsubmit="return confirm('Restore composer.json and composer.lock from quarantine?');">
                <input type="hidden" name="action" value="restore">
                <button class="restore" type="submit" <?php echo $canRestore ? '' : 'disabled'; ?>>Restore Composer Files</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Not touched</h2>
        <p class="muted">
            Root <code>/vendor</code>, WordPress vendor folders, PHP files, JS files, database, scheduler, cron, and configuration are untouched.
        </p>
    </div>
</div>
</body>
</html>
