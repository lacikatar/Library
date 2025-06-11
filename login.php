<?php 
session_start(); // Add session start at the beginning

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
   
    $loginUsername = $_POST['username'];
   
    $loginPassword =sha1($_POST['password']);
   
    

    $sql="SELECT Member_ID, username FROM Member WHERE username = :username AND password = :password";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        
        ':username' => $loginUsername,
       
        ':password' => $loginPassword

  ]);

  if($stmt->rowCount() > 0){
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user_id'] = $user['Member_ID'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = 'member';
    header("Location: index.php"); // Redirect to home page
  //  $_SESSION['Member_ID']=$user['username'];
    exit();
  }else{
    $sql1="SELECT Work_ID, username FROM Worker WHERE username = :username AND password = :password";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([
        ':username' => $loginUsername,
        ':password' => $loginPassword
    ]);
    if($stmt1->rowCount() > 0)
    {
        $user = $stmt1->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $user['Work_ID'];
        $_SESSION['username'] =htmlspecialchars( $user['username']);
        $_SESSION['user_type'] = 'worker';
        header("Location: index.php"); // Redirect to home page
        exit();
    }
    else
    {
        $error_message = "Invalid username or password";
    }
  }

// Get the last inserted ID
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Alexandria's Haven</title>
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
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="text-center">Login</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form action="login.php" method="POST">
                       
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person me-2"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-2"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                       
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php"><i class="bi bi-person-plus me-1"></i>Register here</a></p>
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