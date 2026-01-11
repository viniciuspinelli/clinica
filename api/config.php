<?php
declare(strict_types=1);

// Inicia sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Lê JSON do body e retorna array associativo
function json_input(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// Envia resposta JSON e encerra
function respond(int $status, $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

// Verifica sessão de admin
function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        respond(401, ['ok' => false, 'error' => 'Acesso negado (admin).']);
    }
}
