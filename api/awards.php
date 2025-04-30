<?php
/**
 * Handles requests being made on award resources in the database. We cannot use the `ActionHandler` class
 * because it only handles JSON requests, and the award requests are URL form encoded since they may or
 * may not include a file upload.
 */
include_once '../bootstrap.php';

use DataAccess\UsersDao;
use DataAccess\AwardDao;
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

// Make sure the current user has permission to perform this action (i.e. they are either an admin or a
// collaborator on the project)
// $userId = $_SESSION['userID'];
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$userId = $profilesDao->getUserIdFromOnid($_SESSION['auth']['id']); // TEMPORARY FIX for login issues across eecs sites
$usersDao = new UsersDao($dbConn, $logger);
$awardsDao = new AwardDao($dbConn, $logger);
$user = $usersDao->getUser($userId);

//
// The client making the request has passed all access checks. We can now handle the request based on the action.
//
switch ($_POST['action']) {

    case 'awardActiveToggle':
        handleAwardActiveToggle($awardsDao, $configManager, $logger);

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
 * Handles a request to toggle an award's active status
 *
 * @param \Util\ConfigManager $configManager configuration manager to getting file location information
 * @param \Util\Logger $logger logger for capturing information
 * @return void
 */
function handleAwardActiveToggle($awardsDao, $configManager, $logger) {
    $isActive = getFromBody('isActive');
    $awardId = getFromBody('awardId');

    if ($isActive == 1) {
        $ok = $awardsDao->updateAwardInactive($awardId);
    } else {
        $ok = $awardsDao->updateAwardActive($awardId);
    }

    if (!$ok) {
        respond(500, 'Failed to toggle award active status');
    }

    respond(201, 'Successfully toggled award active status');
}
