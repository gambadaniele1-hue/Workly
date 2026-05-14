# Test Unit - Workly

Questa cartella contiene gli unit test PHPUnit per le classi principali del progetto Workly.

## Struttura

```
tests/
├── bootstrap.php                        # File di bootstrap per i test
├── PDOMysqliCompatTest.php             # Test wrapper di compatibilità PDO
├── PDOMysqliStmtCompatTest.php         # Test prepared statements
├── PDOMysqliResultCompatTest.php       # Test risultati query
└── ArticoloTest.php                    # Test modello Articolo
```

## Classi Testate

### 1. PDOMysqliCompat ⭐⭐⭐⭐⭐
**File**: `phpMyAdmin/public/SITO/BPIC/database.php`

Wrapper di compatibilità che adatta PDO all'interfaccia mysqli. Fornisce astrazione dal database.

**Test coperti**:
- Inizializzazione costruttore
- Metodo `prepare()` - preparazione query
- Metodo `query()` - esecuzione query diretta
- Metodo `begin_transaction()` - inizio transazione
- Metodo `commit()` - commit transazione
- Metodo `rollback()` - rollback transazione
- Gestione eccezioni PDOException
- Pulizia error property dopo successo

### 2. PDOMysqliStmtCompat ⭐⭐⭐⭐
**File**: `phpMyAdmin/public/SITO/BPIC/database.php`

Wrapper per prepared statements. Gestisce binding di parametri e esecuzione.

**Test coperti**:
- Metodo `bind_param()` - binding parametri
- Metodo `execute()` - esecuzione con gestione rowCount e insert_id
- Metodo `get_result()` - restituzione risultati
- Metodo `close()` - chiusura statement
- Gestione errori PDOException
- Tracking affected_rows
- Tracking insert_id

### 3. PDOMysqliResultCompat ⭐⭐⭐⭐
**File**: `phpMyAdmin/public/SITO/BPIC/database.php`

Wrapper per i risultati di query. Fornisce interfaccia compatibile per fetching.

**Test coperti**:
- Metodo `fetch_assoc()` - fetch riga associativa
- Metodo `fetch_assoc()` - null quando esaurite le righe
- Metodo `fetch_fields()` - metadati colonne
- Metodo `free()` - chiusura cursor
- Gestione eccezioni

### 4. Articolo ⭐⭐⭐
**File**: `phpMyAdmin/public/SITO/CreazioneArticoli/articolo.php`

Classe modello che rappresenta un articolo nel sistema.

**Test coperti**:
- Inizializzazione costruttore con 4 proprietà
- Modifica proprietà `nome`
- Modifica proprietà `descrizione`
- Modifica proprietà `prezzo`
- Modifica proprietà `immagine`
- Metodo `show()` - generazione HTML card
- Struttura HTML corretta (div, img, card-body, ecc)
- Escaping XSS con htmlspecialchars
- Prezzi speciali (zero, decimali alti)
- Indipendenza tra più istanze

## Esecuzione dei Test

### Prerequisiti

```bash
# Installare dipendenze (incluso PHPUnit)
composer install
```

### Eseguire tutti i test

```bash
./vendor/bin/phpunit
```

### Eseguire test di una classe specifica

```bash
# Test PDOMysqliCompat
./vendor/bin/phpunit tests/PDOMysqliCompatTest.php

# Test Articolo
./vendor/bin/phpunit tests/ArticoloTest.php
```

### Eseguire test con output verboso

```bash
./vendor/bin/phpunit --verbose
```

### Eseguire test con coverage HTML

```bash
./vendor/bin/phpunit --coverage-html coverage/
```

## Configurazione

Il file `phpunit.xml` nella root configura:
- Bootstrap: `tests/bootstrap.php`
- Directory test: `tests/`
- Coverage include: `phpMyAdmin/public/SITO/BPIC/**` e `phpMyAdmin/public/SITO/CreazioneArticoli/**`
- Output con colori e verbose

## Mocking

I test utilizzano mock di PHPUnit per isolamento:
- **PDO** mockato per evitare connessioni al database reale
- **PDOStatement** mockato per controllare il comportamento
- **PDOException** per testare scenari di errore

## Note Importanti

1. **Nessuna connessione DB reale**: Tutti i test usano mock, quindi NON richiedono un database attivo.

2. **Classi senza namespace**: Le classi PDOMysqli* non hanno namespace, quindi vengono definite dinamicamente nei test usando `eval()`.

3. **Articolo**: La classe Articolo viene inclusa direttamente dal suo file sorgente.

4. **HTML escaping**: ArticoloTest verifica che `show()` esegua `htmlspecialchars()` per prevenire XSS.

## Risultati Attesi

Tutti i test devono passare (✓ 40+ assertions):

```
OK (4 tests, 40 assertions)
```

Se un test fallisce, controllare:
- La classe testata non è stata modificata in modo incompatibile
- Le dipendenze sono installate (`composer install`)
- PHP >= 7.4 è disponibile

## Estensioni Future

Per aggiungere più test:
1. Creare un nuovo file `Test.php` in `tests/`
2. Estendere `PHPUnit\Framework\TestCase`
3. Scrivere metodi con prefisso `test`
4. Eseguire `./vendor/bin/phpunit`

Esempio template:
```php
<?php
use PHPUnit\Framework\TestCase;

class MiaClasseTest extends TestCase {
    public function testQualcosa(): void {
        $this->assertEquals(expected, actual);
    }
}
```
