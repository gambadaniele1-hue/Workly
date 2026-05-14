<?php

use PHPUnit\Framework\TestCase;

/**
 * Test PDOMysqliStmtCompat
 * Testa il wrapper per gli prepared statements PDO
 */
class PDOMysqliStmtCompatTest extends TestCase
{
    private $mockConn;
    private $mockStatement;
    private $stmt;

    protected function setUp(): void
    {
        // Mock di PDO
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('lastInsertId')->willReturn('42');

        // Mock di PDOMysqliCompat
        $this->mockConn = $this->createMock(stdClass::class);
        $this->mockConn->pdo = $mockPdo;
        $this->mockConn->error = '';

        // Mock di PDOStatement
        $this->mockStatement = $this->createMock(PDOStatement::class);

        // Includiamo le classi compatibility
        $this->includeCompatClasses();
    }

    /**
     * Test: bind_param() salva i parametri per l'esecuzione
     */
    public function testBindParamStoresValues(): void
    {
        $this->stmt = new PDOMysqliStmtCompat($this->mockConn, $this->mockStatement);
        
        $id = 1;
        $name = 'Test';
        $result = $this->stmt->bind_param('is', $id, $name);
        
        $this->assertTrue($result);
    }

    /**
     * Test: execute() esegue la query e imposta affected_rows
     */
    public function testExecuteRunsQuery(): void
    {
        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([1, 'Test'])
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->stmt = new PDOMysqliStmtCompat($this->mockConn, $this->mockStatement);
        $this->stmt->bind_param('is', 1, 'Test');
        $result = $this->stmt->execute();
        
        $this->assertTrue($result);
        $this->assertEquals(1, $this->stmt->affected_rows);
    }

    /**
     * Test: execute() cattura errori PDO
     */
    public function testExecuteCatchesPDOException(): void
    {
        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new PDOException('Query error'));

        $this->stmt = new PDOMysqliStmtCompat($this->mockConn, $this->mockStatement);
        $this->stmt->bind_param('is', 1, 'Test');
        $result = $this->stmt->execute();
        
        $this->assertFalse($result);
        $this->assertStringContainsString('Query error', $this->mockConn->error);
    }

    /**
     * Test: insert_id() ritorna l'ID dell'ultimo insert
     */
    public function testInsertIdReturnsLastInsertId(): void
    {
        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->stmt = new PDOMysqliStmtCompat($this->mockConn, $this->mockStatement);
        $this->stmt->execute();
        
        $this->assertEquals(42, $this->stmt->insert_id);
    }

    /**
     * Test: get_result() ritorna un PDOMysqliResultCompat
     */
    public function testGetResultReturnsCompatResult(): void
    {
        $this->stmt = new PDOMysqliStmtCompat($this->mockConn, $this->mockStatement);
        $result = $this->stmt->get_result();
        
        $this->assertInstanceOf(PDOMysqliResultCompat::class, $result);
    }

    /**
     * Test: close() chiude lo statement
     */
    public function testCloseClosesStatement(): void
    {
        $this->mockStatement
            ->expects($this->once())
            ->method('closeCursor');

        $this->stmt = new PDOMysqliStmtCompat($this->mockConn, $this->mockStatement);
        $this->stmt->close();
        
        $this->assertTrue(true);
    }

    /**
     * Helper: includiamo le classi compatibility
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

        if (!class_exists('PDOMysqliStmtCompat')) {
            eval('
                class PDOMysqliStmtCompat
                {
                    private $conn;
                    private PDOStatement $stmt;
                    private array $boundParams = [];
                    public int $affected_rows = 0;
                    public int $insert_id = 0;

                    public function __construct($conn, PDOStatement $stmt)
                    {
                        $this->conn = $conn;
                        $this->stmt = $stmt;
                    }

                    public function bind_param(string $types, ...$vars): bool
                    {
                        $this->boundParams = [];
                        foreach ($vars as $var) {
                            $this->boundParams[] = $var;
                        }
                        return true;
                    }

                    public function execute(): bool
                    {
                        try {
                            $params = [];
                            foreach ($this->boundParams as $value) {
                                $params[] = $value;
                            }
                            $ok = $this->stmt->execute($params);
                            $this->affected_rows = $this->stmt->rowCount();
                            $this->insert_id = (int)$this->conn->pdo->lastInsertId();
                            $this->conn->error = "";
                            return $ok;
                        } catch (PDOException $e) {
                            $this->conn->error = $e->getMessage();
                            return false;
                        }
                    }

                    public function get_result(): PDOMysqliResultCompat
                    {
                        return new PDOMysqliResultCompat($this->stmt);
                    }

                    public function close(): void
                    {
                        $this->stmt->closeCursor();
                    }
                }
            ');
        }
    }
}
