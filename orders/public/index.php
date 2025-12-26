<?php
declare(strict_types=1);

require __DIR__ . '/../src/verify.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($path === '/ping' && $method === 'GET') {
        $jwt = get_bearer_token();
        $claims = verify_jwt($jwt);

        json_out(200, [
            'service' => 'orders',
            'message' => 'pong',
            'user' => [
                'sub' => $claims->sub ?? null,
                'email' => $claims->email ?? null,
            ],
            'now' => time(),
            'note' => 'JWT verified offline using /shared/jwt-public.pem (no call to auth service).'
        ]);
        exit;
    }

    if ($path === '/health') {
        json_out(200, ['ok' => true, 'service' => 'orders']);
        exit;
    }

    json_out(404, ['error' => 'not_found', 'path' => $path]);
} catch (Throwable $e) {
    json_out(401, ['error' => 'unauthorized', 'message' => $e->getMessage()]);
}
