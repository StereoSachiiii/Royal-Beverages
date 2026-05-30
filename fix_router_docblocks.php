<?php
$files = glob('src/Admin/API/Routes/*.php');
foreach ($files as $file) {
    $c = file_get_contents($file);
    if (!str_contains($c, '/** @var Router $router */')) {
        $c = str_replace('use App\Core\Router;', "use App\Core\Router;\n\n/** @var Router \$router */", $c);
        file_put_contents($file, $c);
    }
}
echo "Done";
