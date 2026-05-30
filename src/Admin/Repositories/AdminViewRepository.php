<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Admin\Exceptions\DatabaseException;

class AdminViewRepository
{
    private PDO $pdo;
    
    private const VALID_ENTITIES = [
        'users', 'categories', 'products', 'orders', 'suppliers', 'warehouses',
        'stock', 'feedback', 'cocktail_recipes', 'carts', 'payments',
        'user_addresses', 'user_preferences', 'flavor_profiles',
        'order_items', 'recipe_ingredients', 'cart_items'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get list view data (paginated, for tables)
     */
    public function getList(string $entity, int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $this->validateEntity($entity);
        
        $viewName = "admin_list_" . $entity;
        
        $sql = "SELECT * FROM {$viewName}";
        $params = [];
        
        if ($search) {
            $sql .= $this->buildSearchClause($entity, $search);
            $params[':search'] = "%{$search}%";
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if ($search) {
            $stmt->bindValue(':search', "%{$search}%", PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    /**
     * Get detail view data (for modal)
     */
    public function getDetail(string $entity, int $id): ?array
    {
        $this->validateEntity($entity);
        
        $viewName = "admin_detail_" . $entity;
        $stmt = $this->pdo->prepare("SELECT * FROM {$viewName} WHERE id = :id");

        $stmt->execute([':id' => $id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        // Parse JSON fields
        return $this->parseJsonFields($row);
    }

    /**
     * Get count for pagination
     */
    public function getCount(string $entity, ?string $search = null): int
    {
        $this->validateEntity($entity);
        
        $viewName = "admin_list_" . $entity;
        $sql = "SELECT COUNT(*) FROM {$viewName}";
        $params = [];
        
        if ($search) {
            $sql .= $this->buildSearchClause($entity, $search);
            $params[':search'] = "%{$search}%";
        }
        
        $stmt = $this->pdo->prepare($sql);
        if ($search) {
            $stmt->bindValue(':search', "%{$search}%", PDO::PARAM_STR);
        }
        $stmt->execute();
        
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

public function getDashboardStats(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    // Helper scalar query (keeps using existing pattern)
    $g = fn(string $sql) => ($stmt = $this->pdo->query($sql)) ? (int)$stmt->fetchColumn() : 0;
    $gFloat = fn(string $sql) => ($stmt = $this->pdo->query($sql)) ? (float)$stmt->fetchColumn() : 0.0;

    // Users
    $totalUsers = $g("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND is_anonymized = FALSE");
    $admins = $g("SELECT COUNT(*) FROM users WHERE is_admin = TRUE AND deleted_at IS NULL");
    $newLast30 = $g("SELECT COUNT(*) FROM users WHERE created_at > NOW() - INTERVAL '30 days' AND deleted_at IS NULL");
    $newLast7 = $g("SELECT COUNT(*) FROM users WHERE created_at > NOW() - INTERVAL '7 days' AND deleted_at IS NULL");
    $prev7 = $g("SELECT COUNT(*) FROM users WHERE created_at BETWEEN NOW() - INTERVAL '14 days' AND NOW() - INTERVAL '7 days' AND deleted_at IS NULL");
    $activeToday = $g("SELECT COUNT(*) FROM users WHERE last_login_at > NOW() - INTERVAL '1 day' AND deleted_at IS NULL");

    $weekly_user_growth_pct = $prev7 === 0 ? ($newLast7 > 0 ? 100 : 0) : (int)round(100.0 * ($newLast7 - $prev7) / max(1, $prev7));

    // Orders & Revenue
    $ordersTotal = $g("SELECT COUNT(*) FROM orders");
    $ordersPending = $g("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $ordersPaidDelivered = $g("SELECT COUNT(*) FROM orders WHERE status IN ('paid','delivered')");
    $ordersToday = $g("SELECT COUNT(*) FROM orders WHERE created_at > CURRENT_DATE");

    $revenueTotal = $g("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status IN ('paid','delivered')");
    $revenueLast30 = $g("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status IN ('paid','delivered') AND created_at > NOW() - INTERVAL '30 days'");
    $revenueToday = $g("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status IN ('paid','delivered') AND created_at > CURRENT_DATE");
    $avgOrderValue = $g("SELECT COALESCE(AVG(total_cents),0) FROM orders WHERE status IN ('paid','delivered')");

    // Conversion & Carts
    $cartsTotal = $g("SELECT COUNT(*) FROM carts");
    $cartsConverted = $g("SELECT COUNT(*) FROM carts WHERE status = 'converted'");
    $cartsAbandoned = $g("SELECT COUNT(*) FROM carts WHERE status = 'abandoned'");
    $conversion_rate_pct = $cartsTotal === 0 ? 0 : (int)round(100.0 * $cartsConverted / $cartsTotal);
    $abandoned_rate_pct = $cartsTotal === 0 ? 0 : (int)round(100.0 * $cartsAbandoned / $cartsTotal);

    // Fulfillment / Logistics
    // average time from paid_at (or created_at) to delivered_at in hours
    $avg_time_to_deliver_hours = (int)round($gFloat("SELECT COALESCE(AVG(EXTRACT(EPOCH FROM (delivered_at - COALESCE(paid_at, created_at))))/3600,0) FROM orders WHERE delivered_at IS NOT NULL"));

    // Items per order (avg quantity)
    $avg_items_per_order = (int)round($gFloat("SELECT COALESCE(AVG(items),0) FROM (SELECT order_id, SUM(quantity) as items FROM order_items GROUP BY order_id) t"));

    // Repeat customers
    $repeat_customers = $g("SELECT COALESCE(SUM(CASE WHEN cnt>1 THEN 1 ELSE 0 END),0) FROM (SELECT user_id, COUNT(*) as cnt FROM orders WHERE user_id IS NOT NULL GROUP BY user_id) t");
    $customers_with_orders = $g("SELECT COUNT(*) FROM (SELECT user_id FROM orders WHERE user_id IS NOT NULL GROUP BY user_id) t");
    $repeat_customer_rate_pct = $customers_with_orders === 0 ? 0 : (int)round(100.0 * $repeat_customers / $customers_with_orders);

    // Products & Inventory health
    $productsTotal = $g("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL");
    $productsActive = $g("SELECT COUNT(*) FROM products WHERE is_active = TRUE AND deleted_at IS NULL");

    $inventory_total_items = $g("SELECT COALESCE(SUM(quantity),0) FROM stock");
    $inventory_reserved = $g("SELECT COALESCE(SUM(reserved),0) FROM stock");
    $inventory_available = $g("SELECT COALESCE(SUM(quantity - reserved),0) FROM stock");

    $low_stock_products = $g("SELECT COUNT(*) FROM (SELECT product_id, COALESCE(SUM(quantity - reserved),0) as available FROM stock GROUP BY product_id) t WHERE available < 10");
    $out_of_stock_products = $g("SELECT COUNT(*) FROM (SELECT product_id, COALESCE(SUM(quantity - reserved),0) as available FROM stock GROUP BY product_id) t WHERE available = 0");

    // Inventory value (in cents) based on product price * available stock
    $inventory_value_cents = $g("SELECT COALESCE(SUM(p.price_cents * (s.quantity - s.reserved)),0) FROM stock s JOIN products p ON s.product_id = p.id WHERE p.deleted_at IS NULL");

    // Top products (by revenue) - small list for quick action
    $topProductsStmt = $this->pdo->query(
        "SELECT p.id, p.name, COALESCE(SUM(oi.quantity * oi.price_cents),0)::bigint AS revenue_cents, COALESCE(SUM(oi.quantity),0)::bigint AS total_sold
         FROM order_items oi
         JOIN products p ON oi.product_id = p.id
         GROUP BY p.id
         ORDER BY revenue_cents DESC
         LIMIT 5"
    );
    $top_products = $topProductsStmt ? $topProductsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Low performing products (no sales last 90 days but have stock) - candidate for promotions / delist
    $low_perf_stmt = $this->pdo->query(
        "SELECT p.id, p.name, COALESCE(SUM(s.quantity - s.reserved),0) AS available_stock
         FROM products p
         JOIN stock s ON s.product_id = p.id
         WHERE p.deleted_at IS NULL
         GROUP BY p.id, p.name
         HAVING COALESCE(SUM(s.quantity - s.reserved),0) > 0
         AND NOT EXISTS (
             SELECT 1 FROM order_items oi JOIN orders o ON oi.order_id = o.id 
             WHERE oi.product_id = p.id AND o.created_at > NOW() - INTERVAL '90 days'
         )
         ORDER BY available_stock DESC
         LIMIT 10"
    );
    $low_performing_products = $low_perf_stmt ? $low_perf_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $cache = [
        'users' => [
            'total' => $totalUsers,
            'admins' => $admins,
            'active_today' => $activeToday,
            'new_last_7_days' => $newLast7,
            'new_last_30_days' => $newLast30,
            'weekly_user_growth_pct' => $weekly_user_growth_pct
        ],
        'orders' => [
            'total' => $ordersTotal,
            'pending' => $ordersPending,
            'paid_or_delivered' => $ordersPaidDelivered,
            'today' => $ordersToday,
            'avg_items_per_order' => $avg_items_per_order,
            'avg_time_to_deliver_hours' => $avg_time_to_deliver_hours,
            'avg_order_value_cents' => $avgOrderValue,
            'conversion_rate_pct' => $conversion_rate_pct,
            'abandoned_cart_rate_pct' => $abandoned_rate_pct,
            'repeat_customer_rate_pct' => $repeat_customer_rate_pct
        ],
        'revenue' => [
            'total_cents' => $revenueTotal,
            'last_30_days_cents' => $revenueLast30,
            'today_cents' => $revenueToday,
            'avg_order_value_cents' => $avgOrderValue
        ],
        'products' => [
            'total' => $productsTotal,
            'active' => $productsActive,
            'low_stock_products' => $low_stock_products,
            'out_of_stock_products' => $out_of_stock_products,
            'inventory_total_items' => $inventory_total_items,
            'inventory_reserved' => $inventory_reserved,
            'inventory_available' => $inventory_available,
            'inventory_value_cents' => $inventory_value_cents,
            'top_products_by_revenue' => $top_products,
            'low_performing_products' => $low_performing_products
        ],
        // Warehouse stock breakdown for dashboard visualization
        'warehouses' => (function() {
            $stmt = $this->pdo->query(
                "SELECT w.id, w.name,
                    COALESCE(SUM(s.quantity), 0)::int AS total_stock,
                    COALESCE(SUM(s.reserved), 0)::int AS reserved_stock,
                    COALESCE(SUM(s.quantity - s.reserved), 0)::int AS available_stock,
                    COUNT(DISTINCT s.product_id)::int AS product_count
                 FROM warehouses w
                 LEFT JOIN stock s ON s.warehouse_id = w.id
                 WHERE w.deleted_at IS NULL AND w.is_active = TRUE
                 GROUP BY w.id, w.name
                 ORDER BY total_stock DESC"
            );
            return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        })(),
        // quick summary for admin UI
        'summary' => [
            'users' => $totalUsers,
            'orders' => $ordersTotal,
            'revenue_total_cents' => $revenueTotal,
            'inventory_value_cents' => $inventory_value_cents
        ],
        'generated_at' => (string)date('c')
    ];

    return $cache;
}


    private function validateEntity(string $entity): void
    {
        if (!in_array($entity, self::VALID_ENTITIES)) {
            throw new DatabaseException("Invalid entity: {$entity}");
        }
    }

    private function buildSearchClause(string $entity, string $search): string
    {
       $searchFields = [
    'users' => ['name', 'email', 'phone'],
    'products' => ['product_name', 'slug', 'category_name', 'supplier_name'],
    'orders' => ['order_number', 'user_name', 'user_email'],
    'categories' => ['name', 'slug'],
    'suppliers' => ['name', 'email', 'phone'],
    'warehouses' => ['name', 'phone'],
    'stock' => ['product_name', 'warehouse_name'],
    'carts' => ['session_id', 'user_name', 'user_email'],
    'cart_items' => ['product_name'],
    'order_items' => ['product_name', 'order_number', 'warehouse_name'],
    'payments' => ['gateway', 'order_number'],
    'user_addresses' => ['recipient_name', 'city', 'country'],
    'user_preferences' => ['user_name', 'user_email'],
    'flavor_profiles' => ['product_name'],
    'feedback' => ['user_name', 'product_name'],
    'cocktail_recipes' => ['name'],
    'recipe_ingredients' => ['product_name', 'recipe_name'],
];

        
        $fields = $searchFields[$entity] ?? ['name'];
        
        $conditions = array_map(fn($field) => "{$field} ILIKE :search", $fields);
        return " WHERE " . implode(' OR ', $conditions);
    }

    private function parseJsonFields(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value) && !empty($value) && ($value[0] === '{' || $value[0] === '[')) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$key] = $decoded;
                }
            }
        }
        return $row;
    }
}
