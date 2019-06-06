<?php
/**
 * This file handles downloading profile images. It allows for access control on images in a private directory
 * rather than under the web root. It also allows us to fetch everything using the users ID and then rename the
 * file as appropriate.
 */
include_once '../bootstrap.php';

use DataAccess\ShowcaseProfilesDao;

// Make sure we have an ID of the image to fetch
$userId = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : false;
if(!$userId) {
    $logger->warn('User ID not provided when fetching profile image');
    header("X-PHP-Response-Code: 400", true, 400);
    die();
}

// Make sure the file exists
$filepath = 
    $configManager->getPrivateFilesDirectory() . "/" .
    $configManager->get('server.upload_profile_image_file_path') .
    "/$userId";
if(!file_exists($filepath)) {
    $logger->warn('Error fetching file for profile image: does not exist: ' . $filepath);
    header("X-PHP-Response-Code: 404", true, 404);
    die();
}

// Fetch the user information
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$profile = $profilesDao->getUserProfileInformation($userId);
if(!$profile) {
    $logger->warn('Failed to fetch user profile information when fetching profile image for user ' . $userId);
    header("X-PHP-Response-Code: 500", true, 500);
    die();
}

// Construct a name for the file
$mime = mime_content_type($filepath);
$mimeParts = explode('/', $mime);
$filename = 
    strtolower($profile->getUser()->getFirstName()) . '-' .
    strtolower($profile->getUser()->getLastName()) . '.' .
    $mimeParts[1];

// Send the file contents
header("Content-Type: $mime");
header("Content-Disposition: filename=$filename");
$bytes = readfile($filepath);
if(!$bytes) {
    $logger->warn("An error occurred while trying to read the profile image file ($filename) for user $userId");
    header("X-PHP-Response-Code: 500", true, 500);
    die();
}
