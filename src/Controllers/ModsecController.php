<?php

namespace App\Controllers;

use App\Services\ModsecService;
use InvalidArgumentException;
use Throwable;

class ModsecController
{
    public function __construct(
        private ModsecService $service
    ) {
    }

    public function store(): void
    {
        try {
            $payload = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($payload)) {
                $this->response(400, [
                    'success' => false,
                    'message' => 'Invalid JSON payload'
                ]);

                return;
            }

            $result = $this->service->store($payload);

            $this->response(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (InvalidArgumentException $e) {
            $this->response(400, [
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            $this->response(500, [
                'success' => false,
                'message' => 'Internal server error'
            ]);
        }
    }

    public function storeBulk(): void
    {
        try {
            $payload = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($payload)) {
                $this->response(400, [
                    'success' => false,
                    'message' => 'Invalid JSON payload'
                ]);

                return;
            }

            $result = $this->service->storeBulk($payload);

            $this->response(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (InvalidArgumentException $e) {
            $this->response(400, [
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            error_log($e->getMessage());

            $this->response(500, [
                'success' => false,
                'message' => 'Internal server error'
            ]);
        }
    }

    public function updateStatus(): void
    {
        try {
            $payload = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($payload)) {
                $this->response(400, [
                    'success' => false,
                    'message' => 'Invalid JSON payload'
                ]);

                return;
            }

            $result = $this->service->updateStatus($payload);

            $this->response(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (InvalidArgumentException $e) {
            $this->response(400, [
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());

            $this->response(500, [
                'success' => false,
                'message' => 'Internal server error'
            ]);
        }
    }

    public function storeReset(): void
    {
        try {
            $payload = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($payload)) {
                $this->response(400, [
                    'success' => false,
                    'message' => 'Invalid JSON payload'
                ]);

                return;
            }

            $result = $this->service->storeReset($payload);

            $this->response(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (InvalidArgumentException $e) {
            $this->response(400, [
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());

            $this->response(500, [
                'success' => false,
                'message' => 'Internal server error'
            ]);
        }
    }

    private function response(int $statusCode, array $data): void
    {
        http_response_code($statusCode);

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}