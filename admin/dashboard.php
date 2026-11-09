<?php
require('../actions/database.php');

// Vérifier si admin
if (!isset($_SESSION['auth']) || $_SESSION['auth'] == false || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Stats
$stats = array();

// Nombre d'utilisateurs
$stmt = $bdb->query("SELECT COUNT(*) FROM users");
$stats['users'] = $stmt->fetch()[0];

// Nombre de questions
$stmt = $bdb->query("SELECT COUNT(*) FROM questions");
$stats['questions'] = $stmt->fetch()[0];

// Nombre de réponses
$stmt = $bdb->query("SELECT COUNT(*) FROM reponses");
$stats['reponses'] = $stmt->fetch()[0];

// Signalements en attente
$stmt = $bdb->query("SELECT COUNT(*) FROM signalements WHERE statut = 'en_attente'");
$stats['signalements'] = $stmt->fetch()[0];

// Derniers signalements
$stmt = $bdb->prepare("SELECT s.*, u.pseudo FROM signalements s LEFT JOIN users u ON s.utilisateur_id = u.id ORDER BY s.date_creation DESC LIMIT 10");
$stmt->execute();
$signalements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">🔐 Panneau d'Administration</h2>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Utilisateurs</h5>
                        <p class="card-text" style="font-size: 2rem; font-weight: bold;"><?php echo $stats['users']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Questions</h5>
                        <p class="card-text" style="font-size: 2rem; font-weight: bold;"><?php echo $stats['questions']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Réponses</h5>
                        <p class="card-text" style="font-size: 2rem; font-weight: bold;"><?php echo $stats['reponses']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Signalements</h5>
                        <p class="card-text" style="font-size: 2rem; font-weight: bold;"><?php echo $stats['signalements']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signalements -->
        <h3>Signalements en attente</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Auteur</th>
                    <th>Raison</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($signalements as $sig): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sig['pseudo']); ?></td>
                    <td><?php echo htmlspecialchars(substr($sig['raison'], 0, 50)); ?></td>
                    <td>
                        <?php
                        if ($sig['question_id']) echo "Question";
                        else if ($sig['reponse_id']) echo "Réponse";
                        ?>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($sig['date_creation'])); ?></td>
                    <td>
                        <a href="manage-report.php?id=<?php echo $sig['id']; ?>" class="btn btn-sm btn-info">Voir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="../index.php" class="btn btn-secondary mt-3">Retour au forum</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
