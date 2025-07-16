<?php
$pageTitle = "Gestion des commandes";

session_start();

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}


if (!isLoggedIn() || !isAdmin()) {
  header("Location: ../login.php");
  exit;
}


try {
  $conn = connectDB();

  $stmt = $conn->query("
      SELECT o.*, u.firstname, u.lastname, u.email,
      COUNT(oi.id) as item_count, SUM(oi.quantity) as total_items
      FROM orders o
      JOIN users u ON o.user_id = u.id
      LEFT JOIN order_items oi ON o.id = oi.order_id
      GROUP BY o.id
      ORDER BY o.created_at DESC
  ");
  $orders = $stmt->fetchAll();
  
} catch(PDOException $e) {
  die("Erreur lors de la récupération des commandes: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $orderId = intval($_POST['order_id']);
  $status = $_POST['status'];
  
  try {
    $stmt = $conn->prepare("
        UPDATE orders
        SET status = :status
        WHERE id = :id
    ");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $orderId);
    $stmt->execute();

    header("Location: orders.php?success=1");
    exit;
  } catch(PDOException $e) {
    $error = "Erreur lors de la mise à jour du statut de la commande: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle . ' | Délices Sucrés' : 'Délices Sucrés'; ?></title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="admin-body">
  <div class="admin-container">
    <aside class="admin-sidebar">
      <nav class="admin-nav">
        <ul>
          <li>
            <a href="index.php">
              <i class="fas fa-tachometer-alt"></i>
              <span>Tableau de bord</span>
            </a>
          </li>
          <li>
            <a href="products.php">
              <i class="fas fa-box"></i>
              <span>Produits</span>
            </a>
          </li>
          <li>
            <a href="categories.php">
              <i class="fas fa-tags"></i>
              <span>Catégories</span>
            </a>
          </li>
          <li class="active">
            <a href="orders.php">
              <i class="fas fa-shopping-cart"></i>
              <span>Commandes</span>
            </a>
          </li>
          <li>
            <a href="users.php">
              <i class="fas fa-users"></i>
              <span>Utilisateurs</span>
            </a>
          </li>
          <li>
            <a href="settings.php">
              <i class="fas fa-cog"></i>
              <span>Paramètres</span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>

<main class="admin-main">
  <div class="admin-header-section">
    <h1>Gestion des commandes</h1>
    <p>Gérez les commandes passées par vos clients</p>
  </div>

  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
  <div class="alert alert-success">
    Le statut de la commande a été mis à jour avec succès.
  </div>
  <?php endif; ?>

  <?php if (isset($error)): ?>
  <div class="alert alert-error">
    <?php echo $error; ?>
  </div>
  <?php endif; ?>

  <div class="admin-content">
    <div class="admin-section">
      <div class="section-header">
        <h2>Liste des commandes</h2>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Date</th>
              <th>Client</th>
              <th>Articles</th>
              <th>Total</th>
              <th>Statut</th>
              <th>Paiement</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($orders) > 0): ?>
              <?php foreach ($orders as $order): ?>
              <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                <td>
                  <div><?php echo $order['firstname'] . ' ' . $order['lastname']; ?></div>
                  <div class="text-muted"><?php echo $order['email']; ?></div>
                </td>
                <td><?php echo $order['total_items']; ?> (<?php echo $order['item_count']; ?> produits)</td>
                <td><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> €</td>
                <td>
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
                </td>
                <td>
                  <span class="payment-badge payment-<?php echo $order['payment_status']; ?>">
                    <?php echo $order['payment_status']; ?>
                  </span>
                  <div class="text-muted"><?php echo $order['payment_method']; ?></div>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-icon view-order-btn" data-id="<?php echo $order['id']; ?>" title="Voir détails">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon edit-status-btn" data-id="<?php echo $order['id']; ?>" data-status="<?php echo $order['status']; ?>" title="Modifier le statut">
                      <i class="fas fa-edit"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center">Aucune commande trouvée</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>


  <div class="modal" id="order-details-modal">
    <div class="modal-content modal-lg">
      <div class="modal-header">
        <h2 id="order-details-title">Détails de la commande #<span id="order-id"></span></h2>
        <button class="modal-close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="order-details-container">
          <div class="order-info-block">
            <div class="info-card">
              <h3>Informations de commande</h3>
              <p><strong>Date:</strong> <span id="order-date"></span></p>
              <p><strong>Statut:</strong> <span id="order-status"></span></p>
              <p><strong>Paiement:</strong> <span id="order-payment"></span></p>
              <p><strong>Notes:</strong> <span id="order-notes"></span></p>
            </div>
            <div class="info-card">
              <h3>Informations client</h3>
              <p><strong>Nom:</strong> <span id="customer-name"></span></p>
              <p><strong>Email:</strong> <span id="customer-email"></span></p>
              <p><strong>Téléphone:</strong> <span id="customer-phone"></span></p>
            </div>
            <div class="info-card">
              <h3>Adresse de livraison</h3>
              <p id="shipping-address"></p>
              <p id="shipping-city"></p>
              <p id="shipping-country"></p>
            </div>
          </div>
          <div class="order-items-block">
            <h3>Articles commandés</h3>
            <div class="order-items" id="order-items-list">
            </div>
            <div class="order-totals">
              <div class="total-row">
                <span>Sous-total</span>
                <span id="order-subtotal"></span>
              </div>
              <div class="total-row">
                <span>Livraison</span>
                <span id="order-shipping"></span>
              </div>
              <div class="total-row total">
                <span>Total</span>
                <span id="order-total"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  
  <div class="modal" id="status-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Modifier le statut de la commande</h2>
        <button class="modal-close">&times;</button>
      </div>
      <div class="modal-body">
        <form method="post" action="orders.php">
          <input type="hidden" name="order_id" id="status-order-id">
          
          <div class="form-group">
            <label for="status">Statut</label>
            <select id="status" name="status" required>
              <option value="pending">En attente</option>
              <option value="processing">En traitement</option>
              <option value="shipped">Expédiée</option>
              <option value="delivered">Livrée</option>
              <option value="cancelled">Annulée</option>
            </select>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary modal-close-btn">Annuler</button>
            <button type="submit" name="update_status" class="btn btn-primary">Mettre à jour</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
  </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
:root {
  --site-primary: #9a7b4f; 
  --site-primary-light: #c4ad8a;
  --site-primary-dark: #7a5f35;
  --site-secondary: #4a3c2e;
  --site-bg: #f8f9fa;
  --site-text: #333;
  --site-text-light: #6c757d;
  --site-border: #dee2e6;
  --site-success: #28a745;
  --site-warning: #ffc107;
  --site-danger: #dc3545;
  --site-info: #17a2b8;
  --site-card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  --site-transition: all 0.3s ease;
  --radius-md: 10px;
}


body {
  background-color: var(--site-bg);
  font-family: "Inter", "Segoe UI", Roboto, sans-serif;
  color: var(--site-text);
  line-height: 1.6;
  margin: 0;
  padding: 0;
}

.admin-body {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.admin-container {
  display: flex;
  flex: 1;
}

.admin-sidebar {
  width: 260px;
  background-color: white;
  border-right: 1px solid var(--site-border);
  height: 100%;
  position: sticky;
  top: 0;
  height: calc(100vh);
  overflow-y: auto;
  transition: var(--site-transition);
  z-index: 99;
}

.admin-nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.admin-nav li {
  margin: 0.25rem 0;
}

.admin-nav a {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  color: var(--site-text);
  text-decoration: none;
  font-weight: 500;
  transition: var(--site-transition);
  border-left: 3px solid transparent;
}

.admin-nav a:hover {
  background-color: var(--site-bg);
  color: var(--site-primary);
}

.admin-nav li.active a {
  border-left-color: var(--site-primary);
  color: var(--site-primary);
  background-color: rgba(154, 123, 79, 0.05);
}

.admin-main {
  flex: 1;
  padding: 2rem;
  overflow-x: hidden;
}

.admin-header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.admin-header-section h1 {
  font-size: 1.8rem;
  color: var(--site-text);
  margin: 0;
  font-weight: 600;
}

.admin-actions {
  display: flex;
  gap: 1rem;
}

.admin-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background-color: white;
  border-radius: var(--radius-md);
  padding: 1.5rem;
  box-shadow: var(--site-card-shadow);
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: var(--site-transition);
  border: 1px solid transparent;
}

.stat-card:hover {
  transform: translateY(-3px);
  border-color: rgba(154, 123, 79, 0.2);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 60px;
  height: 60px;
  background-color: rgba(154, 123, 79, 0.1);
  color: var(--site-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  transition: var(--site-transition);
}

.stat-card:hover .stat-icon {
  background-color: var(--site-primary);
  color: white;
}

.stat-content h3 {
  font-size: 1rem;
  margin-bottom: 0.5rem;
  color: var(--site-text-light);
  font-weight: 500;
}

.stat-number {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--site-text);
  margin: 0;
  margin-bottom: 0.25rem;
}

.stat-info {
  margin: 0;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-info.up {
  color: var(--site-success);
}

.stat-info.down {
  color: var(--site-danger);
}

.admin-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.admin-section, .admin-recent-orders {
  background-color: white;
  border-radius: var(--radius-md);
  padding: 1.5rem;
  box-shadow: var(--site-card-shadow);
  margin-bottom: 2rem;
  border: 1px solid transparent;
  transition: var(--site-transition);
}

.admin-section:hover, .admin-recent-orders:hover {
  border-color: rgba(154, 123, 79, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  position: relative;
}

.section-header h2 {
  font-size: 1.3rem;
  color: var(--site-text);
  margin: 0;
  font-weight: 600;
  position: relative;
  padding-bottom: 0.5rem;
}

.section-header h2::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 50px;
  height: 2px;
  background-color: var(--site-primary);
}

.view-all {
  color: var(--site-primary);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
  transition: var(--site-transition);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-all:hover {
  color: var(--site-primary-dark);
}


.table-responsive {
  overflow-x: auto;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table th,
.admin-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--site-border);
}

.admin-table th {
  font-weight: 600;
  color: var(--site-text);
  background-color: var(--site-bg);
}

.admin-table tr:last-child td {
  border-bottom: none;
}

.admin-table tr:hover {
  background-color: rgba(154, 123, 79, 0.05);
}
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 500;
  transition: var(--site-transition);
}

.status-badge.pending, .status-badge.status-pending {
  background-color: #ffeeba;
  color: #856404;
}

.status-badge.processing, .status-badge.status-processing {
  background-color: #b8daff;
  color: #004085;
}

.status-badge.shipped, .status-badge.status-shipped {
  background-color: #c3e6cb;
  color: #155724;
}

.status-badge.delivered, .status-badge.status-delivered {
  background-color: #d4edda;
  color: #155724;
}

.status-badge.cancelled, .status-badge.status-cancelled {
  background-color: #f5c6cb;
  color: #721c24;
}

.payment-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 500;
}

.payment-paid {
  background-color: #d4edda;
  color: #155724;
}

.payment-pending {
  background-color: #ffeeba;
  color: #856404;
}

.payment-failed {
  background-color: #f5c6cb;
  color: #721c24;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: var(--site-bg);
  color: var(--site-text);
  transition: var(--site-transition);
  border: 1px solid var(--site-border);
  text-decoration: none;
  cursor: pointer;
}

.btn-icon:hover {
  background-color: var(--site-primary);
  color: white;
  transform: translateY(-2px);
}

.btn-icon.view:hover, .view-order-btn:hover {
  background-color: var(--site-info);
}

.btn-icon.edit:hover, .edit-status-btn:hover {
  background-color: var(--site-warning);
}

.btn-icon.delete:hover {
  background-color: var(--site-danger);
}
.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.action-card {
  background-color: var(--site-bg);
  border-radius: var(--radius-md);
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: var(--site-transition);
  border: 1px solid transparent;
  text-decoration: none;
  color: var(--site-text);
}

.action-card:hover {
  background-color: var(--site-primary);
  color: white;
  transform: translateY(-3px);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.action-card:hover .action-icon {
  background-color: white;
  color: var(--site-primary);
}

.action-icon {
  width: 50px;
  height: 50px;
  background-color: rgba(154, 123, 79, 0.1);
  color: var(--site-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  transition: var(--site-transition);
}

.action-content h3 {
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.action-content p {
  font-size: 0.9rem;
  color: var(--site-text-light);
  transition: var(--site-transition);
  margin: 0;
}

.action-card:hover .action-content p {
  color: rgba(255, 255, 255, 0.8);
}
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-size: 0.95rem;
  font-weight: 500;
  text-decoration: none;
  transition: var(--site-transition);
  cursor: pointer;
  border: none;
}

.btn-primary {
  background-color: var(--site-primary);
  color: white;
}

.btn-primary:hover {
  background-color: var(--site-primary-dark);
  transform: translateY(-2px);
}

.btn-secondary {
  background-color: white;
  color: var(--site-text);
  border: 1px solid var(--site-border);
}

.btn-secondary:hover {
  background-color: var(--site-bg);
  border-color: var(--site-text-light);
  transform: translateY(-2px);
}
.activity-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid var(--site-border);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  background-color: rgba(154, 123, 79, 0.1);
  color: var(--site-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.activity-content {
  flex: 1;
}

.activity-text {
  margin: 0 0 0.25rem 0;
  color: var(--site-text);
}

.activity-time {
  margin: 0;
  font-size: 0.85rem;
  color: var(--site-text-light);
}
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  overflow-y: auto;
}

.modal-content {
  background-color: white;
  margin: 2rem auto;
  padding: 0;
  width: 90%;
  max-width: 600px;
  border-radius: var(--radius-md);
  box-shadow: var(--site-card-shadow);
  transition: var(--site-transition);
}

.modal-lg {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid var(--site-border);
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  color: var(--site-text);
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--site-text-light);
  transition: var(--site-transition);
}

.modal-close:hover {
  color: var(--site-danger);
}

.modal-body {
  padding: 1.5rem;
}

.order-details-container {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 1.5rem;
}

.order-info-block {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-card {
  background-color: var(--site-bg);
  padding: 1.5rem;
  border-radius: var(--radius-md);
  box-shadow: var(--site-card-shadow);
}

.info-card h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1.2rem;
  color: var(--site-primary);
}

.order-items {
  margin-bottom: 1.5rem;
}

.order-item {
  display: flex;
  align-items: center;
  padding: 1rem 0;
  border-bottom: 1px solid var(--site-border);
}

.item-image {
  width: 60px;
  height: 60px;
  margin-right: 1rem;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: var(--radius-md);
}

.item-details {
  flex: 1;
}

.item-details h4 {
  margin: 0 0 0.5rem 0;
}

.item-price {
  color: var(--site-text-light);
  font-size: 0.9rem;
}

.item-total {
  font-weight: 600;
}

.order-totals {
  margin-top: 1.5rem;
  border-top: 1px solid var(--site-border);
  padding-top: 1.5rem;
}

.total-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.total-row.total {
  font-weight: 600;
  font-size: 1.2rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--site-border);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--site-text);
}

.form-group select,
.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="number"],
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--site-border);
  border-radius: var(--radius-md);
  font-size: 1rem;
  transition: var(--site-transition);
}

.form-group select:focus,
.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="tel"]:focus,
.form-group input[type="number"]:focus,
.form-group textarea:focus {
  border-color: var(--site-primary);
  outline: none;
  box-shadow: 0 0 0 3px rgba(154, 123, 79, 0.1);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

.alert {
  padding: 1rem;
  border-radius: var(--radius-md);
  margin-bottom: 1.5rem;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.text-muted {
  color: var(--site-text-light);
  font-size: 0.9rem;
}

.text-center {
  text-align: center;
}
@media (max-width: 1024px) {
  .admin-sidebar {
    width: 80px;
  }
  
  .admin-nav a span {
    display: none;
  }
  
  .admin-nav a {
    justify-content: center;
    padding: 1rem;
  }
  
  .admin-nav a i {
    font-size: 1.2rem;
  }
}

@media (max-width: 768px) {
  .order-details-container {
    grid-template-columns: 1fr;
  }
  
  .admin-container {
    flex-direction: column;
  }
  
  .admin-sidebar {
    width: 100%;
    height: auto;
    position: static;
    border-right: none;
    border-bottom: 1px solid var(--site-border);
  }
  
  .admin-nav ul {
    display: flex;
    overflow-x: auto;
  }
  
  .admin-nav a {
    padding: 1rem;
    white-space: nowrap;
  }
  
  .admin-nav a span {
    display: inline;
  }
  
  .admin-main {
    padding: 1rem;
  }
  
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .admin-header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}

@media (max-width: 576px) {
  .action-buttons {
    flex-wrap: wrap;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const orderDetailsModal = document.getElementById('order-details-modal');
  const viewOrderBtns = document.querySelectorAll('.view-order-btn');
  const orderDetailsCloseBtn = orderDetailsModal.querySelector('.modal-close');
  
  const statusModal = document.getElementById('status-modal');
  const editStatusBtns = document.querySelectorAll('.edit-status-btn');
  const statusCloseBtn = statusModal.querySelector('.modal-close');
  const statusCloseBtnFooter = statusModal.querySelector('.modal-close-btn');
  const statusSelect = document.getElementById('status');
  const statusOrderIdInput = document.getElementById('status-order-id');
  
  viewOrderBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const orderId = this.getAttribute('data-id');
      
      fetch('get-order.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
          
          document.getElementById('order-id').textContent = data.order.id;
          document.getElementById('order-date').textContent = formatDate(data.order.created_at);
          
          
          let statusText = '';
          switch (data.order.status) {
            case 'pending':
              statusText = 'En attente';
              break;
            case 'processing':
              statusText = 'En traitement';
              break;
            case 'shipped':
              statusText = 'Expédiée';
              break;
            case 'delivered':
              statusText = 'Livrée';
              break;
            case 'cancelled':
              statusText = 'Annulée';
              break;
            default:
              statusText = data.order.status;
          }
          document.getElementById('order-status').innerHTML = `<span class="status-badge status-${data.order.status}">${statusText}</span>`;
          
        
          document.getElementById('order-payment').textContent = `${data.order.payment_method} (${data.order.payment_status})`;
          document.getElementById('order-notes').textContent = data.order.notes || 'Aucune note';
          
          
          document.getElementById('customer-name').textContent = `${data.customer.firstname} ${data.customer.lastname}`;
          document.getElementById('customer-email').textContent = data.customer.email;
          document.getElementById('customer-phone').textContent = data.customer.phone || 'Non renseigné';
          
         
          document.getElementById('shipping-address').textContent = data.order.shipping_address;
          document.getElementById('shipping-city').textContent = `${data.order.shipping_postal_code} ${data.order.shipping_city}`;
          document.getElementById('shipping-country').textContent = data.order.shipping_country;
          
          
          const orderItemsList = document.getElementById('order-items-list');
          orderItemsList.innerHTML = '';
          
          data.items.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'order-item';
            itemElement.innerHTML = `
              <div class="item-image">
                <img src="${item.image_url || '../assets/images/no-image.png'}" alt="${item.item_name}">
              </div>
              <div class="item-details">
                <h4>${item.item_name}</h4>
                <p class="item-price">${formatPrice(item.unit_price)} € x ${item.quantity}</p>
              </div>
              <div class="item-total">
                ${formatPrice(item.subtotal)} €
              </div>
            `;
            orderItemsList.appendChild(itemElement);
          });
          
          
          document.getElementById('order-subtotal').textContent = `${formatPrice(data.totals.subtotal)} €`;
          document.getElementById('order-shipping').textContent = `${formatPrice(data.totals.shipping_fee)} €`;
          document.getElementById('order-total').textContent = `${formatPrice(data.totals.total_amount)} €`;
          
       
          orderDetailsModal.style.display = 'block';
        })
        .catch(error => {
          console.error('Erreur:', error);
        });
    });
  });
  
  editStatusBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const orderId = this.getAttribute('data-id');
      const currentStatus = this.getAttribute('data-status');
      
      statusOrderIdInput.value = orderId;
      statusSelect.value = currentStatus;
      
      statusModal.style.display = 'block';
    });
  });
  
  
  orderDetailsCloseBtn.addEventListener('click', function() {
    orderDetailsModal.style.display = 'none';
  });
  
  statusCloseBtn.addEventListener('click', function() {
    statusModal.style.display = 'none';
  });
  
  statusCloseBtnFooter.addEventListener('click', function() {
    statusModal.style.display = 'none';
  });
  
  window.addEventListener('click', function(event) {
    if (event.target == orderDetailsModal) {
      orderDetailsModal.style.display = 'none';
    }
    if (event.target == statusModal) {
      statusModal.style.display = 'none';
    }
  });
  
  
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
  
  function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace('.', ',');
  }
});
</script>
</body>
</html>