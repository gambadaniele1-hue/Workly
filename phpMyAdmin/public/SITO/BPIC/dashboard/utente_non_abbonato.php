<?php
declare(strict_types=1);

/*
 * dashboard/utente_non_abbonato.php — Dashboard per utenti senza abbonamento.
 *
 * Sezioni:
 *   - Buste Paga   : lista + creazione nuova busta
 *   - Contratto    : impostazioni contratto (tipologia, maggiorazioni, ecc.)
 *   - Abbonamento  : piani disponibili per l'upgrade
 */

require_once __DIR__ . '/../api/auth.php'; // popola $currentUser e $pdo

// Solo utenti_non_abbonato possono accedere
if ($currentUser['role_name'] !== 'utente_non_abbonato') {
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
  <title>Dashboard — BPIC</title>
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
    .ps-hint { font-size: 11px; color: #a0aec0; margin-top: auto; padding-top: 12px; text-align: right; }

    /* ── Form ──────────────────────────────────────────────────────────── */
    .form-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
    }
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

    /* ── Piani abbonamento ─────────────────────────────────────────────── */
    .plans-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 8px; }
    .plan-card {
      border: 2px solid var(--border); border-radius: 16px;
      padding: 24px; text-align: center; position: relative;
      transition: all 0.2s;
    }
    .plan-card:hover { border-color: var(--primary); box-shadow: 0 6px 20px rgba(37,99,235,0.10); }
    .plan-card.featured { border-color: var(--primary); background: #fafcff; }
    .plan-card h3 { margin: 0 0 6px; font-size: 20px; }
    .plan-card .price { font-size: 30px; font-weight: 800; color: var(--primary); margin: 10px 0; }
    .plan-card .price span { font-size: 14px; color: var(--muted); font-weight: 400; }
    .plan-card ul { text-align: left; padding: 0 0 0 18px; margin: 14px 0 20px; color: #334155; font-size: 14px; line-height: 1.9; }
    .plan-badge {
      display: inline-block; background: var(--primary); color: #fff;
      font-size: 11px; font-weight: 700; padding: 3px 12px;
      border-radius: 99px; margin-bottom: 10px;
    }

    /* ── Step abbonamento ─────────────────────────────────────────────── */
    .sub-steps { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; flex-wrap: wrap; }
    .sub-step { font-size: 12px; font-weight: 700; color: var(--muted); padding: 4px 12px; border-radius: 99px; background: #f1f5ff; }
    .sub-step-active { background: #dbeafe; color: var(--primary); }
    .sub-step-done   { background: #d1fae5; color: var(--success); }
    .sub-step-sep { color: #cbd5e1; font-size: 14px; }
    .plan-selected-info {
      background: #dbeafe; border-radius: 10px;
      padding: 12px 16px; font-size: 14px; color: #1e40af;
      margin: 16px 0 20px;
    }

    /* ── Card di credito preview ─────────────────────────────────────── */
    .pay-card-preview {
      background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
      border-radius: 16px; padding: 24px 26px; color: #fff;
      position: relative; overflow: hidden;
      display: flex; flex-direction: column; gap: 0;
      min-height: 160px; justify-content: space-between;
    }
    .pay-card-preview::before {
      content: ''; position: absolute; top: -50px; right: -50px;
      width: 200px; height: 200px;
      background: rgba(255,255,255,0.07); border-radius: 50%;
    }
    .pay-card-preview::after {
      content: ''; position: absolute; bottom: -60px; left: 60px;
      width: 160px; height: 160px;
      background: rgba(255,255,255,0.05); border-radius: 50%;
    }
    .pay-card-chip {
      width: 42px; height: 32px;
      background: linear-gradient(135deg, #f9d423, #d97706);
      border-radius: 6px; margin-bottom: 18px;
    }
    .pay-card-number { font-size: 19px; font-family: 'Courier New', monospace; letter-spacing: 3px; margin-bottom: 18px; }
    .pay-card-bottom { display: flex; justify-content: space-between; }
    .pay-card-label  { font-size: 9px; opacity: 0.65; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }
    .pay-card-value  { font-size: 13px; font-weight: 700; font-family: 'Courier New', monospace; }

    /* ── Bottone paga ────────────────────────────────────────────────── */
    .pay-btn {
      width: 100%; padding: 16px; font-size: 16px; font-weight: 800;
      background: var(--primary); color: #fff;
      border: none; border-radius: 12px; cursor: pointer;
      transition: background 0.2s; margin-top: 20px;
    }
    .pay-btn:hover { background: #1d4ed8; }

    /* ── Box non implementato ────────────────────────────────────────── */
    .not-impl-box { text-align: center; padding: 48px 24px; }
    .not-impl-icon { font-size: 52px; margin-bottom: 16px; }
    .not-impl-title { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
    .not-impl-sub { color: var(--muted); margin-bottom: 28px; line-height: 1.7; font-size: 14px; }

    /* ── Modal ─────────────────────────────────────────────────────────── */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.45); z-index: 100;
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: #fff; border-radius: 18px;
      padding: 28px; max-width: 620px; width: 90%;
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

    @media (max-width: 900px) {
      .layout { grid-template-columns: 1fr; }
      .sidebar { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.14); }
      .form-grid { grid-template-columns: 1fr; }
      .plans-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<main class="layout">

  <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="brand">BPIC</div>
    <div class="brand-sub">Area Utente</div>
    <div class="user">Utente: <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>

    <?php if (!$hasContract): ?>
    <!-- Avviso cliccabile: porta direttamente alla sezione contratto -->
    <div class="urgent" id="urgent-warning" onclick="showSection('contratto')">
      ⚠️ Urgente: configura le impostazioni del contratto!
    </div>
    <?php endif; ?>

    <div class="menu-title">Funzioni</div>
    <nav class="menu">
      <button id="btn-buste-paga" onclick="showSection('buste-paga')">Buste Paga</button>
      <button id="btn-contratto"  onclick="showSection('contratto')">Impostazioni Contratto</button>
      <button id="btn-abbonamento" onclick="showSection('abbonamento')">Abbonamento</button>
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
        <p>Crea nuove buste paga o consulta quelle già generate. Clicca su una busta per i dettagli.</p>

        <!-- Bottone principale: crea nuova busta -->
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

    <!-- Sezione: Abbonamento -->
    <div id="section-abbonamento" class="section">
      <div class="card">
        <h2>Abbonamento</h2>
        <p>Scegli un piano per sbloccare funzioni avanzate: download PDF, archivio storico, invio email e confronto buste paga.</p>
        <div id="plans-content">
          <p style="color:var(--muted)">Caricamento piani…</p>
        </div>
      </div>
    </div>

  </section>
</main>

<!-- ── Modal overlay (usato sia per dettaglio che per creazione) ──────── -->
<div id="modal-overlay" class="modal-overlay" onclick="handleOverlayClick(event)">
  <div class="modal-box" id="modal-box">
    <div id="modal-content"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Chiudi</button>
    </div>
  </div>
</div>

<script>
  // ── Dati di esempio (usati come fallback se l'API non è disponibile) ──
  const MOCK_PAYSLIPS = [
    { ID_busta: 101, Mese_riferimento: '2025-04', Stipendio_lordo: 2150.00, Stipendio_netto: 1524.80, Paga_oraria: 13.44, Ore_lavorate: 160, Ore_ferie: 0,  Ore_malattia: 0, Ore_straordinari: 0,  Ore_festivi: 0, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2025-04-30' },
    { ID_busta: 102, Mese_riferimento: '2025-03', Stipendio_lordo: 2150.00, Stipendio_netto: 1524.80, Paga_oraria: 13.44, Ore_lavorate: 160, Ore_ferie: 16, Ore_malattia: 0, Ore_straordinari: 0,  Ore_festivi: 0, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2025-03-31' },
    { ID_busta: 103, Mese_riferimento: '2025-02', Stipendio_lordo: 2380.50, Stipendio_netto: 1672.30, Paga_oraria: 13.44, Ore_lavorate: 160, Ore_ferie: 0,  Ore_malattia: 0, Ore_straordinari: 12, Ore_festivi: 8, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2025-02-28' },
    { ID_busta: 104, Mese_riferimento: '2025-01', Stipendio_lordo: 2150.00, Stipendio_netto: 1524.80, Paga_oraria: 13.44, Ore_lavorate: 152, Ore_ferie: 0,  Ore_malattia: 8, Ore_straordinari: 0,  Ore_festivi: 0, Ore_prefestivi: 0, Ore_notturne: 0, Ore_reperibilita: 0, Ore_trasferta: 0, Data_creazione: '2025-01-31' },
  ];

  const MOCK_PLANS = [
    { id: 1, nome: 'Base', prezzo: 9.99,  periodo: 'mese', features: ['Buste paga illimitate', 'Storico 12 mesi', 'Download PDF buste paga'] },
    { id: 2, nome: 'Pro',  prezzo: 19.99, periodo: 'mese', features: ['Tutto il piano Base', 'Invio via email', 'Confronto buste paga', 'Supporto prioritario'] },
  ];

  let allPlans = [];
  let selectedPlan = null;

  // ── Navigazione tra sezioni ──────────────────────────────────────────
  function showSection(name) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.menu button').forEach(b => b.classList.remove('active'));
    document.getElementById('section-' + name).classList.add('active');
    document.getElementById('btn-' + name).classList.add('active');

    if (name === 'buste-paga')  loadPayslips();
    if (name === 'contratto')   loadContract();
    if (name === 'abbonamento') loadPlans();
  }

  // ── Modal ────────────────────────────────────────────────────────────
  function openModal(html) {
    document.getElementById('modal-content').innerHTML = html;
    document.getElementById('modal-overlay').classList.add('open');
  }
  function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
  }
  // Chiude cliccando fuori dal box
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
    return '€ ' + Number(v).toLocaleString('it-IT', { minimumFractionDigits: 2 });
  }

  // ── Buste Paga: carica lista ─────────────────────────────────────────
  async function loadPayslips() {
    const grid = document.getElementById('payslips-grid');
    grid.innerHTML = '<p style="color:var(--muted)">Caricamento…</p>';

    const { ok, data: _d } = await apiFetch('/SITO/BPIC/api/payslip');
    const real = ok ? (_d.payslips || []) : [];
    const data = { payslips: real.length > 0 ? real : MOCK_PAYSLIPS };

    if (!data.payslips || data.payslips.length === 0) {
      grid.innerHTML = '<p style="color:var(--muted)">Nessuna busta paga trovata. Creane una!</p>';
      return;
    }

    grid.innerHTML = data.payslips.map(p => {
      const badges = [];
      if (p.Ore_straordinari > 0) badges.push(`<span class="badge badge-straord">Straord. ${p.Ore_straordinari}h</span>`);
      if (p.Ore_ferie > 0)        badges.push(`<span class="badge badge-ferie">Ferie ${p.Ore_ferie}h</span>`);
      if (p.Ore_malattia > 0)     badges.push(`<span class="badge badge-malat">Malattia ${p.Ore_malattia}h</span>`);
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
          <div class="ps-hint">Dettagli →</div>
        </div>`;
    }).join('');
  }

  function formatMese(str) {
    if (!str) return str;
    const mesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
                   'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    const [anno, m] = str.split('-');
    return `${mesi[parseInt(m)] || m} ${anno}`;
  }

  // ── Buste Paga: modal dettaglio ──────────────────────────────────────
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
    `;
  }

  // ── Buste Paga: modal creazione ──────────────────────────────────────
  function openCreateModal() {
    const currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM
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
    const data = Object.fromEntries(new FormData(form).entries());
    btn.disabled = true; btn.textContent = 'Generazione…';

    const { ok, data: res } = await apiFetch('/SITO/BPIC/api/payslip', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });

    if (ok) {
      closeModal();
      showMsg('payslips-msg', `Busta paga creata — Lordo: ${formatCurrency(res.stipendio_lordo)}, Netto: ${formatCurrency(res.stipendio_netto)}`, false);
      loadPayslips();
    } else {
      const msgEl = document.getElementById('create-msg');
      msgEl.className = 'msg-err';
      msgEl.textContent = res.error || 'Errore durante la creazione.';
      btn.disabled = false; btn.textContent = 'Genera busta paga';
    }
  }

  // ── Contratto: carica form ───────────────────────────────────────────
  async function loadContract() {
    const { ok, data } = await apiFetch('/SITO/BPIC/api/contract');
    if (!ok || !data) return; // form resta vuoto se non configurato

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
      // Nasconde il warning urgente se il contratto è stato appena configurato
      const w = document.getElementById('urgent-warning');
      if (w) w.style.display = 'none';
    } else {
      showMsg('contratto-msg', data.error || 'Errore durante il salvataggio.', true);
    }
  }

  // ── Abbonamento: step indicator ─────────────────────────────────────
  function stepsHtml(active) {
    const labels = ['Piano', 'Pagamento', 'Conferma'];
    return `<div class="sub-steps">` + labels.map((s, i) => {
      const cls = i < active ? 'sub-step sub-step-done'
                : i === active ? 'sub-step sub-step-active'
                : 'sub-step';
      return (i > 0 ? `<span class="sub-step-sep">›</span>` : '') +
             `<span class="${cls}">${i + 1}. ${s}</span>`;
    }).join('') + `</div>`;
  }

  // ── Abbonamento: step 1 — scegli piano ──────────────────────────────
  async function loadPlans() {
    const { ok, data: _d } = await apiFetch('/SITO/BPIC/api/subscription/plans');
    allPlans = (ok ? _d : { plans: MOCK_PLANS }).plans;
    renderPlanStep();
  }

  function renderPlanStep() {
    const el = document.getElementById('plans-content');
    el.innerHTML = stepsHtml(0) + `
      <div class="plans-grid" style="margin-top:24px">
        ${allPlans.map((p, i) => `
          <div class="plan-card ${i === 1 ? 'featured' : ''}">
            ${i === 1 ? '<div class="plan-badge">Più popolare</div>' : ''}
            <h3>${p.nome}</h3>
            <div class="price">${formatCurrency(p.prezzo)} <span>/ ${p.periodo}</span></div>
            <ul>${p.features.map(f => `<li>${f}</li>`).join('')}</ul>
            <button class="btn btn-primary" style="width:100%;margin-top:4px" onclick="selectPlan(${p.id})">
              Scegli ${p.nome}
            </button>
          </div>
        `).join('')}
      </div>`;
  }

  function selectPlan(id) {
    selectedPlan = allPlans.find(p => p.id === id);
    renderPaymentStep();
  }

  // ── Abbonamento: step 2 — dati di pagamento ──────────────────────────
  function renderPaymentStep() {
    const el = document.getElementById('plans-content');
    el.innerHTML = stepsHtml(1) + `
      <button class="btn btn-ghost" style="margin:16px 0 4px" onclick="renderPlanStep()">← Torna ai piani</button>
      <div class="plan-selected-info">
        Piano selezionato: <strong>${selectedPlan.nome}</strong>
        &nbsp;—&nbsp;<strong>${formatCurrency(selectedPlan.prezzo)} / ${selectedPlan.periodo}</strong>
      </div>
      <div class="pay-card-preview">
        <div class="pay-card-chip"></div>
        <div class="pay-card-number" id="card-preview-num">•••• &nbsp;•••• &nbsp;•••• &nbsp;••••</div>
        <div class="pay-card-bottom">
          <div>
            <div class="pay-card-label">Titolare</div>
            <div class="pay-card-value" id="card-preview-name">NOME COGNOME</div>
          </div>
          <div style="text-align:right">
            <div class="pay-card-label">Scadenza</div>
            <div class="pay-card-value" id="card-preview-exp">MM/AA</div>
          </div>
        </div>
      </div>
      <form id="payment-form" onsubmit="submitPayment(event)" style="margin-top:20px">
        <div class="form-grid">
          <div class="form-group form-full">
            <label>Numero carta</label>
            <input type="text" name="card_number" placeholder="1234 5678 9012 3456"
                   maxlength="19" inputmode="numeric" autocomplete="cc-number"
                   oninput="fmtCardNum(this)" required>
          </div>
          <div class="form-group form-full">
            <label>Nome titolare</label>
            <input type="text" name="card_holder" placeholder="Mario Rossi"
                   autocomplete="cc-name"
                   oninput="document.getElementById('card-preview-name').textContent=(this.value.toUpperCase()||'NOME COGNOME')"
                   required>
          </div>
          <div class="form-group">
            <label>Scadenza</label>
            <input type="text" name="card_expiry" placeholder="12/27" maxlength="5"
                   inputmode="numeric" autocomplete="cc-exp"
                   oninput="fmtExpiry(this)" required>
          </div>
          <div class="form-group">
            <label>CVV</label>
            <input type="text" name="card_cvv" placeholder="123" maxlength="4"
                   inputmode="numeric" autocomplete="cc-csc"
                   oninput="this.value=this.value.replace(/\\D/g,'')" required>
          </div>
        </div>
        <button type="submit" class="pay-btn">
          Paga ${formatCurrency(selectedPlan.prezzo)} / ${selectedPlan.periodo}
        </button>
        <p style="font-size:11px;color:var(--muted);text-align:center;margin-top:10px">
          Pagamento sicuro — connessione cifrata SSL
        </p>
      </form>`;
  }

  function fmtCardNum(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 16);
    input.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
    const disp = (v + '•'.repeat(Math.max(0, 16 - v.length))).replace(/(.{4})/g, '$1 ').trim();
    document.getElementById('card-preview-num').textContent = disp;
  }

  function fmtExpiry(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 4);
    if (v.length >= 3) v = v.slice(0, 2) + '/' + v.slice(2);
    input.value = v;
    document.getElementById('card-preview-exp').textContent = v || 'MM/AA';
  }

  function submitPayment(e) {
    e.preventDefault();
    renderConfirmStep();
  }

  // ── Abbonamento: step 3 — conferma (non implementata) ───────────────
  function renderConfirmStep() {
    const el = document.getElementById('plans-content');
    el.innerHTML = stepsHtml(2) + `
      <div class="not-impl-box">
        <div class="not-impl-icon">🔧</div>
        <div class="not-impl-title">Funzione non ancora disponibile</div>
        <p class="not-impl-sub">
          Il sistema di pagamento è in fase di sviluppo.<br>
          Ti contatteremo non appena il servizio sarà operativo.
        </p>
        <button class="btn btn-primary" onclick="renderPlanStep()" style="padding:12px 32px;font-size:15px">
          Torna ai piani
        </button>
      </div>`;
  }

  // ── Avvio: mostra la sezione buste paga ──────────────────────────────
  showSection('buste-paga');
</script>
</body>
</html>
