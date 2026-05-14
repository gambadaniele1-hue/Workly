<?php

use PHPUnit\Framework\TestCase;

// Includiamo la classe Articolo dal file sorgente
require_once __DIR__ . '/../phpMyAdmin/public/SITO/CreazioneArticoli/articolo.php';

/**
 * Test Articolo
 * Testa la classe modello per la gestione degli articoli
 */
class ArticoloTest extends TestCase
{
    private Articolo $articolo;

    protected function setUp(): void
    {
        $this->articolo = new Articolo(
            'Laptop Gaming',
            'Laptop ad alte prestazioni per gaming',
            1299.99,
            'laptop.jpg'
        );
    }

    /**
     * Test: Costruttore inizializza correttamente le proprietà
     */
    public function testConstructorInitializesProperties(): void
    {
        $this->assertEquals('Laptop Gaming', $this->articolo->nome);
        $this->assertEquals('Laptop ad alte prestazioni per gaming', $this->articolo->descrizione);
        $this->assertEquals(1299.99, $this->articolo->prezzo);
        $this->assertEquals('laptop.jpg', $this->articolo->immagine);
    }

    /**
     * Test: proprietà nome può essere modificata
     */
    public function testNomePropertyCanBeModified(): void
    {
        $this->articolo->nome = 'Laptop Nuovo';
        $this->assertEquals('Laptop Nuovo', $this->articolo->nome);
    }

    /**
     * Test: proprietà descrizione può essere modificata
     */
    public function testDescrizionePropertyCanBeModified(): void
    {
        $newDesc = 'Descrizione aggiornata';
        $this->articolo->descrizione = $newDesc;
        $this->assertEquals($newDesc, $this->articolo->descrizione);
    }

    /**
     * Test: proprietà prezzo può essere modificata
     */
    public function testPrezzoPropertyCanBeModified(): void
    {
        $this->articolo->prezzo = 999.99;
        $this->assertEquals(999.99, $this->articolo->prezzo);
    }

    /**
     * Test: proprietà immagine può essere modificata
     */
    public function testImmaginePropertyCanBeModified(): void
    {
        $this->articolo->immagine = 'nuova_immagine.png';
        $this->assertEquals('nuova_immagine.png', $this->articolo->immagine);
    }

    /**
     * Test: show() genera HTML corretto
     */
    public function testShowGeneratesValidHtml(): void
    {
        ob_start();
        $this->articolo->show();
        $html = ob_get_clean();

        // Verifichiamo che il contenuto sia presente nel HTML
        $this->assertStringContainsString('Laptop Gaming', $html);
        $this->assertStringContainsString('Laptop ad alte prestazioni per gaming', $html);
        $this->assertStringContainsString('1299.99', $html);
        $this->assertStringContainsString('IMG/laptop.jpg', $html);
    }

    /**
     * Test: show() contiene la struttura card corretta
     */
    public function testShowContainsCardStructure(): void
    {
        ob_start();
        $this->articolo->show();
        $html = ob_get_clean();

        // Verifichiamo tag card
        $this->assertStringContainsString('class="card"', $html);
        $this->assertStringContainsString('class="card-img-top"', $html);
        $this->assertStringContainsString('class="card-body"', $html);
        $this->assertStringContainsString('class="card-title"', $html);
        $this->assertStringContainsString('class="card-text"', $html);
    }

    /**
     * Test: show() escapeHtmlSpecialChars per sicurezza
     */
    public function testShowEscapesHtmlSpecialCharacters(): void
    {
        // Creiamo un articolo con caratteri speciali
        $articoloSpeciale = new Articolo(
            'Test <script>alert("XSS")</script>',
            'Descrizione con & <tag>',
            99.99,
            'img&test.jpg'
        );

        ob_start();
        $articoloSpeciale->show();
        $html = ob_get_clean();

        // Verifichiamo che i caratteri speciali sono escapati
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    /**
     * Test: show() con prezzo formattato
     */
    public function testShowFormattedPrice(): void
    {
        ob_start();
        $this->articolo->show();
        $html = ob_get_clean();

        // Verifichiamo che il prezzo e l'Euro siano presenti
        $this->assertStringContainsString('€', $html);
        $this->assertStringContainsString('1299.99', $html);
    }

    /**
     * Test: show() con prezzo zero
     */
    public function testShowWithZeroPrice(): void
    {
        $articoloGratis = new Articolo('Articolo Gratis', 'Articolo omaggio', 0, 'free.jpg');
        
        ob_start();
        $articoloGratis->show();
        $html = ob_get_clean();

        $this->assertStringContainsString('0', $html);
    }

    /**
     * Test: show() con prezzo decimale alto
     */
    public function testShowWithHighDecimalPrice(): void
    {
        $articoloCaro = new Articolo('Articolo Lusso', 'Articolo premium', 9999.99, 'luxury.jpg');
        
        ob_start();
        $articoloCaro->show();
        $html = ob_get_clean();

        $this->assertStringContainsString('9999.99', $html);
    }

    /**
     * Test: Creazione articoli multipli indipendenti
     */
    public function testMultipleArticoliAreIndependent(): void
    {
        $articolo2 = new Articolo('Mouse', 'Mouse gaming', 49.99, 'mouse.jpg');
        
        // Verifichiamo che i due articoli sono indipendenti
        $this->assertNotEquals($this->articolo->nome, $articolo2->nome);
        $this->assertNotEquals($this->articolo->prezzo, $articolo2->prezzo);
        
        // Modificare uno non influisce sull'altro
        $this->articolo->nome = 'Laptop Modificato';
        $this->assertEquals('Mouse', $articolo2->nome);
    }
}
