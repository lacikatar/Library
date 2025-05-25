<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/rec_functions.php';

// Process users in batches
$users = $pdo->query("SELECT Member_ID FROM member")->fetchAll(PDO::FETCH_COLUMN, 0);

foreach ($users as $userId) {
    // Hybrid scoring: 60% collaborative, 40% content-based
    $stmt = $pdo->prepare("
        INSERT INTO recommendations (Member_ID, ISBN, Score)
        SELECT :userId AS Member_ID, 
               bs.ISBN_2 AS ISBN,
               (0.6 * cf_score + 0.4 * bs.Similarity_Score) AS Score
        FROM (
            SELECT ISBN, SUM(Similarity_Score) * 0.6 AS cf_score
            FROM activity_log al
            JOIN book_similarity bs ON al.ISBN = bs.ISBN_1
            WHERE al.Member_ID = :userId
            GROUP BY bs.ISBN_2
        ) cf
        JOIN book_similarity bs ON cf.ISBN = bs.ISBN_1
        WHERE bs.ISBN_2 NOT IN (
            SELECT ISBN FROM activity_log WHERE Member_ID = :userId
        )
        GROUP BY bs.ISBN_2
        ORDER BY Score DESC
        LIMIT 50
    ");
    $stmt->execute(['userId' => $userId]);
    
    // Clear old recommendations
    $pdo->prepare("
        DELETE FROM recommendations 
        WHERE Member_ID = ? 
        AND Timestamp < NOW() - INTERVAL 7 DAY
    ")->execute([$userId]);
}
?>
