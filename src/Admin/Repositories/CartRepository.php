<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Core\Database;
use App\Admin\Models\CartModel;
use App\Admin\Exceptions\DatabaseException;

class CartRepository extends BaseRepository
{

    /**
     * Get all carts with pagination and enriched user data
     */
    public function getAllPaginated(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
                    c.*,
                    u.name as user_name,
                    u.email as user_email
                FROM carts c
                LEFT JOIN users u ON c.user_id = u.id
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        // Map to array (not models) so we keep joined fields
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Search carts by user name, email, or session ID
     */
    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $searchTerm = '%' . strtolower($query) . '%';
        
        $sql = "SELECT 
                    c.*,
                    u.name as user_name,
                    u.email as user_email
                FROM carts c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE LOWER(u.name) LIKE :query
                   OR LOWER(u.email) LIKE :query
                   OR LOWER(c.session_id) LIKE :query
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Get cart by ID with enriched data (user details + items)
     */
    public function getByIdEnriched(int $id): ?array
    {
        // 1. Fetch Cart + User
        $sql = "SELECT 
                    c.*,
                    u.name as user_name,
                    u.email as user_email
                FROM carts c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.id = :id
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart) return null;

        // 2. Fetch Items
        // Using explicit columns to avoid clutter, join with products for name/image
        // Assuming products table exists and has name/image_url
        $sqlItems = "SELECT 
                        ci.*,
                        p.name as product_name,
                        p.image_url as product_image
                     FROM cart_items ci
                     LEFT JOIN products p ON ci.product_id = p.id
                     WHERE ci.cart_id = :cart_id
                     ORDER BY ci.created_at ASC";
                     
        $stmtItems = $this->pdo->prepare($sqlItems);
        $stmtItems->execute([':cart_id' => $id]);
        $cart['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        
        return $cart;
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM carts ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getById(int $id): ?CartModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM carts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getActiveByUser(int $userId): ?CartModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM carts WHERE user_id = :user_id AND status = 'active'"
        );
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getActiveBySession(string $sessionId): ?CartModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM carts WHERE session_id = :session_id AND status = 'active'"
        );
        $stmt->execute([':session_id' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM carts");
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    public function create(array $data): CartModel
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO carts (user_id, session_id) 
             VALUES (:user_id, :session_id) 
             RETURNING *"
        );
        $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':session_id' => $data['session_id']
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DatabaseException('Failed to create cart');
        return $this->mapToModel($row);
    }

    public function update(int $id, array $data): ?CartModel
    {
        $sets = [];
        $params = [':id' => $id];

        foreach (['user_id', 'session_id', 'status', 'total_cents', 'item_count'] as $col) {
            if (isset($data[$col])) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($sets)) return null;

        $sql = "UPDATE carts SET " . implode(', ', $sets) . ", updated_at = NOW() 
                WHERE id = :id RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM carts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    protected function mapToModel(array $row): CartModel
    {
        return new CartModel(
            id: (int)$row['id'],
            user_id: $row['user_id'] ? (int)$row['user_id'] : null,
            session_id: $row['session_id'],
            status: $row['status'],
            total_cents: (int)$row['total_cents'],
            item_count: (int)$row['item_count'],
            created_at: $row['created_at'],
            updated_at: $row['updated_at'],
            converted_at: $row['converted_at'],
            abandoned_at: $row['abandoned_at']
        );
    }

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
