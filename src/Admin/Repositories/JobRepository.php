<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Admin\Models\JobModel;

class JobRepository extends BaseRepository
{
    /**
     * Enqueue a new background job
     */
    public function enqueue(string $jobType, array $payload, ?string $runAt = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO jobs (job_type, payload, run_at)
             VALUES (:job_type, :payload, COALESCE(:run_at, NOW()))"
        );
        $stmt->execute([
            ':job_type' => $jobType,
            ':payload'  => json_encode($payload),
            ':run_at'   => $runAt
        ]);
    }

    /**
     * Claim the next available job for processing using SKIP LOCKED
     */
    public function claimNext(): ?JobModel
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->query(
                "SELECT * FROM jobs 
                 WHERE status = 'pending' AND run_at <= NOW()
                 ORDER BY run_at ASC 
                 LIMIT 1 
                 FOR UPDATE SKIP LOCKED"
            );

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->pdo->commit();
                return null;
            }

            // Mark as processing
            $updateStmt = $this->pdo->prepare(
                "UPDATE jobs 
                 SET status = 'processing', attempts = attempts + 1 
                 WHERE id = :id"
            );
            $updateStmt->execute([':id' => $row['id']]);

            $this->pdo->commit();

            return new JobModel(
                id: (int)$row['id'],
                jobType: $row['job_type'],
                payload: json_decode($row['payload'], true) ?? [],
                status: 'processing',
                attempts: (int)$row['attempts'] + 1,
                runAt: $row['run_at'],
                createdAt: $row['created_at']
            );
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Mark a job as completed
     */
    public function complete(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE jobs SET status = 'done' WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Mark a job as failed, potentially scheduling a retry
     */
    public function fail(int $id, int $maxAttempts = 3): void
    {
        $stmt = $this->pdo->prepare("SELECT attempts FROM jobs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $attempts = (int)$stmt->fetchColumn();

        if ($attempts >= $maxAttempts) {
            $update = $this->pdo->prepare("UPDATE jobs SET status = 'failed' WHERE id = :id");
            $update->execute([':id' => $id]);
        } else {
            // Exponential backoff for retries
            $delay = pow(2, $attempts) * 60; // seconds
            $update = $this->pdo->prepare(
                "UPDATE jobs SET status = 'pending', run_at = NOW() + INTERVAL '$delay seconds' WHERE id = :id"
            );
            $update->execute([':id' => $id]);
        }
    }

    protected function mapToModel(array $row): JobModel
    {
        return new JobModel(
            id: (int)$row['id'],
            jobType: $row['job_type'],
            payload: json_decode($row['payload'], true) ?? [],
            status: $row['status'],
            attempts: (int)$row['attempts'],
            runAt: $row['run_at'],
            createdAt: $row['created_at']
        );
    }

    protected function mapToModels(array $rows): array
    {
        return array_map([$this, 'mapToModel'], $rows);
    }
}
