DELIMITER //

CREATE FUNCTION IF NOT EXISTS SIMILARITY(str1 TEXT, str2 TEXT) 
RETURNS FLOAT
DETERMINISTIC
BEGIN
    DECLARE similarity FLOAT;
    DECLARE words1 INT;
    DECLARE words2 INT;
    DECLARE common_words INT;
    
    -- If either string is NULL, return 0
    IF str1 IS NULL OR str2 IS NULL THEN
        RETURN 0;
    END IF;
    
    -- If either string is empty, return 0
    IF LENGTH(str1) = 0 OR LENGTH(str2) = 0 THEN
        RETURN 0;
    END IF;
    
    -- Count total words in each string
    SET words1 = (LENGTH(str1) - LENGTH(REPLACE(str1, ' ', '')) + 1);
    SET words2 = (LENGTH(str2) - LENGTH(REPLACE(str2, ' ', '')) + 1);
    
    -- Count common words (case insensitive)
    SET common_words = (
        SELECT COUNT(*) 
        FROM (
            SELECT DISTINCT LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(str1, ' ', n.n), ' ', -1))) AS word
            FROM (
                SELECT a.N + b.N * 10 + 1 AS n
                FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
                CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
                ORDER BY n
            ) n
            WHERE n.n <= words1
        ) words1
        INNER JOIN (
            SELECT DISTINCT LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(str2, ' ', n.n), ' ', -1))) AS word
            FROM (
                SELECT a.N + b.N * 10 + 1 AS n
                FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
                CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
                ORDER BY n
            ) n
            WHERE n.n <= words2
        ) words2 ON words1.word = words2.word
    );
    
    -- Calculate similarity as ratio of common words to total unique words
    SET similarity = common_words / GREATEST(words1, words2);
    
    RETURN similarity;
END //

DELIMITER ; 