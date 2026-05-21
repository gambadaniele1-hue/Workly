<?php
declare(strict_types=1);

/**
 * Per eseguire: ./vendor/bin/phpunit tests/JwtTest.php
 *
 * Testa le funzioni JWT (create_jwt / verify_jwt) in api/jwt.php.
 * Sono funzioni pure: niente DB, niente HTTP, niente mock.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../phpMyAdmin/public/SITO/BPIC/api/jwt.php';

class JwtTest extends TestCase
{
    private string $secret = 'chiave_di_test_lunga_almeno_32_caratteri';

    // Token valido → verify restituisce il payload con user_id corretto
    public function test_token_valido(): void
    {
        $token   = create_jwt(42, 3600, $this->secret);
        $payload = verify_jwt($token, $this->secret);

        $this->assertIsArray($payload);
        $this->assertSame(42, $payload['user_id']);
    }

    // Token scaduto → verify restituisce null
    public function test_token_scaduto(): void
    {
        $token  = create_jwt(1, -1, $this->secret); // TTL -1 = già scaduto
        $result = verify_jwt($token, $this->secret);

        $this->assertNull($result);
    }

    // Secret sbagliato → verify restituisce null
    public function test_secret_sbagliato(): void
    {
        $token  = create_jwt(1, 3600, $this->secret);
        $result = verify_jwt($token, 'secret_sbagliato');

        $this->assertNull($result);
    }

    // Token corrotto → verify restituisce null
    public function test_token_corrotto(): void
    {
        $result = verify_jwt('questo.non.e.un.token', $this->secret);

        $this->assertNull($result);
    }
}
