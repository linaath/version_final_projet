<?php
$pageTitle = "Détails de la commande";
require_once 'includes/header.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mes-commandes.php");
    exit;
}

$orderId = intval($_GET['id']);

try {
    $conn = connectDB();
    $stmt = $conn->prepare("CALL GetOrderDetails(?, ?)");
    $stmt->bindParam(1, $orderId, PDO::PARAM_INT);
    $stmt->bindParam(2, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    
    $orderInfo = $stmt->fetch();
    if (!$orderInfo) {
        header("Location: mes-commandes.php");
        exit;
    }
    

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
    <h1>Détails de la commande #<?php echo $orderId; ?></h1>
    <p>Passée le <?php echo date('d/m/Y', strtotime($orderInfo['order_date'])); ?></p>
  </div>

  <section class="order-details-section">
    <div class="order-status-bar">
      <div class="status-step <?php echo in_array($orderInfo['status'], ['pending', 'processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
        <div class="step-icon"><i class="fas fa-check"></i></div>
        <div class="step-label">Commande confirmée</div>
      </div>
      <div class="status-step <?php echo in_array($orderInfo['status'], ['processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
        <div class="step-icon"><i class="fas fa-box"></i></div>
        <div class="step-label">En préparation</div>
      </div>
      <div class="status-step <?php echo in_array($orderInfo['status'], ['shipped', 'delivered']) ? 'active' : ''; ?>">
        <div class="step-icon"><i class="fas fa-truck"></i></div>
        <div class="step-label">Expédiée</div>
      </div>
      <div class="status-step <?php echo $orderInfo['status'] === 'delivered' ? 'active' : ''; ?>">
        <div class="step-icon"><i class="fas fa-home"></i></div>
        <div class="step-label">Livrée</div>
      </div>
    </div>

    <div class="order-details-container">
      <div class="order-info-block">
        <div class="info-card">
          <h3>Informations de commande</h3>
          <p><strong>Numéro de commande:</strong> #<?php echo $orderId; ?></p>
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
            (<?php echo $orderInfo['payment_status']; ?>)
          </p>
        </div>
        <div class="info-card">
          <h3>Adresse de livraison</h3>
          <p><?php echo $orderInfo['shipping_address']; ?></p>
          <p><?php echo $orderInfo['shipping_postal_code'] . ' ' . $orderInfo['shipping_city']; ?></p>
          <p><?php echo $orderInfo['shipping_country']; ?></p>
        </div>
      </div>

      <div class="order-items-block">
        <h3>Articles commandés</h3>
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
    <span><?php echo isset($orderTotals['subtotal']) ? number_format($orderTotals['subtotal'], 2, ',', ' ') : '0,00'; ?> €</span>
  </div>
  <div class="total-row">
    <span>Livraison</span>
    <span><?php echo isset($orderTotals['shipping_fee']) ? number_format($orderTotals['shipping_fee'], 2, ',', ' ') : '0,00'; ?> €</span>
  </div>
  <div class="total-row total">
    <span>Total</span>
    <span><?php echo isset($orderTotals['total_amount']) ? number_format($orderTotals['total_amount'], 2, ',', ' ') : '0,00'; ?> €</span>
  </div>
</div>
      </div>
    </div>

    <div class="order-actions">
      <a href="mes-commandes.php" class="btn-secondary">Retour à mes commandes</a>
      <?php if ($orderInfo['status'] === 'pending' || $orderInfo['status'] === 'processing'): ?>
      <form method="post" action="annuler-commande.php">
        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
        <button type="submit" class="btn-outline" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ?')">Annuler la commande</button>
      </form>
      <?php endif; ?>
      <?php if ($orderInfo['status'] === 'delivered'): ?>
      <a href="ajouter-avis.php?order_id=<?php echo $orderId; ?>" class="btn-primary">Laisser un avis</a>
      <?php endif; ?>
    </div>
  </section>
</main>
<style>


:root {
    --primary-color: #b39465;
    --secondary-color: #483728;
    --light-color: #f8f5f0;
    --dark-color: #2c2418;
    --border-color: #e0d5c8;
    --status-pending: #f0ad4e;
    --status-processing: #5bc0de;
    --status-shipped: #337ab7;
    --status-delivered: #5cb85c;
    --status-cancelled: #d9534f;
}

main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Montserrat', Arial, sans-serif;
    color: var(--dark-color);
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    color: var(--secondary-color);
    margin-bottom: 10px;
}

.page-header p {
    color: #777;
    font-size: 16px;
}


.order-status-bar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    padding: 0 20px;
}

.status-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.status-step:not(:last-child):after {
    content: '';
    position: absolute;
    top: 20px;
    right: -50%;
    width: 100%;
    height: 2px;
    background-color: var(--border-color);
    z-index: -1;
}

.step-icon {
    width: 40px;
    height: 40px;
    background-color: var(--light-color);
    border: 2px solid var(--border-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    color: #aaa;
}

.status-step.active .step-icon {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.step-label {
    font-size: 14px;
    color: #888;
    text-align: center;
}

.status-step.active .step-label {
    color: var(--secondary-color);
    font-weight: 600;
}


.order-details-container {
    display: flex;
    flex-direction: column;
    gap: 30px;
    margin-bottom: 30px;
}

@media (min-width: 768px) {
    .order-info-block {
        display: flex;
        gap: 20px;
    }
    
    .info-card {
        flex: 1;
    }
}


.info-card {
    background-color: var(--light-color);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

.info-card h3 {
    font-family: 'Playfair Display', serif;
    color: var(--secondary-color);
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}

.info-card p {
    margin-bottom: 8px;
}

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    color: white;
}

.status-pending {
    background-color: var(--status-pending);
}

.status-processing {
    background-color: var(--status-processing);
}

.status-shipped {
    background-color: var(--status-shipped);
}

.status-delivered {
    background-color: var(--status-delivered);
}

.status-cancelled {
    background-color: var(--status-cancelled);
}


.order-items-block {
    background-color: var(--light-color);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.order-items-block h3 {
    font-family: 'Playfair Display', serif;
    color: var(--secondary-color);
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    margin-right: 15px;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-details h4 {
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--secondary-color);
}

.item-price {
    color: #777;
    font-size: 14px;
}

.item-total {
    font-weight: 600;
    color: var(--secondary-color);
    font-size: 16px;
}

.order-totals {
    background-color: #f5f0e8;
    border-radius: 8px;
    padding: 15px 20px;
    margin-top: 20px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 15px;
}

.total-row.total {
    padding-top: 15px;
    margin-top: 10px;
    border-top: 1px solid var(--border-color);
    font-weight: 700;
    font-size: 18px;
    color: var(--secondary-color);
}

.order-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn-primary, .btn-secondary, .btn-outline {
    padding: 12px 24px;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    color: white;
}

.btn-secondary {
    background-color: var(--light-color);
    color: var(--secondary-color);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background-color: #e0d5c8;
}

.btn-outline {
    background-color: transparent;
    color: var(--secondary-color);
    border: 1px solid var(--secondary-color);
}

.btn-outline:hover {
    background-color: rgba(179, 148, 101, 0.1);
}


@media (max-width: 768px) {
    .order-status-bar {
        padding: 0;
    }
    
    .step-label {
        font-size: 12px;
    }
    
    .order-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .btn-primary, .btn-secondary, .btn-outline {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .order-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .item-image {
        margin-bottom: 10px;
    }
    
    .item-total {
        margin-top: 10px;
        align-self: flex-end;
    }
}
</style>
<?php require_once 'includes/footer.php'; ?>