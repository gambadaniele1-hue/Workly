<?php
/**
 * File: Impostazioni_contratto.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

require_once __DIR__ . '/database.php';
session_start();

if (empty($_SESSION['user_id'])) {
  header('Location: /SITO/BPIC/login.php');
  exit;
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

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
  switch ($value) {
    case 'statale':
      return 'Statale';
    case 'commerciante':
      return 'Commerciale';
    case 'metalmeccanico':
      return 'Mettalmeccanico';
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

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
      return 'metalmeccanico';
    default:
      return '';
  }
}


/**
 * Function: normalize_decimal
 * Parameters: string $value
 * Return: mixed
 * Description: Executes business logic for normalize_decimal.
 */
function normalize_decimal(string $value): float
{
  $value = str_replace(',', '.', trim($value));
  if ($value === '') {
    return 0.0;
  }
  return (float)$value;
}


/**
 * Function: format_decimal
 * Parameters: $value
 * Return: mixed
 * Description: Executes business logic for format_decimal.
 */
function format_decimal($value): string
{
  return number_format((float)$value, 2, '.', '');
}


// ===== SEZIONE 4: LOGICA DI PROCESSO =====
$userId = (int)$_SESSION['user_id'];
$saveSuccess = false;
$settingsTable = 'Impostazioni_contratto';

try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS Impostazioni_contratto (
    ID_utente INT(100) NOT NULL,
    tipologia_dipendente ENUM('Statale','Mettalmeccanico','Commerciale','') NOT NULL DEFAULT '',
    Livello_dipendente VARCHAR(10) NOT NULL DEFAULT '',
    Maggiorazione_notturna DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    Maggiorazione_straordinaria DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    Maggiorazione_festiva DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    Maggiorazione_prefestiva DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    Indennita_malattia DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    Indennita_reperibilita DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    Indennita_trasferta DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    Tredicesima ENUM('SI','NO') NOT NULL DEFAULT 'NO',
    Quattordicesima ENUM('SI','NO') NOT NULL DEFAULT 'NO',
    PRIMARY KEY (ID_utente)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ===== SEZIONE 5: LOGICA DI PROCESSO =====
} catch (PDOException $e) {
  error_log('Impostazioni_contratto create table error: ' . $e->getMessage());
}

$savedRow = [];

/* BLOCK COMMENT: SQL Query execution to interact with database records */
$stmt = $pdo->prepare("SELECT * FROM {$settingsTable} WHERE ID_utente = ? LIMIT 1");
$stmt->execute([$userId]);
$savedRow = (array)($stmt->fetch() ?: []);

if (empty($savedRow) && $settingsTable !== 'Profilo_contratto') {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
  $fallbackStmt = $pdo->prepare('SELECT * FROM Profilo_contratto WHERE ID_utente = ? LIMIT 1');
  $fallbackStmt->execute([$userId]);
  $savedRow = (array)($fallbackStmt->fetch() ?: []);
}

$contratto = normalize_contratto((string)($_GET['contratto'] ?? ($_POST['contratto'] ?? '')));
if ($contratto === '' && !empty($savedRow['tipologia_dipendente'])) {
  $contratto = db_to_contratto((string)$savedRow['tipologia_dipendente']);
}


// ===== SEZIONE 6: LOGICA DI PROCESSO =====
$livelloValue = (string)($savedRow['Livello_dipendente'] ?? '');
$maggNotturna = (float)($savedRow['Maggiorazione_notturna'] ?? 0);
$maggStraordinari = (float)($savedRow['Maggiorazione_straordinaria'] ?? 0);
$maggFestivi = (float)($savedRow['Maggiorazione_festiva'] ?? 0);
$maggPrefestivi = (float)($savedRow['Maggiorazione_prefestiva'] ?? 0);
$indMalattia = (float)($savedRow['Indennita_malattia'] ?? 0);
$indReperibilita = (float)($savedRow['Indennita_reperibilita'] ?? 0);
$indTrasferta = (float)($savedRow['Indennita_trasferta'] ?? 0);
$tredicesimaValue = (string)($savedRow['Tredicesima'] ?? 'NO');
$quattordicesimaValue = (string)($savedRow['Quattordicesima'] ?? 'NO');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $livelloValue = trim((string)($_POST['livello'] ?? ''));
  $maggNotturna = normalize_decimal((string)($_POST['maggiorazione_notturna'] ?? ''));
  $maggStraordinari = normalize_decimal((string)($_POST['maggiorazione_straordinari'] ?? ''));
  $maggFestivi = normalize_decimal((string)($_POST['maggiorazione_festivi'] ?? ''));
  $maggPrefestivi = normalize_decimal((string)($_POST['maggiorazione_prefestivi'] ?? ''));
  $indMalattia = normalize_decimal((string)($_POST['indennita_malattia'] ?? ''));
  $indReperibilita = normalize_decimal((string)($_POST['indennita_reperibilita'] ?? ''));
  $indTrasferta = normalize_decimal((string)($_POST['indennita_trasferta'] ?? ''));

// ===== SEZIONE 7: LOGICA DI PROCESSO =====
  $tredicesimaValue = isset($_POST['tredicesima']) ? 'SI' : 'NO';
  $quattordicesimaValue = isset($_POST['quattordicesima']) ? 'SI' : 'NO';

  $dbTipologia = contratto_to_db($contratto);
  if ($dbTipologia === '' && !empty($savedRow['tipologia_dipendente'])) {
    $dbTipologia = (string)$savedRow['tipologia_dipendente'];
  }


/* BLOCK COMMENT: SQL Query execution to interact with database records */
  $stmt = $pdo->prepare("INSERT INTO {$settingsTable} (ID_utente, tipologia_dipendente, Livello_dipendente, Maggiorazione_notturna, Maggiorazione_straordinaria, Maggiorazione_festiva, Maggiorazione_prefestiva, Indennita_malattia, Indennita_reperibilita, Indennita_trasferta, Tredicesima, Quattordicesima) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE tipologia_dipendente = VALUES(tipologia_dipendente), Livello_dipendente = VALUES(Livello_dipendente), Maggiorazione_notturna = VALUES(Maggiorazione_notturna), Maggiorazione_straordinaria = VALUES(Maggiorazione_straordinaria), Maggiorazione_festiva = VALUES(Maggiorazione_festiva), Maggiorazione_prefestiva = VALUES(Maggiorazione_prefestiva), Indennita_malattia = VALUES(Indennita_malattia), Indennita_reperibilita = VALUES(Indennita_reperibilita), Indennita_trasferta = VALUES(Indennita_trasferta), Tredicesima = VALUES(Tredicesima), Quattordicesima = VALUES(Quattordicesima)");
  if ($stmt->execute([
    $userId,
    $dbTipologia,
    $livelloValue,
    $maggNotturna,
    $maggStraordinari,
    $maggFestivi,
    $maggPrefestivi,
    $indMalattia,
    $indReperibilita,
    $indTrasferta,

// ===== SEZIONE 8: LOGICA DI PROCESSO =====
    $tredicesimaValue,
    $quattordicesimaValue,
  ])) {
    $saveSuccess = true;
  }
}

$livelli = [];
$subtitle = '';

if ($contratto === 'metalmeccanico') {
  $livelli = ['D1', 'D2', 'C1', 'C2', 'C3', 'B1', 'B2', 'B3', 'A1'];
  $subtitle = 'Livelli contratto metalmeccanico.';
} elseif ($contratto === 'commerciante') {
  $livelli = ['7', '6', '5', '4', '3', '2', '1'];
  $subtitle = 'Livelli contratto commercio.';
} else {
  $subtitle = 'Livelli non disponibili per questa tipologia.';
}
?>

<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Impostazioni Contratto</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --primary: #667eea;
      --primary-dark: #764ba2;
      --text: #1f2a44;
      --muted: #6b7280;
      --border: #e5e7eb;
      --panel: #ffffff;
      --shadow: 0 18px 36px rgba(44, 62, 80, 0.12);
      --glow: 0 12px 22px rgba(102, 126, 234, 0.25);
    }
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
      background: linear-gradient(180deg, #f5f7ff 0%, #eef2ff 45%, #f8fafc 100%);
      color: var(--text);
    }
    .page {
      max-width: 960px;
      margin: 40px auto 80px;
      padding: 0 24px;
    }
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
      gap: 12px;
    }
    .brand {
      display: flex;

      align-items: center;
      gap: 12px;
      font-weight: 700;
      font-size: 18px;
      color: #1f2a44;
    }
    .brand-badge {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      display: grid;
      place-items: center;
      color: #fff;
      box-shadow: var(--glow);
      font-size: 20px;
    }
    .ghost-link {
      text-decoration: none;
      color: var(--primary);

      font-weight: 600;
      background: rgba(102, 126, 234, 0.12);
      padding: 8px 14px;
      border-radius: 999px;
    }
    .topbar-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }
    .mockup-link {
      text-decoration: none;
      color: #0b8f77;
      font-weight: 700;
      background: #d6f4ec;
      border: 1px solid #9dd7cb;
      padding: 8px 14px;
      border-radius: 999px;

    }
    .hero {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.25));
      border: 1px solid rgba(102, 126, 234, 0.25);
      border-radius: 24px;
      padding: 24px 26px;
      box-shadow: var(--shadow);
      margin-bottom: 22px;
    }
    .title {
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .subtitle {
      color: var(--muted);
      margin-bottom: 4px;
    }
    .subtitle strong {
      color: #1f2a44;

    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px;
    }
    .card {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 18px 20px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }
    .card::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;

      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--primary-dark));
      opacity: 0.6;
    }
    .card h3 {
      margin: 0 0 10px;
      font-size: 16px;
      color: #1e1b4b;
    }
    .field {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 14px;
    }
    .field:last-child {
      margin-bottom: 0;
    }
    label {

      font-size: 14px;
      color: var(--text);
    }
    input[type="number"],
    select {
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px 12px;
      font-size: 14px;
      background: #fff;
      color: var(--text);
    }
    input[type="number"]:focus,
    select:focus {
      outline: 2px solid rgba(102, 126, 234, 0.35);
      outline-offset: 2px;
    }
    .unit {
      font-size: 12px;
      color: var(--muted);

    }
    .helper {
      font-size: 13px;
      color: var(--muted);
    }
    .toggle-row {
      display: flex;
      flex-wrap: wrap;
      gap: 18px;
    }
    .toggle {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: var(--text);
    }
    .toggle input {
      appearance: none;
      width: 44px;

      height: 26px;
      border-radius: 999px;
      background: #e2e8f0;
      border: 1px solid #cbd5f5;
      position: relative;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .toggle input::after {
      content: "";
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #fff;
      position: absolute;
      top: 2px;
      left: 3px;
      box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
      transition: all 0.2s ease;
    }

    .toggle input:checked {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      border-color: rgba(102, 126, 234, 0.8);
    }
    .toggle input:checked::after {
      transform: translateX(18px);
    }
    .toggle input:focus-visible {
      outline: 2px solid rgba(102, 126, 234, 0.35);
      outline-offset: 3px;
    }
    .notice {
      padding: 12px 16px;
      border-radius: 14px;
      margin: 18px 0 10px;
      font-size: 14px;
    }
    .notice.success {
      background: #ecfdf3;
      border: 1px solid #bbf7d0;

      color: #166534;
    }
    .footer {
      margin-top: 24px;
      display: flex;
      justify-content: flex-end;
    }
    .save-btn {
      background: linear-gradient(90deg, var(--primary), var(--primary-dark));
      color: #fff;
      border: none;
      border-radius: 12px;
      padding: 12px 26px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: var(--glow);
      transition: transform 0.2s ease;
    }
    .save-btn:active {

      transform: translateY(1px);
    }
    @media (max-width: 700px) {
      .topbar {
        flex-direction: column;
        align-items: flex-start;
      }
      .topbar-actions {
        width: 100%;
        justify-content: flex-start;
      }
    }
  </style>
</head>
<body>
  <main class="page">
    <div class="topbar">
      <div class="brand">
        <span class="brand-badge">BP</span>
        Impostazioni contratto

      </div>
      <div class="topbar-actions">
        <a class="mockup-link" href="/SITO/BPIC/mockup_viste.php">Apri mockup</a>
        <a class="ghost-link" href="/SITO/BPIC/Profilo_contratto.php">Torna al profilo</a>
      </div>
    </div>

    <section class="hero">
      <div class="title">Personalizza il tuo contratto</div>
      <div class="subtitle"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></div>
    </section>

    <?php if ($saveSuccess): ?>
      <div class="notice success">Impostazioni salvate correttamente.</div>
    <?php endif; ?>

    <form method="post" action="/SITO/BPIC/Impostazioni_contratto.php">
      <input type="hidden" name="contratto" value="<?php echo htmlspecialchars($contratto, ENT_QUOTES, 'UTF-8'); ?>">

      <section class="card">

        <h3>Livello del dipendente</h3>
        <div class="field">
          <label for="livello">Seleziona livello</label>

/* BLOCK COMMENT: SQL Query execution to interact with database records */
          <select id="livello" name="livello" <?php echo empty($livelli) ? 'disabled' : ''; ?>>
            <option value="">Seleziona...</option>
            <?php foreach ($livelli as $livello): ?>
              <option value="<?php echo htmlspecialchars($livello, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $livelloValue === $livello ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($livello, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($livelli)): ?>
            <div class="helper">Per questa tipologia il livello non e richiesto.</div>
          <?php endif; ?>
        </div>
      </section>

      <div class="grid" style="margin-top: 16px;">
        <section class="card">
          <h3>Maggiorazioni (%)</h3>

// ===== SEZIONE 24: LOGICA DI PROCESSO =====
          <div class="field">
            <label for="notturna">Maggiorazione notturna</label>
            <input id="notturna" name="maggiorazione_notturna" type="number" min="0" step="1" placeholder="0.00" value="<?php echo htmlspecialchars(format_decimal($maggNotturna), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="unit">Percentuale</div>
          </div>
          <div class="field">
            <label for="straordinari">Maggiorazione straordinari</label>
            <input id="straordinari" name="maggiorazione_straordinari" type="number" min="0" step="1" placeholder="0.00" value="<?php echo htmlspecialchars(format_decimal($maggStraordinari), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="unit">Percentuale</div>
          </div>
          <div class="field">
            <label for="festivi">Maggiorazione festivi</label>
            <input id="festivi" name="maggiorazione_festivi" type="number" min="0" step="1" placeholder="0.00" value="<?php echo htmlspecialchars(format_decimal($maggFestivi), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="unit">Percentuale</div>
          </div>
          <div class="field">
            <label for="prefestivi">Maggiorazione prefestivi</label>
            <input id="prefestivi" name="maggiorazione_prefestivi" type="number" min="0" step="1" placeholder="0.00" value="<?php echo htmlspecialchars(format_decimal($maggPrefestivi), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="unit">Percentuale</div>
          </div>

// ===== SEZIONE 25: LOGICA DI PROCESSO =====
          <div class="field">
            <label for="malattia">Indennita di malattia</label>
            <input id="malattia" name="indennita_malattia" type="number" min="0" step="1" placeholder="0.00" value="<?php echo htmlspecialchars(format_decimal($indMalattia), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="unit">Percentuale</div>
          </div>
        </section>

        <section class="card">
          <h3>Indennita in euro/ora</h3>
          <div class="field">
            <label for="reperibilita">Indennita di reperibilita</label>
            <input id="reperibilita" name="indennita_reperibilita" type="number" min="0" step="1" placeholder="0.00" value="<?php echo htmlspecialchars(format_decimal($indReperibilita), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="unit">Euro/ora</div>
          </div>
          <div class="field">
            <label for="trasferta">Indennita di trasferta</label>
            <input id="trasferta" name="indennita_trasferta" type="number" min="0" step="1" placeholder="0.00" value="<?php echo htmlspecialchars(format_decimal($indTrasferta), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="unit">Euro/ora</div>
          </div>
        </section>


        <section class="card">
          <h3>Mensilita aggiuntive</h3>
          <div class="toggle-row">
            <label class="toggle">
              <input type="checkbox" name="tredicesima" value="1" <?php echo $tredicesimaValue === 'SI' ? 'checked' : ''; ?>>
              Tredicesima
            </label>
            <label class="toggle">
              <input type="checkbox" name="quattordicesima" value="1" <?php echo $quattordicesimaValue === 'SI' ? 'checked' : ''; ?>>
              Quattordicesima
            </label>
          </div>
        </section>
      </div>

      <div class="footer">
        <button class="save-btn" type="submit">Salva</button>
      </div>
    </form>

  </main>
  <script src="/SITO/BPIC/auth/auto_logout_on_close.js"></script>
</body>
</html>