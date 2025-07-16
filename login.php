<?php
$pageTitle = "Connexion";
require_once 'includes/header.php';

$email = '';
$password = '';
$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
       
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } else {
            try {
                $conn = connectDB();
                $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, is_admin FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch();
                    
                    
                    if (password_verify($password, $user['password'])) {
                       
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['is_admin'] = (bool)$user['is_admin'];
                        
                        $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = :user_id");
                        $stmt->bindParam(':user_id', $user['id']);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() === 0) {
                          
                            $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (:user_id)");
                            $stmt->bindParam(':user_id', $user['id']);
                            $stmt->execute();
                        }
                        
                       
                        header("Location: index.php");
                        exit;
                    } else {
                        $error = "Email ou mot de passe incorrect.";
                    }
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            } catch(PDOException $e) {
                $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
            }
        }
    } elseif (isset($_POST['register'])) {
      
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm-password'];
        
       
        if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = "Veuillez remplir tous les champs.";
        } elseif ($password !== $confirmPassword) {
            $error = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($password) < 8) {
            $error = "Le mot de passe doit contenir au moins 8 caractères.";
        } else {
            try {
                $conn = connectDB();
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = "Cet email est déjà utilisé.";
                } else {
                   
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                  
                    $stmt = $conn->prepare("
                        INSERT INTO users (firstname, lastname, email, password)
                        VALUES (:firstname, :lastname, :email, :password)
                    ");
                    $stmt->bindParam(':firstname', $firstname);
                    $stmt->bindParam(':lastname', $lastname);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashedPassword);
                    $stmt->execute();
                    $userId = $conn->lastInsertId();
                    
                  
                    $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (:user_id)");
                    $stmt->bindParam(':user_id', $userId);
                    $stmt->execute();
                    
                    $success = "Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.";
                    
                    $firstname = '';
                    $lastname = '';
                    $email = '';
                }
            } catch(PDOException $e) {
                $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
            }
        }
    }
}
?>

<main>
  <div class="page-header">
    <h1>Connexion / Inscription</h1>
    <p>Connectez-vous à votre compte ou créez-en un nouveau</p>
  </div>

  <div class="auth-container">
    <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
      <div class="alert alert-success">
        <?php echo $success; ?>
      </div>
    <?php endif; ?>
    
    <div class="auth-tabs">
      <button class="auth-tab <?php echo !isset($_POST['register']) ? 'active' : ''; ?>" data-tab="login">Connexion</button>
      <button class="auth-tab <?php echo isset($_POST['register']) ? 'active' : ''; ?>" data-tab="register">Inscription</button>
    </div>

    <form id="login" class="auth-form <?php echo !isset($_POST['register']) ? 'active' : ''; ?>" method="post" action="login.php">
      <h2>Connexion</h2>
      <div class="form-group">
        <label for="login-email">Email</label>
        <input type="email" id="login-email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
      </div>
      <div class="form-group">
        <label for="login-password">Mot de passe</label>
        <div class="password-input">
          <input type="password" id="login-password" name="password" required>
          <button type="button" class="toggle-password"><i class="far fa-eye"></i></button>
        </div>
      </div>
      <div class="form-options">
        <div class="remember-me">
          <input type="checkbox" id="remember-me" name="remember-me">
          <label for="remember-me">Se souvenir de moi</label>
        </div>
        <a href="forgot-password.php" class="forgot-password">Mot de passe oublié ?</a>
      </div>
      <button type="submit" name="login" class="btn-primary btn-full">Se connecter</button>

      <div class="social-login">
        <p>Ou connectez-vous avec</p>
        <div class="social-buttons">
          <button type="button" class="social-btn google"><i class="fab fa-google"></i> Google</button>
          <button type="button" class="social-btn facebook"><i class="fab fa-facebook-f"></i> Facebook</button>
        </div>
      </div>
    </form>

    <form id="register" class="auth-form <?php echo isset($_POST['register']) ? 'active' : ''; ?>" method="post" action="login.php">
      <h2>Inscription</h2>
      <div class="form-row">
        <div class="form-group">
          <label for="firstname">Prénom</label>
          <input type="text" id="firstname" name="firstname" value="<?php echo isset($firstname) ? htmlspecialchars($firstname) : ''; ?>" required>
        </div>
        <div class="form-group">
          <label for="lastname">Nom</label>
          <input type="text" id="lastname" name="lastname" value="<?php echo isset($lastname) ? htmlspecialchars($lastname) : ''; ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label for="register-email">Email</label>
        <input type="email" id="register-email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
      </div>
      <div class="form-group">
        <label for="register-password">Mot de passe</label>
        <div class="password-input">
          <input type="password" id="register-password" name="password" required>
          <button type="button" class="toggle-password"><i class="far fa-eye"></i></button>
        </div>
        <div class="password-strength">
          <div class="strength-bar">
            <div class="strength-fill" style="width: 0%"></div>
          </div>
          <div class="strength-text">Mot de passe très faible</div>
        </div>
      </div>
      <div class="form-group">
        <label for="confirm-password">Confirmer le mot de passe</label>
        <div class="password-input">
          <input type="password" id="confirm-password" name="confirm-password" required>
          <button type="button" class="toggle-password"><i class="far fa-eye"></i></button>
        </div>
      </div>
      <div class="form-options">
        <div class="terms-check">
          <input type="checkbox" id="terms" name="terms" required>
          <label for="terms">J'accepte les <a href="conditions-generales.php">conditions générales</a></label>
        </div>
      </div>
      <button type="submit" name="register" class="btn-primary btn-full">S'inscrire</button>
    </form>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>