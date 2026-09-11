<?php

use App\Controllers\ModsecController;
use App\Database\Connection;
use App\Repositories\ModsecRepository;
use App\Services\ModsecService;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/') ?: '/';

if ($uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/../src/Views/index.php';
    exit;
}

if ($method === 'GET' && $uri === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'API is running'
    ]);

    exit;
}

if ($method === 'POST' && $uri === '/v1/modsec/events') {
    header('Content-Type: application/json; charset=utf-8');
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (
        !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)
    ) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

        exit;
    }

    $apiToken = $_ENV['API_TOKEN'] ?? '';
    $receivedToken = $matches[1];

    if (
        $apiToken === '' ||
        !hash_equals($apiToken, $receivedToken)
    ) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

        exit;
    }

    try {
        $connection = new Connection();

        $repository = new ModsecRepository(
            $connection->getConnection()
        );

        $service = new ModsecService($repository);

        $controller = new ModsecController($service);

        $controller->store();
    } catch (\Throwable $e) {
        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        exit;
    }

    exit;
}

// Qualquer rota que não exista redireciona para a raiz
header('Location: /', true, 302);
exit;