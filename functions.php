<?php 

function logUserActivity($memberId, $isbn, $actionType, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (Member_ID, ISBN, Action_Type) 
        VALUES (:member_id, :isbn, :action_type)
    ");
    $stmt->execute([
        ':member_id' => $memberId,
        ':isbn' => $isbn,
        ':action_type' => $actionType
    ]);
}
?>