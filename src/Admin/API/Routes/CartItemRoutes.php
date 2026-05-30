<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\CartItemController;
use App\Admin\Middleware\RateLimitMiddleware;
use App\Admin\Middleware\AuthMiddleware;
use App\Admin\Middleware\CSRFMiddleware;
use App\Core\Router;


$router->group('/api/v1', function (Router $router): void {
    // Admin list of cart items
    $router->get('/cart-items', function (Request $request): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        $search = $request->getQuery('search', '');
        if ($search) {
            return $cartItemController->search($search, $limit, $offset);
        }
        return $cartItemController->getAllEnriched($limit, $offset);
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Get cart item by ID (enriched with product + cart data)
    $router->get('/cart-items/:id', function (Request $request, array $params): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Cart item ID required',
                'code'    => 400,
            ];
        }
        return $cartItemController->getByIdEnriched($id);
    });

    // Get cart item by cart and product
    $router->get('/cart-items/cart/:cart_id/product/:product_id', function (Request $request, array $params): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $cartId     = (int)($params['cart_id'] ?? 0);
        $productId  = (int)($params['product_id'] ?? 0);
        return $cartItemController->getByCartProduct($cartId, $productId);
    });

    // Get all items for a cart
    $router->get('/cart-items/cart/:cart_id', function (Request $request, array $params): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $cartId     = (int)($params['cart_id'] ?? 0);
        return $cartItemController->getByCart($cartId);
    });

    // Count cart items
    $router->get('/cart-items/count', function (Request $request): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        return $cartItemController->count();
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Create cart item
    $router->post('/cart-items', function (Request $request): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $body       = $request->getAllBody();
        return $cartItemController->create($body);
    
    })->middleware([
        new CSRFMiddleware(),
        new RateLimitMiddleware('cart_item_create', 20, 60)
    ]);

    // Update cart item
    $router->put('/cart-items/:id', function (Request $request, array $params): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Cart item ID required',
                'code'    => 400,
            ];
        }
        return $cartItemController->update($id, $body);
    
    })->middleware([
        new CSRFMiddleware(),
        new RateLimitMiddleware('cart_item_update', 20, 60)
    ]);

    // Update by cart + product
    $router->put('/cart-items/cart/:cart_id/product/:product_id', function (Request $request, array $params): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $body       = $request->getAllBody();
        $cartId     = (int)($params['cart_id'] ?? 0);
        $productId  = (int)($params['product_id'] ?? 0);
        return $cartItemController->updateByCartProduct($cartId, $productId, $body);
    
    })->middleware([
        new CSRFMiddleware(),
        new RateLimitMiddleware('cart_item_update', 20, 60)
    ]);

    // Delete cart item
    $router->delete('/cart-items/:id', function (Request $request, array $params): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Cart item ID required',
                'code'    => 400,
            ];
        }
        return $cartItemController->delete($id);
    
    })->middleware([
        new CSRFMiddleware(),
        new RateLimitMiddleware('cart_item_delete', 10, 60)
    ]);

    // Delete by cart + product
    $router->delete('/cart-items/cart/:cart_id/product/:product_id', function (Request $request, array $params): array {
        $cartItemController = $GLOBALS['container']->get(CartItemController::class);
        $cartId     = (int)($params['cart_id'] ?? 0);
        $productId  = (int)($params['product_id'] ?? 0);
        return $cartItemController->deleteByCartProduct($cartId, $productId);
    
    })->middleware([
        new CSRFMiddleware(),
        new RateLimitMiddleware('cart_item_delete', 10, 60)
    ]);
});
