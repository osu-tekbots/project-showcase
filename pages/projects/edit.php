<?php
/**
 * Edit showcase projects page
 */
include_once '../../bootstrap.php';

use DataAccess\ShowcaseProjectsDao;
use DataAccess\UsersDao;
use Model\UserType;
use Util\Security;
use DataAccess\KeywordsDao;

if (!isset($_SESSION)) {
    session_start();
}

// Make sure we have the project ID.
// Also redirect if the user is not logged in.
$projectId = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : false;

$baseUrl = $configManager->getBaseUrl();

if (!$projectId || !$isLoggedIn) {
    echo "<script>window.location.replace('$baseUrl');</script>";
    die();
}

$userId = $_SESSION['userID'];

// Fetch the current user
$usersDao = new UsersDao($dbConn, $logger);
$user = $usersDao->getUser($userId);

// Restrict access users who are a part of this project and admins
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$associates = $projectsDao->getProjectCollaborators($projectId);
$found = false;
if ($associates) {
    foreach ($associates as $a) {
        if ($a->getUser()->getId() == $user->getId()) {
            $found = true;
            break;
        }
    }
}
if (!$found && $user->getType()->getId() != UserType::ADMIN) {
    echo "<script>window.location.replace('$baseUrl');</script>";
    die();
}

//
// The user has passed all access checks. Start capturing variables used to
// render the page.
//

// Fetch the project. We don't need to check if it exists or not because that check already happened concurrently while
// checking the project associates. If the project did not exist, no associates would be found, and the `$found`
// variable would have been false, redirecting the user before we got to this point.
$project = $projectsDao->getProject($projectId);

$pTitle = $project->getTitle();
$pDescription = $project->getDescription();

// Check if the project is published or not. If it is not, display an alert informing the user their project has been
// flagged by the admins and is not visible
$pPublishedHtml = $project->isPublished() ? "" : "
    <div class='row'>
        <div class='col'>
            <div class='alert alert-warning'>
                <p><i class='fas fa-eye-slash'></i>&nbsp;&nbsp;This project has been hidden from public view by the website
                administrators. For questions regarding questionable or innapropriate content, please contact an OSU Tekbots
                administrator.</p>
            </div>
        </div>
    </div>
    
";

// Get the tags for the project
$keywordsDao = new KeywordsDao($dbConn, $logger);
$keywords = $keywordsDao->getKeywordsForEntity($projectId);
$noKeywordsTextStyle = "";
$keywordsHtml = "";
$keywordsInputIds = "";
if(count($keywords) > 0) {
    $noKeywordsTextStyle = "style='display: none;'";
    $keywordsInputIds = array();
    $keywordsHtml = "";
    foreach($keywords as $k) {
        $kId = $k->getId();
        $kName = $k->getName();
        $keywordsHtml .= "
        <div class='keyword' id='$kId'>
            $kName
            <i class='fas fa-times-circle' data-id='$kId'></i>
        </div>
        ";
        $keywordsInputIds[] = $kId;
    }
    $keywordsInputIds = join(",", $keywordsInputIds);
}

$allKeywords = $keywordsDao->getAllKeywords();
$allKeywords = array_map(function($k) { 
    $kName = $k->getName();
    $kId = $k->getId();
    return "{id: $kId, name: '$kName' }";
}, $allKeywords);
$allKeywords = join(',', $allKeywords);

// Fetch any images for the project
$pImagePreviewSrc = '';
$pButtonImageDeleteStyle = 'style="display: none;"';
$pButtonImagePreviewStyle = $pButtonImageDeleteStyle;
$pProjectImagesSelectHtml = "
    <select class='image-picker' id='selectProjectImages'>
";
$pImages = $project->getImages();
$first = true;
foreach ($pImages as $i) {
    $id = $i->getId();
    $name = $i->getFileName();
    $selected = $first ? 'selected' : '';
    $pProjectImagesSelectHtml .= "
        <option 
            $selected
            id='$id'
            data-img-src='downloaders/project-images?id=$id'
            data-img-class='project-image-thumbnail'
            data-img-alt='$name'
            value='$id'>
            $name
        </option>
    ";
    if ($first) {
        $pButtonImageDeleteStyle = '';
        $pButtonImagePreviewStyle = '';
        $pImagePreviewSrc = "downloaders/project-images?id=$id";
        $first = false;
    }
}
$pProjectImagesSelectHtml .= '
    </select>
';

// Fetch the artifacts for the project
$pArtifacts =$project->getArtifacts();
if (count($pArtifacts) == 0) {
    $pArtifactsHtml = '<p id="pNoArtifacts">There are no artifacts associated with this project</p>';
} else {
    $pArtifactsHtml = "
        <table class='table table-artifacts'>
            <thead>
                <th>Name</th>
                <th>Description</th>
                <th>Content</th>
                <th></th>
            </thead>
            <tbody id='tableBodyArtifacts'>
    ";

    foreach ($pArtifacts as $artifact) {
        $aId = $artifact->getId();
        $aName = $artifact->getName();
        $aDescription = $artifact->getDescription();
        if ($artifact->isFileUploaded()) {
            $aContentHtml = "
                <a href='downloaders/artifacts?id=$aId'>Download Artifact File</a>
             ";
        } else {
            $aLink = Security::ValidateUrl($artifact->getLink());
            $aContentHtml = "
                <a href='$aLink' target='_blank'>Link to Artifact</a>
            ";
        }
        
        $pArtifactsHtml .= "
            <tr class='artifact-row' id='$aId'>
                <td>$aName</td>
                <td>$aDescription</a>
                <td>$aContentHtml</td>
                <td class='artifact-actions'>
                    <button type='button' class='btn btn-sm btn-danger btn-delete-artifact' data-id='$aId'>
                        <i class='fas fa-trash'></i>
                    </button>
                </td>
            </tr>

        ";
    }

    $pArtifactsHtml .= '
            </tbody>
        </table>
    ';
}

// Fetch all of the collaborators on this project
$collaborators = $projectsDao->getProjectCollaborators($projectId, true);
$collaboratorsRowsHtml = '';
// The first row is the current user. We should allow them to edit their visibility
$cName = Security::HtmlEntitiesEncode($user->getFullName());
$cId = $user->getId();
$cVisibleButton = $projectsDao->verifyUserIsCollaboratorOnProject($projectId, $userId, true) ? "
    <button type='button' class='btn btn-sm btn-success' id='btnToggleVisibility' data-visible='true' 
        data-toggle='tooltip' data-placement='right' title='Toggle visibility'>
        <i class='far fa-check-circle'></i>&nbsp;&nbsp;Visible
    </button>
" : "
    <button type='button' class='btn btn-sm btn-light' id='btnToggleVisibility' data-visible='false'
        data-toggle='tooltip' data-placement='right' title='Toggle visibility'>
        <i class='far fa-times-circle'></i>&nbsp;&nbsp;Not Visible
    </button>
";

$collaboratorsRowsHtml .= "
<tr>
    <td><strong>$cName</strong></td>
    <td class='publically-visible'>
        $cVisibleButton
    </td>
    <td class='actions'>
        <a href='profile/?id=$cId' class='btn btn-sm btn-light'>
            View Profile
        </a>
    </td>
</tr>
";
foreach ($collaborators as $c) {
    $cId = $c->getUser()->getId();
    $cName = Security::HtmlEntitiesEncode($c->getUser()->getFullName());

    if ($cId != $userId) {
        $collaboratorsRowsHtml .= "
        <tr>
            <td>$cName</td>
            <td class='publically-visible'></td>
            <td class='actions'>
                <a href='profile/?id=$cId' class='btn btn-sm btn-light'>
                    View Profile
                </a>
            </td>
        </tr>
        ";
    }
}



$title = 'Edit Project';
$css = array(
    'assets/css/image-picker.css',
    'assets/css/projects-edit.css'
);
$js = array(
    'assets/js/image-picker.min.js',
    array(
        'src' => 'assets/js/projects-edit.js',
        'defer' => 'true'
    )
);
include_once PUBLIC_FILES . '/modules/header.php';
?>

<div class="container">
    <h1>Edit Project</h1>
    <a href="projects/?id=<?php echo $projectId; ?>" class="btn btn-sm btn-light">
        <i class="fas fa-chevron-left"></i>&nbsp;&nbsp;View Project
    </a>
    <br/>
    <br/>

    <?php echo $pPublishedHtml; ?>

    <form id="formEditProjectGeneral">
        <input type="hidden" name="projectId" id="projectId" value="<?php echo $projectId; ?>" />

        <h3 id="general">General</h3>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Title</label>
            <div class="col-sm-5">
                <input required name="title" type="text" class="form-control" placeholder="Enter project title" 
                    value="<?php echo $pTitle; ?>" />
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Description</label>
            <div class="col-sm-7">
                <textarea required name="description" type="text" class="form-control" 
                    placeholder="Enter description for project" rows="18"><?php echo $pDescription; ?></textarea>
            </div>         
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Keywords <br/>(<i>only provided keywords are acceptable</i>)</label>
            <div class="col-sm-7 autocomplete">
                <div class="project-keywords">
                    <i id="noKeywordsText" <?php echo $noKeywordsTextStyle; ?>>
                    No keywords have been associated with this project</i>
                    <?php echo $keywordsHtml; ?>
                </div>
                <div class="project-keywords-input">
                    <input type="hidden" name="keywords" value="<?php echo $keywordsInputIds; ?>" />
                    <input type="text" class="form-control" id="keywords" placeholder="Search available keywords" />
                </div>
            </div>
            <script>
                const keywords = [<?php echo $allKeywords; ?>];
                keywords.sort((a, b) => {
                    let n1 = a.name.toLowerCase();
                    let n2 = b.name.toLowerCase();
                    return n1 < n2 ? -1 : n1 === n2 ? 0 : 1;
                });
            </script>
        </div>
        <div class="form-group row">
            <div class="col-sm-7 offset-sm-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>&nbsp;&nbsp;Save Changes
                </button>
            </div>
        </div>
        <br/>
    </form>

    <h3 id="images">Images</h3>
    <p><i class="fas fa-info-circle"></i><i>&nbsp;&nbsp;You can upload images to accompany your project's profile.
        Images must be no larger than 5MB. Please limit the number of images per project to 10.</i>
    </p>
    <div class="edit-project-images-container">
        <button type="button" class="btn btn-sm btn-danger" id="btnDeleteSelectedImage" 
            <?php echo $pButtonImageDeleteStyle; ?>>
            <i class="fas fa-trash"></i>&nbsp;&nbsp;Delete Selected Image
        </button>
        <div class="project-images-select-container">
            <?php echo $pProjectImagesSelectHtml; ?>
        </div>
        <form id="formAddNewImage">
            <input type="hidden" name="projectId" value="<?php echo $projectId; ?>" />
            <div class="form-group row custom-file-row" id="divNewArtifactFile">
                <div class="custom-file col-md-4">
                    <input required name="imageFile" type="file" class="custom-file-input" id="imageFile">
                    <label class="custom-file-label" for="imageFile" id="labelImageFile">
                        Choose a new image to upload
                    </label>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-4 row-project-image-submit">
                    <button type="submit" id="btnUploadImage" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload"></i>&nbsp;&nbsp;Upload
                    </button>
                    <div class="loader" id="formAddNewImageLoader"></div>
                </div>
            </div>
        </form>
        <h6>Image Preview</h6>
        <img id="projectImagePreview" src="<?php echo $pImagePreviewSrc; ?>" <?php echo $pButtonImagePreviewStyle; ?>>
    </div>

    <h3 id="artifacts">Artifacts</h3>
    <p><i class="fas fa-info-circle"></i>&nbsp;&nbsp;<i>Artifacts represent the concrete results 
        of a project. These results can be documents, links, videos, pictures, or other files associated with the 
        project.</i></p>
    <div class="edit-artifacts-container">
        <?php echo $pArtifactsHtml; ?>
        <div class="add-new-artifact-container col-sm-7" id="divAddNewArtifactContainer">
            <form id="formAddNewArtifact">
                <input type="hidden" name="projectId" value="<?php echo $projectId; ?>" />
                <div class="form-group row">
                    <div class="col-sm-8">
                        <input required type="text" name="name" class="form-control" 
                            placeholder="Artifact Name" />
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-12">
                        <textarea required name="description" class="form-control" 
                            placeholder="Artifact description" rows="2"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="artifactType" id="artifactType" 
                            value="file" checked>
                        <label class="form-check-label" for="artifactType">
                            File
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="artifactType" id="artifactType" 
                            value="link">
                        <label class="form-check-label" for="artifactType">
                            Link
                        </label>
                    </div>
                </div>
                <div class="form-group row custom-file-row" id="divNewArtifactFile">
                    <div class="custom-file col-sm-8">
                        <input required name="artifactFile" type="file" class="custom-file-input" id="artifactFile">
                        <label class="custom-file-label" for="artifactFile" id="labelArtifactFile">
                            Choose artifact file
                        </label>
                    </div>
                </div>
                <div class="form-group row" id="divNewArtifactLink" style="display: none;">
                    <div class="col-sm-8">
                        <input name="artifactLink" type="text" class="form-control" placeholder="Link for artifact" />
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-12">
                        <button type="submit" id="btnAddArtifact" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i>&nbsp;&nbsp;Artifact
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
    </div>
    <br/>

    <h3 id="collaborators">Collaborators</h3>
    <p class="col-sm-8"><i class="fas fa-info-circle"></i>&nbsp;&nbsp;<i>Collaborators are other students who
        participated in the development of the project and should share credit for the project in the showcase.
        Students who are invited to participate will have the opportunity to accept the invitation and have the
        project appear on their profile.
    </i></p>
    <div class="form-group row">
        <div class="col-md-8">
            <table class="table table-collaborators">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="publically-visible"></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $collaboratorsRowsHtml; ?>
                </tbody>
                <tfoot>
                    <form id="formSendInvite">
                        <input type="hidden" name="projectId" value="<?php echo $projectId; ?>" />
                        <input type="hidden" name="userId" id="userId" value="<?php echo $userId; ?>" />
                        <tr>
                            <td colspan="2">
                                <input required type="email" class="form-control" name="email" value=""  
                                    placeholder="Enter email address" />
                            </td>
                            <td class="actions">
                                <button type="submit" id="btnSendInvite" class="btn btn-sm btn-primary">
                                    <i class="fas fa-paper-plane"></i>&nbsp;&nbsp;Send Invite
                                </button>
                            </td>
                        </tr>
                    </form>
                </tfoot>
            </table>
        </div>
    </div> 
    
</div>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>