<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION='v076';
const TARGET_VERSION='v077';

$docRoot=rtrim((string)($_SERVER['DOCUMENT_ROOT']??__DIR__),'/\\');
$target=$docRoot.'/race_results/weekly_standings.php';
$backupDir=$docRoot.'/_installer_backups/weekly_standings_view_transition_20260921_113131pm';
$backupFile=$backupDir.'/weekly_standings.php';

function h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function lint_php_file(string $path):array{
    if(!function_exists('shell_exec'))return ['ok'=>false,'output'=>'shell_exec() is not available.'];
    $out=@shell_exec('php -l '.escapeshellarg($path).' 2>&1');
    if($out===null)return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    $out=trim((string)$out);
    return ['ok'=>stripos($out,'No syntax errors detected')!==false,'output'=>$out];
}
function current_version(string $content):string{
    if(preg_match('/\*\s*VERSION:\s*(v\d+)/i',$content,$m))return (string)$m[1];
    return '';
}
function replace_once(string $subject,string $search,string $replace,string $label,array &$errors):string{
    $count=substr_count($subject,$search);
    if($count!==1){$errors[]=$label.': expected exactly 1 match, found '.$count.'.';return $subject;}
    $pos=strpos($subject,$search);
    return substr($subject,0,$pos).$replace.substr($subject,$pos+strlen($search));
}
function build_candidate(string $source,array &$errors):string{
    $out=$source;
    $out=replace_once($out,' * VERSION: v076',' * VERSION: v077','Version header',$errors);

    if(preg_match('/ \* LAST MODIFIED: .*? ET\R/',$out,$m)){
        $out=str_replace($m[0],' * LAST MODIFIED: 9/21/2026 11:31:31 pm ET'.PHP_EOL,$out);
    }else{$errors[]='LAST MODIFIED header not found.';}

    $ca=" * CHANGELOG:\n *\n";
    $ci=" * CHANGELOG:\n *\n"
       ." * v077 (9/21/2026 11:31:31 pm ET)\n"
       ." *   - UI: Added cross-document view transition (@view-transition) so supported browsers crossfade between race pages instead of flashing blank.\n"
       ." *   - PRESERVE: v076 mobile layout, nav behavior, report buttons, scoring, themes, print/PDF, spreadsheet export, validation, audit, and release history unchanged.\n"
       ." *\n";
    $out=replace_once($out,$ca,$ci,'Changelog anchor',$errors);

    $styleAnchor="    <style>\n        html {\n";
    $styleInsert="    <style>\n"
        ."        /* v077: crossfade between page loads in supporting browsers */\n"
        ."        @view-transition {\n"
        ."            navigation: auto;\n"
        ."        }\n\n"
        ."        html {\n";
    $out=replace_once($out,$styleAnchor,$styleInsert,'View transition CSS anchor',$errors);

    return $out;
}
function candidate_lint(string $candidate,string $dir):array{
    $tmp=@tempnam($dir,'.mrl_ws077_');
    if($tmp===false)$tmp=@tempnam(sys_get_temp_dir(),'mrl_ws077_');
    if($tmp===false)return ['ok'=>false,'output'=>'Could not create temporary lint file.'];
    if(@file_put_contents($tmp,$candidate,LOCK_EX)===false){@unlink($tmp);return ['ok'=>false,'output'=>'Could not write temporary lint file.'];}
    $r=lint_php_file($tmp);@unlink($tmp);return $r;
}

$action=(string)($_POST['action']??'');$message='';$messageClass='info';
$targetExists=is_file($target);$targetWritable=$targetExists&&is_writable($target);
$source=$targetExists?(string)@file_get_contents($target):'';$version=$source!==''?current_version($source):'';$alreadyInstalled=($version===TARGET_VERSION);
$errors=[];$candidate='';$candidateLint=['ok'=>false,'output'=>'Not run.'];

if($targetExists&&$version===EXPECTED_SOURCE_VERSION){
    $candidate=build_candidate($source,$errors);
    if(empty($errors))$candidateLint=candidate_lint($candidate,dirname($target));
}

$installedLint=$targetExists?lint_php_file($target):['ok'=>false,'output'=>'Target missing.'];
$backupExists=is_file($backupFile);
$canApply=$targetExists&&$targetWritable&&$version===EXPECTED_SOURCE_VERSION&&empty($errors)&&!empty($candidateLint['ok']);

if($action==='apply'){
    if(!$canApply){$message='Apply blocked: preflight is not fully PASS.';$messageClass='bad';}
    else{
        if(!is_dir($backupDir)&&!@mkdir($backupDir,0755,true)&&!is_dir($backupDir)){$message='Apply blocked: backup directory could not be created.';$messageClass='bad';}
        elseif(is_file($backupFile)){$message='Apply blocked: backup already exists.';$messageClass='bad';}
        elseif(!@copy($target,$backupFile)){$message='Apply blocked: backup could not be created.';$messageClass='bad';}
        else{
            $tmp=$target.'.mrl_tmp_'.uniqid('',true);
            @file_put_contents($tmp,$candidate,LOCK_EX);@chmod($tmp,0644);
            $l=lint_php_file($tmp);
            if(empty($l['ok'])){@unlink($tmp);$message='Apply blocked: temporary file failed lint.';$messageClass='bad';}
            elseif(!@rename($tmp,$target)){@unlink($tmp);$message='Apply failed: replacement could not be completed.';$messageClass='bad';}
            else{
                @chmod($target,0644);$l2=lint_php_file($target);
                if(empty($l2['ok'])){@copy($backupFile,$target);@chmod($target,0644);$message='Installed file failed lint and backup was restored.';$messageClass='bad';}
                else{$message='PASS — weekly_standings.php v077 installed and passed PHP lint.';$messageClass='good';}
            }
        }
    }
}

if($action==='rollback'&&is_file($backupFile)){
    @copy($backupFile,$target);@chmod($target,0644);
    $message='Rollback complete — weekly_standings.php v076 restored.';$messageClass='good';
}

$source=is_file($target)?(string)@file_get_contents($target):'';$version=$source!==''?current_version($source):'';$alreadyInstalled=($version===TARGET_VERSION);$backupExists=is_file($backupFile);
?>
<!doctype html>
<html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Weekly Standings View Transition</title>
<style>
:root{color-scheme:dark}body{margin:0;padding:22px;background:#101010;color:#eee;font-family:Arial,sans-serif}
.wrap{max-width:1000px;margin:auto}h1{color:#f2c98e}
.card{margin:14px 0;padding:16px;background:#1b1b1b;border:1px solid #3d3d3d;border-radius:14px}
.pass{color:#66df8d;font-weight:800}.fail{color:#ff7474;font-weight:800}
table{width:100%;border-collapse:collapse}th,td{padding:9px;border-bottom:1px solid #333;text-align:left}th{color:#f2c98e}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}
.apply{background:#248c4b}.neutral{background:#276fca}.rollback{background:#a83434}button:disabled{opacity:.4}
</style></head><body><div class="wrap">
<h1>MRL Weekly Standings View Transition</h1>
<div>Installer v001 · generated 9/21/2026 11:31:31 pm ET</div>
<?php if($message!==''):?><div class="card"><?php echo h($message);?></div><?php endif;?>
<div class="card"><h2>Change</h2><p>Adds only the same cross-document <code>@view-transition</code> navigation rule now used by Team Chart v027. No Weekly Standings controls are hidden or altered.</p></div>
<div class="card"><h2>Preflight</h2><table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<tr><td>Version</td><td class="<?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'pass':'fail';?>"><?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'PASS':'FAIL';?></td><td><?php echo h($version);?> → v077</td></tr>
<tr><td>Patch signatures</td><td class="<?php echo ($alreadyInstalled||empty($errors))?'pass':'fail';?>"><?php echo ($alreadyInstalled||empty($errors))?'PASS':'FAIL';?></td><td><?php echo h($alreadyInstalled?'Already installed.':(empty($errors)?'Expected v076 anchors found exactly once.':implode(' | ',$errors)));?></td></tr>
<tr><td>Candidate lint</td><td class="<?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'pass':'fail';?>"><?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'PASS':'FAIL';?></td><td><?php echo h($alreadyInstalled?'Not needed.':$candidateLint['output']);?></td></tr>
</table>
<p>
<form method="post" style="display:inline"><input type="hidden" name="action" value="apply"><button class="apply" <?php echo $canApply?'':'disabled';?>>Apply v077</button></form>
<a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF']??''));?>">Refresh / Preflight</a>
<form method="post" style="display:inline"><input type="hidden" name="action" value="rollback"><button class="rollback" <?php echo $backupExists?'':'disabled';?>>Rollback</button></form>
</p></div></div></body></html>
