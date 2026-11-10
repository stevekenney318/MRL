<?php
declare(strict_types=1);

/**
 * payouts.php
 *
 * VERSION: v008
 * LAST MODIFIED: 11/10/2026 1:53:00 am ET
 *
 * PURPOSE:
 * - Public MRL payout page built from the same race-result snapshots and scoring rules as Weekly Standings.
 * - Shows the complete planned payout chart all season, with winners filling in as results become available.
 * - Supports per-year public/hidden and official/unofficial state controlled by admin_payouts.php.
 * - Includes public Team Payouts views plus Main Chart and Everything Excel/PDF/Print export options.
 *
 * CHANGELOG:
 * v008 (11/10/2026 1:53:00 am ET)
 * - LAYOUT: Treats the year/Weekly Standings controls, UNOFFICIAL badge, and export actions as one top-aligned row.
 * - LAYOUT: Uses the export block's current vertical position as the shared reference so all three groups begin on the same line.
 * - PRESERVE: v007 status behavior, conditional Team Payouts note, export filenames, payout calculations, winners, ties, colors, widths, admin/config/scoring/data sources unchanged.
 *
 * v007 (11/10/2026 1:15:00 am ET)
 * - STATUS: When a payout year is marked official, the UNOFFICIAL notice disappears; no OFFICIAL replacement badge is shown.
 * - TEAM PAYOUTS: The explanatory future-awards note is shown only while at least one planned payout row still has no winner.
 * - EXPORT: Main Chart and Everything Excel filenames use the standard timestamp form YYYYMMDD_hhmmssam/pm.
 * - EXPORT: Print/PDF views use timestamped document titles so Save as PDF receives a timestamped suggested filename.
 * - LAYOUT: Drops the export button block slightly for cleaner alignment with the year/Weekly Standings/status row.
 * - PRESERVE: Payout calculations, winners, tie allocation, colors, widths, year visibility, admin/config/scoring/data sources unchanged.
 *
 * v006 (11/5/2026 4:00:00 pm ET)
 * - LAYOUT: Moves OFFICIAL/UNOFFICIAL into the same top control row as the year, Weekly Standings, and export buttons.
 * - LABELS: Enforces High Point Total R## for all weekly payout labels, including tie-suffixed labels such as High Point Total R13 (Tie).
 * - TOTALS: Team Payout grand-total amount now shows current / planned, for example $530.00 / $750.00.
 * - PRESERVE: v005 exports, Team Payout views, calculations, tie allocation, colors, widths, year visibility, admin/config/scoring/data sources unchanged.
 *
 * v005 (11/5/2026 3:40:00 pm ET)
 * - STATUS: Replaces AUTO-SCORING – UNOFFICIAL with a larger centered UNOFFICIAL banner (OFFICIAL remains centered when enabled).
 * - LABELS: Weekly payout prize labels now use the MRL race-number form: High Point Total R1, R2, ... R36.
 * - TOTALS: Team Payout grand totals now read Grand Total — Through R##, based on the latest populated High Point Total race.
 * - EXPORT: Adds Excel — Everything with four sheets: Main Chart, By Team, By Payout, and Team + Prize.
 * - EXPORT: Adds PDF / Print — Everything for the full public payout page.
 * - LAYOUT: Export actions use a compact two-row grid on the right; Weekly Standings remains public and linked.
 * - PRESERVE: Payout calculations, tie allocation, v003 tie divider, colors, widths, year visibility, config, admin, scoring, database, cron/scheduler, email, and picks unchanged.
 *
 * v004 (11/5/2026 2:30:00 pm ET)
 * - PUBLIC: Replaces the old Payout Tally with Team Payouts.
 * - PUBLIC: Adds Team & Team Owner sorted by Team.
 * - PUBLIC: Adds Team & Team Owner sorted by Payout.
 * - PUBLIC: Adds Grouped by Team and Prize, sorted by Team, showing every known prize that builds each team total.
 * - EXPORT: Adds a WYSIWYG-style Main Chart Excel (.xlsx) export using the same row order and payout colors.
 * - EXPORT: Adds a Main Chart PDF/Print view that opens the browser print dialog for Save as PDF or printing.
 * - PRESERVE: v003 main chart, tie divider, payout calculations, tie money allocation, year visibility, admin settings, scoring, and data sources unchanged.
 *
 * v003 (10/29/2026 3:03:19 am ET)
 * - PRESENTATION FIX: Tie dividers now remove both adjoining solid cell borders before drawing the long-dash/short-dash separator, so the patterned divider is the only seam visible between tied rows.
 * - PRESERVE: v002 colors, widths, column proportions, payout calculations, tie money allocation, tally/export, year visibility, admin settings, scoring, and data sources unchanged.
 *
 * v002 (10/29/2026 2:54:04 am ET)
 * - PRESENTATION: Segment/The Chase payout-total rows now use the same green treatment as Yearly Point Total rows.
 * - PRESENTATION: Weekly High Point rows retain the Weekly Standings S1/S2/S3/S4 colors.
 * - PRESENTATION: Desktop report width and column proportions are tightened to more closely match the payout spreadsheet.
 * - PRESENTATION: Consecutive tie rows now use a visible long-dash/short-dash separator between tied entries.
 * - PRESERVE: Payout calculations, tie money allocation, tally/export, year visibility, admin settings, scoring, and data sources unchanged.
 */

ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('America/New_York');

require_once $_SERVER['DOCUMENT_ROOT'].'/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config_mrl.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/mrl_team/mrl_theme_helper.php';
require_once __DIR__.'/payouts_helper.php';

$theme='dark';
try { $uid=(int)($_SESSION['userSession']??0); if(isset($dbo)&&$dbo instanceof PDO&&$uid>0)$theme=mrl_theme_get($dbo,$uid); } catch(Throwable $e){$theme='dark';}

$config=mrlp_load_config();$allYears=mrlp_available_years(__DIR__);$isAdminPreview=false;
if(!empty($_GET['preview']) && function_exists('isAdmin')){ $isAdminPreview=isAdmin((int)($_SESSION['userSession']??0)); }

$publicYears=[];
foreach($allYears as $y){$yc=mrlp_year_config($config,$y);if(!empty($yc['public']))$publicYears[]=$y;}
$requested=preg_replace('/[^0-9]/','',(string)($_GET['year']??''));
if($requested==='')$requested=!empty($publicYears)?$publicYears[0]:(!empty($allYears)?$allYears[0]:date('Y'));
if(!in_array($requested,$allYears,true))$requested=!empty($publicYears)?$publicYears[0]:(!empty($allYears)?$allYears[0]:date('Y'));
$yearCfg=mrlp_year_config($config,$requested);
$allowed=!empty($yearCfg['public'])||$isAdminPreview;
if(!$allowed){http_response_code(404);echo '<!doctype html><html><body style="font:18px Arial;padding:30px">Payouts for '.mrlp_h($requested).' are not public yet.</body></html>';exit;}

$score=mrlp_compute_year($requested,$dbo??null,$dbconnect??null);$model=mrlp_build_payout_model($score,$yearCfg);

function pp_money($v):string{return '$'.number_format((float)$v,2);}
function pp_progress($current,$planned):string{return pp_money($current).' / '.pp_money($planned);}

function pp_display_prize(string $prize): string
{
    $out=preg_replace('/^High Point Total\s+R?(\d+)(.*)$/i','High Point Total R$1$2',$prize);
    return is_string($out)?$out:$prize;
}

function pp_through_race(array $model): int
{
    $max=0;
    foreach(['S1','S2','S3','S4'] as $seg){
        foreach(($model['sections'][$seg]??[]) as $row){
            if(!empty($row['future']))continue;
            if(trim((string)($row['winner']??''))==='')continue;
            $m=[];
            if(preg_match('/^High Point Total\s+R?(\d+)(?:.*)$/i',(string)($row['prize']??''),$m)){
                $n=(int)($m[1]??0);if($n>$max)$max=$n;
            }
        }
    }
    return $max;
}

function pp_has_unresolved_payouts(array $model): bool
{
    foreach(['S1','S2','S3','S4'] as $seg){
        foreach(($model['sections'][$seg]??[]) as $row){
            if(trim((string)($row['winner']??''))==='')return true;
        }
    }
    foreach(($model['yearly']??[]) as $row){
        if(trim((string)($row['winner']??''))==='')return true;
    }
    return false;
}

function pp_team_payouts(array $model): array
{
    $byTeam=[];$rows=[];
    foreach(['S1','S2','S3','S4'] as $seg){
        foreach(($model['sections'][$seg]??[]) as $row){
            if(!empty($row['future']))continue;
            $team=trim((string)($row['winner']??''));if($team==='')continue;
            $owner=trim((string)($row['owner']??''));
            $cents=mrlp_money_cents((float)($row['amount']??0));
            if($cents<=0)continue;
            $rows[]=['team'=>$team,'owner'=>$owner,'prize'=>pp_display_prize((string)($row['prize']??'')),'amount'=>mrlp_cents_money($cents),'cents'=>$cents];
        }
    }
    foreach(($model['yearly']??[]) as $row){
        if(!empty($row['future']))continue;
        $team=trim((string)($row['winner']??''));if($team==='')continue;
        $owner=trim((string)($row['owner']??''));
        $cents=mrlp_money_cents((float)($row['amount']??0));
        if($cents<=0)continue;
        $rows[]=['team'=>$team,'owner'=>$owner,'prize'=>pp_display_prize((string)($row['prize']??'')),'amount'=>mrlp_cents_money($cents),'cents'=>$cents];
    }
    foreach($rows as $row){
        $key=strtolower($row['team']);
        if(!isset($byTeam[$key]))$byTeam[$key]=['team'=>$row['team'],'owner'=>$row['owner'],'cents'=>0,'payout'=>0.0,'prizes'=>[]];
        if($byTeam[$key]['owner']===''&&$row['owner']!=='')$byTeam[$key]['owner']=$row['owner'];
        $byTeam[$key]['cents']+=(int)$row['cents'];
        $byTeam[$key]['payout']=mrlp_cents_money((int)$byTeam[$key]['cents']);
        $byTeam[$key]['prizes'][]=['prize'=>$row['prize'],'amount'=>$row['amount']];
    }
    $teamRows=array_values($byTeam);
    usort($teamRows,function($a,$b){return strnatcasecmp((string)$a['team'],(string)$b['team']);});
    $payoutRows=$teamRows;
    usort($payoutRows,function($a,$b){
        $cmp=((int)$b['cents'])<=>((int)$a['cents']);
        return $cmp!==0?$cmp:strnatcasecmp((string)$a['team'],(string)$b['team']);
    });
    return ['by_team'=>$teamRows,'by_payout'=>$payoutRows];
}

function pp_xlsx_cell(string $ref,string $value,int $style=0): string
{
    return '<c r="'.$ref.'" t="inlineStr" s="'.$style.'"><is><t>'.mrlp_xlsx_xml($value).'</t></is></c>';
}

function pp_chart_xlsx_styles(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        .'<fonts count="4">'
        .'<font><sz val="11"/><name val="Arial"/></font>'
        .'<font><b/><sz val="16"/><name val="Arial"/></font>'
        .'<font><b/><sz val="11"/><name val="Arial"/></font>'
        .'<font><b/><sz val="11"/><name val="Arial"/><color rgb="FFFFFFFF"/></font>'
        .'</fonts>'
        .'<fills count="9">'
        .'<fill><patternFill patternType="none"/></fill>'
        .'<fill><patternFill patternType="gray125"/></fill>'
        .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFFF00"/><bgColor indexed="64"/></patternFill></fill>'
        .'<fill><patternFill patternType="solid"><fgColor rgb="FFC5D9F1"/><bgColor indexed="64"/></patternFill></fill>'
        .'<fill><patternFill patternType="solid"><fgColor rgb="FFC4BD97"/><bgColor indexed="64"/></patternFill></fill>'
        .'<fill><patternFill patternType="solid"><fgColor rgb="FFFCD5B4"/><bgColor indexed="64"/></patternFill></fill>'
        .'<fill><patternFill patternType="solid"><fgColor rgb="FFC4D79B"/><bgColor indexed="64"/></patternFill></fill>'
        .'<fill><patternFill patternType="solid"><fgColor rgb="FF92D050"/><bgColor indexed="64"/></patternFill></fill>'
        .'<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>'
        .'</fills>'
        .'<borders count="3">'
        .'<border/>'
        .'<border><left style="thin"><color rgb="FF333333"/></left><right style="thin"><color rgb="FF333333"/></right><top style="thin"><color rgb="FF333333"/></top><bottom style="thin"><color rgb="FF333333"/></bottom></border>'
        .'<border><left style="thin"><color rgb="FF333333"/></left><right style="thin"><color rgb="FF333333"/></right><top style="dashed"><color rgb="FF555555"/></top><bottom style="thin"><color rgb="FF333333"/></bottom></border>'
        .'</borders>'
        .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        .'<cellXfs count="17">'
        .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
        .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyAlignment="1"><alignment horizontal="center"/></xf>'
        .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" applyAlignment="1"><alignment horizontal="center"/></xf>'
        .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
        .'<xf numFmtId="0" fontId="0" fillId="3" borderId="1"/>'
        .'<xf numFmtId="0" fontId="0" fillId="4" borderId="1"/>'
        .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1"/>'
        .'<xf numFmtId="0" fontId="0" fillId="6" borderId="1"/>'
        .'<xf numFmtId="0" fontId="0" fillId="7" borderId="1"/>'
        .'<xf numFmtId="0" fontId="3" fillId="8" borderId="1"/>'
        .'<xf numFmtId="0" fontId="0" fillId="3" borderId="2"/>'
        .'<xf numFmtId="0" fontId="0" fillId="4" borderId="2"/>'
        .'<xf numFmtId="0" fontId="0" fillId="5" borderId="2"/>'
        .'<xf numFmtId="0" fontId="0" fillId="6" borderId="2"/>'
        .'<xf numFmtId="0" fontId="0" fillId="7" borderId="2"/>'
        .'<xf numFmtId="0" fontId="0" fillId="7" borderId="2"/>'
        .'<xf numFmtId="0" fontId="0" fillId="7" borderId="1"/>'
        .'</cellXfs>'
        .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        .'</styleSheet>';
}

function pp_send_main_chart_xlsx(string $year,array $model,array $yearCfg): void
{
    $rows=[];$r=1;
    $rows[]='<row r="'.$r.'" ht="26" customHeight="1">'.pp_xlsx_cell('A'.$r,$year.' MRL Payouts',1).'</row>';$r++;
    $status=!empty($yearCfg['official'])?'':'UNOFFICIAL';
    $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,$status,2).'</row>';$r++;
    foreach(['S1','S2','S3','S4'] as $seg){
        $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'Prize',3).pp_xlsx_cell('B'.$r,'Winner',3).pp_xlsx_cell('C'.$r,'Team Owner',3).pp_xlsx_cell('D'.$r,'Amount',3).'</row>';$r++;
        $lastPrize='';
        foreach(($model['sections'][$seg]??[]) as $row){
            $tieFollow=!empty($row['tie'])&&$lastPrize===(string)($row['prize']??'');
            if(($row['kind']??'')==='segment')$style=$tieFollow?15:8;
            else{
                $base=['S1'=>4,'S2'=>5,'S3'=>6,'S4'=>7][$seg];
                $dash=['S1'=>10,'S2'=>11,'S3'=>12,'S4'=>13][$seg];
                $style=$tieFollow?$dash:$base;
            }
            $rows[]='<row r="'.$r.'">'
                .pp_xlsx_cell('A'.$r,pp_display_prize((string)($row['prize']??'')),$style)
                .pp_xlsx_cell('B'.$r,(string)($row['winner']??''),$style)
                .pp_xlsx_cell('C'.$r,(string)($row['owner']??''),$style)
                .pp_xlsx_cell('D'.$r,pp_money($row['amount']??0),$style)
                .'</row>';
            $lastPrize=(string)($row['prize']??'');$r++;
        }
    }
    $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'Prize',3).pp_xlsx_cell('B'.$r,'Winner',3).pp_xlsx_cell('C'.$r,'Team Owner',3).pp_xlsx_cell('D'.$r,'Amount',3).'</row>';$r++;
    $lastPrize='';
    foreach(($model['yearly']??[]) as $row){
        $tieFollow=!empty($row['tie'])&&$lastPrize===(string)($row['prize']??'');
        $style=$tieFollow?15:8;
        $rows[]='<row r="'.$r.'">'
            .pp_xlsx_cell('A'.$r,pp_display_prize((string)($row['prize']??'')),$style)
            .pp_xlsx_cell('B'.$r,(string)($row['winner']??''),$style)
            .pp_xlsx_cell('C'.$r,(string)($row['owner']??''),$style)
            .pp_xlsx_cell('D'.$r,pp_money($row['amount']??0),$style)
            .'</row>';
        $lastPrize=(string)($row['prize']??'');$r++;
    }
    $rows[]='<row r="'.$r.'">'
        .pp_xlsx_cell('A'.$r,'Planned Season Payout Total',9)
        .pp_xlsx_cell('B'.$r,'',9).pp_xlsx_cell('C'.$r,'',9)
        .pp_xlsx_cell('D'.$r,pp_money($model['planned_total']??0),9).'</row>';

    $sheet='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        .'<cols><col min="1" max="1" width="34" customWidth="1"/><col min="2" max="2" width="23" customWidth="1"/><col min="3" max="3" width="26" customWidth="1"/><col min="4" max="4" width="13" customWidth="1"/></cols>'
        .'<sheetData>'.implode('',$rows).'</sheetData>'
        .'<mergeCells count="2"><mergeCell ref="A1:D1"/><mergeCell ref="A2:D2"/></mergeCells>'
        .'</worksheet>';
    $files=[
        '[Content_Types].xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
        '_rels/.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Payouts" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml'=>$sheet,
        'xl/styles.xml'=>pp_chart_xlsx_styles(),
    ];
    $filename='MRL_Payouts_'.$year.'_Main_Chart_'.date('Ymd_hisa').'.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: no-store');
    echo mrlp_zip_strings($files);exit;
}


function pp_xlsx_sheet(array $rows,array $merges=[],string $cols='<col min="1" max="1" width="34" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/><col min="3" max="3" width="28" customWidth="1"/><col min="4" max="4" width="14" customWidth="1"/>'): string
{
    $mergeXml='';
    if(!empty($merges)){
        $parts=[];foreach($merges as $ref)$parts[]='<mergeCell ref="'.mrlp_xlsx_xml((string)$ref).'"/>';
        $mergeXml='<mergeCells count="'.count($parts).'">'.implode('',$parts).'</mergeCells>';
    }
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols>'.$cols.'</cols><sheetData>'.implode('',$rows).'</sheetData>'.$mergeXml.'</worksheet>';
}

function pp_send_everything_xlsx(string $year,array $model,array $yearCfg,array $teamPayouts,int $throughRace): void
{
    $sheets=[];

    // Sheet 1: Main Chart
    $rows=[];$r=1;
    $rows[]='<row r="'.$r.'" ht="26" customHeight="1">'.pp_xlsx_cell('A'.$r,$year.' MRL Payouts',1).'</row>';$r++;
    $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,!empty($yearCfg['official'])?'':'UNOFFICIAL',2).'</row>';$r++;
    foreach(['S1','S2','S3','S4'] as $seg){
        $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'Prize',3).pp_xlsx_cell('B'.$r,'Winner',3).pp_xlsx_cell('C'.$r,'Team Owner',3).pp_xlsx_cell('D'.$r,'Amount',3).'</row>';$r++;
        $lastPrize='';
        foreach(($model['sections'][$seg]??[]) as $row){
            $tieFollow=!empty($row['tie'])&&$lastPrize===(string)($row['prize']??'');
            if(($row['kind']??'')==='segment')$style=$tieFollow?15:8;
            else{$base=['S1'=>4,'S2'=>5,'S3'=>6,'S4'=>7][$seg];$dash=['S1'=>10,'S2'=>11,'S3'=>12,'S4'=>13][$seg];$style=$tieFollow?$dash:$base;}
            $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,pp_display_prize((string)($row['prize']??'')),$style).pp_xlsx_cell('B'.$r,(string)($row['winner']??''),$style).pp_xlsx_cell('C'.$r,(string)($row['owner']??''),$style).pp_xlsx_cell('D'.$r,pp_money($row['amount']??0),$style).'</row>';
            $lastPrize=(string)($row['prize']??'');$r++;
        }
    }
    $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'Prize',3).pp_xlsx_cell('B'.$r,'Winner',3).pp_xlsx_cell('C'.$r,'Team Owner',3).pp_xlsx_cell('D'.$r,'Amount',3).'</row>';$r++;
    $lastPrize='';foreach(($model['yearly']??[]) as $row){$tieFollow=!empty($row['tie'])&&$lastPrize===(string)($row['prize']??'');$style=$tieFollow?15:8;$rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,pp_display_prize((string)($row['prize']??'')),$style).pp_xlsx_cell('B'.$r,(string)($row['winner']??''),$style).pp_xlsx_cell('C'.$r,(string)($row['owner']??''),$style).pp_xlsx_cell('D'.$r,pp_money($row['amount']??0),$style).'</row>';$lastPrize=(string)($row['prize']??'');$r++;}
    $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'Planned Season Payout Total',9).pp_xlsx_cell('B'.$r,'',9).pp_xlsx_cell('C'.$r,'',9).pp_xlsx_cell('D'.$r,pp_money($model['planned_total']??0),9).'</row>';
    $sheets[]=['name'=>'Main Chart','xml'=>pp_xlsx_sheet($rows,['A1:D1','A2:D2'])];

    // Sheet 2: By Team
    $rows=[];$r=1;$rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'Team',9).pp_xlsx_cell('B'.$r,'Team Owner',9).pp_xlsx_cell('C'.$r,'Payout',9).'</row>';$r++;
    foreach($teamPayouts['by_team'] as $tr){$rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,(string)$tr['team'],4).pp_xlsx_cell('B'.$r,(string)$tr['owner'],4).pp_xlsx_cell('C'.$r,pp_money($tr['payout']),4).'</row>';$r++;}
    $label=$throughRace>0?'Grand Total — Through R'.$throughRace:'Grand Total';$rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,$label,9).pp_xlsx_cell('B'.$r,'',9).pp_xlsx_cell('C'.$r,pp_progress($model['awarded_total']??0,$model['planned_total']??0),9).'</row>';
    $sheets[]=['name'=>'By Team','xml'=>pp_xlsx_sheet($rows,[],'<col min="1" max="1" width="28" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/><col min="3" max="3" width="14" customWidth="1"/>')];

    // Sheet 3: By Payout
    $rows=[];$r=1;$rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'Team',9).pp_xlsx_cell('B'.$r,'Team Owner',9).pp_xlsx_cell('C'.$r,'Payout',9).'</row>';$r++;
    foreach($teamPayouts['by_payout'] as $tr){$rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,(string)$tr['team'],4).pp_xlsx_cell('B'.$r,(string)$tr['owner'],4).pp_xlsx_cell('C'.$r,pp_money($tr['payout']),4).'</row>';$r++;}
    $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,$label,9).pp_xlsx_cell('B'.$r,'',9).pp_xlsx_cell('C'.$r,pp_progress($model['awarded_total']??0,$model['planned_total']??0),9).'</row>';
    $sheets[]=['name'=>'By Payout','xml'=>pp_xlsx_sheet($rows,[],'<col min="1" max="1" width="28" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/><col min="3" max="3" width="14" customWidth="1"/>')];

    // Sheet 4: Team + Prize
    $rows=[];$r=1;$rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'Team / Prize',9).pp_xlsx_cell('B'.$r,'Team Owner',9).pp_xlsx_cell('C'.$r,'Payout',9).'</row>';$r++;
    foreach($teamPayouts['by_team'] as $tr){
        $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,(string)$tr['team'],9).pp_xlsx_cell('B'.$r,(string)$tr['owner'],9).pp_xlsx_cell('C'.$r,pp_money($tr['payout']),9).'</row>';$r++;
        foreach($tr['prizes'] as $pr){$rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,'  '.(string)$pr['prize'],4).pp_xlsx_cell('B'.$r,'',4).pp_xlsx_cell('C'.$r,pp_money($pr['amount']),4).'</row>';$r++;}
    }
    $rows[]='<row r="'.$r.'">'.pp_xlsx_cell('A'.$r,$label,9).pp_xlsx_cell('B'.$r,'',9).pp_xlsx_cell('C'.$r,pp_progress($model['awarded_total']??0,$model['planned_total']??0),9).'</row>';
    $sheets[]=['name'=>'Team + Prize','xml'=>pp_xlsx_sheet($rows,[],'<col min="1" max="1" width="38" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/><col min="3" max="3" width="14" customWidth="1"/>')];

    $sheetTags=[];$rels=[];$types=[];$files=[];
    foreach($sheets as $i=>$sh){$n=$i+1;$sheetTags[]='<sheet name="'.mrlp_xlsx_xml($sh['name']).'" sheetId="'.$n.'" r:id="rId'.$n.'"/>';$rels[]='<Relationship Id="rId'.$n.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$n.'.xml"/>';$types[]='<Override PartName="/xl/worksheets/sheet'.$n.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';$files['xl/worksheets/sheet'.$n.'.xml']=$sh['xml'];}
    $styleRid=count($sheets)+1;$rels[]='<Relationship Id="rId'.$styleRid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    $files['[Content_Types].xml']='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.implode('',$types).'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    $files['_rels/.rels']='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    $files['xl/workbook.xml']='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.implode('',$sheetTags).'</sheets></workbook>';
    $files['xl/_rels/workbook.xml.rels']='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.implode('',$rels).'</Relationships>';
    $files['xl/styles.xml']=pp_chart_xlsx_styles();
    $filename='MRL_Payouts_'.$year.'_Everything_'.date('Ymd_hisa').'.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Cache-Control: no-store');echo mrlp_zip_strings($files);exit;
}

$teamPayouts=pp_team_payouts($model);
$throughRace=pp_through_race($model);
$grandTotalLabel=$throughRace>0?'Grand Total — Through R'.$throughRace:'Grand Total';
$hasUnresolvedPayouts=pp_has_unresolved_payouts($model);
if(($_GET['export']??'')==='main_excel')pp_send_main_chart_xlsx($requested,$model,$yearCfg);
if(($_GET['export']??'')==='everything_excel')pp_send_everything_xlsx($requested,$model,$yearCfg,$teamPayouts,$throughRace);
$printMode=(string)($_GET['print']??'');
$printChart=($printMode==='chart');
$printEverything=($printMode==='everything');
$printStamp=date('Ymd_hisa');
$printFilenameBase=$printChart
    ? 'MRL_Payouts_'.$requested.'_Main_Chart_'.$printStamp
    : ($printEverything ? 'MRL_Payouts_'.$requested.'_Everything_'.$printStamp : 'MRL Payouts');
$selectYears=$publicYears;if($isAdminPreview&&!in_array($requested,$selectYears,true))$selectYears[]=$requested;rsort($selectYears,SORT_STRING);
?>
<!doctype html>
<html class="mrl-theme-<?php echo mrlp_h($theme); ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo mrlp_h($printFilenameBase); ?></title>
<link rel="stylesheet" href="/mrl_team/mrl_shared_theme.css?v=001">
<style>
@view-transition{navigation:auto}*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;font-size:16px;color:var(--mrl-rd-text,#111)}
.page-wrap{width:96%;max-width:900px;margin:14px auto 30px}.badge{display:inline-block;border:3px solid #c00000;color:#c00000;background:#fff;border-radius:999px;padding:5px 18px;font-size:20px;font-weight:800;letter-spacing:.4px;white-space:nowrap}.badge.official{border-color:#2e8b57;color:#116b36}.topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:nowrap;margin-bottom:8px}.controls,.top-status,.export-actions{position:relative;top:3px}.top-status{display:flex;align-items:center;justify-content:center;flex:0 0 auto}
.controls{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.controls select,.btn{height:32px;border:1px solid #7a7a7a;border-radius:9px;background:#f6f6f6;color:#111;padding:0 10px;font:600 14px Arial;text-decoration:none;display:inline-flex;align-items:center;cursor:pointer}
.export-actions{display:grid;grid-template-columns:max-content max-content;gap:7px}.title-row h1{margin:3px 0 8px;font-size:28px;color:var(--mrl-rd-text,#fff)}
.report{background:#fff;color:#111;border:1px solid #555;border-radius:4px;overflow:hidden}.year-label{font-size:20px;font-weight:700;padding:8px 10px 4px;background:#fff}
table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:16px}col.prize{width:37%}col.winner{width:25%}col.owner{width:27%}col.amount{width:11%}
th,td{border:1px solid #333;padding:4px 7px;line-height:1.16;vertical-align:middle}th{background:#ffff00;font-size:17px;font-weight:700;text-align:center}.amount{text-align:right;white-space:nowrap}
.seg-S1 td{background:#c5d9f1}.seg-S2 td{background:#c4bd97}.seg-S3 td{background:#fcd5b4}.seg-S4 td{background:#c4d79b}.segment-prize td{background:#92d050!important;font-weight:500;border-top:2px solid #333}.yearly td{background:#92d050;font-weight:500}.future .winner,.future .owner{color:#777}
.tie-lead td{border-bottom:0!important}.tie-follow td{border-top:0!important;background-image:repeating-linear-gradient(to right,#555 0 14px,transparent 14px 19px,#555 19px 23px,transparent 23px 29px)!important;background-size:29px 1px!important;background-repeat:repeat-x!important;background-position:left top!important}.section-head th{border-top:3px solid #333}.planned-total td{background:#1f4e78;color:#fff;font-weight:700;font-size:17px}.planned-total td:first-child{text-align:right}.team-payouts{margin-top:18px}.team-payouts>h2{margin:0 0 8px;font-size:23px}.team-payout-card{margin-top:14px}.team-payout-title{margin:0 0 6px;font-size:19px}.team-payout-card table{font-size:15px}.team-payout-card th{background:#1f4e78;color:#fff}.team-payout-card td{background:#d9e8f6;color:#111}.team-payout-card .grand-total td,.team-total td{background:#1f4e78;color:#fff;font-weight:700}.team-payout-card .team-name{font-weight:700}.grouped-prizes .prize-detail td{background:#eef5fb}.grouped-prizes .prize-detail td:first-child{padding-left:24px}.note{font-size:13px;color:var(--mrl-rd-muted,#666);margin-top:8px}.print-note{display:none}
@media(max-width:760px){body{font-size:15px}.page-wrap{width:99%}table{font-size:14px}th{font-size:14px}th,td{padding:4px}.title-row h1{font-size:24px}.team-payout-card table{font-size:14px}.topbar{flex-wrap:wrap}.top-status{order:2}.export-actions{grid-template-columns:1fr;order:3}.badge{font-size:18px}}
@media print{html,body{background:#fff!important;background-image:none!important;color:#111!important}.controls,.export-actions,.print-note{display:none!important}.print-main .team-payouts{display:none!important}.page-wrap{width:100%;max-width:none;margin:0}.report{border:0}.badge,.team-payout-card th,.team-payout-card td{-webkit-print-color-adjust:exact;print-color-adjust:exact}.seg-S1 td,.seg-S2 td,.seg-S3 td,.seg-S4 td,.segment-prize td,.yearly td,th,.planned-total td{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style></head>
<body class="<?php echo $printChart?'print-main':($printEverything?'print-everything':''); ?>"><div class="page-wrap">
<div class="topbar"><div class="controls"><?php if(!$printChart&&!$printEverything): ?><form method="get"><select name="year" onchange="this.form.submit()"><?php foreach($selectYears as $y): ?><option value="<?php echo mrlp_h($y); ?>" <?php echo $y===$requested?'selected':''; ?>><?php echo mrlp_h($y); ?></option><?php endforeach; ?></select><?php if($isAdminPreview): ?><input type="hidden" name="preview" value="1"><?php endif; ?></form><a class="btn" href="weekly_standings.php?year=<?php echo mrlp_h($requested); ?>">Weekly Standings</a><?php else: ?><a class="btn" href="?year=<?php echo mrlp_h($requested); ?><?php echo $isAdminPreview?'&preview=1':''; ?>">Back to Payouts</a><?php endif; ?></div><?php if(empty($yearCfg['official'])): ?><div class="top-status"><span class="badge">UNOFFICIAL</span></div><?php endif; ?><?php if(!$printChart&&!$printEverything): ?><div class="export-actions"><a class="btn" href="?year=<?php echo mrlp_h($requested); ?><?php echo $isAdminPreview?'&preview=1':''; ?>&export=main_excel">Excel — Main Chart</a><a class="btn" href="?year=<?php echo mrlp_h($requested); ?><?php echo $isAdminPreview?'&preview=1':''; ?>&print=chart&autoprint=1">PDF / Print — Main Chart</a><a class="btn" href="?year=<?php echo mrlp_h($requested); ?><?php echo $isAdminPreview?'&preview=1':''; ?>&export=everything_excel">Excel — Everything</a><a class="btn" href="?year=<?php echo mrlp_h($requested); ?><?php echo $isAdminPreview?'&preview=1':''; ?>&print=everything&autoprint=1">PDF / Print — Everything</a></div><?php endif; ?></div>
<div class="title-row"><h1><?php echo mrlp_h($requested); ?> MRL Payouts</h1></div>
<div class="report"><div class="year-label"><?php echo mrlp_h($requested); ?></div><table><colgroup><col class="prize"><col class="winner"><col class="owner"><col class="amount"></colgroup>
<?php foreach(['S1','S2','S3','S4'] as $seg): ?><thead class="section-head"><tr><th>Prize</th><th>Winner</th><th>Team Owner</th><th>Amount</th></tr></thead><tbody>
<?php $lastPrize=''; $sectionRows=$model['sections'][$seg]; foreach($sectionRows as $rowIndex=>$row): $tieFollow=!empty($row['tie'])&&$lastPrize===(string)$row['prize']; $nextRow=$sectionRows[$rowIndex+1]??null; $tieLead=!empty($row['tie'])&&is_array($nextRow)&&!empty($nextRow['tie'])&&(string)($nextRow['prize']??'')===(string)$row['prize']; $cls='seg-'.$seg.' '.(($row['kind']??'')==='segment'?'segment-prize ':'').(!empty($row['future'])?'future ':'').($tieLead?'tie-lead ':'').($tieFollow?'tie-follow ':''); ?>
<tr class="<?php echo mrlp_h(trim($cls)); ?>"><td><?php echo mrlp_h(pp_display_prize((string)$row['prize'])); ?></td><td class="winner"><?php echo mrlp_h($row['winner']); ?></td><td class="owner"><?php echo mrlp_h($row['owner']); ?></td><td class="amount"><?php echo pp_money($row['amount']); ?></td></tr>
<?php $lastPrize=(string)$row['prize']; endforeach; ?></tbody><?php endforeach; ?>
<thead class="section-head"><tr><th>Prize</th><th>Winner</th><th>Team Owner</th><th>Amount</th></tr></thead><tbody><?php $lastPrize=''; $yearlyRows=$model['yearly']; foreach($yearlyRows as $rowIndex=>$row): $tf=!empty($row['tie'])&&$lastPrize===(string)$row['prize']; $nextRow=$yearlyRows[$rowIndex+1]??null; $tl=!empty($row['tie'])&&is_array($nextRow)&&!empty($nextRow['tie'])&&(string)($nextRow['prize']??'')===(string)$row['prize']; ?><tr class="yearly <?php echo !empty($row['future'])?'future ':''; ?><?php echo $tl?'tie-lead ':''; ?><?php echo $tf?'tie-follow':''; ?>"><td><?php echo mrlp_h(pp_display_prize((string)$row['prize'])); ?></td><td><?php echo mrlp_h($row['winner']); ?></td><td><?php echo mrlp_h($row['owner']); ?></td><td class="amount"><?php echo pp_money($row['amount']); ?></td></tr><?php $lastPrize=(string)$row['prize']; endforeach; ?>
<tr class="planned-total"><td colspan="3">Planned Season Payout Total</td><td class="amount"><?php echo pp_money($model['planned_total']); ?></td></tr></tbody></table></div>
<?php if(!$printChart): ?><div class="team-payouts"><h2>Team Payouts</h2><?php if($hasUnresolvedPayouts): ?><div class="note">These views include only awards whose winners are currently known. Future payout rows remain visible in the main chart above.</div><?php endif; ?>

<div class="team-payout-card"><h3 class="team-payout-title">Team &amp; Team Owner — Sorted by Team</h3><table><thead><tr><th>Team</th><th>Team Owner</th><th>Payout</th></tr></thead><tbody><?php foreach($teamPayouts['by_team'] as $tr): ?><tr><td class="team-name"><?php echo mrlp_h($tr['team']); ?></td><td><?php echo mrlp_h($tr['owner']); ?></td><td class="amount"><?php echo pp_money($tr['payout']); ?></td></tr><?php endforeach; ?><tr class="grand-total"><td colspan="2"><?php echo mrlp_h($grandTotalLabel); ?></td><td class="amount"><?php echo pp_progress($model['awarded_total'],$model['planned_total']); ?></td></tr></tbody></table></div>

<div class="team-payout-card"><h3 class="team-payout-title">Team &amp; Team Owner — Sorted by Payout</h3><table><thead><tr><th>Team</th><th>Team Owner</th><th>Payout</th></tr></thead><tbody><?php foreach($teamPayouts['by_payout'] as $tr): ?><tr><td class="team-name"><?php echo mrlp_h($tr['team']); ?></td><td><?php echo mrlp_h($tr['owner']); ?></td><td class="amount"><?php echo pp_money($tr['payout']); ?></td></tr><?php endforeach; ?><tr class="grand-total"><td colspan="2"><?php echo mrlp_h($grandTotalLabel); ?></td><td class="amount"><?php echo pp_progress($model['awarded_total'],$model['planned_total']); ?></td></tr></tbody></table></div>

<div class="team-payout-card grouped-prizes"><h3 class="team-payout-title">Grouped by Team and Prize — Sorted by Team</h3><table><thead><tr><th>Team / Prize</th><th>Team Owner</th><th>Payout</th></tr></thead><tbody><?php foreach($teamPayouts['by_team'] as $tr): ?><tr class="team-total"><td><?php echo mrlp_h($tr['team']); ?></td><td><?php echo mrlp_h($tr['owner']); ?></td><td class="amount"><?php echo pp_money($tr['payout']); ?></td></tr><?php foreach($tr['prizes'] as $pr): ?><tr class="prize-detail"><td><?php echo mrlp_h($pr['prize']); ?></td><td></td><td class="amount"><?php echo pp_money($pr['amount']); ?></td></tr><?php endforeach; ?><?php endforeach; ?><tr class="grand-total"><td colspan="2"><?php echo mrlp_h($grandTotalLabel); ?></td><td class="amount"><?php echo pp_progress($model['awarded_total'],$model['planned_total']); ?></td></tr></tbody></table></div>
</div><?php endif; ?>
<?php if(($printChart||$printEverything) && !empty($_GET['autoprint'])): ?><script>window.addEventListener('load',function(){window.print();});</script><?php endif; ?>
</div></body></html>
