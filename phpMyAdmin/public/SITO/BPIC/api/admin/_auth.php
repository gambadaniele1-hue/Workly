<?php
declare(strict_types=1);

/*
 * api/admin/_auth.php — Helper condiviso per tutti gli endpoint admin.
 *
 * Includi questo file all'inizio di ogni endpoint nella cartella api/admin/.
 * Dopo l'inclusione hai disponibile $pdo e $currentUser (da auth.php).
 *
 * Usa require_permission() per verificare che l'utente abbia il permesso
 * necessario prima di eseguire un'operazione.
 */

require_once __DIR__ . '/../../auth.php'; // verifica JWT, popola $currentUser e $pdo

header('Content-Type: application/json; charset=utf-8');

/*
 * Controlla se il ruolo dell'utente ha un certo permesso nel DB.
 * Se il permesso manca, risponde con 403 e termina lo script.
 *
 * $risorsa : colonna Risorsa nella tabella Privilegi  (es. 'utenti', 'ruoli')
 * $azione  : colonna Azione  nella tabella Privilegi  (es. 'SELECT', 'DELETE', 'ALL')
 *
 * Un privilegio con Azione='ALL' soddisfa qualsiasi richiesta.
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
