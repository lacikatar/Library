import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import mysql.connector

# Step 1: Connect to your DB
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="librarydb"
)

# Step 2: Get the necessary data
query = """
SELECT
    b.ISBN,
    b.Description,
    GROUP_CONCAT(DISTINCT t.Tag_Name) AS Tags,
    GROUP_CONCAT(DISTINCT c.Name) AS Categories
FROM book b
LEFT JOIN book_tags bt ON b.ISBN = bt.ISBN
LEFT JOIN tags t ON bt.Tag_ID = t.Tag_ID
LEFT JOIN belongs bl ON b.ISBN = bl.ISBN
LEFT JOIN category c ON bl.Category_ID = c.Category_ID
GROUP BY b.ISBN
"""

df = pd.read_sql(query, conn)
df.fillna('', inplace=True)

# Step 3: Combine fields into a single document
df['content'] = df['Description'] + ' ' + df['Tags'] + ' ' + df['Categories']

# Step 4: TF-IDF Vectorization
vectorizer = TfidfVectorizer(stop_words='english')
tfidf_matrix = vectorizer.fit_transform(df['content'])

# Step 5: Compute cosine similarity
cosine_sim = cosine_similarity(tfidf_matrix)

# Step 6: Prepare results
results = []
isbn_list = df['ISBN'].tolist()

for i in range(len(isbn_list)):
    for j in range(len(isbn_list)):
        if i != j:
            results.append({
                'ISBN_1': isbn_list[i],
                'ISBN_2': isbn_list[j],
                'Similarity_Score': round(float(cosine_sim[i][j]), 4)
            })

# Optional: filter top N similar books per ISBN
top_n = 10
filtered_results = []
from collections import defaultdict

similar_books = defaultdict(list)
for row in results:
    similar_books[row['ISBN_1']].append((row['ISBN_2'], row['Similarity_Score']))

for isbn, sims in similar_books.items():
    sims.sort(key=lambda x: x[1], reverse=True)
    for isbn2, score in sims[:top_n]:
        filtered_results.append((isbn, isbn2, score))


# Step 7: Insert or Update into DB
cursor = conn.cursor()

select_query = """
SELECT Similarity_Score FROM book_similarity 
WHERE ISBN_1 = %s AND ISBN_2 = %s
"""
insert_query = """
INSERT INTO book_similarity (ISBN_1, ISBN_2, Similarity_Score)
VALUES (%s, %s, %s)
"""
update_query = """
UPDATE book_similarity SET Similarity_Score = %s
WHERE ISBN_1 = %s AND ISBN_2 = %s
"""

for isbn1, isbn2, score in filtered_results:
    cursor.execute(select_query, (isbn1, isbn2))
    result = cursor.fetchone()
    
    if result:
        existing_score = round(result[0], 4)
        if existing_score != score:
            cursor.execute(update_query, (score, isbn1, isbn2))
    else:
        cursor.execute(insert_query, (isbn1, isbn2, score))

conn.commit()
cursor.close()
conn.close()
print("Book similarity data updated.")

conn.commit()

# Done
cursor.close()
conn.close()
print("Book similarity data generated and stored.")
