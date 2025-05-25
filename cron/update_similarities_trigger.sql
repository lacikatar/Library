DELIMITER //

-- Function to calculate similarity between two books
CREATE FUNCTION calculate_book_similarity(
    isbn1 VARCHAR(13),
    isbn2 VARCHAR(13)
) RETURNS FLOAT
DETERMINISTIC
BEGIN
    DECLARE series_sim FLOAT;
    DECLARE desc_sim FLOAT;
    DECLARE author_sim FLOAT;
    DECLARE cat_sim FLOAT;
    DECLARE tag_sim FLOAT;
    DECLARE total_sim FLOAT;
    
    -- Series similarity (0.1)
    SELECT 
        CASE 
            WHEN b1.Series_ID = b2.Series_ID AND b1.Series_ID IS NOT NULL THEN 0.1
            ELSE 0
        END INTO series_sim
    FROM Book b1, Book b2
    WHERE b1.ISBN = isbn1 AND b2.ISBN = isbn2;
    
    -- Author similarity (0.2)
    SELECT 
        CASE 
            WHEN COUNT(DISTINCT a1.Author_ID) > 0 THEN
                0.2 * COUNT(DISTINCT a1.Author_ID) / 
                GREATEST(
                    (SELECT COUNT(*) FROM wrote WHERE ISBN = isbn1),
                    (SELECT COUNT(*) FROM wrote WHERE ISBN = isbn2)
                )
            ELSE 0
        END INTO author_sim
    FROM wrote w1
    JOIN wrote w2 ON w1.Author_ID = w2.Author_ID
    JOIN author a1 ON w1.Author_ID = a1.Author_ID
    WHERE w1.ISBN = isbn1 AND w2.ISBN = isbn2;
    
    -- Category similarity (0.2)
    SELECT 
        CASE 
            WHEN COUNT(DISTINCT c1.Category_ID) > 0 THEN
                0.2 * COUNT(DISTINCT c1.Category_ID) / 
                GREATEST(
                    (SELECT COUNT(*) FROM belongs WHERE ISBN = isbn1),
                    (SELECT COUNT(*) FROM belongs WHERE ISBN = isbn2)
                )
            ELSE 0
        END INTO cat_sim
    FROM belongs b1
    JOIN belongs b2 ON b1.Category_ID = b2.Category_ID
    JOIN category c1 ON b1.Category_ID = c1.Category_ID
    WHERE b1.ISBN = isbn1 AND b2.ISBN = isbn2;
    
    -- Tag similarity (0.2)
    SELECT 
        CASE 
            WHEN COUNT(DISTINCT t1.Tag_ID) > 0 THEN
                0.2 * COUNT(DISTINCT t1.Tag_ID) / 
                GREATEST(
                    (SELECT COUNT(*) FROM book_tags WHERE ISBN = isbn1),
                    (SELECT COUNT(*) FROM book_tags WHERE ISBN = isbn2)
                )
            ELSE 0
        END INTO tag_sim
    FROM book_tags bt1
    JOIN book_tags bt2 ON bt1.Tag_ID = bt2.Tag_ID
    JOIN tags t1 ON bt1.Tag_ID = t1.Tag_ID
    WHERE bt1.ISBN = isbn1 AND bt2.ISBN = isbn2;
    
    -- Description similarity (0.3)
    -- Note: This is a simplified version. For better text similarity,
    -- you should use the Python script periodically
    SELECT 
        CASE 
            WHEN b1.Description IS NOT NULL AND b2.Description IS NOT NULL THEN
                0.3 * (
                    LENGTH(b1.Description) + LENGTH(b2.Description) - 
                    LENGTH(REPLACE(b1.Description, b2.Description, ''))
                ) / GREATEST(LENGTH(b1.Description), LENGTH(b2.Description))
            ELSE 0
        END INTO desc_sim
    FROM Book b1, Book b2
    WHERE b1.ISBN = isbn1 AND b2.ISBN = isbn2;
    
    -- Calculate total similarity
    SET total_sim = series_sim + desc_sim + author_sim + cat_sim + tag_sim;
    
    RETURN total_sim;
END //

-- Trigger for new book insertion
CREATE TRIGGER after_book_insert
AFTER INSERT ON Book
FOR EACH ROW
BEGIN
    -- Calculate similarities with all existing books
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        NEW.ISBN,
        b.ISBN,
        calculate_book_similarity(NEW.ISBN, b.ISBN)
    FROM Book b
    WHERE b.ISBN != NEW.ISBN
    AND calculate_book_similarity(NEW.ISBN, b.ISBN) > 0.2;
    
    -- Insert reverse similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        b.ISBN,
        NEW.ISBN,
        calculate_book_similarity(b.ISBN, NEW.ISBN)
    FROM Book b
    WHERE b.ISBN != NEW.ISBN
    AND calculate_book_similarity(b.ISBN, NEW.ISBN) > 0.2;
END //

-- Trigger for book update
CREATE TRIGGER after_book_update
AFTER UPDATE ON Book
FOR EACH ROW
BEGIN
    -- Delete old similarities
    DELETE FROM book_similarity 
    WHERE ISBN_1 = NEW.ISBN OR ISBN_2 = NEW.ISBN;
    
    -- Recalculate similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        NEW.ISBN,
        b.ISBN,
        calculate_book_similarity(NEW.ISBN, b.ISBN)
    FROM Book b
    WHERE b.ISBN != NEW.ISBN
    AND calculate_book_similarity(NEW.ISBN, b.ISBN) > 0.2;
    
    -- Insert reverse similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        b.ISBN,
        NEW.ISBN,
        calculate_book_similarity(b.ISBN, NEW.ISBN)
    FROM Book b
    WHERE b.ISBN != NEW.ISBN
    AND calculate_book_similarity(b.ISBN, NEW.ISBN) > 0.2;
END //

-- Trigger for tag insertion
CREATE TRIGGER after_tag_insert
AFTER INSERT ON book_tags
FOR EACH ROW
BEGIN
    -- Delete old similarities
    DELETE FROM book_similarity 
    WHERE ISBN_1 = NEW.ISBN OR ISBN_2 = NEW.ISBN;
    
    -- Recalculate similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        NEW.ISBN,
        b.ISBN,
        calculate_book_similarity(NEW.ISBN, b.ISBN)
    FROM Book b
    WHERE b.ISBN != NEW.ISBN
    AND calculate_book_similarity(NEW.ISBN, b.ISBN) > 0.2;
    
    -- Insert reverse similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        b.ISBN,
        NEW.ISBN,
        calculate_book_similarity(b.ISBN, NEW.ISBN)
    FROM Book b
    WHERE b.ISBN != NEW.ISBN
    AND calculate_book_similarity(b.ISBN, NEW.ISBN) > 0.2;
END //

-- Trigger for tag update
CREATE TRIGGER after_tag_update
AFTER UPDATE ON book_tags
FOR EACH ROW
BEGIN
    -- Delete old similarities
    DELETE FROM book_similarity 
    WHERE ISBN_1 = NEW.ISBN OR ISBN_2 = NEW.ISBN;
    
    -- Recalculate similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        NEW.ISBN,
        b.ISBN,
        calculate_book_similarity(NEW.ISBN, b.ISBN)
    FROM Book b
    WHERE b.ISBN != NEW.ISBN
    AND calculate_book_similarity(NEW.ISBN, b.ISBN) > 0.2;
    
    -- Insert reverse similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        b.ISBN,
        NEW.ISBN,
        calculate_book_similarity(b.ISBN, NEW.ISBN)
    FROM Book b
    WHERE b.ISBN != NEW.ISBN
    AND calculate_book_similarity(b.ISBN, NEW.ISBN) > 0.2;
END //

-- Trigger for tag deletion
CREATE TRIGGER after_tag_delete
AFTER DELETE ON book_tags
FOR EACH ROW
BEGIN
    -- Delete old similarities
    DELETE FROM book_similarity 
    WHERE ISBN_1 = OLD.ISBN OR ISBN_2 = OLD.ISBN;
    
    -- Recalculate similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        OLD.ISBN,
        b.ISBN,
        calculate_book_similarity(OLD.ISBN, b.ISBN)
    FROM Book b
    WHERE b.ISBN != OLD.ISBN
    AND calculate_book_similarity(OLD.ISBN, b.ISBN) > 0.2;
    
    -- Insert reverse similarities
    INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
    SELECT 
        b.ISBN,
        OLD.ISBN,
        calculate_book_similarity(b.ISBN, OLD.ISBN)
    FROM Book b
    WHERE b.ISBN != OLD.ISBN
    AND calculate_book_similarity(b.ISBN, OLD.ISBN) > 0.2;
END //

DELIMITER ; 