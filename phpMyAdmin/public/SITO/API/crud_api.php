<?php /* Pulsante grande con icona casetta per tornare alla home - Pulsante per tornare alla home */ ?>
<div class="d-flex justify-content-center mt-5">
    <a href="index.php" class="btn btn-lg btn-success" title="Home">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-house-door" viewBox="0 0 16 16">
            <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 2 7.5V14a1 1 0 0 0 1 1h3.5a.5.5 0 0 0 .5-.5V11a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v3.5a.5.5 0 0 0 .5.5H13a1 1 0 0 0 1-1V7.5a.5.5 0 0 0-.146-.354l-6-6z"/>
            <path d="M13 2.5V6l-5-5-5 5V2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5z"/>
        </svg>
    </a>
</div>
<!doctype html> <?php /* Dichiara il tipo di documento come HTML5 */ ?>
<html lang="it"> <?php /* Inizio documento HTML, lingua italiana */ ?>
    <head> <?php /* Inizio header */ ?>
        <title>Esercizio CRUD API (PHP/JS)</title> <?php /* Titolo della pagina */ ?>
        <meta charset="utf-8" /> <?php /* Set di caratteri UTF-8 */ ?>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" /> <?php /* Responsive */ ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" /> <?php /* Bootstrap CSS */ ?>
    </head>
    <body> <?php /* Inizio corpo pagina */ ?>
    <?php
    // Endpoint API
    $json_file = 'users.json'; // File JSON dove sono salvati gli utenti
    $result = '';
    $users = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : []; // Carica utenti dal file
    // CREATE
    if (isset($_POST['create'])) {
        $name = trim($_POST['name']); // Prende il nome dal form
        $email = trim($_POST['email']); // Prende l'email dal form
        if ($name && $email) {
            $id = count($users) > 0 ? max(array_column($users, 'id')) + 1 : 1; // Calcola nuovo ID
            $users[] = ['id' => $id, 'name' => $name, 'email' => $email]; // Aggiunge nuovo utente
            file_put_contents($json_file, json_encode($users, JSON_PRETTY_PRINT)); // Salva su file
            $result = 'Creato: ' . htmlspecialchars($name); // Messaggio di successo
        } else {
            $result = 'Dati mancanti'; // Messaggio di errore
        }
    }
    // READ
    if (isset($_POST['read'])) {
        if (count($users) === 0) {
            $result = 'Nessun utente presente.'; // Nessun utente trovato
        } else {
            $result = '<ul>';
            foreach ($users as $u) {
                $result .= '<li>ID: ' . $u['id'] . ' | ' . htmlspecialchars($u['name']) . ' (' . htmlspecialchars($u['email']) . ')</li>'; // Mostra ogni utente
            }
            $result .= '</ul>';
        }
    }
    // UPDATE
    if (isset($_POST['update'])) {
        $id = intval($_POST['id']); // Prende l'ID dal form
        $name = trim($_POST['name']); // Prende il nome
        $email = trim($_POST['email']); // Prende l'email
        $found = false;
        foreach ($users as &$u) {
            if ($u['id'] === $id) {
                if ($name) $u['name'] = $name; // Aggiorna nome se presente
                if ($email) $u['email'] = $email; // Aggiorna email se presente
                $found = true;
                break;
            }
        }
        unset($u);
        if ($found) {
            file_put_contents($json_file, json_encode($users, JSON_PRETTY_PRINT)); // Salva modifiche
            $result = 'Aggiornato utente con ID: ' . $id; // Messaggio di successo
        } else {
            $result = 'Utente non trovato'; // Messaggio di errore
        }
    }
    // DELETE
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']); // Prende l'ID dal form
        $old_count = count($users); // Conta utenti prima
        $users = array_filter($users, function($u) use ($id) { return $u['id'] !== $id; }); // Rimuove utente
        if (count($users) < $old_count) {
            file_put_contents($json_file, json_encode(array_values($users), JSON_PRETTY_PRINT)); // Salva su file
            $result = 'Eliminato utente con ID: ' . $id; // Messaggio di successo
        } else {
            $result = 'Utente non trovato'; // Messaggio di errore
        }
    }
    ?>
    <div class="container mt-4"> <?php /* Contenitore principale */ ?>
    <ul class="nav nav-tabs" id="crudTab" role="tablist"> <?php /* Tab per selezionare PHP o JS */ ?>
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="php-tab" data-bs-toggle="tab" data-bs-target="#php" type="button" role="tab">PHP</button> <?php /* Tab PHP */ ?>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="js-tab" data-bs-toggle="tab" data-bs-target="#js" type="button" role="tab">JS</button> <?php /* Tab JS */ ?>
          </li>
        </ul>
    <div class="tab-content" id="crudTabContent"> <?php /* Contenuto dei tab */ ?>
                    <div class="tab-pane fade show active p-3" id="php" role="tabpanel"> <?php /* Tab PHP */ ?>
                        <h2 class="text-center">CRUD API PHP</h2> <?php /* Titolo sezione PHP */ ?>
                        <form method="post" class="mb-3"> <?php /* Form PHP */ ?>
                <div class="row g-2">
                    <div class="col">
                        <input type="text" class="form-control" name="id" placeholder="ID (per Update/Delete)"> <?php /* Campo ID */ ?>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" name="name" placeholder="Nome"> <?php /* Campo Nome */ ?>
                    </div>
                    <div class="col">
                        <input type="email" class="form-control" name="email" placeholder="Email"> <?php /* Campo Email */ ?>
                    </div>
                </div>
                <div class="mt-2">
                    <button name="create" class="btn btn-success">Crea</button> <?php /* Bottone Crea */ ?>
                    <button name="read" class="btn btn-primary">Visualizza</button> <?php /* Bottone Visualizza */ ?>
                    <button name="update" class="btn btn-warning">Modifica</button> <?php /* Bottone Modifica */ ?>
                    <button name="delete" class="btn btn-danger">Elimina</button> <?php /* Bottone Elimina */ ?>
                </div>
            </form>
            <?php if ($result) { echo '<div class="alert alert-info">' . $result . '</div>'; } ?> <?php /* Mostra risultato */ ?>
                    </div>
                    <div class="tab-pane fade p-3" id="js" role="tabpanel"> <?php /* Tab JS */ ?>
                        <h2 class="text-center">CRUD API JS</h2> <?php /* Titolo sezione JS */ ?>
                        <form id="jsCrudForm" class="mb-3"> <?php /* Form JS */ ?>
                <div class="row g-2">
                    <div class="col">
                        <input type="text" class="form-control" id="js-id" placeholder="ID (per Update/Delete)"> <?php /* Campo ID */ ?>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" id="js-name" placeholder="Nome"> <?php /* Campo Nome */ ?>
                    </div>
                    <div class="col">
                        <input type="email" class="form-control" id="js-email" placeholder="Email"> <?php /* Campo Email */ ?>
                    </div>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-success" onclick="jsCreate()">Crea</button> <?php /* Bottone Crea */ ?>
                    <button type="button" class="btn btn-primary" onclick="jsRead()">Visualizza</button> <?php /* Bottone Visualizza */ ?>
                    <button type="button" class="btn btn-warning" onclick="jsUpdate()">Modifica</button> <?php /* Bottone Modifica */ ?>
                    <button type="button" class="btn btn-danger" onclick="jsDelete()">Elimina</button> <?php /* Bottone Elimina */ ?>
                </div>
            </form>
            <div id="jsResult"></div> <?php /* Risultato operazioni JS */ ?>
          </div>
        </div>
      </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script> <?php /* Popper.js */ ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" crossorigin="anonymous"></script> <?php /* Bootstrap JS */ ?>
      <script>
    const apiUrl = 'https://{{random}}.beeceptor.com/api/users'; <?php /* URL API di esempio */ ?>
    function jsCreate() { <?php /* Funzione per creare utente via JS */ ?>
          const name = document.getElementById('js-name').value; // Prende nome
          const email = document.getElementById('js-email').value; // Prende email
          fetch(apiUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ name, email })
          })
          .then(r => r.text())
          .then(data => document.getElementById('jsResult').innerHTML = `<div class='alert alert-success'>Creato: ${data}</div>`) <?php /* Mostra risultato */ ?>
          .catch(e => document.getElementById('jsResult').innerHTML = `<div class='alert alert-danger'>Errore: ${e}</div>`); <?php /* Mostra errore */ ?>
      }
    function jsRead() { <?php /* Funzione per leggere utenti via JS */ ?>
          fetch(apiUrl)
          .then(r => r.text())
          .then(data => document.getElementById('jsResult').innerHTML = `<div class='alert alert-primary'>Utenti: ${data}</div>`) <?php /* Mostra utenti */ ?>
          .catch(e => document.getElementById('jsResult').innerHTML = `<div class='alert alert-danger'>Errore: ${e}</div>`); <?php /* Mostra errore */ ?>
      }
    function jsUpdate() { <?php /* Funzione per aggiornare utente via JS */ ?>
          const id = document.getElementById('js-id').value; // Prende ID
          const name = document.getElementById('js-name').value; // Prende nome
          const email = document.getElementById('js-email').value; // Prende email
          fetch(apiUrl + '/' + id, {
              method: 'PUT',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ name, email })
          })
          .then(r => r.text())
          .then(data => document.getElementById('jsResult').innerHTML = `<div class='alert alert-warning'>Aggiornato: ${data}</div>`) <?php /* Mostra risultato */ ?>
          .catch(e => document.getElementById('jsResult').innerHTML = `<div class='alert alert-danger'>Errore: ${e}</div>`); <?php /* Mostra errore */ ?>
      }
    function jsDelete() { <?php /* Funzione per eliminare utente via JS */ ?>
          const id = document.getElementById('js-id').value; // Prende ID
          fetch(apiUrl + '/' + id, {
              method: 'DELETE'
          })
          .then(r => r.text())
          .then(data => document.getElementById('jsResult').innerHTML = `<div class='alert alert-danger'>Eliminato: ${data}</div>`) <?php /* Mostra risultato */ ?>
          .catch(e => document.getElementById('jsResult').innerHTML = `<div class='alert alert-danger'>Errore: ${e}</div>`); <?php /* Mostra errore */ ?>
      }
      </script>
    </body> <?php /* Fine corpo pagina */ ?>
</html> <?php /* Fine documento HTML */ ?>
