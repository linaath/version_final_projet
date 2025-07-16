<?php
/**
 * Génère un jeton CSRF
 * 
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le jeton CSRF est valide
 * 
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}

/**
 * Formate un prix
 * 
 * @param float $price
 * @return string
 */
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

/**
 * Formate une date
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Tronque un texte à une longueur donnée
 * 
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Génère une URL conviviale
 * 
 * @param string $string
 * @return string
 */
function slugify($string) {
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    $string = strtolower($string);
    $string = str_replace(' ', '-', $string);
    return $string;
}

/**
 * Redirige vers une URL
 * 
 * @param string $url
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Affiche un message d'alerte
 * 
 * @param string $message
 * @param string $type
 * @return string
 */
function alert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

/**
 * Nettoie les données d'entrée
 * 
 * @param string $data
 * @return string
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
/**
 * Récupère le nombre d'articles dans le panier
 * @param bool $forceRefresh Force la mise à jour du compteur depuis la base de données
 * @return int
 */
function getCartItemCount($forceRefresh = false) {
    
    
    if ($forceRefresh || !isset($_SESSION['cart_count'])) {
       
        $count = 0;
        
      
        if (isset($_SESSION['user_id'])) {
            try {
                $conn = connectDB();
                
              
                $stmt = $conn->prepare("
                    SELECT SUM(ci.quantity) as total_quantity 
                    FROM cart_items ci 
                    JOIN carts c ON ci.cart_id = c.id 
                    WHERE c.user_id = :user_id
                ");
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && $result['total_quantity'] !== null) {
                    $count = (int)$result['total_quantity'];
                }
            } catch(PDOException $e) {
                error_log("Erreur lors du comptage des articles du panier: " . $e->getMessage());
            }
        } 
       
        else if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                if (isset($item['quantity'])) {
                    $count += (int)$item['quantity'];
                }
            }
        }
        
       
        $_SESSION['cart_count'] = $count;
        error_log("PANIER: Nouveau compteur calculé: " . $count);
        
        return $count;
    }

    return (int)$_SESSION['cart_count'];
}

/**
 * Mettre à jour le compteur du panier et retourner la nouvelle valeur
 * @return int
 */
function updateCartCount() {
    return getCartItemCount(true);
}
function resetCartCount() {
    $_SESSION['cart_count'] = 0;
    error_log("PANIER: Compteur réinitialisé à 0");
}
?>