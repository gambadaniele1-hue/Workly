<?php
declare(strict_types=1);

/*
 * api/admin/users.php — Gestione utenti (solo admin).
 *
 * GET    → restituisce la lista di tutti gli utenti
 * DELETE → elimina un utente (id_utente nel body JSON)
 */

require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: lista tutti gli utenti ──────────────────────────────────────────────
if ($method === 'GET') {

    require_permission($pdo, $currentUser['role_id'], 'utenti', 'SELECT');

    $stmt = $pdo->query(
        'SELECT u.ID_utente, u.Email, u.N_Telefono, u.ID_ruolo, r.Nome_ruolo
         FROM Utenti u
         LEFT JOIN Ruoli r ON r.ID_ruolo = u.ID_ruolo
         ORDER BY u.ID_utente'
    );

    echo json_encode(['users' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);

// ── DELETE: elimina un utente ────────────────────────────────────────────────
} elseif ($method === 'DELETE') {

    require_permission($pdo, $currentUser['role_id'], 'utenti', 'DELETE');

    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $idTarget = (int)($body['id_utente'] ?? 0);

    if ($idTarget <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'id_utente non valido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Non permettere di eliminare se stessi
    if ($idTarget === $currentUser['user_id']) {
        http_response_code(422);
        echo json_encode(['error' => 'Non puoi eliminare il tuo account.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM Utenti WHERE ID_utente = ?');
    $stmt->execute([$idTarget]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Utente non trovato.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

// ── Metodo non supportato ────────────────────────────────────────────────────
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non supportato.'], JSON_UNESCAPED_UNICODE);
}
