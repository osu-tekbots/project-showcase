<?php
/**
 * Edit showcase profile page
 */
include_once '../../bootstrap.php';

use DataAccess\ShowcaseProfilesDao;
use DataAccess\ShowcaseProjectsDao;
use Util\Security;
use Model\UserType;

if (!isset($_SESSION)) {
    session_start();
}

$baseUrl = $configManager->getBaseUrl();
if (!$isLoggedIn) {
    echo "<script>window.location.replace('$baseUrl');</script>";
    die();
}
$userId = $_SESSION['userID'];



if (isset($_GET['id']) && $_GET['id'] != $userId) {
    if ($_SESSION['userType'] == UserType::ADMIN) {
        $userId = $_GET['id'];
    } else {
        $_SESSION['error'] = 'You do not have permission to edit that profile';
        echo "<script>window.location.replace('$baseUrl/error');</script>";
        die();
    }
}





// Get the user profile information
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$profile = $profilesDao->getUserProfileInformation($userId);
if (!$profile) {
    echo "<script>window.location.replace('');</script>";
    die();
}

$pFirstname = $profile->getUser()->getFirstName();
$pLastName = $profile->getUser()->getLastName();
$pMajor = $profile->getUser()->getMajor();
$pPhoneNumber = $profile->getUser()->getPhone();
$pEmail = Security::ValidateEmail($profile->getUser()->getEmail());

$pShowContactInfo = $profile->canShowContactInfo();
$pContactInfoHtmlDisplay = $pShowContactInfo ? '' : "style='display: none;'";

$pAbout = $profile->getAbout();

$pHasProfileImage = $profile->isImageUploaded();
$pProfileImageText = $pHasProfileImage ? "
    <p id='profileImageText'>Current Profile Image</p>
" : "
    <p id='profileImageText'>No Image has been uploaded</p>
";
$pProfileImagePreviewHtml = $pHasProfileImage ? "
    <img id='profileImagePreview' style='display: none;' />
    <script>
        crop('downloaders/profile-images?id=$userId', (cropped) => {
            $('#profileImagePreview').attr('src', cropped);
            $('#profileImagePreview').show();
            $('#btnProfileImageDelete').show();
        });
    </script>
    <button type='button' class='btn btn-danger' id='btnProfileImageDelete' style='display: none'>
        <i class='fas fa-trash-alt'></i>
    </button>
" : "
    <img id='profileImagePreview' style='display: none;' />
    <button type='button' class='btn btn-danger' id='btnProfileImageDelete' style='display: none'>
        <i class='fas fa-trash-alt'></i>
    </button>
";

$pWebsiteLink = $profile->getWebsiteLink();
$pGitHubLink = $profile->getGithubLink();
$pLinkedInLink = $profile->getLinkedInLink();

$pHasResume = $profile->isResumeUploaded();
$pResumeLink = $pHasResume ? "downloaders/resumes?id=$userId" : '';
$pResumeButtonsStyle = $pHasResume ? '' : "style='display: none;'";
$pResumeHtml = $pHasResume ? "
    <p id='resumeText'>You have uploaded a resume.</p>
" : "
    <p id='resumeText'>No resume has been uploaded</p>
";

// Generate the HTML for the projects
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$projects = $projectsDao->getUserProjects($userId, false, true);
if (!$projects || count($projects) == 0) {
    $pProjectsMessage = "
        <p>You haven't created any projects yet.</p>
    ";
    $pProjectsHtml = '';
    $pProjectsStyle = "style='display: none;'";
} else {
    $pProjectsMessage = '';
    $pProjectsHtml = '';
    foreach ($projects as $p) {
        $pid = $p->getId();
        $title = Security::HtmlEntitiesEncode($p->getTitle());
        $description = $p->getDescription();
        if (strlen($description) > 280) {
            $description = substr($description, 0, 280) . '...';
        }
        $description = Security::HtmlEntitiesEncode($description);

        $hidden = !$p->isPublished() ? "
            &nbsp;&nbsp;&nbsp;<span class='hidden-alert badge badge-pill badge-danger'><i class='fas fa-eye-slash'></i></span>"
            : '';

        $pProjectsHtml .= "
            <tr>
                <td class='project-title'>$title $hidden</td>
                <td>$description</td>
                <td>
                    <a href='projects/edit?id=$pid' class='btn btn-sm btn-light' data-toggle='tooltip'
                        data-placement='right' title='Edit'>
                        <i class='fas fa-edit'></i>
                    </a>
                </td>
            </tr>
        ";
    }

    $pProjectsStyle = '';
}




$title = 'Edit Profile';
$css = array(
    'assets/css/profile-edit.css'
);
$js = array(
    'assets/js/smartcrop.js',
    'assets/js/crop.js',
    array(
        'src' => 'assets/js/profile-edit.js',
        'defer' => 'true'
    )
);
include_once PUBLIC_FILES . '/modules/header.php';

?>


<div class="container">
    <h1>Edit Profile</h1>
    <a href="profile/?id=<?php echo $userId; ?>" class="btn btn-sm btn-light">
        <i class="fas fa-chevron-left"></i>&nbsp;&nbsp;View Profile
    </a>
    <br />
    <br />

    <form id="formEditProfile">

        <input type="hidden" name="userId" id="userId" value="<?php echo $userId; ?>" />

        <div class="btn-profile-edit-submit">
            <button disabled type="submit" class="btn btn-lg btn-primary" id="btnEditProfileSubmit">
                <i class="fas fa-save"></i>&nbsp;&nbsp;Save Changes
            </button>
            <span class="loader" id="formEditProfileLoader"></span>
        </div>

        <h3 id="general">General</h3>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">First Name</label>
            <div class="col-sm-5">
                <input required name="firstName" type="text" class="form-control" placeholder="First Name" value="<?php echo $pFirstname; ?>" />
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Last Name</label>
            <div class="col-sm-5">
                <input required name="lastName" type="text" class="form-control" placeholder="Last Name" value="<?php echo $pLastName; ?>" />
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Major</label>
            <div class="col-sm-5">
                <input name="major" type="text" class="form-control" placeholder="Major" value="<?php echo $pMajor; ?>" />
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2">Contact Information</label>
            <div class="col-sm-5">
                <div class="form-check">
                    <input name="publishContactInfo" id="publishContactInfo" type="checkbox" class="form-check-input" value="true" <?php
                                                                                                                                    if ($pShowContactInfo) {
                                                                                                                                        echo 'checked';
                                                                                                                                    } ?> />
                    <label class="form-check-label" for="publishContactInfo">
                        Allow contact information to be visible on profile
                    </label>
                </div>
            </div>
        </div>
        <div id="divContactInfo" <?php echo $pContactInfoHtmlDisplay; ?>>
            <div class="form-group row">
                <label class="col-sm-2">Phone Number</label>
                <div class="col-sm-5">
                    <input type="tel" class="form-control" name="phone" value="<?php echo $pPhoneNumber; ?>" />
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2">Email</label>
                <div class="col-sm-5">
                    <input type="email" class="form-control" name="email" value="<?php echo $pEmail; ?>" />
                </div>
            </div>
        </div>
        <br />

        <h3 id="resume">Profile Image</h3>
        <div class="form-group">
            <?php echo $pProfileImageText; ?>
            <div class="image-preview">
                <?php echo $pProfileImagePreviewHtml; ?>
            </div>
            <div class="custom-file col-sm-6 profile-image-input-container">
                <input name="profileImage" type="file" class="custom-file-input" id="profileImage" accept=".png, .jpeg, image/png, image/jpeg">
                <label class="custom-file-label" for="profileImage" id="profileImageLabel">
                    Choose image (PNG or JPEG)
                </label>
            </div>
        </div>
        <br />

        <h3 id="about">About</h3>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">
                Include a summary of your skills, experience, and acheivements
            </label>
            <div class="col-sm-5">
                <textarea name="about" class="form-control" rows="15"><?php echo $pAbout; ?></textarea>
            </div>

        </div>

        <h3 id="links">Links</h3>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Personal Website</label>
            <div class="col-sm-5">
                <input name="websiteLink" type="text" class="form-control" placeholder="Personal Website URL" value="<?php echo $pWebsiteLink; ?>" />
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">GitHub</label>
            <div class="col-sm-5">
                <input name="githubLink" type="text" class="form-control" placeholder="GitHub URL" value="<?php echo $pGitHubLink; ?>" />
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">LinkedIn</label>
            <div class="col-sm-5">
                <input name="linkedInLink" type="text" class="form-control" placeholder="LinkedIn URL" value="<?php echo $pLinkedInLink; ?>" />
            </div>
        </div>
        <br />

        <h3 id="resume">Resume</h3>
        <div class="form-group">
            <?php echo $pResumeHtml; ?>
            <div id="resumeActions" <?php echo $pResumeButtonsStyle; ?>>
                <a href="<?php echo $pResumeLink; ?>" id="aResumeDownload" class="btn btn-primary btn-sm">
                    Download
                </a>
                <button type="button" id="btnResumeDelete" class="btn btn-danger btn-sm">
                    Delete Resume
                </button>
            </div>
            <div class="custom-file col-sm-6">
                <input name="profileResume" type="file" class="custom-file-input" id="profileResume" accept=".pdf, application/pdf">
                <label class="custom-file-label" for="profileResume" id="profileResumeLabel">
                    Choose file (PDF)
                </label>
            </div>
        </div>
        <br />

        <h3 id="projects">Projects</h3>
        <div class="projects-container col-md-8">
            <?php echo $pProjectsMessage; ?>
            <table class="table" <?php echo $pProjectsStyle; ?> id="tableProjects">
                <thead>
                    <th>Title</th>
                    <th>Description</th>
                    <th></th>
                </thead>
                <tbody id="tableBodyProjects">
                    <?php echo $pProjectsHtml; ?>
                </tbody>
            </table>
        </div>
        <div class="form-group form-group-project col-md-6">
            <div class="form-group">
                <label>Title</label>
                <input type="text" class="form-control" id="newProjectTitle" placeholder="Enter project title" />
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="newProjectDescription" class="form-control" rows="3" placeholder="Enter a description about your project"></textarea>
            </div>
            <button type="button" class="btn btn-primary" id="btnAddProject">
                <i class="fas fa-plus"></i>&nbsp;&nbsp;Add Project
            </button>
        </div>

    </form>
</div>


<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>