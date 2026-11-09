<?php require_once __DIR__ . '/actions/auth/signup.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="auth-container">
            <div class="auth-icon"><i class="fas fa-user-plus"></i></div>
            <h2><i class="fas fa-user-plus"></i> Créer un compte</h2>
            <p class="auth-intro">Rejoignez la communauté et partagez vos idées avec les autres membres.</p>

            <?php if(isset($errorMsg)){echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '.$errorMsg.'</div>';}?>

            <form method="POST">
                <div class="form-group">
                    <label for="pseudo" class="form-label">
                        <i class="fas fa-at"></i> Pseudo
                    </label>
                    <input type="text" class="form-control" id="pseudo" name="pseudo" placeholder="Votre pseudo..." required>
                </div>
                <div class="form-group">
                    <label for="lastname" class="form-label">
                        <i class="fas fa-user"></i> Nom
                    </label>
                    <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Votre nom..." required>
                </div>
                <div class="form-group">
                    <label for="firstname" class="form-label">
                        <i class="fas fa-user"></i> Prénom
                    </label>
                    <input type="text" class="form-control" id="firstname" name="firstname" placeholder="Votre prénom..." required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimum 6 caractères..." required>
                    <small class="text-muted d-block mt-2">Minimum 6 caractères pour votre sécurité.</small>
                </div>
                <button type="submit" class="btn btn-primary" name="validate">
                    <i class="fas fa-check-circle"></i> S'inscrire
                </button>
            </form>

            <div class="auth-link">
                Vous avez déjà un compte? <br>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
