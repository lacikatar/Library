import ast  
import random  
import os


def read_books_from_txt(file_path):
    books = []
    with open(file_path, 'r', encoding='utf-8') as file:
        book_data = {}
        
        
        for line in file:
            line = line.strip()
            
            if line.startswith('Title:'):
                book_data['Title'] = line.split(': ')[1].strip()
            elif line.startswith('Author:'):
                book_data['Author'] = line.split(': ')[1].strip()
            elif line.startswith('Series:'):
                book_data['Series'] = line.split(': ')[1].strip()
            elif line.startswith('ISBN:'):
                book_data['ISBN'] = line.split(': ')[1].strip()
            elif line.startswith('Publisher:'):
                book_data['Publisher'] = line.split(': ')[1].strip()
            elif line.startswith('Release Year:'):
                book_data['Release Year'] = int(line.split(': ')[1].strip())
            elif line.startswith('Categories:'):
               
                book_data['Categories'] = ast.literal_eval(line.split(': ')[1].strip())
            elif line.startswith('Tags:'):
                 book_data['Tags'] = ast.literal_eval(line.split(': ')[1].strip())
            elif line.startswith('Page Count:'):
                book_data['Page Count'] = int(line.split(': ')[1].strip())
            elif line.startswith('Image_URL:'):
                book_data['Image_URL'] = line.split(': ')[1].strip()
            elif line.startswith('Description:'):
                book_data['Description'] = line.split(': ')[1].strip()
            
            
            if line == '' and book_data:
                books.append(book_data)
                book_data = {}
        
       
        if book_data:
            books.append(book_data)
    
    return books


def escape_sql_string(text):
    if text is None:
        return None
    return text.replace("'", "''")


def generate_book_insert(books):
    book_sql = []
    for book in books:
        
        title = escape_sql_string(book['Title'])
        publisher = escape_sql_string(book.get('Publisher', None)) 
        image_url = escape_sql_string(book.get('Image_URL', None))
        description = escape_sql_string(book.get('Description', None))
        
        sql = f"INSERT INTO book (isbn, Title, Publisher_ID, Description, Release_year, Page_nr, Series_ID, Image_URL) "
        series_value = 'NULL' if book['Series'] == 'Standalone' else 'NULL'
        
        
        image_value = 'NULL' if not image_url else f"'{image_url}'"
        description_value = 'NULL' if not description else f"'{description}'"
        
        
        publisher_subquery = f"(SELECT Publisher_ID FROM publisher WHERE Name = '{publisher}')" if publisher else 'NULL'
        
        sql += f"VALUES ('{book['ISBN']}','{title}',{publisher_subquery},{description_value},{book['Release Year']},{book['Page Count']},{series_value},{image_value});"
        book_sql.append(sql)
    return "\n".join(book_sql)


def generate_author_insert(books):
    authors = set()
    for book in books:
        book_authors = [author.strip() for author in book['Author'].split(',')]
        authors.update(book_authors)
    
    author_sql = []
    for author in authors:
        escaped_author = escape_sql_string(author)
        sql = f"INSERT INTO author (name) "
        sql += f"SELECT '{escaped_author}' AS name WHERE NOT EXISTS (SELECT 1 FROM author a WHERE a.name = '{escaped_author}');"
        author_sql.append(sql)
    return "\n".join(author_sql)


def generate_category_insert(books):
    categories = set()
    for book in books:
        categories.update(book['Categories'])
    category_sql = []
    for category in categories:
        escaped_category = escape_sql_string(category)
        sql = f"INSERT INTO category (name) "
        sql += f"SELECT '{escaped_category}' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = '{escaped_category}');"
        category_sql.append(sql)
    return "\n".join(category_sql)

def generate_belongs_insert(books):
    belongs_sql = []
    for book in books:
        categories = book['Categories']
 
        quoted_categories = [f"'{escape_sql_string(category)}'" for category in categories]
        category_list = ", ".join(quoted_categories)
        escaped_title = escape_sql_string(book['Title'])
        
        category_sql = f"INSERT INTO belongs (isbn, category_id) "
        category_sql += f"SELECT b.isbn, c.category_id FROM book b JOIN category c ON c.name IN ({category_list}) "
        category_sql += f"WHERE b.title = '{escaped_title}';"
        belongs_sql.append(category_sql)
    return "\n".join(belongs_sql)

  
def generate_book_tags_insert(books):
    tag_sql = []
    for book in books:
        if 'Tags' not in book:
            continue
        tags = book['Tags']
        quoted_tags = [f"'{escape_sql_string(tag)}'" for tag in tags]
        tag_list = ", ".join(quoted_tags)
        escaped_title = escape_sql_string(book['Title'])

        sql = f"INSERT INTO book_tags (isbn, tag_id) "
        sql += f"SELECT b.isbn, t.tag_id FROM book b JOIN tags t ON t.Tag_Name IN ({tag_list}) "
        sql += f"WHERE b.title = '{escaped_title}';"
        tag_sql.append(sql)
    return "\n".join(tag_sql)


def generate_tag_insert(books):
    tags = set()
    for book in books:
        if 'Tags' in book:
            tags.update(book['Tags'])
    tag_sql = []
    for tag in tags:
        escaped_tag = escape_sql_string(tag)
        sql = f"INSERT INTO tags (Tag_Name) "
        sql += f"SELECT '{escaped_tag}' AS Tag_Name WHERE NOT EXISTS (SELECT 1 FROM tags t WHERE t.Tag_Name = '{escaped_tag}');"
        tag_sql.append(sql)
    return "\n".join(tag_sql)


def generate_publisher_insert(books):
    publishers = set()
    for book in books:
        if 'Publisher' in book:
            publishers.add(book['Publisher'])
    
    publisher_sql = []
    for publisher in publishers:
        escaped_publisher = escape_sql_string(publisher)
        sql = f"INSERT INTO publisher (Name) "
        sql += f"SELECT '{escaped_publisher}' AS Name WHERE NOT EXISTS (SELECT 1 FROM publisher p WHERE p.Name = '{escaped_publisher}');"
        publisher_sql.append(sql)
    return "\n".join(publisher_sql)

def generate_series_insert(books):
    series = set(book['Series'] for book in books if book['Series'] != 'Standalone')
    series_sql = []
    for serie in series:
        escaped_serie = escape_sql_string(serie)
        sql = f"INSERT INTO book_series (name) "
        sql += f"SELECT '{escaped_serie}' AS name WHERE NOT EXISTS (SELECT 1 FROM book_series bs WHERE bs.name = '{escaped_serie}');"
        series_sql.append(sql)
    return "\n".join(series_sql)


def generate_book_update(books):
    update_sql = []
    for book in books:
        if book['Series'] != 'Standalone':
            escaped_series = escape_sql_string(book['Series'])
            escaped_title = escape_sql_string(book['Title'])
            update_sql.append(f"UPDATE book SET series_id = (SELECT series_id FROM book_series WHERE name = '{escaped_series}') WHERE title = '{escaped_title}';")
    return "\n".join(update_sql)


def generate_wrote_insert(books):
    wrote_sql = []
    for book in books:
      
        book_authors = [author.strip() for author in book['Author'].split(',')]
        for author in book_authors:
            escaped_author = escape_sql_string(author)
            escaped_title = escape_sql_string(book['Title'])
            
            sql = f"INSERT INTO wrote (isbn, author_id) "
            sql += f"SELECT b.isbn, a.author_id "
            sql += f"FROM book b JOIN author a ON a.name = '{escaped_author}' "
            sql += f"WHERE b.title = '{escaped_title}';"
            wrote_sql.append(sql)
    return "\n".join(wrote_sql)


def generate_copy_insert(books):
    copy_sql = []
    conditions = ['Good', 'New', 'Fair']
    
    for book in books:
       
        shelf_pos = f"{random.randint(1,9)}-{random.randint(1,9)}"
        
        for condition in conditions:
            sql = f"INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) "
            sql += f"VALUES ('{condition}', '{shelf_pos}', '{book['ISBN']}');"
            copy_sql.append(sql)
    return "\n".join(copy_sql)

def generate_sql_from_txt(input_file_path, output_file_path='generated_sql.sql'):
   
    if os.path.exists(output_file_path):
        os.remove(output_file_path)
    
    books = read_books_from_txt(input_file_path)

    publisher_insert_sql = generate_publisher_insert(books)
    book_insert_sql = generate_book_insert(books)
    author_insert_sql = generate_author_insert(books)
    category_insert_sql = generate_category_insert(books)
    belongs_insert_sql = generate_belongs_insert(books)
    tag_insert_sql = generate_tag_insert(books)
    book_tags_insert_sql = generate_book_tags_insert(books)
    series_insert_sql = generate_series_insert(books)
    book_update_sql = generate_book_update(books)
    wrote_insert_sql = generate_wrote_insert(books)
    copy_insert_sql = generate_copy_insert(books)

  
    with open(output_file_path, 'w', encoding='utf-8') as f:
        f.write(f"#publisher\n{publisher_insert_sql}\n")
        f.write(f"\n#book\n{book_insert_sql}\n")
        f.write(f"\n#author\n{author_insert_sql}\n")
        f.write(f"\n#category\n{category_insert_sql}\n")
        f.write(f"\n#belongs\n{belongs_insert_sql}\n")
        f.write(f"\n#tags\n{tag_insert_sql}\n")
        f.write(f"\n#book_tags\n{book_tags_insert_sql}\n")
        f.write(f"\n#series\n{series_insert_sql}\n")
        f.write(f"\n#book update\n{book_update_sql}\n")
        f.write(f"\n#wrote\n{wrote_insert_sql}\n")
        f.write(f"\n#copy\n{copy_insert_sql}\n")
    
    print(f"SQL code has been generated in {output_file_path}")

input_file_path = 'book_data.txt'
output_file_path = 'generated_sql.sql'
generate_sql_from_txt(input_file_path, output_file_path)
