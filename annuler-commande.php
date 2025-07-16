<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    header("Location: mes-commandes.php");
    exit;
}

$orderId = intval($_POST['order_id']);

try {
    $conn = connectDB();
    $stmt = $conn->prepare("
        SELECT id, status FROM orders
        WHERE id = :order_id AND user_id = :user_id
    ");
    $stmt->bindParam(':order_id', $orderId);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header("Location: mes-commandes.php");
        exit;
    }
    
    $order = $stmt->fetch();
   
    if ($order['status'] !== 'pending' && $order['status'] !== 'processing') {
        header("Location: commande-details.php?id=$orderId&error=cannot_cancel");
        exit;
    }
  
    $stmt = $conn->prepare("
        UPDATE orders
        SET status = 'cancelled'
        WHERE id = :order_id
    ");
    $stmt->bindParam(':order_id', $orderId);
    $stmt->execute();
    
    header("Location: mes-commandes.php?cancelled=1");
    exit;
    
} catch(PDOException $e) {
    die("Erreur lors de l'annulation de la commande: " . $e->getMessage());
}