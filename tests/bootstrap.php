<?php
declare(strict_types=1);

// 1. Composer Autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 2. Set environment to testing
$_ENV['APP_ENV'] = 'testing';

// 3. Core Bootstrap
require_once __DIR__ . '/../src/Core/bootstrap.php';
