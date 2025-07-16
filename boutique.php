<?php
$pageTitle = "Boutique";
require_once 'includes/header.php';


$categoryFilter = isset($_GET['category']) ? (is_array($_GET['category']) ? $_GET['category'] : [$_GET['category']]) : [];
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 50;
$allergenFilter = isset($_GET['allergen']) ? (is_array($_GET['allergen']) ? $_GET['allergen'] : [$_GET['allergen']]) : [];
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'popularity';


$productsPerPage = 9;
$currentPage = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);


$baseSql = "
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE 1=1
";


$params = [];
$filterSql = "";


if (!empty($categoryFilter)) {
    $placeholders = [];
    foreach ($categoryFilter as $index => $cat) {
        if (!empty($cat)) {
            $catPlaceholder = ":category" . $index;
            $placeholders[] = $catPlaceholder;
            $params[$catPlaceholder] = $cat;
        }
    }
    
    if (!empty($placeholders)) {
        $filterSql .= " AND c.name IN (" . implode(", ", $placeholders) . ")";
    }
}

if ($minPrice > 0) {
    $filterSql .= " AND i.price >= :min_price";
    $params[':min_price'] = $minPrice;
}
if ($maxPrice < 50) {
    $filterSql .= " AND i.price <= :max_price";
    $params[':max_price'] = $maxPrice;
}


if (!empty($allergenFilter)) {
    foreach ($allergenFilter as $allergen) {
        if (empty($allergen)) continue;
        
        switch($allergen) {
            case 'sans-gluten':
                $filterSql .= " AND i.allergens NOT LIKE :allergen_gluten";
                $params[':allergen_gluten'] = '%gluten%';
                break;
            case 'sans-lactose':
                $filterSql .= " AND i.allergens NOT LIKE :allergen_lactose";
                $params[':allergen_lactose'] = '%lait%';
                break;
            case 'sans-oeufs':
                $filterSql .= " AND i.allergens NOT LIKE :allergen_oeufs";
                $params[':allergen_oeufs'] = '%œufs%';
                break;
            case 'vegan':
                $filterSql .= " AND i.allergens NOT LIKE :allergen_lait 
                               AND i.allergens NOT LIKE :allergen_oeufs 
                               AND i.allergens NOT LIKE :allergen_miel";
                $params[':allergen_lait'] = '%lait%';
                $params[':allergen_oeufs'] = '%œufs%';
                $params[':allergen_miel'] = '%miel%';
                break;
        }
    }
}


$orderSql = " ORDER BY ";
switch ($sortBy) {
    case 'price-asc': $orderSql .= "i.price ASC"; break;
    case 'price-desc': $orderSql .= "i.price DESC"; break;
    case 'newest': $orderSql .= "i.created_at DESC"; break;
    default: $orderSql .= "i.is_featured DESC, i.review_count DESC"; break;
}

try {
    $conn = connectDB();
    
    
    $countSql = "SELECT COUNT(*) as total " . $baseSql . $filterSql;
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalProducts / $productsPerPage);
    
  
    if ($currentPage > $totalPages && $totalPages > 0) {
      
        $queryParams = ['page' => $totalPages];
        if (!empty($categoryFilter)) $queryParams['category'] = $categoryFilter;
        if ($minPrice > 0) $queryParams['min_price'] = $minPrice;
        if ($maxPrice < 50) $queryParams['max_price'] = $maxPrice;
        if (!empty($allergenFilter)) $queryParams['allergen'] = $allergenFilter;
        if ($sortBy !== 'popularity') $queryParams['sort'] = $sortBy;
        
        $redirectUrl = 'boutique.php?' . http_build_query($queryParams);
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    $offset = ($currentPage - 1) * $productsPerPage;
    $productsSql = "SELECT i.*, c.name as category_name " . $baseSql . $filterSql . $orderSql . 
                  " LIMIT " . $productsPerPage . " OFFSET " . $offset;
    
    $stmt = $conn->prepare($productsSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    
    $stmtCategories = $conn->query("SELECT name FROM categories ORDER BY name");
    $categories = $stmtCategories->fetchAll();
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des produits: " . $e->getMessage());
}


function buildUrl($page, $params = []) {
    $queryParams = array_merge($_GET, $params);
    $queryParams['page'] = $page;
    return 'boutique.php?' . http_build_query($queryParams);
}
?>

<main>
  <div class="page-header">
    <h1>Notre Boutique</h1>
    <p>Découvrez nos délicieuses pâtisseries artisanales</p>
  </div>

  <div class="shop-container">
    <div class="filters-sidebar">
      <form id="filter-form" action="boutique.php" method="get">
        <input type="hidden" name="page" value="1">
        
        <div class="filter-group">
          <h3>Catégories</h3>
          <ul class="filter-options">
            <?php foreach ($categories as $category): ?>
            <li>
              <input type="checkbox" id="category-<?php echo strtolower($category['name']); ?>" 
                     name="category[]" 
                     value="<?php echo $category['name']; ?>" 
                     <?php echo (in_array($category['name'], $categoryFilter)) ? 'checked' : ''; ?>>
              <label for="category-<?php echo strtolower($category['name']); ?>"><?php echo $category['name']; ?></label>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="filter-group">
          <h3>Prix</h3>
          <div class="price-range">
            <input type="range" id="price-slider" min="0" max="50" value="<?php echo $maxPrice; ?>">
            <div class="price-inputs">
              <input type="number" id="min-price" name="min_price" min="0" max="50" value="<?php echo $minPrice; ?>">
              <span>à</span>
              <input type="number" id="max-price" name="max_price" min="0" max="50" value="<?php echo $maxPrice; ?>">
              <span>€</span>
            </div>
          </div>
        </div>

        <div class="filter-group">
          <h3>Allergènes</h3>
          <ul class="filter-options">
            <?php 
            $allergens = [
                'sans-gluten' => 'Sans gluten',
                'sans-lactose' => 'Sans lactose',
                'sans-oeufs' => 'Sans œufs',
                'vegan' => 'Vegan'
            ];
            
            foreach ($allergens as $value => $label): 
            ?>
            <li>
              <input type="checkbox" id="allergen-<?php echo $value; ?>" 
                     name="allergen[]" value="<?php echo $value; ?>" 
                     <?php echo in_array($value, $allergenFilter) ? 'checked' : ''; ?>>
              <label for="allergen-<?php echo $value; ?>"><?php echo $label; ?></label>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <button type="submit" class="btn-primary btn-filter">Appliquer les filtres</button>
        <button type="button" class="btn-secondary btn-reset">Réinitialiser</button>
      </form>
    </div>

    <div class="products-container">
      <div class="products-header">
        <div class="products-count"><?php echo $totalProducts; ?> produits</div>
        <div class="products-sort">
          <label for="sort-by">Trier par:</label>
          <select id="sort-by" name="sort" form="sort-form">
            <?php 
            $sortOptions = [
                'popularity' => 'Popularité',
                'price-asc' => 'Prix croissant',
                'price-desc' => 'Prix décroissant',
                'newest' => 'Nouveautés'
            ];
            
            foreach ($sortOptions as $value => $label): 
            ?>
            <option value="<?php echo $value; ?>" <?php echo $sortBy === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
          
          <form id="sort-form" action="boutique.php" method="get">
            <input type="hidden" name="page" value="<?php echo $currentPage; ?>">
            
            <?php foreach ($_GET as $key => $value): ?>
              <?php if ($key !== 'sort' && $key !== 'page'): ?>
                <?php if (is_array($value)): ?>
                  <?php foreach ($value as $item): ?>
                    <input type="hidden" name="<?php echo $key; ?>[]" value="<?php echo htmlspecialchars($item); ?>">
                  <?php endforeach; ?>
                <?php else: ?>
                  <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
                <?php endif; ?>
              <?php endif; ?>
            <?php endforeach; ?>
          </form>
        </div>
      </div>

      <div class="products-grid">
        <?php foreach ($products as $product): ?>
        <div class="product-card">
          <div class="product-image">
            <img src="<?php echo !empty($product['image_url']) ? $product['image_url'] : 'assets/images/default-product.jpg'; ?>" alt="<?php echo $product['name']; ?>">
            <div class="product-overlay">
              <a href="produit.php?id=<?php echo $product['id']; ?>" class="btn-view">Voir détails</a>
              <button class="btn-add-cart" data-id="<?php echo $product['id']; ?>">Ajouter au panier</button>
            </div>
          </div>
          <div class="product-info">
            <h3><?php echo $product['name']; ?></h3>
            <p class="product-description"><?php echo substr($product['description'], 0, 100) . '...'; ?></p>
            <p class="product-price"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($products) === 0): ?>
      <div class="no-products">
        <p>Aucun produit ne correspond à vos critères de recherche.</p>
        <a href="boutique.php" class="btn-secondary">Réinitialiser les filtres</a>
      </div>
      <?php endif; ?>

      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <a href="<?php echo $currentPage > 1 ? buildUrl($currentPage - 1) : '#'; ?>" 
           class="pagination-arrow <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>" 
           aria-label="Page précédente">
          <i class="fas fa-chevron-left"></i>
        </a>
        
        <div class="pagination-numbers">
          <?php
          
          echo '<a href="' . buildUrl(1) . '" class="' . (1 == $currentPage ? 'active' : '') . '">1</a>';
          
         
          if ($currentPage > 3) echo '<span class="pagination-dots">...</span>';
          if ($currentPage > 2) echo '<a href="' . buildUrl($currentPage - 1) . '">' . ($currentPage - 1) . '</a>';
          if ($currentPage != 1 && $currentPage != $totalPages) {
              echo '<a href="' . buildUrl($currentPage) . '" class="active">' . $currentPage . '</a>';
          }
          
          
          if ($currentPage < $totalPages - 1) {
              echo '<a href="' . buildUrl($currentPage + 1) . '">' . ($currentPage + 1) . '</a>';
          }
          
          
          if ($currentPage < $totalPages - 2) echo '<span class="pagination-dots">...</span>';
          
         
          if ($totalPages > 1) {
              echo '<a href="' . buildUrl($totalPages) . '" class="' . ($totalPages == $currentPage ? 'active' : '') . '">' . $totalPages . '</a>';
          }
          ?>
        </div>
        
        <a href="<?php echo $currentPage < $totalPages ? buildUrl($currentPage + 1) : '#'; ?>" 
           class="pagination-arrow <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>"
           aria-label="Page suivante">
          <i class="fas fa-chevron-right"></i>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
document.getElementById('sort-by').addEventListener('change', function() {
    document.getElementById('sort-form').submit();
});

document.querySelector('.btn-reset').addEventListener('click', function() {
    window.location.href = 'boutique.php';
});


const priceSlider = document.getElementById('price-slider');
const minPriceInput = document.getElementById('min-price');
const maxPriceInput = document.getElementById('max-price');

priceSlider.addEventListener('input', function() {
    maxPriceInput.value = this.value;
});

minPriceInput.addEventListener('input', function() {
    const minPrice = parseFloat(this.value);
    const maxPrice = parseFloat(maxPriceInput.value);
    
    if (minPrice > maxPrice) {
        maxPriceInput.value = minPrice;
    }
});

maxPriceInput.addEventListener('input', function() {
    const minPrice = parseFloat(minPriceInput.value);
    const maxPrice = parseFloat(this.value);
    
    if (maxPrice < minPrice) {
        minPriceInput.value = maxPrice;
    }
    
    priceSlider.value = this.value;
});

document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.btn-add-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.getAttribute('data-id');
            
          
            fetch('ajouter-au-panier.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                  
                    const cartCounter = document.querySelector('.cart-count');
                    if (cartCounter) {
                        cartCounter.textContent = data.cart_count;
                    }
                    
                 
                    alert('Produit ajouté au panier');
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        });
    });
});

</script>

<?php require_once 'includes/footer.php'; ?>