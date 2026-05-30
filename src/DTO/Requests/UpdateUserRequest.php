<?php
declare(strict_types=1);

namespace App\DTO\Requests;

use App\DTO\BaseDTO;
use App\DTO\DTOException;
use App\Validation\Validator;

/**
 * Validated request DTO for updating a user profile.
 * All fields optional (PATCH semantics).
 * Replaces UserValidator::validateUpdate().
 * Note: UserModel uses camelCase but request body uses snake_case/camelCase mix — 
 * we accept both profileImageUrl and profile_image_url for frontend compatibility.
 */
class UpdateUserRequest extends BaseDTO
{
    public ?string $name              = null;
    public ?string $email             = null;
    public ?string $phone             = null;
    public ?string $password          = null;
    public ?string $profileImageUrl   = null;  // camelCase — matches frontend
    public ?string $profile_image_url = null;  // snake_case — also accepted
    public ?bool   $is_active         = null;
    public ?bool   $is_admin          = null;

    public static function fromArray(array $data): static
    {
        $validator = new Validator($data);

        if (isset($data['name'])) {
            $validator->field('name', 'min_length:2|max_length:100');
        }
        if (isset($data['email'])) {
            $validator->field('email', 'email');
        }
        if (isset($data['phone']) && $data['phone'] !== '') {
            $validator->field('phone', 'regex:/^\+?[0-9]{8,15}$/');
        }
        if (isset($data['password'])) {
            $validator->field('password', 'min_length:8');
        }
        if (isset($data['profileImageUrl'])) {
            $validator->field('profileImageUrl', 'max_length:500');
        }
        if (isset($data['profile_image_url'])) {
            $validator->field('profile_image_url', 'max_length:500');
        }
        if (isset($data['is_active'])) {
            $validator->field('is_active', 'boolean');
        }
        if (isset($data['is_admin'])) {
            $validator->field('is_admin', 'boolean');
        }

        if (!$validator->validate()) {
            throw new DTOException('Validation failed', $validator->errors());
        }

        return parent::fromArray($data);
    }

    public function toChangeset(): array
    {
        return array_filter($this->toArray(), fn($v) => $v !== '');
    }
}
