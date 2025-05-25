<?php
function getHybridRecommendations($userId, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               (0.6 * r.Score + 0.4 * bs.Similarity_Score) AS CombinedScore
        FROM book b
        LEFT JOIN recommendations r 
            ON b.ISBN = r.ISBN AND r.Member_ID = ?
        LEFT JOIN (
            SELECT ISBN_2 AS ISBN, AVG(Similarity_Score) AS Similarity_Score
            FROM book_similarity
            WHERE ISBN_1 IN (
                SELECT ISBN FROM activity_log 
                WHERE Member_ID = ? 
                ORDER BY Timestamp DESC 
                LIMIT 5
            )
            GROUP BY ISBN_2
        ) bs ON b.ISBN = bs.ISBN
        ORDER BY CombinedScore DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculateTfIdf($text) {
    // Implementation with stemming and stopword removal
    // Returns normalized TF-IDF vector
}
?>
