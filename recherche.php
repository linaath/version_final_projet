<?php
$pageTitle = "Recherche";
require_once 'includes/header.php';


$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 50;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';


try {
    $conn = connectDB();
    
    
    $sql = "
        SELECT i.*, c.name as category_name
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE 1=1
    ";
    
    $params = [];
   
    if (!empty($query)) {
        $sql .= " AND (i.name LIKE :query OR i.description LIKE :query)";
        $params[':query'] = "%$query%";
    }
    
 
    if (!empty($categoryFilter)) {
        $sql .= " AND c.name = :category";
        $params[':category'] = $categoryFilter;
    }
    
    
    $sql .= " AND i.price BETWEEN :min_price AND :max_price";
    $params[':min_price'] = $minPrice;
    $params[':max_price'] = $maxPrice;
    
 
    switch ($sortBy) {
        case 'price_asc':
            $sql .= " ORDER BY i.price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY i.price DESC";
            break;
        case 'rating_desc':
            $sql .= " ORDER BY i.rating DESC, i.review_count DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY i.created_at DESC";
            break;
        case 'name_asc':
        default:
            $sql .= " ORDER BY i.name ASC";
            break;
    }
    
  
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $searchResults = $stmt->fetchAll();
    
    
    $stmt = $conn->query("SELECT name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    die("Erreur lors de la recherche: " . $e->getMessage());
}
?>

<main>
  <div class="page-header">
    <h1>Résultats de recherche</h1>
    <?php if (!empty($query)): ?>
    <p>Résultats pour "<?php echo htmlspecialchars($query); ?>"</p>
    <?php endif; ?>
  </div>

  <section class="search-section">
    <div class="search-container">
      <div class="search-filters">
        <div class="filter-header">
          <h2>Filtres</h2>
          <button class="filter-toggle-mobile">
            <i class="fas fa-filter"></i> Filtres
          </button>
        </div>
        
        <form class="filter-form" method="get" action="recherche.php">
          <?php if (!empty($query)): ?>
          <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
          <?php endif; ?>
          
          <div class="filter-group">
            <h3>Catégories</h3>
            <div class="filter-options">
              <div class="filter-option">
                <input type="radio" id="category-all" name="category" value="" <?php echo empty($categoryFilter) ? 'checked' : ''; ?>>
                <label for="category-all">Toutes les catégories</label>
              </div>
              <?php foreach ($categories as $category): ?>
              <div class="filter-option">
                <input type="radio" id="category-<?php echo strtolower(str_replace(' ', '-', $category)); ?>" name="category" value="<?php echo $category; ?>" <?php echo $categoryFilter === $category ? 'checked' : ''; ?>>
                <label for="category-<?php echo strtolower(str_replace(' ', '-', $category)); ?>"><?php echo $category; ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          
          <div class="filter-group">
            <h3>Prix</h3>
            <div class="price-range">
              <div class="price-inputs">
                <div class="price-input">
                  <label for="min-price">Min</label>
                  <input type="number" id="min-price" name="min_price" min="0" max="50" step="0.5" value="<?php echo $minPrice; ?>">
                </div>
                <div class="price-input">
                  <label for="max-price">Max</label>
                  <input type="number" id="max-price" name="max_price" min="0" max="50" step="0.5" value="<?php echo $maxPrice; ?>">
                </div>
              </div>
              <div class="price-slider">
                <input type="range" id="price-range" min="0" max="50" step="0.5" value="<?php echo $maxPrice; ?>">
              </div>
            </div>
          </div>
          
          <div class="filter-group">
            <h3>Trier par</h3>
            <div class="filter-options">
              <div class="filter-option">
                <input type="radio" id="sort-name-asc" name="sort" value="name_asc" <?php echo $sortBy === 'name_asc' ? 'checked' : ''; ?>>
                <label for="sort-name-asc">Nom (A-Z)</label>
              </div>
              <div class="filter-option">
                <input type="radio" id="sort-price-asc" name="sort" value="price_asc" <?php echo $sortBy === 'price_asc' ? 'checked' : ''; ?>>
                <label for="sort-price-asc">Prix croissant</label>
              </div>
              <div class="filter-option">
                <input type="radio" id="sort-price-desc" name="sort" value="price_desc" <?php echo $sortBy === 'price_desc' ? 'checked' : ''; ?>>
                <label for="sort-price-desc">Prix décroissant</label>
              </div>
              <div class="filter-option">
                <input type="radio" id="sort-rating" name="sort" value="rating_desc" <?php echo $sortBy === 'rating_desc' ? 'checked' : ''; ?>>
                <label for="sort-rating">Mieux notés</label>
              </div>
              <div class="filter-option">
                <input type="radio" id="sort-newest" name="sort" value="newest" <?php echo $sortBy === 'newest' ? 'checked' : ''; ?>>
                <label for="sort-newest">Nouveautés</label>
              </div>
            </div>
          </div>
          
          <div class="filter-actions">
            <button type="submit" class="btn-primary">Appliquer les filtres</button>
            <a href="recherche.php<?php echo !empty($query) ? '?q=' . urlencode($query) : ''; ?>" class="btn-secondary">Réinitialiser</a>
          </div>
        </form>
      </div>
      
      <div class="search-results">
        <div class="results-header">
          <p class="results-count"><?php echo count($searchResults); ?> résultat(s) trouvé(s)</p>
          <div class="results-sort-mobile">
            <label for="sort-mobile">Trier par:</label>
            <select id="sort-mobile" onchange="this.form.submit()">
              <option value="name_asc" <?php echo $sortBy === 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
              <option value="price_asc" <?php echo $sortBy === 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
              <option value="price_desc" <?php echo $sortBy === 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
              <option value="rating_desc" <?php echo $sortBy === 'rating_desc' ? 'selected' : ''; ?>>Mieux notés</option>
              <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Nouveautés</option>
            </select>
          </div>
        </div>
        
        <?php if (count($searchResults) > 0): ?>
        <div class="products-grid">
          <?php foreach ($searchResults as $product): ?>
          <div class="product-card">
            <div class="product-image">
              <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
              <div class="product-overlay">
                <a href="produit.php?id=<?php echo $product['id']; ?>" class="btn-view">Voir détails</a>
                <button class="btn-add-cart" data-id="<?php echo $product['id']; ?>">Ajouter au panier</button>
              </div>
              <?php if ($product['is_limited_edition']): ?>
              <div class="product-badge limited">Édition limitée</div>
              <?php endif; ?>
            </div>
            <div class="product-info">
              <h3><?php echo $product['name']; ?></h3>
              <p class="product-category"><?php echo $product['category_name']; ?></p>
              <p class="product-description"><?php echo substr($product['description'], 0, 100) . '...'; ?></p>
              <div class="product-meta">
                <p class="product-price"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</p>
                <div class="product-rating">
                  <?php
                  $rating = $product['rating'];
                  $fullStars = floor($rating);
                  $halfStar = $rating - $fullStars >= 0.5;
                  
                  for ($i = 1; $i <= 5; $i++) {
                      if ($i <= $fullStars) {
                          echo '<i class="fas fa-star"></i>';
                      } elseif ($i == $fullStars + 1 && $halfStar) {
                          echo '<i class="fas fa-star-half-alt"></i>';
                      } else {
                          echo '<i class="far fa-star"></i>';
                      }
                  }
                  ?>
                  <span>(<?php echo $product['review_count']; ?>)</span>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-results">
          <div class="no-results-icon">
            <i class="fas fa-search"></i>
          </div>
          <h2>Aucun résultat trouvé</h2>
          <p>Essayez de modifier vos critères de recherche ou de réinitialiser les filtres.</p>
          <a href="boutique.php" class="btn-primary">Voir tous les produits</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Gestion du slider de prix
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    const priceRangeSlider = document.getElementById('price-range');
    
    // Mettre à jour le slider en fonction des inputs
    function updateSlider() {
      const minVal = parseFloat(minPriceInput.value);
      const maxVal = parseFloat(maxPriceInput.value);
      
      if (minVal > maxVal) {
        maxPriceInput.value = minVal;
      }
      
      priceRangeSlider.value = maxPriceInput.value;
    }
    
    // Mettre à jour l'input max en fonction du slider
    priceRangeSlider.addEventListener('input', function() {
      maxPriceInput.value = this.value;
    });
    
    minPriceInput.addEventListener('change', updateSlider);
    maxPriceInput.addEventListener('change', updateSlider);
    
    // Gestion du tri mobile
    const sortMobile = document.getElementById('sort-mobile');
    sortMobile.addEventListener('change', function() {
      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('sort', this.value);
      window.location.href = currentUrl.toString();
    });
    
    // Gestion du toggle des filtres sur mobile
    const filterToggle = document.querySelector('.filter-toggle-mobile');
    const filterForm = document.querySelector('.filter-form');
    
    filterToggle.addEventListener('click', function() {
      filterForm.classList.toggle('active');
    });
    
    // Ajouter au panier
    const addToCartButtons = document.querySelectorAll('.btn-add-cart');
    
    addToCartButtons.forEach(function(button) {
      button.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        
        // Envoyer une requête AJAX pour ajouter au panier
        fetch('ajouter-au-panier.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'item_id=' + productId + '&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Mettre à jour le compteur du panier
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
              cartCount.textContent = data.cart_count;
            }
            
            // Afficher un message de succès
            alert('Produit ajouté au panier !');
          } else {
            alert(data.message || 'Une erreur est survenue. Veuillez réessayer.');
          }
        })
        .catch(error => {
          console.error('Erreur:', error);
          alert('Une erreur est survenue. Veuillez réessayer.');
        });
      });
    });
  });
</script>

<?php require_once 'includes/footer.php'; ?>