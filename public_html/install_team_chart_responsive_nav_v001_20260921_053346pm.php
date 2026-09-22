<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');
const EXPECTED_SOURCE_VERSION='v024';
const TARGET_VERSION='v025';
$docRoot=rtrim((string)($_SERVER['DOCUMENT_ROOT']??__DIR__),'/\\');
$target=$docRoot.'/team_chart.php';
$backupDir=$docRoot.'/_installer_backups/team_chart_responsive_nav_20260921_053346pm';
$backupFile=$backupDir.'/team_chart.php';

function h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function lint_php_file(string $path):array{
    if(!function_exists('shell_exec'))return ['ok'=>false,'output'=>'shell_exec() is not available.'];
    $out=@shell_exec('php -l '.escapeshellarg($path).' 2>&1');
    if($out===null)return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    $out=trim((string)$out);
    return ['ok'=>stripos($out,'No syntax errors detected')!==false,'output'=>$out];
}
function current_version(string $c):string{if(preg_match('/\*\s*VERSION:\s*(v\d+)/i',$c,$m))return (string)$m[1];return '';}
function replace_once(string $s,string $a,string $b,string $label,array &$errors):string{
    $n=substr_count($s,$a);
    if($n!==1){$errors[]=$label.': expected 1 match, found '.$n.'.';return $s;}
    $p=strpos($s,$a);
    return substr($s,0,$p).$b.substr($s,$p+strlen($a));
}
function build_candidate(string $source,array &$errors):string{
    $out=$source;
    $out=replace_once($out,' * VERSION: v024',' * VERSION: v025','Version header',$errors);
    if(preg_match('/ \* LAST MODIFIED: .*? ET\R/',$out,$m)){$out=str_replace($m[0],' * LAST MODIFIED: 9/21/2026 5:33:46 pm ET'.PHP_EOL,$out);}else{$errors[]='LAST MODIFIED header not found.';}
    $ca=" * CHANGELOG:\n *\n";
    $ci=" * CHANGELOG:\n *\n"
       ." * v025 (9/21/2026 5:33:46 pm ET)\n"
       ." * - RESPONSIVE: Team Chart now fits the available screen width instead of defaulting to a horizontal-scroll viewport.\n"
       ." * - MOBILE: Narrow screens allow cell wrapping so the full seven-column chart remains visible and can be pinch-zoomed naturally.\n"
       ." * - NAV: Previous/next controls now use ◀ / ▶ with directional half-pill rounding while preserving the existing gap between buttons.\n"
       ." * - PRINT: Existing landscape print/PDF behavior remains unchanged.\n"
       ." *\n";
    $out=replace_once($out,$ca,$ci,'Changelog anchor',$errors);
    $out=replace_once($out,'<meta charset="UTF-8">','<meta charset="UTF-8">'."\n".'    <meta name="viewport" content="width=device-width, initial-scale=1">','Viewport meta',$errors);
    $out=replace_once($out,'title="Previous year">&lt;&lt;</button>','title="Previous year" aria-label="Previous year">◀</button>','Previous year glyph',$errors);
    $out=replace_once($out,'title="Next year">&gt;&gt;</button>','title="Next year" aria-label="Next year">▶</button>','Next year glyph',$errors);
    $out=replace_once($out,'title="Previous segment">&lt;&lt;</button>','title="Previous segment" aria-label="Previous segment">◀</button>','Previous segment glyph',$errors);
    $out=replace_once($out,'title="Next segment">&gt;&gt;</button>','title="Next segment" aria-label="Next segment">▶</button>','Next segment glyph',$errors);

    $anchor="        .teamchart-navpair .teamchart-actionbtn {\n            min-width: 34px !important;\n            padding-left: 6px !important;\n            padding-right: 6px !important;\n        }\n";
    $insert=$anchor
        ."\n        .teamchart-navpair .teamchart-actionbtn:first-child { border-radius: 14px 3px 3px 14px !important; }\n"
        ."        .teamchart-navpair .teamchart-actionbtn:last-child { border-radius: 3px 14px 14px 3px !important; }\n"
        ."        .teamchart-scroll { overflow-x: visible !important; width: 100% !important; }\n"
        ."        .teamchart-table { width: 100% !important; display: table !important; table-layout: auto !important; }\n"
        ."        @media screen and (max-width: 900px) {\n"
        ."            .teamchart-container { width: 100% !important; max-width: none !important; }\n"
        ."            .teamchart-row { flex-wrap: wrap !important; }\n"
        ."            .teamchart-table, .teamchart-table th, .teamchart-table td { font-size: 11px !important; line-height: 1.2 !important; }\n"
        ."            .teamchart-table th, .teamchart-table td { white-space: normal !important; padding: 2px 3px !important; overflow-wrap: anywhere; }\n"
        ."            .teamchart-table td.teamchart-notes-row, .teamchart-table td.teamchart-notes-row .teamchart-note-line { font-size: 10px !important; }\n"
        ."        }\n";
    $out=replace_once($out,$anchor,$insert,'Responsive/nav CSS anchor',$errors);
    return $out;
}
function candidate_lint(string $candidate,string $dir):array{
    $tmp=@tempnam($dir,'.mrl_tc_lint_');if($tmp===false)$tmp=@tempnam(sys_get_temp_dir(),'mrl_tc_lint_');
    if($tmp===false)return ['ok'=>false,'output'=>'Could not create temporary lint file.'];
    @file_put_contents($tmp,$candidate,LOCK_EX);$r=lint_php_file($tmp);@unlink($tmp);return $r;
}
$action=(string)($_POST['action']??'');$message='';$messageClass='info';
$targetExists=is_file($target);$targetWritable=$targetExists&&is_writable($target);
$source=$targetExists?(string)@file_get_contents($target):'';$version=$source!==''?current_version($source):'';$alreadyInstalled=$version===TARGET_VERSION;
$errors=[];$candidate='';$candidateLint=['ok'=>false,'output'=>'Not run.'];
if($targetExists&&$version===EXPECTED_SOURCE_VERSION){$candidate=build_candidate($source,$errors);if(empty($errors))$candidateLint=candidate_lint($candidate,dirname($target));}
$installedLint=$targetExists?lint_php_file($target):['ok'=>false,'output'=>'Target missing.'];$backupExists=is_file($backupFile);
$canApply=$targetExists&&$targetWritable&&$version===EXPECTED_SOURCE_VERSION&&empty($errors)&&!empty($candidateLint['ok']);
if($action==='apply'){
 if(!$canApply){$message='Apply blocked: preflight is not fully PASS.';$messageClass='bad';}
 else{
  if(!is_dir($backupDir)&&!@mkdir($backupDir,0755,true)&&!is_dir($backupDir)){$message='Apply blocked: backup directory could not be created.';$messageClass='bad';}
  elseif(is_file($backupFile)){$message='Apply blocked: backup already exists.';$messageClass='bad';}
  elseif(!@copy($target,$backupFile)){$message='Apply blocked: backup could not be created.';$messageClass='bad';}
  else{
    $tmp=$target.'.mrl_tmp_'.uniqid('',true);@file_put_contents($tmp,$candidate,LOCK_EX);@chmod($tmp,0644);$l=lint_php_file($tmp);
    if(empty($l['ok'])){@unlink($tmp);$message='Apply blocked: temp file failed lint.';$messageClass='bad';}
    elseif(!@rename($tmp,$target)){@unlink($tmp);$message='Apply failed: replacement could not be completed.';$messageClass='bad';}
    else{@chmod($target,0644);$l2=lint_php_file($target);if(empty($l2['ok'])){@copy($backupFile,$target);$message='Installed file failed lint and backup was restored.';$messageClass='bad';}else{$message='PASS — team_chart.php v025 installed and passed PHP lint.';$messageClass='good';}}
  }
 }
}
if($action==='rollback'&&is_file($backupFile)){@copy($backupFile,$target);@chmod($target,0644);$message='Rollback complete.';$messageClass='good';}
$source=is_file($target)?(string)@file_get_contents($target):'';$version=$source!==''?current_version($source):'';$alreadyInstalled=$version===TARGET_VERSION;$backupExists=is_file($backupFile);$installedLint=is_file($target)?lint_php_file($target):['ok'=>false,'output'=>'Target missing.'];
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MRL Team Chart Responsive + Nav</title>
<style>:root{color-scheme:dark}body{margin:0;padding:22px;background:#101010;color:#eee;font-family:Arial,sans-serif}.wrap{max-width:1100px;margin:auto}h1{color:#f2c98e}.card{margin:14px 0;padding:16px;background:#1b1b1b;border:1px solid #3d3d3d;border-radius:14px}table{width:100%;border-collapse:collapse}th,td{padding:9px;border-bottom:1px solid #333;text-align:left}th{color:#f2c98e}.pass{color:#66df8d;font-weight:800}.fail{color:#ff7474;font-weight:800}button,.btn{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}.apply{background:#248c4b}.neutral{background:#276fca}.rollback{background:#a83434}button:disabled{opacity:.4}</style>
</head><body><div class="wrap"><h1>MRL Team Chart Responsive + Nav</h1><div>Installer v001 · generated 9/21/2026 5:33:46 pm ET</div>
<?php if($message!==''):?><div class="card <?php echo h($messageClass);?>"><?php echo h($message);?></div><?php endif;?>
<div class="card"><h2>Changes</h2><ul><li>Full chart fits the available screen instead of using a horizontal-scroll-first viewport.</li><li>Narrow screens allow wrapping so all seven columns remain visible and pinch-zoom is natural.</li><li>Nav arrows become ◀ / ▶ with directional half-pill rounding; existing spacing is preserved.</li><li>Print/PDF rules are untouched.</li></ul></div>
<div class="card"><h2>Preflight</h2><table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<tr><td>Target</td><td class="<?php echo $targetExists?'pass':'fail';?>"><?php echo $targetExists?'PASS':'FAIL';?></td><td><?php echo h($target);?></td></tr>
<tr><td>Version</td><td class="<?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'pass':'fail';?>"><?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'PASS':'FAIL';?></td><td><?php echo h($version);?> → v025</td></tr>
<tr><td>Patch signatures</td><td class="<?php echo ($alreadyInstalled||empty($errors))?'pass':'fail';?>"><?php echo ($alreadyInstalled||empty($errors))?'PASS':'FAIL';?></td><td><?php echo h($alreadyInstalled?'Already installed.':(empty($errors)?'All expected anchors found exactly once.':implode(' | ',$errors)));?></td></tr>
<tr><td>Candidate lint</td><td class="<?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'pass':'fail';?>"><?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'PASS':'FAIL';?></td><td><?php echo h($alreadyInstalled?'Not needed.':$candidateLint['output']);?></td></tr>
<tr><td>Installed lint</td><td class="<?php echo !empty($installedLint['ok'])?'pass':'fail';?>"><?php echo !empty($installedLint['ok'])?'PASS':'FAIL';?></td><td><?php echo h($installedLint['output']);?></td></tr>
</table><p>
<form method="post" style="display:inline"><input type="hidden" name="action" value="apply"><button class="apply" <?php echo $canApply?'':'disabled';?>>Apply v025</button></form>
<a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF']??''));?>">Refresh / Preflight</a>
<form method="post" style="display:inline"><input type="hidden" name="action" value="rollback"><button class="rollback" <?php echo $backupExists?'':'disabled';?>>Rollback</button></form>
</p></div></div></body></html>