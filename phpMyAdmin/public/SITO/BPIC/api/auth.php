<?php
declare(strict_types=1);

/*
 * api/auth.php — Middleware JWT per pagine e API protette.
 *
 * Percorsi di inclusione in base a dove ti trovi:
 *   dalla root BPIC  →  require_once __DIR__ . '/api/auth.php';
 *   da api/          →  require_once __DIR__ . '/auth.php';
 *   da api/admin/    →  require_once __DIR__ . '/../auth.php';
 *   da dashboard/    →  require_once __DIR__ . '/../api/auth.php';
 *
 * Dopo l'inclusione hai disponibile:
 *   $pdo                      — connessione PDO al database
 *   $currentUser['user_id']   — int
 *   $currentUser['role_id']   — int
 *   $currentUser['role_name'] — string
 *
 * Per gli endpoint API usa require_permission() (definita qui sotto)
 * per verificare i permessi prima di eseguire un'operazione.
 */

require_once __DIR__ . '/../database.php'; // crea $pdo
require_once __DIR__ . '/jwt.php';         // define JWT_SECRET, create_jwt(), verify_jwt()

// Legge il JWT dal cookie HttpOnly inviato automaticamente dal browser
$_jwtRaw  = $_COOKIE['jwt'] ?? '';
$_jwtData = verify_jwt($_jwtRaw, JWT_SECRET);

// Token assente, scaduto o firma non valida → redirect al login
if (!$_jwtData || empty($_jwtData['user_id'])) {
    header('Location: /SITO/BPIC/login.php');
    exit;
}

// Popola $currentUser con i dati dal payload JWT (nessuna query al DB)
$currentUser = [
    'user_id'   => (int)$_jwtData['user_id'],
    'role_id'   => (int)($_jwtData['role_id']      ?? 0),
    'role_name' => (string)($_jwtData['role_name'] ?? ''),
];

unset($_jwtRaw, $_jwtData);

/*
 * require_permission() — Verifica che il ruolo dell'utente abbia il permesso
 * richiesto controllando la tabella Ruolo_Privilegio nel database.
 *
 * Se il permesso manca risponde con HTTP 403 JSON e termina lo script.
 *
 * Parametri:
 *   $risorsa — colonna Risorsa nella tabella Privilegi  (es. 'utenti', 'ruoli')
 *   $azione  — colonna Azione  nella tabella Privilegi  (es. 'SELECT', 'DELETE', 'ALL')
 *              Un privilegio con Azione='ALL' soddisfa qualsiasi $azione richiesta.
 */
function require_permission(PDO $pdo, int $roleId, string $risorsa, string $azione): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM Ruolo_Privilegio rp
         JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
         WHERE rp.ID_ruolo = ?
           AND p.Risorsa = ?
           AND (p.Azione = "ALL" OR p.Azione = ?)'
    );
    $stmt->execute([$roleId, $risorsa, $azione]);

    if ((int)$stmt->fetchColumn() === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Permesso negato.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
