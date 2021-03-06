<?php
/**
 * This page contains bootstraping logic required on all pages in the project showcase. It is automatically included
 * in the `.htaccess` file at the root of the website using the `php_value auto_prepend_file` setting.
 */

session_start();

define('PUBLIC_FILES', __DIR__);

include PUBLIC_FILES . '/lib/shared/autoload.php';

// Load configuration
$configManager = new Util\ConfigManager(__DIR__);

$dbConn = DataAccess\DatabaseConnection::FromConfig($configManager->getDatabaseConfig());

try {
    $logger = new Util\Logger($configManager->getLogFilePath(), $configManager->getLogLevel());
} catch (\Exception $e) {
    $logger = null;
}

$isLoggedIn = isset($_SESSION['userID']) && !empty($_SESSION['userID']);
