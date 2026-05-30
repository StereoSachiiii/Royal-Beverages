<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\ProductController;
use App\Admin\Middleware\RateLimitMiddleware;
use App\Admin\Middleware\AuthMiddleware;
use App\Admin\Middleware\CSRFMiddleware;
use App\Core\Router;

if (!defined('ROOT_PATH')) {
    http_response_code(400);
    exit;
}

/** @var \App\Core\Router $router */


/** @var Router $router */


$router->group('/api/v1', function (Router $router): void {
    // List products (basic) - with optional search
    $router->get('/products', function (Request $request): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        $search = trim((string)$request->getQuery('search', ''));
        
        // Use search method if query provided, otherwise getAll
        if ($search !== '') {
            return $productController->search($search, $limit, $offset);
        }
        return $productController->getAll($limit, $offset);
    });

    // Enriched listing with filters (shopAllEnriched)
    $router->get('/products/enriched', function (Request $request): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $limit       = (int)$request->getQuery('limit', 24);
        $offset      = (int)$request->getQuery('offset', 0);
        $search      = trim((string)$request->getQuery('search', ''));
        $categoryId  = $request->getQuery('category_id') !== null ? (int)$request->getQuery('category_id') : null;
        $minPrice    = $request->getQuery('min_price') !== null ? (int)$request->getQuery('min_price') : null;
        $maxPrice    = $request->getQuery('max_price') !== null ? (int)$request->getQuery('max_price') : null;
        $sort        = (string)$request->getQuery('sort', 'newest');

        return $productController->shopAllEnriched($limit, $offset, $search, $categoryId, $minPrice, $maxPrice, $sort);
    });

    // Simple enriched list
    $router->get('/products/enriched/all', function (Request $request): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $limit      = (int)$request->getQuery('limit', 50);
        $offset     = (int)$request->getQuery('offset', 0);
        return $productController->getAllEnriched($limit, $offset);
    });

    // Top sellers
    $router->get('/products/top', function (Request $request): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $limit      = (int)$request->getQuery('top', 10);
        return $productController->getTopSellers($limit);
    });

    // Search (basic)
    $router->get('/products/search', function (Request $request): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $query      = (string)$request->getQuery('search', '');
        $limit      = (int)$request->getQuery('limit', 50);
        $offset     = (int)$request->getQuery('offset', 0);
        return $productController->search($query, $limit, $offset);
    });

    // Search enriched
    $router->get('/products/search/enriched', function (Request $request): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $query      = (string)$request->getQuery('search', '');
        $limit      = (int)$request->getQuery('limit', 50);
        $offset     = (int)$request->getQuery('offset', 0);
        return $productController->searchEnriched($query, $limit, $offset);
    });

    // Single product enriched (includes stock, ratings, category, supplier)
    $router->get('/products/:id/enriched', function (Request $request, array $params): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Product ID required', 'code' => 400];
        }
        return $productController->getByIdEnriched($id);
    });

    // Single product (basic)
    $router->get('/products/:id', function (Request $request, array $params): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $id         = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Product ID required',
                'code'    => 400,
            ];
        }
        return $productController->getById($id);
    });

    // Counts
    $router->get('/products/count', function (Request $request): array {
        $productController = $GLOBALS['container']->get(ProductController::class);
        $includeInactive = $request->getQuery('includeInactive') === 'true';

        if ($includeInactive) {
            AuthMiddleware::requireAdmin();
            return $productController->countAll();
        }
        return $productController->count();
    });

    // Admin create
    $router->post('/products', function (Request $request): array {
        AuthMiddleware::requireAdmin();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('product_create', 5, 60);

        $productController = $GLOBALS['container']->get(ProductController::class);
        $body       = $request->getAllBody();
        return $productController->create($body);
    });

    // Admin update
    $router->put('/products/:id', function (Request $request, array $params): array {
        AuthMiddleware::requireAdmin();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('product_update', 5, 60);

        $productController = $GLOBALS['container']->get(ProductController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Product ID required',
                'code'    => 400,
            ];
        }

        return $productController->update($id, $body);
    });

    // Admin delete / hard delete
    $router->delete('/products/:id', function (Request $request, array $params): array {
        AuthMiddleware::requireAdmin();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('product_delete', 5, 60);

        $productController = $GLOBALS['container']->get(ProductController::class);
        $id         = (int)($params['id'] ?? 0);
        $hard       = $request->getQuery('hard') === 'true';

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Product ID required',
                'code'    => 400,
            ];
        }

        return $hard ? $productController->hardDelete($id) : $productController->delete($id);
    });
});
