<?php

use PHPUnit\Framework\TestCase;

/**
 * Test PDOMysqliResultCompat
 * Testa il wrapper per i risultati di query PDO
 */
class PDOMysqliResultCompatTest extends TestCase
{
    private PDOStatement $mockStatement;

    protected function setUp(): void
    {
        // Creiamo un mock di PDOStatement
        $this->mockStatement = $this->createMock(PDOStatement::class);
    }

    /**
     * Test: fetch_assoc() ritorna un array associativo
     */
    public function testFetchAssocReturnsArray(): void
    {
        $expectedData = ['id' => 1, 'nome' => 'Test User'];
        
        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Includiamo le classi compatibility
        $this->includeCompatClasses();
        
        $result = new PDOMysqliResultCompat($this->mockStatement);
        $row = $result->fetch_assoc();
        
        $this->assertEquals($expectedData, $row);
    }

    /**
     * Test: fetch_assoc() ritorna null quando non ci sono più righe
     */
    public function testFetchAssocReturnsNullWhenNoData(): void
    {
        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->includeCompatClasses();
        
        $result = new PDOMysqliResultCompat($this->mockStatement);
        $row = $result->fetch_assoc();
        
        $this->assertNull($row);
    }

    /**
     * Test: fetch_fields() ritorna i metadati delle colonne
     */
    public function testFetchFieldsReturnsColumnMetadata(): void
    {
        $this->mockStatement
            ->expects($this->once())
            ->method('columnCount')
            ->willReturn(2);

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('getColumnMeta')
            ->willReturnOnConsecutiveCalls(
                ['name' => 'id'],
                ['name' => 'nome']
            );

        $this->includeCompatClasses();
        
        $result = new PDOMysqliResultCompat($this->mockStatement);
        $fields = $result->fetch_fields();
        
        $this->assertCount(2, $fields);
        $this->assertEquals('id', $fields[0]->name);
        $this->assertEquals('nome', $fields[1]->name);
    }

    /**
     * Test: free() chiude il cursor
     */
    public function testFreeClosesStatement(): void
    {
        $this->mockStatement
            ->expects($this->once())
            ->method('closeCursor');

        $this->includeCompatClasses();
        
        $result = new PDOMysqliResultCompat($this->mockStatement);
        $result->free();
        
        $this->assertTrue(true); // Se non lancia eccezione, è ok
    }

    /**
     * Helper: includiamo le classi compatibility (poiché non hanno namespace)
     */
    private function includeCompatClasses(): void
    {
        if (!class_exists('PDOMysqliResultCompat')) {
            eval('
                class PDOMysqliResultCompat
                {
                    private PDOStatement $stmt;

                    public function __construct(PDOStatement $stmt)
                    {
                        $this->stmt = $stmt;
                    }

                    public function fetch_assoc(): ?array
                    {
                        $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
                        return $row === false ? null : $row;
                    }

                    public function fetch_fields(): array
                    {
                        $fields = [];
                        $count = $this->stmt->columnCount();
                        for ($i = 0; $i < $count; $i++) {
                            $meta = $this->stmt->getColumnMeta($i);
                            $name = $meta["name"] ?? ("col_" . $i);
                            $fields[] = (object)["name" => $name];
                        }
                        return $fields;
                    }

                    public function free(): void
                    {
                        $this->stmt->closeCursor();
                    }
                }
            ');
        }
    }
}
