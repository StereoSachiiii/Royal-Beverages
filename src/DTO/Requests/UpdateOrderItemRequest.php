<?php
declare(strict_types=1);

namespace App\DTO\Requests;

use App\DTO\BaseDTO;
use App\DTO\DTOException;
use App\Validation\Validator;

/**
 * Validated request DTO for updating an order item.
 * Replaces OrderItemValidator::validateUpdate().
 */
class UpdateOrderItemRequest extends BaseDTO
{
    public ?int $quantity = null;
    public ?int $warehouse_id = null;

    public static function fromArray(array $data): static
    {
        $validator = new Validator($data);

        if (isset($data['quantity'])) {
            $validator->field('quantity', 'integer|min:1|max:10000');
        }
        // warehouse_id is nullable for unassign, we only validate > 0 if it is not null
        if (isset($data['warehouse_id']) && $data['warehouse_id'] !== '') {
            $validator->field('warehouse_id', 'integer|min:1');
        }

        if (!$validator->validate()) {
            throw new DTOException('Validation failed', $validator->errors());
        }

        return parent::fromArray($data);
    }

    public function toChangeset(): array
    {
        $changeset = [];
        // We only return fields that are explicitly set in the request 
        // to handle null unassignment properly.
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isInitialized($this)) {
                $changeset[$property->getName()] = $property->getValue($this);
            }
        }
        return $changeset;
    }
}
