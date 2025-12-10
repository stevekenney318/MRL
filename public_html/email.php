<?php
session_start();

// Store the current page URL in the session
$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];

require_once 'class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
}

// determine if logged in user is Admin
require_once 'Admin.php';

date_default_timezone_set('America/New_York');
include 'header.php';
include "config.php"; // Database connection using PDO
include "config_mrl.php"; // setup variables for current MRL season & segment
$currentTimeIs = date("n/j/Y g:i a");

// list of active email addresses
$sql = "SELECT * FROM `users` WHERE `userID` > 0 AND `userActive` = 'Y'";
echo "Active email addresses :<br><br>";
foreach ($dbo->query($sql) as $row) {
    echo "$row[userEmail]  $row[userEmail2]  ";
}
?>