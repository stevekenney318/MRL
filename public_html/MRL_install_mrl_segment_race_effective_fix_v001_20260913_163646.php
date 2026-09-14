<?php
declare(strict_types=1);

/**
 * MRL installer
 * TITLE: MRL Segment race-effective competitive-driver fix
 * INSTALLER VERSION: v001
 * GENERATED: 9/13/2026 4:36:46 pm ET
 *
 * TARGET:
 *   /race_results/race_results_snapshot_views_helper.php
 *   v001 -> v002
 *
 * PURPOSE:
 * - Prevent userID 0 / 999 and the MRL test team from contributing drivers
 *   to authoritative _mrl_segment companion snapshots.
 * - Build the segment driver pool from the race-effective competitive pick
 *   state for the specific R## being generated.
 * - Preserve SEG / ADJ baseline behavior.
 * - Apply LP / RD only when effective_race <= the generated race number.
 * - Preserve the original canonical ESPN snapshot and all companion timestamps.
 *
 * SAFETY:
 * - No database writes.
 * - No snapshot regeneration.
 * - No scheduler changes.
 * - Exact v001 + anchor preflight.
 * - Timestamped backup.
 * - Temp-file write + PHP lint when available.
 * - Atomic rename.
 * - Rollback support.
 */

date_default_timezone_set('America/New_York');

$targetRel = '/race_results/race_results_snapshot_views_helper.php';
$target = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . $targetRel;
$backup = dirname($target) . '/race_results_snapshot_views_helper.php.pre_v002_20260913_163646.bak';
$temp = dirname($target) . '/.race_results_snapshot_views_helper_v002_20260913_163646.tmp.php';

function mrl_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mrl_replace_once(string $src, string $old, string $new, string $label): string
{
    $count = substr_count($src, $old);
    if ($count !== 1) {
        throw new RuntimeException($label . ': expected exactly 1 anchor, found ' . $count . '.');
    }
    return str_replace($old, $new, $src);
}

function mrl_build_v002(string $src): string
{
    $src = mrl_replace_once(
        $src,
        " * VERSION: v001\n * LAST MODIFIED: 7/19/2026 1:35:18 pm\n",
        " * VERSION: v002\n * LAST MODIFIED: 9/13/2026 4:36:46 pm ET\n",
        'Header version'
    );

    $changeAnchor = " * CHANGELOG:\n * v001 (7/19/2026 1:35:18 pm)\n";
    $changeText =
        " * CHANGELOG:\n"
        . " * v002 (9/13/2026 4:36:46 pm ET)\n"
        . " *   - FIX: _mrl_segment driver pools now use race-effective competitive picks for the specific R## being generated.\n"
        . " *   - FIX: userID 0 / 999, MRL test team, and username MRL are excluded from the segment-driver source rows.\n"
        . " *   - FIX: LP / RD drivers enter the pool only when effective_race <= the generated race number; before that, the applicable baseline SEG / ADJ row remains authoritative.\n"
        . " *   - PRESERVE: Canonical ESPN snapshots, _lite / _mrl behavior, original NASCAR positions, filenames, and canonical file timestamps remain unchanged.\n"
        . " *\n"
        . " * v001 (7/19/2026 1:35:18 pm)\n";
    $src = mrl_replace_once($src, $changeAnchor, $changeText, 'Changelog');

    $oldFunction = <<<'PHP'
function rrsv_query_segment_drivers(string $year, string $segment, $dbo, $dbconnect): array
{
    $names = [];

    if ($dbo instanceof PDO) {
        $stmt = $dbo->prepare(
            'SELECT driverA, driverB, driverC, driverD FROM user_picks '
            . 'WHERE raceYear = :year AND segment = :segment'
        );
        $stmt->execute([':year' => $year, ':segment' => $segment]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            foreach (['driverA', 'driverB', 'driverC', 'driverD'] as $field) {
                $name = trim((string)($row[$field] ?? ''));
                if ($name !== '') $names[rrsv_name_key($name)] = $name;
            }
        }
        return $names;
    }

    if ($dbconnect instanceof mysqli) {
        $stmt = mysqli_prepare(
            $dbconnect,
            'SELECT driverA, driverB, driverC, driverD FROM user_picks WHERE raceYear = ? AND segment = ?'
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $year, $segment);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                foreach (['driverA', 'driverB', 'driverC', 'driverD'] as $field) {
                    $name = trim((string)($row[$field] ?? ''));
                    if ($name !== '') $names[rrsv_name_key($name)] = $name;
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    return $names;
}
PHP;

    $newFunction = <<<'PHP'
function rrsv_segment_driver_names_from_pick_rows(array $rows, int $raceNumber): array
{
    $teams = [];

    foreach ($rows as $row) {
        if (!is_array($row)) continue;

        $userId = (int)($row['userID'] ?? 0);
        $teamName = trim((string)($row['teamName'] ?? ''));
        $userName = trim((string)($row['userName'] ?? ''));

        if ($userId === 0 || $userId === 999) continue;
        if (strcasecmp($teamName, 'MRL test team') === 0) continue;
        if (strcasecmp($userName, 'MRL') === 0) continue;

        $teamKey = (string)$userId;
        if (!isset($teams[$teamKey])) {
            $teams[$teamKey] = [
                'base' => null,
                'special' => null,
            ];
        }

        $pickType = strtoupper(trim((string)($row['pick_type'] ?? 'SEG')));

        if ($pickType === 'SEG' || $pickType === 'ADJ') {
            // Match the current shared baseline-pick loader: first baseline row wins.
            if ($teams[$teamKey]['base'] === null) {
                $teams[$teamKey]['base'] = $row;
            }
            continue;
        }

        if ($pickType !== 'LP' && $pickType !== 'RD') {
            continue;
        }

        $effectiveRace = (int)($row['effective_race'] ?? 0);
        if ($effectiveRace <= 0 || $effectiveRace > $raceNumber) {
            continue;
        }

        $current = $teams[$teamKey]['special'];
        if ($current === null) {
            $teams[$teamKey]['special'] = $row;
            continue;
        }

        $currentRace = (int)($current['effective_race'] ?? 0);
        $currentDate = strtotime((string)($current['entryDate'] ?? '')) ?: 0;
        $rowDate = strtotime((string)($row['entryDate'] ?? '')) ?: 0;
        $currentPickId = (int)($current['pickID'] ?? 0);
        $rowPickId = (int)($row['pickID'] ?? 0);

        if (
            $effectiveRace > $currentRace
            || ($effectiveRace === $currentRace && $rowDate > $currentDate)
            || ($effectiveRace === $currentRace && $rowDate === $currentDate && $rowPickId > $currentPickId)
        ) {
            $teams[$teamKey]['special'] = $row;
        }
    }

    $names = [];

    foreach ($teams as $team) {
        $effectiveRow = is_array($team['special'] ?? null)
            ? $team['special']
            : (is_array($team['base'] ?? null) ? $team['base'] : null);

        if ($effectiveRow === null) continue;

        foreach (['driverA', 'driverB', 'driverC', 'driverD'] as $field) {
            $name = trim((string)($effectiveRow[$field] ?? ''));
            if ($name !== '') {
                $names[rrsv_name_key($name)] = $name;
            }
        }
    }

    return $names;
}

function rrsv_query_segment_drivers(string $year, string $segment, int $raceNumber, $dbo, $dbconnect): array
{
    $rows = [];

    $pdoSql = "
        SELECT
            up.pickID,
            up.userID,
            up.teamName,
            COALESCE(u.userName, '') AS userName,
            up.driverA,
            up.driverB,
            up.driverC,
            up.driverD,
            up.entryDate,
            up.pick_type,
            up.effective_race
        FROM user_picks up
        LEFT JOIN users u ON u.userID = up.userID
        WHERE up.raceYear = :year
          AND up.segment = :segment
          AND up.pick_type IN ('SEG', 'ADJ', 'LP', 'RD')
          AND up.userID NOT IN (0, 999)
          AND LOWER(TRIM(COALESCE(up.teamName, ''))) <> 'mrl test team'
          AND COALESCE(u.userName, '') <> 'MRL'
        ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC
    ";

    if ($dbo instanceof PDO) {
        $stmt = $dbo->prepare($pdoSql);
        $stmt->execute([
            ':year' => $year,
            ':segment' => $segment,
        ]);

        $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (is_array($fetched)) {
            $rows = $fetched;
        }

        return rrsv_segment_driver_names_from_pick_rows($rows, $raceNumber);
    }

    if ($dbconnect instanceof mysqli) {
        $mysqliSql = "
            SELECT
                up.pickID,
                up.userID,
                up.teamName,
                COALESCE(u.userName, '') AS userName,
                up.driverA,
                up.driverB,
                up.driverC,
                up.driverD,
                up.entryDate,
                up.pick_type,
                up.effective_race
            FROM user_picks up
            LEFT JOIN users u ON u.userID = up.userID
            WHERE up.raceYear = ?
              AND up.segment = ?
              AND up.pick_type IN ('SEG', 'ADJ', 'LP', 'RD')
              AND up.userID NOT IN (0, 999)
              AND LOWER(TRIM(COALESCE(up.teamName, ''))) <> 'mrl test team'
              AND COALESCE(u.userName, '') <> 'MRL'
            ORDER BY up.userID ASC, up.entryDate ASC, up.pickID ASC
        ";

        $stmt = mysqli_prepare($dbconnect, $mysqliSql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $year, $segment);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($result && ($row = mysqli_fetch_assoc($result))) {
                $rows[] = $row;
            }

            mysqli_stmt_close($stmt);
        }
    }

    return rrsv_segment_driver_names_from_pick_rows($rows, $raceNumber);
}
PHP;

    $src = mrl_replace_once(
        $src,
        $oldFunction,
        $newFunction,
        'Segment-driver query function'
    );

    $src = mrl_replace_once(
        $src,
        '$segmentDrivers = rrsv_query_segment_drivers((string)$year, $segment, $dbo, $dbconnect);',
        '$segmentDrivers = rrsv_query_segment_drivers((string)$year, $segment, $raceNumber, $dbo, $dbconnect);',
        'Race-number call'
    );

    return str_replace('9/13/2026 4:36:46 pm ET', '__HUMAN_REAL__', $src);
}

function mrl_lint_file(string $path): array
{
    if (!function_exists('exec')) {
        return ['ok' => true, 'message' => 'PHP lint unavailable; skipped.'];
    }

    $bin = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($bin) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines = [];
    $code = 0;
    @exec($cmd, $lines, $code);

    return [
        'ok' => ($code === 0),
        'message' => trim(implode("\n", $lines)),
    ];
}

function mrl_preflight(string $target): array
{
    $rows = [];
    $ok = true;

    if (!is_file($target)) {
        return [
            'ok' => false,
            'rows' => [['bad', 'Target exists: FAIL']],
            'source' => '',
        ];
    }

    $src = file_get_contents($target);
    if (!is_string($src)) {
        return [
            'ok' => false,
            'rows' => [['bad', 'Target readable: FAIL']],
            'source' => '',
        ];
    }

    $checks = [
        'Target exists' => true,
        'Current version is v001' => (
            strpos($src, '* VERSION: v001') !== false
            && strpos($src, '* VERSION: v002') === false
        ),
        'Original segment-driver function anchor' => (
            substr_count($src, 'function rrsv_query_segment_drivers(string $year, string $segment, $dbo, $dbconnect): array') === 1
        ),
        'Race-number generation path available' => (
            substr_count($src, 'int $raceNumber,') >= 1
            && substr_count($src, '$segment = rrsv_segment_from_race_number($raceNumber);') === 1
        ),
        'Original segment-driver call anchor' => (
            substr_count($src, '$segmentDrivers = rrsv_query_segment_drivers((string)$year, $segment, $dbo, $dbconnect);') === 1
        ),
        'Canonical timestamp preservation present' => (
            substr_count($src, '@touch($path, $mtime, $mtime);') === 1
        ),
    ];

    foreach ($checks as $label => $pass) {
        $rows[] = [$pass ? 'ok' : 'bad', $label . ': ' . ($pass ? 'PASS' : 'FAIL')];
        if (!$pass) $ok = false;
    }

    if ($ok) {
        try {
            $patched = mrl_build_v002($src);
            $patched = str_replace('__HUMAN_REAL__', '9/13/2026 4:36:46 pm ET', $patched);
            $rows[] = ['ok', 'Patch construction: PASS'];
            $rows[] = ['ok', 'No database writes: PASS'];
            $rows[] = ['ok', 'No existing snapshots will be regenerated: PASS'];
            $rows[] = ['ok', 'Expected behavior: 0 / 999 / MRL test rows cannot add drivers to new _mrl_segment files.'];
            $rows[] = ['ok', 'Expected behavior: LP / RD drivers appear only at or after their effective race.'];
            $rows[] = ['ok', 'Canonical ESPN snapshot remains untouched; generated companion mtimes continue to mirror it.'];
        } catch (Throwable $e) {
            $ok = false;
            $rows[] = ['bad', 'Patch construction: FAIL — ' . $e->getMessage()];
        }
    }

    return [
        'ok' => $ok,
        'rows' => $rows,
        'source' => $src,
    ];
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
$results = [];
$status = 'idle';
$headline = 'Ready for manual preflight.';
$preflightPassed = false;
$installed = false;
$rollbackAvailable = is_file($backup);

try {
    if ($action === 'preflight') {
        $pf = mrl_preflight($target);
        $results = $pf['rows'];
        $preflightPassed = !empty($pf['ok']);
        $status = $preflightPassed ? 'ready' : 'failed';
        $headline = $preflightPassed
            ? 'Preflight passed — Ready to Install v002.'
            : 'Preflight failed — Install remains disabled.';
    } elseif ($action === 'install') {
        // Re-run the same exact preflight server-side before changing anything.
        $pf = mrl_preflight($target);
        $results = $pf['rows'];

        if (empty($pf['ok'])) {
            throw new RuntimeException('Install blocked because preflight no longer passes.');
        }

        $src = (string)$pf['source'];
        $new = mrl_build_v002($src);
        $new = str_replace('__HUMAN_REAL__', '9/13/2026 4:36:46 pm ET', $new);

        if (is_file($backup)) {
            throw new RuntimeException('This installer backup already exists; refusing to overwrite it.');
        }

        if (!copy($target, $backup)) {
            throw new RuntimeException('Backup creation failed.');
        }
        $results[] = ['ok', 'Backup created: ' . basename($backup)];

        if (file_put_contents($temp, $new, LOCK_EX) === false) {
            @unlink($temp);
            throw new RuntimeException('Temporary target write failed.');
        }

        $lint = mrl_lint_file($temp);
        $results[] = [$lint['ok'] ? 'ok' : 'bad', 'PHP lint: ' . ($lint['message'] !== '' ? $lint['message'] : 'completed')];
        if (!$lint['ok']) {
            @unlink($temp);
            throw new RuntimeException('PHP lint failed; original target remains unchanged.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Atomic rename failed; original target remains in place.');
        }

        $post = file_get_contents($target);
        if (!is_string($post)
            || strpos($post, '* VERSION: v002') === false
            || strpos($post, 'function rrsv_segment_driver_names_from_pick_rows') === false
            || strpos($post, 'rrsv_query_segment_drivers((string)$year, $segment, $raceNumber, $dbo, $dbconnect)') === false
        ) {
            @copy($backup, $target);
            throw new RuntimeException('Postflight verification failed; backup restored.');
        }

        $results[] = ['ok', 'Postflight version v002: PASS'];
        $results[] = ['ok', 'Race-effective segment-driver call: PASS'];
        $results[] = ['ok', 'Install complete. No DB writes and no snapshot files were changed.'];

        $status = 'installed';
        $headline = 'Installed successfully — v002 is active.';
        $installed = true;
        $rollbackAvailable = true;
    } elseif ($action === 'rollback') {
        if (!is_file($backup)) {
            throw new RuntimeException('Rollback backup is not available.');
        }

        if (!copy($backup, $temp)) {
            throw new RuntimeException('Could not stage rollback.');
        }

        $lint = mrl_lint_file($temp);
        $results[] = [$lint['ok'] ? 'ok' : 'bad', 'Rollback lint: ' . ($lint['message'] !== '' ? $lint['message'] : 'completed')];
        if (!$lint['ok']) {
            @unlink($temp);
            throw new RuntimeException('Rollback lint failed.');
        }

        if (!@rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Rollback rename failed.');
        }

        $restored = file_get_contents($target);
        if (!is_string($restored) || strpos($restored, '* VERSION: v001') === false) {
            throw new RuntimeException('Rollback postflight could not confirm v001.');
        }

        $results[] = ['ok', 'Rollback complete — restored pre-v002 helper.'];
        $status = 'rolledback';
        $headline = 'Rollback complete — v001 restored.';
        $rollbackAvailable = true;
    }
} catch (Throwable $e) {
    $results[] = ['bad', $e->getMessage()];
    $status = 'failed';
    $headline = 'Action failed — target was not intentionally advanced.';
    $rollbackAvailable = is_file($backup);
}

$hasResults = !empty($results);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Segment Race-Effective Fix</title>
<style>
:root{
    color-scheme:dark;
    --bg:#111;
    --panel:#1d1d1d;
    --line:#3b3b3b;
    --text:#eee;
    --muted:#b9b9b9;
    --ok:#62e6a7;
    --bad:#ff7777;
    --warn:#ffd166;
    --blue:#65b7ff;
    --green:#5ee29b;
    --red:#ef6464;
    --disabled:#565656;
    --disabledText:#9b9b9b;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.45 system-ui,-apple-system,Segoe UI,Arial,sans-serif}
.wrap{max-width:980px;margin:0 auto;padding:18px}
.panel{background:var(--panel);border:1px solid var(--line);border-radius:13px;padding:17px;margin-bottom:14px}
h1{font-size:24px;line-height:1.2;margin:0 0 8px;color:#ffd166}
p{margin:6px 0}.muted,.small{color:var(--muted)}.small{font-size:13px}
.state{font-size:17px;font-weight:800;margin:0 0 10px}
.state.ready,.state.installed{color:var(--ok)}
.state.failed{color:var(--bad)}
.state.idle,.state.rolledback{color:var(--warn)}
.msg{padding:7px 9px;border-bottom:1px solid #343434}
.msg:last-child{border-bottom:0}
.ok{color:var(--ok)}.bad{color:var(--bad)}
.actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
form{margin:0}
button{
    border:0;border-radius:9px;padding:10px 16px;
    font:inherit;font-weight:800;cursor:pointer;
}
button.preflight{background:var(--blue);color:#07131d}
button.install{background:var(--green);color:#07150d}
button.rollback{background:var(--red);color:#fff}
button.export{background:#d6c36a;color:#171400}
button:disabled{
    background:var(--disabled)!important;
    color:var(--disabledText)!important;
    cursor:not-allowed;
    opacity:.7;
}
code{color:#bfe3ff}
.flow{display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-weight:700}
.step{padding:5px 9px;border:1px solid #4a4a4a;border-radius:999px}
.arrow{color:#777}
.note{border-left:4px solid #ffd166;padding-left:11px}
</style>
</head>
<body>
<div class="wrap">

<div class="panel">
    <h1>MRL Segment Race-Effective Competitive-Driver Fix</h1>
    <p><code>race_results_snapshot_views_helper.php v001 → v002</code></p>
    <p class="muted">Installer v001 · generated 9/13/2026 4:36:46 pm ET</p>
    <p class="small">No database writes. No snapshot regeneration. Only future companion generation behavior changes.</p>
</div>

<div class="panel">
    <div class="flow">
        <span class="step">1. Preview / Preflight</span>
        <span class="arrow">→</span>
        <span class="step">2. Install v002</span>
        <span class="arrow">→</span>
        <span class="step">3. Verify / Rollback if needed</span>
    </div>
</div>

<div class="panel">
    <div class="state <?=mrl_h($status)?>"><?=mrl_h($headline)?></div>

    <?php if (!$hasResults): ?>
        <p class="muted">Nothing has run yet. Click <strong>Preview / Preflight</strong> first.</p>
    <?php else: ?>
        <div id="resultText">
        <?php foreach ($results as $row): ?>
            <div class="msg <?=mrl_h($row[0])?>"><?=mrl_h($row[1])?></div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="panel actions">
    <form method="post">
        <input type="hidden" name="action" value="preflight">
        <button class="preflight" type="submit">Preview / Preflight</button>
    </form>

    <form method="post">
        <input type="hidden" name="action" value="install">
        <button class="install" type="submit" <?=($preflightPassed && !$installed) ? '' : 'disabled'?>>Install v002</button>
    </form>

    <form method="post">
        <input type="hidden" name="action" value="rollback">
        <button class="rollback" type="submit" <?=$rollbackAvailable ? '' : 'disabled'?>>Rollback</button>
    </form>

    <button class="export" id="exportBtn" type="button" <?=$hasResults ? '' : 'disabled'?>>Export Results</button>
</div>

<div class="panel small">
    <p><strong>Expected effect:</strong> a test/admin pick can no longer make an extra driver appear in a newly generated <code>_mrl_segment</code> file.</p>
    <p><strong>Race-effective rule:</strong> SEG / ADJ supplies the baseline; LP / RD replaces it only once that row's <code>effective_race</code> has been reached.</p>
    <p><strong>Timestamp rule:</strong> the existing helper already copies the canonical ESPN snapshot modification time to the companion files; v002 preserves that behavior.</p>
    <p class="note"><strong>Important:</strong> this installer does not regenerate or alter any existing race snapshot files.</p>
</div>

</div>
<script>
(function(){
    var btn = document.getElementById('exportBtn');
    if (!btn || btn.disabled) return;

    btn.addEventListener('click', function(){
        var lines = [];
        lines.push('MRL Segment Race-Effective Fix');
        lines.push('Installer v001');
        lines.push('Generated: 9/13/2026 4:36:46 pm ET');
        lines.push('Page state: <?=mrl_h($headline)?>');
        lines.push('');

        var nodes = document.querySelectorAll('#resultText .msg');
        nodes.forEach(function(node){
            lines.push(node.textContent.trim());
        });

        var blob = new Blob([lines.join('\r\n') + '\r\n'], {type:'text/plain;charset=utf-8'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'MRL_segment_fix_results_20260913_163646.txt';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });
})();
</script>
</body>
</html>
