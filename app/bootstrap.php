<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Forum\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once dirname(__DIR__) . '/includes/formatting.php';

try {
    $bdb = Forum\Infrastructure\Database::connection();
} catch (RuntimeException $exception) {
    error_log('Forum database connection failed: ' . $exception->getPrevious()->getMessage());
    http_response_code(503);
    exit('Le forum est temporairement indisponible. Réessayez plus tard.');
}
