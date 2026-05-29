<?php
declare(strict_types=1);

namespace App\Admin\Exceptions;

use Throwable;

class ForbiddenException extends BaseException
{
    protected int $statusCode = 403;

    public function __construct(
        string $message = "Forbidden: Access is denied",
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
    }
}
