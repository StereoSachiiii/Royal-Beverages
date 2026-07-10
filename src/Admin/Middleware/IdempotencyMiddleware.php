<?php
declare(strict_types=1);

namespace App\Admin\Middleware;

use App\Core\Request;
use App\Interfaces\MiddlewareInterface;
use App\Admin\Repositories\IdempotencyRepository;

class IdempotencyMiddleware implements MiddlewareInterface
{
    public function __construct(private IdempotencyRepository $repository)
    {
    }

    public function handle(Request $request, callable $next): mixed
    {
        $idempotencyKey = $request->getHeader('Idempotency-Key');

        if (!$idempotencyKey) {
            return $next($request);
        }

        $existingRecord = $this->repository->find($idempotencyKey);
        
        if ($existingRecord) {
            // Return cached response
            $body = json_decode($existingRecord->responseBody, true) ?? [];
            return $body;
        }

        // Process request normally
        $response = $next($request);

        // Standardize the result to cache it properly (similar to index.php logic)
        $normalized = [
            'success' => $response['success'] ?? true,
            'message' => $response['message'] ?? 'OK',
            'data'    => $response['data']    ?? ($response['success'] ?? true ? ($response['data'] ?? $response) : null),
            'errors'  => $response['errors']  ?? [],
            'meta'    => $response['meta']    ?? null,
            'code'    => isset($response['code']) && is_int($response['code']) ? $response['code'] : 200,
        ];

        // Save new response
        $this->repository->store(
            $idempotencyKey,
            $request->getUri(),
            json_encode($normalized),
            $normalized['code']
        );

        return $normalized;
    }
}
