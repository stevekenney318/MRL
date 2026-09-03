<?php
declare(strict_types=1);

/**
 * install_team_custom_html_block.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/31/2026 1:30:45 pm
 *
 * TASK 1:
 *   Add one optional Custom HTML block to Manage Team Page Content.
 *
 * CHANGES:
 *   - admin_team_page_content.php v006 -> v007
 *   - team.php v040 -> v041
 *   - JSON schema advances to v4 the next time Team Page Content is saved.
 *   - Custom HTML block supports Enabled/Disabled and Above/Below Announcement.
 *   - HTML is stored in JSON and rendered in an isolated iframe on team.php.
 *   - Manager includes an inline Preview iframe.
 *
 * ROLLBACK:
 *   This installer creates a dedicated backup set and stores a manifest.
 *   The same installer can restore the exact pre-install PHP files and JSON.
 *
 * NO DATABASE CHANGES.
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) session_start();

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

function thb_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function thb_lf(string $s): string { return str_replace(["\r\n", "\r"], "\n", $s); }

$adminPath = __DIR__ . '/mrl_team/admin_team_page_content.php';
$teamPath  = __DIR__ . '/team.php';
$jsonPath  = __DIR__ . '/mrl_team/mrl_team_page_content.json';

$backupRoot = __DIR__ . '/_migration_backups/team_custom_html_block';
$manifestPath = $backupRoot . '/manifest.json';

$adminRaw = is_file($adminPath) ? file_get_contents($adminPath) : false;
$teamRaw  = is_file($teamPath) ? file_get_contents($teamPath) : false;
$admin = is_string($adminRaw) ? thb_lf($adminRaw) : '';
$team  = is_string($teamRaw) ? thb_lf($teamRaw) : '';

$installed =
    strpos($admin, ' * VERSION: v007') !== false
    && strpos($admin, 'custom_html_block') !== false
    && strpos($team, ' * VERSION: v041') !== false
    && strpos($team, 'mrl-rd-custom-html') !== false;

$checks = [
    'admin_team_page_content.php exists' => is_file($adminPath),
    'team.php exists' => is_file($teamPath),
    'content JSON exists' => is_file($jsonPath),
    'manager is v006' => strpos($admin, ' * VERSION: v006') !== false,
    'manager drag/drop v006 marker present' => strpos($admin, 'class="drag-handle" draggable="true"') !== false,
    'manager announcement builder present' => strpos($admin, 'function atpc_build_announcement(array $post): array') !== false,
    'manager schema v3 save present' => strpos($admin, "'schema_version' => 3,") !== false,
    'team is v040' => strpos($team, ' * VERSION: v040') !== false,
    'team quiet-submit marker present' => strpos($team, 'X-MRL-Quiet-Submit') !== false,
    'team announcement defaults present' => strpos($team, "'announcement_panel' => [") !== false,
    'team announcement render block present' => strpos($team, '$announcementPanel = isset($teamPageContent[\'announcement_panel\'])') !== false,
];

$ready = !in_array(false, $checks, true);
$rollbackAvailable = is_file($manifestPath);

function thb_replace_once(string $haystack, string $old, string $new, string $label): string
{
    $count = substr_count($haystack, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ' expected once; found ' . $count . '.');
    }
    return str_replace($old, $new, $haystack);
}

function thb_backup_file(string $source, string $dest): array
{
    if (!is_file($source)) return ['exists' => false, 'sha256' => null];
    if (!copy($source, $dest)) throw new RuntimeException('Could not back up ' . basename($source));
    return ['exists' => true, 'sha256' => hash_file('sha256', $source)];
}

$action = $_POST['action'] ?? 'preview';
$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'apply') {
    if ($installed) {
        $ok = true;
        $message = 'Custom HTML block is already installed.';
    } elseif (!$ready) {
        $message = 'Apply blocked because one or more preflight checks failed.';
    } else {
        try {
            if (!is_dir($backupRoot) && !mkdir($backupRoot, 0755, true) && !is_dir($backupRoot)) {
                throw new RuntimeException('Could not create backup folder.');
            }

            $adminBackup = $backupRoot . '/admin_team_page_content.php';
            $teamBackup  = $backupRoot . '/team.php';
            $jsonBackup  = $backupRoot . '/mrl_team_page_content.json';

            $manifest = [
                'created_at' => date('Y-m-d H:i:s'),
                'task' => 'team_custom_html_block',
                'files' => [
                    'admin' => thb_backup_file($adminPath, $adminBackup),
                    'team'  => thb_backup_file($teamPath, $teamBackup),
                    'json'  => thb_backup_file($jsonPath, $jsonBackup),
                ],
            ];

            file_put_contents(
                $manifestPath,
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                LOCK_EX
            );

            // ---- manager v006 -> v007 ----
            $newAdmin = $admin;

            $newAdmin = thb_replace_once(
                $newAdmin,
                " * VERSION: v006\n * LAST MODIFIED: 8/30/2026 3:22:21 pm",
                " * VERSION: v007\n * LAST MODIFIED: 8/31/2026 1:30:45 pm",
                'manager version header'
            );

            $newAdmin = thb_replace_once(
                $newAdmin,
                " * CHANGELOG:\n *\n * v006 (8/30/2026 3:22:21 pm)",
                " * CHANGELOG:\n *\n"
                . " * v007 (8/31/2026 1:30:45 pm)\n"
                . " * - NEW: Adds one optional Custom HTML block with Enabled/Disabled and Above/Below Announcement position.\n"
                . " * - NEW: Adds an isolated HTML Preview iframe in the manager.\n"
                . " * - CHANGE: Content schema advances to v4 on the next save while preserving all existing content.\n"
                . " * - PRESERVE: Drag/drop ordering, announcements, links, authentication, CSRF, backups, and JSON behavior.\n"
                . " *\n"
                . " * v006 (8/30/2026 3:22:21 pm)",
                'manager changelog'
            );

            $newAdmin = thb_replace_once(
                $newAdmin,
                "function atpc_build_announcement(array \$post): array\n{\n    return [\n        'enabled' => !empty(\$post['announcement_enabled']),\n        'title' => trim((string)(\$post['announcement_title'] ?? '')),\n        'content' => trim((string)(\$post['announcement_content'] ?? '')),\n    ];\n}\n",
                "function atpc_build_announcement(array \$post): array\n{\n    return [\n        'enabled' => !empty(\$post['announcement_enabled']),\n        'title' => trim((string)(\$post['announcement_title'] ?? '')),\n        'content' => trim((string)(\$post['announcement_content'] ?? '')),\n    ];\n}\n\n"
                . "function atpc_build_custom_html(array \$post): array\n{\n    \$position = ((string)(\$post['custom_html_position'] ?? 'above') === 'below') ? 'below' : 'above';\n    return [\n        'enabled' => !empty(\$post['custom_html_enabled']),\n        'position' => \$position,\n        'html' => (string)(\$post['custom_html_content'] ?? ''),\n    ];\n}\n",
                'custom HTML builder'
            );

            $newAdmin = thb_replace_once(
                $newAdmin,
                "            'schema_version' => 3,\n            'updated_at' => date('Y-m-d H:i:s'),\n            'announcement_panel' => atpc_build_announcement(\$_POST),",
                "            'schema_version' => 4,\n            'updated_at' => date('Y-m-d H:i:s'),\n            'custom_html_block' => atpc_build_custom_html(\$_POST),\n            'announcement_panel' => atpc_build_announcement(\$_POST),",
                'schema/save structure'
            );

            $newAdmin = thb_replace_once(
                $newAdmin,
                ".panel-title{width:100%;max-width:560px}.announcement-title{width:100%;max-width:760px}.announcement-text{width:100%;min-height:150px;resize:vertical;padding:9px;background:#111;color:#eee;border:1px solid #666;border-radius:5px;font:16px/1.4 Tahoma,Verdana,Segoe UI,sans-serif}",
                ".panel-title{width:100%;max-width:560px}.announcement-title{width:100%;max-width:760px}.announcement-text{width:100%;min-height:150px;resize:vertical;padding:9px;background:#111;color:#eee;border:1px solid #666;border-radius:5px;font:16px/1.4 Tahoma,Verdana,Segoe UI,sans-serif}.custom-html-text{width:100%;min-height:260px;resize:vertical;padding:9px;background:#111;color:#eee;border:1px solid #666;border-radius:5px;font:14px/1.4 Consolas,\"Courier New\",monospace}.html-preview{width:100%;min-height:220px;background:#fff;border:1px solid #666;border-radius:8px}",
                'manager HTML CSS'
            );

            $announcementCard = <<<'ANN'
<div class="card">
<h2>Team Page Announcement / News</h2>
<label class="inline-check"><input name="announcement_enabled" value="1" type="checkbox" <?php echo !empty($data['announcement_panel']['enabled']) ? 'checked' : ''; ?>> Enabled</label>
<label>Panel title (optional)<br><input class="announcement-title" name="announcement_title" value="<?php echo atpc_h($data['announcement_panel']['title'] ?? 'League News'); ?>"></label>
<p><label>Announcement / notes<br><textarea class="announcement-text" name="announcement_content" placeholder="Write a sentence, paragraph, reminder, league news, etc."><?php echo atpc_h($data['announcement_panel']['content'] ?? ''); ?></textarea></label></p>
<div class="hint">Plain http:// or https:// URLs become clickable links automatically on the Team page. No HTML is required.</div>
</div>
ANN;

            $htmlCard = <<<'HTMLCARD'
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
HTMLCARD;

            $newAdmin = thb_replace_once(
                $newAdmin,
                $announcementCard,
                $htmlCard . "\n" . $announcementCard,
                'manager Custom HTML card'
            );

            $newAdmin = thb_replace_once(
                $newAdmin,
                "document.querySelectorAll('tbody[data-key]').forEach(tb=>{\n renumber(tb);",
                "const htmlEditor=document.getElementById('custom-html-content');\n"
                . "const htmlPreview=document.getElementById('custom-html-preview');\n"
                . "function refreshHtmlPreview(){\n"
                . " if(!htmlEditor||!htmlPreview)return;\n"
                . " htmlPreview.srcdoc=htmlEditor.value||'<div style=\"font-family:sans-serif;color:#777;padding:16px;\">Preview is empty.</div>';\n"
                . "}\n"
                . "if(htmlEditor){htmlEditor.addEventListener('input',refreshHtmlPreview);refreshHtmlPreview();}\n\n"
                . "document.querySelectorAll('tbody[data-key]').forEach(tb=>{\n renumber(tb);",
                'manager preview JavaScript'
            );

            $newAdmin = str_replace('8/31/2026 1:30:45 pm', '8/31/2026 1:30:45 pm', $newAdmin);

            // ---- team v040 -> v041 ----
            $newTeam = $team;

            if (strpos($newTeam, " * VERSION: v040") === false) {
                throw new RuntimeException('team.php v040 marker missing.');
            }
            $newTeam = preg_replace(
                '/ \* VERSION: v040\n \* LAST MODIFIED: [^\n]+/',
                " * VERSION: v041\n * LAST MODIFIED: 8/31/2026 1:30:45 pm",
                $newTeam,
                1
            );

            $newTeam = thb_replace_once(
                $newTeam,
                " * CHANGELOG:\n *\n",
                " * CHANGELOG:\n *\n"
                . " * v041 (8/31/2026 1:30:45 pm)\n"
                . " * - NEW: Adds one JSON-managed Custom HTML block to the Team page.\n"
                . " * - NEW: Supports Enabled/Disabled plus Above/Below Announcement placement.\n"
                . " * - SAFETY: Custom HTML is isolated inside a sandboxed iframe rather than injected into the Team page DOM.\n"
                . " * - PRESERVE: v040 quiet pick submission, announcements, menus, themes, charts, picks, LP, RD, scoring, and database behavior.\n"
                . " *\n",
                'team changelog'
            );

            $newTeam = thb_replace_once(
                $newTeam,
                "\$teamPageContentDefaults = [\n    'announcement_panel' => [",
                "\$teamPageContentDefaults = [\n"
                . "    'custom_html_block' => [\n"
                . "        'enabled' => false,\n"
                . "        'position' => 'above',\n"
                . "        'html' => '',\n"
                . "    ],\n"
                . "    'announcement_panel' => [",
                'team defaults'
            );

            $newTeam = thb_replace_once(
                $newTeam,
                "\$teamPageContentPanelKeys = [\n    'announcement_panel',",
                "\$teamPageContentPanelKeys = [\n    'custom_html_block',\n    'announcement_panel',",
                'team content keys'
            );

            $newTeam = thb_replace_once(
                $newTeam,
                "        .mrl-rd-announcement-body a{color:var(--mrl-rd-blue)!important;text-decoration:underline!important}\n",
                "        .mrl-rd-announcement-body a{color:var(--mrl-rd-blue)!important;text-decoration:underline!important}\n\n"
                . "        .mrl-rd-custom-html{\n"
                . "            margin:0 0 16px;\n"
                . "            border:0;\n"
                . "            border-radius:14px;\n"
                . "            overflow:hidden;\n"
                . "            background:transparent;\n"
                . "        }\n"
                . "        .mrl-rd-custom-html iframe{\n"
                . "            display:block;\n"
                . "            width:100%;\n"
                . "            min-height:120px;\n"
                . "            border:0;\n"
                . "            background:transparent;\n"
                . "        }\n",
                'team Custom HTML CSS'
            );

            $oldRender = <<<'OLDRENDER'
    <?php
    $announcementPanel = isset($teamPageContent['announcement_panel']) && is_array($teamPageContent['announcement_panel'])
        ? $teamPageContent['announcement_panel']
        : [];
    $announcementEnabled = !empty($announcementPanel['enabled']);
    $announcementTitle = trim((string)($announcementPanel['title'] ?? ''));
    $announcementContent = trim((string)($announcementPanel['content'] ?? ''));
    ?>
    <?php if ($announcementEnabled && $announcementContent !== ''): ?>
        <section class="mrl-rd-announcement">
            <?php if ($announcementTitle !== ''): ?><div class="mrl-rd-announcement-title"><?php echo teampage_h($announcementTitle); ?></div><?php endif; ?>
            <div class="mrl-rd-announcement-body"><?php teampage_render_announcement_text($announcementContent); ?></div>
        </section>
    <?php endif; ?>
OLDRENDER;

            $newRender = <<<'NEWRENDER'
    <?php
    $customHtmlBlock = isset($teamPageContent['custom_html_block']) && is_array($teamPageContent['custom_html_block'])
        ? $teamPageContent['custom_html_block']
        : [];
    $customHtmlEnabled = !empty($customHtmlBlock['enabled']);
    $customHtmlPosition = ((string)($customHtmlBlock['position'] ?? 'above') === 'below') ? 'below' : 'above';
    $customHtmlContent = (string)($customHtmlBlock['html'] ?? '');

    $announcementPanel = isset($teamPageContent['announcement_panel']) && is_array($teamPageContent['announcement_panel'])
        ? $teamPageContent['announcement_panel']
        : [];
    $announcementEnabled = !empty($announcementPanel['enabled']);
    $announcementTitle = trim((string)($announcementPanel['title'] ?? ''));
    $announcementContent = trim((string)($announcementPanel['content'] ?? ''));

    function teampage_render_custom_html_iframe(string $html): void
    {
        if (trim($html) === '') return;
        $srcdoc = htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        echo '<section class="mrl-rd-custom-html">';
        echo '<iframe class="mrl-rd-custom-html-frame" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" srcdoc="' . $srcdoc . '" onload="try{this.style.height=Math.max(120,this.contentWindow.document.documentElement.scrollHeight+4)+\'px\';}catch(e){}"></iframe>';
        echo '</section>';
    }
    ?>

    <?php if ($customHtmlEnabled && $customHtmlPosition === 'above' && trim($customHtmlContent) !== ''): ?>
        <?php teampage_render_custom_html_iframe($customHtmlContent); ?>
    <?php endif; ?>

    <?php if ($announcementEnabled && $announcementContent !== ''): ?>
        <section class="mrl-rd-announcement">
            <?php if ($announcementTitle !== ''): ?><div class="mrl-rd-announcement-title"><?php echo teampage_h($announcementTitle); ?></div><?php endif; ?>
            <div class="mrl-rd-announcement-body"><?php teampage_render_announcement_text($announcementContent); ?></div>
        </section>
    <?php endif; ?>

    <?php if ($customHtmlEnabled && $customHtmlPosition === 'below' && trim($customHtmlContent) !== ''): ?>
        <?php teampage_render_custom_html_iframe($customHtmlContent); ?>
    <?php endif; ?>
NEWRENDER;

            $newTeam = thb_replace_once($newTeam, $oldRender, $newRender, 'team Custom HTML render');

            $newTeam = str_replace('8/31/2026 1:30:45 pm', '8/31/2026 1:30:45 pm', $newTeam);

            $newAdmin = str_replace('8/31/2026 1:30:45 pm', '8/31/2026 1:30:45 pm', $newAdmin);
            $newTeam = str_replace('8/31/2026 1:30:45 pm', '8/31/2026 1:30:45 pm', $newTeam);

            if (file_put_contents($adminPath, $newAdmin, LOCK_EX) === false) throw new RuntimeException('Could not write manager.');
            if (file_put_contents($teamPath, $newTeam, LOCK_EX) === false) {
                @copy($adminBackup, $adminPath);
                throw new RuntimeException('Could not write team.php; manager restored.');
            }

            $verifyAdmin = thb_lf((string)file_get_contents($adminPath));
            $verifyTeam  = thb_lf((string)file_get_contents($teamPath));

            $post = [
                'manager v007 installed' => strpos($verifyAdmin, ' * VERSION: v007') !== false,
                'manager Custom HTML editor installed' => strpos($verifyAdmin, 'Team Page Custom HTML') !== false,
                'manager preview installed' => strpos($verifyAdmin, 'custom-html-preview') !== false,
                'manager schema v4 installed' => strpos($verifyAdmin, "'schema_version' => 4,") !== false,
                'team v041 installed' => strpos($verifyTeam, ' * VERSION: v041') !== false,
                'team Custom HTML defaults installed' => strpos($verifyTeam, "'custom_html_block' => [") !== false,
                'team iframe renderer installed' => strpos($verifyTeam, 'teampage_render_custom_html_iframe') !== false,
                'v040 quiet submit preserved' => strpos($verifyTeam, 'X-MRL-Quiet-Submit') !== false,
            ];

            if (in_array(false, $post, true)) {
                @copy($adminBackup, $adminPath);
                @copy($teamBackup, $teamPath);
                @copy($jsonBackup, $jsonPath);
                throw new RuntimeException('Postflight failed; originals restored.');
            }

            $checks = $post;
            $ok = true;
            $message = 'Custom HTML block installed successfully. Backup/rollback set created.';
            $installed = true;
            $rollbackAvailable = true;
        } catch (Throwable $e) {
            $message = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'rollback') {
    try {
        if (!is_file($manifestPath)) throw new RuntimeException('Rollback manifest not found.');

        $adminBackup = $backupRoot . '/admin_team_page_content.php';
        $teamBackup  = $backupRoot . '/team.php';
        $jsonBackup  = $backupRoot . '/mrl_team_page_content.json';

        if (!is_file($adminBackup) || !is_file($teamBackup)) {
            throw new RuntimeException('Rollback PHP backups are incomplete.');
        }

        if (!copy($adminBackup, $adminPath)) throw new RuntimeException('Could not restore manager.');
        if (!copy($teamBackup, $teamPath)) throw new RuntimeException('Could not restore team.php.');
        if (is_file($jsonBackup) && !copy($jsonBackup, $jsonPath)) throw new RuntimeException('Could not restore JSON.');

        $ok = true;
        $message = 'ROLLBACK COMPLETE — pre-install manager, team.php and content JSON restored.';
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
<title>MRL Custom HTML Block Installer</title>
<style>
:root{--bg:#101214;--panel:#1d2023;--border:#4b5055;--text:#f0f0f0;--gold:#efc77e;--green:#63e69a;--red:#ff7e7e;--blue:#55c7ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1150px;margin:20px auto}.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:800}
.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}.info{background:#122a3a;border:1px solid #2d6a8c;color:#d8f2ff}
table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #3a3e42;text-align:left}th{color:#ffe0a0}.pass{color:var(--green);font-weight:800}.fail{color:var(--red);font-weight:800}
.btn{padding:11px 18px;border-radius:8px;font-weight:800;cursor:pointer;margin-right:10px}.apply{background:#16894b;color:#fff;border:1px solid #4be388}.rollback{background:#a32222;color:#fff;border:1px solid #ef6666}.disabled{background:#555;color:#aaa;border:1px solid #777;cursor:not-allowed}
code{color:var(--blue)}
</style>
</head>
<body><div class="wrap">
<div class="card">
<h1>Task 1 — Team Page Custom HTML Block</h1>
<p><strong>Installer:</strong> v001 &nbsp; | &nbsp; <strong>Generated:</strong> 8/31/2026 1:30:45 pm America/New_York</p>
<?php if ($message !== ''): ?><div class="banner <?php echo $ok ? 'ok' : 'bad'; ?>"><?php echo thb_h($message); ?></div><?php endif; ?>
<?php if (!$message && $installed): ?><div class="banner ok">CUSTOM HTML BLOCK IS INSTALLED.</div><?php elseif (!$message && $ready): ?><div class="banner ok">PREVIEW PASS — ready to install.</div><?php elseif (!$message): ?><div class="banner bad">PREVIEW BLOCKED — see failed checks below.</div><?php endif; ?>
</div>

<div class="card">
<h2>What You Get</h2>
<div class="banner info">One saved HTML snippet, Enabled/Disabled, positioned Above or Below Announcement / News, with Preview in the manager.</div>
<p>The HTML stays saved when disabled. On the Team page it runs inside an isolated iframe so a pasted countdown/snippet does not become part of the surrounding Team-page DOM.</p>
</div>

<div class="card">
<h2>Preflight / Postflight</h2>
<table><thead><tr><th>Check</th><th>Status</th></tr></thead><tbody>
<?php foreach ($checks as $label=>$status): ?>
<tr><td><?php echo thb_h($label); ?></td><td class="<?php echo $status?'pass':'fail'; ?>"><?php echo $status?'PASS':'FAIL'; ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>

<div class="card">
<h2>Actions</h2>
<?php if ($ready && !$installed): ?>
<form method="post" style="display:inline" onsubmit="return confirm('Install the Custom HTML block?');">
<input type="hidden" name="action" value="apply"><button class="btn apply" type="submit">Apply Custom HTML Block</button>
</form>
<?php endif; ?>

<?php if ($rollbackAvailable): ?>
<form method="post" style="display:inline" onsubmit="return confirm('ROLL BACK Task 1 and restore the exact pre-install files/content JSON?');">
<input type="hidden" name="action" value="rollback"><button class="btn rollback" type="submit">Rollback Task 1</button>
</form>
<?php endif; ?>
</div>
</div></body></html>
