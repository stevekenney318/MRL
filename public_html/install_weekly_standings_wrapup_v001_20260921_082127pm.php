<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');

const EXPECTED_SOURCE_VERSION = 'v075';
const TARGET_VERSION = 'v076';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\');
$target = $docRoot . '/race_results/weekly_standings.php';
$backupDir = $docRoot . '/_installer_backups/weekly_standings_wrapup_20260921_082127pm';
$backupFile = $backupDir . '/weekly_standings.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function lint_php_file(string $path): array {
    if (!function_exists('shell_exec')) return ['ok'=>false,'output'=>'shell_exec() is not available.'];
    $out = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');
    if ($out === null) return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    $out = trim((string)$out);
    return ['ok'=>stripos($out,'No syntax errors detected') !== false,'output'=>$out];
}

function current_version(string $content): string {
    if (preg_match('/\*\s*VERSION:\s*(v\d+)/i', $content, $m)) return (string)$m[1];
    return '';
}

function replace_once(string $subject, string $search, string $replace, string $label, array &$errors): string {
    $count = substr_count($subject, $search);
    if ($count !== 1) {
        $errors[] = $label . ': expected exactly 1 match, found ' . $count . '.';
        return $subject;
    }
    $pos = strpos($subject, $search);
    return substr($subject,0,$pos) . $replace . substr($subject,$pos + strlen($search));
}

function build_candidate(string $source, array &$errors): string {
    $out = $source;

    $out = replace_once($out, ' * VERSION: v075', ' * VERSION: v076', 'Version header', $errors);

    if (preg_match('/ \* LAST MODIFIED: .*? ET\R/', $out, $m)) {
        $out = str_replace($m[0], ' * LAST MODIFIED: 9/21/2026 8:21:27 pm ET' . PHP_EOL, $out);
    } else {
        $errors[] = 'LAST MODIFIED header not found.';
    }

    $anchor = " * CHANGELOG:\n *\n";
    $insert = " * CHANGELOG:\n *\n"
        . " * v076 (9/21/2026 8:21:27 pm ET)\n"
        . " *   - MOBILE: Adds viewport metadata so phones use the real device width and the existing <=760px one-column layout actually activates.\n"
        . " *   - MOBILE: Raises narrow-screen body/table text to 15px and control text to 13px for easier reading.\n"
        . " *   - NAV: Wraps ◀ / ▶ race controls as a 4px-gap pair, matching Team Chart spacing while preserving surrounding control spacing.\n"
        . " *   - XLSX: Rank/week columns are now stored as real numeric cells, removing Excel's green 'number stored as text' indicators.\n"
        . " *   - XLSX: Weekly Winners rows now carry the same S1 blue / S2 tan / S3 peach / S4 green segment fills as the web report.\n"
        . " *   - PRESERVE: Scoring, snapshots, validation, audit, release history, themes, print/PDF, and all report data unchanged.\n"
        . " *\n";
    $out = replace_once($out, $anchor, $insert, 'Changelog anchor', $errors);

    $out = replace_once(
        $out,
        '<meta charset="UTF-8">',
        '<meta charset="UTF-8">' . "\n" . '    <meta name="viewport" content="width=device-width, initial-scale=1">',
        'Viewport meta',
        $errors
    );

    $navOld = <<<'HTML'
            <button type="button" class="nav-button" id="navPrevBtn" onclick="navigateRace(-1)" title="Previous Race" aria-label="Previous Race">◀</button>
            <button type="button" class="nav-button" id="navNextBtn" onclick="navigateRace(1)" title="Next Race" aria-label="Next Race">▶</button>
HTML;

    $navNew = <<<'HTML'
            <span class="nav-pair">
                <button type="button" class="nav-button" id="navPrevBtn" onclick="navigateRace(-1)" title="Previous Race" aria-label="Previous Race">◀</button>
                <button type="button" class="nav-button" id="navNextBtn" onclick="navigateRace(1)" title="Next Race" aria-label="Next Race">▶</button>
            </span>
HTML;

    $out = replace_once($out, $navOld, $navNew, 'Race nav pair wrapper', $errors);

    $navCssAnchor = "        #navNextBtn {\n            border-radius: 3px 14px 14px 3px;\n        }\n";
    $navCssInsert = $navCssAnchor
        . "\n        .nav-pair {\n"
        . "            display: inline-flex;\n"
        . "            align-items: center;\n"
        . "            gap: 4px;\n"
        . "        }\n";
    $out = replace_once($out, $navCssAnchor, $navCssInsert, 'Nav pair CSS', $errors);

    $out = replace_once(
        $out,
        "            body {\n                margin: 8px;\n                font-size: 14px;\n            }",
        "            body {\n                margin: 8px;\n                font-size: 15px;\n            }",
        'Mobile body font',
        $errors
    );

    $out = replace_once(
        $out,
        "            .top-controls select,\n            .top-controls button {\n                font-size: 12px;\n                padding: 2px 6px;\n            }",
        "            .top-controls select,\n            .top-controls button {\n                font-size: 13px;\n                padding: 2px 6px;\n            }",
        'Mobile control font',
        $errors
    );

    $out = replace_once(
        $out,
        "            table {\n                font-size: 14px;\n            }",
        "            table {\n                font-size: 15px;\n            }",
        'Mobile table font',
        $errors
    );

    $out = replace_once(
        $out,
        '$isNumeric = ($c === 2 && is_numeric($value));',
        '$isNumeric = (($c === 0 || $c === 2) && is_numeric($value));',
        'XLSX numeric rank/week columns',
        $errors
    );

    $loopAnchor = <<<'PHPBLOCK'
                $values = $dataRow['values'] ?? [];
                $isEvenStripe = (($rowNum - 3) % 2 === 1);
                for ($c = 0; $c < 3; $c++) {
PHPBLOCK;

    $loopInsert = <<<'PHPBLOCK'
                $values = $dataRow['values'] ?? [];
                $isEvenStripe = (($rowNum - 3) % 2 === 1);

                $winnerSegment = '';
                if ($idx === 3 && isset($values[0]) && is_numeric($values[0])) {
                    $winnerWeek = (int)$values[0];
                    if ($winnerWeek >= 1 && $winnerWeek <= 8) {
                        $winnerSegment = 'S1';
                    } elseif ($winnerWeek <= 17) {
                        $winnerSegment = 'S2';
                    } elseif ($winnerWeek <= 26) {
                        $winnerSegment = 'S3';
                    } else {
                        $winnerSegment = 'S4';
                    }
                }

                for ($c = 0; $c < 3; $c++) {
PHPBLOCK;

    $out = replace_once($out, $loopAnchor, $loopInsert, 'Winner segment detection', $errors);

    $styleOld = <<<'PHPBLOCK'
                    if ($isTeamColumn) {
                        $styleIndex = $isEvenStripe ? ($boldThisCell ? 11 : 9) : ($boldThisCell ? 10 : 8);
                    } else {
                        $styleIndex = $isEvenStripe ? ($boldThisCell ? 7 : 4) : ($boldThisCell ? 6 : 3);
                    }
PHPBLOCK;

    $styleNew = <<<'PHPBLOCK'
                    if ($idx === 3 && $winnerSegment !== '') {
                        $winnerStyles = [
                            'S1' => ['center' => 12, 'centerBold' => 13, 'left' => 14],
                            'S2' => ['center' => 15, 'centerBold' => 16, 'left' => 17],
                            'S3' => ['center' => 18, 'centerBold' => 19, 'left' => 20],
                            'S4' => ['center' => 21, 'centerBold' => 22, 'left' => 23],
                        ];

                        if ($isTeamColumn) {
                            $styleIndex = $winnerStyles[$winnerSegment]['left'];
                        } else {
                            $styleIndex = $boldThisCell
                                ? $winnerStyles[$winnerSegment]['centerBold']
                                : $winnerStyles[$winnerSegment]['center'];
                        }
                    } elseif ($isTeamColumn) {
                        $styleIndex = $isEvenStripe ? ($boldThisCell ? 11 : 9) : ($boldThisCell ? 10 : 8);
                    } else {
                        $styleIndex = $isEvenStripe ? ($boldThisCell ? 7 : 4) : ($boldThisCell ? 6 : 3);
                    }
PHPBLOCK;

    $out = replace_once($out, $styleOld, $styleNew, 'Winner segment XLSX styles', $errors);

    $fillsOld = <<<'XML'
        . '<fills count="5">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFBFF00"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFD2E5F7"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
XML;

    $fillsNew = <<<'XML'
        . '<fills count="9">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFBFF00"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFD2E5F7"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFC5D9F1"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFC4BD97"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFCD5B4"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFC4D79B"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
XML;

    $out = replace_once($out, $fillsOld, $fillsNew, 'XLSX segment fills', $errors);

    $out = replace_once(
        $out,
        ". '<cellXfs count=\"12\">'",
        ". '<cellXfs count=\"24\">'",
        'XLSX style count',
        $errors
    );

    $xfsAnchor = <<<'XML'
        . '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        . '</cellXfs>'
XML;

    $xfsInsert = <<<'XML'
        . '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'

        . '<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'

        . '<xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'

        . '<xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'

        . '<xf numFmtId="0" fontId="0" fillId="8" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="8" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
        . '</cellXfs>'
XML;

    $out = replace_once($out, $xfsAnchor, $xfsInsert, 'XLSX segment cell styles', $errors);

    return $out;
}

function candidate_lint(string $candidate, string $dir): array {
    $tmp = @tempnam($dir, '.mrl_ws076_');
    if ($tmp === false) $tmp = @tempnam(sys_get_temp_dir(), 'mrl_ws076_');
    if ($tmp === false) return ['ok'=>false,'output'=>'Could not create temporary lint file.'];
    if (@file_put_contents($tmp,$candidate,LOCK_EX) === false) {
        @unlink($tmp);
        return ['ok'=>false,'output'=>'Could not write temporary lint file.'];
    }
    $r = lint_php_file($tmp);
    @unlink($tmp);
    return $r;
}

$action=(string)($_POST['action']??'');
$message='';$messageClass='info';
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
    if(!$canApply){
        $message='Apply blocked: preflight is not fully PASS.';$messageClass='bad';
    }else{
        if(!is_dir($backupDir)&&!@mkdir($backupDir,0755,true)&&!is_dir($backupDir)){
            $message='Apply blocked: backup directory could not be created.';$messageClass='bad';
        }elseif(is_file($backupFile)){
            $message='Apply blocked: backup already exists.';$messageClass='bad';
        }elseif(!@copy($target,$backupFile)){
            $message='Apply blocked: backup could not be created.';$messageClass='bad';
        }else{
            $tmp=$target.'.mrl_tmp_'.uniqid('',true);
            @file_put_contents($tmp,$candidate,LOCK_EX);@chmod($tmp,0644);
            $l=lint_php_file($tmp);

            if(empty($l['ok'])){
                @unlink($tmp);$message='Apply blocked: temporary file failed lint.';$messageClass='bad';
            }elseif(!@rename($tmp,$target)){
                @unlink($tmp);$message='Apply failed: replacement could not be completed.';$messageClass='bad';
            }else{
                @chmod($target,0644);$l2=lint_php_file($target);
                if(empty($l2['ok'])){
                    @copy($backupFile,$target);@chmod($target,0644);
                    $message='Installed file failed lint and backup was restored.';$messageClass='bad';
                }else{
                    $message='PASS — weekly_standings.php v076 installed and passed PHP lint.';$messageClass='good';
                }
            }
        }
    }
}

if($action==='rollback'&&is_file($backupFile)){
    @copy($backupFile,$target);@chmod($target,0644);
    $message='Rollback complete — weekly_standings.php v075 restored.';$messageClass='good';
}

$source=is_file($target)?(string)@file_get_contents($target):'';$version=$source!==''?current_version($source):'';$alreadyInstalled=($version===TARGET_VERSION);$backupExists=is_file($backupFile);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Weekly Standings Wrap-up</title>
<style>
:root{color-scheme:dark}
body{margin:0;padding:22px;background:#101010;color:#eee;font-family:Arial,sans-serif}
.wrap{max-width:1100px;margin:auto}h1{color:#f2c98e}
.card{margin:14px 0;padding:16px;background:#1b1b1b;border:1px solid #3d3d3d;border-radius:14px}
table{width:100%;border-collapse:collapse}th,td{padding:9px;border-bottom:1px solid #333;text-align:left}th{color:#f2c98e}
.pass{color:#66df8d;font-weight:800}.fail{color:#ff7474;font-weight:800}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}
.apply{background:#248c4b}.neutral{background:#276fca}.rollback{background:#a83434}button:disabled{opacity:.4}
</style>
</head>
<body><div class="wrap">
<h1>MRL Weekly Standings Wrap-up</h1>
<div>Installer v001 · generated 9/21/2026 8:21:27 pm ET</div>

<?php if($message!==''):?><div class="card"><?php echo h($message);?></div><?php endif;?>

<div class="card">
<h2>What this finishes</h2>
<ul>
<li>Adds the missing mobile viewport so phone layout uses the existing one-column breakpoint.</li>
<li>Raises mobile body/table text to 15px and mobile controls to 13px.</li>
<li>Makes the race ◀ / ▶ buttons a 4px-gap pair like Team Chart.</li>
<li>Writes rank/week columns as real numbers in Excel.</li>
<li>Adds S1/S2/S3/S4 row colors to Weekly Winners in Excel.</li>
</ul>
</div>

<div class="card">
<h2>Preflight</h2>
<table>
<tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<tr><td>Version</td><td class="<?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'pass':'fail';?>"><?php echo ($version===EXPECTED_SOURCE_VERSION||$alreadyInstalled)?'PASS':'FAIL';?></td><td><?php echo h($version);?> → v076</td></tr>
<tr><td>Patch signatures</td><td class="<?php echo ($alreadyInstalled||empty($errors))?'pass':'fail';?>"><?php echo ($alreadyInstalled||empty($errors))?'PASS':'FAIL';?></td><td><?php echo h($alreadyInstalled?'Already installed.':(empty($errors)?'All expected v075 anchors found exactly once.':implode(' | ',$errors)));?></td></tr>
<tr><td>Candidate lint</td><td class="<?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'pass':'fail';?>"><?php echo ($alreadyInstalled||!empty($candidateLint['ok']))?'PASS':'FAIL';?></td><td><?php echo h($alreadyInstalled?'Not needed.':$candidateLint['output']);?></td></tr>
</table>
<p>
<form method="post" style="display:inline"><input type="hidden" name="action" value="apply"><button class="apply" <?php echo $canApply?'':'disabled';?>>Apply v076</button></form>
<a class="btn neutral" href="<?php echo h(basename($_SERVER['PHP_SELF']??''));?>">Refresh / Preflight</a>
<form method="post" style="display:inline"><input type="hidden" name="action" value="rollback"><button class="rollback" <?php echo $backupExists?'':'disabled';?>>Rollback</button></form>
</p>
</div></div></body></html>
