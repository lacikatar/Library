<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/rec_functions.php';

// Batch processing to avoid memory issues
$batch_size = 100;
$offset = 0;

do {
    $books = $pdo->query("
        SELECT b.ISBN, b.Title, b.Description, 
               GROUP_CONCAT(c.Name) AS Categories,
               GROUP_CONCAT(t.Tag_Name) AS Tags
        FROM book b
        LEFT JOIN belongs bc ON b.ISBN = bc.ISBN
        LEFT JOIN category c ON bc.Category_ID = c.Category_ID
        LEFT JOIN book_tags bt ON b.ISBN = bt.ISBN
        LEFT JOIN tags t ON bt.Tag_ID = t.Tag_ID
        GROUP BY b.ISBN
        LIMIT $offset, $batch_size
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($books as $book1) {
        // Get similar books using tags+categories+metadata
        $similarBooks = $pdo->prepare("
            SELECT b2.ISBN, 
                   (COUNT(common_tags.Tag_ID) * 0.4 + 
                    COUNT(common_cats.Category_ID) * 0.3 +
                    SIMILARITY(b1.Description, b2.Description) * 0.3) AS score
            FROM book b1
            CROSS JOIN book b2
            LEFT JOIN book_tags common_tags ON b1.ISBN = common_tags.ISBN 
                AND b2.ISBN = common_tags.ISBN
            LEFT JOIN belongs common_cats ON b1.ISBN = common_cats.ISBN 
                AND b2.ISBN = common_cats.ISBN
            WHERE b1.ISBN = ?
            GROUP BY b2.ISBN
            HAVING score > 0.2 AND b2.ISBN != ?
            ORDER BY score DESC
            LIMIT 20
        ");
        $similarBooks->execute([$book1['ISBN'], $book1['ISBN']]);
        
        // Store similarities
        $stmt = $pdo->prepare("
            INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE Similarity_Score = VALUES(Similarity_Score)
        ");
        
        foreach ($similarBooks as $similar) {
            $stmt->execute([$book1['ISBN'], $similar['ISBN'], $similar['score']]);
        }
    }
    
    $offset += $batch_size;
} while (count($books) > 0);
?>
