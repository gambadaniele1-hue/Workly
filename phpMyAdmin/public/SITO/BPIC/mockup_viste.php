<?php
/**
 * File: mockup_viste.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /SITO/BPIC/login.php');
    exit;
}

require_once __DIR__ . '/database.php';

/* =====================================================================
 * MAPPA ROTTE BPIC
 * =====================================================================
 *
 * AUTH  (/SITO/BPIC/)
 * ─────────────────────────────────────────────────────────────────────
 * GET|POST  /SITO/BPIC/login.php                Login form + JWT generation
 * GET|POST  /SITO/BPIC/register.php             Registrazione nuovo utente
 * GET       /SITO/BPIC/logout.php               Distrugge sessione → redirect login
 * POST      /SITO/BPIC/validate_token.php       Valida JWT da form e avvia sessione

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
 *
 * APP PROTETTA  (/SITO/BPIC/) — richiede sessione PHP autenticata
 * ─────────────────────────────────────────────────────────────────────
 * GET|POST  /SITO/BPIC/dashboard.php            Dashboard principale (ruoli/permessi)
 * GET|POST  /SITO/BPIC/Impostazioni_contratto.php  Imposta contratto utente
 * GET|POST  /SITO/BPIC/Profilo_contratto.php    Profilo contratto utente
 * GET       /SITO/BPIC/mockup_viste.php         Questa pagina (mockup viste DB)
 *
 * REST API JWT  (/SITO/BPIC/api/) — richiede Bearer JWT valido
 * ─────────────────────────────────────────────────────────────────────
 * POST      /SITO/BPIC/api/token.php            Ottieni JWT (email + password)
 * POST      /SITO/BPIC/api/verify_token.php     Verifica JWT → ruoli/permessi
 * GET|POST  /SITO/BPIC/api/permissions.php      Permessi dell'utente autenticato
 * GET       /SITO/BPIC/api/generate_token.php   Genera JWT da sessione attiva
 * ===================================================================== */

$roles = $_SESSION['roles'] ?? null;
$permissions = $_SESSION['permissions'] ?? null;

if (!$roles || !$permissions) {

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
    $email = $_SESSION['email'] ?? null;
    if (is_string($email) && $email !== '') {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
        // Usa il role_id in sessione invece della tabella Utente_Ruolo
        $roleId = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
        $roles = [];
        $permissions = [];
        $roleMap = [];
        $permMap = [];
        if ($roleId > 0) {
            $stmt = $mysqli->prepare('SELECT r.ID_ruolo, r.Nome_ruolo, p.ID_privilegio, p.Nome_privilegio, p.Risorsa, p.Azione
                FROM Ruoli r
                JOIN Ruolo_Privilegio rp ON rp.ID_ruolo = r.ID_ruolo
                JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
                WHERE r.ID_ruolo = ?');
            if ($stmt) {
                $stmt->bind_param('i', $roleId);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $roleIdRow = (int)$row['ID_ruolo'];
                    if (!isset($roleMap[$roleIdRow])) {
                        $roleMap[$roleIdRow] = true;
                        $roles[] = ['id' => $roleIdRow, 'name' => $row['Nome_ruolo']];
                    }
                    $permId = (int)$row['ID_privilegio'];
                    if (!isset($permMap[$permId])) {
                        $permMap[$permId] = true;
                        $permissions[] = [
                            'id' => $permId,
                            'name' => $row['Nome_privilegio'],
                            'resource' => $row['Risorsa'],
                            'action' => $row['Azione'],
                        ];
                    }
                }

                $stmt->close();
            }
        }

            $roles = [];
            $permissions = [];
            $roleMap = [];
            $permMap = [];

            while ($row = $result->fetch_assoc()) {
                $roleId = (int)$row['ID_ruolo'];

// ===== SEZIONE 4: LOGICA DI PROCESSO =====
                if (!isset($roleMap[$roleId])) {
                    $roleMap[$roleId] = true;
                    $roles[] = ['id' => $roleId, 'name' => $row['Nome_ruolo']];
                }

                $permId = (int)$row['ID_privilegio'];
                if (!isset($permMap[$permId])) {
                    $permMap[$permId] = true;
                    $permissions[] = [
                        'id' => $permId,
                        'name' => $row['Nome_privilegio'],
                        'resource' => $row['Risorsa'],
                        'action' => $row['Azione'],
                    ];
                }
            }

            $stmt->close();
            $_SESSION['roles'] = $roles;
            $_SESSION['permissions'] = $permissions;

// ===== SEZIONE 5: LOGICA DI PROCESSO =====
        }
    }


$roles = is_array($roles) ? $roles : [];
$permissions = is_array($permissions) ? $permissions : [];
$roleNames = array_values(array_filter(array_map(static function ($role): string {
    return (string)($role['name'] ?? '');
}, $roles)));
$isAdmin = in_array('admin', $roleNames, true);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);


/**
 * Function: hasPermission
 * Parameters: array $roleNames, array $permissions, string $resource, string $action
 * Return: mixed
 * Description: Executes business logic for hasPermission.
 */
function hasPermission(array $roleNames, array $permissions, string $resource, string $action): bool
{
    if (in_array('admin', $roleNames, true)) {
        return true;
    }

    foreach ($permissions as $perm) {
        $permResource = (string)($perm['resource'] ?? '');

// ===== SEZIONE 6: LOGICA DI PROCESSO =====
        $permAction = (string)($perm['action'] ?? '');
        if ($permResource === $resource && ($permAction === $action || $permAction === 'ALL')) {
            return true;
        }
    }

    return false;
}


/**
 * Function: canSeeRouteByAccess
 * Parameters: string $accessTag, array $roleNames, array $permissions
 * Return: mixed
 * Description: Executes business logic for canSeeRouteByAccess.
 */
function canSeeRouteByAccess(string $accessTag, array $roleNames, array $permissions): bool
{
    if (in_array('admin', $roleNames, true)) {
        return true;
    }

    switch ($accessTag) {
        case 'admin':
            return hasPermission($roleNames, $permissions, 'utenti', 'ALL')
                || hasPermission($roleNames, $permissions, 'ruoli', 'ALL')
                || hasPermission($roleNames, $permissions, 'privilegi', 'ALL');

// ===== SEZIONE 7: LOGICA DI PROCESSO =====

        case 'non_abbonato+':
            return hasPermission($roleNames, $permissions, 'buste_paga', 'INSERT');

        case 'abbonato+':
            return hasPermission($roleNames, $permissions, 'pdf', 'SELECT')
                || hasPermission($roleNames, $permissions, 'email', 'INSERT')
                || hasPermission($roleNames, $permissions, 'archivio', 'SELECT')
                || hasPermission($roleNames, $permissions, 'confronto', 'SELECT');

        default:
            return true;
    }
}

$views = [
    [
        'name' => 'v_generazione_busta_paga',
        'title' => 'Generazione busta paga',
        'group' => 'Operativo',

// ===== SEZIONE 8: LOGICA DI PROCESSO =====
        'resource' => 'buste_paga',
        'action' => 'INSERT',
        'self_scope' => true,
    ],
    [
        'name' => 'v_download_pdf',
        'title' => 'Download PDF',
        'group' => 'Operativo',
        'resource' => 'pdf',
        'action' => 'SELECT',
        'self_scope' => true,
    ],
    [
        'name' => 'v_invio_pdf_email',
        'title' => 'Invio PDF email',
        'group' => 'Operativo',
        'resource' => 'email',
        'action' => 'INSERT',
        'self_scope' => true,
    ],

// ===== SEZIONE 9: LOGICA DI PROCESSO =====
    [
        'name' => 'v_archivio_buste_paga',
        'title' => 'Archivio buste paga',
        'group' => 'Operativo',
        'resource' => 'archivio',
        'action' => 'SELECT',
        'self_scope' => true,
    ],
    [
        'name' => 'v_confronto_buste_paga',
        'title' => 'Confronto buste paga',
        'group' => 'Operativo',
        'resource' => 'confronto',
        'action' => 'SELECT',
        'self_scope' => true,
    ],
    [
        'name' => 'v_gestione_utenti',
        'title' => 'Gestione utenti',
        'group' => 'Admin',

// ===== SEZIONE 10: LOGICA DI PROCESSO =====
        'resource' => 'utenti',
        'action' => 'ALL',
        'self_scope' => false,
    ],
    [
        'name' => 'v_gestione_ruoli',
        'title' => 'Gestione ruoli',
        'group' => 'Admin',
        'resource' => 'ruoli',
        'action' => 'ALL',
        'self_scope' => false,
    ],
    [
        'name' => 'v_gestione_privilegi',
        'title' => 'Gestione privilegi',
        'group' => 'Admin',
        'resource' => 'privilegi',
        'action' => 'ALL',
        'self_scope' => false,
    ],

// ===== SEZIONE 11: LOGICA DI PROCESSO =====
];

$views = array_values(array_filter($views, static function (array $view) use ($roleNames, $permissions): bool {
    return hasPermission($roleNames, $permissions, $view['resource'], $view['action']);
}));

$results = [];
$totalRows = 0;
$nonEmptyViews = 0;

foreach ($views as $view) {
    $viewName = $view['name'];
    $viewTitle = $view['title'];
    $viewGroup = $view['group'];
    $selfScope = (bool)($view['self_scope'] ?? false);
    $isRestrictedToUser = !$isAdmin && $selfScope;

    $count = 0;
    $rows = [];
    $columns = [];

// ===== SEZIONE 12: LOGICA DI PROCESSO =====
    $error = null;

    if ($isRestrictedToUser) {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
        $countStmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM {$viewName} WHERE ID_utente = ?");
        if ($countStmt) {
            $countStmt->bind_param('i', $currentUserId);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult ? $countResult->fetch_assoc() : null;
            $count = (int)($countRow['total'] ?? 0);
            if ($countResult) {
                $countResult->free();
            }
            $countStmt->close();
        } else {
            $error = $mysqli->error;
        }


/* BLOCK COMMENT: SQL Query execution to interact with database records */
        $rowsStmt = $mysqli->prepare("SELECT * FROM {$viewName} WHERE ID_utente = ? ORDER BY 1 DESC LIMIT 5");
        if ($rowsStmt) {

// ===== SEZIONE 13: LOGICA DI PROCESSO =====
            $rowsStmt->bind_param('i', $currentUserId);
            $rowsStmt->execute();
            $rowsResult = $rowsStmt->get_result();

            if ($rowsResult) {
                $fieldInfo = $rowsResult->fetch_fields();
                foreach ($fieldInfo as $field) {
                    $columns[] = $field->name;
                }

                while ($row = $rowsResult->fetch_assoc()) {
                    $rows[] = $row;
                }
                $rowsResult->free();
            } elseif ($error === null) {
                $error = $mysqli->error;
            }

            $rowsStmt->close();
        } elseif ($error === null) {

// ===== SEZIONE 14: LOGICA DI PROCESSO =====
            $error = $mysqli->error;
        }
    } else {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
        $countQuery = "SELECT COUNT(*) AS total FROM {$viewName}";
        $countResult = $mysqli->query($countQuery);

        if ($countResult) {
            $countRow = $countResult->fetch_assoc();
            $count = (int)($countRow['total'] ?? 0);
            $countResult->free();
        } else {
            $error = $mysqli->error;
        }


/* BLOCK COMMENT: SQL Query execution to interact with database records */
        $rowsQuery = "SELECT * FROM {$viewName} ORDER BY 1 DESC LIMIT 5";
        $rowsResult = $mysqli->query($rowsQuery);

        if ($rowsResult) {
            $fieldInfo = $rowsResult->fetch_fields();
            foreach ($fieldInfo as $field) {

// ===== SEZIONE 15: LOGICA DI PROCESSO =====
                $columns[] = $field->name;
            }

            while ($row = $rowsResult->fetch_assoc()) {
                $rows[] = $row;
            }
            $rowsResult->free();
        } elseif ($error === null) {
            $error = $mysqli->error;
        }
    }

    if ($count > 0) {
        $nonEmptyViews++;
    }
    $totalRows += $count;

    $results[] = [
        'name' => $viewName,
        'title' => $viewTitle,

// ===== SEZIONE 16: LOGICA DI PROCESSO =====
        'group' => $viewGroup,
        'count' => $count,
        'columns' => $columns,
        'rows' => $rows,
        'error' => $error
    ];
}


/**
 * Function: badgeClass
 * Parameters: string $group
 * Return: mixed
 * Description: Executes business logic for badgeClass.
 */
function badgeClass(string $group): string
{
    return $group === 'Admin' ? 'badge-admin' : 'badge-operativo';
}

$rolesLabel = empty($roleNames) ? 'nessun ruolo' : implode(', ', $roleNames);

/* =====================================================================
 * DATI TABELLA RIEPILOGO PERMESSI PER ROTTA
 * Struttura: [metodo, path, descrizione, ospite, registrato, abbonato, admin, accesso, privilegio]
 * Valori accesso: 'pubblica' | 'sessione' | 'JWT'
 * ===================================================================== */

// ===== SEZIONE 17: LOGICA DI PROCESSO =====
$routePermissions = [
    // --- Auth ---
    [
        'group'       => 'Auth',
        'group_bg'    => '#fff7ed',
        'group_bdr'   => '#fdba74',
        'group_dot'   => '#c06a2a',
        'routes'      => [
            ['GET|POST', 'login.php',           'Login + generazione JWT',       true,  true,  true,  true,  'pubblica', '—'],
            ['GET|POST', 'register.php',         'Registrazione nuovo utente',    true,  true,  true,  true,  'pubblica', '—'],
            ['GET',      'logout.php',           'Logout → redirect login',       false, true,  true,  true,  'sessione', '—'],
            ['POST',     'validate_token.php',   'Valida JWT da form → sessione', true,  true,  true,  true,  'pubblica', '—'],
        ],
    ],
    // --- App protetta ---
    [
        'group'       => 'App protetta',
        'group_bg'    => '#fef2f2',
        'group_bdr'   => '#fca5a5',
        'group_dot'   => '#d04040',

// ===== SEZIONE 18: LOGICA DI PROCESSO =====
        'routes'      => [
            ['GET|POST', 'dashboard.php',                 'Dashboard principale',         false, true,  true,  true,  'sessione', 'login'],
            ['GET|POST', 'Impostazioni_contratto.php',    'Imposta contratto utente',     false, true,  true,  true,  'sessione', 'buste_paga · INSERT'],
            ['GET|POST', 'Profilo_contratto.php',         'Profilo contratto utente',     false, true,  true,  true,  'sessione', 'login'],
            ['GET',      'mockup_viste.php',              'Mockup viste DB (questa pag)', false, true,  true,  true,  'sessione', 'login'],
        ],
    ],
    // --- REST API JWT ---
    [
        'group'       => 'REST API JWT',
        'group_bg'    => '#f0fdf4',
        'group_bdr'   => '#86efac',
        'group_dot'   => '#0b8f77',
        'routes'      => [
            ['POST',     'api/token.php',           'Ottieni JWT (email+password)',   true,  true,  true,  true,  'pubblica', '—'],
            ['POST',     'api/verify_token.php',    'Verifica JWT → ruoli/permessi',  false, true,  true,  true,  'JWT',      'Bearer JWT valido'],
            ['GET|POST', 'api/permissions.php',     'Permessi utente autenticato',    false, true,  true,  true,  'JWT',      'Bearer JWT valido'],
            ['GET',      'api/generate_token.php',  'Genera JWT da sessione attiva',  false, true,  true,  true,  'sessione', 'sessione attiva'],
        ],
    ],

// ===== SEZIONE 19: LOGICA DI PROCESSO =====
];
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BPIC Mockup viste</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-0: #f5f2ea;
            --bg-1: #fffdf8;
            --ink: #1c1a15;
            --muted: #676055;
            --card: #fff9ef;
            --line: #e7decd;
            --accent: #0b8f77;

            --accent-soft: #d6f4ec;
            --admin: #c06a2a;
            --admin-soft: #ffe7d5;
            --shadow: 0 10px 40px rgba(61, 50, 28, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 5% 10%, #efe4d4 0%, transparent 35%),
                radial-gradient(circle at 90% 0%, #d4ece8 0%, transparent 35%),
                linear-gradient(180deg, var(--bg-0), var(--bg-1));
            min-height: 100vh;
        }

        .wrap {

            max-width: 1180px;
            margin: 0 auto;
            padding: 28px 20px 72px;
        }

        <?php /* ── Hero ── */ ?>
        .hero {
            background: rgba(255, 249, 239, 0.8);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 28px;
            position: relative;
            overflow: hidden;
            animation: reveal 500ms ease-out;
        }

        .hero::after {
            content: '';
            position: absolute;

            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: linear-gradient(160deg, rgba(11, 143, 119, 0.2), rgba(192, 106, 42, 0.15));
            right: -60px;
            top: -80px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: clamp(28px, 4vw, 44px);
            letter-spacing: -0.02em;
        }

        .subtitle {
            margin: 0;
            color: var(--muted);
            max-width: 760px;
        }


        .session-note {
            margin-top: 10px;
            font-size: 14px;
            color: #4e473c;
        }

        .kpi-grid {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .kpi {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 12px;
        }


        .kpi label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .kpi strong {
            font-size: 28px;
            line-height: 1.15;
        }

        .top-link {
            display: inline-block;
            margin-top: 18px;
            color: #0a6b5a;
            text-decoration: none;
            border-bottom: 1px solid currentColor;
        }


        <?php /* ── Generic panel ── */ ?>
        .sections {
            margin-top: 22px;
            display: grid;
            gap: 14px;
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: var(--shadow);
            padding: 16px;
            animation: slideUp 550ms ease both;
        }

        .panel-head {
            display: flex;
            justify-content: space-between;

            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .panel-title {
            margin: 0;
            font-size: 20px;
        }

        .panel-meta {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 13px;
            font-family: 'IBM Plex Mono', monospace;
            color: var(--muted);
            flex-wrap: wrap;
        }


        .badge {
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border: 1px solid transparent;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-operativo {
            background: var(--accent-soft);
            color: #0a6b5a;
            border-color: #9dd7cb;
        }

        .badge-admin {
            background: var(--admin-soft);
            color: #8f4b1b;
            border-color: #f0c39d;

        }

        .badge-count {
            background: #efe8db;
            color: #4e473c;
            border-color: #d6c9b1;
        }

        .empty {
            margin-top: 12px;
            border: 1px dashed var(--line);
            border-radius: 12px;
            padding: 14px;
            color: var(--muted);
            background: #fffcf7;
        }

        .table-wrap {
            margin-top: 12px;
            overflow-x: auto;

            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #f0e7d7;
            text-align: left;
            vertical-align: middle;
        }

        th {

            background: #f6efe3;
            color: #5c513f;
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-family: 'IBM Plex Mono', monospace;
        }

        tr:last-child td { border-bottom: none; }

        code {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            background: #f7f0e5;
            color: #5c513f;
            padding: 2px 6px;
            border-radius: 6px;

        }

        .error {
            margin-top: 10px;
            color: #8a1f1f;
            background: #feeaea;
            border: 1px solid #f6bebe;
            border-radius: 10px;
            padding: 10px;
            font-size: 13px;
        }

        <?php /* ── Riepilogo permessi – stili specifici ── */ ?>
        .perm-section {
            margin-top: 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }


        .perm-section-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            color: var(--ink);
            border-bottom: 1px solid var(--line);
        }

        .perm-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }


        .perm-table-wrap {
            overflow-x: auto;
            background: #fffcf7;
        }

        .perm-table {
            min-width: 820px;
            border-collapse: collapse;
            font-size: 13px;
            width: 100%;
        }

        .perm-table th {
            background: #f6efe3;
            color: #5c513f;
            padding: 8px 10px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;

            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .perm-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0e8d8;
            vertical-align: middle;
        }

        .perm-table tr:last-child td { border-bottom: none; }
        .perm-table tr:hover td { background: #fdf6e8; }

        .perm-table .col-role { text-align: center; width: 80px; }

        <?php /* Method pills */ ?>

        .m-pill {
            display: inline-block;
            border-radius: 999px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 9px;
            white-space: nowrap;
            letter-spacing: 0.03em;
        }
        .m-get    { background: #dcfce7; color: #166534; border: 1px solid #9fdfb8; }
        .m-post   { background: #dbeafe; color: #1e3a8a; border: 1px solid #93c5fd; }
        .m-both   { background: #ede9fe; color: #4c1d95; border: 1px solid #c4b5fd; }

        <?php /* Access badges */ ?>
        .a-badge {
            display: inline-block;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;

            padding: 3px 9px;
            white-space: nowrap;
        }
        .a-pub  { background: #dcfce7; color: #166534; border: 1px solid #9fdfb8; }
        .a-sess { background: #fef9c3; color: #854d0e; border: 1px solid #fde68a; }
        .a-jwt  { background: #dbeafe; color: #1e3a8a; border: 1px solid #93c5fd; }

        <?php /* Route code */ ?>
        code.route {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            color: #3b5bdb;
            background: #eef2ff;
            padding: 2px 7px;
            border-radius: 6px;
            white-space: nowrap;
        }

        <?php /* Privilege cell */ ?>
        .priv-cell {

            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            color: #5c513f;
            background: #f7f0e5;
            padding: 2px 7px;
            border-radius: 6px;
            white-space: nowrap;
        }
        .priv-none { color: #9ca3af; font-style: italic; }

        <?php /* Check/cross icons */ ?>
        .ico-ok   { color: #16a34a; font-size: 15px; font-weight: 700; }
        .ico-no   { color: #dc2626; font-size: 15px; font-weight: 700; }

        <?php /* Legend */ ?>
        .perm-legend {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            padding: 10px 4px 2px;

            font-size: 12px;
            color: var(--muted);
        }
        .perm-legend-item { display: flex; align-items: center; gap: 5px; }

        <?php /* Animations */ ?>
        @keyframes reveal {
            from { transform: translateY(10px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(14px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        @media (max-width: 700px) {
            .wrap  { padding: 16px 12px 42px; }
            .hero  { padding: 18px; border-radius: 18px; }
            .panel { padding: 12px; }
        }

    </style>
</head>
<body>
<main class="wrap">

        <?php /* =========================================================
            HERO
            ========================================================= */ ?>
    <section class="hero">
        <h1>Mockup applicazione BPIC</h1>
        <p class="subtitle">
            Layout costruito leggendo i risultati reali delle viste nel database.
            Ogni blocco rappresenta una funzione del sistema con record attuali e anteprima dei dati (max 5 righe).
        </p>
        <p class="session-note">
            Visibilità calcolata in base ai tuoi ruoli: <strong><?= htmlspecialchars($rolesLabel); ?></strong>
        </p>

        <div class="kpi-grid">
            <article class="kpi">
                <label>Viste monitorate</label>
                <strong><?= count($results); ?></strong>
            </article>
            <article class="kpi">
                <label>Viste con dati</label>
                <strong><?= $nonEmptyViews; ?></strong>
            </article>
            <article class="kpi">
                <label>Righe totali</label>
                <strong><?= $totalRows; ?></strong>
            </article>
            <article class="kpi">
                <label>Data mockup</label>
                <strong><?= date('d/m/Y'); ?></strong>
            </article>
        </div>

        <a class="top-link" href="/SITO/BPIC/dashboard.php">Torna alla dashboard</a>
    </section>
        <?php /* =========================================================
            RIEPILOGO PERMESSI PER ROTTA
            ========================================================= */ ?>
    <section class="sections" aria-label="Riepilogo permessi per rotta" style="margin-top:22px;">
        <article class="panel" style="animation-delay:0ms;">
            <header class="panel-head">
                <div>
                    <h2 class="panel-title">Riepilogo permessi per rotta</h2>
                    <div class="panel-meta"><code>ruoli: ospite · registrato · abbonato · admin</code></div>
                </div>
                <span class="badge badge-count">tutte le rotte</span>
            </header>

            <?php foreach ($routePermissions as $rg): ?>
                <div class="perm-section">
                    <div class="perm-section-head" style="background:<?= $rg['group_bg']; ?>; border-bottom-color:<?= $rg['group_bdr']; ?>;">
                        <span class="perm-dot" style="background:<?= $rg['group_dot']; ?>;"></span>
                        <?= htmlspecialchars($rg['group']); ?>
                        &nbsp;—&nbsp;/SITO/BPIC/
                        <?php if ($rg['group'] === 'App protetta'): ?>

                            <span style="font-weight:400;opacity:.65;">(sessione PHP richiesta)</span>
                        <?php elseif ($rg['group'] === 'REST API JWT'): ?>
                            <span style="font-weight:400;opacity:.65;">(Bearer JWT richiesto)</span>
                        <?php endif; ?>
                    </div>
                    <div class="perm-table-wrap">
                        <table class="perm-table">
                            <thead>
                                <tr>
                                    <th>Rotta</th>
                                    <th>Metodo</th>
                                    <th class="col-role">Ospite</th>
                                    <th class="col-role">Registrato</th>
                                    <th class="col-role">Abbonato</th>
                                    <th class="col-role">Admin</th>
                                    <th>Accesso</th>
                                    <th>Privilegio richiesto</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php foreach ($rg['routes'] as $r):
                                    [$method, $path, $desc, $ospite, $registrato, $abbonato, $admin, $accesso, $privilegio] = $r;

                                    $methodClass = match($method) {
                                        'GET'      => 'm-get',
                                        'POST'     => 'm-post',
                                        default    => 'm-both',
                                    };
                                    $accessClass = match($accesso) {
                                        'pubblica' => 'a-pub',
                                        'JWT'      => 'a-jwt',
                                        default    => 'a-sess',
                                    };

                                    $ico = static fn(bool $v): string => $v
                                        ? '<span class="ico-ok">✓</span>'
                                        : '<span class="ico-no">✗</span>';
                                ?>
                                <tr>
                                    <td>

                                        <code class="route"><?= htmlspecialchars($path); ?></code><br>
                                        <span style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($desc); ?></span>
                                    </td>
                                    <td><span class="m-pill <?= $methodClass; ?>"><?= htmlspecialchars($method); ?></span></td>
                                    <td class="col-role"><?= $ico($ospite); ?></td>
                                    <td class="col-role"><?= $ico($registrato); ?></td>
                                    <td class="col-role"><?= $ico($abbonato); ?></td>
                                    <td class="col-role"><?= $ico($admin); ?></td>
                                    <td><span class="a-badge <?= $accessClass; ?>"><?= htmlspecialchars($accesso); ?></span></td>
                                    <td>
                                        <?php if ($privilegio === '—'): ?>
                                            <span class="priv-none">—</span>
                                        <?php else: ?>
                                            <span class="priv-cell"><?= htmlspecialchars($privilegio); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="perm-legend">
                <span class="perm-legend-item"><span class="ico-ok" style="font-size:13px;">✓</span> Accesso consentito</span>
                <span class="perm-legend-item"><span class="ico-no" style="font-size:13px;">✗</span> Accesso negato</span>
                <span class="perm-legend-item"><span class="a-badge a-pub" style="font-size:11px;padding:2px 7px;">pubblica</span> Nessuna autenticazione</span>
                <span class="perm-legend-item"><span class="a-badge a-sess" style="font-size:11px;padding:2px 7px;">sessione</span> Sessione PHP attiva</span>
                <span class="perm-legend-item"><span class="a-badge a-jwt" style="font-size:11px;padding:2px 7px;">JWT</span> Bearer token valido</span>
            </div>

        </article>
    </section>

        <?php /* =========================================================
            MAPPA ROTTE (dettaglio gruppi)
            ========================================================= */ ?>
    <section class="sections" aria-label="Mappa rotte">
        <article class="panel" style="animation-delay:35ms;">

            <header class="panel-head">
                <div>
                    <h2 class="panel-title">Mappa rotte BPIC</h2>
                    <div class="panel-meta"><code>solo sezioni BPIC</code></div>
                </div>
                <span class="badge badge-count">rotte BPIC</span>
            </header>

            <?php
            $routeGroups = [
                [
                    'label' => 'Auth &nbsp;/SITO/BPIC/',
                    'color' => '#fff7ed',
                    'border' => '#fdba74',
                    'routes' => [
                        ['GET|POST', '/SITO/BPIC/login.php',          'Login + generazione JWT',          'pubblica'],
                        ['GET|POST', '/SITO/BPIC/register.php',       'Registrazione nuovo utente',       'pubblica'],
                        ['GET',      '/SITO/BPIC/logout.php',         'Logout → redirect login',          'sessione'],
                        ['POST',     '/SITO/BPIC/validate_token.php', 'Valida JWT da form → sessione',    'pubblica'],
                    ],
                ],
                [
                    'label' => 'App protetta &nbsp;/SITO/BPIC/ (sessione PHP richiesta)',
                    'color' => '#fef2f2',
                    'border' => '#fca5a5',
                    'routes' => [
                        ['GET|POST', '/SITO/BPIC/dashboard.php',               'Dashboard principale',         'sessione'],
                        ['GET|POST', '/SITO/BPIC/Impostazioni_contratto.php',  'Imposta contratto utente',     'sessione'],
                        ['GET|POST', '/SITO/BPIC/Profilo_contratto.php',       'Profilo contratto utente',     'sessione'],
                        ['GET',      '/SITO/BPIC/mockup_viste.php',            'Mockup viste DB (questa pag)', 'sessione'],
                    ],
                ],
                [
                    'label' => 'REST API JWT &nbsp;/SITO/BPIC/api/ (Bearer JWT richiesto)',
                    'color' => '#f0fdf4',
                    'border' => '#86efac',
                    'routes' => [
                        ['POST',     '/SITO/BPIC/api/token.php',          'Ottieni JWT (email+password)',    'pubblica'],
                        ['POST',     '/SITO/BPIC/api/verify_token.php',   'Verifica JWT → ruoli/permessi',   'JWT'],
                        ['GET|POST', '/SITO/BPIC/api/permissions.php',    'Permessi utente autenticato',     'JWT'],
                        ['GET',      '/SITO/BPIC/api/generate_token.php', 'Genera JWT da sessione attiva',   'sessione'],
                    ],
                ],
            ];

            $methodColor = [
                'GET'      => ['bg' => '#dcfce7', 'col' => '#166534'],
                'POST'     => ['bg' => '#dbeafe', 'col' => '#1e40af'],
                'PUT'      => ['bg' => '#fef9c3', 'col' => '#854d0e'],
                'DELETE'   => ['bg' => '#fee2e2', 'col' => '#991b1b'],
                'GET|POST' => ['bg' => '#ede9fe', 'col' => '#4c1d95'],
            ];
            $accessColor = [
                'pubblica'       => ['bg' => '#dcfce7', 'col' => '#166534'],
                'sessione'       => ['bg' => '#fef9c3', 'col' => '#854d0e'],
                'JWT'            => ['bg' => '#dbeafe', 'col' => '#1e40af'],
                'non_abbonato+'  => ['bg' => '#f3e8ff', 'col' => '#6b21a8'],
                'abbonato+'      => ['bg' => '#ffedd5', 'col' => '#9a3412'],
                'admin'          => ['bg' => '#fee2e2', 'col' => '#991b1b'],
                'nessuna auth'   => ['bg' => '#f1f5f9', 'col' => '#475569'],
            ];

            $filteredRouteGroups = [];
            foreach ($routeGroups as $group) {
                $visibleRoutes = [];
                foreach ($group['routes'] as $route) {
                    if (canSeeRouteByAccess((string)$route[3], $roleNames, $permissions)) {
                        $visibleRoutes[] = $route;
                    }
                }
                if (!empty($visibleRoutes)) {
                    $group['routes'] = $visibleRoutes;
                    $filteredRouteGroups[] = $group;
                }
            }
            ?>

            <?php foreach ($filteredRouteGroups as $gi => $group): ?>
                <div style="margin-top:<?= $gi === 0 ? '16' : '14'; ?>px; border:1px solid <?= $group['border']; ?>; border-radius:12px; overflow:hidden;">
                    <div style="background:<?= $group['color']; ?>; padding:8px 14px; font-size:13px; font-weight:700; font-family:'IBM Plex Mono',monospace; color:#1c1a15;">
                        <?= $group['label']; ?>
                    </div>
                    <div style="overflow-x:auto; background:#fff;">
                        <table style="min-width:640px;">
                            <thead>
                                <tr>
                                    <th style="width:110px;">Metodo</th>
                                    <th>Path / use_case</th>
                                    <th>Descrizione</th>
                                    <th style="width:130px;">Accesso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group['routes'] as $r): ?>
                                    <?php
                                    $mc = $methodColor[$r[0]] ?? ['bg' => '#f1f5f9', 'col' => '#475569'];
                                    $ac = $accessColor[$r[3]] ?? ['bg' => '#f1f5f9', 'col' => '#475569'];
                                    ?>
                                    <tr>
                                        <td>

                                            <span style="display:inline-block;background:<?= $mc['bg']; ?>;color:<?= $mc['col']; ?>;border-radius:6px;padding:2px 8px;font-family:'IBM Plex Mono',monospace;font-size:11px;font-weight:700;white-space:nowrap;">
                                                <?= htmlspecialchars($r[0]); ?>
                                            </span>
                                        </td>
                                        <td><code><?= htmlspecialchars($r[1]); ?></code></td>
                                        <td style="color:#374151;font-size:13px;"><?= htmlspecialchars($r[2]); ?></td>
                                        <td>
                                            <span style="display:inline-block;background:<?= $ac['bg']; ?>;color:<?= $ac['col']; ?>;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:600;">
                                                <?= htmlspecialchars($r[3]); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

        </article>

    </section>

        <?php /* =========================================================
            VISTE DATABASE
            ========================================================= */ ?>
    <section class="sections" aria-label="Viste database">
        <?php foreach ($results as $index => $result): ?>
            <article class="panel" style="animation-delay: <?= ($index * 35); ?>ms;">
                <header class="panel-head">
                    <div>
                        <h2 class="panel-title"><?= htmlspecialchars($result['title']); ?></h2>
                        <div class="panel-meta">
                            <code><?= htmlspecialchars($result['name']); ?></code>
                        </div>
                    </div>
                    <div class="panel-meta">
                        <span class="badge <?= badgeClass($result['group']); ?>"><?= htmlspecialchars($result['group']); ?></span>
                        <span class="badge badge-count"><?= $result['count']; ?> record</span>
                    </div>
                </header>


                <?php if ($result['error'] !== null): ?>
                    <div class="error">
                        Errore query: <?= htmlspecialchars($result['error']); ?>
                    </div>
                <?php elseif ($result['count'] === 0): ?>
                    <div class="empty">
                        Nessun dato disponibile in questa vista. Il mockup evidenzia uno stato vuoto che può essere usato per CTA o onboarding.
                    </div>
                <?php else: ?>
                        <?php if ($result['name'] === 'v_archivio_buste_paga'): ?>
                            <div class="cards-grid" style="display:grid;gap:12px">
                                <?php foreach ($result['rows'] as $row): ?>
                                    <div style="background:#fff;padding:14px;border-radius:12px;box-shadow:0 6px 18px rgba(12,36,80,0.04);display:flex;flex-direction:column;gap:8px">
                                        <div style="display:flex;justify-content:space-between;align-items:center">
                                            <div style="font-weight:800"><?php echo htmlspecialchars($row['Mese_riferimento'] ?? ($row['Mese'] ?? '')); ?></div>
                                            <div style="color:#64748b;font-size:13px"><?= htmlspecialchars($row['Data_archiviazione'] ?? ($row['Data'] ?? '')) ?></div>
                                        </div>
                                        <div style="display:flex;gap:12px">
                                            <div style="flex:1;background:#ecfeff;padding:10px;border-radius:8px">

                                                <div style="font-size:12px;color:#065f46">Netto</div>
                                                <div style="font-weight:800">€ <?= htmlspecialchars(number_format((float)($row['Netto'] ?? $row['Stipendio_netto'] ?? 0), 2, ',', '.')) ?></div>
                                            </div>
                                            <div style="flex:1;background:#f1f5f9;padding:10px;border-radius:8px">
                                                <div style="font-size:12px;color:#0f172a">Lordo</div>
                                                <div style="font-weight:800">€ <?= htmlspecialchars(number_format((float)($row['Lordo'] ?? $row['Stipendio_lordo'] ?? 0), 2, ',', '.')) ?></div>
                                            </div>
                                            <div style="flex:1;background:#fff1f2;padding:10px;border-radius:8px">
                                                <div style="font-size:12px;color:#9f1239">Tasse</div>
                                                <div style="font-weight:800;color:#b91c1c">€ <?= htmlspecialchars(number_format((float)($row['Tasse'] ?? (($row['Stipendio_lordo'] ?? 0) - ($row['Stipendio_netto'] ?? 0))), 2, ',', '.')) ?></div>
                                            </div>
                                        </div>
                                        <div style="display:flex;gap:8px;align-items:center">
                                            <a class="btn" href="#" style="border:1px solid #e6eefc;padding:8px 12px;border-radius:8px">Scarica PDF</a>
                                            <form method="post" action="/SITO/BPIC/api/use_cases.php?use_case=archivio_buste_paga_delete" style="margin:0">
                                                <input type="hidden" name="id_busta" value="<?= htmlspecialchars($row['ID_busta']) ?>">
                                                <button class="btn" style="background:#fee2e2;border-radius:8px;padding:8px 12px;border:0;color:#b91c1c">Elimina</button>
                                            </form>
                                        </div>
                                    </div>

                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <?php foreach ($result['columns'] as $column): ?>
                                                <th><?= htmlspecialchars($column); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($result['rows'] as $row): ?>
                                            <tr>
                                                <?php foreach ($result['columns'] as $column): ?>
                                                    <td>
                                                        <?php
                                                        $value = $row[$column] ?? null;
                                                        echo $value === null || $value === '' ? '<em>NULL</em>' : htmlspecialchars((string)$value);
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

</main>
<script src="/SITO/BPIC/auth/auto_logout_on_close.js"></script>
</body>
</html>