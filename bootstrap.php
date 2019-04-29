<?php

define('PUBLIC_FILES', __DIR__);

include PUBLIC_FILES . '/lib/shared/autoload.php';

// Load configuration
$configManager = new Util\ConfigManager(PUBLIC_FILES . '/config' );

$dbConn = DataAccess\DatabaseConnection::FromConfig($configManager->getDatabaseConfig());

try {
    $logger = new Util\Logger($configManager->getLogFilePath(), $configManager->getLogLevel());
} catch (\Exception $e) {
    $logger = null;
}
