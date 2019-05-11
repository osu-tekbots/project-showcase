<?php
include_once PUBLIC_FILES . '/lib/auth-onid.php';

authenticate();

$redirect = $configManager->getBaseUrl() . 'profile/';
echo "<script>window.location.replace('$redirect');</script>";
die();