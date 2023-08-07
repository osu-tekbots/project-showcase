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
    $logger = new Util\Logger($configManager->getLogFilePath(), $configManager->getLogLevel());
} catch (\Exception $e) {
    $logger = null;
}

$isLoggedIn = isset($_SESSION['userID']) && !empty($_SESSION['userID']);

/*

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

print_r($_SESSION);

if ($isLoggedIn){ //patch to deal with logging via other tools
	if (!isset($_SESSION['userType'])){
		$usersDao = new UsersDao($dbConn, $logger);
		$user = $usersDao->getUser($_SESSION['userID']);
		
		$_SESSION['userType'] = $user->getType();
	}
}
*/

