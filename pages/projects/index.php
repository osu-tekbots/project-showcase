<?php
/**
 * View showcase projects page
 */
include_once '../../bootstrap.php';

use DataAccess\ShowcaseProjectsDao;
use DataAccess\VoteDao;
use DataAccess\ShowcaseProfilesDao;
use Util\Security;
use DataAccess\KeywordsDao;
use Model\UserType;
use Model\Award;


//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

if (!isset($_SESSION)) {
    session_start();
}

if ($isLoggedIn)
	if (!isset($_SESSION['userType']))
		$_SESSION['userType'] = UserType::STUDENT;


function renderLiftButton($projectId, $userId, $voteDao){
	if ($voteDao->checkVote($projectId, $userId) == true){
		$buttonText = 'Lifted!';
		$buttonClass = 'btn btn-sm btn-success';
		$buttonTooltip = 'You lifted this project because you think it is awesome! Click again to remove Lift.';
	} else {
		$buttonText = 'Lift?';
		$buttonClass = 'btn btn-sm';
		$buttonTooltip = 'You can Lift a project so that others can more easily find this awesome project!';
	}
	
	
	$script = "<script>
			function onVote(pid, uid) {
				if ($('#voteIcon').data('state') == 'Lifted!'){ //Already voted 
					var action = 'removeVote';
					$('#voteIcon').data('state', '');
					$('#voteIcon').attr('class','btn btn-sm');
					$('#voteIcon').text('Lift?');
					$('#voteIcon').tooltip('hide').attr('data-original-title', 'You can Lift a project so that others can more easily find this awesome project!').tooltip('show');	
				} else {
					var action = 'addVote';
					$('#voteIcon').data('state', 'Lifted!');
					$('#voteIcon').attr('class','btn btn-sm btn-success');
					$('#voteIcon').text('Lifted!');
					$('#voteIcon').tooltip('hide').attr('data-original-title', 'You lifted this project because you think it is awesome! Click again to remove Lift.').tooltip('show');	
				}
				
				body = {
					action: action,
					projectId: pid,
					userId: uid
				};
				api.post('/showcase-votes.php', body).then(res => {
					snackbar(res.message, 'success');
					location.reload();
				}).catch(err => {
					snackbar(err.message, 'error');
				});				
			}
			</script>";
	$html = "<button onclick='onVote(\"$projectId\",\"$userId\");' id='voteIcon' style='border:1px solid black;' class='$buttonClass' 
			data-state='$buttonText' data-toggle='tooltip' data-placement='left' title='$buttonTooltip'>
				$buttonText
			</button>";
	
	return $script . $html;
}

// Make sure we have the project ID
$projectId = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : false;
$baseUrl = $configManager->getBaseUrl();
if (!$projectId) {
    echo "<script>window.location.replace('$baseUrl');</script>";
    die();
}

// $userId = isset($_SESSION['userID']) ? $_SESSION['userID'] : false;
$profilesDao = new ShowcaseProfilesDao($dbConn, $logger);
$userId = $profilesDao->getUserIdFromOnid($_SESSION['auth']['id']); // TEMPORARY FIX for login issues across eecs sites

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
$voteDao = new VoteDao($dbConn, $logger);
$project = $projectsDao->getProject($projectId);
if (!$project) { //Removed check for (!$project->isPublished() && $_SESSION['userType'] != UserType::ADMIN)
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
	//Update Score
	//TODO: Need to define a formula that calculates a score based on Lifts but decreases as projects get older
	$project->setScore(count($voteDao->getAllVotesByProject($projectId)));
	$projectsDao->updateProject($project);
	
    // Awards
	$pAwardsHtml = '';
	$awards = $projectsDao->getProjectAwards($projectId);
	if ($awards != false) {
		$pAwardsHtml = "
		<div class='showcase-project-artifacts row justify-content-md-center'>
            <div class='col-md-8'>
                <h3>Awards</h3>"; 
		foreach ($awards as $award){
			if ($award->getImageNameSquare() != '')
				$pAwardsHtml .= "<img class='img-responsive col-md-2' src='assets/img/".$award->getImageNameSquare()."' title='".$award->getName()."'>";
			else
				$pAwardsHtml .= "<div class='col-md-1'>" . $award->getName() . "<div>";
			
		}
        $pAwardsHtml .= "
			</div>
        </div>
		";
	}
	
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
    $collaboratorIsUser = $userId != false ? $projectsDao->verifyUserIsCollaboratorOnProject($projectId, $userId) : false;
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
    
    $editButtonHtml = $collaboratorIsUser || ($isLoggedIn && $_SESSION['userType'] == UserType::ADMIN) ? "
        <a href='projects/edit.php?id=$projectId' class='btn btn-sm btn-light'>
            <i class='fas fa-edit'></i>&nbsp;&nbsp;Edit
        </a>
    " : '';
	
	
	
	$publishButtonHtml = '';
	if ($isLoggedIn && $_SESSION['userType'] == UserType::ADMIN){
		$published = $project->isPublished();
		if ($published) {
			$publishedButtonText = 'Published';
			$publishedButtonClass = 'btn-success';
			$publishedButtonTooltip = 'Hide';
		} else {
			$publishedButtonText = 'Hidden';
			$publishedButtonClass = 'btn-danger';
			$publishedButtonTooltip = 'Publish';
		}
		$publishButtonHtml = "
			<script>
			function onPublish(id) {
				let published = $('#publishButton').data('published');
				body = {
					action: 'updateVisibility',
					publish: !published,
					id
				};
				api.post('/showcase-projects.php', body).then(res => {
					snackbar(res.message, 'success');
					$('#publishButton').data('published', !published);
					if(published) {
						$('#publishButton').removeClass('btn-success').addClass('btn-danger');
						$('#publishButton').text('Hidden');	
						$('#publishButton').tooltip('hide').attr('data-original-title', 'Publish').tooltip('show');						
					} else {
						$('#publishButton').removeClass('btn-danger').addClass('btn-success');
						$('#publishButton').text('Published');
						$('#publishButton').tooltip('hide').attr('data-original-title', 'Hide').tooltip('show');
					}
				}).catch(err => {
					snackbar(err.message, 'error');
				});
				
				
			}
			</script>
			
			<button onclick='onPublish(\"$projectId\");' id='publishButton' class='btn btn-sm $publishedButtonClass btn-published' data-published='$published'
				data-toggle='tooltip' data-placement='left' title='$publishedButtonTooltip'>
				$publishedButtonText
			</button>
		";		
	}

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
                <img src='downloaders/project-images.php?id=$imageId' />
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
                <a href='downloaders/artifacts.php?id=$id' class='btn btn-sm btn-primary'>
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
            $editButtonHtml $publishButtonHtml
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
		<div class='row justify-content-md-center'>
            <div class='col-md-8 text-center'>
			<span data-toggle='tooltip' data-placement='left' title='Authenticated users can Lift projects to make them more visible to people browsing the showcase site.'>".count($voteDao->getAllVotesByProject($projectId)) ." Lifts</span>&nbsp" . (isset($_SESSION['userID']) ? renderLiftButton($projectId, $userId, $voteDao) : '')."
			</div>
		</div>
		$pAwardsHtml
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
