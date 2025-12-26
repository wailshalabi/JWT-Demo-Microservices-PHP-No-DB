<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function json_out(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
}

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
        throw new RuntimeException('JWT public key missing');
    }

    $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

    if (($decoded->iss ?? null) !== $issuer) {
        throw new RuntimeException('Invalid issuer');
    }

    $audClaim = $decoded->aud ?? null;
    $audOk = is_string($audClaim) ? ($audClaim === $aud)
          : (is_array($audClaim) && in_array($aud, $audClaim, true));
    if (!$audOk) {
        throw new RuntimeException('Invalid audience');
    }

    return $decoded;
}
