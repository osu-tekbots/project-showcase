<?php
namespace Model;

use Util\IdGenerator;

/**
 * Represents information about a project on a student's profile page
 */
class ShowcaseProject {
    /** @var string */
    private $id;
    /** @var string */
    private $title;
    /** @var string */
    private $description;
    /** @var bool */
    private $published;
    /** @var \DateTime */
    private $dateCreated;
    /** @var \DateTime */
    private $dateUpdated;
    /** @var ShowcaseProjectArtifact[] */
    private $artifacts;
    /** @var ShowcaseProjectImage[] */
    private $images;

    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
            $this->setDateCreated(new \DateTime());
            $this->setPublished(true);
        }
        $this->setId($id);
        $this->setArtifacts(array());
        $this->setImages(array());
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
     * Get the value of title
     */ 
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @return  self
     */ 
    public function setTitle($title) {
        $this->title = $title;

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
     * Get the value of artifacts
     */ 
    public function getArtifacts() {
        return $this->artifacts;
    }

    /**
     * Set the value of artifacts
     *
     * @return  self
     */ 
    public function setArtifacts($artifacts) {
        $this->artifacts = $artifacts;

        return $this;
    }

    /**
     * Adds a new artifact to the internal array of artifacts
     *
     * @param \Model\ShowcaseProjectArtifact $artifact
     * @return self
     */
    public function addArtifact($artifact) {
        if ($this->artifacts == null) {
            $this->artifacts = array();
        }
        $artifact->setProject($this);
        $this->artifacts[] = $artifact;
        return $this;
    }

    /**
     * Get the value of images
     */ 
    public function getImages() {
        return $this->images;
    }

    /**
     * Set the value of images
     *
     * @return  self
     */ 
    public function setImages($images) {
        $this->images = $images;

        return $this;
    }

    /**
     * Adds an image to the project and sets the image's project reference to this project.
     *
     * @param ShowcaseProjectImage $image the image to add
     * @return self
     */
    public function addImage($image) {
        $image->setProject($this);
        if($this->images == null) {
            $this->images = array($image);
        } else {
            $this->images[] = $image;
        }
        return $this;
    }

}
