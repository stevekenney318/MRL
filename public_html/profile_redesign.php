<?php
declare(strict_types=1);

/**
 * profile_redesign.php
 *
 * VERSION: v003
 * LAST MODIFIED: 8/27/2026 9:24:30 pm
 *
 * PURPOSE:
 * Modern MRL profile page with integrated email management and per-user theme selection.
 * Production profile.php remains untouched while this page is tested.
 *
 * CHANGELOG:
 *
 * v003 (8/27/2026 9:24:30 pm)
 * - PROFILE: Integrates Login Email editing directly into the profile page.
 * - PROFILE: Integrates Secondary Email add/change/remove directly into the profile page.
 * - SECURITY: Uses only authenticated $_SESSION['userSession'] for user identity.
 * - SECURITY: Adds CSRF protection to all profile writes.
 * - THEME: Preserves Cars / Starry Night / Dark / Light user preference support.
 * - CLEANUP: Removes dependency on change-login-email.php and change-second-email.php.
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
if ($uid <= 0) {
    $user_home->redirect('logout.php');
    exit;
}

if (!isset($_SESSION['mrl_profile_csrf'])) {
    $_SESSION['mrl_profile_csrf'] = bin2hex(random_bytes(24));
}

function pr_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pr_load_user(USER $user_home, int $uid): array
{
    $stmt = $user_home->runQuery('SELECT userID, userName, userEmail, userEmail2 FROM users WHERE userID=:uid LIMIT 1');
    $stmt->execute([':uid' => $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : [];
}

function pr_valid_email(string $email): bool
{
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

$row = pr_load_user($user_home, $uid);
if (!$row) {
    http_response_code(403);
    exit('User not found.');
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals((string)$_SESSION['mrl_profile_csrf'], $csrf)) {
        $message = 'Nothing was changed: security token mismatch.';
        $messageType = 'error';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'save_primary_email') {
            $newEmail = trim((string)($_POST['primary_email'] ?? ''));
            if (!pr_valid_email($newEmail)) {
                $message = 'Please enter a valid login email address.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $dbo->prepare('UPDATE users SET userEmail=:email WHERE userID=:uid LIMIT 1');
                    $ok = $stmt->execute([':email' => $newEmail, ':uid' => $uid]);
                    $message = $ok ? 'Login email saved.' : 'Login email could not be saved.';
                    $messageType = $ok ? 'success' : 'error';
                } catch (Throwable $e) {
                    $message = 'Login email could not be saved.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'save_secondary_email') {
            $newEmail2 = trim((string)($_POST['secondary_email'] ?? ''));
            if ($newEmail2 !== '' && !pr_valid_email($newEmail2)) {
                $message = 'Please enter a valid secondary email address, or leave it blank to remove it.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $dbo->prepare('UPDATE users SET userEmail2=:email WHERE userID=:uid LIMIT 1');
                    $ok = $stmt->execute([':email' => $newEmail2, ':uid' => $uid]);
                    if ($ok) {
                        $message = $newEmail2 === '' ? 'Secondary email removed.' : 'Secondary email saved.';
                        $messageType = 'success';
                    } else {
                        $message = 'Secondary email could not be saved.';
                        $messageType = 'error';
                    }
                } catch (Throwable $e) {
                    $message = 'Secondary email could not be saved.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'save_theme') {
            $requestedTheme = mrl_theme_normalize((string)($_POST['team_theme'] ?? 'dark'));
            if (mrl_theme_save($dbo, $uid, $requestedTheme)) {
                $message = 'Theme preference saved.';
                $messageType = 'success';
            } else {
                $message = 'Theme preference could not be saved.';
                $messageType = 'error';
            }
        }
    }
    $row = pr_load_user($user_home, $uid);
}

$nameParts = explode(' ', trim((string)($row['userName'] ?? '')));
$firstName = $nameParts[0] ?? '';
$email1 = (string)($row['userEmail'] ?? '');
$email2 = (string)($row['userEmail2'] ?? '');
$theme = mrl_theme_get($dbo, $uid);
$options = mrl_theme_options();
?><!DOCTYPE html>
<html class="mrl-theme-<?php echo pr_h($theme); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo pr_h($firstName); ?>'s Profile Page</title>
<style>
*{box-sizing:border-box}
:root{--gold:#f1c97f;--text:#f2f2f2;--blue:#43b7f0;--panel:rgba(28,28,28,.52);--panel-head:rgba(34,34,34,.46);--border:rgba(195,195,195,.34);--field:rgba(10,10,10,.55);--width:85%;--max:1600px}
html{min-height:100%;background:#151515}
html.mrl-theme-cars{background:linear-gradient(rgba(10,20,15,.70),rgba(10,20,15,.70)),url("/images/cars.jpg") center/cover no-repeat fixed!important}
html.mrl-theme-starry-night{background:linear-gradient(rgba(5,8,18,.60),rgba(5,8,18,.60)),url("/images/starry_night.jpg") center/cover no-repeat fixed!important}
html.mrl-theme-dark{background:#151515!important}
html.mrl-theme-light{--gold:#8b5b00;--text:#202020;--blue:#006eaa;--panel:rgba(255,255,255,.90);--panel-head:rgba(244,244,244,.96);--border:rgba(60,60,60,.28);--field:#fff;background:#eceff1!important}
body{margin:0;min-height:100%;background:transparent;color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.shell{width:var(--width);max-width:var(--max);margin-left:auto;margin-right:auto}
.header{position:sticky;top:8px;z-index:20;margin-top:8px;padding:10px 16px;border:1px solid rgba(67,142,94,.72);border-radius:14px;background:linear-gradient(180deg,rgba(18,58,40,.82),rgba(20,35,29,.78));display:grid;grid-template-columns:1fr 2fr 1fr;align-items:center;box-shadow:0 10px 28px rgba(0,0,0,.25)}
.header a{color:#fff;text-decoration:none}.title{text-align:center;color:#fff5e2;font-size:20px;font-weight:800}.right{text-align:right}
.card{margin-top:18px;border:1px solid var(--border);border-radius:14px;background:var(--panel);overflow:hidden;backdrop-filter:blur(2px)}
.card h2{margin:0;padding:13px 18px;background:var(--panel-head);border-bottom:1px solid rgba(255,255,255,.09);color:var(--gold);font-size:18px}
.body{padding:18px}.profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field-card{border:1px solid var(--border);border-radius:10px;padding:14px;background:rgba(0,0,0,.10)}
.field-card label{display:block;margin-bottom:7px;color:var(--gold);font-weight:800}.field-row{display:flex;gap:10px;align-items:center}.field-row input[type=email]{flex:1;min-width:0;padding:10px 11px;border:1px solid var(--border);border-radius:7px;background:var(--field);color:var(--text);font:15px Tahoma,Verdana,Segoe UI,sans-serif}
.field-help{margin-top:7px;color:var(--text);opacity:.78;font-size:12px}.theme-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.theme-option{display:block;border:1px solid var(--border);border-radius:10px;padding:12px;background:rgba(0,0,0,.12);cursor:pointer}.theme-option input{margin-right:7px}
button{border:1px solid #5a7fb5;border-radius:9px;background:#1466c9;color:#fff;padding:9px 16px;font-weight:800;cursor:pointer;white-space:nowrap}.message{margin-top:16px;padding:11px 13px;border:1px solid var(--border);border-radius:9px}.message.success{color:#78e69c}.message.error{color:#ff8b8b}.message.info{color:var(--gold)}
footer{padding:24px 0 30px;color:var(--gold);font-size:13px}
html.mrl-theme-light .header,html.mrl-theme-light .header *{color:#fff7e6!important}html.mrl-theme-light .header a{color:#fff!important}html.mrl-theme-light .field-card,html.mrl-theme-light .theme-option{background:rgba(255,255,255,.68)!important;color:#202020!important}html.mrl-theme-light .field-row input[type=email]{color:#202020!important;background:#fff!important}html.mrl-theme-light .message.success{color:#176b34!important}html.mrl-theme-light .message.error{color:#a00000!important}html.mrl-theme-light .message.info{color:#6f4a00!important}
@media(max-width:900px){:root{--width:94%}.header{grid-template-columns:1fr auto}.title{grid-column:1/-1;grid-row:1;text-align:left}.theme-grid{grid-template-columns:1fr 1fr}.profile-grid{grid-template-columns:1fr}}
@media(max-width:560px){.theme-grid{grid-template-columns:1fr}.field-row{align-items:stretch;flex-direction:column}.field-row button{width:100%}}
</style>
</head>
<body>
<header class="shell header"><div><a href="/team_redesign.php">← Team Page</a></div><div class="title">Manlius Racing League <div style="font-size:12px;color:#f1c97f">My Profile Page · redesign test</div></div><div class="right"><a href="/logout.php">Logout</a></div></header>
<main class="shell">
<div style="margin:16px 2px;color:var(--gold);font-size:18px">Hi <?php echo pr_h($firstName); ?> ...</div>
<?php if ($message !== ''): ?><div class="message <?php echo pr_h($messageType); ?>"><?php echo pr_h($message); ?></div><?php endif; ?>
<section class="card"><h2>Profile</h2><div class="body"><div class="profile-grid">
<form class="field-card" method="post"><input type="hidden" name="csrf" value="<?php echo pr_h((string)$_SESSION['mrl_profile_csrf']); ?>"><input type="hidden" name="action" value="save_primary_email"><label for="primary_email">Login Email</label><div class="field-row"><input type="email" id="primary_email" name="primary_email" maxlength="100" required value="<?php echo pr_h($email1); ?>"><button type="submit">Save</button></div><div class="field-help">The email address used for your MRL login/account.</div></form>
<form class="field-card" method="post"><input type="hidden" name="csrf" value="<?php echo pr_h((string)$_SESSION['mrl_profile_csrf']); ?>"><input type="hidden" name="action" value="save_secondary_email"><label for="secondary_email">Secondary Email</label><div class="field-row"><input type="email" id="secondary_email" name="secondary_email" maxlength="100" value="<?php echo pr_h($email2); ?>" placeholder="Optional"><button type="submit">Save</button></div><div class="field-help">Optional. Leave blank and Save to remove it.</div></form>
</div></div></section>
<section class="card"><h2>Page Theme</h2><div class="body"><p>Choose the theme used by your MRL team/profile pages.</p><form method="post"><input type="hidden" name="csrf" value="<?php echo pr_h((string)$_SESSION['mrl_profile_csrf']); ?>"><input type="hidden" name="action" value="save_theme"><div class="theme-grid"><?php foreach ($options as $value => $label): ?><label class="theme-option"><input type="radio" name="team_theme" value="<?php echo pr_h($value); ?>" <?php echo $theme === $value ? 'checked' : ''; ?>><?php echo pr_h($label); ?></label><?php endforeach; ?></div><button type="submit" style="margin-top:14px">Save Theme</button></form></div></section>
</main><footer class="shell">Copyright © 2017-<?php echo date('Y'); ?> Manlius Racing League</footer>
</body></html>
