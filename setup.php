<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f5f7">
    <script>
        (() => {
            try {
                const savedTheme = localStorage.getItem('forum-theme');
                document.documentElement.dataset.theme = savedTheme
                    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            } catch {
                document.documentElement.dataset.theme = window.matchMedia('(prefers-color-scheme: dark)').matches
                    ? 'dark'
                    : 'light';
            }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css?v=4">
    <script src="assets/theme.js" defer></script>
    <title>Configuration du Forum</title>
</head>
<body>
    <div class="container">
        <div class="auth-container" style="margin-top: 40px; max-width: 500px;">
            <button type="button" class="theme-toggle setup-theme-toggle" data-theme-toggle aria-label="Activer le mode sombre" aria-pressed="false">
                <i class="fas fa-moon theme-icon-dark" aria-hidden="true"></i>
                <i class="fas fa-sun theme-icon-light" aria-hidden="true"></i>
                <span class="theme-toggle-label">Thème</span>
            </button>
            <h2 class="text-center mb-4">
                <i class="fas fa-wrench"></i> Installation du Forum
            </h2>

            <div id="loading" class="text-center" style="display: none;">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p>Configuration en cours...</p>
            </div>

            <div id="content" class="text-center">
                <p class="text-muted mb-4">
                    <i class="fas fa-database"></i><br>
                    Cliquez sur le bouton ci-dessous pour initialiser la base de données du forum.
                </p>
                <button type="button" class="btn btn-primary" onclick="setupDatabase()">
                    <i class="fas fa-check-circle"></i> Initialiser la base de données
                </button>
            </div>

            <div id="result" style="display: none; margin-top: 24px;">
                <div id="success" style="display: none;">
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <strong>Succès!</strong><br>
                        La base de données a été configurée avec succès.
                    </div>
                    <a href="index.php" class="btn btn-primary w-100">
                        <i class="fas fa-home"></i> Aller au forum
                    </a>
                </div>

                <div id="error" style="display: none;">
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Erreur!</strong><br>
                        <span id="error-msg"></span>
                    </div>
                    <button type="button" class="btn btn-secondary w-100" onclick="location.reload()">
                        <i class="fas fa-redo"></i> Réessayer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setupDatabase() {
            document.getElementById('content').style.display = 'none';
            document.getElementById('loading').style.display = 'block';

            fetch('setup-action.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('result').style.display = 'block';

                    if (data.success) {
                        document.getElementById('success').style.display = 'block';
                    } else {
                        document.getElementById('error').style.display = 'block';
                        document.getElementById('error-msg').textContent = data.error || 'Une erreur est survenue';
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('result').style.display = 'block';
                    document.getElementById('error').style.display = 'block';
                    document.getElementById('error-msg').textContent = error.message;
                });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
