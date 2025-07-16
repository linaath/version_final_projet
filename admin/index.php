<?php
$pageTitle = "Administration";
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
    $stmt = $conn->query("SELECT COUNT(*) as count FROM items");
    $totalProducts = $stmt->fetch()['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
    $totalUsers = $stmt->fetch()['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM orders");
    $totalOrders = $stmt->fetch()['count'];
    $stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
    $totalRevenue = $stmt->fetch()['total'];
    if ($totalRevenue === null) {
        $totalRevenue = 0;
    }
    
    $stmt = $conn->query("
        SELECT o.id, o.total_amount, o.status, o.created_at, u.firstname, u.lastname
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des statistiques: " . $e->getMessage());
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
          <li class="active">
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
          <li>
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
        <h1>Tableau de bord d'administration</h1>
        <div class="admin-actions">
          <a href="../" class="btn btn-secondary">
            <i class="fas fa-home"></i>
            Voir le site
          </a>
        </div>
      </div>

      <div class="admin-stats">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-box"></i>
          </div>
          <div class="stat-content">
            <h3>Produits</h3>
            <p class="stat-number"><?php echo $totalProducts; ?></p>
            <p class="stat-info up">
              <i class="fas fa-arrow-up"></i> 12% depuis le mois dernier
            </p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-content">
            <h3>Utilisateurs</h3>
            <p class="stat-number"><?php echo $totalUsers; ?></p>
            <p class="stat-info up">
              <i class="fas fa-arrow-up"></i> 8% depuis le mois dernier
            </p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <div class="stat-content">
            <h3>Commandes</h3>
            <p class="stat-number"><?php echo $totalOrders; ?></p>
            <p class="stat-info up">
              <i class="fas fa-arrow-up"></i> 5% depuis le mois dernier
            </p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-euro-sign"></i>
          </div>
          <div class="stat-content">
            <h3>Chiffre d'affaires</h3>
            <p class="stat-number"><?php echo number_format($totalRevenue, 2, ',', ' '); ?> €</p>
            <p class="stat-info up">
              <i class="fas fa-arrow-up"></i> 14% depuis le mois dernier
            </p>
          </div>
        </div>
      </div>

      <div class="admin-content">
        <div class="admin-section admin-recent-orders">
          <div class="section-header">
            <h2>Commandes récentes</h2>
            <a href="orders.php" class="view-all">
              Voir toutes les commandes
              <i class="fas fa-arrow-right"></i>
            </a>
          </div>
          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Client</th>
                  <th>Montant</th>
                  <th>Statut</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($recentOrders) > 0): ?>
                  <?php foreach ($recentOrders as $order): ?>
                  <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo $order['firstname'] . ' ' . $order['lastname']; ?></td>
                    <td><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> €</td>
                    <td>
                      <span class="status-badge <?php echo strtolower($order['status']); ?>">
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
                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    <td class="action-buttons">
                      <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-icon view" title="Voir les détails">
                        <i class="fas fa-eye"></i>
                      </a>
                      <a href="edit-order.php?id=<?php echo $order['id']; ?>" class="btn-icon edit" title="Modifier">
                        <i class="fas fa-edit"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center">Aucune commande récente</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="admin-section">
          <div class="section-header">
            <h2>Actions rapides</h2>
          </div>
          <div class="quick-actions">
            <a href="products.php" class="action-card">
              <div class="action-icon">
                <i class="fas fa-box"></i>
              </div>
              <div class="action-content">
                <h3>Gérer les produits</h3>
                <p>Ajouter, modifier ou supprimer des produits</p>
              </div>
            </a>
            <a href="categories.php" class="action-card">
              <div class="action-icon">
                <i class="fas fa-tags"></i>
              </div>
              <div class="action-content">
                <h3>Gérer les catégories</h3>
                <p>Ajouter, modifier ou supprimer des catégories</p>
              </div>
            </a>
            <a href="users.php" class="action-card">
              <div class="action-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="action-content">
                <h3>Gérer les utilisateurs</h3>
                <p>Voir et gérer les comptes utilisateurs</p>
              </div>
            </a>
            <a href="orders.php" class="action-card">
              <div class="action-icon">
                <i class="fas fa-shopping-cart"></i>
              </div>
              <div class="action-content">
                <h3>Gérer les commandes</h3>
                <p>Voir et mettre à jour les commandes</p>
              </div>
            </a>
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

.status-badge.pending {
  background-color: #ffeeba;
  color: #856404;
}

.status-badge.processing {
  background-color: #b8daff;
  color: #004085;
}

.status-badge.shipped {
  background-color: #c3e6cb;
  color: #155724;
}

.status-badge.delivered {
  background-color: #d4edda;
  color: #155724;
}

.status-badge.cancelled {
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

.btn-icon.view:hover {
  background-color: var(--site-info);
}

.btn-icon.edit:hover {
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
  
  .admin-stats, .quick-actions {
    grid-template-columns: 1fr;
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

});
</script>

</body>
</html>