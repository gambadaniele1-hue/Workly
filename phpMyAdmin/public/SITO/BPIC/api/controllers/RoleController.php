<?php
declare(strict_types=1);

/*
 * RoleController — Gestione ruoli (solo admin).
 *
 * Rotte:
 *   GET   /api/roles — lista tutti i ruoli con i permessi associati
 *   PATCH /api/roles — cambia il ruolo di un utente (body JSON: id_utente, id_ruolo)
 */
class RoleController
{
    public function __construct(
        private PDO   $pdo,
        private array $currentUser
    ) {}

    // ── GET /api/roles ────────────────────────────────────────────────────
    public function list(): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'ruoli', 'SELECT');

        // Carica tutti i ruoli
        $ruoli = $this->pdo->query(
            'SELECT ID_ruolo, Nome_ruolo FROM Ruoli ORDER BY ID_ruolo'
        )->fetchAll();

        // Per ogni ruolo aggiunge la lista dei permessi associati
        $stmtPerms = $this->pdo->prepare(
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
    }

    // ── PATCH /api/users/{id}/roles/{id_role} ────────────────────────────
    public function updateUserRole(int $idUtente, int $idRuolo): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'ruoli', 'ALL');

        // Verifica che il ruolo di destinazione esista
        $check = $this->pdo->prepare('SELECT COUNT(*) FROM Ruoli WHERE ID_ruolo = ?');
        $check->execute([$idRuolo]);
        if ((int)$check->fetchColumn() === 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Ruolo non esistente.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $this->pdo->prepare('UPDATE Utenti SET ID_ruolo = ? WHERE ID_utente = ?');
        $stmt->execute([$idRuolo, $idUtente]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Utente non trovato.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }
}
