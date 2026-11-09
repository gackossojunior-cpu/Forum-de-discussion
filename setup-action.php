<?php
header('Content-Type: application/json');

try {
    define('FORUM_BOOTSTRAPPED', true);
    $databaseConfig = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $databaseConfig['host'],
        $databaseConfig['port'],
        $databaseConfig['database']
    );
    $bdb = new PDO($dsn, $databaseConfig['username'], $databaseConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $bdb->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pseudo VARCHAR(255) UNIQUE NOT NULL,
        nom VARCHAR(255) NOT NULL,
        prenom VARCHAR(255) NOT NULL,
        mdp VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'user',
        date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $existingColumns = $bdb->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN, 0);

    foreach (['lastname' => 'nom', 'firstname' => 'prenom', 'password' => 'mdp'] as $oldColumn => $newColumn) {
        if (in_array($oldColumn, $existingColumns, true) && !in_array($newColumn, $existingColumns, true)) {
            $bdb->exec("ALTER TABLE users CHANGE `$oldColumn` `$newColumn` VARCHAR(255) NOT NULL");
        }
    }

    $existingColumns = $bdb->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('nom', $existingColumns, true)) {
        $bdb->exec("ALTER TABLE users ADD COLUMN nom VARCHAR(255) NOT NULL DEFAULT '' AFTER pseudo");
    }
    if (!in_array('prenom', $existingColumns, true)) {
        $bdb->exec("ALTER TABLE users ADD COLUMN prenom VARCHAR(255) NOT NULL DEFAULT '' AFTER nom");
    }
    if (!in_array('mdp', $existingColumns, true)) {
        $bdb->exec("ALTER TABLE users ADD COLUMN mdp VARCHAR(255) NOT NULL DEFAULT '' AFTER prenom");
    }
    if (!in_array('role', $existingColumns, true)) {
        $bdb->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'");
    }

    $bdb->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        description TEXT,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $checkCategories = $bdb->query("SELECT COUNT(*) FROM categories");
    if ($checkCategories->fetchColumn() == 0) {
        $categories = [
            ['Technique', 'Problèmes et questions techniques'],
            ['Général', 'Discussions générales'],
            ['Aide', 'Demandes d\'aide et support'],
            ['Annonces', 'Annonces importantes'],
            ['Autre', 'Autres sujets']
        ];

        $stmt = $bdb->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
    }

    $bdb->exec("CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(255) NOT NULL,
        contenu TEXT NOT NULL,
        categorie_id INT,
        utilisateur_id INT NOT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (categorie_id) REFERENCES categories(id),
        FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $bdb->exec("CREATE TABLE IF NOT EXISTS reponses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contenu TEXT NOT NULL,
        question_id INT NOT NULL,
        utilisateur_id INT NOT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
        FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $bdb->exec("CREATE TABLE IF NOT EXISTS votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id INT NOT NULL,
        question_id INT,
        reponse_id INT,
        type VARCHAR(50) NOT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
        FOREIGN KEY (reponse_id) REFERENCES reponses(id) ON DELETE CASCADE
    )");

    $bdb->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT,
        reponse_id INT,
        utilisateur_id INT NOT NULL,
        raison VARCHAR(255) NOT NULL,
        description TEXT,
        statut VARCHAR(50) DEFAULT 'pending',
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
        FOREIGN KEY (reponse_id) REFERENCES reponses(id) ON DELETE CASCADE,
        FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $bdb->exec("CREATE TABLE IF NOT EXISTS signalements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NULL,
        reponse_id INT NULL,
        utilisateur_id INT NOT NULL,
        raison VARCHAR(255) NOT NULL,
        description TEXT,
        statut VARCHAR(50) NOT NULL DEFAULT 'en_attente',
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
        FOREIGN KEY (reponse_id) REFERENCES reponses(id) ON DELETE CASCADE,
        FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $reportsTableExists = $bdb->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'reports'"
    )->fetchColumn();
    if ($reportsTableExists) {
        $bdb->exec("INSERT INTO signalements
            (question_id, reponse_id, utilisateur_id, raison, description, statut, date_creation)
            SELECT source.question_id, source.reponse_id, source.utilisateur_id, source.raison, source.description,
                CASE source.statut
                    WHEN 'pending' THEN 'en_attente'
                    WHEN 'accepted' THEN 'accepte'
                    WHEN 'refused' THEN 'refuse'
                    ELSE statut
                END,
                source.date_creation
            FROM reports AS source
            WHERE NOT EXISTS (
                SELECT 1 FROM signalements AS existing
                WHERE existing.utilisateur_id = source.utilisateur_id
                    AND existing.question_id <=> source.question_id
                    AND existing.reponse_id <=> source.reponse_id
                    AND existing.raison = source.raison
                    AND existing.description <=> source.description
                    AND existing.date_creation = source.date_creation
            )");
    }

    $bdb->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id INT NOT NULL,
        titre VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        lue INT DEFAULT 0,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
