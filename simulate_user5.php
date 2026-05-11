<?php
// Simula POST per utente con ID 5
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
  'mese' => date('Y-m'),
  'ore_lavorate' => 170,
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
$_SESSION['user_id'] = 5;
$_SESSION['email'] = 'a@gmail.com';

ob_start();
include __DIR__ . '/phpMyAdmin/public/SITO/BPIC/api/generate_busta.php';
$html = ob_get_clean();
file_put_contents(__DIR__ . '/simulate_output_user5.html', $html);
echo "Simulazione utente 5 eseguita, output in simulate_output_user5.html\n";
?>