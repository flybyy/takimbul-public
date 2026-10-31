<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AmateurCareerEntry
{
    public const MAX_ROWS = 12;
    public const LEVELS = ['BAL','Süper Amatör','1. Amatör','2. Amatör','U19','U18','U17','Akademi','Yerel Lig','Diğer'];

    public static function validateRows(array $rows): array
    {
        $errors=[];
        $clean=self::normalizeRows($rows);
        if(count($clean)>self::MAX_ROWS){$errors['career']='En fazla '.self::MAX_ROWS.' sezon/kulüp satırı ekleyebilirsin.';return $errors;}
        foreach($clean as $i=>$row){
            $label='Kariyer satırı '.($i+1);
            $season=trim((string)($row['season']??''));
            $club=trim((string)($row['club_name']??''));
            $level=trim((string)($row['level']??''));
            $position=trim((string)($row['position']??''));
            if($season===''||self::length($season)>20)$errors['career_'.$i.'_season']=$label.': sezon zorunlu ve en fazla 20 karakter olmalı.';
            if($club===''||self::length($club)>140)$errors['career_'.$i.'_club']=$label.': kulüp adı zorunlu ve en fazla 140 karakter olmalı.';
            if($level!==''&&!in_array($level,self::LEVELS,true))$errors['career_'.$i.'_level']=$label.': lig/seviye geçersiz.';
            if($position!==''&&!in_array($position,PlayerProfile::POSITIONS,true))$errors['career_'.$i.'_position']=$label.': mevki geçersiz.';
            $numbers=[];
            foreach(['matches','starts','goals','assists','yellow_cards','red_cards'] as $key){
                $raw=trim((string)($row[$key]??'0'));
                if($raw==='' )$raw='0';
                if(!ctype_digit($raw)||(int)$raw>999){$errors['career_'.$i.'_'.$key]=$label.': sayısal alanlar 0-999 arasında olmalı.';$numbers[$key]=0;}else{$numbers[$key]=(int)$raw;}
            }
            $jersey=trim((string)($row['jersey_number']??''));
            if($jersey!==''&&(!ctype_digit($jersey)||(int)$jersey<1||(int)$jersey>99))$errors['career_'.$i.'_jersey']=$label.': forma numarası 1-99 arasında olmalı.';
            if(($numbers['starts']??0)>($numbers['matches']??0))$errors['career_'.$i.'_starts']=$label.': ilk 11 sayısı maç sayısından fazla olamaz.';
            if(self::length(trim((string)($row['note']??'')))>300)$errors['career_'.$i.'_note']=$label.': not 300 karakteri geçemez.';
        }
        return $errors;
    }

    public static function normalizeRows(array $rows): array
    {
        $out=[];
        foreach($rows as $row){
            if(!is_array($row))continue;
            $hasValue=false;
            foreach(['season','club_name','level','position','jersey_number','matches','starts','goals','assists','yellow_cards','red_cards','note'] as $key){
                if(trim((string)($row[$key]??''))!==''){$hasValue=true;break;}
            }
            if(!$hasValue)continue;
            $out[]=[
                'season'=>trim((string)($row['season']??'')),
                'club_name'=>trim((string)($row['club_name']??'')),
                'level'=>self::nullEmpty($row['level']??null),
                'position'=>self::nullEmpty($row['position']??null),
                'jersey_number'=>self::intOrNull($row['jersey_number']??null),
                'matches'=>self::nonNegativeInt($row['matches']??0),
                'starts'=>self::nonNegativeInt($row['starts']??0),
                'goals'=>self::nonNegativeInt($row['goals']??0),
                'assists'=>self::nonNegativeInt($row['assists']??0),
                'yellow_cards'=>self::nonNegativeInt($row['yellow_cards']??0),
                'red_cards'=>self::nonNegativeInt($row['red_cards']??0),
                'note'=>self::nullEmpty(substr(trim((string)($row['note']??'')),0,300)),
            ];
        }
        return $out;
    }

    public static function summary(array $rows): array
    {
        $summary=['seasons'=>0,'matches'=>0,'starts'=>0,'goals'=>0,'assists'=>0,'yellow_cards'=>0,'red_cards'=>0];
        foreach(self::normalizeRows($rows) as $row){
            $summary['seasons']++;
            foreach(['matches','starts','goals','assists','yellow_cards','red_cards'] as $key)$summary[$key]+=(int)$row[$key];
        }
        return $summary;
    }

    public static function forAd(int $adId): array
    {
        $stmt=Database::connection()->prepare('SELECT id,ad_id,season,club_name,level,position,jersey_number,matches,starts,goals,assists,yellow_cards,red_cards,note,verification_status,sort_order,created_at,updated_at FROM amateur_player_career_entries WHERE ad_id=:ad_id ORDER BY sort_order ASC,id ASC');
        $stmt->execute(['ad_id'=>$adId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function replace(PDO $pdo,int $adId,array $rows): void
    {
        $clean=array_slice(self::normalizeRows($rows),0,self::MAX_ROWS);
        $delete=$pdo->prepare('DELETE FROM amateur_player_career_entries WHERE ad_id=:ad_id');
        $delete->execute(['ad_id'=>$adId]);
        if($clean===[])return;
        $insert=$pdo->prepare('INSERT INTO amateur_player_career_entries (ad_id,season,club_name,level,position,jersey_number,matches,starts,goals,assists,yellow_cards,red_cards,note,verification_status,sort_order) VALUES (:ad_id,:season,:club_name,:level,:position,:jersey_number,:matches,:starts,:goals,:assists,:yellow_cards,:red_cards,:note,"self_declared",:sort_order)');
        foreach($clean as $i=>$row){
            $insert->execute(['ad_id'=>$adId,'season'=>$row['season'],'club_name'=>$row['club_name'],'level'=>$row['level'],'position'=>$row['position'],'jersey_number'=>$row['jersey_number'],'matches'=>$row['matches'],'starts'=>$row['starts'],'goals'=>$row['goals'],'assists'=>$row['assists'],'yellow_cards'=>$row['yellow_cards'],'red_cards'=>$row['red_cards'],'note'=>$row['note'],'sort_order'=>$i]);
        }
    }

    private static function nullEmpty(mixed $value): ?string{$v=trim((string)$value);return $v===''?null:$v;}
    private static function intOrNull(mixed $value): ?int{$v=trim((string)$value);return $v===''?null:(int)$v;}
    private static function nonNegativeInt(mixed $value): int{$v=trim((string)$value);return($v!==''&&ctype_digit($v))?min(999,(int)$v):0;}
    private static function length(string $value): int{return function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value);}

}
