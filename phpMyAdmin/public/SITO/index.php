<?php
session_start(); // Avvia la sessione PHP
// Nessun controllo login: la home è sempre accessibile
?>
<!doctype html> <?php /* Dichiara il tipo di documento come HTML5 */ ?>
<html lang="it"> <?php /* Inizio del documento HTML, lingua italiana */ ?>
    <head> <?php /* Inizio dell'header della pagina */ ?>
        <title>Corna Mattia Progetti scolastici 25/26</title> <?php /* Titolo della pagina */ ?>
        <?php /* Required meta tags - Meta tag necessari per la corretta visualizzazione */ ?>
        <meta charset="utf-8" /> <?php /* Set di caratteri UTF-8 */ ?>
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1, shrink-to-fit=no"
        /> <?php /* Rende la pagina responsive sui dispositivi mobili */ ?>

    <?php /* Bootstrap CSS v5.2.1: importa lo stile grafico - Inclusione del CSS di Bootstrap */ ?>
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
            crossorigin="anonymous"
        /> <?php /* Collegamento al file CSS di Bootstrap tramite CDN */ ?>

    </head> <?php /* Fine dell'header */ ?>


    <body> <?php /* Inizio del corpo della pagina */ ?>
        <header>
            <?php /* place navbar here - Qui può essere inserita la barra di navigazione */ ?>
        </header>
                <main>
                    <div class="container mt-5"> <?php /* Contenitore principale con margine superiore */ ?>
                        <h2 class="text-center mb-4">Scegli una materia</h2> <?php /* Titolo centrato */ ?>
                        <div class="d-flex justify-content-center gap-3"> <?php /* Pulsanti centrati con spazio */ ?>
                            <?php /* Pulsanti per scegliere la materia: portano alle rispettive pagine */ ?>
                            <a href="informatica.php" class="btn btn-success btn-lg">INFORMATICA</a> <?php /* Pulsante Informatica */ ?>
                            <a href="tep.php" class="btn btn-primary btn-lg">TPS</a> <?php /* Pulsante TPS */ ?>
                            <a href="gpo.php" class="btn btn-warning btn-lg">GPO</a> <?php /* Pulsante GPO */ ?>
                        </div>
                    </div>
                </main>
        <footer>
            <?php /* place footer here - Qui può essere inserito il piè di pagina */ ?>
        </footer>
    <?php /* Bootstrap JavaScript Libraries: necessari per alcune funzionalità grafiche - Inclusione delle librerie JavaScript di Bootstrap */ ?>
        <script
            src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
            integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
            crossorigin="anonymous"
        ></script> <?php /* Inclusione di Popper.js necessario per alcuni componenti Bootstrap */ ?>

        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
            integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
            crossorigin="anonymous"
        ></script> <?php /* Inclusione del file JavaScript di Bootstrap tramite CDN */ ?>
    </body> <?php /* Fine del corpo della pagina */ ?>
</html> <?php /* Fine del documento HTML */ ?>
