<?php

namespace App\Auth;

use App\Http\Response;

class AuthMiddleware
{
    private JwtHandler $jwt;

    public function __construct()
    {
        $this->jwt = new JwtHandler();
    }

    /**
     * Проверяет, что запрос содержит валидный токен.
     * Возвращает payload (['sub' => userId, 'role' => ...]) или сразу
     * прерывает выполнение ответом 401.
     */
    public function authenticate(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            Response::error('Missing or malformed Authorization header', 401, 'unauthorized');
        }

        $payload = $this->jwt->verify($matches[1]);

        if ($payload === null) {
            Response::error('Invalid or expired token', 401, 'unauthorized');
        }

        return $payload;
    }

    /**
     * Проверяет, что роль пользователя входит в список разрешённых.
     * Пример: $auth->requireRole($payload, ['operator']);
     */
    public function requireRole(array $payload, array $allowedRoles): void
    {
        if (!in_array($payload['role'], $allowedRoles, true)) {
            Response::error('Insufficient permissions for this action', 403, 'forbidden');
        }
    }
}
