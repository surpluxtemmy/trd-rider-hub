<?php
/**
 * Router for PHP built-in server:
 * php -S 0.0.0.0:8080 router.php
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'js' && (str_ends_with($uri, '/sw.js') || $uri === '/sw.js')) {
        header('Content-Type: application/javascript; charset=UTF-8');
        header('Service-Worker-Allowed: /');
        header('Cache-Control: no-cache');
        readfile($file);
        return true;
    }
    return false;
}

if ($uri === '/install.php' || str_ends_with($uri, '/install.php')) {
    require __DIR__ . '/install.php';
    return true;
}

require __DIR__ . '/index.php';
