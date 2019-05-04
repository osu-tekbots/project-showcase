<?php

/**
 * Generates the HTML for a project card on a user's profile
 *
 * @param \Model\ShowcaseProject $project the project to display
 * @param boolean $isOwnProject indicates whether the project is owned by the viewer or not
 * @return string the HTML for rendering a profile project
 */
function createProfileProjectHtml($project, $isOwnProject) {

    $descriptionCharLimit = 280;

    $id = $project->getId();
    $title = $project->getTitle();
    if(strlen($title) > 30) {
        $descriptionCharLimit = 220;
    }
    $description = $project->getDescription();
    if (strlen($description) > $descriptionCharLimit) {
        $description = substr($description, 0, $descriptionCharLimit) . '...';
    }

    return "
    <div class='profile-project col-md-4'>
        <h3 class='project-title'>$title</h3>
        <p class='project-description'>$description</p>
        <a href='projects/?id=$id' class='btn btn-outline-osu project-details'>
            Details
        </a>
    </div>
    ";
}
