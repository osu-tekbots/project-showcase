<?php
/**
 * View showcase profile page
 */
include_once '../../bootstrap.php';

use DataAccess\ShowcaseProfilesDao;
use DataAccess\ShowcaseProjectsDao;
use Util\Security;
use DataAccess\KeywordsDao;

if (!isset($_SESSION)) {
    session_start();
}

$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);

// If an ID is provided, display the profile for that user.
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $userId = $_GET['id'];
    $isOwnProfile = $isLoggedIn && $userId == $_SESSION['userID'];
} 
// If no ID is provided and the user is logged in, then we will show their profile
elseif ($isLoggedIn) {
    // $userId = $_SESSION['userID'];
    $userId = $profilesDao->getUserIdFromOnid($_SESSION['auth']['id']); // TEMPORARY FIX for login issues across eecs sites
    $isOwnProfile = true;
}
// The user is not logged in and no ID is provided, so redirect to the home page 
else {
    $baseUrl = $configManager->getBaseUrl();
    echo "<script>window.location.replace('$baseUrl');</script>";
    die();
}

//
// Include the header
// This is so if we render a 'not found' error the page still looks good.
//
$title = 'Showcase Profile';
$css = array(
    'assets/css/profile.css',
    'assets/css/project.css'
);
$js = array(
    'assets/js/smartcrop.js',
    'assets/js/crop.js'
);
include_once PUBLIC_FILES . '/modules/header.php';

$profile = $profilesDao->getUserProfileInformation($userId);

if (!$profile) {
    $success = false;

    if($isLoggedIn) {
        include_once PUBLIC_FILES."/lib/auth-onid.php";
        createProfileIfNeeded($dbConn, $logger, $_SESSION['userID']);
        
        // Now try fetching again to get all necessary data
        $profile = $profilesDao->getUserProfileInformation($userId);
        if($profile)
            $success = true;
    } 
    
    // Check !$success instead of just `else` in case profile creation fails
    if(!$success) {
        echo "
        <div class='container'>
            <h1>Whoops!</h1>
            <p>Looks like we couldn't find the profile you were looking for. Try returning to <a href=''>the home page</a>. You may need to logout and back in.
            </p>
        </div>
        ";
        include_once PUBLIC_FILES . '/modules/footer.php';
        die();
    }
} 

// Everything checks start, start capturing variables to render the page

// Create the HTML for the header content
$image = $profile->isImageUploaded() 
    ? "
        <img class='profile-image pulse' id='profileImage' />
        <script>
            crop('downloaders/profile-images.php?id=$userId', (cropped) => {
                $('#profileImage').attr('src', cropped);
                $('#profileImage').removeClass('pulse');
            });
        </script>" 
    : '';
$name = Security::HtmlEntitiesEncode($profile->getUser()->getFullName());
$major = Security::HtmlEntitiesEncode($profile->getUser()->getMajor());

$contactHtml = '';
if ($profile->canShowContactInfo()) {
    $phone = $profile->getUser()->getPhone();
    $email = Security::ValidateEmail($profile->getUser()->getEmail());
    if (empty($phone) && empty($email)){
        $contactHtml = "";
    }
    else if (empty($phone)){
        $contactHtml = "
        <i><a href='mailto:$email'>$email</a></i>
    ";
    } else if (empty($email)) {
    $contactHtml = "
        <i>$phone</i>
    ";
    } else {
        $contactHtml = "
        <i><a href='mailto:$email'>$email</a></i>&nbsp;|&nbsp;<i>$phone</i>
    ";
    }
}

$editButton = !$isOwnProfile ? '' : "
    <a href='profile/edit.php' id='btnEditProfile' class='btn btn-primary'>
        <i class='fas fa-edit'></i>&nbsp;&nbsp;Edit
    </a>
";

$signoutButton = !$isOwnProfile ? '' : "
    <a href='signout' id='btnSignOut' class='btn btn-light'>
        <i class='fas fa-sign-out-alt'></i>&nbsp;&nbsp;Sign Out
    </a>
";

// Create HTML for external links if the user has any
$websiteLink = Security::ValidateUrl($profile->getWebsiteLink());
$websiteHtml = !is_null($websiteLink) && !empty($websiteLink) ? "
    <a href='$websiteLink' target='_blank'>
        <i class='fas fa-globe fa-2x'></i>
    </a>
" : '';
$githubLink = Security::ValidateUrl($profile->getGithubLink());
$githubHtml = !empty($githubLink) && !is_null($githubLink) ? "
    <a href='$githubLink' target='_blank'>
        <i class='fab fa-github fa-2x'></i>
    </a>
" : '';
$linkedinLink = Security::ValidateUrl($profile->getLinkedInLink());
$linkedinHtml = !is_null($linkedinLink) && !empty($linkedinLink) ? "
    <a href='$linkedinLink' target='_blank'>
        <i class='fab fa-linkedin fa-2x'></i>
    </a>
": '';

$contactStyle = '';
if (empty($websiteHtml) && empty($githubHtml) && empty($linkedinHtml)) {
    $contactStyle = "style='padding-bottom: 60px; margin-top: -35px;'";
}

// Create the HTML to render a resume download link if the user has a resume uploaded
$resumeUploaded = $profile->isResumeUploaded();
$resumeHtml = $resumeUploaded ? "
    <a href='downloaders/resumes.php?id=$userId' class='btn btn-primary'>
        <i class='fas fa-download'></i>&nbsp;&nbsp;Resume
    </a>
" : '';

// Create the HTML for the 'About' section
$about = Security::HtmlEntitiesEncode($profile->getAbout());
if (empty($about) || is_null($about)) {
    if ($isOwnProfile) {
        $aboutHtml = "
        <h2>About</h2>
        <p>Add a brief description about yourself highlighting your skills and experience.</p>
        <a href='profile/edit.php' class='btn btn-primary'>
            <i class='fas fa-handshake'></i>&nbsp;&nbsp;Introduce Yourself
        </a>
        ";
        
    } else {
        $aboutHtml = '';
        $profileLink = "";
    }
} else {
    $aboutHtml = "
    <h2>About</h2>
    <p>$about</p>
    ";
}

if ($isOwnProfile) {
    $link = $baseUrl . "profile/?id=$userId";
    $profileLink = "
    <p>Sharable Profile Link: <a href='$link'>$link</a></p>
";
} else {
    $profileLink = "";
}

// Create the HTML for the 'Projects' section
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$projects = $projectsDao->getUserProjects($userId, false, true);
//    Edited line above to show hidden project also when going to personal profile. $projects = $projectsDao->getUserProjects($userId, false, $isOwnProfile);
if (!$projects) {
    if ($isOwnProfile) {
        $projectsHtml = "
            <p>You don't have any projects on display</p>
            <a href='profile/edit.php#projects' class='btn btn-primary'>
                <i class='fas fa-plus'></i>&nbsp;&nbsp;Add a Project
            </a>
        ";
        
    } else {
        $projectsHtml = "<p>The student doesn't have any projects on display</p>";
    }
} else {
    include_once PUBLIC_FILES . '/modules/project.php';

    $keywordsDao = new KeywordsDao($dbConn, $logger);
    
    $projectsHtml = "<div class='projects-container'>";
    
    foreach ($projects as $p) {
        $keywords = $keywordsDao->getKeywordsForEntity($p->getId());
        $p->setKeywords($keywords);
        $projectsHtml .= createProfileProjectHtml($p, $isOwnProfile);
    }

    
    $projectsHtml .= "<a href='profile/edit.php#projects' class='btn btn-primary'>
                <i class='fas fa-plus'></i>&nbsp;&nbsp;Add a Project
            </a>";
    $projectsHtml .= '</div>';
}

// Render all of the HTML
echo "

<div class='profile-container'>
    <div class='profile-banner'></div>
    <div class='container'>
        <div class='profile'>
            <div class='profile-header'>
                $image
                <div class='profile-header-content'>
                    <h1 class='profile-name'>$name</h1>
                    <h4 class='profile-major'>$major</h4>
                    <div class='profile-links'>
                        $websiteHtml
                        $githubHtml
                        $linkedinHtml
                    </div>
                    <div class='profile-contact' $contactStyle>
                        $contactHtml
                        $profileLink
                    </div>
                </div>
                $resumeHtml
                $editButton
                $signoutButton
            </div>
            <div class='profile-content'>
                <section class='profile-about'>
                    $aboutHtml
                </section>
                <section class='profile-projects-section'>
                    <h2>Projects</h2>
                    $projectsHtml
                </section>
            </div>
        </div>
    </div>
</div>

";

include_once PUBLIC_FILES . '/modules/footer.php';
