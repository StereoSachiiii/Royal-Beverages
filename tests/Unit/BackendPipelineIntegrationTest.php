<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\Request;
use App\Core\Router;
use App\DIContainer\Container;
use App\Admin\API\ApiServiceProvider;
use App\Admin\API\RouteLoader;

class BackendPipelineIntegrationTest extends TestCase
{
    private Container $container;
    private Router $router;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Authenticate as admin to pass AuthMiddleware
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin']  = true;
        $_SESSION['user_id']   = 1;
        $_SESSION['csrf_token'] = 'valid_csrf_token_123';

        $this->container = new Container();
        $provider = new ApiServiceProvider($this->container);
        $provider->register();

        $GLOBALS['container'] = $this->container;

        $this->router = new Router($this->container);
        RouteLoader::load($this->router);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($GLOBALS['container']);
    }

    /**
     * @dataProvider entityRoutesProvider
     */
    public function testEntityPipelinesResolveCorrectly(
        string $method,
        string $uri,
        string $expectedControllerClass,
        string $expectedControllerMethod
    ): void {
        // 1. Mock the specific controller to intercept the call at the end of the pipeline
        $mockController = $this->createMock($expectedControllerClass);
        
        // We expect the pipeline to successfully reach the controller's method
        $mockController->expects($this->once())
            ->method($expectedControllerMethod)
            ->willReturn([
                'success' => true,
                'message' => 'Pipeline reached successfully',
                'data' => [],
                'code' => 200
            ]);

        // Inject the mock into the container so the Router resolves it instead of the real one
        $this->container->instance($expectedControllerClass, $mockController);

        // 2. Setup the Request
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI']    = $uri;
        
        $headers = [];
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $_SERVER['HTTP_X_CSRF_TOKEN'] = 'valid_csrf_token_123';
            $headers['X-CSRF-Token'] = 'valid_csrf_token_123';
        }

        $request = new Request(
            $method,
            $uri,
            $headers,
            [], // query
            [], // body
            $_SERVER
        );

        // 3. Dispatch
        $result = $this->router->dispatch($request);

        // 4. Verify API contract is maintained (array returned with expected keys)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Pipeline reached successfully', $result['message']);
    }

    /**
     * Provides a massive list of all entity routes to ensure every single one
     * is wired up correctly in the Router without fatals or typos.
     */
    public static function entityRoutesProvider(): array
    {
        return [
            // Format: [ HTTP_METHOD, URI, ExpectedControllerClass, ExpectedMethod ]
            
            // Products
            ['GET',  '/api/v1/products', \App\Admin\Controllers\ProductController::class, 'getAll'],
            ['POST', '/api/v1/products', \App\Admin\Controllers\ProductController::class, 'create'],
            ['GET',  '/api/v1/products/1', \App\Admin\Controllers\ProductController::class, 'getById'],
            ['PUT',  '/api/v1/products/1', \App\Admin\Controllers\ProductController::class, 'update'],
            ['DELETE','/api/v1/products/1', \App\Admin\Controllers\ProductController::class, 'delete'],

            // Categories
            ['GET',  '/api/v1/categories', \App\Admin\Controllers\CategoryController::class, 'getAll'],
            ['POST', '/api/v1/categories', \App\Admin\Controllers\CategoryController::class, 'create'],
            
            // Users
            ['GET',  '/api/v1/admin/users', \App\Admin\Controllers\UserController::class, 'getAllUsers'],
            ['POST', '/api/v1/users/register', \App\Admin\Controllers\UserController::class, 'register'],
            
            // Suppliers
            ['GET',  '/api/v1/suppliers', \App\Admin\Controllers\SupplierController::class, 'getAll'],
            
            // Orders
            ['GET',  '/api/v1/orders', \App\Admin\Controllers\OrderController::class, 'getAll'],
            ['PUT',  '/api/v1/orders/1', \App\Admin\Controllers\OrderController::class, 'update'],
            
            // Order Items
            ['GET',  '/api/v1/order-items', \App\Admin\Controllers\OrderItemController::class, 'getAll'],
            
            // Stock
            ['GET',  '/api/v1/stock', \App\Admin\Controllers\StockController::class, 'getAll'],
            
            // Warehouses
            ['GET',  '/api/v1/warehouses', \App\Admin\Controllers\WarehouseController::class, 'getAll'],
            
            // Carts
            ['GET',  '/api/v1/carts', \App\Admin\Controllers\CartController::class, 'getAllEnriched'],
            
            // Cart Items
            ['GET',  '/api/v1/cart-items', \App\Admin\Controllers\CartItemController::class, 'getAllEnriched'],
            
            // Feedback
            ['GET',  '/api/v1/feedback', \App\Admin\Controllers\FeedbackController::class, 'getAllPaginated'],
            
            // Payments
            ['GET',  '/api/v1/payments', \App\Admin\Controllers\PaymentController::class, 'getAll'],
            
            // Recipe Ingredients
            ['GET',  '/api/v1/recipe-ingredients', \App\Admin\Controllers\RecipeIngredientController::class, 'getAll'],
            
            // Cocktail Recipes
            ['GET',  '/api/v1/cocktail-recipes', \App\Admin\Controllers\CocktailRecipeController::class, 'getAll'],
            
            // Flavour Profiles
            ['GET',  '/api/v1/flavor-profiles', \App\Admin\Controllers\FlavorProfileController::class, 'getAll'],
            
            // User Addresses
            ['GET',  '/api/v1/users/addresses', \App\Admin\Controllers\UserController::class, 'getAddresses'],
            
            // User Preferences
            ['GET',  '/api/v1/user-preferences', \App\Admin\Controllers\UserPreferenceController::class, 'getAll'],
            
            // Wishlist
            ['GET',  '/api/v1/wishlists', \App\Admin\Controllers\WishlistController::class, 'getMine'],
        ];
    }
}
