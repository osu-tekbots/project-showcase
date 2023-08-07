<?php
namespace Model;

use Util\IdGenerator;

/**
 * Represents information about a project on a student's profile page
 */
class Category {
    /** @var string */
    private $id;
    /** @var string */
    private $name;
    /** @var string */
    private $shortname;
    

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
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of title
     */ 
    public function getName() {
        return $this->name;
    }

    /**
     * Set the value of title
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
    public function getShortName() {
        return $this->shortname;
    }

    /**
     * Set the value of description
     *
     * @return  self
     */ 
    public function setShortName($data) {
        $this->shortname = $data;

        return $this;
    }

}
