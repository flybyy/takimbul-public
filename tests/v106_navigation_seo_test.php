<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Core\Seo;
$root=dirname(__DIR__);
$header=(string)file_get_contents($root.'/app/Views/layouts/header.php');
$footer=(string)file_get_contents($root.'/app/Views/layouts/footer.php');
foreach(['Oyuncular','Takımlar','Maç','Haberler'] as $label){expect_true(str_contains($header,$label),'Desktop navigasyonda '.$label.' grubu bulunmalı.');}
expect_true(!str_contains($header,'<details class="tb-nav-group'),'Desktop menü native details markerlarına bağlı olmamalı.');
foreach(['/oyuncu-bul','/takimlar','/maclar','/haberler'] as $href){expect_true(str_contains($header,'href="'.$href.'"'),'Desktop menü doğrudan erişilebilir ana linki içermeli: '.$href);}
expect_true(str_contains($footer,'tb-mobile-groups'),'Mobil komut sayfasında ayrı gruplanmış menü bulunmalı.');
$seo=Seo::forRequest('/takim/42',[],['team'=>['id'=>42,'name'=>'Veribayt FC','province'=>'Antalya','district'=>'Alanya','roster'=>[['id'=>1],['id'=>2]]]]);
expect_same('Veribayt FC Kadrosu, Oyuncuları ve Maçları | TakımBul',$seo['title'],'Takım title güçlü kadro/oyuncu/maç niyetini içermeli.');
expect_true(str_contains($seo['description'],'Alanya')&&str_contains($seo['description'],'Antalya'),'Takım açıklaması lokasyonu içermeli.');
$types=array_map(fn($x)=>$x['@type']??null,$seo['jsonLd']);
expect_true(in_array('SportsTeam',$types,true),'Takım sayfasında SportsTeam schema bulunmalı.');
expect_true(in_array('BreadcrumbList',$types,true),'Takım sayfasında breadcrumb schema bulunmalı.');
$sitemap=(string)file_get_contents($root.'/app/Controllers/SeoController.php');
expect_true(str_contains($sitemap,'FROM teams WHERE status="active"'),'Sitemap aktif public takımları içermeli.');
expect_true(!str_contains($sitemap,"/yonet"),'Sitemap yönetim route eklememeli.');
expect_same('noindex,nofollow',Seo::robotsFor('/takim/42/yonet',[]),'Takım yönetimi noindex kalmalı.');
echo "PASS v106_navigation_seo_test\n";
