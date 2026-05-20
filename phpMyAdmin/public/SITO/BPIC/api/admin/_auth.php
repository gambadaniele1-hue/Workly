<?php
declare(strict_types=1);

/*
 * api/admin/_auth.php — Setup condiviso per gli endpoint admin.
 *
 * Includi questo file all'inizio di ogni endpoint in api/admin/.
 * Fornisce: $pdo, $currentUser e require_permission() (da api/auth.php).
 * Imposta il Content-Type della risposta a JSON.
 */

require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');
