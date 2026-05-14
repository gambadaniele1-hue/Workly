<?php
session_start();
$_SESSION['user_id'] = 5;
$_SESSION['email'] = 'a@gmail.com';

include __DIR__ . '/../phpMyAdmin/public/SITO/BPIC/storico_buste_paga.php';

?>