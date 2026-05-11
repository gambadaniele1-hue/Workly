<?php
session_start();
$_SESSION['user_id'] = 5;
$_SESSION['email'] = 'a@gmail.com';
$_GET['id_busta'] = 10;
ob_start();
include __DIR__ . '/phpMyAdmin/public/SITO/BPIC/download_busta_pdf.php';
$pdfBinary = ob_get_clean();
file_put_contents(__DIR__ . '/test_cedolino.pdf', $pdfBinary);
echo "PDF generato: test_cedolino.pdf\n";
?>