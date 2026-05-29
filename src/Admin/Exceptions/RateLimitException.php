<?php
declare(strict_types=1);

namespace App\Admin\Exceptions;

use Throwable;

class RateLimitException extends BaseException
{
    protected int $statusCode = 429;

    public function __construct(
        string $message = "Rate limit exceeded. Try again later.",
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
    }
}
