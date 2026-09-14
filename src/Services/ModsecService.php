<?php

namespace App\Services;

use App\Repositories\ModsecRepository;
use InvalidArgumentException;

class ModsecService
{
    public function __construct(
        private ModsecRepository $repository
    ) {
    }

    public function store(array $payload): array
    {
        $event = $this->validateEvent($payload);

        return $this->processEvent(
            $event['source'],
            $event['ip'],
            $event['amount']
        );
    }

    // rota para registro vazio, quando não existe nenhum registro
    public function storeReset(array $payload): array
    {
        $this->repository->delete($payload['source']);

        return [
            'source' => $payload['source'],
            'action' => 'reset'
        ];
    }

    public function storeBulk(array $payload): array
    {
        $source = $payload['source'] ?? null;
        $events = $payload['events'] ?? null;

        if (!is_string($source) || trim($source) === '') {
            throw new InvalidArgumentException(
                'Field "source" is required'
            );
        }

        if (!is_array($events) || $events === []) {
            throw new InvalidArgumentException(
                'Field "events" must be a non-empty array'
            );
        }

        $source = trim($source);

        $created = 0;
        $updated = 0;
        $results = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                throw new InvalidArgumentException(
                    'Each event must be an object'
                );
            }

            $validated = $this->validateEvent([
                'source' => $source,
                'ip' => $event['ip'] ?? null,
                'amount' => $event['amount'] ?? null
            ]);

            // $this->repository->blockAll($validated['source']);
            $this->repository->delete($validated['source']);

            $result = $this->processEvent(
                $validated['source'],
                $validated['ip'],
                $validated['amount']
            );

            if ($result['action'] === 'created') {
                $created++;
            } else {
                $updated++;
            }

            $results[] = $result;
        }

        return [
            'total' => count($results),
            'created' => $created,
            'updated' => $updated,
            'events' => $results
        ];
    }

    private function validateEvent(array $event): array
    {
        $source = $event['source'] ?? null;
        $ip = $event['ip'] ?? null;
        $amount = $event['amount'] ?? null;

        if (!is_string($source) || trim($source) === '') {
            throw new InvalidArgumentException(
                'Field "source" is required'
            );
        }

        if (!is_string($ip) || trim($ip) === '') {
            throw new InvalidArgumentException(
                'Field "ip" is required'
            );
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException(
                'Field "ip" must be a valid IP address'
            );
        }

        if (!is_int($amount) && !is_numeric($amount)) {
            throw new InvalidArgumentException(
                'Field "amount" must be numeric'
            );
        }

        $amount = (int) $amount;

        if ($amount < 0) {
            throw new InvalidArgumentException(
                'Field "amount" must be greater than or equal to zero'
            );
        }

        return [
            'source' => trim($source),
            'ip' => trim($ip),
            'amount' => $amount
        ];
    }

    private function processEvent(
        string $source,
        string $ip,
        int $amount
    ): array {
        $existing = $this->repository->findBySourceAndIp(
            $source,
            $ip
        );

        if ($existing === null) {
            $id = $this->repository->create(
                $source,
                $ip,
                $amount
            );

            return [
                'id' => $id,
                'source' => $source,
                'ip' => $ip,
                'current_amount' => $amount,
                'previous_amount' => $amount,
                'initial_amount' => $amount,
                'action' => 'created'
            ];
        }

        $previousAmount = (int) $existing['current_amount'];

        $this->repository->update(
            (int) $existing['id'],
            $amount,
            $previousAmount
        );

        return [
            'id' => (int) $existing['id'],
            'source' => $source,
            'ip' => $ip,
            'current_amount' => $amount,
            'previous_amount' => $previousAmount,
            'initial_amount' => (int) $existing['initial_amount'],
            'action' => 'updated'
        ];
    }

    public function updateStatus(array $payload): array
    {
        $events = $payload['events'] ?? null;

        if (!is_array($events) || $events === []) {
            throw new InvalidArgumentException(
                'Field "events" must be a non-empty array'
            );
        }

        $updated = 0;
        $notFound = 0;
        $results = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                throw new InvalidArgumentException(
                    'Each event must be an object'
                );
            }

            $ip = $event['ip'] ?? null;
            $source = $event['source'] ?? null;
            $status = $event['status'] ?? null;

            if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new InvalidArgumentException(
                    'Field "ip" must be a valid IP address'
                );
            }

            if (!is_string($source) || trim($source) === '') {
                throw new InvalidArgumentException(
                    'Field "source" is required'
                );
            }

            if (!is_string($status) || trim($status) === '') {
                throw new InvalidArgumentException(
                    'Field "status" is required'
                );
            }

            $updatedStatus = $this->repository->updateStatus(
                trim($source),
                trim($ip),
                trim($status)
            );

            if ($updatedStatus) {
                $updated++;

                $results[] = [
                    'source' => trim($source),
                    'ip' => trim($ip),
                    'status' => trim($status),
                    'action' => 'updated'
                ];
            } else {
                $notFound++;

                $results[] = [
                    'source' => trim($source),
                    'ip' => trim($ip),
                    'status' => trim($status),
                    'action' => 'not_found'
                ];
            }
        }

        return [
            'total' => count($events),
            'updated' => $updated,
            'not_found' => $notFound,
            'events' => $results
        ];
    }
}