<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Core\Database;
use App\Admin\Models\CartItemModel;
use App\Admin\Exceptions\DatabaseException;

class CartItemRepository extends BaseRepository
{

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM cart_items ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getAllPaginated(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ci.*,
                p.name as product_name,
                p.image_url as product_image,
                c.session_id,
                c.status as cart_status,
                u.name as user_name,
                (ci.price_at_add_cents * ci.quantity) as subtotal_cents,
                (p.price_cents - ci.price_at_add_cents) as price_difference_cents,
                p.price_cents as current_price_cents
            FROM cart_items ci
            LEFT JOIN products p ON ci.product_id = p.id
            LEFT JOIN carts c ON ci.cart_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            ORDER BY ci.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Search cart items by product name, user name, or session ID
     */
    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $searchTerm = '%' . strtolower($query) . '%';
        
        $stmt = $this->pdo->prepare("
            SELECT 
                ci.*,
                p.name as product_name,
                p.image_url as product_image,
                c.session_id,
                c.status as cart_status,
                u.name as user_name,
                (ci.price_at_add_cents * ci.quantity) as subtotal_cents,
                (p.price_cents - ci.price_at_add_cents) as price_difference_cents,
                p.price_cents as current_price_cents
            FROM cart_items ci
            LEFT JOIN products p ON ci.product_id = p.id
            LEFT JOIN carts c ON ci.cart_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE LOWER(p.name) LIKE :query
               OR LOWER(u.name) LIKE :query
               OR LOWER(c.session_id) LIKE :query
            ORDER BY ci.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getByIdEnriched(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ci.*,
                p.name as product_name,
                p.image_url as product_image,
                c.session_id,
                c.status as cart_status,
                u.name as user_name,
                (ci.price_at_add_cents * ci.quantity) as subtotal_cents,
                (p.price_cents - ci.price_at_add_cents) as price_difference_cents,
                p.price_cents as current_price_cents
            FROM cart_items ci
            LEFT JOIN products p ON ci.product_id = p.id
            LEFT JOIN carts c ON ci.cart_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE ci.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getById(int $id): ?CartItemModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cart_items WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByCartProduct(int $cartId, int $productId): ?CartItemModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id"
        );
        $stmt->execute([
            ':cart_id' => $cartId,
            ':product_id' => $productId
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByCart(int $cartId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ci.*,
                p.name as name,
                p.image_url as image_url,
                p.price_cents as current_price_cents
            FROM cart_items ci
            LEFT JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = :cart_id
        ");
        $stmt->execute([':cart_id' => $cartId]);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM cart_items");
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    public function create(array $data): CartItemModel
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO cart_items (cart_id, product_id, quantity, price_at_add_cents) 
             VALUES (:cart_id, :product_id, :quantity, :price_at_add_cents) 
             RETURNING *"
        );
        $stmt->execute([
            ':cart_id' => $data['cart_id'],
            ':product_id' => $data['product_id'],
            ':quantity' => $data['quantity'],
            ':price_at_add_cents' => $data['price_at_add_cents']
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DatabaseException('Failed to create cart item');
        return $this->mapToModel($row);
    }

    public function update(int $id, array $data): ?CartItemModel
    {
        $sets = [];
        $params = [':id' => $id];

        foreach (['quantity', 'price_at_add_cents'] as $col) {
            if (isset($data[$col])) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($sets)) return null;

        $sql = "UPDATE cart_items SET " . implode(', ', $sets) . ", updated_at = NOW() 
                WHERE id = :id RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function updateByCartProduct(int $cartId, int $productId, array $data): ?CartItemModel
    {
        $sets = [];
        $params = [':cart_id' => $cartId, ':product_id' => $productId];

        foreach (['quantity', 'price_at_add_cents'] as $col) {
            if (isset($data[$col])) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($sets)) return null;

        $sql = "UPDATE cart_items SET " . implode(', ', $sets) . ", updated_at = NOW() 
                WHERE cart_id = :cart_id AND product_id = :product_id RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteByCartProduct(int $cartId, int $productId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id"
        );
        $stmt->execute([
            ':cart_id' => $cartId,
            ':product_id' => $productId
        ]);
        return $stmt->rowCount() > 0;
    }

    protected function mapToModel(array $row): CartItemModel
    {
        return new CartItemModel(
            id: (int)$row['id'],
            cart_id: (int)$row['cart_id'],
            product_id: (int)$row['product_id'],
            quantity: (int)$row['quantity'],
            price_at_add_cents: (int)$row['price_at_add_cents'],
            created_at: $row['created_at'],
            updated_at: $row['updated_at']
        );
    }

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
