<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private PDO $connection;

    public function __construct()
    {
        try {
            $this->connection = new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $_ENV['DB_HOST'],
                    $_ENV['DB_PORT'],
                    $_ENV['DB_DATABASE']
                ),
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new PDOException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}