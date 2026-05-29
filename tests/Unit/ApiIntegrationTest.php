<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\DIContainer\Container;
use App\Admin\API\ApiServiceProvider;
use App\Admin\API\RouteLoader;
use App\Admin\Controllers\ProductController;
use App\Admin\Exceptions\UnauthorizedException;
use App\Admin\Exceptions\ForbiddenException;
use App\Admin\Exceptions\RateLimitException;

class ApiIntegrationTest extends TestCase
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

        // Bind container to GLOBALS as expected by the route loader
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
     * Test the full routing + middleware + controller resolution flow.
     */
    public function testRouteThroughEntirePipelineAndMiddleware(): void
    {
        // 1. Setup mock controller to intercept the request at the end of MVC
        $productControllerMock = $this->createMock(ProductController::class);
        $productControllerMock->expects($this->once())
            ->method('create')
            ->willReturn([
                'success' => true,
                'message' => 'Product created successfully',
                'data'    => ['id' => 99, 'name' => 'Premium Vodka'],
                'code'    => 201
            ]);

        // Register the mock controller in the container
        $this->container->instance(ProductController::class, $productControllerMock);

        // 2. Simulate POST /api/v1/products with Admin credentials and valid CSRF
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin']  = true;
        $_SESSION['user_id']   = 1;
        $_SESSION['csrf_token'] = 'valid_csrf_token_123';

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI']    = '/api/v1/products';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'valid_csrf_token_123';

        // Set fake post body to satisfy Request
        $request = new Request(
            'POST',
            '/api/v1/products',
            ['X-CSRF-Token' => 'valid_csrf_token_123'],
            [], // query
            ['name' => 'Premium Vodka'], // body
            $_SERVER
        );

        // 3. Dispatch the request
        $result = $this->router->dispatch($request);

        // 4. Assert the result returned from the pipeline matches expectations
        $this->assertTrue($result['success']);
        $this->assertEquals('Product created successfully', $result['message']);
        $this->assertEquals(99, $result['data']['id']);
        $this->assertEquals(201, $result['code']);
    }

    /**
     * Verify that AuthMiddleware blocks unauthorized calls on protected routes.
     */
    public function testPostProductBlocksUnauthenticated(): void
    {
        // Ensure logged_in is false
        $_SESSION['logged_in'] = false;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI']    = '/api/v1/products';

        $request = Request::createFromGlobals();

        $this->expectException(UnauthorizedException::class);
        $this->router->dispatch($request);
    }

    /**
     * Verify that CSRFMiddleware blocks state-changing calls with missing CSRF tokens.
     */
    public function testPostProductBlocksInvalidCsrf(): void
    {
        // Authenticated as admin, but no CSRF token sent
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin']  = true;
        $_SESSION['csrf_token'] = 'valid_csrf_token_123';

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI']    = '/api/v1/products';
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        }

        $request = Request::createFromGlobals();

        $this->expectException(ForbiddenException::class);
        $this->router->dispatch($request);
    }
}
