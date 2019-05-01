<?php

/**
 * Generates the HTML for a project card on a user's profile
 *
 * @param \Model\ShowcaseProject $project the project to display
 * @param boolean $isOwnProject indicates whether the project is owned by the viewer or not
 * @return void
 */
function createProfileProjectHtml($project, $isOwnProject) {
    return "
    <div class='profile-project'>
        <h3 class='project-title'>Title</h3>
        <p class='project-description'></p>
        <button class='btn btn-outline-osu'>
            Details
        </button>
    </div>
    ";
}