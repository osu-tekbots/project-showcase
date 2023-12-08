<?php
/**
 * This page contains bootstraping logic required on all pages in the project showcase. It is automatically included
 * in the `.htaccess` file at the root of the website using the `php_value auto_prepend_file` setting.
 */

use DataAccess\UsersDao;
use Model\User;

session_start();

define('PUBLIC_FILES', __DIR__);

include PUBLIC_FILES . '/lib/shared/autoload.php';

// Load configuration
$configManager = new Util\ConfigManager(__DIR__);

$dbConn = DataAccess\DatabaseConnection::FromConfig($configManager->getDatabaseConfig());

try {
    $logFileName = $configManager->getLogFilePath() . date('MY') . ".log";
    $logger = new Util\Logger($logFileName, $configManager->getLogLevel());
} catch (\Exception $e) {
    $logger = null;
}

// Set $_SESSION variables to be for this site
include PUBLIC_FILES . '/lib/authenticate.php';

$isLoggedIn = isset($_SESSION['userID']) && !empty($_SESSION['userID']);
