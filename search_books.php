<?php
session_start();
header('Content-Type: application/json');

// Database connection setup
$host = "localhost";
$username = "root";
$password = "lacika";
$database = "Librarydb";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}

if (isset($_GET['query']) && strlen($_GET['query']) >= 4) {
    $query = '%' . $_GET['query'] . '%';
    
    $sql = "SELECT DISTINCT b.ISBN, 
           b.Title, 
           GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as authors, 
           b.Image_URL
    FROM Book b
    INNER JOIN wrote w ON b.isbn = w.isbn
    INNER JOIN author a ON a.author_id = w.author_id
    WHERE b.Title LIKE :query
    OR a.Name LIKE :query
    GROUP BY b.isbn, b.Title, b.Image_URL
    ORDER BY 
        CASE 
            WHEN b.Title LIKE :query THEN 1
            ELSE 2
        END,
        b.Title ASC
    LIMIT 10";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([':query' => $query]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}

$conn = null;
?> 