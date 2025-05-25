DELIMITER //

-- Trigger for new books
CREATE TRIGGER after_book_insert
AFTER INSERT ON book
FOR EACH ROW
BEGIN
    -- Delete existing similarities for this book
    DELETE FROM book_similarity 
    WHERE ISBN_1 = NEW.ISBN OR ISBN_2 = NEW.ISBN;
    
    -- Calculate new similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT NEW.ISBN, b2.ISBN, 
           (
               -- Tag similarity (25%)
               (COUNT(DISTINCT common_tags.Tag_ID) * 0.25) +
               
               -- Category similarity (20%)
               (COUNT(DISTINCT common_cats.Category_ID) * 0.20) +
               
               -- Description similarity (15%)
               (SIMILARITY(NEW.Description, b2.Description) * 0.15) +
               
               -- Author similarity (25%)
               (COUNT(DISTINCT common_authors.Author_ID) * 0.25) +
               
               -- Series similarity (15%)
               (CASE 
                   WHEN NEW.Series_ID = b2.Series_ID AND NEW.Series_ID IS NOT NULL THEN 1
                   ELSE 0
               END * 0.15)
           ) AS score
    FROM book b2
    LEFT JOIN book_tags common_tags ON NEW.ISBN = common_tags.ISBN 
        AND b2.ISBN = common_tags.ISBN
    LEFT JOIN belongs common_cats ON NEW.ISBN = common_cats.ISBN 
        AND b2.ISBN = common_cats.ISBN
    LEFT JOIN wrote common_authors ON NEW.ISBN = common_authors.ISBN 
        AND b2.ISBN = common_authors.ISBN
    WHERE b2.ISBN != NEW.ISBN
    GROUP BY b2.ISBN
    HAVING score > 0.2
    ORDER BY score DESC
    LIMIT 10;
END //

-- Trigger for book updates
CREATE TRIGGER after_book_update
AFTER UPDATE ON book
FOR EACH ROW
BEGIN
    -- Delete existing similarities for this book
    DELETE FROM book_similarity 
    WHERE ISBN_1 = NEW.ISBN OR ISBN_2 = NEW.ISBN;
    
    -- Calculate new similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT NEW.ISBN, b2.ISBN, 
           (
               -- Tag similarity (25%)
               (COUNT(DISTINCT common_tags.Tag_ID) * 0.25) +
               
               -- Category similarity (20%)
               (COUNT(DISTINCT common_cats.Category_ID) * 0.20) +
               
               -- Description similarity (15%)
               (SIMILARITY(NEW.Description, b2.Description) * 0.15) +
               
               -- Author similarity (25%)
               (COUNT(DISTINCT common_authors.Author_ID) * 0.25) +
               
               -- Series similarity (15%)
               (CASE 
                   WHEN NEW.Series_ID = b2.Series_ID AND NEW.Series_ID IS NOT NULL THEN 1
                   ELSE 0
               END * 0.15)
           ) AS score
    FROM book b2
    LEFT JOIN book_tags common_tags ON NEW.ISBN = common_tags.ISBN 
        AND b2.ISBN = common_tags.ISBN
    LEFT JOIN belongs common_cats ON NEW.ISBN = common_cats.ISBN 
        AND b2.ISBN = common_cats.ISBN
    LEFT JOIN wrote common_authors ON NEW.ISBN = common_authors.ISBN 
        AND b2.ISBN = common_authors.ISBN
    WHERE b2.ISBN != NEW.ISBN
    GROUP BY b2.ISBN
    HAVING score > 0.2
    ORDER BY score DESC
    LIMIT 10;
END //

DELIMITER ; 