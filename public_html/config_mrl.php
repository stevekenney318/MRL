<?php
/**
 * config_mrl.php
 *
 * VERSION: v003
 * LAST MODIFIED: 8/20/2026 2:33:24 pm
 *
 * DESCRIPTION:
 * Backward-compatible MRL configuration layer with automatic pick-window support.
 *
 * CHANGELOG:
 * v003 (8/20/2026 2:33:24 pm)
 * - NEW: Reads optional exact one-segment opening timestamp.
 * - NEW: Exposes next-segment state/open/deadline for team/admin messaging.
 * - NEW: Exposes lead mode/display for exact-vs-days UI.
 * - PRESERVE: Existing automatic, LP/RD compatibility and year-aware segment naming.
 *
 * v002 (8/19/2026 3:08:00 pm)
 * - NEW: Reads configurable global default pick-window lead time from admin_setup.
 * - NEW: Reads optional one-segment lead-time adjustment from admin_setup.
 * - NEW: Exposes effective/default lead-time variables for admin/UI use.
 * - CHANGE: Temporary exact-date override remains higher priority than AUTO lead-time rules.
 *
 * v001 (8/19/2026 4:51:53 am)
 * - Initial automatic pick-window configuration layer.
 */

$formLocked = 'no';
$raceYear = '2026';
$previousRaceYear = (int)$raceYear - 1;
$segment = 'S1';
$formLockDate = '2/15/2026 2:30 pm';
$currentForm = 'form-team-picks.php';
$formLockedMessage = '**** Message - Submission form is currently offline ****';
$formHeaderMessage = '** Dropdown will only show drivers available to add to your team. **';

$scoringSegment = $segment;
$pickSegment = $segment;
$pickWindowIsOpen = false;
$pickWindowStatus = 'FALLBACK';
$pickWindowSource = 'FALLBACK';
$pickWindowOpenAt = '';
$pickDeadlineAt = $formLockDate;
$pickWindowError = '';
$pickWindowDefaultDays = 15;
$pickWindowEffectiveDays = 15;
$pickWindowLeadSource = 'DEFAULT';
$pickLeadAdjustYear = null;
$pickLeadAdjustSegment = '';
$pickLeadAdjustDays = null;
$pickLeadAdjustOpenAt = '';
$pickWindowLeadMode = 'DAYS';
$pickWindowLeadDisplay = '15 days';
$nextSegment = '';
$nextSegmentName = '';
$nextSegmentStartRace = 0;
$nextPickWindowOpenAt = '';
$nextPickDeadlineAt = '';
$nextPickLeadSource = '';
$nextPickLeadMode = '';
$nextPickLeadDisplay = '';

$adminConfiguredSegment = $segment;
$adminConfiguredLockDate = $formLockDate;
$adminConfiguredFormLocked = $formLocked;
$adminSetupRow = null;

if (isset($dbconnect) && $dbconnect instanceof mysqli) {
    $sql = "
        SELECT
            raceYear,
            segment,
            formLocked,
            formLockDate,
            formLockTime,
            currentForm,
            pickOverrideEnabled,
            pickOverrideSegment,
            pickOverrideOpenAt,
            pickOverrideDeadlineAt,
            pickWindowDefaultDays,
            pickLeadAdjustYear,
            pickLeadAdjustSegment,
            pickLeadAdjustDays,
            pickLeadAdjustOpenAt
        FROM admin_setup
        ORDER BY updatedAt DESC
        LIMIT 1
    ";

    $result = mysqli_query($dbconnect, $sql);
    if ($result && mysqli_num_rows($result) === 1) {
        $adminSetupRow = mysqli_fetch_assoc($result);
    }
}

if (is_array($adminSetupRow)) {
    $raceYear = (string)$adminSetupRow['raceYear'];
    $previousRaceYear = (int)$raceYear - 1;
    $formLocked = strtolower((string)$adminSetupRow['formLocked']) === 'yes' ? 'yes' : 'no';
    $currentForm = trim((string)$adminSetupRow['currentForm']) !== ''
        ? (string)$adminSetupRow['currentForm']
        : $currentForm;

    $adminConfiguredSegment = strtoupper(trim((string)$adminSetupRow['segment']));
    $adminConfiguredFormLocked = $formLocked;
    $adminConfiguredLockDate = trim((string)$adminSetupRow['formLockDate'] . ' ' . (string)$adminSetupRow['formLockTime']);

    $defaultDaysCandidate = filter_var($adminSetupRow['pickWindowDefaultDays'] ?? 15, FILTER_VALIDATE_INT);
    if ($defaultDaysCandidate !== false && $defaultDaysCandidate >= 0 && $defaultDaysCandidate <= 90) {
        $pickWindowDefaultDays = (int)$defaultDaysCandidate;
    }
    $pickLeadAdjustYear = isset($adminSetupRow['pickLeadAdjustYear']) && $adminSetupRow['pickLeadAdjustYear'] !== null
        ? (int)$adminSetupRow['pickLeadAdjustYear']
        : null;
    $pickLeadAdjustSegment = strtoupper(trim((string)($adminSetupRow['pickLeadAdjustSegment'] ?? '')));
    $pickLeadAdjustDays = isset($adminSetupRow['pickLeadAdjustDays']) && $adminSetupRow['pickLeadAdjustDays'] !== null
        ? (int)$adminSetupRow['pickLeadAdjustDays']
        : null;
    $pickLeadAdjustOpenAt = trim((string)($adminSetupRow['pickLeadAdjustOpenAt'] ?? ''));

    if ($adminConfiguredSegment !== '') {
        $segment = $adminConfiguredSegment;
        $scoringSegment = $segment;
        $pickSegment = $segment;
    }
    if ($adminConfiguredLockDate !== '') {
        $formLockDate = $adminConfiguredLockDate;
        $pickDeadlineAt = $formLockDate;
    }
}

$pickWindowHelper = __DIR__ . '/pick_window_helper.php';
if (is_file($pickWindowHelper)) {
    require_once $pickWindowHelper;

    try {
        $override = [
            'enabled' => is_array($adminSetupRow) ? (string)($adminSetupRow['pickOverrideEnabled'] ?? 'no') : 'no',
            'segment' => is_array($adminSetupRow) ? (string)($adminSetupRow['pickOverrideSegment'] ?? '') : '',
            'open_at' => is_array($adminSetupRow) ? (string)($adminSetupRow['pickOverrideOpenAt'] ?? '') : '',
            'deadline_at' => is_array($adminSetupRow) ? (string)($adminSetupRow['pickOverrideDeadlineAt'] ?? '') : '',
        ];
        $settings = [
            'default_days' => $pickWindowDefaultDays,
            'adjust_year' => $pickLeadAdjustYear,
            'adjust_segment' => $pickLeadAdjustSegment,
            'adjust_days' => $pickLeadAdjustDays,
            'adjust_open_at' => $pickLeadAdjustOpenAt,
        ];

        $pickWindowState = mrl_pick_window_state((int)$raceYear, $override, null, $settings);

        $scoringSegment = (string)$pickWindowState['scoring_segment'];
        $pickSegment = (string)$pickWindowState['pick_segment'];
        $segment = $pickSegment;

        $pickWindowIsOpen = (bool)$pickWindowState['window_is_open'];
        $pickWindowStatus = (string)$pickWindowState['status'];
        $pickWindowSource = (string)$pickWindowState['source'];
        $pickWindowOpenAt = (string)$pickWindowState['window_open_display'];
        $pickDeadlineAt = (string)$pickWindowState['deadline_display'];
        $pickWindowError = (string)($pickWindowState['override_error'] ?? '');
        $pickWindowDefaultDays = (int)($pickWindowState['default_lead_days'] ?? $pickWindowDefaultDays);
        $pickWindowEffectiveDays = (int)($pickWindowState['effective_lead_days'] ?? $pickWindowDefaultDays);
        $pickWindowLeadSource = (string)($pickWindowState['lead_source'] ?? 'DEFAULT');
        $pickWindowLeadMode = (string)($pickWindowState['effective_lead_mode'] ?? 'DAYS');
        $pickWindowLeadDisplay = (string)($pickWindowState['effective_lead_display'] ?? ($pickWindowEffectiveDays . ' days'));

        $nextSegment = (string)($pickWindowState['next_segment'] ?? '');
        $nextSegmentName = (string)($pickWindowState['next_segment_label'] ?? '');
        $nextSegmentStartRace = (int)($pickWindowState['next_start_race'] ?? 0);
        $nextPickWindowOpenAt = (string)($pickWindowState['next_window_open_display'] ?? '');
        $nextPickDeadlineAt = (string)($pickWindowState['next_deadline_display'] ?? '');
        $nextPickLeadSource = (string)($pickWindowState['next_lead_source'] ?? '');
        $nextPickLeadMode = (string)($pickWindowState['next_lead_mode'] ?? '');
        $nextPickLeadDisplay = (string)($pickWindowState['next_lead_display'] ?? '');

        $formLockDate = $pickDeadlineAt;
    } catch (Throwable $e) {
        $pickWindowError = $e->getMessage();
        $pickWindowStatus = 'FALLBACK';
        $pickWindowSource = 'FALLBACK';
        $pickWindowIsOpen = false;
    }
}

if (!function_exists('mrl_config_segment_name')) {
    function mrl_config_segment_name(int $year, string $segment): string
    {
        $segment = strtoupper(trim($segment));
        if ($segment === 'S1') return 'Segment #1';
        if ($segment === 'S2') return 'Segment #2';
        if ($segment === 'S3') return 'Segment #3';
        if ($segment === 'S4') return $year >= 2026 ? 'The Chase' : 'Playoffs';
        return $segment;
    }
}

$segmentName = mrl_config_segment_name((int)$raceYear, (string)$segment);
$pickSegmentName = mrl_config_segment_name((int)$raceYear, (string)$pickSegment);
$scoringSegmentName = mrl_config_segment_name((int)$raceYear, (string)$scoringSegment);

$formHeaderMessage2 = "Picks for $raceYear $segmentName due by $formLockDate. When you click 'Submit Picks', they will be entered into our database, and appear in chart above.";

if ($segment === 'S1') $prevSegment = 'S4';
if ($segment === 'S2') $prevSegment = 'S1';
if ($segment === 'S3') $prevSegment = 'S2';
if ($segment === 'S4') $prevSegment = 'S3';

if ($segment === 'S1') $compareSegment = 'S4';
if ($segment === 'S2') $compareSegment = 'S1';
if ($segment === 'S3') $compareSegment = 'S1';
if ($segment === 'S4') $compareSegment = 'S1';
?>