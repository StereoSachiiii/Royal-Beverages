<?php
declare(strict_types=1);

namespace App\Admin\Exceptions;

use Throwable;

class NotFoundException extends BaseException{

      protected int $statusCode = 404;
    

    /**
     * Summary of __construct
     * @param string $message response message
     * @param array{field?:string|null,value?:string|null} $context context/ fields and values
     * @param int $code    response code
     * @param Throwable|null $previous previous 
     */
    public function __construct(
        string $message = 'NotFoundException: resource not found',
        array $context = [], 
        int $code = 0, 
        Throwable|null $previous = null
        ){
        parent::__construct(
            $message,
            $context,
            $code,
            $previous ?? null
        );
    }











}
















?>
