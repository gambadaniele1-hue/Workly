<?php
session_start(); // Avvia la sessione PHP
// Nessun controllo login: la home è sempre accessibile
?>
<!doctype html>
<html lang="it">
    <head>
        <title>Corna Mattia Progetti scolastici 25/26</title>
        <meta charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1, shrink-to-fit=no"
        />
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
            crossorigin="anonymous"
        />

    </head>


    <body>
        <header>
        </header>
                <main>
                    <div class="container mt-5">
                        <h2 class="text-center mb-4">Scegli una materia</h2>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="informatica.php" class="btn btn-success btn-lg">INFORMATICA</a>
                            <a href="tep.php" class="btn btn-primary btn-lg">TPS</a>
                            <a href="gpo.php" class="btn btn-warning btn-lg">GPO</a>
                        </div>
                    </div>
                </main>
        <footer>
        </footer>
        <script
            src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
            integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
            crossorigin="anonymous"
        ></script>

        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
            integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
            crossorigin="anonymous"
        ></script>
    </body>
</html>
