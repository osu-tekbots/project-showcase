<?php
/**
 * This page handles the login process for a user. We use OSU's CAS method of authentication. The authentication logic
 * is defined inside of a separate PHP file and called from this file. Once authentication is complete, the user
 * will be redirected back to the home page.
 */
include_once PUBLIC_FILES . '/lib/auth-onid.php';

authenticate();

$redirect = $configManager->getBaseUrl() . 'profile/';
echo "<script>window.location.replace('$redirect');</script>";
die();