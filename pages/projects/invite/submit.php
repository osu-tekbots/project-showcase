<?php
/**
 * This page handles a decision from a user on whether or not to accept or decline an invitation to collaborate on a
 * project from another user. If the user declined the invitation, they will be redirected to the 'declined.php'
 * page after this script completes. If they accepted the invitation, they will be redirected to the 'accepted.php'
 * file.
 */
include_once '../../../bootstrap.php';

use DataAccess\ShowcaseProjectsDao;

if (!isset($_SESSION)) {
    session_start();
}

$decision = isset($_POST['decision']) ? $_POST['decision'] : false;
$projectId = isset($_POST['projectId']) ? $_POST['projectId'] : false;
$userId = isset($_POST['userId']) ? $_POST['userId'] : false;
$invitationId = isset($_POST['invitationId']) ? $_POST['invitationId'] : false;

$baseUrl = $configManager->getBaseUrl();

switch ($decision) {

    case 'accept':
        acceptInvitation($projectId, $userId, $invitationId);
        break;

    case 'decline':
        declineInvitation($invitationId);
        break;

    default:
        redirectToError($baseUrl);

}

/**
 * Processes accepting an invitation to collaborate on a project and updates the database accordingly.
 *
 * @param string $projectId the ID of the project to associate the user with
 * @param string $userId the ID of the user to associate with the project
 * @param string $invitationId the ID of the invitation, which will be removed from the database
 * @return void
 */
function acceptInvitation($projectId, $userId, $invitationId) {
    global $dbConn, $logger, $baseUrl;

    if (!$projectId || !$userId || !$invitationId) {
        redirectToError($baseUrl);
    }

    $dao = new ShowcaseProjectsDao($dbConn, $logger);
    $ok = $dao->acceptInvitationToCollaborateOnProject($projectId, $userId, $invitationId);
    if (!$ok) {
        redirectToError($baseUrl);
    }

    $redirect = $baseUrl . "projects/invite/accepted.php?pid=$projectId";
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

/**
 * Processes declining an invitation to collaborate on a project, removing the invitation from the database.
 *
 * @param string $invitationId the ID of the invitation to remove
 * @return void
 */
function declineInvitation($invitationId) {
    global $dbConn, $logger, $baseUrl;

    if (!$invitationId) {
        redirectToError($baseUrl);
    }

    $dao = new ShowcaseProjectsDao($dbConn, $logger);
    $ok = $dao->removeInvitationToCollaborateOnProject($invitationId);
    if (!$ok) {
        redirectToError($baseUrl);
    }

    $redirect = $baseUrl . 'projects/invite/declined.php';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

/**
 * Redirects to the sites error page and displays the message.
 *
 * @param string $baseUrl the base URL of the website
 * @return void
 */
function redirectToError($baseUrl) {
    $_SESSION['error'] = "
            We were unable to process the invitation decision. Please try the link again or 
            <a href='$baseUrl'>return to the home page</a>
        ";
    $redirect = $baseUrl . 'error';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}
