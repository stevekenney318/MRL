<?php
// *********************************************************************
// 	These variables need to be set to the current MRL season & segment
//	       required for submiting picks to database
//
//	Steve Kenney 7/27/2020
//
// *********************************************************************

$formLocked = "no";    // this is a manual lock used to show form or not. Must be "no" to show form

$raceYear = "2026"; // Current Year
$previousRaceYear = $raceYear - 1; // Subtract 1 to get the previous year

// Segment 4
// $segment = "S4"; // Current Submission Segment S1 , S2 , S3 , S4
// $formLockDate = '8/31/2025 6:00 pm'; // date/time to lock form for segment

// Segment 3
// $segment = "S3"; // Current Submission Segment S1 , S2 , S3 , S4
// $formLockDate = '6/28/2025 7:00 pm'; // date/time to lock form for segment

// Segment 2
// $segment = "S2"; // Current Submission Segment S1 , S2 , S3 , S4
// $formLockDate = '4/13/2025 3:00 pm'; // date/time to lock form for segment

// Segment 1
$segment = "S1"; // Current Submission Segment S1 , S2 , S3 , S4
$formLockDate = '2/15/2026 2:30 pm'; // date/time to lock form for segment (updated time)


// set segmentName based on segment
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

// $currentForm = 'form-mrl005.php'; // this is the current form being used.
$currentForm = 'form-mrl006.php'; // this is the current form being used.
$formLockedMessage = "**** Message - Submission form is currently offline ****";    // Message to show when form is locked
$formHeaderMessage = "** Dropdown will only show drivers available to add to your team. **";
$formHeaderMessage2 = "Picks for $raceYear $segmentName due by $formLockDate. When you click 'Submit Picks', they will be entered into our database, and appear in chart above.";



// Previous Submission Segment S1 , S2 , S3 , S4 - used to see who hasn't submitted yet
// set prevSegment based on current segment
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

// Compare Submission Segment S1 , S2 , S3 , S4 - used to see who hasn't submitted yet
// set compareSegment based on current segment
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