<?php
declare(strict_types=1);

/**
 * mrl_live_prior_year_simple_connection_fix_installer.php
 *
 * VERSION: v001
 * GENERATED: 8/21/2026 10:30:00 pm America/New_York
 *
 * LIVE MRL ONLY
 *
 * PURPOSE:
 * Apply only the proven connection-reuse fix to Live
 * prior_year_user_team_chart.php.
 *
 * CHANGE:
 * - ACTIVE include "config.php"      -> require_once "config.php"
 * - ACTIVE include "config_mrl.php"  -> require_once "config_mrl.php"
 *
 * NOTHING ELSE CHANGES.
 *
 * No query rewrites.
 * No helper functions.
 * No LP/RD/TestPHP8 code.
 * No chart markup changes.
 * No database changes.
 * No team.php changes.
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
$backupDir = $root . '/mrl_live_prior_year_simple_connection_backup_20260821_103000pm';

$checks = [];
$errors = [];
$postflight = [];
$installed = false;

function sl_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function sl_check(array &$checks, string $name, bool $ok, string $detail = ''): void
{
    $checks[] = ['name'=>$name, 'ok'=>$ok, 'detail'=>$detail];
}

function sl_atomic_write(string $path, string $content): bool
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

function sl_count_active_include(string $src, string $fileName): int
{
    $pattern = '/^[ \t]*include[ \t]+(["\'])'
        . preg_quote($fileName, '/')
        . '\1;[ \t]*(?:\/\/.*)?$/m';

    $count = preg_match_all($pattern, $src, $m);
    return $count === false ? -1 : (int)$count;
}

function sl_count_active_require_once(string $src, string $fileName): int
{
    $pattern = '/^[ \t]*require_once[ \t]+(["\'])'
        . preg_quote($fileName, '/')
        . '\1;[ \t]*(?:\/\/.*)?$/m';

    $count = preg_match_all($pattern, $src, $m);
    return $count === false ? -1 : (int)$count;
}

function sl_replace_active_include(string $src, string $fileName, int $expected): string
{
    $pattern = '/^([ \t]*)include[ \t]+(["\'])'
        . preg_quote($fileName, '/')
        . '\2;([ \t]*(?:\/\/.*)?)$/m';

    $count = preg_match_all($pattern, $src, $m);
    if ($count !== $expected) {
        throw new RuntimeException(
            $fileName . ': expected ' . $expected . ' ACTIVE include(s), found ' . $count . '.'
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

    if ($result === null || $replaced !== $expected) {
        throw new RuntimeException($fileName . ': replacement count mismatch.');
    }

    return $result;
}

// ---------------- PREFLIGHT ----------------

sl_check($checks, 'Host is LIVE MRL', in_array($host, $allowedHosts, true), $host);
sl_check($checks, 'Document root available', $root !== '' && is_dir($root), $root);
sl_check($checks, 'PHP 7.3 compatible target', PHP_VERSION_ID >= 70300, PHP_VERSION);
sl_check($checks, 'prior_year_user_team_chart.php exists', is_file($target), $target);

if (!in_array($host, $allowedHosts, true)) $errors[] = 'REFUSED: Live-MRL-only installer.';
if ($root === '' || !is_dir($root)) $errors[] = 'Document root unavailable.';
if (PHP_VERSION_ID < 70300) $errors[] = 'PHP 7.3 or newer required.';
if (!is_file($target)) $errors[] = 'prior_year_user_team_chart.php not found.';

$current = is_file($target) ? (string)@file_get_contents($target) : '';

$activeConfig = sl_count_active_include($current, 'config.php');
$activeConfigMrl = sl_count_active_include($current, 'config_mrl.php');
$reqConfig = sl_count_active_require_once($current, 'config.php');
$reqConfigMrl = sl_count_active_require_once($current, 'config_mrl.php');

$alreadyInstalled = ($activeConfig === 0 && $activeConfigMrl === 0 && $reqConfig >= 2 && $reqConfigMrl >= 1);

sl_check(
    $checks,
    'ACTIVE config.php includes',
    $activeConfig === 2 || $alreadyInstalled,
    $alreadyInstalled ? 'already converted' : 'matches: ' . $activeConfig
);

sl_check(
    $checks,
    'ACTIVE config_mrl.php includes',
    $activeConfigMrl === 1 || $alreadyInstalled,
    $alreadyInstalled ? 'already converted' : 'matches: ' . $activeConfigMrl
);

if (!$alreadyInstalled) {
    if ($activeConfig !== 2) $errors[] = 'REFUSED: expected exactly 2 ACTIVE config.php includes.';
    if ($activeConfigMrl !== 1) $errors[] = 'REFUSED: expected exactly 1 ACTIVE config_mrl.php include.';
}

$prepared = $current;

if (empty($errors) && !$alreadyInstalled) {
    try {
        $prepared = sl_replace_active_include($prepared, 'config.php', 2);
        $prepared = sl_replace_active_include($prepared, 'config_mrl.php', 1);

        $onlyExpectedChange =
            sl_count_active_include($prepared, 'config.php') === 0
            && sl_count_active_include($prepared, 'config_mrl.php') === 0
            && sl_count_active_require_once($prepared, 'config.php') === 2
            && sl_count_active_require_once($prepared, 'config_mrl.php') === 1;

        sl_check(
            $checks,
            'Simple transform prepared',
            $onlyExpectedChange,
            '3 ACTIVE include lines -> require_once; nothing else'
        );

        if (!$onlyExpectedChange) {
            $errors[] = 'Transform preparation did not produce expected include counts.';
        }
    } catch (Throwable $e) {
        $errors[] = 'Transform failed: ' . $e->getMessage();
        sl_check($checks, 'Simple transform prepared', false, $e->getMessage());
    }
}

// ---------------- INSTALL ----------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['install'])
    && empty($errors)
) {
    if ($alreadyInstalled) {
        $installed = true;
    } else {
        if (
            !is_dir($backupDir)
            && !@mkdir($backupDir, 0775, true)
            && !is_dir($backupDir)
        ) {
            $errors[] = 'Could not create backup directory.';
        }

        if (
            empty($errors)
            && !@copy($target, $backupDir . '/prior_year_user_team_chart.php')
        ) {
            $errors[] = 'Could not back up Live prior_year_user_team_chart.php.';
        }

        if (empty($errors) && !sl_atomic_write($target, $prepared)) {
            @copy($backupDir . '/prior_year_user_team_chart.php', $target);
            $errors[] = 'Write failed; rollback attempted.';
        }

        if (empty($errors)) {
            $after = (string)@file_get_contents($target);

            $postflight = [
                [
                    'No ACTIVE config.php include remains',
                    sl_count_active_include($after, 'config.php') === 0
                ],
                [
                    'No ACTIVE config_mrl.php include remains',
                    sl_count_active_include($after, 'config_mrl.php') === 0
                ],
                [
                    'Two ACTIVE config.php require_once lines installed',
                    sl_count_active_require_once($after, 'config.php') === 2
                ],
                [
                    'One ACTIVE config_mrl.php require_once line installed',
                    sl_count_active_require_once($after, 'config_mrl.php') === 1
                ],
                [
                    'Chart palette still present',
                    strpos($after, '#fabf8f') !== false
                    && strpos($after, '#b7dee8') !== false
                    && strpos($after, '#d9d9d9') !== false
                    && strpos($after, '#c4bd97') !== false
                    && strpos($after, '#b8cce4') !== false
                    && strpos($after, '#d8e4bc') !== false
                ],
                [
                    'Four original PDO segment loops still present',
                    substr_count($after, 'foreach ($dbo->query($sql) as $row) {') === 4
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
<title>MRL Live Simple Prior-Year Connection Fix</title>
<style>
:root{color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;background:#111;color:#eee;font:14px/1.4 Arial,Helvetica,sans-serif}
.wrap{max-width:1150px;margin:0 auto;padding:15px}
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
    <h1>MRL LIVE Simple Prior-Year Connection Fix v001</h1>
    <div class="sub">LIVE MRL ONLY • generated 8/21/2026 10:30:00 pm • DB changes: NONE</div>
</div>

<div class="card">
    <h2>Exactly Three Line Changes</h2>
    <div>
        This installer does only the connection-reuse change that fixed TestPHP8:
        the two active <code>config.php</code> includes and the one active
        <code>config_mrl.php</code> include become <code>require_once</code>.
    </div>
    <div class="note" style="margin-top:7px">
        No query changes. No error helper. No LP/RD code. No chart markup changes.
        No database changes. No team.php changes.
    </div>
</div>

<div class="card">
    <h2>Preflight</h2>
    <table>
    <?php foreach ($checks as $c): ?>
        <tr>
            <td style="width:46%"><?=sl_h($c['name'])?></td>
            <td style="width:8%" class="<?=$c['ok']?'ok':'bad'?>"><?=$c['ok']?'PASS':'FAIL'?></td>
            <td><?=sl_h($c['detail'])?></td>
        </tr>
    <?php endforeach; ?>
    </table>
</div>

<?php if (!empty($errors)): ?>
<div class="card">
    <h2 class="bad">STOPPED SAFELY</h2>
    <?php foreach ($errors as $e): ?><div class="bad">• <?=sl_h($e)?></div><?php endforeach; ?>
</div>
<?php elseif (!$installed): ?>
<div class="card">
    <h2>Ready to Install on LIVE</h2>
    <form method="post">
        <button type="submit" name="install" value="1">INSTALL 3-LINE LIVE FIX</button>
    </form>
</div>
<?php endif; ?>

<?php if ($installed): ?>
<div class="card success">
    <h2 class="ok">INSTALL COMPLETE</h2>

    <?php if (is_dir($backupDir)): ?>
    <div><strong>Backup folder:</strong><br><code><?=sl_h($backupDir)?></code></div>
    <?php endif; ?>

    <table style="margin-top:8px">
    <?php foreach ($postflight as $pf): ?>
        <tr>
            <td><?=sl_h($pf[0])?></td>
            <td class="<?=$pf[1]?'ok':'bad'?>"><?=$pf[1]?'PASS':'FAIL'?></td>
        </tr>
    <?php endforeach; ?>
    </table>

    <div style="margin-top:10px"><a href="/team.php">Open Live team.php</a></div>

    <div class="note" style="margin-top:8px">
        Verify prior years continue through 2017 and the copyright footer appears.
        This installer intentionally does not add the optional red error panel.
    </div>
</div>
<?php endif; ?>

</div>
</body>
</html>
