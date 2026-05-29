<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\WishlistService;
use App\DTO\Requests\CreateWishlistItemRequest;
use App\Core\Session;

class WishlistController extends BaseController
{
    private WishlistService $service;

    public function __construct(WishlistService $service)
    {
        $this->service = $service;
    }

    public function getMine(): array
    {
        $userId = Session::getInstance()->getUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Unauthorized', 'code' => 401];
        }

        try {
            $items = $this->service->getWishlist($userId);
            return ['success' => true, 'data' => $items];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 500];
        }
    }

    public function add(array $data = []): array
    {
        $userId = Session::getInstance()->getUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Unauthorized', 'code' => 401];
        }

        try {
            if (empty($data)) {
                $data = $this->getJsonInput();
            }
            $request = new CreateWishlistItemRequest($data);
            
            if ($request->product_id <= 0) {
                return ['success' => false, 'message' => 'Invalid product id', 'code' => 400];
            }

            return $this->service->addItem($userId, $request);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 500];
        }
    }

    public function remove(int $productId): array
    {
        $userId = Session::getInstance()->getUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Unauthorized', 'code' => 401];
        }

        try {
            return $this->service->removeItem($userId, $productId);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 500];
        }
    }

    public function syncBulk(array $data = []): array
    {
        $userId = Session::getInstance()->getUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Unauthorized', 'code' => 401];
        }

        try {
            if (empty($data)) {
                $data = $this->getJsonInput();
            }
            $productIds = $data['product_ids'] ?? [];
            
            if (!is_array($productIds)) {
                return ['success' => false, 'message' => 'product_ids must be an array', 'code' => 400];
            }

            $mergedItems = $this->service->sync($userId, $productIds);
            
            return [
                'success' => true,
                'message' => 'Wishlist synchronized successfully',
                'data' => $mergedItems
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 500];
        }
    }
}
