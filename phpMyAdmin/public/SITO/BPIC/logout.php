<?php
declare(strict_types=1);

/*
 * logout.php — Disconnette l'utente cancellando il cookie JWT.
 *
 * Per cancellare un cookie si imposta la sua scadenza nel passato:
 * il browser lo rimuove automaticamente alla risposta successiva.
 */
setcookie('jwt', '', [
    'expires'  => time() - 3600, // scadenza nel passato = cancella il cookie
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
    // 'secure' => true,
]);

header('Location: /SITO/BPIC/login.php');
exit;
