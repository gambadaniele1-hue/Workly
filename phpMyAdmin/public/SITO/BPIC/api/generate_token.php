<?php
/**
 * File: api/generate_token.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autenticato.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$ttl = 24 * 3600; // token valido 24 ore
$token = create_jwt($userId, $ttl, JWT_SECRET);

http_response_code(200);
echo json_encode(['token' => $token, 'expires_in' => $ttl], JSON_UNESCAPED_UNICODE);

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
exit;