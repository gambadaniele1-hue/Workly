<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Login — BPIC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; background: #f4f6f8; }
    .home-btn {
      position: fixed; top: 20px; left: 20px;
      background: #667eea; color: white; border: none;
      padding: 12px 16px; border-radius: 50%; font-size: 24px;
      cursor: pointer; text-decoration: none; display: flex;
      align-items: center; justify-content: center;
      width: 50px; height: 50px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s, background 0.3s;
    }
    .home-btn:hover { background: #764ba2; transform: scale(1.1); }
    .container {
      max-width: 500px; margin: 0 auto; background: #fff;
      padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
    label { display: block; margin-top: 15px; font-weight: bold; color: #333; }
    input {
      width: 100%; padding: 12px; margin-top: 8px;
      border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;
    }
    button {
      margin-top: 20px; padding: 12px 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white; border: none; border-radius: 5px;
      cursor: pointer; font-size: 16px; width: 100%; transition: transform 0.3s;
    }
    button:hover:not(:disabled) { transform: translateY(-2px); }
    button:disabled { opacity: 0.6; cursor: not-allowed; }
    .err {
      display: none;
      background: #ffecec; border: 1px solid #f5a5a5;
      padding: 10px; border-radius: 8px; margin: 12px 0; color: #c00;
    }
    a { display: inline-block; margin-top: 15px; color: #667eea; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>

<a href="../index.php" class="home-btn" title="Home">🏠</a>

<div class="container">
  <h1>Login</h1>

  <div id="error-box" class="err"></div>

  <form id="login-form" autocomplete="on">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" id="submit-btn">Entra</button>
  </form>

  <a href="/SITO/BPIC/register.php">Crea un account</a>
</div>

<script>
  // ── Redirect in base al ruolo ──────────────────────────────────────────
  function redirectByRole(roleName) {
    if (roleName === 'admin')               { window.location.href = '/SITO/BPIC/dashboard/admin.php';                return; }
    if (roleName === 'utente_abbonato')     { window.location.href = '/SITO/BPIC/dashboard/utente_abbonato.php';     return; }
    if (roleName === 'utente_non_abbonato') { window.location.href = '/SITO/BPIC/dashboard/utente_non_abbonato.php'; return; }
    // Ruolo sconosciuto o non gestito
    window.location.href = '/SITO/BPIC/unknown_role.php';
  }

  // ── All'avvio: controlla se c'è già una sessione attiva ───────────────
  // Se /api/auth/me risponde 200 l'utente è già loggato → redirect diretto
  (async function checkSession() {
    try {
      const res = await fetch('/SITO/BPIC/api/auth/me', { credentials: 'same-origin' });
      if (res.ok) {
        const user = await res.json();
        sessionStorage.setItem('user', JSON.stringify(user)); // cache dati utente
        redirectByRole(user.role_name);
      }
      // 401 = nessuna sessione attiva → mostriamo il form (non fare nulla)
    } catch (_) {
      // Errore di rete: mostriamo il form comunque
    }
  })();

  // ── Submit form: chiama /api/auth/login ───────────────────────────────
  document.getElementById('login-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn    = document.getElementById('submit-btn');
    const errBox = document.getElementById('error-box');
    btn.disabled         = true;
    errBox.style.display = 'none';

    try {
      const res = await fetch('/SITO/BPIC/api/auth/login', {
        method:      'POST',
        headers:     { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          email:    document.getElementById('email').value,
          password: document.getElementById('password').value,
        }),
      });

      const user = await res.json();

      if (res.ok) {
        // Login ok: salva i dati utente in sessionStorage e redirige
        sessionStorage.setItem('user', JSON.stringify(user));
        redirectByRole(user.role_name);
      } else {
        errBox.textContent   = user.error || 'Errore durante il login.';
        errBox.style.display = 'block';
        btn.disabled         = false;
      }
    } catch (_) {
      errBox.textContent   = 'Errore di rete. Riprova.';
      errBox.style.display = 'block';
      btn.disabled         = false;
    }
  });
</script>

</body>
</html>
