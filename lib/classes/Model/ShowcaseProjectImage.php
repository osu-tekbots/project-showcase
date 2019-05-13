<?php
namespace Model;

use Util\IdGenerator;

/**
 * Represents image metadata associated with an image for a project in the showcase.
 */
class ShowcaseProjectImage {

    /** @var string */
    private $id;

    /** @var string */
    private $projectId;

    /** @var ShowcaseProject */
    private $project;

    /** @var string */
    private $fileName;

    /** @var \DateTime() */
    private $dateCreated;

    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
            $this->setDateCreated(new \DateTime());
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
     * Get the value of fileName
     */ 
    public function getFileName() {
        return $this->fileName;
    }

    /**
     * Set the value of fileName
     *
     * @return  self
     */ 
    public function setFileName($fileName) {
        $this->fileName = $fileName;

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
}
