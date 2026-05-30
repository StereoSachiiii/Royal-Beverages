<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/bootstrap.php';

use App\Core\Database;

/**
 * Performance Benchmark: Time a product search with 10k items
 */

echo "Starting performance benchmark...\n";
$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    // 1. Create a dummy category and supplier
    $db->exec("INSERT INTO categories (name, slug) VALUES ('Benchmark Category', 'benchmark-cat') ON CONFLICT DO NOTHING");
    $db->exec("INSERT INTO suppliers (name, contact_email) VALUES ('Benchmark Supplier', 'bench@example.com') ON CONFLICT DO NOTHING");

    $catId = $db->query("SELECT id FROM categories WHERE slug = 'benchmark-cat'")->fetchColumn();
    $supId = $db->query("SELECT id FROM suppliers WHERE contact_email = 'bench@example.com'")->fetchColumn();

    // 2. Insert 10k dummy products
    echo "Inserting 10,000 dummy products (this might take a moment)...\n";
    $stmt = $db->prepare("
        INSERT INTO products (name, slug, description, price_cents, category_id, supplier_id, is_active)
        VALUES (:name, :slug, :description, :price, :cat, :sup, TRUE)
    ");

    for ($i = 1; $i <= 10000; $i++) {
        $stmt->execute([
            ':name' => "BenchProduct #{$i}",
            ':slug' => "bench-product-{$i}",
            ':description' => "This is a dummy description for product {$i} used in benchmarking search performance.",
            ':price' => rand(1000, 50000),
            ':cat' => $catId,
            ':sup' => $supId
        ]);
    }
    echo "10,000 products inserted.\n";

    // 3. Perform a text search and time it
    echo "Running search query...\n";
    
    $container = new \App\DIContainer\Container();
    $repo = $container->get(\App\Admin\Repositories\ProductRepository::class);

    $start = microtime(true);
    // Searching for something that will match a lot of records
    $results = $repo->searchEnriched('bench-product-', 50, 0);
    $end = microtime(true);

    $timeTaken = ($end - $start) * 1000; // in milliseconds
    
    echo "Search returned " . count($results) . " results.\n";
    echo "Time taken for searchEnriched with 10k+ rows: " . number_format($timeTaken, 2) . " ms\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    // 4. Rollback to leave database clean
    echo "Rolling back benchmark data...\n";
    $db->rollBack();
    echo "Benchmark finished.\n";
}
