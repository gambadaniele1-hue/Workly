<?php
// session_start(); // Login NON richiesto per esercizio creazione articoli
require_once 'articolo.php'; // Include la classe Articolo
$error = '';
$articolo = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Se il form è stato inviato
    $nome = trim($_POST['nome'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $prezzo = trim($_POST['prezzo'] ?? '');
    $immagine = trim($_POST['immagine'] ?? '');
    if ($nome === '' || $descrizione === '' || $prezzo === '' || $immagine === '') {
        $error = 'Tutti i campi sono obbligatori.';
    } elseif (!is_numeric($prezzo)) {
        $error = 'Il prezzo deve essere un numero.';
    } else {
        $articolo = new Articolo($nome, $descrizione, $prezzo, $immagine);
        $file = __DIR__ . '/articoli.json';
        $articoli = [];
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $articoli = json_decode($json, true);
            if (!is_array($articoli)) {
                $articoli = [];
            }
        }
        $articoli[] = [
            'nome' => $nome,
            'descrizione' => $descrizione,
            'prezzo' => $prezzo,
            'immagine' => $immagine
        ];
        $json_data = json_encode($articoli, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json_data !== false) {
            file_put_contents($file, $json_data, LOCK_EX);
        } else {
            $error = 'Errore nella codifica JSON.';
        }
    }
}
?>
<!DOCTYPE html> <?php /* Dichiara il tipo di documento come HTML5 */ ?>
<html lang="it"> <?php /* Inizio documento HTML, lingua italiana */ ?>
<head>
    <meta charset="UTF-8"> <?php /* Set di caratteri UTF-8 */ ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <?php /* Responsive */ ?>
    <title>Articolo Inserito</title> <?php /* Titolo della pagina */ ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <?php /* Bootstrap CSS */ ?>
</head>
<body>
<div class="container mt-5"> <?php /* Contenitore principale con margine superiore */ ?>
    <h1 class="mb-4">Inserisci un articolo</h1> <?php /* Titolo */ ?>
    <?php
    if ($articolo) {
        echo '<div class="alert alert-success">Articolo inserito correttamente!</div>';
        $articolo->show();
        echo '<a href="index.php" class="btn btn-secondary mt-4">Torna indietro</a> ';
        echo '<a href="visualizza.php" class="btn btn-success mt-4 ms-2">Visualizza Articoli</a>';
        // Reset dei valori POST per permettere un nuovo inserimento
        $_POST = [];
    }
    if (!$articolo) {
        if ($error) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
        }
    ?>
    <form method="post" class="mt-4">
        <a href="visualizza.php" class="btn btn-success mb-3">Visualizza Articoli</a>
        <div class="mb-3">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="descrizione" class="form-label">Descrizione</label>
            <textarea class="form-control" id="descrizione" name="descrizione" required><?php echo htmlspecialchars($_POST['descrizione'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="prezzo" class="form-label">Prezzo</label>
            <input type="text" class="form-control" id="prezzo" name="prezzo" required value="<?php echo htmlspecialchars($_POST['prezzo'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="immagine" class="form-label">Scegli immagine</label>
            <select class="form-control" id="immagine" name="immagine" required>
                <option value="">-- Seleziona immagine --</option>
                <?php
                $imgDir = __DIR__ . '/IMG';
                if (is_dir($imgDir)) {
                    $imgs = array_diff(scandir($imgDir), ['.','..']);
                    foreach ($imgs as $img) {
                        $imgBase = pathinfo($img, PATHINFO_FILENAME);
                        $sel = (isset($_POST['immagine']) && $_POST['immagine'] === $img) ? 'selected' : '';
                        echo '<option value="'.htmlspecialchars($img).'" '.$sel.'>'.htmlspecialchars($imgBase).'</option>';
                    }
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Inserisci</button>
    </form>
    <?php } ?>
    <?php /* Pulsante grande con icona casetta per tornare alla home */ ?>
    <div class="d-flex justify-content-center mt-5">
        <a href="index.php" class="btn btn-lg btn-success" title="Home">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-house-door" viewBox="0 0 16 16">
                <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 2 7.5V14a1 1 0 0 0 1 1h3.5a.5.5 0 0 0 .5-.5V11a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v3.5a.5.5 0 0 0 .5.5H13a1 1 0 0 0 1-1V7.5a.5.5 0 0 0-.146-.354l-6-6z"/>
                <path d="M13 2.5V6l-5-5-5 5V2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5z"/>
            </svg>
        </a>
    </div>
</div>
</body>
</html>
