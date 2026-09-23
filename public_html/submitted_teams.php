<?php
/**
 * submitted_teams.php
 *
 * VERSION: v003
 * LAST MODIFIED: 9/23/2026 3:31:15 am ET
 *
 * DESCRIPTION:
 * Lists teams that have submitted picks for the active year/segment.
 *
 * v003 (9/23/2026 3:31:15 am ET)
 * - SAFETY: Submitted-team output explicitly excludes test accounts userID 0, 998, and 999 plus MRL test-team rows.
 * - PARTICIPATION: Already-submitted legitimate picks remain visible even if that regular user later becomes userActive='N'.
 * - EXPECTATION: The Missing Picks list requires users.userActive='Y', so inactive/dropout users are no longer expected to submit future picks.
 * - PRESERVE: LP markers/footnotes, counts, segment comparison, and display formatting unchanged.
 *
 * CHANGELOG:
 * v002 (9/9/2026 3:11:07 am ET)
 * - NEW: Adds a compact explanatory LP footnote below the submitted-team list.
 * - FIX: Known R28 canonical short-name value "World" resolves through richer trusted schedule fields to "World Wide Tech".
 * - PRESERVE: LP marker remains on team name only in the submitted list.
 *
 * v001 (9/9/2026 2:44:18 am ET)
 * - NEW: Adds standard MRL file/version header.
 * - CONSISTENCY: Late Pick submissions append " *" to the team name only.
 * - PRESERVE: Existing timestamps, counts, missing-team list, and test-team exclusions.
 */
session_start();
include "config.php"; // Database connection info
include "config_mrl.php"; // setup variables for current MRL season & segment
require_once __DIR__ . '/race_results/race_schedule_helper.php';
$currentTimeIs = date("n/j/Y g:i a"); //get date in format '8/25/2020 12:20 am';
?>

<title><?php echo 'Submitted Teams'; ?></title>
<style>
    body {
        font-size: 14pt;
        line-height: 140%;
        font-family: 'Century Gothic', sans-serif;
        color: #dfcca8;
        background-color: #222222;
        background:#222222;
        padding-top: 0px;
        padding-bottom: 20px;
        padding-left: 20px;
    }
</style>

<?php
//
// list of Teams submitted for current year & current segment
// AND `userID` != '0' 

function st_short_race_name(string $year, int $raceNumber): string
{
    if ($raceNumber <= 0) return '';

    try {
        $race = mrl_schedule_helper_race_by_number((int)$year, $raceNumber);
        if (!is_array($race)) return '';

        $preferred = trim((string)(
            $race['mrl_race_name']
            ?? $race['race_name']
            ?? $race['track_name']
            ?? ''
        ));

        $clean = static function (string $name): string {
            $name = str_replace('_', ' ', trim($name));
            $name = preg_replace('/\s+/', ' ', $name);
            $name = preg_replace('/^NASCAR\s+Cup\s+Series\s+at\s+/i', '', (string)$name);
            return trim((string)$name);
        };

        $preferred = $clean($preferred);
        if ($preferred === '') return '';

        if (strcasecmp($preferred, 'World') === 0) {
            foreach (['race_name', 'track_name', 'display_name', 'name'] as $field) {
                $candidate = $clean((string)($race[$field] ?? ''));
                if (
                    stripos($candidate, 'World Wide Technology') !== false
                    || stripos($candidate, 'World Wide Tech') !== false
                ) {
                    return 'World Wide Tech';
                }
            }
        }

        if (
            stripos($preferred, 'World Wide Technology') !== false
            || stripos($preferred, 'World Wide Tech') !== false
        ) {
            return 'World Wide Tech';
        }

        return $preferred;
    } catch (Throwable $e) {
        return '';
    }
}

$sql_submitted = "SELECT up.* FROM `user_picks` up WHERE up.`raceYear` = '$raceYear' AND up.`userID` NOT IN (0, 998, 999) AND up.`segment` = '$segment' AND LOWER(TRIM(up.teamName)) <> 'mrl test team' ORDER BY up.`entryDate` ASC";

echo "Teams submitted for $raceYear $segment :<br><br>";
$result_submitted = mysqli_query($dbconnect, $sql_submitted);
$lpFootnotes = [];

while ($row = mysqli_fetch_assoc($result_submitted)) {
    $teamDisplay = (string)($row['teamName'] ?? '');
    $pickType = strtoupper(trim((string)($row['pick_type'] ?? '')));

    if ($pickType === 'LP') {
        $teamDisplay .= ' *';

        $effectiveRace = (int)($row['effective_race'] ?? 0);
        $raceLabel = $effectiveRace > 0
            ? ('R' . str_pad((string)$effectiveRace, 2, '0', STR_PAD_LEFT))
            : '';

        if ($effectiveRace > 0) {
            $raceName = st_short_race_name((string)$raceYear, $effectiveRace);
            if ($raceName !== '') {
                $raceLabel .= ' (' . $raceName . ')';
            }
        }

        $note = '* ' . (string)($row['teamName'] ?? '') . ' — Late Pick';
        if ($raceLabel !== '') {
            $note .= ' — Effective ' . $raceLabel;
        }
        $lpFootnotes[] = $note;
    }

    echo "{$row['entryDate']} : {$teamDisplay}<br>";
}

if (!empty($lpFootnotes)) {
    echo "<div style='margin-top:14px; font-family:Arial,sans-serif; font-size:12px; line-height:1.35; color:#dfcca8;'>";
    foreach ($lpFootnotes as $note) {
        echo htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . "<br>";
    }
    echo "</div>";
}

// Count of Teams submitted for current year & current segment
echo "<br>As of $currentTimeIs, " . mysqli_num_rows($result_submitted) . " teams have submitted their picks for $raceYear $segmentName ";

// list of Teams not yet submitted for current year & current segment
if ($segment != 'S1') {
    echo "<br><br><br>Missing picks from the following teams for $raceYear $segment:<br><br>";
    $notSubmitted = "SELECT DISTINCT up_prev.teamName FROM `user_picks` up_prev INNER JOIN `users` u_prev ON u_prev.userID = up_prev.userID WHERE up_prev.`raceYear` = '$raceYear' AND up_prev.`segment` = '$compareSegment' AND up_prev.`userID` NOT IN (0, 998, 999) AND COALESCE(u_prev.userActive, 'N') = 'Y' AND LOWER(TRIM(up_prev.teamName)) <> 'mrl test team' AND up_prev.teamName NOT IN ( SELECT up_cur.teamName FROM `user_picks` up_cur WHERE up_cur.`raceYear` = '$raceYear' AND up_cur.`segment` = '$segment' AND up_cur.`userID` NOT IN (0, 998, 999) AND LOWER(TRIM(up_cur.teamName)) <> 'mrl test team' ) ORDER BY up_prev.teamName ASC";
    $result_notSubmitted = mysqli_query($dbconnect, $notSubmitted);
    while ($row = mysqli_fetch_assoc($result_notSubmitted)) {
        echo "{$row['teamName']}<br>";
    }
}
?>