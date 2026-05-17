<?php
declare(strict_types=1);
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/jwt.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$token = trim($input['token'] ?? ($_POST['token'] ?? ''));
if ($token === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Token mancante.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = verify_jwt($token, JWT_SECRET);
if (!$payload || empty($payload['user_id'])) {
    http_response_code(200);
    echo json_encode(['valid' => false, 'error' => 'Token non valido o scaduto.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$payload['user_id'];

// Recupera email e ruoli/privilegi
// Ottieni email e eventualmente ID_ruolo
$stmt = $pdo->prepare('SELECT Email, ID_ruolo FROM Utenti WHERE ID_utente = ? LIMIT 1');
try {
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore interno (execute).'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$user) {
    http_response_code(404);
    echo json_encode(['valid' => false, 'error' => 'Utente non trovato.'], JSON_UNESCAPED_UNICODE);
    exit;
}


// Determina role_id: prefer valore nel payload, altrimenti quello salvato in tabella Utenti
$email = $user['Email'];
$roleId = null;
if (!empty($payload['role_id'])) {
    $roleId = (int)$payload['role_id'];
} elseif (!empty($user['ID_ruolo'])) {
    $roleId = (int)$user['ID_ruolo'];
}

$result = [];
if ($roleId !== null) {
    $stmt = $pdo->prepare('SELECT r.ID_ruolo, r.Nome_ruolo, p.ID_privilegio, p.Nome_privilegio, p.Risorsa, p.Azione
        FROM Ruoli r
        JOIN Ruolo_Privilegio rp ON rp.ID_ruolo = r.ID_ruolo
        JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
        WHERE r.ID_ruolo = ?');
    $stmt->execute([$roleId]);
    $result = $stmt->fetchAll();
}

$roles = [];
$permissions = [];
$roleMap = [];
$permMap = [];

foreach ($result as $row) {
    $roleId = (int)$row['ID_ruolo'];
    if (!isset($roleMap[$roleId])) {
        $roleMap[$roleId] = true;
        $roles[] = ['id' => $roleId, 'name' => $row['Nome_ruolo']];
    }

    $permId = (int)$row['ID_privilegio'];
    if (!isset($permMap[$permId])) {
        $permMap[$permId] = true;
        $permissions[] = ['id' => $permId, 'name' => $row['Nome_privilegio'], 'resource' => $row['Risorsa'], 'action' => $row['Azione']];
    }
}

http_response_code(200);
echo json_encode([
    'valid' => true,
    'payload' => $payload,
    'email' => $email,
    'roles' => $roles,
    'permissions' => $permissions,
], JSON_UNESCAPED_UNICODE);
exit;
