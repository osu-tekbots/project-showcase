<?php
/**
 * Handles requests being made on artifact resources in the database. We cannot use the `ActionHandler` class
 * because it only handles JSON requests, and the artifact requests are URL form encoded since they may or
 * may not include a file upload.
 */
include_once '../bootstrap.php';

use DataAccess\UsersDao;
use DataAccess\ShowcaseProjectsDao;
use DataAccess\ShowcaseProfilesDao;
use Api\Response;
use Model\ShowcaseProjectImage;

function correctImageOrientation($filename) {
  if (function_exists('exif_read_data')) {
    $exif = exif_read_data($filename);
    if($exif && isset($exif['Orientation'])) {
      $orientation = $exif['Orientation'];
      if($orientation != 1){
        $img = imagecreatefromjpeg($filename);
        $deg = 0;
        switch ($orientation) {
          case 3:
            $deg = 180;
            break;
          case 6:
            $deg = 270;
            break;
          case 8:
            $deg = 90;
            break;
        }
        return $deg;
      } // if there is some rotation necessary
    } // if have the exif orientation info
  } // if function exists   
  return 0;  
}


if (!isset($_SESSION)) {
    session_start();
}

// Only allow logged in users to access this resource
if (!$isLoggedIn) {
    respond(401, 'You do not have permission to make this request');
}

// Verify the action on the resource
if (!isset($_POST['action'])) {
    respond(400, 'Missing action in request body');
}

// Make sure we have the project ID
$projectId = isset($_POST['projectId']) && !empty($_POST['projectId']) ? $_POST['projectId'] : null;
if (is_null($projectId) || empty($projectId)) {
    respond(400, 'Must include ID of project in request');
}

// Make sure the current user has permission to perform this action (i.e. they are either an admin or a
// collaborator on the project)
// $userId = $_SESSION['userID'];
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$userId = $profilesDao->getUserIdFromOnid($_SESSION['auth']['id']); // TEMPORARY FIX for login issues across eecs sites
$usersDao = new UsersDao($dbConn, $logger);
$user = $usersDao->getUser($userId);

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$isCollaborator = $projectsDao->verifyUserIsCollaboratorOnProject($projectId, $userId);
if ($isCollaborator == null) {
    respond(500, 'Failed to verify if user is collaborator on project');
}
if (!$isCollaborator && $user->getType()->getId() != UserType::ADMIN) {
    respond(401, 'You do not have permission to make this request');
}

//
// The client making the request has passed all access checks. We can now handle the request based on the action.
//

switch ($_POST['action']) {

    case 'addProjectImage':
        handleAddNewProjectImage($projectId, $projectsDao, $configManager, $logger);

    case 'deleteProjectImage':
        handleDeleteProjectImage($projectsDao, $configManager, $logger);
    case 'moveProjectImage':
        handleMoveProjectImage($projectsDao, $configManager, $logger);

    default: 
        respond(400, 'Invalid action on project image resource');
}

/**
 * Simple function that allows us to respond with a response code and a message inside a JSON object.
 *
 * @param int  $code the HTTP status code of the response
 * @param string $message the message to send back to the client
 * @return void
 */
function respond($code, $message, $content = null) {
    $response = new Response($code, $message, $content);
    header('Content-Type: application/json');
    header("X-PHP-Response-Code: $code", true, $code);
    echo $response->serialize();
    die();
}

/**
 * Fetches the request body parameter with the provided key.
 * 
 * If the require flag is set to 'true' and the key is not in the request body, the server will respond with a 
 * 400 HTTP status code.
 *
 * @param string $key the name of the parameter to fetch
 * @param boolean $require indicates whether to require the parameter. Defaults to true.
 * @return mixed|null the result if it exists in the body, null otherwise
 */
function getFromBody($key, $require = true) {
    $set = isset($_POST[$key]);
    if ($require && !$set) {
        respond(400, "Missing parameter '$key' in request body");
    }
    return $set ? $_POST[$key] : null;
}

/**
 * Handles a request to add an artifact to a project.
 *
 * @param string $projectId the ID of the project to add the artifact to
 * @param \DataAccess\ShowcaseProjectsDao $projectsDao data access object for projects
 * @param \Util\ConfigManager $configManager configuration manager to getting file location information
 * @param \Util\Logger $logger logger for capturing information
 * @return void
 */
function handleAddNewProjectImage($projectId, $projectsDao, $configManager, $logger) {

    if (!isset($_FILES['imageFile'])) {
        respond(400, 'Must include file in request to create project image');
    }
    $fileSize = $_FILES['imageFile']['size'];
    $fileName = $_FILES['imageFile']['name'];
    $mimeType = $_FILES['imageFile']['type'];
    $fileTmp = $_FILES['imageFile']['tmp_name'];

    $fiveMb = 5242880;
    if ($fileSize > $fiveMb) {
        respond(400, 'Project image file must be smaller than 5MB');
    }

    $mimeParts = explode('/', $mimeType);
    if($mimeParts[0] != 'image') {
        respond(400, 'Uploaded file must be an image');
    }

    $image = new ShowcaseProjectImage();
    $image
        ->setFileName($fileName)
        ->setProjectId($projectId);

    // Set the new image as the last in order
    $project = $projectsDao->getProject($projectId);
    $projectImgs = $project->getImages();
    if(count($projectImgs)) {
        $nextOrderIndex = $projectImgs[count($projectImgs) - 1]->getOrder() + 1;
        $image->setOrder($nextOrderIndex);
    } else {
        $image->setOrder(1);
    }

    $imageId = $image->getId();

    $filepath = 
        $configManager->getPrivateFilesDirectory() . '/' .
        $configManager->get('server.upload_project_image_file_path') .
        "/$imageId";

    $ok = move_uploaded_file($fileTmp, $filepath);

    if (!$ok) {
        respond(500, 'Failed to upload image file');
    }


    $ok = $projectsDao->addNewProjectImage($image);
    if (!$ok) {
        respond(500, 'Failed to add project image');
    }

    respond(201, 'Successfully added new image', array('id' => $imageId));
}

/**
 * Handles a request to delete an artifact from a project.
 *
 * @param \DataAccess\ShowcaseProjectsDao $projectsDao data access object for projects
 * @param \Util\ConfigManager $configManager configuration manager to getting file location information
 * @param \Util\Logger $logger logger for capturing information
 * @return void
 */
function handleDeleteProjectImage($projectsDao, $configManager, $logger) {
    $imageId = getFromBody('imageId');

    $filepath = 
            $configManager->getPrivateFilesDirectory() . '/' .
            $configManager->get('server.upload_project_image_file_path') .
            "/$imageId";
    $ok = unlink($filepath);
    if (!$ok) {
        respond(500, 'Failed to delete image');
    }

    // Move reference to the end for keeping order numbering sequential
    $image = $projectsDao->getProjectImage($imageId);
    $projectId = $image->getProjectId();
    $oldIndex = $image->getOrder();
    $newIndex = count($projectsDao->getProject($projectId)->getImages());
    $ok = $projectsDao->moveProjectImage($projectId, $imageId, $oldIndex, $newIndex);
    if (!$ok) {
        respond(500, 'Failed to move image reference for deletion');
    }

    // Delete reference from database
    $ok = $projectsDao->deleteProjectImage($imageId);
    if (!$ok) {
        respond(500, 'Failed to delete image reference from database');
    }

    respond(200, 'Successfully deleted image');
}


/**
 * Handles a request to move an image in a project.
 *
 * @param \DataAccess\ShowcaseProjectsDao $projectsDao data access object for projects
 * @param \Util\ConfigManager $configManager configuration manager to getting file location information
 * @param \Util\Logger $logger logger for capturing information
 * @return void
 */
function handleMoveProjectImage($projectsDao, $configManager, $logger) {
    $projectId = getFromBody('projectId');
    $imageId = getFromBody('imageId');
    $direction = getFromBody('direction');

    $image = $projectsDao->getProjectImage($imageId);
    $oldIndex = $image->getOrder();
    $newIndex = $direction == 'up' ? $oldIndex-1 : $oldIndex+1;

    $ok = $projectsDao->moveProjectImage($projectId, $imageId, $oldIndex, $newIndex);
    if (!$ok) {
        respond(500, 'Failed to move image');
    }

    respond(200, 'Successfully moved image '.$direction);
}