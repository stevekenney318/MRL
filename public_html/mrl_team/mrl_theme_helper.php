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