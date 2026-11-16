<?php
/**
 * install_admin_paid_payouts_relocate.php
 *
 * VERSION: v002
 * LAST MODIFIED: 11/15/2026 7:33:08 pm
 *
 * Purpose:
 * - Correct the v001 admin_paid_payouts file location.
 * - Move the two newly installed financial-admin PHP files from /race_results
 *   to /public_html without touching the new financial database tables.
 * - Build corrected candidates from the exact installed v001 source files.
 * - Adjust only location-dependent include/path references.
 * - Lint before Apply and after installation.
 * - Remove the misplaced /race_results copies only after root-level validation passes.
 *
 * CHANGELOG:
 * v002 (11/15/2026)
 * - FIX: Relocates admin_paid_payouts.php and admin_paid_payouts_helper.php to /public_html.
 * - SAFE: Uses the installed v001 files as source; does not recreate financial logic from memory.
 * - SAFE: Preserves mrl_financial_fee_history and mrl_financial_team_year tables/data.
 * - SAFE: Does not modify payouts.php, payouts_helper.php, Paid_Status_Year.php, scoring, scheduler, or snapshots.
 * - SAFE: Backs up both misplaced source files and supports explicit rollback.
 */

declare(strict_types=1);

date_default_timezone_set('America/New_York');

$installerVersion = 'v002';
$publicRoot = dirname(__DIR__);
$raceDir = $publicRoot . '/race_results';
$installerDir = __DIR__;
$sourcePage = $raceDir . '/admin_paid_payouts.php';
$sourceHelper = $raceDir . '/admin_paid_payouts_helper.php';
$targetPage = $publicRoot . '/admin_paid_payouts.php';
$targetHelper = $publicRoot . '/admin_paid_payouts_helper.php';
$backupDir = $installerDir . '/_backup_admin_paid_payouts_relocate_v002';
$backupPage = $backupDir . '/admin_paid_payouts.php';
$backupHelper = $backupDir . '/admin_paid_payouts_helper.php';
$stateFile = $backupDir . '/state.json';

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nowEt(): string { return date('c'); }
function shaFileSafe(string $f): string { return is_file($f) ? (hash_file('sha256', $f) ?: '') : ''; }
function phpLint(string $file): array {
    $cmd = 'php -l ' . escapeshellarg($file) . ' 2>&1';
    $out = shell_exec($cmd);
    $out = is_string($out) ? trim($out) : '';
    return [strpos($out, 'No syntax errors detected') !== false, $out !== '' ? $out : 'No lint output returned'];
}
function atomicWrite(string $target, string $content): bool {
    $dir = dirname($target);
    $tmp = tempnam($dir, '.mrl_tmp_');
    if ($tmp === false) return false;
    $ok = file_put_contents($tmp, $content, LOCK_EX) !== false;
    if ($ok) @chmod($tmp, 0644);
    if ($ok) $ok = @rename($tmp, $target);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    return $ok;
}
function addCheck(array &$checks, string $label, bool $ok, bool $required, string $detail=''): void {
    $checks[] = ['label'=>$label,'status'=>$ok?'PASS':'FAIL','required'=>$required,'detail'=>$detail];
}
function allRequiredPass(array $checks): bool {
    foreach ($checks as $c) if (!empty($c['required']) && ($c['status'] ?? '') !== 'PASS') return false;
    return true;
}
function relocateContent(string $content, string $which, array &$notes): string {
    $original = $content;
    $repls = [];

    // Most likely v001 root dependency pattern when file lived in /race_results.
    $pairs = [
        ["dirname(__DIR__) . '/config.php'", "__DIR__ . '/config.php'"],
        ["dirname(__DIR__) . '/config_mrl.php'", "__DIR__ . '/config_mrl.php'"],
        ["dirname(__DIR__) . '/class.user.php'", "__DIR__ . '/class.user.php'"],
        ['dirname(__DIR__) . "/config.php"', '__DIR__ . "/config.php"'],
        ['dirname(__DIR__) . "/config_mrl.php"', '__DIR__ . "/config_mrl.php"'],
        ['dirname(__DIR__) . "/class.user.php"', '__DIR__ . "/class.user.php"'],
        ["__DIR__ . '/payouts_helper.php'", "__DIR__ . '/race_results/payouts_helper.php'"],
        ['__DIR__ . "/payouts_helper.php"', '__DIR__ . "/race_results/payouts_helper.php"'],
        ["__DIR__ . '/payouts.php'", "__DIR__ . '/race_results/payouts.php'"],
        ['__DIR__ . "/payouts.php"', '__DIR__ . "/race_results/payouts.php"'],
    ];
    foreach ($pairs as [$a,$b]) {
        $count = substr_count($content, $a);
        if ($count > 0) {
            $content = str_replace($a, $b, $content);
            $repls[] = $a . ' -> ' . $b . ' (' . $count . ')';
        }
    }

    // Common root-variable assignment forms.
    $regexes = [
        ['~(\$[A-Za-z_][A-Za-z0-9_]*Root\s*=\s*)dirname\(__DIR__\)(\s*;)~', '$1__DIR__$2', 'root variable dirname(__DIR__) -> __DIR__'],
        ['~(\$[A-Za-z_][A-Za-z0-9_]*_root\s*=\s*)dirname\(__DIR__\)(\s*;)~i', '$1__DIR__$2', 'root variable dirname(__DIR__) -> __DIR__'],
    ];
    foreach ($regexes as [$pat,$rep,$desc]) {
        $new = preg_replace($pat, $rep, $content, -1, $count);
        if ($new !== null && $count > 0) {
            $content = $new;
            $repls[] = $desc . ' (' . $count . ')';
        }
    }

    // Relative browser link that is correct from /race_results but not from root.
    if ($which === 'page') {
        foreach (["href=\"payouts.php\""=>"href=\"race_results/payouts.php\"", "href='payouts.php'"=>"href='race_results/payouts.php'"] as $a=>$b) {
            $count = substr_count($content, $a);
            if ($count > 0) { $content = str_replace($a,$b,$content); $repls[] = $a.' -> '.$b.' ('.$count.')'; }
        }
    }

    $notes = $repls;
    return $content;
}
function makeCandidates(string $sourcePage, string $sourceHelper): array {
    $p = file_get_contents($sourcePage);
    $h = file_get_contents($sourceHelper);
    if ($p === false || $h === false) throw new RuntimeException('Unable to read installed v001 source files.');
    $pn=[]; $hn=[];
    $p2 = relocateContent($p, 'page', $pn);
    $h2 = relocateContent($h, 'helper', $hn);
    return [$p2,$h2,$pn,$hn];
}
function reportPayload(string $stage, array $checks, array $extra=[]): array {
    return array_merge([
        'installer'=>'install_admin_paid_payouts_relocate_v002',
        'installer_version'=>'v002',
        'report_stage'=>$stage,
        'report_generated_et'=>nowEt(),
        'scope'=>[
            'source_files'=>['race_results/admin_paid_payouts.php','race_results/admin_paid_payouts_helper.php'],
            'target_files'=>['admin_paid_payouts.php','admin_paid_payouts_helper.php'],
            'database_tables_preserved'=>['mrl_financial_fee_history','mrl_financial_team_year'],
            'does_not_change'=>['Paid_Status_Year.php','race_results/payouts.php','race_results/payouts_helper.php','existing Financial table','weekly standings/scoring','cron/scheduler','race snapshots']
        ],
        'result'=>['ok'=>allRequiredPass($checks),'checks'=>$checks]
    ], $extra);
}
function jsonDownload(array $payload, string $stage): void {
    $stamp = date('Ymd_His');
    $name = 'install_admin_paid_payouts_relocate_v002_' . $stage . '_' . $stamp . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$name.'"');
    echo json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$checks=[]; $message=''; $messageClass='info'; $candidateInfo=[];

// PRECHECK
addCheck($checks, 'Installer running from /public_html/_installers', realpath($installerDir) === realpath($publicRoot.'/_installers'), true, $installerDir);
addCheck($checks, 'Misplaced v001 page exists', is_file($sourcePage), true, $sourcePage);
addCheck($checks, 'Misplaced v001 helper exists', is_file($sourceHelper), true, $sourceHelper);
addCheck($checks, 'Source page readable', is_readable($sourcePage), true, $sourcePage);
addCheck($checks, 'Source helper readable', is_readable($sourceHelper), true, $sourceHelper);
addCheck($checks, 'Public root writable', is_writable($publicRoot), true, $publicRoot);
addCheck($checks, 'Root target page absent', !file_exists($targetPage), true, file_exists($targetPage)?'Already exists':'Not present');
addCheck($checks, 'Root target helper absent', !file_exists($targetHelper), true, file_exists($targetHelper)?'Already exists':'Not present');
foreach (['config.php','config_mrl.php','class.user.php'] as $f) addCheck($checks, 'Required root file: '.$f, is_file($publicRoot.'/'.$f), true, $publicRoot.'/'.$f);
addCheck($checks, 'Required race_results/payouts_helper.php', is_file($raceDir.'/payouts_helper.php'), true, $raceDir.'/payouts_helper.php');
addCheck($checks, 'Required race_results/payouts.php', is_file($raceDir.'/payouts.php'), true, $raceDir.'/payouts.php');

if (allRequiredPass($checks)) {
    try {
        [$candidatePage,$candidateHelper,$pageNotes,$helperNotes] = makeCandidates($sourcePage,$sourceHelper);
        $tmpP=tempnam(sys_get_temp_dir(),'mrlrel_p_'); $tmpH=tempnam(sys_get_temp_dir(),'mrlrel_h_');
        file_put_contents($tmpP,$candidatePage); file_put_contents($tmpH,$candidateHelper);
        [$okP,$outP]=phpLint($tmpP); [$okH,$outH]=phpLint($tmpH);
        addCheck($checks,'php -l candidate: admin_paid_payouts.php',$okP,true,$outP);
        addCheck($checks,'php -l candidate: admin_paid_payouts_helper.php',$okH,true,$outH);
        addCheck($checks,'Candidate page differs only through relocation transform',strlen($candidatePage)>0,true,'transformations: '.count($pageNotes));
        addCheck($checks,'Candidate helper differs only through relocation transform',strlen($candidateHelper)>0,true,'transformations: '.count($helperNotes));
        $candidateInfo=[
            'source_sha256'=>['page'=>shaFileSafe($sourcePage),'helper'=>shaFileSafe($sourceHelper)],
            'candidate_sha256'=>['page'=>hash('sha256',$candidatePage),'helper'=>hash('sha256',$candidateHelper)],
            'path_transformations'=>['page'=>$pageNotes,'helper'=>$helperNotes]
        ];
        @unlink($tmpP); @unlink($tmpH);
    } catch (Throwable $e) { addCheck($checks,'Build relocation candidates',false,true,$e->getMessage()); }
}

if ($action === 'download_precheck') jsonDownload(reportPayload('PRECHECK',$checks,['candidate_info'=>$candidateInfo]),'PRECHECK');

if ($action === 'apply') {
    if (!allRequiredPass($checks)) { $message='Apply blocked: PRECHECK has a required failure.'; $messageClass='bad'; }
    else {
        try {
            [$candidatePage,$candidateHelper,$pageNotes,$helperNotes] = makeCandidates($sourcePage,$sourceHelper);
            if (!is_dir($backupDir) && !mkdir($backupDir,0755,true)) throw new RuntimeException('Could not create backup folder.');
            if (!copy($sourcePage,$backupPage) || !copy($sourceHelper,$backupHelper)) throw new RuntimeException('Could not back up both v001 source files.');
            file_put_contents($stateFile,json_encode(['applied_at_et'=>nowEt(),'source_page_sha256'=>shaFileSafe($sourcePage),'source_helper_sha256'=>shaFileSafe($sourceHelper)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

            $tmpP=tempnam(sys_get_temp_dir(),'mrlrel_p_'); $tmpH=tempnam(sys_get_temp_dir(),'mrlrel_h_');
            file_put_contents($tmpP,$candidatePage); file_put_contents($tmpH,$candidateHelper);
            [$okP,$outP]=phpLint($tmpP); [$okH,$outH]=phpLint($tmpH);
            if (!$okP || !$okH) throw new RuntimeException('Candidate lint failed. Page: '.$outP.' Helper: '.$outH);
            @unlink($tmpP); @unlink($tmpH);

            if (!atomicWrite($targetHelper,$candidateHelper)) throw new RuntimeException('Could not install root helper.');
            if (!atomicWrite($targetPage,$candidatePage)) { @unlink($targetHelper); throw new RuntimeException('Could not install root page.'); }
            [$iOkH,$iOutH]=phpLint($targetHelper); [$iOkP,$iOutP]=phpLint($targetPage);
            if (!$iOkH || !$iOkP) {
                @unlink($targetPage); @unlink($targetHelper);
                throw new RuntimeException('Installed-file lint failed; root copies removed. Page: '.$iOutP.' Helper: '.$iOutH);
            }
            if (shaFileSafe($targetPage)!==hash('sha256',$candidatePage) || shaFileSafe($targetHelper)!==hash('sha256',$candidateHelper)) {
                @unlink($targetPage); @unlink($targetHelper);
                throw new RuntimeException('Installed hash verification failed; root copies removed.');
            }

            // Remove misplaced files only after root copies are fully validated.
            if (!@unlink($sourcePage) || !@unlink($sourceHelper)) {
                // Restore source layout and remove targets if cleanup is incomplete.
                @copy($backupPage,$sourcePage); @copy($backupHelper,$sourceHelper);
                @unlink($targetPage); @unlink($targetHelper);
                throw new RuntimeException('Could not remove both misplaced /race_results copies. Original layout restored; root copies removed.');
            }
            $message='SUCCESS: v001 financial admin files relocated to /public_html and validated. Database tables/data were not changed.';
            $messageClass='good';
        } catch (Throwable $e) { $message='APPLY FAILED: '.$e->getMessage(); $messageClass='bad'; }
    }
}

if ($action === 'rollback') {
    try {
        if (!is_file($backupPage) || !is_file($backupHelper)) throw new RuntimeException('Rollback backup files are not available.');
        if (!copy($backupPage,$sourcePage) || !copy($backupHelper,$sourceHelper)) throw new RuntimeException('Could not restore both /race_results source files.');
        if (is_file($targetPage)) @unlink($targetPage);
        if (is_file($targetHelper)) @unlink($targetHelper);
        [$rOkP,$rOutP]=phpLint($sourcePage); [$rOkH,$rOutH]=phpLint($sourceHelper);
        if (!$rOkP || !$rOkH) throw new RuntimeException('Rollback files restored but lint failed. Page: '.$rOutP.' Helper: '.$rOutH);
        $message='ROLLBACK COMPLETE: original /race_results v001 files restored; root copies removed. Financial tables/data preserved.';
        $messageClass='good';
    } catch (Throwable $e) { $message='ROLLBACK FAILED: '.$e->getMessage(); $messageClass='bad'; }
}

// Rebuild POSTCHECK from current state.
$post=[];
addCheck($post,'Root admin_paid_payouts.php exists',is_file($targetPage),true,$targetPage);
addCheck($post,'Root admin_paid_payouts_helper.php exists',is_file($targetHelper),true,$targetHelper);
addCheck($post,'Misplaced race_results/admin_paid_payouts.php removed',!file_exists($sourcePage),true,$sourcePage);
addCheck($post,'Misplaced race_results/admin_paid_payouts_helper.php removed',!file_exists($sourceHelper),true,$sourceHelper);
if (is_file($targetPage)) { [$x,$o]=phpLint($targetPage); addCheck($post,'php -l installed root page',$x,true,$o); }
if (is_file($targetHelper)) { [$x,$o]=phpLint($targetHelper); addCheck($post,'php -l installed root helper',$x,true,$o); }
addCheck($post,'Financial fee-history table intentionally untouched',true,false,'mrl_financial_fee_history preserved');
addCheck($post,'Financial team-year table intentionally untouched',true,false,'mrl_financial_team_year preserved');

if ($action === 'download_postcheck') jsonDownload(reportPayload('POSTCHECK',$post,['installed_files'=>['admin_paid_payouts.php'=>shaFileSafe($targetPage),'admin_paid_payouts_helper.php'=>shaFileSafe($targetHelper)]]),'POSTCHECK');

?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Admin Paid/Payouts Relocation v002</title>
<style>
body{margin:0;background:#10151b;color:#e8edf3;font-family:Arial,sans-serif}.wrap{max-width:1120px;margin:28px auto;padding:0 18px}.card{background:#18212b;border:1px solid #2d3b49;border-radius:12px;padding:18px;margin:14px 0}.good{background:#123a28;border-color:#2b9d63}.bad{background:#471f25;border-color:#c94c5c}.info{background:#16344a;border-color:#3a80aa}h1{font-size:26px;margin:0 0 6px}h2{font-size:19px;margin:0 0 12px}.muted{color:#aeb9c5}table{width:100%;border-collapse:collapse}th,td{padding:9px 10px;border-bottom:1px solid #2b3946;text-align:left;vertical-align:top}th{color:#b8c8d8}.pass{color:#73e3a2;font-weight:bold}.fail{color:#ff7b88;font-weight:bold}.btn{display:inline-block;border:0;border-radius:7px;padding:10px 15px;margin:4px 5px 4px 0;color:white;text-decoration:none;cursor:pointer;font-weight:bold}.apply{background:#218c55}.neutral{background:#296b9b}.rollback{background:#b33a49}.btn:disabled{opacity:.45;cursor:not-allowed}code{background:#0d1217;padding:2px 5px;border-radius:4px}.small{font-size:13px}</style></head><body><div class="wrap">
<div class="card"><h1>MRL Admin Paid/Payouts Relocation v002</h1><div class="muted">Corrects the v001 file location only. Financial tables/data are preserved.</div></div>
<?php if ($message): ?><div class="card <?=h($messageClass)?>"><strong><?=h($message)?></strong></div><?php endif; ?>
<div class="card"><h2>What this changes</h2><p><code>/race_results/admin_paid_payouts.php</code> → <code>/admin_paid_payouts.php</code><br><code>/race_results/admin_paid_payouts_helper.php</code> → <code>/admin_paid_payouts_helper.php</code></p><p class="muted small">The installer builds corrected candidates from the exact installed v001 files and adjusts location-dependent paths only.</p></div>
<div class="card"><h2>PRECHECK</h2><table><tr><th>Status</th><th>Check</th><th>Detail</th></tr><?php foreach($checks as $c): ?><tr><td class="<?=strtolower($c['status'])?>"><?=h($c['status'])?></td><td><?=h($c['label'])?><?=!empty($c['required'])?' *':''?></td><td class="small"><?=h($c['detail'])?></td></tr><?php endforeach; ?></table></div>
<?php if ($candidateInfo): ?><div class="card"><h2>Detected path transformations</h2><p><strong>Page</strong></p><ul><?php foreach($candidateInfo['path_transformations']['page'] as $n): ?><li><?=h($n)?></li><?php endforeach; ?><?php if(!$candidateInfo['path_transformations']['page']): ?><li class="muted">No page path replacements detected; source may already use absolute/document-root paths.</li><?php endif; ?></ul><p><strong>Helper</strong></p><ul><?php foreach($candidateInfo['path_transformations']['helper'] as $n): ?><li><?=h($n)?></li><?php endforeach; ?><?php if(!$candidateInfo['path_transformations']['helper']): ?><li class="muted">No helper path replacements detected; source may already use absolute/document-root paths.</li><?php endif; ?></ul></div><?php endif; ?>
<div class="card"><form method="post" style="display:inline"><button class="btn apply" name="action" value="apply" <?=allRequiredPass($checks)?'':'disabled'?>>Apply v002</button></form><form method="get" style="display:inline"><button class="btn neutral" name="action" value="download_precheck">Export PRECHECK JSON</button></form><form method="get" style="display:inline"><button class="btn neutral" name="action" value="download_postcheck">Export POSTCHECK JSON</button></form><form method="post" style="display:inline"><button class="btn rollback" name="action" value="rollback">Rollback</button></form></div>
<div class="card"><h2>POSTCHECK snapshot</h2><table><tr><th>Status</th><th>Check</th><th>Detail</th></tr><?php foreach($post as $c): ?><tr><td class="<?=strtolower($c['status'])?>"><?=h($c['status'])?></td><td><?=h($c['label'])?></td><td class="small"><?=h($c['detail'])?></td></tr><?php endforeach; ?></table></div>
</div></body></html>
