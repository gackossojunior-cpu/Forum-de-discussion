<?php require_once __DIR__ . '/actions/auth/login.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="container" id="main-content">
        <div class="auth-container">
            <div class="auth-icon" aria-hidden="true"><i class="fas fa-comments"></i></div>
            <span class="eyebrow auth-eyebrow">Votre espace communauté</span>
            <h1>Bon retour.</h1>
            <p class="auth-intro">Ravi de vous revoir. Connectez-vous pour participer aux échanges.</p>

            <?php if (isset($errorMsg)): ?>
                <div class="alert alert-danger" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <?php echo forum_escape($errorMsg); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="pseudo" class="form-label">Pseudo</label>
                    <input type="text" class="form-control" id="pseudo" name="pseudo" placeholder="Votre pseudo…" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Votre mot de passe…" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary" name="validate">
                    Se connecter <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <div class="auth-link">
                Pas encore membre ? <a href="signup.php">Créer un compte</a>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
