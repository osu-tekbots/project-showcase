<?php
namespace DataAccess;


use Model\Award;
use DataAccess\ShowcaseProjectsDao;

/**
 * Handles database interactions for categories.
 */
class AwardDao {

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
    public function getAllAwards() {
        try {
            $sql = "
            SELECT *
            FROM showcase_awards
            ORDER BY name ASC
            ";
            $results = $this->conn->query($sql);

            $awards = array();
            foreach ($results as $row) {
                $awards[] = self::ExtractAwardFromRow($row);
            }

            return $awards;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all awards: ' . $e->getMessage());
            return false;
        }
    }
	
	
	/**
     * Fetches all project award winners based on award id.
     *
     * @param sting $id the award id string
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function getAwardRecipients($awardId) {
        try {
            $sql = "
            SELECT *
            FROM showcase_project
			WHERE showcase_project.sp_id IN (SELECT showcase_project_awards.sp_id FROM showcase_project_awards WHERE award_id = :id)
            ORDER BY showcase_project.sp_title ASC
            ";
            $params = array(':id' => $awardId);
			$results = $this->conn->query($sql, $params);

            $projects = array();
            foreach ($results as $row) {
                $projects[] = ShowcaseProjectsDao::ExtractShowcaseProjectFromRow($row);
            }

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get projects: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Attaches an award to a project.
     *
     * @param sting $id the award id string
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function giveAward($awardId, $projectId) {
        try {
            $sql = "
            INSERT INTO showcase_project_awards (award_id, sp_id) VALUES (:aid, :pid)
            ";
            $params = array(':aid' => $awardId,
							':pid' => $projectId);
							
			$results = $this->conn->execute($sql, $params);


            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to give award: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Removes an award from a project.
     *
     * @param sting $id the award id string
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function removeAward($awardId, $projectId) {
        try {
            $sql = "
            DELETE FROM showcase_project_awards WHERE award_id = :aid AND sp_id = :pid
            ";
            $params = array(':aid' => $awardId,
							':pid' => $projectId);
							
			$results = $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove award: ' . $e->getMessage());
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
    public function updateAward($award) {
        try {
            $sql = '
            UPDATE showcase_awards SET 
                name = :name, 
                description = :description, 
                image_name_square = :image_name_square,
                image_name_rectangle = :image_name_rectangle 
            WHERE id = :id
            ';
            $params = array(
                ':id' => $award->getId(),
                ':name' => $award->getName(),
                ':description' => $award->getDescription(),
                ':image_name_square' => $award->getImageNameSquare(),
                ':image_name_rectangle' => $award->getImageNameRectangle()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update showcase award: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Users information from a row in the database to create an Award object.
     *
     * @param mixed[] $row the row from the database
     * 
     * @return \Model\Award the award
     */
    public static function ExtractAwardFromRow($row) {
        $award = new Award($row['id']);
        $award
            ->setName($row['name'])
            ->setDescription($row['description'])
			->setImageNameSquare($row['image_name_square'])
			->setImageNameRectangle($row['image_name_rectangle']);

        return $award;
    }
}
