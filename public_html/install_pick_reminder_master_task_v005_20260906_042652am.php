<?php
declare(strict_types=1);

/*
 * install_pick_reminder_master_task_v005_20260906_042652am.php
 * VERSION: v001
 * LAST MODIFIED: 9/6/2026 4:26:52 am
 *
 * PURPOSE:
 * - Connect Pick Reminder to the EXISTING production master scheduler.
 * - Leave the Hostinger cron command completely unchanged.
 * - Leave race_results/cron_master_scheduler.php v014 completely unchanged.
 *
 * CHANGES:
 * - Creates /public_html/race_results/pick_reminder_task.php v001.
 * - Adds one interval task named pick_reminder to
 *   /public_html/race_results/_scheduler/schedule.json.
 * - The task runs every minute and bridges to /public_html/pick_reminder_scheduler.php.
 *
 * EXPECTED RESULT AFTER APPLY:
 * - Existing master scheduler log should move from TASKS checked=2 to checked=3.
 * - Pick Reminder Dashboard should begin recording scheduler checks.
 * - Current expired AUTO TEST timestamp will NOT be re-fired outside its catch window.
 *
 * SAFETY:
 * - No Hostinger cron change.
 * - No cron_master_scheduler.php code change.
 * - No database change.
 * - Installer itself sends no email and never executes the reminder task.
 * - schedule.json is backed up before Apply.
 */

date_default_timezone_set('America/New_York');

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__)), '/\\');
$raceDir = $root . '/race_results';
$scheduleFile = $raceDir . '/_scheduler/schedule.json';
$masterFile = $raceDir . '/cron_master_scheduler.php';
$bridgeFile = $raceDir . '/pick_reminder_task.php';
$pickSchedulerFile = $root . '/pick_reminder_scheduler.php';
$pickHelperFile = $root . '/pick_reminder_helper.php';
$pickDashboardFile = $root . '/pick_reminder_dashboard.php';

$backupDir = $root . '/_migration_backups/pick_reminder_master_task_v005_20260906_042652am';
$scheduleBackup = $backupDir . '/schedule.json';

$bridgePayload = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogcGlja19yZW1pbmRlcl90YXNrLnBocAogKgogKiBWRVJTSU9OOiB2MDAxCiAqIExBU1QgTU9ESUZJRUQ6IDkvNi8yMDI2IDQ6MjY6NTIgYW0KICoKICogUFVSUE9TRToKICogLSBUaGluIGJyaWRnZSB0YXNrIGZvciB0aGUgZXhpc3RpbmcgcmFjZV9yZXN1bHRzL2Nyb25fbWFzdGVyX3NjaGVkdWxlci5waHAuCiAqIC0gTGV0cyB0aGUgcHJvdmVuIG1hc3RlciBzY2hlZHVsZXIgY2FsbCB0aGUgcm9vdC1sZXZlbCBQaWNrIFJlbWluZGVyIHNjaGVkdWxlcgogKiAgIHdpdGhvdXQgY2hhbmdpbmcgdGhlIEhvc3RpbmdlciBjcm9uIGNvbW1hbmQgb3IgdGhlIG1hc3RlciBzY2hlZHVsZXIgZW5naW5lLgogKgogKiBGTE9XOgogKiBIb3N0aW5nZXIgY3JvbgogKiAgIC0+IHJhY2VfcmVzdWx0cy9jcm9uX21hc3Rlcl9zY2hlZHVsZXIucGhwCiAqICAgICAgLT4gcmFjZV9yZXN1bHRzL3BpY2tfcmVtaW5kZXJfdGFzay5waHAKICogICAgICAgICAtPiAvcHVibGljX2h0bWwvcGlja19yZW1pbmRlcl9zY2hlZHVsZXIucGhwCiAqCiAqIE5PVEVTOgogKiAtIE5vIGVtYWlsIGxvZ2ljIGxpdmVzIGhlcmUuCiAqIC0gVEVTVC9MSVZFIGFuZCBBVVRPL01BTlVBTC9PRkYgc2FmZXR5IHJlbWFpbnMgaW4gcGlja19yZW1pbmRlcl9zY2hlZHVsZXIucGhwLgogKiAtIFRoaXMgZmlsZSBzaW1wbHkgaGFuZHMgZXhlY3V0aW9uIHRvIHRoZSBleGlzdGluZyBQaWNrIFJlbWluZGVyIHNjaGVkdWxlci4KICovCgpkYXRlX2RlZmF1bHRfdGltZXpvbmVfc2V0KCdBbWVyaWNhL05ld19Zb3JrJyk7CgokdGFyZ2V0ID0gZGlybmFtZShfX0RJUl9fKSAuICcvcGlja19yZW1pbmRlcl9zY2hlZHVsZXIucGhwJzsKCmlmICghaXNfZmlsZSgkdGFyZ2V0KSkgewogICAgZndyaXRlKFNUREVSUiwgIlBpY2sgUmVtaW5kZXIgdGFzayBicmlkZ2U6IHRhcmdldCBzY2hlZHVsZXIgbm90IGZvdW5kOiB7JHRhcmdldH1cbiIpOwogICAgZXhpdCgyKTsKfQoKcmVxdWlyZSAkdGFyZ2V0OwoKLy8gcGlja19yZW1pbmRlcl9zY2hlZHVsZXIucGhwIG5vcm1hbGx5IGV4aXRzIGl0c2VsZi4KZXhpdCgwKTsK';

function prm5_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function prm5_decode(string $b64): string {
    $d = base64_decode($b64, true);
    return is_string($d) ? $d : '';
}

function prm5_atomic(string $path, string $content): array {
    try { $suffix = bin2hex(random_bytes(4)); }
    catch (Throwable $e) { $suffix = (string)mt_rand(100000,999999); }

    $tmp = $path . '.tmp_' . $suffix;
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return [false, 'Could not write temporary file: ' . $tmp];
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return [false, 'Could not replace/create: ' . $path];
    }
    return [true, ''];
}

function prm5_lint(string $path): array {
    $cmd = escapeshellarg(PHP_BINARY ?: 'php') . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = [];
    $code = 1;
    @exec($cmd, $out, $code);
    if ($code === 0) return [true, implode("\n", $out)];
    if (!$out) return [null, 'PHP CLI syntax check unavailable.'];
    return [false, implode("\n", $out)];
}

$masterContent = is_file($masterFile) ? (string)@file_get_contents($masterFile) : '';
$scheduleRaw = is_file($scheduleFile) ? (string)@file_get_contents($scheduleFile) : '';
$schedule = json_decode($scheduleRaw, true);
if (!is_array($schedule)) $schedule = [];
$tasks = isset($schedule['tasks']) && is_array($schedule['tasks']) ? $schedule['tasks'] : [];

$masterExpected = is_file($masterFile)
    && strpos($masterContent, "const CMS_VERSION = 'v014';") !== false
    && strpos($masterContent, "const CMS_SIGNATURE = 'CRON_MASTER_SCHEDULER v014';") !== false;

$expectedTwoTasks = count($tasks) === 2
    && isset($tasks['race_results_monitor'])
    && isset($tasks['race_results_revision_monitor'])
    && !isset($tasks['pick_reminder']);

$bridgeUnused = !file_exists($bridgeFile);
$pickSchedulerExpected = is_file($pickSchedulerFile)
    && strpos((string)@file_get_contents($pickSchedulerFile), 'VERSION: v002') !== false;
$pickHelperExpected = is_file($pickHelperFile)
    && strpos((string)@file_get_contents($pickHelperFile), 'VERSION: v003') !== false;
$pickDashboardExpected = is_file($pickDashboardFile)
    && strpos((string)@file_get_contents($pickDashboardFile), 'VERSION v004 | Admin-only') !== false;
$bridgePayloadOk = strpos(prm5_decode($bridgePayload), 'VERSION: v001') !== false
    && strpos(prm5_decode($bridgePayload), "dirname(__DIR__) . '/pick_reminder_scheduler.php'") !== false;

$checks = [
    'Existing master scheduler v014 unchanged baseline' => $masterExpected,
    'schedule.json exists and parses' => is_file($scheduleFile) && is_array(json_decode($scheduleRaw, true)),
    'Current schedule has exactly the two proven tasks' => $expectedTwoTasks,
    'schedule.json writable' => is_file($scheduleFile) && is_writable($scheduleFile),
    'New bridge filename unused' => $bridgeUnused,
    'Pick Reminder scheduler remains expected v002' => $pickSchedulerExpected,
    'Pick Reminder helper remains expected v003' => $pickHelperExpected,
    'Pick Reminder dashboard remains expected v004' => $pickDashboardExpected,
    'Embedded bridge payload valid' => $bridgePayloadOk,
    'race_results folder writable' => is_dir($raceDir) && is_writable($raceDir),
    'public_html writable' => is_writable($root),
];

$ready = !in_array(false, $checks, true);
$msg = '';
$type = '';

if (($_POST['action'] ?? '') === 'apply') {
    if (!$ready) {
        $msg = 'APPLY BLOCKED — required preflight checks are not all passing.';
        $type = 'err';
    } else {
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
            $msg = 'APPLY FAILED — could not create rollback folder.';
            $type = 'err';
        } elseif (!@copy($scheduleFile, $scheduleBackup)) {
            $msg = 'APPLY FAILED — could not back up schedule.json.';
            $type = 'err';
        } else {
            $err = '';

            // Create the thin bridge first. It is NOT executed here.
            [$okBridge, $whyBridge] = prm5_atomic($bridgeFile, prm5_decode($bridgePayload));
            if (!$okBridge) {
                $err = $whyBridge;
            } else {
                [$lintOk, $lintReport] = prm5_lint($bridgeFile);
                if ($lintOk === false) {
                    $err = 'Bridge PHP syntax check failed: ' . $lintReport;
                }
            }

            if ($err === '') {
                $newSchedule = $schedule;
                if (!isset($newSchedule['tasks']) || !is_array($newSchedule['tasks'])) {
                    $err = 'schedule.json tasks block disappeared during Apply.';
                } else {
                    $newSchedule['tasks']['pick_reminder'] = [
                        'enabled' => true,
                        'type' => 'interval',
                        'interval_minutes' => 1,
                        'script' => 'pick_reminder_task.php',
                        'args' => [],
                        'run_method' => 'php',
                        'lock_minutes' => 2,
                        'timeout_seconds' => 120,
                        'description' => 'Runs the root Pick Reminder scheduler every minute. The Pick Reminder scheduler itself owns AUTO/MANUAL/OFF, TEST/LIVE, due-time and duplicate-send decisions.'
                    ];

                    $json = json_encode($newSchedule, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if (!is_string($json)) {
                        $err = 'Could not encode updated schedule.json.';
                    } else {
                        [$okSchedule, $whySchedule] = prm5_atomic($scheduleFile, $json . PHP_EOL);
                        if (!$okSchedule) $err = $whySchedule;
                    }
                }
            }

            if ($err !== '') {
                @copy($scheduleBackup, $scheduleFile);
                @unlink($bridgeFile);
                $msg = 'APPLY FAILED — ' . $err . ' Original schedule restored and bridge removed.';
                $type = 'err';
            } else {
                // Postflight only inspects files. It deliberately does not execute the scheduler.
                $postRaw = (string)@file_get_contents($scheduleFile);
                $post = json_decode($postRaw, true);
                $postTask = is_array($post) && isset($post['tasks']['pick_reminder']) && is_array($post['tasks']['pick_reminder']);
                $postBridge = is_file($bridgeFile);

                if (!$postTask || !$postBridge) {
                    @copy($scheduleBackup, $scheduleFile);
                    @unlink($bridgeFile);
                    $msg = 'APPLY FAILED — postflight could not confirm the new task/bridge. Original schedule restored.';
                    $type = 'err';
                } else {
                    $msg = 'INSTALL COMPLETE — Pick Reminder is now task #3 under the existing master scheduler. Hostinger cron and cron_master_scheduler.php were not changed. No email was sent by this installer.';
                    $type = 'ok';
                }
            }
        }
    }
}

if (($_POST['action'] ?? '') === 'rollback') {
    if (!is_file($scheduleBackup)) {
        $msg = 'ROLLBACK NOT AVAILABLE — no schedule backup exists yet.';
        $type = 'err';
    } else {
        $ok = @copy($scheduleBackup, $scheduleFile);
        if (is_file($bridgeFile)) @unlink($bridgeFile);
        if ($ok) {
            $msg = 'ROLLBACK COMPLETE — original two-task schedule restored and Pick Reminder bridge removed.';
            $type = 'ok';
        } else {
            $msg = 'ROLLBACK FAILED — could not restore schedule.json.';
            $type = 'err';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install Pick Reminder Master Task v005</title>
<style>
:root{color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;background:#0d1013;color:#eef2f6;font-family:Arial,Helvetica,sans-serif;font-size:14px}
.wrap{max-width:1080px;margin:20px auto 50px;padding:0 16px}
.card{background:#1b1f23;border:1px solid #3b4249;border-radius:12px;padding:18px;margin-bottom:14px}
h1,h2{color:#ffcf83;margin-top:0}
h1{font-size:30px}
.sub{color:#c8d0d8;line-height:1.55}
.info{background:#12344a;border:1px solid #1d6f9d;border-radius:8px;padding:12px;line-height:1.55}
.ok{background:#104d2d;border:1px solid #218750;border-radius:8px;padding:13px;font-weight:bold}
.err{background:#671f1f;border:1px solid #c83a3a;border-radius:8px;padding:13px;font-weight:bold}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 8px;border-bottom:1px solid #3a4249;text-align:left;vertical-align:top}
th{color:#ffd27f;font-size:12px}
.pass{color:#57e38c;font-weight:bold}
.fail{color:#ff7373;font-weight:bold}
code{color:#ffd27f;font-family:Consolas,monospace}
.btn{border:0;border-radius:7px;padding:11px 16px;font-weight:bold;cursor:pointer}
.green{background:#23864b;color:#fff}
.red{background:#ca2e2e;color:#fff}
.disabled{background:#666;color:#ccc}
a{color:#8fc8ff}
.small{font-size:12px;color:#aeb8c1}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>Install Pick Reminder Master Task v005</h1>
<div class="sub">This connects Pick Reminder to the master scheduler you already trust. It does <b>not</b> replace that scheduler and does <b>not</b> change the Hostinger cron command.</div>
<div class="info" style="margin-top:14px">
<b>Before:</b> existing master scheduler checks Race Monitor + Revision Monitor (2 tasks).<br>
<b>After:</b> the same master scheduler checks those same two tasks + Pick Reminder (3 tasks).<br>
<b>Implementation:</b> one small bridge file in <code>/race_results/</code> plus one new task entry in the existing <code>schedule.json</code>.<br>
<b>cron_master_scheduler.php v014 itself is untouched.</b><br>
<b>No database changes. No email is sent by this installer.</b>
</div>
</div>

<?php if ($msg !== ''): ?>
<div class="<?php echo $type === 'ok' ? 'ok' : 'err'; ?>" style="margin-bottom:14px">
<?php echo prm5_h($msg); ?>
<?php if ($type === 'ok' && strpos($msg, 'INSTALL COMPLETE') !== false): ?>
&nbsp; <a href="/pick_reminder_dashboard.php">Open Pick Reminder Dashboard</a>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
<h2>Preflight</h2>
<table><thead><tr><th>CHECK</th><th>STATUS</th></tr></thead><tbody>
<?php foreach ($checks as $label => $ok): ?>
<tr><td><?php echo prm5_h($label); ?></td><td class="<?php echo $ok ? 'pass' : 'fail'; ?>"><?php echo $ok ? 'PASS' : 'FAIL'; ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>

<div class="card">
<h2>Apply</h2>
<p class="sub">Backs up <code>schedule.json</code>, creates the bridge, then adds only the third task. It does not manually execute the task during installation.</p>
<form method="post"><input type="hidden" name="action" value="apply"><button class="btn <?php echo $ready ? 'green' : 'disabled'; ?>" <?php echo $ready ? '' : 'disabled'; ?>>Apply Pick Reminder Master Task v005</button></form>
</div>

<div class="card">
<h2>Rollback</h2>
<p class="sub">Restores the exact original two-task schedule and removes the bridge file.</p>
<form method="post" onsubmit="return confirm('Rollback Pick Reminder master-task integration?');"><input type="hidden" name="action" value="rollback"><button class="btn red">Rollback</button></form>
</div>

<div class="small" style="text-align:right">install_pick_reminder_master_task_v005_20260906_042652am.php</div>
</div>
</body>
</html>
