<?php
$appPrefix = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin' ? '../' : '';
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$unreadNotifications = 0;
if (!empty($_SESSION['auth'])) {
    $unreadNotifications = (new Forum\Repositories\NotificationRepository($bdb))
        ->unreadCount((int)$_SESSION['id']);
}
?>
<a class="skip-link" href="#main-content">Aller au contenu</a>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid navbar-inner">
    <a class="navbar-brand" href="<?php echo $appPrefix; ?>index.php" aria-label="Forum, accueil">
      <span class="brand-mark" aria-hidden="true"><i class="fas fa-comments"></i></span>
      <span>Forum<span class="brand-period">.</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" href="<?php echo $appPrefix; ?>index.php" <?php echo $currentPage === 'index.php' ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-house" aria-hidden="true"></i> Accueil
          </a>
        </li>
        <?php if(isset($_SESSION['auth']) && $_SESSION['auth']): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'publish-question.php' ? 'active' : ''; ?>" href="<?php echo $appPrefix; ?>publish-question.php" <?php echo $currentPage === 'publish-question.php' ? 'aria-current="page"' : ''; ?>>
              <i class="fas fa-plus" aria-hidden="true"></i> Nouvelle question
            </a>
          </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item">
          <button type="button" class="theme-toggle" data-theme-toggle aria-label="Activer le mode sombre" aria-pressed="false">
            <i class="fas fa-moon theme-icon-dark" aria-hidden="true"></i>
            <i class="fas fa-sun theme-icon-light" aria-hidden="true"></i>
            <span class="theme-toggle-label">Thème</span>
          </button>
        </li>
        <?php if(isset($_SESSION['auth']) && $_SESSION['auth']): ?>
          <li class="nav-item">
            <button type="button" class="sound-toggle" data-sound-toggle aria-pressed="true" aria-label="Désactiver la sonnerie des notifications">
              <i class="fas fa-volume-high" data-sound-icon aria-hidden="true"></i>
              <span data-sound-label>Sonnerie activée</span>
            </button>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'notifications.php' ? 'active' : ''; ?>" href="<?php echo $appPrefix; ?>notifications.php" <?php echo $currentPage === 'notifications.php' ? 'aria-current="page"' : ''; ?>>
              <i class="fas fa-bell" aria-hidden="true"></i> Notifications
              <span class="notification-count" data-notification-count aria-live="polite" aria-label="<?php echo $unreadNotifications; ?> notification<?php echo $unreadNotifications === 1 ? '' : 's'; ?> non lue<?php echo $unreadNotifications === 1 ? '' : 's'; ?>" <?php echo $unreadNotifications > 0 ? '' : 'hidden'; ?>>
                <?php echo $unreadNotifications; ?>
              </span>
            </a>
          </li>
          <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link <?php echo $currentPage === 'dashboard.php' || $currentPage === 'manage-report.php' ? 'active' : ''; ?>" href="<?php echo $appPrefix; ?>admin/dashboard.php">
                  <i class="fas fa-shield-halved" aria-hidden="true"></i> Admin
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-circle-user" aria-hidden="true"></i> <?php echo forum_escape($_SESSION['pseudo'] ?? 'Mon compte'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="<?php echo $appPrefix; ?>user-profile.php?id=<?php echo $_SESSION['id']; ?>">
                <i class="fas fa-user" aria-hidden="true"></i> Mon profil
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?php echo $appPrefix; ?>actions/logOut.php">
                <i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i> Déconnexion
              </a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>" href="<?php echo $appPrefix; ?>login.php" <?php echo $currentPage === 'login.php' ? 'aria-current="page"' : ''; ?>>
              <i class="fas fa-arrow-right-to-bracket" aria-hidden="true"></i> Connexion
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link nav-link-cta <?php echo $currentPage === 'signup.php' ? 'active' : ''; ?>" href="<?php echo $appPrefix; ?>signup.php" <?php echo $currentPage === 'signup.php' ? 'aria-current="page"' : ''; ?>>
              <i class="fas fa-user-plus" aria-hidden="true"></i> Inscription
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>