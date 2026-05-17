<?php
/**
 * File: validate_token.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/api/jwt.php";

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /SITO/BPIC/login.php');
    exit;
}

$token = trim($_POST['token'] ?? '');
if ($token === '') {
    $error = 'Token mancante.';
    echo "<p>$error</p><p><a href=\"/SITO/BPIC/login.php\">Torna al login</a></p>";
    exit;
}

$payload = verify_jwt($token, JWT_SECRET);

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
if (!$payload || empty($payload['user_id'])) {
    $error = 'Token non valido o scaduto.';
    echo "<p>$error</p><p><a href=\"/SITO/BPIC/login.php\">Torna al login</a></p>";
    exit;
}

$userId = (int)$payload['user_id'];

try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
    $stmt = $pdo->prepare('SELECT ID_utente, Email, ID_ruolo FROM Utenti WHERE ID_utente = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    echo "<p>Errore interno (execute).</p><p><a href=\"/SITO/BPIC/login.php\">Torna al login</a></p>";
    exit;
}

if (!$user) {
    echo "<p>Utente non trovato.</p><p><a href=\"/SITO/BPIC/login.php\">Torna al login</a></p>";
    exit;

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
}

// Imposta la sessione come se l'utente avesse fatto login
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['ID_utente'];
$_SESSION['email'] = $user['Email'];
// role_id: prefer value from token payload, fallback to DB field
$_SESSION['role_id'] = null;
if (!empty($payload['role_id'])) {
    $_SESSION['role_id'] = (int)$payload['role_id'];
} elseif (!empty($user['ID_ruolo'])) {
    $_SESSION['role_id'] = (int)$user['ID_ruolo'];
}

// Recupera ruoli e privilegi per mostrare nella dashboard basandosi su ID_ruolo
$roles = [];
// se non abbiamo role_id, non possiamo popolare privilegi
$permissions = [];
if (!empty($_SESSION['role_id'])) {
    try {
        $stmt = $pdo->prepare('SELECT r.ID_ruolo, r.Nome_ruolo, p.ID_privilegio, p.Nome_privilegio, p.Risorsa, p.Azione
            FROM Ruoli r
            JOIN Ruolo_Privilegio rp ON rp.ID_ruolo = r.ID_ruolo
            JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
            WHERE r.ID_ruolo = ?');
        $stmt->execute([$_SESSION['role_id']]);
        $result = $stmt->fetchAll();
    } catch (PDOException $e) {
        $result = [];
    }
} else {
    $result = [];
}

// ===== SEZIONE 4: LOGICA DI PROCESSO =====
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
$_SESSION['permissions'] = $permissions;

// ===== SEZIONE 5: LOGICA DI PROCESSO =====

$roleNames = array_map(static fn(array $r): string => (string)($r['name'] ?? ''), $roles);
$isTenant = in_array('tenant', $roleNames, true);

if ($isTenant) {
    header('Location: /SITO/BPIC/tenant_dashboard.php');
    exit;
}

// Se l'utente ha gia impostato il contratto, evita il setup e porta alla home provvisoria.
$hasContractSettings = false;
try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
    $stmt = $pdo->prepare('SELECT ID_utente FROM Impostazioni_contratto WHERE ID_utente = ? AND tipologia_dipendente <> "" LIMIT 1');
    $stmt->execute([$userId]);
    $hasContractSettings = (bool)$stmt->fetch();
} catch (Throwable $e) {
    $hasContractSettings = false;
}

if ($hasContractSettings) {

// ===== SEZIONE 6: LOGICA DI PROCESSO =====
    header('Location: /SITO/BPIC/home.php');
    exit;
}

// Al primo accesso l'utente compila il profilo contratto.
header('Location: /SITO/BPIC/Profilo_contratto.php');
exit;