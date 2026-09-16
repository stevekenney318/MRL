<?php
declare(strict_types=1);

/**
 * MRL_install_team_user_menu_restore_v002.php
 *
 * PURPOSE
 * -------
 * Corrected recovery installer for the Team page account dropdown.
 *
 * v002 correction:
 * - v001 preflight expected 5 occurrences of the portal marker.
 * - The installed v048 source contains 6 legitimate occurrences.
 * - v002 expects 6 and removes the v048 portal experiment completely.
 *
 * INSTALLS
 * --------
 * - team.php v048 -> v049
 * - Removes v048 body-level portal CSS/JS.
 * - Restores the known-working v047 account-menu open/close behavior.
 * - Keeps the v047 z-index values for later diagnosis.
 * - No DB, pick/scoring, scheduler, theme, or menu-content changes.
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v002';

$root = __DIR__;
$teamFile = $root . '/team.php';
$generated = '9/13/2026 8:07:13 am ET';
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
$preflight[] = ['team.php', $readable ? 'PASS' : 'FAIL', $readable ? 'Readable' : 'Missing/unreadable'];

$source = $readable ? (string)@file_get_contents($teamFile) : '';

if ($readable) {
    $checks = [
        ['* VERSION: v048', 1, 'v048 version header'],
        ['mrl-rd-user-menu-portal', 6, 'v048 portal marker'],
        ['document.body.appendChild(menu);', 1, 'v048 portal append'],
        ["window.addEventListener('scroll', positionUserMenuPortal, true);", 1, 'v048 scroll tracking'],
        ['z-index:5000;', 1, 'v047 sticky z-index retained'],
        ['justify-self:start;z-index:5001', 1, 'v047 user wrapper z-index retained'],
        ['z-index:5002;', 1, 'v047 menu z-index retained'],
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
        empty($errors) ? 'v048 portal + v047 stacking signatures found' : implode('; ', $errors)
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

            $newSource = replace_exact($newSource, '* VERSION: v048', '* VERSION: v049', 1, 'Version header');

            if (!preg_match('/\* LAST MODIFIED: .*$/m', $newSource, $lastModifiedMatch)) {
                throw new RuntimeException('Could not locate LAST MODIFIED header.');
            }
            $newSource = replace_exact(
                $newSource,
                $lastModifiedMatch[0],
                '* LAST MODIFIED: 9/13/2026 8:07:13 am',
                1,
                'Last modified'
            );

            $newSource = replace_exact(
                $newSource,
                " * CHANGELOG:\n *\n * v048",
                " * CHANGELOG:\n"
                . " *\n"
                . " * v049 (9/13/2026 8:07:13 am)\n"
                . " * - RESTORE: Removes v048 body-level dropdown portal experiment after it suppressed the account menu.\n"
                . " * - RESTORE: Returns account dropdown to the known-working v047 open/close behavior.\n"
                . " * - PRESERVE: Keeps v047 stacking values for later diagnosis; all other Team-page behavior unchanged.\n"
                . " *\n"
                . " * v048",
                1,
                'Changelog insertion'
            );

            $portalCss = <<<'CSS'
        .mrl-rd-user-menu.mrl-rd-user-menu-portal{
            display:block!important;
            position:fixed!important;
            z-index:2147483000!important;
            margin:0!important;
        }
CSS;

            $newSource = replace_exact($newSource, $portalCss, '', 1, 'Portal CSS removal');

            $newSource = replace_exact(
                $newSource,
                "    var user = document.getElementById('mrl-rd-user');\n"
                . "    var button = document.getElementById('mrl-rd-user-button');\n"
                . "    var menu = document.getElementById('mrl-rd-user-menu');\n"
                . "    var menuPlaceholder = null;",
                "    var user = document.getElementById('mrl-rd-user');\n"
                . "    var button = document.getElementById('mrl-rd-user-button');",
                1,
                'Portal JS variables removal'
            );

            $portalBlock = <<<'PORTAL'
    if (user && button && menu) {
        function positionUserMenuPortal() {
            if (!menu.classList.contains('mrl-rd-user-menu-portal')) return;

            var rect = button.getBoundingClientRect();
            menu.style.left = Math.round(rect.left) + 'px';
            menu.style.top = Math.round(rect.bottom + 7) + 'px';
        }

        function openUserMenu() {
            if (menu.classList.contains('mrl-rd-user-menu-portal')) return;

            menuPlaceholder = document.createComment('mrl-user-menu-placeholder');
            menu.parentNode.insertBefore(menuPlaceholder, menu);
            document.body.appendChild(menu);

            user.classList.add('open');
            menu.classList.add('mrl-rd-user-menu-portal');
            positionUserMenuPortal();

            button.setAttribute('aria-expanded', 'true');
        }

        function closeUserMenu() {
            user.classList.remove('open');
            menu.classList.remove('mrl-rd-user-menu-portal');
            menu.style.left = '';
            menu.style.top = '';

            if (menuPlaceholder && menuPlaceholder.parentNode) {
                menuPlaceholder.parentNode.insertBefore(menu, menuPlaceholder);
                menuPlaceholder.parentNode.removeChild(menuPlaceholder);
            }

            menuPlaceholder = null;
            button.setAttribute('aria-expanded', 'false');
        }

        button.addEventListener('click', function (event) {
            event.stopPropagation();

            if (menu.classList.contains('mrl-rd-user-menu-portal')) {
                closeUserMenu();
            } else {
                openUserMenu();
            }
        });

        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function () {
            closeUserMenu();
        });

        window.addEventListener('resize', positionUserMenuPortal);
        window.addEventListener('scroll', positionUserMenuPortal, true);
    }
PORTAL;

            $restoredBlock = <<<'RESTORED'
    if (user && button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = user.classList.toggle('open');
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            user.classList.remove('open');
            button.setAttribute('aria-expanded', 'false');
        });
    }
RESTORED;

            $newSource = replace_exact($newSource, $portalBlock, $restoredBlock, 1, 'Portal JS restoration');

            if (
                strpos($newSource, '* VERSION: v049') === false ||
                strpos($newSource, 'mrl-rd-user-menu-portal') !== false ||
                strpos($newSource, 'document.body.appendChild(menu);') !== false ||
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
                strpos($verify, '* VERSION: v049') === false ||
                strpos($verify, 'mrl-rd-user-menu-portal') !== false ||
                strpos($verify, "var open = user.classList.toggle('open');") === false
            ) {
                throw new RuntimeException('Post-write verification failed.');
            }

            $installMessages[] = 'team.php v048 -> v049';
            $installMessages[] = 'Removed v048 body-level portal CSS/JS.';
            $installMessages[] = 'Restored known-working v047 account-menu open/close behavior.';
            $installMessages[] = 'Retained v047 z-index values for later diagnosis.';
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
<title>MRL Team User Menu Restore v002</title>
<style>
:root{color-scheme:dark;--bg:#121212;--panel:#1d1d1d;--border:#454545;--text:#f2f2f2;--gold:#ffc866;--green:#5cf0a7;--red:#ff7474}
*{box-sizing:border-box}
body{margin:0;padding:14px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.35}
.wrap{max-width:1180px;margin:0 auto}
.panel{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:14px}
h1,h2{color:var(--gold);margin:0 0 8px} h1{font-size:25px} h2{font-size:20px}
p{margin:6px 0} ul{margin:8px 0 0 22px;padding:0}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{text-align:left;padding:10px;border-bottom:1px solid #3b3b3b;vertical-align:top}
th{color:var(--gold)} .pass{color:var(--green);font-weight:700}.fail,.error{color:var(--red);font-weight:700}
code{background:#111;border-radius:5px;padding:1px 5px;color:#fff}
button{border:1px solid #a97600;background:#5d4300;color:#ffd27a;font-weight:700;font-size:16px;padding:11px 16px;border-radius:10px;cursor:pointer}
button:disabled{opacity:.45;cursor:not-allowed}.complete{color:var(--green);font-size:20px;font-weight:700}
</style>
</head>
<body>
<div class="wrap">

<div class="panel">
    <h1>MRL Team User Menu Restore v002</h1>
    <p>Generated <?= h($generated) ?> · Corrected preflight · No DB writes</p>
</div>

<div class="panel">
    <h2>v002 correction</h2>
    <ul>
        <li>v001 expected 5 portal-marker occurrences.</li>
        <li>The installed v048 source contains 6 legitimate occurrences.</li>
        <li>v002 expects 6 and otherwise performs the same restore.</li>
        <li>The failed v001 preflight changed nothing.</li>
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
<div class="panel"><p class="pass">Done. Reload Team page and confirm the account menu is visible again.</p></div>
<?php elseif ($installError !== ''): ?>
<div class="panel"><div class="error">INSTALL FAILED</div><p><?= h($installError) ?></p></div>
<?php endif; ?>

<?php if (!$installed): ?>
<div class="panel">
    <form method="post">
        <button type="submit" name="install" value="1" <?= $canInstall ? '' : 'disabled' ?>>Install Restore</button>
    </form>
</div>
<?php endif; ?>

</div>
</body>
</html>
