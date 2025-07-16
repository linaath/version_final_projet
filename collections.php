<?php
$pageTitle = "Collections | Délices Sucrés";
require_once 'includes/header.php';

try {
    $conn = connectDB();
    $stmt = $conn->query("
        SELECT *
        FROM collections
        WHERE type = 'featured'
        ORDER BY display_order
        LIMIT 1
    ");
    $featuredCollection = $stmt->fetch();
    
  
    $stmt = $conn->query("
        SELECT *
        FROM collections
        WHERE type = 'regular'
        ORDER BY display_order
    ");
    $regularCollections = $stmt->fetchAll();
    
   
    $stmt = $conn->query("
        SELECT *
        FROM collections
        WHERE type = 'limited'
        AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY display_order
    ");
    $limitedCollections = $stmt->fetchAll();
    
   
    $stmt = $conn->query("
        SELECT *
        FROM collections
        WHERE type = 'upcoming'
        ORDER BY availability_date, display_order
    ");
    $upcomingCollections = $stmt->fetchAll();
    
    
    function getCollectionProducts($conn, $collectionId, $limit = 4) {
        $stmt = $conn->prepare("
            SELECT i.*
            FROM items i
            JOIN collection_items ci ON i.id = ci.item_id
            WHERE ci.collection_id = :collection_id
            ORDER BY ci.display_order
            LIMIT :limit
        ");
        $stmt->bindParam(':collection_id', $collectionId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
} catch(PDOException $e) {
    die("Erreur lors de la récupération des collections: " . $e->getMessage());
}
?>

<main>
  <section class="page-header">
    <h1>Nos Collections</h1>
    <p>Découvrez nos créations saisonnières et nos collections exclusives</p>
  </section>

  <section class="collections-showcase">
   
    <?php if ($featuredCollection): ?>
    <div class="collection-card featured">
      <div class="collection-image">
        <img src="<?php echo $featuredCollection['image_url']; ?>" alt="<?php echo $featuredCollection['name']; ?>">
      </div>
      <div class="collection-content">
        <h2><?php echo $featuredCollection['name']; ?></h2>
        <p><?php echo $featuredCollection['description']; ?></p>
        <a href="boutique.php?collection=<?php echo urlencode($featuredCollection['name']); ?>" class="btn-primary">Découvrir</a>
      </div>
    </div>
    <?php endif; ?>


    <div class="collections-grid">
      <?php foreach ($regularCollections as $collection): ?>
      <div class="collection-card">
        <div class="collection-image">
          <img src="<?php echo $collection['image_url']; ?>" alt="Collection <?php echo $collection['name']; ?>">
        </div>
        <div class="collection-content">
          <h2><?php echo $collection['name']; ?></h2>
          <p><?php echo $collection['description']; ?></p>
          <a href="boutique.php?collection=<?php echo urlencode($collection['name']); ?>" class="btn-primary">Découvrir</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="limited-edition">
    <div class="section-header">
      <h2>Éditions Limitées</h2>
      <p>Des créations éphémères disponibles pour une durée limitée</p>
    </div>
    <div class="limited-items">
      <?php foreach ($limitedCollections as $limited): ?>
      <div class="limited-item">
        <div class="limited-image">
          <img src="<?php echo $limited['image_url']; ?>" alt="Édition limitée - <?php echo $limited['name']; ?>">
          <div class="limited-badge">Édition Limitée</div>
        </div>
        <div class="limited-content">
          <h3><?php echo $limited['name']; ?></h3>
          <p><?php echo $limited['description']; ?></p>
          <?php if ($limited['end_date']): ?>
          <p class="limited-availability">Disponible jusqu'au <?php echo date('j F Y', strtotime($limited['end_date'])); ?></p>
          <?php endif; ?>
          <a href="produit.php?collection=<?php echo urlencode($limited['name']); ?>" class="btn-secondary">Voir détails</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="collection-calendar">
    <div class="section-header">
      <h2>Calendrier des Collections</h2>
      <p>Nos prochaines collections et événements</p>
    </div>
    <div class="calendar-container">
      <?php 
      $seasons = ['spring', 'summer', 'autumn', 'winter'];
      $seasonIndex = 0;
      foreach ($upcomingCollections as $upcoming): 
        $season = $seasons[$seasonIndex % count($seasons)];
        $month = date('M', strtotime($upcoming['availability_date']));
        $year = date('Y', strtotime($upcoming['availability_date']));
        $seasonIndex++;
      ?>
      <div class="calendar-item <?php echo $season; ?>">
        <div class="calendar-date">
          <span class="month"><?php echo $month; ?></span>
          <span class="year"><?php echo $year; ?></span>
        </div>
        <div class="calendar-content">
          <h3><?php echo $upcoming['name']; ?></h3>
          <p><?php echo $upcoming['description']; ?></p>
        </div>
        <div class="coming-soon-overlay">
          <div class="coming-soon-text">Coming soon...</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<?php require_once 'includes/footer.php'; ?>