<?php
session_start();

// Store the current page URL in the session
$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];

require_once 'class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
}

date_default_timezone_set('America/New_York');
require "config.php";
require "config_mrl.php";
$currentTimeIs = date("n/j/Y g:i a");

$stmt = $user_home->runQuery("SELECT * FROM users WHERE userID=:uid");
$stmt->execute(array(":uid" => $_SESSION['userSession']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$name_parts = explode(' ', $row['userName']);
$first_name = $name_parts[0];

// Check if the user is an admin - used when offline
$isAdmin = isAdmin($_SESSION['userSession']);

// used for team page maintenance mode
// Display admin status

// if ($isAdmin) {
//     echo '<div style="color: red; text-align: center; font-size:18.0pt;">Team page is currently in maintenance mode</div>'; // current status note
//     echo "<br>"; // line break
//     echo '<div style="color: red; text-align: center; font-size:20.0pt;">-- ONLY AVAILABLE TO ADMIN USERS --</div>'; // additional message for admins
//     echo "<br>"; // line break
//     echo "<script>console.log('Validated admin');</script>";
// } else {
//     echo "<br>"; // line break
//     echo "<script>console.log('Validated non-admin');</script>";
//     include 'maintenance.php'; // currently in maintenance mode for non-admins
//     die(); // STOP for non-admins
// }

?>

<!DOCTYPE html>
<html class="no-js">

<head>
    <title><?php echo $first_name; ?>'s Team Page </title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet" media="screen">
    <link href="assets/styles.css" rel="stylesheet" media="screen">
    <style>
        body {
            background-color: transparent;
            background-color: #222222;
            padding-top: 60px;
        }
    </style>
</head>

<body>

    <div class="navbar navbar-fixed-top">
        <div class="navbar-inner">
            <div class="container-fluid">
                <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </a>
                <ul class="nav pull-left">
                    <li class="dropdown">
                        <a href="#" role="button" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="icon-user"></i>
                            <?php echo $first_name; ?> <i class="caret"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a tabindex="-1" href="<?php echo $mrl; ?>">MRL Home</a>
                                <a tabindex="-2" href="<?php echo $mrl; ?>profile.php">Profile Page</a>
                                <a tabindex="-3" href="<?php echo $mrl; ?>logout.php">Logout</a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <a class="brand">
                    <ol align='center'><?php echo $sitename; ?> - My Team Page
                </a>
                <iframe src="https://freesecure.timeanddate.com/clock/i7eqrnfz/n777/fn16/fs18/bas/bat0/pd2/tt0/tw1/tm2" frameborder="1px" width="330" height="28"></iframe>
            </div>
        </div>
    </div>

    <div style="width:80%; margin:0 auto; text-align: left;">
        <div style="color: #dfcca8; font-size:16.0pt; line-height:120%; font-family:'Century Gothic',sans-serif;">
            Hi <?php echo $first_name; ?> ... <br>

            <?php
            $userID = $_SESSION['userSession'];
            if (isAdmin($userID)) {
                echo "<br>";
                echo "*********************** Admin Menu ****************************";
                echo "<br>";
                echo "*******************************************************************";
                echo "<br>";
                echo "<a href='https://manliusracingleague.com/Paid_Status.php' target='_blank'>- See Paid Status for current year</a>";
                echo "<br>";
                echo "<a href='https://manliusracingleague.com/email.php' target='_blank'>- List all active players email addresses</a>";
                echo "<br>";
                echo "<a href='https://manliusracingleague.com/report_all_users_auth.php' target='_blank'>- View user auth status to make late picks or change driver</a>";
                echo "<br>";
                echo "<a href='https://manliusracingleague.com/change_user_auth.php' target='_blank'>- Toggle user status to make late picks or change driver</a>";
                echo "<br>";
                echo "<a href='https://manliusracingleague.com/addDrivers.php' target='_blank'>- Add drivers for a new year.</a>";
                echo "<br>";
                echo "<a href='https://manliusracingleague.com/current_segment_chart_by_entry_time.php' target='_blank'>- Show current segment team chart sorted by Entry Time.</a>";
                echo "<br>";
                echo "*******************************************************************";
                echo "<br>";
                echo "<a href='https://auth-db1928.hstgr.io/index.php?db=u809830586_MRL_DB' target='_blank'>- MRL database - (MySql) phpMyAdmin on Hostinger</a>";
                echo "<br>";
                echo "<a href='https://manliusracingleague.com/wp-admin/' target='_blank'>- WordPress Admin</a>";
                echo "<br>";
                echo "<a href='https://hpanel.hostinger.com/websites/manliusracingleague.com' target='_blank'>- hPanel Hostinger</a>";
                echo "<br>";
                echo "*******************************************************************";
                echo "<br>";
            } else {
                echo "";
            }
            ?>
            <br>
            Welcome to your team page.<br>
            <br>
            <a style="color:red;">See note below regarding previous years picks</a><br>
            <br>
            Below, you will find links for this year's season, payment status, your current team chart, the latest submission form, or the current segment team chart, and then any previous years played.
            <br>
            <br>
            2025 Fees & Payment info is <a href="2025_Fees.php" target="_blank" rel="noopener noreferrer">here </a><br>
            2025 Rules are <a href="2025_Rules.php" target="_blank" rel="noopener noreferrer">here </a><br>
            <!-- 2025 Race Schedule (on MRL) is <a href="2025_Schedule.php" target="_blank" rel="noopener noreferrer">here </a><br> -->
            2025 Race Schedule (on MRL) is <a style="color:red;">N/A at this time</a><br>
            2025 Race Schedule (on NASCAR) is <a href="https://www.nascar.com/nascar-cup-series/2025/schedule/" target="_blank" rel="noopener noreferrer">here </a><br>
            <br>
            ************************ Team Menu ******************************
            *******************************************************************
            <br>
            <a href="https://manliusracingleague.com/showDrivers.php" target="_blank" rel="noopener noreferrer">- Show driver selection chart for a given year </a><br>
            <a href="https://manliusracingleague.com/submitted_teams.php" target="_blank" rel="noopener noreferrer">- Submitted Teams for Current Segment </a><br>
            - Your Profile page (change your email addresses) -> Use dropdown menu - upper left at your name.<br>
            <br>
            *******************************************************************
            <br>
        </div>
    </div>

    <a name="current_user_team_chart"></a>
    <?php include 'current_user_team_chart.php'; ?>

    <?php
    $stmt = $user_home->runQuery("SELECT * FROM users WHERE userID=:uid AND changeAuth=:changeAuth");
    $stmt->execute(array(":uid" => $_SESSION['userSession'], ":changeAuth" => "Y"));
    $count = $stmt->rowCount();
    if ($count == 1) {
        include 'team-late-pick.php';
    }
    ?>

    <div style="width:80%; margin:0 auto; text-align: left;">
        <div style="color: #dfcca8; font-size:16.0pt; line-height:120%; font-family:'Century Gothic',sans-serif;">
            <br>
            <?php
            $end_ts = strtotime($formLockDate);
            $user_ts = strtotime($currentTimeIs);
            if ($formLocked == 'no') {
                if ($end_ts > $user_ts) {
                    include $currentForm;
                    include 'submitted_teams_count.php';
                } else {
                    echo "$formLockedMessage - past Lock date of $formLockDate for $raceYear $segmentName -";
                    include 'current_segment_chart.php';
                }
            } else {
                echo "$formLockedMessage";
            }
            ?>
<br>
<br>
<p style='font-size:16.0pt;line-height:120%;font-family:"Century Gothic",sans-serif;color:#dfcca8'>
    <span style="font-size:20.0pt; text-decoration:underline; display:inline;">Previous Years Picks</span>
    <br><br>
    <a style="color:red;">FYI: As of 2025-01-26 16:57:01 - The repair process of the previous years report is still a work in progress. <br></a>
    Some background info - with every submission made, a duplicate entry is recorded into a separate data table. Therefore every submission made, including additional picks or late picks, are stored in this backup table. So until I resolve the issue, I have modified the reports to use this table. The one drawback to this method, due to its purpose, is all of your picks will show. Including the multiple picks made prior to a segment starting and/or any late picks, changed drivers, etc. But for now, more is better than less or none! You should see your picks below for the years of 2017 - 2024.<br>
</p>

</div>
</div>
<br>


    <?php
    $sql = "SELECT * FROM `years` WHERE `year` < '$raceYear' AND `year` > '0' ORDER BY `years`.`year` DESC";
    foreach ($dbo->query($sql) as $row) {
        $prevRaceYear = $row[year];
        // include 'prior_year_user_team_chart.php'; // this is the original line
        include 'prior_year_user_team_chart_history.php';  // this is the temporary fix to show the history picks
    }
    ?>

    <br>

    <div style="width: 80%; margin: 0 auto; border: none; text-align: left;">
        <p style='font-size: 12.0pt; line-height: 120%; font-family: "Century Gothic", sans-serif; color: #dfcca8;'>
            Copyright &copy; 2017-<script>
                document.write(new Date().getFullYear())
            </script> Manlius Racing League
        </p>
    </div>

    <script src="bootstrap/js/jquery-1.9.1.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/scripts.js"></script>
</body>

</html>
