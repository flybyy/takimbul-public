<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

use App\Models\Admin;

expect_true(Admin::identifierReserved('veribayt'),'Yönetici kullanıcı adı DB erişilemese bile normal üyelere kapalı kalmalı.');
expect_true(Admin::identifierReserved('VERIBAYT'),'Yönetici kullanıcı adı büyük/küçük harften bağımsız rezerve olmalı.');
expect_true(Admin::identifierReserved('admin@takimbul.com.tr'),'Yönetici e-postası DB erişilemese bile normal üyelere kapalı kalmalı.');

echo "PASS v912_admin_identity_reservation_test\n";
