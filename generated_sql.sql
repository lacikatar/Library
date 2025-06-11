#publisher
INSERT INTO publisher (Name) SELECT 'Del Rey' AS Name WHERE NOT EXISTS (SELECT 1 FROM publisher p WHERE p.Name = 'Del Rey');

#book
INSERT INTO book (isbn, Title, Publisher_ID, Description, Release_year, Page_nr, Series_ID, Image_URL) VALUES ('9780593873359','Blood Over Bright Haven',(SELECT Publisher_ID FROM publisher WHERE Name = 'Del Rey'),'Magic has made the city of Tiran an industrial utopia, but magic has a cost—and the collectors have come calling. An orphan since the age of four, Sciona has always had more to prove than her fellow students. For twenty years, she has devoted every waking moment to the study of magic, fueled by a mad desire to achieve the impossible',2023,430,NULL,'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1717354584i/208430658.jpg');

#author
INSERT INTO author (name) SELECT 'M.L. Wang' AS name WHERE NOT EXISTS (SELECT 1 FROM author a WHERE a.name = 'M.L. Wang');

#category
INSERT INTO category (name) SELECT 'Dark Academia' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Dark Academia');
INSERT INTO category (name) SELECT 'High Fantasy' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'High Fantasy');
INSERT INTO category (name) SELECT 'Magic' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Magic');

#belongs
INSERT INTO belongs (isbn, category_id) SELECT b.isbn, c.category_id FROM book b JOIN category c ON c.name IN ('Dark Academia', 'Magic', 'High Fantasy') WHERE b.title = 'Blood Over Bright Haven';

#tags


#book_tags


#series


#book update


#wrote
INSERT INTO wrote (isbn, author_id) SELECT b.isbn, a.author_id FROM book b JOIN author a ON a.name = 'M.L. Wang' WHERE b.title = 'Blood Over Bright Haven';

#copy
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('Good', '1-8', '9780593873359');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('New', '1-8', '9780593873359');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('Fair', '1-8', '9780593873359');
