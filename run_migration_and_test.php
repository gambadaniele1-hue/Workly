<?php
// Esegue la migrazione e crea una busta di test
require_once __DIR__ . '/phpMyAdmin/public/SITO/BPIC/database.php';

$sql = file_get_contents(__DIR__ . '/Database/migrate_busta_paga.sql');
if ($sql === false) {
    echo "Errore: impossibile leggere il file di migrazione\n";
    exit(1);
}

$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $i => $stmt) {
    if ($stmt === '') continue;
    try {
        $pdo->exec($stmt);
        echo "OK: eseguita statement #" . ($i+1) . "\n";
    } catch (PDOException $e) {
        echo "ERRORE statement #" . ($i+1) . ": " . $e->getMessage() . "\n";
    }
}

// Ora simuliamo una richiesta POST a generate_busta.php
echo "\n--- Eseguo generazione busta di prova ---\n";

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
  'mese' => date('Y-m'),
  'ore_lavorate' => 160,
  'paga_oraria' => 10.00,
  'ore_ferie' => 0,
  'ore_malattia' => 0,
  'ore_straordinari' => 0,
  'ore_trasferta' => 0,
  'ore_festivi' => 0,
  'ore_prefestivi' => 0,
  'ore_notturne' => 0,
  'ore_reperibilita' => 0,
];

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'test@example.com';

ob_start();
include __DIR__ . '/phpMyAdmin/public/SITO/BPIC/api/generate_busta.php';
$html = ob_get_clean();
file_put_contents(__DIR__ . '/simulate_output_after_migration.html', $html);
echo "Generazione completata, output salvato in simulate_output_after_migration.html\n";

// Conta righe nella tabella Confronta
try {
    $count = (int)$pdo->query('SELECT COUNT(*) FROM Confronta')->fetchColumn();
    echo "Righe in Confronta: $count\n";
} catch (Exception $e) {
    echo "Impossibile contare Confronta: " . $e->getMessage() . "\n";
}

?>