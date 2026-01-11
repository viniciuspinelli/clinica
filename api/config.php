<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    $url = getenv('DATABASE_URL');
    if (!$url) {
        throw new RuntimeException('DATABASE_URL não definida no ambiente.');
    }

    $parts = parse_url($url);
    if ($parts === false) {
        throw new RuntimeException('DATABASE_URL inválida.');
    }

    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 5432;
    $user = $parts['user'] ?? '';
    $pass = $parts['pass'] ?? '';
    $db   = ltrim($parts['path'] ?? '', '/');

    parse_str($parts['query'] ?? '', $query);
    $sslmode = $query['sslmode'] ?? null;

    // DSN do PDO_PGSQL: pgsql:host=...;port=...;dbname=...;sslmode=...
    $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
    if ($sslmode) $dsn .= ";sslmode={$sslmode}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
