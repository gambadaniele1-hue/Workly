<?php
declare(strict_types=1);

/*
 * SubscriptionController — Piani e acquisto abbonamento.
 *
 * Rotte:
 *   GET  /api/subscription/plans — restituisce i piani disponibili (dati mock)
 *   POST /api/subscription/buy   — acquista un abbonamento (TODO)
 */
class SubscriptionController
{
    public function __construct(
        private PDO        $pdo,
        private array|null $currentUser = null
    ) {}

    // ── GET /api/subscription/plans ───────────────────────────────────────
    public function plans(): void
    {
        // TODO: caricare i piani da una tabella Piani_abbonamento da creare nel DB
        echo json_encode([
            'plans' => [
                [
                    'id'       => 1,
                    'nome'     => 'Base',
                    'prezzo'   => 9.99,
                    'periodo'  => 'mese',
                    'features' => [
                        'Download PDF buste paga',
                        'Archivio storico buste paga',
                        'Invio PDF via email',
                    ],
                ],
                [
                    'id'       => 2,
                    'nome'     => 'Pro',
                    'prezzo'   => 19.99,
                    'periodo'  => 'mese',
                    'features' => [
                        'Tutto il piano Base',
                        'Confronto tra buste paga',
                        'Supporto prioritario',
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    // ── POST /api/subscription/buy ────────────────────────────────────────
    public function buy(): void
    {
        require_permission($this->pdo, $this->currentUser['role_id'], 'contratti', 'INSERT');

        // TODO: integrare con un gateway di pagamento (es. Stripe, PayPal):
        //       1. Ricevere nel body l'ID del piano scelto
        //       2. Creare una sessione di pagamento sul gateway
        //       3. Su callback di conferma pagamento:
        //          - Aggiornare ID_ruolo in Utenti a 2 (utente_abbonato)
        //          - Emettere un nuovo JWT col ruolo aggiornato (setcookie)
        //          - Registrare l'acquisto in una tabella Abbonamenti (da creare)

        http_response_code(501);
        echo json_encode(['error' => 'Acquisto abbonamento non ancora implementato.'], JSON_UNESCAPED_UNICODE);
    }
}
