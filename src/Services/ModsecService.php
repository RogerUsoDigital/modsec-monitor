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
        $source = $payload['source'] ?? null;
        $ip = $payload['ip'] ?? null;
        $amount = $payload['amount'] ?? null;

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

        $source = trim($source);
        $ip = trim($ip);

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

        $this->repository->update(
            (int) $existing['id'],
            $amount,
            (int) $existing['current_amount']
        );

        return [
            'id' => (int) $existing['id'],
            'source' => $source,
            'ip' => $ip,
            'current_amount' => $amount,
            'previous_amount' => (int) $existing['current_amount'],
            'initial_amount' => (int) $existing['initial_amount'],
            'action' => 'updated'
        ];
    }
}