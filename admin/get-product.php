<?php
require_once '../config/database.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de produit invalide']);
    exit;
}

$productId = intval($_GET['id']);

try {
    $conn = connectDB();
    $stmt = $conn->prepare("
        SELECT * FROM items WHERE id = :id
    ");
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Produit non trouvé']);
        exit;
    }
    
    $product = $stmt->fetch();
    header('Content-Type: application/json');
    echo json_encode($product);
    
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de la récupération du produit: ' . $e->getMessage()]);
    exit;
}