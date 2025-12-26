<?php
declare(strict_types=1);

require __DIR__ . '/../src/util.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/login' && $method === 'POST') {
    $data = json_in();
    $email = (string)($data['email'] ?? '');
    $password = (string)($data['password'] ?? '');

    // Hardcoded demo credentials
    if ($email !== 'demo@example.com' || $password !== 'secret123') {
        json_out(401, ['error' => 'invalid_credentials']);
        exit;
    }

    $jwt = issue_access_token('1001', $email);
    json_out(200, ['access_token' => $jwt, 'token_type' => 'Bearer', 'expires_in' => 900]);
    exit;
}

if ($path === '/health') {
    json_out(200, ['ok' => true, 'service' => 'auth']);
    exit;
}

json_out(404, ['error' => 'not_found', 'path' => $path]);
