<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Admin\Controllers\OrderController;
use App\Admin\Services\OrderService;
use App\Core\Session;
use App\DTO\Requests\CreateOrderRequest;

class CheckoutTest extends TestCase
{
    private $serviceMock;
    private $sessionMock;
    private OrderController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(OrderService::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->controller = new OrderController($this->serviceMock, $this->sessionMock);
    }

    public function testCheckoutCreatesOrderAndReturnsSuccess(): void
    {
        $payload = [
            'user_id' => 1,
            'status' => 'pending',
            'total_cents' => 15000,
            'items' => [
                ['product_id' => 10, 'quantity' => 2, 'price_cents' => 7500]
            ]
        ];

        // Ensure the create method on OrderService is called with the request payload
        $this->serviceMock->expects($this->once())
            ->method('create')
            ->with($payload)
            ->willReturn(array_merge(['id' => 999], $payload));

        $result = $this->controller->create($payload);

        $this->assertTrue($result['success']);
        $this->assertEquals('Order created', $result['message']);
        $this->assertEquals(999, $result['data']['id']);
        $this->assertEquals(201, $result['code']);
    }

    public function testCheckoutFailsWhenTotalIsZero(): void
    {
        $payload = [
            'user_id' => 1,
            'status' => 'pending',
            'total_cents' => 0,
            'items' => []
        ];

        // Since the actual validation logic in the real app might be inside the service
        // or controller, we test how the controller handles an exception from the service
        $this->serviceMock->expects($this->once())
            ->method('create')
            ->with($payload)
            ->willThrowException(new \App\Admin\Exceptions\ValidationException('Order must have items and total greater than 0'));

        $this->expectException(\App\Admin\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Order must have items and total greater than 0');

        $this->controller->create($payload);
    }
}
