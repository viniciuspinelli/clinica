<?php
// api/agendamentos.php
require_once __DIR__ . '/config.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

/* =========================
   GET (público)
   ========================= */
if ($method === 'GET') {

  // ---- Disponibilidade por dia ----
  // /api/agendamentos.php?available=1&date=2026-01-10
  if (isset($_GET['available']) && (int)$_GET['available'] === 1) {
    $date = $_GET['date'] ?? '';
    if (!$date) respond(400, ['ok' => false, 'error' => 'Parâmetro date é obrigatório.']);

    // Config (ajuste seu horário de trabalho)
    $start = '09:00';
    $end   = '20:00'; // último slot começa 19:30
    $stepMinutes = 30;

    // Busca horários ocupados no dia
    $stmt = $pdo->prepare("SELECT TIME_FORMAT(horario, '%H:%i') AS h FROM agendamentos WHERE data = :d");
    $stmt->execute([':d' => $date]);
    $busy = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Gera slots de 30 em 30 min
    $slots = [];
    $t = strtotime($date . ' ' . $start);
    $tEnd = strtotime($date . ' ' . $end);

    while ($t < $tEnd) {
      $slots[] = date('H:i', $t);
      $t = strtotime("+{$stepMinutes} minutes", $t);
    }

    // Filtra disponíveis
    $busySet = array_fill_keys($busy, true);
    $available = array_values(array_filter($slots, fn($s) => empty($busySet[$s])));

    respond(200, [
      'ok' => true,
      'date' => $date,
      'start' => $start,
      'end' => $end,
      'step' => $stepMinutes,
      'busy' => $busy,
      'available' => $available
    ]);
  }

  // ---- Listagem do mês (como já estava) ----
  $year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
  $monthIndex = isset($_GET['month']) ? (int)$_GET['month'] : -1;

  if ($year <= 0 || $monthIndex < 0 || $monthIndex > 11) {
    respond(400, ['ok' => false, 'error' => 'Parâmetros year/month inválidos.']);
  }

  $month = $monthIndex + 1;
  $start = sprintf('%04d-%02d-01', $year, $month);
  $end = date('Y-m-d', strtotime($start . ' +1 month'));

  $stmt = $pdo->prepare("
    SELECT id, data, nome, TIME_FORMAT(horario, '%H:%i') AS horario
    FROM agendamentos
    WHERE data >= :start AND data < :end
    ORDER BY data ASC, horario ASC
  ");
  $stmt->execute([':start' => $start, ':end' => $end]);
  $rows = $stmt->fetchAll();

  $map = [];
  foreach ($rows as $r) {
    $day = (int)date('j', strtotime($r['data']));
    if (!isset($map[$day])) $map[$day] = [];
    $map[$day][] = [
      'id' => (int)$r['id'],
      'name' => $r['nome'],
      'time' => $r['horario']
    ];
  }

  $out = [];
  foreach ($map as $day => $apps) {
    $out[] = ['date' => (string)$day, 'appointments' => $apps];
  }

  respond(200, $out);
}

/* =========================
   POST/PUT/DELETE (admin)
   ========================= */
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
  require_admin();
}

// POST: criar
if ($method === 'POST') {
  $data = json_input();
  $date = $data['date'] ?? '';
  $name = trim($data['name'] ?? '');
  $time = $data['time'] ?? '';

  if (!$date || !$name || !$time) {
    respond(400, ['ok' => false, 'error' => 'Campos obrigatórios: date, name, time.']);
  }

  // Bloqueia duplicidade no mesmo horário (recomendado)
  $chk = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE data = :d AND horario = :h");
  $chk->execute([':d' => $date, ':h' => $time]);
  if ((int)$chk->fetchColumn() > 0) {
    respond(409, ['ok' => false, 'error' => 'Horário já está ocupado.']);
  }

  $stmt = $pdo->prepare("INSERT INTO agendamentos (data, nome, horario) VALUES (:data, :nome, :horario)");
  $stmt->execute([':data' => $date, ':nome' => $name, ':horario' => $time]);

  respond(201, ['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}

// PUT: editar
if ($method === 'PUT') {
  $data = json_input();
  $id = isset($data['id']) ? (int)$data['id'] : 0;
  $name = isset($data['name']) ? trim($data['name']) : null;
  $time = $data['time'] ?? null;
  $date = $data['date'] ?? null;

  if ($id <= 0) respond(400, ['ok' => false, 'error' => 'Campo obrigatório: id.']);

  // Se estiver mudando date/time, impedir conflito
  if ($date && $time) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE data = :d AND horario = :h AND id <> :id");
    $chk->execute([':d' => $date, ':h' => $time, ':id' => $id]);
    if ((int)$chk->fetchColumn() > 0) {
      respond(409, ['ok' => false, 'error' => 'Horário já está ocupado.']);
    }
  }

  $fields = [];
  $params = [':id' => $id];

  if ($name !== null && $name !== '') { $fields[] = "nome = :nome"; $params[':nome'] = $name; }
  if ($time !== null && $time !== '') { $fields[] = "horario = :horario"; $params[':horario'] = $time; }
  if ($date !== null && $date !== '') { $fields[] = "data = :data"; $params[':data'] = $date; }

  if (!$fields) respond(400, ['ok' => false, 'error' => 'Nada para atualizar.']);

  $sql = "UPDATE agendamentos SET " . implode(", ", $fields) . " WHERE id = :id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  respond(200, ['ok' => true]);
}

// DELETE: excluir
if ($method === 'DELETE') {
  $data = json_input();
  $id = isset($data['id']) ? (int)$data['id'] : 0;
  if ($id <= 0) respond(400, ['ok' => false, 'error' => 'Campo obrigatório: id.']);

  $stmt = $pdo->prepare("DELETE FROM agendamentos WHERE id = :id");
  $stmt->execute([':id' => $id]);

  respond(200, ['ok' => true]);
}

respond(405, ['ok' => false, 'error' => 'Método não suportado.']);
