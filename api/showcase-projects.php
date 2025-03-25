<?php
/**
 * This is the endpoint for API requests on showcase project resources. The requests are handled inside the
 * `ShowcaseProjectActionHandler`, but the handler, mailer, and required DAOs are initialized in this file.
 */
include_once '../bootstrap.php';

use Api\Response;
use DataAccess\ShowcaseProjectsDao;
use Api\ShowcaseProjectsActionHandler;
use DataAccess\UsersDao;
use Email\CollaborationMailer;
use DataAccess\KeywordsDao;
use DataAccess\AwardDao;
use DataAccess\CategoryDao;

if (!isset($_SESSION)) {
    session_start();
}

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$usersDao = new UsersDao($dbConn, $logger);
$keywordsDao = new KeywordsDao($dbConn, $logger);
$awardDao = new AwardDao($dbConn, $logger);
$categoryDao = new CategoryDao($dbConn, $logger);
$mailer = new CollaborationMailer(
    $configManager->get('email.subject_tag'), 
    $configManager->get('email.from_address'), 
    $logger, 
    $configManager
);
$handler = new ShowcaseProjectsActionHandler($dbConn, $projectsDao, $usersDao, $keywordsDao, $awardDao, $categoryDao, $mailer, $logger);

if ($handler->getAction() == 'browseProjects') {
	$handler->handleRequest();
} else if ($isLoggedIn) {
    $handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource'));
}
