<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;

use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

class UserPreferenceRepository extends BaseRepository
{
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM user_preferences 
                 ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $rows = $this->fetchAll($sql, [
            ':limit' => $limit,
            ':offset' => $offset
        ]);
        return $this->toArrays($rows);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM user_preferences WHERE id = :id";
        $row = $this->fetchOne($sql, [':id' => $id]);
        return $row ? $this->toArray($row) : null;
    }

    /**
     * Get all user preferences with pagination and enriched user data
     */
    public function getAllPaginated(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
                    up.id,
                    up.user_id,
                    up.preferred_sweetness,
                    up.preferred_bitterness,
                    up.preferred_strength,
                    up.preferred_smokiness,
                    up.preferred_fruitiness,
                    up.preferred_spiciness,
                    up.favorite_categories,
                    up.created_at,
                    u.name as user_name,
                    u.email as user_email
                FROM user_preferences up
                LEFT JOIN users u ON up.user_id = u.id
                ORDER BY up.id DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Get user preference by ID with enriched data (user details)
     */
    public function getByIdEnriched(int $id): ?array
    {
        $sql = "SELECT 
                    up.id,
                    up.user_id,
                    up.preferred_sweetness,
                    up.preferred_bitterness,
                    up.preferred_strength,
                    up.preferred_smokiness,
                    up.preferred_fruitiness,
                    up.preferred_spiciness,
                    up.favorite_categories,
                    up.created_at,
                    u.name as user_name,
                    u.email as user_email,
                    u.created_at as user_created_at
                FROM user_preferences up
                LEFT JOIN users u ON up.user_id = u.id
                WHERE up.id = :id
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    public function getByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM user_preferences 
                 WHERE user_id = :user_id";
        $row = $this->fetchOne($sql, [':user_id' => $userId]);
        return $row ? $this->toArray($row) : null;
    }

    public function existsByUserId(int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM user_preferences 
                 WHERE user_id = :user_id";
        return (int)$this->fetchColumn($sql, [':user_id' => $userId]) > 0;
    }

    public function count(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM user_preferences");
    }

    public function create(array $data): array
    {
        $sql = "INSERT INTO user_preferences (
                    user_id, 
                    preferred_sweetness, 
                    preferred_bitterness, 
                    preferred_strength, 
                    preferred_smokiness, 
                    preferred_fruitiness, 
                    preferred_spiciness, 
                    favorite_categories
                ) VALUES (
                    :user_id, 
                    :preferred_sweetness, 
                    :preferred_bitterness, 
                    :preferred_strength, 
                    :preferred_smokiness, 
                    :preferred_fruitiness, 
                    :preferred_spiciness, 
                    :favorite_categories
                ) RETURNING *";
        
        // Convert favorite_categories array to PostgreSQL array format
        $favCats = isset($data['favorite_categories']) && is_array($data['favorite_categories'])
            ? '{' . implode(',', $data['favorite_categories']) . '}'
            : null;
        
        $stmt = $this->executeStatement($sql, [
            ':user_id' => $data['user_id'],
            ':preferred_sweetness' => $data['preferred_sweetness'] ?? 5,
            ':preferred_bitterness' => $data['preferred_bitterness'] ?? 5,
            ':preferred_strength' => $data['preferred_strength'] ?? 5,
            ':preferred_smokiness' => $data['preferred_smokiness'] ?? 5,
            ':preferred_fruitiness' => $data['preferred_fruitiness'] ?? 5,
            ':preferred_spiciness' => $data['preferred_spiciness'] ?? 5,
            ':favorite_categories' => $favCats
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new DatabaseException('Failed to create user preference');
        }
        return $this->toArray($row);
    }

    public function update(int $id, array $data): ?array
    {
        $sets = ['updated_at = NOW()'];
        $params = [':id' => $id];

        $fields = [
            'preferred_sweetness', 'preferred_bitterness', 'preferred_strength',
            'preferred_smokiness', 'preferred_fruitiness', 'preferred_spiciness'
        ];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $sets[] = "$field = :$field";
                $params[":$field"] = (int)$data[$field];
            }
        }
        
        // Handle favorite_categories array
        if (isset($data['favorite_categories'])) {
            $sets[] = "favorite_categories = :favorite_categories";
            $params[':favorite_categories'] = is_array($data['favorite_categories'])
                ? '{' . implode(',', $data['favorite_categories']) . '}'
                : null;
        }

        $sql = "UPDATE user_preferences SET " . implode(', ', $sets) . " 
                WHERE id = :id RETURNING *";
        
        $stmt = $this->executeStatement($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->toArray($row) : null;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM user_preferences WHERE id = :id";
        $stmt = $this->executeStatement($sql, [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function toArray(array $row): array
    {
        // Return as array since the UserPreferenceModel class is an old Active Record pattern
        // The table has individual preference columns, not a JSON preferences field
        return [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'preferred_sweetness' => $row['preferred_sweetness'] ?? null,
            'preferred_bitterness' => $row['preferred_bitterness'] ?? null,
            'preferred_strength' => $row['preferred_strength'] ?? null,
            'preferred_smokiness' => $row['preferred_smokiness'] ?? null,
            'preferred_fruitiness' => $row['preferred_fruitiness'] ?? null,
            'preferred_spiciness' => $row['preferred_spiciness'] ?? null,
            'favorite_categories' => $row['favorite_categories'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function toArrays(array $rows): array
    {
        return array_map(fn($row) => $this->toArray($row), $rows);
    }

    // Required by BaseRepository - returns stdClass wrapper for compatibility
    protected function mapToModel(array $row): object
    {
        return (object)$this->toArray($row);
    }

    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }
}
