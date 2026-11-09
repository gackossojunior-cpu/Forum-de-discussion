<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../../includes/head.php'; ?>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container forum-home mt-5 mb-5" id="main-content">
        <div class="home-heading">
            <div>
                <span class="eyebrow">Un lieu pour aller plus loin</span>
                <h1 class="mb-2">Les bonnes réponses<br>commencent ici.</h1>
                <p class="text-muted mb-0">Posez vos questions. Partagez ce que vous savez. Faites avancer les idées.</p>
            </div>
            <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
            <a href="publish-question.php" class="btn btn-primary">
                <i class="fas fa-plus" aria-hidden="true"></i> Poser une question
            </a>
            <?php else: ?>
            <a href="signup.php" class="btn btn-primary">
                Rejoindre la communauté <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
            <?php endif; ?>
        </div>

        <section class="search-section mb-4" aria-labelledby="browse-title">
            <div class="search-panel">
                <div class="search-heading">
                    <div>
                        <span class="eyebrow">Explorer</span>
                        <h2 id="browse-title">Trouvez votre sujet.</h2>
                    </div>
                    <span class="result-count"><?php echo count($questions); ?> sujet<?php echo count($questions) === 1 ? '' : 's'; ?></span>
                </div>
                <form class="search-form" method="GET">
                    <div class="search-input-wrap">
                        <label class="visually-hidden" for="forum-search">Rechercher dans les questions</label>
                        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" class="form-control" id="forum-search" placeholder="Rechercher une question…" name="search" value="<?php echo forum_escape($search); ?>">
                    </div>
                    <div class="search-select-wrap">
                        <label class="visually-hidden" for="forum-category">Filtrer par catégorie</label>
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        <select name="categorie" id="forum-category" class="form-control">
                        <option value="0">Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categorie == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo forum_escape($cat['nom']); ?>
                        </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Rechercher <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                    <?php if (!empty($search) || $categorie): ?>
                    <a href="index.php" class="btn btn-reset" aria-label="Effacer les filtres">
                        Effacer
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </section>

        <!-- Résultats de la recherche -->
        <?php if (!empty($search) || $categorie): ?>
        <div class="alert alert-info mb-4" role="status">
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
                <?php endif; ?>
            </p>
            <?php if (empty($search) && isset($_SESSION['auth']) && $_SESSION['auth']): ?>
                <a href="publish-question.php" class="btn btn-primary btn-sm">Soyez le premier à en poser une !</a>
            <?php elseif (empty($search)): ?>
                <a href="login.php" class="btn btn-primary btn-sm">Connectez-vous</a>
                <p class="empty-state-hint">pour poser une question.</p>
            <?php endif; ?>
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
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
