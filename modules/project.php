<?php
use Util\Security;

/**
 * Generates the HTML for a project card on a user's profile
 *
 * @param \Model\ShowcaseProject $project the project to display
 * @param boolean $isOwnProject indicates whether the project is owned by the viewer or not
 * @return string the HTML for rendering a profile project
 */
function createProfileProjectHtml($project, $isOwnProject = false) {

    $descriptionCharLimit = 230;
    $titleCharLimit = 27;

    $id = $project->getId();
    $title = $project->getTitle();
    if(strlen($title) > $titleCharLimit) {
        $title = substr($title, 0, $titleCharLimit) . '...';
    }
    $description = $project->getDescription();
    if (strlen($description) > $descriptionCharLimit) {
        $description = substr($description, 0, $descriptionCharLimit) . '...';
    }

    $title = Security::HtmlEntitiesEncode($title);
    $description = Security::HtmlEntitiesEncode($description);

    $keywords = $project->getKeywords();
    $keywordsHtml = '';
    if(count($keywords) > 0) {
        foreach($keywords as $k) {
            $kName = $k->getName();
            $keywordsHtml .= "
            <div>$kName</div>
            ";
        }
    }

    $actionButton = "
    <a href='projects/?id=$id' class='btn btn-outline-osu'>
        Details
    </a>
    ";
    $hiddenAlert = '';
    if($isOwnProject && !$project->isPublished()) {
        $actionButton = "
        <a href='projects/edit?id=$id' class='btn btn-outline-osu'>
            Edit
        </a>
        ";
        $hiddenAlert = "<span class='hidden-alert badge badge-pill badge-danger'><i class='fas fa-eye-slash'></i></span>";
    }

    return "
    <div class='card profile-project-card'>
        <div class='card-body profile-project-card-body'>
            $hiddenAlert
            <h5 class='project-title'>$title</h5>
            <p class='project-description'>$description</p>
            <div class='project-details'>
                <div class='project-tile-keywords'>
                    $keywordsHtml
                </div>
                $actionButton
            </div>
        </div>
    </div>
    ";
}
