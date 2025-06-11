<?php
// Get current year for copyright
$currentYear = date('Y');
?>

<footer class="footer mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5 class="footer-heading">Laci's Library</h5>
                <p class="footer-text">
                    Thesis - Integration of Hybrid Recommendation Systems Into a Library Website.
                </p>
            </div>
            <div class="col-md-4">
                <h5 class="footer-heading">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="books.php">Books</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="recommendations.php">Recommendations</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>

                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="footer-heading">Contact</h5>
                <ul class="footer-contact">
                    <li><i class="bi bi-envelope"></i> tarlacyka26@gmail.com</li>
                    <li><i class="bi bi-telephone"></i> +40733577824</li>
                    <li><i class="bi bi-geo-alt"></i> Cluj-Napoca, Romania</li>
                </ul>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="row">
            <div class="col-md-6">
                <p class="footer-copyright">
                    &copy; <?php echo $currentYear; ?> Laci's Library. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <ul class="footer-social">
                    <li><a href="https://www.facebook.com/lacikatar"><i class="bi bi-facebook"></i></a></li>
                    <li><a href="https://www.instagram.com/lacikatar"><i class="bi bi-instagram"></i></a></li>
                    <li><a href="https://www.linkedin.com/in/lacikatar/"><i class="bi bi-linkedin"></i></a></li>
                    <li><a href="https://github.com/lacikatar"><i class="bi bi-github"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background-color: #8B7355;
    color: #F4EBE2;
    margin-top: auto;
}

.footer-heading {
    color: #fff;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.footer-text {
    color: #F4EBE2;
    font-size: 0.9rem;
    line-height: 1.6;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: #F4EBE2;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: #fff;
}

.footer-contact {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-contact li {
    color: #F4EBE2;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.footer-contact i {
    color: #fff;
}

.footer-divider {
    border-color: rgba(244, 235, 226, 0.2);
    margin: 1.5rem 0;
}

.footer-copyright {
    color: #F4EBE2;
    font-size: 0.9rem;
    margin: 0;
}

.footer-social {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.footer-social a {
    color: #F4EBE2;
    font-size: 1.2rem;
    transition: color 0.3s ease;
}

.footer-social a:hover {
    color: #fff;
}

/* Make sure the footer stays at the bottom */
body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
</style> 