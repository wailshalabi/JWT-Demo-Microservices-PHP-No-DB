<?php
declare(strict_types=1);

require __DIR__ . '/../src/util.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/login' && $method === 'POST') {
    $d = json_in();
    $email = (string)($d['email'] ?? '');
    $password = (string)($d['password'] ?? '');

    // Hardcoded demo login
    if ($email !== 'demo@example.com' || $password !== 'secret123') {
        json_out(401, ['error' => 'invalid_credentials']);
        exit;
    }

    $access = issue_access_token('1001', $email);
    $refresh = issue_refresh_token('1001');

    json_out(200, [
        'access_token' => $access,
        'refresh_token' => $refresh,
        'token_type' => 'Bearer',
        'expires_in' => 10,
        'refresh_expires_in' => 600
    ]);
    exit;
}

if ($path === '/refresh' && $method === 'POST') {
    $d = json_in();
    $refreshToken = (string)($d['refresh_token'] ?? '');

    if ($refreshToken === '') {
        json_out(400, ['error' => 'missing_refresh_token']);
        exit;
    }

    try {
        [$access, $newRefresh] = rotate_refresh_token($refreshToken, 'demo@example.com');
        json_out(200, [
            'access_token' => $access,
            'refresh_token' => $newRefresh,
            'token_type' => 'Bearer',
            'expires_in' => 10,
            'refresh_expires_in' => 600
        ]);
    } catch (Throwable $e) {
        json_out(401, ['error' => 'refresh_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($path === '/health') {
    json_out(200, ['ok' => true, 'service' => 'auth']);
    exit;
}

json_out(404, ['error' => 'not_found', 'path' => $path]);
