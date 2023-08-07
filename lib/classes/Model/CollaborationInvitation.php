<?php
namespace Model;

use Util\IdGenerator;

/**
 * Represents the metadata associated with an invitation to collaborate on a showcase project.
 */
class CollaborationInvitation {
    /** @var string */
    private $id;
    /** @var string */
    private $projectId;
    /** @var  ShowcaseProject */
    private $project;
    /** @var string */
    private $email;
    /** @var \DateTime */
    private $dateCreated;

    /**
     * Constructs a new instance of the collaboration invitation metadata.
     * 
     * If no ID is provided, a new ID will be generated and the creation date will be set.
     * 
     * @param string|null $id the ID of the collaboration invitation, or null to generate a new one
     */
    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
            $this->setDateCreated(new \DateTime("now"));
        }
        $this->setId($id);
    }

    /**
     * Get the value of id
     */ 
    public function getId() {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of projectId
     */ 
    public function getProjectId() {
        return $this->projectId;
    }

    /**
     * Set the value of projectId
     *
     * @return  self
     */ 
    public function setProjectId($projectId) {
        $this->projectId = $projectId;

        return $this;
    }

    /**
     * Get the value of project
     */ 
    public function getProject() {
        return $this->project;
    }

    /**
     * Set the value of project
     *
     * @return  self
     */ 
    public function setProject($project) {
        $this->project = $project;

        return $this;
    }

    /**
     * Get the value of email
     */ 
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */ 
    public function setEmail($email) {
        $this->email = $email;

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
}
