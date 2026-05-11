<?php
session_start();
$_SESSION['user_id'] = 5;
$_SESSION['email'] = 'a@gmail.com';

function runGen(array $post): string {
    $_POST = $post;
    ob_start();
    include __DIR__ . '/phpMyAdmin/public/SITO/BPIC/api/generate_busta.php';
    return ob_get_clean();
}

$_SERVER['REQUEST_METHOD'] = 'POST';

$html1 = runGen([
  'mese' => date('Y-m'),
  'ore_lavorate' => 150,
  'paga_oraria' => 10,
  'ore_ferie' => 0,
  'ore_malattia' => 0,
  'ore_straordinari' => 0,
  'ore_trasferta' => 0,
  'ore_festivi' => 0,
  'ore_prefestivi' => 0,
  'ore_notturne' => 0,
  'ore_reperibilita' => 0,
]);

$html2 = runGen([
  'mese' => date('Y-m'),
  'ore_lavorate' => 160,
  'paga_oraria' => 10,
  'ore_ferie' => 0,
  'ore_malattia' => 0,
  'ore_straordinari' => 0,
  'ore_trasferta' => 0,
  'ore_festivi' => 0,
  'ore_prefestivi' => 0,
  'ore_notturne' => 0,
  'ore_reperibilita' => 0,
]);

file_put_contents(__DIR__ . '/test_no_409_1.html', $html1);
file_put_contents(__DIR__ . '/test_no_409_2.html', $html2);

echo "OK: eseguite due generazioni stesso mese senza eccezioni fatali.\n";
?>