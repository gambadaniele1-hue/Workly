<?php
declare(strict_types=1);

/*
 * ContractController — Impostazioni contratto dell'utente.
 *
 * Rotte:
 *   GET  /api/contract — restituisce le impostazioni correnti (null se non configurato)
 *   POST /api/contract — salva (insert o update) le impostazioni
 *
 * Entrambe le rotte richiedono il privilegio contratti/INSERT
 * (posseduto da utente_abbonato e utente_non_abbonato).
 */
class ContractController
{
    public function __construct(
        private PDO   $pdo,
        private array $currentUser
    ) {}

    // ── GET /api/contract ─────────────────────────────────────────────────
    // Restituisce null se l'utente non ha ancora configurato il contratto.
    public function get(): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'contratti', 'INSERT');

        $stmt = $this->pdo->prepare(
            'SELECT tipologia_dipendente, Livello_dipendente,
                    Maggiorazione_notturna, Maggiorazione_straordinaria,
                    Maggiorazione_festiva, Maggiorazione_prefestiva,
                    Indennita_malattia, Indennita_reperibilita, Indennita_trasferta,
                    Tredicesima, Quattordicesima
             FROM Impostazioni_contratto
             WHERE ID_utente = ? LIMIT 1'
        );
        $stmt->execute([$this->currentUser['user_id']]);
        $row = $stmt->fetch();

        // null = non ancora configurato (il frontend mostra il warning urgente)
        echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
    }

    // ── POST /api/contract ────────────────────────────────────────────────
    // Upsert: inserisce o aggiorna le impostazioni contratto.
    public function save(): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'contratti', 'INSERT');

        $input    = $this->body();
        $tipologia = (string)($input['tipologia_dipendente'] ?? '');
        $livello   = trim((string)($input['livello_dipendente'] ?? ''));

        $allowed = ['Statale', 'Mettalmeccanico', 'Commerciale', ''];
        if (!in_array($tipologia, $allowed, true)) {
            http_response_code(422);
            echo json_encode(['error' => 'Tipologia dipendente non valida.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // INSERT … ON DUPLICATE KEY UPDATE: upsert sulla chiave primaria ID_utente
        $stmt = $this->pdo->prepare(
            'INSERT INTO Impostazioni_contratto
                (ID_utente, tipologia_dipendente, Livello_dipendente,
                 Maggiorazione_notturna, Maggiorazione_straordinaria,
                 Maggiorazione_festiva, Maggiorazione_prefestiva,
                 Indennita_malattia, Indennita_reperibilita, Indennita_trasferta,
                 Tredicesima, Quattordicesima)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                tipologia_dipendente        = VALUES(tipologia_dipendente),
                Livello_dipendente          = VALUES(Livello_dipendente),
                Maggiorazione_notturna      = VALUES(Maggiorazione_notturna),
                Maggiorazione_straordinaria = VALUES(Maggiorazione_straordinaria),
                Maggiorazione_festiva       = VALUES(Maggiorazione_festiva),
                Maggiorazione_prefestiva    = VALUES(Maggiorazione_prefestiva),
                Indennita_malattia          = VALUES(Indennita_malattia),
                Indennita_reperibilita      = VALUES(Indennita_reperibilita),
                Indennita_trasferta         = VALUES(Indennita_trasferta),
                Tredicesima                 = VALUES(Tredicesima),
                Quattordicesima             = VALUES(Quattordicesima)'
        );
        $stmt->execute([
            $this->currentUser['user_id'],
            $tipologia,
            $livello,
            (float)($input['maggiorazione_notturna']      ?? 0),
            (float)($input['maggiorazione_straordinaria'] ?? 0),
            (float)($input['maggiorazione_festiva']       ?? 0),
            (float)($input['maggiorazione_prefestiva']    ?? 0),
            (float)($input['indennita_malattia']          ?? 0),
            (float)($input['indennita_reperibilita']      ?? 0),
            (float)($input['indennita_trasferta']         ?? 0),
            ($input['tredicesima']    ?? 'NO') === 'SI' ? 'SI' : 'NO',
            ($input['quattordicesima'] ?? 'NO') === 'SI' ? 'SI' : 'NO',
        ]);

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    private function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return [];
    }
}
