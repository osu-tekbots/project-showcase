<?php
namespace Api;

use DataAccess\FlagDao;
use Model\ShowcaseProject;
use Model\CollaborationInvitation;
use Model\Keyword;
use Model\Award;
use Model\Flag;

/**
 * Defines the logic for how to handle AJAX requests with JSON bodies made to modify showcase project information.
 */
class ShowcaseProjectsActionHandler extends ActionHandler {

    /** @var \DataAccess\ShowcaseProjectsDao */
    private $projectsDao;

	/** @var \DataAccess\AwardDao */
    private $awardDao;

	/** @var DatabaseConnection */
    private $conn;

    /** @var \DataAccess\UsersDao */
    private $usersDao;

	/** @var \DataAccess\CategoryDao */
    private $categoryDao;

    /** @var \DataAccess\KeywordsDao */
    private $keywordsDao;

    /** @var \Email\CollaborationMailer */
    private $mailer;

    /**
     * Constructs a new instance of the action handler for requests on user resources.
     *
     * @param \DataAccess\ShowcaseProjectsDao $projectsDao the data access object for showcase projects
     * @param \DataAccess\UsersDao $usersDao the data access object for users
     * @param \DataAccess\KeywordsDao $keywordsDao the data access object for keywords
     * @param \Email\CollaborationMailer  $mailer the Mailer class providing email functionality for collaboration
     * @param \Util\Logger $logger the logger to use for logging information about actions
     */
    public function __construct($conn, $projectsDao, $usersDao, $keywordsDao, $awardDao, $categoryDao, $mailer, $logger) {
        parent::__construct($logger);
        $this->mailer = $mailer;
        $this->conn = $conn;
        $this->flagDao = $flagDao;
        $this->awardDao = $awardDao;
        $this->categoryDao = $categoryDao;
        $this->usersDao = $usersDao;
        $this->keywordsDao = $keywordsDao;
        $this->projectsDao = $projectsDao;
    }

    /**
     * Handles a request to create a new project and associate it with a user.
     *
     * @return void
     */
    public function handleCreateProject() {
        $userId = $this->getFromBody('userId');
        $title = $this->getFromBody('title');
        $description = $this->getFromBody('description');

        if (empty(trim($title))){
            $this->respond(new Response(Response::BAD_REQUEST, 'Title cannot be empty'));
        }
        if (empty(trim($description))){
            $this->respond(new Response(Response::BAD_REQUEST, 'Description cannot be empty'));
        }

        $project = new ShowcaseProject();
        $project
            ->setTitle($title)
            ->setDescription($description);
        
        $ok = $this->projectsDao->addNewProject($project, $userId);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to create project'));
        }

        $this->respond(new Response(
            Response::CREATED, 
            'Successfully created new project',
            array('id' => $project->getId())
        ));
    }

    /**
     * Handles a request to update information in the database about a project.
     *
     * @return void
     */
    public function handleUpdateProject() {
        $projectId = $this->getFromBody('projectId');
        $title = $this->getFromBody('title');
        $description = $this->getFromBody('description');
        $category = $this->getFromBody('category');
        $keywords = $this->getFromBody('keywords');

        if (empty(trim($title))){
            $this->respond(new Response(Response::BAD_REQUEST, 'Title cannot be empty'));
        }
        if (empty(trim($description))){
            $this->respond(new Response(Response::BAD_REQUEST, 'Description cannot be empty'));
        }

        $project = $this->projectsDao->getProject($projectId);
        // TODO: handle case when project is not found

        $project
            ->setTitle($title)
            ->setDescription($description)
            ->setCategory($category)
            ->setDateUpdated(new \DateTime("now")); //Modified 3/31/2023
        
        $ok = $this->projectsDao->updateProject($project);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to save changes to project'));
        }

        // Update the keywords. First we remove all of the old keywords.
        $ok = $this->keywordsDao->removeAllKeywordsForEntity($project->getId());
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to save project keyword information'));
        }
        if (!empty($keywords)) {
            // Clean the keyword IDs and split them into an array
            $keywords = \trim($keywords, ' ,');
            $keywords = \explode(',', $keywords);
            foreach ($keywords as $kId) {
                $k = new Keyword($kId);
                $ok = $this->keywordsDao->addKeywordInJoinTable($k, $project->getId());
                if (!$ok) {
                    $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to save project keyword information'));
                }
            }
        }
        

        $this->respond(new Response(
            Response::OK,
            'Successfully saved changes to project'
        ));
    }
	
	
	/**
     * Handles a request to update category in the database for a project.
     *
     * @return void
     */
    public function handleUpdateCategory() {
        $projectId = $this->getFromBody('projectId');
        $category = $this->getFromBody('category');
        
        $project = $this->projectsDao->getProject($projectId);
        // TODO: handle case when project is not found

        $project->setCategory($category);
        
        $ok = $this->projectsDao->updateProject($project);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to save changes to project'));
        }

        $this->respond(new Response(
            Response::OK,
            'Successfully updated category to '.$category.' for ' . $projectId
        ));
    }
	
	/**
     * Handles a request to create a category in the database.
     *
     * @return void
     */
    public function handleCreateCategory() {
        $name = $this->getFromBody('name');
        $shrtname = $this->getFromBody('shrtname');
        
		$ok = $this->categoryDao->createCategory($name, $shrtname);
		if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to create category'));
        }
		
		$this->respond(new Response(
            Response::OK,
            'Successfully added category, '.$name));
    }

    
    public function handleCreateAward() {
        $award = new Award();
        $award->setName($this->getFromBody('name'));
        $award->setDescription($this->getFromBody('description'));
        $award->setImageNameSquare('gold_square.png');
        $award->setImageNameRectangle('');

        $ok = $this->awardDao->createAward($award);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to create award.'));
        }
        $this->respond(new Response(
            Response::OK,
            'Successfully created award'
        ));
    }
	
	public function handleCreateFlag() {
        $flag = new Flag();
        $flag->setDescription($this->getFromBody('name'));
        $flag->setActive(1);
			
		$flagDao = new FlagDao($this->conn, $this->logger);

        $ok = $flagDao->createFlag($flag);
        
		if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to create flag.'));
        }
        $this->respond(new Response(
            Response::OK,
            'Successfully created flag'
        ));
    }
	
	
	/**
     * Handles a request to give an award to a project.
     *
     * @return void
     */
    public function handleGiveAward() {
        $projectId = $this->getFromBody('projectId');
        $awardId = $this->getFromBody('awardId');
        
        $ok = $this->awardDao->giveAward($awardId, $projectId);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to give award.'));
        }
        $this->respond(new Response(
            Response::OK,
            'Successfully added award'
        ));
    }
	
	/**
     * Handles a request to remove an award from a project.
     *
     * @return void
     */
    public function handleRemoveAward() {
        $projectId = $this->getFromBody('projectId');
        $awardId = $this->getFromBody('awardId');
        
        $ok = $this->awardDao->removeAward($awardId, $projectId);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to remove award.'));
        }
        $this->respond(new Response(
            Response::OK,
            'Successfully removed award'
        ));
    }
	

    /**
     * Handles inviting a user to collaborate on a project.
     *
     * @return void
     */
    public function handleInviteUserToProject() {
        $projectId = $this->getFromBody('projectId');
        $userId = $this->getFromBody('userId');
        $email = $this->getFromBody('email');

		if (substr($email,-16,16) != '@oregonstate.edu') //Can not be added to project
			$this->respond(new Response(Response::INTERNAL_SERVER_ERROR, "Collaborators must have '@oregonstate.edu' email addresses."));

        $user = $this->usersDao->getUser($userId);
        // if (!($user)) // User search returned false
        //     $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, "User not found in user table. They need to login to this site once before you can add them."));
        
        // if ($user->getOnid() == '') //Can not be added to project
        //     $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, "Collaborators must have '@oregonstate.edu' email addresses."));
			
        $project = $this->projectsDao->getProject($projectId);
        // TODO: handle case when showcase project is not found

        $invitation = new CollaborationInvitation();
        $invitation
            ->setProjectId($projectId)
            ->setEmail($email);

        $sent = $this->mailer->sendInvite($user, $email, $project, $invitation->getId());
        if (!$sent) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, "Failed to send invitation to $email"));
        }

        $ok = $this->projectsDao->addInvitationToCollaborateOnProject($invitation);
        if (!$ok) {
            $this->response(new Response(
                Response::INTERNAL_SERVER_ERROR, 
                'Failed to save invitation. You may have to send another invite.'
            ));
        }

        $this->respond(new Response(
            Response::OK,
            "Successfully sent invitation to $email"
        ));
    }
	
	/**
     * Handles removing a user from a project.
     *
     * @return void
     */
    public function handleRemoveUserFromProject() {
        $projectId = $this->getFromBody('projectId');
        $userId = $this->getFromBody('userId');
        /*        
        $user = $this->usersDao->getUser($userId);			
        $project = $this->projectsDao->getProject($projectId);


        $sent = $this->mailer->sendInvite($user, $email, $project, $invitation->getId());
        if (!$sent) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, "Failed to send invitation to $email"));
        }
        */
        $ok = $this->projectsDao->deleteProjectCollaborator($projectId, $userId);
        if (!$ok) {
            $this->response(new Response(
                Response::INTERNAL_SERVER_ERROR, 
                'Failed to remove user. You need to contact support.'
            ));
        }

        $this->respond(new Response(
            Response::OK,
            "Successfully removed user."
        ));
    }

    /**
     * Handles a request to not show a user as a project associate publicly.
     *
     * @return void
     */
    public function handleHideUserFromProject() {
        $userId = $this->getFromBody('userId');
        $projectId = $this->getFromBody('projectId');

        $ok = $this->projectsDao->updateVisibilityOfUserForProject($projectId, $userId, false);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to update privacy preferences'));
        }

        $this->respond(new Response(
            Response::OK,
            'Successfully updated privacy preferences'
        ));
    }

    /**
     * Handles a request to show a user as a project associate publically.
     *
     * @return void
     */
    public function handleShowUserOnProject() {
        $userId = $this->getFromBody('userId');
        $projectId = $this->getFromBody('projectId');

        $ok = $this->projectsDao->updateVisibilityOfUserForProject($projectId, $userId, true);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to update privacy preferences'));
        }

        $this->respond(new Response(
            Response::OK,
            'Successfully updated privacy preferences'
        ));
    }

	
	public function getAction() {
        return $this->getFromBody('action');
	}
	
    /**
     * Handles a request for showcase projects that match the query in the body
     *
     * @return void
     */
    public function handleBrowseProjects() {
        $query = $this->getFromBody('query', false);

        // If the query is empty, do nothing. We don't want to return everything
        if (empty($query)) {
            $this->respond(new Response(Response::BAD_REQUEST, 'Query cannot be empty'));
        }

        if (isset($_REQUEST['all'])){
            $projects = $this->projectsDao->getProjectsWithQuery($query);
        } else{
            $projects = $this->projectsDao->getRecentlyCreatedProjectsWithQuery($query);
        }

        if (!$projects && !is_array($projects)) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to fetch projects for query'));
        }

        // Map the projects to an array we can represent as JSON
        include_once PUBLIC_FILES . '/modules/project.php';
        $body = array('html' => '');
        foreach ($projects as $p) {
			$keywords = $this->keywordsDao->getKeywordsForEntity($p->getId());
			$awards = $this->projectsDao->getProjectAwards($p->getId());
			$p->setKeywords($keywords);
			$p->setAwards($awards);
            $body['html'] .= createProfileProjectHtml($p, false);
        }
        $this->respond(new Response(
            Response::OK,
            'Successfully fetched projects with query',
            $body
        ));
    }

    /**
     * Handles a request to change the visibility of a showcase project
     *
     * @return void
     */
    public function handleUpdateVisibility() {
        $id = $this->getFromBody('id');
        $published = $this->getFromBody('publish');
        
        $project = $this->projectsDao->getProject($id);

        $project->setPublished($published);

        $ok = $this->projectsDao->updateProject($project);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to upate visibility of project'));
        }

        $this->respond(new Response(
            Response::OK,
            'Successfully updated project visibility'
        ));
    }

    /**
     * Handles a request to delete a showcase project entirely
     *
     * @return void
     */
    public function handleDeleteProject() {
        $sid = $this->getFromBody('id');
        
        $project = $this->projectsDao->getProject($sid);
    
        $pImages = $project->getImages();
        foreach ($pImages as $i) {
            $id = $i->getId();
            $ok = $this->projectsDao->deleteProjectImage($id);
            if (!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to upate delete project image'));
            }
        }

        $pArtifacts = $project->getArtifacts();
        foreach ($pArtifacts as $artifact) {
            $aId = $artifact->getId();
            $ok = $this->projectsDao->deleteProjectArtifact($aId);
            if (!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to delete project artifacts'));
            }
        }

        $ok = $this->projectsDao->deleteProjectInvites($sid);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to delete project invites'));
        }

        $ok = $this->projectsDao->deleteProjectCollaborators($sid);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to delete collaborators'));
        }

        $ok = $this->projectsDao->deleteShowcaseProject($sid);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to delete project'));
        }

    
        $this->respond(new Response(
            Response::OK,
            'Successfully deleted the project'
        ));
    }

    /**
     * Handles a request to delete all showcase project attached to a user
     *
     * @return void
     */
    public function handleDeleteProfileProjects() {
        $userId = $this->getFromBody('userId');
        $projects = $this->projectsDao->getUserProjects($userId, false, true, 'title');

        foreach ($projects as $project) {
            $sid = $project->getId();

            $pImages = $project->getImages();
            foreach ($pImages as $i) {
                $id = $i->getId();
                $ok = $this->projectsDao->deleteProjectImage($id);
                if (!$ok) {
                    $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to upate delete project image'));
                }
            }

            $pArtifacts = $project->getArtifacts();
            foreach ($pArtifacts as $artifact) {
                $aId = $artifact->getId();
                $ok = $this->projectsDao->deleteProjectArtifact($aId);
                if (!$ok) {
                    $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to delete project artifacts'));
                }
            }

            $ok = $this->projectsDao->deleteProjectInvites($sid);
            if (!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to delete project invites'));
            }

            $ok = $this->projectsDao->deleteProjectCollaborators($sid);
            if (!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to delete collaborators'));
            }

            $ok = $this->projectsDao->deleteShowcaseProject($sid);
            if (!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to delete project'));
            }
        }
    
        $this->respond(new Response(
            Response::OK,
            'Successfully deleted projects'
        ));
    }

    /**
     * Handles a request to add a keyword
     *
     * @return void
     */
    public function handleAddKeyword() {
        $newKeyword = $this->getFromBody('keyword');

        if (!$this->keywordsDao->keywordExists($newKeyword)) {
            $ok = $this->keywordsDao->addKeyword($newKeyword, 0);
            if (!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to create keyword'));
            }
        }

        $keyword = $this->keywordsDao->getKeyword($newKeyword);
        if (!$keyword) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to create keyword'));
        }
        
        $keywordId = $keyword->getId();

        $this->respond(new Response(
            Response::OK,
            $keywordId
        ));
    }

    /**
     * Handles the HTTP request on the API resource. 
     * 
     * This effectively will invoke the correct action based on the `action` parameter value in the request body. If
     * the `action` parameter is not in the body, the request will be rejected. The assumption is that the request
     * has already been authorized before this function is called.
     *
     * @return void
     */
    public function handleRequest() {
        // Make sure the action parameter exists
        $this->requireParam('action');

        // Call the correct handler based on the action
        switch ($this->requestBody['action']) {

            case 'createProject':
                $this->handleCreateProject();

            case 'updateProject':
                $this->handleUpdateProject();

            case 'inviteUser':
                $this->handleInviteUserToProject();

			case 'removeUser':
                $this->handleRemoveUserFromProject();

			case 'giveAward':
               $this->handleGiveAward();
			
			case 'removeAward':
                $this->handleRemoveAward();

            case 'hideUserFromProject':
                $this->handleHideUserFromProject();

            case 'showUserOnProject':
                $this->handleShowUserOnProject();

            case 'browseProjects':
                $this->handleBrowseProjects();

            case 'updateVisibility':
                $this->handleUpdateVisibility();

			case 'updateCategory':
                $this->handleUpdateCategory();

			case 'createCategory':
                $this->handleCreateCategory();

            case 'deleteProject':
                $this->handleDeleteProject();
            
            case 'deleteProfileProjects':
                $this->handleDeleteProfileProjects();

            case 'createAward':
                $this->handleCreateAward();

			case 'createFlag':
                $this->handleCreateFlag();

            case 'addKeyword':
                $this->handleAddKeyword();

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on showcase project resource'));
        }
    }
}
