<?php
namespace Api;

use Model\ShowcaseProject;
use Model\CollaborationInvitation;

/**
 * Defines the logic for how to handle AJAX requests with JSON bodies made to modify showcase project information.
 */
class ShowcaseProjectsActionHandler extends ActionHandler {

    /** @var \DataAccess\ShowcaseProjectsDao */
    private $projectsDao;

    /** @var \DataAccess\UsersDao */
    private $usersDao;

    /** @var \Email\CollaborationMailer */
    private $mailer;

    /**
     * Constructs a new instance of the action handler for requests on user resources.
     *
     * @param \DataAccess\ShowcaseProjectsDao $projectsDao the data access object for showcase projects
     * @param \Email\CollaborationMailer  $mailer the Mailer class providing email functionality for collaboration
     * @param \Util\Logger $logger the logger to use for logging information about actions
     */
    public function __construct($projectsDao, $usersDao, $mailer, $logger) {
        parent::__construct($logger);
        $this->mailer = $mailer;
        $this->usersDao = $usersDao;
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

        $project = $this->projectsDao->getProject($projectId);
        // TODO: handle case when project is not found

        $project
            ->setTitle($title)
            ->setDescription($description)
            ->setDateUpdated(new \DateTime());
        
        $ok = $this->projectsDao->updateProject($project);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to save changes to project'));
        }

        $this->respond(new Response(
            Response::OK,
            'Successfully saved changes to project'
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

        $user = $this->usersDao->getUser($userId);
        // TODO: handle case when user is not found

        $project = $this->projectsDao->getProject($projectId);
        // TODO: handle case when showcase project is not found

        $invitation = new CollaborationInvitation();
        $invitation
            ->setProjectId($projectId)
            ->setEmail($email);

        $sent = $this->mailer->sendInvite($user, $email, $project, $invitation->getId());
        // if (!$sent) {
        //     $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, "Failed to send invitation to $email"));
        // }

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
     * Handles a request to not show a user as a project associate publically.
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

            case 'hideUserFromProject':
                $this->handleHideUserFromProject();

            case 'showUserOnProject':
                $this->handleShowUserOnProject();

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on showcase project resource'));
        }
    }
}
