<?php
/**
 * This file handles downloading artifacts for showcase projects. 
 * 
 * It allows for access control on images in a private directory
 * rather than under the web root. It also allows us to fetch everything using the users ID and then rename the
 * file as appropriate.
 */

include_once '../bootstrap.php';

use DataAccess\ShowcaseProjectsDao;


// Make sure we have an ID of the image to fetch
$artifactId = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : false;
if (!$artifactId) {
    header('X-PHP-Response-Code: 400', true, 400);
    die();
}

// Make sure the file exists
$filepath = 
    $configManager->getPrivateFilesDirectory() . '/' .
    $configManager->get('server.upload_artifact_file_path') .
    "/$artifactId";
if (!file_exists($filepath)) {
    header('X-PHP-Response-Code: 404', true, 404);
    die();
}

// Fetch the user information
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$artifact = $projectsDao->getProjectArtifact($artifactId);
if (!$artifact) {
    header('X-PHP-Response-Code: 500', true, 500);
    die();
}

// Construct a name for the file

if ($artifact->getExtension() != '')
	$filename = $artifact->getName() . '.' . $artifact->getExtension();
else {
	$mime = mime_content_type($filepath);
	$mimeParts = explode('/', $mime);
	$filename = $artifact->getName() . '.' . $mimeParts[1];
	header("Content-Type: $mime");
}
header("Content-Disposition: attachment; filename=$filename");
$bytes = readfile($filepath);
if (!$bytes) {
    header('X-PHP-Response-Code: 500', true, 500);
    die();
}
