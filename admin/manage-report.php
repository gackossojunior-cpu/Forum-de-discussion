<?php
require('../actions/database.php');

// Vérifier si admin
if (!isset($_SESSION['auth']) || $_SESSION['auth'] == false || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$report_id) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer le signalement
$stmt = $bdb->prepare("SELECT s.*, u.pseudo, q.titre as question_titre, r.contenu as reponse_contenu FROM signalements s LEFT JOIN users u ON s.utilisateur_id = u.id LEFT JOIN questions q ON s.question_id = q.id LEFT JOIN reponses r ON s.reponse_id = r.id WHERE s.id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    die('Signalement introuvable');
}

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $newStatus = '';

    if ($action == 'accept') {
        $newStatus = 'accepte';
        // Supprimer le contenu signalé
        if ($report['question_id']) {
            $bdb->prepare("DELETE FROM questions WHERE id = ?")->execute([$report['question_id']]);
        } elseif ($report['reponse_id']) {
            $bdb->prepare("DELETE FROM reponses WHERE id = ?")->execute([$report['reponse_id']]);
        }
    } elseif ($action == 'refuse') {
        $newStatus = 'refuse';
    }

    if ($newStatus) {
        $stmt = $bdb->prepare("UPDATE signalements SET statut = ? WHERE id = ?");
        $stmt->execute([$newStatus, $report_id]);
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h2>Gestion du signalement #<?php echo $report_id; ?></h2>

        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <p><strong>Signalé par:</strong> <?php echo htmlspecialchars($report['pseudo']); ?></p>
            <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($report['date_creation'])); ?></p>
            <p><strong>Raison:</strong> <?php echo htmlspecialchars($report['raison']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($report['description']); ?></p>
            <p><strong>Statut:</strong> <span style="background: #ffc107; padding: 4px 8px; border-radius: 4px;"><?php echo $report['statut']; ?></span></p>

            <hr>

            <h5>Contenu signalé:</h5>
            <?php if ($report['question_titre']): ?>
                <p><strong>Question:</strong> <?php echo htmlspecialchars($report['question_titre']); ?></p>
            <?php elseif ($report['reponse_contenu']): ?>
                <p><strong>Réponse:</strong> <?php echo htmlspecialchars($report['reponse_contenu']); ?></p>
            <?php endif; ?>
        </div>

        <form method="POST" style="display: flex; gap: 10px;">
            <button type="submit" name="action" value="accept" class="btn btn-danger">Accepter et Supprimer</button>
            <button type="submit" name="action" value="refuse" class="btn btn-success">Refuser</button>
            <a href="dashboard.php" class="btn btn-secondary">Retour</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
