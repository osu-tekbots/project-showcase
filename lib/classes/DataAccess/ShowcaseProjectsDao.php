<?php
namespace DataAccess;

use Model\ShowcaseProject;
use Model\ShowcaseProjectArtifact;
use Model\CollaborationInvitation;
use Model\ShowcaseProjectImage;

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
                    $projects[] = self::ExtractShowcaseProjectFromRow($row);
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
     * @return \Model\ShowcaseProject|boolean the project on success, false if not found or an error occurred
     */
    public function getProject($projectId) {
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

            $project = self::ExtractShowcaseProjectFromRow($results[0]);

            // When we loop over the rows to extract the artifacts, we also need to make sure that we don't duplicate
            // artifacts when there are multiple users collaborating on a single project. So we capture the user ID
            // of the first row and check to see if it changes. If it does, then we are in danger of creating duplicate
            // artifacts and break the loop
            $uid = $results[0]['swo_u_id'];
            foreach ($results as $row) {
                if ($row['swo_u_id'] != $uid) {
                    break;
                }
                // Check to see if there is an artifact in this row. If there is, extract it.
                $artifact = self::ExtractShowcaseArtifactFromRow($row);
                if ($artifact) {
                    $project->addArtifact($artifact);
                }
            }

            // Now get the images. We do this separately to simplify the query.
            $sql = '
            SELECT *
            FROM showcase_project_image
            WHERE spi_sp_id = :id
            ORDER BY spi_date_created
            ';
            $results = $this->conn->query($sql, $params);

            $images = array();
            foreach($results as $row) {
                $image = self::ExtractShowcaseProjectImageFromRow($row);
                $image->setProject($project);
                $images[] = $image;
            }
            \usort($images, function($i1, $i2) {
                return $i1->getDateCreated() > $i2->getDateCreated();
            });
            $project->setImages($images);

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
     * Fetches project image metadata from the database.
     *
     * @param string $imageId the ID of the image to fetch
     * @return \Model\ShowcaseProjectImage|boolean the image on success, false if not found or an error occurs
     */
    public function getProjectImage($imageId) {
        try {
            $sql = '
            SELECT *
            FROM showcase_project_image
            WHERE spi_id = :id
            ';  
            $params = array(':id' => $imageId);

            $results = $this->conn->query($sql, $params);
            if (\count($results) == 0) {
                return false;
            }

            return self::ExtractShowcaseProjectImageFromRow($results[0]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch project image with ID '$imageId': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserts a new showcase project into the database.
     * 
     * This does not add artifacts to the project. It does take a user ID which it will use to associate
     * a user with a project. All projects must be associated with a user, so this argument is required.
     *
     * @param \Model\ShowcaseProject $project the project to add
     * @param string|null $userId the ID of the user to associate with the project.
     * @return boolean true on success, false otherwise
     */
    public function addNewProject($project, $userId) {
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

            $ok = $this->associateProjectWithUser($project->getId(), $userId);
            if (!$ok) {
                $this->conn->rollback();
                return false;
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
     * Inserts new metadata about an image for a showcase project in the database.
     *
     * @param \Model\ShowcaseProjectImage $image
     * @return boolean true on success, false otherwise
     */
    public function addNewProjectImage($image) {
        try {
            $sql = '
            INSERT INTO showcase_project_image (
                spi_id, spi_sp_id, spi_file_name, spi_date_created
            ) VALUES (
                :id, :pid, :name, :created
            )
            ';
            $params = array(
                ':id' => $image->getId(),
                ':pid' => $image->getProjectId(),
                ':name' => $image->getFileName(),
                ':created' => QueryUtils::FormatDate($image->getDateCreated())
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add new showcase project image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Adds a user as a collaborator on a project, inserting a new entry into the `showcase_worked_on` table.
     *
     * @param string $projectId the ID of the project
     * @param string $userId the ID of the user
     * @param bool $accepted indicates whether the user has 'accepted' an invitation. This should only be set to true
     * when the user is the creator of the project. Invitations should be set to false. Defaults to false.
     * @return boolean true on success, false otherwise
     */
    public function associateProjectWithUser($projectId, $userId) {
        try {
            $sql = '
            INSERT INTO showcase_worked_on (
                swo_sp_id, swo_u_id, swo_is_visible
            ) VALUES (
                :pid,
                :uid,
                :visible
            )
            ';
            $params = array(
                ':pid' => $projectId,
                ':uid' => $userId,
                ':visible' => true
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to associated user $userId with project $projectId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the visibility of a user, meaning whether or not the user will be publically associated with the
     * project or not.
     *
     * @param string $projectId the ID of the project the user is associated with
     * @param string $userId the ID of the user
     * @param bool $visible whether the user should be visible or not
     * @return bool true on success, false otherwise
     */
    public function updateVisibilityOfUserForProject($projectId, $userId, $visible) {
        try {
            $sql = '
            UPDATE showcase_worked_on SET
                swo_is_visible = :visible
            WHERE swo_sp_id = :pid AND swo_u_id = :uid
            ';
            $params = array(
                ':pid' => $projectId,
                ':uid' => $userId,
                ':visible' => $visible
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to associated user $userId with project $projectId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Accepts an invitation for collaborating on a project.
     *
     * @param string $projectId the ID of the project the user is joining
     * @param string $userId the ID of the user joining the project
     * @param string $invitationId the ID of the invitation used to invite the user to join the project
     * @return boolean true on success, false otherwise
     */
    public function acceptInvitationToCollaborateOnProject($projectId, $userId, $invitationId) {
        try {
            $this->conn->startTransaction();
            // First create the new entry   
            $ok = $this->associateProjectWithUser($projectId, $userId);
            if (!$ok) {
                throw new Exception('Failed to associate project with user');
            }

            // Remove the invitation
            $ok = $this->removeInvitationToCollaborateOnProject($invitationId);
            if (!$ok) {
                throw new \Exception('Failed to remove invitation after accepting');
            }

            // Commit the transaction
            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            $this->logger->error("Failed to accept collaboration invitates for user $userId with project $projectId: " 
            . $e->getMessage());
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
     * Deletes a project artifact from the database.
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
     * Deletes project image metadata from the database.
     *
     * @param string $imageId the ID of the image to delete
     * @return boolean true on success, false otherwise
     */
    public function deleteProjectImage($imageId) {
        try {
            $sql = '
            DELETE FROM showcase_project_image
            WHERE spi_id = :id
            ';
            $params = array(':id' => $imageId);

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete showcase project image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches the user profile entries that are connected with the project that has the provided ID.
     *
     * @param string $projectId the ID of the project
     * @return \Model\ShowcaseProfile[]|boolean an array of profiles on success, false otherwise
     */
    public function getProjectCollaborators($projectId, $visibleOnly = false) {
        try {
            $visibleSql = $visibleOnly ? 'AND swo_is_visible = :visible' : '';
            $sql = "
            SELECT *
            FROM user, showcase_user_profile, showcase_worked_on
            WHERE u_id = swo_u_id AND swo_sp_id = :id AND sup_u_id = u_id $visibleSql
            ";
            $params = array(':id' => $projectId);
            if ($visibleOnly) {
                $params[':visible'] = true;
            }
            
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
     * @param boolean $checkVisibility indicates whether to also check if the collaborator has preferred that their
     * association with the project is not publically visible (if the association exists)
     * @return boolean|null true or false if the query is successful, null otherwise
     */
    public function verifyUserIsCollaboratorOnProject($projectId, $userId, $checkVisibility = false) {
        try {
            $checkVisibilitySql = $checkVisibility ? ' AND swo_is_visible = :visible' : '';
            $sql = "
            SELECT *
            FROM showcase_worked_on
            WHERE swo_u_id = :uid AND swo_sp_id = :pid $checkVisibilitySql
            ";
            $params = array(':uid' => $userId, ':pid' => $projectId);
            if ($checkVisibility) {
                $params[':visible'] = true;
            }
            
            $results = $this->conn->query($sql, $params);

            return \count($results) != 0;
        } catch (\Exception $e) {
            $this->logger->error("Failed to determine if user $userId is a collaborator for project $projectId: " . 
                $e->getMessage());
            return null;
        }
    }

    /**
     * Stores metadata about an invitation in the database.
     *
     * @param \Model\CollaborationInvitation $invitation the invitation to add
     * @return boolean true on success, false otherwise
     */
    public function addInvitationToCollaborateOnProject($invitation) {
        try {
            $sql = '
            INSERT INTO showcase_collaboration_invite (
                sci_id, sci_sp_id, sci_email, sci_date_created
            ) VALUES (
                :id, :pid, :email, :created
            )
            ';
            $params = array(
                ':id' => $invitation->getId(),
                ':pid' => $invitation->getProjectId(),
                ':email' => $invitation->getEmail(),
                ':created' => QueryUtils::FormatDate($invitation->getDateCreated())
            );

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add new invitation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches a collaboration invitation with the provided ID.
     * 
     * If no invitation is found, the function will return false.
     *
     * @param string $invitationId the ID of the invitation to fetch
     * @return \Model\CollaborationInvitation|bool the invitation on success, false otherwise
     */
    public function getInvitationToCollaborateOnProject($invitationId) {
        try {
            $sql = '
            SELECT *
            FROM showcase_collaboration_invite, showcase_project
            WHERE sci_sp_id = sp_id AND sci_id = :id
            ';
            $params = array(
                ':id' => $invitationId
            );

            $results = $this->conn->query($sql, $params);
            if (\count($results) == 0) {
                return false;
            }

            return self::ExtractCollaborationInvitationFromRow($results[0], true);
        } catch (\Exception $e) {
            $this->logger->error('Failed to add new invitation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Removes an invitation to collaborate on a project.
     * 
     * This function should be used whether an individual accepts or declines the invitation.
     *
     * @param string $invitationId the ID of the invitation to remove
     * @return boolean true on success, false otherwise
     */
    public function removeInvitationToCollaborateOnProject($invitationId) {
        try {
            $sql = '
            DELETE FROM showcase_collaboration_invite WHERE sci_id = :id
            ';
            $params = array(
                ':id' => $invitationId
            );

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to remove the invitation with ID $invitationId: " . $e->getMessage());
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

    /**
     * Uses information from a row in the database to create a ShowcaseProjectImage object.
     * 
     * If there is no image available in the row then the function will return false.
     *
     * @param mixed[] $row the row from the databsae
     * @return \Model\ShowcaseProjectImage|boolean the extracted image if it exists, false otherwise
     */
    public static function ExtractShowcaseProjectImageFromRow($row) {
        if (!isset($row['spi_id'])) {
            return false;
        }
        $image = new ShowcaseProjectImage($row['spi_id']);
        $image
            ->setProjectId($row['spi_sp_id'])
            ->setFileName($row['spi_file_name'])
            ->setDateCreated(new \DateTime($row['spi_date_created']));
        return $image;
    }

    /**
     * Users information from a row in the database to create a CollaborationInvitation object.
     *
     * @param mixed[] $row the row from the database
     * @param boolean $projectInRow indicates whether the project information is also in the row and should be
     * extracted.
     * @return \Model\CollaborationInvitation the invitation
     */
    public static function ExtractCollaborationInvitationFromRow($row, $projectInRow = false) {
        $invitation = new CollaborationInvitation($row['sci_id']);
        $invitation
            ->setProjectId($row['sci_sp_id'])
            ->setEmail($row['sci_email'])
            ->setDateCreated(new \DateTime($row['sci_date_created']));
        if ($projectInRow) {
            $invitation->setProject(self::ExtractShowcaseProjectFromRow($row));
        }
        return $invitation;
    }
}
