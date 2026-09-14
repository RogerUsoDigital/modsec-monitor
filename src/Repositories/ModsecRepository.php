<?php

namespace App\Repositories;

use PDO;

class ModsecRepository
{
    public function __construct(
        private PDO $connection
    ) {
    }

    public function findBySourceAndIp(
        string $source,
        string $ip
    ): ?array {
        $sql = '
            SELECT
                id,
                source,
                ip,
                current_amount,
                previous_amount,
                initial_amount,
                created_at,
                updated_at,
                status
            FROM modsec_ip_events
            WHERE source = :source
              AND ip = :ip
            LIMIT 1
        ';

        $statement = $this->connection->prepare($sql);

        $statement->execute([
            'source' => $source,
            'ip' => $ip
        ]);

        $result = $statement->fetch();

        return $result ?: null;
    }

    public function create(
        string $source,
        string $ip,
        int $amount
    ): int {
        $sql = '
            INSERT INTO modsec_ip_events (
                source,
                ip,
                current_amount,
                previous_amount,
                initial_amount
            ) VALUES (
                :source,
                :ip,
                :current_amount,
                :previous_amount,
                :initial_amount
            )
        ';

        $statement = $this->connection->prepare($sql);

        $statement->execute([
            'source' => $source,
            'ip' => $ip,
            'current_amount' => $amount,
            'previous_amount' => $amount,
            'initial_amount' => $amount
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(
        int $id,
        int $currentAmount,
        int $previousAmount
    ): void {
        $sql = '
            UPDATE modsec_ip_events
            SET
                current_amount = :current_amount,
                previous_amount = :previous_amount,
                updated_at = NOW(),
                status = :status
            WHERE id = :id
        ';

        $statement = $this->connection->prepare($sql);

        $statement->execute([
            'id' => $id,
            'current_amount' => $currentAmount,
            'previous_amount' => $previousAmount,
            'status' => 'active'
        ]);
    }

    public function updateStatus(
        string $source,
        string $ip,
        string $status
    ): bool {
        $sql = '
            UPDATE modsec_ip_events
            SET status = :status
            WHERE source = :source
            AND ip = :ip
        ';

        $statement = $this->connection->prepare($sql);

        $statement->execute([
            'source' => $source,
            'ip' => $ip,
            'status' => $status
        ]);

        return $statement->rowCount() > 0;
    }

    public function blockAll(string $source): void
    {
        $sql = 'UPDATE modsec_ip_events SET status = :status WHERE source = :source';

        $statement = $this->connection->prepare($sql);

        $statement->execute([
            'source' => $source,
            'status' => 'blocked'
        ]);
    }

    public function delete(string $source): void
    {
        $sql = 'DELETE FROM modsec_ip_events WHERE source = :source';

        $statement = $this->connection->prepare($sql);

        $statement->execute([
            'source' => $source
        ]);
    }
}