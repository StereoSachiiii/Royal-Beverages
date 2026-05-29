<?php
declare(strict_types=1);

/**
 * Royal Beverages Core Bootstrap
 * Initializes autoloader, session, and basic configurations
 */

// 1. Define Root Path (Required for reliable includes)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/../..'));
}

// 2. Load main configuration IMMEDIATELY
// This ensures that any subsequent class initialization (like Database) has config access.
$config = require_once ROOT_PATH . "/config/config.php";
$GLOBALS['app_config'] = $config;

// 3. Initialize Autoloader
require_once __DIR__ . "/Autoloader.php";
$autoloader = new \App\Core\Autoloader();
$autoloader->addNamespace('App', realpath(__DIR__ . '/..'));
$autoloader->register();

// 4. Initialize Session
require_once __DIR__ . "/Session.php";
$session = \App\Core\Session::getInstance();

// 5. Define Global Constants
if (!defined('DEBUG')) define('DEBUG', $config['app']['debug'] ?? $config['debug'] ?? true);

// 6. Web Root & Base URL Detection
if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $publicPath = str_replace('\\', '/', realpath(__DIR__ . '/../../public') ?: '');
    
    if ($docRoot && $publicPath && str_starts_with($publicPath, $docRoot)) {
        $base = '/' . trim(substr($publicPath, strlen($docRoot)), '/') . '/';
    } else {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (str_contains($scriptName, '/public/')) {
            $base = substr($scriptName, 0, strpos($scriptName, '/public/') + 8);
        } else {
            $base = '/';
        }
    }
    
    $base = '/' . trim($base, '/') . '/';
    $base = str_replace('//', '/', $base);
    if ($base === '//') $base = '/';
    
    define('BASE_URL', $base);
}

if (!defined('WEB_ROOT'))      define('WEB_ROOT', BASE_URL);
if (!defined('APP_BASE_URL'))  define('APP_BASE_URL', BASE_URL);
if (!defined('API_BASE_URL'))  define('API_BASE_URL', rtrim(BASE_URL, '/') . '/api/v1');

// 8. Path Utility Constants
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

return [
    'autoloader'   => $autoloader,
    'session'      => $session,
    'config'       => $config,
    'web_root'     => WEB_ROOT,
    'base_url'     => BASE_URL,
    'api_base_url' => API_BASE_URL
];
