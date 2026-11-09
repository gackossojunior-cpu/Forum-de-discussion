<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../../includes/head.php'; ?>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <main class="container page-shell mt-5 mb-5" id="main-content">
        <a href="index.php" class="text-decoration-none mb-4 d-inline-block">
            <i class="fas fa-arrow-left"></i> Retour aux questions
        </a>

        <article class="question-card question-detail-card">
            <div class="question-header" style="margin-bottom: 24px;">
                <h1 class="question-title mb-0">
                    <?php echo forum_escape($question['titre']); ?>
                </h1>
            </div>

            <div class="question-meta mb-4">
                <span class="meta-item">
                    <i class="fas fa-user-circle"></i>
                    <a href="user-profile.php?id=<?php echo $question['user_id']; ?>">
                        <?php echo htmlspecialchars($question['pseudo']); ?>
                    </a>
                </span>
                <span class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('d/m/Y à H:i', strtotime($question['date_creation'])); ?>
                </span>
            </div>

            <div class="question-content mb-4">
                <?php echo nl2br(forum_escape($question['contenu'])); ?>
            </div>

            <!-- Votes et Signalement -->
            <div class="question-actions" style="margin-bottom: 20px;">
                <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
                    <a href="actions/voteAction.php?type=like&question_id=<?php echo $question_id; ?>">
                        <i class="fas fa-thumbs-up"></i> <?php echo $votes_question['like']; ?>
                    </a>
                    <a href="actions/voteAction.php?type=dislike&question_id=<?php echo $question_id; ?>">
                        <i class="fas fa-thumbs-down"></i> <?php echo $votes_question['dislike']; ?>
                    </a>
                    <button type="button" class="inline-action" data-bs-toggle="modal" data-bs-target="#report-modal">
                        <i class="fas fa-flag" aria-hidden="true"></i> Signaler
                    </button>
                <?php else: ?>
                    <span><i class="fas fa-thumbs-up"></i> <?php echo $votes_question['like']; ?></span>
                    <span><i class="fas fa-thumbs-down"></i> <?php echo $votes_question['dislike']; ?></span>
                <?php endif; ?>

                <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] && $_SESSION['id'] == $question['user_id']): ?>
                    <a href="edit-question.php?id=<?php echo $question_id; ?>" style="margin-left: auto;">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="actions/deleteQuestionAction.php?id=<?php echo $question_id; ?>" class="btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette question?')">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                <?php endif; ?>
            </div>
        </article>

        <h2 class="mt-5 mb-4">
            <i class="fas fa-comments"></i> Réponses
            <span class="reply-count" data-reply-count>
                <?php echo count($replies); ?>
            </span>
        </h2>

        <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
        <div class="form-section mt-4 mb-5" id="reply-section">
            <h5 class="mb-3"><i class="fas fa-pen"></i> Votre réponse</h5>
            <form method="POST" action="actions/publishReplyAction.php" data-async-publish="reply">
                <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                <div class="alert" data-form-status hidden></div>
                <div class="form-group">
                    <textarea class="form-control" name="contenu" rows="5" placeholder="Partagez votre réponse..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" name="reply" value="1">
                    <i class="fas fa-paper-plane"></i> Publier la réponse
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-5">
            <i class="fas fa-info-circle"></i>
            <a href="login.php">Connectez-vous</a> pour publier une réponse.
        </div>
        <?php endif; ?>

        <div class="mt-4" id="replies-list" data-question-id="<?php echo (int)$question_id; ?>">
            <?php if (empty($replies)): ?>
            <div class="empty-state" id="replies-empty-state">
                <i class="fas fa-inbox"></i>
                <p>Aucune réponse pour le moment. Soyez le premier à répondre!</p>
                <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
                <a href="#reply-section" class="btn btn-primary btn-sm mt-3">Écrire une réponse</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                <div class="response-card" data-reply-id="<?php echo (int)$reply['id']; ?>">
                    <div class="response-meta">
                        <strong>
                            <i class="fas fa-user-circle"></i>
                            <a href="user-profile.php?id=<?php echo $reply['user_id']; ?>">
                                <?php echo forum_escape($reply['pseudo']); ?>
                            </a>
                        </strong>
                        <span style="margin-left: 12px;">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo date('d/m/Y à H:i', strtotime($reply['date_creation'])); ?>
                        </span>
                    </div>
                    <div class="response-content">
                        <?php echo nl2br(forum_escape($reply['contenu'])); ?>
                    </div>

                    <!-- Votes pour les réponses -->
                    <?php
                    $votes_reply = [
                        'like' => (int)$reply['like_count'],
                        'dislike' => (int)$reply['dislike_count'],
                    ];
                    ?>
                    <div style="display: flex; gap: 12px; margin: 12px 0; padding-top: 12px; border-top: 1px solid rgba(0,0,0,0.05);">
                        <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
                            <a href="actions/voteAction.php?type=like&reponse_id=<?php echo $reply['id']; ?>&question_id=<?php echo $question_id; ?>" style="color: var(--primary); text-decoration: none; font-size: 0.95rem;">
                                <i class="fas fa-thumbs-up"></i> <?php echo $votes_reply['like']; ?>
                            </a>
                            <a href="actions/voteAction.php?type=dislike&reponse_id=<?php echo $reply['id']; ?>&question_id=<?php echo $question_id; ?>" style="color: var(--danger); text-decoration: none; font-size: 0.95rem;">
                                <i class="fas fa-thumbs-down"></i> <?php echo $votes_reply['dislike']; ?>
                            </a>
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-size: 0.95rem;">
                                <i class="fas fa-thumbs-up"></i> <?php echo $votes_reply['like']; ?>
                            </span>
                            <span style="color: var(--text-muted); font-size: 0.95rem;">
                                <i class="fas fa-thumbs-down"></i> <?php echo $votes_reply['dislike']; ?>
                            </span>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['auth']) && $_SESSION['auth'] && $_SESSION['id'] == $reply['user_id']): ?>
                            <a href="actions/deleteReplyAction.php?id=<?php echo $reply['id']; ?>" class="btn-danger" style="margin-left: auto;" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réponse?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de signalement -->
    <div class="modal fade" id="report-modal" tabindex="-1" aria-labelledby="report-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content report-modal-content">
                <div class="modal-header">
                    <div>
                        <span class="eyebrow">Modération</span>
                        <h2 class="modal-title" id="report-modal-title">Signaler cette question</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Aidez-nous à maintenir des échanges utiles et respectueux.</p>
            <form method="POST" action="actions/reportAction.php">
                <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                <div class="form-group">
                    <label for="report-reason" class="form-label">Raison du signalement</label>
                    <select name="raison" id="report-reason" class="form-control" required>
                        <option value="">-- Sélectionnez une raison --</option>
                        <option>Spam</option>
                        <option>Contenu offensant</option>
                        <option>Hors sujet</option>
                        <option>Doublon</option>
                        <option>Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="report-description" class="form-label">Détails <span class="text-muted">(optionnel)</span></label>
                    <textarea name="description" id="report-description" class="form-control" rows="3" placeholder="Décrivez le problème…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Envoyer le signalement</button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/live-forms.js?v=1" defer></script>
</body>
</html>
