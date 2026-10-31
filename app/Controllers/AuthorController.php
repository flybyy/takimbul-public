<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpError;
use App\Core\View;
use App\Models\Admin;
use App\Models\News;

final class AuthorController
{
    public function show(array $params): void
    {
        $slug=trim((string)($params['slug']??''));$author=Admin::findPublicBySlug($slug);
        if(!$author){HttpError::render(404);return;}
        $articles=News::publishedByAuthor((int)$author['id'],30);
        View::render('authors/show',['title'=>(string)$author['display_name'].' Yazıları','author'=>$author,'articles'=>$articles]);
    }
}
