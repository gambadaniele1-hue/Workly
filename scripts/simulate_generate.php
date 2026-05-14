<?php
// Simula una richiesta POST a generate_busta.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
  'mese' => date('Y-m'),
  'ore_lavorate' => 168,
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
// Simula session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'test@example.com';

// Esegui lo script e cattura output
ob_start();
include __DIR__ . '/../phpMyAdmin/public/SITO/BPIC/api/generate_busta.php';
$html = ob_get_clean();
file_put_contents(__DIR__ . '/simulate_output.html', $html);
echo "Simulazione eseguita. Output salvato in " . __DIR__ . "/simulate_output.html\n";
?>