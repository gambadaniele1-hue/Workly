<?php
// Script di debug per verificare lo stato del database
session_start();

// Simula l'utente loggato
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['email'] = 'test@example.com';
}

require_once __DIR__ . '/phpMyAdmin/public/SITO/BPIC/database.php';

echo "<h2>🔍 Debug Database Busta Paga</h2>";

// 1. Verifica struttura tabella
echo "<h3>1. Struttura tabella Busta_paga:</h3>";
try {
    $result = $pdo->query("DESCRIBE Busta_paga");
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td style='padding: 8px'>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Errore: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. Verifica indici
echo "<h3>2. Indici della tabella:</h3>";
try {
    $result = $pdo->query("SHOW INDEX FROM Busta_paga");
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0'>";
    echo "<tr><th>Table</th><th>Non_unique</th><th>Key_name</th><th>Column_name</th><th>Seq_in_index</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td style='padding: 8px'>" . htmlspecialchars($row['Table'] ?? '') . "</td>";
        echo "<td style='padding: 8px'>" . htmlspecialchars($row['Non_unique'] ?? '') . "</td>";
        echo "<td style='padding: 8px'>" . htmlspecialchars($row['Key_name'] ?? '') . "</td>";
        echo "<td style='padding: 8px'>" . htmlspecialchars($row['Column_name'] ?? '') . "</td>";
        echo "<td style='padding: 8px'>" . htmlspecialchars($row['Seq_in_index'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Errore: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 3. Prova un INSERT di test
echo "<h3>3. Test INSERT:</h3>";
try {
    $testMonth = date('Y-m');
    $ins = $pdo->prepare('INSERT INTO Busta_paga (Mese_riferimento, Stipendio_lordo, Stipendio_netto, Ore_lavorate, Paga_oraria) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([
        $testMonth,
        1000.00,
        700.00,
        168,
        10.00
    ]);
    $bustaId = $pdo->lastInsertId();
    echo "<p style='color: green'>✅ INSERT riuscito! ID busta generato: <strong>$bustaId</strong></p>";
    
    // Pulisci il record di test
    $pdo->prepare('DELETE FROM Busta_paga WHERE ID_busta = ?')->execute([$bustaId]);
    echo "<p style='color: blue'>ℹ️ Record di test eliminato.</p>";
} catch (PDOException $e) {
    echo "<p style='color: red'>❌ Errore INSERT: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Codice errore: " . $e->getCode() . "</p>";
}

// 4. Conta record storici
echo "<h3>4. Dati storici:</h3>";
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM Busta_paga")->fetch()['cnt'];
    echo "<p>Record presenti: <strong>$count</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Errore: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p style='color: #666; font-size: 12px'>Controlla le informazioni sopra per capire lo stato del database.</p>";
?>
