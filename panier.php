<?php
$pageTitle = "Panier";
require_once 'includes/header.php';
require_once 'includes/functions.php'; 
if (!isLoggedIn()) {
   
    $_SESSION['redirect_after_login'] = 'panier.php';
    ?>
    <main>
        <div class="login-required-message">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <p>Vous devez être connecté pour accéder à votre panier.</p>
            </div>
            <div class="login-buttons">
                <a href="login.php" class="btn-primary">Se connecter</a>
                <a href="login.php" class="btn-secondary">Créer un compte</a>
            </div>
        </div>
    </main> 
    <?php
   
    require_once 'includes/footer.php';
    exit;
}


try {
    $conn = connectDB();
    
  
    $stmt = $conn->prepare("
        SELECT id FROM carts WHERE user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
    
        $stmt = $conn->prepare("
            INSERT INTO carts (user_id, created_at) VALUES (:user_id, NOW())
        ");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $cartId = $conn->lastInsertId();
    } else {
        $cartId = $stmt->fetch()['id'];
    }
    
    
    $stmt = $conn->prepare("
        SELECT ci.id, ci.quantity, ci.unit_price, i.id as item_id, i.name, i.price, i.image_url, i.stock
        FROM cart_items ci
        JOIN items i ON ci.item_id = i.id
        WHERE ci.cart_id = :cart_id
    ");
    $stmt->bindParam(':cart_id', $cartId);
    $stmt->execute();
    
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $itemId => $item) {
            
            $check_stmt = $conn->prepare("
                SELECT id, price, stock FROM items WHERE id = :item_id
            ");
            $check_stmt->bindParam(':item_id', $itemId);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $product = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
               
                $existing_stmt = $conn->prepare("
                    SELECT id, quantity FROM cart_items 
                    WHERE cart_id = :cart_id AND item_id = :item_id
                ");
                $existing_stmt->bindParam(':cart_id', $cartId);
                $existing_stmt->bindParam(':item_id', $itemId);
                $existing_stmt->execute();
                
                if ($existing_stmt->rowCount() > 0) {
                    $existing_item = $existing_stmt->fetch(PDO::FETCH_ASSOC);
                    $new_quantity = $existing_item['quantity'] + $item['quantity'];
                    
                    
                    if ($new_quantity > $product['stock']) {
                        $new_quantity = $product['stock'];
                    }
                    
                    $update_stmt = $conn->prepare("
                        UPDATE cart_items 
                        SET quantity = :quantity 
                        WHERE id = :id
                    ");
                    $update_stmt->bindParam(':quantity', $new_quantity);
                    $update_stmt->bindParam(':id', $existing_item['id']);
                    $update_stmt->execute();
                } else {
                    
                    $quantity = min($item['quantity'], $product['stock']);
                    if ($quantity > 0) {
                        $insert_stmt = $conn->prepare("
                            INSERT INTO cart_items (cart_id, item_id, quantity, unit_price)
                            VALUES (:cart_id, :item_id, :quantity, :unit_price)
                        ");
                        $insert_stmt->bindParam(':cart_id', $cartId);
                        $insert_stmt->bindParam(':item_id', $itemId);
                        $insert_stmt->bindParam(':quantity', $quantity);
                        $insert_stmt->bindParam(':unit_price', $product['price']);
                        $insert_stmt->execute();
                    }
                }
            }
        }
        
        
        unset($_SESSION['cart']);
        
        $stmt = $conn->prepare("
            SELECT ci.id, ci.quantity, ci.unit_price, i.id as item_id, i.name, i.price, i.image_url, i.stock
            FROM cart_items ci
            JOIN items i ON ci.item_id = i.id
            WHERE ci.cart_id = :cart_id
        ");
        $stmt->bindParam(':cart_id', $cartId);
        $stmt->execute();
        
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    $subtotal = 0;
    foreach ($cartItems as $item) {
      
        $price = isset($item['unit_price']) && $item['unit_price'] > 0 ? $item['unit_price'] : $item['price'];
        $subtotal += $price * $item['quantity'];
    }
    
    $shippingFee = 5.00;
    $total = $subtotal + $shippingFee;
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération du panier: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
  if (isset($_POST['update_cart'])) {
    
    foreach ($_POST['quantity'] as $itemId => $quantity) {
        $quantity = intval($quantity);
        if ($quantity > 0) {
            try {
                $stmt = $conn->prepare("
                    UPDATE cart_items
                    SET quantity = :quantity
                    WHERE id = :id AND cart_id = :cart_id
                ");
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':id', $itemId);
                $stmt->bindParam(':cart_id', $cartId);
                $stmt->execute();
            } catch(PDOException $e) {
                error_log("Erreur lors de la mise à jour du panier: " . $e->getMessage());
            }
        }
    }
    
    
    unset($_SESSION['cart_count']);
    getCartItemCount(true);
    
    
    header("Location: panier.php?updated=1");
    exit;
} 

elseif (isset($_POST['remove_item']) && isset($_POST['item_id'])) {
    $itemId = intval($_POST['item_id']);
    
    try {
        $stmt = $conn->prepare("
            DELETE FROM cart_items
            WHERE id = :id AND cart_id = :cart_id
        ");
        $stmt->bindParam(':id', $itemId);
        $stmt->bindParam(':cart_id', $cartId);
        $stmt->execute();
        
       
        unset($_SESSION['cart_count']);
        getCartItemCount(true);
        
       
        header("Location: panier.php?removed=1");
        exit;
    } catch(PDOException $e) {
        error_log("Erreur lors de la suppression de l'article: " . $e->getMessage());
    }

  } elseif (isset($_POST['apply_coupon']) && isset($_POST['coupon_code'])) {
       
        $couponCode = trim($_POST['coupon_code']);
        
        try {
            $stmt = $conn->prepare("
                SELECT * FROM promo_codes
                WHERE code = :code
                AND is_active = 1
                AND valid_from <= NOW()
                AND valid_to >= NOW()
            ");
            $stmt->bindParam(':code', $couponCode);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $coupon = $stmt->fetch();
                $discountPercent = $coupon['discount_percent'];
                
              
                $_SESSION['coupon'] = [
                    'code' => $couponCode,
                    'discount_percent' => $discountPercent
                ];
                
               
                header("Location: panier.php?coupon=applied");
                exit;
            } else {
                
                header("Location: panier.php?coupon=invalid");
                exit;
            }
        } catch(PDOException $e) {
           
        }
    }
}


$discount = 0;
if (isset($_SESSION['coupon'])) {
    $discount = $subtotal * ($_SESSION['coupon']['discount_percent'] / 100);
    $total = $subtotal - $discount + $shippingFee;
}
?>

<main>
  <div class="page-header">
    <h1>Votre Panier</h1>
    <p>Vérifiez vos articles et procédez au paiement</p>
  </div>

  <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
  <div class="alert alert-success">
    Votre panier a été mis à jour.
  </div>
  <?php endif; ?>

  <?php if (isset($_GET['removed']) && $_GET['removed'] == 1): ?>
  <div class="alert alert-success">
    L'article a été retiré de votre panier.
  </div>
  <?php endif; ?>

  <?php if (isset($_GET['coupon']) && $_GET['coupon'] == 'applied'): ?>
  <div class="alert alert-success">
    Le code promo a été appliqué avec succès.
  </div>
  <?php endif; ?>

  <?php if (isset($_GET['coupon']) && $_GET['coupon'] == 'invalid'): ?>
  <div class="alert alert-error">
    Le code promo est invalide ou a expiré.
  </div>
  <?php endif; ?>

  <section class="cart-section">
    <?php if (count($cartItems) > 0): ?>
    <form method="post" action="panier.php">
      <div class="cart-container">
        <div class="cart-items-container">
          <div class="cart-header">
            <div class="cart-header-product">Produit</div>
            <div>Prix</div>
            <div>Quantité</div>
            <div>Total</div>
          </div>
          <div class="cart-items">
            <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
              <div class="cart-item-product">
                <div class="cart-item-image">
                  <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                </div>
                <div class="cart-item-details">
  <h3><?php echo htmlspecialchars($item['name']); ?></h3>
  <p>Disponibilité: <?php echo $item['stock'] > 0 ? 'En stock' : 'Épuisé'; ?></p>
  <button type="button" class="remove-item" data-id="<?php echo $item['id']; ?>">
    <i class="fas fa-trash-alt"></i> Supprimer
  </button>
</div>
              </div>
              <div class="cart-item-price"><?php echo number_format($item['price'], 2, ',', ' '); ?> €</div>
              <div class="cart-item-quantity">
                <div class="quantity-selector">
                  <button type="button" class="quantity-btn minus">-</button>
                  <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input">
                  <button type="button" class="quantity-btn plus">+</button>
                </div>
              </div>
              <div class="cart-item-total"><?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> €</div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="cart-actions">
            <div class="coupon-form">
              <input type="text" name="coupon_code" placeholder="Code promo" class="coupon-input">
              <button type="submit" name="apply_coupon" class="btn-secondary">Appliquer</button>
            </div>
            <button type="submit" name="update_cart" class="btn-secondary update-cart">Mettre à jour le panier</button>
          </div>
        </div>
        <div class="cart-summary">
          <h2>Récapitulatif</h2>
          <div class="summary-row">
            <span>Sous-total</span>
            <span><?php echo number_format($subtotal, 2, ',', ' '); ?> €</span>
          </div>
          <?php if ($discount > 0): ?>
          <div class="summary-row discount">
            <span>Réduction (<?php echo $_SESSION['coupon']['discount_percent']; ?>%)</span>
            <span>-<?php echo number_format($discount, 2, ',', ' '); ?> €</span>
          </div>
          <?php endif; ?>
          <div class="summary-row">
            <span>Livraison</span>
            <span><?php echo number_format($shippingFee, 2, ',', ' '); ?> €</span>
          </div>
          <div class="summary-row total">
            <span>Total</span>
            <span><?php echo number_format($total, 2, ',', ' '); ?> €</span>
          </div>
          <div class="delivery-options">
            <h3>Options de livraison</h3>
            <div class="delivery-option">
              <input type="radio" id="delivery-standard" name="delivery_option" value="standard" checked>
              <label for="delivery-standard">Livraison standard (2-3 jours ouvrés)</label>
            </div>
            <div class="delivery-option">
              <input type="radio" id="delivery-express" name="delivery_option" value="express">
              <label for="delivery-express">Livraison express (24h) +3,00 €</label>
            </div>
            <div class="delivery-option">
              <input type="radio" id="pickup" name="delivery_option" value="pickup">
              <label for="pickup">Retrait en boutique (gratuit)</label>
            </div>
          </div>
          <a href="paiement.php" class="btn-primary checkout-btn">Procéder au paiement</a>
          <div class="continue-shopping">
            <a href="boutique.php"><i class="fas fa-arrow-left"></i> Continuer mes achats</a>
          </div>
        </div>
      </div>
    </form>
    <?php else: ?>
    <div class="empty-cart">
      <i class="fas fa-shopping-bag"></i>
      <p>Votre panier est vide</p>
      <a href="boutique.php" class="btn-primary">Continuer mes achats</a>
    </div>
    <?php endif; ?>
  </section>

  <section class="recommended-products">
    <h2>Vous pourriez aussi aimer</h2>
    <div class="products-grid">
      <?php
      
      try {
          $stmt = $conn->prepare("
              SELECT * FROM items
              WHERE is_featured = 1
              LIMIT 4
          ");
          $stmt->execute();
          $recommendedProducts = $stmt->fetchAll();
          
          foreach ($recommendedProducts as $product):
      ?>
      <div class="product-card">
        <div class="product-image">
          <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
          <div class="product-overlay">
            <a href="produit.php?id=<?php echo $product['id']; ?>" class="btn-view">Voir détails</a>
            <button class="btn-add-cart" data-id="<?php echo $product['id']; ?>">Ajouter au panier</button>
          </div>
        </div>
        <div class="product-info">
          <h3><?php echo htmlspecialchars($product['name']); ?></h3>
          <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100) . '...'); ?></p>
          <p class="product-price"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</p>
        </div>
      </div>
      <?php
          endforeach;
      } catch(PDOException $e) {
        
      }
      ?>
    </div>
  </section>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    
    const minusButtons = document.querySelectorAll('.quantity-btn.minus');
    const plusButtons = document.querySelectorAll('.quantity-btn.plus');
    
    minusButtons.forEach(button => {
      button.addEventListener('click', function() {
        const input = this.nextElementSibling;
        let value = parseInt(input.value);
        if (value > 1) {
          input.value = value - 1;
        }
      });
    });
    
    plusButtons.forEach(button => {
      button.addEventListener('click', function() {
        const input = this.previousElementSibling;
        let value = parseInt(input.value);
        let max = parseInt(input.getAttribute('max'));
        if (value < max) {
          input.value = value + 1;
        }
      });
    });
    
   
const addToCartButtons = document.querySelectorAll('.btn-add-cart');

addToCartButtons.forEach(button => {
  button.addEventListener('click', function() {
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
       
        const cartCounters = document.querySelectorAll('.cart-count');
        cartCounters.forEach(counter => {
          counter.textContent = data.cart_count;
        });
        
       
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
  
const removeButtons = document.querySelectorAll('.remove-item');
removeButtons.forEach(button => {
  button.addEventListener('click', function() {
    const itemId = this.getAttribute('data-id');
    
   
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'panier.php';
    
    const itemIdInput = document.createElement('input');
    itemIdInput.type = 'hidden';
    itemIdInput.name = 'item_id';
    itemIdInput.value = itemId;
    
    const removeItemInput = document.createElement('input');
    removeItemInput.type = 'hidden';
    removeItemInput.name = 'remove_item';
    removeItemInput.value = '1';
    
    form.appendChild(itemIdInput);
    form.appendChild(removeItemInput);
    document.body.appendChild(form);
    
    form.submit();
  });
});
</script>

<?php require_once 'includes/footer.php'; ?>