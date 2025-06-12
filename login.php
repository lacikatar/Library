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
    <title>Login - Laci's Library</title>
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
                       
                        <button type="submit" class="btn btn-primary w-100" style="background-color: #8B7355; border-color: #8B7355;">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php" style="color: #8B7355;"><i class="bi bi-person-plus me-1"></i>Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<br><br>


<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 