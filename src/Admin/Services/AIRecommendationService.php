<?php
declare(strict_types=1);

namespace App\Admin\Services;

use App\Core\Database;
use App\Admin\Repositories\ProductRepository;
use Exception;
use PDO;

class AIRecommendationService
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Builds context and queries Gemini.
     */
    public function getRecommendationsForUser(mixed $userId, int $limit = 4): array
    {
        $apiKey = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? null;
        
        // If no API key or no real user context available (guests), return generic bestsellers
        if (!$apiKey || !$userId || !is_numeric($userId)) {
            return $this->getGenericRecommendations($limit);
        }

        try {
            $context = $this->buildUserContext($userId);
            // If they have literally no footprint, return generics
            if (empty($context['wishlist_names']) && empty($context['purchased_names'])) {
                return $this->getGenericRecommendations($limit);
            }

            // Fetch the entire inventory catalog for Gemini to choose from
            $catalog = $this->fetchActiveCatalog();
            
            $prompt = $this->constructPrompt($context, $catalog, $limit);
            $geminiResponse = $this->callGeminiAPI($apiKey, $prompt);

            // Parse Gemini's JSON return exactly
            $recommendedIds = $this->extractProductIdsFromResponse($geminiResponse);

            if (empty($recommendedIds)) {
                return $this->getGenericRecommendations($limit);
            }

            // Fetch those exactly from repository
            return $this->getEnrichedProductsByIds($recommendedIds, $limit);

        } catch (Exception $e) {
            // Silently fallback to generics so the homepage never breaks
            error_log("Gemini AI failed: " . $e->getMessage());
            return $this->getGenericRecommendations($limit);
        }
    }

    private function getGenericRecommendations(int $limit): array
    {
        // Simple fallback: Get the newest or randomly shuffled distinct products
        // Correcting method signature: Passing $limit as the first argument
        return $this->productRepository->shopAllEnriched($limit, 0);
    }

    private function buildUserContext(int $userId): array
    {
        $pdo = Database::getPdo();
        $context = [
            'wishlist_names' => [],
            'purchased_names' => [],
            'preferences' => []
        ];

        // 1. Wishlist Items
        $stmt = $pdo->prepare("SELECT p.name FROM wishlist_items w JOIN products p ON w.product_id = p.id WHERE w.user_id = :uid LIMIT 10");
        $stmt->execute([':uid' => $userId]);
        $context['wishlist_names'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 2. Purchased Items
        $stmt2 = $pdo->prepare("
            SELECT DISTINCT p.name 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.id 
            JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = :uid LIMIT 10
        ");
        $stmt2->execute([':uid' => $userId]);
        $context['purchased_names'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        // 3. True Taste Preferences
        $stmt3 = $pdo->prepare("SELECT preferred_categories, preferred_sweetness, preferred_strength FROM user_preferences WHERE user_id = :uid LIMIT 1");
        $stmt3->execute([':uid' => $userId]);
        if ($pref = $stmt3->fetch(PDO::FETCH_ASSOC)) {
            $context['preferences'] = $pref;
        }

        return $context;
    }

    private function fetchActiveCatalog(): array
    {
        $pdo = Database::getPdo();
        $stmt = $pdo->prepare("SELECT id, name, category_id, price_cents FROM products WHERE is_active = TRUE AND deleted_at IS NULL");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function constructPrompt(array $context, array $catalog, int $limit): string
    {
        $wishlistStr = implode(", ", $context['wishlist_names']);
        $purchasedStr = implode(", ", $context['purchased_names']);
        
        $prefStr = "";
        if (!empty($context['preferences'])) {
            $prefStr = "User preferred categories: " . implode(", ", json_decode($context['preferences']['preferred_categories'] ?? '[]', true)) . ". ";
        }

        $catalogStr = json_encode($catalog);

        return "
You are an expert sommelier and artificial intelligence for Royal Beverages.
You must analyze the user's past data and recommend EXACTLY $limit products from our available catalog.

User Context:
- Past Purchases: $purchasedStr
- Wishlisted Items: $wishlistStr
- System Preferences: $prefStr

Available Catalog (JSON):
$catalogStr

Instructions:
1. Find $limit products from the catalog that closely align with the user's demonstrated tastes.
2. DO NOT recommend products they have already purchased or wishlisted if possible, aim for discovery, but keep within similar flavor profiles.
3. You must respond ONLY with a raw JSON Array of integers representing the Product IDs. Example: [12, 45, 8, 22]. No markdown, no explanation.
";
    }

    private function callGeminiAPI(string $apiKey, string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;
        $data = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Optional timeout bounds
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new Exception("Gemini API Error: HTTP " . $httpCode);
        }

        return $response;
    }

    private function extractProductIdsFromResponse(string $json): array
    {
        $data = json_decode($json, true);
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return [];
        }

        $text = trim($data['candidates'][0]['content']['parts'][0]['text']);
        // Strip markdown backticks if Gemini ignored instruction
        $text = str_replace(['```json', '```'], '', $text);
        $text = trim($text);

        $ids = json_decode($text, true);
        if (is_array($ids)) {
            return array_filter(array_map('intval', $ids));
        }

        return [];
    }

    private function getEnrichedProductsByIds(array $ids, int $limit): array
    {
        // Leverage existing frontend enrichment shopAllEnriched, but filtered
        // We'll execute a direct SQL to safely pull these $limit IDs natively matching the shop interface
        
        if (empty($ids)) return [];
        
        $pdo = Database::getPdo();
        
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        
        $sql = "
            SELECT p.*,
                c.name as category_name, c.slug as category_slug,
                COALESCE((SELECT SUM(quantity) FROM stock WHERE product_id = p.id), 0) as physical_stock,
                COALESCE((
                    SELECT SUM(oi.quantity)
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE oi.product_id = p.id AND o.status IN ('pending', 'processing')
                ), 0) as reserved_stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id IN ($inQuery) AND p.is_active = TRUE AND p.deleted_at IS NULL
            LIMIT " . (int)$limit;
            
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format them similarly to ProductRepository::shopAllEnriched array layout
        $formatted = [];
        foreach ($rows as $row) {
            $formatted[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'category_id' => $row['category_id'],
                'category_name' => $row['category_name'],
                'category_slug' => $row['category_slug'],
                'price_cents' => $row['price_cents'],
                'image_url' => $row['image_url'],
                'stock' => [
                    'available_stock' => max(0, $row['physical_stock'] - $row['reserved_stock'])
                ]
            ];
        }
        
        return $formatted;
    }
}
