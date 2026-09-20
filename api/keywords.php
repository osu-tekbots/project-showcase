<?php
/**
 * This page handles client requests to modify or remove project keywords. All requests made to this page should
 * be a POST request with a corresponding `action` field in the request body.
 */
include_once '../bootstrap.php';

use Api\Response;
use Api\KeywordsActionHandler;
use DataAccess\KeywordsDao;
use Model\UserType;

if (!session_id()) session_start();

// Setup our data access and handler classes
$keywordsDao = new KeywordsDao($dbConn, $logger);

$handler = new KeywordsActionHandler($keywordsDao, $configManager, $logger);

// Authorize the request
if ($isLoggedIn && $_SESSION['userType'] == UserType::ADMIN) {
    // Handle the request
    $handler->handleRequest();
} else {
    $logger->error("Userid: ". $_SESSION['userID']. " attempted to query keywords api without permissions");
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to log in again?'));
}
?>