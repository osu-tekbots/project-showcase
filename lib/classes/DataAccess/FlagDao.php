<?php
namespace DataAccess;


use Model\Flag;
use DataAccess\ShowcaseProjectsDao;

/**
 * Handles database interactions for categories.
 */
class FlagDao {

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
     * Fetches all the flags from the database.
     * @param int $active Get either active or inactive flags. Defaults to active flags
     * @return \Model\Flag[] an array of flags on success, false otherwise
     */
    public function getAllFlags($active = 1) {
        try {
            $sql = "
            SELECT showcase_flags.*
            FROM showcase_flags
            WHERE sf_active = :active 
			ORDER BY sf_description ASC
            ";
			$params = array(':active' => $active);
			$results = $this->conn->query($sql, $params);

            $flags = array();
            foreach ($results as $row) {
                $flags[] = self::ExtractFlagFromRow($row);
            }

            return $flags;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all flags: ' . $e->getMessage());
            return false;
        }
    }
	
	
	/**
     * Fetches all projects based on flag id.
     *
     * @param string $id the flag id string
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function getProjectsByFlag($flagId) {
        try {
            $sql = "
            SELECT showcase_project.*
            FROM showcase_project
			WHERE showcase_project.sp_id IN (SELECT showcase_project_flags.sp_id FROM showcase_project_flags WHERE sf_id = :id)
            ORDER BY showcase_project.sp_title ASC
            ";
            $params = array(':id' => $flagId);
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
     * Creates a new flag
     *
     * @param \Model\Flag $flag the flag object
     * @return true on success, false otherwise
     */
    public function createFlag($flag) {
        try {
            $sql = "INSERT INTO showcase_flags 
                (
                    sf_id,
                    sf_description,
                    sf_active
                )
                VALUES (
                    :id, 
                    :description, 
                    :active
                )
            ";
            $params = array(
                ':id' => $flag->getId(),
                ':description' => $flag->getDescription(),
                ':active' => $flag->getActive()
            );
							
			$this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create flag: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Attaches a flag to a project.
     *
     * @param string $flagId the flag id 
     * @param string $projectId the project id 
     * @return true on success, false otherwise
     */
    public function assignFlag($flagId, $projectId) {
        try {
            $sql = "
            INSERT INTO showcase_project_flags (sf_id, sp_id) VALUES (:fid, :pid)
            ";
            $params = array(':fid' => $flagId,
							':pid' => $projectId);
							
			$results = $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to attach flag: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Removes a flag from a project.
     *
     * @param string $flagId the flag id 
     * @param string $projectId the project id 
     * @return true on success, false otherwise
	*/
    public function removeAward($flagId, $projectId) {
        try {
            $sql = "
            DELETE FROM showcase_project_flags WHERE sf_id = :fid AND sp_id = :pid
            ";
            $params = array(':fid' => $flagId,
							':pid' => $projectId);
							
			$results = $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove flag: ' . $e->getMessage());
            return false;
        }
    }
	
	
	/**
     * Updates an existing flag.
     *
     * @param \Model\Flag $flag the flag object to be updated
     * @return true on success, false otherwise
     */
    public function updateFlag($flag) {
        try {
            $sql = '
            UPDATE showcase_flags SET  
                sf_description = :description, 
                sf_active = :sf_active,
                sf_date_created = :sf_date_created 
            WHERE sf_id = :id
            ';
            $params = array(
                ':id' => $flag->getId(),
                ':description' => $flag->getDescription(),
                ':sf_active' => $flag->getActive(),
                ':sf_date_created' => $flag->getDateCreated()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update flag: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Users information from a row in the database to create a Flag object.
     *
     * @param mixed[] $row the row from the database
     * 
     * @return \Model\Flag the Flag
     */
    public static function ExtractFlagFromRow($row) {
        $flag = new Flag($row['sf_id']);
        $flag
            ->setDescription($row['sf_description'])
            ->setActive($row['sf_active'])
			->setDateCreated($row['sf_date_created']);

        return $flag;
    }
}
