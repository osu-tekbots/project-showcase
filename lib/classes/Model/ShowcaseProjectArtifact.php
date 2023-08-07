<?php
namespace Model;

use Util\IdGenerator;

/**
 * Represents an artifact belonging to a showcase project.
 */
class ShowcaseProjectArtifact {
    /** @var string */
    private $id;
    /** @var string */
    private $projectId;
    /** @var ShowcaseProject */
    private $project;
    /** @var string */
    private $name;
    /** @var string */
    private $description;
    /** @var string */
    private $fileUploaded;
    /** @var string */
    private $link;
    /** @var bool */
    private $published;
    /** @var \DateTime */
    private $dateCreated;
    /** @var \DateTime */
    private $dateUpdated;
	/** @var string */
    private $extension;

    /**
     * Constructs a new instance of an artifact.
     * 
     * If no ID is provided, an ID will be generated and the default values will be set.
     *
     * @param string|null $id the ID of the artifact, or null to generate a new ID
     */
    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
            $this->setPublished(true);
            $this->setFileUploaded(false);
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
     * Get the value of name
     */ 
    public function getName() {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of description
     */ 
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set the value of description
     *
     * @return  self
     */ 
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of fileUploaded
     */ 
    public function isFileUploaded() {
        return $this->fileUploaded;
    }

    /**
     * Set the value of fileUploaded
     *
     * @return  self
     */ 
    public function setFileUploaded($value) {
        $this->fileUploaded = $value;

        return $this;
    }

    /**
     * Get the value of link
     */ 
    public function getLink() {
        return $this->link;
    }

    /**
     * Set the value of link
     *
     * @return  self
     */ 
    public function setLink($link) {
        $this->link = $link;

        return $this;
    }

    /**
     * Get the value of published
     */ 
    public function isPublished() {
        return $this->published;
    }

    /**
     * Set the value of published
     *
     * @return  self
     */ 
    public function setPublished($published) {
        $this->published = $published;

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
     * Get the value of extension
     */ 
    public function getExtension() {
        return $this->extension;
    }

    /**
     * Set the value of extension
     *
     * @return  self
     */ 
    public function setExtension($data) {
        $this->extension = $data;

        return $this;
    }
}
