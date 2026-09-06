<?php
declare(strict_types=1);

/*
 * install_pick_reminder_v006_20260906_044742am.php
 * VERSION: v001
 * LAST MODIFIED: 9/6/2026 4:47:42 am
 *
 * PURPOSE:
 * - Narrow follow-up to the current Pick Reminder iteration.
 *
 * CHANGES:
 * 1) Updates pick_reminder_dashboard.php v004 -> v005:
 *    - Adds "Use Current ET Time" for repeated AUTO TEST setup.
 *    - Uses America/New_York explicitly in browser JavaScript.
 *
 * 2) Updates ONLY the existing pick_reminder task in
 *    /race_results/_scheduler/schedule.json:
 *       run_method: "php" -> "url"
 *
 * WHY:
 * - Existing master scheduler is already successfully checking task #3.
 * - URL execution uses the same proven LiteSpeed/web execution path used by
 *   other web-oriented MRL tasks.
 *
 * PRESERVES:
 * - Hostinger cron command unchanged.
 * - race_results/cron_master_scheduler.php v014 unchanged.
 * - Existing Race Monitor task unchanged.
 * - Existing Revision Monitor task unchanged.
 * - Pick Reminder interval remains 1 minute.
 * - Pick Reminder TEST/LIVE and AUTO/MANUAL/OFF safety unchanged.
 * - No database changes.
 * - Installer itself sends no email and does not execute the reminder task.
 */

date_default_timezone_set('America/New_York');

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$raceDir = $root . '/race_results';

$dashboard = $root . '/pick_reminder_dashboard.php';
$master = $raceDir . '/cron_master_scheduler.php';
$scheduleFile = $raceDir . '/_scheduler/schedule.json';
$bridge = $raceDir . '/pick_reminder_task.php';
$pickScheduler = $root . '/pick_reminder_scheduler.php';

$backupDir = $root . '/_migration_backups/pick_reminder_v006_20260906_044742am';
$dashboardBackup = $backupDir . '/pick_reminder_dashboard.php';
$scheduleBackup = $backupDir . '/schedule.json';

$dashboardPayload = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7Ci8qKgogKiBwaWNrX3JlbWluZGVyX2Rhc2hib2FyZC5waHAKICogVkVSU0lPTjogdjAwNQogKiBMQVNUIE1PRElGSUVEOiA5LzYvMjAyNiA0OjQ3OjQyIGFtCiAqCiAqIENIQU5HRUxPRzoKICogdjAwNSAoOS82LzIwMjYgNDo0Nzo0MiBhbSkKICogLSBORVc6IEFkZHMgVXNlIEN1cnJlbnQgRVQgVGltZSBidXR0b24gZm9yIHJlcGVhdGVkIEFVVE8gVEVTVCBzZXR1cC4KICogLSBDSEFOR0U6IEJ1dHRvbiByZWZyZXNoZXMgRGF0ZS9UaW1lIGZpZWxkcyB0byB0aGUgYWN0dWFsIGN1cnJlbnQgQW1lcmljYS9OZXdfWW9yayB0aW1lLgogKiAtIFBSRVNFUlZFOiBTYXZlZCB0ZXN0IHZhbHVlcyBzdGlsbCBwZXJzaXN0IHVudGlsIHRoZSBidXR0b24gaXMgZGVsaWJlcmF0ZWx5IHVzZWQuCiAqCiAqIHYwMDQgKDkvNi8yMDI2IDM6NTk6NTggYW0pCiAqIC0gQ0hBTkdFOiBBVVRPIFRFU1QgdXNlcyBzZXBhcmF0ZSBkYXRlIGFuZCB0aW1lIGZpZWxkcyBpbnN0ZWFkIG9mIGRhdGV0aW1lLWxvY2FsLgogKiAtIE5FVzogV2hlbiBubyBzYXZlZCBBVVRPIFRFU1QgdGltZSBleGlzdHMsIGRhdGUvdGltZSBkZWZhdWx0IHRvIGN1cnJlbnQgRVQuCiAqIC0gQ0hBTkdFOiBTYXZlZCBBVVRPIFRFU1QgZGF0ZS90aW1lIHBlcnNpc3RzIGFmdGVyIHJlZnJlc2guCiAqIC0gTkVXOiBTYXZlIERhc2hib2FyZCBTZXR0aW5ncyBidXR0b24gYWRkZWQgZGlyZWN0bHkgaW4gQVVUTyBURVNUIHNlY3Rpb24uCiAqIC0gQ0hBTkdFOiBFeGlzdGluZyBlbWFpbC1zZWN0aW9uIHNhdmUgYnV0dG9uIHJlbmFtZWQgU2F2ZSBEYXNoYm9hcmQgU2V0dGluZ3MuCiAqCiAqIHYwMDMgKDkvNi8yMDI2IDM6MzQ6MTYgYW0pCiAqIC0gQ0hBTkdFOiBUZWFtLW5hbWUgcGVyc29uYWxpemVkIGRlZmF1bHQgcmVtaW5kZXIgbWVzc2FnZS4KICogLSBORVc6IE9uZS10aW1lIEFVVE8gVEVTVCB0aW1lc3RhbXAgY29udHJvbCBmb3IgTVJMIElEIDk5OS4KICogLSBDSEFOR0U6IERhc2hib2FyZCByZWZsZWN0cyBUbzogTVJMIEdtYWlsIC8gQkNDOiB0ZWFtIHByaXZhY3kgbW9kZWwuCiAqCiAqIHYwMDIgKDkvNi8yMDI2IDM6MDI6MDggYW0pCiAqIC0gRklYOiBVc2VzIGNvcnJlY3RlZCBkZWFkbGluZSBwYXJzZXIgZnJvbSBwaWNrX3JlbWluZGVyX2hlbHBlci5waHAgdjAwMi4KICovCmRhdGVfZGVmYXVsdF90aW1lem9uZV9zZXQoJ0FtZXJpY2EvTmV3X1lvcmsnKTsKaWYoc2Vzc2lvbl9zdGF0dXMoKT09PVBIUF9TRVNTSU9OX05PTkUpc2Vzc2lvbl9zdGFydCgpOwokX1NFU1NJT05bJ3JldHVybl90byddPSRfU0VSVkVSWydSRVFVRVNUX1VSSSddPz8nL3BpY2tfcmVtaW5kZXJfZGFzaGJvYXJkLnBocCc7CnJlcXVpcmVfb25jZSBfX0RJUl9fLicvY29uZmlnLnBocCc7cmVxdWlyZV9vbmNlIF9fRElSX18uJy9jb25maWdfbXJsLnBocCc7cmVxdWlyZV9vbmNlIF9fRElSX18uJy9jbGFzcy51c2VyLnBocCc7CiR1aD1uZXcgVVNFUigpO2lmKCEkdWgtPmlzX2xvZ2dlZF9pbigpKXskdWgtPnJlZGlyZWN0KCcvbG9naW4ucGhwJyk7ZXhpdDt9aWYoIWlzQWRtaW4oJF9TRVNTSU9OWyd1c2VyU2Vzc2lvbiddPz9udWxsKSl7aHR0cF9yZXNwb25zZV9jb2RlKDQwMyk7ZXhpdCgnQWRtaW4gYWNjZXNzIHJlcXVpcmVkLicpO30KZGVmaW5lKCdNUkxfUElDS19SRU1JTkRFUl9DT05URVhUJywnZGFzaGJvYXJkJyk7cmVxdWlyZV9vbmNlIF9fRElSX18uJy9waWNrX3JlbWluZGVyX2hlbHBlci5waHAnOwppZighaXNzZXQoJGRiY29ubmVjdCl8fCEoJGRiY29ubmVjdCBpbnN0YW5jZW9mIG15c3FsaSkpe2h0dHBfcmVzcG9uc2VfY29kZSg1MDApO2V4aXQoJ0RhdGFiYXNlIGNvbm5lY3Rpb24gdW5hdmFpbGFibGUuJyk7fQppZighaXNzZXQoJF9TRVNTSU9OWydtcmxwcl9jc3JmJ10pKSRfU0VTU0lPTlsnbXJscHJfY3NyZiddPWJpbjJoZXgocmFuZG9tX2J5dGVzKDI0KSk7CiRjZmc9bXJscHJfbG9hZF9jb25maWcoKTskY3R4PW1ybHByX2N1cnJlbnRfY29udGV4dCgpOyRtc2c9Jyc7JG1zZ0NsYXNzPSdpbmZvJzsKZnVuY3Rpb24gcHJkX2NzcmYoKTpib29se3JldHVybiBpc3NldCgkX1BPU1RbJ2NzcmYnXSkmJmhhc2hfZXF1YWxzKChzdHJpbmcpKCRfU0VTU0lPTlsnbXJscHJfY3NyZiddPz8nJyksKHN0cmluZykkX1BPU1RbJ2NzcmYnXSk7fQpmdW5jdGlvbiBwcmRfcG9zdF9jZmcoYXJyYXkgJGMpOmFycmF5ewogICAgJG09c3RydG91cHBlcih0cmltKChzdHJpbmcpKCRfUE9TVFsnbW9kZSddPz8nTUFOVUFMJykpKTskY1snbW9kZSddPWluX2FycmF5KCRtLFsnQVVUTycsJ01BTlVBTCcsJ09GRiddLHRydWUpPyRtOidNQU5VQUwnOwogICAgJHM9c3RydG91cHBlcih0cmltKChzdHJpbmcpKCRfUE9TVFsnc2NvcGUnXT8/J1RFU1QnKSkpOyRjWydzY29wZSddPWluX2FycmF5KCRzLFsnVEVTVCcsJ0xJVkUnXSx0cnVlKT8kczonVEVTVCc7CiAgICAkbz1bXTtmb3JlYWNoKFsnb2Zmc2V0MScsJ29mZnNldDInLCdvZmZzZXQzJ10gYXMgJGspeyRuPW1ybHByX2R1cmF0aW9uX3RvX21pbnV0ZXMoKHN0cmluZykoJF9QT1NUWyRrXT8/JycpKTtpZigkbiE9PW51bGwpJG9bXT0kbjt9JG89YXJyYXlfdmFsdWVzKGFycmF5X3VuaXF1ZSgkbykpO3Jzb3J0KCRvLFNPUlRfTlVNRVJJQyk7JGNbJ29mZnNldHNfbWludXRlcyddPSRvPzpbMTgwLDEyMCw2MF07CiAgICAkc3ViPXRyaW0oKHN0cmluZykoJF9QT1NUWydzdWJqZWN0X3RlbXBsYXRlJ10/PycnKSk7JGJvZHk9dHJpbSgoc3RyaW5nKSgkX1BPU1RbJ2JvZHlfdGVtcGxhdGUnXT8/JycpKTtpZigkc3ViIT09JycpJGNbJ3N1YmplY3RfdGVtcGxhdGUnXT0kc3ViO2lmKCRib2R5IT09JycpJGNbJ2JvZHlfdGVtcGxhdGUnXT0kYm9keTsKICAgICRjWyd0ZXN0X2F1dG9fZW5hYmxlZCddPWlzc2V0KCRfUE9TVFsndGVzdF9hdXRvX2VuYWJsZWQnXSkgJiYgKHN0cmluZykkX1BPU1RbJ3Rlc3RfYXV0b19lbmFibGVkJ109PT0nMSc7CgogICAgJHRlc3REYXRlPXRyaW0oKHN0cmluZykoJF9QT1NUWyd0ZXN0X2F1dG9fZGF0ZSddPz8nJykpOwogICAgJHRlc3RUaW1lPXRyaW0oKHN0cmluZykoJF9QT1NUWyd0ZXN0X2F1dG9fdGltZSddPz8nJykpOwoKICAgIGlmKCR0ZXN0RGF0ZT09PScnICYmICR0ZXN0VGltZT09PScnKXsKICAgICAgICAkY1sndGVzdF9hdXRvX2F0J109Jyc7CiAgICB9ZWxzZXsKICAgICAgICAkcGFyc2VkPURhdGVUaW1lOjpjcmVhdGVGcm9tRm9ybWF0KAogICAgICAgICAgICAnbS9kL1kgaDppIEEnLAogICAgICAgICAgICAkdGVzdERhdGUuJyAnLnN0cnRvdXBwZXIoJHRlc3RUaW1lKSwKICAgICAgICAgICAgbmV3IERhdGVUaW1lWm9uZSgnQW1lcmljYS9OZXdfWW9yaycpCiAgICAgICAgKTsKCiAgICAgICAgJHBhcnNlRXJyb3JzPURhdGVUaW1lOjpnZXRMYXN0RXJyb3JzKCk7CiAgICAgICAgJGhhc0Vycm9ycz1pc19hcnJheSgkcGFyc2VFcnJvcnMpCiAgICAgICAgICAgICYmICgoKGludCkoJHBhcnNlRXJyb3JzWyd3YXJuaW5nX2NvdW50J10/PzApPjApIHx8ICgoaW50KSgkcGFyc2VFcnJvcnNbJ2Vycm9yX2NvdW50J10/PzApPjApKTsKCiAgICAgICAgaWYoJHBhcnNlZCBpbnN0YW5jZW9mIERhdGVUaW1lICYmICEkaGFzRXJyb3JzKXsKICAgICAgICAgICAgJGNbJ3Rlc3RfYXV0b19hdCddPSRwYXJzZWQtPmZvcm1hdCgnWS1tLWQgSDppJyk7CiAgICAgICAgfWVsc2V7CiAgICAgICAgICAgICRjWyd0ZXN0X2F1dG9fYXQnXT0nX19JTlZBTElEX18nOwogICAgICAgIH0KICAgIH0KCiAgICByZXR1cm4gJGM7Cn0KJGFjdGlvbj0oc3RyaW5nKSgkX1BPU1RbJ2FjdGlvbiddPz8nJyk7CmlmKCRfU0VSVkVSWydSRVFVRVNUX01FVEhPRCddPT09J1BPU1QnJiYkYWN0aW9uPT09J3NhdmVfc2V0dGluZ3MnKXsKICAgIGlmKCFwcmRfY3NyZigpKXskbXNnPSdTYXZlIGJsb2NrZWQ6IHNlY3VyaXR5IHRva2VuIG1pc21hdGNoLic7JG1zZ0NsYXNzPSdiYWQnO30KICAgIGVsc2V7JG49cHJkX3Bvc3RfY2ZnKCRjZmcpO2lmKChzdHJpbmcpKCRuWyd0ZXN0X2F1dG9fYXQnXT8/JycpPT09J19fSU5WQUxJRF9fJyl7JG1zZz0nQVVUTyBURVNUIGRhdGUvdGltZSBpcyBpbnZhbGlkLiBVc2UgTU0vREQvWVlZWSBhbmQgSEg6TU0gQU0vUE0uJzskbXNnQ2xhc3M9J2JhZCc7fQogICAgZWxzZWlmKCRuWydtb2RlJ109PT0nQVVUTycmJiRuWydzY29wZSddPT09J0xJVkUnJiZ0cmltKChzdHJpbmcpKCRfUE9TVFsnbGl2ZV9hdXRvX2NvbmZpcm0nXT8/JycpKSE9PSdFTkFCTEUgTElWRSBBVVRPJyl7JG1zZz0nTElWRSBBVVRPIHdhcyBub3QgZW5hYmxlZC4gVHlwZSBFTkFCTEUgTElWRSBBVVRPIGV4YWN0bHkuJzskbXNnQ2xhc3M9J2JhZCc7fQogICAgZWxzZWlmKCFtcmxwcl9zYXZlX2NvbmZpZygkbikpeyRtc2c9J0NvdWxkIG5vdCBzYXZlIHNldHRpbmdzLic7JG1zZ0NsYXNzPSdiYWQnO31lbHNleyRjZmc9JG47JG1zZz0nU2V0dGluZ3Mgc2F2ZWQuJzskbXNnQ2xhc3M9J29rJzt9fQp9CmlmKCRfU0VSVkVSWydSRVFVRVNUX01FVEhPRCddPT09J1BPU1QnJiYkYWN0aW9uPT09J3NlbmRfbWFudWFsJyl7CiAgICBpZighcHJkX2NzcmYoKSl7JG1zZz0nU2VuZCBibG9ja2VkOiBzZWN1cml0eSB0b2tlbiBtaXNtYXRjaC4nOyRtc2dDbGFzcz0nYmFkJzt9CiAgICBlbHNleyR3b3JrPXByZF9wb3N0X2NmZygkY2ZnKTskd29ya1snbW9kZSddPSRjZmdbJ21vZGUnXTskc2NvcGU9KHN0cmluZykkd29ya1snc2NvcGUnXTskcmVjPW1ybHByX21pc3NpbmdfcmVjaXBpZW50cygkZGJjb25uZWN0LCRzY29wZSwoc3RyaW5nKSRjdHhbJ3llYXInXSwoc3RyaW5nKSRjdHhbJ3NlZ21lbnQnXSk7JGJ5PVtdO2ZvcmVhY2goJHJlYyBhcyAkcikkYnlbKGludCkkclsndXNlcklEJ11dPSRyOyRzZWw9YXJyYXlfdmFsdWVzKGFycmF5X3VuaXF1ZShhcnJheV9tYXAoJ2ludHZhbCcsKGFycmF5KSgkX1BPU1RbJ3JlY2lwaWVudF9pZHMnXT8/W10pKSkpOwogICAgICAgIGlmKCRzY29wZT09PSdMSVZFJyYmdHJpbSgoc3RyaW5nKSgkX1BPU1RbJ2xpdmVfc2VuZF9jb25maXJtJ10/PycnKSkhPT0nU0VORCBMSVZFJyl7JG1zZz0nTElWRSBzZW5kIGJsb2NrZWQuIFR5cGUgU0VORCBMSVZFIGV4YWN0bHkuJzskbXNnQ2xhc3M9J2JhZCc7fQogICAgICAgIGVsc2VpZighJHNlbCl7JG1zZz0nTm8gcmVjaXBpZW50cyBzZWxlY3RlZC4nOyRtc2dDbGFzcz0nYmFkJzt9CiAgICAgICAgZWxzZXskc2VudD0wOyRza2lwPTA7JGZhaWw9MDtmb3JlYWNoKCRzZWwgYXMgJHVpZCl7aWYoIWlzc2V0KCRieVskdWlkXSkpeyRza2lwKys7Y29udGludWU7fSR4PW1ybHByX3NlbmRfdXNlcigkZGJjb25uZWN0LCRieVskdWlkXSwkd29yaywkY3R4LCdNQU5VQUwnLG51bGwpOyRzdD0oc3RyaW5nKSgkeFsncmVzdWx0J10/PycnKTtpZigkc3Q9PT0nU0VOVCcpJHNlbnQrKztlbHNlaWYoc3RycG9zKCRzdCwnU0tJUFBFRCcpPT09MCkkc2tpcCsrO2Vsc2UkZmFpbCsrO30kbXNnPSJNYW51YWwgcmVtaW5kZXIgcnVuIGNvbXBsZXRlIOKAlCBzZW50ICRzZW50LCBza2lwcGVkICRza2lwLCBmYWlsZWQgJGZhaWwuIjskbXNnQ2xhc3M9JGZhaWw/J2JhZCc6J29rJzt9CiAgICB9Cn0KJHJlY2lwaWVudHM9bXJscHJfbWlzc2luZ19yZWNpcGllbnRzKCRkYmNvbm5lY3QsKHN0cmluZykkY2ZnWydzY29wZSddLChzdHJpbmcpJGN0eFsneWVhciddLChzdHJpbmcpJGN0eFsnc2VnbWVudCddKTskZGVhZGxpbmU9JGN0eFsnZGVhZGxpbmVfZHQnXTsKJHJjPVsneWVhcic9PiRjdHhbJ3llYXInXSwnc2VnbWVudCc9PiRjdHhbJ3NlZ21lbnQnXSwnc2VnbWVudF9uYW1lJz0+JGN0eFsnc2VnbWVudF9uYW1lJ10sJ2RlYWRsaW5lJz0+JGN0eFsnZGVhZGxpbmVfZGlzcGxheSddLCd0ZWFtX25hbWUnPT4kcmVjaXBpZW50cz8oc3RyaW5nKSgkcmVjaXBpZW50c1swXVsndGVhbU5hbWUnXT8/J1RlYW0nKTonVGVhbScsJ3RlYW1fcGFnZV91cmwnPT4kY2ZnWyd0ZWFtX3BhZ2VfdXJsJ11dOwokcHJldmlld1N1Yj1tcmxwcl9yZW5kZXJfdGVtcGxhdGUoKHN0cmluZykkY2ZnWydzdWJqZWN0X3RlbXBsYXRlJ10sJHJjLGZhbHNlKTskcHJldmlld0JvZHk9bXJscHJfcmVuZGVyX3RlbXBsYXRlKChzdHJpbmcpJGNmZ1snYm9keV90ZW1wbGF0ZSddLCRyYyxmYWxzZSk7CiRzdGF0ZT1tcmxwcl9sb2FkX3NjaGVkdWxlcl9zdGF0ZSgpOyRsb2dzPWFycmF5X3JldmVyc2UobXJscHJfcmVhZF9sb2coMzApKTsKJHByZT1bJ0FkbWluIGFjY2Vzcyc9PnRydWUsJ1BIUE1haWxlciBhdmFpbGFibGUnPT5pc19maWxlKF9fRElSX18uJy9tYWlsZXIvY2xhc3MucGhwbWFpbGVyLnBocCcpICYmIGNsYXNzX2V4aXN0cygnVVNFUicpICYmIG1ldGhvZF9leGlzdHMoJ1VTRVInLCdzZW5kX21haWwnKSwndXNlcnMgZW1haWwgY29sdW1ucyBhdmFpbGFibGUnPT5tcmxwcl90YWJsZV9oYXNfY29sdW1ucygkZGJjb25uZWN0LCd1c2VycycsWyd1c2VySUQnLCd1c2VyTmFtZScsJ3VzZXJFbWFpbCcsJ3VzZXJFbWFpbDInLCd1c2VyQWN0aXZlJ10pLCd1c2VyX3RlYW1zIGNvbHVtbnMgYXZhaWxhYmxlJz0+bXJscHJfdGFibGVfaGFzX2NvbHVtbnMoJGRiY29ubmVjdCwndXNlcl90ZWFtcycsWyd1c2VySUQnLCdyYWNlWWVhcicsJ3RlYW1OYW1lJ10pLCd1c2VyX3BpY2tzIGNvbHVtbnMgYXZhaWxhYmxlJz0+bXJscHJfdGFibGVfaGFzX2NvbHVtbnMoJGRiY29ubmVjdCwndXNlcl9waWNrcycsWyd1c2VySUQnLCdyYWNlWWVhcicsJ3NlZ21lbnQnXSksJ0RlYWRsaW5lIHBhcnNlZCc9PiRkZWFkbGluZSBpbnN0YW5jZW9mIERhdGVUaW1lLCdTdGF0ZSBmb2xkZXIgd3JpdGFibGUvY3JlYXRhYmxlJz0+bXJscHJfZW5zdXJlX3N0YXRlX2RpcigpXTskcmVhZHk9IWluX2FycmF5KGZhbHNlLCRwcmUsdHJ1ZSk7CiRvZmZzPWFycmF5X3ZhbHVlcygoYXJyYXkpJGNmZ1snb2Zmc2V0c19taW51dGVzJ10pO3doaWxlKGNvdW50KCRvZmZzKTwzKSRvZmZzW109MDsKPz48IWRvY3R5cGUgaHRtbD48aHRtbCBsYW5nPSJlbiI+PGhlYWQ+PG1ldGEgY2hhcnNldD0idXRmLTgiPjxtZXRhIG5hbWU9InZpZXdwb3J0IiBjb250ZW50PSJ3aWR0aD1kZXZpY2Utd2lkdGgsaW5pdGlhbC1zY2FsZT0xIj48dGl0bGU+TVJMIFBpY2sgUmVtaW5kZXIgRGFzaGJvYXJkPC90aXRsZT4KPHN0eWxlPgo6cm9vdHstLWJnOiMxMDEyMTQ7LS1wOiMxYjFmMjM7LS1wMjojMTUxOTFkOy0tYjojNDE0ODUwOy0tdDojZWVmMmY2Oy0tbTojYWViOGMxOy0tZzojZmZjZjgzOy0tZ3JlZW46IzU3ZTM4YzstLXJlZDojZmY3MzczOy0tYmx1ZTojOGZjOGZmfSp7Ym94LXNpemluZzpib3JkZXItYm94fWJvZHl7bWFyZ2luOjA7YmFja2dyb3VuZDp2YXIoLS1iZyk7Y29sb3I6dmFyKC0tdCk7Zm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7Zm9udC1zaXplOjE0cHh9LndyYXB7bWF4LXdpZHRoOjExODBweDttYXJnaW46MjBweCBhdXRvIDUwcHg7cGFkZGluZzowIDE2cHh9LmNhcmR7YmFja2dyb3VuZDp2YXIoLS1wKTtib3JkZXI6MXB4IHNvbGlkIHZhcigtLWIpO2JvcmRlci1yYWRpdXM6MTJweDtwYWRkaW5nOjE4cHg7bWFyZ2luLWJvdHRvbToxNHB4fWgxLGgyLGgze2NvbG9yOnZhcigtLWcpO21hcmdpbi10b3A6MH1oMXtmb250LXNpemU6MzBweDttYXJnaW4tYm90dG9tOjhweH1oMntmb250LXNpemU6MjFweH0ubXV0ZWR7Y29sb3I6dmFyKC0tbSl9Lmcye2Rpc3BsYXk6Z3JpZDtncmlkLXRlbXBsYXRlLWNvbHVtbnM6MWZyIDFmcjtnYXA6MTRweH0uZzN7ZGlzcGxheTpncmlkO2dyaWQtdGVtcGxhdGUtY29sdW1uczpyZXBlYXQoMywxZnIpO2dhcDoxMnB4fS5zdGF0e2JhY2tncm91bmQ6dmFyKC0tcDIpO2JvcmRlcjoxcHggc29saWQgIzM1M2M0Mztib3JkZXItcmFkaXVzOjlweDtwYWRkaW5nOjEycHh9LmJpZ3tmb250LXNpemU6MjRweDtmb250LXdlaWdodDo4MDB9LmJhbm5lcntwYWRkaW5nOjEycHggMTRweDtib3JkZXItcmFkaXVzOjlweDttYXJnaW46MTJweCAwO2ZvbnQtd2VpZ2h0OjcwMH0ub2t7YmFja2dyb3VuZDojMTIzYTJhO2JvcmRlcjoxcHggc29saWQgIzJiODE1Yjtjb2xvcjojZDlmZmVhfS5iYWR7YmFja2dyb3VuZDojNGExODE4O2JvcmRlcjoxcHggc29saWQgI2E2NGU0ZTtjb2xvcjojZmZkNGQ0fS5pbmZve2JhY2tncm91bmQ6IzEyMzA0NDtib3JkZXI6MXB4IHNvbGlkICMyODZhOTM7Y29sb3I6I2RiZjJmZn0ud2FybntiYWNrZ3JvdW5kOiM0YTM1MTQ7Ym9yZGVyOjFweCBzb2xpZCAjOWI2YTEyO2NvbG9yOiNmZmU4YjR9bGFiZWx7ZGlzcGxheTpibG9jazttYXJnaW46NnB4IDB9aW5wdXRbdHlwZT10ZXh0XSx0ZXh0YXJlYXt3aWR0aDoxMDAlO2JhY2tncm91bmQ6IzBmMTIxNTtjb2xvcjojZmZmO2JvcmRlcjoxcHggc29saWQgIzU5NjE2YTtib3JkZXItcmFkaXVzOjdweDtwYWRkaW5nOjlweDtmb250OmluaGVyaXR9dGV4dGFyZWF7bWluLWhlaWdodDoxMzJweH0ucmFke2Rpc3BsYXk6ZmxleDtnYXA6MThweDtmbGV4LXdyYXA6d3JhcH0ucmFkIGxhYmVse2Rpc3BsYXk6ZmxleDtnYXA6NnB4O2FsaWduLWl0ZW1zOmNlbnRlcjttYXJnaW46MH0uYnRue2JvcmRlcjowO2JvcmRlci1yYWRpdXM6N3B4O3BhZGRpbmc6MTFweCAxNnB4O2ZvbnQtd2VpZ2h0OjgwMDtjdXJzb3I6cG9pbnRlcn0uZ3JlZW57YmFja2dyb3VuZDojMjM4NjRiO2NvbG9yOiNmZmZ9LmJsdWV7YmFja2dyb3VuZDojMmY2ZmViO2NvbG9yOiNmZmZ9dGFibGV7d2lkdGg6MTAwJTtib3JkZXItY29sbGFwc2U6Y29sbGFwc2V9dGgsdGR7cGFkZGluZzo5cHggOHB4O2JvcmRlci1ib3R0b206MXB4IHNvbGlkICMzNDNiNDI7dGV4dC1hbGlnbjpsZWZ0O3ZlcnRpY2FsLWFsaWduOnRvcH10aHtjb2xvcjojZmZkMjdmO2ZvbnQtc2l6ZToxMnB4fS5wYXNze2NvbG9yOnZhcigtLWdyZWVuKTtmb250LXdlaWdodDo4MDB9LmZhaWx7Y29sb3I6dmFyKC0tcmVkKTtmb250LXdlaWdodDo4MDB9Y29kZSwubW9ub3tmb250LWZhbWlseTpDb25zb2xhcyxNZW5sbyxtb25vc3BhY2U7Y29sb3I6I2ZmZDI3Zn0ucHJldmlld3t3aGl0ZS1zcGFjZTpwcmUtd3JhcDtiYWNrZ3JvdW5kOiMwZjEyMTU7Ym9yZGVyOjFweCBzb2xpZCAjNDU0ZDU1O2JvcmRlci1yYWRpdXM6OHB4O3BhZGRpbmc6MTRweDtsaW5lLWhlaWdodDoxLjU1fS5zbWFsbHtmb250LXNpemU6MTJweDtjb2xvcjp2YXIoLS1tKX0udGVzdC1maWVsZHN7ZGlzcGxheTpncmlkO2dyaWQtdGVtcGxhdGUtY29sdW1uczoxZnIgMWZyO2dhcDoxMHB4fS5zYXZlLXJvd3ttYXJnaW4tdG9wOjE0cHh9QG1lZGlhKG1heC13aWR0aDo2MDBweCl7LnRlc3QtZmllbGRze2dyaWQtdGVtcGxhdGUtY29sdW1uczoxZnJ9fUBtZWRpYShtYXgtd2lkdGg6ODAwcHgpey5nMiwuZzN7Z3JpZC10ZW1wbGF0ZS1jb2x1bW5zOjFmcn1ib2R5e2ZvbnQtc2l6ZToxNnB4fX0KPC9zdHlsZT48L2hlYWQ+PGJvZHk+PGRpdiBjbGFzcz0id3JhcCI+CjxkaXYgY2xhc3M9ImNhcmQiPjxoMT5NUkwgUGljayBSZW1pbmRlciBEYXNoYm9hcmQ8L2gxPjxkaXYgY2xhc3M9Im11dGVkIj5WRVJTSU9OIHYwMDUgfCBBZG1pbi1vbmx5IHwgU2FmZSBkZWZhdWx0OiBNQU5VQUwgKyBURVNUIChNUkwgSUQgOTk5IG9ubHkpPC9kaXY+PD9waHAgaWYoJG1zZyE9PScnKTo/PjxkaXYgY2xhc3M9ImJhbm5lciA8P3BocCBlY2hvICRtc2dDbGFzcz09PSdvayc/J29rJzooJG1zZ0NsYXNzPT09J2JhZCc/J2JhZCc6J2luZm8nKTs/PiI+PD9waHAgZWNobyBtcmxwcl9oKCRtc2cpOz8+PC9kaXY+PD9waHAgZW5kaWY7Pz48L2Rpdj4KPGRpdiBjbGFzcz0iY2FyZCI+PGgyPkN1cnJlbnQgUGljayBXaW5kb3c8L2gyPjxkaXYgY2xhc3M9ImczIj48ZGl2IGNsYXNzPSJzdGF0Ij48ZGl2IGNsYXNzPSJtdXRlZCI+WWVhciAvIFNlZ21lbnQ8L2Rpdj48ZGl2IGNsYXNzPSJiaWciPjw/cGhwIGVjaG8gbXJscHJfaCgkY3R4Wyd5ZWFyJ10uJyAnLiRjdHhbJ3NlZ21lbnRfbmFtZSddKTs/PjwvZGl2PjwvZGl2PjxkaXYgY2xhc3M9InN0YXQiPjxkaXYgY2xhc3M9Im11dGVkIj5EZWFkbGluZTwvZGl2PjxkaXYgY2xhc3M9ImJpZyIgc3R5bGU9ImZvbnQtc2l6ZToxOHB4Ij48P3BocCBlY2hvIG1ybHByX2goJGN0eFsnZGVhZGxpbmVfZGlzcGxheSddKTs/PjwvZGl2PjwvZGl2PjxkaXYgY2xhc3M9InN0YXQiPjxkaXYgY2xhc3M9Im11dGVkIj5NaXNzaW5nIGluIGN1cnJlbnQgc2NvcGU8L2Rpdj48ZGl2IGNsYXNzPSJiaWciPjw/cGhwIGVjaG8gY291bnQoJHJlY2lwaWVudHMpOz8+PC9kaXY+PC9kaXY+PC9kaXY+PC9kaXY+CjxkaXYgY2xhc3M9ImNhcmQiPjxoMj5QcmVmbGlnaHQ8L2gyPjx0YWJsZT48dGhlYWQ+PHRyPjx0aD5DSEVDSzwvdGg+PHRoPlNUQVRVUzwvdGg+PC90cj48L3RoZWFkPjx0Ym9keT48P3BocCBmb3JlYWNoKCRwcmUgYXMgJGw9PiRvayk6Pz48dHI+PHRkPjw/cGhwIGVjaG8gbXJscHJfaCgkbCk7Pz48L3RkPjx0ZCBjbGFzcz0iPD9waHAgZWNobyAkb2s/J3Bhc3MnOidmYWlsJzs/PiI+PD9waHAgZWNobyAkb2s/J1BBU1MnOidGQUlMJzs/PjwvdGQ+PC90cj48P3BocCBlbmRmb3JlYWNoOz8+PC90Ym9keT48L3RhYmxlPjwvZGl2Pgo8Zm9ybSBtZXRob2Q9InBvc3QiPjxpbnB1dCB0eXBlPSJoaWRkZW4iIG5hbWU9ImNzcmYiIHZhbHVlPSI8P3BocCBlY2hvIG1ybHByX2goJF9TRVNTSU9OWydtcmxwcl9jc3JmJ10pOz8+Ij4KPGRpdiBjbGFzcz0iY2FyZCI+PGgyPk1vZGUgKyBTY29wZTwvaDI+PGRpdiBjbGFzcz0iZzIiPjxkaXYgY2xhc3M9InN0YXQiPjxoMz5Nb2RlPC9oMz48ZGl2IGNsYXNzPSJyYWQiPjw/cGhwIGZvcmVhY2goWydBVVRPJywnTUFOVUFMJywnT0ZGJ10gYXMgJG0pOj8+PGxhYmVsPjxpbnB1dCB0eXBlPSJyYWRpbyIgbmFtZT0ibW9kZSIgdmFsdWU9Ijw/cGhwIGVjaG8gJG07Pz4iIDw/cGhwIGVjaG8gJGNmZ1snbW9kZSddPT09JG0/J2NoZWNrZWQnOicnOz8+Pjw/cGhwIGVjaG8gJG07Pz48L2xhYmVsPjw/cGhwIGVuZGZvcmVhY2g7Pz48L2Rpdj48cCBjbGFzcz0ic21hbGwiPkFVVE8gPSBzY2hlZHVsZWQgc2VuZHMuIE1BTlVBTCA9IGJ1dHRvbiBvbmx5LiBPRkYgPSBubyBhdXRvbWF0aWMgc2VuZGluZy48L3A+PC9kaXY+PGRpdiBjbGFzcz0ic3RhdCI+PGgzPlJlY2lwaWVudCBTY29wZTwvaDM+PGRpdiBjbGFzcz0icmFkIj48bGFiZWw+PGlucHV0IHR5cGU9InJhZGlvIiBuYW1lPSJzY29wZSIgdmFsdWU9IlRFU1QiIDw/cGhwIGVjaG8gJGNmZ1snc2NvcGUnXT09PSdURVNUJz8nY2hlY2tlZCc6Jyc7Pz4+VEVTVCDigJQgTVJMIDk5OSBvbmx5PC9sYWJlbD48bGFiZWw+PGlucHV0IHR5cGU9InJhZGlvIiBuYW1lPSJzY29wZSIgdmFsdWU9IkxJVkUiIDw/cGhwIGVjaG8gJGNmZ1snc2NvcGUnXT09PSdMSVZFJz8nY2hlY2tlZCc6Jyc7Pz4+TElWRSDigJQgbWlzc2luZyBhY3RpdmUgdGVhbXM8L2xhYmVsPjwvZGl2PjxwIGNsYXNzPSJzbWFsbCI+TElWRSBleGNsdWRlcyB1c2VySUQgMCBhbmQgOTk5IGFuZCByZWNoZWNrcyB0aGUgREIgaW1tZWRpYXRlbHkgYmVmb3JlIGV2ZXJ5IHNlbmQuPC9wPjwvZGl2PjwvZGl2PjwvZGl2Pgo8P3BocAokdGVzdFNhdmVkQXQ9dHJpbSgoc3RyaW5nKSgkY2ZnWyd0ZXN0X2F1dG9fYXQnXT8/JycpKTsKJHRlc3REYXRlVmFsdWU9ZGF0ZSgnbS9kL1knKTsKJHRlc3RUaW1lVmFsdWU9ZGF0ZSgnaDppIEEnKTsKCmlmKCR0ZXN0U2F2ZWRBdCE9PScnKXsKICAgIHRyeXsKICAgICAgICAkdGVzdFNhdmVkRHQ9bmV3IERhdGVUaW1lKCR0ZXN0U2F2ZWRBdCxuZXcgRGF0ZVRpbWVab25lKCdBbWVyaWNhL05ld19Zb3JrJykpOwogICAgICAgICR0ZXN0RGF0ZVZhbHVlPSR0ZXN0U2F2ZWREdC0+Zm9ybWF0KCdtL2QvWScpOwogICAgICAgICR0ZXN0VGltZVZhbHVlPSR0ZXN0U2F2ZWREdC0+Zm9ybWF0KCdoOmkgQScpOwogICAgfWNhdGNoKFRocm93YWJsZSAkZSl7CiAgICAgICAgLy8gRmFsbCBiYWNrIHRvIGN1cnJlbnQgRVQgZGF0ZS90aW1lIGZvciBkaXNwbGF5IG9ubHkuCiAgICB9Cn0KPz4KPGRpdiBjbGFzcz0iY2FyZCI+PGgyPkFVVE8gVEVTVCDigJQgSUQgOTk5IE9ubHk8L2gyPgo8cCBjbGFzcz0ibXV0ZWQiPlVzZSB0aGlzIHRvIHByb3ZlIHRoZSBjb21wbGV0ZSBhdXRvbWF0aWMgcGF0aCBhdCBhIGNvbnZlbmllbnQgdGltZTogSG9zdGluZ2VyIGNyb24g4oaSIGNyb24gbGF1bmNoZXIg4oaSIHJlbWluZGVyIHNjaGVkdWxlciDihpIgbWlzc2luZy1waWNrIGNoZWNrIOKGkiBlbWFpbCDihpIgc2VuZCBoaXN0b3J5LjwvcD4KPGRpdiBjbGFzcz0iZzIiPgogICAgPGRpdiBjbGFzcz0ic3RhdCI+CiAgICAgICAgPGxhYmVsPjxpbnB1dCB0eXBlPSJjaGVja2JveCIgbmFtZT0idGVzdF9hdXRvX2VuYWJsZWQiIHZhbHVlPSIxIiA8P3BocCBlY2hvICFlbXB0eSgkY2ZnWyd0ZXN0X2F1dG9fZW5hYmxlZCddKT8nY2hlY2tlZCc6Jyc7ID8+PiA8Yj5Vc2Ugb25lLXRpbWUgVEVTVCBBVVRPIHRpbWVzdGFtcDwvYj48L2xhYmVsPgogICAgICAgIDxwIGNsYXNzPSJzbWFsbCI+T25seSBhcHBsaWVzIHdoZW4gPGI+QVVUTyArIFRFU1Q8L2I+IGlzIHNlbGVjdGVkLiBJdCBuZXZlciB0YXJnZXRzIExJVkUgdGVhbXMuPC9wPgogICAgPC9kaXY+CiAgICA8ZGl2IGNsYXNzPSJzdGF0Ij4KICAgICAgICA8bGFiZWw+PGI+VGVzdCBzZW5kIGRhdGUvdGltZSAoRVQpPC9iPjwvbGFiZWw+CiAgICAgICAgPGRpdiBjbGFzcz0idGVzdC1maWVsZHMiPgogICAgICAgICAgICA8ZGl2PgogICAgICAgICAgICAgICAgPGxhYmVsIGNsYXNzPSJzbWFsbCI+RGF0ZTwvbGFiZWw+CiAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9InRlc3RfYXV0b19kYXRlIiBuYW1lPSJ0ZXN0X2F1dG9fZGF0ZSIgdmFsdWU9Ijw/cGhwIGVjaG8gbXJscHJfaCgkdGVzdERhdGVWYWx1ZSk7ID8+IiBwbGFjZWhvbGRlcj0iTU0vREQvWVlZWSI+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8ZGl2PgogICAgICAgICAgICAgICAgPGxhYmVsIGNsYXNzPSJzbWFsbCI+VGltZTwvbGFiZWw+CiAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgaWQ9InRlc3RfYXV0b190aW1lIiBuYW1lPSJ0ZXN0X2F1dG9fdGltZSIgdmFsdWU9Ijw/cGhwIGVjaG8gbXJscHJfaCgkdGVzdFRpbWVWYWx1ZSk7ID8+IiBwbGFjZWhvbGRlcj0iSEg6TU0gQU0iPgogICAgICAgICAgICA8L2Rpdj4KICAgICAgICA8L2Rpdj4KICAgICAgICA8ZGl2IHN0eWxlPSJtYXJnaW4tdG9wOjEwcHgiPgogICAgICAgICAgICA8YnV0dG9uIGNsYXNzPSJidG4gYmx1ZSIgdHlwZT0iYnV0dG9uIiBpZD0idXNlX2N1cnJlbnRfZXRfdGltZSI+VXNlIEN1cnJlbnQgRVQgVGltZTwvYnV0dG9uPgogICAgICAgIDwvZGl2PgogICAgICAgIDxwIGNsYXNzPSJzbWFsbCI+V2hlbiBubyB0ZXN0IGhhcyBiZWVuIHNhdmVkLCB0aGVzZSBzdGFydCBhdCB0aGUgY3VycmVudCBFVCBkYXRlL3RpbWUuIEZvciBhbm90aGVyIHRlc3QsIGNsaWNrIFVzZSBDdXJyZW50IEVUIFRpbWUsIHRoZW4gdXN1YWxseSBqdXN0IGNoYW5nZSB0aGUgbWludXRlcy48L3A+CiAgICA8L2Rpdj4KPC9kaXY+Cjw/cGhwCiR0ZXN0U3RhdHVzPSdOb3Qgc2NoZWR1bGVkJzsKJHRlc3RLZXk9dHJpbSgoc3RyaW5nKSgkY2ZnWyd0ZXN0X2F1dG9fYXQnXT8/JycpKTsKaWYoIWVtcHR5KCRjZmdbJ3Rlc3RfYXV0b19lbmFibGVkJ10pICYmICR0ZXN0S2V5IT09JycpewogICAgJHRlc3RTdGF0dXM9bXJscHJfdGVzdF9hdXRvX3NlbnQoJHRlc3RLZXksTVJMX1BSX1RFU1RfVUlEKQogICAgICAgID8gJ1NFTlQg4oCUIGF1dG9tYXRpYyB0ZXN0IGNvbXBsZXRlZCcKICAgICAgICA6ICdBUk1FRCDigJQgd2FpdGluZyBmb3Igc2NoZWR1bGVyJzsKfQo/Pgo8ZGl2IGNsYXNzPSJiYW5uZXIgPD9waHAgZWNobyBzdHJwb3MoJHRlc3RTdGF0dXMsJ1NFTlQnKT09PTA/J29rJzonaW5mbyc7ID8+Ij48Yj5URVNUIEFVVE8gc3RhdHVzOjwvYj4gPD9waHAgZWNobyBtcmxwcl9oKCR0ZXN0U3RhdHVzKTsgPz48L2Rpdj4KPGRpdiBjbGFzcz0ic2F2ZS1yb3ciPgogICAgPGJ1dHRvbiBjbGFzcz0iYnRuIGJsdWUiIHR5cGU9InN1Ym1pdCIgbmFtZT0iYWN0aW9uIiB2YWx1ZT0ic2F2ZV9zZXR0aW5ncyI+U2F2ZSBEYXNoYm9hcmQgU2V0dGluZ3M8L2J1dHRvbj4KPC9kaXY+CjwvZGl2PgoKPGRpdiBjbGFzcz0iY2FyZCI+PGgyPkF1dG9tYXRpYyBSZW1pbmRlciBUaW1lczwvaDI+PHAgY2xhc3M9Im11dGVkIj5FbnRlciBIOk1NIGJlZm9yZSBkZWFkbGluZS48L3A+PGRpdiBjbGFzcz0iZzMiPjw/cGhwIGZvcigkaT0wOyRpPDM7JGkrKyk6Pz48ZGl2IGNsYXNzPSJzdGF0Ij48bGFiZWw+PGI+UmVtaW5kZXIgPD9waHAgZWNobyAkaSsxOz8+PC9iPjwvbGFiZWw+PGlucHV0IHR5cGU9InRleHQiIG5hbWU9Im9mZnNldDw/cGhwIGVjaG8gJGkrMTs/PiIgdmFsdWU9Ijw/cGhwIGVjaG8gJG9mZnNbJGldP21ybHByX2gobXJscHJfbWludXRlc190b19kdXJhdGlvbigoaW50KSRvZmZzWyRpXSkpOicnOz8+Ij48P3BocCBpZigkZGVhZGxpbmUgaW5zdGFuY2VvZiBEYXRlVGltZSYmJG9mZnNbJGldKTokc2xvdD1jbG9uZSAkZGVhZGxpbmU7JHNsb3QtPm1vZGlmeSgnLScuKGludCkkb2Zmc1skaV0uJyBtaW51dGVzJyk7Pz48ZGl2IGNsYXNzPSJzbWFsbCIgc3R5bGU9Im1hcmdpbi10b3A6N3B4Ij5Xb3VsZCBydW4gYXQgPD9waHAgZWNobyBtcmxwcl9oKCRzbG90LT5mb3JtYXQoJ2c6aSBBJykpOz8+IEVUPC9kaXY+PD9waHAgZW5kaWY7Pz48L2Rpdj48P3BocCBlbmRmb3I7Pz48L2Rpdj48ZGl2IGNsYXNzPSJiYW5uZXIgd2FybiI+QVVUTyArIExJVkUgcmVxdWlyZXMgdHlwaW5nIDxiPkVOQUJMRSBMSVZFIEFVVE88L2I+LjwvZGl2PjxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJsaXZlX2F1dG9fY29uZmlybSIgcGxhY2Vob2xkZXI9IkVOQUJMRSBMSVZFIEFVVE8iPjwvZGl2Pgo8ZGl2IGNsYXNzPSJjYXJkIj48aDI+RW1haWwgTWVzc2FnZTwvaDI+PGRpdiBjbGFzcz0iZzIiPjxkaXY+PGxhYmVsPjxiPlN1YmplY3Q8L2I+PC9sYWJlbD48aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ic3ViamVjdF90ZW1wbGF0ZSIgdmFsdWU9Ijw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSRjZmdbJ3N1YmplY3RfdGVtcGxhdGUnXSk7Pz4iPjxsYWJlbCBzdHlsZT0ibWFyZ2luLXRvcDoxMnB4Ij48Yj5NZXNzYWdlPC9iPjwvbGFiZWw+PHRleHRhcmVhIG5hbWU9ImJvZHlfdGVtcGxhdGUiPjw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSRjZmdbJ2JvZHlfdGVtcGxhdGUnXSk7Pz48L3RleHRhcmVhPjxkaXYgY2xhc3M9InNtYWxsIj5QbGFjZWhvbGRlcnM6IDxjb2RlPnt7eWVhcn19PC9jb2RlPiwgPGNvZGU+e3tzZWdtZW50X25hbWV9fTwvY29kZT4sIDxjb2RlPnt7ZGVhZGxpbmV9fTwvY29kZT4sIDxjb2RlPnt7dGVhbV9uYW1lfX08L2NvZGU+LCA8Y29kZT57e3RlYW1fcGFnZX19PC9jb2RlPi48L2Rpdj48L2Rpdj48ZGl2PjxsYWJlbD48Yj5DdXJyZW50IFByZXZpZXc8L2I+PC9sYWJlbD48ZGl2IGNsYXNzPSJwcmV2aWV3Ij48Yj48P3BocCBlY2hvIG1ybHByX2goJHByZXZpZXdTdWIpOz8+PC9iPgoKPD9waHAgZWNobyBtcmxwcl9oKCRwcmV2aWV3Qm9keSk7Pz48L2Rpdj48L2Rpdj48L2Rpdj48ZGl2IHN0eWxlPSJtYXJnaW4tdG9wOjE0cHgiPjxidXR0b24gY2xhc3M9ImJ0biBibHVlIiB0eXBlPSJzdWJtaXQiIG5hbWU9ImFjdGlvbiIgdmFsdWU9InNhdmVfc2V0dGluZ3MiPlNhdmUgRGFzaGJvYXJkIFNldHRpbmdzPC9idXR0b24+PC9kaXY+PC9kaXY+CjxkaXYgY2xhc3M9ImNhcmQiPjxoMj5DdXJyZW50IE1pc3NpbmcgUGlja3Mg4oCUIDw/cGhwIGVjaG8gJGNmZ1snc2NvcGUnXTs/PiBTY29wZTwvaDI+PHAgY2xhc3M9InNtYWxsIj5FYWNoIHRlYW0gZ2V0cyBpdHMgb3duIG1lc3NhZ2UuIFZpc2libGUgVG86IGlzIG1hbmxpdXNyYWNpbmdsZWFndWVAZ21haWwuY29tOyB0aGF0IHRlYW1cJ3MgZW1haWwgYWRkcmVzcyhlcykgYXJlIEJDQyByZWNpcGllbnRzLjwvcD48P3BocCBpZighJHJlY2lwaWVudHMpOj8+PGRpdiBjbGFzcz0iYmFubmVyIG9rIj5ObyB0ZWFtcyBpbiB0aGUgY3VycmVudCBzY29wZSBhcmUgbWlzc2luZyBwaWNrcy48L2Rpdj48P3BocCBlbHNlOj8+PHRhYmxlPjx0aGVhZD48dHI+PHRoPlNlbmQ8L3RoPjx0aD5UZWFtPC90aD48dGg+VXNlcjwvdGg+PHRoPkVtYWlsKHMpPC90aD48L3RyPjwvdGhlYWQ+PHRib2R5Pjw/cGhwIGZvcmVhY2goJHJlY2lwaWVudHMgYXMgJHIpOj8+PHRyPjx0ZD48aW5wdXQgdHlwZT0iY2hlY2tib3giIG5hbWU9InJlY2lwaWVudF9pZHNbXSIgdmFsdWU9Ijw/cGhwIGVjaG8gKGludCkkclsndXNlcklEJ107Pz4iIGNoZWNrZWQ+PC90ZD48dGQ+PD9waHAgZWNobyBtcmxwcl9oKChzdHJpbmcpKCRyWyd0ZWFtTmFtZSddPz8nJykpOz8+PC90ZD48dGQ+PD9waHAgZWNobyBtcmxwcl9oKChzdHJpbmcpKCRyWyd1c2VyTmFtZSddPz8nJykuJyAoSUQgJy4oaW50KSRyWyd1c2VySUQnXS4nKScpOz8+PC90ZD48dGQ+PD9waHAgZWNobyBtcmxwcl9oKGltcGxvZGUoJywgJywoYXJyYXkpJHJbJ2VtYWlscyddKSk7Pz48L3RkPjwvdHI+PD9waHAgZW5kZm9yZWFjaDs/PjwvdGJvZHk+PC90YWJsZT48P3BocCBpZigkY2ZnWydzY29wZSddPT09J0xJVkUnKTo/PjxkaXYgY2xhc3M9ImJhbm5lciB3YXJuIj5MSVZFIG1hbnVhbCBzZW5kIHJlcXVpcmVzIHR5cGluZyA8Yj5TRU5EIExJVkU8L2I+LjwvZGl2PjxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJsaXZlX3NlbmRfY29uZmlybSIgcGxhY2Vob2xkZXI9IlNFTkQgTElWRSI+PD9waHAgZW5kaWY7Pz48ZGl2IHN0eWxlPSJtYXJnaW4tdG9wOjE0cHgiPjxidXR0b24gY2xhc3M9ImJ0biBncmVlbiIgdHlwZT0ic3VibWl0IiBuYW1lPSJhY3Rpb24iIHZhbHVlPSJzZW5kX21hbnVhbCIgPD9waHAgZWNobyAkcmVhZHk/Jyc6J2Rpc2FibGVkJzs/PiBvbmNsaWNrPSJyZXR1cm4gY29uZmlybSgnU2VuZCByZW1pbmRlciBub3cgdG8gY2hlY2tlZCByZWNpcGllbnQocyk/Jyk7Ij5TZW5kIFNlbGVjdGVkIFJlbWluZGVyIE5vdzwvYnV0dG9uPjwvZGl2Pjw/cGhwIGVuZGlmOz8+PC9kaXY+PC9mb3JtPgo8ZGl2IGNsYXNzPSJjYXJkIj48aDI+QXV0b21hdGljIFNjaGVkdWxlciBTdGF0dXM8L2gyPjw/cGhwIGlmKCEkc3RhdGUpOj8+PGRpdiBjbGFzcz0ibXV0ZWQiPk5vIHNjaGVkdWxlciBjaGVjayByZWNvcmRlZCB5ZXQuPC9kaXY+PD9waHAgZWxzZTo/Pjx0YWJsZT48dHI+PHRoPkxhc3QgY2hlY2s8L3RoPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHN0YXRlWydjaGVja2VkX2F0J10/PycnKSk7Pz48L3RkPjwvdHI+PHRyPjx0aD5TdGF0dXM8L3RoPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHN0YXRlWydzdGF0dXMnXT8/JycpKTs/PjwvdGQ+PC90cj48dHI+PHRoPk1vZGUgLyBTY29wZTwvdGg+PHRkPjw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSgkc3RhdGVbJ21vZGUnXT8/JycpLicgLyAnLihzdHJpbmcpKCRzdGF0ZVsnc2NvcGUnXT8/JycpKTs/PjwvdGQ+PC90cj48dHI+PHRoPlN1bW1hcnk8L3RoPjx0ZD48P3BocCBlY2hvIG1ybHByX2goanNvbl9lbmNvZGUoJHN0YXRlWydzdW1tYXJ5J10/P1tdLEpTT05fVU5FU0NBUEVEX1NMQVNIRVMpKTs/PjwvdGQ+PC90cj48L3RhYmxlPjw/cGhwIGVuZGlmOz8+PC9kaXY+CjxkaXYgY2xhc3M9ImNhcmQiPjxoMj5SZWNlbnQgU2VuZCBIaXN0b3J5PC9oMj48P3BocCBpZighJGxvZ3MpOj8+PGRpdiBjbGFzcz0ibXV0ZWQiPk5vIHJlbWluZGVyIHNlbmRzIHJlY29yZGVkIHlldC48L2Rpdj48P3BocCBlbHNlOj8+PHRhYmxlPjx0aGVhZD48dHI+PHRoPlRpbWU8L3RoPjx0aD5LaW5kPC90aD48dGg+U2NvcGU8L3RoPjx0aD5UZWFtPC90aD48dGg+UmVzdWx0PC90aD48dGg+T2Zmc2V0PC90aD48L3RyPjwvdGhlYWQ+PHRib2R5Pjw/cGhwIGZvcmVhY2goJGxvZ3MgYXMgJHIpOj8+PHRyPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHJbJ3NlbnRfYXQnXT8/JycpKTs/PjwvdGQ+PHRkPjw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSgkclsnc2VuZF9raW5kJ10/PycnKSk7Pz48L3RkPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHJbJ3Njb3BlJ10/PycnKSk7Pz48L3RkPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHJbJ3RlYW1OYW1lJ10/PycnKS4nIChJRCAnLihpbnQpKCRyWyd1c2VySUQnXT8/MCkuJyknKTs/PjwvdGQ+PHRkPjw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSgkclsncmVzdWx0J10/PycnKSk7Pz48L3RkPjx0ZD48P3BocCBlY2hvIGlzc2V0KCRyWydvZmZzZXRfbWludXRlcyddKSYmJHJbJ29mZnNldF9taW51dGVzJ10hPT1udWxsP21ybHByX2gobXJscHJfbWludXRlc190b19kdXJhdGlvbigoaW50KSRyWydvZmZzZXRfbWludXRlcyddKSk6J01hbnVhbCc7Pz48L3RkPjwvdHI+PD9waHAgZW5kZm9yZWFjaDs/PjwvdGJvZHk+PC90YWJsZT48P3BocCBlbmRpZjs/PjwvZGl2Pgo8ZGl2IGNsYXNzPSJzbWFsbCIgc3R5bGU9InRleHQtYWxpZ246cmlnaHQiPnBpY2tfcmVtaW5kZXJfZGFzaGJvYXJkLnBocCB8IFZFUlNJT04gdjAwNTwvZGl2PjwvZGl2Pgo8c2NyaXB0PgooZnVuY3Rpb24oKXsKICAgICd1c2Ugc3RyaWN0JzsKCiAgICBjb25zdCBidG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgndXNlX2N1cnJlbnRfZXRfdGltZScpOwogICAgY29uc3QgZGF0ZUZpZWxkID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ3Rlc3RfYXV0b19kYXRlJyk7CiAgICBjb25zdCB0aW1lRmllbGQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgndGVzdF9hdXRvX3RpbWUnKTsKCiAgICBpZiAoIWJ0biB8fCAhZGF0ZUZpZWxkIHx8ICF0aW1lRmllbGQpIHJldHVybjsKCiAgICBidG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBmdW5jdGlvbigpewogICAgICAgIGNvbnN0IHBhcnRzID0gbmV3IEludGwuRGF0ZVRpbWVGb3JtYXQoJ2VuLVVTJywgewogICAgICAgICAgICB0aW1lWm9uZTogJ0FtZXJpY2EvTmV3X1lvcmsnLAogICAgICAgICAgICB5ZWFyOiAnbnVtZXJpYycsCiAgICAgICAgICAgIG1vbnRoOiAnMi1kaWdpdCcsCiAgICAgICAgICAgIGRheTogJzItZGlnaXQnLAogICAgICAgICAgICBob3VyOiAnMi1kaWdpdCcsCiAgICAgICAgICAgIG1pbnV0ZTogJzItZGlnaXQnLAogICAgICAgICAgICBob3VyMTI6IHRydWUKICAgICAgICB9KS5mb3JtYXRUb1BhcnRzKG5ldyBEYXRlKCkpOwoKICAgICAgICBjb25zdCB2YWx1ZXMgPSB7fTsKICAgICAgICBwYXJ0cy5mb3JFYWNoKGZ1bmN0aW9uKHApewogICAgICAgICAgICBpZiAocC50eXBlICE9PSAnbGl0ZXJhbCcpIHZhbHVlc1twLnR5cGVdID0gcC52YWx1ZTsKICAgICAgICB9KTsKCiAgICAgICAgZGF0ZUZpZWxkLnZhbHVlID0KICAgICAgICAgICAgU3RyaW5nKHZhbHVlcy5tb250aCB8fCAnJykucGFkU3RhcnQoMiwgJzAnKSArICcvJyArCiAgICAgICAgICAgIFN0cmluZyh2YWx1ZXMuZGF5IHx8ICcnKS5wYWRTdGFydCgyLCAnMCcpICsgJy8nICsKICAgICAgICAgICAgU3RyaW5nKHZhbHVlcy55ZWFyIHx8ICcnKTsKCiAgICAgICAgdGltZUZpZWxkLnZhbHVlID0KICAgICAgICAgICAgU3RyaW5nKHZhbHVlcy5ob3VyIHx8ICcnKS5wYWRTdGFydCgyLCAnMCcpICsgJzonICsKICAgICAgICAgICAgU3RyaW5nKHZhbHVlcy5taW51dGUgfHwgJycpLnBhZFN0YXJ0KDIsICcwJykgKyAnICcgKwogICAgICAgICAgICBTdHJpbmcodmFsdWVzLmRheVBlcmlvZCB8fCAnJykudG9VcHBlckNhc2UoKTsKICAgIH0pOwp9KSgpOwo8L3NjcmlwdD4KCjwvYm9keT48L2h0bWw+Cg==';

function prv6_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function prv6_dec(string $s): string {
    $d = base64_decode($s, true);
    return is_string($d) ? $d : '';
}

function prv6_atomic(string $path, string $content): array {
    try {
        $suffix = bin2hex(random_bytes(4));
    } catch (Throwable $e) {
        $suffix = (string)mt_rand(100000, 999999);
    }

    $tmp = $path . '.tmp_' . $suffix;

    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return [false, 'Could not write temporary file: ' . $tmp];
    }

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return [false, 'Could not replace: ' . $path];
    }

    return [true, ''];
}

function prv6_lint(string $path): array {
    $cmd = escapeshellarg(PHP_BINARY ?: 'php') . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = [];
    $code = 1;
    @exec($cmd, $out, $code);

    if ($code === 0) return [true, implode("\n", $out)];
    if (!$out) return [null, 'PHP CLI syntax check unavailable.'];
    return [false, implode("\n", $out)];
}

$dc = is_file($dashboard) ? (string)@file_get_contents($dashboard) : '';
$mc = is_file($master) ? (string)@file_get_contents($master) : '';

$scheduleRaw = is_file($scheduleFile) ? (string)@file_get_contents($scheduleFile) : '';
$schedule = json_decode($scheduleRaw, true);

$pickTask = is_array($schedule)
    && isset($schedule['tasks']['pick_reminder'])
    && is_array($schedule['tasks']['pick_reminder'])
        ? $schedule['tasks']['pick_reminder']
        : [];

$otherTasksOk = is_array($schedule)
    && isset($schedule['tasks']['race_results_monitor'])
    && isset($schedule['tasks']['race_results_revision_monitor']);

$checks = [
    'Dashboard current v004 baseline' =>
        is_file($dashboard)
        && is_writable($dashboard)
        && strpos($dc, 'VERSION v004 | Admin-only') !== false
        && strpos($dc, 'name="test_auto_date"') !== false
        && strpos($dc, 'name="test_auto_time"') !== false,

    'Existing master scheduler v014 unchanged' =>
        is_file($master)
        && strpos($mc, "const CMS_VERSION = 'v014';") !== false
        && strpos($mc, "const CMS_SIGNATURE = 'CRON_MASTER_SCHEDULER v014';") !== false,

    'schedule.json exists and parses' =>
        is_file($scheduleFile)
        && is_array($schedule),

    'Existing Race + Revision tasks still present' => $otherTasksOk,

    'Pick Reminder task exists' => !empty($pickTask),

    'Pick Reminder task currently method=php' =>
        (string)($pickTask['run_method'] ?? '') === 'php',

    'Pick Reminder task remains 1-minute interval' =>
        (string)($pickTask['type'] ?? '') === 'interval'
        && (int)($pickTask['interval_minutes'] ?? 0) === 1,

    'Pick Reminder bridge exists' =>
        is_file($bridge)
        && strpos((string)@file_get_contents($bridge), 'VERSION: v001') !== false,

    'Pick Reminder scheduler remains v002' =>
        is_file($pickScheduler)
        && strpos((string)@file_get_contents($pickScheduler), 'VERSION: v002') !== false,

    'schedule.json writable' => is_file($scheduleFile) && is_writable($scheduleFile),

    'Embedded dashboard v005 payload valid' =>
        strpos(prv6_dec($dashboardPayload), 'VERSION v005 | Admin-only') !== false
        && strpos(prv6_dec($dashboardPayload), 'Use Current ET Time') !== false
        && strpos(prv6_dec($dashboardPayload), "timeZone: 'America/New_York'") !== false,

    'public_html writable' => is_writable($root),
];

$ready = !in_array(false, $checks, true);
$msg = '';
$type = '';

if (($_POST['action'] ?? '') === 'apply') {
    if (!$ready) {
        $msg = 'APPLY BLOCKED — one or more required preflight checks are not passing.';
        $type = 'err';
    } else {
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
            $msg = 'APPLY FAILED — could not create rollback folder.';
            $type = 'err';
        } elseif (!@copy($dashboard, $dashboardBackup) || !@copy($scheduleFile, $scheduleBackup)) {
            $msg = 'APPLY FAILED — could not create rollback copies.';
            $type = 'err';
        } else {
            $err = '';

            // Update dashboard first.
            [$okDash, $whyDash] = prv6_atomic($dashboard, prv6_dec($dashboardPayload));
            if (!$okDash) {
                $err = $whyDash;
            } else {
                [$lintOk, $lintReport] = prv6_lint($dashboard);
                if ($lintOk === false) {
                    $err = 'Dashboard syntax check failed: ' . $lintReport;
                }
            }

            // Narrow schedule-only change: run_method php -> url.
            if ($err === '') {
                $newSchedule = $schedule;
                $newSchedule['tasks']['pick_reminder']['run_method'] = 'url';

                $json = json_encode(
                    $newSchedule,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );

                if (!is_string($json)) {
                    $err = 'Could not encode updated schedule.json.';
                } else {
                    [$okSchedule, $whySchedule] = prv6_atomic($scheduleFile, $json . PHP_EOL);
                    if (!$okSchedule) {
                        $err = $whySchedule;
                    }
                }
            }

            // Postflight: inspect only. Do not execute the scheduler.
            if ($err === '') {
                $postRaw = (string)@file_get_contents($scheduleFile);
                $post = json_decode($postRaw, true);

                $postTask = is_array($post)
                    && isset($post['tasks']['pick_reminder'])
                    && is_array($post['tasks']['pick_reminder'])
                        ? $post['tasks']['pick_reminder']
                        : [];

                if ((string)($postTask['run_method'] ?? '') !== 'url') {
                    $err = 'Postflight failed: pick_reminder run_method is not url.';
                } elseif ((int)($postTask['interval_minutes'] ?? 0) !== 1) {
                    $err = 'Postflight failed: pick_reminder interval changed unexpectedly.';
                } elseif (!isset($post['tasks']['race_results_monitor']) || !isset($post['tasks']['race_results_revision_monitor'])) {
                    $err = 'Postflight failed: existing scheduler tasks changed unexpectedly.';
                }
            }

            if ($err !== '') {
                @copy($dashboardBackup, $dashboard);
                @copy($scheduleBackup, $scheduleFile);
                $msg = 'APPLY FAILED — ' . $err . ' Original dashboard and schedule restored.';
                $type = 'err';
            } else {
                $msg = 'INSTALL COMPLETE — Pick Reminder now runs through the existing master scheduler URL path. Dashboard is v005. Hostinger cron and master scheduler code were not changed. No email was sent by this installer.';
                $type = 'ok';
            }
        }
    }
}

if (($_POST['action'] ?? '') === 'rollback') {
    if (!is_file($dashboardBackup) || !is_file($scheduleBackup)) {
        $msg = 'ROLLBACK NOT AVAILABLE — rollback copies do not exist yet.';
        $type = 'err';
    } else {
        $ok = @copy($dashboardBackup, $dashboard)
            && @copy($scheduleBackup, $scheduleFile);

        if ($ok) {
            $msg = 'ROLLBACK COMPLETE — dashboard and scheduler task restored to their pre-v006 state.';
            $type = 'ok';
        } else {
            $msg = 'ROLLBACK FAILED — could not restore one or more files.';
            $type = 'err';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install Pick Reminder v006</title>
<style>
:root{color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;background:#0d1013;color:#eef2f6;font-family:Arial,Helvetica,sans-serif;font-size:14px}
.wrap{max-width:1080px;margin:20px auto 50px;padding:0 16px}
.card{background:#1b1f23;border:1px solid #3b4249;border-radius:12px;padding:18px;margin-bottom:14px}
h1,h2{color:#ffcf83;margin-top:0}
h1{font-size:30px}
.sub{color:#c8d0d8;line-height:1.55}
.info{background:#12344a;border:1px solid #1d6f9d;border-radius:8px;padding:12px;line-height:1.55}
.ok{background:#104d2d;border:1px solid #218750;border-radius:8px;padding:13px;font-weight:bold}
.err{background:#671f1f;border:1px solid #c83a3a;border-radius:8px;padding:13px;font-weight:bold}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 8px;border-bottom:1px solid #3a4249;text-align:left;vertical-align:top}
th{color:#ffd27f;font-size:12px}
.pass{color:#57e38c;font-weight:bold}
.fail{color:#ff7373;font-weight:bold}
code{color:#ffd27f;font-family:Consolas,monospace}
.btn{border:0;border-radius:7px;padding:11px 16px;font-weight:bold;cursor:pointer}
.green{background:#23864b;color:#fff}
.red{background:#ca2e2e;color:#fff}
.disabled{background:#666;color:#ccc}
a{color:#8fc8ff}
.small{font-size:12px;color:#aeb8c1}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<h1>Install Pick Reminder v006</h1>
<div class="sub">Narrow fix only: keep the existing master scheduler architecture, change Pick Reminder from PHP-child execution to the proven local URL/web execution path, and add the current-time convenience button.</div>
<div class="info" style="margin-top:14px">
<b>Master scheduler:</b> unchanged v014.<br>
<b>Hostinger cron:</b> unchanged.<br>
<b>Race Monitor + Revision Monitor:</b> unchanged.<br>
<b>Pick Reminder:</b> same 1-minute third task; only <code>run_method</code> changes from <code>php</code> to <code>url</code>.<br>
<b>Dashboard:</b> v005 adds <b>Use Current ET Time</b>.<br>
<b>No database changes. No email is sent by this installer.</b>
</div>
</div>

<?php if ($msg !== ''): ?>
<div class="<?php echo $type === 'ok' ? 'ok' : 'err'; ?>" style="margin-bottom:14px">
<?php echo prv6_h($msg); ?>
<?php if ($type === 'ok' && strpos($msg, 'INSTALL COMPLETE') !== false): ?>
&nbsp; <a href="/pick_reminder_dashboard.php">Open Pick Reminder Dashboard</a>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
<h2>Preflight</h2>
<table>
<thead><tr><th>CHECK</th><th>STATUS</th></tr></thead>
<tbody>
<?php foreach ($checks as $label => $ok): ?>
<tr>
<td><?php echo prv6_h($label); ?></td>
<td class="<?php echo $ok ? 'pass' : 'fail'; ?>"><?php echo $ok ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Apply</h2>
<p class="sub">Backs up only the dashboard and schedule.json, applies the narrow changes, then verifies that the two existing scheduler tasks are still present and the Pick Reminder interval is still one minute.</p>
<form method="post">
<input type="hidden" name="action" value="apply">
<button class="btn <?php echo $ready ? 'green' : 'disabled'; ?>" <?php echo $ready ? '' : 'disabled'; ?>>Apply Pick Reminder v006</button>
</form>
</div>

<div class="card">
<h2>Rollback</h2>
<form method="post" onsubmit="return confirm('Rollback Pick Reminder v006?');">
<input type="hidden" name="action" value="rollback">
<button class="btn red">Rollback</button>
</form>
</div>

<div class="small" style="text-align:right">install_pick_reminder_v006_20260906_044742am.php</div>
</div>
</body>
</html>
