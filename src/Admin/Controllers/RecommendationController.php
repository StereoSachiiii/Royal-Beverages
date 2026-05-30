<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AIRecommendationService;
use App\Core\Session;

class RecommendationController extends BaseController
{
    private AIRecommendationService $service;

    public function __construct(AIRecommendationService $service)
    {
        $this->service = $service;
    }

    public function getForYou(): void
    {
        try {
            $userId = Session::getInstance()->getUserId(); // Can be null for guests
            
            // Limit to 4 cards for the homepage UI row
            $recommendations = $this->service->getRecommendationsForUser($userId, 4);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $recommendations
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function previewMatches(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid payload'], 400);
                return;
            }

            // Provide fallback defaults to 5 if any missing
            $prefs = [
                'sweetness' => isset($input['sweetness']) ? (int)$input['sweetness'] : 5,
                'bitterness' => isset($input['bitterness']) ? (int)$input['bitterness'] : 5,
                'strength' => isset($input['strength']) ? (int)$input['strength'] : 5,
                'smokiness' => isset($input['smokiness']) ? (int)$input['smokiness'] : 5,
                'fruitiness' => isset($input['fruitiness']) ? (int)$input['fruitiness'] : 5,
                'spiciness' => isset($input['spiciness']) ? (int)$input['spiciness'] : 5,
            ];

            $matches = $this->service->getDynamicTasteMatches($prefs);

            $this->jsonResponse([
                'success' => true,
                'data' => $matches
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
