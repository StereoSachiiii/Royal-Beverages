<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\RecipeIngredientController;
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
    // Search by product name - MUST be before :id route
    $router->get('/recipe-ingredients/search', function (Request $request): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $query      = (string)$request->getQuery('search', '');
        $limit      = $request->getQuery('limit') ? (int)$request->getQuery('limit') : 50;
        $offset     = $request->getQuery('offset') ? (int)$request->getQuery('offset') : 0;
        return $controller->searchByProduct($query, $limit, $offset);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_search', 30, 60)
    ]);

    // GET single ingredient by ID
    $router->get('/recipe-ingredients/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $id         = (int)($params['id'] ?? 0);
        return $controller->getById($id);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_getById', 30, 60)
    ]);

    // GET all ingredients for a recipe
    $router->get('/recipe-ingredients/recipe/:recipe_id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $recipeId   = (int)($params['recipe_id'] ?? 0);
        return $controller->getByRecipeId($recipeId);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_getByRecipeId', 30, 60)
    ]);

    // GET required ingredients only
    $router->get('/recipe-ingredients/recipe/:recipe_id/required', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $recipeId   = (int)($params['recipe_id'] ?? 0);
        return $controller->getRequiredByRecipeId($recipeId);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_getRequired', 30, 60)
    ]);

    // GET recipes using a specific product
    $router->get('/recipe-ingredients/product/:product_id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $productId  = (int)($params['product_id'] ?? 0);
        return $controller->getByProductId($productId);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_getByProduct', 30, 60)
    ]);

    // GET recipe cost
    $router->get('/recipe-ingredients/recipe/:recipe_id/cost', function (Request $request, array $params): array {
        $controller      = $GLOBALS['container']->get(RecipeIngredientController::class);
        $recipeId        = (int)($params['recipe_id'] ?? 0);
        $includeOptional = $request->getQuery('include_optional') ? (bool)$request->getQuery('include_optional') : false;
        return $controller->getRecipeCost($recipeId, $includeOptional);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_getCost', 30, 60)
    ]);

    // GET low stock ingredients
    $router->get('/recipe-ingredients/recipe/:recipe_id/low-stock', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $recipeId   = (int)($params['recipe_id'] ?? 0);
        $threshold  = $request->getQuery('threshold') ? (int)$request->getQuery('threshold') : 10;
        return $controller->getLowStockIngredients($recipeId, $threshold);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_getLowStock', 30, 60)
    ]);

    // Check if recipe-product combination exists
    $router->get('/recipe-ingredients/recipe/:recipe_id/product/:product_id/exists', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $recipeId   = (int)($params['recipe_id'] ?? 0);
        $productId  = (int)($params['product_id'] ?? 0);
        return $controller->checkExists($recipeId, $productId);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_checkExists', 30, 60)
    ]);

    // Get ingredient count for a recipe
    $router->get('/recipe-ingredients/recipe/:recipe_id/count', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $recipeId   = (int)($params['recipe_id'] ?? 0);
        return $controller->getCount($recipeId);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_getCount', 30, 60)
    ]);

    // Get all ingredients (paginated)
    $router->get('/recipe-ingredients', function (Request $request): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $limit      = $request->getQuery('limit') ? (int)$request->getQuery('limit') : 50;
        $offset     = $request->getQuery('offset') ? (int)$request->getQuery('offset') : 0;
        return $controller->getAll($limit, $offset);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_getAll', 30, 60)
    ]);

    // Bulk create ingredients for a recipe
    $router->post('/recipe-ingredients/recipe/:recipe_id/bulk', function (Request $request, array $params): array {
        $controller  = $GLOBALS['container']->get(RecipeIngredientController::class);
        $body        = $request->getAllBody();
        $ingredients = $body['ingredients'] ?? [];
        $recipeId    = (int)($params['recipe_id'] ?? 0);
        return $controller->createBulk($recipeId, $ingredients);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_createBulk', 5, 60)
    ]);

    // Replace all ingredients for a recipe
    $router->post('/recipe-ingredients/recipe/:recipe_id/replace', function (Request $request, array $params): array {
        $controller  = $GLOBALS['container']->get(RecipeIngredientController::class);
        $body        = $request->getAllBody();
        $ingredients = $body['ingredients'] ?? [];
        $recipeId    = (int)($params['recipe_id'] ?? 0);
        return $controller->replaceRecipeIngredients($recipeId, $ingredients);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_replace', 5, 60)
    ]);

    // Create single ingredient
    $router->post('/recipe-ingredients', function (Request $request): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $body       = $request->getAllBody();
        return $controller->create($body);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_create', 10, 60)
    ]);

    // Update ingredient by ID
    $router->put('/recipe-ingredients/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required for update',
                'code'    => 400,
            ];
        }
        return $controller->update($id, $body);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_update', 10, 60)
    ]);

    // Delete all ingredients for a recipe
    $router->delete('/recipe-ingredients/recipe/:recipe_id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $recipeId   = (int)($params['recipe_id'] ?? 0);
        return $controller->deleteByRecipeId($recipeId);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_deleteAll', 5, 60)
    ]);

    // Delete single ingredient
    $router->delete('/recipe-ingredients/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(RecipeIngredientController::class);
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required for delete',
                'code'    => 400,
            ];
        }
        return $controller->delete($id);
    
    })->middleware([
        new RateLimitMiddleware('recipe_ingredient_delete', 10, 60)
    ]);
});
