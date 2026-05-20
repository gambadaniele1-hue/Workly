<?php
/**
 * File: test_transazione_t14.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

require_once __DIR__ . '/api/auth.php';
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Test T14 - Transazione Busta Paga</title>
  <style>
    :root {
      --bg: #f6f9ff;
      --card: #ffffff;
      --text: #0f172a;

      --muted: #64748b;
      --primary: #2563eb;
      --danger: #dc2626;
      --ok: #0f766e;
      --border: #dbe7ff;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Segoe UI", system-ui, sans-serif;
      background: radial-gradient(circle at 10% 10%, #eef4ff 0, var(--bg) 45%, #eef2ff 100%);
      color: var(--text);
    }
    .wrap {
      max-width: 1100px;
      margin: 32px auto;
      padding: 0 16px 40px;
    }
    .card {
      background: var(--card);

      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 10px 30px rgba(37, 99, 235, 0.08);
      margin-bottom: 16px;
    }
    h1 { margin: 0 0 8px; }
    p { margin: 6px 0; color: var(--muted); }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 10px;
      margin-top: 14px;
    }
    button, a.btn {
      border: none;
      border-radius: 10px;
      padding: 12px 14px;
      font-weight: 700;
      cursor: pointer;

      text-decoration: none;
      display: inline-block;
      text-align: center;
    }
    button.primary { background: var(--primary); color: #fff; }
    button.danger { background: var(--danger); color: #fff; }
    button.neutral, a.btn.neutral { background: #e2e8f0; color: #0f172a; }
    .legend {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      margin-top: 10px;
      font-size: 14px;
    }
    .pill { padding: 4px 10px; border-radius: 999px; font-weight: 700; }
    .pill.ok { background: #ccfbf1; color: var(--ok); }
    .pill.bad { background: #fee2e2; color: var(--danger); }
    pre {
      background: #0b1220;
      color: #e5edf9;

      border-radius: 12px;
      padding: 14px;
      overflow: auto;
      min-height: 180px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    th, td {
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
      padding: 10px 8px;
    }
    th { color: #334155; }
  </style>
</head>
<body>
  <div class="wrap">

    <div class="card">
      <h1>Test T14 - Generazione busta paga</h1>
      <p>Workflow testato: BEGIN TRANSACTION, SELECT Contratto, SELECT DatiMensili, CALCOLO, INSERT BustaPaga, COMMIT.</p>
      <div class="legend">
        <span class="pill ok">Transazione con commit: dati persistiti</span>
        <span class="pill bad">Non atomica: rischio stato parziale</span>
      </div>
      <div class="grid">
        <button id="setupBtn" class="primary">1) Setup tabella di test + seed</button>
        <button id="atomicBtn" class="primary">2) Esegui TRANSAZIONE + COMMIT</button>
        <button id="nonAtomicBtn" class="danger">3) Esegui caso NON atomico</button>
        <button id="refreshBtn" class="neutral">4) Aggiorna stato tabella</button>
        <a class="btn neutral" href="/SITO/BPIC/dashboard.php">Torna alla dashboard</a>
      </div>
    </div>

    <div class="card">
      <h2>Output API</h2>
      <pre id="log">Pronto.</pre>
    </div>


    <div class="card">
      <h2>Stato tabella T14_Test_BustaPaga (ultime 30 righe)</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Scenario</th>
            <th>Step</th>
            <th>Contratto</th>
            <th>Lordo</th>
            <th>Netto</th>
            <th>Tasse</th>
            <th>Batch</th>
            <th>Data</th>
          </tr>
        </thead>
        <tbody id="rows"></tbody>
      </table>
    </div>

  </div>

  <script>
    const logEl = document.getElementById('log');
    const rowsEl = document.getElementById('rows');
    let bearerToken = '';


/**
 * Function: log
 * Parameters: data
 * Return: mixed
 * Description: Executes business logic for log.
 */
    function log(data) {
      const text = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
      logEl.textContent = text;
    }

    async function ensureToken() {
      if (bearerToken) return bearerToken;
      const res = await fetch('/SITO/BPIC/api/generate_token.php', { method: 'GET' });
      const json = await res.json();
      if (!res.ok || !json.token) {
        throw new Error('Impossibile ottenere token API.');
      }
      bearerToken = json.token;

      return bearerToken;
    }

    async function callUseCase(useCase, method = 'GET') {
      const token = await ensureToken();
      const opts = {
        method,
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json'
        }
      };
      if (method !== 'GET') {
        opts.body = JSON.stringify({ use_case: useCase });
      }

      const url = method === 'GET'
        ? '/SITO/BPIC/api/use_cases.php?use_case=' + encodeURIComponent(useCase)
        : '/SITO/BPIC/api/use_cases.php';


      const res = await fetch(url, opts);
      const json = await res.json();
      return { status: res.status, data: json };
    }


/**
 * Function: renderRows
 * Parameters: rows
 * Return: mixed
 * Description: Executes business logic for renderRows.
 */
    function renderRows(rows) {
      rowsEl.innerHTML = '';
      if (!rows || !rows.length) {
        rowsEl.innerHTML = '<tr><td colspan="9">Nessun dato.</td></tr>';
        return;
      }

      rows.forEach((r) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${r.ID_test ?? ''}</td>
          <td>${r.scenario ?? ''}</td>
          <td>${r.step_code ?? ''}</td>
          <td>${r.contratto_tipo ?? ''}</td>
          <td>${r.lordo ?? ''}</td>

          <td>${r.netto ?? ''}</td>
          <td>${r.tasse ?? ''}</td>
          <td>${r.batch_uuid ?? ''}</td>
          <td>${r.created_at ?? ''}</td>
        `;
        rowsEl.appendChild(tr);
      });
    }

    async function refreshState() {
      const out = await callUseCase('t14_test_state', 'GET');
      if (out.status >= 400) {
        log(out.data);
        return;
      }
      renderRows(out.data.rows || []);
    }

    document.getElementById('setupBtn').addEventListener('click', async () => {
      try {

        const out = await callUseCase('t14_test_setup', 'POST');
        log(out);
        await refreshState();
      } catch (e) {
        log(String(e));
      }
    });

    document.getElementById('atomicBtn').addEventListener('click', async () => {
      try {
        const out = await callUseCase('t14_generazione_busta_paga_atomic', 'POST');
        log(out);
        await refreshState();
      } catch (e) {
        log(String(e));
      }
    });

    document.getElementById('nonAtomicBtn').addEventListener('click', async () => {
      try {

        const out = await callUseCase('t14_generazione_busta_paga_non_atomic', 'POST');
        log(out);
        await refreshState();
      } catch (e) {
        log(String(e));
      }
    });

    document.getElementById('refreshBtn').addEventListener('click', async () => {
      try {
        await refreshState();
      } catch (e) {
        log(String(e));
      }
    });

    refreshState().catch((e) => log(String(e)));
  </script>
  <script src="/SITO/BPIC/auth/auto_logout_on_close.js"></script>
</body>

</html>