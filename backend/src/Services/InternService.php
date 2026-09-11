<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\AuditLogRepository;
use App\Models\InternRepository;
use PDO;

class InternService
{
    public function __construct(
        private PDO $db,
        private InternRepository $interns,
        private AuditLogRepository $auditLogs
    ) {
    }

    /**
     * Создаёт стажёра и сразу пишет audit_log с action=INSERT.
     * old_values пустой — это соответствует схеме (DEFAULT '{}'::jsonb).
     * Всё в одной транзакции: если запись лога не удалась, откатываем insert,
     * чтобы не было "молчаливых" изменений без следа в истории.
     */
    public function createIntern(array $data, int $userId): array
    {
        $this->db->beginTransaction();

        try {
            $created = $this->interns->create($data, $userId);

            $this->auditLogs->create(
                internId: (int) $created['id'],
                userId: $userId,
                action: 'INSERT',
                oldValues: [],
                newValues: $created
            );

            $this->db->commit();
            return $created;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Обновляет стажёра, снимая snapshot "до" перед изменением
     * и записывая "после" сразу вслед за update — обе операции атомарны.
     */
    public function updateIntern(int $id, array $data, int $userId): array
    {
        $before = $this->interns->find($id);

        if ($before === null) {
            throw new NotFoundException('Intern not found');
        }

        $this->db->beginTransaction();

        try {
            $after = $this->interns->update($id, $data, $userId);

            $this->auditLogs->create(
                internId: $id,
                userId: $userId,
                action: 'UPDATE',
                oldValues: $before,
                newValues: $after
            );

            $this->db->commit();
            return $after;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
