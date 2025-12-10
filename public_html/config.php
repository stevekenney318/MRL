<?php
// Set the database hostname, name, username, and password
$host_name = "localhost";  // your host name
$database = "u809830586_MRL_DB";  // your database name
$username = "u809830586_MRL_DB";  // your user name
$password = "7neGYdSZkFpR";  // your password

/////////////////////////////////////////////////////////////////////////////
//////// Do not edit below here or you risk accessing your database /////////
/////////////////////////////////////////////////////////////////////////////

// Method 1: Connect to the database using mysqli
$dbconnect = mysqli_connect($host_name, $username, $password, $database);

// Check if the connection was successful, and if not, output an error message and stop the script
if ($dbconnect->connect_error) {
    die("Connection failed: " . $dbconnect->connect_error);
}

// Method 2: Create a new PDO object to connect to the database using the same credentials
try {
    $dbo = new PDO('mysql:host=' . $host_name . ';dbname=' . $database, $username, $password);
} catch (PDOException $e) {
    // If an error occurs during the creation of the PDO object, output an error message and stop the script
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>