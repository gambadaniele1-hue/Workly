<?php
require_once __DIR__ . '/phpMyAdmin/public/SITO/BPIC/database.php';
try {
    $rows = $pdo->query('SELECT * FROM Confronta ORDER BY Data_confronto DESC')->fetchAll();
    echo "Confronta rows: " . count($rows) . "\n";
    foreach ($rows as $r) {
        echo json_encode($r) . "\n";
    }
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
?>