<?php
session_start();
$_SESSION['user_id'] = 5;
$_SESSION['email'] = 'a@gmail.com';
$_SERVER['REQUEST_METHOD'] = 'POST';

$ore = (int)($argv[1] ?? 160);
$_POST = [
  'mese' => date('Y-m'),
  'ore_lavorate' => $ore,
  'paga_oraria' => 10,
  'ore_ferie' => 0,
  'ore_malattia' => 0,
  'ore_straordinari' => 0,
  'ore_trasferta' => 0,
  'ore_festivi' => 0,
  'ore_prefestivi' => 0,
  'ore_notturne' => 0,
  'ore_reperibilita' => 0,
];

ob_start();
include __DIR__ . '/phpMyAdmin/public/SITO/BPIC/api/generate_busta.php';
$html = ob_get_clean();

echo $html;
?>