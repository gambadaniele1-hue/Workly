<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: /SITO/BPIC/login.php');
  exit;
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuova busta paga - BPIC</title>
  <style>
    :root {
      --bg: #f1f5ff;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --primary: #2563eb;
      --border: #dbeafe;
      --input-bg: #f8fbff;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Segoe UI", system-ui, sans-serif;
      background: radial-gradient(circle at 15% 10%, #dce8ff 0%, var(--bg) 45%, #eef4ff 100%);
      color: var(--text);
      padding: 32px 20px;
    }
    .container {
      max-width: 980px;
      margin: 0 auto;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      box-shadow: 0 12px 32px rgba(37, 99, 235, 0.12);
      padding: 26px;
    }
    h1 { margin: 0; font-size: 30px; }
    .subtitle { color: var(--muted); margin: 8px 0 24px; }
    .grid { display: grid; gap: 16px; }
    .cols-2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .cols-3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
    .section-title {
      font-size: 18px;
      font-weight: 700;
      margin: 8px 0 4px;
    }
    label {
      display: block;
      font-weight: 600;
      color: #334155;
      margin-bottom: 8px;
    }
    .field { display: flex; flex-direction: column; }
    input {
      width: 100%;
      border: 1px solid #c7d2fe;
      background: var(--input-bg);
      border-radius: 12px;
      padding: 12px;
      font-size: 16px;
      color: #0f172a;
    }
    .actions { margin-top: 26px; display: flex; gap: 10px; flex-wrap: wrap; }
    .btn {
      display: inline-block;
      text-decoration: none;
      border: none;
      border-radius: 12px;
      padding: 12px 18px;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-primary {
      color: #fff;
      background: linear-gradient(90deg, #2563eb 0%, #4f7df0 100%);
    }
    .btn-ghost {
      color: #0f172a;
      background: #e2e8f0;
    }
    @media (max-width: 860px) {
      .cols-2, .cols-3 { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <main class="container">
    <h1>Nuova busta paga</h1>
    <p class="subtitle">Inserisci i dati del mese per generare una simulazione in linea con lo stile BPIC.</p>

    <form method="post" action="#" class="grid">
      <div class="field" style="max-width: 320px;">
        <label for="mese">Mese di riferimento</label>
        <input type="month" id="mese" name="mese" required>
      </div>

      <div class="grid cols-2">
        <div class="field">
          <label for="ore_lavorate">Ore lavorate</label>
          <input type="number" id="ore_lavorate" name="ore_lavorate" min="0" value="168" required>
        </div>
        <div class="field">
          <label for="paga_oraria">Paga oraria (€)</label>
          <input type="number" step="0.01" id="paga_oraria" name="paga_oraria" min="0" value="10" required>
        </div>
      </div>

      <div class="section-title">Assenze</div>
      <div class="grid cols-2">
        <div class="field">
          <label for="ore_ferie">Ore ferie</label>
          <input type="number" id="ore_ferie" name="ore_ferie" min="0" value="0">
        </div>
        <div class="field">
          <label for="ore_malattia">Ore malattia</label>
          <input type="number" id="ore_malattia" name="ore_malattia" min="0" value="0">
        </div>
      </div>

      <div class="section-title">Ore extra</div>
      <div class="grid cols-3">
        <div class="field"><label for="ore_straordinari">Ore straordinari</label><input type="number" id="ore_straordinari" name="ore_straordinari" min="0" value="0"></div>
        <div class="field"><label for="ore_festivi">Ore festivi</label><input type="number" id="ore_festivi" name="ore_festivi" min="0" value="0"></div>
        <div class="field"><label for="ore_prefestivi">Ore prefestivi</label><input type="number" id="ore_prefestivi" name="ore_prefestivi" min="0" value="0"></div>
        <div class="field"><label for="ore_notturne">Ore notturne</label><input type="number" id="ore_notturne" name="ore_notturne" min="0" value="0"></div>
        <div class="field"><label for="ore_reperibilita">Ore reperibilita</label><input type="number" id="ore_reperibilita" name="ore_reperibilita" min="0" value="0"></div>
        <div class="field"><label for="ore_trasferta">Ore trasferta</label><input type="number" id="ore_trasferta" name="ore_trasferta" min="0" value="0"></div>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary">Genera busta paga</button>
        <a class="btn btn-ghost" href="/SITO/BPIC/home.php">Torna alla home</a>
      </div>
    </form>
  </main>
</body>
</html>