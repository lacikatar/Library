# interaction_impact_demo.py
# Demonstrálja, hogyan befolyásolja egy új interakció a felhasználók közötti hasonlóságot

import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
import matplotlib.pyplot as plt
import seaborn as sns

# Eredeti felhasználói interakciós mátrix (súlyozott interakciók)
data = {
    'ISBN_1': [3, 0, 5, 0, 2, 1],
    'ISBN_2': [0, 4, 0, 3, 0, 5],
    'ISBN_3': [4, 0, 0, 5, 3, 0],
    'ISBN_4': [0, 5, 3, 0, 0, 4],
    'ISBN_5': [0, 0, 0, 2, 5, 0],
}
user_item_matrix = pd.DataFrame(data, index=[f'User_{i+1}' for i in range(6)])

# Eredeti hasonlóság kiszámítása
original_similarity = cosine_similarity(user_item_matrix)
original_df = pd.DataFrame(original_similarity, index=user_item_matrix.index, columns=user_item_matrix.index)

# Új interakció hozzáadása
updated_matrix = user_item_matrix.copy()
updated_matrix.loc['User_1', 'ISBN_4'] = 4  # új kölcsönzés
updated_matrix.loc['User_2', 'ISBN_5'] = 5  # új értékelés

# Frissített hasonlóság kiszámítása
updated_similarity = cosine_similarity(updated_matrix)
updated_df = pd.DataFrame(updated_similarity, index=updated_matrix.index, columns=updated_matrix.index)

# Változás kiszámítása
similarity_diff = updated_df - original_df

# Ábrák megjelenítése
plt.figure(figsize=(16, 12))

plt.subplot(1, 3, 1)
sns.heatmap(original_df, annot=True, cmap='Blues', cbar=False)
plt.title("Eredeti felhasználói hasonlóság")

plt.subplot(1, 3, 2)
sns.heatmap(updated_df, annot=True, cmap='Oranges', cbar=False)
plt.title("Frissített felhasználói hasonlóság")

plt.subplot(1, 3, 3)
sns.heatmap(similarity_diff, annot=True, cmap='coolwarm', center=0, cbar=False)
plt.title("Változás a hasonlóságban")

plt.tight_layout()
plt.show()

# Opcionálisan: mentés fájlba
# plt.savefig("similarity_comparison_heatmaps.png")
