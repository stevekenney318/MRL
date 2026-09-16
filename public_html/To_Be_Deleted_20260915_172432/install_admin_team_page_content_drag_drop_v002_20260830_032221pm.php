<?php
declare(strict_types=1);

/**
 * install_admin_team_page_content_drag_drop.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/30/2026 3:22:21 pm
 *
 * PURPOSE:
 *   Update /mrl_team/admin_team_page_content.php from v005 to v006.
 *
 * CHANGE:
 *   - Replace ↑ / ↓ reorder buttons with a ⋮⋮ drag handle.
 *   - Dragging starts only from the handle.
 *   - Preserve current JSON schema and save behavior.
 *
 * v002:
 *   - FIX: Reworked the JavaScript preflight/patch to use stable start/end
 *     markers rather than one exact multi-line string.
 *
 * BASELINE:
 *   GitHub blob SHA for verified v005:
 *   6bc5c126be939ff20887aa8067dd773f1736477c
 *
 * NO DATABASE CHANGES.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';

$uid = (int)($_SESSION['userSession'] ?? 0);
if (!isAdmin($uid)) {
    http_response_code(403);
    exit('Admin access required.');
}

function dd_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function dd_lf(string $s): string
{
    return str_replace(["\r\n", "\r"], "\n", $s);
}

function dd_blob_sha(string $content): string
{
    return sha1('blob ' . strlen($content) . "\0" . $content);
}

$target = $_SERVER['DOCUMENT_ROOT'] . '/mrl_team/admin_team_page_content.php';
$expectedSha = '6bc5c126be939ff20887aa8067dd773f1736477c';

$raw = is_file($target) ? file_get_contents($target) : false;
$content = is_string($raw) ? dd_lf($raw) : '';
$currentSha = $content !== '' ? dd_blob_sha($content) : '';

$oldHeader = " * VERSION: v005\n * LAST MODIFIED: 8/28/2026 3:09:01 pm";
$newHeader = " * VERSION: v006\n * LAST MODIFIED: 8/30/2026 3:22:21 pm";

$oldChangelog = " * CHANGELOG:\n *\n * v005 (8/28/2026 3:09:01 pm)";
$newChangelog =
    " * CHANGELOG:\n *\n" .
    " * v006 (8/30/2026 3:22:21 pm)\n" .
    " * - NEW: Link rows can now be reordered by dragging the ⋮⋮ handle.\n" .
    " * - CHANGE: Removed the ↑ / ↓ reorder buttons in favor of handle-only drag/drop.\n" .
    " * - PRESERVE: Existing panel titles, links, enabled/new-tab/remove controls, Add Link, JSON schema, backups, authentication, and CSRF behavior.\n" .
    " *\n" .
    " * v005 (8/28/2026 3:09:01 pm)";

$oldRow = '        echo \'<td class="order"><button type="button" class="mini" onclick="moveRow(this,-1)">↑</button><button type="button" class="mini" onclick="moveRow(this,1)">↓</button></td>\';';
$newRow = '        echo \'<td class="order"><button type="button" class="drag-handle" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">⋮⋮</button></td>\';';

$oldCss = '.mini{padding:3px 8px;margin:1px;background:#2b2b2b;border-color:#777}.order{width:82px;white-space:nowrap}.message{margin-top:12px;padding:10px;border:1px solid #777;border-radius:8px;color:#efc982}.save{position:sticky;bottom:8px}';
$newCss = '.drag-handle{padding:4px 10px;margin:0;background:#2b2b2b;border-color:#777;color:#ddd;font-size:20px;line-height:1;letter-spacing:-3px;cursor:grab;touch-action:none}.drag-handle:active{cursor:grabbing}.order{width:58px;white-space:nowrap;text-align:center}.dragging{opacity:.45}.drag-over td{box-shadow:inset 0 2px 0 #76cfff}.message{margin-top:12px;padding:10px;border:1px solid #777;border-radius:8px;color:#efc982}.save{position:sticky;bottom:8px}';

$oldHint = '<p>Use ↑ / ↓ to reorder.</p><button type="button" onclick="addRow(\'<?php echo atpc_h($key);?>\')">Add Link</button>';
$newHint = '<p>Drag the ⋮⋮ handle to reorder links.</p><button type="button" onclick="addRow(\'<?php echo atpc_h($key);?>\')">Add Link</button>';

$jsStart = "function moveRow(b,d){";
$jsEnd = "document.querySelectorAll('tbody[data-key]').forEach(renumber);";

$newJs = <<<'JS'
let draggedRow=null;

document.addEventListener('dragstart',e=>{
 const handle=e.target.closest('.drag-handle');
 if(!handle)return;
 draggedRow=handle.closest('tr');
 if(!draggedRow)return;
 draggedRow.classList.add('dragging');
 e.dataTransfer.effectAllowed='move';
 e.dataTransfer.setData('text/plain','mrl-team-link-row');
});

document.addEventListener('dragend',()=>{
 if(draggedRow){
   const tb=draggedRow.parentElement;
   draggedRow.classList.remove('dragging');
   document.querySelectorAll('tr.drag-over').forEach(r=>r.classList.remove('drag-over'));
   renumber(tb);
 }
 draggedRow=null;
});

document.querySelectorAll('tbody[data-key]').forEach(tb=>{
 renumber(tb);

 tb.addEventListener('dragover',e=>{
   if(!draggedRow||draggedRow.parentElement!==tb)return;
   e.preventDefault();
   e.dataTransfer.dropEffect='move';

   const over=e.target.closest('tr');
   if(!over||over===draggedRow)return;

   document.querySelectorAll('tr.drag-over').forEach(r=>r.classList.remove('drag-over'));
   over.classList.add('drag-over');

   const rect=over.getBoundingClientRect();
   const after=e.clientY > rect.top + rect.height/2;
   tb.insertBefore(draggedRow,after?over.nextSibling:over);
 });

 tb.addEventListener('drop',e=>{
   if(!draggedRow||draggedRow.parentElement!==tb)return;
   e.preventDefault();
   document.querySelectorAll('tr.drag-over').forEach(r=>r.classList.remove('drag-over'));
   renumber(tb);
 });
});

function addRow(k){
 const tb=document.getElementById(k+'-rows'),r=tb.insertRow();
 r.innerHTML='<td class="order"><button type="button" class="drag-handle" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">⋮⋮</button></td>'+
 '<td><input data-role="label"></td><td><input data-role="url"></td>'+
 '<td><input data-role="enabled" value="1" type="checkbox" checked></td>'+
 '<td><input data-role="newtab" value="1" type="checkbox" checked></td>'+
 '<td><input data-role="remove" value="1" type="checkbox"></td>';
 renumber(tb);
}
JS;

$jsStartPos = strpos($content, $jsStart);
$jsEndPos = strpos($content, $jsEnd);
$jsBlockFound = ($jsStartPos !== false && $jsEndPos !== false && $jsEndPos > $jsStartPos);

$checks = [
    'target file exists' => is_file($target),
    'GitHub v005 baseline SHA matches' => hash_equals($expectedSha, $currentSha),
    'VERSION v005 present once' => substr_count($content, ' * VERSION: v005') === 1,
    'header patch location found once' => substr_count($content, $oldHeader) === 1,
    'changelog patch location found once' => substr_count($content, $oldChangelog) === 1,
    'old arrow row found once' => substr_count($content, $oldRow) === 1,
    'old reorder CSS found once' => substr_count($content, $oldCss) === 1,
    'old reorder hint found once' => substr_count($content, $oldHint) === 1,
    'old reorder JavaScript block located' => $jsBlockFound,
];

$ready = !in_array(false, $checks, true);

$action = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') ? 'apply' : 'preview';
$attempted = $action === 'apply';
$success = false;
$error = '';
$backupPath = '';

if ($attempted) {
    if (!$ready) {
        $error = 'Apply blocked because the live file does not exactly match the verified v005 baseline.';
    } else {
        try {
            $updated = $content;

            $replacements = [
                [$oldHeader, str_replace('8/30/2026 3:22:21 pm', '8/30/2026 3:22:21 pm', $newHeader), 'header'],
                [$oldChangelog, str_replace('8/30/2026 3:22:21 pm', '8/30/2026 3:22:21 pm', $newChangelog), 'changelog'],
                [$oldRow, $newRow, 'row'],
                [$oldCss, $newCss, 'css'],
                [$oldHint, $newHint, 'hint'],
            ];

            foreach ($replacements as [$old, $new, $name]) {
                $updated = str_replace($old, $new, $updated, $count);
                if ($count !== 1) {
                    throw new RuntimeException('Unexpected replacement count for ' . $name . ': ' . $count);
                }
            }

            $start = strpos($updated, $jsStart);
            $end = strpos($updated, $jsEnd);

            if ($start === false || $end === false || $end <= $start) {
                throw new RuntimeException('Could not locate JavaScript reorder block during Apply.');
            }

            $end += strlen($jsEnd);
            $updated = substr($updated, 0, $start) . $newJs . substr($updated, $end);

            $updated = str_replace('8/30/2026 3:22:21 pm', '8/30/2026 3:22:21 pm', $updated);

            $backupDir = $_SERVER['DOCUMENT_ROOT']
                . '/_migration_backups/team_page_content_drag_drop_20260830_032221pm';

            if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                throw new RuntimeException('Could not create backup directory.');
            }

            $backupPath = $backupDir . '/admin_team_page_content.php';

            if (!copy($target, $backupPath)) {
                throw new RuntimeException('Could not create backup file.');
            }

            if (file_put_contents($target, $updated, LOCK_EX) === false) {
                @copy($backupPath, $target);
                throw new RuntimeException('Could not write updated file.');
            }

            $verifyRaw = file_get_contents($target);
            $verify = is_string($verifyRaw) ? dd_lf($verifyRaw) : '';

            $postOk =
                substr_count($verify, ' * VERSION: v006') === 1
                && strpos($verify, 'class="drag-handle" draggable="true"') !== false
                && strpos($verify, 'Drag the ⋮⋮ handle to reorder links.') !== false
                && strpos($verify, 'onclick="moveRow(this,-1)"') === false
                && strpos($verify, "let draggedRow=null;") !== false;

            if (!$postOk) {
                @copy($backupPath, $target);
                throw new RuntimeException('Postflight verification failed; original v005 was restored.');
            }

            $success = true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$postRaw = is_file($target) ? file_get_contents($target) : '';
$post = is_string($postRaw) ? dd_lf($postRaw) : '';

$installed =
    substr_count($post, ' * VERSION: v006') === 1
    && strpos($post, 'class="drag-handle" draggable="true"') !== false
    && strpos($post, 'Drag the ⋮⋮ handle to reorder links.') !== false
    && strpos($post, "let draggedRow=null;") !== false;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MRL Team Content Drag & Drop Installer</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root{--bg:#101214;--panel:#1d2023;--border:#4b5055;--text:#f0f0f0;--gold:#efc77e;--green:#63e69a;--red:#ff7e7e;--blue:#55c7ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1200px;margin:20px auto}.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:800}
.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}
table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #3a3e42;text-align:left}th{color:#ffe0a0}
.pass{color:var(--green);font-weight:800}.fail{color:var(--red);font-weight:800}code{color:var(--blue)}
.btn{padding:11px 18px;border-radius:8px;font-weight:800;cursor:pointer}.apply{background:#16894b;color:#fff;border:1px solid #4be388}
.demo{font-size:26px;letter-spacing:-4px;vertical-align:middle}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>Manage Team Page Content — Drag & Drop Reordering</h1>
<p><strong>Installer:</strong> v002 &nbsp; | &nbsp; <strong>Generated:</strong> 8/30/2026 3:22:21 pm America/New_York</p>

<?php if ($attempted && $success): ?>
<div class="banner ok">INSTALL COMPLETE — admin_team_page_content.php is now v006 and postflight passed.</div>
<?php elseif ($attempted): ?>
<div class="banner bad">INSTALL NOT APPLIED — <?php echo dd_h($error); ?></div>
<?php elseif ($installed): ?>
<div class="banner ok">DRAG & DROP UPDATE ALREADY INSTALLED.</div>
<?php elseif ($ready): ?>
<div class="banner ok">PREVIEW PASS — exact GitHub v005 baseline and all patch locations verified.</div>
<?php else: ?>
<div class="banner bad">PREVIEW BLOCKED — live file differs from the verified v005 baseline.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>What Changes</h2>
<p>The current ↑ / ↓ controls are replaced with a <span class="demo">⋮⋮</span> handle. Grab that handle and drag the link to its new position.</p>
<p>Only the handle starts a drag, so clicking or editing Link Text, URL, checkboxes, or Remove will not move the row.</p>
<p><strong>The saved JSON format is unchanged.</strong></p>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($checks as $label => $ok): ?>
<tr><td><?php echo dd_h($label); ?></td><td class="<?php echo $ok ? 'pass' : 'fail'; ?>"><?php echo $ok ? 'PASS' : 'FAIL'; ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Action</h2>
<?php if ($ready && !$installed && !$attempted): ?>
<form method="post" onsubmit="return confirm('Apply drag-and-drop reordering to Manage Team Page Content?');">
<input type="hidden" name="action" value="apply">
<button type="submit" class="btn apply">Apply Drag & Drop Update</button>
</form>
<?php elseif (!$ready && !$installed): ?>
<p class="fail">Apply is unavailable until every preflight check passes.</p>
<?php endif; ?>

<?php if ($backupPath !== ''): ?>
<p><strong>Backup:</strong> <code><?php echo dd_h($backupPath); ?></code></p>
<?php endif; ?>
</div>

</div>
</body>
</html>
