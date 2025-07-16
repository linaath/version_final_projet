<?php
$pageTitle = "Paiement";
require_once 'includes/header.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}


try {
    $conn = connectDB();
    
    $stmt = $conn->prepare("
        SELECT * FROM users WHERE id = :user_id
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    $stmt = $conn->prepare("
        SELECT c.id as cart_id FROM carts c WHERE c.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header("Location: panier.php");
        exit;
    }
    
    $cart = $stmt->fetch();
    $cartId = $cart['cart_id'];
    
    $stmt = $conn->prepare("
        SELECT ci.id, ci.quantity, i.id as item_id, i.name, i.price, i.image_url
        FROM cart_items ci
        JOIN items i ON ci.item_id = i.id
        WHERE ci.cart_id = :cart_id
    ");
    $stmt->bindParam(':cart_id', $cartId);
    $stmt->execute();
    
    $cartItems = $stmt->fetchAll();
    
   
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    echo "<!-- Debug: Sous-total calculé = " . $subtotal . " -->";
    $shippingFee = 5.00;
    
    $discount = 0;
    if (isset($_SESSION['coupon'])) {
        $discount = $subtotal * ($_SESSION['coupon']['discount_percent'] / 100);
    }
    
    $total = $subtotal - $discount + $shippingFee;
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des informations: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postalCode = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    $paymentMethod = $_POST['payment_method'];
    $notes = trim($_POST['notes']);
    
   
    if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || 
        empty($address) || empty($city) || empty($postalCode) || empty($country)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            
            $stmt = $conn->prepare("
                UPDATE users
                SET firstname = :firstname, lastname = :lastname, email = :email, phone = :phone,
                    address = :address, city = :city, postal_code = :postal_code, country = :country
                WHERE id = :user_id
            ");
            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':postal_code', $postalCode);
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
          
            $orderId = 0;
            
            
            $stmt = $conn->prepare("CALL FinalizeOrder(?, ?, ?, ?, ?, ?, ?, @p_order_id)");
            $stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(2, $address, PDO::PARAM_STR);
            $stmt->bindParam(3, $city, PDO::PARAM_STR);
            $stmt->bindParam(4, $postalCode, PDO::PARAM_STR);
            $stmt->bindParam(5, $country, PDO::PARAM_STR);
            $stmt->bindParam(6, $paymentMethod, PDO::PARAM_STR);
            $stmt->bindParam(7, $notes, PDO::PARAM_STR);
            $stmt->execute();
            
           
            $stmt = $conn->query("SELECT @p_order_id as order_id");
            $result = $stmt->fetch();
            $orderId = $result['order_id'];
            
            if (isset($_SESSION['coupon'])) {
                unset($_SESSION['coupon']);
            }
            
            resetCartCount();
            
            
            header("Location: confirmation.php?order_id=$orderId");
            exit;
        } catch(PDOException $e) {
            $error = "Une erreur est survenue lors de la finalisation de la commande: " . $e->getMessage();
        }
    }
}
?>

<main>
  <div class="page-header">
    <h1>Paiement</h1>
    <p>Finalisez votre commande en toute sécurité</p>
  </div>

  <?php if (isset($error)): ?>
  <div class="alert alert-error">
    <?php echo $error; ?>
  </div>
  <?php endif; ?>

  <section class="checkout-section">
    <form method="post" action="paiement.php" class="checkout-form">
      <div class="checkout-container">
        <div class="checkout-details">
          <div class="checkout-block">
            <h2>Informations personnelles</h2>
            <div class="form-row">
              <div class="form-group">
                <label for="firstname">Prénom *</label>
                <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>" required>
              </div>
              <div class="form-group">
                <label for="lastname">Nom *</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
              </div>
              <div class="form-group">
                <label for="phone">Téléphone *</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
              </div>
            </div>
          </div>

          <div class="checkout-block">
            <h2>Adresse de livraison</h2>
            <div class="form-group">
              <label for="address">Adresse *</label>
              <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="city">Ville *</label>
                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
              </div>
              <div class="form-group">
                <label for="postal_code">Code postal *</label>
                <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label for="country">Pays *</label>
              <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>" required>
            </div>
          </div>

          <div class="checkout-block">
            <h2>Mode de paiement</h2>
            <div class="payment-methods">
              <div class="payment-method">
                <input type="radio" id="payment-card" name="payment_method" value="card" checked>
                <label for="payment-card">
                  <span class="payment-icon"><i class="fas fa-credit-card"></i></span>
                  <span class="payment-name">Carte bancaire</span>
                </label>
              </div>
              <div class="payment-method">
                <input type="radio" id="payment-paypal" name="payment_method" value="paypal">
                <label for="payment-paypal">
                  <span class="payment-icon"><i class="fab fa-paypal"></i></span>
                  <span class="payment-name">PayPal</span>
                </label>
              </div>
            </div>

            <div id="card-payment-form" class="payment-form active">
              <div class="form-row">
                <div class="form-group">
                  <label for="card-number">Numéro de carte</label>
                  <input type="text" id="card-number" placeholder="1234 5678 9012 3456">
                </div>
                <div class="form-group">
                  <label for="card-name">Nom sur la carte</label>
                  <input type="text" id="card-name" placeholder="John Doe">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="card-expiry">Date d'expiration</label>
                  <input type="text" id="card-expiry" placeholder="MM/AA">
                </div>
                <div class="form-group">
                  <label for="card-cvc">CVC</label>
                  <input type="text" id="card-cvc" placeholder="123">
                </div>
              </div>
            </div>

            <div id="paypal-payment-form" class="payment-form">
              <p>Vous serez redirigé vers PayPal pour finaliser votre paiement.</p>
            </div>
          </div>

          <div class="checkout-block">
            <h2>Informations supplémentaires</h2>
            <div class="form-group">
              <label for="notes">Notes de commande (facultatif)</label>
              <textarea id="notes" name="notes" rows="4" placeholder="Instructions spéciales pour la livraison, etc."></textarea>
            </div>
          </div>
        </div>

        <div class="checkout-summary">
          <div class="summary-block">
            <h2>Récapitulatif de la commande</h2>
            <div class="cart-items">
              <?php foreach ($cartItems as $item): ?>
              <div class="summary-item">
                <div class="item-image">
                  <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                  <span class="item-quantity"><?php echo $item['quantity']; ?></span>
                </div>
                <div class="item-details">
                  <h4><?php echo $item['name']; ?></h4>
                  <p class="item-price"><?php echo number_format($item['price'], 2, ',', ' '); ?> €</p>
                </div>
                <div class="item-total">
                  <?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> €
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="summary-totals">
              <div class="summary-row">
                <span>Sous-total</span>
                <span><?php echo number_format($subtotal, 2, ',', ' '); ?> €</span>
              </div>
              <?php if ($discount > 0): ?>
              <div class="summary-row discount">
                <span>Réduction</span>
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
            </div>
          </div>
          <div class="checkout-actions">
            <button type="submit" class="btn-primary btn-full">Payer <?php echo number_format($total, 2, ',', ' '); ?> €</button>
            <p class="secure-payment">
              <i class="fas fa-lock"></i> Paiement sécurisé
            </p>
          </div>
        </div>
      </div>
    </form>
  </section>
</main>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const paymentForms = document.querySelectorAll('.payment-form');
    
    paymentMethods.forEach(function(method) {
      method.addEventListener('change', function() {
        paymentForms.forEach(function(form) {
          form.classList.remove('active');
        });
        
        if (this.value === 'card') {
          document.getElementById('card-payment-form').classList.add('active');
        } else if (this.value === 'paypal') {
          document.getElementById('paypal-payment-form').classList.add('active');
        }
      });
    });
  });
</script>

<?php require_once 'includes/footer.php'; ?>