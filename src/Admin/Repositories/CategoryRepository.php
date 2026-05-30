<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Core\Database;
use App\Admin\Models\CategoryModel;
use App\Admin\Exceptions\DatabaseException;

class CategoryRepository extends BaseRepository
{

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories 
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
            "SELECT * FROM categories 
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->mapToModels($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getById(int $id): ?CategoryModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE id = :id AND is_active = TRUE AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByIdAdmin(int $id): ?CategoryModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByName(string $name): ?CategoryModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE name = :name AND deleted_at IS NULL"
        );
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getBySlug(string $slug): ?CategoryModel
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE slug = :slug AND is_active = TRUE AND deleted_at IS NULL"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories 
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
            "SELECT COUNT(*) FROM categories WHERE is_active = TRUE AND deleted_at IS NULL"
        );
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categories");
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    public function create(array $data): CategoryModel
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO categories (name, slug, description, image_url) 
             VALUES (:name, :slug, :description, :image_url) 
             RETURNING *"
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'] ?? null,
            ':description' => $data['description'] ?? null,
            ':image_url' => $data['image_url'] ?? null
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DatabaseException('Failed to create category');
        return $this->mapToModel($row);
    }

    public function update(int $id, array $data): ?CategoryModel
    {
        $sets = [];
        $params = [':id' => $id];

        foreach (['name', 'slug', 'description', 'image_url'] as $col) {
            if (isset($data[$col])) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($sets)) return null;

        $sql = "UPDATE categories SET " . implode(', ', $sets) . ", updated_at = NOW() 
                WHERE id = :id AND deleted_at IS NULL RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE categories SET deleted_at = NOW(), is_active = FALSE, updated_at = NOW() 
             WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE categories SET deleted_at = NULL, is_active = TRUE, updated_at = NOW() 
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getAllEnriched(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id, c.name, c.slug, c.description, c.image_url,
                c.is_active, c.created_at, c.updated_at,
                COUNT(DISTINCT p.id)::int as product_count,
                COALESCE(SUM(st.quantity - st.reserved), 0)::int as total_available_stock,
                (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) as has_stock,
                ROUND(AVG(p.price_cents)::numeric, 0)::int as avg_price_cents,
                MIN(p.price_cents) as min_price_cents,
                MAX(p.price_cents) as max_price_cents,
                (SELECT array_agg(DISTINCT s.name) FROM suppliers s
                 JOIN products p ON s.id = p.supplier_id
                 WHERE p.category_id = c.id AND p.is_active = TRUE) as suppliers,
                json_build_object(
                    'avg_sweetness', ROUND(AVG(fp.sweetness)::numeric, 1),
                    'avg_bitterness', ROUND(AVG(fp.bitterness)::numeric, 1),
                    'avg_strength', ROUND(AVG(fp.strength)::numeric, 1),
                    'avg_smokiness', ROUND(AVG(fp.smokiness)::numeric, 1),
                    'avg_fruitiness', ROUND(AVG(fp.fruitiness)::numeric, 1),
                    'avg_spiciness', ROUND(AVG(fp.spiciness)::numeric, 1),
                    'common_tags', COALESCE((
                        SELECT json_agg(tag ORDER BY cnt DESC)
                        FROM (
                            SELECT tag, COUNT(*) AS cnt
                            FROM flavor_profiles fp2
                            CROSS JOIN unnest(fp2.tags) AS tag
                            WHERE fp2.product_id IN (
                                SELECT p.id FROM products p
                                WHERE p.category_id = c.id
                                  AND p.is_active = TRUE
                                  AND p.deleted_at IS NULL
                            )
                            GROUP BY tag
                            ORDER BY COUNT(*) DESC
                            LIMIT 5
                        ) t(tag, cnt)
                    ), '[]')
                ) as flavor_summary
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.is_active = TRUE AND p.deleted_at IS NULL
            LEFT JOIN stock st ON p.id = st.product_id
            LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
            WHERE c.deleted_at IS NULL AND c.is_active = TRUE
            GROUP BY c.id
            ORDER BY c.name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getByIdEnriched(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id, c.name, c.slug, c.description, c.image_url,
                c.is_active, c.created_at, c.updated_at,
                COUNT(DISTINCT p.id)::int as product_count,
                COALESCE(SUM(st.quantity - st.reserved), 0)::int as total_available_stock,
                (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) as has_stock,
                ROUND(AVG(p.price_cents)::numeric, 0)::int as avg_price_cents,
                MIN(p.price_cents) as min_price_cents,
                MAX(p.price_cents) as max_price_cents,
                (SELECT array_agg(DISTINCT s.name) FROM suppliers s
                 JOIN products p ON s.id = p.supplier_id
                 WHERE p.category_id = c.id AND p.is_active = TRUE) as suppliers,
                json_build_object(
                    'avg_sweetness', ROUND(AVG(fp.sweetness)::numeric, 1),
                    'avg_bitterness', ROUND(AVG(fp.bitterness)::numeric, 1),
                    'avg_strength', ROUND(AVG(fp.strength)::numeric, 1),
                    'avg_smokiness', ROUND(AVG(fp.smokiness)::numeric, 1),
                    'avg_fruitiness', ROUND(AVG(fp.fruitiness)::numeric, 1),
                    'avg_spiciness', ROUND(AVG(fp.spiciness)::numeric, 1),
                    'common_tags', COALESCE((
                        SELECT json_agg(tag ORDER BY cnt DESC)
                        FROM (
                            SELECT tag, COUNT(*) AS cnt
                            FROM flavor_profiles fp2
                            CROSS JOIN unnest(fp2.tags) AS tag
                            WHERE fp2.product_id IN (
                                SELECT p.id FROM products p
                                WHERE p.category_id = c.id
                                  AND p.is_active = TRUE
                                  AND p.deleted_at IS NULL
                            )
                            GROUP BY tag
                            ORDER BY COUNT(*) DESC
                            LIMIT 5
                        ) t(tag, cnt)
                    ), '[]')
                ) as flavor_summary
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.is_active = TRUE AND p.deleted_at IS NULL
            LEFT JOIN stock st ON p.id = st.product_id
            LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
            WHERE c.id = :id AND c.deleted_at IS NULL AND c.is_active = TRUE
            GROUP BY c.id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function searchEnriched(string $query, int $limit = 50, int $offset = 0): array
    {
        $searchTerm = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id, c.name, c.slug, c.description, c.image_url,
                c.is_active, c.created_at, c.updated_at,
                COUNT(DISTINCT p.id)::int as product_count,
                COALESCE(SUM(st.quantity - st.reserved), 0)::int as total_available_stock,
                (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) as has_stock,
                ROUND(AVG(p.price_cents)::numeric, 0)::int as avg_price_cents,
                MIN(p.price_cents) as min_price_cents,
                MAX(p.price_cents) as max_price_cents,
                (SELECT array_agg(DISTINCT s.name) FROM suppliers s
                 JOIN products p ON s.id = p.supplier_id
                 WHERE p.category_id = c.id AND p.is_active = TRUE) as suppliers,
                json_build_object(
                    'avg_sweetness', ROUND(AVG(fp.sweetness)::numeric, 1),
                    'avg_bitterness', ROUND(AVG(fp.bitterness)::numeric, 1),
                    'avg_strength', ROUND(AVG(fp.strength)::numeric, 1),
                    'avg_smokiness', ROUND(AVG(fp.smokiness)::numeric, 1),
                    'avg_fruitiness', ROUND(AVG(fp.fruitiness)::numeric, 1),
                    'avg_spiciness', ROUND(AVG(fp.spiciness)::numeric, 1),
                    'common_tags', COALESCE((
                        SELECT json_agg(tag ORDER BY cnt DESC)
                        FROM (
                            SELECT tag, COUNT(*) AS cnt
                            FROM flavor_profiles fp2
                            CROSS JOIN unnest(fp2.tags) AS tag
                            WHERE fp2.product_id IN (
                                SELECT p.id FROM products p
                                WHERE p.category_id = c.id
                                  AND p.is_active = TRUE
                                  AND p.deleted_at IS NULL
                            )
                            GROUP BY tag
                            ORDER BY COUNT(*) DESC
                            LIMIT 5
                        ) t(tag, cnt)
                    ), '[]')
                ) as flavor_summary
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.is_active = TRUE AND p.deleted_at IS NULL
            LEFT JOIN stock st ON p.id = st.product_id
            LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
            WHERE c.deleted_at IS NULL AND c.is_active = TRUE
              AND (c.name ILIKE :search OR c.description ILIKE :search)
            GROUP BY c.id
            ORDER BY c.name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':search', $searchTerm);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getProductsByCategoryIdEnriched(int $categoryId, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.slug,
                p.description,
                p.price_cents,
                p.image_url,
                p.created_at,
                COALESCE(SUM(st.quantity - st.reserved), 0)::int AS available_stock,
                (COALESCE(SUM(st.quantity - st.reserved), 0) > 0) AS is_available,
                COALESCE(SUM(oi.quantity), 0)::int AS units_sold,
                ROUND(AVG(f.rating)::numeric, 1) AS avg_rating,
                COUNT(f.id)::int AS feedback_count,
                s.name AS supplier_name,
                c.name AS category_name,
                c.slug AS category_slug,
                json_build_object(
                    'sweetness', COALESCE(fp.sweetness, 5),
                    'bitterness', COALESCE(fp.bitterness, 5),
                    'strength', COALESCE(fp.strength, 5),
                    'smokiness', COALESCE(fp.smokiness, 5),
                    'fruitiness', COALESCE(fp.fruitiness, 5),
                    'spiciness', COALESCE(fp.spiciness, 5),
                    'tags', COALESCE(fp.tags, ARRAY[]::text[])
                ) AS flavor_profile
            FROM products p
            LEFT JOIN stock st ON p.id = st.product_id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('paid', 'shipped', 'delivered')
            LEFT JOIN feedback f ON p.id = f.product_id AND f.is_active = TRUE
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN flavor_profiles fp ON p.id = fp.product_id
            JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = :category_id
              AND p.is_active = TRUE
              AND p.deleted_at IS NULL
            GROUP BY p.id, s.name, fp.sweetness, fp.bitterness, fp.strength, 
                     fp.smokiness, fp.fruitiness, fp.spiciness, fp.tags, c.name, c.slug
            ORDER BY units_sold DESC, avg_rating DESC NULLS LAST, p.name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function countProductsByCategoryId(int $categoryId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = :category_id
              AND p.is_active = TRUE 
              AND p.deleted_at IS NULL
              AND c.is_active = TRUE 
              AND c.deleted_at IS NULL
        ");
        $stmt->execute([':category_id' => $categoryId]);
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    protected function mapToModel(array $row): CategoryModel
    {
        return new CategoryModel(
            id: (int)$row['id'],
            name: $row['name'],
            slug: $row['slug'],
            description: $row['description'],
            image_url: $row['image_url'],
            is_active: (bool)$row['is_active'],
            created_at: $row['created_at'],
            updated_at: $row['updated_at'],
            deleted_at: $row['deleted_at']
        );
    }

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
