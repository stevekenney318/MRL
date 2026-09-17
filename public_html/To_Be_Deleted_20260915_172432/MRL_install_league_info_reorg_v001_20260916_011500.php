<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * League Information Relocation Installer
 *
 * VERSION: v001
 * CREATED: 9/16/2026 1:15:00 am EDT
 *
 * PURPOSE
 * -------
 * Move the 11 discovered Rules / Fees / Schedule files from public_html root
 * into /league_info/, repair move-sensitive PHP includes, move historical
 * companion folders when present, and update literal site references.
 *
 * SOURCE DISCOVERY:
 * MRL_historical_reorg_discovery_v002_20260915_232709.json
 *
 * SAFETY
 * ------
 * - Exact source path + size + mtime preflight for all 11 source files.
 * - Destination collision checks.
 * - Expected dependency checks before changing anything.
 * - Full backups of every source file and every caller changed.
 * - Optional companion folders moved only when present and destination clear.
 * - Atomic edits for rewritten PHP files and caller files.
 * - Rollback restores original root files, caller contents, and companion folders.
 * - No WordPress database writes in this installer.
 * - PHP 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

const MRL_INSTALLER_VERSION = 'v001';
const MRL_DEST_DIR = 'league_info';
const MRL_BACKUP_DIR = '_mrl_installer_backups/league_info_reorg_20260916_011500';
const MRL_STATE_FILE = '_mrl_league_info_reorg_state.json';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$manifest = [
    "2019_rules.docx" => ['size' => 16880, 'mtime' => 1765271964],
    "2019_rules.htm" => ['size' => 76471, 'mtime' => 1765271964],
    "2020_rules.htm" => ['size' => 79956, 'mtime' => 1765271964],
    "2024_Fees.php" => ['size' => 6214, 'mtime' => 1765866154],
    "2024_Rules.php" => ['size' => 5647, 'mtime' => 1765866154],
    "2024_Schedule.html" => ['size' => 45602, 'mtime' => 1765271964],
    "2024_Schedule.php" => ['size' => 1365, 'mtime' => 1765866153],
    "2025_Fees.php" => ['size' => 6569, 'mtime' => 1769495219],
    "2025_Rules.php" => ['size' => 5687, 'mtime' => 1765866153],
    "2026_Fees.php" => ['size' => 6952, 'mtime' => 1771223419],
    "2026_Rules.php" => ['size' => 5625, 'mtime' => 1769649138],
];

/*
 * Dependencies that must point back to public_html after the file moves.
 * 2024_Schedule.php -> 2024_Schedule.html intentionally remains local because
 * both files move together into /league_info/.
 */
$rootDependencyMap = [
    '2019_rules.docx' => [],
    '2019_rules.htm' => [],
    '2020_rules.htm' => [],
    '2024_Fees.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2024_Rules.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2024_Schedule.html' => [],
    '2024_Schedule.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2025_Fees.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2025_Rules.php' => ['class.user.php','config.php','config_mrl.php','header.php'],
    '2026_Fees.php' => [],
    '2026_Rules.php' => ['header.php'],
];

$localDependencyMap = [
    '2024_Schedule.php' => ['2024_Schedule.html'],
];

$companionDirs = [
    '2019_rules_files',
    'Schedules_files',
];

$scanExcludeTop = [
    'wp-admin','wp-includes','wp-content',
    'To_Be_Deleted_20260915_172432',
    '_migration_backups','_mrl_installer_backups','db_backups',
    'team_charts','league_info'
];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function full_path(string $root,string $relative): string {
    return rtrim($root,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);
}

function rel_path(string $root,string $path): string {
    $r=str_replace('\\','/',rtrim($root,'/\\'));
    $p=str_replace('\\','/',$path);
    return strpos($p,$r.'/')===0 ? substr($p,strlen($r)+1) : ltrim($p,'/');
}

function ensure_dir(string $dir): bool {
    return is_dir($dir) || @mkdir($dir,0755,true) || is_dir($dir);
}

function fmt_bytes(int $bytes): string {
    if($bytes<1024) return $bytes.' B';
    $units=['KB','MB','GB','TB']; $v=(float)$bytes;
    foreach($units as $u){
        $v/=1024;
        if($v<1024 || $u==='TB') return number_format($v,$v>=100?0:($v>=10?1:2)).' '.$u;
    }
    return $bytes.' B';
}

function atomic_write(string $path,string $content): bool {
    if(!ensure_dir(dirname($path))) return false;
    $tmp=$path.'.tmp_'.bin2hex(random_bytes(4));
    if(@file_put_contents($tmp,$content,LOCK_EX)===false) return false;
    if(!@rename($tmp,$path)){ @unlink($tmp); return false; }
    return true;
}

function state_path(string $root): string {
    return full_path($root,MRL_BACKUP_DIR.'/'.MRL_STATE_FILE);
}

function load_state(string $root): ?array {
    $f=state_path($root);
    if(!is_file($f)) return null;
    $raw=@file_get_contents($f);
    if($raw===false) return null;
    $d=json_decode($raw,true);
    return is_array($d)?$d:null;
}

function save_state(string $root,array $state): bool {
    $json=json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    return $json!==false && atomic_write(state_path($root),$json."\n");
}

function count_literal_dependency(string $content,string $dep): int {
    $q=preg_quote($dep,'/');
    $patterns=[
        '/\b(?:include|include_once|require|require_once)\s+[\'"]'.$q.'[\'"]\s*;?/i',
        '/\b(?:include|include_once|require|require_once)\s*\(\s*[\'"]'.$q.'[\'"]\s*\)\s*;?/i'
    ];
    $count=0;
    foreach($patterns as $p) $count+=preg_match_all($p,$content,$m);
    return $count;
}

function rewrite_root_dependencies(string $content,array $deps,array &$counts): string {
    $counts=[];
    foreach($deps as $dep){
        $counts[$dep]=0;
        $q=preg_quote($dep,'/');
        $patterns=[
            '/\b(include|include_once|require|require_once)\s+[\'"]'.$q.'[\'"]\s*;?/i',
            '/\b(include|include_once|require|require_once)\s*\(\s*[\'"]'.$q.'[\'"]\s*\)\s*;?/i'
        ];
        foreach($patterns as $pattern){
            $content=preg_replace_callback($pattern,function($m) use($dep,&$counts){
                $counts[$dep]++;
                return $m[1]." dirname(__DIR__) . '/".$dep."';";
            },$content);
        }
    }
    return $content;
}

function discover_literal_callers(string $root,array $scanExcludeTop,array $names): array {
    $refs=[]; foreach($names as $n) $refs[$n]=[];
    $stack=[$root];
    $allowed=['php','html','htm','js','json'];

    while($stack){
        $dir=array_pop($stack);
        $entries=@scandir($dir);
        if(!is_array($entries)) continue;

        foreach($entries as $entry){
            if($entry==='.'||$entry==='..') continue;
            $full=$dir.DIRECTORY_SEPARATOR.$entry;
            $relative=rel_path($root,$full);

            if(substr_count(str_replace('\\','/',$relative),'/')===0 && is_dir($full) && in_array($entry,$scanExcludeTop,true)) continue;
            if(is_link($full)) continue;

            if(is_dir($full)){ $stack[]=$full; continue; }
            if(!is_file($full)) continue;

            $ext=strtolower(pathinfo($entry,PATHINFO_EXTENSION));
            if(!in_array($ext,$allowed,true)) continue;

            // Do not treat the 11 source files or this installer as callers.
            if(isset($names[$entry]) && dirname($full)===$root) continue;
            if($entry===basename(__FILE__)) continue;

            $content=@file_get_contents($full);
            if($content===false) continue;

            foreach(array_keys($names) as $name){
                if(strpos($content,$name)!==false) $refs[$name][]=$relative;
            }
        }
    }

    foreach($refs as $name=>$list){
        $list=array_values(array_unique($list));
        sort($list,SORT_NATURAL|SORT_FLAG_CASE);
        $refs[$name]=$list;
    }
    return $refs;
}

function discover_dynamic_team_routes(string $root): array {
    $path=full_path($root,'team.php');
    if(!is_file($path)) return [];

    $content=@file_get_contents($path);
    if($content===false) return [];

    $hits=[];
    $patterns=[
        'Fees concat' => '/\$\w+\s*\.\s*[\'"]_Fees\.php[\'"]/i',
        'Rules concat' => '/\$\w+\s*\.\s*[\'"]_Rules\.php[\'"]/i',
        'Fees interpolation' => '/[\'"][^\'"]*\{\$\w+\}_Fees\.php[^\'"]*[\'"]/i',
        'Rules interpolation' => '/[\'"][^\'"]*\{\$\w+\}_Rules\.php[^\'"]*[\'"]/i',
    ];

    foreach($patterns as $kind=>$pattern){
        if(preg_match_all($pattern,$content,$m)){
            foreach($m[0] as $match){
                if(stripos($match,'league_info/')!==false) continue;
                $hits[]=['kind'=>$kind,'match'=>$match];
            }
        }
    }
    return $hits;
}

function rewrite_dynamic_team_routes(string $content,array &$changes): string {
    $changes=[];

    // $variable . '_Fees.php' / '_Rules.php'
    $content=preg_replace_callback(
        '/(\$\w+\s*\.\s*)([\'"])(_Fees|_Rules)\.php\2/i',
        function($m) use (&$changes){
            $old=$m[0];
            $new="'league_info/' . ".$m[1].$m[2].$m[3].".php".$m[2];
            $changes[]=['old'=>$old,'new'=>$new];
            return $new;
        },
        $content
    );

    // "{$variable}_Fees.php" / "{$variable}_Rules.php", optionally with leading slash.
    $content=preg_replace_callback(
        '/([\'"])(\/?)(\{\$\w+\}_(?:Fees|Rules)\.php)\1/i',
        function($m) use (&$changes){
            if(stripos($m[3],'league_info/')!==false) return $m[0];
            $prefix=$m[2]==='/'?'/league_info/':'league_info/';
            $new=$m[1].$prefix.$m[3].$m[1];
            $changes[]=['old'=>$m[0],'new'=>$new];
            return $new;
        },
        $content
    );

    return $content;
}

function preflight(string $root,array $manifest,array $rootDependencyMap,array $localDependencyMap,array $companionDirs,array $scanExcludeTop): array {
    $rows=[]; $blocking=0; $matched=0; $depsReady=0; $destClear=0; $bytes=0;
    $destDir=full_path($root,MRL_DEST_DIR);

    $rows[]=['check'=>'Production root','status'=>is_dir($root)?'PASS':'FAIL','detail'=>$root];
    if(!is_dir($root)) $blocking++;

    $rows[]=['check'=>'Destination folder','status'=>is_dir($destDir)?'INFO':'PASS',
        'detail'=>is_dir($destDir)?'/league_info/ already exists; target filename collisions are checked below.':'/league_info/ does not yet exist.'];

    foreach($manifest as $name=>$meta){
        $src=full_path($root,$name);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

        if(!is_file($src)){
            $rows[]=['check'=>'Source baseline','status'=>'FAIL','detail'=>$name.' is missing.'];
            $blocking++;
            continue;
        }

        $size=@filesize($src); $mtime=@filemtime($src);
        $size=$size===false?-1:(int)$size;
        $mtime=$mtime===false?-1:(int)$mtime;

        if($size!==(int)$meta['size'] || $mtime!==(int)$meta['mtime']){
            $rows[]=['check'=>'Source baseline','status'=>'FAIL','detail'=>$name.' changed since discovery scan.'];
            $blocking++;
            continue;
        }

        $matched++; $bytes+=$size;

        $content=@file_get_contents($src);
        $ok=($content!==false);

        foreach($rootDependencyMap[$name] as $dep){
            if(!$ok || count_literal_dependency($content,$dep)<1){ $ok=false; break; }
        }
        foreach(($localDependencyMap[$name]??[]) as $dep){
            if(!$ok || count_literal_dependency($content,$dep)<1){ $ok=false; break; }
        }

        if($ok) $depsReady++;
        else {
            $rows[]=['check'=>'Dependency readiness','status'=>'FAIL','detail'=>$name.' does not match expected dependencies.'];
            $blocking++;
        }

        if(file_exists($dest)){
            $rows[]=['check'=>'Destination collision','status'=>'FAIL','detail'=>MRL_DEST_DIR.'/'.$name.' already exists.'];
            $blocking++;
        } else $destClear++;
    }

    $rows[]=['check'=>'11-file source manifest','status'=>$matched===count($manifest)?'PASS':'FAIL',
        'detail'=>$matched.' of '.count($manifest).' exact baselines match; '.fmt_bytes($bytes).'.'];
    if($matched!==count($manifest)) $blocking++;

    $rows[]=['check'=>'Dependency repair readiness','status'=>$depsReady===count($manifest)?'PASS':'FAIL',
        'detail'=>$depsReady.' of '.count($manifest).' files match expected include/require structure.'];
    if($depsReady!==count($manifest)) $blocking++;

    $rows[]=['check'=>'Destination filenames','status'=>$destClear===count($manifest)?'PASS':'FAIL',
        'detail'=>$destClear.' of '.count($manifest).' target filenames are clear.'];
    if($destClear!==count($manifest)) $blocking++;

    foreach($companionDirs as $dir){
        $src=full_path($root,$dir);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$dir);
        if(is_dir($src)){
            if(file_exists($dest)){
                $rows[]=['check'=>'Companion directory','status'=>'FAIL','detail'=>$dir.' exists in root but destination already exists.'];
                $blocking++;
            } else {
                $rows[]=['check'=>'Companion directory','status'=>'PASS','detail'=>$dir.' exists and will move with the historical page(s).'];
            }
        } else {
            $rows[]=['check'=>'Companion directory','status'=>'INFO','detail'=>$dir.' is not present in root; nothing to move.'];
        }
    }

    $refs=discover_literal_callers($root,$scanExcludeTop,$manifest);
    $callerFiles=[];
    foreach($refs as $name=>$list){
        foreach($list as $f) $callerFiles[$f]=true;
    }

    $rows[]=['check'=>'Literal site references','status'=>'INFO',
        'detail'=>count($callerFiles).' caller file(s) currently contain one or more of the 11 filenames and will be backed up/updated.'];

    $dynamic=discover_dynamic_team_routes($root);
    $rows[]=['check'=>'team.php dynamic Fees/Rules routing','status'=>'INFO',
        'detail'=>$dynamic ? count($dynamic).' dynamic pattern(s) detected and eligible for repair.' : 'No unprefixed dynamic Fees/Rules pattern detected; literal-reference updates will still apply.'];

    return [
        'ok'=>($blocking===0 && load_state($root)===null),
        'rows'=>$rows,
        'refs'=>$refs,
        'caller_files'=>array_keys($callerFiles),
        'dynamic_team_routes'=>$dynamic
    ];
}

function install_reorg(string $root,array $manifest,array $rootDependencyMap,array $localDependencyMap,array $companionDirs,array $scanExcludeTop): array {
    $pre=preflight($root,$manifest,$rootDependencyMap,$localDependencyMap,$companionDirs,$scanExcludeTop);
    if(!$pre['ok']) return ['ok'=>false,'message'=>'Install blocked: preflight is not fully green.','errors'=>[]];

    $destDir=full_path($root,MRL_DEST_DIR);
    $backupDir=full_path($root,MRL_BACKUP_DIR);
    if(!ensure_dir($destDir)||!ensure_dir($backupDir)){
        return ['ok'=>false,'message'=>'Could not create destination or backup directory.','errors'=>[]];
    }

    $errors=[]; $movedFiles=[]; $movedDirs=[]; $repairCounts=[]; $callerEdits=[];

    // Back up all 11 source files.
    foreach($manifest as $name=>$meta){
        $src=full_path($root,$name);
        $bak=full_path($root,MRL_BACKUP_DIR.'/sources/'.$name);
        if(!ensure_dir(dirname($bak)) || !@copy($src,$bak)){
            $errors[]='Backup failed for '.$name; break;
        }
    }

    // Back up every literal caller.
    if(!$errors){
        foreach($pre['caller_files'] as $caller){
            $src=full_path($root,$caller);
            $bak=full_path($root,MRL_BACKUP_DIR.'/callers/'.$caller);
            if(!is_file($src) || !ensure_dir(dirname($bak)) || !@copy($src,$bak)){
                $errors[]='Caller backup failed for '.$caller; break;
            }
        }
    }

    // Back up team.php separately if a dynamic repair may be needed and it isn't already a literal caller.
    $teamDynamic=!empty($pre['dynamic_team_routes']);
    if(!$errors && $teamDynamic && !in_array('team.php',$pre['caller_files'],true)){
        $src=full_path($root,'team.php');
        $bak=full_path($root,MRL_BACKUP_DIR.'/callers/team.php');
        if(!is_file($src) || !ensure_dir(dirname($bak)) || !@copy($src,$bak)){
            $errors[]='Dynamic team.php backup failed.';
        }
    }

    if($errors) return ['ok'=>false,'message'=>'Install stopped before changes because backup creation failed.','errors'=>$errors];

    // Move the 11 source files and repair root dependencies.
    foreach($manifest as $name=>$meta){
        $src=full_path($root,$name);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

        if(!@rename($src,$dest)){
            $errors[]='Move failed for '.$name; break;
        }
        $movedFiles[]=$name;

        $content=@file_get_contents($dest);
        if($content===false){ $errors[]='Could not read moved file '.$name; break; }

        $counts=[];
        $newContent=rewrite_root_dependencies($content,$rootDependencyMap[$name],$counts);

        foreach($rootDependencyMap[$name] as $dep){
            if(($counts[$dep]??0)<1){
                $errors[]='Expected dependency '.$dep.' was not rewritten in '.$name; break 2;
            }
        }

        if($newContent!==$content && !atomic_write($dest,$newContent)){
            $errors[]='Atomic rewrite failed for '.$name; break;
        }
        $repairCounts[$name]=$counts;
    }

    // Move companion directories if present.
    if(!$errors){
        foreach($companionDirs as $dir){
            $src=full_path($root,$dir);
            $dest=full_path($root,MRL_DEST_DIR.'/'.$dir);
            if(is_dir($src)){
                if(file_exists($dest) || !@rename($src,$dest)){
                    $errors[]='Companion directory move failed for '.$dir; break;
                }
                $movedDirs[]=$dir;
            }
        }
    }

    // Update all literal caller references.
    if(!$errors){
        foreach($pre['caller_files'] as $caller){
            $path=full_path($root,$caller);
            $content=@file_get_contents($path);
            if($content===false){ $errors[]='Could not read caller '.$caller; break; }

            $changed=$content; $changes=0;
            foreach(array_keys($manifest) as $name){
                $pattern='#(?<!'.preg_quote(MRL_DEST_DIR.'/','#').')'.preg_quote($name,'#').'#';
                $changed=preg_replace($pattern,MRL_DEST_DIR.'/'.$name,$changed,-1,$count);
                $changes+=$count;
            }

            if($changes>0){
                if(!atomic_write($path,$changed)){ $errors[]='Caller update failed for '.$caller; break; }
                $callerEdits[$caller]=$changes;
            }
        }
    }

    // Repair dynamic team.php Fees/Rules route if detected.
    if(!$errors && $teamDynamic){
        $path=full_path($root,'team.php');
        $content=@file_get_contents($path);
        if($content===false){
            $errors[]='Could not read team.php for dynamic route repair.';
        } else {
            $changes=[];
            $changed=rewrite_dynamic_team_routes($content,$changes);
            if($changes && $changed!==$content){
                if(!atomic_write($path,$changed)) $errors[]='Dynamic team.php route update failed.';
                else $callerEdits['team.php dynamic']=count($changes);
            }
        }
    }

    if($errors){
        // Restore callers from backups.
        $callerRestore=$pre['caller_files'];
        if($teamDynamic && !in_array('team.php',$callerRestore,true)) $callerRestore[]='team.php';
        foreach($callerRestore as $caller){
            $bak=full_path($root,MRL_BACKUP_DIR.'/callers/'.$caller);
            $dest=full_path($root,$caller);
            if(is_file($bak)){ ensure_dir(dirname($dest)); @copy($bak,$dest); }
        }

        // Restore companion directories.
        foreach(array_reverse($movedDirs) as $dir){
            $src=full_path($root,MRL_DEST_DIR.'/'.$dir);
            $dest=full_path($root,$dir);
            if(is_dir($src) && !file_exists($dest)) @rename($src,$dest);
        }

        // Restore source files from backups and remove relocated copies.
        foreach($manifest as $name=>$meta){
            $bak=full_path($root,MRL_BACKUP_DIR.'/sources/'.$name);
            $orig=full_path($root,$name);
            $dest=full_path($root,MRL_DEST_DIR.'/'.$name);
            if(is_file($bak)){ @copy($bak,$orig); @touch($orig,(int)$meta['mtime']); }
            if(is_file($dest)) @unlink($dest);
        }

        @rmdir($destDir);

        return ['ok'=>false,'message'=>'Install failed; automatic rollback was attempted.','errors'=>$errors];
    }

    $state=[
        'tool'=>'MRL League Information Relocation Installer',
        'version'=>MRL_INSTALLER_VERSION,
        'installed_at'=>date(DATE_ATOM),
        'destination'=>MRL_DEST_DIR,
        'moved_files'=>array_keys($manifest),
        'moved_companion_dirs'=>$movedDirs,
        'repair_counts'=>$repairCounts,
        'caller_edits'=>$callerEdits,
        'caller_files'=>$pre['caller_files'],
        'dynamic_team_route_repaired'=>$teamDynamic
    ];

    if(!save_state($root,$state)){
        return ['ok'=>false,'message'=>'Files installed, but writing installer state failed. Keep backups.','errors'=>['Could not write state file.']];
    }

    return ['ok'=>true,'message'=>'League Information files moved and repaired successfully.','errors'=>[]];
}

function rollback_reorg(string $root,array $manifest,array $companionDirs): array {
    $state=load_state($root);
    if(!$state) return ['ok'=>false,'message'=>'Rollback state not found.','errors'=>[]];

    $errors=[];

    foreach(($state['caller_files']??[]) as $caller){
        $bak=full_path($root,MRL_BACKUP_DIR.'/callers/'.$caller);
        $dest=full_path($root,$caller);
        if(is_file($bak)){ ensure_dir(dirname($dest)); if(!@copy($bak,$dest)) $errors[]='Could not restore caller '.$caller; }
    }

    if(!empty($state['dynamic_team_route_repaired']) && !in_array('team.php',$state['caller_files']??[],true)){
        $bak=full_path($root,MRL_BACKUP_DIR.'/callers/team.php');
        $dest=full_path($root,'team.php');
        if(is_file($bak) && !@copy($bak,$dest)) $errors[]='Could not restore team.php';
    }

    foreach(array_reverse($state['moved_companion_dirs']??[]) as $dir){
        $src=full_path($root,MRL_DEST_DIR.'/'.$dir);
        $dest=full_path($root,$dir);
        if(is_dir($src)){
            if(file_exists($dest) || !@rename($src,$dest)) $errors[]='Could not restore companion directory '.$dir;
        }
    }

    foreach($manifest as $name=>$meta){
        $bak=full_path($root,MRL_BACKUP_DIR.'/sources/'.$name);
        $orig=full_path($root,$name);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

        if(!is_file($bak) || !@copy($bak,$orig)){
            $errors[]='Could not restore '.$name; continue;
        }

        @touch($orig,(int)$meta['mtime']);
        if(is_file($dest) && !@unlink($dest)) $errors[]='Could not remove relocated '.$name;
    }

    $destDir=full_path($root,MRL_DEST_DIR);
    if(is_dir($destDir)) @rmdir($destDir);

    if(!$errors){
        @unlink(state_path($root));
        return ['ok'=>true,'message'=>'Rollback completed. Original League Information files and callers restored.','errors'=>[]];
    }

    return ['ok'=>false,'message'=>'Rollback incomplete. Leave backups in place.','errors'=>$errors];
}

function postflight(string $root,array $manifest,array $rootDependencyMap): array {
    $rows=[]; $ok=true; $destGood=0; $rootGone=0; $repairGood=0;

    foreach($manifest as $name=>$meta){
        $src=full_path($root,$name);
        $dest=full_path($root,MRL_DEST_DIR.'/'.$name);

        if(!is_file($src)) $rootGone++;

        if(is_file($dest)){
            $destGood++;
            $content=@file_get_contents($dest);
            $depsOk=($content!==false);
            foreach($rootDependencyMap[$name] as $dep){
                if(!$depsOk || strpos($content,"dirname(__DIR__) . '/".$dep."'")===false){
                    $depsOk=false; break;
                }
            }
            if($depsOk) $repairGood++;
        }
    }

    $rows[]=['check'=>'Relocated files present','status'=>$destGood===count($manifest)?'PASS':'FAIL',
        'detail'=>$destGood.' of '.count($manifest).' files exist under /'.MRL_DEST_DIR.'/'];
    if($destGood!==count($manifest)) $ok=false;

    $rows[]=['check'=>'Old root copies removed','status'=>$rootGone===count($manifest)?'PASS':'FAIL',
        'detail'=>$rootGone.' of '.count($manifest).' old root paths are absent.'];
    if($rootGone!==count($manifest)) $ok=false;

    $rows[]=['check'=>'PHP dependency repairs','status'=>$repairGood===count($manifest)?'PASS':'FAIL',
        'detail'=>$repairGood.' of '.count($manifest).' relocated files have all expected root dependencies repaired.'];
    if($repairGood!==count($manifest)) $ok=false;

    $state=load_state($root);
    foreach(($state['moved_companion_dirs']??[]) as $dir){
        $present=is_dir(full_path($root,MRL_DEST_DIR.'/'.$dir)) && !is_dir(full_path($root,$dir));
        $rows[]=['check'=>'Companion directory','status'=>$present?'PASS':'FAIL',
            'detail'=>$dir.($present?' moved under /league_info/.':' did not verify cleanly.')];
        if(!$present) $ok=false;
    }

    $fees=is_file(full_path($root,MRL_DEST_DIR.'/2026_Fees.php'));
    $rules=is_file(full_path($root,MRL_DEST_DIR.'/2026_Rules.php'));
    $rows[]=['check'=>'Active 2026 League Info','status'=>($fees&&$rules)?'PASS':'FAIL',
        'detail'=>'2026_Fees.php '.($fees?'present':'missing').' / 2026_Rules.php '.($rules?'present':'missing').'.'];
    if(!$fees||!$rules) $ok=false;

    return ['ok'=>$ok,'rows'=>$rows];
}

$action=(string)($_POST['action']??'');
$result=null;

if($action==='install'){
    if(($_POST['confirm_install']??'')!=='yes'){
        $result=['ok'=>false,'message'=>'Install not started: confirmation box was not checked.','errors'=>[]];
    } else {
        $result=install_reorg($root,$manifest,$rootDependencyMap,$localDependencyMap,$companionDirs,$scanExcludeTop);
    }
} elseif($action==='rollback'){
    $result=rollback_reorg($root,$manifest,$companionDirs);
}

$state=load_state($root);
$pre=$state?null:preflight($root,$manifest,$rootDependencyMap,$localDependencyMap,$companionDirs,$scanExcludeTop);
$post=$state?postflight($root,$manifest,$rootDependencyMap):null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL League Information Reorganization</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1180px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.banner{padding:11px 13px;margin-bottom:11px;border:1px solid #3f8bc2;border-radius:10px;background:#15354d;color:#e8f5ff;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.card{padding:11px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:20px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
.status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
.pass{background:#17613a;border:1px solid #55db8b;color:#e8fff1}
.info{background:#4a3813;border:1px solid #d8aa49;color:#ffe6a7}
.fail{background:#5b2323;border:1px solid #e77a7a;color:#ffe4e4}
.actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
button{min-height:38px;padding:8px 14px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.install{background:#2f7f53}.rollback{background:#b46d22}.refresh{background:#2c6f9e}
button:disabled{background:#5a5f5d;color:#b9b9b9;cursor:not-allowed;opacity:.7}
.confirm{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid #5a6a62;border-radius:8px;background:#151a18}
.result-ok{border-color:#2f9a61;background:#103b27}.result-bad{border-color:#a65353;background:#3d1d1d}
.linkgrid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
.linkcard{padding:8px 10px;border:1px solid #3f4945;border-radius:8px;background:#151917}
.linkcard a{color:#63c5ff;text-decoration:none;font-weight:700}
.exist{color:#8fe0a9;font-size:12px}.missing{color:#ff9c9c;font-size:12px}
code{color:#f8d89a}
@media(max-width:900px){.grid,.linkgrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">

<h1>MRL League Information Reorganization</h1>

<div class="banner">
Moves the 11 discovered Rules / Fees / Schedule files into <code>/league_info/</code>,
repairs their PHP dependencies, moves historical companion folders when present,
and updates literal site references. No WordPress database writes are performed.
</div>

<div class="panel">
<h2>Plan</h2>
<div class="grid">
<div class="card"><span class="small">League Info files</span><span class="value">11</span></div>
<div class="card"><span class="small">Active 2026 files</span><span class="value">2</span></div>
<div class="card"><span class="small">Destination</span><span class="value" style="font-size:16px">/league_info/</span></div>
<div class="card"><span class="small">WP database writes</span><span class="value">0</span></div>
</div>
</div>

<?php if($result): ?>
<div class="panel <?php echo !empty($result['ok'])?'result-ok':'result-bad'; ?>">
<h2>Result</h2>
<p><strong><?php echo !empty($result['ok'])?'PASS':'ATTENTION'; ?></strong> — <?php echo h($result['message']); ?></p>
<?php if(!empty($result['errors'])): ?><ul><?php foreach($result['errors'] as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>
<?php endif; ?>

<div class="panel">
<h2><?php echo $state?'Postflight / Result':'Preflight / Result'; ?></h2>
<table><tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php
$rows=$state?$post['rows']:$pre['rows'];
foreach($rows as $r):
$cls=$r['status']==='PASS'?'pass':($r['status']==='FAIL'?'fail':'info');
?>
<tr><td><?php echo h($r['check']); ?></td><td><span class="status <?php echo h($cls); ?>"><?php echo h($r['status']); ?></span></td><td><?php echo h($r['detail']); ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Action</h2>
<?php if($state): ?>
<p>The relocation state is active. Test the links below. Rollback remains available.</p>
<form method="post">
<input type="hidden" name="action" value="rollback">
<div class="actions">
<button class="rollback" type="submit">Rollback League Info Reorganization</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Postflight</button>
</div>
</form>
<?php else: ?>
<form method="post">
<input type="hidden" name="action" value="install">
<label class="confirm">
<input type="checkbox" name="confirm_install" value="yes" id="confirmInstall">
I reviewed the preflight and want to move/repair the League Information files.
</label>
<div class="actions" style="margin-top:10px">
<button class="install" type="submit" id="installButton" disabled>Install League Info Reorganization</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Preflight</button>
</div>
<p class="small"><?php echo $pre['ok']?'Preflight has no blocking failures. Check the confirmation box to enable Install.':'Install is blocked because one or more preflight checks failed.'; ?></p>
</form>
<?php endif; ?>
</div>

<div class="panel">
<h2>Verification Links</h2>
<div class="linkgrid">
<?php foreach(array_keys($manifest) as $name):
$exists=is_file(full_path($root,MRL_DEST_DIR.'/'.$name));
$href='/'.MRL_DEST_DIR.'/'.rawurlencode($name);
?>
<div class="linkcard">
<a href="<?php echo h($href); ?>" target="_blank" rel="noopener"><?php echo h($name); ?></a>
<div class="<?php echo $exists?'exist':'missing'; ?>"><?php echo $exists?'FILE EXISTS':'NOT MOVED YET'; ?></div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="panel small">
Backups: <code><?php echo h(MRL_BACKUP_DIR); ?>/</code><br>
The WordPress Team Chart database-link migration remains a separate later step.
</div>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 1:15:00 am EDT
</div>

</div>

<script>
(function(){
'use strict';
var c=document.getElementById('confirmInstall');
var b=document.getElementById('installButton');
var ok=<?php echo (!$state && $pre && $pre['ok'])?'true':'false'; ?>;
function sync(){ if(b) b.disabled=!(ok && c && c.checked); }
if(c) c.addEventListener('change',sync);
sync();
}());
</script>
</body>
</html>
