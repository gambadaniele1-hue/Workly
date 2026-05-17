<?php
/**
 * File: user_manual.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: /SITO/BPIC/login.php');
  exit;
}

$email = (string)($_SESSION['email'] ?? 'utente');
$roles = $_SESSION['roles'] ?? [];
$roleNames = [];
if (is_array($roles)) {
  foreach ($roles as $role) {
    if (isset($role['name'])) {
      $roleNames[] = $role['name'];
    }
  }
}
$roleDisplay = !empty($roleNames) ? implode(', ', $roleNames) : 'Utente standard';

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
$roleIds = [];
if (is_array($roles)) {
  foreach ($roles as $role) {
    if (isset($role['id'])) {
      $roleIds[] = (int)$role['id'];
    }
  }
}
$isAbbonato = in_array(1, $roleIds, true) || in_array(2, $roleIds, true);
$isTenant = in_array('tenant', $roleNames, true);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manuale Utente - BPIC</title>
  <style>
    :root {
      --bg: #f1f5ff;

      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --primary: #2563eb;
      --secondary: #0f766e;
      --border: #dbeafe;
      --sidebar: #0b1a3a;
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
    .layout {
      min-height: 100vh;
      display: grid;

      grid-template-columns: 300px 1fr;
    }
    .sidebar {
      background: linear-gradient(180deg, var(--sidebar) 0%, #0f224a 100%);
      color: var(--sidebar-text);
      padding: 24px 18px;
      border-right: 1px solid rgba(255, 255, 255, 0.14);
    }
    .brand {
      font-size: 22px;
      font-weight: 800;
      letter-spacing: 0.5px;
      margin-bottom: 16px;
    }
    .user {
      font-size: 13px;
      color: #bcd0ff;
      margin-bottom: 18px;
      word-break: break-word;
    }

    .menu-title {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #9fb8f1;
      margin: 16px 8px 8px;
    }
    .menu {
      display: grid;
      gap: 8px;
    }
    .menu a {
      text-decoration: none;
      color: var(--sidebar-text);
      font-weight: 700;
      border-radius: 12px;
      padding: 12px 14px;
      background: transparent;
      border: 1px solid rgba(174, 197, 255, 0.18);
      transition: all 0.2s ease;

    }
    .menu a:hover {
      background: var(--sidebar-soft);
      border-color: rgba(174, 197, 255, 0.45);
      transform: translateX(2px);
    }
    .content {
      padding: 32px 26px;
      overflow-y: auto;
      max-height: 100vh;
    }
    .hero {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 10px 28px rgba(37, 99, 235, 0.10);
      margin-bottom: 24px;
    }
    h1 {

      margin: 0 0 8px;
      font-size: 32px;
    }
    h2 {
      margin: 24px 0 12px;
      font-size: 22px;
      color: var(--primary);
      border-bottom: 2px solid var(--border);
      padding-bottom: 8px;
    }
    .subtitle {
      margin: 0;
      color: var(--muted);
    }
    .role-badge {
      display: inline-block;
      background: var(--primary);
      color: white;
      padding: 6px 12px;
      border-radius: 8px;

      font-weight: 700;
      font-size: 13px;
      margin-top: 8px;
    }
    .role-badge.tenant {
      background: var(--secondary);
    }
    .section-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 16px;
    }
    .section-card h3 {
      margin: 0 0 8px;
      font-size: 16px;
      color: var(--primary);
    }
    .section-card p, .section-card li {

      margin: 6px 0;
      line-height: 1.6;
      color: #334155;
    }
    .section-card ul {
      margin: 12px 0;
      padding-left: 20px;
    }
    .section-card li {
      margin: 8px 0;
    }
    .step {
      background: #f0f4ff;
      border-left: 4px solid var(--primary);
      padding: 12px;
      margin: 12px 0;
      border-radius: 4px;
    }
    .step strong {
      color: var(--primary);

    }
    .tip {
      background: #f0ffd4;
      border-left: 4px solid #84cc16;
      padding: 12px;
      margin: 12px 0;
      border-radius: 4px;
      color: #476d07;
    }
    .warning {
      background: #ffedd5;
      border-left: 4px solid #ea580c;
      padding: 12px;
      margin: 12px 0;
      border-radius: 4px;
      color: #7c2d12;
    }
    .back-btn {
      display: inline-block;
      text-decoration: none;

      background: var(--secondary);
      color: white;
      padding: 10px 16px;
      border-radius: 8px;
      font-weight: 700;
      margin-bottom: 20px;
      transition: all 0.2s ease;
    }
    .back-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3);
    }
    @media (max-width: 980px) {
      .layout {
        grid-template-columns: 1fr;
      }
      .sidebar {
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.14);
      }

    }
  </style>
</head>
<body>
  <main class="layout">
    <aside class="sidebar">
      <div class="brand">BPIC</div>
      <div class="user">Utente: <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>

      <div class="menu-title">Navigazione</div>
      <nav class="menu">
        <a href="/SITO/BPIC/home.php">Home</a>
        <a href="/SITO/BPIC/user_manual.php">Manuale Utente</a>
      </nav>

      <div class="menu-title">Sessione</div>
      <nav class="menu">
        <a href="/SITO/BPIC/logout.php">Logout</a>
      </nav>
    </aside>


    <section class="content">
      <a href="/SITO/BPIC/home.php" class="back-btn">← Torna alla Home</a>

      <div class="hero">
        <h1>📖 Manuale Utente</h1>
        <p class="subtitle">Guida passo passo per utilizzare il sistema BPIC</p>
        <span class="role-badge <?= $isTenant ? 'tenant' : '' ?>">
          👤 Ruolo: <?= htmlspecialchars($roleDisplay, ENT_QUOTES, 'UTF-8') ?>
        </span>
      </div>

      <div class="section-card">
        <h3>🔑 Introduzione al Sistema</h3>
        <p>Benvenuto su BPIC! Questo sistema è progettato per gestire contratti, impostazioni e operazioni relative agli impiegati. A seconda del tuo ruolo, avrai accesso a diverse funzioni.</p>
        <div class="tip">
          <strong>💡 Suggerimento:</strong> Tutti i tuoi dati sono sicuri e autenticati tramite sessione. Quando chiudi la pagina, la sessione termina automaticamente.
        </div>
      </div>


      <div class="section-card">
        <h3>👥 Il Tuo Ruolo</h3>
        <p>
          Sei loggato come: <strong><?= htmlspecialchars($roleDisplay, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
        <?php if ($isTenant): ?>
          <div class="step">
            <strong>🏢 Tenant Admin</strong>
            <p>Hai accesso all'area tenant per gestire aziende, trattative e pipeline commerciale. Puoi visualizzare e modificare i record della tua area.</p>
          </div>
        <?php elseif ($isAbbonato): ?>
          <div class="step">
            <strong>⭐ Utente Abbonato</strong>
            <p>Hai accesso completo alle funzioni Storico e Confronto, oltre alle viste operative standard. Potrai analizzare i dati storici e fare confronti.</p>
          </div>
        <?php else: ?>
          <div class="step">
            <strong>👤 Utente Standard</strong>
            <p>Hai accesso alle funzioni base del sistema. Alcune funzioni avanzate sono disponibili solo per utenti abbonati.</p>
          </div>

        <?php endif; ?>
      </div>

      <div class="section-card">
        <h3>🚀 Come Iniziare</h3>
        <div class="step">
          <strong>Passo 1: Completa il Tuo Profilo Contratto</strong>
          <p>Prima di usare il sistema, devi indicare il tipo di contratto che applichi. Vai su <em>Impostazioni Contratto</em> dalla sidebar e seleziona il tuo contratto.</p>
          <ul>
            <li>📋 Statale</li>
            <li>🏪 Commerciale</li>
            <li>⚙️ Metalmeccanico</li>
          </ul>
        </div>
        <div class="step">
          <strong>Passo 2: Naviga le Viste Operative</strong>
          <p>Una volta salvato il contratto, puoi accedere alle <em>Viste Operative</em>. Qui troverai tutte le funzionalità per gestire i dati relativi ai tuoi impiegati.</p>
        </div>
        <div class="step">
          <strong>Passo 3: Aggiorna le Impostazioni</strong>

          <p>Puoi tornare in qualsiasi momento a <em>Impostazioni Contratto</em> per modificare i parametri, come indennità, mensilità aggiuntive e altre configurazioni.</p>
        </div>
      </div>

      <div class="section-card">
        <h3>📊 Funzionalità Principali</h3>
        <ul>
          <li><strong>Viste Operative:</strong> Visualizza e gestisci i dati operativi relativi ai contratti.</li>
          <li><strong>Impostazioni Contratto:</strong> Configura i parametri del contratto (indennità, tredicesima, ecc.).</li>
          <li><strong>Dashboard Marketing:</strong> Accedi alla dashboard di marketing per visualizzare KPI e metriche.</li>
          <?php if (!$isAbbonato): ?>
            <li><em>(Funzioni Storico e Confronto disponibili per utenti abbonati)</em></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="section-card">
        <h3>🔐 Sicurezza e Sessione</h3>
        <p>
          La tua sessione è protetta e si chiude automaticamente quando:

        </p>
        <ul>
          <li>Chiudi la pagina/tab del browser</li>
          <li>Clicchi su "Logout"</li>
          <li>Il browser viene chiuso</li>
        </ul>
        <div class="warning">
          <strong>⚠️ Importante:</strong> Non condividere mai la tua sessione con altri utenti. Ogni accesso è tracciato e personalizzato.
        </div>
      </div>

      <div class="section-card">
        <h3>❓ Domande Frequenti</h3>
        <p><strong>Come posso cambiare il mio tipo di contratto?</strong></p>
        <p>Vai su "Impostazioni Contratto" dalla sidebar e seleziona il contratto desiderato, quindi salva.</p>
        
        <p><strong>Le mie impostazioni sono salvate?</strong></p>
        <p>Sì, tutte le modifiche vengono salvate nel database. Puoi controllare lo stato dalla home.</p>
        
        <p><strong>Posso accedere da più dispositivi contemporaneamente?</strong></p>

        <p>Sì, puoi avere più sessioni contemporaneamente su dispositivi diversi.</p>
      </div>

      <div class="section-card">
        <h3>📞 Supporto</h3>
        <p>Se hai domande o riscontri problemi, verifica il tuo ruolo e assicurati di avere i permessi per la funzione che desideri utilizzare.</p>
      </div>

      <a href="/SITO/BPIC/home.php" class="back-btn">← Torna alla Home</a>
    </section>
  </main>

  <script src="/SITO/BPIC/auth/auto_logout_on_close.js"></script>
</body>
</html>