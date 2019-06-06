<?php
/**
 * This page handles the logout process for a user.
 */
include_once '../bootstrap.php';

// Unset the session variable and redirect to the home page
if(!isset($_SESSION)){
    session_start();
}
unset($_SESSION['userID']);
unset($_SESSION['auth']);

session_destroy();

$baseUrl = $configManager->getBaseUrl();

echo "<script>window.location.replace('$baseUrl');</script>";
die();