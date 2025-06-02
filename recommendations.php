<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
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

// Get filter parameters
$method = isset($_GET['method']) ? $_GET['method'] : 'hybrid';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$books_per_page = 12;
$offset = ($page - 1) * $books_per_page;

// Get recommendations based on method
$recommendations = [];
$total_recommendations = 0;

if ($method === 'hybrid') {
    // Execute Python script for hybrid recommendations
    $command = "python hybrid_recommendations.py";
    $output = shell_exec($command);
    
    // Parse the output to get recommendations for current user
    $lines = explode("\n", $output);
    $current_user = false;
    $user_recommendations = [];
    
    foreach ($lines as $line) {
        if (strpos($line, "Hybrid Recommendations for " . $_SESSION['username']) !== false) {
            $current_user = true;
            continue;
        }
        if ($current_user && strpos($line, "====") !== false) {
            $current_user = false;
            continue;
        }
        if ($current_user && preg_match('/^\d+\.\s+(.+?)\s+by\s+(.+?)$/', $line, $matches)) {
            // Get book details including cover
            $stmt = $conn->prepare("
                SELECT b.ISBN, b.Image_URL
                FROM book b
                WHERE b.Title = :title
                LIMIT 1
            ");
            $stmt->execute(['title' => $matches[1]]);
            $book_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $user_recommendations[] = [
                'ISBN' => $book_details['ISBN'] ?? '',
                'title' => $matches[1],
                'authors' => $matches[2],
                'Image_URL' => $book_details['Image_URL'] ?? 'default_cover.jpg'
            ];
        }
    }
    
    // Limit to 12 recommendations
    $recommendations = array_slice($user_recommendations, 0, 12);
    $total_recommendations = count($recommendations);
} elseif ($method === 'collaborative') {
    // Execute Python script for collaborative filtering
    $command = "python collaborative_filtering.py";
    $output = shell_exec($command);
    
    // Parse the output similar to hybrid
    $lines = explode("\n", $output);
    $current_user = false;
    $user_recommendations = [];
    
    foreach ($lines as $line) {
        if (strpos($line, "Recommendations for " . $_SESSION['username']) !== false) {
            $current_user = true;
            continue;
        }
        if ($current_user && strpos($line, "====") !== false) {
            $current_user = false;
            continue;
        }
        if ($current_user && preg_match('/^\d+\.\s+(.+?)\s+by\s+(.+?)$/', $line, $matches)) {
            // Get book details including cover
            $stmt = $conn->prepare("
                SELECT b.ISBN, b.Image_URL
                FROM book b
                WHERE b.Title = :title
                LIMIT 1
            ");
            $stmt->execute(['title' => $matches[1]]);
            $book_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $user_recommendations[] = [
                'ISBN' => $book_details['ISBN'] ?? '',
                'title' => $matches[1],
                'authors' => $matches[2],
                'Image_URL' => $book_details['Image_URL'] ?? 'default_cover.jpg'
            ];
        }
    }
    
    // Limit to 12 recommendations
    $recommendations = array_slice($user_recommendations, 0, 12);
    $total_recommendations = count($recommendations);
} else {
    // Content-based recommendations
    $stmt = $conn->prepare("
        SELECT DISTINCT b.ISBN, b.Title, b.Image_URL,
               GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors,
               AVG(bs.Similarity_Score) as avg_similarity
        FROM book b
        INNER JOIN wrote w ON b.ISBN = w.ISBN
        INNER JOIN author a ON w.Author_ID = a.Author_ID
        INNER JOIN book_similarity bs ON b.ISBN = bs.ISBN_2
        INNER JOIN user_book_status ubs ON bs.ISBN_1 = ubs.ISBN
        WHERE ubs.Member_ID = :user_id
        AND ubs.Status = 'Read'
        AND b.ISBN NOT IN (
            SELECT ISBN 
            FROM user_book_status 
            WHERE Member_ID = :user_id 
            AND Status = 'Read'
        )
        GROUP BY b.ISBN, b.Title, b.Image_URL
        ORDER BY avg_similarity DESC
        LIMIT 12
    ");
    
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_recommendations = count($recommendations);
}

// Since we're limiting to 12 books, we don't need pagination
$total_pages = 1;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recommendations - Laci's Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #E6D5C3; 
        }
        .navbar { 
            background-color: #8B7355 !important;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: #fff !important;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .navbar-brand:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.2rem;
        }
        .nav-link:hover {
            color: #fff !important;
            background-color: rgba(255,255,255,0.1);
        }
        .card { 
            background-color: #F4EBE2;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .card:hover { 
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .card img { 
            transition: transform 0.3s ease;
            height: 300px;
            object-fit: contain;
            background-color: white;
            padding: 20px;
        }
        .card:hover img { 
            transform: scale(1.05);
        }
        .card-body {
            padding: 1.5rem;
            background-color: #F4EBE2;
        }
        .card-title {
            color: #2C3E50;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
            height: 2.8em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .card-text {
            color: #7F8C8D;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .filter-section {
            background-color: #F4EBE2;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .filter-title {
            color: #2C3E50;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .filter-btn {
            background-color: #8B7355;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .filter-btn:hover {
            background-color: #6B5B4C;
            color: white;
        }
        .filter-btn.active {
            background-color: #6B5B4C;
        }
        .pagination {
            margin-top: 2rem;
        }
        .page-link {
            color: #8B7355;
            border: none;
            padding: 0.5rem 1rem;
            margin: 0 0.2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .page-link:hover {
            background-color: #F4EBE2;
            color: #8B7355;
        }
        .page-item.active .page-link {
            background-color: #8B7355;
            color: white;
        }
        .page-item.disabled .page-link {
            color: #ccc;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="filter-section">
            <h3 class="filter-title">Recommendation Method</h3>
            <div class="d-flex gap-2">
                <a href="?method=hybrid<?php echo $page > 1 ? '&page=' . $page : ''; ?>" 
                   class="filter-btn <?php echo $method === 'hybrid' ? 'active' : ''; ?>">
                    Hybrid
                </a>
                <a href="?method=collaborative<?php echo $page > 1 ? '&page=' . $page : ''; ?>" 
                   class="filter-btn <?php echo $method === 'collaborative' ? 'active' : ''; ?>">
                    Collaborative
                </a>
                <a href="?method=content<?php echo $page > 1 ? '&page=' . $page : ''; ?>" 
                   class="filter-btn <?php echo $method === 'content' ? 'active' : ''; ?>">
                    Content-Based
                </a>
            </div>
        </div>

        <?php if (!empty($recommendations)): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
                <?php foreach ($recommendations as $book): ?>
                    <div class="col">
                        <div class="card h-100">
                            <a href="book_details.php?isbn=<?php echo urlencode($book['ISBN']); ?>" class="text-decoration-none">
                                <img src="<?php echo htmlspecialchars($book['Image_URL'] ?? 'default_cover.jpg'); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($book['Title']); ?>">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($book['Title']); ?></h5>
                                <p class="card-text">By <?php echo htmlspecialchars($book['authors']); ?></p>
                                <?php if (isset($book['avg_similarity'])): ?>
                                    <p class="card-text">
                                        <small class="text-muted">Similarity Score: <?php echo number_format($book['avg_similarity'], 2); ?></small>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <a href="book_details.php?isbn=<?php echo urlencode($book['ISBN']); ?>" 
                                   class="btn btn-primary w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No recommendations available. Try interacting with more books to get personalized recommendations!
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 