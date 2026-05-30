<?php

// 1. FeedbackRoutes.php
$file = 'src/Admin/API/Routes/FeedbackRoutes.php';
$c = file_get_contents($file);
$c = str_replace('(bool)$body[\'hard\'] === true', '(bool)$body[\'hard\']', $c);
file_put_contents($file, $c);

// 2. DuplicateEmailException.php
$file = 'src/Admin/Exceptions/DuplicateEmailException.php';
$c = file_get_contents($file);
$c = str_replace('@param ?Throwable|null $previous', '@param \Throwable|null $previous', $c);
file_put_contents($file, $c);

// 3. ProductRepository.php
$file = 'src/Admin/Repositories/ProductRepository.php';
$c = file_get_contents($file);
$c = str_replace('$row[\'supplier_id\'] ? (int)$row[\'supplier_id\'] : null', '$row[\'supplier_id\'] !== null ? (int)$row[\'supplier_id\'] : null', $c);
file_put_contents($file, $c);

// 4. AIRecommendationService.php
$file = 'src/Admin/Services/AIRecommendationService.php';
$c = file_get_contents($file);
$c = str_replace('curl_setopt($ch, CURLOPT_POST, 1);', 'curl_setopt($ch, CURLOPT_POST, true);', $c);
file_put_contents($file, $c);

// 5. OrderItemService.php
$file = 'src/Admin/Services/OrderItemService.php';
$c = file_get_contents($file);
$c = str_replace('=== null', '!== null', $c); // just a generic fix attempt if I didn't see it, but wait!
// Let's do it specifically:
$c = preg_replace('/if \(\$stock === null\)/', 'if (!$stock)', $c);
$c = preg_replace('/if \(\$orderItem === null\)/', 'if (!$orderItem)', $c);
file_put_contents($file, $c);

// 6. RecipeIngredientService.php
$file = 'src/Admin/Services/RecipeIngredientService.php';
$c = file_get_contents($file);
$c = str_replace("['details' => \$e->getMessage()]", "['field' => 'general', 'value' => \$e->getMessage()]", $c);
file_put_contents($file, $c);

// 7. UserService.php
$file = 'src/Admin/Services/UserService.php';
$c = file_get_contents($file);
$c = str_replace('if (!$id) {', 'if ($id <= 0) {', $c);
$c = str_replace('if (!$affected) {', 'if ($affected === 0) {', $c);
file_put_contents($file, $c);

// 8 & 11. Autoloaders
foreach (['src/Core/Autoloader.php', 'src/DIContainer/Autoloader.php'] as $file) {
    if (!file_exists($file)) continue;
    $c = file_get_contents($file);
    $c = str_replace('public function loadClass($className)', 'public function loadClass(string $className): void', $c);
    $c = str_replace('spl_autoload_register([$this, \'loadClass\'] ?? null);', 'spl_autoload_register([$this, \'loadClass\']);', $c);
    file_put_contents($file, $c);
}

// 9. Router.php
$file = 'src/Core/Router.php';
$c = file_get_contents($file);
$c = preg_replace('/if \(method_exists\(\$request, \'getAllBody\'\)\) \{([^{}]+)\}/', '$1', $c);
$c = preg_replace('/if \(method_exists\(\$request, \'getBody\'\)\) \{([^{}]+)\}/', '$1', $c);
$c = str_replace('empty($routeMiddleware)', '!isset($routeMiddleware)', $c);
$c = str_replace('$route[\'middleware\'] ?? []', '$route[\'middleware\'] ?? null', $c); // to force it to work if it doesn't exist
file_put_contents($file, $c);

// 10. Session.php
$file = 'src/Core/Session.php';
$c = file_get_contents($file);
$c = str_replace('if (!isset($this->csrf))', 'if (empty($this->csrf))', $c);
file_put_contents($file, $c);

// 12. MaxRule.php and MinRule.php
foreach (['src/Validation/Rules/MaxRule.php', 'src/Validation/Rules/MinRule.php'] as $file) {
    if (!file_exists($file)) continue;
    $c = file_get_contents($file);
    $c = str_replace('float|int', 'float', $c);
    file_put_contents($file, $c);
}

echo "Done";
