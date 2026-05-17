<?php
declare(strict_types=1);

// Wrapper JWT: usa firebase/php-jwt se installata, altrimenti fallback minimale.
if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', getenv('JWT_SECRET') ?: 'cambia_questa_chiave_super_segreta');
}

// Try to load composer autoload if present
$composerAutoload = __DIR__ . '/../../../../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

use Exception;

function create_jwt(int $userId, int $ttlSeconds, string $secret, array $extraClaims = []): string
{
    // Prefer firebase/php-jwt if available
    if (class_exists('\\Firebase\\JWT\\JWT')) {
        $now = time();
        $payload = array_merge([
            'user_id' => $userId,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ], $extraClaims);

        // Firebase v6 uses static methods on JWT and separate Key for decoding; encode remains JWT::encode
        return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
    }

    // Fallback: minimal JWT HS256 implementation
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now = time();
    $payload = array_merge([
        'user_id' => $userId,
        'iat' => $now,
        'exp' => $now + $ttlSeconds,
    ], $extraClaims);

    $enc = static function ($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    };

    $encodedHeader = $enc(json_encode($header));
    $encodedPayload = $enc(json_encode($payload));
    $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true);
    $encodedSignature = $enc($signature);
    return $encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature;
}

function verify_jwt(string $token, string $secret): ?array
{
    if (class_exists('\\Firebase\\JWT\\JWT')) {
        try {
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
            // convert object to assoc array
            return json_decode(json_encode($decoded), true);
        } catch (Exception $e) {
            return null;
        }
    }

    // Fallback decode
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$h, $p, $s] = $parts;
    $dec = static function ($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    };

    $payloadJson = $dec($p);
    if ($payloadJson === false) {
        return null;
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return null;
    }

    // verify signature
    $expected = hash_hmac('sha256', $h . '.' . $p, $secret, true);
    $sig = $dec($s);
    if ($sig === false || !hash_equals($expected, $sig)) {
        return null;
    }

    if (isset($payload['exp']) && time() >= (int)$payload['exp']) {
        return null;
    }

    return $payload;
}
