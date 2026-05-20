<?php
/**
 * File: api/generate_busta.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

// auth.php verifica il JWT e popola $currentUser (reindirizza al login se non valido)
require_once __DIR__ . '/../auth.php';

// Basic input retrieval and sanitation
$month = filter_input(INPUT_POST, 'mese', FILTER_SANITIZE_STRING) ?: date('Y-m');
$ore = (int)($_POST['ore_lavorate'] ?? 0);
$paga = (float)($_POST['paga_oraria'] ?? 0.0);
$ore = (int)($_POST['ore_lavorate'] ?? 0);
$paga = (float)($_POST['paga_oraria'] ?? 0.0);

$ferie = (int)($_POST['ore_ferie'] ?? 0);

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
$malattia = (int)($_POST['ore_malattia'] ?? 0);
$stra = (int)($_POST['ore_straordinari'] ?? 0);
$trasf = (int)($_POST['ore_trasferta'] ?? 0);
$festivi = (int)($_POST['ore_festivi'] ?? 0);
$prefestivi = (int)($_POST['ore_prefestivi'] ?? 0);
$notturne = (int)($_POST['ore_notturne'] ?? 0);
$reperibilita = (int)($_POST['ore_reperibilita'] ?? 0);

// $pdo è già disponibile tramite auth.php → database.php
$userId = $currentUser['user_id'];
$settings = [
  'Maggiorazione_festiva' => 0.0,
  'Maggiorazione_prefestiva' => 0.0,
  'Maggiorazione_notturna' => 0.0,
  'Maggiorazione_straordinaria' => 0.0,
  'Indennita_reperibilita' => 0.0,
  'Indennita_trasferta' => 0.0,

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
];
try {

/* ===== TODO: CASO D'USO NON IMPLEMENTATO =====
 * Use Case: "Confronto buste paga"
 * Description: Comparazione tra buste paga multiple per analisi retributive
 * Implementation Note: Implementare logica di confronto tra buste selezionate
 * Required: Tabella Confronta (già esistente), endpoint GET per recuperare confronti, API per comparazione
 * Expected Flow:
 *   1. Selezionare 2-3 buste paga da visualizzare in parallelo
 *   2. Calcolare differenze in lordo/netto/scatti/trattenute
 *   3. Visualizzare grafici/tabelle comparative
 *   4. Esportare report confronto in PDF/Excel
 * Database: Tabella Confronta (ID_utente, ID_busta) già creata
 * Status: PENDING - Struttura DB pronta, logica comparazione da sviluppare
 */

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
  // ignore and use defaults
}

// Simple payroll calculation (placeholder logic)
$ore_normali = $ore + $ferie + $malattia;

// ===== SEZIONE 4: LOGICA DI PROCESSO =====
$lordo_base = $ore_normali * $paga;

// use percentages from settings (stored as percent, e.g. 100 => 100%)
$mf = $settings['Maggiorazione_festiva'] / 100.0;
$mp = $settings['Maggiorazione_prefestiva'] / 100.0;
$mn = $settings['Maggiorazione_notturna'] / 100.0;
$ms = $settings['Maggiorazione_straordinaria'] / 100.0;
$ind_reper = $settings['Indennita_reperibilita'];
$ind_trasf = $settings['Indennita_trasferta'];

// Sempre consentiamo la generazione: ogni chiamata crea una nuova busta

$lordo_stra = $stra * $paga * (1 + $ms);
$lordo_trasf = $trasf * ($paga + $ind_trasf);
$lordo_festivi = $festivi * $paga * (1 + $mf);
$lordo_prefestivi = $prefestivi * $paga * (1 + $mp);
$lordo_notturne = $notturne * $paga * (1 + $mn);
$lordo_reperibilita = $reperibilita * ($paga + $ind_reper);

$lordo = $lordo_base + $lordo_stra + $lordo_trasf + $lordo_festivi + $lordo_prefestivi + $lordo_notturne + $lordo_reperibilita;

// ===== SEZIONE 5: LOGICA DI PROCESSO =====

// Calcolo trattenute secondo legge italiana
$inps = $lordo * 0.0919;           // 9,19% INPS
$irpef = $lordo * 0.2090;          // 20,9% IRPEF
$add_regionale = $lordo * 0.0160;  // 1,6% addizionale regionale
$add_comunale = $lordo * 0.0070;   // 0,70% addizionale comunale

$total_trattenute_percent = 0.0919 + 0.2090 + 0.0160 + 0.0070; // 32,39%
$netto = max(0.0, $lordo - ($lordo * $total_trattenute_percent));
$trattenute = $lordo - $netto;

// Calcolo ore di ferie maturate (8,3% del totale ore mensili)
$ore_totali_mese = $ore + $ferie + $malattia + $stra + $trasf + $festivi + $prefestivi + $notturne + $reperibilita;
$ferie_maturate = round($ore_totali_mese * 0.083, 2);

// Helper function for error messages

/**
 * Function: errorMsg
 * Parameters: $msg
 * Return: mixed
 * Description: Executes business logic for errorMsg.
 */
function errorMsg($msg) {
  ?>
  <div class="panel" style="background:#fee2e2;border-left:4px solid #dc2626;padding:16px">
    <div style="color:#991b1b;font-weight:700">❌ Errore</div>

    <div style="color:#7f1d1d;margin-top:8px"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
  </div>
  <?php
}

// Persist in DB: inseriamo sempre una nuova busta (ogni generazione resta nello storico)
try {
  // Try with ID_utente first (new schema)
  try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
    $ins = $pdo->prepare('INSERT INTO Busta_paga (ID_utente, Mese_riferimento, Stipendio_lordo, Stipendio_netto, Ore_lavorate, Paga_oraria, Ore_ferie, Ore_malattia, Ore_straordinari, Ore_festivi, Ore_prefestivi, Ore_notturne, Ore_reperibilita, Ore_trasferta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([
      $userId,
      $month,
      round($lordo, 2),
      round($netto, 2),
      $ore,
      $paga,
      $ferie,
      $malattia,
      $stra,

// ===== SEZIONE 7: LOGICA DI PROCESSO =====
      $festivi,
      $prefestivi,
      $notturne,
      $reperibilita,
      $trasf,
    ]);
    $bustaId = (int)$pdo->lastInsertId();

    // registra la busta nello storico (Confronta)
    try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
      $insArch = $pdo->prepare('INSERT INTO Confronta (ID_utente, ID_busta) VALUES (?, ?)');
      if ($insArch) {
        $insArch->execute([$userId, $bustaId]);
      }
    } catch (Exception $ex) {
      // Non blocchiamo il flusso se la tabella non esiste o l'insert fallisce
    }
  } catch (PDOException $e) {
    // If ID_utente column doesn't exist yet, try without it (old schema)
    if (strpos($e->getMessage(), 'Unknown column') !== false) {

// ===== SEZIONE 8: LOGICA DI PROCESSO =====

/* BLOCK COMMENT: SQL Query execution to interact with database records */
      $ins = $pdo->prepare('INSERT INTO Busta_paga (Mese_riferimento, Stipendio_lordo, Stipendio_netto, Ore_lavorate, Paga_oraria, Ore_ferie, Ore_malattia, Ore_straordinari, Ore_festivi, Ore_prefestivi, Ore_notturne, Ore_reperibilita, Ore_trasferta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $ins->execute([
        $month,
        round($lordo, 2),
        round($netto, 2),
        $ore,
        $paga,
        $ferie,
        $malattia,
        $stra,
        $festivi,
        $prefestivi,
        $notturne,
        $reperibilita,
        $trasf,
      ]);
      $bustaId = (int)$pdo->lastInsertId();

      try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
        $insArch = $pdo->prepare('INSERT INTO Confronta (ID_utente, ID_busta) VALUES (?, ?)');

// ===== SEZIONE 9: LOGICA DI PROCESSO =====
        if ($insArch) {
          $insArch->execute([$userId, $bustaId]);
        }
      } catch (Exception $ex) {
        // Non bloccare il flusso in fallback schema.
      }
    } else {
      // If it's a duplicate entry (unique index in DB not removed), insert without ID_utente
      if (strpos($e->getMessage(), '1062') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
          $ins = $pdo->prepare('INSERT INTO Busta_paga (Mese_riferimento, Stipendio_lordo, Stipendio_netto, Ore_lavorate, Paga_oraria, Ore_ferie, Ore_malattia, Ore_straordinari, Ore_festivi, Ore_prefestivi, Ore_notturne, Ore_reperibilita, Ore_trasferta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
          $ins->execute([
            $month,
            round($lordo, 2),
            round($netto, 2),
            $ore,
            $paga,
            $ferie,
            $malattia,
            $stra,

// ===== SEZIONE 10: LOGICA DI PROCESSO =====
            $festivi,
            $prefestivi,
            $notturne,
            $reperibilita,
            $trasf,
          ]);
          $bustaId = (int)$pdo->lastInsertId();
          try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
            $insArch = $pdo->prepare('INSERT INTO Confronta (ID_utente, ID_busta) VALUES (?, ?)');
            if ($insArch) {
              $insArch->execute([$userId, $bustaId]);
            }
          } catch (Exception $ex) {
            // Non bloccare il flusso
          }
        } catch (Exception $ex) {
          $bustaId = 0;
        }
      } else {
        // errore imprevisto

// ===== SEZIONE 11: LOGICA DI PROCESSO =====
        $bustaId = 0;
      }
    }
  }
} catch (Exception $e) {
  $bustaId = 0;
}

// Format

/**
 * Function: fm
 * Parameters: $n){return number_format((float)$n, 2, ',', '.'
 * Return: mixed
 * Description: Executes business logic for fm.
 */
function fm($n){return number_format((float)$n, 2, ',', '.');}

// Return HTML fragment
?>
<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
    <div>
      <h2 style="margin:0">Risultato simulazione — <?= htmlspecialchars($month, ENT_QUOTES, 'UTF-8') ?></h2>
      <p style="margin:6px 0;color:#64748b">Valori calcolati dalla simulazione rapida</p>
    </div>
    <div style="text-align:right">

// ===== SEZIONE 12: LOGICA DI PROCESSO =====
      <small style="color:#94a3b8">Lordo</small>
      <div style="font-weight:800;font-size:20px">€ <?= fm($lordo) ?></div>
      <?php if (!empty($bustaId)): ?>
        <div style="font-size:12px;color:#0f766e">Salvato (ID <?= $bustaId ?>)</div>
      <?php endif; ?>
    </div>
  </div>

  <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div style="background:#f8fafc;padding:12px;border-radius:8px">
      <strong>Stipendio lordo</strong>
      <div>Ore normali + ferie + malattia: <?= (int)$ore_normali ?> h</div>
      <div>Base: € <?= fm($lordo_base) ?></div>
      <div>Straordinari: € <?= fm($lordo_stra) ?></div>
      <div>Trasferte: € <?= fm($lordo_trasf) ?></div>
      <div>Festivi: € <?= fm($lordo_festivi) ?></div>
      <div>Prefestivi: € <?= fm($lordo_prefestivi) ?></div>
      <div>Notturne: € <?= fm($lordo_notturne) ?></div>
      <div>Reperibilità: € <?= fm($lordo_reperibilita) ?></div>
    </div>

// ===== SEZIONE 13: LOGICA DI PROCESSO =====
    <div style="background:#fff7ed;padding:12px;border-radius:8px">
      <strong>Netto pagato</strong>
      <div style="font-size:18px;margin-top:6px">€ <?= fm($netto) ?></div>
      <div style="color:#64748b;margin-top:6px">Trattenute: € <?= fm($trattenute) ?> (approx)</div>
    </div>
  </div>

  <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
    <button onclick="document.getElementById('result-panel').innerHTML=''" class="btn" style="background:#e6eefc;border-radius:8px;padding:8px 12px">Chiudi</button>
  </div>
</div>