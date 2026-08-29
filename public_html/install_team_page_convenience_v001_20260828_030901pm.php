<?php
declare(strict_types=1);

/**
 * install_team_page_convenience_v001.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/28/2026 3:09:01 pm
 *
 * PURPOSE:
 * Small production Team-page convenience/UI upgrade:
 *   - remember the two +/- panel states in this browser
 *   - indent "Hi <name> ..." to align with panel content
 *   - enlarge/breathe the 3-part sticky header while preserving left/center/right alignment
 *   - add an optional JSON-managed announcement/news panel directly below the greeting
 *   - automatically linkify plain http:// and https:// URLs in announcement text
 *
 * FILES:
 *   /team.php                              v035 -> v036
 *   /mrl_team/admin_team_page_content.php v004 -> v005
 *   /mrl_team/mrl_team_page_content.json  add announcement_panel if missing
 *
 * SAFETY:
 * - exact Git blob SHA-1 gate for production team.php v035
 * - exact SHA-256 gate for admin_team_page_content.php v004
 * - JSON must decode successfully before any write
 * - backups of all 3 files before any replacement
 * - all-or-nothing rollback on write/postflight failure
 * - no pick, LP, RP/RD, scoring, chart, scheduler, profile, theme-selection, or DB logic changes
 *
 * LOCATION:
 * Put this installer in public_html/.
 */

date_default_timezone_set('America/New_York');

const TEAM_BASE_GIT_BLOB_SHA1 = '96d0bd4e60407c132261767ff1b78f384e28432d';
const ADMIN_BASE_SHA256 = '838ec28c0a62fb153fb9877035f4e3bf89fdab0f5dbfa3fc5afcbb6620682cdc';
const ADMIN_NEW_SHA256 = '6aff20dc9342a74ad23a5bfb24d25737ce34f6fd4d0e925143a082649a55150d';

$teamPath = __DIR__ . '/team.php';
$adminPath = __DIR__ . '/mrl_team/admin_team_page_content.php';
$jsonPath = __DIR__ . '/mrl_team/mrl_team_page_content.json';

$adminReplacement = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogYWRtaW5fdGVhbV9wYWdlX2NvbnRlbnQucGhwCiAqCiAqIFZFUlNJT046IHYwMDUKICogTEFTVCBNT0RJRklFRDogOC8yOC8yMDI2IDM6MDk6MDEgcG0KICoKICogQWRtaW4tb25seSBlZGl0b3IgZm9yIEpTT04tZHJpdmVuIFRlYW0gUGFnZSBjb250ZW50LgogKgogKiBDSEFOR0VMT0c6CiAqCiAqIHYwMDUgKDgvMjgvMjAyNiAzOjA5OjAxIHBtKQogKiAtIE5FVzogQWRkcyBhbiBvcHRpb25hbCBUZWFtIFBhZ2UgYW5ub3VuY2VtZW50L25ld3MgcGFuZWwgZWRpdG9yLgogKiAtIE5FVzogQW5ub3VuY2VtZW50IHBhbmVsIHN1cHBvcnRzIEVuYWJsZWQvRGlzYWJsZWQsIGFuIG9wdGlvbmFsIHRpdGxlLCBhbmQgZnJlZWZvcm0gbXVsdGktbGluZSB0ZXh0LgogKiAtIE5FVzogUGxhaW4gaHR0cDovLyBhbmQgaHR0cHM6Ly8gVVJMcyBlbnRlcmVkIGluIGFubm91bmNlbWVudCB0ZXh0IGFyZSBhdXRvbWF0aWNhbGx5IGNsaWNrYWJsZSBvbiB0ZWFtLnBocC4KICogLSBDSEFOR0U6IENvbnRlbnQgc2NoZW1hIGFkdmFuY2VzIHRvIHYzIHdoaWxlIHByZXNlcnZpbmcgYWxsIGZvdXIgZXhpc3RpbmcgbGluayBwYW5lbHMuCiAqIC0gUFJFU0VSVkU6IEV4aXN0aW5nIGxpbmsgb3JkZXJpbmcsIGVuYWJsZWQvbmV3LXRhYi9yZW1vdmUgY29udHJvbHMsIGF1dGhlbnRpY2F0aW9uLCBDU1JGLCBiYWNrdXAsIGFuZCBKU09OIGJlaGF2aW9yLgogKgogKiB2MDA0ICg4LzI4LzIwMjYgMjozNjo1OCBwbSkKICogLSBGSVg6IFByb2R1Y3Rpb25pemVkIHRoZSByZXR1cm4gbGluayBmcm9tIHRoZSBUZWFtIFBhZ2UgQ29udGVudCBtYW5hZ2VyLgogKiAtIENIQU5HRTogTGluayBsYWJlbCBjaGFuZ2VkIGZyb20gIlRlYW0gUmVkZXNpZ24iIHRvICJUZWFtIi4KICogLSBDSEFOR0U6IExpbmsgdGFyZ2V0IGNoYW5nZWQgZnJvbSAvdGVhbV9yZWRlc2lnbi5waHAgdG8gL3RlYW0ucGhwLgogKiAtIFBSRVNFUlZFOiBObyBjb250ZW50LW1hbmFnZXIsIEpTT04sIG9yZGVyaW5nLCBhdXRoZW50aWNhdGlvbiwgb3IgQWRtaW4gYmVoYXZpb3IgY2hhbmdlcy4KICoKICogdjAwMyAoOC8yNy8yMDI2IDY6NTc6MjggcG0pCiAqIC0gRklYOiBFdmVyeSBlZGl0YWJsZSByb3cgbm93IHVzZXMgZXhwbGljaXQgaW5kZXhlZCBmaWVsZCBuYW1lcy4KICogLSBGSVg6IEVuYWJsZWQvTmV3LXRhYi9SZW1vdmUgY2hlY2tib3hlcyByZW1haW4gYWxpZ25lZCBhZnRlciBVcC9Eb3duIG1vdmVzLgogKiAtIFBSRVNFUlZFOiBGb3VyIGVkaXRhYmxlIHBhbmVscyBhbmQgZml4ZWQgTWFuYWdlIFRlYW0gUGFnZSBDb250ZW50IGNvbnRyb2wuCiAqCiAqIHYwMDIgKDgvMjcvMjAyNiA2OjMzOjEyIHBtKQogKiAtIEFkZGVkIGFsbCBmb3VyIHBhbmVscyBhbmQgVXAvRG93biBvcmRlcmluZy4KICovCgpzZXNzaW9uX3N0YXJ0KCk7CgpyZXF1aXJlX29uY2UgZGlybmFtZShfX0RJUl9fKSAuICcvY2xhc3MudXNlci5waHAnOwokdXNlcl9ob21lID0gbmV3IFVTRVIoKTsKCmlmICghJHVzZXJfaG9tZS0+aXNfbG9nZ2VkX2luKCkpIHsKICAgICR1c2VyX2hvbWUtPnJlZGlyZWN0KCcvbG9naW4ucGhwJyk7CiAgICBleGl0Owp9CgpkYXRlX2RlZmF1bHRfdGltZXpvbmVfc2V0KCdBbWVyaWNhL05ld19Zb3JrJyk7CnJlcXVpcmUgZGlybmFtZShfX0RJUl9fKSAuICcvY29uZmlnLnBocCc7CnJlcXVpcmUgZGlybmFtZShfX0RJUl9fKSAuICcvY29uZmlnX21ybC5waHAnOwoKJHVpZCA9IChpbnQpKCRfU0VTU0lPTlsndXNlclNlc3Npb24nXSA/PyAwKTsKaWYgKCFpc0FkbWluKCR1aWQpKSB7CiAgICBodHRwX3Jlc3BvbnNlX2NvZGUoNDAzKTsKICAgIGV4aXQoJ0FkbWluIGFjY2VzcyByZXF1aXJlZC4nKTsKfQoKJGNvbnRlbnRQYXRoID0gX19ESVJfXyAuICcvbXJsX3RlYW1fcGFnZV9jb250ZW50Lmpzb24nOwoKZnVuY3Rpb24gYXRwY19oKCR2KTogc3RyaW5nIHsgcmV0dXJuIGh0bWxzcGVjaWFsY2hhcnMoKHN0cmluZykkdiwgRU5UX1FVT1RFUywgJ1VURi04Jyk7IH0KCmZ1bmN0aW9uIGF0cGNfbG9hZChzdHJpbmcgJHBhdGgpOiBhcnJheQp7CiAgICAkcmF3ID0gaXNfZmlsZSgkcGF0aCkgPyBmaWxlX2dldF9jb250ZW50cygkcGF0aCkgOiAnJzsKICAgICRkYXRhID0gaXNfc3RyaW5nKCRyYXcpID8ganNvbl9kZWNvZGUoJHJhdywgdHJ1ZSkgOiBudWxsOwogICAgcmV0dXJuIGlzX2FycmF5KCRkYXRhKSA/ICRkYXRhIDogW107Cn0KCmZ1bmN0aW9uIGF0cGNfY2xlYW5fdXJsKHN0cmluZyAkdXJsKTogc3RyaW5nCnsKICAgICR1cmwgPSB0cmltKCR1cmwpOwogICAgaWYgKCR1cmwgPT09ICcnKSByZXR1cm4gJyc7CiAgICBpZiAoJHVybFswXSA9PT0gJy8nKSByZXR1cm4gJHVybDsKICAgIGlmIChwcmVnX21hdGNoKCd+Xmh0dHBzPzovL35pJywgJHVybCkpIHJldHVybiAkdXJsOwogICAgcmV0dXJuICcnOwp9CgpmdW5jdGlvbiBhdHBjX2J1aWxkX3BhbmVsKGFycmF5ICRwb3N0LCBzdHJpbmcgJGtleSwgc3RyaW5nICRmYWxsYmFjayk6IGFycmF5CnsKICAgICR0aXRsZSA9IHRyaW0oKHN0cmluZykoJHBvc3RbJGtleSAuICdfdGl0bGUnXSA/PyAkZmFsbGJhY2spKTsKICAgIGlmICgkdGl0bGUgPT09ICcnKSAkdGl0bGUgPSAkZmFsbGJhY2s7CgogICAgJGxhYmVscyA9IGlzX2FycmF5KCRwb3N0WyRrZXkgLiAnX2xhYmVsJ10gPz8gbnVsbCkgPyAkcG9zdFska2V5IC4gJ19sYWJlbCddIDogW107CiAgICAkdXJscyA9IGlzX2FycmF5KCRwb3N0WyRrZXkgLiAnX3VybCddID8/IG51bGwpID8gJHBvc3RbJGtleSAuICdfdXJsJ10gOiBbXTsKICAgICRlbmFibGVkID0gaXNfYXJyYXkoJHBvc3RbJGtleSAuICdfZW5hYmxlZCddID8/IG51bGwpID8gJHBvc3RbJGtleSAuICdfZW5hYmxlZCddIDogW107CiAgICAkbmV3dGFiID0gaXNfYXJyYXkoJHBvc3RbJGtleSAuICdfbmV3X3RhYiddID8/IG51bGwpID8gJHBvc3RbJGtleSAuICdfbmV3X3RhYiddIDogW107CiAgICAkcmVtb3ZlID0gaXNfYXJyYXkoJHBvc3RbJGtleSAuICdfcmVtb3ZlJ10gPz8gbnVsbCkgPyAkcG9zdFska2V5IC4gJ19yZW1vdmUnXSA6IFtdOwoKICAgICRpdGVtcyA9IFtdOwogICAgZm9yZWFjaCAoJGxhYmVscyBhcyAkaSA9PiAkbGFiZWxSYXcpIHsKICAgICAgICBpZiAoIWVtcHR5KCRyZW1vdmVbJGldKSkgY29udGludWU7CgogICAgICAgICRsYWJlbCA9IHRyaW0oKHN0cmluZykkbGFiZWxSYXcpOwogICAgICAgICR1cmwgPSBhdHBjX2NsZWFuX3VybCgoc3RyaW5nKSgkdXJsc1skaV0gPz8gJycpKTsKICAgICAgICBpZiAoJGxhYmVsID09PSAnJyB8fCAkdXJsID09PSAnJykgY29udGludWU7CgogICAgICAgICRpdGVtc1tdID0gWwogICAgICAgICAgICAnbGFiZWwnID0+ICRsYWJlbCwKICAgICAgICAgICAgJ3VybCcgPT4gJHVybCwKICAgICAgICAgICAgJ2VuYWJsZWQnID0+ICFlbXB0eSgkZW5hYmxlZFskaV0pLAogICAgICAgICAgICAnbmV3X3RhYicgPT4gIWVtcHR5KCRuZXd0YWJbJGldKSwKICAgICAgICBdOwogICAgfQoKICAgIHJldHVybiBbJ3RpdGxlJyA9PiAkdGl0bGUsICdpdGVtcycgPT4gJGl0ZW1zXTsKfQoKZnVuY3Rpb24gYXRwY19idWlsZF9hbm5vdW5jZW1lbnQoYXJyYXkgJHBvc3QpOiBhcnJheQp7CiAgICByZXR1cm4gWwogICAgICAgICdlbmFibGVkJyA9PiAhZW1wdHkoJHBvc3RbJ2Fubm91bmNlbWVudF9lbmFibGVkJ10pLAogICAgICAgICd0aXRsZScgPT4gdHJpbSgoc3RyaW5nKSgkcG9zdFsnYW5ub3VuY2VtZW50X3RpdGxlJ10gPz8gJycpKSwKICAgICAgICAnY29udGVudCcgPT4gdHJpbSgoc3RyaW5nKSgkcG9zdFsnYW5ub3VuY2VtZW50X2NvbnRlbnQnXSA/PyAnJykpLAogICAgXTsKfQoKaWYgKCFpc3NldCgkX1NFU1NJT05bJ2F0cGNfY3NyZiddKSkgewogICAgJF9TRVNTSU9OWydhdHBjX2NzcmYnXSA9IGJpbjJoZXgocmFuZG9tX2J5dGVzKDI0KSk7Cn0KCiRkYXRhID0gYXRwY19sb2FkKCRjb250ZW50UGF0aCk7CiRtZXNzYWdlID0gJyc7CgppZiAoJF9TRVJWRVJbJ1JFUVVFU1RfTUVUSE9EJ10gPT09ICdQT1NUJykgewogICAgaWYgKCFoYXNoX2VxdWFscygoc3RyaW5nKSRfU0VTU0lPTlsnYXRwY19jc3JmJ10sIChzdHJpbmcpKCRfUE9TVFsnY3NyZiddID8/ICcnKSkpIHsKICAgICAgICAkbWVzc2FnZSA9ICdTYXZlIGJsb2NrZWQ6IHNlY3VyaXR5IHRva2VuIG1pc21hdGNoLic7CiAgICB9IGVsc2UgewogICAgICAgICRuZXcgPSBbCiAgICAgICAgICAgICdzY2hlbWFfdmVyc2lvbicgPT4gMywKICAgICAgICAgICAgJ3VwZGF0ZWRfYXQnID0+IGRhdGUoJ1ktbS1kIEg6aTpzJyksCiAgICAgICAgICAgICdhbm5vdW5jZW1lbnRfcGFuZWwnID0+IGF0cGNfYnVpbGRfYW5ub3VuY2VtZW50KCRfUE9TVCksCiAgICAgICAgICAgICdhZG1pbl9sZWFndWVfcGFuZWwnID0+IGF0cGNfYnVpbGRfcGFuZWwoJF9QT1NULCAnYWRtaW5fbGVhZ3VlJywgJ0xlYWd1ZSAmIFRlYW0nKSwKICAgICAgICAgICAgJ2FkbWluX2hvc3RpbmdfcGFuZWwnID0+IGF0cGNfYnVpbGRfcGFuZWwoJF9QT1NULCAnYWRtaW5faG9zdGluZycsICdIb3N0aW5nICYgSW5mcmFzdHJ1Y3R1cmUnKSwKICAgICAgICAgICAgJ2xlYWd1ZV9wYW5lbCcgPT4gYXRwY19idWlsZF9wYW5lbCgkX1BPU1QsICdsZWFndWUnLCAnTGVhZ3VlIEluZm9ybWF0aW9uJyksCiAgICAgICAgICAgICd0ZWFtX3BhbmVsJyA9PiBhdHBjX2J1aWxkX3BhbmVsKCRfUE9TVCwgJ3RlYW0nLCAnVGVhbSBNZW51JyksCiAgICAgICAgXTsKCiAgICAgICAgJGJhY2t1cERpciA9IGRpcm5hbWUoX19ESVJfXykgLiAnL19taWdyYXRpb25fYmFja3Vwcy90ZWFtX3BhZ2VfY29udGVudF8nIC4gZGF0ZSgnWW1kX0hpcycpOwogICAgICAgICRiYWNrdXBPayA9IHRydWU7CiAgICAgICAgaWYgKGlzX2ZpbGUoJGNvbnRlbnRQYXRoKSkgewogICAgICAgICAgICAkYmFja3VwT2sgPSAoaXNfZGlyKCRiYWNrdXBEaXIpIHx8IG1rZGlyKCRiYWNrdXBEaXIsIDA3NTUsIHRydWUpKQogICAgICAgICAgICAgICAgJiYgY29weSgkY29udGVudFBhdGgsICRiYWNrdXBEaXIgLiAnL21ybF90ZWFtX3BhZ2VfY29udGVudC5qc29uJyk7CiAgICAgICAgfQoKICAgICAgICAkanNvbiA9IGpzb25fZW5jb2RlKCRuZXcsIEpTT05fUFJFVFRZX1BSSU5UIHwgSlNPTl9VTkVTQ0FQRURfU0xBU0hFUyk7CiAgICAgICAgJG9rID0gJGJhY2t1cE9rICYmIGlzX3N0cmluZygkanNvbikKICAgICAgICAgICAgJiYgZmlsZV9wdXRfY29udGVudHMoJGNvbnRlbnRQYXRoLCAkanNvbiAuIFBIUF9FT0wsIExPQ0tfRVgpICE9PSBmYWxzZTsKCiAgICAgICAgJG1lc3NhZ2UgPSAkb2sKICAgICAgICAgICAgPyAnVGVhbSBwYWdlIGNvbnRlbnQgc2F2ZWQuIEV4aXN0aW5nIEpTT04gd2FzIGJhY2tlZCB1cCBmaXJzdC4nCiAgICAgICAgICAgIDogJ1NhdmUgZmFpbGVkLic7CiAgICAgICAgJGRhdGEgPSBhdHBjX2xvYWQoJGNvbnRlbnRQYXRoKTsKICAgIH0KfQoKZnVuY3Rpb24gYXRwY19yb3dzKHN0cmluZyAka2V5LCBhcnJheSAkaXRlbXMpOiB2b2lkCnsKICAgIGZvcmVhY2ggKCRpdGVtcyBhcyAkaSA9PiAkaXQpIHsKICAgICAgICBlY2hvICc8dHI+JzsKICAgICAgICBlY2hvICc8dGQgY2xhc3M9Im9yZGVyIj48YnV0dG9uIHR5cGU9ImJ1dHRvbiIgY2xhc3M9Im1pbmkiIG9uY2xpY2s9Im1vdmVSb3codGhpcywtMSkiPuKGkTwvYnV0dG9uPjxidXR0b24gdHlwZT0iYnV0dG9uIiBjbGFzcz0ibWluaSIgb25jbGljaz0ibW92ZVJvdyh0aGlzLDEpIj7ihpM8L2J1dHRvbj48L3RkPic7CiAgICAgICAgZWNobyAnPHRkPjxpbnB1dCBkYXRhLXJvbGU9ImxhYmVsIiBuYW1lPSInIC4gYXRwY19oKCRrZXkpIC4gJ19sYWJlbFsnIC4gJGkgLiAnXSIgdmFsdWU9IicgLiBhdHBjX2goJGl0WydsYWJlbCddID8/ICcnKSAuICciPjwvdGQ+JzsKICAgICAgICBlY2hvICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0idXJsIiBuYW1lPSInIC4gYXRwY19oKCRrZXkpIC4gJ191cmxbJyAuICRpIC4gJ10iIHZhbHVlPSInIC4gYXRwY19oKCRpdFsndXJsJ10gPz8gJycpIC4gJyI+PC90ZD4nOwogICAgICAgIGVjaG8gJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJlbmFibGVkIiBuYW1lPSInIC4gYXRwY19oKCRrZXkpIC4gJ19lbmFibGVkWycgLiAkaSAuICddIiB2YWx1ZT0iMSIgdHlwZT0iY2hlY2tib3giICcgLiAoIWVtcHR5KCRpdFsnZW5hYmxlZCddKSA/ICdjaGVja2VkJyA6ICcnKSAuICc+PC90ZD4nOwogICAgICAgIGVjaG8gJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJuZXd0YWIiIG5hbWU9IicgLiBhdHBjX2goJGtleSkgLiAnX25ld190YWJbJyAuICRpIC4gJ10iIHZhbHVlPSIxIiB0eXBlPSJjaGVja2JveCIgJyAuICghZW1wdHkoJGl0WyduZXdfdGFiJ10pID8gJ2NoZWNrZWQnIDogJycpIC4gJz48L3RkPic7CiAgICAgICAgZWNobyAnPHRkPjxpbnB1dCBkYXRhLXJvbGU9InJlbW92ZSIgbmFtZT0iJyAuIGF0cGNfaCgka2V5KSAuICdfcmVtb3ZlWycgLiAkaSAuICddIiB2YWx1ZT0iMSIgdHlwZT0iY2hlY2tib3giPjwvdGQ+JzsKICAgICAgICBlY2hvICc8L3RyPic7CiAgICB9Cn0KCiRwYW5lbHMgPSBbCiAgICAnYWRtaW5fbGVhZ3VlJyA9PiBbJ2RhdGEnPT4nYWRtaW5fbGVhZ3VlX3BhbmVsJywnaGVhZGluZyc9PidBZG1pbiDCtyBMZWFndWUgJiBUZWFtJ10sCiAgICAnYWRtaW5faG9zdGluZycgPT4gWydkYXRhJz0+J2FkbWluX2hvc3RpbmdfcGFuZWwnLCdoZWFkaW5nJz0+J0FkbWluIMK3IEhvc3RpbmcgJiBJbmZyYXN0cnVjdHVyZSddLAogICAgJ2xlYWd1ZScgPT4gWydkYXRhJz0+J2xlYWd1ZV9wYW5lbCcsJ2hlYWRpbmcnPT4nTGVhZ3VlIEluZm9ybWF0aW9uJ10sCiAgICAndGVhbScgPT4gWydkYXRhJz0+J3RlYW1fcGFuZWwnLCdoZWFkaW5nJz0+J1RlYW0gTWVudSddLApdOwo/PjwhZG9jdHlwZSBodG1sPgo8aHRtbD48aGVhZD48bWV0YSBjaGFyc2V0PSJ1dGYtOCI+PG1ldGEgbmFtZT0idmlld3BvcnQiIGNvbnRlbnQ9IndpZHRoPWRldmljZS13aWR0aCxpbml0aWFsLXNjYWxlPTEiPgo8dGl0bGU+TWFuYWdlIFRlYW0gUGFnZSBDb250ZW50PC90aXRsZT4KPHN0eWxlPgoqe2JveC1zaXppbmc6Ym9yZGVyLWJveH1ib2R5e21hcmdpbjowO2JhY2tncm91bmQ6IzE1MTUxNTtjb2xvcjojZWVlO2ZvbnQtZmFtaWx5OlRhaG9tYSxWZXJkYW5hLFNlZ29lIFVJLHNhbnMtc2VyaWZ9Ci53cmFwe3dpZHRoOjk0JTttYXgtd2lkdGg6MTQwMHB4O21hcmdpbjoyMHB4IGF1dG99LmNhcmR7YmFja2dyb3VuZDojMjAyMDIwO2JvcmRlcjoxcHggc29saWQgIzU1NTtib3JkZXItcmFkaXVzOjE0cHg7cGFkZGluZzoxOHB4O21hcmdpbi1ib3R0b206MTZweH0KaDEsaDJ7Y29sb3I6I2VmYzk4Mn1he2NvbG9yOiM3NmNmZmZ9Lm5vdGV7cGFkZGluZzoxMXB4O2JvcmRlcjoxcHggc29saWQgIzU1NTtib3JkZXItcmFkaXVzOjlweDtiYWNrZ3JvdW5kOiMxNzE3MTd9CnRhYmxle3dpZHRoOjEwMCU7Ym9yZGVyLWNvbGxhcHNlOmNvbGxhcHNlO21hcmdpbi10b3A6MTJweH10aCx0ZHtib3JkZXItYm90dG9tOjFweCBzb2xpZCAjNDQ0O3BhZGRpbmc6N3B4O3RleHQtYWxpZ246bGVmdH0KdGQgaW5wdXQ6bm90KFt0eXBlPWNoZWNrYm94XSl7d2lkdGg6MTAwJX1pbnB1dHtwYWRkaW5nOjdweDtiYWNrZ3JvdW5kOiMxMTE7Y29sb3I6I2VlZTtib3JkZXI6MXB4IHNvbGlkICM2NjY7Ym9yZGVyLXJhZGl1czo1cHh9Ci5wYW5lbC10aXRsZXt3aWR0aDoxMDAlO21heC13aWR0aDo1NjBweH0uYW5ub3VuY2VtZW50LXRpdGxle3dpZHRoOjEwMCU7bWF4LXdpZHRoOjc2MHB4fS5hbm5vdW5jZW1lbnQtdGV4dHt3aWR0aDoxMDAlO21pbi1oZWlnaHQ6MTUwcHg7cmVzaXplOnZlcnRpY2FsO3BhZGRpbmc6OXB4O2JhY2tncm91bmQ6IzExMTtjb2xvcjojZWVlO2JvcmRlcjoxcHggc29saWQgIzY2Njtib3JkZXItcmFkaXVzOjVweDtmb250OjE2cHgvMS40IFRhaG9tYSxWZXJkYW5hLFNlZ29lIFVJLHNhbnMtc2VyaWZ9Ci5pbmxpbmUtY2hlY2t7ZGlzcGxheTppbmxpbmUtZmxleDthbGlnbi1pdGVtczpjZW50ZXI7Z2FwOjhweDttYXJnaW46NHB4IDAgMTJweH0uaGludHtjb2xvcjojYmJiO2ZvbnQtc2l6ZToxM3B4O2xpbmUtaGVpZ2h0OjEuMzU7bWFyZ2luLXRvcDo3cHh9CmJ1dHRvbntwYWRkaW5nOjEwcHggMTdweDtib3JkZXI6MXB4IHNvbGlkICM1YTdmYjU7Ym9yZGVyLXJhZGl1czo4cHg7YmFja2dyb3VuZDojMTQ2NmM5O2NvbG9yOiNmZmY7Zm9udC13ZWlnaHQ6ODAwO2N1cnNvcjpwb2ludGVyfQoubWluaXtwYWRkaW5nOjNweCA4cHg7bWFyZ2luOjFweDtiYWNrZ3JvdW5kOiMyYjJiMmI7Ym9yZGVyLWNvbG9yOiM3Nzd9Lm9yZGVye3dpZHRoOjgycHg7d2hpdGUtc3BhY2U6bm93cmFwfS5tZXNzYWdle21hcmdpbi10b3A6MTJweDtwYWRkaW5nOjEwcHg7Ym9yZGVyOjFweCBzb2xpZCAjNzc3O2JvcmRlci1yYWRpdXM6OHB4O2NvbG9yOiNlZmM5ODJ9LnNhdmV7cG9zaXRpb246c3RpY2t5O2JvdHRvbTo4cHh9Cjwvc3R5bGU+PC9oZWFkPjxib2R5PjxkaXYgY2xhc3M9IndyYXAiPgo8ZGl2IGNsYXNzPSJjYXJkIj48aDE+TWFuYWdlIFRlYW0gUGFnZSBDb250ZW50PC9oMT48cD48YSBocmVmPSIvdGVhbS5waHAiPuKGkCBUZWFtPC9hPjwvcD4KPGRpdiBjbGFzcz0ibm90ZSI+PHN0cm9uZz5NYW5hZ2UgVGVhbSBQYWdlIENvbnRlbnQ8L3N0cm9uZz4gaXMgYSBmaXhlZCBBZG1pbiBjb250cm9sIGFuZCBjYW5ub3QgYmUgZWRpdGVkIGhlcmUuPC9kaXY+Cjw/cGhwIGlmKCRtZXNzYWdlIT09JycpOj8+PGRpdiBjbGFzcz0ibWVzc2FnZSI+PD9waHAgZWNobyBhdHBjX2goJG1lc3NhZ2UpOz8+PC9kaXY+PD9waHAgZW5kaWY7Pz48L2Rpdj4KPGZvcm0gbWV0aG9kPSJwb3N0Ij48aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJjc3JmIiB2YWx1ZT0iPD9waHAgZWNobyBhdHBjX2goKHN0cmluZykkX1NFU1NJT05bJ2F0cGNfY3NyZiddKTs/PiI+CjxkaXYgY2xhc3M9ImNhcmQiPgo8aDI+VGVhbSBQYWdlIEFubm91bmNlbWVudCAvIE5ld3M8L2gyPgo8bGFiZWwgY2xhc3M9ImlubGluZS1jaGVjayI+PGlucHV0IG5hbWU9ImFubm91bmNlbWVudF9lbmFibGVkIiB2YWx1ZT0iMSIgdHlwZT0iY2hlY2tib3giIDw/cGhwIGVjaG8gIWVtcHR5KCRkYXRhWydhbm5vdW5jZW1lbnRfcGFuZWwnXVsnZW5hYmxlZCddKSA/ICdjaGVja2VkJyA6ICcnOyA/Pj4gRW5hYmxlZDwvbGFiZWw+CjxsYWJlbD5QYW5lbCB0aXRsZSAob3B0aW9uYWwpPGJyPjxpbnB1dCBjbGFzcz0iYW5ub3VuY2VtZW50LXRpdGxlIiBuYW1lPSJhbm5vdW5jZW1lbnRfdGl0bGUiIHZhbHVlPSI8P3BocCBlY2hvIGF0cGNfaCgkZGF0YVsnYW5ub3VuY2VtZW50X3BhbmVsJ11bJ3RpdGxlJ10gPz8gJ0xlYWd1ZSBOZXdzJyk7ID8+Ij48L2xhYmVsPgo8cD48bGFiZWw+QW5ub3VuY2VtZW50IC8gbm90ZXM8YnI+PHRleHRhcmVhIGNsYXNzPSJhbm5vdW5jZW1lbnQtdGV4dCIgbmFtZT0iYW5ub3VuY2VtZW50X2NvbnRlbnQiIHBsYWNlaG9sZGVyPSJXcml0ZSBhIHNlbnRlbmNlLCBwYXJhZ3JhcGgsIHJlbWluZGVyLCBsZWFndWUgbmV3cywgZXRjLiI+PD9waHAgZWNobyBhdHBjX2goJGRhdGFbJ2Fubm91bmNlbWVudF9wYW5lbCddWydjb250ZW50J10gPz8gJycpOyA/PjwvdGV4dGFyZWE+PC9sYWJlbD48L3A+CjxkaXYgY2xhc3M9ImhpbnQiPlBsYWluIGh0dHA6Ly8gb3IgaHR0cHM6Ly8gVVJMcyBiZWNvbWUgY2xpY2thYmxlIGxpbmtzIGF1dG9tYXRpY2FsbHkgb24gdGhlIFRlYW0gcGFnZS4gTm8gSFRNTCBpcyByZXF1aXJlZC48L2Rpdj4KPC9kaXY+Cjw/cGhwIGZvcmVhY2goJHBhbmVscyBhcyAka2V5PT4kbWV0YSk6JGRrPSRtZXRhWydkYXRhJ107Pz48ZGl2IGNsYXNzPSJjYXJkIj4KPGgyPjw/cGhwIGVjaG8gYXRwY19oKCRtZXRhWydoZWFkaW5nJ10pOz8+PC9oMj4KPGxhYmVsPlBhbmVsIHRpdGxlPGJyPjxpbnB1dCBjbGFzcz0icGFuZWwtdGl0bGUiIG5hbWU9Ijw/cGhwIGVjaG8gYXRwY19oKCRrZXkpOz8+X3RpdGxlIiB2YWx1ZT0iPD9waHAgZWNobyBhdHBjX2goJGRhdGFbJGRrXVsndGl0bGUnXT8/JycpOz8+Ij48L2xhYmVsPgo8dGFibGU+PHRoZWFkPjx0cj48dGg+T3JkZXI8L3RoPjx0aD5MaW5rIHRleHQ8L3RoPjx0aD5VUkw8L3RoPjx0aD5FbmFibGVkPC90aD48dGg+TmV3IHRhYjwvdGg+PHRoPlJlbW92ZTwvdGg+PC90cj48L3RoZWFkPgo8dGJvZHkgaWQ9Ijw/cGhwIGVjaG8gYXRwY19oKCRrZXkpOz8+LXJvd3MiIGRhdGEta2V5PSI8P3BocCBlY2hvIGF0cGNfaCgka2V5KTs/PiI+Cjw/cGhwIGF0cGNfcm93cygka2V5LGlzX2FycmF5KCRkYXRhWyRka11bJ2l0ZW1zJ10/P251bGwpPyRkYXRhWyRka11bJ2l0ZW1zJ106W10pOz8+CjwvdGJvZHk+PC90YWJsZT4KPHA+VXNlIOKGkSAvIOKGkyB0byByZW9yZGVyLjwvcD48YnV0dG9uIHR5cGU9ImJ1dHRvbiIgb25jbGljaz0iYWRkUm93KCc8P3BocCBlY2hvIGF0cGNfaCgka2V5KTs/PicpIj5BZGQgTGluazwvYnV0dG9uPgo8L2Rpdj48P3BocCBlbmRmb3JlYWNoOz8+CjxkaXYgY2xhc3M9ImNhcmQgc2F2ZSI+PGJ1dHRvbiB0eXBlPSJzdWJtaXQiPlNhdmUgVGVhbSBQYWdlIENvbnRlbnQ8L2J1dHRvbj48L2Rpdj48L2Zvcm0+PC9kaXY+CjxzY3JpcHQ+CmZ1bmN0aW9uIHJlbnVtYmVyKHRiKXsKIGNvbnN0IGs9dGIuZGF0YXNldC5rZXk7CiBbLi4udGIucm93c10uZm9yRWFjaCgocixpKT0+ewogICBjb25zdCBtYXA9e2xhYmVsOidfbGFiZWwnLHVybDonX3VybCcsZW5hYmxlZDonX2VuYWJsZWQnLG5ld3RhYjonX25ld190YWInLHJlbW92ZTonX3JlbW92ZSd9OwogICByLnF1ZXJ5U2VsZWN0b3JBbGwoJ2lucHV0W2RhdGEtcm9sZV0nKS5mb3JFYWNoKHg9Pnt4Lm5hbWU9ayttYXBbeC5kYXRhc2V0LnJvbGVdKydbJytpKyddJzt9KTsKIH0pOwp9CmZ1bmN0aW9uIG1vdmVSb3coYixkKXsKIGNvbnN0IHI9Yi5jbG9zZXN0KCd0cicpLHRiPXIucGFyZW50RWxlbWVudDsKIGlmKGQ8MCYmci5wcmV2aW91c0VsZW1lbnRTaWJsaW5nKXRiLmluc2VydEJlZm9yZShyLHIucHJldmlvdXNFbGVtZW50U2libGluZyk7CiBlbHNlIGlmKGQ+MCYmci5uZXh0RWxlbWVudFNpYmxpbmcpdGIuaW5zZXJ0QmVmb3JlKHIubmV4dEVsZW1lbnRTaWJsaW5nLHIpOwogcmVudW1iZXIodGIpOwp9CmZ1bmN0aW9uIGFkZFJvdyhrKXsKIGNvbnN0IHRiPWRvY3VtZW50LmdldEVsZW1lbnRCeUlkKGsrJy1yb3dzJykscj10Yi5pbnNlcnRSb3coKTsKIHIuaW5uZXJIVE1MPSc8dGQgY2xhc3M9Im9yZGVyIj48YnV0dG9uIHR5cGU9ImJ1dHRvbiIgY2xhc3M9Im1pbmkiIG9uY2xpY2s9Im1vdmVSb3codGhpcywtMSkiPuKGkTwvYnV0dG9uPjxidXR0b24gdHlwZT0iYnV0dG9uIiBjbGFzcz0ibWluaSIgb25jbGljaz0ibW92ZVJvdyh0aGlzLDEpIj7ihpM8L2J1dHRvbj48L3RkPicrCiAnPHRkPjxpbnB1dCBkYXRhLXJvbGU9ImxhYmVsIj48L3RkPjx0ZD48aW5wdXQgZGF0YS1yb2xlPSJ1cmwiPjwvdGQ+JysKICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0iZW5hYmxlZCIgdmFsdWU9IjEiIHR5cGU9ImNoZWNrYm94IiBjaGVja2VkPjwvdGQ+JysKICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0ibmV3dGFiIiB2YWx1ZT0iMSIgdHlwZT0iY2hlY2tib3giIGNoZWNrZWQ+PC90ZD4nKwogJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJyZW1vdmUiIHZhbHVlPSIxIiB0eXBlPSJjaGVja2JveCI+PC90ZD4nOwogcmVudW1iZXIodGIpOwp9CmRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJ3Rib2R5W2RhdGEta2V5XScpLmZvckVhY2gocmVudW1iZXIpOwo8L3NjcmlwdD48L2JvZHk+PC9odG1sPgo=', true);

function ih(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function row(string $label, bool $ok, string $detail=''): void {
    echo '<tr><td>'.ih($label).'</td><td class="'.($ok?'ok':'bad').'">'.($ok?'PASS':'FAIL').'</td><td>'.ih($detail).'</td></tr>';
}

function atomic_write(string $path, string $data): bool {
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $data, LOCK_EX) === false) return false;
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function git_blob_sha1(string $data): string {
    return sha1('blob ' . strlen($data) . "\0" . $data);
}

function replace_once(string $source, string $old, string $new, string $label, array &$log): ?string {
    $count = substr_count($source, $old);
    if ($count !== 1) {
        $log[] = 'PATCH FAIL: ' . $label . ' expected 1 anchor, found ' . $count . '.';
        return null;
    }
    $log[] = 'PATCH PASS: ' . $label . '.';
    return str_replace($old, $new, $source);
}

function build_team_v036(string $source, array &$log): ?string {
    $s = $source;

    $pairs = [
        [
            " * VERSION: v035\n * LAST MODIFIED: 8/27/2026 10:16:02 pm",
            " * VERSION: v036\n * LAST MODIFIED: 8/28/2026 3:09:01 pm",
            'version header'
        ],
        [
            " * CHANGELOG:\n *\n",
            " * CHANGELOG:\n *\n"
            . " * v036 (8/28/2026 3:09:01 pm)\n"
            . " * - UI: Admin Menu and Previous Years Picks remember their open/closed state per browser using localStorage.\n"
            . " * - UI: Indents the greeting to align visually with the content below.\n"
            . " * - UI: Enlarges sticky-header text and vertical spacing while preserving left / center / right alignment.\n"
            . " * - NEW: Optional JSON-managed announcement/news panel directly below the greeting.\n"
            . " * - NEW: Plain http:// and https:// URLs in announcement text are automatically rendered as safe clickable links.\n"
            . " * - PRESERVE: Existing themes, menus, charts, normal picks, LP, RP/RD, scoring, View-As, profile, scheduler and DB behavior.\n"
            . " *\n",
            'v036 changelog'
        ],
        [
            "\$teamPageContentDefaults = [\n    'admin_league_panel' => [",
            "\$teamPageContentDefaults = [\n"
            . "    'announcement_panel' => [\n"
            . "        'enabled' => false,\n"
            . "        'title' => 'League News',\n"
            . "        'content' => '',\n"
            . "    ],\n"
            . "    'admin_league_panel' => [",
            'announcement defaults'
        ],
        [
            "\$teamPageContentPanelKeys = [\n    'admin_league_panel',",
            "\$teamPageContentPanelKeys = [\n    'announcement_panel',\n    'admin_league_panel',",
            'announcement JSON merge key'
        ],
        [
            "    echo '</ul>';\n}\n\n?>",
            "    echo '</ul>';\n}\n\n"
            . "function teampage_render_announcement_text(string \$text): void\n"
            . "{\n"
            . "    \$parts = preg_split('~(https?://[^\\\\s<]+)~i', \$text, -1, PREG_SPLIT_DELIM_CAPTURE);\n"
            . "    if (!is_array(\$parts)) {\n"
            . "        echo nl2br(teampage_h(\$text));\n"
            . "        return;\n"
            . "    }\n\n"
            . "    foreach (\$parts as \$part) {\n"
            . "        if (\$part === '') continue;\n"
            . "        if (preg_match('~^https?://~i', \$part) === 1) {\n"
            . "            \$url = \$part;\n"
            . "            \$trail = '';\n"
            . "            while (\$url !== '' && preg_match('/[.,;:!?)]$/', \$url) === 1) {\n"
            . "                \$trail = substr(\$url, -1) . \$trail;\n"
            . "                \$url = substr(\$url, 0, -1);\n"
            . "            }\n"
            . "            if (\$url !== '') {\n"
            . "                echo '<a href=\"' . teampage_h(\$url) . '\" target=\"_blank\" rel=\"noopener noreferrer\">' . teampage_h(\$url) . '</a>';\n"
            . "            }\n"
            . "            if (\$trail !== '') echo teampage_h(\$trail);\n"
            . "        } else {\n"
            . "            echo nl2br(teampage_h(\$part));\n"
            . "        }\n"
            . "    }\n"
            . "}\n\n?>",
            'announcement safe linkifier'
        ],
        [
            "            min-height:58px;\n            display:grid;",
            "            min-height:70px;\n            display:grid;",
            'sticky-header height'
        ],
        [
            "            padding:8px 14px;",
            "            padding:10px 16px;",
            'sticky-header vertical padding'
        ],
        [
            "            font:600 15px/1.1 Tahoma,Verdana,Segoe UI,sans-serif;",
            "            font:600 18px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;",
            'left header text size'
        ],
        [
            "            font:800 20px/1.1 Tahoma,Verdana,Segoe UI,sans-serif;",
            "            font:800 24px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;",
            'center header title size'
        ],
        [
            "            font-size:12px;\n            font-weight:700;",
            "            font-size:14px;\n            font-weight:700;",
            'center header subtitle size'
        ],
        [
            "            font:700 14px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;",
            "            font:700 17px/1.2 Tahoma,Verdana,Segoe UI,sans-serif;",
            'right header clock size'
        ],
        [
            "            font-size:11px;\n            font-weight:600;",
            "            font-size:13px;\n            font-weight:600;",
            'right header date size'
        ],
        [
            "            margin:6px 2px 10px;",
            "            margin:8px 20px 12px;",
            'greeting indentation'
        ],
        [
            "        .mrl-rd-admin-wrap{\n",
            "        .mrl-rd-announcement{\n"
            . "            margin:0 0 16px;\n"
            . "            border:1px solid var(--mrl-rd-border);\n"
            . "            border-radius:14px;\n"
            . "            background:var(--mrl-rd-panel);\n"
            . "            backdrop-filter:blur(2px);\n"
            . "            -webkit-backdrop-filter:blur(2px);\n"
            . "            box-shadow:0 8px 22px rgba(0,0,0,.18);\n"
            . "            overflow:hidden;\n"
            . "        }\n\n"
            . "        .mrl-rd-announcement-title{\n"
            . "            padding:12px 18px 10px;\n"
            . "            color:var(--mrl-rd-gold);\n"
            . "            background:var(--mrl-rd-panel-header);\n"
            . "            border-bottom:1px solid rgba(255,255,255,.10);\n"
            . "            font:800 18px/1.25 Tahoma,Verdana,Segoe UI,sans-serif;\n"
            . "        }\n\n"
            . "        .mrl-rd-announcement-body{\n"
            . "            padding:14px 20px 16px;\n"
            . "            color:var(--mrl-rd-text);\n"
            . "            font:16px/1.5 Tahoma,Verdana,Segoe UI,sans-serif;\n"
            . "            white-space:normal;\n"
            . "        }\n"
            . "        .mrl-rd-announcement-body a{color:var(--mrl-rd-blue)!important;text-decoration:underline!important}\n\n"
            . "        .mrl-rd-admin-wrap{\n",
            'announcement presentation CSS'
        ],
        [
            "        html.mrl-theme-light .mrl-rd-admin-wrap{background:rgba(255,255,255,.58)!important;color:#202020!important}",
            "        html.mrl-theme-light .mrl-rd-announcement{background:rgba(255,255,255,.90)!important;color:#202020!important}\n"
            . "        html.mrl-theme-light .mrl-rd-announcement-title{background:rgba(244,244,244,.98)!important;color:#8b5b00!important}\n"
            . "        html.mrl-theme-light .mrl-rd-announcement-body{color:#202020!important}\n"
            . "        html.mrl-theme-light .mrl-rd-announcement-body a{color:#006eaa!important}\n"
            . "        html.mrl-theme-light .mrl-rd-admin-wrap{background:rgba(255,255,255,.58)!important;color:#202020!important}",
            'announcement Light-theme contrast'
        ],
        [
            "    <div class=\"mrl-rd-greeting\">Hi <?php echo teampage_h(\$first_name); ?> ...</div>\n\n    <?php if (\$isAdmin): ?>",
            "    <div class=\"mrl-rd-greeting\">Hi <?php echo teampage_h(\$first_name); ?> ...</div>\n\n"
            . "    <?php\n"
            . "    \$announcementPanel = isset(\$teamPageContent['announcement_panel']) && is_array(\$teamPageContent['announcement_panel'])\n"
            . "        ? \$teamPageContent['announcement_panel']\n"
            . "        : [];\n"
            . "    \$announcementEnabled = !empty(\$announcementPanel['enabled']);\n"
            . "    \$announcementTitle = trim((string)(\$announcementPanel['title'] ?? ''));\n"
            . "    \$announcementContent = trim((string)(\$announcementPanel['content'] ?? ''));\n"
            . "    ?>\n"
            . "    <?php if (\$announcementEnabled && \$announcementContent !== ''): ?>\n"
            . "        <section class=\"mrl-rd-announcement\">\n"
            . "            <?php if (\$announcementTitle !== ''): ?><div class=\"mrl-rd-announcement-title\"><?php echo teampage_h(\$announcementTitle); ?></div><?php endif; ?>\n"
            . "            <div class=\"mrl-rd-announcement-body\"><?php teampage_render_announcement_text(\$announcementContent); ?></div>\n"
            . "        </section>\n"
            . "    <?php endif; ?>\n\n"
            . "    <?php if (\$isAdmin): ?>",
            'announcement markup below greeting'
        ],
        [
            "<details class=\"mrl-rd-admin-wrap\">",
            "<details class=\"mrl-rd-admin-wrap\" id=\"mrl-rd-admin-details\">",
            'Admin details id'
        ],
        [
            "<details class=\"mrl-previous-years mrl-rd-chart-shell\">",
            "<details class=\"mrl-previous-years mrl-rd-chart-shell\" id=\"mrl-rd-previous-years-details\">",
            'Previous Years details id'
        ],
        [
            "    var timeNode = document.getElementById('mrl-rd-clock-time');",
            "    function rememberDetails(id, storageKey) {\n"
            . "        var details = document.getElementById(id);\n"
            . "        if (!details || !window.localStorage) return;\n\n"
            . "        try {\n"
            . "            var saved = window.localStorage.getItem(storageKey);\n"
            . "            if (saved === 'open') details.open = true;\n"
            . "            if (saved === 'closed') details.open = false;\n\n"
            . "            details.addEventListener('toggle', function () {\n"
            . "                window.localStorage.setItem(storageKey, details.open ? 'open' : 'closed');\n"
            . "            });\n"
            . "        } catch (e) {\n"
            . "            /* localStorage is convenience-only; normal details behavior remains if unavailable. */\n"
            . "        }\n"
            . "    }\n\n"
            . "    rememberDetails('mrl-rd-admin-details', 'mrl.team.adminMenu');\n"
            . "    rememberDetails('mrl-rd-previous-years-details', 'mrl.team.previousYears');\n\n"
            . "    var timeNode = document.getElementById('mrl-rd-clock-time');",
            'remember +/- panel states'
        ],
    ];

    foreach ($pairs as $pair) {
        [$old, $new, $label] = $pair;
        $next = replace_once($s, $old, $new, $label, $log);
        if ($next === null) return null;
        $s = $next;
    }

    return $s;
}

$teamExists = is_file($teamPath);
$adminExists = is_file($adminPath);
$jsonExists = is_file($jsonPath);

$teamRaw = $teamExists ? (string)file_get_contents($teamPath) : '';
$adminRaw = $adminExists ? (string)file_get_contents($adminPath) : '';
$jsonRaw = $jsonExists ? (string)file_get_contents($jsonPath) : '';

$teamBlobSha = $teamExists ? git_blob_sha1($teamRaw) : '';
$adminHash = $adminExists ? (string)hash_file('sha256', $adminPath) : '';
$jsonData = $jsonExists ? json_decode($jsonRaw, true) : null;

$teamBaselineOk =
    $teamExists
    && $teamBlobSha === TEAM_BASE_GIT_BLOB_SHA1
    && strpos($teamRaw, 'VERSION: v035') !== false;

$adminBaselineOk =
    $adminExists
    && $adminHash === ADMIN_BASE_SHA256
    && strpos($adminRaw, 'VERSION: v004') !== false;

$jsonBaselineOk =
    $jsonExists
    && is_array($jsonData)
    && isset($jsonData['admin_league_panel'])
    && isset($jsonData['admin_hosting_panel'])
    && isset($jsonData['league_panel'])
    && isset($jsonData['team_panel']);

$adminReplacementOk =
    is_string($adminReplacement)
    && hash('sha256', $adminReplacement) === ADMIN_NEW_SHA256
    && strpos($adminReplacement, 'VERSION: v005') !== false
    && strpos($adminReplacement, 'announcement_panel') !== false
    && strpos($adminReplacement, 'announcement-text') !== false;

$patchLog = [];
$teamReplacement = $teamBaselineOk ? build_team_v036($teamRaw, $patchLog) : null;

$teamReplacementOk =
    is_string($teamReplacement)
    && strpos($teamReplacement, 'VERSION: v036') !== false
    && strpos($teamReplacement, 'mrl-rd-announcement') !== false
    && strpos($teamReplacement, 'mrl-rd-admin-details') !== false
    && strpos($teamReplacement, 'mrl-rd-previous-years-details') !== false
    && strpos($teamReplacement, 'mrl.team.adminMenu') !== false
    && strpos($teamReplacement, 'mrl.team.previousYears') !== false
    && strpos($teamReplacement, 'function teampage_render_announcement_text') !== false;

$preflightOk = $teamBaselineOk && $adminBaselineOk && $jsonBaselineOk && $adminReplacementOk && $teamReplacementOk;

$apply = isset($_POST['apply']) && $_POST['apply'] === '1';
$messages = [];
$success = false;

if ($apply && $preflightOk) {
    $backupDir = __DIR__ . '/_migration_backups/team_page_convenience_' . date('Ymd_His');
    $ok = is_dir($backupDir) || mkdir($backupDir, 0755, true);

    if (!$ok) $messages[] = 'FAIL: Could not create backup directory.';

    foreach ([
        [$teamPath, $backupDir . '/team.php', 'team.php v035'],
        [$adminPath, $backupDir . '/admin_team_page_content.php', 'admin_team_page_content.php v004'],
        [$jsonPath, $backupDir . '/mrl_team_page_content.json', 'mrl_team_page_content.json'],
    ] as $copySpec) {
        if (!$ok) break;
        [$from, $to, $label] = $copySpec;
        if (!copy($from, $to)) {
            $ok = false;
            $messages[] = 'FAIL: Could not back up ' . $label . '.';
        } else {
            $messages[] = 'PASS: Backed up ' . $label . '.';
        }
    }

    if ($ok && !atomic_write($teamPath, (string)$teamReplacement)) {
        $ok = false;
        $messages[] = 'FAIL: Could not install team.php v036.';
    } elseif ($ok) {
        $messages[] = 'PASS: Installed team.php v036.';
    }

    if ($ok && !atomic_write($adminPath, (string)$adminReplacement)) {
        $ok = false;
        $messages[] = 'FAIL: Could not install admin_team_page_content.php v005.';
    } elseif ($ok) {
        $messages[] = 'PASS: Installed admin_team_page_content.php v005.';
    }

    if ($ok) {
        $newJson = $jsonData;
        $newJson['schema_version'] = 3;
        $newJson['updated_at'] = date('Y-m-d H:i:s');
        if (!isset($newJson['announcement_panel']) || !is_array($newJson['announcement_panel'])) {
            $newJson['announcement_panel'] = [
                'enabled' => false,
                'title' => 'League News',
                'content' => '',
            ];
        }

        $jsonOut = json_encode($newJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($jsonOut) || !atomic_write($jsonPath, $jsonOut . PHP_EOL)) {
            $ok = false;
            $messages[] = 'FAIL: Could not update mrl_team_page_content.json.';
        } else {
            $messages[] = 'PASS: Added/preserved announcement_panel in mrl_team_page_content.json.';
        }
    }

    if ($ok) {
        $installedTeam = (string)file_get_contents($teamPath);
        $installedAdmin = (string)file_get_contents($adminPath);
        $installedJson = json_decode((string)file_get_contents($jsonPath), true);

        $checks = [
            'team.php v036 header' => strpos($installedTeam, 'VERSION: v036') !== false,
            'Admin panel state memory' => strpos($installedTeam, "mrl.team.adminMenu") !== false,
            'Previous Years state memory' => strpos($installedTeam, "mrl.team.previousYears") !== false,
            'greeting alignment adjustment' => strpos($installedTeam, 'margin:8px 20px 12px;') !== false,
            'larger left header control' => strpos($installedTeam, 'font:600 18px/1.15') !== false,
            'larger center header title' => strpos($installedTeam, 'font:800 24px/1.15') !== false,
            'larger right header clock' => strpos($installedTeam, 'font:700 17px/1.2') !== false,
            'announcement renderer' => strpos($installedTeam, 'function teampage_render_announcement_text') !== false,
            'announcement below greeting' => strpos($installedTeam, 'class="mrl-rd-announcement"') !== false,
            'admin manager v005 header' => strpos($installedAdmin, 'VERSION: v005') !== false,
            'admin announcement editor' => strpos($installedAdmin, 'announcement_content') !== false,
            'admin replacement hash exact' => hash_file('sha256', $adminPath) === ADMIN_NEW_SHA256,
            'JSON schema v3' => is_array($installedJson) && (int)($installedJson['schema_version'] ?? 0) === 3,
            'JSON announcement panel' => is_array($installedJson) && isset($installedJson['announcement_panel']) && is_array($installedJson['announcement_panel']),
            'existing Admin League panel preserved' => is_array($installedJson) && isset($installedJson['admin_league_panel']),
            'existing Admin Hosting panel preserved' => is_array($installedJson) && isset($installedJson['admin_hosting_panel']),
            'existing League panel preserved' => is_array($installedJson) && isset($installedJson['league_panel']),
            'existing Team panel preserved' => is_array($installedJson) && isset($installedJson['team_panel']),
        ];

        foreach ($checks as $label => $pass) {
            $messages[] = ($pass ? 'PASS: ' : 'FAIL: ') . $label;
            if (!$pass) $ok = false;
        }
    }

    if (!$ok) {
        foreach ([
            [$backupDir . '/team.php', $teamPath, 'team.php v035'],
            [$backupDir . '/admin_team_page_content.php', $adminPath, 'admin_team_page_content.php v004'],
            [$backupDir . '/mrl_team_page_content.json', $jsonPath, 'mrl_team_page_content.json'],
        ] as $spec) {
            [$from, $to, $label] = $spec;
            if (is_file($from) && copy($from, $to)) {
                $messages[] = 'ROLLBACK: Restored ' . $label . '.';
            } else {
                $messages[] = 'ROLLBACK ERROR: Could not restore ' . $label . '.';
            }
        }
    } else {
        $success = true;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Install Team Page Convenience v001</title>
<style>
*{box-sizing:border-box}html{background:#111}body{margin:0;color:#eee;font-family:Tahoma,Verdana,"Segoe UI",sans-serif}
.wrap{width:94%;max-width:1140px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}
h1,h2{color:#efc982}table{width:100%;border-collapse:collapse}td{padding:9px;border-bottom:1px solid #444;vertical-align:top}
.ok{color:#61e493}.bad{color:#ff7777}button{padding:11px 20px;background:#1466c9;color:#fff;border:1px solid #5a7fb5;border-radius:9px;font-weight:800;cursor:pointer}
a,code{color:#76cfff}li{line-height:1.45;margin-bottom:5px}.small{color:#bbb;font-size:13px}
</style>
</head>
<body><div class="wrap">

<div class="card">
<h1>Team Page Convenience Upgrade</h1>
<p>Small UI/state improvements plus the optional announcement/news panel.</p>
</div>

<div class="card"><h2>Preflight</h2><table>
<?php row('Exact production team.php v035 baseline', $teamBaselineOk, $teamBlobSha); ?>
<?php row('Exact admin_team_page_content.php v004 baseline', $adminBaselineOk, $adminHash); ?>
<?php row('Current Team content JSON valid', $jsonBaselineOk, $jsonExists ? 'Existing user-edited JSON will be preserved.' : 'Missing'); ?>
<?php row('Complete admin manager v005 embedded', $adminReplacementOk, ADMIN_NEW_SHA256); ?>
<?php row('team.php v036 patch builds cleanly', $teamReplacementOk, implode(' | ', $patchLog)); ?>
</table></div>

<?php if ($preflightOk): ?>
<div class="card"><h2>What changes</h2><ul>
<li>Admin Menu and Previous Years Picks remember open/closed state independently in this browser.</li>
<li>"Hi …" is indented to align with the content below.</li>
<li>The sticky header keeps left / center / right alignment but gets larger text and a little more vertical breathing room.</li>
<li>Adds an optional announcement/news panel directly below the greeting.</li>
<li>Announcement panel can be enabled/disabled, titled, and filled with freeform multi-line text in Manage Team Page Content.</li>
<li>Plain http:// and https:// URLs in announcement text automatically become clickable links.</li>
<li>No database changes. No pick, LP, RP/RD, scoring, chart, scheduler, profile, or theme-selection logic changes.</li>
</ul>
<p class="small">The new announcement starts Disabled, so installing this does not add a visible public notice until you enable one.</p>
<?php if (!$apply): ?><form method="post"><input type="hidden" name="apply" value="1"><button>Install Team Page Upgrade</button></form><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($apply): ?>
<div class="card"><h2>Apply Result</h2>
<p class="<?php echo $success?'ok':'bad'; ?>"><strong><?php echo $success?'SUCCESS':'FAILED / ROLLED BACK'; ?></strong></p>
<ul><?php foreach($messages as $m): ?><li><?php echo ih($m); ?></li><?php endforeach; ?></ul>
<?php if ($success): ?>
<p><a href="/team.php" target="_blank">Open Team Page v036</a></p>
<p><a href="/mrl_team/admin_team_page_content.php" target="_blank">Open Manage Team Page Content v005</a></p>
<?php endif; ?>
</div>
<?php endif; ?>

</div></body></html>
