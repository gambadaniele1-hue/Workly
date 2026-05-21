<?php
declare(strict_types=1);

/*
 * dashboard/utente_abbonato.php — Dashboard per utenti con abbonamento attivo.
 *
 * Sezioni:
 *   - Buste Paga  : lista + creazione + download PDF + invio email + elimina
 *   - Contratto   : impostazioni contratto
 *   - Confronto   : confronto libero tra due buste paga
 */

require_once __DIR__ . '/../api/auth.php'; // popola $currentUser e $pdo

// Solo utenti_abbonato possono accedere
if ($currentUser['role_name'] !== 'utente_abbonato') {
    header('Location: /SITO/BPIC/login.php');
    exit;
}

// Recupera email dal DB per mostrarla nella sidebar
$_stmt = $pdo->prepare('SELECT Email FROM Utenti WHERE ID_utente = ? LIMIT 1');
$_stmt->execute([$currentUser['user_id']]);
$email = (string)($_stmt->fetchColumn() ?: '');
unset($_stmt);

// Controlla se le impostazioni contratto sono già state configurate
$_stmt = $pdo->prepare('SELECT COUNT(*) FROM Impostazioni_contratto WHERE ID_utente = ?');
$_stmt->execute([$currentUser['user_id']]);
$hasContract = (int)$_stmt->fetchColumn() > 0;
unset($_stmt);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Abbonato — BPIC</title>
  <style>
    :root {
      --bg:           #f1f5ff;
      --card:         #ffffff;
      --text:         #0f172a;
      --muted:        #64748b;
      --primary:      #2563eb;
      --danger:       #dc2626;
      --success:      #0f766e;
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

    /* ── Layout ────────────────────────────────────────────────────────── */
    .layout { min-height: 100vh; display: grid; grid-template-columns: 280px 1fr; }

    .sidebar {
      background: linear-gradient(180deg, var(--sidebar) 0%, #0f224a 100%);
      color: var(--sidebar-text);
      padding: 24px 18px;
      border-right: 1px solid rgba(255,255,255,0.14);
    }

    .brand     { font-size: 22px; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 4px; }
    .brand-sub { font-size: 12px; color: #7fa4e8; margin-bottom: 16px; }
    .user      { font-size: 13px; color: #bcd0ff; margin-bottom: 18px; word-break: break-word; }

    .menu-title {
      font-size: 12px; text-transform: uppercase;
      letter-spacing: 1px; color: #9fb8f1;
      margin: 16px 8px 8px;
    }
    .menu { display: grid; gap: 8px; }
    .menu button {
      width: 100%; text-align: left;
      background: transparent;
      border: 1px solid rgba(174,197,255,0.18);
      color: var(--sidebar-text);
      font-weight: 700; font-size: 14px;
      border-radius: 12px; padding: 12px 14px;
      cursor: pointer; transition: all 0.2s ease;
    }
    .menu button:hover, .menu button.active {
      background: var(--sidebar-soft);
      border-color: rgba(174,197,255,0.45);
      transform: translateX(2px);
    }
    .menu a {
      display: block; text-decoration: none;
      color: var(--sidebar-text); font-weight: 700;
      border-radius: 12px; padding: 12px 14px;
      background: transparent;
      border: 1px solid rgba(174,197,255,0.18);
      transition: all 0.2s ease;
    }
    .menu a:hover { background: var(--sidebar-soft); border-color: rgba(174,197,255,0.45); }

    /* ── Avviso urgente contratto ──────────────────────────────────────── */
    .urgent {
      background: #fee2e2; border: 1px solid #fca5a5;
      border-radius: 8px; padding: 10px 12px;
      color: #b91c1c; font-weight: 700; font-size: 13px;
      margin: 12px 0; cursor: pointer;
    }
    .urgent:hover { background: #fca5a5; }

    /* ── Contenuto ─────────────────────────────────────────────────────── */
    .content { padding: 32px 26px; }
    .section { display: none; }
    .section.active { display: block; }

    /* ── Card ──────────────────────────────────────────────────────────── */
    .card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 18px; padding: 24px;
      box-shadow: 0 10px 28px rgba(37,99,235,0.10);
      margin-bottom: 20px;
    }
    .card h2 { margin: 0 0 6px; font-size: 22px; }
    .card p  { margin: 0 0 16px; color: var(--muted); font-size: 14px; }

    /* ── Bottoni ───────────────────────────────────────────────────────── */
    .btn {
      border: none; border-radius: 8px;
      padding: 8px 16px; font-weight: 700; font-size: 13px; cursor: pointer;
    }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: #1d4ed8; }
    .btn-danger  { background: #fee2e2; color: var(--danger); }
    .btn-danger:hover { background: #fca5a5; }
    .btn-success { background: #d1fae5; color: var(--success); }
    .btn-success:hover { background: #a7f3d0; }
    .btn-ghost   { background: #e2e8f0; color: var(--text); }
    .btn-ghost:hover { background: #cbd5e1; }
    .btn-big {
      width: 100%; padding: 14px; font-size: 16px;
      border-radius: 12px; margin-bottom: 20px;
    }

    /* ── Griglia buste paga ────────────────────────────────────────────── */
    .payslip-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 16px; margin-top: 16px;
    }
    .payslip-card {
      background: #fff; border: 1.5px solid #dbeafe;
      border-radius: 16px; padding: 20px;
      cursor: pointer; transition: all 0.22s ease;
      display: flex; flex-direction: column;
    }
    .payslip-card:hover {
      border-color: var(--primary);
      box-shadow: 0 6px 22px rgba(37,99,235,0.14);
      transform: translateY(-3px);
    }
    .ps-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .ps-month { font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.8px; }
    .ps-netto { font-size: 26px; font-weight: 800; color: var(--success); line-height: 1; }
    .ps-netto-label { font-size: 11px; color: var(--muted); margin-bottom: 12px; margin-top: 2px; }
    .ps-divider { height: 1px; background: #eef2ff; margin-bottom: 10px; }
    .ps-row { display: flex; justify-content: space-between; font-size: 13px; padding: 3px 0; }
    .ps-row span:first-child { color: var(--muted); }
    .ps-badges { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 10px; }
    .badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 99px; }
    .badge-straord { background: #ffedd5; color: #c2410c; }
    .badge-ferie   { background: #dbeafe; color: #1d4ed8; }
    .badge-malat   { background: #fce7f3; color: #be185d; }
    .badge-tred    { background: #d1fae5; color: #0f766e; }
    .ps-hint { font-size: 11px; color: #a0aec0; margin-top: auto; padding-top: 12px; text-align: right; }

    /* ── Form ──────────────────────────────────────────────────────────── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-group { display: flex; flex-direction: column; gap: 4px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #334155; }
    .form-group input, .form-group select {
      padding: 9px 11px; border: 1px solid #cbd5e1;
      border-radius: 8px; font-size: 14px;
    }
    .form-group input:focus, .form-group select:focus {
      outline: none; border-color: var(--primary);
    }
    .form-full { grid-column: 1 / -1; }

    /* ── Messaggi ──────────────────────────────────────────────────────── */
    .msg-ok  { color: #0f766e; font-weight: 700; font-size: 13px; }
    .msg-err { color: var(--danger); font-weight: 700; font-size: 13px; }

    /* ── Tabella confronto ─────────────────────────────────────────────── */
    .compare-table {
      width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 16px;
    }
    .compare-table th, .compare-table td {
      padding: 9px 10px; border-bottom: 1px solid #e5e7eb; text-align: right;
    }
    .compare-table th:first-child, .compare-table td:first-child {
      text-align: left; color: var(--muted); font-weight: 600;
    }
    .compare-table th { background: #f8faff; }
    .compare-table .positive { color: var(--success); font-weight: 700; }
    .compare-table .negative { color: var(--danger); font-weight: 700; }

    /* ── Modal ─────────────────────────────────────────────────────────── */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.45); z-index: 100;
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: #fff; border-radius: 18px;
      padding: 28px; max-width: 640px; width: 90%;
      max-height: 90vh; overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .modal-box h3 { margin: 0 0 16px; font-size: 20px; }
    .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

    /* ── Tabella dettaglio busta ───────────────────────────────────────── */
    .detail-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .detail-table td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; }
    .detail-table td:first-child { color: var(--muted); font-weight: 600; width: 55%; }
    .detail-table tr:last-child td { border-bottom: none; }

    /* ── Azioni busta (in modal) ───────────────────────────────────────── */
    .detail-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }

    @media (max-width: 900px) {
      .layout { grid-template-columns: 1fr; }
      .sidebar { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.14); }
      .form-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<main class="layout">

  <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="brand">BPIC</div>
    <div class="brand-sub">Area Abbonato</div>
    <div class="user">Utente: <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>

    <?php if (!$hasContract): ?>
    <div class="urgent" id="urgent-warning" onclick="showSection('contratto')">
      ⚠️ Urgente: configura le impostazioni del contratto!
    </div>
    <?php endif; ?>

    <div class="menu-title">Funzioni</div>
    <nav class="menu">
      <button id="btn-buste-paga" onclick="showSection('buste-paga')">Buste Paga</button>
      <button id="btn-contratto"  onclick="showSection('contratto')">Impostazioni Contratto</button>
      <button id="btn-confronto"  onclick="showSection('confronto')">Confronto Buste Paga</button>
    </nav>

    <div class="menu-title">Sessione</div>
    <nav class="menu">
      <a href="/SITO/BPIC/logout.php">Logout</a>
    </nav>
  </aside>

  <!-- ── Contenuto ────────────────────────────────────────────────────── -->
  <section class="content">

    <!-- Sezione: Buste Paga -->
    <div id="section-buste-paga" class="section">
      <div class="card">
        <h2>Buste Paga</h2>
        <p>Crea, scarica, invia via email o elimina le tue buste paga. Clicca su una busta per le azioni disponibili.</p>

        <button class="btn btn-primary btn-big" onclick="openCreateModal()">
          + Crea nuova busta paga
        </button>

        <div id="payslips-msg"></div>
        <div id="payslips-grid" class="payslip-grid">
          <p style="color:var(--muted)">Caricamento…</p>
        </div>
      </div>
    </div>

    <!-- Sezione: Impostazioni Contratto -->
    <div id="section-contratto" class="section">
      <div class="card">
        <h2>Impostazioni Contratto</h2>
        <p>Configura le caratteristiche del tuo contratto: tipologia, maggiorazioni e indennità.</p>
        <div id="contratto-msg"></div>
        <form id="contratto-form" onsubmit="submitContract(event)">
          <div class="form-grid">
            <div class="form-group form-full">
              <label>Tipologia dipendente</label>
              <select name="tipologia_dipendente">
                <option value="">— Seleziona —</option>
                <option value="Statale">Statale</option>
                <option value="Mettalmeccanico">Metalmeccanico</option>
                <option value="Commerciale">Commerciale</option>
              </select>
            </div>
            <div class="form-group">
              <label>Livello dipendente</label>
              <input type="text" name="livello_dipendente" placeholder="es. C1, B2…">
            </div>
            <div class="form-group">
              <label>Magg. straordinari (%)</label>
              <input type="number" name="maggiorazione_straordinaria" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Magg. festivi (%)</label>
              <input type="number" name="maggiorazione_festiva" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Magg. prefestivi (%)</label>
              <input type="number" name="maggiorazione_prefestiva" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Magg. notturni (%)</label>
              <input type="number" name="maggiorazione_notturna" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Indennità malattia (%)</label>
              <input type="number" name="indennita_malattia" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Indennità reperibilità (€/ora)</label>
              <input type="number" name="indennita_reperibilita" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Indennità trasferta (€/ora)</label>
              <input type="number" name="indennita_trasferta" min="0" step="0.01" value="0">
            </div>
            <div class="form-group">
              <label>Tredicesima</label>
              <select name="tredicesima">
                <option value="NO">No</option>
                <option value="SI">Sì</option>
              </select>
            </div>
            <div class="form-group">
              <label>Quattordicesima</label>
              <select name="quattordicesima">
                <option value="NO">No</option>
                <option value="SI">Sì</option>
              </select>
            </div>
          </div>
          <div style="margin-top:20px">
            <button type="submit" class="btn btn-primary">Salva impostazioni</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Sezione: Confronto Buste Paga -->
    <div id="section-confronto" class="section">
      <div class="card">
        <h2>Confronto Buste Paga</h2>
        <p>Seleziona due buste paga per confrontarle fianco a fianco.</p>
        <div id="confronto-msg"></div>

        <div class="form-grid" style="max-width:500px">
          <div class="form-group">
            <label>Busta A</label>
            <select id="compare-a">
              <option value="">Caricamento…</option>
            </select>
          </div>
          <div class="form-group">
            <label>Busta B</label>
            <select id="compare-b">
              <option value="">Caricamento…</option>
            </select>
          </div>
        </div>

        <div style="margin-top:16px">
          <button class="btn btn-primary" onclick="submitCompare()">Confronta</button>
        </div>

        <div id="compare-result"></div>
      </div>
    </div>

  </section>
</main>

<!-- ── Modal overlay ─────────────────────────────────────────────────── -->
<div id="modal-overlay" class="modal-overlay" onclick="handleOverlayClick(event)">
  <div class="modal-box" id="modal-box">
    <div id="modal-content"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Chiudi</button>
    </div>
  </div>
</div>

<script>
  // Cache locale delle buste paga (usata anche nei select del confronto)
  let cachedPayslips = [];

  // ── Dati di esempio (usati come fallback se l'API non è disponibile) ──
  const MOCK_PAYSLIPS = [
    { ID_busta: 101, Mese_riferimento: '2025-04', Stipendio_lordo: 2850.00, Stipendio_netto: 1982.40, Paga_oraria: 17.81, Ore_lavorate: 160, Ore_ferie: 0,  Ore_malattia: 0, Ore_straordinari: 0,  Ore_festivi: 0, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2025-04-30' },
    { ID_busta: 102, Mese_riferimento: '2025-03', Stipendio_lordo: 2850.00, Stipendio_netto: 1982.40, Paga_oraria: 17.81, Ore_lavorate: 160, Ore_ferie: 8,  Ore_malattia: 0, Ore_straordinari: 0,  Ore_festivi: 0, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2025-03-31' },
    { ID_busta: 103, Mese_riferimento: '2025-02', Stipendio_lordo: 3210.50, Stipendio_netto: 2215.60, Paga_oraria: 17.81, Ore_lavorate: 160, Ore_ferie: 0,  Ore_malattia: 0, Ore_straordinari: 18, Ore_festivi: 8, Ore_prefestivi: 4, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2025-02-28' },
    { ID_busta: 104, Mese_riferimento: '2025-01', Stipendio_lordo: 2850.00, Stipendio_netto: 1982.40, Paga_oraria: 17.81, Ore_lavorate: 160, Ore_ferie: 0,  Ore_malattia: 8, Ore_straordinari: 0,  Ore_festivi: 0, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2025-01-31' },
    { ID_busta: 105, Mese_riferimento: '2024-12', Stipendio_lordo: 4250.00, Stipendio_netto: 2890.30, Paga_oraria: 17.81, Ore_lavorate: 160, Ore_ferie: 0,  Ore_malattia: 0, Ore_straordinari: 0,  Ore_festivi: 0, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2024-12-31' },
    { ID_busta: 106, Mese_riferimento: '2024-11', Stipendio_lordo: 2850.00, Stipendio_netto: 1982.40, Paga_oraria: 17.81, Ore_lavorate: 152, Ore_ferie: 8,  Ore_malattia: 0, Ore_straordinari: 0,  Ore_festivi: 0, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2024-11-30' },
  ];

  // ── Navigazione tra sezioni ──────────────────────────────────────────
  function showSection(name) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.menu button').forEach(b => b.classList.remove('active'));
    document.getElementById('section-' + name).classList.add('active');
    document.getElementById('btn-' + name).classList.add('active');

    if (name === 'buste-paga') loadPayslips();
    if (name === 'contratto')  loadContract();
    if (name === 'confronto')  loadCompareSelects();
  }

  // ── Modal ────────────────────────────────────────────────────────────
  function openModal(html) {
    document.getElementById('modal-content').innerHTML = html;
    document.getElementById('modal-overlay').classList.add('open');
  }
  function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
  }
  function handleOverlayClick(e) {
    if (e.target === document.getElementById('modal-overlay')) closeModal();
  }

  // ── Helper fetch ─────────────────────────────────────────────────────
  async function apiFetch(url, opts = {}) {
    const res = await fetch(url, { credentials: 'same-origin', ...opts });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
  }

  function showMsg(elId, text, isError) {
    const el = document.getElementById(elId);
    el.className = isError ? 'msg-err' : 'msg-ok';
    el.textContent = text;
    setTimeout(() => { el.textContent = ''; }, 4000);
  }

  function formatCurrency(v) {
    return '€ ' + Number(v).toLocaleString('it-IT', { minimumFractionDigits: 2 });
  }
  function formatMese(str) {
    if (!str) return str;
    const mesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
                   'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    const [anno, m] = str.split('-');
    return `${mesi[parseInt(m)] || m} ${anno}`;
  }

  // ── Buste Paga: carica lista ─────────────────────────────────────────
  async function loadPayslips() {
    const grid = document.getElementById('payslips-grid');
    grid.innerHTML = '<p style="color:var(--muted)">Caricamento…</p>';

    const { ok, data } = await apiFetch('/SITO/BPIC/api/payslip');
    const real = ok ? (data.payslips || []) : [];
    cachedPayslips = real.length > 0 ? real : MOCK_PAYSLIPS;

    if (cachedPayslips.length === 0) {
      grid.innerHTML = '<p style="color:var(--muted)">Nessuna busta paga trovata. Creane una!</p>';
      return;
    }

    grid.innerHTML = cachedPayslips.map(p => {
      const badges = [];
      if (p.Ore_straordinari > 0) badges.push(`<span class="badge badge-straord">Straord. ${p.Ore_straordinari}h</span>`);
      if (p.Ore_ferie > 0)        badges.push(`<span class="badge badge-ferie">Ferie ${p.Ore_ferie}h</span>`);
      if (p.Ore_malattia > 0)     badges.push(`<span class="badge badge-malat">Malattia ${p.Ore_malattia}h</span>`);
      if (p.Mese_riferimento?.endsWith('-12')) badges.push(`<span class="badge badge-tred">Tredicesima</span>`);
      return `
        <div class="payslip-card" onclick="openDetailModal(${p.ID_busta})">
          <div class="ps-top">
            <span class="ps-month">${formatMese(p.Mese_riferimento)}</span>
            <span style="font-size:18px">🗒️</span>
          </div>
          <div class="ps-netto">${formatCurrency(p.Stipendio_netto)}</div>
          <div class="ps-netto-label">stipendio netto</div>
          <div class="ps-divider"></div>
          <div class="ps-row"><span>Lordo</span><strong>${formatCurrency(p.Stipendio_lordo)}</strong></div>
          ${p.Ore_lavorate != null ? `<div class="ps-row"><span>Ore lavorate</span><strong>${p.Ore_lavorate} h</strong></div>` : ''}
          ${badges.length ? `<div class="ps-badges">${badges.join('')}</div>` : ''}
          <div class="ps-hint">Dettagli e azioni →</div>
        </div>`;
    }).join('');
  }

  // ── Buste Paga: modal dettaglio con azioni ───────────────────────────
  async function openDetailModal(id) {
    openModal('<p style="color:var(--muted)">Caricamento…</p>');

    const { ok, data: _d } = await apiFetch(`/SITO/BPIC/api/payslip/${id}`);
    const data = ok ? _d : (MOCK_PAYSLIPS.find(p => p.ID_busta === id) ?? MOCK_PAYSLIPS[0]);

    document.getElementById('modal-content').innerHTML = `
      <h3>Busta paga — ${formatMese(data.Mese_riferimento)}</h3>
      <table class="detail-table">
        <tr><td>Stipendio lordo</td><td><strong>${formatCurrency(data.Stipendio_lordo)}</strong></td></tr>
        <tr><td>Stipendio netto</td><td><strong style="color:var(--success)">${formatCurrency(data.Stipendio_netto)}</strong></td></tr>
        <tr><td>Paga oraria</td><td>${formatCurrency(data.Paga_oraria)}</td></tr>
        <tr><td>Ore lavorate</td><td>${data.Ore_lavorate}</td></tr>
        <tr><td>Ore ferie</td><td>${data.Ore_ferie}</td></tr>
        <tr><td>Ore malattia</td><td>${data.Ore_malattia}</td></tr>
        <tr><td>Ore straordinari</td><td>${data.Ore_straordinari}</td></tr>
        <tr><td>Ore festivi</td><td>${data.Ore_festivi}</td></tr>
        <tr><td>Ore prefestivi</td><td>${data.Ore_prefestivi}</td></tr>
        <tr><td>Ore notturne</td><td>${data.Ore_notturne}</td></tr>
        <tr><td>Ore reperibilità</td><td>${data.Ore_reperibilita}</td></tr>
        <tr><td>Ore trasferta</td><td>${data.Ore_trasferta}</td></tr>
        <tr><td>Data creazione</td><td>${data.Data_creazione ?? '—'}</td></tr>
      </table>
      <div class="detail-actions">
        <button class="btn btn-success" onclick="downloadPdf(${id})">Scarica PDF</button>
        <button class="btn btn-primary" onclick="sendEmail(${id})">Invia via Email</button>
        <button class="btn btn-danger"  onclick="deletePayslip(${id})">Elimina</button>
      </div>
      <div id="detail-msg-${id}" style="margin-top:10px"></div>
    `;
  }

  // ── Scarica PDF ──────────────────────────────────────────────────────
  async function downloadPdf(id) {
    const { ok, data } = await apiFetch(`/SITO/BPIC/api/payslip/${id}/pdf`);
    // TODO: quando implementato, il server risponde con il PDF binario → aprire in nuova tab
    const msgEl = document.getElementById(`detail-msg-${id}`);
    msgEl.className = ok ? 'msg-ok' : 'msg-err';
    msgEl.textContent = data.error || 'Funzione non ancora disponibile.';
  }

  // ── Invia via Email ──────────────────────────────────────────────────
  async function sendEmail(id) {
    const { ok, data } = await apiFetch(`/SITO/BPIC/api/payslip/${id}/email`, { method: 'POST' });
    const msgEl = document.getElementById(`detail-msg-${id}`);
    msgEl.className = ok ? 'msg-ok' : 'msg-err';
    msgEl.textContent = ok ? 'Email inviata!' : (data.error || 'Funzione non ancora disponibile.');
  }

  // ── Elimina busta ────────────────────────────────────────────────────
  async function deletePayslip(id) {
    if (!confirm('Eliminare questa busta paga? L\'operazione non è reversibile.')) return;

    const { ok, data } = await apiFetch(`/SITO/BPIC/api/payslip/${id}`, { method: 'DELETE' });
    if (ok) {
      closeModal();
      showMsg('payslips-msg', 'Busta paga eliminata.', false);
      loadPayslips();
    } else {
      const msgEl = document.getElementById(`detail-msg-${id}`);
      if (msgEl) { msgEl.className = 'msg-err'; msgEl.textContent = data.error || 'Errore nell\'eliminazione.'; }
    }
  }

  // ── Modal creazione nuova busta ──────────────────────────────────────
  function openCreateModal() {
    const currentMonth = new Date().toISOString().slice(0, 7);
    openModal(`
      <h3>Crea nuova busta paga</h3>
      <div id="create-msg"></div>
      <form id="create-form" onsubmit="submitCreate(event)">
        <div class="form-grid">
          <div class="form-group form-full">
            <label>Mese di riferimento</label>
            <input type="month" name="mese_riferimento" value="${currentMonth}" required>
          </div>
          <div class="form-group">
            <label>Paga oraria (€) *</label>
            <input type="number" name="paga_oraria" min="0.01" step="0.01" required>
          </div>
          <div class="form-group">
            <label>Ore lavorate</label>
            <input type="number" name="ore_lavorate" min="0" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>Ore ferie</label>
            <input type="number" name="ore_ferie" min="0" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>Ore malattia</label>
            <input type="number" name="ore_malattia" min="0" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>Ore straordinari</label>
            <input type="number" name="ore_straordinari" min="0" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>Ore festivi</label>
            <input type="number" name="ore_festivi" min="0" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>Ore prefestivi</label>
            <input type="number" name="ore_prefestivi" min="0" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>Ore notturne</label>
            <input type="number" name="ore_notturne" min="0" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>Ore reperibilità</label>
            <input type="number" name="ore_reperibilita" min="0" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>Ore trasferta</label>
            <input type="number" name="ore_trasferta" min="0" step="0.5" value="0">
          </div>
        </div>
        <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end;">
          <button type="button" class="btn btn-ghost" onclick="closeModal()">Annulla</button>
          <button type="submit" id="create-btn" class="btn btn-primary">Genera busta paga</button>
        </div>
      </form>
    `);
  }

  async function submitCreate(e) {
    e.preventDefault();
    const btn  = document.getElementById('create-btn');
    const form = document.getElementById('create-form');
    const body = Object.fromEntries(new FormData(form).entries());
    btn.disabled = true; btn.textContent = 'Generazione…';

    const { ok, data } = await apiFetch('/SITO/BPIC/api/payslip', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });

    if (ok) {
      closeModal();
      showMsg('payslips-msg', `Busta creata — Lordo: ${formatCurrency(data.stipendio_lordo)}, Netto: ${formatCurrency(data.stipendio_netto)}`, false);
      loadPayslips();
    } else {
      const msgEl = document.getElementById('create-msg');
      msgEl.className = 'msg-err';
      msgEl.textContent = data.error || 'Errore durante la creazione.';
      btn.disabled = false; btn.textContent = 'Genera busta paga';
    }
  }

  // ── Contratto: carica e salva ─────────────────────────────────────────
  async function loadContract() {
    const { ok, data } = await apiFetch('/SITO/BPIC/api/contract');
    if (!ok || !data) return;

    const form = document.getElementById('contratto-form');
    for (const [key, val] of Object.entries(data)) {
      const el = form.elements[key.toLowerCase()] || form.elements[key];
      if (el) el.value = val;
    }
  }

  async function submitContract(e) {
    e.preventDefault();
    const form = document.getElementById('contratto-form');
    const body = Object.fromEntries(new FormData(form).entries());

    const { ok, data } = await apiFetch('/SITO/BPIC/api/contract', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });

    if (ok) {
      showMsg('contratto-msg', 'Impostazioni salvate con successo.', false);
      const w = document.getElementById('urgent-warning');
      if (w) w.style.display = 'none';
    } else {
      showMsg('contratto-msg', data.error || 'Errore durante il salvataggio.', true);
    }
  }

  // ── Confronto: popola select e confronta ─────────────────────────────
  async function loadCompareSelects() {
    if (cachedPayslips.length === 0) {
      const { ok, data } = await apiFetch('/SITO/BPIC/api/payslip');
      const real = ok ? (data.payslips || []) : [];
      cachedPayslips = real.length > 0 ? real : MOCK_PAYSLIPS;
    }

    const options = cachedPayslips.map(p =>
      `<option value="${p.ID_busta}">${formatMese(p.Mese_riferimento)} — Netto: ${formatCurrency(p.Stipendio_netto)}</option>`
    ).join('');

    const placeholder = '<option value="">— Seleziona —</option>';
    document.getElementById('compare-a').innerHTML = placeholder + options;
    document.getElementById('compare-b').innerHTML = placeholder + options;
  }

  async function submitCompare() {
    const idA = document.getElementById('compare-a').value;
    const idB = document.getElementById('compare-b').value;

    if (!idA || !idB) { showMsg('confronto-msg', 'Seleziona entrambe le buste paga.', true); return; }
    if (idA === idB)  { showMsg('confronto-msg', 'Seleziona due buste paga diverse.', true); return; }

    const { ok, data: _dc } = await apiFetch('/SITO/BPIC/api/payslip/compare', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_a: parseInt(idA), id_b: parseInt(idB) }),
    });

    let data;
    if (ok) {
      data = _dc;
    } else {
      const ma = MOCK_PAYSLIPS.find(p => p.ID_busta === parseInt(idA)) ?? MOCK_PAYSLIPS[0];
      const mb = MOCK_PAYSLIPS.find(p => p.ID_busta === parseInt(idB)) ?? MOCK_PAYSLIPS[1];
      data = {
        busta_a:    ma,
        busta_b:    mb,
        diff_lordo: parseFloat((ma.Stipendio_lordo - mb.Stipendio_lordo).toFixed(2)),
        diff_netto: parseFloat((ma.Stipendio_netto - mb.Stipendio_netto).toFixed(2)),
      };
    }

    const a = data.busta_a, b = data.busta_b;

    // Righe da confrontare
    const rows = [
      ['Stipendio lordo',   a.Stipendio_lordo,   b.Stipendio_lordo,   data.diff_lordo, true],
      ['Stipendio netto',   a.Stipendio_netto,   b.Stipendio_netto,   data.diff_netto, true],
      ['Paga oraria',       a.Paga_oraria,       b.Paga_oraria,       null, true],
      ['Ore lavorate',      a.Ore_lavorate,      b.Ore_lavorate,      null, false],
      ['Ore ferie',         a.Ore_ferie,         b.Ore_ferie,         null, false],
      ['Ore malattia',      a.Ore_malattia,      b.Ore_malattia,      null, false],
      ['Ore straordinari',  a.Ore_straordinari,  b.Ore_straordinari,  null, false],
      ['Ore festivi',       a.Ore_festivi,       b.Ore_festivi,       null, false],
      ['Ore prefestivi',    a.Ore_prefestivi,    b.Ore_prefestivi,    null, false],
      ['Ore notturne',      a.Ore_notturne,      b.Ore_notturne,      null, false],
      ['Ore reperibilità',  a.Ore_reperibilita,  b.Ore_reperibilita,  null, false],
      ['Ore trasferta',     a.Ore_trasferta,     b.Ore_trasferta,     null, false],
    ];

    function diffClass(v) {
      if (v === null) return '';
      return v > 0 ? 'positive' : v < 0 ? 'negative' : '';
    }
    function fmtDiff(v, isCurrency) {
      if (v === null) return '';
      const prefix = v > 0 ? '+' : '';
      return isCurrency ? prefix + formatCurrency(v) : prefix + v;
    }

    document.getElementById('compare-result').innerHTML = `
      <table class="compare-table">
        <thead>
          <tr>
            <th></th>
            <th>${formatMese(a.Mese_riferimento)}</th>
            <th>${formatMese(b.Mese_riferimento)}</th>
            <th>Differenza</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map(([label, va, vb, diff, isCurr]) => `
            <tr>
              <td>${label}</td>
              <td>${isCurr ? formatCurrency(va) : va}</td>
              <td>${isCurr ? formatCurrency(vb) : vb}</td>
              <td class="${diffClass(diff)}">${fmtDiff(diff, isCurr)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  // ── Avvio ────────────────────────────────────────────────────────────
  showSection('buste-paga');
</script>
</body>
</html>
