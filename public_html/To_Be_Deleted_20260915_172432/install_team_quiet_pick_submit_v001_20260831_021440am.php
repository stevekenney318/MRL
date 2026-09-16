<?php
declare(strict_types=1);

/**
 * install_team_quiet_pick_submit.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/31/2026 2:14:40 am
 *
 * PURPOSE:
 *   Update production team.php from v039 to v040 so successful
 *   SEG / LP / RD pick submissions happen quietly in the background.
 *
 * RESULT:
 *   - No full-page reload / screen flash on Submit Picks.
 *   - Existing submit-team-picks.php v012 remains the authoritative
 *     database handler and is NOT modified.
 *   - The fetch request follows the handler's normal redirect to team.php.
 *   - Success is confirmed only when the returned Team page contains the
 *     existing server-generated mrl-pick-success marker.
 *   - The current team chart and submitted-team count are refreshed quietly
 *     from that returned page.
 *   - The existing green success confirmation is shown near the form for
 *     about eight seconds, then fades.
 *   - If JavaScript/fetch is unavailable, the form keeps its normal full-page
 *     submission behavior as a fallback.
 *
 * BASELINE:
 *   team.php v039 SHA-256:
 *   2e89ba91bed8a23265e278a2e09ba635b75135e623b3e9f2ad4bdd1fd82cba75
 *
 * NO DATABASE CHANGES.
 * NO CHANGE TO submit-team-picks.php.
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

function qps_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function qps_lf(string $text): string
{
    return str_replace(["\r\n", "\r"], "\n", $text);
}

$target = __DIR__ . '/team.php';
$expectedSha256 = '2e89ba91bed8a23265e278a2e09ba635b75135e623b3e9f2ad4bdd1fd82cba75';

$exists = is_file($target);
$raw = $exists ? file_get_contents($target) : false;
$current = is_string($raw) ? qps_lf($raw) : '';
$currentSha256 = $exists ? hash_file('sha256', $target) : '';

$oldHeader = <<<'OLD'
 * VERSION: v039
 * LAST MODIFIED: 8/30/2026
OLD;

$newHeader = <<<'NEW'
 * VERSION: v040
 * LAST MODIFIED: 8/31/2026 2:14:40 am
NEW;

$oldChangelog = <<<'OLD'
 * CHANGELOG:
 *
 * v039 (8/30/2026)
OLD;

$newChangelog = <<<'NEW'
 * CHANGELOG:
 *
 * v040 (8/31/2026 2:14:40 am)
 * - UI: Successful SEG / LP / RD submissions now run quietly in the background with fetch(), eliminating the full-page reload flash.
 * - UI: After confirmed success, the current team chart and submitted-team count refresh quietly from the returned Team page.
 * - UI: Preserves the existing green "✓ Your picks have been submitted." confirmation near the form for about eight seconds.
 * - SAFETY: Success is shown only when the normal server submission flow returns the existing success marker.
 * - FALLBACK: If browser fetch support is unavailable, normal full-page form submission remains unchanged.
 * - PRESERVE: submit-team-picks.php remains the authoritative database handler; no pick, LP, RP/RD, audit-history, scoring, deadline, or database behavior changes.
 *
 * v039 (8/30/2026)
NEW;

$oldTail = <<<'OLD'
    var pickSuccess = document.getElementById('mrl-pick-success');
    if (pickSuccess) {
        window.setTimeout(function () {
            pickSuccess.classList.add('mrl-pick-success-hide');

            window.setTimeout(function () {
                if (pickSuccess.parentNode) {
                    pickSuccess.parentNode.removeChild(pickSuccess);
                }
            }, 850);
        }, 8000);
    }
}());
OLD;

$newTail = <<<'NEW'
    var pickSuccessTimer = null;
    var pickSuccessRemoveTimer = null;

    function fadePickSuccess(node) {
        if (!node) return;

        if (pickSuccessTimer) {
            window.clearTimeout(pickSuccessTimer);
        }
        if (pickSuccessRemoveTimer) {
            window.clearTimeout(pickSuccessRemoveTimer);
        }

        node.classList.remove('mrl-pick-success-hide');

        pickSuccessTimer = window.setTimeout(function () {
            node.classList.add('mrl-pick-success-hide');

            pickSuccessRemoveTimer = window.setTimeout(function () {
                if (node.parentNode) {
                    node.parentNode.removeChild(node);
                }
            }, 850);
        }, 8000);
    }

    function showPickSuccess(form) {
        var oldNode = document.getElementById('mrl-pick-success');
        if (oldNode && oldNode.parentNode) {
            oldNode.parentNode.removeChild(oldNode);
        }

        var node = document.createElement('div');
        node.className = 'mrl-pick-success';
        node.id = 'mrl-pick-success';
        node.setAttribute('role', 'status');
        node.setAttribute('aria-live', 'polite');
        node.textContent = '✓ Your picks have been submitted.';

        form.insertAdjacentElement('afterend', node);
        fadePickSuccess(node);
    }

    function refreshQuietSection(parsedDocument, selector) {
        var currentNode = document.querySelector(selector);
        var returnedNode = parsedDocument.querySelector(selector);

        if (!currentNode || !returnedNode) {
            return;
        }

        currentNode.innerHTML = returnedNode.innerHTML;
    }

    var initialPickSuccess = document.getElementById('mrl-pick-success');
    if (initialPickSuccess) {
        fadePickSuccess(initialPickSuccess);
    }

    if (window.fetch && window.FormData && window.DOMParser) {
        document.addEventListener('submit', function (event) {
            var form = event.target;

            if (!form || String(form.tagName).toLowerCase() !== 'form') {
                return;
            }

            var actionValue = form.getAttribute('action') || '';
            var actionUrl;

            try {
                actionUrl = new URL(actionValue, window.location.href);
            } catch (e) {
                return;
            }

            if (!/\/submit-team-picks\.php$/i.test(actionUrl.pathname)) {
                return;
            }

            event.preventDefault();

            if (form.dataset.mrlSubmitting === '1') {
                return;
            }

            form.dataset.mrlSubmitting = '1';

            var submitControls = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            Array.prototype.forEach.call(submitControls, function (control) {
                control.disabled = true;
            });

            window.fetch(actionUrl.href, {
                method: (form.getAttribute('method') || 'post').toUpperCase(),
                body: new FormData(form),
                credentials: 'same-origin',
                redirect: 'follow',
                headers: {
                    'X-MRL-Quiet-Submit': '1'
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Submission request failed with HTTP ' + response.status + '.');
                }

                return response.text();
            })
            .then(function (html) {
                var parsed = new DOMParser().parseFromString(html, 'text/html');
                var confirmed = parsed.getElementById('mrl-pick-success');

                if (!confirmed) {
                    throw new Error('The server did not return the successful-submission marker.');
                }

                refreshQuietSection(parsed, '.mrl-user-info-panel');
                refreshQuietSection(parsed, '.mrl-rd-submission-panel');
                showPickSuccess(form);
            })
            .catch(function (error) {
                console.error('MRL quiet pick submission:', error);

                var oldError = document.getElementById('mrl-pick-submit-error');
                if (oldError && oldError.parentNode) {
                    oldError.parentNode.removeChild(oldError);
                }

                var errorNode = document.createElement('div');
                errorNode.className = 'mrl-rd-notice-panel';
                errorNode.id = 'mrl-pick-submit-error';
                errorNode.setAttribute('role', 'alert');
                errorNode.textContent = 'Pick submission could not be confirmed. Please refresh the page before trying again.';
                form.insertAdjacentElement('afterend', errorNode);
            })
            .then(function () {
                form.dataset.mrlSubmitting = '0';

                Array.prototype.forEach.call(submitControls, function (control) {
                    control.disabled = false;
                });
            });
        });
    }
}());
NEW;

$checks = [
    'team.php exists' => $exists,
    'exact v039 SHA-256 baseline matches' => $exists && hash_equals($expectedSha256, (string)$currentSha256),
    'VERSION v039 present once' => substr_count($current, ' * VERSION: v039') === 1,
    'header patch location found once' => substr_count($current, $oldHeader) === 1,
    'changelog patch location found once' => substr_count($current, $oldChangelog) === 1,
    'existing 8-second success script found once' => substr_count($current, $oldTail) === 1,
    'existing success renderer present' => strpos($current, 'function teampage_render_pick_success(bool $show): void') !== false,
    'normal success message present' => strpos($current, '✓ Your picks have been submitted.') !== false,
];

$ready = !in_array(false, $checks, true);

$alreadyInstalled =
    strpos($current, ' * VERSION: v040') !== false
    && strpos($current, 'X-MRL-Quiet-Submit') !== false
    && strpos($current, "new DOMParser().parseFromString(html, 'text/html')") !== false;

$action = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply')
    ? 'apply'
    : 'preview';

$attempted = ($action === 'apply');
$success = false;
$error = '';
$backupPath = '';

if ($attempted) {
    if ($alreadyInstalled) {
        $success = true;
    } elseif (!$ready) {
        $error = 'Apply blocked because team.php does not exactly match the verified v039 baseline.';
    } else {
        try {
            $updated = $current;

            $replacements = [
                [$oldHeader, str_replace('8/31/2026 2:14:40 am', '8/31/2026 2:14:40 am', $newHeader), 'header'],
                [$oldChangelog, str_replace('8/31/2026 2:14:40 am', '8/31/2026 2:14:40 am', $newChangelog), 'changelog'],
                [$oldTail, $newTail, 'JavaScript'],
            ];

            foreach ($replacements as [$old, $new, $label]) {
                $updated = str_replace($old, $new, $updated, $count);

                if ($count !== 1) {
                    throw new RuntimeException(
                        'Unexpected replacement count for ' . $label . ': ' . $count
                    );
                }
            }

            $updated = str_replace('8/31/2026 2:14:40 am', '8/31/2026 2:14:40 am', $updated);

            $backupDir = __DIR__ . '/_migration_backups/team_quiet_pick_submit_20260831_021440am';

            if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                throw new RuntimeException('Could not create backup directory.');
            }

            $backupPath = $backupDir . '/team.php';

            if (!copy($target, $backupPath)) {
                throw new RuntimeException('Could not create team.php backup.');
            }

            if (file_put_contents($target, $updated, LOCK_EX) === false) {
                @copy($backupPath, $target);
                throw new RuntimeException('Could not write updated team.php.');
            }

            $verifyRaw = file_get_contents($target);
            $verify = is_string($verifyRaw) ? qps_lf($verifyRaw) : '';

            $postflight = [
                'VERSION v040 installed' => substr_count($verify, ' * VERSION: v040') === 1,
                'quiet submit hook installed' => strpos($verify, 'X-MRL-Quiet-Submit') !== false,
                'fetch submission installed' => strpos($verify, 'window.fetch(actionUrl.href') !== false,
                'server success marker verification installed' => strpos($verify, "parsed.getElementById('mrl-pick-success')") !== false,
                'quiet chart refresh installed' => strpos($verify, "refreshQuietSection(parsed, '.mrl-user-info-panel')") !== false,
                'quiet submitted-count refresh installed' => strpos($verify, "refreshQuietSection(parsed, '.mrl-rd-submission-panel')") !== false,
                'normal fallback remains possible' => strpos($verify, 'if (window.fetch && window.FormData && window.DOMParser)') !== false,
                'existing success renderer preserved' => strpos($verify, 'function teampage_render_pick_success(bool $show): void') !== false,
            ];

            if (in_array(false, $postflight, true)) {
                @copy($backupPath, $target);
                throw new RuntimeException('Postflight verification failed; original v039 was restored.');
            }

            $success = true;
            $checks = $postflight;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$postRaw = is_file($target) ? file_get_contents($target) : false;
$post = is_string($postRaw) ? qps_lf($postRaw) : '';

$installed =
    strpos($post, ' * VERSION: v040') !== false
    && strpos($post, 'X-MRL-Quiet-Submit') !== false
    && strpos($post, "parsed.getElementById('mrl-pick-success')") !== false;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Quiet Pick Submission Installer</title>
<style>
:root{
    --bg:#101214;
    --panel:#1d2023;
    --border:#4b5055;
    --text:#f0f0f0;
    --gold:#efc77e;
    --green:#63e69a;
    --red:#ff7e7e;
    --blue:#55c7ff;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1150px;margin:20px auto}
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
code{color:var(--blue)}
.btn{padding:11px 18px;border-radius:8px;font-weight:800;cursor:pointer}
.apply{background:#16894b;color:#fff;border:1px solid #4be388}
.disabled{background:#555;color:#aaa;border:1px solid #777;cursor:not-allowed}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>MRL Quiet Pick Submission</h1>
<p><strong>Installer:</strong> v001 &nbsp; | &nbsp; <strong>Generated:</strong> 8/31/2026 2:14:40 am America/New_York</p>

<?php if ($attempted && $success): ?>
<div class="banner ok">INSTALL COMPLETE — team.php v040 installed and postflight passed.</div>
<?php elseif ($attempted): ?>
<div class="banner bad">INSTALL NOT APPLIED — <?php echo qps_h($error); ?></div>
<?php elseif ($installed): ?>
<div class="banner ok">QUIET PICK SUBMISSION IS ALREADY INSTALLED.</div>
<?php elseif ($ready): ?>
<div class="banner ok">PREVIEW PASS — exact production team.php v039 baseline verified.</div>
<?php else: ?>
<div class="banner bad">PREVIEW BLOCKED — team.php differs from the verified v039 baseline.</div>
<?php endif; ?>
</div>

<div class="card">
<h2>What This Changes</h2>
<div class="banner info">
Submit Picks will no longer reload the entire Team page when JavaScript/fetch is available.
</div>
<p>The existing <code>submit-team-picks.php v012</code> continues doing every database write exactly as it does now. The browser simply sends the same form in the background, follows the handler's normal return to Team, and confirms success from the existing server-generated success marker.</p>
<p>After success, the current team chart and submitted-team count refresh quietly, then <strong>✓ Your picks have been submitted.</strong> appears near the form for about eight seconds and fades away.</p>
<p>If fetch support is unavailable, the old normal full-page submission remains the fallback.</p>
</div>

<div class="card">
<h2>Preflight / Postflight</h2>
<table>
<thead><tr><th>Check</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($checks as $label => $ok): ?>
<tr>
<td><?php echo qps_h($label); ?></td>
<td class="<?php echo $ok ? 'pass' : 'fail'; ?>"><?php echo $ok ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Action</h2>
<?php if ($ready && !$installed && !$attempted): ?>
<form method="post" onsubmit="return confirm('Apply quiet background pick submission to team.php?');">
<input type="hidden" name="action" value="apply">
<button type="submit" class="btn apply">Apply Quiet Submit Update</button>
</form>
<?php elseif (!$ready && !$installed): ?>
<button type="button" class="btn disabled" disabled>Apply Unavailable</button>
<?php endif; ?>

<?php if ($backupPath !== ''): ?>
<p><strong>Backup:</strong> <code><?php echo qps_h($backupPath); ?></code></p>
<?php endif; ?>
</div>

</div>
</body>
</html>
