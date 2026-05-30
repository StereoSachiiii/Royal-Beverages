<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Core\Database;
use App\Admin\Models\ProductModel;
use App\Admin\Exceptions\DatabaseException;

class ProductRepository extends BaseRepository
{

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products 
             WHERE is_active = TRUE AND deleted_at IS NULL 
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getAllIncludingInactive(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products 
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getById(int $id): ?ProductModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products WHERE id = :id AND is_active = TRUE AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByIdAdmin(int $id): ?ProductModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getBySlug(string $slug): ?ProductModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products WHERE slug = :slug AND is_active = TRUE AND deleted_at IS NULL"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products 
             WHERE (name ILIKE :query OR description ILIKE :query) 
             AND is_active = TRUE AND deleted_at IS NULL 
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->execute([
            ':query' => "%$query%",
            ':limit' => $limit,
            ':offset' => $offset
        ]);
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function count(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM products WHERE is_active = TRUE AND deleted_at IS NULL"
        );
        return (int)$stmt->fetchColumn();
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM products");
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): ProductModel
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO products (name, slug, description, price_cents, image_url, category_id, supplier_id, is_active) 
             VALUES (:name, :slug, :description, :price_cents, :image_url, :category_id, :supplier_id, :is_active) 
             RETURNING *"
        );
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':slug', $data['slug'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':image_url', $data['image_url'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':category_id', $data['category_id'], PDO::PARAM_INT); 
        $stmt->bindValue(':supplier_id', $data['supplier_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':price_cents', $data['price_cents'], PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $data['is_active'] ?? true, PDO::PARAM_BOOL);

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DatabaseException('Failed to create product');
        return $this->mapToModel($row);
    }

    public function update(int $id, array $data): ?ProductModel
    {
        $sets = [];
        $params = [':id' => $id];

        foreach ([
    'name', 'slug', 'description', 'price_cents',
    'image_url', 'category_id', 'supplier_id', 'is_active'
] as $col)
 {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($sets)) return null;

        $sql = "UPDATE products SET " . implode(', ', $sets) . ", updated_at = NOW() 
                WHERE id = :id RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE products SET deleted_at = NOW(), is_active = FALSE, updated_at = NOW() 
             WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE products SET deleted_at = NULL, is_active = TRUE, updated_at = NOW() 
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getByName(string $data): ?ProductModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products WHERE name = :name AND is_active = TRUE AND deleted_at IS NULL"
        );
        $stmt->bindValue(':name', $data, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    protected function mapToModel(array $row): ProductModel
    {
        return new ProductModel(
            id: (int)$row['id'],
            name: $row['name'],
            slug: $row['slug'],
            description: $row['description'],
            price_cents: (int)$row['price_cents'],
            image_url: $row['image_url'],
            category_id: (int)$row['category_id'],
            supplier_id: $row['supplier_id'] !== null ? (int)$row['supplier_id'] : null,
            is_active: (bool)$row['is_active'],
            created_at: $row['created_at'],
            updated_at: $row['updated_at'],
            deleted_at: $row['deleted_at']
        );
    }
    public function getAllEnriched(int $limit = 50, int $offset = 0): array
{
    $stmt = $this->pdo->prepare("
        SELECT 
            p.id, p.category_id, p.name, p.slug, p.description, p.price_cents, p.image_url,
            p.is_active, p.created_at, p.updated_at,
            c.name as category_name,
            s.name as supplier_name,
            COALESCE(SUM(st.quantity - st.reserved), 0)::int as available_stock,
            (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) as is_available,
            COALESCE(SUM(oi.quantity), 0)::int as units_sold,
            ROUND(AVG(f.rating)::numeric, 1) as avg_rating,
            COUNT(f.id)::int as feedback_count,
            json_build_object(
                'sweetness', fp.sweetness,
                'bitterness', fp.bitterness,
                'strength', fp.strength,
                'smokiness', fp.smokiness,
                'fruitiness', fp.fruitiness,
                'spiciness', fp.spiciness,
                'tags', fp.tags
            ) as flavor_profile
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        LEFT JOIN stock st ON p.id = st.product_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('paid', 'shipped', 'delivered')
        LEFT JOIN feedback f ON p.id = f.product_id AND f.is_active = TRUE
        LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
        WHERE p.deleted_at IS NULL
        GROUP BY p.id, p.category_id, c.name, s.name, fp.sweetness, fp.bitterness, fp.strength, fp.smokiness, fp.fruitiness, fp.spiciness, fp.tags
        ORDER BY (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) DESC, p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Return assoc arrays for simplicity; extend ProductModel if needed
}

public function getByIdEnriched(int $id): ?array
{
    $stmt = $this->pdo->prepare("
        SELECT 
            p.id, p.category_id, p.name, p.slug, p.description, p.price_cents, p.image_url,
            p.is_active, p.created_at, p.updated_at,
            c.name as category_name,
            s.name as supplier_name,
            COALESCE(SUM(st.quantity - st.reserved), 0)::int as available_stock,
            (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) as is_available,
            COALESCE(SUM(oi.quantity), 0)::int as units_sold,
            ROUND(AVG(f.rating)::numeric, 1) as avg_rating,
            COUNT(f.id)::int as feedback_count,
            json_build_object(
                'sweetness', fp.sweetness,
                'bitterness', fp.bitterness,
                'strength', fp.strength,
                'smokiness', fp.smokiness,
                'fruitiness', fp.fruitiness,
                'spiciness', fp.spiciness,
                'tags', fp.tags
            ) as flavor_profile
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        LEFT JOIN stock st ON p.id = st.product_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('paid', 'shipped', 'delivered')
        LEFT JOIN feedback f ON p.id = f.product_id AND f.is_active = TRUE
        LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
        WHERE p.id = :id AND p.deleted_at IS NULL
        GROUP BY p.id, p.category_id, c.name, s.name, fp.sweetness, fp.bitterness, fp.strength, fp.smokiness, fp.fruitiness, fp.spiciness, fp.tags
    ");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

public function getTopSellers(int $limit = 10): array
{
    $stmt = $this->pdo->prepare("
        SELECT 
            p.id, p.category_id, p.name, p.slug, p.description, p.price_cents, p.image_url,
            p.is_active, p.created_at, p.updated_at,
            c.name as category_name,
            s.name as supplier_name,
            COALESCE(SUM(st.quantity - st.reserved), 0)::int as available_stock,
            (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) as is_available,
            COALESCE(SUM(oi.quantity), 0)::int as units_sold,
            ROUND(AVG(f.rating)::numeric, 1) as avg_rating,
            COUNT(f.id)::int as feedback_count,
            json_build_object(
                'sweetness', fp.sweetness,
                'bitterness', fp.bitterness,
                'strength', fp.strength,
                'smokiness', fp.smokiness,
                'fruitiness', fp.fruitiness,
                'spiciness', fp.spiciness,
                'tags', fp.tags
            ) as flavor_profile
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        LEFT JOIN stock st ON p.id = st.product_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('paid', 'shipped', 'delivered')
        LEFT JOIN feedback f ON p.id = f.product_id AND f.is_active = TRUE
        LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
        WHERE p.deleted_at IS NULL
        GROUP BY p.id, p.category_id, c.name, s.name, fp.sweetness, fp.bitterness, fp.strength, fp.smokiness, fp.fruitiness, fp.spiciness, fp.tags
        ORDER BY units_sold DESC, p.name ASC  -- Tie-breaker by name
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function searchEnriched(string $query, int $limit = 50, int $offset = 0): array
{
    $searchTerm = '%' . $query . '%';
    $stmt = $this->pdo->prepare("
        SELECT 
            p.id, p.category_id, p.name, p.slug, p.description, p.price_cents, p.image_url,
            p.is_active, p.created_at, p.updated_at,
            c.name as category_name,
            s.name as supplier_name,
            COALESCE(SUM(st.quantity - st.reserved), 0)::int as available_stock,
            (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) as is_available,
            COALESCE(SUM(oi.quantity), 0)::int as units_sold,
            ROUND(AVG(f.rating)::numeric, 1) as avg_rating,
            COUNT(f.id)::int as feedback_count,
            json_build_object(
                'sweetness', fp.sweetness,
                'bitterness', fp.bitterness,
                'strength', fp.strength,
                'smokiness', fp.smokiness,
                'fruitiness', fp.fruitiness,
                'spiciness', fp.spiciness,
                'tags', fp.tags
            ) as flavor_profile
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        LEFT JOIN stock st ON p.id = st.product_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('paid', 'shipped', 'delivered')
        LEFT JOIN feedback f ON p.id = f.product_id AND f.is_active = TRUE
        LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
        WHERE p.deleted_at IS NULL
        AND (p.name ILIKE :search OR p.description ILIKE :search OR c.name ILIKE :search OR array_to_string(fp.tags, ',') ILIKE :search)
        GROUP BY p.id, p.category_id, c.name, s.name, fp.sweetness, fp.bitterness, fp.strength, fp.smokiness, fp.fruitiness, fp.spiciness, fp.tags
        ORDER BY p.name ASC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':search', $searchTerm);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    public function shopAllEnriched(
    int $limit = 24,
    int $offset = 0,
    string $search = '',
    ?int $categoryId = null,
    ?int $minPrice = null,
    ?int $maxPrice = null,
    string $sort = 'newest'
): array {
    $params = [];
    $where  = ["p.is_active = TRUE", "p.deleted_at IS NULL"];
    $order  = "p.created_at DESC";

    if ($search !== '') {
        $where[] = "(p.name ILIKE :search OR p.description ILIKE :search OR c.name ILIKE :search)";
        $params[':search'] = "%$search%";
    }
    if ($categoryId !== null) {
        $where[] = "p.category_id = :category_id";
        $params[':category_id'] = $categoryId;
    }
    if ($minPrice !== null) {
        $where[] = "p.price_cents >= :min_price";
        $params[':min_price'] = $minPrice;
    }
    if ($maxPrice !== null) {
        $where[] = "p.price_cents <= :max_price";
        $params[':max_price'] = $maxPrice;
    }

    // Sorting
    $sortMap = [
        'newest'      => "p.created_at DESC",
        'price_asc'   => "p.price_cents ASC",
        'price_desc'  => "p.price_cents DESC",
        'name_asc'    => "p.name ASC",
        'name_desc'   => "p.name DESC",
        'popularity'  => "units_sold DESC, avg_rating DESC NULLS LAST"
    ];
    $order = $sortMap[$sort] ?? $sortMap['newest'];

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT 
            p.id, p.category_id, p.name, p.slug, p.description, p.price_cents, p.image_url,
            p.created_at, p.updated_at,
            c.name as category_name,
            s.name as supplier_name,
            COALESCE(SUM(st.quantity - st.reserved), 0)::int as available_stock,
            (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) as is_available,
            COALESCE(SUM(oi.quantity), 0)::int as units_sold,
            ROUND(AVG(f.rating)::numeric, 1) as avg_rating,
            COUNT(f.id)::int as feedback_count,
            COALESCE(json_build_object(
                'sweetness', fp.sweetness,
                'bitterness', fp.bitterness,
                'strength', fp.strength,
                'smokiness', fp.smokiness,
                'fruitiness', fp.fruitiness,
                'spiciness', fp.spiciness,
                'tags', COALESCE(fp.tags, '{}')
            ), json_build_object(
                'sweetness', 5, 'bitterness', 5, 'strength', 5,
                'smokiness', 5, 'fruitiness', 5, 'spiciness', 5, 'tags', '{}'
            )) as flavor_profile
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        LEFT JOIN stock st ON p.id = st.product_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('paid', 'shipped', 'delivered')
        LEFT JOIN feedback f ON p.id = f.product_id AND f.is_active = TRUE
        LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
        $whereSql
        GROUP BY p.id, p.category_id, c.name, s.name, fp.sweetness, fp.bitterness, fp.strength,
                 fp.smokiness, fp.fruitiness, fp.spiciness, fp.tags
        ORDER BY $order
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
