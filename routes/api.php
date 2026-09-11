<?php

use App\Controllers\ModsecController;
use App\Database\Connection;
use App\Repositories\ModsecRepository;
use App\Services\ModsecService;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET' && $uri === '/health') {
    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'API is running'
    ]);

    exit;
}

if ($method === 'POST' && $uri === '/v1/modsec/events') {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;

    if (
        empty($apiKey) ||
        !hash_equals($_ENV['API_TOKEN'], $apiKey)
    ) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

        exit;
    }

    $connection = new Connection();

    $repository = new ModsecRepository(
        $connection->getConnection()
    );

    $service = new ModsecService($repository);

    $controller = new ModsecController($service);

    $controller->store();

    exit;
}

http_response_code(404);

echo json_encode([
    'success' => false,
    'message' => 'Route not found'
]);