<?php
/**
 * File: dashboard.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
session_start();
$isAuthenticated = isset($_SESSION['user_id']);

// Recupera ruoli e privilegi dalla sessione se presenti, altrimenti dal DB
$roles = $isAuthenticated ? ($_SESSION['roles'] ?? null) : [];
$permissions = $isAuthenticated ? ($_SESSION['permissions'] ?? null) : [];

if ($isAuthenticated && (!$roles || !$permissions) && isset($_SESSION['email'])) {
    require_once __DIR__ . "/database.php";
    $email = $_SESSION['email'];

/* BLOCK COMMENT: SQL Query execution to interact with database records */
    $stmt = $pdo->prepare('SELECT r.ID_ruolo, r.Nome_ruolo, p.ID_privilegio, p.Nome_privilegio, p.Risorsa, p.Azione
        FROM Utente_Ruolo ur
        JOIN Ruoli r ON r.ID_ruolo = ur.ID_ruolo
        JOIN Ruolo_Privilegio rp ON rp.ID_ruolo = r.ID_ruolo
        JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
        WHERE ur.email_utente = ?');
    $stmt->execute([$email]);
    $result = $stmt->fetchAll();


// ===== SEZIONE 2: LOGICA DI PROCESSO =====
    $roles = [];
    $permissions = [];
    $roleMap = [];
    $permMap = [];

    foreach ($result as $row) {
        $roleId = (int)$row['ID_ruolo'];
        if (!isset($roleMap[$roleId])) {
            $roleMap[$roleId] = true;
            $roles[] = ['id' => $roleId, 'name' => $row['Nome_ruolo']];
        }

        $permId = (int)$row['ID_privilegio'];
        if (!isset($permMap[$permId])) {
            $permMap[$permId] = true;
            $permissions[] = ['id' => $permId, 'name' => $row['Nome_privilegio'], 'resource' => $row['Risorsa'], 'action' => $row['Azione']];
        }
    }

    $_SESSION['roles'] = $roles;

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
    $_SESSION['permissions'] = $permissions;
}

$roleNames = array_map(fn($r) => $r['name'], $roles ?? []);
$isAdmin = in_array('admin', $roleNames, true);
$isAbbonato = in_array('utente_abbonato', $roleNames, true);
$isNonAbbonato = in_array('utente_non_abbonato', $roleNames, true);
$isTenant = in_array('tenant', $roleNames, true);

if ($isAuthenticated && $isTenant) {
    header('Location: /SITO/BPIC/tenant_dashboard.php');
    exit;
}

if ($isAuthenticated) {
    header('Location: /SITO/BPIC/home.php');
    exit;
}
?>
<!DOCTYPE html>

<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BPIC | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue-700: #2563eb;
            --blue-500: #3b82f6;
            --blue-100: #e0edff;
            --slate-900: #0f172a;
            --slate-600: #475569;
            --white: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            background: radial-gradient(circle at top left, #eef4ff 0%, #f5f8ff 35%, #f8fafc 100%);

            color: var(--slate-900);
        }
        a { text-decoration: none; color: inherit; }
        .page { min-height: 100vh; }
        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 80px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
        }
        .brand-icon {
            width: 40px;
            height: 40px;

            border-radius: 12px;
            background: var(--blue-500);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
        }
        .nav-links {
            display: flex;
            gap: 24px;
            color: var(--slate-600);
            font-weight: 500;
        }
        .nav-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .btn {

            border-radius: 12px;
            padding: 10px 20px;
            border: 1px solid transparent;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-outline {
            background: transparent;
            border-color: var(--blue-500);
            color: var(--blue-500);
        }
        .btn-primary {
            background: var(--blue-500);
            color: var(--white);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);

        }
        .btn:hover { transform: translateY(-1px); }
        .hero {
            text-align: center;
            padding: 40px 24px 20px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            background: var(--blue-100);
            color: var(--blue-700);
            font-weight: 600;
            font-size: 14px;
        }
        .hero-title {
            font-size: 56px;
            font-weight: 800;

            margin: 24px 0 12px;
            line-height: 1.1;
        }
        .hero-title span { color: var(--blue-500); }
        .hero-subtitle {
            max-width: 680px;
            margin: 0 auto 28px;
            color: var(--slate-600);
            font-size: 18px;
        }
        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;

            padding: 40px 80px;
        }
        .feature-card {
            background: var(--white);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            text-align: center;
        }
        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: var(--blue-100);
            color: var(--blue-500);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

    .feature-card h3 { margin: 0 0 10px; font-size: 18px; }
        .feature-card p { margin: 0; color: var(--slate-600); font-size: 14px; line-height: 1.5; }
        .panel {
            background: var(--white);
            margin: 0 80px 60px;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }
        .panel h2 { margin-top: 0; }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: #f1f5f9;
            color: var(--slate-600);
            font-weight: 600;
        }

    .status.admin { color: #15803d; background: #dcfce7; }
        .status.abbonato { color: #1d4ed8; background: #dbeafe; }
        .status.non-abbonato { color: #b45309; background: #fef3c7; }
        .split {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }
        .list { margin: 0; padding-left: 18px; color: var(--slate-600); }
        .token-box textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
        }
        .token-actions {
            display: flex;
            gap: 12px;

            flex-wrap: wrap;
            margin-top: 12px;
        }
        .token-actions button {
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            background: var(--blue-500);
            color: var(--white);
            font-weight: 600;
            cursor: pointer;
        }
        .token-actions button.secondary {
            background: #e2e8f0;
            color: var(--slate-600);
        }
        .token-result {
            margin-top: 12px;
            color: var(--slate-600);
            white-space: pre-line;

        }
        .footer-actions {
            display: flex;
            justify-content: center;
            padding-bottom: 60px;
        }
        @media (max-width: 900px) {
            .nav { padding: 20px 24px; flex-wrap: wrap; gap: 16px; }
            .feature-grid, .panel { padding: 32px 24px; margin: 0 24px 48px; }
            .feature-grid { padding: 24px; }
            .hero-title { font-size: 42px; }
        }
    </style>
</head>
<body>
<div class="page">
    <nav class="nav">
        <div class="brand">
            <span class="brand-icon">📄</span>
            BPIC

        </div>
        <div class="nav-links">
            <a href="#funzionalita">Funzionalità</a>
            <a href="#prezzi">Prezzi</a>
        </div>
        <div class="nav-actions">
            <a class="btn btn-outline" href="/SITO/BPIC/login.php">Accedi</a>
            <a class="btn btn-primary" href="/SITO/BPIC/register.php">Registrati</a>
        </div>
    </nav>

    <section class="hero">
        <div class="pill">⚡ Genera la tua busta paga in pochi click</div>
        <h1 class="hero-title">La tua busta paga,<br><span>semplificata</span></h1>
        <p class="hero-subtitle">Calcola stipendio lordo, netto, tasse e contributi in modo preciso. Gestisci straordinari, ferie, malattie e tutte le voci variabili della tua retribuzione.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="/SITO/BPIC/register.php">Inizia Gratis →</a>
            <a class="btn btn-outline" href="/SITO/BPIC/login.php">Accedi</a>
            <a class="btn btn-outline" href="/SITO/BPIC/test_transazione_t14.php">Test Transazione T14</a>
        </div>

    </section>

    <section id="funzionalita" class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon">📑</div>
            <h3>Calcoli precisi</h3>
            <p>IRPEF, INPS, addizionali regionali e comunali calcolati secondo la normativa italiana.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🛡️</div>
            <h3>Dati sicuri</h3>
            <p>I tuoi dati personali e contrattuali sono protetti e accessibili solo a te.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <h3>Istantaneo</h3>
            <p>Genera la busta paga in tempo reale, esporta in PDF e invia via email.</p>
        </div>
    </section>



    <section id="prezzi" class="panel" style="text-align:center;">
        <h2>Prezzi chiari e trasparenti</h2>
        <p class="hero-subtitle">Scegli il piano giusto per la tua azienda o per il tuo team HR.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="/SITO/BPIC/register.php">Prova gratuita</a>
        </div>
    </section>

</div>

<script>
</script>
</body>
</html>