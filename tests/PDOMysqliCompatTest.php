<?php

use PHPUnit\Framework\TestCase;

/**
 * Test PDOMysqliCompat
 * Testa il wrapper di compatibilità PDO-mysqli
 */
class PDOMysqliCompatTest extends TestCase
{
    private $mockPdo;
    private $compat;

    protected function setUp(): void
    {
        // Mock di PDO
        $this->mockPdo = $this->createMock(PDO::class);
        
        // Includiamo le classi compatibility
        $this->includeCompatClasses();
        
        // Creiamo l'istanza di PDOMysqliCompat
        $this->compat = new PDOMysqliCompat($this->mockPdo);
    }

    /**
     * Test: Costruttore inizializza correttamente
     */
    public function testConstructorInitializesProperties(): void
    {
        $this->assertInstanceOf(PDO::class, $this->compat->pdo);
        $this->assertEquals('', $this->compat->error);
    }

    /**
     * Test: prepare() ritorna PDOMysqliStmtCompat
     */
    public function testPrepareReturnsStmtCompat(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM utenti WHERE id = ?')
            ->willReturn($mockStmt);

        $result = $this->compat->prepare('SELECT * FROM utenti WHERE id = ?');
        
        $this->assertInstanceOf(PDOMysqliStmtCompat::class, $result);
    }

    /**
     * Test: prepare() cattura errori PDOException
     */
    public function testPrepareCatchesPDOException(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Prepare failed'));

        $result = $this->compat->prepare('INVALID SQL');
        
        $this->assertFalse($result);
        $this->assertStringContainsString('Prepare failed', $this->compat->error);
    }

    /**
     * Test: query() ritorna PDOMysqliResultCompat
     */
    public function testQueryReturnsResultCompat(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        
        $this->mockPdo
            ->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM utenti')
            ->willReturn($mockStmt);

        $result = $this->compat->query('SELECT * FROM utenti');
        
        $this->assertInstanceOf(PDOMysqliResultCompat::class, $result);
    }

    /**
     * Test: query() cattura errori PDOException
     */
    public function testQueryCatchesPDOException(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('query')
            ->willThrowException(new PDOException('Query failed'));

        $result = $this->compat->query('INVALID SQL');
        
        $this->assertFalse($result);
        $this->assertStringContainsString('Query failed', $this->compat->error);
    }

    /**
     * Test: begin_transaction() avvia una transazione
     */
    public function testBeginTransactionStartsTransaction(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $result = $this->compat->begin_transaction();
        
        $this->assertTrue($result);
    }

    /**
     * Test: commit() commita una transazione
     */
    public function testCommitCompletesTransaction(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $result = $this->compat->commit();
        
        $this->assertTrue($result);
    }

    /**
     * Test: rollback() ritorna true se in transazione
     */
    public function testRollbackIfInTransaction(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $this->mockPdo
            ->expects($this->once())
            ->method('rollBack')
            ->willReturn(true);

        $result = $this->compat->rollback();
        
        $this->assertTrue($result);
    }

    /**
     * Test: rollback() ritorna true se NON in transazione
     */
    public function testRollbackIfNotInTransaction(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('inTransaction')
            ->willReturn(false);

        $this->mockPdo
            ->expects($this->never())
            ->method('rollBack');

        $result = $this->compat->rollback();
        
        $this->assertTrue($result);
    }

    /**
     * Test: error property é ripulita dopo operazione riuscita
     */
    public function testErrorClearedOnSuccess(): void
    {
        $this->compat->error = 'Previous error';
        
        $mockStmt = $this->createMock(PDOStatement::class);
        $this->mockPdo
            ->expects($this->once())
            ->method('query')
            ->willReturn($mockStmt);

        $this->compat->query('SELECT 1');
        
        $this->assertEquals('', $this->compat->error);
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

        if (!class_exists('PDOMysqliCompat')) {
            eval('
                class PDOMysqliCompat
                {
                    public PDO $pdo;
                    public string $error = "";

                    public function __construct(PDO $pdo)
                    {
                        $this->pdo = $pdo;
                    }

                    public function prepare(string $sql)
                    {
                        try {
                            $stmt = $this->pdo->prepare($sql);
                            $this->error = "";
                            return new PDOMysqliStmtCompat($this, $stmt);
                        } catch (PDOException $e) {
                            $this->error = $e->getMessage();
                            return false;
                        }
                    }

                    public function query(string $sql)
                    {
                        try {
                            $stmt = $this->pdo->query($sql);
                            $this->error = "";
                            return new PDOMysqliResultCompat($stmt);
                        } catch (PDOException $e) {
                            $this->error = $e->getMessage();
                            return false;
                        }
                    }

                    public function begin_transaction(): bool
                    {
                        return $this->pdo->beginTransaction();
                    }

                    public function commit(): bool
                    {
                        return $this->pdo->commit();
                    }

                    public function rollback(): bool
                    {
                        if ($this->pdo->inTransaction()) {
                            return $this->pdo->rollBack();
                        }
                        return true;
                    }
                }
            ');
        }
    }
}
