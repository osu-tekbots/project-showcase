<?php
use Util\Security;

/**
 * Generates the HTML for a project card on a user's profile
 *
 * @param \Model\ShowcaseProject $project the project to display
 * @param boolean $isOwnProject indicates whether the project is owned by the viewer or not
 * @return string the HTML for rendering a profile project
 */
function createProfileProjectHtml($project, $isOwnProject) {

    $descriptionCharLimit = 250;

    $id = $project->getId();
    $title = $project->getTitle();
    if(strlen($title) > 30) {
        $descriptionCharLimit = 200;
    }
    $description = $project->getDescription();
    if (strlen($description) > $descriptionCharLimit) {
        $description = substr($description, 0, $descriptionCharLimit) . '...';
    }

    $title = Security::HtmlEntitiesEncode($title);
    $description = Security::HtmlEntitiesEncode($description);

    return "
    <div class='profile-project'>
        <h3 class='project-title'>$title</h3>
        <p class='project-description'>$description</p>
        <a href='projects/?id=$id' class='btn btn-outline-osu project-details'>
            Details
        </a>
    </div>
    ";
}
