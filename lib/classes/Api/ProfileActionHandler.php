<?php
namespace Api;

/**
 * Defines the logic for how to handle AJAX requests made to modify user information.
 */
class ProfileActionHandler extends ActionHandler {

    /** @var \DataAccess\ShowcaseProfilesDao */
    private $profilesDao;
    /** @var \DataAccess\UsersDao */
    private $usersDao;

    /**
     * Constructs a new instance of the action handler for requests on user resources.
     *
     * @param \DataAccess\UsersDao $dao the data access object for users
     * @param \Util\Logger $logger the logger to use for logging information about actions
     */
    public function __construct($profilesDao, $usersDao, $logger) {
        parent::__construct($logger);
        $this->profilesDao = $profilesDao;
        $this->usersDao = $usersDao;
    }

    /**
     * Updates profile information about a user in the database based on data in an HTTP request.
     * 
     * This function, after invocation is finished, will exit the script via the `ActionHandler\respond()` function.
     *
     * @return void
     */
    public function saveUserProfile() {
        // Make sure the required parameters exit
        $userId = $this->getFromBody('userId');
        $firstName = $this->getFromBody('firstName');
        $lastName = $this->getFromBody('lastName');
        $major = $this->getFromBody('major');
        $canShowContactInfo = $this->getFromBody('publishContactInfo', false);
        $about = $this->getFromBody('about');
        $websiteLink = $this->getFromBody('websiteLink');
        $githubLink = $this->getFromBody('githubLink');
        $linkedinLink = $this->getFromBody('linkedInLink');

        $phone = $this->getFromBody('phone', false);
        $email = $this->getFromBody('email', false);

        $profile = $this->profilesDao->getUserProfileInformation($userId);
        // TODO: handle case when profile is not found

        // Update the values
        $profile
            ->setAbout($about)
            ->setShowContactInfo($canShowContactInfo ? true : false)
            ->setWebsiteLink($websiteLink)
            ->setGithubLink($githubLink)
            ->setLinkedInLink($linkedinLink);
        
        $profile->getUser()
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setMajor($major);

        if ($phone) {
            $profile->getUser()->setPhone($phone);
        }

        if ($email) {
            $profile->getUser()->setEmail($email);
        }

        $ok = $this->profilesDao->updateShowcaseProfile($profile);
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to update profile information'));
        }

        $ok = $this->usersDao->updateUser($profile->getUser());
        if (!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to update user information'));
        }

        $this->respond(new Response(
            Response::OK,
            'Successfully saved profile information'
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

            case 'saveProfile':
                $this->saveUserProfile();

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on user resource'));
        }
    }
}
