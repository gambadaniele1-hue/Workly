<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo 'Unauthorized';
  exit;
}

// Basic input retrieval and sanitation
$month = filter_input(INPUT_POST, 'mese', FILTER_SANITIZE_STRING) ?: date('Y-m');
$ore = (int)($_POST['ore_lavorate'] ?? 0);
$paga = (float)($_POST['paga_oraria'] ?? 0.0);

$ferie = (int)($_POST['ore_ferie'] ?? 0);
$malattia = (int)($_POST['ore_malattia'] ?? 0);
$stra = (int)($_POST['ore_straordinari'] ?? 0);
$trasf = (int)($_POST['ore_trasferta'] ?? 0);
$festivi = (int)($_POST['ore_festivi'] ?? 0);
$prefestivi = (int)($_POST['ore_prefestivi'] ?? 0);
$notturne = (int)($_POST['ore_notturne'] ?? 0);
$reperibilita = (int)($_POST['ore_reperibilita'] ?? 0);

// Include DB connection
require_once __DIR__ . '/../database.php';

// Load contract settings for current user
$userId = (int)($_SESSION['user_id'] ?? 0);
$settings = [
  'Maggiorazione_festiva' => 0.0,
  'Maggiorazione_prefestiva' => 0.0,
  'Maggiorazione_notturna' => 0.0,
  'Maggiorazione_straordinaria' => 0.0,
  'Indennita_reperibilita' => 0.0,
  'Indennita_trasferta' => 0.0,
];
try {
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
$lordo_base = $ore_normali * $paga;

// use percentages from settings (stored as percent, e.g. 100 => 100%)
$mf = $settings['Maggiorazione_festiva'] / 100.0;
$mp = $settings['Maggiorazione_prefestiva'] / 100.0;
$mn = $settings['Maggiorazione_notturna'] / 100.0;
$ms = $settings['Maggiorazione_straordinaria'] / 100.0;
$ind_reper = $settings['Indennita_reperibilita'];
$ind_trasf = $settings['Indennita_trasferta'];

$requestSignature = hash('sha256', json_encode([
  $userId,
  $month,
  $ore,
  $paga,
  $ferie,
  $malattia,
  $stra,
  $trasf,
  $festivi,
  $prefestivi,
  $notturne,
  $reperibilita,
]));

$alreadyGenerated = false;
if (
  isset($_SESSION['last_busta_signature'], $_SESSION['last_busta_at']) &&
  $_SESSION['last_busta_signature'] === $requestSignature &&
  (time() - (int)$_SESSION['last_busta_at']) < 10
) {
  $alreadyGenerated = true;
}

$lordo_stra = $stra * $paga * (1 + $ms);
$lordo_trasf = $trasf * ($paga + $ind_trasf);
$lordo_festivi = $festivi * $paga * (1 + $mf);
$lordo_prefestivi = $prefestivi * $paga * (1 + $mp);
$lordo_notturne = $notturne * $paga * (1 + $mn);
$lordo_reperibilita = $reperibilita * ($paga + $ind_reper);

$lordo = $lordo_base + $lordo_stra + $lordo_trasf + $lordo_festivi + $lordo_prefestivi + $lordo_notturne + $lordo_reperibilita;

// Netto: tolgo il 30%
$netto = max(0.0, $lordo * 0.70);
$trattenute = $lordo - $netto;

// Persist in DB
try {
  if (!$alreadyGenerated) {
    $ins = $pdo->prepare('INSERT INTO Busta_paga (Mese_riferimento, Stipendio_lordo, Stipendio_netto, Ore_lavorate, Paga_oraria, Ore_ferie, Ore_malattia, Ore_straordinari, Ore_festivi, Ore_prefestivi, Ore_notturne, Ore_reperibilita, Ore_trasferta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if ($ins) {
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
      $_SESSION['last_busta_signature'] = $requestSignature;
      $_SESSION['last_busta_at'] = time();
    } else {
      $bustaId = 0;
    }
  } else {
    $bustaId = 0;
  }
} catch (Exception $e) {
  $bustaId = 0;
}

// Format
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
