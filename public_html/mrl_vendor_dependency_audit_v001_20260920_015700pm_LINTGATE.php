<?php
declare(strict_types=1);

/**
 * mrl_vendor_dependency_audit_v001_20260920_015700pm_LINTGATE.php
 * VERSION: v001
 * GENERATED: 9/20/2026 1:57:00 pm ET
 */

date_default_timezone_set('America/New_York');

const REAL_FILE_NAME = 'mrl_vendor_dependency_audit_v001_20260920_015700pm.php';
const REAL_FILE_SHA256 = '7d456adb542acaed20569a8cd2718169050d2d84f48bb82ed038a812fae6aab7';
const REAL_FILE_B64 = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogbXJsX3ZlbmRvcl9kZXBlbmRlbmN5X2F1ZGl0X3YwMDFfMjAyNjA5MjBfMDE1NzAwcG0ucGhwCiAqCiAqIFZFUlNJT046IHYwMDEKICogR0VORVJBVEVEOiA5LzIwLzIwMjYgMTo1NzowMCBwbSBFVAogKgogKiBQVVJQT1NFOgogKiAtIFJFQUQtT05MWSBwcm9kdWN0aW9uIGF1ZGl0IG9mIHRoZSBhY3R1YWwgSG9zdGluZ2VyIHB1YmxpY19odG1sIHRyZWUuCiAqIC0gRmluZHMgcmVmZXJlbmNlcyB0aGF0IGNvdWxkIGRlcGVuZCBvbiAvcHVibGljX2h0bWwvdmVuZG9yLgogKiAtIEludmVudG9yaWVzIHRoZSBhY3R1YWwgL3ZlbmRvciB0cmVlLgogKiAtIERvZXMgTk9UIG1vdmUsIGRlbGV0ZSwgcmVuYW1lLCBlZGl0LCBvciBleGVjdXRlIHByb2R1Y3Rpb24gZmlsZXMuCiAqLwoKZGF0ZV9kZWZhdWx0X3RpbWV6b25lX3NldCgnQW1lcmljYS9OZXdfWW9yaycpOwoKZnVuY3Rpb24gaCgkdmFsdWUpOiBzdHJpbmcKewogICAgcmV0dXJuIGh0bWxzcGVjaWFsY2hhcnMoKHN0cmluZykkdmFsdWUsIEVOVF9RVU9URVMsICdVVEYtOCcpOwp9CgpmdW5jdGlvbiBmbXRfYnl0ZXMoaW50ICRieXRlcyk6IHN0cmluZwp7CiAgICAkdW5pdHMgPSBbJ0InLCdLQicsJ01CJywnR0InXTsKICAgICRuID0gKGZsb2F0KSRieXRlczsKICAgICRpID0gMDsKICAgIHdoaWxlICgkbiA+PSAxMDI0ICYmICRpIDwgY291bnQoJHVuaXRzKSAtIDEpIHsKICAgICAgICAkbiAvPSAxMDI0OwogICAgICAgICRpKys7CiAgICB9CiAgICByZXR1cm4gbnVtYmVyX2Zvcm1hdCgkbiwgJGkgPT09IDAgPyAwIDogMikgLiAnICcgLiAkdW5pdHNbJGldOwp9CgpmdW5jdGlvbiBpc190ZXh0X2NhbmRpZGF0ZShzdHJpbmcgJHBhdGgpOiBib29sCnsKICAgICRleHQgPSBzdHJ0b2xvd2VyKChzdHJpbmcpcGF0aGluZm8oJHBhdGgsIFBBVEhJTkZPX0VYVEVOU0lPTikpOwogICAgcmV0dXJuIGluX2FycmF5KCRleHQsIFsKICAgICAgICAncGhwJywnaW5jJywncGh0bWwnLCdodG1sJywnaHRtJywnanMnLCdqc29uJywndHh0JywnbWQnLCdjc3MnLCd4bWwnLCdpbmknCiAgICBdLCB0cnVlKTsKfQoKZnVuY3Rpb24gaXNfaGlzdG9yeV9wYXRoKHN0cmluZyAkcmVsYXRpdmUpOiBib29sCnsKICAgICRyID0gc3RydG9sb3dlcignLycgLiBsdHJpbShzdHJfcmVwbGFjZSgnXFwnLCAnLycsICRyZWxhdGl2ZSksICcvJykpOwoKICAgIGZvcmVhY2ggKFsKICAgICAgICAnL2FyY2hpdmUvJywnL2FyY2hpdmVzLycsJy9iYWNrdXAvJywnL2JhY2t1cHMvJywnL19iYWNrdXAvJywnL19iYWNrdXBzLycsCiAgICAgICAgJy9faW5zdGFsbGVyX2JhY2t1cHMvJywnL19xdWFyYW50aW5lLycsJy9xdWFyYW50aW5lLycsJy9vbGQvJywnL29ic29sZXRlLycsJy9sZWdhY3kvJwogICAgXSBhcyAkbWFya2VyKSB7CiAgICAgICAgaWYgKHN0cnBvcygkciwgJG1hcmtlcikgIT09IGZhbHNlKSB7CiAgICAgICAgICAgIHJldHVybiB0cnVlOwogICAgICAgIH0KICAgIH0KCiAgICAkYmFzZSA9IHN0cnRvbG93ZXIoYmFzZW5hbWUoJHIpKTsKCiAgICByZXR1cm4gc3RycG9zKCRiYXNlLCAnYmFja3VwJykgIT09IGZhbHNlCiAgICAgICAgfHwgc3RycG9zKCRiYXNlLCAnX29sZCcpICE9PSBmYWxzZQogICAgICAgIHx8IHN0cnBvcygkYmFzZSwgJ29sZF8nKSAhPT0gZmFsc2UKICAgICAgICB8fCBzdHJwb3MoJGJhc2UsICdwcmUtJykgIT09IGZhbHNlCiAgICAgICAgfHwgc3RycG9zKCRiYXNlLCAnX3ByZV8nKSAhPT0gZmFsc2U7Cn0KCiRkb2NSb290ID0gcnRyaW0oKHN0cmluZykoJF9TRVJWRVJbJ0RPQ1VNRU5UX1JPT1QnXSA/PyBfX0RJUl9fKSwgJy9cXCcpOwokdmVuZG9yRGlyID0gJGRvY1Jvb3QgLiAnL3ZlbmRvcic7CgokbmVlZGxlcyA9IFsKICAgICd2ZW5kb3IvYXV0b2xvYWQucGhwJywKICAgICcvdmVuZG9yL2F1dG9sb2FkLnBocCcsCiAgICAnUGhwT2ZmaWNlJywKICAgICdQaHBTcHJlYWRzaGVldCcsCiAgICAnQ29tcG9zZXJcXEF1dG9sb2FkJywKICAgICdjb21wb3Nlci9hdXRvbG9hZCcsCiAgICAnL3ZlbmRvci8nLApdOwoKJHNraXBQcmVmaXhlcyA9IFsKICAgIHJ0cmltKCR2ZW5kb3JEaXIsICcvXFwnKSAuIERJUkVDVE9SWV9TRVBBUkFUT1IsCiAgICBydHJpbSgkZG9jUm9vdCAuICcvLmdpdCcsICcvXFwnKSAuIERJUkVDVE9SWV9TRVBBUkFUT1IsCiAgICBydHJpbSgkZG9jUm9vdCAuICcvd3AtY29udGVudC9jYWNoZScsICcvXFwnKSAuIERJUkVDVE9SWV9TRVBBUkFUT1IsCl07CgokZmlsZXNTY2FubmVkID0gMDsKJGJ5dGVzU2Nhbm5lZCA9IDA7CiRvdmVyc2l6ZVNraXBwZWQgPSAwOwokaGl0cyA9IFtdOwokZXJyb3JzID0gW107Cgp0cnkgewogICAgJGl0ID0gbmV3IFJlY3Vyc2l2ZUl0ZXJhdG9ySXRlcmF0b3IoCiAgICAgICAgbmV3IFJlY3Vyc2l2ZURpcmVjdG9yeUl0ZXJhdG9yKAogICAgICAgICAgICAkZG9jUm9vdCwKICAgICAgICAgICAgRmlsZXN5c3RlbUl0ZXJhdG9yOjpTS0lQX0RPVFMgfCBGaWxlc3lzdGVtSXRlcmF0b3I6OkNVUlJFTlRfQVNfRklMRUlORk8KICAgICAgICApCiAgICApOwoKICAgIGZvcmVhY2ggKCRpdCBhcyAkaW5mbykgewogICAgICAgIGlmICghJGluZm8tPmlzRmlsZSgpKSB7CiAgICAgICAgICAgIGNvbnRpbnVlOwogICAgICAgIH0KCiAgICAgICAgJHBhdGggPSAkaW5mby0+Z2V0UGF0aG5hbWUoKTsKCiAgICAgICAgJHNraXAgPSBmYWxzZTsKICAgICAgICBmb3JlYWNoICgkc2tpcFByZWZpeGVzIGFzICRwcmVmaXgpIHsKICAgICAgICAgICAgaWYgKHN0cnBvcygkcGF0aCwgJHByZWZpeCkgPT09IDApIHsKICAgICAgICAgICAgICAgICRza2lwID0gdHJ1ZTsKICAgICAgICAgICAgICAgIGJyZWFrOwogICAgICAgICAgICB9CiAgICAgICAgfQogICAgICAgIGlmICgkc2tpcCB8fCAhaXNfdGV4dF9jYW5kaWRhdGUoJHBhdGgpKSB7CiAgICAgICAgICAgIGNvbnRpbnVlOwogICAgICAgIH0KCiAgICAgICAgJGZpbGVzU2Nhbm5lZCsrOwogICAgICAgICRzaXplID0gKGludCkkaW5mby0+Z2V0U2l6ZSgpOwogICAgICAgICRieXRlc1NjYW5uZWQgKz0gJHNpemU7CgogICAgICAgIGlmICgkc2l6ZSA+IDUgKiAxMDI0ICogMTAyNCkgewogICAgICAgICAgICAkb3ZlcnNpemVTa2lwcGVkKys7CiAgICAgICAgICAgIGNvbnRpbnVlOwogICAgICAgIH0KCiAgICAgICAgJGNvbnRlbnQgPSBAZmlsZV9nZXRfY29udGVudHMoJHBhdGgpOwogICAgICAgIGlmICgkY29udGVudCA9PT0gZmFsc2UpIHsKICAgICAgICAgICAgJGVycm9yc1tdID0gJ0NvdWxkIG5vdCByZWFkOiAnIC4gJHBhdGg7CiAgICAgICAgICAgIGNvbnRpbnVlOwogICAgICAgIH0KCiAgICAgICAgJG1hdGNoZWQgPSBbXTsKICAgICAgICBmb3JlYWNoICgkbmVlZGxlcyBhcyAkbmVlZGxlKSB7CiAgICAgICAgICAgIGlmIChzdHJpcG9zKCRjb250ZW50LCAkbmVlZGxlKSAhPT0gZmFsc2UpIHsKICAgICAgICAgICAgICAgICRtYXRjaGVkW10gPSAkbmVlZGxlOwogICAgICAgICAgICB9CiAgICAgICAgfQoKICAgICAgICBpZiAoIWVtcHR5KCRtYXRjaGVkKSkgewogICAgICAgICAgICAkcmVsYXRpdmUgPSBsdHJpbShzdHJfcmVwbGFjZSgkZG9jUm9vdCwgJycsICRwYXRoKSwgJy9cXCcpOwogICAgICAgICAgICAkaGl0c1tdID0gWwogICAgICAgICAgICAgICAgJ3BhdGgnID0+ICRyZWxhdGl2ZSwKICAgICAgICAgICAgICAgICdtYXRjaGVzJyA9PiBhcnJheV92YWx1ZXMoYXJyYXlfdW5pcXVlKCRtYXRjaGVkKSksCiAgICAgICAgICAgICAgICAnaGlzdG9yeScgPT4gaXNfaGlzdG9yeV9wYXRoKCRyZWxhdGl2ZSksCiAgICAgICAgICAgICAgICAnc2l6ZScgPT4gJHNpemUsCiAgICAgICAgICAgIF07CiAgICAgICAgfQogICAgfQp9IGNhdGNoIChUaHJvd2FibGUgJGUpIHsKICAgICRlcnJvcnNbXSA9ICdUcmVlIHNjYW4gZXhjZXB0aW9uOiAnIC4gJGUtPmdldE1lc3NhZ2UoKTsKfQoKdXNvcnQoJGhpdHMsIGZ1bmN0aW9uICgkYSwgJGIpIHsKICAgIGlmICgkYVsnaGlzdG9yeSddICE9PSAkYlsnaGlzdG9yeSddKSB7CiAgICAgICAgcmV0dXJuICRhWydoaXN0b3J5J10gPyAxIDogLTE7CiAgICB9CiAgICByZXR1cm4gc3RyY2FzZWNtcCgoc3RyaW5nKSRhWydwYXRoJ10sIChzdHJpbmcpJGJbJ3BhdGgnXSk7Cn0pOwoKJHZlbmRvckZpbGVzID0gMDsKJHZlbmRvckZvbGRlcnMgPSAwOwokdmVuZG9yQnl0ZXMgPSAwOwokdG9wTGV2ZWxWZW5kb3IgPSBbXTsKCmlmIChpc19kaXIoJHZlbmRvckRpcikpIHsKICAgICR0b3AgPSBAc2NhbmRpcigkdmVuZG9yRGlyKTsKICAgIGlmIChpc19hcnJheSgkdG9wKSkgewogICAgICAgIGZvcmVhY2ggKCR0b3AgYXMgJG5hbWUpIHsKICAgICAgICAgICAgaWYgKCRuYW1lID09PSAnLicgfHwgJG5hbWUgPT09ICcuLicpIHsKICAgICAgICAgICAgICAgIGNvbnRpbnVlOwogICAgICAgICAgICB9CiAgICAgICAgICAgICRmdWxsID0gJHZlbmRvckRpciAuICcvJyAuICRuYW1lOwogICAgICAgICAgICAkdG9wTGV2ZWxWZW5kb3JbXSA9IFsKICAgICAgICAgICAgICAgICduYW1lJyA9PiAkbmFtZSwKICAgICAgICAgICAgICAgICd0eXBlJyA9PiBpc19kaXIoJGZ1bGwpID8gJ2ZvbGRlcicgOiAnZmlsZScsCiAgICAgICAgICAgIF07CiAgICAgICAgfQogICAgfQoKICAgIHRyeSB7CiAgICAgICAgJHZJdCA9IG5ldyBSZWN1cnNpdmVJdGVyYXRvckl0ZXJhdG9yKAogICAgICAgICAgICBuZXcgUmVjdXJzaXZlRGlyZWN0b3J5SXRlcmF0b3IoCiAgICAgICAgICAgICAgICAkdmVuZG9yRGlyLAogICAgICAgICAgICAgICAgRmlsZXN5c3RlbUl0ZXJhdG9yOjpTS0lQX0RPVFMgfCBGaWxlc3lzdGVtSXRlcmF0b3I6OkNVUlJFTlRfQVNfRklMRUlORk8KICAgICAgICAgICAgKSwKICAgICAgICAgICAgUmVjdXJzaXZlSXRlcmF0b3JJdGVyYXRvcjo6U0VMRl9GSVJTVAogICAgICAgICk7CgogICAgICAgIGZvcmVhY2ggKCR2SXQgYXMgJHZJbmZvKSB7CiAgICAgICAgICAgIGlmICgkdkluZm8tPmlzRGlyKCkpIHsKICAgICAgICAgICAgICAgICR2ZW5kb3JGb2xkZXJzKys7CiAgICAgICAgICAgIH0gZWxzZWlmICgkdkluZm8tPmlzRmlsZSgpKSB7CiAgICAgICAgICAgICAgICAkdmVuZG9yRmlsZXMrKzsKICAgICAgICAgICAgICAgICR2ZW5kb3JCeXRlcyArPSAoaW50KSR2SW5mby0+Z2V0U2l6ZSgpOwogICAgICAgICAgICB9CiAgICAgICAgfQogICAgfSBjYXRjaCAoVGhyb3dhYmxlICRlKSB7CiAgICAgICAgJGVycm9yc1tdID0gJ1ZlbmRvciBpbnZlbnRvcnkgZXhjZXB0aW9uOiAnIC4gJGUtPmdldE1lc3NhZ2UoKTsKICAgIH0KfQoKJGFjdGl2ZUhpdHMgPSBhcnJheV92YWx1ZXMoYXJyYXlfZmlsdGVyKCRoaXRzLCBmdW5jdGlvbiAoJHJvdykgewogICAgcmV0dXJuIGVtcHR5KCRyb3dbJ2hpc3RvcnknXSk7Cn0pKTsKCiRoaXN0b3J5SGl0cyA9IGFycmF5X3ZhbHVlcyhhcnJheV9maWx0ZXIoJGhpdHMsIGZ1bmN0aW9uICgkcm93KSB7CiAgICByZXR1cm4gIWVtcHR5KCRyb3dbJ2hpc3RvcnknXSk7Cn0pKTsKCj8+CjwhZG9jdHlwZSBodG1sPgo8aHRtbCBsYW5nPSJlbiI+CjxoZWFkPgo8bWV0YSBjaGFyc2V0PSJ1dGYtOCI+CjxtZXRhIG5hbWU9InZpZXdwb3J0IiBjb250ZW50PSJ3aWR0aD1kZXZpY2Utd2lkdGgsaW5pdGlhbC1zY2FsZT0xIj4KPHRpdGxlPk1STCBWZW5kb3IgRGVwZW5kZW5jeSBBdWRpdDwvdGl0bGU+CjxzdHlsZT4KOnJvb3R7Y29sb3Itc2NoZW1lOmRhcms7LS1iZzojMTAxMDEwOy0tcGFuZWw6IzFiMWIxYjstLWxpbmU6IzNkM2QzZDstLXRleHQ6I2VlZTstLW11dGVkOiNhYWE7LS1nb29kOiM2NmRmOGQ7LS13YXJuOiNmZmQxNjY7LS1iYWQ6I2ZmNzQ3NDstLWdvbGQ6I2YyYzk4ZX0KKntib3gtc2l6aW5nOmJvcmRlci1ib3h9CmJvZHl7bWFyZ2luOjA7cGFkZGluZzoyMnB4O2JhY2tncm91bmQ6dmFyKC0tYmcpO2NvbG9yOnZhcigtLXRleHQpO2ZvbnQtZmFtaWx5OkFyaWFsLEhlbHZldGljYSxzYW5zLXNlcmlmfQoud3JhcHttYXgtd2lkdGg6MTIwMHB4O21hcmdpbjowIGF1dG99Cmgxe21hcmdpbjowIDAgNnB4O2NvbG9yOnZhcigtLWdvbGQpfQpoMnttYXJnaW46MCAwIDEwcHh9Ci5jYXJke21hcmdpbjoxNHB4IDA7cGFkZGluZzoxNnB4O2JhY2tncm91bmQ6dmFyKC0tcGFuZWwpO2JvcmRlcjoxcHggc29saWQgdmFyKC0tbGluZSk7Ym9yZGVyLXJhZGl1czoxNHB4fQouZ3JpZHtkaXNwbGF5OmdyaWQ7Z3JpZC10ZW1wbGF0ZS1jb2x1bW5zOnJlcGVhdCg0LG1pbm1heCgwLDFmcikpO2dhcDoxMHB4fQouc3RhdHtwYWRkaW5nOjEycHg7Ym9yZGVyOjFweCBzb2xpZCAjMzgzODM4O2JvcmRlci1yYWRpdXM6MTBweDtiYWNrZ3JvdW5kOiMxNTE1MTV9Ci5zdGF0IC5sYWJlbHtjb2xvcjp2YXIoLS1tdXRlZCk7Zm9udC1zaXplOjEzcHh9Ci5zdGF0IC52YWx1ZXtmb250LXNpemU6MjJweDtmb250LXdlaWdodDo4MDA7bWFyZ2luLXRvcDozcHh9Ci5nb29ke2NvbG9yOnZhcigtLWdvb2QpfS53YXJue2NvbG9yOnZhcigtLXdhcm4pfS5iYWR7Y29sb3I6dmFyKC0tYmFkKX0ubXV0ZWR7Y29sb3I6dmFyKC0tbXV0ZWQpfQpjb2Rle2JhY2tncm91bmQ6IzI4MjgyODtwYWRkaW5nOjJweCA1cHg7Ym9yZGVyLXJhZGl1czo1cHg7d29yZC1icmVhazpicmVhay13b3JkfQp0YWJsZXt3aWR0aDoxMDAlO2JvcmRlci1jb2xsYXBzZTpjb2xsYXBzZX0KdGgsdGR7cGFkZGluZzo4cHggOXB4O2JvcmRlci1ib3R0b206MXB4IHNvbGlkICMzMzM7dGV4dC1hbGlnbjpsZWZ0O3ZlcnRpY2FsLWFsaWduOnRvcH0KdGh7Y29sb3I6dmFyKC0tZ29sZCl9Ci50YWd7ZGlzcGxheTppbmxpbmUtYmxvY2s7cGFkZGluZzoycHggN3B4O2JvcmRlci1yYWRpdXM6MTJweDtmb250LXNpemU6MTJweDtmb250LXdlaWdodDo3MDB9Ci50YWcuYWN0aXZle2JhY2tncm91bmQ6IzRhMmYxMjtjb2xvcjojZmZkNTk2fS50YWcuaGlzdG9yeXtiYWNrZ3JvdW5kOiMyNjMwM2I7Y29sb3I6I2M5ZDhlOH0KdWx7bGluZS1oZWlnaHQ6MS41fQpAbWVkaWEobWF4LXdpZHRoOjg1MHB4KXsuZ3JpZHtncmlkLXRlbXBsYXRlLWNvbHVtbnM6MWZyIDFmcn19Cjwvc3R5bGU+CjwvaGVhZD4KPGJvZHk+CjxkaXYgY2xhc3M9IndyYXAiPgogICAgPGgxPk1STCBWZW5kb3IgRGVwZW5kZW5jeSBBdWRpdDwvaDE+CiAgICA8ZGl2IGNsYXNzPSJtdXRlZCI+UkVBRC1PTkxZIHByb2R1Y3Rpb24gc2NhbiDCtyB2MDAxIMK3IGdlbmVyYXRlZCA5LzIwLzIwMjYgMTo1NzowMCBwbSBFVDwvZGl2PgoKICAgIDxkaXYgY2xhc3M9ImNhcmQiPgogICAgICAgIDxoMj5BY3R1YWwgcHJvZHVjdGlvbiBzb3VyY2U8L2gyPgogICAgICAgIDxwPgogICAgICAgICAgICBEb2N1bWVudCByb290OiA8Y29kZT48P3BocCBlY2hvIGgoJGRvY1Jvb3QpOyA/PjwvY29kZT48YnI+CiAgICAgICAgICAgIFZlbmRvciBwYXRoOiA8Y29kZT48P3BocCBlY2hvIGgoJHZlbmRvckRpcik7ID8+PC9jb2RlPgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0iZ29vZCI+PHN0cm9uZz5UaGlzIHJlcG9ydCBzY2FucyB0aGUgSG9zdGluZ2VyIGZpbGUgdHJlZSBkaXJlY3RseS4gSXQgZG9lcyBub3QgdXNlIEdpdEh1YiBhcyB0aGUgYXV0aG9yaXR5Ljwvc3Ryb25nPjwvcD4KICAgIDwvZGl2PgoKICAgIDxkaXYgY2xhc3M9ImNhcmQiPgogICAgICAgIDxoMj5WZW5kb3IgaW52ZW50b3J5PC9oMj4KICAgICAgICA8ZGl2IGNsYXNzPSJncmlkIj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ic3RhdCI+PGRpdiBjbGFzcz0ibGFiZWwiPlZlbmRvciBleGlzdHM8L2Rpdj48ZGl2IGNsYXNzPSJ2YWx1ZSA8P3BocCBlY2hvIGlzX2RpcigkdmVuZG9yRGlyKSA/ICdnb29kJyA6ICdiYWQnOyA/PiI+PD9waHAgZWNobyBpc19kaXIoJHZlbmRvckRpcikgPyAnWUVTJyA6ICdOTyc7ID8+PC9kaXY+PC9kaXY+CiAgICAgICAgICAgIDxkaXYgY2xhc3M9InN0YXQiPjxkaXYgY2xhc3M9ImxhYmVsIj5WZW5kb3IgZmlsZXM8L2Rpdj48ZGl2IGNsYXNzPSJ2YWx1ZSI+PD9waHAgZWNobyAoaW50KSR2ZW5kb3JGaWxlczsgPz48L2Rpdj48L2Rpdj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ic3RhdCI+PGRpdiBjbGFzcz0ibGFiZWwiPlZlbmRvciBmb2xkZXJzPC9kaXY+PGRpdiBjbGFzcz0idmFsdWUiPjw/cGhwIGVjaG8gKGludCkkdmVuZG9yRm9sZGVyczsgPz48L2Rpdj48L2Rpdj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ic3RhdCI+PGRpdiBjbGFzcz0ibGFiZWwiPlZlbmRvciBzaXplPC9kaXY+PGRpdiBjbGFzcz0idmFsdWUiPjw/cGhwIGVjaG8gaChmbXRfYnl0ZXMoJHZlbmRvckJ5dGVzKSk7ID8+PC9kaXY+PC9kaXY+CiAgICAgICAgPC9kaXY+CgogICAgICAgIDw/cGhwIGlmICghZW1wdHkoJHRvcExldmVsVmVuZG9yKSk6ID8+CiAgICAgICAgICAgIDxwPjxzdHJvbmc+VG9wLWxldmVsIGNvbnRlbnRzOjwvc3Ryb25nPgogICAgICAgICAgICA8P3BocAogICAgICAgICAgICAkcGFydHMgPSBbXTsKICAgICAgICAgICAgZm9yZWFjaCAoJHRvcExldmVsVmVuZG9yIGFzICRpdGVtKSB7CiAgICAgICAgICAgICAgICAkcGFydHNbXSA9ICRpdGVtWyduYW1lJ10gLiAnIFsnIC4gJGl0ZW1bJ3R5cGUnXSAuICddJzsKICAgICAgICAgICAgfQogICAgICAgICAgICBlY2hvIGgoaW1wbG9kZSgnIMK3ICcsICRwYXJ0cykpOwogICAgICAgICAgICA/PgogICAgICAgICAgICA8L3A+CiAgICAgICAgPD9waHAgZW5kaWY7ID8+CiAgICA8L2Rpdj4KCiAgICA8ZGl2IGNsYXNzPSJjYXJkIj4KICAgICAgICA8aDI+UmVmZXJlbmNlIHNjYW4gc3VtbWFyeTwvaDI+CiAgICAgICAgPGRpdiBjbGFzcz0iZ3JpZCI+CiAgICAgICAgICAgIDxkaXYgY2xhc3M9InN0YXQiPjxkaXYgY2xhc3M9ImxhYmVsIj5UZXh0L2NvZGUgZmlsZXMgc2Nhbm5lZDwvZGl2PjxkaXYgY2xhc3M9InZhbHVlIj48P3BocCBlY2hvIChpbnQpJGZpbGVzU2Nhbm5lZDsgPz48L2Rpdj48L2Rpdj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ic3RhdCI+PGRpdiBjbGFzcz0ibGFiZWwiPkJ5dGVzIHNjYW5uZWQ8L2Rpdj48ZGl2IGNsYXNzPSJ2YWx1ZSI+PD9waHAgZWNobyBoKGZtdF9ieXRlcygkYnl0ZXNTY2FubmVkKSk7ID8+PC9kaXY+PC9kaXY+CiAgICAgICAgICAgIDxkaXYgY2xhc3M9InN0YXQiPjxkaXYgY2xhc3M9ImxhYmVsIj5Ob24taGlzdG9yeSBoaXRzPC9kaXY+PGRpdiBjbGFzcz0idmFsdWUgPD9waHAgZWNobyBlbXB0eSgkYWN0aXZlSGl0cykgPyAnZ29vZCcgOiAnd2Fybic7ID8+Ij48P3BocCBlY2hvIGNvdW50KCRhY3RpdmVIaXRzKTsgPz48L2Rpdj48L2Rpdj4KICAgICAgICAgICAgPGRpdiBjbGFzcz0ic3RhdCI+PGRpdiBjbGFzcz0ibGFiZWwiPkhpc3RvcnkvcXVhcmFudGluZSBoaXRzPC9kaXY+PGRpdiBjbGFzcz0idmFsdWUiPjw/cGhwIGVjaG8gY291bnQoJGhpc3RvcnlIaXRzKTsgPz48L2Rpdj48L2Rpdj4KICAgICAgICA8L2Rpdj4KCiAgICAgICAgPD9waHAgaWYgKCRvdmVyc2l6ZVNraXBwZWQgPiAwKTogPz4KICAgICAgICAgICAgPHAgY2xhc3M9Indhcm4iPjxzdHJvbmc+Tm90ZTo8L3N0cm9uZz4gPD9waHAgZWNobyAoaW50KSRvdmVyc2l6ZVNraXBwZWQ7ID8+IHRleHQtbGlrZSBmaWxlKHMpIG92ZXIgNSBNQiB3ZXJlIGNvdW50ZWQgYnV0IG5vdCBjb250ZW50LXNjYW5uZWQuPC9wPgogICAgICAgIDw/cGhwIGVuZGlmOyA/PgoKICAgICAgICA8cCBjbGFzcz0iPD9waHAgZWNobyBlbXB0eSgkYWN0aXZlSGl0cykgPyAnZ29vZCcgOiAnd2Fybic7ID8+IiBzdHlsZT0iZm9udC13ZWlnaHQ6ODAwOyI+CiAgICAgICAgICAgIDw/cGhwIGVjaG8gZW1wdHkoJGFjdGl2ZUhpdHMpCiAgICAgICAgICAgICAgICA/ICdObyBub24taGlzdG9yeSByZWZlcmVuY2VzIHdlcmUgZm91bmQgb3V0c2lkZSAvdmVuZG9yLicKICAgICAgICAgICAgICAgIDogJ1JldmlldyB0aGUgbm9uLWhpc3RvcnkgcmVmZXJlbmNlcyBiZWxvdyBiZWZvcmUgcXVhcmFudGluaW5nIC92ZW5kb3IuJzsgPz4KICAgICAgICA8L3A+CiAgICA8L2Rpdj4KCiAgICA8ZGl2IGNsYXNzPSJjYXJkIj4KICAgICAgICA8aDI+UmVmZXJlbmNlcyBmb3VuZCBvdXRzaWRlIC92ZW5kb3I8L2gyPgoKICAgICAgICA8P3BocCBpZiAoZW1wdHkoJGhpdHMpKTogPz4KICAgICAgICAgICAgPHAgY2xhc3M9Imdvb2QiPjxzdHJvbmc+Tm8gbWF0Y2hpbmcgcmVmZXJlbmNlcyBmb3VuZC48L3N0cm9uZz48L3A+CiAgICAgICAgPD9waHAgZWxzZTogPz4KICAgICAgICAgICAgPHRhYmxlPgogICAgICAgICAgICAgICAgPHRoZWFkPgogICAgICAgICAgICAgICAgICAgIDx0cj48dGg+Q2xhc3NpZmljYXRpb248L3RoPjx0aD5GaWxlPC90aD48dGg+TWF0Y2hlZCB0ZXJtczwvdGg+PHRoPlNpemU8L3RoPjwvdHI+CiAgICAgICAgICAgICAgICA8L3RoZWFkPgogICAgICAgICAgICAgICAgPHRib2R5PgogICAgICAgICAgICAgICAgPD9waHAgZm9yZWFjaCAoJGhpdHMgYXMgJHJvdyk6ID8+CiAgICAgICAgICAgICAgICAgICAgPHRyPgogICAgICAgICAgICAgICAgICAgICAgICA8dGQ+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBpZiAoIWVtcHR5KCRyb3dbJ2hpc3RvcnknXSkpOiA/PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJ0YWcgaGlzdG9yeSI+aGlzdG9yeSAvIHF1YXJhbnRpbmU8L3NwYW4+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBlbHNlOiA/PgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJ0YWcgYWN0aXZlIj5yZXZpZXc8L3NwYW4+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8P3BocCBlbmRpZjsgPz4KICAgICAgICAgICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgICAgICAgICAgICAgPHRkPjxjb2RlPjw/cGhwIGVjaG8gaCgkcm93WydwYXRoJ10pOyA/PjwvY29kZT48L3RkPgogICAgICAgICAgICAgICAgICAgICAgICA8dGQ+PD9waHAgZWNobyBoKGltcGxvZGUoJywgJywgJHJvd1snbWF0Y2hlcyddKSk7ID8+PC90ZD4KICAgICAgICAgICAgICAgICAgICAgICAgPHRkPjw/cGhwIGVjaG8gaChmbXRfYnl0ZXMoKGludCkkcm93WydzaXplJ10pKTsgPz48L3RkPgogICAgICAgICAgICAgICAgICAgIDwvdHI+CiAgICAgICAgICAgICAgICA8P3BocCBlbmRmb3JlYWNoOyA/PgogICAgICAgICAgICAgICAgPC90Ym9keT4KICAgICAgICAgICAgPC90YWJsZT4KICAgICAgICA8P3BocCBlbmRpZjsgPz4KICAgIDwvZGl2PgoKICAgIDw/cGhwIGlmICghZW1wdHkoJGVycm9ycykpOiA/PgogICAgPGRpdiBjbGFzcz0iY2FyZCI+CiAgICAgICAgPGgyIGNsYXNzPSJiYWQiPlJlYWQgd2FybmluZ3M8L2gyPgogICAgICAgIDx1bD4KICAgICAgICAgICAgPD9waHAgZm9yZWFjaCAoJGVycm9ycyBhcyAkZXJyb3IpOiA/PgogICAgICAgICAgICAgICAgPGxpPjw/cGhwIGVjaG8gaCgkZXJyb3IpOyA/PjwvbGk+CiAgICAgICAgICAgIDw/cGhwIGVuZGZvcmVhY2g7ID8+CiAgICAgICAgPC91bD4KICAgIDwvZGl2PgogICAgPD9waHAgZW5kaWY7ID8+CgogICAgPGRpdiBjbGFzcz0iY2FyZCI+CiAgICAgICAgPGgyPlNhZmV0eTwvaDI+CiAgICAgICAgPHVsPgogICAgICAgICAgICA8bGk+Tm8gZmlsZXMgYXJlIGVkaXRlZC48L2xpPgogICAgICAgICAgICA8bGk+Tm8gZmlsZXMgb3IgZm9sZGVycyBhcmUgbW92ZWQsIHJlbmFtZWQsIHF1YXJhbnRpbmVkLCBvciBkZWxldGVkLjwvbGk+CiAgICAgICAgICAgIDxsaT5ObyBkYXRhYmFzZSwgc2NoZWR1bGVyLCBjcm9uLCBvciBXb3JkUHJlc3Mgc3RhdGUgaXMgY2hhbmdlZC48L2xpPgogICAgICAgICAgICA8bGk+VGhlIDxjb2RlPi92ZW5kb3I8L2NvZGU+IGZvbGRlciBpdHNlbGYgaXMgZXhjbHVkZWQgZnJvbSB0aGUgcmVmZXJlbmNlIHNlYXJjaCBzbyBpdCBkb2VzIG5vdCBtZXJlbHkgZmluZCBpdHMgb3duIENvbXBvc2VyIGNvZGUuPC9saT4KICAgICAgICAgICAgPGxpPklmIHRoaXMgYXVkaXQgbG9va3MgY2xlYW4sIHF1YXJhbnRpbmUgd2lsbCBiZSBhIHNlcGFyYXRlIHJldmVyc2libGUgaW5zdGFsbGVyLjwvbGk+CiAgICAgICAgPC91bD4KICAgIDwvZGl2Pgo8L2Rpdj4KPC9ib2R5Pgo8L2h0bWw+Cg==';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function lint_php_file(string $path): array
{
    if (!function_exists('shell_exec')) {
        return ['ok'=>false,'output'=>'shell_exec() is not available.'];
    }

    $output = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');

    if ($output === null) {
        return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    }

    $output = trim((string)$output);

    return [
        'ok'=>stripos($output, 'No syntax errors detected') !== false,
        'output'=>$output,
    ];
}

$realPath = __DIR__ . '/' . REAL_FILE_NAME;
$action = (string)($_POST['action'] ?? '');
$message = '';
$messageClass = 'info';
$lint = null;

if ($action === 'stage') {
    $payload = base64_decode(REAL_FILE_B64, true);

    if ($payload === false) {
        $message = 'FAIL — embedded audit payload could not be decoded.';
        $messageClass = 'bad';
    } elseif (hash('sha256', $payload) !== REAL_FILE_SHA256) {
        $message = 'FAIL — embedded audit payload hash did not match.';
        $messageClass = 'bad';
    } else {
        if (is_file($realPath)) {
            $existing = (string)@file_get_contents($realPath);
            if (hash('sha256', $existing) !== REAL_FILE_SHA256) {
                $message = 'FAIL — a different file already exists with the audit filename. Nothing was overwritten.';
                $messageClass = 'bad';
            }
        }

        if ($message === '' && !is_file($realPath)) {
            if (@file_put_contents($realPath, $payload, LOCK_EX) === false) {
                $message = 'FAIL — could not stage the audit file.';
                $messageClass = 'bad';
            } else {
                @chmod($realPath, 0644);
            }
        }

        if ($message === '') {
            $lint = lint_php_file($realPath);
            if (!empty($lint['ok'])) {
                $message = 'PASS — audit file staged, hash-matched, and PHP lint passed.';
                $messageClass = 'good';
            } else {
                $message = 'FAIL — audit file was staged but did not pass PHP lint.';
                $messageClass = 'bad';
            }
        }
    }
}

if ($lint === null && is_file($realPath)) {
    $existing = (string)@file_get_contents($realPath);
    if (hash('sha256', $existing) === REAL_FILE_SHA256) {
        $lint = lint_php_file($realPath);
    }
}

$ready = is_file($realPath)
    && $lint !== null
    && !empty($lint['ok'])
    && hash_file('sha256', $realPath) === REAL_FILE_SHA256;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Vendor Audit Lint Gate</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:950px;margin:0 auto}
h1{color:var(--gold);margin:0 0 6px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.notice{margin:14px 0;padding:12px 14px;border-radius:10px;border:1px solid #36506f;background:#142033}
.notice.good{border-color:#327a4b;background:#13271a;color:#a8efbf}
.notice.bad{border-color:#983f3f;background:#2a1515;color:#ffb0b0}
.pass{color:var(--good);font-weight:800}.fail{color:var(--bad);font-weight:800}.muted{color:var(--muted)}
code{background:#282828;padding:2px 5px;border-radius:5px;word-break:break-all}
.buttons{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:white;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;font-size:14px}
.stage{background:#248c4b}.open{background:#276fca}
pre{white-space:pre-wrap;word-break:break-word;background:#111;border:1px solid #333;border-radius:10px;padding:12px}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Vendor Audit Lint Gate</h1>
    <div class="muted">Read-only production audit · generated 9/20/2026 1:57:00 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Audit file</h2>
        <p><code><?php echo h(REAL_FILE_NAME); ?></code></p>
        <p class="muted">This wrapper stages the exact audit file, verifies SHA-256, and runs <code>php -l</code> before it can be opened.</p>
    </div>

    <div class="card">
        <h2>Lint status</h2>

        <?php if ($ready): ?>
            <p class="pass">PASS — audit file is staged, hash-matched, and lint-clean.</p>
        <?php elseif ($lint !== null): ?>
            <p class="fail">NOT READY — review lint result.</p>
        <?php else: ?>
            <p class="muted">Not staged yet.</p>
        <?php endif; ?>

        <?php if ($lint !== null): ?>
            <pre><?php echo h((string)$lint['output']); ?></pre>
        <?php endif; ?>

        <div class="buttons">
            <form method="post">
                <input type="hidden" name="action" value="stage">
                <button class="stage" type="submit">Stage + Lint Audit</button>
            </form>

            <?php if ($ready): ?>
                <a class="btn open" href="<?php echo h(REAL_FILE_NAME); ?>">Open Read-Only Audit</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
