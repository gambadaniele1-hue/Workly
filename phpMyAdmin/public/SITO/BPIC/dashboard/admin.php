<?php
declare(strict_types=1);

/*
 * dashboard/admin.php — Area riservata agli amministratori.
 *
 * Flusso:
 *   1. auth.php verifica il JWT nel cookie e popola $currentUser
 *   2. Se il ruolo non è 'admin' → redirect a home.php
 *   3. Altrimenti mostra la dashboard con tre sezioni:
 *        - Gestione Utenti  : lista + elimina
 *        - Ruoli            : lista ruoli con permessi
 *        - Gestione Ruoli   : cambia ruolo a un utente
 *   4. Ogni azione usa fetch() verso le API in api/admin/
 */

require_once __DIR__ . '/../api/auth.php'; // popola $currentUser e $pdo

// Solo gli admin possono accedere
if ($currentUser['role_name'] !== 'admin') {
    header('Location: /SITO/BPIC/home.php');
    exit;
}

// Recupera email dal DB per mostrarla nella sidebar
$_stmt = $pdo->prepare('SELECT Email FROM Utenti WHERE ID_utente = ? LIMIT 1');
$_stmt->execute([$currentUser['user_id']]);
$email = (string)($_stmt->fetchColumn() ?: '');
unset($_stmt);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard — BPIC</title>
  <style>
    /* ── Variabili colore (stesse di home.php) ─────────────────────────── */
    :root {
      --bg:           #f1f5ff;
      --card:         #ffffff;
      --text:         #0f172a;
      --muted:        #64748b;
      --primary:      #2563eb;
      --danger:       #dc2626;
      --border:       #dbeafe;
      --sidebar:      #0b1a3a;
      --sidebar-soft: #172a55;
      --sidebar-text: #e6eeff;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: "Segoe UI", system-ui, sans-serif;
      background: radial-gradient(circle at 15% 10%, #dce8ff 0%, var(--bg) 45%, #eef4ff 100%);
      color: var(--text);
    }

    /* ── Layout sidebar + contenuto ───────────────────────────────────── */
    .layout {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 300px 1fr;
    }

    .sidebar {
      background: linear-gradient(180deg, var(--sidebar) 0%, #0f224a 100%);
      color: var(--sidebar-text);
      padding: 24px 18px;
      border-right: 1px solid rgba(255,255,255,0.14);
    }

    .brand      { font-size: 22px; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 4px; }
    .brand-sub  { font-size: 12px; color: #7fa4e8; margin-bottom: 16px; }
    .user       { font-size: 13px; color: #bcd0ff; margin-bottom: 18px; word-break: break-word; }

    .menu-title {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #9fb8f1;
      margin: 16px 8px 8px;
    }

    .menu { display: grid; gap: 8px; }

    .menu button {
      width: 100%;
      text-align: left;
      background: transparent;
      border: 1px solid rgba(174,197,255,0.18);
      color: var(--sidebar-text);
      font-weight: 700;
      font-size: 14px;
      border-radius: 12px;
      padding: 12px 14px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .menu button:hover,
    .menu button.active {
      background: var(--sidebar-soft);
      border-color: rgba(174,197,255,0.45);
      transform: translateX(2px);
    }

    .menu a {
      text-decoration: none;
      color: var(--sidebar-text);
      font-weight: 700;
      border-radius: 12px;
      padding: 12px 14px;
      background: transparent;
      border: 1px solid rgba(174,197,255,0.18);
      transition: all 0.2s ease;
    }
    .menu a:hover { background: var(--sidebar-soft); border-color: rgba(174,197,255,0.45); }

    /* ── Contenuto principale ──────────────────────────────────────────── */
    .content { padding: 32px 26px; }

    .section { display: none; }
    .section.active { display: block; }

    /* ── Schede / card ─────────────────────────────────────────────────── */
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 10px 28px rgba(37,99,235,0.10);
      margin-bottom: 20px;
    }
    .card h2 { margin: 0 0 6px; font-size: 22px; }
    .card p  { margin: 0 0 16px; color: var(--muted); font-size: 14px; }

    /* ── Tabelle ───────────────────────────────────────────────────────── */
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
    th { color: #334155; font-weight: 600; }
    tr:last-child td { border-bottom: none; }

    /* ── Bottoni ───────────────────────────────────────────────────────── */
    .btn {
      border: none;
      border-radius: 8px;
      padding: 7px 14px;
      font-weight: 700;
      font-size: 13px;
      cursor: pointer;
    }
    .btn-danger  { background: #fee2e2; color: var(--danger); }
    .btn-danger:hover  { background: #fca5a5; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: #1d4ed8; }

    /* ── Select ruolo ──────────────────────────────────────────────────── */
    select.role-select {
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 6px 10px;
      font-size: 13px;
      cursor: pointer;
    }

    /* ── Messaggi di stato ─────────────────────────────────────────────── */
    .msg-ok  { color: #0f766e; font-weight: 700; font-size: 13px; }
    .msg-err { color: var(--danger); font-weight: 700; font-size: 13px; }

    /* ── Badge permessi ────────────────────────────────────────────────── */
    .perm-badge {
      display: inline-block;
      background: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
      border-radius: 6px;
      padding: 2px 8px;
      font-size: 12px;
      margin: 2px;
    }

    @media (max-width: 980px) {
      .layout { grid-template-columns: 1fr; }
      .sidebar { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.14); }
    }
  </style>
</head>
<body>
<main class="layout">

  <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="brand">BPIC</div>
    <div class="brand-sub">Area Amministratore</div>
    <div class="user">Utente: <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>

    <div class="menu-title">Admin</div>
    <nav class="menu">
      <button id="btn-utenti"         onclick="showSection('utenti')">Gestione Utenti</button>
      <button id="btn-ruoli"          onclick="showSection('ruoli')">Ruoli e Permessi</button>
      <button id="btn-gestione-ruoli" onclick="showSection('gestione-ruoli')">Gestione Ruoli</button>
    </nav>

    <div class="menu-title">Navigazione</div>
    <nav class="menu">
      <a href="/SITO/BPIC/home.php">Home utente</a>
      <a href="/SITO/BPIC/logout.php">Logout</a>
    </nav>
  </aside>

  <!-- ── Contenuto ────────────────────────────────────────────────────── -->
  <section class="content">

    <!-- Sezione 1: Gestione Utenti -->
    <div id="section-utenti" class="section">
      <div class="card">
        <h2>Gestione Utenti</h2>
        <p>Lista di tutti gli utenti registrati. Puoi eliminare un account (tranne il tuo).</p>
        <div id="utenti-msg"></div>
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Email</th><th>Telefono</th><th>Ruolo</th><th>Azione</th>
            </tr>
          </thead>
          <tbody id="utenti-tbody">
            <tr><td colspan="5" style="color:var(--muted)">Caricamento…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Sezione 2: Ruoli e Permessi -->
    <div id="section-ruoli" class="section">
      <div class="card">
        <h2>Ruoli e Permessi</h2>
        <p>Panoramica dei ruoli esistenti e dei permessi associati a ciascuno.</p>
        <div id="ruoli-content">
          <p style="color:var(--muted)">Caricamento…</p>
        </div>
      </div>
    </div>

    <!-- Sezione 3: Gestione Ruoli -->
    <div id="section-gestione-ruoli" class="section">
      <div class="card">
        <h2>Gestione Ruoli</h2>
        <p>Cambia il ruolo di un utente usando il menu a tendina. Puoi anche promuovere a admin.</p>
        <div id="gestione-msg"></div>
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Email</th><th>Ruolo attuale</th><th>Nuovo ruolo</th><th>Salva</th>
            </tr>
          </thead>
          <tbody id="gestione-tbody">
            <tr><td colspan="5" style="color:var(--muted)">Caricamento…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </section>
</main>

<script>
  /*
   * Stato condiviso: lista ruoli caricata una volta sola e riusata
   * sia nella sezione "Ruoli" che in "Gestione Ruoli".
   */
  let allRoles = [];

  // ── Navigazione tra sezioni ──────────────────────────────────────────
  function showSection(name) {
    // Nasconde tutte le sezioni
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    // Rimuove 'active' da tutti i bottoni sidebar
    document.querySelectorAll('.menu button').forEach(b => b.classList.remove('active'));

    // Mostra la sezione scelta e marca il bottone come attivo
    document.getElementById('section-' + name).classList.add('active');
    document.getElementById('btn-' + name).classList.add('active');

    // Carica i dati per la sezione
    if (name === 'utenti')         loadUtenti();
    if (name === 'ruoli')          loadRuoli();
    if (name === 'gestione-ruoli') loadGestioneRuoli();
  }

  // ── Helper: mostra un messaggio temporaneo ───────────────────────────
  function showMsg(elId, text, isError) {
    const el = document.getElementById(elId);
    el.className = isError ? 'msg-err' : 'msg-ok';
    el.textContent = text;
    setTimeout(() => { el.textContent = ''; }, 3000);
  }

  // ── Helper: fetch JSON con gestione errori ──────────────────────────
  // Restituisce { ok, status, data } senza lanciare eccezioni.
  async function fetchJson(url, options = {}) {
    try {
      const res  = await fetch(url, options);
      let data;
      try {
        data = await res.json();
      } catch {
        // Il server ha risposto con non-JSON (es. redirect a login)
        data = { error: 'Sessione scaduta. Ricarica la pagina.' };
      }
      return { ok: res.ok, status: res.status, data };
    } catch (e) {
      return { ok: false, status: 0, data: { error: 'Errore di rete: ' + e.message } };
    }
  }

  // ── Sezione 1: Gestione Utenti ───────────────────────────────────────
  async function loadUtenti() {
    const tbody = document.getElementById('utenti-tbody');
    tbody.innerHTML = '<tr><td colspan="5" style="color:var(--muted)">Caricamento…</td></tr>';

    const { ok, data } = await fetchJson('/SITO/BPIC/api/admin/users.php');

    if (!ok) {
      tbody.innerHTML = `<tr><td colspan="5" class="msg-err">${data.error || 'Errore.'}</td></tr>`;
      return;
    }

    if (!data.users || data.users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5">Nessun utente trovato.</td></tr>';
      return;
    }

    tbody.innerHTML = data.users.map(u => `
      <tr>
        <td>${u.ID_utente}</td>
        <td>${escHtml(u.Email)}</td>
        <td>${escHtml(u.N_Telefono || '—')}</td>
        <td>${escHtml(u.Nome_ruolo || '—')}</td>
        <td>
          <button class="btn btn-danger" onclick="deleteUser(${u.ID_utente}, '${escHtml(u.Email)}')">
            Elimina
          </button>
        </td>
      </tr>
    `).join('');
  }

  async function deleteUser(id, email) {
    if (!confirm('Eliminare l\'utente "' + email + '"?\nQuesta operazione non può essere annullata.')) return;

    const { ok, data } = await fetchJson('/SITO/BPIC/api/admin/users.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_utente: id }),
    });

    if (ok) {
      showMsg('utenti-msg', 'Utente eliminato.', false);
      loadUtenti();
    } else {
      showMsg('utenti-msg', data.error || 'Errore durante l\'eliminazione.', true);
    }
  }

  // ── Sezione 2: Ruoli e Permessi ──────────────────────────────────────
  async function loadRuoli() {
    const container = document.getElementById('ruoli-content');
    container.innerHTML = '<p style="color:var(--muted)">Caricamento…</p>';

    const { ok, data } = await fetchJson('/SITO/BPIC/api/admin/roles.php');

    if (!ok) {
      container.innerHTML = `<p class="msg-err">${data.error || 'Errore.'}</p>`;
      return;
    }

    allRoles = data.roles || [];

    container.innerHTML = allRoles.map(r => `
      <div style="margin-bottom:16px; padding:14px; background:#f8fafc; border-radius:12px; border:1px solid var(--border)">
        <strong style="font-size:15px">#${r.ID_ruolo} — ${escHtml(r.Nome_ruolo)}</strong>
        <div style="margin-top:8px">
          ${r.permessi.length === 0
            ? '<span style="color:var(--muted);font-size:13px">Nessun permesso</span>'
            : r.permessi.map(p =>
                `<span class="perm-badge">${escHtml(p.Nome_privilegio)} (${escHtml(p.Azione)})</span>`
              ).join('')
          }
        </div>
      </div>
    `).join('');
  }

  // ── Sezione 3: Gestione Ruoli ────────────────────────────────────────
  async function loadGestioneRuoli() {
    const tbody = document.getElementById('gestione-tbody');
    tbody.innerHTML = '<tr><td colspan="5" style="color:var(--muted)">Caricamento…</td></tr>';

    const [resUsers, resRoles] = await Promise.all([
      fetchJson('/SITO/BPIC/api/admin/users.php'),
      fetchJson('/SITO/BPIC/api/admin/roles.php'),
    ]);

    const dataUsers = resUsers.data;
    const dataRoles = resRoles.data;

    if (!resUsers.ok || !resRoles.ok) {
      const errMsg = (!resUsers.ok ? resUsers.data.error : resRoles.data.error) || 'Errore nel caricamento dati.';
      tbody.innerHTML = `<tr><td colspan="5" class="msg-err">${errMsg}</td></tr>`;
      return;
    }

    allRoles = dataRoles.roles || [];

    // Costruisce le opzioni del select una volta sola
    const roleOptions = allRoles.map(r =>
      `<option value="${r.ID_ruolo}">${escHtml(r.Nome_ruolo)}</option>`
    ).join('');

    tbody.innerHTML = dataUsers.users.map(u => `
      <tr>
        <td>${u.ID_utente}</td>
        <td>${escHtml(u.Email)}</td>
        <td>${escHtml(u.Nome_ruolo || '—')}</td>
        <td>
          <select class="role-select" id="role-select-${u.ID_utente}">
            ${allRoles.map(r =>
              `<option value="${r.ID_ruolo}" ${r.ID_ruolo == u.ID_ruolo ? 'selected' : ''}>
                ${escHtml(r.Nome_ruolo)}
              </option>`
            ).join('')}
          </select>
        </td>
        <td>
          <button class="btn btn-primary" onclick="changeRole(${u.ID_utente})">
            Salva
          </button>
        </td>
      </tr>
    `).join('');
  }

  async function changeRole(idUtente) {
    const select  = document.getElementById('role-select-' + idUtente);
    const idRuolo = parseInt(select.value);

    const { ok, data } = await fetchJson('/SITO/BPIC/api/admin/roles.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_utente: idUtente, id_ruolo: idRuolo }),
    });

    if (ok) {
      showMsg('gestione-msg', 'Ruolo aggiornato.', false);
    } else {
      showMsg('gestione-msg', data.error || 'Errore durante l\'aggiornamento.', true);
    }
  }

  // ── Utility: escape HTML per prevenire XSS ───────────────────────────
  function escHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // Mostra Gestione Utenti di default all'apertura della pagina
  showSection('utenti');
</script>
</body>
</html>
