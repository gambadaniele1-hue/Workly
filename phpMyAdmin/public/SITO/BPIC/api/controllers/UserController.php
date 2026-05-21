<?php
declare(strict_types=1);

/*
 * UserController — Gestione utenti (solo admin).
 *
 * Rotte:
 *   GET    /api/users — lista tutti gli utenti con il loro ruolo
 *   DELETE /api/users — elimina un utente (id_utente nel body JSON)
 */
class UserController
{
    public function __construct(
        private PDO   $pdo,
        private array $currentUser
    ) {}

    // ── GET /api/users ────────────────────────────────────────────────────
    public function list(): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'utenti', 'SELECT');

        $stmt = $this->pdo->query(
            'SELECT u.ID_utente, u.Email, u.N_Telefono, u.ID_ruolo, r.Nome_ruolo
             FROM Utenti u
             LEFT JOIN Ruoli r ON r.ID_ruolo = u.ID_ruolo
             ORDER BY u.ID_utente'
        );

        echo json_encode(['users' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
    }

    // ── DELETE /api/users/{id} ────────────────────────────────────────────
    public function delete(int $idTarget): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'utenti', 'DELETE');

        // Non si può eliminare il proprio account
        if ($idTarget === $this->currentUser['user_id']) {
            http_response_code(422);
            echo json_encode(['error' => 'Non puoi eliminare il tuo account.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $this->pdo->prepare('DELETE FROM Utenti WHERE ID_utente = ?');
        $stmt->execute([$idTarget]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Utente non trovato.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }
}
