<?php
declare(strict_types=1);

/**
 * profile_redesign.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/27/2026 6:33:12 pm
 *
 * Modern profile test page with per-user MRL theme selection.
 * Production profile.php is untouched.
 */

session_start();

require_once 'class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}

date_default_timezone_set('America/New_York');
require 'config.php';
require 'config_mrl.php';
require_once __DIR__ . '/mrl_team/mrl_theme_helper.php';

$uid = (int)($_SESSION['userSession'] ?? 0);

$stmt = $user_home->runQuery("SELECT * FROM users WHERE userID=:uid");
$stmt->execute([':uid' => $uid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$nameParts = explode(' ', trim((string)($row['userName'] ?? '')));
$firstName = $nameParts[0] ?? '';
$email1 = (string)($row['userEmail'] ?? '');
$email2 = (string)($row['userEmail2'] ?? '');

if (!isset($_SESSION['mrl_profile_csrf'])) {
    $_SESSION['mrl_profile_csrf'] = bin2hex(random_bytes(24));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals((string)$_SESSION['mrl_profile_csrf'], $csrf)) {
        $message = 'Theme was not saved: security token mismatch.';
    } else {
        $requestedTheme = mrl_theme_normalize((string)($_POST['team_theme'] ?? 'dark'));
        $message = mrl_theme_save($dbo, $uid, $requestedTheme)
            ? 'Theme preference saved.'
            : 'Theme preference could not be saved.';
    }
}

$theme = mrl_theme_get($dbo, $uid);
$options = mrl_theme_options();

function pr_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?><!DOCTYPE html>
<html class="mrl-theme-<?php echo pr_h($theme); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo pr_h($firstName); ?>'s Profile Page</title>
<style>
*{box-sizing:border-box}
:root{
    --gold:#f1c97f;--text:#f2f2f2;--blue:#43b7f0;
    --panel:rgba(28,28,28,.52);--panel-head:rgba(34,34,34,.46);
    --border:rgba(195,195,195,.34);--width:85%;--max:1600px
}
html{min-height:100%;background:#151515}
html.mrl-theme-cars{
    background:linear-gradient(rgba(10,20,15,.70),rgba(10,20,15,.70)),url("/images/cars.jpg") center/cover no-repeat fixed!important
}
html.mrl-theme-starry-night{
    background:linear-gradient(rgba(5,8,18,.60),rgba(5,8,18,.60)),url("/images/starry_night.jpg") center/cover no-repeat fixed!important
}
html.mrl-theme-dark{background:#151515!important}
html.mrl-theme-light{
    --gold:#8b5b00;--text:#202020;--blue:#006eaa;--panel:rgba(255,255,255,.90);
    --panel-head:rgba(244,244,244,.96);--border:rgba(60,60,60,.28);
    background:#eceff1!important
}
body{margin:0;min-height:100%;background:transparent;color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.shell{width:var(--width);max-width:var(--max);margin-left:auto;margin-right:auto}
.header{position:sticky;top:8px;z-index:20;margin-top:8px;padding:10px 16px;border:1px solid rgba(67,142,94,.72);border-radius:14px;background:linear-gradient(180deg,rgba(18,58,40,.82),rgba(20,35,29,.78));display:grid;grid-template-columns:1fr 2fr 1fr;align-items:center;box-shadow:0 10px 28px rgba(0,0,0,.25)}
.header a{color:#fff;text-decoration:none}.title{text-align:center;color:#fff5e2;font-size:20px;font-weight:800}.right{text-align:right}
.card{margin-top:18px;border:1px solid var(--border);border-radius:14px;background:var(--panel);overflow:hidden;backdrop-filter:blur(2px)}
.card h2{margin:0;padding:13px 18px;background:var(--panel-head);border-bottom:1px solid rgba(255,255,255,.09);color:var(--gold);font-size:18px}
.body{padding:18px}.body p{line-height:1.5}.body a{color:var(--blue)}
.theme-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.theme-option{display:block;border:1px solid var(--border);border-radius:10px;padding:12px;background:rgba(0,0,0,.12);cursor:pointer}
.theme-option input{margin-right:7px}
button{margin-top:14px;border:1px solid #5a7fb5;border-radius:9px;background:#1466c9;color:#fff;padding:9px 16px;font-weight:800;cursor:pointer}
.message{margin-top:12px;padding:10px 12px;border:1px solid var(--border);border-radius:9px;color:var(--gold)}
footer{padding:24px 0 30px;color:var(--gold);font-size:13px}
@media(max-width:900px){:root{--width:94%}.header{grid-template-columns:1fr auto}.title{grid-column:1/-1;grid-row:1;text-align:left}.theme-grid{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.theme-grid{grid-template-columns:1fr}}

html.mrl-theme-light .header,html.mrl-theme-light .header *{color:#fff7e6!important}
html.mrl-theme-light .header a{color:#fff!important}
html.mrl-theme-light .theme-option{background:rgba(255,255,255,.68)!important;color:#202020!important}
html.mrl-theme-light .message{color:#6f4a00!important}
</style>
</head>
<body>
<header class="shell header">
    <div><a href="/team_redesign.php">← Team Page</a></div>
    <div class="title">Manlius Racing League <div style="font-size:12px;color:#f1c97f">My Profile Page · redesign test</div></div>
    <div class="right"><a href="/logout.php">Logout</a></div>
</header>

<main class="shell">
    <div style="margin:16px 2px;color:var(--gold);font-size:18px">Hi <?php echo pr_h($firstName); ?> ...</div>

    <section class="card">
        <h2>Profile</h2>
        <div class="body">
            <p><a href="/change-login-email.php">Change Login Email</a><br><?php echo pr_h($email1); ?></p>
            <p><a href="/change-second-email.php">Change or Add a Secondary Email</a><br><?php echo pr_h($email2); ?></p>
        </div>
    </section>

    <section class="card">
        <h2>Page Theme</h2>
        <div class="body">
            <p>Choose the theme used by your MRL team/profile pages.</p>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo pr_h((string)$_SESSION['mrl_profile_csrf']); ?>">
                <div class="theme-grid">
                    <?php foreach ($options as $value => $label): ?>
                    <label class="theme-option">
                        <input type="radio" name="team_theme" value="<?php echo pr_h($value); ?>" <?php echo $theme === $value ? 'checked' : ''; ?>>
                        <?php echo pr_h($label); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit">Save Theme</button>
            </form>
            <?php if ($message !== ''): ?><div class="message"><?php echo pr_h($message); ?></div><?php endif; ?>
        </div>
    </section>
</main>

<footer class="shell">Copyright © 2017-<?php echo date('Y'); ?> Manlius Racing League</footer>
</body>
</html>
