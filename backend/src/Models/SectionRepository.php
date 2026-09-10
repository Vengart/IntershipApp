<?php

namespace App\Models;

use PDO;

class SectionRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @param int|null $directionId если передан — вернёт только секции этого направления
     *                               (полезно на фронте: сначала выбрали направление,
     *                               потом список секций сужается только под него)
     */
    public function all(?int $directionId = null): array
    {
        if ($directionId !== null) {
            $stmt = $this->db->prepare(
                'SELECT id, direction_id, name FROM sections WHERE direction_id = :direction_id ORDER BY id'
            );
            $stmt->execute(['direction_id' => $directionId]);
        } else {
            $stmt = $this->db->query('SELECT id, direction_id, name FROM sections ORDER BY id');
        }

        return $stmt->fetchAll();
    }
}
