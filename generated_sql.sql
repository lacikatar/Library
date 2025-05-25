#publisher
INSERT INTO publisher (Name) SELECT 'St. Martin''s Griffin' AS Name WHERE NOT EXISTS (SELECT 1 FROM publisher p WHERE p.Name = 'St. Martin''s Griffin');

#book
INSERT INTO book (isbn, Title, Publisher_ID, Description, Release_year, Page_nr, Series_ID, Image_URL) VALUES ('9780312330873','And Then There Were None',(SELECT Publisher_ID FROM publisher WHERE Name = 'St. Martin''s Griffin'),'First, there were ten—a curious assortment of strangers summoned as weekend guests to a little private island off the coast of Devon. Their host, an eccentric millionaire unknown to all of them, is nowhere to be found. All that the guests have in common is a wicked past they''re unwilling to reveal—and a secret that will seal their fate. For each has been marked for murder. A famous nursery rhyme is framed and hung in every room of the mansion',1939,264,NULL,'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1638425885i/16299.jpg');

#author
INSERT INTO author (name) SELECT 'Agatha Christie' AS name WHERE NOT EXISTS (SELECT 1 FROM author a WHERE a.name = 'Agatha Christie');

#category
INSERT INTO category (name) SELECT 'Mystery' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Mystery');
INSERT INTO category (name) SELECT 'Mystery Thriller' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Mystery Thriller');
INSERT INTO category (name) SELECT 'Classics' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Classics');
INSERT INTO category (name) SELECT 'Thriller' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Thriller');
INSERT INTO category (name) SELECT 'Fiction' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Fiction');
INSERT INTO category (name) SELECT 'Crime' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Crime');

#belongs
INSERT INTO belongs (isbn, category_id) SELECT b.isbn, c.category_id FROM book b JOIN category c ON c.name IN ('Mystery', 'Fiction', 'Thriller', 'Crime', 'Mystery Thriller', 'Classics') WHERE b.title = 'And Then There Were None';

#tags
INSERT INTO tags (Tag_Name) SELECT 'Book Club' AS Tag_Name WHERE NOT EXISTS (SELECT 1 FROM tags t WHERE t.Tag_Name = 'Book Club');

#book_tags
INSERT INTO book_tags (isbn, tag_id) SELECT b.isbn, t.tag_id FROM book b JOIN tags t ON t.Tag_Name IN ('Book Club') WHERE b.title = 'And Then There Were None';

#series


#book update


#wrote
INSERT INTO wrote (isbn, author_id) SELECT b.isbn, a.author_id FROM book b JOIN author a ON a.name = 'Agatha Christie' WHERE b.title = 'And Then There Were None';

#copy
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('Good', '7-5', '9780312330873');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('New', '7-5', '9780312330873');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('Fair', '7-5', '9780312330873');
