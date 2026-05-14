<?php
session_start();
$_SESSION['user_id'] = 5;
$_SESSION['email'] = 'a@gmail.com';
ob_start();
include __DIR__ . '/../phpMyAdmin/public/SITO/BPIC/storico_buste_paga.php';
$html = ob_get_clean();
file_put_contents(__DIR__ . '/storico_page_user5.html', $html);
echo "OK: pagina storico renderizzata in " . __DIR__ . "/storico_page_user5.html\n";
?>