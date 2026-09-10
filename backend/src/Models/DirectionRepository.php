<?php

namespace App\Models;

use PDO;

class DirectionRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT id, name FROM directions ORDER BY id');
        return $stmt->fetchAll();
    }
}
