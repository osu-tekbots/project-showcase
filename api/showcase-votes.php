<?php
/**
 * This is the endpoint for API requests on showcase project resources. The requests are handled inside the
 * `ShowcaseProjectActionHandler`, but the handler, mailer, and required DAOs are initialized in this file.
 */
include_once '../bootstrap.php';

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

use Api\Response;
use DataAccess\ShowcaseProjectsDao;
use Api\VoteActionHandler;
use DataAccess\UsersDao;
use Email\CollaborationMailer;
use DataAccess\VoteDao;
use DataAccess\AwardDao;

if (!isset($_SESSION)) {
    session_start();
}

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$usersDao = new UsersDao($dbConn, $logger);
$voteDao = new VoteDao($dbConn, $logger);
$awardDao = new AwardDao($dbConn, $logger);


$handler = new VoteActionHandler($projectsDao, $usersDao, $voteDao, $awardDao, $logger);

if ($isLoggedIn) {
    $handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource'));
}
