<?php
declare(strict_types=1);

/**
 * pick_reminder_task.php
 *
 * VERSION: v001
 * LAST MODIFIED: 9/6/2026 4:26:52 am
 *
 * PURPOSE:
 * - Thin bridge task for the existing race_results/cron_master_scheduler.php.
 * - Lets the proven master scheduler call the root-level Pick Reminder scheduler
 *   without changing the Hostinger cron command or the master scheduler engine.
 *
 * FLOW:
 * Hostinger cron
 *   -> race_results/cron_master_scheduler.php
 *      -> race_results/pick_reminder_task.php
 *         -> /public_html/pick_reminder_scheduler.php
 *
 * NOTES:
 * - No email logic lives here.
 * - TEST/LIVE and AUTO/MANUAL/OFF safety remains in pick_reminder_scheduler.php.
 * - This file simply hands execution to the existing Pick Reminder scheduler.
 */

date_default_timezone_set('America/New_York');

$target = dirname(__DIR__) . '/pick_reminder_scheduler.php';

if (!is_file($target)) {
    fwrite(STDERR, "Pick Reminder task bridge: target scheduler not found: {$target}\n");
    exit(2);
}

require $target;

// pick_reminder_scheduler.php normally exits itself.
exit(0);
