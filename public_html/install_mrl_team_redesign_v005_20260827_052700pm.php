<?php
declare(strict_types=1);

/**
 * install_mrl_team_redesign.php
 *
 * VERSION: v005
 * LAST MODIFIED: 8/27/2026 5:27:00 pm
 *
 * PURPOSE:
 * Pre-Live feature pass for team_redesign.php.
 *
 * EXPECTED INPUT:
 * - team_redesign.php VERSION: v004
 *
 * OUTPUTS:
 * - team_redesign.php VERSION: v005
 * - mrl_theme_helper.php VERSION: v001
 * - profile_redesign.php VERSION: v001
 * - admin_team_page_content.php VERSION: v001
 * - database table mrl_user_preferences
 *
 * SAFETY:
 * - production team.php untouched
 * - production profile.php untouched
 * - existing redesign/support files backed up
 * - mrl_team_page_content.json preserved
 */

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$baseDir = __DIR__;
$teamPath = $baseDir . '/team_redesign.php';
$prodTeam = $baseDir . '/team.php';
$prodProfile = $baseDir . '/profile.php';
$contentPath = $baseDir . '/mrl_team_page_content.json';
$helperPath = $baseDir . '/mrl_theme_helper.php';
$profilePath = $baseDir . '/profile_redesign.php';
$adminPath = $baseDir . '/admin_team_page_content.php';

require_once $baseDir . '/config.php';

function ih(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

function row_status(string $label, bool $ok, string $detail=''): void {
    echo '<tr><td>'.ih($label).'</td><td class="'.($ok?'ok':'bad').'">'.($ok?'PASS':'FAIL').'</td><td>'.ih($detail).'</td></tr>';
}

function atomic_write(string $path, string $data): bool {
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $data, LOCK_EX) === false) return false;
    if (!@rename($tmp, $path)) { @unlink($tmp); return false; }
    return true;
}

function backup_one(string $path, string $dir, array &$messages): bool {
    if (!is_file($path)) return true;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        $messages[]='Could not create backup directory.';
        return false;
    }
    if (!copy($path, $dir.'/'.basename($path))) {
        $messages[]='Could not back up '.basename($path).'.';
        return false;
    }
    $messages[]='Backed up '.basename($path).' to '.$dir;
    return true;
}

function build_team_v005(string $src): array {
    if (strpos($src, 'VERSION: v004') === false) return [false,'','Expected team_redesign.php v004.'];

    $w = $src;
    $w = preg_replace('/VERSION:\s*v004/', 'VERSION: v005', $w, 1, $c1);
    $w = preg_replace('/LAST MODIFIED:\s*8\/27\/2026 4:46:58 pm/', 'LAST MODIFIED: 8/27/2026 5:27:00 pm', $w, 1, $c2);
    if ($c1 !== 1 || $c2 !== 1) return [false,'','Could not update v004 header safely.'];

    $change = " *\n"
        ." * v005 (8/27/2026 5:27:00 pm)\n"
        ." * - UI: Dedicated non-collapsible pick/submission status panels.\n"
        ." * - ADMIN: Hard-wired Manage Team Page Content action.\n"
        ." * - THEME: Per-user Cars / Starry Night / Dark / Light themes.\n"
        ." * - PROFILE: Profile redesign/theme selector integration.\n"
        ." * - PRESERVE: Chart presentation and production team.php/profile.php.\n";

    $w = preg_replace('/(\* CHANGELOG:\R)/', '$1'.$change, $w, 1, $cc);
    if ($cc !== 1) return [false,'','Could not add v005 changelog.'];

    $needle = "require 'config_mrl.php';\n";
    if (substr_count($w,$needle)!==1) return [false,'','config_mrl require not found.'];
    $w = str_replace($needle, $needle."require_once __DIR__ . '/mrl_theme_helper.php';\n", $w);

    $needle = "\$uid = (int)(\$_SESSION['userSession'] ?? 0);\n\$isAdmin = isAdmin(\$uid);\n";
    if (substr_count($w,$needle)!==1) return [false,'','uid/isAdmin block not found.'];
    $w = str_replace($needle, $needle."\$mrlTheme = mrl_theme_get(\$dbo, \$uid);\n", $w);

    if (substr_count($w,'<html class="no-js">')!==1) return [false,'','html class marker not found.'];
    $w = str_replace('<html class="no-js">','<html class="no-js mrl-theme-<?php echo teampage_h($mrlTheme); ?>">',$w);

    $w = str_replace('<?php echo teampage_h((string)$mrl); ?>profile.php',
                     '<?php echo teampage_h((string)$mrl); ?>profile_redesign.php',
                     $w);

    $adminNeedle='<li><a href="/race_results/weekly_standings.php" target="_blank">Weekly Standings / scoring - Beta</a></li>';
    if (substr_count($w,$adminNeedle)!==1) return [false,'','Admin list marker not found.'];
    $w=str_replace($adminNeedle,
        '<li><a href="/admin_team_page_content.php" target="_blank">Manage Team Page Content</a></li>'."\n                        ".$adminNeedle,
        $w);

    $submitNeedle="include \$currentForm;\n                    include 'submitted_teams_count.php';";
    if (substr_count($w,$submitNeedle)!==1) return [false,'','Submission include pair not found.'];
    $submitReplace="include \$currentForm;\n"
        ."                    echo \"<div class='mrl-rd-notice-panel mrl-rd-submission-panel'>\";\n"
        ."                    include 'submitted_teams_count.php';\n"
        ."                    echo \"</div>\";";
    $w=str_replace($submitNeedle,$submitReplace,$w);

    $closedStart='                        echo teampage_h((string)$raceYear) . " " . teampage_h($closedSegmentLabel) . " picks are closed.";';
    if (substr_count($w,$closedStart)!==1) return [false,'','Closed status start not found.'];
    $w=str_replace($closedStart,
        '                        echo "<div class=\'mrl-rd-notice-panel mrl-rd-pick-status-panel\'>";'."\n".$closedStart,
        $w);

    $closedEnd='                        echo "<br><br>";'."\n".'                        include \'current_segment_chart.php\';';
    if (substr_count($w,$closedEnd)!==1) return [false,'','Closed status end not found.'];
    $w=str_replace($closedEnd,
        '                        echo "</div>";'."\n".'                        include \'current_segment_chart.php\';',
        $w);

    $css = <<<'CSS'

        /* team_redesign.php v005 - per-user themes and status panels */
        html.mrl-theme-cars{
            --mrl-rd-panel:rgba(28,28,28,.48);
            --mrl-rd-panel-header:rgba(34,34,34,.42);
            --mrl-rd-border:rgba(195,195,195,.34);
            --mrl-rd-gold:#f1c97f;--mrl-rd-text:#f2f2f2;--mrl-rd-blue:#43b7f0;
            background:linear-gradient(rgba(10,20,15,.70),rgba(10,20,15,.70)),
                       url("/images/cars.jpg") center/cover no-repeat fixed!important;
        }
        html.mrl-theme-starry-night{
            --mrl-rd-panel:rgba(19,22,31,.56);
            --mrl-rd-panel-header:rgba(24,27,39,.50);
            --mrl-rd-border:rgba(190,198,221,.34);
            --mrl-rd-gold:#e8cf9a;--mrl-rd-text:#f2f3f7;--mrl-rd-blue:#67bdf2;
            background:linear-gradient(rgba(5,8,18,.60),rgba(5,8,18,.60)),
                       url("/images/starry_night.jpg") center/cover no-repeat fixed!important;
        }
        html.mrl-theme-dark{
            --mrl-rd-panel:rgba(28,28,28,.88);
            --mrl-rd-panel-header:rgba(34,34,34,.92);
            --mrl-rd-border:rgba(195,195,195,.34);
            --mrl-rd-gold:#f1c97f;--mrl-rd-text:#f2f2f2;--mrl-rd-blue:#43b7f0;
            background:#151515!important;
        }
        html.mrl-theme-light{
            --mrl-rd-panel:rgba(255,255,255,.90);
            --mrl-rd-panel-header:rgba(244,244,244,.96);
            --mrl-rd-border:rgba(60,60,60,.28);
            --mrl-rd-gold:#8b5b00;--mrl-rd-text:#202020;--mrl-rd-muted:#555;--mrl-rd-blue:#006eaa;
            background:#eceff1!important;
        }
        html.mrl-theme-light .mrl-rd-card,
        html.mrl-theme-light .mrl-rd-admin-wrap,
        html.mrl-theme-light .mrl-rd-notice-panel{color:#202020!important}
        .mrl-rd-notice-panel{
            box-sizing:border-box;width:100%;margin:14px 0 18px;padding:13px 18px;
            border:1px solid var(--mrl-rd-border);border-radius:12px;
            background:var(--mrl-rd-panel);color:var(--mrl-rd-gold);
            font:18px/1.45 "Century Gothic",Tahoma,Verdana,sans-serif;
            backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);
        }
        .mrl-rd-submission-panel{margin-top:18px;margin-bottom:18px}
        .mrl-rd-submission-panel a{color:var(--mrl-rd-blue)!important}
CSS;

    $p=strpos($w,'</style>');
    if($p===false) return [false,'','Main style block not found.'];
    $w=substr_replace($w,$css."\n    </style>",$p,strlen('</style>'));

    foreach(['VERSION: v005','mrl_theme_helper.php','Manage Team Page Content','mrl-theme-starry-night','starry_night.jpg','mrl-rd-notice-panel'] as $guard) {
        if(strpos($w,$guard)===false) return [false,'','Guard failed: '.$guard];
    }

    return [true,$w,'v005 build ready.'];
}

$helperData = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogbXJsX3RoZW1lX2hlbHBlci5waHAKICoKICogVkVSU0lPTjogdjAwMQogKiBMQVNUIE1PRElGSUVEOiA4LzI3LzIwMjYgNToyNzowMCBwbQogKi8KCmZ1bmN0aW9uIG1ybF90aGVtZV9vcHRpb25zKCk6IGFycmF5CnsKICAgIHJldHVybiBbCiAgICAgICAgJ2NhcnMnID0+ICdDYXJzJywKICAgICAgICAnc3RhcnJ5LW5pZ2h0JyA9PiAnU3RhcnJ5IE5pZ2h0JywKICAgICAgICAnZGFyaycgPT4gJ0RhcmsnLAogICAgICAgICdsaWdodCcgPT4gJ0xpZ2h0JywKICAgIF07Cn0KCmZ1bmN0aW9uIG1ybF90aGVtZV9ub3JtYWxpemUoc3RyaW5nICR0aGVtZSk6IHN0cmluZwp7CiAgICAkdGhlbWUgPSBzdHJ0b2xvd2VyKHRyaW0oJHRoZW1lKSk7CiAgICByZXR1cm4gYXJyYXlfa2V5X2V4aXN0cygkdGhlbWUsIG1ybF90aGVtZV9vcHRpb25zKCkpID8gJHRoZW1lIDogJ2RhcmsnOwp9CgpmdW5jdGlvbiBtcmxfdGhlbWVfZ2V0KFBETyAkZGJvLCBpbnQgJHVzZXJJRCk6IHN0cmluZwp7CiAgICBpZiAoJHVzZXJJRCA8PSAwKSB7CiAgICAgICAgcmV0dXJuICdkYXJrJzsKICAgIH0KCiAgICB0cnkgewogICAgICAgICRzdG10ID0gJGRiby0+cHJlcGFyZSgKICAgICAgICAgICAgIlNFTEVDVCB0ZWFtX3RoZW1lCiAgICAgICAgICAgICBGUk9NIG1ybF91c2VyX3ByZWZlcmVuY2VzCiAgICAgICAgICAgICBXSEVSRSB1c2VySUQgPSA6dWlkCiAgICAgICAgICAgICBMSU1JVCAxIgogICAgICAgICk7CiAgICAgICAgJHN0bXQtPmV4ZWN1dGUoWyc6dWlkJyA9PiAkdXNlcklEXSk7CiAgICAgICAgJHJvdyA9ICRzdG10LT5mZXRjaChQRE86OkZFVENIX0FTU09DKTsKCiAgICAgICAgcmV0dXJuIGlzX2FycmF5KCRyb3cpCiAgICAgICAgICAgID8gbXJsX3RoZW1lX25vcm1hbGl6ZSgoc3RyaW5nKSgkcm93Wyd0ZWFtX3RoZW1lJ10gPz8gJ2RhcmsnKSkKICAgICAgICAgICAgOiAnZGFyayc7CiAgICB9IGNhdGNoIChUaHJvd2FibGUgJGUpIHsKICAgICAgICByZXR1cm4gJ2RhcmsnOwogICAgfQp9CgpmdW5jdGlvbiBtcmxfdGhlbWVfc2F2ZShQRE8gJGRibywgaW50ICR1c2VySUQsIHN0cmluZyAkdGhlbWUpOiBib29sCnsKICAgIGlmICgkdXNlcklEIDw9IDApIHsKICAgICAgICByZXR1cm4gZmFsc2U7CiAgICB9CgogICAgJHRoZW1lID0gbXJsX3RoZW1lX25vcm1hbGl6ZSgkdGhlbWUpOwoKICAgIHRyeSB7CiAgICAgICAgJHN0bXQgPSAkZGJvLT5wcmVwYXJlKAogICAgICAgICAgICAiSU5TRVJUIElOVE8gbXJsX3VzZXJfcHJlZmVyZW5jZXMgKHVzZXJJRCwgdGVhbV90aGVtZSwgdXBkYXRlZF9hdCkKICAgICAgICAgICAgIFZBTFVFUyAoOnVpZCwgOnRoZW1lLCBOT1coKSkKICAgICAgICAgICAgIE9OIERVUExJQ0FURSBLRVkgVVBEQVRFCiAgICAgICAgICAgICAgICB0ZWFtX3RoZW1lID0gVkFMVUVTKHRlYW1fdGhlbWUpLAogICAgICAgICAgICAgICAgdXBkYXRlZF9hdCA9IE5PVygpIgogICAgICAgICk7CgogICAgICAgIHJldHVybiAkc3RtdC0+ZXhlY3V0ZShbCiAgICAgICAgICAgICc6dWlkJyA9PiAkdXNlcklELAogICAgICAgICAgICAnOnRoZW1lJyA9PiAkdGhlbWUsCiAgICAgICAgXSk7CiAgICB9IGNhdGNoIChUaHJvd2FibGUgJGUpIHsKICAgICAgICByZXR1cm4gZmFsc2U7CiAgICB9Cn0K', true);
$profileData = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogcHJvZmlsZV9yZWRlc2lnbi5waHAKICoKICogVkVSU0lPTjogdjAwMQogKiBMQVNUIE1PRElGSUVEOiA4LzI3LzIwMjYgNToyNzowMCBwbQogKgogKiBNb2Rlcm4gcHJvZmlsZSB0ZXN0IHBhZ2Ugd2l0aCBwZXItdXNlciBNUkwgdGhlbWUgc2VsZWN0aW9uLgogKiBQcm9kdWN0aW9uIHByb2ZpbGUucGhwIGlzIHVudG91Y2hlZC4KICovCgpzZXNzaW9uX3N0YXJ0KCk7CgpyZXF1aXJlX29uY2UgJ2NsYXNzLnVzZXIucGhwJzsKJHVzZXJfaG9tZSA9IG5ldyBVU0VSKCk7CgppZiAoISR1c2VyX2hvbWUtPmlzX2xvZ2dlZF9pbigpKSB7CiAgICAkdXNlcl9ob21lLT5yZWRpcmVjdCgnbG9naW4ucGhwJyk7CiAgICBleGl0Owp9CgpkYXRlX2RlZmF1bHRfdGltZXpvbmVfc2V0KCdBbWVyaWNhL05ld19Zb3JrJyk7CnJlcXVpcmUgJ2NvbmZpZy5waHAnOwpyZXF1aXJlICdjb25maWdfbXJsLnBocCc7CnJlcXVpcmVfb25jZSBfX0RJUl9fIC4gJy9tcmxfdGhlbWVfaGVscGVyLnBocCc7CgokdWlkID0gKGludCkoJF9TRVNTSU9OWyd1c2VyU2Vzc2lvbiddID8/IDApOwoKJHN0bXQgPSAkdXNlcl9ob21lLT5ydW5RdWVyeSgiU0VMRUNUICogRlJPTSB1c2VycyBXSEVSRSB1c2VySUQ9OnVpZCIpOwokc3RtdC0+ZXhlY3V0ZShbJzp1aWQnID0+ICR1aWRdKTsKJHJvdyA9ICRzdG10LT5mZXRjaChQRE86OkZFVENIX0FTU09DKSA/OiBbXTsKCiRuYW1lUGFydHMgPSBleHBsb2RlKCcgJywgdHJpbSgoc3RyaW5nKSgkcm93Wyd1c2VyTmFtZSddID8/ICcnKSkpOwokZmlyc3ROYW1lID0gJG5hbWVQYXJ0c1swXSA/PyAnJzsKJGVtYWlsMSA9IChzdHJpbmcpKCRyb3dbJ3VzZXJFbWFpbCddID8/ICcnKTsKJGVtYWlsMiA9IChzdHJpbmcpKCRyb3dbJ3VzZXJFbWFpbDInXSA/PyAnJyk7CgppZiAoIWlzc2V0KCRfU0VTU0lPTlsnbXJsX3Byb2ZpbGVfY3NyZiddKSkgewogICAgJF9TRVNTSU9OWydtcmxfcHJvZmlsZV9jc3JmJ10gPSBiaW4yaGV4KHJhbmRvbV9ieXRlcygyNCkpOwp9CgokbWVzc2FnZSA9ICcnOwoKaWYgKCRfU0VSVkVSWydSRVFVRVNUX01FVEhPRCddID09PSAnUE9TVCcpIHsKICAgICRjc3JmID0gKHN0cmluZykoJF9QT1NUWydjc3JmJ10gPz8gJycpOwogICAgaWYgKCFoYXNoX2VxdWFscygoc3RyaW5nKSRfU0VTU0lPTlsnbXJsX3Byb2ZpbGVfY3NyZiddLCAkY3NyZikpIHsKICAgICAgICAkbWVzc2FnZSA9ICdUaGVtZSB3YXMgbm90IHNhdmVkOiBzZWN1cml0eSB0b2tlbiBtaXNtYXRjaC4nOwogICAgfSBlbHNlIHsKICAgICAgICAkcmVxdWVzdGVkVGhlbWUgPSBtcmxfdGhlbWVfbm9ybWFsaXplKChzdHJpbmcpKCRfUE9TVFsndGVhbV90aGVtZSddID8/ICdkYXJrJykpOwogICAgICAgICRtZXNzYWdlID0gbXJsX3RoZW1lX3NhdmUoJGRibywgJHVpZCwgJHJlcXVlc3RlZFRoZW1lKQogICAgICAgICAgICA/ICdUaGVtZSBwcmVmZXJlbmNlIHNhdmVkLicKICAgICAgICAgICAgOiAnVGhlbWUgcHJlZmVyZW5jZSBjb3VsZCBub3QgYmUgc2F2ZWQuJzsKICAgIH0KfQoKJHRoZW1lID0gbXJsX3RoZW1lX2dldCgkZGJvLCAkdWlkKTsKJG9wdGlvbnMgPSBtcmxfdGhlbWVfb3B0aW9ucygpOwoKZnVuY3Rpb24gcHJfaCgkdmFsdWUpOiBzdHJpbmcKewogICAgcmV0dXJuIGh0bWxzcGVjaWFsY2hhcnMoKHN0cmluZykkdmFsdWUsIEVOVF9RVU9URVMsICdVVEYtOCcpOwp9Cj8+PCFET0NUWVBFIGh0bWw+CjxodG1sIGNsYXNzPSJtcmwtdGhlbWUtPD9waHAgZWNobyBwcl9oKCR0aGVtZSk7ID8+Ij4KPGhlYWQ+CjxtZXRhIGNoYXJzZXQ9InV0Zi04Ij4KPG1ldGEgbmFtZT0idmlld3BvcnQiIGNvbnRlbnQ9IndpZHRoPWRldmljZS13aWR0aCwgaW5pdGlhbC1zY2FsZT0xIj4KPHRpdGxlPjw/cGhwIGVjaG8gcHJfaCgkZmlyc3ROYW1lKTsgPz4ncyBQcm9maWxlIFBhZ2U8L3RpdGxlPgo8c3R5bGU+Cip7Ym94LXNpemluZzpib3JkZXItYm94fQo6cm9vdHsKICAgIC0tZ29sZDojZjFjOTdmOy0tdGV4dDojZjJmMmYyOy0tYmx1ZTojNDNiN2YwOwogICAgLS1wYW5lbDpyZ2JhKDI4LDI4LDI4LC41Mik7LS1wYW5lbC1oZWFkOnJnYmEoMzQsMzQsMzQsLjQ2KTsKICAgIC0tYm9yZGVyOnJnYmEoMTk1LDE5NSwxOTUsLjM0KTstLXdpZHRoOjg1JTstLW1heDoxNjAwcHgKfQpodG1se21pbi1oZWlnaHQ6MTAwJTtiYWNrZ3JvdW5kOiMxNTE1MTV9Cmh0bWwubXJsLXRoZW1lLWNhcnN7CiAgICBiYWNrZ3JvdW5kOmxpbmVhci1ncmFkaWVudChyZ2JhKDEwLDIwLDE1LC43MCkscmdiYSgxMCwyMCwxNSwuNzApKSx1cmwoIi9pbWFnZXMvY2Fycy5qcGciKSBjZW50ZXIvY292ZXIgbm8tcmVwZWF0IGZpeGVkIWltcG9ydGFudAp9Cmh0bWwubXJsLXRoZW1lLXN0YXJyeS1uaWdodHsKICAgIGJhY2tncm91bmQ6bGluZWFyLWdyYWRpZW50KHJnYmEoNSw4LDE4LC42MCkscmdiYSg1LDgsMTgsLjYwKSksdXJsKCIvaW1hZ2VzL3N0YXJyeV9uaWdodC5qcGciKSBjZW50ZXIvY292ZXIgbm8tcmVwZWF0IGZpeGVkIWltcG9ydGFudAp9Cmh0bWwubXJsLXRoZW1lLWRhcmt7YmFja2dyb3VuZDojMTUxNTE1IWltcG9ydGFudH0KaHRtbC5tcmwtdGhlbWUtbGlnaHR7CiAgICAtLWdvbGQ6IzhiNWIwMDstLXRleHQ6IzIwMjAyMDstLWJsdWU6IzAwNmVhYTstLXBhbmVsOnJnYmEoMjU1LDI1NSwyNTUsLjkwKTsKICAgIC0tcGFuZWwtaGVhZDpyZ2JhKDI0NCwyNDQsMjQ0LC45Nik7LS1ib3JkZXI6cmdiYSg2MCw2MCw2MCwuMjgpOwogICAgYmFja2dyb3VuZDojZWNlZmYxIWltcG9ydGFudAp9CmJvZHl7bWFyZ2luOjA7bWluLWhlaWdodDoxMDAlO2JhY2tncm91bmQ6dHJhbnNwYXJlbnQ7Y29sb3I6dmFyKC0tdGV4dCk7Zm9udC1mYW1pbHk6VGFob21hLFZlcmRhbmEsU2Vnb2UgVUksc2Fucy1zZXJpZn0KLnNoZWxse3dpZHRoOnZhcigtLXdpZHRoKTttYXgtd2lkdGg6dmFyKC0tbWF4KTttYXJnaW4tbGVmdDphdXRvO21hcmdpbi1yaWdodDphdXRvfQouaGVhZGVye3Bvc2l0aW9uOnN0aWNreTt0b3A6OHB4O3otaW5kZXg6MjA7bWFyZ2luLXRvcDo4cHg7cGFkZGluZzoxMHB4IDE2cHg7Ym9yZGVyOjFweCBzb2xpZCByZ2JhKDY3LDE0Miw5NCwuNzIpO2JvcmRlci1yYWRpdXM6MTRweDtiYWNrZ3JvdW5kOmxpbmVhci1ncmFkaWVudCgxODBkZWcscmdiYSgxOCw1OCw0MCwuODIpLHJnYmEoMjAsMzUsMjksLjc4KSk7ZGlzcGxheTpncmlkO2dyaWQtdGVtcGxhdGUtY29sdW1uczoxZnIgMmZyIDFmcjthbGlnbi1pdGVtczpjZW50ZXI7Ym94LXNoYWRvdzowIDEwcHggMjhweCByZ2JhKDAsMCwwLC4yNSl9Ci5oZWFkZXIgYXtjb2xvcjojZmZmO3RleHQtZGVjb3JhdGlvbjpub25lfS50aXRsZXt0ZXh0LWFsaWduOmNlbnRlcjtjb2xvcjojZmZmNWUyO2ZvbnQtc2l6ZToyMHB4O2ZvbnQtd2VpZ2h0OjgwMH0ucmlnaHR7dGV4dC1hbGlnbjpyaWdodH0KLmNhcmR7bWFyZ2luLXRvcDoxOHB4O2JvcmRlcjoxcHggc29saWQgdmFyKC0tYm9yZGVyKTtib3JkZXItcmFkaXVzOjE0cHg7YmFja2dyb3VuZDp2YXIoLS1wYW5lbCk7b3ZlcmZsb3c6aGlkZGVuO2JhY2tkcm9wLWZpbHRlcjpibHVyKDJweCl9Ci5jYXJkIGgye21hcmdpbjowO3BhZGRpbmc6MTNweCAxOHB4O2JhY2tncm91bmQ6dmFyKC0tcGFuZWwtaGVhZCk7Ym9yZGVyLWJvdHRvbToxcHggc29saWQgcmdiYSgyNTUsMjU1LDI1NSwuMDkpO2NvbG9yOnZhcigtLWdvbGQpO2ZvbnQtc2l6ZToxOHB4fQouYm9keXtwYWRkaW5nOjE4cHh9LmJvZHkgcHtsaW5lLWhlaWdodDoxLjV9LmJvZHkgYXtjb2xvcjp2YXIoLS1ibHVlKX0KLnRoZW1lLWdyaWR7ZGlzcGxheTpncmlkO2dyaWQtdGVtcGxhdGUtY29sdW1uczpyZXBlYXQoNCxtaW5tYXgoMCwxZnIpKTtnYXA6MTBweH0KLnRoZW1lLW9wdGlvbntkaXNwbGF5OmJsb2NrO2JvcmRlcjoxcHggc29saWQgdmFyKC0tYm9yZGVyKTtib3JkZXItcmFkaXVzOjEwcHg7cGFkZGluZzoxMnB4O2JhY2tncm91bmQ6cmdiYSgwLDAsMCwuMTIpO2N1cnNvcjpwb2ludGVyfQoudGhlbWUtb3B0aW9uIGlucHV0e21hcmdpbi1yaWdodDo3cHh9CmJ1dHRvbnttYXJnaW4tdG9wOjE0cHg7Ym9yZGVyOjFweCBzb2xpZCAjNWE3ZmI1O2JvcmRlci1yYWRpdXM6OXB4O2JhY2tncm91bmQ6IzE0NjZjOTtjb2xvcjojZmZmO3BhZGRpbmc6OXB4IDE2cHg7Zm9udC13ZWlnaHQ6ODAwO2N1cnNvcjpwb2ludGVyfQoubWVzc2FnZXttYXJnaW4tdG9wOjEycHg7cGFkZGluZzoxMHB4IDEycHg7Ym9yZGVyOjFweCBzb2xpZCB2YXIoLS1ib3JkZXIpO2JvcmRlci1yYWRpdXM6OXB4O2NvbG9yOnZhcigtLWdvbGQpfQpmb290ZXJ7cGFkZGluZzoyNHB4IDAgMzBweDtjb2xvcjp2YXIoLS1nb2xkKTtmb250LXNpemU6MTNweH0KQG1lZGlhKG1heC13aWR0aDo5MDBweCl7OnJvb3R7LS13aWR0aDo5NCV9LmhlYWRlcntncmlkLXRlbXBsYXRlLWNvbHVtbnM6MWZyIGF1dG99LnRpdGxle2dyaWQtY29sdW1uOjEvLTE7Z3JpZC1yb3c6MTt0ZXh0LWFsaWduOmxlZnR9LnRoZW1lLWdyaWR7Z3JpZC10ZW1wbGF0ZS1jb2x1bW5zOjFmciAxZnJ9fQpAbWVkaWEobWF4LXdpZHRoOjU2MHB4KXsudGhlbWUtZ3JpZHtncmlkLXRlbXBsYXRlLWNvbHVtbnM6MWZyfX0KPC9zdHlsZT4KPC9oZWFkPgo8Ym9keT4KPGhlYWRlciBjbGFzcz0ic2hlbGwgaGVhZGVyIj4KICAgIDxkaXY+PGEgaHJlZj0iL3RlYW1fcmVkZXNpZ24ucGhwIj7ihpAgVGVhbSBQYWdlPC9hPjwvZGl2PgogICAgPGRpdiBjbGFzcz0idGl0bGUiPk1hbmxpdXMgUmFjaW5nIExlYWd1ZSA8ZGl2IHN0eWxlPSJmb250LXNpemU6MTJweDtjb2xvcjojZjFjOTdmIj5NeSBQcm9maWxlIFBhZ2UgwrcgcmVkZXNpZ24gdGVzdDwvZGl2PjwvZGl2PgogICAgPGRpdiBjbGFzcz0icmlnaHQiPjxhIGhyZWY9Ii9sb2dvdXQucGhwIj5Mb2dvdXQ8L2E+PC9kaXY+CjwvaGVhZGVyPgoKPG1haW4gY2xhc3M9InNoZWxsIj4KICAgIDxkaXYgc3R5bGU9Im1hcmdpbjoxNnB4IDJweDtjb2xvcjp2YXIoLS1nb2xkKTtmb250LXNpemU6MThweCI+SGkgPD9waHAgZWNobyBwcl9oKCRmaXJzdE5hbWUpOyA/PiAuLi48L2Rpdj4KCiAgICA8c2VjdGlvbiBjbGFzcz0iY2FyZCI+CiAgICAgICAgPGgyPlByb2ZpbGU8L2gyPgogICAgICAgIDxkaXYgY2xhc3M9ImJvZHkiPgogICAgICAgICAgICA8cD48YSBocmVmPSIvY2hhbmdlLWxvZ2luLWVtYWlsLnBocCI+Q2hhbmdlIExvZ2luIEVtYWlsPC9hPjxicj48P3BocCBlY2hvIHByX2goJGVtYWlsMSk7ID8+PC9wPgogICAgICAgICAgICA8cD48YSBocmVmPSIvY2hhbmdlLXNlY29uZC1lbWFpbC5waHAiPkNoYW5nZSBvciBBZGQgYSBTZWNvbmRhcnkgRW1haWw8L2E+PGJyPjw/cGhwIGVjaG8gcHJfaCgkZW1haWwyKTsgPz48L3A+CiAgICAgICAgPC9kaXY+CiAgICA8L3NlY3Rpb24+CgogICAgPHNlY3Rpb24gY2xhc3M9ImNhcmQiPgogICAgICAgIDxoMj5QYWdlIFRoZW1lPC9oMj4KICAgICAgICA8ZGl2IGNsYXNzPSJib2R5Ij4KICAgICAgICAgICAgPHA+Q2hvb3NlIHRoZSB0aGVtZSB1c2VkIGJ5IHlvdXIgTVJMIHRlYW0vcHJvZmlsZSBwYWdlcy48L3A+CiAgICAgICAgICAgIDxmb3JtIG1ldGhvZD0icG9zdCI+CiAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJjc3JmIiB2YWx1ZT0iPD9waHAgZWNobyBwcl9oKChzdHJpbmcpJF9TRVNTSU9OWydtcmxfcHJvZmlsZV9jc3JmJ10pOyA/PiI+CiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJ0aGVtZS1ncmlkIj4KICAgICAgICAgICAgICAgICAgICA8P3BocCBmb3JlYWNoICgkb3B0aW9ucyBhcyAkdmFsdWUgPT4gJGxhYmVsKTogPz4KICAgICAgICAgICAgICAgICAgICA8bGFiZWwgY2xhc3M9InRoZW1lLW9wdGlvbiI+CiAgICAgICAgICAgICAgICAgICAgICAgIDxpbnB1dCB0eXBlPSJyYWRpbyIgbmFtZT0idGVhbV90aGVtZSIgdmFsdWU9Ijw/cGhwIGVjaG8gcHJfaCgkdmFsdWUpOyA/PiIgPD9waHAgZWNobyAkdGhlbWUgPT09ICR2YWx1ZSA/ICdjaGVja2VkJyA6ICcnOyA/Pj4KICAgICAgICAgICAgICAgICAgICAgICAgPD9waHAgZWNobyBwcl9oKCRsYWJlbCk7ID8+CiAgICAgICAgICAgICAgICAgICAgPC9sYWJlbD4KICAgICAgICAgICAgICAgICAgICA8P3BocCBlbmRmb3JlYWNoOyA/PgogICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICA8YnV0dG9uIHR5cGU9InN1Ym1pdCI+U2F2ZSBUaGVtZTwvYnV0dG9uPgogICAgICAgICAgICA8L2Zvcm0+CiAgICAgICAgICAgIDw/cGhwIGlmICgkbWVzc2FnZSAhPT0gJycpOiA/PjxkaXYgY2xhc3M9Im1lc3NhZ2UiPjw/cGhwIGVjaG8gcHJfaCgkbWVzc2FnZSk7ID8+PC9kaXY+PD9waHAgZW5kaWY7ID8+CiAgICAgICAgPC9kaXY+CiAgICA8L3NlY3Rpb24+CjwvbWFpbj4KCjxmb290ZXIgY2xhc3M9InNoZWxsIj5Db3B5cmlnaHQgwqkgMjAxNy08P3BocCBlY2hvIGRhdGUoJ1knKTsgPz4gTWFubGl1cyBSYWNpbmcgTGVhZ3VlPC9mb290ZXI+CjwvYm9keT4KPC9odG1sPgo=', true);
$adminData = base64_decode('PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogYWRtaW5fdGVhbV9wYWdlX2NvbnRlbnQucGhwCiAqCiAqIFZFUlNJT046IHYwMDEKICogTEFTVCBNT0RJRklFRDogOC8yNy8yMDI2IDU6Mjc6MDAgcG0KICoKICogQWRtaW4tb25seSBlZGl0b3IgZm9yIEpTT04tZHJpdmVuIFRlYW0gUGFnZSBwYW5lbCBjb250ZW50LgogKi8KCnNlc3Npb25fc3RhcnQoKTsKCnJlcXVpcmVfb25jZSAnY2xhc3MudXNlci5waHAnOwokdXNlcl9ob21lID0gbmV3IFVTRVIoKTsKCmlmICghJHVzZXJfaG9tZS0+aXNfbG9nZ2VkX2luKCkpIHsKICAgICR1c2VyX2hvbWUtPnJlZGlyZWN0KCdsb2dpbi5waHAnKTsKICAgIGV4aXQ7Cn0KCmRhdGVfZGVmYXVsdF90aW1lem9uZV9zZXQoJ0FtZXJpY2EvTmV3X1lvcmsnKTsKcmVxdWlyZSAnY29uZmlnLnBocCc7CnJlcXVpcmUgJ2NvbmZpZ19tcmwucGhwJzsKCiR1aWQgPSAoaW50KSgkX1NFU1NJT05bJ3VzZXJTZXNzaW9uJ10gPz8gMCk7CmlmICghaXNBZG1pbigkdWlkKSkgewogICAgaHR0cF9yZXNwb25zZV9jb2RlKDQwMyk7CiAgICBleGl0KCdBZG1pbiBhY2Nlc3MgcmVxdWlyZWQuJyk7Cn0KCiRjb250ZW50UGF0aCA9IF9fRElSX18gLiAnL21ybF90ZWFtX3BhZ2VfY29udGVudC5qc29uJzsKCmZ1bmN0aW9uIGF0cGNfaCgkdik6IHN0cmluZyB7IHJldHVybiBodG1sc3BlY2lhbGNoYXJzKChzdHJpbmcpJHYsIEVOVF9RVU9URVMsICdVVEYtOCcpOyB9CgpmdW5jdGlvbiBhdHBjX2RlZmF1bHQoKTogYXJyYXkKewogICAgcmV0dXJuIFsKICAgICAgICAnc2NoZW1hX3ZlcnNpb24nID0+IDEsCiAgICAgICAgJ2xlYWd1ZV9wYW5lbCcgPT4gWyd0aXRsZScgPT4gJ0xlYWd1ZSBJbmZvcm1hdGlvbicsICdpdGVtcycgPT4gW11dLAogICAgICAgICd0ZWFtX3BhbmVsJyA9PiBbJ3RpdGxlJyA9PiAnVGVhbSBNZW51JywgJ2l0ZW1zJyA9PiBbXV0sCiAgICBdOwp9CgpmdW5jdGlvbiBhdHBjX2xvYWQoc3RyaW5nICRwYXRoKTogYXJyYXkKewogICAgaWYgKCFpc19maWxlKCRwYXRoKSkgcmV0dXJuIGF0cGNfZGVmYXVsdCgpOwogICAgJHJhdyA9IGZpbGVfZ2V0X2NvbnRlbnRzKCRwYXRoKTsKICAgICRkYXRhID0gaXNfc3RyaW5nKCRyYXcpID8ganNvbl9kZWNvZGUoJHJhdywgdHJ1ZSkgOiBudWxsOwogICAgcmV0dXJuIGlzX2FycmF5KCRkYXRhKSA/IGFycmF5X3JlcGxhY2VfcmVjdXJzaXZlKGF0cGNfZGVmYXVsdCgpLCAkZGF0YSkgOiBhdHBjX2RlZmF1bHQoKTsKfQoKZnVuY3Rpb24gYXRwY19jbGVhbl91cmwoc3RyaW5nICR1cmwpOiBzdHJpbmcKewogICAgJHVybCA9IHRyaW0oJHVybCk7CiAgICBpZiAoJHVybCA9PT0gJycpIHJldHVybiAnJzsKICAgIGlmICgkdXJsWzBdID09PSAnLycpIHJldHVybiAkdXJsOwogICAgaWYgKHByZWdfbWF0Y2goJ35eaHR0cHM/Oi8vfmknLCAkdXJsKSkgcmV0dXJuICR1cmw7CiAgICByZXR1cm4gJyc7Cn0KCmZ1bmN0aW9uIGF0cGNfYnVpbGRfcGFuZWwoYXJyYXkgJHBvc3QsIHN0cmluZyAka2V5LCBzdHJpbmcgJGZhbGxiYWNrVGl0bGUpOiBhcnJheQp7CiAgICAkdGl0bGUgPSB0cmltKChzdHJpbmcpKCRwb3N0WyRrZXkgLiAnX3RpdGxlJ10gPz8gJGZhbGxiYWNrVGl0bGUpKTsKICAgIGlmICgkdGl0bGUgPT09ICcnKSAkdGl0bGUgPSAkZmFsbGJhY2tUaXRsZTsKCiAgICAkbGFiZWxzID0gJHBvc3RbJGtleSAuICdfbGFiZWwnXSA/PyBbXTsKICAgICR1cmxzID0gJHBvc3RbJGtleSAuICdfdXJsJ10gPz8gW107CiAgICAkZW5hYmxlZCA9ICRwb3N0WyRrZXkgLiAnX2VuYWJsZWQnXSA/PyBbXTsKICAgICRuZXdUYWIgPSAkcG9zdFska2V5IC4gJ19uZXdfdGFiJ10gPz8gW107CiAgICAkcmVtb3ZlID0gJHBvc3RbJGtleSAuICdfcmVtb3ZlJ10gPz8gW107CgogICAgJGl0ZW1zID0gW107CiAgICAkY291bnQgPSBtYXgoaXNfYXJyYXkoJGxhYmVscykgPyBjb3VudCgkbGFiZWxzKSA6IDAsIGlzX2FycmF5KCR1cmxzKSA/IGNvdW50KCR1cmxzKSA6IDApOwoKICAgIGZvciAoJGkgPSAwOyAkaSA8ICRjb3VudDsgJGkrKykgewogICAgICAgIGlmIChpc3NldCgkcmVtb3ZlWyRpXSkpIGNvbnRpbnVlOwoKICAgICAgICAkbGFiZWwgPSB0cmltKChzdHJpbmcpKCRsYWJlbHNbJGldID8/ICcnKSk7CiAgICAgICAgJHVybCA9IGF0cGNfY2xlYW5fdXJsKChzdHJpbmcpKCR1cmxzWyRpXSA/PyAnJykpOwoKICAgICAgICBpZiAoJGxhYmVsID09PSAnJyAmJiAkdXJsID09PSAnJykgY29udGludWU7CiAgICAgICAgaWYgKCRsYWJlbCA9PT0gJycgfHwgJHVybCA9PT0gJycpIGNvbnRpbnVlOwoKICAgICAgICAkaXRlbXNbXSA9IFsKICAgICAgICAgICAgJ2xhYmVsJyA9PiAkbGFiZWwsCiAgICAgICAgICAgICd1cmwnID0+ICR1cmwsCiAgICAgICAgICAgICdlbmFibGVkJyA9PiBpc3NldCgkZW5hYmxlZFskaV0pLAogICAgICAgICAgICAnbmV3X3RhYicgPT4gaXNzZXQoJG5ld1RhYlskaV0pLAogICAgICAgIF07CiAgICB9CgogICAgcmV0dXJuIFsndGl0bGUnID0+ICR0aXRsZSwgJ2l0ZW1zJyA9PiAkaXRlbXNdOwp9CgppZiAoIWlzc2V0KCRfU0VTU0lPTlsnYXRwY19jc3JmJ10pKSB7CiAgICAkX1NFU1NJT05bJ2F0cGNfY3NyZiddID0gYmluMmhleChyYW5kb21fYnl0ZXMoMjQpKTsKfQoKJGRhdGEgPSBhdHBjX2xvYWQoJGNvbnRlbnRQYXRoKTsKJG1lc3NhZ2UgPSAnJzsKCmlmICgkX1NFUlZFUlsnUkVRVUVTVF9NRVRIT0QnXSA9PT0gJ1BPU1QnKSB7CiAgICAkY3NyZiA9IChzdHJpbmcpKCRfUE9TVFsnY3NyZiddID8/ICcnKTsKCiAgICBpZiAoIWhhc2hfZXF1YWxzKChzdHJpbmcpJF9TRVNTSU9OWydhdHBjX2NzcmYnXSwgJGNzcmYpKSB7CiAgICAgICAgJG1lc3NhZ2UgPSAnU2F2ZSBibG9ja2VkOiBzZWN1cml0eSB0b2tlbiBtaXNtYXRjaC4nOwogICAgfSBlbHNlIHsKICAgICAgICAkbmV3RGF0YSA9IFsKICAgICAgICAgICAgJ3NjaGVtYV92ZXJzaW9uJyA9PiAxLAogICAgICAgICAgICAndXBkYXRlZF9hdCcgPT4gZGF0ZSgnWS1tLWQgSDppOnMnKSwKICAgICAgICAgICAgJ2xlYWd1ZV9wYW5lbCcgPT4gYXRwY19idWlsZF9wYW5lbCgkX1BPU1QsICdsZWFndWUnLCAnTGVhZ3VlIEluZm9ybWF0aW9uJyksCiAgICAgICAgICAgICd0ZWFtX3BhbmVsJyA9PiBhdHBjX2J1aWxkX3BhbmVsKCRfUE9TVCwgJ3RlYW0nLCAnVGVhbSBNZW51JyksCiAgICAgICAgXTsKCiAgICAgICAgJGJhY2t1cERpciA9IF9fRElSX18gLiAnL19taWdyYXRpb25fYmFja3Vwcy90ZWFtX3BhZ2VfY29udGVudF8nIC4gZGF0ZSgnWW1kX0hpcycpOwogICAgICAgICRiYWNrdXBPayA9IHRydWU7CgogICAgICAgIGlmIChpc19maWxlKCRjb250ZW50UGF0aCkpIHsKICAgICAgICAgICAgJGJhY2t1cE9rID0gKGlzX2RpcigkYmFja3VwRGlyKSB8fCBta2RpcigkYmFja3VwRGlyLCAwNzU1LCB0cnVlKSkKICAgICAgICAgICAgICAgICYmIGNvcHkoJGNvbnRlbnRQYXRoLCAkYmFja3VwRGlyIC4gJy9tcmxfdGVhbV9wYWdlX2NvbnRlbnQuanNvbicpOwogICAgICAgIH0KCiAgICAgICAgJGpzb24gPSBqc29uX2VuY29kZSgkbmV3RGF0YSwgSlNPTl9QUkVUVFlfUFJJTlQgfCBKU09OX1VORVNDQVBFRF9TTEFTSEVTKTsKICAgICAgICAkc2F2ZU9rID0gJGJhY2t1cE9rICYmIGlzX3N0cmluZygkanNvbikKICAgICAgICAgICAgJiYgZmlsZV9wdXRfY29udGVudHMoJGNvbnRlbnRQYXRoLCAkanNvbiAuIFBIUF9FT0wsIExPQ0tfRVgpICE9PSBmYWxzZTsKCiAgICAgICAgJG1lc3NhZ2UgPSAkc2F2ZU9rCiAgICAgICAgICAgID8gJ1RlYW0gcGFnZSBjb250ZW50IHNhdmVkLiBFeGlzdGluZyBKU09OIHdhcyBiYWNrZWQgdXAgZmlyc3QuJwogICAgICAgICAgICA6ICdTYXZlIGZhaWxlZC4gTm8gaW50ZW50aW9uYWwgY2hhbmdlcyB3ZXJlIG1hZGUuJzsKICAgICAgICAkZGF0YSA9IGF0cGNfbG9hZCgkY29udGVudFBhdGgpOwogICAgfQp9CgpmdW5jdGlvbiBhdHBjX3Jvd3Moc3RyaW5nICRrZXksIGFycmF5ICRpdGVtcyk6IHZvaWQKewogICAgZm9yZWFjaCAoJGl0ZW1zIGFzICRpID0+ICRpdGVtKSB7CiAgICAgICAgZWNobyAnPHRyPic7CiAgICAgICAgZWNobyAnPHRkIGNsYXNzPSJoYW5kbGUiPicgLiAoJGkgKyAxKSAuICc8L3RkPic7CiAgICAgICAgZWNobyAnPHRkPjxpbnB1dCBuYW1lPSInIC4gYXRwY19oKCRrZXkpIC4gJ19sYWJlbFtdIiB2YWx1ZT0iJyAuIGF0cGNfaCgkaXRlbVsnbGFiZWwnXSA/PyAnJykgLiAnIj48L3RkPic7CiAgICAgICAgZWNobyAnPHRkPjxpbnB1dCBuYW1lPSInIC4gYXRwY19oKCRrZXkpIC4gJ191cmxbXSIgdmFsdWU9IicgLiBhdHBjX2goJGl0ZW1bJ3VybCddID8/ICcnKSAuICciPjwvdGQ+JzsKICAgICAgICBlY2hvICc8dGQ+PGlucHV0IHR5cGU9ImNoZWNrYm94IiBuYW1lPSInIC4gYXRwY19oKCRrZXkpIC4gJ19lbmFibGVkWycgLiAkaSAuICddIiAnIC4gKCFlbXB0eSgkaXRlbVsnZW5hYmxlZCddKSA/ICdjaGVja2VkJyA6ICcnKSAuICc+PC90ZD4nOwogICAgICAgIGVjaG8gJzx0ZD48aW5wdXQgdHlwZT0iY2hlY2tib3giIG5hbWU9IicgLiBhdHBjX2goJGtleSkgLiAnX25ld190YWJbJyAuICRpIC4gJ10iICcgLiAoIWVtcHR5KCRpdGVtWyduZXdfdGFiJ10pID8gJ2NoZWNrZWQnIDogJycpIC4gJz48L3RkPic7CiAgICAgICAgZWNobyAnPHRkPjxpbnB1dCB0eXBlPSJjaGVja2JveCIgbmFtZT0iJyAuIGF0cGNfaCgka2V5KSAuICdfcmVtb3ZlWycgLiAkaSAuICddIj48L3RkPic7CiAgICAgICAgZWNobyAnPC90cj4nOwogICAgfQp9Cj8+PCFET0NUWVBFIGh0bWw+CjxodG1sPgo8aGVhZD4KPG1ldGEgY2hhcnNldD0idXRmLTgiPgo8bWV0YSBuYW1lPSJ2aWV3cG9ydCIgY29udGVudD0id2lkdGg9ZGV2aWNlLXdpZHRoLGluaXRpYWwtc2NhbGU9MSI+Cjx0aXRsZT5NYW5hZ2UgVGVhbSBQYWdlIENvbnRlbnQ8L3RpdGxlPgo8c3R5bGU+Cip7Ym94LXNpemluZzpib3JkZXItYm94fWJvZHl7bWFyZ2luOjA7YmFja2dyb3VuZDojMTUxNTE1O2NvbG9yOiNlZWU7Zm9udC1mYW1pbHk6VGFob21hLFZlcmRhbmEsU2Vnb2UgVUksc2Fucy1zZXJpZn0KLndyYXB7d2lkdGg6OTQlO21heC13aWR0aDoxMzAwcHg7bWFyZ2luOjIwcHggYXV0b30uY2FyZHtiYWNrZ3JvdW5kOiMyMDIwMjA7Ym9yZGVyOjFweCBzb2xpZCAjNTU1O2JvcmRlci1yYWRpdXM6MTRweDtwYWRkaW5nOjE4cHg7bWFyZ2luLWJvdHRvbToxNnB4fQpoMSxoMntjb2xvcjojZWZjOTgyfS50b3AgYXtjb2xvcjojNzZjZmZmfS5wYW5lbC10aXRsZXt3aWR0aDoxMDAlO21heC13aWR0aDo1NjBweDtwYWRkaW5nOjhweH0KdGFibGV7d2lkdGg6MTAwJTtib3JkZXItY29sbGFwc2U6Y29sbGFwc2U7bWFyZ2luLXRvcDoxMnB4fXRoLHRke2JvcmRlci1ib3R0b206MXB4IHNvbGlkICM0NDQ7cGFkZGluZzo3cHg7dGV4dC1hbGlnbjpsZWZ0fQp0ZCBpbnB1dDpub3QoW3R5cGU9Y2hlY2tib3hdKXt3aWR0aDoxMDAlfWlucHV0e3BhZGRpbmc6N3B4O2JhY2tncm91bmQ6IzExMTtjb2xvcjojZWVlO2JvcmRlcjoxcHggc29saWQgIzY2Njtib3JkZXItcmFkaXVzOjVweH0KYnV0dG9ue3BhZGRpbmc6MTBweCAxN3B4O2JvcmRlcjoxcHggc29saWQgIzVhN2ZiNTtib3JkZXItcmFkaXVzOjhweDtiYWNrZ3JvdW5kOiMxNDY2Yzk7Y29sb3I6I2ZmZjtmb250LXdlaWdodDo4MDA7Y3Vyc29yOnBvaW50ZXJ9Ci5tZXNzYWdle3BhZGRpbmc6MTBweDtib3JkZXI6MXB4IHNvbGlkICM3Nzc7Ym9yZGVyLXJhZGl1czo4cHg7Y29sb3I6I2VmYzk4Mn0uc21hbGx7Y29sb3I6I2JiYjtmb250LXNpemU6MTNweH0uaGFuZGxle3dpZHRoOjM2cHg7Y29sb3I6I2FhYX0KPC9zdHlsZT4KPC9oZWFkPgo8Ym9keT4KPGRpdiBjbGFzcz0id3JhcCI+CjxkaXYgY2xhc3M9ImNhcmQgdG9wIj4KPGgxPk1hbmFnZSBUZWFtIFBhZ2UgQ29udGVudDwvaDE+CjxwPjxhIGhyZWY9Ii90ZWFtX3JlZGVzaWduLnBocCI+4oaQIFRlYW0gUmVkZXNpZ248L2E+PC9wPgo8cD5UaGlzIGlzIGEgZGVkaWNhdGVkIGFkbWluIHRvb2wuIFRoZSBsaW5rIHRvIHRoaXMgcGFnZSBpcyBoYXJkLXdpcmVkIGludG8gdGhlIEFkbWluIE1lbnUgYW5kIGlzIG5vdCBlZGl0YWJsZSBoZXJlLjwvcD4KPD9waHAgaWYgKCRtZXNzYWdlICE9PSAnJyk6ID8+PGRpdiBjbGFzcz0ibWVzc2FnZSI+PD9waHAgZWNobyBhdHBjX2goJG1lc3NhZ2UpOyA/PjwvZGl2Pjw/cGhwIGVuZGlmOyA/Pgo8L2Rpdj4KCjxmb3JtIG1ldGhvZD0icG9zdCI+CjxpbnB1dCB0eXBlPSJoaWRkZW4iIG5hbWU9ImNzcmYiIHZhbHVlPSI8P3BocCBlY2hvIGF0cGNfaCgoc3RyaW5nKSRfU0VTU0lPTlsnYXRwY19jc3JmJ10pOyA/PiI+Cgo8P3BocCBmb3JlYWNoIChbJ2xlYWd1ZScgPT4gJ2xlYWd1ZV9wYW5lbCcsICd0ZWFtJyA9PiAndGVhbV9wYW5lbCddIGFzICRrZXkgPT4gJGRhdGFLZXkpOiA/Pgo8ZGl2IGNsYXNzPSJjYXJkIj4KPGgyPjw/cGhwIGVjaG8gJGtleSA9PT0gJ2xlYWd1ZScgPyAnTGVhZ3VlIEluZm9ybWF0aW9uIFBhbmVsJyA6ICdUZWFtIE1lbnUgUGFuZWwnOyA/PjwvaDI+CjxsYWJlbD5QYW5lbCB0aXRsZTxicj48aW5wdXQgY2xhc3M9InBhbmVsLXRpdGxlIiBuYW1lPSI8P3BocCBlY2hvIGF0cGNfaCgka2V5KTsgPz5fdGl0bGUiIHZhbHVlPSI8P3BocCBlY2hvIGF0cGNfaCgkZGF0YVskZGF0YUtleV1bJ3RpdGxlJ10gPz8gJycpOyA/PiI+PC9sYWJlbD4KPHRhYmxlPgo8dGhlYWQ+PHRyPjx0aD4jPC90aD48dGg+TGluayB0ZXh0PC90aD48dGg+VVJMPC90aD48dGg+RW5hYmxlZDwvdGg+PHRoPk5ldyB0YWI8L3RoPjx0aD5SZW1vdmU8L3RoPjwvdHI+PC90aGVhZD4KPHRib2R5IGlkPSI8P3BocCBlY2hvIGF0cGNfaCgka2V5KTsgPz4tcm93cyI+Cjw/cGhwIGF0cGNfcm93cygka2V5LCBpc19hcnJheSgkZGF0YVskZGF0YUtleV1bJ2l0ZW1zJ10gPz8gbnVsbCkgPyAkZGF0YVskZGF0YUtleV1bJ2l0ZW1zJ10gOiBbXSk7ID8+CjwvdGJvZHk+CjwvdGFibGU+CjxwIGNsYXNzPSJzbWFsbCI+Um93cyBzYXZlIGluIHRoZSBvcmRlciBzaG93bi4gVVJMcyBtdXN0IGJlZ2luIHdpdGggLywgaHR0cDovLywgb3IgaHR0cHM6Ly8uPC9wPgo8YnV0dG9uIHR5cGU9ImJ1dHRvbiIgb25jbGljaz0iYWRkUm93KCc8P3BocCBlY2hvIGF0cGNfaCgka2V5KTsgPz4nKSI+QWRkIExpbms8L2J1dHRvbj4KPC9kaXY+Cjw/cGhwIGVuZGZvcmVhY2g7ID8+Cgo8ZGl2IGNsYXNzPSJjYXJkIj48YnV0dG9uIHR5cGU9InN1Ym1pdCI+U2F2ZSBUZWFtIFBhZ2UgQ29udGVudDwvYnV0dG9uPjwvZGl2Pgo8L2Zvcm0+CjwvZGl2Pgo8c2NyaXB0PgpmdW5jdGlvbiBhZGRSb3coa2V5KXsKICAgIGNvbnN0IGJvZHk9ZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoa2V5Kyctcm93cycpOwogICAgY29uc3QgaT1ib2R5LnJvd3MubGVuZ3RoOwogICAgY29uc3Qgcm93PWJvZHkuaW5zZXJ0Um93KCk7CiAgICByb3cuaW5uZXJIVE1MPQogICAgICAnPHRkIGNsYXNzPSJoYW5kbGUiPicrKGkrMSkrJzwvdGQ+JysKICAgICAgJzx0ZD48aW5wdXQgbmFtZT0iJytrZXkrJ19sYWJlbFtdIiB2YWx1ZT0iIj48L3RkPicrCiAgICAgICc8dGQ+PGlucHV0IG5hbWU9Iicra2V5KydfdXJsW10iIHZhbHVlPSIiPjwvdGQ+JysKICAgICAgJzx0ZD48aW5wdXQgdHlwZT0iY2hlY2tib3giIG5hbWU9Iicra2V5KydfZW5hYmxlZFsnK2krJ10iIGNoZWNrZWQ+PC90ZD4nKwogICAgICAnPHRkPjxpbnB1dCB0eXBlPSJjaGVja2JveCIgbmFtZT0iJytrZXkrJ19uZXdfdGFiWycraSsnXSIgY2hlY2tlZD48L3RkPicrCiAgICAgICc8dGQ+PGlucHV0IHR5cGU9ImNoZWNrYm94IiBuYW1lPSInK2tleSsnX3JlbW92ZVsnK2krJ10iPjwvdGQ+JzsKfQo8L3NjcmlwdD4KPC9ib2R5Pgo8L2h0bWw+Cg==', true);

$teamExists=is_file($teamPath);
$teamSrc=$teamExists?(string)file_get_contents($teamPath):'';
$baselineOk=$teamExists && strpos($teamSrc,'VERSION: v004')!==false;
[$buildOk,$teamV005,$buildNote]=$baselineOk?build_team_v005($teamSrc):[false,'','team_redesign.php missing or not v004.'];

$dbOk=isset($dbo) && $dbo instanceof PDO;
if($dbOk) {
    try { $dbo->query('SELECT 1'); } catch(Throwable $e) { $dbOk=false; }
}

$prodTeamHash=is_file($prodTeam)?hash_file('sha256',$prodTeam):'';
$prodProfileHash=is_file($prodProfile)?hash_file('sha256',$prodProfile):'';

$apply=isset($_POST['apply']) && $_POST['apply']==='1';
$messages=[];
$success=false;

if($apply && $buildOk && $dbOk && is_string($helperData) && is_string($profileData) && is_string($adminData)) {
    $backupDir=$baseDir.'/_migration_backups/team_redesign_v005_'.date('Ymd_His');
    $backupOk=true;
    foreach([$teamPath,$helperPath,$profilePath,$adminPath] as $f) {
        if(!backup_one($f,$backupDir,$messages)) {$backupOk=false;break;}
    }

    $tableOk=false;
    if($backupOk) {
        try {
            $dbo->exec("CREATE TABLE IF NOT EXISTS mrl_user_preferences (
                userID INT NOT NULL,
                team_theme VARCHAR(32) NOT NULL DEFAULT 'dark',
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (userID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $tableOk=true;
            $messages[]='Verified/created mrl_user_preferences table.';
        } catch(Throwable $e) {
            $messages[]='Could not create/verify mrl_user_preferences table.';
        }
    }

    if($backupOk && $tableOk) {
        foreach([
            $helperPath=>$helperData,
            $profilePath=>$profileData,
            $adminPath=>$adminData,
            $teamPath=>$teamV005
        ] as $file=>$data) {
            if(!atomic_write($file,$data)) {$messages[]='Could not write '.basename($file).'.';break;}
            $messages[]='Installed '.basename($file).'.';
        }

        $sessionUID=(int)($_SESSION['userSession']??0);
        if($sessionUID>0) {
            try {
                $stmt=$dbo->prepare("INSERT INTO mrl_user_preferences (userID,team_theme,updated_at)
                                     VALUES (:uid,'cars',NOW())
                                     ON DUPLICATE KEY UPDATE userID=userID");
                $stmt->execute([':uid'=>$sessionUID]);
                $messages[]='Current logged-in user seeded to Cars only if no preference existed.';
            } catch(Throwable $e) {
                $messages[]='Current-user Cars seed was skipped.';
            }
        }
    }

    $teamUntouched=$prodTeamHash!=='' && $prodTeamHash===hash_file('sha256',$prodTeam);
    $profileUntouched=$prodProfileHash!=='' && $prodProfileHash===hash_file('sha256',$prodProfile);
    $messages[]=$teamUntouched?'Verified production team.php unchanged.':'ERROR: production team.php changed.';
    $messages[]=$profileUntouched?'Verified production profile.php unchanged.':'ERROR: production profile.php changed.';
    $messages[]=is_file($contentPath)?'Preserved mrl_team_page_content.json.':'WARNING: mrl_team_page_content.json missing.';

    $success=$teamUntouched && $profileUntouched
        && is_file($helperPath) && is_file($profilePath) && is_file($adminPath)
        && is_file($teamPath) && strpos((string)file_get_contents($teamPath),'VERSION: v005')!==false
        && !preg_grep('/Could not write|ERROR:/',$messages);
}
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>MRL Team Redesign Installer v005</title>
<style>
*{box-sizing:border-box}html{background:#111}body{margin:0;color:#eee;font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:94%;max-width:1200px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}
h1,h2{color:#efc982}table{width:100%;border-collapse:collapse}td{padding:9px;border-bottom:1px solid #444}.ok{color:#61e493}.bad{color:#ff7777}
button{padding:11px 20px;border:1px solid #5a7fb5;border-radius:9px;background:#1466c9;color:#fff;font-weight:800;cursor:pointer}
code,a{color:#76cfff}li{line-height:1.45;margin-bottom:5px}
</style></head><body><div class="wrap">
<div class="card"><h1>MRL Team Redesign Installer v005</h1><p>Pre-Live feature pass. Production <code>team.php</code> and <code>profile.php</code> stay untouched.</p></div>

<div class="card"><h2>Preflight</h2><table>
<?php row_status('team_redesign.php present',$teamExists,$teamPath); ?>
<?php row_status('Expected redesign baseline v004',$baselineOk,$baselineOk?'Ready for v005.':'STOP - baseline mismatch.'); ?>
<?php row_status('v005 build generated',$buildOk,$buildNote); ?>
<?php row_status('Database connection available',$dbOk,$dbOk?'PDO ready.':'STOP - PDO unavailable.'); ?>
<?php row_status('Production team.php present',is_file($prodTeam),$prodTeam); ?>
<?php row_status('Production profile.php present',is_file($prodProfile),$prodProfile); ?>
<?php row_status('JSON content file present',is_file($contentPath),$contentPath); ?>
</table></div>

<?php if($buildOk && $dbOk): ?>
<div class="card"><h2>What v005 adds</h2><ul>
<li>Non-collapsible status panels above the current team chart and below the normal submission form.</li>
<li>Hard-wired Admin action: <strong>Manage Team Page Content</strong>.</li>
<li>Admin editor for League Information and Team Menu titles/links/enabled state.</li>
<li>Per-user database themes: <strong>Cars, Starry Night, Dark, Light</strong>.</li>
<li>Dark is the default for users without a saved preference.</li>
<li>The currently logged-in installer user keeps Cars if no preference exists yet.</li>
<li>New <code>profile_redesign.php</code> with the same theme and a Page Theme selector.</li>
<li>Starry Night uses <code>/images/starry_night.jpg</code>.</li>
<li>Actual chart presentation remains unchanged.</li>
</ul>
<?php if(!$apply): ?><form method="post"><input type="hidden" name="apply" value="1"><button>Install v005 Feature Pass</button></form><?php endif; ?>
</div>
<?php endif; ?>

<?php if($apply): ?><div class="card"><h2>Apply Result</h2>
<p class="<?php echo $success?'ok':'bad'; ?>"><strong><?php echo $success?'SUCCESS':'FAILED'; ?></strong></p>
<ul><?php foreach($messages as $m): ?><li><?php echo ih($m); ?></li><?php endforeach; ?></ul>
<?php if($success): ?>
<p><a href="/team_redesign.php" target="_blank">Open Team Redesign v005</a></p>
<p><a href="/profile_redesign.php" target="_blank">Open Profile Redesign</a></p>
<p><a href="/admin_team_page_content.php" target="_blank">Open Team Page Content Manager</a></p>
<?php endif; ?></div><?php endif; ?>
</div></body></html>
