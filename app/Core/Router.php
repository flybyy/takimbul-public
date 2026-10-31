<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string,path:string,handler:callable}> */
    private array $routes = [];
    private $notFoundHandler = null;

    public function get(string $path, callable $handler): void
    {
        $this->routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler];
    }

    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    public function dispatch(string $method, string $uri, bool $returnResult = false): mixed
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $method = strtoupper($method) === 'HEAD' ? 'GET' : strtoupper($method);
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = [];
            if (!$this->matches($route['path'], $path, $params)) {
                continue;
            }

            $result = ($route['handler'])($params);
            return $returnResult ? $result : null;
        }

        if ($returnResult) {
            return null;
        }

        if (is_callable($this->notFoundHandler)) {
            http_response_code(404);
            ($this->notFoundHandler)();
        } else {
            HttpError::render(404);
        }
        return null;
    }

    private function matches(string $pattern, string $path, array &$params): bool
    {
        $names = [];
        $quoted = preg_quote('/' . trim($pattern, '/'), '#');
        if ($pattern === '/') {
            $quoted = '/';
        }
        $regex = preg_replace_callback('/\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}/', static function (array $match) use (&$names): string {
            $names[] = $match[1];
            return '([^/]+)';
        }, $quoted);

        if (!is_string($regex) || !preg_match('#^' . $regex . '$#', $path, $matches)) {
            return false;
        }

        array_shift($matches);
        foreach ($names as $index => $name) {
            $params[$name] = urldecode((string) ($matches[$index] ?? ''));
        }

        return true;
    }
}
