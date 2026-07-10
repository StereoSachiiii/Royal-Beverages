<?php
declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Repositories\OrderRepository;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\DatabaseException;

use App\DTO\Requests\CreateOrderRequest;
use App\DTO\Requests\UpdateOrderRequest;
use App\DTO\DTOException;

class OrderService
{
    public function __construct(
        private OrderRepository $repo,
        private OrderItemService $itemService,
        private StockService $stockService,
        private \App\Core\Database $db,
        private ?\App\Admin\Repositories\JobRepository $jobRepo = null
    ) {}

    public function create(array $data): array
    {
        try {
            $dto = CreateOrderRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        $pdo = $this->db->getPdo();
        
        try {
            $pdo->beginTransaction();

            $orderData = $dto->toArray();
            $itemsData = $orderData['items'] ?? [];
            unset($orderData['items']);

            // 1. Create the order
            $order = $this->repo->create($orderData);

            // 2. Create order items (OrderItemService handles stock reservation internally)
            if (!empty($itemsData)) {
                foreach ($itemsData as $item) {
                    $item['order_id'] = $order->getId();
                    $this->itemService->create($item);
                }
            }

            $pdo->commit();

            if ($this->jobRepo) {
                $this->jobRepo->enqueue('order_confirmation', [
                    'order_id' => $order->getId(),
                    'user_id' => $order->getUserId()
                ]);
            }

            return $order->toArray();

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $orders = $this->repo->getAll($limit, $offset);
        return array_map(fn($o) => $o->toArray(), $orders);
    }

    public function getById(int $id): array
    {
        $order = $this->repo->getById($id);
        if (!$order) {
            throw new NotFoundException('Order not found');
        }
        return $order->toArray();
    }

    public function getDetailedOrderById(int $id): array
    {
        $orderModel = $this->repo->getById($id);
        if (!$orderModel) {
            throw new NotFoundException('Order not found');
        }

        $detailed = $this->repo->getDetailedOrderById($id);
        if (!$detailed) {
            throw new NotFoundException('Order not found');
        }

        return $detailed;
    }

    public function getByOrderNumber(string $orderNumber): array
    {
        $order = $this->repo->getByOrderNumber($orderNumber);
        if (!$order) {
            throw new NotFoundException('Order not found');
        }
        return $order->toArray();
    }

    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $orders = $this->repo->getByUser($userId, $limit, $offset);
        return array_map(fn($o) => $o->toArray(), $orders);
    }

    public function count(): int
    {
        return $this->repo->count();
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $orders = $this->repo->search($query, $limit, $offset);
        return array_map(fn($o) => $o->toArray(), $orders);
    }

    public function update(int $id, array $data): array
    {
        try {
            $dto = UpdateOrderRequest::fromArray($data);
        } catch (DTOException $e) {
            throw new ValidationException($e->getMessage(), $e->getErrors());
        }

        if (!$this->repo->getById($id)) {
            throw new NotFoundException('Order not found');
        }

        $updated = $this->repo->update($id, $dto->toChangeset());
        if (!$updated) {
            throw new NotFoundException('Order not found');
        }

        return $updated->toArray();
    }

    public function delete(int $id): void
    {
        $deleted = $this->repo->delete($id);
        if (!$deleted) {
            throw new NotFoundException('Order not found');
        }
    }

    public function cancel(int $id, int $currentUserId, bool $isAdmin): array
    {
        $order = $this->repo->getById($id);
        if (!$order) {
            throw new NotFoundException('Order not found');
        }

        if (!$isAdmin && $order->getUserId() !== $currentUserId) {
            throw new NotFoundException('Order not found or access denied');
        }

        if (in_array($order->getStatus(), ['shipped', 'delivered', 'refunded', 'cancelled'])) {
            throw new ValidationException('Order cannot be cancelled in its current state: ' . $order->getStatus());
        }

        $pdo = $this->db->getPdo();

        try {
            $pdo->beginTransaction();

            // 1. Release stock reservations
            $this->stockService->cancelOrder($id);

            // 2. Update order status
            $cancelledOrder = $this->repo->cancelOrder($id);
            if (!$cancelledOrder) {
                throw new DatabaseException('Failed to update order status during cancellation');
            }

            $pdo->commit();
            return $cancelledOrder->toArray();

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
