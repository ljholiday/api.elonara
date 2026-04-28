<?php

declare(strict_types=1);

namespace Elonara\Api;

use PDO;

final class Database
{
    private function __construct(private PDO $pdo)
    {
    }

    public static function fromEnv(string $path): self
    {
        $env = self::loadEnv($path);

        $host = $env['DB_HOST'] ?? '127.0.0.1';
        $port = $env['DB_PORT'] ?? '3306';
        $name = $env['DB_NAME'] ?? 'elonara_api';
        $user = $env['DB_USER'] ?? '';
        $pass = $env['DB_PASS'] ?? '';
        $charset = $env['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);

        return new self(new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]));
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @return array<string, string>
     */
    private static function loadEnv(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $values = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            if ($key === '') {
                continue;
            }

            $values[$key] = self::cleanEnvValue($value);
        }

        return $values;
    }

    private static function cleanEnvValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
