<?php
namespace DataAccess;

use Model\ShowcaseProject;
use Model\ShowcaseProjectArtifact;
use Model\CollaborationInvitation;
use Model\ShowcaseProjectImage;
use Model\Award;

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
     * Fetches all the projects from the database.
     *
     * @param integer $count the number to limit the return array size to
     * @param integer $offset the offset in the database table to start retrieve projects from
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function getAllProjects($count = 0, $offset = 0, $includeHidden = false) {
        try {
            $limit = '';
            if ($offset > 0) {
                $limit = "LIMIT $offset";
                if ($count > 0) {
                    $limit .= ", $count";
                }
            } elseif ($count > 0) {
                $limit = "LIMIT $count";
            }
            $hiddenCondition = !$includeHidden ? 'WHERE sp_published = 1' : '';
            $sql = "
            SELECT *
            FROM showcase_project
            $hiddenCondition
            ORDER BY sp_date_updated ASC
            $limit
            ";
            $results = $this->conn->query($sql);

            $projects = array();
            foreach ($results as $row) {
                $projects[] = self::ExtractShowcaseProjectFromRow($row);
            }

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all projects: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches all the projects from the database within the last two years.
     *
     * @param integer $count the number to limit the return array size to
     * @param integer $offset the offset in the database table to start retrieve projects from
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function getAllRecentlyCreatedProjects($count = 0, $offset = 0, $includeHidden = false) {
        try {
            $limit = '';
            if ($offset > 0) {
                $limit = "LIMIT $offset";
                if ($count > 0) {
                    $limit .= ", $count";
                }
            } elseif ($count > 0) {
                $limit = "LIMIT $count";
            }
            $hiddenCondition = !$includeHidden ? 'WHERE sp_published = 1' : '';
            $date = date('Y-m-d', strtotime(date("Y-m-d") . ' -2 years'));
            $sql = "
            SELECT *
            FROM showcase_project
            $hiddenCondition
            AND sp_date_created >= '$date'
            ORDER BY sp_date_updated ASC
            $limit
            ";
            $results = $this->conn->query($sql);

            $projects = array();
            foreach ($results as $row) {
                $projects[] = self::ExtractShowcaseProjectFromRow($row);
            }

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all projects: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Fetches all the projects from the database.
     *
     * @param integer $count the number to limit the return array size to
     * @param integer $offset the offset in the database table to start retrieve projects from
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function getAllProjectsSortByScore($count = 0, $offset = 0, $includeHidden = false) {
        try {
            $limit = '';
            if ($offset > 0) {
                $limit = "LIMIT $offset";
                if ($count > 0) {
                    $limit .= ", $count";
                }
            } elseif ($count > 0) {
                $limit = "LIMIT $count";
            }
            $hiddenCondition = !$includeHidden ? 'WHERE sp_published = 1' : '';
            $sql = "
            SELECT *
            FROM showcase_project
            $hiddenCondition
            ORDER BY sp_score DESC, sp_title ASC
            $limit
            ";
            $results = $this->conn->query($sql);

            $projects = array();
            foreach ($results as $row) {
                $projects[] = self::ExtractShowcaseProjectFromRow($row);
            }

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all projects: ' . $e->getMessage());
            return false;
        }
    }
	
	
	/**
     * Fetches all the projects from the database by type.
     *
     * @param string $category the short name of the category
     * @param integer $count the number to limit the return array size to
     * @param integer $offset the offset in the database table to start retrieve projects from
     * @return \Model\ShowcaseProject[] an array of showcase projects on success, false otherwise
     */
    public function getProjectsByCategory($category, $count = 0, $offset = 0, $includeHidden = false) {
        try {
            $limit = '';
            if ($offset > 0) {
                $limit = "LIMIT $offset";
                if ($count > 0) {
                    $limit .= ", $count";
                }
            } elseif ($count > 0) {
                $limit = "LIMIT $count";
            }
            $hiddenCondition = !$includeHidden ? ' AND sp_published = 1 ' : '';
            $sql = "
            SELECT showcase_project.* 
            FROM showcase_project 
            INNER JOIN showcase_category ON showcase_category.id = showcase_project.sp_category 
			WHERE showcase_category.shortname = '$category' 
			$hiddenCondition 
			ORDER BY sp_title ASC 
            $limit
            ";

            $results = $this->conn->query($sql);

            $projects = array();
            foreach ($results as $row) {
                $projects[] = self::ExtractShowcaseProjectFromRow($row);
            }

			
            return $projects;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all projects: ' . $e->getMessage());
            return false;
        }
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
    public function getUserProjects($userId, $includeArtifacts = false, $includeHidden = false, $sort = 'title') {
        try {
            $artifactsTable = $includeArtifacts ? ', showcase_project_artifact ' : '';
            $artifactsPredicate = $includeArtifacts ? 'AND spa_sp_id = sp_id' : '';
            $hiddenPredicate = !$includeHidden ? 'AND sp_published = 1' : '';
            $sql = "
            SELECT * 
            FROM showcase_project, showcase_worked_on $artifactsTable
            WHERE swo_u_id = :id AND swo_sp_id = sp_id $artifactsPredicate $hiddenPredicate
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
			// TODO: This needs to be imporved for readability and function
            if ($sort == 'title')
				\usort($projects, function($p1, $p2) {		
						return ($p1->getTitle() > $p2->getTitle() ? 1 : 0);
				});
			else
				\usort($projects, function($p1, $p2) {		
						return ($p1->getDateCreated() > $p2->getDateCreated() ? 1 : 0);
				});

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch projects for user with id '$userId': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches projects that match the constraints specified in the query.
     * 
     * The `$query` parameter should be a string or an associative array with the following keys:
     * - `query`: the string query used to search project titles, description, and collaborator names
     *
     * @param string|mixed[] $query the string or associative array specifying the query/parameters
     * @return \Model\ShowcaseProject[]|boolean an array of projects on success, false otherwise
     */
    public function getProjectsWithQuery($query) {
        try {
            $sql = '
            SELECT p.*
            FROM showcase_project p
            LEFT OUTER JOIN (
                SELECT *
                FROM capstone_keyword_for, capstone_keyword
                WHERE ckf_ck_id = ck_id
            ) AS keywords ON ckf_entity_id = sp_id
            WHERE
                sp_published = 1 AND (
                    LOWER(sp_title) LIKE :query
                    OR LOWER(sp_description) LIKE :query
                    OR LOWER(ck_name) LIKE :query
                )
            GROUP BY sp_id
            ';
            $params = array(':query' => strtolower("%$query%"));
            
            $results = $this->conn->query($sql, $params);

            $projects = array();
            foreach ($results as $row) {
                $projects[] = self::ExtractShowcaseProjectFromRow($row);
            }

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch projects with query '$query': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches projects that match the constraints specified in the query that were created in the last 2 years.
     * 
     * The `$query` parameter should be a string or an associative array with the following keys:
     * - `query`: the string query used to search project titles, description, and collaborator names
     *
     * @param string|mixed[] $query the string or associative array specifying the query/parameters
     * @return \Model\ShowcaseProject[]|boolean an array of projects on success, false otherwise
     */
    public function getRecentlyCreatedProjectsWithQuery($query) {
        try {
            $date = date('Y-m-d', strtotime(date("Y-m-d") . ' -2 years'));
            $sql = "
            SELECT p.*
            FROM showcase_project p
            LEFT OUTER JOIN (
                SELECT *
                FROM capstone_keyword_for, capstone_keyword
                WHERE ckf_ck_id = ck_id
            ) AS keywords ON ckf_entity_id = sp_id
            WHERE
                sp_published = 1 AND (
                    LOWER(sp_title) LIKE :query
                    OR LOWER(sp_description) LIKE :query
                    OR LOWER(ck_name) LIKE :query
                )
            AND sp_date_created >= '$date'
            GROUP BY sp_id
            ";
            $params = array(':query' => strtolower("%$query%"));
            
            $results = $this->conn->query($sql, $params);

            $projects = array();
            foreach ($results as $row) {
                $projects[] = self::ExtractShowcaseProjectFromRow($row);
            }

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch projects with query '$query': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches the most recently added projects by date.
     *
     * @param integer $limit the maximum number of projects to fetch. Defaults to 10.
     * @return \Model\ShowcaseProject[] an array of projects on success, false otherwise
     */
    public function getMostRecentProjects($limit = 10) {
        try {
            $sql = '
            SELECT *
            FROM showcase_project
            WHERE sp_published = 1
            ORDER BY sp_date_created DESC
            LIMIT :limit
            ';
            $params = array(':limit' => $limit);

            $results = $this->conn->query($sql, $params);

            $projects = array();
            foreach ($results as $row) {
                $projects[] = self::ExtractShowcaseProjectFromRow($row);
            }

            return $projects;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get the $limit most recent projects: " . $e->getMessage());
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
            ORDER BY spi_order ASC
            ';
            $results = $this->conn->query($sql, $params);

            $images = array();
            foreach ($results as $row) {
                $image = self::ExtractShowcaseProjectImageFromRow($row);
                $image->setProject($project);
                $images[] = $image;
            }
            $project->setImages($images);

            return $project;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch project with ID '$projectId': " . $e->getMessage());
            return false;
        }
    }

	/**
     * Fetches a awards (if any) for a showcase project with the provided ID.
     * 
     *
     * @param string $projectId the ID of the awards to retrieve
     * @return \Model\ShowcaseProject|boolean the project on success, false if not found or an error occurred
     */
    public function getProjectAwards($projectId) {
        try {
            $sql = '
            SELECT showcase_awards.* 
			FROM `showcase_awards` 
			WHERE id IN (SELECT award_id FROM showcase_project_awards WHERE showcase_project_awards.sp_id = :id) 
			ORDER BY showcase_awards.name ASC
            ';
            $params = array(':id' => $projectId);

            $results = $this->conn->query($sql, $params);
            if (\count($results) == 0) {
                return false;
            }

			$awards = array();
            foreach ($results as $row) {
                $awards[] = self::ExtractAwardFromRow($row);
            }

            return $awards;
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch awards for project with ID '$projectId': " . $e->getMessage());
            return false;
        }
    }

	/**
     * Fetches a project award with the provided ID.
     *
     * @param string $artifactId the string ID of the artifact
     * @return \Model\ShowcaseProjectArtifact the artifact on success, false if not found or an error occurs
     */
    public function getProjectAward($awardId) {
        try {
            $sql = '
            SELECT showcase_awards.*
            FROM showcase_awards
            WHERE id = :id
            ';  
            $params = array(':id' => $awardId);

            $results = $this->conn->query($sql, $params);
            if (\count($results) == 0) {
                return false;
            }

            return self::ExtractShowcaseAwardFromRow($results[0]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch project award with ID '$awardId': " . $e->getMessage());
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
                spa_date_created, spa_date_updated, spa_extension 
            ) VALUES (
                :id, :pid, :name, :description, :file, :link, :published, :created, :updated, :extension
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
                ':updated' => QueryUtils::FormatDate($artifacts->getDateUpdated()),
				':extension' => $artifacts->getExtension()
                
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
                spi_id, spi_sp_id, spi_file_name, spi_date_created, spi_order
            ) VALUES (
                :id, :pid, :name, :created, :order
            )
            ';
            $params = array(
                ':id' => $image->getId(),
                ':pid' => $image->getProjectId(),
                ':name' => $image->getFileName(),
                ':created' => QueryUtils::FormatDate($image->getDateCreated()),
                ':order' => $image->getOrder()
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
            $this->logger->error("Failed to accept collaboration invitation for user $userId with project $projectId: " 
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
                sp_category = :mycategory, 
				sp_score = :score, 
                sp_date_updated = :updated 
            WHERE sp_id = :id
            ';
            $params = array(
                ':id' => $project->getId(),
                ':title' => $project->getTitle(),
                ':description' => $project->getDescription(),
                ':published' => $project->isPublished(),
                ':mycategory' => $project->getCategory(),
                ':score' => $project->getScore(),
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
     * Sets the order for a project's images in the database based on the date they were added
     * This function is used for projects created before the order information was added
     *
     * @param string $imageId  the image to set the order of
     * @param int    $index    the order to assign the image
     * 
     * @return boolean true on success, false otherwise
     */

    public function setProjectImageOrder($imageId, $index) {
        try {
            // Update the order of the desired image
            $sql = '
            UPDATE showcase_project_image
            SET spi_order = :index
            WHERE spi_id = :id
            ';
            $params = array(
                ':index' => $index,
                ':id' => $imageId
            );
            $this->conn->execute($sql, $params);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to move showcase project image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Changes the order for a project image in the database
     *
     * @param string $projectId  the ID of the project to move the image in
     * @param string $imageId    the ID of the image to move
     * @param integer $oldIndex  the old index of the image
     * @param integer $newIndex  the new index of the image
     * 
     * @return boolean true on success, false otherwise
     */
    public function moveProjectImage($projectId, $imageId, $oldIndex, $newIndex) {
        try {
            // For compatability (ish!) with older projects
            if(is_null($oldIndex) || is_null($newIndex)) {
                $oldIndex = 1;
                $newIndex = 1;
            }

            // Change the order of other necessary images
            $sql = '
            UPDATE showcase_project_image
            SET spi_order = spi_order '.($oldIndex > $newIndex? '+ 1' : '- 1').'
            WHERE spi_sp_id = :pid
            AND spi_order >= :ione
            AND spi_order <= :itwo
            ';
            $params = array(
                ':pid' => $projectId,
                ':ione' => min($oldIndex, $newIndex),
                ':itwo' => max($oldIndex, $newIndex)
            );
            $this->conn->execute($sql, $params);

            // Update the order of the desired image
            $sql = '
            UPDATE showcase_project_image
            SET spi_order = :index
            WHERE spi_id = :id
            ';
            $params = array(
                ':index' => $newIndex,
                ':id' => $imageId
            );
            $this->conn->execute($sql, $params);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to move showcase project image: ' . $e->getMessage());
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
            unlink(PUBLIC_FILES . '/.private/artfacts' . "/$artifactId");

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
     * Deletes project invites from the database
     *
     * @param string $projectId the ID of the project invites to delete
     * @return boolean true on success, false otherwise
     */
    public function deleteProjectInvites($projectId) {
        try {
            $sql = '
            DELETE FROM showcase_collaboration_invite
            WHERE sci_sp_id = :id
            ';
            $params = array(':id' => $projectId);

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete showcase project invites: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes project collaborators from the database
     *
     * @param string $projectId the ID of the project collaborators to delete
     * @return boolean true on success, false otherwise
     */
    public function deleteProjectCollaborators($projectId) {
        try {
            $sql = '
            DELETE FROM showcase_worked_on
            WHERE swo_sp_id = :id
            ';
            $params = array(':id' => $projectId);

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete showcase project invites: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Deletes a single project collaborator from the database based on project and user id
     *
     * @param string $projectId the ID of the project collaborators to delete
     * @return boolean true on success, false otherwise
     */
    public function deleteProjectCollaborator($projectId, $userId) {
        try {
            $sql = '
            DELETE FROM showcase_worked_on
            WHERE swo_sp_id = :id AND swo_u_id = :uid
            ';
            $params = array(':id' => $projectId, ':uid' => $userId);

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete showcase project user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes project image metadata from the database as well as the image stored
     *
     * @param string $imageId the ID of the image to delete
     * @return boolean true on success, false otherwise
     */
    public function deleteProjectImage($imageId) {
        try {
            unlink(PUBLIC_FILES . '/.private/images/projects' . "/$imageId");

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
     * Deletes project information from the database
     *
     * @param string $projectId the ID of the project to delete
     * @return boolean true on success, false otherwise
     */
    public function deleteShowcaseProject($projectId) {
        try {
            $sql = '
            DELETE FROM showcase_project
            WHERE sp_id = :id
            ';
            $params = array(':id' => $projectId);

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete showcase project: ' . $e->getMessage());
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
     * Fetches various statistics about all showcase projects in the database.
     * 
     * Returns an associative array with the following keys:
     * - `keywords`: associative array of counts of all the keywords used in projects in the showcase
     * - `totalProjects`: total number of showcase projects
     * - `usersWithProjects`: total number of users that have at least on project in the showcase
     *
     * @return mixed[]|bool an array of statistics with the above keys on success. False on error.
     */
    public function getStatsAboutProjects() {
        try {
            $stats = array();
            // All keywords used in projects
            $sql = '
            SELECT k.ck_name, COUNT(k.ck_id) AS count
            FROM capstone_keyword k, capstone_keyword_for kf, showcase_project p
            WHERE k.ck_id = kf.ckf_ck_id AND kf.ckf_entity_id = p.sp_id
            GROUP BY k.ck_name
            ORDER BY `count` DESC;
            ';
            $stats['keywords'] = array();
            $results = $this->conn->query($sql);
            foreach($results as $row) {
                $stats['keywords'][$row['ck_name']] = $row['count'];
            }

            // Total number of projects
            $sql = '
            SELECT COUNT(*) AS total
            FROM showcase_project
            ';
            $stats['totalProjects'] = $this->conn->query($sql)[0]['total'];

            // Total number of users with at least one project
            $sql = '
            SELECT COUNT(DISTINCT swo_u_id) AS total
            FROM showcase_worked_on
            ';
            $stats['usersWithProjects'] = $this->conn->query($sql)[0]['total'];

            return $stats;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get stats for projects: ' . $e->getMessage());
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
            ->setDateCreated(new \DateTime(($row['sp_date_created'] == '' ? "now" : $row['sp_date_created']))) //Modified 3/31/2023
            ->setDateUpdated($row['sp_date_updated'] ? new \DateTime($row['sp_date_updated']) : null)
			->setCategory($row['sp_category'])
			->setScore($row['sp_score']);

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
            ->setExtension($row['spa_extension'])
            ->setPublished($row['spa_published'] ? true : false)
            ->setDateCreated(new \DateTime(($row['spa_date_created'] == '' ? "now" : $row['spa_date_created']))) //Modified 3/31/2023
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
            ->setDateCreated(new \DateTime(($row['spi_date_created'] == '' ? "now" : $row['spi_date_created']))) //Modified 3/31/2023
            ->setOrder($row['spi_order']);
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
            ->setDateCreated(new \DateTime(($row['sci_date_created'] == '' ? "now" : $row['sci_date_created']))); //Modified 3/31/2023
        if ($projectInRow) {
            $invitation->setProject(self::ExtractShowcaseProjectFromRow($row));
        }
        return $invitation;
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
