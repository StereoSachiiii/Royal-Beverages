<?php
declare(strict_types=1);

namespace App\Admin\Exceptions;

use Throwable;

class DuplicateException extends BaseException {
    protected int $statusCode = 409;

    public function __construct(string $message = "Duplicate entry found.", array $context = [], int $code = 409, ?Throwable $previous = null) {
        parent::__construct($message, $context, $code, $previous);
    }   
}



?>
