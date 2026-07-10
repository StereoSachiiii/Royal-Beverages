<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Admin\Models\IdempotencyModel;

class IdempotencyRepository extends BaseRepository
{
    public function find(string $key): ?IdempotencyModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM idempotency_keys WHERE key = :key");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new IdempotencyModel(
            key: $row['key'],
            endpoint: $row['endpoint'],
            responseBody: $row['response_body'],
            statusCode: (int)$row['status_code'],
            createdAt: $row['created_at']
        );
    }

    public function store(string $key, string $endpoint, string $responseBody, int $statusCode): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO idempotency_keys (key, endpoint, response_body, status_code)
             VALUES (:key, :endpoint, :response_body, :status_code)
             ON CONFLICT (key) DO NOTHING"
        );
        
        $stmt->execute([
            ':key' => $key,
            ':endpoint' => $endpoint,
            ':response_body' => $responseBody,
            ':status_code' => $statusCode
        ]);
    }
}
