<?php
require_once '../config/database.php';
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Accès non autorisé']);
  exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'ID de commande invalide']);
  exit;
}

$orderId = intval($_GET['id']);

try {
  $conn = connectDB();
  $stmt = $conn->prepare("
      SELECT * FROM orders WHERE id = :id
  ");
  $stmt->bindParam(':id', $orderId);
  $stmt->execute();
  
  if ($stmt->rowCount() === 0) {
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Commande non trouvée']);
      exit;
  }
  
  $order = $stmt->fetch();
  $stmt = $conn->prepare("
      SELECT * FROM users WHERE id = :user_id
  ");
  $stmt->bindParam(':user_id', $order['user_id']);
  $stmt->execute();
  
  $customer = $stmt->fetch();
  $stmt = $conn->prepare("
      SELECT oi.*, i.name as item_name, i.image_url
      FROM order_items oi
      JOIN items i ON oi.item_id = i.id
      WHERE oi.order_id = :order_id
  ");
  $stmt->bindParam(':order_id', $orderId);
  $stmt->execute();
  
  $items = $stmt->fetchAll();
  $subtotal = 0;
  foreach ($items as $item) {
      $subtotal += $item['unit_price'] * $item['quantity'];
  }
  
  $shippingFee = $order['shipping_fee'];
  $totalAmount = $order['total_amount'];
  header('Content-Type: application/json');
  echo json_encode([
      'order' => $order,
      'customer' => $customer,
      'items' => $items,
      'totals' => [
          'subtotal' => $subtotal,
          'shipping_fee' => $shippingFee,
          'total_amount' => $totalAmount
      ]
  ]);
  
} catch(PDOException $e) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Erreur lors de la récupération des détails de la commande: ' . $e->getMessage()]);
  exit;
}