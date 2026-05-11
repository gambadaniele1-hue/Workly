<?php
require_once __DIR__ . '/phpMyAdmin/public/SITO/BPIC/database.php';
$rows = $pdo->query('SELECT ID_busta, ID_utente, Mese_riferimento, Stipendio_lordo, Stipendio_netto FROM Busta_paga ORDER BY ID_busta DESC')->fetchAll();
foreach ($rows as $r) {
    echo implode(' | ', [$r['ID_busta'] ?? 'NULL', $r['ID_utente'] ?? 'NULL', $r['Mese_riferimento'] ?? 'NULL', $r['Stipendio_lordo'] ?? 'NULL', $r['Stipendio_netto'] ?? 'NULL']) . "\n";
}
?>