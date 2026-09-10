<?php

namespace App\Controllers;

use App\Models\InternRepository;
use App\Services\ExportService;

class ExportController
{
    public function __construct(
        private InternRepository $interns,
        private ExportService $exportService
    ) {
    }

    
    //  GET /export/excel — поддерживает те же query-фильтры, что и /interns | (direction_id, section_id, university, specialty, date_from, date_to, is_active), может понадобится выгрузить ограниченную часть
    
    public function exportInterns(): never
    {
        $filters = array_filter([
            'direction_id' => $_GET['direction_id'] ?? null,
            'section_id' => $_GET['section_id'] ?? null,
            'university' => $_GET['university'] ?? null,
            'specialty' => $_GET['specialty'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ]);
        // Добавляем фильтр по is_active, если он передан в query-параметрах
        if (isset($_GET['is_active'])) {
            $filters['is_active'] = $_GET['is_active'];
        }
        //Тут мы можем использовать метод list из InternRepository, чтобы получить список стажеров с учетом фильтров, а затем передать этот список в ExportService для генерации Excel-файла.
        $interns = $this->interns->list($filters);

        $this->exportService->streamInternsXlsx($interns);
    }
}
