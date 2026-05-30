<?php
declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Repositories\StockRepository;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

use App\DTO\Requests\CreateStockRequest;
use App\DTO\Requests\UpdateStockRequest;
use App\DTO\DTOException;

class StockService
{
    public function __construct(
        private StockRepository $repo,
    ) {}

    // ORDER-RELATED
    public function reserveStock(int $orderId): void
    {
        $this->repo->reserveStock($orderId);
    }

    public function confirmPayment(int $orderId): void
    {
        $this->repo->confirmPayment($orderId);
    }

    public function cancelOrder(int $orderId): void
    {
        $this->repo->cancelOrder($orderId);
    }

    public function refundOrder(int $orderId): void
    {
        $this->repo->refundOrder($orderId);
    }

    // WAREHOUSE OPERATIONS
    public function adjustStock(int $productId, int $warehouseId, int $adjustment, ?string $reason = null): array
    {
        $stock = $this->repo->getByProductAndWarehouse($productId, $warehouseId);
        if (!$stock) {
            throw new NotFoundException('Stock not found');
        }

        $newQuantity = $stock->getQuantity() + $adjustment;
        if ($newQuantity < 0) {
            throw new ValidationException('Quantity cannot be negative');
        }
        if ($newQuantity < $stock->getReserved()) {
            throw new ValidationException('Cannot reduce quantity below reserved amount');
        }

        $updated = $this->repo->update((int)$stock->getId(), ['quantity' => $newQuantity]);
        if (!$updated) {
            throw new DatabaseException('Failed to adjust stock');
        }

        return $updated->toArray();
    }

    public function transferStock(int $productId, int $fromWarehouseId, int $toWarehouseId, int $quantity, ?string $reason = null): array
    {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new ValidationException('Cannot transfer to the same warehouse');
        }

        $fromStock = $this->repo->getByProductAndWarehouse($productId, $fromWarehouseId);
        if (!$fromStock) {
            throw new NotFoundException('Source stock not found');
        }

        $available = $fromStock->getQuantity() - $fromStock->getReserved();
        if ($quantity > $available) {
            throw new ValidationException("Insufficient available stock. Available: {$available}, Requested: {$quantity}");
        }

        // Reduce from source
        $this->repo->update((int)$fromStock->getId(), [
            'quantity' => $fromStock->getQuantity() - $quantity,
        ]);

        // Add to destination (create if doesn't exist)
        $toStock = $this->repo->getByProductAndWarehouse($productId, $toWarehouseId);
        if ($toStock) {
            $this->repo->update((int)$toStock->getId(), [
                'quantity' => $toStock->getQuantity() + $quantity,
            ]);
        } else {
            $this->repo->create([
                'product_id' => $productId,
                'warehouse_id' => $toWarehouseId,
                'quantity' => $quantity,
                'reserved' => 0,
            ]);
        }

        return [
            'product_id' => $productId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'quantity' => $quantity,
            'reason' => $reason,
        ];
    }

    // FRONTEND HELPERS
    public function getAvailableStock(int $productId): array
    {
        $available = $this->repo->getAvailableStockByProduct($productId);
        return [
            'product_id' => $productId,
            'available' => $available,
            'in_stock' => $available > 0,
        ];
    }

    public function getStockSummary(int $productId): array
    {
        $stocks = $this->repo->getByProduct($productId);

        $total = 0;
        $reserved = 0;
        $byWarehouse = [];

        foreach ($stocks as $stock) {
            $total += $stock->getQuantity();
            $reserved += $stock->getReserved();
            $byWarehouse[] = [
                'warehouse_id' => $stock->getWarehouseId(),
                'quantity' => $stock->getQuantity(),
                'reserved' => $stock->getReserved(),
                'available' => $stock->getQuantity() - $stock->getReserved(),
            ];
        }

        return [
            'product_id' => $productId,
            'total_quantity' => $total,
            'total_reserved' => $reserved,
            'total_available' => $total - $reserved,
            'warehouses' => $byWarehouse,
            'low_stock_warning' => ($total - $reserved) < 50,
        ];
    }

    // CRUD
    public function create(array $data): array
    {
        try {
            $dto = CreateStockRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        if ($this->repo->getByProductAndWarehouse($dto->product_id, $dto->warehouse_id)) {
            throw new ValidationException('Stock entry already exists for this product and warehouse');
        }

        $stock = $this->repo->create($dto->toArray());
        return $stock->toArray();
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stocks = $this->repo->getAll($limit, $offset);
        return array_map(fn($s) => $s->toArray(), $stocks);
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $stocks = $this->repo->search($query, $limit, $offset);
        return array_map(fn($s) => $s->toArray(), $stocks);
    }

    public function getById(int $id): array
    {
        $stock = $this->repo->getById($id);
        if (!$stock) {
            throw new NotFoundException('Stock not found');
        }
        return $stock->toArray();
    }

    /**
     * Get enriched stock data with product and warehouse names
     */
    public function getByIdEnriched(int $id): array
    {
        $stock = $this->repo->getByIdEnriched($id);
        if (!$stock) {
            throw new NotFoundException('Stock not found');
        }
        return $stock;
    }

    public function getByProductWarehouse(int $productId, int $warehouseId): array
    {
        $stock = $this->repo->getByProductAndWarehouse($productId, $warehouseId);
        if (!$stock) {
            throw new NotFoundException('Stock not found');
        }
        return $stock->toArray();
    }

    public function getByProduct(int $productId): array
    {
        $stocks = $this->repo->getByProduct($productId);
        return array_map(fn($s) => $s->toArray(), $stocks);
    }

    public function getByWarehouse(int $warehouseId): array
    {
        $stocks = $this->repo->getByWarehouse($warehouseId);
        return array_map(fn($s) => $s->toArray(), $stocks);
    }

    public function count(): int
    {
        return $this->repo->count();
    }

    public function update(int $id, array $data): array
    {
        try {
            $dto = UpdateStockRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        $updated = $this->repo->update($id, $dto->toArray());
        if (!$updated) {
            throw new NotFoundException('Stock not found');
        }
        return $updated->toArray();
    }

    public function updateByProductWarehouse(int $productId, int $warehouseId, array $data): array
    {
        try {
            $dto = UpdateStockRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        $updated = $this->repo->updateByProductWarehouse($productId, $warehouseId, $dto->toArray());
        if (!$updated) {
            throw new NotFoundException('Stock not found');
        }
        return $updated->toArray();
    }

    public function delete(int $id): void
    {
        $stock = $this->repo->getById($id);
        if (!$stock) {
            throw new NotFoundException('Stock not found');
        }
        if ($stock->getReserved() > 0) {
            throw new ValidationException('Cannot delete stock with active reservations');
        }
        if (!$this->repo->delete($id)) {
            throw new NotFoundException('Stock not found');
        }
    }

    public function deleteByProductWarehouse(int $productId, int $warehouseId): void
    {
        $stock = $this->repo->getByProductAndWarehouse($productId, $warehouseId);
        if (!$stock) {
            throw new NotFoundException('Stock not found');
        }
        if ($stock->getReserved() > 0) {
            throw new ValidationException('Cannot delete stock with active reservations');
        }
        if (!$this->repo->deleteByProductWarehouse($productId, $warehouseId)) {
            throw new NotFoundException('Stock not found');
        }
    }
}
