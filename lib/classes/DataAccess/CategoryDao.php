<?php
namespace DataAccess;

use Model\Category;

/**
 * Handles database interactions for categories.
 */
class CategoryDao {

    /** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;

    /**
     * Constructs a new data access object for showcase data.
     *
     * @param DatabaseConnection $connection the connection to use for the database queries
     * @param \Util\Logger $logger logger instance to use for logging errors and other information
     */
	 
    public function __construct($connection, $logger) {
        $this->conn = $connection;
        $this->logger = $logger;
    }

    /**
     * Fetches all the categories from the database.
     *
     * @param integer $count the number to limit the return array size to
     * @param integer $offset the offset in the database table to start retrieve projects from
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function getAllCategories() {
        try {
            $sql = "
            SELECT *
            FROM showcase_category
            ORDER BY name ASC
            ";
            $results = $this->conn->query($sql);

            $categories = array();
            foreach ($results as $row) {
                $categories[] = self::ExtractCategoryFromRow($row);
            }

            return $categories;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all categories: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Fetches all the categories from the database.
     *
     * @param integer $count the number to limit the return array size to
     * @param integer $offset the offset in the database table to start retrieve projects from
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function getCategoryByShortName($shortname) {
        try {
            $sql = "
            SELECT * 
            FROM showcase_category 
			WHERE showcase_category.shortname = '$shortname' 
            ORDER BY name ASC
            ";
            $results = $this->conn->query($sql);

			foreach ($results as $row) {
				$category = self::ExtractCategoryFromRow($row);
            }
			
            return $category;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get category: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Creates a new categories in the database.
     *
     * @param string $name Name for the category
     * @param string $shortname Short name for the link for the category
     * @return boolean
     */
    public function createCategory($name, $shortname) {
        try {
            $this->conn->startTransaction();
            $sql = 'INSERT INTO showcase_category (shortname, name) VALUES (:shortname, :name)';
            			
			$params = array(
                ':shortname' => $shortname,
                ':name' => $name
            );
            $this->conn->execute($sql, $params);
			$this->conn->commit();
			return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to create category: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Users information from a row in the database to create a CollaborationInvitation object.
     *
     * @param mixed[] $row the row from the database
     * @param boolean $projectInRow indicates whether the project information is also in the row and should be
     * extracted.
     * @return \Model\CollaborationInvitation the invitation
     */
    public static function ExtractCategoryFromRow($row) {
        $category = new Category($row['id']);
        $category
            ->setName($row['name'])
            ->setShortName($row['shortname']);

        return $category;
    }
}
