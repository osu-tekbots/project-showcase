<?php
/**
 * This api endpoint uploads new profile images to the server.
 */

use DataAccess\ShowcaseProfilesDao;
use DataAccess\UsersDao;
use Model\UserType;

/**
 * Simple function that allows us to respond with a response code and a message inside a JSON object.
 *
 * @param int  $code the HTTP status code of the response
 * @param string $message the message to send back to the client
 * @return void
 */
function respond($code, $message) {
    header('Content-Type: application/json');
    header("X-PHP-Response-Code: $code", true, $code);
    echo '{"message": "' . $message . '"}';
    die();
}

// Verify the action on the resource
if (!isset($_POST['action'])) {
    respond(400, 'Missing action in request body');
}

// Make sure we have the user ID
$userId = isset($_POST['userId']) && !empty($_POST['userId']) ? $_POST['userId'] : null;
if (empty($userId)) {
    respond(400, 'Must include ID of user in request');
}

// Make sure the current user has permission to perform this action
$usersDao = new UsersDao($dbConn, $logger);
$user = $usersDao->getUser($userId);    
if (!$user || !$isLoggedIn || ($userId != $_SESSION['userID'] && $user->getType()->getId() != UserType::ADMIN) ) {
    respond(401, 'You do not have permission to make this request');
}

// Construct the path
$filepath = 
    $configManager->getPrivateFilesDirectory() . '/' .
    $configManager->get('server.upload_profile_image_file_path') .
    "/$userId";

// Get the profile
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$profile = $profilesDao->getUserProfileInformation($userId);
// TODO: handle case when no user profile is found

switch ($_POST['action']) {

    case 'uploadImage';

        // Make sure we have a file
        if (!isset($_FILES['profileImage'])) {
            respond(400, 'Must include file in upload request');
        }

        // Get the information we need
        $fileName = $_FILES['profileImage']['name'];
        $fileSize = $_FILES['profileImage']['size'];
        $fileTmpName  = $_FILES['profileImage']['tmp_name'];

        // Check the file size
        $tenMb = 10485760;
        if ($fileSize > $tenMb) {
            respond(400, 'File size must be smaller than 10MB');
        }

        // Check the mime type
        $mime = mime_content_type($fileTmpName);
        $mimeParts = explode('/', $mime);
        if ($mimeParts[0] != 'image') {
            respond(400, 'File must be an image');
        }

        //
        // We've passed all the checks, now we can upload the image
        //

        $ok = move_uploaded_file($fileTmpName, $filepath);

        if (!$ok) {
            respond(500, 'Failed to upload profile image');
        }

        $profile->setImageUploaded(true);

        $ok = $profilesDao->updateShowcaseProfile($profile);
        if (!$ok) {
            $logger->warning('Profile image was uploaded, but inserting metadata into the database failed');
            respond(500, 'Failed to upload profile image properly');
        }

        respond(200, 'Successfully uploaded profile image');

        
    case 'deleteImage':

        // Delete the image
        $ok = unlink($filepath);
        if (!$ok) {
            respond(500, 'Failed to delete profile image');
        }

        $profile->setImageUploaded(false);
        $ok = $profilesDao->updateShowcaseProfile($profile);
        if (!$ok) {
            $logger->warning('Profile image was deleted, but inserting metadata into the database failed');
            respond(500, 'Failed to delete profile image properly');
        }

        respond(200, 'Successfully deleted profile image');

    default:
        respond(400, 'Invalid action on profile image resource');
}
