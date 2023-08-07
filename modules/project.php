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
        $title = substr($title, 0, $titleCharLimit - 3) . '...';
    }
    $description = $project->getDescription();
    if (strlen($description) > $descriptionCharLimit) {
        $description = substr($description, 0, $descriptionCharLimit - 3) . '...';
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
	
	// Awards
	$pAwardsHtml = '';
	$awards = $project->getAwards();
	if ($awards != null){
		if(count($awards) > 0) {
			foreach ($awards as $award){
				if ($award->getImageNameSquare() != '')
					$pAwardsHtml .= "<div class='award_overlay'><img class='' style='width:3em;' src='assets/img/".$award->getImageNameSquare()."' title='".$award->getName()."'></div>";
			}
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
        $hiddenAlert = "<span class='hidden-alert badge badge-pill badge-danger'><i class='fas fa-eye-slash' data-toggle='tooltip' data-placement='top' title='This project is hidden from search. Either you requested this or it does not meet the community standards for the showcase. Add more content if you want it searchable.'></i></span>";
    }

    return "
    <div class='card profile-project-card'>
        <div class='card-body profile-project-card-body'>
            $hiddenAlert
            <h5 class='project-title'>$title</h5>
            <p class='project-description'>$description</p>
            <div class='project-details'>
                <div class='project-tile-keywords'>
                    $pAwardsHtml $keywordsHtml
                </div>
                $actionButton
            </div>
        </div>
    </div>
    ";
}
