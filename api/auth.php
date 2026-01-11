<?php
// api/auth.php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  // status da sessão
  respond(200, [
    'ok' => true,
    'authenticated' => !empty($_SESSION['admin_id']),
    'username' => $_SESSION['admin_username'] ?? null
  ]);
}

if ($method === 'POST') {
  $data = json_input();
  $username = trim($data['username'] ?? '');
  $password = (string)($data['password'] ?? '');

  if ($username === '' || $password === '') {
    respond(400, ['ok' => false, 'error' => 'username e password são obrigatórios.']);
  }

  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1");
  $stmt->execute([':u' => $username]);
  $admin = $stmt->fetch();

  if (!$admin || !password_verify($password, $admin['password_hash'])) {
    respond(401, ['ok' => false, 'error' => 'Usuário ou senha inválidos.']);
  }

  $_SESSION['admin_id'] = (int)$admin['id'];
  $_SESSION['admin_username'] = $admin['username'];

  respond(200, ['ok' => true]);
}

if ($method === 'DELETE') {
  // logout
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
  }
  session_destroy();
  respond(200, ['ok' => true]);
}

respond(405, ['ok' => false, 'error' => 'Método não suportado.']);
