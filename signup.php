<?php require_once __DIR__ . '/actions/auth/signup.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="container" id="main-content">
        <div class="auth-container">
            <div class="auth-icon" aria-hidden="true"><i class="fas fa-user-plus"></i></div>
            <span class="eyebrow auth-eyebrow">La communauté commence ici</span>
            <h1>Rejoignez-nous.</h1>
            <p class="auth-intro">Rejoignez la communauté et partagez vos idées avec les autres membres.</p>

            <?php if (isset($errorMsg)): ?>
                <div class="alert alert-danger" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <?php echo forum_escape($errorMsg); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="pseudo" class="form-label">Pseudo</label>
                    <input type="text" class="form-control" id="pseudo" name="pseudo" placeholder="Choisissez un pseudo…" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="lastname" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Votre nom…" autocomplete="family-name" required>
                </div>
                <div class="form-group">
                    <label for="firstname" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="firstname" name="firstname" placeholder="Votre prénom…" autocomplete="given-name" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="6 caractères minimum…" autocomplete="new-password" minlength="6" required>
                    <small class="text-muted d-block mt-2">Minimum 6 caractères pour votre sécurité.</small>
                </div>
                <button type="submit" class="btn btn-primary" name="validate">
                    Créer mon compte <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <div class="auth-link">
                Déjà membre ? <a href="login.php">Se connecter</a>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
