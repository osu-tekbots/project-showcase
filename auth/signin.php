<?php

if ($isLoggedIn) {
    echo "<script>window.location.replace('profile/')</script>";
    die();
}

$provider = isset($_GET['provider']) ? $_GET['provider'] : false;

if (!$provider) {
    echo "<script>window.location.replace('')</script>";
    die();
}

include_once PUBLIC_FILES . '/lib/shared/auth/onid.php';

switch ($provider) {
    case 'onid':
        authenticateWithONID();
        break;

    case 'google':
        authenticateWithGoogle();
        break;

    default:
        echo "<script>window.location.replace('')</script>";
        die();
}

// Once we have made it to this point, we have successfully logged in. Navigate to the user's profile
echo "<script>window.location.replace('profile/')</script>";
die();
