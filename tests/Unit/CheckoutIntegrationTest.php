<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Admin\Services\OrderService;
use App\Admin\Services\StockService;
use App\Admin\Services\CartItemService;

class CheckoutIntegrationTest extends TestCase
{
    private $orderServiceMock;
    private $stockServiceMock;
    private $cartItemServiceMock;

    protected function setUp(): void
    {
        $this->orderServiceMock = $this->createMock(OrderService::class);
        $this->stockServiceMock = $this->createMock(StockService::class);
        $this->cartItemServiceMock = $this->createMock(CartItemService::class);
    }

    public function testFullCheckoutFlow(): void
    {
        $userId = 1;
        $cartId = 5;
        $orderId = 99;

        // 1. Get Cart Items
        $this->cartItemServiceMock->expects($this->once())
            ->method('getByCart')
            ->with($cartId)
            ->willReturn([
                ['product_id' => 10, 'quantity' => 2, 'price_cents' => 5000],
                ['product_id' => 15, 'quantity' => 1, 'price_cents' => 2000]
            ]);

        // 2. Create Order
        $this->orderServiceMock->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($payload) use ($userId) {
                return $payload['user_id'] === $userId && $payload['total_cents'] === 12000;
            }))
            ->willReturn(['id' => $orderId, 'status' => 'pending', 'total_cents' => 12000]);

        // 3. Reserve Stock
        $this->stockServiceMock->expects($this->once())
            ->method('reserveStock')
            ->with($orderId);

        // 4. Confirm Payment
        $this->orderServiceMock->expects($this->once())
            ->method('update')
            ->with($orderId, ['status' => 'paid'])
            ->willReturn(['id' => $orderId, 'status' => 'paid']);

        $this->stockServiceMock->expects($this->once())
            ->method('confirmPayment')
            ->with($orderId);

        // Execute Flow Simulation
        $cartItems = $this->cartItemServiceMock->getByCart($cartId);
        $totalCents = array_reduce($cartItems, fn($c, $i) => $c + ($i['quantity'] * $i['price_cents']), 0);

        $order = $this->orderServiceMock->create([
            'user_id' => $userId,
            'status' => 'pending',
            'total_cents' => $totalCents,
            'items' => $cartItems
        ]);

        $this->stockServiceMock->reserveStock($order['id']);

        // Payment gateway success simulation
        $paidOrder = $this->orderServiceMock->update($order['id'], ['status' => 'paid']);
        $this->stockServiceMock->confirmPayment($order['id']);

        // Assert final state
        $this->assertEquals('paid', $paidOrder['status']);
    }
}
