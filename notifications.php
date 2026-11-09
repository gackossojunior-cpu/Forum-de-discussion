<?php
require('actions/database.php');

if (!isset($_SESSION['auth']) || $_SESSION['auth'] == false) {
    header('Location: login.php');
    exit;
}

// Récupérer les notifications
$stmt = $bdb->prepare("SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_creation DESC LIMIT 50");
$stmt->execute([$_SESSION['id']]);
$notifications = $stmt->fetchAll();

// Marquer comme lues
if (isset($_GET['mark_read'])) {
    $stmt = $bdb->prepare("UPDATE notifications SET lue = 1 WHERE utilisateur_id = ?");
    $stmt->execute([$_SESSION['id']]);
    header('Location: notifications.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5 mb-5">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 16px; flex-wrap: wrap;">
            <h2 class="mb-0">
                <i class="fas fa-bell"></i> Mes Notifications
            </h2>
            <?php if (!empty($notifications)): ?>
            <a href="?mark_read=1" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-check-double"></i> Tout marquer comme lu
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <p>Vous n'avez aucune notification pour le moment.</p>
                <a href="index.php" class="btn btn-primary btn-sm mt-3">
                    <i class="fas fa-arrow-left"></i> Retour au forum
                </a>
            </div>
        <?php else: ?>
            <div style="max-width: 700px;">
                <?php
                $unread_count = 0;
                foreach ($notifications as $notif):
                    if (!$notif['lue']) $unread_count++;
                ?>
                <div class="notification-item" style="background: <?php echo $notif['lue'] ? 'white' : 'linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%)'; ?>; border: 1px solid <?php echo $notif['lue'] ? '#e5e7eb' : '#c7d2fe'; ?>; border-left: 4px solid <?php echo $notif['lue'] ? '#d1d5db' : '#6366f1'; ?>; border-radius: 12px; padding: 20px; margin-bottom: 16px; transition: all 0.3s ease; box-shadow: <?php echo $notif['lue'] ? '0 1px 3px rgba(0,0,0,0.05)' : '0 4px 12px rgba(99, 102, 241, 0.1)'; ?>;">
                    <div style="display: flex; align-items: flex-start; gap: 16px;">
                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                                <h5 class="mb-0"><?php echo htmlspecialchars($notif['titre']); ?></h5>
                                <?php if (!$notif['lue']): ?>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-star"></i> Nouveau
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p style="color: var(--text); margin-bottom: 10px; line-height: 1.5;">
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </p>
                            <small style="color: var(--text-muted);">
                                <i class="fas fa-clock"></i>
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
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour au forum
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
