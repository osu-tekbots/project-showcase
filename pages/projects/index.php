<?php
/**
 * View showcase projects page
 */
include_once '../../bootstrap.php';

use DataAccess\ShowcaseProjectsDao;
use Util\Security;
use DataAccess\KeywordsDao;
use Model\UserType;

if (!isset($_SESSION)) {
    session_start();
}

// Make sure we have the project ID
$projectId = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : false;
$baseUrl = $configManager->getBaseUrl();
if (!$projectId) {
    echo "<script>window.location.replace('$baseUrl');</script>";
    die();
}

$userId = isset($_SESSION['userID']) ? $_SESSION['userID'] : false;

//
// Include the header in case we need to render a 'Not Found' message
//
$title = 'Showcase Project';
$css = array(
    'assets/css/showcase-project.css',
    'assets/css/slideshow.css'
);
$js = array(
    array(
        'src' => 'assets/js/slideshow.js',
        'defer' => 'true'
    )
);
include_once PUBLIC_FILES . '/modules/header.php';


// Fetch the showcase project and artifacts
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$project = $projectsDao->getProject($projectId);
if (!$project || (!$project->isPublished() && $_SESSION['userType'] != UserType::ADMIN)) {
    // The project was not found, let the user know
    echo "
    
    <div class='container'>
        <div class='row'>
            <div class='col'>
                <h1>Whoops!</h1>
                <p>We weren't able to find the project you were looking for. Try returning to <a href=''>the home
                page</a>.</p>
            </div>
        </div>
    </div>

    ";
} else {
    // General information
    $pTitle = Security::HtmlEntitiesEncode($project->getTitle());
    $pDescription = Security::HtmlEntitiesEncode($project->getDescription());

    // Keywords
    $keywordsDao = new KeywordsDao($dbConn, $logger);
    $keywords = $keywordsDao->getKeywordsForEntity($project->getId());
    $keywordsHtml = "";
    if(count($keywords) > 0) {
        $keywordsHtml = "
            <div class='row justify-content-center fade-in'>
                <div class='col-md-8 keywords'>
        ";

        foreach($keywords as $k) {
            $kName = $k->getName();
            $keywordsHtml .= "<div>$kName</div>";
        }

        $keywordsHtml .= "
                </div>
            </div>
        ";
    }

    // Collaborators
    $pCollaborators = $projectsDao->getProjectCollaborators($projectId, true);
    $pCollaboratorsHtml = '';
    $collaboratorIsUser = $projectsDao->verifyUserIsCollaboratorOnProject($projectId, $userId);
    $numCollaborators = count($pCollaborators);
    if($numCollaborators > 0) {
        $pCollaboratorsHtml = '<h4>';
        for ($i = 0; $i < $numCollaborators ; $i++) {
            if ($numCollaborators > 1) {
                if ($i == $numCollaborators - 1) {
                    $pCollaboratorsHtml .= ' <span class="small-font">and</span> ';
                } elseif ($i != 0) {
                    $pCollaboratorsHtml .= '<span class="small-font">,</span> ';
                }
            }
    
            $c = $pCollaborators[$i];
            $name = Security::HtmlEntitiesEncode($c->getUser()->getFullName());
            $cId = $c->getUser()->getId();
    
            $pCollaboratorsHtml .= "<a class='collaborator-link' href='profile/?id=$cId'>$name</a>";
        }
        $pCollaboratorsHtml .= '</h4>';

        $pCollaboratorsHtml = "
        <h6><i>By</i></h6>
        <div class='collaborators-container'>
            $pCollaboratorsHtml
        </div>
        ";
    }
    
    $editButtonHtml = $collaboratorIsUser || $_SESSION['userType'] == UserType::ADMIN ? "
        <a href='projects/edit?id=$projectId' class='btn btn-sm btn-light'>
            <i class='fas fa-edit'></i>&nbsp;&nbsp;Edit
        </a>
    " : '';

    // Gather the images and generate the HTML to render them in a slideshow
    $pImagesHtml = '';
    $pImagesDotsHtml = '';
    $pImagesHeaderHtml = '';
    $i = 1;
    $numImages = count($project->getImages());
    foreach ($project->getImages() as $image) {
        $count = $i . ' / ' . $numImages;
        $imageId = $image->getId();

        $pImagesHtml .= "
            <div class='slide fade'>
                <img src='downloaders/project-images?id=$imageId' />
            </div>
        ";

        if($numImages > 1) {
            if ($i == 1) {
                $pImagesDotsHtml = "
                    <div class='dot-container'>
                ";
            }
    
            $pImagesDotsHtml .= "
                <span class='dot' onclick='currentSlide($i)'></span>
            ";
        }

        $i++;
    }

    if ($numImages > 1) {
        $pImagesDotsHtml .= '
            </div>
        ';
        $pImagesHtml .= "
            <a class='prev' onclick='plusSlides(-1)'>&#10094;</a>
            <a class='next' onclick='plusSlides(1)'>&#10095;</a>
        ";
    }
    
    // Artifacts
    $pArtifacts = $project->getArtifacts();
    $pArtifactsHtml = '';
    if (count($pArtifacts) == 0) {
        $pArtifactsHtml = '
            <p><i>There are no artifacts for this project</i></p>
        ';
    } else {
        $pArtifactsHtml = "
            <table class='table'>
                <thead>
                    <th>Name</th>
                    <th>Description</th>
                    <th></th>
                </thead>
                <tbody>
        ";

        foreach ($pArtifacts as $a) {
            $id = $a->getId();
            $name = Security::HtmlEntitiesEncode($a->getName());
            $description = Security::HtmlEntitiesEncode($a->getDescription());
            $link = Security::ValidateUrl($a->getLink());
            $linkHtml = $a->isFileUploaded() ? "
                <a href='downloaders/artifacts?id=$id' class='btn btn-sm btn-primary'>
                    <i class='fas fa-download'></i>&nbsp;&nbsp;Download
                </a>
            " : "
                <a href='$link' target='_blank' class='btn btn-sm btn-primary'>
                    <i class='fas fa-external-link-alt'></i>&nbsp;&nbsp;Link
                </a>
            ";

            $pArtifactsHtml .= "
                <tr>
                    <td>$name</td>
                    <td>$description</td>
                    <td>$linkHtml</td>
                </tr>
            ";
        }

        $pArtifactsHtml .= '
                </tbody>
            </table>
        ';
    }

    echo "
    
    <div class='container showcase-project-container'>
        <div class='showcase-project-header'>
            <h1>$pTitle</h1>
            $editButtonHtml
        </div>
        <div class='showcase-project-collaborators'>
            $pCollaboratorsHtml
        </div>
        $keywordsHtml
        <div class='showcase-project-description row justify-content-md-center'>
            <div class='col-md-8'>
                <p>$pDescription</p>
            </div>
        </div>
        <div class='showcase-project-images row justify-content-md-center'>
            <div class='col-md-8'>
                $pImagesHeaderHtml
                <div class='slideshow-container'>
                    $pImagesHtml
                    $pImagesDotsHtml
                </div>
            </div>
        </div>
        <div class='showcase-project-artifacts  row justify-content-md-center'>
            <div class='col-md-8'>
                <h3>Artifacts</h3>
                $pArtifactsHtml
            </div>
        </div>
    </div>

    ";
}

include_once PUBLIC_FILES . '/modules/footer.php';
