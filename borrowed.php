<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection setup
$host = "localhost";
$username = "root";
$password = "lacika";
$database = "Librarydb";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle book return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $borrowId = $_POST['borrow_id'];
    $returnStmt = $conn->prepare("
        UPDATE borrowing 
        SET Status = 'Returned', Return_Date = CURDATE() 
        WHERE Bor_ID = ? AND Member_ID = ?
    ");
    $returnStmt->execute([$borrowId, $_SESSION['user_id']]);
    
    // Log the return activity
    $isbnStmt = $conn->prepare("
        SELECT b.ISBN 
        FROM borrowing br 
        JOIN copy c ON br.Copy_ID = c.Copy_ID 
        JOIN book b ON c.ISBN = b.ISBN 
        WHERE br.Bor_ID = ?
    ");
    $isbnStmt->execute([$borrowId]);
    $isbn = $isbnStmt->fetchColumn();
    logUserActivity($_SESSION['user_id'], $isbn, 'Returned', $conn);
    
    header("Location: borrowed.php");
    exit();
}

// Get current borrowed books
$currentSql = "
    SELECT 
        b.Bor_ID,
        bk.ISBN,
        bk.Title,
        bk.Image_URL,
        GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors,
        b.Borrowing_Date,
        DATE_ADD(b.Borrowing_Date, INTERVAL 14 DAY) as Due_Date,
        b.Return_Date,
        b.Status,
        c.Copy_Condition,
        CASE 
            WHEN b.Status = 'Checked Out' AND CURDATE() > DATE_ADD(b.Borrowing_Date, INTERVAL 14 DAY) 
            THEN DATEDIFF(CURDATE(), DATE_ADD(b.Borrowing_Date, INTERVAL 14 DAY)) * 0.50
            ELSE 0 
        END as Fine
    FROM borrowing b
    JOIN copy c ON b.Copy_ID = c.Copy_ID
    JOIN book bk ON c.ISBN = bk.ISBN
    JOIN wrote w ON bk.ISBN = w.ISBN
    JOIN author a ON w.Author_ID = a.Author_ID
    WHERE b.Member_ID = ? AND b.Status IN ('Checked Out', 'Overdue')
    GROUP BY b.Bor_ID, bk.ISBN, bk.Title, bk.Image_URL, b.Borrowing_Date, b.Return_Date, b.Status, c.Copy_Condition
    ORDER BY 
        CASE 
            WHEN b.Status = 'Overdue' THEN 1
            ELSE 2
        END,
        DATE_ADD(b.Borrowing_Date, INTERVAL 14 DAY) ASC
";

// Get past borrowed books
$pastSql = "
    SELECT 
        b.Bor_ID,
        bk.ISBN,
        bk.Title,
        bk.Image_URL,
        GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors,
        b.Borrowing_Date,
        DATE_ADD(b.Borrowing_Date, INTERVAL 14 DAY) as Due_Date,
        b.Return_Date,
        b.Status,
        c.Copy_Condition
    FROM borrowing b
    JOIN copy c ON b.Copy_ID = c.Copy_ID
    JOIN book bk ON c.ISBN = bk.ISBN
    JOIN wrote w ON bk.ISBN = w.ISBN
    JOIN author a ON w.Author_ID = a.Author_ID
    WHERE b.Member_ID = ? AND b.Status = 'Returned'
    GROUP BY b.Bor_ID, bk.ISBN, bk.Title, bk.Image_URL, b.Borrowing_Date, b.Return_Date, b.Status, c.Copy_Condition
    ORDER BY b.Return_Date DESC
    LIMIT 10
";

$currentStmt = $conn->prepare($currentSql);
$currentStmt->execute([$_SESSION['user_id']]);
$currentBorrowed = $currentStmt->fetchAll(PDO::FETCH_ASSOC);

$pastStmt = $conn->prepare($pastSql);
$pastStmt->execute([$_SESSION['user_id']]);
$pastBorrowed = $pastStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Borrowed Books - Laci's Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background-color: #E6D5C3; }
        .navbar { background-color: #8B7355 !important; }
        .book-card { 
            background-color: #F4EBE2;
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-5px);
        }
        .overdue {
            border-left: 4px solid #dc3545;
        }
        .fine-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .section-title {
            color: #8B7355;
            border-bottom: 2px solid #8B7355;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Laci's Library</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Catalogue
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="books.php">Books</a></li>
                        <li><a class="dropdown-item" href="authors.php">Authors</a></li>
                        <li><a class="dropdown-item" href="categories.php">Categories</a></li>
                        <li><a class="dropdown-item" href="book_series.php">Book Series</a></li>
                    </ul>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            My Library
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="reading-lists.php">Reading Lists</a></li>
                            <li><a class="dropdown-item" href="borrowed.php">Borrowed Books</a></li>
                            <li><a class="dropdown-item" href="read.php">Read Books</a></li>
                            <li><a class="dropdown-item" href="recommendations.php">Recommendations</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="section-title">Currently Borrowed Books</h2>
    
    <?php if (empty($currentBorrowed)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            You don't have any borrowed books at the moment.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($currentBorrowed as $book): ?>
                <div class="col">
                    <div class="card h-100 book-card <?= $book['Status'] === 'Overdue' ? 'overdue' : '' ?>">
                        <?php if ($book['Fine'] > 0): ?>
                            <span class="badge bg-danger fine-badge">
                                Fine: $<?= number_format($book['Fine'], 2) ?>
                            </span>
                        <?php endif; ?>
                        
                        <img src="<?= htmlspecialchars($book['Image_URL'] ?: 'default_cover.jpg') ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($book['Title']) ?>"
                             style="height: 300px; object-fit: contain;">
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['Title']) ?></h5>
                            <p class="card-text text-muted">By <?= htmlspecialchars($book['authors']) ?></p>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Borrowed: <?= date('M d, Y', strtotime($book['Borrowing_Date'])) ?>
                                </small>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-x me-1"></i>
                                    Due: <?= date('M d, Y', strtotime($book['Due_Date'])) ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <span class="badge <?= $book['Status'] === 'Overdue' ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $book['Status'] ?>
                                </span>
                                <span class="badge bg-info ms-2">
                                    <?= $book['Copy_Condition'] ?>
                                </span>
                            </div>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="borrow_id" value="<?= $book['Bor_ID'] ?>">
                                <button type="submit" name="return_book" class="btn btn-primary">
                                    <i class="bi bi-arrow-return-left me-1"></i>
                                    Return Book
                                </button>
                            </form>
                            
                            <a href="book_details.php?isbn=<?= urlencode($book['ISBN']) ?>" 
                               class="btn btn-outline-secondary ms-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="section-title mt-5">Recently Returned Books</h2>
    
    <?php if (empty($pastBorrowed)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            You haven't returned any books yet.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($pastBorrowed as $book): ?>
                <div class="col">
                    <div class="card h-100 book-card">
                        <img src="<?= htmlspecialchars($book['Image_URL'] ?: 'default_cover.jpg') ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($book['Title']) ?>"
                             style="height: 300px; object-fit: contain;">
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['Title']) ?></h5>
                            <p class="card-text text-muted">By <?= htmlspecialchars($book['authors']) ?></p>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Borrowed: <?= date('M d, Y', strtotime($book['Borrowing_Date'])) ?>
                                </small>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Returned: <?= date('M d, Y', strtotime($book['Return_Date'])) ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <span class="badge bg-secondary">Returned</span>
                                <span class="badge bg-info ms-2">
                                    <?= $book['Copy_Condition'] ?>
                                </span>
                            </div>
                            
                            <a href="book_details.php?isbn=<?= urlencode($book['ISBN']) ?>" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-info-circle me-1"></i>
                                Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn = null;
?>
