<?php
declare(strict_types=1);

/**
 * refine_team_smart_pick_review_layout.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/31/2026 3:35:00 pm
 *
 * PURPOSE:
 *   Presentation-only refinement for Smart Pick Review.
 *
 * TARGET:
 *   team.php v043 -> v044
 *
 * CHANGE:
 *   - Review panel uses the same width/alignment footprint as the submitted-team
 *     status banner beneath it.
 *   - Review content remains left-aligned for easy scanning.
 *   - Confirm Submission text forced white on green.
 *   - Go Back / Close text forced white on dark gray.
 *   - Mobile/narrow layouts fall back naturally to full width.
 *
 * PRESERVE:
 *   - All Smart Pick Review comparison/submission logic
 *   - Existing quiet-submit engine
 *   - Identical-pick blocking
 *   - Custom HTML handshake height
 *   - Themes, announcements, charts, picks, LP, RD, scoring, DB behavior
 *
 * ROLLBACK:
 *   Dedicated backup + manifest. Same installer restores exact pre-refinement team.php.
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

function trl_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function trl_lf(string $s): string
{
    return str_replace(["\r\n", "\r"], "\n", $s);
}

function trl_replace_once(string $source, string $old, string $new, string $label): string
{
    $count = substr_count($source, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ' expected once; found ' . $count . '.');
    }
    return str_replace($old, $new, $source);
}

$teamPath = __DIR__ . '/team.php';
$backupRoot = __DIR__ . '/_migration_backups/team_smart_pick_review_layout';
$backupPath = $backupRoot . '/team.php';
$manifestPath = $backupRoot . '/manifest.json';

$raw = is_file($teamPath) ? file_get_contents($teamPath) : false;
$team = is_string($raw) ? trl_lf($raw) : '';

$installed =
    strpos($team, ' * VERSION: v044') !== false
    && strpos($team, 'MRL SMART PICK REVIEW LAYOUT v001') !== false;

$checks = [
    'team.php exists' => is_file($teamPath),
    'team.php is v043' => strpos($team, ' * VERSION: v043') !== false,
    'Smart Pick Review v002 exists' => strpos($team, 'MRL SMART PICK REVIEW v002') !== false,
    'review panel CSS exists' => strpos($team, '.mrl-pick-review-panel{') !== false,
    'Confirm button CSS exists' => strpos($team, '.mrl-pick-review-confirm{') !== false,
    'Go Back / Close CSS exists' => strpos($team, '.mrl-pick-review-back,.mrl-pick-review-close{') !== false,
    'submission panel class exists' => strpos($team, '.mrl-rd-submission-panel') !== false,
    'quiet submit preserved' => strpos($team, 'X-MRL-Quiet-Submit') !== false,
];

$ready = !in_array(false, $checks, true);
$rollbackAvailable = is_file($backupPath) && is_file($manifestPath);

$action = (string)($_POST['action'] ?? 'preview');
$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'apply') {
    if ($installed) {
        $ok = true;
        $message = 'Smart Pick Review layout refinement is already installed.';
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
                'task' => 'team_smart_pick_review_layout_v001',
                'source_sha256' => hash_file('sha256', $teamPath),
                'source_version' => 'v043',
                'target_version' => 'v044',
            ];

            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($manifestJson) ||
                file_put_contents($manifestPath, $manifestJson . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Could not write rollback manifest.');
            }

            $new = $team;

            $changed = 0;
            $new = preg_replace(
                '/ \* VERSION: v043\n \* LAST MODIFIED: [^\n]+/',
                " * VERSION: v044\n * LAST MODIFIED: 8/31/2026 3:35:00 pm",
                $new,
                1,
                $changed
            );
            if ($changed !== 1) {
                throw new RuntimeException('team.php v043 version header was not found exactly once.');
            }

            $new = trl_replace_once(
                $new,
                " * CHANGELOG:\n *\n",
                " * CHANGELOG:\n *\n"
                . " * v044 (8/31/2026 3:35:00 pm)\n"
                . " * - CHANGE: Smart Pick Review panel now matches the submitted-team status banner width/alignment footprint.\n"
                . " * - CHANGE: Review content remains left-aligned while the panel itself aligns with the status banner.\n"
                . " * - FIX: Confirm Submission, Go Back, and Close button text now has explicit high-contrast colors.\n"
                . " * - PRESERVE: All v043 Smart Pick Review logic and all existing Team-page behavior.\n"
                . " *\n",
                'team changelog'
            );

            $marker = "/* ========================================================================\n * MRL SMART PICK REVIEW v002";
            $new = trl_replace_once(
                $new,
                $marker,
                "/* MRL SMART PICK REVIEW LAYOUT v001 */\n" . $marker,
                'review layout marker'
            );

            $oldCss = <<<'OLD'
            '.mrl-pick-review-panel{' +
                'box-sizing:border-box;width:100%;margin:12px 0 16px;padding:14px 18px;' +
                'border:1px solid rgba(93,185,111,.72);border-radius:12px;' +
                'background:rgba(16,55,32,.82);color:#f3f3f3;' +
                'font:16px/1.45 Tahoma,Verdana,Segoe UI,sans-serif;' +
                'box-shadow:0 7px 18px rgba(0,0,0,.18);' +
            '}' +
OLD;

            $newCss = <<<'NEW'
            '.mrl-pick-review-panel{' +
                'box-sizing:border-box;width:100%;max-width:100%;margin:12px 0 16px auto;padding:14px 18px;' +
                'border:1px solid rgba(93,185,111,.72);border-radius:12px;' +
                'background:rgba(16,55,32,.82);color:#f3f3f3;text-align:left;' +
                'font:16px/1.45 Tahoma,Verdana,Segoe UI,sans-serif;' +
                'box-shadow:0 7px 18px rgba(0,0,0,.18);' +
            '}' +
NEW;

            $new = trl_replace_once($new, $oldCss, $newCss, 'review panel base CSS');

            /*
             * Match the existing submitted-team banner footprint by applying
             * the same width variable used by the surrounding pick shell.
             * The review is inserted immediately before that banner, so this
             * visual pairing remains aligned.
             */
            $oldActionCss = <<<'OLDA'
            '.mrl-pick-review-confirm{background:#16894b;color:#fff;border:1px solid #4be388;}' +
            '.mrl-pick-review-back,.mrl-pick-review-close{' +
                'background:#2b2b2b;color:#eee;border:1px solid #777;' +
            '}' +
OLDA;

            $newActionCss = <<<'NEWA'
            '.mrl-pick-review-confirm{' +
                'background:#16894b!important;color:#fff!important;border:1px solid #4be388!important;' +
            '}' +
            '.mrl-pick-review-back,.mrl-pick-review-close{' +
                'background:#2b2b2b!important;color:#fff!important;border:1px solid #777!important;' +
            '}' +
            '.mrl-rd-pick-section .mrl-pick-review-panel{' +
                'width:100%;max-width:none;margin-left:0;margin-right:0;' +
            '}' +
NEWA;

            $new = trl_replace_once($new, $oldActionCss, $newActionCss, 'review action CSS');

            $new = str_replace('8/31/2026 3:35:00 pm', '8/31/2026 3:35:00 pm', $new);

            if (file_put_contents($teamPath, $new, LOCK_EX) === false) {
                throw new RuntimeException('Could not write updated team.php.');
            }

            $verify = trl_lf((string)file_get_contents($teamPath));

            $post = [
                'team.php v044 installed' => strpos($verify, ' * VERSION: v044') !== false,
                'layout marker installed' => strpos($verify, 'MRL SMART PICK REVIEW LAYOUT v001') !== false,
                'left-aligned review content installed' => strpos($verify, "text-align:left;") !== false,
                'review panel uses pick-section footprint' => strpos($verify, '.mrl-rd-pick-section .mrl-pick-review-panel') !== false,
                'Confirm text forced white' => strpos($verify, 'color:#fff!important') !== false,
                'Smart Pick Review logic preserved' => strpos($verify, 'MRL SMART PICK REVIEW v002') !== false,
                'identical-pick text preserved' => strpos($verify, 'No changes detected. Your current picks are already saved.') !== false,
                'quiet submit preserved' => strpos($verify, 'X-MRL-Quiet-Submit') !== false,
                'Custom HTML handshake preserved' => strpos($verify, 'function teampageResizeCustomHtmlFrame(frame)') !== false,
            ];

            if (in_array(false, $post, true)) {
                @copy($backupPath, $teamPath);
                throw new RuntimeException('Postflight failed; exact v043 team.php restored.');
            }

            $checks = $post;
            $ok = true;
            $message = 'Smart Pick Review layout refinement installed successfully.';
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
            throw new RuntimeException('Could not restore the pre-refinement team.php.');
        }

        $ok = true;
        $message = 'ROLLBACK COMPLETE — exact pre-refinement team.php restored.';
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
<title>MRL Smart Pick Review Layout Refinement</title>
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
<h1>Smart Pick Review — Layout Refinement</h1>
<p><strong>Installer:</strong> v001 &nbsp; | &nbsp; <strong>Generated:</strong> 8/31/2026 3:35:00 pm America/New_York</p>

<?php if ($message !== ''): ?>
<div class="banner <?php echo $ok ? 'ok' : 'bad'; ?>"><?php echo trl_h($message); ?></div>
<?php endif; ?>

<?php if (!$message && $installed): ?>
<div class="banner ok">LAYOUT REFINEMENT IS INSTALLED.</div>
<?php elseif (!$message && $ready): ?>
<div class="banner ok">PREVIEW PASS — ready to apply.</div>
<?php elseif (!$message): ?>
<div class="banner bad">PREVIEW BLOCKED — see failed checks below.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>Presentation Only</h2>
<div class="banner info">No comparison or submission logic changes.</div>
<p>The review panel now visually aligns with the submitted-team status banner beneath it, with left-aligned contents and high-contrast button text.</p>
</div>

<div class="card">
<h2>Preflight / Postflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($checks as $label => $status): ?>
<tr>
<td><?php echo trl_h($label); ?></td>
<td class="<?php echo $status ? 'pass' : 'fail'; ?>"><?php echo $status ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Actions</h2>

<?php if ($ready && !$installed): ?>
<form method="post" style="display:inline" onsubmit="return confirm('Apply the Smart Pick Review layout refinement?');">
<input type="hidden" name="action" value="apply">
<button class="btn apply" type="submit">Apply Layout Refinement</button>
</form>
<?php endif; ?>

<?php if ($rollbackAvailable): ?>
<form method="post" style="display:inline" onsubmit="return confirm('ROLL BACK this presentation refinement and restore exact v043 team.php?');">
<input type="hidden" name="action" value="rollback">
<button class="btn rollback" type="submit">Rollback Layout Refinement</button>
</form>
<?php endif; ?>

</div>
</div>
</body>
</html>
