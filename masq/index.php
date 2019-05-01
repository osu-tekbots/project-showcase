<?php
/**
 * This file is password protected on the Apache Web Server. It allows for local development of an authenticated
 * test user without the need for CAS or other OAuth authentication services, since these services do not permit
 * the use of localhost URLs.
 * 
 * Essentially, we are masquerading as another user while we do development offline.
 */
use DataAccess\UsersDao;
use DataAccess\ShowcaseProfilesDao;
use Model\ShowcaseProfile;

if (!isset($_SESSION)) {
    session_start();
}

$dao = new UsersDao($dbConn, $logger);

$redirect = "<script>location.replace('../index.php')</script>";

$masqerading = isset($_SESSION['masq']);
if ($masqerading) {
    $user = $dao->getUser($_SESSION['userID']);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'start':
        $onid = $_POST['onid'];
        if ($onid . '' != '') {
            $user = $dao->getUserByOnid($onid);
            if ($user) {
                // Make sure the user has a showcase profile. If not, create one
                $profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
                $profile = $profilesDao->getUserProfileInformation($user->getId());
                if (!$profile) {
                    $profile = new ShowcaseProfile($user->getId(), true);
                    $ok = $profilesDao->addNewShowcaseProfile($profile);
                    if (!$ok) {
                        $message = 'Failed to add new showcase profile for user';
                    }
                }
                if (isset($ok) && $ok) {
                    stopMasquerade();
                    startMasquerade($user);
                    echo $redirect;
                    die();
                }
            } else {
                $message = 'User with the provided ONID not found';
            }
        }
        break;
        
    case 'stop':
        stopMasquerade();
        echo $redirect;
        die();

    default:
        break;
}

/**
 * Stops the current masquerade (if there is one) and restores the original user session variables.
 *
 * @return void
 */
function stopMasquerade() {
    if (isset($_SESSION['masq'])) {
        unset($_SESSION['userID']);
        if (isset($_SESSION['masq']['savedPreviousUser'])) {
            $_SESSION['userID'] = $_SESSION['masq']['userID'];
        }
        unset($_SESSION['masq']);
    }
}

/**
 * Starts to masquerade as the provided user
 *
 * @param \Model\User $user the user to masquerade as
 * @return void
 */
function startMasquerade($user) {
    $_SESSION['masq'] = array('active' => true);
    if (isset($_SESSION['userID'])) {
        $_SESSION['masq']['savedPreviousUser'] = true;
        $_SESSION['masq']['userID'] = $_SESSION['userID'];
    }
    $_SESSION['userID'] = $user->getId();
}
?>

<h1>OSU Project Showcase: Masquerade as Another User</h1>

<?php if ($masqerading): ?>
    <p>Currently masqerading as <strong><?php echo $user->getFirstName() . ' ' . $user->getLastName(); ?></strong></p>
<?php endif; ?>

<?php if (isset($message)): ?>
    <p><?php echo $message ?></p>
<?php endif; ?>

<h3>Masquerade as Existing</h3>
<form method="post">
    <input type="hidden" name="action" value="start" />
    <label for="onid">ONID</label>
    <input required type="text" id="eonid" name="onid" autocomplete="off" />
    <button type="submit">Start Masquerading</button>
</form>

<h3>Stop Masquerading</h3>
<form method="post">
    <input type="hidden" name="action" value="stop" />
    <button type="submit">Stop</button>
</form>



