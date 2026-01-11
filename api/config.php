<?php
// api/config.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  // ======= INFINITYFREE (preenchido) =======
  $DB_HOST = "sql113.infinityfree.com";
  $DB_NAME = "if0_40873383_clinica";
  $DB_USER = "if0_40873383";
  $DB_PASS = "Shut328078"; // <- coloque a senha do MySQL do InfinityFree
  // ========================================

  // Usar charset no DSN é o padrão recomendado para UTF-8 (utf8mb4). [web:300][web:299]
  $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

  try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  } catch (Throwable $e) {
    // Evita vazar detalhes em produção
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Falha ao conectar no banco.'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  return $pdo;
}

function json_input(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function require_admin(): void {
  if (empty($_SESSION['admin_id'])) {
    respond(401, ['ok' => false, 'error' => 'Não autorizado.']);
  }
}
