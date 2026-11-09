<?php
require_once __DIR__ . '/actions/questions/publish.php';
require_once __DIR__ . '/actions/database.php';

// Récupérer les catégories
$categories = (new Forum\Repositories\QuestionRepository($bdb))->categories();
?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="container page-shell mt-5 mb-5" id="main-content">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <span class="eyebrow">À votre tour</span>
                <h1 class="page-title">Posez votre question.</h1>
                <p class="page-intro">Un bon titre et quelques détails donnent envie à la communauté de vous aider.</p>

                <?php if (isset($errorMsg)): ?>
                    <div class="alert alert-danger" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <?php echo forum_escape($errorMsg); ?></div>
                <?php endif; ?>

                <div class="form-section">
                    <form method="POST" data-async-publish="question">
                        <div class="alert" data-form-status hidden></div>
                        <div class="form-group">
                            <label for="categorie_id" class="form-label">
                                <i class="fas fa-tag"></i> Catégorie
                            </label>
                            <select class="form-control" id="categorie_id" name="categorie_id" required>
                                <option value="">-- Sélectionnez une catégorie --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (int)($_POST['categorie_id'] ?? 0) === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo forum_escape($cat['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="titre" class="form-label">
                                <i class="fas fa-heading"></i> Titre de la question
                            </label>
                            <input type="text" class="form-control" id="titre" name="titre" maxlength="255" value="<?php echo forum_escape($_POST['titre'] ?? ''); ?>" placeholder="Posez une question claire et concise…" required>
                        </div>
                        <div class="form-group">
                            <label for="contenu" class="form-label">
                                <i class="fas fa-file-alt"></i> Détails de la question
                            </label>
                            <textarea class="form-control" id="contenu" name="contenu" rows="8" placeholder="Décrivez votre problème en détail…" required><?php echo forum_escape($_POST['contenu'] ?? ''); ?></textarea>
                            <small class="text-muted d-block mt-2">Soyez aussi détaillé que possible pour obtenir de meilleures réponses.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" name="publish">
                                <i class="fas fa-rocket"></i> Publier la question
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/live-forms.js?v=1" defer></script>
</body>
</html>
