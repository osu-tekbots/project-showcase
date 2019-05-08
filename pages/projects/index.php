<?php
use DataAccess\ShowcaseProjectsDao;

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
    'assets/css/showcase-project.css'
);
include_once PUBLIC_FILES . '/modules/header.php';


// Fetch the showcase project and artifacts
$projectsDao = new ShowcaseProjectsDao($dbConn, $logger);
$project = $projectsDao->getProject($projectId);
if (!$project) {
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
    $pTitle = $project->getTitle();
    $pDescription = $project->getDescription();

    // Collaborators
    $pCollaborators = $projectsDao->getProjectCollaborators($projectId, true);
    $pCollaboratorsHtml = '<h4>';
    $collaboratorIsUser = $projectsDao->verifyUserIsCollaboratorOnProject($projectId, $userId);
    $numCollaborators = count($pCollaborators);
    for ($i = 0; $i < $numCollaborators ; $i++) {

        if($numCollaborators > 1) {
            if($i == $numCollaborators - 1) {
                $pCollaboratorsHtml .= ' <span class="small-font">and</span> ';
            } elseif ($i != 0) {
                $pCollaboratorsHtml .= '<span class="small-font">,</span> ';
            }
        }
        

        $c = $pCollaborators[$i];
        $name = $c->getUser()->getFullName();

        $pCollaboratorsHtml .= $name;

    }
    $pCollaboratorsHtml .= '</h4>';
    
    $editButtonHtml = $collaboratorIsUser ? "
        <a href='projects/edit?id=$projectId' class='btn btn-sm btn-light'>
            <i class='fas fa-edit'></i>&nbsp;&nbsp;Edit
        </a>
    " : '';
    
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
            $name = $a->getName();
            $description = $a->getDescription();
            $link = $a->getLink();
            $linkHtml = $a->isFileUploaded() ? "
                <a href='downloaders/artifacts?id=$id' class='btn btn-sm btn-primary'>
                    <i class='fas fa-download'></i>&nbsp;&nbsp;Download
                </a>
            " : "
                <a href='$link' target='_blank' class='btn btn-sm btn-primary'>
                    <i class='fas fa-external-link-alt'></i>&nbsp;&nbsp;View
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
            <h6><i>By</i></h6>
            <div class='collaborators-container'>
                $pCollaboratorsHtml
            </div>
        </div>
        <div class='showcase-project-description row justify-content-md-center'>
            <div class='col-md-8'>
                <p>$pDescription</p>
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
