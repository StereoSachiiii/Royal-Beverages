<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\ProductService;

class ProductController extends BaseController
{
    public function __construct(
        private ProductService $service,
    ) {}

    public function create(array $data): array
    {
        return $this->handle(function () use ($data) {
            $product = $this->service->create($data);
            return $this->success('Product created', $product, 201);
        });
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        return $this->handle(function () use ($limit, $offset) {
            $data = $this->service->getAll($limit, $offset);
            return $this->success('Products retrieved', $data);
        });
    }

    public function getAllIncludingInactive(int $limit = 50, int $offset = 0): array
    {
        return $this->handle(function () use ($limit, $offset) {
            $data = $this->service->getAllIncludingInactive($limit, $offset);
            return $this->success('All products retrieved', $data);
        });
    }

    public function getById(int $id): array
    {
        return $this->handle(function () use ($id) {
            $product = $this->service->getById($id);
            return $this->success('Product retrieved', $product);
        });
    }

    public function getByIdAdmin(int $id): array
    {
        return $this->handle(function () use ($id) {
            $product = $this->service->getByIdAdmin($id);
            return $this->success('Product retrieved', $product);
        });
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        return $this->handle(function () use ($query, $limit, $offset) {
            $data = $this->service->search($query, $limit, $offset);
            return $this->success('Search results', $data);
        });
    }

    public function searchEnriched(string $query, int $limit = 50, int $offset = 0): array
    {
        return $this->handle(function () use ($query, $limit, $offset) {
            $data = $this->service->searchEnriched($query, $limit, $offset);
            return $this->success('Enriched search results', $data);
        });
    }

    public function count(): array
    {
        return $this->handle(function () {
            $count = $this->service->count(false);
            return $this->success('Count retrieved', $count);
        });
    }

    public function countAll(): array
    {
        return $this->handle(function () {
            $count = $this->service->count(true);
            return $this->success('Total count retrieved', $count);
        });
    }

    public function update(int $id, array $data): array
    {
        return $this->handle(function () use ($id, $data) {
            $updated = $this->service->update($id, $data);
            return $this->success('Product updated', $updated);
        });
    }

    public function delete(int $id): array
    {
        return $this->handle(function () use ($id) {
            $this->service->delete($id, false);
            return $this->success('Product deleted');
        });
    }

    public function hardDelete(int $id): array
    {
        return $this->handle(function () use ($id) {
            $this->service->delete($id, true);
            return $this->success('Product permanently deleted');
        });
    }

    public function restore(int $id): array
    {
        return $this->handle(function () use ($id) {
            $this->service->restore($id);
            return $this->success('Product restored');
        });
    }

    public function getAllEnriched(int $limit = 50, int $offset = 0): array
    {
        return $this->handle(function () use ($limit, $offset) {
            $data = $this->service->getAllEnriched($limit, $offset);
            return $this->success('Enriched products retrieved', $data);
        });
    }

    public function getByIdEnriched(int $id): array
    {
        return $this->handle(function () use ($id) {
            $product = $this->service->getByIdEnriched($id);
            return $this->success('Product retrieved', $product);
        });
    }

    public function getTopSellers(int $limit = 10): array
    {
        return $this->handle(function () use ($limit) {
            $data = $this->service->getTopSellers($limit);
            return $this->success('Top sellers retrieved', $data);
        });
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
        return $this->handle(function () use ($limit, $offset, $search, $categoryId, $minPrice, $maxPrice, $sort) {
            $data = $this->service->shopAllEnriched(
                $limit, $offset, $search, $categoryId, $minPrice, $maxPrice, $sort
            );
            return $this->success('Shop products retrieved', $data);
        });
    }
}
