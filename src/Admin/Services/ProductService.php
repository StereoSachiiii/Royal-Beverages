<?php
declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Repositories\ProductRepository;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;
use App\Admin\Exceptions\DuplicateException;

use App\DTO\Requests\CreateProductRequest;
use App\DTO\Requests\UpdateProductRequest;
use App\DTO\DTOException;

class ProductService
{
    public function __construct(
        private ProductRepository $repo,
    ) {}

    /**
     * Create a product from a raw request body array.
     * Validates via CreateProductRequest (replaces ProductValidator::validateCreate).
     */
    public function create(array $data): array
    {
        try {
            $dto = CreateProductRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        if ($this->repo->getByName($dto->name)) {
            throw new DuplicateException('Product name already exists', ['name' => $dto->name]);
        }

        if ($dto->slug && $this->repo->getBySlug($dto->slug)) {
            throw new DuplicateException('Product slug already exists', ['slug' => $dto->slug]);
        }

        $product = $this->repo->create($dto->toArray());
        return $product->toArray();
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $products = $this->repo->getAll($limit, $offset);
        return array_map(fn($p) => $p->toArray(), $products);
    }

    public function getAllIncludingInactive(int $limit = 50, int $offset = 0): array
    {
        $products = $this->repo->getAllIncludingInactive($limit, $offset);
        return array_map(fn($p) => $p->toArray(), $products);
    }

    public function getById(int $id): array
    {
        $product = $this->repo->getById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }
        return $product->toArray();
    }

    public function getByIdAdmin(int $id): array
    {
        $product = $this->repo->getByIdAdmin($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }
        return $product->toArray();
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        if (empty(trim($query))) {
            $products = $this->repo->getAll($limit, $offset);
        } else {
            $products = $this->repo->search($query, $limit, $offset);
        }
        return array_map(fn($p) => $p->toArray(), $products);
    }

    public function count(bool $includeInactive = false): int
    {
        return $includeInactive ? $this->repo->countAll() : $this->repo->count();
    }

    /**
     * Update a product from a raw request body array.
     * Validates via UpdateProductRequest (replaces ProductValidator::validateUpdate).
     */
    public function update(int $id, array $data): array
    {
        try {
            $dto = UpdateProductRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        $existing = $this->repo->getByIdAdmin($id);
        if (!$existing) {
            throw new NotFoundException('Product not found');
        }

        $updated = $this->repo->update($id, $dto->toChangeset());
        if (!$updated) {
            throw new DatabaseException('Update failed');
        }

        return $updated->toArray();
    }

    public function delete(int $id, bool $hard = false): void
    {
        if (!$this->repo->getByIdAdmin($id)) {
            throw new NotFoundException('Product not found');
        }

        $ok = $hard ? $this->repo->hardDelete($id) : $this->repo->delete($id);
        if (!$ok) {
            throw new DatabaseException($hard ? 'Hard delete failed' : 'Delete failed');
        }
    }

    public function restore(int $id): void
    {
        if (!$this->repo->getByIdAdmin($id)) {
            throw new NotFoundException('Product not found');
        }

        if (!$this->repo->restore($id)) {
            throw new DatabaseException('Restore failed');
        }
    }

    public function getAllEnriched(int $limit = 50, int $offset = 0): array
    {
        return $this->repo->getAllEnriched($limit, $offset);
    }

    public function getByIdEnriched(int $id): array
    {
        $product = $this->repo->getByIdEnriched($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }
        return $product;
    }

    public function getTopSellers(int $limit = 10): array
    {
        return $this->repo->getTopSellers($limit);
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
        return $this->repo->shopAllEnriched($limit, $offset, $search, $categoryId, $minPrice, $maxPrice, $sort);
    }

    public function searchEnriched(string $query, int $limit = 50, int $offset = 0): array
    {
        return $this->repo->searchEnriched($query, $limit, $offset);
    }
}
