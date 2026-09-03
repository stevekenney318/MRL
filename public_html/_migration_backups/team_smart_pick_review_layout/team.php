<?php
declare(strict_types=1);

/**
 * team.php
 *
 * VERSION: v043
 * LAST MODIFIED: 8/31/2026 3:24:34 pm
 *
 * DESCRIPTION:
 * Main universal team landing page for MRL / testphp8.
 * Keeps team.php as the single controller / landing point while supporting
 * normal picks now and LP / RD form routing later.
 *
 * CHANGELOG:
 *
 * v043 (8/31/2026 3:24:34 pm)
 * - NEW: Adds a quiet inline Review Your Submission step before SEG / LP / RD pick writes.
 * - NEW: Shows exact new picks, old -> new changes, and unchanged picks by driver group.
 * - NEW: Identical selections are detected and not submitted.
 * - PRESERVE: Existing v040 quiet-submit engine remains authoritative after Confirm Submission.
 * - PRESERVE: v042 Custom HTML handshake height behavior, themes, announcements, charts, scoring, and DB logic.
 *
 * v042 (8/31/2026 3:03:01 pm)
 * - FIX: Custom HTML iframe height now uses an explicit resize handshake instead of continuous observers.
 * - NEW: Dynamic snippets can request a resize after they finish building their layout.
 * - PRESERVE: Custom HTML remains transparent by default; snippet controls its own background.
 * - PRESERVE: v041 Custom HTML management, v040 quiet submit, themes, announcement, charts, picks, LP, RD, scoring, and DB behavior.
 *
 * v041 (8/31/2026 1:30:45 pm)
 * - NEW: Adds one JSON-managed Custom HTML block to the Team page.
 * - NEW: Supports Enabled/Disabled plus Above/Below Announcement placement.
 * - SAFETY: Custom HTML is isolated inside a sandboxed iframe rather than injected into the Team page DOM.
 * - PRESERVE: v040 quiet pick submission, announcements, menus, themes, charts, picks, LP, RD, scoring, and database behavior.
 *
 * v040 (8/31/2026 2:14:40 am)
 * - UI: Successful SEG / LP / RD submissions now run quietly in the background with fetch(), eliminating the full-page reload flash.
 * - UI: After confirmed success, the current team chart and submitted-team count refresh quietly from the returned Team page.
 * - UI: Preserves the existing green "✓ Your picks have been submitted." confirmation near the form for about eight seconds.
 * - SAFETY: Success is shown only when the normal server submission flow returns the existing success marker.
 * - FALLBACK: If browser fetch support is unavailable, normal full-page form submission remains unchanged.
 * - PRESERVE: submit-team-picks.php remains the authoritative database handler; no pick, LP, RP/RD, audit-history, scoring, deadline, or database behavior changes.
 *
 * v039 (8/30/2026)
 * - UI: Moves the successful-pick confirmation directly beneath the active pick form, aligned to the right near Submit Picks.
 * - UI: Extends confirmation display time from about four seconds to about eight seconds before fading.
 * - PRESERVE: Successful-submission-only behavior and all existing pick/database logic.
 *
 * v038 (8/30/2026)
 * - UI: Adds a one-time successful-pick submission confirmation above the current team chart.
 * - UI: Confirmation remains visible for about four seconds, then fades away smoothly.
 * - UI: Uses a translucent dark-green panel with a green border to fit the current team-page design.
 * - PRESERVE: Existing picks, LP, RP/RD, scoring, charts, menus, themes and database behavior.
 *
 * v037 (8/28/2026 3:45:40 pm)
 * - UI: Replaces the user/person icon with a checkered flag.
 * - UI: Returns the sticky masthead to approximately its original compact height.
 * - UI: Uses Georgia / Times-style serif typography across the masthead.
 * - UI: Uses normal font weight across user, title, subtitle, clock and date text.
 * - PRESERVE: v036 panel-state memory, announcement/news panel, themes, menus, charts,
 *             normal picks, LP, RP/RD, scoring, View-As, profile, scheduler and DB behavior.
 *
 * v036 (8/28/2026 3:09:01 pm)
 * - UI: Admin Menu and Previous Years Picks remember their open/closed state per browser using localStorage.
 * - UI: Indents the greeting to align visually with the content below.
 * - UI: Enlarges sticky-header text and vertical spacing while preserving left / center / right alignment.
 * - NEW: Optional JSON-managed announcement/news panel directly below the greeting.
 * - NEW: Plain http:// and https:// URLs in announcement text are automatically rendered as safe clickable links.
 * - PRESERVE: Existing themes, menus, charts, normal picks, LP, RP/RD, scoring, View-As, profile, scheduler and DB behavior.
 *
 * v035 (8/27/2026 10:16:02 pm)
 * - PRODUCTION: Promotes the fully tested team redesign into team.php.
 * - UI: Uses the consolidated 85% themed layout with Cars, Starry Night, Dark and Light.
 * - ADMIN: Uses JSON-managed League & Team, Hosting & Infrastructure, League Information and Team Menu panels.
 * - PROFILE: Production links now target profile.php.
 * - CLEANUP: Retains the consolidated v009-v011 component-boundary and Light-theme fixes.
 * - PRESERVE: Normal picks, LP, RP/RD, scoring, charts, View-As and scheduler behavior.
 *
 * v011 (8/27/2026 8:47:32 pm)
 * - FIX: Light theme status-panel text is black for maximum readability.
 * - FIX: Links inside Light-theme status panels remain blue.
 * - PRESERVE: All non-Light themes, charts, forms, menus, themes, profile,
 *             pick/LP/RP-RD/scoring behavior, and production pages.
 *
 * v010 (8/27/2026 8:21:46 pm)
 * - FIX: Restores black table/form text inside included chart and pick components.
 * - ARCHITECTURE: Tightens parent-page CSS boundary so included components keep their own table presentation.
 * - PRESERVE: v009 consolidated layout, four-panel menus, themes, charts,
 *             pick/LP/RP-RD/scoring behavior, profile integration, and production pages.
 *
 * v009 (8/27/2026 7:36:25 pm)
 * - CLEANUP: Consolidates redesign CSS into one current stylesheet.
 * - CLEANUP: Removes two unused legacy helper functions and an unused timestamp variable.
 * - CLEANUP: Removes superseded presentation selectors from earlier redesign passes.
 * - PRESERVE: Current v008 layout, four-panel JSON menus, themes, charts,
 *             pick/LP/RP-RD/scoring behavior, profile integration, and production pages.
 *
 * v008 (8/27/2026 7:13:18 pm)
 * - FIX: Loads all four JSON-driven panels, including both Admin panels.
 * - CLEANUP: Removes obsolete Connected DB diagnostic/environment URL helpers.
 * - CLEANUP: Uses one explicit four-panel content schema for fallback + JSON merge.
 * - PRESERVE: User-edited JSON content/order, themes, profile integration, charts,
 *             picks/scoring behavior, and production team.php/profile.php.
 *
 * v007 (8/27/2026 6:57:28 pm)
 * - FIX: Admin menu data-state repair / Content Manager v003 compatibility.
 * - PRESERVE: No visual/chart/theme changes.
 *
 * v006 (8/27/2026 6:33:12 pm)
 * - ORGANIZATION: uses /mrl_team/ for JSON/helper/content manager.
 * - ADMIN: all four panels JSON-driven; Manager control fixed.
 * - THEME: Light contrast/readability cleanup.
 * - PRESERVE: charts and production pages untouched.
 *
 * v005 (8/27/2026 5:27:00 pm)
 * - UI: Dedicated non-collapsible pick/submission status panels.
 * - ADMIN: Hard-wired Manage Team Page Content action.
 * - THEME: Per-user Cars / Starry Night / Dark / Light themes.
 * - PROFILE: Profile redesign/theme selector integration.
 * - PRESERVE: Chart presentation and production team.php/profile.php.
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
require_once __DIR__ . '/mrl_team/mrl_theme_helper.php';
require_once __DIR__ . '/race_results/race_schedule_helper.php';

$pickSubmissionSuccess = !empty($_SESSION['mrl_pick_submission_success']);
unset($_SESSION['mrl_pick_submission_success']);

$currentTimeIs = date('n/j/Y g:i a');

function teampage_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function teampage_render_pick_success(bool $show): void
{
    if (!$show) {
        return;
    }

    echo '<div class="mrl-pick-success" id="mrl-pick-success" role="status" aria-live="polite">'
        . '✓ Your picks have been submitted.'
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

$stmt = $user_home->runQuery("SELECT * FROM users WHERE userID = :uid");
$stmt->execute([':uid' => $_SESSION['userSession']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$name_parts = explode(' ', (string)($row['userName'] ?? ''));
$first_name = $name_parts[0] ?? '';

$uid = (int)($_SESSION['userSession'] ?? 0);
$isAdmin = isAdmin($uid);
$mrlTheme = mrl_theme_get($dbo, $uid);

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

/*
 * team.php presentation content.
 * The JSON file is deliberately separate from application logic so the page
 * can later be managed by an admin editor without editing this PHP file.
 */
$teamPageContentDefaults = [
    'custom_html_block' => [
        'enabled' => false,
        'position' => 'above',
        'html' => '',
    ],
    'announcement_panel' => [
        'enabled' => false,
        'title' => 'League News',
        'content' => '',
    ],
    'admin_league_panel' => [
        'title' => 'League & Team',
        'items' => [
            ['label' => 'Weekly Standings / scoring - Beta', 'url' => '/race_results/weekly_standings.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Setup Year / Pick Window', 'url' => '/admin_setup.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Paid Status by year', 'url' => '/Paid_Status_Year.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'View Team page as alternate user', 'url' => '/team_view_as.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Email addresses', 'url' => '/email.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Special user authorization', 'url' => '/change_user_auth.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Approve LP as regular segment pick', 'url' => '/admin_pick_adjustment.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Add drivers for a new year', 'url' => '/addDrivers.php', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Current segment chart by entry time', 'url' => '/current_segment_chart_by_entry_time.php', 'enabled' => true, 'new_tab' => true],
        ],
    ],
    'admin_hosting_panel' => [
        'title' => 'Hosting & Infrastructure',
        'items' => [
            ['label' => 'phpMyAdmin (Hostinger)', 'url' => 'https://hpanel.hostinger.com/', 'enabled' => true, 'new_tab' => true],
            ['label' => 'WP Admin', 'url' => '/wp-admin/', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Hostinger Backups', 'url' => 'https://hpanel.hostinger.com/', 'enabled' => true, 'new_tab' => true],
            ['label' => 'Hostinger hPanel', 'url' => 'https://hpanel.hostinger.com/', 'enabled' => true, 'new_tab' => true],
        ],
    ],
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

$teamPageContentPanelKeys = [
    'custom_html_block',
    'announcement_panel',
    'admin_league_panel',
    'admin_hosting_panel',
    'league_panel',
    'team_panel',
];

$teamPageContent = $teamPageContentDefaults;
$teamPageContentPath = __DIR__ . '/mrl_team/mrl_team_page_content.json';

if (is_file($teamPageContentPath)) {
    $teamPageContentRaw = @file_get_contents($teamPageContentPath);

    if (is_string($teamPageContentRaw) && trim($teamPageContentRaw) !== '') {
        $teamPageContentDecoded = json_decode($teamPageContentRaw, true);

        if (is_array($teamPageContentDecoded)) {
            foreach ($teamPageContentPanelKeys as $panelKey) {
                if (isset($teamPageContentDecoded[$panelKey])
                    && is_array($teamPageContentDecoded[$panelKey])) {
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

function teampage_render_announcement_text(string $text): void
{
    $parts = preg_split('~(https?://[^\\s<]+)~i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) {
        echo nl2br(teampage_h($text));
        return;
    }

    foreach ($parts as $part) {
        if ($part === '') continue;
        if (preg_match('~^https?://~i', $part) === 1) {
            $url = $part;
            $trail = '';
            while ($url !== '' && preg_match('/[.,;:!?)]$/', $url) === 1) {
                $trail = substr($url, -1) . $trail;
                $url = substr($url, 0, -1);
            }
            if ($url !== '') {
                echo '<a href="' . teampage_h($url) . '" target="_blank" rel="noopener noreferrer">' . teampage_h($url) . '</a>';
            }
            if ($trail !== '') echo teampage_h($trail);
        } else {
            echo nl2br(teampage_h($part));
        }
    }
}

?>
<!DOCTYPE html>
<html class="no-js mrl-theme-<?php echo teampage_h($mrlTheme); ?>">
<head>
    <title><?php echo teampage_h($first_name); ?>'s Team Page</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet" media="screen">
    <link href="assets/styles.css" rel="stylesheet" media="screen">
    <style>
        /* =====================================================================
         * team.php v009 - consolidated current presentation
         * =================================================================== */
        :root{
            --mrl-page-width:85%;
            --mrl-page-max:1600px;
            --mrl-rd-panel:rgba(28,28,28,.48);
            --mrl-rd-panel-header:rgba(34,34,34,.42);
            --mrl-rd-border:rgba(195,195,195,.34);
            --mrl-rd-gold:#f1c97f;
            --mrl-rd-text:#f2f2f2;
            --mrl-rd-muted:#d4d0c7;
            --mrl-rd-blue:#43b7f0;
            --mrl-rd-shadow:0 10px 28px rgba(0,0,0,.30);
        }

        html{
            min-height:100%;
            background:#151515;
        }

        html.mrl-theme-cars{
            --mrl-rd-panel:rgba(28,28,28,.48);
            --mrl-rd-panel-header:rgba(34,34,34,.42);
            --mrl-rd-border:rgba(195,195,195,.34);
            --mrl-rd-gold:#f1c97f;
            --mrl-rd-text:#f2f2f2;
            --mrl-rd-muted:#d4d0c7;
            --mrl-rd-blue:#43b7f0;
            background:
                linear-gradient(rgba(10,20,15,.70),rgba(10,20,15,.70)),
                url("/images/cars.jpg") center/cover no-repeat fixed!important;
        }

        html.mrl-theme-starry-night{
            --mrl-rd-panel:rgba(19,22,31,.56);
            --mrl-rd-panel-header:rgba(24,27,39,.50);
            --mrl-rd-border:rgba(190,198,221,.34);
            --mrl-rd-gold:#e8cf9a;
            --mrl-rd-text:#f2f3f7;
            --mrl-rd-muted:#d4d7e2;
            --mrl-rd-blue:#67bdf2;
            background:
                linear-gradient(rgba(5,8,18,.60),rgba(5,8,18,.60)),
                url("/images/starry_night.jpg") center/cover no-repeat fixed!important;
        }

        html.mrl-theme-dark{
            --mrl-rd-panel:rgba(28,28,28,.88);
            --mrl-rd-panel-header:rgba(34,34,34,.92);
            --mrl-rd-border:rgba(195,195,195,.34);
            --mrl-rd-gold:#f1c97f;
            --mrl-rd-text:#f2f2f2;
            --mrl-rd-muted:#d4d0c7;
            --mrl-rd-blue:#43b7f0;
            background:#151515!important;
        }

        html.mrl-theme-light{
            --mrl-rd-panel:rgba(255,255,255,.90);
            --mrl-rd-panel-header:rgba(244,244,244,.96);
            --mrl-rd-border:rgba(60,60,60,.28);
            --mrl-rd-gold:#8b5b00;
            --mrl-rd-text:#202020;
            --mrl-rd-muted:#555;
            --mrl-rd-blue:#006eaa;
            background:#eceff1!important;
        }

        body{
            min-height:100%;
            padding-top:0!important;
            background:transparent!important;
            color:var(--mrl-rd-text);
        }

        .mrl-rd-shell,
        .mrl-rd-top,
        .mrl-rd-chart-shell{
            width:var(--mrl-page-width)!important;
            max-width:var(--mrl-page-max)!important;
            box-sizing:border-box;
            margin-left:auto!important;
            margin-right:auto!important;
        }

        /* Sticky header */
        .mrl-rd-sticky{
            position:sticky;
            top:8px;
            z-index:1000;
            margin-top:8px!important;
            margin-bottom:14px!important;
            border:1px solid rgba(67,142,94,.72);
            border-radius:14px;
            background:linear-gradient(180deg,rgba(18,58,40,.78),rgba(20,35,29,.74));
            backdrop-filter:blur(3px);
            -webkit-backdrop-filter:blur(3px);
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

        .mrl-rd-user{position:relative;justify-self:start}

        .mrl-rd-user-button{
            appearance:none;
            border:1px solid rgba(239,201,130,.34);
            border-radius:999px;
            background:rgba(255,255,255,.045);
            color:var(--mrl-rd-text);
            padding:7px 12px;
            font:400 18px/1.15 Georgia,"Times New Roman",serif;
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
            color:#f2f2f2!important;
            font:14px/1.2 Tahoma,Verdana,Segoe UI,sans-serif;
            text-decoration:none;
        }

        .mrl-rd-user-menu a:hover{background:#333;color:#fff!important}

        .mrl-rd-title{
            min-width:0;
            text-align:center;
            color:#fff5e2;
            font:400 24px/1.15 Georgia,"Times New Roman",serif;
            letter-spacing:.5px;
        }

        .mrl-rd-title small{
            display:block;
            margin-top:2px;
            color:var(--mrl-rd-gold);
            font-size:14px;
            font-weight:400;
            font-family:Georgia,"Times New Roman",serif;
            letter-spacing:.2px;
        }

        .mrl-rd-clock{
            justify-self:end;
            text-align:right;
            color:var(--mrl-rd-text);
            font:400 17px/1.2 Georgia,"Times New Roman",serif;
            white-space:nowrap;
        }

        .mrl-rd-clock small{
            display:block;
            margin-top:2px;
            color:var(--mrl-rd-muted);
            font-size:13px;
            font-weight:400;
            font-family:Georgia,"Times New Roman",serif;
        }

        /* Top navigation panels */
        .mrl-rd-top{
            margin-bottom:18px!important;
            color:var(--mrl-rd-text);
            font-family:Tahoma,Verdana,Segoe UI,sans-serif;
        }

        .mrl-rd-greeting{
            margin:8px 20px 12px;
            color:var(--mrl-rd-gold);
            font-size:18px;
            line-height:1.3;
        }

        .mrl-rd-announcement{
            margin:0 0 16px;
            border:1px solid var(--mrl-rd-border);
            border-radius:14px;
            background:var(--mrl-rd-panel);
            backdrop-filter:blur(2px);
            -webkit-backdrop-filter:blur(2px);
            box-shadow:0 8px 22px rgba(0,0,0,.18);
            overflow:hidden;
        }

        .mrl-rd-announcement-title{
            padding:12px 18px 10px;
            color:var(--mrl-rd-gold);
            background:var(--mrl-rd-panel-header);
            border-bottom:1px solid rgba(255,255,255,.10);
            font:800 18px/1.25 Tahoma,Verdana,Segoe UI,sans-serif;
        }

        .mrl-rd-announcement-body{
            padding:14px 20px 16px;
            color:var(--mrl-rd-text);
            font:16px/1.5 Tahoma,Verdana,Segoe UI,sans-serif;
            white-space:normal;
        }
        .mrl-rd-announcement-body a{color:var(--mrl-rd-blue)!important;text-decoration:underline!important}

        .mrl-rd-custom-html{
            margin:0 0 16px;
            border:0;
            border-radius:14px;
            overflow:hidden;
            background:transparent;
        }
        .mrl-rd-custom-html iframe{
            display:block;
            width:100%;
            min-height:120px;
            border:0;
            background:transparent;
            overflow:hidden;
        }

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

        .mrl-rd-admin-wrap>summary{
            list-style:none;
            cursor:pointer;
            padding:12px 18px;
            color:var(--mrl-rd-gold);
            font:800 18px/1.25 Tahoma,Verdana,Segoe UI,sans-serif;
            outline:none;
        }

        .mrl-rd-admin-wrap>summary::-webkit-details-marker{display:none}
        .mrl-rd-admin-wrap>summary::before{content:"+ ";font-weight:500}
        .mrl-rd-admin-wrap[open]>summary::before{content:"− "}
        .mrl-rd-admin-wrap[open]>summary{border-bottom:1px solid rgba(255,255,255,.09)}

        .mrl-rd-admin-fixed-control{
            margin:12px 14px 0;
            padding:10px 14px;
            border:1px solid var(--mrl-rd-border);
            border-radius:10px;
            background:rgba(0,0,0,.16);
        }

        .mrl-rd-admin-fixed-control a{
            color:var(--mrl-rd-blue)!important;
            text-decoration:none!important;
            font-weight:800;
        }

        .mrl-rd-admin-grid,
        .mrl-rd-main-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:14px;
            align-items:start;
        }

        .mrl-rd-admin-wrap .mrl-rd-admin-grid{margin:0;padding:14px}
        .mrl-rd-main-grid{margin-top:12px;margin-bottom:14px}

        .mrl-rd-card{
            border:1px solid var(--mrl-rd-border);
            border-radius:14px;
            background:var(--mrl-rd-panel);
            backdrop-filter:blur(2px);
            -webkit-backdrop-filter:blur(2px);
            box-shadow:0 8px 22px rgba(0,0,0,.18);
            overflow:hidden;
        }

        .mrl-rd-admin-wrap .mrl-rd-card{background:rgba(28,28,28,.40)}

        .mrl-rd-card-title{
            padding:13px 18px 11px;
            color:var(--mrl-rd-gold);
            background:var(--mrl-rd-panel-header);
            border-bottom:1px solid rgba(255,255,255,.10);
            font:800 18px/1.25 Tahoma,Verdana,Segoe UI,sans-serif;
        }

        .mrl-rd-card-body{
            padding:14px 20px 16px;
            color:var(--mrl-rd-text);
            font:16px/1.4 Tahoma,Verdana,Segoe UI,sans-serif;
        }

        .mrl-rd-list{
            margin:0;
            padding-left:24px;
            color:var(--mrl-rd-text);
        }

        .mrl-rd-list li{margin:0 0 8px;padding-left:2px;line-height:1.35}
        .mrl-rd-list li:last-child{margin-bottom:0}
        .mrl-rd-list li::marker{color:#eee}
        .mrl-rd-list a{color:var(--mrl-rd-blue)!important;text-decoration:none!important}
        .mrl-rd-list a:hover{color:#85d5ff!important;text-decoration:underline!important}

        /* Chart/form shells: geometry only; chart cells remain untouched. */
        .mrl-rd-chart-shell{
            position:relative;
            margin-top:18px!important;
            margin-bottom:28px!important;
            padding:0!important;
            border:0!important;
            background:transparent!important;
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
        }

        .mrl-user-info-panel,
        .mrl-previous-years{width:var(--mrl-page-width)!important}

        .mrl-user-info-panel table,
        .mrl-previous-years-content table{
            width:100%!important;
            max-width:none!important;
            margin-left:0!important;
            margin-right:0!important;
        }

        .mrl-pick-panel{
            position:relative;
            box-sizing:border-box;
            width:100%!important;
            margin:0!important;
            padding:0!important;
            border:0!important;
            background:transparent!important;
            color:#f1d49a!important;
            font:18px/1.35 "Century Gothic",Tahoma,Verdana,sans-serif!important;
            text-shadow:none!important;
            filter:none!important;
        }

        .mrl-rd-pick-section .mrl-pick-panel>table{width:100%!important}

        /* Included components own their table presentation.  The team-page shell
         * controls geometry only and must not leak theme text colors into tables. */
        .mrl-user-info-panel table,
        .mrl-user-info-panel th,
        .mrl-user-info-panel td,
        .mrl-rd-pick-section .mrl-pick-panel table,
        .mrl-rd-pick-section .mrl-pick-panel th,
        .mrl-rd-pick-section .mrl-pick-panel td{
            color:#000!important;
            text-shadow:none!important;
            filter:none!important;
        }

        .mrl-rd-pick-section .mrl-pick-panel select,
        .mrl-rd-pick-section .mrl-pick-panel input,
        .mrl-rd-pick-section .mrl-pick-panel button{
            color:#000!important;
            text-shadow:none!important;
        }

        .mrl-rd-notice-panel{
            box-sizing:border-box;
            width:100%;
            margin:14px 0 18px;
            padding:13px 18px;
            border:1px solid var(--mrl-rd-border);
            border-radius:12px;
            background:var(--mrl-rd-panel);
            color:var(--mrl-rd-gold);
            font:18px/1.45 "Century Gothic",Tahoma,Verdana,sans-serif;
            backdrop-filter:blur(2px);
            -webkit-backdrop-filter:blur(2px);
        }

        .mrl-rd-submission-panel{margin-top:18px;margin-bottom:18px}
        .mrl-rd-submission-panel a{color:var(--mrl-rd-blue)!important}

        .mrl-previous-years{
            position:relative;
            margin-top:24px!important;
            margin-bottom:28px!important;
            border:0;
            background:transparent;
        }

        .mrl-previous-years summary{
            list-style:none;
            box-sizing:border-box;
            width:100%!important;
            margin:0!important;
            padding:12px 0 14px!important;
            cursor:pointer;
            color:#dfcca8;
            font:400 20pt/1.2 "Century Gothic",Tahoma,Verdana,sans-serif;
            outline:none;
        }

        .mrl-previous-years summary::-webkit-details-marker{display:none}
        .mrl-previous-years summary::before{content:"+ ";font-weight:400}
        .mrl-previous-years[open] summary::before{content:"− "}

        .mrl-previous-years-content{
            width:100%!important;
            padding:6px 0 0!important;
            color:#000;
        }

        .mrl-previous-years-content table,
        .mrl-previous-years-content th,
        .mrl-previous-years-content td{color:#000!important}

        /* Light theme requires explicit contrast instead of dark-theme inheritance. */
        html.mrl-theme-light body{color:#202020!important}
        html.mrl-theme-light .mrl-rd-sticky,
        html.mrl-theme-light .mrl-rd-sticky *{color:#fff7e6!important}
        html.mrl-theme-light .mrl-rd-user,
        html.mrl-theme-light .mrl-rd-user *{color:#fff!important}
        html.mrl-theme-light .mrl-rd-user-menu{background:#242424!important;border-color:#555!important}
        html.mrl-theme-light .mrl-rd-user-menu a{color:#f2f2f2!important}
        html.mrl-theme-light .mrl-rd-user-menu a:hover{color:#fff!important;background:#333!important}
        html.mrl-theme-light .mrl-rd-title small,
        html.mrl-theme-light .mrl-rd-clock,
        html.mrl-theme-light .mrl-rd-clock *{color:#fff3d5!important}
        html.mrl-theme-light .mrl-rd-greeting{color:#8b5b00!important}
        html.mrl-theme-light .mrl-rd-announcement{background:rgba(255,255,255,.90)!important;color:#202020!important}
        html.mrl-theme-light .mrl-rd-announcement-title{background:rgba(244,244,244,.98)!important;color:#8b5b00!important}
        html.mrl-theme-light .mrl-rd-announcement-body{color:#202020!important}
        html.mrl-theme-light .mrl-rd-announcement-body a{color:#006eaa!important}
        html.mrl-theme-light .mrl-rd-admin-wrap{background:rgba(255,255,255,.58)!important;color:#202020!important}
        html.mrl-theme-light .mrl-rd-admin-wrap>summary{color:#8b5b00!important}
        html.mrl-theme-light .mrl-rd-card{background:rgba(255,255,255,.90)!important;color:#202020!important}
        html.mrl-theme-light .mrl-rd-card-title{background:rgba(244,244,244,.98)!important;color:#8b5b00!important}
        html.mrl-theme-light .mrl-rd-card-body{color:#202020!important}
        html.mrl-theme-light .mrl-rd-list{color:#202020!important}
        html.mrl-theme-light .mrl-rd-list li::marker{color:#555!important}
        html.mrl-theme-light .mrl-rd-list a{color:#006eaa!important}
        html.mrl-theme-light .mrl-rd-admin-fixed-control{background:rgba(255,255,255,.78)!important}
        html.mrl-theme-light .mrl-rd-admin-fixed-control a{color:#006eaa!important}
        html.mrl-theme-light .mrl-rd-notice-panel{background:rgba(255,255,255,.88)!important;color:#000!important}
        html.mrl-theme-light .mrl-rd-notice-panel *{color:#000!important}
        html.mrl-theme-light .mrl-rd-notice-panel a{color:#006eaa!important}
        html.mrl-theme-light .mrl-previous-years summary{color:#8b5b00!important;opacity:1!important}

        .mrl-pick-success{
            width:32%;
            min-width:360px;
            box-sizing:border-box;
            margin:10px 0 12px auto;
            padding:10px 14px;
            border:1px solid rgba(83,190,112,.85);
            border-radius:8px;
            background:rgba(14,72,38,.82);
            color:#f3fff5!important;
            font-family:Georgia,"Times New Roman",serif;
            font-size:18px;
            line-height:1.3;
            text-align:center;
            box-shadow:0 2px 10px rgba(0,0,0,.20);
            opacity:1;
            transition:opacity .8s ease, margin .8s ease, padding .8s ease, max-height .8s ease;
            max-height:80px;
            overflow:hidden;
        }

        .mrl-pick-success.mrl-pick-success-hide{
            opacity:0;
            margin-top:0;
            margin-bottom:0;
            padding-top:0;
            padding-bottom:0;
            max-height:0;
            border-width:0;
        }

        html.mrl-theme-light .mrl-pick-success{
            background:rgba(222,246,226,.96)!important;
            border-color:#4b9d5c!important;
            color:#174c25!important;
        }

        @media(max-width:1000px){
            :root{--mrl-page-width:94%}
            .mrl-rd-header{grid-template-columns:1fr auto;gap:8px}
            .mrl-rd-title{grid-column:1/-1;grid-row:1;text-align:left}
            .mrl-rd-user{grid-column:1;grid-row:2}
            .mrl-rd-clock{grid-column:2;grid-row:2}
            .mrl-rd-admin-grid,
            .mrl-rd-main-grid{grid-template-columns:1fr!important}
            .mrl-rd-admin-wrap .mrl-rd-admin-grid{padding:10px}
        }

        @media(max-width:600px){
            .mrl-pick-success{width:100%;min-width:0}
            .mrl-rd-header{padding:8px 10px}
            .mrl-rd-title{font-size:17px}
            .mrl-rd-clock{font-size:12px}
            .mrl-rd-user-button{font-size:13px}
        }
    </style>
</head>

<body>

<div class="mrl-rd-shell mrl-rd-sticky">
    <div class="mrl-rd-header">
        <div class="mrl-rd-user" id="mrl-rd-user">
            <button type="button" class="mrl-rd-user-button" id="mrl-rd-user-button"
                    aria-expanded="false" aria-controls="mrl-rd-user-menu">
                🏁 <?php echo teampage_h($first_name); ?> ▾
            </button>
            <div class="mrl-rd-user-menu" id="mrl-rd-user-menu">
                <a href="<?php echo teampage_h((string)$mrl); ?>">MRL Home</a>
                <a href="<?php echo teampage_h((string)$mrl); ?>profile.php">Profile Page</a>
                <a href="<?php echo teampage_h((string)$mrl); ?>logout.php">Logout</a>
            </div>
        </div>

        <div class="mrl-rd-title">
            <?php echo teampage_h((string)$sitename); ?>
            <small>My Team Page</small>
        </div>

        <div class="mrl-rd-clock" id="mrl-rd-clock">
            <span id="mrl-rd-clock-time">--:--:--</span>
            <small id="mrl-rd-clock-date">America/New_York</small>
        </div>
    </div>
</div>

<div class="mrl-rd-top">
    <div class="mrl-rd-greeting">Hi <?php echo teampage_h($first_name); ?> ...</div>

    <?php
    $customHtmlBlock = isset($teamPageContent['custom_html_block']) && is_array($teamPageContent['custom_html_block'])
        ? $teamPageContent['custom_html_block']
        : [];
    $customHtmlEnabled = !empty($customHtmlBlock['enabled']);
    $customHtmlPosition = ((string)($customHtmlBlock['position'] ?? 'above') === 'below') ? 'below' : 'above';
    $customHtmlContent = (string)($customHtmlBlock['html'] ?? '');

    $announcementPanel = isset($teamPageContent['announcement_panel']) && is_array($teamPageContent['announcement_panel'])
        ? $teamPageContent['announcement_panel']
        : [];
    $announcementEnabled = !empty($announcementPanel['enabled']);
    $announcementTitle = trim((string)($announcementPanel['title'] ?? ''));
    $announcementContent = trim((string)($announcementPanel['content'] ?? ''));

    function teampage_render_custom_html_iframe(string $html): void
    {
        if (trim($html) === '') return;
        $srcdoc = htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        echo '<section class="mrl-rd-custom-html">';
        echo '<iframe class="mrl-rd-custom-html-frame" scrolling="no" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" srcdoc="' . $srcdoc . '" onload="teampageResizeCustomHtmlFrame(this)"></iframe>';
        echo '</section>';
    }
    ?>

    <?php if ($customHtmlEnabled && $customHtmlPosition === 'above' && trim($customHtmlContent) !== ''): ?>
        <?php teampage_render_custom_html_iframe($customHtmlContent); ?>
    <?php endif; ?>

    <?php if ($announcementEnabled && $announcementContent !== ''): ?>
        <section class="mrl-rd-announcement">
            <?php if ($announcementTitle !== ''): ?><div class="mrl-rd-announcement-title"><?php echo teampage_h($announcementTitle); ?></div><?php endif; ?>
            <div class="mrl-rd-announcement-body"><?php teampage_render_announcement_text($announcementContent); ?></div>
        </section>
    <?php endif; ?>

    <?php if ($customHtmlEnabled && $customHtmlPosition === 'below' && trim($customHtmlContent) !== ''): ?>
        <?php teampage_render_custom_html_iframe($customHtmlContent); ?>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <details class="mrl-rd-admin-wrap" id="mrl-rd-admin-details">
            <summary>Admin Menu</summary>
            <div class="mrl-rd-admin-fixed-control">
                <a href="/mrl_team/admin_team_page_content.php" target="_blank" rel="noopener noreferrer">Manage Team Page Content</a>
            </div>
            <div class="mrl-rd-admin-grid">
                <section class="mrl-rd-card">
                    <div class="mrl-rd-card-title"><?php echo teampage_h((string)($teamPageContent['admin_league_panel']['title'] ?? 'League & Team')); ?></div>
                    <div class="mrl-rd-card-body"><?php teampage_redesign_render_links($teamPageContent['admin_league_panel'] ?? [], (string)$raceYear); ?></div>
                </section>
                <section class="mrl-rd-card">
                    <div class="mrl-rd-card-title"><?php echo teampage_h((string)($teamPageContent['admin_hosting_panel']['title'] ?? 'Hosting & Infrastructure')); ?></div>
                    <div class="mrl-rd-card-body"><?php teampage_redesign_render_links($teamPageContent['admin_hosting_panel'] ?? [], (string)$raceYear); ?></div>
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
                    teampage_render_pick_success($pickSubmissionSuccess);
                    echo "<div class='mrl-rd-notice-panel mrl-rd-submission-panel'>";
                    include 'submitted_teams_count.php';
                    echo "</div>";

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
                        teampage_render_pick_success($pickSubmissionSuccess);
                    } elseif ($teamFormMode === 'LP' || $teamFormMode === 'SPECIAL_AUTH') {
                        include 'team-late-pick.php';
                        teampage_render_pick_success($pickSubmissionSuccess);
                    } else {
                        $closedSegmentLabel = isset($scoringSegmentName) && trim((string)$scoringSegmentName) !== ''
                            ? (string)$scoringSegmentName
                            : (string)$segmentName;

                        echo "<div class='mrl-rd-notice-panel mrl-rd-pick-status-panel'>";
                        echo teampage_h((string)$raceYear) . " " . teampage_h($closedSegmentLabel) . " picks are closed.";

                        if (isset($nextSegment) && trim((string)$nextSegment) !== ''
                            && isset($nextSegmentName) && trim((string)$nextSegmentName) !== ''
                            && isset($nextPickWindowOpenAt) && trim((string)$nextPickWindowOpenAt) !== '') {
                            echo " " . teampage_h((string)$raceYear) . " " . teampage_h((string)$nextSegmentName)
                                . " picks open on " . teampage_h((string)$nextPickWindowOpenAt) . ".";
                        }

                        echo "</div>";
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

<details class="mrl-previous-years mrl-rd-chart-shell" id="mrl-rd-previous-years-details">
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

    function rememberDetails(id, storageKey) {
        var details = document.getElementById(id);
        if (!details || !window.localStorage) return;

        try {
            var saved = window.localStorage.getItem(storageKey);
            if (saved === 'open') details.open = true;
            if (saved === 'closed') details.open = false;

            details.addEventListener('toggle', function () {
                window.localStorage.setItem(storageKey, details.open ? 'open' : 'closed');
            });
        } catch (e) {
            /* localStorage is convenience-only; normal details behavior remains if unavailable. */
        }
    }

    rememberDetails('mrl-rd-admin-details', 'mrl.team.adminMenu');
    rememberDetails('mrl-rd-previous-years-details', 'mrl.team.previousYears');

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

    var pickSuccessTimer = null;
    var pickSuccessRemoveTimer = null;

    function fadePickSuccess(node) {
        if (!node) return;

        if (pickSuccessTimer) {
            window.clearTimeout(pickSuccessTimer);
        }
        if (pickSuccessRemoveTimer) {
            window.clearTimeout(pickSuccessRemoveTimer);
        }

        node.classList.remove('mrl-pick-success-hide');

        pickSuccessTimer = window.setTimeout(function () {
            node.classList.add('mrl-pick-success-hide');

            pickSuccessRemoveTimer = window.setTimeout(function () {
                if (node.parentNode) {
                    node.parentNode.removeChild(node);
                }
            }, 850);
        }, 8000);
    }

    function showPickSuccess(form) {
        var oldNode = document.getElementById('mrl-pick-success');
        if (oldNode && oldNode.parentNode) {
            oldNode.parentNode.removeChild(oldNode);
        }

        var node = document.createElement('div');
        node.className = 'mrl-pick-success';
        node.id = 'mrl-pick-success';
        node.setAttribute('role', 'status');
        node.setAttribute('aria-live', 'polite');
        node.textContent = '✓ Your picks have been submitted.';

        form.insertAdjacentElement('afterend', node);
        fadePickSuccess(node);
    }

    function refreshQuietSection(parsedDocument, selector) {
        var currentNode = document.querySelector(selector);
        var returnedNode = parsedDocument.querySelector(selector);

        if (!currentNode || !returnedNode) {
            return;
        }

        currentNode.innerHTML = returnedNode.innerHTML;
    }

    var initialPickSuccess = document.getElementById('mrl-pick-success');
    if (initialPickSuccess) {
        fadePickSuccess(initialPickSuccess);
    }

    if (window.fetch && window.FormData && window.DOMParser) {
        document.addEventListener('submit', function (event) {
            var form = event.target;

            if (!form || String(form.tagName).toLowerCase() !== 'form') {
                return;
            }

            var actionValue = form.getAttribute('action') || '';
            var actionUrl;

            try {
                actionUrl = new URL(actionValue, window.location.href);
            } catch (e) {
                return;
            }

            if (!/\/submit-team-picks\.php$/i.test(actionUrl.pathname)) {
                return;
            }

            event.preventDefault();

            if (form.dataset.mrlSubmitting === '1') {
                return;
            }

            form.dataset.mrlSubmitting = '1';

            var submitControls = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            Array.prototype.forEach.call(submitControls, function (control) {
                control.disabled = true;
            });

            window.fetch(actionUrl.href, {
                method: (form.getAttribute('method') || 'post').toUpperCase(),
                body: new FormData(form),
                credentials: 'same-origin',
                redirect: 'follow',
                headers: {
                    'X-MRL-Quiet-Submit': '1'
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Submission request failed with HTTP ' + response.status + '.');
                }

                return response.text();
            })
            .then(function (html) {
                var parsed = new DOMParser().parseFromString(html, 'text/html');
                var confirmed = parsed.getElementById('mrl-pick-success');

                if (!confirmed) {
                    throw new Error('The server did not return the successful-submission marker.');
                }

                refreshQuietSection(parsed, '.mrl-user-info-panel');
                refreshQuietSection(parsed, '.mrl-rd-submission-panel');
                showPickSuccess(form);
            })
            .catch(function (error) {
                console.error('MRL quiet pick submission:', error);

                var oldError = document.getElementById('mrl-pick-submit-error');
                if (oldError && oldError.parentNode) {
                    oldError.parentNode.removeChild(oldError);
                }

                var errorNode = document.createElement('div');
                errorNode.className = 'mrl-rd-notice-panel';
                errorNode.id = 'mrl-pick-submit-error';
                errorNode.setAttribute('role', 'alert');
                errorNode.textContent = 'Pick submission could not be confirmed. Please refresh the page before trying again.';
                form.insertAdjacentElement('afterend', errorNode);
            })
            .then(function () {
                form.dataset.mrlSubmitting = '0';

                Array.prototype.forEach.call(submitControls, function (control) {
                    control.disabled = false;
                });
            });
        });
    }
}());
</script>



<script>
/* ========================================================================
 * MRL SMART PICK REVIEW v002
 *
 * This layer only pauses a pick form before the existing quiet-submit layer.
 * It never writes picks itself and never calls submit-team-picks.php directly.
 * ====================================================================== */
(function () {
    'use strict';

    var reviewBaselines = new WeakMap();

    function addReviewStyles() {
        if (document.getElementById('mrl-pick-review-style')) return;

        var style = document.createElement('style');
        style.id = 'mrl-pick-review-style';
        style.textContent =
            '.mrl-pick-review-panel{' +
                'box-sizing:border-box;width:100%;margin:12px 0 16px;padding:14px 18px;' +
                'border:1px solid rgba(93,185,111,.72);border-radius:12px;' +
                'background:rgba(16,55,32,.82);color:#f3f3f3;' +
                'font:16px/1.45 Tahoma,Verdana,Segoe UI,sans-serif;' +
                'box-shadow:0 7px 18px rgba(0,0,0,.18);' +
            '}' +
            '.mrl-pick-review-panel h3{' +
                'margin:0 0 10px;color:#ffe2a0;font:800 19px/1.25 Tahoma,Verdana,Segoe UI,sans-serif;' +
            '}' +
            '.mrl-pick-review-label{' +
                'margin-top:8px;color:#ffe2a0;font-weight:800;' +
            '}' +
            '.mrl-pick-review-panel ul{margin:5px 0 10px;padding-left:24px;}' +
            '.mrl-pick-review-panel li{margin:3px 0;}' +
            '.mrl-pick-review-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;}' +
            '.mrl-pick-review-actions button{' +
                'min-height:38px;padding:8px 14px;border-radius:7px;font-weight:800;cursor:pointer;' +
            '}' +
            '.mrl-pick-review-confirm{background:#16894b;color:#fff;border:1px solid #4be388;}' +
            '.mrl-pick-review-back,.mrl-pick-review-close{' +
                'background:#2b2b2b;color:#eee;border:1px solid #777;' +
            '}' +
            'html.mrl-theme-light .mrl-pick-review-panel{' +
                'background:rgba(237,250,241,.96);color:#202020;border-color:#4f9464;' +
            '}' +
            'html.mrl-theme-light .mrl-pick-review-panel h3,' +
            'html.mrl-theme-light .mrl-pick-review-label{color:#5f4300;}';

        document.head.appendChild(style);
    }

    function isPickSubmitForm(form) {
        if (!form || String(form.tagName).toLowerCase() !== 'form') return false;

        try {
            var url = new URL(form.getAttribute('action') || '', window.location.href);
            return /\/submit-team-picks\.php$/i.test(url.pathname);
        } catch (e) {
            return false;
        }
    }

    function driverMap(form) {
        var map = {};

        ['A', 'B', 'C', 'D'].forEach(function (group) {
            var field = form.querySelector(
                '[name="group-' + group.toLowerCase() + '-driver"]'
            );

            map[group] = field ? String(field.value || '').trim() : '';
        });

        return map;
    }

    function rememberBaseline(form) {
        if (!reviewBaselines.has(form)) {
            reviewBaselines.set(form, driverMap(form));
        }
    }

    function removeReview(form) {
        if (!form || !form.parentNode) return;

        var next = form.nextElementSibling;
        if (next && next.classList && next.classList.contains('mrl-pick-review-panel')) {
            next.parentNode.removeChild(next);
        }
    }

    function makeList(panel, label, rows, formatter) {
        if (!rows.length) return;

        var heading = document.createElement('div');
        heading.className = 'mrl-pick-review-label';
        heading.textContent = label;
        panel.appendChild(heading);

        var list = document.createElement('ul');

        rows.forEach(function (row) {
            var item = document.createElement('li');
            item.textContent = formatter(row);
            list.appendChild(item);
        });

        panel.appendChild(list);
    }

    function showReview(form, baseline, current) {
        removeReview(form);

        var newPicks = [];
        var changes = [];
        var unchanged = [];

        ['A', 'B', 'C', 'D'].forEach(function (group) {
            var before = String(baseline[group] || '').trim();
            var after = String(current[group] || '').trim();

            if (before === after) {
                if (after !== '') {
                    unchanged.push({
                        group: group,
                        driver: after
                    });
                }
                return;
            }

            if (before === '' && after !== '') {
                newPicks.push({
                    group: group,
                    driver: after
                });
                return;
            }

            changes.push({
                group: group,
                before: before || '(none)',
                after: after || '(none)'
            });
        });

        var panel = document.createElement('div');
        panel.className = 'mrl-pick-review-panel';
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-live', 'polite');

        var title = document.createElement('h3');
        title.textContent = 'Review your submission';
        panel.appendChild(title);

        if (newPicks.length === 0 && changes.length === 0) {
            var noChange = document.createElement('div');
            noChange.textContent =
                'No changes detected. Your current picks are already saved.';
            panel.appendChild(noChange);

            var closeActions = document.createElement('div');
            closeActions.className = 'mrl-pick-review-actions';

            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'mrl-pick-review-close';
            close.textContent = 'Close';
            close.addEventListener('click', function () {
                removeReview(form);
            });

            closeActions.appendChild(close);
            panel.appendChild(closeActions);

            form.insertAdjacentElement('afterend', panel);
            panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});

            return;
        }

        makeList(panel, 'New picks', newPicks, function (row) {
            return 'Group ' + row.group + ': ' + row.driver;
        });

        makeList(panel, 'Changes', changes, function (row) {
            return 'Group ' + row.group + ': ' + row.before + ' → ' + row.after;
        });

        makeList(panel, 'Unchanged', unchanged, function (row) {
            return 'Group ' + row.group + ': ' + row.driver;
        });

        var actions = document.createElement('div');
        actions.className = 'mrl-pick-review-actions';

        var confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'mrl-pick-review-confirm';
        confirm.textContent = 'Confirm Submission';

        var back = document.createElement('button');
        back.type = 'button';
        back.className = 'mrl-pick-review-back';
        back.textContent = 'Go Back';

        confirm.addEventListener('click', function () {
            /*
             * Allow exactly one subsequent submit event through THIS review
             * layer. The already-existing document quiet-submit listener then
             * handles the real background submission and success detection.
             */
            form.dataset.mrlPickReviewBypass = '1';
            removeReview(form);

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                /*
                 * Fallback for an unusually old browser. submit() bypasses
                 * submit-event listeners, so use the native submit button click
                 * when possible to preserve the existing quiet-submit path.
                 */
                var submitButton = form.querySelector(
                    'button[type="submit"], input[type="submit"]'
                );
                if (submitButton) {
                    submitButton.click();
                } else {
                    form.dataset.mrlPickReviewBypass = '0';
                }
            }
        });

        back.addEventListener('click', function () {
            removeReview(form);
        });

        actions.appendChild(confirm);
        actions.appendChild(back);
        panel.appendChild(actions);

        form.insertAdjacentElement('afterend', panel);
        panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }

    function attachReview(form) {
        if (!isPickSubmitForm(form)) return;
        if (form.dataset.mrlPickReviewAttached === '1') return;

        form.dataset.mrlPickReviewAttached = '1';
        rememberBaseline(form);

        /*
         * A listener directly on the form runs before the existing document
         * bubble listener used by quiet-submit.
         *
         * On RD forms, the existing inline rdPrepareSubmit() remains intact
         * and prepares the canonical group fields used below.
         */
        form.addEventListener('submit', function (event) {
            if (form.dataset.mrlPickReviewBypass === '1') {
                form.dataset.mrlPickReviewBypass = '0';
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            var baseline = reviewBaselines.get(form) || {
                A: '', B: '', C: '', D: ''
            };

            var current = driverMap(form);
            showReview(form, baseline, current);
        });
    }

    function attachAll() {
        addReviewStyles();

        Array.prototype.forEach.call(
            document.querySelectorAll('form'),
            attachReview
        );
    }

    attachAll();
}());
</script>

<script>
function teampageResizeCustomHtmlFrame(frame) {
    if (!frame) return;

    function measure() {
        try {
            var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
            if (!doc) return;

            var body = doc.body;
            var root = doc.documentElement;

            if (body) body.style.overflow = 'hidden';
            if (root) root.style.overflow = 'hidden';

            var height = 0;

            if (body) {
                height = Math.max(
                    height,
                    body.scrollHeight || 0,
                    body.offsetHeight || 0
                );
            }

            if (root) {
                height = Math.max(
                    height,
                    root.scrollHeight || 0,
                    root.offsetHeight || 0
                );
            }

            frame.style.height = Math.max(120, height + 4) + 'px';
        } catch (e) {
            // Leave current size alone if the frame cannot be measured.
        }
    }

    measure();

    // One small delayed pass handles fonts/layout settling after initial load.
    window.setTimeout(measure, 80);
}
</script>
</body>
</html>
