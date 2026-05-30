<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\ImageController;
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
    $router->post('/images/upload', function (Request $request): array {
        $controller = $GLOBALS['container']->get(ImageController::class);
        // Grab post body and files
        return $controller->upload($_POST, $_FILES);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('image_upload', 20, 60)
    ]);
});
