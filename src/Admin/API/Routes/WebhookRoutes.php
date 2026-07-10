<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Admin\Controllers\StripeWebhookController;
use App\Core\Router;

if (!defined('ROOT_PATH')) {
    http_response_code(400);
    exit;
}

/** @var Router $router */

$router->group('/api/v1', function (Router $router): void {
    $router->post('/webhooks/stripe', function (Request $request): array {
        $controller = $GLOBALS['container']->get(StripeWebhookController::class);
        return $controller->handleWebhook($request);
    });
});
