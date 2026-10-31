<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Csrf;
use App\Core\Router;

$_SESSION = [];
$first = Csrf::token();
$second = Csrf::token();
expect_true(strlen($first) >= 32, 'CSRF token should be sufficiently long');
expect_same($first, $second, 'CSRF token should remain stable for the same session');
expect_true(Csrf::validate($first), 'CSRF should validate the active token');
expect_true(!Csrf::validate('wrong-token'), 'CSRF should reject an invalid token');

$router = new Router();
$router->get('/takim/{id}', static fn(array $params) => 'team-' . $params['id']);
$router->post('/giris', static fn() => 'posted');

expect_same('team-42', $router->dispatch('GET', '/takim/42', true), 'Router should match dynamic GET routes');
expect_same('team-42', $router->dispatch('HEAD', '/takim/42', true), 'Router should match HEAD against GET routes');
expect_same('posted', $router->dispatch('POST', '/giris', true), 'Router should match exact POST routes');
expect_same(null, $router->dispatch('GET', '/giris', true), 'Router should not match a POST route with GET');

echo "PASS core_test\n";
