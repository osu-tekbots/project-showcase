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
use Model\ShowcaseProjectArtifact;
use Model\UserType;

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
if (($isCollaborator == null) && ($user->getType()->getId() != UserType::ADMIN)) {
    respond(500, 'Failed to verify if user is collaborator on project');
}

if (!$isCollaborator && $user->getType()->getId() != UserType::ADMIN) {
    respond(401, 'You do not have permission to make this request');
}

//
// The client making the request has passed all access checks. We can now handle the request based on the action.
//
switch ($_POST['action']) {

    case 'addArtifact':
        handleAddNewArtifact($projectId, $projectsDao, $configManager, $logger);

    case 'deleteArtifact':
        handleDeleteProjectArtifact($projectsDao, $configManager, $logger);

    default: 
        respond(400, 'Invalid action on artifact resource');
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
        respond(400, "Missin parameter '$key' in request body");
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
function handleAddNewArtifact($projectId, $projectsDao, $configManager, $logger) {
    $name = getFromBody('name');
    $description = getFromBody('description');
    $type = getFromBody('artifactType');

    $project = $projectsDao->getProject($projectId);
    // TODO: handle case when project is not found

    $artifact = new ShowcaseProjectArtifact();
    $artifact
        ->setName($name)
        ->setDescription($description)
        ->setProject($project);

    $artifactId = $artifact->getId();

    switch ($type) {

        case 'link':
            $link = getFromBody('artifactLink');
            $artifact->setLink($link);
            break;

        case 'file':
            if (!isset($_FILES['artifactFile'])) {
                respond(400, 'Must include file in request to create artifact with file attachment');
            }
            $fileSize = $_FILES['artifactFile']['size'];
            $fileTmp = $_FILES['artifactFile']['tmp_name'];
			
			$filename = $_FILES['artifactFile']['name'];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);

            $fiveMb = 25242880;
            if ($fileSize > $fiveMb) {
                respond(400, 'Artifact file must be smaller than 25MB');
            }

            $filepath = 
                $configManager->getPrivateFilesDirectory() . '/' .
                $configManager->get('server.upload_artifact_file_path') .
                "/$artifactId";

            $ok = move_uploaded_file($fileTmp, $filepath);

            if (!$ok) {
                respond(500, 'Failed to upload artifact file');
            }

            $artifact->setFileUploaded(true);
			$artifact->setExtension($ext);

            break;
    }

    $ok = $projectsDao->addNewProjectArtifact($artifact);
    if (!$ok) {
        respond(500, 'Failed to add project artifact');
    }

    respond(201, 'Successfully added new artifact', array('id' => $artifactId));
}

/**
 * Handles a request to delete an artifact from a project.
 *
 * @param \DataAccess\ShowcaseProjectsDao $projectsDao data access object for projects
 * @param \Util\ConfigManager $configManager configuration manager to getting file location information
 * @param \Util\Logger $logger logger for capturing information
 * @return void
 */
function handleDeleteProjectArtifact($projectsDao, $configManager, $logger) {
    $artifactId = getFromBody('artifactId');

    $artifact = $projectsDao->getProjectArtifact($artifactId);
    // TODO: handle case when artifact is not found
	
    // Check to see if we need to delete a file
    $removedFile = false;
    if ($artifact->isFileUploaded()) {
        $filepath = 
                $configManager->getPrivateFilesDirectory() . '/' .
                $configManager->get('server.upload_artifact_file_path') .
                "/$artifactId";
        $ok = unlink($filepath);
        if (!$ok) {
            respond(500, 'Failed to delete artifact');
        }
        $removedFile = true;
    }
	
	
    $ok = $projectsDao->deleteProjectArtifact($artifactId);
    if (!$ok) {
        if ($removedFile) {
            $logger->warn('Error when deleting artifact: successfully deleted file but failed to remove metadata from database');
        }
        respond(500, 'Failed to delete artifact');
    }

    respond(200, 'Successfully deleted artifact');
}
