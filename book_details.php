<?php
$host = "localhost";
$username = "root";
$password = "lacika";
$database = "Librarydb";
session_start();

require_once 'functions.php';


try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$isbn = $_GET['isbn'] ?? '';

if ($isbn) {
    $sql = "SELECT DISTINCT 
                b.ISBN, 
                b.Title, 
                b.Description, 
                p.Name AS Publisher, 
                b.Release_Year, 
                b.Page_Nr, 
                bs.Name AS series_name,
                GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') AS authors,
                b.Image_URL, 
                GROUP_CONCAT(DISTINCT c.Name SEPARATOR ', ') AS categories 
            FROM Book b
            INNER JOIN wrote w ON b.ISBN = w.ISBN
            INNER JOIN author a ON a.Author_ID = w.Author_ID
            LEFT JOIN belongs bl ON b.ISBN = bl.ISBN
            LEFT JOIN category c ON bl.Category_ID = c.Category_ID
            LEFT JOIN book_series bs ON b.Series_ID = bs.Series_ID
            LEFT JOIN publisher p ON b.Publisher_ID = p.Publisher_ID
            WHERE b.ISBN = :isbn 
            GROUP BY b.ISBN";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['isbn' => $isbn]);
    $bookDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $copiesStmt = $conn->prepare("
        SELECT c.Copy_ID, c.Copy_Condition, c.Shelf_Position,
               CASE 
                   WHEN EXISTS (
                       SELECT 1 FROM borrowing b 
                       WHERE b.Copy_ID = c.Copy_ID 
                       AND b.Status IN ('Checked Out', 'Overdue')
                   ) THEN 1
                   ELSE 0
               END as is_borrowed
        FROM copy c
        WHERE c.ISBN = ?
        ORDER BY c.Copy_Condition = 'New' DESC, c.Copy_Condition = 'Good' DESC
    ");
    $copiesStmt->execute([$isbn]);
    $allCopies = $copiesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separate available and borrowed copies
    $availableCopies = array_filter($allCopies, function($copy) {
        return $copy['is_borrowed'] == 0;
    });
    $borrowedCopies = array_filter($allCopies, function($copy) {
        return $copy['is_borrowed'] == 1;
    });

    if (isset($_SESSION['username'])) {
        // Get waitlist position
        $waitlistPositionStmt = $conn->prepare("
            SELECT COUNT(*) as position 
            FROM waitlist 
            WHERE ISBN = ? AND Join_Date < (
                SELECT Join_Date 
                FROM waitlist 
                WHERE Member_ID = ? AND ISBN = ?
            )
        ");
        $waitlistPositionStmt->execute([$isbn, $_SESSION['user_id'], $isbn]);
        $waitlistPosition = $waitlistPositionStmt->fetch(PDO::FETCH_ASSOC)['position'] ?? 0;

        // Get total waitlist count
        $waitlistCountStmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM waitlist 
            WHERE ISBN = ?
        ");
        $waitlistCountStmt->execute([$isbn]);
        $totalWaitlist = $waitlistCountStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Check if user is on waitlist
        $waitlistStmt = $conn->prepare("
            SELECT * FROM waitlist 
            WHERE Member_ID = ? AND ISBN = ?
        ");
        $waitlistStmt->execute([$_SESSION['user_id'], $isbn]);
        $onWaitlist = $waitlistStmt->rowCount() > 0;
    }

    if(isset($_SESSION['user_id'])){

    logUserActivity($_SESSION['user_id'], $isbn, 'Viewed', $conn);
    // Handle borrow action
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['borrow']) && isset($_POST['copy_id'])) {
            $copyId = $_POST['copy_id'];
            $memberId = $_SESSION['user_id'];

            try {
                $conn->beginTransaction();

                // Check if copy is still available
                $checkCopyStmt = $conn->prepare("
                    SELECT COUNT(*) 
                    FROM borrowing 
                    WHERE Copy_ID = ? AND Status IN ('Checked Out', 'Overdue')
                ");
                $checkCopyStmt->execute([$copyId]);
                if ($checkCopyStmt->fetchColumn() > 0) {
                    throw new Exception("This copy is no longer available.");
                }

                // Insert into borrowing table
                $borrowStmt = $conn->prepare("
                    INSERT INTO borrowing (Borrowing_Date, Work_Id, Copy_ID, Member_ID, Status)
                    VALUES (CURDATE(), 1, :copy_id, :member_id, 'Checked Out')
                ");
                $borrowStmt->execute([
                    ':copy_id' => $copyId,
                    ':member_id' => $memberId
                ]);

                $conn->commit();
                logUserActivity($_SESSION['user_id'], $isbn, 'Borrowed', $conn);
                echo "<script>alert('You borrowed the book successfully!');window.location.href='borrowed.php';</script>";
                exit;
            } catch (Exception $e) {
                $conn->rollBack();
                echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');window.location.href='book_details.php?isbn=$isbn';</script>";
                exit;
            }
        }
    }

    // Handle join waitlist
    if (isset($_POST['join_waitlist'])) {
        $waitlistInsert = $conn->prepare("
            INSERT IGNORE INTO waitlist (Member_ID, ISBN)
            VALUES (:member_id, :isbn)
        ");
        $waitlistInsert->execute([
            ':member_id' => $_SESSION['user_id'],
            ':isbn' => $isbn
        ]);

        echo "<script>alert('You have been added to the waitlist.');window.location.href='book_details.php?isbn=$isbn';</script>";
        logUserActivity($_SESSION['user_id'], $isbn, 'Added to List', $conn);
        exit;
    }

    // Handle leave waitlist
    if (isset($_POST['leave_waitlist'])) {
        $waitlistDelete = $conn->prepare("
            DELETE FROM waitlist 
            WHERE Member_ID = :member_id AND ISBN = :isbn
        ");
        $waitlistDelete->execute([
            ':member_id' => $_SESSION['user_id'],
            ':isbn' => $isbn
        ]);

        echo "<script>alert('You have been removed from the waitlist.');window.location.href='book_details.php?isbn=$isbn';</script>";
        logUserActivity($_SESSION['user_id'], $isbn, 'Removed from List', $conn);
        exit;
    }

    // Handle add to reading list
    if (isset($_POST['add_to_list']) && isset($_POST['list_id'])) {
        $listId = $_POST['list_id'];
        
        try {
            // Check if book is already in the list
            $checkStmt = $conn->prepare("
                SELECT COUNT(*) FROM reading_list_book
                WHERE List_ID = ? AND ISBN = ?
            ");
            $checkStmt->execute([$listId, $isbn]);
            
            if ($checkStmt->fetchColumn() == 0) {
                // Add book to reading list
                $addStmt = $conn->prepare("
                    INSERT INTO reading_list_book (List_ID, ISBN)
                    VALUES (?, ?)
                ");
                $addStmt->execute([$listId, $isbn]);
                
                logUserActivity($_SESSION['user_id'], $isbn, 'Added to List', $conn);
                echo "<script>alert('Book added to reading list successfully!');window.location.href='book_details.php?isbn=$isbn';</script>";
            } else {
                echo "<script>alert('This book is already in the selected reading list.');window.location.href='book_details.php?isbn=$isbn';</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');window.location.href='book_details.php?isbn=$isbn';</script>";
        }
        exit;
    }

    // Handle update status
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['update_status'];
        
        try {
            $conn->beginTransaction();
            
            // Remove from all reading lists first
            $stmt = $conn->prepare("
                DELETE rlb FROM reading_list_book rlb
                JOIN reading_list rl ON rlb.List_ID = rl.List_ID
                WHERE rl.Member_ID = ? AND rlb.ISBN = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $isbn]);
            
            if ($newStatus === 'remove') {
                // Remove status
                $stmt = $conn->prepare("
                    DELETE FROM user_book_status 
                    WHERE Member_ID = ? AND ISBN = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $isbn]);
            } else {
                // Get the reading list ID for the new status
                $listStmt = $conn->prepare("
                    SELECT List_ID FROM reading_list 
                    WHERE Member_ID = ? AND Name = ?
                ");
                $listStmt->execute([$_SESSION['user_id'], $newStatus]);
                $listId = $listStmt->fetchColumn();
                
                if ($listId) {
                    // Add to the corresponding reading list
                    $stmt = $conn->prepare("
                        INSERT IGNORE INTO reading_list_book (List_ID, ISBN)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$listId, $isbn]);
                    
                    // For DNF status, store it as 'Read' in the database
                    $dbStatus = ($newStatus === 'DNF') ? 'Read' : $newStatus;
                    
                    // Update or insert status
                    $stmt = $conn->prepare("
                        INSERT INTO user_book_status (Member_ID, ISBN, Status)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE Status = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $isbn, $dbStatus, $dbStatus]);
                }
            }
            
            logUserActivity($_SESSION['user_id'], $isbn, 'Added to List', $conn);
            $conn->commit();
            echo "<script>window.location.href='book_details.php?isbn=$isbn';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Error updating status: " . addslashes($e->getMessage()) . "');window.location.href='book_details.php?isbn=$isbn';</script>";
        }
        exit;
    }

    // Add this near the top of the file where other POST handlers are
    if (isset($_POST['submit_review'])) {
        $rating = $_POST['rating'] ?? null;
        $review = trim($_POST['review'] ?? '');
        
        try {
            if ($rating) {
                $stmt = $conn->prepare("
                    INSERT INTO review (Member_ID, ISBN, Rating, Comment)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE Rating = ?, Comment = ?
                ");
                $stmt->execute([
                    $_SESSION['user_id'], 
                    $isbn, 
                    $rating, 
                    $review,
                    $rating,
                    $review
                ]);
                logUserActivity($_SESSION['user_id'], $isbn, 'Reviewed', $conn);
                echo "<script>alert('Review submitted successfully!');window.location.href='book_details.php?isbn=$isbn';</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Error submitting review: " . addslashes($e->getMessage()) . "');window.location.href='book_details.php?isbn=$isbn';</script>";
        }
        exit;
    }

    if (isset($_POST['delete_review'])) {
        try {
            $stmt = $conn->prepare("
                DELETE FROM review 
                WHERE Member_ID = ? AND ISBN = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $isbn]);
            logUserActivity($_SESSION['user_id'], $isbn, 'Deleted Review', $conn);
            echo "<script>alert('Review deleted successfully!');window.location.href='book_details.php?isbn=$isbn';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error deleting review: " . addslashes($e->getMessage()) . "');window.location.href='book_details.php?isbn=$isbn';</script>";
        }
        exit;
    }
}




}
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #E6D5C3; 
        }
        .navbar { 
            background-color: #8B7355 !important; 
        }
        .book-cover {
            height: 500px;
            width: 100%;
            object-fit: contain;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
            background-color: white;
            padding: 20px;
        }
        .book-info {
            background-color: #F4EBE2;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .book-title {
            color: #2C3E50;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .book-authors {
            color: #7F8C8D;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        .info-label {
            color: #8B7355;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            color: #2C3E50;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        .description-box {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .availability-box {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .badge-custom {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 6px;
        }
        .btn-custom {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .copy-select {
            border-radius: 6px;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            background-color: white;
        }
        .copy-select:focus {
            border-color: #8B7355;
            box-shadow: 0 0 0 0.2rem rgba(139, 115, 85, 0.25);
        }
        .status-box {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .status-form .btn {
            text-align: left;
            position: relative;
            padding-left: 1rem;
        }
        .status-form .btn i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }
        .rating-stars {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .rating-stars input {
            display: none;
        }

        .star-label {
            cursor: pointer;
            font-size: 2rem;
            color: #dee2e6;
            transition: color 0.2s;
        }

        .rating-stars input:checked ~ .star-label,
        .star-label:hover,
        .star-label:hover ~ .star-label {
            color: #ffc107;
        }

        .review-form textarea {
            resize: none;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            padding: 0.75rem;
        }

        .review-form textarea:focus {
            border-color: #8B7355;
            box-shadow: 0 0 0 0.2rem rgba(139, 115, 85, 0.25);
        }
        .similar-books-container {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: 1rem 0;
            scroll-behavior: smooth;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .similar-books-container::-webkit-scrollbar {
            display: none;
        }
        .similar-book-card {
            flex: 0 0 200px;
            width: 200px;
        }
        .similar-book-card .card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .similar-book-card .card-img-top {
            height: 300px;
            object-fit: cover;
            width: 100%;
        }
        .similar-book-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .similar-book-card .card-title {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        .similar-book-card .card-text {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <?php if ($bookDetails): ?>
        <div class="row">
            <!-- Left Column - Cover Image -->
            <div class="col-md-4 mb-4">
                <div class="position-sticky" style="top: 2rem;">
                    <img src="<?= htmlspecialchars($bookDetails['Image_URL'] ?: 'default_cover.jpg') ?>" 
                         alt="<?= htmlspecialchars($bookDetails['Title']) ?>" 
                         class="book-cover">
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="status-box mt-4">
                            <h5 class="mb-3">My Status</h5>
                            <?php
                            // Fetch current status
                            $statusStmt = $conn->prepare("
                                SELECT Status 
                                FROM user_book_status 
                                WHERE Member_ID = ? AND ISBN = ?
                            ");
                            $statusStmt->execute([$_SESSION['user_id'], $isbn]);
                            $currentStatus = $statusStmt->fetchColumn();
                            
                            // Check if this is a DNF book (stored as 'Read' in DB but shown as DNF)
                            $isDNF = false;
                            if ($currentStatus === 'Read') {
                                $listStmt = $conn->prepare("
                                    SELECT rl.Name
                                    FROM reading_list rl
                                    JOIN reading_list_book rlb ON rl.List_ID = rlb.List_ID
                                    WHERE rl.Member_ID = ? AND rlb.ISBN = ? AND rl.Name = 'DNF'
                                ");
                                $listStmt->execute([$_SESSION['user_id'], $isbn]);
                                $isDNF = $listStmt->rowCount() > 0;
                            }
                            ?>
                            <form method="POST" class="status-form">
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_status" value="Want to Read" 
                                            class="btn <?= $currentStatus === 'Want to Read' ? 'btn-primary' : 'btn-outline-primary' ?> btn-custom">
                                        <i class="bi bi-bookmark-plus me-2"></i>Want to Read
                                    </button>
                                    <button type="submit" name="update_status" value="Currently Reading" 
                                            class="btn <?= $currentStatus === 'Currently Reading' ? 'btn-success' : 'btn-outline-success' ?> btn-custom">
                                        <i class="bi bi-book me-2"></i>Currently Reading
                                    </button>
                                    <button type="submit" name="update_status" value="Read" 
                                            class="btn <?= ($currentStatus === 'Read' && !$isDNF) ? 'btn-info' : 'btn-outline-info' ?> btn-custom">
                                        <i class="bi bi-check-circle me-2"></i>Read
                                    </button>
                                    <button type="submit" name="update_status" value="DNF" 
                                            class="btn <?= $isDNF ? 'btn-danger' : 'btn-outline-danger' ?> btn-custom">
                                        <i class="bi bi-x-circle me-2"></i>Did Not Finish
                                    </button>
                                    <?php if ($currentStatus): ?>
                                        <button type="submit" name="update_status" value="remove" 
                                                class="btn btn-outline-secondary btn-custom">
                                            <i class="bi bi-dash-circle me-2"></i>Remove Status
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <?php if ($currentStatus === 'Read'): ?>
                            <div class="status-box mt-4">
                                <h5 class="mb-3">My Review</h5>
                                <?php
                                // Fetch existing review
                                $reviewStmt = $conn->prepare("
                                    SELECT Rating, Comment as Review 
                                    FROM review 
                                    WHERE Member_ID = ? AND ISBN = ?
                                ");
                                $reviewStmt->execute([$_SESSION['user_id'], $isbn]);
                                $review = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <form method="POST" class="review-form">
                                    <div class="mb-4">
                                        <label class="form-label info-label">Rating</label>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <input type="radio" name="rating" value="<?= $i ?>" 
                                                       id="star<?= $i ?>" <?= ($review && $review['Rating'] == $i) ? 'checked' : '' ?>>
                                                <label for="star<?= $i ?>" class="star-label">
                                                    <i class="bi bi-star-fill"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="review" class="form-label info-label">Review (Optional)</label>
                                        <textarea class="form-control" id="review" name="review" rows="4" 
                                                  placeholder="Share your thoughts about this book..."><?= $review ? htmlspecialchars($review['Review']) : '' ?></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="submit_review" class="btn btn-primary btn-custom">
                                            <i class="bi bi-pencil me-2"></i><?= $review ? 'Update Review' : 'Submit Review' ?>
                                        </button>
                                        <?php if ($review): ?>
                                            <button type="submit" name="delete_review" class="btn btn-outline-danger btn-custom">
                                                <i class="bi bi-trash me-2"></i>Delete Review
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Book Details -->
            <div class="col-md-8">
                <div class="book-info">
                    <h1 class="book-title"><?= htmlspecialchars($bookDetails['Title']) ?></h1>
                    <p class="book-authors">By <?= htmlspecialchars($bookDetails['authors']) ?></p>
                    
                    <div class="row">
                        <?php if (!empty($bookDetails['categories'])): ?>
                            <div class="col-md-6">
                                <div class="info-label">Categories</div>
                                <div class="info-value">
                                    <?php foreach (explode(', ', $bookDetails['categories']) as $category): ?>
                                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($category) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($bookDetails['series_name'])): ?>
                            <div class="col-md-6">
                                <div class="info-label">Series</div>
                                <div class="info-value"><?= htmlspecialchars($bookDetails['series_name']) ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <div class="info-label">Publisher</div>
                            <div class="info-value"><?= htmlspecialchars($bookDetails['Publisher']) ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-label">Publication Year</div>
                            <div class="info-value"><?= htmlspecialchars($bookDetails['Release_Year']) ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-label">Pages</div>
                            <div class="info-value"><?= htmlspecialchars(number_format($bookDetails['Page_Nr'])) ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-label">ISBN</div>
                            <div class="info-value"><?= htmlspecialchars($bookDetails['ISBN']) ?></div>
                        </div>
                    </div>

                    <?php if (!empty($bookDetails['Description'])): ?>
                        <div class="description-box">
                            <div class="info-label mb-3">Description</div>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($bookDetails['Description'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['username'])): ?>
                        <div class="availability-box">
                            <h5 class="mb-4">Availability Status</h5>
                            
                            <?php if (count($availableCopies) > 0): ?>
                                <div class="alert alert-success mb-4">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <?= count($availableCopies) ?> copy/copies available
                                </div>
                                <form method="POST">
                                    <div class="mb-4">
                                        <label for="copy_id" class="form-label info-label">Select a copy to borrow:</label>
                                        <select class="form-select copy-select" name="copy_id" id="copy_id" required>
                                            <option value="" disabled selected>Choose a copy...</option>
                                            <?php foreach ($availableCopies as $copy): ?>
                                                <option value="<?= htmlspecialchars($copy['Copy_ID']) ?>">
                                                    <?= "Copy #{$copy['Copy_ID']} - " . 
                                                        htmlspecialchars($copy['Copy_Condition']) . 
                                                        " - Shelf: " . htmlspecialchars($copy['Shelf_Position']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="borrow" class="btn btn-primary btn-custom">
                                        <i class="bi bi-book me-2"></i>Borrow Now
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning mb-4">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    No copies currently available
                                </div>
                                
                                <?php if (count($borrowedCopies) > 0): ?>
                                    <div class="mb-4">
                                        <div class="info-label mb-2">Expected Return Dates:</div>
                                        <div class="list-group">
                                            <?php foreach ($borrowedCopies as $copy): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Copy #<?= htmlspecialchars($copy['Copy_ID']) ?>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?= date('M d, Y', strtotime($copy['Return_Date'])) ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$onWaitlist): ?>
                                    <form method="POST">
                                        <button type="submit" name="join_waitlist" class="btn btn-warning btn-custom">
                                            <i class="bi bi-clock me-2"></i>Join Waitlist
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info mb-4">
                                        <i class="bi bi-info-circle me-2"></i>
                                        You are #<?= $waitlistPosition + 1 ?> on the waitlist
                                        (<?= $totalWaitlist ?> people waiting)
                                    </div>
                                    <form method="POST">
                                        <button type="submit" name="leave_waitlist" class="btn btn-outline-danger btn-custom">
                                            <i class="bi bi-x-circle me-2"></i>Leave Waitlist
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Reading List Section -->
                        <div class="availability-box mt-4">
                            <h5 class="mb-4">Reading Lists</h5>
                            <?php
                            // Fetch user's reading lists
                            $readingListsStmt = $conn->prepare("
                                SELECT rl.List_ID, rl.Name as List_Name, 
                                       CASE WHEN rlb.ISBN IS NOT NULL THEN 1 ELSE 0 END as is_in_list
                                FROM reading_list rl
                                LEFT JOIN reading_list_book rlb ON rl.List_ID = rlb.List_ID 
                                    AND rlb.ISBN = :isbn
                                WHERE rl.Member_ID = :member_id
                                ORDER BY rl.Name
                            ");
                            $readingListsStmt->execute([
                                ':isbn' => $isbn,
                                ':member_id' => $_SESSION['user_id']
                            ]);
                            $readingLists = $readingListsStmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (empty($readingLists)): ?>
                                <div class="alert alert-info mb-4">
                                    <i class="bi bi-info-circle me-2"></i>
                                    You haven't created any reading lists yet.
                                </div>
                                <a href="reading-lists.php" class="btn btn-success btn-custom">
                                    <i class="bi bi-plus-circle me-2"></i>Create Reading List
                                </a>
                            <?php else: ?>
                                <form method="POST" class="mb-4">
                                    <div class="mb-3">
                                        <label for="list_id" class="form-label info-label">Add to Reading List:</label>
                                        <select class="form-select copy-select" name="list_id" id="list_id">
                                            <option value="" disabled selected>Select a reading list...</option>
                                            <?php foreach ($readingLists as $list): ?>
                                                <option value="<?= htmlspecialchars($list['List_ID']) ?>">
                                                    <?= htmlspecialchars($list['List_Name']) ?>
                                                    <?= $list['is_in_list'] ? ' (Already in list)' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="add_to_list" class="btn btn-success btn-custom">
                                            <i class="bi bi-plus-circle me-2"></i>Add to List
                                        </button>
                                        <a href="reading-lists.php" class="btn btn-outline-success btn-custom">
                                            <i class="bi bi-plus-circle me-2"></i>Create New List
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Similar Books Section -->
        <div class="mt-5">
            <div class="book-info">
                <h3 class="mb-4">Similar Books</h3>
                <div class="position-relative">
                    <div class="similar-books-container d-flex" style="overflow: hidden;">
                        <?php
                        // Get similar books
                        $similarStmt = $conn->prepare("
                            SELECT b.ISBN, b.Title, b.Image_URL,
                                   GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors,
                                   bs.Similarity_Score
                            FROM book_similarity bs
                            JOIN book b ON bs.ISBN_2 = b.ISBN
                            LEFT JOIN wrote w ON b.ISBN = w.ISBN
                            LEFT JOIN author a ON w.Author_ID = a.Author_ID
                            WHERE bs.ISBN_1 = ?
                            GROUP BY b.ISBN, b.Title, b.Image_URL, bs.Similarity_Score
                            ORDER BY bs.Similarity_Score DESC
                            LIMIT 10
                        ");
                        $similarStmt->execute([$isbn]);
                        $similarBooks = $similarStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($similarBooks)):
                            foreach ($similarBooks as $similarBook):
                        ?>
                            <div class="similar-book-card me-3" style="min-width: 200px; flex-shrink: 0;">
                                <div class="card h-100">
                                    <a href="book_details.php?isbn=<?= urlencode($similarBook['ISBN']) ?>" class="text-decoration-none">
                                        <img src="<?= htmlspecialchars($similarBook['Image_URL'] ?? 'default_cover.jpg') ?>" 
                                             class="card-img-top" 
                                             alt="<?= htmlspecialchars($similarBook['Title']) ?>"
                                             style="height: 250px; object-fit: cover;">
                                    </a>
                                    <div class="card-body">
                                        <h6 class="card-title text-truncate"><?= htmlspecialchars($similarBook['Title']) ?></h6>
                                        <p class="card-text small text-muted text-truncate">By <?= htmlspecialchars($similarBook['authors']) ?></p>
                                        <p class="card-text small text-muted">
                                            Similarity: <?= number_format($similarBook['Similarity_Score'] * 100, 1) ?>%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <div class="alert alert-info w-100">
                                No similar books found.
                            </div>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-light position-absolute start-0 top-50 translate-middle-y rounded-circle shadow" 
                            onclick="scrollSimilarBooks('left')" 
                            style="z-index: 1;">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="btn btn-light position-absolute end-0 top-50 translate-middle-y rounded-circle shadow" 
                            onclick="scrollSimilarBooks('right')" 
                            style="z-index: 1;">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Book not found!
    </div>
<?php endif; ?>
<?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function scrollSimilarBooks(direction) {
            const container = document.querySelector('.similar-books-container');
            const scrollAmount = 400; // Adjust this value to control scroll distance
            
            if (direction === 'left') {
                container.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            } else {
                container.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            }
        }
        
    document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('.rating-stars input');
    const starLabels = document.querySelectorAll('.star-label');
    
    // Function to update star colors
    function updateStars(rating) {
        starLabels.forEach((label, index) => {
            if (index < rating) {
                label.style.color = '#ffc107'; // Gold color for filled stars
            } else {
                label.style.color = '#dee2e6'; // Gray color for unfilled stars
            }
        });
    }

    // Initialize stars based on current rating
    const checkedInput = document.querySelector('.rating-stars input:checked');
    if (checkedInput) {
        updateStars(parseInt(checkedInput.value));
    }

    // Add hover effects
    starLabels.forEach((label, index) => {
        label.addEventListener('mouseenter', () => {
            updateStars(index + 1);
        });

        label.addEventListener('mouseleave', () => {
            const checkedInput = document.querySelector('.rating-stars input:checked');
            updateStars(checkedInput ? parseInt(checkedInput.value) : 0);
        });

        label.addEventListener('click', () => {
            const input = label.previousElementSibling;
            input.checked = true;
            updateStars(parseInt(input.value));
        });
    });

    // Handle form submission
    const reviewForm = document.querySelector('.review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            const rating = document.querySelector('.rating-stars input:checked');
            if (!rating) {
                e.preventDefault();
                alert('Please select a rating before submitting.');
            }
        });
    }
});
    </script>
</body>
</html>

<?php
$conn = null;
?>
