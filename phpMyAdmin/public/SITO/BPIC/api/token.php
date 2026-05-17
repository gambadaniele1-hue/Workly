<?php
declare(strict_types=1);

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/jwt.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
} else {
    $input = $_POST;
}

$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Credenziali non valide.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare('
    SELECT ID_utente, Email, Password_hash, ID_ruolo
    FROM Utenti
    WHERE Email = ?
    LIMIT 1
');
try {
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore interno (prepare).'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$user || !password_verify($password, $user['Password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenziali non corrette.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ttlSeconds = 1000;
$extra = [];
if (isset($user['ID_ruolo'])) {
    $extra['role_id'] = (int)$user['ID_ruolo'];
}
$token = create_jwt((int)$user['ID_utente'], $ttlSeconds, JWT_SECRET, $extra);

http_response_code(200);
echo json_encode([
    'token' => $token,
    'token_type' => 'Bearer',
    'expires_in' => $ttlSeconds,
], JSON_UNESCAPED_UNICODE);
exit;
