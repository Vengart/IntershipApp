<?php

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JwtHandler
{
    private string $secret;
    private int $ttlSeconds;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? '';
        $this->ttlSeconds = (int) ($_ENV['JWT_TTL_SECONDS'] ?? 3600);

        if ($this->secret === '') {
            throw new \RuntimeException('JWT_SECRET is not configured');
        }
    }

    /**
     * Генерирует токен для пользователя. В payload кладём только то,
     * что нужно для авторизации запросов — не пароль, не лишние данные.
     */
    public function generate(int $userId, string $role): string
    {
        $now = time();

        $payload = [
            'sub' => $userId,
            'role' => $role,
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Возвращает payload если токен валиден, иначе null.
     * Не бросает исключение наружу — вызывающий код сам решает,
     * как реагировать (обычно 401).
     */
    public function verify(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException | SignatureInvalidException | \UnexpectedValueException $e) {
            return null;
        }
    }
}
