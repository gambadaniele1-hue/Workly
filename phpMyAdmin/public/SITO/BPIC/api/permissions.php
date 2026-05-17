<?php
declare(strict_types=1);

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/jwt.php';

header('Content-Type: application/json; charset=utf-8');

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($authHeader === '' && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}

if (!preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token mancante o non valido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = verify_jwt($matches[1], JWT_SECRET);
if (!$payload || empty($payload['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Token non valido o scaduto.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$payload['user_id'];

// Ottieni email e ID_ruolo se presente
$stmt = $pdo->prepare('SELECT Email, ID_ruolo FROM Utenti WHERE ID_utente = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'Utente non trovato.'], JSON_UNESCAPED_UNICODE);
    exit;
}

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
        $roles[] = [
            'id' => $roleId,
            'name' => $row['Nome_ruolo'],
        ];
    }

    $permId = (int)$row['ID_privilegio'];
    if (!isset($permMap[$permId])) {
        $permMap[$permId] = true;
        $permissions[] = [
            'id' => $permId,
            'name' => $row['Nome_privilegio'],
            'resource' => $row['Risorsa'],
            'action' => $row['Azione'],
        ];
    }
}

http_response_code(200);
echo json_encode([
    'user_id' => $userId,
    'email' => $email,
    'roles' => $roles,
    'permissions' => $permissions,
], JSON_UNESCAPED_UNICODE);
exit;