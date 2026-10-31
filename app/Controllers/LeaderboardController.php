<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Leaderboard;
use App\Models\Season;

final class LeaderboardController
{
    public function index(): void
    {
        $seasons=Season::all();
        $current=Season::current();
        $seasonId=(int)($_GET['season'] ?? ($current['id'] ?? 0));
        if($seasonId<=0 && $seasons!==[]) $seasonId=(int)$seasons[0]['id'];
        $province=trim((string)($_GET['province'] ?? ''));
        $district=trim((string)($_GET['district'] ?? ''));
        $sort=(string)($_GET['sort'] ?? 'rating');
        if(!in_array($sort,['rating','goals','assists','mvps','matches'],true)) $sort='rating';
        View::render('leaderboard/index',[
            'title'=>'Sezon Sıralaması',
            'seasons'=>$seasons,
            'seasonId'=>$seasonId,
            'province'=>$province,
            'district'=>$district,
            'sort'=>$sort,
            'rows'=>$seasonId>0?Leaderboard::rows($seasonId,$province,$district,$sort):[],
        ]);
    }
}
