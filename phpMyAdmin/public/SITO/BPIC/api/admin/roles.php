<?php
declare(strict_types=1);

/*
 * api/admin/roles.php — Gestione ruoli (solo admin).
 *
 * GET   → restituisce tutti i ruoli con i relativi permessi
 * PATCH → cambia il ruolo di un utente (body JSON: id_utente, id_ruolo)
 */

require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: lista ruoli con permessi ────────────────────────────────────────────
if ($method === 'GET') {

    require_permission($pdo, $currentUser['role_id'], 'ruoli', 'SELECT');

    // Carica tutti i ruoli
    $ruoli = $pdo->query(
        'SELECT ID_ruolo, Nome_ruolo FROM Ruoli ORDER BY ID_ruolo'
    )->fetchAll();

    // Per ogni ruolo aggiunge la lista dei permessi associati
    $stmtPerms = $pdo->prepare(
        'SELECT p.Nome_privilegio, p.Risorsa, p.Azione
         FROM Ruolo_Privilegio rp
         JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
         WHERE rp.ID_ruolo = ?
         ORDER BY p.Risorsa'
    );

    foreach ($ruoli as &$ruolo) {
        $stmtPerms->execute([$ruolo['ID_ruolo']]);
        $ruolo['permessi'] = $stmtPerms->fetchAll();
    }
    unset($ruolo);

    echo json_encode(['roles' => $ruoli], JSON_UNESCAPED_UNICODE);

// ── PATCH: cambia ruolo a un utente ─────────────────────────────────────────
} elseif ($method === 'PATCH') {

    require_permission($pdo, $currentUser['role_id'], 'ruoli', 'ALL');

    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $idUtente = (int)($body['id_utente'] ?? 0);
    $idRuolo  = (int)($body['id_ruolo']  ?? 0);

    if ($idUtente <= 0 || $idRuolo <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Parametri non validi.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verifica che il ruolo di destinazione esista
    $checkRuolo = $pdo->prepare('SELECT COUNT(*) FROM Ruoli WHERE ID_ruolo = ?');
    $checkRuolo->execute([$idRuolo]);
    if ((int)$checkRuolo->fetchColumn() === 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Ruolo non esistente.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE Utenti SET ID_ruolo = ? WHERE ID_utente = ?');
    $stmt->execute([$idRuolo, $idUtente]);

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
