<?php

/**
 * Generates the HTML for a project card on a user's profile
 *
 * @param \Model\ShowcaseProject $project the project to display
 * @param boolean $isOwnProject indicates whether the project is owned by the viewer or not
 * @return string the HTML for rendering a profile project
 */
function createProfileProjectHtml($project, $isOwnProject) {

    $title = $project->getTitle();
    $description = $project->getDescription();
    if(strlen($description) > 340) {
        $description = substr($description, 0, 300) . '...';
    }

    return "
    <div class='profile-project col-md-4'>
        <h3 class='project-title'>$title</h3>
        <p class='project-description'>$description</p>
        <button class='btn btn-outline-osu project-details'>
            Details
        </button>
    </div>
    ";
}