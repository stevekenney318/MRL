<?php
declare(strict_types=1);

/**
 * MRL_install_team_user_menu_header_filter_test_v001.php
 *
 * PURPOSE
 * -------
 * Narrow diagnostic/fix for the Team-page account dropdown layering problem.
 *
 * Based on the new observation that the problem is unchanged regardless of
 * whether Admin Menu is open, this targets the common sticky masthead itself.
 *
 * INSTALLS
 * --------
 * - team.php v049 -> v050
 * - Leaves the known-working account-menu HTML/CSS/JS behavior unchanged.
 * - Removes backdrop-filter only from .mrl-rd-sticky.
 * - Keeps the same masthead background, border, radius, shadow, position,
 *   z-index, dimensions, and dropdown z-index values.
 * - No DB, picks/scoring, scheduler, theme-selection, or menu-content changes.
 *
 * SAFETY
 * ------
 * - Exact v049 preflight signatures.
 * - Timestamped backup.
 * - Atomic write.
 * - Post-write verification.
 * - Automatic rollback on failure.
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v001';

$root = __DIR__;
$teamFile = $root . '/team.php';
$generated = '9/13/2026 8:22:22 am ET';
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

function backup_file(string $path, string $stamp): string
{
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

function badge(string $status): string
{
    $class = $status === 'PASS' ? 'pass' : 'fail';
    return '<span class="' . $class . '">' . h($status) . '</span>';
}

$preflight = [];
$errors = [];

$readable = is_file($teamFile) && is_readable($teamFile);
$preflight[] = [
    'team.php',
    $readable ? 'PASS' : 'FAIL',
    $readable ? 'Readable' : 'Missing/unreadable'
];

$source = $readable ? (string)@file_get_contents($teamFile) : '';

if ($readable) {
    $checks = [
        ['* VERSION: v049', 1, 'v049 version header'],
        ['mrl-rd-user-menu-portal', 0, 'v048 portal marker absent'],
        ["var open = user.classList.toggle('open');", 1, 'known-working menu toggle'],
        ['z-index:5000;', 1, 'sticky z-index'],
        ['justify-self:start;z-index:5001', 1, 'user wrapper z-index'],
        ['z-index:5002;', 1, 'menu z-index'],
        ["            backdrop-filter:blur(3px);\n            -webkit-backdrop-filter:blur(3px);", 1, 'sticky masthead backdrop filter'],
    ];

    foreach ($checks as [$needle, $expected, $label]) {
        $count = substr_count($source, $needle);
        if ($count !== $expected) {
            $errors[] = $label . ': expected ' . $expected . ', found ' . $count;
        }
    }

    $preflight[] = [
        'team.php source signatures',
        empty($errors) ? 'PASS' : 'FAIL',
        empty($errors)
            ? 'v049 known-working menu + sticky masthead filter signatures found'
            : implode('; ', $errors)
    ];
}

$canInstall = $readable && empty($errors);

$installed = false;
$installMessages = [];
$installError = '';
$backupPath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    if (!$canInstall) {
        $installError = 'Preflight failed. No files were changed.';
    } else {
        try {
            $newSource = $source;

            $newSource = replace_exact(
                $newSource,
                '* VERSION: v049',
                '* VERSION: v050',
                1,
                'Version header'
            );

            if (!preg_match('/\* LAST MODIFIED: .*$/m', $newSource, $lastModifiedMatch)) {
                throw new RuntimeException('Could not locate LAST MODIFIED header.');
            }

            $newSource = replace_exact(
                $newSource,
                $lastModifiedMatch[0],
                '* LAST MODIFIED: 9/13/2026 8:22:22 am',
                1,
                'Last modified'
            );

            $newSource = replace_exact(
                $newSource,
                " * CHANGELOG:\n *\n * v049",
                " * CHANGELOG:\n"
                . " *\n"
                . " * v050 (9/13/2026 8:22:22 am)\n"
                . " * - TEST/FIX: Removes backdrop-filter from the sticky masthead, the common parent of the account dropdown.\n"
                . " * - PRESERVE: Account-menu HTML/CSS/JS behavior, geometry, links, and existing z-index values unchanged.\n"
                . " * - PRESERVE: Masthead background/border/radius/shadow/layout plus picks, scoring, scheduler, themes, and DB behavior unchanged.\n"
                . " *\n"
                . " * v049",
                1,
                'Changelog insertion'
            );

            $oldFilter = "            backdrop-filter:blur(3px);\n            -webkit-backdrop-filter:blur(3px);";
            $newFilter = "            /* v050: backdrop filtering removed here to avoid trapping the dropdown in a Chrome compositing layer. */";

            $newSource = replace_exact(
                $newSource,
                $oldFilter,
                $newFilter,
                1,
                'Sticky masthead backdrop-filter removal'
            );

            if (
                strpos($newSource, '* VERSION: v050') === false ||
                strpos($newSource, 'backdrop filtering removed here') === false ||
                strpos($newSource, $oldFilter) !== false ||
                strpos($newSource, "var open = user.classList.toggle('open');") === false ||
                strpos($newSource, 'z-index:5000;') === false ||
                strpos($newSource, 'justify-self:start;z-index:5001') === false ||
                strpos($newSource, 'z-index:5002;') === false
            ) {
                throw new RuntimeException('Generated source verification failed before write.');
            }

            $backupPath = backup_file($teamFile, $backupStamp);

            if (!atomic_write($teamFile, $newSource)) {
                throw new RuntimeException('Could not write team.php.');
            }

            $verify = (string)@file_get_contents($teamFile);

            if (
                strpos($verify, '* VERSION: v050') === false ||
                strpos($verify, 'backdrop filtering removed here') === false ||
                strpos($verify, $oldFilter) !== false
            ) {
                throw new RuntimeException('Post-write verification failed.');
            }

            $installMessages[] = 'team.php v049 -> v050';
            $installMessages[] = 'Removed backdrop-filter only from the sticky masthead.';
            $installMessages[] = 'Account-menu behavior and all existing z-index values preserved.';
            $installMessages[] = 'No database writes performed.';
            $installed = true;

        } catch (Throwable $e) {
            $installError = $e->getMessage();

            if ($backupPath !== '' && is_file($backupPath)) {
                @copy($backupPath, $teamFile);
                $installError .= ' team.php was rolled back from backup.';
            }
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRL Team User Menu Header Filter Test v001</title>
<style>
:root{
    color-scheme:dark;
    --bg:#121212;--panel:#1d1d1d;--border:#454545;--text:#f2f2f2;
    --gold:#ffc866;--green:#5cf0a7;--red:#ff7474;
}
*{box-sizing:border-box}
body{
    margin:0;padding:14px;background:var(--bg);color:var(--text);
    font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.35
}
.wrap{max-width:1180px;margin:0 auto}
.panel{
    background:var(--panel);border:1px solid var(--border);border-radius:14px;
    padding:18px 20px;margin-bottom:14px
}
h1,h2{color:var(--gold);margin:0 0 8px}h1{font-size:25px}h2{font-size:20px}
p{margin:6px 0}ul{margin:8px 0 0 22px;padding:0}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{text-align:left;padding:10px;border-bottom:1px solid #3b3b3b;vertical-align:top}
th{color:var(--gold)}.pass{color:var(--green);font-weight:700}.fail,.error{color:var(--red);font-weight:700}
code{background:#111;border-radius:5px;padding:1px 5px;color:#fff}
button{
    border:1px solid #a97600;background:#5d4300;color:#ffd27a;font-weight:700;
    font-size:16px;padding:11px 16px;border-radius:10px;cursor:pointer
}
button:disabled{opacity:.45;cursor:not-allowed}.complete{color:var(--green);font-size:20px;font-weight:700}
</style>
</head>
<body>
<div class="wrap">

<div class="panel">
    <h1>MRL Team User Menu Header Filter Test v001</h1>
    <p>Generated <?= h($generated) ?> · One-file common-parent test · No DB writes</p>
</div>

<div class="panel">
    <h2>What this installs</h2>
    <ul>
        <li>Updates <code>team.php</code> v049 → v050.</li>
        <li>Leaves the visible/working account-menu mechanism completely alone.</li>
        <li>Removes only the blur/filter effect from the sticky masthead that contains the menu.</li>
        <li>Keeps masthead background, border, radius, shadow, position, dimensions, and z-index values.</li>
        <li>No picks/scoring, scheduler, theme-selection, menu-content, or database changes.</li>
    </ul>
</div>

<div class="panel">
    <h2>Preflight</h2>
    <table>
        <thead><tr><th>Target</th><th>Status</th><th>Detail</th></tr></thead>
        <tbody>
        <?php foreach ($preflight as $row): ?>
            <tr><td><?= h($row[0]) ?></td><td><?= badge($row[1]) ?></td><td><?= h($row[2]) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($installed): ?>
<div class="panel">
    <div class="complete">INSTALL COMPLETE</div>
    <ul><?php foreach ($installMessages as $message): ?><li><?= h($message) ?></li><?php endforeach; ?></ul>
</div>
<div class="panel">
    <p class="pass">Done. Reload Team page and test the account dropdown normally; Admin Menu state does not matter.</p>
</div>
<?php elseif ($installError !== ''): ?>
<div class="panel"><div class="error">INSTALL FAILED</div><p><?= h($installError) ?></p></div>
<?php endif; ?>

<?php if (!$installed): ?>
<div class="panel">
    <form method="post">
        <button type="submit" name="install" value="1" <?= $canInstall ? '' : 'disabled' ?>>Install Test Fix</button>
    </form>
</div>
<?php endif; ?>

</div>
</body>
</html>
