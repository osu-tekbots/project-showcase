<?php
namespace DataAccess;

use Model\Vote;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Handles database interactions for showcase project and artifact data.
 */
class VoteDao {

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
     * Fetches all the votes from the database.
     *
     * @return \Model\Vote[] an array of votes on success, false otherwise
     */
    public function getAllVotes() {
        try {
            $sql = "
            SELECT *
            FROM showcase_votes
            ORDER BY sp_id";
            $results = $this->conn->query($sql);

            $votes = array();
            foreach ($results as $row) {
                $votes[] = self::ExtractVoteFromRow($row);
            }

            return $votes;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all votes: ' . $e->getMessage());
            return false;
        }
    }
	
	
	/**
     * Fetches all the votes from the database for a given project id
     *
     * @param string $project the project id
     * @return \Model\Vote[] an array of votes on success, false otherwise
     */
    public function getAllVotesByProject($projectid) {
        try {
            $sql = "
            SELECT *
            FROM showcase_votes
            WHERE sp_id = :projectid";
            $params = array(':projectid' => $projectid);
			$results = $this->conn->query($sql, $params);

            $votes = array();
            foreach ($results as $row) {
                $votes[] = self::ExtractVoteFromRow($row);
            }

            return $votes;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all votes for project: ' . $e->getMessage());
            return false;
        }
    }
	
	/**
     * Checks if a vote by the userid for that projectid already exists
     *
     * @param string $projectId the project id
     * @param string $userId the user id
     * @return boolean true if vote exists, false otherwise
     */
    public function checkVote($projectId, $userId) {
        
		
		try {
            $sql = "
            SELECT *
            FROM showcase_votes
            WHERE sp_id = :projectid AND u_id = :uid";
            $params = array(
				':projectid' => $projectId,
				':uid' => $userId
				);
			
			$results = $this->conn->query($sql, $params);
			
			if (\count($results) > 0) 
                return true;
            else
				return false;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get all votes for project: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Inserts a new vote into the database.
     * 
     * This does not add artifacts to the project. It does take a user ID which it will use to associate
     * a user with a project. All projects must be associated with a user, so this argument is required.
     *
     * @param \Model\Vote $vote the vote to add
     * @return boolean true on success, false otherwise
     */
    public function addNewVote($vote) {
        try {
            $sql = '
            INSERT INTO showcase_votes (
                sv_id, sp_id, u_id, sv_score
            ) VALUES (
               :id, :projectid, :userid, :score
            )
            ';
            $params = array(
                ':id' => $vote->getId(),
                ':projectid' => $vote->getProjectId(),
                ':userid' => $vote->getUserId(),
                ':score' => $vote->getScore()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            $this->logger->error('Failed to lift: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the values of a vote entry in the database.
     *
     * @param \Model\Vote $vote the vote to add
     * @return boolean true on success, false otherwise
     */
    public function updateVote($vote) {
        try {
            $sql = '
            UPDATE showcase_votes SET 
                sp_id = :projectid, 
                u_id = :userid, 
                sv_score = :score
            WHERE sv_id = :id
            ';
            $params = array(
                ':id' => $vote->getId(),
                ':projectid' => $vote->getProjectId(),
                ':userid' => $vote->getUserId(),
                ':score' => $vote->getScore()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update lift: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a vote from the database.
     *
     * @param string $voteId the ID of the artifact to delete
     * @return boolean true on success, false otherwise
     */
    public function deleteVote($projectId, $userId) {
        try {

            $sql = '
            DELETE FROM 
			showcase_votes 
            WHERE 
			sp_id = :projectid AND u_id = :uid
            ';
            $params = array(
				':projectid' => $projectId,
				':uid' => $userId,
				);

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove lift: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Uses information from a row in the database to create a Vote object.
     *
     * @param mixed[] $row the row from the database
     * @return \Model\Vote the extracted project
     */
    public static function ExtractVoteFromRow($row) {
        $vote = new Vote($row['sv_id']);
        $vote
            ->setProjectId($row['sp_id'])
            ->setUserId($row['u_id'])
            ->setScore($row['sv_score'])
            ->setDateCreated(new \DateTime(($row['sv_date_created'] == '' ? "now" : $row['sv_date_created']))); //Modified 3/31/2023

        return $vote;
    }
}
