<?php
use DataAccess\ShowcaseProfilesDao;
use DataAccess\ShowcaseProjectsDao;

if (!isset($_SESSION)) {
    session_start();
}

// If an ID is provided, display the profile for that user.
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $userId = $_GET['id'];
    $isOwnProfile = $isLoggedIn && $userId == $_SESSION['userID'];
} 
// If no ID is provided and the user is logged in, then we will show their profile
elseif ($isLoggedIn) {
    $userId = $_SESSION['userID'];
    $isOwnProfile = true;
}
// The user is not logged in and no ID is provided, so redirect to the home page 
else {
    echo "<script>window.location.href('');</script>";
    die();
}

//
// Include the header
// This is so if we render a 'not found' error the page still looks good.
//
$title = 'Showcase Profile';
$css = array(
    'assets/css/profile.css'
);
include_once PUBLIC_FILES . '/modules/header.php';

$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$profile = $profilesDao->getUserProfileInformation($userId);

if (!$profile) {
    echo "
    <div class='container'>
        <h1>Whoops!</h1>
        <p>Looks like we couldn't find the profile you were looking for. Try returning to <a href=''>the home page</a>.
        </p>
    </div>
    ";
} else {
    // Everything checks start, start capturing variables to render the page

    // Create the HTML for the header content
    $image = $profile->isImageUploaded() ? "files/images/$userId" : 'assets/img/avatar.png';
    $name = $profile->getUser()->getFirstName() . ' ' . $profile->getUser()->getLastName();
    $major = $profile->getUser()->getMajor();

    $editButton = !$isOwnProfile ? '' : "
        <a href='profile/edit' id='btnEditProfile' class='btn btn-primary'>
            <i class='fas fa-edit'></i>&nbsp;&nbsp;Edit
        </a>
    ";

    // Create HTML for external links if the user has any
    $websiteLink = $profile->getWebsiteLink();
    $websiteHtml = !is_null($websiteLink) && !empty($websiteLink) ? "
        <a href='$websiteLink' target='_blank'>
            <i class='fas fa-globe fa-2x'></i>
        </a>
    " : '';
    $githubLink = $profile->getGithubLink();
    $githubHtml = !empty($githubLink) && !is_null($githubLink) ? "
        <a href='$githubLink' target='_blank'>
            <i class='fab fa-github fa-2x'></i>
        </a>
    " : '';
    $linkedinLink = $profile->getLinkedInLink();
    $linkedinHtml = !is_null($linkedinLink) && !empty($linkedinLink) ? "
        <a href='$linkedinLink' target='_blank'>
            <i class='fab fa-linkedin fa-2x'></i>
        </a>
    ": '';

    // Create the HTML to render a resume download link if the user has a resume uploaded
    $resumeFileName = $profile->getResumeFileName();
    $resumeHtml = !is_null($resumeFileName) && !empty($resumeFileName) ? "
        <a href='files/resumes/$userId-$resumeFileName' class='btn btn-primary'>
            <i class='fas fa-download'></i>&nbsp;&nbsp;Resume
        </a>
    " : '';

    // Create the HTML for the 'About' section
    $about = $profile->getAbout();
    $about = !empty($about) && !is_null($about) ? "
        <h2>About</h2>
        $about
    " : "
        <h2>About</h2>
        <p>Add a brief description about yourself highlighting your skills and experience.</p>
        <a href='profile/edit' class='btn btn-primary'>
            <i class='fas fa-handshake'></i>&nbsp;&nbsp;Introduce Yourself
        </a>
    ";

    // Create the HTML for the 'Projects' section
    $projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
    $projects = $projectsDao->getUserProjects($userId);
    if (!$projects) {
        if($isOwnProfile) {
            $projectsHtml = "
                <p>You don't have any projects on display</p>
                <a href='profile/edit#projects' class='btn btn-primary'>
                    <i class='fas fa-plus'></i>&nbsp;&nbsp;Add a Project
                </a>
            ";
        } else {
            $projectsHtml = "<p>The student doesn't have any projects on display</p>";
        }
    } else {
        include_once PUBLIC_FILES . '/modules/project.php';
        
        $projectsHtml = "<div class='projects-container>";
        
        foreach ($projects as $p) {
            $projectsHtml .= createProfileProjectHtml($p, $isOwnProfile);
        }

        $projectsHtml = '</div>';
    }

    // Render all of the HTML
    echo "

    <div class='profile-container'>
        <div class='profile-banner'></div>
        <div class='container'>
            <div class='profile'>
                <div class='profile-header'>
                    <img class='profile-image' src='$image' />
                    <div class='profile-header-content'>
                        <h1 class='profile-name'>$name</h1>
                        <h4 class='profile-major'>$major</h4>
                        <div class='profile-links'>
                            $websiteHtml
                            $githubHtml
                            $linkedinHtml
                        </div>
                    </div>
                    $resumeHtml
                    $editButton
                </div>
                <div class='profile-content'>
                    <section class='profile-about'>
                        $about
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
}

include_once PUBLIC_FILES . '/modules/footer.php';
