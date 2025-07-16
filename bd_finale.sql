-- Création de la base de données
CREATE DATABASE IF NOT EXISTS ecommerce_db;
USE ecommerce_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    postal_code VARCHAR(10),
    city VARCHAR(50),
    country VARCHAR(50),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des produits (items)
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category_id INT,
    image_url VARCHAR(255),
    allergens VARCHAR(255),
    ingredients TEXT,
    conservation TEXT,
    rating DECIMAL(3, 1) DEFAULT 0,
    review_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_limited_edition BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table des avis
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des paniers
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des éléments du panier
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Table des commandes
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_fee DECIMAL(10, 2) DEFAULT 5.00,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city VARCHAR(50) NOT NULL,
    shipping_postal_code VARCHAR(10) NOT NULL,
    shipping_country VARCHAR(50) NOT NULL,
    payment_method ENUM('card', 'paypal') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des éléments de commande
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Table des commandes annulées (pour le trigger)
CREATE TABLE cancelled_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    reason VARCHAR(255),
    cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des codes promo
CREATE TABLE promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    discount_percent INT NOT NULL,
    valid_from TIMESTAMP NOT NULL,
    valid_to TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Table des images de produits (pour les galeries)
CREATE TABLE item_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_main BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);
--table de contact messages 
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    is_read BOOLEAN DEFAULT FALSE
);
-- Création de la table collections
CREATE TABLE collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'regular', 'featured', 'limited', 'upcoming'
    availability_date DATE NULL, -- Pour les collections à venir ou limitées
    end_date DATE NULL, -- Pour les collections limitées
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
ALTER TABLE cart_items 
ADD COLUMN unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00 
AFTER quantity;
UPDATE cart_items SET cart_id = 5 WHERE cart_id IN (1, 2);
-- Table des paramètres généraux
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key_group` (`setting_key`, `setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ajout d'une table de liaison entre les collections et les produits (items)
CREATE TABLE collection_items (
    collection_id INT NOT NULL,
    item_id INT NOT NULL,
    display_order INT DEFAULT 0,
    PRIMARY KEY (collection_id, item_id),
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);
-- Table des zones de livraison
CREATE TABLE `shipping_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `postal_codes` text DEFAULT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `free_shipping_threshold` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertion des collections régulières
INSERT INTO collections (name, description, image_url, type, display_order) VALUES
('Classiques Revisités', 'Redécouvrez les grands classiques de la pâtisserie française réinterprétés avec une touche contemporaine.', 'photo/collections_revisité.jpg', 'regular', 1),
('Chocolat Grand Cru', 'Une sélection de créations mettant à l''honneur les meilleurs chocolats du monde, des grands crus aux notes aromatiques exceptionnelles.', 'photo/chocolat_grand_cru.jpg', 'regular', 2),
('Délices Vegan', 'Des pâtisseries 100% végétales, élaborées sans produits d''origine animale mais avec tout le goût et la finesse de nos créations traditionnelles.', 'photo/collection_vegan.jpg', 'regular', 3),
('Sans Gluten', 'Une gamme complète de pâtisseries sans gluten, conçues pour offrir le même plaisir gustatif sans compromis sur la texture.', 'photo/collection_sans_glutin.jpg', 'regular', 4);

-- Insertion de la collection en vedette
INSERT INTO collections (name, description, image_url, type, display_order) VALUES
('Collection Printemps 2025', 'Notre nouvelle collection célèbre l''arrivée du printemps avec des saveurs fraîches et florales. Des créations légères aux couleurs vives qui évoquent la renaissance de la nature.', 'photo/collections_printemps.jpg', 'featured', 0);

-- Insertion des éditions limitées
INSERT INTO collections (name, description, image_url, type, end_date, display_order) VALUES
('Tarte Sakura', 'Une tarte délicate aux fleurs de cerisier, inspirée de la tradition japonaise du Hanami.', 'photo/tarte_sakura.jpg', 'limited', '2025-05-15', 1),
('Éclair Or 24 carats', 'Un éclair d''exception orné de feuilles d''or comestibles, garni d''une crème au champagne.', 'photo/Éclair_Or_24_carats.jpg', 'limited', '2025-04-30', 2);

-- Insertion des collections à venir
INSERT INTO collections (name, description, image_url, type, availability_date, display_order) VALUES
('Collection Été', 'Des créations légères et fruitées pour célébrer l''été', 'photo/collections_ete.jpg', 'upcoming', '2025-05-01', 1),
('Collection Tropicale', 'Un voyage gustatif à travers les saveurs exotiques', 'photo/collections_tropicale.jpg', 'upcoming', '2025-07-01', 2),
('Collection Automne', 'Des saveurs réconfortantes aux notes d''épices et de fruits d''automne', 'photo/collections_automne.jpg', 'upcoming', '2025-09-01', 3),
('Collection Fêtes', 'Des créations festives pour célébrer la fin d''année', 'photo/collections_fetes.jpg', 'upcoming', '2025-11-01', 4);

INSERT INTO collection_items (collection_id, item_id, display_order) VALUES
-- Collection Classiques Revisités (id 1)
(1, 1, 1), (1, 2, 2), (1, 3, 3), (1, 4, 4),
-- Collection Chocolat Grand Cru (id 2)
(2, 5, 1), (2, 6, 2), (2, 7, 3), (2, 8, 4),
-- Collection Délices Vegan (id 3)
(3, 9, 1), (3, 10, 2), (3, 11, 3), (3, 12, 4),
-- Collection Sans Gluten (id 4)
(4, 13, 1), (4, 14, 2), (4, 15, 3), (4, 16, 4),
-- Collection Printemps 2025 (id 5)
(5, 17, 1), (5, 18, 2), (5, 19, 3), (5, 20, 4);

-- Insertion des catégories
INSERT INTO categories (name, description, image_url) VALUES
('Entremets', 'Nos entremets sont élaborés avec les meilleurs ingrédients pour une expérience gustative exceptionnelle.', 'photo/entremet.jpg'),
('Tartes', 'Des tartes aux fruits frais de saison, préparées chaque jour dans notre atelier.', 'photo/tartes.jpg'),
('Macarons', 'Découvrez nos macarons aux saveurs variées, préparés selon la tradition française.', 'photo/macarons.jpg'),
('Éclairs', 'Des éclairs gourmands avec des garnitures créatives et savoureuses.', 'photo/eclaire_chocolat.jpg'),
('Viennoiseries', 'Des viennoiseries pur beurre, croustillantes et légères.', 'photo/croissant.jpg');

-- Insertion des produits
INSERT INTO items (name, description, price, stock, category_id, image_url, allergens, ingredients, conservation, is_featured) VALUES
('Éclair Chocolat Grand Cru', 'Notre éclair au chocolat Grand Cru est une véritable ode à l\'excellence. La pâte à choux, légère et croustillante, renferme une ganache onctueuse élaborée à partir d\'un chocolat noir 70% d\'origine Équateur.', 8.50, 20, 4, 'photo/eclaire_chocolat.jpg', 'Gluten, œufs, produits laitiers', 'Farine, beurre AOP, œufs fermiers, chocolat noir 70% (fèves de cacao, sucre), crème fraîche, sucre, sel de Guérande.', 'À conserver au réfrigérateur et à consommer dans les 24h.', TRUE),
('Tarte Framboise Pistache', 'Une délicieuse tarte composée d\'une pâte sablée, d\'une crème de pistache et de framboises fraîches.', 7.90, 15, 2, 'photo/tarte_framboise_pistache.jpg', 'Gluten, œufs, produits laitiers, fruits à coque', 'Farine, beurre AOP, œufs fermiers, pistaches, framboises, sucre, sel de Guérande.', 'À conserver au réfrigérateur et à consommer dans les 24h.', TRUE),
('Macaron Caramel Beurre Salé', 'Un macaron aux notes gourmandes de caramel au beurre salé.', 2.50, 50, 3, 'photo/macaron_caramel_beurre_sale.jpg', 'Œufs, produits laitiers, fruits à coque', 'Amandes, sucre glace, blancs d\'œufs, sucre, caramel au beurre salé.', 'À conserver dans un endroit frais et sec.', FALSE),
('Entremet Vanille Passion', 'Un entremet léger composé d\'un biscuit moelleux, d\'une mousse à la vanille et d\'un cœur coulant à la passion.', 9.90, 10, 1, 'photo/entremet_vanille_passion.jpg', 'Gluten, œufs, produits laitiers', 'Farine, beurre AOP, œufs fermiers, vanille de Madagascar, fruits de la passion, sucre, gélatine.', 'À conserver au réfrigérateur et à consommer dans les 48h.', TRUE),
('Croissant Pur Beurre', 'Un croissant traditionnel au beurre AOP Charentes-Poitou.', 2.20, 30, 5, 'photo/croissant.jpg', 'Gluten, produits laitiers', 'Farine, beurre AOP Charentes-Poitou, levure, sucre, sel.', 'À consommer le jour même.', FALSE),
('Millefeuille Vanille', 'Un classique de la pâtisserie française revisité avec une crème pâtissière à la vanille de Madagascar.', 6.90, 12, 1, 'photo/mille_feuille_vanille.jpg', 'Gluten, œufs, produits laitiers', 'Pâte feuilletée, crème pâtissière à la vanille de Madagascar, sucre glace.', 'À conserver au réfrigérateur et à consommer dans les 24h.', FALSE),
('Tarte Citron Meringuée', 'Une tarte au citron surmontée d\'une meringue italienne légère et aérienne.', 6.50, 18, 2, 'photo/tarte_citron_meringuée.jpg', 'Gluten, œufs', 'Pâte sablée, citrons, œufs, sucre, beurre.', 'À conserver au réfrigérateur et à consommer dans les 24h.', FALSE),
('Éclair Café', 'Un éclair garni d\'une crème pâtissière au café et recouvert d\'un glaçage café.', 7.90, 15, 4, 'photo/eclaire_café.jpg', 'Gluten, œufs, produits laitiers', 'Farine, beurre AOP, œufs fermiers, café, crème fraîche, sucre, sel de Guérande.', 'À conserver au réfrigérateur et à consommer dans les 24h.', FALSE);

-- Insertion des avis
INSERT INTO reviews (item_id, user_id, rating, comment) VALUES
(1, 2, 5, 'Un éclair exceptionnel ! Le chocolat est intense et la pâte à choux parfaitement exécutée. Je recommande vivement.'),
(1, 3, 4, 'Très bon éclair, le chocolat est de grande qualité. Seul bémol, j\'aurais aimé qu\'il soit un peu plus généreux en ganache.'),
(2, 2, 5, 'Cette tarte est un délice ! Les framboises sont fraîches et la crème de pistache est parfaitement dosée.'),
(3, 3, 4, 'Excellent macaron, le caramel est bien équilibré avec une pointe de sel qui relève parfaitement les saveurs.');

-- Paramètres généraux
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('store_name', 'Délices Sucrés', 'general'),
('store_tagline', 'L\'art de la pâtisserie fine', 'general'),
('store_email', 'contact@delices-sucres.fr', 'general'),
('store_phone', '+33 1 23 45 67 89', 'general'),
('store_address', '123 Avenue des Pâtissiers, 75001 Paris', 'general'),
('store_currency', 'EUR', 'general'),
('store_language', 'fr', 'general');

-- Paramètres de la boutique
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('products_per_page', '12', 'store'),
('default_sorting', 'popularity', 'store'),
('show_stock', '1', 'store'),
('show_out_of_stock', '1', 'store'),
('enable_reviews', '1', 'store'),
('moderate_reviews', '1', 'store');

-- Paramètres de paiement
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('enable_card', '1', 'payment'),
('enable_paypal', '1', 'payment'),
('paypal_sandbox', '1', 'payment');

-- Paramètres de livraison
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('enable_pickup', '1', 'shipping');

-- Paramètres d'emails
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('email_from', 'contact@delices-sucres.fr', 'email'),
('email_name', 'Délices Sucrés', 'email'),
('email_footer', '© 2025 Délices Sucrés. Tous droits réservés. 123 Avenue des Pâtissiers, 75001 Paris', 'email'),
('admin_new_order', '1', 'email'),
('admin_new_customer', '1', 'email'),
('admin_low_stock', '1', 'email'),
('customer_order_confirmation', '1', 'email'),
('customer_order_shipped', '1', 'email');

-- insertion de zone de livraison
INSERT INTO `shipping_zones` (`name`, `postal_codes`, `shipping_fee`, `free_shipping_threshold`) VALUES
('Paris', '75001-75020', 5.00, 50.00),
('Île-de-France', '77,78,91,92,93,94,95', 7.50, 75.00),
('France métropolitaine', '', 9.90, 100.00);

--insertion du code promo 
INSERT INTO promo_codes (code, discount_percent, valid_from, valid_to, is_active, created_at) VALUES
('WELCOME10', 10, '2025-05-01', '2025-06-30', TRUE, NOW());


-- 1.  la procédure FinalizeOrder 
DELIMITER //
CREATE PROCEDURE FinalizeOrder(
    IN p_user_id INT,
    IN p_shipping_address VARCHAR(255),
    IN p_shipping_city VARCHAR(50),
    IN p_shipping_postal_code VARCHAR(10),
    IN p_shipping_country VARCHAR(50),
    IN p_payment_method VARCHAR(20),
    IN p_notes TEXT,
    OUT p_order_id INT
)
BEGIN
    DECLARE v_cart_id INT;
    DECLARE v_total_amount DECIMAL(10, 2) DEFAULT 0;
    DECLARE v_shipping_fee DECIMAL(10, 2) DEFAULT 5.00;
    DECLARE exit_handler BOOLEAN DEFAULT FALSE;
    
   
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    BEGIN
        SET exit_handler = TRUE;
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
   
    SELECT id INTO v_cart_id FROM carts WHERE user_id = p_user_id LIMIT 1;
    

    IF v_cart_id IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Panier introuvable';
    ELSE
       
        SELECT SUM(ci.quantity * i.price) INTO v_total_amount
        FROM cart_items ci
        JOIN items i ON ci.item_id = i.id
        WHERE ci.cart_id = v_cart_id;
        
        
        IF v_total_amount IS NULL OR v_total_amount = 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Le panier est vide';
        ELSE
            
            SELECT COUNT(*) INTO @stock_issues
            FROM cart_items ci
            JOIN items i ON ci.item_id = i.id
            WHERE ci.cart_id = v_cart_id AND i.stock < ci.quantity;
            
            IF @stock_issues > 0 THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'Certains articles ne sont pas disponibles en quantité suffisante';
            ELSE
                
                SELECT IFNULL(sz.shipping_fee, 5.00) INTO v_shipping_fee
                FROM users u
                LEFT JOIN shipping_zones sz ON 
                    u.postal_code BETWEEN SUBSTRING_INDEX(sz.postal_codes, '-', 1) 
                    AND SUBSTRING_INDEX(sz.postal_codes, '-', -1)
                WHERE u.id = p_user_id
                LIMIT 1;
                
             
                SET v_total_amount = v_total_amount + v_shipping_fee;
                
              
                INSERT INTO orders (
                    user_id, 
                    status, 
                    total_amount, 
                    shipping_fee,
                    shipping_address,
                    shipping_city,
                    shipping_postal_code,
                    shipping_country,
                    payment_method,
                    payment_status,
                    notes
                ) VALUES (
                    p_user_id,
                    'pending',
                    v_total_amount,
                    v_shipping_fee,
                    p_shipping_address,
                    p_shipping_city,
                    p_shipping_postal_code,
                    p_shipping_country,
                    p_payment_method,
                    'pending',
                    p_notes
                );
                
              
                SET p_order_id = LAST_INSERT_ID();
                
             
                INSERT INTO order_items (order_id, item_id, quantity, price)
                SELECT p_order_id, ci.item_id, ci.quantity, i.price
                FROM cart_items ci
                JOIN items i ON ci.item_id = i.id
                WHERE ci.cart_id = v_cart_id;
                
                
                DELETE FROM cart_items WHERE cart_id = v_cart_id;
            END IF;
        END IF;
    END IF;
    
    IF exit_handler = FALSE THEN
        COMMIT;
    END IF;
END//
DELIMITER ;


--2. Procédure stockée pour afficher les détails d'une commande pour un client
DELIMITER //
CREATE PROCEDURE GetOrderDetails(IN p_order_id INT, IN p_user_id INT)
BEGIN
    
    DECLARE order_exists INT;
    SELECT COUNT(*) INTO order_exists FROM orders WHERE id = p_order_id AND user_id = p_user_id;
    
    IF order_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Cette commande n\'existe pas ou n\'appartient pas à cet utilisateur';
    ELSE
        
        SELECT o.id AS order_id, 
               o.status, 
               o.total_amount, 
               o.shipping_fee,
               o.shipping_address,
               o.shipping_city,
               o.shipping_postal_code,
               o.shipping_country,
               o.payment_method,
               o.payment_status,
               o.created_at AS order_date
        FROM orders o
        WHERE o.id = p_order_id;
        
        SELECT oi.id AS order_item_id,
               i.name AS item_name,
               i.image_url,
               oi.quantity,
               oi.price AS unit_price,
               (oi.quantity * oi.price) AS subtotal
        FROM order_items oi
        JOIN items i ON oi.item_id = i.id
        WHERE oi.order_id = p_order_id;
        
        SELECT 
            SUM(oi.quantity * oi.price) AS subtotal,
            o.shipping_fee,
            o.total_amount
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.order_id = p_order_id
        GROUP BY o.shipping_fee, o.total_amount;
    END IF;
END //
DELIMITER ;

--3. Procédure stockée pour afficher l'historique des commandes d'un client
DELIMITER //
CREATE PROCEDURE GetOrderHistory(IN p_user_id INT)
BEGIN
    SELECT 
        o.id AS order_id,
        o.status,
        o.total_amount,
        o.created_at AS order_date,
        o.payment_method,
        o.payment_status,
        COUNT(oi.id) AS total_items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = p_user_id
    GROUP BY o.id, o.status, o.total_amount, o.created_at, o.payment_method, o.payment_status
    ORDER BY o.created_at DESC;
END //
DELIMITER ;

-- 4. Procédure stocké pour mettre à jour le statut d'une commande
DELIMITER //
CREATE PROCEDURE update_order_status(
    IN p_order_id INT,
    IN p_status VARCHAR(20),
    IN p_admin_id INT
)
BEGIN
    DECLARE current_status VARCHAR(20);
    

    SELECT status INTO current_status FROM orders WHERE id = p_order_id;
    
    IF current_status IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Commande introuvable';
    ELSE
      
        IF p_status NOT IN ('pending', 'processing', 'shipped', 'delivered', 'cancelled') THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Statut de commande invalide';
        ELSE
            
            UPDATE orders
            SET status = p_status,
                updated_at = NOW()
            WHERE id = p_order_id;
        END IF;
    END IF;
END//
DELIMITER ;

-- 5.Trigger qui vérifie le stock avant insertion
DELIMITER //
CREATE TRIGGER check_stock
BEFORE INSERT ON order_items
FOR EACH ROW
BEGIN
    DECLARE available_stock INT;
    
   
    SELECT stock INTO available_stock
    FROM items
    WHERE id = NEW.item_id;
    
    IF NEW.quantity > available_stock THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Quantité demandée supérieure au stock disponible';
    END IF;
END//
DELIMITER ;

-- 6.Ajout d'un trigger pour garder trace des commandes annulées
DELIMITER //
CREATE TRIGGER order_cancelled_handler
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
  
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
    
        UPDATE items i
        JOIN order_items oi ON i.id = oi.item_id
        SET i.stock = i.stock + oi.quantity
        WHERE oi.order_id = NEW.id;
    
        INSERT INTO cancelled_orders (order_id, user_id, total_amount, reason, cancelled_at)
        VALUES (NEW.id, NEW.user_id, NEW.total_amount, 'Annulation par le client ou l\''administrateur', NOW());
    END IF;
END//
DELIMITER ;

-- 7. Trigger qui met à jour le stock après validation
DELIMITER //
CREATE TRIGGER update_stock AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
   
    UPDATE items
    SET stock = stock - NEW.quantity
    WHERE id = NEW.item_id;
END //
DELIMITER ;



