<?php
declare(strict_types=1);

/**
 * install_mrl_team_redesign.php
 *
 * VERSION: v007
 * LAST MODIFIED: 8/27/2026 6:57:28 pm
 *
 * PURPOSE:
 * Fix Admin menu items disappearing after Content Manager save/reorder.
 *
 * EXPECTED INPUT:
 * - team_redesign.php VERSION: v006
 * - /mrl_team/admin_team_page_content.php VERSION: v002
 *
 * CHANGELOG:
 *
 * v007 (8/27/2026 6:57:28 pm)
 * - FIX: Repairs Admin panel enabled flags if a prior v002 save disabled every item.
 * - FIX: Content Manager v003 uses explicit indexed names for all row fields.
 * - FIX: Reordering now keeps Enabled/New-tab/Remove state aligned to the correct row.
 * - PRESERVE: Current layout, all themes, JSON order/content, charts, profile,
 *             production team.php and production profile.php.
 */

date_default_timezone_set('America/New_York');

$base=__DIR__;
$team=$base.'/team_redesign.php';
$manager=$base.'/mrl_team/admin_team_page_content.php';
$json=$base.'/mrl_team/mrl_team_page_content.json';
$prodTeam=$base.'/team.php';
$prodProfile=$base.'/profile.php';

function h(string $v):string{return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
function srow(string $l,bool $ok,string $d=''):void{echo '<tr><td>'.h($l).'</td><td class="'.($ok?'ok':'bad').'">'.($ok?'PASS':'FAIL').'</td><td>'.h($d).'</td></tr>';}
function aw(string $p,string $d):bool{$t=$p.'.tmp_'.bin2hex(random_bytes(4));if(file_put_contents($t,$d,LOCK_EX)===false)return false;if(!@rename($t,$p)){@unlink($t);return false;}return true;}

$teamSrc=is_file($team)?(string)file_get_contents($team):'';
$mgrSrc=is_file($manager)?(string)file_get_contents($manager):'';
$jsonRaw=is_file($json)?(string)file_get_contents($json):'';
$data=$jsonRaw!==''?json_decode($jsonRaw,true):null;

$teamOk=strpos($teamSrc,'VERSION: v006')!==false;
$mgrOk=strpos($mgrSrc,'VERSION: v002')!==false;
$jsonOk=is_array($data);

$teamV007=$teamSrc;
if($teamOk){
 $teamV007=preg_replace('/VERSION:\s*v006/','VERSION: v007',$teamV007,1,$c1);
 $teamV007=preg_replace('/LAST MODIFIED:\s*8\/27\/2026 6:33:12 pm/','LAST MODIFIED: 8/27/2026 6:57:28 pm',$teamV007,1,$c2);
 $change=" *\n * v007 (8/27/2026 6:57:28 pm)\n"
   ." * - FIX: Admin menu data-state repair / Content Manager v003 compatibility.\n"
   ." * - PRESERVE: No visual/chart/theme changes.\n";
 $teamV007=preg_replace('/(\* CHANGELOG:\R)/','$1'.$change,$teamV007,1,$cc);
 $teamOk=$c1===1&&$c2===1&&$cc===1;
}

$managerV003=base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogYWRtaW5fdGVhbV9wYWdlX2NvbnRlbnQucGhwCiAqCiAqIFZFUlNJT046IHYwMDMKICogTEFTVCBNT0RJRklFRDogOC8yNy8yMDI2IDY6NTc6MjggcG0KICoKICogQWRtaW4tb25seSBlZGl0b3IgZm9yIEpTT04tZHJpdmVuIFRlYW0gUGFnZSBjb250ZW50LgogKgogKiBDSEFOR0VMT0c6CiAqCiAqIHYwMDMgKDgvMjcvMjAyNiA2OjU3OjI4IHBtKQogKiAtIEZJWDogRXZlcnkgZWRpdGFibGUgcm93IG5vdyB1c2VzIGV4cGxpY2l0IGluZGV4ZWQgZmllbGQgbmFtZXMuCiAqIC0gRklYOiBFbmFibGVkL05ldy10YWIvUmVtb3ZlIGNoZWNrYm94ZXMgcmVtYWluIGFsaWduZWQgYWZ0ZXIgVXAvRG93biBtb3Zlcy4KICogLSBQUkVTRVJWRTogRm91ciBlZGl0YWJsZSBwYW5lbHMgYW5kIGZpeGVkIE1hbmFnZSBUZWFtIFBhZ2UgQ29udGVudCBjb250cm9sLgogKgogKiB2MDAyICg4LzI3LzIwMjYgNjozMzoxMiBwbSkKICogLSBBZGRlZCBhbGwgZm91ciBwYW5lbHMgYW5kIFVwL0Rvd24gb3JkZXJpbmcuCiAqLwoKc2Vzc2lvbl9zdGFydCgpOwoKcmVxdWlyZV9vbmNlIGRpcm5hbWUoX19ESVJfXykgLiAnL2NsYXNzLnVzZXIucGhwJzsKJHVzZXJfaG9tZSA9IG5ldyBVU0VSKCk7CgppZiAoISR1c2VyX2hvbWUtPmlzX2xvZ2dlZF9pbigpKSB7CiAgICAkdXNlcl9ob21lLT5yZWRpcmVjdCgnL2xvZ2luLnBocCcpOwogICAgZXhpdDsKfQoKZGF0ZV9kZWZhdWx0X3RpbWV6b25lX3NldCgnQW1lcmljYS9OZXdfWW9yaycpOwpyZXF1aXJlIGRpcm5hbWUoX19ESVJfXykgLiAnL2NvbmZpZy5waHAnOwpyZXF1aXJlIGRpcm5hbWUoX19ESVJfXykgLiAnL2NvbmZpZ19tcmwucGhwJzsKCiR1aWQgPSAoaW50KSgkX1NFU1NJT05bJ3VzZXJTZXNzaW9uJ10gPz8gMCk7CmlmICghaXNBZG1pbigkdWlkKSkgewogICAgaHR0cF9yZXNwb25zZV9jb2RlKDQwMyk7CiAgICBleGl0KCdBZG1pbiBhY2Nlc3MgcmVxdWlyZWQuJyk7Cn0KCiRjb250ZW50UGF0aCA9IF9fRElSX18gLiAnL21ybF90ZWFtX3BhZ2VfY29udGVudC5qc29uJzsKCmZ1bmN0aW9uIGF0cGNfaCgkdik6IHN0cmluZyB7IHJldHVybiBodG1sc3BlY2lhbGNoYXJzKChzdHJpbmcpJHYsIEVOVF9RVU9URVMsICdVVEYtOCcpOyB9CgpmdW5jdGlvbiBhdHBjX2xvYWQoc3RyaW5nICRwYXRoKTogYXJyYXkKewogICAgJHJhdyA9IGlzX2ZpbGUoJHBhdGgpID8gZmlsZV9nZXRfY29udGVudHMoJHBhdGgpIDogJyc7CiAgICAkZGF0YSA9IGlzX3N0cmluZygkcmF3KSA/IGpzb25fZGVjb2RlKCRyYXcsIHRydWUpIDogbnVsbDsKICAgIHJldHVybiBpc19hcnJheSgkZGF0YSkgPyAkZGF0YSA6IFtdOwp9CgpmdW5jdGlvbiBhdHBjX2NsZWFuX3VybChzdHJpbmcgJHVybCk6IHN0cmluZwp7CiAgICAkdXJsID0gdHJpbSgkdXJsKTsKICAgIGlmICgkdXJsID09PSAnJykgcmV0dXJuICcnOwogICAgaWYgKCR1cmxbMF0gPT09ICcvJykgcmV0dXJuICR1cmw7CiAgICBpZiAocHJlZ19tYXRjaCgnfl5odHRwcz86Ly9+aScsICR1cmwpKSByZXR1cm4gJHVybDsKICAgIHJldHVybiAnJzsKfQoKZnVuY3Rpb24gYXRwY19idWlsZF9wYW5lbChhcnJheSAkcG9zdCwgc3RyaW5nICRrZXksIHN0cmluZyAkZmFsbGJhY2spOiBhcnJheQp7CiAgICAkdGl0bGUgPSB0cmltKChzdHJpbmcpKCRwb3N0WyRrZXkgLiAnX3RpdGxlJ10gPz8gJGZhbGxiYWNrKSk7CiAgICBpZiAoJHRpdGxlID09PSAnJykgJHRpdGxlID0gJGZhbGxiYWNrOwoKICAgICRsYWJlbHMgPSBpc19hcnJheSgkcG9zdFska2V5IC4gJ19sYWJlbCddID8/IG51bGwpID8gJHBvc3RbJGtleSAuICdfbGFiZWwnXSA6IFtdOwogICAgJHVybHMgPSBpc19hcnJheSgkcG9zdFska2V5IC4gJ191cmwnXSA/PyBudWxsKSA/ICRwb3N0WyRrZXkgLiAnX3VybCddIDogW107CiAgICAkZW5hYmxlZCA9IGlzX2FycmF5KCRwb3N0WyRrZXkgLiAnX2VuYWJsZWQnXSA/PyBudWxsKSA/ICRwb3N0WyRrZXkgLiAnX2VuYWJsZWQnXSA6IFtdOwogICAgJG5ld3RhYiA9IGlzX2FycmF5KCRwb3N0WyRrZXkgLiAnX25ld190YWInXSA/PyBudWxsKSA/ICRwb3N0WyRrZXkgLiAnX25ld190YWInXSA6IFtdOwogICAgJHJlbW92ZSA9IGlzX2FycmF5KCRwb3N0WyRrZXkgLiAnX3JlbW92ZSddID8/IG51bGwpID8gJHBvc3RbJGtleSAuICdfcmVtb3ZlJ10gOiBbXTsKCiAgICAkaXRlbXMgPSBbXTsKICAgIGZvcmVhY2ggKCRsYWJlbHMgYXMgJGkgPT4gJGxhYmVsUmF3KSB7CiAgICAgICAgaWYgKCFlbXB0eSgkcmVtb3ZlWyRpXSkpIGNvbnRpbnVlOwoKICAgICAgICAkbGFiZWwgPSB0cmltKChzdHJpbmcpJGxhYmVsUmF3KTsKICAgICAgICAkdXJsID0gYXRwY19jbGVhbl91cmwoKHN0cmluZykoJHVybHNbJGldID8/ICcnKSk7CiAgICAgICAgaWYgKCRsYWJlbCA9PT0gJycgfHwgJHVybCA9PT0gJycpIGNvbnRpbnVlOwoKICAgICAgICAkaXRlbXNbXSA9IFsKICAgICAgICAgICAgJ2xhYmVsJyA9PiAkbGFiZWwsCiAgICAgICAgICAgICd1cmwnID0+ICR1cmwsCiAgICAgICAgICAgICdlbmFibGVkJyA9PiAhZW1wdHkoJGVuYWJsZWRbJGldKSwKICAgICAgICAgICAgJ25ld190YWInID0+ICFlbXB0eSgkbmV3dGFiWyRpXSksCiAgICAgICAgXTsKICAgIH0KCiAgICByZXR1cm4gWyd0aXRsZScgPT4gJHRpdGxlLCAnaXRlbXMnID0+ICRpdGVtc107Cn0KCmlmICghaXNzZXQoJF9TRVNTSU9OWydhdHBjX2NzcmYnXSkpIHsKICAgICRfU0VTU0lPTlsnYXRwY19jc3JmJ10gPSBiaW4yaGV4KHJhbmRvbV9ieXRlcygyNCkpOwp9CgokZGF0YSA9IGF0cGNfbG9hZCgkY29udGVudFBhdGgpOwokbWVzc2FnZSA9ICcnOwoKaWYgKCRfU0VSVkVSWydSRVFVRVNUX01FVEhPRCddID09PSAnUE9TVCcpIHsKICAgIGlmICghaGFzaF9lcXVhbHMoKHN0cmluZykkX1NFU1NJT05bJ2F0cGNfY3NyZiddLCAoc3RyaW5nKSgkX1BPU1RbJ2NzcmYnXSA/PyAnJykpKSB7CiAgICAgICAgJG1lc3NhZ2UgPSAnU2F2ZSBibG9ja2VkOiBzZWN1cml0eSB0b2tlbiBtaXNtYXRjaC4nOwogICAgfSBlbHNlIHsKICAgICAgICAkbmV3ID0gWwogICAgICAgICAgICAnc2NoZW1hX3ZlcnNpb24nID0+IDIsCiAgICAgICAgICAgICd1cGRhdGVkX2F0JyA9PiBkYXRlKCdZLW0tZCBIOmk6cycpLAogICAgICAgICAgICAnYWRtaW5fbGVhZ3VlX3BhbmVsJyA9PiBhdHBjX2J1aWxkX3BhbmVsKCRfUE9TVCwgJ2FkbWluX2xlYWd1ZScsICdMZWFndWUgJiBUZWFtJyksCiAgICAgICAgICAgICdhZG1pbl9ob3N0aW5nX3BhbmVsJyA9PiBhdHBjX2J1aWxkX3BhbmVsKCRfUE9TVCwgJ2FkbWluX2hvc3RpbmcnLCAnSG9zdGluZyAmIEluZnJhc3RydWN0dXJlJyksCiAgICAgICAgICAgICdsZWFndWVfcGFuZWwnID0+IGF0cGNfYnVpbGRfcGFuZWwoJF9QT1NULCAnbGVhZ3VlJywgJ0xlYWd1ZSBJbmZvcm1hdGlvbicpLAogICAgICAgICAgICAndGVhbV9wYW5lbCcgPT4gYXRwY19idWlsZF9wYW5lbCgkX1BPU1QsICd0ZWFtJywgJ1RlYW0gTWVudScpLAogICAgICAgIF07CgogICAgICAgICRiYWNrdXBEaXIgPSBkaXJuYW1lKF9fRElSX18pIC4gJy9fbWlncmF0aW9uX2JhY2t1cHMvdGVhbV9wYWdlX2NvbnRlbnRfJyAuIGRhdGUoJ1ltZF9IaXMnKTsKICAgICAgICAkYmFja3VwT2sgPSB0cnVlOwogICAgICAgIGlmIChpc19maWxlKCRjb250ZW50UGF0aCkpIHsKICAgICAgICAgICAgJGJhY2t1cE9rID0gKGlzX2RpcigkYmFja3VwRGlyKSB8fCBta2RpcigkYmFja3VwRGlyLCAwNzU1LCB0cnVlKSkKICAgICAgICAgICAgICAgICYmIGNvcHkoJGNvbnRlbnRQYXRoLCAkYmFja3VwRGlyIC4gJy9tcmxfdGVhbV9wYWdlX2NvbnRlbnQuanNvbicpOwogICAgICAgIH0KCiAgICAgICAgJGpzb24gPSBqc29uX2VuY29kZSgkbmV3LCBKU09OX1BSRVRUWV9QUklOVCB8IEpTT05fVU5FU0NBUEVEX1NMQVNIRVMpOwogICAgICAgICRvayA9ICRiYWNrdXBPayAmJiBpc19zdHJpbmcoJGpzb24pCiAgICAgICAgICAgICYmIGZpbGVfcHV0X2NvbnRlbnRzKCRjb250ZW50UGF0aCwgJGpzb24gLiBQSFBfRU9MLCBMT0NLX0VYKSAhPT0gZmFsc2U7CgogICAgICAgICRtZXNzYWdlID0gJG9rCiAgICAgICAgICAgID8gJ1RlYW0gcGFnZSBjb250ZW50IHNhdmVkLiBFeGlzdGluZyBKU09OIHdhcyBiYWNrZWQgdXAgZmlyc3QuJwogICAgICAgICAgICA6ICdTYXZlIGZhaWxlZC4nOwogICAgICAgICRkYXRhID0gYXRwY19sb2FkKCRjb250ZW50UGF0aCk7CiAgICB9Cn0KCmZ1bmN0aW9uIGF0cGNfcm93cyhzdHJpbmcgJGtleSwgYXJyYXkgJGl0ZW1zKTogdm9pZAp7CiAgICBmb3JlYWNoICgkaXRlbXMgYXMgJGkgPT4gJGl0KSB7CiAgICAgICAgZWNobyAnPHRyPic7CiAgICAgICAgZWNobyAnPHRkIGNsYXNzPSJvcmRlciI+PGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJtaW5pIiBvbmNsaWNrPSJtb3ZlUm93KHRoaXMsLTEpIj7ihpE8L2J1dHRvbj48YnV0dG9uIHR5cGU9ImJ1dHRvbiIgY2xhc3M9Im1pbmkiIG9uY2xpY2s9Im1vdmVSb3codGhpcywxKSI+4oaTPC9idXR0b24+PC90ZD4nOwogICAgICAgIGVjaG8gJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJsYWJlbCIgbmFtZT0iJyAuIGF0cGNfaCgka2V5KSAuICdfbGFiZWxbJyAuICRpIC4gJ10iIHZhbHVlPSInIC4gYXRwY19oKCRpdFsnbGFiZWwnXSA/PyAnJykgLiAnIj48L3RkPic7CiAgICAgICAgZWNobyAnPHRkPjxpbnB1dCBkYXRhLXJvbGU9InVybCIgbmFtZT0iJyAuIGF0cGNfaCgka2V5KSAuICdfdXJsWycgLiAkaSAuICddIiB2YWx1ZT0iJyAuIGF0cGNfaCgkaXRbJ3VybCddID8/ICcnKSAuICciPjwvdGQ+JzsKICAgICAgICBlY2hvICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0iZW5hYmxlZCIgbmFtZT0iJyAuIGF0cGNfaCgka2V5KSAuICdfZW5hYmxlZFsnIC4gJGkgLiAnXSIgdmFsdWU9IjEiIHR5cGU9ImNoZWNrYm94IiAnIC4gKCFlbXB0eSgkaXRbJ2VuYWJsZWQnXSkgPyAnY2hlY2tlZCcgOiAnJykgLiAnPjwvdGQ+JzsKICAgICAgICBlY2hvICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0ibmV3dGFiIiBuYW1lPSInIC4gYXRwY19oKCRrZXkpIC4gJ19uZXdfdGFiWycgLiAkaSAuICddIiB2YWx1ZT0iMSIgdHlwZT0iY2hlY2tib3giICcgLiAoIWVtcHR5KCRpdFsnbmV3X3RhYiddKSA/ICdjaGVja2VkJyA6ICcnKSAuICc+PC90ZD4nOwogICAgICAgIGVjaG8gJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJyZW1vdmUiIG5hbWU9IicgLiBhdHBjX2goJGtleSkgLiAnX3JlbW92ZVsnIC4gJGkgLiAnXSIgdmFsdWU9IjEiIHR5cGU9ImNoZWNrYm94Ij48L3RkPic7CiAgICAgICAgZWNobyAnPC90cj4nOwogICAgfQp9CgokcGFuZWxzID0gWwogICAgJ2FkbWluX2xlYWd1ZScgPT4gWydkYXRhJz0+J2FkbWluX2xlYWd1ZV9wYW5lbCcsJ2hlYWRpbmcnPT4nQWRtaW4gwrcgTGVhZ3VlICYgVGVhbSddLAogICAgJ2FkbWluX2hvc3RpbmcnID0+IFsnZGF0YSc9PidhZG1pbl9ob3N0aW5nX3BhbmVsJywnaGVhZGluZyc9PidBZG1pbiDCtyBIb3N0aW5nICYgSW5mcmFzdHJ1Y3R1cmUnXSwKICAgICdsZWFndWUnID0+IFsnZGF0YSc9PidsZWFndWVfcGFuZWwnLCdoZWFkaW5nJz0+J0xlYWd1ZSBJbmZvcm1hdGlvbiddLAogICAgJ3RlYW0nID0+IFsnZGF0YSc9Pid0ZWFtX3BhbmVsJywnaGVhZGluZyc9PidUZWFtIE1lbnUnXSwKXTsKPz48IWRvY3R5cGUgaHRtbD4KPGh0bWw+PGhlYWQ+PG1ldGEgY2hhcnNldD0idXRmLTgiPjxtZXRhIG5hbWU9InZpZXdwb3J0IiBjb250ZW50PSJ3aWR0aD1kZXZpY2Utd2lkdGgsaW5pdGlhbC1zY2FsZT0xIj4KPHRpdGxlPk1hbmFnZSBUZWFtIFBhZ2UgQ29udGVudDwvdGl0bGU+CjxzdHlsZT4KKntib3gtc2l6aW5nOmJvcmRlci1ib3h9Ym9keXttYXJnaW46MDtiYWNrZ3JvdW5kOiMxNTE1MTU7Y29sb3I6I2VlZTtmb250LWZhbWlseTpUYWhvbWEsVmVyZGFuYSxTZWdvZSBVSSxzYW5zLXNlcmlmfQoud3JhcHt3aWR0aDo5NCU7bWF4LXdpZHRoOjE0MDBweDttYXJnaW46MjBweCBhdXRvfS5jYXJke2JhY2tncm91bmQ6IzIwMjAyMDtib3JkZXI6MXB4IHNvbGlkICM1NTU7Ym9yZGVyLXJhZGl1czoxNHB4O3BhZGRpbmc6MThweDttYXJnaW4tYm90dG9tOjE2cHh9CmgxLGgye2NvbG9yOiNlZmM5ODJ9YXtjb2xvcjojNzZjZmZmfS5ub3Rle3BhZGRpbmc6MTFweDtib3JkZXI6MXB4IHNvbGlkICM1NTU7Ym9yZGVyLXJhZGl1czo5cHg7YmFja2dyb3VuZDojMTcxNzE3fQp0YWJsZXt3aWR0aDoxMDAlO2JvcmRlci1jb2xsYXBzZTpjb2xsYXBzZTttYXJnaW4tdG9wOjEycHh9dGgsdGR7Ym9yZGVyLWJvdHRvbToxcHggc29saWQgIzQ0NDtwYWRkaW5nOjdweDt0ZXh0LWFsaWduOmxlZnR9CnRkIGlucHV0Om5vdChbdHlwZT1jaGVja2JveF0pe3dpZHRoOjEwMCV9aW5wdXR7cGFkZGluZzo3cHg7YmFja2dyb3VuZDojMTExO2NvbG9yOiNlZWU7Ym9yZGVyOjFweCBzb2xpZCAjNjY2O2JvcmRlci1yYWRpdXM6NXB4fQoucGFuZWwtdGl0bGV7d2lkdGg6MTAwJTttYXgtd2lkdGg6NTYwcHh9YnV0dG9ue3BhZGRpbmc6MTBweCAxN3B4O2JvcmRlcjoxcHggc29saWQgIzVhN2ZiNTtib3JkZXItcmFkaXVzOjhweDtiYWNrZ3JvdW5kOiMxNDY2Yzk7Y29sb3I6I2ZmZjtmb250LXdlaWdodDo4MDA7Y3Vyc29yOnBvaW50ZXJ9Ci5taW5pe3BhZGRpbmc6M3B4IDhweDttYXJnaW46MXB4O2JhY2tncm91bmQ6IzJiMmIyYjtib3JkZXItY29sb3I6Izc3N30ub3JkZXJ7d2lkdGg6ODJweDt3aGl0ZS1zcGFjZTpub3dyYXB9Lm1lc3NhZ2V7bWFyZ2luLXRvcDoxMnB4O3BhZGRpbmc6MTBweDtib3JkZXI6MXB4IHNvbGlkICM3Nzc7Ym9yZGVyLXJhZGl1czo4cHg7Y29sb3I6I2VmYzk4Mn0uc2F2ZXtwb3NpdGlvbjpzdGlja3k7Ym90dG9tOjhweH0KPC9zdHlsZT48L2hlYWQ+PGJvZHk+PGRpdiBjbGFzcz0id3JhcCI+CjxkaXYgY2xhc3M9ImNhcmQiPjxoMT5NYW5hZ2UgVGVhbSBQYWdlIENvbnRlbnQ8L2gxPjxwPjxhIGhyZWY9Ii90ZWFtX3JlZGVzaWduLnBocCI+4oaQIFRlYW0gUmVkZXNpZ248L2E+PC9wPgo8ZGl2IGNsYXNzPSJub3RlIj48c3Ryb25nPk1hbmFnZSBUZWFtIFBhZ2UgQ29udGVudDwvc3Ryb25nPiBpcyBhIGZpeGVkIEFkbWluIGNvbnRyb2wgYW5kIGNhbm5vdCBiZSBlZGl0ZWQgaGVyZS48L2Rpdj4KPD9waHAgaWYoJG1lc3NhZ2UhPT0nJyk6Pz48ZGl2IGNsYXNzPSJtZXNzYWdlIj48P3BocCBlY2hvIGF0cGNfaCgkbWVzc2FnZSk7Pz48L2Rpdj48P3BocCBlbmRpZjs/PjwvZGl2Pgo8Zm9ybSBtZXRob2Q9InBvc3QiPjxpbnB1dCB0eXBlPSJoaWRkZW4iIG5hbWU9ImNzcmYiIHZhbHVlPSI8P3BocCBlY2hvIGF0cGNfaCgoc3RyaW5nKSRfU0VTU0lPTlsnYXRwY19jc3JmJ10pOz8+Ij4KPD9waHAgZm9yZWFjaCgkcGFuZWxzIGFzICRrZXk9PiRtZXRhKTokZGs9JG1ldGFbJ2RhdGEnXTs/PjxkaXYgY2xhc3M9ImNhcmQiPgo8aDI+PD9waHAgZWNobyBhdHBjX2goJG1ldGFbJ2hlYWRpbmcnXSk7Pz48L2gyPgo8bGFiZWw+UGFuZWwgdGl0bGU8YnI+PGlucHV0IGNsYXNzPSJwYW5lbC10aXRsZSIgbmFtZT0iPD9waHAgZWNobyBhdHBjX2goJGtleSk7Pz5fdGl0bGUiIHZhbHVlPSI8P3BocCBlY2hvIGF0cGNfaCgkZGF0YVskZGtdWyd0aXRsZSddPz8nJyk7Pz4iPjwvbGFiZWw+Cjx0YWJsZT48dGhlYWQ+PHRyPjx0aD5PcmRlcjwvdGg+PHRoPkxpbmsgdGV4dDwvdGg+PHRoPlVSTDwvdGg+PHRoPkVuYWJsZWQ8L3RoPjx0aD5OZXcgdGFiPC90aD48dGg+UmVtb3ZlPC90aD48L3RyPjwvdGhlYWQ+Cjx0Ym9keSBpZD0iPD9waHAgZWNobyBhdHBjX2goJGtleSk7Pz4tcm93cyIgZGF0YS1rZXk9Ijw/cGhwIGVjaG8gYXRwY19oKCRrZXkpOz8+Ij4KPD9waHAgYXRwY19yb3dzKCRrZXksaXNfYXJyYXkoJGRhdGFbJGRrXVsnaXRlbXMnXT8/bnVsbCk/JGRhdGFbJGRrXVsnaXRlbXMnXTpbXSk7Pz4KPC90Ym9keT48L3RhYmxlPgo8cD5Vc2Ug4oaRIC8g4oaTIHRvIHJlb3JkZXIuPC9wPjxidXR0b24gdHlwZT0iYnV0dG9uIiBvbmNsaWNrPSJhZGRSb3coJzw/cGhwIGVjaG8gYXRwY19oKCRrZXkpOz8+JykiPkFkZCBMaW5rPC9idXR0b24+CjwvZGl2Pjw/cGhwIGVuZGZvcmVhY2g7Pz4KPGRpdiBjbGFzcz0iY2FyZCBzYXZlIj48YnV0dG9uIHR5cGU9InN1Ym1pdCI+U2F2ZSBUZWFtIFBhZ2UgQ29udGVudDwvYnV0dG9uPjwvZGl2PjwvZm9ybT48L2Rpdj4KPHNjcmlwdD4KZnVuY3Rpb24gcmVudW1iZXIodGIpewogY29uc3Qgaz10Yi5kYXRhc2V0LmtleTsKIFsuLi50Yi5yb3dzXS5mb3JFYWNoKChyLGkpPT57CiAgIGNvbnN0IG1hcD17bGFiZWw6J19sYWJlbCcsdXJsOidfdXJsJyxlbmFibGVkOidfZW5hYmxlZCcsbmV3dGFiOidfbmV3X3RhYicscmVtb3ZlOidfcmVtb3ZlJ307CiAgIHIucXVlcnlTZWxlY3RvckFsbCgnaW5wdXRbZGF0YS1yb2xlXScpLmZvckVhY2goeD0+e3gubmFtZT1rK21hcFt4LmRhdGFzZXQucm9sZV0rJ1snK2krJ10nO30pOwogfSk7Cn0KZnVuY3Rpb24gbW92ZVJvdyhiLGQpewogY29uc3Qgcj1iLmNsb3Nlc3QoJ3RyJyksdGI9ci5wYXJlbnRFbGVtZW50OwogaWYoZDwwJiZyLnByZXZpb3VzRWxlbWVudFNpYmxpbmcpdGIuaW5zZXJ0QmVmb3JlKHIsci5wcmV2aW91c0VsZW1lbnRTaWJsaW5nKTsKIGVsc2UgaWYoZD4wJiZyLm5leHRFbGVtZW50U2libGluZyl0Yi5pbnNlcnRCZWZvcmUoci5uZXh0RWxlbWVudFNpYmxpbmcscik7CiByZW51bWJlcih0Yik7Cn0KZnVuY3Rpb24gYWRkUm93KGspewogY29uc3QgdGI9ZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoaysnLXJvd3MnKSxyPXRiLmluc2VydFJvdygpOwogci5pbm5lckhUTUw9Jzx0ZCBjbGFzcz0ib3JkZXIiPjxidXR0b24gdHlwZT0iYnV0dG9uIiBjbGFzcz0ibWluaSIgb25jbGljaz0ibW92ZVJvdyh0aGlzLC0xKSI+4oaRPC9idXR0b24+PGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJtaW5pIiBvbmNsaWNrPSJtb3ZlUm93KHRoaXMsMSkiPuKGkzwvYnV0dG9uPjwvdGQ+JysKICc8dGQ+PGlucHV0IGRhdGEtcm9sZT0ibGFiZWwiPjwvdGQ+PHRkPjxpbnB1dCBkYXRhLXJvbGU9InVybCI+PC90ZD4nKwogJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJlbmFibGVkIiB2YWx1ZT0iMSIgdHlwZT0iY2hlY2tib3giIGNoZWNrZWQ+PC90ZD4nKwogJzx0ZD48aW5wdXQgZGF0YS1yb2xlPSJuZXd0YWIiIHZhbHVlPSIxIiB0eXBlPSJjaGVja2JveCIgY2hlY2tlZD48L3RkPicrCiAnPHRkPjxpbnB1dCBkYXRhLXJvbGU9InJlbW92ZSIgdmFsdWU9IjEiIHR5cGU9ImNoZWNrYm94Ij48L3RkPic7CiByZW51bWJlcih0Yik7Cn0KZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgndGJvZHlbZGF0YS1rZXldJykuZm9yRWFjaChyZW51bWJlcik7Cjwvc2NyaXB0PjwvYm9keT48L2h0bWw+Cg==',true);
$prodTeamHash=is_file($prodTeam)?hash_file('sha256',$prodTeam):'';
$prodProfileHash=is_file($prodProfile)?hash_file('sha256',$prodProfile):'';

$apply=isset($_POST['apply'])&&$_POST['apply']==='1';
$msgs=[];$success=false;

if($apply&&$teamOk&&$mgrOk&&$jsonOk&&is_string($managerV003)){
 $bd=$base.'/_migration_backups/team_redesign_v007_'.date('Ymd_His');
 $ok=is_dir($bd)||mkdir($bd,0755,true);
 foreach([$team,$manager,$json] as $f){
   if($ok&&!copy($f,$bd.'/'.basename($f))){$ok=false;$msgs[]='Could not back up '.basename($f).'.';}
 }
 if($ok)$msgs[]='Backed up team_redesign.php, Content Manager and menu JSON.';

 if($ok){
   $repaired=[];
   foreach(['admin_league_panel','admin_hosting_panel'] as $panelKey){
     $items=$data[$panelKey]['items']??[];
     if(is_array($items)&&count($items)>0){
       $enabledCount=0;
       foreach($items as $it)if(!empty($it['enabled']))$enabledCount++;
       if($enabledCount===0){
         foreach($items as &$it)$it['enabled']=true;
         unset($it);
         $data[$panelKey]['items']=$items;
         $repaired[]=$panelKey;
       }
     }
   }
   $data['updated_at']=date('Y-m-d H:i:s');
   $j=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
   if(!is_string($j)||!aw($json,$j.PHP_EOL)){$ok=false;$msgs[]='Could not write repaired JSON.';}
   else $msgs[]=empty($repaired)?'Admin enabled flags were already valid.':'Re-enabled Admin links in: '.implode(', ',$repaired).'.';
 }

 if($ok&&!aw($manager,$managerV003)){$ok=false;$msgs[]='Could not install Content Manager v003.';}
 elseif($ok)$msgs[]='Installed mrl_team/admin_team_page_content.php v003.';

 if($ok&&!aw($team,$teamV007)){$ok=false;$msgs[]='Could not install team_redesign.php v007.';}
 elseif($ok)$msgs[]='Installed team_redesign.php v007.';

 $tu=$prodTeamHash!==''&&$prodTeamHash===hash_file('sha256',$prodTeam);
 $pu=$prodProfileHash!==''&&$prodProfileHash===hash_file('sha256',$prodProfile);
 $msgs[]=$tu?'Verified production team.php unchanged.':'ERROR: production team.php changed.';
 $msgs[]=$pu?'Verified production profile.php unchanged.':'ERROR: production profile.php changed.';
 $success=$ok&&$tu&&$pu;
}
?><!doctype html><html><head><meta charset="utf-8"><title>MRL Team Redesign Installer v007</title>
<style>*{box-sizing:border-box}html{background:#111}body{margin:0;color:#eee;font-family:Tahoma,Verdana,Segoe UI,sans-serif}.wrap{width:94%;max-width:1150px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}h1,h2{color:#efc982}table{width:100%;border-collapse:collapse}td{padding:9px;border-bottom:1px solid #444}.ok{color:#61e493}.bad{color:#ff7777}button{padding:11px 20px;background:#1466c9;color:#fff;border:1px solid #5a7fb5;border-radius:9px;font-weight:800}a{color:#76cfff}</style></head>
<body><div class="wrap"><div class="card"><h1>MRL Team Redesign Installer v007</h1><p>Targeted Admin-menu fix. No theme or chart presentation changes.</p></div>
<div class="card"><h2>Preflight</h2><table>
<?php srow('team_redesign.php v006',$teamOk,$team); ?>
<?php srow('Content Manager v002',$mgrOk,$manager); ?>
<?php srow('Menu JSON readable',$jsonOk,$json); ?>
<?php srow('Production team.php present',is_file($prodTeam),$prodTeam); ?>
<?php srow('Production profile.php present',is_file($prodProfile),$prodProfile); ?>
</table></div>
<?php if($teamOk&&$mgrOk&&$jsonOk):?><div class="card"><h2>What v007 fixes</h2><ul>
<li>Repairs the Admin panel links if the prior manager save accidentally stored every Admin link as disabled.</li>
<li>Updates the Content Manager so every row field has an explicit index.</li>
<li>Up/Down ordering now keeps Enabled / New tab / Remove controls attached to the correct row.</li>
<li>No changes to Light theme, Cars, Starry Night, Dark, charts, or profile presentation.</li>
</ul>
<?php if(!$apply):?><form method="post"><input type="hidden" name="apply" value="1"><button>Install v007 Fix</button></form><?php endif;?></div><?php endif;?>
<?php if($apply):?><div class="card"><h2>Apply Result</h2><p class="<?php echo $success?'ok':'bad';?>"><strong><?php echo $success?'SUCCESS':'FAILED';?></strong></p><ul><?php foreach($msgs as $m):?><li><?php echo h($m);?></li><?php endforeach;?></ul><?php if($success):?><p><a href="/team_redesign.php" target="_blank">Open Team Redesign v007</a></p><p><a href="/mrl_team/admin_team_page_content.php" target="_blank">Open Content Manager v003</a></p><?php endif;?></div><?php endif;?>
</div></body></html>