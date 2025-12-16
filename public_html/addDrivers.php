<?php
// Start the session to access session variables
session_start();

// Store the current page URL in the session
$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];

// Import the USER class and create an instance
require_once 'class.user.php';
$user_home = new USER();

// If the user is not logged in, redirect to the login page
if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
}

// Import the header.php file
// include 'header.php';

// Import the config.php and config_mrl.php files
require "config.php";
require "config_mrl.php";

// Set the timezone to America/New_York and get the current time
date_default_timezone_set("America/New_York");
$currentTimeIs = date("n/j/Y g:i a");

// Prepare a mysqli statement to select all columns from the users table where userID matches the userSession variable in the session array
$stmt = mysqli_prepare($dbconnect, "SELECT * FROM users WHERE userID=?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['userSession']);
mysqli_stmt_execute($stmt);
$row = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Check if the database connection was successful
if (!$dbconnect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle the selected year for displaying drivers
if (isset($_POST['showDrivers'])) {
    $selectedYear = $_POST['year'];
    if ($selectedYear == '') {
        echo "<p>No year selected.</p>";
    } else {
        $_SESSION['selectedYear'] = $selectedYear; // Store the selected year in a session variable
    }
}

$selectedYear = $_SESSION['selectedYear'] ?? ''; // Retrieve the selected year from the session

// Variable to store the confirmation message
$confirmationMessage = "";

// Handle adding a driver to a group
if (isset($_POST['addDriver'])) {
    $selectedDriver = $_POST['driverName'];
    $selectedColumn = $_POST['column'];
    $selectedTag = $_POST['tag'];

    if (!empty($selectedYear) && !empty($selectedDriver) && !empty($selectedColumn)) {
        // Use NULL if no tag is selected
        $selectedTag = !empty($selectedTag) ? $selectedTag : null;

        $stmt = mysqli_prepare($dbconnect, "INSERT INTO `{$selectedColumn} Drivers` (driverName, driverYear, Tag) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $selectedDriver, $selectedYear, $selectedTag);
        mysqli_stmt_execute($stmt);

        // Update the confirmation message
        $confirmationMessage = "$selectedDriver added to Group $selectedColumn for year $selectedYear" . ($selectedTag ? " with Tag $selectedTag." : ".");
    } else {
        $confirmationMessage = "Please select all fields.";
    }
}

// Handle adding a new driver to the database
if (isset($_POST['addNewDriver'])) {
    $newDriverName = trim($_POST['newDriverName']);

    // Check if the name is empty
    if (empty($newDriverName)) {
        $confirmationMessage = "Driver name cannot be empty.";
    } else {
        // Check if the driver already exists in the database
        $stmt = mysqli_prepare($dbconnect, "SELECT COUNT(*) FROM drivers WHERE driverName = ?");
        mysqli_stmt_bind_param($stmt, "s", $newDriverName);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_array($result);

        if ($row[0] > 0) {
            // Driver already exists
            $confirmationMessage = "Driver '$newDriverName' already exists in the database. Please use a different name.";
        } else {
            // Insert the new driver into the database
            $stmt = mysqli_prepare($dbconnect, "INSERT INTO drivers (driverName) VALUES (?)");
            mysqli_stmt_bind_param($stmt, "s", $newDriverName);
            mysqli_stmt_execute($stmt);

            // Confirmation message
            $confirmationMessage = "Driver '$newDriverName' has been successfully added to the database.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="mrl-styles.css">
</head>
<body style="font-family: Tahoma, Verdana, Segoe, sans-serif; font-size: 13pt; color: #000000;">

<div style="display: flex; justify-content: center;">
    <form method="post" action="">
        <select name="year" id="year">
            <option value=""> Select Year </option>
            <?php
            // Query to get a list of years from the database
            $stmt = mysqli_prepare($dbconnect, "SELECT year FROM years WHERE year != '0000'");
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            // Loop through the results and create an option element for each year
            while ($row = mysqli_fetch_array($result)) {
                $year = $row['year'];
                $selected = ($selectedYear == $year) ? 'selected' : '';
                echo "<option value=\"$year\" $selected>$year</option>";
            }
            ?>
        </select>
        <input type="submit" name="showDrivers" value="Show Drivers">
    </form>
</div>
<br>

<?php
if ($selectedYear) {
    // Display drivers logic
    $tables = ['A Drivers', 'B Drivers', 'C Drivers', 'D Drivers'];
    $driverData = [];

    foreach ($tables as $table) {
        $stmt = mysqli_prepare($dbconnect, "SELECT driverName, Tag FROM `$table` WHERE driverYear = ?");
        mysqli_stmt_bind_param($stmt, "s", $selectedYear);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $driverData[$table][] = $row;
        }
    }

    // Display logic
    echo "<div style='display: flex; justify-content: center;'>";
    echo "<table style='border-collapse: collapse; text-align: center;'>";
    echo "<tr><th colspan='4' style='background-color:#fabf8f; color:#333333; text-align: center;'>$selectedYear</th></tr>";
    echo "<tr>
            <th style='background-color:#fabf8f; color:#333333;'>Group A</th>
            <th style='background-color:#fabf8f; color:#333333;'>Group B</th>
            <th style='background-color:#fabf8f; color:#333333;'>Group C</th>
            <th style='background-color:#fabf8f; color:#333333;'>Group D</th>
          </tr>";

    $maxRows = max(array_map('count', $driverData));
    for ($i = 0; $i < $maxRows; $i++) {
        echo "<tr>";
        foreach ($tables as $index => $table) {
            $colors = ['#d9d9d9', '#c4bd97', '#b8cce4', '#d8e4bc'];
            $driver = $driverData[$table][$i] ?? ['driverName' => '', 'Tag' => ''];
            $cellContent = ($driver['driverName'] ? $driver['driverName'] . ' ' . $driver['Tag'] : '');

            // Style cells only if there's content
            $cellStyle = $cellContent ? "background-color: {$colors[$index]}; border: 1px solid black; padding: 3px; text-align: left;" : "padding: 3px;";
            echo "<td style='$cellStyle'>$cellContent</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
}
?>



<!-- Additional forms and messages -->
<div style="display: flex; justify-content: center; margin-top: 20px;">
    <form method="post" action="">
        <input type="text" name="newDriverName" id="newDriverName" placeholder="Enter driver name">
        <input type="submit" name="addNewDriver" value="Add New Driver">
    </form>
</div>

<div style="display: flex; justify-content: center; margin-top: 20px;">
    <form method="post" action="">
        <select name="driverName" id="driverName">
            <option value=""> Select Driver </option>
            <?php
            $alreadySelectedDrivers = [];
            if (!empty($selectedYear)) {
                foreach ($tables as $table) {
                    $stmt = mysqli_prepare($dbconnect, "SELECT driverName FROM `$table` WHERE driverYear = ?");
                    mysqli_stmt_bind_param($stmt, "s", $selectedYear);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $alreadySelectedDrivers[] = $row['driverName'];
                    }
                }
            }

            $stmt = mysqli_prepare($dbconnect, "SELECT driverName FROM drivers");
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                if (!in_array($row['driverName'], $alreadySelectedDrivers) && $row['driverName'] !== '-- No Pick Yet --') {
                    echo "<option value=\"{$row['driverName']}\">{$row['driverName']}</option>";
                }
            }
            ?>
        </select>

        <select name="tag" id="tag">
    <option value="">Select Tag</option>
    <?php
    $stmt = mysqli_prepare($dbconnect, "SHOW COLUMNS FROM `A Drivers` LIKE 'Tag'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if (preg_match("/^enum\((.*)\)$/", $row['Type'], $matches)) {
        $enumValues = str_getcsv($matches[1], ',', "'");
        // Map database values to user-friendly labels
        $tagLabels = [
            "(R)" => "Rookie",
            "(P)" => "Part Time"
        ];
        foreach ($enumValues as $value) {
            $label = $tagLabels[$value] ?? $value; // Use label if available, otherwise default to value
            echo "<option value=\"$value\">$label</option>";
        }
    }
    ?>
</select>


        <select name="column" id="column">
            <option value=""> Select Column </option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
        </select>
        <input type="submit" name="addDriver" value="Add Driver">
    </form>
</div>

<!-- Display confirmation message -->
<?php if (!empty($confirmationMessage)): ?>
    <div style="text-align: center; margin-top: 20px;">
        <p style="font-weight: bold; color: red;"><?php echo $confirmationMessage; ?></p>
    </div>
<?php endif; ?>

</body>
</html>
