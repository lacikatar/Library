import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
import mysql.connector
from datetime import datetime, timedelta
import logging

# Set up logging
logging.basicConfig(
    filename='collaborative_filtering.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

class CollaborativeFiltering:
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
        
    def get_user_interactions(self):
        """Fetch user interactions from activity_log and reviews"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            
            # Get activity logs with weights
            cursor.execute("""
                SELECT 
                    Member_ID,
                    ISBN,
                    Action_Type,
                    Timestamp
                FROM activity_log
                WHERE Timestamp > DATE_SUB(NOW(), INTERVAL 6 MONTH)
            """)
            activity_logs = cursor.fetchall()
            
            # Get reviews with ratings
            cursor.execute("""
                SELECT 
                    Member_ID,
                    ISBN,
                    Rating,
                    Created_At as Timestamp
                FROM review
                WHERE Created_At > DATE_SUB(NOW(), INTERVAL 6 MONTH)
            """)
            reviews = cursor.fetchall()
            
            cursor.close()
            conn.close()
            
            # Convert to DataFrames
            activity_df = pd.DataFrame(activity_logs)
            reviews_df = pd.DataFrame(reviews)
            
            # Apply weights to activities
            activity_df['weight'] = activity_df['Action_Type'].map(self.weights)
            
            # Combine activities and reviews
            interactions = []
            
            # Add activities
            for _, row in activity_df.iterrows():
                interactions.append({
                    'Member_ID': row['Member_ID'],
                    'ISBN': row['ISBN'],
                    'weight': row['weight'],
                    'Timestamp': row['Timestamp']
                })
            
            # Add reviews (weight = rating * 2)
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
    
    def get_book_details(self, isbn_list):
        """Get book details for the given ISBNs"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            
            # Convert list to comma-separated string for SQL
            isbn_str = ','.join([f"'{isbn}'" for isbn in isbn_list])
            
            cursor.execute(f"""
                SELECT 
                    b.ISBN,
                    b.Title,
                    GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as Authors
                FROM book b
                LEFT JOIN wrote w ON b.ISBN = w.ISBN
                LEFT JOIN author a ON w.Author_ID = a.Author_ID
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
        """Get username for a given user ID"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            
            cursor.execute("""
                SELECT Username 
                FROM member 
                WHERE Member_ID = %s
            """, (user_id,))
            
            result = cursor.fetchone()
            cursor.close()
            conn.close()
            
            return result['Username'] if result else f"User {user_id}"
            
        except Exception as e:
            logging.error(f"Error fetching username: {str(e)}")
            return f"User {user_id}"
    
    def calculate_user_similarity(self, interactions_df):
        """Calculate user similarity matrix using cosine similarity"""
        try:
            # Create user-item matrix
            user_item_matrix = interactions_df.pivot_table(
                index='Member_ID',
                columns='ISBN',
                values='weight',
                fill_value=0
            )
            
            # Calculate cosine similarity
            user_similarity = cosine_similarity(user_item_matrix)
            
            # Convert to DataFrame
            user_similarity_df = pd.DataFrame(
                user_similarity,
                index=user_item_matrix.index,
                columns=user_item_matrix.index
            )
            
            return user_similarity_df, user_item_matrix
            
        except Exception as e:
            logging.error(f"Error calculating user similarity: {str(e)}")
            raise
    
    def get_recommendations(self, user_id, n_recommendations=10):
        """Get personalized recommendations for a user"""
        try:
            # Get interactions
            interactions_df = self.get_user_interactions()
            
            # Calculate similarities
            user_similarity_df, user_item_matrix = self.calculate_user_similarity(interactions_df)
            
            if user_id not in user_similarity_df.index:
                logging.warning(f"User {user_id} not found in similarity matrix")
                return []
            
            # Get similar users
            similar_users = user_similarity_df[user_id].sort_values(ascending=False)[1:11]
            
            # Get items the user hasn't interacted with
            user_items = set(user_item_matrix.columns[user_item_matrix.loc[user_id] > 0])
            all_items = set(user_item_matrix.columns)
            candidate_items = all_items - user_items
            
            # Calculate recommendation scores
            recommendations = []
            for item in candidate_items:
                score = 0
                for similar_user, similarity in similar_users.items():
                    score += similarity * user_item_matrix.loc[similar_user, item]
                recommendations.append((item, score))
            
            # Sort and return top recommendations
            recommendations.sort(key=lambda x: x[1], reverse=True)
            return recommendations[:n_recommendations]
            
        except Exception as e:
            logging.error(f"Error getting recommendations: {str(e)}")
            raise
    
    def print_recommendations(self):
        """Print recommendations for all users"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor()
            
            # Get all users
            cursor.execute("SELECT Member_ID FROM member")
            users = cursor.fetchall()
            
            for (user_id,) in users:
                username = self.get_username(user_id)
                print(f"\n{'='*80}")
                print(f"Recommendations for {username}:")
                print(f"{'='*80}")
                
                recommendations = self.get_recommendations(user_id)
                if not recommendations:
                    print("No recommendations available.")
                    continue
                
                # Get book details for recommendations
                isbn_list = [rec[0] for rec in recommendations]
                book_details = self.get_book_details(isbn_list)
                
                # Print recommendations
                for i, (isbn, score) in enumerate(recommendations, 1):
                    book = book_details.get(isbn, {'Title': 'Unknown', 'Authors': 'Unknown'})
                    print(f"{i}. {book['Title']} by {book['Authors']}")
                    print(f"   Score: {score:.2f}")
                    print()
            
            cursor.close()
            conn.close()
            
        except Exception as e:
            logging.error(f"Error printing recommendations: {str(e)}")
            raise

if __name__ == "__main__":
    cf = CollaborativeFiltering()
    cf.print_recommendations() 