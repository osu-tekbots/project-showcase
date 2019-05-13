<?php
use DataAccess\ShowcaseProjectsDao;

/**
 * This file handles downloading project images. It allows for access control on images in a private directory
 * rather than under the web root. It also allows us to fetch everything using the users ID and then rename the
 * file as appropriate.
 */


// Make sure we have an ID of the image to fetch
$imageId = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : false;
if(!$imageId) {
    $logger->warn('Image ID not provided when fetching project image');
    header("X-PHP-Response-Code: 400", true, 400);
    die();
}

// Make sure the file exists
$filepath = 
    $configManager->getPrivateFilesDirectory() . "/" .
    $configManager->get('server.upload_project_image_file_path') .
    "/$imageId";
if(!file_exists($filepath)) {
    $logger->warn('Error fetching file for profile image: does not exist: ' . $filepath);
    header("X-PHP-Response-Code: 404", true, 404);
    die();
}

// Fetch the user information
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$image = $projectsDao->getProjectImage($imageId);
if(!$image) {
    $logger->warn('Failed to fetch project image metadata when fetching image for project');
    header("X-PHP-Response-Code: 500", true, 500);
    die();
}

// Construct a name for the file
$mime = mime_content_type($filepath);
$filename = $image->getFileName();

// Send the file contents
header("Content-Type: $mime");
header("Content-Disposition: filename=$filename");
$bytes = readfile($filepath);
if(!$bytes) {
    $logger->warn("An error occurred while trying to read the project image file ($filename) $imageId");
    header("X-PHP-Response-Code: 500", true, 500);
    die();
}
