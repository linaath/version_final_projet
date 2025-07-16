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
  echo json_encode(['error' => 'ID d\'utilisateur invalide']);
  exit;
}

$userId = intval($_GET['id']);

try {
  $conn = connectDB();
  $stmt = $conn->prepare("
      SELECT * FROM users WHERE id = :id
  ");
  $stmt->bindParam(':id', $userId);
  $stmt->execute();
  
  if ($stmt->rowCount() === 0) {
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Utilisateur non trouvé']);
      exit;
  }
  
  $user = $stmt->fetch();
  header('Content-Type: application/json');
  echo json_encode($user);
  
} catch(PDOException $e) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Erreur lors de la récupération de l\'utilisateur: ' . $e->getMessage()]);
  exit;
}