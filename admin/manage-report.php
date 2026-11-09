<?php
require_once __DIR__ . '/../includes/admin.php';
forum_require_admin();

$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$reportId || $reportId < 1) {
    header('Location: dashboard.php');
    exit;
}

$statement = $bdb->prepare(
    'SELECT s.*, u.pseudo, q.titre AS question_titre, q.contenu AS question_contenu,
            r.contenu AS reponse_contenu
     FROM signalements s
     LEFT JOIN users u ON u.id = s.utilisateur_id
     LEFT JOIN questions q ON q.id = s.question_id
     LEFT JOIN reponses r ON r.id = s.reponse_id
     WHERE s.id = ?'
);
$statement->execute([$reportId]);
$report = $statement->fetch();

if (!$report) {
    http_response_code(404);
    $report = null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!forum_admin_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $errorMessage = 'La session du formulaire a expiré. Rechargez la page et réessayez.';
    } elseif (!in_array($report['statut'], ['en_attente', 'pending'], true)) {
        $_SESSION['admin_flash'] = [
            'type' => 'warning',
            'message' => 'Ce signalement a déjà été traité.',
        ];
        header('Location: dashboard.php');
        exit;
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'accept' || $action === 'refuse') {
            try {
                $bdb->beginTransaction();

                if ($action === 'accept') {
                    $update = $bdb->prepare(
                        "UPDATE signalements SET statut = 'accepte', question_id = NULL, reponse_id = NULL WHERE id = ?"
                    );
                    $update->execute([$reportId]);

                    if ($report['question_id']) {
                        $delete = $bdb->prepare('DELETE FROM questions WHERE id = ?');
                        $delete->execute([$report['question_id']]);
                    } elseif ($report['reponse_id']) {
                        $delete = $bdb->prepare('DELETE FROM reponses WHERE id = ?');
                        $delete->execute([$report['reponse_id']]);
                    }
                } else {
                    $update = $bdb->prepare("UPDATE signalements SET statut = 'refuse' WHERE id = ?");
                    $update->execute([$reportId]);
                }

                $bdb->commit();
                $_SESSION['admin_flash'] = [
                    'type' => 'success',
                    'message' => $action === 'accept'
                        ? 'Signalement accepté et contenu supprimé.'
                        : 'Signalement refusé. Le contenu a été conservé.',
                ];
                header('Location: dashboard.php');
                exit;
            } catch (PDOException $exception) {
                if ($bdb->inTransaction()) {
                    $bdb->rollBack();
                }
                error_log('Admin report moderation failed: ' . $exception->getMessage());
                $errorMessage = 'Le signalement n’a pas pu être traité. Réessayez.';
            }
        } else {
            $errorMessage = 'Action invalide.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../includes/head.php'; ?>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container page-shell mt-5 mb-5" id="main-content">
        <a href="dashboard.php" class="text-decoration-none d-inline-block mb-4">
            <i class="fas fa-arrow-left"></i> Retour aux signalements
        </a>

        <?php if (!$report): ?>
            <div class="alert alert-warning">Ce signalement n’existe pas ou a déjà été supprimé.</div>
        <?php else: ?>
            <header class="mb-4">
                <span class="eyebrow">Modération</span>
                <h1 class="h2">Signalement #<?php echo (int)$reportId; ?></h1>
            </header>

            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger" role="alert"><?php echo forum_escape($errorMessage); ?></div>
            <?php endif; ?>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Signalé par</dt>
                        <dd class="col-sm-9"><?php echo forum_escape($report['pseudo'] ?? 'Compte supprimé'); ?></dd>
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9"><?php echo date('d/m/Y H:i', strtotime($report['date_creation'])); ?></dd>
                        <dt class="col-sm-3">Raison</dt>
                        <dd class="col-sm-9"><?php echo forum_escape($report['raison']); ?></dd>
                        <dt class="col-sm-3">Détails</dt>
                        <dd class="col-sm-9"><?php echo nl2br(forum_escape($report['description'] ?? 'Aucun détail fourni.')); ?></dd>
                        <dt class="col-sm-3">Statut</dt>
                        <dd class="col-sm-9"><?php echo forum_escape($report['statut']); ?></dd>
                    </dl>
                </div>
            </section>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h4">Contenu signalé</h2>
                    <?php if ($report['question_id'] && $report['question_titre'] !== null): ?>
                        <h3 class="h5"><?php echo forum_escape($report['question_titre']); ?></h3>
                        <p class="mb-0"><?php echo nl2br(forum_escape($report['question_contenu'])); ?></p>
                    <?php endif; ?>
                    <?php if ($report['reponse_id'] && $report['reponse_contenu'] !== null): ?>
                        <p class="mb-0"><?php echo nl2br(forum_escape($report['reponse_contenu'])); ?></p>
                    <?php elseif (!$report['question_titre'] && !$report['reponse_contenu']): ?>
                        <p class="text-muted mb-0">Le contenu n’est plus disponible.</p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (in_array($report['statut'], ['en_attente', 'pending'], true)): ?>
                <form method="POST" class="d-flex gap-2 flex-wrap">
                    <input type="hidden" name="csrf_token" value="<?php echo forum_admin_csrf_token(); ?>">
                    <button type="submit" name="action" value="accept" class="btn btn-danger"
                            onclick="return confirm('Supprimer définitivement le contenu signalé ?')">
                        Accepter et supprimer le contenu
                    </button>
                    <button type="submit" name="action" value="refuse" class="btn btn-success">
                        Refuser le signalement
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
