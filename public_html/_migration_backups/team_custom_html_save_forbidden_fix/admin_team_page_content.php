<?php
declare(strict_types=1);

/**
 * admin_team_page_content.php
 *
 * VERSION: v007
 * LAST MODIFIED: 8/31/2026 1:30:45 pm
 *
 * Admin-only editor for JSON-driven Team Page content.
 *
 * CHANGELOG:
 *
 * v007 (8/31/2026 1:30:45 pm)
 * - NEW: Adds one optional Custom HTML block with Enabled/Disabled and Above/Below Announcement position.
 * - NEW: Adds an isolated HTML Preview iframe in the manager.
 * - CHANGE: Content schema advances to v4 on the next save while preserving all existing content.
 * - PRESERVE: Drag/drop ordering, announcements, links, authentication, CSRF, backups, and JSON behavior.
 *
 * v006 (8/30/2026 3:22:21 pm)
 * - NEW: Link rows can now be reordered by dragging the ⋮⋮ handle.
 * - CHANGE: Removed the ↑ / ↓ reorder buttons in favor of handle-only drag/drop.
 * - PRESERVE: Existing panel titles, links, enabled/new-tab/remove controls, Add Link, JSON schema, backups, authentication, and CSRF behavior.
 *
 * v005 (8/28/2026 3:09:01 pm)
 * - NEW: Adds an optional Team Page announcement/news panel editor.
 * - NEW: Announcement panel supports Enabled/Disabled, an optional title, and freeform multi-line text.
 * - NEW: Plain http:// and https:// URLs entered in announcement text are automatically clickable on team.php.
 * - CHANGE: Content schema advances to v3 while preserving all four existing link panels.
 * - PRESERVE: Existing link ordering, enabled/new-tab/remove controls, authentication, CSRF, backup, and JSON behavior.
 *
 * v004 (8/28/2026 2:36:58 pm)
 * - FIX: Productionized the return link from the Team Page Content manager.
 * - CHANGE: Link label changed from "Team Redesign" to "Team".
 * - CHANGE: Link target changed from /team_redesign.php to /team.php.
 * - PRESERVE: No content-manager, JSON, ordering, authentication, or Admin behavior changes.
 *
 * v003 (8/27/2026 6:57:28 pm)
 * - FIX: Every editable row now uses explicit indexed field names.
 * - FIX: Enabled/New-tab/Remove checkboxes remain aligned after Up/Down moves.
 * - PRESERVE: Four editable panels and fixed Manage Team Page Content control.
 *
 * v002 (8/27/2026 6:33:12 pm)
 * - Added all four panels and Up/Down ordering.
 */

session_start();

require_once dirname(__DIR__) . '/class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

date_default_timezone_set('America/New_York');
require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/config_mrl.php';

$uid = (int)($_SESSION['userSession'] ?? 0);
if (!isAdmin($uid)) {
    http_response_code(403);
    exit('Admin access required.');
}

$contentPath = __DIR__ . '/mrl_team_page_content.json';

function atpc_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function atpc_load(string $path): array
{
    $raw = is_file($path) ? file_get_contents($path) : '';
    $data = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($data) ? $data : [];
}

function atpc_clean_url(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    if ($url[0] === '/') return $url;
    if (preg_match('~^https?://~i', $url)) return $url;
    return '';
}

function atpc_build_panel(array $post, string $key, string $fallback): array
{
    $title = trim((string)($post[$key . '_title'] ?? $fallback));
    if ($title === '') $title = $fallback;

    $labels = is_array($post[$key . '_label'] ?? null) ? $post[$key . '_label'] : [];
    $urls = is_array($post[$key . '_url'] ?? null) ? $post[$key . '_url'] : [];
    $enabled = is_array($post[$key . '_enabled'] ?? null) ? $post[$key . '_enabled'] : [];
    $newtab = is_array($post[$key . '_new_tab'] ?? null) ? $post[$key . '_new_tab'] : [];
    $remove = is_array($post[$key . '_remove'] ?? null) ? $post[$key . '_remove'] : [];

    $items = [];
    foreach ($labels as $i => $labelRaw) {
        if (!empty($remove[$i])) continue;

        $label = trim((string)$labelRaw);
        $url = atpc_clean_url((string)($urls[$i] ?? ''));
        if ($label === '' || $url === '') continue;

        $items[] = [
            'label' => $label,
            'url' => $url,
            'enabled' => !empty($enabled[$i]),
            'new_tab' => !empty($newtab[$i]),
        ];
    }

    return ['title' => $title, 'items' => $items];
}

function atpc_build_announcement(array $post): array
{
    return [
        'enabled' => !empty($post['announcement_enabled']),
        'title' => trim((string)($post['announcement_title'] ?? '')),
        'content' => trim((string)($post['announcement_content'] ?? '')),
    ];
}

function atpc_build_custom_html(array $post): array
{
    $position = ((string)($post['custom_html_position'] ?? 'above') === 'below') ? 'below' : 'above';
    return [
        'enabled' => !empty($post['custom_html_enabled']),
        'position' => $position,
        'html' => (string)($post['custom_html_content'] ?? ''),
    ];
}

if (!isset($_SESSION['atpc_csrf'])) {
    $_SESSION['atpc_csrf'] = bin2hex(random_bytes(24));
}

$data = atpc_load($contentPath);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string)$_SESSION['atpc_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $message = 'Save blocked: security token mismatch.';
    } else {
        $new = [
            'schema_version' => 4,
            'updated_at' => date('Y-m-d H:i:s'),
            'custom_html_block' => atpc_build_custom_html($_POST),
            'announcement_panel' => atpc_build_announcement($_POST),
            'admin_league_panel' => atpc_build_panel($_POST, 'admin_league', 'League & Team'),
            'admin_hosting_panel' => atpc_build_panel($_POST, 'admin_hosting', 'Hosting & Infrastructure'),
            'league_panel' => atpc_build_panel($_POST, 'league', 'League Information'),
            'team_panel' => atpc_build_panel($_POST, 'team', 'Team Menu'),
        ];

        $backupDir = dirname(__DIR__) . '/_migration_backups/team_page_content_' . date('Ymd_His');
        $backupOk = true;
        if (is_file($contentPath)) {
            $backupOk = (is_dir($backupDir) || mkdir($backupDir, 0755, true))
                && copy($contentPath, $backupDir . '/mrl_team_page_content.json');
        }

        $json = json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $ok = $backupOk && is_string($json)
            && file_put_contents($contentPath, $json . PHP_EOL, LOCK_EX) !== false;

        $message = $ok
            ? 'Team page content saved. Existing JSON was backed up first.'
            : 'Save failed.';
        $data = atpc_load($contentPath);
    }
}

function atpc_rows(string $key, array $items): void
{
    foreach ($items as $i => $it) {
        echo '<tr>';
        echo '<td class="order"><button type="button" class="drag-handle" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">⋮⋮</button></td>';
        echo '<td><input data-role="label" name="' . atpc_h($key) . '_label[' . $i . ']" value="' . atpc_h($it['label'] ?? '') . '"></td>';
        echo '<td><input data-role="url" name="' . atpc_h($key) . '_url[' . $i . ']" value="' . atpc_h($it['url'] ?? '') . '"></td>';
        echo '<td><input data-role="enabled" name="' . atpc_h($key) . '_enabled[' . $i . ']" value="1" type="checkbox" ' . (!empty($it['enabled']) ? 'checked' : '') . '></td>';
        echo '<td><input data-role="newtab" name="' . atpc_h($key) . '_new_tab[' . $i . ']" value="1" type="checkbox" ' . (!empty($it['new_tab']) ? 'checked' : '') . '></td>';
        echo '<td><input data-role="remove" name="' . atpc_h($key) . '_remove[' . $i . ']" value="1" type="checkbox"></td>';
        echo '</tr>';
    }
}

$panels = [
    'admin_league' => ['data'=>'admin_league_panel','heading'=>'Admin · League & Team'],
    'admin_hosting' => ['data'=>'admin_hosting_panel','heading'=>'Admin · Hosting & Infrastructure'],
    'league' => ['data'=>'league_panel','heading'=>'League Information'],
    'team' => ['data'=>'team_panel','heading'=>'Team Menu'],
];
?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Team Page Content</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#151515;color:#eee;font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:94%;max-width:1400px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:18px;margin-bottom:16px}
h1,h2{color:#efc982}a{color:#76cfff}.note{padding:11px;border:1px solid #555;border-radius:9px;background:#171717}
table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border-bottom:1px solid #444;padding:7px;text-align:left}
td input:not([type=checkbox]){width:100%}input{padding:7px;background:#111;color:#eee;border:1px solid #666;border-radius:5px}
.panel-title{width:100%;max-width:560px}.announcement-title{width:100%;max-width:760px}.announcement-text{width:100%;min-height:150px;resize:vertical;padding:9px;background:#111;color:#eee;border:1px solid #666;border-radius:5px;font:16px/1.4 Tahoma,Verdana,Segoe UI,sans-serif}.custom-html-text{width:100%;min-height:260px;resize:vertical;padding:9px;background:#111;color:#eee;border:1px solid #666;border-radius:5px;font:14px/1.4 Consolas,"Courier New",monospace}.html-preview{width:100%;min-height:220px;background:#fff;border:1px solid #666;border-radius:8px}
.inline-check{display:inline-flex;align-items:center;gap:8px;margin:4px 0 12px}.hint{color:#bbb;font-size:13px;line-height:1.35;margin-top:7px}
button{padding:10px 17px;border:1px solid #5a7fb5;border-radius:8px;background:#1466c9;color:#fff;font-weight:800;cursor:pointer}
.drag-handle{padding:4px 10px;margin:0;background:#2b2b2b;border-color:#777;color:#ddd;font-size:20px;line-height:1;letter-spacing:-3px;cursor:grab;touch-action:none}.drag-handle:active{cursor:grabbing}.order{width:58px;white-space:nowrap;text-align:center}.dragging{opacity:.45}.drag-over td{box-shadow:inset 0 2px 0 #76cfff}.message{margin-top:12px;padding:10px;border:1px solid #777;border-radius:8px;color:#efc982}.save{position:sticky;bottom:8px}
</style></head><body><div class="wrap">
<div class="card"><h1>Manage Team Page Content</h1><p><a href="/team.php">← Team</a></p>
<div class="note"><strong>Manage Team Page Content</strong> is a fixed Admin control and cannot be edited here.</div>
<?php if($message!==''):?><div class="message"><?php echo atpc_h($message);?></div><?php endif;?></div>
<form method="post"><input type="hidden" name="csrf" value="<?php echo atpc_h((string)$_SESSION['atpc_csrf']);?>">
<div class="card">
<h2>Team Page Custom HTML</h2>
<label class="inline-check"><input name="custom_html_enabled" value="1" type="checkbox" <?php echo !empty($data['custom_html_block']['enabled']) ? 'checked' : ''; ?>> Enabled</label>
<label style="margin-left:18px;">Position
<select name="custom_html_position" style="margin-left:6px;padding:7px;background:#111;color:#eee;border:1px solid #666;border-radius:5px;">
<option value="above" <?php echo (($data['custom_html_block']['position'] ?? 'above') === 'above') ? 'selected' : ''; ?>>Above Announcement / News</option>
<option value="below" <?php echo (($data['custom_html_block']['position'] ?? 'above') === 'below') ? 'selected' : ''; ?>>Below Announcement / News</option>
</select>
</label>
<p><label>HTML snippet<br><textarea id="custom-html-content" class="custom-html-text" name="custom_html_content" placeholder="Paste HTML, CSS and/or JavaScript here..."><?php echo atpc_h($data['custom_html_block']['html'] ?? ''); ?></textarea></label></p>
<div class="hint">Saved HTML remains available when Disabled. It is rendered inside an isolated iframe on the Team page. PHP/server-side code is not executed.</div>
<p><strong>Preview</strong></p>
<iframe id="custom-html-preview" class="html-preview" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
</div>
<div class="card">
<h2>Team Page Announcement / News</h2>
<label class="inline-check"><input name="announcement_enabled" value="1" type="checkbox" <?php echo !empty($data['announcement_panel']['enabled']) ? 'checked' : ''; ?>> Enabled</label>
<label>Panel title (optional)<br><input class="announcement-title" name="announcement_title" value="<?php echo atpc_h($data['announcement_panel']['title'] ?? 'League News'); ?>"></label>
<p><label>Announcement / notes<br><textarea class="announcement-text" name="announcement_content" placeholder="Write a sentence, paragraph, reminder, league news, etc."><?php echo atpc_h($data['announcement_panel']['content'] ?? ''); ?></textarea></label></p>
<div class="hint">Plain http:// or https:// URLs become clickable links automatically on the Team page. No HTML is required.</div>
</div>
<?php foreach($panels as $key=>$meta):$dk=$meta['data'];?><div class="card">
<h2><?php echo atpc_h($meta['heading']);?></h2>
<label>Panel title<br><input class="panel-title" name="<?php echo atpc_h($key);?>_title" value="<?php echo atpc_h($data[$dk]['title']??'');?>"></label>
<table><thead><tr><th>Order</th><th>Link text</th><th>URL</th><th>Enabled</th><th>New tab</th><th>Remove</th></tr></thead>
<tbody id="<?php echo atpc_h($key);?>-rows" data-key="<?php echo atpc_h($key);?>">
<?php atpc_rows($key,is_array($data[$dk]['items']??null)?$data[$dk]['items']:[]);?>
</tbody></table>
<p>Drag the ⋮⋮ handle to reorder links.</p><button type="button" onclick="addRow('<?php echo atpc_h($key);?>')">Add Link</button>
</div><?php endforeach;?>
<div class="card save"><button type="submit">Save Team Page Content</button></div></form></div>
<script>
function renumber(tb){
 const k=tb.dataset.key;
 [...tb.rows].forEach((r,i)=>{
   const map={label:'_label',url:'_url',enabled:'_enabled',newtab:'_new_tab',remove:'_remove'};
   r.querySelectorAll('input[data-role]').forEach(x=>{x.name=k+map[x.dataset.role]+'['+i+']';});
 });
}
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

const htmlEditor=document.getElementById('custom-html-content');
const htmlPreview=document.getElementById('custom-html-preview');
function refreshHtmlPreview(){
 if(!htmlEditor||!htmlPreview)return;
 htmlPreview.srcdoc=htmlEditor.value||'<div style="font-family:sans-serif;color:#777;padding:16px;">Preview is empty.</div>';
}
if(htmlEditor){htmlEditor.addEventListener('input',refreshHtmlPreview);refreshHtmlPreview();}

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
</script></body></html>
