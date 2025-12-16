<?php
// ------------------------------------------------------------
// MRL Admin Setup (Edit + Summary)
// ------------------------------------------------------------

session_start();

// Store the current page URL in the session (so login can return here)
$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];

// Include necessary files after session start
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class.user.php';

// Create a new USER object
$user_home = new USER();

// Redirect to login if not logged in
if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}

// Check if the user is an admin (admin-only page)
$isAdmin = isAdmin($_SESSION['userSession'] ?? null);

// Prepare the one-line status message (rendered inside <body> later)
$adminStatusLine = $isAdmin
    ? '<div class="admin-status admin-yes">You are authorized to view/use this page</div>'
    : '<div class="admin-status admin-no">You are NOT authorized to view/use this page</div>';

// Stop here for non-admin, but still show the red line
if (!$isAdmin) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Not Authorized</title>
        <link rel="stylesheet" href="/mrl-styles.css">
        <style>
            /* body { background:#222; color:#eee; font-family: Arial, Helvetica, sans-serif; margin:0; padding:12px 20px; } */
            .admin-status { font-size: 18px; font-weight: normal; margin:0; }
            .admin-no { color: red; }
        </style>
    </head>
    <body>
        <?php echo $adminStatusLine; ?>
    </body>
    </html>
    <?php
    exit;
}

// ------------------------------------------------------------
// Helpers
// ------------------------------------------------------------
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function parseUsaDateToSql($mdy) {
    $mdy = trim((string)$mdy);
    if ($mdy === '') return null;

    $dt = DateTime::createFromFormat('n/j/Y', $mdy)
       ?: DateTime::createFromFormat('m/d/Y', $mdy);

    if (!$dt) return false;

    // Ensure exact match (catches 2/30/2026, etc.)
    if ($dt->format('n/j/Y') !== ltrim($mdy, '0') &&
        $dt->format('m/d/Y') !== $mdy) {
        return false;
    }

    return $dt->format('Y-m-d');
}

function parseUsaTimeToSql($hm_ampm) {
    $hm_ampm = trim((string)$hm_ampm);
    if ($hm_ampm === '') return null;

    $dt = DateTime::createFromFormat('g:i A', strtoupper($hm_ampm))
       ?: DateTime::createFromFormat('h:i A', strtoupper($hm_ampm));

    if (!$dt) return false;

    return $dt->format('H:i:00');
}

function formatUsaLock($sqlDate, $sqlTime) {
    if (!$sqlDate || !$sqlTime) return '';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', "$sqlDate $sqlTime");
    return $dt ? $dt->format('n/j/y g:i A') : "$sqlDate $sqlTime";
}

function formatUsaUpdated($sqlDateTime) {
    if (!$sqlDateTime) return '';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $sqlDateTime);
    return $dt ? $dt->format('n/j/y g:i A') : $sqlDateTime;
}

// ------------------------------------------------------------
// Load dropdown data
// ------------------------------------------------------------
$years = [];
$res = mysqli_query($dbconnect, "SELECT year FROM years ORDER BY year DESC");
while ($res && ($r = mysqli_fetch_assoc($res))) {
    $years[] = (int)$r['year'];
}

$maxYear  = $years ? max($years) : (int)date('Y');
$nextYear = $maxYear + 1;

$segments = [];
$res = mysqli_query($dbconnect, "SELECT segment FROM segments ORDER BY segment");
while ($res && ($r = mysqli_fetch_assoc($res))) {
    $segments[] = $r['segment'];
}
if (!$segments) {
    $segments = ['S1','S2','S3','S4'];
}

// ------------------------------------------------------------
// POST handling
// ------------------------------------------------------------
$flash = '';
$isError = false;

// Preserve user input on validation failure
$postedDate = $_POST['formLockDate'] ?? '';
$postedTime = $_POST['formLockTime'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ---- Add Year ----
    if ($action === 'add_year') {

        $ny = (int)$nextYear;
        mysqli_query(
            $dbconnect,
            "INSERT INTO years (yearID, year) VALUES ($ny, $ny)"
        );

        header("Location: admin_setup.php?msg=" . urlencode("Added year $ny."));
        exit;
    }
    // ---- Save Changes ----
    if ($action === 'save_changes') {

        // Validate date
        $dateResult = parseUsaDateToSql($postedDate);
        if ($postedDate !== '' && $dateResult === false) {
            $flash = "Invalid date. Please use MM/DD/YYYY.";
            $isError = true;
        }

        // Validate time
        $timeResult = parseUsaTimeToSql($postedTime);
        if ($postedTime !== '' && $timeResult === false) {
            $flash = "Invalid time. Please use HH:MM AM/PM.";
            $isError = true;
        }

        if (!$isError) {

            $raceYear  = (int)($_POST['raceYear'] ?? 0);
            $segment   = mysqli_real_escape_string($dbconnect, $_POST['segment'] ?? '');
            $lockedUi  = strtolower($_POST['formLocked'] ?? 'no');
            $locked    = ($lockedUi === 'yes') ? 'yes' : 'no';

            $dSql = $dateResult ? "'$dateResult'" : "NULL";
            $tSql = $timeResult ? "'$timeResult'" : "NULL";

            $uid = (int)$_SESSION['userSession'];

            mysqli_query($dbconnect, "
                UPDATE admin_setup SET
                    raceYear     = $raceYear,
                    segment      = '$segment',
                    formLocked   = '$locked',
                    formLockDate = $dSql,
                    formLockTime = $tSql,
                    updatedBy    = $uid,
                    updatedAt    = NOW()
                WHERE id = 1
            ");

            header("Location: admin_setup.php?msg=" . urlencode("Configuration updated successfully."));
            exit;
        }
    }
}

// ------------------------------------------------------------
// Load current config
// ------------------------------------------------------------
$res = mysqli_query($dbconnect, "
    SELECT a.*, u.userName
    FROM admin_setup a
    LEFT JOIN users u ON a.updatedBy = u.userID
    WHERE a.id = 1
");
$current = $res ? mysqli_fetch_assoc($res) : null;

if (!$current) {
    die("Error: Unable to read admin_setup.");
}

$displayLock    = formatUsaLock($current['formLockDate'], $current['formLockTime']);
$displayUpdated = formatUsaUpdated($current['updatedAt']);

$prefillDate = $isError ? $postedDate :
    ($current['formLockDate'] ? date('n/j/Y', strtotime($current['formLockDate'])) : '');

$prefillTime = $isError ? $postedTime :
    ($current['formLockTime'] ? date('g:i A', strtotime($current['formLockTime'])) : '');

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>MRL Admin Setup</title>
<link rel="stylesheet" href="/mrl-styles.css">
<style>
/* body { background:#222; color:#eee; font-family:Arial; margin:0; padding:0; } */
.admin-status { font-size: 18px; font-weight: normal; margin:0; padding:0px 0px 0 0px; }
.admin-yes { color: green; }
.admin-no { color: red; }
.page-wrap { padding: 0px; }

h1 { font-size:48px; margin:10px 0; }
.flash { color:#ff6b6b; font-size:26px; font-weight:800; margin-bottom:18px; }
.flash.success { color:#3ee06f; }
table { border-collapse:collapse; min-width:520px; margin-bottom:24px; }
th,td { border:1px solid #666; padding:12px; }
th { background:#444; }
td { background:#333; }
label { font-size:26px; font-weight:800; display:block; margin-bottom:6px; }
select,input { width:100%; font-size:22px; padding:10px; }
.row { display:flex; gap:16px; align-items:end; }
.grow { flex:1; }
.btn { font-size:20px; padding:12px 16px; cursor:pointer; }
.btn-primary { width:240px; }
.note { font-size:12px; color:#bbb; margin-top:12px; }
</style>
</head>
<body>

<?php echo $adminStatusLine; ?>

<div class="page-wrap">

<h1>MRL Admin Setup</h1>

<?php if ($flash): ?>
<div class="flash"><?php echo h($flash); ?></div>
<?php elseif ($msg): ?>
<div class="flash success"><?php echo h($msg); ?></div>
<?php endif; ?>

<table>
<tr><th>Setting</th><th>Current Value</th></tr>
<tr><td>Race Year</td><td><?php echo h($current['raceYear']); ?></td></tr>
<tr><td>Segment</td><td><?php echo h($current['segment']); ?></td></tr>
<tr><td>Form Locked</td><td><?php echo h($current['formLocked']); ?></td></tr>
<tr><td>Form Lock</td><td><?php echo h($displayLock); ?></td></tr>
<tr><td>Current Form</td><td><?php echo h($current['currentForm']); ?></td></tr>
<tr><td>Last Updated</td><td><?php echo h($displayUpdated); ?></td></tr>
<tr><td>Updated By</td><td><?php echo h($current['userName']); ?></td></tr>
</table>
<form method="post" action="admin_setup.php" autocomplete="off">

<div class="row">
<div class="grow">
<label>Race Year</label>
<select name="raceYear">
<?php foreach ($years as $y): ?>
<option value="<?php echo $y; ?>" <?php if ($y == $current['raceYear']) echo 'selected'; ?>>
<?php echo $y; ?>
</option>
<?php endforeach; ?>
</select>
</div>

<button class="btn" type="submit" name="action" value="add_year">
Add <?php echo $nextYear; ?>
</button>
</div>

<label>Segment</label>
<select name="segment">
<?php foreach ($segments as $s): ?>
<option <?php if ($s === $current['segment']) echo 'selected'; ?>>
<?php echo h($s); ?>
</option>
<?php endforeach; ?>
</select>

<label>Form Locked</label>
<select name="formLocked">
<option value="No"  <?php if ($current['formLocked'] !== 'yes') echo 'selected'; ?>>No</option>
<option value="Yes" <?php if ($current['formLocked'] === 'yes') echo 'selected'; ?>>Yes</option>
</select>

<label>Form Lock Date</label>
<input type="text" name="formLockDate" value="<?php echo h($prefillDate); ?>">

<label>Form Lock Time</label>
<div class="row">
<div class="grow">
<input type="text" name="formLockTime" value="<?php echo h($prefillTime); ?>">
</div>
<button class="btn btn-primary" type="submit" name="action" value="save_changes">
Save Changes
</button>
</div>

</form>

<div class="note">POST → redirect → GET prevents resubmission.</div>

</div>

</body>
</html>
