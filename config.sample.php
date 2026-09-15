<?php
/**
 * Copy to config.php or let install.php write it.
 * Do not commit real credentials.
 */
if (!defined('TRD_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'trd_hub');
define('DB_USER', 'trd_hub');
define('DB_PASS', 'change-me');
define('DB_CHARSET', 'utf8mb4');

// Optional: set if the app lives in a subdirectory, e.g. '/rider-hub'
// define('APP_BASE_PATH', '');
