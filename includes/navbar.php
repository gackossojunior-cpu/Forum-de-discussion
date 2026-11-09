<?php $appPrefix = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin' ? '../' : ''; ?>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid navbar-inner">
    <a class="navbar-brand" href="<?php echo $appPrefix; ?>index.php">
      <span class="brand-mark"><i class="fas fa-comments"></i></span>
      <span>Forum<span class="brand-period">.</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" href="<?php echo $appPrefix; ?>index.php">
            <i class="fas fa-home"></i> Accueil
          </a>
        </li>
        <?php if(isset($_SESSION['auth']) && $_SESSION['auth']): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo $appPrefix; ?>publish-question.php">
              <i class="fas fa-plus-circle"></i> Nouvelle question
            </a>
          </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-auto">
        <?php if(isset($_SESSION['auth']) && $_SESSION['auth']): ?>
          <!-- Notifications -->
          <li class="nav-item">
            <a class="nav-link" href="<?php echo $appPrefix; ?>notifications.php">
              <i class="fas fa-bell"></i> Notifications
            </a>
          </li>
          <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo $appPrefix; ?>admin/dashboard.php">
                <i class="fas fa-shield-alt"></i> Admin
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['pseudo']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="<?php echo $appPrefix; ?>user-profile.php?id=<?php echo $_SESSION['id']; ?>">
                <i class="fas fa-user"></i> Mon profil
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?php echo $appPrefix; ?>actions/logOut.php">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
              </a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo $appPrefix; ?>login.php">
              <i class="fas fa-sign-in-alt"></i> Connexion
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo $appPrefix; ?>signup.php">
              <i class="fas fa-user-plus"></i> Inscription
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>