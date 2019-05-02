<?php
use DataAccess\ShowcaseProfilesDao;

if(!isset($_SESSION)) {
    session_start();
}

if(!$isLoggedIn) {
    echo "<script>window.location.replace('');</script>";
    die();
}

$userId = $_SESSION['userID'];

// Get the user profile information
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$profile = $profilesDao->getUserProfileInformation($userId);
if(!$profile) {
    echo "<script>window.location.replace('');</script>";
    die();
}

$pFirstname = $profile->getUser()->getFirstName();
$pLastName = $profile->getUser()->getLastName();
$pMajor = $profile->getUser()->getMajor();

$pShowContactInfo = $profile->canShowContactInfo();

$pAbout = $profile->getAbout();

$pHasProfileImage = $profile->isImageUploaded();
$pProfileImageHtml = $pHasProfileImage ? "
    <p id='profileImageText'>Current Profile Image</p>
" : "
    <p id='profileImageText'>No Image has been uploaded</p>
";

$pProfileImageLink = $pHasProfileImage ? "downloaders/profile-images?id=$userId" : '';
$pProfileImagePreviewStyle = $pHasProfileImage ? '' : "style='display: none;'";
$pProfileImageDeleteStyle = $pHasProfileImage ? '' : "style='display: none;'";

$pWebsiteLink = $profile->getWebsiteLink();
$pGitHubLink = $profile->getGithubLink();
$pLinkedInLink = $profile->getLinkedInLink();

$pHasResume = $profile->isResumeUploaded();
$pResumeLink = $pHasResume ? "downloaders/resumes?id=$userId" : '';
$pResumeHtml = $pHasResume ? "
    <p id='resumeText'>You have uploaded a resume. <a href='$pResumeLink'>Download</a></p>
" : "
    <p id='resumeText'>No resume has been uploaded</p>
";





$title = 'Edit Profile';
$css = array(
    'assets/css/profile-edit.css'
);
$js = array(
    array(
        'src' => 'assets/js/profile-edit.js',
        'defer' => 'true'
    )
);
include_once PUBLIC_FILES . '/modules/header.php';

?>


<div class="container">
    <h1>Edit Profile</h1>
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
                <input required name="firstName" type="text" class="form-control" placeholder="First Name" 
                    value="<?php echo $pFirstname; ?>" />
            </div>         
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Last Name</label>
            <div class="col-sm-5">
                <input required name="lastName" type="text" class="form-control" placeholder="Last Name" 
                    value="<?php echo $pLastName; ?>" />
            </div>         
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Major</label>
            <div class="col-sm-5">
                <input required name="major" type="text" class="form-control" placeholder="Major" 
                    value="<?php echo $pMajor; ?>" />
            </div>         
        </div>
        <div class="form-group row">
            <label class="col-sm-2">Contact Information</label>
            <div class="col-sm-10">
                <div class="form-check">
                    <input name="publishContactInfo" id="publishContactInfo" type="checkbox" class="form-check-input" 
                        value="true" <?php if ($pShowContactInfo) echo 'checked'; ?>>
                    <label class="form-check-label" for="publishContactInfo">
                        Allow contact information to be visible on profile
                    </label>
                </div>
            </div>
        </div>
        <br/>

        <h3 id="resume">Profile Image</h3>
        <div class="form-group">
            <?php echo $pProfileImageHtml; ?>
            <div class="image-preview">
                <img id="profileImagePreview" src="<?php echo $pProfileImageLink; ?>" 
                    <?php echo $pProfileImagePreviewStyle; ?> />
                <button type="button" class="btn btn-danger" id="btnProfileImageDelete" 
                    <?php echo $pProfileImageDeleteStyle; ?>>
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <div class="custom-file col-sm-6 profile-image-input-container">
                <input name="profileImage" type="file" class="custom-file-input" id="profileImage"
                    accept=".png, .jpeg, image/png, image/jpeg">
                <label class="custom-file-label" for="profileImage" id="profileImageLabel">
                    Choose image (PNG or JPEG)
                </label>
            </div>
        </div>
        <br/>

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
                <input name="websiteLink" type="text" class="form-control" placeholder="Personal Website URL" 
                    value="<?php echo $pWebsiteLink; ?>" />
            </div>         
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">GitHub</label>
            <div class="col-sm-5">
                <input name="githubLink" type="text" class="form-control" placeholder="GitHub URL" 
                    value="<?php echo $pGitHubLink; ?>" />
            </div>         
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">LinkedIn</label>
            <div class="col-sm-5">
                <input name="linkedInLink" type="text" class="form-control" placeholder="LinkedIn URL" 
                    value="<?php echo $pLinkedInLink; ?>" />
            </div>         
        </div>
        <br/>

        <h3 id="resume">Resume</h3>
        <div class="form-group">
            <?php echo $pResumeHtml; ?>
            <div class="custom-file col-sm-6">
                <input name="profileResume" type="file" class="custom-file-input" id="profileResume"
                    accept=".pdf, application/pdf">
                <label class="custom-file-label" for="profileResume" id="profileResumeLabel">
                    Choose file (PDF)
                </label>
            </div>
        </div>
        <br/>

    </form>
</div>


<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
