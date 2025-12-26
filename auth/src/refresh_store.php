<?php
declare(strict_types=1);

/**
 * File-based refresh token store (demo only).
 * - No DB
 * - Uses flock to avoid corruption
 * - Stores records keyed by refresh_token string
 */

function refresh_store_path(): string {
    // inside container; writable
    return '/tmp/refresh_store.json';
}

function refresh_store_load(): array {
    $path = refresh_store_path();
    if (!file_exists($path)) {
        return [];
    }
    $fp = fopen($path, 'r');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp) ?: '';
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function refresh_store_save(array $data): void {
    $path = refresh_store_path();
    $fp = fopen($path, 'c+');
    if (!$fp) {
        throw new RuntimeException('Cannot open refresh store file');
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function refresh_store_put(string $token, array $record): void {
    $data = refresh_store_load();
    $data[$token] = $record;
    refresh_store_save($data);
}

function refresh_store_get(string $token): ?array {
    $data = refresh_store_load();
    return $data[$token] ?? null;
}

function refresh_store_delete(string $token): void {
    $data = refresh_store_load();
    if (isset($data[$token])) {
        unset($data[$token]);
        refresh_store_save($data);
    }
}
