<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Core\Database;
use App\Admin\Models\OrderItemModel;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

class OrderItemRepository extends BaseRepository
{

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM order_items ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getById(int $id): ?OrderItemModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM order_items WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByOrder(int $orderId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Get all order items with pagination and enriched data (order details, product info)
     */
    public function getAllPaginated(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
                    oi.id,
                    oi.order_id,
                    oi.product_id,
                    oi.product_name,
                    oi.product_image_url,
                    oi.price_cents,
                    oi.quantity,
                    oi.warehouse_id,
                    oi.created_at,
                    o.status as order_status,
                    o.total_cents as order_total,
                    u.name as user_name,
                    u.email as user_email,
                    p.name as current_product_name,
                    p.image_url as current_product_image
                FROM order_items oi
                LEFT JOIN orders o ON oi.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN products p ON oi.product_id = p.id
                ORDER BY oi.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Get order item by ID with enriched data (order, product, user details)
     */
    public function getByIdEnriched(int $id): ?array
    {
        $sql = "SELECT 
                    oi.id,
                    oi.order_id,
                    oi.product_id,
                    oi.product_name,
                    oi.product_image_url,
                    oi.price_cents,
                    oi.quantity,
                    oi.warehouse_id,
                    oi.created_at,
                    o.status as order_status,
                    o.total_cents as order_total,
                    o.created_at as order_created_at,
                    u.name as user_name,
                    u.email as user_email,
                    p.name as current_product_name,
                    p.image_url as current_product_image,
                    p.price_cents as current_product_price
                FROM order_items oi
                LEFT JOIN orders o ON oi.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.id = :id
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

public function update(int $id, array $data): OrderItemModel
{
    // Only allow updating quantity and warehouse_id
    $allowedFields = [];
    $params = [':id' => $id];
    
    if (isset($data['quantity'])) {
        $allowedFields[] = 'quantity = :quantity';
        $params[':quantity'] = $data['quantity'];
    }
    
    if (isset($data['warehouse_id'])) {
        $allowedFields[] = 'warehouse_id = :warehouse_id';
        $params[':warehouse_id'] = $data['warehouse_id'];
    }
    
    if (empty($allowedFields)) {
        throw new \InvalidArgumentException('No valid fields to update');
    }
    
    $sql = "UPDATE order_items SET " . implode(', ', $allowedFields) . " WHERE id = :id RETURNING *";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new NotFoundException("Order item with ID $id not found");
    }
    
    return $this->mapToModel($row);
}
    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM order_items");
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    public function create(array $data): OrderItemModel
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, product_name, product_image_url, 
              price_cents, quantity, warehouse_id) 
             VALUES (:order_id, :product_id, :product_name, :product_image_url, 
              :price_cents, :quantity, :warehouse_id) 
             RETURNING *"
        );
        $stmt->execute([
            ':order_id' => $data['order_id'],
            ':product_id' => $data['product_id'],
            ':product_name' => $data['product_name'],
            ':product_image_url' => $data['product_image_url'] ?? null,
            ':price_cents' => $data['price_cents'],
            ':quantity' => $data['quantity'],
            ':warehouse_id' => $data['warehouse_id'] ?? null
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DatabaseException('Failed to create order item');
        return $this->mapToModel($row);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM order_items WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    protected function mapToModel(array $row): OrderItemModel
{
    return new OrderItemModel(
        id: (int)$row['id'],
        order_id: (int)$row['order_id'],
        product_id: (int)$row['product_id'],
        product_name: $row['product_name'],
        product_image_url: $row['product_image_url'],
        price_cents: (int)$row['price_cents'],
        quantity: (int)$row['quantity'],
        warehouse_id: isset($row['warehouse_id']) ? (int)$row['warehouse_id'] : null, // ADD THIS
        created_at: $row['created_at']
    );
}

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
