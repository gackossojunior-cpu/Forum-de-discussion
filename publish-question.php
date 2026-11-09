<?php
require('actions/publishQuestionAction.php');
require('actions/database.php');

// Récupérer les catégories
$getCategories = $bdb->query('SELECT * FROM categories ORDER BY nom');
$categories = $getCategories->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2><i class="fas fa-pen-fancy"></i> Publier une nouvelle question</h2>

                <?php if(isset($errorMsg)){ echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '.$errorMsg.'</div>'; } ?>

                <div class="form-section">
                    <form method="POST">
                        <div class="form-group">
                            <label for="categorie_id" class="form-label">
                                <i class="fas fa-tag"></i> Catégorie
                            </label>
                            <select class="form-control" id="categorie_id" name="categorie_id" required>
                                <option value="">-- Sélectionnez une catégorie --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="titre" class="form-label">
                                <i class="fas fa-heading"></i> Titre de la question
                            </label>
                            <input type="text" class="form-control" id="titre" name="titre" placeholder="Posez une question claire et concise..." required>
                        </div>
                        <div class="form-group">
                            <label for="contenu" class="form-label">
                                <i class="fas fa-file-alt"></i> Détails de la question
                            </label>
                            <textarea class="form-control" id="contenu" name="contenu" rows="8" placeholder="Décrivez votre problème en détail..." required></textarea>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
