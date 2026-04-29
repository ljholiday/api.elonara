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
$databasePath = dirname(__DIR__) . '/config/.env';

$router->get('/health', static function (): void {
    Response::json([
        'status' => 'ok',
        'service' => 'elonara_api',
    ]);
});

$router->get('/db-check', static function () use ($databasePath): void {
    try {
        Database::fromEnv($databasePath)->pdo()->query('SELECT 1');
        Response::json(['database' => 'connected']);
    } catch (Throwable) {
        Response::json([
            'database' => 'error',
            'message' => 'Database connection failed',
        ], 500);
    }
});

$router->post('/identity/register', static function () use ($databasePath): void {
    $input = readJsonInput();
    $email = normalizeEmail($input['email'] ?? null);
    $password = is_string($input['password'] ?? null) ? $input['password'] : '';

    if ($email === null || $password === '') {
        Response::json(['error' => 'Email and password are required.'], 400);
        return;
    }

    $now = date('Y-m-d H:i:s');

    try {
        $stmt = apiPdo($databasePath)->prepare(
            "INSERT INTO identity_users (email, password_hash, status, created_at, updated_at)
             VALUES (:email, :password_hash, 'active', :created_at, NULL)"
        );
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':created_at' => $now,
        ]);

        Response::json(['status' => 'created'], 201);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') {
            Response::json(['error' => 'User already exists.'], 409);
            return;
        }

        Response::json(['error' => 'Unable to create user.'], 500);
    } catch (Throwable) {
        Response::json(['error' => 'Unable to create user.'], 500);
    }
});

$router->post('/identity/login', static function () use ($databasePath): void {
    $input = readJsonInput();
    $email = normalizeEmail($input['email'] ?? null);
    $password = is_string($input['password'] ?? null) ? $input['password'] : '';

    if ($email === null || $password === '') {
        Response::json(['error' => 'Email and password are required.'], 400);
        return;
    }

    try {
        $pdo = apiPdo($databasePath);
        $stmt = $pdo->prepare(
            "SELECT id, password_hash, status
             FROM identity_users
             WHERE email = :email
             LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (
            $user === false ||
            ($user['status'] ?? '') !== 'active' ||
            !password_verify($password, (string)$user['password_hash'])
        ) {
            Response::json(['error' => 'Invalid email or password.'], 401);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $insert = $pdo->prepare(
            "INSERT INTO identity_sessions (user_id, token, expires_at, created_at)
             VALUES (:user_id, :token, :expires_at, :created_at)"
        );
        $insert->execute([
            ':user_id' => (int)$user['id'],
            ':token' => $token,
            ':expires_at' => $expiresAt,
            ':created_at' => $now,
        ]);

        Response::json([
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    } catch (Throwable) {
        Response::json(['error' => 'Unable to login.'], 500);
    }
});

$router->get('/identity/me', static function () use ($databasePath): void {
    $token = bearerToken();
    if ($token === null) {
        Response::json(['error' => 'Bearer token required.'], 401);
        return;
    }

    try {
        $stmt = apiPdo($databasePath)->prepare(
            "SELECT u.id, u.email
             FROM identity_sessions s
             INNER JOIN identity_users u ON u.id = s.user_id
             WHERE s.token = :token
               AND s.expires_at > :now
               AND u.status = 'active'
             LIMIT 1"
        );
        $stmt->execute([
            ':token' => $token,
            ':now' => date('Y-m-d H:i:s'),
        ]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user === false) {
            Response::json(['error' => 'Invalid or expired token.'], 401);
            return;
        }

        Response::json([
            'id' => (int)$user['id'],
            'email' => (string)$user['email'],
        ]);
    } catch (Throwable) {
        Response::json(['error' => 'Unable to load identity.'], 500);
    }
});

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'
);

/**
 * @return array<string, mixed>
 */
function readJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function normalizeEmail(mixed $email): ?string
{
    if (!is_string($email)) {
        return null;
    }

    $email = strtolower(trim($email));
    return filter_var($email, FILTER_VALIDATE_EMAIL) === false ? null : $email;
}

function apiPdo(string $databasePath): \PDO
{
    static $pdo = null;

    if ($pdo instanceof \PDO) {
        return $pdo;
    }

    $pdo = Database::fromEnv($databasePath)->pdo();
    return $pdo;
}

function bearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!is_string($header) || stripos($header, 'Bearer ') !== 0) {
        return null;
    }

    $token = trim(substr($header, 7));
    return $token === '' ? null : $token;
}
