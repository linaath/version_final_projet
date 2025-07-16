<?php
$pageTitle = "Gestion des catégories";

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
      SELECT c.*, COUNT(i.id) as product_count
      FROM categories c
      LEFT JOIN items i ON c.id = i.category_id
      GROUP BY c.id
      ORDER BY c.name
  ");
  $categories = $stmt->fetchAll();
  
} catch(PDOException $e) {
  die("Erreur lors de la récupération des catégories: " . $e->getMessage());
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_category']) || isset($_POST['edit_category'])) {
      $name = trim($_POST['name']);
      $description = trim($_POST['description']);
      $image_url = null;
      if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
          $upload_dir = dirname(__DIR__) . '/photo/';
          $filename = basename($_FILES['image']['name']);
          $target_file = $upload_dir . $filename;
          $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
          if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
              if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                  $image_url = 'photo/' . $filename;
              }
          }
      }
      if (empty($name)) {
          $error = "Veuillez remplir tous les champs obligatoires.";
      } else {
          try {
              if (isset($_POST['add_category'])) {
                  $stmt = $conn->prepare("
                      INSERT INTO categories (name, description, image_url)
                      VALUES (:name, :description, :image_url)
                  ");
                  $stmt->bindParam(':name', $name);
                  $stmt->bindParam(':description', $description);
                  $stmt->bindParam(':image_url', $image_url);
                  $stmt->execute();
                  
                  $success = "La catégorie a été ajoutée avec succès.";
              } else {
                  $categoryId = intval($_POST['category_id']);
                  $sqlUpdate = "UPDATE categories SET name = :name, description = :description";
                  $params = [
                      ':name' => $name,
                      ':description' => $description,
                      ':id' => $categoryId
                  ];
                  if ($image_url !== null) {
                      $sqlUpdate .= ", image_url = :image_url";
                      $params[':image_url'] = $image_url;
                  }
                  
                  $sqlUpdate .= " WHERE id = :id";
                  
                  $stmt = $conn->prepare($sqlUpdate);
                  foreach($params as $key => $value) {
                      $stmt->bindValue($key, $value);
                  }
                  $stmt->execute();
                  
                  $success = "La catégorie a été modifiée avec succès.";
              }
              header("Location: categories.php?success=1");
              exit;
          } catch(PDOException $e) {
              $error = "Erreur lors de l'enregistrement de la catégorie: " . $e->getMessage();
          }
      }
  } elseif (isset($_POST['delete_category']) && isset($_POST['category_id'])) {
      $categoryId = intval($_POST['category_id']);
      
      try {
          $stmt = $conn->prepare("
              SELECT COUNT(*) as product_count
              FROM items
              WHERE category_id = :category_id
          ");
          $stmt->bindParam(':category_id', $categoryId);
          $stmt->execute();
          
          $productCount = $stmt->fetch()['product_count'];
          
          if ($productCount > 0) {
              $error = "Impossible de supprimer cette catégorie car elle contient des produits.";
          } else {
              $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
              $stmt->bindParam(':id', $categoryId);
              $stmt->execute();
              header("Location: categories.php?deleted=1");
              exit;
          }
      } catch(PDOException $e) {
          $error = "Erreur lors de la suppression de la catégorie: " . $e->getMessage();
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
          <li class="active">
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
<div class="admin-header">
  <h1>Gestion des catégories</h1>
  <p>Gérez les catégories de produits de votre boutique</p>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<div class="alert alert-success">
  La catégorie a été enregistrée avec succès.
</div>
<?php endif; ?>

<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
<div class="alert alert-success">
  La catégorie a été supprimée avec succès.
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
      <h2>Liste des catégories</h2>
      <button class="btn btn-primary" id="add-category-btn">
        <i class="fas fa-plus"></i> Ajouter une catégorie
      </button>
    </div>
    <div class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Nom</th>
            <th>Description</th>
            <th>Nombre de produits</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($categories) > 0): ?>
            <?php foreach ($categories as $category): ?>
            <tr>
              <td>#<?php echo $category['id']; ?></td>
              <td>
                <?php if (!empty($category['image_url'])): ?>
                  <img src="<?php echo '../' . $category['image_url']; ?>" alt="<?php echo $category['name']; ?>" class="category-thumbnail">
                <?php else: ?>
                  <img src="../photo/placeholder.png" alt="<?php echo $category['name']; ?>" class="category-thumbnail">
                <?php endif; ?>
              </td>
              <td><?php echo $category['name']; ?></td>
              <td><?php echo substr($category['description'], 0, 100) . (strlen($category['description']) > 100 ? '...' : ''); ?></td>
              <td><?php echo $category['product_count']; ?></td>
              <td>
                <button class="btn-icon edit-category-btn" data-id="<?php echo $category['id']; ?>" data-name="<?php echo htmlspecialchars($category['name']); ?>" data-description="<?php echo htmlspecialchars($category['description']); ?>" title="Modifier">
                  <i class="fas fa-edit"></i>
                </button>
                <form method="post" action="categories.php" class="inline-form delete-form">
                  <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                  <button type="submit" name="delete_category" class="btn-icon btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center">Aucune catégorie trouvée</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<div class="modal" id="category-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modal-title">Ajouter une catégorie</h2>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <form method="post" action="categories.php" enctype="multipart/form-data">
        <input type="hidden" name="category_id" id="category_id">
        
        <div class="form-group">
          <label for="name">Nom de la catégorie *</label>
          <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="4"></textarea>
        </div>
        
        <div class="form-group">
          <label for="image">Image de la catégorie</label>
          <input type="file" id="image" name="image">
          <p class="form-help">Formats acceptés: JPG, PNG. Taille max: 2MB</p>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn btn-secondary modal-close-btn">Annuler</button>
          <button type="submit" name="add_category" id="add_category_btn" class="btn btn-primary">Ajouter</button>
          <button type="submit" name="edit_category" id="edit_category_btn" class="btn btn-primary" style="display: none;">Modifier</button>
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

.category-thumbnail {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
  width: 20px;
  height: 20px;
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
.form-group input[type="tel"],
.form-group input[type="file"],
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--site-border);
  border-radius: var(--radius-sm);
  font-size: 1rem;
  transition: var(--site-transition);
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="tel"]:focus,
.form-group input[type="file"]:focus,
.form-group textarea:focus {
  border-color: var(--site-primary);
  outline: none;
  box-shadow: 0 0 0 3px rgba(154, 123, 79, 0.1);
}

.form-help {
  font-size: 0.8rem;
  color: var(--site-text-light);
  margin-top: 0.25rem;
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
}

@media (max-width: 576px) {
  .action-buttons {
    flex-wrap: wrap;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('category-modal');
  const addCategoryBtn = document.getElementById('add-category-btn');
  const modalCloseBtn = document.querySelector('.modal-close');
  const modalCloseBtnFooter = document.querySelector('.modal-close-btn');
  const editCategoryBtns = document.querySelectorAll('.edit-category-btn');
  const categoryForm = document.querySelector('#category-modal form');
  const modalTitle = document.getElementById('modal-title');
  const categoryIdInput = document.getElementById('category_id');
  const nameInput = document.getElementById('name');
  const descriptionInput = document.getElementById('description');
  const addCategoryFormBtn = document.getElementById('add_category_btn');
  const editCategoryFormBtn = document.getElementById('edit_category_btn');
  addCategoryBtn.addEventListener('click', function() {
    modalTitle.textContent = 'Ajouter une catégorie';
    categoryForm.reset();
    categoryIdInput.value = '';
    addCategoryFormBtn.style.display = 'block';
    editCategoryFormBtn.style.display = 'none';
    modal.style.display = 'block';
  });
  editCategoryBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const categoryId = this.getAttribute('data-id');
      const categoryName = this.getAttribute('data-name');
      const categoryDescription = this.getAttribute('data-description');
      
      modalTitle.textContent = 'Modifier la catégorie';
      categoryIdInput.value = categoryId;
      nameInput.value = categoryName;
      descriptionInput.value = categoryDescription;
      
      addCategoryFormBtn.style.display = 'none';
      editCategoryFormBtn.style.display = 'block';
      modal.style.display = 'block';
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