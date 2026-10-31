<?php

declare(strict_types=1);

namespace App\Services;

final class AdminPermission
{
    /** @var array<string,list<string>> */
    private const MAP=[
        'super_admin'=>[
            'dashboard','dashboard.editorial','news.create','news.edit_own','news.edit_any','news.sponsor_attach',
            'sponsors.manage','players.verify','moderation.manage','ads.manage','settings.manage','staff.manage','audit.view',
        ],
        'authorized'=>[
            'dashboard','dashboard.editorial','news.create','news.edit_own','news.edit_any','news.sponsor_attach',
            'sponsors.manage','players.verify','moderation.manage','audit.view',
        ],
        'writer'=>['dashboard.editorial','news.create','news.edit_own','news.sponsor_attach'],
    ];

    public static function can(?string $role,string $permission): bool
    {
        $role=trim((string)$role);$permission=trim($permission);
        if($role===''||$permission===''||!isset(self::MAP[$role])) return false;
        return in_array($permission,self::MAP[$role],true);
    }

    /** @return list<string> */
    public static function permissions(?string $role): array
    {
        return self::MAP[trim((string)$role)]??[];
    }
}
