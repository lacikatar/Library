<?php

session_start();


exec("python3 ./hybrid_recommendations.py  > /dev/null 2>&1 &");
   
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laci's Library</title>
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
        .welcome-text.navbar-welcome {
            color: rgba(255,255,255,0.9);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background-color: rgba(255,255,255,0.1);
            margin-right: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .welcome-text.navbar-welcome i {
            font-size: 1.2rem;
        }
        .welcome-text.page-welcome {
            color: #5C4033;
            text-align: center;
            margin-top: 200px;
            font-family: 'Georgia', serif;
        }
        .welcome-text.page-welcome h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #2C3E50;
        }
        .welcome-text.page-welcome p {
            font-size: 1.5rem;
            color: #7F8C8D;
        }
        .btn-login, .btn-register {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-login i, .btn-register i {
            font-size: 1.2rem;
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
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="welcome-text page-welcome">
        <h1>Welcome to Laci's Library</h1>
       <!-- <p class="lead"></p>-->
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 