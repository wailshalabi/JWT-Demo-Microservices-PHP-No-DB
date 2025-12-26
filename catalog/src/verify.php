<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function get_bearer_token(): string {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $hdr, $m)) {
        throw new RuntimeException('Missing Bearer token');
    }
    return $m[1];
}

function verify_jwt(string $jwt): object {
    $issuer = getenv('JWT_ISSUER') ?: 'https://localhost';
    $aud    = getenv('JWT_AUDIENCE') ?: 'shop-api';

    $publicKey = file_get_contents('/shared/jwt-public.pem');
    if (!$publicKey) {
        throw new RuntimeException('Public key not found');
    }

    // Decode+verify signature (RS256)
    $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

    // Validate issuer
    if (($decoded->iss ?? null) !== $issuer) {
        throw new RuntimeException('Invalid issuer');
    }

    // Validate audience (string or array)
    $audClaim = $decoded->aud ?? null;
    $audOk = is_string($audClaim) ? ($audClaim === $aud)
          : (is_array($audClaim) && in_array($aud, $audClaim, true));
    if (!$audOk) {
        throw new RuntimeException('Invalid audience');
    }

    // exp is validated by library during decode based on current time, but we keep a sanity check:
    if (!isset($decoded->exp) || !is_numeric($decoded->exp)) {
        throw new RuntimeException('Missing exp');
    }

    return $decoded;
}

function json_out(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
}
