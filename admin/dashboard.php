<?php
require_once __DIR__ . '/../includes/admin.php';
forum_require_admin();

$stats = [
    'users' => (int)$bdb->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'questions' => (int)$bdb->query('SELECT COUNT(*) FROM questions')->fetchColumn(),
    'replies' => (int)$bdb->query('SELECT COUNT(*) FROM reponses')->fetchColumn(),
    'pending_reports' => (int)$bdb->query(
        "SELECT COUNT(*) FROM signalements WHERE statut IN ('en_attente', 'pending')"
    )->fetchColumn(),
];

$status = $_GET['status'] ?? 'en_attente';
$allowedStatuses = ['en_attente', 'accepte', 'refuse', 'all'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'en_attente';
}

$sql = 'SELECT s.id, s.question_id, s.reponse_id, s.raison, s.statut, s.date_creation,
               u.pseudo, q.titre AS question_titre, r.contenu AS reponse_contenu
        FROM signalements s
        LEFT JOIN users u ON u.id = s.utilisateur_id
        LEFT JOIN questions q ON q.id = s.question_id
        LEFT JOIN reponses r ON r.id = s.reponse_id';
if ($status === 'en_attente') {
    $sql .= " WHERE s.statut IN ('en_attente', 'pending')";
} elseif ($status !== 'all') {
    $sql .= ' WHERE s.statut = ?';
}
$sql .= ' ORDER BY s.date_creation DESC LIMIT 50';

$statement = $bdb->prepare($sql);
if ($status !== 'all' && $status !== 'en_attente') {
    $statement->execute([$status]);
} else {
    $statement->execute();
}
$reports = $statement->fetchAll();

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../includes/head.php'; ?>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <span class="eyebrow">Espace de modération</span>
                <h1 class="mb-1">Administration</h1>
                <p class="text-muted mb-0">Suivez l’activité du forum et traitez les signalements.</p>
            </div>
            <a href="../index.php" class="btn btn-secondary">Retour au forum</a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo forum_escape($flash['type']); ?>" role="status">
                <?php echo forum_escape($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-5">
            <?php foreach ([
                ['Utilisateurs', $stats['users'], 'fa-users'],
                ['Questions', $stats['questions'], 'fa-circle-question'],
                ['Réponses', $stats['replies'], 'fa-comments'],
                ['Signalements à traiter', $stats['pending_reports'], 'fa-flag'],
            ] as [$label, $value, $icon]): ?>
                <div class="col-12 col-sm-6 col-xl-3">
                    <section class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="admin-stat-icon"><i class="fas <?php echo $icon; ?>"></i></span>
                            <div>
                                <div class="text-muted"><?php echo $label; ?></div>
                                <div class="fs-3 fw-bold"><?php echo $value; ?></div>
                            </div>
                        </div>
                    </section>
                </div>
            <?php endforeach; ?>
        </div>

        <section class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h2 class="h4 mb-1">Signalements</h2>
                        <p class="text-muted mb-0">Les 50 signalements les plus récents.</p>
                    </div>
                    <nav class="btn-group" aria-label="Filtrer les signalements">
                        <?php foreach ([
                            'en_attente' => 'À traiter',
                            'accepte' => 'Acceptés',
                            'refuse' => 'Refusés',
                            'all' => 'Tous',
                        ] as $value => $label): ?>
                            <a class="btn btn-sm <?php echo $status === $value ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                               href="?status=<?php echo urlencode($value); ?>"><?php echo $label; ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <?php if (!$reports): ?>
                    <div class="empty-state py-5">
                        <i class="fas fa-shield-check"></i>
                        <p>Aucun signalement dans cette vue.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Contenu</th>
                                    <th scope="col">Signalé par</th>
                                    <th scope="col">Raison</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col"><span class="visually-hidden">Action</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <?php
                                    $contentTitle = $report['question_titre']
                                        ? forum_escape($report['question_titre'])
                                        : ($report['reponse_contenu'] ? forum_excerpt($report['reponse_contenu'], 90) : 'Contenu supprimé');
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge text-bg-light"><?php echo $report['question_id'] ? 'Question' : ($report['reponse_id'] ? 'Réponse' : 'Traité'); ?></span>
                                            <div class="fw-semibold mt-1"><?php echo $contentTitle; ?></div>
                                        </td>
                                        <td><?php echo forum_escape($report['pseudo'] ?? 'Compte supprimé'); ?></td>
                                        <td><?php echo forum_escape($report['raison']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($report['date_creation'])); ?></td>
                                        <td><?php echo forum_escape($report['statut']); ?></td>
                                        <td>
                                            <a href="manage-report.php?id=<?php echo (int)$report['id']; ?>" class="btn btn-sm btn-outline-primary">Examiner</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
