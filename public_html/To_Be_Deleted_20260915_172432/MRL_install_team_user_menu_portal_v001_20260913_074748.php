<?php
declare(strict_types=1);

/**
 * MRL_install_team_user_menu_portal_v001.php
 *
 * PURPOSE
 * -------
 * Fix the Team page account dropdown layering by moving the open menu out of
 * the sticky-header/backdrop-filter compositing hierarchy and temporarily
 * rendering it at document-body level.
 *
 * INSTALLS
 * --------
 * - team.php v047 -> v048
 * - Keeps the existing Steve button and menu contents.
 * - On open, portals the menu to <body> and positions it directly below the button.
 * - On close, returns the menu to its original DOM location.
 * - Repositions while scrolling/resizing.
 * - No DB, picks, scoring, scheduler, theme, or menu-content changes.
 *
 * SAFETY
 * ------
 * - Exact v047 preflight signatures.
 * - Timestamped backup.
 * - Atomic write.
 * - Post-write verification.
 * - Automatic rollback on failure.
 */

date_default_timezone_set('America/New_York');

const INSTALLER_VERSION = 'v001';

$root = __DIR__;
$teamFile = $root . '/team.php';
$generated = '9/13/2026 7:47:48 am ET';
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
        ['* VERSION: v047', 1, 'v047 version header'],
        ['* LAST MODIFIED: 9/13/2026 7:36:42 am', 1, 'v047 last-modified header'],
        ['        .mrl-rd-user{position:relative;justify-self:start;z-index:5001}', 1, 'v047 user wrapper CSS'],
        ["        .mrl-rd-user-menu{\n            display:none;\n            position:absolute;\n            z-index:5002;", 1, 'v047 dropdown CSS'],
        ["    var user = document.getElementById('mrl-rd-user');\n    var button = document.getElementById('mrl-rd-user-button');", 1, 'user-menu JS variables'],
        ["        button.addEventListener('click', function (event) {\n            event.stopPropagation();\n            var open = user.classList.toggle('open');\n            button.setAttribute('aria-expanded', open ? 'true' : 'false');\n        });", 1, 'v047 user-menu click handler'],
        ["        document.addEventListener('click', function () {\n            user.classList.remove('open');\n            button.setAttribute('aria-expanded', 'false');\n        });", 1, 'v047 outside-click handler'],
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
        empty($errors) ? 'v047 CSS + menu-JS signatures found' : implode('; ', $errors)
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
                '* VERSION: v047',
                '* VERSION: v048',
                1,
                'Version header'
            );

            $newSource = replace_exact(
                $newSource,
                '* LAST MODIFIED: 9/13/2026 7:36:42 am',
                '* LAST MODIFIED: 9/13/2026 7:47:48 am',
                1,
                'Last modified'
            );

            $newSource = replace_exact(
                $newSource,
                " * CHANGELOG:\n *\n * v047 (9/13/2026 7:36:42 am)",
                " * CHANGELOG:\n"
                . " *\n"
                . " * v048 (9/13/2026 7:47:48 am)\n"
                . " * - FIX: Account dropdown now portals to document body while open, escaping sticky/backdrop-filter stacking contexts.\n"
                . " * - UI: Dropdown remains anchored directly below the account button and follows scroll/resize while open.\n"
                . " * - PRESERVE: Menu contents, layout, themes, picks, scoring, scheduler, and DB behavior unchanged.\n"
                . " *\n"
                . " * v047 (9/13/2026 7:36:42 am)",
                1,
                'Changelog insertion'
            );

            $newSource = replace_exact(
                $newSource,
                "        .mrl-rd-user.open .mrl-rd-user-menu{display:block}",
                "        .mrl-rd-user.open .mrl-rd-user-menu{display:block}\n"
                . "        .mrl-rd-user-menu.mrl-rd-user-menu-portal{\n"
                . "            display:block!important;\n"
                . "            position:fixed!important;\n"
                . "            z-index:2147483000!important;\n"
                . "            margin:0!important;\n"
                . "        }",
                1,
                'Portal CSS'
            );

            $newSource = replace_exact(
                $newSource,
                "    var user = document.getElementById('mrl-rd-user');\n    var button = document.getElementById('mrl-rd-user-button');",
                "    var user = document.getElementById('mrl-rd-user');\n"
                . "    var button = document.getElementById('mrl-rd-user-button');\n"
                . "    var menu = document.getElementById('mrl-rd-user-menu');\n"
                . "    var menuPlaceholder = null;",
                1,
                'Menu JS variables'
            );

            $oldBlock = <<<'OLDJS'
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
OLDJS;

            $newBlock = <<<'NEWJS'
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
NEWJS;

            $newSource = replace_exact(
                $newSource,
                $oldBlock,
                $newBlock,
                1,
                'User-menu portal JS'
            );

            if (
                strpos($newSource, '* VERSION: v048') === false ||
                strpos($newSource, 'mrl-rd-user-menu-portal') === false ||
                strpos($newSource, 'document.body.appendChild(menu);') === false ||
                strpos($newSource, 'positionUserMenuPortal') === false
            ) {
                throw new RuntimeException('Generated source verification failed before write.');
            }

            $backupPath = backup_file($teamFile, $backupStamp);

            if (!atomic_write($teamFile, $newSource)) {
                throw new RuntimeException('Could not write team.php.');
            }

            $verify = (string)@file_get_contents($teamFile);

            if (
                strpos($verify, '* VERSION: v048') === false ||
                strpos($verify, 'mrl-rd-user-menu-portal') === false ||
                strpos($verify, 'document.body.appendChild(menu);') === false ||
                strpos($verify, 'window.addEventListener(\'scroll\', positionUserMenuPortal, true);') === false
            ) {
                throw new RuntimeException('Post-write verification failed.');
            }

            $installMessages[] = 'team.php v047 -> v048';
            $installMessages[] = 'Open account menu now renders at body level above Team-page stacking contexts.';
            $installMessages[] = 'Menu remains anchored below the account button and follows scroll/resize.';
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
<title>MRL Team User Menu Portal v001</title>
<style>
:root{
    color-scheme:dark;
    --bg:#121212; --panel:#1d1d1d; --border:#454545; --text:#f2f2f2;
    --gold:#ffc866; --green:#5cf0a7; --red:#ff7474;
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
h1,h2{color:var(--gold);margin:0 0 8px}
h1{font-size:25px} h2{font-size:20px}
p{margin:6px 0}
ul{margin:8px 0 0 22px;padding:0}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{text-align:left;padding:10px;border-bottom:1px solid #3b3b3b;vertical-align:top}
th{color:var(--gold)}
.pass{color:var(--green);font-weight:700}
.fail,.error{color:var(--red);font-weight:700}
code{background:#111;border-radius:5px;padding:1px 5px;color:#fff}
button{
    border:1px solid #a97600;background:#5d4300;color:#ffd27a;font-weight:700;
    font-size:16px;padding:11px 16px;border-radius:10px;cursor:pointer
}
button:disabled{opacity:.45;cursor:not-allowed}
.complete{color:var(--green);font-size:20px;font-weight:700}
</style>
</head>
<body>
<div class="wrap">

<div class="panel">
    <h1>MRL Team User Menu Portal v001</h1>
    <p>Generated <?= h($generated) ?> · One-file stacking-context fix · No DB writes</p>
</div>

<div class="panel">
    <h2>What this installs</h2>
    <ul>
        <li>Updates <code>team.php</code> v047 → v048.</li>
        <li>Moves the open account dropdown to document-body level so Team-page panels cannot paint over it.</li>
        <li>Keeps the dropdown visually anchored beneath the account button.</li>
        <li>Returns the menu to its original DOM location when closed.</li>
        <li>No menu-content, theme, pick/scoring, scheduler, or database changes.</li>
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
    <p class="pass">Done. Reload Team page and test the account menu over the open Admin Menu.</p>
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
