<?php
session_start();

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';

$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}

$isAdmin = isAdmin($_SESSION['userSession'] ?? null);

$adminStatusLine = $isAdmin
    ? '<div class="admin-status admin-yes">You are authorized to view/use this page</div>'
    : '<div class="admin-status admin-no">You are NOT authorized to view/use this page</div>';

if (!$isAdmin) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Not Authorized</title>
        <link rel="stylesheet" href="/mrl-styles.css">
    </head>
    <body><?php echo $adminStatusLine; ?></body>
    </html>
    <?php
    exit;
}

$currentTimeIs = date("n/j/Y g:i a");


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>MRL Admin Setup</title>
<link rel="stylesheet" href="/mrl-styles.css">

<style>

    </style>
</head>
<body>

<?php echo $adminStatusLine; ?>

<?php if($msg): ?>
<div id="flashMsg" class="flash-top notice-success"><?php echo h($msg); ?></div>
<script>
(function(){
    var el = document.getElementById('flashMsg');
    if(!el) return;
    window.setTimeout(function(){
        el.style.transition = "opacity 0.6s ease";
        el.style.opacity = "0";
        window.setTimeout(function(){ el.style.display = "none"; }, 650);
    }, 2200);
})();
</script>
<?php endif; 
// list of active email addresses
$sql = "SELECT * FROM `users` WHERE `userID` > 0 AND `userActive` = 'Y'";
echo "<br><br>";
echo "Active email addresses :<br><br>";
foreach ($dbo->query($sql) as $row) {
    echo "$row[userEmail]  $row[userEmail2]  ";
};

// list of inactive email addresses
$sql = "SELECT * FROM `users` WHERE `userID` > 0 AND `userActive` = 'N'";
echo "<br><br>";
echo "Inactive email addresses :<br><br>";
foreach ($dbo->query($sql) as $row) {
    echo "$row[userEmail]  $row[userEmail2]  ";
}
?>
