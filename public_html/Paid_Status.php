<?php
// Paid_Status.php

// Start the session and include necessary files
session_start();
// Store the current page URL in the session
$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
// Include necessary files after session start
require_once 'class.user.php';
require 'config.php';
require 'config_mrl.php';

// Create a new USER object
$user_home = new USER();

// Redirect to login if not logged in
if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
}

// Include header for MRL styling, etc.
include 'header.php';

// Check if the user is an admin
$isAdmin = isAdmin($_SESSION['userSession']);

// Display admin status
if ($isAdmin) {
    echo '<div style="color: green;">You are authorized to view/use this page</div>';
    echo "<script>console.log('Validated admin');</script>";
} else {
    echo '<div style="color: red;">You are NOT authorized to view/use this page</div>';
    echo "<script>console.log('Validated non-admin');</script>";
    die();
}

// ***** Set date EST/EDT for webpage and database ************
// ************************************************************
// Get the current time in America/New_York timezone
$nyTime = time();
$nyZone = new DateTimeZone("America/New_York");
$nyOffset = $nyZone->getOffset(new DateTime("@$nyTime")) / 3600;
echo "<div>America/New_York offset: $nyOffset</div>"; // Display America/New_York offset with line break

// Get the current time in UTC timezone
$utcTime = time();
$utcZone = new DateTimeZone("UTC");
$utcOffset = $utcZone->getOffset(new DateTime("@$utcTime")) / 3600;
// echo "<div>UTC offset: $utcOffset</div>"; // Display UTC offset with line break

// Calculate the difference in hours between UTC and America/New_York
$offset = $nyOffset - $utcOffset;
// echo "<div>Calculated offset: $offset</div>"; // Display calculated offset with line break

// Set the timezone for the current session to America/New_York with the calculated offset
$sql = "SET time_zone = '$offset:00';";
if (mysqli_query($dbconnect, $sql)) {
    // Get the current time from the database
    $query = "SELECT NOW()";
    $result = mysqli_query($dbconnect, $query);
    if ($result) {
        $row = mysqli_fetch_row($result);
        $currentTimeFromDB = $row[0];
        
        echo "<div>Timezone set successfully to $currentTimeFromDB (America/New_York)</div>"; // Display timezone set message with line break
    } else {
        echo "<div>Error retrieving current time: " . mysqli_error($dbconnect) . "</div>"; // Display error message with line break
    }
} else {
    echo "<div>Error setting timezone: " . mysqli_error($dbconnect) . "</div>"; // Display error message with line break
}
// ************************************************************


?>

<!DOCTYPE html>
<html>
<head>
    <title>MRL Paid Status</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="mrl-styles.css">
    <style>
        body {
            padding-top: 0px;
        }
    </style>
</head>
<body>

<?php
// Paid Status

$sql = "SELECT * FROM `Financial` WHERE `raceYear` = '$raceYear' AND `userActive` = 'Y' AND `userID`!= 0 ORDER BY `raceYear` DESC";

echo "<style type='text/css'>table, th, td {border: 1px solid black;border-collapse: collapse;padding: 3px;}</style>";

echo "<table align=center>";
echo "<tr style='background-color:#fabf8f; color:#222222;'>";
echo "<th colspan=6>$raceYear Paid Status</th>";

echo "<tr style='background-color:#fabf8f; color:#222222;'>";
echo "<th>Team</th><th>Owner</th><th>Status</th><th>Amount</th><th>How</th><th>Comments</th></tr>";

foreach ($dbo->query($sql) as $row) {
    echo "<tr>";
    echo "<td style='background-color:#b7dee8; color:#222222; font-size: 13pt; line-height: 140%; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;'>" . $row[teamName] . "</td>";
    echo "<td style='background-color:#b7dee8; color:#222222; font-size: 13pt; line-height: 140%; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;'>" . $row[userName] . "</td>";
    echo "<td style='background-color:#b7dee8; color:#222222; font-size: 13pt; line-height: 140%; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;'>" . $row[paidStatus] . "</td>";
    echo "<td style='background-color:#b7dee8; color:#222222; font-size: 13pt; line-height: 140%; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;'>" . "$" . $row[paidAmount] . "</td>";
    echo "<td style='background-color:#b7dee8; color:#222222; font-size: 13pt; line-height: 140%; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;'>" . $row[paidHow] . "</td>";
    echo "<td style='background-color:#b7dee8; color:#222222; font-size: 13pt; line-height: 140%; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;'>" . $row[paidComment] . "</td>";
    echo "</tr>";
}
echo "</table>";

// SQL to get total amount for the year
$sql = "SELECT  SUM(paidAmount) AS Total FROM Financial WHERE `raceYear` = $raceYear";

echo "<table align=center>";
echo "<tr style='background-color:#fabf8f; color:#222222; font-size: 13pt; line-height: 140%; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;'>";
echo "<th colspan=1>$raceYear Total</th>";

foreach ($dbo->query($sql) as $row) {
    echo "<tr><td style='background-color:#d8e4bc; color:#222222; font-size: 13pt; line-height: 140%; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;'>" . "$" . $row[Total] . "</td></tr>";
}
echo "</table>";

?>
</body>
</html>
