<?php
declare(strict_types=1);

/**
 * install_mrl_team_redesign.php
 *
 * VERSION: v006
 * LAST MODIFIED: 8/27/2026 6:33:12 pm
 *
 * PURPOSE:
 * Organize redesign support files under /mrl_team/, expand the Content Manager
 * to all four editable panels with up/down ordering, and polish Light theme.
 *
 * EXPECTED INPUT:
 * - team_redesign.php VERSION: v005
 * - profile_redesign.php VERSION: v001
 *
 * OUTPUT:
 * - team_redesign.php VERSION: v006
 * - profile_redesign.php VERSION: v002
 * - /mrl_team/mrl_theme_helper.php VERSION: v002
 * - /mrl_team/admin_team_page_content.php VERSION: v002
 * - /mrl_team/mrl_team_page_content.json schema_version 2
 *
 * SAFETY:
 * - production team.php is never written
 * - production profile.php is never written
 * - existing redesign/support files are backed up before replacement
 * - root support files are removed only after successful relocated writes
 *
 * CHANGELOG:
 *
 * v006 (8/27/2026 6:33:12 pm)
 * - ORGANIZATION: moves JSON/helper/content manager into /mrl_team/.
 * - ADMIN: all four panels are editable; Manager action stays fixed.
 * - ADMIN: adds up/down ordering controls.
 * - THEME: fixes Light theme contrast/readability.
 * - PRESERVE: chart presentation and production team.php/profile.php.
 */

date_default_timezone_set('America/New_York');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$base = __DIR__;
$team = $base . '/team_redesign.php';
$profile = $base . '/profile_redesign.php';
$prodTeam = $base . '/team.php';
$prodProfile = $base . '/profile.php';
$oldJson = $base . '/mrl_team_page_content.json';
$oldHelper = $base . '/mrl_theme_helper.php';
$oldManager = $base . '/admin_team_page_content.php';
$supportDir = $base . '/mrl_team';
$newJson = $supportDir . '/mrl_team_page_content.json';
$newHelper = $supportDir . '/mrl_theme_helper.php';
$newManager = $supportDir . '/admin_team_page_content.php';

function ih(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function status_row(string $label, bool $ok, string $detail=''): void {
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
        $messages[] = 'Could not create backup directory.';
        return false;
    }
    if (!copy($path, $dir . '/' . basename($path))) {
        $messages[] = 'Could not back up ' . basename($path) . '.';
        return false;
    }
    $messages[] = 'Backed up ' . basename($path) . '.';
    return true;
}

function migrate_json(array $data): array {
    $defaults = [
        'admin_league_panel' => [
            'title' => 'League & Team',
            'items' => [
                ['label'=>'Weekly Standings / scoring - Beta','url'=>'/race_results/weekly_standings.php','enabled'=>true,'new_tab'=>true],
                ['label'=>'Setup Year / Pick Window','url'=>'/admin_setup.php','enabled'=>true,'new_tab'=>true],
                ['label'=>'Paid Status by year','url'=>'/Paid_Status_Year.php','enabled'=>true,'new_tab'=>true],
                ['label'=>'View Team page as alternate user','url'=>'/team_view_as.php','enabled'=>true,'new_tab'=>true],
                ['label'=>'Email addresses','url'=>'/email.php','enabled'=>true,'new_tab'=>true],
                ['label'=>'Special user authorization','url'=>'/change_user_auth.php','enabled'=>true,'new_tab'=>true],
                ['label'=>'Approve LP as regular segment pick','url'=>'/admin_pick_adjustment.php','enabled'=>true,'new_tab'=>true],
                ['label'=>'Add drivers for a new year','url'=>'/addDrivers.php','enabled'=>true,'new_tab'=>true],
                ['label'=>'Current segment chart by entry time','url'=>'/current_segment_chart_by_entry_time.php','enabled'=>true,'new_tab'=>true],
            ],
        ],
        'admin_hosting_panel' => [
            'title' => 'Hosting & Infrastructure',
            'items' => [
                ['label'=>'phpMyAdmin (Hostinger)','url'=>'https://hpanel.hostinger.com/','enabled'=>true,'new_tab'=>true],
                ['label'=>'WP Admin','url'=>'/wp-admin/','enabled'=>true,'new_tab'=>true],
                ['label'=>'Hostinger Backups','url'=>'https://hpanel.hostinger.com/','enabled'=>true,'new_tab'=>true],
                ['label'=>'Hostinger hPanel','url'=>'https://hpanel.hostinger.com/','enabled'=>true,'new_tab'=>true],
            ],
        ],
        'league_panel' => ['title'=>'League Information','items'=>[]],
        'team_panel' => ['title'=>'Team Menu','items'=>[]],
    ];
    foreach ($defaults as $key=>$value) if (!isset($data[$key])) $data[$key] = $value;
    $data['schema_version'] = 2;
    $data['updated_at'] = date('Y-m-d H:i:s');
    return $data;
}

function build_team_v006(string $src): array {
    if (strpos($src, 'VERSION: v005') === false) return [false,'','Expected team_redesign.php v005.'];
    $w = $src;
    $w = preg_replace('/VERSION:\s*v005/', 'VERSION: v006', $w, 1, $c1);
    $w = preg_replace('/LAST MODIFIED:\s*8\/27\/2026 5:27:00 pm/', 'LAST MODIFIED: 8/27/2026 6:33:12 pm', $w, 1, $c2);
    if ($c1 !== 1 || $c2 !== 1) return [false,'','Could not update team header safely.'];

    $change = " *\n * v006 (8/27/2026 6:33:12 pm)\n"
        . " * - ORGANIZATION: uses /mrl_team/ for JSON/helper/content manager.\n"
        . " * - ADMIN: all four panels JSON-driven; Manager control fixed.\n"
        . " * - THEME: Light contrast/readability cleanup.\n"
        . " * - PRESERVE: charts and production pages untouched.\n";
    $w = preg_replace('/(\* CHANGELOG:\R)/', '$1'.$change, $w, 1, $cc);
    if ($cc !== 1) return [false,'','Could not add team changelog.'];

    $w = str_replace("require_once __DIR__ . '/mrl_theme_helper.php';", "require_once __DIR__ . '/mrl_team/mrl_theme_helper.php';", $w, $hc);
    if ($hc !== 1) return [false,'','Theme helper include not found.'];

    $w2 = str_replace("__DIR__ . '/mrl_team_page_content.json'", "__DIR__ . '/mrl_team/mrl_team_page_content.json'", $w, $jc);
    if ($jc < 1) return [false,'','Team JSON path not found.'];
    $w = $w2;

    $pattern = '~<details class="mrl-rd-admin-wrap">\s*<summary>Admin Menu</summary>\s*<div class="mrl-rd-admin-grid">.*?</div>\s*</details>~s';
    $replacement = <<<'HTML'
<details class="mrl-rd-admin-wrap">
            <summary>Admin Menu</summary>
            <div class="mrl-rd-admin-fixed-control">
                <a href="/mrl_team/admin_team_page_content.php" target="_blank" rel="noopener noreferrer">Manage Team Page Content</a>
            </div>
            <div class="mrl-rd-admin-grid">
                <section class="mrl-rd-card">
                    <div class="mrl-rd-card-title"><?php echo teampage_h((string)($teamPageContent['admin_league_panel']['title'] ?? 'League & Team')); ?></div>
                    <div class="mrl-rd-card-body"><?php teampage_redesign_render_links($teamPageContent['admin_league_panel'] ?? [], (string)$raceYear); ?></div>
                </section>
                <section class="mrl-rd-card">
                    <div class="mrl-rd-card-title"><?php echo teampage_h((string)($teamPageContent['admin_hosting_panel']['title'] ?? 'Hosting & Infrastructure')); ?></div>
                    <div class="mrl-rd-card-body"><?php teampage_redesign_render_links($teamPageContent['admin_hosting_panel'] ?? [], (string)$raceYear); ?></div>
                </section>
            </div>
        </details>
HTML;
    $w2 = preg_replace($pattern, $replacement, $w, 1, $ac);
    if ($ac !== 1 || !is_string($w2)) return [false,'','Could not replace Admin block safely.'];
    $w = $w2;

    $css = <<<'CSS'

        /* team_redesign.php v006 - Light polish / fixed admin control */
        .mrl-rd-admin-fixed-control{margin:12px 14px 0;padding:10px 14px;border:1px solid var(--mrl-rd-border);border-radius:10px;background:rgba(0,0,0,.16)}
        .mrl-rd-admin-fixed-control a{color:var(--mrl-rd-blue)!important;text-decoration:none!important;font-weight:800}
        html.mrl-theme-light body{color:#202020!important}
        html.mrl-theme-light .mrl-rd-sticky,html.mrl-theme-light .mrl-rd-sticky *{color:#fff7e6!important}
        html.mrl-theme-light .mrl-rd-user,html.mrl-theme-light .mrl-rd-user *{color:#fff!important}
        html.mrl-theme-light .dropdown-menu{background:#242424!important;border-color:#555!important}
        html.mrl-theme-light .dropdown-menu a{color:#f2f2f2!important}
        html.mrl-theme-light .dropdown-menu a:hover{color:#fff!important;background:#333!important}
        html.mrl-theme-light .mrl-rd-subtitle,html.mrl-theme-light .mrl-rd-clock,html.mrl-theme-light .mrl-rd-clock *{color:#fff3d5!important}
        html.mrl-theme-light .mrl-rd-greeting{color:#8b5b00!important}
        html.mrl-theme-light .mrl-rd-admin-wrap{background:rgba(255,255,255,.58)!important;color:#202020!important}
        html.mrl-theme-light .mrl-rd-admin-wrap>summary{color:#8b5b00!important}
        html.mrl-theme-light .mrl-rd-card{background:rgba(255,255,255,.90)!important;color:#202020!important}
        html.mrl-theme-light .mrl-rd-card-title{background:rgba(244,244,244,.98)!important;color:#8b5b00!important}
        html.mrl-theme-light .mrl-rd-list li::marker{color:#555!important}
        html.mrl-theme-light .mrl-rd-list a{color:#006eaa!important}
        html.mrl-theme-light .mrl-rd-admin-fixed-control{background:rgba(255,255,255,.78)!important}
        html.mrl-theme-light .mrl-rd-admin-fixed-control a{color:#006eaa!important}
        html.mrl-theme-light .mrl-rd-notice-panel{background:rgba(255,255,255,.88)!important;color:#6f4a00!important}
        html.mrl-theme-light .mrl-rd-notice-panel a{color:#006eaa!important}
        html.mrl-theme-light .mrl-previous-years summary{color:#8b5b00!important;opacity:1!important}
CSS;
    $p = strpos($w, '</style>');
    if ($p === false) return [false,'','Team style block not found.'];
    $w = substr_replace($w, $css."\n    </style>", $p, strlen('</style>'));

    foreach (['VERSION: v006','/mrl_team/mrl_theme_helper.php','/mrl_team/admin_team_page_content.php','admin_league_panel','admin_hosting_panel'] as $guard) {
        if (strpos($w, $guard) === false) return [false,'','Team guard failed: '.$guard];
    }
    return [true,$w,'team_redesign.php v006 ready.'];
}

function build_profile_v002(string $src): array {
    if (strpos($src, 'VERSION: v001') === false) return [false,'','Expected profile_redesign.php v001.'];
    $w = $src;
    $w = preg_replace('/VERSION:\s*v001/', 'VERSION: v002', $w, 1, $c1);
    $w = preg_replace('/LAST MODIFIED:\s*8\/27\/2026 5:27:00 pm/', 'LAST MODIFIED: 8/27/2026 6:33:12 pm', $w, 1, $c2);
    if ($c1 !== 1 || $c2 !== 1) return [false,'','Could not update profile header.'];
    $w = str_replace("require_once __DIR__ . '/mrl_theme_helper.php';", "require_once __DIR__ . '/mrl_team/mrl_theme_helper.php';", $w, $hc);
    if ($hc !== 1) return [false,'','Profile helper include not found.'];
    $css = <<<'CSS'

html.mrl-theme-light .header,html.mrl-theme-light .header *{color:#fff7e6!important}
html.mrl-theme-light .header a{color:#fff!important}
html.mrl-theme-light .theme-option{background:rgba(255,255,255,.68)!important;color:#202020!important}
html.mrl-theme-light .message{color:#6f4a00!important}
CSS;
    $p = strpos($w, '</style>');
    if ($p === false) return [false,'','Profile style block not found.'];
    $w = substr_replace($w, $css."\n</style>", $p, strlen('</style>'));
    return [true,$w,'profile_redesign.php v002 ready.'];
}

$themeHelper = <<<'PHPFILE'
<?php
declare(strict_types=1);
/**
 * mrl_theme_helper.php
 * VERSION: v002
 * LAST MODIFIED: 8/27/2026 6:33:12 pm
 */
function mrl_theme_options(): array { return ['cars'=>'Cars','starry-night'=>'Starry Night','dark'=>'Dark','light'=>'Light']; }
function mrl_theme_normalize(string $theme): string { $theme=strtolower(trim($theme)); return array_key_exists($theme,mrl_theme_options())?$theme:'dark'; }
function mrl_theme_get(PDO $dbo,int $userID): string {
    if($userID<=0)return 'dark';
    try{$s=$dbo->prepare("SELECT team_theme FROM mrl_user_preferences WHERE userID=:uid LIMIT 1");$s->execute([':uid'=>$userID]);$r=$s->fetch(PDO::FETCH_ASSOC);return is_array($r)?mrl_theme_normalize((string)($r['team_theme']??'dark')):'dark';}catch(Throwable $e){return 'dark';}
}
function mrl_theme_save(PDO $dbo,int $userID,string $theme): bool {
    if($userID<=0)return false;$theme=mrl_theme_normalize($theme);
    try{$s=$dbo->prepare("INSERT INTO mrl_user_preferences (userID,team_theme,updated_at) VALUES (:uid,:theme,NOW()) ON DUPLICATE KEY UPDATE team_theme=VALUES(team_theme),updated_at=NOW()");return $s->execute([':uid'=>$userID,':theme'=>$theme]);}catch(Throwable $e){return false;}
}
PHPFILE;

$contentManager = <<<'PHPFILE'
<?php
declare(strict_types=1);
/**
 * admin_team_page_content.php
 * VERSION: v002
 * LAST MODIFIED: 8/27/2026 6:33:12 pm
 */
session_start();
require_once dirname(__DIR__) . '/class.user.php';
$user_home=new USER();
if(!$user_home->is_logged_in()){$user_home->redirect('/login.php');exit;}
require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/config_mrl.php';
$uid=(int)($_SESSION['userSession']??0);
if(!isAdmin($uid)){http_response_code(403);exit('Admin access required.');}
$contentPath=__DIR__.'/mrl_team_page_content.json';
function ah($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function load_data(string $p):array{$r=is_file($p)?file_get_contents($p):'';$d=is_string($r)?json_decode($r,true):null;return is_array($d)?$d:[];}
function clean_url(string $u):string{$u=trim($u);if($u==='')return '';if($u[0]==='/')return $u;if(preg_match('~^https?://~i',$u))return $u;return '';}
function build_panel(array $post,string $key,string $fallback):array{
    $title=trim((string)($post[$key.'_title']??$fallback));if($title==='')$title=$fallback;
    $labels=is_array($post[$key.'_label']??null)?$post[$key.'_label']:[];$urls=is_array($post[$key.'_url']??null)?$post[$key.'_url']:[];$enabled=is_array($post[$key.'_enabled']??null)?$post[$key.'_enabled']:[];$newtab=is_array($post[$key.'_new_tab']??null)?$post[$key.'_new_tab']:[];$remove=is_array($post[$key.'_remove']??null)?$post[$key.'_remove']:[];
    $items=[];$count=max(count($labels),count($urls));for($i=0;$i<$count;$i++){if(isset($remove[$i]))continue;$l=trim((string)($labels[$i]??''));$u=clean_url((string)($urls[$i]??''));if($l===''||$u==='')continue;$items[]=['label'=>$l,'url'=>$u,'enabled'=>isset($enabled[$i]),'new_tab'=>isset($newtab[$i])];}
    return ['title'=>$title,'items'=>$items];
}
if(!isset($_SESSION['atpc_csrf']))$_SESSION['atpc_csrf']=bin2hex(random_bytes(24));
$data=load_data($contentPath);$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals((string)$_SESSION['atpc_csrf'],(string)($_POST['csrf']??''))){$message='Save blocked: security token mismatch.';}
    else{$new=['schema_version'=>2,'updated_at'=>date('Y-m-d H:i:s'),'admin_league_panel'=>build_panel($_POST,'admin_league','League & Team'),'admin_hosting_panel'=>build_panel($_POST,'admin_hosting','Hosting & Infrastructure'),'league_panel'=>build_panel($_POST,'league','League Information'),'team_panel'=>build_panel($_POST,'team','Team Menu')];$bd=dirname(__DIR__).'/_migration_backups/team_page_content_'.date('Ymd_His');$bk=true;if(is_file($contentPath))$bk=(is_dir($bd)||mkdir($bd,0755,true))&&copy($contentPath,$bd.'/mrl_team_page_content.json');$j=json_encode($new,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);$ok=$bk&&is_string($j)&&file_put_contents($contentPath,$j.PHP_EOL,LOCK_EX)!==false;$message=$ok?'Team page content saved. Existing JSON was backed up first.':'Save failed.';$data=load_data($contentPath);}
}
function rows(string $key,array $items):void{foreach($items as $i=>$it){echo '<tr><td class="order"><button type="button" class="mini" onclick="moveRow(this,-1)">↑</button><button type="button" class="mini" onclick="moveRow(this,1)">↓</button></td><td><input name="'.ah($key).'_label[]" value="'.ah($it['label']??'').'"></td><td><input name="'.ah($key).'_url[]" value="'.ah($it['url']??'').'"></td><td><input data-role="enabled" type="checkbox" '.(!empty($it['enabled'])?'checked':'').'></td><td><input data-role="newtab" type="checkbox" '.(!empty($it['new_tab'])?'checked':'').'></td><td><input data-role="remove" type="checkbox"></td></tr>';}}
$panels=['admin_league'=>['data'=>'admin_league_panel','heading'=>'Admin · League & Team'],'admin_hosting'=>['data'=>'admin_hosting_panel','heading'=>'Admin · Hosting & Infrastructure'],'league'=>['data'=>'league_panel','heading'=>'League Information'],'team'=>['data'=>'team_panel','heading'=>'Team Menu']];
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manage Team Page Content</title><style>
*{box-sizing:border-box}body{margin:0;background:#151515;color:#eee;font-family:Tahoma,Verdana,Segoe UI,sans-serif}.wrap{width:94%;max-width:1400px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:18px;margin-bottom:16px}h1,h2{color:#efc982}a{color:#76cfff}.note{padding:11px;border:1px solid #555;border-radius:9px;background:#171717}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border-bottom:1px solid #444;padding:7px;text-align:left}td input:not([type=checkbox]){width:100%}input{padding:7px;background:#111;color:#eee;border:1px solid #666;border-radius:5px}.panel-title{width:100%;max-width:560px}button{padding:10px 17px;border:1px solid #5a7fb5;border-radius:8px;background:#1466c9;color:#fff;font-weight:800;cursor:pointer}.mini{padding:3px 8px;margin:1px;background:#2b2b2b;border-color:#777}.order{width:82px;white-space:nowrap}.message{margin-top:12px;padding:10px;border:1px solid #777;border-radius:8px;color:#efc982}.save{position:sticky;bottom:8px}
</style></head><body><div class="wrap"><div class="card"><h1>Manage Team Page Content</h1><p><a href="/team_redesign.php">← Team Redesign</a></p><div class="note"><strong>Manage Team Page Content</strong> is a fixed Admin control and cannot be edited here.</div><?php if($message!==''):?><div class="message"><?php echo ah($message);?></div><?php endif;?></div><form method="post"><input type="hidden" name="csrf" value="<?php echo ah((string)$_SESSION['atpc_csrf']);?>">
<?php foreach($panels as $key=>$meta):$dk=$meta['data'];?><div class="card"><h2><?php echo ah($meta['heading']);?></h2><label>Panel title<br><input class="panel-title" name="<?php echo ah($key);?>_title" value="<?php echo ah($data[$dk]['title']??'');?>"></label><table><thead><tr><th>Order</th><th>Link text</th><th>URL</th><th>Enabled</th><th>New tab</th><th>Remove</th></tr></thead><tbody id="<?php echo ah($key);?>-rows" data-key="<?php echo ah($key);?>"><?php rows($key,is_array($data[$dk]['items']??null)?$data[$dk]['items']:[]);?></tbody></table><p>Use ↑ / ↓ to reorder.</p><button type="button" onclick="addRow('<?php echo ah($key);?>')">Add Link</button></div><?php endforeach;?><div class="card save"><button type="submit">Save Team Page Content</button></div></form></div><script>
function renumber(tb){const k=tb.dataset.key;[...tb.rows].forEach((r,i)=>{r.querySelectorAll('input').forEach(x=>{if(x.name.includes('_label['))x.name=k+'_label[]';else if(x.name.includes('_url['))x.name=k+'_url[]';else if(x.dataset.role==='enabled')x.name=k+'_enabled['+i+']';else if(x.dataset.role==='newtab')x.name=k+'_new_tab['+i+']';else if(x.dataset.role==='remove')x.name=k+'_remove['+i+']';});});}
function moveRow(b,d){const r=b.closest('tr'),tb=r.parentElement;if(d<0&&r.previousElementSibling)tb.insertBefore(r,r.previousElementSibling);else if(d>0&&r.nextElementSibling)tb.insertBefore(r.nextElementSibling,r);renumber(tb);}
function addRow(k){const tb=document.getElementById(k+'-rows'),r=tb.insertRow();r.innerHTML='<td class="order"><button type="button" class="mini" onclick="moveRow(this,-1)">↑</button><button type="button" class="mini" onclick="moveRow(this,1)">↓</button></td><td><input name="'+k+'_label[]"></td><td><input name="'+k+'_url[]"></td><td><input data-role="enabled" type="checkbox" checked></td><td><input data-role="newtab" type="checkbox" checked></td><td><input data-role="remove" type="checkbox"></td>';renumber(tb);}
document.querySelectorAll('tbody[data-key]').forEach(renumber);
</script></body></html>
PHPFILE;

$teamExists=is_file($team);$profileExists=is_file($profile);
$teamSrc=$teamExists?(string)file_get_contents($team):'';$profileSrc=$profileExists?(string)file_get_contents($profile):'';
[$teamOk,$teamV006,$teamNote]=$teamExists?build_team_v006($teamSrc):[false,'','team_redesign.php missing.'];
[$profileOk,$profileV002,$profileNote]=$profileExists?build_profile_v002($profileSrc):[false,'','profile_redesign.php missing.'];
$jsonSource=is_file($newJson)?$newJson:$oldJson;$jsonRaw=is_file($jsonSource)?(string)file_get_contents($jsonSource):'';$jsonData=$jsonRaw!==''?json_decode($jsonRaw,true):null;$jsonOk=is_array($jsonData);
$prodTeamHash=is_file($prodTeam)?hash_file('sha256',$prodTeam):'';$prodProfileHash=is_file($prodProfile)?hash_file('sha256',$prodProfile):'';
$apply=isset($_POST['apply'])&&$_POST['apply']==='1';$messages=[];$success=false;
if($apply&&$teamOk&&$profileOk&&$jsonOk){
    $backupDir=$base.'/_migration_backups/team_redesign_v006_'.date('Ymd_His');$ok=true;
    foreach([$team,$profile,$oldJson,$newJson,$oldHelper,$newHelper,$oldManager,$newManager] as $f){if(!backup_one($f,$backupDir,$messages)){$ok=false;break;}}
    if($ok&&!is_dir($supportDir)&&!mkdir($supportDir,0755,true)&&!is_dir($supportDir)){$messages[]='Could not create /mrl_team/.';$ok=false;}elseif($ok){$messages[]='Verified/created /mrl_team/ support folder.';}
    if($ok){$m=migrate_json($jsonData);$j=json_encode($m,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);if(!is_string($j)||!atomic_write($newJson,$j.PHP_EOL)){$messages[]='Could not write relocated JSON.';$ok=false;}else{$messages[]='Installed mrl_team/mrl_team_page_content.json.';}}
    if($ok){foreach([$newHelper=>$themeHelper,$newManager=>$contentManager,$profile=>$profileV002,$team=>$teamV006] as $f=>$d){if(!atomic_write($f,$d)){$messages[]='Could not write '.basename($f).'.';$ok=false;break;}$messages[]='Installed '.str_replace($base.'/','',$f).'.';}}
    if($ok&&is_file($oldJson)&&realpath($oldJson)!==realpath($newJson)){if(@unlink($oldJson))$messages[]='Removed old root JSON after relocation.';}
    if($ok&&is_file($oldHelper)&&realpath($oldHelper)!==realpath($newHelper)){if(@unlink($oldHelper))$messages[]='Removed old root theme helper after relocation.';}
    if($ok&&is_file($oldManager)&&realpath($oldManager)!==realpath($newManager)){if(@unlink($oldManager))$messages[]='Removed old root content manager after relocation.';}
    $teamUntouched=$prodTeamHash!==''&&$prodTeamHash===hash_file('sha256',$prodTeam);$profileUntouched=$prodProfileHash!==''&&$prodProfileHash===hash_file('sha256',$prodProfile);
    $messages[]=$teamUntouched?'Verified production team.php unchanged.':'ERROR: production team.php changed.';$messages[]=$profileUntouched?'Verified production profile.php unchanged.':'ERROR: production profile.php changed.';
    $success=$ok&&$teamUntouched&&$profileUntouched&&is_file($newJson)&&is_file($newHelper)&&is_file($newManager)&&strpos((string)file_get_contents($team),'VERSION: v006')!==false&&strpos((string)file_get_contents($profile),'VERSION: v002')!==false;
}
?><!doctype html><html><head><meta charset="utf-8"><title>MRL Team Redesign Installer v006</title><style>*{box-sizing:border-box}html{background:#111}body{margin:0;color:#eee;font-family:Tahoma,Verdana,Segoe UI,sans-serif}.wrap{width:94%;max-width:1200px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}h1,h2{color:#efc982}table{width:100%;border-collapse:collapse}td{padding:9px;border-bottom:1px solid #444}.ok{color:#61e493}.bad{color:#ff7777}button{padding:11px 20px;border:1px solid #5a7fb5;border-radius:9px;background:#1466c9;color:#fff;font-weight:800;cursor:pointer}code,a{color:#76cfff}li{line-height:1.45;margin-bottom:5px}</style></head><body><div class="wrap"><div class="card"><h1>MRL Team Redesign Installer v006</h1><p>Organization + Content Manager + Light Theme polish. Production <code>team.php</code> and <code>profile.php</code> remain protected.</p></div><div class="card"><h2>Preflight</h2><table><?php status_row('team_redesign.php v005',$teamOk,$teamNote);?><?php status_row('profile_redesign.php v001',$profileOk,$profileNote);?><?php status_row('Current menu JSON readable',$jsonOk,$jsonSource);?><?php status_row('Production team.php present',is_file($prodTeam),$prodTeam);?><?php status_row('Production profile.php present',is_file($prodProfile),$prodProfile);?></table></div><?php if($teamOk&&$profileOk&&$jsonOk):?><div class="card"><h2>What v006 changes</h2><ul><li>Creates <code>/public_html/mrl_team/</code> for support/config/admin files.</li><li>Moves the menu JSON, theme helper and Content Manager there.</li><li>Content Manager edits all four panels.</li><li><strong>Manage Team Page Content</strong> remains a fixed Admin control.</li><li>Adds ↑ / ↓ ordering controls.</li><li>Polishes Light theme header, clock, dropdown, Admin area, notices and Previous Years title.</li><li>Charts themselves are not restyled.</li></ul><?php if(!$apply):?><form method="post"><input type="hidden" name="apply" value="1"><button>Install v006</button></form><?php endif;?></div><?php endif;?><?php if($apply):?><div class="card"><h2>Apply Result</h2><p class="<?php echo $success?'ok':'bad';?>"><strong><?php echo $success?'SUCCESS':'FAILED';?></strong></p><ul><?php foreach($messages as $m):?><li><?php echo ih($m);?></li><?php endforeach;?></ul><?php if($success):?><p><a href="/team_redesign.php" target="_blank">Open Team Redesign v006</a></p><p><a href="/profile_redesign.php" target="_blank">Open Profile Redesign v002</a></p><p><a href="/mrl_team/admin_team_page_content.php" target="_blank">Open Content Manager v002</a></p><?php endif;?></div><?php endif;?></div></body></html>
