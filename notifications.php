<?php
require_once __DIR__ . '/actions/database.php';

if (!isset($_SESSION['auth']) || $_SESSION['auth'] == false) {
    header('Location: login.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['mark_read'])) {
    (new Forum\Repositories\NotificationRepository($bdb))->markAllRead((int)$_SESSION['id']);
    Forum\Infrastructure\Realtime::emit([
        'type' => 'notification:read',
        'userId' => (int)$_SESSION['id'],
        'notificationId' => null,
    ]);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;
    }
    header('Location: notifications.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['mark_one'])) {
    (new Forum\Repositories\NotificationRepository($bdb))->markRead(
        (int)$_POST['mark_one'],
        (int)$_SESSION['id']
    );
    Forum\Infrastructure\Realtime::emit([
        'type' => 'notification:read',
        'userId' => (int)$_SESSION['id'],
        'notificationId' => (int)$_POST['mark_one'],
    ]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
    exit;
}

// Récupérer les notifications
$stmt = $bdb->prepare("SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_creation DESC LIMIT 50");
$stmt->execute([$_SESSION['id']]);
$notifications = $stmt->fetchAll();
$unreadCount = count(array_filter($notifications, static function ($notification) {
    return empty($notification['lue']);
}));

// Preserve the legacy link while the visible control uses an asynchronous POST.
if (isset($_GET['mark_read'])) {
    (new Forum\Repositories\NotificationRepository($bdb))->markAllRead((int)$_SESSION['id']);
    header('Location: notifications.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="container page-shell mt-5 mb-5" id="main-content">
        <header class="page-heading">
            <div>
                <span class="eyebrow">Votre espace</span>
                <h1 class="page-title">Notifications.</h1>
                <p class="page-intro">Les dernières nouvelles de votre communauté.</p>
            </div>
            <div class="notification-controls">
                <button type="button" class="btn btn-notification-permission" data-browser-notification-permission>
                    <i class="fas fa-desktop" aria-hidden="true"></i>
                    <span data-browser-notification-label>Autoriser les alertes navigateur</span>
                </button>
                <form method="POST" action="notifications.php" data-mark-all-read-form <?php echo empty($notifications) || $unreadCount === 0 ? 'hidden' : ''; ?>>
                    <input type="hidden" name="mark_read" value="1">
                    <button type="submit" class="btn btn-mark-read" data-mark-all-read>
                        <i class="fas fa-check-double" aria-hidden="true"></i> Tout marquer comme lu
                    </button>
                </form>
                <p class="notification-feedback" data-notification-feedback role="status" hidden></p>
            </div>
        </header>

        <?php if (empty($notifications)): ?>
            <div class="empty-state" data-notifications-empty role="status">
                <i class="fas fa-bell-slash" aria-hidden="true"></i>
                <p>Vous n'avez aucune notification pour le moment.</p>
                <a href="index.php" class="btn btn-primary btn-sm mt-3">
                    <i class="fas fa-arrow-left"></i> Retour au forum
                </a>
            </div>
        <?php else: ?>
            <div class="notification-list" data-notification-list role="list" aria-live="polite" aria-relevant="additions">
                <?php
                foreach ($notifications as $notif):
                    $isUnread = empty($notif['lue']);
                ?>
                <article class="notification-item <?php echo $isUnread ? 'is-unread' : 'is-read'; ?>" role="listitem">
                    <div class="notification-icon" aria-hidden="true">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notification-body">
                            <div class="notification-title-row">
                                <h2 class="notification-title"><?php echo forum_escape($notif['titre']); ?></h2>
                                <?php if ($isUnread): ?>
                                    <span class="notification-badge">Nouveau</span>
                                <?php endif; ?>
                            </div>
                            <p class="notification-message"><?php echo forum_escape($notif['message']); ?></p>
                            <small class="notification-time">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <?php
                                $date = strtotime($notif['date_creation']);
                                $now = time();
                                $diff = $now - $date;

                                if ($diff < 60) echo 'À l\'instant';
                                elseif ($diff < 3600) echo 'Il y a ' . floor($diff/60) . ' min';
                                elseif ($diff < 86400) echo 'Il y a ' . floor($diff/3600) . 'h';
                                else echo date('d/m/Y H:i', $date);
                                ?>
                            </small>
                        </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour au forum
            </a>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/notifications.js?v=1" defer></script>
</body>
</html>
