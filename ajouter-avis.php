<?php
$pageTitle = "Ajouter un avis";
require_once 'includes/header.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}


if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: mes-commandes.php");
    exit;
}

$orderId = intval($_GET['order_id']);


try {
    $conn = connectDB();
    
  
    $stmt = $conn->prepare("
        SELECT id FROM orders
        WHERE id = :order_id AND user_id = :user_id AND status = 'delivered'
    ");
    $stmt->bindParam(':order_id', $orderId);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header("Location: mes-commandes.php");
        exit;
    }
    
    
    $stmt = $conn->prepare("
        SELECT oi.item_id, i.name, i.image_url
        FROM order_items oi
        JOIN items i ON oi.item_id = i.id
        WHERE oi.order_id = :order_id
    ");
    $stmt->bindParam(':order_id', $orderId);
    $stmt->execute();
    
    $orderItems = $stmt->fetchAll();
    
    
    foreach ($orderItems as $key => $item) {
        $stmt = $conn->prepare("
            SELECT id, rating, comment FROM reviews
            WHERE item_id = :item_id AND user_id = :user_id
        ");
        $stmt->bindParam(':item_id', $item['item_id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $review = $stmt->fetch();
            $orderItems[$key]['has_review'] = true;
            $orderItems[$key]['review'] = $review;
        } else {
            $orderItems[$key]['has_review'] = false;
        }
    }
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des produits de la commande: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
   
    if ($itemId <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        $error = "Veuillez remplir tous les champs correctement.";
    } else {
        try {
            
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
                
                $success = "Votre avis a été mis à jour avec succès.";
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
                
                $success = "Votre avis a été ajouté avec succès.";
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
            
           
            header("Location: ajouter-avis.php?order_id=$orderId&success=1");
            exit;
        } catch(PDOException $e) {
            $error = "Une erreur est survenue lors de l'ajout de votre avis. Veuillez réessayer plus tard.";
        }
    }
}
?>

<main>
  <div class="page-header">
    <h1>Ajouter un avis</h1>
    <p>Partagez votre expérience avec nos produits</p>
  </div>

  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
  <div class="alert alert-success">
    Votre avis a été enregistré avec succès. Merci pour votre retour !
  </div>
  <?php endif; ?>

  <?php if (isset($error)): ?>
  <div class="alert alert-error">
    <?php echo $error; ?>
  </div>
  <?php endif; ?>

  <section class="reviews-section">
    <div class="reviews-intro">
      <p>Vous avez commandé ces produits le <?php echo date('d/m/Y', strtotime($orderInfo['order_date'])); ?>. Partagez votre avis pour aider les autres clients à faire leur choix.</p>
    </div>

    <div class="products-to-review">
      <?php foreach ($orderItems as $item): ?>
      <div class="product-review-card">
        <div class="product-info">
          <div class="product-image">
            <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
          </div>
          <div class="product-details">
            <h3><?php echo $item['name']; ?></h3>
          </div>
        </div>
        
        <?php if ($item['has_review']): ?>
        <div class="existing-review">
          <div class="review-rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <?php if ($i <= $item['review']['rating']): ?>
                <i class="fas fa-star"></i>
              <?php else: ?>
                <i class="far fa-star"></i>
              <?php endif; ?>
            <?php endfor; ?>
          </div>
          <div class="review-comment">
            <p><?php echo nl2br($item['review']['comment']); ?></p>
          </div>
          <button class="btn-secondary edit-review-btn" data-item-id="<?php echo $item['item_id']; ?>">Modifier mon avis</button>
        </div>
        
        <form class="review-form edit-form" id="review-form-<?php echo $item['item_id']; ?>" method="post" action="ajouter-avis.php?order_id=<?php echo $orderId; ?>" style="display: none;">
          <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
          <div class="form-group">
            <label>Votre note</label>
            <div class="rating-select">
              <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" id="star<?php echo $i; ?>-<?php echo $item['item_id']; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $item['review']['rating'] == $i ? 'checked' : ''; ?> required>
              <label for="star<?php echo $i; ?>-<?php echo $item['item_id']; ?>"><i class="far fa-star"></i></label>
              <?php endfor; ?>
            </div>
          </div>
          <div class="form-group">
            <label for="comment-<?php echo $item['item_id']; ?>">Votre commentaire</label>
            <textarea id="comment-<?php echo $item['item_id']; ?>" name="comment" rows="4" required><?php echo $item['review']['comment']; ?></textarea>
          </div>
          <div class="form-actions">
            <button type="button" class="btn-secondary cancel-edit-btn" data-item-id="<?php echo $item['item_id']; ?>">Annuler</button>
            <button type="submit" class="btn-primary">Mettre à jour</button>
          </div>
        </form>
        <?php else: ?>
        <form class="review-form" method="post" action="ajouter-avis.php?order_id=<?php echo $orderId; ?>">
          <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
          <div class="form-group">
            <label>Votre note</label>
            <div class="rating-select">
              <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" id="star<?php echo $i; ?>-<?php echo $item['item_id']; ?>" name="rating" value="<?php echo $i; ?>" required>
              <label for="star<?php echo $i; ?>-<?php echo $item['item_id']; ?>"><i class="far fa-star"></i></label>
              <?php endfor; ?>
            </div>
          </div>
          <div class="form-group">
            <label for="comment-<?php echo $item['item_id']; ?>">Votre commentaire</label>
            <textarea id="comment-<?php echo $item['item_id']; ?>" name="comment" rows="4" required></textarea>
          </div>
          <button type="submit" class="btn-primary">Soumettre</button>
        </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="reviews-actions">
      <a href="mes-commandes.php" class="btn-secondary">Retour à mes commandes</a>
    </div>
  </section>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
   
    const editReviewBtns = document.querySelectorAll('.edit-review-btn');
    const cancelEditBtns = document.querySelectorAll('.cancel-edit-btn');
    
    editReviewBtns.forEach(function(btn) {
      btn.addEventListener('click', function() {
        const itemId = this.getAttribute('data-item-id');
        const existingReview = this.parentElement;
        const editForm = document.getElementById('review-form-' + itemId);
        
        existingReview.style.display = 'none';
        editForm.style.display = 'block';
      });
    });
    
    cancelEditBtns.forEach(function(btn) {
      btn.addEventListener('click', function() {
        const itemId = this.getAttribute('data-item-id');
        const existingReview = this.closest('.product-review-card').querySelector('.existing-review');
        const editForm = document.getElementById('review-form-' + itemId);
        
        editForm.style.display = 'none';
        existingReview.style.display = 'block';
      });
    });
    
  
    const ratingInputs = document.querySelectorAll('.rating-select input');
    
    ratingInputs.forEach(function(input) {
      input.addEventListener('change', function() {
        const ratingSelect = this.closest('.rating-select');
        const stars = ratingSelect.querySelectorAll('label');
        const rating = parseInt(this.value);
        
        stars.forEach(function(star, index) {
          if (5 - index <= rating) {
            star.innerHTML = '<i class="fas fa-star"></i>';
          } else {
            star.innerHTML = '<i class="far fa-star"></i>';
          }
        });
      });
    });
  });
</script>

<?php require_once 'includes/footer.php'; ?>