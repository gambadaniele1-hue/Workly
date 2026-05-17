<?php
/**
 * File: login.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);
require_once __DIR__ . "/database.php";

session_start();

if (!empty($_SESSION['user_id'])) {
  $roleNames = array_map(static fn(array $r): string => (string)($r['name'] ?? ''), $_SESSION['roles'] ?? []);
  if (in_array('tenant', $roleNames, true)) {
    header('Location: /SITO/BPIC/tenant_dashboard.php');
    exit;
  }
  header('Location: /SITO/BPIC/home.php');
  exit;
}

$errors = [];
$generatedToken = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
    $email = trim($_POST["email"] ?? "");
    $password = (string)($_POST["password"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email non valida.";
    } else {
      $stmt = $pdo->prepare(" 
            SELECT ID_utente, Email, Password_hash
            FROM Utenti
            WHERE Email = ?
            LIMIT 1
        ");
      try {
        $stmt->execute([$email]);
        $user = $stmt->fetch();
      } catch (PDOException $e) {
        $errors[] = "Errore interno (execute).";
        $user = null;
      }


// ===== SEZIONE 3: LOGICA DI PROCESSO =====
      if (!$user) {
        $errors[] = "Email non trovata nel sistema.";
      } elseif (!password_verify($password, $user["Password_hash"])) {
        $errors[] = "❌ Password errata. Riprova.";
      } else {
        // Genera un token JWT visibile all'utente e non imposta ancora la sessione.
        // L'utente dovrà validare il token (tramite la form) per essere reindirizzato alla dashboard.
        require_once __DIR__ . '/api/jwt.php';
        $ttlSeconds = 600; // durata in secondi
        $generatedToken = create_jwt((int)$user['ID_utente'], $ttlSeconds, JWT_SECRET);
        // Non eseguire redirect qui: il token viene mostrato nella vista e l'utente lo valida tramite validate_token.php
        // in modo che solo dopo la validazione la sessione venga impostata e si entri nella dashboard.
        }
    }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">

  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 40px;
      background: #f4f6f8;
    }
    .home-btn {
      position: fixed;
      top: 20px;
      left: 20px;
      background: #667eea;
      color: white;
      border: none;
      padding: 12px 16px;
      border-radius: 50%;
      font-size: 24px;
      cursor: pointer;
      text-decoration: none;

      display: flex;
      align-items: center;
      justify-content: center;
      width: 50px;
      height: 50px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      transition: transform 0.3s, background 0.3s;
    }
    .home-btn:hover {
      background: #764ba2;
      transform: scale(1.1);
    }
    .container {
      max-width: 500px;
      margin: 0 auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    h1 {
      color: #2c3e50;
      text-align: center;
      margin-bottom: 30px;
    }
    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
      color: #333;
    }
    input {
      width: 100%;
      padding: 12px;
      margin-top: 8px;
      border: 1px solid #ddd;
      border-radius: 5px;
      box-sizing: border-box;
    }
    button {

      margin-top: 20px;
      padding: 12px 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      width: 100%;
      transition: transform 0.3s;
    }
    button:hover {
      transform: translateY(-2px);
    }
    .err {
      background: #ffecec;
      border: 1px solid #f5a5a5;
      padding: 10px;
      border-radius: 8px;
      margin: 12px 0;

    }
    a {
      display: inline-block;
      margin-top: 15px;
      color: #667eea;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    .token-box {
      max-width: 500px;
      margin: 0 auto;
      background: #f9f9f9;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #eee;
    }
    .token-actions {
      display: flex;

      gap: 10px;
      margin-top: 10px;
      align-items: center;
    }
    .btn-secondary {
      background: #eef2ff;
      color: #3730a3;
      border: 1px solid #c7d2fe;
    }
    .token-hint {
      font-size: 14px;
      color: #555;
    }
    .copy-status {
      font-size: 13px;
      color: #1f6feb;
      min-height: 18px;
    }
  </style>
</head>

<body>

<a href="../index.php" class="home-btn" title="Home">🏠</a>

<div class="container">
  <h1>Login</h1>

  <?php if ($errors): ?>
    <div class="err">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e, ENT_QUOTES, "UTF-8") ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" autocomplete="on">
    <label>Email</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>">


    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit">Entra</button>
  </form>

  <?php if (!empty($generatedToken)): ?>
    <hr>
    <h2 style="text-align:center;">Token generato</h2>
    <div class="token-box">
      <p class="token-hint">Questo e il tuo token JWT (scade in <?= $ttlSeconds ?? 600 ?> secondi). Copialo e incollalo nella casella di validazione qui sotto.</p>
      <textarea id="generated-token" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;" readonly><?= htmlspecialchars($generatedToken, ENT_QUOTES, 'UTF-8') ?></textarea>
      <div class="token-actions">
        <button type="button" class="btn-secondary" id="copy-token">Copia token</button>
        <span class="copy-status" id="copy-status" aria-live="polite"></span>
      </div>
    </div>
    <hr>
  <?php endif; ?>


  <h2 style="text-align:center;">Hai un token?</h2>
  <form method="post" action="/SITO/BPIC/validate_token.php" style="max-width:500px;margin:0 auto;">
    <label>Inserisci qui il token (JWT)</label>
    <textarea name="token" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;"></textarea>
    <button type="submit">Valida token e accedi</button>
  </form>

  <a href="/SITO/BPIC/register.php">Crea un account</a>
</div>

<script>
  const copyBtn = document.getElementById('copy-token');
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      const tokenField = document.getElementById('generated-token');
      const status = document.getElementById('copy-status');
      if (!tokenField || !status) {
        return;
      }

      try {
        await navigator.clipboard.writeText(tokenField.value);
        status.textContent = 'Token copiato.';
      } catch (err) {
        status.textContent = 'Copia non riuscita.';
      }
    });
  }
</script>

</body>
</html>