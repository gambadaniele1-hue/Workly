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
    <img src="DiagrammaCasiDuso.png" alt="Diagramma dei Casi d'Uso">
  </div>
  
  <section class="usecase-details">
    <h2>Generazione busta paga senza PDF</h2>

    <h3>Precondizioni</h3>
    <ul>
      <li>L'utente è autenticato e ha i permessi per generare buste paga.</li>
      <li>I dati anagrafici e retributivi del dipendente sono presenti nel database.</li>
      <li>È definito il periodo di competenza e la configurazione delle voci retributive.</li>
      <li>Il servizio di generazione PDF è disabilitato o non richiesto per questo caso.</li>
    </ul>

    <h3>Scenario</h3>
    <ol>
      <li>L'utente seleziona il dipendente e il periodo di competenza dalla UI.</li>
      <li>Il sistema calcola imponibili, trattenute, contributi e netto a pagare.</li>
      <li>Il sistema salva la busta paga nel database senza avviare la creazione del PDF.</li>
      <li>Il sistema aggiorna lo stato della busta (es. "generata - senza PDF") e notifica l'utente.</li>
    </ol>

    <h3>Postcondizioni</h3>
    <ul>
      <li>La busta paga è registrata nel database con stato che indica l'assenza del PDF.</li>
      <li>I risultati dei calcoli sono disponibili per consultazione e verifica nello storico.</li>
      <li>La generazione del PDF può essere eseguita in un secondo momento come job separato.</li>
      <li>Viene creato un log dell'operazione per scopi di audit e tracciamento.</li>
    </ul>
  </section>
</div>

</body>
</html>
