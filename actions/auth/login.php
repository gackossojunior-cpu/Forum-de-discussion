<?php
require_once __DIR__ . '/../database.php';

if (isset($_POST['validate'])) {
    if (!empty($_POST['pseudo']) && !empty($_POST['password'])) {
        $userPseudo = trim($_POST['pseudo']);
        $userPassword = $_POST['password'];

        $statement = $bdb->prepare('SELECT id, nom, prenom, pseudo, mdp, role FROM users WHERE pseudo = ?');
        $statement->execute([$userPseudo]);
        $user = $statement->fetch();

        if ($user !== false && password_verify($userPassword, $user['mdp'])) {
            session_regenerate_id(true);
            $_SESSION['auth'] = true;
            $_SESSION['id'] = $user['id'];
            $_SESSION['lastname'] = $user['nom'];
            $_SESSION['firstname'] = $user['prenom'];
            $_SESSION['pseudo'] = $user['pseudo'];
            $_SESSION['role'] = $user['role'] ?? 'user';

            header('Location: index.php');
            exit;
        }

        $errorMsg = $user === false ? 'Utilisateur non trouvé' : 'Mot de passe incorrect';
    } else {
        $errorMsg = 'Veuillez remplir tous les champs';
    }
}
