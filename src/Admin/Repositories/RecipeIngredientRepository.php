<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Admin\Models\RecipeIngredientModel;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;
use Exception;

class RecipeIngredientRepository extends BaseRepository
{
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT ri.*, 
                       p.name as product_name, 
                       p.price_cents as product_price_cents,
                       p.image_url as product_image_url,
                       r.name as recipe_name
                FROM recipe_ingredients ri
                LEFT JOIN products p ON ri.product_id = p.id
                LEFT JOIN cocktail_recipes r ON ri.recipe_id = r.id
                ORDER BY ri.id DESC 
                LIMIT :limit OFFSET :offset";
        $rows = $this->fetchAll($sql, [
            ':limit' => $limit,
            ':offset' => $offset
        ]);
        return $this->mapToModels($rows);
    }

    public function searchByProduct(string $query, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT ri.*, 
                       p.name as product_name, 
                       p.price_cents as product_price_cents,
                       TO_CHAR(p.price_cents / 100.0, 'FM999990.00') as product_price,
                       p.image_url as product_image_url,
                       r.name as recipe_name
                FROM recipe_ingredients ri
                LEFT JOIN products p ON ri.product_id = p.id
                LEFT JOIN cocktail_recipes r ON ri.recipe_id = r.id
                WHERE p.name ILIKE :query OR r.name ILIKE :query
                ORDER BY ri.id DESC 
                LIMIT :limit OFFSET :offset";
        $rows = $this->fetchAll($sql, [
            ':query' => '%' . $query . '%',
            ':limit' => $limit,
            ':offset' => $offset
        ]);
        return $this->mapToModels($rows);
    }

    public function getById(int $id): ?RecipeIngredientModel
    {
        $sql = "SELECT ri.*, 
                       p.name as product_name, 
                       p.price_cents as product_price_cents,
                       p.image_url as product_image_url,
                       r.name as recipe_name
                FROM recipe_ingredients ri
                LEFT JOIN products p ON ri.product_id = p.id
                LEFT JOIN cocktail_recipes r ON ri.recipe_id = r.id
                WHERE ri.id = :id";
        $row = $this->fetchOne($sql, [':id' => $id]);
        return $row ? $this->mapToModel($row) : null;
    }

    public function getByRecipe(int $recipeId): array
    {
        $sql = "SELECT ri.*, 
                       p.name as product_name, 
                       p.price_cents as product_price_cents,
                       p.image_url as product_image_url
                FROM recipe_ingredients ri
                LEFT JOIN products p ON ri.product_id = p.id
                WHERE ri.recipe_id = :recipe_id 
                ORDER BY ri.id ASC";
        $rows = $this->fetchAll($sql, [':recipe_id' => $recipeId]);
        return $this->mapToModels($rows);
    }

    public function count(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM recipe_ingredients");
    }

    public function create(array $data): RecipeIngredientModel
    {
        $sql = "INSERT INTO recipe_ingredients (recipe_id, product_id, quantity, unit, is_optional) 
                 VALUES (:recipe_id, :product_id, :quantity, :unit, :is_optional) 
                 RETURNING *";
        
        $stmt = $this->executeStatement($sql, [
            ':recipe_id' => $data['recipe_id'],
            ':product_id' => $data['product_id'],
            ':quantity' => $data['quantity'],
            ':unit' => $data['unit'] ?? 'oz',
            ':is_optional' => isset($data['is_optional']) ? ($data['is_optional'] ? 't' : 'f') : 'f'
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new DatabaseException('Failed to create recipe ingredient');
        }
        return $this->mapToModel($row);
    }

    public function update(int $id, array $data): ?RecipeIngredientModel
    {
        $sets = [];
        $params = [':id' => $id];

        if (isset($data['product_id'])) {
            $sets[] = "product_id = :product_id";
            $params[':product_id'] = $data['product_id'];
        }
        if (isset($data['quantity'])) {
            $sets[] = "quantity = :quantity";
            $params[':quantity'] = $data['quantity'];
        }
        if (isset($data['unit'])) {
            $sets[] = "unit = :unit";
            $params[':unit'] = $data['unit'];
        }
        if (isset($data['is_optional'])) {
            $sets[] = "is_optional = :is_optional";
            $params[':is_optional'] = $data['is_optional'] ? 't' : 'f';
        }

        if (empty($sets)) {
            return $this->getById($id);
        }

        $sql = "UPDATE recipe_ingredients SET " . implode(', ', $sets) . " 
                WHERE id = :id RETURNING *";
        
        $stmt = $this->executeStatement($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM recipe_ingredients WHERE id = :id";
        $stmt = $this->executeStatement($sql, [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function bulkCreate(array $ingredients): array
    {
        $this->beginTransaction();
        try {
            $results = [];
            foreach ($ingredients as $ingredient) {
                $results[] = $this->create($ingredient);
            }
            $this->commit();
            return $results;
        } catch (Exception $e) {
            $this->rollBack();
            throw new DatabaseException('Bulk create failed: ' . $e->getMessage());
        }
    }

    public function deleteByRecipe(int $recipeId): void
    {
        $sql = "DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id";
        $this->executeStatement($sql, [':recipe_id' => $recipeId]);
    }

    protected function mapToModel(array $row): RecipeIngredientModel
    {
        return new RecipeIngredientModel(
            id: (int)$row['id'],
            recipeId: (int)$row['recipe_id'],
            productId: (int)$row['product_id'],
            quantity: (float)$row['quantity'],
            unit: $row['unit'] ?? 'oz',
            isOptional: ($row['is_optional'] ?? false) === true || ($row['is_optional'] ?? '') === 't',
            createdAt: $row['created_at'] ?? date('Y-m-d H:i:s'),
            productName: $row['product_name'] ?? null,
            productPriceCents: isset($row['product_price_cents']) ? (int)$row['product_price_cents'] : null,
            productImageUrl: $row['product_image_url'] ?? null,
            recipeName: $row['recipe_name'] ?? null
        );
    }

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
