from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from bs4 import BeautifulSoup
import time
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import json
import sys  # Add this import at the top

def scrape_goodreads(url):
    options = webdriver.ChromeOptions()
  #  options.add_argument("--headless")  # Comment out headless mode for testing
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)
    driver.get(url)
    
    wait = WebDriverWait(driver, 30)
    
    try:
        more_details_button = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, "button[aria-label='Book details and editions']")
        ))
        
        driver.execute_script("arguments[0].scrollIntoView(true);", more_details_button)
        time.sleep(2)
        
        more_details_button.click()
        
        # Wait for both sections to load
        wait.until(EC.presence_of_element_located((By.CLASS_NAME, "EditionDetails")))
        wait.until(EC.presence_of_element_located((By.CLASS_NAME, "FeaturedDetails")))
        time.sleep(2)
        
    except (TimeoutException, NoSuchElementException) as e:
        print(f"Failed to load book details: {str(e)}")
    
    soup = BeautifulSoup(driver.page_source, 'html.parser')
    
    # Get image URL
    image_url = None
    image_element = soup.find("img", class_="ResponsiveImage")
    if image_element:
        image_url = image_element.get('src')
    
    title = soup.find("h1", class_="Text__title1").text.strip() if soup.find("h1", class_="Text__title1") else None
    authors = []
    author_elements = soup.find_all("span", class_="ContributorLink__name")
    if author_elements:
        seen_authors = set()  # Keep track of authors we've already seen
        for author_elem in author_elements:
            author_name = author_elem.text.strip()
            # Skip if author has (translator) or any other role in parentheses
            # and skip if we've already seen this author
            if '(' not in author_name and author_name not in seen_authors:
                authors.append(author_name)
                seen_authors.add(author_name)
    
    # Join authors with commas for the result
    author_string = ", ".join(authors)
    
    series_tag = soup.find("h3", class_="Text Text__title3 Text__italic Text__regular Text__subdued")
    series = series_tag.a.text.split("#")[0].strip() if series_tag and series_tag.a else "Standalone"
    
    # Get first published year from Featured Details
    release_year = None
    publication_info = soup.find("p", {"data-testid": "publicationInfo"})
    if publication_info:
        text = publication_info.text.strip()
        if "First published" in text:
            try:
                release_year = int(text.split()[-1])
            except ValueError:
                print(f"Could not parse year from: {text}")
    
    details_section = soup.find("div", class_="EditionDetails")
    isbn = None
    publisher = None
    categories = []
    page_nr = None
    
    if details_section:
        # Extract ISBN-13
        isbn_tag = details_section.find("dt", string=lambda x: x and "ISBN" in x)
        if isbn_tag:
            isbn_value = isbn_tag.find_next("dd").find("div", class_="TruncatedContent__text TruncatedContent__text--small").text.strip()
            isbn_parts = isbn_value.split()
            for part in isbn_parts:
                if len(part) == 13 and part.isdigit():  # Ensure it's a 13-digit number
                    isbn = part
                    break
        
        # Extract Publisher
        published_tag = details_section.find("dt", string=lambda x: x and "Published" in x)
        if published_tag:
            published_value = published_tag.find_next("dd").find("div", class_="TruncatedContent__text TruncatedContent__text--small").text.strip()
            try:
                publisher_part = published_value.split(" by ")[-1]
                publisher = publisher_part.strip()
            except ValueError:
                publisher = None
        
        # Extract Page Count
        pages_tag = details_section.find("dt", string=lambda x: x and "Format" in x)
        if pages_tag:
            pages_value = pages_tag.find_next("dd").find("div", class_="TruncatedContent__text TruncatedContent__text--small").text.strip()
            page_nr_parts = pages_value.split()
            for part in page_nr_parts:
                if part.isdigit():  # Look for numeric value indicating page count
                    page_nr = int(part)
                    break
    
    # Extract categories/genres
    categories = [tag.text for tag in soup.find_all("a", class_="Button") if "genres" in tag.get("href", "")]
    description = ""
    desc_container = soup.find("div", {"data-testid": "description"})
    if desc_container:
        content_container = desc_container.find("div", {"data-testid": "contentContainer"})
        if content_container:
            # Get the formatted span
            formatted_span = content_container.find("span", class_="Formatted")
            if formatted_span:
                # Get all text elements, including <br> tags
                description_parts = []
                for element in formatted_span.children:
                    if element.name == 'br':
                        description_parts.append(" ")  # Replace <br> with space
                    elif element.name == 'i' or element.name == 'b':
                        description_parts.append(element.text.strip())
                    elif isinstance(element, str):
                        description_parts.append(element.strip())
                
                # Join all parts and clean up extra spaces
                description = ' '.join(part for part in description_parts if part)
                description = ' '.join(description.split())  # Clean up multiple spaces
    
    result = {
        "Title": title,
        "Author": author_string,  # Store the comma-separated string
        "Series": series,
        "ISBN": isbn,
        "Publisher": publisher,
        "Release Year": release_year,
        "Categories": categories,
        "Page Count": page_nr,
        "Image_URL": image_url,
         "Description": description
    }
    
    driver.quit()
    return result

if __name__ == "__main__":
    # Check if URL is provided as command line argument
    if len(sys.argv) < 2:
        print("Please provide a Goodreads URL as an argument.")
        print("Usage: python scraper.py <goodreads_url>")
        sys.exit(1)
    
    # Get the URL from command line argument
    url = sys.argv[1]
    
    try:
        # Scrape the book data
        data = scrape_goodreads(url)
        
        # Save to file
        with open('book_data.txt', 'a', encoding='utf-8') as f:
            f.write(f"\n{'-'*50}\n")
            for key, value in data.items():
                if key == 'Authors':  # Special handling for Authors list
                    f.write(f"Author: {', '.join(value)}\n")  # Write as comma-separated string
                else:
                    f.write(f"{key}: {value}\n")
        
        print(f"\nBook data has been scraped and saved to book_data.txt")
        
    except Exception as e:
        print(f"An error occurred: {str(e)}")
        sys.exit(1)
