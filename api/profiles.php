<?php
/**
 * This is the endpoint for API requests on showcase profile resources. The requests are handled inside the
 * `ProfileActionHandler`, but the handler and required DAOs are initialized in this file.
 */
include_once '../bootstrap.php';

use DataAccess\ShowcaseProfilesDao;
use DataAccess\UsersDao;
use Api\ProfileActionHandler;
use Api\Response;

if (!isset($_SESSION)) {
    session_start();
}

$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$usersDao = new UsersDao($dbConn, $logger);
$handler = new ProfileActionHandler($profilesDao, $usersDao, $logger);

if ($isLoggedIn) {
    $handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource'));
}
