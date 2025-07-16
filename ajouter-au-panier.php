<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}


if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id']) || !isset($_POST['quantity']) || !is_numeric($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

$itemId = intval($_POST['item_id']);
$quantity = intval($_POST['quantity']);


if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'La quantité doit être positive']);
    exit;
}

try {
    $conn = connectDB();
    
 
    $stmt = $conn->prepare("
        SELECT id, name, price, stock FROM items
        WHERE id = :item_id
    ");
    $stmt->bindParam(':item_id', $itemId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit;
    }
    
    $product = $stmt->fetch();
    
   
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
        exit;
    }
    

    if (isset($_SESSION['user_id'])) {
        
        $stmt = $conn->prepare("
            SELECT id FROM carts
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            
            $stmt = $conn->prepare("
                INSERT INTO carts (user_id, created_at)
                VALUES (:user_id, NOW())
            ");
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            $cartId = $conn->lastInsertId();
        } else {
            $cartId = $stmt->fetch()['id'];
        }
        
     
        $stmt = $conn->prepare("
            SELECT id, quantity FROM cart_items
            WHERE cart_id = :cart_id AND item_id = :item_id
        ");
        $stmt->bindParam(':cart_id', $cartId);
        $stmt->bindParam(':item_id', $itemId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            
            $cartItem = $stmt->fetch();
            $newQuantity = $cartItem['quantity'] + $quantity;
            
         
            if ($product['stock'] < $newQuantity) {
                echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
                exit;
            }
            
            $stmt = $conn->prepare("
                UPDATE cart_items
                SET quantity = :quantity
                WHERE id = :id
            ");
            $stmt->bindParam(':quantity', $newQuantity);
            $stmt->bindParam(':id', $cartItem['id']);
            $stmt->execute();
        } else {
           
            $stmt = $conn->prepare("
                INSERT INTO cart_items (cart_id, item_id, quantity, unit_price)
                VALUES (:cart_id, :item_id, :quantity, :unit_price)
            ");
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->bindParam(':item_id', $itemId);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':unit_price', $product['price']);
            $stmt->execute();
        }
    } else {

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$itemId])) {
           
            $newQuantity = $_SESSION['cart'][$itemId]['quantity'] + $quantity;
            
            
            if ($product['stock'] < $newQuantity) {
                echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
                exit;
            }
            
            $_SESSION['cart'][$itemId]['quantity'] = $newQuantity;
        } else {
            
            $_SESSION['cart'][$itemId] = [
                'id' => $itemId,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
    }
     if (isset($_SESSION['cart_count'])) {
        unset($_SESSION['cart_count']);
    }
   
    $cartCount = getCartItemCount(true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté au panier',
        'cart_count' => $cartCount
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue: ' . $e->getMessage()]);
    exit;
}