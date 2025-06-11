<?php
session_start();
// Database connection setup
$host = "localhost";
$username = "root";
$password = "lacika";
$database = "Librarydb";
require_once 'functions.php';
try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get current page and sort parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'title';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$availability = isset($_GET['availability']) ? $_GET['availability'] : '';

$books_per_page = 12;
$offset = ($page - 1) * $books_per_page;

// Get sorting
$sort_column = match($sort_by) {
    'page' => 'b.Page_Nr',
    'year' => 'b.Release_Year',
    default => 'b.Title'
};

$sort_direction = $sort_order === 'desc' ? 'DESC' : 'ASC';

// Build availability condition
$availability_condition = '';
if ($availability === 'available') {
    $availability_condition = "AND EXISTS (
        SELECT 1 FROM copy c2 
        WHERE c2.ISBN = b.ISBN 
        AND NOT EXISTS (
            SELECT 1 FROM borrowing b2 
            WHERE b2.Copy_ID = c2.Copy_ID 
            AND b2.Status IN ('Checked Out', 'Overdue')
        )
    )";
}

// First get total count of books
$count_sql = "SELECT COUNT(DISTINCT b.ISBN) as total 
              FROM Book b
              WHERE 1=1 $availability_condition";
$total_books = $conn->query($count_sql)->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_books / $books_per_page);

// Fetch books with pagination and sorting
$sql1 = "WITH RankedBooks AS (
    SELECT DISTINCT b.ISBN, 
           b.Title, 
           b.Page_Nr,
           GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors, 
           b.Image_URL, 
           GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories,
           b.Release_Year,
           ROW_NUMBER() OVER (ORDER BY $sort_column $sort_direction) as row_num
    FROM Book b
    INNER JOIN wrote w ON b.isbn = w.isbn
    INNER JOIN author a ON a.author_id = w.author_id
    LEFT JOIN belongs bl ON b.isbn = bl.isbn
    LEFT JOIN category c ON bl.category_id = c.category_id
    WHERE 1=1 $availability_condition
    GROUP BY b.isbn, b.Title, b.Page_Nr, b.Image_URL, b.Release_Year
)
SELECT * FROM RankedBooks 
WHERE row_num BETWEEN :offset + 1 AND :offset + :limit";

$stmt = $conn->prepare($sql1);
$stmt->bindValue(':limit', $books_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .dropdown-menu {
            background-color: #F4EBE2;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 0.5rem;
        }
        .dropdown-item {
            color: #2C3E50;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .dropdown-item:hover {
            background-color: #8B7355;
            color: #fff;
        }
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .navbar-toggler:focus {
            box-shadow: none;
            background-color: rgba(255,255,255,0.1);
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .welcome-text {
            color: rgba(255,255,255,0.9);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background-color: rgba(255,255,255,0.1);
            margin-right: 1rem;
        }
        .btn-login {
            background-color: rgba(255,255,255,0.1);
            color: #fff !important;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background-color: rgba(255,255,255,0.2);
        }
        .btn-register {
            background-color: #fff;
            color: #8B7355 !important;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-left: 0.5rem;
        }
        .btn-register:hover {
            background-color: #F4EBE2;
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
        .card-footer {
            background-color: transparent;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
        }
        .search-container {
            position: relative;
            display: none;
            width: 300px;
        }
        .search-container.active {
            display: block;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .search-results.active {
            display: block;
        }
        .search-result-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .search-result-item:hover {
            background-color: #f8f9fa;
        }
        .search-result-item img {
            width: 50px;
            height: 75px;
            object-fit: contain;
            margin-right: 12px;
            background-color: white;
            padding: 5px;
            border-radius: 4px;
        }
        .search-result-item .book-info {
            flex: 1;
        }
        .search-result-item .book-title {
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 4px;
        }
        .search-result-item .book-author {
            font-size: 0.9rem;
            color: #7F8C8D;
        }
        .search-toggle {
            color: white;
            font-size: 1.2rem;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        .search-toggle:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .search-input {
            border-radius: 20px;
            padding: 8px 16px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(139, 115, 85, 0.25);
        }
        .no-results {
            padding: 20px;
            text-align: center;
            color: #7F8C8D;
        }
         .navbar-nav {
            align-items: center;
        }
        .d-flex.align-items-center {
            align-items: center !important;
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
        .sort-dropdown {
            position: relative;
            display: inline-block;
        }
        .sort-btn {
            background-color: #8B7355;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .sort-btn:hover {
            background-color: #6B5B4C;
        }
        .sort-options {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.5rem;
            min-width: 200px;
            display: none;
            z-index: 1000;
        }
        .sort-options.show {
            display: block;
        }
        .sort-option {
            display: block;
            padding: 0.5rem 1rem;
            color: #2C3E50;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        .sort-option:hover {
            background-color: #F4EBE2;
            color: #8B7355;
        }
        .sort-option.active {
            background-color: #8B7355;
            color: white;
        }
        .sort-divider {
            height: 1px;
            background-color: #eee;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-book"></i> Laci's Library
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-grid-3x3-gap"></i> Catalogue
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="books.php"><i class="bi bi-book"></i> Books</a></li>
                        <li><a class="dropdown-item" href="authors.php"><i class="bi bi-person"></i> Authors</a></li>
                        <li><a class="dropdown-item" href="categories.php"><i class="bi bi-tags"></i> Categories</a></li>
                        <li><a class="dropdown-item" href="book_series.php"><i class="bi bi-collection"></i> Book Series</a></li>
                    </ul>
                </li>
                <?php
                if (isset($_SESSION['user_id'])) {
                    echo '<li class="nav-item dropdown">';
                    echo '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">';
                    echo '<i class="bi bi-person-circle"></i> My Library';
                    echo '</a>';
                    echo '<ul class="dropdown-menu">';
                    echo '<li><a class="dropdown-item" href="reading-lists.php"><i class="bi bi-list-check"></i> Reading Lists</a></li>';
                    echo '<li><a class="dropdown-item" href="borrowed.php"><i class="bi bi-bookmark"></i> Borrowed Books</a></li>';
                    echo '<li><a class="dropdown-item" href="read.php"><i class="bi bi-check-circle"></i> Read Books</a></li>';
                    echo '<li><a class="dropdown-item" href="recommendations.php"><i class="bi bi-stars"></i> Recommendations</a></li>';
                    echo '</ul>';
                    echo '</li>';
                }
                ?>
            </ul>
            <div class="d-flex align-items-center">
                <div class="search-container">
                    <input type="text" class="form-control search-input" id="searchInput" placeholder="Search books...">
                    <div class="search-results" id="searchResults"></div>
                </div>
                <button class="btn btn-link text-light ms-2 search-toggle" id="searchToggle">
                    <i class="bi bi-search"></i>
                </button>
                <ul class="navbar-nav">
                    <?php
                    if (!isset($_SESSION['user_id'])) {
                        echo '<li class="nav-item"><a class="nav-link btn-login" href="login.php">Login</a></li>';
                        echo '<li class="nav-item"><a class="nav-link btn-register" href="register.php">Register</a></li>';
                    } else {
                        echo '<li class="nav-item"><span class="welcome-text"><i class="bi bi-person-circle"></i> Welcome, ' . htmlspecialchars($_SESSION['username']) . '</span></li>';
                        echo '<li class="nav-item"><a class="nav-link btn-login" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</nav>
<div class="container mt-4">
    <div class="d-flex justify-content-end mb-4">
        <div class="sort-dropdown">
            <button class="sort-btn" onclick="toggleSortOptions()">
                <i class="bi bi-sort-down"></i> Sort
            </button>
            <div class="sort-options" id="sortOptions">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['availability' => ''])); ?>" 
                   class="sort-option <?php echo $availability === '' ? 'active' : ''; ?>">
                    All Books
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['availability' => 'available'])); ?>" 
                   class="sort-option <?php echo $availability === 'available' ? 'active' : ''; ?>">
                    Available Only
                </a>
                <div class="sort-divider"></div>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'page', 'order' => 'asc'])); ?>" 
                   class="sort-option <?php echo $sort_by === 'page' && $sort_order === 'asc' ? 'active' : ''; ?>">
                    Page Number (Ascending)
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'page', 'order' => 'desc'])); ?>" 
                   class="sort-option <?php echo $sort_by === 'page' && $sort_order === 'desc' ? 'active' : ''; ?>">
                    Page Number (Descending)
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title', 'order' => 'asc'])); ?>" 
                   class="sort-option <?php echo $sort_by === 'title' && $sort_order === 'asc' ? 'active' : ''; ?>">
                    Title (A-Z)
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title', 'order' => 'desc'])); ?>" 
                   class="sort-option <?php echo $sort_by === 'title' && $sort_order === 'desc' ? 'active' : ''; ?>">
                    Title (Z-A)
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'year', 'order' => 'desc'])); ?>" 
                   class="sort-option <?php echo $sort_by === 'year' && $sort_order === 'desc' ? 'active' : ''; ?>">
                    Release Year (Newest)
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'year', 'order' => 'asc'])); ?>" 
                   class="sort-option <?php echo $sort_by === 'year' && $sort_order === 'asc' ? 'active' : ''; ?>">
                    Release Year (Oldest)
                </a>
            </div>
        </div>
    </div>
    <?php
    if ($stmt->rowCount() > 0) {
        echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">';
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<div class="col">';
            echo '<div class="card h-100">';
            
            // Image section
            echo '<a href="book_details.php?isbn=' . urlencode($row['ISBN']) . '" class="text-decoration-none">';
            echo '<img src="' . ($row['Image_URL'] ?: 'default_cover.jpg') . '" 
                      class="card-img-top" 
                      alt="' . htmlspecialchars($row['Title']) . '">';
            echo '</a>';

            // Card body
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($row['Title']) . '</h5>';
            echo '<p class="card-text">By ' . htmlspecialchars($row['authors']) . '</p>';
            
            // Categories
            if (!empty($row['categories'])) {
                echo '<div class="d-flex flex-wrap gap-1">';
                foreach (explode(', ', $row['categories']) as $category) {
                    echo '<span class="badge bg-secondary">' . htmlspecialchars($category) . '</span>';
                }
                echo '</div>';
            }
            echo '</div>';

            // Card footer with link
            echo '<div class="card-footer">';
            echo '<a href="book_details.php?isbn=' . urlencode($row['ISBN']) . '" 
                      class="btn btn-primary w-100">View Details</a>';
            echo '</div>';
            
            echo '</div></div>';
        }
        echo '</div>';
        
        // Add pagination controls
        echo '<nav aria-label="Page navigation" class="mt-4">';
        echo '<ul class="pagination justify-content-center">';
        
        // Previous button
        $prev_disabled = $page <= 1 ? 'disabled' : '';
        echo '<li class="page-item ' . $prev_disabled . '">';
        echo '<a class="page-link" href="?page=' . ($page - 1) . '" aria-label="Previous">';
        echo '<span aria-hidden="true">&laquo;</span>';
        echo '</a></li>';
        
        // Page numbers
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
            if ($start_page > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active = $i == $page ? 'active' : '';
            echo '<li class="page-item ' . $active . '">';
            echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
            echo '</li>';
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
        }
        
        // Next button
        $next_disabled = $page >= $total_pages ? 'disabled' : '';
        echo '<li class="page-item ' . $next_disabled . '">';
        echo '<a class="page-link" href="?page=' . ($page + 1) . '" aria-label="Next">';
        echo '<span aria-hidden="true">&raquo;</span>';
        echo '</a></li>';
        
        echo '</ul>';
        echo '</nav>';
    } else {
        echo '<div class="alert alert-info">No books found!</div>';
    }
    ?>
</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchToggle = document.getElementById('searchToggle');
    const searchContainer = document.querySelector('.search-container');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;

    searchToggle.addEventListener('click', function() {
        searchContainer.classList.toggle('active');
        if (searchContainer.classList.contains('active')) {
            searchInput.focus();
        }
    });

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 4) {
            searchResults.classList.remove('active');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch('search_books.php?query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(book => {
                            const div = document.createElement('div');
                            div.className = 'search-result-item d-flex align-items-center';
                            div.innerHTML = `
                                <img src="${book.Image_URL || 'default_cover.jpg'}" alt="${book.Title}">
                                <div>
                                    <div class="fw-bold">${book.Title}</div>
                                    <div class="small text-muted">${book.authors}</div>
                                </div>
                            `;
                            div.addEventListener('click', () => {
                                window.location.href = 'book_details.php?isbn=' + book.ISBN;
                            });
                            searchResults.appendChild(div);
                        });
                        searchResults.classList.add('active');
                    } else {
                        searchResults.innerHTML = '<div class="p-3 text-center">No results found</div>';
                        searchResults.classList.add('active');
                    }
                });
        }, 300);
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchContainer.contains(e.target) && !searchToggle.contains(e.target)) {
            searchResults.classList.remove('active');
        }
    });
});

function toggleSortOptions() {
    const sortOptions = document.getElementById('sortOptions');
    sortOptions.classList.toggle('show');
}

// Close the dropdown when clicking outside
document.addEventListener('click', function(event) {
    const sortDropdown = document.querySelector('.sort-dropdown');
    const sortOptions = document.getElementById('sortOptions');
    if (!sortDropdown.contains(event.target)) {
        sortOptions.classList.remove('show');
    }
});
</script>
</body>
</html>

<?php
$conn = null;
?>
