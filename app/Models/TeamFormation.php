<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

final class TeamFormation
{
    /** @return array<string,array{code:string,label:string,slots:list<array{id:string,label:string,x:int,y:int}>}> */
    public static function templates(): array
    {
        return [
            '5v5'=>['code'=>'1-2-1','label'=>'1-2-1','slots'=>[
                ['id'=>'gk','label'=>'KL','x'=>50,'y'=>90],
                ['id'=>'d1','label'=>'DEF','x'=>30,'y'=>66],['id'=>'d2','label'=>'DEF','x'=>70,'y'=>66],
                ['id'=>'m1','label'=>'OS','x'=>50,'y'=>43],['id'=>'f1','label'=>'FV','x'=>50,'y'=>16],
            ]],
            '6v6'=>['code'=>'1-2-2','label'=>'1-2-2','slots'=>[
                ['id'=>'gk','label'=>'KL','x'=>50,'y'=>90],
                ['id'=>'d1','label'=>'DEF','x'=>30,'y'=>68],['id'=>'d2','label'=>'DEF','x'=>70,'y'=>68],
                ['id'=>'m1','label'=>'OS','x'=>30,'y'=>43],['id'=>'m2','label'=>'OS','x'=>70,'y'=>43],
                ['id'=>'f1','label'=>'FV','x'=>50,'y'=>16],
            ]],
            '7v7'=>['code'=>'1-2-2-1','label'=>'1-2-2-1','slots'=>[
                ['id'=>'gk','label'=>'KL','x'=>50,'y'=>91],
                ['id'=>'d1','label'=>'DEF','x'=>28,'y'=>70],['id'=>'d2','label'=>'DEF','x'=>72,'y'=>70],
                ['id'=>'m1','label'=>'OS','x'=>30,'y'=>47],['id'=>'m2','label'=>'OS','x'=>70,'y'=>47],
                ['id'=>'f1','label'=>'FV','x'=>34,'y'=>20],['id'=>'f2','label'=>'FV','x'=>66,'y'=>20],
            ]],
            '8v8'=>['code'=>'1-3-2-1','label'=>'1-3-2-1','slots'=>[
                ['id'=>'gk','label'=>'KL','x'=>50,'y'=>91],
                ['id'=>'d1','label'=>'DEF','x'=>20,'y'=>70],['id'=>'d2','label'=>'DEF','x'=>50,'y'=>70],['id'=>'d3','label'=>'DEF','x'=>80,'y'=>70],
                ['id'=>'m1','label'=>'OS','x'=>32,'y'=>45],['id'=>'m2','label'=>'OS','x'=>68,'y'=>45],
                ['id'=>'f1','label'=>'FV','x'=>35,'y'=>18],['id'=>'f2','label'=>'FV','x'=>65,'y'=>18],
            ]],
            '11v11'=>['code'=>'1-4-3-3','label'=>'1-4-3-3','slots'=>[
                ['id'=>'gk','label'=>'KL','x'=>50,'y'=>92],
                ['id'=>'d1','label'=>'DEF','x'=>14,'y'=>72],['id'=>'d2','label'=>'DEF','x'=>38,'y'=>72],['id'=>'d3','label'=>'DEF','x'=>62,'y'=>72],['id'=>'d4','label'=>'DEF','x'=>86,'y'=>72],
                ['id'=>'m1','label'=>'OS','x'=>25,'y'=>47],['id'=>'m2','label'=>'OS','x'=>50,'y'=>43],['id'=>'m3','label'=>'OS','x'=>75,'y'=>47],
                ['id'=>'f1','label'=>'FV','x'=>22,'y'=>18],['id'=>'f2','label'=>'FV','x'=>50,'y'=>14],['id'=>'f3','label'=>'FV','x'=>78,'y'=>18],
            ]],
        ];
    }

    /** @return array{errors:array<string,string>,slots:list<array{id:string,label:string,x:int,y:int,user_id:?int}>} */
    public static function validate(string $format,string $formationCode,array $assignments,array $activeMemberIds): array
    {
        $templates=self::templates();
        if(!isset($templates[$format])) return ['errors'=>['match_format'=>'Maç formatı geçersiz.'],'slots'=>[]];
        $template=$templates[$format];
        if($formationCode!==$template['code']) return ['errors'=>['formation_code'=>'Diziliş şablonu geçersiz.'],'slots'=>[]];

        $active=array_fill_keys(array_map('intval',$activeMemberIds),true);
        $seen=[];$errors=[];$slots=[];
        foreach($template['slots'] as $slot){
            $raw=$assignments[$slot['id']] ?? null;
            $userId=($raw===null || $raw==='' || (int)$raw<=0)?null:(int)$raw;
            if($userId!==null){
                if(!isset($active[$userId])) $errors['assignments']='Takım kadrosunda olmayan oyuncu dizilişe eklenemez.';
                elseif(isset($seen[$userId])) $errors['assignments']='Bir oyuncu aynı dizilişte iki farklı pozisyona atanamaz.';
                $seen[$userId]=true;
            }
            $slots[]=['id'=>$slot['id'],'label'=>$slot['label'],'x'=>(int)$slot['x'],'y'=>(int)$slot['y'],'user_id'=>$userId];
        }
        return ['errors'=>$errors,'slots'=>$slots];
    }

    public static function find(int $teamId): ?array
    {
        $stmt=Database::connection()->prepare('SELECT team_id,match_format,formation_code,slots_json,updated_by_user_id,updated_at FROM team_formations WHERE team_id=:team_id LIMIT 1');
        $stmt->execute(['team_id'=>$teamId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!is_array($row)) return null;
        $slots=json_decode((string)$row['slots_json'],true);
        $row['slots']=is_array($slots)?$slots:[];
        unset($row['slots_json']);
        return $row;
    }

    public static function save(int $teamId,int $actorUserId,string $format,string $formationCode,array $assignments): void
    {
        if(!Team::isCaptain($teamId,$actorUserId)) throw new RuntimeException('Dizilişi yalnızca takım kaptanı değiştirebilir.');
        $stmt=Database::connection()->prepare('SELECT user_id FROM team_members WHERE team_id=:team_id AND status="active"');
        $stmt->execute(['team_id'=>$teamId]);$active=array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN)?:[]);
        $validated=self::validate($format,$formationCode,$assignments,$active);
        if($validated['errors']!==[]) throw new RuntimeException((string)reset($validated['errors']));
        $json=json_encode($validated['slots'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(!is_string($json)) throw new RuntimeException('Diziliş kaydedilemedi.');
        $up=Database::connection()->prepare(
            'INSERT INTO team_formations (team_id,match_format,formation_code,slots_json,updated_by_user_id,updated_at)
             VALUES (:team_id,:match_format,:formation_code,:slots_json,:updated_by_user_id,NOW())
             ON DUPLICATE KEY UPDATE match_format=VALUES(match_format),formation_code=VALUES(formation_code),slots_json=VALUES(slots_json),updated_by_user_id=VALUES(updated_by_user_id),updated_at=NOW()'
        );
        $up->execute(['team_id'=>$teamId,'match_format'=>$format,'formation_code'=>$formationCode,'slots_json'=>$json,'updated_by_user_id'=>$actorUserId]);
    }
}
