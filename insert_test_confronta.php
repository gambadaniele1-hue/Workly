<?php
require_once __DIR__ . '/phpMyAdmin/public/SITO/BPIC/database.php';
try {
    $stmt = $pdo->prepare('INSERT INTO Confronta (ID_utente, ID_busta) VALUES (?, ?)');
    $stmt->execute([1, 9]);
    echo "Inserito OK, id: " . $pdo->lastInsertId() . "\n";
} catch (Exception $e) {
    echo "Errore insert: " . $e->getMessage() . "\n";
}

$rows = $pdo->query('SELECT * FROM Confronta')->fetchAll();
var_export($rows);
?>