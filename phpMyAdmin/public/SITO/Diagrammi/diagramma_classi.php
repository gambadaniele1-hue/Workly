<?php
// Esercizio: Diagramma delle Classi
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diagramma delle Classi</title>
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
    .chart-container {
      width: 70%;
      max-width: 800px;
      margin: auto;
      background: #fff;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .diagram-container {
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 10px 0 30px 0;
    }
    .diagram-container img {
      max-width: 100%;
      height: auto;
      display: block;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      cursor: zoom-in;
      transition: transform 0.15s ease;
      transform-origin: center center;
    }
  </style>
</head>
<body>

<a href="../gpo.php" class="home-btn" title="Indietro">⬅️</a>

<div class="container">
  <h1>📋 Diagramma delle Classi</h1>
  <p class="description">Diagramma UML che mostra la struttura e le relazioni tra le classi del progetto</p>
  
  <div class="diagram-container">
    <img src="DiagrammaClassi.png" alt="Diagramma delle Classi" data-zoom="1">
  </div>
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