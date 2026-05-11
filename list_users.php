<?php
require_once __DIR__ . '/phpMyAdmin/public/SITO/BPIC/database.php';
$rows = $pdo->query('SELECT ID_utente, Email FROM Utenti')->fetchAll();
foreach ($rows as $r) {
    echo $r['ID_utente'] . ' - ' . ($r['Email'] ?? 'NULL') . "\n";
}
?>