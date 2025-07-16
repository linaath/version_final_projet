<?php
$pageTitle = "Gestion des utilisateurs";


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
      SELECT u.*, COUNT(o.id) as order_count
      FROM users u
      LEFT JOIN orders o ON u.id = o.user_id
      GROUP BY u.id
      ORDER BY u.created_at DESC
  ");
  $users = $stmt->fetchAll();
  
} catch(PDOException $e) {
  die("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['edit_user'])) {
    
      $userId = intval($_POST['user_id']);
      $firstname = trim($_POST['firstname']);
      $lastname = trim($_POST['lastname']);
      $email = trim($_POST['email']);
      $phone = trim($_POST['phone']);
      $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
      $isActive = isset($_POST['is_active']) ? 1 : 0;
      
      if (empty($firstname) || empty($lastname) || empty($email)) {
          $error = "Veuillez remplir tous les champs obligatoires.";
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $error = "Veuillez entrer une adresse email valide.";
      } else {
          try {
            
              $stmt = $conn->prepare("
                  SELECT id FROM users
                  WHERE email = :email AND id != :user_id
              ");
              $stmt->bindParam(':email', $email);
              $stmt->bindParam(':user_id', $userId);
              $stmt->execute();
              
              if ($stmt->rowCount() > 0) {
                  $error = "Cette adresse email est déjà utilisée par un autre utilisateur.";
              } else {
                  
                  $stmt = $conn->prepare("
                      UPDATE users
                      SET firstname = :firstname, lastname = :lastname, email = :email, phone = :phone,
                          is_admin = :is_admin, is_active = :is_active
                      WHERE id = :id
                  ");
                  $stmt->bindParam(':firstname', $firstname);
                  $stmt->bindParam(':lastname', $lastname);
                  $stmt->bindParam(':email', $email);
                  $stmt->bindParam(':phone', $phone);
                  $stmt->bindParam(':is_admin', $isAdmin);
                  $stmt->bindParam(':is_active', $isActive);
                  $stmt->bindParam(':id', $userId);
                  $stmt->execute();
                  
                  $success = "L'utilisateur a été modifié avec succès.";
                  
                  header("Location: users.php?success=1");
                  exit;
              }
          } catch(PDOException $e) {
              $error = "Erreur lors de la modification de l'utilisateur: " . $e->getMessage();
          }
      }
  } elseif (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    
      $userId = intval($_POST['user_id']);
      
      
      if ($userId === $_SESSION['user_id']) {
          $error = "Vous ne pouvez pas supprimer votre propre compte.";
      } else {
          try {
             
              $stmt = $conn->prepare("
                  SELECT COUNT(*) as order_count
                  FROM orders
                  WHERE user_id = :user_id
              ");
              $stmt->bindParam(':user_id', $userId);
              $stmt->execute();
              
              $orderCount = $stmt->fetch()['order_count'];
              
              if ($orderCount > 0) {
                  $error = "Impossible de supprimer cet utilisateur car il a des commandes associées.";
              } else {
                  $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
                  $stmt->bindParam(':id', $userId);
                  $stmt->execute();
                  
                  
                  header("Location: users.php?deleted=1");
                  exit;
              }
          } catch(PDOException $e) {
              $error = "Erreur lors de la suppression de l'utilisateur: " . $e->getMessage();
          }
      }
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
          <li>
            <a href="orders.php">
              <i class="fas fa-shopping-cart"></i>
              <span>Commandes</span>
            </a>
          </li>
          <li class="active">
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
<div class="admin-header">
  <h1>Gestion des utilisateurs</h1>
  <p>Gérez les comptes utilisateurs de votre boutique</p>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<div class="alert alert-success">
  L'utilisateur a été modifié avec succès.
</div>
<?php endif; ?>

<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
<div class="alert alert-success">
  L'utilisateur a été supprimé avec succès.
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
      <h2>Liste des utilisateurs</h2>
    </div>
    <div class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Date d'inscription</th>
            <th>Commandes</th>
            <th>Statut</th>
            <th>Rôle</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($users) > 0): ?>
            <?php foreach ($users as $user): ?>
            <tr>
              <td>#<?php echo $user['id']; ?></td>
              <td><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></td>
              <td><?php echo $user['email']; ?></td>
              <td><?php echo $user['phone']; ?></td>
              <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
              <td><?php echo $user['order_count']; ?></td>
              <td>
                <?php  if (isset($user['is_active']) && $user['is_active']): ?>
                  <span class="status-badge status-delivered">Actif</span>
                <?php else: ?>
                  <span class="status-badge status-cancelled">Inactif</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($user['is_admin']): ?>
                  <span class="status-badge status-processing">Administrateur</span>
                <?php else: ?>
                  <span class="status-badge status-pending">Client</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn-icon edit-user-btn" data-id="<?php echo $user['id']; ?>" title="Modifier">
                  <i class="fas fa-edit"></i>
                </button>
                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                <form method="post" action="users.php" class="inline-form delete-form">
                  <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                  <button type="submit" name="delete_user" class="btn-icon btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="text-center">Aucun utilisateur trouvé</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal" id="user-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modal-title">Modifier l'utilisateur</h2>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <form method="post" action="users.php">
        <input type="hidden" name="user_id" id="user_id">
        
        <div class="form-row">
          <div class="form-group">
            <label for="firstname">Prénom *</label>
            <input type="text" id="firstname" name="firstname" required>
          </div>
          <div class="form-group">
            <label for="lastname">Nom *</label>
            <input type="text" id="lastname" name="lastname" required>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div class="form-group">
            <label for="phone">Téléphone</label>
            <input type="tel" id="phone" name="phone">
          </div>
        </div>
        
        <div class="form-group checkbox-group">
          <input type="checkbox" id="is_admin" name="is_admin">
          <label for="is_admin">Administrateur</label>
        </div>
        
        <div class="form-group checkbox-group">
          <input type="checkbox" id="is_active" name="is_active">
          <label for="is_active">Compte actif</label>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn btn-secondary modal-close-btn">Annuler</button>
          <button type="submit" name="edit_user" class="btn btn-primary">Modifier</button>
        </div>
      </form>
    </div>
  </div>
</div>
</main>
  </div>
</div>

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
  --radius-sm: 6px;
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
  max-width: 1600px;
  margin: 0 auto;
  padding: 2rem;
  flex: 1;
}

.admin-header {
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
}

.admin-header h1 {
  font-size: 1.8rem;
  color: var(--site-text);
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.admin-header p {
  color: var(--site-primary);
  font-weight: 500;
}


.admin-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.admin-section {
  background-color: white;
  border-radius: var(--radius-md);
  padding: 1.5rem;
  box-shadow: var(--site-card-shadow);
  border: 1px solid transparent;
  transition: var(--site-transition);
}

.admin-section:hover {
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


.admin-table-container {
  overflow-x: auto;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table th,
.admin-table td {
  padding: 0.5rem;
  text-align: left;
  border-bottom: 1px solid var(--site-border);
}

.admin-table th {
  font-weight: 600;
  color: var(--site-text);
  background-color: var(--site-bg);
}

.admin-table tr:nth-child(even) {
  background-color: rgba(248, 249, 250, 0.5);
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

.status-pending {
  background-color: #ffeeba;
  color: #856404;
}

.status-processing {
  background-color: #b8daff;
  color: #004085;
}

.status-shipped {
  background-color: #c3e6cb;
  color: #155724;
}

.status-delivered {
  background-color: #d4edda;
  color: #155724;
}

.status-cancelled {
  background-color: #f5c6cb;
  color: #721c24;
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

.btn-danger {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--site-danger);
}

.btn-danger:hover {
  background-color: var(--site-danger);
  color: white;
}

.inline-form {
  display: inline;
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

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
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

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"] {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--site-border);
  border-radius: var(--radius-sm);
  font-size: 1rem;
  transition: var(--site-transition);
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="tel"]:focus {
  border-color: var(--site-primary);
  outline: none;
  box-shadow: 0 0 0 3px rgba(154, 123, 79, 0.1);
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
  width: auto;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

.alert {
  padding: 1rem;
  border-radius: var(--radius-sm);
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
  
  .form-row {
    flex-direction: column;
    gap: 0;
  }
  
  .admin-main {
    padding: 1rem;
  }
  
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  
  const modal = document.getElementById('user-modal');
  const modalCloseBtn = document.querySelector('.modal-close');
  const modalCloseBtnFooter = document.querySelector('.modal-close-btn');
  const editUserBtns = document.querySelectorAll('.edit-user-btn');
  

  const userForm = document.querySelector('#user-modal form');
  const userIdInput = document.getElementById('user_id');
  const firstnameInput = document.getElementById('firstname');
  const lastnameInput = document.getElementById('lastname');
  const emailInput = document.getElementById('email');
  const phoneInput = document.getElementById('phone');
  const isAdminInput = document.getElementById('is_admin');
  const isActiveInput = document.getElementById('is_active');
  

  editUserBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const userId = this.getAttribute('data-id');
      
      
      fetch('get-user.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
          userIdInput.value = data.id;
          firstnameInput.value = data.firstname;
          lastnameInput.value = data.lastname;
          emailInput.value = data.email;
          phoneInput.value = data.phone;
          isAdminInput.checked = data.is_admin == 1;
          isActiveInput.checked = data.is_active == 1;
          
          modal.style.display = 'block';
        })
        .catch(error => {
          console.error('Erreur:', error);
        });
    });
  });
  

  modalCloseBtn.addEventListener('click', function() {
    modal.style.display = 'none';
  });
  
  modalCloseBtnFooter.addEventListener('click', function() {
    modal.style.display = 'none';
  });
  
  window.addEventListener('click', function(event) {
    if (event.target == modal) {
      modal.style.display = 'none';
    }
  });
});
</script>
</body>
</html>