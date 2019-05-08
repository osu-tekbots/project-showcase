<?php
use Api\Response;
use DataAccess\ShowcaseProjectsDao;
use Api\ShowcaseProjectsActionHandler;
use DataAccess\UsersDao;
use Email\CollaborationMailer;

if (!isset($_SESSION)) {
    session_start();
}

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$usersDao = new UsersDao($dbConn, $logger);
$mailer = new CollaborationMailer(
    $configManager->getEmailFromAddress(), 
    $configManager->getEmailSubjectTag(), 
    $logger, 
    $configManager
);
$handler = new ShowcaseProjectsActionHandler($projectsDao, $usersDao, $mailer, $logger);

if ($isLoggedIn) {
    $handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource'));
}
