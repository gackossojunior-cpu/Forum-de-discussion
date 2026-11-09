<?php
if (!defined('FORUM_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: 3306,
    'database' => getenv('DB_NAME') ?: 'forum',
    'username' => getenv('DB_USER') ?: 'forum_app',
    'password' => getenv('DB_PASSWORD') ?: 'CHANGE_ME',
];
