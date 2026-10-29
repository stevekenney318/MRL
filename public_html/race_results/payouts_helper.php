<?php
declare(strict_types=1);

/**
 * payouts_helper.php
 *
 * VERSION: v001
 * LAST MODIFIED: 10/29/2026 1:48:37 am ET
 *
 * PURPOSE:
 * - Shared scoring/payout data layer for race_results/payouts.php and admin_payouts.php.
 * - Reuses the same race snapshots, pick rows, LP/RD overlay rules, competitive roster rules,
 *   and weekly-winner logic used by Weekly Standings.
 * - Stores only payout configuration in JSON; winners remain derived from scoring data.
 *
 * FIRST-PASS RULES:
 * - Weekly High Point prize is one configured pool per race and is split equally on ties.
 * - Segment/year prize slots are occupied by standings positions. A tie group spanning multiple
 *   prize positions shares each occupied prize pool equally, matching the historical spreadsheet.
 * - Split rounding is deterministic to cents; remainder pennies go to the first listed tied rows.
 * - Segment prizes populate only after that segment's final-race snapshot exists.
 * - Yearly prizes populate only after R36 has a snapshot.
 * - Known noncompetitive IDs 0/998/999 and MRL test team are excluded.
 *
 * PHP: 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

require_once __DIR__ . '/race_results_team_helper.php';
require_once __DIR__ . '/race_results_snapshot_helper.php';
require_once __DIR__ . '/race_results_engine.php';
require_once __DIR__ . '/race_schedule_helper.php';

function mrlp_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function mrlp_config_dir(): string
{
    return __DIR__ . '/_payouts';
}

function mrlp_config_path(): string
{
    return mrlp_config_dir() . '/payouts_config.json';
}

function mrlp_default_year_config(string $year): array
{
    return [
        'year' => $year,
        'public' => ($year === '2026'),
        'official' => false,
        'weekly_high_point' => 10.00,
        'segments' => [
            'S1' => [40.00, 20.00],
            'S2' => [40.00, 20.00],
            'S3' => [40.00, 20.00],
            'S4' => [40.00, 10.00, 10.00],
        ],
        'yearly' => [60.00, 50.00, 40.00],
    ];
}

function mrlp_load_config(): array
{
    $path = mrlp_config_path();
    if (!is_file($path)) {
        return ['version' => 1, 'years' => []];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return ['version' => 1, 'years' => []];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['version' => 1, 'years' => []];
    }
    if (!isset($data['years']) || !is_array($data['years'])) {
        $data['years'] = [];
    }
    $data['version'] = 1;
    return $data;
}

function mrlp_year_config(array $config, string $year): array
{
    $defaults = mrlp_default_year_config($year);
    $stored = isset($config['years'][$year]) && is_array($config['years'][$year])
        ? $config['years'][$year]
        : [];

    $out = $defaults;
    $out['public'] = array_key_exists('public', $stored) ? !empty($stored['public']) : !empty($defaults['public']);
    $out['official'] = array_key_exists('official', $stored) ? !empty($stored['official']) : !empty($defaults['official']);
    if (isset($stored['weekly_high_point']) && is_numeric($stored['weekly_high_point'])) {
        $out['weekly_high_point'] = max(0, (float)$stored['weekly_high_point']);
    }

    foreach (['S1','S2','S3','S4'] as $segment) {
        if (isset($stored['segments'][$segment]) && is_array($stored['segments'][$segment])) {
            $vals = [];
            foreach ($stored['segments'][$segment] as $v) {
                if (is_numeric($v)) $vals[] = max(0, (float)$v);
            }
            if (!empty($vals)) $out['segments'][$segment] = $vals;
        }
    }

    if (isset($stored['yearly']) && is_array($stored['yearly'])) {
        $vals = [];
        foreach ($stored['yearly'] as $v) {
            if (is_numeric($v)) $vals[] = max(0, (float)$v);
        }
        if (!empty($vals)) $out['yearly'] = $vals;
    }

    return $out;
}

function mrlp_save_config(array $config): bool
{
    $dir = mrlp_config_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }
    $path = mrlp_config_path();
    $config['version'] = 1;
    $config['updated_at'] = date('Y-m-d H:i:s');
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json)) return false;
    $json .= "\n";

    if (is_file($path)) {
        $backup = $dir . '/payouts_config_previous_' . date('Ymd_His') . '.json';
        @copy($path, $backup);
    }

    $tmp = $path . '.tmp_' . getmypid() . '_' . bin2hex(random_bytes(3));
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    @chmod($tmp, 0644);
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    @chmod($path, 0644);
    return true;
}

function mrlp_available_years(string $baseDir): array
{
    $years = [];
    $items = @scandir($baseDir);
    if (!is_array($items)) return [];
    foreach ($items as $name) {
        if (!preg_match('/^\d{4}$/', (string)$name)) continue;
        $folder = rtrim($baseDir, '/\\') . '/' . $name;
        if (is_dir($folder) && is_file($folder . '/_year_index.json')) {
            $years[] = (string)$name;
        }
    }
    rsort($years, SORT_STRING);
    return $years;
}

function mrlp_load_year_index(string $path): array
{
    $data = rr_load_json($path);
    if (!is_array($data) || !isset($data['races']) || !is_array($data['races'])) return [];
    return $data;
}

function mrlp_points_races_from_index(array $index, string $yearFolder): array
{
    $rows = [];
    foreach ($index['races'] as $raceId => $row) {
        if (!is_array($row) || (string)($row['kind'] ?? '') !== 'R') continue;
        $number = (int)($row['number'] ?? 0);
        $folder = trim((string)($row['folder'] ?? ''));
        if ($number < 1 || $number > 36 || $folder === '') continue;
        $rows[] = [
            'raceId' => (string)$raceId,
            'number' => $number,
            'raceCode' => 'R' . str_pad((string)$number, 2, '0', STR_PAD_LEFT),
            'raceName' => (string)($row['race_name'] ?? ''),
            'raceFolder' => rtrim($yearFolder, '/\\') . '/' . $folder,
        ];
    }
    usort($rows, function ($a, $b) {
        return ((int)$a['number']) <=> ((int)$b['number']);
    });
    return $rows;
}

function mrlp_segment_from_race(int $raceNumber): string
{
    if ($raceNumber <= 8) return 'S1';
    if ($raceNumber <= 17) return 'S2';
    if ($raceNumber <= 26) return 'S3';
    return 'S4';
}

function mrlp_segment_bounds(string $segment): array
{
    if ($segment === 'S1') return ['start'=>1,'end'=>8];
    if ($segment === 'S2') return ['start'=>9,'end'=>17];
    if ($segment === 'S3') return ['start'=>18,'end'=>26];
    return ['start'=>27,'end'=>36];
}

function mrlp_segment_display(string $segment, string $year): string
{
    if ($segment === 'S4') return ((int)$year >= 2026) ? 'The Chase' : 'Playoffs';
    return 'Seg #' . substr($segment, 1);
}

function mrlp_is_noncompetitive(array $team): bool
{
    $hasId = array_key_exists('userID', $team) && $team['userID'] !== null && $team['userID'] !== '';
    $uid = $hasId ? (int)$team['userID'] : null;
    $name = strtolower(trim((string)($team['teamName'] ?? '')));
    if ($hasId && in_array($uid, [0,998,999], true)) return true;
    return $name === 'mrl test team';
}

function mrlp_year_roster(string $year, $dbo): array
{
    if (!($dbo instanceof PDO)) return [];
    $sql = "
        SELECT ut.userID, ut.teamName, COALESCE(u.userName,'') AS userName
        FROM user_teams ut
        LEFT JOIN users u ON u.userID=ut.userID
        WHERE ut.raceYear=:year
          AND ut.userID NOT IN (0,998,999)
          AND LOWER(TRIM(ut.teamName)) <> 'mrl test team'
          AND EXISTS (
              SELECT 1 FROM user_picks up
              WHERE up.raceYear=ut.raceYear
                AND up.userID=ut.userID
                AND up.userID NOT IN (0,998,999)
          )
        ORDER BY ut.teamName ASC, ut.userID ASC
    ";
    $stmt = $dbo->prepare($sql);
    $stmt->execute([':year'=>$year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    if (!is_array($rows)) return $out;
    foreach ($rows as $row) {
        if (!is_array($row) || mrlp_is_noncompetitive($row)) continue;
        $team = trim((string)($row['teamName'] ?? ''));
        if ($team === '') continue;
        $out[$team] = [
            'userID'=>(int)($row['userID'] ?? 0),
            'teamName'=>$team,
            'userName'=>(string)($row['userName'] ?? ''),
        ];
    }
    ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
    return $out;
}

function mrlp_owner_map(string $year, $dbo): array
{
    if (!($dbo instanceof PDO)) return [];
    $sql = "
        SELECT ut.userID, ut.teamName, COALESCE(u.userName,'') AS userName
        FROM user_teams ut
        LEFT JOIN users u ON u.userID=ut.userID
        WHERE ut.raceYear=:year
          AND ut.userID NOT IN (0,998,999)
          AND LOWER(TRIM(ut.teamName)) <> 'mrl test team'
        ORDER BY ut.teamName ASC, ut.userID ASC
    ";
    $stmt = $dbo->prepare($sql);
    $stmt->execute([':year'=>$year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    if (!is_array($rows)) return $out;
    foreach ($rows as $row) {
        $team = trim((string)($row['teamName'] ?? ''));
        if ($team === '') continue;
        $out[$team] = trim((string)($row['userName'] ?? ''));
    }
    return $out;
}

function mrlp_special_rows(string $year, string $segment, $dbo): array
{
    if (!($dbo instanceof PDO)) return [];
    $sql = "
        SELECT up.userID,up.teamName,COALESCE(u.userName,'') AS userName,
               up.driverA,up.driverB,up.driverC,up.driverD,up.entryDate,
               up.pick_type,up.effective_race,up.supersedes_pickID,
               base.driverA AS original_driverA,base.driverB AS original_driverB,
               base.driverC AS original_driverC,base.driverD AS original_driverD
        FROM user_picks up
        LEFT JOIN users u ON u.userID=up.userID
        LEFT JOIN user_picks base ON base.pickID=up.supersedes_pickID
        WHERE up.raceYear=:year AND up.segment=:segment AND up.pick_type IN ('LP','RD')
        ORDER BY up.teamName ASC,up.effective_race ASC,up.entryDate ASC,up.pickID ASC
    ";
    $stmt = $dbo->prepare($sql);
    $stmt->execute([':year'=>$year,':segment'=>$segment]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
}

function mrlp_overlay_special(array $baseRows, array $specialRows, int $raceNumber, string $segment): array
{
    $byTeam = [];
    foreach ($baseRows as $row) {
        if (!is_array($row) || mrlp_is_noncompetitive($row)) continue;
        $team = trim((string)($row['teamName'] ?? ''));
        if ($team === '') continue;
        $row['pick_type'] = (string)($row['pick_type'] ?? 'SEG');
        $row['effective_race'] = (int)($row['effective_race'] ?? mrlp_segment_bounds($segment)['start']);
        foreach (['A','B','C','D'] as $g) $row['original_driver'.$g] = (string)($row['original_driver'.$g] ?? '');
        $byTeam[$team] = $row;
    }

    $specialByTeam = [];
    foreach ($specialRows as $row) {
        if (!is_array($row) || mrlp_is_noncompetitive($row)) continue;
        $team = trim((string)($row['teamName'] ?? ''));
        if ($team === '') continue;
        if (!isset($specialByTeam[$team])) $specialByTeam[$team] = [];
        $specialByTeam[$team][] = $row;
    }

    foreach ($specialByTeam as $team => $rows) {
        usort($rows, function ($a,$b) {
            $ar=(int)($a['effective_race']??0); $br=(int)($b['effective_race']??0);
            if ($ar !== $br) return $ar <=> $br;
            $ad=strtotime((string)($a['entryDate']??'')); $bd=strtotime((string)($b['entryDate']??''));
            return $ad <=> $bd;
        });
        $applicable = null;
        foreach ($rows as $row) if ((int)($row['effective_race']??0) <= $raceNumber) $applicable = $row;
        $first = $rows[0];
        if ($applicable !== null) {
            $byTeam[$team] = [
                'userID'=>(int)($applicable['userID']??($byTeam[$team]['userID']??0)),
                'teamName'=>$team,
                'userName'=>(string)($applicable['userName']??($byTeam[$team]['userName']??'')),
                'driverA'=>(string)($applicable['driverA']??''),
                'driverB'=>(string)($applicable['driverB']??''),
                'driverC'=>(string)($applicable['driverC']??''),
                'driverD'=>(string)($applicable['driverD']??''),
                'pick_type'=>(string)($applicable['pick_type']??''),
                'effective_race'=>(int)($applicable['effective_race']??0),
                'original_driverA'=>(string)($applicable['original_driverA']??''),
                'original_driverB'=>(string)($applicable['original_driverB']??''),
                'original_driverC'=>(string)($applicable['original_driverC']??''),
                'original_driverD'=>(string)($applicable['original_driverD']??''),
            ];
        } else {
            $firstEffective = (int)($first['effective_race']??0);
            $existing = isset($byTeam[$team]) && is_array($byTeam[$team]) ? $byTeam[$team] : null;
            $existingType = strtoupper((string)($existing['pick_type']??''));
            if ($firstEffective > $raceNumber && ($existing === null || $existingType === 'LP')) {
                $byTeam[$team] = [
                    'userID'=>(int)($first['userID']??0),'teamName'=>$team,
                    'userName'=>(string)($first['userName']??''),
                    'driverA'=>'','driverB'=>'','driverC'=>'','driverD'=>'',
                    'pick_type'=>(string)($first['pick_type']??''),'effective_race'=>$firstEffective,
                    'original_driverA'=>(string)($first['original_driverA']??''),
                    'original_driverB'=>(string)($first['original_driverB']??''),
                    'original_driverC'=>(string)($first['original_driverC']??''),
                    'original_driverD'=>(string)($first['original_driverD']??''),
                ];
            }
        }
    }
    $out = array_values($byTeam);
    usort($out, function($a,$b){return strcasecmp((string)($a['teamName']??''),(string)($b['teamName']??''));});
    return $out;
}

function mrlp_append_missing_team_rows(array $rows, array $roster): array
{
    if (empty($roster)) return $rows;
    $seen = [];
    foreach ($rows as $row) {
        $team = strtolower(trim((string)($row['teamName'] ?? '')));
        if ($team !== '') $seen[$team] = true;
    }
    foreach ($roster as $teamName => $rr) {
        $key = strtolower(trim((string)$teamName));
        if ($key === '' || isset($seen[$key])) continue;
        $rows[] = [
            'userID'=>(int)($rr['userID']??0),'teamName'=>(string)$teamName,'userName'=>(string)($rr['userName']??''),
            'driverA'=>'','driverB'=>'','driverC'=>'','driverD'=>'','pick_type'=>'MISS','effective_race'=>0,
            'original_driverA'=>'','original_driverB'=>'','original_driverC'=>'','original_driverD'=>'',
        ];
    }
    usort($rows, function($a,$b){return strcasecmp((string)($a['teamName']??''),(string)($b['teamName']??''));});
    return $rows;
}

function mrlp_driver_net(array $driverPoints, string $name): int
{
    if ($name === '' || !isset($driverPoints[$name]) || !is_array($driverPoints[$name])) return 0;
    return (int)($driverPoints[$name]['net'] ?? 0);
}

function mrlp_weekly_rows(array $teamRows, array $driverPoints): array
{
    $rows=[];
    foreach ($teamRows as $team) {
        if (!is_array($team) || mrlp_is_noncompetitive($team)) continue;
        $a=(string)($team['driverA']??''); $b=(string)($team['driverB']??'');
        $c=(string)($team['driverC']??''); $d=(string)($team['driverD']??'');
        $na=mrlp_driver_net($driverPoints,$a); $nb=mrlp_driver_net($driverPoints,$b);
        $nc=mrlp_driver_net($driverPoints,$c); $nd=mrlp_driver_net($driverPoints,$d);
        $rows[]=[
            'teamName'=>(string)($team['teamName']??''),'userName'=>(string)($team['userName']??''),
            'weeklyTotal'=>$na+$nb+$nc+$nd,
        ];
    }
    usort($rows,function($a,$b){
        $av=(int)($a['weeklyTotal']??0); $bv=(int)($b['weeklyTotal']??0);
        if($av!==$bv)return $bv<=>$av;
        return strcasecmp((string)($a['teamName']??''),(string)($b['teamName']??''));
    });
    return $rows;
}

function mrlp_append_missing_weekly_rows(array $rows, array $roster): array
{
    if (empty($roster)) return $rows;
    $seen=[];
    foreach($rows as $row){$t=strtolower(trim((string)($row['teamName']??'')));if($t!=='')$seen[$t]=true;}
    foreach($roster as $team=>$rr){
        $k=strtolower((string)$team);if(isset($seen[$k]))continue;
        $rows[]=['teamName'=>(string)$team,'userName'=>(string)($rr['userName']??''),'weeklyTotal'=>0];
    }
    usort($rows,function($a,$b){$av=(int)($a['weeklyTotal']??0);$bv=(int)($b['weeklyTotal']??0);if($av!==$bv)return $bv<=>$av;return strcasecmp((string)$a['teamName'],(string)$b['teamName']);});
    return $rows;
}

function mrlp_weekly_winner(array $rows): array
{
    if (empty($rows)) return ['teamNames'=>[],'points'=>0];
    $top=(int)($rows[0]['weeklyTotal']??0);$names=[];
    foreach($rows as $row){if((int)($row['weeklyTotal']??0)!==$top)break;$names[]=(string)($row['teamName']??'');}
    return ['teamNames'=>$names,'points'=>$top];
}

function mrlp_latest_snapshot(string $raceFolder): string
{
    if(!is_dir($raceFolder))return '';
    $files=glob(rtrim($raceFolder,'/\\').'/snapshot_*.html');
    if(!is_array($files)||empty($files))return '';
    sort($files,SORT_STRING);return (string)end($files);
}

function mrlp_sorted_total_rows(array $totals): array
{
    $rows=[];foreach($totals as $team=>$total)$rows[]=['teamName'=>(string)$team,'total'=>(int)$total];
    usort($rows,function($a,$b){$av=(int)$a['total'];$bv=(int)$b['total'];if($av!==$bv)return $bv<=>$av;return strcasecmp((string)$a['teamName'],(string)$b['teamName']);});
    return $rows;
}

function mrlp_compute_year(string $year, $dbo, $dbconnect): array
{
    $base=__DIR__;
    $yearFolder=$base.'/'.$year;
    $index=mrlp_load_year_index($yearFolder.'/_year_index.json');
    $races=mrlp_points_races_from_index($index,$yearFolder);
    $roster=((int)$year>=2026)?mrlp_year_roster($year,$dbo):[];
    $owners=mrlp_owner_map($year,$dbo);
    $weekly=[];$segmentTotals=['S1'=>[],'S2'=>[],'S3'=>[],'S4'=>[]];$seasonTotals=[];$completed=[];

    foreach($races as $race){
        $rn=(int)$race['number'];$seg=mrlp_segment_from_race($rn);$code=(string)$race['raceCode'];
        $snapshot=mrlp_latest_snapshot((string)$race['raceFolder']);
        if($snapshot===''){ $weekly[$rn]=['teamNames'=>[],'points'=>0,'complete'=>false]; continue; }
        $baseRows=rr_get_segment_team_picks($dbo,$dbconnect,$year,$seg);
        $special=mrlp_special_rows($year,$seg,$dbo);
        $teamRows=mrlp_overlay_special($baseRows,$special,$rn,$seg);
        if(!empty($roster))$teamRows=mrlp_append_missing_team_rows($teamRows,$roster);
        $driverPoints=rrs_load_snapshot_driver_points($snapshot);
        $weekRows=mrlp_weekly_rows($teamRows,$driverPoints);
        if(!empty($roster))$weekRows=mrlp_append_missing_weekly_rows($weekRows,$roster);
        $winner=mrlp_weekly_winner($weekRows);
        $winner['complete']=true;$winner['snapshot']=$snapshot;$weekly[$rn]=$winner;$completed[$rn]=true;
        foreach($weekRows as $wr){
            $team=(string)($wr['teamName']??'');if($team==='')continue;$pts=(int)($wr['weeklyTotal']??0);
            if(!isset($seasonTotals[$team]))$seasonTotals[$team]=0;$seasonTotals[$team]+=$pts;
            if(!isset($segmentTotals[$seg][$team]))$segmentTotals[$seg][$team]=0;$segmentTotals[$seg][$team]+=$pts;
            if(!isset($owners[$team])||$owners[$team]==='')$owners[$team]=(string)($wr['userName']??'');
        }
    }

    $segments=[];
    foreach(['S1','S2','S3','S4'] as $seg){
        $bounds=mrlp_segment_bounds($seg);$complete=!empty($completed[$bounds['end']]);
        $segments[$seg]=['complete'=>$complete,'standings'=>mrlp_sorted_total_rows($segmentTotals[$seg])];
    }
    $seasonComplete=!empty($completed[36]);

    return [
        'year'=>$year,'races'=>$races,'weekly'=>$weekly,'segments'=>$segments,
        'season'=>['complete'=>$seasonComplete,'standings'=>mrlp_sorted_total_rows($seasonTotals)],
        'owners'=>$owners,'completed'=>$completed,
    ];
}

function mrlp_money_cents($amount): int
{
    return (int)round(((float)$amount)*100);
}

function mrlp_cents_money(int $cents): float
{
    return $cents/100;
}

function mrlp_split_cents(int $totalCents, int $count): array
{
    if($count<=0)return [];
    $base=(int)floor($totalCents/$count);$rem=$totalCents-($base*$count);$out=[];
    for($i=0;$i<$count;$i++)$out[]=$base+($i<$rem?1:0);
    return $out;
}

function mrlp_ordinal(int $n): string
{
    if($n===1)return '1st';if($n===2)return '2nd';if($n===3)return '3rd';return $n.'th';
}

function mrlp_prize_rows_from_standings(array $standings, array $prizes, string $labelPrefix, array $owners): array
{
    $rows=[];$position=1;$i=0;$countRows=count($standings);$slots=count($prizes);
    while($i<$countRows && $position<=$slots){
        $score=(int)($standings[$i]['total']??0);$group=[];
        while($i<$countRows && (int)($standings[$i]['total']??0)===$score){$group[]=(string)($standings[$i]['teamName']??'');$i++;}
        $groupCount=count($group);if($groupCount===0)break;
        $endPosition=$position+$groupCount-1;
        for($slot=$position;$slot<=$endPosition && $slot<=$slots;$slot++){
            $amount=(float)$prizes[$slot-1];$parts=mrlp_split_cents(mrlp_money_cents($amount),$groupCount);$tie=$groupCount>1;
            foreach($group as $idx=>$team){
                $rows[]=[
                    'prize'=>$labelPrefix.' - '.mrlp_ordinal($slot).' Place Point Total'.($tie?' (Tie)':''),
                    'winner'=>$team,'owner'=>(string)($owners[$team]??''),'amount'=>mrlp_cents_money((int)$parts[$idx]),
                    'tie'=>$tie,'slot'=>$slot,'future'=>false,
                ];
            }
        }
        $position=$endPosition+1;
    }
    return $rows;
}

function mrlp_blank_prize_rows(array $prizes, string $labelPrefix): array
{
    $rows=[];foreach($prizes as $i=>$amount)$rows[]=[
        'prize'=>$labelPrefix.' - '.mrlp_ordinal($i+1).' Place Point Total','winner'=>'','owner'=>'','amount'=>(float)$amount,
        'tie'=>false,'slot'=>$i+1,'future'=>true,
    ];return $rows;
}

function mrlp_build_payout_model(array $score, array $yearCfg): array
{
    $owners=$score['owners']??[];$sections=[];$allAwarded=[];$planned=0.0;
    $weeklyAmount=(float)$yearCfg['weekly_high_point'];$planned += $weeklyAmount*36;
    foreach(['S1','S2','S3','S4'] as $seg)foreach($yearCfg['segments'][$seg] as $v)$planned+=(float)$v;
    foreach($yearCfg['yearly'] as $v)$planned+=(float)$v;

    foreach(['S1','S2','S3','S4'] as $seg){
        $bounds=mrlp_segment_bounds($seg);$rows=[];
        for($rn=$bounds['start'];$rn<=$bounds['end'];$rn++){
            $w=$score['weekly'][$rn]??['teamNames'=>[],'points'=>0,'complete'=>false];$names=is_array($w['teamNames']??null)?$w['teamNames']:[];
            if(!empty($w['complete'])&&!empty($names)){
                $parts=mrlp_split_cents(mrlp_money_cents($weeklyAmount),count($names));$tie=count($names)>1;
                foreach($names as $idx=>$team){
                    $row=['kind'=>'weekly','segment'=>$seg,'prize'=>'High Point Total '.$rn.($tie?' (Tie)':''),'winner'=>(string)$team,
                        'owner'=>(string)($owners[$team]??''),'amount'=>mrlp_cents_money((int)$parts[$idx]),'tie'=>$tie,'future'=>false,'week'=>$rn];
                    $rows[]=$row;$allAwarded[]=$row;
                }
            } else {
                $rows[]=['kind'=>'weekly','segment'=>$seg,'prize'=>'High Point Total '.$rn,'winner'=>'','owner'=>'','amount'=>$weeklyAmount,'tie'=>false,'future'=>true,'week'=>$rn];
            }
        }
        $segCfg=$yearCfg['segments'][$seg];$label=mrlp_segment_display($seg,(string)$score['year']);
        if(!empty($score['segments'][$seg]['complete'])){
            $pr=mrlp_prize_rows_from_standings($score['segments'][$seg]['standings'],$segCfg,$label,$owners);
            foreach($pr as &$x){$x['kind']='segment';$x['segment']=$seg;}unset($x);
            $rows=array_merge($rows,$pr);$allAwarded=array_merge($allAwarded,$pr);
        } else {
            $pr=mrlp_blank_prize_rows($segCfg,$label);foreach($pr as &$x){$x['kind']='segment';$x['segment']=$seg;}unset($x);$rows=array_merge($rows,$pr);
        }
        $sections[$seg]=$rows;
    }

    if(!empty($score['season']['complete'])){
        $yearRows=mrlp_prize_rows_from_standings($score['season']['standings'],$yearCfg['yearly'],'Yearly Point Total',$owners);
        foreach($yearRows as &$x){$x['kind']='yearly';$x['segment']='YEAR';}unset($x);$allAwarded=array_merge($allAwarded,$yearRows);
    } else {
        $yearRows=mrlp_blank_prize_rows($yearCfg['yearly'],'Yearly Point Total');foreach($yearRows as &$x){$x['kind']='yearly';$x['segment']='YEAR';}unset($x);
    }

    $tally=[];$awardedCents=0;
    foreach($allAwarded as $row){
        $owner=trim((string)($row['owner']??''));if($owner==='')$owner=trim((string)($row['winner']??''));if($owner==='')continue;
        $c=mrlp_money_cents((float)$row['amount']);$awardedCents+=$c;if(!isset($tally[$owner]))$tally[$owner]=0;$tally[$owner]+=$c;
    }
    ksort($tally,SORT_NATURAL|SORT_FLAG_CASE);$tallyRows=[];foreach($tally as $owner=>$c)$tallyRows[]=['owner'=>$owner,'payout'=>mrlp_cents_money($c)];

    return ['sections'=>$sections,'yearly'=>$yearRows,'planned_total'=>$planned,'awarded_total'=>mrlp_cents_money($awardedCents),'tally'=>$tallyRows];
}

function mrlp_xlsx_xml(string $v): string { return htmlspecialchars($v,ENT_XML1|ENT_COMPAT,'UTF-8'); }

function mrlp_zip_strings(array $files): string
{
    $data='';$central='';$offset=0;$ts=time();$y=(int)date('Y',$ts);$mo=(int)date('n',$ts);$d=(int)date('j',$ts);$h=(int)date('G',$ts);$mi=(int)date('i',$ts);$s=(int)date('s',$ts);
    $dosTime=($h<<11)|($mi<<5)|(int)floor($s/2);$dosDate=(($y-1980)<<9)|($mo<<5)|$d;
    foreach($files as $name=>$body){$name=(string)$name;$body=(string)$body;$nl=strlen($name);$bl=strlen($body);$crc=crc32($body);
        $local=pack('VvvvvvVVVvv',0x04034b50,20,0,0,$dosTime,$dosDate,$crc,$bl,$bl,$nl,0);$data.=$local.$name.$body;
        $central.=pack('VvvvvvvVVVvvvvvVV',0x02014b50,20,20,0,0,$dosTime,$dosDate,$crc,$bl,$bl,$nl,0,0,0,0,0,$offset).$name;
        $offset+=strlen($local)+$nl+$bl;}
    $co=strlen($data);$cs=strlen($central);$fc=count($files);$end=pack('VvvvvVVv',0x06054b50,0,0,$fc,$fc,$cs,$co,0);return $data.$central.$end;
}

function mrlp_send_tally_xlsx(string $year, array $tallyRows, float $awardedTotal): void
{
    $rows=[];$r=1;$rows[]='<row r="1" ht="24" customHeight="1"><c r="A1" t="inlineStr" s="1"><is><t>'.mrlp_xlsx_xml($year.' Payout Tally').'</t></is></c></row>';
    $rows[]='<row r="2"><c r="A2" t="inlineStr" s="2"><is><t>Team Owner</t></is></c><c r="B2" t="inlineStr" s="2"><is><t>Payout</t></is></c></row>';$r=3;
    foreach($tallyRows as $tr){$rows[]='<row r="'.$r.'"><c r="A'.$r.'" t="inlineStr" s="3"><is><t>'.mrlp_xlsx_xml((string)$tr['owner']).'</t></is></c><c r="B'.$r.'" s="4"><v>'.number_format((float)$tr['payout'],2,'.','').'</v></c></row>';$r++;}
    $rows[]='<row r="'.$r.'"><c r="A'.$r.'" t="inlineStr" s="5"><is><t>Grand Total</t></is></c><c r="B'.$r.'" s="6"><v>'.number_format($awardedTotal,2,'.','').'</v></c></row>';
    $sheet='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="28" customWidth="1"/><col min="2" max="2" width="14" customWidth="1"/></cols><sheetData>'.implode('',$rows).'</sheetData><mergeCells count="1"><mergeCell ref="A1:B1"/></mergeCells></worksheet>';
    $styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="$#,##0.00"/></numFmts><fonts count="3"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="16"/><name val="Arial"/><color rgb="FFFFFFFF"/></font><font><b/><sz val="11"/><name val="Arial"/><color rgb="FFFFFFFF"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9E8F6"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="2" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="1"/><xf numFmtId="164" fontId="0" fillId="3" borderId="1" applyNumberFormat="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="1"/><xf numFmtId="164" fontId="2" fillId="2" borderId="1" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    $files=[
        '[Content_Types].xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
        '_rels/.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Tally" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml'=>$sheet,'xl/styles.xml'=>$styles,
    ];
    $filename='MRL_Payout_Tally_'.$year.'_'.date('Ymd_His').'.xlsx';header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Cache-Control: no-store');echo mrlp_zip_strings($files);exit;
}
