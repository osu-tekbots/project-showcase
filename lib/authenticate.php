<?php

use DataAccess\UsersDao;
$usersDao = new UsersDao($dbConn, $logger);

if (!session_id()) session_start();

$user = NULL;

// Get user & set $_SESSION user variables for this site
if(isset($_SESSION['site']) && $_SESSION['site'] == 'projectShowcase') {
    // $_SESSION["site"] is this one! User info should be correct
    $logger->trace('Moved to another page while logged into this site');
} else {
    if(isset($_SESSION['auth']['method'])) {
        switch($_SESSION['auth']['method']) {
            case 'onid':
                // Logged in with ONID on another site; storing this site's user info in $_SESSION...
                
                $logger->trace('Updating $_SESSION for this site using ONID: '.$_SESSION['auth']['id'].' (came from '.($_SESSION['site'] ?? 'no site').')');
                $user = $usersDao->getUserByOnid($_SESSION['auth']['id']);
                
                $_SESSION['site'] = 'projectShowcase';
                $_SESSION['userID'] = $user->getId();
                $_SESSION['userType'] = $user->getType()->getId();
                
                break;
            
            default:
                // Logged in with something not valid for this site; setting as not logged in
                $logger->trace('Authentication provider is '.$_SESSION['auth']['method'].', not something this site recognizes');

                $_SESSION['site'] = NULL;
                unset($_SESSION['userID']);
                $_SESSION['userType'] = NULL;
        }
    } else {
        // Not logged in; still clear just to avoid the possibility of issues?
        $logger->trace('Switched from another site, but not logged in');
        $_SESSION['site'] = NULL;
        unset($_SESSION['userID']);
        $_SESSION['userType'] = NULL;
    }
}