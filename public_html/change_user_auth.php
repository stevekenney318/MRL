<?php
// include 'header.php';
session_start(); // ready to go!

require_once 'class.user.php';
$user_home = new USER();
if(!$user_home->is_logged_in())
{
	$user_home->redirect('login.php');
}

require "config.php"; // setup variables for database connection 
require "config_mrl.php"; // setup variables for current MRL season & segment
date_default_timezone_set("America/New_York");
$currentTimeIs = date("n/j/Y g:i a"); //get date in format '8/25/2020 12:20 am'

// use $dbconnect to connect to database
require_once 'config.php';
$mysqli = mysqli_connect($host_name,$username,$password,$database);
if (!$mysqli) {
  die("Connection failed: " . mysqli_connect_error());
}

////////////IMPORTANT//////////////////
// determine if logged in user is Admin
require_once 'Admin.php';
///////////////////////////////////////

// fetch user names from database
$result = mysqli_query($mysqli,"SELECT userName, changeAuth FROM users WHERE userActive='Y'");

$usernames = array();
while($row = mysqli_fetch_array($result)) {
  $usernames[$row['userName']] = $row['changeAuth'];
}

// handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $selected_user = $_POST['user'];
  
  // toggle changeAuth in database
  $stmt = $user_home->runQuery("UPDATE users SET changeAuth = IF(changeAuth='N', 'Y', 'N') WHERE userName=:user");
  $stmt->bindParam(':user', $selected_user);
  $stmt->execute();

  // update value in $usernames array
  $usernames[$selected_user] = ($usernames[$selected_user] == 'Y') ? 'N' : 'Y';

  // create success message
  $message = "$selected_user's authorization was changed to {$usernames[$selected_user]}";
}
?>

<html>
<head>
<title>MRL Admin</title>
</head>
<body>

<p>This is the page to use to change a users access to be able to make late picks or change a driver.</b>


<form method="post">
  <label for="user">Select a user:</label>
  <select name="user" id="user" onchange="document.getElementById('message').innerHTML=''">
    <option value="">Select Name</option>
    <?php foreach ($usernames as $name => $auth) { ?>
      <option value="<?php echo $name; ?>"><?php echo $name . ' (' . $auth . ')'; ?></option>
    <?php } ?>
  </select>
  <br><br>
  <input type="submit" value="Toggle Authorization">
</form>

<div id="message"><?php echo $message; ?></div>

</body>
</html>

<?php
include 'report_all_users_auth.php';
?>
