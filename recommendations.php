<?php
session_start();
require_once 'functions.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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


$method = isset($_GET['method']) ? $_GET['method'] : 'hybrid';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$books_per_page = 12;
$offset = ($page - 1) * $books_per_page;

//Methodus alapu aj
$recommendations = [];
$total_recommendations = 0;

if ($method === 'hybrid') {
    // hibrid
    $stmt = $conn->prepare("
        SELECT b.ISBN, b.Title, b.Image_URL,
               GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors,
               r.Score as recommendation_score
        FROM recommendations r
        JOIN book b ON r.ISBN = b.ISBN
        LEFT JOIN wrote w ON b.ISBN = w.ISBN
        LEFT JOIN author a ON w.Author_ID = a.Author_ID
        WHERE r.Member_ID = :user_id
        GROUP BY b.ISBN, b.Title, b.Image_URL, r.Score
        ORDER BY r.Score DESC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $books_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $countStmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM recommendations 
        WHERE Member_ID = :user_id
    ");
    $countStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $countStmt->execute();
    $total_recommendations = $countStmt->fetchColumn();
    
    $total_pages = ceil($total_recommendations / $books_per_page);
} elseif ($method === 'collaborative') {
    // Kollab
    $stmt = $conn->prepare("
        SELECT b.ISBN, b.Title, b.Image_URL,
               GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors,
               cr.Score as recommendation_score
        FROM collab_recommendations cr
        JOIN book b ON cr.ISBN = b.ISBN
        LEFT JOIN wrote w ON b.ISBN = w.ISBN
        LEFT JOIN author a ON w.Author_ID = a.Author_ID
        WHERE cr.Member_ID = :user_id
        GROUP BY b.ISBN, b.Title, b.Image_URL, cr.Score
        ORDER BY cr.Score DESC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $books_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $countStmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM collab_recommendations 
        WHERE Member_ID = :user_id
    ");
    $countStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $countStmt->execute();
    $total_recommendations = $countStmt->fetchColumn();
    
    $total_pages = ceil($total_recommendations / $books_per_page);
} else {
    // Tartalom
    $stmt = $conn->prepare("
        WITH weighted_scores AS (
            SELECT b.ISBN, b.Title, b.Image_URL,
                   GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors,
                   AVG(bs.Similarity_Score * COALESCE(r.Rating, 1)) as raw_score
            FROM book b
            INNER JOIN wrote w ON b.ISBN = w.ISBN
            INNER JOIN author a ON w.Author_ID = a.Author_ID
            INNER JOIN book_similarity bs ON b.ISBN = bs.ISBN_2
            INNER JOIN user_book_status ubs ON bs.ISBN_1 = ubs.ISBN
            LEFT JOIN review r ON bs.ISBN_1 = r.ISBN AND r.Member_ID = :user_id
            WHERE ubs.Member_ID = :user_id
            AND ubs.Status = 'Read'
            AND b.ISBN NOT IN (
                SELECT ISBN 
                FROM user_book_status 
                WHERE Member_ID = :user_id 
                AND Status = 'Read'
            )
            GROUP BY b.ISBN, b.Title, b.Image_URL
        )
        SELECT ISBN, Title, Image_URL, authors,
               raw_score / (SELECT MAX(raw_score) FROM weighted_scores) as avg_similarity
        FROM weighted_scores
        ORDER BY avg_similarity DESC
        LIMIT 12
    ");
    
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_recommendations = count($recommendations);
}

$total_pages = 1;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recommendations - Laci's Library</title>
    <link rel="icon" type="favicon" href="img/favicon.png">
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
        <h2 class="section-title">Recommendations</h2>
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
                                <?php if (isset($book['recommendation_score'])): ?>
                                    <p class="card-text">
                                        <small class="text-muted">Recommendation Score: <?php echo number_format($book['recommendation_score'], 2); ?></small>
                                    </p>
                                <?php endif; ?>
                                <?php if (isset($book['avg_similarity'])): ?>
                                    <p class="card-text">
                                        <small class="text-muted">Similarity Score: <?php echo number_format($book['avg_similarity'], 2); ?></small>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <a href="book_details.php?isbn=<?php echo urlencode($book['ISBN']); ?>" 
                                   class="btn btn-primary w-100" style="background-color: #8B7355; border-color: #8B7355;">View Details</a>
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

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 