<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../../includes/head.php'; ?>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container forum-home mt-5 mb-5">
        <div class="home-heading">
            <div>
                <span class="eyebrow">La communauté s'entraide</span>
                <h2 class="mb-2">
                    <i class="fas fa-comments"></i> Forum de Discussion
                </h2>
                <p class="text-muted mb-0">Posez vos questions et partagez vos connaissances</p>
            </div>
            <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
            <a href="publish-question.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Nouvelle question
            </a>
            <?php endif; ?>
        </div>

        <!-- Barre de recherche et catégories -->
        <div class="search-section mb-4">
            <div class="card search-panel border-0 shadow-sm p-4 mb-4">
                <form class="d-flex gap-2 flex-column flex-md-row" method="GET">
                    <input type="text" class="form-control" placeholder="🔍 Rechercher une question..." name="search" value="<?php echo forum_escape($search); ?>" style="flex: 1;">
                    <select name="categorie" class="form-control" style="min-width: 200px;">
                        <option value="0"><i class="fas fa-list"></i> Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categorie == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                        <i class="fas fa-search"></i> Chercher
                    </button>
                    <?php if (!empty($search) || $categorie): ?>
                    <a href="index.php" class="btn btn-outline-secondary" style="white-space: nowrap;">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Résultats de la recherche -->
        <?php if (!empty($search) || $categorie): ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle"></i>
            <?php if (!empty($search) && $categorie): ?>
                Résultats pour "<strong><?php echo forum_escape($search); ?></strong>" dans <strong><?php echo forum_escape($categories[array_search($categorie, array_column($categories, 'id'))]['nom'] ?? ''); ?></strong>
            <?php elseif (!empty($search)): ?>
                Résultats pour "<strong><?php echo forum_escape($search); ?></strong>"
            <?php else: ?>
                Catégorie: <strong><?php echo forum_escape($categories[array_search($categorie, array_column($categories, 'id'))]['nom'] ?? ''); ?></strong>
            <?php endif; ?>
            (<?php echo count($questions); ?> résultat<?php echo count($questions) > 1 ? 's' : ''; ?>)
        </div>
        <?php endif; ?>

        <!-- Liste des questions -->
        <?php if (empty($questions)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>
                <?php if (!empty($search)): ?>
                    Aucune question trouvée pour votre recherche.
                <?php else: ?>
                    Aucune question pour le moment.
                    <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
                    <br><a href="publish-question.php" class="btn btn-primary btn-sm mt-3">Soyez le premier à en poser une!</a>
                    <?php else: ?>
                    <br><a href="login.php" class="btn btn-primary btn-sm mt-3">Connectez-vous</a> pour poser une question.
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </div>
        <?php else: ?>
            <?php foreach ($questions as $question): ?>
            <div class="question-card">
                <div class="question-header">
                    <div>
                        <h3 class="question-title">
                            <a href="question-detail.php?id=<?php echo $question['id']; ?>">
                                <?php echo forum_escape($question['titre']); ?>
                            </a>
                        </h3>
                        <div class="question-meta">
                            <span class="meta-item">
                                <i class="fas fa-user-circle"></i>
                                <a href="user-profile.php?id=<?php echo $question['utilisateur_id']; ?>">
                                    <?php echo htmlspecialchars($question['pseudo']); ?>
                                </a>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($question['date_creation'])); ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-comments"></i>
                                <?php echo $question['reponses_count']; ?> répon<?php echo $question['reponses_count'] > 1 ? 'ses' : 'se'; ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($question['categorie']): ?>
                    <span class="category-badge">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($question['categorie']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <p class="question-content">
                    <?php echo forum_excerpt($question['contenu']); ?>
                </p>
                <div class="question-actions">
                    <a href="question-detail.php?id=<?php echo $question['id']; ?>">
                        <i class="fas fa-eye"></i> Voir la question
                    </a>
                    <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] && $_SESSION['id'] == $question['utilisateur_id']): ?>
                        <a href="edit-question.php?id=<?php echo $question['id']; ?>">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="actions/deleteQuestionAction.php?id=<?php echo $question['id']; ?>" class="btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette question?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
