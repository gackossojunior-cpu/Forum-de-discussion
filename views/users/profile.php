<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../../includes/head.php'; ?>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-5 mb-5">
        <a href="index.php" class="text-decoration-none mb-4 d-inline-block">
            <i class="fas fa-arrow-left"></i> Retour
        </a>

        <div class="profile-header">
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 24px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h1 class="mb-2"><?php echo htmlspecialchars($user['pseudo']); ?></h1>
                    <p style="color: var(--text-muted); margin: 0;">
<?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </p>
                </div>
            </div>

            <div class="profile-stats mt-4">
                <div class="profile-stat">
                    <i class="fas fa-question-circle" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 8px;"></i>
                    <div class="number"><?php echo count($questions); ?></div>
                    <div class="label">Questions posées</div>
                </div>
                <div class="profile-stat">
                    <i class="fas fa-comment" style="font-size: 1.5rem; color: var(--success); margin-bottom: 8px;"></i>
                    <div class="number">                    <?php echo $repliesCount; ?></div>
                    <div class="label">Réponses données</div>
                </div>
                <div class="profile-stat">
                    <i class="fas fa-calendar-check" style="font-size: 1.5rem; color: var(--warning); margin-bottom: 8px;"></i>
                    <div class="number"><?php echo date('Y', strtotime($user['date_inscription'])); ?></div>
                    <div class="label">Membre depuis</div>
                </div>
            </div>
        </div>

        <h2 class="mt-5 mb-4">
            <i class="fas fa-list"></i> Questions de <?php echo htmlspecialchars($user['pseudo']); ?>
        </h2>

        <?php if (empty($questions)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Cet utilisateur n'a pas encore posé de questions.</p>
        </div>
        <?php else: ?>
            <?php foreach ($questions as $q): ?>
            <div class="question-card">
                <h3 class="question-title mb-2">
                    <a href="question-detail.php?id=<?php echo $q['id']; ?>">
                        <?php echo forum_escape($q['titre']); ?>
                    </a>
                </h3>
                <div class="question-meta mb-3">
                    <span class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d/m/Y à H:i', strtotime($q['date_creation'])); ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-comments"></i>
                        <?php echo $q['reponses_count']; ?> répon<?php echo $q['reponses_count'] > 1 ? 'ses' : 'se'; ?>
                    </span>
                </div>
                <div class="question-content mb-3">
                    <?php echo forum_excerpt($q['contenu']); ?>
                </div>
                <a href="question-detail.php?id=<?php echo $q['id']; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-eye"></i> Voir la question
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
