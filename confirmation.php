<?php
$pageTitle = "Confirmation de commande";
require_once 'includes/header.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}


if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$orderId = intval($_GET['order_id']);


try {
    $conn = connectDB();
    $stmt = $conn->prepare("CALL GetOrderDetails(?, ?)");
    $stmt->bindParam(1, $orderId, PDO::PARAM_INT);
    $stmt->bindParam(2, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
  
    $orderInfo = $stmt->fetch();
    $stmt->nextRowset();
    $orderItems = $stmt->fetchAll();
    $stmt->nextRowset();
    $orderTotals = $stmt->fetch();
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des détails de la commande: " . $e->getMessage());
}
?>

<main>
  <div class="page-header">
    <h1>Confirmation de commande</h1>
    <p>Merci pour votre commande !</p>
  </div>

  <section class="confirmation-section">
    <div class="confirmation-message">
      <div class="confirmation-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <h2>Votre commande a été confirmée</h2>
      <p>Un email de confirmation a été envoyé à <strong><?php echo $_SESSION['user_email']; ?></strong></p>
      <p class="order-number">Numéro de commande: <strong>#<?php echo $orderId; ?></strong></p>
    </div>

    <div class="order-details">
      <div class="order-info">
        <div class="info-block">
          <h3>Informations de commande</h3>
          <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($orderInfo['order_date'])); ?></p>
          <p><strong>Statut:</strong> 
            <span class="status-badge status-<?php echo $orderInfo['status']; ?>">
              <?php
              switch ($orderInfo['status']) {
                  case 'pending':
                      echo 'En attente';
                      break;
                  case 'processing':
                      echo 'En traitement';
                      break;
                  case 'shipped':
                      echo 'Expédiée';
                      break;
                  case 'delivered':
                      echo 'Livrée';
                      break;
                  case 'cancelled':
                      echo 'Annulée';
                      break;
                  default:
                      echo $orderInfo['status'];
              }
              ?>
            </span>
          </p>
          <p><strong>Paiement:</strong> 
            <?php echo $orderInfo['payment_method'] === 'card' ? 'Carte bancaire' : 'PayPal'; ?>
          </p>
        </div>
        <div class="info-block">
          <h3>Adresse de livraison</h3>
          <p><?php echo $orderInfo['shipping_address']; ?></p>
          <p><?php echo $orderInfo['shipping_postal_code'] . ' ' . $orderInfo['shipping_city']; ?></p>
          <p><?php echo $orderInfo['shipping_country']; ?></p>
        </div>
      </div>

      <div class="order-summary">
        <h3>Récapitulatif de la commande</h3>
        <div class="order-items">
          <?php foreach ($orderItems as $item): ?>
          <div class="order-item">
            <div class="item-image">
              <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['item_name']; ?>">
            </div>
            <div class="item-details">
              <h4><?php echo $item['item_name']; ?></h4>
              <p class="item-price"><?php echo number_format($item['unit_price'], 2, ',', ' '); ?> € x <?php echo $item['quantity']; ?></p>
            </div>
            <div class="item-total">
              <?php echo number_format($item['subtotal'], 2, ',', ' '); ?> €
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="order-totals">
          <div class="total-row">
            <span>Sous-total</span>
            <span><?php echo number_format($orderTotals['subtotal'], 2, ',', ' '); ?> €</span>
          </div>
          <div class="total-row">
            <span>Livraison</span>
            <span><?php echo number_format($orderTotals['shipping_fee'], 2, ',', ' '); ?> €</span>
          </div>
          <div class="total-row total">
            <span>Total</span>
            <span><?php echo number_format($orderTotals['total_amount'], 2, ',', ' '); ?> €</span>
          </div>
        </div>
      </div>
    </div>

    <div class="confirmation-actions">
      <a href="mes-commandes.php" class="btn-secondary">Voir mes commandes</a>
      <a href="boutique.php" class="btn-primary">Continuer mes achats</a>
    </div>
  </section>

  <section class="recommended-products">
    <div class="section-header">
      <h2>Vous pourriez aussi aimer</h2>
    </div>
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
          <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
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
      <?php
          endforeach;
      } catch(PDOException $e) {
        
      }
      ?>
    </div>
  </section>
</main>

<?php require_once 'includes/footer.php'; ?>