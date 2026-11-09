<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../../includes/head.php'; ?>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-5 mb-5">
        <a href="index.php" class="text-decoration-none mb-4 d-inline-block">
            <i class="fas fa-arrow-left"></i> Retour aux questions
        </a>

        <div class="question-card">
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
                    <a href="#report" onclick="document.getElementById('report-modal').style.display='block'">
                        <i class="fas fa-flag"></i> Signaler
                    </a>
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
        </div>

        <h2 class="mt-5 mb-4">
            <i class="fas fa-comments"></i> Réponses
            <span style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 4px 12px; border-radius: 20px; font-size: 1rem; margin-left: 10px;">
                <?php echo count($replies); ?>
            </span>
        </h2>

        <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
        <div class="form-section mt-4 mb-5">
            <h5 class="mb-3"><i class="fas fa-pen"></i> Votre réponse</h5>
            <form method="POST" action="actions/publishReplyAction.php">
                <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                <div class="form-group">
                    <textarea class="form-control" name="contenu" rows="5" placeholder="Partagez votre réponse..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" name="reply">
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

        <div class="mt-4">
            <?php if (empty($replies)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Aucune réponse pour le moment. Soyez le premier à répondre!</p>
                <?php if (isset($_SESSION['auth']) && $_SESSION['auth']): ?>
                <a href="#reply-section" class="btn btn-primary btn-sm mt-3">Écrire une réponse</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                <div class="response-card">
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
    </div>

    <!-- Modal de signalement -->
    <div id="report-modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); animation: fadeIn 0.3s ease;">
        <div style="background-color:#fefefe; margin:10% auto; padding:30px; border:none; width:90%; max-width:500px; border-radius:12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <span onclick="document.getElementById('report-modal').style.display='none'" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer; transition: color 0.3s;">&times;</span>
            <h5><i class="fas fa-flag"></i> Signaler cette question</h5>
            <p style="color: var(--text-muted); margin-bottom: 20px;">Aidez-nous à maintenir la qualité du forum.</p>
            <form method="POST" action="actions/reportAction.php">
                <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-exclamation-triangle"></i> Raison du signalement</label>
                    <select name="raison" class="form-control" required>
                        <option value="">-- Sélectionnez une raison --</option>
                        <option>Spam</option>
                        <option>Contenu offensant</option>
                        <option>Hors sujet</option>
                        <option>Doublon</option>
                        <option>Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-comments"></i> Détails (optionnel)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Décrivez le problème..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-send"></i> Envoyer le signalement
                    </button>
                    <button type="button" onclick="document.getElementById('report-modal').style.display='none'" class="btn btn-secondary">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
