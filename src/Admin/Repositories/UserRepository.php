<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Core\Database;
use App\Admin\Models\UserModel;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

class UserRepository extends BaseRepository
{


    public function create(string $name, string $email, ?string $phone, string $passwordHash, ?string $profileImageUrl = null): UserModel
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, phone, password_hash, profile_image_url)
            VALUES (:name, :email, :phone, :hash, :profile_image_url)
            RETURNING *
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':hash' => $passwordHash,
            ':profile_image_url' => $profileImageUrl
        ]);

        $row = $stmt->fetch();
        if (!$row) throw new DatabaseException('Failed to create user');
        return $this->mapToModel($row);
    }

    public function createAdmin(string $name, string $email, ?string $phone, string $passwordHash, ?string $profileImageUrl = null): UserModel
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, phone, password_hash, profile_image_url, is_admin)
            VALUES (:name, :email, :phone, :hash, :profile_image_url, true)
            RETURNING *
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':hash' => $passwordHash,
            ':profile_image_url' => $profileImageUrl
        ]);

        $row = $stmt->fetch();
        if (!$row) throw new DatabaseException('Failed to create admin');
        return $this->mapToModel($row);
    }

    public function createFromOAuth(string $name, string $email, string $provider, string $providerId, ?string $profileImageUrl): UserModel
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, password_hash, oauth_provider, oauth_provider_id, profile_image_url)
            VALUES (:name, :email, '', :provider, :provider_id, :profile_image_url)
            RETURNING *
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':provider' => $provider,
            ':provider_id' => $providerId,
            ':profile_image_url' => $profileImageUrl
        ]);

        $row = $stmt->fetch();
        if (!$row) throw new DatabaseException('Failed to create user via OAuth');
        return $this->mapToModel($row);
    }

    public function linkOAuthProvider(int $userId, string $provider, string $providerId): void
    {
        $this->pdo->prepare("
            UPDATE users SET oauth_provider = :provider, oauth_provider_id = :provider_id, updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL AND oauth_provider IS NULL
        ")->execute([
            ':id' => $userId,
            ':provider' => $provider,
            ':provider_id' => $providerId
        ]);
    }

    public function findByEmail(string $email): ?UserModel
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE email = :email AND deleted_at IS NULL AND is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ? $this->mapToModel($row) : null;
    }

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users WHERE id = :id AND deleted_at IS NULL AND is_anonymized = FALSE
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->mapToModel($row) : null;
    }

    public function updateLastLogin(int $userId): void
    {
        $this->pdo->prepare("
            UPDATE users SET last_login_at = NOW() 
            WHERE id = :id AND deleted_at IS NULL
        ")->execute([':id' => $userId]);
    }

    public function anonymizeUser(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET name = 'Anonymized User',
                email = CONCAT('deleted_', id, '@deleted.local'),
                phone = NULL,
                password_hash = '',
                profile_image_url = NULL,
                is_anonymized = TRUE,
                anonymized_at = NOW(),
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $userId]);
        return $stmt->rowCount();
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateProfile(int $userId, array $data): UserModel
    {
        $sets = ['updated_at = NOW()'];
        $params = [':id' => $userId];

        foreach (['name', 'email', 'phone', 'profile_image_url', 'is_active', 'is_admin'] as $col) {
            if (isset($data[$col])) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (isset($data['password'])) {
            $sets[] = "password_hash = :hash";
            $params[':hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :id AND deleted_at IS NULL RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch();
        if (!$row) throw new NotFoundException('User not found');
        return $this->mapToModel($row);
    }

    public function getUserAddresses(int $userId, ?string $addressType = null): array
    {
        $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id AND deleted_at IS NULL";
        $params = [':user_id' => $userId];

        if ($addressType) {
            $sql .= " AND address_type = :type";
            $params[':type'] = $addressType;
        }

        $sql .= " ORDER BY is_default DESC, created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Search users by name, email, or phone
     * @param string $query Search query
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchUsers(string $query, int $limit = 50, int $offset = 0): array
    {
        $searchTerm = "%{$query}%";
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE deleted_at IS NULL 
            AND (
                name ILIKE :query 
                OR email ILIKE :query 
                OR phone ILIKE :query
            )
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':query', $searchTerm);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $users = [];
        while ($row = $stmt->fetch()) {
            $users[] = $this->mapToModel($row);
        }
        return $users;
    }
    public function createAddress(int $userId, array $data): int
    {
        $sql = "
            INSERT INTO user_addresses 
            (user_id, address_type, recipient_name, phone, address_line1, address_line2, city, state, postal_code, country, is_default)
            VALUES 
            (:user_id, :type, :recipient, :phone, :line1, :line2, :city, :state, :postal, :country, :default)
            RETURNING id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $data['address_type'] ?? 'both',
            ':recipient' => $data['recipient_name'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':line1' => $data['address_line1'],
            ':line2' => $data['address_line2'] ?? null,
            ':city' => $data['city'],
            ':state' => $data['state'] ?? null,
            ':postal' => $data['postal_code'],
            ':country' => $data['country'] ?? 'Sri Lanka',
            ':default' => $data['is_default'] ?? false
        ]);

        $row = $stmt->fetch();
        return $row['id'] ?? 0;
    }

    public function updateAddress(int $addressId, array $data): int
    {
        $sets = [];
        $params = [':id' => $addressId];

        $fields = [
            'address_type', 'recipient_name', 'phone', 'address_line1', 'address_line2',
            'city', 'state', 'postal_code', 'country', 'is_default'
        ];

        foreach ($fields as $field) {
            $key = match($field) {
                'is_default' => 'is_default',
                default => $field
            };
            if (isset($data[$key])) {
                $col = $field;
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$key];
            }
        }

        if (empty($sets)) return 0;

        $sql = "UPDATE user_addresses SET " . implode(', ', $sets) . ", updated_at = NOW() 
                WHERE id = :id AND deleted_at IS NULL RETURNING id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function softDeleteAddress(int $addressId): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE user_addresses SET deleted_at = NOW(), updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $addressId]);
        return $stmt->rowCount();
    }

    public function getAllPaginated(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE deleted_at IS NULL 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Alias for getAllPaginated - used by UserService and UserController
     * Returns UserModel objects for consistency with other methods
     */
    public function getAllUsers(int $limit = 50, int $offset = 0): array
    {
        $rows = $this->getAllPaginated($limit, $offset);
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }



    public function getByIdEnriched(int $id): ?array
    {
        // 1. Basic User Data
        $stmt = $this->pdo->prepare("
            SELECT * FROM users WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return null;

        // 2. Aggregate Stats (Lifetime Value, Order Counts)
        $stmtStats = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_cents), 0) as lifetime_value_cents,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                COUNT(CASE WHEN status IN ('pending', 'paid', 'processing', 'shipped') THEN 1 END) as pending_orders
            FROM orders 
            WHERE user_id = :id
        ");
        $stmtStats->execute([':id' => $id]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        // Calculate Average Order Value
        $avgOrderValue = $stats['total_orders'] > 0 
            ? round($stats['lifetime_value_cents'] / $stats['total_orders']) 
            : 0;

        // 3. Active Carts Count
        $stmtCarts = $this->pdo->prepare("
            SELECT COUNT(*) FROM carts WHERE user_id = :id AND status = 'active'
        ");
        $stmtCarts->execute([':id' => $id]);
        $activeCarts = $stmtCarts->fetchColumn();

        // 4. Address Count
        $stmtAddr = $this->pdo->prepare("
             SELECT COUNT(*) FROM user_addresses WHERE user_id = :id AND deleted_at IS NULL
        ");
        $stmtAddr->execute([':id' => $id]);
        $addressCount = $stmtAddr->fetchColumn();

        // 5. Recent Orders
        $stmtRecent = $this->pdo->prepare("
            SELECT id, order_number, total_cents, status, created_at 
            FROM orders 
            WHERE user_id = :id 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmtRecent->execute([':id' => $id]);
        $recentOrders = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

        // Merge all into one array
        return array_merge($user, $stats, [
            'avg_order_value_cents' => $avgOrderValue,
            'active_carts' => $activeCarts,
            'address_count' => $addressCount,
            'recent_orders' => $recentOrders
        ]);
    }

    protected function mapToModel(array $row): UserModel
    {
        return new UserModel(
            id: (int)$row['id'],
            name: $row['name'],
            email: $row['email'],
            phone: $row['phone'],
            passwordHash: $row['password_hash'],
            profileImageUrl: $row['profile_image_url'],
            isActive: (bool)$row['is_active'],
            isAdmin: (bool)$row['is_admin'],
            isAnonymized: (bool)$row['is_anonymized'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
            deletedAt: $row['deleted_at'],
            anonymizedAt: $row['anonymized_at'],
            lastLoginAt: $row['last_login_at']
        );
    }
    protected function mapToModels(array $rows): array
    {
        return array_map(fn($row) => $this->mapToModel($row), $rows);
    }

    public function count(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
    }
}
