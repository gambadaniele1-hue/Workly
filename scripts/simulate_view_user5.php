<?php
// Simula la visualizzazione di mockup_viste.php per utente 5
session_start();
$_SESSION['user_id'] = 5;
$_SESSION['email'] = 'a@gmail.com';

ob_start();
include __DIR__ . '/../phpMyAdmin/public/SITO/BPIC/mockup_viste.php';
$html = ob_get_clean();
file_put_contents(__DIR__ . '/mockup_viste_user5.html', $html);
echo "Output mockup_viste generato in " . __DIR__ . "/mockup_viste_user5.html\n";
?>