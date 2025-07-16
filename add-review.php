<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
   
    if ($itemId <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        header("Location: produit.php?id=$itemId&error=invalid");
        exit;
    }
    
    try {
        $conn = connectDB();
        
    
        $stmt = $conn->prepare("
            SELECT id FROM reviews
            WHERE item_id = :item_id AND user_id = :user_id
        ");
        $stmt->bindParam(':item_id', $itemId);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
          
            $reviewId = $stmt->fetch()['id'];
            
            $stmt = $conn->prepare("
                UPDATE reviews
                SET rating = :rating, comment = :comment, created_at = NOW()
                WHERE id = :id
            ");
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':comment', $comment);
            $stmt->bindParam(':id', $reviewId);
            $stmt->execute();
        } else {
          
            $stmt = $conn->prepare("
                INSERT INTO reviews (item_id, user_id, rating, comment)
                VALUES (:item_id, :user_id, :rating, :comment)
            ");
            $stmt->bindParam(':item_id', $itemId);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':comment', $comment);
            $stmt->execute();
        }
        
       
        $stmt = $conn->prepare("
            UPDATE items i
            SET i.rating = (
                SELECT AVG(r.rating) FROM reviews r WHERE r.item_id = :item_id
            ),
            i.review_count = (
                SELECT COUNT(*) FROM reviews r WHERE r.item_id = :item_id
            )
            WHERE i.id = :item_id
        ");
        $stmt->bindParam(':item_id', $itemId);
        $stmt->execute();
        
        header("Location: produit.php?id=$itemId&review=success");
        exit;
    } catch(PDOException $e) {
        header("Location: produit.php?id=$itemId&error=db");
        exit;
    }
} else {
  
    header("Location: boutique.php");
    exit;
}