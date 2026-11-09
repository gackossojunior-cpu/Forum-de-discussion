<?php require_once __DIR__ . '/actions/questions/edit.php';
require_once __DIR__ . '/actions/database.php';

if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$question_id = (int)$_GET['id'];

// Récupérer la question
$question = (new Forum\Repositories\QuestionRepository($bdb))->findOwned(
    $question_id,
    (int)$_SESSION['id']
);

if (!$question) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <a href="question-detail.php?id=<?php echo $question_id; ?>" class="text-decoration-none mb-4 d-inline-block">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>

                <h2 class="mb-4"><i class="fas fa-edit"></i> Modifier la question</h2>

                <?php if (isset($errorMsg)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo forum_escape($errorMsg); ?></div>
                <?php endif; ?>

                <div class="form-section">
                    <form method="POST" action="actions/editQuestionAction.php">
                        <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                        <div class="form-group">
                            <label for="titre" class="form-label">
                                <i class="fas fa-heading"></i> Titre de la question
                            </label>
                            <input type="text" class="form-control" id="titre" name="titre" value="<?php echo forum_escape($question['titre']); ?>" placeholder="Titre de votre question..." required>
                        </div>
                        <div class="form-group">
                            <label for="contenu" class="form-label">
                                <i class="fas fa-file-alt"></i> Détails de la question
                            </label>
                            <textarea class="form-control" id="contenu" name="contenu" rows="8" placeholder="Décrivez votre question en détail..." required><?php echo forum_escape($question['contenu']); ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" name="update">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <a href="question-detail.php?id=<?php echo $question_id; ?>" class="btn btn-secondary">
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
