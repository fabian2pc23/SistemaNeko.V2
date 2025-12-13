<?php

declare(strict_types=1);

use App\Core\Router;
use App\Core\Database;

session_start();

// Simple PSR-4 style autoloader for App\
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/helpers.php';

$router = new Router();

// Load routes definition
require __DIR__ . '/../config/routes.php';

// Remove query string from URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Adjust URI in case the project is in a subfolder
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && str_starts_with($uri, $scriptName)) {
    $uri = substr($uri, strlen($scriptName));
}

$router->dispatch($uri ?: '/', $_SERVER['REQUEST_METHOD']);
