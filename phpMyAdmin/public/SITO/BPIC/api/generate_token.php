<?php
declare(strict_types=1);

/*
 * api/generate_token.php — Genera un nuovo JWT per l'utente già autenticato.
 *
 * Usato da client che hanno già il cookie valido e vogliono un token
 * da passare ad altri servizi tramite header Authorization: Bearer.
 */

require_once __DIR__ . '/../auth.php'; // verifica cookie JWT, popola $currentUser

header('Content-Type: application/json; charset=utf-8');

// Crea un nuovo JWT con gli stessi dati dell'utente loggato
$ttl   = 3600;
$token = create_jwt($currentUser['user_id'], $ttl, JWT_SECRET, [
    'role_id'   => $currentUser['role_id'],
    'role_name' => $currentUser['role_name'],
]);

http_response_code(200);
echo json_encode(['token' => $token, 'expires_in' => $ttl], JSON_UNESCAPED_UNICODE);
