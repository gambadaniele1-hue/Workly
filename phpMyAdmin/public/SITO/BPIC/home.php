<?php
/**
 * File: home.php
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
$roleIds = [];
if (is_array($roles)) {
  foreach ($roles as $role) {
    if (isset($role['id'])) {
      $roleIds[] = (int)$role['id'];
    }
  }
}
$isAbbonato = in_array(1, $roleIds, true) || in_array(2, $roleIds, true);

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home BPIC (Provvisoria)</title>
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
    .pill {
      display: inline-block;
      margin-left: 8px;
      font-size: 11px;

      padding: 2px 8px;
      border-radius: 999px;
      background: #264c9b;
      color: #dbe8ff;
    }
    .content {
      padding: 32px 26px;
    }
    .hero {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 10px 28px rgba(37, 99, 235, 0.10);
    }
    h1 {
      margin: 0 0 8px;
      font-size: 32px;
    }
    .subtitle {

      margin: 0;
      color: var(--muted);
    }
    .mail {
      margin-top: 6px;
      color: #334155;
      font-weight: 600;
    }
    .grid {
      margin-top: 18px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 12px;
    }
    a.btn {
      display: inline-block;
      text-decoration: none;
      text-align: center;
      font-weight: 700;
      padding: 12px 14px;

      border-radius: 12px;
      border: 1px solid transparent;
    }
    a.primary { background: var(--primary); color: #fff; }
    a.secondary { background: var(--secondary); color: #fff; }
    a.ghost { background: #e2e8f0; color: #0f172a; }
    .note {
      margin-top: 14px;
      font-size: 14px;
      color: var(--muted);
    }
    .warn {
      margin-top: 10px;
      display: inline-block;
      background: #ffedd5;
      color: #9a3412;
      border: 1px solid #fdba74;
      border-radius: 10px;
      padding: 8px 10px;
      font-size: 13px;

      font-weight: 700;
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
        <a href="/SITO/BPIC/mockup_viste.php">Nuova busta paga</a>
        <a href="/SITO/BPIC/mockup_viste.php">Mockup viste</a>
        <a href="/SITO/BPIC/user_manual.php">📖 Manuale Utente</a>
      </nav>

      <div class="menu-title">Area Abbonato</div>
      <nav class="menu">
        <?php if ($isAbbonato): ?>
          <a href="/SITO/BPIC/storico_buste_paga.php">Storico buste paga <span class="pill">Ruoli 1-2</span></a>
          <a href="/SITO/BPIC/mockup_viste.php">Confronto buste paga <span class="pill">Ruoli 1-2</span></a>
        <?php else: ?>
          <a href="/SITO/BPIC/storico_buste_paga.php">Storico buste paga</a>
          <a href="/SITO/BPIC/mockup_viste.php">Confronto buste paga</a>
        <?php endif; ?>
      </nav>

      <div class="menu-title">Contratto</div>
      <nav class="menu">

        <a href="/SITO/BPIC/Impostazioni_contratto.php">Modifica impostazioni contratto</a>
      </nav>

      <div class="menu-title">Sessione</div>
      <nav class="menu">
        <a href="/SITO/BPIC/dashboard.php">Apri dashboard</a>
        <a href="/SITO/BPIC/logout.php">Logout</a>
      </nav>
    </aside>

    <section class="content">
      <div class="hero">
        <h1>Home BPIC (provvisoria)</h1>
        <p class="subtitle">Da questa pagina puoi navigare le funzioni principali dalla sidebar a sinistra.</p>
        <p class="mail">Accesso completato. Le impostazioni contratto risultano gia salvate.</p>

        <?php if (!$isAbbonato): ?>
          <div class="warn">Le funzioni Storico e Confronto sono disponibili in modo completo per ruoli abbonato (ID ruolo 1 o 2).</div>
        <?php endif; ?>


        <div class="grid">
          <a class="btn primary" href="/SITO/BPIC/nuovabustapaga.php">Crea nuova busta paga</a>
          <a class="btn secondary" href="/SITO/BPIC/Impostazioni_contratto.php">Aggiorna contratto</a>
          <a class="btn ghost" href="/SITO/BPIC/test_transazione_t14.php">Test transazione T14</a>
          <a class="btn ghost" href="/SITO/BPIC/dashboard.php">Dashboard marketing</a>
          <a class="btn ghost" href="/SITO/BPIC/user_manual.php">📖 Manuale Utente</a>
        </div>

        <p class="note">Home temporanea: puo essere evoluta in una dashboard completa con widget e KPI.</p>
      </div>
    </section>
  </main>
  <script src="/SITO/BPIC/auth/auto_logout_on_close.js"></script>
</body>
</html>