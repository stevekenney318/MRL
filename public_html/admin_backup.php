<?php
declare(strict_types=1);

/*
    filename: admin_backup.php
    VERSION: v001
    LAST MODIFIED: 9/3/2026 1:11:25 pm

    PURPOSE:
    - Unified MRL Backup Manager front door.
    - Database backup/restore uses admin_backup_db_helper.php.
    - Files backup uses admin_backup_files_helper.php.
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';

$user_home = new USER();
if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}
if (!isAdmin($_SESSION['userSession'] ?? null)) {
    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Not Authorized</title></head><body><h1>Not Authorized</h1></body></html>';
    exit;
}

define('MRL_BACKUP_MANAGER_ENTRY', true);

$section = strtolower(trim((string)($_GET['section'] ?? '')));
if ($section === 'db') {
    require __DIR__ . '/admin_backup_db_helper.php';
    exit;
}
if ($section === 'files') {
    require __DIR__ . '/admin_backup_files_helper.php';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRL Backup Manager</title>
<style>
body{margin:0;background:#111;color:#eee;font-family:Arial,Helvetica,sans-serif}.wrap{max-width:1100px;margin:24px auto;padding:0 14px}.card{background:#1b1b1b;border:1px solid #343434;border-radius:12px;padding:22px;margin-bottom:18px;box-shadow:0 8px 24px rgba(0,0,0,.35)}h1{font-size:36px;color:#d8c08a;margin:0 0 8px}.sub{color:#c9bfa9;margin:0}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px}.section-title{font-size:24px;font-weight:700;color:#ffd18a;margin-bottom:10px}.desc{color:#cfcfcf;line-height:1.5;min-height:70px}.btn{display:inline-block;padding:13px 18px;border-radius:8px;text-decoration:none;font-weight:700;margin-top:10px}.safe{background:#237a45;color:#fff}.blue{background:#2f6feb;color:#fff}.note{font-size:13px;color:#aaa;margin-top:12px;line-height:1.5}.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>MRL Backup Manager</h1>
    <p class="sub">Database and MRL website-file backups in one place.</p>
  </div>
  <div class="grid">
    <div class="card">
      <div class="section-title">Database Backup + Restore</div>
      <div class="desc">Uses the existing proven DB backup/restore engine and keeps the current <span class="mono">/db_backups/</span> storage structure.</div>
      <a class="btn safe" href="admin_backup.php?section=db">Open Database Backup</a>
    </div>
    <div class="card">
      <div class="section-title">MRL Files Backup</div>
      <div class="desc">Creates a ZIP of <span class="mono">/public_html/</span> while excluding the three WordPress folders: <span class="mono">wp-admin</span>, <span class="mono">wp-includes</span>, and <span class="mono">wp-content</span>.</div>
      <a class="btn safe" href="admin_backup.php?section=files">Open Files Backup</a>
    </div>
  </div>
  <div class="card note">Helper files are intentionally not meant to be opened directly. If you do, they will point you back here and exit safely.</div>
</div>
</body>
</html>
