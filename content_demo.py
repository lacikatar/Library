# content_adaptivity_demo.py
# Demonstrálja, hogyan épül be egy új könyv a tartalom-alapú hasonlósági mátrixba

import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import matplotlib.pyplot as plt
import seaborn as sns

# Könyvek szöveges leírásai (egyszerűsített)
books = {
    'Book_1': "romantic novel set in Paris with dramatic love story",
    'Book_2': "historical fiction during the French revolution",
    'Book_3': "science fiction adventure with alien planets",
    'Book_4': "fantasy world with dragons and magic",
}

# TF-IDF vektorizálás
vectorizer = TfidfVectorizer()
tfidf_matrix = vectorizer.fit_transform(books.values())
similarity_original = cosine_similarity(tfidf_matrix)
original_df = pd.DataFrame(similarity_original, index=books.keys(), columns=books.keys())

# Új könyv érkezik
new_book = "a magical love story between a dragon and a human"
books_updated = list(books.values()) + [new_book]
labels_updated = list(books.keys()) + ['Book_5']

# Frissített TF-IDF
updated_matrix = vectorizer.fit_transform(books_updated)
similarity_updated = cosine_similarity(updated_matrix)
updated_df = pd.DataFrame(similarity_updated, index=labels_updated, columns=labels_updated)

# Különbség (csak a közös részen)
common_index = list(books.keys())
similarity_diff = updated_df.loc[common_index, common_index] - original_df

# Hőtérképek
plt.figure(figsize=(18, 5))

plt.subplot(1, 3, 1)
sns.heatmap(original_df, annot=True, cmap="Blues")
plt.title("Eredeti hasonlósági mátrix")

plt.subplot(1, 3, 2)
sns.heatmap(updated_df, annot=True, cmap="Oranges")
plt.title("Frissített hasonlósági mátrix (Book_5 belépése után)")

plt.subplot(1, 3, 3)
sns.heatmap(similarity_diff, annot=True, cmap="coolwarm", center=0)
plt.title("Változás a hasonlóságokban")

plt.tight_layout()
plt.show()
