<?php
// Esercizio: Diagramma dei Casi d'Uso
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diagramma dei Casi d'Uso</title>
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
      max-width: 1000px;
      margin: 0 auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1 {
      color: #2c3e50;
      text-align: center;
      margin-bottom: 10px;
    }
    .description {
      text-align: center;
      color: #666;
      margin-bottom: 30px;
      font-size: 14px;
    }
    .diagram-container {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      border: 2px dashed #667eea;
      text-align: center;
      min-height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .diagram-container img {
      max-width: 100%;
      max-height: 600px;
      object-fit: contain;
      cursor: zoom-in;
      transition: transform 0.15s ease;
      transform-origin: center center;
    }
    .placeholder {
      color: #999;
      font-size: 16px;
    }
    .usecase-details {
      text-align: left;
      margin-top: 24px;
      background: #fcfcff;
      border: 1px solid #e6e9ee;
      padding: 18px;
      border-radius: 8px;
    }
    .usecase-details h2 {
      margin-top: 0;
      color: #2c3e50;
    }
    .usecase-details h3 {
      color: #34495e;
      margin-bottom: 6px;
    }
    .usecase-details ul,
    .usecase-details ol {
      margin-top: 6px;
      margin-bottom: 12px;
      padding-left: 20px;
      color: #444;
    }
  </style>
</head>
<body>

<a href="../gpo.php" class="home-btn" title="Indietro">⬅️</a>

<div class="container">
  <h1>🎭 Diagramma dei Casi d'Uso</h1>
  <p class="description">Diagramma UML che mostra i casi d'uso e gli attori del sistema</p>
  
  <div class="diagram-container">
    <img src="DiagrammaCasiDuso.png" alt="Diagramma dei Casi d'Uso" data-zoom="1">
  </div>
  
  <section class="usecase-details">
    <h2>Login</h2>

    <h3>Precondizioni</h3>
    <ul>
      <li>L'utente ha già un account registrato nel sistema.</li>
      <li>Le credenziali dell'utente sono presenti nel database.</li>
      <li>La pagina di login è raggiungibile dal browser.</li>
      <li>Non è già presente un token JWT valido nel cookie, altrimenti l'utente viene reindirizzato automaticamente.</li>
    </ul>

    <h3>Scenario</h3>
    <ol>
      <li>L'utente apre la pagina di login.</li>
      <li>Il sistema controlla se nel browser è presente un token JWT valido.</li>
      <li>Se il token è valido, l'utente viene portato direttamente alla propria area personale.</li>
      <li>Se il token non è valido, il sistema mostra il form di accesso.</li>
      <li>L'utente inserisce email e password.</li>
      <li>Il sistema verifica le credenziali nel database.</li>
      <li>Se i dati sono corretti, il sistema genera un nuovo JWT e lo salva in un cookie sicuro.</li>
      <li>Infine l'utente viene reindirizzato alla pagina corretta in base al proprio ruolo.</li>
    </ol>

    <h3>Postcondizioni</h3>
    <ul>
      <li>L'utente risulta autenticato nel sistema.</li>
      <li>Il browser contiene un cookie con il token JWT.</li>
      <li>L'utente può accedere alle pagine riservate in base al proprio ruolo.</li>
      <li>In caso di errore, viene mostrato un messaggio generico di credenziali non valide.</li>
    </ul>
  </section>
</div>

<script>
  (function () {
    const image = document.querySelector('.diagram-container img');
    if (!image) return;
    const minZoom = 1;
    const maxZoom = 4;
    const step = 0.15;
    image.dataset.zoom = image.dataset.zoom || '1';

    function applyZoom(scale) {
      const next = Math.min(maxZoom, Math.max(minZoom, scale));
      image.dataset.zoom = String(next);
      image.style.transform = `scale(${next})`;
      image.style.cursor = next > 1 ? 'zoom-out' : 'zoom-in';
    }

    image.addEventListener('wheel', function (e) {
      e.preventDefault();
      const cur = Number(image.dataset.zoom || '1');
      const dir = e.deltaY < 0 ? 1 : -1;
      applyZoom(cur + dir * step);
    }, { passive: false });

    image.addEventListener('dblclick', function () {
      const cur = Number(image.dataset.zoom || '1');
      applyZoom(cur > 1 ? 1 : 2);
    });

    image.addEventListener('click', function () {
      const cur = Number(image.dataset.zoom || '1');
      if (cur > 1) applyZoom(1);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') applyZoom(1);
    });
  })();
</script>

</body>
</html>
