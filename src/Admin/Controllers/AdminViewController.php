<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminViewService;

class AdminViewController extends BaseController
{
    public function __construct(
        private AdminViewService $service,
    ) {}

    public function getDashboardStats(): array
    {
        $stats = $this->service->getDashboardStats();
        return $this->success('Dashboard stats loaded successfully', $stats);
    }



    /**
     * Generic detail view endpoint used by admin-views.php
     */
    public function getDetail(string $entity, int $id): array
    {
        $detail = $this->service->getDetail($entity, $id);
        return $this->success(ucfirst($entity) . ' detail loaded successfully', $detail);
    }

    /**
     * Generic list view endpoint used by admin-views.php
     */
    public function getList(string $entity, int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $list = $this->service->getList($entity, $limit, $offset, $search);
        return $this->success(ucfirst($entity) . ' list loaded successfully', $list);
    }
}
