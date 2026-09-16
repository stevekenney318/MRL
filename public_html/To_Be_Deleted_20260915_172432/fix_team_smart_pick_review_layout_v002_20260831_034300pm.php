<?php
declare(strict_types=1);

/**
 * fix_team_smart_pick_review_layout.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/31/2026 3:43:00 pm
 *
 * PURPOSE:
 *   Correct the first Smart Pick Review layout refinement.
 *
 * TARGET:
 *   team.php v044 -> v045
 *
 * FIXES:
 *   - The previous refinement accidentally left the review panel at width:100%.
 *   - Review panel is now a compact right-aligned panel, approximately the
 *     same visual footprint as the small submission confirmation banner.
 *   - Narrow screens fall back to full width.
 *   - Button text color selectors now outrank the Team page's generic
 *     ".mrl-pick-panel button { color:#000!important; }" rule.
 *
 * PRESERVE:
 *   - ALL Smart Pick Review functionality
 *   - Existing quiet-submit engine
 *   - Identical-pick blocking
 *   - Custom HTML handshake height
 *   - Themes, announcements, charts, picks, LP, RD, scoring and DB behavior
 *
 * ROLLBACK:
 *   Dedicated backup + manifest. Same installer restores exact v044 team.php.
 *
 * NO DATABASE CHANGES.
 * NO JSON CHANGES.
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

function tsrl2_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tsrl2_lf(string $s): string
{
    return str_replace(["\r\n", "\r"], "\n", $s);
}

function tsrl2_replace_once(string $source, string $old, string $new, string $label): string
{
    $count = substr_count($source, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ' expected once; found ' . $count . '.');
    }
    return str_replace($old, $new, $source);
}

$teamPath = __DIR__ . '/team.php';
$backupRoot = __DIR__ . '/_migration_backups/team_smart_pick_review_layout_v002';
$backupPath = $backupRoot . '/team.php';
$manifestPath = $backupRoot . '/manifest.json';

$raw = is_file($teamPath) ? file_get_contents($teamPath) : false;
$team = is_string($raw) ? tsrl2_lf($raw) : '';

$installed =
    strpos($team, ' * VERSION: v045') !== false
    && strpos($team, 'MRL SMART PICK REVIEW LAYOUT v002') !== false;

$checks = [
    'team.php exists' => is_file($teamPath),
    'team.php is v044' => strpos($team, ' * VERSION: v044') !== false,
    'Smart Pick Review v002 exists' => strpos($team, 'MRL SMART PICK REVIEW v002') !== false,
    'first layout refinement exists' => strpos($team, 'MRL SMART PICK REVIEW LAYOUT v001') !== false,
    'full-width override exists' => strpos($team, ".mrl-rd-pick-section .mrl-pick-review-panel{") !== false,
    'generic black button rule exists' => strpos($team, '.mrl-rd-pick-section .mrl-pick-panel button') !== false,
    'quiet submit preserved' => strpos($team, 'X-MRL-Quiet-Submit') !== false,
    'Custom HTML handshake preserved' => strpos($team, 'function teampageResizeCustomHtmlFrame(frame)') !== false,
];

$ready = !in_array(false, $checks, true);
$rollbackAvailable = is_file($backupPath) && is_file($manifestPath);

$action = (string)($_POST['action'] ?? 'preview');
$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'apply') {
    if ($installed) {
        $ok = true;
        $message = 'Smart Pick Review layout fix v002 is already installed.';
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
                'task' => 'team_smart_pick_review_layout_v002',
                'source_sha256' => hash_file('sha256', $teamPath),
                'source_version' => 'v044',
                'target_version' => 'v045',
            ];

            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($manifestJson) ||
                file_put_contents($manifestPath, $manifestJson . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Could not write rollback manifest.');
            }

            $new = $team;

            $changed = 0;
            $new = preg_replace(
                '/ \* VERSION: v044\n \* LAST MODIFIED: [^\n]+/',
                " * VERSION: v045\n * LAST MODIFIED: 8/31/2026 3:43:00 pm",
                $new,
                1,
                $changed
            );
            if ($changed !== 1) {
                throw new RuntimeException('team.php v044 version header was not found exactly once.');
            }

            $new = tsrl2_replace_once(
                $new,
                " * CHANGELOG:\n *\n",
                " * CHANGELOG:\n *\n"
                . " * v045 (8/31/2026 3:43:00 pm)\n"
                . " * - FIX: Smart Pick Review is now compact and right-aligned instead of full width.\n"
                . " * - FIX: Confirm / Go Back / Close text colors now override the Team page's generic black button rule.\n"
                . " * - RESPONSIVE: Review returns to full width on narrower screens.\n"
                . " * - PRESERVE: All v044 review logic and all other Team-page behavior.\n"
                . " *\n",
                'team changelog'
            );

            $new = tsrl2_replace_once(
                $new,
                '/* MRL SMART PICK REVIEW LAYOUT v001 */',
                '/* MRL SMART PICK REVIEW LAYOUT v002 */',
                'layout marker'
            );

            $oldOverride = <<<'OLD'
            '.mrl-rd-pick-section .mrl-pick-review-panel{' +
                'width:100%;max-width:none;margin-left:0;margin-right:0;' +
            '}' +
OLD;

            $newOverride = <<<'NEW'
            '.mrl-rd-pick-section .mrl-pick-review-panel{' +
                'width:38%;max-width:620px;min-width:430px;margin-left:auto;margin-right:0;' +
            '}' +
            '.mrl-rd-pick-section .mrl-pick-panel .mrl-pick-review-confirm{' +
                'background:#16894b!important;color:#fff!important;border:1px solid #4be388!important;' +
            '}' +
            '.mrl-rd-pick-section .mrl-pick-panel .mrl-pick-review-back,' +
            '.mrl-rd-pick-section .mrl-pick-panel .mrl-pick-review-close{' +
                'background:#2b2b2b!important;color:#fff!important;border:1px solid #777!important;' +
            '}' +
            '@media(max-width:1000px){' +
                '.mrl-rd-pick-section .mrl-pick-review-panel{' +
                    'width:100%;max-width:none;min-width:0;margin-left:0;margin-right:0;' +
                '}' +
            '}' +
NEW;

            $new = tsrl2_replace_once($new, $oldOverride, $newOverride, 'review panel width/button override');

            $new = str_replace('8/31/2026 3:43:00 pm', '8/31/2026 3:43:00 pm', $new);

            if (file_put_contents($teamPath, $new, LOCK_EX) === false) {
                throw new RuntimeException('Could not write updated team.php.');
            }

            $verify = tsrl2_lf((string)file_get_contents($teamPath));
            $post = [
                'team.php v045 installed' => strpos($verify, ' * VERSION: v045') !== false,
                'layout v002 marker installed' => strpos($verify, 'MRL SMART PICK REVIEW LAYOUT v002') !== false,
                'compact 38% width installed' => strpos($verify, 'width:38%;max-width:620px;min-width:430px') !== false,
                'right alignment installed' => strpos($verify, 'margin-left:auto;margin-right:0') !== false,
                'responsive full-width fallback installed' => strpos($verify, '@media(max-width:1000px)') !== false,
                'high-specificity Confirm white text installed' => strpos($verify, '.mrl-rd-pick-section .mrl-pick-panel .mrl-pick-review-confirm') !== false,
                'high-specificity Go Back / Close white text installed' => strpos($verify, '.mrl-rd-pick-section .mrl-pick-panel .mrl-pick-review-back') !== false,
                'Smart Pick Review logic preserved' => strpos($verify, 'MRL SMART PICK REVIEW v002') !== false,
                'identical-pick block preserved' => strpos($verify, 'No changes detected. Your current picks are already saved.') !== false,
                'quiet submit preserved' => strpos($verify, 'X-MRL-Quiet-Submit') !== false,
                'Custom HTML handshake preserved' => strpos($verify, 'function teampageResizeCustomHtmlFrame(frame)') !== false,
            ];

            if (in_array(false, $post, true)) {
                @copy($backupPath, $teamPath);
                throw new RuntimeException('Postflight failed; exact v044 team.php restored.');
            }

            $checks = $post;
            $ok = true;
            $message = 'Smart Pick Review layout fix v002 installed successfully.';
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
            throw new RuntimeException('Could not restore v044 team.php.');
        }

        $ok = true;
        $message = 'ROLLBACK COMPLETE — exact v044 team.php restored.';
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
<title>MRL Smart Pick Review Layout Fix v002</title>
<style>
:root{--bg:#101214;--panel:#1d2023;--border:#4b5055;--text:#f0f0f0;--gold:#efc77e;--green:#63e69a;--red:#ff7e7e}
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
<h1>Smart Pick Review — Layout Fix v002</h1>
<p><strong>Generated:</strong> 8/31/2026 3:43:00 pm America/New_York</p>

<?php if ($message !== ''): ?>
<div class="banner <?php echo $ok ? 'ok' : 'bad'; ?>"><?php echo tsrl2_h($message); ?></div>
<?php endif; ?>

<?php if (!$message && $installed): ?>
<div class="banner ok">LAYOUT FIX v002 IS INSTALLED.</div>
<?php elseif (!$message && $ready): ?>
<div class="banner ok">PREVIEW PASS — ready to apply.</div>
<?php elseif (!$message): ?>
<div class="banner bad">PREVIEW BLOCKED — see failed checks below.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>What Was Wrong</h2>
<div class="banner info">The first refinement accidentally contained a 100% width override, and the Team page's existing black-button rule had higher CSS specificity.</div>
<p>This version explicitly fixes both issues. No review/submission behavior is changed.</p>
</div>

<div class="card">
<h2>Preflight / Postflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($checks as $label => $status): ?>
<tr>
<td><?php echo tsrl2_h($label); ?></td>
<td class="<?php echo $status ? 'pass' : 'fail'; ?>"><?php echo $status ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Actions</h2>

<?php if ($ready && !$installed): ?>
<form method="post" style="display:inline" onsubmit="return confirm('Apply Smart Pick Review Layout Fix v002?');">
<input type="hidden" name="action" value="apply">
<button class="btn apply" type="submit">Apply Layout Fix v002</button>
</form>
<?php endif; ?>

<?php if ($rollbackAvailable): ?>
<form method="post" style="display:inline" onsubmit="return confirm('ROLL BACK this fix and restore exact v044 team.php?');">
<input type="hidden" name="action" value="rollback">
<button class="btn rollback" type="submit">Rollback Layout Fix v002</button>
</form>
<?php endif; ?>

</div>
</div>
</body>
</html>
