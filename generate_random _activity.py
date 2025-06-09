import mysql.connector
from faker import Faker
import random
import hashlib
from datetime import datetime, timedelta

fake = Faker()
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="lacika",
    database="Librarydb"
)
cursor = conn.cursor()

# Insert fake users with SHA1-hashed passwords
num_users = 10
for _ in range(num_users):
    name = fake.name()
    email = fake.email()
    username = fake.user_name()
    raw_password = 'test123'
    hashed_password = hashlib.sha1(raw_password.encode()).hexdigest()
    cursor.execute("""
        INSERT INTO member (Name, Email, Username, Password)
        VALUES (%s, %s, %s, %s)
    """, (name, email, username, hashed_password))

# Get newly added member_ids
cursor.execute("SELECT Member_ID FROM member ORDER BY Member_ID DESC LIMIT %s", (num_users,))
member_ids = [row[0] for row in cursor.fetchall()]

# Get random books
cursor.execute("SELECT ISBN FROM book")
isbns = [row[0] for row in cursor.fetchall()]

# Create a shared pool of books
shared_books = random.sample(isbns, k=min(8, len(isbns)))

# Insert overlapping activity logs, read status, and reviews
actions = ['Viewed', 'Borrowed', 'Reviewed', 'Added to List']
read_statuses = ['Read', 'Currently Reading', 'Want to Read']

for member_id in member_ids:
    user_books = random.sample(shared_books, k=min(5, len(shared_books)))

    for isbn in user_books:
        # Insert random activity with current timestamp
        action = random.choice(actions)
        cursor.execute("""
            INSERT INTO activity_log (Member_ID, ISBN, Action_Type, Timestamp)
            VALUES (%s, %s, %s, %s)
        """, (member_id, isbn, action, datetime.now() - timedelta(days=random.randint(0, 180))))

        # Insert reading status
        status = random.choice(read_statuses)
        cursor.execute("""
            INSERT INTO user_book_status (Member_ID, ISBN, Status)
            VALUES (%s, %s, %s)
        """, (member_id, isbn, status))

        # Insert review only if book was read
        if status == 'Read':
            rating = random.randint(1, 5)
            comment = fake.sentence()
            cursor.execute("""
                INSERT INTO review (Member_ID, ISBN, Rating, Comment, Created_At)
                VALUES (%s, %s, %s, %s, %s)
            """, (member_id, isbn, rating, comment, datetime.now() - timedelta(days=random.randint(0, 180))))

conn.commit()
cursor.close()
conn.close()
