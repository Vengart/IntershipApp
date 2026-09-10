<?php

namespace App\Models;

use PDO;

class AuditLogRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(int $internId, int $userId, string $action, array $oldValues, array $newValues): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (intern_id, user_id, action, old_values, new_values)
             VALUES (:intern_id, :user_id, :action, :old_values, :new_values)'
        );

        $stmt->execute([
            'intern_id' => $internId,
            'user_id' => $userId,
            'action' => $action,
            'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'new_values' => json_encode($newValues, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @param int|null $internId если передан — вернёт историю только этого стажёра,
     *                            иначе — общую ленту изменений по всем.
     */
    public function findAll(?int $internId = null): array
    {
        $sql = 'SELECT al.id, al.intern_id, i.full_name AS intern_name,
                       al.action, al.old_values, al.new_values, al.created_at,
                       u.username AS changed_by
                FROM audit_logs al
                JOIN users u ON u.id = al.user_id
                JOIN interns i ON i.id = al.intern_id';

        if ($internId !== null) {
            $sql .= ' WHERE al.intern_id = :intern_id';
        }
        $sql .= ' ORDER BY al.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($internId !== null ? ['intern_id' => $internId] : []);

        return $stmt->fetchAll();
    }
}
