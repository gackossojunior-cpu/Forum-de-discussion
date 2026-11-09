<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../../includes/head.php'; ?>
<body class="messenger-page">
    <main class="messenger-app" id="main-content" data-messenger data-user-id="<?php echo (int)$_SESSION['id']; ?>" data-csrf-token="<?php echo forum_escape($messengerCsrfToken); ?>">
        <p class="messenger-error" data-messenger-error role="alert" hidden></p>
        <aside class="messenger-sidebar" aria-label="Conversations">
            <header class="messenger-sidebar-header">
                <a class="messenger-brand" href="index.php"><span class="brand-mark" aria-hidden="true"><i class="fas fa-comments"></i></span> Messages</a>
                <div class="messenger-account">
                    <span class="messenger-avatar" aria-hidden="true"><?php echo forum_escape(mb_strtoupper(mb_substr($_SESSION['pseudo'] ?? 'M', 0, 1))); ?></span>
                    <span><?php echo forum_escape($_SESSION['pseudo'] ?? 'Mon compte'); ?></span>
                    <a href="actions/logOut.php" class="messenger-logout">Déconnexion</a>
                </div>
            </header>

            <div class="messenger-sidebar-tools">
                <label class="messenger-search">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="visually-hidden">Rechercher un contact ou une conversation</span>
                    <input type="search" data-contact-search placeholder="Rechercher ou démarrer une discussion">
                </label>
                <button type="button" class="btn btn-primary messenger-new-chat" data-new-chat>
                    <i class="fas fa-pen-to-square" aria-hidden="true"></i> Nouvelle discussion
                </button>
                <div class="messenger-filter" role="tablist" aria-label="Filtrer les discussions">
                    <button type="button" class="is-active" data-filter="all" role="tab" aria-selected="true">Toutes</button>
                    <button type="button" data-filter="unread" role="tab" aria-selected="false">Non lues</button>
                </div>
            </div>

            <div class="conversation-list" data-conversation-list aria-live="polite"></div>
            <div class="messenger-sidebar-footer">
                <a href="community.php"><i class="fas fa-users" aria-hidden="true"></i> Ouvrir le forum</a>
            </div>
        </aside>

        <section class="messenger-thread" aria-label="Discussion">
            <header class="thread-header" data-thread-header hidden>
                <button type="button" class="thread-back" data-thread-back aria-label="Retour aux conversations"><i class="fas fa-arrow-left"></i></button>
                <span class="messenger-avatar" data-thread-avatar aria-hidden="true"></span>
                <div class="thread-identity">
                    <h1 data-thread-title></h1>
                    <p data-thread-status></p>
                </div>
                <span class="thread-encryption" title="Les messages sont enregistrés de manière sécurisée sur ce serveur. Le chiffrement de bout en bout n’est pas encore activé." aria-label="Chiffrement de bout en bout non disponible">
                    <i class="fas fa-lock" aria-hidden="true"></i> Protection du compte
                </span>
            </header>

            <div class="thread-empty" data-thread-empty>
                <span class="thread-empty-icon"><i class="fas fa-message" aria-hidden="true"></i></span>
                <h1>Vos conversations, au même endroit.</h1>
                <p>Choisissez une discussion ou démarrez-en une nouvelle.</p>
            </div>

            <div class="message-list" data-message-list role="log" aria-live="polite" aria-relevant="additions text" hidden></div>

            <form class="message-composer" data-message-form hidden>
                <label class="message-input-wrap">
                    <span class="visually-hidden">Votre message</span>
                    <textarea data-message-input rows="1" maxlength="5000" placeholder="Écrivez un message…" required></textarea>
                </label>
                <span class="typing-indicator" data-typing-indicator aria-live="polite"></span>
                <button type="submit" class="send-message-button" aria-label="Envoyer le message">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                </button>
            </form>
        </section>

        <dialog class="conversation-dialog" data-conversation-dialog>
            <form method="dialog" class="conversation-dialog-header">
                <div>
                    <span class="eyebrow">Nouvelle conversation</span>
                    <h2>À qui écrire ?</h2>
                </div>
                <button type="submit" class="dialog-close" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
            </form>
            <label class="messenger-search">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                <span class="visually-hidden">Rechercher un membre</span>
                <input type="search" data-dialog-search placeholder="Rechercher un membre">
            </label>
            <div class="conversation-mode">
                <button type="button" class="is-active" data-new-type="direct">Discussion privée</button>
                <button type="button" data-new-type="group">Créer un groupe</button>
            </div>
            <label class="group-title-field" data-group-title-field hidden>
                Nom du groupe
                <input type="text" maxlength="120" data-group-title placeholder="Ex. Équipe projet">
            </label>
            <div class="contact-list" data-contact-list></div>
            <p class="messenger-error" data-dialog-error role="alert" hidden></p>
            <button type="button" class="btn btn-primary conversation-create-button" data-create-conversation disabled>Continuer</button>
        </dialog>
    </main>
    <script src="assets/messenger.js?v=1" defer></script>
</body>
</html>
