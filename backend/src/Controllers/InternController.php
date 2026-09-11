<?php

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Http\Response;
use App\Models\InternRepository;
use App\Services\InternService;

class InternController
{
    public function __construct(
        private InternRepository $interns,
        private InternService $service
    ) {
    }

    /**
     * GET /interns?direction_id=&section_id=&university=&specialty=&date_from=&date_to=
     * Доступно operator и auditor.
     */
    public function list(): never
    {
        $filters = array_filter([
            'direction_id' => $_GET['direction_id'] ?? null,
            'section_id' => $_GET['section_id'] ?? null,
            'university' => $_GET['university'] ?? null,
            'specialty' => $_GET['specialty'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ]);

        // Отдельно от array_filter выше: '0'/'false' — валидные значения
        // is_active, их нельзя терять как "пустые".
        if (isset($_GET['is_active'])) {
            $filters['is_active'] = $_GET['is_active'];
        }

        Response::json($this->interns->list($filters));
    }

    /**
     * POST /interns — только operator (проверка роли уже сделана в роутере).
     * body: любые поля из WRITABLE_FIELDS, direction_id/section_id обязательны.
     */
    public function create(int $userId): never
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['full_name'])) {
            Response::error('full_name is required', 422, 'validation_error');
        }
        if (empty($data['direction_id']) || empty($data['section_id'])) {
            Response::error('direction_id and section_id are required', 422, 'validation_error');
        }

        try {
            $created = $this->service->createIntern($data, $userId);
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
        }

        Response::json($created, 201);
    }

    /**
     * PUT /interns/{id} — только operator. Никогда не удаляем запись,
     * только редактируем (по требованию ТЗ).
     */
    public function update(int $id, int $userId): never
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data)) {
            Response::error('Request body is empty', 422, 'validation_error');
        }

        try {
            $updated = $this->service->updateIntern($id, $data, $userId);
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404, 'not_found');
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e);
        }

        Response::json($updated);
    }

    /**
     * Общая точка для ошибок БД в create()/update(). SQLSTATE 22001 —
     * "string data right truncation", т.е. значение длиннее, чем позволяет
     * VARCHAR(N) в схеме. Для этого случая — понятное сообщение вместо
     * сырого текста Postgres. Всё остальное пробрасываем дальше — долетит
     * до общего catch(\Throwable) в index.php как обычная 500.
     */
    private function handleDatabaseError(\PDOException $e): never
    {
        if ($e->getCode() === '22001') {
            Response::error(
                'Unul dintre câmpuri depășește lungimea maximă permisă',
                422,
                'validation_error'
            );
        }

        throw $e;
    }
}
