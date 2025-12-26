<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

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

function issue_access_token(string $userId, string $email): string {
    $issuer = getenv('JWT_ISSUER') ?: 'https://localhost';
    $aud    = getenv('JWT_AUDIENCE') ?: 'shop-api';
    $kid    = getenv('JWT_KID') ?: 'demo-key-1';

    $privateKey = file_get_contents('/shared/jwt-private.pem');
    if (!$privateKey) {
        throw new RuntimeException('Private key not found');
    }

    $now = time();
    $payload = [
        'iss' => $issuer,
        'aud' => $aud,
        'sub' => $userId,
        'email' => $email,
        'scope' => 'catalog:read orders:read',
        'iat' => $now,
        'exp' => $now + 10, // 10 seconds for demo purposes
    ];

    return JWT::encode($payload, $privateKey, 'RS256', $kid);
}
