<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use PDOStatement;
use App\Admin\Exceptions\DatabaseException;

abstract class BaseRepository
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function executeStatement(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->executeStatement($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->executeStatement($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    protected function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->executeStatement($sql, $params)->fetchColumn();
    }

    protected function getLastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    protected function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    protected function commit(): void
    {
        $this->pdo->commit();
    }

    protected function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    abstract protected function mapToModel(array $row): object;
    abstract protected function mapToModels(array $rows): array;
}
