<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/rec_functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log file
$logFile = __DIR__ . '/similarity_calculation.log';
file_put_contents($logFile, "Starting similarity calculation at " . date('Y-m-d H:i:s') . "\n");

try {
    // Test database connection
    $test = $pdo->query("SELECT 1")->fetch();
    file_put_contents($logFile, "Database connection successful\n", FILE_APPEND);

    // Test if SIMILARITY function exists
    $functionExists = $pdo->query("SHOW FUNCTION STATUS WHERE Name = 'SIMILARITY'")->fetch();
    if (!$functionExists) {
        throw new Exception("SIMILARITY function does not exist in the database");
    }
    file_put_contents($logFile, "SIMILARITY function exists\n", FILE_APPEND);

    // Test the SIMILARITY function
    $testSimilarity = $pdo->query("SELECT SIMILARITY('test', 'test') as result")->fetch();
    file_put_contents($logFile, "SIMILARITY function test result: " . $testSimilarity['result'] . "\n", FILE_APPEND);

    // Batch processing to avoid memory issues
    $batch_size = 100;
    $offset = 0;
    $total_books_processed = 0;
    $total_similarities_added = 0;

    do {
        $query = "
            SELECT b.ISBN, b.Title, b.Description, b.Series_ID,
                   GROUP_CONCAT(DISTINCT c.Name) AS Categories,
                   GROUP_CONCAT(DISTINCT t.Tag_Name) AS Tags,
                   GROUP_CONCAT(DISTINCT a.Name) AS Authors
            FROM book b
            LEFT JOIN belongs bc ON b.ISBN = bc.ISBN
            LEFT JOIN category c ON bc.Category_ID = c.Category_ID
            LEFT JOIN book_tags bt ON b.ISBN = bt.ISBN
            LEFT JOIN tags t ON bt.Tag_ID = t.Tag_ID
            LEFT JOIN wrote w ON b.ISBN = w.ISBN
            LEFT JOIN author a ON w.Author_ID = a.Author_ID
            LEFT JOIN (
                SELECT ISBN_1, COUNT(*) AS sim_count
                FROM book_similarity
                GROUP BY ISBN_1
            ) bs ON b.ISBN = bs.ISBN_1
            WHERE bs.sim_count IS NULL OR bs.sim_count < 10
            GROUP BY b.ISBN
            LIMIT $batch_size OFFSET $offset
        ";

        file_put_contents($logFile, "Executing query: $query\n", FILE_APPEND);

        $books = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents($logFile, "Retrieved " . count($books) . " books in this batch\n", FILE_APPEND);

        foreach ($books as $book1) {
            file_put_contents($logFile, "Processing book: " . $book1['Title'] . " (ISBN: " . $book1['ISBN'] . ")\n", FILE_APPEND);

            $similarQuery = "
                SELECT 
                    b2.ISBN,
                    (
                        (COUNT(DISTINCT t2.Tag_ID) / GREATEST(
                            (SELECT COUNT(*) FROM book_tags WHERE ISBN = ?),
                            (SELECT COUNT(*) FROM book_tags WHERE ISBN = b2.ISBN)
                        ) * 0.30) +

                        (COUNT(DISTINCT c2.Category_ID) / GREATEST(
                            (SELECT COUNT(*) FROM belongs WHERE ISBN = ?),
                            (SELECT COUNT(*) FROM belongs WHERE ISBN = b2.ISBN)
                        ) * 0.30) +

                        (COUNT(DISTINCT a2.Author_ID) / GREATEST(
                            (SELECT COUNT(*) FROM wrote WHERE ISBN = ?),
                            (SELECT COUNT(*) FROM wrote WHERE ISBN = b2.ISBN)
                        ) * 0.30) +

                        (CASE 
                            WHEN b1.Series_ID = b2.Series_ID AND b1.Series_ID IS NOT NULL THEN 1
                            ELSE 0
                        END * 0.10)
                    ) AS score
                FROM book b1
                CROSS JOIN book b2
                LEFT JOIN book_tags bt1 ON b1.ISBN = bt1.ISBN
                LEFT JOIN book_tags bt2 ON b2.ISBN = bt2.ISBN
                LEFT JOIN tags t1 ON bt1.Tag_ID = t1.Tag_ID
                LEFT JOIN tags t2 ON bt2.Tag_ID = t2.Tag_ID AND t1.Tag_ID = t2.Tag_ID
                LEFT JOIN belongs bc1 ON b1.ISBN = bc1.ISBN
                LEFT JOIN belongs bc2 ON b2.ISBN = bc2.ISBN
                LEFT JOIN category c1 ON bc1.Category_ID = c1.Category_ID
                LEFT JOIN category c2 ON bc2.Category_ID = c2.Category_ID AND c1.Category_ID = c2.Category_ID
                LEFT JOIN wrote w1 ON b1.ISBN = w1.ISBN
                LEFT JOIN wrote w2 ON b2.ISBN = w2.ISBN
                LEFT JOIN author a1 ON w1.Author_ID = a1.Author_ID
                LEFT JOIN author a2 ON w2.Author_ID = a2.Author_ID AND a1.Author_ID = a2.Author_ID
                WHERE b1.ISBN = ?
                GROUP BY b2.ISBN
                HAVING score > 0.2 AND b2.ISBN != ?
                ORDER BY score DESC
                LIMIT 10
            ";

            file_put_contents($logFile, "Executing similarity query for ISBN: " . $book1['ISBN'] . "\n", FILE_APPEND);

            $similarBooksStmt = $pdo->prepare($similarQuery);
            $similarBooksStmt->execute([
                $book1['ISBN'],
                $book1['ISBN'],
                $book1['ISBN'],
                $book1['ISBN'],
                $book1['ISBN']
            ]);
            $similarBooks = $similarBooksStmt->fetchAll(PDO::FETCH_ASSOC);

            file_put_contents($logFile, "Found " . count($similarBooks) . " similar books\n", FILE_APPEND);

            $stmt = $pdo->prepare("
                INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE Similarity_Score = VALUES(Similarity_Score)
            ");

            foreach ($similarBooks as $similar) {
                file_put_contents($logFile, "Adding similarity: " . $book1['ISBN'] . " -> " . $similar['ISBN'] . " (score: " . $similar['score'] . ")\n", FILE_APPEND);
                $stmt->execute([$book1['ISBN'], $similar['ISBN'], $similar['score']]);
                $total_similarities_added++;
            }

            $total_books_processed++;
        }

        $offset += $batch_size;

    } while (count($books) > 0);

    file_put_contents($logFile, "Finished processing. Total books processed: $total_books_processed, Total similarities added: $total_similarities_added\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents($logFile, "Error occurred: " . $e->getMessage() . "\n", FILE_APPEND);
    throw $e;
}
?>
