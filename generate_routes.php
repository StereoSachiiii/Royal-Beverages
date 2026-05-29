<?php

$dir = __DIR__ . '/src/Admin/API/Routes/';
$files = glob($dir . '*.php');

$output = "        return [\n";

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    preg_match_all('/\$router->(get|post|put|delete)\(\s*\'([^\']+)\'(.*?)\}\)(?:->middleware\(\[.*?\]\))?;/s', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $httpMethod = strtoupper($match[1]);
        $uri = '/api/v1' . $match[2]; 
        $body = $match[3];
        
        preg_match('/(?:get|instance)\(([A-Za-z0-9_]+)::class\)/', $body, $classMatch);
        $controllerClass = $classMatch[1] ?? 'UnknownController';
        
        if ($controllerClass === 'UnknownController') {
             preg_match('/([A-Za-z0-9_]+Controller)::class/', $body, $classMatch2);
             if (isset($classMatch2[1])) {
                 $controllerClass = $classMatch2[1];
             }
        }
        
        // Specifically look for the controller variable method call
        // usually it's $controller->methodName, or $orderController->methodName, $cartController->methodName
        preg_match('/\$[a-zA-Z0-9]*[cC]ontroller\s*->\s*([a-zA-Z0-9_]+)\s*\(/', $body, $methodMatch);
        
        if (!isset($methodMatch[1])) {
             if (strpos($body, 'Session::getInstance()->logout()') !== false) {
                 continue; 
             }
             if (strpos($body, 'session_status') !== false || strpos($body, 'Session::getInstance') !== false) {
                 continue;
             }
             $controllerMethod = 'unknown';
        } else {
             $controllerMethod = $methodMatch[1];
        }
        
        if ($controllerClass !== 'UnknownController' && $controllerMethod !== 'unknown') {
            $output .= sprintf("            ['%s', '%s', \App\Admin\Controllers\%s::class, '%s'],\n", 
                $httpMethod, $uri, $controllerClass, $controllerMethod);
        }
    }
}

$output .= "        ];\n";

file_put_contents('test_generator.txt', $output);
echo "Done! Generated test_generator.txt\n";
