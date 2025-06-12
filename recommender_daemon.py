import time
import subprocess
import mysql.connector
from datetime import datetime
import logging

# Set up logging
logging.basicConfig(
    filename='recommender_daemon.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

# Database config
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'lacika',
    'database': 'Librarydb'
}

# Paths to scripts
PYTHON_PATH = r'C:\Users\tarla\AppData\Local\Programs\Python\Python310\python.exe'
COLLAB_SCRIPT = r'D:\xampp\htdocs\Library\collaborative_filtering.py'
HYBRID_SCRIPT = r'D:\xampp\htdocs\Library\hybrid_recommendations.py'
SIMILARITY_SCRIPT = r'D:\xampp\htdocs\Library\book_similarity.py'

# Track last seen IDs
last_activity_id = None
last_book_isbn = None

def get_last_activity_id():
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        cursor.execute("SELECT MAX(Activity_ID) FROM activity_log")
        result = cursor.fetchone()[0]
        cursor.close()
        conn.close()
        return result
    except Exception as e:
        logging.error(f"Failed to fetch latest activity ID: {str(e)}")
        return None

def get_last_book_isbn():
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        cursor.execute("SELECT MAX(ISBN) FROM book")
        result = cursor.fetchone()[0]
        cursor.close()
        conn.close()
        return result
    except Exception as e:
        logging.error(f"Failed to fetch latest book ISBN: {str(e)}")
        return None

def run_script(script_path):
    try:
        logging.info(f"Running: {script_path}")
        subprocess.run([PYTHON_PATH, script_path], check=True)
        logging.info(f"Finished: {script_path}")
    except subprocess.CalledProcessError as e:
        logging.error(f"Script {script_path} exited with error: {e}")
    except Exception as e:
        logging.error(f"Failed to run {script_path}: {str(e)}")

def trigger_script_sequence(scripts):
    for script in scripts:
        run_script(script)

def main():
    global last_activity_id, last_book_isbn

    logging.info("Starting recommender daemon...")
    last_activity_id = get_last_activity_id()
    last_book_isbn = get_last_book_isbn()

    while True:
        try:
            new_activity_id = get_last_activity_id()
            new_book_isbn = get_last_book_isbn()

            # Trigger collab + hybrid if activity changed
            if new_activity_id and new_activity_id != last_activity_id:
                logging.info("New user activity detected.")
                trigger_script_sequence([COLLAB_SCRIPT, HYBRID_SCRIPT])
                last_activity_id = new_activity_id

            # Trigger similarity if a new book was added
            if new_book_isbn and new_book_isbn != last_book_isbn:
                logging.info("New book added.")
                run_script(SIMILARITY_SCRIPT)
                last_book_isbn = new_book_isbn

            time.sleep(30)  # Wait 2 minutes

        except Exception as e:
            logging.error(f"Daemon loop error: {str(e)}")
            time.sleep(120)

if __name__ == "__main__":
    main()
