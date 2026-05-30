<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Admin\Models\WishlistItemModel;
use App\Admin\Exceptions\DatabaseException;

class WishlistRepository extends BaseRepository
{
    public function getByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT w.*, p.name as product_name, p.slug as product_slug, 
                   p.price_cents, p.image_url, p.is_active
            FROM wishlist_items w
            JOIN products p ON w.product_id = p.id
            WHERE w.user_id = :user_id
            ORDER BY w.created_at DESC
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new WishlistItemModel(
                id: (int)$row['id'],
                user_id: (int)$row['user_id'],
                product_id: (int)$row['product_id'],
                created_at: $row['created_at'],
                product_name: $row['product_name'],
                product_slug: $row['product_slug'],
                price_cents: (int)$row['price_cents'],
                image_url: $row['image_url'],
                is_active: (bool)$row['is_active']
            );
        }
        return $results;
    }

    public function addItem(int $userId, int $productId): ?WishlistItemModel
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO wishlist_items (user_id, product_id) 
            VALUES (:user_id, :product_id)
            ON CONFLICT (user_id, product_id) DO NOTHING
            RETURNING *
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            // Already exists
            return null;
        }

        return new WishlistItemModel(
            id: (int)$row['id'],
            user_id: (int)$row['user_id'],
            product_id: (int)$row['product_id'],
            created_at: $row['created_at']
        );
    }

    public function removeItem(int $userId, int $productId): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM wishlist_items 
            WHERE user_id = :user_id AND product_id = :product_id
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    public function syncBulk(int $userId, array $productIds): array
    {
        // 1. Insert all local items, ignoring conflicts
        if (!empty($productIds)) {
            $values = [];
            $params = [':user_id' => $userId];
            
            foreach ($productIds as $index => $pid) {
                // Ensure they are integers
                $pidInt = (int)$pid;
                $key = ":pid_$index";
                $values[] = "(:user_id, $key)";
                $params[$key] = $pidInt;
            }
            
            $valuesStr = implode(', ', $values);
            $sql = "INSERT INTO wishlist_items (user_id, product_id) VALUES $valuesStr ON CONFLICT DO NOTHING";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }
        
        // 2. Return the updated fully merged wishlist
        return $this->getByUserId($userId);
    }

    protected function mapToModel(array $row): WishlistItemModel
    {
        return new WishlistItemModel(
            id: (int)$row['id'],
            user_id: (int)$row['user_id'],
            product_id: (int)$row['product_id'],
            created_at: $row['created_at'],
            product_name: $row['product_name'] ?? null,
            product_slug: $row['product_slug'] ?? null,
            price_cents: isset($row['price_cents']) ? (int)$row['price_cents'] : null,
            image_url: $row['image_url'] ?? null,
            is_active: isset($row['is_active']) ? (bool)$row['is_active'] : null
        );
    }

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
