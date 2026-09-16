<?php
declare(strict_types=1);

/**
 * MRL installer
 * TITLE: Weekly Standings competitive-roster zero-row fix — corrective pass
 * INSTALLER VERSION: v002
 * GENERATED: 9/13/2026 3:44:52 pm ET
 *
 * TARGET:
 *   /race_results/weekly_standings.php
 *   v070 -> v071
 *
 * PURPOSE:
 * - Preserve the v070 competitive-roster authority.
 * - Enforce roster completion at the FINAL weekly-row boundary as well.
 * - This guarantees that a legitimate competitive team with no race-effective
 *   picks becomes an explicit 0-point weekly row before totals/winners are built.
 * - Fixes R27 Over The Edge disappearing after teamRows correctly reached 18.
 *
 * SAFETY:
 * - No database writes.
 * - Exact v070 + anchor preflight.
 * - Timestamped backup.
 * - Temp-file write + PHP lint when available.
 * - Atomic rename.
 * - Rollback support.
 */

date_default_timezone_set('America/New_York');

$targetRel = '/race_results/weekly_standings.php';
$target = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . $targetRel;
$backup = dirname($target) . '/weekly_standings.php.pre_v071_20260913_154452.bak';
$temp = dirname($target) . '/.weekly_standings_v071_20260913_154452.tmp.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function replace_once(string $src, string $old, string $new, string $label): string {
    $count = substr_count($src, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ': expected 1 anchor, found ' . $count . '.');
    }
    return str_replace($old, $new, $src);
}

function build_v071(string $src): string {
    $headerOld = " * VERSION: v070\n";
    $headerNew = " * VERSION: v071\n";
    $src = replace_once($src, $headerOld, $headerNew, 'Version header');

    $changelogAnchor = " * CHANGELOG:\n *\n";
    $changelogInsert = " * CHANGELOG:\n *\n"
        . " * v071 (9/13/2026 3:44:52 pm ET)\n"
        . " *   - FIX: After race-effective scoring rows are built, Weekly Standings now appends any still-missing competitive-roster teams as explicit 0-point rows before totals/winners are calculated.\n"
        . " *   - FIX: This closes the R27 edge case where team loading correctly reached 18 but weekly-row generation still returned 17.\n"
        . " *   - PRESERVE: v070 competitive-roster filtering, LP/RD effective-race logic, scoring values, snapshots, release history, exports, print, and pre-2026 behavior remain unchanged.\n"
        . " *\n";
    $src = replace_once($src, $changelogAnchor, $changelogInsert, 'Changelog');

    $breakdownOld = <<<'TXT'
        $driverPoints = rrs_load_snapshot_driver_points($snapshotFile);
        $weeklyRows = rrsg_build_weekly_rows($raceTeamRows, $driverPoints);

        $rows[] = [
TXT;
    $breakdownNew = <<<'TXT'
        $driverPoints = rrs_load_snapshot_driver_points($snapshotFile);
        $weeklyRows = rrsg_build_weekly_rows($raceTeamRows, $driverPoints);
        $weeklyRows = rrsg_append_missing_roster_rows($weeklyRows, $competitiveRoster);

        $rows[] = [
TXT;
    $src = replace_once($src, $breakdownOld, $breakdownNew, 'Segment breakdown weekly-row completion');

    $mainOld = <<<'TXT'
            $driverPoints = rrs_load_snapshot_driver_points($snapshotFile);
            $weeklyRows = rrsg_build_weekly_rows($raceTeamRows, $driverPoints);
            $winner = rrsg_get_weekly_winner($weeklyRows);
TXT;
    $mainNew = <<<'TXT'
            $driverPoints = rrs_load_snapshot_driver_points($snapshotFile);
            $weeklyRows = rrsg_build_weekly_rows($raceTeamRows, $driverPoints);
            $weeklyRows = rrsg_append_missing_roster_rows($weeklyRows, $competitiveRoster);
            $winner = rrsg_get_weekly_winner($weeklyRows);
TXT;
    $src = replace_once($src, $mainOld, $mainNew, 'Main race-loop weekly-row completion');

    return str_replace('9/13/2026 3:44:52 pm ET', '__HUMAN_REAL__', $src);
}

function lint_file(string $path): array {
    if (!function_exists('exec')) {
        return ['ok'=>true, 'message'=>'PHP lint unavailable; skipped.'];
    }
    $bin = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($bin) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines=[]; $code=0;
    @exec($cmd, $lines, $code);
    return ['ok'=>$code===0, 'message'=>implode("\n",$lines)];
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : 'preview';
$msg = [];
$failed = false;

try {
    if (!is_file($target)) {
        throw new RuntimeException('Target not found.');
    }
    $src = file_get_contents($target);
    if (!is_string($src)) {
        throw new RuntimeException('Unable to read target.');
    }

    $checks = [
        'Target exists' => true,
        'Current version is v070' => strpos($src, '* VERSION: v070') !== false && strpos($src, '* VERSION: v071') === false,
        'v070 competitive roster helper exists' => substr_count($src, 'function rrsg_append_missing_competitive_team_rows') === 1,
        'Existing zero-row helper exists' => substr_count($src, 'function rrsg_append_missing_roster_rows') === 1,
        'Segment weekly builder anchor exists' => substr_count($src, '$weeklyRows = rrsg_build_weekly_rows($raceTeamRows, $driverPoints);') === 2,
    ];

    foreach ($checks as $label=>$ok) {
        $msg[] = [$ok?'ok':'bad', $label . ': ' . ($ok?'PASS':'FAIL')];
        if (!$ok) $failed = true;
    }
    if ($failed) {
        throw new RuntimeException('Preflight failed. Nothing changed.');
    }

    $new = build_v071($src);
    $new = str_replace('__HUMAN_REAL__', '9/13/2026 3:44:52 pm ET', $new);

    if ($action === 'apply') {
        if (is_file($backup)) {
            throw new RuntimeException('Backup already exists for this installer timestamp.');
        }
        if (!copy($target, $backup)) {
            throw new RuntimeException('Backup creation failed.');
        }
        $msg[] = ['ok', 'Backup created: ' . basename($backup)];

        if (file_put_contents($temp, $new, LOCK_EX) === false) {
            @unlink($temp);
            throw new RuntimeException('Temporary write failed.');
        }

        $lint = lint_file($temp);
        $msg[] = [$lint['ok']?'ok':'bad', 'PHP lint: ' . trim((string)$lint['message'])];
        if (!$lint['ok']) {
            @unlink($temp);
            throw new RuntimeException('PHP lint failed; target unchanged.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Atomic rename failed.');
        }

        $installed = file_get_contents($target);
        if (!is_string($installed) || strpos($installed, '* VERSION: v071') === false) {
            @copy($backup, $target);
            throw new RuntimeException('Postflight failed; backup restored.');
        }

        $msg[] = ['ok', 'Installed weekly_standings.php v071 successfully.'];
        $msg[] = ['ok', 'No database writes were performed.'];
    } elseif ($action === 'rollback') {
        if (!is_file($backup)) {
            throw new RuntimeException('Rollback backup not found.');
        }
        if (!copy($backup, $temp)) {
            throw new RuntimeException('Could not stage rollback.');
        }
        $lint = lint_file($temp);
        $msg[] = [$lint['ok']?'ok':'bad', 'Rollback lint: ' . trim((string)$lint['message'])];
        if (!$lint['ok']) {
            @unlink($temp);
            throw new RuntimeException('Rollback lint failed.');
        }
        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Rollback rename failed.');
        }
        $msg[] = ['ok', 'Rollback complete: restored pre-v071 weekly_standings.php.'];
    } else {
        $msg[] = ['ok', 'Preview complete. No files changed.'];
        $msg[] = ['ok', 'Planned correction: v070 → v071.'];
        $msg[] = ['ok', 'Expected R27 after install: 18 expected → 18 loaded → 18 weekly rows; Over The Edge = 0.'];
    }
} catch (Throwable $e) {
    $failed = true;
    $msg[] = ['bad', $e->getMessage()];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Weekly Roster Fix v002</title>
<style>
:root{color-scheme:dark;--bg:#111;--panel:#1d1d1d;--line:#3b3b3b;--text:#eee;--muted:#aaa;--ok:#62e6a7;--bad:#ff6f6f;--blue:#69b7ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px/1.4 system-ui,-apple-system,Segoe UI,Arial,sans-serif}
.wrap{max-width:900px;margin:0 auto;padding:18px}.panel{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px;margin-bottom:14px}
h1{font-size:23px;margin:0 0 5px;color:#ffd166}.muted,.small{color:var(--muted)}.small{font-size:13px}.msg{padding:6px 8px;border-bottom:1px solid #333}.ok{color:var(--ok)}.bad{color:var(--bad)}
.actions{display:flex;gap:10px;flex-wrap:wrap}button{border:0;border-radius:8px;padding:9px 15px;font:inherit;font-weight:700;cursor:pointer}.preview{background:var(--blue);color:#07131d}.apply{background:#5ee29b;color:#07150d}.rollback{background:#e45d5d;color:#fff}
code{color:#bfe3ff}
</style>
</head>
<body><div class="wrap">
<div class="panel">
<h1>Weekly Standings Competitive-Roster Fix — Corrective Pass</h1>
<p><code>weekly_standings.php v070 → v071</code></p>
<p class="muted">Installer v002 · generated 9/13/2026 3:44:52 pm ET</p>
<p class="small">No DB writes. This only closes the final 18-loaded / 17-weekly-row gap shown by R27 validation.</p>
</div>
<div class="panel">
<?php foreach($msg as $m): ?><div class="msg <?=h($m[0])?>"><?=h($m[1])?></div><?php endforeach; ?>
</div>
<div class="panel actions">
<form method="post"><input type="hidden" name="action" value="preview"><button class="preview">Preview / Preflight</button></form>
<form method="post"><input type="hidden" name="action" value="apply"><button class="apply">Apply v071</button></form>
<form method="post"><input type="hidden" name="action" value="rollback"><button class="rollback">Rollback</button></form>
</div>
<div class="panel small">
<strong>What changed:</strong> v070 successfully established the correct 18-team competitive roster. v071 also enforces that roster after race-effective weekly rows are built, before totals and winners are calculated.
</div>
</div></body></html>
