<?php
namespace Model;

use Util\IdGenerator;

/**
 * Represents information about a project on a student's profile page
 */
class Flag {
    /** @var string */
    private $id;
    /** @var string */
    private $description;
	/** @var int */
    private $active;
	/** @var DateTime */
    private $dateCreated;
    

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
     * Get the value of title
     */ 
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set the value of title
     *
     * @return  self
     */ 
    public function setDescription($data) {
        $this->description = $data;

        return $this;
    }
	
	/**
     * Get the value of score
     */ 
    public function getActive() {
        return $this->active;
    }

    /**
     * Set the value of score
     *
     * @return  self
     */ 
    public function setActive($data) {
        $this->active = $data;

        return $this;
    }
	
	/**
     * Get the value of dateCreated
     *
     * @return  dateCreated
     */ 
    public function getDateCreated() {
        return $this->dateCreated;
    }
	
	/**
     * Set the value of dateCreated
     *
     * @return  self
     */ 
    public function setDateCreated($data) {
        $this->dateCreated = $data;

        return $this;
    }

}
