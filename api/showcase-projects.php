<?php
use Api\Response;
use DataAccess\ShowcaseProjectsDao;
use Api\ShowcaseProjectsActionHandler;
use DataAccess\UsersDao;
use Email\CollaborationMailer;
use DataAccess\KeywordsDao;

if (!isset($_SESSION)) {
    session_start();
}

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$usersDao = new UsersDao($dbConn, $logger);
$keywordsDao = new KeywordsDao($dbConn, $logger);
$mailer = new CollaborationMailer(
    $configManager->get('email.subject_tag'), 
    $configManager->get('email.from_address'), 
    $logger, 
    $configManager
);
$handler = new ShowcaseProjectsActionHandler($projectsDao, $usersDao, $keywordsDao, $mailer, $logger);

if ($isLoggedIn) {
    $handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource'));
}
