ALTER TABLE `activity_log` 
MODIFY COLUMN `Action_Type` enum('Viewed','Borrowed','Reviewed','Added to List','Returned') NOT NULL; 