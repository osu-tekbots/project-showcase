<?php
use DataAccess\UsersDao;
use Model\User;
use DataAccess\ShowcaseProfilesDao;
use Model\ShowcaseProfile;

$baseUrl = $configManager->getBaseUrl();

if ($isLoggedIn) {
    $redirect = $baseUrl . 'profile/';
    echo "<script>window.location.replace('$redirect')</script>";
    die();
}

$provider = isset($_GET['provider']) ? $_GET['provider'] : false;

if (!$provider) {
    echo "<script>window.location.replace('$baseUrl')</script>";
    die();
}

switch ($provider) {
    case 'onid':
        include_once PUBLIC_FILES . '/lib/shared/auth/onid.php';
        $onid = authenticateWithONID();

        $ok = createUserAndProfileIfNeeded($dbConn, $logger, $provider, $onid);
        if (!$ok) {
            $_SESSION['error'] = "
                We were unable to authenticate your sign-in request successfully. Please try again later or contact
                the Tekbots Webdev team if the problem persists.
            ";
            $redirect = $baseUrl . 'error';
            echo "<script>window.location.replace('$redirect');</script>";
            die();
        }

        break;

    default:
        echo "<script>window.location.replace('$baseUrl')</script>";
        die();
}

// Once we have made it to this point, we have successfully logged in. Navigate to the user's profile or to the
// location specified in the URL
$redirect = isset($_GET['redirect'])? $_GET['redirect'] : $baseUrl . 'profile/';
echo "<script>window.location.replace('$redirect')</script>";
die();






/**
 * Creates a new user entry and a new profile entry if needed.
 * 
 * This function utilizes the `$_SESSION['auth']` variables set by authentication providers. Therefore it must be
 * called after successful authentication to work properly.
 *
 * @param \DataAccess\DatabaseConnect $dbConn
 * @param \Util\Logger $logger
 * @param string $provider indicates which provider is being used
 * @param string $authId the ID provided by the provider
 * @return bool true if an entry was created or one exists, false otherwise
 */
function createUserAndProfileIfNeeded($dbConn, $logger, $provider, $authId) {

    // First check if the user was created
    $usersDao = new UsersDao($dbConn, $logger);
    $exists = true;
    switch($provider) {
        case 'onid':
            $user = $usersDao->getUserByOnid($authId);
            if(!$user) {
                $user = new User();
                $user->setOnid($authId);
                $exists = false;
            }

        default:
            return false;
    }

    if(!$exists) {
        $user
            ->setFirstName($_SESSION['auth']['firstName'])
            ->setLastName($_SESSION['auth']['lastName'])
            ->setEmail($_SESSION['auth']['email']);

        $ok = $usersDao->addNewUser($user);
        if(!$ok) {
            return false;
        }
    }

    // The user exists or was created successfully. Check the profile.
    $profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
    $profile = $profilesDao->getUserProfileInformation($user->getId());
    if(!$profile) {
        // The profile does not exist. Create one.
        $profile = new ShowcaseProfile($user->getId());
        $ok = $profilesDao->addNewShowcaseProfile($profile);
        if(!$ok) {
            return false;
        }
    }

    // The user and profile existed or were created successfully
    return true;

}
