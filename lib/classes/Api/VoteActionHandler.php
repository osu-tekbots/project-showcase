<?php
namespace Api;

use Model\ShowcaseProject;
use Model\Vote;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Defines the logic for how to handle AJAX requests with JSON bodies made to modify showcase project information.
 */
class VoteActionHandler extends ActionHandler {

    /** @var \DataAccess\ShowcaseProjectsDao */
    private $projectsDao;

	/** @var \DataAccess\AwardDao */
    private $awardDao;

    /** @var \DataAccess\UsersDao */
    private $usersDao;

    /** @var \DataAccess\VoteDao */
    private $voteDao;

    /**
     * Constructs a new instance of the action handler for requests on user resources.
     *
     * @param \DataAccess\ShowcaseProjectsDao $projectsDao the data access object for showcase projects
     * @param \DataAccess\UsersDao $usersDao the data access object for users
     * @param \DataAccess\KeywordsDao $keywordsDao the data access object for keywords
     * @param \Email\CollaborationMailer  $mailer the Mailer class providing email functionality for collaboration
     * @param \Util\Logger $logger the logger to use for logging information about actions
     */
    public function __construct($projectsDao, $usersDao, $voteDao, $awardDao, $logger) {
        parent::__construct($logger);
        $this->awardDao = $awardDao;
        $this->usersDao = $usersDao;
        $this->voteDao = $voteDao;
        $this->projectsDao = $projectsDao;
    }

    /**
     * Handles a request to create a new vote.
     *
     * @return void
     */
    public function handleCreateVote() {
        $this->requireParam('userId');
		$this->requireParam('projectId');
		  
		$userId = $this->getFromBody('userId');
        $projectId = $this->getFromBody('projectId');
        
		//TODO: This needs to be made configurable. This is currently done this way to prevent users from making a vote worth more than another.
		$score = 1;

        $vote = new Vote();
        $vote
            ->setProjectId($projectId)
            ->setUserId($userId)
			->setScore($score);

		//Check if a vote for this project and this user exists. If so, we will not add another
		if ($this->voteDao->checkVote($projectId, $userId) == false){
			$ok = $this->voteDao->addNewVote($vote);
			if (!$ok) {
				$this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to lift.'));
			}

			$this->respond(new Response(
				Response::CREATED, 
				'Project Lifted')
			);
		}
		
		$this->respond(new Response(
            Response::OK, 
            'You have already lifted this project')
        );
		
    }
	
	/**
     * Handles a request to delete a vote.
     *
     * @return void
     */
    public function handleDeleteVote() {
        $userId = $this->getFromBody('userId');
        $projectId = $this->getFromBody('projectId');
		
        $ok = $this->voteDao->deleteVote($projectId, $userId);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to remove Lift'));
        }

        $this->respond(new Response(
            Response::OK, 
            'Successfully removed Lift')
        );
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

            case 'addVote':
                $this->handleCreateVote();

            case 'removeVote':
                $this->handleDeleteVote();

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on showcase project resource'));
        }
    }
}
