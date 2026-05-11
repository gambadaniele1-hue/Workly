<?php
require_once __DIR__ . '/phpMyAdmin/public/SITO/BPIC/database.php';
$stmt = $pdo->query('DESCRIBE Busta_paga');
$rows = $stmt->fetchAll();
foreach ($rows as $r) {
    echo $r['Field'] . "\t" . $r['Type'] . "\t" . $r['Key'] . "\t" . ($r['Default'] ?? 'NULL') . "\n";
}
?>