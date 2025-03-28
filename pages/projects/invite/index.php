<?php
/**
 * View invitation to collaborate on project page. Users must have a valid invitation URL sent from a current user
 * of the site to successfully view an invitation on this page.
 */
include_once '../../../bootstrap.php';

use DataAccess\ShowcaseProfilesDao;
use DataAccess\ShowcaseProjectsDao;

if (!isset($_SESSION)) {
    session_start();
}

// Get what we need from the URL and the SESSION (the user must be logged in to accept invitations)
$projectId = isset($_GET['pid']) ? $_GET['pid'] : false;
$invitationId = isset($_GET['iid']) ? $_GET['iid'] : false;
// $userId = isset($_SESSION['userID']) ? $_SESSION['userID'] : false;
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$userId = $profilesDao->getUserIdFromOnid($_SESSION['auth']['id']); // TEMPORARY FIX for login issues across eecs sites

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);

$baseUrl = $configManager->getBaseUrl();

// If we don't have a user ID, the user may need to log in/create a new account
if (!$userId) {

    // First we need to save the IDs provided in the URL so that they aren't lost during the authentication process
    if (!isset($_SESSION['iInvitationId'])) {
        $_SESSION['iInvitationId'] = $invitationId;
        $_SESSION['iProjectId'] = $projectId;
    }

    // Authenticate using ONID
    include_once PUBLIC_FILES . '/lib/auth-onid.php';
    authenticate();
}

// If we don't have an invitation ID, they may have been lost during authentication. Attempt to recover them. If they
// still are not present, display an error.
if (!$invitationId || !$projectId) {
    $invitationId = isset($_SESSION['iInvitationId']) ? $_SESSION['iInvitationId'] : false;
    $projectId = isset($_SESSION['iProjectId']) ? $_SESSION['iProjectId'] : false;

    if (!$invitationId || !$projectId) {
        $_SESSION['error'] = '
            This is not a valid invitation.
        ';
        $redirect = $baseUrl . 'error';
        echo "<script>window.location.replace('$redirect');</script>";
        die();
    } else {
        unset($_SESSION['iInvitationId']);
        unset($_SESSION['iProjectId']);
        $redirect = $baseUrl . "projects/invite/?pid=$projectId&iid=$invitationId";
        echo "<script>window.location.replace('$redirect');</script>";
        die();
    }
}

// Check if the project exists
$project = $projectsDao->getProject($projectId);
if (!$project) {
    $_SESSION['error'] = "
        Looks like we couldn't find the project for this invitation.
    ";
    $redirect = $baseUrl . 'error';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
} 

// Check to make sure the profile exists
$profile = $profilesDao->getUserProfileInformation($userId);
if (!$profile) {
    $_SESSION['error'] = "
        Looks like we couldn't find a valid user for this invitation.
    ";
    $redirect = $baseUrl . 'error';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

//
// Everything is okay. We have the project and the user who is being invited. We need to do a couple more checks to
// makes sure this is a valid invitation that hasn't already been accepted. Then we can display the invitation and the
// options to accept or decline.
//

$isCollaborator = $projectsDao->verifyUserIsCollaboratorOnProject($projectId, $userId);

if ($isCollaborator) {
    // The user has already accepted the invitation. Redirect to the project page.
    $redirect = $baseUrl . "projects/?id=$projectId";
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

// The user is not a collaborator, so make sure they have an invitation.
// TODO: consider adding an expiration date to invitations and checking it here as well
$invitation = $projectsDao->getInvitationToCollaborateOnProject($invitationId);
if (!$invitation) {
    $_SESSION['error'] = '
        Looks like the invitation is no longer valid.
    ';
    $redirect = $baseUrl . 'error';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

//
// All checks pass. Display the invitation.
//

$title = 'Project Invitation';
$css = array(
    'assets/css/invite.css'
);
include_once PUBLIC_FILES . '/modules/header.php';

$title = $project->getTitle();

echo "
<div class='container'>
    <div class='row justify-content-center'>
        <div class='col-md-7 text-center'>
            <h1>You're Invited</h1>
            <p>You've been invited to join the showcase project <strong>$title</strong> as a collaborator. By
            accepting the invitation, the project will be displayed on your profile. After you have accepted
            the invitation, if you no longer wish for the project to show up publically on your profile, you can
            modify its visibility in the project settings.</p>
        </div>
    </div>
    <div class='row justify-content-md-center'>
        <div class='col-md-7 invitation-container'>
            <div class='invitation-actions'>
                <form method='POST' action='projects/invite/submit.php'>
                    <input type='hidden' name='projectId' value='$projectId' />
                    <input type='hidden' name='userId' value='$userId' />
                    <input type='hidden' name='invitationId' value='$invitationId' />
                    <button type='submit' name='decision' value='accept' class='btn btn-lg btn-primary'>
                        <i class='far fa-check-circle'></i>&nbsp;&nbsp;Accept
                    </button>
                    <button type='submit' name='decision' value='decline' class='btn btn-lg btn-danger'>
                        <i class='far fa-times-circle'></i>&nbsp;&nbsp;Decline
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
";

include_once PUBLIC_FILES . '/modules/footer.php';
