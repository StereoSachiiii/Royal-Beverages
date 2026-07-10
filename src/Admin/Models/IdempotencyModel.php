<?php
declare(strict_types=1);

namespace App\Admin\Models;

class IdempotencyModel
{
    public function __construct(
        public string $key,
        public string $endpoint,
        public string $responseBody,
        public int $statusCode,
        public ?string $createdAt = null
    ) {}
}
