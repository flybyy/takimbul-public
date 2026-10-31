<?php

declare(strict_types=1);
namespace App\Models;

use App\Core\Database;use PDO;
final class CoachCareerEntry
{
    public static function validateRows(array $rows): array
    {
        $errors=[];$i=0;foreach($rows as $row){$i++;if(!is_array($row))continue;$season=trim((string)($row['season']??''));$team=trim((string)($row['team_name']??''));$role=trim((string)($row['role']??''));$matches=max(0,(int)($row['matches']??0));$wins=max(0,(int)($row['wins']??0));$draws=max(0,(int)($row['draws']??0));$losses=max(0,(int)($row['losses']??0));if($season===''&&$team===''&&$role==='')continue;if($season===''||strlen($season)>30)$errors['career_'.$i]='Sezon bilgisi zorunlu ve en fazla 30 karakter olmalı.';elseif($team===''||strlen($team)>160)$errors['career_'.$i]='Takım adı zorunlu ve en fazla 160 karakter olmalı.';elseif($role===''||strlen($role)>100)$errors['career_'.$i]='Görev zorunlu ve en fazla 100 karakter olmalı.';elseif($wins+$draws+$losses>$matches)$errors['career_'.$i]='Galibiyet, beraberlik ve mağlubiyet toplamı maç sayısını aşamaz.';elseif($matches>1000)$errors['career_'.$i]='Maç sayısı geçersiz.';}
        return $errors;
    }
    public static function forProfile(int $profileId): array{$s=Database::connection()->prepare('SELECT * FROM coach_career_entries WHERE coach_profile_id=:id ORDER BY sort_order ASC,id ASC');$s->execute(['id'=>$profileId]);return $s->fetchAll(PDO::FETCH_ASSOC)?:[];}
    public static function replaceForProfile(int $profileId,array $rows): void
    {
        $pdo=Database::connection();$pdo->prepare('DELETE FROM coach_career_entries WHERE coach_profile_id=:id')->execute(['id'=>$profileId]);$stmt=$pdo->prepare('INSERT INTO coach_career_entries (coach_profile_id,season,team_id,team_name,role,competition,matches,wins,draws,losses,notes,sort_order) VALUES (:profile_id,:season,:team_id,:team_name,:role,:competition,:matches,:wins,:draws,:losses,:notes,:sort_order)');$sort=0;foreach($rows as $row){if(!is_array($row))continue;$season=trim((string)($row['season']??''));$team=trim((string)($row['team_name']??''));$role=trim((string)($row['role']??''));if($season===''&&$team===''&&$role==='')continue;$sort++;$stmt->execute(['profile_id'=>$profileId,'season'=>$season,'team_id'=>!empty($row['team_id'])?(int)$row['team_id']:null,'team_name'=>$team,'role'=>$role,'competition'=>self::nullEmpty($row['competition']??null),'matches'=>max(0,(int)($row['matches']??0)),'wins'=>max(0,(int)($row['wins']??0)),'draws'=>max(0,(int)($row['draws']??0)),'losses'=>max(0,(int)($row['losses']??0)),'notes'=>self::nullEmpty($row['notes']??null),'sort_order'=>$sort]);}
    }
    private static function nullEmpty(mixed $v): ?string{$v=trim((string)$v);return $v===''?null:$v;}
}
