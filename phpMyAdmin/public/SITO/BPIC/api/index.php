<?php
declare(strict_types=1);

/*
 * api/index.php — Router principale dell'API BPIC.
 *
 * Rotte statiche:
 *   POST  /api/auth/login    — login (pubblica)
 *   POST  /api/auth/register — registrazione (pubblica)
 *   GET   /api/users         — lista utenti  (richiede auth)
 *   GET   /api/roles         — lista ruoli   (richiede auth)
 *
 * Rotte dinamiche (ID nella URL):
 *   DELETE /api/users/{id}              — elimina utente
 *   PATCH  /api/users/{id}/roles/{id}   — cambia ruolo a un utente
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// ── Estrae il path relativo alla cartella api/ ────────────────────────────
// Es. REQUEST_URI = /SITO/BPIC/api/auth/login → path = 'auth/login'
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path   = trim(substr($uri, strlen($base)), '/');
$method = $_SERVER['REQUEST_METHOD'];

// ── Rotte statiche ────────────────────────────────────────────────────────
// [classe, metodo, richiede_auth]
$staticRoutes = [
    // Auth
    'POST:auth/login'    => ['AuthController', 'login',    false],
    'POST:auth/register' => ['AuthController', 'register', false],
    'GET:auth/me'        => ['AuthController', 'me',       true],

    // Admin
    'GET:users'          => ['UserController',  'list',    true],
    'GET:roles'          => ['RoleController',  'list',    true],

    // Buste paga
    'GET:payslip'             => ['PayslipController', 'list',     true],
    'POST:payslip'            => ['PayslipController', 'generate', true],
    'POST:payslip/compare'    => ['PayslipController', 'compare',  true],

    // Impostazioni contratto
    'GET:contract'            => ['ContractController', 'get',  true],
    'POST:contract'           => ['ContractController', 'save', true],

    // Abbonamento
    'GET:subscription/plans'  => ['SubscriptionController', 'plans', false],
    'POST:subscription/buy'   => ['SubscriptionController', 'buy',   true],
];

// ── Rotte dinamiche (con ID nella URL) ────────────────────────────────────
// I parametri estratti vengono passati come argomenti al metodo del controller.
$class        = null;
$action       = null;
$requiresAuth = true;
$params       = [];

if (isset($staticRoutes[$method . ':' . $path])) {
    // Rotta statica trovata
    [$class, $action, $requiresAuth] = $staticRoutes[$method . ':' . $path];

} elseif ($method === 'DELETE' && preg_match('/^users\/(\d+)$/', $path, $m)) {
    // DELETE /api/users/{id}
    [$class, $action] = ['UserController', 'delete'];
    $params = [(int)$m[1]];

} elseif ($method === 'PATCH' && preg_match('/^users\/(\d+)\/roles\/(\d+)$/', $path, $m)) {
    // PATCH /api/users/{id}/roles/{id_role}
    [$class, $action] = ['RoleController', 'updateUserRole'];
    $params = [(int)$m[1], (int)$m[2]];

} elseif ($method === 'GET' && preg_match('/^payslip\/(\d+)\/pdf$/', $path, $m)) {
    // GET /api/payslip/{id}/pdf  (verificato prima di payslip/{id})
    [$class, $action] = ['PayslipController', 'downloadPdf'];
    $params = [(int)$m[1]];

} elseif ($method === 'POST' && preg_match('/^payslip\/(\d+)\/email$/', $path, $m)) {
    // POST /api/payslip/{id}/email
    [$class, $action] = ['PayslipController', 'sendEmail'];
    $params = [(int)$m[1]];

} elseif ($method === 'GET' && preg_match('/^payslip\/(\d+)$/', $path, $m)) {
    // GET /api/payslip/{id}
    [$class, $action] = ['PayslipController', 'get'];
    $params = [(int)$m[1]];

} elseif ($method === 'DELETE' && preg_match('/^payslip\/(\d+)$/', $path, $m)) {
    // DELETE /api/payslip/{id}
    [$class, $action] = ['PayslipController', 'delete'];
    $params = [(int)$m[1]];

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route non trovata.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Autenticazione per rotte protette ─────────────────────────────────────
$currentUser = null;
if ($requiresAuth) {
    $jwtRaw  = $_COOKIE['jwt'] ?? '';
    $jwtData = verify_jwt($jwtRaw, JWT_SECRET);

    if (!$jwtData || empty($jwtData['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non autenticato.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $currentUser = [
        'user_id'   => (int)$jwtData['user_id'],
        'role_id'   => (int)($jwtData['role_id']      ?? 0),
        'role_name' => (string)($jwtData['role_name'] ?? ''),
    ];
}

// ── Dispatch al controller ────────────────────────────────────────────────
require_once __DIR__ . '/controllers/' . $class . '.php';

$controller = $requiresAuth
    ? new $class($pdo, $currentUser)
    : new $class($pdo);

// I parametri URL (es. user_id, role_id) vengono passati come argomenti
$controller->$action(...$params);