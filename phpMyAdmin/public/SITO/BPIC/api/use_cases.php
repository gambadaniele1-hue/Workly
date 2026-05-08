<?php
declare(strict_types=1);

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/jwt.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function send_json(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function request_data(string $method): array
{
    if ($method === 'GET') {
        return $_GET;
    }
    if ($method === 'POST') {
        $body = get_json_body();
        if (!empty($body)) {
            return $body;
        }
        return $_POST;
    }

    return get_json_body();
}

function bearer_token(): ?string
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($authHeader === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $matches)) {
        return null;
    }

    return $matches[1];
}

function fetch_user_and_permissions($mysqli, int $userId): array
{
    $stmt = $mysqli->prepare('SELECT ID_utente, Email, N_Telefono FROM Utenti WHERE ID_utente = ? LIMIT 1');
    if (!$stmt) {
        send_json(500, ['error' => 'Errore interno (prepare utente).']);
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user) {
        send_json(404, ['error' => 'Utente non trovato.']);
    }

    $email = (string)$user['Email'];

    $stmt = $mysqli->prepare('SELECT r.ID_ruolo, r.Nome_ruolo, p.ID_privilegio, p.Nome_privilegio, p.Risorsa, p.Azione
        FROM Utente_Ruolo ur
        JOIN Ruoli r ON r.ID_ruolo = ur.ID_ruolo
        JOIN Ruolo_Privilegio rp ON rp.ID_ruolo = r.ID_ruolo
        JOIN Privilegi p ON p.ID_privilegio = rp.ID_privilegio
        WHERE ur.email_utente = ?');
    if (!$stmt) {
        send_json(500, ['error' => 'Errore interno (prepare permessi).']);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $roles = [];
    $permissions = [];
    $roleMap = [];
    $permMap = [];
    $roleNames = [];

    while ($row = $result->fetch_assoc()) {
        $roleId = (int)$row['ID_ruolo'];
        if (!isset($roleMap[$roleId])) {
            $roleMap[$roleId] = true;
            $roles[] = ['id' => $roleId, 'name' => $row['Nome_ruolo']];
            $roleNames[] = $row['Nome_ruolo'];
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

    return [
        'user' => $user,
        'roles' => $roles,
        'permissions' => $permissions,
        'role_names' => $roleNames,
    ];
}

function has_permission(array $auth, string $resource, string $action): bool
{
    if (in_array('admin', $auth['role_names'], true)) {
        return true;
    }

    foreach ($auth['permissions'] as $perm) {
        $resourceOk = isset($perm['resource']) && $perm['resource'] === $resource;
        $actionOk = isset($perm['action']) && ($perm['action'] === $action || $perm['action'] === 'ALL');
        if ($resourceOk && $actionOk) {
            return true;
        }
    }

    return false;
}

function require_permission(array $auth, string $resource, string $action): void
{
    if (!has_permission($auth, $resource, $action)) {
        send_json(403, [
            'error' => 'Permessi insufficienti.',
            'required' => ['resource' => $resource, 'action' => $action],
        ]);
    }
}

function fetch_view($mysqli, string $viewName, bool $isAdmin, int $userId): array
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $viewName)) {
        send_json(400, ['error' => 'Nome vista non valido.']);
    }

    $sql = "SELECT * FROM {$viewName}";
    $types = '';
    $params = [];

    if (!$isAdmin && in_array($viewName, [
        'v_generazione_busta_paga',
        'v_download_pdf',
        'v_invio_pdf_email',
        'v_archivio_buste_paga',
        'v_confronto_buste_paga',
    ], true)) {
        $sql .= ' WHERE ID_utente = ?';
        $types = 'i';
        $params[] = $userId;
    }

    $sql .= ' ORDER BY 1 DESC';
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        send_json(500, ['error' => 'Errore interno (prepare vista).']);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    return $rows;
}

function ensure_t14_test_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS T14_Test_BustaPaga (
        ID_test INT AUTO_INCREMENT PRIMARY KEY,
        ID_utente INT NOT NULL,
        scenario ENUM('seed','atomic','non_atomic') NOT NULL,
        step_code VARCHAR(20) NOT NULL DEFAULT 'MAIN',
        contratto_tipo VARCHAR(50) NOT NULL,
        retribuzione_base DECIMAL(10,2) NOT NULL DEFAULT 0,
        bonus DECIMAL(10,2) NOT NULL DEFAULT 0,
        straordinari_ore DECIMAL(8,2) NOT NULL DEFAULT 0,
        aliquota_tasse DECIMAL(6,4) NOT NULL DEFAULT 0,
        giorni_ferie INT NOT NULL DEFAULT 0,
        lordo DECIMAL(10,2) NULL,
        netto DECIMAL(10,2) NULL,
        tasse DECIMAL(10,2) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        batch_uuid VARCHAR(36) NOT NULL,
        UNIQUE KEY uq_batch_step (batch_uuid, step_code),
        KEY idx_t14_utente (ID_utente)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function t14_load_seed(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT contratto_tipo, aliquota_tasse FROM T14_Test_BustaPaga WHERE ID_utente = ? AND scenario = ? ORDER BY ID_test DESC LIMIT 1');
    $stmt->execute([$userId, 'seed']);
    $contratto = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT retribuzione_base, bonus, straordinari_ore, giorni_ferie FROM T14_Test_BustaPaga WHERE ID_utente = ? AND scenario = ? ORDER BY ID_test DESC LIMIT 1');
    $stmt->execute([$userId, 'seed']);
    $datiMensili = $stmt->fetch();

    if (!$contratto || !$datiMensili) {
        throw new RuntimeException('Seed T14 non trovato. Esegui prima use_case=t14_test_setup.');
    }

    return [
        'contratto' => $contratto,
        'dati_mensili' => $datiMensili,
    ];
}

function t14_calcolo(array $contratto, array $datiMensili): array
{
    $base = (float)$datiMensili['retribuzione_base'];
    $bonus = (float)$datiMensili['bonus'];
    $oreStraordinario = (float)$datiMensili['straordinari_ore'];
    $aliquota = (float)$contratto['aliquota_tasse'];

    $lordo = $base + $bonus + ($oreStraordinario * 12.5);
    $tasse = $lordo * $aliquota;
    $netto = $lordo - $tasse;

    return [
        'lordo' => round($lordo, 2),
        'netto' => round($netto, 2),
        'tasse' => round($tasse, 2),
        'ferie' => (int)$datiMensili['giorni_ferie'],
    ];
}

if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    send_json(405, ['error' => 'Metodo non consentito.']);
}

$token = bearer_token();
if ($token === null) {
    send_json(401, ['error' => 'Token Bearer mancante.']);
}

$payload = verify_jwt($token, JWT_SECRET);
if (!$payload || empty($payload['user_id'])) {
    send_json(401, ['error' => 'JWT non valido o scaduto.']);
}

$auth = fetch_user_and_permissions($mysqli, (int)$payload['user_id']);
$isAdmin = in_array('admin', $auth['role_names'], true);
$currentUserId = (int)$auth['user']['ID_utente'];

$input = request_data($method);
$useCase = (string)($input['use_case'] ?? $_GET['use_case'] ?? '');

if ($useCase === '') {
    send_json(400, [
        'error' => 'Parametro use_case mancante.',
        'example' => [
            'GET /SITO/BPIC/api/use_cases.php?use_case=generazione_busta_paga_list',
            'POST /SITO/BPIC/api/use_cases.php {"use_case":"generazione_busta_paga_create","mese_riferimento":"2026-05","ore_lavorate":168,"paga_oraria":10,"ore_ferie":0,"ore_malattia":0,"ore_straordinari":0,"ore_festivi":0,"ore_prefestivi":0,"ore_notturne":0,"ore_reperibilita":0,"ore_trasferta":0}',
        ],
    ]);
}

switch ($useCase) {
    case 'generazione_busta_paga_list':
        require_permission($auth, 'buste_paga', 'INSERT');
        send_json(200, [
            'use_case' => $useCase,
            'rows' => fetch_view($mysqli, 'v_generazione_busta_paga', $isAdmin, $currentUserId),
        ]);

    case 'generazione_busta_paga_create':
        require_permission($auth, 'buste_paga', 'INSERT');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        $mese = trim((string)($input['mese_riferimento'] ?? $input['mese'] ?? ''));
        $oreLavorate = (float)($input['ore_lavorate'] ?? 0);
        $pagaOraria = (float)($input['paga_oraria'] ?? 0);
        $oreFerie = (float)($input['ore_ferie'] ?? 0);
        $oreMalattia = (float)($input['ore_malattia'] ?? 0);
        $oreStraordinari = (float)($input['ore_straordinari'] ?? 0);
        $oreFestivi = (float)($input['ore_festivi'] ?? 0);
        $orePrefestivi = (float)($input['ore_prefestivi'] ?? 0);
        $oreNotturne = (float)($input['ore_notturne'] ?? 0);
        $oreReperibilita = (float)($input['ore_reperibilita'] ?? 0);
        $oreTrasferta = (float)($input['ore_trasferta'] ?? 0);

        $lordo = array_key_exists('stipendio_lordo', $input)
            ? (float)$input['stipendio_lordo']
            : ($oreLavorate * $pagaOraria);
        $netto = array_key_exists('stipendio_netto', $input)
            ? (float)$input['stipendio_netto']
            : $lordo;

        if ($mese === '' || $lordo < 0 || $netto < 0 || $oreLavorate < 0 || $pagaOraria < 0) {
            send_json(422, ['error' => 'Valori busta paga non validi.']);
        }

        $mysqli->begin_transaction();
        try {
            $stmt = $mysqli->prepare('INSERT INTO Busta_paga (Mese_riferimento, Stipendio_lordo, Stipendio_netto, Ore_lavorate, Paga_oraria, Ore_ferie, Ore_malattia, Ore_straordinari, Ore_festivi, Ore_prefestivi, Ore_notturne, Ore_reperibilita, Ore_trasferta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            if (!$stmt) {
                throw new RuntimeException('Prepare insert busta fallita.');
            }
            $stmt->bind_param('sdddddddddddd', $mese, $lordo, $netto, $oreLavorate, $pagaOraria, $oreFerie, $oreMalattia, $oreStraordinari, $oreFestivi, $orePrefestivi, $oreNotturne, $oreReperibilita, $oreTrasferta);
            $stmt->execute();
            $idBusta = (int)$stmt->insert_id;
            $stmt->close();

            $stmt = $mysqli->prepare('UPDATE Utenti SET ID_busta = ? WHERE ID_utente = ?');
            if (!$stmt) {
                throw new RuntimeException('Prepare update utente fallita.');
            }
            $stmt->bind_param('ii', $idBusta, $currentUserId);
            $stmt->execute();
            $stmt->close();

            $mysqli->commit();
        } catch (Throwable $e) {
            $mysqli->rollback();
            send_json(500, ['error' => 'Errore durante la creazione della busta paga.']);
        }

        send_json(201, [
            'use_case' => $useCase,
            'message' => 'Busta paga creata con successo.',
            'id_busta' => $idBusta,
        ]);

    case 't14_test_setup':
        require_permission($auth, 'buste_paga', 'INSERT');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        ensure_t14_test_table($pdo);

        $pdo->prepare('DELETE FROM T14_Test_BustaPaga WHERE ID_utente = ?')->execute([$currentUserId]);

        $batch = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare('INSERT INTO T14_Test_BustaPaga (ID_utente, scenario, step_code, contratto_tipo, retribuzione_base, bonus, straordinari_ore, aliquota_tasse, giorni_ferie, lordo, netto, tasse, batch_uuid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?)');
        $stmt->execute([$currentUserId, 'seed', 'SEED', 'Metalmeccanico', 1800.00, 120.00, 10.00, 0.2350, 26, $batch]);

        send_json(201, [
            'use_case' => $useCase,
            'message' => 'Tabella di test T14 pronta e seed inserito.',
            'seed_batch' => $batch,
        ]);

    case 't14_generazione_busta_paga_atomic':
        require_permission($auth, 'buste_paga', 'INSERT');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        ensure_t14_test_table($pdo);

        try {
            $seed = t14_load_seed($pdo, $currentUserId);
            $calc = t14_calcolo($seed['contratto'], $seed['dati_mensili']);

            $batch = bin2hex(random_bytes(16));
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO T14_Test_BustaPaga (ID_utente, scenario, step_code, contratto_tipo, retribuzione_base, bonus, straordinari_ore, aliquota_tasse, giorni_ferie, lordo, netto, tasse, batch_uuid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$currentUserId, 'atomic', 'MAIN', (string)$seed['contratto']['contratto_tipo'], (float)$seed['dati_mensili']['retribuzione_base'], (float)$seed['dati_mensili']['bonus'], (float)$seed['dati_mensili']['straordinari_ore'], (float)$seed['contratto']['aliquota_tasse'], (int)$seed['dati_mensili']['giorni_ferie'], $calc['lordo'], $calc['netto'], $calc['tasse'], $batch]);
            $stmt->execute([$currentUserId, 'atomic', 'AUDIT', (string)$seed['contratto']['contratto_tipo'], (float)$seed['dati_mensili']['retribuzione_base'], (float)$seed['dati_mensili']['bonus'], (float)$seed['dati_mensili']['straordinari_ore'], (float)$seed['contratto']['aliquota_tasse'], (int)$seed['dati_mensili']['giorni_ferie'], $calc['lordo'], $calc['netto'], $calc['tasse'], $batch]);

            $pdo->commit();

            $check = $pdo->prepare('SELECT COUNT(*) AS cnt FROM T14_Test_BustaPaga WHERE ID_utente = ? AND batch_uuid = ?');
            $check->execute([$currentUserId, $batch]);
            $left = $check->fetch();

            send_json(201, [
                'use_case' => $useCase,
                'message' => 'T14 con transazione eseguita e salvata: beginTransaction + query + commit.',
                'batch' => $batch,
                'calcolo' => $calc,
                'rows_after_commit' => (int)($left['cnt'] ?? 0),
            ]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            send_json(500, ['error' => 'Errore T14 atomica.', 'detail' => $e->getMessage()]);
        }

    case 't14_generazione_busta_paga_non_atomic':
        require_permission($auth, 'buste_paga', 'INSERT');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        ensure_t14_test_table($pdo);

        try {
            $seed = t14_load_seed($pdo, $currentUserId);
            $calc = t14_calcolo($seed['contratto'], $seed['dati_mensili']);
            $batch = bin2hex(random_bytes(16));

            // NO TRANSACTION: simuliamo una scrittura parziale che lascia inconsistenza.
            $stmt = $pdo->prepare('INSERT INTO T14_Test_BustaPaga (ID_utente, scenario, step_code, contratto_tipo, retribuzione_base, bonus, straordinari_ore, aliquota_tasse, giorni_ferie, lordo, netto, tasse, batch_uuid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$currentUserId, 'non_atomic', 'MAIN', (string)$seed['contratto']['contratto_tipo'], (float)$seed['dati_mensili']['retribuzione_base'], (float)$seed['dati_mensili']['bonus'], (float)$seed['dati_mensili']['straordinari_ore'], (float)$seed['contratto']['aliquota_tasse'], (int)$seed['dati_mensili']['giorni_ferie'], $calc['lordo'], $calc['netto'], $calc['tasse'], $batch]);

            // Seconda INSERT volutamente errata: duplicate key su (batch_uuid, step_code).
            $stmt->execute([$currentUserId, 'non_atomic', 'MAIN', (string)$seed['contratto']['contratto_tipo'], (float)$seed['dati_mensili']['retribuzione_base'], (float)$seed['dati_mensili']['bonus'], (float)$seed['dati_mensili']['straordinari_ore'], (float)$seed['contratto']['aliquota_tasse'], (int)$seed['dati_mensili']['giorni_ferie'], $calc['lordo'], $calc['netto'], $calc['tasse'], $batch]);

            send_json(201, [
                'use_case' => $useCase,
                'message' => 'Scenario non atomico concluso senza errori (non previsto).',
            ]);
        } catch (Throwable $e) {
            $check = $pdo->prepare('SELECT COUNT(*) AS cnt FROM T14_Test_BustaPaga WHERE ID_utente = ? AND scenario = ? AND batch_uuid = ?');
            $check->execute([$currentUserId, 'non_atomic', $batch ?? '']);
            $row = $check->fetch();

            send_json(500, [
                'use_case' => $useCase,
                'error' => 'Scenario non atomico fallito: database in stato parziale (dimostrazione problema).',
                'detail' => $e->getMessage(),
                'partial_rows_left' => (int)($row['cnt'] ?? 0),
                'batch' => $batch ?? null,
            ]);
        }

    case 't14_test_state':
        require_permission($auth, 'buste_paga', 'INSERT');
        ensure_t14_test_table($pdo);

        $stmt = $pdo->prepare('SELECT ID_test, scenario, step_code, contratto_tipo, lordo, netto, tasse, batch_uuid, created_at FROM T14_Test_BustaPaga WHERE ID_utente = ? ORDER BY ID_test DESC LIMIT 30');
        $stmt->execute([$currentUserId]);
        $rows = $stmt->fetchAll();

        send_json(200, [
            'use_case' => $useCase,
            'rows' => $rows,
        ]);

    case 'download_pdf':
        require_permission($auth, 'pdf', 'SELECT');
        send_json(200, [
            'use_case' => $useCase,
            'rows' => fetch_view($mysqli, 'v_download_pdf', $isAdmin, $currentUserId),
            'note' => 'Questa API espone i dati necessari al download PDF. La generazione file e demandata al frontend o a un servizio PDF.',
        ]);

    case 'invio_pdf_email':
        require_permission($auth, 'email', 'INSERT');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        $idBusta = (int)($input['id_busta'] ?? 0);
        $destinatario = trim((string)($input['destinatario'] ?? $auth['user']['Email'] ?? ''));
        if ($idBusta <= 0 || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            send_json(422, ['error' => 'Parametri invio non validi.']);
        }

        $sql = 'SELECT * FROM v_invio_pdf_email WHERE ID_busta = ?';
        if (!$isAdmin) {
            $sql .= ' AND ID_utente = ?';
        }

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            send_json(500, ['error' => 'Errore interno (prepare invio).']);
        }
        if ($isAdmin) {
            $stmt->bind_param('i', $idBusta);
        } else {
            $stmt->bind_param('ii', $idBusta, $currentUserId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            send_json(404, ['error' => 'Busta paga non trovata per invio email.']);
        }

        send_json(200, [
            'use_case' => $useCase,
            'message' => 'Invio email simulato con successo.',
            'to' => $destinatario,
            'payload' => $row,
        ]);

    case 'archivio_buste_paga_list':
        require_permission($auth, 'archivio', 'SELECT');
        send_json(200, [
            'use_case' => $useCase,
            'rows' => fetch_view($mysqli, 'v_archivio_buste_paga', $isAdmin, $currentUserId),
        ]);

    case 'archivio_buste_paga_delete':
        require_permission($auth, 'archivio', 'SELECT');
        if ($method !== 'DELETE') {
            send_json(405, ['error' => 'Metodo richiesto: DELETE']);
        }

        $idBusta = (int)($input['id_busta'] ?? 0);
        if ($idBusta <= 0) {
            send_json(422, ['error' => 'id_busta non valido.']);
        }

        $sql = 'DELETE FROM Confronta WHERE ID_busta = ?';
        if (!$isAdmin) {
            $sql .= ' AND ID_utente = ?';
        }

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            send_json(500, ['error' => 'Errore interno (prepare delete archivio).']);
        }
        if ($isAdmin) {
            $stmt->bind_param('i', $idBusta);
        } else {
            $stmt->bind_param('ii', $idBusta, $currentUserId);
        }
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        send_json(200, [
            'use_case' => $useCase,
            'deleted_rows' => $affected,
        ]);

    case 'confronto_buste_paga_list':
        require_permission($auth, 'confronto', 'SELECT');
        send_json(200, [
            'use_case' => $useCase,
            'rows' => fetch_view($mysqli, 'v_confronto_buste_paga', $isAdmin, $currentUserId),
        ]);

    case 'confronto_buste_paga_create':
        require_permission($auth, 'confronto', 'SELECT');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        $idA = (int)($input['id_busta_a'] ?? 0);
        $idB = (int)($input['id_busta_b'] ?? 0);
        if ($idA <= 0 || $idB <= 0 || $idA === $idB) {
            send_json(422, ['error' => 'id_busta_a/id_busta_b non validi.']);
        }

        $mysqli->begin_transaction();
        try {
            $stmt = $mysqli->prepare('INSERT IGNORE INTO Confronta (ID_utente, ID_busta, Data_confronto) VALUES (?, ?, NOW())');
            if (!$stmt) {
                throw new RuntimeException('Prepare confronto fallita.');
            }
            $stmt->bind_param('ii', $currentUserId, $idA);
            $stmt->execute();
            $stmt->bind_param('ii', $currentUserId, $idB);
            $stmt->execute();
            $stmt->close();
            $mysqli->commit();
        } catch (Throwable $e) {
            $mysqli->rollback();
            send_json(500, ['error' => 'Errore durante il salvataggio del confronto.']);
        }

        send_json(201, [
            'use_case' => $useCase,
            'message' => 'Buste aggiunte al confronto.',
            'rows' => fetch_view($mysqli, 'v_confronto_buste_paga', $isAdmin, $currentUserId),
        ]);

    case 'gestione_utenti_list':
        require_permission($auth, 'utenti', 'ALL');
        send_json(200, [
            'use_case' => $useCase,
            'rows' => fetch_view($mysqli, 'v_gestione_utenti', true, $currentUserId),
        ]);

    case 'gestione_utenti_create':
        require_permission($auth, 'utenti', 'ALL');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        $email = trim((string)($input['email'] ?? ''));
        $telefono = trim((string)($input['n_telefono'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $idRuolo = (int)($input['id_ruolo'] ?? 3);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            send_json(422, ['error' => 'Dati utente non validi (email/password).']);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $mysqli->begin_transaction();
        try {
            $stmt = $mysqli->prepare('INSERT INTO Utenti (N_Telefono, Email, ID_busta, Password_hash) VALUES (?, ?, NULL, ?)');
            if (!$stmt) {
                throw new RuntimeException('Prepare insert utente fallita.');
            }
            $stmt->bind_param('sss', $telefono, $email, $passwordHash);
            $stmt->execute();
            $userId = (int)$stmt->insert_id;
            $stmt->close();

            $stmt = $mysqli->prepare('INSERT INTO Utente_Ruolo (ID_ruolo, email_utente) VALUES (?, ?)');
            if (!$stmt) {
                throw new RuntimeException('Prepare ruolo utente fallita.');
            }
            $stmt->bind_param('is', $idRuolo, $email);
            $stmt->execute();
            $stmt->close();

            $mysqli->commit();
        } catch (Throwable $e) {
            $mysqli->rollback();
            send_json(500, ['error' => 'Errore creazione utente (forse email gia presente o ruolo non valido).']);
        }

        send_json(201, [
            'use_case' => $useCase,
            'message' => 'Utente creato con successo.',
            'user_id' => $userId,
            'email' => $email,
            'id_ruolo' => $idRuolo,
        ]);

    case 'gestione_utenti_update':
        require_permission($auth, 'utenti', 'ALL');
        if ($method !== 'PUT') {
            send_json(405, ['error' => 'Metodo richiesto: PUT']);
        }

        $userId = (int)($input['user_id'] ?? 0);
        if ($userId <= 0) {
            send_json(422, ['error' => 'user_id non valido.']);
        }

        $telefono = array_key_exists('n_telefono', $input) ? trim((string)$input['n_telefono']) : null;
        $password = array_key_exists('password', $input) ? (string)$input['password'] : null;
        $idRuolo = array_key_exists('id_ruolo', $input) ? (int)$input['id_ruolo'] : null;

        $stmt = $mysqli->prepare('SELECT Email FROM Utenti WHERE ID_utente = ? LIMIT 1');
        if (!$stmt) {
            send_json(500, ['error' => 'Errore interno (select utente).']);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$existing) {
            send_json(404, ['error' => 'Utente non trovato.']);
        }

        $mysqli->begin_transaction();
        try {
            if ($telefono !== null) {
                $stmt = $mysqli->prepare('UPDATE Utenti SET N_Telefono = ? WHERE ID_utente = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update telefono fallita.');
                }
                $stmt->bind_param('si', $telefono, $userId);
                $stmt->execute();
                $stmt->close();
            }

            if ($password !== null) {
                if (strlen($password) < 6) {
                    throw new RuntimeException('Password troppo corta.');
                }
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $mysqli->prepare('UPDATE Utenti SET Password_hash = ? WHERE ID_utente = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update password fallita.');
                }
                $stmt->bind_param('si', $passwordHash, $userId);
                $stmt->execute();
                $stmt->close();
            }

            if ($idRuolo !== null) {
                $email = (string)$existing['Email'];
                $stmt = $mysqli->prepare('DELETE FROM Utente_Ruolo WHERE email_utente = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare delete ruolo utente fallita.');
                }
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->close();

                $stmt = $mysqli->prepare('INSERT INTO Utente_Ruolo (ID_ruolo, email_utente) VALUES (?, ?)');
                if (!$stmt) {
                    throw new RuntimeException('Prepare insert ruolo utente fallita.');
                }
                $stmt->bind_param('is', $idRuolo, $email);
                $stmt->execute();
                $stmt->close();
            }

            $mysqli->commit();
        } catch (Throwable $e) {
            $mysqli->rollback();
            send_json(500, ['error' => 'Errore aggiornamento utente.', 'detail' => $e->getMessage()]);
        }

        send_json(200, [
            'use_case' => $useCase,
            'message' => 'Utente aggiornato con successo.',
            'user_id' => $userId,
        ]);

    case 'gestione_utenti_delete':
        require_permission($auth, 'utenti', 'ALL');
        if ($method !== 'DELETE') {
            send_json(405, ['error' => 'Metodo richiesto: DELETE']);
        }

        $userId = (int)($input['user_id'] ?? 0);
        if ($userId <= 0) {
            send_json(422, ['error' => 'user_id non valido.']);
        }

        if ($userId === $currentUserId) {
            send_json(409, ['error' => 'Non puoi eliminare il tuo stesso account da questa API.']);
        }

        $stmt = $mysqli->prepare('DELETE FROM Utenti WHERE ID_utente = ?');
        if (!$stmt) {
            send_json(500, ['error' => 'Errore interno (delete utente).']);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        send_json(200, [
            'use_case' => $useCase,
            'deleted_rows' => $deleted,
        ]);

    case 'gestione_ruoli_list':
        require_permission($auth, 'ruoli', 'ALL');
        send_json(200, [
            'use_case' => $useCase,
            'rows' => fetch_view($mysqli, 'v_gestione_ruoli', true, $currentUserId),
        ]);

    case 'gestione_ruoli_create':
        require_permission($auth, 'ruoli', 'ALL');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        $nomeRuolo = trim((string)($input['nome_ruolo'] ?? ''));
        $descrizione = trim((string)($input['descrizione'] ?? ''));
        $attivo = isset($input['attivo']) ? (int)(bool)$input['attivo'] : 1;
        $privilegi = $input['privilegi'] ?? [];

        if ($nomeRuolo === '') {
            send_json(422, ['error' => 'nome_ruolo obbligatorio.']);
        }
        if (!is_array($privilegi)) {
            send_json(422, ['error' => 'privilegi deve essere un array di ID privilegio.']);
        }

        $mysqli->begin_transaction();
        try {
            $stmt = $mysqli->prepare('INSERT INTO Ruoli (Nome_ruolo, Descrizione, Attivo) VALUES (?, ?, ?)');
            if (!$stmt) {
                throw new RuntimeException('Prepare insert ruolo fallita.');
            }
            $stmt->bind_param('ssi', $nomeRuolo, $descrizione, $attivo);
            $stmt->execute();
            $idRuolo = (int)$stmt->insert_id;
            $stmt->close();

            if (!empty($privilegi)) {
                $stmt = $mysqli->prepare('INSERT INTO Ruolo_Privilegio (ID_ruolo, ID_privilegio, Data_assegnazione) VALUES (?, ?, NOW())');
                if (!$stmt) {
                    throw new RuntimeException('Prepare ruolo_privilegio fallita.');
                }
                foreach ($privilegi as $idPrivilegioRaw) {
                    $idPrivilegio = (int)$idPrivilegioRaw;
                    if ($idPrivilegio <= 0) {
                        continue;
                    }
                    $stmt->bind_param('ii', $idRuolo, $idPrivilegio);
                    $stmt->execute();
                }
                $stmt->close();
            }

            $mysqli->commit();
        } catch (Throwable $e) {
            $mysqli->rollback();
            send_json(500, ['error' => 'Errore creazione ruolo.', 'detail' => $e->getMessage()]);
        }

        send_json(201, [
            'use_case' => $useCase,
            'id_ruolo' => $idRuolo,
            'message' => 'Ruolo creato con successo.',
        ]);

    case 'gestione_ruoli_update':
        require_permission($auth, 'ruoli', 'ALL');
        if ($method !== 'PUT') {
            send_json(405, ['error' => 'Metodo richiesto: PUT']);
        }

        $idRuolo = (int)($input['id_ruolo'] ?? 0);
        if ($idRuolo <= 0) {
            send_json(422, ['error' => 'id_ruolo non valido.']);
        }

        $nomeRuolo = array_key_exists('nome_ruolo', $input) ? trim((string)$input['nome_ruolo']) : null;
        $descrizione = array_key_exists('descrizione', $input) ? trim((string)$input['descrizione']) : null;
        $attivo = array_key_exists('attivo', $input) ? (int)(bool)$input['attivo'] : null;
        $privilegi = array_key_exists('privilegi', $input) ? $input['privilegi'] : null;

        $mysqli->begin_transaction();
        try {
            if ($nomeRuolo !== null) {
                $stmt = $mysqli->prepare('UPDATE Ruoli SET Nome_ruolo = ? WHERE ID_ruolo = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update nome ruolo fallita.');
                }
                $stmt->bind_param('si', $nomeRuolo, $idRuolo);
                $stmt->execute();
                $stmt->close();
            }
            if ($descrizione !== null) {
                $stmt = $mysqli->prepare('UPDATE Ruoli SET Descrizione = ? WHERE ID_ruolo = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update descrizione ruolo fallita.');
                }
                $stmt->bind_param('si', $descrizione, $idRuolo);
                $stmt->execute();
                $stmt->close();
            }
            if ($attivo !== null) {
                $stmt = $mysqli->prepare('UPDATE Ruoli SET Attivo = ? WHERE ID_ruolo = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update attivo ruolo fallita.');
                }
                $stmt->bind_param('ii', $attivo, $idRuolo);
                $stmt->execute();
                $stmt->close();
            }

            if ($privilegi !== null) {
                if (!is_array($privilegi)) {
                    throw new RuntimeException('privilegi deve essere un array.');
                }

                $stmt = $mysqli->prepare('DELETE FROM Ruolo_Privilegio WHERE ID_ruolo = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare delete ruolo_privilegio fallita.');
                }
                $stmt->bind_param('i', $idRuolo);
                $stmt->execute();
                $stmt->close();

                if (!empty($privilegi)) {
                    $stmt = $mysqli->prepare('INSERT INTO Ruolo_Privilegio (ID_ruolo, ID_privilegio, Data_assegnazione) VALUES (?, ?, NOW())');
                    if (!$stmt) {
                        throw new RuntimeException('Prepare insert ruolo_privilegio fallita.');
                    }
                    foreach ($privilegi as $idPrivRaw) {
                        $idPriv = (int)$idPrivRaw;
                        if ($idPriv <= 0) {
                            continue;
                        }
                        $stmt->bind_param('ii', $idRuolo, $idPriv);
                        $stmt->execute();
                    }
                    $stmt->close();
                }
            }

            $mysqli->commit();
        } catch (Throwable $e) {
            $mysqli->rollback();
            send_json(500, ['error' => 'Errore aggiornamento ruolo.', 'detail' => $e->getMessage()]);
        }

        send_json(200, [
            'use_case' => $useCase,
            'message' => 'Ruolo aggiornato con successo.',
            'id_ruolo' => $idRuolo,
        ]);

    case 'gestione_ruoli_delete':
        require_permission($auth, 'ruoli', 'ALL');
        if ($method !== 'DELETE') {
            send_json(405, ['error' => 'Metodo richiesto: DELETE']);
        }

        $idRuolo = (int)($input['id_ruolo'] ?? 0);
        if ($idRuolo <= 0) {
            send_json(422, ['error' => 'id_ruolo non valido.']);
        }

        $stmt = $mysqli->prepare('DELETE FROM Ruoli WHERE ID_ruolo = ?');
        if (!$stmt) {
            send_json(500, ['error' => 'Errore interno (delete ruolo).']);
        }
        $stmt->bind_param('i', $idRuolo);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        send_json(200, [
            'use_case' => $useCase,
            'deleted_rows' => $deleted,
        ]);

    case 'gestione_privilegi_list':
        require_permission($auth, 'privilegi', 'ALL');
        send_json(200, [
            'use_case' => $useCase,
            'rows' => fetch_view($mysqli, 'v_gestione_privilegi', true, $currentUserId),
        ]);

    case 'gestione_privilegi_create':
        require_permission($auth, 'privilegi', 'ALL');
        if ($method !== 'POST') {
            send_json(405, ['error' => 'Metodo richiesto: POST']);
        }

        $nome = trim((string)($input['nome_privilegio'] ?? ''));
        $descrizione = trim((string)($input['descrizione'] ?? ''));
        $risorsa = trim((string)($input['risorsa'] ?? ''));
        $azione = strtoupper(trim((string)($input['azione'] ?? '')));

        if ($nome === '' || $risorsa === '' || !in_array($azione, ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'ALL'], true)) {
            send_json(422, ['error' => 'Dati privilegio non validi.']);
        }

        $stmt = $mysqli->prepare('INSERT INTO Privilegi (Nome_privilegio, Descrizione, Risorsa, Azione) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            send_json(500, ['error' => 'Errore interno (insert privilegio).']);
        }
        $stmt->bind_param('ssss', $nome, $descrizione, $risorsa, $azione);
        $stmt->execute();
        $idPrivilegio = (int)$stmt->insert_id;
        $stmt->close();

        send_json(201, [
            'use_case' => $useCase,
            'id_privilegio' => $idPrivilegio,
            'message' => 'Privilegio creato con successo.',
        ]);

    case 'gestione_privilegi_update':
        require_permission($auth, 'privilegi', 'ALL');
        if ($method !== 'PUT') {
            send_json(405, ['error' => 'Metodo richiesto: PUT']);
        }

        $idPrivilegio = (int)($input['id_privilegio'] ?? 0);
        if ($idPrivilegio <= 0) {
            send_json(422, ['error' => 'id_privilegio non valido.']);
        }

        $nome = array_key_exists('nome_privilegio', $input) ? trim((string)$input['nome_privilegio']) : null;
        $descrizione = array_key_exists('descrizione', $input) ? trim((string)$input['descrizione']) : null;
        $risorsa = array_key_exists('risorsa', $input) ? trim((string)$input['risorsa']) : null;
        $azione = array_key_exists('azione', $input) ? strtoupper(trim((string)$input['azione'])) : null;

        if ($azione !== null && !in_array($azione, ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'ALL'], true)) {
            send_json(422, ['error' => 'azione non valida.']);
        }

        $mysqli->begin_transaction();
        try {
            if ($nome !== null) {
                $stmt = $mysqli->prepare('UPDATE Privilegi SET Nome_privilegio = ? WHERE ID_privilegio = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update nome privilegio fallita.');
                }
                $stmt->bind_param('si', $nome, $idPrivilegio);
                $stmt->execute();
                $stmt->close();
            }
            if ($descrizione !== null) {
                $stmt = $mysqli->prepare('UPDATE Privilegi SET Descrizione = ? WHERE ID_privilegio = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update descrizione privilegio fallita.');
                }
                $stmt->bind_param('si', $descrizione, $idPrivilegio);
                $stmt->execute();
                $stmt->close();
            }
            if ($risorsa !== null) {
                $stmt = $mysqli->prepare('UPDATE Privilegi SET Risorsa = ? WHERE ID_privilegio = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update risorsa privilegio fallita.');
                }
                $stmt->bind_param('si', $risorsa, $idPrivilegio);
                $stmt->execute();
                $stmt->close();
            }
            if ($azione !== null) {
                $stmt = $mysqli->prepare('UPDATE Privilegi SET Azione = ? WHERE ID_privilegio = ?');
                if (!$stmt) {
                    throw new RuntimeException('Prepare update azione privilegio fallita.');
                }
                $stmt->bind_param('si', $azione, $idPrivilegio);
                $stmt->execute();
                $stmt->close();
            }

            $mysqli->commit();
        } catch (Throwable $e) {
            $mysqli->rollback();
            send_json(500, ['error' => 'Errore aggiornamento privilegio.', 'detail' => $e->getMessage()]);
        }

        send_json(200, [
            'use_case' => $useCase,
            'id_privilegio' => $idPrivilegio,
            'message' => 'Privilegio aggiornato con successo.',
        ]);

    case 'gestione_privilegi_delete':
        require_permission($auth, 'privilegi', 'ALL');
        if ($method !== 'DELETE') {
            send_json(405, ['error' => 'Metodo richiesto: DELETE']);
        }

        $idPrivilegio = (int)($input['id_privilegio'] ?? 0);
        if ($idPrivilegio <= 0) {
            send_json(422, ['error' => 'id_privilegio non valido.']);
        }

        $stmt = $mysqli->prepare('DELETE FROM Privilegi WHERE ID_privilegio = ?');
        if (!$stmt) {
            send_json(500, ['error' => 'Errore interno (delete privilegio).']);
        }
        $stmt->bind_param('i', $idPrivilegio);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        send_json(200, [
            'use_case' => $useCase,
            'deleted_rows' => $deleted,
        ]);

    default:
        send_json(404, ['error' => 'Use case non riconosciuto.']);
}
