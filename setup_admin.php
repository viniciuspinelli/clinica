<?php
// setup_admin.php  (USAR UMA VEZ E APAGAR)
// Cria/atualiza um admin na tabela `admins` com senha hasheada.

declare(strict_types=1);

$pdo = new PDO(
  "mysql:host=localhost;dbname=clinica;charset=utf8mb4",
  "root",
  "",
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]
);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $password2 = (string)($_POST['password2'] ?? '');

  if ($username === '' || $password === '' || $password2 === '') {
    $err = 'Preencha usuário e senha.';
  } elseif (strlen($username) < 3) {
    $err = 'Usuário deve ter pelo menos 3 caracteres.';
  } elseif (strlen($password) < 6) {
    $err = 'Senha deve ter pelo menos 6 caracteres.';
  } elseif ($password !== $password2) {
    $err = 'As senhas não conferem.';
  } else {
    // Hash seguro (não guarda senha pura no banco) [web:119]
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Cria ou atualiza (se já existir)
    $stmt = $pdo->prepare("
      INSERT INTO admins (username, password_hash)
      VALUES (:u, :h)
      ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
    ");
    $stmt->execute([':u' => $username, ':h' => $hash]);

    $msg = "Admin criado/atualizado com sucesso: {$username}. Agora apague este arquivo (setup_admin.php).";
  }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup Admin</title>
  <style>
    body{font-family:Arial,sans-serif;background:#0b0b10;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .card{width:100%;max-width:520px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.55);padding:22px}
    h1{margin:0 0 10px;font-size:18px}
    p{margin:0 0 14px;color:rgba(255,255,255,.75);font-size:14px;line-height:1.4}
    label{display:block;margin:12px 0 6px;font-weight:700;font-size:13px}
    input{width:100%;padding:12px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.08);color:#fff;font-size:15px}
    button{margin-top:16px;width:100%;padding:12px;border:0;border-radius:12px;background:linear-gradient(135deg,#ff5fa2,#d7b56d);color:#fff;font-weight:900;cursor:pointer}
    .ok{padding:10px 12px;border-radius:12px;background:rgba(34,197,94,.18);border:1px solid rgba(34,197,94,.35);margin:12px 0}
    .err{padding:10px 12px;border-radius:12px;background:rgba(239,68,68,.18);border:1px solid rgba(239,68,68,.35);margin:12px 0}
    code{background:rgba(0,0,0,.25);padding:2px 6px;border-radius:8px}
  </style>
</head>
<body>
  <div class="card">
    <h1>Setup do Admin</h1>
    <p>Crie/atualize o usuário admin para o sistema de agendamentos. Depois de usar, apague <code>setup_admin.php</code>.</p>

    <?php if ($msg): ?>
      <div class="ok"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($err): ?>
      <div class="err"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post">
      <label for="username">Usuário</label>
      <input id="username" name="username" type="text" minlength="3" required>

      <label for="password">Senha</label>
      <input id="password" name="password" type="password" minlength="6" required>

      <label for="password2">Confirmar senha</label>
      <input id="password2" name="password2" type="password" minlength="6" required>

      <button type="submit">Criar / Atualizar admin</button>
    </form>
  </div>
</body>
</html>
