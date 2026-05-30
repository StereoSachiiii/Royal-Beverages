<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Admin\Models\FeedbackModel;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

class FeedbackRepository extends BaseRepository
{
    public function create(array $data): FeedbackModel
    {
        $sql = "INSERT INTO feedback (user_id, product_id, rating, comment, is_verified_purchase, is_active)
                VALUES (:user_id, :product_id, :rating, :comment, :is_verified_purchase, :is_active)
                RETURNING *";
        
        // PostgreSQL needs 't'/'f' for boolean, not empty strings
        $verified = filter_var($data['is_verified_purchase'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $active = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        $stmt = $this->executeStatement($sql, [
            ':user_id' => $data['user_id'],
            ':product_id' => $data['product_id'],
            ':rating' => $data['rating'],
            ':comment' => $data['comment'] ?? null,
            ':is_verified_purchase' => $verified ? 't' : 'f',
            ':is_active' => $active ? 't' : 'f'
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new DatabaseException('Failed to create feedback');
        }
        return $this->mapToModel($row);
    }

    public function getById(int $id): ?FeedbackModel
    {
        $sql = "SELECT * FROM feedback WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $row = $this->fetchOne($sql, [':id' => $id]);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getAllWithProductDetails(): array
    {
        $sql = "SELECT 
                    f.id,
                    f.user_id,
                    f.product_id,
                    f.rating,
                    f.comment,
                    f.is_verified_purchase,
                    f.is_active,
                    f.created_at,
                    p.name as product_name,
                    p.image_url as product_image
                FROM feedback f
                JOIN products p ON f.product_id = p.id
                WHERE f.deleted_at IS NULL
                ORDER BY f.created_at DESC";
        
        $rows = $this->fetchAll($sql);
        return array_map(fn($row) => $this->mapToDetailedModel($row), $rows);
    }

    /**
     * Get all feedback with pagination and enriched data (user, product details)
     */
    public function getAllPaginated(int $limit = 50, int $offset = 0, ?bool $isActive = null): array
    {
        $where = ["f.deleted_at IS NULL"];
        $params = [
            ':limit' => $limit,
            ':offset' => $offset
        ];

        if ($isActive !== null) {
            $where[] = "f.is_active = :is_active";
            $params[':is_active'] = $isActive ? 't' : 'f';
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    f.id,
                    f.user_id,
                    f.product_id,
                    f.rating,
                    f.comment,
                    f.is_verified_purchase,
                    f.is_active,
                    f.created_at,
                    f.updated_at,
                    u.name as user_name,
                    u.email as user_email,
                    p.name as product_name,
                    p.slug as product_slug,
                    (SELECT COUNT(*) FROM orders o 
                     INNER JOIN order_items oi ON o.id = oi.order_id 
                     WHERE oi.product_id = f.product_id 
                       AND o.user_id = f.user_id 
                       AND o.status IN ('paid', 'shipped', 'delivered')
                    ) as purchase_count
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN products p ON f.product_id = p.id
                WHERE $whereClause
                ORDER BY f.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->execute();
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Get feedback by ID with enriched data (user, product details, purchase count)
     * This matches what admin_detail_feedback view would return
     */
    public function getByIdEnriched(int $id): ?array
    {
        $sql = "SELECT 
                    f.id,
                    f.user_id,
                    f.product_id,
                    f.rating,
                    f.comment,
                    f.is_verified_purchase,
                    f.is_active,
                    f.created_at,
                    f.updated_at,
                    u.name as user_name,
                    u.email as user_email,
                    p.name as product_name,
                    p.slug as product_slug,
                    (SELECT COUNT(*) FROM orders o 
                     INNER JOIN order_items oi ON o.id = oi.order_id 
                     WHERE oi.product_id = f.product_id 
                       AND o.user_id = f.user_id 
                       AND o.status IN ('paid', 'shipped', 'delivered')
                    ) as purchase_count
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN products p ON f.product_id = p.id
                WHERE f.id = :id AND f.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    public function exists(int $userId, int $productId): bool
    {
        $sql = "SELECT COUNT(*) FROM feedback 
                 WHERE user_id = :user_id AND product_id = :product_id AND deleted_at IS NULL";
        return (int)$this->fetchColumn($sql, [
            ':user_id' => $userId,
            ':product_id' => $productId
        ]) > 0;
    }

    public function update(int $id, array $data): ?FeedbackModel
    {
        $sets = [];
        $params = [':id' => $id];

        $allowedFields = ['user_id', 'product_id', 'rating', 'comment', 'is_verified_purchase', 'is_active'];
        
        foreach ($allowedFields as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = :$col";
                // Handle boolean conversion for PostgreSQL
                if (in_array($col, ['is_verified_purchase', 'is_active'])) {
                    $params[":$col"] = filter_var($data[$col] ?? false, FILTER_VALIDATE_BOOLEAN) ? 't' : 'f';
                } else {
                    $params[":$col"] = $data[$col];
                }
            }
        }

        if (empty($sets)) {
            return $this->getById($id);
        }

        $sets[] = "updated_at = NOW()";

        $sql = "UPDATE feedback SET " . implode(', ', $sets) . " 
                WHERE id = :id AND deleted_at IS NULL RETURNING *";
        
        $stmt = $this->executeStatement($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function softDelete(int $id): bool
    {
        $sql = "UPDATE feedback SET deleted_at = NOW() 
                 WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->executeStatement($sql, [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->executeStatement("DELETE FROM feedback WHERE id = :id", [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    protected function mapToModel(array $row): FeedbackModel
    {
        return new FeedbackModel(
            id: (int)$row['id'],
            userId: (int)$row['user_id'],
            productId: (int)$row['product_id'],
            rating: (int)$row['rating'],
            comment: $row['comment'],
            isVerifiedPurchase: (bool)$row['is_verified_purchase'],
            isActive: (bool)$row['is_active'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'] ?? $row['created_at'],
            deletedAt: $row['deleted_at'] ?? null
        );
    }

    protected function mapToDetailedModel(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'product_id' => (int)$row['product_id'],
            'rating' => (int)$row['rating'],
            'comment' => $row['comment'],
            'is_verified_purchase' => (bool)$row['is_verified_purchase'],
            'is_active' => (bool)$row['is_active'],
            'created_at' => $row['created_at'],
            'product_name' => $row['product_name'],
            'product_image' => $row['product_image']
        ];
    }

    public function getTopTestimonials(int $limit = 6): array
    {
        $sql = "SELECT 
                    f.rating, 
                    f.comment, 
                    f.created_at,
                    u.name as user_name,
                    ua.city, 
                    ua.country
                FROM feedback f
                JOIN users u ON f.user_id = u.id
                LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = true
                WHERE f.is_active = true 
                  AND f.rating >= 4 
                  AND f.deleted_at IS NULL
                  AND f.comment IS NOT NULL
                  AND f.comment != ''
                ORDER BY f.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}

