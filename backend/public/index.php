<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth\AuthMiddleware;
use App\Auth\JwtHandler;
use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\ExportController;
use App\Controllers\InternController;
use App\Http\Response;
use App\Models\AuditLogRepository;
use App\Models\InternRepository;
use App\Models\UserRepository;
use App\Services\ExportService;
use App\Services\InternService;

// --- 1. Загружаем .env ---
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// --- 2. CORS ---
// Разрешаем только конкретный origin фронтенда, не '*' — иначе Bearer-токен
// теоретически можно перехватить с произвольного сайта в некоторых сценариях
$allowedOrigin = $_ENV['FRONTEND_ORIGIN'] ?? 'http://localhost:5173';
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight-запросы браузер шлёт отдельно, на них просто отвечаем 204
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- 3. Роутинг ---
// Простой роутер без библиотек: сравниваем метод + путь.
// Если список эндпоинтов вырастет — можно заменить на таблицу routes[],
// но для текущего масштаба явный match() читается яснее.

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Убираем возможный префикс, если API повешен не в корень домена
$path = preg_replace('#^/api#', '', $path);

try {
    $db = Database::getConnection();

    // Фабрика контроллера — чтобы не собирать зависимости в каждой ветке руками
    $makeInternController = function () use ($db): InternController {
        return new InternController(
            new InternRepository($db),
            new AuditLogRepository($db),
            new InternService($db, new InternRepository($db), new AuditLogRepository($db))
        );
    };

    match (true) {
        $method === 'POST' && $path === '/auth/login' => (function () use ($db) {
            $controller = new AuthController(new UserRepository($db), new JwtHandler());
            $controller->login();
        })(),

        // --- Interns ---
        $method === 'GET' && $path === '/interns' => (function () use ($makeInternController) {
            $auth = new AuthMiddleware();
            $auth->authenticate(); // operator и auditor оба могут читать
            $makeInternController()->list();
        })(),

        $method === 'POST' && $path === '/interns' => (function () use ($makeInternController) {
            $auth = new AuthMiddleware();
            $payload = $auth->authenticate();
            $auth->requireRole($payload, ['operator']);
            $makeInternController()->create((int) $payload['sub']);
        })(),

        $method === 'PUT' && preg_match('#^/interns/(\d+)$#', $path, $m) === 1
            => (function () use ($makeInternController, $m) {
                $auth = new AuthMiddleware();
                $payload = $auth->authenticate();
                $auth->requireRole($payload, ['operator']);
                $makeInternController()->update((int) $m[1], (int) $payload['sub']);
            })(),

        $method === 'GET' && preg_match('#^/interns/(\d+)/history$#', $path, $m) === 1
            => (function () use ($makeInternController, $m) {
                $auth = new AuthMiddleware();
                $auth->authenticate(); // operator и auditor оба могут читать историю
                $makeInternController()->history((int) $m[1]);
            })(),

        // Export to excel  
        $method === 'GET' && $path === '/export/excel' => (function () use ($db) {
            $auth = new AuthMiddleware();
            $auth->authenticate(); // экспорт — это чтение, доступно обеим ролям
            $controller = new ExportController(new InternRepository($db), new ExportService());
            $controller->exportInterns();
        })(),

        default => Response::error('Route not found', 404, 'not_found'),
    };
} catch (\Throwable $e) {
    error_log($e->getMessage());
    Response::error('Internal server error', 500, 'internal_error');
}