<?php

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: boutique.php");
    exit;
}

$productId = intval($_GET['id']);

try {
    require_once 'config/database.php';
    $conn = connectDB();
    
    $stmt = $conn->prepare("
        SELECT i.*, c.name as category_name
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE i.id = :id
    ");
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header("Location: boutique.php");
        exit;
    }
    
    $product = $stmt->fetch();
    
   
    $stmtImages = $conn->prepare("
        SELECT image_url
        FROM item_images
        WHERE item_id = :item_id
    ");
    $stmtImages->bindParam(':item_id', $productId);
    $stmtImages->execute();
    $images = $stmtImages->fetchAll();
    
   
    if (count($images) === 0) {
        $images[] = ['image_url' => $product['image_url']];
    }
    
    
    $stmtReviews = $conn->prepare("
        SELECT r.*, u.firstname, u.lastname
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.item_id = :item_id
        ORDER BY r.created_at DESC
        LIMIT 3
    ");
    $stmtReviews->bindParam(':item_id', $productId);
    $stmtReviews->execute();
    $reviews = $stmtReviews->fetchAll();
    
    
    $stmtReviewCount = $conn->prepare("
        SELECT COUNT(*) as count
        FROM reviews
        WHERE item_id = :item_id
    ");
    $stmtReviewCount->bindParam(':item_id', $productId);
    $stmtReviewCount->execute();
    $reviewCount = $stmtReviewCount->fetch()['count'];
    
   
    $stmtRating = $conn->prepare("
        SELECT AVG(rating) as avg_rating
        FROM reviews
        WHERE item_id = :item_id
    ");
    $stmtRating->bindParam(':item_id', $productId);
    $stmtRating->execute();
    $avgRating = $stmtRating->fetch()['avg_rating'];
    if ($avgRating === null) {
        $avgRating = 0;
    }
    
  
    $stmtRelated = $conn->prepare("
        SELECT i.*
        FROM items i
        WHERE i.category_id = :category_id AND i.id != :id
        LIMIT 4
    ");
    $stmtRelated->bindParam(':category_id', $product['category_id']);
    $stmtRelated->bindParam(':id', $productId);
    $stmtRelated->execute();
    $relatedProducts = $stmtRelated->fetchAll();
    
  
    $pageTitle = $product['name'];
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des détails du produit: " . $e->getMessage());
}


if (isset($_POST['add_to_cart']) && isset($_SESSION['user_id'])) {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($quantity > 0 && $quantity <= $product['stock']) {
        try {
           
            $stmtCart = $conn->prepare("
                SELECT id FROM carts WHERE user_id = :user_id
            ");
            $stmtCart->bindParam(':user_id', $_SESSION['user_id']);
            $stmtCart->execute();
            
            if ($stmtCart->rowCount() > 0) {
                $cartId = $stmtCart->fetch()['id'];
                
                
                $stmtCartItem = $conn->prepare("
                    SELECT id, quantity FROM cart_items
                    WHERE cart_id = :cart_id AND item_id = :item_id
                ");
                $stmtCartItem->bindParam(':cart_id', $cartId);
                $stmtCartItem->bindParam(':item_id', $productId);
                $stmtCartItem->execute();
                
                if ($stmtCartItem->rowCount() > 0) {
                  
                    $cartItem = $stmtCartItem->fetch();
                    $newQuantity = $cartItem['quantity'] + $quantity;
                    
                    $stmtUpdate = $conn->prepare("
                        UPDATE cart_items
                        SET quantity = :quantity
                        WHERE id = :id
                    ");
                    $stmtUpdate->bindParam(':quantity', $newQuantity);
                    $stmtUpdate->bindParam(':id', $cartItem['id']);
                    $stmtUpdate->execute();
                } else {
                    
                    $stmtInsert = $conn->prepare("
                        INSERT INTO cart_items (cart_id, item_id, quantity)
                        VALUES (:cart_id, :item_id, :quantity)
                    ");
                    $stmtInsert->bindParam(':cart_id', $cartId);
                    $stmtInsert->bindParam(':item_id', $productId);
                    $stmtInsert->bindParam(':quantity', $quantity);
                    $stmtInsert->execute();
                }
                
                
                header("Location: produit.php?id=$productId&added=1");
                exit;
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de l'ajout au panier: " . $e->getMessage();
        }
    } else {
        $error = "Quantité invalide ou stock insuffisant.";
    }
}

require_once 'includes/header.php';
?>

<main>
  <div class="breadcrumb">
    <a href="index.php">Accueil</a> &gt; 
    <a href="boutique.php">Boutique</a> &gt; 
    <span><?php echo $product['name']; ?></span>
  </div>

  <?php if (isset($_GET['added']) && $_GET['added'] == 1): ?>
  <div class="alert alert-success">
    Le produit a été ajouté à votre panier.
  </div>
  <?php endif; ?>

  <?php if (isset($error)): ?>
  <div class="alert alert-error">
    <?php echo $error; ?>
  </div>
  <?php endif; ?>

  <section class="product-detail">
    <div class="product-gallery">
      <div class="main-image">
        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" id="main-product-image">
      </div>
      <div class="thumbnail-images">
        <?php foreach ($images as $index => $image): ?>
        <img src="<?php echo $image['image_url']; ?>" alt="<?php echo $product['name']; ?> - Vue <?php echo $index + 1; ?>" class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeImage(this)">
        <?php endforeach; ?>
      </div>
    </div>
    <div class="product-info-detail">
      <div class="product-category"><?php echo $product['category_name']; ?></div>
      <h1 class="product-title"><?php echo $product['name']; ?></h1>
      <div class="product-rating">
        <div class="stars">
          <?php
          $fullStars = floor($avgRating);
          $halfStar = $avgRating - $fullStars >= 0.5;
          
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
        </div>
        <span class="rating-count"><?php echo $reviewCount; ?> avis</span>
      </div>
      <div class="product-price"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</div>
      <div class="product-description-long">
        <p><?php echo nl2br($product['description']); ?></p>
      </div>
      <div class="product-attributes">
        <div class="attribute">
          <span class="attribute-label">Ingrédients:</span>
          <span class="attribute-value"><?php echo $product['ingredients']; ?></span>
        </div>
        <div class="attribute">
          <span class="attribute-label">Allergènes:</span>
          <span class="attribute-value"><?php echo $product['allergens']; ?></span>
        </div>
        <div class="attribute">
          <span class="attribute-label">Conservation:</span>
          <span class="attribute-value"><?php echo $product['conservation']; ?></span>
        </div>
      </div>
      <form method="post" action="produit.php?id=<?php echo $productId; ?>" class="product-actions">
        <div class="quantity-selector">
          <button type="button" class="quantity-btn minus">-</button>
          <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
          <button type="button" class="quantity-btn plus">+</button>
        </div>
        <button type="submit" name="add_to_cart" class="btn-primary add-to-cart-btn">Ajouter au panier</button>
        <button type="button" class="btn-wishlist"><i class="far fa-heart"></i></button>
      </form>
      <div class="product-meta">
        <div class="delivery-info">
          <i class="fas fa-truck"></i> Livraison disponible à Paris et proche banlieue
        </div>
        <div class="pickup-info">
          <i class="fas fa-store"></i> Retrait en boutique disponible
        </div>
      </div>
    </div>
  </section>

  <section class="product-tabs">
    <div class="tabs-header">
      <button class="tab-btn active" data-tab="description">Description</button>
      <button class="tab-btn" data-tab="ingredients">Ingrédients</button>
      <button class="tab-btn" data-tab="reviews">Avis (<?php echo $reviewCount; ?>)</button>
    </div>
    <div class="tabs-content">
      <div class="tab-panel active" id="description">
        <h3>L'histoire de notre <?php echo strtolower($product['name']); ?></h3>
        <p><?php echo nl2br($product['description']); ?></p>
      </div>
      <div class="tab-panel" id="ingredients">
        <h3>Composition</h3>
        <p><?php echo nl2br($product['ingredients']); ?></p>
        <div class="allergens-info">
          <h4>Informations allergènes</h4>
          <p>Ce produit contient : <?php echo $product['allergens']; ?>.</p>
          <p>Fabriqué dans un atelier qui utilise également : fruits à coque, soja, sésame.</p>
        </div>
        <div class="nutritional-info">
          <h4>Valeurs nutritionnelles (pour 100g)</h4>
          <table>
            <tr>
              <td>Énergie</td>
              <td>385 kcal</td>
            </tr>
            <tr>
              <td>Matières grasses</td>
              <td>24g</td>
            </tr>
            <tr>
              <td>dont acides gras saturés</td>
              <td>15g</td>
            </tr>
            <tr>
              <td>Glucides</td>
              <td>35g</td>
            </tr>
            <tr>
              <td>dont sucres</td>
              <td>22g</td>
            </tr>
            <tr>
              <td>Protéines</td>
              <td>6g</td>
            </tr>
            <tr>
              <td>Sel</td>
              <td>0,2g</td>
            </tr>
          </table>
        </div>
      </div>
      <div class="tab-panel" id="reviews">
        <div class="reviews-summary">
          <div class="average-rating">
            <div class="rating-number"><?php echo number_format($avgRating, 1); ?></div>
            <div class="stars">
              <?php
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
            </div>
            <div class="total-reviews"><?php echo $reviewCount; ?> avis</div>
          </div>
          <div class="rating-bars">
            <?php
            
            $stmtRatingDistribution = $conn->prepare("
                SELECT rating, COUNT(*) as count
                FROM reviews
                WHERE item_id = :item_id
                GROUP BY rating
                ORDER BY rating DESC
            ");
            $stmtRatingDistribution->bindParam(':item_id', $productId);
            $stmtRatingDistribution->execute();
            $ratingDistribution = $stmtRatingDistribution->fetchAll();
            
            $ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
            foreach ($ratingDistribution as $rating) {
                $ratingCounts[$rating['rating']] = $rating['count'];
            }
            
            for ($i = 5; $i >= 1; $i--) {
                $percent = $reviewCount > 0 ? round(($ratingCounts[$i] / $reviewCount) * 100) : 0;
                ?>
                <div class="rating-bar-item">
                  <span class="rating-label"><?php echo $i; ?> étoiles</span>
                  <div class="rating-bar">
                    <div class="rating-fill" style="width: <?php echo $percent; ?>%"></div>
                  </div>
                  <span class="rating-percent"><?php echo $percent; ?>%</span>
                </div>
                <?php
            }
            ?>
          </div>
        </div>
        <div class="reviews-list">
          <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-item">
              <div class="review-header">
                <div class="reviewer-info">
                  <div class="reviewer-name"><?php echo $review['firstname'] . ' ' . substr($review['lastname'], 0, 1) . '.'; ?></div>
                  <div class="review-date"><?php echo date('d F Y', strtotime($review['created_at'])); ?></div>
                </div>
                <div class="review-rating">
                  <?php
                  for ($i = 1; $i <= 5; $i++) {
                      if ($i <= $review['rating']) {
                          echo '<i class="fas fa-star"></i>';
                      } else {
                          echo '<i class="far fa-star"></i>';
                      }
                  }
                  ?>
                </div>
              </div>
              <div class="review-content">
                <p><?php echo nl2br($review['comment']); ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>Aucun avis pour le moment. Soyez le premier à donner votre avis sur ce produit !</p>
          <?php endif; ?>
        </div>
        <?php if ($reviewCount > 3): ?>
        <button class="btn-secondary load-more-reviews">Voir plus d'avis</button>
        <?php endif; ?>
        
        <?php if (isLoggedIn()): ?>
        <div class="add-review">
          <h3>Ajouter un avis</h3>
          <form method="post" action="add-review.php">
            <input type="hidden" name="item_id" value="<?php echo $productId; ?>">
            <div class="form-group">
              <label>Votre note</label>
              <div class="rating-select">
                <input type="radio" id="star5" name="rating" value="5" required>
                <label for="star5"><i class="far fa-star"></i></label>
                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4"><i class="far fa-star"></i></label>
                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3"><i class="far fa-star"></i></label>
                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2"><i class="far fa-star"></i></label>
                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1"><i class="far fa-star"></i></label>
              </div>
            </div>
            <div class="form-group">
              <label for="review-comment">Votre commentaire</label>
              <textarea id="review-comment" name="comment" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn-primary">Soumettre mon avis</button>
          </form>
        </div>
        <?php else: ?>
        <div class="login-to-review">
          <p>Vous devez être connecté pour laisser un avis.</p>
          <a href="login.php" class="btn-secondary">Se connecter</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="related-products">
    <h2>Vous aimerez aussi</h2>
    <div class="products-grid">
      <?php foreach ($relatedProducts as $relatedProduct): ?>
      <div class="product-card">
        <div class="product-image">
          <img src="<?php echo $relatedProduct['image_url']; ?>" alt="<?php echo $relatedProduct['name']; ?>">
          <div class="product-overlay">
            <a href="produit.php?id=<?php echo $relatedProduct['id']; ?>" class="btn-view">Voir détails</a>
            <button class="btn-add-cart" data-id="<?php echo $relatedProduct['id']; ?>">Ajouter au panier</button>
          </div>
        </div>
        <div class="product-info">
          <h3><?php echo $relatedProduct['name']; ?></h3>
          <p class="product-description"><?php echo substr($relatedProduct['description'], 0, 100) . '...'; ?></p>
          <p class="product-price"><?php echo number_format($relatedProduct['price'], 2, ',', ' '); ?> €</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<?php require_once 'includes/footer.php'; ?>