<?php
declare(strict_types=1);

namespace App\Admin\Models;

class RecipeIngredientModel
{
    public function __construct(
        public readonly int $id,
        public readonly int $recipeId,
        public readonly int $productId,
        public readonly float $quantity,
        public readonly string $unit,
        public readonly bool $isOptional,
        public readonly string $createdAt,
        // Optional fields from JOIN queries
        public readonly ?string $productName = null,
        public readonly ?int $productPriceCents = null,
        public readonly ?string $productImageUrl = null,
        public readonly ?bool $productIsActive = null,
        public readonly ?string $recipeName = null,
        public readonly ?string $recipeDifficulty = null,
        public readonly ?bool $recipeIsActive = null,
        public readonly ?int $availableStock = null
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'recipe_id' => $this->recipeId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'is_optional' => $this->isOptional,
            'created_at' => $this->createdAt,
        ];

        // Include optional fields if present
        if ($this->productName !== null) {
            $data['product_name'] = $this->productName;
        }
        if ($this->productPriceCents !== null) {
            $data['product_price_cents'] = $this->productPriceCents;
            $data['product_price'] = $this->formatPrice($this->productPriceCents);
        }
        if ($this->productImageUrl !== null) {
            $data['product_image_url'] = $this->productImageUrl;
        }
        if ($this->productIsActive !== null) {
            $data['product_is_active'] = $this->productIsActive;
        }
        if ($this->recipeName !== null) {
            $data['recipe_name'] = $this->recipeName;
        }
        if ($this->recipeDifficulty !== null) {
            $data['recipe_difficulty'] = $this->recipeDifficulty;
        }
        if ($this->recipeIsActive !== null) {
            $data['recipe_is_active'] = $this->recipeIsActive;
        }
        if ($this->availableStock !== null) {
            $data['available_stock'] = $this->availableStock;
        }

        return $data;
    }

    /**
     * Calculate cost for this ingredient
     */
    public function calculateCost(): ?float
    {
        if ($this->productPriceCents === null) {
            return null;
        }
        return ($this->productPriceCents * $this->quantity) / 100;
    }

    /**
     * Get formatted display string
     */
    public function getDisplayString(): string
    {
        $str = "{$this->quantity} {$this->unit}";
        if ($this->productName !== null) {
            $str .= " {$this->productName}";
        }
        if ($this->isOptional) {
            $str .= " (optional)";
        }
        return $str;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): ?bool
    {
        if ($this->availableStock === null) {
            return null;
        }
        return $this->availableStock > 0;
    }

    /**
     * Check if sufficient stock for this ingredient
     */
    public function hasSufficientStock(): ?bool
    {
        if ($this->availableStock === null) {
            return null;
        }
        // Assuming quantity represents units needed
        return $this->availableStock >= $this->quantity;
    }

    /**
     * Format price in cents to currency string
     */
    private function formatPrice(int $cents): string
    {
        return number_format($cents / 100, 2);
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return (string)json_encode($this->toArray());
    }
}
