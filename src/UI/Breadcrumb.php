<?php
declare(strict_types=1);

namespace App\UI;

/**
 * OOP Model to manage Breadcrumbs fluently.
 */
class Breadcrumb
{
    private array $items = [];

    /**
     * Add a breadcrumb node.
     * 
     * @param string $label The visual text of the crumb.
     * @param string $url The destination URL (empty for active/non-link node).
     * @return self
     */
    public function add(string $label, string $url = ''): self
    {
        $this->items[] = [
            'label' => $label,
            'url' => $url
        ];
        return $this;
    }

    /**
     * Get all breadcrumb items.
     * 
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
