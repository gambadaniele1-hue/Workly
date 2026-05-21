<?php
declare(strict_types=1);

/*
 * AuthController — Login, registrazione e info utente corrente.
 *
 * Rotte:
 *   POST /api/auth/login    — verifica credenziali, setta cookie JWT
 *   POST /api/auth/register — crea account, setta cookie JWT
 *   GET  /api/auth/me       — ritorna i dati dell'utente dal cookie (richiede auth)
 *
 * Login e register ritornano i dati utente (non un redirect):
 * è il frontend a decidere dove andare in base a role_name.
 */
class AuthController
{
    private const TTL = 3600; // durata cookie JWT: 1 ora

    public function __construct(
        private PDO         $pdo,
        private array|null  $currentUser = null  // null per rotte pubbliche
    ) {}

    // ── POST /api/auth/login ──────────────────────────────────────────────
    public function login(): void
    {
        $input    = $this->body();
        $email    = trim((string)($input['email']    ?? ''));
        $password = (string)($input['password'] ?? '');

        // Messaggio generico: non riveliamo se l'email esiste
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $this->json(400, ['error' => 'Credenziali non valide.']);
        }

        $stmt = $this->pdo->prepare(
            'SELECT ID_utente, Password_hash, N_Telefono, ID_ruolo FROM Utenti WHERE Email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['Password_hash'])) {
            $this->json(401, ['error' => 'Credenziali non valide.']);
        }

        $roleId   = (int)$user['ID_ruolo'];
        $roleName = $this->getRoleName($roleId);

        $this->issueToken((int)$user['ID_utente'], $roleId, $roleName);

        // Il frontend usa role_name per decidere il redirect
        $this->json(200, $this->userPayload(
            (int)$user['ID_utente'],
            $roleId,
            $roleName,
            $email,
            (string)($user['N_Telefono'] ?? '')
        ));
    }

    // ── POST /api/auth/register ───────────────────────────────────────────
    public function register(): void
    {
        $input    = $this->body();
        $email    = trim((string)($input['email']      ?? ''));
        $password = (string)($input['password']  ?? '');
        $telefono = trim((string)($input['n_telefono'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $this->json(422, ['error' => 'Email non valida o password troppo corta (min 6 caratteri).']);
        }

        // Ruolo di default: 'utente' oppure ID 3 come fallback
        $stmtRuolo = $this->pdo->prepare("SELECT ID_ruolo FROM Ruoli WHERE Nome_ruolo = 'utente' LIMIT 1");
        $stmtRuolo->execute();
        $ruolo   = $stmtRuolo->fetch();
        $idRuolo = $ruolo ? (int)$ruolo['ID_ruolo'] : 3;

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO Utenti (Email, Password_hash, N_Telefono, ID_ruolo) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$email, password_hash($password, PASSWORD_BCRYPT), $telefono ?: null, $idRuolo]);
            $newId = (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate')) {
                $this->json(409, ['error' => 'Email già registrata.']);
            }
            $this->json(500, ['error' => 'Errore durante la registrazione.']);
        }

        $roleName = $this->getRoleName($idRuolo);
        $this->issueToken($newId, $idRuolo, $roleName);

        $this->json(201, $this->userPayload($newId, $idRuolo, $roleName, $email, $telefono));
    }

    // ── GET /api/auth/me ──────────────────────────────────────────────────
    // Usato dal frontend all'avvio per sapere se c'è una sessione attiva.
    // Se il cookie è valido ritorna i dati utente (salvabili in sessionStorage).
    // Se non c'è cookie o è scaduto il router risponde già 401 prima di arrivare qui.
    public function me(): void
    {
        // Recupera email e telefono dal DB (non sono nel JWT)
        $stmt = $this->pdo->prepare(
            'SELECT Email, N_Telefono FROM Utenti WHERE ID_utente = ? LIMIT 1'
        );
        $stmt->execute([$this->currentUser['user_id']]);
        $row = $stmt->fetch();

        $this->json(200, $this->userPayload(
            $this->currentUser['user_id'],
            $this->currentUser['role_id'],
            $this->currentUser['role_name'],
            (string)($row['Email']      ?? ''),
            (string)($row['N_Telefono'] ?? '')
        ));
    }

    // ── Metodi privati ────────────────────────────────────────────────────

    /** Struttura dati utente restituita da login, register e me. */
    private function userPayload(
        int $userId, int $roleId, string $roleName, string $email, string $telefono
    ): array {
        return [
            'user_id'    => $userId,
            'role_id'    => $roleId,
            'role_name'  => $roleName,
            'email'      => $email,
            'n_telefono' => $telefono,
        ];
    }

    /** Recupera il nome del ruolo dal DB. */
    private function getRoleName(int $idRuolo): string
    {
        $stmt = $this->pdo->prepare('SELECT Nome_ruolo FROM Ruoli WHERE ID_ruolo = ? LIMIT 1');
        $stmt->execute([$idRuolo]);
        return (string)($stmt->fetchColumn() ?: '');
    }

    /** Crea JWT e setta il cookie HttpOnly. */
    private function issueToken(int $userId, int $roleId, string $roleName): void
    {
        $token = create_jwt($userId, self::TTL, JWT_SECRET, [
            'role_id'   => $roleId,
            'role_name' => $roleName,
        ]);

        setcookie('jwt', $token, [
            'expires'  => time() + self::TTL,
            'path'     => '/',
            'httponly' => true,   // JS non può leggere il cookie (protezione XSS)
            'samesite' => 'Strict',
            // 'secure' => true,  // decommentare in produzione (HTTPS)
        ]);
    }

    /** Legge il body JSON della richiesta. */
    private function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /** Risponde con JSON e termina. */
    private function json(int $status, array $data): never
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
