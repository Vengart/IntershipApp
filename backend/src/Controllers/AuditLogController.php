<?php

namespace App\Controllers;

use App\Http\Response;
use App\Models\AuditLogRepository;

class AuditLogController
{
    public function __construct(private AuditLogRepository $auditLogs)
    {
    }

    /**
     * GET /audit-logs?intern_id= — доступно operator и auditor.
     * Без intern_id — общая лента изменений по всем стажёрам сразу.
     * С intern_id — история конкретного стажёра (старое поведение history()).
     */
    public function list(): never
    {
        $internId = isset($_GET['intern_id']) ? (int) $_GET['intern_id'] : null;

        Response::json($this->auditLogs->findAll($internId));
    }
}
