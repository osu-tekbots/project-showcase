<?php
/**
 * This file handles downloading resumes. It allows for access control on images in a private directory
 * rather than under the web root. It also allows us to fetch everything using the users ID and then rename the
 * file as appropriate.
 */
include_once '../bootstrap.php';

use DataAccess\ShowcaseProfilesDao;

// Make sure we have an ID of the image to fetch
$userId = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : false;
if(!$userId) {
    header("X-PHP-Response-Code: 400", true, 400);
    die();
}

// Make sure the file exists
$filepath = 
    $configManager->getPrivateFilesDirectory() . "/" .
    $configManager->get('server.upload_resume_file_path') .
    "/$userId";
if(!file_exists($filepath)) {
    header("X-PHP-Response-Code: 404", true, 404);
    die();
}

// Fetch the user information
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$profile = $profilesDao->getUserProfileInformation($userId);
if(!$profile) {
    header("X-PHP-Response-Code: 500", true, 500);
    die();
}

// Construct a name for the file
$mime = mime_content_type($filepath);
$mimeParts = explode('/', $mime);
$filename = 
    strtolower($profile->getUser()->getFirstName()) . '-' .
    strtolower($profile->getUser()->getLastName()) . '-' .
    'resume.' .
    $mimeParts[1];

// Send the file contents
header("Content-Type: $mime");
header("Content-Disposition: attachment; filename=$filename");
$bytes = readfile($filepath);
if(!$bytes) {
    header("X-PHP-Response-Code: 500", true, 500);
    die();
}
