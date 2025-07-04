<?php
session_start();
require_once 'functions.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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

// uj lista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_list'])) {
    $listName = trim($_POST['list_name']);
    if (!empty($listName)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO reading_list (Member_ID, Name)
                VALUES (?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $listName]);
            echo "<script>alert('Reading list created successfully!');window.location.href='reading-lists.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error creating reading list: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

//Lista torlese
if (isset($_GET['delete_list'])) {
    $listId = $_GET['delete_list'];
    try {
        $stmt = $conn->prepare("
            DELETE FROM reading_list 
            WHERE List_ID = ? AND Member_ID = ?
        ");
        $stmt->execute([$listId, $_SESSION['user_id']]);
        echo "<script>alert('Reading list deleted successfully!');window.location.href='reading-lists.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error deleting reading list: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Konzv kiszedese
if (isset($_GET['remove_book'])) {
    $listId = $_GET['list_id'];
    $isbn = $_GET['remove_book'];
    try {
        $stmt = $conn->prepare("
            DELETE FROM reading_list_book
            WHERE List_ID = ? AND ISBN = ?
        ");
        $stmt->execute([$listId, $isbn]);
        echo "<script>alert('Book removed from list successfully!');window.location.href='reading-lists.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error removing book: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Osszes lista
$listsStmt = $conn->prepare("
    SELECT rl.List_ID, rl.Name as List_Name, COUNT(rlb.ISBN) as book_count
    FROM reading_list rl
    LEFT JOIN reading_list_book rlb ON rl.List_ID = rlb.List_ID
    WHERE rl.Member_ID = ?
    GROUP BY rl.List_ID, rl.Name
    ORDER BY rl.Name
");
$listsStmt->execute([$_SESSION['user_id']]);
$readingLists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Reading Lists - Laci's Library</title>
    <link rel="icon" type="favicon" href="img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #E6D5C3; 
        }
        .navbar { 
            background-color: #8B7355 !important; 
        }
        .list-card {
            background-color: #F4EBE2;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .list-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .book-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .book-cover {
            height: 200px;
            object-fit: contain;
            background-color: white;
            padding: 10px;
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
        .section-title {
            color: #8B7355;
            border-bottom: 2px solid #8B7355;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="section-title">My Reading Lists</h2>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-success btn-custom" data-bs-toggle="modal" data-bs-target="#createListModal">
                <i class="bi bi-plus-circle me-2"></i>Create New List
            </button>
        </div>
    </div>

    <?php if (empty($readingLists)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            You haven't created any reading lists yet. Create your first list to start organizing your books!
        </div>
    <?php else: ?>
        <?php foreach ($readingLists as $list): ?>
            <div class="list-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h4 mb-0"><?= htmlspecialchars($list['List_Name']) ?></h3>
                        <div class="d-flex gap-2">
                            <span class="badge bg-secondary">
                                <?= $list['book_count'] ?> book<?= $list['book_count'] != 1 ? 's' : '' ?>
                            </span>
                            <a href="?delete_list=<?= $list['List_ID'] ?>" 
                               class="btn btn-outline-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this list?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>

                    <?php
                    // lekeres konyv
                    $booksStmt = $conn->prepare("
                        SELECT b.*, GROUP_CONCAT(a.Name SEPARATOR ', ') as authors
                        FROM reading_list_book rlb
                        JOIN Book b ON rlb.ISBN = b.ISBN
                        LEFT JOIN wrote w ON b.ISBN = w.ISBN
                        LEFT JOIN author a ON w.Author_ID = a.Author_ID
                        WHERE rlb.List_ID = ?
                        GROUP BY b.ISBN
                        ORDER BY b.Title
                    ");
                    $booksStmt->execute([$list['List_ID']]);
                    $books = $booksStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (empty($books)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            This list is empty. Add some books to get started!
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-4 g-4">
                            <?php foreach ($books as $book): ?>
                                <div class="col">
                                    <div class="book-card h-100">
                                        <div class="card-body">
                                            <img src="<?= htmlspecialchars($book['Image_URL'] ?: 'default_cover.jpg') ?>" 
                                                 alt="<?= htmlspecialchars($book['Title']) ?>" 
                                                 class="book-cover w-100 mb-3">
                                            <h5 class="card-title"><?= htmlspecialchars($book['Title']) ?></h5>
                                            <p class="card-text text-muted small">
                                                By <?= htmlspecialchars($book['authors']) ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="book_details.php?isbn=<?= $book['ISBN'] ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    View Details
                                                </a>
                                                <a href="?list_id=<?= $list['List_ID'] ?>&remove_book=<?= $book['ISBN'] ?>" 
                                                   class="btn btn-outline-danger btn-sm"
                                                   onclick="return confirm('Remove this book from the list?')">
                                                    <i class="bi bi-x"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Lista-->
<div class="modal fade" id="createListModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Reading List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="list_name" class="form-label">List Name</label>
                        <input type="text" class="form-control" id="list_name" name="list_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_list" class="btn btn-success">Create List</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn = null;
?> 