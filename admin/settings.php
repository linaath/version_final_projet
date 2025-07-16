<?php
$pageTitle = "Paramètres";


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
   
$stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'general'");
$generalSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'store'");
$storeSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'payment'");
$paymentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'shipping'");
$shippingSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'email'");
$emailSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des paramètres: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    if (isset($_POST['update_general'])) {
        try {
           
            foreach ($_POST as $key => $value) {
                if ($key !== 'update_general' && !empty($value)) {
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, setting_group) 
                        VALUES (:key, :value, 'general') 
                        ON DUPLICATE KEY UPDATE setting_value = :value
                    ");
                    $stmt->bindParam(':key', $key);
                    $stmt->bindParam(':value', $value);
                    $stmt->execute();
                }
            }
            
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $logoPath = uploadFile($_FILES['store_logo'], '../assets/images/');
                if ($logoPath) {
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, setting_group) 
                        VALUES ('store_logo', :value, 'general') 
                        ON DUPLICATE KEY UPDATE setting_value = :value
                    ");
                    $stmt->bindParam(':value', $logoPath);
                    $stmt->execute();
                }
            }
          
            if (isset($_FILES['store_favicon']) && $_FILES['store_favicon']['error'] === UPLOAD_ERR_OK) {
                $faviconPath = uploadFile($_FILES['store_favicon'], '../assets/images/');
                if ($faviconPath) {
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, setting_group) 
                        VALUES ('store_favicon', :value, 'general') 
                        ON DUPLICATE KEY UPDATE setting_value = :value
                    ");
                    $stmt->bindParam(':value', $faviconPath);
                    $stmt->execute();
                }
            }
            
            $success = "Les paramètres généraux ont été mis à jour avec succès.";
            header("Location: settings.php?tab=general&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de la mise à jour des paramètres généraux: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_store'])) {
        try {
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'update_store') {
                    
                    if (in_array($key, ['show_stock', 'show_out_of_stock', 'enable_reviews', 'moderate_reviews'])) {
                        $value = isset($_POST[$key]) ? '1' : '0';
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, setting_group) 
                        VALUES (:key, :value, 'store') 
                        ON DUPLICATE KEY UPDATE setting_value = :value
                    ");
                    $stmt->bindParam(':key', $key);
                    $stmt->bindParam(':value', $value);
                    $stmt->execute();
                }
            }
            
            $success = "Les paramètres de la boutique ont été mis à jour avec succès.";
            header("Location: settings.php?tab=store&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de la mise à jour des paramètres de la boutique: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_payment'])) {
        try {
           
            foreach ($_POST as $key => $value) {
                if ($key !== 'update_payment') {
                  
                    if (in_array($key, ['enable_card', 'enable_paypal', 'paypal_sandbox'])) {
                        $value = isset($_POST[$key]) ? '1' : '0';
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, setting_group) 
                        VALUES (:key, :value, 'payment') 
                        ON DUPLICATE KEY UPDATE setting_value = :value
                    ");
                    $stmt->bindParam(':key', $key);
                    $stmt->bindParam(':value', $value);
                    $stmt->execute();
                }
            }
            
            $success = "Les paramètres de paiement ont été mis à jour avec succès.";
            header("Location: settings.php?tab=payment&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de la mise à jour des paramètres de paiement: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_shipping'])) {
        try {
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'update_shipping') {
                    
                    if (in_array($key, ['enable_pickup'])) {
                        $value = isset($_POST[$key]) ? '1' : '0';
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, setting_group) 
                        VALUES (:key, :value, 'shipping') 
                        ON DUPLICATE KEY UPDATE setting_value = :value
                    ");
                    $stmt->bindParam(':key', $key);
                    $stmt->bindParam(':value', $value);
                    $stmt->execute();
                }
            }
            
            $success = "Les paramètres de livraison ont été mis à jour avec succès.";
            header("Location: settings.php?tab=shipping&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de la mise à jour des paramètres de livraison: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_email'])) {
        try {
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'update_email') {
                    
                    if (strpos($key, 'admin_') === 0 || strpos($key, 'customer_') === 0) {
                        $value = isset($_POST[$key]) ? '1' : '0';
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, setting_group) 
                        VALUES (:key, :value, 'email') 
                        ON DUPLICATE KEY UPDATE setting_value = :value
                    ");
                    $stmt->bindParam(':key', $key);
                    $stmt->bindParam(':value', $value);
                    $stmt->execute();
                }
            }
            
            $success = "Les paramètres d'emails ont été mis à jour avec succès.";
            header("Location: settings.php?tab=email&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de la mise à jour des paramètres d'emails: " . $e->getMessage();
        }
    } elseif (isset($_POST['add_shipping_zone'])) {
        try {
           
            $name = trim($_POST['zone_name']);
            $postalCodes = trim($_POST['postal_codes']);
            $shippingFee = floatval($_POST['shipping_fee']);
            $freeShippingThreshold = floatval($_POST['free_shipping_threshold']);
            
            $stmt = $conn->prepare("
                INSERT INTO shipping_zones (name, postal_codes, shipping_fee, free_shipping_threshold)
                VALUES (:name, :postal_codes, :shipping_fee, :free_shipping_threshold)
            ");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':postal_codes', $postalCodes);
            $stmt->bindParam(':shipping_fee', $shippingFee);
            $stmt->bindParam(':free_shipping_threshold', $freeShippingThreshold);
            $stmt->execute();
            
            $success = "La zone de livraison a été ajoutée avec succès.";
            header("Location: settings.php?tab=shipping&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de l'ajout de la zone de livraison: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_shipping_zone'])) {
        try {
           
            $zoneId = intval($_POST['zone_id']);
            $name = trim($_POST['zone_name']);
            $postalCodes = trim($_POST['postal_codes']);
            $shippingFee = floatval($_POST['shipping_fee']);
            $freeShippingThreshold = floatval($_POST['free_shipping_threshold']);
            
            $stmt = $conn->prepare("
                UPDATE shipping_zones
                SET name = :name, postal_codes = :postal_codes, shipping_fee = :shipping_fee, free_shipping_threshold = :free_shipping_threshold
                WHERE id = :id
            ");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':postal_codes', $postalCodes);
            $stmt->bindParam(':shipping_fee', $shippingFee);
            $stmt->bindParam(':free_shipping_threshold', $freeShippingThreshold);
            $stmt->bindParam(':id', $zoneId);
            $stmt->execute();
            
            $success = "La zone de livraison a été modifiée avec succès.";
            header("Location: settings.php?tab=shipping&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de la modification de la zone de livraison: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_shipping_zone'])) {
        try {
           
            $zoneId = intval($_POST['zone_id']);
            
            $stmt = $conn->prepare("DELETE FROM shipping_zones WHERE id = :id");
            $stmt->bindParam(':id', $zoneId);
            $stmt->execute();
            
            $success = "La zone de livraison a été supprimée avec succès.";
            header("Location: settings.php?tab=shipping&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Erreur lors de la suppression de la zone de livraison: " . $e->getMessage();
        }
    }
}


function uploadFile($file, $targetDir) {
    $fileName = basename($file['name']);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
  
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'ico', 'svg');
    if (!in_array(strtolower($fileType), $allowedTypes)) {
        return false;
    }
    

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
  
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return $fileName;
    }
    
    return false;
}


$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
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
          <li>
            <a href="users.php">
              <i class="fas fa-users"></i>
              <span>Utilisateurs</span>
            </a>
          </li>
          <li class="active">
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
        <h1>Paramètres</h1>
        <p>Configurez les paramètres de votre boutique</p>
      </div>

      <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
      <div class="alert alert-success">
        Les paramètres ont été mis à jour avec succès.
      </div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
      <div class="alert alert-error">
        <?php echo $error; ?>
      </div>
      <?php endif; ?>

      <div class="admin-tabs">
        <a href="?tab=general" class="admin-tab <?php echo $activeTab === 'general' ? 'active' : ''; ?>">Général</a>
        <a href="?tab=store" class="admin-tab <?php echo $activeTab === 'store' ? 'active' : ''; ?>">Boutique</a>
        <a href="?tab=payment" class="admin-tab <?php echo $activeTab === 'payment' ? 'active' : ''; ?>">Paiement</a>
        <a href="?tab=shipping" class="admin-tab <?php echo $activeTab === 'shipping' ? 'active' : ''; ?>">Livraison</a>
        <a href="?tab=email" class="admin-tab <?php echo $activeTab === 'email' ? 'active' : ''; ?>">Emails</a>
      </div>

      <div class="settings-content">
        <!-- Paramètres généraux -->
        <div class="settings-section <?php echo $activeTab === 'general' ? 'active' : ''; ?>" id="general-settings">
          <div class="settings-card">
            <div class="settings-card-header">
              <h2>Paramètres généraux</h2>
              <p>Configurez les informations générales de votre boutique</p>
            </div>
            <div class="settings-card-body">
              <form class="admin-form" method="post" action="settings.php?tab=general" enctype="multipart/form-data">
                <div class="form-row">
                  <div class="form-group">
                    <label for="store_name">Nom de la boutique</label>
                    <input type="text" id="store_name" name="store_name" value="<?php echo isset($generalSettings['store_name']) ? $generalSettings['store_name'] : 'Délices Sucrés'; ?>" required>
                  </div>
                  <div class="form-group">
                    <label for="store_tagline">Slogan</label>
                    <input type="text" id="store_tagline" name="store_tagline" value="<?php echo isset($generalSettings['store_tagline']) ? $generalSettings['store_tagline'] : 'L\'art de la pâtisserie fine'; ?>">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="store_email">Email de contact</label>
                    <input type="email" id="store_email" name="store_email" value="<?php echo isset($generalSettings['store_email']) ? $generalSettings['store_email'] : 'contact@delices-sucres.fr'; ?>" required>
                  </div>
                  <div class="form-group">
                    <label for="store_phone">Téléphone</label>
                    <input type="tel" id="store_phone" name="store_phone" value="<?php echo isset($generalSettings['store_phone']) ? $generalSettings['store_phone'] : '+33 1 23 45 67 89'; ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label for="store_address">Adresse</label>
                  <textarea id="store_address" name="store_address" rows="3"><?php echo isset($generalSettings['store_address']) ? $generalSettings['store_address'] : '123 Avenue des Pâtissiers, 75001 Paris'; ?></textarea>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="store_currency">Devise</label>
                    <select id="store_currency" name="store_currency">
                      <option value="EUR" <?php echo (isset($generalSettings['store_currency']) && $generalSettings['store_currency'] === 'EUR') ? 'selected' : ''; ?>>Euro (€)</option>
                      <option value="USD" <?php echo (isset($generalSettings['store_currency']) && $generalSettings['store_currency'] === 'USD') ? 'selected' : ''; ?>>Dollar américain ($)</option>
                      <option value="GBP" <?php echo (isset($generalSettings['store_currency']) && $generalSettings['store_currency'] === 'GBP') ? 'selected' : ''; ?>>Livre sterling (£)</option>
                      <option value="CHF" <?php echo (isset($generalSettings['store_currency']) && $generalSettings['store_currency'] === 'CHF') ? 'selected' : ''; ?>>Franc suisse (CHF)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="store_language">Langue</label>
                    <select id="store_language" name="store_language">
                      <option value="fr" <?php echo (isset($generalSettings['store_language']) && $generalSettings['store_language'] === 'fr') ? 'selected' : ''; ?>>Français</option>
                      <option value="en" <?php echo (isset($generalSettings['store_language']) && $generalSettings['store_language'] === 'en') ? 'selected' : ''; ?>>Anglais</option>
                      <option value="es" <?php echo (isset($generalSettings['store_language']) && $generalSettings['store_language'] === 'es') ? 'selected' : ''; ?>>Espagnol</option>
                      <option value="de" <?php echo (isset($generalSettings['store_language']) && $generalSettings['store_language'] === 'de') ? 'selected' : ''; ?>>Allemand</option>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="store_logo">Logo</label>
                  <?php if (isset($generalSettings['store_logo']) && !empty($generalSettings['store_logo'])): ?>
                    <div class="current-image">
                      <img src="../assets/images/<?php echo $generalSettings['store_logo']; ?>" alt="Logo actuel" style="max-height: 50px; margin-bottom: 10px;">
                    </div>
                  <?php endif; ?>
                  <input type="file" id="store_logo" name="store_logo" accept="image/*">
                  <p class="form-help">Format recommandé : PNG ou SVG, dimensions minimales 200x50px</p>
                </div>
                <div class="form-group">
                  <label for="store_favicon">Favicon</label>
                  <?php if (isset($generalSettings['store_favicon']) && !empty($generalSettings['store_favicon'])): ?>
                    <div class="current-image">
                      <img src="../assets/images/<?php echo $generalSettings['store_favicon']; ?>" alt="Favicon actuel" style="max-height: 32px; margin-bottom: 10px;">
                    </div>
                  <?php endif; ?>
                  <input type="file" id="store_favicon" name="store_favicon" accept="image/*">
                  <p class="form-help">Format recommandé : ICO, PNG, dimensions 32x32px ou 16x16px</p>
                </div>
                <div class="form-actions">
                  <button type="submit" name="update_general" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
              </form>
            </div>
          </div>
        </div>

       
        <div class="settings-section <?php echo $activeTab === 'store' ? 'active' : ''; ?>" id="store-settings">
          <div class="settings-card">
            <div class="settings-card-header">
              <h2>Paramètres de la boutique</h2>
              <p>Configurez les options de votre boutique en ligne</p>
            </div>
            <div class="settings-card-body">
              <form class="admin-form" method="post" action="settings.php?tab=store">
                <div class="form-row">
                  <div class="form-group">
                    <label for="products_per_page">Produits par page</label>
                    <input type="number" id="products_per_page" name="products_per_page" value="<?php echo isset($storeSettings['products_per_page']) ? $storeSettings['products_per_page'] : '12'; ?>" min="4" max="48" step="4">
                  </div>
                  <div class="form-group">
                    <label for="default_sorting">Tri par défaut</label>
                    <select id="default_sorting" name="default_sorting">
                      <option value="popularity" <?php echo (isset($storeSettings['default_sorting']) && $storeSettings['default_sorting'] === 'popularity') ? 'selected' : ''; ?>>Popularité</option>
                      <option value="date" <?php echo (isset($storeSettings['default_sorting']) && $storeSettings['default_sorting'] === 'date') ? 'selected' : ''; ?>>Date (plus récent en premier)</option>
                      <option value="price-asc" <?php echo (isset($storeSettings['default_sorting']) && $storeSettings['default_sorting'] === 'price-asc') ? 'selected' : ''; ?>>Prix (croissant)</option>
                      <option value="price-desc" <?php echo (isset($storeSettings['default_sorting']) && $storeSettings['default_sorting'] === 'price-desc') ? 'selected' : ''; ?>>Prix (décroissant)</option>
                    </select>
                  </div>
                </div>
                <div class="form-group checkbox-group">
                  <input type="checkbox" id="show_stock" name="show_stock" <?php echo (isset($storeSettings['show_stock']) && $storeSettings['show_stock'] === '1') ? 'checked' : ''; ?>>
                  <label for="show_stock">Afficher le stock des produits</label>
                </div>
                <div class="form-group checkbox-group">
                  <input type="checkbox" id="show_out_of_stock" name="show_out_of_stock" <?php echo (isset($storeSettings['show_out_of_stock']) && $storeSettings['show_out_of_stock'] === '1') ? 'checked' : ''; ?>>
                  <label for="show_out_of_stock">Afficher les produits en rupture de stock</label>
                </div>
                <div class="form-group checkbox-group">
                  <input type="checkbox" id="enable_reviews" name="enable_reviews" <?php echo (isset($storeSettings['enable_reviews']) && $storeSettings['enable_reviews'] === '1') ? 'checked' : ''; ?>>
                  <label for="enable_reviews">Activer les avis clients</label>
                </div>
                <div class="form-group checkbox-group">
                  <input type="checkbox" id="moderate_reviews" name="moderate_reviews" <?php echo (isset($storeSettings['moderate_reviews']) && $storeSettings['moderate_reviews'] === '1') ? 'checked' : ''; ?>>
                  <label for="moderate_reviews">Modérer les avis avant publication</label>
                </div>
                <div class="form-actions">
                  <button type="submit" name="update_store" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
              </form>
            </div>
          </div>
        </div>

       
        <div class="settings-section <?php echo $activeTab === 'payment' ? 'active' : ''; ?>" id="payment-settings">
          <div class="settings-card">
            <div class="settings-card-header">
              <h2>Paramètres de paiement</h2>
              <p>Configurez les méthodes de paiement acceptées</p>
            </div>
            <div class="settings-card-body">
              <form class="admin-form" method="post" action="settings.php?tab=payment">
                <div class="payment-method-card">
                  <div class="payment-method-header">
                    <div class="payment-method-title">
                      <h3>Carte bancaire</h3>
                      <div class="payment-method-toggle">
                        <input type="checkbox" id="enable_card" name="enable_card" class="toggle-input" <?php echo (isset($paymentSettings['enable_card']) && $paymentSettings['enable_card'] === '1') ? 'checked' : ''; ?>>
                        <label for="enable_card" class="toggle-label"></label>
                      </div>
                    </div>
                    <div class="payment-method-icons">
                      <i class="fab fa-cc-visa"></i>
                      <i class="fab fa-cc-mastercard"></i>
                      <i class="fab fa-cc-amex"></i>
                    </div>
                  </div>
                  <div class="payment-method-body">
                    <div class="form-row">
                      <div class="form-group">
                        <label for="stripe_public_key">Clé publique Stripe</label>
                        <input type="text" id="stripe_public_key" name="stripe_public_key" value="<?php echo isset($paymentSettings['stripe_public_key']) ? $paymentSettings['stripe_public_key'] : ''; ?>">
                      </div>
                      <div class="form-group">
                        <label for="stripe_secret_key">Clé secrète Stripe</label>
                        <input type="password" id="stripe_secret_key" name="stripe_secret_key" value="<?php echo isset($paymentSettings['stripe_secret_key']) ? $paymentSettings['stripe_secret_key'] : ''; ?>">
                      </div>
                    </div>
                  </div>
                </div>

                <div class="payment-method-card">
                  <div class="payment-method-header">
                    <div class="payment-method-title">
                      <h3>PayPal</h3>
                      <div class="payment-method-toggle">
                        <input type="checkbox" id="enable_paypal" name="enable_paypal" class="toggle-input" <?php echo (isset($paymentSettings['enable_paypal']) && $paymentSettings['enable_paypal'] === '1') ? 'checked' : ''; ?>>
                        <label for="enable_paypal" class="toggle-label"></label>
                      </div>
                    </div>
                    <div class="payment-method-icons">
                      <i class="fab fa-paypal"></i>
                    </div>
                  </div>
                  <div class="payment-method-body">
                    <div class="form-row">
                      <div class="form-group">
                        <label for="paypal_client_id">Client ID PayPal</label>
                        <input type="text" id="paypal_client_id" name="paypal_client_id" value="<?php echo isset($paymentSettings['paypal_client_id']) ? $paymentSettings['paypal_client_id'] : ''; ?>">
                      </div>
                      <div class="form-group">
                        <label for="paypal_secret">Secret PayPal</label>
                        <input type="password" id="paypal_secret" name="paypal_secret" value="<?php echo isset($paymentSettings['paypal_secret']) ? $paymentSettings['paypal_secret'] : ''; ?>">
                      </div>
                    </div>
                    <div class="form-group checkbox-group">
                      <input type="checkbox" id="paypal_sandbox" name="paypal_sandbox" <?php echo (isset($paymentSettings['paypal_sandbox']) && $paymentSettings['paypal_sandbox'] === '1') ? 'checked' : ''; ?>>
                      <label for="paypal_sandbox">Mode Sandbox (test)</label>
                    </div>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" name="update_payment" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
              </form>
            </div>
          </div>
        </div>

     
        <div class="settings-section <?php echo $activeTab === 'shipping' ? 'active' : ''; ?>" id="shipping-settings">
          <div class="settings-card">
            <div class="settings-card-header">
              <h2>Paramètres de livraison</h2>
              <p>Configurez les options de livraison</p>
            </div>
            <div class="settings-card-body">
              <form class="admin-form" method="post" action="settings.php?tab=shipping">
                <div class="form-group">
                  <label for="shipping-zones">Zones de livraison</label>
                  <div class="shipping-zones">
                    <?php if (isset($shippingZones) && count($shippingZones) > 0): ?>
                      <?php foreach ($shippingZones as $zone): ?>
                        <div class="shipping-zone">
                          <div class="shipping-zone-header">
                            <h4><?php echo $zone['name']; ?></h4>
                            <div class="shipping-zone-actions">
                              <button type="button" class="btn-icon edit edit-zone-btn" data-id="<?php echo $zone['id']; ?>" data-name="<?php echo $zone['name']; ?>" data-postal-codes="<?php echo $zone['postal_codes']; ?>" data-fee="<?php echo $zone['shipping_fee']; ?>" data-threshold="<?php echo $zone['free_shipping_threshold']; ?>" title="Modifier"><i class="fas fa-edit"></i></button>
                              <form method="post" action="settings.php?tab=shipping" class="inline-form">
                                <input type="hidden" name="zone_id" value="<?php echo $zone['id']; ?>">
                                <button type="submit" name="delete_shipping_zone" class="btn-icon delete" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette zone de livraison ?')"><i class="fas fa-trash-alt"></i></button>
                              </form>
                            </div>
                          </div>
                          <div class="shipping-zone-body">
                            <p><strong>Codes postaux :</strong> <?php echo $zone['postal_codes']; ?></p>
                            <p><strong>Frais de livraison :</strong> <?php echo number_format($zone['shipping_fee'], 2, ',', ' '); ?> €</p>
                            <p><strong>Livraison gratuite à partir de :</strong> <?php echo number_format($zone['free_shipping_threshold'], 2, ',', ' '); ?> €</p>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <p>Aucune zone de livraison définie.</p>
                    <?php endif; ?>
                  </div>
                  <button type="button" class="btn btn-secondary add-shipping-zone" id="add-zone-btn"><i class="fas fa-plus"></i> Ajouter une zone</button>
                </div>
                <div class="form-group checkbox-group">
                  <input type="checkbox" id="enable_pickup" name="enable_pickup" <?php echo (isset($shippingSettings['enable_pickup']) && $shippingSettings['enable_pickup'] === '1') ? 'checked' : ''; ?>>
                  <label for="enable_pickup">Activer le retrait en boutique</label>
                </div>
                <div class="form-actions">
                  <button type="submit" name="update_shipping" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
              </form>
            </div>
          </div>
        </div>

      
        <div class="settings-section <?php echo $activeTab === 'email' ? 'active' : ''; ?>" id="email-settings">
          <div class="settings-card">
            <div class="settings-card-header">
              <h2>Paramètres d'emails</h2>
              <p>Configurez les notifications par email</p>
            </div>
            <div class="settings-card-body">
              <form class="admin-form" method="post" action="settings.php?tab=email">
                <div class="form-row">
                  <div class="form-group">
                    <label for="email_from">Email expéditeur</label>
                    <input type="email" id="email_from" name="email_from" value="<?php echo isset($emailSettings['email_from']) ? $emailSettings['email_from'] : 'contact@delices-sucres.fr'; ?>" required>
                  </div>
                  <div class="form-group">
                    <label for="email_name">Nom expéditeur</label>
                    <input type="text" id="email_name" name="email_name" value="<?php echo isset($emailSettings['email_name']) ? $emailSettings['email_name'] : 'Délices Sucrés'; ?>" required>
                  </div>
                </div>
                <div class="form-group">
                  <label for="email_footer">Pied de page des emails</label>
                  <textarea id="email_footer" name="email_footer" rows="3"><?php echo isset($emailSettings['email_footer']) ? $emailSettings['email_footer'] : '© 2025 Délices Sucrés. Tous droits réservés. 123 Avenue des Pâtissiers, 75001 Paris'; ?></textarea>
                </div>
                <div class="form-group">
                  <label>Notifications administrateur</label>
                  <div class="checkbox-list">
                    <div class="checkbox-group">
                      <input type="checkbox" id="admin_new_order" name="admin_new_order" <?php echo (isset($emailSettings['admin_new_order']) && $emailSettings['admin_new_order'] === '1') ? 'checked' : ''; ?>>
                      <label for="admin_new_order">Nouvelle commande</label>
                    </div>
                    <div class="checkbox-group">
                      <input type="checkbox" id="admin_new_customer" name="admin_new_customer" <?php echo (isset($emailSettings['admin_new_customer']) && $emailSettings['admin_new_customer'] === '1') ? 'checked' : ''; ?>>
                      <label for="admin_new_customer">Nouveau client</label>
                    </div>
                    <div class="checkbox-group">
                      <input type="checkbox" id="admin_low_stock" name="admin_low_stock" <?php echo (isset($emailSettings['admin_low_stock']) && $emailSettings['admin_low_stock'] === '1') ? 'checked' : ''; ?>>
                      <label for="admin_low_stock">Stock faible</label>
                    </div>
                    <div class="checkbox-group">
                      <input type="checkbox" id="admin_product_review" name="admin_product_review" <?php echo (isset($emailSettings['admin_product_review']) && $emailSettings['admin_product_review'] === '1') ? 'checked' : ''; ?>>
                      <label for="admin_product_review">Nouvel avis produit</label>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label>Notifications client</label>
                  <div class="checkbox-list">
                    <div class="checkbox-group">
                      <input type="checkbox" id="customer_order_confirmation" name="customer_order_confirmation" <?php echo (isset($emailSettings['customer_order_confirmation']) && $emailSettings['customer_order_confirmation'] === '1') ? 'checked' : ''; ?>>
                      <label for="customer_order_confirmation">Confirmation de commande</label>
                    </div>
                    <div class="checkbox-group">
                      <input type="checkbox" id="customer_order_shipped" name="customer_order_shipped" <?php echo (isset($emailSettings['customer_order_shipped']) && $emailSettings['customer_order_shipped'] === '1') ? 'checked' : ''; ?>>
                      <label for="customer_order_shipped">Commande expédiée</label>
                    </div>
                    <div class="checkbox-group">
                      <input type="checkbox" id="customer_order_delivered" name="customer_order_delivered" <?php echo (isset($emailSettings['customer_order_delivered']) && $emailSettings['customer_order_delivered'] === '1') ? 'checked' : ''; ?>>
                      <label for="customer_order_delivered">Commande livrée</label>
                    </div>
                    <div class="checkbox-group">
                      <input type="checkbox" id="customer_account_created" name="customer_account_created" <?php echo (isset($emailSettings['customer_account_created']) && $emailSettings['customer_account_created'] === '1') ? 'checked' : ''; ?>>
                      <label for="customer_account_created">Création de compte</label>
                    </div>
                  </div>
                </div>
                <div class="form-actions">
                  <button type="submit" name="update_email" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<div class="modal" id="shipping-zone-modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="zone-modal-title">Ajouter une zone de livraison</h2>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <form method="post" action="settings.php?tab=shipping">
        <input type="hidden" name="zone_id" id="zone_id">
        
        <div class="form-group">
          <label for="zone_name">Nom de la zone</label>
          <input type="text" id="zone_name" name="zone_name" required>
        </div>
        
        <div class="form-group">
          <label for="postal_codes">Codes postaux</label>
          <textarea id="postal_codes" name="postal_codes" rows="3" required></textarea>
          <p class="form-help">Séparez les codes postaux par des virgules ou utilisez des plages (ex: 75001-75020)</p>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="shipping_fee">Frais de livraison (€)</label>
            <input type="number" id="shipping_fee" name="shipping_fee" step="0.01" min="0" required>
          </div>
          <div class="form-group">
            <label for="free_shipping_threshold">Livraison gratuite à partir de (€)</label>
            <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" step="0.01" min="0">
          </div>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn btn-secondary modal-close-btn">Annuler</button>
          <button type="submit" name="add_shipping_zone" id="add_zone_btn" class="btn btn-primary">Ajouter</button>
          <button type="submit" name="edit_shipping_zone" id="edit_zone_btn" class="btn btn-primary" style="display: none;">Modifier</button>
        </div>
      </form>
    </div>
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

.admin-tabs {
  display: flex;
  border-bottom: 1px solid var(--site-border);
  margin-bottom: 2rem;
  overflow-x: auto;
}

.admin-tab {
  padding: 1rem 1.5rem;
  font-weight: 500;
  color: var(--site-text);
  cursor: pointer;
  transition: var(--site-transition);
  border-bottom: 2px solid transparent;
  white-space: nowrap;
  text-decoration: none;
}

.admin-tab:hover {
  color: var(--site-primary);
}

.admin-tab.active {
  color: var(--site-primary);
  border-bottom-color: var(--site-primary);
}

.settings-content {
  margin-top: 1.5rem;
}

.settings-section {
  display: none;
}

.settings-section.active {
  display: block;
}

.settings-card {
  background-color: white;
  border-radius: 10px;
  box-shadow: var(--site-card-shadow);
  margin-bottom: 2rem;
  overflow: hidden;
}

.settings-card-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--site-border);
}

.settings-card-header h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.4rem;
  color: var(--site-text);
}

.settings-card-header p {
  margin: 0;
  color: var(--site-text-light);
  font-size: 0.95rem;
}

.settings-card-body {
  padding: 2rem;
}

.form-help {
  font-size: 0.85rem;
  color: var(--site-text-light);
  margin-top: 0.25rem;
}

.payment-method-card {
  border: 1px solid var(--site-border);
  border-radius: 8px;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.payment-method-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background-color: #f8f9fa;
  border-bottom: 1px solid var(--site-border);
}

.payment-method-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.payment-method-title h3 {
  margin: 0;
  font-size: 1.1rem;
}

.payment-method-icons {
  display: flex;
  gap: 0.75rem;
  font-size: 1.5rem;
}

.payment-method-icons i {
  color: var(--site-text);
}

.payment-method-body {
  padding: 1.5rem;
}

.toggle-input {
  display: none;
}

.toggle-label {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 24px;
  background-color: #e9ecef;
  border-radius: 12px;
  cursor: pointer;
  transition: var(--site-transition);
}

.toggle-label::after {
  content: "";
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background-color: white;
  border-radius: 50%;
  transition: var(--site-transition);
}

.toggle-input:checked + .toggle-label {
  background-color: var(--site-primary);
}

.toggle-input:checked + .toggle-label::after {
  left: 26px;
}

.shipping-zones {
  margin-bottom: 1rem;
}

.shipping-zone {
  border: 1px solid var(--site-border);
  border-radius: 8px;
  margin-bottom: 1rem;
  overflow: hidden;
}

.shipping-zone-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background-color: #f8f9fa;
  border-bottom: 1px solid var(--site-border);
}

.shipping-zone-header h4 {
  margin: 0;
  font-size: 1rem;
}

.shipping-zone-actions {
  display: flex;
  gap: 0.5rem;
}

.shipping-zone-body {
  padding: 1rem 1.5rem;
}

.shipping-zone-body p {
  margin: 0.5rem 0;
  font-size: 0.95rem;
}

.add-shipping-zone {
  margin-top: 0.5rem;
}

.checkbox-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 0.5rem;
}


.form-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
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
.form-group input[type="password"],
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
.form-group input[type="password"]:focus,
.form-group select:focus,
.form-group textarea:focus {
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

.btn-icon.edit:hover {
  background-color: var(--site-warning);
}

.btn-icon.delete:hover {
  background-color: var(--site-danger);
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
  
  .admin-header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}

@media (max-width: 576px) {
  .shipping-zone-actions {
    flex-wrap: wrap;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
 
  const shippingZoneModal = document.getElementById('shipping-zone-modal');
  const addZoneBtn = document.getElementById('add-zone-btn');
  const editZoneBtns = document.querySelectorAll('.edit-zone-btn');
  
  const modalCloseBtn = shippingZoneModal.querySelector('.modal-close');
  const modalCloseBtnFooter = shippingZoneModal.querySelector('.modal-close-btn');
  
  
  const zoneForm = shippingZoneModal.querySelector('form');
  const modalTitle = document.getElementById('zone-modal-title');
  const zoneIdInput = document.getElementById('zone_id');
  const zoneNameInput = document.getElementById('zone_name');
  const postalCodesInput = document.getElementById('postal_codes');
  const shippingFeeInput = document.getElementById('shipping_fee');
  const freeShippingThresholdInput = document.getElementById('free_shipping_threshold');
  const addZoneFormBtn = document.getElementById('add_zone_btn');
  const editZoneFormBtn = document.getElementById('edit_zone_btn');
  
  
  addZoneBtn.addEventListener('click', function() {
    modalTitle.textContent = 'Ajouter une zone de livraison';
    zoneForm.reset();
    zoneIdInput.value = '';
    addZoneFormBtn.style.display = 'block';
    editZoneFormBtn.style.display = 'none';
    shippingZoneModal.style.display = 'block';
  });
  
  
  editZoneBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const zoneId = this.getAttribute('data-id');
      const zoneName = this.getAttribute('data-name');
      const postalCodes = this.getAttribute('data-postal-codes');
      const shippingFee = this.getAttribute('data-fee');
      const freeShippingThreshold = this.getAttribute('data-threshold');
      
      modalTitle.textContent = 'Modifier la zone de livraison';
      zoneIdInput.value = zoneId;
      zoneNameInput.value = zoneName;
      postalCodesInput.value = postalCodes;
      shippingFeeInput.value = shippingFee;
      freeShippingThresholdInput.value = freeShippingThreshold;
      
      addZoneFormBtn.style.display = 'none';
      editZoneFormBtn.style.display = 'block';
      shippingZoneModal.style.display = 'block';
    });
  });
  

  modalCloseBtn.addEventListener('click', function() {
    shippingZoneModal.style.display = 'none';
  });
  
  modalCloseBtnFooter.addEventListener('click', function() {
    shippingZoneModal.style.display = 'none';
  });
  
  window.addEventListener('click', function(event) {
    if (event.target == shippingZoneModal) {
      shippingZoneModal.style.display = 'none';
    }
  });
});
</script>
</body>
</html>