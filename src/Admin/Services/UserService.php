<?php
declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Repositories\UserRepository;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\DuplicateException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;
use App\DTO\Requests\RegisterUserRequest;
use App\DTO\Requests\LoginRequest;
use App\DTO\Requests\UpdateUserRequest;
use App\DTO\DTOException;

class UserService
{
    public function __construct(
        private UserRepository $repo,
    ) {}

    public function register(array $data): array
    {
        try {
            $dto = RegisterUserRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        if ($this->repo->findByEmail($dto->email)) {
            throw new DuplicateException('Email already registered', ['email' => $dto->email]);
        }

        $passwordHash = password_hash($dto->password, PASSWORD_BCRYPT);

        $user = $dto->is_admin
            ? $this->repo->createAdmin($dto->name, $dto->email, $dto->phone, $passwordHash, $dto->profile_image_url)
            : $this->repo->create($dto->name, $dto->email, $dto->phone, $passwordHash, $dto->profile_image_url);

        return $user->toArray();
    }

    public function login(array $data): array
    {
        try {
            $dto = LoginRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        $user = $this->repo->findByEmail($dto->email);
        if (!$user || $user->getPasswordHash() === null || !password_verify($dto->password, $user->getPasswordHash())) {
            throw new ValidationException('Invalid credentials', [], 401);
        }

        if (!$user->isActive()) {
            throw new ValidationException('Account is disabled', [], 403);
        }

        $this->repo->updateLastLogin((int)$user->getId());

        return $user->toArray();
    }

    public function handleGoogleOAuth(array $googleProfile): array
    {
        $email = $googleProfile['email'] ?? null;
        if (!$email) {
            throw new ValidationException('Google profile missing email', [], 400);
        }

        $user = $this->repo->findByEmail($email);
        
        if ($user) {
            if (!$user->isActive()) {
                throw new ValidationException('Account is disabled', [], 403);
            }
            $this->repo->linkOAuthProvider((int)$user->getId(), 'google', $googleProfile['id']);
        } else {
            $user = $this->repo->createFromOAuth(
                $googleProfile['name'] ?? 'Google User',
                $email,
                'google',
                $googleProfile['id'],
                $googleProfile['picture'] ?? null
            );
        }

        $this->repo->updateLastLogin((int)$user->getId());
        return $user->toArray();
    }

    public function getProfile(int $userId): array
    {
        $user = $this->repo->findById($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }
        return $user->toArray();
    }

    public function updateProfile(int $userId, array $data): array
    {
        try {
            $dto = UpdateUserRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        if ($dto->email) {
            $existing = $this->repo->findByEmail($dto->email);
            if ($existing && $existing->getId() !== $userId) {
                throw new DuplicateException('Email already taken', ['email' => $dto->email]);
            }
        }

        // Build update payload — normalise image URL field name
        $updates = $dto->toChangeset();
        if (isset($updates['profileImageUrl'])) {
            $updates['profile_image_url'] = $updates['profileImageUrl'];
            unset($updates['profileImageUrl']);
        }

        $user = $this->repo->updateProfile($userId, $updates);

        return $user->toArray();
    }

    public function anonymizeUser(int $userId): void
    {
        $affected = $this->repo->anonymizeUser($userId);
        if ($affected === 0) {
            throw new NotFoundException('User not found');
        }
    }

    public function getAddresses(int $userId, ?string $type = null): array
    {
        return $this->repo->getUserAddresses($userId, $type);
    }

    public function createAddress(int $userId, array $data): int
    {
        $id = $this->repo->createAddress($userId, $data);
        if ($id <= 0) {
            throw new DatabaseException('Failed to create address');
        }
        return $id;
    }

    public function updateAddress(int $addressId, array $data): void
    {
        $affected = $this->repo->updateAddress($addressId, $data);
        if ($affected === 0) {
            throw new NotFoundException('Address not found');
        }
    }

    public function deleteAddress(int $addressId): void
    {
        $affected = $this->repo->softDeleteAddress($addressId);
        if ($affected === 0) {
            throw new NotFoundException('Address not found');
        }
    }

    public function getAllUsers(int $limit = 50, int $offset = 0): array
    {
        $users = $this->repo->getAllUsers($limit, $offset);
        return array_map(fn($u) => $u->toArray(), $users);
    }

    public function searchUsers(string $query, int $limit = 50, int $offset = 0): array
    {
        if (empty(trim($query))) {
            $users = $this->repo->getAllUsers($limit, $offset);
        } else {
            $users = $this->repo->searchUsers(trim($query), $limit, $offset);
        }
        return array_map(fn($u) => $u->toArray(), $users);
    }

    public function getUserById(int $id): array
    {
        $user = $this->repo->findById($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }
        return $user->toArray();
    }

    public function getByIdEnriched(int $id): array
    {
        $user = $this->repo->getByIdEnriched($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }
        return $user;
    }

    public function getAllPaginated(int $limit = 50, int $offset = 0): array
    {
        return $this->repo->getAllPaginated($limit, $offset);
    }

    public function count(): int
    {
        return $this->repo->count();
    }

    public function softDelete(int $id): void
    {
        $this->repo->softDelete($id);
    }

    public function hardDelete(int $id): void
    {
        $this->repo->hardDelete($id);
    }
}
