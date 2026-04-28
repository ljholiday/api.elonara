<?php

declare(strict_types=1);

namespace Elonara\Api;

final class Router
{
    /** @var array<string, array<string, callable(): void>> */
    private array $routes = [];

    /**
     * @param callable(): void $handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            Response::json(['error' => 'Not Found'], 404);
            return;
        }

        $handler();
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');
        return $normalized === '//' ? '/' : $normalized;
    }
}
