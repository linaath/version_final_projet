<?php
$pageTitle = "Accueil";
require_once 'includes/header.php';

try {
    $conn = connectDB();
    
 
    $stmt = $conn->query("
        SELECT i.*, c.name as category_name
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE i.is_featured = 1
        ORDER BY i.created_at DESC
        LIMIT 4
    ");
    $featuredProducts = $stmt->fetchAll();
    
   
    $stmt = $conn->query("
        SELECT i.*, c.name as category_name
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        ORDER BY i.created_at DESC
        LIMIT 8
    ");
    $newProducts = $stmt->fetchAll();
    
 
    $stmt = $conn->query("
        SELECT i.*, c.name as category_name
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE i.rating > 0
        ORDER BY i.rating DESC, i.review_count DESC
        LIMIT 4
    ");
    $topRatedProducts = $stmt->fetchAll();
    

    $stmt = $conn->query("
        SELECT c.*, COUNT(i.id) as product_count
        FROM categories c
        LEFT JOIN items i ON c.id = i.category_id
        GROUP BY c.id
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des produits: " . $e->getMessage());
}
?>

<main>

  <section class="hero-slideshow">
    <div class="slideshow-container">
      <div class="slide active" style="background-image: url('photo/hero_section.jpg')"></div>
      <div class="slide" style="background-image: url('photo/hero_section._3.jpg')"></div>
      <div class="slide" style="background-image: url('photo/hero_section._2.jpg')"></div>
      <div class="slide" style="background-image: url('photo/hero_section_1.jpg')"></div>
      <div class="slideshow-overlay"></div>
    </div>
    
    <div class="hero-content">
      <h1>L'Art de la Pâtisserie</h1>
      <p>Des créations uniques qui éveillent les sens</p>
      <a href="boutique.php" class="btn-primary">Découvrir nos créations</a>
    </div>
    
    <div class="slideshow-indicators">
      <span class="indicator active" data-slide="0"></span>
      <span class="indicator" data-slide="1"></span>
      <span class="indicator" data-slide="2"></span>
      <span class="indicator" data-slide="3"></span>
    </div>
  </section>


  <section class="categories">
    <div class="section-header">
      <h2>Nos Catégories</h2>
      <p>Explorez notre univers gourmand</p>
    </div>
    <div class="categories-grid">
      <?php foreach ($categories as $category): ?>
      <div class="category-card">
        <img src="<?php echo $category['image_url']; ?>" alt="<?php echo $category['name']; ?>">
        <div class="category-content">
          <h3><?php echo $category['name']; ?></h3>
          <a href="boutique.php?category=<?php echo urlencode($category['name']); ?>" class="btn-outline">Découvrir</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  
<section class="about-preview">     
  <div class="about-content">       
    <h2 class="section-title">Notre Philosophie</h2>       
    <div class="title-accent"></div>
    <p class="philosophy-text">Chez Délices Sucrés, nous croyons que la pâtisserie est un art qui mérite d'être célébré. Chaque création est le fruit d'un savoir-faire artisanal, d'ingrédients soigneusement sélectionnés et d'une passion sans limite pour l'excellence.</p>       
    
    <div class="philosophy-quote">
      <p>"La pâtisserie est une harmonie entre tradition et innovation, un équilibre parfait entre saveurs et textures."</p>
    </div>
    
    <p class="philosophy-text">Notre chef pâtissier s'inspire des techniques traditionnelles tout en les réinventant avec une touche contemporaine pour vous offrir des expériences gustatives uniques.</p>       
    
    <a href="a-propos.php" class="btn-secondary">En savoir plus</a>     
  </div>     
  
  <div class="about-image">       
    <img src="photo/nicolas_bernardé.jpg" alt="Chef pâtissier">     
  </div>   
</section>
 
  <section class="instagram-feed">
    <div class="section-header">
      <h2>Nos créations sur Instagram</h2>
      <p>Suivez-nous <a href="https://www.instagram.com/" target="_blank">@delices_sucres</a> pour plus d'inspirations gourmandes</p>
    </div>
    <div class="instagram-grid">
      <?php foreach ($featuredProducts as $product): ?>
      <div class="instagram-item">
        <div class="instagram-image">
          <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
          <a href="https://www.instagram.com/" target="_blank" class="instagram-link">
            <i class="fab fa-instagram"></i>
          </a>
        </div>
        <div class="instagram-details">
          <h4><?php echo $product['name']; ?></h4>
          <p><?php echo substr($product['description'], 0, 100) . '...'; ?></p>
        </div>
      </div>
      <?php endforeach; ?>
      
     
      <?php foreach (array_slice($newProducts, 0, 4) as $product): ?>
      <div class="instagram-item">
        <div class="instagram-image">
          <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
          <a href="https://www.instagram.com/" target="_blank" class="instagram-link">
            <i class="fab fa-instagram"></i>
          </a>
        </div>
        <div class="instagram-details">
          <h4><?php echo $product['name']; ?></h4>
          <p><?php echo substr($product['description'], 0, 100) . '...'; ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="instagram-follow-btn">
      <a href="https://www.instagram.com/" target="_blank" class="btn-secondary">Voir plus sur Instagram</a>
    </div>
  </section>


  <section class="newsletter">
    <div class="newsletter-content">
      <h2>Restez informé</h2>
      <p>Inscrivez-vous à notre newsletter pour découvrir nos nouvelles créations et offres exclusives</p>
      <form class="newsletter-form" method="post" action="subscribe.php">
        <input type="email" name="email" placeholder="Votre adresse email" required>
        <button type="submit" class="btn-primary">S'inscrire</button>
      </form>
    </div>
  </section>
</main>
<?php require_once 'includes/footer.php'; ?>