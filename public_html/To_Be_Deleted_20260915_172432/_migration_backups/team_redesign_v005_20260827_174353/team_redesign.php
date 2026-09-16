<?php
declare(strict_types=1);

/**
 * team_redesign.php
 *
 * VERSION: v004
 * LAST MODIFIED: 8/27/2026 4:46:58 pm
 *
 * DESCRIPTION:
 * Main universal team landing page for MRL / testphp8.
 * Keeps team.php as the single controller / landing point while supporting
 * normal picks now and LP / RD form routing later.
 *
 * CHANGELOG:
 *
 * v004 (8/27/2026 4:46:58 pm)
 * - THEME: Built-in cars.jpg background with rgba(10,20,15,0.70) overlay.
 * - THEME: Body remains transparent so the fixed background shows through.
 * - ARCHITECTURE: Named theme variables added for future per-user Cars/Dark/Light themes.
 * - PRESERVE: v003 layout, chart presentation, data and production team.php isolation.
 *
 * v003 (8/27/2026 3:51:57 pm)
 * - ARCHITECTURE: One common 85% width shell now controls header, menus and chart sections.
 * - UI: Chart-section decorative borders removed; table presentation remains unchanged.
 * - UI: Admin Menu is one collapsible +/- section containing two desktop columns.
 * - MOBILE: Admin modules stack; chart shells permit horizontal overflow when needed.
 * - PRESERVE: Production team.php and all chart/pick/scoring data behavior remain unchanged.
 *
 * v002 (8/27/2026 3:23:36 pm)
 * - UI: Base display width increased to 85% and aligned across header/menu/chart panels.
 * - UI: Admin area moved above League/Team menus and split into two side-by-side panels.
 * - UI: Installer-style typography, spacing, bullets and colors with greater transparency.
 * - FIX: Dark fallback replaces white background when no custom background image is active.
 * - FIX: Pick-window closed/open status text receives an explicit readable gold style.
 * - PRESERVE: Production team.php remains untouched; application/pick/scoring logic unchanged.
 *
 * v001 (8/27/2026 12:36:47 pm)
 * - DESIGN TEST: Isolated team-page presentation redesign; production team.php remains untouched.
 * - UI: Narrow centered sticky header based on race_results_dashboard.php / admin_setup.php styling.
 * - UI: Native JavaScript live clock replaces the external clock iframe on this test page.
 * - UI: Admin, League Information and Team Menu use translucent modular panels.
 * - NEW: League/Team links load from mrl_team_page_content.json with built-in fallback defaults.
 * - CHANGE: DB debug banner disabled on this test page.
 * - PRESERVE: Inherited v034 pick, LP, RP/RD, scoring, privacy and scheduler behavior.
 *
 * v034 (8/25/2026 12:26:16 am)
 * - UI: Previous Years now uses + / − instead of the native caret.
 * - UI: Previous Years summary typography matches Admin Menu (20pt, normal weight).
 * - PRESERVE: All v033 layout, pick, LP, RP, scoring, privacy, and scheduler behavior.
 *
 * v033 (8/24/2026 11:47:20 pm)
 * - UI: Admin Menu collapsed by default with + / − control; admin-only gate preserved.
 * - UI: Removed decorative asterisks from Admin Menu and Team Menu.
 * - UI: Removed dated red League Info timestamp line.
 * - UI: Added matching borders to Team Menu and current user info/picks.
 * - PRESERVE: Pick, LP, RP, scoring, privacy and chart logic unchanged.
 *
 * v032 (8/24/2026 4:10:00 pm)
 * - UI: Moves the Previous Years decorative border outward equally on left/right.
 * - UI: Adds matching top/bottom breathing room around the expanded charts.
 * - PRESERVE: Prior-year chart widths and alignment remain exactly as in v031.
 * - PRESERVE: Pick/form panel, collapse behavior and text readability remain unchanged.
 * - PRESERVE: No LP/RP, deadline, submission, database, scheduler or scoring changes.
 *
 * v031 (8/24/2026 4:01:00 pm)
 * - UI: Previous Years now uses the same outward decorative-panel model as the pick/form section.
 * - UI: Border encloses the heading and expanded charts without becoming their width parent.
 * - PRESERVE: Prior-year charts keep their established ~80% page-relative width.
 * - PRESERVE: Previous Years remains collapsed by default with readable dark table text.
 * - PRESERVE: No LP/RP, deadline, submission, database, scheduler or scoring changes.
 *
 * v030 (8/24/2026 3:32:51 pm)
 * - UI FIX: Uses the established team-page content width as the single width authority.
 * - UI FIX: Pick/form content now fills that width with no panel padding reducing table width.
 * - UI: Decorative panel border is drawn outside the content using a pseudo-element.
 * - PRESERVE: Previous Years collapse, chart width and text readability behavior.
 * - PRESERVE: No LP/RP, deadline, submission, database, scheduler or scoring changes.
 *
 * v029 (8/24/2026 3:22:00 pm)
 * - UI FIX: Removes v028 125% form-panel width compensation and negative margin.
 * - UI: Form/pick panel now stays at the normal team-page content width.
 * - PRESERVE: v028 Previous Years collapse, chart width and text readability fixes.
 * - PRESERVE: No LP/RP, deadline, submission, database, scheduler or scoring changes.
 *
 * v028 (8/24/2026 2:48:56 pm)
 * - UI FIX: Restores effective form/table width inside the softened panel.
 * - UI FIX: Previous Years charts render at their original page-relative widths.
 * - UI FIX: Prior-year table text is forced dark on light chart cells for readability.
 * - PRESERVE: Previous Years remains collapsed by default.
 * - PRESERVE: No LP/RP, deadline, submission, database, or scoring logic changes.
 *
 * v027 (8/24/2026 2:10:33 pm)
 * - FINALIZE: Preserves permanent LP-as-Replacement-Pick base-row support.
 * - CLEANUP: Removes LP→RP artificial-time fixture behavior and banner.
 * - UI: Removes obsolete 2025 previous-picks notices.
 * - UI: Adds subtle neutral panel around active pick/form area.
 * - UI: Previous Years Picks is collapsible and collapsed by default.
 * - PRESERVE: Existing normal/LP/RD/SPECIAL_AUTH routing and scoring semantics.
 *
 * v025 (8/22/2026 9:06:00 pm)
 * - TESTPHP8 TEMPORARY: Exact single-driver RP time-travel hook for Be Like Biff / Denny Hamlin / S1 / R08.
 * - TEST: Does not alter normal pick-window timing or schedule data.

 *
 * v024 (8/22/2026 7:26:00 pm)
 * - NEW: RD pending JSON may contain one or multiple qualifying drivers.
 * - NEW: Builds replacement option maps independently for every eligible group.
 * - NEW: Once an RD row exists, editing is locked to the originally replaced group.
 * - CHANGE: User-facing form can use the same explicit choice UI for single or dual eligibility.
 * - PRESERVE: Existing LP, normal picks, SPECIAL_AUTH, charts, menu, and deadline behavior.
 *
 * v023 (8/20/2026 8:29:00 pm)
 * - FIX: User-menu toggle now executes directly on the anchor click.
 * - FIX: return false prevents navigation to team.php#.
 * - CHANGE: Removes the v022 deferred listener block.
 * - PRESERVE: Existing menu links/appearance, charts, routing, pick logic, LP/RD logic, and data.
 *
 * v022 (8/20/2026 7:36:02 pm)
 * - FIX: Upper-left user dropdown no longer depends on Bootstrap dropdown JavaScript.
 * - NEW: Small native-JavaScript toggle opens/closes the existing MRL Home / Profile / Logout menu.
 * - PRESERVE: Existing menu appearance/links, page layout, charts, PHP routing, pick logic, LP/RD logic, and data.
 *
 * v021 (8/20/2026 2:33:24 pm)
 * - CHANGE: Pick-window closed/open messaging now follows shared automatic state.
 * - NEW: Closed-between-segments message tells users when the next segment opens.
 * - FIX: Early normal windows are treated/displayed as normal picks, not LP messaging.
 * - PRESERVE: LP, SPECIAL_AUTH, RD routing and current-segment chart behavior.
 *
 * v020 (8/19/2026 7:12:00 pm)
 * - NEW: Admin menu link to admin_pick_adjustment.php.
 * - CHANGE: No routing/scoring/LP/RD logic changes.
 *
 * v019 (8/19/2026 4:51:53 am)
 * - NEW: Normal pick-window availability now follows the shared automatic pick-window state.
 * - NEW: Normal picks open 15 days before the first race in the pick segment and close at that race start.
 * - CHANGE: Uses config_mrl.php's backward-compatible pick-segment mapping instead of requiring manual admin segment/deadline changes.
 * - SAFETY: Before a future segment's normal window opens, direct normal-form display remains blocked.
 * - CHANGE: Preserved LP, SPECIAL_AUTH and RD routing after the active pick-segment deadline.
 *
 * v018 (8/18/2026 4:13:00 am)
 * - CHANGE: RD deadline lookup now uses shared race_schedule_helper.php.
 * - CHANGE: LP and RD timing now share /race_results/_race_results_schedule.json.
 * - CHANGE: Removed team.php dependency on legacy race_results/<year>/_schedule.json.
 * - CHANGE: RD deadline lookup now respects DB-defined segment boundaries through the shared helper.
 * - CHANGE: Preserved automatic LP, SPECIAL_AUTH, normal-pick and RD routing behavior.
 *
 * v017 (8/18/2026 3:08:27 am)
 * - CHANGE: LP eligibility is now automatic and no longer requires changeAuth.
 * - CHANGE: changeAuth remains available only through the existing SPECIAL_AUTH/admin override path.
 * - FIX: Existing SEG/ADJ picks no longer become LP merely because changeAuth is enabled.
 * - NEW: Existing LP picks remain editable only until their stored effective race starts.
 * - NEW: Users with no segment pick automatically roll to the next future same-segment LP race until they submit or the segment ends.
 * - CHANGE: Preserved existing normal-pick and RD routing behavior.
 *
 * v016 (4/7/2026)
 * - CHANGE: Sync version bump only so team.php stays aligned with the updated RD wrapper terminology and hidden effective-race value.
 * - CHANGE: No logic changes from v015.
 *
 * v015 (4/6/2026)
 * - FIX: RD dropdown now only preselects an existing RD choice and no longer falls back to the original replaced driver.
 * - FIX: Preserved base segment row for readonly display while separating latest RD selection logic for the editable group.
 * - CHANGE: Preserved repeated-change RD behavior before deadline.
 *
 * v014 (4/6/2026)
 * - CHANGE: RD form now remains available for repeated changes until the schedule-based deadline passes.
 * - FIX: Added latest-RD-row loading so the current replacement choice stays visible/editable before deadline.
 * - FIX: RD option filtering now excludes the original replaced driver plus the other current team drivers while preserving the latest selected RD choice.
 *
 * v013 (4/6/2026)
 * - CHANGE: Sync version bump only so team.php stays aligned with the latest RD wrapper update.
 * - CHANGE: No logic changes from v012.
 *
 * v012 (4/6/2026)
 * - FIX: RD form now auto-expires based on the actual next-race start time from race_results/<year>/_schedule.json.
 * - FIX: Added RD deadline metadata for display in the RD wrapper (deadline race + ESPN schedule time).
 * - CHANGE: Preserved existing RD pending detection, routing, and current-year replacement dropdown logic.
 *
 * v011 (4/6/2026)
 * - FIX: Restored RD pending-file detection and routing logic in team.php.
 * - FIX: Reconnected RD helper values, base pick row lookup, and current-year replacement dropdown generation.
 * - FIX: Restored post-deadline branch to load team_replacement_driver.php when a valid RD pending file exists and no RD has already been submitted for that segment.
 *
 * v010 (4/6/2026)
 * - FIX: RD replacement dropdown now uses the same current-year driver filtering logic as form-team-picks.php.
 * - FIX: Eliminates duplicate drivers and drivers from other years in RD dropdown.
 * - Preserves existing LP / normal / special-auth routing behavior.
 *
 * v009 (4/6/2026)
 * - Added RD pending-file detection for the logged-in user's team.
 * - Added RD helper values for team name, pending JSON payload, current pick row, and replacement dropdown options.
 * - Added automatic RD routing to team_replacement_driver.php when a valid pending RD exists and no RD has already been submitted for that segment.
 * - Preserved existing LP / special-auth / normal behavior otherwise.
 *
 * v008 (4/1/2026)
 * - Added /race_results/weekly_standings.php to admin menu.
 *
 * v007 (3/31/2026)
 * - LP eligibility now requires a future points race still remain in the same active segment.
 * - Integrated race_schedule_helper.php for LP effective-race availability checks.
 * - Prevents LP form from showing when the active segment is already effectively over.
 * - Preserved existing normal form, special-auth, and past-deadline behavior otherwise.
 *
 * v006 (3/30/2026)
 * - Restored current segment team chart in the normal past-deadline / no-auth path.
 * - Keeps LP / special-auth users on the special wrapper path after deadline.
 * - Preserved working LP timing logic and normal pre-deadline form behavior.
 *
 * v005 (3/30/2026)
 * - Fixed LP routing so it only applies after the normal deadline has passed.
 * - Kept normal form behavior before deadline.
 * - Preserved SPECIAL_AUTH / LP wrapper path for post-deadline special handling.
 * - Prepared clean branch points for future RD logic.
 *
 * v004 (3/30/2026)
 * - Added LP-first routing logic in team.php.
 * - Added form mode detection for LP / RD / NORMAL while keeping current live behavior stable.
 * - LP is now detected when changeAuth = Y and the user has no picks yet for the active segment.
 * - RD is reserved as the next step and currently falls back to the normal special wrapper path.
 *
 * v003 (3/30/2026)
 * - Renamed internal helper functions with team-page-specific prefixes to avoid collisions with included form files.
 * - Preserved current behavior: normal picks still use admin_setup.currentForm and changeAuth still routes through team-late-pick.php.
 * - Kept optional DB debug banner toggle and environment-safe admin links.
 */

session_start();

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '/team.php';

require_once 'class.user.php';
$user_home = new USER();

if (!$user_home->is_logged_in()) {
    $user_home->redirect('login.php');
    exit;
}

date_default_timezone_set('America/New_York');
require 'config.php';
require 'config_mrl.php';
require_once __DIR__ . '/race_results/race_schedule_helper.php';

$currentTimeIs = date('n/j/Y g:i a');

$showDbDebugBanner = false;

function teampage_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function teampage_current_host(): string
{
    return isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== ''
        ? (string)$_SERVER['HTTP_HOST']
        : 'manliusracingleague.com';
}

function teampage_absolute_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        $path = '/';
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    return 'https://' . teampage_current_host() . $path;
}

function teampage_hostinger_site_url(string $suffix = ''): string
{
    $host = teampage_current_host();
    $base = 'https://hpanel.hostinger.com/websites/' . rawurlencode($host);

    if ($suffix === '') {
        return $base;
    }
    if ($suffix[0] !== '/') {
        $suffix = '/' . $suffix;
    }

    return $base . $suffix;
}

function teampage_get_current_db_names(USER $user_home, $dbo, $dbconnect): array
{
    $userDbName = '';
    $pdoDbName  = '';
    $myDbName   = '';

    try {
        $stmtDb = $user_home->runQuery("SELECT DATABASE() AS db");
        $stmtDb->execute();
        $rowDb = $stmtDb->fetch(PDO::FETCH_ASSOC);
        $userDbName = isset($rowDb['db']) ? (string)$rowDb['db'] : '';
    } catch (Throwable $e) {
        $userDbName = '';
    }

    try {
        if (isset($dbo) && $dbo instanceof PDO) {
            $pdoDbName = (string)$dbo->query("SELECT DATABASE()")->fetchColumn();
        }
    } catch (Throwable $e) {
        $pdoDbName = '';
    }

    try {
        if (isset($dbconnect) && $dbconnect instanceof mysqli) {
            $res = mysqli_query($dbconnect, "SELECT DATABASE() AS db");
            if ($res) {
                $row = mysqli_fetch_assoc($res);
                $myDbName = isset($row['db']) ? (string)$row['db'] : '';
            }
        }
    } catch (Throwable $e) {
        $myDbName = '';
    }

    return [
        'userDbName' => $userDbName,
        'pdoDbName'  => $pdoDbName,
        'myDbName'   => $myDbName,
    ];
}

function teampage_render_db_debug_banner(array $dbNames): string
{
    $parts = [];
    $parts[] = 'USER(PDO): ' . teampage_h($dbNames['userDbName'] !== '' ? $dbNames['userDbName'] : '(unknown)');
    $parts[] = 'dbo(PDO): ' . teampage_h($dbNames['pdoDbName'] !== '' ? $dbNames['pdoDbName'] : '(unknown)');
    $parts[] = 'dbconnect(mysqli): ' . teampage_h($dbNames['myDbName'] !== '' ? $dbNames['myDbName'] : '(unknown)');
    $parts[] = 'HOST: ' . teampage_h(teampage_current_host());

    return '<div style="padding:8px 12px; color:#fff; background:#333; font-family:Arial, sans-serif; font-size:14px;">Connected DBs: '
        . implode(' | ', $parts)
        . '</div>';
}

function teampage_user_has_change_auth(USER $user_home, int $uid): bool
{
    $stmt = $user_home->runQuery("SELECT userID FROM users WHERE userID = :uid AND changeAuth = :changeAuth");
    $stmt->execute([
        ':uid' => $uid,
        ':changeAuth' => 'Y',
    ]);

    return ($stmt->rowCount() === 1);
}


function teampage_get_current_user_team_name(mysqli $dbconnect, int $uid, string $raceYear): string
{
    $sql = "SELECT teamName
            FROM user_teams
            WHERE userID = ?
              AND raceYear = ?
            LIMIT 1";

    $stmt = mysqli_prepare($dbconnect, $sql);
    if (!$stmt) {
        return '';
    }

    mysqli_stmt_bind_param($stmt, 'is', $uid, $raceYear);
    mysqli_stmt_execute($stmt);

    $res = mysqli_stmt_get_result($stmt);
    $teamName = '';

    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $teamName = trim((string)($row['teamName'] ?? ''));
    }

    mysqli_stmt_close($stmt);
    return $teamName;
}

function teampage_rd_slug(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = trim($value);
    $value = preg_replace('/[^A-Za-z0-9 _-]+/', '', $value);
    $value = preg_replace('/\s+/', ' ', (string)$value);
    $value = str_replace([' ', '-'], '_', (string)$value);
    $value = preg_replace('/_+/', '_', (string)$value);
    $value = trim((string)$value, '_');

    return ($value !== '') ? $value : 'Team';
}

function teampage_find_latest_rd_pending(string $raceYear, string $teamName): ?array
{
    $teamSlug = teampage_rd_slug($teamName);
    $baseDir = __DIR__ . '/race_results/' . $raceYear;

    if (!is_dir($baseDir)) {
        return null;
    }

    $pattern = $baseDir . '/R??_*/_rd_pending_' . $teamSlug . '.json';
    $matches = glob($pattern);

    if (!is_array($matches) || empty($matches)) {
        return null;
    }

    rsort($matches, SORT_STRING);
    $jsonPath = (string)$matches[0];

    $payloadRaw = @file_get_contents($jsonPath);
    if ($payloadRaw === false || trim($payloadRaw) === '') {
        return null;
    }

    $payload = json_decode($payloadRaw, true);
    if (!is_array($payload)) {
        return null;
    }

    return [
        'jsonPath' => $jsonPath,
        'raceFolderName' => basename(dirname($jsonPath)),
        'payload' => $payload,
    ];
}

function teampage_rd_normalize_qualifiers(array $payload): array
{
    $out = [];

    $raw = isset($payload['qualifiers']) && is_array($payload['qualifiers'])
        ? $payload['qualifiers']
        : [];

    foreach ($raw as $q) {
        if (!is_array($q)) {
            continue;
        }

        $slot = strtoupper(trim((string)($q['slot'] ?? '')));
        $driver = trim((string)($q['driver'] ?? ''));

        if (!in_array($slot, ['A', 'B', 'C', 'D'], true) || $driver === '') {
            continue;
        }

        $triggers = isset($q['trigger_races']) && is_array($q['trigger_races'])
            ? array_values($q['trigger_races'])
            : [];

        $out[] = [
            'slot' => $slot,
            'driver' => $driver,
            'trigger_races' => $triggers,
            'effective_race' => trim((string)($q['effective_race'] ?? '')),
        ];
    }

    // Backward compatibility with old single-qualifier pending JSON.
    if (empty($out)) {
        $slot = strtoupper(trim((string)($payload['slot'] ?? '')));
        $driver = trim((string)($payload['driver'] ?? ''));

        if (in_array($slot, ['A', 'B', 'C', 'D'], true) && $driver !== '') {
            $out[] = [
                'slot' => $slot,
                'driver' => $driver,
                'trigger_races' => isset($payload['trigger_races']) && is_array($payload['trigger_races'])
                    ? array_values($payload['trigger_races'])
                    : [],
                'effective_race' => trim((string)($payload['effective_race'] ?? '')),
            ];
        }
    }

    return $out;
}

function teampage_rd_changed_group(array $baseRow, array $rdRow): string
{
    $changed = [];

    foreach (['A', 'B', 'C', 'D'] as $group) {
        $key = 'driver' . $group;
        $base = trim((string)($baseRow[$key] ?? ''));
        $rd = trim((string)($rdRow[$key] ?? ''));

        if ($base !== $rd) {
            $changed[] = $group;
        }
    }

    return count($changed) === 1 ? $changed[0] : '';
}

function teampage_user_has_rd_for_segment(PDO $dbo, int $uid, string $raceYear, string $segment): bool
{
    $sql = "SELECT pickID
            FROM user_picks
            WHERE userID = :uid
              AND raceYear = :raceYear
              AND segment = :segment
              AND pick_type = 'RD'
            LIMIT 1";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':uid' => $uid,
        ':raceYear' => $raceYear,
        ':segment' => $segment,
    ]);

    return ($stmt->fetch(PDO::FETCH_ASSOC) !== false);
}

function teampage_get_segment_base_pick_row(PDO $dbo, int $uid, string $raceYear, string $segment): ?array
{
    $sql = "SELECT pickID, driverA, driverB, driverC, driverD
            FROM user_picks
            WHERE userID = :uid
              AND raceYear = :raceYear
              AND segment = :segment
              AND pick_type IN ('SEG', 'ADJ')
            ORDER BY entryDate ASC, pickID ASC
            LIMIT 1";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':uid' => $uid,
        ':raceYear' => $raceYear,
        ':segment' => $segment,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}


function teampage_get_latest_rd_pick_row(PDO $dbo, int $uid, string $raceYear, string $segment): ?array
{
    $sql = "SELECT pickID, driverA, driverB, driverC, driverD, effective_race, entryDate
            FROM user_picks
            WHERE userID = :uid
              AND raceYear = :raceYear
              AND segment = :segment
              AND pick_type = 'RD'
            ORDER BY pickID DESC
            LIMIT 1";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':uid' => $uid,
        ':raceYear' => $raceYear,
        ':segment' => $segment,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function teampage_rd_driver_options(mysqli $dbconnect, string $slot, int $raceYear, int $uid, string $segment, array $excludeDrivers): array
{
    $tableMap = [
        'A' => ['table' => 'A Drivers', 'column' => 'driverA'],
        'B' => ['table' => 'B Drivers', 'column' => 'driverB'],
        'C' => ['table' => 'C Drivers', 'column' => 'driverC'],
        'D' => ['table' => 'D Drivers', 'column' => 'driverD'],
    ];

    $slot = strtoupper(trim($slot));
    if (!isset($tableMap[$slot])) {
        return [];
    }

    $tableName = $tableMap[$slot]['table'];
    $columnName = $tableMap[$slot]['column'];
    $raceYearStr = (string)$raceYear;

    $sql = "
        SELECT driverName, Tag
        FROM `$tableName`
        WHERE driverYear = ?
          AND Available = 'Y'
          AND driverName NOT IN (
              SELECT `$columnName`
              FROM user_picks
              WHERE userID = ?
                AND raceYear = ?
                AND segment != ?
          )
    ";

    $stmt = mysqli_prepare($dbconnect, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "iiss", $raceYear, $uid, $raceYearStr, $segment);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    $excludeMap = [];

    foreach ($excludeDrivers as $driverName) {
        $driverName = trim((string)$driverName);
        if ($driverName !== '') {
            $excludeMap[$driverName] = true;
        }
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $driverName = trim((string)($row['driverName'] ?? ''));
            $driverTag = trim((string)($row['Tag'] ?? ''));

            if ($driverName === '' || isset($excludeMap[$driverName])) {
                continue;
            }

            $rows[] = [
                'driverName' => $driverName,
                'tag' => $driverTag,
            ];
        }
    }

    mysqli_stmt_close($stmt);

    return $rows;
}

function teampage_rd_segment_label(string $segment): string
{
    $segment = strtoupper(trim($segment));

    if ($segment === 'S1') return 'Segment #1';
    if ($segment === 'S2') return 'Segment #2';
    if ($segment === 'S3') return 'Segment #3';
    if ($segment === 'S4') return 'Segment #4';

    return $segment;
}


function teampage_rd_folder_race_number(string $raceFolderName): int
{
    if (preg_match('/^R(\d{2})_/', $raceFolderName, $m)) {
        return (int)$m[1];
    }

    return 0;
}

function teampage_schedule_deadline_info(
    string $raceYear,
    string $segment,
    string $raceFolderName
): array {
    $currentRaceNumber = teampage_rd_folder_race_number($raceFolderName);
    if ($currentRaceNumber <= 0) {
        return [];
    }

    try {
        $row = mrl_schedule_helper_next_race_in_segment(
            (int)$raceYear,
            $segment,
            $currentRaceNumber
        );

        if (!is_array($row)) {
            return [];
        }

        $deadlineRaceNumber = mrl_schedule_helper_race_number($row);
        $dt = mrl_schedule_helper_race_datetime($row);

        return [
            'deadline_race_number' => $deadlineRaceNumber,
            'deadline_race_code' => (string)($row['mrl_race_code'] ?? ('R' . str_pad((string)$deadlineRaceNumber, 2, '0', STR_PAD_LEFT))),
            'deadline_timestamp' => $dt->getTimestamp(),
            'deadline_display' => $dt->format('n/j/Y g:i a') . ' ET',
            'deadline_datetime_et' => $dt->format('Y-m-d H:i:s'),
        ];
    } catch (Throwable $e) {
        return [];
    }
}

function teampage_rd_should_show(?array $rdPending): bool
{
    return ($rdPending !== null);
}

function teampage_user_has_active_segment_pick(PDO $dbo, int $uid, string $raceYear, string $segment): bool
{
    $sql = "SELECT pickID
            FROM user_picks
            WHERE userID = :uid
              AND raceYear = :raceYear
              AND segment = :segment
            LIMIT 1";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':uid' => $uid,
        ':raceYear' => $raceYear,
        ':segment' => $segment,
    ]);

    return ($stmt->fetch(PDO::FETCH_ASSOC) !== false);
}

function teampage_lp_effective_race_exists(string $raceYear, string $segment): bool
{
    try {
        $lpEffectiveRace = mrl_get_effective_race_for_lp((int)$raceYear, $segment);

        return is_array($lpEffectiveRace) && !empty($lpEffectiveRace);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * RD-specific complete base-lineup resolver.
 *
 * Normal SEG/ADJ remains first priority.  When no normal base row exists,
 * a genuine LP row may be the team's complete active lineup for this segment,
 * so the RP form must be allowed to use that LP row as its base context.
 *
 * This helper is deliberately RD-specific so normal LP/form-mode decisions
 * continue using teampage_get_segment_base_pick_row() unchanged.
 */
function teampage_get_rd_base_pick_row(PDO $dbo, int $uid, string $raceYear, string $segment): ?array
{
    $normalBase = teampage_get_segment_base_pick_row(
        $dbo,
        $uid,
        $raceYear,
        $segment
    );

    if (is_array($normalBase)) {
        return $normalBase;
    }

    $sql = "SELECT pickID, driverA, driverB, driverC, driverD
            FROM user_picks
            WHERE userID = :uid
              AND raceYear = :raceYear
              AND segment = :segment
              AND pick_type = 'LP'
            ORDER BY effective_race DESC, pickID DESC
            LIMIT 1";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':uid' => $uid,
        ':raceYear' => $raceYear,
        ':segment' => $segment,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function teampage_get_lp_pick_row(PDO $dbo, int $uid, string $raceYear, string $segment): ?array
{
    $sql = "SELECT pickID, effective_race
            FROM user_picks
            WHERE userID = :uid
              AND raceYear = :raceYear
              AND segment = :segment
              AND pick_type = 'LP'
            ORDER BY pickID DESC
            LIMIT 1";

    $stmt = $dbo->prepare($sql);
    $stmt->execute([
        ':uid' => $uid,
        ':raceYear' => $raceYear,
        ':segment' => $segment,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function teampage_lp_pick_window_open(string $raceYear, array $lpPickRow): bool
{
    $effectiveRace = (int)($lpPickRow['effective_race'] ?? 0);
    if ($effectiveRace <= 0) {
        return false;
    }

    try {
        $now = new DateTimeImmutable('now', mrl_schedule_helper_timezone());
        $races = mrl_schedule_helper_points_races((int)$raceYear);

        foreach ($races as $race) {
            if ((int)($race['race_number'] ?? 0) !== $effectiveRace) {
                continue;
            }

            $raceStart = mrl_schedule_helper_race_datetime($race);
            return ($now < $raceStart);
        }
    } catch (Throwable $e) {
        return false;
    }

    return false;
}

function teampage_determine_form_mode(USER $user_home, PDO $dbo, int $uid, string $raceYear, string $segment): string
{
    // A legitimate base segment pick blocks automatic LP.  changeAuth may still
    // deliberately open the existing SPECIAL_AUTH/admin override path.
    $basePickRow = teampage_get_segment_base_pick_row($dbo, $uid, $raceYear, $segment);
    if (is_array($basePickRow)) {
        return teampage_user_has_change_auth($user_home, $uid) ? 'SPECIAL_AUTH' : 'NORMAL';
    }

    // If an LP was already submitted, it may be edited only until that LP's
    // stored effective race starts.  It does NOT roll forward after submission.
    $lpPickRow = teampage_get_lp_pick_row($dbo, $uid, $raceYear, $segment);
    if (is_array($lpPickRow)) {
        if (teampage_lp_pick_window_open($raceYear, $lpPickRow)) {
            return 'LP';
        }

        return teampage_user_has_change_auth($user_home, $uid) ? 'SPECIAL_AUTH' : 'NORMAL';
    }

    // No SEG/ADJ/LP pick exists.  Automatic LP is available whenever the
    // canonical schedule still contains a future points race in this segment.
    // team.php only uses this mode after the original segment deadline passes.
    if (teampage_lp_effective_race_exists($raceYear, $segment)) {
        return 'LP';
    }

    // Keep the existing manual admin override available for unforeseen cases,
    // but it is no longer part of normal LP eligibility.
    if (teampage_user_has_change_auth($user_home, $uid)) {
        return 'SPECIAL_AUTH';
    }

    return 'NORMAL';
}

$dbNames = teampage_get_current_db_names($user_home, $dbo, $dbconnect);

$stmt = $user_home->runQuery("SELECT * FROM users WHERE userID = :uid");
$stmt->execute([':uid' => $_SESSION['userSession']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$name_parts = explode(' ', (string)($row['userName'] ?? ''));
$first_name = $name_parts[0] ?? '';

$uid = (int)($_SESSION['userSession'] ?? 0);
$isAdmin = isAdmin($uid);

require_once 'team_name.php';

if (isset($dbconnect)) {
    mrl_teamname_handle_ajax($dbconnect);
}

$teamNameMessage = '';
if (isset($dbconnect)) {
    $teamNameMessage = mrl_teamname_handle_save($dbconnect, (string)$raceYear, $uid);
}

$teamFormMode = teampage_determine_form_mode($user_home, $dbo, $uid, (string)$raceYear, (string)$segment);

$currentUserTeamName = '';
$rdPendingInfo = null;
$showRdWrapper = false;
$rdPendingPayload = [];
$rdPendingSegment = '';
$rdPendingSegmentLabel = '';
$rdPendingGroup = '';
$rdPendingCurrentDriver = '';
$rdPendingTriggerRaces = '';
$rdPendingEffectiveRace = '';
$rdPendingQualifiers = [];
$rdBasePickRow = null;
$rdLatestPickRow = null;
$rdActivePickRow = null;
$rdReplacementOptions = [];
$rdReplacementOptionsByGroup = [];
$rdSelectedDriversByGroup = [];
$rdLockedSelectedGroup = '';
$rdDeadlineRaceCode = '';
$rdDeadlineDisplay = '';
$rdDeadlineTimestamp = 0;
$rdSelectedDriver = '';

if (isset($dbconnect) && $dbconnect instanceof mysqli) {
    $currentUserTeamName = teampage_get_current_user_team_name($dbconnect, $uid, (string)$raceYear);
}

if ($currentUserTeamName !== '') {
    $rdPendingInfo = teampage_find_latest_rd_pending((string)$raceYear, $currentUserTeamName);
}

if ($rdPendingInfo !== null) {
    $rdPendingPayload = isset($rdPendingInfo['payload']) && is_array($rdPendingInfo['payload'])
        ? $rdPendingInfo['payload']
        : [];

    $rdPendingSegment = trim((string)($rdPendingPayload['segment'] ?? ''));
    $showRdWrapper = teampage_rd_should_show($rdPendingInfo);

    if ($showRdWrapper) {
        $rdPendingSegmentLabel = teampage_rd_segment_label($rdPendingSegment);
        $rdPendingQualifiers = teampage_rd_normalize_qualifiers($rdPendingPayload);

        if (empty($rdPendingQualifiers)) {
            $showRdWrapper = false;
        } else {
            $rdBasePickRow = teampage_get_rd_base_pick_row(
                $dbo,
                $uid,
                (string)$raceYear,
                $rdPendingSegment
            );
            $rdLatestPickRow = teampage_get_latest_rd_pick_row(
                $dbo,
                $uid,
                (string)$raceYear,
                $rdPendingSegment
            );

            $rdActivePickRow = is_array($rdLatestPickRow)
                ? $rdLatestPickRow
                : $rdBasePickRow;

            // After the first RD submission, edits remain on that one replaced
            // group. This prevents an edit from becoming a second replacement.
            if (is_array($rdBasePickRow) && is_array($rdLatestPickRow)) {
                $rdLockedSelectedGroup = teampage_rd_changed_group(
                    $rdBasePickRow,
                    $rdLatestPickRow
                );

                if ($rdLockedSelectedGroup !== '') {
                    $rdPendingQualifiers = array_values(array_filter(
                        $rdPendingQualifiers,
                        function (array $q) use ($rdLockedSelectedGroup): bool {
                            return strtoupper((string)($q['slot'] ?? '')) === $rdLockedSelectedGroup;
                        }
                    ));
                }
            }

            if (empty($rdPendingQualifiers)) {
                $showRdWrapper = false;
            } else {
                // Keep legacy singular variables populated from the first
                // remaining choice for diagnostics/backward compatibility.
                $firstQualifier = $rdPendingQualifiers[0];
                $rdPendingGroup = strtoupper(trim((string)($firstQualifier['slot'] ?? '')));
                $rdPendingCurrentDriver = trim((string)($firstQualifier['driver'] ?? ''));
                $rdPendingEffectiveRace = trim((string)($firstQualifier['effective_race'] ?? ''));
                $firstTriggers = isset($firstQualifier['trigger_races']) && is_array($firstQualifier['trigger_races'])
                    ? $firstQualifier['trigger_races']
                    : [];
                $rdPendingTriggerRaces = implode(', ', $firstTriggers);

                if (is_array($rdActivePickRow) && isset($dbconnect) && $dbconnect instanceof mysqli) {
                    foreach ($rdPendingQualifiers as $qualifier) {
                        $group = strtoupper(trim((string)($qualifier['slot'] ?? '')));
                        $originalDriver = trim((string)($qualifier['driver'] ?? ''));

                        if (!in_array($group, ['A', 'B', 'C', 'D'], true) || $originalDriver === '') {
                            continue;
                        }

                        $selectedDriver = '';
                        if (is_array($rdLatestPickRow)) {
                            $selectedDriver = trim((string)($rdLatestPickRow['driver' . $group] ?? ''));
                        }
                        $rdSelectedDriversByGroup[$group] = $selectedDriver;

                        $excludeDrivers = [];
                        foreach (['A', 'B', 'C', 'D'] as $groupCode) {
                            $driverKey = 'driver' . $groupCode;
                            $driverValue = trim((string)($rdActivePickRow[$driverKey] ?? ''));

                            if ($groupCode !== $group && $driverValue !== '') {
                                $excludeDrivers[] = $driverValue;
                            }
                        }

                        if ($originalDriver !== '' && $originalDriver !== $selectedDriver) {
                            $excludeDrivers[] = $originalDriver;
                        }

                        $rdReplacementOptionsByGroup[$group] = teampage_rd_driver_options(
                            $dbconnect,
                            $group,
                            (int)$raceYear,
                            $uid,
                            $rdPendingSegment,
                            $excludeDrivers
                        );
                    }

                    // Backward-compatible singular option list.
                    $rdReplacementOptions = $rdReplacementOptionsByGroup[$rdPendingGroup] ?? [];
                    $rdSelectedDriver = $rdSelectedDriversByGroup[$rdPendingGroup] ?? '';
                }
            }
        }
        $deadlineInfo = teampage_schedule_deadline_info((string)$raceYear, $rdPendingSegment, (string)($rdPendingInfo['raceFolderName'] ?? ''));
        if (!empty($deadlineInfo)) {
            $rdDeadlineRaceCode = (string)($deadlineInfo['deadline_race_code'] ?? '');
            $rdDeadlineDisplay = (string)($deadlineInfo['deadline_display'] ?? '');
            $rdDeadlineTimestamp = (int)($deadlineInfo['deadline_timestamp'] ?? 0);

            if ($rdDeadlineTimestamp > 0 && time() >= $rdDeadlineTimestamp) {
                $showRdWrapper = false;
            }
        }
    }
}

$wpAdminUrl = teampage_absolute_url('/wp-login.php');
$hostingerBackupsUrl = teampage_hostinger_site_url('/files/backups');
$hostingerPanelUrl = teampage_hostinger_site_url();
$phpMyAdminDb = $dbNames['myDbName'] !== '' ? $dbNames['myDbName'] : ($dbNames['pdoDbName'] !== '' ? $dbNames['pdoDbName'] : '');
$phpMyAdminUrl = $phpMyAdminDb !== ''
    ? 'https://auth-db1928.hstgr.io/index.php?db=' . rawurlencode($phpMyAdminDb)
    : 'https://auth-db1928.hstgr.io/';

/*
 * team_redesign.php presentation content.
 * The JSON file is deliberately separate from application logic so the page
 * can later be managed by an admin editor without editing this PHP file.
 */
$teamPageContentDefaults = [
    'league_panel' => [
        'title' => 'League Information',
        'items' => [
            ['label' => '{year} Fees & Payment Info', 'url' => '/{year}_Fees.php', 'enabled' => true, 'new_tab' => true],
            ['label' => '{year} Rules', 'url' => '/{year}_Rules.php', 'enabled' => true, 'new_tab' => true],
            ['label' => '{year} Race Schedule - PDF', 'url' => '/wp-content/uploads/{year}/01/{year}_Schedule_MRL.pdf', 'enabled' => true, 'new_tab' => true],
            ['label' => '{year} Race Schedule - Spreadsheet', 'url' => '/wp-content/uploads/{year}/01/{year}_Schedule_MRL.xlsx', 'enabled' => true, 'new_tab' => true],
            ['label' => '{year} Race Schedule on NASCAR.com', 'url' => 'https://www.nascar.com/nascar-cup-series/{year}/schedule/', 'enabled' => true, 'new_tab' => true],
        ],
    ],
    'team_panel' => [
        'title' => 'Team Menu',
        'items' => [
            ['label' => 'Driver Chart(s) - view, print for any year', 'url' => '/showDrivers.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Team Chart(s) - view, PDF, spreadsheet for any year/segment', 'url' => '/team_chart.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Submitted Teams for Current Segment', 'url' => '/submitted_teams.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Your Profile page', 'url' => '/profile.php', 'enabled' => true, 'new_tab' => true],
        ],
    ],
];

$teamPageContent = $teamPageContentDefaults;
$teamPageContentPath = __DIR__ . '/mrl_team_page_content.json';
if (is_file($teamPageContentPath)) {
    $teamPageContentRaw = @file_get_contents($teamPageContentPath);
    if (is_string($teamPageContentRaw) && trim($teamPageContentRaw) !== '') {
        $teamPageContentDecoded = json_decode($teamPageContentRaw, true);
        if (is_array($teamPageContentDecoded)) {
            foreach (['league_panel', 'team_panel'] as $panelKey) {
                if (isset($teamPageContentDecoded[$panelKey]) && is_array($teamPageContentDecoded[$panelKey])) {
                    $teamPageContent[$panelKey] = array_replace(
                        $teamPageContentDefaults[$panelKey],
                        $teamPageContentDecoded[$panelKey]
                    );
                }
            }
        }
    }
}

function teampage_redesign_token(string $value, string $raceYear): string
{
    return str_replace('{year}', $raceYear, $value);
}

function teampage_redesign_render_links(array $panel, string $raceYear): void
{
    $items = isset($panel['items']) && is_array($panel['items']) ? $panel['items'] : [];
    echo '<ul class="mrl-rd-list">';

    foreach ($items as $item) {
        if (!is_array($item) || empty($item['enabled'])) {
            continue;
        }

        $label = teampage_redesign_token((string)($item['label'] ?? ''), $raceYear);
        $url = teampage_redesign_token((string)($item['url'] ?? ''), $raceYear);
        if ($label === '' || $url === '') {
            continue;
        }

        $newTab = !empty($item['new_tab']);
        $target = $newTab ? ' target="_blank" rel="noopener noreferrer"' : '';

        echo '<li><a href="' . teampage_h($url) . '"' . $target . '>'
            . teampage_h($label)
            . '</a></li>';
    }

    echo '</ul>';
}

?>
<!DOCTYPE html>
<html class="no-js">
<head>
    <title><?php echo teampage_h($first_name); ?>'s Team Page</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet" media="screen">
    <link href="assets/styles.css" rel="stylesheet" media="screen">
    <style>
        body {
            background-color: #222222;
            padding-top: 60px;
        }

        /*
         * v030: keep the form/chart geometry untouched at the existing
         * team-page content width.  The panel border is decorative only
         * and is drawn outside the content box so it cannot narrow tables.
         */
        .mrl-pick-panel {
            position: relative;
            box-sizing: border-box;
            width: 100%;
            margin: 28px 0 34px 0;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .mrl-pick-panel::before {
            content: "";
            position: absolute;
            top: -18px;
            right: -22px;
            bottom: -18px;
            left: -22px;
            border: 1px solid #666666;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.018);
            pointer-events: none;
            z-index: 0;
        }

        .mrl-pick-panel > * {
            position: relative;
            z-index: 1;
        }

        /*
         * v031: Previous Years uses the same geometry model as the v030
         * pick/form panel.  The section stays full-page for layout purposes,
         * while the decorative border is positioned around the established
         * ~80% chart footprint.  The charts are NOT nested inside an 80%
         * width parent, so their existing width is preserved.
         */
        .mrl-previous-years {
            position: relative;
            width: 100%;
            margin: 26px 0 34px 0;
            border: 0;
            background: transparent;
        }

        .mrl-previous-years::before {
            content: "";
            position: absolute;
            top: -10px;
            right: calc(10% - 22px);
            bottom: -10px;
            left: calc(10% - 22px);
            border: 1px solid #666666;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.018);
            pointer-events: none;
            z-index: 0;
        }

        .mrl-previous-years > * {
            position: relative;
            z-index: 1;
        }
        .mrl-previous-years summary {
            box-sizing: border-box;
            width: 80%;
            margin: 0 auto;
            padding: 12px 16px;
            cursor: pointer;
            font-size: 20.0pt;
            font-weight: bold;
            color: #dfcca8;
            outline: none;
            border: 0;
            background: transparent;
        }
        .mrl-previous-years-content {
            width: 100%;
            padding: 10px 0 18px 0;
            color: #000000;
        }
        .mrl-previous-years-content table,
        .mrl-previous-years-content th,
        .mrl-previous-years-content td {
            color: #000000 !important;
        }

        /* v033 presentation-only section framing */
        .mrl-section-panel{position:relative;box-sizing:border-box;width:100%;margin:12px 0 18px;padding:0;border:0;background:transparent}
        .mrl-section-panel::before{content:"";position:absolute;top:-10px;right:-22px;bottom:-10px;left:-22px;border:1px solid #666;border-radius:12px;background:rgba(255,255,255,.018);pointer-events:none;z-index:0}
        .mrl-section-panel>*{position:relative;z-index:1}
        .mrl-section-panel-title,.mrl-admin-menu-panel>summary{font-size:20pt;color:#dfcca8;line-height:120%}
        .mrl-section-panel-title{margin-bottom:8px}.mrl-section-panel-content{padding:4px 0 2px}
        .mrl-admin-menu-panel>summary{cursor:pointer;list-style:none;outline:none}
        .mrl-admin-menu-panel>summary::-webkit-details-marker{display:none}
        .mrl-admin-menu-panel>summary::before{content:"+ ";font-weight:normal}
        .mrl-admin-menu-panel[open]>summary::before{content:"− "}
        .mrl-admin-menu-panel[open]>summary{margin-bottom:8px}
        .mrl-user-info-panel{position:relative;box-sizing:border-box;width:100%;margin:18px 0 28px;padding:0;border:0;background:transparent}
        .mrl-user-info-panel::before{content:"";position:absolute;top:-10px;right:calc(10% - 22px);bottom:-10px;left:calc(10% - 22px);border:1px solid #666;border-radius:12px;background:rgba(255,255,255,.018);pointer-events:none;z-index:0}
        .mrl-user-info-panel>*{position:relative;z-index:1}

        /*
         * v034 Previous Years toggle alignment:
         * Match the Admin Menu summary typography and +/- behavior.
         */
        .mrl-previous-years summary {
            list-style: none;
            font-size: 20.0pt;
            font-weight: normal;
            line-height: 120%;
        }

        .mrl-previous-years summary::-webkit-details-marker {
            display: none;
        }

        .mrl-previous-years summary::before {
            content: "+ ";
            font-weight: normal;
        }

        .mrl-previous-years[open] summary::before {
            content: "− ";
        }
    
        /* =====================================================================
         * team_redesign.php v001 - isolated presentation layer
         * =================================================================== */
        :root{
            --mrl-rd-bg:rgba(18,18,18,.76);
            --mrl-rd-panel:rgba(30,30,30,.72);
            --mrl-rd-panel-strong:rgba(23,34,29,.88);
            --mrl-rd-border:rgba(206,170,104,.34);
            --mrl-rd-green:#5be08d;
            --mrl-rd-gold:#efc982;
            --mrl-rd-text:#f3f0e9;
            --mrl-rd-muted:#c8c1b5;
            --mrl-rd-blue:#38a9ef;
            --mrl-rd-shadow:0 10px 28px rgba(0,0,0,.30);
            --mrl-rd-width:80%;
            --mrl-rd-max:1500px;
        }

        body{
            background-color:transparent !important;
            padding-top:0 !important;
        }

        .mrl-rd-shell{
            width:var(--mrl-rd-width);
            max-width:var(--mrl-rd-max);
            margin:0 auto;
        }

        .mrl-rd-sticky{
            position:sticky;
            top:8px;
            z-index:1000;
            margin:8px auto 14px;
            border:1px solid rgba(58,125,83,.72);
            border-radius:14px;
            background:linear-gradient(180deg,rgba(20,49,35,.94),rgba(19,28,24,.91));
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
            box-shadow:var(--mrl-rd-shadow);
        }

        .mrl-rd-header{
            min-height:58px;
            display:grid;
            grid-template-columns:minmax(170px,1fr) minmax(260px,2fr) minmax(190px,1fr);
            align-items:center;
            gap:12px;
            padding:8px 14px;
        }

        .mrl-rd-user{
            position:relative;
            justify-self:start;
        }

        .mrl-rd-user-button{
            appearance:none;
            border:1px solid rgba(239,201,130,.34);
            border-radius:999px;
            background:rgba(255,255,255,.045);
            color:var(--mrl-rd-text);
            padding:7px 12px;
            font:600 15px/1.1 Tahoma,Verdana,Segoe UI,sans-serif;
            cursor:pointer;
        }

        .mrl-rd-user-button:hover{
            border-color:rgba(239,201,130,.72);
            background:rgba(255,255,255,.08);
        }

        .mrl-rd-user-menu{
            display:none;
            position:absolute;
            top:calc(100% + 7px);
            left:0;
            min-width:190px;
            padding:7px;
            border:1px solid var(--mrl-rd-border);
            border-radius:10px;
            background:rgba(22,22,22,.97);
            box-shadow:var(--mrl-rd-shadow);
        }

        .mrl-rd-user.open .mrl-rd-user-menu{display:block}

        .mrl-rd-user-menu a{
            display:block;
            padding:8px 10px;
            border-radius:7px;
            color:var(--mrl-rd-text) !important;
            font:14px/1.2 Tahoma,Verdana,Segoe UI,sans-serif;
            text-decoration:none;
        }

        .mrl-rd-user-menu a:hover{
            background:rgba(255,255,255,.08);
            color:#fff !important;
        }

        .mrl-rd-title{
            min-width:0;
            text-align:center;
            color:#fff5e2;
            font:800 20px/1.1 Tahoma,Verdana,Segoe UI,sans-serif;
            letter-spacing:.5px;
        }

        .mrl-rd-title small{
            display:block;
            margin-top:2px;
            color:var(--mrl-rd-gold);
            font-size:12px;
            font-weight:700;
            letter-spacing:.2px;
        }

        .mrl-rd-clock{
            justify-self:end;
            text-align:right;
            color:var(--mrl-rd-text);
            font:700 14px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;
            white-space:nowrap;
        }

        .mrl-rd-clock small{
            display:block;
            margin-top:2px;
            color:var(--mrl-rd-muted);
            font-size:11px;
            font-weight:600;
        }

        .mrl-rd-top{
            width:var(--mrl-rd-width);
            max-width:var(--mrl-rd-max);
            margin:0 auto 18px;
            color:var(--mrl-rd-text);
            font-family:Tahoma,Verdana,Segoe UI,sans-serif;
        }

        .mrl-rd-greeting{
            margin:4px 2px 10px;
            color:var(--mrl-rd-gold);
            font-size:16px;
        }

        .mrl-rd-grid{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:12px;
            align-items:start;
        }

        .mrl-rd-grid.no-admin{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }

        .mrl-rd-card{
            border:1px solid var(--mrl-rd-border);
            border-radius:14px;
            background:var(--mrl-rd-panel);
            backdrop-filter:blur(8px);
            -webkit-backdrop-filter:blur(8px);
            box-shadow:0 8px 22px rgba(0,0,0,.20);
            overflow:hidden;
        }

        .mrl-rd-card.admin{
            border-color:rgba(239,201,130,.42);
        }

        .mrl-rd-card-title,
        .mrl-rd-card > summary{
            list-style:none;
            margin:0;
            padding:11px 14px;
            color:var(--mrl-rd-gold);
            font-size:16px;
            font-weight:800;
            line-height:1.2;
            cursor:default;
            border-bottom:1px solid rgba(255,255,255,.07);
        }

        .mrl-rd-card > summary{
            cursor:pointer;
        }

        .mrl-rd-card > summary::-webkit-details-marker{display:none}
        .mrl-rd-card > summary::after{content:"+";float:right;font-weight:500}
        .mrl-rd-card[open] > summary::after{content:"−"}

        .mrl-rd-card-body{
            padding:9px 12px 11px;
        }

        .mrl-rd-links{
            display:flex;
            flex-direction:column;
            gap:4px;
        }

        .mrl-rd-link{
            display:flex;
            align-items:flex-start;
            gap:7px;
            padding:6px 7px;
            border-radius:8px;
            color:var(--mrl-rd-blue) !important;
            font-size:14px;
            line-height:1.25;
            text-decoration:none !important;
        }

        .mrl-rd-link:hover{
            background:rgba(255,255,255,.065);
            color:#79cfff !important;
        }

        .mrl-rd-link-arrow{
            flex:0 0 auto;
            color:var(--mrl-rd-gold);
            font-weight:900;
        }

        .mrl-rd-admin-group{
            margin:2px 0 9px;
            color:var(--mrl-rd-muted);
            font-size:11px;
            font-weight:800;
            letter-spacing:.7px;
            text-transform:uppercase;
        }

        .mrl-rd-admin-group:not(:first-child){margin-top:12px}

        .mrl-rd-admin-link{
            display:block;
            padding:4px 5px;
            border-radius:6px;
            color:var(--mrl-rd-blue) !important;
            font-size:13px;
            line-height:1.2;
            text-decoration:none !important;
        }

        .mrl-rd-admin-link:hover{background:rgba(255,255,255,.06)}

        /* Keep inherited lower-page geometry but use the same visual language. */
        .mrl-user-info-panel::before,
        .mrl-pick-panel::before,
        .mrl-previous-years::before,
        .mrl-section-panel::before{
            border-color:var(--mrl-rd-border) !important;
            background:rgba(24,24,24,.58) !important;
            backdrop-filter:blur(7px);
            -webkit-backdrop-filter:blur(7px);
        }

        @media (max-width:1000px){
            :root{--mrl-rd-width:94%}
            .mrl-rd-header{grid-template-columns:1fr auto;gap:8px}
            .mrl-rd-title{grid-column:1/-1;grid-row:1;text-align:left}
            .mrl-rd-user{grid-column:1;grid-row:2}
            .mrl-rd-clock{grid-column:2;grid-row:2}
            .mrl-rd-grid,.mrl-rd-grid.no-admin{grid-template-columns:1fr}
        }

        @media (max-width:600px){
            .mrl-rd-header{padding:8px 10px}
            .mrl-rd-title{font-size:17px}
            .mrl-rd-clock{font-size:12px}
            .mrl-rd-user-button{font-size:13px}
        }
    
        /* =====================================================================
         * team_redesign.php v002 - presentation refinements
         * =================================================================== */
        html{
            background:#151515;
        }

        :root{
            --mrl-rd-width:85%;
            --mrl-rd-max:1600px;
            --mrl-rd-panel:rgba(28,28,28,.56);
            --mrl-rd-panel-header:rgba(34,34,34,.48);
            --mrl-rd-border:rgba(195,195,195,.34);
            --mrl-rd-gold:#f1c97f;
            --mrl-rd-text:#f2f2f2;
            --mrl-rd-muted:#d4d0c7;
            --mrl-rd-blue:#43b7f0;
        }

        body{
            background-color:transparent !important;
        }

        .mrl-rd-shell,
        .mrl-rd-top{
            width:var(--mrl-rd-width) !important;
            max-width:var(--mrl-rd-max) !important;
        }

        .mrl-rd-sticky{
            background:linear-gradient(180deg,rgba(18,58,40,.78),rgba(20,35,29,.74)) !important;
            border-color:rgba(67,142,94,.72) !important;
            backdrop-filter:blur(3px);
            -webkit-backdrop-filter:blur(3px);
        }

        .mrl-rd-greeting{
            margin:6px 2px 10px;
            color:var(--mrl-rd-gold);
            font-size:18px;
            line-height:1.3;
        }

        .mrl-rd-section-heading{
            margin:12px 2px 8px;
            color:var(--mrl-rd-gold);
            font:800 15px/1.2 Tahoma,Verdana,Segoe UI,sans-serif;
            letter-spacing:.4px;
        }

        .mrl-rd-admin-grid,
        .mrl-rd-main-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:14px;
            align-items:start;
            margin-bottom:14px;
        }

        .mrl-rd-card{
            border:1px solid var(--mrl-rd-border) !important;
            border-radius:14px !important;
            background:var(--mrl-rd-panel) !important;
            backdrop-filter:blur(2px);
            -webkit-backdrop-filter:blur(2px);
            box-shadow:0 8px 22px rgba(0,0,0,.18) !important;
        }

        .mrl-rd-card-title{
            padding:13px 18px 11px !important;
            color:var(--mrl-rd-gold) !important;
            background:var(--mrl-rd-panel-header);
            border-bottom:1px solid rgba(255,255,255,.10) !important;
            font:800 18px/1.25 Tahoma,Verdana,Segoe UI,sans-serif !important;
        }

        .mrl-rd-card-body{
            padding:14px 20px 16px !important;
            color:var(--mrl-rd-text);
            font:16px/1.4 Tahoma,Verdana,Segoe UI,sans-serif;
        }

        .mrl-rd-list{
            margin:0;
            padding-left:24px;
            color:var(--mrl-rd-text);
        }

        .mrl-rd-list li{
            margin:0 0 8px;
            padding-left:2px;
            line-height:1.35;
        }

        .mrl-rd-list li:last-child{
            margin-bottom:0;
        }

        .mrl-rd-list li::marker{
            color:#eeeeee;
        }

        .mrl-rd-list a{
            color:var(--mrl-rd-blue) !important;
            text-decoration:none !important;
        }

        .mrl-rd-list a:hover{
            color:#85d5ff !important;
            text-decoration:underline !important;
        }

        /* Retire v001 arrow-row presentation if any stale markup remains. */
        .mrl-rd-links{display:block}
        .mrl-rd-link-arrow{display:none}

        /*
         * 85% geometry.
         * current_user_team_chart.php emits its own inline 80% tables;
         * !important here deliberately makes the redesign test 85%.
         */
        .mrl-user-info-panel table{
            width:85% !important;
            margin-left:auto !important;
            margin-right:auto !important;
        }

        .mrl-user-info-panel::before,
        .mrl-previous-years::before{
            left:calc(7.5% - 22px) !important;
            right:calc(7.5% - 22px) !important;
        }

        .mrl-previous-years summary{
            width:85% !important;
        }

        /*
         * The current-segment chart lives inside an inherited centered wrapper.
         * That wrapper is converted to 85% by the v002 installer, while the
         * included current_segment_chart.php remains width:100% within it.
         */
        .mrl-pick-panel{
            color:#f1d49a !important;
            font:18px/1.35 "Century Gothic",Tahoma,Verdana,sans-serif !important;
            text-shadow:none !important;
            filter:none !important;
        }

        .mrl-pick-panel::before{
            background:rgba(28,28,28,.50) !important;
            border-color:var(--mrl-rd-border) !important;
            backdrop-filter:blur(2px) !important;
            -webkit-backdrop-filter:blur(2px) !important;
        }

        .mrl-user-info-panel::before,
        .mrl-previous-years::before,
        .mrl-section-panel::before{
            background:rgba(28,28,28,.50) !important;
            border-color:var(--mrl-rd-border) !important;
            backdrop-filter:blur(2px) !important;
            -webkit-backdrop-filter:blur(2px) !important;
        }

        @media (max-width:1000px){
            :root{--mrl-rd-width:94%}

            .mrl-rd-admin-grid,
            .mrl-rd-main-grid{
                grid-template-columns:1fr;
            }

            .mrl-user-info-panel table,
            .mrl-previous-years summary{
                width:94% !important;
            }

            .mrl-user-info-panel::before,
            .mrl-previous-years::before{
                left:calc(3% - 10px) !important;
                right:calc(3% - 10px) !important;
            }
        }
    
        /* =====================================================================
         * team_redesign.php v003 - single width authority / chart shell
         * =================================================================== */
        :root{
            --mrl-page-width:85%;
            --mrl-page-max:1600px;
        }

        .mrl-rd-shell,
        .mrl-rd-top,
        .mrl-rd-chart-shell{
            width:var(--mrl-page-width) !important;
            max-width:var(--mrl-page-max) !important;
            box-sizing:border-box;
            margin-left:auto !important;
            margin-right:auto !important;
        }

        /*
         * Charts are the core of the page.
         * Do not restyle their cells; only control their outer geometry.
         */
        .mrl-rd-chart-shell{
            position:relative;
            margin-top:18px !important;
            margin-bottom:28px !important;
            padding:0 !important;
            border:0 !important;
            background:transparent !important;
        }

        .mrl-user-info-panel::before,
        .mrl-pick-panel::before,
        .mrl-previous-years::before{
            display:none !important;
            content:none !important;
        }

        .mrl-user-info-panel{
            width:var(--mrl-page-width) !important;
            margin-top:18px !important;
            margin-bottom:28px !important;
        }

        .mrl-user-info-panel table,
        .mrl-previous-years-content table{
            width:100% !important;
            max-width:none !important;
            margin-left:0 !important;
            margin-right:0 !important;
        }

        .mrl-rd-pick-section .mrl-pick-panel{
            width:100% !important;
            margin:0 !important;
            padding:0 !important;
        }

        .mrl-rd-pick-section .mrl-pick-panel > table{
            width:100% !important;
        }

        .mrl-previous-years{
            width:var(--mrl-page-width) !important;
            margin-top:24px !important;
            margin-bottom:28px !important;
        }

        .mrl-previous-years summary{
            width:100% !important;
            margin:0 !important;
            padding:12px 0 14px !important;
        }

        .mrl-previous-years-content{
            width:100% !important;
            padding:6px 0 0 !important;
        }

        /* One +/- Admin bar controls both Admin modules. */
        .mrl-rd-admin-wrap{
            margin:12px 0 18px;
            padding:0;
            border:1px solid var(--mrl-rd-border);
            border-radius:14px;
            background:rgba(28,28,28,.48);
            overflow:hidden;
            backdrop-filter:blur(2px);
            -webkit-backdrop-filter:blur(2px);
        }

        .mrl-rd-admin-wrap > summary{
            list-style:none;
            cursor:pointer;
            padding:12px 18px;
            color:var(--mrl-rd-gold);
            font:800 18px/1.25 Tahoma,Verdana,Segoe UI,sans-serif;
            border-bottom:0;
            outline:none;
        }

        .mrl-rd-admin-wrap > summary::-webkit-details-marker{
            display:none;
        }

        .mrl-rd-admin-wrap > summary::before{
            content:"+ ";
            font-weight:500;
        }

        .mrl-rd-admin-wrap[open] > summary::before{
            content:"− ";
        }

        .mrl-rd-admin-wrap[open] > summary{
            border-bottom:1px solid rgba(255,255,255,.09);
        }

        .mrl-rd-admin-wrap .mrl-rd-admin-grid{
            margin:0;
            padding:14px;
            gap:14px;
        }

        /*
         * The two inner Admin cards stay separate modules, but their common
         * outer <details> makes the entire Admin area open/close as one.
         */
        .mrl-rd-admin-wrap .mrl-rd-card{
            background:rgba(28,28,28,.40) !important;
        }

        .mrl-rd-main-grid{
            margin-top:12px;
        }

        /*
         * Mobile: preserve chart presentation rather than forcing every table
         * to become a tiny stacked layout. If a chart needs room, scroll it.
         */
        .mrl-rd-chart-shell{
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
        }

        @media (max-width:1000px){
            :root{
                --mrl-page-width:94%;
            }

            .mrl-rd-admin-grid,
            .mrl-rd-main-grid{
                grid-template-columns:1fr !important;
            }

            .mrl-rd-admin-wrap .mrl-rd-admin-grid{
                padding:10px;
            }
        }
    
        /* =====================================================================
         * team_redesign.php v004 - built-in default theme
         * =================================================================== */
        :root{
            --mrl-theme-overlay:rgba(10,20,15,0.70);
            --mrl-theme-image:url("https://manliusracingleague.com/images/cars.jpg");
            --mrl-theme-fallback:#151515;
        }

        html{
            min-height:100%;
            background:
                linear-gradient(var(--mrl-theme-overlay), var(--mrl-theme-overlay)),
                var(--mrl-theme-image)
                center / cover no-repeat fixed !important;
            background-color:var(--mrl-theme-fallback) !important;
        }

        body{
            min-height:100%;
            background:transparent !important;
        }
    </style>
</head>

<body>

<div class="mrl-rd-shell mrl-rd-sticky">
    <div class="mrl-rd-header">
        <div class="mrl-rd-user" id="mrl-rd-user">
            <button type="button" class="mrl-rd-user-button" id="mrl-rd-user-button"
                    aria-expanded="false" aria-controls="mrl-rd-user-menu">
                👤 <?php echo teampage_h($first_name); ?> ▾
            </button>
            <div class="mrl-rd-user-menu" id="mrl-rd-user-menu">
                <a href="<?php echo teampage_h((string)$mrl); ?>">MRL Home</a>
                <a href="<?php echo teampage_h((string)$mrl); ?>profile.php">Profile Page</a>
                <a href="<?php echo teampage_h((string)$mrl); ?>logout.php">Logout</a>
            </div>
        </div>

        <div class="mrl-rd-title">
            <?php echo teampage_h((string)$sitename); ?>
            <small>My Team Page · redesign test</small>
        </div>

        <div class="mrl-rd-clock" id="mrl-rd-clock">
            <span id="mrl-rd-clock-time">--:--:--</span>
            <small id="mrl-rd-clock-date">America/New_York</small>
        </div>
    </div>
</div>

<div class="mrl-rd-top">
    <div class="mrl-rd-greeting">Hi <?php echo teampage_h($first_name); ?> ...</div>

    <?php if ($isAdmin): ?>
        <details class="mrl-rd-admin-wrap">
            <summary>Admin Menu</summary>
            <div class="mrl-rd-admin-grid">
            <section class="mrl-rd-card">
                <div class="mrl-rd-card-title">League &amp; Team</div>
                <div class="mrl-rd-card-body">
                    <ul class="mrl-rd-list">
                        <li><a href="/race_results/weekly_standings.php" target="_blank">Weekly Standings / scoring - Beta</a></li>
                        <li><a href="/admin_setup.php" target="_blank">Setup Year / Pick Window</a></li>
                        <li><a href="/Paid_Status_Year.php" target="_blank">Paid Status by year</a></li>
                        <li><a href="/team_view_as.php" target="_blank">View Team page as alternate user</a></li>
                        <li><a href="/email.php" target="_blank">Email addresses</a></li>
                        <li><a href="/change_user_auth.php" target="_blank">Special user authorization</a></li>
                        <li><a href="/admin_pick_adjustment.php" target="_blank">Approve LP as regular segment pick</a></li>
                        <li><a href="/addDrivers.php" target="_blank">Add drivers for a new year</a></li>
                        <li><a href="/current_segment_chart_by_entry_time.php" target="_blank">Current segment chart by entry time</a></li>
                    </ul>
                </div>
            </section>

            <section class="mrl-rd-card">
                <div class="mrl-rd-card-title">Hosting &amp; Infrastructure</div>
                <div class="mrl-rd-card-body">
                    <ul class="mrl-rd-list">
                        <li><a href="<?php echo teampage_h($phpMyAdminUrl); ?>" target="_blank">phpMyAdmin (Hostinger)</a></li>
                        <li><a href="<?php echo teampage_h($wpAdminUrl); ?>" target="_blank">WP Admin</a></li>
                        <li><a href="<?php echo teampage_h($hostingerBackupsUrl); ?>" target="_blank">Hostinger Backups</a></li>
                        <li><a href="<?php echo teampage_h($hostingerPanelUrl); ?>" target="_blank">Hostinger hPanel</a></li>
                    </ul>
                </div>
            </section>
        </div>
        </details>
    <?php endif; ?>

    <div class="mrl-rd-main-grid">
        <section class="mrl-rd-card">
            <div class="mrl-rd-card-title">
                <?php echo teampage_h((string)($teamPageContent['league_panel']['title'] ?? 'League Information')); ?>
            </div>
            <div class="mrl-rd-card-body">
                <?php teampage_redesign_render_links($teamPageContent['league_panel'], (string)$raceYear); ?>
            </div>
        </section>

        <section class="mrl-rd-card">
            <div class="mrl-rd-card-title">
                <?php echo teampage_h((string)($teamPageContent['team_panel']['title'] ?? 'Team Menu')); ?>
            </div>
            <div class="mrl-rd-card-body">
                <?php teampage_redesign_render_links($teamPageContent['team_panel'], (string)$raceYear); ?>
            </div>
        </section>
    </div>
</div>

<a name="current_user_team_chart"></a>
<section class="mrl-rd-chart-shell mrl-user-info-panel">
    <?php include 'current_user_team_chart.php'; ?>
</section>

<section class="mrl-rd-chart-shell mrl-rd-pick-section">
    <div style="color:#dfcca8; font-size:16.0pt; line-height:120%; font-family:'Century Gothic',sans-serif;">
        <div class="mrl-pick-panel">
        <?php
        $end_ts = strtotime((string)$formLockDate);
        $user_ts = strtotime((string)$currentTimeIs);
        $normalPickWindowOpen = isset($pickWindowIsOpen)
            ? (bool)$pickWindowIsOpen
            : ($end_ts !== false && $end_ts > $user_ts);
        $pickWindowOpenTs = isset($pickWindowOpenAt) ? strtotime((string)$pickWindowOpenAt) : false;

        if ($formLocked === 'no') {
            if ($normalPickWindowOpen) {

                $teamName = '';

                if (isset($dbconnect)) {
                    $teamCheck = mysqli_query(
                        $dbconnect,
                        "SELECT teamName
                         FROM user_teams
                         WHERE userID = $uid
                           AND raceYear = $raceYear
                         LIMIT 1"
                    );
                    if ($teamCheck) {
                        $teamRow = mysqli_fetch_assoc($teamCheck);
                        $teamName = trim((string)($teamRow['teamName'] ?? ''));
                    }
                }

                if ($teamName === '') {

                    if (!isset($dbconnect)) {
                        echo "<div style='color:red; font-weight:bold; font-size:14pt; text-align:center;'>Database connection not available.</div>";
                    } else {
                        mrl_teamname_render_form($dbconnect, (string)$raceYear, $uid, (string)$teamNameMessage);
                    }

                } else {

                    include $currentForm;
                    include 'submitted_teams_count.php';

                }

            } else {

                // If the active normal pick segment has not opened yet, explain when it opens.
                if (isset($pickWindowStatus) && $pickWindowStatus === 'CLOSED_BEFORE_OPEN') {
                    $openText = isset($pickWindowOpenAt) && trim((string)$pickWindowOpenAt) !== ''
                        ? (string)$pickWindowOpenAt
                        : 'the scheduled opening time';
                    echo teampage_h((string)$raceYear) . " " . teampage_h((string)$segmentName)
                        . " picks open on " . teampage_h($openText) . ".";
                } else {
                    if ($showRdWrapper) {
                        include 'team_replacement_driver.php';
                    } elseif ($teamFormMode === 'LP' || $teamFormMode === 'SPECIAL_AUTH') {
                        include 'team-late-pick.php';
                    } else {
                        $closedSegmentLabel = isset($scoringSegmentName) && trim((string)$scoringSegmentName) !== ''
                            ? (string)$scoringSegmentName
                            : (string)$segmentName;

                        echo teampage_h((string)$raceYear) . " " . teampage_h($closedSegmentLabel) . " picks are closed.";

                        if (isset($nextSegment) && trim((string)$nextSegment) !== ''
                            && isset($nextSegmentName) && trim((string)$nextSegmentName) !== ''
                            && isset($nextPickWindowOpenAt) && trim((string)$nextPickWindowOpenAt) !== '') {
                            echo " " . teampage_h((string)$raceYear) . " " . teampage_h((string)$nextSegmentName)
                                . " picks open on " . teampage_h((string)$nextPickWindowOpenAt) . ".";
                        }

                        echo "<br><br>";
                        include 'current_segment_chart.php';
                    }
                }

            }
        } else {
            echo teampage_h((string)$formLockedMessage);
        }
        ?>
        </div>
    </div>
</section>

<details class="mrl-previous-years mrl-rd-chart-shell">
    <summary>Previous Years Picks</summary>
    <div class="mrl-previous-years-content">
        <?php
        $sqlYears = "SELECT * FROM years WHERE year < :raceYear AND year > 0 ORDER BY year DESC";
        $stmtYears = $dbo->prepare($sqlYears);
        $stmtYears->execute([':raceYear' => $raceYear]);

        while ($yearRow = $stmtYears->fetch(PDO::FETCH_ASSOC)) {
            $prevRaceYear = $yearRow['year'];
            include 'prior_year_user_team_chart.php';
        }
        ?>
    </div>
</details>

<br>

<div style="width:85%; margin:0 auto; border:none; text-align:left;">
    <p style='font-size:12.0pt; line-height:120%; font-family:"Century Gothic",sans-serif; color:#dfcca8;'>
        Copyright &copy; 2017-<script>document.write(new Date().getFullYear())</script> Manlius Racing League
    </p>
</div>

<script src="bootstrap/js/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="assets/scripts.js"></script>


<script>
(function () {
    'use strict';

    var user = document.getElementById('mrl-rd-user');
    var button = document.getElementById('mrl-rd-user-button');

    if (user && button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = user.classList.toggle('open');
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            user.classList.remove('open');
            button.setAttribute('aria-expanded', 'false');
        });
    }

    var timeNode = document.getElementById('mrl-rd-clock-time');
    var dateNode = document.getElementById('mrl-rd-clock-date');

    function updateClock() {
        if (!timeNode || !dateNode) return;

        var now = new Date();
        timeNode.textContent = new Intl.DateTimeFormat('en-US', {
            timeZone: 'America/New_York',
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit'
        }).format(now);

        dateNode.textContent = new Intl.DateTimeFormat('en-US', {
            timeZone: 'America/New_York',
            weekday: 'short',
            month: 'numeric',
            day: 'numeric',
            year: 'numeric'
        }).format(now) + ' ET';
    }

    updateClock();
    window.setInterval(updateClock, 1000);
}());
</script>

</body>
</html>
