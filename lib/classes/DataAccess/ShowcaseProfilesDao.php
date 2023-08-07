<?php
namespace DataAccess;

use Model\ShowcaseProfile;

/**
 * Handles database interactions relating to showcase profile metadata for users.
 */
class ShowcaseProfilesDao {

    /** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;

    /**
     * Constructs a new data access object for showcase profile data.
     *
     * @param DatabaseConnection $connection the connection to use for the database queries
     * @param \Util\Logger $logger logger instance to use for logging errors and other information
     */
    public function __construct($connection, $logger) {
        $this->conn = $connection;
        $this->logger = $logger;
    }

    /**
     * Fetches all of the showcase profiles for users in the database.
     *
     * @param integer $count
     * @param integer $offset
     * @return \Model\ShowcaseProfile[] an array of showcase profiles on success, false otherwise
     */
    public function getAllProfiles($count = 0, $offset = 0) {
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
            $sql = "
            SELECT *
            FROM user, showcase_user_profile
            WHERE u_id = sup_u_id
            ORDER BY u_lname ASC
            $limit
            ";
            $results = $this->conn->query($sql);

            $profiles = array();
            foreach ($results as $row) {
                $profiles[] = self::ExtractShowcaseProfileFromRow($row);
            }

            return $profiles;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all user profiles: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches additional profile information about a user needed for the project showcase site.
     * 
     * The profile will also include a reference to the User object associated with the profile.
     *
     * @param string $userId
     * @return \Model\ShowcaseProfile|boolean the profile on success, false if not found or an error occurs
     */
    public function getUserProfileInformation($userId) {
        try {
            $sql = '
            SELECT *
            FROM user, showcase_user_profile
            WHERE u_id = sup_u_id AND u_id = :id
            ';
            $params = array(':id' => $userId);
            $results = $this->conn->query($sql, $params);
            if (\count($results) == 0) {
                return false;
            }

            return self::ExtractShowcaseProfileFromRow($results[0]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user profile for user with id '$userId': " . $e->getMessage());
            return false;
        }
    }

    public function getUserIdFromOnid($onid) {
        try {
            $sql = '
            SELECT u_id
            FROM user
            WHERE u_onid = :onid
            ';
            $params = array(':onid' => $onid);
            $results = $this->conn->query($sql, $params);
            if (\count($results) == 0) {
                return false;
            }

            return $results[0]['u_id'];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get user profile for user with ONID '$onid': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Adds new information about the showcase profile for the user in the database.
     *
     * @param \Model\ShowcaseProfile $profile the profile to add
     * @return boolean true on success, false otherwise
     */
    public function addNewShowcaseProfile($profile) {
        try {
            $sql = '
            INSERT INTO showcase_user_profile (
                sup_u_id, sup_about, sup_show_contact_info, sup_accepting_invites, sup_website_link, sup_github_link,
                sup_linkedin_link, sup_resume_uploaded, sup_image_uploaded, sup_date_created, sup_date_updated
            ) VALUES (
                :id,
                :about,
                :contact,
                :invites,
                :website,
                :github,
                :linkedin,
                :resume,
                :image,
                :dcreated,
                :dupdated
            )
            ';
            $params = array(
                ':about' => $profile->getAbout(),
                ':contact' => $profile->canShowContactInfo(),
                ':invites' => $profile->isAcceptingInvites(),
                ':website' => $profile->getWebsiteLink(),
                ':github' => $profile->getGithubLink(),
                ':linkedin' => $profile->getLinkedInLink(),
                ':resume' => $profile->isResumeUploaded(),
                ':image' => $profile->isImageUploaded(),
                ':id' => $profile->getUserId(),
                ':dcreated' => QueryUtils::FormatDate($profile->getDateCreated()),
                ':dupdated' => QueryUtils::FormatDate($profile->getDateUpdated())
            );

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add new profile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the information about the showcase profile for the user in the database.
     *
     * @param \Model\ShowcaseProfile $profile the profile to update
     * @return boolean true on success, false otherwise
     */
    public function updateShowcaseProfile($profile) {
        try {
            $sql = '
            UPDATE showcase_user_profile SET
                sup_about = :about,
                sup_show_contact_info = :contact,
                sup_accepting_invites = :invites,
                sup_website_link = :website,
                sup_github_link = :github,
                sup_linkedin_link = :linkedin,
                sup_resume_uploaded = :resume,
                sup_image_uploaded = :image,
                sup_date_updated = :dupdated
            WHERE sup_u_id = :id
            ';
            $params = array(
                ':about' => $profile->getAbout(),
                ':contact' => $profile->canShowContactInfo(),
                ':invites' => $profile->isAcceptingInvites(),
                ':website' => $profile->getWebsiteLink(),
                ':github' => $profile->getGithubLink(),
                ':linkedin' => $profile->getLinkedInLink(),
                ':resume' => $profile->isResumeUploaded(),
                ':image' => $profile->isImageUploaded(),
                ':dupdated' => QueryUtils::FormatDate($profile->getDateUpdated()),
                ':id' => $profile->getUserId()
            );

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update profile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches statistics about user profiles in the showcase.
     * 
     * The resulting associative array will have the following keys:
     * - `totalUsers`: the total number of users of the showcase
     *
     * @return mixed[]|bool an array containing the stats fetched from the database on success. False on error.
     */
    public function getStatsAboutProfiles() {
        
        try {
            $stats = array();
            // Total number of users
            $sql = "
            SELECT COUNT(sup_u_id) AS total
            FROM showcase_user_profile
            ";
            $stats['totalUsers'] = $this->conn->query($sql)[0]['total'];

            return $stats;
        } catch(\Exception $e) {
            $this->logger->error("Failed to get stats for profiles: " . $e->getMessage());
            return false;
        }
    }

	 /**
     * Fetches statistics about user profiles in the showcase.
     * 
     * The resulting associative array will have the following keys:
     * - `totalUsers`: the total number of users of the showcase
     *
     * @return mixed[]|bool an array containing the stats fetched from the database on success. False on error.
     */
    public function getTopProfiles() {
        
        try {
            $profileIds = array();
            // Total number of users
            $sql = "
            SELECT swo_u_id AS user_id, COUNT(DISTINCT(swo_sp_id)) AS project_count 
			FROM showcase_worked_on 
			GROUP BY swo_u_id 
			ORDER BY project_count DESC
            ";
            
			$results = $this->conn->query($sql);
			
            return $results;
        } catch(\Exception $e) {
            $this->logger->error("Failed to get Top profiles: " . $e->getMessage());
            return false;
        }
    }



    /**
     * Uses information from a row in the database to create a ShowcaseProfile object.
     * 
     * If the `$includeUser` flag is true, then the user reference will also be set in the profile. This
     * implies that the necessary information from the `user` table is in the row being processed.
     *
     * @param mixed[] $row the row from the database
     * @param boolean $includeUser indicates whether `user` table information is in the row
     * @return \Model\ShowcaseProfile the extracted profile
     */
    public static function ExtractShowcaseProfileFromRow($row, $includeUser = true) {
        $profile = new ShowcaseProfile($row['sup_u_id']);
//        echo "Grabbing Profile";
		if ($includeUser) {
            $profile->setUser(UsersDao::ExtractUserFromRow($row));
        }
        $profile
            ->setAbout($row['sup_about'])
            ->setShowContactInfo($row['sup_show_contact_info'] ? true : false)
            ->setAcceptingInvites($row['sup_accepting_invites'])
            ->setWebsiteLink($row['sup_website_link'])
            ->setGithubLink($row['sup_github_link'])
            ->setLinkedInLink($row['sup_linkedin_link'])
            ->setResumeUploaded($row['sup_resume_uploaded'] ? true : false)
            ->setImageUploaded($row['sup_image_uploaded'] ? true : false)
            ->setDateUpdated(new \DateTime(($row['sup_date_updated'] == '' ? "now" : $row['sup_date_updated']))); //Modified 3/31/2023
        
        return $profile;
    }
}
