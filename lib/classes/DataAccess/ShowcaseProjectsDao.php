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
            ORDER BY swo_u_id, swo_sp_id
            ";
            $params = array(':id' => $userId);
            $results = $this->conn->query($sql, $params);

            $projects = array();
            $pid = '';
            $uid = '';
            foreach ($results as $row) {
                if ($row['swo_u_id'] != $uid || $row['swo_sp_id'] != $pid) {
                    $uid = $row['swo_u_id'];
                    $pid = $row['swo_sp_id'];
                    $projects[] = self::ExtractShowcaseProjectFromRow($row, true);
                }
                if ($includeArtifacts) {
                    $p = $projects[\count($projects) - 1];
                    $artifact = self::ExtractShowcaseArtifactFromRow($row);
                    if ($artifact) {
                        $p->addArtifact($artifact);
                    }
                }
            }

            // Finally sort the projects by their create date
            \usort($projects, function($p1, $p2) {
                return $p1->getDateCreated() > $p2->getDateCreated();
            });

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch projects for user with id '$userId': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches a single showcase project with the provided ID.
     * 
     * This will also fetch all the artifacts associated with the project.
     *
     * @param string $projectId the ID of the project to retrieve
     * @param bool $includeUserMetadata indicates whether to also extract user metadata about the project (such as
     * the visibility and invitation status) into the ShowcaseProject object. Defaults to false.
     * @return \Model\ShowcaseProject|boolean the project on success, false if not found or an error occurred
     */
    public function getProject($projectId, $includeUserMetadata = false) {
        try {
            $sql = '
            SELECT * 
            FROM showcase_project
            LEFT OUTER JOIN showcase_project_artifact ON spa_sp_id = sp_id
            INNER JOIN showcase_worked_on ON swo_sp_id = sp_id
            WHERE sp_id = :id
            ORDER BY sp_id, swo_u_id, spa_date_created
            ';
            $params = array(':id' => $projectId);

            $results = $this->conn->query($sql, $params);
            if (\count($results) == 0) {
                return false;
            }

            $project = self::ExtractShowcaseProjectFromRow($results[0], $includeUserMetadata);

            // When we loop over the rows to extract the artifacts, we also need to make sure that we don't duplicate
            // artifacts when there are multiple users collaborating on a single project. So we capture the user ID
            // of the first row and check to see if it changes. If it does, then we are in danger of creating duplicate
            // artifacts and break the loop
            $uid = $results[0]['swo_u_id'];
            foreach ($results as $row) {
                if($row['swo_u_id'] != $uid) {
                    break;
                }
                $artifact = self::ExtractShowcaseArtifactFromRow($row);
                if ($artifact) {
                    $project->addArtifact($artifact);
                }
            }

            return $project;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch project with ID '$projectId': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches a project artifact with the provided ID.
     *
     * @param string $artifactId the string ID of the artifact
     * @return \Model\ShowcaseProjectArtifact the artifact on success, false if not found or an error occurs
     */
    public function getProjectArtifact($artifactId) {
        try {
            $sql = '
            SELECT *
            FROM showcase_project_artifact
            WHERE spa_id = :id
            ';  
            $params = array(':id' => $artifactId);

            $results = $this->conn->query($sql, $params);
            if (\count($results) == 0) {
                return false;
            }

            return self::ExtractShowcaseArtifactFromRow($results[0]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch project artifact with ID '$artifactId': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserts a new showcase project into the database.
     * 
     * This does not add artifacts to the project. It does optionally take a user ID which it will use to associate
     * a user with a project. If no user ID is provided, a corresponding entry in the `showcase_worked_on` table
     * will not be created.
     *
     * @param \Model\ShowcaseProject $project the project to add
     * @param string|null $userId the ID of the user to associate with the project.
     * @return boolean true on success, false otherwise
     */
    public function addNewProject($project, $userId = null) {
        try {
            $this->conn->startTransaction();
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

            if ($userId != null) {
                $ok = $this->associateProjectWithUser($project, $userId);
                if (!$ok) {
                    $this->conn->rollback();
                    return false;
                }
            }

            $this->conn->commit();

            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            $this->logger->error('Failed to add new showcase project: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserts a new project artifact into the database and associates it with its project.
     *
     * @param \Model\ShowcaseProjectArtifact $artifacts an artifacts to insert
     * @return boolean true on success, false otherwise
     */
    public function addNewProjectArtifact($artifacts) {
        try {
            $sql = '
            INSERT INTO showcase_project_artifact (
                spa_id, spa_sp_id, spa_name, spa_description, spa_file_uploaded, spa_link, spa_published,
                spa_date_created, spa_date_updated
            ) VALUES (
                :id, :pid, :name, :description, :file, :link, :published, :created, :updated
            )
            ';
            $params = array(
                ':id' => $artifacts->getId(),
                ':pid' => $artifacts->getProject()->getId(),
                ':name' => $artifacts->getName(),
                ':description' => $artifacts->getDescription(),
                ':file' => $artifacts->isFileUploaded(),
                ':link' => $artifacts->getLink(),
                ':published' => $artifacts->isPublished(),
                ':created' => QueryUtils::FormatDate($artifacts->getDateCreated()),
                ':updated' => QueryUtils::FormatDate($artifacts->getDateUpdated())
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add new showcase project artifact: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Adds a user as a collaborator on a project, inserting a new entry into the `showcase_worked_on` table.
     *
     * @param \Model\ShowcaseProject $project
     * @param string $userId
     * @return boolean true on success, false otherwise
     */
    public function associateProjectWithUser($project, $userId) {
        try {
            $sql = '
            INSERT INTO showcase_worked_on (
                swo_sp_id, swo_u_id, swo_invited, swo_accepted, swo_is_visible
            ) VALUES (
                :pid,
                :uid,
                :invited,
                :accepted,
                :visible
            )
            ';
            $params = array(
                ':pid' => $project->getId(),
                ':uid' => $userId,
                ':invited' => $project->isUserInvited(),
                ':accepted' => $project->hasUserAcceptedInvitation(),
                ':visible' => $project->isUserVisible()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to associated user $userId with project: " . $e->getMessage());
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
     * Deltes a project artifact from the database.
     *
     * @param string $artifactId the ID of the artifact to delete
     * @return boolean true on success, false otherwise
     */
    public function deleteProjectArtifact($artifactId) {
        try {
            $sql = '
            DELETE FROM showcase_project_artifact
            WHERE spa_id = :id
            ';
            $params = array(':id' => $artifactId);

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete showcase project artifact: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches the user profile entries that are connected with the project that has the provided ID.
     *
     * @param string $projectId the ID of the project
     * @return \Model\ShowcaseProfile[]|boolean an array of profiles on success, false otherwise
     */
    public function getProjectCollaborators($projectId) {
        try {
            $sql = '
            SELECT *
            FROM user, showcase_user_profile, showcase_worked_on
            WHERE u_id = swo_u_id AND swo_sp_id = :id AND sup_u_id = u_id
            ';
            $params = array(':id' => $projectId);
            
            $results = $this->conn->query($sql, $params);
            
            $associates = array();
            foreach ($results as $row) {
                $associates[] = ShowcaseProfilesDao::ExtractShowcaseProfileFromRow($row, true);
            }

            return $associates;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch associates of project with ID '$projectId': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Queries the database to determine if a user is a collaborator on a project.
     *
     * @param string $projectId the ID of the project
     * @param string $userId the ID of the user
     * @return boolean|null true or false if the query is successful, null otherwise
     */
    public function verifyUserIsCollaboratorOnProject($projectId, $userId) {
        try {
            $sql = '
            SELECT *
            FROM showcase_worked_on
            WHERE swo_u_id = :uid AND swo_sp_id = :pid
            ';
            $params = array(':uid' => $userId, ':pid' => $projectId);
            
            $results = $this->conn->query($sql, $params);

            return \count($results) != 0;
        } catch (\Exception $e) {
            $this->logger->error("Failed to determine if user $userId is a collaborator for project $projectId: " . 
                $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches additional metadata about how a user is associated with a project they are collaborating on.
     * 
     * The returned result will be an associative array with the following keys:
     * - `isVisible`
     * - `invited`
     * - `acceptedInvitation`
     *
     * @param string $projectId the ID of the project the user is associated with
     * @param string $userId the ID the user
     * @return bool[]|bool the metadata as an array on success, false if not found or an error occurs
     */
    public function getProjectCollaborationMetadataForUser($projectId, $userId) {
        try {
            $sql = '
            SELECT *
            FROM showcase_worked_on
            WHERE swo_sp_id = :pid AND swo_u_id = :uid
            ';
            $params = array(':uid' => $userId, ':pid' => $projectId);

            $results = $this->conn->query($sql, $params);

            if (\count($results) == 0) {
                return false;
            }

            return array(
                'isVisible' => $results[0]['swo_is_visible'],
                'invited' => $results[0]['swo_invited'],
                'acceptedInvitation' => $results[0]['swo_accepted']
            );
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch metadata for user $userId on project $projectId: " . 
                $e->getMessage());
            return false;
        }
    }

    /**
     * Uses information from a row in the database to create a ShowcaseProject object.
     *
     * @param mixed[] $row the row from the database
     * @param bool $includeUserMetadata includes metadata about the user associated with the project, such as whether
     * they indicated the project is visible, or have been invited/accepted an invitation to the project
     * @return \Model\ShowcaseProject the extracted project
     */
    public static function ExtractShowcaseProjectFromRow($row, $includeUserMetadata = false) {
        $project = new ShowcaseProject($row['sp_id']);
        $project
            ->setTitle($row['sp_title'])
            ->setDescription($row['sp_description'])
            ->setPublished($row['sp_published'] ? true : false)
            ->setDateCreated(new \DateTime($row['sp_date_created']))
            ->setDateUpdated($row['sp_date_updated'] ? new \DateTime($row['sp_date_updated']) : null);

        if ($includeUserMetadata) {
            $project
                ->setUserInvited($row['swo_invited'])
                ->setUserAcceptedInvitation($row['swo_accepted'])
                ->setUserVisible($row['swo_is_visible']);
        }

        return $project;
    }

    /**
     * Uses information from a row in the database to create a ShowcaseProjectArtifact object. 
     * 
     * If no artifact is able to be extracted from the row, the function will return false
     * 
     * NOTE: the artifact object can have a reference to the project it belongs to. By default, he reference is not set
     * in this function, as it is assumed that the project information is not available. This helps
     * reduce memory overhead and avoid unnecessary duplicates of project objects. To also create a ShowcaseProject
     * object from information in the row, set the `$projetInRow` argument to `true`.
     *
     * @param mixed[] $row the row from the database
     * @param boolean $projectInRow determines whether to also create a ShowcaseProject object from the row
     * @return \Model\ShowcaseProjectArtifact|boolean the extracted artifact if it exists, false otherwise
     */
    public static function ExtractShowcaseArtifactFromRow($row, $projectInRow = false) {
        if (!isset($row['spa_id'])) {
            return false;
        }
        $artifact = new ShowcaseProjectArtifact($row['spa_id']);
        $artifact
            ->setProjectId($row['spa_sp_id'])
            ->setName($row['spa_name'])
            ->setDescription($row['spa_description'])
            ->setFileUploaded($row['spa_file_uploaded'] ? true : false)
            ->setLink($row['spa_link'])
            ->setPublished($row['spa_published'] ? true : false)
            ->setDateCreated(new \DateTime($row['spa_date_created']))
            ->setDateUpdated($row['spa_date_updated'] ? new \DateTime($row['spa_date_updated']) : null);

        if ($projectInRow) {
            $artifact->setProject(self::ExtractShowcaseProjectFromRow($row));
        }
        
        return $artifact;
    }
}
