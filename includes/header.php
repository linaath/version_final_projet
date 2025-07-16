<?php

session_start();


require_once dirname(__DIR__) . '/config/database.php';

require_once __DIR__ . '/functions.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}


function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}


$cartCount = getCartItemCount();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle . ' | Délices Sucrés' : 'Délices Sucrés'; ?></title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
   
    .user-dropdown {
      position: relative;
      display: inline-block;
    }
    .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      background-color: #fff;
      min-width: 180px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
      z-index: 1000;
      border-radius: 4px;
      padding: 8px 0;
    }
    .user-dropdown:hover .dropdown-menu {
      display: block;
    }
    .dropdown-menu a {
      color: #333;
      padding: 8px 15px;
      text-decoration: none;
      display: flex;
      align-items: center;
    }
    .dropdown-menu a i {
      margin-right: 8px;
      width: 16px;
      text-align: center;
    }
    .dropdown-menu a:hover {
      background-color: #f5f5f5;
    }
  </style>
</head>
<body>
  <header>
    <div class="header-container">
      <div class="logo">
        <img src="photo/logo.png" alt="Logo">
      </div>
      <nav>
        <ul class="menu">
          <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Accueil</a></li>
          <li><a href="boutique.php" <?php echo basename($_SERVER['PHP_SELF']) == 'boutique.php' ? 'class="active"' : ''; ?>>Boutique</a></li>
          <li><a href="collections.php" <?php echo basename($_SERVER['PHP_SELF']) == 'collections.php' ? 'class="active"' : ''; ?>>Collections</a></li>
          <li><a href="a-propos.php" <?php echo basename($_SERVER['PHP_SELF']) == 'a-propos.php' ? 'class="active"' : ''; ?>>À propos</a></li>
          <li><a href="contact.php" <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'class="active"' : ''; ?>>Contact</a></li>
        </ul>
      </nav>
      <div class="header-icons">
        <a href="#" class="search-icon"><i class="fas fa-search"></i></a>
        <a href="panier.php" class="cart-icon"><i class="fas fa-shopping-bag"></i><span class="cart-count"><?php echo $cartCount; ?></span></a>
        <?php if (isLoggedIn()): ?>
          <div class="user-dropdown">
            <a href="#" class="user-icon"><i class="fas fa-user"></i></a>
            <div class="dropdown-menu">
             
              <a href="mes-commandes.php"><i class="fas fa-shopping-basket"></i>Mes Achats</a>
              <?php if (isAdmin()): ?>
                <a href="admin/index.php"><i class="fas fa-cog"></i> Administration</a>
              <?php endif; ?>
              <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
          </div>
        <?php else: ?>
          <a href="login.php" class="user-icon"><i class="fas fa-user"></i></a>
        <?php endif; ?>
      </div>
      <div class="hamburger-menu">
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
      </div>
    </div>
  </header>