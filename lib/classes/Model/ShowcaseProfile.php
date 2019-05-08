<?php
namespace Model;

/**
 * Represents additional settings and metadata associated with a user of the showcase website.
 */
class ShowcaseProfile {
    /** @var string */
    private $userId;
    /** @var User */
    private $user;
    /** @var string */
    private $about;
    /** @var bool */
    private $showContactInfo;
    /** @var bool */
    private $acceptingInvites;
    /** @var string */
    private $websiteLink;
    /** @var string */
    private $githubLink;
    /** @var string */
    private $linkedInLink;
    /** @var bool */
    private $resumeUploaded;
    /** @var bool */
    private $imageUploaded;
    /** @var \DateTime */
    private $dateCreated;
    /** @var \DateTime */
    private $dateUpdated;

    /**
     * Constructs a new metadata class of user information.
     * 
     * Additional showcase profile information must be associated with an existing user ID.
     *
     * @param string $userId the ID of the user who is attached to the profile information
     * @param boolean $isNew indicates whether defaults should be set for the model object
     */
    public function __construct($userId, $isNew = false) {
        $this->setUserId($userId);
        if ($isNew) {
            $this->setShowContactInfo(false);
            $this->setAcceptingInvites(true);
            $this->setImageUploaded(false);
            $this->setResumeUploaded(false);
            $this->setDateCreated(new \DateTime());
        }
    }

    /**
     * Get the value of userId
     */ 
    public function getUserId() {
        return $this->userId;
    }

    /**
     * Set the value of userId
     *
     * @return  self
     */ 
    public function setUserId($userId) {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get the value of about
     */ 
    public function getAbout() {
        return $this->about;
    }

    /**
     * Set the value of about
     *
     * @return  self
     */ 
    public function setAbout($about) {
        $this->about = $about;

        return $this;
    }

    /**
     * Get the value of showContactInfo
     */ 
    public function canShowContactInfo() {
        return $this->showContactInfo;
    }

    /**
     * Set the value of showContactInfo
     *
     * @return  self
     */ 
    public function setShowContactInfo($showContactInfo) {
        $this->showContactInfo = $showContactInfo;

        return $this;
    }

    /**
     * Get the value of websiteLink
     */ 
    public function getWebsiteLink() {
        return $this->websiteLink;
    }

    /**
     * Set the value of websiteLink
     *
     * @return  self
     */ 
    public function setWebsiteLink($websiteLink) {
        $this->websiteLink = $websiteLink;

        return $this;
    }

    /**
     * Get the value of githubLink
     */ 
    public function getGithubLink() {
        return $this->githubLink;
    }

    /**
     * Set the value of githubLink
     *
     * @return  self
     */ 
    public function setGithubLink($githubLink) {
        $this->githubLink = $githubLink;

        return $this;
    }

    /**
     * Get the value of linkedInLink
     */ 
    public function getLinkedInLink() {
        return $this->linkedInLink;
    }

    /**
     * Set the value of linkedInLink
     *
     * @return  self
     */ 
    public function setLinkedInLink($linkedInLink) {
        $this->linkedInLink = $linkedInLink;

        return $this;
    }

    /**
     * Check if there is a resume uploaded for the profile
     */ 
    public function isResumeUploaded() {
        return $this->resumeUploaded;
    }

    /**
     * Set the value of resumeUploaded
     *
     * @return  self
     */ 
    public function setResumeUploaded($resumeUploaded) {
        $this->resumeUploaded = $resumeUploaded;

        return $this;
    }

    /**
     * Get the value of imageUploaded
     */ 
    public function isImageUploaded() {
        return $this->imageUploaded;
    }

    /**
     * Set the value of imageUploaded
     *
     * @return  self
     */ 
    public function setImageUploaded($imageUploaded) {
        $this->imageUploaded = $imageUploaded;

        return $this;
    }

    /**
     * Get the value of user
     */ 
    public function getUser() {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */ 
    public function setUser($user) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of dateUpdated
     */ 
    public function getDateUpdated() {
        return $this->dateUpdated;
    }

    /**
     * Set the value of dateUpdated
     *
     * @return  self
     */ 
    public function setDateUpdated($dateUpdated) {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * Get the value of dateCreated
     */ 
    public function getDateCreated() {
        return $this->dateCreated;
    }

    /**
     * Set the value of dateCreated
     *
     * @return  self
     */ 
    public function setDateCreated($dateCreated) {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get the value of acceptingInvites
     */ 
    public function isAcceptingInvites() {
        return $this->acceptingInvites;
    }

    /**
     * Set the value of acceptingInvites
     *
     * @return  self
     */ 
    public function setAcceptingInvites($acceptingInvites) {
        $this->acceptingInvites = $acceptingInvites;

        return $this;
    }
}
