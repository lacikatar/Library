<?php 
session_start();
$host = "localhost";
$username = "root";  
$password = "lacika";      
$database = "Librarydb";


try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = sha1( $_POST['password']);
    $confirm_password = $_POST['confirm_password'];
    

    $sql="INSERT INTO Member (name, username, email, password) VALUES (:name, :username, :email, :password)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':username' => $username,
        ':email' => $email,
        ':password' => $password
    ]);


$memberID = $conn->lastInsertId();


$defaultLists = [
    'Currently Reading',
    'Read',
    'Want to Read',
    'DNF'
];

foreach ($defaultLists as $listName) {
    $listSql = "INSERT INTO reading_list (Member_ID, Name) VALUES (:member_id, :name)";
    $listStmt = $conn->prepare($listSql);
    $listStmt->execute([
        ':member_id' => $memberID,
        ':name' => $listName
    ]);
}
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Laci's Library</title>
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
        .card {
            background-color: #F4EBE2;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="text-center">Register</h4>
                </div>
                <div class="card-body">
                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="bi bi-person-badge me-2"></i>Name
                            </label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person me-2"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-2"></i>Email address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-2"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="bi bi-lock-fill me-2"></i>Confirm Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" style="background-color: #8B7355; border-color: #8B7355;">
                            <i class="bi bi-person-plus me-2"></i>Register
                        </button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php" style="color: #8B7355;"><i class="bi bi-box-arrow-in-right me-1" style="color: #8B7355;"></i>Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 