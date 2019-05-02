<?php
use Api\Response;
use DataAccess\ShowcaseProjectsDao;
use Api\ShowcaseProjectsActionHandler;

if (!isset($_SESSION)) {
    session_start();
}

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$handler = new ShowcaseProjectsActionHandler($projectsDao, $logger);

if ($isLoggedIn) {
    $handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource'));
}
