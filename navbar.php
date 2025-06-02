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

<style>
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
.navbar-nav {
    align-items: center;
}
.d-flex.align-items-center {
    align-items: center !important;
}
</style>

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
</script> 