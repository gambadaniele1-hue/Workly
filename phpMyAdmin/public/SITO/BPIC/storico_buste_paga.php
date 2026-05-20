<?php
/**
 * File: storico_buste_paga.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

// auth.php include già database.php, quindi $pdo è disponibile
require_once __DIR__ . '/api/auth.php';

$userId = $currentUser['user_id'];
// Email non è nel JWT: la recuperiamo dal DB
$_stmt = $pdo->prepare('SELECT Email FROM Utenti WHERE ID_utente = ? LIMIT 1');
$_stmt->execute([$userId]);
$email = (string)($_stmt->fetchColumn() ?: '');
unset($_stmt);
$message = '';
$error = '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Carica impostazioni contratto per calcolare le voci (maggiorazioni e indennità)
$settings = [
  'Maggiorazione_festiva' => 0.0,

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
  'Maggiorazione_prefestiva' => 0.0,
  'Maggiorazione_notturna' => 0.0,
  'Maggiorazione_straordinaria' => 0.0,
  'Indennita_reperibilita' => 0.0,
  'Indennita_trasferta' => 0.0,
];
try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
  $stmt = $pdo->prepare('SELECT Maggiorazione_festiva, Maggiorazione_prefestiva, Maggiorazione_notturna, Maggiorazione_straordinaria, Indennita_reperibilita, Indennita_trasferta FROM Impostazioni_contratto WHERE ID_utente = ? LIMIT 1');
  if ($stmt) {
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      foreach ($settings as $k => $_) {
        if (array_key_exists($k, $row)) {
          $settings[$k] = (float)$row[$k];
        }
      }
    }
  }
} catch (Exception $e) {

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
  // fallback usa valori di default
}

if ($requestMethod === 'POST' && isset($_POST['delete_id_busta'])) {
  $idBusta = (int)($_POST['delete_id_busta'] ?? 0);
  if ($idBusta > 0) {
    try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
      $del = $pdo->prepare('DELETE FROM Confronta WHERE ID_utente = ? AND ID_busta = ?');
      $del->execute([$userId, $idBusta]);
      if ($del->rowCount() > 0) {
        $message = 'Busta rimossa dallo storico.';
      } else {
        $error = 'Impossibile rimuovere la busta selezionata.';
      }
    } catch (Exception $e) {
      $error = 'Errore durante la rimozione dallo storico.';
    }
  }
}


// ===== SEZIONE 4: LOGICA DI PROCESSO =====
$rows = [];

try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
  $q = $pdo->prepare('SELECT c.ID_busta, c.Data_confronto AS data_storico, bp.Mese_riferimento, bp.Stipendio_lordo, bp.Stipendio_netto,
    bp.Ore_lavorate, bp.Paga_oraria, bp.Ore_ferie, bp.Ore_malattia, bp.Ore_straordinari, bp.Ore_festivi, bp.Ore_prefestivi, bp.Ore_notturne, bp.Ore_reperibilita, bp.Ore_trasferta
    FROM Confronta c
    JOIN Busta_paga bp ON bp.ID_busta = c.ID_busta
    WHERE c.ID_utente = ?
    ORDER BY c.Data_confronto DESC, c.ID_busta DESC');
  $q->execute([$userId]);
  $rows = $q->fetchAll(PDO::FETCH_ASSOC);

  // Fallback: se lo storico e vuoto, mostra direttamente le buste dell'utente.
  if (empty($rows)) {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
    $f = $pdo->prepare('SELECT bp.ID_busta, bp.Data_creazione AS data_storico, bp.Mese_riferimento, bp.Stipendio_lordo, bp.Stipendio_netto,
      bp.Ore_lavorate, bp.Paga_oraria, bp.Ore_ferie, bp.Ore_malattia, bp.Ore_straordinari, bp.Ore_festivi, bp.Ore_prefestivi, bp.Ore_notturne, bp.Ore_reperibilita, bp.Ore_trasferta
      FROM Busta_paga bp
      WHERE bp.ID_utente = ?
      ORDER BY bp.ID_busta DESC');
    $f->execute([$userId]);

// ===== SEZIONE 5: LOGICA DI PROCESSO =====
    $rows = $f->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Exception $e) {
  $error = 'Errore nel caricamento dello storico buste paga.';
}


/**
 * Function: eur
 * Parameters: float $value
 * Return: mixed
 * Description: Executes business logic for eur.
 */
function eur(float $value): string {
  return number_format($value, 2, ',', '.') . ' EUR';
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Storico buste paga - BPIC</title>
  <style>
    :root {
      --bg: #f6f8fc;
      --card: #ffffff;

      --line: #e5e7eb;
      --txt: #0f172a;
      --muted: #64748b;
      --brand: #2563eb;
      --sidebar: #0f172a;
      --sidebar-soft: #1e293b;
      --green-bg: #ecfdf5;
      --green-txt: #047857;
      --gray-bg: #f1f5f9;
      --red-bg: #fef2f2;
      --red-txt: #b91c1c;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      background: var(--bg);
      color: var(--txt);
      font-family: "Segoe UI", system-ui, sans-serif;
    }


    .layout {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 280px 1fr;
    }

    .sidebar {
      background: linear-gradient(180deg, var(--sidebar), #111827);
      color: #e2e8f0;
      padding: 20px;
    }

    .brand {
      font-size: 24px;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .user {

      font-size: 13px;
      color: #cbd5e1;
      margin-bottom: 18px;
      word-break: break-word;
    }

    .menu {
      display: grid;
      gap: 8px;
    }

    .menu a {
      text-decoration: none;
      color: #e2e8f0;
      font-weight: 700;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid rgba(148, 163, 184, 0.3);
    }


    .menu a:hover,
    .menu a.active {
      background: var(--sidebar-soft);
      border-color: rgba(148, 163, 184, 0.6);
    }

    .content {
      padding: 24px;
    }

    .head {
      margin-bottom: 16px;
    }

    .head h1 {
      margin: 0;
      font-size: 40px;
      line-height: 1.1;
    }


    .head p {
      margin: 8px 0 0;
      color: var(--muted);
      font-size: 18px;
    }

    .notice {
      border-radius: 10px;
      padding: 10px 12px;
      margin-bottom: 14px;
      font-size: 14px;
      font-weight: 700;
    }

    .notice.ok {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #86efac;
    }


    .notice.err {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
    }

    .stack {
      display: grid;
      gap: 14px;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
      padding: 16px;
    }

    .card-top {

      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }

    .month {
      font-size: 34px;
      font-weight: 800;
      line-height: 1;
    }

    .date {
      color: var(--muted);
      font-size: 14px;
    }

    .metrics {
      display: grid;

      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 12px;
    }

    .box {
      border-radius: 12px;
      padding: 12px;
    }

    .box small {
      display: block;
      color: var(--muted);
      font-size: 12px;
      margin-bottom: 4px;
    }

    .box strong {
      font-size: 30px;
      line-height: 1;

    }

    .box.netto { background: var(--green-bg); }
    .box.netto strong { color: var(--green-txt); }
    .box.lordo { background: var(--gray-bg); }
    .box.tasse { background: var(--red-bg); }
    .box.tasse strong { color: var(--red-txt); }

    .actions {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .btn {
      border-radius: 10px;
      border: 1px solid #93c5fd;
      background: #eff6ff;
      color: #1d4ed8;
      font-weight: 700;

      padding: 8px 12px;
      text-decoration: none;
      cursor: pointer;
    }

    .btn.del {
      border-color: #fecaca;
      background: #fef2f2;
      color: #dc2626;
    }

    .empty {
      border: 1px dashed #cbd5e1;
      background: #ffffff;
      border-radius: 16px;
      padding: 18px;
      color: var(--muted);
      font-weight: 700;
    }


    @media (max-width: 1024px) {
      .layout { grid-template-columns: 1fr; }
      .metrics { grid-template-columns: 1fr; }
      .head h1 { font-size: 30px; }
      .month { font-size: 26px; }
      .box strong { font-size: 26px; }
    }
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">BPIC</div>
      <div class="user">Utente: <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
      <nav class="menu">
        <a href="/SITO/BPIC/home.php">Home</a>
        <a href="/SITO/BPIC/nuovabustapaga.php">Nuova busta paga</a>
        <a class="active" href="/SITO/BPIC/storico_buste_paga.php">Storico buste paga</a>
        <a href="/SITO/BPIC/mockup_viste.php">Mockup viste</a>
        <a href="/SITO/BPIC/Impostazioni_contratto.php">Impostazioni contratto</a>

// ===== SEZIONE 17: LOGICA DI PROCESSO =====
        <a href="/SITO/BPIC/logout.php">Logout</a>
      </nav>
    </aside>

    <main class="content">
      <header class="head">
        <h1>Storico Buste Paga</h1>
        <p>Visualizza e gestisci le buste generate con riepilogo Netto, Lordo e Tasse.</p>
      </header>

      <?php if ($message !== ''): ?>
        <div class="notice ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="notice err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <?php if (empty($rows)): ?>
        <div class="empty">Nessuna busta paga presente nello storico.</div>
      <?php else: ?>

// ===== SEZIONE 18: LOGICA DI PROCESSO =====
        <section class="stack">

/* ===== TODO: CASO D'USO NON IMPLEMENTATO =====
 * Use Case: "Archivio buste paga"
 * Description: Archiviazione e gestione dei cedolini storici con esportazione e ricerca avanzata
 * Implementation Note: Aggiungere funzionalità di archivio con ricerca, filtri avanzati, esportazione
 * Required: Tabella di archivio separata o flag archive, API di ricerca, export PDF/Excel
 * Expected Features:
 *   1. Ricerca avanzata: per periodo, importo, tipo contratto, stato
 *   2. Esportazione batch: selezionare multiple buste per esportare in ZIP/Excel
 *   3. Archiviazione: spostare buste vecchie in archivio separato per performance
 *   4. Statistiche: grafici redditi annuali, medie, andamenti storici
 * Current State: Solo visualizzazione con paginazione e filtri base
 * Database: Potrebbe usare tabella separata Storico_buste_paga_archivio
 * Status: PENDING - Solo visualizzazione implementata
 */
          <?php foreach ($rows as $row): ?>
            <?php
              $lordo = (float)($row['Stipendio_lordo'] ?? 0);
              $netto = (float)($row['Stipendio_netto'] ?? 0);
              $tasse = $lordo - $netto;
              $mese = (string)($row['Mese_riferimento'] ?? '');
              $data = (string)($row['data_storico'] ?? '');

              // calcola i singoli importi a partire dalle ore salvate
              $ore_lavorate = (float)($row['Ore_lavorate'] ?? 0);
              $paga_oraria = (float)($row['Paga_oraria'] ?? 0);
              $ore_ferie = (float)($row['Ore_ferie'] ?? 0);
              $ore_malattia = (float)($row['Ore_malattia'] ?? 0);
              $ore_stra = (float)($row['Ore_straordinari'] ?? 0);
              $ore_trasf = (float)($row['Ore_trasferta'] ?? 0);
              $ore_festivi = (float)($row['Ore_festivi'] ?? 0);
              $ore_prefestivi = (float)($row['Ore_prefestivi'] ?? 0);
              $ore_notturne = (float)($row['Ore_notturne'] ?? 0);
              $ore_reper = (float)($row['Ore_reperibilita'] ?? 0);

// ===== SEZIONE 19: LOGICA DI PROCESSO =====

              $mf = $settings['Maggiorazione_festiva'] / 100.0;
              $mp = $settings['Maggiorazione_prefestiva'] / 100.0;
              $mn = $settings['Maggiorazione_notturna'] / 100.0;
              $ms = $settings['Maggiorazione_straordinaria'] / 100.0;
              $ind_reper = $settings['Indennita_reperibilita'];
              $ind_trasf = $settings['Indennita_trasferta'];

              $lordo_base = ($ore_lavorate + $ore_ferie + $ore_malattia) * $paga_oraria;
              $lordo_stra = $ore_stra * $paga_oraria * (1 + $ms);
              $lordo_trasf = $ore_trasf * ($paga_oraria + $ind_trasf);
              $lordo_festivi = $ore_festivi * $paga_oraria * (1 + $mf);
              $lordo_prefestivi = $ore_prefestivi * $paga_oraria * (1 + $mp);
              $lordo_notturne = $ore_notturne * $paga_oraria * (1 + $mn);
              $lordo_reperibilita = $ore_reper * ($paga_oraria + $ind_reper);
              $calculated_lordo_sum = $lordo_base + $lordo_stra + $lordo_trasf + $lordo_festivi + $lordo_prefestivi + $lordo_notturne + $lordo_reperibilita;
            ?>
            <article class="card">
              <div class="card-top">
                <div>

                  <div class="month"><?= htmlspecialchars($mese, ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="date">Generata il <?= htmlspecialchars($data, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>

              <div class="metrics">
                <div class="box netto">
                  <small>Netto</small>
                  <strong><?= htmlspecialchars(eur($netto), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="box lordo">
                  <small>Voci retributive</small>
                  <div style="font-weight:700;font-size:16px;margin-bottom:6px">Totale € <?= htmlspecialchars(number_format($calculated_lordo_sum, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
                  <div style="font-size:13px;color:var(--muted)">
                    Ore lavorative: <?= htmlspecialchars(eur($lordo_base), ENT_QUOTES, 'UTF-8') ?><br>
                    Reperibilità: <?= htmlspecialchars(eur($lordo_reperibilita), ENT_QUOTES, 'UTF-8') ?><br>
                    Straordinari: <?= htmlspecialchars(eur($lordo_stra), ENT_QUOTES, 'UTF-8') ?><br>
                    Trasferte: <?= htmlspecialchars(eur($lordo_trasf), ENT_QUOTES, 'UTF-8') ?><br>
                    Festivi: <?= htmlspecialchars(eur($lordo_festivi), ENT_QUOTES, 'UTF-8') ?><br>
                    Prefestivi: <?= htmlspecialchars(eur($lordo_prefestivi), ENT_QUOTES, 'UTF-8') ?><br>

                    Notturne: <?= htmlspecialchars(eur($lordo_notturne), ENT_QUOTES, 'UTF-8') ?><br>
                  </div>
                </div>
                <div class="box tasse">
                  <small>Tasse</small>
                  <strong><?= htmlspecialchars(eur($tasse), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
              </div>

              <div class="actions">
                <a class="btn" href="/SITO/BPIC/download_busta_pdf.php?id_busta=<?= (int)($row['ID_busta'] ?? 0) ?>">Scarica PDF</a>
                <form method="post" style="margin:0;">
                  <input type="hidden" name="delete_id_busta" value="<?= (int)($row['ID_busta'] ?? 0) ?>">
                  <button type="submit" class="btn del">Elimina</button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

    </main>
  </div>
  <script src="/SITO/BPIC/auth/auto_logout_on_close.js"></script>
</body>
</html>