<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Core\Database;
use App\Admin\Models\OrderModel;
use App\Admin\Exceptions\DatabaseException;

class OrderRepository extends BaseRepository
{
    
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getById(int $id): ?OrderModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->bindValue(":id",$id,PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByOrderNumber(string $orderNumber): ?OrderModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE order_number = :order_number");
        $stmt->execute([':order_number' => $orderNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
             FROM orders o WHERE o.user_id = :user_id 
             ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM orders");
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    /**
     * Search orders by order number, status, or user info
     */
    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $searchTerm = "%{$query}%";
        $stmt = $this->pdo->prepare("
            SELECT o.* FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.order_number ILIKE :query
               OR o.status::text ILIKE :query
               OR u.name ILIKE :query
               OR u.email ILIKE :query
            ORDER BY o.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':query', $searchTerm);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): OrderModel
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO orders (cart_id, user_id, total_cents, shipping_address_id, billing_address_id, notes) 
             VALUES (:cart_id, :user_id, :total_cents, :shipping_address_id, :billing_address_id, :notes) 
             RETURNING *"
        );
        $stmt->execute([
            ':cart_id' => $data['cart_id'],
            ':user_id' => $data['user_id'] ?? null,
            ':total_cents' => $data['total_cents'],
            ':shipping_address_id' => $data['shipping_address_id'] ?? null,
            ':billing_address_id'  => $data['billing_address_id'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DatabaseException('Failed to create order');
        return $this->mapToModel($row);
    }

    public function update(int $id, array $data): ?OrderModel
    {
        $sets = [];
        $params = [':id' => $id];

        foreach (['status', 'notes', 'shipping_address_id', 'billing_address_id','total_cents'] as $col) {
            if (isset($data[$col])) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($sets)) return null;

        $sql = "UPDATE orders SET " . implode(', ', $sets) . ", updated_at = NOW() 
                WHERE id = :id RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }
    /**
     * Updates an order's status to 'cancelled' and records the cancellation timestamp.
     * @param int $id The order ID.
     * @return OrderModel|null The updated order model, or null if not found.
     */
    public function cancelOrder(int $id): ?OrderModel
    {
        $sql = "
            UPDATE orders 
            SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW()
            WHERE id = :id 
            RETURNING *
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $this->mapToModel($row) : null;
    }
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM orders WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
   /**
     * Fetches an order with all associated details (items, payments, addresses).
     * @param int $id The order ID.
     * @return array|null The detailed order data as an associative array, or null if not found.
     */
    public function getDetailedOrderById(int $id): ?array
    {
        $sql = "
            SELECT 
                o.id, o.order_number, o.status, o.total_cents, o.notes,
                o.created_at, o.updated_at, o.paid_at, o.shipped_at, o.delivered_at, o.cancelled_at,
                o.user_id, u.name as user_name, u.email as user_email, u.phone as user_phone,
                
                -- Shipping Address (row_to_json generates a JSON object or NULL)
                (SELECT row_to_json(sa) FROM (
                    SELECT recipient_name, phone, address_line1, address_line2, city, state, postal_code, country
                    FROM user_addresses WHERE id = o.shipping_address_id
                ) sa) as shipping_address,
                
                -- Billing Address (row_to_json generates a JSON object or NULL)
                (SELECT row_to_json(ba) FROM (
                    SELECT recipient_name, phone, address_line1, address_line2, city, state, postal_code, country
                    FROM user_addresses WHERE id = o.billing_address_id
                ) ba) as billing_address,
                
                -- Items (JSON_AGG generates a JSON array or NULL)
                (SELECT JSON_AGG(row_to_json(t)) FROM (
                    SELECT oi.id, oi.product_id, oi.product_name, oi.product_image_url, 
                           oi.quantity, oi.price_cents, (oi.quantity * oi.price_cents) as subtotal_cents,
                           w.name as warehouse_name
                    FROM order_items oi
                    LEFT JOIN warehouses w ON oi.warehouse_id = w.id
                    WHERE oi.order_id = o.id
                ) t) as items,
                
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                
                -- Payments (JSON_AGG generates a JSON array or NULL)
                (SELECT JSON_AGG(row_to_json(t)) FROM (
                    SELECT id, amount_cents, currency, gateway, transaction_id, status, created_at
                    FROM payments
                    WHERE order_id = o.id
                ) t) as payments,
                
                -- Cart info (for context/debugging)
                o.cart_id,
                (SELECT session_id FROM carts WHERE id = o.cart_id) as cart_session_id
                
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
          
            $row['shipping_address'] = json_decode($row['shipping_address'] ?? '{}', true);
            $row['billing_address'] = json_decode($row['billing_address'] ?? '{}', true);
            
            $row['items'] = json_decode($row['items'] ?? '[]', true);
            $row['payments'] = json_decode($row['payments'] ?? '[]', true);
        }

        return $row ?: null;
    }
    protected function mapToModel(array $row): OrderModel
    {
        return new OrderModel(
            id: (int)$row['id'],
            order_number: $row['order_number'],
            cart_id: (int)$row['cart_id'],
            user_id: $row['user_id'] ? (int)$row['user_id'] : null,
            status: $row['status'],
            total_cents: (int)$row['total_cents'],
            shipping_address_id: $row['shipping_address_id'] ? (int)$row['shipping_address_id'] : null,
            billing_address_id: $row['billing_address_id'] ? (int)$row['billing_address_id'] : null,
            notes: $row['notes'],
            created_at: $row['created_at'],
            updated_at: $row['updated_at'],
            paid_at: $row['paid_at'],
            shipped_at: $row['shipped_at'],
            delivered_at: $row['delivered_at'],
            cancelled_at: $row['cancelled_at'],
            item_count: isset($row['item_count']) ? (int)$row['item_count'] : null
        );
    }

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
