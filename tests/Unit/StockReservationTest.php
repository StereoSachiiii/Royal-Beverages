<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Admin\Services\StockService;
use App\Admin\Repositories\StockRepository;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Models\StockModel;

class StockReservationTest extends TestCase
{
    private $repoMock;
    private StockService $service;

    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(StockRepository::class);
        $this->service = new StockService($this->repoMock);
    }

    public function testReserveStockCallsRepository(): void
    {
        $orderId = 123;
        
        $this->repoMock->expects($this->once())
            ->method('reserveStock')
            ->with($orderId);

        $this->service->reserveStock($orderId);
    }

    public function testTransferStockSuccessfully(): void
    {
        $productId = 1;
        $fromWarehouse = 10;
        $toWarehouse = 20;
        $quantity = 5;

        $fromStockMock = $this->createMock(StockModel::class);
        $fromStockMock->method('getId')->willReturn(100);
        $fromStockMock->method('getQuantity')->willReturn(20);
        $fromStockMock->method('getReserved')->willReturn(5); // Available = 15

        $toStockMock = $this->createMock(StockModel::class);
        $toStockMock->method('getId')->willReturn(101);
        $toStockMock->method('getQuantity')->willReturn(50);

        // Map consecutive calls to getByProductAndWarehouse
        $this->repoMock->expects($this->exactly(2))
            ->method('getByProductAndWarehouse')
            ->willReturnCallback(function($pId, $wId) use ($fromWarehouse, $fromStockMock, $toStockMock) {
                if ($wId === $fromWarehouse) return $fromStockMock;
                return $toStockMock;
            });

        $this->repoMock->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(function($id, $data) use ($toStockMock) {
                return $toStockMock;
            });

        $result = $this->service->transferStock($productId, $fromWarehouse, $toWarehouse, $quantity);

        $this->assertEquals($productId, $result['product_id']);
        $this->assertEquals($quantity, $result['quantity']);
    }

    public function testTransferStockFailsWhenInsufficientStock(): void
    {
        $fromStockMock = $this->createMock(StockModel::class);
        $fromStockMock->method('getQuantity')->willReturn(10);
        $fromStockMock->method('getReserved')->willReturn(8); // Available = 2

        $this->repoMock->method('getByProductAndWarehouse')->willReturn($fromStockMock);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient available stock. Available: 2, Requested: 5');

        $this->service->transferStock(1, 10, 20, 5);
    }
}
