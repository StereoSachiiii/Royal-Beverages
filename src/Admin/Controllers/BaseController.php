<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Exceptions\BaseException;
use App\Admin\Exceptions\DatabaseException;
use Throwable;

abstract class BaseController
{
    /**
     * Execute a controller action with unified exception handling.
     *
     * @param callable $callback
     * @return array
     * @throws BaseException
     */
    protected function handle(callable $callback): array
    {
        try {
            return $callback();
        } catch (BaseException $e) {
            // Let domain exceptions bubble up to the global handler
            throw $e;
        } catch (Throwable $e) {
            // Wrap unexpected errors into a DatabaseException for now
            throw new DatabaseException(
                'Unexpected error: ' . $e->getMessage(),
                ['previous' => $e->getMessage()],
                500,
                $e
            );
        }
    }

    /**
     * Helper to build a standard success response array.
     */
    protected function success(string $message, mixed $data = null, int $code = 200, ?array $meta = null): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => [],
            'meta'    => $meta,
            'code'    => $code,
        ];
    }

    /**
     * Helper to build a standard error response array.
     */
    protected function error(string $message, int $code = 400, ?array $details = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $details ? [$details] : [],
            'code'    => $code,
        ];
    }

    /**
     * Helper for paginated responses.
     */
    protected function paginated(
        string $message,
        array $items,
        int $total,
        int $limit,
        int $offset
    ): array {
        return $this->success($message, $items, 200, [
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
            'count'  => count($items),
        ]);
    }

    /**
     * Legacy helper to send a JSON response directly and exit.
     * Bridging support for controllers not yet fully refactored to the Router return pattern.
     */
    protected function jsonResponse(array $data, int $code = 200): void
    {
        // Standardize the shape if success is missing
        if (!isset($data['success'])) {
            $data['success'] = true;
        }

        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Helper to retrieve and decode raw JSON input from the request body.
     */
    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode((string)$input, true) ?? [];
    }
}
