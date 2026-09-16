<?php
declare(strict_types=1);

/**
 * MRL_install_scheduler_canonical_race_labels_v002.php
 *
 * PURPOSE
 * -------
 * Corrected follow-up installer for scheduler canonical short race labels.
 *
 * v002 correction:
 * - v001 preflight correctly stopped because the existing short_name state line
 *   occurs TWO times in cron_master_scheduler.php v014, not once.
 * - Both occurrences are legitimate scheduler-state paths and should use the
 *   canonical mrl_race_name preference.
 *
 * INSTALLS
 * --------
 * - cron_master_scheduler.php v014 -> v015
 * - Both scheduler-state short_name paths prefer mrl_race_name
 * - Main scheduler race label prefers mrl_race_name
 * - Current _scheduler/state.json next-race label is repaired immediately
 * - No database writes
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v002';

$root = __DIR__;
$cronFile = $root . '/race_results/cron_master_scheduler.php';
$scheduleFile = $root . '/race_results/_race_results_schedule.json';
$stateFile = $root . '/race_results/_scheduler/state.json';

$generated = '9/13/2026 7:00:21 am ET';
$backupStamp = date('Ymd_His');

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function atomic_write(string $path, string $contents): bool
{
    $dir = dirname($path);
    $tmp = $dir . '/.' . basename($path) . '.tmp.' . bin2hex(random_bytes(4));

    if (@file_put_contents($tmp, $contents) === false) {
        return false;
    }

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

function read_json(string $path): ?array
{
    if (!is_file($path)) return null;

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') return null;

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function backup_file(string $path, string $stamp): ?string
{
    if (!is_file($path)) return null;

    $backup = $path . '.bak_' . $stamp;
    if (!@copy($path, $backup)) {
        throw new RuntimeException('Could not create backup: ' . $backup);
    }
    return $backup;
}

function replace_exact(string $source, string $old, string $new, int $expectedCount, string $label): string
{
    $count = substr_count($source, $old);
    if ($count !== $expectedCount) {
        throw new RuntimeException(
            $label . ' signature count mismatch. Expected '
            . $expectedCount . ', found ' . $count . '.'
        );
    }
    return str_replace($old, $new, $source);
}

function race_number_from_row(array $race): int
{
    $n = (int)($race['mrl_race_number'] ?? 0);
    if ($n <= 0) $n = (int)($race['race_number'] ?? 0);
    if ($n <= 0) $n = (int)($race['schedule_sequence'] ?? 0);
    return $n;
}

function find_schedule_race(array $schedule, int $raceNumber): ?array
{
    foreach (['mrl_points_races', 'races'] as $key) {
        if (empty($schedule[$key]) || !is_array($schedule[$key])) continue;

        foreach ($schedule[$key] as $race) {
            if (!is_array($race)) continue;
            if (race_number_from_row($race) === $raceNumber) return $race;
        }
    }
    return null;
}

function current_scheduler_next_race(array $state): array
{
    $node = $state['tasks']['race_results_monitor']['auto_schedule']['next_race'] ?? [];
    return is_array($node) ? $node : [];
}

function repair_scheduler_state(array $state, array $schedule, array &$detail): array
{
    $next = current_scheduler_next_race($state);

    if (empty($next)) {
        $detail[] = 'Scheduler state has no current next_race node; no immediate runtime repair was needed.';
        return $state;
    }

    $raceNumber = (int)($next['mrl_race_number'] ?? 0);
    if ($raceNumber <= 0) {
        $code = trim((string)($next['mrl_race_code'] ?? ''));
        if (preg_match('/^R(\d+)$/i', $code, $m)) $raceNumber = (int)$m[1];
    }

    if ($raceNumber <= 0) {
        throw new RuntimeException('Could not determine scheduler next-race number for runtime repair.');
    }

    $race = find_schedule_race($schedule, $raceNumber);
    if (!is_array($race)) {
        throw new RuntimeException('R' . $raceNumber . ' was not found in the canonical schedule.');
    }

    $canonical = trim((string)($race['mrl_race_name'] ?? ''));
    if ($canonical === '') {
        throw new RuntimeException('Canonical mrl_race_name is missing for R' . $raceNumber . '.');
    }

    $code = trim((string)($next['mrl_race_code'] ?? ('R' . str_pad((string)$raceNumber, 2, '0', STR_PAD_LEFT))));
    $oldLabel = trim((string)($next['label'] ?? ''));

    $state['tasks']['race_results_monitor']['auto_schedule']['next_race']['label'] =
        trim($code . ' ' . $canonical);
    $state['tasks']['race_results_monitor']['auto_schedule']['next_race']['short_name'] =
        $canonical;
    $state['tasks']['race_results_monitor']['auto_schedule']['next_race']['mrl_race_name'] =
        $canonical;

    $detail[] = 'Runtime scheduler state: '
        . ($oldLabel !== '' ? $oldLabel : $code)
        . ' -> ' . $code . ' ' . $canonical;

    return $state;
}

function badge(string $status): string
{
    $class = $status === 'PASS' ? 'pass' : ($status === 'WARN' ? 'warn' : 'fail');
    return '<span class="' . $class . '">' . h($status) . '</span>';
}

$preflight = [];
$errors = [];

$cronReadable = is_file($cronFile) && is_readable($cronFile);
$preflight[] = ['cron_master_scheduler.php', $cronReadable ? 'PASS' : 'FAIL', $cronReadable ? 'Readable' : 'Missing/unreadable'];

$scheduleReadable = is_file($scheduleFile) && is_readable($scheduleFile);
$preflight[] = ['_race_results_schedule.json', $scheduleReadable ? 'PASS' : 'FAIL', $scheduleReadable ? 'Readable' : 'Missing/unreadable'];

$stateReadable = is_file($stateFile) && is_readable($stateFile);
$preflight[] = ['_scheduler/state.json', $stateReadable ? 'PASS' : 'WARN', $stateReadable ? 'Readable' : 'Missing/unreadable'];

$cronSource = $cronReadable ? (string)@file_get_contents($cronFile) : '';

if ($cronReadable) {
    $checks = [
        ["* VERSION: v014", 1, 'v014 version header'],
        ["const CMS_VERSION = 'v014';", 1, 'CMS_VERSION v014'],
        ["const CMS_SIGNATURE = 'CRON_MASTER_SCHEDULER v014';", 1, 'CMS_SIGNATURE v014'],
        [
            "\$raceLabel = trim((string)(\$nextRace['mrl_race_code'] ?? '') . ' ' . (string)(\$nextRace['short_name'] ?? \$nextRace['race_name'] ?? ''));",
            1,
            'scheduler race-label source'
        ],
        [
            "'short_name' => (string)(\$nextRace['short_name'] ?? ''),",
            2,
            'scheduler next_race short_name state'
        ],
    ];

    foreach ($checks as [$needle, $expected, $label]) {
        $count = substr_count($cronSource, $needle);
        if ($count !== $expected) {
            $errors[] = $label . ': expected ' . $expected . ', found ' . $count;
        }
    }

    $preflight[] = [
        'cron source signatures',
        empty($errors) ? 'PASS' : 'FAIL',
        empty($errors)
            ? 'v014 expected signatures found; duplicate short_name paths confirmed'
            : implode('; ', $errors)
    ];
}

$schedule = $scheduleReadable ? read_json($scheduleFile) : null;
if ($scheduleReadable) {
    $preflight[] = [
        'canonical schedule JSON',
        is_array($schedule) ? 'PASS' : 'FAIL',
        is_array($schedule) ? 'Valid JSON' : 'Invalid JSON'
    ];

    if (!is_array($schedule)) $errors[] = 'Canonical schedule JSON is invalid.';
}

$state = $stateReadable ? read_json($stateFile) : null;
if ($stateReadable && !is_array($state)) {
    $errors[] = 'Scheduler state JSON is invalid.';
}

$stateNext = is_array($state) ? current_scheduler_next_race($state) : [];
$stateLabel = trim((string)($stateNext['label'] ?? ''));
$stateRaceNumber = (int)($stateNext['mrl_race_number'] ?? 0);

$canInstall = empty($errors) && $cronReadable && is_array($schedule);

$installed = false;
$installMessages = [];
$installError = '';
$backups = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    if (!$canInstall) {
        $installError = 'Preflight failed. No files were changed.';
    } else {
        try {
            $newCron = $cronSource;

            $newCron = replace_exact($newCron, '* VERSION: v014', '* VERSION: v015', 1, 'Version header');
            $newCron = replace_exact(
                $newCron,
                '* LAST MODIFIED: 6/21/2026 9:41:10 pm',
                '* LAST MODIFIED: 9/13/2026 7:00:21 am',
                1,
                'Last modified'
            );

            $newCron = replace_exact(
                $newCron,
                " * CHANGELOG:\n * v013 (6/21/2026)",
                " * CHANGELOG:\n"
                . " * v015 (9/13/2026 7:00:21 am)\n"
                . " * - FIX: Race scheduler display labels now prefer canonical mrl_race_name before ESPN short_name/race_name.\n"
                . " * - FIX: Both scheduler next-race state paths now preserve canonical compact short_name/mrl_race_name for downstream displays.\n"
                . " * - PRESERVE: Scheduling cadence, race matching, monitor execution, revision scheduling, and database behavior are unchanged.\n"
                . " *\n"
                . " * v013 (6/21/2026)",
                1,
                'Changelog insertion'
            );

            $newCron = replace_exact($newCron, "const CMS_VERSION = 'v014';", "const CMS_VERSION = 'v015';", 1, 'CMS_VERSION');
            $newCron = replace_exact(
                $newCron,
                "const CMS_SIGNATURE = 'CRON_MASTER_SCHEDULER v014';",
                "const CMS_SIGNATURE = 'CRON_MASTER_SCHEDULER v015';",
                1,
                'CMS_SIGNATURE'
            );

            $newCron = replace_exact(
                $newCron,
                "\$raceLabel = trim((string)(\$nextRace['mrl_race_code'] ?? '') . ' ' . (string)(\$nextRace['short_name'] ?? \$nextRace['race_name'] ?? ''));",
                "\$raceLabel = trim((string)(\$nextRace['mrl_race_code'] ?? '') . ' ' . (string)(\$nextRace['mrl_race_name'] ?? \$nextRace['short_name'] ?? \$nextRace['race_name'] ?? ''));",
                1,
                'Race label preference'
            );

            $newCron = replace_exact(
                $newCron,
                "'short_name' => (string)(\$nextRace['short_name'] ?? ''),",
                "'short_name' => (string)(\$nextRace['mrl_race_name'] ?? \$nextRace['short_name'] ?? ''),\n"
                . "                'mrl_race_name' => (string)(\$nextRace['mrl_race_name'] ?? \$nextRace['short_name'] ?? ''),",
                2,
                'Both scheduler next-race state paths'
            );

            if (
                strpos($newCron, "* VERSION: v015") === false ||
                strpos($newCron, "const CMS_VERSION = 'v015';") === false ||
                strpos($newCron, "const CMS_SIGNATURE = 'CRON_MASTER_SCHEDULER v015';") === false ||
                substr_count($newCron, "'mrl_race_name' => (string)(\$nextRace['mrl_race_name'] ?? \$nextRace['short_name'] ?? ''),") !== 2
            ) {
                throw new RuntimeException('Source verification failed before write.');
            }

            $backups[$cronFile] = backup_file($cronFile, $backupStamp);

            if (!atomic_write($cronFile, $newCron)) {
                throw new RuntimeException('Could not write cron_master_scheduler.php.');
            }

            $installMessages[] = 'cron_master_scheduler.php v014 -> v015';
            $installMessages[] = 'Both scheduler next-race state paths updated to prefer canonical mrl_race_name.';

            if (is_array($state)) {
                $backups[$stateFile] = backup_file($stateFile, $backupStamp);

                $runtimeMessages = [];
                $newState = repair_scheduler_state($state, $schedule, $runtimeMessages);

                $stateJson = json_encode($newState, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                if (!is_string($stateJson)) {
                    throw new RuntimeException('Could not encode repaired scheduler state JSON.');
                }

                if (!atomic_write($stateFile, $stateJson . "\n")) {
                    throw new RuntimeException('Could not write repaired scheduler state JSON.');
                }

                foreach ($runtimeMessages as $message) $installMessages[] = $message;
            } else {
                $installMessages[] = 'Scheduler state not present; next normal scheduler run will create canonical display state.';
            }

            $verifyCron = (string)@file_get_contents($cronFile);

            if (
                strpos($verifyCron, "* VERSION: v015") === false ||
                strpos($verifyCron, "const CMS_VERSION = 'v015';") === false ||
                strpos($verifyCron, "const CMS_SIGNATURE = 'CRON_MASTER_SCHEDULER v015';") === false ||
                substr_count($verifyCron, "'mrl_race_name' => (string)(\$nextRace['mrl_race_name'] ?? \$nextRace['short_name'] ?? ''),") !== 2
            ) {
                throw new RuntimeException('Post-write cron verification failed.');
            }

            if (is_file($stateFile)) {
                $verifyState = read_json($stateFile);
                if (is_array($verifyState)) {
                    $verifyNext = current_scheduler_next_race($verifyState);

                    if (!empty($verifyNext)) {
                        $verifyRaceNumber = (int)($verifyNext['mrl_race_number'] ?? 0);
                        $verifyRace = $verifyRaceNumber > 0 ? find_schedule_race($schedule, $verifyRaceNumber) : null;

                        if (is_array($verifyRace)) {
                            $expectedName = trim((string)($verifyRace['mrl_race_name'] ?? ''));
                            $actualName = trim((string)($verifyNext['mrl_race_name'] ?? $verifyNext['short_name'] ?? ''));

                            if ($expectedName !== '' && $actualName !== $expectedName) {
                                throw new RuntimeException(
                                    'Post-write scheduler-state verification failed. Expected '
                                    . $expectedName . ', found ' . $actualName . '.'
                                );
                            }
                        }
                    }
                }
            }

            $installMessages[] = 'No database writes performed.';
            $installed = true;

        } catch (Throwable $e) {
            $installError = $e->getMessage();

            foreach (array_reverse(array_keys($backups)) as $original) {
                $backup = $backups[$original] ?? null;
                if (is_string($backup) && is_file($backup)) {
                    @copy($backup, $original);
                }
            }

            $installError .= ' Any files changed by this installer were rolled back from backup.';
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRL Scheduler Canonical Race Labels v002</title>
<style>
:root {
    color-scheme: dark;
    --bg:#121212; --panel:#1d1d1d; --border:#454545; --text:#f2f2f2;
    --muted:#cfcfcf; --gold:#ffc866; --green:#5cf0a7; --red:#ff7474; --yellow:#ffd76a;
}
* { box-sizing:border-box; }
body {
    margin:0; padding:14px; background:var(--bg); color:var(--text);
    font-family:Arial,Helvetica,sans-serif; font-size:15px; line-height:1.35;
}
.wrap { max-width:1180px; margin:0 auto; }
.panel {
    background:var(--panel); border:1px solid var(--border); border-radius:14px;
    padding:18px 20px; margin-bottom:14px;
}
h1,h2 { color:var(--gold); margin:0 0 8px; }
h1 { font-size:25px; } h2 { font-size:20px; }
p { margin:6px 0; } ul { margin:8px 0 0 22px; padding:0; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th,td { text-align:left; padding:10px; border-bottom:1px solid #3b3b3b; vertical-align:top; }
th { color:var(--gold); }
.pass { color:var(--green); font-weight:700; }
.warn { color:var(--yellow); font-weight:700; }
.fail,.error { color:var(--red); font-weight:700; }
code { background:#111; border-radius:5px; padding:1px 5px; color:#fff; }
button {
    border:1px solid #a97600; background:#5d4300; color:#ffd27a; font-weight:700;
    font-size:16px; padding:11px 16px; border-radius:10px; cursor:pointer;
}
button:disabled { opacity:.45; cursor:not-allowed; }
.complete { color:var(--green); font-size:20px; font-weight:700; }
</style>
</head>
<body>
<div class="wrap">

<div class="panel">
    <h1>MRL Scheduler Canonical Race Labels v002</h1>
    <p>Generated <?= h($generated) ?> · Corrected preflight/install logic · No DB writes</p>
</div>

<div class="panel">
    <h2>v002 correction</h2>
    <ul>
        <li>v001 expected one scheduler <code>short_name</code> state path.</li>
        <li>The live v014 source correctly contains two such paths.</li>
        <li>v002 expects and updates both paths.</li>
        <li>No production files were changed by the failed v001 preflight.</li>
    </ul>
</div>

<div class="panel">
    <h2>Preflight</h2>
    <table>
        <thead><tr><th>Target</th><th>Status</th><th>Detail</th></tr></thead>
        <tbody>
        <?php foreach ($preflight as $row): ?>
            <tr>
                <td><?= h($row[0]) ?></td>
                <td><?= badge($row[1]) ?></td>
                <td><?= h($row[2]) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($stateLabel !== ''): ?>
        <p style="margin-top:12px;">
            Current scheduler next-race label seen by preflight:
            <strong><?= h($stateLabel) ?></strong>
            <?php if ($stateRaceNumber > 0): ?>(R<?= h($stateRaceNumber) ?>)<?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<?php if ($installed): ?>
<div class="panel">
    <div class="complete">INSTALL COMPLETE</div>
    <ul>
        <?php foreach ($installMessages as $message): ?>
            <li><?= h($message) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<div class="panel">
    <p class="pass">Done. Reload Race Scheduler and MRL At a Glance.</p>
</div>
<?php elseif ($installError !== ''): ?>
<div class="panel">
    <div class="error">INSTALL FAILED</div>
    <p><?= h($installError) ?></p>
</div>
<?php endif; ?>

<?php if (!$installed): ?>
<div class="panel">
    <form method="post">
        <button type="submit" name="install" value="1" <?= $canInstall ? '' : 'disabled' ?>>Install Fix</button>
    </form>
</div>
<?php endif; ?>

</div>
</body>
</html>
