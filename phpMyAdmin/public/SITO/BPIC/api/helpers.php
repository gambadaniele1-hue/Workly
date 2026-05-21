<?php
declare(strict_types=1);

/*
 * api/helpers.php — Funzioni condivise tra il middleware auth.php e il router index.php.
 *
 * Estratto da auth.php per evitare che il router debba includere l'intero middleware
 * (che farebbe il check JWT e il redirect, cosa sbagliata per un'API che risponde JSON).
 */

/**
 * Controlla che il ruolo abbia il permesso richiesto sulla risorsa.
 * Se manca il permesso risponde con HTTP 403 JSON e termina lo script.
 *
 * @param string $risorsa  Colonna Risorsa in tabella Privilegi  (es. 'utenti')
 * @param string $azione   Colonna Azione  in tabella Privilegi  (es. 'SELECT', 'DELETE', 'ALL')
 */
function require_permission(PDO $pdo, int $roleId, string $risorsa, string $azione): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM Ruolo_Privilegio rp
         JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
         WHERE rp.ID_ruolo = ?
           AND p.Risorsa   = ?
           AND (p.Azione = "ALL" OR p.Azione = ?)'
    );
    $stmt->execute([$roleId, $risorsa, $azione]);

    if ((int)$stmt->fetchColumn() === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Permesso negato.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
