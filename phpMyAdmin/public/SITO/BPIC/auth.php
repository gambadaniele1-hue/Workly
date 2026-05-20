<?php
declare(strict_types=1);

/*
 * auth.php — Middleware di autenticazione JWT per le pagine protette.
 *
 * Come usarlo: metti require_once __DIR__ . '/auth.php'; in cima a ogni pagina protetta.
 * Dopo l'inclusione avrai disponibile $currentUser con i dati dell'utente loggato.
 *
 * $currentUser contiene:
 *   - user_id   : int   — ID univoco dell'utente nel database
 *   - role_id   : int   — ID del ruolo (1=admin, 2=abbonato, 3=non abbonato, 4=tenant)
 *   - role_name : string — nome del ruolo ('admin', 'utente_abbonato', ecc.)
 *
 * Se il cookie JWT è assente, scaduto o manomesso, l'utente viene
 * reindirizzato automaticamente alla pagina di login.
 */

// Carica la connessione al database ($pdo) e le funzioni JWT
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/api/jwt.php';

// Legge il JWT dal cookie HttpOnly (il browser lo invia automaticamente a ogni richiesta)
$_jwtRaw  = $_COOKIE['jwt'] ?? '';
$_jwtData = verify_jwt($_jwtRaw, JWT_SECRET);

// Token assente, scaduto o firma non valida → manda al login
if (!$_jwtData || empty($_jwtData['user_id'])) {
    header('Location: /SITO/BPIC/login.php');
    exit;
}

/*
 * Popola $currentUser con i dati estratti dal payload del JWT.
 * Nessuna query al database: le informazioni sono già nel token firmato.
 */
$currentUser = [
    'user_id'   => (int)$_jwtData['user_id'],
    'role_id'   => (int)($_jwtData['role_id']      ?? 0),
    'role_name' => (string)($_jwtData['role_name'] ?? ''),
];

// Pulizia: rimuove le variabili temporanee dal namespace globale
unset($_jwtRaw, $_jwtData);
