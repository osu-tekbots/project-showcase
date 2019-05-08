<?php

if(!isset($_SESSION)) {
    session_start();
}

// Get the error message. If there isn't one, redirect to the home page
$message = isset($_SESSION['error']) ? $_SESSION['error'] : false;
$baseUrl = $configManager->getBaseUrl();
if(!$message) {
    echo "<script>window.location.replace('$baseUrl');</script>";
    die();
}

$title = 'An Error Occurred';
include_once PUBLIC_FILES . '/modules/header.php';

echo "

<div class='container' style='padding-top: 20px;'>
    <div class='row'>
        <div class='col'>
            <h1>Whoops!</h1>
            <p>$message</p>
            <a href='$baseUrl' class='btn btn-primary'>
                Home Page
            </a>
        </div>
    </div>
</div>

";

include_once PUBLIC_FILES . '/modules/footer.php';