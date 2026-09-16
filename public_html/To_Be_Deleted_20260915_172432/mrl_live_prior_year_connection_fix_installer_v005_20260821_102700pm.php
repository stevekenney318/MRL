<?php
declare(strict_types=1);

/**
 * mrl_live_prior_year_connection_fix_installer.php
 *
 * VERSION: v005
 * GENERATED: 8/21/2026 10:27:00 pm America/New_York
 *
 * LIVE MRL ONLY
 *
 * PURPOSE:
 * Apply only the proven prior-year connection fix to the older Live
 * prior_year_user_team_chart.php without bringing over TestPHP8 LP/RD/chart
 * modernization work.
 *
 * LIVE SOURCE VERIFIED BEFORE PACKAGING:
 * - No modern file version header.
 * - config.php is included twice.
 * - config_mrl.php is included once.
 * - Four direct PDO user_picks loops exist for S1-S4.
 *
 * CHANGES:
 * 1) Change repeated config/config_mrl includes to require_once so the file
 *    reuses the connection/config state already loaded by team.php.
 * 2) Wrap each of the four existing segment queries in a small helper that
 *    catches Throwable and renders a large red MRL error panel naming the
 *    affected year/segment.
 *
 * PRESERVES:
 * - Existing Live chart HTML, colors, labels, and order.
 * - Existing Live user_picks data source.
 * - Existing "Playoffs" S4 wording.
 * - No LP/RD/TestPHP8 presentation code is imported.
 * - No database changes.
 * - No team.php changes.
 *
 * PHP 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

$allowedHosts = [
    'manliusracingleague.com',
    'www.manliusracingleague.com',
];

$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$target = $root . '/prior_year_user_team_chart.php';
$backupDir = $root . '/mrl_live_prior_year_connection_backup_20260821_102700pm';

$checks = [];
$errors = [];
$postflight = [];
$installed = false;

function lpy_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function lpy_check(array &$checks, string $name, bool $ok, string $detail = ''): void
{
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
}

function lpy_replace_exact_count(
    string $src,
    string $old,
    string $new,
    int $expectedCount,
    string $label
): string {
    $count = substr_count($src, $old);

    if ($count !== $expectedCount) {
        throw new RuntimeException(
            $label . ': expected ' . $expectedCount . ' match(es), found ' . $count . '.'
        );
    }

    return str_replace($old, $new, $src);
}

function lpy_replace_active_include(
    string $src,
    string $fileName,
    int $expectedCount,
    string $label
): string {
    $pattern = '/^([ \t]*)include[ \t]+(["\'])'
        . preg_quote($fileName, '/')
        . '\\2;([ \t]*(?:\\/\\/.*)?)$/m';

    $count = preg_match_all($pattern, $src, $matches);
    if ($count !== $expectedCount) {
        throw new RuntimeException(
            $label . ': expected ' . $expectedCount . ' ACTIVE match(es), found ' . $count . '.'
        );
    }

    $result = preg_replace_callback(
        $pattern,
        function ($m) use ($fileName) {
            return $m[1] . 'require_once "' . $fileName . '";' . $m[3];
        },
        $src,
        -1,
        $replaced
    );

    if ($result === null || $replaced !== $expectedCount) {
        throw new RuntimeException(
            $label . ': replacement failed or replaced unexpected count.'
        );
    }

    return $result;
}

function lpy_atomic_write(string $path, string $content): bool
{
    $tmp = $path . '.mrl_tmp_' . str_replace('.', '', uniqid('', true));

    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

// ---------------- PREFLIGHT ----------------

lpy_check($checks, 'Host is LIVE MRL', in_array($host, $allowedHosts, true), $host);
lpy_check($checks, 'Document root available', $root !== '' && is_dir($root), $root);
lpy_check($checks, 'PHP 7.3 compatible target', PHP_VERSION_ID >= 70300, PHP_VERSION);
lpy_check($checks, 'prior_year_user_team_chart.php exists', is_file($target), $target);

if (!in_array($host, $allowedHosts, true)) {
    $errors[] = 'REFUSED: Live-MRL-only installer.';
}
if ($root === '' || !is_dir($root)) {
    $errors[] = 'Document root unavailable.';
}
if (PHP_VERSION_ID < 70300) {
    $errors[] = 'PHP 7.3 or newer required.';
}
if (!is_file($target)) {
    $errors[] = 'prior_year_user_team_chart.php not found.';
}

$current = is_file($target) ? (string)@file_get_contents($target) : '';

$alreadyPatched = strpos($current, 'MRL LIVE PRIOR-YEAR CONNECTION FIX v001') !== false;

if (!$alreadyPatched && empty($errors)) {
    /*
     * Count ACTIVE include lines only.  The Live file contains a commented-out
     * config_mrl.php example line, so raw substring counting is intentionally
     * not used here.
     */
    $activeConfigPhp = preg_match_all('/^[ \\t]*include[ \\t]+["\\\']config\\.php["\\\'];[ \\t]*(?:\\/\\/.*)?$/m', $current, $m1);
    $activeConfigMrl = preg_match_all('/^[ \\t]*include[ \\t]+["\\\']config_mrl\\.php["\\\'];[ \\t]*(?:\\/\\/.*)?$/m', $current, $m2);

    $markers = [
        [$activeConfigPhp, 2, 'Live source has two ACTIVE config.php includes'],
        [$activeConfigMrl, 1, 'Live source has one ACTIVE config_mrl.php include'],
        [substr_count($current, 'foreach ($dbo->query($sql) as $row) {'), 4, 'Live source has four direct PDO segment loops'],
        [substr_count($current, "'Segment #1'"), 1, 'S1 display marker present'],
        [substr_count($current, "'Segment #2'"), 1, 'S2 display marker present'],
        [substr_count($current, "'Segment #3'"), 1, 'S3 display marker present'],
        [substr_count($current, "'Playoffs'"), 1, 'S4 Playoffs display marker present'],
    ];

    foreach ($markers as $m) {
        $count = (int)$m[0];
        $ok = ($count === (int)$m[1]);
        lpy_check($checks, $m[2], $ok, 'matches: ' . $count);

        if (!$ok) {
            $errors[] = 'REFUSED: Live source marker mismatch: ' . $m[2];
        }
    }
} elseif ($alreadyPatched) {
    lpy_check($checks, 'Focused Live fix already installed', true, 'marker present');
}

$prepared = $current;

if (!$alreadyPatched && empty($errors)) {
    try {
        $header = <<<'HDR'
<?php
/*
 * MRL LIVE PRIOR-YEAR CONNECTION FIX v001
 * Applied 8/21/2026 10:02:00 pm America/New_York
 * - Reuses existing config/database bootstrap with require_once.
 * - Adds visible red prior-year query error panels.
 * - No LP/RD/TestPHP8 chart modernization imported.
 */

HDR;

        if (strpos($prepared, '<?php') !== 0) {
            throw new RuntimeException('Expected PHP opening tag not found at file start.');
        }

        $prepared = $header . substr($prepared, 6);

        $prepared = lpy_replace_active_include(
            $prepared,
            'config.php',
            2,
            'config.php reuse'
        );

        $prepared = lpy_replace_active_include(
            $prepared,
            'config_mrl.php',
            1,
            'config_mrl.php reuse'
        );

        $insertMarker = <<<'OLD'
// include CSS Style Sheet
OLD;

        $helper = <<<'NEW'
if (!function_exists('mrl_live_prior_year_query_rows')) {
    function mrl_live_prior_year_query_rows($dbo, $sql, $prevRaceYear, $segmentLabel)
    {
        try {
            $result = $dbo->query($sql);

            if (!$result) {
                return [];
            }

            return $result->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            echo "<div style='width:80%;margin:10px auto;padding:12px 14px;"
                . "background:#5b1111;color:#ffffff;border:2px solid #ff5c5c;"
                . "border-radius:6px;font-family:Arial,sans-serif;font-size:15px;"
                . "line-height:1.4;'>"
                . "<strong>MRL ERROR — Previous-year chart could not be loaded.</strong><br>"
                . "Year: " . htmlspecialchars((string)$prevRaceYear, ENT_QUOTES, 'UTF-8')
                . " &nbsp; Segment: " . htmlspecialchars((string)$segmentLabel, ENT_QUOTES, 'UTF-8')
                . "<br><span style='font-size:13px;'>"
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
                . "</span></div>";

            return [];
        }
    }
}

// include CSS Style Sheet
NEW;

        $prepared = lpy_replace_exact_count(
            $prepared,
            $insertMarker,
            $helper,
            1,
            'visible-error helper insertion'
        );

        $segmentMap = [
            'S1' => 'Segment #1',
            'S2' => 'Segment #2',
            'S3' => 'Segment #3',
            'S4' => 'Playoffs',
        ];

        foreach ($segmentMap as $seg => $label) {
            $old = "foreach (\$dbo->query(\$sql) as \$row) {";
            $new = "foreach (mrl_live_prior_year_query_rows(\$dbo, \$sql, \$prevRaceYear, '" . $label . "') as \$row) {";

            $pos = strpos($prepared, $old);
            if ($pos === false) {
                throw new RuntimeException('Could not locate direct PDO loop for ' . $seg . '.');
            }

            $prepared = substr_replace($prepared, $new, $pos, strlen($old));
        }

        lpy_check(
            $checks,
            'Focused Live transform prepared',
            true,
            'connection reuse + visible error handling only'
        );
    } catch (Throwable $e) {
        $errors[] = 'Transform failed: ' . $e->getMessage();
        lpy_check($checks, 'Focused Live transform prepared', false, $e->getMessage());
    }
}

// ---------------- INSTALL ----------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['install']) &&
    empty($errors)
) {
    if ($alreadyPatched) {
        $installed = true;
    } else {
        if (
            !is_dir($backupDir) &&
            !@mkdir($backupDir, 0775, true) &&
            !is_dir($backupDir)
        ) {
            $errors[] = 'Could not create backup directory: ' . $backupDir;
        }

        if (
            empty($errors) &&
            !@copy($target, $backupDir . '/prior_year_user_team_chart.php')
        ) {
            $errors[] = 'Could not back up Live prior_year_user_team_chart.php.';
        }

        if (empty($errors) && !lpy_atomic_write($target, $prepared)) {
            @copy($backupDir . '/prior_year_user_team_chart.php', $target);
            $errors[] = 'Write failed; rollback attempted.';
        }

        if (empty($errors)) {
            $after = (string)@file_get_contents($target);

            $postflight = [
                [
                    'Focused Live fix marker installed',
                    strpos($after, 'MRL LIVE PRIOR-YEAR CONNECTION FIX v001') !== false
                ],
                [
                    'Both config.php loads now use require_once',
                    substr_count($after, 'require_once "config.php";') === 2
                ],
                [
                    'config_mrl.php load now uses require_once',
                    substr_count($after, 'require_once "config_mrl.php";') === 1
                ],
                [
                    'No ACTIVE plain config includes remain',
                    preg_match('/^[ \t]*include[ \t]+["\']config\\.php["\'];/m', $after) !== 1
                    && preg_match('/^[ \t]*include[ \t]+["\']config_mrl\\.php["\'];/m', $after) !== 1
                ],
                [
                    'All four direct PDO loops removed',
                    substr_count($after, 'foreach ($dbo->query($sql) as $row) {') === 0
                ],
                [
                    'Safe query helper installed',
                    strpos($after, 'function mrl_live_prior_year_query_rows') !== false
                ],
                [
                    'S1 safe-query call installed',
                    strpos($after, "mrl_live_prior_year_query_rows($dbo, $sql, $prevRaceYear, 'Segment #1')") !== false
                ],
                [
                    'S2 safe-query call installed',
                    strpos($after, "mrl_live_prior_year_query_rows($dbo, $sql, $prevRaceYear, 'Segment #2')") !== false
                ],
                [
                    'S3 safe-query call installed',
                    strpos($after, "mrl_live_prior_year_query_rows($dbo, $sql, $prevRaceYear, 'Segment #3')") !== false
                ],
                [
                    'S4 safe-query call installed',
                    strpos($after, "mrl_live_prior_year_query_rows($dbo, $sql, $prevRaceYear, 'Playoffs')") !== false
                ],
                [
                    'Visible MRL error panel installed',
                    strpos($after, 'MRL ERROR — Previous-year chart could not be loaded.') !== false
                ],
                [
                    'No LP/RD modern chart code imported',
                    strpos($after, 'pyutc_build_year_chart_context') === false
                    && strpos($after, 'effective_race') === false
                    && strpos($after, 'supersedes_pickID') === false
                ],
                [
                    'Live chart palette preserved',
                    strpos($after, '#fabf8f') !== false
                    && strpos($after, '#b7dee8') !== false
                    && strpos($after, '#d9d9d9') !== false
                    && strpos($after, '#c4bd97') !== false
                    && strpos($after, '#b8cce4') !== false
                    && strpos($after, '#d8e4bc') !== false
                ],
            ];

            foreach ($postflight as $pf) {
                if (!$pf[1]) {
                    $errors[] = 'Postflight failed: ' . $pf[0];
                }
            }

            if (empty($errors)) {
                $installed = true;
            } else {
                @copy($backupDir . '/prior_year_user_team_chart.php', $target);
                $errors[] = 'Postflight failure triggered rollback.';
            }
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Live Prior-Year Connection Fix</title>
<style>
:root{color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;background:#111;color:#eee;font:14px/1.4 Arial,Helvetica,sans-serif}
.wrap{max-width:1220px;margin:0 auto;padding:15px}
.banner{background:#15331f;border:1px solid #27854a;border-radius:11px;padding:12px 15px}
.banner h1{margin:0;color:#dcffe7;font-size:22px}
.sub{font-size:12px;color:#b9eac8;margin-top:4px}
.card{background:#1d1d1d;border:1px solid #444;border-radius:9px;padding:12px 14px;margin-top:11px}
h2{margin:0 0 8px;color:#b8efc8;font-size:18px}
table{width:100%;border-collapse:collapse}
td{padding:6px 7px;border-bottom:1px solid #333;vertical-align:top}
.ok{color:#5cf09a;font-weight:700}
.bad{color:#ff7777;font-weight:700}
code{color:#f0d98c}
button{background:#163d27;color:#baffcf;border:1px solid #398b58;border-radius:7px;padding:9px 14px;font-weight:700;cursor:pointer}
.success{background:#143b2b;border-color:#2c7754}
.note{font-size:12px;color:#bbb;line-height:1.45}
a{color:#8fc7ff}
</style>
</head>
<body>
<div class="wrap">

<div class="banner">
    <h1>MRL LIVE Prior-Year Connection Fix Installer v005</h1>
    <div class="sub">LIVE MRL ONLY • generated 8/21/2026 10:27:00 pm • DB changes: NONE</div>
</div>

<div class="card">
    <h2>Focused Live Scope</h2>
    <div>
        The Live source was checked separately. v005 keeps the proven transform unchanged and makes postflight validate the result semantically instead of with one brittle exact substring count. This installer changes only the
        old Live <code>prior_year_user_team_chart.php</code>.
    </div>
    <div class="note" style="margin-top:7px">
        It does not copy the TestPHP8 v004 chart file and does not bring over LP,
        RD, merged-row, effective-race, or other TestPHP8 presentation changes.
        It applies only the proven connection-reuse fix plus visible query errors.
    </div>
</div>

<div class="card">
    <h2>Preflight</h2>
    <table>
        <?php foreach ($checks as $c): ?>
        <tr>
            <td style="width:46%"><?=lpy_h($c['name'])?></td>
            <td style="width:8%" class="<?=$c['ok']?'ok':'bad'?>"><?=$c['ok']?'PASS':'FAIL'?></td>
            <td><?=lpy_h($c['detail'])?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php if (!empty($errors)): ?>
<div class="card">
    <h2 class="bad">STOPPED SAFELY</h2>
    <?php foreach ($errors as $e): ?><div class="bad">• <?=lpy_h($e)?></div><?php endforeach; ?>
</div>
<?php elseif (!$installed): ?>
<div class="card">
    <h2>Ready to Install on LIVE</h2>
    <div class="note" style="margin-bottom:9px">
        A timestamped backup of the existing Live file is created before writing.
        No database changes are made.
    </div>
    <form method="post">
        <button type="submit" name="install" value="1">INSTALL FOCUSED LIVE FIX</button>
    </form>
</div>
<?php endif; ?>

<?php if ($installed): ?>
<div class="card success">
    <h2 class="ok">INSTALL COMPLETE</h2>

    <?php if (is_dir($backupDir)): ?>
    <div><strong>Backup folder:</strong><br><code><?=lpy_h($backupDir)?></code></div>
    <?php endif; ?>

    <table style="margin-top:8px">
        <?php foreach ($postflight as $pf): ?>
        <tr>
            <td><?=lpy_h($pf[0])?></td>
            <td class="<?=$pf[1]?'ok':'bad'?>"><?=$pf[1]?'PASS':'FAIL'?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div style="margin-top:10px">
        <a href="/team.php">Open Live team.php</a>
    </div>

    <div class="note" style="margin-top:8px">
        Verify the Previous Years Picks section now continues through 2017,
        chart appearance remains unchanged, the copyright footer is visible,
        and no SQLSTATE error appears. If a prior-year query fails, the new
        red MRL error panel should identify the exact year/segment.
    </div>
</div>
<?php endif; ?>

</div>
</body>
</html>
