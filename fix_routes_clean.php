<?php
$files = glob('src/Admin/API/Routes/*.php');
foreach ($files as $file) {
    $c = file_get_contents($file);
    
    // regex to strip out the old guards and docblocks
    $c = preg_replace('#/\*\* @var \\\\?App\\\\Core\\\\Router \$router \*/\s*#', '', $c);
    $c = preg_replace('#// Guard against direct access - must be loaded via router\s*if \(!isset\(\$router\)(?: \|\| !\$router instanceof Router)?\) \{\s*(?:header[^;]+;)?\s*(?:http_response_code[^;]+;)?\s*(?:echo[^;]+;)?\s*exit;\s*\}#s', '', $c);
    $c = preg_replace('#if \(!isset\(\$router\)(?: \|\| !\$router instanceof Router)?\) \{\s*(?:header[^;]+;)?\s*(?:http_response_code[^;]+;)?\s*(?:echo[^;]+;)?\s*exit;\s*\}#s', '', $c);
    
    // add a clean guard at the top
    $guard = "if (!defined('ROOT_PATH')) {\n    http_response_code(400);\n    exit;\n}\n\n/** @var \App\Core\Router \$router */\n";
    $c = preg_replace('#(use App\\\\Core\\\\Router;)#', "$1\n\n$guard", $c);
    
    file_put_contents($file, $c);
}
echo "Done";
