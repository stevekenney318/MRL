<?php
declare(strict_types=1);

/**
 * install_admin_team_page_content_v004.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/28/2026 2:36:58 pm
 *
 * PURPOSE:
 * Fix the stale test-era return link in /mrl_team/admin_team_page_content.php.
 *
 * CHANGE:
 * - "← Team Redesign" /team_redesign.php
 *   becomes
 * - "← Team" /team.php
 *
 * SAFETY:
 * - exact SHA-256 baseline check against current v003
 * - backup before replacement
 * - complete-file replacement only
 * - postflight hash/signature checks
 * - rollback on failure
 * - no JSON/content-manager logic changes
 *
 * LOCATION:
 * Put this installer in public_html/mrl_team/ beside admin_team_page_content.php.
 */

date_default_timezone_set('America/New_York');

const BASE_SHA256 = 'fe2a8c9050f6b5c6055975b3f211dd3ca0b57363ff5c6b225d74cf5a02bf4bbb';
const NEW_SHA256  = '838ec28c0a62fb153fb9877035f4e3bf89fdab0f5dbfa3fc5afcbb6620682cdc';

$target = __DIR__ . '/admin_team_page_content.php';
$replacement = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogYWRtaW5fdGVhbV9wYWdlX2NvbnRlbnQucGhwCiAqCiAqIFZFUlNJT046IHYwMDQKICogTEFTVCBNT0RJRklFRDogOC8yOC8yMDI2IDI6MzY6NTggcG0KICoKICogQWRtaW4tb25seSBlZGl0b3IgZm9yIEpTT04tZHJpdmVuIFRlYW0gUGFnZSBjb250ZW50LgogKgogKiBDSEFOR0VMT0c6CiAqCiAqIHYwMDQgKDgvMjgvMjAyNiAyOjM2OjU4IHBtKQogKiAtIEZJWDogUHJvZHVjdGlvbml6ZWQgdGhlIHJldHVybiBsaW5rIGZyb20gdGhlIFRlYW0gUGFnZSBDb250ZW50IG1hbmFnZXIuCiAqIC0gQ0hBTkdFOiBMaW5rIGxhYmVsIGNoYW5nZWQgZnJvbSAiVGVhbSBSZWRlc2lnbiIgdG8gIlRlYW0iLgogKiAtIENIQU5HRTogTGluayB0YXJnZXQgY2hhbmdlZCBmcm9tIC90ZWFtX3JlZGVzaWduLnBocCB0byAvdGVhbS5waHAuCiAqIC0gUFJFU0VSVkU6IE5vIGNvbnRlbnQtbWFuYWdlciwgSlNPTiwgb3JkZXJpbmcsIGF1dGhlbnRpY2F0aW9uLCBvciBBZG1pbiBiZWhhdmlvciBjaGFuZ2VzLgogKgogKiB2MDAzICg4LzI3LzIwMjYgNjo1NzoyOCBwbSkKICogLSBGSVg6IEV2ZXJ5IGVkaXRhYmxlIHJvdyBub3cgdXNlcyBleHBsaWNpdCBpbmRleGVkIGZpZWxkIG5hbWVzLgogKiAtIEZJWDogRW5hYmxlZC9OZXctdGFiL1JlbW92ZSBjaGVja2JveGVzIHJlbWFpbiBhbGlnbmVkIGFmdGVyIFVwL0Rvd24gbW92ZXMuCiAqIC0gUFJFU0VSVkU6IEZvdXIgZWRpdGFibGUgcGFuZWxzIGFuZCBmaXhlZCBNYW5hZ2UgVGVhbSBQYWdlIENvbnRlbnQgY29udHJvbC4KICoKICogdjAwMiAoOC8yNy8yMDI2IDY6MzM6MTIgcG0pCiAqIC0gQWRkZWQgYWxsIGZvdXIgcGFuZWxzIGFuZCBVcC9Eb3duIG9yZGVyaW5nLgogKi8KCnNlc3Npb25fc3RhcnQoKTsKCnJlcXVpcmVfb25jZSBkaXJuYW1lKF9fRElSX18pIC4gJy9jbGFzcy51c2VyLnBocCc7CiR1c2VyX2hvbWUgPSBuZXcgVVNFUigpOwoKaWYgKCEkdXNlcl9ob21lLT5pc19sb2dnZWRfaW4oKSkgewogICAgJHVzZXJfaG9tZS0+cmVkaXJlY3QoJy9sb2dpbi5waHAnKTsKICAgIGV4aXQ7Cn0KCmRhdGVfZGVmYXVsdF90aW1lem9uZV9zZXQoJ0FtZXJpY2EvTmV3X1lvcmsnKTsKcmVxdWlyZSBkaXJuYW1lKF9fRElSX18pIC4gJy9jb25maWcucGhwJzsKcmVxdWlyZSBkaXJuYW1lKF9fRElSX18pIC4gJy9jb25maWdfbXJsLnBocCc7CgokdWlkID0gKGludCkoJF9TRVNTSU9OWyd1c2VyU2Vzc2lvbiddID8/IDApOwppZiAoIWlzQWRtaW4oJHVpZCkpIHsKICAgIGh0dHBfcmVzcG9uc2VfY29kZSg0MDMpOwogICAgZXhpdCgnQWRtaW4gYWNjZXNzIHJlcXVpcmVkLicpOwp9CgokY29udGVudFBhdGggPSBfX0RJUl9fIC4gJy9tcmxfdGVhbV9wYWdlX2NvbnRlbnQuanNvbic7CgpmdW5jdGlvbiBhdHBjX2goJHYpOiBzdHJpbmcgeyByZXR1cm4gaHRtbHNwZWNpYWxjaGFycygoc3RyaW5nKSR2LCBFTlRfUVVPVEVTLCAnVVRGLTgnKTsgfQoKZnVuY3Rpb24gYXRwY19sb2FkKHN0cmluZyAkcGF0aCk6IGFycmF5CnsKICAgICRyYXcgPSBpc19maWxlKCRwYXRoKSA/IGZpbGVfZ2V0X2NvbnRlbnRzKCRwYXRoKSA6ICcnOwogICAgJGRhdGEgPSBpc19zdHJpbmcoJHJhdykgPyBqc29uX2RlY29kZSgkcmF3LCB0cnVlKSA6IG51bGw7CiAgICByZXR1cm4gaXNfYXJyYXkoJGRhdGEpID8gJGRhdGEgOiBbXTsKfQoKZnVuY3Rpb24gYXRwY19jbGVhbl91cmwoc3RyaW5nICR1cmwpOiBzdHJpbmcKewogICAgJHVybCA9IHRyaW0oJHVybCk7CiAgICBpZiAoJHVybCA9PT0gJycpIHJldHVybiAnJzsKICAgIGlmICgkdXJsWzBdID09PSAnLycpIHJldHVybiAkdXJsOwogICAgaWYgKHByZWdfbWF0Y2goJ35eaHR0cHM/Oi8vfmknLCAkdXJsKSkgcmV0dXJuICR1cmw7CiAgICByZXR1cm4gJyc7Cn0KCmZ1bmN0aW9uIGF0cGNfYnVpbGRfcGFuZWwoYXJyYXkgJHBvc3QsIHN0cmluZyAka2V5LCBzdHJpbmcgJGZhbGxiYWNrKTogYXJyYXkKewogICAgJHRpdGxlID0gdHJpbSgoc3RyaW5nKSgkcG9zdFska2V5IC4gJ190aXRsZSddID8/ICRmYWxsYmFjaykpOwogICAgaWYgKCR0aXRsZSA9PT0gJycpICR0aXRsZSA9ICRmYWxsYmFjazsKCiAgICAkbGFiZWxzID0gaXNfYXJyYXkoJHBvc3RbJGtleSAuICdfbGFiZWwnXSA/PyBudWxsKSA/ICRwb3N0WyRrZXkgLiAnX2xhYmVsJ10gOiBbXTsKICAgICR1cmxzID0gaXNfYXJyYXkoJHBvc3RbJGtleSAuICdfdXJsJ10gPz8gbnVsbCkgPyAkcG9zdFska2V5IC4gJ191cmwnXSA6IFtdOwogICAgJGVuYWJsZWQgPSBpc19hcnJheSgkcG9zdFska2V5IC4gJ19lbmFibGVkJ10gPz8gbnVsbCkgPyAkcG9zdFska2V5IC4gJ19lbmFibGVkJ10gOiBbXTsKICAgICRuZXd0YWIgPSBpc19hcnJheSgkcG9zdFska2V5IC4gJ19uZXdfdGFiJ10gPz8gbnVsbCkgPyAkcG9zdFska2V5IC4gJ19uZXdfdGFiJ10gOiBbXTsKICAgICRyZW1vdmUgPSBpc19hcnJheSgkcG9zdFska2V5IC4gJ19yZW1vdmUnXSA/PyBudWxsKSA/ICRwb3N0WyRrZXkgLiAnX3JlbW92ZSddIDogW107CgogICAgJGl0ZW1zID0gW107CiAgICBmb3JlYWNoICgkbGFiZWxzIGFzICRpID0+ICRsYWJlbFJhdykgewogICAgICAgIGlmICghZW1wdHkoJHJlbW92ZVskaV0pKSBjb250aW51ZTsKCiAgICAgICAgJGxhYmVsID0gdHJpbSgoc3RyaW5nKSRsYWJlbFJhdyk7CiAgICAgICAgJHVybCA9IGF0cGNfY2xlYW5fdXJsKChzdHJpbmcpKCR1cmxzWyRpXSA/PyAnJykpOwogICAgICAgIGlmICgkbGFiZWwgPT09ICcnIHx8ICR1cmwgPT09ICcnKSBjb250aW51ZTsKCiAgICAgICAgJGl0ZW1zW10gPSBbCiAgICAgICAgICAgICdsYWJlbCcgPT4gJGxhYmVsLAogICAgICAgICAgICAndXJsJyA9PiAkdXJsLAogICAgICAgICAgICAnZW5hYmxlZCcgPT4gIWVtcHR5KCRlbmFibGVkWyRpXSksCiAgICAgICAgICAgICduZXdfdGFiJyA9PiAhZW1wdHkoJG5ld3RhYlskaV0pLAogICAgICAgIF07CiAgICB9CgogICAgcmV0dXJuIFsndGl0bGUnID0+ICR0aXRsZSwgJ2l0ZW1zJyA9PiAkaXRlbXNdOwp9CgppZiAoIWlzc2V0KCRfU0VTU0lPTlsnYXRwY19jc3JmJ10pKSB7CiAgICAkX1NFU1NJT05bJ2F0cGNfY3NyZiddID0gYmluMmhleChyYW5kb21fYnl0ZXMoMjQpKTsKfQoKJGRhdGEgPSBhdHBjX2xvYWQoJGNvbnRlbnRQYXRoKTsKJG1lc3NhZ2UgPSAnJzsKCmlmICgkX1NFUlZFUlsnUkVRVUVTVF9NRVRIT0QnXSA9PT0gJ1BPU1QnKSB7CiAgICBpZiAoIWhhc2hfZXF1YWxzKChzdHJpbmcpJF9TRVNTSU9OWydhdHBjX2NzcmYnXSwgKHN0cmluZykoJF9QT1NUWydjc3JmJ10gPz8gJycpKSkgewogICAgICAgICRtZXNzYWdlID0gJ1NhdmUgYmxvY2tlZDogc2VjdXJpdHkgdG9rZW4gbWlzbWF0Y2guJzsKICAgIH0gZWxzZSB7CiAgICAgICAgJG5ldyA9IFsKICAgICAgICAgICAgJ3NjaGVtYV92ZXJzaW9uJyA9PiAyLAogICAgICAgICAgICAndXBkYXRlZF9hdCcgPT4gZGF0ZSgnWS1tLWQgSDppOnMnKSwKICAgICAgICAgICAgJ2FkbWluX2xlYWd1ZV9wYW5lbCcgPT4gYXRwY19idWlsZF9wYW5lbCgkX1BPU1QsICdhZG1pbl9sZWFndWUnLCAnTGVhZ3VlICYgVGVhbScpLAogICAgICAgICAgICAnYWRtaW5faG9zdGluZ19wYW5lbCcgPT4gYXRwY19idWlsZF9wYW5lbCgkX1BPU1QsICdhZG1pbl9ob3N0aW5nJywgJ0hvc3RpbmcgJiBJbmZyYXN0cnVjdHVyZScpLAogICAgICAgICAgICAnbGVhZ3VlX3BhbmVsJyA9PiBhdHBjX2J1aWxkX3BhbmVsKCRfUE9TVCwgJ2xlYWd1ZScsICdMZWFndWUgSW5mb3JtYXRpb24nKSwKICAgICAgICAgICAgJ3RlYW1fcGFuZWwnID0+IGF0cGNfYnVpbGRfcGFuZWwoJF9QT1NULCAndGVhbScsICdUZWFtIE1lbnUnKSwKICAgICAgICBdOwoKICAgICAgICAkYmFja3VwRGlyID0gZGlybmFtZShfX0RJUl9fKSAuICcvX21pZ3JhdGlvbl9iYWNrdXBzL3RlYW1fcGFnZV9jb250ZW50XycgLiBkYXRlKCdZbWRfSGlzJyk7CiAgICAgICAgJGJhY2t1cE9rID0gdHJ1ZTsKICAgICAgICBpZiAoaXNfZmlsZSgkY29udGVudFBhdGgpKSB7CiAgICAgICAgICAgICRiYWNrdXBPayA9IChpc19kaXIoJGJhY2t1cERpcikgfHwgbWtkaXIoJGJhY2t1cERpciwgMDc1NSwgdHJ1ZSkpCiAgICAgICAgICAgICAgICAmJiBjb3B5KCRjb250ZW50UGF0aCwgJGJhY2t1cERpciAuICcvbXJsX3RlYW1fcGFnZV9jb250ZW50Lmpzb24nKTsKICAgICAgICB9CgogICAgICAgICRqc29uID0ganNvbl9lbmNvZGUoJG5ldywgSlNPTl9QUkVUVFlfUFJJTlQgfCBKU09OX1VORVNDQVBFRF9TTEFTSEVTKTsKICAgICAgICAkb2sgPSAkYmFja3VwT2sgJiYgaXNfc3RyaW5nKCRqc29uKQogICAgICAgICAgICAmJiBmaWxlX3B1dF9jb250ZW50cygkY29udGVudFBhdGgsICRqc29uIC4gUEhQX0VPTCwgTE9DS19FWCkgIT09IGZhbHNlOwoKICAgICAgICAkbWVzc2FnZSA9ICRvawogICAgICAgICAgICA/ICdUZWFtIHBhZ2UgY29udGVudCBzYXZlZC4gRXhpc3RpbmcgSlNPTiB3YXMgYmFja2VkIHVwIGZpcnN0LicKICAgICAgICAgICAgOiAnU2F2ZSBmYWlsZWQuJzsKICAgICAgICAkZGF0YSA9IGF0cGNfbG9hZCgkY29udGVudFBhdGgpOwogICAgfQp9CgpmdW5jdGlvbiBhdHBjX3Jvd3Moc3RyaW5nICRrZXksIGFycmF5ICRpdGVtcyk6IHZvaWQKewogICAgZm9yZWFjaCAoJGl0ZW1zIGFzICRpID0+ICRpdCkgewogICAgICAgIGVjaG8gJzx0cj4nOwogICAgICAgIGVjaG8gJzx0ZCBjbGFzcz0ib3JkZXIiPjxidXR0b24gdHlwZT0iYnV0dG9uIiBjbGFzcz0ibWluaSIgb25jbGljaz0ibW92ZVJvdyh0aGlzLC0xKSI+4oaRPC9idXR0b24+PGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJtaW5pIiBvbmNsaWNrPSJtb3ZlUm93KHRoaXMsMSkiPuKGkzwvYnV0dG9uPjwvdGQ+JzsKICAgICAgICBlY2hvICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0ibGFiZWwiIG5hbWU9IicgLiBhdHBjX2goJGtleSkgLiAnX2xhYmVsWycgLiAkaSAuICddIiB2YWx1ZT0iJyAuIGF0cGNfaCgkaXRbJ2xhYmVsJ10gPz8gJycpIC4gJyI+PC90ZD4nOwogICAgICAgIGVjaG8gJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJ1cmwiIG5hbWU9IicgLiBhdHBjX2goJGtleSkgLiAnX3VybFsnIC4gJGkgLiAnXSIgdmFsdWU9IicgLiBhdHBjX2goJGl0Wyd1cmwnXSA/PyAnJykgLiAnIj48L3RkPic7CiAgICAgICAgZWNobyAnPHRkPjxpbnB1dCBkYXRhLXJvbGU9ImVuYWJsZWQiIG5hbWU9IicgLiBhdHBjX2goJGtleSkgLiAnX2VuYWJsZWRbJyAuICRpIC4gJ10iIHZhbHVlPSIxIiB0eXBlPSJjaGVja2JveCIgJyAuICghZW1wdHkoJGl0WydlbmFibGVkJ10pID8gJ2NoZWNrZWQnIDogJycpIC4gJz48L3RkPic7CiAgICAgICAgZWNobyAnPHRkPjxpbnB1dCBkYXRhLXJvbGU9Im5ld3RhYiIgbmFtZT0iJyAuIGF0cGNfaCgka2V5KSAuICdfbmV3X3RhYlsnIC4gJGkgLiAnXSIgdmFsdWU9IjEiIHR5cGU9ImNoZWNrYm94IiAnIC4gKCFlbXB0eSgkaXRbJ25ld190YWInXSkgPyAnY2hlY2tlZCcgOiAnJykgLiAnPjwvdGQ+JzsKICAgICAgICBlY2hvICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0icmVtb3ZlIiBuYW1lPSInIC4gYXRwY19oKCRrZXkpIC4gJ19yZW1vdmVbJyAuICRpIC4gJ10iIHZhbHVlPSIxIiB0eXBlPSJjaGVja2JveCI+PC90ZD4nOwogICAgICAgIGVjaG8gJzwvdHI+JzsKICAgIH0KfQoKJHBhbmVscyA9IFsKICAgICdhZG1pbl9sZWFndWUnID0+IFsnZGF0YSc9PidhZG1pbl9sZWFndWVfcGFuZWwnLCdoZWFkaW5nJz0+J0FkbWluIMK3IExlYWd1ZSAmIFRlYW0nXSwKICAgICdhZG1pbl9ob3N0aW5nJyA9PiBbJ2RhdGEnPT4nYWRtaW5faG9zdGluZ19wYW5lbCcsJ2hlYWRpbmcnPT4nQWRtaW4gwrcgSG9zdGluZyAmIEluZnJhc3RydWN0dXJlJ10sCiAgICAnbGVhZ3VlJyA9PiBbJ2RhdGEnPT4nbGVhZ3VlX3BhbmVsJywnaGVhZGluZyc9PidMZWFndWUgSW5mb3JtYXRpb24nXSwKICAgICd0ZWFtJyA9PiBbJ2RhdGEnPT4ndGVhbV9wYW5lbCcsJ2hlYWRpbmcnPT4nVGVhbSBNZW51J10sCl07Cj8+PCFkb2N0eXBlIGh0bWw+CjxodG1sPjxoZWFkPjxtZXRhIGNoYXJzZXQ9InV0Zi04Ij48bWV0YSBuYW1lPSJ2aWV3cG9ydCIgY29udGVudD0id2lkdGg9ZGV2aWNlLXdpZHRoLGluaXRpYWwtc2NhbGU9MSI+Cjx0aXRsZT5NYW5hZ2UgVGVhbSBQYWdlIENvbnRlbnQ8L3RpdGxlPgo8c3R5bGU+Cip7Ym94LXNpemluZzpib3JkZXItYm94fWJvZHl7bWFyZ2luOjA7YmFja2dyb3VuZDojMTUxNTE1O2NvbG9yOiNlZWU7Zm9udC1mYW1pbHk6VGFob21hLFZlcmRhbmEsU2Vnb2UgVUksc2Fucy1zZXJpZn0KLndyYXB7d2lkdGg6OTQlO21heC13aWR0aDoxNDAwcHg7bWFyZ2luOjIwcHggYXV0b30uY2FyZHtiYWNrZ3JvdW5kOiMyMDIwMjA7Ym9yZGVyOjFweCBzb2xpZCAjNTU1O2JvcmRlci1yYWRpdXM6MTRweDtwYWRkaW5nOjE4cHg7bWFyZ2luLWJvdHRvbToxNnB4fQpoMSxoMntjb2xvcjojZWZjOTgyfWF7Y29sb3I6Izc2Y2ZmZn0ubm90ZXtwYWRkaW5nOjExcHg7Ym9yZGVyOjFweCBzb2xpZCAjNTU1O2JvcmRlci1yYWRpdXM6OXB4O2JhY2tncm91bmQ6IzE3MTcxN30KdGFibGV7d2lkdGg6MTAwJTtib3JkZXItY29sbGFwc2U6Y29sbGFwc2U7bWFyZ2luLXRvcDoxMnB4fXRoLHRke2JvcmRlci1ib3R0b206MXB4IHNvbGlkICM0NDQ7cGFkZGluZzo3cHg7dGV4dC1hbGlnbjpsZWZ0fQp0ZCBpbnB1dDpub3QoW3R5cGU9Y2hlY2tib3hdKXt3aWR0aDoxMDAlfWlucHV0e3BhZGRpbmc6N3B4O2JhY2tncm91bmQ6IzExMTtjb2xvcjojZWVlO2JvcmRlcjoxcHggc29saWQgIzY2Njtib3JkZXItcmFkaXVzOjVweH0KLnBhbmVsLXRpdGxle3dpZHRoOjEwMCU7bWF4LXdpZHRoOjU2MHB4fWJ1dHRvbntwYWRkaW5nOjEwcHggMTdweDtib3JkZXI6MXB4IHNvbGlkICM1YTdmYjU7Ym9yZGVyLXJhZGl1czo4cHg7YmFja2dyb3VuZDojMTQ2NmM5O2NvbG9yOiNmZmY7Zm9udC13ZWlnaHQ6ODAwO2N1cnNvcjpwb2ludGVyfQoubWluaXtwYWRkaW5nOjNweCA4cHg7bWFyZ2luOjFweDtiYWNrZ3JvdW5kOiMyYjJiMmI7Ym9yZGVyLWNvbG9yOiM3Nzd9Lm9yZGVye3dpZHRoOjgycHg7d2hpdGUtc3BhY2U6bm93cmFwfS5tZXNzYWdle21hcmdpbi10b3A6MTJweDtwYWRkaW5nOjEwcHg7Ym9yZGVyOjFweCBzb2xpZCAjNzc3O2JvcmRlci1yYWRpdXM6OHB4O2NvbG9yOiNlZmM5ODJ9LnNhdmV7cG9zaXRpb246c3RpY2t5O2JvdHRvbTo4cHh9Cjwvc3R5bGU+PC9oZWFkPjxib2R5PjxkaXYgY2xhc3M9IndyYXAiPgo8ZGl2IGNsYXNzPSJjYXJkIj48aDE+TWFuYWdlIFRlYW0gUGFnZSBDb250ZW50PC9oMT48cD48YSBocmVmPSIvdGVhbS5waHAiPuKGkCBUZWFtPC9hPjwvcD4KPGRpdiBjbGFzcz0ibm90ZSI+PHN0cm9uZz5NYW5hZ2UgVGVhbSBQYWdlIENvbnRlbnQ8L3N0cm9uZz4gaXMgYSBmaXhlZCBBZG1pbiBjb250cm9sIGFuZCBjYW5ub3QgYmUgZWRpdGVkIGhlcmUuPC9kaXY+Cjw/cGhwIGlmKCRtZXNzYWdlIT09JycpOj8+PGRpdiBjbGFzcz0ibWVzc2FnZSI+PD9waHAgZWNobyBhdHBjX2goJG1lc3NhZ2UpOz8+PC9kaXY+PD9waHAgZW5kaWY7Pz48L2Rpdj4KPGZvcm0gbWV0aG9kPSJwb3N0Ij48aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJjc3JmIiB2YWx1ZT0iPD9waHAgZWNobyBhdHBjX2goKHN0cmluZykkX1NFU1NJT05bJ2F0cGNfY3NyZiddKTs/PiI+Cjw/cGhwIGZvcmVhY2goJHBhbmVscyBhcyAka2V5PT4kbWV0YSk6JGRrPSRtZXRhWydkYXRhJ107Pz48ZGl2IGNsYXNzPSJjYXJkIj4KPGgyPjw/cGhwIGVjaG8gYXRwY19oKCRtZXRhWydoZWFkaW5nJ10pOz8+PC9oMj4KPGxhYmVsPlBhbmVsIHRpdGxlPGJyPjxpbnB1dCBjbGFzcz0icGFuZWwtdGl0bGUiIG5hbWU9Ijw/cGhwIGVjaG8gYXRwY19oKCRrZXkpOz8+X3RpdGxlIiB2YWx1ZT0iPD9waHAgZWNobyBhdHBjX2goJGRhdGFbJGRrXVsndGl0bGUnXT8/JycpOz8+Ij48L2xhYmVsPgo8dGFibGU+PHRoZWFkPjx0cj48dGg+T3JkZXI8L3RoPjx0aD5MaW5rIHRleHQ8L3RoPjx0aD5VUkw8L3RoPjx0aD5FbmFibGVkPC90aD48dGg+TmV3IHRhYjwvdGg+PHRoPlJlbW92ZTwvdGg+PC90cj48L3RoZWFkPgo8dGJvZHkgaWQ9Ijw/cGhwIGVjaG8gYXRwY19oKCRrZXkpOz8+LXJvd3MiIGRhdGEta2V5PSI8P3BocCBlY2hvIGF0cGNfaCgka2V5KTs/PiI+Cjw/cGhwIGF0cGNfcm93cygka2V5LGlzX2FycmF5KCRkYXRhWyRka11bJ2l0ZW1zJ10/P251bGwpPyRkYXRhWyRka11bJ2l0ZW1zJ106W10pOz8+CjwvdGJvZHk+PC90YWJsZT4KPHA+VXNlIOKGkSAvIOKGkyB0byByZW9yZGVyLjwvcD48YnV0dG9uIHR5cGU9ImJ1dHRvbiIgb25jbGljaz0iYWRkUm93KCc8P3BocCBlY2hvIGF0cGNfaCgka2V5KTs/PicpIj5BZGQgTGluazwvYnV0dG9uPgo8L2Rpdj48P3BocCBlbmRmb3JlYWNoOz8+CjxkaXYgY2xhc3M9ImNhcmQgc2F2ZSI+PGJ1dHRvbiB0eXBlPSJzdWJtaXQiPlNhdmUgVGVhbSBQYWdlIENvbnRlbnQ8L2J1dHRvbj48L2Rpdj48L2Zvcm0+PC9kaXY+CjxzY3JpcHQ+CmZ1bmN0aW9uIHJlbnVtYmVyKHRiKXsKIGNvbnN0IGs9dGIuZGF0YXNldC5rZXk7CiBbLi4udGIucm93c10uZm9yRWFjaCgocixpKT0+ewogICBjb25zdCBtYXA9e2xhYmVsOidfbGFiZWwnLHVybDonX3VybCcsZW5hYmxlZDonX2VuYWJsZWQnLG5ld3RhYjonX25ld190YWInLHJlbW92ZTonX3JlbW92ZSd9OwogICByLnF1ZXJ5U2VsZWN0b3JBbGwoJ2lucHV0W2RhdGEtcm9sZV0nKS5mb3JFYWNoKHg9Pnt4Lm5hbWU9ayttYXBbeC5kYXRhc2V0LnJvbGVdKydbJytpKyddJzt9KTsKIH0pOwp9CmZ1bmN0aW9uIG1vdmVSb3coYixkKXsKIGNvbnN0IHI9Yi5jbG9zZXN0KCd0cicpLHRiPXIucGFyZW50RWxlbWVudDsKIGlmKGQ8MCYmci5wcmV2aW91c0VsZW1lbnRTaWJsaW5nKXRiLmluc2VydEJlZm9yZShyLHIucHJldmlvdXNFbGVtZW50U2libGluZyk7CiBlbHNlIGlmKGQ+MCYmci5uZXh0RWxlbWVudFNpYmxpbmcpdGIuaW5zZXJ0QmVmb3JlKHIubmV4dEVsZW1lbnRTaWJsaW5nLHIpOwogcmVudW1iZXIodGIpOwp9CmZ1bmN0aW9uIGFkZFJvdyhrKXsKIGNvbnN0IHRiPWRvY3VtZW50LmdldEVsZW1lbnRCeUlkKGsrJy1yb3dzJykscj10Yi5pbnNlcnRSb3coKTsKIHIuaW5uZXJIVE1MPSc8dGQgY2xhc3M9Im9yZGVyIj48YnV0dG9uIHR5cGU9ImJ1dHRvbiIgY2xhc3M9Im1pbmkiIG9uY2xpY2s9Im1vdmVSb3codGhpcywtMSkiPuKGkTwvYnV0dG9uPjxidXR0b24gdHlwZT0iYnV0dG9uIiBjbGFzcz0ibWluaSIgb25jbGljaz0ibW92ZVJvdyh0aGlzLDEpIj7ihpM8L2J1dHRvbj48L3RkPicrCiAnPHRkPjxpbnB1dCBkYXRhLXJvbGU9ImxhYmVsIj48L3RkPjx0ZD48aW5wdXQgZGF0YS1yb2xlPSJ1cmwiPjwvdGQ+JysKICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0iZW5hYmxlZCIgdmFsdWU9IjEiIHR5cGU9ImNoZWNrYm94IiBjaGVja2VkPjwvdGQ+JysKICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0ibmV3dGFiIiB2YWx1ZT0iMSIgdHlwZT0iY2hlY2tib3giIGNoZWNrZWQ+PC90ZD4nKwogJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJyZW1vdmUiIHZhbHVlPSIxIiB0eXBlPSJjaGVja2JveCI+PC90ZD4nOwogcmVudW1iZXIodGIpOwp9CmRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJ3Rib2R5W2RhdGEta2V5XScpLmZvckVhY2gocmVudW1iZXIpOwo8L3NjcmlwdD48L2JvZHk+PC9odG1sPgo=', true);

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

$exists = is_file($target);
$currentHash = $exists ? (string)hash_file('sha256', $target) : '';

$baselineOk =
    $exists
    && $currentHash === BASE_SHA256
    && strpos((string)file_get_contents($target), 'VERSION: v003') !== false
    && strpos((string)file_get_contents($target), 'href="/team_redesign.php">← Team Redesign</a>') !== false;

$replacementOk =
    is_string($replacement)
    && hash('sha256', $replacement) === NEW_SHA256
    && strpos($replacement, 'VERSION: v004') !== false
    && strpos($replacement, 'href="/team.php">← Team</a>') !== false
    && strpos($replacement, 'href="/team_redesign.php"') === false;

$preflightOk = $baselineOk && $replacementOk;
$apply = isset($_POST['apply']) && $_POST['apply'] === '1';
$messages = [];
$success = false;

if ($apply && $preflightOk) {
    $backupDir = __DIR__ . '/_migration_backups/admin_team_page_content_v004_' . date('Ymd_His');
    $ok = is_dir($backupDir) || mkdir($backupDir, 0755, true);

    if (!$ok) {
        $messages[] = 'FAIL: Could not create backup directory.';
    }

    if ($ok && !copy($target, $backupDir . '/admin_team_page_content.php')) {
        $ok = false;
        $messages[] = 'FAIL: Could not back up admin_team_page_content.php v003.';
    } elseif ($ok) {
        $messages[] = 'PASS: Backed up admin_team_page_content.php v003.';
    }

    if ($ok && !atomic_write($target, $replacement)) {
        $ok = false;
        $messages[] = 'FAIL: Could not install admin_team_page_content.php v004.';
    } elseif ($ok) {
        $messages[] = 'PASS: Installed admin_team_page_content.php v004.';
    }

    if ($ok) {
        $installed = (string)file_get_contents($target);
        $checks = [
            'v004 header' => strpos($installed, 'VERSION: v004') !== false,
            'production Team link' => strpos($installed, 'href="/team.php">← Team</a>') !== false,
            'test Team Redesign link removed' => strpos($installed, 'href="/team_redesign.php"') === false,
            'replacement hash exact' => hash_file('sha256', $target) === NEW_SHA256,
        ];

        foreach ($checks as $label => $pass) {
            $messages[] = ($pass ? 'PASS: ' : 'FAIL: ') . $label;
            if (!$pass) $ok = false;
        }
    }

    if (!$ok && is_file($backupDir . '/admin_team_page_content.php')) {
        if (copy($backupDir . '/admin_team_page_content.php', $target)) {
            $messages[] = 'ROLLBACK: Restored admin_team_page_content.php v003.';
        } else {
            $messages[] = 'ROLLBACK ERROR: Could not restore admin_team_page_content.php v003.';
        }
    } else {
        $success = true;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Install Team Content Manager v004</title>
<style>
*{box-sizing:border-box} html{background:#111} body{margin:0;color:#eee;font-family:Tahoma,Verdana,"Segoe UI",sans-serif}
.wrap{width:94%;max-width:1050px;margin:20px auto} .card{background:#202020;border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}
h1,h2{color:#efc982} table{width:100%;border-collapse:collapse} td{padding:9px;border-bottom:1px solid #444;vertical-align:top}
.ok{color:#61e493} .bad{color:#ff7777} button{padding:11px 20px;background:#1466c9;color:#fff;border:1px solid #5a7fb5;border-radius:9px;font-weight:800;cursor:pointer}
a{color:#76cfff} li{line-height:1.45;margin-bottom:5px}
</style>
</head>
<body><div class="wrap">

<div class="card">
<h1>Team Content Manager v004</h1>
<p>One-link production cleanup.</p>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<?php row('Exact admin_team_page_content.php v003 baseline', $baselineOk, $currentHash); ?>
<?php row('Complete v004 replacement embedded', $replacementOk, NEW_SHA256); ?>
</table>
</div>

<?php if ($preflightOk): ?>
<div class="card">
<h2>What changes</h2>
<ul>
<li><strong>← Team Redesign</strong> becomes <strong>← Team</strong>.</li>
<li><code>/team_redesign.php</code> becomes <code>/team.php</code>.</li>
<li>No content-manager behavior or JSON handling changes.</li>
</ul>
<?php if (!$apply): ?>
<form method="post"><input type="hidden" name="apply" value="1"><button>Install v004</button></form>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if ($apply): ?>
<div class="card">
<h2>Apply Result</h2>
<p class="<?php echo $success ? 'ok' : 'bad'; ?>"><strong><?php echo $success ? 'SUCCESS' : 'FAILED / ROLLED BACK'; ?></strong></p>
<ul><?php foreach ($messages as $m): ?><li><?php echo ih($m); ?></li><?php endforeach; ?></ul>
<?php if ($success): ?><p><a href="/mrl_team/admin_team_page_content.php" target="_blank">Open Team Content Manager</a></p><?php endif; ?>
</div>
<?php endif; ?>

</div></body></html>
