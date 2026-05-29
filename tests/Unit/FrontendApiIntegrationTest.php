<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\Request;
use App\Core\Router;
use App\DIContainer\Container;
use App\Admin\API\ApiServiceProvider;
use App\Admin\API\RouteLoader;
use App\Admin\Controllers\ProductController;
use App\Admin\Controllers\CategoryController;
use App\Admin\Controllers\WishlistController;
use App\Admin\Controllers\FeedbackController;

class FrontendApiIntegrationTest extends TestCase
{
    private Container $container;
    private Router $router;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];

        // 1. Initialize DI Container and register all admin services/controllers
        $this->container = new Container();
        $provider = new ApiServiceProvider($this->container);
        $provider->register();

        $GLOBALS['container'] = $this->container;

        // 2. Initialize Router and load all real route files
        $this->router = new Router($this->container);
        RouteLoader::load($this->router);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($GLOBALS['container']);
    }

    /**
     * Replicates API.products.list() -> GET /api/v1/products
     */
    public function testProductsListReplication(): void
    {
        $mock = $this->createMock(ProductController::class);
        $mock->expects($this->once())
            ->method('getAll')
            ->willReturn(['success' => true, 'data' => [['id' => 1, 'name' => 'Whiskey']]]);

        $this->container->instance(ProductController::class, $mock);

        $request = new Request('GET', '/api/v1/products');
        $result = $this->router->dispatch($request);

        $this->assertTrue($result['success']);
        $this->assertEquals('Whiskey', $result['data'][0]['name']);
    }

    /**
     * Replicates API.products.search() -> GET /api/v1/products/search
     */
    public function testProductsSearchReplication(): void
    {
        $mock = $this->createMock(ProductController::class);
        $mock->expects($this->once())
            ->method('search')
            ->with('vodka', 50, 0)
            ->willReturn(['success' => true, 'data' => [['id' => 2, 'name' => 'Vodka']]]);

        $this->container->instance(ProductController::class, $mock);

        $request = new Request('GET', '/api/v1/products/search', [], ['search' => 'vodka']);
        $result = $this->router->dispatch($request);

        $this->assertTrue($result['success']);
        $this->assertEquals('Vodka', $result['data'][0]['name']);
    }

    /**
     * Replicates API.products.get() -> GET /api/v1/products/:id
     */
    public function testProductsGetReplication(): void
    {
        $mock = $this->createMock(ProductController::class);
        $mock->expects($this->once())
            ->method('getById')
            ->with(42)
            ->willReturn(['success' => true, 'data' => ['id' => 42, 'name' => 'Gin']]);

        $this->container->instance(ProductController::class, $mock);

        $request = new Request('GET', '/api/v1/products/42');
        $result = $this->router->dispatch($request);

        $this->assertTrue($result['success']);
        $this->assertEquals('Gin', $result['data']['name']);
    }

    /**
     * Replicates API.categories.list() -> GET /api/v1/categories
     */
    public function testCategoriesListReplication(): void
    {
        $mock = $this->createMock(CategoryController::class);
        $mock->expects($this->once())
            ->method('getAll')
            ->willReturn(['success' => true, 'data' => [['id' => 3, 'name' => 'Spirits']]]);

        $this->container->instance(CategoryController::class, $mock);

        $request = new Request('GET', '/api/v1/categories');
        $result = $this->router->dispatch($request);

        $this->assertTrue($result['success']);
        $this->assertEquals('Spirits', $result['data'][0]['name']);
    }

    /**
     * Replicates API.wishlist.get() -> GET /api/v1/wishlists
     */
    public function testWishlistGetReplication(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = 7;

        $mock = $this->createMock(WishlistController::class);
        $mock->expects($this->once())
            ->method('getMine')
            ->will($this->returnCallback(function() {
                // Mimic the jsonResponse behavior by returning/printing the data structure
                return ['success' => true, 'data' => [['product_id' => 1]]];
            }));

        $this->container->instance(WishlistController::class, $mock);

        $request = new Request('GET', '/api/v1/wishlists');
        $result = $this->router->dispatch($request);

        // Since it's a mocked legacy controller method returning void (via jsonResponse),
        // we assert it matches the mock return.
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data'][0]['product_id']);
    }

    /**
     * Replicates API.wishlist.sync() -> POST /api/v1/wishlists/sync
     */
    public function testWishlistSyncReplication(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = 7;
        $_SESSION['csrf_token'] = 'valid_csrf';

        $mock = $this->createMock(WishlistController::class);
        $mock->expects($this->once())
            ->method('syncBulk')
            ->will($this->returnCallback(function() {
                return ['success' => true, 'message' => 'Synced'];
            }));

        $this->container->instance(WishlistController::class, $mock);

        $request = new Request(
            'POST',
            '/api/v1/wishlists/sync',
            ['X-CSRF-Token' => 'valid_csrf'],
            [],
            ['product_ids' => [1, 2]],
            $_SERVER
        );

        $result = $this->router->dispatch($request);

        $this->assertTrue($result['success']);
        $this->assertEquals('Synced', $result['message']);
    }

    /**
     * Replicates API.feedback.submit() -> POST /api/v1/feedback
     */
    public function testFeedbackSubmitReplication(): void
    {
        $_SESSION['csrf_token'] = 'valid_csrf';

        $mock = $this->createMock(FeedbackController::class);
        $mock->expects($this->once())
            ->method('create')
            ->willReturn(['success' => true, 'message' => 'Feedback submitted']);

        $this->container->instance(FeedbackController::class, $mock);

        $request = new Request(
            'POST',
            '/api/v1/feedback',
            ['X-CSRF-Token' => 'valid_csrf'],
            [],
            ['product_id' => 1, 'rating' => 5, 'comment' => 'Great!'],
            $_SERVER
        );

        $result = $this->router->dispatch($request);

        $this->assertTrue($result['success']);
        $this->assertEquals('Feedback submitted', $result['message']);
    }
}
