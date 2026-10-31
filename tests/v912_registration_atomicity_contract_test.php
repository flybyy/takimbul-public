<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$auth=file_get_contents(dirname(__DIR__).'/app/Controllers/AuthController.php')?:'';
expect_true(str_contains($auth,'beginTransaction()'),'Kayıt kullanıcı ve profil oluşturmayı transaction içinde yapmalı.');
expect_true(str_contains($auth,'inTransaction()'),'Kayıt hatasında transaction rollback kontrolü olmalı.');
expect_true(str_contains($auth,'rollBack()'),'Kayıt hatasında yarım kullanıcı kaydı bırakılmamalı.');
expect_true(str_contains($auth,"'23000'") && str_contains($auth,'1062'),'Duplicate kayıt ile altyapı hatası birbirinden ayrılmalı.');
expect_true(str_contains($auth,'Kayıt şu anda tamamlanamadı'),'Genel DB hatası kullanıcıya yanlış e-posta-duplicate mesajı vermemeli.');

echo "PASS v912_registration_atomicity_contract_test\n";
