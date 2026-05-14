<?php
/**
 * File: Profilo_contratto.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

require_once __DIR__ . '/database.php';
session_start();

// INLINE COMMENT: Conditional logic or loop processing
if (empty($_SESSION['user_id'])) {
  header('Location: /SITO/BPIC/login.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];

try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
  $stmt = $pdo->prepare('SELECT ID_utente FROM Impostazioni_contratto WHERE ID_utente = ? AND tipologia_dipendente <> "" LIMIT 1');
  $stmt->execute([$userId]);
// INLINE COMMENT: Conditional logic or loop processing
  if ($stmt->fetch()) {
    header('Location: /SITO/BPIC/home.php');
    exit;
  }

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
} catch (Throwable $e) {
  // Se la tabella non esiste ancora o c'e un errore, continua con il flusso standard.
}


/**
 * Function: normalize_contratto
 * Parameters: string $value
 * Return: mixed
 * Description: Executes business logic for normalize_contratto.
 */
function normalize_contratto(string $value): string
{
  $value = strtolower(trim($value));
  $allowed = ['statale', 'commerciante', 'metalmeccanico'];
  return in_array($value, $allowed, true) ? $value : '';
}


/**
 * Function: contratto_to_db
 * Parameters: string $value
 * Return: mixed
 * Description: Executes business logic for contratto_to_db.
 */
function contratto_to_db(string $value): string
{
  switch ($value) {
    case 'statale':
      return 'Statale';
    case 'commerciante':
      return 'Commerciale';
    case 'metalmeccanico':
      return 'Mettalmeccanico';

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
    default:
      return '';
  }
}


/**
 * Function: db_to_contratto
 * Parameters: string $value
 * Return: mixed
 * Description: Executes business logic for db_to_contratto.
 */
function db_to_contratto(string $value): string
{
  switch ($value) {
    case 'Statale':
      return 'statale';
    case 'Commerciale':
      return 'commerciante';
    case 'Mettalmeccanico':
      return 'metalmeccanico';
    default:
      return '';
  }
}

$selectedContratto = '';

// ===== SEZIONE 4: LOGICA DI PROCESSO =====

try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
  $stmt = $pdo->prepare('SELECT tipologia_dipendente FROM Profilo_contratto WHERE ID_utente = ? LIMIT 1');
  $stmt->execute([$userId]);
  $row = $stmt->fetch();
// INLINE COMMENT: Conditional logic or loop processing
  if ($row) {
    $selectedContratto = db_to_contratto((string)($row['tipologia_dipendente'] ?? ''));
  }
} catch (PDOException $e) {
  error_log('Profilo_contratto load error: ' . $e->getMessage());
}

// INLINE COMMENT: Conditional logic or loop processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contratto = normalize_contratto((string)($_POST['contratto'] ?? ''));
  $dbValue = contratto_to_db($contratto);

// INLINE COMMENT: Conditional logic or loop processing
  if ($dbValue !== '') {
    try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
      $stmt = $pdo->prepare("INSERT INTO Profilo_contratto (ID_utente, tipologia_dipendente, Livello_dipendente, Maggiorazione_notturna, Maggiorazione_straordinaria, Maggiorazione_festiva, Maggiorazione_prefestiva, Indennita_malattia, Indennita_reperibilita, Indennita_trasferta, Tredicesima, Quattordicesima) VALUES (?, ?, '1', 0, 0, 0, 0, 0, 0, 0, 'NO', 'NO') ON DUPLICATE KEY UPDATE tipologia_dipendente = VALUES(tipologia_dipendente)");
      $stmt->execute([$userId, $dbValue]);

// ===== SEZIONE 5: LOGICA DI PROCESSO =====
    } catch (PDOException $e) {
      error_log('Profilo_contratto submit error: ' . $e->getMessage());
    }
  }

  $redirect = '/SITO/BPIC/Impostazioni_contratto.php';
// INLINE COMMENT: Conditional logic or loop processing
  if ($contratto !== '') {
    $redirect .= '?contratto=' . urlencode($contratto);
  }
  header('Location: ' . $redirect);
  exit;
}

// INLINE COMMENT: Conditional logic or loop processing
if ($selectedContratto === '') {
  $selectedContratto = 'statale';
}
?>
<!doctype html>
<html lang="it">
<head>

// ===== SEZIONE 6: LOGICA DI PROCESSO =====
  <meta charset="utf-8">
  <title>Profilo Contratto</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --primary: #1f6feb;
      --primary-dark: #0b5ad9;
      --text: #1f2937;
      --muted: #6b7280;
      --border: #e5e7eb;
      --bg: #f5f7fb;
    }
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);

// ===== SEZIONE 7: LOGICA DI PROCESSO =====
    }
    .page {
      max-width: 860px;
      margin: 40px auto 80px;
      padding: 0 24px;
    }
    .title {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .subtitle {
      color: var(--muted);
      margin-bottom: 24px;
    }
    .card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 18px 20px;

// ===== SEZIONE 8: LOGICA DI PROCESSO =====
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      box-shadow: 0 8px 18px rgba(31, 111, 235, 0.06);
    }
    .card + .card {
      margin-top: 16px;
    }
    .card-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .badge {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      background: rgba(31, 111, 235, 0.12);
      display: grid;

// ===== SEZIONE 9: LOGICA DI PROCESSO =====
      place-items: center;
      font-size: 22px;
      color: var(--primary);
    }
    .card h3 {
      margin: 0;
      font-size: 17px;
    }
    .card p {
      margin: 4px 0 0;
      color: var(--muted);
      font-size: 14px;
    }
    .option {
      position: relative;
    }
    .option input {
      appearance: none;
      width: 50px;
      height: 28px;

// ===== SEZIONE 10: LOGICA DI PROCESSO =====
      border-radius: 999px;
      background: #e5e7eb;
      border: 1px solid #d1d5db;
      transition: all 0.2s ease;
      cursor: pointer;
      position: relative;
    }
    .option input::after {
      content: "";
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #fff;
      position: absolute;
      top: 2px;
      left: 3px;
      transition: all 0.2s ease;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .option input:checked {

// ===== SEZIONE 11: LOGICA DI PROCESSO =====
      background: var(--primary);
      border-color: var(--primary);
    }
    .option input:checked::after {
      transform: translateX(22px);
    }
    .option input:focus-visible {
      outline: 2px solid rgba(31, 111, 235, 0.35);
      outline-offset: 3px;
    }
    .footer {
      margin-top: 28px;
      display: flex;
      justify-content: flex-end;
    }
    .save-btn {
      background: linear-gradient(90deg, var(--primary), var(--primary-dark));
      color: #fff;
      border: none;
      border-radius: 12px;

// ===== SEZIONE 12: LOGICA DI PROCESSO =====
      padding: 12px 26px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 10px 18px rgba(31, 111, 235, 0.2);
    }
    .save-btn:active {
      transform: translateY(1px);
    }
  </style>
</head>
<body>
  <main class="page">
    <div class="title">Profilo contratto</div>
    <div class="subtitle">Seleziona la tipologia di contratto per impostare il profilo corretto.</div>

    <form method="post" action="/SITO/BPIC/Profilo_contratto.php">
      <section class="card" data-option>
        <div class="card-left">
          <div class="badge">🏛️</div>

// ===== SEZIONE 13: LOGICA DI PROCESSO =====
          <div>
            <h3>Dipendente Statale</h3>
            <p>Lavori nel settore pubblico?</p>
          </div>
        </div>
        <label class="option">
          <input type="radio" name="contratto" value="statale" <?php echo $selectedContratto === 'statale' ? 'checked' : ''; ?>>
        </label>
      </section>

      <section class="card" data-option>
        <div class="card-left">
          <div class="badge">🛍️</div>
          <div>
            <h3>Dipendente Commerciante</h3>
            <p>Applichi il contratto del commercio?</p>
          </div>
        </div>
        <label class="option">
          <input type="radio" name="contratto" value="commerciante" <?php echo $selectedContratto === 'commerciante' ? 'checked' : ''; ?>>

// ===== SEZIONE 14: LOGICA DI PROCESSO =====
        </label>
      </section>

      <section class="card" data-option>
        <div class="card-left">
          <div class="badge">⚙️</div>
          <div>
            <h3>Dipendente Metalmeccanico</h3>
            <p>Applichi il contratto metalmeccanico?</p>
          </div>
        </div>
        <label class="option">
          <input type="radio" name="contratto" value="metalmeccanico" <?php echo $selectedContratto === 'metalmeccanico' ? 'checked' : ''; ?>>
        </label>
      </section>

      <div class="footer">
        <button class="save-btn" type="submit">Continua</button>
      </div>
    </form>

// ===== SEZIONE 15: LOGICA DI PROCESSO =====
  </main>

  <script>
    const cards = document.querySelectorAll('[data-option]');
    cards.forEach((card) => {
      card.addEventListener('click', () => {
        const input = card.querySelector('input[type="radio"]');
// INLINE COMMENT: Conditional logic or loop processing
        if (input) {
          input.checked = true;
        }
      });
    });
  </script>
  <script src="/SITO/BPIC/auth/auto_logout_on_close.js"></script>
</body>
</html>