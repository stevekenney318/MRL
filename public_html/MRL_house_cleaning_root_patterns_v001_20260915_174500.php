<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * House Cleaning Root Pattern Add-On
 *
 * VERSION: v001
 * CREATED: 9/15/2026 5:45:00 pm EDT
 *
 * PURPOSE
 * -------
 * Safely add remaining root-level housekeeping artifacts to the existing
 * quarantine folder:
 *   To_Be_Deleted_20260915_172432/
 *
 * This utility discovers root-level files matching:
 *   install_*
 *   fix_*
 *   mrl_*
 *
 * SAFETY DESIGN
 * -------------
 * - No permanent deletion.
 * - Existing quarantine must already exist.
 * - Existing quarantine rollback manifest must load successfully.
 * - Current MRL house-cleaning utilities are always excluded.
 * - install_* and fix_* candidates are preselected.
 * - mrl_* candidates are preselected ONLY when their names contain a strong
 *   disposable marker such as installer, diagnostic, check, utility, or package.
 * - Other mrl_* candidates remain visible but UNCHECKED.
 * - User can review/uncheck any candidate before moving.
 * - Exact size + mtime are captured during page load and reverified on submit.
 * - Existing rollback manifest is extended, not replaced.
 * - No database writes.
 */

date_default_timezone_set('America/New_York');

const MRL_TOOL_VERSION = 'v001';
const MRL_QUARANTINE_DIR = 'To_Be_Deleted_20260915_172432';
const MRL_STATE_FILE = '_mrl_quarantine_manifest.json';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function full_path(string $root, string $relative): string {
    return rtrim($root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function fmt_bytes(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    $units = ['KB','MB','GB','TB'];
    $v = (float)$bytes;
    foreach ($units as $u) {
        $v /= 1024;
        if ($v < 1024 || $u === 'TB') {
            return number_format($v, $v >= 100 ? 0 : ($v >= 10 ? 1 : 2)) . ' ' . $u;
        }
    }
    return $bytes . ' B';
}

function load_state(string $root): ?array {
    $file = full_path($root, MRL_QUARANTINE_DIR . '/' . MRL_STATE_FILE);
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function save_state(string $root, array $state): bool {
    $file = full_path($root, MRL_QUARANTINE_DIR . '/' . MRL_STATE_FILE);
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return $json !== false && @file_put_contents($file, $json . "\n", LOCK_EX) !== false;
}

function classify_candidate(string $name): ?array {
    $lower = strtolower($name);

    // Never touch any current/older house-cleaning utilities with this pattern tool.
    if (preg_match('/^mrl_house_cleaning_/i', $name)) {
        return null;
    }

    if (preg_match('/^install_.+/i', $name)) {
        return [
            'group' => 'install_*',
            'default' => true,
            'reason' => 'Root deployment installer pattern.'
        ];
    }

    if (preg_match('/^fix_.+/i', $name)) {
        return [
            'group' => 'fix_*',
            'default' => true,
            'reason' => 'Root one-time fix/repair pattern.'
        ];
    }

    if (preg_match('/^mrl_.+/i', $name)) {
        $strong = preg_match('/(?:installer|diagnostic|readiness|check|utility|package|migration|repair|rebuild|test)/i', $name) === 1;

        return [
            'group' => 'mrl_*',
            'default' => $strong,
            'reason' => $strong
                ? 'MRL-prefixed artifact with strong temporary/generated marker.'
                : 'MRL-prefixed root file; shown for review but not preselected.'
        ];
    }

    return null;
}

function discover_candidates(string $root): array {
    $items = [];
    $entries = @scandir($root);
    if (!is_array($entries)) return $items;

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;

        $full = $root . DIRECTORY_SEPARATOR . $entry;
        if (!is_file($full) || is_link($full)) continue;

        $class = classify_candidate($entry);
        if ($class === null) continue;

        $size = @filesize($full);
        $mtime = @filemtime($full);

        $items[$entry] = [
            'name' => $entry,
            'size' => $size === false ? -1 : (int)$size,
            'mtime' => $mtime === false ? -1 : (int)$mtime,
            'group' => $class['group'],
            'default' => (bool)$class['default'],
            'reason' => $class['reason'],
        ];
    }

    uksort($items, 'strnatcasecmp');
    return $items;
}

function preflight(string $root, array $items): array {
    $rows = [];
    $fail = 0;

    $q = full_path($root, MRL_QUARANTINE_DIR);
    $state = load_state($root);

    $rows[] = [
        'check' => 'Production root',
        'status' => is_dir($root) ? 'PASS' : 'FAIL',
        'detail' => $root,
    ];
    if (!is_dir($root)) $fail++;

    $rows[] = [
        'check' => 'Existing quarantine',
        'status' => is_dir($q) ? 'PASS' : 'FAIL',
        'detail' => is_dir($q) ? MRL_QUARANTINE_DIR . ' exists.' : MRL_QUARANTINE_DIR . ' is missing.',
    ];
    if (!is_dir($q)) $fail++;

    $rows[] = [
        'check' => 'Rollback manifest',
        'status' => $state ? 'PASS' : 'FAIL',
        'detail' => $state ? 'Existing quarantine state loaded successfully.' : 'Rollback state missing or invalid.',
    ];
    if (!$state) $fail++;

    $groups = ['install_*' => 0, 'fix_*' => 0, 'mrl_*' => 0];
    foreach ($items as $item) {
        if (isset($groups[$item['group']])) $groups[$item['group']]++;
    }

    foreach ($groups as $group => $count) {
        $rows[] = [
            'check' => 'Candidate discovery',
            'status' => 'INFO',
            'detail' => $group . ': ' . number_format($count) . ' root files.',
        ];
    }

    return ['ok' => $fail === 0, 'rows' => $rows];
}

function move_selected(string $root, array $discovered, array $selected): array {
    $pre = preflight($root, $discovered);
    if (!$pre['ok']) {
        return ['ok' => false, 'message' => 'Move blocked: preflight is not green.', 'errors' => [], 'moved' => []];
    }

    $state = load_state($root);
    if (!$state) {
        return ['ok' => false, 'message' => 'Could not load existing rollback manifest.', 'errors' => [], 'moved' => []];
    }

    $selected = array_values(array_unique(array_map('strval', $selected)));
    if (!$selected) {
        return ['ok' => false, 'message' => 'No files were selected.', 'errors' => [], 'moved' => []];
    }

    $q = full_path($root, MRL_QUARANTINE_DIR);
    $moved = [];
    $errors = [];
    $movedBytes = 0;

    foreach ($selected as $name) {
        // Exact discovered-set membership is mandatory.
        if (!isset($discovered[$name])) {
            $errors[] = 'Safety stop: selected file is outside discovered candidate set: ' . $name;
            break;
        }

        if (strpos($name, '/') !== false || strpos($name, '\\') !== false) {
            $errors[] = 'Safety stop: non-root path submitted: ' . $name;
            break;
        }

        $item = $discovered[$name];
        $src = full_path($root, $name);
        $dest = $q . DIRECTORY_SEPARATOR . $name;

        if (!is_file($src)) {
            $errors[] = 'Source missing: ' . $name;
            break;
        }

        if (file_exists($dest)) {
            $errors[] = 'Destination already exists in quarantine: ' . $name;
            break;
        }

        $size = @filesize($src);
        $mtime = @filemtime($src);
        $size = $size === false ? -1 : (int)$size;
        $mtime = $mtime === false ? -1 : (int)$mtime;

        if ($size !== (int)$item['size'] || $mtime !== (int)$item['mtime']) {
            $errors[] = 'Baseline changed after page load: ' . $name;
            break;
        }

        if (!@rename($src, $dest)) {
            $errors[] = 'rename() failed: ' . $name;
            break;
        }

        clearstatcache(true, $dest);
        $dSize = @filesize($dest);
        $dMtime = @filemtime($dest);
        $dSize = $dSize === false ? -1 : (int)$dSize;
        $dMtime = $dMtime === false ? -1 : (int)$dMtime;

        if ($dSize !== $size || $dMtime !== $mtime) {
            $errors[] = 'Post-move metadata verification failed: ' . $name;
            $moved[] = $name;
            break;
        }

        $moved[] = $name;
        $movedBytes += $size;
    }

    if ($errors) {
        $rbErrors = [];

        foreach (array_reverse($moved) as $name) {
            $src = $q . DIRECTORY_SEPARATOR . $name;
            $dest = full_path($root, $name);
            if (!@rename($src, $dest)) {
                $rbErrors[] = 'Could not auto-restore ' . $name;
            }
        }

        return [
            'ok' => false,
            'message' => $rbErrors
                ? 'Move failed and automatic rollback was incomplete.'
                : 'Move failed; this batch was automatically restored.',
            'errors' => array_merge($errors, $rbErrors),
            'moved' => [],
        ];
    }

    $existingPaths = isset($state['moved_paths']) && is_array($state['moved_paths']) ? $state['moved_paths'] : [];

    foreach ($moved as $name) {
        if (!in_array($name, $existingPaths, true)) {
            $existingPaths[] = $name;
        }
    }

    $state['moved_paths'] = $existingPaths;
    $state['moved_count'] = count($existingPaths);
    $state['moved_bytes'] = (int)($state['moved_bytes'] ?? 0) + $movedBytes;
    $state['last_pattern_addon'] = [
        'tool' => 'MRL House Cleaning Root Pattern Add-On',
        'version' => MRL_TOOL_VERSION,
        'added_at' => date(DATE_ATOM),
        'added_count' => count($moved),
        'added_bytes' => $movedBytes,
        'added_paths' => $moved,
    ];

    if (!save_state($root, $state)) {
        return [
            'ok' => false,
            'message' => 'Files moved, but rollback manifest update failed. Do NOT delete the quarantine folder.',
            'errors' => ['Could not update ' . MRL_STATE_FILE],
            'moved' => $moved,
        ];
    }

    return [
        'ok' => true,
        'message' => 'Selected root cleanup files were moved to quarantine and verified.',
        'errors' => [],
        'moved' => $moved,
        'bytes' => $movedBytes,
    ];
}

$candidates = discover_candidates($root);
$pre = preflight($root, $candidates);

$action = (string)($_POST['action'] ?? '');
$result = null;

if ($action === 'move') {
    $selected = isset($_POST['selected']) && is_array($_POST['selected']) ? $_POST['selected'] : [];
    $result = move_selected($root, $candidates, $selected);
    $candidates = discover_candidates($root);
    $pre = preflight($root, $candidates);
}

$defaultCount = 0;
$defaultBytes = 0;
foreach ($candidates as $item) {
    if ($item['default']) {
        $defaultCount++;
        $defaultBytes += max(0, (int)$item['size']);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL House Cleaning — Root Patterns</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1180px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.banner{padding:11px 13px;margin-bottom:11px;border:1px solid #3f8bc2;border-radius:10px;background:#15354d;color:#e8f5ff;font-weight:800}
.small{font-size:12px;color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.card{padding:11px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:22px;font-weight:800}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
.status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
.pass{background:#17613a;border:1px solid #55db8b;color:#e8fff1}
.info{background:#4a3813;border:1px solid #d8aa49;color:#ffe6a7}
.fail{background:#5b2323;border:1px solid #e77a7a;color:#ffe4e4}
.group{font-weight:800;color:#bfe3ff}
.actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
button{min-height:38px;padding:8px 14px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.move{background:#2f7f53}.toggle{background:#2c6f9e}.refresh{background:#725d2e}
button:disabled{background:#5a5f5d;color:#b9b9b9;cursor:not-allowed;opacity:.7}
.result-ok{border-color:#2f9a61;background:#103b27}
.result-bad{border-color:#a65353;background:#3d1d1d}
.filecheck{transform:scale(1.15)}
code{color:#f8d89a}
</style>
</head>
<body>
<div class="wrap">

<h1>MRL House Cleaning — Root Pattern Add-On</h1>

<div class="banner">
Discovers root-level <code>install_*</code>, <code>fix_*</code>, and <code>mrl_*</code> files and moves only the files you leave checked into the existing quarantine.
</div>

<div class="panel">
<h2>Selection Rules</h2>
<ul>
<li><code>install_*</code> — preselected.</li>
<li><code>fix_*</code> — preselected.</li>
<li><code>mrl_*</code> — preselected only when the filename looks clearly generated/temporary: installer, diagnostic, check, readiness, utility, package, migration, repair, rebuild, or test.</li>
<li>Other <code>mrl_*</code> files remain visible but unchecked.</li>
<li>All <code>MRL_house_cleaning_*</code> utilities are excluded automatically.</li>
</ul>
</div>

<div class="panel">
<h2>At a Glance</h2>
<div class="grid">
<div class="card"><span class="small">Candidates found</span><span class="value"><?php echo number_format(count($candidates)); ?></span></div>
<div class="card"><span class="small">Preselected</span><span class="value"><?php echo number_format($defaultCount); ?></span></div>
<div class="card"><span class="small">Preselected size</span><span class="value"><?php echo h(fmt_bytes($defaultBytes)); ?></span></div>
</div>
</div>

<?php if ($result): ?>
<div class="panel <?php echo !empty($result['ok']) ? 'result-ok' : 'result-bad'; ?>">
<h2>Result</h2>
<p><strong><?php echo !empty($result['ok']) ? 'PASS' : 'ATTENTION'; ?></strong> — <?php echo h($result['message']); ?></p>
<?php if (!empty($result['moved'])): ?>
<p><?php echo number_format(count($result['moved'])); ?> files moved<?php echo isset($result['bytes']) ? ' / ' . h(fmt_bytes((int)$result['bytes'])) : ''; ?>.</p>
<?php endif; ?>
<?php if (!empty($result['errors'])): ?><ul><?php foreach ($result['errors'] as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>
<?php endif; ?>

<div class="panel">
<h2>Preflight / Result</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($pre['rows'] as $row):
$cls=$row['status']==='PASS'?'pass':($row['status']==='FAIL'?'fail':'info');
?>
<tr><td><?php echo h($row['check']); ?></td><td><span class="status <?php echo h($cls); ?>"><?php echo h($row['status']); ?></span></td><td><?php echo h($row['detail']); ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Root Candidates</h2>

<form method="post" id="moveForm">
<input type="hidden" name="action" value="move">

<div class="actions" style="margin-bottom:10px">
<button type="button" class="toggle" onclick="setAll(true)">Check All</button>
<button type="button" class="toggle" onclick="setAll(false)">Uncheck All</button>
<button type="button" class="toggle" onclick="restoreDefaults()">Restore Safe Defaults</button>
</div>

<table>
<tr><th>Move?</th><th>Group</th><th>File</th><th>Size</th><th>Why</th></tr>
<?php foreach ($candidates as $name => $item): ?>
<tr>
<td><input class="filecheck" type="checkbox" name="selected[]" value="<?php echo h($name); ?>" data-default="<?php echo $item['default'] ? '1' : '0'; ?>" <?php echo $item['default'] ? 'checked' : ''; ?>></td>
<td class="group"><?php echo h($item['group']); ?></td>
<td><code><?php echo h($name); ?></code></td>
<td><?php echo h(fmt_bytes(max(0,(int)$item['size']))); ?></td>
<td class="small"><?php echo h($item['reason']); ?></td>
</tr>
<?php endforeach; ?>
</table>

<div class="actions" style="margin-top:12px">
<button class="move" type="submit" <?php echo $pre['ok'] ? '' : 'disabled'; ?>>Move Checked Files to Quarantine</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh / Rescan</button>
</div>

<p class="small">Nothing is permanently deleted. The existing rollback manifest is extended with the files moved by this utility.</p>
</form>
</div>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/15/2026 5:45:00 pm EDT
</div>

</div>

<script>
function setAll(value){
    document.querySelectorAll('.filecheck').forEach(function(cb){ cb.checked=value; });
}
function restoreDefaults(){
    document.querySelectorAll('.filecheck').forEach(function(cb){ cb.checked=cb.getAttribute('data-default')==='1'; });
}
</script>
</body>
</html>
