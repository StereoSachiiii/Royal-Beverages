<?php
declare(strict_types=1);

namespace App\Admin\Models;

class JobModel
{
    public function __construct(
        public int $id,
        public string $jobType,
        public array $payload,
        public string $status,
        public int $attempts,
        public string $runAt,
        public string $createdAt
    ) {}
}
