<?php
namespace DataAccess;

use Model\ShowcaseProject;
use Model\ShowcaseProjectArtifact;

/**
 * Handles database interactions for showcase project and artifact data.
 */
class ShowcaseProjectsDao {

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
     * Fetches all showcase projects associated with the user that has the provided ID.
     * 
     * By default, the artifacts accompanying the projects will not be included. This helps us optimize the function
     * when we only need project information (such as for summaries).
     *
     * @param string $userId the ID of the user whose projects to fetch
     * @param boolean $includeArtifacts flag to indicate whether to include project artifacts. Defaults to false.
     * @return \Model\ShowcaseProject[]|boolean an array of projects on success, false on error
     */
    public function getUserProjects($userId, $includeArtifacts = false) {
        try {
            $artifactsTable = $includeArtifacts ? ', showcase_project_artifact ' : '';
            $artifactsPredicate = $includeArtifacts ? 'AND spa_sp_id = sp_id' : '';
            $sql = "
            SELECT * 
            FROM showcase_project, showcase_worked_on $artifactsTable
            WHERE swo_u_id = :id AND swo_sp_id = sp_id $artifactsPredicate
            ORDER BY swo_u_id
            ";
            $params = array(':id' => $userId);
            $results = $this->conn->query($sql, $params);

            $projects = array();
            $uid = '';
            foreach ($results as $row) {
                if ($row['swo_u_id'] != $uid) {
                    $uid = $row['swo_u_id'];
                    $projects[] = self::ExtractShowcaseProjectFromRow($row);
                }
                if ($includeArtifacts) {
                    $p = $projects[\count($projects) - 1];
                    $artifact = self::ExtractShowcaseArtifactFromRow($row);
                    $p->addArtifact($artifact);
                }
            }

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch projects for user with id '$userId': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserts a new showcase project into the database.
     * 
     * This does not add artifacts to the project.
     *
     * @param \Model\ShowcaseProject $project the project o add
     * @return boolean true on success, false otherwise
     */
    public function addNewProject($project) {
        try {
            $sql = '
            INSERT INTO showcase_project (
                sp_id, sp_title, sp_description, sp_published, sp_date_created, sp_date_updated
            ) VALUES (
                :id, :title, :description, :published, :created, :updated
            )
            ';
            $params = array(
                ':id' => $project->getId(),
                ':title' => $project->getTitle(),
                ':description' => $project->getDescription(),
                ':published' => $project->isPublished(),
                ':created' => QueryUtils::FormatDate($project->getDateCreated()),
                ':updated' => QueryUtils::FormatDate($project->getDateUpdated())
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add new showcase project: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserts new project artifacts into the database and associates them with their project.
     *
     * @param \Model\ShowcaseProjectArtifact[] $artifacts an array of artifacts to insert
     * @return boolean true on success, false otherwise
     */
    public function addNewProjectArtifacts($artifacts) {
        try {
            $this->conn->startTransaction();

            $sql = '
            INSERT INTO showcase_project_artifact (
                spa_id, spa_sp_id, spa_name, spa_description, spa_file_uploaded, spa_link, spa_published,
                spa_date_created, spa_date_updated
            ) VALUES (
                :id, :pid, :name, :description, :file, :link, :published, :created, :updated
            )
            ';

            foreach ($artifacts as $a) {
                $params = array(
                    ':id' => $a->getId(),
                    ':pid' => $a->getProject()->getId(),
                    ':name' => $a->getName(),
                    ':description' => $a->getDescription(),
                    ':file' => $a->isFileUploaded(),
                    ':link' => $a->getLink(),
                    ':published' => $a->isPublished(),
                    ':created' => QueryUtils::FormatDate($a->getDateCreated()),
                    ':updated' => QueryUtils::FormatDate($a->getDateUpdated())
                );
                $this->conn->execute($sql, $params);
            }

            $this->conn->commit();

            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            $this->logger->error('Failed to add new showcase project artifacts: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Adds a user as a collaborator on a project, inserting a new entry into the `showcase_worked_on` table.
     *
     * @param string $projectId
     * @param string $userId
     * @return boolean true on success, false otherwise
     */
    public function associateProjectWithUser($projectId, $userId) {
        try {
            $sql = '
            INSERT INTO showcase_worked_on (
                swo_sp_id, swo_u_id
            ) VALUES (
                :pid,
                :uid
            )
            ';
            $params = array(
                ':pid' => $projectId,
                ':uid' => $userId
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to associated user $userId with project $projectId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the values of a project entry in the database.
     *
     * @param \Model\ShowcaseProject $project the project to update
     * @return boolean true on success, false otherwise
     */
    public function updateProject($project) {
        try {
            $sql = '
            UPDATE showcase_project SET
                sp_title = :title,
                sp_description = :description,
                sp_published = :published,
                sp_date_updated = :updated
            WHERE sp_id = :id
            ';
            $params = array(
                ':id' => $project->getId(),
                ':title' => $project->getTitle(),
                ':description' => $project->getDescription(),
                ':published' => $project->isPublished(),
                ':updated' => QueryUtils::FormatDate($project->getDateUpdated())
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update showcase project: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates information about a showcase project artifact in the database.
     *
     * @param \Model\ShowcaseProjectArtifact $artifact
     * @return boolean true on success, false otherwise
     */
    public function updateProjectArtifact($artifact) {
        try {
            $sql = '
            UPDATE showcase_project_artifact SET
                spa_name = :name,
                spa_description = :description,
                spa_file_uploaded = :file,
                spa_link = :link,
                spa_published = :published,
                spa_updated = :updated
            WHERE spa_id = :id
            ';
            $params = array(
                ':id' => $artifact->getId(),
                ':name' => $artifact->getName(),
                ':description' => $artifact->getDescription(),
                ':file' => $artifact->isFileUploaded(),
                ':link' => $artifact->getLink(),
                ':published' => $artifact->isPublished(),
                ':updated' => QueryUtils::FormatDate($artifact->getDateUpdated())
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update showcase project artifact: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Uses information from a row in the database to create a ShowcaseProject object.
     *
     * @param mixed[] $row the row from the database
     * @return \Model\ShowcaseProject the extracted project
     */
    public static function ExtractShowcaseProjectFromRow($row) {
        $project = new ShowcaseProject($row['sp_id']);
        $project
            ->setTitle($row['sp_title'])
            ->setDescription($row['sp_description'])
            ->setPublished($row['sp_published'] ? true : false)
            ->setDateCreated(new \DateTime($row['sp_date_created']))
            ->setDateUpdated($row['sp_date_updated'] ? new \DateTime($row['sp_date_updated']) : null);

        return $project;
    }

    /**
     * Uses information from a row in the database to create a ShowcaseProjectArtifact object.
     * 
     * NOTE: the artifact object can have a reference to the project it belongs to. The reference is not set
     * in this function, however, as it is assumed that the project information is not available. This helps
     * reduce memory overhead and avoid unnecessary duplicates of project objects.
     *
     * @param mixed[] $row the row from the database
     * @return \Model\ShowcaseProjectArtifact the extracted artifact
     */
    public static function ExtractShowcaseArtifactFromRow($row) {
        $artifact = new ShowcaseProjectArtifact($row['spa_id']);
        $artifact
            ->setProjectId($row['spa_sp_id'])
            ->setName($row['spa_name'])
            ->setDescription($row['spa_description'])
            ->setFileName($row['spa_file_name'])
            ->setLink($row['spa_link'])
            ->setPublished($row['spa_published'] ? true : false)
            ->setDateCreated(new \DateTime($row['spa_date_created']))
            ->setDateUpdated($row['spa_date_updated'] ? new \DateTime($row['spa_date_updated']) : null);
        
        return $artifact;
    }
}
