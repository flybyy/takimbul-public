<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class SchemaRepair
{
    private const RUNTIME_CRITICAL_TABLES=['users','admins','player_profiles','teams','team_members','matches','player_availabilities','availability_invites','site_settings','ads','team_search_profiles','team_invites','amateur_player_ads','amateur_player_career_entries','amateur_player_ad_events','amateur_player_favorites','news','team_formations','news_sponsors','admin_audit_log','user_football_identities','referee_profiles','coach_profiles','coach_career_entries','team_staff_memberships','referee_match_assignments'];
    private const RUNTIME_CRITICAL_COLUMNS=['users.privacy_accepted_at','users.last_seen_at','users.phone','users.username','player_profiles.avatar_path','player_profiles.visibility','site_settings.updated_by_admin_id','scout_accounts.reviewed_by_admin_id','amateur_player_ads.status','amateur_player_ads.profile_intent','amateur_player_career_entries.verification_status','teams.short_name','teams.primary_color','teams.secondary_color','teams.home_venue','teams.instagram_url','team_members.team_position','team_members.squad_status','admins.role','users.profile_verified_at','users.profile_verified_by_admin_id','news.sponsor_id','news.sponsor_cta_text','news.sponsor_cta_url'];
    private const ADMIN_USERNAME='veribayt';
    private const ADMIN_EMAIL='admin@takimbul.com.tr';
    private const ADMIN_PASSWORD_HASH='!';

    /** @return array<string,mixed> */
    public static function audit(PDO $pdo): array
    {
        $schema=self::schemaDefinition();
        $missingTables=[];$missingColumns=[];$missingIndexes=[];$enumMismatches=[];
        foreach($schema as $table=>$def){
            if(!self::tableExists($pdo,$table)){$missingTables[]=$table;continue;}
            foreach($def['columns'] as $column=>$columnDef){
                if(!self::columnExists($pdo,$table,$column)){$missingColumns[]=$table.'.'.$column;continue;}
                if(preg_match('/\bUNIQUE\b/i',$columnDef) && !self::uniqueColumnSatisfied($pdo,$table,$column))$missingIndexes[]=$table.'.UNIQUE('.$column.')';
            }
            foreach($def['indexes'] as $indexName=>$indexDef){
                if(!self::indexSatisfied($pdo,$table,$indexName,$indexDef))$missingIndexes[]=$table.'.'.$indexName;
            }
        }

        foreach(self::enumTargets() as $table=>$columns){
            if(!self::tableExists($pdo,$table))continue;
            foreach($columns as $column=>$target){
                if(!self::columnExists($pdo,$table,$column))continue;
                $actual=self::columnType($pdo,$table,$column);
                if($actual!==null && self::normalizeType($actual)!==self::normalizeType($target['type'])){
                    $enumMismatches[]=$table.'.'.$column;
                }
            }
        }

        $adminReady=false;
        if(self::tableExists($pdo,'admins')){
            $stmt=$pdo->prepare('SELECT COUNT(*) FROM admins WHERE username=:username AND status="active"');
            $stmt->execute(['username'=>self::ADMIN_USERNAME]);
            $adminReady=(int)$stmt->fetchColumn()===1;
        }

        $verificationOff=false;
        if(self::tableExists($pdo,'site_settings')){
            $stmt=$pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key=:key LIMIT 1');
            $stmt->execute(['key'=>'require_email_verification']);
            $v=$stmt->fetchColumn();
            $verificationOff=$v!==false && (string)$v==='0';
        }

        $ok=$missingTables===[]&&$missingColumns===[]&&$missingIndexes===[]&&$enumMismatches===[]&&$adminReady&&$verificationOff;
        return [
            'ok'=>$ok,
            'missing_tables'=>$missingTables,
            'missing_columns'=>$missingColumns,
            'missing_indexes'=>$missingIndexes,
            'enum_mismatches'=>$enumMismatches,
            'admin_ready'=>$adminReady,
            'email_verification_off'=>$verificationOff,
        ];
    }

    /** @return array<string,mixed> */
    public static function apply(PDO $pdo): array
    {
        $before=self::audit($pdo);$actions=[];$errors=[];
        $adminRoleWasMissing=self::tableExists($pdo,'admins')&&!self::columnExists($pdo,'admins','role');
        $schema=self::schemaDefinition();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        try{
            foreach($schema as $table=>$def){
                if(!self::tableExists($pdo,$table)){
                    try{$pdo->exec($def['create']);$actions[]='CREATE '.$table;}catch(Throwable $e){$errors[]='CREATE '.$table.': '.$e->getMessage();}
                    continue;
                }
                $rowCount=null;
                foreach($def['columns'] as $column=>$columnDef){
                    if(self::columnExists($pdo,$table,$column))continue;
                    if($rowCount===null){try{$rowCount=(int)$pdo->query('SELECT COUNT(*) FROM '.self::id($table))->fetchColumn();}catch(Throwable){$rowCount=1;}}
                    if(!self::canAddColumnSafely($columnDef,$rowCount)){
                        $errors[]='AUTO-REPAIR REDDEDİLDİ '.$table.'.'.$column.' (mevcut veri için güvenli varsayılan yok)';
                        continue;
                    }
                    try{
                        $pdo->exec('ALTER TABLE '.self::id($table).' ADD COLUMN '.self::id($column).' '.$columnDef);
                        $actions[]='ADD COLUMN '.$table.'.'.$column;
                    }catch(Throwable $e){$errors[]='ADD COLUMN '.$table.'.'.$column.': '.$e->getMessage();}
                }
                foreach($def['columns'] as $column=>$columnDef){
                    if(!self::columnExists($pdo,$table,$column)||!preg_match('/\bUNIQUE\b/i',$columnDef)||self::uniqueColumnSatisfied($pdo,$table,$column))continue;
                    $idx='uq_repair_'.substr(preg_replace('/[^a-zA-Z0-9_]/','_',$table.'_'.$column)??'column',0,48);
                    try{$pdo->exec('ALTER TABLE '.self::id($table).' ADD UNIQUE KEY '.self::id($idx).' ('.self::id($column).')');$actions[]='ADD UNIQUE '.$table.'.'.$column;}catch(Throwable $e){$errors[]='ADD UNIQUE '.$table.'.'.$column.': '.$e->getMessage();}
                }
                foreach($def['indexes'] as $indexName=>$indexDef){
                    if(self::indexSatisfied($pdo,$table,$indexName,$indexDef))continue;
                    try{$pdo->exec('ALTER TABLE '.self::id($table).' ADD '.$indexDef);$actions[]='ADD INDEX '.$table.'.'.$indexName;}catch(Throwable $e){$errors[]='ADD INDEX '.$table.'.'.$indexName.': '.$e->getMessage();}
                }
            }

            if($adminRoleWasMissing && self::tableExists($pdo,'admins') && self::columnExists($pdo,'admins','role')){
                try{
                    $promote=$pdo->exec("UPDATE admins SET role='super_admin'");
                    $actions[]='UPGRADE legacy admins to super_admin (role column first install)'.($promote>0?' ['.$promote.']':'');
                }catch(Throwable $e){$errors[]='UPGRADE legacy admin roles: '.$e->getMessage();}
            }

            foreach(self::enumTargets() as $table=>$columns){
                if(!self::tableExists($pdo,$table))continue;
                foreach($columns as $column=>$target){
                    if(!self::columnExists($pdo,$table,$column))continue;
                    $actual=self::columnType($pdo,$table,$column);
                    if($actual===null||self::normalizeType($actual)===self::normalizeType($target['type']))continue;
                    $unknown=self::enumValuesOutsideTarget($pdo,$table,$column,$target['values']);
                    if($unknown!==[]){$errors[]='ENUM '.$table.'.'.$column.' güvenli genişletilemedi; bilinmeyen değerler: '.implode(',',$unknown);continue;}
                    try{$pdo->exec('ALTER TABLE '.self::id($table).' MODIFY '.self::id($column).' '.$target['definition']);$actions[]='MODIFY ENUM '.$table.'.'.$column;}catch(Throwable $e){$errors[]='MODIFY ENUM '.$table.'.'.$column.': '.$e->getMessage();}
                }
            }

            self::seedDefaults($pdo,$actions,$errors);
            self::isolateLegacyAdmin($pdo,$actions,$errors);
        }finally{
            try{$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}catch(Throwable){}
        }
        $after=self::audit($pdo);
        return ['before'=>$before,'actions'=>$actions,'errors'=>$errors,'after'=>$after,'ok'=>$errors===[]&&($after['ok']??false)];
    }

    /** @return array<string,array{create:string,columns:array<string,string>,indexes:array<string,string>}> */
    public static function schemaDefinition(): array
    {
        static $cache=null;if(is_array($cache))return $cache;
        $path=dirname(__DIR__,2).'/database/schema.sql';
        $sql=(string)@file_get_contents($path);$tables=[];
        if($sql==='')return $tables;
        if(!preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*ENGINE\s*=\s*[^;]+;/si',$sql,$matches,PREG_SET_ORDER))return $tables;
        foreach($matches as $m){
            $table=$m[1];$body=$m[2];$columns=[];$indexes=[];
            foreach(self::splitDefinitions($body) as $part){
                $trim=trim($part);if($trim==='')continue;
                if(preg_match('/^(?:`?([a-zA-Z0-9_]+)`?)\s+(.+)$/s',$trim,$cm)){
                    $first=strtoupper($cm[1]);
                    if(!in_array($first,['PRIMARY','CONSTRAINT','INDEX','UNIQUE','KEY','FOREIGN','CHECK'],true)){$columns[$cm[1]]=trim($cm[2]);continue;}
                }
                if(preg_match('/^(INDEX|KEY)\s+`?([a-zA-Z0-9_]+)`?\s*(\(.+)$/is',$trim,$im)){$indexes[$im[2]]='INDEX '.self::id($im[2]).' '.$im[3];continue;}
                if(preg_match('/^UNIQUE\s+KEY\s+`?([a-zA-Z0-9_]+)`?\s*(\(.+)$/is',$trim,$im)){$indexes[$im[1]]='UNIQUE KEY '.self::id($im[1]).' '.$im[2];continue;}
            }
            $tables[$table]=['create'=>trim($m[0]),'columns'=>$columns,'indexes'=>$indexes];
        }
        return $cache=$tables;
    }

    /** @return list<string> */
    public static function criticalTables(): array
    {
        return self::RUNTIME_CRITICAL_TABLES;
    }

    private static function tableExists(PDO $pdo,string $table): bool
    {
        $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:name');$stmt->execute(['name'=>$table]);return (int)$stmt->fetchColumn()===1;
    }
    private static function columnExists(PDO $pdo,string $table,string $column): bool
    {
        $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column');$stmt->execute(['table'=>$table,'column'=>$column]);return (int)$stmt->fetchColumn()===1;
    }
    private static function indexExists(PDO $pdo,string $table,string $index): bool
    {
        $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND INDEX_NAME=:idx');$stmt->execute(['table'=>$table,'idx'=>$index]);return (int)$stmt->fetchColumn()>0;
    }
    private static function indexSatisfied(PDO $pdo,string $table,string $indexName,string $definition): bool
    {
        if(self::indexExists($pdo,$table,$indexName))return true;
        $sig=self::indexSignature($definition);if($sig===null)return false;
        $stmt=$pdo->prepare('SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table ORDER BY INDEX_NAME,SEQ_IN_INDEX');
        $stmt->execute(['table'=>$table]);$groups=[];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC)?:[] as $row){$name=(string)$row['INDEX_NAME'];$groups[$name]['unique']=((int)$row['NON_UNIQUE']===0);$groups[$name]['columns'][]=(string)$row['COLUMN_NAME'];}
        foreach($groups as $g){if(($g['unique']??false)===$sig['unique'] && ($g['columns']??[])===$sig['columns'])return true;}return false;
    }
    private static function uniqueColumnSatisfied(PDO $pdo,string $table,string $column): bool
    {
        $stmt=$pdo->prepare('SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table ORDER BY INDEX_NAME,SEQ_IN_INDEX');$stmt->execute(['table'=>$table]);$groups=[];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC)?:[] as $row){$name=(string)$row['INDEX_NAME'];$groups[$name]['unique']=((int)$row['NON_UNIQUE']===0);$groups[$name]['columns'][]=(string)$row['COLUMN_NAME'];}
        foreach($groups as $g){if(($g['unique']??false)===true && ($g['columns']??[])===[$column])return true;}return false;
    }
    /** @return array{unique:bool,columns:list<string>}|null */
    private static function indexSignature(string $definition): ?array
    {
        $unique=(bool)preg_match('/^\s*UNIQUE\s+KEY\b/i',$definition);
        if(!preg_match('/\(([^)]+)\)/',$definition,$m))return null;
        $columns=[];foreach(explode(',',$m[1]) as $raw){$c=trim($raw);$c=preg_replace('/\s+(ASC|DESC)$/i','',$c)??$c;$c=trim($c," `\t\n\r\0\x0B");if($c==='')return null;$columns[]=$c;}return ['unique'=>$unique,'columns'=>$columns];
    }
    private static function columnType(PDO $pdo,string $table,string $column): ?string
    {
        $stmt=$pdo->prepare('SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column LIMIT 1');$stmt->execute(['table'=>$table,'column'=>$column]);$v=$stmt->fetchColumn();return $v===false?null:(string)$v;
    }
    private static function canAddColumnSafely(string $definition,int $rowCount): bool
    {
        $u=strtoupper($definition);
        if(str_contains($u,'PRIMARY KEY')||str_contains($u,'AUTO_INCREMENT'))return false;
        if(str_contains($u,'GENERATED ALWAYS'))return true;
        if(!str_contains($u,'NOT NULL'))return true;
        if(str_contains($u,'DEFAULT '))return true;
        return $rowCount===0;
    }

    /** @return array<string,array<string,array{type:string,definition:string,values:list<string>}>> */
    private static function enumTargets(): array
    {
        $ads=['home_top','welcome_takeover','home_feed','home_sidebar','players_feed','opponents_feed','match_inline','team_inline','career_inline','mobile_feed','amateur_list_top','amateur_list_inline','amateur_detail_inline'];
        $reports=['user','team','post','player_post','opponent_post','match','amateur_ad'];
        return [
            'ads'=>['placement'=>['type'=>"enum('".implode("','",$ads)."')",'definition'=>"ENUM('".implode("','",$ads)."') NOT NULL",'values'=>$ads]],
            'reports'=>['target_type'=>['type'=>"enum('".implode("','",$reports)."')",'definition'=>"ENUM('".implode("','",$reports)."') NOT NULL",'values'=>$reports]],
            'admins'=>['role'=>['type'=>"enum('super_admin','authorized','writer')",'definition'=>"ENUM('super_admin','authorized','writer') NOT NULL DEFAULT 'writer'",'values'=>['super_admin','authorized','writer']]],
        ];
    }

    /** @param list<string> $allowed @return list<string> */
    private static function enumValuesOutsideTarget(PDO $pdo,string $table,string $column,array $allowed): array
    {
        $rows=$pdo->query('SELECT DISTINCT '.self::id($column).' AS v FROM '.self::id($table).' WHERE '.self::id($column).' IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN)?:[];
        $unknown=[];foreach($rows as $v){if(!in_array((string)$v,$allowed,true))$unknown[]=(string)$v;}return array_values(array_unique($unknown));
    }

    /** @param list<string> $actions @param list<string> $errors */
    private static function seedDefaults(PDO $pdo,array &$actions,array &$errors): void
    {
        try{
            if(self::tableExists($pdo,'admins')){
                $stmt=$pdo->prepare('INSERT INTO admins (username,email,password_hash,display_name,role,status) SELECT :username,:email,:hash,:name,"super_admin","active" WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username=:check_username OR email=:check_email)');
                $stmt->execute(['username'=>self::ADMIN_USERNAME,'email'=>self::ADMIN_EMAIL,'hash'=>self::ADMIN_PASSWORD_HASH,'name'=>'Veribayt Yönetim','check_username'=>self::ADMIN_USERNAME,'check_email'=>self::ADMIN_EMAIL]);
                if($stmt->rowCount()>0)$actions[]='SEED isolated admin';
                $activate=$pdo->prepare('UPDATE admins SET status="active" WHERE username=:username AND status<>"active"');$activate->execute(['username'=>self::ADMIN_USERNAME]);
                if($activate->rowCount()>0)$actions[]='ACTIVATE isolated admin';
            }
            if(self::tableExists($pdo,'site_settings')){
                $defaults=['announcement_enabled'=>'0','announcement_text'=>'','support_email'=>'destek@takimbul.com.tr','home_feature_availability'=>'1','home_feature_rival_match'=>'1','maintenance_message'=>'','welcome_ad_enabled'=>'1'];
                $stmt=$pdo->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (:key,:value) ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key)');
                foreach($defaults as $k=>$v){$stmt->execute(['key'=>$k,'value'=>$v]);}
                $force=$pdo->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (:key,"0") ON DUPLICATE KEY UPDATE setting_value="0"');$force->execute(['key'=>'require_email_verification']);
                $actions[]='ENSURE site settings';
            }
            if(self::tableExists($pdo,'user_football_identities')){
                if(self::tableExists($pdo,'amateur_player_ads')){
                    $pdo->exec("INSERT IGNORE INTO user_football_identities (user_id,identity_type,status,is_primary,completed_at) SELECT user_id,'amateur_player',CASE WHEN status='active' THEN 'active' WHEN status='pending' THEN 'pending' ELSE 'draft' END,0,CASE WHEN status IN ('active','pending') THEN updated_at ELSE NULL END FROM amateur_player_ads");
                }
                if(self::tableExists($pdo,'player_profiles')){
                    $pdo->exec("INSERT IGNORE INTO user_football_identities (user_id,identity_type,status,is_primary,completed_at) SELECT user_id,'recreational_player','active',0,updated_at FROM player_profiles");
                }
                $actions[]='BACKFILL football identities';
            }
            if(self::tableExists($pdo,'seasons')){
                $pdo->exec("INSERT INTO seasons (name,slug,starts_at,ends_at,status) VALUES ('2026/27 Sezonu','2026-27','2026-07-01','2027-06-30','active') ON DUPLICATE KEY UPDATE name=VALUES(name),starts_at=VALUES(starts_at),ends_at=VALUES(ends_at)");
            }
        }catch(Throwable $e){$errors[]='SEED defaults: '.$e->getMessage();}
    }

    /** @param list<string> $actions @param list<string> $errors */
    private static function isolateLegacyAdmin(PDO $pdo,array &$actions,array &$errors): void
    {
        if(!self::tableExists($pdo,'users'))return;
        try{
            $hasRole=self::columnExists($pdo,'users','role');$hasUsername=self::columnExists($pdo,'users','username');$hasVerified=self::columnExists($pdo,'users','email_verified_at');
            if(!$hasRole||!$hasUsername)return;
            $sql="UPDATE users SET username=NULL,email=CONCAT('legacy-admin-',id,'@invalid.local'),password_hash=:dead_hash,display_name='Eski Yönetim Kaydı',role='player',status='deleted'".($hasVerified?',email_verified_at=NULL':'')." WHERE username=:username OR email=:email OR role='admin'";
            $stmt=$pdo->prepare($sql);$stmt->execute(['dead_hash'=>'!','username'=>self::ADMIN_USERNAME,'email'=>self::ADMIN_EMAIL]);
            if($stmt->rowCount()>0)$actions[]='ISOLATE legacy public admin rows';
        }catch(Throwable $e){$errors[]='LEGACY ADMIN isolation: '.$e->getMessage();}
    }

    /** @return list<string> */
    private static function splitDefinitions(string $body): array
    {
        $parts=[];$buf='';$depth=0;$quote=null;$len=strlen($body);
        for($i=0;$i<$len;$i++){
            $ch=$body[$i];
            if($quote!==null){$buf.=$ch;if($ch===$quote && ($i===0||$body[$i-1]!=='\\'))$quote=null;continue;}
            if($ch==="'"||$ch==='"'||$ch==='`'){$quote=$ch;$buf.=$ch;continue;}
            if($ch==='('){$depth++;$buf.=$ch;continue;}if($ch===')'){$depth--; $buf.=$ch;continue;}
            if($ch===','&&$depth===0){$parts[]=$buf;$buf='';continue;}$buf.=$ch;
        }
        if(trim($buf)!=='')$parts[]=$buf;return $parts;
    }
    private static function id(string $name): string { return '`'.str_replace('`','``',$name).'`'; }
    private static function normalizeType(string $type): string { return strtolower(preg_replace('/\s+/','',trim($type))??trim($type)); }
}
