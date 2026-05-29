<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\CocktailRecipeController;
use App\Admin\Middleware\RateLimitMiddleware;
use App\Admin\Middleware\AuthMiddleware;
use App\Admin\Middleware\CsrfMiddleware;
use App\Core\Router;

/** @var Router $router */

$router->group('/api/v1', function (Router $router): void {
    // Search by name - MUST be before list route
    $router->get('/cocktail-recipes/search', function (Request $request): array {
        $controller = $GLOBALS['container']->get(CocktailRecipeController::class);
        $query      = (string)$request->getQuery('search', '');
        $limit      = (int)$request->getQuery('limit', 50);
        $offset     = (int)$request->getQuery('offset', 0);
        return $controller->search($query, $limit, $offset);
    
    })->middleware([
        new RateLimitMiddleware('cocktail_search', 30, 60)
    ]);

    // GET /api/v1/cocktail-recipes/:id or list / count
    $router->get('/cocktail-recipes', function (Request $request): array {
        $controller = $GLOBALS['container']->get(CocktailRecipeController::class);
        // Single by ID
        $id = $request->getQuery('id');
        if ($id !== null) {
            return $controller->getById((int)$id);
        }
        // Count
        if ($request->getQuery('count') !== null) {
            return $controller->count();
        }
        // List all (admin only)
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        return $controller->getAll($limit, $offset);
    });

    // POST /api/v1/cocktail-recipes
    $router->post('/cocktail-recipes', function (Request $request): array {
        $controller = $GLOBALS['container']->get(CocktailRecipeController::class);
        $body       = $request->getAllBody();
        return $controller->create($body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('cocktail_create', 10, 60)
    ]);

    // PUT /api/v1/cocktail-recipes/:id
    $router->put('/cocktail-recipes/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(CocktailRecipeController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required',
                'code'    => 400,
            ];
        }
        return $controller->update($id, $body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware()
    ]);

    // DELETE /api/v1/cocktail-recipes/:id?hard=true
    $router->delete('/cocktail-recipes/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(CocktailRecipeController::class);
        $id         = (int)($params['id'] ?? 0);
        $hard       = $request->getQuery('hard') !== null;
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required',
                'code'    => 400,
            ];
        }
        return $controller->delete($id, $hard);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware()
    ]);
});
