import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
import mysql.connector
from datetime import datetime, timedelta
import logging

# Set up logging
logging.basicConfig(
    filename='hybrid_recommendations.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

class HybridRecommender:
    def __init__(self, host="localhost", user="root", password="lacika", database="Librarydb"):
        self.db_config = {
            'host': host,
            'user': user,
            'password': password,
            'database': database
        }
        self.weights = {
            'Viewed': 1,
            'Borrowed': 3,
            'Reviewed': 4,
            'Added to List': 2
        }
        # Weight for hybrid scoring (0.6 collaborative, 0.4 content-based)
        self.collaborative_weight = 0.6
        self.content_weight = 0.4

    def normalize(self, scores):
        if not scores:
            return {}
        values = list(scores.values())
        min_val, max_val = min(values), max(values)
        if max_val == min_val:
            return {k: 0 for k in scores}
        return {k: (v - min_val) / (max_val - min_val) for k, v in scores.items()}

    def get_user_interactions(self):
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)

            cursor.execute("""
                SELECT Member_ID, ISBN, Action_Type, Timestamp
                FROM activity_log
                WHERE Timestamp > DATE_SUB(NOW(), INTERVAL 6 MONTH)
            """)
            activity_logs = cursor.fetchall()

            cursor.execute("""
                SELECT Member_ID, ISBN, Rating, Created_At as Timestamp
                FROM review
                WHERE Created_At > DATE_SUB(NOW(), INTERVAL 6 MONTH)
            """)
            reviews = cursor.fetchall()

            cursor.close()
            conn.close()

            activity_df = pd.DataFrame(activity_logs)
            reviews_df = pd.DataFrame(reviews)

            activity_df['weight'] = activity_df['Action_Type'].map(self.weights)

            interactions = []

            for _, row in activity_df.iterrows():
                interactions.append({
                    'Member_ID': row['Member_ID'],
                    'ISBN': row['ISBN'],
                    'weight': row['weight'],
                    'Timestamp': row['Timestamp']
                })

            for _, row in reviews_df.iterrows():
                interactions.append({
                    'Member_ID': row['Member_ID'],
                    'ISBN': row['ISBN'],
                    'weight': row['Rating'] * 2,
                    'Timestamp': row['Timestamp']
                })

            return pd.DataFrame(interactions)

        except Exception as e:
            logging.error(f"Error fetching user interactions: {str(e)}")
            raise

    def get_content_similarities(self, user_isbns):
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            isbn_str = ','.join([f"'{isbn}'" for isbn in user_isbns])
            cursor.execute(f"""
                SELECT ISBN_1, ISBN_2, Similarity_Score
                FROM book_similarity
                WHERE ISBN_1 IN ({isbn_str})
            """)
            similarities = cursor.fetchall()
            cursor.close()
            conn.close()
            return pd.DataFrame(similarities)
        except Exception as e:
            logging.error(f"Error fetching content similarities: {str(e)}")
            raise

    def get_book_details(self, isbn_list):
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            isbn_str = ','.join([f"'{isbn}'" for isbn in isbn_list])
            cursor.execute(f"""
                SELECT b.ISBN, b.Title,
                       GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as Authors,
                       GROUP_CONCAT(DISTINCT c.Name SEPARATOR ', ') as Categories
                FROM book b
                LEFT JOIN wrote w ON b.ISBN = w.ISBN
                LEFT JOIN author a ON w.Author_ID = a.Author_ID
                LEFT JOIN belongs bl ON b.ISBN = bl.ISBN
                LEFT JOIN category c ON bl.Category_ID = c.Category_ID
                WHERE b.ISBN IN ({isbn_str})
                GROUP BY b.ISBN, b.Title
            """)
            books = cursor.fetchall()
            cursor.close()
            conn.close()
            return {book['ISBN']: book for book in books}
        except Exception as e:
            logging.error(f"Error fetching book details: {str(e)}")
            raise

    def get_username(self, user_id):
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT Username FROM member WHERE Member_ID = %s", (user_id,))
            result = cursor.fetchone()
            cursor.close()
            conn.close()
            return result['Username'] if result else f"User {user_id}"
        except Exception as e:
            logging.error(f"Error fetching username: {str(e)}")
            return f"User {user_id}"

    def calculate_user_similarity(self, interactions_df):
        try:
            user_item_matrix = interactions_df.pivot_table(
                index='Member_ID',
                columns='ISBN',
                values='weight',
                fill_value=0
            )
            user_similarity = cosine_similarity(user_item_matrix)
            user_similarity_df = pd.DataFrame(
                user_similarity,
                index=user_item_matrix.index,
                columns=user_item_matrix.index
            )
            return user_similarity_df, user_item_matrix
        except Exception as e:
            logging.error(f"Error calculating user similarity: {str(e)}")
            raise
    def save_recommendations(self, user_id, recommendations):
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()

            # Clear old recommendations
            cursor.execute("DELETE FROM recommendations WHERE Member_ID = %s", (user_id,))

            # Insert new ones
            for isbn, score in recommendations:
                cursor.execute("""
                    INSERT INTO recommendations (Member_ID, ISBN, Score)
                    VALUES (%s, %s, %s)
                """, (user_id, isbn, round(score, 4)))

            conn.commit()
            cursor.close()
            conn.close()
        except Exception as e:
            logging.error(f"Error saving recommendations for user {user_id}: {str(e)}")
                
    def get_recommendations(self, user_id, n_recommendations=12):
        try:
            interactions_df = self.get_user_interactions()
            user_similarity_df, user_item_matrix = self.calculate_user_similarity(interactions_df)

            if user_id not in user_similarity_df.index:
                logging.warning(f"User {user_id} not found in similarity matrix")
                return []

            user_items = set(user_item_matrix.columns[user_item_matrix.loc[user_id] > 0])
            all_items = set(user_item_matrix.columns)
            candidate_items = all_items - user_items
            similar_users = user_similarity_df[user_id].sort_values(ascending=False)[1:11]

            cf_scores = {}
            for item in candidate_items:
                score = sum(similarity * user_item_matrix.loc[sim_user, item]
                            for sim_user, similarity in similar_users.items())
                cf_scores[item] = score

            content_similarities = self.get_content_similarities(list(user_items))
            content_scores = {}
            for item in candidate_items:
                sims = content_similarities[content_similarities['ISBN_2'] == item]['Similarity_Score']
                content_scores[item] = sims.mean() if not sims.empty else 0

            cf_scores = self.normalize(cf_scores)
            content_scores = self.normalize(content_scores)

            recommendations = []
            for item in candidate_items:
                hybrid_score = (self.collaborative_weight * cf_scores.get(item, 0) +
                                self.content_weight * content_scores.get(item, 0))
                recommendations.append((item, hybrid_score))

            recommendations.sort(key=lambda x: x[1], reverse=True)
            return recommendations[:n_recommendations]

        except Exception as e:
            logging.error(f"Error getting recommendations: {str(e)}")
            raise

    def print_recommendations(self):
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            cursor.execute("SELECT Member_ID FROM member")
            users = cursor.fetchall()

            for (user_id,) in users:
                username = self.get_username(user_id)
                print(f"\n{'='*100}")
                print(f"Hybrid Recommendations for {username}:")
                print(f"{'='*100}")
                recommendations = self.get_recommendations(user_id)
                if not recommendations:
                    print("No recommendations available.")
                    continue

                isbn_list = [rec[0] for rec in recommendations]
                book_details = self.get_book_details(isbn_list)

                for i, (isbn, score) in enumerate(recommendations, 1):
                    book = book_details.get(isbn, {
                        'Title': 'Unknown',
                        'Authors': 'Unknown',
                        'Categories': 'Unknown'
                    })
                    print(f"{i}. {book['Title']} by {book['Authors']}")
                    print(f"   Categories: {book['Categories']}")
                    print(f"   Score: {score:.2f}\n")
                self.save_recommendations(user_id, recommendations)

           
            cursor.close()
            conn.close()
            logging.info("Ok")
            
        except Exception as e:
            logging.error(f"Error printing recommendations: {str(e)}")
            raise

if __name__ == "__main__":
    recommender = HybridRecommender()
    recommender.print_recommendations()

