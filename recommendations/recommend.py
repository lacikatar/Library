import mysql.connector
from collections import defaultdict

def get_db_connection():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='LibraryDB'
    )

def get_user_interactions(cursor, member_id):
    query = """
        SELECT ISBN, 5 AS weight FROM user_book_status WHERE Member_ID = %s AND Status IN ('Read', 'Currently Reading')
        UNION
        SELECT ISBN, Rating AS weight FROM review WHERE Member_ID = %s AND Rating IS NOT NULL
        UNION
        SELECT ISBN, 2 AS weight FROM activity_log WHERE Member_ID = %s AND Action_Type IN ('Viewed', 'Reviewed', 'Borrowed')
        UNION
        SELECT ISBN, 1 AS weight FROM user_book_status WHERE Member_ID = %s AND Status = 'Want to Read'
    """
    cursor.execute(query, (member_id, member_id, member_id, member_id))
    interactions = cursor.fetchall()
    
    book_weights = defaultdict(float)
    for isbn, weight in interactions:
        book_weights[isbn] += float(weight)
    return book_weights

def get_similar_books(cursor, isbn):
    cursor.execute("""
        SELECT ISBN_2, Similarity_Score 
        FROM book_similarity 
        WHERE ISBN_1 = %s
    """, (isbn,))
    return cursor.fetchall()

def get_book_details(cursor, isbns):
    format_strings = ','.join(['%s'] * len(isbns))
    cursor.execute(f"""
        SELECT ISBN, Title, Image_URL 
        FROM book 
        WHERE ISBN IN ({format_strings})
    """, tuple(isbns))
    return cursor.fetchall()

def generate_recommendations(member_id, limit=10):
    conn = get_db_connection()
    cursor = conn.cursor()

    book_weights = get_user_interactions(cursor, member_id)
    already_seen = set(book_weights.keys())

    recommendation_scores = defaultdict(float)

    for isbn, weight in book_weights.items():
        for sim_isbn, sim_score in get_similar_books(cursor, isbn):
            if sim_isbn not in already_seen:
                recommendation_scores[sim_isbn] += sim_score * weight

    # Sort and pick top N
    sorted_recs = sorted(recommendation_scores.items(), key=lambda x: x[1], reverse=True)
    top_isbns = [isbn for isbn, _ in sorted_recs[:limit]]

    # Fetch book details
    if not top_isbns:
        return []

    book_details = get_book_details(cursor, top_isbns)

    # Clean up
    cursor.close()
    conn.close()

    return book_details

# Example usage
if __name__ == '__main__':
    member_id = 2  # Replace with real member ID
    recommendations = generate_recommendations(member_id)
    
    for book in recommendations:
        print(f"{book[1]} (ISBN: {book[0]}) - Cover: {book[2]}")
