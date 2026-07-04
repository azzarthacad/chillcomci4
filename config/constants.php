<?php
// Site Configuration
define('SITE_NAME', 'ChillCom - Minecraft Community');
define('SITE_URL', 'http://localhost/chillcom/');

// Paths
define('BASE_PATH', dirname(dirname(__FILE__)) . '/');
define('ASSETS_PATH', SITE_URL . 'assets/');
define('UPLOADS_PATH', BASE_PATH . 'uploads/');

// Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_name('chillcom_session');
    session_start();
}
?>