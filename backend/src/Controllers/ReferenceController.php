<?php

namespace App\Controllers;

use App\Http\Response;
use App\Models\DirectionRepository;
use App\Models\SectionRepository;

class ReferenceController
{
    public function __construct(
        private DirectionRepository $directions,
        private SectionRepository $sections
    ) {
    }

    /**
     * GET /directions — доступно operator и auditor.
     */
    public function listDirections(): never
    {
        Response::json($this->directions->all());
    }

    /**
     * GET /sections?direction_id= — доступно operator и auditor.
     * direction_id опционален: без него вернёт все секции сразу.
     */
    public function listSections(): never
    {
        $directionId = isset($_GET['direction_id']) ? (int) $_GET['direction_id'] : null;

        Response::json($this->sections->all($directionId));
    }
}
