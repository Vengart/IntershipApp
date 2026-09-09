<?php

namespace App\Controllers;

use App\Auth\JwtHandler;
use App\Http\Response;
use App\Models\UserRepository;

class AuthController
{
    public function __construct(
        private UserRepository $users,
        private JwtHandler $jwt
    ) {
    }

    /**
     * POST /auth/login
     * body: { "username": "...", "password": "..." }
     */
    public function login(): never
    {
        $body = json_decode(file_get_contents('php://input'), true);

        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        if ($username === '' || $password === '') {
            Response::error('Username and password are required', 422, 'validation_error');
        }

        $user = $this->users->findByUsername($username);

        // Намеренно одинаковое сообщение при "нет юзера" и "неверный пароль" —
        // не даём атакующему понять, существует ли аккаунт
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            Response::error('Invalid credentials', 401, 'invalid_credentials');
        }

        $token = $this->jwt->generate((int) $user['id'], $user['role']);

        Response::json([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
            ],
        ]);
    }
}
