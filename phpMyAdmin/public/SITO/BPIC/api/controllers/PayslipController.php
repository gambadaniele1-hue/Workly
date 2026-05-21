<?php
declare(strict_types=1);

/*
 * PayslipController — Gestione buste paga.
 *
 * Rotte:
 *   GET    /api/payslip              — lista buste paga dell'utente
 *   POST   /api/payslip              — genera una nuova busta paga
 *   POST   /api/payslip/compare      — confronta due buste paga
 *   GET    /api/payslip/{id}         — dettaglio singola busta
 *   DELETE /api/payslip/{id}         — elimina busta (solo abbonati)
 *   GET    /api/payslip/{id}/pdf     — scarica PDF (solo abbonati) — TODO
 *   POST   /api/payslip/{id}/email   — invia PDF via email (solo abbonati) — TODO
 */
class PayslipController
{
    public function __construct(
        private PDO   $pdo,
        private array $currentUser
    ) {}

    // ── GET /api/payslip ──────────────────────────────────────────────────
    public function list(): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'buste_paga', 'INSERT');

        $stmt = $this->pdo->prepare(
            'SELECT ID_busta, Mese_riferimento, Stipendio_lordo, Stipendio_netto,
                    Ore_lavorate, Paga_oraria, Ore_ferie, Ore_malattia,
                    Ore_straordinari, Ore_festivi, Ore_prefestivi, Ore_notturne,
                    Ore_reperibilita, Ore_trasferta, Data_creazione
             FROM Busta_paga
             WHERE ID_utente = ?
             ORDER BY Mese_riferimento DESC'
        );
        $stmt->execute([$this->currentUser['user_id']]);

        echo json_encode(['payslips' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
    }

    // ── GET /api/payslip/{id} ─────────────────────────────────────────────
    public function get(int $id): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'buste_paga', 'INSERT');

        $stmt = $this->pdo->prepare(
            'SELECT * FROM Busta_paga WHERE ID_busta = ? AND ID_utente = ? LIMIT 1'
        );
        $stmt->execute([$id, $this->currentUser['user_id']]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Busta paga non trovata.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode($row, JSON_UNESCAPED_UNICODE);
    }

    // ── POST /api/payslip ─────────────────────────────────────────────────
    public function generate(): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'buste_paga', 'INSERT');

        $input = $this->body();
        $mese  = trim((string)($input['mese_riferimento'] ?? ''));
        $paga  = (float)($input['paga_oraria']      ?? 0);
        $ore   = (float)($input['ore_lavorate']      ?? 0);

        if ($mese === '' || !preg_match('/^\d{4}-\d{2}$/', $mese) || $paga <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Dati non validi. Verifica mese (YYYY-MM) e paga oraria.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // TODO: leggere Impostazioni_contratto dell'utente e applicare le
        //       maggiorazioni (straordinari, festivi, notturni ecc.) al lordo.
        // TODO: applicare IRPEF e detrazioni per calcolare il netto reale.
        // Calcolo semplificato — lordo = ore * paga; netto ≈ lordo * 67,5 % (stima ~32,5 % trattenute)
        $lordo = round($ore * $paga, 2);
        $netto = round($lordo * 0.675, 2);

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO Busta_paga
                    (ID_utente, Mese_riferimento, Stipendio_lordo, Stipendio_netto,
                     Ore_lavorate, Paga_oraria, Ore_ferie, Ore_malattia, Ore_straordinari,
                     Ore_festivi, Ore_prefestivi, Ore_notturne, Ore_reperibilita, Ore_trasferta)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $this->currentUser['user_id'],
                $mese, $lordo, $netto, $ore, $paga,
                (float)($input['ore_ferie']        ?? 0),
                (float)($input['ore_malattia']     ?? 0),
                (float)($input['ore_straordinari'] ?? 0),
                (float)($input['ore_festivi']      ?? 0),
                (float)($input['ore_prefestivi']   ?? 0),
                (float)($input['ore_notturne']     ?? 0),
                (float)($input['ore_reperibilita'] ?? 0),
                (float)($input['ore_trasferta']    ?? 0),
            ]);
            $newId = (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate')) {
                http_response_code(409);
                echo json_encode(['error' => 'Esiste già una busta paga per questo mese.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            http_response_code(500);
            echo json_encode(['error' => 'Errore durante la generazione.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(201);
        echo json_encode([
            'id_busta'        => $newId,
            'stipendio_lordo' => $lordo,
            'stipendio_netto' => $netto,
        ], JSON_UNESCAPED_UNICODE);
    }

    // ── DELETE /api/payslip/{id} ──────────────────────────────────────────
    // Solo utenti abbonati (privilgio archivio/SELECT).
    public function delete(int $id): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'archivio', 'SELECT');

        $stmt = $this->pdo->prepare(
            'DELETE FROM Busta_paga WHERE ID_busta = ? AND ID_utente = ?'
        );
        $stmt->execute([$id, $this->currentUser['user_id']]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Busta paga non trovata.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    // ── GET /api/payslip/{id}/pdf ─────────────────────────────────────────
    public function downloadPdf(int $id): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'pdf', 'SELECT');

        // TODO: generare il PDF con una libreria (es. FPDF, TCPDF, mPDF):
        //       - Recuperare i dati da Busta_paga JOIN Impostazioni_contratto
        //       - Formattare il documento secondo il layout aziendale
        //       - Rispondere con header Content-Type: application/pdf

        http_response_code(501);
        echo json_encode(['error' => 'Generazione PDF non ancora implementata.'], JSON_UNESCAPED_UNICODE);
    }

    // ── POST /api/payslip/{id}/email ──────────────────────────────────────
    public function sendEmail(int $id): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'email', 'INSERT');

        // TODO: generare il PDF (vedi downloadPdf) e inviarlo via SMTP o API
        //       (es. PHPMailer + SendGrid / SMTP aziendale):
        //       - Recuperare l'email dell'utente da Utenti
        //       - Allegare il PDF al messaggio
        //       - Logare l'invio in una tabella LogEmail (da creare)

        http_response_code(501);
        echo json_encode(['error' => 'Invio email non ancora implementato.'], JSON_UNESCAPED_UNICODE);
    }

    // ── POST /api/payslip/compare ─────────────────────────────────────────
    public function compare(): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'confronto', 'SELECT');

        $input = $this->body();
        $idA   = (int)($input['id_a'] ?? 0);
        $idB   = (int)($input['id_b'] ?? 0);

        if ($idA <= 0 || $idB <= 0 || $idA === $idB) {
            http_response_code(422);
            echo json_encode(['error' => 'Seleziona due buste paga diverse.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Entrambe le buste devono appartenere all'utente corrente
        $stmt = $this->pdo->prepare(
            'SELECT * FROM Busta_paga WHERE ID_busta = ? AND ID_utente = ? LIMIT 1'
        );

        $stmt->execute([$idA, $this->currentUser['user_id']]);
        $bustaA = $stmt->fetch();

        $stmt->execute([$idB, $this->currentUser['user_id']]);
        $bustaB = $stmt->fetch();

        if (!$bustaA || !$bustaB) {
            http_response_code(404);
            echo json_encode(['error' => 'Una o entrambe le buste paga non trovate.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'busta_a'    => $bustaA,
            'busta_b'    => $bustaB,
            'diff_lordo' => round((float)$bustaA['Stipendio_lordo'] - (float)$bustaB['Stipendio_lordo'], 2),
            'diff_netto' => round((float)$bustaA['Stipendio_netto'] - (float)$bustaB['Stipendio_netto'], 2),
        ], JSON_UNESCAPED_UNICODE);
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
