<?php

namespace App\Http;

/**
 * Единый формат ответа для всех эндпоинтов:
 * успех: { "data": ... }
 * ошибка: { "error": { "message": "...", "code": "..." } }
 */
class Response
{
    public static function json(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, string $code = 'error'): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
