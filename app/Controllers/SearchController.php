<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\View;use App\Models\GlobalSearch;
final class SearchController{public function index(): void{$q=trim((string)($_GET['q']??''));View::render('search/index',['title'=>$q!==''?'“'.$q.'” Araması':'Ara','q'=>$q,'results'=>$q!==''?GlobalSearch::query($q):['players'=>[],'referees'=>[],'coaches'=>[],'teams'=>[],'player_posts'=>[],'opponent_posts'=>[]]]);}}
