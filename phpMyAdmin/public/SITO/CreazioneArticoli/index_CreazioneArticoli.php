<?php
session_start();
if (!isset($_SESSION['loggato']) || $_SESSION['loggato'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Articolo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Inserisci un nuovo articolo</h1>
    <?php /* Form per inserire un nuovo articolo */ ?>
    <form action="insert.php" method="post">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome articolo</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
        </div>
        <div class="mb-3">
            <label for="descrizione" class="form-label">Descrizione</label>
            <textarea class="form-control" id="descrizione" name="descrizione" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label for="prezzo" class="form-label">Prezzo (€)</label>
            <input type="number" step="0.01" class="form-control" id="prezzo" name="prezzo" required>
        </div>
        <div class="mb-3">
            <label for="immagine" class="form-label">Immagine</label>
            <?php /* Select per scegliere il file immagine tra quelli disponibili */ ?>
            <select class="form-select" id="immagine" name="immagine" required>
                <option value="biscotti.jpg">biscotti</option>
                <option value="latte.jpg">latte</option>
                <option value="patatine.jpg">patatine</option>
            </select>
            <div class="form-text">Scegli tra le immagini disponibili nella cartella IMG.</div>
        </div>
        <button type="submit" class="btn btn-primary">Inserisci</button>
    </form>
</div>
</body>
</html>
