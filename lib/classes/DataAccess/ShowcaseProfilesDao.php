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
                sup_u_id, sup_about, sup_show_contact_info, sup_website_link, sup_github_link, sup_linkedin_link,
                sup_resume_file_name, sup_image_uploaded, sup_date_created, sup_date_updated
            ) VALUES (
                :id,
                :about,
                :contact,
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
                ':website' => $profile->getWebsiteLink(),
                ':github' => $profile->getGithubLink(),
                ':linkedin' => $profile->getLinkedInLink(),
                ':resume' => $profile->getResumeFileName(),
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
                sup_website_link = :website,
                sup_github_link = :github,
                sup_linkedin_link = :linkedin,
                sup_resume_file_name = :resume,
                sup_image_uploaded = :image,
                sup_date_updated = :dupdated
            WHERE sup_u_id = :id
            ';
            $params = array(
                ':about' => $profile->getAbout(),
                ':contact' => $profile->canShowContactInfo(),
                ':website' => $profile->getWebsiteLink(),
                ':github' => $profile->getGithubLink(),
                ':linkedin' => $profile->getLinkedInLink(),
                ':resume' => $profile->getResumeFileName(),
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
        if ($includeUser) {
            $profile->setUser(UsersDao::ExtractUserFromRow($row));
        }
        $profile
            ->setAbout($row['sup_about'])
            ->setShowContactInfo($row['sup_show_contact_info'] ? true : false)
            ->setWebsiteLink($row['sup_website_link'])
            ->setGithubLink($row['sup_github_link'])
            ->setLinkedInLink($row['sup_linkedin_link'])
            ->setResumeFileName($row['sup_resume_file_name'])
            ->setImageUploaded($row['sup_image_uploaded'] ? true : false)
            ->setDateUpdated(new \DateTime($row['sup_date_updated']));
        
        return $profile;
    }
}
