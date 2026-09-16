<?php
declare(strict_types=1);

/**
 * install_team_smart_pick_review.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/31/2026 3:24:34 pm
 *
 * PURPOSE:
 *   Add a quiet, inline pre-submit review panel to Team pick forms.
 *
 * TARGET:
 *   team.php v042 -> v043
 *
 * BEHAVIOR:
 *   - User clicks Submit Picks.
 *   - Native/form-specific validation still runs first.
 *   - A quiet inline review panel appears; NO database write has happened yet.
 *   - Panel names:
 *       * New picks
 *       * Exact old -> new changes
 *       * Unchanged picks
 *   - If all selected picks are identical to the stored/current values:
 *       "No changes detected. Your current picks are already saved."
 *     and NOTHING is submitted.
 *   - Confirm Submission re-submits the exact form through the existing
 *     v040 quiet-submit layer and existing submit-team-picks.php backend.
 *   - Go Back closes the review with no submission.
 *
 * IMPLEMENTATION:
 *   This does NOT replace or duplicate the existing quiet-submit engine.
 *   It attaches directly to forms posting to /submit-team-picks.php.
 *   On confirmation it deliberately hands the form back to the existing
 *   quiet-submit listener.
 *
 * PRESERVE:
 *   - submit-team-picks.php untouched
 *   - Existing v040 quiet background submission
 *   - Existing success banner
 *   - SEG / LP / RD server logic
 *   - RD's rdPrepareSubmit() / canonical driver fields
 *   - v042 Custom HTML handshake height behavior
 *   - Themes, announcements, charts, scoring, DB behavior
 *
 * ROLLBACK:
 *   Dedicated backup + manifest. Same installer restores exact pre-install team.php.
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

function tspr_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tspr_lf(string $s): string
{
    return str_replace(["\r\n", "\r"], "\n", $s);
}

function tspr_replace_once(string $source, string $old, string $new, string $label): string
{
    $count = substr_count($source, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ' expected once; found ' . $count . '.');
    }
    return str_replace($old, $new, $source);
}

$teamPath = __DIR__ . '/team.php';
$backupRoot = __DIR__ . '/_migration_backups/team_smart_pick_review_v002';
$backupPath = $backupRoot . '/team.php';
$manifestPath = $backupRoot . '/manifest.json';

$raw = is_file($teamPath) ? file_get_contents($teamPath) : false;
$team = is_string($raw) ? tspr_lf($raw) : '';

$installed =
    strpos($team, ' * VERSION: v043') !== false
    && strpos($team, 'MRL SMART PICK REVIEW v002') !== false
    && strpos($team, 'No changes detected. Your current picks are already saved.') !== false;

$checks = [
    'team.php exists' => is_file($teamPath),
    'team.php is v042' => strpos($team, ' * VERSION: v042') !== false,
    'safe Custom HTML handshake is present' => strpos($team, 'function teampageResizeCustomHtmlFrame(frame)') !== false,
    'quiet-submit header marker is present' => strpos($team, 'X-MRL-Quiet-Submit') !== false,
    'existing quiet success text is present' => strpos($team, '✓ Your picks have been submitted.') !== false,
    'submit-team-picks form path is represented' => strpos($team, 'submit-team-picks.php') !== false,
    'smart review is not already present' => strpos($team, 'MRL SMART PICK REVIEW v002') === false,
];

$ready = !in_array(false, $checks, true);
$rollbackAvailable = is_file($backupPath) && is_file($manifestPath);

$action = (string)($_POST['action'] ?? 'preview');
$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'apply') {
    if ($installed) {
        $ok = true;
        $message = 'Smart Pick Review v002 is already installed.';
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
                'task' => 'team_smart_pick_review_v002',
                'source_sha256' => hash_file('sha256', $teamPath),
                'source_version' => 'v042',
                'target_version' => 'v043',
            ];

            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($manifestJson) ||
                file_put_contents($manifestPath, $manifestJson . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Could not write rollback manifest.');
            }

            $new = $team;

            $changed = 0;
            $new = preg_replace(
                '/ \* VERSION: v042\n \* LAST MODIFIED: [^\n]+/',
                " * VERSION: v043\n * LAST MODIFIED: 8/31/2026 3:24:34 pm",
                $new,
                1,
                $changed
            );
            if ($changed !== 1) {
                throw new RuntimeException('team.php v042 version header was not found exactly once.');
            }

            $new = tspr_replace_once(
                $new,
                " * CHANGELOG:\n *\n",
                " * CHANGELOG:\n *\n"
                . " * v043 (8/31/2026 3:24:34 pm)\n"
                . " * - NEW: Adds a quiet inline Review Your Submission step before SEG / LP / RD pick writes.\n"
                . " * - NEW: Shows exact new picks, old -> new changes, and unchanged picks by driver group.\n"
                . " * - NEW: Identical selections are detected and not submitted.\n"
                . " * - PRESERVE: Existing v040 quiet-submit engine remains authoritative after Confirm Submission.\n"
                . " * - PRESERVE: v042 Custom HTML handshake height behavior, themes, announcements, charts, scoring, and DB logic.\n"
                . " *\n",
                'team changelog'
            );

            $reviewScript = <<<'SCRIPT'

<script>
/* ========================================================================
 * MRL SMART PICK REVIEW v002
 *
 * This layer only pauses a pick form before the existing quiet-submit layer.
 * It never writes picks itself and never calls submit-team-picks.php directly.
 * ====================================================================== */
(function () {
    'use strict';

    var reviewBaselines = new WeakMap();

    function addReviewStyles() {
        if (document.getElementById('mrl-pick-review-style')) return;

        var style = document.createElement('style');
        style.id = 'mrl-pick-review-style';
        style.textContent =
            '.mrl-pick-review-panel{' +
                'box-sizing:border-box;width:100%;margin:12px 0 16px;padding:14px 18px;' +
                'border:1px solid rgba(93,185,111,.72);border-radius:12px;' +
                'background:rgba(16,55,32,.82);color:#f3f3f3;' +
                'font:16px/1.45 Tahoma,Verdana,Segoe UI,sans-serif;' +
                'box-shadow:0 7px 18px rgba(0,0,0,.18);' +
            '}' +
            '.mrl-pick-review-panel h3{' +
                'margin:0 0 10px;color:#ffe2a0;font:800 19px/1.25 Tahoma,Verdana,Segoe UI,sans-serif;' +
            '}' +
            '.mrl-pick-review-label{' +
                'margin-top:8px;color:#ffe2a0;font-weight:800;' +
            '}' +
            '.mrl-pick-review-panel ul{margin:5px 0 10px;padding-left:24px;}' +
            '.mrl-pick-review-panel li{margin:3px 0;}' +
            '.mrl-pick-review-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;}' +
            '.mrl-pick-review-actions button{' +
                'min-height:38px;padding:8px 14px;border-radius:7px;font-weight:800;cursor:pointer;' +
            '}' +
            '.mrl-pick-review-confirm{background:#16894b;color:#fff;border:1px solid #4be388;}' +
            '.mrl-pick-review-back,.mrl-pick-review-close{' +
                'background:#2b2b2b;color:#eee;border:1px solid #777;' +
            '}' +
            'html.mrl-theme-light .mrl-pick-review-panel{' +
                'background:rgba(237,250,241,.96);color:#202020;border-color:#4f9464;' +
            '}' +
            'html.mrl-theme-light .mrl-pick-review-panel h3,' +
            'html.mrl-theme-light .mrl-pick-review-label{color:#5f4300;}';

        document.head.appendChild(style);
    }

    function isPickSubmitForm(form) {
        if (!form || String(form.tagName).toLowerCase() !== 'form') return false;

        try {
            var url = new URL(form.getAttribute('action') || '', window.location.href);
            return /\/submit-team-picks\.php$/i.test(url.pathname);
        } catch (e) {
            return false;
        }
    }

    function driverMap(form) {
        var map = {};

        ['A', 'B', 'C', 'D'].forEach(function (group) {
            var field = form.querySelector(
                '[name="group-' + group.toLowerCase() + '-driver"]'
            );

            map[group] = field ? String(field.value || '').trim() : '';
        });

        return map;
    }

    function rememberBaseline(form) {
        if (!reviewBaselines.has(form)) {
            reviewBaselines.set(form, driverMap(form));
        }
    }

    function removeReview(form) {
        if (!form || !form.parentNode) return;

        var next = form.nextElementSibling;
        if (next && next.classList && next.classList.contains('mrl-pick-review-panel')) {
            next.parentNode.removeChild(next);
        }
    }

    function makeList(panel, label, rows, formatter) {
        if (!rows.length) return;

        var heading = document.createElement('div');
        heading.className = 'mrl-pick-review-label';
        heading.textContent = label;
        panel.appendChild(heading);

        var list = document.createElement('ul');

        rows.forEach(function (row) {
            var item = document.createElement('li');
            item.textContent = formatter(row);
            list.appendChild(item);
        });

        panel.appendChild(list);
    }

    function showReview(form, baseline, current) {
        removeReview(form);

        var newPicks = [];
        var changes = [];
        var unchanged = [];

        ['A', 'B', 'C', 'D'].forEach(function (group) {
            var before = String(baseline[group] || '').trim();
            var after = String(current[group] || '').trim();

            if (before === after) {
                if (after !== '') {
                    unchanged.push({
                        group: group,
                        driver: after
                    });
                }
                return;
            }

            if (before === '' && after !== '') {
                newPicks.push({
                    group: group,
                    driver: after
                });
                return;
            }

            changes.push({
                group: group,
                before: before || '(none)',
                after: after || '(none)'
            });
        });

        var panel = document.createElement('div');
        panel.className = 'mrl-pick-review-panel';
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-live', 'polite');

        var title = document.createElement('h3');
        title.textContent = 'Review your submission';
        panel.appendChild(title);

        if (newPicks.length === 0 && changes.length === 0) {
            var noChange = document.createElement('div');
            noChange.textContent =
                'No changes detected. Your current picks are already saved.';
            panel.appendChild(noChange);

            var closeActions = document.createElement('div');
            closeActions.className = 'mrl-pick-review-actions';

            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'mrl-pick-review-close';
            close.textContent = 'Close';
            close.addEventListener('click', function () {
                removeReview(form);
            });

            closeActions.appendChild(close);
            panel.appendChild(closeActions);

            form.insertAdjacentElement('afterend', panel);
            panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});

            return;
        }

        makeList(panel, 'New picks', newPicks, function (row) {
            return 'Group ' + row.group + ': ' + row.driver;
        });

        makeList(panel, 'Changes', changes, function (row) {
            return 'Group ' + row.group + ': ' + row.before + ' → ' + row.after;
        });

        makeList(panel, 'Unchanged', unchanged, function (row) {
            return 'Group ' + row.group + ': ' + row.driver;
        });

        var actions = document.createElement('div');
        actions.className = 'mrl-pick-review-actions';

        var confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'mrl-pick-review-confirm';
        confirm.textContent = 'Confirm Submission';

        var back = document.createElement('button');
        back.type = 'button';
        back.className = 'mrl-pick-review-back';
        back.textContent = 'Go Back';

        confirm.addEventListener('click', function () {
            /*
             * Allow exactly one subsequent submit event through THIS review
             * layer. The already-existing document quiet-submit listener then
             * handles the real background submission and success detection.
             */
            form.dataset.mrlPickReviewBypass = '1';
            removeReview(form);

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                /*
                 * Fallback for an unusually old browser. submit() bypasses
                 * submit-event listeners, so use the native submit button click
                 * when possible to preserve the existing quiet-submit path.
                 */
                var submitButton = form.querySelector(
                    'button[type="submit"], input[type="submit"]'
                );
                if (submitButton) {
                    submitButton.click();
                } else {
                    form.dataset.mrlPickReviewBypass = '0';
                }
            }
        });

        back.addEventListener('click', function () {
            removeReview(form);
        });

        actions.appendChild(confirm);
        actions.appendChild(back);
        panel.appendChild(actions);

        form.insertAdjacentElement('afterend', panel);
        panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }

    function attachReview(form) {
        if (!isPickSubmitForm(form)) return;
        if (form.dataset.mrlPickReviewAttached === '1') return;

        form.dataset.mrlPickReviewAttached = '1';
        rememberBaseline(form);

        /*
         * A listener directly on the form runs before the existing document
         * bubble listener used by quiet-submit.
         *
         * On RD forms, the existing inline rdPrepareSubmit() remains intact
         * and prepares the canonical group fields used below.
         */
        form.addEventListener('submit', function (event) {
            if (form.dataset.mrlPickReviewBypass === '1') {
                form.dataset.mrlPickReviewBypass = '0';
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            var baseline = reviewBaselines.get(form) || {
                A: '', B: '', C: '', D: ''
            };

            var current = driverMap(form);
            showReview(form, baseline, current);
        });
    }

    function attachAll() {
        addReviewStyles();

        Array.prototype.forEach.call(
            document.querySelectorAll('form'),
            attachReview
        );
    }

    attachAll();
}());
</script>
SCRIPT;

            /*
             * Insert immediately before the safe v042 handshake script when
             * possible, otherwise before </body>. This keeps the review code
             * independent of the handshake itself.
             */
            $handshakeAnchor = "<script>\nfunction teampageResizeCustomHtmlFrame(frame) {";

            if (strpos($new, $handshakeAnchor) !== false) {
                $new = tspr_replace_once(
                    $new,
                    $handshakeAnchor,
                    $reviewScript . "\n\n" . $handshakeAnchor,
                    'v042 handshake insertion point'
                );
            } else {
                $new = tspr_replace_once(
                    $new,
                    "</body>",
                    $reviewScript . "\n</body>",
                    'closing body insertion point'
                );
            }

            $new = str_replace('8/31/2026 3:24:34 pm', '8/31/2026 3:24:34 pm', $new);

            if (file_put_contents($teamPath, $new, LOCK_EX) === false) {
                throw new RuntimeException('Could not write updated team.php.');
            }

            $verify = tspr_lf((string)file_get_contents($teamPath));

            $post = [
                'team.php v043 installed' => strpos($verify, ' * VERSION: v043') !== false,
                'Smart Pick Review v002 installed' => strpos($verify, 'MRL SMART PICK REVIEW v002') !== false,
                'new-pick detail installed' => strpos($verify, "return 'Group ' + row.group + ': ' + row.driver;") !== false,
                'old -> new detail installed' => strpos($verify, "row.before + ' → ' + row.after") !== false,
                'identical-pick block installed' => strpos($verify, 'No changes detected. Your current picks are already saved.') !== false,
                'Confirm Submission installed' => strpos($verify, 'Confirm Submission') !== false,
                'Go Back installed' => strpos($verify, 'Go Back') !== false,
                'existing quiet-submit marker preserved' => strpos($verify, 'X-MRL-Quiet-Submit') !== false,
                'v042 Custom HTML handshake preserved' => strpos($verify, 'function teampageResizeCustomHtmlFrame(frame)') !== false,
                'submit-team-picks backend path preserved' => strpos($verify, 'submit-team-picks.php') !== false,
            ];

            if (in_array(false, $post, true)) {
                @copy($backupPath, $teamPath);
                throw new RuntimeException('Postflight failed; exact v042 team.php restored.');
            }

            $checks = $post;
            $ok = true;
            $message = 'Smart Pick Review v002 installed successfully.';
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
            throw new RuntimeException('Could not restore the pre-install team.php.');
        }

        $ok = true;
        $message = 'ROLLBACK COMPLETE — exact pre-Smart-Pick-Review team.php restored.';
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
<title>MRL Smart Pick Review v002 Installer</title>
<style>
:root{--bg:#101214;--panel:#1d2023;--border:#4b5055;--text:#f0f0f0;--gold:#efc77e;--green:#63e69a;--red:#ff7e7e;--blue:#55c7ff}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1120px;margin:20px auto}
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
<h1>Task 2 — Smart Pick Review v002</h1>
<p><strong>Generated:</strong> 8/31/2026 3:24:34 pm America/New_York</p>

<?php if ($message !== ''): ?>
<div class="banner <?php echo $ok ? 'ok' : 'bad'; ?>"><?php echo tspr_h($message); ?></div>
<?php endif; ?>

<?php if (!$message && $installed): ?>
<div class="banner ok">SMART PICK REVIEW v002 IS INSTALLED.</div>
<?php elseif (!$message && $ready): ?>
<div class="banner ok">PREVIEW PASS — ready to apply.</div>
<?php elseif (!$message): ?>
<div class="banner bad">PREVIEW BLOCKED — see failed checks below.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>Expected User Flow</h2>
<div class="banner info">Submit Picks → quiet Review panel → Confirm Submission or Go Back → existing quiet-submit engine handles the real save.</div>
<p><strong>Identical selections do not submit.</strong> They simply show: “No changes detected. Your current picks are already saved.”</p>
<p>The review layer itself never writes to the database and does not replace <code>submit-team-picks.php</code>.</p>
</div>

<div class="card">
<h2>Preflight / Postflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($checks as $label => $status): ?>
<tr>
<td><?php echo tspr_h($label); ?></td>
<td class="<?php echo $status ? 'pass' : 'fail'; ?>"><?php echo $status ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Actions</h2>

<?php if ($ready && !$installed): ?>
<form method="post" style="display:inline" onsubmit="return confirm('Install Smart Pick Review v002?');">
<input type="hidden" name="action" value="apply">
<button class="btn apply" type="submit">Apply Smart Pick Review</button>
</form>
<?php endif; ?>

<?php if ($rollbackAvailable): ?>
<form method="post" style="display:inline" onsubmit="return confirm('ROLL BACK Smart Pick Review and restore the exact pre-install team.php?');">
<input type="hidden" name="action" value="rollback">
<button class="btn rollback" type="submit">Rollback Smart Pick Review</button>
</form>
<?php endif; ?>

</div>
</div>
</body>
</html>
