<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Router;
use App\Admin\Controllers\RecommendationController;
use App\Admin\Middleware\RateLimitMiddleware;

$router->group('/api/v1', function (Router $router): void {

    // GET /api/v1/recommendations/for-you
    $router->get('/recommendations/for-you', function (Request $request): void {
        // Rate limit softly so bots don't drain the Gemini API key bounds
        $controller = $GLOBALS['container']->get(RecommendationController::class);
        $controller->getForYou();
    
    })->middleware([
        new RateLimitMiddleware('ai_recommendations', 20, 60)
    ]);

});
