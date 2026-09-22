<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION = 'v056';
const TARGET_VERSION = 'v057';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/team.php';
$backupDir = $docRoot . '/_installer_backups/team_name_gate_20260922_014220pm';
$backupFile = $backupDir . '/team.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function lint_php_file(string $path): array {
    $out = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');
    if ($out === null) return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    $out = trim((string)$out);
    return ['ok'=>stripos($out,'No syntax errors detected') !== false,'output'=>$out];
}
function current_version(string $content): string {
    if (preg_match('/\\*\\s*VERSION:\\s*(v\\d+)/i', $content, $m)) return (string)$m[1];
    return '';
}
function replace_once(string $subject,string $search,string $replace,string $label,array &$errors): string {
    $count=substr_count($subject,$search);
    if($count!==1){$errors[]=$label.': expected exactly 1 match, found '.$count.'.';return $subject;}
    $pos=strpos($subject,$search);
    return substr($subject,0,$pos).$replace.substr($subject,$pos+strlen($search));
}
function build_candidate(string $source,array &$errors): string {
    $out=$source;
    $out=replace_once($out,' * VERSION: v056',' * VERSION: v057','Version header',$errors);
    if(preg_match('/ \\* LAST MODIFIED: .*? ET\\R/',$out,$m)){
        $out=str_replace($m[0],' * LAST MODIFIED: 9/22/2026 1:42:20 pm ET'.PHP_EOL,$out);
    } else {$errors[]='LAST MODIFIED header not found.';}
    $anchor=" * CHANGELOG:\n *\n";
    $insert=" * CHANGELOG:\n *\n"
      ." * v057 (9/22/2026 1:42:20 pm ET)\n"
      ." * - FIX: Current-year Team Name is now a prerequisite gate before NORMAL, LP, SPECIAL_AUTH, RD, or closed-window routing.\n"
      ." * - FIX: Brand-new accounts with no user_teams row can no longer fall directly into Late Pick after the normal segment deadline.\n"
      ." * - UI: Users without a Team Name are always shown the existing Team Name form first, regardless of pick-window state.\n"
      ." * - PRESERVE: Existing Team Name save handler/form, LP/RD eligibility, deadlines, pick forms, charts, themes, print/export, scoring, and DB write behavior unchanged.\n"
      ." *\n";
    $out=replace_once($out,$anchor,$insert,'Changelog anchor',$errors);

    $old=<<<'PHPBLOCK'
        if ($formLocked === 'no') {
            if ($normalPickWindowOpen) {

                $teamName = '';

                if (isset($dbconnect)) {
                    $teamCheck = mysqli_query(
                        $dbconnect,
                        "SELECT teamName
                         FROM user_teams
                         WHERE userID = $uid
                           AND raceYear = $raceYear
                         LIMIT 1"
                    );
                    if ($teamCheck) {
                        $teamRow = mysqli_fetch_assoc($teamCheck);
                        $teamName = trim((string)($teamRow['teamName'] ?? ''));
                    }
                }

                if ($teamName === '') {

                    if (!isset($dbconnect)) {
                        echo "<div style='color:red; font-weight:bold; font-size:14pt; text-align:center;'>Database connection not available.</div>";
                    } else {
                        mrl_teamname_render_form($dbconnect, (string)$raceYear, $uid, (string)$teamNameMessage);
                    }

                } else {

                    include $currentForm;
                    teampage_render_pick_success($pickSubmissionSuccess);
                    echo "<div class='mrl-rd-notice-panel mrl-rd-submission-panel'>";
                    include 'submitted_teams_count.php';
                    echo "</div>";

                }

            } else {
PHPBLOCK;

    $new=<<<'PHPBLOCK'
        if ($formLocked === 'no') {
            /*
             * Team Name is the outer prerequisite for every current-year pick path.
             * A brand-new account must establish its team identity before NORMAL,
             * LP, SPECIAL_AUTH, RD, or closed-window routing can be considered.
             */
            if ($currentUserTeamName === '') {

                if (!isset($dbconnect)) {
                    echo "<div style='color:red; font-weight:bold; font-size:14pt; text-align:center;'>Database connection not available.</div>";
                } else {
                    mrl_teamname_render_form($dbconnect, (string)$raceYear, $uid, (string)$teamNameMessage);
                }

            } elseif ($normalPickWindowOpen) {

                include $currentForm;
                teampage_render_pick_success($pickSubmissionSuccess);
                echo "<div class='mrl-rd-notice-panel mrl-rd-submission-panel'>";
                include 'submitted_teams_count.php';
                echo "</div>";

            } else {
PHPBLOCK;

    $out=replace_once($out,$old,$new,'Team Name outer gate',$errors);
    return $out;
}
function candidate_lint(string $candidate,string $dir): array {
    $tmp=@tempnam($dir,'.mrl_team057_'); if($tmp===false)$tmp=@tempnam(sys_get_temp_dir(),'mrl_team057_');
    if($tmp===false)return ['ok'=>false,'output'=>'Could not create temporary lint file.'];
    if(@file_put_contents($tmp,$candidate,LOCK_EX)===false){@unlink($tmp);return ['ok'=>false,'output'=>'Could not write temporary lint file.'];}
    $r=lint_php_file($tmp); @unlink($tmp); return $r;
}

$action=(string)($_POST['action']??''); $message=''; $messageClass='info';
$targetExists=is_file($target); $targetWritable=$targetExists&&is_writable($target);
$source=$targetExists?(string)@file_get_contents($target):''; $version=$source!==''?current_version($source):''; $alreadyInstalled=($version===TARGET_VERSION);
$errors=[]; $candidate=''; $candidateLint=['ok'=>false,'output'=>'Not run.'];
if($targetExists&&$version===EXPECTED_SOURCE_VERSION){$candidate=build_candidate($source,$errors); if(empty($errors))$candidateLint=candidate_lint($candidate,dirname($target));}
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
            @chmod($backupFile,0644); $tmp=$target.'.mrl_tmp_'.uniqid('',true); @file_put_contents($tmp,$candidate,LOCK_EX); @chmod($tmp,0644); $l=lint_php_file($tmp);
            if(empty($l['ok'])){@unlink($tmp);$message='Apply blocked: temporary replacement failed PHP lint.';$messageClass='bad';}
            elseif(!@rename($tmp,$target)){@unlink($tmp);$message='Apply failed: replacement could not be completed.';$messageClass='bad';}
            else{@chmod($target,0644);$l2=lint_php_file($target);if(empty($l2['ok'])){@copy($backupFile,$target);@chmod($target,0644);$message='Installed file failed lint and backup was restored.';$messageClass='bad';}else{$message='PASS — team.php v057 installed and passed PHP lint.';$messageClass='good';}}
        }
    }
}
if($action==='rollback'){
    if(!is_file($backupFile)){$message='Rollback unavailable: backup not found.';$messageClass='bad';}
    elseif(!@copy($backupFile,$target)){$message='Rollback failed: backup could not be restored.';$messageClass='bad';}
    else{@chmod($target,0644);$l=lint_php_file($target);$message=!empty($l['ok'])?'Rollback complete — team.php v056 restored and lint passed.':'Rollback copy completed, but restored file failed lint.';$messageClass=!empty($l['ok'])?'good':'bad';}
}
$source=is_file($target)?(string)@file_get_contents($target):'';$version=$source!==''?current_version($source):'';$alreadyInstalled=($version===TARGET_VERSION);$backupExists=is_file($backupFile);$installedLint=is_file($target)?lint_php_file($target):['ok'=>false,'output'=>'Target missing.'];
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MRL Team Name Gate</title>
<style>:root{color-scheme:dark}body{margin:0;padding:22px;background:#101010;color:#eee;font-family:Arial,sans-serif}.wrap{max-width:1050px;margin:auto}h1{color:#f2c98e}.card{margin:14px 0;padding:16px;background:#1b1b1b;border:1px solid #3d3d3d;border-radius:14px}.pass{color:#66df8d;font-weight:800}.fail{color:#ff7474;font-weight:800}table{width:100%;border-collapse:collapse}th,td{padding:9px;border-bottom:1px solid #333;text-align:left}th{color:#f2c98e}button,.btn{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}.apply{background:#248c4b}.neutral{background:#276fca}.rollback{background:#a83434}button:disabled{opacity:.4}</style></head><body><div class="wrap">
<h1>MRL Team Name Gate</h1><div>Installer v003 · generated 9/22/2026 1:58:01 pm ET</div>
<?php if($message!==''):?><div class="card"><?php echo h($message);?></div><?php endif;?>
<div class="card"><h2>What this fixes</h2><p>A logged-in user with no current-year Team Name is stopped at the existing Team Name form before any Normal, Late Pick, Special Auth, Replacement Driver, or closed-window routing.</p><p>No database schema or pick-writing logic changes.</p></div>
<div class="card"><h2>Preflight</h2><table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<tr><td>Version</td><td class="<?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'pass':'fail';?>"><?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'PASS':'FAIL';?></td><td><?php echo h($version);?> → v057</td></tr>
<tr><td>Patch signatures</td><td class="<?php echo ($alreadyInstalled||empty($errors))?'pass':'fail';?>"><?php echo ($alreadyInstalled||empty($errors))?'PASS':'FAIL';?></td><td><?php echo h($alreadyInstalled?'Already installed.':(empty($errors)?'Expected v056 Team Name routing block found exactly once.':implode(' | ',$errors)));?></td></tr>
<tr><td>Candidate lint</td><td class="<?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'pass':'fail';?>"><?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'PASS':'FAIL';?></td><td><?php echo h($alreadyInstalled?'Not needed.':$candidateLint['output']);?></td></tr>
<tr><td>Installed lint</td><td class="<?php echo !empty($installedLint['ok'])?'pass':'fail';?>"><?php echo !empty($installedLint['ok'])?'PASS':'FAIL';?></td><td><?php echo h($installedLint['output']);?></td></tr>
</table><p><form method="post" style="display:inline"><input type="hidden" name="action" value="apply"><button class="apply" <?php echo $canApply?'':'disabled';?>>Apply v057</button></form> <a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF']??''));?>">Refresh / Preflight</a> <form method="post" style="display:inline"><input type="hidden" name="action" value="rollback"><button class="rollback" <?php echo $backupExists?'':'disabled';?>>Rollback</button></form></p></div>
</div></body></html>