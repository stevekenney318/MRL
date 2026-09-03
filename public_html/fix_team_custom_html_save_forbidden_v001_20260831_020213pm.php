<?php
declare(strict_types=1);

/**
 * fix_team_custom_html_save_forbidden.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/31/2026 2:02:13 pm
 *
 * PURPOSE:
 *   Fix Hostinger/WAF "Forbidden" when saving raw HTML through
 *   Manage Team Page Content.
 *
 * CHANGE:
 *   admin_team_page_content.php v007 -> v008
 *   The browser base64-encodes ONLY the Custom HTML textarea before POST.
 *   The PHP manager decodes it before writing the existing JSON field.
 *
 * PRESERVE:
 *   - Custom HTML preview
 *   - Enabled / Disabled
 *   - Above / Below Announcement
 *   - Existing announcement and link panels
 *   - Drag/drop
 *   - Existing JSON format/schema
 *   - team.php (untouched)
 *
 * ROLLBACK:
 *   Dedicated backup + manifest. Same installer restores exact pre-fix manager.
 *
 * NO DATABASE CHANGES.
 * NO JSON CONTENT CHANGES DURING INSTALL.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('/login.php');
    exit;
}

require __DIR__ . '/config.php';
require __DIR__ . '/config_mrl.php';

$uid = (int)($_SESSION['userSession'] ?? 0);
if (!isAdmin($uid)) {
    http_response_code(403);
    exit('Admin access required.');
}

function thsf_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function thsf_lf(string $s): string
{
    return str_replace(["\r\n", "\r"], "\n", $s);
}

function thsf_replace_once(string $source, string $old, string $new, string $label): string
{
    $count = substr_count($source, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ' expected once; found ' . $count . '.');
    }
    return str_replace($old, $new, $source);
}

$managerPath = __DIR__ . '/mrl_team/admin_team_page_content.php';
$backupRoot = __DIR__ . '/_migration_backups/team_custom_html_save_forbidden_fix';
$backupPath = $backupRoot . '/admin_team_page_content.php';
$manifestPath = $backupRoot . '/manifest.json';

$raw = is_file($managerPath) ? file_get_contents($managerPath) : false;
$manager = is_string($raw) ? thsf_lf($raw) : '';

$installed =
    strpos($manager, ' * VERSION: v008') !== false
    && strpos($manager, 'custom_html_content_b64') !== false
    && strpos($manager, 'thsfBase64Utf8') !== false;

$checks = [
    'admin_team_page_content.php exists' => is_file($managerPath),
    'manager is v007' => strpos($manager, ' * VERSION: v007') !== false,
    'Custom HTML builder exists' => strpos($manager, 'function atpc_build_custom_html(array $post): array') !== false,
    'raw Custom HTML POST field exists' => strpos($manager, 'name="custom_html_content"') !== false,
    'Custom HTML editor id exists' => strpos($manager, 'id="custom-html-content"') !== false,
    'manager form exists' => strpos($manager, '<form method="post">') !== false,
    'preview JavaScript exists' => strpos($manager, 'function refreshHtmlPreview()') !== false,
];

$ready = !in_array(false, $checks, true);
$rollbackAvailable = is_file($backupPath) && is_file($manifestPath);

$action = (string)($_POST['action'] ?? 'preview');
$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'apply') {
    if ($installed) {
        $ok = true;
        $message = 'The Forbidden-save fix is already installed.';
    } elseif (!$ready) {
        $message = 'Apply blocked because one or more preflight checks failed.';
    } else {
        try {
            if (!is_dir($backupRoot) && !mkdir($backupRoot, 0755, true) && !is_dir($backupRoot)) {
                throw new RuntimeException('Could not create backup folder.');
            }

            if (!copy($managerPath, $backupPath)) {
                throw new RuntimeException('Could not back up admin_team_page_content.php.');
            }

            $manifest = [
                'created_at' => date('Y-m-d H:i:s'),
                'task' => 'team_custom_html_save_forbidden_fix',
                'source_sha256' => hash_file('sha256', $managerPath),
                'source_version' => 'v007',
                'target_version' => 'v008',
            ];

            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($manifestJson) ||
                file_put_contents($manifestPath, $manifestJson . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Could not write rollback manifest.');
            }

            $new = $manager;

            // Version/header: tolerate the exact v007 timestamp.
            $changed = 0;
            $new = preg_replace(
                '/ \* VERSION: v007\n \* LAST MODIFIED: [^\n]+/',
                " * VERSION: v008\n * LAST MODIFIED: 8/31/2026 2:02:13 pm",
                $new,
                1,
                $changed
            );
            if ($changed !== 1) {
                throw new RuntimeException('Manager version header was not found exactly once.');
            }

            $new = thsf_replace_once(
                $new,
                " * CHANGELOG:\n *\n",
                " * CHANGELOG:\n *\n"
                . " * v008 (8/31/2026 2:02:13 pm)\n"
                . " * - FIX: Custom HTML is base64-encoded in the browser before Save, avoiding host/WAF rejection of raw HTML POST bodies.\n"
                . " * - FIX: Manager decodes the Custom HTML server-side before writing the existing JSON field.\n"
                . " * - PRESERVE: Preview, Enabled/Disabled, position, announcement, links, drag/drop, backups, CSRF, and schema v4.\n"
                . " *\n",
                'changelog anchor'
            );

            $oldBuilder = <<<'OLD'
function atpc_build_custom_html(array $post): array
{
    $position = ((string)($post['custom_html_position'] ?? 'above') === 'below') ? 'below' : 'above';
    return [
        'enabled' => !empty($post['custom_html_enabled']),
        'position' => $position,
        'html' => (string)($post['custom_html_content'] ?? ''),
    ];
}
OLD;

            $newBuilder = <<<'NEW'
function atpc_build_custom_html(array $post): array
{
    $position = ((string)($post['custom_html_position'] ?? 'above') === 'below') ? 'below' : 'above';

    $html = '';
    $encoded = (string)($post['custom_html_content_b64'] ?? '');

    if ($encoded !== '') {
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            throw new RuntimeException('Custom HTML could not be decoded. Save was blocked.');
        }
        $html = $decoded;
    } else {
        // Backward-compatible fallback for a direct/manual POST.
        $html = (string)($post['custom_html_content'] ?? '');
    }

    return [
        'enabled' => !empty($post['custom_html_enabled']),
        'position' => $position,
        'html' => $html,
    ];
}
NEW;

            $new = thsf_replace_once($new, $oldBuilder, $newBuilder, 'Custom HTML builder');

            $new = thsf_replace_once(
                $new,
                '<form method="post"><input type="hidden" name="csrf"',
                '<form method="post" id="mrl-team-content-form"><input type="hidden" name="csrf"',
                'manager form id'
            );

            $new = thsf_replace_once(
                $new,
                '<p><label>HTML snippet<br><textarea id="custom-html-content" class="custom-html-text" name="custom_html_content" placeholder="Paste HTML, CSS and/or JavaScript here...">',
                '<p><label>HTML snippet<br><textarea id="custom-html-content" class="custom-html-text" placeholder="Paste HTML, CSS and/or JavaScript here...">',
                'remove raw HTML POST name'
            );

            $new = thsf_replace_once(
                $new,
                '</textarea></label></p>' . "\n" . '<div class="hint">Saved HTML remains available when Disabled. It is rendered inside an isolated iframe on the Team page. PHP/server-side code is not executed.</div>',
                '</textarea></label></p>' . "\n"
                . '<input type="hidden" name="custom_html_content_b64" id="custom-html-content-b64" value="">' . "\n"
                . '<div class="hint">Saved HTML remains available when Disabled. It is rendered inside an isolated iframe on the Team page. PHP/server-side code is not executed.</div>',
                'encoded hidden field'
            );

            $previewAnchor = <<<'PREVIEW'
if(htmlEditor){htmlEditor.addEventListener('input',refreshHtmlPreview);refreshHtmlPreview();}
PREVIEW;

            $encodingJs = <<<'ENCODE'
if(htmlEditor){htmlEditor.addEventListener('input',refreshHtmlPreview);refreshHtmlPreview();}

function thsfBase64Utf8(value){
 const bytes=new TextEncoder().encode(value);
 let binary='';
 const chunk=0x8000;
 for(let i=0;i<bytes.length;i+=chunk){
   binary+=String.fromCharCode.apply(null,bytes.subarray(i,i+chunk));
 }
 return btoa(binary);
}

const teamContentForm=document.getElementById('mrl-team-content-form');
if(teamContentForm){
 teamContentForm.addEventListener('submit',function(event){
   const hidden=document.getElementById('custom-html-content-b64');
   if(!htmlEditor||!hidden||typeof TextEncoder==='undefined'||typeof btoa!=='function'){
     event.preventDefault();
     alert('Custom HTML could not be safely prepared for saving. No changes were submitted.');
     return;
   }
   hidden.value=thsfBase64Utf8(htmlEditor.value||'');
 });
}
ENCODE;

            $new = thsf_replace_once($new, $previewAnchor, $encodingJs, 'HTML encoding JavaScript');

            $new = str_replace('8/31/2026 2:02:13 pm', '8/31/2026 2:02:13 pm', $new);

            if (file_put_contents($managerPath, $new, LOCK_EX) === false) {
                throw new RuntimeException('Could not write updated manager.');
            }

            $verify = thsf_lf((string)file_get_contents($managerPath));
            $post = [
                'manager v008 installed' => strpos($verify, ' * VERSION: v008') !== false,
                'encoded POST field installed' => strpos($verify, 'custom_html_content_b64') !== false,
                'raw textarea name removed' => strpos($verify, 'name="custom_html_content"') === false,
                'UTF-8 base64 encoder installed' => strpos($verify, 'function thsfBase64Utf8(value)') !== false,
                'server decode installed' => strpos($verify, 'base64_decode($encoded, true)') !== false,
                'preview preserved' => strpos($verify, 'function refreshHtmlPreview()') !== false,
                'schema v4 preserved' => strpos($verify, "'schema_version' => 4,") !== false,
                'drag/drop preserved' => strpos($verify, 'drag-handle') !== false,
            ];

            if (in_array(false, $post, true)) {
                @copy($backupPath, $managerPath);
                throw new RuntimeException('Postflight failed; exact v007 manager restored.');
            }

            $checks = $post;
            $ok = true;
            $message = 'Forbidden-save fix installed successfully. The Custom HTML will now be encoded before POST.';
            $installed = true;
            $rollbackAvailable = true;
        } catch (Throwable $e) {
            $message = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'rollback') {
    try {
        if (!is_file($backupPath) || !is_file($manifestPath)) {
            throw new RuntimeException('Rollback backup/manifest not found.');
        }

        if (!copy($backupPath, $managerPath)) {
            throw new RuntimeException('Could not restore the pre-fix manager.');
        }

        $ok = true;
        $message = 'ROLLBACK COMPLETE — exact pre-fix admin_team_page_content.php restored.';
        $installed = false;
    } catch (Throwable $e) {
        $message = 'Rollback failed: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Custom HTML Save Fix</title>
<style>
:root{--bg:#101214;--panel:#1d2023;--border:#4b5055;--text:#f0f0f0;--gold:#efc77e;--green:#63e69a;--red:#ff7e7e;--blue:#55c7ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1100px;margin:20px auto}.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:800}
.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}.info{background:#122a3a;border:1px solid #2d6a8c;color:#d8f2ff}
table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #3a3e42;text-align:left}th{color:#ffe0a0}.pass{color:var(--green);font-weight:800}.fail{color:var(--red);font-weight:800}
.btn{padding:11px 18px;border-radius:8px;font-weight:800;cursor:pointer;margin-right:10px}.apply{background:#16894b;color:#fff;border:1px solid #4be388}.rollback{background:#a32222;color:#fff;border:1px solid #ef6666}
</style>
</head>
<body><div class="wrap">
<div class="card">
<h1>Fix — Custom HTML Save “Forbidden”</h1>
<p><strong>Installer:</strong> v001 &nbsp; | &nbsp; <strong>Generated:</strong> 8/31/2026 2:02:13 pm America/New_York</p>
<?php if ($message !== ''): ?><div class="banner <?php echo $ok ? 'ok' : 'bad'; ?>"><?php echo thsf_h($message); ?></div><?php endif; ?>
<?php if (!$message && $installed): ?><div class="banner ok">FIX IS INSTALLED.</div><?php elseif (!$message && $ready): ?><div class="banner ok">PREVIEW PASS — ready to apply.</div><?php elseif (!$message): ?><div class="banner bad">PREVIEW BLOCKED — see failed checks below.</div><?php endif; ?>
</div>

<div class="card">
<h2>What This Fix Does</h2>
<div class="banner info">The host is almost certainly rejecting the raw HTML in the POST request before PHP ever sees it.</div>
<p>This fix keeps the editor exactly as-is, but base64-encodes only the Custom HTML field in the browser before Save. The manager decodes it immediately before writing the same JSON field.</p>
<p><strong>team.php is not touched.</strong> Your Custom HTML block, preview, position and enable/disable controls remain unchanged.</p>
</div>

<div class="card">
<h2>Preflight / Postflight</h2>
<table><thead><tr><th>Check</th><th>Status</th></tr></thead><tbody>
<?php foreach ($checks as $label => $status): ?>
<tr><td><?php echo thsf_h($label); ?></td><td class="<?php echo $status ? 'pass' : 'fail'; ?>"><?php echo $status ? 'PASS' : 'FAIL'; ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>

<div class="card">
<h2>Actions</h2>
<?php if ($ready && !$installed): ?>
<form method="post" style="display:inline" onsubmit="return confirm('Apply the Custom HTML Forbidden-save fix?');">
<input type="hidden" name="action" value="apply">
<button class="btn apply" type="submit">Apply Save Fix</button>
</form>
<?php endif; ?>

<?php if ($rollbackAvailable): ?>
<form method="post" style="display:inline" onsubmit="return confirm('ROLL BACK this save fix and restore the exact pre-fix manager?');">
<input type="hidden" name="action" value="rollback">
<button class="btn rollback" type="submit">Rollback Save Fix</button>
</form>
<?php endif; ?>
</div>
</div></body></html>
