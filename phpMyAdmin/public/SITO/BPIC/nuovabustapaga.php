<?php
/**
 * File: nuovabustapaga.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Email non è nel JWT: la recuperiamo dal DB
$_stmt = $pdo->prepare('SELECT Email FROM Utenti WHERE ID_utente = ? LIMIT 1');
$_stmt->execute([$currentUser['user_id']]);
$email = (string)($_stmt->fetchColumn() ?: '');
unset($_stmt);
$currentMonth = date('Y-m');
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuova busta paga - BPIC</title>
  <link rel="stylesheet" href="/SITO/BPIC/styles/global.css" />
  <style>

    :root{--bg:#f6f9ff;--card:#fff;--muted:#64748b;--primary:#2563eb;--sidebar:#0b1a3a;--sidebar-text:#e6eeff}
    *{box-sizing:border-box}
    body{margin:0;font-family:Segoe UI,system-ui,-apple-system,Roboto,Arial;color:#0f172a;background:var(--bg);}
    .layout{min-height:100vh;display:grid;grid-template-columns:280px 1fr;gap:24px;padding:28px}
    .sidebar{background:linear-gradient(180deg,var(--sidebar),#0f224a);color:var(--sidebar-text);padding:20px;border-radius:12px}
    .brand{font-weight:800;font-size:20px;margin-bottom:8px}
    .user{font-size:13px;color:#cfe0ff;margin-bottom:18px}
    .menu{display:flex;flex-direction:column;gap:8px}
    .menu a{color:var(--sidebar-text);text-decoration:none;padding:10px;border-radius:10px;font-weight:700}
    .menu a.active{background:rgba(255,255,255,0.06)}
    .content{padding:20px}
    .panel{background:var(--card);border-radius:12px;padding:18px;box-shadow:0 6px 18px rgba(12, 36, 80, 0.06)}
    .grid{display:grid;gap:12px}
    .cols-2{grid-template-columns:repeat(2,1fr)}
    .cols-3{grid-template-columns:repeat(3,1fr)}
    label{font-weight:700;color:#334155;font-size:14px;margin-bottom:6px}
    input,select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eefc;background:#fff}
    .actions{display:flex;gap:12px;align-items:center;margin-top:12px}
    .btn{padding:10px 14px;border-radius:10px;border:0;cursor:pointer;font-weight:800}
    .btn-primary{background:linear-gradient(90deg,#2563eb,#3b82f6);color:#fff}

    .hint{color:var(--muted);font-size:13px}
    @media(max-width:900px){.layout{grid-template-columns:1fr}.sidebar{order:2}}
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar" aria-labelledby="nav-title">
      <div class="brand">BPIC</div>
      <div class="user">Utente: <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="menu">
        <a href="/SITO/BPIC/home.php">Home</a>
        <a class="active" href="/SITO/BPIC/nuovabustapaga.php">Crea nuova busta paga</a>
        <a href="/SITO/BPIC/storico_buste_paga.php">Storico buste paga</a>
        <a href="/SITO/BPIC/mockup_viste.php">Mockup viste</a>
        <a href="/SITO/BPIC/user_manual.php">Manuale utente</a>
      </div>
      <hr style="margin:16px 0;border:none;border-top:1px solid rgba(255,255,255,0.04)">
      <div class="menu">
        <a href="/SITO/BPIC/Impostazioni_contratto.php">Impostazioni contratto</a>
        <a href="/SITO/BPIC/dashboard.php">Dashboard</a>

        <a href="/SITO/BPIC/logout.php">Logout</a>
      </div>
    </aside>

    <main class="content">
      <div class="panel">
        <h1 style="margin:0 0 8px">Nuova busta paga</h1>
        <p class="hint">Compila i dati e premi <strong>Genera busta paga</strong>. Il contenuto verrà mostrato qui senza cambiare pagina.</p>
        <form id="busta-form" class="grid" style="margin-top:12px">
          <div class="grid cols-2">
            <div>
              <label for="mese">Mese di riferimento</label>
              <input type="month" id="mese" name="mese" value="<?= htmlspecialchars($currentMonth, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div>
              <label for="paga_oraria">Paga oraria lorda(€)</label>
              <input type="number" step="0.01" id="paga_oraria" name="paga_oraria" min="0" value="10" required>
            </div>
          </div>


          <div class="grid cols-2">
            <div>
              <label for="ore_lavorate">Ore lavorate</label>
              <input type="number" id="ore_lavorate" name="ore_lavorate" min="0" value="168" required>
            </div>
            <div>
              <label for="ore_ferie">Ore ferie</label>
              <input type="number" id="ore_ferie" name="ore_ferie" min="0" value="0">
            </div>
          </div>

          <div class="grid cols-3">
            <div>
              <label for="ore_malattia">Ore malattia</label>
              <input type="number" id="ore_malattia" name="ore_malattia" min="0" value="0">
            </div>
            <div>
              <label for="ore_straordinari">Ore straordinari</label>
              <input type="number" id="ore_straordinari" name="ore_straordinari" min="0" value="0">
            </div>

            <div>
              <label for="ore_trasferta">Ore trasferta</label>
              <input type="number" id="ore_trasferta" name="ore_trasferta" min="0" value="0">
            </div>
          </div>

          <div class="grid cols-3" style="margin-top:6px">
            <div>
              <label for="ore_festivi">Ore festivi</label>
              <input type="number" id="ore_festivi" name="ore_festivi" min="0" value="0">
            </div>
            <div>
              <label for="ore_prefestivi">Ore prefestivi</label>
              <input type="number" id="ore_prefestivi" name="ore_prefestivi" min="0" value="0">
            </div>
            <div>
              <label for="ore_notturne">Ore notturne</label>
              <input type="number" id="ore_notturne" name="ore_notturne" min="0" value="0">
            </div>
          </div>


          <div class="grid cols-1" style="margin-top:6px">
            <div>
              <label for="ore_reperibilita">Ore reperibilità</label>
              <input type="number" id="ore_reperibilita" name="ore_reperibilita" min="0" value="0">
            </div>
          </div>

          <div class="actions">
            <button id="submit-btn" type="submit" class="btn btn-primary">Genera busta paga</button>
            <a class="btn" href="/SITO/BPIC/home.php">Annulla</a>
            <div style="margin-left:auto;color:var(--muted)" id="status-text"></div>
          </div>
        </form>

        <div id="result-panel" style="margin-top:16px"></div>
      </div>
    </main>
  </div>


  <script>
  (function(){
    const form = document.getElementById('busta-form');
    const btn = document.getElementById('submit-btn');
    const status = document.getElementById('status-text');
    const result = document.getElementById('result-panel');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      btn.disabled = true;
      status.textContent = 'Calcolo in corso…';
      result.innerHTML = '';

      const data = new FormData(form);
      try {
        const res = await fetch('/SITO/BPIC/api/generate_busta.php', { method: 'POST', body: data, credentials: 'same-origin' });
        if (!res.ok) throw new Error('Errore server: ' + res.status);
        const html = await res.text();
        result.innerHTML = html;
        status.textContent = 'Aggiornato';

      } catch (err) {
        status.textContent = 'Errore: ' + (err.message || err);
      } finally {
        btn.disabled = false;
        setTimeout(()=> status.textContent = '', 3000);
      }
    });
  })();
  </script>
</body>
</html>
                .panel{background:var(--card);border-radius:12px;padding:18px;box-shadow:0 6px 18px rgba(12, 36, 80, 0.06)}
                .grid{display:grid;gap:12px}
                .cols-2{grid-template-columns:repeat(2,1fr)}
                .cols-3{grid-template-columns:repeat(3,1fr)}
                label{font-weight:700;color:#334155;font-size:14px;margin-bottom:6px}
                input,select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eefc;background:#fff}
                .actions{display:flex;gap:12px;align-items:center;margin-top:12px}
                .btn{padding:10px 14px;border-radius:10px;border:0;cursor:pointer;font-weight:800}
                .btn-primary{background:linear-gradient(90deg,#2563eb,#3b82f6);color:#fff}

                .hint{color:var(--muted);font-size:13px}
                @media(max-width:900px){.layout{grid-template-columns:1fr}.sidebar{order:2}}
              </style>
            </head>
            <body>
              <div class="layout">
                <aside class="sidebar" aria-labelledby="nav-title">
                  <div class="brand">BPIC</div>
                  <div class="user">Utente: <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="menu">
                    <a href="/SITO/BPIC/home.php">Home</a>
                    <a class="active" href="/SITO/BPIC/nuovabustapaga.php">Crea nuova busta paga</a>
                    <a href="/SITO/BPIC/mockup_viste.php">Mockup viste</a>
                    <a href="/SITO/BPIC/user_manual.php">Manuale utente</a>
                  </div>
                  <hr style="margin:16px 0;border:none;border-top:1px solid rgba(255,255,255,0.04)">
                  <div class="menu">
                    <a href="/SITO/BPIC/Impostazioni_contratto.php">Impostazioni contratto</a>
                    <a href="/SITO/BPIC/dashboard.php">Dashboard</a>
                    <a href="/SITO/BPIC/logout.php">Logout</a>

// ===== SEZIONE 11: LOGICA DI PROCESSO =====
                  </div>
                </aside>

                <main class="content">
                  <div class="panel">
                    <h1 style="margin:0 0 8px">Nuova busta paga</h1>
                    <p class="hint">Compila i dati e premi <strong>Genera busta paga</strong>. Il contenuto verrà mostrato qui senza cambiare pagina.</p>
                    <form id="busta-form" class="grid" style="margin-top:12px">
                      <div class="grid cols-2">
                        <div>
                          <label for="mese">Mese di riferimento</label>
                          <input type="month" id="mese" name="mese" value="<?= htmlspecialchars($currentMonth, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div>
                          <label for="paga_oraria">Paga oraria lorda(€)</label>
                          <input type="number" step="0.01" id="paga_oraria" name="paga_oraria" min="0" value="10" required>
                        </div>
                      </div>

                      <div class="grid cols-2">

                        <div>
                          <label for="ore_lavorate">Ore lavorate</label>
                          <input type="number" id="ore_lavorate" name="ore_lavorate" min="0" value="168" required>
                        </div>
                        <div>
                          <label for="ore_ferie">Ore ferie</label>
                          <input type="number" id="ore_ferie" name="ore_ferie" min="0" value="0">
                        </div>
                      </div>

                      <div class="grid cols-3">
                        <div>
                          <label for="ore_malattia">Ore malattia</label>
                          <input type="number" id="ore_malattia" name="ore_malattia" min="0" value="0">
                        </div>
                        <div>
                          <label for="ore_straordinari">Ore straordinari</label>
                          <input type="number" id="ore_straordinari" name="ore_straordinari" min="0" value="0">
                        </div>
                        <div>

                          <label for="ore_trasferta">Ore trasferta</label>
                          <input type="number" id="ore_trasferta" name="ore_trasferta" min="0" value="0">
                        </div>
                      </div>

                      <div class="actions">
                        <button id="submit-btn" type="submit" class="btn btn-primary">Genera busta paga</button>
                        <a class="btn" href="/SITO/BPIC/home.php">Annulla</a>
                        <div style="margin-left:auto;color:var(--muted)" id="status-text"></div>
                      </div>
                    </form>

                    <div id="result-panel" style="margin-top:16px"></div>
                  </div>
                </main>
              </div>

              <script>
              (function(){
                const form = document.getElementById('busta-form');

                const btn = document.getElementById('submit-btn');
                const status = document.getElementById('status-text');
                const result = document.getElementById('result-panel');

                form.addEventListener('submit', async (e) => {
                  e.preventDefault();
                  btn.disabled = true;
                  status.textContent = 'Calcolo in corso…';
                  result.innerHTML = '';

                  const data = new FormData(form);
                  try {
                    const res = await fetch('/SITO/BPIC/api/generate_busta.php', { method: 'POST', body: data, credentials: 'same-origin' });
                    if (!res.ok) throw new Error('Errore server: ' + res.status);
                    const html = await res.text();
                    result.innerHTML = html;
                    status.textContent = 'Aggiornato';
                  } catch (err) {
                    status.textContent = 'Errore: ' + (err.message || err);
                  } finally {

                    btn.disabled = false;
                    setTimeout(()=> status.textContent = '', 3000);
                  }
                });
              })();
              </script>
            </body>
            </html>