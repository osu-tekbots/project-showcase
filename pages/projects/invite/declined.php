<?php
/**
 * Displays the message after a user has declined an invitation to collaborate on a project
 */
include_once '../../../bootstrap.php';

$title = 'Invitation Declined';
include_once PUBLIC_FILES . '/modules/header.php';


echo "
<div class='container' style='padding-top: 20px;'>
    <div class='row'>
        <div class='col'>
            <h1>Successfully declined invitation to collaborate</h1>
            <p>You have declined to join the project.</p>
        </div>
    </div>
</div>
";

include_once PUBLIC_FILES . '/modules/footer.php';
