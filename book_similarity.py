import mysql.connector
from mysql.connector import Error
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np
from datetime import datetime
import logging
import time
from collections import defaultdict

# Naplo
logging.basicConfig(
    filename='similarity_calculation.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def get_db_connection():
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",
            password="lacika",
            database="Librarydb"
        )
        return connection
    except Error as e:
        logging.error(f"Error connecting to MySQL: {e}")
        raise
#adatok lekerese
def get_books_data(connection):
    try:
        cursor = connection.cursor(dictionary=True)
        query = """
            SELECT 
                b.ISBN,
                b.Title,
                b.Description,
                b.Series_ID,
                GROUP_CONCAT(DISTINCT c.Name) AS Categories,
                GROUP_CONCAT(DISTINCT t.Tag_Name) AS Tags,
                GROUP_CONCAT(DISTINCT a.Name) AS Authors
            FROM Book b
            LEFT JOIN belongs bl ON b.ISBN = bl.ISBN
            LEFT JOIN category c ON bl.Category_ID = c.Category_ID
            LEFT JOIN book_tags bt ON b.ISBN = bt.ISBN
            LEFT JOIN tags t ON bt.Tag_ID = t.Tag_ID
            LEFT JOIN wrote w ON b.ISBN = w.ISBN
            LEFT JOIN author a ON w.Author_ID = a.Author_ID
            GROUP BY b.ISBN
        """
        cursor.execute(query)
        return cursor.fetchall()
    except Error as e:
        logging.error(f"Error fetching books data: {e}")
        raise

#leiras szamitasa
def calculate_text_similarity(books):
    text_blob = [
        f"{book['Title']} {book['Description']} {book['Authors']} {book['Tags']} {book['Categories']}"
        for book in books
    ]
    vectorizer = TfidfVectorizer(stop_words='english')
    tfidf_matrix = vectorizer.fit_transform(text_blob)
    return cosine_similarity(tfidf_matrix)

def calculate_similarities(books):
    n_books = len(books)
    similarities = []
    text_similarities = calculate_text_similarity(books)

    for j in range(n_books):
        book1 = books[j]
        sim_list = []

        for k in range(n_books):
            if j == k:
                continue
            book2 = books[k]

            
            series_sim = 0.1 if book1['Series_ID'] and book1['Series_ID'] == book2['Series_ID'] else 0

          
            desc_sim = text_similarities[j][k] * 0.3

            
            authors1 = set(book1['Authors'].split(',')) if book1['Authors'] else set()
            authors2 = set(book2['Authors'].split(',')) if book2['Authors'] else set()
            author_sim = 0.2 * len(authors1.intersection(authors2)) / max(len(authors1), len(authors2)) if authors1 and authors2 else 0

      
            cats1 = set(book1['Categories'].split(',')) if book1['Categories'] else set()
            cats2 = set(book2['Categories'].split(',')) if book2['Categories'] else set()
            cat_sim = 0.2 * len(cats1.intersection(cats2)) / max(len(cats1), len(cats2)) if cats1 and cats2 else 0

            
            tags1 = set(book1['Tags'].split(',')) if book1['Tags'] else set()
            tags2 = set(book2['Tags'].split(',')) if book2['Tags'] else set()
            tag_sim = 0.2 * len(tags1.intersection(tags2)) / max(len(tags1), len(tags2)) if tags1 and tags2 else 0

            total_sim = series_sim + desc_sim + author_sim + cat_sim + tag_sim

            
            sim_list.append((book2['ISBN'], total_sim))

       
        sim_list.sort(key=lambda x: x[1], reverse=True)
        for isbn2, score in sim_list[:10]:
            similarities.append((book1['ISBN'], isbn2, round(score, 4)))

    return similarities

def store_similarities(connection, similarities):
    try:
        cursor = connection.cursor()

       
        cursor.execute("TRUNCATE TABLE book_similarity")

       
        batch_size = 1000
        for i in range(0, len(similarities), batch_size):
            batch = similarities[i:i + batch_size]
            query = """
                INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
                VALUES (%s, %s, %s)
            """
            cursor.executemany(query, batch)
            connection.commit()
            logging.info(f"Stored batch {i//batch_size + 1} of {(len(similarities) + batch_size - 1)//batch_size}")

    except Error as e:
        logging.error(f"Error storing similarities: {e}")
        raise

def main():
    start_time = time.time()
    logging.info("Starting similarity calculation")

    try:
        connection = get_db_connection()
        logging.info("Database connection established")

        books = get_books_data(connection)
        logging.info(f"Retrieved {len(books)} books")

        similarities = calculate_similarities(books)
        logging.info(f"Calculated {len(similarities)} similarity pairs")

        store_similarities(connection, similarities)
        logging.info("Similarities stored in database")

        connection.close()
        end_time = time.time()
        logging.info(f"Process completed in {end_time - start_time:.2f} seconds")

    except Exception as e:
        logging.error(f"Error in main process: {e}")
        raise

if __name__ == "__main__":
    main()
