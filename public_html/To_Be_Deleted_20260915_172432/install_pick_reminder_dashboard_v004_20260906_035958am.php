<?php
declare(strict_types=1);

/*
 * install_pick_reminder_dashboard_v004_20260906_035958am.php
 * VERSION: v001
 * LAST MODIFIED: 9/6/2026 3:59:58 am
 *
 * PURPOSE:
 * - Update only pick_reminder_dashboard.php v003 -> v004.
 *
 * CHANGES:
 * - AUTO TEST now uses separate Date and Time text fields.
 * - When no saved AUTO TEST exists, both fields populate from current ET.
 * - A saved test date/time persists after refresh.
 * - Adds Save Dashboard Settings directly in the AUTO TEST section.
 * - Renames the Email Message save button to Save Dashboard Settings.
 *
 * PRESERVES:
 * - pick_reminder_helper.php v003
 * - pick_reminder_scheduler.php v002
 * - Gmail/PHPMailer To-MRL/BCC-team delivery
 * - AUTO TEST scheduler behavior
 * - all TEST/LIVE safeguards
 * - existing saved settings/logs
 *
 * NO DATABASE CHANGES.
 * NO EMAIL IS SENT BY THIS INSTALLER.
 */

date_default_timezone_set('America/New_York');

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$dashboard = $root . '/pick_reminder_dashboard.php';
$helper = $root . '/pick_reminder_helper.php';
$scheduler = $root . '/pick_reminder_scheduler.php';

$backupDir = $root . '/_migration_backups/pick_reminder_dashboard_v004_20260906_035958am';
$dashboardBackup = $backupDir . '/pick_reminder_dashboard.php';

$payload = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7Ci8qKgogKiBwaWNrX3JlbWluZGVyX2Rhc2hib2FyZC5waHAKICogVkVSU0lPTjogdjAwNAogKiBMQVNUIE1PRElGSUVEOiA5LzYvMjAyNiAzOjU5OjU4IGFtCiAqCiAqIENIQU5HRUxPRzoKICogdjAwNCAoOS82LzIwMjYgMzo1OTo1OCBhbSkKICogLSBDSEFOR0U6IEFVVE8gVEVTVCB1c2VzIHNlcGFyYXRlIGRhdGUgYW5kIHRpbWUgZmllbGRzIGluc3RlYWQgb2YgZGF0ZXRpbWUtbG9jYWwuCiAqIC0gTkVXOiBXaGVuIG5vIHNhdmVkIEFVVE8gVEVTVCB0aW1lIGV4aXN0cywgZGF0ZS90aW1lIGRlZmF1bHQgdG8gY3VycmVudCBFVC4KICogLSBDSEFOR0U6IFNhdmVkIEFVVE8gVEVTVCBkYXRlL3RpbWUgcGVyc2lzdHMgYWZ0ZXIgcmVmcmVzaC4KICogLSBORVc6IFNhdmUgRGFzaGJvYXJkIFNldHRpbmdzIGJ1dHRvbiBhZGRlZCBkaXJlY3RseSBpbiBBVVRPIFRFU1Qgc2VjdGlvbi4KICogLSBDSEFOR0U6IEV4aXN0aW5nIGVtYWlsLXNlY3Rpb24gc2F2ZSBidXR0b24gcmVuYW1lZCBTYXZlIERhc2hib2FyZCBTZXR0aW5ncy4KICoKICogdjAwMyAoOS82LzIwMjYgMzozNDoxNiBhbSkKICogLSBDSEFOR0U6IFRlYW0tbmFtZSBwZXJzb25hbGl6ZWQgZGVmYXVsdCByZW1pbmRlciBtZXNzYWdlLgogKiAtIE5FVzogT25lLXRpbWUgQVVUTyBURVNUIHRpbWVzdGFtcCBjb250cm9sIGZvciBNUkwgSUQgOTk5LgogKiAtIENIQU5HRTogRGFzaGJvYXJkIHJlZmxlY3RzIFRvOiBNUkwgR21haWwgLyBCQ0M6IHRlYW0gcHJpdmFjeSBtb2RlbC4KICoKICogdjAwMiAoOS82LzIwMjYgMzowMjowOCBhbSkKICogLSBGSVg6IFVzZXMgY29ycmVjdGVkIGRlYWRsaW5lIHBhcnNlciBmcm9tIHBpY2tfcmVtaW5kZXJfaGVscGVyLnBocCB2MDAyLgogKi8KZGF0ZV9kZWZhdWx0X3RpbWV6b25lX3NldCgnQW1lcmljYS9OZXdfWW9yaycpOwppZihzZXNzaW9uX3N0YXR1cygpPT09UEhQX1NFU1NJT05fTk9ORSlzZXNzaW9uX3N0YXJ0KCk7CiRfU0VTU0lPTlsncmV0dXJuX3RvJ109JF9TRVJWRVJbJ1JFUVVFU1RfVVJJJ10/PycvcGlja19yZW1pbmRlcl9kYXNoYm9hcmQucGhwJzsKcmVxdWlyZV9vbmNlIF9fRElSX18uJy9jb25maWcucGhwJztyZXF1aXJlX29uY2UgX19ESVJfXy4nL2NvbmZpZ19tcmwucGhwJztyZXF1aXJlX29uY2UgX19ESVJfXy4nL2NsYXNzLnVzZXIucGhwJzsKJHVoPW5ldyBVU0VSKCk7aWYoISR1aC0+aXNfbG9nZ2VkX2luKCkpeyR1aC0+cmVkaXJlY3QoJy9sb2dpbi5waHAnKTtleGl0O31pZighaXNBZG1pbigkX1NFU1NJT05bJ3VzZXJTZXNzaW9uJ10/P251bGwpKXtodHRwX3Jlc3BvbnNlX2NvZGUoNDAzKTtleGl0KCdBZG1pbiBhY2Nlc3MgcmVxdWlyZWQuJyk7fQpkZWZpbmUoJ01STF9QSUNLX1JFTUlOREVSX0NPTlRFWFQnLCdkYXNoYm9hcmQnKTtyZXF1aXJlX29uY2UgX19ESVJfXy4nL3BpY2tfcmVtaW5kZXJfaGVscGVyLnBocCc7CmlmKCFpc3NldCgkZGJjb25uZWN0KXx8ISgkZGJjb25uZWN0IGluc3RhbmNlb2YgbXlzcWxpKSl7aHR0cF9yZXNwb25zZV9jb2RlKDUwMCk7ZXhpdCgnRGF0YWJhc2UgY29ubmVjdGlvbiB1bmF2YWlsYWJsZS4nKTt9CmlmKCFpc3NldCgkX1NFU1NJT05bJ21ybHByX2NzcmYnXSkpJF9TRVNTSU9OWydtcmxwcl9jc3JmJ109YmluMmhleChyYW5kb21fYnl0ZXMoMjQpKTsKJGNmZz1tcmxwcl9sb2FkX2NvbmZpZygpOyRjdHg9bXJscHJfY3VycmVudF9jb250ZXh0KCk7JG1zZz0nJzskbXNnQ2xhc3M9J2luZm8nOwpmdW5jdGlvbiBwcmRfY3NyZigpOmJvb2x7cmV0dXJuIGlzc2V0KCRfUE9TVFsnY3NyZiddKSYmaGFzaF9lcXVhbHMoKHN0cmluZykoJF9TRVNTSU9OWydtcmxwcl9jc3JmJ10/PycnKSwoc3RyaW5nKSRfUE9TVFsnY3NyZiddKTt9CmZ1bmN0aW9uIHByZF9wb3N0X2NmZyhhcnJheSAkYyk6YXJyYXl7CiAgICAkbT1zdHJ0b3VwcGVyKHRyaW0oKHN0cmluZykoJF9QT1NUWydtb2RlJ10/PydNQU5VQUwnKSkpOyRjWydtb2RlJ109aW5fYXJyYXkoJG0sWydBVVRPJywnTUFOVUFMJywnT0ZGJ10sdHJ1ZSk/JG06J01BTlVBTCc7CiAgICAkcz1zdHJ0b3VwcGVyKHRyaW0oKHN0cmluZykoJF9QT1NUWydzY29wZSddPz8nVEVTVCcpKSk7JGNbJ3Njb3BlJ109aW5fYXJyYXkoJHMsWydURVNUJywnTElWRSddLHRydWUpPyRzOidURVNUJzsKICAgICRvPVtdO2ZvcmVhY2goWydvZmZzZXQxJywnb2Zmc2V0MicsJ29mZnNldDMnXSBhcyAkayl7JG49bXJscHJfZHVyYXRpb25fdG9fbWludXRlcygoc3RyaW5nKSgkX1BPU1RbJGtdPz8nJykpO2lmKCRuIT09bnVsbCkkb1tdPSRuO30kbz1hcnJheV92YWx1ZXMoYXJyYXlfdW5pcXVlKCRvKSk7cnNvcnQoJG8sU09SVF9OVU1FUklDKTskY1snb2Zmc2V0c19taW51dGVzJ109JG8/OlsxODAsMTIwLDYwXTsKICAgICRzdWI9dHJpbSgoc3RyaW5nKSgkX1BPU1RbJ3N1YmplY3RfdGVtcGxhdGUnXT8/JycpKTskYm9keT10cmltKChzdHJpbmcpKCRfUE9TVFsnYm9keV90ZW1wbGF0ZSddPz8nJykpO2lmKCRzdWIhPT0nJykkY1snc3ViamVjdF90ZW1wbGF0ZSddPSRzdWI7aWYoJGJvZHkhPT0nJykkY1snYm9keV90ZW1wbGF0ZSddPSRib2R5OwogICAgJGNbJ3Rlc3RfYXV0b19lbmFibGVkJ109aXNzZXQoJF9QT1NUWyd0ZXN0X2F1dG9fZW5hYmxlZCddKSAmJiAoc3RyaW5nKSRfUE9TVFsndGVzdF9hdXRvX2VuYWJsZWQnXT09PScxJzsKCiAgICAkdGVzdERhdGU9dHJpbSgoc3RyaW5nKSgkX1BPU1RbJ3Rlc3RfYXV0b19kYXRlJ10/PycnKSk7CiAgICAkdGVzdFRpbWU9dHJpbSgoc3RyaW5nKSgkX1BPU1RbJ3Rlc3RfYXV0b190aW1lJ10/PycnKSk7CgogICAgaWYoJHRlc3REYXRlPT09JycgJiYgJHRlc3RUaW1lPT09JycpewogICAgICAgICRjWyd0ZXN0X2F1dG9fYXQnXT0nJzsKICAgIH1lbHNlewogICAgICAgICRwYXJzZWQ9RGF0ZVRpbWU6OmNyZWF0ZUZyb21Gb3JtYXQoCiAgICAgICAgICAgICdtL2QvWSBoOmkgQScsCiAgICAgICAgICAgICR0ZXN0RGF0ZS4nICcuc3RydG91cHBlcigkdGVzdFRpbWUpLAogICAgICAgICAgICBuZXcgRGF0ZVRpbWVab25lKCdBbWVyaWNhL05ld19Zb3JrJykKICAgICAgICApOwoKICAgICAgICAkcGFyc2VFcnJvcnM9RGF0ZVRpbWU6OmdldExhc3RFcnJvcnMoKTsKICAgICAgICAkaGFzRXJyb3JzPWlzX2FycmF5KCRwYXJzZUVycm9ycykKICAgICAgICAgICAgJiYgKCgoaW50KSgkcGFyc2VFcnJvcnNbJ3dhcm5pbmdfY291bnQnXT8/MCk+MCkgfHwgKChpbnQpKCRwYXJzZUVycm9yc1snZXJyb3JfY291bnQnXT8/MCk+MCkpOwoKICAgICAgICBpZigkcGFyc2VkIGluc3RhbmNlb2YgRGF0ZVRpbWUgJiYgISRoYXNFcnJvcnMpewogICAgICAgICAgICAkY1sndGVzdF9hdXRvX2F0J109JHBhcnNlZC0+Zm9ybWF0KCdZLW0tZCBIOmknKTsKICAgICAgICB9ZWxzZXsKICAgICAgICAgICAgJGNbJ3Rlc3RfYXV0b19hdCddPSdfX0lOVkFMSURfXyc7CiAgICAgICAgfQogICAgfQoKICAgIHJldHVybiAkYzsKfQokYWN0aW9uPShzdHJpbmcpKCRfUE9TVFsnYWN0aW9uJ10/PycnKTsKaWYoJF9TRVJWRVJbJ1JFUVVFU1RfTUVUSE9EJ109PT0nUE9TVCcmJiRhY3Rpb249PT0nc2F2ZV9zZXR0aW5ncycpewogICAgaWYoIXByZF9jc3JmKCkpeyRtc2c9J1NhdmUgYmxvY2tlZDogc2VjdXJpdHkgdG9rZW4gbWlzbWF0Y2guJzskbXNnQ2xhc3M9J2JhZCc7fQogICAgZWxzZXskbj1wcmRfcG9zdF9jZmcoJGNmZyk7aWYoKHN0cmluZykoJG5bJ3Rlc3RfYXV0b19hdCddPz8nJyk9PT0nX19JTlZBTElEX18nKXskbXNnPSdBVVRPIFRFU1QgZGF0ZS90aW1lIGlzIGludmFsaWQuIFVzZSBNTS9ERC9ZWVlZIGFuZCBISDpNTSBBTS9QTS4nOyRtc2dDbGFzcz0nYmFkJzt9CiAgICBlbHNlaWYoJG5bJ21vZGUnXT09PSdBVVRPJyYmJG5bJ3Njb3BlJ109PT0nTElWRScmJnRyaW0oKHN0cmluZykoJF9QT1NUWydsaXZlX2F1dG9fY29uZmlybSddPz8nJykpIT09J0VOQUJMRSBMSVZFIEFVVE8nKXskbXNnPSdMSVZFIEFVVE8gd2FzIG5vdCBlbmFibGVkLiBUeXBlIEVOQUJMRSBMSVZFIEFVVE8gZXhhY3RseS4nOyRtc2dDbGFzcz0nYmFkJzt9CiAgICBlbHNlaWYoIW1ybHByX3NhdmVfY29uZmlnKCRuKSl7JG1zZz0nQ291bGQgbm90IHNhdmUgc2V0dGluZ3MuJzskbXNnQ2xhc3M9J2JhZCc7fWVsc2V7JGNmZz0kbjskbXNnPSdTZXR0aW5ncyBzYXZlZC4nOyRtc2dDbGFzcz0nb2snO319Cn0KaWYoJF9TRVJWRVJbJ1JFUVVFU1RfTUVUSE9EJ109PT0nUE9TVCcmJiRhY3Rpb249PT0nc2VuZF9tYW51YWwnKXsKICAgIGlmKCFwcmRfY3NyZigpKXskbXNnPSdTZW5kIGJsb2NrZWQ6IHNlY3VyaXR5IHRva2VuIG1pc21hdGNoLic7JG1zZ0NsYXNzPSdiYWQnO30KICAgIGVsc2V7JHdvcms9cHJkX3Bvc3RfY2ZnKCRjZmcpOyR3b3JrWydtb2RlJ109JGNmZ1snbW9kZSddOyRzY29wZT0oc3RyaW5nKSR3b3JrWydzY29wZSddOyRyZWM9bXJscHJfbWlzc2luZ19yZWNpcGllbnRzKCRkYmNvbm5lY3QsJHNjb3BlLChzdHJpbmcpJGN0eFsneWVhciddLChzdHJpbmcpJGN0eFsnc2VnbWVudCddKTskYnk9W107Zm9yZWFjaCgkcmVjIGFzICRyKSRieVsoaW50KSRyWyd1c2VySUQnXV09JHI7JHNlbD1hcnJheV92YWx1ZXMoYXJyYXlfdW5pcXVlKGFycmF5X21hcCgnaW50dmFsJywoYXJyYXkpKCRfUE9TVFsncmVjaXBpZW50X2lkcyddPz9bXSkpKSk7CiAgICAgICAgaWYoJHNjb3BlPT09J0xJVkUnJiZ0cmltKChzdHJpbmcpKCRfUE9TVFsnbGl2ZV9zZW5kX2NvbmZpcm0nXT8/JycpKSE9PSdTRU5EIExJVkUnKXskbXNnPSdMSVZFIHNlbmQgYmxvY2tlZC4gVHlwZSBTRU5EIExJVkUgZXhhY3RseS4nOyRtc2dDbGFzcz0nYmFkJzt9CiAgICAgICAgZWxzZWlmKCEkc2VsKXskbXNnPSdObyByZWNpcGllbnRzIHNlbGVjdGVkLic7JG1zZ0NsYXNzPSdiYWQnO30KICAgICAgICBlbHNleyRzZW50PTA7JHNraXA9MDskZmFpbD0wO2ZvcmVhY2goJHNlbCBhcyAkdWlkKXtpZighaXNzZXQoJGJ5WyR1aWRdKSl7JHNraXArKztjb250aW51ZTt9JHg9bXJscHJfc2VuZF91c2VyKCRkYmNvbm5lY3QsJGJ5WyR1aWRdLCR3b3JrLCRjdHgsJ01BTlVBTCcsbnVsbCk7JHN0PShzdHJpbmcpKCR4WydyZXN1bHQnXT8/JycpO2lmKCRzdD09PSdTRU5UJykkc2VudCsrO2Vsc2VpZihzdHJwb3MoJHN0LCdTS0lQUEVEJyk9PT0wKSRza2lwKys7ZWxzZSRmYWlsKys7fSRtc2c9Ik1hbnVhbCByZW1pbmRlciBydW4gY29tcGxldGUg4oCUIHNlbnQgJHNlbnQsIHNraXBwZWQgJHNraXAsIGZhaWxlZCAkZmFpbC4iOyRtc2dDbGFzcz0kZmFpbD8nYmFkJzonb2snO30KICAgIH0KfQokcmVjaXBpZW50cz1tcmxwcl9taXNzaW5nX3JlY2lwaWVudHMoJGRiY29ubmVjdCwoc3RyaW5nKSRjZmdbJ3Njb3BlJ10sKHN0cmluZykkY3R4Wyd5ZWFyJ10sKHN0cmluZykkY3R4WydzZWdtZW50J10pOyRkZWFkbGluZT0kY3R4WydkZWFkbGluZV9kdCddOwokcmM9Wyd5ZWFyJz0+JGN0eFsneWVhciddLCdzZWdtZW50Jz0+JGN0eFsnc2VnbWVudCddLCdzZWdtZW50X25hbWUnPT4kY3R4WydzZWdtZW50X25hbWUnXSwnZGVhZGxpbmUnPT4kY3R4WydkZWFkbGluZV9kaXNwbGF5J10sJ3RlYW1fbmFtZSc9PiRyZWNpcGllbnRzPyhzdHJpbmcpKCRyZWNpcGllbnRzWzBdWyd0ZWFtTmFtZSddPz8nVGVhbScpOidUZWFtJywndGVhbV9wYWdlX3VybCc9PiRjZmdbJ3RlYW1fcGFnZV91cmwnXV07CiRwcmV2aWV3U3ViPW1ybHByX3JlbmRlcl90ZW1wbGF0ZSgoc3RyaW5nKSRjZmdbJ3N1YmplY3RfdGVtcGxhdGUnXSwkcmMsZmFsc2UpOyRwcmV2aWV3Qm9keT1tcmxwcl9yZW5kZXJfdGVtcGxhdGUoKHN0cmluZykkY2ZnWydib2R5X3RlbXBsYXRlJ10sJHJjLGZhbHNlKTsKJHN0YXRlPW1ybHByX2xvYWRfc2NoZWR1bGVyX3N0YXRlKCk7JGxvZ3M9YXJyYXlfcmV2ZXJzZShtcmxwcl9yZWFkX2xvZygzMCkpOwokcHJlPVsnQWRtaW4gYWNjZXNzJz0+dHJ1ZSwnUEhQTWFpbGVyIGF2YWlsYWJsZSc9PmlzX2ZpbGUoX19ESVJfXy4nL21haWxlci9jbGFzcy5waHBtYWlsZXIucGhwJykgJiYgY2xhc3NfZXhpc3RzKCdVU0VSJykgJiYgbWV0aG9kX2V4aXN0cygnVVNFUicsJ3NlbmRfbWFpbCcpLCd1c2VycyBlbWFpbCBjb2x1bW5zIGF2YWlsYWJsZSc9Pm1ybHByX3RhYmxlX2hhc19jb2x1bW5zKCRkYmNvbm5lY3QsJ3VzZXJzJyxbJ3VzZXJJRCcsJ3VzZXJOYW1lJywndXNlckVtYWlsJywndXNlckVtYWlsMicsJ3VzZXJBY3RpdmUnXSksJ3VzZXJfdGVhbXMgY29sdW1ucyBhdmFpbGFibGUnPT5tcmxwcl90YWJsZV9oYXNfY29sdW1ucygkZGJjb25uZWN0LCd1c2VyX3RlYW1zJyxbJ3VzZXJJRCcsJ3JhY2VZZWFyJywndGVhbU5hbWUnXSksJ3VzZXJfcGlja3MgY29sdW1ucyBhdmFpbGFibGUnPT5tcmxwcl90YWJsZV9oYXNfY29sdW1ucygkZGJjb25uZWN0LCd1c2VyX3BpY2tzJyxbJ3VzZXJJRCcsJ3JhY2VZZWFyJywnc2VnbWVudCddKSwnRGVhZGxpbmUgcGFyc2VkJz0+JGRlYWRsaW5lIGluc3RhbmNlb2YgRGF0ZVRpbWUsJ1N0YXRlIGZvbGRlciB3cml0YWJsZS9jcmVhdGFibGUnPT5tcmxwcl9lbnN1cmVfc3RhdGVfZGlyKCldOyRyZWFkeT0haW5fYXJyYXkoZmFsc2UsJHByZSx0cnVlKTsKJG9mZnM9YXJyYXlfdmFsdWVzKChhcnJheSkkY2ZnWydvZmZzZXRzX21pbnV0ZXMnXSk7d2hpbGUoY291bnQoJG9mZnMpPDMpJG9mZnNbXT0wOwo/PjwhZG9jdHlwZSBodG1sPjxodG1sIGxhbmc9ImVuIj48aGVhZD48bWV0YSBjaGFyc2V0PSJ1dGYtOCI+PG1ldGEgbmFtZT0idmlld3BvcnQiIGNvbnRlbnQ9IndpZHRoPWRldmljZS13aWR0aCxpbml0aWFsLXNjYWxlPTEiPjx0aXRsZT5NUkwgUGljayBSZW1pbmRlciBEYXNoYm9hcmQ8L3RpdGxlPgo8c3R5bGU+Cjpyb290ey0tYmc6IzEwMTIxNDstLXA6IzFiMWYyMzstLXAyOiMxNTE5MWQ7LS1iOiM0MTQ4NTA7LS10OiNlZWYyZjY7LS1tOiNhZWI4YzE7LS1nOiNmZmNmODM7LS1ncmVlbjojNTdlMzhjOy0tcmVkOiNmZjczNzM7LS1ibHVlOiM4ZmM4ZmZ9Kntib3gtc2l6aW5nOmJvcmRlci1ib3h9Ym9keXttYXJnaW46MDtiYWNrZ3JvdW5kOnZhcigtLWJnKTtjb2xvcjp2YXIoLS10KTtmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjtmb250LXNpemU6MTRweH0ud3JhcHttYXgtd2lkdGg6MTE4MHB4O21hcmdpbjoyMHB4IGF1dG8gNTBweDtwYWRkaW5nOjAgMTZweH0uY2FyZHtiYWNrZ3JvdW5kOnZhcigtLXApO2JvcmRlcjoxcHggc29saWQgdmFyKC0tYik7Ym9yZGVyLXJhZGl1czoxMnB4O3BhZGRpbmc6MThweDttYXJnaW4tYm90dG9tOjE0cHh9aDEsaDIsaDN7Y29sb3I6dmFyKC0tZyk7bWFyZ2luLXRvcDowfWgxe2ZvbnQtc2l6ZTozMHB4O21hcmdpbi1ib3R0b206OHB4fWgye2ZvbnQtc2l6ZToyMXB4fS5tdXRlZHtjb2xvcjp2YXIoLS1tKX0uZzJ7ZGlzcGxheTpncmlkO2dyaWQtdGVtcGxhdGUtY29sdW1uczoxZnIgMWZyO2dhcDoxNHB4fS5nM3tkaXNwbGF5OmdyaWQ7Z3JpZC10ZW1wbGF0ZS1jb2x1bW5zOnJlcGVhdCgzLDFmcik7Z2FwOjEycHh9LnN0YXR7YmFja2dyb3VuZDp2YXIoLS1wMik7Ym9yZGVyOjFweCBzb2xpZCAjMzUzYzQzO2JvcmRlci1yYWRpdXM6OXB4O3BhZGRpbmc6MTJweH0uYmlne2ZvbnQtc2l6ZToyNHB4O2ZvbnQtd2VpZ2h0OjgwMH0uYmFubmVye3BhZGRpbmc6MTJweCAxNHB4O2JvcmRlci1yYWRpdXM6OXB4O21hcmdpbjoxMnB4IDA7Zm9udC13ZWlnaHQ6NzAwfS5va3tiYWNrZ3JvdW5kOiMxMjNhMmE7Ym9yZGVyOjFweCBzb2xpZCAjMmI4MTViO2NvbG9yOiNkOWZmZWF9LmJhZHtiYWNrZ3JvdW5kOiM0YTE4MTg7Ym9yZGVyOjFweCBzb2xpZCAjYTY0ZTRlO2NvbG9yOiNmZmQ0ZDR9LmluZm97YmFja2dyb3VuZDojMTIzMDQ0O2JvcmRlcjoxcHggc29saWQgIzI4NmE5Mztjb2xvcjojZGJmMmZmfS53YXJue2JhY2tncm91bmQ6IzRhMzUxNDtib3JkZXI6MXB4IHNvbGlkICM5YjZhMTI7Y29sb3I6I2ZmZThiNH1sYWJlbHtkaXNwbGF5OmJsb2NrO21hcmdpbjo2cHggMH1pbnB1dFt0eXBlPXRleHRdLHRleHRhcmVhe3dpZHRoOjEwMCU7YmFja2dyb3VuZDojMGYxMjE1O2NvbG9yOiNmZmY7Ym9yZGVyOjFweCBzb2xpZCAjNTk2MTZhO2JvcmRlci1yYWRpdXM6N3B4O3BhZGRpbmc6OXB4O2ZvbnQ6aW5oZXJpdH10ZXh0YXJlYXttaW4taGVpZ2h0OjEzMnB4fS5yYWR7ZGlzcGxheTpmbGV4O2dhcDoxOHB4O2ZsZXgtd3JhcDp3cmFwfS5yYWQgbGFiZWx7ZGlzcGxheTpmbGV4O2dhcDo2cHg7YWxpZ24taXRlbXM6Y2VudGVyO21hcmdpbjowfS5idG57Ym9yZGVyOjA7Ym9yZGVyLXJhZGl1czo3cHg7cGFkZGluZzoxMXB4IDE2cHg7Zm9udC13ZWlnaHQ6ODAwO2N1cnNvcjpwb2ludGVyfS5ncmVlbntiYWNrZ3JvdW5kOiMyMzg2NGI7Y29sb3I6I2ZmZn0uYmx1ZXtiYWNrZ3JvdW5kOiMyZjZmZWI7Y29sb3I6I2ZmZn10YWJsZXt3aWR0aDoxMDAlO2JvcmRlci1jb2xsYXBzZTpjb2xsYXBzZX10aCx0ZHtwYWRkaW5nOjlweCA4cHg7Ym9yZGVyLWJvdHRvbToxcHggc29saWQgIzM0M2I0Mjt0ZXh0LWFsaWduOmxlZnQ7dmVydGljYWwtYWxpZ246dG9wfXRoe2NvbG9yOiNmZmQyN2Y7Zm9udC1zaXplOjEycHh9LnBhc3N7Y29sb3I6dmFyKC0tZ3JlZW4pO2ZvbnQtd2VpZ2h0OjgwMH0uZmFpbHtjb2xvcjp2YXIoLS1yZWQpO2ZvbnQtd2VpZ2h0OjgwMH1jb2RlLC5tb25ve2ZvbnQtZmFtaWx5OkNvbnNvbGFzLE1lbmxvLG1vbm9zcGFjZTtjb2xvcjojZmZkMjdmfS5wcmV2aWV3e3doaXRlLXNwYWNlOnByZS13cmFwO2JhY2tncm91bmQ6IzBmMTIxNTtib3JkZXI6MXB4IHNvbGlkICM0NTRkNTU7Ym9yZGVyLXJhZGl1czo4cHg7cGFkZGluZzoxNHB4O2xpbmUtaGVpZ2h0OjEuNTV9LnNtYWxse2ZvbnQtc2l6ZToxMnB4O2NvbG9yOnZhcigtLW0pfS50ZXN0LWZpZWxkc3tkaXNwbGF5OmdyaWQ7Z3JpZC10ZW1wbGF0ZS1jb2x1bW5zOjFmciAxZnI7Z2FwOjEwcHh9LnNhdmUtcm93e21hcmdpbi10b3A6MTRweH1AbWVkaWEobWF4LXdpZHRoOjYwMHB4KXsudGVzdC1maWVsZHN7Z3JpZC10ZW1wbGF0ZS1jb2x1bW5zOjFmcn19QG1lZGlhKG1heC13aWR0aDo4MDBweCl7LmcyLC5nM3tncmlkLXRlbXBsYXRlLWNvbHVtbnM6MWZyfWJvZHl7Zm9udC1zaXplOjE2cHh9fQo8L3N0eWxlPjwvaGVhZD48Ym9keT48ZGl2IGNsYXNzPSJ3cmFwIj4KPGRpdiBjbGFzcz0iY2FyZCI+PGgxPk1STCBQaWNrIFJlbWluZGVyIERhc2hib2FyZDwvaDE+PGRpdiBjbGFzcz0ibXV0ZWQiPlZFUlNJT04gdjAwNCB8IEFkbWluLW9ubHkgfCBTYWZlIGRlZmF1bHQ6IE1BTlVBTCArIFRFU1QgKE1STCBJRCA5OTkgb25seSk8L2Rpdj48P3BocCBpZigkbXNnIT09JycpOj8+PGRpdiBjbGFzcz0iYmFubmVyIDw/cGhwIGVjaG8gJG1zZ0NsYXNzPT09J29rJz8nb2snOigkbXNnQ2xhc3M9PT0nYmFkJz8nYmFkJzonaW5mbycpOz8+Ij48P3BocCBlY2hvIG1ybHByX2goJG1zZyk7Pz48L2Rpdj48P3BocCBlbmRpZjs/PjwvZGl2Pgo8ZGl2IGNsYXNzPSJjYXJkIj48aDI+Q3VycmVudCBQaWNrIFdpbmRvdzwvaDI+PGRpdiBjbGFzcz0iZzMiPjxkaXYgY2xhc3M9InN0YXQiPjxkaXYgY2xhc3M9Im11dGVkIj5ZZWFyIC8gU2VnbWVudDwvZGl2PjxkaXYgY2xhc3M9ImJpZyI+PD9waHAgZWNobyBtcmxwcl9oKCRjdHhbJ3llYXInXS4nICcuJGN0eFsnc2VnbWVudF9uYW1lJ10pOz8+PC9kaXY+PC9kaXY+PGRpdiBjbGFzcz0ic3RhdCI+PGRpdiBjbGFzcz0ibXV0ZWQiPkRlYWRsaW5lPC9kaXY+PGRpdiBjbGFzcz0iYmlnIiBzdHlsZT0iZm9udC1zaXplOjE4cHgiPjw/cGhwIGVjaG8gbXJscHJfaCgkY3R4WydkZWFkbGluZV9kaXNwbGF5J10pOz8+PC9kaXY+PC9kaXY+PGRpdiBjbGFzcz0ic3RhdCI+PGRpdiBjbGFzcz0ibXV0ZWQiPk1pc3NpbmcgaW4gY3VycmVudCBzY29wZTwvZGl2PjxkaXYgY2xhc3M9ImJpZyI+PD9waHAgZWNobyBjb3VudCgkcmVjaXBpZW50cyk7Pz48L2Rpdj48L2Rpdj48L2Rpdj48L2Rpdj4KPGRpdiBjbGFzcz0iY2FyZCI+PGgyPlByZWZsaWdodDwvaDI+PHRhYmxlPjx0aGVhZD48dHI+PHRoPkNIRUNLPC90aD48dGg+U1RBVFVTPC90aD48L3RyPjwvdGhlYWQ+PHRib2R5Pjw/cGhwIGZvcmVhY2goJHByZSBhcyAkbD0+JG9rKTo/Pjx0cj48dGQ+PD9waHAgZWNobyBtcmxwcl9oKCRsKTs/PjwvdGQ+PHRkIGNsYXNzPSI8P3BocCBlY2hvICRvaz8ncGFzcyc6J2ZhaWwnOz8+Ij48P3BocCBlY2hvICRvaz8nUEFTUyc6J0ZBSUwnOz8+PC90ZD48L3RyPjw/cGhwIGVuZGZvcmVhY2g7Pz48L3Rib2R5PjwvdGFibGU+PC9kaXY+Cjxmb3JtIG1ldGhvZD0icG9zdCI+PGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iY3NyZiIgdmFsdWU9Ijw/cGhwIGVjaG8gbXJscHJfaCgkX1NFU1NJT05bJ21ybHByX2NzcmYnXSk7Pz4iPgo8ZGl2IGNsYXNzPSJjYXJkIj48aDI+TW9kZSArIFNjb3BlPC9oMj48ZGl2IGNsYXNzPSJnMiI+PGRpdiBjbGFzcz0ic3RhdCI+PGgzPk1vZGU8L2gzPjxkaXYgY2xhc3M9InJhZCI+PD9waHAgZm9yZWFjaChbJ0FVVE8nLCdNQU5VQUwnLCdPRkYnXSBhcyAkbSk6Pz48bGFiZWw+PGlucHV0IHR5cGU9InJhZGlvIiBuYW1lPSJtb2RlIiB2YWx1ZT0iPD9waHAgZWNobyAkbTs/PiIgPD9waHAgZWNobyAkY2ZnWydtb2RlJ109PT0kbT8nY2hlY2tlZCc6Jyc7Pz4+PD9waHAgZWNobyAkbTs/PjwvbGFiZWw+PD9waHAgZW5kZm9yZWFjaDs/PjwvZGl2PjxwIGNsYXNzPSJzbWFsbCI+QVVUTyA9IHNjaGVkdWxlZCBzZW5kcy4gTUFOVUFMID0gYnV0dG9uIG9ubHkuIE9GRiA9IG5vIGF1dG9tYXRpYyBzZW5kaW5nLjwvcD48L2Rpdj48ZGl2IGNsYXNzPSJzdGF0Ij48aDM+UmVjaXBpZW50IFNjb3BlPC9oMz48ZGl2IGNsYXNzPSJyYWQiPjxsYWJlbD48aW5wdXQgdHlwZT0icmFkaW8iIG5hbWU9InNjb3BlIiB2YWx1ZT0iVEVTVCIgPD9waHAgZWNobyAkY2ZnWydzY29wZSddPT09J1RFU1QnPydjaGVja2VkJzonJzs/Pj5URVNUIOKAlCBNUkwgOTk5IG9ubHk8L2xhYmVsPjxsYWJlbD48aW5wdXQgdHlwZT0icmFkaW8iIG5hbWU9InNjb3BlIiB2YWx1ZT0iTElWRSIgPD9waHAgZWNobyAkY2ZnWydzY29wZSddPT09J0xJVkUnPydjaGVja2VkJzonJzs/Pj5MSVZFIOKAlCBtaXNzaW5nIGFjdGl2ZSB0ZWFtczwvbGFiZWw+PC9kaXY+PHAgY2xhc3M9InNtYWxsIj5MSVZFIGV4Y2x1ZGVzIHVzZXJJRCAwIGFuZCA5OTkgYW5kIHJlY2hlY2tzIHRoZSBEQiBpbW1lZGlhdGVseSBiZWZvcmUgZXZlcnkgc2VuZC48L3A+PC9kaXY+PC9kaXY+PC9kaXY+Cjw/cGhwCiR0ZXN0U2F2ZWRBdD10cmltKChzdHJpbmcpKCRjZmdbJ3Rlc3RfYXV0b19hdCddPz8nJykpOwokdGVzdERhdGVWYWx1ZT1kYXRlKCdtL2QvWScpOwokdGVzdFRpbWVWYWx1ZT1kYXRlKCdoOmkgQScpOwoKaWYoJHRlc3RTYXZlZEF0IT09JycpewogICAgdHJ5ewogICAgICAgICR0ZXN0U2F2ZWREdD1uZXcgRGF0ZVRpbWUoJHRlc3RTYXZlZEF0LG5ldyBEYXRlVGltZVpvbmUoJ0FtZXJpY2EvTmV3X1lvcmsnKSk7CiAgICAgICAgJHRlc3REYXRlVmFsdWU9JHRlc3RTYXZlZER0LT5mb3JtYXQoJ20vZC9ZJyk7CiAgICAgICAgJHRlc3RUaW1lVmFsdWU9JHRlc3RTYXZlZER0LT5mb3JtYXQoJ2g6aSBBJyk7CiAgICB9Y2F0Y2goVGhyb3dhYmxlICRlKXsKICAgICAgICAvLyBGYWxsIGJhY2sgdG8gY3VycmVudCBFVCBkYXRlL3RpbWUgZm9yIGRpc3BsYXkgb25seS4KICAgIH0KfQo/Pgo8ZGl2IGNsYXNzPSJjYXJkIj48aDI+QVVUTyBURVNUIOKAlCBJRCA5OTkgT25seTwvaDI+CjxwIGNsYXNzPSJtdXRlZCI+VXNlIHRoaXMgdG8gcHJvdmUgdGhlIGNvbXBsZXRlIGF1dG9tYXRpYyBwYXRoIGF0IGEgY29udmVuaWVudCB0aW1lOiBIb3N0aW5nZXIgY3JvbiDihpIgY3JvbiBsYXVuY2hlciDihpIgcmVtaW5kZXIgc2NoZWR1bGVyIOKGkiBtaXNzaW5nLXBpY2sgY2hlY2sg4oaSIGVtYWlsIOKGkiBzZW5kIGhpc3RvcnkuPC9wPgo8ZGl2IGNsYXNzPSJnMiI+CiAgICA8ZGl2IGNsYXNzPSJzdGF0Ij4KICAgICAgICA8bGFiZWw+PGlucHV0IHR5cGU9ImNoZWNrYm94IiBuYW1lPSJ0ZXN0X2F1dG9fZW5hYmxlZCIgdmFsdWU9IjEiIDw/cGhwIGVjaG8gIWVtcHR5KCRjZmdbJ3Rlc3RfYXV0b19lbmFibGVkJ10pPydjaGVja2VkJzonJzsgPz4+IDxiPlVzZSBvbmUtdGltZSBURVNUIEFVVE8gdGltZXN0YW1wPC9iPjwvbGFiZWw+CiAgICAgICAgPHAgY2xhc3M9InNtYWxsIj5Pbmx5IGFwcGxpZXMgd2hlbiA8Yj5BVVRPICsgVEVTVDwvYj4gaXMgc2VsZWN0ZWQuIEl0IG5ldmVyIHRhcmdldHMgTElWRSB0ZWFtcy48L3A+CiAgICA8L2Rpdj4KICAgIDxkaXYgY2xhc3M9InN0YXQiPgogICAgICAgIDxsYWJlbD48Yj5UZXN0IHNlbmQgZGF0ZS90aW1lIChFVCk8L2I+PC9sYWJlbD4KICAgICAgICA8ZGl2IGNsYXNzPSJ0ZXN0LWZpZWxkcyI+CiAgICAgICAgICAgIDxkaXY+CiAgICAgICAgICAgICAgICA8bGFiZWwgY2xhc3M9InNtYWxsIj5EYXRlPC9sYWJlbD4KICAgICAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJ0ZXN0X2F1dG9fZGF0ZSIgdmFsdWU9Ijw/cGhwIGVjaG8gbXJscHJfaCgkdGVzdERhdGVWYWx1ZSk7ID8+IiBwbGFjZWhvbGRlcj0iTU0vREQvWVlZWSI+CiAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICA8ZGl2PgogICAgICAgICAgICAgICAgPGxhYmVsIGNsYXNzPSJzbWFsbCI+VGltZTwvbGFiZWw+CiAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0idGVzdF9hdXRvX3RpbWUiIHZhbHVlPSI8P3BocCBlY2hvIG1ybHByX2goJHRlc3RUaW1lVmFsdWUpOyA/PiIgcGxhY2Vob2xkZXI9IkhIOk1NIEFNIj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9kaXY+CiAgICAgICAgPHAgY2xhc3M9InNtYWxsIj5XaGVuIG5vIHRlc3QgaGFzIGJlZW4gc2F2ZWQsIHRoZXNlIHN0YXJ0IGF0IHRoZSBjdXJyZW50IEVUIGRhdGUvdGltZS4gVXN1YWxseSB5b3Ugb25seSBuZWVkIHRvIGNoYW5nZSB0aGUgbWludXRlcy48L3A+CiAgICA8L2Rpdj4KPC9kaXY+Cjw/cGhwCiR0ZXN0U3RhdHVzPSdOb3Qgc2NoZWR1bGVkJzsKJHRlc3RLZXk9dHJpbSgoc3RyaW5nKSgkY2ZnWyd0ZXN0X2F1dG9fYXQnXT8/JycpKTsKaWYoIWVtcHR5KCRjZmdbJ3Rlc3RfYXV0b19lbmFibGVkJ10pICYmICR0ZXN0S2V5IT09JycpewogICAgJHRlc3RTdGF0dXM9bXJscHJfdGVzdF9hdXRvX3NlbnQoJHRlc3RLZXksTVJMX1BSX1RFU1RfVUlEKQogICAgICAgID8gJ1NFTlQg4oCUIGF1dG9tYXRpYyB0ZXN0IGNvbXBsZXRlZCcKICAgICAgICA6ICdBUk1FRCDigJQgd2FpdGluZyBmb3Igc2NoZWR1bGVyJzsKfQo/Pgo8ZGl2IGNsYXNzPSJiYW5uZXIgPD9waHAgZWNobyBzdHJwb3MoJHRlc3RTdGF0dXMsJ1NFTlQnKT09PTA/J29rJzonaW5mbyc7ID8+Ij48Yj5URVNUIEFVVE8gc3RhdHVzOjwvYj4gPD9waHAgZWNobyBtcmxwcl9oKCR0ZXN0U3RhdHVzKTsgPz48L2Rpdj4KPGRpdiBjbGFzcz0ic2F2ZS1yb3ciPgogICAgPGJ1dHRvbiBjbGFzcz0iYnRuIGJsdWUiIHR5cGU9InN1Ym1pdCIgbmFtZT0iYWN0aW9uIiB2YWx1ZT0ic2F2ZV9zZXR0aW5ncyI+U2F2ZSBEYXNoYm9hcmQgU2V0dGluZ3M8L2J1dHRvbj4KPC9kaXY+CjwvZGl2PgoKPGRpdiBjbGFzcz0iY2FyZCI+PGgyPkF1dG9tYXRpYyBSZW1pbmRlciBUaW1lczwvaDI+PHAgY2xhc3M9Im11dGVkIj5FbnRlciBIOk1NIGJlZm9yZSBkZWFkbGluZS48L3A+PGRpdiBjbGFzcz0iZzMiPjw/cGhwIGZvcigkaT0wOyRpPDM7JGkrKyk6Pz48ZGl2IGNsYXNzPSJzdGF0Ij48bGFiZWw+PGI+UmVtaW5kZXIgPD9waHAgZWNobyAkaSsxOz8+PC9iPjwvbGFiZWw+PGlucHV0IHR5cGU9InRleHQiIG5hbWU9Im9mZnNldDw/cGhwIGVjaG8gJGkrMTs/PiIgdmFsdWU9Ijw/cGhwIGVjaG8gJG9mZnNbJGldP21ybHByX2gobXJscHJfbWludXRlc190b19kdXJhdGlvbigoaW50KSRvZmZzWyRpXSkpOicnOz8+Ij48P3BocCBpZigkZGVhZGxpbmUgaW5zdGFuY2VvZiBEYXRlVGltZSYmJG9mZnNbJGldKTokc2xvdD1jbG9uZSAkZGVhZGxpbmU7JHNsb3QtPm1vZGlmeSgnLScuKGludCkkb2Zmc1skaV0uJyBtaW51dGVzJyk7Pz48ZGl2IGNsYXNzPSJzbWFsbCIgc3R5bGU9Im1hcmdpbi10b3A6N3B4Ij5Xb3VsZCBydW4gYXQgPD9waHAgZWNobyBtcmxwcl9oKCRzbG90LT5mb3JtYXQoJ2c6aSBBJykpOz8+IEVUPC9kaXY+PD9waHAgZW5kaWY7Pz48L2Rpdj48P3BocCBlbmRmb3I7Pz48L2Rpdj48ZGl2IGNsYXNzPSJiYW5uZXIgd2FybiI+QVVUTyArIExJVkUgcmVxdWlyZXMgdHlwaW5nIDxiPkVOQUJMRSBMSVZFIEFVVE88L2I+LjwvZGl2PjxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJsaXZlX2F1dG9fY29uZmlybSIgcGxhY2Vob2xkZXI9IkVOQUJMRSBMSVZFIEFVVE8iPjwvZGl2Pgo8ZGl2IGNsYXNzPSJjYXJkIj48aDI+RW1haWwgTWVzc2FnZTwvaDI+PGRpdiBjbGFzcz0iZzIiPjxkaXY+PGxhYmVsPjxiPlN1YmplY3Q8L2I+PC9sYWJlbD48aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ic3ViamVjdF90ZW1wbGF0ZSIgdmFsdWU9Ijw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSRjZmdbJ3N1YmplY3RfdGVtcGxhdGUnXSk7Pz4iPjxsYWJlbCBzdHlsZT0ibWFyZ2luLXRvcDoxMnB4Ij48Yj5NZXNzYWdlPC9iPjwvbGFiZWw+PHRleHRhcmVhIG5hbWU9ImJvZHlfdGVtcGxhdGUiPjw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSRjZmdbJ2JvZHlfdGVtcGxhdGUnXSk7Pz48L3RleHRhcmVhPjxkaXYgY2xhc3M9InNtYWxsIj5QbGFjZWhvbGRlcnM6IDxjb2RlPnt7eWVhcn19PC9jb2RlPiwgPGNvZGU+e3tzZWdtZW50X25hbWV9fTwvY29kZT4sIDxjb2RlPnt7ZGVhZGxpbmV9fTwvY29kZT4sIDxjb2RlPnt7dGVhbV9uYW1lfX08L2NvZGU+LCA8Y29kZT57e3RlYW1fcGFnZX19PC9jb2RlPi48L2Rpdj48L2Rpdj48ZGl2PjxsYWJlbD48Yj5DdXJyZW50IFByZXZpZXc8L2I+PC9sYWJlbD48ZGl2IGNsYXNzPSJwcmV2aWV3Ij48Yj48P3BocCBlY2hvIG1ybHByX2goJHByZXZpZXdTdWIpOz8+PC9iPgoKPD9waHAgZWNobyBtcmxwcl9oKCRwcmV2aWV3Qm9keSk7Pz48L2Rpdj48L2Rpdj48L2Rpdj48ZGl2IHN0eWxlPSJtYXJnaW4tdG9wOjE0cHgiPjxidXR0b24gY2xhc3M9ImJ0biBibHVlIiB0eXBlPSJzdWJtaXQiIG5hbWU9ImFjdGlvbiIgdmFsdWU9InNhdmVfc2V0dGluZ3MiPlNhdmUgRGFzaGJvYXJkIFNldHRpbmdzPC9idXR0b24+PC9kaXY+PC9kaXY+CjxkaXYgY2xhc3M9ImNhcmQiPjxoMj5DdXJyZW50IE1pc3NpbmcgUGlja3Mg4oCUIDw/cGhwIGVjaG8gJGNmZ1snc2NvcGUnXTs/PiBTY29wZTwvaDI+PHAgY2xhc3M9InNtYWxsIj5FYWNoIHRlYW0gZ2V0cyBpdHMgb3duIG1lc3NhZ2UuIFZpc2libGUgVG86IGlzIG1hbmxpdXNyYWNpbmdsZWFndWVAZ21haWwuY29tOyB0aGF0IHRlYW1cJ3MgZW1haWwgYWRkcmVzcyhlcykgYXJlIEJDQyByZWNpcGllbnRzLjwvcD48P3BocCBpZighJHJlY2lwaWVudHMpOj8+PGRpdiBjbGFzcz0iYmFubmVyIG9rIj5ObyB0ZWFtcyBpbiB0aGUgY3VycmVudCBzY29wZSBhcmUgbWlzc2luZyBwaWNrcy48L2Rpdj48P3BocCBlbHNlOj8+PHRhYmxlPjx0aGVhZD48dHI+PHRoPlNlbmQ8L3RoPjx0aD5UZWFtPC90aD48dGg+VXNlcjwvdGg+PHRoPkVtYWlsKHMpPC90aD48L3RyPjwvdGhlYWQ+PHRib2R5Pjw/cGhwIGZvcmVhY2goJHJlY2lwaWVudHMgYXMgJHIpOj8+PHRyPjx0ZD48aW5wdXQgdHlwZT0iY2hlY2tib3giIG5hbWU9InJlY2lwaWVudF9pZHNbXSIgdmFsdWU9Ijw/cGhwIGVjaG8gKGludCkkclsndXNlcklEJ107Pz4iIGNoZWNrZWQ+PC90ZD48dGQ+PD9waHAgZWNobyBtcmxwcl9oKChzdHJpbmcpKCRyWyd0ZWFtTmFtZSddPz8nJykpOz8+PC90ZD48dGQ+PD9waHAgZWNobyBtcmxwcl9oKChzdHJpbmcpKCRyWyd1c2VyTmFtZSddPz8nJykuJyAoSUQgJy4oaW50KSRyWyd1c2VySUQnXS4nKScpOz8+PC90ZD48dGQ+PD9waHAgZWNobyBtcmxwcl9oKGltcGxvZGUoJywgJywoYXJyYXkpJHJbJ2VtYWlscyddKSk7Pz48L3RkPjwvdHI+PD9waHAgZW5kZm9yZWFjaDs/PjwvdGJvZHk+PC90YWJsZT48P3BocCBpZigkY2ZnWydzY29wZSddPT09J0xJVkUnKTo/PjxkaXYgY2xhc3M9ImJhbm5lciB3YXJuIj5MSVZFIG1hbnVhbCBzZW5kIHJlcXVpcmVzIHR5cGluZyA8Yj5TRU5EIExJVkU8L2I+LjwvZGl2PjxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJsaXZlX3NlbmRfY29uZmlybSIgcGxhY2Vob2xkZXI9IlNFTkQgTElWRSI+PD9waHAgZW5kaWY7Pz48ZGl2IHN0eWxlPSJtYXJnaW4tdG9wOjE0cHgiPjxidXR0b24gY2xhc3M9ImJ0biBncmVlbiIgdHlwZT0ic3VibWl0IiBuYW1lPSJhY3Rpb24iIHZhbHVlPSJzZW5kX21hbnVhbCIgPD9waHAgZWNobyAkcmVhZHk/Jyc6J2Rpc2FibGVkJzs/PiBvbmNsaWNrPSJyZXR1cm4gY29uZmlybSgnU2VuZCByZW1pbmRlciBub3cgdG8gY2hlY2tlZCByZWNpcGllbnQocyk/Jyk7Ij5TZW5kIFNlbGVjdGVkIFJlbWluZGVyIE5vdzwvYnV0dG9uPjwvZGl2Pjw/cGhwIGVuZGlmOz8+PC9kaXY+PC9mb3JtPgo8ZGl2IGNsYXNzPSJjYXJkIj48aDI+QXV0b21hdGljIFNjaGVkdWxlciBTdGF0dXM8L2gyPjw/cGhwIGlmKCEkc3RhdGUpOj8+PGRpdiBjbGFzcz0ibXV0ZWQiPk5vIHNjaGVkdWxlciBjaGVjayByZWNvcmRlZCB5ZXQuPC9kaXY+PD9waHAgZWxzZTo/Pjx0YWJsZT48dHI+PHRoPkxhc3QgY2hlY2s8L3RoPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHN0YXRlWydjaGVja2VkX2F0J10/PycnKSk7Pz48L3RkPjwvdHI+PHRyPjx0aD5TdGF0dXM8L3RoPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHN0YXRlWydzdGF0dXMnXT8/JycpKTs/PjwvdGQ+PC90cj48dHI+PHRoPk1vZGUgLyBTY29wZTwvdGg+PHRkPjw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSgkc3RhdGVbJ21vZGUnXT8/JycpLicgLyAnLihzdHJpbmcpKCRzdGF0ZVsnc2NvcGUnXT8/JycpKTs/PjwvdGQ+PC90cj48dHI+PHRoPlN1bW1hcnk8L3RoPjx0ZD48P3BocCBlY2hvIG1ybHByX2goanNvbl9lbmNvZGUoJHN0YXRlWydzdW1tYXJ5J10/P1tdLEpTT05fVU5FU0NBUEVEX1NMQVNIRVMpKTs/PjwvdGQ+PC90cj48L3RhYmxlPjw/cGhwIGVuZGlmOz8+PC9kaXY+CjxkaXYgY2xhc3M9ImNhcmQiPjxoMj5SZWNlbnQgU2VuZCBIaXN0b3J5PC9oMj48P3BocCBpZighJGxvZ3MpOj8+PGRpdiBjbGFzcz0ibXV0ZWQiPk5vIHJlbWluZGVyIHNlbmRzIHJlY29yZGVkIHlldC48L2Rpdj48P3BocCBlbHNlOj8+PHRhYmxlPjx0aGVhZD48dHI+PHRoPlRpbWU8L3RoPjx0aD5LaW5kPC90aD48dGg+U2NvcGU8L3RoPjx0aD5UZWFtPC90aD48dGg+UmVzdWx0PC90aD48dGg+T2Zmc2V0PC90aD48L3RyPjwvdGhlYWQ+PHRib2R5Pjw/cGhwIGZvcmVhY2goJGxvZ3MgYXMgJHIpOj8+PHRyPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHJbJ3NlbnRfYXQnXT8/JycpKTs/PjwvdGQ+PHRkPjw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSgkclsnc2VuZF9raW5kJ10/PycnKSk7Pz48L3RkPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHJbJ3Njb3BlJ10/PycnKSk7Pz48L3RkPjx0ZD48P3BocCBlY2hvIG1ybHByX2goKHN0cmluZykoJHJbJ3RlYW1OYW1lJ10/PycnKS4nIChJRCAnLihpbnQpKCRyWyd1c2VySUQnXT8/MCkuJyknKTs/PjwvdGQ+PHRkPjw/cGhwIGVjaG8gbXJscHJfaCgoc3RyaW5nKSgkclsncmVzdWx0J10/PycnKSk7Pz48L3RkPjx0ZD48P3BocCBlY2hvIGlzc2V0KCRyWydvZmZzZXRfbWludXRlcyddKSYmJHJbJ29mZnNldF9taW51dGVzJ10hPT1udWxsP21ybHByX2gobXJscHJfbWludXRlc190b19kdXJhdGlvbigoaW50KSRyWydvZmZzZXRfbWludXRlcyddKSk6J01hbnVhbCc7Pz48L3RkPjwvdHI+PD9waHAgZW5kZm9yZWFjaDs/PjwvdGJvZHk+PC90YWJsZT48P3BocCBlbmRpZjs/PjwvZGl2Pgo8ZGl2IGNsYXNzPSJzbWFsbCIgc3R5bGU9InRleHQtYWxpZ246cmlnaHQiPnBpY2tfcmVtaW5kZXJfZGFzaGJvYXJkLnBocCB8IFZFUlNJT04gdjAwNDwvZGl2PjwvZGl2PjwvYm9keT48L2h0bWw+Cg==';

function prv4_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function prv4_decode(string $b64): string {
    $d = base64_decode($b64, true);
    return is_string($d) ? $d : '';
}

function prv4_atomic(string $path, string $content): array {
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

function prv4_lint(string $path): array {
    $cmd = escapeshellarg(PHP_BINARY ?: 'php') . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $out = [];
    $code = 1;
    @exec($cmd, $out, $code);

    if ($code === 0) return [true, implode("\n", $out)];
    if (!$out) return [null, 'PHP CLI syntax check unavailable on this host.'];
    return [false, implode("\n", $out)];
}

$dc = is_file($dashboard) ? (string)@file_get_contents($dashboard) : '';
$hc = is_file($helper) ? (string)@file_get_contents($helper) : '';
$sc = is_file($scheduler) ? (string)@file_get_contents($scheduler) : '';

$checks = [
    'pick_reminder_dashboard.php exists' => is_file($dashboard),
    'pick_reminder_dashboard.php writable' => is_file($dashboard) && is_writable($dashboard),
    'Expected dashboard v003 baseline' =>
        strpos($dc, 'VERSION v003 | Admin-only') !== false
        && strpos($dc, 'input type="datetime-local" name="test_auto_at"') !== false
        && strpos($dc, 'pick_reminder_dashboard.php | VERSION v003') !== false,
    'Helper remains expected v003' => is_file($helper) && strpos($hc, 'VERSION: v003') !== false,
    'Scheduler remains expected v002' => is_file($scheduler) && strpos($sc, 'VERSION: v002') !== false,
    'public_html writable' => is_writable($root),
    'Embedded v004 payload valid' =>
        strpos(prv4_decode($payload), 'VERSION v004 | Admin-only') !== false
        && strpos(prv4_decode($payload), 'Save Dashboard Settings') !== false
        && strpos(prv4_decode($payload), 'test_auto_date') !== false
        && strpos(prv4_decode($payload), 'test_auto_time') !== false,
];

$ready = !in_array(false, $checks, true);

$msg = '';
$type = '';

if (($_POST['action'] ?? '') === 'apply') {
    if (!$ready) {
        $msg = 'APPLY BLOCKED — required preflight checks are not all passing.';
        $type = 'err';
    } else {
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
            $msg = 'APPLY FAILED — could not create rollback folder.';
            $type = 'err';
        } elseif (!@copy($dashboard, $dashboardBackup)) {
            $msg = 'APPLY FAILED — could not create rollback copy.';
            $type = 'err';
        } else {
            [$ok, $why] = prv4_atomic($dashboard, prv4_decode($payload));
            $err = '';

            if (!$ok) {
                $err = $why;
            } else {
                [$lintOk, $report] = prv4_lint($dashboard);
                if ($lintOk === false) {
                    $err = 'PHP syntax check failed: ' . $report;
                }
            }

            if ($err !== '') {
                @copy($dashboardBackup, $dashboard);
                $msg = 'APPLY FAILED — ' . $err . ' Original dashboard restored.';
                $type = 'err';
            } else {
                $msg = 'INSTALL COMPLETE — Pick Reminder Dashboard is now v004. No email was sent.';
                $type = 'ok';
            }
        }
    }
}

if (($_POST['action'] ?? '') === 'rollback') {
    if (!is_file($dashboardBackup)) {
        $msg = 'ROLLBACK NOT AVAILABLE — no rollback copy exists yet.';
        $type = 'err';
    } else {
        $ok = @copy($dashboardBackup, $dashboard);

        if ($ok) {
            $msg = 'ROLLBACK COMPLETE — Pick Reminder Dashboard restored to v003.';
            $type = 'ok';
        } else {
            $msg = 'ROLLBACK FAILED — could not restore dashboard.';
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
<title>Install Pick Reminder Dashboard v004</title>
<style>
:root{color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;background:#0d1013;color:#eef2f6;font-family:Arial,Helvetica,sans-serif;font-size:14px}
.wrap{max-width:1040px;margin:20px auto 50px;padding:0 16px}
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
<h1>Install Pick Reminder Dashboard v004</h1>
<div class="sub">Small usability iteration for the AUTO TEST controls. This changes only the dashboard; the tested helper, scheduler, mailer path, and safety logic remain untouched.</div>
<div class="info" style="margin-top:14px">
<b>AUTO TEST date/time:</b> separate Date and Time fields.<br>
<b>Default:</b> if no test is saved, both fields populate with current ET so normally you only change the minutes.<br>
<b>Save:</b> a Save Dashboard Settings button now sits directly in the AUTO TEST section.<br>
<b>No database changes. No email is sent by this installer.</b>
</div>
</div>

<?php if ($msg !== ''): ?>
<div class="<?php echo $type === 'ok' ? 'ok' : 'err'; ?>" style="margin-bottom:14px">
    <?php echo prv4_h($msg); ?>
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
    <td><?php echo prv4_h($label); ?></td>
    <td class="<?php echo $ok ? 'pass' : 'fail'; ?>"><?php echo $ok ? 'PASS' : 'FAIL'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card">
<h2>Apply</h2>
<p class="sub">Backs up the current v003 dashboard first, installs v004, then syntax-checks it.</p>
<form method="post">
<input type="hidden" name="action" value="apply">
<button class="btn <?php echo $ready ? 'green' : 'disabled'; ?>" <?php echo $ready ? '' : 'disabled'; ?>>Apply Pick Reminder Dashboard v004</button>
</form>
</div>

<div class="card">
<h2>Rollback</h2>
<form method="post" onsubmit="return confirm('Rollback Pick Reminder Dashboard v004?');">
<input type="hidden" name="action" value="rollback">
<button class="btn red">Rollback</button>
</form>
</div>

<div class="small" style="text-align:right">install_pick_reminder_dashboard_v004_20260906_035958am.php</div>
</div>
</body>
</html>
