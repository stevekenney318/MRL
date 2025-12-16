<?php
// *********************************************************************
//  MRL Configuration File (Database Driven)
//  Reads current season/segment setup from admin_setup table
//
//  Steve Kenney
// *********************************************************************

/*
    NOTES:
    - Preserves all existing variable names
    - All pages requiring this file continue to work
*/


// ---------------------------------------------------------------------
// DEFAULT FALLBACK VALUES (used only if DB read fails)
// ---------------------------------------------------------------------

$formLocked = "no";

$raceYear = "2026";
$previousRaceYear = $raceYear - 1;

$segment = "S1";
$formLockDate = '2/15/2026 2:30 pm';

$currentForm = 'form-mrl006.php';

$formLockedMessage = "**** Message - Submission form is currently offline ****";
$formHeaderMessage = "** Dropdown will only show drivers available to add to your team. **";
$formHeaderMessage2 = "Picks for $raceYear Segment #1 due by $formLockDate. When you click 'Submit Picks', they will be entered into our database, and appear in chart above.";

// ---------------------------------------------------------------------
// ATTEMPT TO READ FROM DATABASE
// ---------------------------------------------------------------------

if (isset($dbconnect)) {

    $sql = "
        SELECT
            raceYear,
            segment,
            formLocked,
            formLockDate,
            formLockTime,
            currentForm
        FROM admin_setup
        ORDER BY updatedAt DESC
        LIMIT 1
    ";

    $result = mysqli_query($dbconnect, $sql);

    if ($result && mysqli_num_rows($result) === 1) {

        $row = mysqli_fetch_assoc($result);

        // Override fallback values with DB values
        $raceYear = $row['raceYear'];
        $previousRaceYear = $raceYear - 1;

        $segment = $row['segment'];
        $formLocked = $row['formLocked'];
        $currentForm = $row['currentForm'];

        // Combine DATE + TIME into legacy-compatible string
        $formLockDate = $row['formLockDate'] . ' ' . $row['formLockTime'];
    }
}

// ---------------------------------------------------------------------
// SEGMENT NAME
// ---------------------------------------------------------------------

if ($segment == 'S1') {
    $segmentName = 'Segment #1';
}
if ($segment == 'S2') {
    $segmentName = 'Segment #2';
}
if ($segment == 'S3') {
    $segmentName = 'Segment #3';
}
if ($segment == 'S4') {
    $segmentName = 'Playoffs';
}

// ---------------------------------------------------------------------
// HEADER MESSAGES (depend on raceYear / segmentName / formLockDate)
// ---------------------------------------------------------------------

$formHeaderMessage2 = "Picks for $raceYear $segmentName due by $formLockDate. When you click 'Submit Picks', they will be entered into our database, and appear in chart above.";

// ---------------------------------------------------------------------
// PREVIOUS SUBMISSION SEGMENT
// ---------------------------------------------------------------------

if ($segment == 'S1') {
    $prevSegment = 'S4';
}
if ($segment == 'S2') {
    $prevSegment = 'S1';
}
if ($segment == 'S3') {
    $prevSegment = 'S2';
}
if ($segment == 'S4') {
    $prevSegment = 'S3';
}

// ---------------------------------------------------------------------
// COMPARE SUBMISSION SEGMENT
// ---------------------------------------------------------------------

if ($segment == 'S1') {
    $compareSegment = 'S4';
}
if ($segment == 'S2') {
    $compareSegment = 'S1';
}
if ($segment == 'S3') {
    $compareSegment = 'S1';
}
if ($segment == 'S4') {
    $compareSegment = 'S1';
}

?>
