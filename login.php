<?php require_once __DIR__ . '/actions/auth/login.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="auth-container">
            <div class="auth-icon"><i class="fas fa-comments"></i></div>
            <h2><i class="fas fa-sign-in-alt"></i> Connexion</h2>
            <p class="auth-intro">Ravi de vous revoir. Connectez-vous pour participer aux échanges.</p>

            <?php if(isset($errorMsg)){echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '.$errorMsg.'</div>';}?>

            <form method="POST">
                <div class="form-group">
                    <label for="pseudo" class="form-label">
                        <i class="fas fa-user"></i> Pseudo
                    </label>
                    <input type="text" class="form-control" id="pseudo" name="pseudo" placeholder="Votre pseudo..." required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Votre mot de passe..." required>
                </div>
                <button type="submit" class="btn btn-primary" name="validate">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <div class="auth-link">
                Pas encore de compte? <br>
                <a href="signup.php">
                    <i class="fas fa-user-plus"></i> Créer un compte
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
