<?php
/**
 * File: register.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);
require_once __DIR__ . "/database.php";

session_start();

// INLINE COMMENT: Conditional logic or loop processing
if (!empty($_SESSION['user_id'])) {
  $roleNames = array_map(static fn(array $r): string => (string)($r['name'] ?? ''), $_SESSION['roles'] ?? []);
// INLINE COMMENT: Conditional logic or loop processing
  if (in_array('tenant', $roleNames, true)) {
    header('Location: /SITO/BPIC/tenant_dashboard.php');
    exit;
  }
  header('Location: /SITO/BPIC/home.php');
  exit;
}

$errors = [];
$ok = false;

// INLINE COMMENT: Conditional logic or loop processing
if ($_SERVER["REQUEST_METHOD"] === "POST") {

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
    $email    = trim($_POST["email"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $password = (string)($_POST["password"] ?? "");

// INLINE COMMENT: Conditional logic or loop processing
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email non valida.";
    }
// INLINE COMMENT: Conditional logic or loop processing
    if (strlen($password) < 8) {
        $errors[] = "Password troppo corta (minimo 8 caratteri).";
    }

// INLINE COMMENT: Conditional logic or loop processing
    if (!$errors) {
      try {

/* BLOCK COMMENT: SQL Query execution to interact with database records */
        $stmt = $pdo->prepare("SELECT ID_utente FROM Utenti WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $exists = $stmt->fetch();

// INLINE COMMENT: Conditional logic or loop processing
        if ($exists) {
          $errors[] = "Email già registrata.";
        } else {

// ===== SEZIONE 3: LOGICA DI PROCESSO =====
          $passwordHash = password_hash($password, PASSWORD_BCRYPT);
          $telefonoParam = ($telefono !== "") ? $telefono : null;

          $pdo->beginTransaction();


/* BLOCK COMMENT: SQL Query execution to interact with database records */
          $stmt = $pdo->prepare("INSERT INTO Utenti (N_Telefono, Email, ID_busta, Password_hash) VALUES (?, ?, ?, ?)");
          $stmt->execute([$telefonoParam, $email, null, $passwordHash]);


/* BLOCK COMMENT: SQL Query execution to interact with database records */
          $stmt2 = $pdo->prepare("INSERT INTO Utente_Ruolo (email_utente, ID_ruolo) VALUES (?, 3)");
          $stmt2->execute([$email]);

          $pdo->commit();
          $ok = true;
        }
      } catch (PDOException $e) {
// INLINE COMMENT: Conditional logic or loop processing
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $errors[] = "Errore durante la registrazione.";
      }

// ===== SEZIONE 4: LOGICA DI PROCESSO =====
    }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Registrazione</title>
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

// ===== SEZIONE 5: LOGICA DI PROCESSO =====
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

// ===== SEZIONE 6: LOGICA DI PROCESSO =====
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

// ===== SEZIONE 7: LOGICA DI PROCESSO =====
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

// ===== SEZIONE 8: LOGICA DI PROCESSO =====
    }
    .err {
      background: #ffecec;
      border: 1px solid #f5a5a5;
      padding: 10px;
      border-radius: 8px;
      margin: 12px 0;
    }
    .ok {
      background: #eaffea;
      border: 1px solid #9ee49e;
      padding: 10px;
      border-radius: 8px;
      margin: 12px 0;
    }
    a {
      display: inline-block;
      margin-top: 15px;
      color: #667eea;
      text-decoration: none;

// ===== SEZIONE 9: LOGICA DI PROCESSO =====
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<a href="../index.php" class="home-btn" title="Home">🏠</a>

<div class="container">
  <h1>Registrazione</h1>

// INLINE COMMENT: Conditional logic or loop processing
  <?php if ($ok): ?>
    <div class="ok">Registrazione completata! Ora puoi fare il login.</div>
  <?php endif; ?>

// INLINE COMMENT: Conditional logic or loop processing
  <?php if ($errors): ?>
    <div class="err">
      <ul>

// ===== SEZIONE 10: LOGICA DI PROCESSO =====
// INLINE COMMENT: Conditional logic or loop processing
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e, ENT_QUOTES, "UTF-8") ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" autocomplete="on">
    <label>Email</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>">

    <label>Telefono (opzionale)</label>
    <input type="text" name="telefono" value="<?= htmlspecialchars($_POST["telefono"] ?? "", ENT_QUOTES, "UTF-8") ?>">

    <label>Password</label>
    <input type="password" name="password" required minlength="8">

    <button type="submit">Registrati</button>
  </form>


// ===== SEZIONE 11: LOGICA DI PROCESSO =====
  <a href="/SITO/BPIC/login.php">Vai al login</a>
</div>

</body>
</html>