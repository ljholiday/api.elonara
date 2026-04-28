<?php

declare(strict_types=1);

use Elonara\Api\Database;
use Elonara\Api\Response;
use Elonara\Api\Router;

spl_autoload_register(static function (string $class): void {
    $prefix = 'Elonara\\Api\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$router = new Router();

$router->get('/health', static function (): void {
    Response::json([
        'status' => 'ok',
        'service' => 'elonara_api',
    ]);
});

$router->get('/db-check', static function (): void {
    try {
        Database::fromEnv(dirname(__DIR__) . '/config/.env')->pdo()->query('SELECT 1');
        Response::json(['database' => 'connected']);
    } catch (Throwable) {
        Response::json([
            'database' => 'error',
            'message' => 'Database connection failed',
        ], 500);
    }
});

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'
);
