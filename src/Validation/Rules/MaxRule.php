<?php
declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

class MaxRule implements RuleInterface
{
    private float $max;

    public function __construct(float|string|int $max)
    {
        $this->max = (float)$max;
    }

    public function passes(mixed $value, array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_numeric($value)) {
            return $value <= $this->max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $this->max;
        }

        if (is_array($value)) {
            return count($value) <= $this->max;
        }

        return false;
    }

    public function message(string $field): string
    {
        return "The {$field} field may not be greater than {$this->max}.";
    }
}
