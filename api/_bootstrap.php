<?php
/**
 * Общая инициализация для API-эндпоинтов: конфиг, JSON-ответы, безопасность.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_fail(string $message, int $code = 400): never
{
    json_out(['ok' => false, 'error' => $message], $code);
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_fail('Требуется метод POST', 405);
    }
}

/** Тело JSON-запроса как ассоциативный массив (с сохранением порядка ключей). */
function read_json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
