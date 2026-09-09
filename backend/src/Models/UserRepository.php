<?php

namespace App\Models;

use PDO;

class UserRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);

        $user = $stmt->fetch();

        return $user ?: null;
    }
}
