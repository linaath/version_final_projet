<?php
$pageTitle = "Mes commandes";
require_once 'includes/header.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}


try {
    $conn = connectDB();
    
    
    $stmt = $conn->prepare("CALL GetOrderHistory(?)");
    $stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $orders = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération de l'historique des commandes: " . $e->getMessage());
}
?>

<main>
  <div class="page-header">
    <h1>Mes commandes</h1>
    <p>Suivez l'état de vos commandes et consultez votre historique d'achats</p>
  </div>

  <section class="orders-section">
    <?php if (count($orders) > 0): ?>
    <div class="orders-list">
      <?php foreach ($orders as $order): ?>
      <div class="order-card">
        <div class="order-header">
          <div class="order-info">
            <h3>Commande #<?php echo $order['order_id']; ?></h3>
            <p class="order-date">Passée le <?php echo date('d/m/Y', strtotime($order['order_date'])); ?></p>
          </div>
          <div class="order-status">
            <span class="status-badge status-<?php echo $order['status']; ?>">
              <?php
              switch ($order['status']) {
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
                      echo $order['status'];
              }
              ?>
            </span>
          </div>
        </div>
        <div class="order-details">
          <div class="order-meta">
            <p><strong>Total:</strong> <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> €</p>
            <p><strong>Articles:</strong> <?php echo $order['total_items']; ?></p>
            <p><strong>Paiement:</strong> 
              <?php echo $order['payment_method'] === 'card' ? 'Carte bancaire' : 'PayPal'; ?>
              (<?php echo $order['payment_status']; ?>)
            </p>
          </div>
          <div class="order-actions">
            <a href="commande-details.php?id=<?php echo $order['order_id']; ?>" class="btn-secondary">Voir les détails</a>
            <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
            <form method="post" action="annuler-commande.php">
              <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
              <button type="submit" class="btn-outline" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ?')">Annuler la commande</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-orders">
      <div class="no-orders-icon">
        <i class="fas fa-shopping-bag"></i>
      </div>
      <h2>Vous n'avez pas encore passé de commande</h2>
      <p>Explorez notre boutique et découvrez nos délicieuses pâtisseries.</p>
      <a href="boutique.php" class="btn-primary">Découvrir nos produits</a>
    </div>
    <?php endif; ?>
  </section>
</main>
<style>
 

:root {
    --primary-color: #9a7b4f;
    --primary-dark: #7a5f3d;
    --primary-light: #c4ad8f;
    --secondary-color: #4a3c2e;
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


.orders-section {
    margin-bottom: 40px;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}


.order-card {
    background-color: var(--light-color);
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.order-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f0e9e0;
    border-bottom: 1px solid var(--border-color);
}

.order-info h3 {
    margin: 0;
    font-family: 'Playfair Display', serif;
    color: var(--secondary-color);
    font-size: 18px;
}

.order-date {
    margin: 5px 0 0;
    font-size: 14px;
    color: #777;
}


.status-badge {
    display: inline-block;
    padding: 5px 12px;
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


.order-details {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.order-meta {
    flex: 1;
    min-width: 200px;
}

.order-meta p {
    margin: 8px 0;
    font-size: 15px;
}

.order-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}


.no-orders {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 50px 20px;
    background-color: var(--light-color);
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.no-orders-icon {
    width: 80px;
    height: 80px;
    background-color: #f0e9e0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    color: var(--primary-color);
    font-size: 30px;
}

.no-orders h2 {
    font-family: 'Playfair Display', serif;
    color: var(--secondary-color);
    margin-bottom: 15px;
}

.no-orders p {
    color: #777;
    margin-bottom: 25px;
    max-width: 500px;
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
    .order-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-status {
        margin-top: 10px;
    }
    
    .order-details {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .order-actions .btn-secondary,
    .order-actions .btn-outline {
        flex: 1;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .order-actions {
        flex-direction: column;
    }
    
    .order-actions form {
        width: 100%;
    }
    
    .btn-secondary, .btn-outline {
        width: 100%;
    }
}
</style>
<?php require_once 'includes/footer.php'; ?>