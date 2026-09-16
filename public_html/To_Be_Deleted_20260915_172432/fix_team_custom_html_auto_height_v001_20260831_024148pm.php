<?php
declare(strict_types=1);

/**
 * fix_team_custom_html_auto_height.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/31/2026 2:41:48 pm
 *
 * PURPOSE:
 *   Fix the Custom HTML iframe height on team.php so snippets that grow after
 *   initial load (such as the S4 countdown after its schedule fetch) expand
 *   automatically instead of showing an internal scrollbar / clipped content.
 *
 * TARGET:
 *   team.php v041 -> v042
 *
 * CHANGE:
 *   - Replaces the one-time iframe height calculation with a reusable parent
 *     page auto-height helper.
 *   - Uses MutationObserver plus ResizeObserver when available.
 *   - Keeps the iframe transparent; each HTML snippet controls its own background.
 *   - Hides iframe scrollbars because the frame itself now grows to its content.
 *
 * PRESERVE:
 *   - Custom HTML content and JSON
 *   - Custom HTML Enabled/Disabled and Above/Below Announcement position
 *   - Announcement / News
 *   - Themes
 *   - Pick forms, LP, RD, quiet submission, scoring and database behavior
 *
 * ROLLBACK:
 *   Creates a dedicated backup and manifest. This installer can restore the
 *   exact pre-fix team.php.
 *
 * NO DATABASE CHANGES.
 * NO JSON CHANGES.
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

function tahr_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tahr_lf(string $s): string
{
    return str_replace(["\r\n", "\r"], "\n", $s);
}

function tahr_replace_once(string $source, string $old, string $new, string $label): string
{
    $count = substr_count($source, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ' expected once; found ' . $count . '.');
    }
    return str_replace($old, $new, $source);
}

$teamPath = __DIR__ . '/team.php';
$backupRoot = __DIR__ . '/_migration_backups/team_custom_html_auto_height';
$backupPath = $backupRoot . '/team.php';
$manifestPath = $backupRoot . '/manifest.json';

$raw = is_file($teamPath) ? file_get_contents($teamPath) : false;
$team = is_string($raw) ? tahr_lf($raw) : '';

$installed =
    strpos($team, ' * VERSION: v042') !== false
    && strpos($team, 'function teampageCustomHtmlFrameLoaded(frame)') !== false
    && strpos($team, 'data-mrl-auto-height="1"') !== false;

$checks = [
    'team.php exists' => is_file($teamPath),
    'team.php is v041' => strpos($team, ' * VERSION: v041') !== false,
    'Custom HTML renderer exists' => strpos($team, 'function teampage_render_custom_html_iframe(string $html): void') !== false,
    'Custom HTML iframe class exists' => strpos($team, 'mrl-rd-custom-html-frame') !== false,
    'current one-time onload sizing exists' => strpos($team, 'this.contentWindow.document.documentElement.scrollHeight') !== false,
    'Custom HTML shell CSS exists' => strpos($team, '.mrl-rd-custom-html iframe{') !== false,
    'quiet-submit code remains present' => strpos($team, 'X-MRL-Quiet-Submit') !== false,
];

$ready = !in_array(false, $checks, true);
$rollbackAvailable = is_file($backupPath) && is_file($manifestPath);

$action = (string)($_POST['action'] ?? 'preview');
$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'apply') {
    if ($installed) {
        $ok = true;
        $message = 'Custom HTML auto-height fix is already installed.';
    } elseif (!$ready) {
        $message = 'Apply blocked because one or more preflight checks failed.';
    } else {
        try {
            if (!is_dir($backupRoot) && !mkdir($backupRoot, 0755, true) && !is_dir($backupRoot)) {
                throw new RuntimeException('Could not create backup folder.');
            }

            if (!copy($teamPath, $backupPath)) {
                throw new RuntimeException('Could not back up team.php.');
            }

            $manifest = [
                'created_at' => date('Y-m-d H:i:s'),
                'task' => 'team_custom_html_auto_height',
                'source_sha256' => hash_file('sha256', $teamPath),
                'source_version' => 'v041',
                'target_version' => 'v042',
            ];

            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($manifestJson) ||
                file_put_contents($manifestPath, $manifestJson . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Could not write rollback manifest.');
            }

            $new = $team;

            $changed = 0;
            $new = preg_replace(
                '/ \* VERSION: v041\n \* LAST MODIFIED: [^\n]+/',
                " * VERSION: v042\n * LAST MODIFIED: 8/31/2026 2:41:48 pm",
                $new,
                1,
                $changed
            );
            if ($changed !== 1) {
                throw new RuntimeException('team.php v041 version header was not found exactly once.');
            }

            $new = tahr_replace_once(
                $new,
                " * CHANGELOG:\n *\n",
                " * CHANGELOG:\n *\n"
                . " * v042 (8/31/2026 2:41:48 pm)\n"
                . " * - FIX: Custom HTML iframes now auto-resize after dynamic content changes instead of clipping/showing an internal scrollbar.\n"
                . " * - NEW: Uses MutationObserver and ResizeObserver when available, with a lightweight fallback resize pass.\n"
                . " * - PRESERVE: Custom HTML remains transparent by default; each snippet controls its own background.\n"
                . " * - PRESERVE: v041 Custom HTML management, v040 quiet pick submission, themes, announcements, charts, picks, LP, RD, scoring, and database behavior.\n"
                . " *\n",
                'team changelog'
            );

            $oldIframe = <<<'OLD'
        echo '<iframe class="mrl-rd-custom-html-frame" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" srcdoc="' . $srcdoc . '" onload="try{this.style.height=Math.max(120,this.contentWindow.document.documentElement.scrollHeight+4)+\'px\';}catch(e){}"></iframe>';
OLD;

            $newIframe = <<<'NEW'
        echo '<iframe class="mrl-rd-custom-html-frame" data-mrl-auto-height="1" scrolling="no" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" srcdoc="' . $srcdoc . '" onload="teampageCustomHtmlFrameLoaded(this)"></iframe>';
NEW;

            $new = tahr_replace_once($new, $oldIframe, $newIframe, 'Custom HTML iframe renderer');

            $oldCss = <<<'OLDCSS'
        .mrl-rd-custom-html iframe{
            display:block;
            width:100%;
            min-height:120px;
            border:0;
            background:transparent;
        }
OLDCSS;

            $newCss = <<<'NEWCSS'
        .mrl-rd-custom-html iframe{
            display:block;
            width:100%;
            min-height:120px;
            border:0;
            background:transparent;
            overflow:hidden;
        }
NEWCSS;

            $new = tahr_replace_once($new, $oldCss, $newCss, 'Custom HTML iframe CSS');

            $autoHeightScript = <<<'SCRIPT'

<script>
/*
 * Custom HTML iframe auto-height.
 * The iframe uses srcdoc + allow-same-origin, so team.php can safely measure
 * its own isolated snippet document without injecting that snippet into the
 * Team page DOM.
 */
function teampageCustomHtmlFrameLoaded(frame) {
    if (!frame || frame.dataset.mrlAutoHeightBound === '1') {
        if (frame && typeof frame._mrlResizeNow === 'function') {
            frame._mrlResizeNow();
        }
        return;
    }

    frame.dataset.mrlAutoHeightBound = '1';

    var pending = false;

    function resizeNow() {
        pending = false;

        try {
            var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
            if (!doc) return;

            var body = doc.body;
            var root = doc.documentElement;

            if (body) {
                body.style.overflow = 'hidden';
            }
            if (root) {
                root.style.overflow = 'hidden';
            }

            var height = 0;

            if (body) {
                height = Math.max(
                    height,
                    body.scrollHeight || 0,
                    body.offsetHeight || 0
                );
            }

            if (root) {
                height = Math.max(
                    height,
                    root.scrollHeight || 0,
                    root.offsetHeight || 0
                );
            }

            frame.style.height = Math.max(120, height + 4) + 'px';
        } catch (error) {
            // Leave the current iframe height alone if measurement is unavailable.
        }
    }

    function requestResize() {
        if (pending) return;
        pending = true;

        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(resizeNow);
        } else {
            window.setTimeout(resizeNow, 0);
        }
    }

    frame._mrlResizeNow = requestResize;

    requestResize();

    try {
        var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
        if (!doc || !doc.documentElement) return;

        if (window.MutationObserver) {
            var mutationObserver = new MutationObserver(requestResize);
            mutationObserver.observe(doc.documentElement, {
                subtree: true,
                childList: true,
                characterData: true,
                attributes: true
            });
            frame._mrlMutationObserver = mutationObserver;
        }

        if (window.ResizeObserver) {
            var resizeObserver = new ResizeObserver(requestResize);
            resizeObserver.observe(doc.documentElement);

            if (doc.body) {
                resizeObserver.observe(doc.body);
            }

            frame._mrlResizeObserver = resizeObserver;
        } else {
            // Older-browser fallback. Tiny cost, and only while this frame exists.
            frame._mrlHeightTimer = window.setInterval(requestResize, 1000);
        }
    } catch (error) {
        // Initial resize already ran; no further action needed.
    }
}
</script>
SCRIPT;

            $new = tahr_replace_once(
                $new,
                "</body>",
                $autoHeightScript . "\n</body>",
                'closing body / auto-height script'
            );

            $new = str_replace('8/31/2026 2:41:48 pm', '8/31/2026 2:41:48 pm', $new);

            if (file_put_contents($teamPath, $new, LOCK_EX) === false) {
                throw new RuntimeException('Could not write updated team.php.');
            }

            $verify = tahr_lf((string)file_get_contents($teamPath));

            $post = [
                'team.php v042 installed' => strpos($verify, ' * VERSION: v042') !== false,
                'auto-height loader installed' => strpos($verify, 'teampageCustomHtmlFrameLoaded(this)') !== false,
                'MutationObserver installed' => strpos($verify, 'new MutationObserver(requestResize)') !== false,
                'ResizeObserver installed' => strpos($verify, 'new ResizeObserver(requestResize)') !== false,
                'iframe scrollbars disabled' => strpos($verify, 'scrolling="no"') !== false,
                'transparent iframe preserved' => strpos($verify, 'background:transparent;') !== false,
                'Custom HTML renderer preserved' => strpos($verify, 'teampage_render_custom_html_iframe') !== false,
                'quiet-submit layer preserved' => strpos($verify, 'X-MRL-Quiet-Submit') !== false,
            ];

            if (in_array(false, $post, true)) {
                @copy($backupPath, $teamPath);
                throw new RuntimeException('Postflight failed; exact pre-fix team.php restored.');
            }

            $checks = $post;
            $ok = true;
            $message = 'Custom HTML auto-height fix installed successfully.';
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

        if (!copy($backupPath, $teamPath)) {
            throw new RuntimeException('Could not restore the pre-fix team.php.');
        }

        $ok = true;
        $message = 'ROLLBACK COMPLETE — exact pre-auto-height team.php restored.';
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
<title>MRL Custom HTML Auto-Height Fix</title>
<style>
:root{--bg:#101214;--panel:#1d2023;--border:#4b5055;--text:#f0f0f0;--gold:#efc77e;--green:#63e69a;--red:#ff7e7e;--blue:#55c7ff}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1100px;margin:20px auto}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:16px}
h1,h2{color:var(--gold);margin-top:0}
.banner{padding:12px 15px;border-radius:10px;margin:12px 0;font-weight:800}
.ok{background:#123a2a;border:1px solid #2b815b;color:#d9ffea}
.bad{background:#4a1818;border:1px solid #a64e4e;color:#ffd4d4}
.info{background:#122a3a;border:1px solid #2d6a8c;color:#d8f2ff}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid #3a3e42;text-align:left}
th{color:#ffe0a0}
.pass{color:var(--green);font-weight:800}
.fail{color:var(--red);font-weight:800}
.btn{padding:11px 18px;border-radius:8px;font-weight:800;cursor:pointer;margin-right:10px}
.apply{background:#16894b;color:#fff;border:1px solid #4be388}
.rollback{background:#a32222;color:#fff;border:1px solid #ef6666}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>Fix — Team Custom HTML Auto-Height</h1>
<p><strong>Installer:</strong> v001 &nbsp; | &nbsp; <strong>Generated:</strong> 8/31/2026 2:41:48 pm America/New_York</p>

<?php if ($message !== ''): ?>
<div class="banner <?php echo $ok ? 'ok' : 'bad'; ?>"><?php echo tahr_h($message); ?></div>
<?php endif; ?>

<?php if (!$message && $installed): ?>
<div class="banner ok">AUTO-HEIGHT FIX IS INSTALLED.</div>
<?php elseif (!$message && $ready): ?>
<div class="banner ok">PREVIEW PASS — ready to apply.</div>
<?php elseif (!$message): ?>
<div class="banner bad">PREVIEW BLOCKED — see failed checks below.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>What This Fix Does</h2>
<div class="banner info">The Custom HTML iframe will grow again whenever its contents change after load.</div>
<p>Your S4 countdown initially renders a small “Loading…” state and then becomes taller after the schedule JSON arrives. The old one-time height check happened too early. This fix keeps watching the snippet and automatically resizes the iframe when that happens.</p>
<p><strong>Background remains snippet-controlled.</strong> The Custom HTML system stays transparent.</p>
</div>

<div class="card">
<h2>Preflight / Postflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($checks as $label => $status): ?>
<tr>
<td><?php echo tahr_h($label); ?></td>
<td class="<?php echo $status ? 'pass' : 'fail'; ?>"><?php echo $status ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Actions</h2>

<?php if ($ready && !$installed): ?>
<form method="post" style="display:inline" onsubmit="return confirm('Apply the Custom HTML auto-height fix?');">
<input type="hidden" name="action" value="apply">
<button class="btn apply" type="submit">Apply Auto-Height Fix</button>
</form>
<?php endif; ?>

<?php if ($rollbackAvailable): ?>
<form method="post" style="display:inline" onsubmit="return confirm('ROLL BACK this fix and restore the exact pre-fix team.php?');">
<input type="hidden" name="action" value="rollback">
<button class="btn rollback" type="submit">Rollback Auto-Height Fix</button>
</form>
<?php endif; ?>

</div>
</div>
</body>
</html>
