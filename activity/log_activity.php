<?php
require_once __DIR__ . '/../includes/db_config.php';

function logActivity($memberId, $isbn, $actionType) {
    // Log base activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (Member_ID, ISBN, Action_Type)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$memberId, $isbn, $actionType]);
    
    // Immediate recommendation boost
    $pdo->prepare("
        UPDATE recommendations
        SET Score = Score * 
            CASE ?
                WHEN 'Borrowed' THEN 1.5
                WHEN 'Reviewed' THEN 1.3
                ELSE 1.1
            END
        WHERE Member_ID = ? AND ISBN = ?
    ")->execute([$actionType, $memberId, $isbn]);
    
    // Update similarity matrix weights
    if ($actionType === 'Reviewed') {
        $pdo->prepare("
            UPDATE book_similarity
            SET Similarity_Score = Similarity_Score * 1.05
            WHERE ISBN_1 = ? OR ISBN_2 = ?
        ")->execute([$isbn, $isbn]);
    }
}
?>
