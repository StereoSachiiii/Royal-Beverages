<?php
declare(strict_types=1);

namespace App\Admin\API;

use App\DIContainer\ServiceProvider;
use App\Core\Session;
use App\Core\Database;
use PDO;

// Repositories
use App\Admin\Repositories\ProductRepository;
use App\Admin\Repositories\StockRepository;
use App\Admin\Repositories\CategoryRepository;
use App\Admin\Repositories\CartRepository;
use App\Admin\Repositories\CartItemRepository;
use App\Admin\Repositories\OrderRepository;
use App\Admin\Repositories\OrderItemRepository;
use App\Admin\Repositories\PaymentRepository;
use App\Admin\Repositories\WarehouseRepository;
use App\Admin\Repositories\SupplierRepository;
use App\Admin\Repositories\AddressRepository;
use App\Admin\Repositories\FeedbackRepository;
use App\Admin\Repositories\FlavorProfileRepository;
use App\Admin\Repositories\CocktailRecipeRepository;

use App\Admin\Repositories\RecipeIngredientRepository;
use App\Admin\Repositories\UserPreferenceRepository;
use App\Admin\Repositories\WishlistRepository;
use App\Admin\Repositories\AdminViewRepository;
use App\Admin\Repositories\UserRepository;

// Services
use App\Admin\Services\ProductService;
use App\Admin\Services\StockService;
use App\Admin\Services\CategoryService;
use App\Admin\Services\CartService;
use App\Admin\Services\CartItemService;
use App\Admin\Services\OrderService;
use App\Admin\Services\OrderItemService;
use App\Admin\Services\PaymentService;
use App\Admin\Services\WarehouseService;
use App\Admin\Services\SupplierService;
use App\Admin\Services\AddressService;
use App\Admin\Services\FeedbackService;
use App\Admin\Services\FlavorProfileService;
use App\Admin\Services\CocktailRecipeService;

use App\Admin\Services\RecipeIngredientService;
use App\Admin\Services\UserPreferenceService;
use App\Admin\Services\WishlistService;
use App\Admin\Services\AIRecommendationService;
use App\Admin\Services\AdminViewService;
use App\Admin\Services\UserService;

// Controllers
use App\Admin\Controllers\ProductController;
use App\Admin\Controllers\StockController;
use App\Admin\Controllers\CategoryController;
use App\Admin\Controllers\CartController;
use App\Admin\Controllers\CartItemController;
use App\Admin\Controllers\OrderController;
use App\Admin\Controllers\OrderItemController;
use App\Admin\Controllers\PaymentController;
use App\Admin\Controllers\WarehouseController;
use App\Admin\Controllers\SupplierController;
use App\Admin\Controllers\AddressController;
use App\Admin\Controllers\FeedbackController;
use App\Admin\Controllers\FlavorProfileController;
use App\Admin\Controllers\CocktailRecipeController;

use App\Admin\Controllers\RecipeIngredientController;
use App\Admin\Controllers\UserPreferenceController;
use App\Admin\Controllers\WishlistController;
use App\Admin\Controllers\RecommendationController;
use App\Admin\Controllers\AdminViewController;
use App\Admin\Controllers\UserController;

/**
 * ApiServiceProvider
 *
 * Registers all Admin API dependencies into the container.
 * Uses the custom DIContainer system.
 */
class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // PDO — single connection managed by container
        $this->container->instance(PDO::class, Database::getPdo());

        // Session — static factory
        $this->container->instance(Session::class, Session::getInstance());

        // Repositories
        foreach ($this->repositories() as $class) {
            $this->container->singleton($class);
        }

        // Services
        foreach ($this->services() as $class) {
            $this->container->singleton($class);
        }

        // Controllers
        foreach ($this->controllers() as $class) {
            $this->container->singleton($class);
        }
    }

    private function repositories(): array
    {
        return [
            ProductRepository::class,
            StockRepository::class,
            CategoryRepository::class,
            CartRepository::class,
            CartItemRepository::class,
            OrderRepository::class,
            OrderItemRepository::class,
            PaymentRepository::class,
            WarehouseRepository::class,
            SupplierRepository::class,
            AddressRepository::class,
            FeedbackRepository::class,
            FlavorProfileRepository::class,
            CocktailRecipeRepository::class,

            RecipeIngredientRepository::class,
            UserPreferenceRepository::class,
            WishlistRepository::class,
            AdminViewRepository::class,
            UserRepository::class,
        ];
    }

    private function services(): array
    {
        return [
            ProductService::class,
            StockService::class,
            CategoryService::class,
            CartService::class,
            CartItemService::class,
            OrderService::class,
            OrderItemService::class,
            PaymentService::class,
            WarehouseService::class,
            SupplierService::class,
            AddressService::class,
            FeedbackService::class,
            FlavorProfileService::class,
            CocktailRecipeService::class,

            RecipeIngredientService::class,
            UserPreferenceService::class,
            WishlistService::class,
            AIRecommendationService::class,
            AdminViewService::class,
            UserService::class,
        ];
    }

    private function controllers(): array
    {
        return [
            ProductController::class,
            StockController::class,
            CategoryController::class,
            CartController::class,
            CartItemController::class,
            OrderController::class,
            OrderItemController::class,
            PaymentController::class,
            WarehouseController::class,
            SupplierController::class,
            AddressController::class,
            FeedbackController::class,
            FlavorProfileController::class,
            CocktailRecipeController::class,

            RecipeIngredientController::class,
            UserPreferenceController::class,
            WishlistController::class,
            RecommendationController::class,
            AdminViewController::class,
            UserController::class,
        ];
    }
}
