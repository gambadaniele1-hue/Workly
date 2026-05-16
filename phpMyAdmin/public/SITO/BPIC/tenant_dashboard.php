<?php
/**
 * File: tenant_dashboard.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

require_once __DIR__ . '/database.php';
session_start();

// INLINE COMMENT: Conditional logic or loop processing
if (empty($_SESSION['user_id'])) {
    header('Location: /SITO/BPIC/login.php');
    exit;
}


/**
 * Function: ensureTenantTables
 * Parameters: PDO $pdo
 * Return: mixed
 * Description: Executes business logic for ensureTenantTables.
 */
function ensureTenantTables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS Aziende_tenant (
        ID_azienda INT(11) NOT NULL AUTO_INCREMENT,
        ID_tenant INT(11) NOT NULL,
        Ragione_sociale VARCHAR(120) NOT NULL,
        Settore VARCHAR(80) DEFAULT NULL,
        Email_commerciale VARCHAR(120) DEFAULT NULL,
        Stato_relazione ENUM('prospect','in_negoziazione','attiva','chiusa') NOT NULL DEFAULT 'prospect',

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
        Data_creazione TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (ID_azienda),
        KEY idx_aziende_tenant (ID_tenant),
        KEY idx_aziende_stato (Stato_relazione)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS Vendite_tenant (
        ID_vendita INT(11) NOT NULL AUTO_INCREMENT,
        ID_tenant INT(11) NOT NULL,
        ID_azienda INT(11) NOT NULL,
        Nome_deal VARCHAR(120) NOT NULL,
        Valore_previsto DECIMAL(12,2) NOT NULL,
        Stato ENUM('bozza','trattativa','vinta','persa') NOT NULL DEFAULT 'bozza',
        Data_chiusura_prevista DATE DEFAULT NULL,
        Note TEXT DEFAULT NULL,
        Data_creazione TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (ID_vendita),
        KEY idx_vendite_tenant (ID_tenant),
        KEY idx_vendite_azienda (ID_azienda),
        KEY idx_vendite_stato (Stato)

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}


/**
 * Function: fetchSessionAuth
 * Parameters: PDO $pdo
 * Return: mixed
 * Description: Executes business logic for fetchSessionAuth.
 */
function fetchSessionAuth(PDO $pdo): array
{
    $roles = $_SESSION['roles'] ?? null;
    $permissions = $_SESSION['permissions'] ?? null;

// INLINE COMMENT: Conditional logic or loop processing
    if ($roles && $permissions) {
        return [is_array($roles) ? $roles : [], is_array($permissions) ? $permissions : []];
    }

    $email = (string)($_SESSION['email'] ?? '');
// INLINE COMMENT: Conditional logic or loop processing
    if ($email === '') {
        return [[], []];
    }


/* BLOCK COMMENT: SQL Query execution to interact with database records */
    $stmt = $pdo->prepare('SELECT r.ID_ruolo, r.Nome_ruolo, p.ID_privilegio, p.Nome_privilegio, p.Risorsa, p.Azione
        FROM Utente_Ruolo ur
        JOIN Ruoli r ON r.ID_ruolo = ur.ID_ruolo

// ===== SEZIONE 4: LOGICA DI PROCESSO =====
        JOIN Ruolo_Privilegio rp ON rp.ID_ruolo = r.ID_ruolo
        JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
        WHERE ur.email_utente = ?');
    $stmt->execute([$email]);
    $rows = $stmt->fetchAll();

    $roles = [];
    $permissions = [];
    $roleMap = [];
    $permMap = [];

// INLINE COMMENT: Conditional logic or loop processing
    foreach ($rows as $row) {
        $roleId = (int)$row['ID_ruolo'];
// INLINE COMMENT: Conditional logic or loop processing
        if (!isset($roleMap[$roleId])) {
            $roleMap[$roleId] = true;
            $roles[] = ['id' => $roleId, 'name' => $row['Nome_ruolo']];
        }

        $permId = (int)$row['ID_privilegio'];
// INLINE COMMENT: Conditional logic or loop processing
        if (!isset($permMap[$permId])) {

// ===== SEZIONE 5: LOGICA DI PROCESSO =====
            $permMap[$permId] = true;
            $permissions[] = [
                'id' => $permId,
                'name' => $row['Nome_privilegio'],
                'resource' => $row['Risorsa'],
                'action' => $row['Azione'],
            ];
        }
    }

    $_SESSION['roles'] = $roles;
    $_SESSION['permissions'] = $permissions;

    return [$roles, $permissions];
}


/**
 * Function: hasTenantPermission
 * Parameters: array $roleNames, array $permissions
 * Return: mixed
 * Description: Executes business logic for hasTenantPermission.
 */
function hasTenantPermission(array $roleNames, array $permissions): bool
{
// INLINE COMMENT: Conditional logic or loop processing
    if (in_array('admin', $roleNames, true)) {
        return true;

// ===== SEZIONE 6: LOGICA DI PROCESSO =====
    }

// INLINE COMMENT: Conditional logic or loop processing
    foreach ($permissions as $permission) {
        $resource = (string)($permission['resource'] ?? '');
        $action = (string)($permission['action'] ?? '');
// INLINE COMMENT: Conditional logic or loop processing
        if ($resource === 'vendite_tenant' && ($action === 'ALL' || $action === 'INSERT' || $action === 'UPDATE' || $action === 'SELECT')) {
            return true;
        }
    }

    return false;
}

ensureTenantTables($pdo);
[$roles, $permissions] = fetchSessionAuth($pdo);
$roleNames = array_values(array_filter(array_map(static fn($r): string => (string)($r['name'] ?? ''), $roles)));

$isAdmin = in_array('admin', $roleNames, true);
$isTenant = in_array('tenant', $roleNames, true);
$canManageTenant = $isTenant || hasTenantPermission($roleNames, $permissions);

// ===== SEZIONE 7: LOGICA DI PROCESSO =====

// INLINE COMMENT: Conditional logic or loop processing
if (!$canManageTenant) {
    http_response_code(403);
    echo '<p>Accesso negato: questa dashboard e riservata al ruolo tenant.</p>';
    echo '<p><a href="/SITO/BPIC/home.php">Torna alla home</a></p>';
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$errors = [];
$messages = [];

// INLINE COMMENT: Conditional logic or loop processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

// INLINE COMMENT: Conditional logic or loop processing
    if ($action === 'create_company') {
        $ragioneSociale = trim((string)($_POST['ragione_sociale'] ?? ''));
        $settore = trim((string)($_POST['settore'] ?? ''));
        $emailCommerciale = trim((string)($_POST['email_commerciale'] ?? ''));
        $statoRelazione = (string)($_POST['stato_relazione'] ?? 'prospect');

// ===== SEZIONE 8: LOGICA DI PROCESSO =====

        $validStati = ['prospect', 'in_negoziazione', 'attiva', 'chiusa'];
// INLINE COMMENT: Conditional logic or loop processing
        if ($ragioneSociale === '') {
            $errors[] = 'La ragione sociale e obbligatoria.';
        }
// INLINE COMMENT: Conditional logic or loop processing
        if ($emailCommerciale !== '' && !filter_var($emailCommerciale, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email commerciale non valida.';
        }
// INLINE COMMENT: Conditional logic or loop processing
        if (!in_array($statoRelazione, $validStati, true)) {
            $errors[] = 'Stato relazione non valido.';
        }

// INLINE COMMENT: Conditional logic or loop processing
        if (!$errors) {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
            $stmt = $pdo->prepare('INSERT INTO Aziende_tenant (ID_tenant, Ragione_sociale, Settore, Email_commerciale, Stato_relazione)
                VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $currentUserId,
                $ragioneSociale,
                $settore !== '' ? $settore : null,
                $emailCommerciale !== '' ? $emailCommerciale : null,

// ===== SEZIONE 9: LOGICA DI PROCESSO =====
                $statoRelazione,
            ]);
            $messages[] = 'Azienda creata correttamente.';
        }
    }

// INLINE COMMENT: Conditional logic or loop processing
    if ($action === 'create_sale') {
        $aziendaId = (int)($_POST['id_azienda'] ?? 0);
        $nomeDeal = trim((string)($_POST['nome_deal'] ?? ''));
        $valorePrevisto = (float)($_POST['valore_previsto'] ?? 0);
        $stato = (string)($_POST['stato'] ?? 'bozza');
        $dataChiusura = trim((string)($_POST['data_chiusura_prevista'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));

        $validStatiVendita = ['bozza', 'trattativa', 'vinta', 'persa'];
// INLINE COMMENT: Conditional logic or loop processing
        if ($aziendaId <= 0) {
            $errors[] = 'Seleziona un azienda valida.';
        }
// INLINE COMMENT: Conditional logic or loop processing
        if ($nomeDeal === '') {
            $errors[] = 'Il nome della trattativa e obbligatorio.';

// ===== SEZIONE 10: LOGICA DI PROCESSO =====
        }
// INLINE COMMENT: Conditional logic or loop processing
        if ($valorePrevisto <= 0) {
            $errors[] = 'Il valore previsto deve essere maggiore di 0.';
        }
// INLINE COMMENT: Conditional logic or loop processing
        if (!in_array($stato, $validStatiVendita, true)) {
            $errors[] = 'Stato vendita non valido.';
        }

// INLINE COMMENT: Conditional logic or loop processing
        if (!$errors) {
// INLINE COMMENT: Conditional logic or loop processing
            if ($isAdmin) {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                $check = $pdo->prepare('SELECT ID_azienda FROM Aziende_tenant WHERE ID_azienda = ? LIMIT 1');
                $check->execute([$aziendaId]);
            } else {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                $check = $pdo->prepare('SELECT ID_azienda FROM Aziende_tenant WHERE ID_azienda = ? AND ID_tenant = ? LIMIT 1');
                $check->execute([$aziendaId, $currentUserId]);
            }

// INLINE COMMENT: Conditional logic or loop processing
            if (!$check->fetch()) {
                $errors[] = 'Azienda non trovata o non autorizzata.';
            } else {

// ===== SEZIONE 11: LOGICA DI PROCESSO =====

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                $stmt = $pdo->prepare('INSERT INTO Vendite_tenant
                    (ID_tenant, ID_azienda, Nome_deal, Valore_previsto, Stato, Data_chiusura_prevista, Note)
                    VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $currentUserId,
                    $aziendaId,
                    $nomeDeal,
                    $valorePrevisto,
                    $stato,
                    $dataChiusura !== '' ? $dataChiusura : null,
                    $note !== '' ? $note : null,
                ]);
                $messages[] = 'Trattativa registrata con successo.';
            }
        }
    }

// INLINE COMMENT: Conditional logic or loop processing
    if ($action === 'update_sale_status') {
        $venditaId = (int)($_POST['id_vendita'] ?? 0);
        $stato = (string)($_POST['stato'] ?? 'bozza');

// ===== SEZIONE 12: LOGICA DI PROCESSO =====
        $validStatiVendita = ['bozza', 'trattativa', 'vinta', 'persa'];

// INLINE COMMENT: Conditional logic or loop processing
        if ($venditaId <= 0 || !in_array($stato, $validStatiVendita, true)) {
            $errors[] = 'Aggiornamento stato non valido.';
        } else {
// INLINE COMMENT: Conditional logic or loop processing
            if ($isAdmin) {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                $stmt = $pdo->prepare('UPDATE Vendite_tenant SET Stato = ? WHERE ID_vendita = ?');
                $stmt->execute([$stato, $venditaId]);
            } else {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                $stmt = $pdo->prepare('UPDATE Vendite_tenant SET Stato = ? WHERE ID_vendita = ? AND ID_tenant = ?');
                $stmt->execute([$stato, $venditaId, $currentUserId]);
            }

// INLINE COMMENT: Conditional logic or loop processing
            if ($stmt->rowCount() > 0) {
                $messages[] = 'Stato trattativa aggiornato.';
            } else {
                $errors[] = 'Nessuna trattativa aggiornata (controlla i permessi).';
            }
        }
    }

// ===== SEZIONE 13: LOGICA DI PROCESSO =====
}

$where = $isAdmin ? '' : ' WHERE ID_tenant = :tenant_id ';

$kpiSql = 'SELECT
    COUNT(*) AS totale_vendite,
    COALESCE(SUM(Valore_previsto), 0) AS pipeline_totale,
    COALESCE(SUM(CASE WHEN Stato = "vinta" THEN Valore_previsto ELSE 0 END), 0) AS valore_vinto,
    COALESCE(SUM(CASE WHEN Stato = "trattativa" THEN 1 ELSE 0 END), 0) AS trattative_aperte
FROM Vendite_tenant' . $where;
$kpiStmt = $pdo->prepare($kpiSql);
// INLINE COMMENT: Conditional logic or loop processing
if (!$isAdmin) {
    $kpiStmt->bindValue(':tenant_id', $currentUserId, PDO::PARAM_INT);
}
$kpiStmt->execute();
$kpi = $kpiStmt->fetch() ?: [
    'totale_vendite' => 0,
    'pipeline_totale' => 0,
    'valore_vinto' => 0,
    'trattative_aperte' => 0,

// ===== SEZIONE 14: LOGICA DI PROCESSO =====
];


/* BLOCK COMMENT: SQL Query execution to interact with database records */
$aziendeSql = 'SELECT ID_azienda, Ragione_sociale, Settore, Stato_relazione, Email_commerciale
FROM Aziende_tenant' . $where . ' ORDER BY Data_creazione DESC';
$aziendeStmt = $pdo->prepare($aziendeSql);
// INLINE COMMENT: Conditional logic or loop processing
if (!$isAdmin) {
    $aziendeStmt->bindValue(':tenant_id', $currentUserId, PDO::PARAM_INT);
}
$aziendeStmt->execute();
$aziende = $aziendeStmt->fetchAll();


/* BLOCK COMMENT: SQL Query execution to interact with database records */
$venditeSql = 'SELECT vt.ID_vendita, vt.Nome_deal, vt.Valore_previsto, vt.Stato, vt.Data_chiusura_prevista,
    a.Ragione_sociale
FROM Vendite_tenant vt
JOIN Aziende_tenant a ON a.ID_azienda = vt.ID_azienda'
. ($isAdmin ? '' : ' WHERE vt.ID_tenant = :tenant_id')
. ' ORDER BY vt.Data_creazione DESC';
$venditeStmt = $pdo->prepare($venditeSql);
// INLINE COMMENT: Conditional logic or loop processing
if (!$isAdmin) {
    $venditeStmt->bindValue(':tenant_id', $currentUserId, PDO::PARAM_INT);

// ===== SEZIONE 15: LOGICA DI PROCESSO =====
}
$venditeStmt->execute();
$vendite = $venditeStmt->fetchAll();

$roleLabel = empty($roleNames) ? 'nessun ruolo' : implode(', ', $roleNames);
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Tenant</title>
    <style>
        :root {
            --bg: #f2f7f2;
            --card: #ffffff;
            --line: #dce6dc;
            --ink: #0f172a;
            --muted: #516173;
            --brand: #136f63;

<?php // ===== SEZIONE 16: LOGICA DI PROCESSO ===== ?>
            --brand-soft: #d8f0eb;
            --warn: #b45309;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, sans-serif;
            background: linear-gradient(180deg, #edf6ef 0%, #f7fbf7 100%);
            color: var(--ink);
        }
        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 18px 44px;
        }
        .top {
            display: flex;
            justify-content: space-between;
            gap: 14px;

<?php // ===== SEZIONE 17: LOGICA DI PROCESSO ===== ?>
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 18px;
        }
        .title h1 {
            margin: 0;
            font-size: 30px;
        }
        .title p {
            margin: 6px 0 0;
            color: var(--muted);
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            border: 1px solid transparent;
            border-radius: 10px;

<?php // ===== SEZIONE 18: LOGICA DI PROCESSO ===== ?>
            padding: 10px 14px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
        }
        .btn-main { background: var(--brand); color: #fff; }
        .btn-ghost { background: #fff; color: var(--ink); border-color: var(--line); }

        .msg, .err {
            border-radius: 10px;
            padding: 10px 12px;
            margin: 10px 0;
            font-weight: 600;
        }
        .msg { background: #ddf7e8; color: #165c2d; border: 1px solid #9adeba; }
        .err { background: #fde8e8; color: #8c1d1d; border: 1px solid #f5b9b9; }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));

<?php // ===== SEZIONE 19: LOGICA DI PROCESSO ===== ?>
            gap: 12px;
            margin: 16px 0 20px;
        }
        .kpi {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }
        .kpi .label { color: var(--muted); font-size: 13px; }
        .kpi .value { font-size: 26px; font-weight: 800; margin-top: 6px; }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }
        .card {
            background: var(--card);

<?php // ===== SEZIONE 20: LOGICA DI PROCESSO ===== ?>
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
        }
        .card h2 { margin: 0 0 10px; font-size: 20px; }
        .sub { margin-top: -3px; color: var(--muted); font-size: 14px; }

        label {
            display: block;
            margin-top: 10px;
            font-weight: 700;
            font-size: 14px;
        }
        input, select, textarea {
            width: 100%;
            margin-top: 6px;
            padding: 10px;
            border: 1px solid #cfd8cf;
            border-radius: 8px;
            font-family: inherit;

<?php // ===== SEZIONE 21: LOGICA DI PROCESSO ===== ?>
            background: #fff;
        }
        textarea { min-height: 80px; resize: vertical; }
        .form-btn {
            margin-top: 12px;
            border: none;
            border-radius: 8px;
            background: var(--brand);
            color: #fff;
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {

<?php // ===== SEZIONE 22: LOGICA DI PROCESSO ===== ?>
            border-bottom: 1px solid #e6ece6;
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
        }
        th { color: #334155; background: #f6faf6; }
        .stato {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 8px;
            background: var(--brand-soft);
            color: #0d4f46;
            font-size: 12px;
            font-weight: 700;
        }
        .small-form {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;

<?php // ===== SEZIONE 23: LOGICA DI PROCESSO ===== ?>
        }
        .small-form select {
            max-width: 150px;
            margin: 0;
            padding: 6px;
        }
        .small-form button {
            border: none;
            background: #0f766e;
            color: #fff;
            border-radius: 6px;
            padding: 6px 8px;
            cursor: pointer;
            font-weight: 700;
        }

        .foot-note {
            margin-top: 14px;
            color: var(--muted);
            font-size: 13px;

<?php // ===== SEZIONE 24: LOGICA DI PROCESSO ===== ?>
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div class="title">
            <h1>Dashboard Tenant</h1>
            <p>Gestione commerciale del sito: aziende, trattative, pipeline e stato vendite.</p>
            <p class="sub">Ruoli attivi: <strong><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></strong></p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="/SITO/BPIC/home.php">Home</a>
            <a class="btn btn-ghost" href="/SITO/BPIC/mockup_viste.php">Viste</a>

// ===== SEZIONE 25: LOGICA DI PROCESSO =====
            <a class="btn btn-main" href="/SITO/BPIC/logout.php">Logout</a>
        </div>
    </div>

// INLINE COMMENT: Conditional logic or loop processing
    <?php foreach ($messages as $message): ?>
        <div class="msg"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

// INLINE COMMENT: Conditional logic or loop processing
    <?php foreach ($errors as $error): ?>
        <div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <section class="kpi-grid">
        <article class="kpi">
            <div class="label">Totale trattative</div>
            <div class="value"><?= (int)$kpi['totale_vendite'] ?></div>
        </article>
        <article class="kpi">
            <div class="label">Pipeline totale prevista</div>
            <div class="value">€ <?= number_format((float)$kpi['pipeline_totale'], 2, ',', '.') ?></div>

// ===== SEZIONE 26: LOGICA DI PROCESSO =====
        </article>
        <article class="kpi">
            <div class="label">Valore vinto</div>
            <div class="value">€ <?= number_format((float)$kpi['valore_vinto'], 2, ',', '.') ?></div>
        </article>
        <article class="kpi">
            <div class="label">Trattative aperte</div>
            <div class="value"><?= (int)$kpi['trattative_aperte'] ?></div>
        </article>
    </section>

    <section class="grid">
        <article class="card">
            <h2>Nuova azienda</h2>
            <p class="sub">Crea e classifica i prospect o clienti della tua area tenant.</p>
            <form method="post">
                <input type="hidden" name="action" value="create_company">

                <label>Ragione sociale</label>
                <input type="text" name="ragione_sociale" required>

// ===== SEZIONE 27: LOGICA DI PROCESSO =====

                <label>Settore</label>
                <input type="text" name="settore" placeholder="Es. e-commerce, retail, food, tecnologia">

                <label>Email commerciale</label>
                <input type="email" name="email_commerciale" placeholder="sales@azienda.it">

                <label>Stato relazione</label>

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                <select name="stato_relazione">
                    <option value="prospect">prospect</option>
                    <option value="in_negoziazione">in_negoziazione</option>
                    <option value="attiva">attiva</option>
                    <option value="chiusa">chiusa</option>
                </select>

                <button class="form-btn" type="submit">Salva azienda</button>
            </form>
        </article>

        <article class="card">

// ===== SEZIONE 28: LOGICA DI PROCESSO =====
            <h2>Nuova trattativa</h2>
            <p class="sub">Aggiungi deal e popola la pipeline commerciale.</p>
            <form method="post">
                <input type="hidden" name="action" value="create_sale">

                <label>Azienda</label>

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                <select name="id_azienda" required>
                    <option value="">Seleziona...</option>
// INLINE COMMENT: Conditional logic or loop processing
                    <?php foreach ($aziende as $azienda): ?>
                        <option value="<?= (int)$azienda['ID_azienda'] ?>">
                            <?= htmlspecialchars((string)$azienda['Ragione_sociale'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Nome deal</label>
                <input type="text" name="nome_deal" required>

                <label>Valore previsto (EUR)</label>
                <input type="number" name="valore_previsto" min="1" step="0.01" required>

// ===== SEZIONE 29: LOGICA DI PROCESSO =====

                <label>Stato</label>

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                <select name="stato">
                    <option value="bozza">bozza</option>
                    <option value="trattativa">trattativa</option>
                    <option value="vinta">vinta</option>
                    <option value="persa">persa</option>
                </select>

                <label>Data chiusura prevista</label>
                <input type="date" name="data_chiusura_prevista">

                <label>Note</label>
                <textarea name="note" placeholder="Obiezioni cliente, next step, priorita..."></textarea>

                <button class="form-btn" type="submit">Salva trattativa</button>
            </form>
        </article>
    </section>


// ===== SEZIONE 30: LOGICA DI PROCESSO =====
    <section class="card" style="margin-bottom: 14px;">
        <h2>Aziende gestite</h2>
        <table>
            <thead>
            <tr>
                <th>Ragione sociale</th>
                <th>Settore</th>
                <th>Email commerciale</th>
                <th>Relazione</th>
            </tr>
            </thead>
            <tbody>
// INLINE COMMENT: Conditional logic or loop processing
            <?php if (!$aziende): ?>
                <tr><td colspan="4">Nessuna azienda registrata.</td></tr>
            <?php else: ?>
// INLINE COMMENT: Conditional logic or loop processing
                <?php foreach ($aziende as $azienda): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$azienda['Ragione_sociale'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($azienda['Settore'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($azienda['Email_commerciale'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>

// ===== SEZIONE 31: LOGICA DI PROCESSO =====
                        <td><span class="stato"><?= htmlspecialchars((string)$azienda['Stato_relazione'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Pipeline vendite</h2>
        <table>
            <thead>
            <tr>
                <th>Deal</th>
                <th>Azienda</th>
                <th>Valore</th>
                <th>Stato</th>
                <th>Chiusura prevista</th>
                <th>Azione rapida</th>
            </tr>

<?php // ===== SEZIONE 32: LOGICA DI PROCESSO ===== ?>
            </thead>
            <tbody>
// INLINE COMMENT: Conditional logic or loop processing
            <?php if (!$vendite): ?>
                <tr><td colspan="6">Nessuna trattativa disponibile.</td></tr>
            <?php else: ?>
// INLINE COMMENT: Conditional logic or loop processing
                <?php foreach ($vendite as $vendita): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$vendita['Nome_deal'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$vendita['Ragione_sociale'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>€ <?= number_format((float)$vendita['Valore_previsto'], 2, ',', '.') ?></td>
                        <td><span class="stato"><?= htmlspecialchars((string)$vendita['Stato'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars((string)($vendita['Data_chiusura_prevista'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form class="small-form" method="post">
                                <input type="hidden" name="action" value="update_sale_status">
                                <input type="hidden" name="id_vendita" value="<?= (int)$vendita['ID_vendita'] ?>">

/* BLOCK COMMENT: SQL Query execution to interact with database records */
                                <select name="stato">
                                    <option value="bozza">bozza</option>
                                    <option value="trattativa">trattativa</option>
                                    <option value="vinta">vinta</option>

// ===== SEZIONE 33: LOGICA DI PROCESSO =====
                                    <option value="persa">persa</option>
                                </select>
                                <button type="submit">Aggiorna</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <p class="foot-note">
            Nota: i tenant visualizzano e modificano solo i propri record commerciali.
            Gli admin possono monitorare l'intera pipeline.
        </p>
    </section>
</div>
<script src="/SITO/BPIC/auth/auto_logout_on_close.js"></script>
</body>
</html>