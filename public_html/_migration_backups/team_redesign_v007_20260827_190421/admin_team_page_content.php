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