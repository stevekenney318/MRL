<?php
declare(strict_types=1);

/**
 * MRL_delete_testphp8_utility.php
 *
 * VERSION: v001
 * CREATED: 9/7/2026 12:18:29 pm ET
 *
 * PURPOSE:
 * Permanently deletes ONLY:
 * /home/u809830586/domains/manliusracingleague.com/public_html/testphp8
 *
 * SAFETY:
 * - Hard-coded exact target.
 * - Exact parent/realpath checks.
 * - Refuses to run from inside testphp8.
 * - Shows file/folder inventory before deletion.
 * - Requires exact typed confirmation: DELETE TESTPHP8
 * - Symlinks are unlinked, never traversed.
 *
 * Upload this utility to /public_html, not inside /public_html/testphp8.
 * Delete this utility after the cleanup is complete.
 */

date_default_timezone_set('America/New_York');

const MRL_UTILITY_VERSION = 'v001';
const MRL_EXPECTED_PARENT = '/home/u809830586/domains/manliusracingleague.com/public_html';
const MRL_TARGET = '/home/u809830586/domains/manliusracingleague.com/public_html/testphp8';
const MRL_CONFIRM_PHRASE = 'DELETE TESTPHP8';

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function human_bytes(int $bytes): string {
    $units = ['B','KB','MB','GB','TB'];
    $size = (float)$bytes;
    $i = 0;
    while ($size >= 1024 && $i < count($units)-1) {
        $size /= 1024;
        $i++;
    }
    return ($i === 0 ? number_format($size, 0) : number_format($size, 2)) . ' ' . $units[$i];
}

function preflight(): array {
    $exists = file_exists(MRL_TARGET) || is_link(MRL_TARGET);
    $real = $exists ? realpath(MRL_TARGET) : false;
    $parent = realpath(dirname(MRL_TARGET));

    $checks = [
        ['Target path is exactly expected', MRL_TARGET === MRL_EXPECTED_PARENT . '/testphp8', MRL_TARGET],
        ['Parent resolves exactly', $parent === MRL_EXPECTED_PARENT, (string)$parent],
        ['Target exists', $exists, MRL_TARGET],
        ['Target is a directory', is_dir(MRL_TARGET), ''],
        ['Target is not a symlink', !is_link(MRL_TARGET), ''],
        ['Resolved target is exact expected path', $real === MRL_TARGET, (string)$real],
        ['Utility is not running inside target', strpos(__DIR__, MRL_TARGET . '/') !== 0 && __DIR__ !== MRL_TARGET, __DIR__],
        ['Parent directory is writable', is_writable(MRL_EXPECTED_PARENT), MRL_EXPECTED_PARENT],
    ];

    $ok = true;
    foreach ($checks as $c) {
        if (!$c[1]) { $ok = false; break; }
    }

    return ['ok'=>$ok, 'checks'=>$checks];
}

function inventory(string $target): array {
    $files = 0; $dirs = 0; $links = 0; $bytes = 0; $errors = [];

    if (!is_dir($target)) {
        return compact('files','dirs','links','bytes','errors');
    }

    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($it as $item) {
            $p = $item->getPathname();

            if (is_link($p)) {
                $links++;
                continue;
            }

            if ($item->isDir()) {
                $dirs++;
            } elseif ($item->isFile()) {
                $files++;
                $bytes += (int)$item->getSize();
            }
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    return compact('files','dirs','links','bytes','errors');
}

function delete_tree(string $target): array {
    $deletedFiles = 0; $deletedDirs = 0; $deletedLinks = 0; $failures = [];

    if (!is_dir($target)) {
        return [
            'ok'=>false,
            'deleted_files'=>0,
            'deleted_dirs'=>0,
            'deleted_links'=>0,
            'failures'=>['Target directory no longer exists.']
        ];
    }

    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($it as $item) {
            $p = $item->getPathname();

            if (strncmp($p, $target . DIRECTORY_SEPARATOR, strlen($target)+1) !== 0) {
                $failures[] = 'Safety stop: path escaped target: ' . $p;
                continue;
            }

            if (is_link($p)) {
                if (@unlink($p)) $deletedLinks++;
                else $failures[] = 'Unable to remove symlink: ' . $p;
                continue;
            }

            if ($item->isDir()) {
                @chmod($p, 0755);
                if (@rmdir($p)) $deletedDirs++;
                else $failures[] = 'Unable to remove directory: ' . $p;
            } else {
                @chmod($p, 0644);
                if (@unlink($p)) $deletedFiles++;
                else $failures[] = 'Unable to remove file: ' . $p;
            }
        }

        @chmod($target, 0755);
        if (@rmdir($target)) $deletedDirs++;
        else $failures[] = 'Unable to remove final target directory.';
    } catch (Throwable $e) {
        $failures[] = $e->getMessage();
    }

    $gone = !file_exists($target) && !is_link($target);

    return [
        'ok'=>$gone && count($failures)===0,
        'deleted_files'=>$deletedFiles,
        'deleted_dirs'=>$deletedDirs,
        'deleted_links'=>$deletedLinks,
        'failures'=>$failures
    ];
}

$pre = preflight();
$inv = inventory(MRL_TARGET);
$result = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = trim((string)($_POST['confirm_phrase'] ?? ''));
    $pre = preflight();

    if (!$pre['ok']) {
        $message = 'DELETE BLOCKED: one or more safety checks failed.';
    } elseif ($confirm !== MRL_CONFIRM_PHRASE) {
        $message = 'DELETE BLOCKED: confirmation phrase did not match exactly.';
    } else {
        $result = delete_tree(MRL_TARGET);
        $message = $result['ok']
            ? 'SUCCESS: /public_html/testphp8 has been permanently deleted.'
            : 'DELETE INCOMPLETE: some items could not be removed.';
    }

    $pre = preflight();
    $inv = inventory(MRL_TARGET);
}

$existsNow = file_exists(MRL_TARGET) || is_link(MRL_TARGET);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL testPHP8 Delete Utility</title>
<style>
:root{color-scheme:dark;--bg:#101312;--panel:#1b201f;--border:#46504d;--text:#eee9df;--muted:#b8b7b0;--gold:#f1c97f;--green:#158547;--red:#b13b3b}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1050px,94%);margin:18px auto 34px}
h1{margin:0 0 14px;color:var(--gold);font-size:29px;line-height:1.1}
h2{margin:0 0 10px;color:var(--gold);font-size:19px}
.panel{margin:0 0 12px;padding:13px 15px;border:1px solid var(--border);border-radius:12px;background:var(--panel)}
.notice{margin:0 0 12px;padding:11px 14px;border-radius:10px;font-weight:700}
.notice.good{background:#103b27;border:1px solid #2f9b63}
.notice.bad{background:#4b1d1d;border:1px solid #c04b4b}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 9px;border-bottom:1px solid #353c3a;text-align:left;vertical-align:top}
th{color:var(--gold)}
.pass{color:#5ee58e;font-weight:800}.fail{color:#ff7b7b;font-weight:800}
.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}
.stat{padding:10px;border:1px solid #3a4240;border-radius:9px;background:#131716}
.stat strong{display:block;color:var(--gold);font-size:20px}
input[type=text]{width:260px;max-width:100%;padding:9px 10px;border:1px solid #666;border-radius:7px;background:#111;color:#fff;font:14px Consolas,"Courier New",monospace}
button{margin-left:8px;padding:9px 14px;border:0;border-radius:8px;background:var(--red);color:#fff;font-weight:800;cursor:pointer}
button:disabled{opacity:.45;cursor:not-allowed}
ul{margin:8px 0 0;padding-left:22px}
@media(max-width:760px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL testPHP8 Delete Utility</h1>

<?php if ($message !== ''): ?>
<div class="notice <?php echo ($result !== null && $result['ok']) ? 'good' : 'bad'; ?>"><?php echo h($message); ?></div>
<?php endif; ?>

<?php if (!$existsNow && $result === null): ?>
<div class="notice good">Target is already gone. Nothing to delete.</div>
<?php endif; ?>

<div class="panel">
<h2>Hard-coded target</h2>
<div class="mono"><?php echo h(MRL_TARGET); ?></div>
<div class="small" style="margin-top:6px">This utility cannot be pointed at another directory.</div>
</div>

<div class="panel">
<h2>Preflight</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($pre['checks'] as $c): ?>
<tr>
<td><?php echo h((string)$c[0]); ?></td>
<td class="<?php echo $c[1] ? 'pass' : 'fail'; ?>"><?php echo $c[1] ? 'PASS' : 'FAIL'; ?></td>
<td class="small mono"><?php echo h((string)$c[2]); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Current target inventory</h2>
<div class="grid">
<div class="stat"><strong><?php echo number_format((int)$inv['files']); ?></strong>Files</div>
<div class="stat"><strong><?php echo number_format((int)$inv['dirs']); ?></strong>Subdirectories</div>
<div class="stat"><strong><?php echo number_format((int)$inv['links']); ?></strong>Symlinks</div>
<div class="stat"><strong><?php echo h(human_bytes((int)$inv['bytes'])); ?></strong>Total file size</div>
</div>
</div>

<?php if ($existsNow): ?>
<div class="panel">
<h2>Permanent delete</h2>
<p style="margin:0 0 10px">Type <span class="mono"><strong><?php echo h(MRL_CONFIRM_PHRASE); ?></strong></span> and click the red button.</p>
<form method="post">
<input type="text" name="confirm_phrase" autocomplete="off" spellcheck="false" placeholder="<?php echo h(MRL_CONFIRM_PHRASE); ?>">
<button type="submit" <?php echo $pre['ok'] ? '' : 'disabled'; ?>>Permanently Delete testphp8</button>
</form>
</div>
<?php endif; ?>

<?php if (is_array($result)): ?>
<div class="panel">
<h2>Delete result</h2>
<table>
<tr><th>Files deleted</th><td><?php echo number_format((int)$result['deleted_files']); ?></td></tr>
<tr><th>Directories deleted</th><td><?php echo number_format((int)$result['deleted_dirs']); ?></td></tr>
<tr><th>Symlinks deleted</th><td><?php echo number_format((int)$result['deleted_links']); ?></td></tr>
<tr><th>Target still exists</th><td><?php echo $existsNow ? 'YES' : 'NO'; ?></td></tr>
</table>
<?php if (!empty($result['failures'])): ?>
<ul>
<?php foreach ($result['failures'] as $f): ?>
<li class="small mono"><?php echo h((string)$f); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: <?php echo h(MRL_UTILITY_VERSION); ?><br>
After a successful delete, remove this utility file from /public_html.
</div>
</div>
</body>
</html>
