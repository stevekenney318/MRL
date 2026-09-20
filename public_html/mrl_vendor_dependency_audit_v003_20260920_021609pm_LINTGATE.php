<?php
declare(strict_types=1);

/**
 * mrl_vendor_dependency_audit_v003_20260920_021609pm_LINTGATE.php
 * VERSION: v001
 * GENERATED: 9/20/2026 2:16:09 pm ET
 */

date_default_timezone_set('America/New_York');

const REAL_FILE_NAME = 'mrl_vendor_dependency_audit_v003_20260920_021609pm.php';
const REAL_FILE_SHA256 = '58cf9e3a9bc30a8b2f55fa93b7384e9b3918944c6bbf374163b046aa29a9bcc1';
const REAL_FILE_B64 = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogbXJsX3ZlbmRvcl9kZXBlbmRlbmN5X2F1ZGl0X3YwMDNfMjAyNjA5MjBfMDIxNjA5cG0ucGhwCiAqCiAqIFZFUlNJT046IHYwMDMKICogR0VORVJBVEVEOiA5LzIwLzIwMjYgMjoxNjowOSBwbSBFVAogKgogKiBQVVJQT1NFOgogKiAtIFJFQUQtT05MWSB0YXJnZXRlZCBmb2xsb3ctdXAgdG8gVmVuZG9yIERlcGVuZGVuY3kgQXVkaXQgdjAwMi4KICogLSBJbnNwZWN0cyB0aGUgZXhhY3QgOSAiYWN0aXZlLXJldmlldyIgZmlsZXMgZnJvbSB2MDAyIG9uIHRoZSBwcm9kdWN0aW9uIHNlcnZlci4KICogLSBTaG93cyBtYXRjaGluZyBzb3VyY2UgbGluZXMvY29udGV4dCBhbmQgZmxhZ3MgYWN0dWFsIFBIUCBpbmNsdWRlL3JlcXVpcmUgdXNhZ2UuCiAqIC0gRG9lcyBOT1QgbW92ZSwgZGVsZXRlLCByZW5hbWUsIGVkaXQsIG9yIGV4ZWN1dGUgYW55IHByb2R1Y3Rpb24gZmlsZS4KICovCgpkYXRlX2RlZmF1bHRfdGltZXpvbmVfc2V0KCdBbWVyaWNhL05ld19Zb3JrJyk7CgpmdW5jdGlvbiBoKCR2YWx1ZSk6IHN0cmluZwp7CiAgICByZXR1cm4gaHRtbHNwZWNpYWxjaGFycygoc3RyaW5nKSR2YWx1ZSwgRU5UX1FVT1RFUywgJ1VURi04Jyk7Cn0KCiRkb2NSb290ID0gcnRyaW0oKHN0cmluZykoJF9TRVJWRVJbJ0RPQ1VNRU5UX1JPT1QnXSA/PyBfX0RJUl9fKSwgJy9cXCcpOwokcm9vdFZlbmRvciA9ICRkb2NSb290IC4gJy92ZW5kb3InOwoKJHRhcmdldHMgPSBbJ2NvbXBvc2VyLmpzb24nLCdjcGFzcy5waHAnLCdmcGFzcy5waHAnLCdqcy9qb3Rmb3JtLmpzJywncmFjZV9yZXN1bHRzL3dlZWtseV9zdGFuZGluZ3MucGhwJywncmFjZV9yZXN1bHRzL3dlZWtseV9zdGFuZGluZ3NfdjA2NS5waHAnLCdyZWdpc3Rlci5waHAnLCd0ZWFtX2NoYXJ0LnBocCcsJ3ZlcmlmeS5waHAnXTsKCiRuZWVkbGVzID0gWwogICAgJ3ZlbmRvci9hdXRvbG9hZC5waHAnLAogICAgJy92ZW5kb3IvYXV0b2xvYWQucGhwJywKICAgICdQaHBPZmZpY2UnLAogICAgJ1BocFNwcmVhZHNoZWV0JywKICAgICdDb21wb3NlclxcQXV0b2xvYWQnLAogICAgJ2NvbXBvc2VyL2F1dG9sb2FkJywKICAgICcvdmVuZG9yLycsCl07Cgokcm93cyA9IFtdOwokZXJyb3JzID0gW107Cgpmb3JlYWNoICgkdGFyZ2V0cyBhcyAkcmVsYXRpdmUpIHsKICAgICRwYXRoID0gJGRvY1Jvb3QgLiAnLycgLiAkcmVsYXRpdmU7CgogICAgJHJvdyA9IFsKICAgICAgICAncmVsYXRpdmUnID0+ICRyZWxhdGl2ZSwKICAgICAgICAnZXhpc3RzJyA9PiBpc19maWxlKCRwYXRoKSwKICAgICAgICAnbWF0Y2hlcycgPT4gW10sCiAgICAgICAgJ2luY2x1ZGVfaGl0cycgPT4gW10sCiAgICAgICAgJ3Jvb3RfdmVuZG9yX2xpdGVyYWwnID0+IGZhbHNlLAogICAgICAgICdub3RlcycgPT4gW10sCiAgICBdOwoKICAgIGlmICghaXNfZmlsZSgkcGF0aCkpIHsKICAgICAgICAkcm93Wydub3RlcyddW10gPSAnRmlsZSBub3QgZm91bmQuJzsKICAgICAgICAkcm93c1tdID0gJHJvdzsKICAgICAgICBjb250aW51ZTsKICAgIH0KCiAgICAkY29udGVudCA9IEBmaWxlX2dldF9jb250ZW50cygkcGF0aCk7CiAgICBpZiAoJGNvbnRlbnQgPT09IGZhbHNlKSB7CiAgICAgICAgJHJvd1snbm90ZXMnXVtdID0gJ0NvdWxkIG5vdCByZWFkIGZpbGUuJzsKICAgICAgICAkcm93c1tdID0gJHJvdzsKICAgICAgICBjb250aW51ZTsKICAgIH0KCiAgICAkbGluZXMgPSBwcmVnX3NwbGl0KCcvXFIvJywgJGNvbnRlbnQpOwogICAgaWYgKCFpc19hcnJheSgkbGluZXMpKSB7CiAgICAgICAgJGxpbmVzID0gW107CiAgICB9CgogICAgZm9yZWFjaCAoJGxpbmVzIGFzICRpZHggPT4gJGxpbmUpIHsKICAgICAgICAkbWF0Y2hlZE5lZWRsZXMgPSBbXTsKCiAgICAgICAgZm9yZWFjaCAoJG5lZWRsZXMgYXMgJG5lZWRsZSkgewogICAgICAgICAgICBpZiAoc3RyaXBvcygkbGluZSwgJG5lZWRsZSkgIT09IGZhbHNlKSB7CiAgICAgICAgICAgICAgICAkbWF0Y2hlZE5lZWRsZXNbXSA9ICRuZWVkbGU7CiAgICAgICAgICAgIH0KICAgICAgICB9CgogICAgICAgIGlmICghZW1wdHkoJG1hdGNoZWROZWVkbGVzKSkgewogICAgICAgICAgICAkcm93WydtYXRjaGVzJ11bXSA9IFsKICAgICAgICAgICAgICAgICdsaW5lJyA9PiAkaWR4ICsgMSwKICAgICAgICAgICAgICAgICd0ZXh0JyA9PiB0cmltKCRsaW5lKSwKICAgICAgICAgICAgICAgICduZWVkbGVzJyA9PiBhcnJheV92YWx1ZXMoYXJyYXlfdW5pcXVlKCRtYXRjaGVkTmVlZGxlcykpLAogICAgICAgICAgICBdOwogICAgICAgIH0KCiAgICAgICAgLy8gU3Ryb25nZXIgc2lnbmFsOiBpbmNsdWRlL3JlcXVpcmUgc3RhdGVtZW50IHRoYXQgcmVmZXJlbmNlcyB2ZW5kb3IuCiAgICAgICAgaWYgKHByZWdfbWF0Y2goJy9cYihyZXF1aXJlfHJlcXVpcmVfb25jZXxpbmNsdWRlfGluY2x1ZGVfb25jZSlcYi4qdmVuZG9yL2knLCAkbGluZSkpIHsKICAgICAgICAgICAgJHJvd1snaW5jbHVkZV9oaXRzJ11bXSA9IFsKICAgICAgICAgICAgICAgICdsaW5lJyA9PiAkaWR4ICsgMSwKICAgICAgICAgICAgICAgICd0ZXh0JyA9PiB0cmltKCRsaW5lKSwKICAgICAgICAgICAgXTsKICAgICAgICB9CgogICAgICAgIC8vIFN0cm9uZyBzaWduYWw6IGV4cGxpY2l0IGRvY3VtZW50LXJvb3Qgb3IgX19ESVJfXyByZWZlcmVuY2UgdG8gcm9vdCAvdmVuZG9yLgogICAgICAgIGlmIChwcmVnX21hdGNoKCcvKD86RE9DVU1FTlRfUk9PVHxfX0RJUl9ffGRpcm5hbWVccypcKCkuKnZlbmRvci9pJywgJGxpbmUpCiAgICAgICAgICAgIHx8IHByZWdfbWF0Y2goJyNbIlwnXS8/dmVuZG9yL2F1dG9sb2FkXC5waHBbIlwnXSNpJywgJGxpbmUpKSB7CiAgICAgICAgICAgICRyb3dbJ3Jvb3RfdmVuZG9yX2xpdGVyYWwnXSA9IHRydWU7CiAgICAgICAgfQogICAgfQoKICAgIC8vIEZpbGUtc3BlY2lmaWMgaW50ZXJwcmV0YXRpb24gaGVscGVycy4KICAgIGlmICgkcmVsYXRpdmUgPT09ICdjb21wb3Nlci5qc29uJykgewogICAgICAgICRyb3dbJ25vdGVzJ11bXSA9ICdEZXBlbmRlbmN5IG1hbmlmZXN0IG9ubHk7IG5vdCBydW50aW1lIGV4ZWN1dGlvbiBieSBpdHNlbGYuJzsKICAgIH0KCiAgICBpZiAoJHJlbGF0aXZlID09PSAndGVhbV9jaGFydC5waHAnKSB7CiAgICAgICAgJHJvd1snbm90ZXMnXVtdID0gJ0V4cGVjdGVkIHRvIGNvbnRhaW4gaGlzdG9yaWNhbC9jaGFuZ2Vsb2cgd29yZGluZyBhZnRlciB2MDIzOyBpbnNwZWN0IHdoZXRoZXIgYW55IGluY2x1ZGUvcmVxdWlyZSByZW1haW5zLic7CiAgICB9CgogICAgaWYgKCRyZWxhdGl2ZSA9PT0gJ3JhY2VfcmVzdWx0cy93ZWVrbHlfc3RhbmRpbmdzLnBocCcpIHsKICAgICAgICAkcm93Wydub3RlcyddW10gPSAnS25vd24gcHVyZS1QSFAgWExTWCBwYWdlOyBlYXJsaWVyIGNvbW1lbnRzIG1heSBtZW50aW9uIFBocFNwcmVhZHNoZWV0IGZvciBjb21wYXJpc29uLic7CiAgICB9CgogICAgaWYgKHByZWdfbWF0Y2goJy8oPzpefFwvKSg/Oi4qX3ZcZHszfVwucGhwKSQvaScsICRyZWxhdGl2ZSkpIHsKICAgICAgICAkcm93Wydub3RlcyddW10gPSAnVmVyc2lvbmVkIHN0YW5kYWxvbmUgY29weTsgbGlrZWx5IGhpc3RvcmljYWwgdW5sZXNzIGxpbmtlZCBlbHNld2hlcmUuJzsKICAgIH0KCiAgICAkcm93c1tdID0gJHJvdzsKfQoKPz4KPCFkb2N0eXBlIGh0bWw+CjxodG1sIGxhbmc9ImVuIj4KPGhlYWQ+CjxtZXRhIGNoYXJzZXQ9InV0Zi04Ij4KPG1ldGEgbmFtZT0idmlld3BvcnQiIGNvbnRlbnQ9IndpZHRoPWRldmljZS13aWR0aCxpbml0aWFsLXNjYWxlPTEiPgo8dGl0bGU+TVJMIFZlbmRvciBEZXBlbmRlbmN5IEF1ZGl0IHYwMDM8L3RpdGxlPgo8c3R5bGU+Cjpyb290e2NvbG9yLXNjaGVtZTpkYXJrOy0tYmc6IzEwMTAxMDstLXBhbmVsOiMxYjFiMWI7LS1saW5lOiMzZDNkM2Q7LS10ZXh0OiNlZWU7LS1tdXRlZDojYWFhOy0tZ29vZDojNjZkZjhkOy0td2FybjojZmZkMTY2Oy0tYmFkOiNmZjc0NzQ7LS1nb2xkOiNmMmM5OGV9Cip7Ym94LXNpemluZzpib3JkZXItYm94fQpib2R5e21hcmdpbjowO3BhZGRpbmc6MjJweDtiYWNrZ3JvdW5kOnZhcigtLWJnKTtjb2xvcjp2YXIoLS10ZXh0KTtmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZn0KLndyYXB7bWF4LXdpZHRoOjEyNTBweDttYXJnaW46MCBhdXRvfQpoMXttYXJnaW46MCAwIDZweDtjb2xvcjp2YXIoLS1nb2xkKX0KaDJ7bWFyZ2luOjAgMCAxMHB4fQouY2FyZHttYXJnaW46MTRweCAwO3BhZGRpbmc6MTZweDtiYWNrZ3JvdW5kOnZhcigtLXBhbmVsKTtib3JkZXI6MXB4IHNvbGlkIHZhcigtLWxpbmUpO2JvcmRlci1yYWRpdXM6MTRweH0KLmdvb2R7Y29sb3I6dmFyKC0tZ29vZCl9Lndhcm57Y29sb3I6dmFyKC0td2Fybil9LmJhZHtjb2xvcjp2YXIoLS1iYWQpfS5tdXRlZHtjb2xvcjp2YXIoLS1tdXRlZCl9CmNvZGV7YmFja2dyb3VuZDojMjgyODI4O3BhZGRpbmc6MnB4IDVweDtib3JkZXItcmFkaXVzOjVweDt3b3JkLWJyZWFrOmJyZWFrLXdvcmR9CnRhYmxle3dpZHRoOjEwMCU7Ym9yZGVyLWNvbGxhcHNlOmNvbGxhcHNlfQp0aCx0ZHtwYWRkaW5nOjhweCA5cHg7Ym9yZGVyLWJvdHRvbToxcHggc29saWQgIzMzMzt0ZXh0LWFsaWduOmxlZnQ7dmVydGljYWwtYWxpZ246dG9wfQp0aHtjb2xvcjp2YXIoLS1nb2xkKX0KcHJle3doaXRlLXNwYWNlOnByZS13cmFwO3dvcmQtYnJlYWs6YnJlYWstd29yZDtiYWNrZ3JvdW5kOiMxMTE7Ym9yZGVyOjFweCBzb2xpZCAjMzMzO2JvcmRlci1yYWRpdXM6OHB4O3BhZGRpbmc6MTBweDttYXJnaW46NnB4IDB9Ci50YWd7ZGlzcGxheTppbmxpbmUtYmxvY2s7cGFkZGluZzoycHggN3B4O2JvcmRlci1yYWRpdXM6MTJweDtmb250LXNpemU6MTJweDtmb250LXdlaWdodDo3MDB9Ci50YWcuZ29vZHtiYWNrZ3JvdW5kOiMxNjMzMjF9LnRhZy53YXJue2JhY2tncm91bmQ6IzRhMmYxMn0udGFnLmJhZHtiYWNrZ3JvdW5kOiM0YjFmMWZ9CnVse2xpbmUtaGVpZ2h0OjEuNX0KPC9zdHlsZT4KPC9oZWFkPgo8Ym9keT4KPGRpdiBjbGFzcz0id3JhcCI+CiAgICA8aDE+TVJMIFZlbmRvciBEZXBlbmRlbmN5IEF1ZGl0IHYwMDM8L2gxPgogICAgPGRpdiBjbGFzcz0ibXV0ZWQiPlJFQUQtT05MWSB0YXJnZXRlZCBwcm9kdWN0aW9uIGluc3BlY3Rpb24gwrcgZ2VuZXJhdGVkIDkvMjAvMjAyNiAyOjE2OjA5IHBtIEVUPC9kaXY+CgogICAgPGRpdiBjbGFzcz0iY2FyZCI+CiAgICAgICAgPGgyPlB1cnBvc2U8L2gyPgogICAgICAgIDxwPgogICAgICAgICAgICBUaGlzIGV4YW1pbmVzIHRoZSBleGFjdCA5IGZpbGVzIHYwMDIgbWFya2VkIGZvciBhY3RpdmUgcmV2aWV3IGFuZCBzaG93cwogICAgICAgICAgICB0aGUgYWN0dWFsIG1hdGNoaW5nIHNvdXJjZSBsaW5lcyBmcm9tIHRoZSBIb3N0aW5nZXIgcHJvZHVjdGlvbiBmaWxlcy4KICAgICAgICA8L3A+CiAgICAgICAgPHA+CiAgICAgICAgICAgIFJvb3QgdmVuZG9yIHVuZGVyIGNvbnNpZGVyYXRpb246CiAgICAgICAgICAgIDxjb2RlPjw/cGhwIGVjaG8gaCgkcm9vdFZlbmRvcik7ID8+PC9jb2RlPgogICAgICAgIDwvcD4KICAgIDwvZGl2PgoKICAgIDw/cGhwIGZvcmVhY2ggKCRyb3dzIGFzICRyb3cpOiA/PgogICAgPGRpdiBjbGFzcz0iY2FyZCI+CiAgICAgICAgPGgyPjw/cGhwIGVjaG8gaCgkcm93WydyZWxhdGl2ZSddKTsgPz48L2gyPgoKICAgICAgICA8P3BocCBpZiAoISRyb3dbJ2V4aXN0cyddKTogPz4KICAgICAgICAgICAgPHAgY2xhc3M9ImJhZCI+PHN0cm9uZz5NSVNTSU5HPC9zdHJvbmc+PC9wPgogICAgICAgIDw/cGhwIGVsc2U6ID8+CiAgICAgICAgICAgIDxwPgogICAgICAgICAgICAgICAgUEhQIGluY2x1ZGUvcmVxdWlyZSBsaW5lcyByZWZlcmVuY2luZyB2ZW5kb3I6CiAgICAgICAgICAgICAgICA8P3BocCBpZiAoZW1wdHkoJHJvd1snaW5jbHVkZV9oaXRzJ10pKTogPz4KICAgICAgICAgICAgICAgICAgICA8c3BhbiBjbGFzcz0idGFnIGdvb2QiPk5PTkU8L3NwYW4+CiAgICAgICAgICAgICAgICA8P3BocCBlbHNlOiA/PgogICAgICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJ0YWcgYmFkIj48P3BocCBlY2hvIGNvdW50KCRyb3dbJ2luY2x1ZGVfaGl0cyddKTsgPz4gRk9VTkQ8L3NwYW4+CiAgICAgICAgICAgICAgICA8P3BocCBlbmRpZjsgPz4KCiAgICAgICAgICAgICAgICAmbmJzcDsmbmJzcDsgUm9vdC12ZW5kb3Itc3R5bGUgbGl0ZXJhbDoKICAgICAgICAgICAgICAgIDw/cGhwIGlmICgkcm93Wydyb290X3ZlbmRvcl9saXRlcmFsJ10pOiA/PgogICAgICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJ0YWcgd2FybiI+UE9TU0lCTEU8L3NwYW4+CiAgICAgICAgICAgICAgICA8P3BocCBlbHNlOiA/PgogICAgICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJ0YWcgZ29vZCI+Tk88L3NwYW4+CiAgICAgICAgICAgICAgICA8P3BocCBlbmRpZjsgPz4KICAgICAgICAgICAgPC9wPgoKICAgICAgICAgICAgPD9waHAgaWYgKCFlbXB0eSgkcm93Wydub3RlcyddKSk6ID8+CiAgICAgICAgICAgICAgICA8dWw+CiAgICAgICAgICAgICAgICA8P3BocCBmb3JlYWNoICgkcm93Wydub3RlcyddIGFzICRub3RlKTogPz4KICAgICAgICAgICAgICAgICAgICA8bGkgY2xhc3M9Im11dGVkIj48P3BocCBlY2hvIGgoJG5vdGUpOyA/PjwvbGk+CiAgICAgICAgICAgICAgICA8P3BocCBlbmRmb3JlYWNoOyA/PgogICAgICAgICAgICAgICAgPC91bD4KICAgICAgICAgICAgPD9waHAgZW5kaWY7ID8+CgogICAgICAgICAgICA8P3BocCBpZiAoIWVtcHR5KCRyb3dbJ2luY2x1ZGVfaGl0cyddKSk6ID8+CiAgICAgICAgICAgICAgICA8aDM+SW5jbHVkZSAvIHJlcXVpcmUgZXZpZGVuY2U8L2gzPgogICAgICAgICAgICAgICAgPD9waHAgZm9yZWFjaCAoJHJvd1snaW5jbHVkZV9oaXRzJ10gYXMgJGhpdCk6ID8+CiAgICAgICAgICAgICAgICAgICAgPHByZT5MaW5lIDw/cGhwIGVjaG8gKGludCkkaGl0WydsaW5lJ107ID8+OiA8P3BocCBlY2hvIGgoJGhpdFsndGV4dCddKTsgPz48L3ByZT4KICAgICAgICAgICAgICAgIDw/cGhwIGVuZGZvcmVhY2g7ID8+CiAgICAgICAgICAgIDw/cGhwIGVuZGlmOyA/PgoKICAgICAgICAgICAgPGgzPkFsbCBtYXRjaGluZyBsaW5lczwvaDM+CiAgICAgICAgICAgIDw/cGhwIGlmIChlbXB0eSgkcm93WydtYXRjaGVzJ10pKTogPz4KICAgICAgICAgICAgICAgIDxwIGNsYXNzPSJnb29kIj5ObyBtYXRjaGluZyBsaW5lcyByZW1haW4uPC9wPgogICAgICAgICAgICA8P3BocCBlbHNlOiA/PgogICAgICAgICAgICAgICAgPD9waHAgZm9yZWFjaCAoJHJvd1snbWF0Y2hlcyddIGFzICRoaXQpOiA/PgogICAgICAgICAgICAgICAgICAgIDxwcmU+TGluZSA8P3BocCBlY2hvIChpbnQpJGhpdFsnbGluZSddOyA/PiBbPD9waHAgZWNobyBoKGltcGxvZGUoJywgJywgJGhpdFsnbmVlZGxlcyddKSk7ID8+XQo8P3BocCBlY2hvIGgoJGhpdFsndGV4dCddKTsgPz48L3ByZT4KICAgICAgICAgICAgICAgIDw/cGhwIGVuZGZvcmVhY2g7ID8+CiAgICAgICAgICAgIDw/cGhwIGVuZGlmOyA/PgogICAgICAgIDw/cGhwIGVuZGlmOyA/PgogICAgPC9kaXY+CiAgICA8P3BocCBlbmRmb3JlYWNoOyA/PgoKICAgIDxkaXYgY2xhc3M9ImNhcmQiPgogICAgICAgIDxoMj5TYWZldHk8L2gyPgogICAgICAgIDx1bD4KICAgICAgICAgICAgPGxpPk5vIGZpbGVzIGFyZSBlZGl0ZWQuPC9saT4KICAgICAgICAgICAgPGxpPk5vIGZpbGVzIG9yIGZvbGRlcnMgYXJlIG1vdmVkLCByZW5hbWVkLCBxdWFyYW50aW5lZCwgb3IgZGVsZXRlZC48L2xpPgogICAgICAgICAgICA8bGk+Tm8gUEhQIHRhcmdldCBmaWxlIGlzIGV4ZWN1dGVkIGJ5IHRoaXMgYXVkaXQuPC9saT4KICAgICAgICAgICAgPGxpPk5vIGRhdGFiYXNlLCBzY2hlZHVsZXIsIGNyb24sIG9yIFdvcmRQcmVzcyBzdGF0ZSBpcyBjaGFuZ2VkLjwvbGk+CiAgICAgICAgPC91bD4KICAgIDwvZGl2Pgo8L2Rpdj4KPC9ib2R5Pgo8L2h0bWw+Cg==';

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
<title>MRL Vendor Audit v003 Lint Gate</title>
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
    <h1>MRL Vendor Audit v003 Lint Gate</h1>
    <div class="muted">Targeted production inspection · generated 9/20/2026 2:16:09 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Audit file</h2>
        <p><code><?php echo h(REAL_FILE_NAME); ?></code></p>
        <p class="muted">Stages the exact read-only audit, verifies SHA-256, and runs <code>php -l</code>.</p>
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
