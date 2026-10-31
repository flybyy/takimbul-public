<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\View;
final class LegalController
{
    public function privacy(): void { View::render('legal/privacy',['title'=>'Gizlilik Politikası']); }
    public function terms(): void { View::render('legal/terms',['title'=>'Kullanım Koşulları']); }
    public function community(): void { View::render('legal/community',['title'=>'Topluluk Kuralları']); }
    public function about(): void { View::render('legal/about',['title'=>'Hakkımızda']); }
    public function contact(): void { View::render('legal/contact',['title'=>'İletişim']); }
}
