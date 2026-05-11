<?php
require_once __DIR__ . '/phpMyAdmin/public/SITO/BPIC/database.php';
$rows = $pdo->query('SHOW INDEX FROM Busta_paga')->fetchAll();
foreach ($rows as $r) {
    echo $r['Key_name'] . '\t' . $r['Column_name'] . '\t' . $r['Non_unique'] . "\n";
}
?>