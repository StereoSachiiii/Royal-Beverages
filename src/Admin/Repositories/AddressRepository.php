<?php
declare(strict_types=1);

namespace App\Admin\Repositories;

use PDO;
use App\Admin\Models\AddressModel;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

class AddressRepository extends BaseRepository
{
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM user_addresses 
                 WHERE deleted_at IS NULL 
                 ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $rows = $this->fetchAll($sql, [
            ':limit' => $limit,
            ':offset' => $offset
        ]);
        return $this->mapToModels($rows);
    }

    public function getById(int $id): ?AddressModel
    {
        $sql = "SELECT * FROM user_addresses WHERE id = :id AND deleted_at IS NULL";
        $row = $this->fetchOne($sql, [':id' => $id]);
        return $row ? $this->mapToModel($row) : null;
    }

    /**
     * Get all addresses with pagination and enriched user data
     */
    public function getAllPaginated(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
                    ua.id,
                    ua.user_id,
                    ua.address_type,
                    ua.recipient_name,
                    ua.phone,
                    ua.address_line1,
                    ua.address_line2,
                    ua.city,
                    ua.state,
                    ua.postal_code,
                    ua.country,
                    ua.is_default,
                    ua.created_at,
                    ua.updated_at,
                    u.name as user_name,
                    u.email as user_email
                FROM user_addresses ua
                LEFT JOIN users u ON ua.user_id = u.id
                WHERE ua.deleted_at IS NULL
                ORDER BY ua.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Get address by ID with enriched data (user details, usage stats)
     */
    public function getByIdEnriched(int $id): ?array
    {
        $sql = "SELECT 
                    ua.id,
                    ua.user_id,
                    ua.address_type,
                    ua.recipient_name,
                    ua.phone,
                    ua.address_line1,
                    ua.address_line2,
                    ua.city,
                    ua.state,
                    ua.postal_code,
                    ua.country,
                    ua.is_default,
                    ua.created_at,
                    ua.updated_at,
                    u.name as user_name,
                    u.email as user_email,
                    (SELECT COUNT(*) FROM orders WHERE shipping_address_id = ua.id) as used_as_shipping,
                    (SELECT COUNT(*) FROM orders WHERE billing_address_id = ua.id) as used_as_billing
                FROM user_addresses ua
                LEFT JOIN users u ON ua.user_id = u.id
                WHERE ua.id = :id AND ua.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    public function getUserAddresses(int $userId, ?string $addressType = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM user_addresses 
                 WHERE user_id = :user_id AND deleted_at IS NULL";
        $params = [':user_id' => $userId];

        if ($addressType) {
            $sql .= " AND address_type = :type";
            $params[':type'] = $addressType;
        }

        $sql .= " ORDER BY is_default DESC, created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $rows = $this->fetchAll($sql, $params);
        return $this->mapToModels($rows);
    }

    public function getUserAddressesAll(int $userId): array
    {
        $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id AND deleted_at IS NULL";
        $rows = $this->fetchAll($sql, [':user_id' => $userId]);
        return $this->mapToModels($rows);
    }

    public function getByUser(int $userId): array
    {
        $sql = "SELECT * FROM user_addresses 
                 WHERE user_id = :user_id AND deleted_at IS NULL 
                 ORDER BY is_default DESC, created_at DESC";
        $rows = $this->fetchAll($sql, [':user_id' => $userId]);
        return $this->mapToModels($rows);
    }

    public function count(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM user_addresses WHERE deleted_at IS NULL");
    }

    public function create(array $data): AddressModel
    {
        $sql = "INSERT INTO user_addresses (user_id, address_type, recipient_name, phone, address_line1, address_line2, 
                  city, state, postal_code, country, is_default) 
                 VALUES (:user_id, :address_type, :recipient_name, :phone, :address_line1, :address_line2, 
                  :city, :state, :postal_code, :country, :is_default) 
                 RETURNING *";
        
        $stmt = $this->executeStatement($sql, [
            ':user_id' => $data['user_id'],
            ':address_type' => $data['address_type'],
            ':recipient_name' => $data['recipient_name'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':address_line1' => $data['address_line1'],
            ':address_line2' => $data['address_line2'] ?? null,
            ':city' => $data['city'],
            ':state' => $data['state'] ?? null,
            ':postal_code' => $data['postal_code'],
            ':country' => $data['country'] ?? 'Sri Lanka',
            ':is_default' => (isset($data['is_default']) && filter_var($data['is_default'], FILTER_VALIDATE_BOOLEAN)) ? 'true' : 'false'
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new DatabaseException('Failed to create address');
        }
        return $this->mapToModel($row);
    }

    public function update(int $id, array $data): ?AddressModel
    {
        $sets = [];
        $params = [':id' => $id];

        foreach (['address_type', 'recipient_name', 'phone', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }
        
        if (array_key_exists('is_default', $data)) {
            $sets[] = "is_default = :is_default";
            $params[":is_default"] = filter_var($data['is_default'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        if (empty($sets)) {
            return null;
        }

        $sql = "UPDATE user_addresses SET " . implode(', ', $sets) . " 
                WHERE id = :id AND deleted_at IS NULL RETURNING *";
        
        $stmt = $this->executeStatement($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapToModel($row) : null;
    }

    public function delete(int $id): bool
    {
        $sql = "UPDATE user_addresses SET deleted_at = NOW() 
                 WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->executeStatement($sql, [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    protected function mapToModel(array $row): AddressModel
    {
        return new AddressModel(
            id: (int)$row['id'],
            user_id: (int)$row['user_id'],
            address_type: $row['address_type'],
            recipient_name: $row['recipient_name'] ?? null,
            phone: $row['phone'] ?? null,
            address_line1: $row['address_line1'],
            address_line2: $row['address_line2'],
            city: $row['city'],
            state: $row['state'],
            postal_code: $row['postal_code'],
            country: $row['country'],
            is_default: (bool)$row['is_default'],
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
