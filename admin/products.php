<?php
$pageTitle = "Gestion des produits";


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
    $stmtCategories = $conn->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmtCategories->fetchAll();
    
   
    $stmt = $conn->query("
        SELECT i.*, c.name as category_name
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        ORDER BY i.name
    ");
    $products = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des produits: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product']) || isset($_POST['edit_product'])) {
        
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $categoryId = intval($_POST['category_id']);
        $ingredients = trim($_POST['ingredients']);
        $allergens = trim($_POST['allergens']);
        $conservation = trim($_POST['conservation']);
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        
       
        if (empty($name) || empty($description) || $price <= 0) {
            $error = "Veuillez remplir tous les champs obligatoires.";
        } else {
            try {
                $imageUrl = null;
                if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    $fileType = $_FILES['image']['type'];
                    
                    if(in_array($fileType, $allowedTypes)) {
                       
                        $fileName = time() . '_' . basename($_FILES['image']['name']);
                        
                        $uploadPath = 'photo/' . $fileName;
                        $targetFilePath = '../' . $uploadPath; 
                        if (!file_exists('../photo')) {
                            mkdir('../photo', 0777, true);
                        }
                        
                        if(move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                            $imageUrl = $uploadPath; 
                        }
                    }
                }
                
                if (isset($_POST['add_product'])) {
                  
                    $stmt = $conn->prepare("
                        INSERT INTO items (name, description, price, stock, category_id, ingredients, allergens, conservation, is_featured, image_url)
                        VALUES (:name, :description, :price, :stock, :category_id, :ingredients, :allergens, :conservation, :is_featured, :image_url)
                    ");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':price', $price);
                    $stmt->bindParam(':stock', $stock);
                    $stmt->bindParam(':category_id', $categoryId);
                    $stmt->bindParam(':ingredients', $ingredients);
                    $stmt->bindParam(':allergens', $allergens);
                    $stmt->bindParam(':conservation', $conservation);
                    $stmt->bindParam(':is_featured', $isFeatured);
                    $stmt->bindParam(':image_url', $imageUrl);
                    $stmt->execute();
                    
                    $success = "Le produit a été ajouté avec succès.";
                } else {
                   
                    $productId = intval($_POST['product_id']);
                    if ($imageUrl === null) {
                        $stmt = $conn->prepare("
                            UPDATE items
                            SET name = :name, description = :description, price = :price, stock = :stock,
                                category_id = :category_id, ingredients = :ingredients, allergens = :allergens,
                                conservation = :conservation, is_featured = :is_featured
                            WHERE id = :id
                        ");
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE items
                            SET name = :name, description = :description, price = :price, stock = :stock,
                                category_id = :category_id, ingredients = :ingredients, allergens = :allergens,
                                conservation = :conservation, is_featured = :is_featured, image_url = :image_url
                            WHERE id = :id
                        ");
                        $stmt->bindParam(':image_url', $imageUrl);
                    }
                    
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':price', $price);
                    $stmt->bindParam(':stock', $stock);
                    $stmt->bindParam(':category_id', $categoryId);
                    $stmt->bindParam(':ingredients', $ingredients);
                    $stmt->bindParam(':allergens', $allergens);
                    $stmt->bindParam(':conservation', $conservation);
                    $stmt->bindParam(':is_featured', $isFeatured);
                    $stmt->bindParam(':id', $productId);
                    $stmt->execute();
                    
                    $success = "Le produit a été modifié avec succès.";
                }
                
                
                header("Location: products.php?success=1");
                exit;
            } catch(PDOException $e) {
                $error = "Erreur lors de l'enregistrement du produit: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
       
        $productId = intval($_POST['product_id']);
        
        try {
            
            $stmt = $conn->prepare("SELECT image_url FROM items WHERE id = :id");
            $stmt->bindParam(':id', $productId);
            $stmt->execute();
            $product = $stmt->fetch();
            
            
            $stmt = $conn->prepare("DELETE FROM items WHERE id = :id");
            $stmt->bindParam(':id', $productId);
            $stmt->execute();
            
            
            if ($product && !empty($product['image_url'])) {
                $imagePath = '../' . $product['image_url'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
          
            header("Location: products.php?deleted=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de la suppression du produit: " . $e->getMessage();
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
          <li class="active">
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
        <h1>Gestion des produits</h1>
        <p>Gérez les produits disponibles dans votre boutique</p>
      </div>

      <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
      <div class="alert alert-success">
        Le produit a été enregistré avec succès.
      </div>
      <?php endif; ?>

      <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
      <div class="alert alert-success">
        Le produit a été supprimé avec succès.
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
            <h2>Liste des produits</h2>
            <button class="btn btn-primary" id="add-product-btn">
              <i class="fas fa-plus"></i> Ajouter un produit
            </button>
          </div>
          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Nom</th>
                  <th>Catégorie</th>
                  <th>Prix</th>
                  <th>Stock</th>
                  <th>Mis en avant</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($products) > 0): ?>
                  <?php foreach ($products as $product): ?>
                  <tr>
                    <td>#<?php echo $product['id']; ?></td>
                    <td>
                      <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo '../' . $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" class="product-thumbnail">
                      <?php else: ?>
                        <img src="../assets/images/no-image.png" alt="No image" class="product-thumbnail">
                      <?php endif; ?>
                    </td>
                    <td><?php echo $product['name']; ?></td>
                    <td><?php echo $product['category_name']; ?></td>
                    <td><?php echo number_format($product['price'], 2, ',', ' '); ?> €</td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                      <?php if ($product['is_featured']): ?>
                        <span class="status-badge status-delivered">Oui</span>
                      <?php else: ?>
                        <span class="status-badge status-cancelled">Non</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-icon edit-product-btn" data-id="<?php echo $product['id']; ?>" title="Modifier">
                          <i class="fas fa-edit"></i>
                        </button>
                        <form method="post" action="products.php" class="inline-form delete-form">
                          <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                          <button type="submit" name="delete_product" class="btn-icon btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center">Aucun produit trouvé</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    
      <div class="modal" id="product-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="modal-title">Ajouter un produit</h2>
            <button class="modal-close">&times;</button>
          </div>
          <div class="modal-body">
            <form method="post" action="products.php" enctype="multipart/form-data">
              <input type="hidden" name="product_id" id="product_id">
              
              <div class="form-row">
                <div class="form-group">
                  <label for="name">Nom du produit *</label>
                  <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                  <label for="category_id">Catégorie *</label>
                  <select id="category_id" name="category_id" required>
                    <option value="">Sélectionner une catégorie</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="price">Prix (€) *</label>
                  <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                  <label for="stock">Stock *</label>
                  <input type="number" id="stock" name="stock" min="0" required>
                </div>
              </div>
              
              <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="4" required></textarea>
              </div>
              
              <div class="form-group">
                <label for="ingredients">Ingrédients</label>
                <textarea id="ingredients" name="ingredients" rows="3"></textarea>
              </div>
              
              <div class="form-group">
                <label for="allergens">Allergènes</label>
                <textarea id="allergens" name="allergens" rows="2"></textarea>
              </div>
              
              <div class="form-group">
                <label for="conservation">Conservation</label>
                <textarea id="conservation" name="conservation" rows="2"></textarea>
              </div>
              
              <div class="form-group">
                <label for="image">Image du produit</label>
                <input type="file" id="image" name="image">
                <p class="form-help">Formats acceptés: JPG, PNG. Taille max: 2MB</p>
              </div>
              
              <div class="form-group checkbox-group">
                <input type="checkbox" id="is_featured" name="is_featured">
                <label for="is_featured">Mettre en avant sur la page d'accueil</label>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn btn-secondary modal-close-btn">Annuler</button>
                <button type="submit" name="add_product" id="add_product_btn" class="btn btn-primary">Ajouter</button>
                <button type="submit" name="edit_product" id="edit_product_btn" class="btn btn-primary" style="display: none;">Modifier</button>
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


.product-thumbnail {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: var(--radius-md);
  border: 1px solid var(--site-border);
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

.btn-icon.edit:hover, .edit-product-btn:hover {
  background-color: var(--site-warning);
}

.btn-icon.delete:hover, .btn-danger:hover {
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
.form-group input[type="number"],
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--site-border);
  border-radius: var(--radius-md);
  font-size: 1rem;
  transition: var(--site-transition);
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="tel"]:focus,
.form-group input[type="number"]:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: var(--site-primary);
  outline: none;
  box-shadow: 0 0 0 3px rgba(154, 123, 79, 0.1);
}

.form-help {
  font-size: 0.8rem;
  color: var(--site-text-light);
  margin-top: 0.5rem;
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
 
  const modal = document.getElementById('product-modal');
  const addProductBtn = document.getElementById('add-product-btn');
  const modalCloseBtn = document.querySelector('.modal-close');
  const modalCloseBtnFooter = document.querySelector('.modal-close-btn');
  const editProductBtns = document.querySelectorAll('.edit-product-btn');
  
  
  const productForm = document.querySelector('#product-modal form');
  const modalTitle = document.getElementById('modal-title');
  const productIdInput = document.getElementById('product_id');
  const addProductFormBtn = document.getElementById('add_product_btn');
  const editProductFormBtn = document.getElementById('edit_product_btn');
  

  addProductBtn.addEventListener('click', function() {
    modalTitle.textContent = 'Ajouter un produit';
    productForm.reset();
    productIdInput.value = '';
    addProductFormBtn.style.display = 'block';
    editProductFormBtn.style.display = 'none';
    modal.style.display = 'block';
  });
  
  
  editProductBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const productId = this.getAttribute('data-id');
      modalTitle.textContent = 'Modifier le produit';
      
      fetch('get-product.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
          productIdInput.value = data.id;
          document.getElementById('name').value = data.name;
          document.getElementById('category_id').value = data.category_id;
          document.getElementById('price').value = data.price;
          document.getElementById('stock').value = data.stock;
          document.getElementById('description').value = data.description;
          document.getElementById('ingredients').value = data.ingredients;
          document.getElementById('allergens').value = data.allergens;
          document.getElementById('conservation').value = data.conservation;
          document.getElementById('is_featured').checked = data.is_featured == 1;
          
          addProductFormBtn.style.display = 'none';
          editProductFormBtn.style.display = 'block';
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