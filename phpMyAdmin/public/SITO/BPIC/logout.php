<?php
/**
 * File: logout.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
session_start();
session_destroy();
header("Location: /SITO/BPIC/login.php");
exit;
?>