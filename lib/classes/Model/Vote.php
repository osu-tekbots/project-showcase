<?php
namespace Model;

use Util\IdGenerator;

/**
 * Represents information about a project on a student's profile page
 */
class Vote {
    /** @var string */
    private $id;
    /** @var string */
    private $projectid;
    /** @var string */
    private $userid;
	/** @var int */
    private $score;
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
    public function getProjectId() {
        return $this->projectid;
    }

    /**
     * Set the value of title
     *
     * @return  self
     */ 
    public function setProjectId($data) {
        $this->projectid = $data;

        return $this;
    }

    /**
     * Get the value of userid
     */ 
    public function getUserId() {
        return $this->userid;
    }

    /**
     * Set the value of userid
     *
     * @return  self
     */ 
    public function setUserId($data) {
        $this->userid = $data;

        return $this;
    }
	
	/**
     * Get the value of score
     */ 
    public function getScore() {
        return $this->score;
    }

    /**
     * Set the value of score
     *
     * @return  self
     */ 
    public function setScore($data) {
        $this->score = $data;

        return $this;
    }
	
	/**
     * Get the value of dateCreated
     *
     * @return  dateCreated
     */ 
    public function getDateCreated() {
        return $dateCreated;
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
