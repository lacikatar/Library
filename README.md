# Laci's Library 📚

A modern, feature-rich library management system built with PHP and Python, featuring integrated hybrid recommendation algorithms for personalized book suggestions.

**Repository**: https://github.com/lacikatar/Library

---

## 📋 Overview

Laci's Library is a full-stack web application designed for managing a library's book collection, user borrowing records, and personalized reading recommendations. This project serves as a thesis project on integrating hybrid recommendation systems into library websites.

**Key Features:**
- 📖 Comprehensive book catalog with detailed metadata
- 👤 User authentication and account management
- 📋 Personal reading lists and tracking
- 🎯 Hybrid recommendation engine (collaborative filtering + content-based)
- 📊 Borrowing and waitlist management
- 🔍 Advanced book search functionality
- 📈 User activity logging and analytics

---

## 🛠 Tech Stack

| Technology | Percentage | Purpose |
|-----------|-----------|---------|
| **PHP** | 72.4% | Backend, server-side logic, database queries |
| **Python** | 27.6% | Machine learning, recommendation algorithms |
| **MySQL** | - | Database management |
| **HTML5** | - | Frontend markup |
| **CSS3** | - | Styling and layout |
| **Bootstrap 5.3.2** | - | Responsive UI framework |
| **Bootstrap Icons 1.7.2** | - | Icon library |
| **JavaScript** | - | Client-side interactivity |

---

## 🚀 Features in Detail

### 1. **User Management**
- User registration with validation
- Secure login/logout functionality
- Session-based authentication
- User profile management with welcome messages
- Password security using SHA-1 hashing

### 2. **Book Catalog**
- Extensive book database with:
  - ISBN identification
  - Author information (multiple authors per book)
  - Publisher details
  - Release years and page counts
  - Book series tracking
  - Categories/genres (multiple categories per book)
  - Book descriptions and images
  - Copy inventory management with condition tracking
  
### 3. **Borrowing System**
- Track book copies with condition status:
  - New
  - Good
  - Fair
  - Poor
- Borrowing request handling
- Status tracking:
  - Checked Out
  - Overdue
  - Returned
- Waitlist management for unavailable books
- Copy availability checking
- Shelf position tracking
- Borrowing history per user

### 4. **Personal Reading Lists**
- Create custom reading lists
- Pre-populated default lists:
  - Currently Reading
  - Read
  - Want to Read
  - DNF (Did Not Finish)
- Add/remove books from lists
- View reading list contents
- Track book count per list
- List management (create, delete, modify)

### 5. **Recommendation Engine**
- **Hybrid Approach** combining:
  - **Collaborative Filtering**: Recommendations based on similar user behaviors
  - **Content-Based**: Recommendations based on book characteristics and user preferences
- Real-time recommendation updates via Python backend (`hybrid_recommendations.py`)
- Score-based ranking system
- Personalized suggestions per user
- Multiple recommendation methods:
  - Hybrid recommendations
  - Collaborative filtering recommendations
  - Content-based recommendations
- Dynamic score adjustment based on user interactions
- Pagination support for browsing recommendations

### 6. **Search & Discovery**
- Real-time book search with autocomplete
- Search by title and author
- Advanced filtering capabilities
- AJAX-powered search results
- Minimum 4-character search query requirement
- Results ranked by relevance
- Display author information with search results

### 7. **Activity Tracking**
- User action logging:
  - Borrowed
  - Reviewed
  - Viewed
  - Added to list
- Activity-based recommendation boosts:
  - Borrowed: 1.5x score multiplier
  - Reviewed: 1.3x score multiplier
  - Other actions: 1.1x score multiplier
- Historical data for analytics
- Similarity matrix updates for content-based recommendations

### 8. **Navigation & UI**
- Responsive navbar with Bootstrap
- Mobile-friendly design
- Dynamic navigation based on authentication status
- Dropdown menus for logged-in users
- Search integration in navbar
- Professional footer with social links and copyright
- Warm, book-themed color scheme:
  - Primary: #8B7355 (Brown)
  - Secondary: #E6D5C3 (Beige)
  - Accent: #F4EBE2 (Light cream)

---

## 📁 Project Structure

```
Library/
├── README.md                    # Project documentation
├── index.php                    # Home page
├── books.php                    # Book catalog
├── book_details.php             # Individual book details page
├── login.php                    # User login page
├── register.php                 # User registration page
├── logout.php                   # User logout handler
├── navbar.php                   # Navigation bar component
├── footer.php                   # Footer component
├── functions.php                # PHP utility functions
├── search_books.php             # AJAX search endpoint
├── recommendations.php          # Recommendation display page
├── reading-lists.php            # User reading lists management
├── borrowed.php                 # User borrowed books tracker
├── hybrid_recommendations.py     # Python ML recommendation engine
├── activity/
│   └── log_activity.php         # Activity logging functions
├── img/
│   └── favicon.png              # Site favicon (book icon)
├── includes/
│   └── db_config.php            # Database configuration
└── css/
    └── style.css                # Custom styles (if separate)
```

---

## 🗄 Database Schema

### Tables Overview

**Members Table**
- Member_ID (Primary Key)
- Name
- Username (Unique)
- Email (Unique)
- Password (SHA-1 hashed)
- Join_Date
- Last_Login

**Books Table**
- ISBN (Primary Key)
- Title
- Description
- Publisher_ID (Foreign Key)
- Release_Year
- Page_Nr (number of pages)
- Series_ID (Foreign Key)
- Image_URL
- Created_Date

**Authors Table**
- Author_ID (Primary Key)
- Name
- Birth_Date (optional)
- Biography (optional)

**Wrote Table** (Books-Authors Junction)
- ISBN (Foreign Key)
- Author_ID (Foreign Key)

**Publishers Table**
- Publisher_ID (Primary Key)
- Name
- Country
- Founded_Year

**Categories Table**
- Category_ID (Primary Key)
- Name

**Belongs Table** (Books-Categories Junction)
- ISBN (Foreign Key)
- Category_ID (Foreign Key)

**Book_Series Table**
- Series_ID (Primary Key)
- Name
- Description

**Copy Table**
- Copy_ID (Primary Key)
- ISBN (Foreign Key)
- Copy_Condition (New, Good, Fair, Poor)
- Shelf_Position
- Added_Date

**Borrowing Table**
- Borrowing_ID (Primary Key)
- Member_ID (Foreign Key)
- Copy_ID (Foreign Key)
- Checkout_Date
- Due_Date
- Return_Date
- Status (Checked Out, Overdue, Returned)

**Waitlist Table**
- Waitlist_ID (Primary Key)
- Member_ID (Foreign Key)
- ISBN (Foreign Key)
- Join_Date
- Position

**Reading_List Table**
- List_ID (Primary Key)
- Member_ID (Foreign Key)
- Name
- Created_Date

**Reading_List_Book Table** (Reading Lists-Books Junction)
- List_ID (Foreign Key)
- ISBN (Foreign Key)

**Recommendations Table**
- Recommendation_ID (Primary Key)
- Member_ID (Foreign Key)
- ISBN (Foreign Key)
- Score (decimal)
- Method (Hybrid, Collaborative, Content-Based)
- Generated_Date

**Collab_Recommendations Table**
- Recommendation_ID (Primary Key)
- Member_ID (Foreign Key)
- ISBN (Foreign Key)
- Score (decimal)
- Generated_Date

**Activity_Log Table**
- Activity_ID (Primary Key)
- Member_ID (Foreign Key)
- ISBN (Foreign Key)
- Action_Type (Borrowed, Reviewed, Viewed, etc.)
- Activity_Date

**Book_Similarity Table**
- Similarity_ID (Primary Key)
- ISBN_1 (Foreign Key)
- ISBN_2 (Foreign Key)
- Similarity_Score (decimal)
- Last_Updated

---

## 🔧 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Python 3.6 or higher
- Apache or Nginx web server
- Composer (optional, for dependency management)
- pip (Python package manager)

### Step-by-Step Installation

#### 1. Clone the Repository
```bash
git clone https://github.com/lacikatar/Library.git
cd Library
```

#### 2. Set Up MySQL Database

Create the database:
```sql
CREATE DATABASE Librarydb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Librarydb;
```

Create all necessary tables:
```sql
-- Members Table
CREATE TABLE Member (
    Member_ID INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Authors Table
CREATE TABLE author (
    Author_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Birth_Date DATE,
    Biography LONGTEXT
);

-- Publishers Table
CREATE TABLE publisher (
    Publisher_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Country VARCHAR(50),
    Founded_Year INT
);

-- Book Series Table
CREATE TABLE book_series (
    Series_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Description LONGTEXT
);

-- Books Table
CREATE TABLE Book (
    ISBN VARCHAR(20) PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Description LONGTEXT,
    Publisher_ID INT,
    Release_Year INT,
    Page_Nr INT,
    Series_ID INT,
    Image_URL VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Publisher_ID) REFERENCES publisher(Publisher_ID),
    FOREIGN KEY (Series_ID) REFERENCES book_series(Series_ID)
);

-- Wrote Table (Books-Authors junction)
CREATE TABLE wrote (
    ISBN VARCHAR(20),
    Author_ID INT,
    PRIMARY KEY (ISBN, Author_ID),
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN),
    FOREIGN KEY (Author_ID) REFERENCES author(Author_ID)
);

-- Categories Table
CREATE TABLE category (
    Category_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL
);

-- Belongs Table (Books-Categories junction)
CREATE TABLE belongs (
    ISBN VARCHAR(20),
    Category_ID INT,
    PRIMARY KEY (ISBN, Category_ID),
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN),
    FOREIGN KEY (Category_ID) REFERENCES category(Category_ID)
);

-- Copy Table
CREATE TABLE copy (
    Copy_ID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20),
    Copy_Condition ENUM('New', 'Good', 'Fair', 'Poor') DEFAULT 'Good',
    Shelf_Position VARCHAR(50),
    Added_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN)
);

-- Borrowing Table
CREATE TABLE borrowing (
    Borrowing_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT,
    Copy_ID INT,
    Checkout_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Due_Date DATE,
    Return_Date DATE,
    Status ENUM('Checked Out', 'Overdue', 'Returned') DEFAULT 'Checked Out',
    FOREIGN KEY (Member_ID) REFERENCES Member(Member_ID),
    FOREIGN KEY (Copy_ID) REFERENCES copy(Copy_ID)
);

-- Waitlist Table
CREATE TABLE waitlist (
    Waitlist_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT,
    ISBN VARCHAR(20),
    Join_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Position INT,
    FOREIGN KEY (Member_ID) REFERENCES Member(Member_ID),
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN)
);

-- Reading List Table
CREATE TABLE reading_list (
    List_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT,
    Name VARCHAR(100) NOT NULL,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Member_ID) REFERENCES Member(Member_ID)
);

-- Reading List Books Junction Table
CREATE TABLE reading_list_book (
    List_ID INT,
    ISBN VARCHAR(20),
    Added_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (List_ID, ISBN),
    FOREIGN KEY (List_ID) REFERENCES reading_list(List_ID),
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN)
);

-- Recommendations Table
CREATE TABLE recommendations (
    Recommendation_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT,
    ISBN VARCHAR(20),
    Score DECIMAL(10, 4),
    Method VARCHAR(50),
    Generated_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Member_ID) REFERENCES Member(Member_ID),
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN)
);

-- Collaborative Recommendations Table
CREATE TABLE collab_recommendations (
    Recommendation_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT,
    ISBN VARCHAR(20),
    Score DECIMAL(10, 4),
    Generated_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Member_ID) REFERENCES Member(Member_ID),
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN)
);

-- Activity Log Table
CREATE TABLE activity_log (
    Activity_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT,
    ISBN VARCHAR(20),
    Action_Type VARCHAR(50),
    Activity_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Member_ID) REFERENCES Member(Member_ID),
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN)
);

-- Book Similarity Table
CREATE TABLE book_similarity (
    Similarity_ID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN_1 VARCHAR(20),
    ISBN_2 VARCHAR(20),
    Similarity_Score DECIMAL(10, 4),
    Last_Updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ISBN_1) REFERENCES Book(ISBN),
    FOREIGN KEY (ISBN_2) REFERENCES Book(ISBN)
);
```

#### 3. Configure PHP Database Connection

Update the database credentials in all PHP files. Look for connection strings like:
```php
$host = "localhost";
$username = "root";
$password = "your_password_here";
$database = "Librarydb";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
```

Replace `your_password_here` with your actual MySQL root password.

#### 4. Set Up Python Environment

Create a virtual environment (optional but recommended):
```bash
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
```

Install required Python packages:
```bash
pip install numpy pandas scikit-learn mysql-connector-python
```

#### 5. Configure Web Server

**Apache Configuration** (if using Apache):
```apache
<Directory /var/www/html/Library>
    AllowOverride All
    Require all granted
    Options +FollowSymLinks
</Directory>
```

Enable mod_rewrite if needed:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Nginx Configuration** (if using Nginx):
```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/Library;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

#### 6. Set File Permissions
```bash
chmod -R 755 Library
chmod -R 777 Library/uploads  # If you have uploads directory
```

#### 7. Start the Application

The application will be accessible at:
- Local: `http://localhost/Library`
- Or wherever you've configured your web server

The Python recommendation engine runs in the background via:
```php
exec("python3 ./hybrid_recommendations.py > /dev/null 2>&1 &");
```

---

## 👥 Usage Guide

### For New Users

#### 1. Create an Account
- Click on **Register** in the navbar
- Fill in your details:
  - Full Name
  - Username (unique)
  - Email
  - Password
- Click **Register**
- Four default reading lists are automatically created:
  - Currently Reading
  - Read
  - Want to Read
  - DNF

#### 2. Browse Books
- Click on **Books** in the navbar
- View the complete book catalog
- Click on any book to see detailed information

#### 3. Search for Books
- Use the search bar in the navbar
- Type at least 4 characters
- Results will appear as you type
- Books are ranked by relevance (title matches first)

#### 4. View Book Details
- Click on a book from the catalog or search results
- See:
  - Title, authors, and publisher
  - Description and cover image
  - Available copies and conditions
  - Borrowing status
  - Waitlist information (if applicable)

#### 5. Borrow Books
- On the book details page, click **Borrow**
- Select a copy if multiple are available
- Book is added to your **Borrowed Books** list

#### 6. Join Waitlist
- If no copies are available, click **Join Waitlist**
- You'll be notified when a copy becomes available
- View your position in the waitlist

#### 7. Manage Reading Lists
- Go to **My Library > Reading Lists**
- Create new custom lists
- Add books to lists
- Remove books from lists
- Delete lists

#### 8. Track Borrowed Books
- Go to **My Library > Borrowed Books**
- See all currently borrowed items
- View due dates
- Track overdue books

#### 9. Get Recommendations
- Go to **My Library > Recommendations**
- Choose recommendation method:
  - Hybrid (best overall)
  - Collaborative Filtering
  - Content-Based
- Browse personalized suggestions
- Click on recommendations to view details
- Recommendations improve as you interact with books

#### 10. Manage Account
- View your username in the navbar welcome message
- Click **Logout** to end your session

---

### For Administrators

#### Database Management
- Access MySQL directly or use phpMyAdmin
- Manage book catalog (add/edit/delete books)
- Manage user accounts
- Review activity logs and borrowing records
- Monitor recommendation system performance

#### Common Tasks

**Add a New Book:**
```sql
INSERT INTO Book (ISBN, Title, Description, Release_Year, Page_Nr, Image_URL)
VALUES ('978-0-123456-78-9', 'Sample Book', 'Description here', 2023, 300, 'url_to_image');

INSERT INTO wrote (ISBN, Author_ID) VALUES ('978-0-123456-78-9', 1);
INSERT INTO belongs (ISBN, Category_ID) VALUES ('978-0-123456-78-9', 1);
```

**Add Book Copies:**
```sql
INSERT INTO copy (ISBN, Copy_Condition, Shelf_Position)
VALUES ('978-0-123456-78-9', 'New', 'A1-1');
```

**View User Activity:**
```sql
SELECT * FROM activity_log WHERE Member_ID = 1 ORDER BY Activity_Date DESC;
```

**Check Recommendations:**
```sql
SELECT * FROM recommendations WHERE Member_ID = 1 ORDER BY Score DESC;
```

---

## 🤖 Recommendation System

### How It Works

The hybrid recommendation system combines three approaches:

#### 1. Collaborative Filtering
- **Principle**: Users with similar tastes like similar books
- **Algorithm**: Finds users with similar borrowing/rating patterns
- **Advantages**:
  - Discovers new genres you might like
  - Works well with large user bases
  - Learns from community patterns
- **Table**: `collab_recommendations`

#### 2. Content-Based Filtering
- **Principle**: Recommend books similar to ones you've already borrowed
- **Algorithm**: Analyzes book features (authors, categories, genres)
- **Advantages**:
  - Works with new users (no history needed)
  - Transparent (easy to explain)
  - No cold-start problem
- **Table**: `recommendations` with content-based scores

#### 3. Hybrid Approach
- **Principle**: Combines both methods for better accuracy
- **Algorithm**: Weighted average of collaborative and content-based scores
- **Advantages**:
  - Mitigates cold-start problem
  - More accurate than individual methods
  - More diverse recommendations
- **Table**: `recommendations`

### Score Calculation

**Activity-Based Scoring**:
```
Borrowed: Score × 1.5  (highest interest)
Reviewed: Score × 1.3  (good interest)
Viewed:   Score × 1.1  (some interest)
Added:    Score × 1.2  (plan to read)
```

**Similarity Scoring**:
```
Book Similarity = Shared_Attributes / Total_Attributes
- Shared authors: +0.3
- Shared categories: +0.3
- Shared series: +0.4
```

### Recommendation Updates

The Python backend (`hybrid_recommendations.py`):
- Runs automatically on page load
- Updates scores based on user activity
- Recalculates similarity matrices
- Generates new recommendations
- Operates in background (non-blocking)

---

## 🔐 Security Features

### Password Protection
- Passwords hashed using SHA-1
- Consider upgrading to bcrypt or Argon2 for production

### Session Management
- Session-based authentication
- Automatic session timeout
- Secure session storage

### Input Validation
- SQL Prepared Statements prevent SQL injection
- htmlspecialchars() for XSS prevention
- Input sanitization on registration/login

### Database Access
- PDO (PHP Data Objects) for secure database queries
- Error reporting disabled in production
- Credentials stored separately from code

### Recommendations for Production
1. **Upgrade password hashing**:
   ```php
   $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
   ```

2. **Enable HTTPS**: Use SSL/TLS certificates

3. **Add CSRF protection**: Implement token validation

4. **Environment variables**: Move credentials to .env file

5. **Rate limiting**: Prevent brute force attacks

6. **Regular backups**: Schedule automated database backups

---

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check MySQL is running: `sudo systemctl status mysql`
- Verify credentials in PHP files
- Ensure database `Librarydb` exists
- Check user permissions

**Recommendations Not Generating**
- Ensure Python is installed: `python3 --version`
- Check Python dependencies: `pip list`
- Review Python error logs
- Verify database tables exist

**Search Not Working**
- Ensure JavaScript is enabled
- Check browser console for errors
- Verify book data exists in database
- Test search with 4+ characters

**Session Expires Too Quickly**
- Increase session timeout in php.ini:
  ```ini
  session.gc_maxlifetime = 3600
  ```

**Slow Performance**
- Add database indexes on frequently queried columns
- Optimize Python recommendation algorithm
- Consider caching recommendations
- Increase server resources

### Debug Mode

Enable detailed error reporting (development only):
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## 📊 Performance Optimization

### Database Optimization
```sql
-- Add indexes for faster queries
CREATE INDEX idx_member_id ON borrowing(Member_ID);
CREATE INDEX idx_isbn ON borrowing(ISBN);
CREATE INDEX idx_activity_member ON activity_log(Member_ID);
CREATE INDEX idx_recommendations_member ON recommendations(Member_ID);
```

### PHP Optimization
- Use prepared statements (already implemented)
- Cache recommendation results
- Minimize database queries
- Use pagination for large result sets

### Front-End Optimization
- Minify CSS and JavaScript
- Optimize image sizes
- Implement lazy loading
- Use content delivery networks (CDN)

---

## 🚀 Future Enhancements

### Planned Features
1. **Advanced Analytics Dashboard**
   - User engagement metrics
   - Popular books statistics
   - Recommendation accuracy tracking

2. **Social Features**
   - User reviews and ratings
   - Social sharing
   - Book club functionality
   - User following/connections

3. **Mobile App**
   - Native mobile application
   - Push notifications for recommendations
   - Offline reading list access

4. **Enhanced Recommendations**
   - Deep learning neural networks
   - Real-time personalization
   - Context-aware suggestions
   - Trending book recommendations

5. **Admin Panel**
   - User-friendly interface
   - Database management tools
   - Analytics visualizations
   - Automated reporting

6. **Payment Integration**
   - Purchase e-books
   - Membership tiers
   - Digital lending

7. **Email Notifications**
   - Recommendation emails
   - Overdue reminders
   - Waitlist notifications
   - Digest emails

---

## 📚 Resources & References

### Documentation
- [PHP Official Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Python Documentation](https://docs.python.org/3/)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [scikit-learn Documentation](https://scikit-learn.org/stable/documentation.html)

### Books on Recommendation Systems
- "Recommender Systems Handbook" - Ricci, Rokach, Shapira
- "Machine Learning in Action" - Peter Harrington
- "Collaborative Filtering with Temporal Dynamics" - Koren, Y.

### Tools & Libraries Used
- **Python Libraries**:
  - NumPy - Numerical computing
  - Pandas - Data manipulation
  - scikit-learn - Machine learning algorithms
  - mysql-connector-python - Database connectivity

---

## 📝 License

This project was created as a thesis project on **Integration of Hybrid Recommendation Systems Into a Library Website**. All code is provided as-is for educational and research purposes.

---

## 👤 Author

**Laci Katar**

### Contact Information
- 📧 **Email**: tarlacyka26@gmail.com
- 📱 **Phone**: +40733577824
- 📍 **Location**: Cluj-Napoca, Romania

### Social Links
- 🔗 [GitHub](https://github.com/lacikatar)
- 💼 [LinkedIn](https://www.linkedin.com/in/lacikatar/)
- 📘 [Facebook](https://www.facebook.com/lacikatar)
- 📷 [Instagram](https://www.instagram.com/lacikatar)

---

## 🤝 Contributing

While this is a thesis project, contributions, suggestions, and feedback are welcome. Please feel free to:
- Report bugs and issues
- Suggest improvements
- Propose new features
- Submit pull requests

---

## ⭐ Acknowledgments

Special thanks to:
- University advisors and thesis committee
- MySQL and PHP communities
- Bootstrap and Bootstrap Icons teams
- scikit-learn and Python ML communities
- All users and testers

---

## 📅 Project Timeline

| Date | Milestone |
|------|-----------|
| May 25, 2025 | Repository created |
| June 2025 | Core features development |
| July 2, 2025 | Final updates and testing |
| Current | Active maintenance and improvements |

---

## 📞 Support & Questions

For questions, issues, or feedback regarding this project:
1. Check existing [GitHub Issues](https://github.com/lacikatar/Library/issues)
2. Create a new issue with detailed description
3. Contact via email: tarlacyka26@gmail.com
4. Connect on LinkedIn for professional inquiries

---

**Last Updated**: April 25, 2026

---

*"A well-managed library is a gateway to endless worlds of knowledge and imagination."*
