<?php
declare(strict_types=1);

/*
 * database.php — Apre la connessione al database e rende disponibile $pdo.
 *
 * Incluso da auth.php, quindi disponibile in tutte le pagine protette.
 * Usare sempre $pdo->prepare() con parametri per evitare SQL injection.
 */

$host    = '127.0.0.1';
$db      = 'gestione_utenti_bp';
$user    = 'utente_phpmyadmin';
$pass    = 'password_sicura';
$charset = 'utf8mb4';

if (!extension_loaded('pdo_mysql')) {
    http_response_code(500);
    echo 'Estensione pdo_mysql non disponibile.';
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Errore di connessione: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
