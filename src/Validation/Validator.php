<?php
declare(strict_types=1);

namespace App\Validation;

use App\Validation\Rules\RequiredRule;
use App\Validation\Rules\MinRule;
use App\Validation\Rules\MaxRule;
use App\Validation\Rules\EmailRule;
use App\Validation\Rules\IntegerRule;
use App\Validation\Rules\StringRule;
use App\Validation\Rules\BooleanRule;
use App\Validation\Rules\InRule;
use App\Validation\Rules\MinLengthRule;
use App\Validation\Rules\MaxLengthRule;
use App\Validation\Rules\UrlRule;
use App\Validation\Rules\RegexRule;
use Exception;

/**
 * Validation Engine
 */
class Validator
{
    private array $data;
    private array $rules = [];
    private array $errors = [];

    /**
     * Map of rule names to class names
     */
    private array $ruleMap = [
        'required'   => RequiredRule::class,
        'min'        => MinRule::class,
        'max'        => MaxRule::class,
        'email'      => EmailRule::class,
        'integer'    => IntegerRule::class,
        'string'     => StringRule::class,
        'boolean'    => BooleanRule::class,
        'in'         => InRule::class,
        'min_length' => MinLengthRule::class,
        'max_length' => MaxLengthRule::class,
        'url'        => UrlRule::class,
        'regex'      => RegexRule::class,
    ];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Add rules for a field
     *
     * @param string $field
     * @param string|array|RuleInterface $rules
     * @return $this
     */
    public function field(string $field, $rules): self
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        if (!is_array($rules)) {
            $rules = [$rules];
        }

        foreach ($rules as $rule) {
            $this->addRule($field, $rule);
        }

        return $this;
    }

    /**
     * Execute validation
     *
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if (!$rule->passes($value, $this->data)) {
                    $this->errors[$field][] = $rule->message($field);
                    
                    // If it's a required rule and it fails, stop validating this field
                    if ($rule instanceof RequiredRule) {
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Internal helper to add a rule instance
     */
    private function addRule(string $field, string|RuleInterface $rule): void
    {
        if ($rule instanceof RuleInterface) {
            $this->rules[$field][] = $rule;
            return;
        }

        $params = [];
        if (str_contains($rule, ':')) {
            // Only split on the FIRST colon to preserve patterns like /^\+?[0-9]{8,15}$/
            $colonPos = strpos($rule, ':');
            if ($colonPos === false) {
                $colonPos = 0; // Should never happen because of str_contains
            }
            $ruleName   = substr($rule, 0, $colonPos);
            $paramString = substr($rule, $colonPos + 1);

            // For 'regex', the entire paramString is the pattern — don't split by comma
            if ($ruleName === 'regex') {
                $params = [$paramString];
            } else {
                $params = explode(',', $paramString);
            }
        } else {
            $ruleName = $rule;
        }

        if (!isset($this->ruleMap[$ruleName])) {
            throw new Exception("Validation rule '{$ruleName}' not found.");
        }

        $className = $this->ruleMap[$ruleName];
        $this->rules[$field][] = new $className(...$params);
    }
}
