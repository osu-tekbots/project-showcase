<?php
namespace DataAccess;

use Model\Keyword;

class KeywordsDao{
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;

    /**
     * Creates a new instance of the data access object for capstone project data.
     *
     * @param DatabaseConnection $connection the connection to use to communiate with the database
     * @param \Util\Logger $logger the logger to use to log details about the interactions with the database
     */
    public function __construct($connection, $logger) {
        $this->conn = $connection;
        $this->logger = $logger;
    }
	
    public function getApprovedKeywords() {
        try {
            $sql = '
            SELECT * 
            FROM showcase_keyword
			WHERE sk_approved = 1
            ORDER BY sk_name ASC
            ';

            $results = $this->conn->query($sql);

            $keywords = array();
            foreach ($results as $row) {
                $k = self::ExtractKeywordFromRow($row, true);
                $keywords[] = $k;
            }

            return $keywords;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all keywords: ' . $e->getMessage());
            return false;
        }
    }

    public function getUnapprovedKeywords() {
        try {
            $sql = '
            SELECT * 
            FROM showcase_keyword
			WHERE sk_approved = 0
            ORDER BY sk_name ASC
            ';

            $results = $this->conn->query($sql);

            $keywords = array();
            foreach ($results as $row) {
                $k = self::ExtractKeywordFromRow($row, true);
                $keywords[] = $k;
            }

            return $keywords;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all keywords: ' . $e->getMessage());
            return false;
        }
    }

    public function getKeywordUsedCount($keywordId) {
        try {
            $sql = '
            SELECT COUNT(*) FROM `showcase_keyword_for`
            WHERE skf_sk_id = :keywordId;
            ';
            $params = array(
                ':keywordId' => $keywordId
            );
            $results = $this->conn->query($sql, $params);
            return implode("&",array_map(function($a) {return implode("~",$a);},$results)); // Not sure why this works
        } catch (\Exception $e) {
            $this->logger->error('Failed to get keyword counts: ' . $e->getMessage());
            return false;
        }
        
    }
	
	
    public function getKeywordsForEntity($entityId) {
        try {
            $sql = '
            SELECT * 
            FROM showcase_keyword, showcase_keyword_for
			WHERE showcase_keyword_for.skf_sk_id = showcase_keyword.sk_id
			AND showcase_keyword_for.skf_entity_id = :entityId
            ';
            $params = array(':entityId' => $entityId);
            $results = $this->conn->query($sql, $params);

            $keywords = array();
            foreach ($results as $row) {
                $k = self::ExtractKeywordFromRow($row, true);
                $keywords[] = $k;
            }
           
            return $keywords;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get keywords for object " . $e->getMessage());
            return false;
        }
    }
	
	public function getKeyword($name) {
        try {
            $sql = '
            SELECT * 
            FROM showcase_keyword
			WHERE showcase_keyword.sk_name = :name
            ';
            $params = array(':name' => $name);
            $results = $this->conn->query($sql, $params);
			
			if (\count($results) == 0) {
                return false;
            }

			return self::ExtractKeywordFromRow($results[0], true);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get keyowrds for object " . $e->getMessage());
            return false;
        }
    }
	
	public function keywordExists($keyword) {
        try {
            $sql = '
            SELECT * 
            FROM showcase_keyword
			WHERE showcase_keyword.sk_name = :keyword
            ';
            $params = array(':keyword' => $keyword);
            $results = $this->conn->query($sql, $params);

			if (\count($results) == 0) {
					return false;
			}
			
			return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to check keywords for object " . $e->getMessage());
            return false;
        }
    }
	
	
	public function keywordExistsForEntity($keyword, $entityId) {
        try {
            $sql = '
            SELECT * 
            FROM showcase_keyword, showcase_keyword_for 
			WHERE showcase_keyword_for.skf_sk_id = showcase_keyword.sk_id
			AND showcase_keyword.sk_name = :keyword
			AND showcase_keyword_for.skf_entity_id = :entityId
            ';
            $params = array(':keyword' => $keyword, ':entityId' => $entityId);
            $results = $this->conn->query($sql, $params);

			if (\count($results) == 0) {
					return false;
			}
			
			return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to check keywords entity for object " . $e->getMessage());
            return false;
        }
    }
	
	
	public function addKeyword($keyword, $approved) {
        try {
            $sql = '
            INSERT INTO showcase_keyword VALUES (
                NULL,
                :keyword,
                NULL,
                :approved
            )';
            $params = array(
                ':keyword' => $keyword,
                ':approved' => $approved
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add new keyword: ' . $e->getMessage());
            return false;
        }
    }
	
	
	public function addKeywordInJoinTable($keyword, $entityId) {
        try {
            $sql = '
            INSERT INTO showcase_keyword_for VALUES (
                :keywordId,
				:entityId
            )';
            $params = array(
                ':keywordId' => $keyword->getId(),
                ':entityId' => $entityId
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add new keyword in join table: ' . $e->getMessage());
            return false;
        }
    }

	public function removeAllKeywordsForEntity($entityId){
		 try {
            $sql = '
            DELETE FROM showcase_keyword_for
			WHERE showcase_keyword_for.skf_entity_id = :entityId
            ';
            $params = array(
                ':entityId' => $entityId
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete all keywords in join table: ' . $e->getMessage());
            return false;
        }
	}

    public function removeKeywordEverywhere($keywordId){
		try {
            $sql = '
            DELETE FROM showcase_keyword_for
            WHERE showcase_keyword_for.skf_sk_id = :keywordId
            ';
            $params = array(
                ':keywordId' => $keywordId
            );
            $this->conn->execute($sql, $params);

            $sql = '
            DELETE FROM showcase_keyword 
            WHERE sk_id = :keywordId
            ';
            $params = array(
                ':keywordId' => $keywordId
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete keyword everywhere: ' . $e->getMessage());
            return false;
        }
	}
	
    public function mergeKeywords($keywordIds) {
        try {
            // Get the first match that's approved or use first value if the query didn't find any approved keywords
            $sql = '
                SELECT sk_id, min(sk_name) FROM showcase_keyword 
                WHERE sk_id IN (:keywordIds)
                AND NOT sk_approved = 0';
            $params = array(
                ':keywordIds' => implode(', ', $keywordIds)
            );
            $results = $this->conn->query($sql, $params);
            $finalId = $results[0]['sk_id'] ?? intval($keywordIds[0]);
            //unset($keywordIds[array_search($finalId, $keywordIds)]);
            
            // Escape the input with intval to prevent arbitrary SQL injection
            $ids = implode(',', array_map('intval', $keywordIds));

            // Get any projects that use a soon-to-be-removed keyword but not the final keyword
            // NOTE: FIND_IN_SET used bc $this->conn automatically adds quotes around string vars, preventing WHERE _ IN
            //     from executing properly
            $sql = '
                WITH uniqueEntities AS (
                    SELECT * FROM showcase_keyword_for
                    WHERE FIND_IN_SET(skf_sk_id, :keywordIds) <> 0
                    GROUP BY skf_entity_id
                    )
                
                SELECT * FROM uniqueEntities
                WHERE skf_sk_id <> :finalId;';
            $params = array(
                ':keywordIds' => $ids,
                ':finalId' => $finalId
            );
            $results = $this->conn->query($sql, $params);
            $entities = array_map(function ($val) {return $val['skf_entity_id'];}, $results);
            $this->logger->info('Keyword ID list: '.$ids.' -> '.$finalId);
            // $this->logger->info('Keyword entities list: '.var_export($entities, true));

            // Add a database row with the final keyword & any projects that don't already have it
            $insertArray = '';
            foreach($entities as $entity) {
                $insertArray .= '(' .
                    $finalId . ', "' . $entity .
                    '"), ';
            }
            $insertArray = rtrim($insertArray, ", ");

            if(strlen($insertArray)) {
                $sql = 'INSERT INTO showcase_keyword_for(skf_sk_id, skf_entity_id) VALUES '.$insertArray;
                $this->conn->execute($sql);
            }
            
            // Remove all database rows that use a merged keyword that's not the final one
            // NOTE: FIND_IN_SET used bc $this->conn automatically adds quotes around string vars, preventing WHERE _ IN
            //     from executing properly
            $sql = '
                DELETE FROM showcase_keyword_for
                WHERE FIND_IN_SET(skf_sk_id, :keywordIds) <> 0
                    AND skf_sk_id <> :finalId';
            $params = array(
                ':keywordIds' => $ids,
                ':finalId' => $finalId
            );
            $results = $this->conn->query($sql, $params);

            // Remove now-unused keywords from keyword table
            $sql = '
                DELETE FROM `showcase_keyword` 
                WHERE FIND_IN_SET(sk_id, :keywordIds) <> 0
                    AND NOT sk_id = :finalId';
            $params = array(
                ':keywordIds' => $ids,
                ':finalId' => $finalId
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to merge keywords: ' . $e->getMessage());
            return false;
        }
    }

    public function updateKeyword($keywordId, $keywordText) {
        try {
            $sql = '
            UPDATE `showcase_keyword` 
            SET `sk_name` = :keywordText
            WHERE `showcase_keyword`.`sk_id` = :keywordId;
            ';
            $params = array(
                ':keywordText' => $keywordText,
                ':keywordId' => $keywordId
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update keyword: ' . $e->getMessage());
            return false;
        }
    }

    public function updateApproval($keywordId, $approved) {
        try {
            $sql = '
            UPDATE `showcase_keyword` 
            SET `sk_approved` = :approved
            WHERE `showcase_keyword`.`sk_id` = :keywordId;
            ';
            $params = array(
                ':approved' => $approved,
                ':keywordId' => $keywordId
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update keyword approval: ' . $e->getMessage());
            return false;
        }
    }
	
	public static function ExtractKeywordFromRow($row){
		$keyword = new Keyword($row['sk_id']);
		$keyword->setName($row['sk_name'])
			->setParentId($row['sk_parent_sk_id'])
			->setApproved($row['sk_approved']);
		return $keyword;
	}
	
}

?>