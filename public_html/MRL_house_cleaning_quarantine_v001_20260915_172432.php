<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * House Cleaning Quarantine Utility
 *
 * VERSION: v001
 * CREATED: 9/15/2026 5:24:32 pm EDT
 *
 * PURPOSE
 * -------
 * Move the user-approved housekeeping files OUT of their active locations
 * into one quarantined folder while preserving their original relative paths.
 *
 * IMPORTANT
 * ---------
 * - This utility DOES NOT permanently delete approved files.
 * - Files are moved with rename() on the same filesystem.
 * - Original file mtimes are verified before and after the move.
 * - A rollback manifest is written inside the quarantine folder.
 * - Rollback restores files to their original locations.
 * - Two explicitly approved KEEP files are never moved:
 *     mrl2_sandbox_site/default.php.bak
 *     race_results/2026/_weekly_standings_release_history_previous.json
 * - Database backups and active backup helpers are untouched.
 * - Probable generated installers not explicitly approved remain untouched.
 *
 * APPROVED SET
 * ------------
 * Built from:
 *   MRL_house_cleaning_inventory_v003_20260915_171534.json
 *
 * Includes:
 * - All CONFIRMED CLEANUP items from that scan.
 * - All REVIEW items except the two explicit KEEP files above.
 * - Root fix_*.php items that were 273 days old or older in that scan.
 *
 * No database writes.
 */

date_default_timezone_set('America/New_York');

const MRL_CLEANUP_VERSION = 'v001';
const MRL_QUARANTINE_DIR = 'To_Be_Deleted_20260915_172432';
const MRL_STATE_FILE = '_mrl_quarantine_manifest.json';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) {
    $root = __DIR__;
}
$realRoot = realpath($root);
if ($realRoot !== false) {
    $root = $realRoot;
}

$approved = [
    "_migration_backups/admin_backup_manager_v002_20260903_011125pm/admin_db_backup.php" => ['size' => 52228, 'mtime' => 1788456172, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/admin_db_backup_manager_20260829_060458/admin_db_backup.php" => ['size' => 33567, 'mtime' => 1787997898, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/admin_db_backup_v16_20260829_064918/admin_db_backup.php" => ['size' => 50778, 'mtime' => 1788000558, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/admin_db_backup_v17_20260829_070510/admin_db_backup.php" => ['size' => 53485, 'mtime' => 1788001510, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/current_user_team_chart_no_picks_contrast_20260830_093758am/current_user_team_chart.php" => ['size' => 16699, 'mtime' => 1788097311, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/fix_admin_backup_files_zip_reopen_v001_20260903_013300pm/admin_backup_files_helper.php" => ['size' => 20171, 'mtime' => 1788457631, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/logout_migration_20260826_072606/logout.php" => ['size' => 245, 'mtime' => 1787743566, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260831_161138.json" => ['size' => 13412, 'mtime' => 1788207098, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260831_163117.json" => ['size' => 1439, 'mtime' => 1788208277, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260831_170945.json" => ['size' => 2676, 'mtime' => 1788210585, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260901_015208.json" => ['size' => 3329, 'mtime' => 1788241928, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260902_121129.json" => ['size' => 2069, 'mtime' => 1788365489, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260902_122121.json" => ['size' => 2704, 'mtime' => 1788366081, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260905_124115.json" => ['size' => 2056, 'mtime' => 1788626475, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260907_030019.json" => ['size' => 1421, 'mtime' => 1788764419, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260907_030028.json" => ['size' => 167, 'mtime' => 1788764428, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/mrl_test_team_reset/MRL_test_reset_backup_2026_20260909_060840.json" => ['size' => 2705, 'mtime' => 1788948520, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_dashboard_v001_20260906_024247am/cron_all_schedulers.php" => ['size' => 5462, 'mtime' => 1788677953, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_dashboard_v003_20260906_033416am/class.user.php" => ['size' => 3790, 'mtime' => 1788680849, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_dashboard_v003_20260906_033416am/pick_reminder_dashboard.php" => ['size' => 13630, 'mtime' => 1788680849, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_dashboard_v003_20260906_033416am/pick_reminder_helper.php" => ['size' => 11120, 'mtime' => 1788680849, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_dashboard_v003_20260906_033416am/pick_reminder_scheduler.php" => ['size' => 2743, 'mtime' => 1788680849, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_dashboard_v004_20260906_035958am/pick_reminder_dashboard.php" => ['size' => 15699, 'mtime' => 1788681965, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_deadline_fix_v002_20260906_030604am/pick_reminder_dashboard.php" => ['size' => 13506, 'mtime' => 1788678611, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_deadline_fix_v002_20260906_030604am/pick_reminder_helper.php" => ['size' => 10519, 'mtime' => 1788678611, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_master_task_v005_20260906_042652am/schedule.json" => ['size' => 3319, 'mtime' => 1788683615, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_v006_20260906_044742am/pick_reminder_dashboard.php" => ['size' => 18079, 'mtime' => 1788685049, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/pick_reminder_v006_20260906_044742am/schedule.json" => ['size' => 3813, 'mtime' => 1788685049, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/profile_redesign_v003_20260827_214539/profile_redesign.php" => ['size' => 6800, 'mtime' => 1787881539, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/admin_setup.php" => ['size' => 8334, 'mtime' => 1766435539, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/config_mrl.php" => ['size' => 3980, 'mtime' => 1769712518, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/current_segment_chart.php" => ['size' => 2028, 'mtime' => 1765659309, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/current_segment_chart_by_entry_time.php" => ['size' => 1832, 'mtime' => 1765659314, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/current_user_team_chart.php" => ['size' => 6468, 'mtime' => 1765271964, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/manifest.json" => ['size' => 9587, 'mtime' => 1787726593, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/prior_year_user_team_chart.php" => ['size' => 3684, 'mtime' => 1787366271, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/submitted_teams_count.php" => ['size' => 2162, 'mtime' => 1769439293, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/team-late-pick.php" => ['size' => 1653, 'mtime' => 1765866153, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/team.php" => ['size' => 13927, 'mtime' => 1770539190, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/root_migration_20260826_024313/team_chart.php" => ['size' => 24265, 'mtime' => 1770509093, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_auto_height/manifest.json" => ['size' => 237, 'mtime' => 1788202348, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_auto_height/team.php" => ['size' => 86131, 'mtime' => 1788202348, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_block/admin_team_page_content.php" => ['size' => 13456, 'mtime' => 1788199160, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_block/manifest.json" => ['size' => 542, 'mtime' => 1788199160, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_block/mrl_team_page_content.json" => ['size' => 6961, 'mtime' => 1788199160, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_block/team.php" => ['size' => 83798, 'mtime' => 1788199160, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_handshake_height/manifest.json" => ['size' => 242, 'mtime' => 1788203630, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_handshake_height/team.php" => ['size' => 86131, 'mtime' => 1788203630, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_save_forbidden_fix/admin_team_page_content.php" => ['size' => 16315, 'mtime' => 1788201081, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_custom_html_save_forbidden_fix/manifest.json" => ['size' => 244, 'mtime' => 1788201081, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_debug_cleanup_20260826_034452/team.php" => ['size' => 53270, 'mtime' => 1787730292, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_header_refinement_20260828_154924/team.php" => ['size' => 74712, 'mtime' => 1787946564, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_175114/mrl_team_page_content.json" => ['size' => 2145, 'mtime' => 1787867474, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_175148/mrl_team_page_content.json" => ['size' => 2353, 'mtime' => 1787867508, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_175210/mrl_team_page_content.json" => ['size' => 2354, 'mtime' => 1787867530, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_175216/mrl_team_page_content.json" => ['size' => 2145, 'mtime' => 1787867536, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_185136/mrl_team_page_content.json" => ['size' => 4905, 'mtime' => 1787871096, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_185339/mrl_team_page_content.json" => ['size' => 4708, 'mtime' => 1787871219, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_185451/mrl_team_page_content.json" => ['size' => 4722, 'mtime' => 1787871291, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_193231/mrl_team_page_content.json" => ['size' => 4722, 'mtime' => 1787873551, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_200037/mrl_team_page_content.json" => ['size' => 5154, 'mtime' => 1787875237, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_200531/mrl_team_page_content.json" => ['size' => 5377, 'mtime' => 1787875531, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_200935/mrl_team_page_content.json" => ['size' => 5392, 'mtime' => 1787875775, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_201010/mrl_team_page_content.json" => ['size' => 5170, 'mtime' => 1787875810, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_205346/mrl_team_page_content.json" => ['size' => 5169, 'mtime' => 1787878426, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_225854/mrl_team_page_content.json" => ['size' => 5175, 'mtime' => 1787885934, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260827_232744/mrl_team_page_content.json" => ['size' => 5354, 'mtime' => 1787887664, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_142913/mrl_team_page_content.json" => ['size' => 5430, 'mtime' => 1787941753, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_143147/mrl_team_page_content.json" => ['size' => 5451, 'mtime' => 1787941907, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_144134/mrl_team_page_content.json" => ['size' => 5706, 'mtime' => 1787942494, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_144633/mrl_team_page_content.json" => ['size' => 5706, 'mtime' => 1787942793, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_144710/mrl_team_page_content.json" => ['size' => 5717, 'mtime' => 1787942830, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_152137/mrl_team_page_content.json" => ['size' => 5856, 'mtime' => 1787944897, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_152152/mrl_team_page_content.json" => ['size' => 5866, 'mtime' => 1787944912, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_152302/mrl_team_page_content.json" => ['size' => 5855, 'mtime' => 1787944982, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_152539/mrl_team_page_content.json" => ['size' => 5913, 'mtime' => 1787945139, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_155347/mrl_team_page_content.json" => ['size' => 5913, 'mtime' => 1787946827, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_222346/mrl_team_page_content.json" => ['size' => 5936, 'mtime' => 1787970226, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_222909/mrl_team_page_content.json" => ['size' => 5938, 'mtime' => 1787970549, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260828_222921/mrl_team_page_content.json" => ['size' => 5939, 'mtime' => 1787970561, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260829_104902/mrl_team_page_content.json" => ['size' => 5938, 'mtime' => 1788014942, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260829_105040/mrl_team_page_content.json" => ['size' => 6171, 'mtime' => 1788015040, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260829_105507/mrl_team_page_content.json" => ['size' => 6441, 'mtime' => 1788015307, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260830_095633/mrl_team_page_content.json" => ['size' => 6670, 'mtime' => 1788098193, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260830_153629/mrl_team_page_content.json" => ['size' => 6671, 'mtime' => 1788118589, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260830_153654/mrl_team_page_content.json" => ['size' => 6671, 'mtime' => 1788118614, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260830_153749/mrl_team_page_content.json" => ['size' => 6671, 'mtime' => 1788118669, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260830_163257/mrl_team_page_content.json" => ['size' => 6671, 'mtime' => 1788121977, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_143142/mrl_team_page_content.json" => ['size' => 6961, 'mtime' => 1788201102, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_143206/mrl_team_page_content.json" => ['size' => 15069, 'mtime' => 1788201126, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_144037/mrl_team_page_content.json" => ['size' => 15068, 'mtime' => 1788201637, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_145829/mrl_team_page_content.json" => ['size' => 15289, 'mtime' => 1788202709, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_151456/mrl_team_page_content.json" => ['size' => 15290, 'mtime' => 1788203696, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_151530/mrl_team_page_content.json" => ['size' => 15920, 'mtime' => 1788203730, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_161546/mrl_team_page_content.json" => ['size' => 15920, 'mtime' => 1788207346, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_165140/mrl_team_page_content.json" => ['size' => 16146, 'mtime' => 1788209500, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_165736/mrl_team_page_content.json" => ['size' => 16378, 'mtime' => 1788209856, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260831_225414/mrl_team_page_content.json" => ['size' => 16378, 'mtime' => 1788231254, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260903_003936/mrl_team_page_content.json" => ['size' => 16563, 'mtime' => 1788410376, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260903_154439/mrl_team_page_content.json" => ['size' => 16772, 'mtime' => 1788464679, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260903_160614/mrl_team_page_content.json" => ['size' => 16797, 'mtime' => 1788465974, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260903_160851/mrl_team_page_content.json" => ['size' => 16950, 'mtime' => 1788466131, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260903_225604/mrl_team_page_content.json" => ['size' => 16986, 'mtime' => 1788490564, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260903_230008/mrl_team_page_content.json" => ['size' => 16998, 'mtime' => 1788490808, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260903_230510/mrl_team_page_content.json" => ['size' => 17107, 'mtime' => 1788491110, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260905_124039/mrl_team_page_content.json" => ['size' => 17117, 'mtime' => 1788626439, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260906_052701/mrl_team_page_content.json" => ['size' => 17107, 'mtime' => 1788686821, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260906_172606/mrl_team_page_content.json" => ['size' => 17338, 'mtime' => 1788729966, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260906_172633/mrl_team_page_content.json" => ['size' => 17339, 'mtime' => 1788729993, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260906_172654/mrl_team_page_content.json" => ['size' => 17338, 'mtime' => 1788730014, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260906_173126/mrl_team_page_content.json" => ['size' => 17339, 'mtime' => 1788730286, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260906_173129/mrl_team_page_content.json" => ['size' => 18131, 'mtime' => 1788730289, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260907_005722/mrl_team_page_content.json" => ['size' => 18130, 'mtime' => 1788757042, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260910_153448/mrl_team_page_content.json" => ['size' => 18131, 'mtime' => 1789068888, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260910_153604/mrl_team_page_content.json" => ['size' => 18359, 'mtime' => 1789068964, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260911_042135/mrl_team_page_content.json" => ['size' => 18360, 'mtime' => 1789114895, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260911_042138/mrl_team_page_content.json" => ['size' => 18360, 'mtime' => 1789114898, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260913_074839/mrl_team_page_content.json" => ['size' => 18360, 'mtime' => 1789300119, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260913_145630/mrl_team_page_content.json" => ['size' => 18527, 'mtime' => 1789325790, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_20260913_145703/mrl_team_page_content.json" => ['size' => 18804, 'mtime' => 1789325823, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_content_drag_drop_20260830_032221pm/admin_team_page_content.php" => ['size' => 11795, 'mtime' => 1788118483, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_convenience_20260828_152003/admin_team_page_content.php" => ['size' => 9701, 'mtime' => 1787944803, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_convenience_20260828_152003/mrl_team_page_content.json" => ['size' => 5741, 'mtime' => 1787944803, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_page_convenience_20260828_152003/team.php" => ['size' => 69884, 'mtime' => 1787944803, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_profile_production_20260827_222215/profile.php" => ['size' => 4864, 'mtime' => 1787883735, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_profile_production_20260827_222215/team.php" => ['size' => 53270, 'mtime' => 1787883735, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_quiet_pick_submit_20260831_021440am/team.php" => ['size' => 78341, 'mtime' => 1788157753, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_20260827_153838/team_redesign.php" => ['size' => 65675, 'mtime' => 1787859518, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_20260827_161040/team_redesign.php" => ['size' => 72062, 'mtime' => 1787861440, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_20260827_170138/team_redesign.php" => ['size' => 77160, 'mtime' => 1787864498, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v005_20260827_174353/team_redesign.php" => ['size' => 78386, 'mtime' => 1787867033, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v006_20260827_184142/admin_team_page_content.php" => ['size' => 7870, 'mtime' => 1787870502, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v006_20260827_184142/mrl_team_page_content.json" => ['size' => 2145, 'mtime' => 1787870502, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v006_20260827_184142/mrl_theme_helper.php" => ['size' => 1674, 'mtime' => 1787870502, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v006_20260827_184142/profile_redesign.php" => ['size' => 6494, 'mtime' => 1787870502, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v006_20260827_184142/team_redesign.php" => ['size' => 81529, 'mtime' => 1787870502, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v007_20260827_190421/admin_team_page_content.php" => ['size' => 7591, 'mtime' => 1787871861, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v007_20260827_190421/mrl_team_page_content.json" => ['size' => 4722, 'mtime' => 1787871861, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v007_20260827_190421/team_redesign.php" => ['size' => 82785, 'mtime' => 1787871861, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v008_20260827_192848/team_redesign.php" => ['size' => 82942, 'mtime' => 1787873328, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v009_20260827_195347/team_redesign.php" => ['size' => 82199, 'mtime' => 1787874827, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v010_20260827_203833/team_redesign.php" => ['size' => 67838, 'mtime' => 1787877513, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_redesign_v011_20260827_204958/team_redesign.php" => ['size' => 69015, 'mtime' => 1787878198, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_smart_pick_review_layout/manifest.json" => ['size' => 243, 'mtime' => 1788205342, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_smart_pick_review_layout/team.php" => ['size' => 98264, 'mtime' => 1788205342, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_smart_pick_review_layout_v002/manifest.json" => ['size' => 243, 'mtime' => 1788205950, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_smart_pick_review_layout_v002/team.php" => ['size' => 99024, 'mtime' => 1788205950, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_smart_pick_review_postsubmit_baseline_v001/manifest.json" => ['size' => 256, 'mtime' => 1788209000, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_smart_pick_review_postsubmit_baseline_v001/team.php" => ['size' => 100119, 'mtime' => 1788209000, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_smart_pick_review_v002/manifest.json" => ['size' => 236, 'mtime' => 1788204472, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_smart_pick_review_v002/team.php" => ['size' => 87767, 'mtime' => 1788204472, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/team_view_as_mrl_default_v001_20260906_011832am/team_view_as.php" => ['size' => 8068, 'mtime' => 1788672761, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/userid_0_to_999_application_safeguards_20260830_075129am/email-inactive.php" => ['size' => 385, 'mtime' => 1765271964, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/userid_0_to_999_application_safeguards_20260830_075129am/email.php" => ['size' => 10464, 'mtime' => 1774556817, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/userid_0_to_999_application_safeguards_20260830_075129am/Paid_Status.php" => ['size' => 5180, 'mtime' => 1766030571, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/userid_0_to_999_application_safeguards_20260830_075129am/Paid_Status_Year.php" => ['size' => 5117, 'mtime' => 1766032002, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/userid_0_to_999_application_safeguards_20260830_075129am/select_year_team_segment.php" => ['size' => 7595, 'mtime' => 1765866153, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/userid_0_to_999_application_safeguards_20260830_075129am/submitted_teams.php" => ['size' => 1908, 'mtime' => 1770508578, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/userid_0_to_999_application_safeguards_20260830_075129am/submitted_teams_count.php" => ['size' => 2488, 'mtime' => 1787623318, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_migration_backups/userid_0_to_999_application_safeguards_20260830_075129am/team_view_as.php" => ['size' => 8052, 'mtime' => 1774891671, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved migration backup tree"],
    "_mrl_installer_backups/_race_results_schedule.json.pre_shared_theme.20260909_040809.bak" => ['size' => 74733, 'mtime' => 1788941289, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/current_segment_chart.php.v007.20260909_024418.bak" => ['size' => 11902, 'mtime' => 1788936258, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/current_segment_chart.php.v008.20260909_031107.bak" => ['size' => 13690, 'mtime' => 1788937867, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/current_user_team_chart.php.v006.20260909_040809.bak" => ['size' => 16967, 'mtime' => 1788941289, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/race_results_classify_revisions.php.pre_v012_20260913_170921.bak" => ['size' => 85137, 'mtime' => 1789334151, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/race_results_monitor.php.pre_v142_20260915_122413.bak" => ['size' => 72036, 'mtime' => 1789489922, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/race_results_monitor.php.v139.20260909_040809.bak" => ['size' => 71092, 'mtime' => 1788941289, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/submit-team-picks.php.v012.20260907_024943.bak" => ['size' => 28312, 'mtime' => 1788763783, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/submit-team-picks_v013_lp_et_fix_manifest.json" => ['size' => 573, 'mtime' => 1788763783, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/submitted_teams.php.UNVERSIONED.20260909_024418.bak" => ['size' => 1926, 'mtime' => 1788936258, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/submitted_teams.php.v001.20260909_031107.bak" => ['size' => 2527, 'mtime' => 1788937867, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/team.php.pre_v051_20260915_130116.bak" => ['size' => 102564, 'mtime' => 1789492115, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/team.php.pre_v052_20260915_131323.bak" => ['size' => 105164, 'mtime' => 1789492614, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/team_chart.php.v018.20260909_024418.bak" => ['size' => 40777, 'mtime' => 1788936258, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/team_chart.php.v019.20260909_031107.bak" => ['size' => 47721, 'mtime' => 1788937867, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/team_chart.php.v020.20260909_040809.bak" => ['size' => 52979, 'mtime' => 1788941289, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/team_chart_lp_consistency_v003_manifest.json" => ['size' => 1494, 'mtime' => 1788936258, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/team_chart_polish_v001_manifest.json" => ['size' => 1480, 'mtime' => 1788937867, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/team_chart_shared_theme_v001_manifest.json" => ['size' => 2460, 'mtime' => 1788941289, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "_mrl_installer_backups/weekly_standings.php.pre_v072_20260913_204941.bak" => ['size' => 195663, 'mtime' => 1789347792, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Approved installer backup tree"],
    "admin_backup_files_helper.php.v002.before_24hr_filename_20260907_141106.bak" => ['size' => 18944, 'mtime' => 1788804666, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "fix_combined2.php" => ['size' => 9500, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_info.php" => ['size' => 1393, 'mtime' => 1765271964, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_merged.php" => ['size' => 9633, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_merged2.php" => ['size' => 10589, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_merged3.php" => ['size' => 10609, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_part1.php" => ['size' => 8865, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_part2.php" => ['size' => 2438, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_picks.php" => ['size' => 11802, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_picks_fetch.php" => ['size' => 5926, 'mtime' => 1765271966, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_team_picks.php" => ['size' => 8352, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_team_picks3.php" => ['size' => 10711, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_team_picks_2.php" => ['size' => 0, 'mtime' => 1765271966, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "fix_team_picks_test.php" => ['size' => 2428, 'mtime' => 1765866153, 'source_status' => "LIKELY CLEANUP", 'category' => "Legacy fix / migration utility"],
    "MRL_delete_testphp8_utility_v001_20260907_121829pm.php" => ['size' => 11044, 'mtime' => 1788797959, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_house_cleaning_inventory_v001_20260915_042651pm.php" => ['size' => 19856, 'mtime' => 1789504230, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_admin_backup_files_helper_v003_24hr_filename_v001_20260907_135256.php" => ['size' => 11722, 'mtime' => 1788804123, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_admin_backup_files_helper_v003_24hr_filename_v002_20260907_140527.php" => ['size' => 11271, 'mtime' => 1788804609, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_canonical_short_race_labels_v001_20260913_063443.php" => ['size' => 32959, 'mtime' => 1789295928, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_mrl_segment_race_effective_fix_v001_20260913_163646.php" => ['size' => 23508, 'mtime' => 1789332038, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_race_monitor_signature_fix_v001_20260915_122413.php" => ['size' => 11181, 'mtime' => 1789489544, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_revision_classifier_race_effective_fix_v001_20260913_170921.php" => ['size' => 20802, 'mtime' => 1789334000, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_scheduler_canonical_race_labels_v001_20260913_065524.php" => ['size' => 20325, 'mtime' => 1789297032, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_scheduler_canonical_race_labels_v002_20260913_070021.php" => ['size' => 18675, 'mtime' => 1789297531, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_submit-team-picks_v013_LP_ET_fix_v001_20260907_022847am.php" => ['size' => 27547, 'mtime' => 1788763260, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_submit-team-picks_v013_LP_ET_fix_v002.php" => ['size' => 27039, 'mtime' => 1788763675, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_chart_LP_consistency_v001_20260909_022106am.php" => ['size' => 45884, 'mtime' => 1788935541, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_chart_LP_consistency_v002_20260909_023648am.php" => ['size' => 45572, 'mtime' => 1788935899, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_chart_LP_consistency_v003_20260909_024033am.php" => ['size' => 46212, 'mtime' => 1788936160, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_chart_polish_v001_20260909_030640am.php" => ['size' => 45991, 'mtime' => 1788937763, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_chart_shared_theme_v001_20260909_040104am.php" => ['size' => 47282, 'mtime' => 1788941164, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_php_v051_cleanup_v001_20260915_130116.php" => ['size' => 25498, 'mtime' => 1789492019, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_php_v052_manage_pill_reverse_v001_20260915_131323.php" => ['size' => 17352, 'mtime' => 1789492474, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_user_menu_header_filter_test_v001_20260913_082222.php" => ['size' => 11135, 'mtime' => 1789302211, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_user_menu_portal_v001_20260913_074748.php" => ['size' => 14888, 'mtime' => 1789300189, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_user_menu_restore_v001_20260913_075716.php" => ['size' => 14109, 'mtime' => 1789301117, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_user_menu_restore_v002_20260913_080713.php" => ['size' => 13663, 'mtime' => 1789301314, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_team_user_menu_stacking_v001_20260913_073642.php" => ['size' => 11181, 'mtime' => 1789299519, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_weekly_competitive_roster_fix_v001_20260913_153557.php" => ['size' => 20969, 'mtime' => 1789328362, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_weekly_competitive_roster_fix_v002_20260913_154452.php" => ['size' => 10331, 'mtime' => 1789328745, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_install_weekly_LP_userid_fix_v001_20260913_204941.php" => ['size' => 17669, 'mtime' => 1789347603, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_rebuild_R27_companions_and_classification_v001_20260913_170921.php" => ['size' => 20665, 'mtime' => 1789334697, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_scoring_edge_case_diagnostic_v001_20260913_151642.php" => ['size' => 15854, 'mtime' => 1789327367, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "MRL_scoring_edge_case_diagnostic_v002_20260913_153000.php" => ['size' => 16008, 'mtime' => 1789327660, 'source_status' => "CONFIRMED CLEANUP", 'category' => "Known generated artifact"],
    "race_results/2026/R18_NASCAR_Cup_Series_at_Sonoma_202606280008/under_review.flag.stale_backup_20260712_133327" => ['size' => 0, 'mtime' => 1782689044, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/_race_results_monitor_state.json.bak_20260913_063443" => ['size' => 6883, 'mtime' => 1789296088, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/_race_results_schedule.json.bak_20260913_063443" => ['size' => 74488, 'mtime' => 1789296088, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/_scheduler/state.json.bak_20260913_070633" => ['size' => 16177, 'mtime' => 1789297593, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/cron_master_scheduler.php.bak_20260913_070633" => ['size' => 73382, 'mtime' => 1789297593, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_at_a_glance.php.bak_20260707_050648" => ['size' => 17541, 'mtime' => 1783415208, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images.html.bak_20260723_022915" => ['size' => 13297, 'mtime' => 1784788155, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images.html.bak_20260723_062216" => ['size' => 11888, 'mtime' => 1784802136, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images.html.bak_20260723_080340" => ['size' => 14208, 'mtime' => 1784808220, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images.html.bak_20260723_154253" => ['size' => 10771, 'mtime' => 1784835773, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images_backup_20260818_121429am.html" => ['size' => 11225, 'mtime' => 1787027033, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images_data.php.bak_20260723_022915" => ['size' => 5983, 'mtime' => 1784788155, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images_data.php.bak_20260723_062216" => ['size' => 11260, 'mtime' => 1784802136, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images_data.php.bak_20260723_080340" => ['size' => 3191, 'mtime' => 1784808220, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/mrl_wp_images_data.php.bak_20260723_154253" => ['size' => 3973, 'mtime' => 1784835773, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/race_results_classify_revisions.php.bak_20260712_133327" => ['size' => 82148, 'mtime' => 1783877607, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/race_results_menu_backup_20260809_014049.php" => ['size' => 24998, 'mtime' => 1786254049, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/race_results_monitor.php.bak_20260719_142948" => ['size' => 67602, 'mtime' => 1784485788, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/race_results_monitor.php.bak_20260913_063443" => ['size' => 71961, 'mtime' => 1789296088, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/race_results_revision_monitor.php.bak_20260719_142948" => ['size' => 45994, 'mtime' => 1784485788, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/race_results_snapshot_helper.php.bak_20260712_133327" => ['size' => 7364, 'mtime' => 1783877607, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/race_results_snapshot_views_helper.php.pre_v002_20260913_163646.bak" => ['size' => 18181, 'mtime' => 1789332778, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/race_schedule_helper.php.bak_20260913_063443" => ['size' => 12183, 'mtime' => 1789296088, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/save/race_results_monitor.php.bak_20260705_005200" => ['size' => 67200, 'mtime' => 1783227120, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/save/race_results_revision_monitor.php.bak_20260705_005200" => ['size' => 45862, 'mtime' => 1783227120, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/save/weekly_standings.php.bak_20260705_005200" => ['size' => 174984, 'mtime' => 1783227120, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings.php.bak_20260722_121351" => ['size' => 176973, 'mtime' => 1784736831, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings.php.bak_20260722_123416" => ['size' => 178415, 'mtime' => 1784738056, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings.php.bak_20260722_130156" => ['size' => 183123, 'mtime' => 1784739716, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings.php.bak_20260722_133329" => ['size' => 186981, 'mtime' => 1784741609, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings.php.bak_20260913_063443" => ['size' => 191939, 'mtime' => 1789296088, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings.php.pre_v070_20260913_153557.bak" => ['size' => 190865, 'mtime' => 1789328538, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings.php.pre_v071_20260913_154452.bak" => ['size' => 194947, 'mtime' => 1789328788, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings_bak_20260722_121351.php" => ['size' => 176973, 'mtime' => 1784736831, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "race_results/weekly_standings_release_history_helper.php.bak_20260712_133327" => ['size' => 33707, 'mtime' => 1783877607, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "save/race_results_monitor.php.bak_20260705_005200" => ['size' => 67200, 'mtime' => 1783227120, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "save/race_results_revision_monitor.php.bak_20260705_005200" => ['size' => 45862, 'mtime' => 1783227120, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "save/weekly_standings.php.bak_20260705_005200" => ['size' => 174984, 'mtime' => 1783227120, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "submit-team-picks.php.backup_20260830_234804" => ['size' => 28312, 'mtime' => 1788148084, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "team.php.backup_20260830_234804" => ['size' => 77650, 'mtime' => 1788148084, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "team.php.bak_20260913_073937" => ['size' => 101091, 'mtime' => 1789299577, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "team.php.bak_20260913_075142" => ['size' => 101467, 'mtime' => 1789300302, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "team.php.bak_20260913_081125" => ['size' => 103727, 'mtime' => 1789301485, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
    "team.php.bak_20260913_082420" => ['size' => 102154, 'mtime' => 1789302260, 'source_status' => "REVIEW", 'category' => "Loose backup / saved copy"],
];

$keepers = [
    "mrl2_sandbox_site/default.php.bak" => true,
    "race_results/2026/_weekly_standings_release_history_previous.json" => true,
];

function mrl_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function mrl_fmt_bytes(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    $units = ['KB', 'MB', 'GB', 'TB'];
    $v = (float)$bytes;
    foreach ($units as $u) {
        $v /= 1024;
        if ($v < 1024 || $u === 'TB') {
            return number_format($v, $v >= 100 ? 0 : ($v >= 10 ? 1 : 2)) . ' ' . $u;
        }
    }
    return $bytes . ' B';
}

function mrl_full_path(string $root, string $relative): string
{
    return rtrim($root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function mrl_normalize_relative(string $path): string
{
    return ltrim(str_replace('\\', '/', $path), '/');
}

function mrl_is_within_root(string $root, string $fullPath): bool
{
    $rootNorm = str_replace('\\', '/', rtrim($root, '/\\')) . '/';
    $pathNorm = str_replace('\\', '/', $fullPath);
    return strpos($pathNorm, $rootNorm) === 0;
}

function mrl_ensure_dir(string $dir): bool
{
    if (is_dir($dir)) return true;
    return @mkdir($dir, 0755, true) || is_dir($dir);
}

function mrl_collect_tree_files(string $root, string $relativeDir): array
{
    $base = mrl_full_path($root, $relativeDir);
    if (!is_dir($base)) {
        return [];
    }

    $result = [];
    $stack = [$base];

    while ($stack) {
        $dir = array_pop($stack);
        $entries = @scandir($dir);
        if ($entries === false) {
            continue;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;

            $full = $dir . DIRECTORY_SEPARATOR . $entry;

            if (is_link($full)) {
                $result[] = '__SYMLINK__:' . $full;
                continue;
            }

            if (is_dir($full)) {
                $stack[] = $full;
                continue;
            }

            if (is_file($full)) {
                $rootNorm = str_replace('\\', '/', rtrim($root, '/\\')) . '/';
                $fullNorm = str_replace('\\', '/', $full);
                $result[] = substr($fullNorm, strlen($rootNorm));
            }
        }
    }

    sort($result, SORT_NATURAL | SORT_FLAG_CASE);
    return $result;
}

function mrl_preflight(string $root, array $approved, array $keepers): array
{
    $rows = [];
    $blocking = 0;
    $approvedBytes = 0;
    $present = 0;

    $quarantine = mrl_full_path($root, MRL_QUARANTINE_DIR);
    $stateFile = $quarantine . DIRECTORY_SEPARATOR . MRL_STATE_FILE;

    $rows[] = [
        'check' => 'Production root',
        'status' => is_dir($root) ? 'PASS' : 'FAIL',
        'detail' => $root,
    ];
    if (!is_dir($root)) $blocking++;

    if (is_file($stateFile)) {
        $rows[] = [
            'check' => 'Existing quarantine state',
            'status' => 'INFO',
            'detail' => 'A previous move state exists. Use Rollback or verify the current state before another move.',
        ];
    } elseif (is_dir($quarantine)) {
        $entries = @scandir($quarantine);
        $nonDot = is_array($entries) ? array_values(array_diff($entries, ['.', '..'])) : ['unknown'];
        if ($nonDot) {
            $rows[] = [
                'check' => 'Quarantine destination',
                'status' => 'FAIL',
                'detail' => MRL_QUARANTINE_DIR . ' already exists and is not empty.',
            ];
            $blocking++;
        } else {
            $rows[] = [
                'check' => 'Quarantine destination',
                'status' => 'PASS',
                'detail' => MRL_QUARANTINE_DIR . ' exists and is empty.',
            ];
        }
    } else {
        $rows[] = [
            'check' => 'Quarantine destination',
            'status' => 'PASS',
            'detail' => MRL_QUARANTINE_DIR . ' does not yet exist.',
        ];
    }

    foreach ($keepers as $relative => $true) {
        $full = mrl_full_path($root, $relative);
        if (is_file($full)) {
            $rows[] = [
                'check' => 'KEEP protection',
                'status' => 'PASS',
                'detail' => $relative . ' is present and excluded.',
            ];
        } else {
            $rows[] = [
                'check' => 'KEEP protection',
                'status' => 'INFO',
                'detail' => $relative . ' is currently absent; it is still excluded from this utility.',
            ];
        }
    }

    foreach ($approved as $relative => $meta) {
        $full = mrl_full_path($root, $relative);

        if (!mrl_is_within_root($root, $full)) {
            $rows[] = [
                'check' => 'Approved path safety',
                'status' => 'FAIL',
                'detail' => $relative . ' resolves outside public_html.',
            ];
            $blocking++;
            continue;
        }

        if (!is_file($full)) {
            $rows[] = [
                'check' => 'Approved file baseline',
                'status' => 'FAIL',
                'detail' => $relative . ' is missing.',
            ];
            $blocking++;
            continue;
        }

        $size = @filesize($full);
        $mtime = @filemtime($full);
        $size = $size === false ? -1 : (int)$size;
        $mtime = $mtime === false ? -1 : (int)$mtime;

        if ($size !== (int)$meta['size'] || $mtime !== (int)$meta['mtime']) {
            $rows[] = [
                'check' => 'Approved file baseline',
                'status' => 'FAIL',
                'detail' => $relative . ' changed since the approved scan'
                    . ' (expected ' . (int)$meta['size'] . ' bytes / mtime ' . (int)$meta['mtime']
                    . '; found ' . $size . ' / ' . $mtime . ').',
            ];
            $blocking++;
            continue;
        }

        $present++;
        $approvedBytes += $size;
    }

    $rows[] = [
        'check' => 'Approved manifest',
        'status' => $blocking === 0 ? 'PASS' : 'FAIL',
        'detail' => number_format($present) . ' of ' . number_format(count($approved))
            . ' approved files match the exact scan baseline; '
            . mrl_fmt_bytes($approvedBytes) . '.',
    ];

    // Exact-tree check for the two fully approved backup trees.
    foreach (['_mrl_installer_backups', '_migration_backups'] as $tree) {
        $actual = mrl_collect_tree_files($root, $tree);
        $expected = [];

        foreach ($approved as $relative => $meta) {
            if (strpos($relative, $tree . '/') === 0) {
                $expected[] = $relative;
            }
        }

        sort($expected, SORT_NATURAL | SORT_FLAG_CASE);

        $unexpected = array_values(array_diff($actual, $expected));
        $missing = array_values(array_diff($expected, $actual));

        if ($unexpected || $missing) {
            $detail = $tree . ': exact tree mismatch.';
            if ($unexpected) {
                $detail .= ' Unexpected: ' . implode(', ', array_slice($unexpected, 0, 5));
                if (count($unexpected) > 5) $detail .= ' …';
            }
            if ($missing) {
                $detail .= ' Missing: ' . implode(', ', array_slice($missing, 0, 5));
                if (count($missing) > 5) $detail .= ' …';
            }

            $rows[] = [
                'check' => 'Approved backup tree exactness',
                'status' => 'FAIL',
                'detail' => $detail,
            ];
            $blocking++;
        } else {
            $rows[] = [
                'check' => 'Approved backup tree exactness',
                'status' => 'PASS',
                'detail' => $tree . ' exactly matches the approved scan (' . count($expected) . ' files).',
            ];
        }
    }

    return [
        'ok' => ($blocking === 0),
        'blocking' => $blocking,
        'rows' => $rows,
        'approved_count' => count($approved),
        'approved_bytes' => array_sum(array_map(static function ($m) { return (int)$m['size']; }, $approved)),
    ];
}

function mrl_remove_empty_source_dirs(string $root, array $movedPaths): array
{
    $dirs = [];

    foreach ($movedPaths as $relative) {
        $relative = mrl_normalize_relative($relative);
        $dir = dirname($relative);

        while ($dir !== '.' && $dir !== '' && $dir !== DIRECTORY_SEPARATOR) {
            $dirs[$dir] = true;
            $parent = dirname($dir);
            if ($parent === $dir) break;
            $dir = $parent;
        }
    }

    $dirs = array_keys($dirs);
    usort($dirs, static function (string $a, string $b): int {
        $da = substr_count(str_replace('\\', '/', $a), '/');
        $db = substr_count(str_replace('\\', '/', $b), '/');
        if ($da !== $db) return $db <=> $da;
        return strlen($b) <=> strlen($a);
    });

    $removed = [];

    foreach ($dirs as $relativeDir) {
        $full = mrl_full_path($root, $relativeDir);

        if (!is_dir($full)) continue;

        $entries = @scandir($full);
        if (!is_array($entries)) continue;

        $nonDot = array_values(array_diff($entries, ['.', '..']));
        if (!$nonDot && @rmdir($full)) {
            $removed[] = $relativeDir;
        }
    }

    return $removed;
}

function mrl_move_to_quarantine(string $root, array $approved, array $keepers): array
{
    $pre = mrl_preflight($root, $approved, $keepers);
    if (!$pre['ok']) {
        return [
            'ok' => false,
            'message' => 'Move blocked: preflight is not green.',
            'preflight' => $pre,
            'moved' => [],
            'removed_dirs' => [],
            'errors' => [],
        ];
    }

    $quarantine = mrl_full_path($root, MRL_QUARANTINE_DIR);
    if (!mrl_ensure_dir($quarantine)) {
        return [
            'ok' => false,
            'message' => 'Could not create quarantine folder.',
            'preflight' => $pre,
            'moved' => [],
            'removed_dirs' => [],
            'errors' => [MRL_QUARANTINE_DIR],
        ];
    }

    $moved = [];
    $errors = [];

    foreach ($approved as $relative => $meta) {
        if (isset($keepers[$relative])) {
            $errors[] = 'Safety stop: approved manifest unexpectedly contains protected keeper ' . $relative;
            break;
        }

        $source = mrl_full_path($root, $relative);
        $dest = $quarantine . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

        $destDir = dirname($dest);
        if (!mrl_ensure_dir($destDir)) {
            $errors[] = 'Could not create destination directory for ' . $relative;
            break;
        }

        if (file_exists($dest)) {
            $errors[] = 'Destination already exists: ' . $relative;
            break;
        }

        if (!@rename($source, $dest)) {
            $errors[] = 'rename() failed for ' . $relative;
            break;
        }

        clearstatcache(true, $dest);
        $size = @filesize($dest);
        $mtime = @filemtime($dest);
        $size = $size === false ? -1 : (int)$size;
        $mtime = $mtime === false ? -1 : (int)$mtime;

        if ($size !== (int)$meta['size'] || $mtime !== (int)$meta['mtime']) {
            $errors[] = 'Post-move metadata verification failed for ' . $relative;
            $moved[] = $relative;
            break;
        }

        $moved[] = $relative;
    }

    if ($errors) {
        // Automatic rollback of anything already moved in this failed attempt.
        $rollbackErrors = [];

        foreach (array_reverse($moved) as $relative) {
            $source = $quarantine . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $dest = mrl_full_path($root, $relative);

            if (!mrl_ensure_dir(dirname($dest))) {
                $rollbackErrors[] = 'Could not recreate directory for ' . $relative;
                continue;
            }

            if (!@rename($source, $dest)) {
                $rollbackErrors[] = 'Could not restore ' . $relative;
            }
        }

        return [
            'ok' => false,
            'message' => $rollbackErrors
                ? 'Move failed and automatic rollback was incomplete.'
                : 'Move failed; all files moved in this attempt were automatically restored.',
            'preflight' => $pre,
            'moved' => [],
            'removed_dirs' => [],
            'errors' => array_merge($errors, $rollbackErrors),
        ];
    }

    $removedDirs = mrl_remove_empty_source_dirs($root, $moved);

    $state = [
        'tool' => 'MRL House Cleaning Quarantine Utility',
        'version' => MRL_CLEANUP_VERSION,
        'created_at' => date(DATE_ATOM),
        'root' => $root,
        'quarantine_dir' => MRL_QUARANTINE_DIR,
        'approved_source_scan' => 'MRL_house_cleaning_inventory_v003_20260915_171534.json',
        'moved_count' => count($moved),
        'moved_bytes' => $pre['approved_bytes'],
        'moved_paths' => $moved,
        'removed_empty_source_dirs' => $removedDirs,
        'keepers' => array_keys($keepers),
    ];

    $stateJson = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($stateJson === false || @file_put_contents($quarantine . DIRECTORY_SEPARATOR . MRL_STATE_FILE, $stateJson . "\n", LOCK_EX) === false) {
        return [
            'ok' => false,
            'message' => 'Files moved, but writing the rollback manifest failed. Do NOT delete the quarantine folder.',
            'preflight' => $pre,
            'moved' => $moved,
            'removed_dirs' => $removedDirs,
            'errors' => ['Could not write ' . MRL_STATE_FILE],
        ];
    }

    return [
        'ok' => true,
        'message' => 'Quarantine move completed and verified.',
        'preflight' => $pre,
        'moved' => $moved,
        'removed_dirs' => $removedDirs,
        'errors' => [],
    ];
}

function mrl_load_state(string $root): ?array
{
    $stateFile = mrl_full_path($root, MRL_QUARANTINE_DIR . '/' . MRL_STATE_FILE);
    if (!is_file($stateFile)) return null;

    $raw = @file_get_contents($stateFile);
    if ($raw === false) return null;

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function mrl_rollback(string $root, array $approved, array $keepers): array
{
    $state = mrl_load_state($root);

    if (!$state || !isset($state['moved_paths']) || !is_array($state['moved_paths'])) {
        return [
            'ok' => false,
            'message' => 'Rollback manifest not found or invalid.',
            'restored' => [],
            'errors' => [],
        ];
    }

    $quarantine = mrl_full_path($root, MRL_QUARANTINE_DIR);
    $restored = [];
    $errors = [];

    foreach ($state['moved_paths'] as $relative) {
        $relative = mrl_normalize_relative((string)$relative);

        if (!isset($approved[$relative])) {
            $errors[] = 'Rollback safety stop: state contains a path outside this approved manifest: ' . $relative;
            continue;
        }

        if (isset($keepers[$relative])) {
            $errors[] = 'Rollback safety stop: keeper appeared in moved state: ' . $relative;
            continue;
        }

        $source = $quarantine . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $dest = mrl_full_path($root, $relative);

        if (!is_file($source)) {
            $errors[] = 'Quarantined source missing: ' . $relative;
            continue;
        }

        if (file_exists($dest)) {
            $errors[] = 'Original location is no longer empty: ' . $relative;
            continue;
        }

        if (!mrl_ensure_dir(dirname($dest))) {
            $errors[] = 'Could not recreate original directory for ' . $relative;
            continue;
        }

        if (!@rename($source, $dest)) {
            $errors[] = 'Could not restore ' . $relative;
            continue;
        }

        clearstatcache(true, $dest);
        $size = @filesize($dest);
        $mtime = @filemtime($dest);
        $size = $size === false ? -1 : (int)$size;
        $mtime = $mtime === false ? -1 : (int)$mtime;

        if ($size !== (int)$approved[$relative]['size'] || $mtime !== (int)$approved[$relative]['mtime']) {
            $errors[] = 'Restored metadata verification failed: ' . $relative;
        } else {
            $restored[] = $relative;
        }
    }

    if (!$errors && count($restored) === count($state['moved_paths'])) {
        @unlink($quarantine . DIRECTORY_SEPARATOR . MRL_STATE_FILE);

        // Remove empty quarantine directories bottom-up.
        $dirs = [];
        $stack = [$quarantine];

        while ($stack) {
            $dir = array_pop($stack);
            $entries = @scandir($dir);
            if (!is_array($entries)) continue;

            $dirs[] = $dir;
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                $full = $dir . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($full) && !is_link($full)) {
                    $stack[] = $full;
                }
            }
        }

        usort($dirs, static function (string $a, string $b): int {
            return strlen($b) <=> strlen($a);
        });

        foreach ($dirs as $dir) {
            @rmdir($dir);
        }

        return [
            'ok' => true,
            'message' => 'Rollback completed. All quarantined files were restored and verified.',
            'restored' => $restored,
            'errors' => [],
        ];
    }

    return [
        'ok' => false,
        'message' => 'Rollback was incomplete. Leave the quarantine folder in place and review the errors.',
        'restored' => $restored,
        'errors' => $errors,
    ];
}

$action = (string)($_POST['action'] ?? '');
$result = null;
$preflight = mrl_preflight($root, $approved, $keepers);
$state = mrl_load_state($root);

if ($action === 'move') {
    $confirm = (string)($_POST['confirm_move'] ?? '');
    if ($confirm !== 'yes') {
        $result = [
            'ok' => false,
            'message' => 'Move not started: confirmation box was not checked.',
            'errors' => [],
        ];
    } else {
        $result = mrl_move_to_quarantine($root, $approved, $keepers);
    }

    $preflight = mrl_preflight($root, $approved, $keepers);
    $state = mrl_load_state($root);
} elseif ($action === 'rollback') {
    $result = mrl_rollback($root, $approved, $keepers);
    $preflight = mrl_preflight($root, $approved, $keepers);
    $state = mrl_load_state($root);
}

$approvedBytes = array_sum(array_map(static function ($m) { return (int)$m['size']; }, $approved));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL House Cleaning Quarantine</title>
<style>
:root{
    color-scheme:dark;
    --bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;
    --text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f;
    --green:#55db8b;--greenbg:#123c27;--blue:#78b9f3;--amber:#f0c56c;
    --amberbg:#4a3813;--red:#ff8585;--redbg:#4d2020;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1160px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.banner{padding:11px 13px;margin-bottom:11px;border:1px solid #3f8bc2;border-radius:10px;background:#15354d;color:#e8f5ff;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.card{padding:11px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:22px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
.mono{font-family:Consolas,"Courier New",monospace;overflow-wrap:anywhere}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
.status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
.pass{background:#17613a;border:1px solid #55db8b;color:#e8fff1}
.info{background:#4a3813;border:1px solid #d8aa49;color:#ffe6a7}
.fail{background:#5b2323;border:1px solid #e77a7a;color:#ffe4e4}
.actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
button{min-height:38px;padding:8px 14px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.move{background:#2f7f53}.rollback{background:#b46d22}.refresh{background:#2c6f9e}
button:disabled{background:#5a5f5d;color:#b9b9b9;cursor:not-allowed;opacity:.7}
.confirm{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid #5a6a62;border-radius:8px;background:#151a18}
.result-ok{border-color:#2f9a61;background:#103b27}
.result-bad{border-color:#a65353;background:#3d1d1d}
ul{margin:6px 0 0;padding-left:20px;line-height:1.45}
code{color:#f7d79b}
@media(max-width:850px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:520px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
<h1>MRL House Cleaning — Quarantine Move</h1>

<div class="banner">
    Nothing is permanently deleted. Approved files are moved to
    <span class="mono"><?php echo mrl_h(MRL_QUARANTINE_DIR); ?>/</span>
    with their original folder structure preserved.
</div>

<div class="panel">
<h2>Approved Move</h2>
<div class="grid">
    <div class="card"><span class="small">Approved files</span><span class="value"><?php echo number_format(count($approved)); ?></span></div>
    <div class="card"><span class="small">Approved size</span><span class="value"><?php echo mrl_h(mrl_fmt_bytes($approvedBytes)); ?></span></div>
    <div class="card"><span class="small">Quarantine folder</span><span class="value" style="font-size:15px"><?php echo mrl_h(MRL_QUARANTINE_DIR); ?></span></div>
    <div class="card"><span class="small">Permanent deletes</span><span class="value">0</span></div>
</div>
</div>

<div class="panel">
<h2>Protected / Not Included</h2>
<ul>
    <li><code>mrl2_sandbox_site/default.php.bak</code> — KEEP.</li>
    <li><code>race_results/2026/_weekly_standings_release_history_previous.json</code> — KEEP.</li>
    <li>Active backup helpers and <code>db_backups/</code> — untouched.</li>
    <li>Newer <code>fix_*</code> utilities and probable generated installers not explicitly approved — untouched.</li>
</ul>
</div>

<?php if ($result): ?>
<div class="panel <?php echo !empty($result['ok']) ? 'result-ok' : 'result-bad'; ?>">
<h2>Result</h2>
<p><strong><?php echo !empty($result['ok']) ? 'PASS' : 'ATTENTION'; ?></strong> — <?php echo mrl_h($result['message'] ?? ''); ?></p>
<?php if (!empty($result['errors'])): ?>
<ul>
<?php foreach ($result['errors'] as $error): ?>
<li><?php echo mrl_h($error); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="panel">
<h2>Preflight / Result</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php foreach ($preflight['rows'] as $row):
    $cls = $row['status'] === 'PASS' ? 'pass' : ($row['status'] === 'FAIL' ? 'fail' : 'info');
?>
<tr>
<td><?php echo mrl_h($row['check']); ?></td>
<td><span class="status <?php echo mrl_h($cls); ?>"><?php echo mrl_h($row['status']); ?></span></td>
<td><?php echo mrl_h($row['detail']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Action</h2>

<?php if ($state): ?>
<p>
    Quarantine state is present:
    <strong><?php echo number_format((int)($state['moved_count'] ?? 0)); ?> files</strong>
    moved.
    Test the site normally. If anything is wrong, use Rollback.
    If everything is good, leave this folder for now and delete it later yourself.
</p>

<form method="post">
    <input type="hidden" name="action" value="rollback">
    <div class="actions">
        <button class="rollback" type="submit">Rollback All Quarantined Files</button>
    </div>
</form>

<?php else: ?>

<form method="post" id="moveForm">
    <input type="hidden" name="action" value="move">

    <label class="confirm">
        <input type="checkbox" name="confirm_move" value="yes" id="confirmMove">
        I reviewed the approved cleanup set and want to move it to quarantine.
    </label>

    <div class="actions" style="margin-top:10px">
        <button class="move" type="submit" id="moveButton" <?php echo $preflight['ok'] ? 'disabled' : 'disabled'; ?>>
            Move Approved Files to Quarantine
        </button>
        <button class="refresh" type="button" onclick="window.location.reload()">Refresh Preflight</button>
    </div>

    <?php if (!$preflight['ok']): ?>
    <p class="small">Move is disabled because preflight has one or more blocking FAIL results.</p>
    <?php else: ?>
    <p class="small">Preflight is green. Check the confirmation box to enable the move button.</p>
    <?php endif; ?>
</form>

<?php endif; ?>
</div>

<div class="panel small">
<strong>After the move:</strong> check the basic MRL functions you care about — team page, standings/race-results pages,
scheduler/dashboard, backup pages, and anything else you normally use. The quarantine folder is your rollback safety net.
</div>

<div class="panel small">
FILE: <?php echo mrl_h(basename(__FILE__)); ?> |
VERSION: <?php echo mrl_h(MRL_CLEANUP_VERSION); ?> |
CREATED: 9/15/2026 5:24:32 pm EDT
</div>

</div>

<script>
(function () {
    'use strict';

    var checkbox = document.getElementById('confirmMove');
    var button = document.getElementById('moveButton');
    var preflightOk = <?php echo $preflight['ok'] ? 'true' : 'false'; ?>;

    if (checkbox && button) {
        function syncButton() {
            button.disabled = !(preflightOk && checkbox.checked);
        }

        checkbox.addEventListener('change', syncButton);
        syncButton();
    }
}());
</script>
</body>
</html>
