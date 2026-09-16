<?php
declare(strict_types=1);

/**
 * admin_team_page_content.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/27/2026 5:27:00 pm
 *
 * Admin-only editor for JSON-driven Team Page panel content.
 */

session_start();

require_once 'class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}

date_default_timezone_set('America/New_York');
require 'config.php';
require 'config_mrl.php';

$uid = (int)($_SESSION['userSession'] ?? 0);
if (!isAdmin($uid)) {
    http_response_code(403);
    exit('Admin access required.');
}

$contentPath = __DIR__ . '/mrl_team_page_content.json';

function atpc_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function atpc_default(): array
{
    return [
        'schema_version' => 1,
        'league_panel' => ['title' => 'League Information', 'items' => []],
        'team_panel' => ['title' => 'Team Menu', 'items' => []],
    ];
}

function atpc_load(string $path): array
{
    if (!is_file($path)) return atpc_default();
    $raw = file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($data) ? array_replace_recursive(atpc_default(), $data) : atpc_default();
}

function atpc_clean_url(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    if ($url[0] === '/') return $url;
    if (preg_match('~^https?://~i', $url)) return $url;
    return '';
}

function atpc_build_panel(array $post, string $key, string $fallbackTitle): array
{
    $title = trim((string)($post[$key . '_title'] ?? $fallbackTitle));
    if ($title === '') $title = $fallbackTitle;

    $labels = $post[$key . '_label'] ?? [];
    $urls = $post[$key . '_url'] ?? [];
    $enabled = $post[$key . '_enabled'] ?? [];
    $newTab = $post[$key . '_new_tab'] ?? [];
    $remove = $post[$key . '_remove'] ?? [];

    $items = [];
    $count = max(is_array($labels) ? count($labels) : 0, is_array($urls) ? count($urls) : 0);

    for ($i = 0; $i < $count; $i++) {
        if (isset($remove[$i])) continue;

        $label = trim((string)($labels[$i] ?? ''));
        $url = atpc_clean_url((string)($urls[$i] ?? ''));

        if ($label === '' && $url === '') continue;
        if ($label === '' || $url === '') continue;

        $items[] = [
            'label' => $label,
            'url' => $url,
            'enabled' => isset($enabled[$i]),
            'new_tab' => isset($newTab[$i]),
        ];
    }

    return ['title' => $title, 'items' => $items];
}

if (!isset($_SESSION['atpc_csrf'])) {
    $_SESSION['atpc_csrf'] = bin2hex(random_bytes(24));
}

$data = atpc_load($contentPath);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');

    if (!hash_equals((string)$_SESSION['atpc_csrf'], $csrf)) {
        $message = 'Save blocked: security token mismatch.';
    } else {
        $newData = [
            'schema_version' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'league_panel' => atpc_build_panel($_POST, 'league', 'League Information'),
            'team_panel' => atpc_build_panel($_POST, 'team', 'Team Menu'),
        ];

        $backupDir = __DIR__ . '/_migration_backups/team_page_content_' . date('Ymd_His');
        $backupOk = true;

        if (is_file($contentPath)) {
            $backupOk = (is_dir($backupDir) || mkdir($backupDir, 0755, true))
                && copy($contentPath, $backupDir . '/mrl_team_page_content.json');
        }

        $json = json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $saveOk = $backupOk && is_string($json)
            && file_put_contents($contentPath, $json . PHP_EOL, LOCK_EX) !== false;

        $message = $saveOk
            ? 'Team page content saved. Existing JSON was backed up first.'
            : 'Save failed. No intentional changes were made.';
        $data = atpc_load($contentPath);
    }
}

function atpc_rows(string $key, array $items): void
{
    foreach ($items as $i => $item) {
        echo '<tr>';
        echo '<td class="handle">' . ($i + 1) . '</td>';
        echo '<td><input name="' . atpc_h($key) . '_label[]" value="' . atpc_h($item['label'] ?? '') . '"></td>';
        echo '<td><input name="' . atpc_h($key) . '_url[]" value="' . atpc_h($item['url'] ?? '') . '"></td>';
        echo '<td><input type="checkbox" name="' . atpc_h($key) . '_enabled[' . $i . ']" ' . (!empty($item['enabled']) ? 'checked' : '') . '></td>';
        echo '<td><input type="checkbox" name="' . atpc_h($key) . '_new_tab[' . $i . ']" ' . (!empty($item['new_tab']) ? 'checked' : '') . '></td>';
        echo '<td><input type="checkbox" name="' . atpc_h($key) . '_remove[' . $i . ']"></td>';
        echo '</tr>';
    }
}
?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Team Page Content</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#151515;color:#eee;font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:94%;max-width:1300px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:18px;margin-bottom:16px}
h1,h2{color:#efc982}.top a{color:#76cfff}.panel-title{width:100%;max-width:560px;padding:8px}
table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border-bottom:1px solid #444;padding:7px;text-align:left}
td input:not([type=checkbox]){width:100%}input{padding:7px;background:#111;color:#eee;border:1px solid #666;border-radius:5px}
button{padding:10px 17px;border:1px solid #5a7fb5;border-radius:8px;background:#1466c9;color:#fff;font-weight:800;cursor:pointer}
.message{padding:10px;border:1px solid #777;border-radius:8px;color:#efc982}.small{color:#bbb;font-size:13px}.handle{width:36px;color:#aaa}
</style>
</head>
<body>
<div class="wrap">
<div class="card top">
<h1>Manage Team Page Content</h1>
<p><a href="/team_redesign.php">← Team Redesign</a></p>
<p>This is a dedicated admin tool. The link to this page is hard-wired into the Admin Menu and is not editable here.</p>
<?php if ($message !== ''): ?><div class="message"><?php echo atpc_h($message); ?></div><?php endif; ?>
</div>

<form method="post">
<input type="hidden" name="csrf" value="<?php echo atpc_h((string)$_SESSION['atpc_csrf']); ?>">

<?php foreach (['league' => 'league_panel', 'team' => 'team_panel'] as $key => $dataKey): ?>
<div class="card">
<h2><?php echo $key === 'league' ? 'League Information Panel' : 'Team Menu Panel'; ?></h2>
<label>Panel title<br><input class="panel-title" name="<?php echo atpc_h($key); ?>_title" value="<?php echo atpc_h($data[$dataKey]['title'] ?? ''); ?>"></label>
<table>
<thead><tr><th>#</th><th>Link text</th><th>URL</th><th>Enabled</th><th>New tab</th><th>Remove</th></tr></thead>
<tbody id="<?php echo atpc_h($key); ?>-rows">
<?php atpc_rows($key, is_array($data[$dataKey]['items'] ?? null) ? $data[$dataKey]['items'] : []); ?>
</tbody>
</table>
<p class="small">Rows save in the order shown. URLs must begin with /, http://, or https://.</p>
<button type="button" onclick="addRow('<?php echo atpc_h($key); ?>')">Add Link</button>
</div>
<?php endforeach; ?>

<div class="card"><button type="submit">Save Team Page Content</button></div>
</form>
</div>
<script>
function addRow(key){
    const body=document.getElementById(key+'-rows');
    const i=body.rows.length;
    const row=body.insertRow();
    row.innerHTML=
      '<td class="handle">'+(i+1)+'</td>'+
      '<td><input name="'+key+'_label[]" value=""></td>'+
      '<td><input name="'+key+'_url[]" value=""></td>'+
      '<td><input type="checkbox" name="'+key+'_enabled['+i+']" checked></td>'+
      '<td><input type="checkbox" name="'+key+'_new_tab['+i+']" checked></td>'+
      '<td><input type="checkbox" name="'+key+'_remove['+i+']"></td>';
}
</script>
</body>
</html>
