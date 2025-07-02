#publisher
INSERT INTO publisher (Name) SELECT 'Wednesday Books' AS Name WHERE NOT EXISTS (SELECT 1 FROM publisher p WHERE p.Name = 'Wednesday Books');

#book
INSERT INTO book (isbn, Title, Publisher_ID, Description, Release_year, Page_nr, Series_ID, Image_URL) VALUES ('9781250857439','Divine Rivals',(SELECT Publisher_ID FROM publisher WHERE Name = 'Wednesday Books'),'After centuries of sleep, the gods are warring again… All eighteen-year-old Iris Winnow wants to do is hold her family together. With a brother on the frontline forced to fight on behalf of the Gods now missing from the frontline and a mother drowning her sorrows, Iris’s best bet is winning the columnist promotion at the Oath Gazette. But when Iris’s letters to her brother fall into the wrong hands – that of the handsome but cold Roman Kitt, her rival at the paper – an unlikely magical connection forms. Expelled into the middle of a mystical war, magical typewriters in tow, can their bond withstand the fight for the fate of mankind and, most importantly, love? An epic enemies-to-lovers fantasy novel filled with hope and heartbreak, and the unparalleled power of love.',2023,357,NULL,'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1655928079i/60784546.jpg');
INSERT INTO book (isbn, Title, Publisher_ID, Description, Release_year, Page_nr, Series_ID, Image_URL) VALUES ('9781250857453','Ruthless Vows',(SELECT Publisher_ID FROM publisher WHERE Name = 'Wednesday Books'),'Torn apart by war. Reunited by love? Two weeks have passed since Iris returned home bruised and heartbroken from the front, but the war is far from over. Roman is missing, lost behind enemy lines, with no memory of his past, or Iris. Hoping his memories return, he begins to write again – but this time for the enemy. When a strange letter arrives through his wardrobe door, he strikes up a correspondence with a penpal who seems at once mysterious… and strangely familiar. As their connection deepens, the two of them will risk their very hearts and futures to change the tides of the war.',2023,420,NULL,'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1684911482i/127280062.jpg');

#author
INSERT INTO author (name) SELECT 'Rebecca   Ross' AS name WHERE NOT EXISTS (SELECT 1 FROM author a WHERE a.name = 'Rebecca   Ross');

#category
INSERT INTO category (name) SELECT 'Audiobook' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Audiobook');
INSERT INTO category (name) SELECT 'Romance' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Romance');
INSERT INTO category (name) SELECT 'Historical Fiction' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Historical Fiction');
INSERT INTO category (name) SELECT 'Fiction' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Fiction');
INSERT INTO category (name) SELECT 'Fantasy' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Fantasy');
INSERT INTO category (name) SELECT 'Enemies To Lovers' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Enemies To Lovers');
INSERT INTO category (name) SELECT 'Romantasy' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Romantasy');
INSERT INTO category (name) SELECT 'Young Adult' AS name WHERE NOT EXISTS (SELECT 1 FROM category c WHERE c.name = 'Young Adult');

#belongs
INSERT INTO belongs (isbn, category_id) SELECT b.isbn, c.category_id FROM book b JOIN category c ON c.name IN ('Romance', 'Young Adult', 'Romantasy', 'Audiobook', 'Fiction', 'Enemies To Lovers', 'Historical Fiction') WHERE b.title = 'Divine Rivals';
INSERT INTO belongs (isbn, category_id) SELECT b.isbn, c.category_id FROM book b JOIN category c ON c.name IN ('Fantasy', 'Romance', 'Young Adult', 'Romantasy', 'Audiobook', 'Fiction', 'Historical Fiction') WHERE b.title = 'Ruthless Vows';

#tags


#book_tags


#series
INSERT INTO book_series (name) SELECT 'Letters of Enchantment' AS name WHERE NOT EXISTS (SELECT 1 FROM book_series bs WHERE bs.name = 'Letters of Enchantment');

#book update
UPDATE book SET series_id = (SELECT series_id FROM book_series WHERE name = 'Letters of Enchantment') WHERE title = 'Divine Rivals';
UPDATE book SET series_id = (SELECT series_id FROM book_series WHERE name = 'Letters of Enchantment') WHERE title = 'Ruthless Vows';

#wrote
INSERT INTO wrote (isbn, author_id) SELECT b.isbn, a.author_id FROM book b JOIN author a ON a.name = 'Rebecca   Ross' WHERE b.title = 'Divine Rivals';
INSERT INTO wrote (isbn, author_id) SELECT b.isbn, a.author_id FROM book b JOIN author a ON a.name = 'Rebecca   Ross' WHERE b.title = 'Ruthless Vows';

#copy
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('Good', '3-7', '9781250857439');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('New', '3-7', '9781250857439');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('Fair', '3-7', '9781250857439');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('Good', '8-3', '9781250857453');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('New', '8-3', '9781250857453');
INSERT INTO copy (Copy_Condition, Shelf_Position, ISBN) VALUES ('Fair', '8-3', '9781250857453');
