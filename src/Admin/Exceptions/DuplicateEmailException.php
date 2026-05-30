<?php
declare(strict_types=1);

namespace App\Admin\Exceptions;

use Throwable;

class DuplicateEmailException extends BaseException {
    protected int $statusCode = 409;

    /**
     * Summary of __construct
     * @param string $message the string message
     * @param array{field?:string|null,value?:string|null} $context  field,value assosiative array
     * @param int $code       response code
     * @param ?Throwable|null $previous
     */
    public function __construct(
        string $message = 'Email Already Registered',
        array $context = [], 
        int $code = 0, 
        Throwable|null $previous = null
    
    ){
        parent::__construct(
            $message,
            $context,
            $code,
            $previous
        );
    }
}


?>
