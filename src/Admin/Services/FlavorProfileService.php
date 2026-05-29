<?php
declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Repositories\FlavorProfileRepository;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\DuplicateException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

use App\DTO\Requests\CreateFlavorProfileRequest;
use App\DTO\Requests\UpdateFlavorProfileRequest;
use App\DTO\DTOException;

class FlavorProfileService
{
    public function __construct(
        private FlavorProfileRepository $repo,
    ) {}

    public function create(array $data): array
    {
        try {
            $dto = CreateFlavorProfileRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }
        
        if ($this->repo->exists($dto->product_id)) {
            throw new DuplicateException('Flavor profile already exists for this product');
        }

        $profile = $this->repo->create($dto->toArray());
        return $profile->toArray();
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        return $this->repo->getAll($limit, $offset);
    }

    public function getByProductId(int $productId): array
    {
        $profile = $this->repo->getByProductIdEnriched($productId);
        if (!$profile) {
            throw new NotFoundException('Flavor profile not found');
        }
        return $profile;
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        return $this->repo->search($query, $limit, $offset);
    }

    public function count(): int
    {
        return $this->repo->count();
    }

    public function update(int $productId, array $data): array
    {
        try {
            $dto = UpdateFlavorProfileRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }
        
        $updated = $this->repo->update($productId, $dto->toChangeset());
        if (!$updated) {
            throw new NotFoundException('Flavor profile not found');
        }
        return $updated->toArray();
    }

    public function delete(int $productId): void
    {
        $deleted = $this->repo->delete($productId);
        if (!$deleted) {
            throw new NotFoundException('Flavor profile not found');
        }
    }
}
