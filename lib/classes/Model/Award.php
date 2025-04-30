<?php
namespace Model;

use Util\IdGenerator;

/**
 * Represents information about a project on a student's profile page
 */
class Award {
    /** @var string */
    private $id;
    /** @var string */
    private $name;
    /** @var string */
    private $description;
     /** @var string */
    private $imageNameSquare;
     /** @var string */
    private $imageNameRectangle;
     /** @var int */
    private $award_active;
    

    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
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
    public function setId($data) {
        $this->id = $data;

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
    public function setName($data) {
        $this->name = $data;

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
    public function setDescription($data) {
        $this->description = $data;

        return $this;
    }
	
	/**
     * Get the value of imageNameSquare
     */ 
    public function getImageNameSquare() {
        return $this->imageNameSquare;
    }

    /**
     * Set the value of imageNameSquare
     *
     * @return  self
     */ 
    public function setImageNameSquare($data) {
        $this->imageNameSquare = $data;

        return $this;
    }
	
	/**
     * Get the value of imageNameRectangle
     */ 
    public function getImageNameRectangle() {
        return $this->imageNameRectangle;
    }

    /**
     * Set the value of imageNameRectangle
     *
     * @return  self
     */ 
    public function setImageNameRectangle($data) {
        $this->imageNameRectangle = $data;

        return $this;
    }

    /**
     * Get the value of award_active
     */ 
    public function getAwardActive() {
        return $this->award_active;
    }

    /**
     * Set the value of award_active
     *
     * @return  self
     */ 
    public function setAwardActive($data) {
        $this->award_active = $data;
        return $this;
    }

}
