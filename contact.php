<?php
$pageTitle = "Contact";
require_once 'includes/header.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
   
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez entrer une adresse email valide.";
    } else {
      
        try {
            $conn = connectDB();
            
            $stmt = $conn->prepare("
                INSERT INTO contact_messages (name, email, subject, message, created_at)
                VALUES (:name, :email, :subject, :message, NOW())
            ");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            $stmt->execute();
            
            
            $success = true;
            $name = '';
            $email = '';
            $subject = '';
            $message = '';
        } catch(PDOException $e) {
            $error = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer plus tard.";
        }
    }
}
?>

<main>
<section class="page-header">
    <h1>Contactez-nous</h1>
    <p>Nous sommes à votre écoute</p>
  </section>

  <section class="contact-container">
    <div class="contact-info">
      <h2>Nos Coordonnées</h2>
      <div class="info-item">
        <div class="info-icon">
          <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="info-content">
          <h3>Adresse</h3>
          <p>123 Avenue des Pâtissiers<br>75001 Paris, France</p>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon">
          <i class="fas fa-phone"></i>
        </div>
        <div class="info-content">
          <h3>Téléphone</h3>
          <p>+33 1 23 45 67 89</p>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon">
          <i class="fas fa-envelope"></i>
        </div>
        <div class="info-content">
          <h3>Email</h3>
          <p>contact@delices-sucres.fr</p>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="info-content">
          <h3>Horaires d'ouverture</h3>
          <p>Lundi - Vendredi: 8h00 - 19h00<br>
          Samedi: 9h00 - 20h00<br>
          Dimanche: 9h00 - 13h00</p>
        </div>
      </div>
      <div class="social-contact">
        <h3>Suivez-nous</h3>
        <div class="social-icons">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-pinterest-p"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
      </div>
    </div>

    <div class="contact-form-container">
      <?php if ($success): ?>
      <div class="alert alert-success">
        <p>Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.</p>
      </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <p><?php echo $error; ?></p>
      </div>
      <?php endif; ?>

      <form class="contact-form" method="post" action="contact.php">
        <div class="form-group">
          <label for="name">Nom complet *</label>
          <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
        </div>
        <div class="form-group">
          <label for="subject">Sujet *</label>
          <input type="text" id="subject" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
        </div>
        <div class="form-group">
          <label for="message">Message *</label>
          <textarea id="message" name="message" rows="6" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
        </div>
        <div class="form-group checkbox-group">
          <input type="checkbox" id="privacy" name="privacy" required>
          <label for="privacy">J'accepte que mes données soient utilisées pour traiter ma demande conformément à la <a href="politique-confidentialite.php">politique de confidentialité</a>.</label>
        </div>
        <button type="submit" class="btn-primary">Envoyer le message</button>
      </form>
    </div>
  </section>

  <section class="map-section">
    <div class="section-header">
      <h2>Nous trouver</h2>
    </div>
    <div class="map-container">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.142047744348!2d2.3354330160472316!3d48.87456857928921!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e38f817b573%3A0x48d69c30470e7aeb!2sPlace%20de%20l&#39;Op%C3%A9ra%2C%2075009%20Paris!5e0!3m2!1sfr!2sfr!4v1625584998065!5m2!1sfr!2sfr" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
  </section>

  <section class="faq-section">
    <div class="section-header">
      <h2>Questions fréquentes</h2>
    </div>
    <div class="faq-container">
      <div class="faq-item">
        <div class="faq-question">
          <h3>Comment puis-je passer une commande spéciale ?</h3>
          <span class="faq-toggle"><i class="fas fa-plus"></i></span>
        </div>
        <div class="faq-answer">
          <p>Pour les commandes spéciales (gâteaux d'anniversaire, pièces montées, etc.), nous vous recommandons de nous contacter par téléphone ou via ce formulaire au moins 72 heures à l'avance. Nous discuterons de vos besoins et vous proposerons des options adaptées.</p>
        </div>
      </div>
      <div class="faq-item">
        <div class="faq-question">
          <h3>Proposez-vous des options pour les régimes alimentaires spécifiques ?</h3>
          <span class="faq-toggle"><i class="fas fa-plus"></i></span>
        </div>
        <div class="faq-answer">
          <p>Oui, nous proposons des options sans gluten, sans lactose et véganes pour certains de nos produits. N'hésitez pas à nous contacter pour connaître les disponibilités.</p>
        </div>
      </div>
      <div class="faq-item">
        <div class="faq-question">
          <h3>Quelles sont les options de livraison disponibles ?</h3>
          <span class="faq-toggle"><i class="fas fa-plus"></i></span>
        </div>
        <div class="faq-answer">
          <p>Nous proposons la livraison à domicile à Paris et en proche banlieue. Les frais de livraison varient en fonction de la distance. Vous pouvez également opter pour le retrait en boutique.</p>
        </div>
      </div>
      <div class="faq-item">
        <div class="faq-question">
          <h3>Comment conserver vos pâtisseries ?</h3>
          <span class="faq-toggle"><i class="fas fa-plus"></i></span>
        </div>
        <div class="faq-answer">
          <p>La plupart de nos pâtisseries se conservent au réfrigérateur pendant 24 à 48 heures. Nous vous recommandons de les sortir 15 à 30 minutes avant dégustation pour une saveur optimale. Les conseils de conservation spécifiques sont indiqués sur chaque produit.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {

    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(function(item) {
      const question = item.querySelector('.faq-question');
      const answer = item.querySelector('.faq-answer');
      const toggle = item.querySelector('.faq-toggle');
      
      question.addEventListener('click', function() {
        answer.classList.toggle('active');
        toggle.innerHTML = answer.classList.contains('active') ? '<i class="fas fa-minus"></i>' : '<i class="fas fa-plus"></i>';
      });
    });
  });
</script>

<?php require_once 'includes/footer.php'; ?>