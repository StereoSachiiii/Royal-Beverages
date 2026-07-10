<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Admin\Controllers\OAuthController;
use App\Core\Router;

if (!defined('ROOT_PATH')) {
    http_response_code(400);
    exit;
}

/** @var Router $router */

$router->group('/api/v1', function (Router $router): void {
    $router->get('/oauth/google/redirect', function (Request $request): void {
        $controller = $GLOBALS['container']->get(OAuthController::class);
        $controller->redirect($request);
    });

    $router->get('/oauth/google/callback', function (Request $request): array {
        $controller = $GLOBALS['container']->get(OAuthController::class);
        return $controller->callback($request);
    });
});
