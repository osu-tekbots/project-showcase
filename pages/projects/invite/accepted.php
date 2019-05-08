<?php

$projectId = isset($_GET['pid']) ? $_GET['pid'] : false;
if(!$projectId) {
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl');</script>";
    die();
}

$title = 'Invitation Accepted';
include_once PUBLIC_FILES . '/modules/header.php';

echo "

<div class='container' style='padding-top: 20px'>
    <div class='row'>
        <div class='col'>
            <h1>Thank you for accepting the invitation</h1>
            <p>If at any time you wish to disassociate yourself from the project or hide it from your profile, you
            may do so from the project edit page.</p>
            <p>You may view the project <a href='projects/?id=$projectId'>here</a>.</p>
        </div>
    </div>
</div>

";

include_once PUBLIC_FILES . '/modules/footer.php';