<?php
declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Repositories\OrderItemRepository;
use App\Admin\Repositories\StockRepository;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

use App\DTO\Requests\CreateOrderItemRequest;
use App\DTO\Requests\UpdateOrderItemRequest;
use App\DTO\DTOException;

class OrderItemService
{
    public function __construct(
        private OrderItemRepository $repo,
        private StockRepository $stockRepo,
    ) {}

    private function adjustStockReservation(
        int $productId, 
        ?int $oldWarehouseId, 
        ?int $newWarehouseId, 
        int $oldQuantity, 
        int $newQuantity
    ): void {
        // Scenario 1: Warehouse changed
        if ($oldWarehouseId !== $newWarehouseId) {
            // Release from old warehouse
            if ($oldWarehouseId !== null) {
                $this->releaseReservation($productId, $oldWarehouseId, $oldQuantity);
            }
            
            // Reserve in new warehouse
            if ($newWarehouseId !== null) {
                $this->reserveInWarehouse($productId, $newWarehouseId, $newQuantity);
            }
        }
        // Scenario 2: Quantity changed (same warehouse)
        elseif ($oldQuantity !== $newQuantity && $oldWarehouseId !== null) {
            $quantityDiff = $newQuantity - $oldQuantity;
            
            if ($quantityDiff > 0) {
                // Increasing - reserve more
                $this->reserveInWarehouse($productId, $oldWarehouseId, $quantityDiff);
            } else {
                // Decreasing - release some
                $this->releaseReservation($productId, $oldWarehouseId, abs($quantityDiff));
            }
        }
    }

    private function reserveInWarehouse(int $productId, int $warehouseId, int $quantity): void
    {
        $stock = $this->stockRepo->getByProductAndWarehouse($productId, $warehouseId);
        
        if (!$stock) {
            throw new DatabaseException("No stock record for product $productId in warehouse $warehouseId");
        }
        
        $available = $stock->quantity - $stock->reserved;
        if ($available < $quantity) {
            throw new DatabaseException(
                "Insufficient stock in warehouse $warehouseId: available=$available, requested=$quantity"
            );
        }
        
        // Update reserved stock
        $this->stockRepo->updateByProductWarehouse($productId, $warehouseId, [
            'reserved' => $stock->reserved + $quantity
        ]);
    }

    private function releaseReservation(int $productId, int $warehouseId, int $quantity): void
    {
        $stock = $this->stockRepo->getByProductAndWarehouse($productId, $warehouseId);
        
        if ($stock) {
            $newReserved = max(0, $stock->reserved - $quantity);
            $this->stockRepo->updateByProductWarehouse($productId, $warehouseId, [
                'reserved' => $newReserved
            ]);
        }
    }

    public function create(array $data): array
    {
        try {
            $dto = CreateOrderItemRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }
        
        $validData = $dto->toArray();

        // Auto-assign warehouse if missing
        if (!isset($validData['warehouse_id']) || $validData['warehouse_id'] !== null) {
            $bestStock = $this->stockRepo->findWarehouseWithHighestStock($validData['product_id']);
            if (!$bestStock || $bestStock['available'] < $validData['quantity']) {
                throw new DatabaseException("Insufficient stock available for product {$validData['product_name']}");
            }
            $validData['warehouse_id'] = $bestStock['warehouse_id'];
        }

        // Reserve stock (since warehouse is now definitely assigned)
        $this->reserveInWarehouse(
            $validData['product_id'], 
            $validData['warehouse_id'], 
            $validData['quantity']
        );
        
        $item = $this->repo->create($validData);
        return $item->toArray();
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $items = $this->repo->getAll($limit, $offset);
        return array_map(fn($i) => $i->toArray(), $items);
    }

    public function getAllPaginated(int $limit = 50, int $offset = 0): array
    {
        return $this->repo->getAllPaginated($limit, $offset);
    }

    public function getByIdEnriched(int $id): ?array
    {
        return $this->repo->getByIdEnriched($id);
    }

    public function update(int $id, array $data): array
    {
        try {
            $dto = UpdateOrderItemRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }
        
        $changeset = $dto->toChangeset();

        // Get current state
        $current = $this->repo->getById($id);
        if (!$current) {
            throw new NotFoundException('Order item not found');
        }
        
        // Determine new values
        $newWarehouseId = array_key_exists('warehouse_id', $changeset) ? $changeset['warehouse_id'] : $current->warehouse_id;
        $newQuantity = $changeset['quantity'] ?? $current->quantity;
        
        // Adjust stock reservations
        $this->adjustStockReservation(
            $current->product_id,
            $current->warehouse_id,
            $newWarehouseId,
            $current->quantity,
            $newQuantity
        );
        
        // Update order item
        $item = $this->repo->update($id, $changeset);
        return $item->toArray();
    }

    public function getById(int $id): array
    {
        $item = $this->repo->getById($id);
        if (!$item) {
            throw new NotFoundException('Order item not found');
        }
        return $item->toArray();
    }

    public function getByOrder(int $orderId): array
    {
        $items = $this->repo->getByOrder($orderId);
        return array_map(fn($i) => $i->toArray(), $items);
    }

    public function count(): int
    {
        return $this->repo->count();
    }

    public function delete(int $id): void
    {
        // Get current state to release stock
        $item = $this->repo->getById($id);
        if (!$item) {
            throw new NotFoundException('Order item not found');
        }
        
        // Release reserved stock if warehouse is assigned
        if ($item->warehouse_id !== null) {
            $this->releaseReservation(
                $item->product_id, 
                $item->warehouse_id, 
                $item->quantity
            );
        }
        
        // Delete the order item
        $deleted = $this->repo->delete($id);
        if (!$deleted) {
            throw new NotFoundException('Order item not found');
        }
    }
}
