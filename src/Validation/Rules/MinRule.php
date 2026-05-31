<?php
declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

class MinRule implements RuleInterface
{
    private float $min;

    public function __construct(float|string|int $min)
    {
        $this->min = (float)$min;
    }

    public function passes(mixed $value, array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true; // Use Required rule to catch empty values
        }

        if (is_numeric($value)) {
            return $value >= $this->min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $this->min;
        }

        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        return false;
    }

    public function message(string $field): string
    {
        return "The {$field} field must be at least {$this->min}.";
    }
}
