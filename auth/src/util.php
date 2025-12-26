<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/refresh_store.php';

use Firebase\JWT\JWT;

function json_in(): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_out(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
}

/** Access token: RS256 JWT, 10s TTL for demo */
function issue_access_token(string $userId, string $email): string {
    $issuer = getenv('JWT_ISSUER') ?: 'https://localhost';
    $aud    = getenv('JWT_AUDIENCE') ?: 'shop-api';
    $kid    = getenv('JWT_KID') ?: 'demo-key';

    $privateKey = file_get_contents('/shared/jwt-private.pem');
    if (!$privateKey) {
        throw new RuntimeException('JWT private key missing');
    }

    $now = time();
    $payload = [
        'iss' => $issuer,
        'aud' => $aud,
        'sub' => $userId,
        'email' => $email,
        'iat' => $now,
        'exp' => $now + 10,
        'scope' => 'catalog:read orders:read',
    ];

    return JWT::encode($payload, $privateKey, 'RS256', $kid);
}

/** Refresh token: opaque random, stored server-side, 10 min TTL for demo */
function issue_refresh_token(string $userId): string {
    $token = bin2hex(random_bytes(32));
    refresh_store_put($token, [
        'uid' => $userId,
        'exp' => time() + 600, // 10 minutes
    ]);
    return $token;
}

/**
 * Validate refresh token (exists + not expired), then rotate it.
 * Returns [new_access_token, new_refresh_token]
 */
function rotate_refresh_token(string $refreshToken, string $email): array {
    $rec = refresh_store_get($refreshToken);
    if (!$rec) {
        throw new RuntimeException('Invalid refresh token');
    }
    if (($rec['exp'] ?? 0) < time()) {
        // expire + delete
        refresh_store_delete($refreshToken);
        throw new RuntimeException('Refresh token expired');
    }

    // Rotation: invalidate old refresh token
    refresh_store_delete($refreshToken);

    $uid = (string)$rec['uid'];
    $newAccess = issue_access_token($uid, $email);
    $newRefresh = issue_refresh_token($uid);

    return [$newAccess, $newRefresh];
}
