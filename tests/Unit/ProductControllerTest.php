<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Admin\Controllers\ProductController;
use App\Admin\Services\ProductService;

class ProductControllerTest extends TestCase
{
    private $serviceMock;
    private ProductController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(ProductService::class);
        $this->controller = new ProductController($this->serviceMock);
    }

    public function testCreateCallsServiceAndReturnsSuccess(): void
    {
        $data = ['name' => 'New Product', 'price_cents' => 1500];
        $this->serviceMock->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn(array_merge(['id' => 1], $data));

        $result = $this->controller->create($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Product created', $result['message']);
        $this->assertEquals(201, $result['code']);
    }

    public function testGetByIdEnrichedCallsCorrectServiceMethod(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getByIdEnriched')
            ->with(42)
            ->willReturn(['id' => 42, 'name' => 'Enriched Product']);

        $result = $this->controller->getByIdEnriched(42);

        $this->assertTrue($result['success']);
        $this->assertEquals('Product retrieved', $result['message']);
        $this->assertEquals(42, $result['data']['id']);
    }

    public function testGetAllCallsService(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getAll')
            ->with(10, 0)
            ->willReturn([]);

        $result = $this->controller->getAll(10, 0);

        $this->assertTrue($result['success']);
    }

    public function testGetByIdCallsService(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getById')
            ->with(99)
            ->willReturn(['id' => 99]);

        $result = $this->controller->getById(99);

        $this->assertTrue($result['success']);
    }

    public function testUpdateCallsService(): void
    {
        $data = ['price_cents' => 2000];
        $this->serviceMock->expects($this->once())
            ->method('update')
            ->with(5, $data)
            ->willReturn(['id' => 5, 'price_cents' => 2000]);

        $result = $this->controller->update(5, $data);

        $this->assertTrue($result['success']);
    }

    public function testDeleteCallsService(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('delete')
            ->with(7, false);

        $result = $this->controller->delete(7);

        $this->assertTrue($result['success']);
    }
}
