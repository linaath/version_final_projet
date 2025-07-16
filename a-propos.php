<?php
$pageTitle = "À propos";
require_once 'includes/header.php';
?>

<main>
  <div class="page-header">
    <h1>À propos de Délices Sucrés</h1>
    <p>Découvrez notre histoire et notre passion pour la pâtisserie</p>
  </div>

  <section class="about-story">

      <div class="about-image">
        <img src="photo/notre_patesserie.jpg" alt="Notre boutique">
      </div>  
     <div class="about-content">
      <div class="about-text">
        <h2>Notre histoire</h2>
        <p>Fondée en 2010 par le Chef pâtissier Jean Dupont, Délices Sucrés est née d'une passion pour la pâtisserie fine et les saveurs authentiques.</p>
        <p>Après avoir travaillé dans plusieurs établissements prestigieux en France et à l'étranger, Jean a décidé de créer sa propre pâtisserie pour partager sa vision de la gourmandise et de l'excellence.</p>
        <p>Ce qui a commencé comme une petite boutique dans le cœur de Paris s'est rapidement transformé en une référence incontournable pour les amateurs de pâtisseries fines.</p>
        <p>Aujourd'hui, Délices Sucrés continue de s'épanouir, fidèle à ses valeurs d'origine : qualité, créativité et passion.</p>
      </div>
    </div>
  </section>

  <section class="our-values">
    <div class="section-header">
      <h2>Nos valeurs</h2>
      <p>Les principes qui guident notre travail au quotidien</p>
    </div>
    <div class="values-grid">
      <div class="value-card">
        <div class="value-icon">
          <i class="fas fa-star"></i>
        </div>
        <h3>Excellence</h3>
        <p>Nous nous efforçons d'atteindre l'excellence dans chaque création, en portant une attention méticuleuse aux détails et en utilisant des techniques de pâtisserie traditionnelles.</p>
      </div>
      <div class="value-card">
        <div class="value-icon">
          <i class="fas fa-leaf"></i>
        </div>
        <h3>Qualité</h3>
        <p>Nous sélectionnons rigoureusement nos ingrédients, privilégiant les produits locaux, de saison et issus de l'agriculture biologique lorsque c'est possible.</p>
      </div>
      <div class="value-card">
        <div class="value-icon">
          <i class="fas fa-lightbulb"></i>
        </div>
        <h3>Créativité</h3>
        <p>Nous innovons constamment, en créant de nouvelles recettes et en revisitant les classiques pour offrir une expérience gustative unique à nos clients.</p>
      </div>
      <div class="value-card">
        <div class="value-icon">
          <i class="fas fa-heart"></i>
        </div>
        <h3>Passion</h3>
        <p>Notre équipe partage une passion commune pour la pâtisserie, ce qui se reflète dans chaque création que nous proposons à nos clients.</p>
      </div>
    </div>
  </section>

  <section class="about-team">
    <div class="section-header">
      <h2>Notre Équipe</h2>
      <p>Les artisans qui donnent vie à nos créations</p>
    </div>
    <div class="team-grid">
      <div class="team-member">
        <div class="member-image">
          <img src="photo/nicolas_bernardé.jpg" alt="nicolas bernardé">
        </div>
        <div class="member-info">
          <h3>nicolas bernardé</h3>
          <p class="member-title">Chef Pâtissier & Fondateur</p>
          <p class="member-bio">Meilleur Ouvrier de France, Thomas a travaillé dans les plus grandes maisons avant de fonder Délices Sucrés. Sa créativité et sa maîtrise technique sont au cœur de chaque création.</p>
        </div>
      </div>
      <div class="team-member">
        <div class="member-image">
          <img src="photo/nina_métayer.jpg" alt="nina métayer">
        </div>
        <div class="member-info">
          <h3>nina métayer</h3>
          <p class="member-title">Chef Pâtissière</p>
          <p class="member-bio">Formée à l'École de Pâtisserie de Paris, nina apporte sa sensibilité et sa précision à chaque création. Elle est spécialisée dans les entremets et les desserts à l'assiette.</p>
        </div>
      </div>
      <div class="team-member">
        <div class="member-image">
          <img src="photo/Pierre_Marcolini.jpg" alt="Pierre Marcolini">
        </div>
        <div class="member-info">
          <h3>Pierre Marcolini</h3>
          <p class="member-title">Chef Chocolatier</p>
          <p class="member-bio">Passionné par le travail du chocolat,  pierre crée des pièces d'exception et sélectionne les meilleurs crus pour nos créations chocolatées.</p>
        </div>
      </div>
      <div class="team-member">
        <div class="member-image">
          <img src="photo/cedrik_grolet.jpg" alt="cedrik grolet">
        </div>
        <div class="member-info">
          <h3>cedrik grolet</h3>
          <p class="member-title">Chef de Création</p>
          <p class="member-bio">Avec son background en design culinaire,cedrik apporte une dimension artistique unique à nos pâtisseries, créant des visuels aussi beaux que délicieux.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="about-atelier">
    <div class="atelier-content">
      <h2>Notre Atelier</h2>
      <p>Situé au cœur de Paris, notre atelier est un lieu où tradition et innovation se rencontrent. C'est ici que nos artisans pâtissiers travaillent chaque jour pour créer des pâtisseries d'exception.</p>
      <p>Nous utilisons des équipements de pointe tout en préservant les gestes artisanaux qui font la richesse de la pâtisserie française. Chaque création est façonnée à la main avec précision et passion.</p>
      <p>Notre atelier est également un lieu d'expérimentation où nous développons constamment de nouvelles recettes et techniques pour repousser les limites de notre art.</p>
      <a href="contact.php" class="btn-primary">Nous contacter</a>
    </div>
    <div class="atelier-gallery">
      <div class="gallery-item">
        <img src="photo/preparation_patesserie.jpg" alt="Préparation des pâtisseries">
      </div>
    </div>
  </section>

   
   <section class="testimonials">
    <div class="section-header">
      <h2>Ce que disent nos clients</h2>
      <p>Des expériences gustatives inoubliables</p>
    </div>
    <div class="testimonials-slider">
      <div class="testimonial">
        <div class="testimonial-content">
          <p>"Les pâtisseries de Délices Sucrés sont tout simplement extraordinaires. Chaque bouchée est une explosion de saveurs parfaitement équilibrées. Un vrai moment de bonheur !"</p>
        </div>
        <div class="testimonial-author">
          <div class="author-image">
            <img src="photo/marie_l.jpg" alt="Marie L.">
          </div>
          <div class="author-info">
            <h4>Marie L.</h4>
            <p>Cliente fidèle depuis 2018</p>
          </div>
        </div>
      </div>
      <div class="testimonial">
        <div class="testimonial-content">
          <p>"J'ai commandé un gâteau d'anniversaire chez Délices Sucrés et ce fut une révélation. Non seulement il était magnifique visuellement, mais le goût était incomparable. Merci pour cette création qui a émerveillé tous mes invités !"</p>
        </div>
        <div class="testimonial-author">
          <div class="author-image">
            <img src="photo/pierre_d.jpg" alt="Pierre D.">
          </div>
          <div class="author-info">
            <h4>Pierre D.</h4>
            <p>Client depuis 2020</p>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="contact-cta">
    <div class="cta-content">
      <h2>Envie d'en savoir plus ?</h2>
      <p>N'hésitez pas à nous contacter pour toute question ou pour passer une commande spéciale.</p>
      <a href="contact.php" class="btn-primary">Contactez-nous</a>
    </div>
  </section>
</main>

<?php require_once 'includes/footer.php'; ?>