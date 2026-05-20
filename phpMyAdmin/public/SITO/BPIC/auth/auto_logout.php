<?php
declare(strict_types=1);

/*
 * auto_logout.php — Chiamato dal JavaScript quando l'utente chiude la finestra.
 * Cancella il cookie JWT esattamente come fa logout.php.
 */
setcookie('jwt', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
    // 'secure' => true,
]);

http_response_code(204); // "No Content" — nessun redirect, è una chiamata JS in background
