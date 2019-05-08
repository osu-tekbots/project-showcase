<?php
use DataAccess\ShowcaseProfilesDao;
use DataAccess\ShowcaseProjectsDao;

$title = 'Project Invitation';
$css = array(
    'assets/css/invite.css'
);
include_once PUBLIC_FILES . '/modules/header.php';

// Get what we need from the URL and the SESSION (the user must be logged in to accept invitations)
$projectId = isset($_GET['pid']) ? $_GET['pid'] : false;
$invitationId = isset($_GET['iid']) ? $_GET['iid'] : false;
$userId = isset($_SESSION['userID']) ? $_SESSION['userID'] : false;

$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);

// If we don't have an invitation ID, then the URL is not valid. Display a message.
if(!$invitationId) {
    renderInvalidInvitationHtml();
}

// If we don't have a user ID, the user may need to log in/create a new account
if(!$userId) {
    $loginRedirect = $configManager->getBaseUrl() . "projects/invite/?pid=$projectId";
    echo "<script>window.location.replace('auth/login?provider=onid&redirect=$loginRedirect');</script>";
    die();
}

// Check if the project exists
$project = $projectsDao->getProject($projectId);
if (!$project) {
    echo "
    <div class='container'>
        <div class='row'>
            <div class='col'>
                <h1>Whoops!</h1>
                <p>Looks like we couldn't find the project for this invitation. Try <a href=''>returning to the home 
                page</a></p>
            </div>
        </div>
    </div>
    ";

    dieWithFooter();
} 

// Check to make sure the profile exists
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$profile = $profilesDao->getUserProfileInformation($userId);
if (!$profile) {
    echo "
    <div class='container'>
        <div class='row'>
            <div class='col'>
                <h1>Whoops!</h1>
                <p>Looks like we couldn't find a valid user for this invitation. Try <a href=''>returning to the home 
                page</a></p>
            </div>
        </div>
    </div>
    ";

    dieWithFooter();
}

//
// Everything is okay. We have the project and the user who is being invited. We need to do a couple more checks to
// makes sure this is a valid invitation that hasn't already been accepted. Then we can display the invitation and the
// options to accept or decline.
//

$isCollaborator = $projectsDao->verifyUserIsCollaboratorOnProject($projectId, $userId);

if($isCollaborator) {
    // The user has already accepted the invitation. Redirect to the project page.
    echo "<script>window.location.replace('projects/?id=$projectId');</script>";
    die();
}

// The user is not a collaborator, so make sure they have an invitation.
// TODO: consider adding an expiration date to invitations and checking it here as well
$invitation = $projectsDao->getInvitationToCollaborateOnProject($invitationId);
if(!$invitation) {
    renderInvalidInvitationHtml();
}

//
// All checks pass. Display the invitation.
//

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
                <form method='POST' action='projects/invite/submit'>
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

dieWithFooter();


/**
 * Terminates the script after printing the footer to the output buffer
 *
 * @return void
 */
function dieWithFooter() {
    include_once PUBLIC_FILES . '/modules/footer.php';
    die();
}

/**
 * Prints a message to the screen for the user indicating the invitation is not valid.
 *
 * @return void
 */
function renderInvalidInvitationHtml() {
    echo "
    <div class='container'>
        <div class='row'>
            <div class='col'>
                <h1>Whoops!</h1>
                <p>Looks like this isn't a valid invitation. Try checking the link or <a href=''>returning to the home 
                page</a></p>
            </div>
        </div>
    </div>
    ";
    dieWithFooter();
}
